-- 013_add_role_to_users_table.sql
-- Adds Role-Based Access Control to the users table.
-- Existing users will default to 'super_admin' to preserve access.

ALTER TABLE `users` ADD COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'super_admin' AFTER `email`;
