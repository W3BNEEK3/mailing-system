### Emirates — Phase 0: Project Scaffolding & Environment Setup

**Version:** 1.0
**Phase:** 0 of 14
**Goal:** A running PHP application that responds to HTTP requests, loads config, connects to the database, and handles errors — before a single feature is built.

---

## How to Use This Document

Work through every task **in order**. Do not skip ahead. Each section builds on the previous one. When you see a file path like `app/Core/Config.php`, it is always relative to your project root `emirates/`.

Copy each code block exactly into the correct file. Comments in the code explain what each part does.

**Checklist notation:**

- `[ ]` Not started
- `[x]` Complete

---

## 0.1 — Repository & Local Environment

These are manual steps you perform in your terminal before writing any code.

- [ ] **0.1.1** Create the project root directory and enter it:

  ```bash
  mkdir emirates
  cd emirates
  ```
- [ ] **0.1.2** Initialise a Git repository:

  ```bash
  git init
  ```
- [ ] **0.1.3** Create `.gitignore` in the root. This tells Git which files and folders to never track:

  ```
  /vendor/
  /storage/logs/
  /storage/uploads/
  /storage/sessions/
  /storage/cache/
  /.env
  node_modules/
  *.DS_Store
  ```
- [ ] **0.1.4** Confirm your PHP version is 8.3 or higher:

  ```bash
  php -v
  # Should print: PHP 8.3.x ...
  ```
- [ ] **0.1.5** Confirm the required PHP extensions are enabled. Run this and look for each one in the output:

  ```bash
  php -m | grep -E "pdo_mysql|curl|mbstring|fileinfo|openssl|json"
  ```

  All six must appear. If any are missing, enable them in your `php.ini`.
- [ ] **0.1.6** Confirm MySQL is running and you can connect to it:

  ```bash
  mysql -u root -p
  # If this opens a MySQL prompt, you're good. Type exit to leave.
  ```
- [ ] **0.1.7** Create the application database:

  ```sql
  CREATE DATABASE emirates CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```
- [ ] **0.1.8** Confirm Composer is installed:

  ```bash
  composer -v
  # Should print Composer version info
  ```

---

## 0.2 — Directory Skeleton

Create every directory the project needs. We add a `.gitkeep` file inside empty directories so Git tracks them (Git does not track empty folders).

Run this entire block in your terminal from inside the `emirates/` root:

```bash
# App namespace directories
mkdir -p app/Core
mkdir -p app/Controllers
mkdir -p app/Middlewares
mkdir -p app/Models
mkdir -p app/Repositories/Contracts
mkdir -p app/Services
mkdir -p app/Providers/Contracts
mkdir -p app/Interfaces
mkdir -p app/Helpers
mkdir -p app/Exceptions
mkdir -p app/DTOs

# Config
mkdir -p config

# Database
mkdir -p database/migrations
mkdir -p database/seeders

# Frontend views
mkdir -p resources/layouts
mkdir -p resources/auth
mkdir -p resources/compose
mkdir -p resources/recipients
mkdir -p resources/logs
mkdir -p resources/settings/templates
mkdir -p resources/components/cards
mkdir -p resources/components/tables
mkdir -p resources/components/forms
mkdir -p resources/components/ui
mkdir -p resources/components/navigation
mkdir -p resources/error

# Runtime storage (these are gitignored but the structure must exist)
mkdir -p storage/logs
mkdir -p storage/uploads/logos/global
mkdir -p storage/uploads/logos/email
mkdir -p storage/uploads/templates
mkdir -p storage/sessions
mkdir -p storage/cache

# Public assets
mkdir -p assets/css
mkdir -p assets/js
mkdir -p assets/icons
mkdir -p assets/img

# Bootstrap and routes
mkdir -p bootstrap
mkdir -p routes

# Web root
mkdir -p public

# Add .gitkeep to storage subdirectories so Git tracks them
touch storage/logs/.gitkeep
touch storage/uploads/logos/global/.gitkeep
touch storage/uploads/logos/email/.gitkeep
touch storage/uploads/templates/.gitkeep
touch storage/sessions/.gitkeep
touch storage/cache/.gitkeep
```

- [ ] **0.2 Complete** — Verify your directory tree looks correct:
  ```bash
  find . -type d | grep -v vendor | grep -v ".git" | sort
  ```

---

## 0.3 — Composer Setup

- [ ] **0.3.1** Create `composer.json` in the project root:

```json
{
    "name": "emirates/app",
    "description": "Emirates — Single-Tenant Email Sending Platform",
    "type": "project",
    "require": {
        "php": "^8.3",
        "phpmailer/phpmailer": "^6.9"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        },
        "files": [
            "bootstrap/helpers.php"
        ]
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist"
    }
}
```

- [ ] **0.3.2** Install dependencies. This downloads PHPMailer and generates the autoloader:

  ```bash
  composer install
  ```
- [ ] **0.3.3** Confirm the autoloader was created:

  ```bash
  ls vendor/autoload.php
  # Should print: vendor/autoload.php
  ```

---

## 0.4 — Environment File

- [ ] **0.4.1** Create `.env.example` in the project root. This is the template file committed to Git. It documents every variable but contains no real secrets:

```dotenv
# Application
APP_NAME="Emirates"
APP_URL=http://localhost:8000
APP_DEBUG=true
APP_KEY=

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=emirates
DB_USER=root
DB_PASS=

# Admin user (used by seeder only)
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=changeme

# LibreTranslate
LIBRETRANSLATE_URL=https://libretranslate.com
LIBRETRANSLATE_API_KEY=

# Resend Webhook Signing Secret
RESEND_WEBHOOK_SECRET=

# Session
SESSION_LIFETIME=7200

# Timezone (use PHP timezone identifiers, e.g. Africa/Lagos, UTC, America/New_York)
TIMEZONE=Africa/Lagos
```

- [ ] **0.4.2** Copy `.env.example` to `.env` and fill in your local values:

  ```bash
  cp .env.example .env
  ```

  Then open `.env` and set `DB_USER`, `DB_PASS`, and any other local values.
- [ ] **0.4.3** Generate a secure `APP_KEY`. Run this in your terminal and paste the output into `.env`:

  ```bash
  php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
  ```

  Your `.env` `APP_KEY` line should look like:

  ```
  APP_KEY=base64:abc123...your64charstring...xyz
  ```

---

## 0.5 — Config Files

Config files are plain PHP files that return an array. They read their values from `$_ENV`, which is populated from `.env` by the `EnvLoader` we build in section 0.6.

- [ ] **0.5.1** Create `config/app.php`:

```php
<?php

// General application configuration.
// Values are read from the .env file via $_ENV.

return [
    // The name displayed in the browser tab and emails
    'name' => $_ENV['APP_NAME'] ?? 'Emirates',

    // The full base URL of the application (no trailing slash)
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),

    // When true, shows the detailed debug error page instead of a generic 500
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN),

    // The 32-byte base64-encoded encryption key used for AES-256 encryption
    'key' => $_ENV['APP_KEY'] ?? '',

    // PHP timezone identifier — used for date display and log timestamps
    'timezone' => $_ENV['TIMEZONE'] ?? 'UTC',
];
```

- [ ] **0.5.2** Create `config/database.php`:

```php
<?php

// MySQL database connection configuration.

return [
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => $_ENV['DB_PORT'] ?? '3306',
    'name'    => $_ENV['DB_NAME'] ?? 'emirates',
    'user'    => $_ENV['DB_USER'] ?? 'root',
    'pass'    => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
```

- [ ] **0.5.3** Create `config/mail.php`:

```php
<?php

// Email sending defaults.
// The active_provider is overridden at runtime by whatever is saved
// in the credentials table via the Email Credentials settings page.

return [
    // Which provider to use if none is saved in the database yet
    // Options: 'resend' or 'smtp'
    'active_provider' => 'resend',

    // Default "From" name shown to email recipients
    'default_sender_name' => $_ENV['APP_NAME'] ?? 'Emirates',

    // Default "From" email address (must match your verified sending domain)
    'default_sender_email' => '',
];
```

- [ ] **0.5.4** Create `config/storage.php`:

```php
<?php

// File storage configuration.

return [
    // Absolute path to the storage/uploads directory
    // BASE_PATH is defined in public/index.php as the project root
    'uploads_path' => (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/storage/uploads',

    // Maximum allowed logo file size: 2MB in bytes
    'max_logo_size' => 2 * 1024 * 1024,

    // Maximum allowed template file size: 5MB in bytes
    'max_template_size' => 5 * 1024 * 1024,

    // Allowed MIME types for logo uploads
    'allowed_logo_mimes' => ['image/png', 'image/jpeg', 'image/svg+xml'],

    // Allowed MIME types for template uploads
    'allowed_template_mimes' => ['text/html', 'application/zip', 'application/x-zip-compressed'],
];
```

- [ ] **0.5.5** Create `config/session.php`:

```php
<?php

// PHP session configuration.

return [
    // How long a session stays alive in seconds (7200 = 2 hours)
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),

    // The name of the session cookie
    'name' => 'emirates_session',

    // Cookie path — '/' means the cookie works across the whole site
    'path' => '/',

    // Set to true in production when running on HTTPS
    // In development on HTTP, this must be false or sessions won't work
    'secure' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN) === false,

    // HttpOnly means JS cannot read the session cookie (security hardening)
    'httponly' => true,

    // SameSite prevents the cookie from being sent in cross-site requests (CSRF protection)
    'samesite' => 'Lax',
];
```

- [ ] **0.5.6** Create `config/translation.php`:

```php
<?php

// LibreTranslate API configuration.

return [
    // Base URL of the LibreTranslate instance (public or self-hosted)
    'base_url' => rtrim($_ENV['LIBRETRANSLATE_URL'] ?? 'https://libretranslate.com', '/'),

    // API key (required for the public instance; may be empty for self-hosted)
    'api_key' => $_ENV['LIBRETRANSLATE_API_KEY'] ?? '',

    // Supported languages shown in the Translate dropdown
    // Format: 'language_code' => 'Display Name'
    'supported_languages' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'de' => 'German',
        'zh' => 'Chinese (Simplified)',
        'yo' => 'Yoruba',
        'ha' => 'Hausa',
        'ig' => 'Igbo',
    ],
];
```

---

## 0.6 — Core Framework Files

This is the heart of Phase 0. We build the MVC framework from scratch. Work through each class in order — they depend on each other.

---

### 0.6.1 — Config Class

The `Config` class loads PHP config files and gives you access to their values using dot notation (e.g. `config('app.debug')` reads the `debug` key from `config/app.php`).

