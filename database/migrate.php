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
    // Run inside a transaction so a failure rolls back cleanly
    try {
        // Start the transaction normally
        $pdo->beginTransaction();
        
        // Execute the SQL (If this is a CREATE TABLE, MySQL will implicitly commit here)
        $pdo->exec($sql);
        
        // Only commit if MySQL hasn't already implicitly closed the transaction
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        // Record this migration as applied
        $stmt = $pdo->prepare("INSERT INTO `_migrations` (migration) VALUES (?)");
        $stmt->execute([$filename]);

        output("  ✅ Applied: {$filename}");
        $ranCount++;

    } catch (PDOException $e) {
        // Only roll back if a transaction is actually active
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
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
