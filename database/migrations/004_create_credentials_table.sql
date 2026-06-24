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
