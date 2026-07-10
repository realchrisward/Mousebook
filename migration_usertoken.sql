-- =====================================================================
-- Mousebook migration — Phase G (issue #19): usertoken table
-- =====================================================================
-- Adds the single-use, time-limited token table backing the user
-- invitation and password-reset flows (php/set_password.php,
-- php/forgot_password.php, php/manage_users.php).
--
-- Only the sha256 HASH of a token is stored here; the raw token exists
-- only in the emailed link. Tokens are single-use (used_at) and expire
-- (expires_at). No credentials are stored in this table.
--
-- Idempotent: safe to run more than once (CREATE TABLE IF NOT EXISTS).
--
-- Apply BEFORE deploying the Phase G PHP pages:
--   mysql -u <admin> -p userbook < migration_usertoken.sql
--
-- (Edit the USE line if your auth db is not named `userbook`.)
-- =====================================================================

USE `userbook`;

CREATE TABLE IF NOT EXISTS `usertoken` (
  `token_id`   bigint      NOT NULL AUTO_INCREMENT,
  `user_idno`  bigint      NOT NULL,
  `token_hash` char(64)    NOT NULL,               -- sha256 hex of the raw token
  `purpose`    varchar(16) NOT NULL,               -- 'invite' | 'reset'
  `created_by` varchar(45) DEFAULT NULL,           -- admin username, or 'self'
  `created_at` datetime    NOT NULL,
  `expires_at` datetime    NOT NULL,
  `used_at`    datetime    DEFAULT NULL,            -- set on consume (single-use)
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash_UNIQUE` (`token_hash`),
  KEY `user_idno` (`user_idno`),
  KEY `lookup` (`token_hash`,`purpose`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
