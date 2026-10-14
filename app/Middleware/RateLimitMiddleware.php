<?php
namespace App\Middleware;

use App\Database;
use App\Request;
use App\Response;

class RateLimitMiddleware {
    public function handle(Request $request, ?string $arg = null): void {
        // format: "maxAttempts,decaySeconds" e.g. "5,900"
        $maxAttempts = 5;
        $decay = 900;

        if ($arg) {
            $parts = explode(',', $arg);
            $maxAttempts = (int)($parts[0] ?? 5);
            $decay = (int)($parts[1] ?? 900);
        }

        $ip = $request->ip();
        $path = $request->getPath();
        $key = 'rate:' . md5($ip . ':' . $path);
        $now = time();

        try {
            $row = Database::fetch("SELECT * FROM rate_limits WHERE rate_key = :k", ['k' => $key]);
            if ($row) {
                if ($row['reset_at'] > $now) {
                    if ($row['hits'] >= $maxAttempts) {
                        $remaining = $row['reset_at'] - $now;
                        if ($request->isAjax()) {
                            Response::error("Too many attempts. Please try again in $remaining seconds.", 429, [
                                'retry_after' => $remaining
                            ]);
                        }
                        Response::view('auth/rate_limited', [
                            'title' => 'Rate Limit Exceeded',
                            'remaining' => $remaining
                        ], 'auth');
                    }
                    Database::query("UPDATE rate_limits SET hits = hits + 1 WHERE rate_key = :k", ['k' => $key]);
                } else {
                    // Reset expired window
                    Database::query("UPDATE rate_limits SET hits = 1, reset_at = :r WHERE rate_key = :k", [
                        'r' => $now + $decay,
                        'k' => $key
                    ]);
                }
            } else {
                Database::query("INSERT INTO rate_limits (rate_key, hits, reset_at) VALUES (:k, 1, :r)", [
                    'k' => $key,
                    'r' => $now + $decay
                ]);
            }
        } catch (\Exception $e) {
            // Fail open on rate limiter db failure, but log error
            error_log("RateLimit error: " . $e->getMessage());
        }
    }
}
