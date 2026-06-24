# Emirates — Phase 1: Database & Models

**Version:** 1.0
**Phase:** 1 of 14
**Depends On:** Phase 0 complete (framework running, `BASE_PATH` defined, autoloader working)
**Goal:** All database tables exist; all models, repositories, seeders, and DTOs are implemented and working.

---

## How to Use This Document

Work through every section in order. Each section builds on the previous one. All file paths are relative to your project root `emirates/`.

**Checklist notation:**
- `[ ]` Not started
- `[x]` Complete

---

## 1.1 — Migration Runner

The migration runner is a CLI PHP script you run from your terminal. It reads SQL files from `database/migrations/`, applies them in order, and tracks which ones have already run so re-running is always safe.

- [ ] **1.1.1** Create `database/migrate.php`:

```php
<?php

declare(strict_types=1);

/**
 * database/migrate.php — CLI Migration Runner
 *
 * Run from the project root (not from inside database/):
 *
 *   php database/migrate.php           — run pending migrations only
 *   php database/migrate.php --seed    — run migrations then seeders
 *   php database/migrate.php --fresh   — drop all tables, re-run everything, then seed
 *
 * This script intentionally does NOT use the App container or any framework classes.
 * It is a standalone utility that only needs PDO and plain PHP so it works
 * even if parts of the framework are broken.
 */

// ── Verify we're being run from the CLI, not via a browser ────────────────
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// ── Set up BASE_PATH so we can include .env and config ────────────────────
define('BASE_PATH', dirname(__DIR__));

// ── Parse command-line arguments ──────────────────────────────────────────
$args  = array_slice($argv, 1); // everything after "php database/migrate.php"
$fresh = in_array('--fresh', $args, true);
$seed  = in_array('--seed', $args, true) || $fresh; // --fresh always seeds

// ── Load .env manually (we can't use EnvLoader here without the autoloader) ─
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim(trim($value), '"\'');
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// ── Connect to the database ───────────────────────────────────────────────
$host    = $_ENV['DB_HOST']    ?? 'localhost';
$port    = $_ENV['DB_PORT']    ?? '3306';
$name    = $_ENV['DB_NAME']    ?? 'emirates';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    output("❌ Database connection failed: " . $e->getMessage(), 'error');
    exit(1);
}

output("✅ Connected to database [{$name}]");

// ── --fresh: drop all tables and start clean ──────────────────────────────
if ($fresh) {
    output("\n⚠️  --fresh flag detected. Dropping all tables...", 'warning');

    // Disable foreign key checks so we can drop tables in any order
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    // Get all table names in this database
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        output("  Dropped table: {$table}");
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    output("✅ All tables dropped. Starting fresh.\n");
}

// ── Ensure the _migrations tracking table exists ──────────────────────────
// This table records which migration files have already been applied.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration`  VARCHAR(255) NOT NULL UNIQUE,
        `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// ── Get list of already-applied migrations ────────────────────────────────
$applied = $pdo->query("SELECT migration FROM `_migrations`")->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied); // flip to use isset() for O(1) lookup

// ── Get all SQL migration files, sorted by filename ───────────────────────
$migrationsDir = BASE_PATH . '/database/migrations';
$files         = glob($migrationsDir . '/*.sql');

if ($files === false || empty($files)) {
    output("No migration files found in database/migrations/", 'warning');
} else {
    sort($files); // Alphabetical order = chronological order (001_, 002_, etc.)
}

// ── Run each pending migration ────────────────────────────────────────────
$ranCount = 0;

foreach ($files as $file) {
    $filename = basename($file); // e.g. '002_create_users_table.sql'

    // Skip if this migration was already applied
    if (isset($applied[$filename])) {
        output("  ⏭  Skipped (already applied): {$filename}");
        continue;
    }

    output("\n  ▶  Running: {$filename}");

    $sql = file_get_contents($file);

    if ($sql === false || trim($sql) === '') {
        output("  ⚠️  Empty or unreadable file, skipping: {$filename}", 'warning');
        continue;
    }

    // Run inside a transaction so a failure rolls back cleanly
    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->commit();

        // Record this migration as applied
        $stmt = $pdo->prepare("INSERT INTO `_migrations` (migration) VALUES (?)");
        $stmt->execute([$filename]);

        output("  ✅ Applied: {$filename}");
        $ranCount++;

    } catch (PDOException $e) {
        $pdo->rollBack();
        output("  ❌ Failed: {$filename}", 'error');
        output("     Error: " . $e->getMessage(), 'error');
        output("\nMigration aborted. Fix the error above and re-run.", 'error');
        exit(1);
    }
}

if ($ranCount === 0) {
    output("\n✅ Database is already up to date. Nothing to migrate.");
} else {
    output("\n✅ {$ranCount} migration(s) applied successfully.");
}

// ── Run seeders if --seed or --fresh flag was passed ──────────────────────
if ($seed) {
    output("\n🌱 Running seeders...\n");

    // Load Composer autoloader so seeder classes can use model classes
    require_once BASE_PATH . '/vendor/autoload.php';

    $seedersDir = BASE_PATH . '/database/seeders';
    $seederFiles = [
        $seedersDir . '/UserSeeder.php',
        $seedersDir . '/SettingSeeder.php',
        $seedersDir . '/TemplateSeeder.php',
    ];

    foreach ($seederFiles as $seederFile) {
        if (!file_exists($seederFile)) {
            output("  ⚠️  Seeder not found: " . basename($seederFile), 'warning');
            continue;
        }

        // Each seeder file must define a class with a run(PDO $pdo): void method
        require_once $seederFile;

        $className = basename($seederFile, '.php'); // e.g. 'UserSeeder'

        if (!class_exists($className)) {
            output("  ⚠️  Class [{$className}] not found in " . basename($seederFile), 'warning');
            continue;
        }

        try {
            output("  ▶  Running: {$className}");
            $seeder = new $className();
            $seeder->run($pdo);
            output("  ✅ Done: {$className}");
        } catch (Throwable $e) {
            output("  ❌ Seeder failed [{$className}]: " . $e->getMessage(), 'error');
            exit(1);
        }
    }

    output("\n✅ All seeders completed.");
}

output("\n🎉 Migration complete.\n");
exit(0);

// ── Helper: print a coloured message to the terminal ──────────────────────
function output(string $message, string $type = 'info'): void
{
    // ANSI colour codes for terminal output
    $colours = [
        'info'    => "\033[0m",    // default (white)
        'success' => "\033[0;32m", // green
        'warning' => "\033[0;33m", // yellow
        'error'   => "\033[0;31m", // red
    ];

    $colour = $colours[$type] ?? $colours['info'];
    $reset  = "\033[0m";

    echo $colour . $message . $reset . PHP_EOL;
}
```

---

## 1.2 — Migration SQL Files

Create each file inside `database/migrations/`. The filenames start with a number prefix so they are always applied in the correct order. Every `CREATE TABLE` uses `IF NOT EXISTS` so the SQL is safe to run multiple times.

- [ ] **1.2.1** Create `database/migrations/001_create_migrations_table.sql`:

```sql
-- 001_create_migrations_table.sql
-- Tracks which migration files have been applied.
-- The migrate.php script creates this automatically,
-- but we include it here for documentation completeness.

CREATE TABLE IF NOT EXISTS `_migrations` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration`  VARCHAR(255) NOT NULL UNIQUE,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.2** Create `database/migrations/002_create_users_table.sql`:

```sql
-- 002_create_users_table.sql
-- The single application user (the business owner / operator).
-- No registration system — account is seeded during setup.

CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(100)  NOT NULL,
    `email`         VARCHAR(150)  NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)  NOT NULL,
    `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.3** Create `database/migrations/003_create_settings_table.sql`:

```sql
-- 003_create_settings_table.sql
-- Key-value store for all application-level settings.
-- Examples: site_name, primary_color, default_sender_email.

CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(100) NOT NULL UNIQUE,
    `value`      TEXT         NULL,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.4** Create `database/migrations/004_create_credentials_table.sql`:

```sql
-- 004_create_credentials_table.sql
-- Stores encrypted email provider credentials (Resend API key, SMTP config).
-- The 'config' column holds an AES-256-CBC encrypted JSON blob.
-- Only one provider can be active at a time.

CREATE TABLE IF NOT EXISTS `credentials` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider`   ENUM('resend', 'smtp') NOT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 0,
    `config`     TEXT         NOT NULL COMMENT 'AES-256-CBC encrypted JSON',
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Only one row per provider type
    UNIQUE KEY `uq_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.5** Create `database/migrations/005_create_email_templates_table.sql`:

