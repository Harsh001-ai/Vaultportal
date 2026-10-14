<?php
namespace App\Services;

use App\Database;
use App\Session;
use Exception;

class AuthService {
    public static function authenticate(string $email, string $password): array {
        $email = trim(strtolower($email));
        $user = Database::fetch("SELECT * FROM users WHERE email = :email LIMIT 1", ['email' => $email]);

        if (!$user) {
            throw new Exception("Invalid email or password.");
        }

        if ($user['status'] === 'suspended') {
            throw new Exception("This account has been suspended. Please contact an administrator.");
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid email or password.");
        }

        // Rehash password if algorithm options updated
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            Database::update('users', ['password_hash' => $newHash], 'id = :id', ['id' => $user['id']]);
        }

        // Regenerate session ID to prevent session fixation attacks
        Session::regenerate();

        // Update last login
        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        // Remove sensitive fields before storing in session
        unset($user['password_hash']);
        Session::set('user', $user);

        AuditService::log('user.login', 'user', (string)$user['id'], [
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        return $user;
    }

    public static function register(string $name, string $email, string $password, string $role = 'user'): array {
        $name = trim($name);
        $email = trim(strtolower($email));

        if (empty($name) || strlen($name) < 2) {
            throw new Exception("Full name must be at least 2 characters.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please provide a valid email address.");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Check if email already registered
        $existing = Database::fetch("SELECT id FROM users WHERE email = :email LIMIT 1", ['email' => $email]);
        if ($existing) {
            throw new Exception("An account with this email address already exists.");
        }

        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = Database::insert('users', [
            'uuid' => $uuid,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => in_array($role, ['admin', 'user']) ? $role : 'user',
            'status' => 'active',
            'storage_quota_bytes' => 1073741824, // 1 GB default
            'storage_used_bytes' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $user = Database::fetch("SELECT id, uuid, name, email, role, status, storage_quota_bytes, storage_used_bytes FROM users WHERE id = :id", ['id' => $userId]);

        AuditService::log('user.register', 'user', (string)$userId, [
            'email' => $email,
            'role' => $user['role']
        ]);

        return $user;
    }

    public static function logout(): void {
        $user = Session::get('user');
        if ($user) {
            AuditService::log('user.logout', 'user', (string)$user['id']);
        }
        Session::destroy();
    }

    public static function currentUser(): ?array {
        Session::start();
        $user = Session::get('user');
        if (!$user) {
            return null;
        }
        // Refresh dynamic storage values from DB
        $fresh = Database::fetch("SELECT id, uuid, name, email, role, status, storage_quota_bytes, storage_used_bytes, last_login_at FROM users WHERE id = :id", ['id' => $user['id']]);
        if ($fresh) {
            Session::set('user', $fresh);
            return $fresh;
        }
        return $user;
    }
}
