<?php

namespace App\Core;

/**
 * Simple middleware helpers for authentication and authorization.
 */
class Middleware
{
    /**
     * Require an authenticated session. Redirects for page requests,
     * returns JSON 401 for API requests.
     */
    public static function auth(): int
    {
        if (!isset($_SESSION['user_id'])) {
            if (self::isJsonRequest()) {
                Response::jsonError('Unauthorized', 401);
            }
            Response::redirect('/fishing_app/login');
        }
        return (int) $_SESSION['user_id'];
    }

    /**
     * Verify CSRF token on POST requests.
     */
    public static function csrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrfToken($token)) {
            if (self::isJsonRequest()) {
                Response::jsonError('Invalid CSRF token', 403);
            }
            http_response_code(403);
            exit('Invalid CSRF token');
        }
    }

    /**
     * Apply rate limiting. Returns true if within limit, exits with error if exceeded.
     */
    public static function rateLimit(string $key, int $max = 10, int $window = 300): void
    {
        if (!checkRateLimit($key, $max, $window)) {
            if (self::isJsonRequest()) {
                Response::jsonError('Too many requests. Please wait.', 429);
            }
            http_response_code(429);
            exit('Rate limit exceeded');
        }
    }

    private static function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }
}
