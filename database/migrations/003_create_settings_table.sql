-- 003_create_settings_table.sql
-- Key-value store for all application-level settings.
-- Examples: site_name, primary_color, default_sender_email.

CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(100) NOT NULL UNIQUE,
    `value`      TEXT         NULL,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
