-- 008_create_recipient_group_pivot_table.sql
-- Many-to-many relationship between recipients and groups.
-- One recipient can belong to multiple groups.
-- One group can have many recipients.

CREATE TABLE IF NOT EXISTS `recipient_group_pivot` (
    `recipient_id` INT UNSIGNED NOT NULL,
    `group_id`     INT UNSIGNED NOT NULL,

    PRIMARY KEY (`recipient_id`, `group_id`),

    FOREIGN KEY (`recipient_id`)
        REFERENCES `recipients` (`id`)
        ON DELETE CASCADE,  -- if the recipient is deleted, remove from all groups

    FOREIGN KEY (`group_id`)
        REFERENCES `recipient_groups` (`id`)
        ON DELETE CASCADE   -- if the group is deleted, remove all pivot rows
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
