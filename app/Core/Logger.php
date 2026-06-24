<?php

declare(strict_types=1);

namespace App\Core;

use App\Interfaces\LoggerInterface;

/**
 * Logger
 *
 * Writes log entries to daily files in the format:
 *   [2025-06-15 14:32:01] INFO: User logged in {"user_id":1}
 *
 * Log files are named: app-YYYY-MM-DD.log
 * They live in the directory passed to the constructor.
 */
class Logger implements LoggerInterface
{
    private string $logDirectory;

    public function __construct(string $logDirectory)
    {
        $this->logDirectory = rtrim($logDirectory, '/\\');
        $this->ensureDirectoryExists();
    }

    // ─── Public log level methods ──────────────────────────────────────────

    public function debug(string $message, array $context = []): void
    {
        $this->write('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write('CRITICAL', $message, $context);
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Write a log entry to today's log file.
     *
     * This method is wrapped in try/catch so a logging failure
     * never crashes the application.
     */
    private function write(string $level, string $message, array $context): void
    {
        try {
            // Today's log file: storage/logs/app-2025-06-15.log
            $filename = $this->logDirectory . '/app-' . date('Y-m-d') . '.log';

            // Build the log line
            $timestamp = date('Y-m-d H:i:s');
            $line      = "[{$timestamp}] {$level}: {$message}";

            // Append context data as JSON if provided
            if (!empty($context)) {
                $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $line .= PHP_EOL;

            // FILE_APPEND means we add to the end of the file, not overwrite it.
            // LOCK_EX prevents two requests writing at the exact same time (race condition).
            file_put_contents($filename, $line, FILE_APPEND | LOCK_EX);

        } catch (\Throwable) {
            // Silently ignore logging failures.
            // The logger must NEVER crash the application.
        }
    }

    /**
     * Make sure the log directory exists.
     * Creates it recursively if it doesn't.
     */
    private function ensureDirectoryExists(): void
    {
        try {
            if (!is_dir($this->logDirectory)) {
                mkdir($this->logDirectory, 0775, true);
            }
        } catch (\Throwable) {
            // Silently ignore — the write() method will also silently fail
            // if the directory truly can't be created.
        }
    }
}
