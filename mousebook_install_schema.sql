-- =====================================================================
-- Mousebook - authoritative install schema (animalbook)
-- =====================================================================
-- Generated from a live `mysqldump --no-data --routines animalbook`
-- (MySQL 8.0.45, 2026-07-05), then made install-portable:
--   * DEFINER clauses stripped; views set to SQL SECURITY INVOKER
--     (no dependency on the `realchrisward`/`root` accounts)
--   * minimal seed data appended (see SEED DATA section near the end)
--
-- This replaces the incomplete repo file default_animalbook.sql
-- (which was missing table_animals, conversion_geno, reservations_animals,
--  list_cage_locations, list_cage_role_assignments and more).
--
-- Load:  mysql -u <admin> -p < mousebook_install_schema.sql
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
-- different db name than `animalbook` (must match config.php).
--
CREATE DATABASE IF NOT EXISTS `animalbook` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;
USE `animalbook`;
--
-- Table structure for table `CagesForInfo`
--

DROP TABLE IF EXISTS `CagesForInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CagesForInfo` (
  `cageid` varchar(255) NOT NULL,
  PRIMARY KEY (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `CagesForPrinting`
--

DROP TABLE IF EXISTS `CagesForPrinting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CagesForPrinting` (
  `cageid` varchar(255) NOT NULL,
  PRIMARY KEY (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_CagesInCohorts`
--

DROP TABLE IF EXISTS `Study_CagesInCohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_CagesInCohorts` (
  `StudyCageKey` bigint NOT NULL AUTO_INCREMENT,
  `StudyCageNumber` varchar(45) NOT NULL,
  `StudyCageName` varchar(45) NOT NULL,
  `StudyCageAlias` varchar(45) DEFAULT NULL,
  `CohortKey` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`StudyCageKey`),
  UNIQUE KEY `StudyCageNumber_UNIQUE` (`StudyCageKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_Cohorts`
--

DROP TABLE IF EXISTS `Study_Cohorts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_Cohorts` (
  `CohortKey` bigint NOT NULL AUTO_INCREMENT,
  `StudyNumber` bigint NOT NULL,
  `CohortName` varchar(45) NOT NULL,
  `CohortDesc` text,
  PRIMARY KEY (`CohortKey`),
  UNIQUE KEY `CohortNumber_UNIQUE` (`CohortKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_GroupAlias`
--

DROP TABLE IF EXISTS `Study_GroupAlias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_GroupAlias` (
  `AliasKey` bigint NOT NULL AUTO_INCREMENT,
  `GroupKey` bigint DEFAULT NULL,
  `AliasText` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`AliasKey`),
  UNIQUE KEY `AliasKey_UNIQUE` (`AliasKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_GroupInfo`
--

DROP TABLE IF EXISTS `Study_GroupInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_GroupInfo` (
  `GroupKey` bigint NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(45) NOT NULL,
  `GroupDesc` varchar(45) DEFAULT NULL,
  `StudyKey` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`GroupKey`),
  UNIQUE KEY `GroupKey_UNIQUE` (`GroupKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_Info`
--

DROP TABLE IF EXISTS `Study_Info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_Info` (
  `StudyNumber` bigint NOT NULL,
  `StudyName` varchar(45) NOT NULL,
  `StudyDesc` text,
  PRIMARY KEY (`StudyNumber`),
  UNIQUE KEY `StudyNumber_UNIQUE` (`StudyNumber`),
  UNIQUE KEY `StudyName_UNIQUE` (`StudyName`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_animals`
--

DROP TABLE IF EXISTS `Study_animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_animals` (
  `StudyanimalKey` bigint NOT NULL AUTO_INCREMENT,
  `animalAutoNo` bigint NOT NULL,
  `FlagReclip` varchar(45) DEFAULT NULL,
  `FlagGenoConf` varchar(45) DEFAULT NULL,
  `FlagExclude` varchar(45) DEFAULT NULL,
  `StudyCageKey` bigint DEFAULT NULL,
  `StudyCohortKey` bigint DEFAULT NULL,
  `StudyKey` bigint DEFAULT NULL,
  PRIMARY KEY (`StudyanimalKey`),
  UNIQUE KEY `StudyanimalKey_UNIQUE` (`StudyanimalKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Study_animalsGroups`
--

DROP TABLE IF EXISTS `Study_animalsGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Study_animalsGroups` (
  `MG_LinkKey` bigint NOT NULL AUTO_INCREMENT,
  `StudyanimalKey` bigint DEFAULT NULL,
  `GroupAliasKey` bigint DEFAULT NULL,
  PRIMARY KEY (`MG_LinkKey`),
  UNIQUE KEY `MG_LinkKey_UNIQUE` (`MG_LinkKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `conversion_geno`
--

DROP TABLE IF EXISTS `conversion_geno`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_geno` (
  `autono` int NOT NULL AUTO_INCREMENT,
  `allelegroupscombo` varchar(255) DEFAULT NULL,
  `genotype` varchar(255) DEFAULT NULL,
  `genoshort` varchar(45) DEFAULT NULL,
  `goodgeno` binary(1) DEFAULT NULL,
  `expgeno` int DEFAULT NULL,
  PRIMARY KEY (`autono`),
  UNIQUE KEY `autono_UNIQUE` (`autono`)
) ENGINE=MyISAM AUTO_INCREMENT=231 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `data_comments`
--

DROP TABLE IF EXISTS `data_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_comments` (
  `commentid` bigint NOT NULL AUTO_INCREMENT,
  `animalautono` bigint DEFAULT NULL,
  `commentdate` datetime DEFAULT NULL,
  `general_comment` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`commentid`),
  UNIQUE KEY `commentid_UNIQUE` (`commentid`),
  KEY `fk_data_comments_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM AUTO_INCREMENT=9628 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `data_weights`
--

DROP TABLE IF EXISTS `data_weights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_weights` (
  `measurementid` bigint NOT NULL AUTO_INCREMENT,
  `dom` datetime DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint DEFAULT NULL,
  PRIMARY KEY (`measurementid`),
  UNIQUE KEY `measurementid_UNIQUE` (`measurementid`),
  KEY `fk_data_weights_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `good_genos`
--

DROP TABLE IF EXISTS `good_genos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `good_genos` (
  `idgood_genos` int NOT NULL AUTO_INCREMENT,
  `line` varchar(45) DEFAULT NULL,
  `allele1` varchar(45) DEFAULT NULL,
  `geno1` varchar(45) DEFAULT NULL,
  `allele2` varchar(45) DEFAULT NULL,
  `geno2` varchar(45) DEFAULT NULL,
  `allele3` varchar(45) DEFAULT NULL,
  `geno3` varchar(45) DEFAULT NULL,
  `alleles_needed` int DEFAULT NULL,
  PRIMARY KEY (`idgood_genos`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `key_allelebyline`
--

DROP TABLE IF EXISTS `key_allelebyline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `key_allelebyline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `line` varchar(255) DEFAULT NULL,
  `allelegroup` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_key_allelebyline_list_allelegroup1_idx` (`allelegroup`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `key_allelegroupbygenotypingrxn`
--

DROP TABLE IF EXISTS `key_allelegroupbygenotypingrxn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `key_allelegroupbygenotypingrxn` (
  `id` int NOT NULL AUTO_INCREMENT,
  `allelegroup` varchar(255) DEFAULT NULL,
  `genotypingrxn` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_key_allelegroupbygenotypingrxn_list_allelegroup1_idx` (`allelegroup`),
  KEY `fk_key_allelegroupbygenotypingrxn_list_genotypingrxns1_idx` (`genotypingrxn`)
) ENGINE=MyISAM AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_allele`
--

DROP TABLE IF EXISTS `list_allele`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_allele` (
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(255) DEFAULT NULL,
  `genderspecific` varchar(45) DEFAULT NULL,
  `indexkey` int NOT NULL AUTO_INCREMENT,
  `notes` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`indexkey`),
  UNIQUE KEY `indexkey_UNIQUE` (`indexkey`),
  KEY `fk_list_allele_list_allelegroup1_idx` (`allelegroup`)
) ENGINE=MyISAM AUTO_INCREMENT=330 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_allelegroup`
--

DROP TABLE IF EXISTS `list_allelegroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_allelegroup` (
  `allelegroup` varchar(255) NOT NULL,
  `gene` varchar(255) NOT NULL,
  `reference` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`allelegroup`),
  UNIQUE KEY `allelegroup_UNIQUE` (`allelegroup`),
  KEY `fk_list_allelegroup_list_gene_idx` (`gene`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_cage_locations`
--

DROP TABLE IF EXISTS `list_cage_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_cage_locations` (
  `Location_Option` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`Location_Option`),
  UNIQUE KEY `Location_Option_UNIQUE` (`Location_Option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_cage_role_assignments`
--

DROP TABLE IF EXISTS `list_cage_role_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_cage_role_assignments` (
  `roleassignment_option` varchar(255) NOT NULL,
  `roleassignment_statuslist` varchar(255) DEFAULT NULL,
  `maincontact` varchar(255) DEFAULT NULL,
  `notes` varchar(1024) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`roleassignment_option`),
  UNIQUE KEY `roleassignment_option_UNIQUE` (`roleassignment_option`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_gene`
--

DROP TABLE IF EXISTS `list_gene`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_gene` (
  `gene` varchar(255) NOT NULL,
  PRIMARY KEY (`gene`),
  UNIQUE KEY `gene_UNIQUE` (`gene`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_genotypingprimers`
--

DROP TABLE IF EXISTS `list_genotypingprimers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_genotypingprimers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `primerseq` varchar(255) DEFAULT NULL,
  `primername` varchar(255) DEFAULT NULL,
  `genotypingrxn` varchar(255) DEFAULT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_list_genotypingprimers_list_genotypingrxns1_idx` (`genotypingrxn`)
) ENGINE=MyISAM AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_genotypingrxns`
--

DROP TABLE IF EXISTS `list_genotypingrxns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_genotypingrxns` (
  `genotypingrxn` varchar(255) NOT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  `recommendedcycle` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`genotypingrxn`),
  UNIQUE KEY `genotypingrxn_UNIQUE` (`genotypingrxn`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_lengthone`
--

DROP TABLE IF EXISTS `list_lengthone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_lengthone` (
  `id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_numbers`
--

DROP TABLE IF EXISTS `list_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_numbers` (
  `number_list` int NOT NULL,
  PRIMARY KEY (`number_list`),
  UNIQUE KEY `number_list_UNIQUE` (`number_list`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `list_strains`
--

DROP TABLE IF EXISTS `list_strains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `list_strains` (
  `strains` varchar(45) NOT NULL,
  PRIMARY KEY (`strains`),
  UNIQUE KEY `strains_UNIQUE` (`strains`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservations_animals`
--

DROP TABLE IF EXISTS `reservations_animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_animals` (
  `maxautono` bigint NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `line` varchar(255) DEFAULT NULL,
  `maxidno` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`maxautono`),
  UNIQUE KEY `maxautono_UNIQUE` (`maxautono`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reservations_cages`
--

DROP TABLE IF EXISTS `reservations_cages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_cages` (
  `reservationno` bigint NOT NULL AUTO_INCREMENT,
  `user` varchar(255) DEFAULT NULL,
  `lineassignment` varchar(255) DEFAULT NULL,
  `cagetype` varchar(255) DEFAULT NULL,
  `maxcageno` bigint DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`reservationno`),
  UNIQUE KEY `reservationno_UNIQUE` (`reservationno`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_animals`
--

DROP TABLE IF EXISTS `table_animals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_animals` (
  `animalautono` bigint NOT NULL AUTO_INCREMENT,
  `line` varchar(255) DEFAULT NULL,
  `idno` varchar(255) DEFAULT NULL,
  `gender` varchar(45) DEFAULT NULL,
  `eartag` varchar(45) DEFAULT NULL,
  `dob` datetime DEFAULT NULL,
  `dow` datetime DEFAULT NULL,
  `dod` datetime DEFAULT NULL,
  `matingcage` varchar(255) DEFAULT NULL,
  `currentcage` varchar(255) DEFAULT NULL,
  `parents` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`),
  KEY `fk_table_animals_table_lines1_idx` (`line`),
  KEY `fk_table_animals_table_cages1_idx` (`currentcage`)
) ENGINE=MyISAM AUTO_INCREMENT=3094 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_cages`
--

DROP TABLE IF EXISTS `table_cages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_cages` (
  `cageid` varchar(255) NOT NULL,
  `cagetype` varchar(45) DEFAULT NULL,
  `setupdate` datetime DEFAULT NULL,
  `stopdate` datetime DEFAULT NULL,
  `cageactive` varchar(1) DEFAULT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  `lineassignment` varchar(255) DEFAULT NULL,
  `cageno` bigint NOT NULL,
  `cagecontents` varchar(1024) DEFAULT NULL,
  `cagelocation_room` varchar(255) DEFAULT NULL,
  `cagerole_assignment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cageid`),
  UNIQUE KEY `cageid_UNIQUE` (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_deadpups`
--

DROP TABLE IF EXISTS `table_deadpups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_deadpups` (
  `cageid` char(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `dod` date DEFAULT NULL,
  `death_autono` bigint NOT NULL AUTO_INCREMENT,
  `comments` text,
  `death_type` char(45) DEFAULT NULL,
  PRIMARY KEY (`death_autono`),
  UNIQUE KEY `death_autono_UNIQUE` (`death_autono`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_genotypes`
--

DROP TABLE IF EXISTS `table_genotypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_genotypes` (
  `genoid` bigint NOT NULL AUTO_INCREMENT,
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(45) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint DEFAULT NULL,
  PRIMARY KEY (`genoid`),
  UNIQUE KEY `genoid_UNIQUE` (`genoid`),
  KEY `fk_table_genotypes_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM AUTO_INCREMENT=4762 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_lines`
--

DROP TABLE IF EXISTS `table_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_lines` (
  `line` varchar(255) NOT NULL,
  `line_description` varchar(255) DEFAULT NULL,
  `strain` varchar(255) DEFAULT NULL,
  `ucsd_number` varchar(255) DEFAULT NULL,
  `color_assignment` varchar(45) DEFAULT NULL,
  `card_color` varchar(45) DEFAULT NULL,
  `deactivated_line` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`line`),
  UNIQUE KEY `line_UNIQUE` (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `table_litterlog`
--

DROP TABLE IF EXISTS `table_litterlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `table_litterlog` (
  `dob` date DEFAULT NULL,
  `line_assign` varchar(255) DEFAULT NULL,
  `cagename` varchar(255) DEFAULT NULL,
  `actual_obs` date DEFAULT NULL,
  `obs_by` varchar(255) DEFAULT NULL,
  `litter name` varchar(255) DEFAULT NULL,
  `estimate_male` int DEFAULT NULL,
  `estimate_female` int DEFAULT NULL,
  `estimate_unknown` int DEFAULT NULL,
  `litter_comments` text,
  `just_sac` varchar(45) DEFAULT NULL,
  `litterlog_autono` bigint NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`litterlog_autono`),
  UNIQUE KEY `litterlog_autono_UNIQUE` (`litterlog_autono`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_cage1`
--

DROP TABLE IF EXISTS `temp_cage1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_cage1` (
  `animalautono` bigint NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_cage2`
--

DROP TABLE IF EXISTS `temp_cage2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_cage2` (
  `animalautono` bigint NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_cage3`
--

DROP TABLE IF EXISTS `temp_cage3`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_cage3` (
  `animalautono` bigint NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_cage4`
--

DROP TABLE IF EXISTS `temp_cage4`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_cage4` (
  `animalautono` bigint NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_comments`
--

DROP TABLE IF EXISTS `temp_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_comments` (
  `commentid` bigint NOT NULL,
  `animalautono` bigint DEFAULT NULL,
  `commentdate` datetime DEFAULT NULL,
  `general_comment` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`commentid`),
  UNIQUE KEY `commentid_UNIQUE` (`commentid`),
  KEY `fk_temp_comments_temp_createanimals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_createanimals`
--

DROP TABLE IF EXISTS `temp_createanimals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_createanimals` (
  `animalautono` bigint NOT NULL,
  `line` varchar(255) DEFAULT NULL,
  `idno` varchar(255) DEFAULT NULL,
  `gender` varchar(45) DEFAULT NULL,
  `dob` datetime DEFAULT NULL,
  `dow` datetime DEFAULT NULL,
  `dod` datetime DEFAULT NULL,
  `matingcage` varchar(255) DEFAULT NULL,
  `currentcage` varchar(255) DEFAULT NULL,
  `parents` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_genotypes`
--

DROP TABLE IF EXISTS `temp_genotypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_genotypes` (
  `genoid` bigint NOT NULL,
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(45) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint DEFAULT NULL,
  PRIMARY KEY (`genoid`),
  UNIQUE KEY `genoid_UNIQUE` (`genoid`),
  KEY `fk_temp_genotypes_temp_createanimals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `temp_shufcg1`
--

DROP TABLE IF EXISTS `temp_shufcg1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `temp_shufcg1` (
  `animalautono` bigint NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `view_activeanimals`
--

DROP TABLE IF EXISTS `view_activeanimals`;
/*!50001 DROP VIEW IF EXISTS `view_activeanimals`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_activeanimals` AS SELECT 
 1 AS `cageno`,
 1 AS `cagetype`,
 1 AS `lineassignment`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `gender`,
 1 AS `eartag`,
 1 AS `dob`,
 1 AS `genorxn`,
 1 AS `genotype`,
 1 AS `genoshort`,
 1 AS `matingcage`,
 1 AS `location`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_activeanimals_sub1`
--

DROP TABLE IF EXISTS `view_activeanimals_sub1`;
/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub1`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_activeanimals_sub1` AS SELECT 
 1 AS `animalautono`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `dob`,
 1 AS `dod`,
 1 AS `gender`,
 1 AS `eartag`,
 1 AS `matingcage`,
 1 AS `currentcage`,
 1 AS `allelegroup`,
 1 AS `allele`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_activeanimals_sub2`
--

DROP TABLE IF EXISTS `view_activeanimals_sub2`;
/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub2`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_activeanimals_sub2` AS SELECT 
 1 AS `cageno`,
 1 AS `cagetype`,
 1 AS `cagelocation`,
 1 AS `lineassignment`,
 1 AS `cageid`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `gender`,
 1 AS `eartag`,
 1 AS `dob`,
 1 AS `genorxn`,
 1 AS `genotype`,
 1 AS `matingcage`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_cagestatus`
--

DROP TABLE IF EXISTS `view_cagestatus`;
/*!50001 DROP VIEW IF EXISTS `view_cagestatus`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_cagestatus` AS SELECT 
 1 AS `lineassignment`,
 1 AS `mating count`,
 1 AS `holding Total`,
 1 AS `litter count`,
 1 AS `holding count 1Mo`,
 1 AS `holding count 2Mo`,
 1 AS `holding count 3Mo`,
 1 AS `holding count 4Mo`,
 1 AS `holding count 5Mo`,
 1 AS `holding count 6Mo`,
 1 AS `holding count >6Mo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_cagestatus_sub1`
--

DROP TABLE IF EXISTS `view_cagestatus_sub1`;
/*!50001 DROP VIEW IF EXISTS `view_cagestatus_sub1`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_cagestatus_sub1` AS SELECT 
 1 AS `agemonth`,
 1 AS `currentcage`,
 1 AS `cagetype`,
 1 AS `lineassignment`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_goodanimals`
--

DROP TABLE IF EXISTS `view_goodanimals`;
/*!50001 DROP VIEW IF EXISTS `view_goodanimals`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_goodanimals` AS SELECT 
 1 AS `allele`,
 1 AS `allelegroup`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `animalautono`,
 1 AS `dob`,
 1 AS `dod`,
 1 AS `gender`,
 1 AS `cagetype`,
 1 AS `goodgeno`,
 1 AS `numalleles`,
 1 AS `curagedays`,
 1 AS `curagemo`,
 1 AS `curagegrp`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_goodanimals_all`
--

DROP TABLE IF EXISTS `view_goodanimals_all`;
/*!50001 DROP VIEW IF EXISTS `view_goodanimals_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_goodanimals_all` AS SELECT 
 1 AS `allele`,
 1 AS `allelegroup`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `animalautono`,
 1 AS `dob`,
 1 AS `dod`,
 1 AS `gender`,
 1 AS `cagetype`,
 1 AS `goodgeno`,
 1 AS `numalleles`,
 1 AS `curagedays`,
 1 AS `curagemo`,
 1 AS `curagegrp`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_goodanimals_filtered`
--

DROP TABLE IF EXISTS `view_goodanimals_filtered`;
/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_goodanimals_filtered` AS SELECT 
 1 AS `line`,
 1 AS `idno`,
 1 AS `animalautono`,
 1 AS `numalleles`,
 1 AS `curagegrp`,
 1 AS `curagedays`,
 1 AS `curagemo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_goodanimals_filtered_all`
--

DROP TABLE IF EXISTS `view_goodanimals_filtered_all`;
/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_goodanimals_filtered_all` AS SELECT 
 1 AS `line`,
 1 AS `idno`,
 1 AS `animalautono`,
 1 AS `numalleles`,
 1 AS `curagegrp`,
 1 AS `curagedays`,
 1 AS `curagemo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_linestatus`
--

DROP TABLE IF EXISTS `view_linestatus`;
/*!50001 DROP VIEW IF EXISTS `view_linestatus`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_linestatus` AS SELECT 
 1 AS `line`,
 1 AS `animals_0-3mo`,
 1 AS `animals_4-6mo`,
 1 AS `animals_7+`,
 1 AS `matings_0-3mo`,
 1 AS `matings_4+mo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_matingcount`
--

DROP TABLE IF EXISTS `view_matingcount`;
/*!50001 DROP VIEW IF EXISTS `view_matingcount`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_matingcount` AS SELECT 
 1 AS `lineassignment`,
 1 AS `matings_0-3mo`,
 1 AS `matings_4+mo`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_matingstatus`
--

DROP TABLE IF EXISTS `view_matingstatus`;
/*!50001 DROP VIEW IF EXISTS `view_matingstatus`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_matingstatus` AS SELECT 
 1 AS `lineassignment`,
 1 AS `cageid`,
 1 AS `MatingAgeDays`,
 1 AS `MatingAgeMos`,
 1 AS `pupsmade`,
 1 AS `goodpupsmade`,
 1 AS `Litters`,
 1 AS `Dead Litters`,
 1 AS `LastLitterDOB`,
 1 AS `LastDeadLitterDOB`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_unkgenos`
--

DROP TABLE IF EXISTS `view_unkgenos`;
/*!50001 DROP VIEW IF EXISTS `view_unkgenos`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_unkgenos` AS SELECT 
 1 AS `allele`,
 1 AS `allelegroup`,
 1 AS `line`,
 1 AS `idno`,
 1 AS `dob`,
 1 AS `dod`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'animalbook'
--
/*!50003 DROP PROCEDURE IF EXISTS `clear_cages1234` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `clear_cages1234`()
BEGIN
truncate table temp_cage1;
truncate table temp_cage2;
truncate table temp_cage3;
truncate table temp_cage4;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_activecages` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_activecages`()
    NO SQL
    DETERMINISTIC
select `currentcage` from `table_animals` where (dod is null) group by `currentcage`

order by `currentcage` ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_activelines` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_activelines`()
    NO SQL
    DETERMINISTIC
select `line` from `table_lines` where deactivated_line <> "1" or deactivated_line is null

order by `line` asc ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_allelegroups` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_allelegroups`()
    NO SQL
    DETERMINISTIC
Begin
SELECT * FROM list_allelegroup

order by allelegroup;
End ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_cage1` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_cage1`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage4

order by animalautono ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_cage2` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_cage2`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage2

order by animalautono ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_cage3` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_cage3`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage3

order by animalautono ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_cage4` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_cage4`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage4

order by animalautono ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_cagecounts` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_cagecounts`()
    NO SQL
    DETERMINISTIC
select count(distinct(currentcage)), lineassignment 

from table_animals join table_cages on table_animals.currentcage=table_cages.cageid

where dod is null group by lineassignment

order by lineassignment ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_colonystats` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_colonystats`()
    NO SQL
    DETERMINISTIC
select sum(if(dob is not null,1,0)) as totalanimalsindb, 

sum(if(dod is null and dob is not null,1,0)) as aliveanimals, 

sum(if(dod is not null and dob is not null,1,0)) as deadanimals, 

sum(if(dob is null,1,0)) as animalswithnodob, 

concat(month(curdate()),"-",year(curdate())) as currentmonthyr, 

if(month(curdate())-1<=0,concat(month(curdate())-1+12,"-",year(curdate())-1),concat(month(curdate())-1,"-",year(curdate()))) as 1monthprev,

if(month(curdate())-2<=0,concat(month(curdate())-2+12,"-",year(curdate())-1),concat(month(curdate())-2,"-",year(curdate()))) as 2monthprev,

if(month(curdate())-3<=0,concat(month(curdate())-3+12,"-",year(curdate())-1),concat(month(curdate())-3,"-",year(curdate()))) as 3monthprev,

if(month(curdate())-4<=0,concat(month(curdate())-4+12,"-",year(curdate())-1),concat(month(curdate())-4,"-",year(curdate()))) as 4monthprev,

if(month(curdate())-5<=0,concat(month(curdate())-5+12,"-",year(curdate())-1),concat(month(curdate())-5,"-",year(curdate()))) as 5monthprev,

if(month(curdate())-6<=0,concat(month(curdate())-6+12,"-",year(curdate())-1),concat(month(curdate())-6,"-",year(curdate()))) as 6monthprev,

sum(if(concat(month(dob),"-",year(dob))=

	concat(month(curdate()),"-",year(curdate())),

    1,0)) as births_currmonth,

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-1<=0,concat(month(curdate())-1+12,"-",year(curdate())-1),concat(month(curdate())-1,"-",year(curdate()))),

    1,0)) as births_1monthprev, 

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-2<=0,concat(month(curdate())-2+12,"-",year(curdate())-1),concat(month(curdate())-2,"-",year(curdate()))),

    1,0)) as births_2monthprev, 

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-3<=0,concat(month(curdate())-3+12,"-",year(curdate())-1),concat(month(curdate())-3,"-",year(curdate()))),

    1,0)) as births_3monthprev, 

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-4<=0,concat(month(curdate())-4+12,"-",year(curdate())-1),concat(month(curdate())-4,"-",year(curdate()))),

	1,0)) as births_4monthprev, 

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-5<=0,concat(month(curdate())-5+12,"-",year(curdate())-1),concat(month(curdate())-5,"-",year(curdate()))),

    1,0)) as births_5monthprev, 

sum(if(concat(month(dob),"-",year(dob))=

	if(month(curdate())-6<=0,concat(month(curdate())-6+12,"-",year(curdate())-1),concat(month(curdate())-6,"-",year(curdate()))),

	1,0)) as births_6monthprev, 

    

sum(if(concat(month(dod),"-",year(dod))=

	concat(month(curdate()),"-",year(curdate())) and dob is not null,

	1,0)) as deaths_currmonth,

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-1<=0,concat(month(curdate())-1+12,"-",year(curdate())-1),concat(month(curdate())-1,"-",year(curdate())))

     and dob is not null,

    1,0)) as deaths_1monthprev, 

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-2<=0,concat(month(curdate())-2+12,"-",year(curdate())-1),concat(month(curdate())-2,"-",year(curdate())))

     and dob is not null,

	1,0)) as deaths_2monthprev, 

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-3<=0,concat(month(curdate())-3+12,"-",year(curdate())-1),concat(month(curdate())-3,"-",year(curdate())))

     and dob is not null,

    1,0)) as deaths_3monthprev, 

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-4<=0,concat(month(curdate())-4+12,"-",year(curdate())-1),concat(month(curdate())-4,"-",year(curdate())))

     and dob is not null,

    1,0)) as deaths_4monthprev, 

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-5<=0,concat(month(curdate())-5+12,"-",year(curdate())-1),concat(month(curdate())-5,"-",year(curdate())))

     and dob is not null,

    1,0)) as deaths_5monthprev, 

sum(if(concat(month(dod),"-",year(dod))=

	if(month(curdate())-6<=0,concat(month(curdate())-6+12,"-",year(curdate())-1),concat(month(curdate())-6,"-",year(curdate())))

     and dob is not null,

    1,0)) as deaths_6monthprev

from table_animals ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_genes` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_genes`()
    NO SQL
    DETERMINISTIC
BEGIN
SELECT * FROM `list_gene`

order by gene;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_genorxns` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_genorxns`()
    NO SQL
    DETERMINISTIC
SELECT * FROM list_genotypingrxns order by genotypingrxn asc ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_lines` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_lines`()
    NO SQL
    DETERMINISTIC
select * from `table_lines` 

order by `line` asc ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_strains` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_strains`()
    NO SQL
    DETERMINISTIC
SELECT * FROM `list_strains` 

order by strains ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_weanlist` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE PROCEDURE `get_weanlist`()
    NO SQL
    DETERMINISTIC
select `currentcage`,max(datediff(curdate(),`dob`)) as age from `table_animals` 

where (dod is null and (left(currentcage,1)="L" or left(currentcage,1)="F")) 

group by `currentcage` order by age desc, currentcage asc ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `view_activeanimals`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals` AS select `y`.`cageno` AS `cageno`,`y`.`cagetype` AS `cagetype`,`y`.`lineassignment` AS `lineassignment`,`y`.`line` AS `line`,`y`.`idno` AS `idno`,`y`.`gender` AS `gender`,`y`.`eartag` AS `eartag`,`y`.`dob` AS `dob`,`y`.`genorxn` AS `genorxn`,`y`.`genotype` AS `genotype`,`conversion_geno`.`genoshort` AS `genoshort`,`y`.`matingcage` AS `matingcage`,`y`.`cagelocation` AS `location` from (`view_activeanimals_sub2` `y` left join `conversion_geno` on(((`y`.`genorxn` = convert(`conversion_geno`.`allelegroupscombo` using utf8mb3)) and (`y`.`genotype` = convert(`conversion_geno`.`genotype` using utf8mb3))))) order by `y`.`lineassignment`,field(`y`.`cagetype`,'holding','rearrange','experimental','mating','litter','sac'),`y`.`cageno`,`y`.`line`,`y`.`idno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_activeanimals_sub1`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals_sub1` AS select `table_animals`.`animalautono` AS `animalautono`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`gender` AS `gender`,`table_animals`.`eartag` AS `eartag`,`table_animals`.`matingcage` AS `matingcage`,`table_animals`.`currentcage` AS `currentcage`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_genotypes`.`allele` AS `allele` from (`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) order by `table_animals`.`animalautono`,`table_genotypes`.`allelegroup` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_activeanimals_sub2`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals_sub2` AS select `table_cages`.`cageno` AS `cageno`,`table_cages`.`cagetype` AS `cagetype`,`table_cages`.`cagelocation_room` AS `cagelocation`,`table_cages`.`lineassignment` AS `lineassignment`,`table_cages`.`cageid` AS `cageid`,`x`.`line` AS `line`,`x`.`idno` AS `idno`,`x`.`gender` AS `gender`,`x`.`eartag` AS `eartag`,`x`.`dob` AS `dob`,group_concat(`x`.`allelegroup` order by `x`.`allelegroup` ASC separator '; ') AS `genorxn`,group_concat(`x`.`allele` order by `x`.`allelegroup` ASC separator '; ') AS `genotype`,`x`.`matingcage` AS `matingcage` from (`view_activeanimals_sub1` `x` join `table_cages` on((`x`.`currentcage` = `table_cages`.`cageid`))) where ((`x`.`dod` is null) and (`x`.`dob` is not null)) group by `x`.`line`,`x`.`idno`,`x`.`gender`,`x`.`eartag`,`x`.`dob`,`x`.`matingcage`,`table_cages`.`cageno`,`table_cages`.`cagetype`,`table_cages`.`cagelocation_room`,`table_cages`.`lineassignment`,`table_cages`.`cageid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_cagestatus`
--

/*!50001 DROP VIEW IF EXISTS `view_cagestatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_cagestatus` AS select `activcages`.`lineassignment` AS `lineassignment`,sum(if((`activcages`.`cagetype` = 'Mating'),1,0)) AS `mating count`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter')),1,0)) AS `holding Total`,sum(if((`activcages`.`cagetype` = 'Litter'),1,0)) AS `litter count`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` <= 1)),1,0)) AS `holding count 1Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 2)),1,0)) AS `holding count 2Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 3)),1,0)) AS `holding count 3Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 4)),1,0)) AS `holding count 4Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 5)),1,0)) AS `holding count 5Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 6)),1,0)) AS `holding count 6Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` > 6)),1,0)) AS `holding count >6Mo` from `view_cagestatus_sub1` `activcages` group by `activcages`.`lineassignment` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_cagestatus_sub1`
--

/*!50001 DROP VIEW IF EXISTS `view_cagestatus_sub1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_cagestatus_sub1` AS select round((avg((to_days(curdate()) - to_days(`table_animals`.`dob`))) / 28),0) AS `agemonth`,`table_animals`.`currentcage` AS `currentcage`,`table_cages`.`cagetype` AS `cagetype`,`table_cages`.`lineassignment` AS `lineassignment` from (`table_animals` join `table_cages` on((`table_cages`.`cageid` = `table_animals`.`currentcage`))) where ((`table_animals`.`dob` is not null) and (`table_animals`.`dod` is null)) group by `table_cages`.`lineassignment`,`table_cages`.`cagetype`,`table_animals`.`currentcage` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_goodanimals`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`gender` AS `gender`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) where (`table_animals`.`dod` is null) having ((`goodgeno` = `table_genotypes`.`allele`) and ((`cagetype` = 'H') or (`cagetype` = 'L'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_goodanimals_all`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_all` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`gender` AS `gender`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) having (`goodgeno` = `table_genotypes`.`allele`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_goodanimals_filtered`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_filtered` AS select `v`.`line` AS `line`,`v`.`idno` AS `idno`,`v`.`animalautono` AS `animalautono`,`v`.`numalleles` AS `numalleles`,`v`.`curagegrp` AS `curagegrp`,`v`.`curagedays` AS `curagedays`,`v`.`curagemo` AS `curagemo` from `view_goodanimals` `v` group by `v`.`line`,`v`.`idno`,`v`.`animalautono`,`v`.`numalleles`,`v`.`curagegrp`,`v`.`curagedays`,`v`.`curagemo` having (count(0) = `v`.`numalleles`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_goodanimals_filtered_all`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_filtered_all` AS select `v`.`line` AS `line`,`v`.`idno` AS `idno`,`v`.`animalautono` AS `animalautono`,`v`.`numalleles` AS `numalleles`,`v`.`curagegrp` AS `curagegrp`,`v`.`curagedays` AS `curagedays`,`v`.`curagemo` AS `curagemo` from `view_goodanimals_all` `v` group by `v`.`line`,`v`.`idno`,`v`.`animalautono`,`v`.`numalleles`,`v`.`curagegrp`,`v`.`curagedays`,`v`.`curagemo` having (count(0) = `v`.`numalleles`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_linestatus`
--

/*!50001 DROP VIEW IF EXISTS `view_linestatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_linestatus` AS select `table_lines`.`line` AS `line`,sum(if((`view_goodanimals_filtered`.`curagemo` <= 3),1,0)) AS `animals_0-3mo`,sum(if(((`view_goodanimals_filtered`.`curagemo` > 3) and (`view_goodanimals_filtered`.`curagemo` <= 6)),1,0)) AS `animals_4-6mo`,sum(if((`view_goodanimals_filtered`.`curagemo` > 6),1,0)) AS `animals_7+`,`view_matingcount`.`matings_0-3mo` AS `matings_0-3mo`,`view_matingcount`.`matings_4+mo` AS `matings_4+mo` from ((`table_lines` left join `view_goodanimals_filtered` on((`table_lines`.`line` = `view_goodanimals_filtered`.`line`))) left join `view_matingcount` on((`table_lines`.`line` = convert(`view_matingcount`.`lineassignment` using utf8mb3)))) group by `table_lines`.`line`,`view_matingcount`.`matings_0-3mo`,`view_matingcount`.`matings_4+mo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_matingcount`
--

/*!50001 DROP VIEW IF EXISTS `view_matingcount`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_matingcount` AS select `view_matingstatus`.`lineassignment` AS `lineassignment`,sum(if((`view_matingstatus`.`MatingAgeMos` <= 3),1,0)) AS `matings_0-3mo`,sum(if((`view_matingstatus`.`MatingAgeMos` > 3),1,0)) AS `matings_4+mo` from `view_matingstatus` group by `view_matingstatus`.`lineassignment` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_matingstatus`
--

/*!50001 DROP VIEW IF EXISTS `view_matingstatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_matingstatus` AS select `table_cages`.`lineassignment` AS `lineassignment`,`table_cages`.`cageid` AS `cageid`,(to_days(curdate()) - to_days(`table_cages`.`setupdate`)) AS `MatingAgeDays`,floor(((to_days(curdate()) - to_days(`table_cages`.`setupdate`)) / 30)) AS `MatingAgeMos`,count(distinct `table_pups`.`idno`) AS `pupsmade`,count(distinct `view_goodanimals_filtered_all`.`idno`) AS `goodpupsmade`,count(distinct `table_pups`.`dob`) AS `Litters`,count(distinct `table_deadpups`.`dob`) AS `Dead Litters`,max(`table_pups`.`dob`) AS `LastLitterDOB`,max(`table_deadpups`.`dob`) AS `LastDeadLitterDOB` from (((`table_cages` left join `table_animals` on((`table_cages`.`cageid` = `table_animals`.`currentcage`))) left join (`table_animals` `table_pups` left join `view_goodanimals_filtered_all` on((`table_pups`.`animalautono` = `view_goodanimals_filtered_all`.`animalautono`))) on((`table_cages`.`cageid` = `table_pups`.`matingcage`))) left join `table_deadpups` on((`table_cages`.`cageid` = `table_deadpups`.`cageid`))) where ((`table_cages`.`cagetype` = 'Mating') and (`table_animals`.`gender` = 'F') and (`table_animals`.`dod` is null)) group by `table_cages`.`cageid` order by `table_cages`.`lineassignment`,`table_cages`.`cageno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_unkgenos`
--

/*!50001 DROP VIEW IF EXISTS `view_unkgenos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_unkgenos` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod` from (`table_genotypes` join `table_animals` on((`table_genotypes`.`animalautono` = `table_animals`.`animalautono`))) where ((`table_genotypes`.`allele` = 'unk') and (`table_animals`.`dod` is null)) order by `table_genotypes`.`allelegroup`,`table_animals`.`line`,cast(`table_animals`.`idno` as unsigned),`table_animals`.`idno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
--
-- ---------------------------------------------------------------------
-- SEED DATA  (minimal - required for a functioning fresh install)
-- ---------------------------------------------------------------------
--
-- "Limbo" default cage location. Assign-mode in add_animals.php and
-- manage_cages.php stamps founder / unplaced cages with the location
-- string 'Limbo'; this row makes it a real, active, selectable option
-- in list_cage_locations so those cages don't reference a missing value.
--
INSERT INTO `list_cage_locations` (`Location_Option`, `active`)
  VALUES ('Limbo', 1)
  ON DUPLICATE KEY UPDATE `active` = VALUES(`active`);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-05 17:20:34

