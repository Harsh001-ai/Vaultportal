<?php
namespace App;

class Session {
    private static bool $started = false;

    public static function start(): void {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $config = require __DIR__ . '/../config/app.php';
        $sessConfig = $config['session'];

        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            session_name($sessConfig['name']);
            session_set_cookie_params([
                'lifetime' => $sessConfig['lifetime'],
                'path' => '/',
                'domain' => '',
                'secure' => $sessConfig['secure'],
                'httponly' => $sessConfig['httponly'],
                'samesite' => $sessConfig['samesite']
            ]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        self::$started = true;
    }

    public static function regenerate(): void {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }


    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$started = false;
    }

    public static function flash(string $key, mixed $value = null): mixed {
        self::start();
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function getCsrfToken(): string {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool {
        self::start();
        $stored = $_SESSION['_csrf_token'] ?? '';
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, $token);
    }
}
