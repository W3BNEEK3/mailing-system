<?php

declare(strict_types=1);

/**
 * public/index.php — Front Controller
 *
 * This is the ONLY file in the public/ directory that PHP executes.
 * All HTTP requests are rewritten here by .htaccess.
 *
 * It does exactly two things:
 *   1. Defines BASE_PATH so the rest of the app can find project files
 *   2. Boots the application and runs the request lifecycle
 */

// BASE_PATH is the absolute path to the project root (one level above public/)
define('BASE_PATH', dirname(__DIR__));

// Boot the application. bootstrap/app.php returns the configured App instance.
$app = require BASE_PATH . '/bootstrap/app.php';

// Run the request: capture HTTP input → dispatch → send response → exit
$app->run();
