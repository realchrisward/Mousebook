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
-- Database: `userbook`
--

-- --------------------------------------------------------

--
-- Table structure for table `dbaccess`
--
-- Creation: Jan 26, 2017 at 06:11 PM
-- Last update: Jan 26, 2017 at 06:21 PM
--

CREATE TABLE IF NOT EXISTS `dbaccess` (
  `db_no` bigint(20) NOT NULL AUTO_INCREMENT,
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `dbaccess`
--

INSERT INTO `dbaccess` (`db_no`, `db_name`, `db_accessun`, `db_accesspw`, `db_formurl`, `db_host`, `db_subject_plural`, `db_subject_single`, `db_guide1_title`, `db_guide1_url`) VALUES
(1, 'mousebook', 'realchrisward', 'Tofe1984!', 'mousebook.neurobehavioralcore.com', '107.180.40.203', 'mice', 'mouse', 'Lines and Strains', 'https://docs.google.com/spreadsheets/d/1vEeKNaDCWHnqlr6O4Cf0vOU8D1vMktZSDf-6C4kwhNU/edit?usp=sharing'),
(2, 'userbook', 'realchrisward', 'Tofe1984!', 'userbook.neurobehavioralcore.com', '107.180.40.203', 'users', 'user', NULL, NULL),
(3, 'ratbook', 'realchrisward', 'Tofe1984!', 'ratbook.neurobehavioralcore.com', '107.180.40.203', 'rats', 'rat', 'Lines and Strains', 'https://docs.google.com/spreadsheets/d/1oV_eSU0ehboPAfE1IqAHOTrytg8Tqv9rfN_89XnK6nc/edit?usp=sharing');

-- --------------------------------------------------------

--
-- Table structure for table `userdbaccess`
--
-- Creation: Sep 02, 2016 at 02:58 AM
-- Last update: Jul 26, 2017 at 04:22 PM
--

CREATE TABLE IF NOT EXISTS `userdbaccess` (
  `user_idno` bigint(20) NOT NULL,
  `link_number` bigint(20) NOT NULL AUTO_INCREMENT,
  `db_name` varchar(45) NOT NULL,
  `db_accesstier` varchar(45) NOT NULL,
  PRIMARY KEY (`link_number`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17 ;

--
-- Dumping data for table `userdbaccess`
--

INSERT INTO `userdbaccess` (`user_idno`, `link_number`, `db_name`, `db_accesstier`) VALUES
(1, 1, 'mousebook', '1'),
(1, 2, 'userbook', '1'),
(1, 3, 'ratbook', '1');

-- --------------------------------------------------------

--
-- Table structure for table `userdetail`
--
-- Creation: Sep 02, 2016 at 02:55 AM
-- Last update: Jul 26, 2017 at 04:21 PM
--

CREATE TABLE IF NOT EXISTS `userdetail` (
  `user_idno` bigint(20) NOT NULL,
  `user_email` varchar(45) DEFAULT NULL,
  `user_phone` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_idno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `userdetail`
--

INSERT INTO `userdetail` (`user_idno`, `user_email`, `user_phone`) VALUES
(1, 'christow@bcm.edu', '269-369-2445');

-- --------------------------------------------------------

--
-- Table structure for table `userpass`
--
-- Creation: Sep 02, 2016 at 02:52 AM
-- Last update: Jul 26, 2017 at 04:20 PM
--

CREATE TABLE IF NOT EXISTS `userpass` (
  `user_idno` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) NOT NULL,
  `user_pass` varchar(45) NOT NULL,
  `user_salt` varchar(45) NOT NULL,
  
  PRIMARY KEY (`user_idno`),
  UNIQUE KEY `user_idno_UNIQUE` (`user_idno`),
  UNIQUE KEY `username_UNIQUE` (`user_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `userpass`
--

INSERT INTO `userpass` (`user_idno`, `user_name`, `user_pass`, `user_salt`) VALUES
(1, 'admin', 'password', 'salt');


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;