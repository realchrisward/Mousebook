-- =============================================================
-- Mousebook Database Migration v1
-- Fixes ALL schema mismatches between default_animalbook.sql
-- and the current PHP codebase.
--
-- Run against your live 'animalbook' database:
--   mysql -u youruser -p animalbook < mousebook_migration_v1.sql
--
-- Safe to re-run: uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS
-- =============================================================


-- -------------------------------------------------------------
-- 1. Add missing `card_color` column to `table_lines`
--    Used by: manage_lines.php, cagecard_printer.php
-- -------------------------------------------------------------
ALTER TABLE `table_lines`
  ADD COLUMN IF NOT EXISTS `card_color` varchar(45) DEFAULT NULL;


-- -------------------------------------------------------------
-- 2. Add missing `cagelocation_room` column to `table_cages`
--    Used by: cage_location.php (SELECT, UPDATE, WHERE filters)
-- -------------------------------------------------------------
ALTER TABLE `table_cages`
  ADD COLUMN IF NOT EXISTS `cagelocation_room` varchar(255) DEFAULT NULL;


-- -------------------------------------------------------------
-- 3. Add missing `cagerole_assignment` column to `table_cages`
--    Used by: cagerole.php (SELECT, UPDATE, WHERE filters)
-- -------------------------------------------------------------
ALTER TABLE `table_cages`
  ADD COLUMN IF NOT EXISTS `cagerole_assignment` varchar(255) DEFAULT NULL;


-- -------------------------------------------------------------
-- 4. Create missing `reservations_animals` table
--    Used by: add_animals.php to reserve auto-increment IDs
--    and prevent duplicate animal numbers during concurrent sessions.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations_animals` (
  `reservation_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user`           varchar(255) DEFAULT NULL,
  `maxautono`      bigint(20)   DEFAULT NULL,
  `maxidno`        bigint(20)   DEFAULT NULL,
  `line`           varchar(255) DEFAULT NULL,
  `reserved_at`    datetime     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_user` (`user`),
  KEY `idx_line` (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- -------------------------------------------------------------
-- 5. Create missing `list_cage_locations` table
--    Used by: cage_location.php to populate location dropdowns.
--    Edit the seeded values below to match your facility's rooms.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `list_cage_locations` (
  `Location_Option` varchar(255) NOT NULL,
  PRIMARY KEY (`Location_Option`),
  UNIQUE KEY `Location_Option_UNIQUE` (`Location_Option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `list_cage_locations` (`Location_Option`) VALUES
  ('Room 1'),
  ('Room 2'),
  ('Room 3'),
  ('Quarantine'),
  ('Procedure Room'),
  ('Storage'),
  ('Limbo');


-- -------------------------------------------------------------
-- 6. Create missing `list_cage_role_assignments` table
--    Used by: cagerole.php and manage_roles.php
--    Edit the seeded values below to match your lab workflow.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `list_cage_role_assignments` (
  `roleassignment_option`     varchar(255)  NOT NULL,
  `roleassignment_statuslist` varchar(255)  DEFAULT NULL,
  `roleassignment_active`     varchar(45)   DEFAULT NULL,
  `maincontact`               varchar(255)  DEFAULT NULL,
  `notes`                     varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`roleassignment_option`),
  UNIQUE KEY `roleassignment_option_UNIQUE` (`roleassignment_option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `list_cage_role_assignments`
  (`roleassignment_option`, `roleassignment_statuslist`, `maincontact`, `notes`) VALUES
  ('Breeding',   'Active,Paused,Stopped', '', ''),
  ('Holding',    'Active,Retired',        '', ''),
  ('Experiment', 'Active,Complete',       '', ''),
  ('Limbo',      'Unassigned',            '', 'Default for unassigned cages');


-- -------------------------------------------------------------
-- Done.
-- Verify with:
--   DESCRIBE table_lines;
--   DESCRIBE table_cages;
--   SHOW TABLES LIKE 'reservations_animals';
--   SELECT * FROM list_cage_locations;
--   SELECT * FROM list_cage_role_assignments;
-- -------------------------------------------------------------
