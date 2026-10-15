<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\AuditService;

class AuditController {
    public function index(Request $request): void {
        $user = AuthService::currentUser();

        $filters = [
            'admin_id' => $request->query('admin_id'),
            'source_user_id' => $request->query('source_user_id'),
            'status' => $request->query('status'),
            'format' => $request->query('format'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => trim($request->query('search', ''))
        ];

        $page = max(1, (int)$request->query('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $auditData = AuditService::getDownloadAuditTrail($filters, $limit, $offset);

        // Fetch user lists for filter dropdowns
        $admins = Database::fetchAll("SELECT id, name, email FROM users WHERE role = 'admin' ORDER BY name ASC");
        $allUsers = Database::fetchAll("SELECT id, name, email FROM users ORDER BY name ASC");

        // Format options list
        $formats = [
            'zip_by_user' => 'ZIP by User',
            'combined_zip' => 'Combined ZIP',
            'zip_by_type' => 'ZIP by Type',
            'zip_by_collection' => 'ZIP by Collection',
            'zip_flat' => 'Flat ZIP',
            'manifest_csv' => 'Manifest CSV',
            'manifest_json' => 'Manifest JSON'
        ];

        Response::view('admin/audit_log', [
            'title' => 'Download History & Delivery Audit Log — Workspace',
            'user' => $user,
            'records' => $auditData['records'],
            'total' => $auditData['total'],
            'page' => $page,
            'totalPages' => max(1, ceil($auditData['total'] / $limit)),
            'filters' => $filters,
            'admins' => $admins,
            'allUsers' => $allUsers,
            'formats' => $formats,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function exportCsv(Request $request): void {
        $user = AuthService::currentUser();

        $filters = [
            'admin_id' => $request->query('admin_id'),
            'source_user_id' => $request->query('source_user_id'),
            'status' => $request->query('status'),
            'format' => $request->query('format'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => trim($request->query('search', ''))
        ];

        // Fetch up to 1000 records for CSV export
        $auditData = AuditService::getDownloadAuditTrail($filters, 1000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vault_download_audit_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Download ID',
            'Export Name / Asset',
            'Export Format',
            'File Count',
            'Total Size (Bytes)',
            'Downloaded By Admin',
            'Admin Email',
            'IP Address',
            'Source Users',
            'Status',
            'Download Timestamp'
        ]);

        foreach ($auditData['records'] as $r) {
            $sourceUsersStr = implode('; ', array_map(fn($u) => "{$u['name']} ({$u['email']})", $r['source_users']));
            fputcsv($out, [
                $r['id'],
                $r['export_name'] ?? $r['direct_filename'],
                $r['export_format'] ?? 'Direct Asset',
                $r['total_files'] ?? 1,
                $r['archive_size_bytes'] ?? $r['direct_file_size'] ?? 0,
                $r['downloaded_by_name'],
                $r['admin_email'] ?? '',
                $r['ip_address'],
                $sourceUsersStr,
                $r['download_status'],
                $r['downloaded_at']
            ]);
        }

        fclose($out);
        exit;
    }
}
