<?php
namespace App\Services;

use ZipArchive;
use Exception;

class ArchiveService {
    /**
     * Build an archive according to format specifications and return [archivePath, archiveSize, items]
     *
     * @param array $assets List of asset records with joined uploader and collection details
     * @param string $format 'zip_flat'|'zip_by_user'|'zip_by_type'|'zip_by_collection'|'manifest_json'|'manifest_csv'|'combined_zip'
     * @param array $options ['archive_name', 'notes', 'include_manifest']
     * @return array
     */
    public static function createExportPackage(array $assets, string $format, array $options = []): array {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $exportsDir = $appConfig['storage']['exports'];

        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        $baseName = !empty($options['archive_name']) 
            ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $options['archive_name']) 
            : 'export_' . date('Ymd_His');

        // Handle standalone Manifest formats (JSON or CSV)
        if ($format === 'manifest_json') {
            $manifestData = self::generateManifestArray($assets);
            $filePath = $exportsDir . DIRECTORY_SEPARATOR . $baseName . '.json';
            file_put_contents($filePath, json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            $items = [];
            foreach ($assets as $asset) {
                $items[] = [
                    'asset_id' => $asset['id'],
                    'source_user_id' => $asset['uploader_id'],
                    'original_filename' => $asset['original_filename'],
                    'archive_path' => $baseName . '.json',
                    'file_size_bytes' => $asset['file_size_bytes'],
                    'sha256_checksum' => $asset['sha256_checksum']
                ];
            }

            return [
                'archive_path' => $filePath,
                'archive_size_bytes' => filesize($filePath),
                'total_files' => count($assets),
                'items' => $items,
                'mime_type' => 'application/json'
            ];
        }

        if ($format === 'manifest_csv') {
            $filePath = $exportsDir . DIRECTORY_SEPARATOR . $baseName . '.csv';
            $fp = fopen($filePath, 'w');
            fputcsv($fp, ['Asset ID', 'Original Filename', 'Extension', 'MIME Type', 'File Size (Bytes)', 'SHA256 Checksum', 'Uploader ID', 'Uploader Name', 'Uploader Email', 'Collection', 'Upload Date']);
            
            $items = [];
            foreach ($assets as $asset) {
                fputcsv($fp, [
                    $asset['uuid'],
                    $asset['original_filename'],
                    $asset['file_extension'],
                    $asset['mime_type'],
                    $asset['file_size_bytes'],
                    $asset['sha256_checksum'],
                    $asset['uploader_id'],
                    $asset['uploader_name'] ?? 'Unknown',
                    $asset['uploader_email'] ?? 'Unknown',
                    $asset['collection_name'] ?? 'Unorganized',
                    $asset['created_at']
                ]);

                $items[] = [
                    'asset_id' => $asset['id'],
                    'source_user_id' => $asset['uploader_id'],
                    'original_filename' => $asset['original_filename'],
                    'archive_path' => $baseName . '.csv',
                    'file_size_bytes' => $asset['file_size_bytes'],
                    'sha256_checksum' => $asset['sha256_checksum']
                ];
            }
            fclose($fp);

            return [
                'archive_path' => $filePath,
                'archive_size_bytes' => filesize($filePath),
                'total_files' => count($assets),
                'items' => $items,
                'mime_type' => 'text/csv'
            ];
        }

        // Handle ZIP Archive generation
        $zipPath = $exportsDir . DIRECTORY_SEPARATOR . $baseName . '_' . bin2hex(random_bytes(6)) . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to initialize ZIP archive on server.");
        }

        $items = [];
        $usedPaths = [];

        foreach ($assets as $asset) {
            $sourceFile = $asset['storage_path'];
            if (!file_exists($sourceFile)) {
                $appConfig = require __DIR__ . '/../../config/app.php';
                $fallbackPath = ($appConfig['storage']['uploads'] ?? '') . DIRECTORY_SEPARATOR . $asset['stored_filename'];
                if (file_exists($fallbackPath)) {
                    $sourceFile = $fallbackPath;
                }
            }
            if (!file_exists($sourceFile)) {
                // If missing on disk, log warning and continue
                error_log("Warning: Export asset physical file missing: " . $sourceFile);
                continue;
            }

            // Determine relative archive path based on format
            $relPath = self::computeArchiveRelativePath($asset, $format);

            // Sanitize archive path against Zip-Slip traversal
            $relPath = self::sanitizeZipPath($relPath);

            // Handle filename collision within the same folder
            $relPath = self::resolveDuplicatePath($relPath, $usedPaths);
            $usedPaths[$relPath] = true;

            // Add file directly from disk without reading entire file into memory
            $zip->addFile($sourceFile, $relPath);

            $items[] = [
                'asset_id' => $asset['id'],
                'source_user_id' => $asset['uploader_id'],
                'original_filename' => $asset['original_filename'],
                'archive_path' => $relPath,
                'file_size_bytes' => $asset['file_size_bytes'],
                'sha256_checksum' => $asset['sha256_checksum']
            ];
        }

