<?php

declare(strict_types=1);

/**
 * bootstrap/helpers.php
 *
 * Global helper functions available everywhere in the application and in views.
 * These are thin wrappers around the App container and core services.
 *
 * Composer's "files" autoload in composer.json ensures this file is loaded
 * automatically — you never need to require it manually.
 */

use App\Core\App;
use App\Core\Config;
use App\Core\Logger;
use App\Core\Session;
use App\Core\Response;

// ─── Config ───────────────────────────────────────────────────────────────

/**
 * Read a config value using dot notation.
 *
 * Usage:
 *   config('app.name')       => 'Emirates'
 *   config('database.host')  => 'localhost'
 *   config('app.missing', 'default') => 'default'
 */
function config(string $key, mixed $default = null): mixed
{
    return App::getInstance()->make(Config::class)->get($key, $default);
}

// ─── Views ────────────────────────────────────────────────────────────────
/**
 * Render a view file and optionally wrap it in a layout.
 *
 * Usage (no layout — returns the view HTML directly):
 *   $html = view('auth/login', ['pageTitle' => 'Sign In']);
 *
 * Usage (with layout — the view HTML is injected as $content):
 *   $html = view('compose/index', ['pageTitle' => 'Compose'], 'app');
 *
 * @param string      $path    Relative to resources/ — e.g. 'compose/index'
 * @param array       $data    Variables extracted into the view scope
 * @param string|null $layout  Layout name in resources/layouts/ (without .php)
 */
function view(string $path, array $data = [], ?string $layout = null): string
{
    // Resolve the view file path
    $file = BASE_PATH . '/resources/' . ltrim($path, '/') . '.php';

    if (!file_exists($file)) {
        throw new \RuntimeException("View not found: {$file}");
    }

    // Extract variables so they are available inside the view file as local variables.
    // EXTR_SKIP prevents user data from overwriting existing local variables like $file.
    extract($data, EXTR_SKIP);

    // Capture the view output into a string
    ob_start();
    require $file;
    $content = ob_get_clean();

    // If no layout requested, return the raw view output
    if ($layout === null) {
        return $content;
    }

    // Resolve the layout file
    $layoutFile = BASE_PATH . '/resources/layouts/' . $layout . '.php';

    if (!file_exists($layoutFile)) {
        throw new \RuntimeException("Layout not found: {$layoutFile}");
    }

    // Re-extract data so the layout also has access to $pageTitle, etc.
    // $content is already set above — it's injected as the slot.
    extract($data, EXTR_SKIP);

    ob_start();
    require $layoutFile;
    return ob_get_clean();
}

// ─── Redirects ────────────────────────────────────────────────────────────

/**
 * Create a redirect response.
 *
 * Usage:
 *   return redirect('/login');
 *   return redirect('/compose', 302);
 */
function redirect(string $url, int $status = 302): Response
{
    return Response::redirect($url, $status);
}

/**
 * Redirect back to the previous page.
 *
 * Usage:
 *   return back();
 */
function back(): Response
{
    return Response::back();
}

// ─── Security ─────────────────────────────────────────────────────────────

/**
 * HTML-escape a value to prevent XSS attacks.
 * Always use this when outputting user-supplied data in HTML.
 *
 * Usage in views:
 *   <p><?= e($user->name) ?></p>
 *   <input value="<?= e($email) ?>">
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get the CSRF token for the current session.
 * Used in the <meta> tag in the layout.
 *
 * Usage:
 *   <meta name="csrf-token" content="<?= csrf_token() ?>">
 */
function csrf_token(): string
{
    return session()->csrfToken();
}

/**
 * Output a hidden CSRF input field for use in forms.
 *
 * Usage in views:
 *   <form method="POST">
 *       <?= csrf_field() ?>
 *       ...
 *   </form>
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

// ─── Session ──────────────────────────────────────────────────────────────

/**
 * Get the Session instance.
 *
 * Usage:
 *   session()->set('user_id', 1);
 *   session()->get('user_id');
 */
function session(): Session
{
    return App::getInstance()->make(Session::class);
}

/**
 * Get a previously flashed input value (for repopulating forms after validation errors).
 */
function old(string $key, mixed $default = ''): mixed
{
    $oldInput = session()->getFlash('old') ?? [];

    if (!empty($oldInput)) {
        session()->flash('old', $oldInput);
    }

    return isset($oldInput[$key]) ? e($oldInput[$key]) : $default;
}

/**
 * Get validation errors flashed to the session.
 */
function errors(?string $key = null): array|string|null
{
    $allErrors = session()->getFlash('errors') ?? [];

    if (!empty($allErrors)) {
        session()->flash('errors', $allErrors);
    }

    if ($key !== null) {
        return $allErrors[$key] ?? null;
    }

    return $allErrors;
}

/**
 * Get a flash message from the session (one-time read).
 */
function flash(string $key, mixed $default = null): mixed
{
    return session()->getFlash($key, $default);
}

/**
 * Get the current Request instance.
 */
function request(): \App\Core\Request
{
    return \App\Core\Request::capture();
}


// ─── Paths ────────────────────────────────────────────────────────────────

/**
 * Get the absolute path to a file inside the storage/ directory.
 *
 * Usage:
 *   storage_path('logs')                  => /var/www/emirates/storage/logs
 *   storage_path('uploads/logos/logo.png') => /var/www/emirates/storage/uploads/logos/logo.png
 */
function storage_path(string $path = ''): string
{
    $base = BASE_PATH . '/storage';
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

/**
 * Get the full URL to a public asset file.
 *
 * Usage:
 *   asset('css/app.css')   => http://localhost:8000/assets/css/app.css
 *   asset('js/htmx.min.js') => http://localhost:8000/assets/js/htmx.min.js
 */
function asset(string $path): string
{
    $base = rtrim(config('app.url', 'http://localhost'), '/');
    return $base . '/assets/' . ltrim($path, '/');
}

/**
 * Get the full URL to an application route.
 *
 * Usage:
 *   url('/login')      => http://localhost:8000/login
 *   url('/recipients') => http://localhost:8000/recipients
 */
function url(string $path = ''): string
{
    $base = rtrim(config('app.url', 'http://localhost'), '/');
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

// ─── Logging ──────────────────────────────────────────────────────────────

/**
 * Get the Logger instance.
 *
 * Usage:
 *   logger()->info('User logged in', ['user_id' => 1]);
 *   logger()->error('Send failed', ['provider' => 'resend']);
 */
function logger(): Logger
{
    return App::getInstance()->make(Logger::class);
}

// ─── Settings ─────────────────────────────────────────────────────────────

/**
 * Read a value from the settings table (app-level configuration saved by the user).
 *
 * This is a STUB for Phase 0. It returns the $default until the
 * SettingRepository is built in Phase 1.
 *
 * Usage:
 *   setting('primary_color', '#4F46E5')
 *   setting('default_sender_name')
 */
function setting(string $key, mixed $default = null): mixed
{
    // Phase 0 stub — will be replaced in Phase 1 once SettingRepository exists.
    // At that point this function will look up the value in the settings DB table.
    try {
        if (class_exists(\App\Repositories\SettingRepository::class)) {
            static $repo = null;
            if ($repo === null) {
                $repo = new \App\Repositories\SettingRepository();
            }
            return $repo->get($key, $default);
        }
    } catch (\Throwable) {
        // Fall through to default
    }

    return $default;
}
