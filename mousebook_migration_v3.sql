-- =============================================================
-- Mousebook Database Migration v3
-- Adds the missing `table_litterlog` table used by litterlogger.php.
--
-- litterlogger.php INSERTs into and SELECTs from this table, but it
-- was never present in default_animalbook.sql, so the page fataled
-- on load ("Table '<db>.table_litterlog' doesn't exist").
--
-- Run against each live colony database (e.g. 'animalbook'):
--   mysql -u youruser -p animalbook < mousebook_migration_v3.sql
--
-- Safe to re-run: uses CREATE TABLE IF NOT EXISTS.
-- =============================================================


-- -------------------------------------------------------------
-- table_litterlog
--   dob, actual_obs           : DATE   (litter DOB, observation date)
--   line_assign, cagename     : line and source/mating-cage name
--   obs_by                    : username that recorded the litter
--   `litter name`             : generated label (note: contains a space,
--                               matching the column name the PHP references
--                               as `litter name`)
--   estimate_male/female/unknown : integer pup counts
--   litter_comments           : free text
--   just_sac                  : read back by the "JUST SAC (y/n)" column
--                               in the display (not written by the INSERT)
--   litterlog_autono          : surrogate AUTO_INCREMENT primary key
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `table_litterlog` (
  `dob` date DEFAULT NULL,
  `line_assign` varchar(255) DEFAULT NULL,
  `cagename` varchar(255) DEFAULT NULL,
  `actual_obs` date DEFAULT NULL,
  `obs_by` varchar(255) DEFAULT NULL,
  `litter name` varchar(255) DEFAULT NULL,
  `estimate_male` int(11) DEFAULT NULL,
  `estimate_female` int(11) DEFAULT NULL,
  `estimate_unknown` int(11) DEFAULT NULL,
  `litter_comments` text,
  `just_sac` varchar(45) DEFAULT NULL,
  `litterlog_autono` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`litterlog_autono`),
  UNIQUE KEY `litterlog_autono_UNIQUE` (`litterlog_autono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- Verify:
--   SHOW TABLES LIKE 'table_litterlog';
--   DESCRIBE `table_litterlog`;
