-- phpMyAdmin SQL Dump
-- version 4.0.10.18
-- https://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Sep 07, 2017 at 09:27 AM
-- Server version: 5.6.36-cll-lve
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `animalbook`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `clear_cages1234`()
BEGIN
truncate table temp_cage1;
truncate table temp_cage2;
truncate table temp_cage3;
truncate table temp_cage4;
END$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_activecages`()
    NO SQL
    DETERMINISTIC
select `currentcage` from `table_animals` where (dod is null) group by `currentcage`

order by `currentcage`$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_activelines`()
    NO SQL
    DETERMINISTIC
select `line` from `table_lines` where deactivated_line <> "1" or deactivated_line is null

order by `line` asc$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_allelegroups`()
    NO SQL
    DETERMINISTIC
Begin
SELECT * FROM list_allelegroup

order by allelegroup;
End$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_cage1`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage4

order by animalautono$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_cage2`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage2

order by animalautono$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_cage3`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage3

order by animalautono$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_cage4`()
    NO SQL
    DETERMINISTIC
SELECT * FROM temp_cage4

order by animalautono$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_cagecounts`()
    NO SQL
    DETERMINISTIC
select count(distinct(currentcage)), lineassignment 

from table_animals join table_cages on table_animals.currentcage=table_cages.cageid

where dod is null group by lineassignment

order by lineassignment$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_colonystats`()
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

from table_animals$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_genes`()
    NO SQL
    DETERMINISTIC
BEGIN
SELECT * FROM `list_gene`

order by gene;
END$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_genorxns`()
    NO SQL
    DETERMINISTIC
SELECT * FROM list_genotypingrxns order by genotypingrxn asc$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_lines`()
    NO SQL
    DETERMINISTIC
select * from `table_lines` 

