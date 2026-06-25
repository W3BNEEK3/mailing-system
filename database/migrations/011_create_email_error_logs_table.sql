-- database/migrations/011_create_email_error_logs_table.sql
--
-- Records failed send attempts for debugging and retry tracking.
-- A row is inserted whenever EmailSendService::send() throws a ProviderException.

CREATE TABLE IF NOT EXISTS email_error_logs (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    subject         VARCHAR(500)  NOT NULL DEFAULT '',
    recipients_json TEXT          NOT NULL DEFAULT '[]',
    error_message   TEXT          NOT NULL,
    provider        VARCHAR(20)   NOT NULL DEFAULT 'resend',
    template_id     INT UNSIGNED  NULL     DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;