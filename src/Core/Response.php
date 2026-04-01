<?php

namespace App\Core;

/**
 * Lightweight response helpers for controllers.
 */
class Response
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function jsonOk(array $data = []): never
    {
        self::json(array_merge(['success' => true], $data));
    }

    public static function jsonError(string $message, int $status = 400): never
    {
        self::json(['success' => false, 'error' => $message], $status);
    }

    public static function redirect(string $url, int $status = 302): never
    {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Render a PHP template with the given data extracted into scope.
     */
    public static function view(string $template, array $data = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/templates/' . $template . '.php';
        if (!is_file($templatePath)) {
            http_response_code(500);
            exit("Template not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        require $templatePath;
    }
}
