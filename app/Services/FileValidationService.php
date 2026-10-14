<?php
namespace App\Services;

use Exception;

class FileValidationService {
    private static array $mimeConfig = [];

    public static function getMimeConfig(): array {
        if (empty(self::$mimeConfig)) {
            self::$mimeConfig = require __DIR__ . '/../../config/mimetypes.php';
        }
        return self::$mimeConfig;
    }

    public static function validateUpload(array $file, array $user): array {
        $config = self::getMimeConfig();

        // Check upload error codes
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMap = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE specified in the form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            throw new Exception($errorMap[$file['error']] ?? 'Unknown upload error.');
        }

        $originalName = basename($file['name']);
        $tempPath = $file['tmp_name'];
        $fileSize = (int)$file['size'];

        if ($fileSize <= 0 || !file_exists($tempPath)) {
            throw new Exception("The uploaded file is empty or missing.");
        }

        // 1. File size limit
        $maxBytes = 100 * 1024 * 1024; // 100 MB
        if ($fileSize > $maxBytes) {
            throw new Exception("File exceeds the maximum allowed size of 100 MB.");
        }

        // 2. User quota check
        $availableQuota = max(0, $user['storage_quota_bytes'] - $user['storage_used_bytes']);
        if ($fileSize > $availableQuota) {
            $availMb = round($availableQuota / (1024 * 1024), 2);
            throw new Exception("Storage quota exceeded. You only have {$availMb} MB remaining.");
        }

        // 3. Extract and sanitize extension
        $pathInfo = pathinfo($originalName);
        $extension = strtolower($pathInfo['extension'] ?? '');

        if (empty($extension)) {
            throw new Exception("Files without a valid file extension are not permitted.");
        }

        // 4. Blacklisted extensions check
        if (in_array($extension, $config['blacklisted_extensions'], true)) {
            throw new Exception("File type '.$extension' is strictly prohibited for security reasons.");
        }

        // 5. Allowed extensions check
        $allowedExtensions = [];
        foreach ($config['categories'] as $cat) {
            $allowedExtensions = array_merge($allowedExtensions, $cat['extensions']);
        }
        $allowedExtensions = array_unique($allowedExtensions);

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new Exception("File extension '.$extension' is not supported.");
        }

        // 6. Magic bytes / Server-side MIME detection
        $realTempPath = realpath($tempPath) ?: str_replace('/', DIRECTORY_SEPARATOR, $tempPath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = @finfo_file($finfo, $realTempPath);
        finfo_close($finfo);

        if (!$detectedMime) {
            throw new Exception("Failed to inspect file MIME type.");
        }

        // 7. Verify detected MIME matches allowed category
        $validMime = false;
        foreach ($config['categories'] as $cat) {
            if (in_array($extension, $cat['extensions'], true)) {
                if (in_array($detectedMime, $cat['mimes'], true)) {
                    $validMime = true;
                    break;
                }
                // Allow fallback for plaintext files
                if ($detectedMime === 'text/plain' && in_array($extension, ['csv', 'txt'])) {
                    $validMime = true;
                    break;
                }
            }
        }

        if (!$validMime) {
            throw new Exception("MIME type mismatch: extension '.$extension' does not match detected type '$detectedMime'.");
        }

        // 8. Heuristic malware & script inspection
        $quarantineReason = self::scanForSuspiciousContent($tempPath, $extension, $detectedMime);
        $status = $quarantineReason ? 'quarantined' : 'ready';

        // 9. Compute cryptographic hash
        $sha256 = hash_file('sha256', $tempPath);

        return [
            'original_filename' => $originalName,
            'file_extension' => $extension,
            'mime_type' => $detectedMime,
            'file_size_bytes' => $fileSize,
            'sha256_checksum' => $sha256,
            'status' => $status,
            'quarantine_reason' => $quarantineReason,
            'temp_path' => $tempPath,
        ];
    }

    private static function scanForSuspiciousContent(string $filePath, string $extension, string $mime): ?string {
        // Read the first 64KB for heuristics
        $handle = fopen($filePath, 'rb');
        if (!$handle) return null;
        $header = fread($handle, 65536);
        fclose($handle);

        $lowerHeader = strtolower($header);

        // 1. Safe inert test markers for verification without dangerous payloads or triggering AV
        if (str_contains($header, 'X-INERT-TEST-QUARANTINE-MARKER') || str_contains($header, '[SIMULATED-INERT-QUARANTINE-FIXTURE]')) {
            return "Test simulation marker detected: file flagged for quarantine testing.";
        }

        // 2. Check for embedded PHP tags in any non-PHP file
        if (str_contains($lowerHeader, '<?php') || str_contains($lowerHeader, '<?=') || (str_contains($lowerHeader, '<script') && !in_array($extension, ['txt', 'csv']))) {
            return "Suspicious executable code or script tag detected in file content header.";
        }

        // 3. Check for Windows executable signatures (MZ header)
        if (str_starts_with($header, "MZ") && !in_array($extension, ['txt', 'csv'])) {
            return "Windows executable PE header (MZ) detected in uploaded non-binary asset.";
        }

        // 4. Check for Linux ELF executable signatures (\x7fELF)
        if (str_starts_with($header, "\x7fELF")) {
            return "Linux ELF executable binary signature detected.";
        }

        return null;
    }
}
