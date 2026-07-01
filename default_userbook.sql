-- =============================================================
-- Mousebook: userbook database schema
-- =============================================================
-- This file defines the userbook database, which controls:
--   - Which users can log in (userpass)
--   - Which colony databases each user can access (userdbaccess)
--   - Colony database connection details (dbaccess)
--   - Optional user contact details (userdetail)
--
-- HOW TO USE:
--   1. Import this file:
--        mysql -u root -p userbook < default_userbook.sql
--
--   2. Then edit the INSERT statements at the bottom to set your
--      real server addresses, credentials, and user details.
--      Or use the setup.sh script which does this interactively.
--
-- NOTE: Passwords in userpass are stored in plain text in this
--   version. Use strong, unique passwords. A hashed-password
--   upgrade is planned for a future release.
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- Database: `userbook`

-- --------------------------------------------------------
-- Table: dbaccess
-- Stores connection details for each colony database.
-- One row per colony database (e.g. mousebook, ratbook).
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `dbaccess` (
  `db_no`             bigint(20)   NOT NULL AUTO_INCREMENT,
  `db_name`           varchar(45)  NOT NULL,  -- MySQL database name
  `db_accessun`       varchar(45)  NOT NULL,  -- MySQL user for this colony DB
  `db_accesspw`       varchar(45)  NOT NULL,  -- MySQL password for db_accessun
  `db_formurl`        varchar(255) NOT NULL,  -- Full URL to this colony's index.php
  `db_host`           varchar(45)  DEFAULT NULL, -- MySQL host for this colony DB
  `db_subject_plural` varchar(45)  DEFAULT NULL, -- e.g. 'mice', 'rats'
  `db_subject_single` varchar(45)  DEFAULT NULL, -- e.g. 'mouse', 'rat'
  `db_guide1_title`   varchar(45)  DEFAULT NULL, -- Optional: label for a help link
  `db_guide1_url`     mediumtext,               -- Optional: URL for the help link
  PRIMARY KEY (`db_no`),
  UNIQUE KEY `db_name_UNIQUE` (`db_name`),
  UNIQUE KEY `db_formurl_UNIQUE` (`db_formurl`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- --------------------------------------------------------
-- Seed data for dbaccess
-- Replace ALL placeholder values before use.
--
-- db_accessun / db_accesspw: the MySQL user that the PHP app
--   uses to read/write the colony database (NOT the userbook
--   read-only account from config.php).
--
-- db_formurl: the full URL users will be sent to after login,
--   e.g. 'http://192.168.1.10/mousebook/index.php'
--
-- db_host: hostname/IP of the MySQL server for this colony DB.
--   Usually the same as server_ip in config.php.
-- --------------------------------------------------------

INSERT IGNORE INTO `dbaccess`
  (`db_no`, `db_name`, `db_accessun`, `db_accesspw`, `db_formurl`,
   `db_host`, `db_subject_plural`, `db_subject_single`,
   `db_guide1_title`, `db_guide1_url`)
VALUES
  (1,
   'animalbook',               -- colony database name (match your MySQL DB)
   'YOUR_COLONY_DB_USER',      -- MySQL user for animalbook
   'YOUR_COLONY_DB_PASSWORD',  -- MySQL password for that user
   'http://YOUR_SERVER/mousebook/index.php', -- URL to your Mousebook install
   'localhost',                -- MySQL host for animalbook (often localhost)
   'mice',                     -- plural subject label shown in UI
   'mouse',                    -- singular subject label shown in UI
   NULL,                       -- optional help link title (or NULL)
   NULL                        -- optional help link URL (or NULL)
  );

-- Add more rows here if you have additional colony databases,
-- e.g. a separate ratbook:
--
-- INSERT INTO `dbaccess` (...) VALUES
--   (2, 'ratbook', 'YOUR_RAT_DB_USER', 'YOUR_RAT_DB_PASSWORD',
--    'http://YOUR_SERVER/ratbook/index.php', 'localhost',
--    'rats', 'rat', NULL, NULL);


-- --------------------------------------------------------
-- Table: userdbaccess
-- Links users (by user_idno) to databases they can access.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `userdbaccess` (
  `user_idno`     bigint(20)   NOT NULL,
  `link_number`   bigint(20)   NOT NULL AUTO_INCREMENT,
  `db_name`       varchar(45)  NOT NULL,
  `db_accesstier` varchar(45)  NOT NULL,  -- reserved for future role-based access
  PRIMARY KEY (`link_number`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

-- Grant user_idno=1 (the admin created below) access to animalbook.
-- Add more rows for additional users or databases as needed.

INSERT IGNORE INTO `userdbaccess` (`user_idno`, `link_number`, `db_name`, `db_accesstier`)
VALUES
  (1, 1, 'animalbook', '1');


-- --------------------------------------------------------
-- Table: userdetail
-- Optional contact information per user.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `userdetail` (
  `user_idno`   bigint(20)  NOT NULL,
  `user_email`  varchar(45) DEFAULT NULL,
  `user_phone`  varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_idno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Replace with real contact details, or leave NULL.

INSERT IGNORE INTO `userdetail` (`user_idno`, `user_email`, `user_phone`)
VALUES
  (1, 'YOUR_EMAIL@EXAMPLE.COM', 'YOUR_PHONE_NUMBER');


-- --------------------------------------------------------
-- Table: userpass
-- Stores login credentials.
-- WARNING: passwords are stored in plain text in this version.
--   Use strong, unique passwords. A hashed-password upgrade
--   is planned for a future release.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `userpass` (
  `user_idno`  bigint(20)  NOT NULL AUTO_INCREMENT,
  `user_name`  varchar(45) NOT NULL,
  `user_pass`  varchar(45) NOT NULL,
  `user_salt`  varchar(45) NOT NULL,
  PRIMARY KEY (`user_idno`),
  UNIQUE KEY `user_idno_UNIQUE` (`user_idno`),
  UNIQUE KEY `username_UNIQUE` (`user_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=2;

-- Default admin account. CHANGE THE PASSWORD before use.
-- Add more rows for additional users (each needs a row in
-- userdbaccess too to grant them database access).

INSERT IGNORE INTO `userpass` (`user_idno`, `user_name`, `user_pass`, `user_salt`)
VALUES
  (1, 'admin', 'CHANGE_THIS_PASSWORD', '');

-- Example: add a second user
-- INSERT INTO `userpass` (`user_name`, `user_pass`, `user_salt`)
-- VALUES ('labuser1', 'THEIR_PASSWORD', '');
-- Then grant them access:
-- INSERT INTO `userdbaccess` (`user_idno`, `db_name`, `db_accesstier`)
-- VALUES (LAST_INSERT_ID(), 'animalbook', '1');


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