- [ ] **0.6.1** Create `app/Core/Config.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Config
 *
 * Loads configuration files from the config/ directory and provides
 * dot-notation access to their values.
 *
 * Usage:
 *   $config = new Config('/path/to/config');
 *   $config->get('app.debug');        // reads config/app.php -> 'debug' key
 *   $config->get('database.host');    // reads config/database.php -> 'host' key
 *   $config->get('app.missing', 'default'); // returns 'default' if key not found
 */
class Config
{
    /**
     * Stores already-loaded config files so we only read each file once per request.
     * Format: ['app' => ['name' => 'Emirates', 'debug' => true, ...], ...]
     */
    private array $cache = [];

    /**
     * The absolute path to the config/ directory.
     */
    private string $configPath;

    public function __construct(string $configPath)
    {
        // Remove any trailing slash so we can safely append filenames later
        $this->configPath = rtrim($configPath, '/\\');
    }

    /**
     * Get a config value using dot notation.
     *
     * The first segment before the dot is the filename.
     * Everything after the dot is the key within that file's array.
     *
     * Examples:
     *   get('app.name')       => value of 'name' key in config/app.php
     *   get('database.host')  => value of 'host' key in config/database.php
     *   get('app.missing', 'fallback') => 'fallback' (key does not exist)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Split 'app.debug' into ['app', 'debug']
        $parts = explode('.', $key, 2);

        // The first part is always the config filename (without .php)
        $file = $parts[0];

        // The second part is the key inside that file (may not exist for top-level access)
        $setting = $parts[1] ?? null;

        // Load the file into the cache if we haven't already
        $this->load($file);

        // If no key was specified after the dot, return the whole file array
        if ($setting === null) {
            return $this->cache[$file] ?? $default;
        }

        // Return the specific key value, or the default if it doesn't exist
        return $this->cache[$file][$setting] ?? $default;
    }

    /**
     * Load a config file into the cache.
     * Does nothing if the file is already cached.
     * Throws a RuntimeException if the file does not exist.
     */
    private function load(string $file): void
    {
        // Already loaded — skip
        if (isset($this->cache[$file])) {
            return;
        }

        $filePath = $this->configPath . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                "Config file not found: [{$filePath}]. " .
                "Make sure config/{$file}.php exists."
            );
        }

        // require the file — it must return an array
        $data = require $filePath;

        if (!is_array($data)) {
            throw new \RuntimeException(
                "Config file [{$filePath}] must return an array."
            );
        }

        $this->cache[$file] = $data;
    }
}
```

---

### 0.6.2 — EnvLoader Class

The `EnvLoader` reads your `.env` file and puts each key=value pair into PHP's `$_ENV` superglobal, making them available to the config files.

- [ ] **0.6.2** Create `app/Core/EnvLoader.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * EnvLoader
 *
 * Reads a .env file and populates $_ENV and putenv() with its values.
 * This is a simple, dependency-free alternative to packages like vlucas/phpdotenv.
 *
 * Usage:
 *   EnvLoader::load('/path/to/project/.env');
 */
class EnvLoader
{
    /**
     * Load the .env file at the given path.
     *
     * Rules:
     * - Lines starting with # are comments and are skipped.
     * - Blank lines are skipped.
     * - Values are split on the FIRST = sign only (so values can contain = signs).
     * - Surrounding quotes (" or ') are stripped from values.
     * - Existing $_ENV values are NOT overwritten (server environment wins).
     */
    public static function load(string $path): void
    {
        // If there's no .env file, silently do nothing.
        // The application will fall back to defaults defined in config files.
        if (!file_exists($path)) {
            return;
        }

        // Read the file into an array, one entry per line
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            // Skip comment lines
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Skip lines that don't contain an = sign
            if (!str_contains($line, '=')) {
                continue;
            }

            // Split on the FIRST = only
            // e.g. "DB_PASS=p@ss=word" becomes ['DB_PASS', 'p@ss=word']
            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value);

            // Strip surrounding quotes from the value
            // e.g. APP_NAME="Emirates" => Emirates
            //      APP_NAME='Emirates' => Emirates
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Only set the value if it isn't already defined in the server environment.
            // This allows real server environment variables to override .env values.
            if (!isset($_ENV[$name])) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}
```

---

### 0.6.3 — Logger Class

The `Logger` writes timestamped log entries to daily rotating files in `storage/logs/`. It must never crash the application even if disk is full or permissions fail.

- [ ] **0.6.3.1** Create `app/Interfaces/LoggerInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * LoggerInterface
 *
 * Defines the contract for all logger implementations.
 * Each method corresponds to a severity level.
 *
 * @param string $message  What happened
 * @param array  $context  Extra data to include (will be JSON-encoded)
 */
interface LoggerInterface
{
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
}
```

- [ ] **0.6.3.2** Create `app/Core/Logger.php`:

```php
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
```

---

### 0.6.4 — Session Class

The `Session` class wraps PHP's native `$_SESSION` superglobal with a clean API. It also provides flash messages (values that survive exactly one redirect) and CSRF token management.

- [ ] **0.6.4** Create `app/Core/Session.php`:

```php
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
```

---

### 0.6.5 — Request Class

The `Request` class gives us clean, safe access to incoming HTTP request data instead of reading `$_POST`, `$_GET`, `$_FILES`, and `$_SERVER` directly throughout the codebase.

- [ ] **0.6.5** Create `app/Core/Request.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Request
 *
 * Wraps the incoming HTTP request.
 * Created once via Request::capture() and passed through the application.
 *
 * Usage:
 *   $request = Request::capture();
 *   $request->method();            // 'GET', 'POST', 'PUT', 'DELETE'
 *   $request->uri();               // '/recipients/5'
 *   $request->post('email');       // $_POST['email'] or null
 *   $request->get('page');         // $_GET['page'] or null
 *   $request->input('name');       // checks POST then GET
 *   $request->file('avatar');      // $_FILES['avatar'] or null
 *   $request->isHtmx();            // true if sent by HTMX
 */
class Request
{
    private string $method;
    private string $uri;
    private array  $getParams;
    private array  $postParams;
    private array  $files;
    private array  $server;
    private array  $headers;

    private function __construct()
    {
        // Read the HTTP method. HTML forms can only send GET and POST,
        // so we support a hidden _method field for PUT/PATCH/DELETE.
        $this->method = strtoupper(
            $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'
        );

        // Strip the query string from the URI
        // '/recipients?page=2' becomes '/recipients'
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->uri = strtok($uri, '?') ?: '/';

        // Make sure the URI always starts with /
        if (!str_starts_with($this->uri, '/')) {
            $this->uri = '/' . $this->uri;
        }

        $this->getParams  = $_GET    ?? [];
        $this->postParams = $_POST   ?? [];
        $this->files      = $_FILES  ?? [];
        $this->server     = $_SERVER ?? [];

        // Parse headers from $_SERVER
        $this->headers = $this->parseHeaders();
    }

    /**
     * Create a Request from the current PHP superglobals.
     * This is the entry point — called once in App::run().
     */
    public static function capture(): static
    {
        return new static();
    }

    // ─── HTTP Method ──────────────────────────────────────────────────────

    public function method(): string
    {
        return $this->method;
    }

    public function isGet(): bool    { return $this->method === 'GET'; }
    public function isPost(): bool   { return $this->method === 'POST'; }
    public function isPut(): bool    { return $this->method === 'PUT'; }
    public function isPatch(): bool  { return $this->method === 'PATCH'; }
    public function isDelete(): bool { return $this->method === 'DELETE'; }

    // ─── URI ──────────────────────────────────────────────────────────────

    public function uri(): string
    {
        return $this->uri;
    }

    // ─── Input data ───────────────────────────────────────────────────────

    /**
     * Read a value from $_GET (query string).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    /**
     * Read a value from $_POST (form body).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Read a value from POST first, then GET.
     * Use this when you don't care where the value comes from.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $this->getParams[$key] ?? $default;
    }

    /**
     * Get all POST and GET values merged together.
     * POST values win if the same key exists in both.
     */
    public function all(): array
    {
        return array_merge($this->getParams, $this->postParams);
    }

    /**
     * Get only specific keys from the request input.
     * Useful for passing only safe fields to the database.
     */
    public function only(array $keys): array
    {
        $all    = $this->all();
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $all[$key] ?? null;
        }
        return $result;
    }

    /**
     * Get an uploaded file's data from $_FILES.
     * Returns null if no file was uploaded with that field name.
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        // A file with error code UPLOAD_ERR_NO_FILE means nothing was uploaded
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }

    // ─── Headers ──────────────────────────────────────────────────────────

    /**
     * Get an HTTP request header value.
     *
     * Pass the header name in normal format: 'Content-Type', 'X-CSRF-Token'
     * This method handles the $_SERVER naming convention internally.
     */
    public function header(string $key): ?string
    {
        // Convert 'X-CSRF-Token' to 'HTTP_X_CSRF_TOKEN' for $_SERVER lookup
        $normalised = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->headers[$normalised] ?? null;
    }

    /**
     * Check if this request was made by HTMX.
     * HTMX automatically sends 'HX-Request: true' with every request it makes.
     */
    public function isHtmx(): bool
    {
        return $this->header('HX-Request') === 'true';
    }

    /**
     * Check if the client wants a JSON response.
     * (Checks for 'Accept: application/json' header)
     */
    public function expectsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    /**
     * Get the client's IP address.
     */
    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Parse a Bearer token from the Authorization header.
     * Returns null if no Bearer token is present.
     */
    public function bearerToken(): ?string
    {
        $auth = $this->server['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Extract HTTP headers from $_SERVER.
     *
     * PHP stores headers in $_SERVER with an HTTP_ prefix and uppercase
     * names with underscores. e.g. 'Content-Type' => 'HTTP_CONTENT_TYPE'.
     * We store them in the same format for consistency.
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
```

---

### 0.6.6 — Response Class

The `Response` class builds an HTTP response (headers + body) and sends it. It supports regular HTML pages, JSON, redirects, and HTMX-specific response headers.

- [ ] **0.6.6** Create `app/Core/Response.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Response
 *
 * Builds and sends HTTP responses. Uses a fluent interface so you can chain calls:
 *
 *   return Response::html('<h1>Hello</h1>');
 *   return Response::redirect('/login');
 *   return Response::html($content)->withStatus(404);
 *   return Response::html($content)->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved!']);
 */
class Response
{
    private int    $statusCode = 200;
    private array  $headers    = [];
    private string $body       = '';

    // ─── Static factory methods ───────────────────────────────────────────

    /**
     * Create an HTML response.
     */
    public static function html(string $content, int $status = 200): static
    {
        $response = new static();
        $response->statusCode = $status;
        $response->body       = $content;
        $response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        return $response;
    }

    /**
     * Create a JSON response.
     * Automatically encodes the data and sets the Content-Type header.
     */
    public static function json(mixed $data, int $status = 200): static
    {
        $response = new static();
        $response->statusCode = $status;
        $response->body       = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->headers['Content-Type'] = 'application/json; charset=UTF-8';
        return $response;
    }

    /**
     * Create a redirect response.
     * Default status 302 = temporary redirect. Use 301 for permanent.
     */
    public static function redirect(string $url, int $status = 302): static
    {
        $response = new static();
        $response->statusCode      = $status;
        $response->headers['Location'] = $url;
        return $response;
    }

    /**
     * Redirect back to the previous page (using the Referer header).
     * Falls back to '/' if there is no Referer.
     */
    public static function back(): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return static::redirect($referer);
    }

    // ─── Fluent modifiers ─────────────────────────────────────────────────

    /**
     * Override or add an HTTP response header.
     * Returns $this so calls can be chained.
     */
    public function withHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Override the HTTP status code.
     */
    public function withStatus(int $status): static
    {
        $this->statusCode = $status;
        return $this;
    }

    /**
     * Add an HTMX HX-Trigger header.
     *
     * HTMX reads this header and fires a JavaScript event on the page.
     * We use it to trigger toast notifications from the server:
     *
     *   ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved!'])
     *
     * The JS in app.js listens for the 'showToast' event and renders the toast.
     */
    public function htmxTrigger(string $eventName, mixed $data = null): static
    {
        $trigger = $data !== null
            ? json_encode([$eventName => $data])
            : json_encode([$eventName => true]);

        $this->headers['HX-Trigger'] = $trigger;
        return $this;
    }

    /**
     * Tell HTMX to perform a client-side redirect.
     *
     * Different from a normal redirect: the browser doesn't reload the page,
     * instead HTMX navigates using its own history API.
     * Use this in HTMX responses when you want smooth navigation after a form submit.
     */
    public function htmxRedirect(string $url): static
    {
        $this->headers['HX-Redirect'] = $url;
        return $this;
    }

    // ─── Sending ─────────────────────────────────────────────────────────

    /**
     * Send the response to the browser and stop execution.
     *
     * This method never returns — it always calls exit after sending.
     * That's why the return type is 'never'.
     */
    public function send(): never
    {
        // Set the HTTP status code
        http_response_code($this->statusCode);

        // Send all headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Send the body
        echo $this->body;

        exit;
    }

    /**
     * Stream a file to the browser.
     *
     * Used by StorageController to serve uploaded logos and templates
     * that live outside the public web root.
     *
     * This method never returns.
     */
    public function stream(string $filepath, string $mimeType): never
    {
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }

        http_response_code(200);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: public, max-age=3600');

        // Read and output the file in chunks to handle large files
        // without loading the entire file into memory at once
        $handle = fopen($filepath, 'rb');
        if ($handle !== false) {
            while (!feof($handle)) {
                echo fread($handle, 8192); // 8KB chunks
            }
            fclose($handle);
        }

        exit;
    }
}
```

