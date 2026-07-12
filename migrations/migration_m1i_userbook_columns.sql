-- =============================================================
-- Mousebook migration — M1-I: widen the dbaccess/userdbaccess columns
-- =============================================================
-- Applies to: the AUTH database (userbook), on an EXISTING install.
-- Fresh installs get these widths from
-- mousebook_userbook_install_schema.sql already and need nothing.
--
-- What changes and why:
--
--   dbaccess.db_accesspw   varchar(45)  -> varchar(255)
--       The password Mousebook uses to reach a colony was capped at 45
--       characters. That is short enough to collide with a generated or
--       policy-mandated password, and the failure is nasty: MySQL truncates
--       on insert (or errors under STRICT mode), and the colony then cannot
--       be connected to with the password you actually set.
--
--   dbaccess.db_formurl    varchar(45)  -> varchar(255)
--       45 characters does not fit a real URL. `https://mousebook.example.edu/
--       mousebook/index.php` is 48 on its own. This forced the form target to
--       be stored as a relative path; it can now be either.
--
--   dbaccess.db_name       varchar(45)  -> varchar(64)
--   userdbaccess.db_name   varchar(45)  -> varchar(64)
--       64 is the database-name maximum the engine itself enforces, so this
--       can no longer be the narrower limit. Matters on cPanel-style hosts,
--       where every database name carries an account prefix. The two columns
--       are joined to each other, so they must be widened together.
--
-- Safe to re-run: MODIFY COLUMN is idempotent, and every change is a
-- widening, so no existing value can be truncated. No data is rewritten.
--
-- Engines: runs on both MySQL 8.x and MariaDB 10.11+.
--
-- Load:  mysql -u <admin> -p <your_userbook_db> < migration_m1i_userbook_columns.sql
-- =============================================================

ALTER TABLE `dbaccess`
    MODIFY COLUMN `db_name`     varchar(64)  NOT NULL,
    MODIFY COLUMN `db_accesspw` varchar(255) NOT NULL,
    MODIFY COLUMN `db_formurl`  varchar(255) NOT NULL;

ALTER TABLE `userdbaccess`
    MODIFY COLUMN `db_name` varchar(64) NOT NULL;

-- -------------------------------------------------------------
-- Verification. Expect: db_name 64, db_accesspw 255, db_formurl 255.
-- -------------------------------------------------------------
SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
  FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE()
   AND TABLE_NAME IN ('dbaccess','userdbaccess')
   AND COLUMN_NAME IN ('db_name','db_accesspw','db_formurl')
 ORDER BY TABLE_NAME, COLUMN_NAME;
