<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\AuditService;
use Exception;

class UserController {
    public function index(Request $request): void {
        $user = AuthService::currentUser();
        $search = trim($request->query('search', ''));
        $role = $request->query('role');
        $status = $request->query('status');

        $where = ["1=1"];
        $params = [];

        if ($search !== '') {
            $where[] = "(name LIKE :search OR email LIKE :search2)";
            $params['search'] = '%' . $search . '%';
            $params['search2'] = '%' . $search . '%';
        }

        if (!empty($role)) {
            $where[] = "role = :role";
            $params['role'] = $role;
        }

        if (!empty($status)) {
            $where[] = "status = :status";
            $params['status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $users = Database::fetchAll(
            "SELECT u.*, 
                    (SELECT COUNT(*) FROM assets a WHERE a.uploader_id = u.id AND a.deleted_at IS NULL) as asset_count,
                    (SELECT COUNT(*) FROM download_events d WHERE d.downloaded_by_id = u.id) as download_count
             FROM users u
             WHERE $whereClause
             ORDER BY u.created_at DESC",
            $params
        );

        Response::view('admin/users', [
            'title' => 'User Management & Quotas — Workspace',
            'user' => $user,
            'users' => $users,
            'search' => $search,
            'selectedRole' => $role,
            'selectedStatus' => $status,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function store(Request $request): void {
        $admin = AuthService::currentUser();
        $name = trim($request->input('name', ''));
        $email = trim(strtolower($request->input('email', '')));
        $password = $request->input('password');
        $role = $request->input('role', 'user');
        $quotaMb = (int)$request->input('quota_mb', 1024);

        try {
            $user = AuthService::register($name, $email, $password, $role);

            // Update custom quota in bytes
            $quotaBytes = max(10, $quotaMb) * 1024 * 1024;
            Database::update('users', ['storage_quota_bytes' => $quotaBytes], 'id = :id', ['id' => $user['id']]);

            AuditService::log('admin.user_create', 'user', (string)$user['id'], [
                'email' => $email,
                'role' => $role,
                'quota_mb' => $quotaMb
            ], $admin['id']);

            Session::flash('success', "User '{$name}' created successfully.");
            Response::redirect('/admin/users');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect('/admin/users');
        }
    }

    public function toggleStatus(Request $request, array $params): void {
        $admin = AuthService::currentUser();
        $targetId = (int)($params['id'] ?? 0);

        // Prevent self suspension
        if ($targetId === (int)$admin['id']) {
            Session::flash('error', 'You cannot suspend your own administrative account.');
            Response::redirect('/admin/users');
        }

        $target = Database::fetch("SELECT * FROM users WHERE id = :id", ['id' => $targetId]);
        if (!$target) {
            Response::redirect('/admin/users');
        }

        $newStatus = ($target['status'] === 'active') ? 'suspended' : 'active';
        Database::update('users', ['status' => $newStatus], 'id = :id', ['id' => $targetId]);

        AuditService::log('admin.user_status_change', 'user', (string)$targetId, [
            'from' => $target['status'],
            'to' => $newStatus
        ], $admin['id']);

        Session::flash('success', "User '{$target['name']}' status changed to {$newStatus}.");
        Response::redirect('/admin/users');
    }

    public function updateQuota(Request $request, array $params): void {
        $admin = AuthService::currentUser();
        $targetId = (int)($params['id'] ?? 0);
        $quotaMb = (int)$request->input('quota_mb', 1024);

        $quotaBytes = max(10, $quotaMb) * 1024 * 1024;
        Database::update('users', ['storage_quota_bytes' => $quotaBytes], 'id = :id', ['id' => $targetId]);

        AuditService::log('admin.user_quota_update', 'user', (string)$targetId, [
            'new_quota_mb' => $quotaMb
        ], $admin['id']);

        Session::flash('success', 'Storage quota updated.');
        Response::redirect('/admin/users');
    }
}
