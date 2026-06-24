# Emirates — Implementation Plan
**Version:** 1.0
**Document Type:** Detailed Task-by-Task Implementation Guide
**Status:** Active
**Reference:** Emirates Architecture Document v1.0

---

## How to Read This Document

Each phase represents a logical, deployable increment of the system. Phases must be completed in order — later phases depend on foundations established earlier. Within each phase, tasks are sequenced so that each task's dependencies are already in place when you reach it.

Task notation:
- `[ ]` — Not started
- `[x]` — Complete
- `[~]` — In progress

**File paths** are relative to the project root (`emirates/`).

---

## Phase 0 — Project Scaffolding & Environment Setup

> Goal: A running PHP application that responds to HTTP requests, loads config, connects to the database, and handles errors — before a single feature is built.

---

### 0.1 — Repository & Local Environment

- [ ] **0.1.1** Create the project root directory `emirates/`.
- [ ] **0.1.2** Initialise a Git repository: `git init`.
- [ ] **0.1.3** Create `.gitignore` with entries for:
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
- [ ] **0.1.4** Confirm local PHP version is 8.3+: `php -v`.
- [ ] **0.1.5** Confirm required PHP extensions are enabled locally: `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `openssl`, `json`, `session`. Check via `php -m`.
- [ ] **0.1.6** Confirm MySQL 8.x is running locally and accessible.
- [ ] **0.1.7** Create the local MySQL database: `CREATE DATABASE emirates CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.
- [ ] **0.1.8** Confirm Composer is installed: `composer -v`.

---

### 0.2 — Directory Skeleton

Create every directory in the project tree. Empty directories need a `.gitkeep` file so Git tracks them.

- [ ] **0.2.1** Create `app/Core/`
- [ ] **0.2.2** Create `app/Controllers/`
- [ ] **0.2.3** Create `app/Middlewares/`
- [ ] **0.2.4** Create `app/Models/`
- [ ] **0.2.5** Create `app/Repositories/Contracts/`
- [ ] **0.2.6** Create `app/Services/`
- [ ] **0.2.7** Create `app/Providers/Contracts/`
- [ ] **0.2.8** Create `app/Interfaces/`
- [ ] **0.2.9** Create `app/Helpers/`
- [ ] **0.2.10** Create `app/Exceptions/`
- [ ] **0.2.11** Create `app/DTOs/`
- [ ] **0.2.12** Create `config/`
- [ ] **0.2.13** Create `database/migrations/`
- [ ] **0.2.14** Create `database/seeders/`
- [ ] **0.2.15** Create `resources/layouts/`
- [ ] **0.2.16** Create `resources/auth/`
- [ ] **0.2.17** Create `resources/compose/`
- [ ] **0.2.18** Create `resources/recipients/`
- [ ] **0.2.19** Create `resources/logs/`
- [ ] **0.2.20** Create `resources/settings/templates/`
- [ ] **0.2.21** Create `resources/components/cards/`
- [ ] **0.2.22** Create `resources/components/tables/`
- [ ] **0.2.23** Create `resources/components/forms/`
- [ ] **0.2.24** Create `resources/components/ui/`
- [ ] **0.2.25** Create `resources/components/navigation/`
- [ ] **0.2.26** Create `resources/error/`
- [ ] **0.2.27** Create `storage/logs/` with `.gitkeep`
- [ ] **0.2.28** Create `storage/uploads/logos/global/` with `.gitkeep`
- [ ] **0.2.29** Create `storage/uploads/logos/email/` with `.gitkeep`
- [ ] **0.2.30** Create `storage/uploads/templates/` with `.gitkeep`
- [ ] **0.2.31** Create `storage/sessions/` with `.gitkeep`
- [ ] **0.2.32** Create `storage/cache/` with `.gitkeep`
- [ ] **0.2.33** Create `assets/css/`
- [ ] **0.2.34** Create `assets/js/`
- [ ] **0.2.35** Create `assets/icons/`
- [ ] **0.2.36** Create `assets/img/`
- [ ] **0.2.37** Create `bootstrap/`
- [ ] **0.2.38** Create `routes/`
- [ ] **0.2.39** Create `public/`

---

### 0.3 — Composer Setup

- [ ] **0.3.1** Create `composer.json`:
  ```json
  {
    "name": "emirates/app",
    "description": "Emirates Email Platform",
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
      "optimize-autoloader": true
    }
  }
  ```
- [ ] **0.3.2** Run `composer install` to pull PHPMailer and generate the autoloader.
- [ ] **0.3.3** Confirm `vendor/autoload.php` exists.

---

### 0.4 — Environment File

- [ ] **0.4.1** Create `.env.example` with all keys documented but no real values:
  ```dotenv
  APP_NAME="Emirates"
  APP_URL=http://localhost
  APP_DEBUG=true
  APP_KEY=

  DB_HOST=localhost
  DB_PORT=3306
  DB_NAME=emirates
  DB_USER=root
  DB_PASS=

  LIBRETRANSLATE_URL=https://libretranslate.com
  LIBRETRANSLATE_API_KEY=

  RESEND_WEBHOOK_SECRET=

  SESSION_LIFETIME=7200
  TIMEZONE=Africa/Lagos
  ```
- [ ] **0.4.2** Copy `.env.example` to `.env` and fill in local values.
- [ ] **0.4.3** Generate a 32-byte random key for `APP_KEY` and write it in base64 format. You can use: `php -r "echo 'base64:' . base64_encode(random_bytes(32));"`.

---

### 0.5 — Config Files

- [ ] **0.5.1** Create `config/app.php`:
  ```php
  <?php
  return [
      'name'     => $_ENV['APP_NAME'] ?? 'Emirates',
      'url'      => $_ENV['APP_URL']  ?? 'http://localhost',
      'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'key'      => $_ENV['APP_KEY']  ?? '',
      'timezone' => $_ENV['TIMEZONE'] ?? 'UTC',
  ];
  ```
- [ ] **0.5.2** Create `config/database.php` with `host`, `port`, `name`, `user`, `pass` keys reading from `$_ENV`.
- [ ] **0.5.3** Create `config/mail.php` with `active_provider` (default: `'resend'`), `default_sender_name`, `default_sender_email` keys.
- [ ] **0.5.4** Create `config/storage.php` with `uploads_path` (absolute path to `storage/uploads/`), `max_logo_size` (2MB in bytes), `max_template_size` (5MB), `allowed_logo_mimes`, `allowed_template_mimes` keys.
- [ ] **0.5.5** Create `config/session.php` with `lifetime`, `name`, `path`, `secure`, `httponly`, `samesite` keys.
- [ ] **0.5.6** Create `config/translation.php` with `base_url`, `api_key`, `supported_languages` (associative array of code → label) keys.

---

### 0.6 — Core Framework Files

#### 0.6.1 — `Config` Class
- [ ] **0.6.1.1** Create `app/Core/Config.php`.
- [ ] **0.6.1.2** Constructor accepts the path to the `config/` directory.
- [ ] **0.6.1.3** Implement `get(string $key, mixed $default = null): mixed` using dot notation: `config('app.debug')` reads from `config/app.php` key `debug`.
- [ ] **0.6.1.4** Cache loaded config files in a private array property so each file is only `require`d once.
- [ ] **0.6.1.5** Throw a clear `\RuntimeException` if the config file does not exist.

#### 0.6.2 — `.env` Loader
- [ ] **0.6.2.1** Create `app/Core/EnvLoader.php`.
- [ ] **0.6.2.2** Implement `static load(string $path): void` that reads the `.env` file line by line, skips comments (`#`) and blank lines, splits on the first `=`, and populates `$_ENV` and `putenv()`.
- [ ] **0.6.2.3** Strip surrounding quotes from values.
- [ ] **0.6.2.4** Do not override existing `$_ENV` values (allows server environment to take precedence).

#### 0.6.3 — `Logger` Class
- [ ] **0.6.3.1** Create `app/Interfaces/LoggerInterface.php` declaring `debug`, `info`, `warning`, `error`, `critical` methods each accepting `(string $message, array $context = [])`.
- [ ] **0.6.3.2** Create `app/Core/Logger.php` implementing `LoggerInterface`.
- [ ] **0.6.3.3** Constructor accepts a `$logDirectory` path.
- [ ] **0.6.3.4** Each write appends to `{logDirectory}/app-{Y-m-d}.log`.
- [ ] **0.6.3.5** Log format: `[{datetime}] {LEVEL}: {message} {context_json_if_any}\n`.
- [ ] **0.6.3.6** Create the log directory if it does not exist (using `mkdir` with `recursive: true`).
- [ ] **0.6.3.7** Silently fail (catch internal exceptions) — the logger must never crash the application.

#### 0.6.4 — `Session` Class
- [ ] **0.6.4.1** Create `app/Core/Session.php`.
- [ ] **0.6.4.2** Implement `start(): void` — applies ini settings from `config/session.php` then calls `session_start()` if not already active.
- [ ] **0.6.4.3** Implement `get(string $key, mixed $default = null): mixed`.
- [ ] **0.6.4.4** Implement `set(string $key, mixed $value): void`.
- [ ] **0.6.4.5** Implement `has(string $key): bool`.
- [ ] **0.6.4.6** Implement `forget(string $key): void`.
- [ ] **0.6.4.7** Implement `flash(string $key, mixed $value): void` — stores value tagged for single-use retrieval.
- [ ] **0.6.4.8** Implement `getFlash(string $key, mixed $default = null): mixed` — retrieves and deletes the flash value.
- [ ] **0.6.4.9** Implement `destroy(): void` — unsets session data, destroys session, clears cookie.
- [ ] **0.6.4.10** Implement `regenerate(): void` — calls `session_regenerate_id(true)`.
- [ ] **0.6.4.11** Implement `csrfToken(): string` — generates a token if none exists, stores in session, returns it.

#### 0.6.5 — `Request` Class
- [ ] **0.6.5.1** Create `app/Core/Request.php`.
- [ ] **0.6.5.2** Implement `static capture(): static` as the primary constructor that reads from superglobals.
- [ ] **0.6.5.3** Implement `method(): string` — returns HTTP method, respecting `_method` POST override for PUT/PATCH/DELETE from HTML forms.
- [ ] **0.6.5.4** Implement `uri(): string` — returns the path portion of the request URI (strips query string).
- [ ] **0.6.5.5** Implement `get(string $key, mixed $default = null): mixed` — reads from `$_GET`.
- [ ] **0.6.5.6** Implement `post(string $key, mixed $default = null): mixed` — reads from `$_POST`.
- [ ] **0.6.5.7** Implement `input(string $key, mixed $default = null): mixed` — reads from POST, then GET.
- [ ] **0.6.5.8** Implement `all(): array` — merged GET + POST.
- [ ] **0.6.5.9** Implement `file(string $key): ?array` — reads from `$_FILES`.
- [ ] **0.6.5.10** Implement `header(string $key): ?string` — reads from `$_SERVER`, normalising `HTTP_` prefix and dashes to underscores.
- [ ] **0.6.5.11** Implement `isHtmx(): bool` — checks for `HX-Request: true` header.
- [ ] **0.6.5.12** Implement `ip(): string` — reads `REMOTE_ADDR` from `$_SERVER`.
- [ ] **0.6.5.13** Implement `isPost(): bool`, `isGet(): bool`, `isPut(): bool`, `isDelete(): bool`.
- [ ] **0.6.5.14** Implement `expectsJson(): bool` — checks `Accept: application/json` header.
- [ ] **0.6.5.15** Implement `bearerToken(): ?string` — parses `Authorization: Bearer ...` header.

