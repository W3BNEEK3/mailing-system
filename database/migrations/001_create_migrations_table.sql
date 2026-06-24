-- 001_create_migrations_table.sql
-- Tracks which migration files have been applied.
-- The migrate.php script creates this automatically,
-- but we include it here for documentation completeness.

CREATE TABLE IF NOT EXISTS `_migrations` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration`  VARCHAR(255) NOT NULL UNIQUE,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
