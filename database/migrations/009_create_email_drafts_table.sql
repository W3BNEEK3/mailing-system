-- database/migrations/009_create_email_drafts_table.sql
--
-- Stores in-progress email compositions (drafts).
--
-- Column notes:
--   recipients_json  — JSON array of recipient strings.
--                      Strings may be email addresses ("alice@example.com")
--                      or group names ("Newsletter").
--                      Resolved to actual email addresses at send-time,
--                      NOT stored here — this stores the user's raw input.
--
--   template_id      — FK to email_templates (nullable). If the user picked
--                      a template, its ID is stored here so it can be
--                      re-selected when the draft is loaded. The HTML body
--                      itself is stored in body_html, not resolved again from
--                      the template, so edits are preserved.
--
--   body_html        — The full HTML body as edited by the user. May contain
--                      substituted tokens (after template load) or raw HTML
--                      pasted/typed by the user.
--
--   email_logo_path  — Per-email logo override. Null = use global logo.
--                      If set, this path is used instead of the settings
--                      email_logo_path when building RenderContext for send.
--
--   primary_color    — Per-email colour override. Null = use global.
--   secondary_color  — Per-email colour override. Null = use global.
--
--   subject          — Email subject line. Stored even if empty so the draft
--                      can be loaded without data loss.

CREATE TABLE IF NOT EXISTS email_drafts (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    subject         VARCHAR(500)  NOT NULL DEFAULT '',
    recipients_json TEXT          NOT NULL DEFAULT '[]',
    template_id     INT UNSIGNED  NULL     DEFAULT NULL,
    body_html       LONGTEXT      NOT NULL DEFAULT '',
    email_logo_path VARCHAR(500)  NULL     DEFAULT NULL,
    primary_color   VARCHAR(10)   NULL     DEFAULT NULL,
    secondary_color VARCHAR(10)   NULL     DEFAULT NULL,
    reply_to        VARCHAR(255)  NULL     DEFAULT NULL,
    cc_json         TEXT          NOT NULL DEFAULT '[]',
    bcc_json        TEXT          NOT NULL DEFAULT '[]',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_updated_at (updated_at),
    CONSTRAINT fk_drafts_template
        FOREIGN KEY (template_id)
        REFERENCES email_templates (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;