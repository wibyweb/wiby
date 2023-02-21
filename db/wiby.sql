-- MySQL dump 10.13  Distrib 8.0.18, for Linux (x86_64)
--
-- Host: localhost    Database: wiby
-- ------------------------------------------------------
-- Server version	8.0.18

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
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `hash` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `level` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `attempts` int(11) DEFAULT '0',
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graveyard`
--

DROP TABLE IF EXISTS `graveyard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `graveyard` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` text,
  `worksafe` tinyint(1) DEFAULT NULL,
  `reserved` text,
  `reservetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graveyard`
--

LOCK TABLES `graveyard` WRITE;
/*!40000 ALTER TABLE `graveyard` DISABLE KEYS */;
/*!40000 ALTER TABLE `graveyard` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `indexqueue`
--

DROP TABLE IF EXISTS `indexqueue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `indexqueue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `worksafe` tinyint(1) DEFAULT NULL,
  `approver` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `surprise` tinyint(1) DEFAULT NULL,
  `updatable` int(11) DEFAULT '1',
  `task` tinyint(4) DEFAULT NULL,
  `crawl_tree` text,
  `crawl_family` text,
  `crawl_depth` int(11) DEFAULT NULL,
  `crawl_pages` int(11) DEFAULT NULL,
  `crawl_type` int(11) DEFAULT NULL,
  `crawl_repeat` tinyint(4) DEFAULT NULL,
  `force_rules` tinyint(1) DEFAULT NULL,
  `crawler_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `indexqueue`
--

LOCK TABLES `indexqueue` WRITE;
/*!40000 ALTER TABLE `indexqueue` DISABLE KEYS */;
/*!40000 ALTER TABLE `indexqueue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviewqueue`
--

DROP TABLE IF EXISTS `reviewqueue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviewqueue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` mediumtext,
  `worksafe` tinyint(1) DEFAULT NULL,
  `reserved` mediumtext,
  `reservetime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviewqueue`
--

LOCK TABLES `reviewqueue` WRITE;
/*!40000 ALTER TABLE `reviewqueue` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviewqueue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `windex`
--

DROP TABLE IF EXISTS `windex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `windex` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `url_noprefix` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `tags` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `language` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `surprise` tinyint(1) DEFAULT NULL,
  `http` tinyint(1) DEFAULT NULL,
  `updatable` int(11) DEFAULT '1',
  `worksafe` tinyint(1) DEFAULT NULL,
  `crawl_tree` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `crawl_family` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `crawl_pages` int(11) DEFAULT NULL,
  `crawl_type` int(11) DEFAULT NULL,
  `crawl_repeat` tinyint(1) DEFAULT NULL,
  `force_rules` tinyint(1) DEFAULT NULL,
  `enable` tinyint(1) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approver` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `fault` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `main` (`tags`,`title`,`body`,`description`,`url`),
  FULLTEXT KEY `title` (`title`),
  FULLTEXT KEY `url` (`url`),
  FULLTEXT KEY `url_noprefix` (`url_noprefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `windex`
--

LOCK TABLES `windex` WRITE;
/*!40000 ALTER TABLE `windex` DISABLE KEYS */;
/*!40000 ALTER TABLE `windex` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-02-21  0:06:18
