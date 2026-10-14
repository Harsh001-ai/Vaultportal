<?php
namespace App\Middleware;

use App\Request;
use App\Response;
use App\Session;

class AuthMiddleware {
    public function handle(Request $request, ?string $arg = null): void {
        Session::start();
        $user = Session::get('user');

        if (!$user) {
            if ($request->isAjax()) {
                Response::error('Authentication required.', 401);
            }
            Session::set('redirect_url', $request->getPath());
            Response::redirect('/login');
        }

        // Verify account is active
        if (($user['status'] ?? '') === 'suspended') {
            Session::destroy();
            if ($request->isAjax()) {
                Response::error('Your account has been suspended by an administrator.', 403);
            }
            Response::redirect('/login?error=suspended');
        }
    }
}
