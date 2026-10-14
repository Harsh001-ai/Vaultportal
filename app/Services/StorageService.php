<?php
namespace App\Services;

use App\Database;
use Exception;

class StorageService {
    public static function storeAsset(array $validationResult, int $uploaderId, ?int $collectionId = null): array {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $uploadDir = $appConfig['storage']['uploads'];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate randomized storage filename: sha256(time() . random_bytes(32)) . ext
        $storedFilename = hash('sha256', microtime(true) . random_bytes(32)) . '.' . $validationResult['file_extension'];
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

        // Move uploaded file from temp to storage
        if (!move_uploaded_file($validationResult['temp_path'], $targetPath)) {
            // If moved in CLI or tests via copy/rename
            if (!copy($validationResult['temp_path'], $targetPath)) {
                throw new Exception("Failed to move uploaded asset to permanent secure storage.");
            }
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        Database::beginTransaction();
        try {
            // Insert asset
            $assetId = Database::insert('assets', [
                'uuid' => $uuid,
                'uploader_id' => $uploaderId,
                'collection_id' => $collectionId,
                'original_filename' => $validationResult['original_filename'],
                'stored_filename' => $storedFilename,
                'storage_path' => $targetPath,
                'mime_type' => $validationResult['mime_type'],
                'file_extension' => $validationResult['file_extension'],
                'file_size_bytes' => $validationResult['file_size_bytes'],
                'sha256_checksum' => $validationResult['sha256_checksum'],
                'status' => $validationResult['status'],
                'quarantine_reason' => $validationResult['quarantine_reason'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update user storage used bytes if not quarantined/failed
            if ($validationResult['status'] === 'ready') {
                Database::query(
                    "UPDATE users SET storage_used_bytes = storage_used_bytes + :bytes WHERE id = :id",
                    ['bytes' => $validationResult['file_size_bytes'], 'id' => $uploaderId]
                );
            }

            Database::commit();

            AuditService::log('asset.upload', 'asset', (string)$assetId, [
                'filename' => $validationResult['original_filename'],
                'size' => $validationResult['file_size_bytes'],
                'status' => $validationResult['status']
            ], $uploaderId);

            if ($validationResult['status'] === 'quarantined') {
                NotificationService::create(
                    $uploaderId,
                    'Asset Under Security Review',
                    "Your uploaded file '{$validationResult['original_filename']}' was flagged for quarantine: {$validationResult['quarantine_reason']}",
                    'warning'
                );
            } else {
                NotificationService::create(
                    $uploaderId,
                    'Upload Successful',
                    "Asset '{$validationResult['original_filename']}' has been safely uploaded and processed.",
                    'success'
                );
            }

            return Database::fetch("SELECT * FROM assets WHERE id = :id", ['id' => $assetId]);
        } catch (Exception $e) {
            Database::rollBack();
            if (file_exists($targetPath)) {
                @unlink($targetPath);
            }
            throw $e;
        }
    }

    public static function deleteAsset(int $assetId, int $userId, bool $isAdmin = false): bool {
        $asset = Database::fetch("SELECT * FROM assets WHERE id = :id AND deleted_at IS NULL", ['id' => $assetId]);
        if (!$asset) {
            throw new Exception("Asset not found or already deleted.");
        }

        if (!$isAdmin && (int)$asset['uploader_id'] !== $userId) {
            throw new Exception("Unauthorized to delete this asset.");
        }

        Database::beginTransaction();
        try {
            // Soft delete asset record
            Database::update('assets', [
                'deleted_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $assetId]);

            // Reclaim user storage if it was ready
            if ($asset['status'] === 'ready') {
                Database::query(
                    "UPDATE users SET storage_used_bytes = GREATEST(0, CAST(storage_used_bytes AS SIGNED) - :bytes) WHERE id = :id",
                    ['bytes' => $asset['file_size_bytes'], 'id' => $asset['uploader_id']]
                );
            }

            Database::commit();

            AuditService::log('asset.delete', 'asset', (string)$assetId, [
                'filename' => $asset['original_filename'],
                'size' => $asset['file_size_bytes'],
                'by_admin' => $isAdmin
            ], $userId);

            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
