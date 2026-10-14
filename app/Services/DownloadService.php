<?php
namespace App\Services;

use App\Database;
use App\Response;
use App\Session;
use Exception;

class DownloadService {
    /**
     * Download completed export archive via secure token
     */
    public static function deliverExport(string $token, bool $terminate = true): bool {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            AuditService::recordDownload(null, null, 0, 'Anonymous', 'failed', 'Unauthenticated download attempt.');
            http_response_code(401);
            if (!$terminate) throw new Exception("Authentication required to download export archives.");
            echo "Authentication required to download export archives.";
            exit;
        }

        $job = Database::fetch("SELECT * FROM export_jobs WHERE download_token = :token LIMIT 1", ['token' => $token]);

        if (!$job) {
            AuditService::recordDownload(null, null, $user['id'], $user['name'], 'failed', 'Invalid download token.');
            http_response_code(404);
            if (!$terminate) throw new Exception("Export archive not found or invalid token.");
            echo "Export archive not found or invalid token.";
            exit;
        }

        // Only admins can download administrative export packages
        if ($user['role'] !== 'admin') {
            AuditService::recordDownload($job['id'], null, $user['id'], $user['name'], 'failed', 'Access denied: non-admin attempted download.');
            http_response_code(403);
            if (!$terminate) throw new Exception("Access denied.");
            echo "Access denied.";
            exit;
        }

        // Check expiration
        if (strtotime($job['token_expires_at']) < time()) {
            Database::update('export_jobs', ['status' => 'expired'], 'id = :id', ['id' => $job['id']]);
            AuditService::recordDownload($job['id'], null, $user['id'], $user['name'], 'failed', 'Export download token expired.');
            http_response_code(410);
            if (!$terminate) throw new Exception("This download link has expired. Please generate a new export.");
            echo "This download link has expired. Please generate a new export.";
            exit;
        }

        if ($job['status'] !== 'ready' || empty($job['archive_path']) || !file_exists($job['archive_path'])) {
            AuditService::recordDownload($job['id'], null, $user['id'], $user['name'], 'failed', 'Archive file missing on disk.');
            http_response_code(404);
            if (!$terminate) throw new Exception("Export archive file is unavailable.");
            echo "Export archive file is unavailable.";
            exit;
        }

        // Record successful download audit event
        AuditService::recordDownload($job['id'], null, $user['id'], $user['name'], 'success');
        AuditService::log('export.download', 'export_job', (string)$job['id'], [
            'name' => $job['export_name'],
            'format' => $job['format'],
            'size' => $job['archive_size_bytes']
        ], $user['id']);

        $ext = pathinfo($job['archive_path'], PATHINFO_EXTENSION);
        $downloadName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $job['export_name']) . '.' . $ext;

        $mime = ($ext === 'json') ? 'application/json' : (($ext === 'csv') ? 'text/csv' : 'application/zip');
        if (!$terminate) {
            return true;
        }
        Response::download($job['archive_path'], $downloadName, $mime);
        return true;
    }

    /**
     * Download direct individual asset
     */
    public static function deliverAsset(int $assetId, array $user, bool $terminate = true): bool {
        $asset = Database::fetch("SELECT * FROM assets WHERE id = :id AND deleted_at IS NULL", ['id' => $assetId]);

        if (!$asset) {
            AuditService::recordDownload(null, $assetId, $user['id'], $user['name'], 'failed', 'Asset not found.');
            http_response_code(404);
            if (!$terminate) throw new Exception("Asset not found.");
            echo "Asset not found.";
            exit;
        }

        // Access check: must be owner or admin
        if ($user['role'] !== 'admin' && (int)$asset['uploader_id'] !== (int)$user['id']) {
            AuditService::recordDownload(null, $assetId, $user['id'], $user['name'], 'failed', 'Unauthorized asset access.');
            http_response_code(403);
            if (!$terminate) throw new Exception("Access denied to asset.");
            echo "Access denied to asset.";
            exit;
        }

        // Check quarantine
        if ($asset['status'] === 'quarantined' && $user['role'] !== 'admin') {
            AuditService::recordDownload(null, $assetId, $user['id'], $user['name'], 'failed', 'Attempted download of quarantined asset.');
            http_response_code(403);
            if (!$terminate) throw new Exception("This asset is currently quarantined for security review.");
            echo "This asset is currently quarantined for security review.";
            exit;
        }

        $filePath = $asset['storage_path'];
        if (!file_exists($filePath)) {
            // Fallback: Check if file exists in currently configured upload directory using stored_filename
            $appConfig = require __DIR__ . '/../../config/app.php';
            $fallbackPath = ($appConfig['storage']['uploads'] ?? '') . DIRECTORY_SEPARATOR . $asset['stored_filename'];
            if (file_exists($fallbackPath)) {
                $filePath = $fallbackPath;
                Database::update('assets', ['storage_path' => $filePath], 'id = :id', ['id' => $asset['id']]);
            }
        }

        if (!file_exists($filePath)) {
            AuditService::recordDownload(null, $assetId, $user['id'], $user['name'], 'failed', 'Asset file missing on disk.');
            http_response_code(404);
            if (!$terminate) throw new Exception("Asset file is missing from storage.");
            echo "Asset file is missing from storage.";
            exit;
        }

        // Record download event
        AuditService::recordDownload(null, $asset['id'], $user['id'], $user['name'], 'success');
        AuditService::log('asset.download', 'asset', (string)$asset['id'], [
            'filename' => $asset['original_filename'],
            'size' => $asset['file_size_bytes']
        ], $user['id']);

        if (!$terminate) {
            return true;
        }
        Response::download($filePath, $asset['original_filename'], $asset['mime_type']);
        return true;
    }
}
