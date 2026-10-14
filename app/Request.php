<?php
namespace App;

class Request {
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private ?array $jsonBody = null;

    public function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public function getMethod(): string {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($path, '/');
    }

    public function query(string $key, mixed $default = null): mixed {
        return $this->get[$key] ?? $default;
    }

    public function allQuery(): array {
        return $this->get;
    }

    public function post(string $key, mixed $default = null): mixed {
        return $this->post[$key] ?? $default;
    }

    public function json(string $key = null, mixed $default = null): mixed {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
        if ($key === null) {
            return $this->jsonBody;
        }
        return $this->jsonBody[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed {
        if ($this->isJson()) {
            return $this->json($key, $default);
        }
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    public function isJson(): bool {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function file(string $key): ?array {
        return $this->files[$key] ?? null;
    }

    public function allFiles(): array {
        return $this->files;
    }

    public function header(string $key, mixed $default = null): mixed {
        $formatted = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($this->server[$formatted])) {
            return $this->server[$formatted];
        }
        // Direct checks for Content-Type or Authorization
        if (strcasecmp($key, 'Content-Type') === 0 && isset($this->server['CONTENT_TYPE'])) {
            return $this->server['CONTENT_TYPE'];
        }
        if (strcasecmp($key, 'Authorization') === 0 && isset($this->server['HTTP_AUTHORIZATION'])) {
            return $this->server['HTTP_AUTHORIZATION'];
        }
        return $default;
    }

    public function ip(): string {
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string {
        return $this->server['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    public function isAjax(): bool {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest') || $this->isJson();
    }
}
