<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;

class DashboardController {
    public function userDashboard(Request $request): void {
        $user = AuthService::currentUser();

        // 1. Storage metrics
        $quota = (int)$user['storage_quota_bytes'];
        $used = (int)$user['storage_used_bytes'];
        $quotaPercent = $quota > 0 ? min(100, round(($used / $quota) * 100, 1)) : 0;

        // 2. Asset counts
        $totalAssetsRow = Database::fetch(
            "SELECT COUNT(*) as cnt FROM assets WHERE uploader_id = :uid AND deleted_at IS NULL",
            ['uid' => $user['id']]
        );
        $totalAssets = (int)($totalAssetsRow['cnt'] ?? 0);

        $quarantinedRow = Database::fetch(
            "SELECT COUNT(*) as cnt FROM assets WHERE uploader_id = :uid AND status = 'quarantined' AND deleted_at IS NULL",
            ['uid' => $user['id']]
        );
        $quarantinedCount = (int)($quarantinedRow['cnt'] ?? 0);

        // 3. Category Breakdown
        $categoriesRow = Database::fetchAll(
            "SELECT file_extension, COUNT(*) as count, SUM(file_size_bytes) as total_bytes
             FROM assets 
             WHERE uploader_id = :uid AND deleted_at IS NULL 
             GROUP BY file_extension",
            ['uid' => $user['id']]
        );

        // 4. Recent uploads
        $recentAssets = Database::fetchAll(
            "SELECT a.*, c.name as collection_name 
             FROM assets a
             LEFT JOIN collections c ON a.collection_id = c.id
             WHERE a.uploader_id = :uid AND a.deleted_at IS NULL
             ORDER BY a.created_at DESC 
             LIMIT 8",
            ['uid' => $user['id']]
        );

        // 5. User's collections
        $collections = Database::fetchAll(
            "SELECT c.*, COUNT(a.id) as asset_count 
             FROM collections c
             LEFT JOIN assets a ON c.id = a.collection_id AND a.deleted_at IS NULL
             WHERE c.user_id = :uid OR c.is_global = 1
             GROUP BY c.id
             ORDER BY c.created_at DESC",
            ['uid' => $user['id']]
        );

        // 6. Recent activity
        $activities = Database::fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 6",
            ['uid' => $user['id']]
        );

        Response::view('user/dashboard', [
            'title' => 'My Dashboard — Workspace',
            'user' => $user,
            'quota' => $quota,
            'used' => $used,
            'quotaPercent' => $quotaPercent,
            'totalAssets' => $totalAssets,
            'quarantinedCount' => $quarantinedCount,
            'categories' => $categoriesRow,
            'recentAssets' => $recentAssets,
            'collections' => $collections,
            'activities' => $activities,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function adminDashboard(Request $request): void {
        $user = AuthService::currentUser();

        // 1. Platform Metrics
        $userMetrics = Database::fetch(
            "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users
             FROM users WHERE role = 'user'"
        );

        $assetMetrics = Database::fetch(
            "SELECT 
                COUNT(*) as total_assets,
                COALESCE(SUM(file_size_bytes), 0) as total_bytes,
                SUM(CASE WHEN status = 'quarantined' THEN 1 ELSE 0 END) as quarantined_assets
             FROM assets WHERE deleted_at IS NULL"
        );

        $exportMetrics = Database::fetch(
            "SELECT COUNT(*) as total_exports FROM export_jobs"
        );

        $downloadMetrics = Database::fetch(
            "SELECT COUNT(*) as total_downloads FROM download_events WHERE download_status = 'success'"
        );

        // 2. Recent Uploads with Source User Attribution
        $recentAssets = Database::fetchAll(
            "SELECT a.*, u.name as uploader_name, u.email as uploader_email, c.name as collection_name
             FROM assets a
             JOIN users u ON a.uploader_id = u.id
             LEFT JOIN collections c ON a.collection_id = c.id
             WHERE a.deleted_at IS NULL
             ORDER BY a.created_at DESC
             LIMIT 10"
        );

        // 3. Recent Exports
        $recentExports = Database::fetchAll(
            "SELECT e.*, u.name as admin_name
             FROM export_jobs e
             JOIN users u ON e.admin_id = u.id
             ORDER BY e.created_at DESC
             LIMIT 6"
        );

        // 4. Recent Download Audit Events
        $recentDownloads = Database::fetchAll(
            "SELECT d.*, e.export_name, a.original_filename as direct_filename
             FROM download_events d
             LEFT JOIN export_jobs e ON d.export_job_id = e.id
             LEFT JOIN assets a ON d.asset_id = a.id
             ORDER BY d.downloaded_at DESC
             LIMIT 8"
        );

        Response::view('admin/dashboard', [
            'title' => 'Administrator Workspace — Workspace',
            'user' => $user,
            'userMetrics' => $userMetrics,
            'assetMetrics' => $assetMetrics,
            'exportMetrics' => $exportMetrics,
            'downloadMetrics' => $downloadMetrics,
            'recentAssets' => $recentAssets,
            'recentExports' => $recentExports,
            'recentDownloads' => $recentDownloads,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }
}
