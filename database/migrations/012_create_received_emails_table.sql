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
