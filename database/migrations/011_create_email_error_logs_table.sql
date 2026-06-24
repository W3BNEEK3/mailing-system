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