```sql
-- 005_create_email_templates_table.sql
-- Stores all email templates — both built-in (shipped with the app)
-- and custom (uploaded or pasted by the user).
--
-- supports_logo and supports_colors are detected at upload time
-- by scanning the HTML for {{LOGO_URL}}, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}.
-- These flags drive which toolbar controls are enabled in the compose page.

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(150) NOT NULL,
    `category`        VARCHAR(80)  NULL COMMENT 'e.g. Newsletter, Transactional, Promotional',
    `html_content`    LONGTEXT     NOT NULL,
    `thumbnail_path`  VARCHAR(255) NULL COMMENT 'Relative path to preview image in storage/',
    `is_built_in`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = shipped with app, cannot be deleted',
    `supports_logo`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = contains {{LOGO_URL}} placeholder',
    `supports_colors` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = contains {{PRIMARY_COLOR}} placeholder',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.6** Create `database/migrations/006_create_recipients_table.sql`:

```sql
-- 006_create_recipients_table.sql
-- Stores saved email contacts.
-- is_suppressed = 1 means the contact has unsubscribed or been blocked.
-- Suppressed contacts are excluded from all future sends automatically.

CREATE TABLE IF NOT EXISTS `recipients` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `first_name`   VARCHAR(80)  NULL,
    `last_name`    VARCHAR(80)  NULL,
    `email`        VARCHAR(150) NOT NULL UNIQUE,
    `company`      VARCHAR(150) NULL,
    `notes`        TEXT         NULL,
    `is_suppressed` TINYINT(1)  NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_email`        (`email`),
    INDEX `idx_suppressed`   (`is_suppressed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.7** Create `database/migrations/007_create_recipient_groups_table.sql`:

```sql
-- 007_create_recipient_groups_table.sql
-- Groups (tags) for organising recipients.
-- Example group names: 'Clients', 'Newsletter', 'VIPs'.
-- In the compose page, you can type a group name in the To field
-- and all non-suppressed members will be resolved as recipients.

CREATE TABLE IF NOT EXISTS `recipient_groups` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.8** Create `database/migrations/008_create_recipient_group_pivot_table.sql`:

```sql
-- 008_create_recipient_group_pivot_table.sql
-- Many-to-many relationship between recipients and groups.
-- One recipient can belong to multiple groups.
-- One group can have many recipients.

CREATE TABLE IF NOT EXISTS `recipient_group_pivot` (
    `recipient_id` INT UNSIGNED NOT NULL,
    `group_id`     INT UNSIGNED NOT NULL,

    PRIMARY KEY (`recipient_id`, `group_id`),

    FOREIGN KEY (`recipient_id`)
        REFERENCES `recipients` (`id`)
        ON DELETE CASCADE,  -- if the recipient is deleted, remove from all groups

    FOREIGN KEY (`group_id`)
        REFERENCES `recipient_groups` (`id`)
        ON DELETE CASCADE   -- if the group is deleted, remove all pivot rows
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.9** Create `database/migrations/009_create_email_drafts_table.sql`:

```sql
-- 009_create_email_drafts_table.sql
-- Stores in-progress email compositions (drafts).
-- Drafts are auto-saved every 60 seconds and manually saved via "Save Draft".
-- All draft state is stored here so it survives page refreshes and browser closes.

CREATE TABLE IF NOT EXISTS `email_drafts` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject`            VARCHAR(255) NULL,
    `recipients_json`    TEXT         NULL COMMENT 'JSON array of email addresses/group names',
    `body_html`          LONGTEXT     NULL,
    `template_id`        INT UNSIGNED NULL COMMENT 'The template used (if any)',
    `logo_override_path` VARCHAR(255) NULL COMMENT 'Relative path to per-email logo override',
    `primary_color`      VARCHAR(7)   NULL COMMENT 'Hex color e.g. #4F46E5',
    `secondary_color`    VARCHAR(7)   NULL COMMENT 'Hex color e.g. #10B981',
    `language`           VARCHAR(10)  NULL COMMENT 'BCP 47 language code e.g. en, es, fr',
    `last_auto_saved_at` TIMESTAMP    NULL,
    `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`template_id`)
        REFERENCES `email_templates` (`id`)
        ON DELETE SET NULL  -- if template deleted, draft keeps its body_html but loses the reference
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.10** Create `database/migrations/010_create_email_logs_table.sql`:

```sql
-- 010_create_email_logs_table.sql
-- Records every email send attempt and inbound email received.
-- Status is updated by Resend webhooks (delivered, bounced, opened).

CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `direction`        ENUM('sent', 'received') NOT NULL DEFAULT 'sent',
    `recipients_json`  TEXT         NULL COMMENT 'JSON array of recipient addresses',
    `subject`          VARCHAR(255) NULL,
    `body_html`        LONGTEXT     NULL COMMENT 'The final rendered HTML that was sent',
    `template_id`      INT UNSIGNED NULL,
    `provider`         ENUM('resend', 'smtp') NULL,
    `provider_msg_id`  VARCHAR(255) NULL COMMENT 'Message ID returned by the provider (for webhook lookup)',
    `status`           ENUM('queued', 'sent', 'delivered', 'failed', 'bounced', 'opened')
                       NOT NULL DEFAULT 'sent',
    `sent_at`          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_provider_msg_id` (`provider_msg_id`),
    INDEX `idx_status`          (`status`),
    INDEX `idx_direction`       (`direction`),

    FOREIGN KEY (`template_id`)
        REFERENCES `email_templates` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.11** Create `database/migrations/011_create_email_error_logs_table.sql`:

```sql
-- 011_create_email_error_logs_table.sql
-- Records failed email send attempts with error details.
-- Shown in the "Errors" tab of the Email Logs page.

CREATE TABLE IF NOT EXISTS `email_error_logs` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `log_id`          INT UNSIGNED NULL COMMENT 'Links to email_logs if a log entry was created before the failure',
    `error_code`      VARCHAR(50)  NULL COMMENT 'HTTP status code or provider error code',
    `error_message`   TEXT         NULL COMMENT 'Full error message from the provider',
    `recipients_json` TEXT         NULL COMMENT 'JSON array of intended recipients',
    `provider`        ENUM('resend', 'smtp') NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`log_id`)
        REFERENCES `email_logs` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.12** Create `database/migrations/012_create_received_emails_table.sql`:

```sql
-- 012_create_received_emails_table.sql
-- Stores inbound emails received via Resend's inbound routing feature.
-- This is a conditional MVP feature — only active if inbound routing is configured.

CREATE TABLE IF NOT EXISTS `received_emails` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sender`      VARCHAR(150) NULL COMMENT 'The From address of the inbound email',
    `subject`     VARCHAR(255) NULL,
    `body_text`   LONGTEXT     NULL COMMENT 'Plain-text version of the email body',
    `body_html`   LONGTEXT     NULL COMMENT 'HTML version of the email body',
    `received_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **1.2.13** Run the migrations and confirm all tables are created:
  ```bash
  php database/migrate.php
  ```
  Expected output: 12 migration files applied, ending with "🎉 Migration complete."

- [ ] **1.2.14** Run again to confirm idempotency:
  ```bash
  php database/migrate.php
  ```
  Expected output: All 12 files show "⏭ Skipped (already applied)". No errors.

---

## 1.3 — Model Classes

Each model extends `App\Core\Model` (built in Phase 0). Models declare `$table`, `$fillable`, and add any domain-specific methods on top of the inherited CRUD operations.

- [ ] **1.3.1** Create `app/Models/User.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * User
 *
 * The single application user (business owner / operator).
 * There is only ever one row in this table.
 *
 * Usage:
 *   $user = User::find(1);
 *   $user = User::findBy('email', 'admin@example.com');
 *   $user->verifyPassword('mypassword');
 */
class User extends Model
{
    protected static string $table = 'users';

    // Only these columns can be set via create() or update()
    protected array $fillable = ['name', 'email', 'password_hash'];

    // ─── Type-hinted property accessors ───────────────────────────────────
    // These are documentation helpers — PHP reads the actual values
    // from $this->attributes via the __get magic method in Model.

    // public int    $id;
    // public string $name;
    // public string $email;
    // public string $password_hash;
    // public string $created_at;

    // ─── Domain methods ───────────────────────────────────────────────────

    /**
     * Verify that a plaintext password matches this user's stored hash.
     *
     * Usage:
     *   if ($user->verifyPassword($request->post('password'))) { ... }
     */
    public function verifyPassword(string $plaintext): bool
    {
        return password_verify($plaintext, (string)$this->password_hash);
    }

    /**
     * Get the user's display name, falling back to their email.
     */
    public function displayName(): string
    {
        return (string)($this->name ?: $this->email);
    }
}
```

- [ ] **1.3.2** Create `app/Models/Setting.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Setting
 *
 * Key-value store for application-level settings.
 * Each row has a unique 'key' and a 'value'.
 *
 * Usage:
 *   Setting::getValue('primary_color', '#4F46E5')
 *   Setting::setValue('primary_color', '#EC4899')
 *
 * Known setting keys:
 *   site_name, site_url, site_logo_path, default_sender_name,
 *   default_sender_email, email_logo_path, primary_color,
 *   secondary_color, default_language, timezone
 */
