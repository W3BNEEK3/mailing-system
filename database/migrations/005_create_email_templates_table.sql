-- 005_create_email_templates_table.sql
-- Stores all email templates — both built-in (shipped with the app)
-- and custom (uploaded or pasted by the user).
--
-- supports_logo and supports_colors are detected at upload time
-- by scanning the HTML for {{LOGO_URL}}, {{PRIMARY_COLOR}}, {{SECONDARY_COLOR}}.
-- These flags drive which toolbar controls are enabled in the compose page.

CREATE TABLE IF NOT EXISTS `email_templates` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`            VARCHAR(150) NOT NULL,
    `category`        VARCHAR(80)  NULL COMMENT 'e.g. Newsletter, Transactional, Promotional',
    `html_content`    LONGTEXT     NOT NULL,
    `thumbnail_path`  VARCHAR(255) NULL COMMENT 'Relative path to preview image in storage/',
    `is_built_in`     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = shipped with app, cannot be deleted',
    `supports_logo`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = contains {{LOGO_URL}} placeholder',
    `supports_colors` TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = contains {{PRIMARY_COLOR}} placeholder',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
