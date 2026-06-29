/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: omega_auto
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(1,'Amadou Ba','amadou.ba@email.sn','+221 77 123 45 67','Dakar, Plateau',NULL,'2026-06-29 05:36:00'),
(2,'Fatou Ndiaye','fatou.n@email.sn','+221 70 234 56 78','Thiès, Centre',NULL,'2026-06-29 05:36:00'),
(3,'Ibrahim Sow','i.sow@email.sn','+221 76 345 67 89','Saint-Louis',NULL,'2026-06-29 05:36:00');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rentals`
--

DROP TABLE IF EXISTS `rentals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rentals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `deposit` decimal(10,2) DEFAULT 0.00,
  `status` enum('en_cours','termine','annule') DEFAULT 'en_cours',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `client_id` (`client_id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `rentals_ibfk_3` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rentals`
--

LOCK TABLES `rentals` WRITE;
/*!40000 ALTER TABLE `rentals` DISABLE KEYS */;
/*!40000 ALTER TABLE `rentals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `sale_price` decimal(12,2) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `final_price` decimal(12,2) DEFAULT NULL,
  `payment_method` enum('especes','virement','cheque','credit') DEFAULT 'especes',
  `status` enum('en_cours','complete','annule') DEFAULT 'complete',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `client_id` (`client_id`),
  KEY `agent_id` (`agent_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','agent','client') DEFAULT 'agent',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Administrateur','admin','admin@omegatech.sn',NULL,'2026-06-29 05:36:00'),
(2,'agent1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Moussa Diallo','agent','moussa@omegatech.sn',NULL,'2026-06-29 05:36:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_categories`
--

DROP TABLE IF EXISTS `vehicle_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_categories`
--

LOCK TABLES `vehicle_categories` WRITE;
/*!40000 ALTER TABLE `vehicle_categories` DISABLE KEYS */;
INSERT INTO `vehicle_categories` VALUES
(1,'Berline','Voitures berlines confortables'),
(2,'SUV','Sport Utility Vehicles'),
(3,'Citadine','Voitures de ville compactes'),
(4,'Utilitaire','Véhicules utilitaires et fourgons'),
(5,'Luxe','Véhicules haut de gamme'),
(6,'Pickup','Camionnettes et pickups');
/*!40000 ALTER TABLE `vehicle_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_images`
--

DROP TABLE IF EXISTS `vehicle_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `vehicle_images_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_images`
--

LOCK TABLES `vehicle_images` WRITE;
/*!40000 ALTER TABLE `vehicle_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicle_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `fuel_type` enum('essence','diesel','hybride','electrique') DEFAULT 'essence',
  `transmission` enum('manuelle','automatique') DEFAULT 'manuelle',
  `mileage` int(11) DEFAULT 0,
  `seats` int(11) DEFAULT 5,
  `category_id` int(11) DEFAULT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `rental_price_day` decimal(10,2) DEFAULT NULL,
  `status` enum('disponible','loue','vendu','maintenance') DEFAULT 'disponible',
  `type` enum('vente','location','les_deux') DEFAULT 'les_deux',
  `description` text DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`id`),
  CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES
(1,'OTA-2024-001','Toyota','Land Cruiser',2024,'Blanc','diesel','automatique',1200,7,2,45000000.00,85000.00,'disponible','les_deux','Toyota Land Cruiser 2024, tout équipé, parfait état',NULL,NULL,'2026-06-29 05:36:00'),
(2,'OTA-2024-002','Mercedes','Classe C',2023,'Noir','essence','automatique',8500,5,5,38000000.00,65000.00,'disponible','les_deux','Mercedes Classe C 2023, intérieur cuir, toit ouvrant',NULL,NULL,'2026-06-29 05:36:00'),
(3,'OTA-2024-003','Hyundai','Tucson',2024,'Gris','hybride','automatique',500,5,2,22000000.00,45000.00,'disponible','les_deux','Hyundai Tucson Hybride 2024, économique et puissant',NULL,NULL,'2026-06-29 05:36:00'),
(4,'OTA-2024-004','Peugeot','208',2023,'Rouge','essence','manuelle',15000,5,3,12000000.00,25000.00,'disponible','location','Peugeot 208 2023, citadine polyvalente',NULL,NULL,'2026-06-29 05:36:00'),
(5,'OTA-2024-005','Ford','Ranger',2024,'Blanc','diesel','automatique',2000,5,6,28000000.00,55000.00,'disponible','les_deux','Ford Ranger 2024, pickup robuste double cabine',NULL,NULL,'2026-06-29 05:36:00'),
(6,'OTA-2023-006','BMW','Série 5',2023,'Bleu','essence','automatique',12000,5,5,52000000.00,95000.00,'disponible','vente','BMW Série 5 2023, berline de luxe, full options',NULL,NULL,'2026-06-29 05:36:00');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-29  6:22:00