#### 0.6.6 — `Response` Class
- [ ] **0.6.6.1** Create `app/Core/Response.php`.
- [ ] **0.6.6.2** Implement `html(string $content, int $status = 200): static`.
- [ ] **0.6.6.3** Implement `json(mixed $data, int $status = 200): static`.
- [ ] **0.6.6.4** Implement `redirect(string $url, int $status = 302): static`.
- [ ] **0.6.6.5** Implement `back(): static` — redirects to `HTTP_REFERER` or `/` as fallback.
- [ ] **0.6.6.6** Implement `withHeader(string $key, string $value): static` (fluent).
- [ ] **0.6.6.7** Implement `withStatus(int $status): static` (fluent).
- [ ] **0.6.6.8** Implement `htmxTrigger(string $eventName, mixed $data = null): static` — appends `HX-Trigger` header as JSON.
- [ ] **0.6.6.9** Implement `htmxRedirect(string $url): static` — sets `HX-Redirect` header for HTMX client-side redirect.
- [ ] **0.6.6.10** Implement `send(): never` — writes all headers and body, then calls `exit`.
- [ ] **0.6.6.11** Implement `stream(string $filepath, string $mimeType): never` — for file streaming (used by StorageController).

#### 0.6.7 — `Router` Class
- [ ] **0.6.7.1** Create `app/Core/Router.php`.
- [ ] **0.6.7.2** Implement `get()`, `post()`, `put()`, `patch()`, `delete()` registration methods, each accepting `(string $uri, array $handler)` where handler is `[ControllerClass, 'methodName']`.
- [ ] **0.6.7.3** Implement `group(array $options, callable $callback): void` for applying middleware to route groups.
- [ ] **0.6.7.4** Implement URI pattern matching supporting `{param}` wildcard segments (e.g. `/recipients/{id}`).
- [ ] **0.6.7.5** Implement `dispatch(Request $request): Response` — finds the matching route, runs its middleware stack, instantiates the controller, calls the method, returns the Response.
- [ ] **0.6.7.6** Inject matched URI parameters into the controller method as named arguments.
- [ ] **0.6.7.7** Throw `NotFoundException` if no route matches.
- [ ] **0.6.7.8** Throw `\RuntimeException` if the controller class or method does not exist.

#### 0.6.8 — `Model` Base Class (PDO Wrapper)
- [ ] **0.6.8.1** Create `app/Core/Database.php` — singleton PDO connection manager. Constructor reads from `config/database.php`, creates a PDO connection with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, and `EMULATE_PREPARES=false`.
- [ ] **0.6.8.2** Create `app/Core/Model.php`.
- [ ] **0.6.8.3** Declare abstract `protected static string $table` that each model must set.
- [ ] **0.6.8.4** Implement `static db(): PDO` — retrieves the PDO connection from the `Database` singleton.
- [ ] **0.6.8.5** Implement `static find(int $id): ?static` — `SELECT * FROM {table} WHERE id = ? LIMIT 1`.
- [ ] **0.6.8.6** Implement `static findBy(string $column, mixed $value): ?static` — single record lookup.
- [ ] **0.6.8.7** Implement `static all(string $orderBy = 'id', string $dir = 'ASC'): array` — returns array of hydrated model instances.
- [ ] **0.6.8.8** Implement `static where(array $conditions, string $orderBy = 'id', string $dir = 'ASC', ?int $limit = null, int $offset = 0): array` — AND-chained conditions.
- [ ] **0.6.8.9** Implement `static count(array $conditions = []): int`.
- [ ] **0.6.8.10** Implement `static paginate(int $perPage, int $page, array $conditions = []): array` — returns `['data' => [...], 'total' => n, 'page' => n, 'per_page' => n, 'last_page' => n]`.
- [ ] **0.6.8.11** Implement `save(): bool` — INSERT if no `id`, UPDATE if `id` is set. Reads from a `protected array $fillable` property.
- [ ] **0.6.8.12** Implement `static create(array $data): static` — creates and saves a new model.
- [ ] **0.6.8.13** Implement `update(array $data): bool` — merges data and saves.
- [ ] **0.6.8.14** Implement `delete(): bool` — `DELETE FROM {table} WHERE id = ?`.
- [ ] **0.6.8.15** Implement `static raw(string $sql, array $bindings = []): array` — for complex queries that don't fit the builder.
- [ ] **0.6.8.16** Implement `static rawOne(string $sql, array $bindings = []): ?static`.
- [ ] **0.6.8.17** Implement `toArray(): array` — returns model properties as an array.
- [ ] **0.6.8.18** Implement magic `__get` / `__set` / `__isset` for property access on fetched rows.

#### 0.6.9 — `ErrorHandler` Class
- [ ] **0.6.9.1** Create `app/Core/ErrorHandler.php`.
- [ ] **0.6.9.2** Implement `register(): void` — calls `set_exception_handler`, `set_error_handler`, `register_shutdown_function`.
- [ ] **0.6.9.3** In debug mode: render `resources/error/debug.php` with exception class, message, file, line, trace, and `$_SERVER`/`$_POST` context. Use syntax highlighting (pass relevant source lines as context).
- [ ] **0.6.9.4** In production mode: log full exception via `Logger`, render appropriate error view (404/403/500).
- [ ] **0.6.9.5** Map exception types to HTTP status codes: `NotFoundException` → 404, `AuthException` → 401, `ValidationException` → 422, everything else → 500.
- [ ] **0.6.9.6** For HTMX requests in production: return a `HX-Trigger: {"showToast": {...}}` response with the appropriate error message instead of a full error page.

#### 0.6.10 — `App` Class (Service Container)
- [ ] **0.6.10.1** Create `app/Core/App.php`.
- [ ] **0.6.10.2** Implement `singleton(string $abstract, callable $factory): void` — stores a lazy factory; the factory is called only on first `make()` and then cached.
- [ ] **0.6.10.3** Implement `bind(string $abstract, callable $factory): void` — stores a factory called fresh every `make()`.
- [ ] **0.6.10.4** Implement `make(string $abstract): mixed` — resolves the binding.
- [ ] **0.6.10.5** Implement `instance(string $abstract, mixed $instance): void` — registers a pre-made instance.
- [ ] **0.6.10.6** Implement `static setInstance(self $app): void` and `static getInstance(): self` for global access.
- [ ] **0.6.10.7** Implement `run(): never` — calls `EnvLoader::load()`, starts session, creates `Request`, runs `Router::dispatch()`, calls `Response::send()`.

---

### 0.7 — Bootstrap Files

- [ ] **0.7.1** Create `bootstrap/app.php`:
  - Require `vendor/autoload.php`.
  - Instantiate `Config`, `Logger`, `Session`, `Database`.
  - Register all singleton and bind entries (see Architecture §4.4).
  - Register `ErrorHandler`.
  - Set timezone from config.
  - Return the `App` instance.

- [ ] **0.7.2** Create `bootstrap/helpers.php` with global convenience functions:
  - `config(string $key, mixed $default = null): mixed` — calls `App::getInstance()->make(Config::class)->get(...)`.
  - `view(string $path, array $data = []): string` — resolves `resources/{path}.php`, extracts data, captures output buffer, returns HTML string.
  - `redirect(string $url, int $status = 302): Response`.
  - `back(): Response`.
  - `e(mixed $value): string` — `htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')`.
  - `session(): Session` — returns the Session singleton.
  - `logger(): Logger` — returns the Logger singleton.
  - `storage_path(string $path = ''): string` — returns absolute path to `storage/`.
  - `asset(string $path): string` — returns full URL to `assets/{path}`.
  - `url(string $path = ''): string` — returns full URL with `APP_URL` prefix.
  - `csrf_token(): string` — returns `session()->csrfToken()`.
  - `csrf_field(): string` — returns `<input type="hidden" name="_csrf" value="{token}">`.
  - `old(string $key, mixed $default = ''): mixed` — reads from flash `_old_input`.
  - `errors(?string $key = null): array|string` — reads from flash `_errors`.
  - `setting(string $key, mixed $default = null): mixed` — reads from `settings` table via `SettingRepository`.

---

### 0.8 — Public Entry Point

- [ ] **0.8.1** Create `public/index.php`:
  ```php
  <?php
  declare(strict_types=1);

  define('BASE_PATH', dirname(__DIR__));

  $app = require BASE_PATH . '/bootstrap/app.php';
  $app->run();
  ```
- [ ] **0.8.2** Create `public/.htaccess` with mod_rewrite rules (route everything to `index.php`, deny `.env` access, disable directory indexes).
- [ ] **0.8.3** Verify the application responds to `GET /` with a 404 (no routes registered yet = correct behaviour).

---

### 0.9 — Route Files

- [ ] **0.9.1** Create `routes/web.php` as a skeleton with all route declarations from Architecture §4.3 — controllers will not exist yet, so these are registered but will throw until Phase 1.
- [ ] **0.9.2** Create `routes/api.php` with the webhook route `POST /webhooks/resend`.
- [ ] **0.9.3** Load both route files inside `bootstrap/app.php` after the router is instantiated.

---

### 0.10 — Helpers Implementation

- [ ] **0.10.1** Create `app/Helpers/Str.php`:
  - `slug(string $text): string` — converts to lowercase-kebab-case.
  - `truncate(string $text, int $length, string $suffix = '...'): string`.
  - `mask(string $text, int $visibleStart = 4, int $visibleEnd = 4): string` — for credential display.
  - `uuid(): string` — generates a UUID v4 using `random_bytes`.
  - `contains(string $haystack, string $needle): bool`.
  - `startsWith(string $haystack, string $prefix): bool`.

- [ ] **0.10.2** Create `app/Helpers/Url.php`:
  - `asset(string $path): string`.
  - `url(string $path): string`.
  - `storagePath(string $relative): string`.
  - `storageUrl(string $relative): string` — returns the `/storage/{type}/{filename}` URL.

- [ ] **0.10.3** Create `app/Helpers/Date.php`:
  - `format(string $datetime, string $format = 'd M Y, g:ia'): string` — uses app timezone.
  - `diffForHumans(string $datetime): string` — "2 hours ago", "just now", etc.
  - `now(): string` — current datetime in MySQL format (`Y-m-d H:i:s`).

- [ ] **0.10.4** Create `app/Helpers/Crypto.php`:
  - `encrypt(string $plaintext): string` — AES-256-CBC with `APP_KEY`, returns base64(iv + ciphertext).
  - `decrypt(string $ciphertext): string` — reverses encrypt.
  - Throw `\RuntimeException` if `APP_KEY` is empty or decryption fails.

