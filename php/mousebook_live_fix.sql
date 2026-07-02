-- =============================================================
-- Mousebook — live instance fix (animalbook)
-- Target: MySQL 8.0.45 (Oracle MySQL — NOT MariaDB)
--
-- Adds the four schema objects the current code needs that the
-- live database is missing. Derived by diffing live_schema.sql
-- against the PHP codebase:
--
--   table_lines.card_color          -> cagecard_printer.php
--   table_cages.cagerole_assignment -> cagerole.php
--   list_cage_role_assignments      -> manage_roles.php, cagerole.php
--   list_cage_locations             -> cage_location.php
--
-- NOTE ON SYNTAX: MySQL 8 does NOT support `ADD COLUMN IF NOT
-- EXISTS` (that is MariaDB-only), which is why migration v1 only
-- partially applied here. The two ADD COLUMNs below are plain
-- (the columns were confirmed absent on the live DB). Run ONCE.
-- Re-running the ALTERs will error "Duplicate column name" — that
-- simply means they are already applied and is safe to ignore.
--
-- Charset utf8mb3 / ENGINE MyISAM to match the existing tables and
-- avoid illegal-mix-of-collations errors on joins.
--
-- Apply:
--   mysql -u youruser -p animalbook < mousebook_live_fix.sql
-- =============================================================


-- 1. card_color on table_lines (cage-card paper color per line)
ALTER TABLE `table_lines`
  ADD COLUMN `card_color` varchar(45) DEFAULT NULL AFTER `color_assignment`;


-- 2. cagerole_assignment on table_cages
ALTER TABLE `table_cages`
  ADD COLUMN `cagerole_assignment` varchar(255) DEFAULT NULL AFTER `cagelocation_room`;


-- 3. list_cage_role_assignments (managed by manage_roles.php)
CREATE TABLE IF NOT EXISTS `list_cage_role_assignments` (
  `roleassignment_option`     varchar(255)  NOT NULL,
  `roleassignment_statuslist` varchar(255)  DEFAULT NULL,
  `roleassignment_active`     varchar(45)   DEFAULT NULL,
  `maincontact`               varchar(255)  DEFAULT NULL,
  `notes`                     varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`roleassignment_option`),
  UNIQUE KEY `roleassignment_option_UNIQUE` (`roleassignment_option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;


-- 4. list_cage_locations (source list for cage_location.php)
CREATE TABLE IF NOT EXISTS `list_cage_locations` (
  `Location_Option` varchar(255) NOT NULL,
  PRIMARY KEY (`Location_Option`),
  UNIQUE KEY `Location_Option_UNIQUE` (`Location_Option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;


-- Verify:
--   SHOW COLUMNS FROM `table_lines` LIKE 'card_color';
--   SHOW COLUMNS FROM `table_cages` LIKE 'cagerole_assignment';
--   SHOW TABLES LIKE 'list_cage_%';