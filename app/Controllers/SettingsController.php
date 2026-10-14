<?php
namespace App\Controllers;

use App\Database;
use App\Request;
use App\Response;
use App\Session;
use App\Services\AuthService;
use App\Services\AuditService;

class SettingsController {
    public function index(Request $request): void {
        $user = AuthService::currentUser();
        $rows = Database::fetchAll("SELECT * FROM system_settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        Response::view('admin/settings', [
            'title' => 'System Settings & Security Policies — VaultPortal',
            'user' => $user,
            'settings' => $settings,
            'success' => Session::flash('success'),
            'error' => Session::flash('error')
        ]);
    }

    public function update(Request $request): void {
        $admin = AuthService::currentUser();
        $appName = trim($request->input('app_name', 'VaultPortal Enterprise'));
        $allowedExtensions = trim($request->input('allowed_extensions', ''));
        $maxUploadSize = (int)$request->input('max_upload_size_mb', 100);
        $exportExpiry = (int)$request->input('export_expiry_hours', 24);
        $quarantine = $request->input('quarantine_enabled') ? '1' : '0';

        $updates = [
            'app_name' => $appName,
            'allowed_extensions' => $allowedExtensions,
            'max_upload_size_mb' => (string)$maxUploadSize,
            'export_expiry_hours' => (string)$exportExpiry,
            'quarantine_enabled' => $quarantine
        ];

        foreach ($updates as $key => $val) {
            Database::query(
                "INSERT INTO system_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2",
                ['k' => $key, 'v' => $val, 'v2' => $val]
            );
        }

        AuditService::log('admin.settings_update', 'system_settings', null, $updates, $admin['id']);

        Session::flash('success', 'System settings saved.');
        Response::redirect('/admin/settings');
    }
}
