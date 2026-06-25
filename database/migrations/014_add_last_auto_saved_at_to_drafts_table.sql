-- 014_add_last_auto_saved_at_to_drafts_table.sql
-- Adds last_auto_saved_at column to email_drafts table

ALTER TABLE `email_drafts` ADD COLUMN `last_auto_saved_at` DATETIME NULL DEFAULT NULL AFTER `bcc_json`;
