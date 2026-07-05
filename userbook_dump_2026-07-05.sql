-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: userbook
-- ------------------------------------------------------
-- Server version	8.0.45

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
-- Table structure for table `dbaccess`
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userdbaccess`
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
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
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
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'userbook'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-05 17:30:26
