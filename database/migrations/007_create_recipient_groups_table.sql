-- 007_create_recipient_groups_table.sql
-- Groups (tags) for organising recipients.
-- Example group names: 'Clients', 'Newsletter', 'VIPs'.
-- In the compose page, you can type a group name in the To field
-- and all non-suppressed members will be resolved as recipients.

CREATE TABLE IF NOT EXISTS `recipient_groups` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
