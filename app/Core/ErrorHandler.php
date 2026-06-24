<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Exceptions\AppException;

/**
 * ErrorHandler
 *
 * Registers PHP's exception, error, and shutdown handlers.
 * In debug mode: renders the full debug view with source snippet.
 * In production: logs the error and renders an appropriate error page.
 * For HTMX requests in production: returns HX-Trigger toast instead of a full page.
 */
class ErrorHandler
{
    private bool   $debug;
    private Logger $logger;

    public function __construct(bool $debug, Logger $logger)
    {
        $this->debug  = $debug;
        $this->logger = $logger;
    }

    /**
     * Register all PHP error and exception handlers.
     * Call this once during bootstrap, before any application code runs.
     */
    public function register(): void
    {
        // Uncaught exceptions
        set_exception_handler([$this, 'handleException']);

        // PHP errors converted to exceptions
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });

        // Fatal errors not caught by set_error_handler (parse errors, OOM, etc.)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->renderError(500, 'Fatal Error: ' . $error['message']);
            }
        });
    }

    /**
     * Main exception handler.
     * Determines the HTTP status code and renders the appropriate response.
     */
    public function handleException(\Throwable $e): void
    {
        $status = $this->statusForException($e);

        // Log everything at 500 level; skip logging for expected 404s and 401s
        if ($status >= 500) {
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }

        if ($this->debug) {
            $this->renderDebug($e);
        } elseif ($this->isHtmxRequest()) {
            $this->renderHtmxError($status, $e->getMessage());
        } else {
            $this->renderProduction($status, $e);
        }
    }

    // ── Internal helpers ───────────────────────────────────────────────────

    /**
     * Map exception types to HTTP status codes.
     */
    private function statusForException(\Throwable $e): int
    {
        return match (true) {
            $e instanceof NotFoundException  => 404,
            $e instanceof AuthException      => 401,
            $e instanceof ValidationException => 422,
            $e instanceof AppException       => $e->getCode() >= 400 ? $e->getCode() : 500,
            default                           => 500,
        };
    }

    /**
     * Render the full debug view (dev only).
     */
    private function renderDebug(\Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        // Build source snippet: 5 lines before and 5 lines after the error line
        $sourceLines = $this->extractSourceLines($e->getFile(), $e->getLine(), 5);

        // Render the debug view directly (no layout wrapper — it's self-contained)
        extract(['exception' => $e, 'sourceLines' => $sourceLines]);
        require BASE_PATH . '/resources/error/debug.php';
        exit;
    }

    /**
     * Render an appropriate production error page (using the error layout).
     */
    private function renderProduction(int $status, \Throwable $e): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');

        $viewFile = BASE_PATH . '/resources/error/' . $status . '.php';

        // Fall back to 500 if no specific view exists for this status code
        if (!file_exists($viewFile)) {
            $viewFile = BASE_PATH . '/resources/error/500.php';
        }

        $errorRef = date('YmdHis');

        // Render view into $content
        ob_start();
        extract(['errorRef' => $errorRef]);
        require $viewFile;
        $content = ob_get_clean();

        // Wrap in the error layout
        $layoutFile = BASE_PATH . '/resources/layouts/error.php';
        $pageTitle  = $status . ' — Error';

        extract(['content' => $content, 'pageTitle' => $pageTitle]);
        require $layoutFile;
        exit;
    }

    /**
     * For HTMX requests in production: return a JSON trigger that shows a toast.
     * HTMX will receive this and fire the 'showToast' event on the client.
     */
    private function renderHtmxError(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/html');
        header('HX-Trigger: ' . json_encode([
            'showToast' => [
                'type'    => 'error',
                'message' => 'An error occurred: ' . $message,
            ],
        ]));
        echo '';
        exit;
    }

    /**
     * Check if the current request was made by HTMX.
     */
    private function isHtmxRequest(): bool
    {
        return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
    }

    /**
     * Extract source code lines around the error location.
     *
     * @param string $file        Absolute path to the PHP file
     * @param int    $errorLine   The line number where the error occurred
     * @param int    $context     Number of lines to include before and after
     * @return array  Array of ['line' => int, 'code' => string, 'active' => bool]
     */
    private function extractSourceLines(string $file, int $errorLine, int $context = 5): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $lines  = file($file, FILE_IGNORE_NEW_LINES);
        $start  = max(0, $errorLine - $context - 1);
        $end    = min(count($lines) - 1, $errorLine + $context - 1);
        $result = [];

        for ($i = $start; $i <= $end; $i++) {
            $result[] = [
                'line'   => $i + 1,
                'code'   => $lines[$i],
                'active' => ($i + 1) === $errorLine,
            ];
        }

        return $result;
    }

    /**
     * Render a plain-text error fallback (used by renderError for fatal shutdown errors).
     */
    private function renderError(int $status, string $message): void
    {
        if (headers_sent()) return;

        http_response_code($status);
        if ($this->debug) {
            echo '<h1>Fatal Error</h1><pre>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</pre>';
        } else {
            echo '<h1>Internal Server Error</h1><p>An unexpected error occurred. Please try again.</p>';
        }
        exit;
    }
}