order by `line` asc$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_strains`()
    NO SQL
    DETERMINISTIC
SELECT * FROM `list_strains` 

order by strains$$

CREATE DEFINER=`realchrisward`@`localhost` PROCEDURE `get_weanlist`()
    NO SQL
    DETERMINISTIC
select `currentcage`,max(datediff(curdate(),`dob`)) as age from `table_animals` 

where (dod is null and (left(currentcage,1)="L" or left(currentcage,1)="F")) 

group by `currentcage` order by age desc, currentcage asc$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `CagesForInfo`
--
-- Creation: Jun 19, 2017 at 06:21 PM
-- Last update: Aug 03, 2017 at 06:29 AM
--

CREATE TABLE IF NOT EXISTS `CagesForInfo` (
  `cageid` varchar(255) NOT NULL,
  PRIMARY KEY (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `CagesForPrinting`
--
-- Creation: May 06, 2017 at 07:31 PM
-- Last update: Aug 03, 2017 at 09:46 PM
--

CREATE TABLE IF NOT EXISTS `CagesForPrinting` (
  `cageid` varchar(255) NOT NULL,
  PRIMARY KEY (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `Study_CagesInCohorts`
--
-- Creation: Jan 08, 2017 at 11:52 PM
-- Last update: Jan 08, 2017 at 11:52 PM
--

CREATE TABLE IF NOT EXISTS `Study_CagesInCohorts` (
  `StudyCageKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `StudyCageNumber` varchar(45) NOT NULL,
  `StudyCageName` varchar(45) NOT NULL,
  `StudyCageAlias` varchar(45) DEFAULT NULL,
  `CohortKey` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`StudyCageKey`),
  UNIQUE KEY `StudyCageNumber_UNIQUE` (`StudyCageKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Study_Cohorts`
--
-- Creation: Jan 08, 2017 at 11:46 PM
-- Last update: Jan 08, 2017 at 11:46 PM
--

CREATE TABLE IF NOT EXISTS `Study_Cohorts` (
  `CohortKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `StudyNumber` bigint(20) NOT NULL,
  `CohortName` varchar(45) NOT NULL,
  `CohortDesc` text,
  PRIMARY KEY (`CohortKey`),
  UNIQUE KEY `CohortNumber_UNIQUE` (`CohortKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Study_GroupAlias`
--
-- Creation: Jan 08, 2017 at 11:55 PM
-- Last update: Jan 08, 2017 at 11:55 PM
--

CREATE TABLE IF NOT EXISTS `Study_GroupAlias` (
  `AliasKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `GroupKey` bigint(20) DEFAULT NULL,
  `AliasText` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`AliasKey`),
  UNIQUE KEY `AliasKey_UNIQUE` (`AliasKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Study_GroupInfo`
--
-- Creation: Jan 08, 2017 at 11:54 PM
-- Last update: Jan 08, 2017 at 11:54 PM
--

CREATE TABLE IF NOT EXISTS `Study_GroupInfo` (
  `GroupKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(45) NOT NULL,
  `GroupDesc` varchar(45) DEFAULT NULL,
  `StudyKey` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`GroupKey`),
  UNIQUE KEY `GroupKey_UNIQUE` (`GroupKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Study_Info`
--
-- Creation: Jan 08, 2017 at 11:41 PM
-- Last update: Jan 08, 2017 at 11:41 PM
--

CREATE TABLE IF NOT EXISTS `Study_Info` (
  `StudyNumber` bigint(20) NOT NULL,
  `StudyName` varchar(45) NOT NULL,
  `StudyDesc` text,
  PRIMARY KEY (`StudyNumber`),
  UNIQUE KEY `StudyNumber_UNIQUE` (`StudyNumber`),
  UNIQUE KEY `StudyName_UNIQUE` (`StudyName`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `Study_animals`
--
-- Creation: Jan 09, 2017 at 12:00 AM
-- Last update: Jan 09, 2017 at 12:00 AM
--

CREATE TABLE IF NOT EXISTS `Study_animals` (
  `StudyanimalKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `animalAutoNo` bigint(20) NOT NULL,
  `FlagReclip` varchar(45) DEFAULT NULL,
  `FlagGenoConf` varchar(45) DEFAULT NULL,
  `FlagExclude` varchar(45) DEFAULT NULL,
  `StudyCageKey` bigint(20) DEFAULT NULL,
  `StudyCohortKey` bigint(20) DEFAULT NULL,
  `StudyKey` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`StudyanimalKey`),
  UNIQUE KEY `StudyanimalKey_UNIQUE` (`StudyanimalKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `Study_animalsGroups`
--
-- Creation: Jan 09, 2017 at 12:02 AM
-- Last update: Jan 09, 2017 at 12:02 AM
--

CREATE TABLE IF NOT EXISTS `Study_animalsGroups` (
  `MG_LinkKey` bigint(20) NOT NULL AUTO_INCREMENT,
  `StudyanimalKey` bigint(20) DEFAULT NULL,
  `GroupAliasKey` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`MG_LinkKey`),
  UNIQUE KEY `MG_LinkKey_UNIQUE` (`MG_LinkKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `data_comments`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Sep 06, 2017 at 09:59 PM
--

CREATE TABLE IF NOT EXISTS `data_comments` (
  `commentid` bigint(20) NOT NULL AUTO_INCREMENT,
  `animalautono` bigint(20) DEFAULT NULL,
  `commentdate` datetime DEFAULT NULL,
  `general_comment` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`commentid`),
  UNIQUE KEY `commentid_UNIQUE` (`commentid`),
  KEY `fk_data_comments_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9598 ;



-- --------------------------------------------------------

--
-- Table structure for table `data_weights`
--
-- Creation: Sep 16, 2016 at 09:45 PM
-- Last update: Sep 16, 2016 at 09:45 PM
-- Last check: Sep 16, 2016 at 09:45 PM
--

CREATE TABLE IF NOT EXISTS `data_weights` (
  `measurementid` bigint(20) NOT NULL AUTO_INCREMENT,
  `dom` datetime DEFAULT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`measurementid`),
  UNIQUE KEY `measurementid_UNIQUE` (`measurementid`),
  KEY `fk_data_weights_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `good_genos`
--
-- Creation: Sep 16, 2016 at 09:47 PM
-- Last update: Apr 18, 2017 at 05:39 PM
--

CREATE TABLE IF NOT EXISTS `good_genos` (
  `idgood_genos` int(11) NOT NULL AUTO_INCREMENT,
  `line` varchar(45) DEFAULT NULL,
  `allele1` varchar(45) DEFAULT NULL,
  `geno1` varchar(45) DEFAULT NULL,
  `allele2` varchar(45) DEFAULT NULL,
  `geno2` varchar(45) DEFAULT NULL,
  `allele3` varchar(45) DEFAULT NULL,
  `geno3` varchar(45) DEFAULT NULL,
  `alleles_needed` int(11) DEFAULT NULL,
  PRIMARY KEY (`idgood_genos`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;


-- --------------------------------------------------------

--
-- Table structure for table `key_allelebyline`
--
-- Creation: Sep 08, 2016 at 06:05 PM
-- Last update: Jul 31, 2017 at 04:02 PM
--

CREATE TABLE IF NOT EXISTS `key_allelebyline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `line` varchar(255) DEFAULT NULL,
  `allelegroup` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_key_allelebyline_list_allelegroup1_idx` (`allelegroup`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=88 ;


-- --------------------------------------------------------

--
-- Table structure for table `key_allelegroupbygenotypingrxn`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: May 27, 2017 at 06:06 PM
-- Last check: Sep 02, 2016 at 02:23 AM
--

CREATE TABLE IF NOT EXISTS `key_allelegroupbygenotypingrxn` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allelegroup` varchar(255) DEFAULT NULL,
  `genotypingrxn` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_key_allelegroupbygenotypingrxn_list_allelegroup1_idx` (`allelegroup`),
  KEY `fk_key_allelegroupbygenotypingrxn_list_genotypingrxns1_idx` (`genotypingrxn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=69 ;

--
-- Dumping data for table `key_allelegroupbygenotypingrxn`
--

-- --------------------------------------------------------

--
-- Table structure for table `list_allele`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: Jul 27, 2017 at 05:48 AM
-- Last check: Sep 02, 2016 at 02:23 AM
--

CREATE TABLE IF NOT EXISTS `list_allele` (
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(255) DEFAULT NULL,
  `genderspecific` varchar(45) DEFAULT NULL,
  `indexkey` int(11) NOT NULL AUTO_INCREMENT,
  `notes` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`indexkey`),
  UNIQUE KEY `indexkey_UNIQUE` (`indexkey`),
  KEY `fk_list_allele_list_allelegroup1_idx` (`allelegroup`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=326 ;


-- --------------------------------------------------------

--
-- Table structure for table `list_allelegroup`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: Jul 27, 2017 at 05:47 AM
-- Last check: Sep 02, 2016 at 02:23 AM
--

CREATE TABLE IF NOT EXISTS `list_allelegroup` (
  `allelegroup` varchar(255) NOT NULL,
  `gene` varchar(255) NOT NULL,
  `reference` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`allelegroup`),
  UNIQUE KEY `allelegroup_UNIQUE` (`allelegroup`),
  KEY `fk_list_allelegroup_list_gene_idx` (`gene`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `list_gene`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: Aug 10, 2017 at 07:07 PM
--

CREATE TABLE IF NOT EXISTS `list_gene` (
  `gene` varchar(255) NOT NULL,
  PRIMARY KEY (`gene`),
  UNIQUE KEY `gene_UNIQUE` (`gene`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `list_genotypingprimers`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: Jan 26, 2017 at 06:58 PM
-- Last check: Sep 02, 2016 at 02:23 AM
--

CREATE TABLE IF NOT EXISTS `list_genotypingprimers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `primerseq` varchar(255) DEFAULT NULL,
  `primername` varchar(255) DEFAULT NULL,
  `genotypingrxn` varchar(255) DEFAULT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  KEY `fk_list_genotypingprimers_list_genotypingrxns1_idx` (`genotypingrxn`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=75 ;


-- --------------------------------------------------------

--
-- Table structure for table `list_genotypingrxns`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: May 27, 2017 at 06:06 PM
--

CREATE TABLE IF NOT EXISTS `list_genotypingrxns` (
  `genotypingrxn` varchar(255) NOT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  `recommendedcycle` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`genotypingrxn`),
  UNIQUE KEY `genotypingrxn_UNIQUE` (`genotypingrxn`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `list_lengthone`
--
-- Creation: Sep 08, 2016 at 06:05 PM
-- Last update: Sep 08, 2016 at 06:05 PM
--

CREATE TABLE IF NOT EXISTS `list_lengthone` (
  `id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `list_numbers`
--
-- Creation: Sep 08, 2016 at 06:05 PM
-- Last update: Sep 08, 2016 at 06:05 PM
--

CREATE TABLE IF NOT EXISTS `list_numbers` (
  `number_list` int(11) NOT NULL,
  PRIMARY KEY (`number_list`),
  UNIQUE KEY `number_list_UNIQUE` (`number_list`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `list_strains`
--
-- Creation: Sep 02, 2016 at 02:23 AM
-- Last update: Apr 28, 2017 at 04:08 PM
--

CREATE TABLE IF NOT EXISTS `list_strains` (
  `strains` varchar(45) NOT NULL,
  PRIMARY KEY (`strains`),
  UNIQUE KEY `strains_UNIQUE` (`strains`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



-- --------------------------------------------------------

--
-- Table structure for table `table_cages`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Aug 03, 2017 at 04:43 PM
--

CREATE TABLE IF NOT EXISTS `table_cages` (
  `cageid` varchar(255) NOT NULL,
  `cagetype` varchar(45) DEFAULT NULL,
  `setupdate` datetime DEFAULT NULL,
  `stopdate` datetime DEFAULT NULL,
  `cageactive` varchar(1) DEFAULT NULL,
  `comments` varchar(1024) DEFAULT NULL,
  `lineassignment` varchar(255) DEFAULT NULL,
  `cageno` bigint(20) NOT NULL,
  `cagecontents` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`cageid`),
  UNIQUE KEY `cageid_UNIQUE` (`cageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `table_deadpups`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Apr 24, 2017 at 07:34 PM
--

CREATE TABLE IF NOT EXISTS `table_deadpups` (
  `cageid` char(255) NOT NULL,
  `dob` date DEFAULT NULL,
  `dod` date DEFAULT NULL,
  `death_autono` bigint(20) NOT NULL AUTO_INCREMENT,
  `comments` text,
  `death_type` char(45) DEFAULT NULL,
  PRIMARY KEY (`death_autono`),
  UNIQUE KEY `death_autono_UNIQUE` (`death_autono`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `table_deadpups`
--

-- --------------------------------------------------------

--
-- Table structure for table `table_genotypes`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Aug 03, 2017 at 10:10 PM
--

CREATE TABLE IF NOT EXISTS `table_genotypes` (
  `genoid` bigint(20) NOT NULL AUTO_INCREMENT,
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(45) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`genoid`),
  UNIQUE KEY `genoid_UNIQUE` (`genoid`),
  KEY `fk_table_genotypes_table_animals1_idx` (`animalautono`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4724 ;



--
-- Table structure for table `table_lines`
--
-- Creation: Sep 08, 2016 at 06:05 PM
-- Last update: Jul 31, 2017 at 04:03 PM
--

CREATE TABLE IF NOT EXISTS `table_lines` (
  `line` varchar(255) NOT NULL,
  `line_description` varchar(255) DEFAULT NULL,
  `strain` varchar(255) DEFAULT NULL,
  `ucsd_number` varchar(255) DEFAULT NULL,
  `color_assignment` varchar(45) DEFAULT NULL,
  `deactivated_line` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`line`),
  UNIQUE KEY `line_UNIQUE` (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `temp_cage1`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Aug 03, 2017 at 06:05 AM
--

CREATE TABLE IF NOT EXISTS `temp_cage1` (
  `animalautono` bigint(20) NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_cage2`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Aug 03, 2017 at 06:05 AM
--

CREATE TABLE IF NOT EXISTS `temp_cage2` (
  `animalautono` bigint(20) NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_cage3`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Aug 03, 2017 at 05:56 AM
--

CREATE TABLE IF NOT EXISTS `temp_cage3` (
  `animalautono` bigint(20) NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_cage4`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Jul 27, 2017 at 06:53 AM
--

CREATE TABLE IF NOT EXISTS `temp_cage4` (
  `animalautono` bigint(20) NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_comments`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Sep 16, 2016 at 09:52 PM
--

CREATE TABLE IF NOT EXISTS `temp_comments` (
  `commentid` bigint(20) NOT NULL,
  `animalautono` bigint(20) DEFAULT NULL,
  `commentdate` datetime DEFAULT NULL,
  `general_comment` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`commentid`),
  UNIQUE KEY `commentid_UNIQUE` (`commentid`),
  KEY `fk_temp_comments_temp_createanimals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_createanimals`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Sep 16, 2016 at 09:52 PM
--

CREATE TABLE IF NOT EXISTS `temp_createanimals` (
  `animalautono` bigint(20) NOT NULL,
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_genotypes`
--
-- Creation: Sep 16, 2016 at 09:52 PM
-- Last update: Sep 16, 2016 at 09:52 PM
--

CREATE TABLE IF NOT EXISTS `temp_genotypes` (
  `genoid` bigint(20) NOT NULL,
  `allelegroup` varchar(255) DEFAULT NULL,
  `allele` varchar(45) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  `animalautono` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`genoid`),
  UNIQUE KEY `genoid_UNIQUE` (`genoid`),
  KEY `fk_temp_genotypes_temp_createanimals1_idx` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `temp_shufcg1`
--
-- Creation: Sep 08, 2016 at 06:05 PM
-- Last update: Sep 08, 2016 at 06:05 PM
--

CREATE TABLE IF NOT EXISTS `temp_shufcg1` (
  `animalautono` bigint(20) NOT NULL,
  PRIMARY KEY (`animalautono`),
  UNIQUE KEY `animalautono_UNIQUE` (`animalautono`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `temp_shufcg1`
--

INSERT INTO `temp_shufcg1` (`animalautono`) VALUES
(1986);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_goodanimals`
--
CREATE TABLE IF NOT EXISTS `view_goodanimals` (
`allele` varchar(45)
,`allelegroup` varchar(255)
,`line` varchar(255)
,`idno` varchar(255)
,`animalautono` bigint(20)
,`dob` datetime
,`dod` datetime
,`gender` varchar(45)
,`cagetype` varchar(1)
,`goodgeno` varchar(45)
,`numalleles` int(11)
,`curagedays` int(7)
,`curagemo` int(9)
,`curagegrp` varchar(9)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_goodanimals_all`
--
CREATE TABLE IF NOT EXISTS `view_goodanimals_all` (
`allele` varchar(45)
,`allelegroup` varchar(255)
,`line` varchar(255)
,`idno` varchar(255)
,`animalautono` bigint(20)
,`dob` datetime
,`dod` datetime
,`gender` varchar(45)
,`cagetype` varchar(1)
,`goodgeno` varchar(45)
,`numalleles` int(11)
,`curagedays` int(7)
,`curagemo` int(9)
,`curagegrp` varchar(9)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_goodanimals_filtered`
--
CREATE TABLE IF NOT EXISTS `view_goodanimals_filtered` (
`line` varchar(255)
,`idno` varchar(255)
,`animalautono` bigint(20)
,`numalleles` int(11)
,`curagegrp` varchar(9)
,`curagedays` int(7)
,`curagemo` int(9)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_goodanimals_filtered_all`
--
CREATE TABLE IF NOT EXISTS `view_goodanimals_filtered_all` (
`line` varchar(255)
,`idno` varchar(255)
,`animalautono` bigint(20)
,`numalleles` int(11)
,`curagegrp` varchar(9)
,`curagedays` int(7)
,`curagemo` int(9)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_linestatus`
--
CREATE TABLE IF NOT EXISTS `view_linestatus` (
`line` varchar(255)
,`animals_0-3mo` decimal(23,0)
,`animals_4-6mo` decimal(23,0)
,`animals_7+` decimal(23,0)
,`matings_0-3mo` decimal(23,0)
,`matings_4+mo` decimal(23,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_matingcount`
--
CREATE TABLE IF NOT EXISTS `view_matingcount` (
`lineassignment` varchar(255)
,`matings_0-3mo` decimal(23,0)
,`matings_4+mo` decimal(23,0)
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_matingstatus`
--
CREATE TABLE IF NOT EXISTS `view_matingstatus` (
`lineassignment` varchar(255)
,`cageid` varchar(255)
,`MatingAgeDays` int(7)
,`MatingAgeMos` int(9)
,`pupsmade` bigint(21)
,`goodpupsmade` bigint(21)
,`Litters` bigint(21)
,`Dead Litters` bigint(21)
,`LastLitterDOB` datetime
,`LastDeadLitterDOB` date
);
-- --------------------------------------------------------

--
-- Stand-in structure for view `view_unkgenos`
--
CREATE TABLE IF NOT EXISTS `view_unkgenos` (
`allele` varchar(45)
,`allelegroup` varchar(255)
,`line` varchar(255)
,`idno` varchar(255)
,`dob` datetime
,`dod` datetime
);
-- --------------------------------------------------------

--
-- Structure for view `view_goodanimals`
--
DROP TABLE IF EXISTS `view_goodanimals`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_goodanimals` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`gender` AS `gender`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) where isnull(`table_animals`.`dod`) having ((`goodgeno` = `table_genotypes`.`allele`) and ((`cagetype` = 'H') or (`cagetype` = 'L')));

-- --------------------------------------------------------

--
-- Structure for view `view_goodanimals_all`
--
DROP TABLE IF EXISTS `view_goodanimals_all`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_goodanimals_all` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`gender` AS `gender`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) having (`goodgeno` = `table_genotypes`.`allele`);

-- --------------------------------------------------------

--
-- Structure for view `view_goodanimals_filtered`
--
DROP TABLE IF EXISTS `view_goodanimals_filtered`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_goodanimals_filtered` AS select `view_goodanimals`.`line` AS `line`,`view_goodanimals`.`idno` AS `idno`,`view_goodanimals`.`animalautono` AS `animalautono`,`view_goodanimals`.`numalleles` AS `numalleles`,`view_goodanimals`.`curagegrp` AS `curagegrp`,`view_goodanimals`.`curagedays` AS `curagedays`,`view_goodanimals`.`curagemo` AS `curagemo` from `view_goodanimals` group by `view_goodanimals`.`line`,`view_goodanimals`.`idno` having (count(0) = `view_goodanimals`.`numalleles`);

-- --------------------------------------------------------

--
-- Structure for view `view_goodanimals_filtered_all`
--
DROP TABLE IF EXISTS `view_goodanimals_filtered_all`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_goodanimals_filtered_all` AS select `view_goodanimals_all`.`line` AS `line`,`view_goodanimals_all`.`idno` AS `idno`,`view_goodanimals_all`.`animalautono` AS `animalautono`,`view_goodanimals_all`.`numalleles` AS `numalleles`,`view_goodanimals_all`.`curagegrp` AS `curagegrp`,`view_goodanimals_all`.`curagedays` AS `curagedays`,`view_goodanimals_all`.`curagemo` AS `curagemo` from `view_goodanimals_all` group by `view_goodanimals_all`.`line`,`view_goodanimals_all`.`idno` having (count(0) = `view_goodanimals_all`.`numalleles`);

-- --------------------------------------------------------

--
-- Structure for view `view_linestatus`
--
DROP TABLE IF EXISTS `view_linestatus`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_linestatus` AS select `table_lines`.`line` AS `line`,sum(if((`view_goodanimals_filtered`.`curagemo` <= 3),1,0)) AS `animals_0-3mo`,sum(if(((`view_goodanimals_filtered`.`curagemo` > 3) and (`view_goodanimals_filtered`.`curagemo` <= 6)),1,0)) AS `animals_4-6mo`,sum(if((`view_goodanimals_filtered`.`curagemo` > 6),1,0)) AS `animals_7+`,`view_matingcount`.`matings_0-3mo` AS `matings_0-3mo`,`view_matingcount`.`matings_4+mo` AS `matings_4+mo` from ((`table_lines` left join `view_goodanimals_filtered` on((`table_lines`.`line` = `view_goodanimals_filtered`.`line`))) left join `view_matingcount` on((`table_lines`.`line` = convert(`view_matingcount`.`lineassignment` using utf8)))) group by `table_lines`.`line`;

-- --------------------------------------------------------

--
-- Structure for view `view_matingcount`
--
DROP TABLE IF EXISTS `view_matingcount`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_matingcount` AS select `view_matingstatus`.`lineassignment` AS `lineassignment`,sum(if((`view_matingstatus`.`MatingAgeMos` <= 3),1,0)) AS `matings_0-3mo`,sum(if((`view_matingstatus`.`MatingAgeMos` > 3),1,0)) AS `matings_4+mo` from `view_matingstatus` group by `view_matingstatus`.`lineassignment`;

-- --------------------------------------------------------

--
-- Structure for view `view_matingstatus`
--
DROP TABLE IF EXISTS `view_matingstatus`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_matingstatus` AS select `table_cages`.`lineassignment` AS `lineassignment`,`table_cages`.`cageid` AS `cageid`,(to_days(curdate()) - to_days(`table_cages`.`setupdate`)) AS `MatingAgeDays`,floor(((to_days(curdate()) - to_days(`table_cages`.`setupdate`)) / 30)) AS `MatingAgeMos`,count(distinct `table_pups`.`idno`) AS `pupsmade`,count(distinct `view_goodanimals_filtered_all`.`idno`) AS `goodpupsmade`,count(distinct `table_pups`.`dob`) AS `Litters`,count(distinct `table_deadpups`.`dob`) AS `Dead Litters`,max(`table_pups`.`dob`) AS `LastLitterDOB`,max(`table_deadpups`.`dob`) AS `LastDeadLitterDOB` from (((`table_cages` left join `table_animals` on((`table_cages`.`cageid` = `table_animals`.`currentcage`))) left join (`table_animals` `table_pups` left join `view_goodanimals_filtered_all` on((`table_pups`.`animalautono` = `view_goodanimals_filtered_all`.`animalautono`))) on((`table_cages`.`cageid` = `table_pups`.`matingcage`))) left join `table_deadpups` on((`table_cages`.`cageid` = `table_deadpups`.`cageid`))) where ((`table_cages`.`cagetype` = 'Mating') and (`table_animals`.`gender` = 'F') and isnull(`table_animals`.`dod`)) group by `table_cages`.`cageid` order by `table_cages`.`lineassignment`,`table_cages`.`cageno`;

-- --------------------------------------------------------

--
-- Structure for view `view_unkgenos`
--
DROP TABLE IF EXISTS `view_unkgenos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`realchrisward`@`localhost` SQL SECURITY DEFINER VIEW `view_unkgenos` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod` from (`table_genotypes` join `table_animals` on((`table_genotypes`.`animalautono` = `table_animals`.`animalautono`))) where ((`table_genotypes`.`allele` = 'unk') and isnull(`table_animals`.`dod`)) order by `table_genotypes`.`allelegroup`,`table_animals`.`line`,cast(`table_animals`.`idno` as unsigned),`table_animals`.`idno`;