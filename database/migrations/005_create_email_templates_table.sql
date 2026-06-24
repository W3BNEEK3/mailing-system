-- database/migrations/005_create_email_templates_table.sql
--
-- Stores all email templates: both built-in (shipped with the app) and custom
-- (uploaded or pasted by the user).
--
-- Column notes:
--   html_content    — Full HTML of the template. Stored in the database for
--                     built-in and paste-based templates. For file-uploaded
--                     templates, this column holds the extracted HTML content
--                     (NOT just a path — the HTML is read from disk at upload
--                     time and stored here so the template remains available
--                     even if storage is reorganised).
--   thumbnail_path  — Reserved for future use. In MVP, thumbnails are generated
--                     from the template name + category as a styled placeholder.
--   is_built_in     — 1 = shipped with the app, cannot be deleted.
--   supports_logo   — 1 = template HTML contains {{LOGO_URL}} placeholder.
--   supports_colors — 1 = template HTML contains {{PRIMARY_COLOR}} or
--                     {{SECONDARY_COLOR}} placeholders.

CREATE TABLE IF NOT EXISTS email_templates (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150)    NOT NULL,
    category        VARCHAR(80)     NOT NULL DEFAULT 'General',
    html_content    LONGTEXT        NOT NULL,
    thumbnail_path  VARCHAR(500)    NULL     DEFAULT NULL,
    is_built_in     TINYINT(1)      NOT NULL DEFAULT 0,
    supports_logo   TINYINT(1)      NOT NULL DEFAULT 0,
    supports_colors TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_category   (category),
    INDEX idx_is_built_in (is_built_in)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
