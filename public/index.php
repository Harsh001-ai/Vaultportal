<?php
// public/index.php — Front Controller

require_once __DIR__ . '/../app/autoload.php';

use App\Request;
use App\Response;
use App\Router;
use App\Session;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\AssetController;
use App\Controllers\CollectionController;
use App\Controllers\ExportController;
use App\Controllers\AuditController;
use App\Controllers\UserController;
use App\Controllers\SettingsController;

// Start session
Session::start();

// Handle static files directly in PHP built-in server (serve existing regular files directly, route directories and virtual paths)
if (php_sapi_name() === 'cli-server') {
    $uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
    $filePath = realpath(__DIR__ . $uri);
    $publicRoot = realpath(__DIR__);
    if ($filePath && is_file($filePath) && str_starts_with($filePath, $publicRoot)) {
        return false;
    }
}

$request = new Request();
$router = new Router();

// Root route
$router->get('/', function ($req) {
    $user = Session::get('user');
    if (!$user) {
        Response::redirect('/login');
    }
    Response::redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/dashboard');
});

// Authentication routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], ['RateLimitMiddleware:5,900', 'CsrfMiddleware']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], ['CsrfMiddleware']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/setup', [AuthController::class, 'showSetup']);
$router->post('/setup', [AuthController::class, 'setupAdmin'], ['CsrfMiddleware']);

// User Workspace routes
$router->get('/dashboard', [DashboardController::class, 'userDashboard'], ['AuthMiddleware']);
$router->get('/assets', [AssetController::class, 'userAssets'], ['AuthMiddleware']);
$router->get('/assets/upload', [AssetController::class, 'userAssets'], ['AuthMiddleware']);
$router->post('/api/assets/upload', [AssetController::class, 'upload'], ['AuthMiddleware', 'CsrfMiddleware']);
$router->get('/assets/{id}/download', [AssetController::class, 'download'], ['AuthMiddleware']);
$router->post('/assets/{id}/delete', [AssetController::class, 'delete'], ['AuthMiddleware', 'CsrfMiddleware']);
$router->get('/api/assets/{id}', [AssetController::class, 'assetDetails'], ['AuthMiddleware']);

// Collections routes
$router->get('/collections', [CollectionController::class, 'index'], ['AuthMiddleware']);
$router->post('/collections', [CollectionController::class, 'store'], ['AuthMiddleware', 'CsrfMiddleware']);
$router->post('/collections/{id}/delete', [CollectionController::class, 'delete'], ['AuthMiddleware', 'CsrfMiddleware']);

// Administrator routes (RoleMiddleware:admin strictly enforced on backend)
$router->get('/admin/dashboard', [DashboardController::class, 'adminDashboard'], ['RoleMiddleware:admin']);
$router->get('/admin/assets', [AssetController::class, 'adminAssets'], ['RoleMiddleware:admin']);
$router->get('/admin/exports', [ExportController::class, 'index'], ['RoleMiddleware:admin']);
$router->post('/api/admin/exports/review', [ExportController::class, 'reviewApi'], ['RoleMiddleware:admin', 'CsrfMiddleware']);
$router->post('/api/admin/exports/create', [ExportController::class, 'createApi'], ['RoleMiddleware:admin', 'CsrfMiddleware']);
$router->get('/api/admin/exports/{id}/status', [ExportController::class, 'statusApi'], ['RoleMiddleware:admin']);
$router->get('/admin/audit-log', [AuditController::class, 'index'], ['RoleMiddleware:admin']);
$router->get('/admin/audit-log/export-csv', [AuditController::class, 'exportCsv'], ['RoleMiddleware:admin']);
$router->get('/admin/users', [UserController::class, 'index'], ['RoleMiddleware:admin']);
$router->post('/admin/users', [UserController::class, 'store'], ['RoleMiddleware:admin', 'CsrfMiddleware']);
$router->post('/admin/users/{id}/toggle-status', [UserController::class, 'toggleStatus'], ['RoleMiddleware:admin', 'CsrfMiddleware']);
$router->post('/admin/users/{id}/update-quota', [UserController::class, 'updateQuota'], ['RoleMiddleware:admin', 'CsrfMiddleware']);
$router->get('/admin/settings', [SettingsController::class, 'index'], ['RoleMiddleware:admin']);
$router->post('/admin/settings', [SettingsController::class, 'update'], ['RoleMiddleware:admin', 'CsrfMiddleware']);

// Authorized Download Delivery Endpoint
$router->get('/download/export/{token}', [ExportController::class, 'deliverDownload']);

// Dispatch request
$router->dispatch($request);
