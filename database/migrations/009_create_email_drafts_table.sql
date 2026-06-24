-- 009_create_email_drafts_table.sql
-- Stores in-progress email compositions (drafts).
-- Drafts are auto-saved every 60 seconds and manually saved via "Save Draft".
-- All draft state is stored here so it survives page refreshes and browser closes.

CREATE TABLE IF NOT EXISTS `email_drafts` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject`            VARCHAR(255) NULL,
    `recipients_json`    TEXT         NULL COMMENT 'JSON array of email addresses/group names',
    `body_html`          LONGTEXT     NULL,
    `template_id`        INT UNSIGNED NULL COMMENT 'The template used (if any)',
    `logo_override_path` VARCHAR(255) NULL COMMENT 'Relative path to per-email logo override',
    `primary_color`      VARCHAR(7)   NULL COMMENT 'Hex color e.g. #4F46E5',
    `secondary_color`    VARCHAR(7)   NULL COMMENT 'Hex color e.g. #10B981',
    `language`           VARCHAR(10)  NULL COMMENT 'BCP 47 language code e.g. en, es, fr',
    `last_auto_saved_at` TIMESTAMP    NULL,
    `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`template_id`)
        REFERENCES `email_templates` (`id`)
        ON DELETE SET NULL  -- if template deleted, draft keeps its body_html but loses the reference
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