class Setting extends Model
{
    protected static string $table = 'settings';

    protected array $fillable = ['key', 'value'];

    // ─── Static convenience methods ────────────────────────────────────────

    /**
     * Get a setting value by key.
     * Returns $default if the key does not exist in the database.
     *
     * Usage:
     *   Setting::getValue('primary_color')           => '#4F46E5' or null
     *   Setting::getValue('primary_color', '#4F46E5') => '#4F46E5' if not set
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::findBy('key', $key);
        return $row ? $row->value : $default;
    }

    /**
     * Create or update a setting value.
     * If the key already exists, its value is updated.
     * If it doesn't exist, a new row is inserted.
     *
     * This is called an "upsert" (UPDATE + INSERT).
     *
     * Usage:
     *   Setting::setValue('primary_color', '#EC4899')
     */
    public static function setValue(string $key, mixed $value): void
    {
        // INSERT ... ON DUPLICATE KEY UPDATE handles both insert and update in one query
        // This works because 'key' has a UNIQUE constraint in the database
        $stmt = static::db()->prepare("
            INSERT INTO `settings` (`key`, `value`)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
        ");

        $stmt->execute([$key, $value]);
    }
}
```

- [ ] **1.3.3** Create `app/Models/Credential.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\Crypto;

/**
 * Credential
 *
 * Stores encrypted email provider credentials.
 * The 'config' column contains an AES-256-CBC encrypted JSON string.
 * Never read 'config' directly — always use decryptedConfig().
 *
 * Usage:
 *   $credential = Credential::findBy('provider', 'resend');
 *   $config     = $credential->decryptedConfig();
 *   $apiKey     = $config['api_key'];
 */
class Credential extends Model
{
    protected static string $table = 'credentials';