---

### 0.6.7 — Router Class

The `Router` matches incoming URLs to controller methods and runs any middleware assigned to the route group.

- [ ] **0.6.7** Create `app/Core/Router.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

/**
 * Router
 *
 * Matches incoming HTTP requests to registered routes and dispatches them
 * to the appropriate controller method, after running any assigned middleware.
 *
 * Route registration:
 *   $router->get('/recipients',         [RecipientController::class, 'index']);
 *   $router->post('/recipients',        [RecipientController::class, 'store']);
 *   $router->get('/recipients/{id}',    [RecipientController::class, 'show']);
 *   $router->delete('/recipients/{id}', [RecipientController::class, 'destroy']);
 *
 * Groups (apply middleware to multiple routes at once):
 *   $router->group(['middleware' => ['auth']], function($r) {
 *       $r->get('/compose', [ComposeController::class, 'index']);
 *   });
 */
class Router
{
    /**
     * All registered routes.
     *
     * Each entry looks like:
     * [
     *   'method'     => 'GET',
     *   'uri'        => '/recipients/{id}',
     *   'handler'    => [RecipientController::class, 'show'],
     *   'middleware' => ['auth'],
     * ]
     */
    private array $routes = [];

    /**
     * Middleware currently active for the open group (if any).
     */
    private array $currentGroupMiddleware = [];

    // ─── Route Registration ───────────────────────────────────────────────

    public function get(string $uri, array $handler): void
    {
        $this->addRoute('GET', $uri, $handler);
    }

    public function post(string $uri, array $handler): void
    {
        $this->addRoute('POST', $uri, $handler);
    }

    public function put(string $uri, array $handler): void
    {
        $this->addRoute('PUT', $uri, $handler);
    }

    public function patch(string $uri, array $handler): void
    {
        $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete(string $uri, array $handler): void
    {
        $this->addRoute('DELETE', $uri, $handler);
    }

    /**
     * Group routes together and apply shared options (like middleware) to all of them.
     *
     * Usage:
     *   $router->group(['middleware' => ['auth', 'csrf']], function($r) {
     *       $r->get('/dashboard', [DashboardController::class, 'index']);
     *   });
     */
    public function group(array $options, callable $callback): void
    {
        // Save the current middleware, then add the group's middleware
        $previousMiddleware = $this->currentGroupMiddleware;
        $this->currentGroupMiddleware = array_merge(
            $previousMiddleware,
            $options['middleware'] ?? []
        );

        // Run the callback — any routes registered inside will inherit the group middleware
        $callback($this);

        // Restore middleware to what it was before the group
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    /**
     * Register a route in the route table.
     */
    private function addRoute(string $method, string $uri, array $handler): void
    {
        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'handler'    => $handler,
            'middleware' => $this->currentGroupMiddleware,
        ];
    }

    // ─── Dispatching ──────────────────────────────────────────────────────

    /**
     * Find the matching route for the current request and execute it.
     *
     * Steps:
     * 1. Loop through registered routes looking for a method + URI match.
     * 2. Extract any {param} values from the URI.
     * 3. Run the middleware stack.
     * 4. Instantiate the controller and call the method.
     * 5. Return the Response.
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            // First check: does the HTTP method match?
            if ($route['method'] !== $request->method()) {
                continue;
            }

            // Second check: does the URI match? Extract any {param} values.
            $params = $this->matchUri($route['uri'], $request->uri());

            if ($params === null) {
                // This route doesn't match the current URI — try the next one
                continue;
            }

            // We found a matching route — run it
            return $this->runRoute($route, $request, $params);
        }

        // No route matched
        throw new NotFoundException("No route found for [{$request->method()}] {$request->uri()}");
    }

    /**
     * Attempt to match a route URI pattern against the actual request URI.
     *
     * Converts '{param}' placeholders into named regex capture groups.
     *
     * Examples:
     *   Pattern '/recipients/{id}' matches '/recipients/5' => ['id' => '5']
     *   Pattern '/compose'         matches '/compose'       => []
     *   Pattern '/compose'         does NOT match '/compose/new' => null
     *
     * Returns:
     *   array  - matched params (may be empty [])
     *   null   - no match
     */
    private function matchUri(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex:
        // '/recipients/{id}' becomes '/recipients/(?P<id>[^/]+)'
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);

        // Add anchors so the full URI must match (not just a prefix)
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null; // No match
        }

        // Filter out the numeric keys from preg_match, keep only named params
        $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

        return $params;
    }

    /**
     * Run a matched route: execute middleware, then call the controller method.
     */
    private function runRoute(array $route, Request $request, array $params): Response
    {
        [$controllerClass, $method] = $route['handler'];

        // Verify the controller class exists
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException(
                "Controller class [{$controllerClass}] does not exist."
            );
        }

        $controller = new $controllerClass();

        // Verify the method exists on the controller
        if (!method_exists($controller, $method)) {
            throw new \RuntimeException(
                "Method [{$method}] does not exist on [{$controllerClass}]."
            );
        }

        // Build the middleware pipeline.
        // The middleware list is ['auth', 'csrf'] etc.
        // We need to resolve those names to actual middleware class instances.
        $middlewareStack = $this->resolveMiddleware($route['middleware']);

        // The final "next" callable at the center of the pipeline
        // is the actual controller method call.
        $core = function (Request $request) use ($controller, $method, $params): Response {
            // Call the controller method.
            // We pass $request first, then any route params (like $id).
            // PHP named arguments allow the router params to match method param names.
            return $controller->$method($request, ...$params);
        };

        // Wrap the middleware around the core, from last to first,
        // so the first middleware in the list runs first.
        $pipeline = array_reduce(
            array_reverse($middlewareStack),
            fn($next, $middleware) => fn(Request $req) => $middleware->handle($req, $next),
            $core
        );

        return $pipeline($request);
    }

    /**
     * Convert middleware name strings to middleware class instances.
     *
     * Middleware name => class mapping:
     *   'auth'  => AuthMiddleware
     *   'guest' => GuestMiddleware
     *   'csrf'  => CsrfMiddleware
     */
    private function resolveMiddleware(array $names): array
    {
        $map = [
            'auth'  => \App\Middlewares\AuthMiddleware::class,
            'guest' => \App\Middlewares\GuestMiddleware::class,
            'csrf'  => \App\Middlewares\CsrfMiddleware::class,
        ];

        return array_map(function (string $name) use ($map) {
            if (!isset($map[$name])) {
                throw new \RuntimeException(
                    "Unknown middleware [{$name}]. Register it in Router::resolveMiddleware()."
                );
            }
            return new $map[$name]();
        }, $names);
    }
}
```

---

### 0.6.8 — Database & Model Classes

The `Database` class creates and holds the PDO connection. The `Model` class is the base class for all our data models — it gives every model a set of standard database operations.

- [ ] **0.6.8.1** Create `app/Core/Database.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database
 *
 * Manages a single PDO database connection for the entire request lifecycle.
 * Uses the Singleton pattern — only one connection is ever created.
 *
 * Usage:
 *   $pdo = Database::getInstance()->getConnection();
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;

    private function __construct()
    {
        $config = require(BASE_PATH . '/config/database.php');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            $this->connection = new \PDO($dsn, $config['user'], $config['pass'], [
                // Throw exceptions on errors (instead of returning false silently)
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                // Return rows as associative arrays by default
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // Disable emulated prepared statements for true security
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            // Wrap in a generic exception so the DB credentials
            // don't appear in error messages shown to users
            throw new \RuntimeException(
                'Database connection failed. Check your DB credentials in .env. ' .
                'Original error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the single Database instance (create it if it doesn't exist yet).
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get the underlying PDO connection object.
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
```

