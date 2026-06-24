<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;

/**
 * ErrorHandler
 *
 * Registers PHP error and exception handlers so all errors are caught
 * and handled consistently instead of showing raw PHP error messages.
 *
 * In DEBUG mode:    Shows a detailed debug page with stack trace.
 * In PRODUCTION:    Logs the error and shows a friendly error page.
 * For HTMX:        Returns a toast trigger header instead of an HTML error page.
 */
class ErrorHandler
{
    private bool $debug;
    private ?Logger $logger;

    public function __construct(bool $debug = false, ?Logger $logger = null)
    {
        $this->debug  = $debug;
        $this->logger = $logger;
    }

    /**
     * Register all three PHP error handlers.
     * Call this once in bootstrap/app.php.
     */
    public function register(): void
    {
        // Handle uncaught exceptions
        set_exception_handler([$this, 'handleException']);

        // Handle PHP errors (E_WARNING, E_NOTICE, etc.) by converting them to exceptions
        set_error_handler([$this, 'handleError']);

        // Handle fatal errors that bypass set_error_handler
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle an uncaught exception.
     * This is the main method — called by PHP when an exception isn't caught.
     */
    public function handleException(\Throwable $exception): void
    {
        // Map exception type to HTTP status code
        $statusCode = $this->getStatusCode($exception);

        // Log the error (in production)
        if (!$this->debug && $this->logger) {
            $this->logger->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);
        }

        // Clear any output that was buffered before the error occurred
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($statusCode);

        // Check if this is an HTMX request
        $isHtmx = ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';

        if ($isHtmx && !$this->debug) {
            // For HTMX: return a trigger header so the toast system shows the error
            header('Content-Type: text/html');
            header('HX-Trigger: ' . json_encode([
                'showToast' => [
                    'type'    => 'error',
                    'message' => $this->getSafeMessage($exception),
                ]
            ]));
            echo ''; // Empty body — HTMX reads the header, not the body
            exit;
        }

        if ($this->debug) {
            // Show the detailed debug page
            $this->renderDebugPage($exception);
        } else {
            // Show the appropriate friendly error page
            $this->renderErrorPage($statusCode, $exception);
        }
    }

    /**
     * Convert PHP errors into ErrorException so they're handled the same as exceptions.
     * Called by PHP's error handling system.
     */
    public function handleError(
        int    $level,
        string $message,
        string $file   = '',
        int    $line   = 0
    ): bool {
        // Only handle errors that match the current error_reporting level
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handle fatal errors (memory exhaustion, parse errors, etc.)
     * that can't be caught by set_error_handler.
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $this->handleException(
                new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
            );
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Map exception types to HTTP status codes.
     */
    private function getStatusCode(\Throwable $exception): int
    {
        return match (true) {
            $exception instanceof NotFoundException   => 404,
            $exception instanceof AuthException       => 401,
            $exception instanceof ValidationException => 422,
            $exception instanceof AppException        => $exception->getCode() ?: 500,
            default                                   => 500,
        };
    }

    /**
     * Get a safe error message for users — never expose internal details.
     */
    private function getSafeMessage(\Throwable $exception): string
    {
        // App exceptions have messages that are safe to show users
        if ($exception instanceof AppException) {
            return $exception->getMessage();
        }

        // For other exceptions, show a generic message
        return 'An unexpected error occurred. Please try again.';
    }

    /**
     * Render the developer debug page with full exception details.
     */
    private function renderDebugPage(\Throwable $exception): void
    {
        // Gather source code lines around where the error occurred
        $sourceLines = $this->getSourceLines($exception->getFile(), $exception->getLine());

        $exceptionClass = get_class($exception);
        $message        = htmlspecialchars($exception->getMessage());
        $file           = htmlspecialchars($exception->getFile());
        $line           = $exception->getLine();
        $trace          = htmlspecialchars($exception->getTraceAsString());

        // This is an inline template — no external dependencies needed
        // so it works even if the view system is broken
        include BASE_PATH . '/resources/error/debug.php';
    }

    /**
     * Render a user-friendly error page (404, 500, etc.)
     */
    private function renderErrorPage(int $statusCode, \Throwable $exception): void
    {
        $viewFile = match ($statusCode) {
            404 => BASE_PATH . '/resources/error/404.php',
            403 => BASE_PATH . '/resources/error/403.php',
            default => BASE_PATH . '/resources/error/500.php',
        };

        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            // Absolute fallback if even the error view is missing
            echo "<h1>Error {$statusCode}</h1><p>Something went wrong.</p>";
        }
    }

    /**
     * Read 10 lines of source code around the error line for the debug view.
     *
     * Returns an array of ['line_number' => 'code_line'] pairs.
     */
    private function getSourceLines(string $file, int $errorLine, int $context = 5): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $allLines = file($file);
        if ($allLines === false) {
            return [];
        }

        // Calculate start and end lines (1-indexed)
        $start  = max(1, $errorLine - $context);
        $end    = min(count($allLines), $errorLine + $context);
        $result = [];

        for ($i = $start; $i <= $end; $i++) {
            // $allLines is 0-indexed, line numbers are 1-indexed
            $result[$i] = $allLines[$i - 1];
        }

        return $result;
    }
}
