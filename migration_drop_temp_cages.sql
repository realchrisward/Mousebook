-- =====================================================================
-- migration_drop_temp_cages.sql
--
-- Track 0 (T0.4): drop the orphaned legacy cage-staging objects from an
-- existing colony (animalbook) database.
--
-- The temp_cage1..4 (MyISAM) tables and their helper procedures were the
-- pre-Phase-3 shared cage-staging mechanism. Phase 3 (#8 V1, Option A)
-- replaced them with per-session, per-colony integer lists held entirely
-- in $_SESSION['mb_stage'] (see includes/filters.php). No application
-- code references these objects any longer.
--
-- Fresh installs do NOT need this file: mousebook_install_schema.sql no
-- longer defines these objects. Run this only against colony databases
-- created before the Track 0 schema cleanup.
--
-- Idempotent (every statement is IF EXISTS) and safe to re-run. Apply
-- once per animalbook (colony) database, e.g.:
--     mysql -u <user> -p <colony_db> < migration_drop_temp_cages.sql
-- =====================================================================

-- Procedures first (they reference the tables below).
DROP PROCEDURE IF EXISTS `clear_cages1234`;
DROP PROCEDURE IF EXISTS `get_cage1`;
DROP PROCEDURE IF EXISTS `get_cage2`;
DROP PROCEDURE IF EXISTS `get_cage3`;
DROP PROCEDURE IF EXISTS `get_cage4`;

-- Staging tables.
DROP TABLE IF EXISTS `temp_cage1`;
DROP TABLE IF EXISTS `temp_cage2`;
DROP TABLE IF EXISTS `temp_cage3`;
DROP TABLE IF EXISTS `temp_cage4`;
