<?php
namespace App\Middleware;

use App\Request;
use App\Response;
use App\Session;

class CsrfMiddleware {
    public function handle(Request $request, ?string $arg = null): void {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->header('X-CSRF-TOKEN')
                ?? $request->post('_csrf_token')
                ?? $request->json('_csrf_token');

            if (!Session::validateCsrfToken($token)) {
                if ($request->isAjax()) {
                    Response::error('Invalid or expired CSRF security token.', 403);
                }
                Session::flash('error', 'Security token expired. Please try again.');
                Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        }
    }
}
