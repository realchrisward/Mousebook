-- =====================================================================
-- Mousebook - authoritative install schema (userbook / auth)
-- =====================================================================
-- Generated from a live `mysqldump --no-data --routines userbook`
-- (MySQL 8.0.45, 2026-07-05). userbook has no views or routines, so
-- (unlike animalbook) there were no DEFINER clauses to strip.
--
-- Made install-portable:
--   * CREATE DATABASE / USE header added
--   * AUTO_INCREMENT initializers reset (a fresh auth db starts empty,
--     so the first user_idno / db_no / link_number should begin at 1)
--
-- Replaces the stale repo file default_userbook.sql.
-- No credentials or user rows are committed here - see BOOTSTRAP below.
--
-- Load:  mysql -u <admin> -p < mousebook_userbook_install_schema.sql
-- =====================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Target database. Edit the name on BOTH lines if your install uses a
-- different db name than `userbook` (must match config.php).
--
CREATE DATABASE IF NOT EXISTS `userbook` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `userbook`;

--
-- Table structure for table `dbaccess`
-- One row per managed colony database (maps a login form to its animalbook-style db).
--

DROP TABLE IF EXISTS `dbaccess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dbaccess` (
  `db_no` bigint NOT NULL AUTO_INCREMENT,
  `db_name` varchar(45) NOT NULL,
  `db_accessun` varchar(45) NOT NULL,
  `db_accesspw` varchar(45) NOT NULL,
  `db_formurl` varchar(45) NOT NULL,
  `db_host` varchar(45) DEFAULT NULL,
  `db_subject_plural` varchar(45) DEFAULT NULL,
  `db_subject_single` varchar(45) DEFAULT NULL,
  `db_guide1_title` varchar(45) DEFAULT NULL,
  `db_guide1_url` mediumtext,
  PRIMARY KEY (`db_no`),
  UNIQUE KEY `db_name_UNIQUE` (`db_name`),
  UNIQUE KEY `db_formurl_UNIQUE` (`db_formurl`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userdbaccess`
-- Per-user access tier for each db (db_accesstier; Phase-3 multi-user work).
--

DROP TABLE IF EXISTS `userdbaccess`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userdbaccess` (
  `user_idno` bigint NOT NULL,
  `link_number` bigint NOT NULL AUTO_INCREMENT,
  `db_name` varchar(45) NOT NULL,
  `db_accesstier` varchar(45) NOT NULL,
  PRIMARY KEY (`link_number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userdetail`
--

DROP TABLE IF EXISTS `userdetail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userdetail` (
  `user_idno` bigint NOT NULL,
  `user_email` varchar(45) DEFAULT NULL,
  `user_phone` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_idno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userpass`
-- user_pass holds a bcrypt hash (see includes/auth.php). user_salt is legacy.
--

DROP TABLE IF EXISTS `userpass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userpass` (
  `user_idno` bigint NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) NOT NULL,
  `user_pass` varchar(255) NOT NULL,
  `user_salt` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_idno`),
  UNIQUE KEY `user_idno_UNIQUE` (`user_idno`),
  UNIQUE KEY `username_UNIQUE` (`user_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usertoken`
-- Single-use, time-limited tokens for invitations and password resets
-- (Phase G / issue #19). Stores only the sha256 hash of each token; the
-- raw token exists only in the emailed link. See includes/usertoken.php.
--

DROP TABLE IF EXISTS `usertoken`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usertoken` (
  `token_id`   bigint      NOT NULL AUTO_INCREMENT,
  `user_idno`  bigint      NOT NULL,
  `token_hash` char(64)    NOT NULL,
  `purpose`    varchar(16) NOT NULL,
  `created_by` varchar(45) DEFAULT NULL,
  `created_at` datetime    NOT NULL,
  `expires_at` datetime    NOT NULL,
  `used_at`    datetime    DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token_hash_UNIQUE` (`token_hash`),
  KEY `user_idno` (`user_idno`),
  KEY `lookup` (`token_hash`,`purpose`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- ---------------------------------------------------------------------
-- BOOTSTRAP (do NOT commit real credentials to the repo)
-- ---------------------------------------------------------------------
-- A fresh userbook is intentionally empty, which means no one can log in
-- yet. To make the install usable, after loading this file you must:
--
--   1. Register one managed colony database in `dbaccess` (one row per
--      animalbook-style db). db_accessun/db_accesspw are the MySQL
--      credentials Mousebook uses to reach that colony db:
--
--        INSERT INTO `dbaccess`
--          (`db_name`,`db_accessun`,`db_accesspw`,`db_formurl`,`db_host`)
--          VALUES ('animalbook','<db_user>','<db_pass>','animalbook','localhost');
--
--   2. Create the first user with a bcrypt hash. Generate the hash with
--      the app's tooling (includes/auth.php / migrate_passwords.php) or:
--        php -r "echo password_hash('<chosen-pass>', PASSWORD_BCRYPT), PHP_EOL;"
--      then:
--        INSERT INTO `userpass` (`user_name`,`user_pass`)
--          VALUES ('<admin>', '<bcrypt-hash-from-above>');
--
--   3. Grant that user access to the db (tier is the Phase-3 access model):
--        INSERT INTO `userdbaccess` (`user_idno`,`db_name`,`db_accesstier`)
--          VALUES (LAST_INSERT_ID(), 'animalbook', 'admin');
-- ---------------------------------------------------------------------

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Regenerated from live 2026-07-05; replaces default_userbook.sql
