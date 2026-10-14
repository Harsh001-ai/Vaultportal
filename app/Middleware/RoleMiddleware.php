<?php
namespace App\Middleware;

use App\Request;
use App\Response;
use App\Session;

class RoleMiddleware {
    public function handle(Request $request, ?string $requiredRole = null): void {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            if ($request->isAjax()) {
                Response::error('Authentication required.', 401);
            }
            Response::redirect('/login');
        }

        if ($requiredRole && ($user['role'] ?? '') !== $requiredRole) {
            if ($request->isAjax()) {
                Response::error('Access denied. Administrator privileges required.', 403);
            }
            Response::view('auth/403', [
                'title' => 'Access Denied',
                'message' => 'You do not have permission to view or manage this administrative resource.'
            ], 'auth');
        }
    }
}
