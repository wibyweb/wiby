-- MySQL dump 10.13  Distrib 8.0.18, for Linux (x86_64)
--
-- Host: localhost    Database: wibytemp
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
-- Table structure for table `failed`
--

DROP TABLE IF EXISTS `failed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url_noprefix` text,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `url_noprefix` (`url_noprefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed`
--

LOCK TABLES `failed` WRITE;
/*!40000 ALTER TABLE `failed` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rejected`
--

DROP TABLE IF EXISTS `rejected`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rejected` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `user` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `type` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rejected`
--

LOCK TABLES `rejected` WRITE;
/*!40000 ALTER TABLE `rejected` DISABLE KEYS */;
INSERT INTO `rejected` VALUES (1,'http://forgottencomputer.com/retro/sound/','',1,NULL),(2,'http://koshka.love/','',1,NULL),(3,'http://koshka.love/','',1,NULL),(4,'http://digdeeper.club/articles/corona.xhtml','',1,NULL),(5,'https://www.gutenberg.org/files/1695/1695-h/1695-h.htm','',1,NULL),(6,'https://www.gutenberg.org/files/1695/1695-h/1695-h.htm','admin',1,NULL),(7,'http://digdeeper.club/articles/email.xhtml','admin',1,NULL),(8,'http://digdeeper.club/articles/email.xhtml','admin',1,NULL),(9,'https://websitereview.neocities.org/','admin',1,NULL),(10,'https://websitereview.neocities.org/','admin',1,NULL),(11,'https://nmap.org/book/firewall-subversion.html','admin',1,NULL),(12,'bob.com','admin',NULL,'2023-06-01 00:15:48'),(13,'h.com','admin',NULL,'2023-06-01 00:15:48'),(14,'bobbb.com','admin',NULL,'2023-06-01 00:15:48'),(15,'bee.com','admin',NULL,'2023-06-01 00:15:48'),(16,'http://digdeeper.club/articles/liftingtheveil.xhtml','admin',1,'2023-06-01 00:21:37'),(17,'https://gigamonkeys.com/book/practical-building-a-unit-test-framework.html','admin',0,'2023-06-01 00:22:47'),(18,'fddd.com','admin',NULL,'2023-06-12 00:02:44'),(19,'fdsjfksd.com','admin',NULL,'2023-06-12 00:02:44'),(20,'fd.com','admin',NULL,'2023-06-12 00:02:44'),(21,'https://www.techtimes.com/articles/3907/20140302/www-turns-25-a-look-back-at-the-internets-early-days.htm','admin',1,'2023-06-12 01:20:16'),(22,'https://www.nytimes.com/2019/09/02/us/san-francisco-fogcam.html','admin',1,'2023-06-12 01:23:06'),(23,'https://www.imdb.com/','admin',1,'2023-06-12 01:23:33'),(24,'http://web.mit.edu/people/mkgray/net/web-growth-summary.html','admin',1,'2023-06-12 01:23:33'),(25,'https://theathletic.com/','admin',1,'2023-06-12 01:23:33'),(26,'http://bytecollector.com/wish_list.htm','admin',1,'2023-06-14 00:10:41');
/*!40000 ALTER TABLE `rejected` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reserve_id`
--

DROP TABLE IF EXISTS `reserve_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reserve_id` (
  `id` bigint(20) NOT NULL,
  `crawler_id` int(11) DEFAULT NULL,
  `time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reserve_id`
--

LOCK TABLES `reserve_id` WRITE;
/*!40000 ALTER TABLE `reserve_id` DISABLE KEYS */;
/*!40000 ALTER TABLE `reserve_id` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `titlecheck`
--

DROP TABLE IF EXISTS `titlecheck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `titlecheck` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `titlecheck`
--

LOCK TABLES `titlecheck` WRITE;
/*!40000 ALTER TABLE `titlecheck` DISABLE KEYS */;
/*!40000 ALTER TABLE `titlecheck` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2023-06-18 23:46:37
