<?php

namespace App\Core;

/**
 * Global exception and error handler.
 *
 * Catches uncaught exceptions and PHP errors, logs them,
 * and returns a clean JSON or HTML error to the client.
 */
class ExceptionHandler
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Register this handler as the global exception/error handler.
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Handle an uncaught exception.
     */
    public function handleException(\Throwable $e): void
    {
        $this->logger->error('Uncaught exception: {message}', [
            'message' => $e->getMessage(),
            'exception' => $e,
        ]);

        if (!headers_sent()) {
            http_response_code(500);
        }

        if ($this->isJsonRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode([
                'success' => false,
                'error' => 'An internal error occurred. Please try again later.',
            ]);
        } else {
            echo '<h1>500 — Internal Server Error</h1>';
            echo '<p>Something went wrong. The error has been logged.</p>';
        }
    }

    /**
     * Convert PHP errors to ErrorException.
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    private function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json');
    }
}
