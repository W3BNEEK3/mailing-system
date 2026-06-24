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
