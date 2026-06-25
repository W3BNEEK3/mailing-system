-- database/migrations/010_create_email_logs_table.sql
--
-- Records every successfully initiated email send attempt.
-- One row per send batch (not per recipient), since some providers
-- accept an array of recipients in a single API call.
--
-- Status lifecycle (updated by webhook in Phase 10):
--   queued → sent → delivered | bounced | failed
--
-- Column notes:
--   provider_message_id  — The message ID returned by the provider
--                          (e.g. Resend's "msg_abc123"). Used to correlate
--                          webhook events with this log row in Phase 10.
--   recipient_count      — Denormalised count of how many addresses the email
--                          was sent to. Avoids re-parsing recipients_json for
--                          the logs listing page.
--   template_id          — FK to email_templates (nullable, SET NULL on delete).
--   provider             — Which provider was active at send time ('resend'/'smtp').

CREATE TABLE IF NOT EXISTS email_logs (
    id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    subject             VARCHAR(500)  NOT NULL DEFAULT '',
    recipients_json     TEXT          NOT NULL DEFAULT '[]',
    recipient_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    template_id         INT UNSIGNED  NULL     DEFAULT NULL,
    provider            VARCHAR(20)   NOT NULL DEFAULT 'resend',
    provider_message_id VARCHAR(255)  NULL     DEFAULT NULL,
    status              ENUM('queued','sent','delivered','bounced','failed')
                                      NOT NULL DEFAULT 'queued',
    body_html           LONGTEXT      NOT NULL DEFAULT '',
    sent_at             DATETIME      NULL     DEFAULT NULL,
    created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_status      (status),
    INDEX idx_sent_at     (sent_at),
    INDEX idx_provider_msg (provider_message_id),
    CONSTRAINT fk_logs_template
        FOREIGN KEY (template_id)
        REFERENCES email_templates (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;