- [ ] **0.10.5** Create `app/Helpers/Validator.php`:
  - `make(array $data, array $rules): static` — factory.
  - `passes(): bool`.
  - `fails(): bool`.
  - `errors(): array` — keyed by field name.
  - `validated(): array` — only the fields that passed.
  - Support rules: `required`, `email`, `min:n`, `max:n`, `url`, `in:a,b,c`, `numeric`, `integer`, `regex:/pattern/`, `confirmed` (checks `field_confirmation`), `file`, `mimes:jpg,png`, `max_size:n` (bytes).

- [ ] **0.10.6** Create `app/Helpers/Html.php`:
  - `e(mixed $value): string` — alias of the global `e()` helper.
  - `selected(mixed $value, mixed $current): string` — returns `selected` or empty.
  - `checked(mixed $value, mixed $current): string` — returns `checked` or empty.
  - `active(string $route): string` — returns `active` class if current URI matches.
  - `htmxAttrs(array $attrs): string` — builds `hx-*` attribute string from an array.

---

### 0.11 — Exception Classes

- [ ] **0.11.1** Create `app/Exceptions/AppException.php` — extends `\RuntimeException`.
- [ ] **0.11.2** Create `app/Exceptions/NotFoundException.php` — extends `AppException`, default message "Not Found", HTTP status 404.
- [ ] **0.11.3** Create `app/Exceptions/AuthException.php` — extends `AppException`, default message "Unauthorised", HTTP status 401.
- [ ] **0.11.4** Create `app/Exceptions/ValidationException.php` — extends `AppException`. Constructor accepts `array $errors`. Implement `errors(): array`. HTTP status 422.
- [ ] **0.11.5** Create `app/Exceptions/ProviderException.php` — extends `AppException`. Stores provider name and HTTP status from provider response.
- [ ] **0.11.6** Create `app/Exceptions/TranslationException.php` — extends `AppException`.
- [ ] **0.11.7** Create `app/Exceptions/StorageException.php` — extends `AppException`.

---

### 0.12 — Middleware Classes

- [ ] **0.12.1** Create `app/Interfaces/MiddlewareInterface.php` declaring `handle(Request $request, callable $next): Response`.
- [ ] **0.12.2** Create `app/Middlewares/AuthMiddleware.php`:
  - Checks `session()->has('user_id')`.
  - If not authenticated: for HTMX requests return `HX-Redirect: /login`; for regular requests return a redirect to `/login`.
- [ ] **0.12.3** Create `app/Middlewares/GuestMiddleware.php`:
  - If already authenticated, redirect to `/compose`.
- [ ] **0.12.4** Create `app/Middlewares/CsrfMiddleware.php`:
  - Skip on GET/HEAD/OPTIONS requests.
  - Compare `request->post('_csrf')` or `request->header('X-CSRF-Token')` with `session()->csrfToken()`.
  - On mismatch: throw `AppException` with HTTP 419 (CSRF Token Mismatch).

---

### 0.13 — Base Controller

- [ ] **0.13.1** Create `app/Controllers/BaseController.php`:
  - `protected function view(string $template, array $data = []): Response` — calls the `view()` helper, wraps in `Response::html()`.
  - `protected function json(mixed $data, int $status = 200): Response` — calls `Response::json()`.
  - `protected function redirect(string $url, int $status = 302): Response`.
  - `protected function back(): Response`.
  - `protected function validate(array $data, array $rules): array` — runs `Validator`, throws `ValidationException` on failure, returns `validated()` on success.
  - `protected function withErrors(ValidationException $e): Response` — flashes errors and old input to session, returns `back()`.

---

### 0.14 — Milestone: Framework Smoke Test

- [ ] **0.14.1** Add a temporary test route in `routes/web.php`: `GET /ping` → inline closure returning `Response::html('<h1>Pong</h1>')`.
- [ ] **0.14.2** Start local PHP server: `php -S localhost:8000 -t public/`.
- [ ] **0.14.3** Visit `http://localhost:8000/ping`. Confirm "Pong" is returned with HTTP 200.
- [ ] **0.14.4** Visit `http://localhost:8000/does-not-exist`. Confirm `NotFoundException` is thrown and the debug page (or 404 page) renders.
- [ ] **0.14.5** Remove the test route.

---

## Phase 1 — Database & Models

> Goal: All tables exist in the database; all models and repositories are implemented and testable.

---

### 1.1 — Migration Runner

- [ ] **1.1.1** Create `database/migrate.php` — a CLI script that:
  - Reads all `.sql` files from `database/migrations/` in filename order.
  - Creates a `_migrations` table if it doesn't exist to track applied migrations.
  - Skips already-applied migrations.
  - Runs each new migration inside a transaction; rolls back and reports on failure.
  - Accepts `--seed` flag to run seeders after migrations.
  - Accepts `--fresh` flag to drop all tables and re-run everything.

---

### 1.2 — Migration SQL Files

Write all SQL migration files exactly as defined in Architecture §5. Each file should include `CREATE TABLE IF NOT EXISTS`.

- [ ] **1.2.1** `001_create_migrations_table.sql` — creates the `_migrations` tracking table.
- [ ] **1.2.2** `002_create_users_table.sql`
- [ ] **1.2.3** `003_create_settings_table.sql`
- [ ] **1.2.4** `004_create_credentials_table.sql`
- [ ] **1.2.5** `005_create_email_templates_table.sql`
- [ ] **1.2.6** `006_create_recipients_table.sql`
- [ ] **1.2.7** `007_create_recipient_groups_table.sql`
- [ ] **1.2.8** `008_create_recipient_group_pivot_table.sql`
- [ ] **1.2.9** `009_create_email_drafts_table.sql`
- [ ] **1.2.10** `010_create_email_logs_table.sql`
- [ ] **1.2.11** `011_create_email_error_logs_table.sql`
- [ ] **1.2.12** `012_create_received_emails_table.sql`
- [ ] **1.2.13** Run `php database/migrate.php` and confirm all tables are created without errors.
- [ ] **1.2.14** Run `php database/migrate.php` a second time and confirm it is fully idempotent (no errors, no re-running).

---

### 1.3 — Model Classes

Each model extends `Core\Model` and declares `$table`, `$fillable`, and type-hinted properties.

- [ ] **1.3.1** Create `app/Models/User.php` — table: `users`. Properties: `id`, `name`, `email`, `password_hash`, `created_at`. Method: `verifyPassword(string $plain): bool`.
- [ ] **1.3.2** Create `app/Models/Setting.php` — table: `settings`. Static convenience: `getValue(string $key, mixed $default = null): mixed` and `setValue(string $key, mixed $value): void`.
- [ ] **1.3.3** Create `app/Models/Credential.php` — table: `credentials`. Properties: `id`, `provider`, `is_active`, `config` (raw encrypted JSON), `created_at`, `updated_at`. Method: `decryptedConfig(): array` — calls `Crypto::decrypt($this->config)` and JSON-decodes.
- [ ] **1.3.4** Create `app/Models/EmailTemplate.php` — table: `email_templates`. Properties: `id`, `name`, `category`, `html_content`, `thumbnail_path`, `is_built_in`, `supports_logo`, `supports_colors`, `created_at`, `updated_at`.
- [ ] **1.3.5** Create `app/Models/Recipient.php` — table: `recipients`. Properties: `id`, `first_name`, `last_name`, `email`, `company`, `notes`, `is_suppressed`, `created_at`. Method: `fullName(): string`.
- [ ] **1.3.6** Create `app/Models/RecipientGroup.php` — table: `recipient_groups`. Properties: `id`, `name`, `created_at`. Method: `members(): array` — joins on pivot table.
- [ ] **1.3.7** Create `app/Models/EmailDraft.php` — table: `email_drafts`. Properties: all columns. Method: `recipientsArray(): array` — JSON-decodes `recipients_json`.
- [ ] **1.3.8** Create `app/Models/EmailLog.php` — table: `email_logs`. Properties: all columns. Method: `recipientsArray(): array`.
- [ ] **1.3.9** Create `app/Models/EmailErrorLog.php` — table: `email_error_logs`. Properties: all columns.

---

### 1.4 — Repository Interfaces

- [ ] **1.4.1** Create `app/Interfaces/RepositoryInterface.php`:
  ```php
  interface RepositoryInterface {
      public function find(int $id): ?object;
      public function all(): array;
      public function create(array $data): object;
      public function update(int $id, array $data): bool;
      public function delete(int $id): bool;
  }
  ```
- [ ] **1.4.2** Create `app/Repositories/Contracts/SettingRepositoryInterface.php` with `get(string $key, mixed $default): mixed` and `set(string $key, mixed $value): void` and `allAsKeyValue(): array`.
- [ ] **1.4.3** Create `app/Repositories/Contracts/TemplateRepositoryInterface.php` with `findBuiltIn(): array`, `findCustom(): array`, `duplicate(int $id): EmailTemplate`.
- [ ] **1.4.4** Create `app/Repositories/Contracts/DraftRepositoryInterface.php` with `findLatest(): array`, `upsertAutosave(array $data): EmailDraft`.
- [ ] **1.4.5** Create `app/Repositories/Contracts/RecipientRepositoryInterface.php` with `search(string $query): array`, `findByEmail(string $email): ?Recipient`, `findByGroup(string $groupName): array`, `bulkInsert(array $records): int`, `suppress(int $id): bool`.
- [ ] **1.4.6** Create `app/Repositories/Contracts/LogRepositoryInterface.php` with `paginate(int $page, string $type, array $filters): array`, `updateStatus(string $providerMsgId, string $status): bool`, `clearAll(string $type): int`.

---

### 1.5 — Repository Implementations

- [ ] **1.5.1** Create `app/Repositories/SettingRepository.php` implementing `SettingRepositoryInterface`. The `allAsKeyValue()` method returns an associative array of `key => value` from all rows. Cache settings in a static property for the request lifetime.
- [ ] **1.5.2** Create `app/Repositories/TemplateRepository.php`. `duplicate()` copies all columns except `id`, `is_built_in` (set false), `created_at`, `updated_at`, appending " (Copy)" to the name.
- [ ] **1.5.3** Create `app/Repositories/DraftRepository.php`. `upsertAutosave()` checks if a draft_id is provided and updates, otherwise inserts, returning the draft model.
- [ ] **1.5.4** Create `app/Repositories/RecipientRepository.php`. `bulkInsert()` uses a single prepared statement with multiple value tuples. Skips rows where the email already exists (INSERT IGNORE). `search()` uses `LIKE` on `first_name`, `last_name`, `email`, `company`.
- [ ] **1.5.5** Create `app/Repositories/LogRepository.php`. `paginate()` accepts `$type` as `'sent'|'error'|'received'` and queries the appropriate table. Supports filters: `recipient`, `subject`, `status`, `date_from`, `date_to`.

---

### 1.6 — Seeders

