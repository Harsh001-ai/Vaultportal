<?php
namespace App;

class Response {
    public static function json(array $data, int $statusCode = 200, array $headers = []): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): void {
        self::json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, mixed $errors = null): void {
        self::json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ], $statusCode);
    }

    public static function redirect(string $url): void {
        header("Location: $url");
        exit;
    }

    public static function view(string $viewPath, array $data = [], string $layout = 'main'): void {
        // Extract data variables for the view
        extract($data);
        
        $viewFile = __DIR__ . '/Views/' . ltrim($viewPath, '/') . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "Error: View [$viewPath] not found.";
            exit;
        }

        // Render the view into a buffer
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // If no layout is specified, output raw content
        if ($layout === 'none' || empty($layout)) {
            echo $content;
            exit;
        }

        $layoutFile = __DIR__ . '/Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            echo $content;
            exit;
        }

        require $layoutFile;
        exit;
    }

    public static function download(string $filePath, string $downloadFilename, string $mimeType = 'application/octet-stream'): void {
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "File not found.";
            exit;
        }

        $fileSize = filesize($filePath);

        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . rawurlencode($downloadFilename) . '"; filename*=UTF-8\'\'' . rawurlencode($downloadFilename));
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        header('X-Content-Type-Options: nosniff');

        // Stream file in 64KB chunks to prevent memory spikes
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            http_response_code(500);
            echo "Unable to read file stream.";
            exit;
        }

        while (!feof($handle)) {
            echo fread($handle, 65536);
            flush();
        }
        fclose($handle);
        exit;
    }
}
