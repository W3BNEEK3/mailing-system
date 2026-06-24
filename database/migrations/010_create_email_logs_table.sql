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
