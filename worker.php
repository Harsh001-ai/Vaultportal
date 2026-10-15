<?php
// worker.php — Background Processing & Cleanup Worker

require_once __DIR__ . '/app/autoload.php';

use App\Database;
use App\Services\ArchiveService;
use App\Services\AuditService;
use App\Services\NotificationService;

echo "[" . date('Y-m-d H:i:s') . "] Workspace CLI Worker started.\n";

$isDaemon = in_array('--daemon', $argv);

do {
    // 1. Process Queued Export Jobs
    $queuedJobs = Database::fetchAll("SELECT * FROM export_jobs WHERE status = 'queued' ORDER BY created_at ASC LIMIT 5");

    foreach ($queuedJobs as $job) {
        $jobId = $job['id'];
        echo "Processing export job #{$jobId} ('{$job['export_name']}')...\n";

        try {
            Database::update('export_jobs', ['status' => 'preparing', 'progress_percent' => 20], 'id = :id', ['id' => $jobId]);

            // Fetch assets
            $items = Database::fetchAll(
                "SELECT a.*, u.name as uploader_name, u.email as uploader_email, c.name as collection_name
                 FROM export_items ei
                 JOIN assets a ON ei.asset_id = a.id
                 JOIN users u ON a.uploader_id = u.id
                 LEFT JOIN collections c ON a.collection_id = c.id
                 WHERE ei.export_job_id = :jid",
                ['jid' => $jobId]
            );

            Database::update('export_jobs', ['status' => 'packaging', 'progress_percent' => 50], 'id = :id', ['id' => $jobId]);

            $result = ArchiveService::createExportPackage($items, $job['format'], [
                'archive_name' => $job['export_name'],
                'notes' => $job['export_notes']
            ]);

            Database::update('export_jobs', [
                'status' => 'ready',
                'progress_percent' => 100,
                'archive_size_bytes' => $result['archive_size_bytes'],
                'archive_path' => $result['archive_path'],
                'completed_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $jobId]);

            AuditService::log('export.packaged_async', 'export_job', (string)$jobId, [
                'size' => $result['archive_size_bytes']
            ], $job['admin_id']);

            NotificationService::create(
                $job['admin_id'],
                'Async Export Ready',
                "Export '{$job['export_name']}' is ready for download.",
                'success',
                "/admin/exports"
            );

            echo "Successfully packaged export #{$jobId}.\n";
        } catch (\Exception $e) {
            echo "Failed export #{$jobId}: " . $e->getMessage() . "\n";
            Database::update('export_jobs', [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ], 'id = :id', ['id' => $jobId]);
        }
    }

    // 2. Clean up Expired Export Packages
    $expiredJobs = Database::fetchAll(
        "SELECT id, archive_path FROM export_jobs WHERE status = 'ready' AND token_expires_at < :now",
        ['now' => date('Y-m-d H:i:s')]
    );

    foreach ($expiredJobs as $exp) {
        if (!empty($exp['archive_path']) && file_exists($exp['archive_path'])) {
            @unlink($exp['archive_path']);
            echo "Removed expired archive file: {$exp['archive_path']}\n";
        }
        Database::update('export_jobs', ['status' => 'expired'], 'id = :id', ['id' => $exp['id']]);
    }

    if ($isDaemon) {
        sleep(5);
    }
} while ($isDaemon);

echo "[" . date('Y-m-d H:i:s') . "] VaultPortal CLI Worker finished.\n";
