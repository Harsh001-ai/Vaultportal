<?php
namespace App\Services;

use App\Database;
use Exception;

class ExportProcessorService {
    /**
     * Inspect and summarize a selection of asset IDs before creating export
     */
    public static function reviewSelection(array $assetIds, int $adminId): array {
        if (empty($assetIds)) {
            return [
                'total_files' => 0,
                'total_uncompressed_bytes' => 0,
                'source_users_count' => 0,
                'type_breakdown' => [],
                'source_users' => [],
                'ineligible_assets' => [],
                'valid_assets' => [],
                'filename_conflicts' => []
            ];
        }

        // Sanitize IDs
        $cleanIds = array_map('intval', $assetIds);
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

        $sql = "SELECT a.*, u.name as uploader_name, u.email as uploader_email, c.name as collection_name
                FROM assets a
                JOIN users u ON a.uploader_id = u.id
                LEFT JOIN collections c ON a.collection_id = c.id
                WHERE a.id IN ($placeholders) AND a.deleted_at IS NULL";

        $rows = Database::fetchAll($sql, $cleanIds);

        $validAssets = [];
        $ineligible = [];
        $typeBreakdown = [];
        $sourceUsers = [];
        $totalBytes = 0;
        $nameCounts = [];

        foreach ($rows as $row) {
            if ($row['status'] !== 'ready') {
                $ineligible[] = [
                    'id' => $row['id'],
                    'filename' => $row['original_filename'],
                    'reason' => $row['status'] === 'quarantined' ? "Quarantined: {$row['quarantine_reason']}" : "Status: {$row['status']}"
                ];
                continue;
            }

            $validAssets[] = $row;
            $totalBytes += (int)$row['file_size_bytes'];
            $ext = strtolower($row['file_extension']);
            $typeBreakdown[$ext] = ($typeBreakdown[$ext] ?? 0) + 1;

            $uid = $row['uploader_id'];
            if (!isset($sourceUsers[$uid])) {
                $sourceUsers[$uid] = [
                    'id' => $uid,
                    'name' => $row['uploader_name'],
                    'email' => $row['uploader_email'],
                    'count' => 0
                ];
            }
            $sourceUsers[$uid]['count']++;

            $nameCounts[$row['original_filename']] = ($nameCounts[$row['original_filename']] ?? 0) + 1;
        }

        $conflicts = [];
        foreach ($nameCounts as $fname => $cnt) {
            if ($cnt > 1) {
                $conflicts[] = [
                    'filename' => $fname,
                    'count' => $cnt
                ];
            }
        }

        return [
            'total_files' => count($validAssets),
            'total_uncompressed_bytes' => $totalBytes,
            'source_users_count' => count($sourceUsers),
            'type_breakdown' => $typeBreakdown,
            'source_users' => array_values($sourceUsers),
            'ineligible_assets' => $ineligible,
            'valid_assets' => $validAssets,
            'filename_conflicts' => $conflicts
        ];
    }

    /**
     * Create and process export job
     */
    public static function createExport(array $assetIds, string $format, array $options, int $adminId): array {
        $review = self::reviewSelection($assetIds, $adminId);

        if (empty($review['valid_assets'])) {
            throw new Exception("No eligible assets selected for export.");
        }

        $validAssets = $review['valid_assets'];
        $appConfig = require __DIR__ . '/../../config/app.php';
        $exportTtl = $appConfig['limits']['export_token_ttl'];

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $downloadToken = bin2hex(random_bytes(32));
        $tokenExpiresAt = date('Y-m-d H:i:s', time() + $exportTtl);
        $exportName = !empty($options['export_name']) ? trim($options['export_name']) : ('Export_' . date('Ymd_His'));

        Database::beginTransaction();
        try {
            $jobId = Database::insert('export_jobs', [
                'uuid' => $uuid,
                'admin_id' => $adminId,
                'export_name' => $exportName,
                'format' => $format,
                'total_files' => count($validAssets),
                'total_uncompressed_bytes' => $review['total_uncompressed_bytes'],
                'archive_size_bytes' => 0,
                'download_token' => $downloadToken,
                'token_expires_at' => $tokenExpiresAt,
                'status' => 'packaging',
                'progress_percent' => 10,
                'export_notes' => $options['notes'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Package archive via ArchiveService
            $result = ArchiveService::createExportPackage($validAssets, $format, [
                'archive_name' => $exportName,
                'notes' => $options['notes'] ?? null,
                'include_manifest' => !empty($options['include_manifest'])
            ]);

            // Insert export items for complete attribution
            foreach ($result['items'] as $item) {
                Database::insert('export_items', [
                    'export_job_id' => $jobId,
                    'asset_id' => $item['asset_id'],
                    'source_user_id' => $item['source_user_id'],
                    'original_filename' => $item['original_filename'],
                    'archive_path' => $item['archive_path'],
                    'file_size_bytes' => $item['file_size_bytes'],
                    'sha256_checksum' => $item['sha256_checksum'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Update job to ready
            Database::update('export_jobs', [
                'status' => 'ready',
                'progress_percent' => 100,
                'archive_size_bytes' => $result['archive_size_bytes'],
                'archive_path' => $result['archive_path'],
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $jobId]);

            Database::commit();

            AuditService::log('export.create', 'export_job', (string)$jobId, [
                'name' => $exportName,
                'format' => $format,
                'files' => count($validAssets),
                'size' => $result['archive_size_bytes'],
                'source_users_count' => $review['source_users_count']
            ], $adminId);

            NotificationService::create(
                $adminId,
                'Export Package Ready',
                "Your export '{$exportName}' ({$result['total_files']} files) is ready to download.",
                'success',
                "/admin/exports"
            );

            return Database::fetch("SELECT * FROM export_jobs WHERE id = :id", ['id' => $jobId]);
        } catch (Exception $e) {
            Database::rollBack();
            if (isset($jobId)) {
                Database::update('export_jobs', [
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ], 'id = :id', ['id' => $jobId]);
            }
            throw $e;
        }
    }
}
