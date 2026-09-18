/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.20-12.3.3-MariaDB, for Android (aarch64)
--
-- Host: localhost    Database: reality_realtor
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES
(1,'admin','$2y$12$zjEEdQFE1yTOnd30R1VxQeZyV9j27qJQZ8lOcONtEdipRxQoloVhK','2026-09-03 22:25:41');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `location` enum('Enugu','Abuja','Asaba') NOT NULL,
  `category` enum('Buy and Build','Land Banking','Luxury Homes','Available Properties','Distress Sales') NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price_negotiable` tinyint(1) NOT NULL DEFAULT 0,
  `bedrooms` int(10) unsigned DEFAULT 0,
  `bathrooms` int(10) unsigned DEFAULT 0,
  `parking_spaces` int(10) unsigned DEFAULT 0,
  `living_rooms` int(10) unsigned DEFAULT 0,
  `land_size` varchar(100) DEFAULT NULL,
  `land_type` varchar(100) DEFAULT NULL,
  `property_use` varchar(100) DEFAULT NULL,
  `title_document` varchar(255) DEFAULT NULL,
  `plot_size` varchar(100) DEFAULT NULL,
  `access_road` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(500) DEFAULT NULL,
  `status` enum('Available','Sold','Reserved') NOT NULL DEFAULT 'Available',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES
(6,'Sport Island Estates','Enugu','Buy and Build','',35000000.00,0,0,0,0,0,'500sqm',NULL,NULL,'C of O, Government Allocation Paper, Deed of Assignment','500sqm',NULL,'Centenary City, Enugu','https://youtu.be/dzVKPHMct60?si=FpssW9nxWauYwwlV','Available',1,'2026-09-11 18:09:19','2026-09-11 18:46:35'),
(7,'Riverbank Estate','Enugu','Buy and Build','',12000000.00,0,0,0,0,0,'500sqm',NULL,NULL,'General C of O, Deed of Assignment, Power of Attorney','500sqm',NULL,'Ibagwa Nike Enugu','https://youtube.com/shorts/YFhWoc3YPb8?si=W03BqZ2a1aCir1Jz','Available',1,'2026-09-12 16:28:35','2026-09-12 16:54:41');
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `property_documents`
--

DROP TABLE IF EXISTS `property_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_documents_property` (`property_id`),
  CONSTRAINT `fk_documents_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `property_documents` WRITE;
/*!40000 ALTER TABLE `property_documents` DISABLE KEYS */;
INSERT INTO `property_documents` VALUES
(7,6,'C of O',0,'2026-09-11 18:46:35'),
(8,6,'Government Allocation Paper',1,'2026-09-11 18:46:35'),
(9,6,'Deed of Assignment',2,'2026-09-11 18:46:35'),
(25,7,'General C of O',0,'2026-09-12 16:54:41'),
(26,7,'Deed of Assignment',1,'2026-09-12 16:54:41'),
(27,7,'Power of Attorney',2,'2026-09-12 16:54:41');
/*!40000 ALTER TABLE `property_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `property_features`
--

DROP TABLE IF EXISTS `property_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_features` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `feature` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_features_property` (`property_id`),
  CONSTRAINT `fk_features_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_features`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `property_features` WRITE;
/*!40000 ALTER TABLE `property_features` DISABLE KEYS */;
INSERT INTO `property_features` VALUES
(13,6,'Perimeter Fencing',0,'2026-09-11 18:46:35'),
(14,6,'Steady Electricity',1,'2026-09-11 18:46:35'),
(15,6,'Gardening Features',2,'2026-09-11 18:46:35'),
(16,6,'Good Access Road',3,'2026-09-11 18:46:35'),
(17,6,'Gate Housing',4,'2026-09-11 18:46:35'),
(18,6,'Estate Security',5,'2026-09-11 18:46:35'),
(19,6,'Drainage System',6,'2026-09-11 18:46:35'),
(40,7,'Good Access Road',0,'2026-09-12 16:54:41'),
(41,7,'Gate Housing',1,'2026-09-12 16:54:41'),
(42,7,'Estate Security',2,'2026-09-12 16:54:41'),
(43,7,'Drainage System',3,'2026-09-12 16:54:41');
/*!40000 ALTER TABLE `property_features` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `property_images`
--

DROP TABLE IF EXISTS `property_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_property_images` (`property_id`),
  CONSTRAINT `fk_property_images` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `property_images` WRITE;
/*!40000 ALTER TABLE `property_images` DISABLE KEYS */;
INSERT INTO `property_images` VALUES
(6,6,'uploads/properties/0cef87826bb08042aad1c4c499ccee34.jpg',1,'2026-09-11 18:09:19'),
(7,7,'uploads/properties/0ebeb361af54060fd7c033767d4b8f22.jpg',1,'2026-09-12 16:28:35');
/*!40000 ALTER TABLE `property_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `property_landmarks`
--

DROP TABLE IF EXISTS `property_landmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_landmarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `description` varchar(500) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_landmarks_property` (`property_id`),
  CONSTRAINT `fk_landmarks_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_landmarks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `property_landmarks` WRITE;
/*!40000 ALTER TABLE `property_landmarks` DISABLE KEYS */;
INSERT INTO `property_landmarks` VALUES
(7,6,'3 mins drive from main Centenary gate',0,'2026-09-11 18:46:35'),
(8,6,'6 mins drive from new Roban stores in Centenary',1,'2026-09-11 18:46:35'),
(9,6,'12 mins away from independence layout, Enugu',2,'2026-09-11 18:46:35'),
(25,7,'3 minutes drive from Nike Lake Resort',0,'2026-09-12 16:54:41'),
(26,7,'Beside the Ibagwa Nike police station',1,'2026-09-12 16:54:41'),
(27,7,'A stone throw from Elim estate',2,'2026-09-12 16:54:41');
/*!40000 ALTER TABLE `property_landmarks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `property_pricing`
--

DROP TABLE IF EXISTS `property_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_pricing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `property_id` int(10) unsigned NOT NULL,
  `plot_size` varchar(100) NOT NULL,
  `actual_price` decimal(15,2) DEFAULT NULL,
  `presale_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pricing_property` (`property_id`),
  CONSTRAINT `fk_pricing_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_pricing`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `property_pricing` WRITE;
/*!40000 ALTER TABLE `property_pricing` DISABLE KEYS */;
INSERT INTO `property_pricing` VALUES
(7,6,'500sqm',35000000.00,28000000.00,'2026-09-11 18:46:35','2026-09-11 18:46:35'),
(15,7,'500sqm',12000000.00,NULL,'2026-09-12 16:54:41','2026-09-12 16:54:41');
/*!40000 ALTER TABLE `property_pricing` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-18 22:52:01