    protected array $fillable = ['provider', 'is_active', 'config'];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decrypt the stored config JSON and return it as an associative array.
     *
     * The stored value looks like: "base64encodedciphertext..."
     * After decryption it becomes: {"api_key": "re_abc123...", ...}
     *
     * Usage:
     *   $config = $credential->decryptedConfig();
     *   $apiKey = $config['api_key'];  // for Resend
     *   $host   = $config['host'];     // for SMTP
     */
    public function decryptedConfig(): array
    {
        if (empty($this->config)) {
            return [];
        }

        try {
            $json = Crypto::decrypt((string)$this->config);
            $data = json_decode($json, associative: true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            // Log the error but don't expose it — return empty array as safe fallback
            logger()->error('Failed to decrypt credential config', [
                'provider' => $this->provider,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if this credential is the currently active provider.
     */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }
}
```

- [ ] **1.3.4** Create `app/Models/EmailTemplate.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailTemplate
 *
 * Represents a stored email template (built-in or custom).
 *
 * supports_logo and supports_colors are boolean flags detected at upload
 * by scanning the HTML for {{LOGO_URL}} and {{PRIMARY_COLOR}} tokens.
 * They control which toolbar overrides are available in the compose page.
 *
 * Usage:
 *   $template = EmailTemplate::find(1);
 *   echo $template->name;
 *   if ($template->supportsLogo()) { ... }
 *   if ($template->isBuiltIn()) { ... }
 */
class EmailTemplate extends Model
{
    protected static string $table = 'email_templates';

    protected array $fillable = [
        'name',
        'category',
        'html_content',
        'thumbnail_path',
        'is_built_in',
        'supports_logo',
        'supports_colors',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Check if this template supports logo injection.
     * True means the HTML contains {{LOGO_URL}}.
     */
    public function supportsLogo(): bool
    {
        return (bool)$this->supports_logo;
    }

    /**
     * Check if this template supports colour injection.
     * True means the HTML contains {{PRIMARY_COLOR}}.
     */
    public function supportsColors(): bool
    {
        return (bool)$this->supports_colors;
    }

    /**
     * Check if this is a built-in template (shipped with the app).
     * Built-in templates cannot be deleted — only duplicated.
     */
    public function isBuiltIn(): bool
    {
        return (bool)$this->is_built_in;
    }
}
```

- [ ] **1.3.5** Create `app/Models/Recipient.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Recipient
 *
 * A saved email contact.
 *
 * Usage:
 *   $recipient = Recipient::findBy('email', 'alice@example.com');
 *   echo $recipient->fullName();  // "Alice Smith"
 *   if ($recipient->isSuppressed()) { ... }
 */
class Recipient extends Model
{
    protected static string $table = 'recipients';

    protected array $fillable = [
        'first_name',
        'last_name',
        'email',
        'company',
        'notes',
        'is_suppressed',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Get the recipient's full name.
     * Falls back to email address if no name is stored.
     *
     * Usage:
     *   echo $recipient->fullName();  // "Alice Smith" or "alice@example.com"
     */
    public function fullName(): string
    {
        $parts = array_filter([(string)$this->first_name, (string)$this->last_name]);
        return implode(' ', $parts) ?: (string)$this->email;
    }

    /**
     * Check if this recipient has been suppressed (unsubscribed or blocked).
     * Suppressed recipients must not receive any further emails.
     */
    public function isSuppressed(): bool
    {
        return (bool)$this->is_suppressed;
    }
}
```

- [ ] **1.3.6** Create `app/Models/RecipientGroup.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * RecipientGroup
 *
 * A named group (tag) that recipients can belong to.
 * Example groups: 'Clients', 'Newsletter Subscribers', 'VIPs'.
 *
 * Usage:
 *   $group   = RecipientGroup::findBy('name', 'Clients');
 *   $members = $group->members(); // array of Recipient instances
 */
class RecipientGroup extends Model
{
    protected static string $table = 'recipient_groups';

    protected array $fillable = ['name'];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Get all active (non-suppressed) recipients that belong to this group.
     *
     * Joins recipient_group_pivot → recipients and filters out suppressed contacts.
     *
     * Usage:
     *   $members = $group->members();
     *   foreach ($members as $recipient) {
     *       echo $recipient->email;
     *   }
     */
    public function members(): array
    {
        if (!$this->id) {
            return [];
        }

        return Recipient::raw(
            "SELECT r.*
             FROM recipients r
             INNER JOIN recipient_group_pivot p ON p.recipient_id = r.id
             WHERE p.group_id = ?
               AND r.is_suppressed = 0
             ORDER BY r.first_name ASC",
            [(int)$this->id]
        );
    }

    /**
     * Get the number of active members in this group.
     */
    public function memberCount(): int
    {
        if (!$this->id) {
            return 0;
        }

        $stmt = static::db()->prepare(
            "SELECT COUNT(*)
             FROM recipient_group_pivot p
             INNER JOIN recipients r ON r.id = p.recipient_id
             WHERE p.group_id = ? AND r.is_suppressed = 0"
        );
        $stmt->execute([(int)$this->id]);
        return (int)$stmt->fetchColumn();
    }
}
```

- [ ] **1.3.7** Create `app/Models/EmailDraft.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailDraft
 *
 * An in-progress email composition saved by the user.
 * Drafts are auto-saved every 60 seconds and manually saved via "Save Draft".
 *
 * Usage:
 *   $draft      = EmailDraft::find(5);
 *   $recipients = $draft->recipientsArray(); // ['alice@example.com', 'Clients']
 */
class EmailDraft extends Model
{
    protected static string $table = 'email_drafts';

    protected array $fillable = [
        'subject',
        'recipients_json',
        'body_html',
        'template_id',
        'logo_override_path',
        'primary_color',
        'secondary_color',
        'language',
        'last_auto_saved_at',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
     *
     * The recipients_json column stores something like:
     *   '["alice@example.com", "bob@example.com", "Clients"]'
     *
     * Where email addresses are literal, and group names are resolved
     * server-side at send time via RecipientGroup::members().
     *
     * Returns an empty array if the column is null or invalid JSON.
     */
    public function recipientsArray(): array
    {
        if (empty($this->recipients_json)) {
            return [];
        }

        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a short display label for the draft (used in the draft list).
     * Falls back to "(No Subject)" if the subject is empty.
     */
    public function displaySubject(): string
    {
        return trim((string)$this->subject) ?: '(No Subject)';
    }

    /**
     * Get the number of recipients as a human-readable string.
     */
    public function recipientSummary(): string
    {
        $count = count($this->recipientsArray());
        return $count === 1 ? '1 recipient' : "{$count} recipients";
    }
}
```

- [ ] **1.3.8** Create `app/Models/EmailLog.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\Date;

/**
 * EmailLog
 *
 * Records every email send attempt and inbound email received.
 * Status is updated by webhook events from Resend.
 *
 * Usage:
 *   $log        = EmailLog::find(10);
 *   $recipients = $log->recipientsArray();
 *   echo $log->statusBadgeClass(); // CSS class for the status badge
 */
class EmailLog extends Model
{
    protected static string $table = 'email_logs';

    protected array $fillable = [
        'direction',
        'recipients_json',
        'subject',
        'body_html',
        'template_id',
        'provider',
        'provider_msg_id',
        'status',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
     */
    public function recipientsArray(): array
    {
        if (empty($this->recipients_json)) {
            return [];
        }

        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a Tailwind CSS colour class for the status badge in the logs view.
     *
     * Usage in views:
     *   <span class="badge <?= e($log->statusBadgeClass()) ?>">
     *       <?= e($log->status) ?>
     *   </span>
     */
    public function statusBadgeClass(): string
    {
        return match ((string)$this->status) {
            'sent'      => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'opened'    => 'bg-teal-100 text-teal-800',
            'failed'    => 'bg-red-100 text-red-800',
            'bounced'   => 'bg-orange-100 text-orange-800',
            'queued'    => 'bg-gray-100 text-gray-800',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    /**
     * Get a human-readable time since this email was sent.
     * e.g. "2 hours ago", "just now"
     */
    public function sentAgo(): string
    {
        return Date::diffForHumans((string)$this->sent_at);
    }

    /**
     * Check if this log entry is for a sent (outbound) email.
     */
    public function isSent(): bool
    {
        return $this->direction === 'sent';
    }

    /**
     * Check if this log entry is for a received (inbound) email.
     */
    public function isReceived(): bool
    {
        return $this->direction === 'received';
    }
}
```

- [ ] **1.3.9** Create `app/Models/EmailErrorLog.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailErrorLog
 *
 * Records failed email send attempts with provider error details.
 * Shown in the "Errors" tab of the Email Logs page.
 *
 * Usage:
 *   $errors = EmailErrorLog::where(['provider' => 'resend'], 'created_at', 'DESC');
 */
class EmailErrorLog extends Model
{
    protected static string $table = 'email_error_logs';

    protected array $fillable = [
        'log_id',
        'error_code',
        'error_message',
        'recipients_json',
        'provider',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
     */
    public function recipientsArray(): array
    {
        if (empty($this->recipients_json)) {
            return [];
        }

        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a short summary of the error for display in the table.
     * Truncates long error messages.
     */
    public function shortMessage(): string
    {
        $msg = (string)$this->error_message;
        return mb_strlen($msg) > 100 ? mb_substr($msg, 0, 97) . '...' : $msg;
    }
}
```

---

## 1.4 — Repository Interfaces

Interfaces define the contract — what methods a repository must have. The implementations (in section 1.5) fulfil those contracts.

- [ ] **1.4.1** Create `app/Interfaces/RepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * RepositoryInterface
 *
 * Base contract for all repositories.
 * Defines the standard CRUD operations every repository must support.
 */
interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     * Returns null if not found.
     */
    public function find(int $id): ?object;

    /**
     * Get all records.
     */
    public function all(): array;

    /**
     * Create a new record with the given data.
     * Returns the created model instance.
     */
    public function create(array $data): object;

    /**
     * Update a record by its primary key.
     * Returns true on success, false on failure.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record by its primary key.
     * Returns true on success, false on failure.
     */
    public function delete(int $id): bool;
}
```

- [ ] **1.4.2** Create `app/Repositories/Contracts/SettingRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * SettingRepositoryInterface
 *
 * Contract for reading and writing application settings
 * stored in the settings table as key-value pairs.
 */
interface SettingRepositoryInterface
{
    /**
     * Get a setting value by key.
     * Returns $default if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Create or update a setting value.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get all settings as a flat key => value array.
     * Example: ['site_name' => 'Emirates', 'primary_color' => '#4F46E5', ...]
     */
    public function allAsKeyValue(): array;
}
```

- [ ] **1.4.3** Create `app/Repositories/Contracts/TemplateRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\EmailTemplate;

/**
 * TemplateRepositoryInterface
 *
 * Contract for email template data access.
 */
interface TemplateRepositoryInterface
{
    /**
     * Get all built-in templates (is_built_in = 1).
     */
    public function findBuiltIn(): array;

    /**
     * Get all custom templates (is_built_in = 0).
     */
    public function findCustom(): array;

    /**
     * Duplicate an existing template.
     * Creates a copy with " (Copy)" appended to the name.
     * The copy is always custom (is_built_in = 0).
     * Returns the new template instance.
     */
    public function duplicate(int $id): EmailTemplate;
}
```

- [ ] **1.4.4** Create `app/Repositories/Contracts/DraftRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\EmailDraft;

/**
 * DraftRepositoryInterface
 *
 * Contract for email draft data access.
 */
interface DraftRepositoryInterface
{
    /**
     * Get the most recently updated drafts (for the draft list panel).
     * Ordered by updated_at descending.
     */
    public function findLatest(int $limit = 20): array;

    /**
     * Create or update a draft (used by both autosave and manual save).
     *
     * If $data contains an 'id' key, updates the existing draft.
     * If no 'id' is present, creates a new draft.
     *
     * Returns the saved/updated EmailDraft instance.
     */
    public function upsertAutosave(array $data): EmailDraft;
}
```

- [ ] **1.4.5** Create `app/Repositories/Contracts/RecipientRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Recipient;

/**
 * RecipientRepositoryInterface
 *
 * Contract for recipient contact data access.
 */
interface RecipientRepositoryInterface
{
    /**
     * Search recipients by name, email, or company.
     * Returns matching Recipient instances.
     */
    public function search(string $query): array;

    /**
     * Find a recipient by their email address.
     * Returns null if not found.
     */
    public function findByEmail(string $email): ?Recipient;

    /**
     * Get all active (non-suppressed) recipients in a named group.
     */
    public function findByGroup(string $groupName): array;

    /**
     * Bulk-insert an array of recipient data records.
     * Skips rows where the email already exists (INSERT IGNORE).
     *
     * Each record in $records should be:
     *   ['first_name' => ..., 'last_name' => ..., 'email' => ..., ...]
     *
     * Returns the number of newly inserted rows.
     */
    public function bulkInsert(array $records): int;

    /**
     * Mark a recipient as suppressed (unsubscribed).
     * Returns true on success.
     */
    public function suppress(int $id): bool;
}
```

- [ ] **1.4.6** Create `app/Repositories/Contracts/LogRepositoryInterface.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * LogRepositoryInterface
 *
 * Contract for email log data access.
 */
interface LogRepositoryInterface
{
    /**
     * Get a paginated list of log entries.
     *
     * @param int    $page    Current page number (1-indexed)
     * @param string $type    One of: 'sent', 'error', 'received'
     * @param array  $filters Optional: ['subject' => ..., 'status' => ..., 'date_from' => ..., 'date_to' => ...]
     *
     * Returns:
     *   ['data' => [...], 'total' => n, 'page' => n, 'per_page' => n, 'last_page' => n]
     */
    public function paginate(int $page, string $type, array $filters = []): array;

    /**
     * Update the status of an email log entry by its provider message ID.
     * Called when a webhook is received from Resend.
     *
     * Returns true if a row was updated, false if no matching log was found.
     */
    public function updateStatus(string $providerMsgId, string $status): bool;

    /**
     * Delete all log entries of a given type.
     *
     * @param string $type One of: 'sent', 'error', 'received'
     *
     * Returns the number of rows deleted.
     */
    public function clearAll(string $type): int;
}
```

---

## 1.5 — Repository Implementations

- [ ] **1.5.1** Create `app/Repositories/SettingRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;

/**
 * SettingRepository
 *
 * Reads and writes application settings from the settings table.
 *
 * Caches all settings in a static property for the request lifetime
 * so we don't hit the database every time setting() is called in a view.
 */
class SettingRepository implements SettingRepositoryInterface
{
    /**
     * In-memory cache of all settings for the current request.
     * Format: ['key' => 'value', 'key2' => 'value2', ...]
     *
     * Static so the cache persists across multiple instances of this class.
     */
    private static ?array $cache = null;

    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->allAsKeyValue();
        return $all[$key] ?? $default;
    }

    /**
     * Create or update a setting value.
     * Also invalidates the in-memory cache so the next get() reads fresh data.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::setValue($key, $value);

        // Clear the cache so the next call to allAsKeyValue() re-reads from DB
        static::$cache = null;
    }

    /**
     * Get all settings as a flat key => value array.
     *
     * Results are cached in a static property for the duration of the request.
     * The cache is cleared by set() so changes are always visible immediately.
     */
    public function allAsKeyValue(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        // Load all rows from the settings table
        $rows = Setting::all('key', 'ASC');

        // Build the key => value map
        $result = [];
        foreach ($rows as $setting) {
            $result[(string)$setting->key] = $setting->value;
        }

        static::$cache = $result;
        return $result;
    }

    // ─── RepositoryInterface stubs ────────────────────────────────────────
    // Settings don't use the standard CRUD interface, but we implement
    // basic stubs so this class is consistent with the rest of the codebase.

    public function find(int $id): ?object
    {
        return Setting::find($id);
    }

    public function all(): array
    {
        return Setting::all();
    }

    public function create(array $data): object
    {
        return Setting::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $setting = Setting::find($id);
        return $setting ? $setting->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $setting = Setting::find($id);
        return $setting ? $setting->delete() : false;
    }

    /**
     * Clear the in-memory cache.
     * Useful in tests or when settings are bulk-updated.
     */
    public static function clearCache(): void
    {
        static::$cache = null;
    }
}
```

- [ ] **1.5.2** Create `app/Repositories/TemplateRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailTemplate;
use App\Repositories\Contracts\TemplateRepositoryInterface;
use App\Exceptions\NotFoundException;

/**
 * TemplateRepository
 *
 * Data access for email templates.
 */
class TemplateRepository implements TemplateRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get all built-in templates, ordered by name.
     */
    public function findBuiltIn(): array
    {
        return EmailTemplate::where(['is_built_in' => 1], 'name', 'ASC');
    }

    /**
     * Get all custom (user-uploaded) templates, most recently updated first.
     */
    public function findCustom(): array
    {
        return EmailTemplate::where(['is_built_in' => 0], 'updated_at', 'DESC');
    }

    /**
     * Duplicate an existing template.
     *
     * The copy:
     *   - Has " (Copy)" appended to the name
     *   - Is always custom (is_built_in = 0)
     *   - Has the same HTML, category, supports_logo, supports_colors
     *   - Gets a fresh id, created_at, and updated_at
     */
    public function duplicate(int $id): EmailTemplate
    {
        $original = EmailTemplate::find($id);

        if (!$original) {
            throw new NotFoundException("Template #{$id} not found.");
        }

        // Create a new template copying all relevant fields from the original
        $copy = EmailTemplate::create([
            'name'            => $original->name . ' (Copy)',
            'category'        => $original->category,
            'html_content'    => $original->html_content,
            'thumbnail_path'  => null,          // No thumbnail for the copy initially
            'is_built_in'     => 0,             // Copies are always custom
            'supports_logo'   => $original->supports_logo,
            'supports_colors' => $original->supports_colors,
        ]);

        return $copy;
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailTemplate::find($id);
    }

    public function all(): array
    {
        return EmailTemplate::all('name', 'ASC');
    }

    public function create(array $data): object
    {
        return EmailTemplate::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $template = EmailTemplate::find($id);
        return $template ? $template->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $template = EmailTemplate::find($id);
        return $template ? $template->delete() : false;
    }
}
```

- [ ] **1.5.3** Create `app/Repositories/DraftRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailDraft;
use App\Repositories\Contracts\DraftRepositoryInterface;
use App\Helpers\Date;

/**
 * DraftRepository
 *
 * Data access for email drafts.
 */
class DraftRepository implements DraftRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get the most recently updated drafts.
     * Shown in the draft list panel in the compose page.
     */
    public function findLatest(int $limit = 20): array
    {
        return EmailDraft::where([], 'updated_at', 'DESC', $limit);
    }

    /**
     * Create or update a draft (upsert).
     *
     * If $data['id'] is set and the draft exists → UPDATE
     * Otherwise → INSERT a new draft
     *
     * This is called by both manual "Save Draft" and the 60-second autosave.
     */
    public function upsertAutosave(array $data): EmailDraft
    {
        // Encode the recipients array to JSON if it's passed as an array
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $data['recipients_json'] = json_encode($data['recipients']);
            unset($data['recipients']);
        }

        // Check if we're updating an existing draft
        if (!empty($data['id'])) {
            $draftId = (int)$data['id'];
            $draft   = EmailDraft::find($draftId);

            if ($draft) {
                // Update the autosave timestamp and other fields
                $data['last_auto_saved_at'] = Date::now();
                unset($data['id']); // Don't try to update the primary key

                $draft->update($data);
                return $draft;
            }
        }

        // No existing draft found — create a new one
        unset($data['id']); // Remove id so INSERT generates a new one

        $data['last_auto_saved_at'] = Date::now();
        return EmailDraft::create($data);
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailDraft::find($id);
    }

    public function all(): array
    {
        return EmailDraft::all('updated_at', 'DESC');
    }

    public function create(array $data): object
    {
        return EmailDraft::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $draft = EmailDraft::find($id);
        return $draft ? $draft->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $draft = EmailDraft::find($id);
        return $draft ? $draft->delete() : false;
    }
}
```

- [ ] **1.5.4** Create `app/Repositories/RecipientRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Recipient;
use App\Models\RecipientGroup;
use App\Core\Database;
use App\Repositories\Contracts\RecipientRepositoryInterface;

/**
 * RecipientRepository
 *
 * Data access for recipient contacts and groups.
 */
class RecipientRepository implements RecipientRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Search recipients by name, email, or company.
     *
     * Uses LIKE with a % wildcard on each field.
     * Only returns non-suppressed contacts.
     *
     * Usage:
     *   $results = $repo->search('alice');
     *   // Returns recipients where first_name, last_name, email, or company contains 'alice'
     */
    public function search(string $query): array
    {
        // Wrap in % for partial matching: 'alice' becomes '%alice%'
        $like = '%' . $query . '%';

        return Recipient::raw(
            "SELECT * FROM recipients
             WHERE is_suppressed = 0
               AND (
                   first_name LIKE ?
                   OR last_name  LIKE ?
                   OR email      LIKE ?
                   OR company    LIKE ?
               )
             ORDER BY first_name ASC, last_name ASC",
            [$like, $like, $like, $like]
        );
    }

    /**
     * Find a recipient by their email address.
     */
    public function findByEmail(string $email): ?Recipient
    {
        return Recipient::findBy('email', $email);
    }

    /**
     * Get all active recipients belonging to a named group.
     *
     * Used at send time to resolve group names entered in the To field.
     */
    public function findByGroup(string $groupName): array
    {
        $group = RecipientGroup::findBy('name', $groupName);

        if (!$group) {
            return [];
        }

        return $group->members();
    }

    /**
     * Bulk-insert an array of recipient records efficiently.
     *
     * Skips rows where the email already exists (INSERT IGNORE prevents duplicates).
     *
     * Uses a single SQL statement with multiple value tuples instead of
     * running one INSERT per row — much faster for large CSV imports.
     *
     * @param array $records Array of ['first_name' => ..., 'email' => ..., ...] arrays
     * @return int Number of newly inserted rows
     */
    public function bulkInsert(array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $pdo = Database::getInstance()->getConnection();

        // Build placeholders: (?, ?, ?, ?, ?) for each record
        $placeholders = [];
        $values       = [];

        foreach ($records as $record) {
            // Ensure each record has all expected columns (use null for missing ones)
            $placeholders[] = '(?, ?, ?, ?, ?)';
            $values[]       = $record['first_name'] ?? null;
            $values[]       = $record['last_name']  ?? null;
            $values[]       = trim($record['email'] ?? '');
            $values[]       = $record['company']    ?? null;
            $values[]       = $record['notes']      ?? null;
        }

        // INSERT IGNORE skips rows that would violate the UNIQUE constraint on 'email'
        $sql = "INSERT IGNORE INTO recipients (first_name, last_name, email, company, notes)
                VALUES " . implode(', ', $placeholders);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // rowCount() returns only the rows that were actually inserted (not the skipped ones)
        return $stmt->rowCount();
    }

    /**
     * Mark a recipient as suppressed so they receive no further emails.
     */
    public function suppress(int $id): bool
    {
        $recipient = Recipient::find($id);
        if (!$recipient) {
            return false;
        }

        return $recipient->update(['is_suppressed' => 1]);
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return Recipient::find($id);
    }

    public function all(): array
    {
        return Recipient::where(['is_suppressed' => 0], 'first_name', 'ASC');
    }

    public function create(array $data): object
    {
        return Recipient::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $recipient = Recipient::find($id);
        return $recipient ? $recipient->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $recipient = Recipient::find($id);
        return $recipient ? $recipient->delete() : false;
    }

    // ─── Pagination ───────────────────────────────────────────────────────

    /**
     * Get a paginated list of recipients with optional search.
     *
     * @param int    $page    Current page (1-indexed)
     * @param int    $perPage Records per page
     * @param string $search  Optional search string
     */
    public function paginate(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        if ($search !== '') {
            // Search doesn't support pagination in the base model, so we handle it here
            $all     = $this->search($search);
            $total   = count($all);
            $offset  = ($page - 1) * $perPage;
            $data    = array_slice($all, $offset, $perPage);

            return [
                'data'      => $data,
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => (int)ceil($total / $perPage),
            ];
        }

        return Recipient::paginate($perPage, $page, ['is_suppressed' => 0]);
    }
}
```

- [ ] **1.5.5** Create `app/Repositories/LogRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailLog;
use App\Models\EmailErrorLog;
use App\Core\Database;
use App\Repositories\Contracts\LogRepositoryInterface;

/**
 * LogRepository
 *
 * Data access for email logs (sent, errors, received).
 */
class LogRepository implements LogRepositoryInterface
{
    private const PER_PAGE = 25;

    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get paginated log entries with optional filters.
     *
     * $type determines which table/dataset to query:
     *   'sent'     → email_logs WHERE direction = 'sent'
     *   'received' → email_logs WHERE direction = 'received'
     *   'error'    → email_error_logs
     *
     * $filters can include:
     *   'subject'   → partial match on subject
     *   'status'    → exact match on status (sent/delivered/etc.)
     *   'date_from' → records on or after this date (Y-m-d)
     *   'date_to'   → records on or before this date (Y-m-d)
     */
    public function paginate(int $page, string $type, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = static::PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        if ($type === 'error') {
            return $this->paginateErrors($page, $perPage, $offset, $filters);
        }

        return $this->paginateSentOrReceived($type, $page, $perPage, $offset, $filters);
    }

    /**
     * Update the delivery status of a sent email log.
     * Called when Resend sends a webhook (delivered, bounced, opened).
     */
    public function updateStatus(string $providerMsgId, string $status): bool
    {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "UPDATE email_logs SET status = ? WHERE provider_msg_id = ?"
        );
        $stmt->execute([$status, $providerMsgId]);

        // Returns true if exactly one row was updated
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all log entries of a given type.
     */
    public function clearAll(string $type): int
    {
        $pdo = Database::getInstance()->getConnection();

        if ($type === 'error') {
            $stmt = $pdo->query("DELETE FROM email_error_logs");
        } elseif ($type === 'received') {
            $stmt = $pdo->prepare("DELETE FROM email_logs WHERE direction = 'received'");
            $stmt->execute();
        } else {
            // 'sent' — delete sent direction logs
            $stmt = $pdo->prepare("DELETE FROM email_logs WHERE direction = 'sent'");
            $stmt->execute();
        }

        return $stmt->rowCount();
    }

    // ─── Standard CRUD (for email_logs) ──────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailLog::find($id);
    }

    public function all(): array
    {
        return EmailLog::all('sent_at', 'DESC');
    }

    public function create(array $data): object
    {
        return EmailLog::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $log = EmailLog::find($id);
        return $log ? $log->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $log = EmailLog::find($id);
        return $log ? $log->delete() : false;
    }

    // ─── Internal helpers ─────────────────────────────────────────────────

    /**
     * Paginate sent or received email logs with optional filters.
     */
    private function paginateSentOrReceived(
        string $type,
        int    $page,
        int    $perPage,
        int    $offset,
        array  $filters
    ): array {
        $pdo    = Database::getInstance()->getConnection();
        $wheres = ['direction = ?'];
        $values = [$type];

        // Apply optional filters
        if (!empty($filters['subject'])) {
            $wheres[] = 'subject LIKE ?';
            $values[] = '%' . $filters['subject'] . '%';
        }
        if (!empty($filters['status'])) {
            $wheres[] = 'status = ?';
            $values[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $wheres[] = 'DATE(sent_at) >= ?';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = 'DATE(sent_at) <= ?';
            $values[] = $filters['date_to'];
        }

        $where = 'WHERE ' . implode(' AND ', $wheres);

        // Count total matching rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_logs {$where}");
        $countStmt->execute($values);
        $total = (int)$countStmt->fetchColumn();

        // Fetch the current page
        $dataStmt = $pdo->prepare(
            "SELECT * FROM email_logs {$where}
             ORDER BY sent_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($values);
        $rows = $dataStmt->fetchAll();

        // Hydrate each row into an EmailLog model instance
        $data = array_map(
            fn($row) => EmailLog::rawOne("SELECT * FROM email_logs WHERE id = ?", [$row['id']]),
            $rows
        );

        return [
            'data'      => array_filter($data),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Paginate error logs with optional filters.
     */
    private function paginateErrors(int $page, int $perPage, int $offset, array $filters): array
    {
        $pdo    = Database::getInstance()->getConnection();
        $wheres = ['1 = 1'];
        $values = [];

        if (!empty($filters['date_from'])) {
            $wheres[] = 'DATE(created_at) >= ?';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = 'DATE(created_at) <= ?';
            $values[] = $filters['date_to'];
        }

        $where = 'WHERE ' . implode(' AND ', $wheres);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_error_logs {$where}");
        $countStmt->execute($values);
        $total = (int)$countStmt->fetchColumn();

        $dataStmt = $pdo->prepare(
            "SELECT * FROM email_error_logs {$where}
             ORDER BY created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($values);
        $rows = $dataStmt->fetchAll();

        $data = array_map(
            fn($row) => EmailErrorLog::rawOne("SELECT * FROM email_error_logs WHERE id = ?", [$row['id']]),
            $rows
        );

        return [
            'data'      => array_filter($data),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }
}
```

---

## 1.6 — Seeders

Seeders populate the database with initial data needed to use the application from day one.

- [ ] **1.6.1 & 1.6.2** First, add these two lines to both `.env` and `.env.example`:

```dotenv
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=changeme123
```

> **Important:** Change `ADMIN_PASSWORD` to a strong password before deploying to production.

---

- [ ] **1.6.1** Create `database/seeders/UserSeeder.php`:

```php
<?php

declare(strict_types=1);

/**
 * UserSeeder
 *
 * Creates the single admin user account.
 * Reads credentials from ADMIN_EMAIL and ADMIN_PASSWORD in .env.
 * Skips if the user already exists (safe to run multiple times).
 */
class UserSeeder
{
    public function run(PDO $pdo): void
    {
        $email    = $_ENV['ADMIN_EMAIL']    ?? 'admin@emirates.local';
        $password = $_ENV['ADMIN_PASSWORD'] ?? 'changeme123';
        $name     = $_ENV['APP_NAME']       ?? 'Emirates Admin';

        // Check if the user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo "    ℹ️  User [{$email}] already exists. Skipping.\n";
            return;
        }

        // Hash the password securely using bcrypt (PHP's default for PASSWORD_DEFAULT)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hash]);

        echo "    ✅ Admin user created: {$email}\n";
        echo "    ⚠️  Change the password after first login!\n";
    }
}
```

- [ ] **1.6.3** Create `database/seeders/SettingSeeder.php`:

```php
<?php

declare(strict_types=1);

/**
 * SettingSeeder
 *
 * Inserts default values for all application settings.
 * Uses INSERT IGNORE so re-running never overwrites user-customised values.
 */
class SettingSeeder
{
    public function run(PDO $pdo): void
    {
        $appName = $_ENV['APP_NAME'] ?? 'Emirates';
        $appUrl  = $_ENV['APP_URL']  ?? 'http://localhost';

        // Default settings — all keys and their initial values
        $defaults = [
            // Platform identity
            'site_name'            => $appName,
            'site_url'             => $appUrl,
            'site_logo_path'       => null,

            // Email sending defaults
            'default_sender_name'  => $appName,
            'default_sender_email' => '',

            // Email branding (injected into templates)
            'email_logo_path'      => null,
            'primary_color'        => '#4F46E5',   // Indigo
            'secondary_color'      => '#10B981',   // Emerald

            // Localisation
            'default_language'     => 'en',
            'timezone'             => $_ENV['TIMEZONE'] ?? 'Africa/Lagos',
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)"
        );

        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
            echo "    ✅ Setting: {$key}\n";
        }
    }
}
```

- [ ] **1.6.4** Create `database/seeders/TemplateSeeder.php`:

```php
<?php

declare(strict_types=1);

/**
 * TemplateSeeder
 *
 * Seeds the three built-in email templates.
 * These are professional HTML emails using the token placeholders
 * {{LOGO_URL}}, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}.
 *
 * Built-in templates cannot be deleted by the user — only duplicated.
 * Uses INSERT IGNORE so re-running is safe.
 */
class TemplateSeeder
{
    public function run(PDO $pdo): void
    {
        $templates = [
            [
                'name'            => 'Newsletter',
                'category'        => 'Newsletter',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->newsletterTemplate(),
            ],
            [
                'name'            => 'Transactional',
                'category'        => 'Transactional',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->transactionalTemplate(),
            ],
            [
                'name'            => 'Promotional',
                'category'        => 'Promotional',
                'is_built_in'     => 1,
                'supports_logo'   => 1,
                'supports_colors' => 1,
                'html_content'    => $this->promotionalTemplate(),
            ],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO email_templates
                (name, category, html_content, is_built_in, supports_logo, supports_colors)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($templates as $template) {
            $stmt->execute([
                $template['name'],
                $template['category'],
                $template['html_content'],
                $template['is_built_in'],
                $template['supports_logo'],
                $template['supports_colors'],
            ]);
            echo "    ✅ Template: {$template['name']}\n";
        }
    }

    // ─── Built-in template HTML ───────────────────────────────────────────

    /**
     * Newsletter template.
     * Clean single-column layout with logo, heading, body, and footer.
     */
    private function newsletterTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter</title>
    <style>
        body        { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper    { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .header     { background-color: {{PRIMARY_COLOR}}; padding: 32px 40px; text-align: center; }
        .header img { max-height: 50px; max-width: 200px; }
        .body       { padding: 40px; color: #374151; }
        .body h1    { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 0 0 16px; }
        .body p     { font-size: 1rem; line-height: 1.7; color: #4b5563; margin: 0 0 16px; }
        .divider    { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .footer     { background-color: #f9fafb; padding: 24px 40px; text-align: center; }
        .footer p   { font-size: 0.78rem; color: #9ca3af; margin: 0; line-height: 1.6; }
        .footer a   { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{LOGO_URL}}" alt="Logo">
        </div>
        <div class="body">
            <h1>Your Newsletter Headline</h1>
            <p>
                Welcome to this month's edition. Here's what we've been working on and what's
                coming up next. We're excited to share these updates with you.
            </p>
            <hr class="divider">
            <h1>Section Two</h1>
            <p>
                Add more content sections here. Each section can have its own heading and
                body text. Keep it concise and scannable for best engagement.
            </p>
        </div>
        <div class="footer">
            <p>
                You're receiving this because you subscribed to our newsletter.<br>
                <a href="#">Unsubscribe</a> &nbsp;·&nbsp; <a href="#">View in browser</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Transactional template.
     * Minimal, clean layout for receipts, confirmations, and notifications.
     */
    private function transactionalTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification</title>
    <style>
        body        { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper    { max-width: 560px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .header     { padding: 32px 40px 0; text-align: center; }
        .header img { max-height: 44px; max-width: 180px; }
        .body       { padding: 32px 40px; color: #374151; }
        .body h1    { font-size: 1.375rem; font-weight: 700; color: #111827; margin: 0 0 12px; }
        .body p     { font-size: 0.9375rem; line-height: 1.7; color: #4b5563; margin: 0 0 16px; }
        .cta        { text-align: center; margin: 28px 0; }
        .cta a      { display: inline-block; padding: 14px 36px; background-color: {{PRIMARY_COLOR}};
                      color: #ffffff; text-decoration: none; border-radius: 8px;
                      font-weight: 600; font-size: 0.9375rem; letter-spacing: 0.01em; }
        .footer     { border-top: 1px solid #e5e7eb; padding: 20px 40px; text-align: center; }
        .footer p   { font-size: 0.75rem; color: #9ca3af; margin: 0; }
        .footer a   { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="{{LOGO_URL}}" alt="Logo">
        </div>
        <div class="body">
            <h1>Hi there 👋</h1>
            <p>
                This is a transactional email notification. Replace this text with
                your specific message — a receipt, confirmation, alert, or any
                action-triggered communication.
            </p>
            <p>
                Keep transactional emails short, clear, and focused on a single action.
            </p>
            <div class="cta">
                <a href="#">Take Action →</a>
            </div>
            <p style="font-size:0.875rem; color:#6b7280;">
                If you have any questions, reply to this email and we'll be happy to help.
            </p>
        </div>
        <div class="footer">
            <p>© 2025 Your Company. All rights reserved.<br>
               <a href="#">Unsubscribe</a></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Promotional template.
     * Eye-catching layout with a coloured hero section for marketing emails.
     */
    private function promotionalTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion</title>
    <style>
        body         { margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper     { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,0.08); }
        .hero        { background-color: {{PRIMARY_COLOR}}; padding: 48px 40px; text-align: center; }
        .hero img    { max-height: 50px; max-width: 180px; margin-bottom: 24px; display: block; margin-left: auto; margin-right: auto; }
        .hero h1     { font-size: 1.75rem; font-weight: 800; color: #ffffff; margin: 0 0 12px; line-height: 1.25; }
        .hero p      { font-size: 1rem; color: rgba(255,255,255,0.85); margin: 0; line-height: 1.6; }
        .body        { padding: 40px; color: #374151; }
        .body p      { font-size: 0.9375rem; line-height: 1.7; color: #4b5563; margin: 0 0 20px; }
        .cta         { text-align: center; margin: 28px 0; }
        .cta a       { display: inline-block; padding: 16px 44px; background-color: {{PRIMARY_COLOR}};
                       color: #ffffff; text-decoration: none; border-radius: 8px;
                       font-weight: 700; font-size: 1rem; }
        .accent-bar  { height: 4px; background: linear-gradient(90deg, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}); }
        .footer      { background-color: #f9fafb; padding: 24px 40px; text-align: center; }
        .footer p    { font-size: 0.75rem; color: #9ca3af; margin: 0; line-height: 1.6; }
        .footer a    { color: {{SECONDARY_COLOR}}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hero">
            <img src="{{LOGO_URL}}" alt="Logo">
            <h1>Your Big Offer Headline</h1>
            <p>A compelling sub-headline that drives excitement and urgency.</p>
        </div>
        <div class="body">
            <p>
                Hello! We have something exciting to share with you. This is where you
                describe your promotion, offer, or announcement in a few short paragraphs.
            </p>
            <p>
                Keep the message focused on the benefit to the reader. What do they get?
                Why should they act now? What makes this offer special?
            </p>
            <div class="cta">
                <a href="#">Claim Your Offer →</a>
            </div>
            <p style="font-size:0.8125rem; color:#9ca3af; text-align:center;">
                Offer valid until [date]. Terms and conditions apply.
            </p>
        </div>
        <div class="accent-bar"></div>
        <div class="footer">
            <p>
                You received this because you signed up for updates.<br>
                <a href="#">Unsubscribe</a> &nbsp;·&nbsp; <a href="#">Privacy Policy</a>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
```

- [ ] **1.6.8** Run migrations and seeders together:
  ```bash
  php database/migrate.php --seed
  ```
  Expected output:
  ```
  ✅ Connected to database [emirates]
  ⏭  Skipped (already applied): 001_create_migrations_table.sql
  ⏭  Skipped (already applied): 002_create_users_table.sql
  ... (all 12 skipped)
  ✅ Database is already up to date.
  🌱 Running seeders...
  ✅ Admin user created: admin@example.com
  ✅ Setting: site_name
  ... (all settings listed)
  ✅ Template: Newsletter
  ✅ Template: Transactional
  ✅ Template: Promotional
  ✅ All seeders completed.
  🎉 Migration complete.
  ```

  To verify in MySQL:
  ```sql
  SELECT COUNT(*) FROM users;          -- should be 1
  SELECT COUNT(*) FROM settings;       -- should be 10
  SELECT name, category FROM email_templates;  -- should show 3 templates
  ```

---

## 1.7 — Data Transfer Objects (DTOs)

DTOs are simple, immutable data containers used to pass structured data between layers (services, controllers, providers). PHP 8.2+ `readonly` classes are perfect for this — their properties can only be set in the constructor and never changed after that.

- [ ] **1.7.1** Create `app/DTOs/EmailPayload.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * EmailPayload
 *
 * Everything needed to send one email.
 * Passed from ComposeController → EmailSendService → Provider.
 *
 * Usage:
 *   $payload = new EmailPayload(
 *       recipients: ['alice@example.com', 'bob@example.com'],
 *       subject:    'Hello from Emirates',
 *       html:       '<h1>Hi!</h1><p>This is your email.</p>',
 *       senderName:  'Acme Corp',
 *       senderEmail: 'hello@acme.com',
 *   );
 */
readonly class EmailPayload
{
    public function __construct(
        /** Array of recipient email address strings */
        public array   $recipients,

        /** Email subject line */
        public string  $subject,

        /** Final rendered HTML body (tokens already replaced) */
        public string  $html,

        /** Display name for the From field: "Acme Corp" */
        public string  $senderName,

        /** Email address for the From field: "hello@acme.com" */
        public string  $senderEmail,

        /** Optional Reply-To address (defaults to senderEmail if null) */
        public ?string $replyTo = null,

        /** Optional CC email addresses */
        public ?array  $cc = null,

        /** Optional BCC email addresses */
        public ?array  $bcc = null,
    ) {}

    /**
     * Get the formatted "From" string used by email providers.
     * Format: "Display Name <email@address.com>"
     */
    public function fromString(): string
    {
        return "{$this->senderName} <{$this->senderEmail}>";
    }

    /**
     * Get the effective reply-to address.
     * Falls back to sender email if not explicitly set.
     */
    public function effectiveReplyTo(): string
    {
        return $this->replyTo ?? $this->senderEmail;
    }
}
```

- [ ] **1.7.2** Create `app/DTOs/SendResult.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * SendResult
 *
 * The result returned by a provider after attempting to send an email.
 * Returned from ResendProvider::send() and SmtpProvider::send().
 *
 * Usage:
 *   $result = $provider->send($payload);
 *   echo $result->messageId;  // 'msg_abc123' (from Resend)
 *   echo $result->status;     // 'sent'
 */
readonly class SendResult
{
    public function __construct(
        /** The unique message ID assigned by the provider */
        public string  $messageId,

        /** Delivery status: 'sent', 'failed', etc. */
        public string  $status,

        /** Raw response body from the provider (for debugging) */
        public ?string $providerResponse = null,
    ) {}

    /**
     * Check if the send was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'sent';
    }
}
```

- [ ] **1.7.3** Create `app/DTOs/RecipientData.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * RecipientData
 *
 * Represents one row of recipient data from a CSV import.
 * Used by CsvImportService to pass validated row data to RecipientRepository.
 *
 * Usage:
 *   $row = new RecipientData(
 *       firstName: 'Alice',
 *       lastName:  'Smith',
 *       email:     'alice@example.com',
 *       company:   'Acme Corp',
 *       tags:      'Clients,VIPs',
 *   );
 */
readonly class RecipientData
{
    public function __construct(
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public ?string $company = null,
        public ?string $tags    = null,
    ) {}

    /**
     * Convert to an array suitable for RecipientRepository::bulkInsert().
     */
    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'email'      => $this->email,
            'company'    => $this->company,
            'notes'      => null,
        ];
    }

    /**
     * Parse the tags string into an array of group names.
     * Input:  'Clients, VIPs, Newsletter'
     * Output: ['Clients', 'VIPs', 'Newsletter']
     */
    public function tagsArray(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $this->tags))
        );
    }
}
```

- [ ] **1.7.4** Create `app/DTOs/TemplateData.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * TemplateData
 *
 * Carries the data needed to create or update an email template.
 * Used by TemplateController → TemplateRepository.
 *
 * Usage:
 *   $data = new TemplateData(
 *       name:          'My Template',
 *       category:      'Newsletter',
 *       htmlContent:   '<html>...',
 *       supportsLogo:  true,
 *       supportsColors: true,
 *   );
 */
readonly class TemplateData
{
    public function __construct(
        public string $name,
        public string $category,
        public string $htmlContent,
        public bool   $supportsLogo,
        public bool   $supportsColors,
    ) {}

    /**
     * Convert to an array for EmailTemplate::create() or update().
     */
    public function toArray(): array
    {
        return [
            'name'            => $this->name,
            'category'        => $this->category,
            'html_content'    => $this->htmlContent,
            'supports_logo'   => $this->supportsLogo   ? 1 : 0,
            'supports_colors' => $this->supportsColors ? 1 : 0,
        ];
    }
}
```

- [ ] **1.7.5** Create `app/DTOs/RenderContext.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * RenderContext
 *
 * The resolved branding and sender context used when rendering an email template.
 * Passed to TemplateRenderService::render() to inject into template tokens.
 *
 * The values in this object represent the FINAL resolved values after
 * applying the override hierarchy:
 *   Email-level override > Global settings
 *
 * Usage:
 *   $context = new RenderContext(
 *       logoUrl:        'https://example.com/storage/logos/global/logo.png',
 *       primaryColor:   '#4F46E5',
 *       secondaryColor: '#10B981',
 *       senderName:     'Acme Corp',
 *       senderEmail:    'hello@acme.com',
 *   );
 *
 *   $html = $templateRenderService->render($template->html_content, $context);
 */
readonly class RenderContext
{
    public function __construct(
        /** URL to the logo image (absolute, accessible from recipient's email client) */
        public string  $logoUrl,

        /** Primary brand colour in hex format: '#4F46E5' */
        public string  $primaryColor,

        /** Secondary brand colour in hex format: '#10B981' */
        public string  $secondaryColor,

        /** Display name shown in the From field */
        public string  $senderName,

        /** Email address shown in the From field */
        public string  $senderEmail,

        /** Optional Reply-To override */
        public ?string $replyTo = null,
    ) {}

    /**
     * Build a RenderContext from the current global settings plus any per-email overrides.
     *
     * The email-level overrides win over global settings when provided.
     *
     * @param array $overrides Keys: logo_override_path, primary_color, secondary_color
     */
    public static function fromSettings(array $overrides = []): static
    {
        $settingRepo  = new \App\Repositories\SettingRepository();
        $settings     = $settingRepo->allAsKeyValue();

        // Resolve logo URL: prefer email-level override, fall back to global
        $logoPath = $overrides['logo_override_path'] ?? $settings['email_logo_path'] ?? null;
        $logoUrl  = $logoPath
            ? url('/storage/logos/' . ltrim(basename((string)$logoPath), '/'))
            : url('/assets/img/placeholder-logo.svg');

        return new static(
            logoUrl:        $logoUrl,
            primaryColor:   $overrides['primary_color']   ?? $settings['primary_color']   ?? '#4F46E5',
            secondaryColor: $overrides['secondary_color']  ?? $settings['secondary_color'] ?? '#10B981',
            senderName:     $settings['default_sender_name']  ?? '',
            senderEmail:    $settings['default_sender_email'] ?? '',
        );
    }
}
```

---

## 1.8 — Register Repositories in the Service Container

Now that all repositories exist, register them in `bootstrap/app.php` so they can be resolved throughout the application.

- [ ] **1.8** Open `bootstrap/app.php` and add these bindings after the core singletons section:

```php
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
```

---

## 1.9 — Milestone: Phase 1 Verification

Run each check in order. All should pass before moving to Phase 2.

- [ ] **1.9.1** Run a fresh migration to confirm everything builds cleanly:
  ```bash
  php database/migrate.php --fresh --seed
  ```
  Expected: All 12 tables dropped, re-created, and seeded without errors.

- [ ] **1.9.2** Verify the table structure in MySQL:
  ```sql
  SHOW TABLES;
  -- Should list: _migrations, credentials, email_drafts, email_error_logs,
  --              email_logs, email_templates, received_emails, recipient_group_pivot,
  --              recipient_groups, recipients, settings, users

  DESCRIBE users;
  -- Should show: id, name, email, password_hash, created_at
  ```

- [ ] **1.9.3** Verify the seeded data:
  ```sql
  SELECT email FROM users;
  -- Should show your ADMIN_EMAIL

  SELECT `key`, `value` FROM settings;
  -- Should show 10 rows including primary_color, secondary_color, etc.

  SELECT name, category, is_built_in FROM email_templates;
  -- Should show: Newsletter, Transactional, Promotional — all with is_built_in = 1
  ```

- [ ] **1.9.4** Confirm the application still starts without errors:
  ```bash
  php -S localhost:8000 -t public/
  ```
  Visit `http://localhost:8000/does-not-exist` — should show the 404 error page (not a PHP error).

- [ ] **1.9.5** Commit Phase 1:
  ```bash
  git add -A
  git commit -m "Phase 1: Migrations, models, repositories, seeders, DTOs"
  ```

---

## Phase 1 Complete ✅

**What you have built:**

| Component | Files |
|---|---|
| Migration runner | `database/migrate.php` |
| SQL migrations | 12 files in `database/migrations/` |
| Domain models | `User`, `Setting`, `Credential`, `EmailTemplate`, `Recipient`, `RecipientGroup`, `EmailDraft`, `EmailLog`, `EmailErrorLog` |
| Repository interfaces | `RepositoryInterface`, `SettingRepositoryInterface`, `TemplateRepositoryInterface`, `DraftRepositoryInterface`, `RecipientRepositoryInterface`, `LogRepositoryInterface` |
| Repository implementations | `SettingRepository`, `TemplateRepository`, `DraftRepository`, `RecipientRepository`, `LogRepository` |
| Seeders | `UserSeeder`, `SettingSeeder`, `TemplateSeeder` (3 built-in email templates) |
| DTOs | `EmailPayload`, `SendResult`, `RecipientData`, `TemplateData`, `RenderContext` |

**Database tables created:** 12 (including `_migrations` tracking table)
**Built-in email templates:** 3 (Newsletter, Transactional, Promotional)
**Admin user:** Seeded from `.env` — ready for login in Phase 2

**Ready for Phase 2:** Authentication — login form, session management, route protection.

---

*End of Emirates Phase 1 Implementation*
```
