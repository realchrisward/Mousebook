-- =============================================================
-- Mousebook Database Migration v2
-- Password hashing: widens user_pass column in userbook.
--
-- Run against your userbook database BEFORE running
-- migrate_passwords.php:
--
--   mysql -u root -p userbook < mousebook_migration_v2.sql
--
-- Safe to re-run (MODIFY COLUMN is idempotent in MySQL).
-- =============================================================

-- Widen user_pass from varchar(45) to varchar(255).
-- bcrypt hashes are 60 chars; varchar(255) leaves room
-- for future algorithm upgrades (Argon2id = ~95 chars).
ALTER TABLE `userpass`
  MODIFY COLUMN `user_pass` varchar(255) NOT NULL;

-- Remove user_salt — it is unused by the codebase and
-- bcrypt embeds its own salt in the hash automatically.
-- Comment this out if you want to keep the column.
ALTER TABLE `userpass`
  MODIFY COLUMN `user_salt` varchar(255) NOT NULL DEFAULT '';

-- Verify:
--   DESCRIBE userpass;
