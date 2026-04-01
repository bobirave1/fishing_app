<?php

namespace App\Core;

/**
 * Simple file-based logger.
 *
 * Log levels follow PSR-3 conventions.
 * Logs are written to storage/logs/ with daily rotation.
 */
class Logger
{
    private string $logDir;

    public function __construct(?string $logDir = null)
    {
        $this->logDir = $logDir ?? dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Write a log entry to the daily log file.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $file = $this->logDir . "/app-{$date}.log";

        $interpolated = $this->interpolate($message, $context);

        $entry = "[{$time}] [{$level}] {$interpolated}";

        if (!empty($context['exception']) && $context['exception'] instanceof \Throwable) {
            $e = $context['exception'];
            $entry .= "\n  Exception: " . get_class($e)
                . " — " . $e->getMessage()
                . "\n  File: " . $e->getFile() . ':' . $e->getLine()
                . "\n  Trace:\n  " . str_replace("\n", "\n  ", $e->getTraceAsString());
        }

        $entry .= PHP_EOL;

        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Replace {key} placeholders with context values.
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $val) {
            if ($key === 'exception') {
                continue;
            }
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{' . $key . '}'] = (string) $val;
            }
        }
        return strtr($message, $replacements);
    }
}