- [ ] **1.6.1** Create `database/seeders/UserSeeder.php` — inserts the single admin user. Reads initial email and password from `.env` (`ADMIN_EMAIL`, `ADMIN_PASSWORD`). Hashes password with `password_hash()`. Skips if user already exists.
- [ ] **1.6.2** Add `ADMIN_EMAIL` and `ADMIN_PASSWORD` to `.env.example` and `.env`.
- [ ] **1.6.3** Create `database/seeders/SettingSeeder.php` — inserts default values for all setting keys defined in Architecture §5.2. Uses `INSERT IGNORE` so re-running is safe.
- [ ] **1.6.4** Create `database/seeders/TemplateSeeder.php` — inserts at least 3 built-in HTML email templates (Newsletter, Transactional, Promotional) with `is_built_in = 1`. Each template HTML must contain `{{LOGO_URL}}`, `{{PRIMARY_COLOR}}`, `{{SECONDARY_COLOR}}` placeholders so `supports_logo = 1` and `supports_colors = 1`.
- [ ] **1.6.5** Write the built-in template HTML for "Newsletter" — a clean single-column layout with logo at top, heading, body text block, and footer.
- [ ] **1.6.6** Write the built-in template HTML for "Transactional" — minimal header, greeting, body, a single CTA button, footer.
- [ ] **1.6.7** Write the built-in template HTML for "Promotional" — logo, hero section with primary color background, body, CTA, footer with secondary color accent.
- [ ] **1.6.8** Run `php database/migrate.php --seed` and confirm all tables are populated correctly.

---

### 1.7 — DTOs

- [ ] **1.7.1** Create `app/DTOs/EmailPayload.php` — `readonly class` with: `array $recipients`, `string $subject`, `string $html`, `string $senderName`, `string $senderEmail`, `?string $replyTo = null`, `?array $cc = null`, `?array $bcc = null`.
- [ ] **1.7.2** Create `app/DTOs/SendResult.php` — `readonly class` with: `string $messageId`, `string $status`, `?string $providerResponse = null`.
- [ ] **1.7.3** Create `app/DTOs/RecipientData.php` — `readonly class` with: `string $firstName`, `string $lastName`, `string $email`, `?string $company`, `?string $tags`.
- [ ] **1.7.4** Create `app/DTOs/TemplateData.php` — `readonly class` with: `string $name`, `string $category`, `string $htmlContent`, `bool $supportsLogo`, `bool $supportsColors`.
- [ ] **1.7.5** Create `app/DTOs/RenderContext.php` — `readonly class` with: `string $logoUrl`, `string $primaryColor`, `string $secondaryColor`, `string $senderName`, `string $senderEmail`, `?string $replyTo`.

---

## Phase 2 — Authentication

> Goal: The platform has a working login/logout flow with session management and full route protection.

---

### 2.1 — Auth Service

- [ ] **2.1.1** Create `app/Services/AuthService.php`.
- [ ] **2.1.2** Implement `attempt(string $email, string $password): bool`:
  - Finds user by email via `User::findBy('email', $email)`.
  - Verifies password with `password_verify()`.
  - On success: calls `session()->regenerate()`, sets `session()->set('user_id', $user->id)`, sets `session()->set('user_name', $user->name)`.
  - Returns true/false.
- [ ] **2.1.3** Implement `logout(): void` — calls `session()->destroy()`.
- [ ] **2.1.4** Implement `check(): bool` — returns `session()->has('user_id')`.
- [ ] **2.1.5** Implement `user(): ?User` — finds and returns the session user.

---

### 2.2 — Auth Controller

- [ ] **2.2.1** Create `app/Controllers/AuthController.php` extending `BaseController`.
- [ ] **2.2.2** Implement `showLogin(Request $request): Response` — if already authenticated, redirect to `/compose`. Otherwise, render `auth/login`.
- [ ] **2.2.3** Implement `login(Request $request): Response`:
  - Validate: `email` required + email format, `password` required.
  - Call `AuthService::attempt()`.
  - On success: redirect to `/compose`.
  - On failure: flash error "Invalid credentials.", redirect back.
- [ ] **2.2.4** Implement `logout(Request $request): Response` — calls `AuthService::logout()`, redirects to `/login`.

---

### 2.3 — Auth Views

- [ ] **2.3.1** Create `resources/layouts/auth.php`:
  - Minimal HTML shell: `<!DOCTYPE html>`, `<html>`, `<head>` with meta charset, viewport, title, Tailwind CDN link, Bootstrap Icons CDN link.
  - `<body>` with a centered card container.
  - `<?= $content ?>` slot.

- [ ] **2.3.2** Create `resources/auth/login.php`:
  - Emirates logo/name at the top of the card.
  - Flash error alert (reads from `errors()` flash).
  - Email field (`type="email"`, `name="email"`, `value="<?= old('email') ?>"`).
  - Password field (`type="password"`, `name="password"`).
  - CSRF hidden field via `<?= csrf_field() ?>`.
  - "Sign In" submit button.
  - No registration link (single user).

---

### 2.4 — Milestone: Authentication Test

