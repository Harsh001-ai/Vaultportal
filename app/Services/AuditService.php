<?php
namespace App\Services;

use App\Database;
use App\Session;

class AuditService {
    public static function log(string $action, string $entityType, ?string $entityId = null, ?array $details = null, ?int $userId = null): void {
        try {
            if ($userId === null) {
                $currentUser = Session::get('user');
                $userId = $currentUser['id'] ?? null;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ip = trim($ips[0]);
            }

            Database::insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details_json' => $details ? json_encode($details, JSON_UNESCAPED_SLASHES) : null,
                'ip_address' => substr($ip, 0, 45),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    public static function recordDownload(?int $exportJobId, ?int $assetId, int $downloadedById, string $downloadedByName, string $status = 'success', ?string $errorMessage = null): int {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            return Database::insert('download_events', [
                'export_job_id' => $exportJobId,
                'asset_id' => $assetId,
                'downloaded_by_id' => $downloadedById,
                'downloaded_by_name' => $downloadedByName,
                'ip_address' => substr($ip, 0, 45),
                'user_agent' => substr($ua, 0, 500),
                'download_status' => $status,
                'error_message' => $errorMessage,
                'downloaded_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Download event recording failed: " . $e->getMessage());
            return 0;
        }
    }

    public static function getDownloadAuditTrail(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['admin_id'])) {
            $where[] = "d.downloaded_by_id = :admin_id";
            $params['admin_id'] = $filters['admin_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "d.download_status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['format'])) {
            $where[] = "e.format = :format";
            $params['format'] = $filters['format'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "d.downloaded_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "d.downloaded_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $where[] = "(e.export_name LIKE :search OR a.original_filename LIKE :search OR d.downloaded_by_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['source_user_id'])) {
            $where[] = "(a.uploader_id = :src_uid OR EXISTS(SELECT 1 FROM export_items ei WHERE ei.export_job_id = e.id AND ei.source_user_id = :src_uid2))";
            $params['src_uid'] = $filters['source_user_id'];
            $params['src_uid2'] = $filters['source_user_id'];
        }

        $whereClause = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) as total FROM download_events d
                     LEFT JOIN export_jobs e ON d.export_job_id = e.id
                     LEFT JOIN assets a ON d.asset_id = a.id
                     WHERE $whereClause";
        $totalRow = Database::fetch($countSql, $params);
        $total = (int)($totalRow['total'] ?? 0);

        $sql = "SELECT d.*, 
                       e.uuid as export_uuid, e.export_name, e.format as export_format, e.total_files, e.archive_size_bytes, e.created_at as export_created_at,
                       a.uuid as asset_uuid, a.original_filename as direct_filename, a.file_size_bytes as direct_file_size,
                       u.email as admin_email
                FROM download_events d
                LEFT JOIN export_jobs e ON d.export_job_id = e.id
                LEFT JOIN assets a ON d.asset_id = a.id
                LEFT JOIN users u ON d.downloaded_by_id = u.id
                WHERE $whereClause
                ORDER BY d.downloaded_at DESC
                LIMIT $limit OFFSET $offset";

        $records = Database::fetchAll($sql, $params);

        // Fetch source users for each export job
        foreach ($records as &$rec) {
            if ($rec['export_job_id']) {
                $rec['source_users'] = Database::fetchAll(
                    "SELECT DISTINCT u.id, u.name, u.email 
                     FROM export_items ei 
                     JOIN users u ON ei.source_user_id = u.id 
                     WHERE ei.export_job_id = :jid",
                    ['jid' => $rec['export_job_id']]
                );
                $rec['items_summary'] = Database::fetchAll(
                    "SELECT ei.original_filename, ei.archive_path, ei.file_size_bytes, u.name as source_user_name
                     FROM export_items ei
                     JOIN users u ON ei.source_user_id = u.id
                     WHERE ei.export_job_id = :jid
                     LIMIT 10",
                    ['jid' => $rec['export_job_id']]
                );
            } else {
                $rec['source_users'] = [];
                $rec['items_summary'] = [];
            }
        }

        return [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'records' => $records
        ];
    }
}