- [ ] **0.6.8.2** Create `app/Core/Model.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Model
 *
 * Base class for all database models. Provides common CRUD operations
 * using PDO prepared statements.
 *
 * Each model that extends this class must define:
 *   protected static string $table = 'table_name';
 *   protected array $fillable = ['column1', 'column2', ...];
 *
 * Usage:
 *   // Find by primary key
 *   $user = User::find(1);
 *
 *   // Find by any column
 *   $user = User::findBy('email', 'test@example.com');
 *
 *   // Get all records
 *   $users = User::all();
 *
 *   // Query with conditions
 *   $active = User::where(['is_active' => 1]);
 *
 *   // Create a new record
 *   $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
 *
 *   // Update a record
 *   $user->update(['name' => 'Alice Smith']);
 *
 *   // Delete a record
 *   $user->delete();
 *
 *   // Access properties
 *   echo $user->name;
 *   echo $user->email;
 */
abstract class Model
{
    /**
     * The database table this model reads from and writes to.
     * Each child class MUST define this.
     */
    protected static string $table = '';

    /**
     * The columns that are allowed to be mass-assigned via create() and save().
     * Any column not in this list is ignored (protects against mass assignment attacks).
     */
    protected array $fillable = [];

    /**
     * The raw data from the database row.
     * We use this internally to track the model's state.
     */
    protected array $attributes = [];

    // ─── Magic property access ────────────────────────────────────────────

    /**
     * Allow reading model attributes like object properties:
     *   $user->name    instead of    $user->attributes['name']
     */
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Allow setting model attributes like object properties:
     *   $user->name = 'Alice'    instead of    $user->attributes['name'] = 'Alice'
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ─── Database connection ──────────────────────────────────────────────

    /**
     * Get the PDO connection.
     */
    protected static function db(): \PDO
    {
        return Database::getInstance()->getConnection();
    }

    // ─── Query methods ────────────────────────────────────────────────────

    /**
     * Find a record by its primary key (id).
     * Returns null if not found.
     */
    public static function find(int $id): ?static
    {
        $table = static::$table;
        $stmt  = static::db()->prepare("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    /**
     * Find a single record where a specific column equals a value.
     * Returns null if not found.
     *
     * Example: User::findBy('email', 'alice@example.com')
     */
    public static function findBy(string $column, mixed $value): ?static
    {
        $table = static::$table;
        // Note: column name is NOT user input so we can safely embed it.
        // Values are always bound as parameters.
        $stmt  = static::db()->prepare("SELECT * FROM `{$table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    /**
     * Get all records from the table, ordered by a column.
     *
     * Example: User::all('created_at', 'DESC')
     */
    public static function all(string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $table = static::$table;
        $dir   = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC'; // whitelist direction
        $stmt  = static::db()->query("SELECT * FROM `{$table}` ORDER BY `{$orderBy}` {$dir}");

        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Get records matching a set of conditions (AND logic).
     *
     * Example:
     *   Recipient::where(['is_suppressed' => 0], 'created_at', 'DESC', 20)
     *
     * This generates: WHERE is_suppressed = ? ORDER BY created_at DESC LIMIT 20
     */
    public static function where(
        array   $conditions,
        string  $orderBy = 'id',
        string  $dir     = 'ASC',
        ?int    $limit   = null,
        int     $offset  = 0
    ): array {
        $table  = static::$table;
        $dir    = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $wheres = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $wheres[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $whereClause = !empty($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';
        $limitClause = $limit !== null ? "LIMIT {$limit} OFFSET {$offset}" : '';

        $sql  = "SELECT * FROM `{$table}` {$whereClause} ORDER BY `{$orderBy}` {$dir} {$limitClause}";
        $stmt = static::db()->prepare($sql);
        $stmt->execute($values);

        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Count records in the table, optionally filtered by conditions.
     *
     * Example: User::count()  or  Recipient::count(['is_suppressed' => 1])
     */
    public static function count(array $conditions = []): int
    {
        $table  = static::$table;
        $wheres = [];
        $values = [];

        foreach ($conditions as $column => $value) {
            $wheres[] = "`{$column}` = ?";
            $values[] = $value;
        }

        $whereClause = !empty($wheres) ? 'WHERE ' . implode(' AND ', $wheres) : '';
        $stmt = static::db()->prepare("SELECT COUNT(*) FROM `{$table}` {$whereClause}");
        $stmt->execute($values);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Get paginated records.
     *
     * Returns an array with:
     *   'data'      => array of model instances for the current page
     *   'total'     => total number of records across all pages
     *   'page'      => current page number
     *   'per_page'  => records per page
     *   'last_page' => total number of pages
     *
     * Example: Recipient::paginate(20, 1)
     */
    public static function paginate(int $perPage, int $page, array $conditions = []): array
    {
        $page   = max(1, $page); // Page must be at least 1
        $offset = ($page - 1) * $perPage;
        $total  = static::count($conditions);

        $data = static::where($conditions, 'id', 'DESC', $perPage, $offset);

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    // ─── Write methods ────────────────────────────────────────────────────

    /**
     * Create a new record and return the populated model instance.
     *
     * Only keys listed in $fillable are inserted.
     *
     * Example: User::create(['name' => 'Alice', 'email' => 'alice@test.com'])
     */
    public static function create(array $data): static
    {
        $instance = new static();
        $instance->fill($data);
        $instance->save();
        return $instance;
    }

    /**
     * Save the current model to the database.
     * If the model has an 'id', it runs UPDATE. Otherwise it runs INSERT.
     */
    public function save(): bool
    {
        $table = static::$table;
        $data  = $this->getFillableData();

        if (empty($data)) {
            return false;
        }

        if (isset($this->attributes['id'])) {
            // UPDATE existing record
            $sets   = array_map(fn($col) => "`{$col}` = ?", array_keys($data));
            $values = array_values($data);
            $values[] = $this->attributes['id']; // for the WHERE clause

            $sql  = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = static::db()->prepare($sql);
            return $stmt->execute($values);

        } else {
            // INSERT new record
            $columns      = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = static::db()->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if ($result) {
                // Store the new auto-increment ID back on the model
                $this->attributes['id'] = (int)static::db()->lastInsertId();
            }

            return $result;
        }
    }

    /**
     * Update specific columns on the current model and save.
     *
     * Example: $user->update(['name' => 'Bob'])
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    /**
     * Delete this record from the database.
     */
    public function delete(): bool
    {
        $table = static::$table;

        if (!isset($this->attributes['id'])) {
            return false;
        }

        $stmt = static::db()->prepare("DELETE FROM `{$table}` WHERE id = ?");
        return $stmt->execute([$this->attributes['id']]);
    }

    // ─── Raw SQL ──────────────────────────────────────────────────────────

    /**
     * Run a custom SQL query and return all results as model instances.
     * Use this for complex queries that don't fit the standard methods.
     *
     * Example:
     *   Recipient::raw(
     *       'SELECT * FROM recipients WHERE email LIKE ? AND is_suppressed = 0',
     *       ['%alice%']
     *   )
     */
    public static function raw(string $sql, array $bindings = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($bindings);
        return array_map(fn($row) => static::hydrate($row), $stmt->fetchAll());
    }

    /**
     * Run a custom SQL query and return a single result as a model instance.
     * Returns null if no row matches.
     */
    public static function rawOne(string $sql, array $bindings = []): ?static
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    // ─── Utilities ────────────────────────────────────────────────────────

    /**
     * Convert this model's attributes to a plain PHP array.
     * Useful for passing data to views or API responses.
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Create a model instance from a database row (associative array).
     */
    protected static function hydrate(array $row): static
    {
        $instance = new static();
        $instance->attributes = $row;
        return $instance;
    }

    /**
     * Set multiple attributes at once, filtering to only $fillable columns.
     */
    protected function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            // Only allow columns that are listed in $fillable
            if (in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Return only the attributes that are in the $fillable list.
     * Used internally before INSERT/UPDATE.
     */
    private function getFillableData(): array
    {
        $result = [];
        foreach ($this->fillable as $column) {
            if (array_key_exists($column, $this->attributes)) {
                $result[$column] = $this->attributes[$column];
            }
        }
        return $result;
    }
}
```

---

### 0.6.9 — ErrorHandler Class

The `ErrorHandler` catches all PHP errors and uncaught exceptions. In development (`APP_DEBUG=true`) it shows a detailed debug page. In production it logs the error and shows a friendly error page.

- [ ] **0.6.9** Create `app/Core/ErrorHandler.php`:

```php
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
```

---

### 0.6.10 — App Class (Service Container)

The `App` class is the heart of the application — a simple dependency injection container that wires all our classes together and runs the request lifecycle.

- [ ] **0.6.10** Create `app/Core/App.php`:

```php
<?php

declare(strict_types=1);

namespace App\Core;

/**
 * App (Service Container)
 *
 * Manages class instances and how they're created.
 *
 * Two binding types:
 *
 *   Singleton: Created once, then the same instance is returned every time.
 *     $app->singleton(Logger::class, fn() => new Logger('/path/to/logs'));
 *     $app->make(Logger::class); // same Logger instance every call
 *
 *   Bind: A fresh instance is created every time make() is called.
 *     $app->bind(SomeService::class, fn() => new SomeService());
 *     $app->make(SomeService::class); // new SomeService every call
 *
 * Global access:
 *   App::getInstance() // from anywhere in the codebase
 */
class App
{
    /**
     * The single global instance of App.
     * Set via setInstance() and retrieved via getInstance().
     */
    private static ?App $instance = null;

    /**
     * Singleton factories — lazy, called only once per abstract.
     * Format: ['ClassName' => callable]
     */
    private array $singletons = [];

    /**
     * Resolved singleton instances (cached after first make() call).
     * Format: ['ClassName' => object|value]
     */
    private array $instances = [];

    /**
     * Bind factories — called fresh every make().
     * Format: ['ClassName' => callable]
     */
    private array $bindings = [];

    // ─── Registration ─────────────────────────────────────────────────────

    /**
     * Register a singleton binding.
     * The factory callable is only called once; the result is cached.
     *
     * Usage:
     *   $app->singleton(Logger::class, fn() => new Logger(storage_path('logs')));
     */
    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    /**
     * Register a regular binding (new instance every time).
     *
     * Usage:
     *   $app->bind(SomeService::class, fn() => new SomeService());
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a pre-made instance directly (no factory needed).
     * The same instance is returned every time.
     *
     * Usage:
     *   $app->instance(Request::class, $request);
     */
    public function instance(string $abstract, mixed $value): void
    {
        $this->instances[$abstract] = $value;
    }

    // ─── Resolution ───────────────────────────────────────────────────────

    /**
     * Resolve a binding and return the instance.
     *
     * Resolution order:
     *   1. Pre-made instances (registered with instance())
     *   2. Singleton bindings (cached after first call)
     *   3. Regular bindings (fresh call each time)
     *   4. RuntimeException if nothing is registered
     */
    public function make(string $abstract): mixed
    {
        // 1. Check pre-made instances first
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // 2. Check singleton bindings
        if (isset($this->singletons[$abstract])) {
            // Create the instance and cache it
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = ($this->singletons[$abstract])($this);
            }
            return $this->instances[$abstract];
        }

        // 3. Check regular bindings (creates a fresh instance each time)
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // 4. Nothing registered
        throw new \RuntimeException(
            "No binding found for [{$abstract}]. " .
            "Register it in bootstrap/app.php using singleton(), bind(), or instance()."
        );
    }

    // ─── Global access ────────────────────────────────────────────────────

    /**
     * Store the global App instance.
     * Called once in bootstrap/app.php.
     */
    public static function setInstance(self $app): void
    {
        static::$instance = $app;
    }

    /**
     * Retrieve the global App instance from anywhere in the codebase.
     * Used by helper functions like config(), session(), logger().
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            throw new \RuntimeException(
                'App instance not set. Make sure bootstrap/app.php has been loaded.'
            );
        }
        return static::$instance;
    }

    // ─── Request Lifecycle ────────────────────────────────────────────────

    /**
     * Run the application.
     *
     * This is the main entry point called from public/index.php.
     * It handles the full request lifecycle:
     *   1. Load .env
     *   2. Start session
     *   3. Capture the HTTP request
     *   4. Dispatch to the correct controller
     *   5. Send the response
     *
     * This method never returns (it calls exit via Response::send()).
     */
    public function run(): never
    {
        // 1. Load environment variables from .env
        EnvLoader::load(BASE_PATH . '/.env');

        // 2. Set the application timezone
        $timezone = $_ENV['TIMEZONE'] ?? 'UTC';
        date_default_timezone_set($timezone);

        // 3. Start the session
        $this->make(Session::class)->start();

        // 4. Capture the current HTTP request
        $request = Request::capture();

        // 5. Dispatch the request through the router
        $router   = $this->make(Router::class);
        $response = $router->dispatch($request);

        // 6. Send the response to the browser
        $response->send();
    }
}
```

---

## 0.7 — Bootstrap Files

These two files wire everything together: `app.php` sets up the container, and `helpers.php` provides the global shortcut functions used throughout the codebase and in views.

- [ ] **0.7.1** Create `bootstrap/app.php`:

```php
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
require BASE_PATH . '/routes/api.php';

// ── 7. Return the App instance to public/index.php ─────────────────────────
return $app;
```

- [ ] **0.7.2** Create `bootstrap/helpers.php`:

```php
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
 * Render a view template and return the HTML as a string.
 *
 * The view file lives in resources/{path}.php
 * All $data keys are extracted as local variables inside the view.
 *
 * Usage:
 *   view('auth/login', ['errors' => ['Email is required']])
 *   view('compose/index', ['templates' => $templates])
 */
function view(string $path, array $data = []): string
{
    $file = BASE_PATH . '/resources/' . ltrim($path, '/') . '.php';

    if (!file_exists($file)) {
        throw new \RuntimeException("View file not found: [{$file}]");
    }

    // extract() converts array keys to local variables
    // e.g. ['name' => 'Alice'] becomes $name = 'Alice' inside the view
    extract($data, EXTR_SKIP);

    // Start output buffering — capture everything the view echoes
    ob_start();
    include $file;
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
 *
 * Usage in views:
 *   <input value="<?= e(old('email')) ?>">
 */
function old(string $key, mixed $default = ''): mixed
{
    $oldInput = session()->getFlash('_old_input') ?? [];

    // getFlash deletes the value after reading, but we might call old() multiple times.
    // Re-flash if there's still data to preserve for the current render.
    if (!empty($oldInput)) {
        session()->flash('_old_input', $oldInput);
    }

    return $oldInput[$key] ?? $default;
}

/**
 * Get validation errors flashed to the session.
 *
 * Usage in views:
 *   errors()           => ['email' => 'Email is required', ...]  (all errors)
 *   errors('email')    => 'Email is required'                    (single field)
 */
function errors(?string $key = null): array|string
{
    $allErrors = session()->getFlash('_errors') ?? [];

    // Re-flash so multiple calls to errors() within one render all work
    if (!empty($allErrors)) {
        session()->flash('_errors', $allErrors);
    }

    if ($key !== null) {
        return $allErrors[$key] ?? '';
    }

    return $allErrors;
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
```

---

## 0.8 — Public Entry Point

- [ ] **0.8.1** Create `public/index.php`:

```php
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
```

- [ ] **0.8.2** Create `public/.htaccess`:

```apache
# public/.htaccess
# Rewrites all requests to public/index.php so PHP handles routing.

# Require mod_rewrite to be enabled
Options -MultiViews
Options -Indexes

# Turn on the rewrite engine
RewriteEngine On

# If the request is for a real file (CSS, JS, images), serve it directly
RewriteCond %{REQUEST_FILENAME} !-f
# If the request is for a real directory, serve it directly
RewriteCond %{REQUEST_FILENAME} !-d

# Otherwise, route everything through index.php
# QSA = append query string, L = last rule
RewriteRule ^(.*)$ index.php [QSA,L]

# Deny direct access to .env files
<Files ".env">
    Order deny,allow
    Deny from all
</Files>

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

---

## 0.9 — Route Files

- [ ] **0.9.1** Create `routes/web.php`:

```php
<?php

declare(strict_types=1);

/**
 * routes/web.php
 *
 * All application HTTP routes.
 *
 * $router is injected by bootstrap/app.php before this file is loaded.
 * Controllers don't exist yet in Phase 0 — they'll be built in Phase 2 onwards.
 * Registering routes here just means they're in the table; they won't throw
 * until someone actually requests them.
 *
 * Middleware:
 *   'auth'  => User must be logged in
 *   'guest' => User must NOT be logged in (for login page)
 *   'csrf'  => Validate CSRF token on state-changing requests
 */

use App\Controllers\AuthController;
use App\Controllers\ComposeController;
use App\Controllers\DraftController;
use App\Controllers\RecipientController;
use App\Controllers\LogController;
use App\Controllers\TemplateController;
use App\Controllers\CredentialController;
use App\Controllers\SettingsController;
use App\Controllers\TranslationController;
use App\Controllers\StorageController;

// ── Guest-only routes (redirect to /compose if already logged in) ──────────
$router->group(['middleware' => ['guest']], function ($router) use (
    &$router, AuthController) {
    $router->get('/login',  [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Logout (any authenticated user can log out) ────────────────────────────
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated routes ───────────────────────────────────────────────────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) use (
    &$router,
    ComposeController,
    DraftController,
    RecipientController,
    LogController,
    TemplateController,
    CredentialController,
    SettingsController,
    TranslationController,
    StorageController
) {
    // Root redirect to compose
    $router->get('/', [ComposeController::class, 'index']);

    // ── Compose ──────────────────────────────────────────────────────────
    $router->get('/compose',                  [ComposeController::class, 'index']);
    $router->post('/compose/send',            [ComposeController::class, 'send']);
    $router->post('/compose/preview',         [ComposeController::class, 'preview']);
    $router->post('/compose/load-template',   [ComposeController::class, 'loadTemplate']);
    $router->post('/compose/translate',       [TranslationController::class, 'translate']);
    $router->post('/compose/translate/revert',[TranslationController::class, 'revert']);

    // ── Drafts ────────────────────────────────────────────────────────────
    $router->get('/drafts',              [DraftController::class, 'index']);
    $router->post('/drafts',             [DraftController::class, 'store']);
    $router->post('/drafts/autosave',    [DraftController::class, 'autosave']);
    $router->get('/drafts/{id}/load',    [DraftController::class, 'load']);
    $router->put('/drafts/{id}',         [DraftController::class, 'update']);
    $router->delete('/drafts/{id}',      [DraftController::class, 'destroy']);

    // ── Recipients ────────────────────────────────────────────────────────
    $router->get('/recipients',              [RecipientController::class, 'index']);
    $router->get('/recipients/create',       [RecipientController::class, 'create']);
    $router->post('/recipients',             [RecipientController::class, 'store']);
    $router->get('/recipients/import',       [RecipientController::class, 'importPage']);
    $router->post('/recipients/import',      [RecipientController::class, 'import']);
    $router->get('/recipients/{id}/edit',    [RecipientController::class, 'edit']);
    $router->put('/recipients/{id}',         [RecipientController::class, 'update']);
    $router->delete('/recipients/{id}',      [RecipientController::class, 'destroy']);
    $router->post('/recipients/{id}/suppress',[RecipientController::class, 'suppress']);

    // ── Email Logs ────────────────────────────────────────────────────────
    $router->get('/logs',              [LogController::class, 'index']);
    $router->get('/logs/{id}',         [LogController::class, 'show']);
    $router->delete('/logs/clear',     [LogController::class, 'clear']);

    // ── Settings: Email Templates ─────────────────────────────────────────
    $router->get('/settings/templates',                   [TemplateController::class, 'index']);
    $router->get('/settings/templates/create',            [TemplateController::class, 'create']);
    $router->post('/settings/templates',                  [TemplateController::class, 'store']);
    $router->post('/settings/templates/preview-draft',    [TemplateController::class, 'previewDraft']);
    $router->get('/settings/templates/{id}/edit',         [TemplateController::class, 'edit']);
    $router->put('/settings/templates/{id}',              [TemplateController::class, 'update']);
    $router->delete('/settings/templates/{id}',           [TemplateController::class, 'destroy']);
    $router->post('/settings/templates/{id}/duplicate',   [TemplateController::class, 'duplicate']);
    $router->get('/settings/templates/{id}/preview',      [TemplateController::class, 'preview']);

    // ── Settings: Email Credentials ───────────────────────────────────────
    $router->get('/settings/credentials',          [CredentialController::class, 'index']);
    $router->post('/settings/credentials',         [CredentialController::class, 'store']);
    $router->post('/settings/credentials/test',    [CredentialController::class, 'test']);

    // ── Settings: General ─────────────────────────────────────────────────
    $router->get('/settings/general',  [SettingsController::class, 'index']);
    $router->post('/settings/general', [SettingsController::class, 'update']);

    // ── Storage: Serve uploaded files ─────────────────────────────────────
    // Files in storage/ are outside the web root, so we serve them through PHP
    $router->get('/storage/{type}/{filename}', [StorageController::class, 'serve']);
});
```

- [ ] **0.9.2** Create `routes/api.php`:

```php
<?php

declare(strict_types=1);

/**
 * routes/api.php
 *
 * Webhook and API routes.
 * These routes do NOT use the auth or csrf middleware —
 * they are protected by their own signature validation instead.
 */

use App\Controllers\WebhookController;

// Resend webhook — receives delivery status updates and inbound emails
// Protected by Svix signature validation inside WebhookController::resend()
$router->post('/webhooks/resend', [WebhookController::class, 'resend']);
```

---

## 0.10 — Helper Classes

---

### 0.10.1 — Str Helper

- [ ] **0.10.1** Create `app/Helpers/Str.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Str — String utility methods
 *
 * Usage:
 *   Str::slug('Hello World!')  => 'hello-world'
 *   Str::truncate('Long text', 10) => 'Long text...'
 *   Str::mask('sk_live_abc123xyz', 4, 4) => 'sk_l••••••••xyz'
 *   Str::uuid() => 'a1b2c3d4-...'
 */
class Str
{
    /**
     * Convert a string to a URL-safe lowercase slug.
     *
     * Examples:
     *   'Hello World!'     => 'hello-world'
     *   'Newsletter (v2)' => 'newsletter-v2'
     */
    public static function slug(string $text): string
    {
        // Lowercase everything
        $text = mb_strtolower($text, 'UTF-8');

        // Replace anything that's not a letter, number, space, or hyphen with a space
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);

        // Replace multiple spaces or hyphens with a single hyphen
        $text = preg_replace('/[\s-]+/', '-', trim($text));

        return $text;
    }

    /**
     * Truncate a string to a maximum length and add a suffix.
     *
     * Examples:
     *   truncate('Hello World', 7)        => 'Hell...'
     *   truncate('Short', 10)             => 'Short'
     *   truncate('Hello World', 7, ' →') => 'Hell →'
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        // Cut to length minus the suffix length, so the total doesn't exceed $length
        $cutAt = $length - mb_strlen($suffix, 'UTF-8');
        return mb_substr($text, 0, max(0, $cutAt), 'UTF-8') . $suffix;
    }

    /**
     * Mask the middle of a string, keeping $visibleStart chars at the start
     * and $visibleEnd chars at the end. Used to safely display API keys.
     *
     * Examples:
     *   mask('sk_live_abc123xyz', 4, 4) => 'sk_l••••••••xyz'
     *   mask('hello@example.com', 2, 4) => 'he••••••••.com'
     */
    public static function mask(string $text, int $visibleStart = 4, int $visibleEnd = 4): string
    {
        $length = mb_strlen($text, 'UTF-8');

        // If the string is too short to mask, just return asterisks
        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat('•', $length);
        }

        $start  = mb_substr($text, 0, $visibleStart, 'UTF-8');
        $end    = mb_substr($text, -$visibleEnd, null, 'UTF-8');
        $middle = str_repeat('•', $length - $visibleStart - $visibleEnd);

        return $start . $middle . $end;
    }

    /**
     * Generate a random UUID v4.
     * UUIDs are used to name uploaded files to prevent collisions.
     *
     * Example output: '550e8400-e29b-41d4-a716-446655440000'
     */
    public static function uuid(): string
    {
        // Generate 16 random bytes
        $bytes = random_bytes(16);

        // Set version (4) and variant bits as per RFC 4122
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // version 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Check if a string contains a substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Check if a string starts with a prefix.
     */
    public static function startsWith(string $haystack, string $prefix): bool
    {
        return str_starts_with($haystack, $prefix);
    }
}
```

---

### 0.10.2 — Url Helper

- [ ] **0.10.2** Create `app/Helpers/Url.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Url — URL and path generation helpers
 */
class Url
{
    /**
     * Get the full URL to a public asset (CSS, JS, images).
     *
     * Usage:
     *   Url::asset('css/app.css') => 'http://localhost:8000/assets/css/app.css'
     */
    public static function asset(string $path): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }

    /**
     * Get the full URL for an application route.
     *
     * Usage:
     *   Url::url('/login')   => 'http://localhost:8000/login'
     *   Url::url('/')        => 'http://localhost:8000'
     */
    public static function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $path && $path !== '/' ? $base . '/' . ltrim($path, '/') : $base;
    }

    /**
     * Get the absolute filesystem path to a storage file.
     *
     * Usage:
     *   Url::storagePath('uploads/logos/logo.png')
     *   => '/var/www/emirates/storage/uploads/logos/logo.png'
     */
    public static function storagePath(string $relative): string
    {
        return BASE_PATH . '/storage/' . ltrim($relative, '/');
    }

    /**
     * Get the public URL for a stored file (served via StorageController).
     *
     * Uploaded files live outside the web root in storage/, so they're
     * served through a PHP controller at /storage/{type}/{filename}.
     *
     * Usage:
     *   Url::storageUrl('logos/global/logo-abc123.png')
     *   => 'http://localhost:8000/storage/logos/global/logo-abc123.png'
     */
    public static function storageUrl(string $relative): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $base . '/storage/' . ltrim($relative, '/');
    }
}
```

---

### 0.10.3 — Date Helper

- [ ] **0.10.3** Create `app/Helpers/Date.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Date — Date and time formatting helpers
 *
 * All methods use the application timezone set in config/app.php.
 */
class Date
{
    /**
     * Format a datetime string for display.
     *
     * Usage:
     *   Date::format('2025-06-15 14:30:00')       => '15 Jun 2025, 2:30pm'
     *   Date::format('2025-06-15 14:30:00', 'd/m/Y') => '15/06/2025'
     */
    public static function format(string $datetime, string $format = 'd M Y, g:ia'): string
    {
        try {
            $timezone = new \DateTimeZone(config('app.timezone', 'UTC'));
            $dt       = new \DateTime($datetime, new \DateTimeZone('UTC'));
            $dt->setTimezone($timezone);
            return $dt->format($format);
        } catch (\Throwable) {
            return $datetime; // Return the raw value if parsing fails
        }
    }

    /**
     * Return a human-readable time difference like "2 hours ago" or "just now".
     *
     * Usage:
     *   Date::diffForHumans('2025-06-15 12:00:00')  => '2 hours ago'
     *   Date::diffForHumans('2025-06-15 13:59:30')  => 'just now'
     */
    public static function diffForHumans(string $datetime): string
    {
        try {
            $past    = new \DateTime($datetime, new \DateTimeZone('UTC'));
            $now     = new \DateTime('now', new \DateTimeZone('UTC'));
            $seconds = $now->getTimestamp() - $past->getTimestamp();

            if ($seconds < 60)  return 'just now';
            if ($seconds < 3600) {
                $m = (int)($seconds / 60);
                return $m . ' minute' . ($m !== 1 ? 's' : '') . ' ago';
            }
            if ($seconds < 86400) {
                $h = (int)($seconds / 3600);
                return $h . ' hour' . ($h !== 1 ? 's' : '') . ' ago';
            }
            if ($seconds < 2592000) {
                $d = (int)($seconds / 86400);
                return $d . ' day' . ($d !== 1 ? 's' : '') . ' ago';
            }

            // Older than 30 days — show the actual date
            return static::format($datetime, 'd M Y');

        } catch (\Throwable) {
            return $datetime;
        }
    }

    /**
     * Get the current datetime in MySQL format (Y-m-d H:i:s).
     *
     * Usage:
     *   Date::now() => '2025-06-15 14:32:01'
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
```

---

### 0.10.4 — Crypto Helper

- [ ] **0.10.4** Create `app/Helpers/Crypto.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Crypto — AES-256-CBC encryption and decryption
 *
 * Used to encrypt sensitive data (API keys, SMTP passwords) before storing
 * them in the database. The encryption key comes from APP_KEY in .env.
 *
 * How it works:
 *   - We use AES-256-CBC, a symmetric cipher. Same key encrypts and decrypts.
 *   - Each encryption generates a random IV (Initialisation Vector).
 *     This means encrypting the same value twice gives DIFFERENT ciphertexts.
 *   - The IV is prepended to the ciphertext and stored together, so we can
 *     extract it during decryption.
 *   - The final stored value is: base64( iv_bytes + encrypted_bytes )
 */
class Crypto
{
    private const CIPHER = 'AES-256-CBC';
    private const IV_LENGTH = 16; // AES-256-CBC always uses a 16-byte IV

    /**
     * Encrypt a plaintext string.
     *
     * Returns a base64-encoded string containing the IV + ciphertext.
     * Safe to store in the database.
     *
     * Usage:
     *   $encrypted = Crypto::encrypt('sk_live_abc123');
     */
    public static function encrypt(string $plaintext): string
    {
        $key = static::getKey();

        // Generate a fresh random IV for every encryption
        // This is critical — reusing IVs with the same key is a security vulnerability
        $iv = random_bytes(static::IV_LENGTH);

        $ciphertext = openssl_encrypt($plaintext, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed. Check that OpenSSL is available.');
        }

        // Combine: IV bytes + ciphertext bytes, then base64-encode the whole thing
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a previously encrypted value.
     *
     * Accepts the base64 string produced by encrypt().
     * Returns the original plaintext.
     *
     * Usage:
     *   $apiKey = Crypto::decrypt($credential->config);
     */
    public static function decrypt(string $encrypted): string
    {
        $key = static::getKey();

        // Decode from base64 back to raw bytes
        $decoded = base64_decode($encrypted, strict: true);

        if ($decoded === false) {
            throw new \RuntimeException('Decryption failed: invalid base64 data.');
        }

        if (strlen($decoded) <= static::IV_LENGTH) {
            throw new \RuntimeException('Decryption failed: data too short to contain an IV.');
        }

        // Split: first 16 bytes = IV, rest = ciphertext
        $iv         = substr($decoded, 0, static::IV_LENGTH);
        $ciphertext = substr($decoded, static::IV_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Decryption failed. The data may be corrupted or the APP_KEY may have changed.'
            );
        }

        return $plaintext;
    }

    /**
     * Derive the 32-byte encryption key from APP_KEY in .env.
     *
     * APP_KEY is stored as "base64:..." in .env.
     * We decode it to get the raw 32 bytes needed by AES-256.
     */
    private static function getKey(): string
    {
        $appKey = config('app.key', '');

        if (empty($appKey)) {
            throw new \RuntimeException(
                'APP_KEY is not set in .env. ' .
                'Generate one with: php -r "echo \'base64:\' . base64_encode(random_bytes(32));"'
            );
        }

        // Strip the "base64:" prefix and decode
        if (str_starts_with($appKey, 'base64:')) {
            $key = base64_decode(substr($appKey, 7), strict: true);
        } else {
            // If no prefix, assume it's already raw (not recommended)
            $key = $appKey;
        }

        if ($key === false || strlen($key) < 32) {
            throw new \RuntimeException(
                'APP_KEY must be a base64-encoded 32-byte key. ' .
                'Regenerate it with: php -r "echo \'base64:\' . base64_encode(random_bytes(32));"'
            );
        }

        return $key;
    }
}
```

---

### 0.10.5 — Validator Helper

- [ ] **0.10.5** Create `app/Helpers/Validator.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Validator — Input validation
 *
 * Usage:
 *   $validator = Validator::make($request->all(), [
 *       'email'    => 'required|email',
 *       'password' => 'required|min:8',
 *       'role'     => 'required|in:admin,editor,viewer',
 *   ]);
 *
 *   if ($validator->fails()) {
 *       // Handle errors
 *       $errors = $validator->errors(); // ['email' => 'Email is required', ...]
 *   }
 *
 *   $cleanData = $validator->validated(); // Only fields that passed
 *
 * Supported rules (pipe-separated):
 *   required          - Field must be present and not empty
 *   email             - Must be a valid email address
 *   min:n             - String must be at least n characters
 *   max:n             - String must not exceed n characters
 *   url               - Must be a valid URL
 *   in:a,b,c          - Value must be one of the listed options
 *   numeric           - Must be a number
 *   integer           - Must be an integer
 *   confirmed         - Field must match 'field_confirmation' value
 *   file              - Must be a successfully uploaded file
 *   mimes:jpg,png     - Uploaded file must have one of these extensions
 *   max_size:n        - Uploaded file must not exceed n bytes
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errorMessages = [];

    private function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    /**
     * Create a new Validator instance and run validation immediately.
     */
    public static function make(array $data, array $rules): static
    {
        $validator = new static($data, $rules);
        $validator->validate();
        return $validator;
    }

    public function passes(): bool
    {
        return empty($this->errorMessages);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get all validation errors, keyed by field name.
     * Returns the FIRST error per field.
     */
    public function errors(): array
    {
        return $this->errorMessages;
    }

    /**
     * Get only the input fields that were listed in the rules and passed validation.
     * Useful for safe mass assignment — never pass $request->all() directly to create().
     */
    public function validated(): array
    {
        $result = [];
        foreach (array_keys($this->rules) as $field) {
            if (!isset($this->errorMessages[$field]) && array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }
        return $result;
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    /**
     * Run all rules for all fields.
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            // Rules are pipe-separated: 'required|email|max:150'
            $rules = explode('|', $ruleString);

            foreach ($rules as $rule) {
                // Split rule name from its argument: 'min:8' => ['min', '8']
                $parts     = explode(':', $rule, 2);
                $ruleName  = trim($parts[0]);
                $ruleParam = isset($parts[1]) ? trim($parts[1]) : null;

                $error = $this->applyRule($field, $ruleName, $ruleParam);

                if ($error !== null) {
                    // Store only the first error per field, then stop checking that field
                    $this->errorMessages[$field] = $error;
                    break;
                }
            }
        }
    }

    /**
     * Apply a single rule to a field and return an error message (or null if it passes).
     */
    private function applyRule(string $field, string $rule, ?string $param): ?string
    {
        $value     = $this->data[$field] ?? null;
        $label     = ucfirst(str_replace('_', ' ', $field)); // 'first_name' => 'First name'

        return match ($rule) {

            'required' => (
                $value === null || $value === '' || $value === []
                    ? "{$label} is required."
                    : null
            ),

            'email' => (
                $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? "{$label} must be a valid email address."
                    : null
            ),

            'url' => (
                $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)
                    ? "{$label} must be a valid URL."
                    : null
            ),

            'numeric' => (
                $value !== null && $value !== '' && !is_numeric($value)
                    ? "{$label} must be a number."
                    : null
            ),

            'integer' => (
                $value !== null && $value !== '' && !ctype_digit((string)$value)
                    ? "{$label} must be a whole number."
                    : null
            ),

            'min' => (
                $param !== null && $value !== null && mb_strlen((string)$value, 'UTF-8') < (int)$param
                    ? "{$label} must be at least {$param} characters."
                    : null
            ),

            'max' => (
                $param !== null && $value !== null && mb_strlen((string)$value, 'UTF-8') > (int)$param
                    ? "{$label} must not exceed {$param} characters."
                    : null
            ),

            'in' => (
                $param !== null && $value !== null && $value !== '' &&
                !in_array($value, explode(',', $param), true)
                    ? "{$label} must be one of: " . implode(', ', explode(',', $param)) . "."
                    : null
            ),

            'confirmed' => (
                $value !== ($this->data[$field . '_confirmation'] ?? null)
                    ? "{$label} confirmation does not match."
                    : null
            ),

            'file' => (
                !isset($this->data[$field]) || ($this->data[$field]['error'] ?? 4) !== UPLOAD_ERR_OK
                    ? "{$label} must be a valid uploaded file."
                    : null
            ),

            'mimes' => (
                $param !== null &&
                isset($this->data[$field]) &&
                ($this->data[$field]['error'] ?? 4) === UPLOAD_ERR_OK
                    ? $this->validateMimes($field, $param, $label)
                    : null
            ),

            'max_size' => (
                $param !== null &&
                isset($this->data[$field]) &&
                ($this->data[$field]['size'] ?? 0) > (int)$param
                    ? "{$label} must not exceed " . number_format((int)$param / 1024 / 1024, 1) . "MB."
                    : null
            ),

            default => null, // Unknown rules are silently ignored
        };
    }

    /**
     * Validate that an uploaded file has an allowed extension.
     */
    private function validateMimes(string $field, string $param, string $label): ?string
    {
        $file           = $this->data[$field];
        $allowedMimes   = explode(',', $param);

        // Get the actual file extension from the original filename
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedMimes, true)) {
            return "{$label} must be a file of type: " . implode(', ', $allowedMimes) . ".";
        }

        return null;
    }
}
```

---

### 0.10.6 — Html Helper

- [ ] **0.10.6** Create `app/Helpers/Html.php`:

```php
<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Html — HTML output helpers for views
 *
 * Usage in views:
 *   <?= Html::e($user->name) ?>                    — safe output
 *   <option <?= Html::selected($lang, 'en') ?>>   — conditional selected
 *   <input <?= Html::checked($active, 1) ?>>       — conditional checked
 *   <a class="<?= Html::active('/compose') ?>">    — active class
 */
class Html
{
    /**
     * HTML-escape a value. Alias of the global e() function.
     * Prevents XSS by converting special characters to HTML entities.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Output 'selected' if $value matches $current (for <select> options).
     *
     * Usage:
     *   <option value="en" <?= Html::selected($currentLang, 'en') ?>>English</option>
     */
    public static function selected(mixed $value, mixed $current): string
    {
        return $value == $current ? 'selected' : '';
    }

    /**
     * Output 'checked' if $value matches $current (for checkboxes/radio inputs).
     *
     * Usage:
     *   <input type="checkbox" <?= Html::checked($isActive, 1) ?>>
     */
    public static function checked(mixed $value, mixed $current): string
    {
        return $value == $current ? 'checked' : '';
    }

    /**
     * Return a CSS class string if the current request URI matches the given route.
     * Used to highlight the active sidebar link.
     *
     * Usage:
     *   <a class="nav-link <?= Html::active('/compose') ?>">Compose</a>
     *
     * If the current URI is '/compose', this outputs 'nav-link active'.
     * If not, this outputs 'nav-link '.
     */
    public static function active(string $route, string $activeClass = 'active'): string
    {
        $currentUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $currentUri === $route ? $activeClass : '';
    }

    /**
     * Build an HTMX attribute string from an array.
     *
     * Usage:
     *   Html::htmxAttrs([
     *       'hx-get'    => '/recipients',
     *       'hx-target' => '#recipient-table',
     *       'hx-swap'   => 'innerHTML',
     *   ])
     *
     * Output:
     *   hx-get="/recipients" hx-target="#recipient-table" hx-swap="innerHTML"
     */
    public static function htmxAttrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            $parts[] = static::e($key) . '="' . static::e($value) . '"';
        }
        return implode(' ', $parts);
    }
}
```

---

## 0.11 — Exception Classes

- [ ] **0.11.1** Create `app/Exceptions/AppException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * AppException — Base exception for all application-level exceptions.
 *
 * All custom exceptions extend this so we can distinguish our own exceptions
 * from PHP's built-in exceptions in the ErrorHandler.
 */
class AppException extends \RuntimeException
{
    // No additional code needed — inherits everything from RuntimeException.
    // The $code property (from the parent) maps to HTTP status codes in ErrorHandler.
}
```

- [ ] **0.11.2** Create `app/Exceptions/NotFoundException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * NotFoundException — Thrown when a requested resource does not exist.
 * Maps to HTTP 404.
 */
class NotFoundException extends AppException
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}
```

- [ ] **0.11.3** Create `app/Exceptions/AuthException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * AuthException — Thrown when a user is not authenticated or not authorised.
 * Maps to HTTP 401.
 */
class AuthException extends AppException
{
    public function __construct(string $message = 'Unauthorised')
    {
        parent::__construct($message, 401);
    }
}
```

- [ ] **0.11.4** Create `app/Exceptions/ValidationException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * ValidationException — Thrown when input validation fails.
 * Maps to HTTP 422.
 *
 * Carries the full errors array so controllers can flash them to the session.
 */
class ValidationException extends AppException
{
    private array $validationErrors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->validationErrors = $errors;
    }

    /**
     * Get the validation errors array.
     * Format: ['field_name' => 'Error message', ...]
     */
    public function errors(): array
    {
        return $this->validationErrors;
    }
}
```

- [ ] **0.11.5** Create `app/Exceptions/ProviderException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * ProviderException — Thrown when an email provider (Resend/SMTP) returns an error.
 *
 * Used in ResendProvider and SmtpProvider to signal send failures.
 * The ErrorHandler logs these and shows a toast notification.
 */
class ProviderException extends AppException
{
    private string $providerName;

    public function __construct(string $message, string $providerName = 'unknown', int $code = 500)
    {
        parent::__construct($message, $code);
        $this->providerName = $providerName;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}
```

- [ ] **0.11.6** Create `app/Exceptions/TranslationException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * TranslationException — Thrown when the LibreTranslate API call fails.
 */
class TranslationException extends AppException
{
    public function __construct(string $message = 'Translation failed')
    {
        parent::__construct($message, 500);
    }
}
```

- [ ] **0.11.7** Create `app/Exceptions/StorageException.php`:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * StorageException — Thrown when a file upload or storage operation fails.
 */
class StorageException extends AppException
{
    public function __construct(string $message = 'File storage operation failed')
    {
        parent::__construct($message, 500);
    }
}
```

---

## 0.12 — Middleware Classes

- [ ] **0.12.1** Create `app/Interfaces/MiddlewareInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Core\Request;
use App\Core\Response;

/**
 * MiddlewareInterface
 *
 * All middleware must implement this interface.
 *
 * The handle() method receives:
 *   $request — the current HTTP request
 *   $next    — a callable that runs the next middleware (or the controller)
 *
 * To allow the request to continue:   return $next($request);
 * To block the request:               return Response::redirect('/login');
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
```

- [ ] **0.12.2** Create `app/Middlewares/AuthMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * AuthMiddleware
 *
 * Protects routes that require a logged-in user.
 *
 * If the user is NOT authenticated:
 *   - Regular requests: redirect to /login
 *   - HTMX requests: send HX-Redirect header (HTMX handles the navigation)
 *
 * If the user IS authenticated:
 *   - Pass through to the next middleware or controller
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Check if user_id is in the session (set on login)
        if (!session()->has('user_id')) {
            // User is not logged in

            if ($request->isHtmx()) {
                // HTMX request: tell the browser to redirect via the HX-Redirect header
                // HTMX will navigate to /login client-side
                return Response::html('')
                    ->withStatus(200)
                    ->htmxRedirect('/login');
            }

            // Regular request: standard HTTP redirect to login page
            return Response::redirect('/login');
        }

        // User is authenticated — continue to the next middleware or controller
        return $next($request);
    }
}
```

- [ ] **0.12.3** Create `app/Middlewares/GuestMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * GuestMiddleware
 *
 * Applied to routes that should only be accessible to guests (non-logged-in users).
 * Specifically, this prevents authenticated users from revisiting /login.
 *
 * If the user IS authenticated: redirect to /compose
 * If the user is NOT authenticated: pass through
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (session()->has('user_id')) {
            // Already logged in — send them to the compose page
            return Response::redirect('/compose');
        }

        // Not logged in — show the guest page (login form)
        return $next($request);
    }
}
```

- [ ] **0.12.4** Create `app/Middlewares/CsrfMiddleware.php`:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;
use App\Interfaces\MiddlewareInterface;

/**
 * CsrfMiddleware
 *
 * Validates the CSRF token on all state-changing requests (POST, PUT, PATCH, DELETE).
 * GET and HEAD requests are skipped — they should never change state.
 *
 * The CSRF token is checked in two places:
 *   1. The '_csrf' hidden form field (for regular HTML form submissions)
 *   2. The 'X-CSRF-Token' request header (set by app.js for HTMX requests)
 *
 * Both are compared against the token stored in the user's session.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    // These HTTP methods don't change state, so no CSRF check is needed
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF check for safe (read-only) methods
        if (in_array($request->method(), static::SAFE_METHODS, true)) {
            return $next($request);
        }

        // Get the token that was submitted with the request
        // Check both the form field and the HTMX header
        $submittedToken = $request->post('_csrf')           // hidden form field
                       ?? $request->header('X-CSRF-Token'); // HTMX header

        // Get the token we stored in the session
        $sessionToken = session()->csrfToken();

        // Compare the two tokens securely
        // hash_equals prevents timing attacks (where comparing character-by-character
        // leaks information about how long the tokens are)
        if ($submittedToken === null || !hash_equals($sessionToken, $submittedToken)) {
            throw new AppException('CSRF token mismatch. Please refresh the page and try again.', 419);
        }

        // Token is valid — continue
        return $next($request);
    }
}
```

---

## 0.13 — Base Controller

- [ ] **0.13** Create `app/Controllers/BaseController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Helpers\Validator;

/**
 * BaseController
 *
 * All controllers extend this class. It provides shared helper methods
 * so controllers stay clean and consistent.
 *
 * Controllers should be "thin" — they receive input, call a service or model,
 * and return a response. Business logic belongs in Service classes.
 */
abstract class BaseController
{
    /**
     * Render a view and wrap it in an HTML response.
     *
     * The view file is resolved to resources/{template}.php
     * Data keys are available as variables inside the view.
     *
     * Usage:
     *   return $this->view('recipients/index', ['recipients' => $list]);
     */
    protected function view(string $template, array $data = []): Response
    {
        $html = view($template, $data);
        return Response::html($html);
    }

    /**
     * Return a JSON response.
     *
     * Usage:
     *   return $this->json(['status' => 'ok', 'message' => 'Saved']);
     *   return $this->json(['error' => 'Not found'], 404);
     */
    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    /**
     * Return a redirect response.
     *
     * Usage:
     *   return $this->redirect('/login');
     *   return $this->redirect('/compose', 301);
     */
    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    /**
     * Redirect back to the previous page.
     *
     * Usage:
     *   return $this->back();
     */
    protected function back(): Response
    {
        return Response::back();
    }

    /**
     * Validate request input against a set of rules.
     *
     * On success: returns the validated data array (only fields in the rules).
     * On failure: throws ValidationException with all error messages.
     *
     * The controller should catch ValidationException and call withErrors().
     *
     * Usage:
     *   $data = $this->validate($request->all(), [
     *       'email'    => 'required|email',
     *       'password' => 'required|min:8',
     *   ]);
     *   // If we get here, $data is safe to use
     */
    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        return $validator->validated();
    }

    /**
     * Handle a ValidationException by flashing errors to the session and redirecting back.
     *
     * This preserves:
     *   - The error messages (read back via errors() helper in views)
     *   - The old input values (read back via old() helper in views)
     *
     * Usage in a controller:
     *   try {
     *       $data = $this->validate($request->all(), $rules);
     *   } catch (ValidationException $e) {
     *       return $this->withErrors($e, $request->all());
     *   }
     */
    protected function withErrors(ValidationException $e, array $oldInput = []): Response
    {
        // Flash the errors so they survive the redirect
        session()->flash('_errors', $e->errors());

        // Flash the old input so forms can be re-populated
        session()->flash('_old_input', $oldInput);

        return $this->back();
    }

    /**
     * Return an HTMX-aware success response with a toast notification.
     *
     * For HTMX requests: returns an empty body with the HX-Trigger toast header.
     * For regular requests: redirects to the given URL.
     *
     * Usage:
     *   return $this->successResponse($request, '/recipients', 'Contact saved.');
     */
    protected function successResponse(
        Request $request,
        string  $redirectUrl,
        string  $toastMessage,
        string  $toastType = 'success'
    ): Response {
        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => $toastType,
                    'message' => $toastMessage,
                ]);
        }

        // Flash a toast message for the next page load (non-HTMX)
        session()->flash('toast', ['type' => $toastType, 'message' => $toastMessage]);
        return $this->redirect($redirectUrl);
    }
}
```

---

## 0.14 — Stub Error Views

The `ErrorHandler` needs these files to exist before the smoke test. We'll build the full versions in Phase 3. For now, just create minimal stubs.

- [ ] **0.14.1** Create `resources/error/404.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 80px 20px; color: #374151; }
        h1   { font-size: 4rem; font-weight: 800; color: #111827; margin: 0; }
        p    { font-size: 1.125rem; color: #6B7280; margin: 12px 0 32px; }
        a    { display: inline-block; padding: 10px 24px; background: #4F46E5; color: #fff;
               border-radius: 8px; text-decoration: none; font-weight: 600; }
        a:hover { background: #4338CA; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

- [ ] **0.14.2** Create `resources/error/403.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 80px 20px; color: #374151; }
        h1   { font-size: 4rem; font-weight: 800; color: #111827; margin: 0; }
        p    { font-size: 1.125rem; color: #6B7280; margin: 12px 0 32px; }
        a    { display: inline-block; padding: 10px 24px; background: #4F46E5; color: #fff;
               border-radius: 8px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <h1>403</h1>
    <p>You don't have permission to access this page.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

- [ ] **0.14.3** Create `resources/error/500.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 80px 20px; color: #374151; }
        h1   { font-size: 4rem; font-weight: 800; color: #111827; margin: 0; }
        p    { font-size: 1.125rem; color: #6B7280; margin: 12px 0 32px; }
        a    { display: inline-block; padding: 10px 24px; background: #4F46E5; color: #fff;
               border-radius: 8px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <h1>500</h1>
    <p>Something went wrong on our end. We've been notified and are looking into it.</p>
    <a href="/">Go Home</a>
</body>
</html>
```

- [ ] **0.14.4** Create `resources/error/debug.php`:

```php
<?php
/**
 * resources/error/debug.php
 *
 * Developer debug error page. Only shown when APP_DEBUG=true.
 * Variables available (injected by ErrorHandler::renderDebugPage()):
 *   $exceptionClass — string: the exception class name
 *   $message        — string: HTML-safe error message
 *   $file           — string: HTML-safe file path
 *   $line           — int:    line number
 *   $trace          — string: HTML-safe stack trace
 *   $sourceLines    — array:  ['line_number' => 'code_line', ...]
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug — <?= $exceptionClass ?? 'Error' ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0f172a; color: #e2e8f0; font-size: 14px; }
        .header { background: #dc2626; padding: 20px 32px; }
        .header h1 { font-size: 1.1rem; font-weight: 700; color: #fff; font-family: monospace; }
        .header p  { font-size: 1.5rem; font-weight: 600; color: #fecaca; margin-top: 6px; }
        .meta { padding: 16px 32px; background: #1e293b; border-bottom: 1px solid #334155; }
        .meta span { background: #334155; padding: 4px 10px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; color: #94a3b8; }
        .meta span b { color: #f8fafc; }
        .section { padding: 24px 32px; }
        .section h2 { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 12px; }
        .source { background: #1e293b; border-radius: 8px; overflow: hidden; border: 1px solid #334155; }
        .source table { width: 100%; border-collapse: collapse; font-family: 'Fira Code', 'Courier New', monospace; font-size: 0.8rem; }
        .source td { padding: 2px 16px; white-space: pre; }
        .source .line-num { color: #475569; text-align: right; user-select: none; width: 48px; border-right: 1px solid #334155; }
        .source .error-line { background: #450a0a; }
        .source .error-line .line-num { background: #dc2626; color: #fff; }
        .trace { background: #1e293b; border-radius: 8px; padding: 20px; font-family: monospace; font-size: 0.78rem; color: #94a3b8; white-space: pre-wrap; word-break: break-all; border: 1px solid #334155; overflow-x: auto; }
        .panel { background: #1e293b; border-radius: 8px; border: 1px solid #334155; overflow: hidden; margin-bottom: 16px; }
        .panel summary { padding: 12px 20px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: #cbd5e1; list-style: none; }
        .panel summary:hover { background: #334155; }
        .panel pre { padding: 16px 20px; font-size: 0.78rem; color: #94a3b8; overflow-x: auto; white-space: pre-wrap; border-top: 1px solid #334155; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($exceptionClass ?? 'RuntimeException') ?></h1>
        <p><?= $message ?></p>
    </div>

    <div class="meta">
        <span><b>File:</b> <?= $file ?></span>
         
        <span><b>Line:</b> <?= $line ?></span>
    </div>

    <?php if (!empty($sourceLines)): ?>
    <div class="section">
        <h2>Source Code</h2>
        <div class="source">
            <table>
                <?php foreach ($sourceLines as $lineNum => $codeLine): ?>
                <tr class="<?= $lineNum === $line ? 'error-line' : '' ?>">
                    <td class="line-num"><?= $lineNum ?></td>
                    <td><?= htmlspecialchars($codeLine) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>Stack Trace</h2>
        <div class="trace"><?= $trace ?></div>
    </div>

    <div class="section">
        <h2>Request Context</h2>

        <details class="panel" open>
            <summary>$_SERVER</summary>
            <pre><?= htmlspecialchars(print_r($_SERVER, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_POST</summary>
            <pre><?= htmlspecialchars(print_r($_POST, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_GET</summary>
            <pre><?= htmlspecialchars(print_r($_GET, true)) ?></pre>
        </details>

        <details class="panel">
            <summary>$_SESSION</summary>
            <pre><?= htmlspecialchars(print_r($_SESSION ?? [], true)) ?></pre>
        </details>
    </div>
</body>
</html>
```

---

## 0.15 — Milestone: Framework Smoke Test

These are the verification steps. Follow them in order.

- [ ] **0.15.1** Add a temporary test route at the bottom of `routes/web.php`:

  ```php
  // TEMPORARY — remove after smoke test passes
  $router->get('/ping', function() {
      return \App\Core\Response::html('<h1 style="font-family:sans-serif;padding:40px">🏓 Pong — Emirates framework is running!</h1>');
  });
  ```

  > **Note:** The router's `addRoute` method expects `[ControllerClass, 'method']` arrays. For this one test we're using a closure. To make this work, update `Router::runRoute` temporarily to detect closures: replace the handler call with `if (is_callable($route['handler'])) { return ($route['handler'])(); }` before the controller instantiation. Or simply test by running through a stub controller — whichever is easier.
  >
- [ ] **0.15.2** Start the local PHP development server:

  ```bash
  php -S localhost:8000 -t public/
  ```
- [ ] **0.15.3** Open your browser and visit `http://localhost:8000/ping`.

  - **Expected result:** You see "🏓 Pong — Emirates framework is running!"
  - If you see a PHP error, check that `BASE_PATH` is being set in `public/index.php` and that `bootstrap/app.php` requires `vendor/autoload.php`.
- [ ] **0.15.4** Visit `http://localhost:8000/does-not-exist`.

  - **Expected result:** With `APP_DEBUG=true`, you see the dark debug page showing a `NotFoundException`.
  - The debug page should show the exception class, message, file, line, and stack trace.
- [ ] **0.15.5** Temporarily set `APP_DEBUG=false` in `.env`, then visit `http://localhost:8000/does-not-exist`.

  - **Expected result:** You see the clean `resources/error/404.php` page.
  - Set `APP_DEBUG=true` again after this test.
- [ ] **0.15.6** Check that the log file was created:

  ```bash
  ls storage/logs/
  # Should show: app-YYYY-MM-DD.log
  cat storage/logs/app-$(date +%Y-%m-%d).log
  ```
- [ ] **0.15.7** Remove the temporary `/ping` route and any temporary Router changes you made for the closure test.
- [ ] **0.15.8** Commit Phase 0:

  ```bash
  git add -A
  git commit -m "Phase 0: Framework scaffolding, MVC core, helpers, middleware, error handling"
  ```

---

## Phase 0 Complete ✅

**What you have built:**

| Component           | Files                                                                                                                                                      |
| ------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Directory structure | All 39 directories +`.gitkeep` files                                                                                                                     |
| Composer            | `composer.json`, `vendor/autoload.php`, PHPMailer installed                                                                                            |
| Environment         | `.env`, `.env.example`, `APP_KEY` generated                                                                                                          |
| Config              | `config/app.php`, `database.php`, `mail.php`, `storage.php`, `session.php`, `translation.php`                                                  |
| Framework Core      | `EnvLoader`, `Config`, `Logger`, `Session`, `Request`, `Response`, `Router`, `Database`, `Model`, `ErrorHandler`, `App`              |
| Bootstrap           | `bootstrap/app.php`, `bootstrap/helpers.php`                                                                                                           |
| Entry point         | `public/index.php`, `public/.htaccess`                                                                                                                 |
| Routes              | `routes/web.php` (all routes declared), `routes/api.php`                                                                                               |
| Helpers             | `Str`, `Url`, `Date`, `Crypto`, `Validator`, `Html`                                                                                            |
| Exceptions          | `AppException`, `NotFoundException`, `AuthException`, `ValidationException`, `ProviderException`, `TranslationException`, `StorageException` |
| Interfaces          | `LoggerInterface`, `MiddlewareInterface`                                                                                                               |
| Middleware          | `AuthMiddleware`, `GuestMiddleware`, `CsrfMiddleware`                                                                                                |
| Base Controller     | `BaseController`                                                                                                                                         |
| Stub error views    | `404.php`, `403.php`, `500.php`, `debug.php`                                                                                                       |

**Ready for Phase 1:** Database migrations, models, repositories, and seeders.

---

*End of Emirates Phase 0 Implementation*
