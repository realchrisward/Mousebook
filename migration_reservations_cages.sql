-- Migration: reservations_cages (Issue #8, cage-transfer concurrency — Vector 2 cageno race)
--
-- Purpose:
--   Backs the cageno reservation mechanism in php/manage_cages.php. When a user opens a
--   cage transfer for a given line + cage type, the page reserves the block of cage numbers
--   it is about to offer, so a second user staging the same line + type concurrently reads
--   past the reservation and cannot mint duplicate cage numbers/names.
--
--   Mirrors the existing reservations_animals table used by php/add_animals.php.
--
-- DEPLOYMENT ORDER (important):
--   Apply this migration to the live animalbook database BEFORE deploying the patched
--   php/manage_cages.php. The patched PHP degrades gracefully if the table is missing
--   (it falls back to the old committed-MAX-only behavior and the race remains open),
--   so applying the migration first is what actually closes the race.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS will not disturb an existing table or its data.
--
-- Apply against the colony (animalbook) database, e.g.:
--   mysql -u <user> -p <animalbook_db_name> < migration_reservations_cages.sql
--
-- Preserve christow:apache ownership on any server-side files; this migration touches only
-- the database, not the filesystem.

CREATE TABLE IF NOT EXISTS `reservations_cages` (
  `reservationno` bigint NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `lineassignment` varchar(255) DEFAULT NULL,
  `cagetype` varchar(255) DEFAULT NULL,
  `maxcageno` bigint DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`reservationno`),
  UNIQUE KEY `reservationno_UNIQUE` (`reservationno`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