        // If format is combined_zip or option include_manifest is requested, embed manifest.json and manifest.csv
        if ($format === 'combined_zip' || !empty($options['include_manifest'])) {
            $manifestJson = json_encode(self::generateManifestArray($assets), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $zip->addFromString('manifest.json', $manifestJson);

            $csvBuffer = fopen('php://memory', 'r+');
            fputcsv($csvBuffer, ['Asset ID', 'Original Filename', 'Extension', 'MIME Type', 'File Size (Bytes)', 'SHA256 Checksum', 'Uploader ID', 'Uploader Name', 'Collection', 'Upload Date']);
            foreach ($assets as $asset) {
                fputcsv($csvBuffer, [
                    $asset['uuid'],
                    $asset['original_filename'],
                    $asset['file_extension'],
                    $asset['mime_type'],
                    $asset['file_size_bytes'],
                    $asset['sha256_checksum'],
                    $asset['uploader_id'],
                    $asset['uploader_name'] ?? 'Unknown',
                    $asset['collection_name'] ?? 'Unorganized',
                    $asset['created_at']
                ]);
            }
            rewind($csvBuffer);
            $manifestCsv = stream_get_contents($csvBuffer);
            fclose($csvBuffer);
            $zip->addFromString('manifest.csv', $manifestCsv);
        }

        // Add optional README / Export note
        if (!empty($options['notes'])) {
            $readmeText = "Export Package: {$baseName}\nGenerated: " . date('Y-m-d H:i:s') . "\nFormat: {$format}\nNotes:\n" . $options['notes'] . "\n";
            $zip->addFromString('EXPORT_README.txt', $readmeText);
        }

        $zip->close();

        if (!file_exists($zipPath)) {
            throw new Exception("Export archive creation failed.");
        }

        return [
            'archive_path' => $zipPath,
            'archive_size_bytes' => filesize($zipPath),
            'total_files' => count($items),
            'items' => $items,
            'mime_type' => 'application/zip'
        ];
    }

    private static function computeArchiveRelativePath(array $asset, string $format): string {
        $safeName = self::sanitizeFilename($asset['original_filename']);
        $catFolder = self::getCategoryFolder($asset['file_extension']);

        switch ($format) {
            case 'zip_by_user':
                // Structure: user_{id}_{username}/{category}/{filename}
                $userName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $asset['uploader_name'] ?? 'user');
                $userFolder = 'user_' . $asset['uploader_id'] . '_' . $userName;
                return $userFolder . '/' . $catFolder . '/' . $safeName;

            case 'zip_by_type':
                // Structure: {Category}/{filename}
                return ucfirst($catFolder) . '/' . $safeName;

            case 'zip_by_collection':
                // Structure: {collection_name}/{filename}
                $collName = !empty($asset['collection_name']) 
                    ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $asset['collection_name']) 
                    : 'Uncategorized';
                return $collName . '/' . $safeName;

            case 'combined_zip':
            case 'zip_flat':
            default:
                // Flat structure preserving safe filename
                return $safeName;
        }
    }

    public static function sanitizeZipPath(string $path): string {
        // Strip null bytes
        $path = str_replace(chr(0), '', $path);
        // Replace backward slashes with forward slashes
        $path = str_replace('\\', '/', $path);
        // Remove leading/trailing slashes
        $path = trim($path, '/');
        // Prevent path traversal: eliminate any occurrence of ../ or ..
        $parts = explode('/', $path);
        $safeParts = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            $safeParts[] = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $part);
        }
        return implode('/', $safeParts);
    }

    private static function sanitizeFilename(string $filename): string {
        $name = basename($filename);
        $name = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $name);
        return $name ?: 'file';
    }

    private static function resolveDuplicatePath(string $path, array $existing): string {
        if (!isset($existing[$path])) {
            return $path;
        }

        $dir = dirname($path);
        $file = basename($path);
        $info = pathinfo($file);
        $name = $info['filename'];
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';

        $counter = 1;
        do {
            $newFilename = "{$name} ({$counter}){$ext}";
            $newPath = ($dir === '.' || $dir === '') ? $newFilename : "{$dir}/{$newFilename}";
            $counter++;
        } while (isset($existing[$newPath]));

        return $newPath;
    }

    private static function getCategoryFolder(string $ext): string {
        $ext = strtolower($ext);
        $map = [
            'jpg' => 'images', 'jpeg' => 'images', 'png' => 'images', 'webp' => 'images', 'gif' => 'images',
            'pdf' => 'documents', 'doc' => 'documents', 'docx' => 'documents',
            'xls' => 'spreadsheets', 'xlsx' => 'spreadsheets', 'csv' => 'spreadsheets',
            'ppt' => 'presentations', 'pptx' => 'presentations',
            'zip' => 'archives',
            'txt' => 'text'
        ];
        return $map[$ext] ?? 'other';
    }

    private static function generateManifestArray(array $assets): array {
        $list = [];
        foreach ($assets as $a) {
            $list[] = [
                'asset_uuid' => $a['uuid'],
                'original_filename' => $a['original_filename'],
                'file_extension' => $a['file_extension'],
                'mime_type' => $a['mime_type'],
                'file_size_bytes' => (int)$a['file_size_bytes'],
                'sha256_checksum' => $a['sha256_checksum'],
                'uploader' => [
                    'id' => (int)$a['uploader_id'],
                    'name' => $a['uploader_name'] ?? 'Unknown',
                    'email' => $a['uploader_email'] ?? 'Unknown'
                ],
                'collection' => $a['collection_name'] ?? null,
                'uploaded_at' => $a['created_at']
            ];
        }

        return [
            'portal' => 'Workspace',
            'generated_at' => date('c'),
            'total_assets' => count($assets),
            'assets' => $list
        ];
    }
}