- [ ] **2.4.1** Visit `/login` — confirm the login form renders.
- [ ] **2.4.2** Submit with wrong credentials — confirm flash error appears.
- [ ] **2.4.3** Submit with correct credentials (from seeded user) — confirm redirect to `/compose` (will 404 for now, that's fine).
- [ ] **2.4.4** Visit `/login` while authenticated — confirm redirect to `/compose`.
- [ ] **2.4.5** Visit `/recipients` without a session — confirm redirect to `/login`.
- [ ] **2.4.6** Call `POST /logout` — confirm session is destroyed, redirect to `/login`.

---

## Phase 3 — Application Shell & Navigation

> Goal: The authenticated layout, sidebar, mobile nav, and global UI components are in place. Every page can be visited and renders the shell (even if the content area is empty).

---

### 3.1 — App Layout

- [ ] **3.1.1** Create `resources/layouts/app.php`:
  - Full HTML document structure with `<head>` including: charset, viewport, title (dynamic), Tailwind CSS, Bootstrap Icons, HTMX JS (from `assets/js/htmx.min.js`), `assets/js/app.js`.
  - `<meta name="csrf-token" content="<?= csrf_token() ?>">` in `<head>`.
  - `<body>` with a two-column flex/grid layout: sidebar (desktop) + main content area.
  - Toast container `<div id="toast-container">` positioned fixed top-right (desktop) / top-center (mobile).
  - Global loader `<div id="global-loader" class="htmx-indicator">` — a thin progress bar at the top.
  - `<?= $content ?>` slot in the main area.

- [ ] **3.1.2** Create `resources/components/navigation/_sidebar.php`:
  - Emirates logo/name at the top.
  - Nav links: Compose (`/compose`), Recipients (`/recipients`), Logs (`/logs`), Templates (`/settings/templates`), Credentials (`/settings/credentials`), General Settings (`/settings/general`).
  - Each link uses a Bootstrap Icon + label.
  - Active state applied via `Html::active()` helper.
  - Logout button at the bottom.
  - Hidden on mobile (CSS `hidden md:flex`).

- [ ] **3.1.3** Create `resources/components/navigation/_bottom-nav.php`:
  - Four main tabs: Compose, Recipients, Logs, Settings.
  - Fixed to bottom of viewport on mobile (`fixed bottom-0`).
  - Touch targets minimum 44×44px.
  - Active state styling.
  - Hidden on desktop (`md:hidden`).

- [ ] **3.1.4** Create `resources/components/ui/_toast.php`:
  - Toast component rendered via JS (triggered by `HX-Trigger` header events).
  - Write JS in `assets/js/app.js` to listen for `showToast` HTMX event and inject a styled toast into `#toast-container`.
  - Toast types: success (green), error (red), warning (amber), info (blue).
  - Auto-dismiss after 4 seconds for success/info; persist until dismissed for error.
  - Dismiss button (×) on each toast.
  - Animate in (slide + fade) and animate out.

- [ ] **3.1.5** Create `resources/components/ui/_modal.php`:
  - Generic modal shell with `id`, `title`, and `#modal-body` slot.
  - Open/close via custom JS function `openModal(id)` / `closeModal(id)`.
  - Backdrop click closes modal.
  - `Escape` key closes modal.

- [ ] **3.1.6** Create `resources/components/ui/_loader.php` — spinner component used within HTMX `hx-indicator` targets.

- [ ] **3.1.7** Create `resources/components/ui/_empty-state.php` — accepts icon, heading, subtext, and optional CTA button. Used across all listing pages when there is no data.

- [ ] **3.1.8** Create `resources/components/ui/_badge.php` — status badge component accepting `type` (success, error, warning, info, neutral) and `label`.

- [ ] **3.1.9** Create `resources/components/tables/_pagination.php` — renders page links given `$page`, `$lastPage`, `$baseUrl`. Uses HTMX `hx-get` and `hx-target` for async page switching.

- [ ] **3.1.10** Create `resources/components/tables/_sortable-header.php` — `<th>` that posts sort column and direction via HTMX.

---

### 3.2 — `app.js` Global Script

- [ ] **3.2.1** Download `htmx.min.js` (2.x) and place in `assets/js/`.
- [ ] **3.2.2** Create `assets/js/app.js`:
  - CSRF header injection on every HTMX request.
  - `showToast` event listener.
  - `openModal` / `closeModal` global functions.
  - Backdrop and Escape key handler for modals.
  - HTMX `htmx:afterRequest` listener for scrolling to top on full page swaps.

---

### 3.3 — Error Views

- [ ] **3.3.1** Create `resources/layouts/error.php` — bare, no sidebar, no nav. Just logo + centred content.
- [ ] **3.3.2** Create `resources/error/404.php` — friendly "Page Not Found" with a "Go Home" button.
- [ ] **3.3.3** Create `resources/error/403.php` — "Access Denied" page.
- [ ] **3.3.4** Create `resources/error/500.php` — "Something went wrong" page with a logged error reference.
- [ ] **3.3.5** Create `resources/error/debug.php` — developer debug view:
  - Exception class and message in a prominent heading.
  - File + line number.
  - Source code snippet (5 lines before and after, highlighted line).
  - Full stack trace with file/line links.
  - Collapsible panels for `$_SERVER`, `$_POST`, `$_SESSION`, `$_GET`.
  - Styled with inline CSS (no external deps — must work even if assets fail to load).

---

### 3.4 — Stub Controllers & Pages

Create empty stub controllers and placeholder views so the nav links render without 500 errors.

- [ ] **3.4.1** Create stub `app/Controllers/ComposeController.php` with `index()` returning `view('compose/index')`.
- [ ] **3.4.2** Create stub `app/Controllers/RecipientController.php` with `index()` returning `view('recipients/index')`.
- [ ] **3.4.3** Create stub `app/Controllers/LogController.php` with `index()` returning `view('logs/index')`.
- [ ] **3.4.4** Create stub `app/Controllers/TemplateController.php` with `index()` returning `view('settings/templates/index')`.
- [ ] **3.4.5** Create stub `app/Controllers/CredentialController.php` with `index()` returning `view('settings/credentials')`.
- [ ] **3.4.6** Create stub `app/Controllers/SettingsController.php` with `index()` returning `view('settings/general')`.
- [ ] **3.4.7** Create minimal placeholder view for each of the above (just a heading in the layout).

---

### 3.5 — Milestone: Shell Test

- [ ] **3.5.1** Log in and confirm the sidebar + main layout renders on `/compose`.
- [ ] **3.5.2** Confirm all nav links in the sidebar load the correct placeholder page without errors.
- [ ] **3.5.3** On a mobile viewport, confirm the bottom nav is visible and the sidebar is hidden.
- [ ] **3.5.4** Manually trigger a toast by adding `HX-Trigger` to a response and confirm it appears and auto-dismisses.
- [ ] **3.5.5** Visit `/does-not-exist` — confirm 404 view renders within the error layout.
- [ ] **3.5.6** Temporarily force a 500 error and confirm the debug page renders (with `APP_DEBUG=true`).

---

## Phase 4 — General Settings

> Goal: The platform's identity settings can be saved and read throughout the application.

---

### 4.1 — Settings Service (Read/Write)

- [ ] **4.1.1** Confirm `SettingRepository` is complete from Phase 1.
- [ ] **4.1.2** Confirm the `setting()` global helper function resolves via `SettingRepository::get()`.
- [ ] **4.1.3** Ensure `SettingRepository` caches the full settings array in a static property so it's only queried once per request.

---

### 4.2 — File Upload Service

- [ ] **4.2.1** Create `app/Services/FileUploadService.php`.
- [ ] **4.2.2** Implement `uploadLogo(array $file, string $context = 'global'): string` — validates MIME (image/png, image/jpeg, image/svg+xml), max 2MB, generates a unique filename, moves to `storage/uploads/logos/{context}/`, returns relative path.
- [ ] **4.2.3** Implement `uploadTemplate(array $file): string` — validates MIME (text/html, application/zip), max 5MB, moves to `storage/uploads/templates/{uuid}/`, extracts ZIP if applicable, returns path to the main `.html` file.
- [ ] **4.2.4** Implement `delete(string $relativePath): bool` — safely removes a file from storage (validates path stays within `storage/uploads/`).
- [ ] **4.2.5** Throw `StorageException` on any failure.

---

### 4.3 — Storage Controller

- [ ] **4.3.1** Create `app/Controllers/StorageController.php`.
- [ ] **4.3.2** Implement `serve(Request $request, string $type, string $filename): Response`:
  - Validates `$type` is one of `logos`, `templates`.
  - Constructs absolute path: `storage_path("uploads/{$type}/{$filename}")`.
  - Confirms path is within `storage/uploads/` (prevent path traversal).
  - Confirms file exists.
  - Detects MIME type via `mime_content_type()`.
  - Returns `Response::stream()`.
- [ ] **4.3.3** Add route `GET /storage/{type}/{filename}` to `routes/web.php` under auth middleware.

---

### 4.4 — Settings Controller & View

- [ ] **4.4.1** Complete `app/Controllers/SettingsController.php`:
  - `index(Request $request): Response` — loads all settings from `SettingRepository::allAsKeyValue()`, passes to view.
  - `update(Request $request): Response`:
    - Validate all fields.
    - If `site_logo` file uploaded, call `FileUploadService::uploadLogo($file, 'site')`, delete old file.
    - If `email_logo` file uploaded, call `FileUploadService::uploadLogo($file, 'global')`, delete old file.
    - Iterate validated fields and call `SettingRepository::set()` for each.
    - Return HTMX-aware response: `HX-Trigger: showToast success "Settings updated."` + redirect or partial refresh.

- [ ] **4.4.2** Create `resources/settings/general.php`:
  - Page heading "General Settings".
  - Form posting to `POST /settings/general` with `_method` override if needed (or just POST).
  - Sections: Platform Identity (Website Name, URL, Site Logo upload), Email Defaults (Sender Name, Sender Email), Email Branding (Global Email Logo, Primary Color picker, Secondary Color picker), Localisation (Default Language dropdown, Timezone dropdown).
  - File upload fields show current logo preview if set.
  - Color picker fields show current hex value with a colour swatch preview.
  - "Save Changes" button.
  - Flash success/error toast on save.

- [ ] **4.4.3** Create `resources/components/forms/_input.php` — reusable field partial accepting `name`, `label`, `type`, `value`, `placeholder`, `error`.
- [ ] **4.4.4** Create `resources/components/forms/_color-picker.php` — `<input type="color">` paired with a hex text input, synced via JS.
- [ ] **4.4.5** Create `resources/components/forms/_file-upload.php` — styled file input with current preview and remove option.

---

### 4.5 — Milestone: Settings Test

- [ ] **4.5.1** Visit `/settings/general` — confirm all fields render with seeded defaults.
- [ ] **4.5.2** Change the site name, save — confirm toast appears and value persists on reload.
- [ ] **4.5.3** Upload a logo — confirm it appears as a preview and is accessible via `/storage/logos/...`.
- [ ] **4.5.4** Set primary and secondary colours — confirm they save and repopulate on reload.
- [ ] **4.5.5** Confirm `setting('primary_color')` returns the correct value in a temporary debug line.

---

## Phase 5 — Email Templates

> Goal: Templates can be listed, uploaded, pasted, previewed, edited, duplicated, and deleted. Built-in templates cannot be deleted.

---

### 5.1 — Template Render Service

- [ ] **5.1.1** Create `app/Services/TemplateRenderService.php`.
- [ ] **5.1.2** Implement `render(string $html, RenderContext $ctx): string` — performs three-pass token replacement as defined in Architecture §7.1.
- [ ] **5.1.3** Implement `inspect(string $html): array` — returns `['supports_logo' => bool, 'supports_colors' => bool]`.
- [ ] **5.1.4** Implement `renderWithGlobalContext(string $html): string` — builds `RenderContext` from current settings and calls `render()`. Used for previews.

---

### 5.2 — Template Controller

- [ ] **5.2.1** Complete `app/Controllers/TemplateController.php`:
  - `index()` — loads all templates, separates built-in and custom, passes to view.
  - `create()` — renders the create form.
  - `store(Request $request): Response`:
    - Accept either file upload or pasted HTML.
    - If file: call `FileUploadService::uploadTemplate()`, read the HTML content.
    - Run `TemplateRenderService::inspect()` on the HTML.
    - Validate: `name` required, `category` required, HTML content not empty.
    - Create via `TemplateRepository::create()`.
    - Return redirect to template list with success toast.
  - `edit(Request $request, int $id): Response` — loads template, renders edit view.
  - `update(Request $request, int $id): Response` — validates + updates.
  - `destroy(Request $request, int $id): Response`:
    - Load template; if `is_built_in` throw `AppException` "Built-in templates cannot be deleted."
    - Delete from DB and delete stored file if applicable.
    - Return HTMX response removing the card from the DOM (use `HX-Trigger` or return empty 200).
  - `duplicate(Request $request, int $id): Response` — calls `TemplateRepository::duplicate()`, redirects to edit view for the new copy.
  - `preview(Request $request, int $id): Response` — renders the template HTML with global context, returns as a partial for the preview modal.

---

### 5.3 — Template Views

- [ ] **5.3.1** Create `resources/settings/templates/index.php`:
  - Page heading "Email Templates" with "Add Template" button.
  - Tab strip or section separator for "Built-in" and "Custom" templates.
  - Grid of `_template-card.php` components.
  - Empty state when no custom templates exist.

- [ ] **5.3.2** Create `resources/components/cards/_template-card.php`:
  - Template name, category badge, last modified date.
  - Thumbnail (iframe or img) — for MVP, use a simple coloured placeholder with the template name if no thumbnail.
  - Action buttons: Preview, Edit (custom only), Duplicate, Delete (custom only, with confirm dialog).
  - Built-in badge on built-in templates.
  - Delete button triggers HTMX DELETE with a confirmation `hx-confirm` attribute.

- [ ] **5.3.3** Create `resources/settings/templates/create.php`:
  - Two-panel layout on desktop: left = form, right = live preview iframe.
  - Toggle between "Upload File" and "Paste HTML" tabs.
  - Upload tab: file input (`.html`, `.zip`), template name field, category dropdown.
  - Paste tab: CodeMirror/basic `<textarea>` for HTML input, name field, category dropdown.
  - HTMX: on input change in the textarea, post to `POST /settings/templates/preview-draft` and render into the preview iframe's `srcdoc` attribute.
  - "Save Template" button.

- [ ] **5.3.4** Create `resources/settings/templates/edit.php` — same layout as create, pre-populated with existing HTML.

- [ ] **5.3.5** Create `resources/settings/templates/_preview-modal.php` — modal containing an iframe with `srcdoc` set to the rendered template HTML. Desktop/mobile viewport toggle buttons that resize the iframe width.

- [ ] **5.3.6** Add route `POST /settings/templates/preview-draft` for live preview during creation (returns rendered HTML fragment).

---

### 5.4 — Milestone: Templates Test

- [ ] **5.4.1** Visit `/settings/templates` — confirm 3 built-in templates display with correct badges.
- [ ] **5.4.2** Open preview modal on a built-in template — confirm it renders with global logo and colours.
- [ ] **5.4.3** Upload a custom `.html` template containing `{{PRIMARY_COLOR}}` — confirm it appears in the list with `supports_colors = true`.
- [ ] **5.4.4** Upload a template without placeholders — confirm colour/logo controls are flagged as unsupported in the DB.
- [ ] **5.4.5** Duplicate a built-in template — confirm a "(Copy)" version appears in the Custom section.
- [ ] **5.4.6** Attempt to delete a built-in template — confirm it is rejected.
- [ ] **5.4.7** Delete a custom template — confirm it is removed from the list without a page reload.
- [ ] **5.4.8** Paste raw HTML in the create form — confirm live preview updates as you type.

---

## Phase 6 — Email Credentials

> Goal: Resend and SMTP credentials can be saved, encrypted, switched between, and tested.

---

### 6.1 — Credential Service

- [ ] **6.1.1** Create `app/Services/CredentialService.php`.
- [ ] **6.1.2** Implement `save(string $provider, array $config): Credential`:
  - Encrypts `$config` array via `Crypto::encrypt(json_encode($config))`.
  - Upserts a `Credential` row for the provider.
- [ ] **6.1.3** Implement `getActive(): ?Credential` — finds the row where `is_active = 1`.
- [ ] **6.1.4** Implement `setActive(string $provider): void` — sets all rows to `is_active = 0`, then sets the given provider's row to `is_active = 1`.
- [ ] **6.1.5** Implement `testConnection(string $provider): bool` — instantiates the appropriate provider adapter and calls `testConnection()` on it. Returns true/false.

---

### 6.2 — Provider Implementations

- [ ] **6.2.1** Create `app/Providers/Contracts/EmailProviderInterface.php` with `send(EmailPayload $payload): SendResult` and `testConnection(): bool`.
- [ ] **6.2.2** Create `app/Providers/ResendProvider.php`:
  - Constructor accepts `string $apiKey`.
  - `send()` — full implementation as shown in Architecture §6.2. Throws `ProviderException` on non-200 response.
  - `testConnection()` — sends a minimal request to `GET https://api.resend.com/domains` to validate the key. Returns true if 200.
- [ ] **6.2.3** Create `app/Providers/SmtpProvider.php`:
  - Constructor accepts array config: `host`, `port`, `encryption`, `username`, `password`, `from_name`, `from_email`.
  - `send()` — creates a `PHPMailer` instance, configures it from the config array, calls `send()`. Throws `ProviderException` on failure.
  - `testConnection()` — configures PHPMailer SMTP and calls `SmtpConnect()`. Returns true/false.
- [ ] **6.2.4** Register the `EmailProviderInterface` binding in `bootstrap/app.php` to resolve the active provider at runtime via `CredentialService::getActive()`.

---

### 6.3 — Credential Controller & View

- [ ] **6.3.1** Complete `app/Controllers/CredentialController.php`:
  - `index()` — loads both Resend and SMTP credential rows (may be null), loads active provider from `CredentialService::getActive()`, passes to view.
  - `store(Request $request): Response`:
    - Determine which provider form was submitted (hidden `provider` field).
    - Validate fields for that provider.
    - Call `CredentialService::save()`.
    - If `set_active` checkbox is checked, call `CredentialService::setActive()`.
    - Return success toast.
  - `test(Request $request): Response`:
    - Read `provider` from POST body.
    - Call `CredentialService::testConnection()`.
    - Return HTMX partial showing pass/fail status with appropriate toast trigger.

- [ ] **6.3.2** Create `resources/settings/credentials.php`:
  - Active provider radio selector at the top.
  - Two accordion/tab sections: Resend and SMTP.
  - **Resend section**: API Key (masked input with reveal toggle), From Email (informational), "Test Connection" button (HTMX POST to `/settings/credentials/test`), "Save" button.
  - **SMTP section**: Host, Port, Encryption dropdown, Username, Password (masked with reveal), From Name, From Email, "Test Connection" button, "Save" button.
  - Test result area: HTMX target `#test-result-{provider}` showing inline pass/fail badge.
  - Passwords never pre-populated — show "••••••••" placeholder if credentials exist, with a "Change" toggle that reveals the input.

---

### 6.4 — Milestone: Credentials Test

- [ ] **6.4.1** Save Resend API key — confirm it is stored encrypted (check the DB directly: the `config` column should be ciphertext).
- [ ] **6.4.2** Test Resend connection — confirm pass/fail toast and inline result display correctly.
- [ ] **6.4.3** Save SMTP credentials — confirm encryption.
- [ ] **6.4.4** Test SMTP connection — confirm result.
- [ ] **6.4.5** Switch active provider — confirm the radio updates and `CredentialService::getActive()` returns the new provider.
- [ ] **6.4.6** Confirm API key is never visible in page source or response bodies (only the masked input placeholder).

---

## Phase 7 — Recipients

> Goal: Contacts can be added individually, imported by CSV, tagged, searched, and suppressed.

---

### 7.1 — Recipient Controller

- [ ] **7.1.1** Complete `app/Controllers/RecipientController.php`:
  - `index(Request $request): Response` — loads paginated recipients (20/page), supports search query, passes to view.
  - `create(): Response` — renders create form.
  - `store(Request $request): Response`:
    - Validate: `email` required + valid format, `first_name` required.
    - Check for duplicate email via `RecipientRepository::findByEmail()`.
    - `RecipientRepository::create()`.
    - For HTMX requests (inline add), return the new recipient row partial. For regular POST, redirect to index.
  - `edit(Request $request, int $id): Response` — loads recipient, renders edit form.
  - `update(Request $request, int $id): Response` — validates + updates.
  - `destroy(Request $request, int $id): Response` — soft-delete or hard-delete. For HTMX, return `<tr id="recipient-{id}"></tr>` (empty target removes the row).
  - `import(Request $request): Response` — renders import page (GET), processes CSV (POST).
  - `suppress(Request $request, int $id): Response` — sets `is_suppressed = 1`.

---

### 7.2 — CSV Import Service

- [ ] **7.2.1** Create `app/Services/CsvImportService.php`.
- [ ] **7.2.2** Implement `import(string $filePath): array` — returns `['imported' => n, 'skipped' => n, 'errors' => [...]]`.
- [ ] **7.2.3** Validate that the uploaded file is `text/csv` or `text/plain`.
- [ ] **7.2.4** Detect and handle CSV with/without a header row (check if first row matches expected column names).
- [ ] **7.2.5** Expected columns: `first_name`, `last_name`, `email`, `company`, `tags` (all optional except `email`).
- [ ] **7.2.6** Validate each row's email format. Add to `errors` list if invalid.
- [ ] **7.2.7** Use `RecipientRepository::bulkInsert()` for valid rows. Skip duplicates (`INSERT IGNORE`).
- [ ] **7.2.8** Parse `tags` column (comma-separated) and create/associate `RecipientGroup` records.

---

### 7.3 — Recipient Views

- [ ] **7.3.1** Create `resources/recipients/index.php`:
  - Page heading "Recipients" + "Add Recipient" and "Import CSV" buttons.
  - Search bar (HTMX `hx-get` to `/recipients` with `q` param, `hx-trigger="keyup changed delay:300ms"`, `hx-target="#recipient-table"`).
  - Recipient table with columns: Name, Email, Company, Tags, Date Added, Actions.
  - Pagination at the bottom.
  - Each row has Edit and Delete (with confirm) action buttons.
  - Suppressed recipients shown with a strikethrough or muted style.

- [ ] **7.3.2** Create `resources/recipients/create.php` — form with First Name, Last Name, Email, Company, Tags (comma-separated input), Notes.

- [ ] **7.3.3** Create `resources/recipients/edit.php` — same form pre-populated.

- [ ] **7.3.4** Create `resources/recipients/import.php`:
  - CSV format description and download link to a sample CSV.
  - File upload input.
  - "Import" button.
  - Results section (HTMX target) showing imported/skipped/error counts after upload.

- [ ] **7.3.5** Create `resources/components/cards/_recipient-card.php` — for a potential mobile card view of recipients.

---

### 7.4 — Milestone: Recipients Test

- [ ] **7.4.1** Add a recipient manually — confirm it appears in the list.
- [ ] **7.4.2** Search for the recipient by name and by email — confirm HTMX live search works.
- [ ] **7.4.3** Edit the recipient — confirm changes save.
- [ ] **7.4.4** Import a CSV with 5 valid rows and 1 invalid email row — confirm 5 imported, 1 error reported.
- [ ] **7.4.5** Import the same CSV again — confirm 5 skipped (duplicates), 0 imported.
- [ ] **7.4.6** Suppress a recipient — confirm they appear as suppressed.
- [ ] **7.4.7** Delete a recipient — confirm the row is removed without page reload.

---

## Phase 8 — Email Composition

> Goal: The full compose page is functional — template selection, metadata entry, branding overrides, body editing, auto-save, and draft management.

---

### 8.1 — Draft Controller

- [ ] **8.1.1** Create `app/Controllers/DraftController.php`:
  - `index(): Response` — lists all drafts for the sidebar drawer.
  - `store(Request $request): Response` — saves a new draft, returns draft ID in a `HX-Trigger` or header.
  - `update(Request $request, int $id): Response` — updates existing draft.
  - `autosave(Request $request): Response` — upserts draft via `DraftRepository::upsertAutosave()`. Returns `<span>Saved {time}</span>` partial.
  - `destroy(Request $request, int $id): Response` — deletes draft.
  - `load(Request $request, int $id): Response` — returns the compose form pre-populated with draft data as an HTMX partial.

---

### 8.2 — Compose Controller

- [ ] **8.2.1** Complete `app/Controllers/ComposeController.php`:
  - `index(Request $request): Response` — loads templates list, active global settings (logo, colours), renders compose page.
  - `send(Request $request): Response`:
    - Validate: recipients not empty, subject not empty, body not empty.
    - Resolve recipients: expand group names to individual emails, filter suppressed.
    - Build `EmailPayload` DTO.
    - Resolve logo and colour context (email-level override → global settings).
    - Call `TemplateRenderService::render()` on the body HTML.
    - Call `EmailSendService::send()`.
    - Log to `email_logs`.
    - On success: return success toast + reset compose form (HTMX).
    - On failure: return error toast + log to `email_error_logs`.
  - `preview(Request $request): Response` — renders the current body + template with active context into a preview partial for the modal.

---

### 8.3 — Email Send Service

- [ ] **8.3.1** Create `app/Services/EmailSendService.php`.
- [ ] **8.3.2** Implement `send(EmailPayload $payload): SendResult`:
  - Resolves `EmailProviderInterface` from the container.
  - Calls `$provider->send($payload)`.
  - Returns `SendResult`.
  - Throws `ProviderException` on failure.
- [ ] **8.3.3** Implement logging responsibility: after a successful send, insert into `email_logs`. After failure, insert into `email_error_logs`. The controller calls `send()` and handles the result.

---

### 8.4 — Compose Views

- [ ] **8.4.1** Create `resources/compose/index.php`:
  - Wraps sub-partials: `_toolbar.php`, `_metadata.php`, `_editor.php`.
  - A hidden `#compose-form` wrapping all inputs for HTMX `hx-include`.
  - Auto-save trigger div (HTMX polling every 60s).
  - `_draft-list.php` sidebar drawer (hidden by default, opened via toggle button).

- [ ] **8.4.2** Create `resources/compose/_toolbar.php`:
  - **Template selector**: `<select>` styled as a dropdown. On change, HTMX POSTs to `/compose/load-template` and replaces `#editor-body` with the template HTML. Shows a confirm dialog if draft content exists (`hx-confirm`).
  - **Logo button**: opens the logo override modal. On upload/select, updates a hidden `email_logo` input.
  - **Colours button**: opens a colour panel (inline popover) with Primary and Secondary colour pickers. Values update hidden inputs.
  - **Translate button**: dropdown of supported languages. On selection, HTMX POSTs to `/compose/translate`.
  - **Save Draft button**: HTMX POSTs to `/drafts` immediately.
  - **Autosave status**: `<span id="autosave-status">` showing last saved time.
  - **Preview button**: HTMX POSTs to `/compose/preview`, response goes into the preview modal body, then opens the modal.
  - **Send button**: triggers the send confirmation modal.

- [ ] **8.4.3** Create `resources/compose/_metadata.php`:
  - To field: tag-style chip input (pure CSS + minimal JS). Accepts free-text emails and group names. Multiple entries supported.
  - Subject field: plain text input.
  - "Add CC / BCC" expand link revealing CC and BCC fields (HTMX swap or JS toggle).
  - Reply-To field (collapsed by default).

- [ ] **8.4.4** Create `resources/compose/_editor.php`:
  - A `<div contenteditable="true">` or `<textarea>` depending on template mode:
    - **Structured template mode**: renders editable content zone placeholders as labelled inputs/textareas.
    - **Free/HTML mode**: a `<textarea id="editor-body">` with basic formatting.
  - For MVP, start with the textarea approach for simplicity; the contenteditable structured mode can be a post-MVP enhancement.
  - The textarea value is submitted as `body_html` in the compose form.

- [ ] **8.4.5** Create `resources/compose/_send-modal.php`:
  - Displays a summary: recipient count, subject, sender name + email.
  - "Confirm & Send" button → HTMX POST to `/compose/send`.
  - "Cancel" button closes modal.

- [ ] **8.4.6** Create `resources/compose/_draft-list.php`:
  - Slide-in drawer from left (on mobile) or sidebar panel (on desktop).
  - Lists drafts with subject (or "No Subject"), recipient count, last saved time.
  - Clicking a draft loads it into the compose form via HTMX `hx-get="/drafts/{id}/load"`.
  - Delete button per draft.

- [ ] **8.4.7** Add route `POST /compose/load-template` to load a template's HTML into the editor.
- [ ] **8.4.8** Add route `POST /compose/preview-draft` for live preview during composition.
- [ ] **8.4.9** Implement `loadTemplate(Request $request): Response` in `ComposeController` — fetches template HTML, runs `TemplateRenderService::renderWithGlobalContext()`, returns the rendered HTML as a partial.

---

### 8.5 — Tag Input (Recipient Chips)

- [ ] **8.5.1** Implement the recipient chip input in `assets/js/app.js`:
  - Pressing comma or Enter adds the current text as a chip.
  - Each chip is a `<span>` with the email address and a remove button (×).
  - Chips are stored in a hidden `<input type="hidden" name="recipients">` as a JSON array.
  - Pasting a comma-separated list creates multiple chips at once.
  - Group names (e.g. "Clients") are accepted and resolved server-side.

---

### 8.6 — Milestone: Compose Test

- [ ] **8.6.1** Open the compose page — confirm the template selector is populated.
- [ ] **8.6.2** Select a template — confirm the editor body updates to the template HTML.
- [ ] **8.6.3** Change the primary colour — confirm the hidden colour input updates.
- [ ] **8.6.4** Add a recipient via the chip input — confirm the chip appears and the hidden input is populated.
- [ ] **8.6.5** Wait 60 seconds (or trigger manually) — confirm auto-save fires and the status indicator updates.
- [ ] **8.6.6** Click "Save Draft" — confirm the draft is saved and appears in the draft list.
- [ ] **8.6.7** Click "Preview" — confirm the preview modal opens with the rendered email.
- [ ] **8.6.8** Click "Send" — confirm the send confirmation modal appears with correct summary.
- [ ] **8.6.9** Confirm send with a real recipient — confirm success toast and email delivered.
- [ ] **8.6.10** Send with an empty subject — confirm validation error appears inline.

---

## Phase 9 — Translation

> Goal: The email body and subject can be translated to a supported language in-place, with an undo option.

---

### 9.1 — Translation Service

- [ ] **9.1.1** Create `app/Services/TranslationService.php` with the full implementation from Architecture §8.1.
- [ ] **9.1.2** Implement `translateBody(string $html, string $targetLang): string` — passes `format: 'html'` so HTML tags are preserved.
- [ ] **9.1.3** Implement `translatePlaintext(string $text, string $targetLang): string` — for the subject line.
- [ ] **9.1.4** Implement error handling: if LibreTranslate returns a non-200 response or no `translatedText` key, throw `TranslationException` with the API's error message.

---

### 9.2 — Translation Controller

- [ ] **9.2.1** Create `app/Controllers/TranslationController.php`:
  - `translate(Request $request): Response`:
    - Validate: `body` not empty, `subject` not empty, `target_lang` in supported list.
    - Call `TranslationService::translateBody()` for body.
    - Call `TranslationService::translatePlaintext()` for subject.
    - Return an HTML partial containing the translated body (for `#editor-body`) and subject (for `#subject-input`), plus:
      - A hidden `#original-body` field with the original body (for undo).
      - A hidden `#original-subject` field.
      - An "Undo Translation" button targeting `#translation-controls` that swaps the originals back in.
    - Trigger toast: "Email translated to {language}."
  - `revert(Request $request): Response`:
    - Receives original body + subject from form.
    - Returns partial swapping them back into the editor.
    - Hides the undo button.

---

### 9.3 — Translation UI

- [ ] **9.3.1** Add the Translate dropdown to `_toolbar.php` — a `<select>` of supported languages from `config/translation.php`.
- [ ] **9.3.2** Wire HTMX: `hx-post="/compose/translate"`, `hx-include="#compose-form"`, `hx-target="#editor-area"` (replaces the entire editor area with the new partial).
- [ ] **9.3.3** Add `<div id="translation-controls">` below the editor for the Undo button — hidden initially, shown after translation.

---

### 9.4 — Milestone: Translation Test

- [ ] **9.4.1** Type a body and subject in the compose form.
- [ ] **9.4.2** Select "Spanish" from the Translate dropdown — confirm the body and subject are replaced with translated text.
- [ ] **9.4.3** Confirm a success toast "Email translated to Spanish." appears.
- [ ] **9.4.4** Confirm the "Undo Translation" button appears.
- [ ] **9.4.5** Click Undo — confirm original body and subject are restored.
- [ ] **9.4.6** Confirm the Undo button disappears after reverting.
- [ ] **9.4.7** Select Arabic — confirm RTL text renders visually correctly.
- [ ] **9.4.8** Test with LibreTranslate unavailable — confirm a graceful error toast appears (no crash).

---

## Phase 10 — Email Logs

> Goal: All sent, errored, and received email activity is visible, searchable, and filterable.

---

### 10.1 — Webhook Controller

- [ ] **10.1.1** Create `app/Controllers/WebhookController.php`.
- [ ] **10.1.2** Implement `resend(Request $request): Response`:
  - Read raw body: `file_get_contents('php://input')`.
  - Validate Svix signature: compute HMAC-SHA256 of `"{timestamp}.{body}"` using `RESEND_WEBHOOK_SECRET`, compare with `Svix-Signature` header.
  - Reject with HTTP 401 on signature mismatch.
  - Decode JSON body.
  - Handle event types:
    - `email.delivered` / `email.bounced` / `email.opened` → `LogRepository::updateStatus()`.
    - `inbound.email` → insert into `received_emails`.
  - Return `HTTP 200` with empty body.

---

### 10.2 — Log Controller

- [ ] **10.2.1** Complete `app/Controllers/LogController.php`:
  - `index(Request $request): Response` — loads paginated sent logs (default), renders view.
  - `show(Request $request, int $id): Response` — loads a log entry + email body, returns the expanded detail partial.
  - `clear(Request $request): Response` — deletes all records of the requested type. Validates a `type` param (`sent|errors|received`). Returns success toast.

---

### 10.3 — Log Views

- [ ] **10.3.1** Create `resources/logs/index.php`:
  - Page heading "Email Logs".
  - Tab strip: Sent | Errors | Received. Tab switching via HTMX GET with `hx-target="#log-table"`.
  - Search/filter bar: text search (recipient, subject), date range pickers, status filter dropdown.
  - Log table area `#log-table`.
  - "Clear Logs" button (with type param, confirmation modal).

- [ ] **10.3.2** Create `resources/logs/_sent-table.php`:
  - Table columns: Timestamp, Recipients, Subject, Template, Provider, Status (badge).
  - Each row is clickable (HTMX GET to `/logs/{id}`) expanding a detail row or opening a modal.
  - Status badge colour: sent=blue, delivered=green, failed=red, bounced=orange, opened=teal.
  - Pagination at bottom.

- [ ] **10.3.3** Create `resources/logs/_error-table.php`:
  - Table columns: Timestamp, Provider, Error Code, Error Message (truncated), Recipients.
  - Expand row for full error detail.

- [ ] **10.3.4** Create `resources/logs/_received-table.php`:
  - Table columns: Sender, Subject, Received At.
  - Expand row shows message body preview.

- [ ] **10.3.5** Create `resources/logs/_log-detail.php`:
  - Full log entry detail: all metadata fields.
  - Rendered preview of the sent email HTML (in a sandboxed iframe).
  - This partial is returned by `LogController::show()` and injected as an expanded row or modal content.

- [ ] **10.3.6** Create `resources/components/cards/_log-card.php` — compact log entry for mobile card view.

---

### 10.4 — Milestone: Logs Test

- [ ] **10.4.1** Send a test email — confirm a record appears in the Sent Logs tab immediately.
- [ ] **10.4.2** Click the log row — confirm the detail view opens with the email preview.
- [ ] **10.4.3** Trigger a send failure (use a bad API key) — confirm the error appears in the Errors tab.
- [ ] **10.4.4** Configure Resend webhook pointing to `/webhooks/resend` in Resend dashboard.
- [ ] **10.4.5** Send an email and wait for the delivered webhook — confirm the log status updates to "Delivered".
- [ ] **10.4.6** Search by recipient email — confirm filtering works.
- [ ] **10.4.7** Filter by date range — confirm only matching logs appear.
- [ ] **10.4.8** Click "Clear Logs" (Sent) — confirm all sent logs are deleted after confirmation.

---

## Phase 11 — Mobile Responsiveness & Polish

> Goal: Every page is fully functional and visually complete on screens down to 375px.

---

### 11.1 — Responsive Audit

Visit every page in a 375px-width viewport and address all layout issues:

- [ ] **11.1.1** Login page — centred card fits within viewport.
- [ ] **11.1.2** Compose page — toolbar wraps or collapses into a scrollable horizontal strip. Metadata fields stack vertically. Editor is full width.
- [ ] **11.1.3** Recipients page — table becomes card-based on mobile (use CSS `@media` to switch between table and card layout), or columns collapse to essential info only.
- [ ] **11.1.4** Logs page — same table → card approach.
- [ ] **11.1.5** Settings pages — all form sections stack vertically. Color pickers expand correctly on touch.
- [ ] **11.1.6** Templates gallery — grid collapses to single column.
- [ ] **11.1.7** Bottom navigation — verify it doesn't cover page content (add `pb-16` padding to main content area on mobile).
- [ ] **11.1.8** All modals — verify they fit within mobile viewport and can be dismissed.
- [ ] **11.1.9** Confirm all touch targets meet 44×44px minimum.
- [ ] **11.1.10** Confirm the tag/chip input in the compose To field wraps gracefully.

---

### 11.2 — Tailwind CSS Compilation (Optional but Recommended)

- [ ] **11.2.1** If using CDN for development, set up the Tailwind CSS CLI for production to eliminate unused classes and reduce CSS payload.
- [ ] **11.2.2** Create `tailwind.config.js` with `content` pointing to `resources/**/*.php`.
- [ ] **11.2.3** Add build script to `package.json`: `"build:css": "tailwindcss -i ./assets/css/source.css -o ./assets/css/app.css --minify"`.
- [ ] **11.2.4** Replace CDN `<link>` in layouts with `<link href="<?= asset('css/app.css') ?>" rel="stylesheet">`.
- [ ] **11.2.5** Add compiled `assets/css/app.css` to `.gitignore` or commit it — decide based on team workflow.

---

### 11.3 — UI Polish

- [ ] **11.3.1** Verify consistent spacing, font sizes, and colour usage across all pages.
- [ ] **11.3.2** Confirm all Bootstrap Icons are rendering correctly (SVG sprite or CDN).
- [ ] **11.3.3** Verify HTMX loading indicators appear during every async request.
- [ ] **11.3.4** Confirm all empty states (no recipients, no templates, no logs) display the `_empty-state.php` component with appropriate messaging.
- [ ] **11.3.5** Verify all confirm dialogs (`hx-confirm`) work correctly before destructive actions.
- [ ] **11.3.6** Confirm focus management: after modals close, focus returns to the trigger element.
- [ ] **11.3.7** Confirm the page `<title>` updates on each page to reflect the current section.

---

## Phase 12 — Security Hardening

> Goal: The application is secure before going to production.

---

### 12.1 — Input & Output Security

- [ ] **12.1.1** Audit every view file: confirm all dynamic values are wrapped in `e()` or `htmlspecialchars()`. Use grep: `grep -rn '\$' resources/ | grep -v 'e('` as a starting point.
- [ ] **12.1.2** Confirm all SQL queries use PDO prepared statements — no string concatenation in SQL. Audit `app/Repositories/` and `app/Core/Model.php`.
- [ ] **12.1.3** Confirm all file upload paths are validated to stay within `storage/uploads/`. Audit `FileUploadService` and `StorageController`.
- [ ] **12.1.4** Confirm uploaded file extensions are validated against an allow-list AND MIME types are validated independently (not trusting the client-provided MIME).
- [ ] **12.1.5** Confirm that template HTML stored in the DB is rendered inside a sandboxed iframe for previews (not directly injected into the page DOM).
- [ ] **12.1.6** Confirm `{{...}}` tokens in template HTML cannot be exploited to inject arbitrary values (the render service only replaces known tokens, strips the rest).

---

### 12.2 — Auth & Session Security

- [ ] **12.2.1** Confirm session cookie is `HttpOnly` and `SameSite=Strict` in `config/session.php`.
- [ ] **12.2.2** Confirm `session_regenerate_id(true)` is called on login.
- [ ] **12.2.3** Confirm CSRF token validation is applied to all POST/PUT/DELETE routes.
- [ ] **12.2.4** Confirm `GuestMiddleware` is applied to `/login` route so authenticated users can't revisit it.
- [ ] **12.2.5** Confirm there is no user registration or password reset endpoint (single user — not needed in MVP; leaving one would be a vulnerability).

---

### 12.3 — Credential Security

- [ ] **12.3.1** Confirm `APP_KEY` is at least 32 bytes and base64-encoded.
- [ ] **12.3.2** Confirm `Crypto::encrypt()` uses a fresh random IV every call (IV is stored alongside the ciphertext).
- [ ] **12.3.3** Confirm `Crypto::decrypt()` validates that decrypted output is valid JSON before returning.
- [ ] **12.3.4** Confirm credential values are never written to log files — audit `Logger` calls in `CredentialService` and `CredentialController`.
- [ ] **12.3.5** Confirm the Resend webhook endpoint validates the Svix signature before processing any payload.

---

### 12.4 — Server & Hosting Security

- [ ] **12.4.1** Confirm `public/` is the document root on the hosting account.
- [ ] **12.4.2** Confirm `Options -Indexes` is set in `.htaccess` to prevent directory listing.
- [ ] **12.4.3** Add `.htaccess` to `storage/` denying all direct HTTP access (belt-and-suspenders, since it's outside the web root).
- [ ] **12.4.4** Add `.htaccess` to `app/` and `config/` denying access, in case the hosting setup incorrectly exposes them.
- [ ] **12.4.5** Confirm `APP_DEBUG=false` in production `.env`.
- [ ] **12.4.6** Confirm `error_reporting(0)` and `display_errors=Off` in production PHP config (or set via `ini_set` in `bootstrap/app.php` when debug is off).
- [ ] **12.4.7** Set `session.cookie_secure = true` if the site runs on HTTPS (it should).
- [ ] **12.4.8** Add security response headers in `public/index.php` or `public/.htaccess`:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Content-Security-Policy` (draft — may need tuning for HTMX + CDN assets).

---

## Phase 13 — Deployment to Shared Hosting

> Goal: The application runs correctly on the production shared hosting environment.

---

### 13.1 — Pre-Deployment Checklist

- [ ] **13.1.1** Verify production server PHP version is 8.3+.
- [ ] **13.1.2** Verify all required PHP extensions are available on the host.
- [ ] **13.1.3** Verify MySQL 8.x is available and a database has been created.
- [ ] **13.1.4** Verify `curl` is enabled (many shared hosts disable it or have it compiled without certain protocols).
- [ ] **13.1.5** Verify `mod_rewrite` is available and `.htaccess` overrides are allowed.
- [ ] **13.1.6** Decide on a deployment method: FTP/SFTP, Git pull, or cPanel File Manager.
- [ ] **13.1.7** Set up SSL certificate on the domain (Let's Encrypt via cPanel or hosting panel).

---

### 13.2 — Deployment Steps

- [ ] **13.2.1** Upload all project files to the server **excluding**: `.env`, `storage/logs/*`, `storage/uploads/*`, `storage/sessions/*`, `vendor/` (will be installed on server or uploaded separately).
- [ ] **13.2.2** Upload or install Composer dependencies: if server has Composer CLI, run `composer install --no-dev --optimize-autoloader`; otherwise upload the `vendor/` directory.
- [ ] **13.2.3** Point the domain's document root to the `public/` directory via the hosting control panel.
- [ ] **13.2.4** Create `.env` on the server directly (never transfer via FTP without a secure channel). Populate with production values: `APP_DEBUG=false`, production DB credentials, production `APP_KEY`, Resend API key, LibreTranslate URL.
- [ ] **13.2.5** Create the production database and run migrations: `php database/migrate.php --seed`.
- [ ] **13.2.6** Set correct permissions on `storage/`: `chmod -R 775 storage/` (or 755 depending on the server's web user).
- [ ] **13.2.7** Verify `storage/logs/`, `storage/uploads/logos/global/`, `storage/uploads/logos/email/`, `storage/uploads/templates/`, `storage/sessions/` are writable by PHP.
- [ ] **13.2.8** Visit the production domain — confirm the login page loads.
- [ ] **13.2.9** Log in and confirm all pages load without errors.
- [ ] **13.2.10** Navigate to Credentials, enter production Resend API key, test the connection.
- [ ] **13.2.11** Navigate to General Settings, upload production logo, set brand colours.
- [ ] **13.2.12** Send a test email to yourself — confirm delivery.
- [ ] **13.2.13** Configure Resend webhook URL in the Resend dashboard: `https://yourdomain.com/webhooks/resend`. Copy the signing secret into `.env` as `RESEND_WEBHOOK_SECRET`.
- [ ] **13.2.14** Trigger a test webhook from Resend dashboard — confirm the log status updates.

---

### 13.3 — Post-Deployment Smoke Test

- [ ] **13.3.1** Login flow.
- [ ] **13.3.2** Settings save and persist.
- [ ] **13.3.3** Template upload and preview.
- [ ] **13.3.4** Add a recipient manually.
- [ ] **13.3.5** Compose and send a real email.
- [ ] **13.3.6** Confirm the sent email appears in Logs.
- [ ] **13.3.7** Confirm translation works against the configured LibreTranslate instance.
- [ ] **13.3.8** Confirm error page renders (not debug page) by intentionally triggering a non-existent URL.
- [ ] **13.3.9** Check `storage/logs/app-{today}.log` for any warnings or errors from the smoke test.

---

## Phase 14 — Documentation & Handoff

> Goal: The project is documented for ongoing maintenance and future development.

---

### 14.1 — Code Documentation

- [ ] **14.1.1** Add DocBlocks to all Core framework classes (`Router`, `Request`, `Response`, `Model`, `App`, etc.) documenting parameters, return types, and notable behaviour.
- [ ] **14.1.2** Add DocBlocks to all Service classes.
- [ ] **14.1.3** Add DocBlocks to all Repository classes.
- [ ] **14.1.4** Add inline comments to any non-obvious logic: token injection regex, CSV row parsing, Svix signature validation, AES encryption.

---

### 14.2 — README

- [ ] **14.2.1** Create `README.md` at the project root covering:
  - Project description.
  - Local setup steps (clone, `composer install`, `.env` setup, migrations, seed, run).
  - Directory structure summary.
  - How to add a new route.
  - How to add a new built-in email template.
  - How to change the active email provider.
  - Deployment steps summary.
  - Known limitations (shared hosting constraints, sync-only sends, no scheduling).

---

### 14.3 — Maintenance Notes

- [ ] **14.3.1** Document log rotation policy (currently manual — logs accumulate indefinitely).
- [ ] **14.3.2** Document how to change the admin password (direct DB update + `password_hash()` call documented in README).
- [ ] **14.3.3** Document how to regenerate `APP_KEY` and how to re-encrypt stored credentials after a key rotation.
- [ ] **14.3.4** Document LibreTranslate API limits and what to do if the public endpoint is rate-limited (self-host fallback).

---

## Appendix A — Implementation Order Summary

| Phase | Description | Key Deliverable |
|---|---|---|
| 0 | Scaffolding & Framework | Running MVC; responds to HTTP |
| 1 | Database & Models | All tables, models, repositories |
| 2 | Authentication | Login/logout; route protection |
| 3 | App Shell & Navigation | Layout, sidebar, mobile nav, error pages |
| 4 | General Settings | Settings form; file uploads |
| 5 | Email Templates | Template CRUD, preview, token detection |
| 6 | Email Credentials | Provider config, encryption, test |
| 7 | Recipients | Contact management, CSV import |
| 8 | Composition | Full compose flow; drafts; send |
| 9 | Translation | LibreTranslate integration; undo |
| 10 | Logs | Sent, error, received log views |
| 11 | Mobile & Polish | Full responsive audit; UI consistency |
| 12 | Security Hardening | Audit; headers; hardening checklist |
| 13 | Deployment | Live on shared hosting |
| 14 | Documentation | README; code docs; handoff notes |

---

## Appendix B — Known Shared Hosting Constraints & Mitigations

| Constraint | Impact | Mitigation |
|---|---|---|
| No background workers | Can't queue email jobs | All sends synchronous; large group sends may time out |
| PHP `max_execution_time` (often 30–60s) | Bulk sends to many recipients may hit the limit | Batch sends server-side; or send sequentially per-recipient with a loop, checking time elapsed |
| No CLI during runtime | Can't run migrations in production via command | Run `migrate.php` locally against production DB via SSH (if available) or via cPanel PHPMyAdmin |
| No WebSockets | No real-time push | HTMX polling for auto-save; log status via webhooks |
| Shared file system | Other tenants theoretically on same disk | `storage/` permissions set to 700/750; no world-readable files |
| Limited outbound connections | Some hosts block cURL to external APIs | Verify cURL + SSL work on host before committing to Resend; SMTP fallback as alternative |

---

*End of Emirates Implementation Plan v1.0*
