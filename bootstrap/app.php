<?php

declare(strict_types=1);

/**
 * bootstrap/app.php
 *
 * This is the application bootstrapper. It:
 *   1. Loads the Composer autoloader (makes all our classes available)
 *   2. Loads the .env file so config files can read $_ENV
 *   3. Creates the App service container
 *   4. Registers all singletons and bindings
 *   5. Loads the route files
 *   6. Returns the App instance to public/index.php
 */

// ── 1. Autoloader ──────────────────────────────────────────────────────────
require_once BASE_PATH . '/vendor/autoload.php';

// ── 2. Load .env early so config files can read $_ENV ──────────────────────
\App\Core\EnvLoader::load(BASE_PATH . '/.env');

// Set timezone immediately after .env is loaded
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

// ── 3. Create the App container ────────────────────────────────────────────
$app = new \App\Core\App();
\App\Core\App::setInstance($app);

// ── 4. Register core singletons ────────────────────────────────────────────

// Config: reads config/ files, accessed via config('app.name')
$app->singleton(\App\Core\Config::class, fn() =>
    new \App\Core\Config(BASE_PATH . '/config')
);

// Logger: writes to storage/logs/app-YYYY-MM-DD.log
$app->singleton(\App\Core\Logger::class, fn() =>
    new \App\Core\Logger(BASE_PATH . '/storage/logs')
);

// Session: wraps $_SESSION with a clean API
$app->singleton(\App\Core\Session::class, fn() =>
    new \App\Core\Session()
);

// Database: single PDO connection for the whole request
$app->singleton(\App\Core\Database::class, fn() =>
    \App\Core\Database::getInstance()
);

// Router: registers and dispatches routes
$app->singleton(\App\Core\Router::class, fn() =>
    new \App\Core\Router()
);

// ── Repository bindings ────────────────────────────────────────────────────
// These let controllers and services call $app->make(SettingRepository::class)
// or use type-hinted constructor injection in future phases.

$app->singleton(\App\Repositories\SettingRepository::class, fn() =>
    new \App\Repositories\SettingRepository()
);

$app->singleton(\App\Repositories\TemplateRepository::class, fn() =>
    new \App\Repositories\TemplateRepository()
);

$app->singleton(\App\Repositories\DraftRepository::class, fn() =>
    new \App\Repositories\DraftRepository()
);

$app->singleton(\App\Repositories\RecipientRepository::class, fn() =>
    new \App\Repositories\RecipientRepository()
);

$app->singleton(\App\Repositories\LogRepository::class, fn() =>
    new \App\Repositories\LogRepository()
);


// ── 5. Register ErrorHandler and activate it ───────────────────────────────
$debug  = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$logger = $app->make(\App\Core\Logger::class);

$errorHandler = new \App\Core\ErrorHandler($debug, $logger);
$errorHandler->register();

// Turn off PHP's default error display in production
if (!$debug) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    error_reporting(E_ALL);
}

// ── 6. Load routes ─────────────────────────────────────────────────────────
// We get the router instance and pass it to the route files
$router = $app->make(\App\Core\Router::class);

require BASE_PATH . '/routes/web.php';
//require BASE_PATH . '/routes/api.php';

// ── 7. Return the App instance to public/index.php ─────────────────────────
return $app;
