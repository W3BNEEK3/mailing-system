<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session
 *
 * A wrapper around PHP's native session handling.
 *
 * Flash values:
 *   Flash values are stored in $_SESSION['_flash'] and are meant
 *   to survive exactly ONE redirect (e.g. after a form submission).
 *   They are deleted the first time they are read back.
 *
 * Usage:
 *   session()->set('user_id', 42);
 *   session()->get('user_id');         // 42
 *   session()->flash('errors', [...]);
 *   session()->getFlash('errors');     // value, then it's gone
 */
class Session
{
    /**
     * Start the session with the settings from config/session.php.
     * Safe to call multiple times — PHP only starts once.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Session is already running — don't start it again
            return;
        }

        // Apply session cookie settings before starting the session
        $config = require(defined('BASE_PATH') ? BASE_PATH . '/config/session.php' : dirname(__DIR__, 2) . '/config/session.php');

        session_name($config['name'] ?? 'emirates_session');

        session_set_cookie_params([
            'lifetime' => $config['lifetime'] ?? 7200,
            'path'     => $config['path']     ?? '/',
            'secure'   => $config['secure']   ?? false,
            'httponly' => $config['httponly']  ?? true,
            'samesite' => $config['samesite']  ?? 'Lax',
        ]);

        session_start();
    }

    // ─── Core get/set ─────────────────────────────────────────────────────

    /**
     * Store a value in the session.
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     * Returns $default if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a key exists in the session.
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // ─── Flash messages ───────────────────────────────────────────────────

    /**
     * Store a flash value — it will be available to read ONCE, then deleted.
     * Typically used to pass messages or error arrays across a redirect.
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Retrieve and delete a flash value.
     * Returns $default if the flash key does not exist.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        // Grab the value, then immediately delete it
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    // ─── Security ─────────────────────────────────────────────────────────

    /**
     * Destroy the current session completely.
     * Used on logout.
     */
    public function destroy(): void
    {
        // Clear all session data
        $_SESSION = [];

        // Delete the session cookie from the browser
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session on the server
        session_destroy();
    }

    /**
     * Regenerate the session ID.
     * Call this after a successful login to prevent session fixation attacks.
     * The 'true' parameter deletes the old session file immediately.
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Get the CSRF token for the current session.
     * Generates and stores a new one if it doesn't exist yet.
     *
     * The token is a random 32-byte hex string.
     */
    public function csrfToken(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            // bin2hex(random_bytes(32)) gives us 64 hex characters of randomness
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}
