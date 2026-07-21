/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: quivi
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB-ubu2404

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
-- Table structure for table `brand`
--

DROP TABLE IF EXISTS `brand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brand`
--

LOCK TABLES `brand` WRITE;
/*!40000 ALTER TABLE `brand` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `brand` VALUES
(1,'SAMSUNG','2026-01-04 14:55:58','2026-01-04 14:55:58',NULL),
(2,'WESTERN DIGITAL','2026-01-04 14:55:58','2026-01-04 14:55:58',NULL),
(3,'ACER','2026-01-04 14:55:58','2026-01-04 14:55:58',NULL),
(4,'KINGSTON','2026-01-04 14:55:58','2026-01-04 14:55:58',NULL),
(5,'CRUCIAL','2026-01-04 14:55:58','2026-01-04 14:55:58',NULL),
(6,'AMD','2026-02-11 17:41:49','2026-02-11 17:41:49',NULL),
(7,'INTEL','2026-02-11 17:42:10','2026-02-11 17:42:10',NULL),
(8,'GIGABYTE','2026-02-11 17:42:54','2026-02-11 17:42:54',NULL),
(9,'ASUS ROG','2026-02-11 17:42:54','2026-02-11 17:42:54',NULL),
(10,'CORSAIR','2026-02-11 17:43:37','2026-02-11 17:43:37',NULL),
(11,'MSI','2026-02-11 17:44:28','2026-02-11 17:44:28',NULL),
(12,'NZXT','2026-02-11 17:44:28','2026-02-11 17:44:28',NULL),
(13,'LIANLI','2026-02-11 17:44:48','2026-02-11 17:44:48',NULL),
(14,'ARCTIC','2026-02-11 17:45:04','2026-02-11 17:45:04',NULL),
(15,'JONSBO','2026-02-11 17:47:57','2026-02-11 17:47:57',NULL),
(16,'HAVN','2026-02-11 17:47:57','2026-02-11 17:47:57',NULL),
(17,'G.SKILL','2026-02-11 17:47:57','2026-02-11 17:47:57',NULL);
/*!40000 ALTER TABLE `brand` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `care`
--

DROP TABLE IF EXISTS `care`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `care` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `fee` varchar(191) DEFAULT NULL,
  `period` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `care`
--

LOCK TABLES `care` WRITE;
/*!40000 ALTER TABLE `care` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `care` VALUES
(1,'COR3','COR3-1402','1479.00','3 years','2025-12-30 05:16:42','2026-01-22 01:01:40',NULL),
(2,'RI5E','RI5E-2109','1499.00','5 years','2025-12-30 05:16:42','2026-01-21 06:54:59',NULL),
(3,'VIS10N','VIS10N-2712','2499.00','10 years','2025-12-30 05:16:42','2026-01-21 06:55:15',NULL);
/*!40000 ALTER TABLE `care` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `care_data`
--

DROP TABLE IF EXISTS `care_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `care_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `care_id` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `lkp_care_id` bigint(20) unsigned NOT NULL,
  `total_part` varchar(191) DEFAULT NULL,
  `price` varchar(191) DEFAULT NULL,
  `update_membership` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `care_id` (`care_id`) USING BTREE,
  KEY `idx_start_serve_enabled` (`update_membership`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `care_data`
--

LOCK TABLES `care_data` WRITE;
/*!40000 ALTER TABLE `care_data` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `care_data` VALUES
(1,'VIS10N-2712-0001',19,2,3,'13913','1489',1,'2026-03-21 04:13:39','2026-03-23 04:45:36',NULL),
(2,'VIS10N-2712-0002',4,1,3,'15432','1709',0,'2026-03-21 05:12:21','2026-07-09 14:21:43',NULL),
(7,'VIS10N-2712-0003',20,3,3,'23333','2479',1,'2026-07-09 14:21:57','2026-07-09 14:46:58',NULL),
(8,'COR3-1402-0001',20,5,1,'5198','379',1,'2026-07-09 14:22:08','2026-07-19 17:15:08',NULL),
(9,'RI5E-2109-0001',20,4,2,'7435','689',1,'2026-07-09 14:22:11','2026-07-19 17:15:31',NULL),
(10,'VIS10N-2712-0004',21,6,3,'10537','1159',1,'2026-07-09 15:44:53','2026-07-09 15:49:19',NULL),
(11,'COR3-1402-0002',20,8,1,'3500','379',0,'2026-07-12 12:24:24','2026-07-19 17:03:21',NULL),
(12,'COR3-1402-0003',20,9,1,'6200','379',0,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(13,'COR3-1402-0004',20,10,1,'8200','379',0,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(14,'COR3-1402-0005',20,11,1,'9750','379',0,'2026-07-12 12:24:24','2026-07-20 03:17:41',NULL),
(15,'VIS10N-2712-0005',4,7,3,'22411','2479',0,'2026-07-20 11:39:47','2026-07-20 11:39:47',NULL);
/*!40000 ALTER TABLE `care_data` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `care_warranty`
--

DROP TABLE IF EXISTS `care_warranty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `care_warranty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `care_warranty_id` varchar(255) NOT NULL,
  `care_data_id` int(11) NOT NULL,
  `care_invoice_id` varchar(255) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `eligible_warranty` tinyint(1) DEFAULT 0,
  `eligible_qvca` tinyint(1) DEFAULT 0,
  `i_qvca_id` varchar(255) DEFAULT NULL,
  `spare_item_name` varchar(255) DEFAULT NULL,
  `spare_category_id` int(11) DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `loan_date_end` date DEFAULT NULL,
  `reset_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_care_data` (`care_data_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `care_warranty`
--

LOCK TABLES `care_warranty` WRITE;
/*!40000 ALTER TABLE `care_warranty` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `care_warranty` VALUES
(1,'QV-CLA-0001',2,'QVT-INV-2607-1',1,1,1,1,'IC-0001',NULL,NULL,'2026-03-19',NULL,0,'2026-07-12 17:56:57','2026-07-12 18:18:49',NULL),
(2,'QV-CLA-0002',1,'QVT-INV-2603-2',5,1,1,0,'QVCA-0002',NULL,NULL,'2026-03-21',NULL,0,'2026-07-12 17:56:57','2026-07-12 17:56:57',NULL),
(3,'QV-CLA-0003',7,'QVT-INV-2607-3',18,3,1,1,'IC-0003',NULL,NULL,'2026-07-09',NULL,1,'2026-07-12 17:56:57','2026-07-12 18:18:49',NULL),
(4,'QV-CLA-0004',9,'QVT-INV-2607-4',16,3,1,0,'QVCA-0004',NULL,NULL,'2026-07-09',NULL,0,'2026-07-12 17:56:57','2026-07-12 17:56:57',NULL),
(5,'QV-CLA-0005',8,'QVT-INV-2607-5',2,1,0,0,NULL,NULL,NULL,'2026-07-09',NULL,0,'2026-07-12 17:56:57','2026-07-12 17:56:57',NULL),
(6,'QV-CLA-0006',10,'QVT-INV-2607-6',1,1,1,1,'7','INTEL Core Ultra 5 245KF',1,'2026-07-09','2026-07-23',0,'2026-07-12 17:56:58','2026-07-16 14:03:50',NULL),
(7,'QV-CLA-0007',1,'QVT-INV-2603-2',5,1,0,0,'7','INTEL Core Ultra 5 245KF',1,'2026-07-16','2026-07-16',0,'2026-07-16 14:59:21','2026-07-16 15:01:17',NULL);
/*!40000 ALTER TABLE `care_warranty` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `categories` VALUES
(1,'CPU','QV-PROD-CPU','2025-12-30 02:49:27','2026-02-05 08:01:28',NULL),
(2,'SSD','QV-PROD-SSD','2026-01-03 21:49:40','2026-02-05 08:03:10',NULL),
(3,'GPU','QV-PROD-GPU','2026-01-03 21:49:40','2026-02-05 08:01:38',NULL),
(4,'HDD','QV-PROD-HDD','2026-01-03 21:49:40','2026-02-05 08:03:10',NULL),
(5,'RAM','QV-PROD-RAM','2026-01-10 08:49:34','2026-02-05 08:01:03',NULL),
(6,'MBD','QV-PROD-MDB','2026-01-10 09:52:37','2026-02-05 08:01:17',NULL),
(7,'PSU','QV-PROD-PSU','2026-02-05 08:02:47','2026-02-05 08:02:47',NULL),
(8,'HSF','QV-PROD-HSF','2026-02-05 08:03:39','2026-02-05 08:03:39',NULL),
(9,'CSE','QV-PROD-CSE','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(10,'FAN','QV-PROD-FAN','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(11,'AIO','QV-PROD-AIO','2026-02-05 08:03:39','2026-02-05 08:03:39',NULL),
(12,'ACC-SAG','QV-PROD-ACC-SAG','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(13,'ACC-CTL ','QV-PROD-ACC-CTL','2025-12-30 02:27:06','2026-02-28 07:02:48',NULL),
(14,'ACC-HUB','QV-PROD-ACC-HUB','2025-12-30 02:59:49','2026-02-28 07:02:56',NULL),
(15,'PER-MON','QV-PROD-PER-MON','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(16,'PER-MOU','QV-PROD-PER-MOU','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(17,'PER-HDS','QV-PROD-PER-HDS','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(18,'PER-MIC','QV-PROD-PER-MIC','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(19,'PER-MSP','QV-PROD-PER-MSP','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(20,'PER-KEY','QV-PROD-PER-KEY','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(21,'PER-CAM','QV-PROD-PER-CAM','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `craft`
--

DROP TABLE IF EXISTS `craft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `fee` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft`
--

LOCK TABLES `craft` WRITE;
/*!40000 ALTER TABLE `craft` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `craft` VALUES
(1,'BASIC','BASIC','600.00','2026-01-03 21:47:46','2026-03-17 23:42:03',NULL),
(2,'PREMIUM','PREMIUM','600.00','2026-01-03 21:48:07','2026-03-17 23:42:27',NULL),
(3,'MEDIUM','MEDIUM','600.00','2026-01-03 21:48:29','2026-03-17 23:42:16',NULL),
(4,'ULTRA','ULTRA','600.00','2026-03-17 23:42:46','2026-03-17 23:42:46',NULL);
/*!40000 ALTER TABLE `craft` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `craft_inspection_items`
--

DROP TABLE IF EXISTS `craft_inspection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inspection_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `craft_inspection_id` bigint(20) unsigned NOT NULL,
  `component_type` varchar(20) NOT NULL,
  `order_detail_id` bigint(20) unsigned DEFAULT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields`)),
  `model_verified` tinyint(1) NOT NULL DEFAULT 0,
  `serial_recorded` tinyint(1) NOT NULL DEFAULT 0,
  `factory_seal` tinyint(1) NOT NULL DEFAULT 0,
  `qc_pass` tinyint(1) NOT NULL DEFAULT 0,
  `inspection_status` varchar(191) DEFAULT NULL,
  `inspection_note` text DEFAULT NULL,
  `inspection_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inspection_photos`)),
  `packaging_status` varchar(191) DEFAULT NULL,
  `packaging_note` text DEFAULT NULL,
  `packaging_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`packaging_photos`)),
  `condition_status` varchar(191) DEFAULT NULL,
  `condition_note` text DEFAULT NULL,
  `condition_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`condition_photos`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `craft_inspection_items_craft_inspection_id_foreign` (`craft_inspection_id`),
  KEY `craft_inspection_items_order_detail_id_foreign` (`order_detail_id`),
  CONSTRAINT `craft_inspection_items_craft_inspection_id_foreign` FOREIGN KEY (`craft_inspection_id`) REFERENCES `craft_inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `craft_inspection_items_order_detail_id_foreign` FOREIGN KEY (`order_detail_id`) REFERENCES `order_details` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inspection_items`
--

LOCK TABLES `craft_inspection_items` WRITE;
/*!40000 ALTER TABLE `craft_inspection_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `craft_inspection_items` VALUES
(1,1,'cpu',8,'{\"model\":\"AMD Ryzen 7 9800X3D\",\"serial\":\"9MP6309Q50163\",\"batch\":\"CF 2519PGEn\",\"visual\":\"sound\",\"pins\":\"sound\"}',1,1,1,1,'sound',NULL,'[\"craft-inspections\\/iAviTspUSTlaoMiI0yPT99Ap6SThWOpH33uwFYSg.jpg\"]','intact',NULL,'[\"craft-inspections\\/7hX4AJNsSenXBubkCl07clivaeJ69ut35lFOLEfp.png\"]','sound_pristine',NULL,'[\"craft-inspections\\/cMgeFn6GLPDVZ7WfXbLBP2XXDfD1KmvEXA193KvL.jpg\"]','2026-07-07 12:17:37','2026-07-08 13:42:26',NULL),
(2,1,'mbd',9,'{\"model\":\"Gigabyte X870 Aorus Stealth ICE\",\"serial\":\"SN254750054000\",\"cpu_socket\":\"sound\",\"dimm_slot\":\"sound\",\"pcie_slots\":\"sound\",\"m2_slots\":\"sound\",\"vrm_heatsinks\":\"sound\",\"rear_io\":\"sound\",\"cmos_batt\":\"sound\",\"accessories\":\"sound\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(3,1,'gpu',NULL,'{\"model\":\"GeForce RTX 5080 16G Gaming Trio OC White\",\"serial\":\"602-V531-290B2507000452\",\"connector_pin\":\"sound\",\"pcie_connector\":\"sound\",\"power_connector\":\"sound\",\"fan_rotation\":\"sound\",\"vrm_heatsinks\":\"sound\",\"backplate\":\"sound\",\"rgb\":\"sound\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(4,1,'ram',10,'{\"model\":\"G.Skill Trident Z5 Royal Neo\",\"serial\":\"F5-6000J2836G32GX2-TR5NS\",\"capacity\":\"64GB (32GB x 2)\",\"speed\":\"DDR5-6000 MHz\",\"timing\":\"CL28-36-36-96\",\"voltage\":\"1.40V\",\"quantity\":\"2 modules\",\"heatspreader\":\"sound\",\"gold_contacts\":\"sound\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(5,1,'ssd',13,'{\"type\":\"NVMe PCIe 5.0 SSD\",\"model\":\"Samsung 9100 Pro\",\"serial\":\"S7YFNJ0Y604707V\",\"capacity\":\"2TB\",\"connector\":\"M.2 2280\",\"contact_pins\":\"sound\",\"label_condition\":\"sound\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(6,1,'aio',12,'{\"model\":\"HydroShift II LCD-C 360N\",\"serial\":\"H236NW250601375\",\"radiator\":\"sound\",\"pump_housing\":\"sound\",\"cold_plate\":\"sound\",\"tubes\":\"sound\",\"fans\":\"fanless\",\"accessories\":\"sound\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(7,1,'psu',11,'{\"model\":\"Corsair RM1000x Shift\",\"serial\":\"A6GLA512K00M98\",\"wattage\":\"1000 Watt\",\"efficiency_rating\":\"80 Plus Gold\",\"modularity\":\"Fully Modular\",\"cables_inclusion\":\"Complete\",\"housing\":\"\",\"fan\":\"\"}',1,1,1,1,'sound',NULL,'[]','intact',NULL,'[]','sound_pristine',NULL,'[]','2026-07-07 12:17:37','2026-07-07 12:17:37',NULL),
(9,4,'cpu',15,'{\"model\":\"AMD Ryzen 7 9800X3D\",\"serial\":\"test\",\"batch\":\"test\",\"visual\":\"test\",\"pins\":\"test\"}',1,1,1,1,'sound',NULL,'[\"craft-inspections\\/EOtzpnKcQ81BjUZe5kzzf24CWlLgAg6tYsl4g4FK.gif\"]','intact',NULL,'[\"craft-inspections\\/ZP2xsG5xBn0sV1dEb6v0awzQSG3MoBYJ4Jqo1m33.gif\"]','sound_pristine',NULL,'[\"craft-inspections\\/SklxAQIyVT7ljzjzJfvummpiYxY82TwdbobLJxob.gif\"]','2026-07-09 14:25:23','2026-07-09 14:25:23',NULL),
(10,6,'cpu',60,'{\"model\":\"AMD Ryzen 7 9800X3D\",\"serial\":\"1324123123123\",\"batch\":\"bsdw\",\"visual\":\"sound\",\"pins\":\"sound\"}',1,1,1,1,'sound',NULL,'[\"craft-inspections\\/LYSCzcG7nTAzELA07PO8UI7RV6woV2lrgT8YLRJa.gif\"]','intact',NULL,'[\"craft-inspections\\/eaO2wmdjIFVnCBlh53UgKbjYN8mSTK3c6PXOCyeF.gif\"]','issue','dent on the top side of box','[]','2026-07-09 15:46:41','2026-07-09 15:46:41',NULL);
/*!40000 ALTER TABLE `craft_inspection_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `craft_inspections`
--

DROP TABLE IF EXISTS `craft_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `craft_inspections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `phase` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `round` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status` varchar(191) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `craft_inspections_order_id_foreign` (`order_id`),
  CONSTRAINT `craft_inspections_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `craft_inspections`
--

LOCK TABLES `craft_inspections` WRITE;
/*!40000 ALTER TABLE `craft_inspections` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `craft_inspections` VALUES
(1,2,2,1,'draft','2026-07-07 04:19:17','2026-07-07 04:19:17',NULL),
(2,2,2,2,'draft','2026-07-07 12:44:35','2026-07-07 12:44:35',NULL),
(3,1,2,1,'draft','2026-07-07 15:00:18','2026-07-07 15:00:18',NULL),
(4,3,2,1,'completed','2026-07-09 14:23:47','2026-07-09 14:27:13',NULL),
(5,3,2,2,'completed','2026-07-09 14:27:33','2026-07-09 14:29:39',NULL),
(6,6,2,1,'completed','2026-07-09 15:45:14','2026-07-09 15:47:05',NULL),
(7,6,2,2,'draft','2026-07-09 15:47:13','2026-07-09 15:47:13',NULL),
(8,11,2,1,'draft','2026-07-16 13:43:19','2026-07-16 13:43:19',NULL),
(9,9,2,1,'draft','2026-07-16 13:43:26','2026-07-16 13:43:26',NULL);
/*!40000 ALTER TABLE `craft_inspections` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customer_progress`
--

DROP TABLE IF EXISTS `customer_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `progress_percentage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `file_path` varchar(191) DEFAULT NULL,
  `file_name` varchar(191) DEFAULT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `updated_by` varchar(191) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_progress_customer_id_index` (`customer_id`),
  KEY `customer_progress_order_id_index` (`order_id`),
  KEY `customer_progress_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_progress`
--

LOCK TABLES `customer_progress` WRITE;
/*!40000 ALTER TABLE `customer_progress` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `customer_progress` VALUES
(1,4,7,'Cable Management','Routing PSU and GPU cables','in_progress',40,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-11 14:20:44','2026-07-11 14:20:53','2026-07-11 14:20:53'),
(2,4,NULL,'Cable Management',NULL,'completed',100,NULL,NULL,NULL,NULL,NULL,'2026-07-11 14:24:29','2026-07-11 14:24:29','2026-07-11 14:24:29','2026-07-11 14:24:29'),
(3,4,1,'Build Assembly','PC build fully assembled and cable-managed.','completed',100,NULL,NULL,NULL,NULL,'Test Staff','2026-07-11 10:00:00','2026-07-10 09:00:00','2026-07-11 10:00:00',NULL),
(4,19,2,'Cable Management','Routing and sleeving completed.','completed',100,NULL,NULL,NULL,NULL,'Test Staff','2026-07-12 14:30:00','2026-07-11 11:00:00','2026-07-12 14:30:00',NULL),
(5,20,3,'Final QC Testing','Running stress test and boot verification.','in_progress',75,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-13 09:15:00','2026-07-15 16:20:00',NULL),
(6,20,4,'Parts Received','All components received and inspected.','completed',100,NULL,NULL,NULL,NULL,'Test Staff','2026-07-10 08:00:00','2026-07-09 15:00:00','2026-07-10 08:00:00',NULL),
(7,20,5,'Build Assembly','Motherboard and CPU installed, working on cooling.','in_progress',50,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-14 10:00:00','2026-07-16 12:00:00',NULL),
(8,21,6,'Packaging','Build boxed and ready for delivery.','completed',100,NULL,NULL,NULL,NULL,'Test Staff','2026-07-11 17:00:00','2026-07-11 09:00:00','2026-07-11 17:00:00',NULL),
(9,20,8,'Parts Received','Awaiting parts delivery from supplier.','pending',0,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-13 09:00:00','2026-07-13 09:00:00',NULL),
(10,20,9,'Build Assembly','Case wiring in progress.','in_progress',40,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-14 11:00:00','2026-07-17 13:00:00',NULL),
(11,20,10,'Cable Management','Sleeving custom cables for PSU.','in_progress',60,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-15 10:00:00','2026-07-18 15:00:00',NULL),
(12,20,11,'Final QC Testing','On hold pending customer confirmation on RGB color.','on_hold',30,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-16 09:00:00','2026-07-19 10:00:00',NULL),
(13,4,7,'Build Assembly','ULTRA-tier build assembly in progress, awaiting GPU delivery.','in_progress',65,NULL,NULL,NULL,NULL,'Test Staff',NULL,'2026-07-18 10:00:00','2026-07-20 14:00:00',NULL);
/*!40000 ALTER TABLE `customer_progress` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(191) NOT NULL,
  `full_name` varchar(191) DEFAULT NULL,
  `preferred_name` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `contact_method` varchar(191) DEFAULT NULL,
  `contact_other` varchar(191) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `hear_about` varchar(191) DEFAULT NULL,
  `hear_about_other` varchar(191) DEFAULT NULL,
  `referred_by` varchar(191) DEFAULT NULL,
  `consent` tinyint(1) DEFAULT NULL,
  `approve` tinyint(1) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `update_token` varchar(191) DEFAULT NULL,
  `update_used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_id_unique` (`customer_id`),
  UNIQUE KEY `customers_update_token_unique` (`update_token`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `customers` VALUES
(3,'QVCST-0001','MUHAMMAD FARIS ISKANDAR BIN SHAMSIR','BruhRis','fariskandar99@gmail.com','+60172109876','47810','WhatsApp',NULL,'Custom PC build','Friend / Referral',NULL,'Najmi Zairul',1,1,'2026-07-09 15:30:49','ac450a3c-93a4-4e70-aaac-5a46e5d11578',1,'2025-12-29 20:54:33','2026-07-09 15:30:49',NULL),
(4,'QVCST-0002','MUHAMMAD NAJMI NOOR ZAIRUL','Najmi','najminoorzairul@gmail.com','+60197017321','A-1-10, Cita Damansara, Jalan PJU 3/27, Sunway Damansara','WhatsApp',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'711774c2-478a-4c85-808e-18848a78e45a',1,'2025-12-29 21:32:12','2025-12-30 00:04:11',NULL),
(5,'QVCST-0003','NURSYAZWANI BINTI AHMAD NIZAM','Wani','wannieq8@gmail.com','+60197266130','A-1-10','WhatsApp',NULL,NULL,'TikTok',NULL,NULL,1,1,'2026-07-09 15:30:43','9f8be78c-9ba9-4218-9e1c-c3028a74a8a6',1,'2025-12-29 21:33:04','2026-07-09 15:30:43',NULL),
(6,'QVCST-0004','MUHAMMAD EIRFAN BIN NOOR ZAIRUL','Epan','eirfan019@gmail.com','+60197091129','No 2&4, Jalan Perdana 2/42, Taman Bukit Perdana 2, 83000, Batu Pahat,Johor','WhatsApp',NULL,'Nice',NULL,NULL,NULL,1,1,'2026-01-11 07:13:03','01064af6-083e-4e5e-9722-b05921e9876f',1,'2025-12-29 22:30:09','2026-01-11 07:13:03',NULL),
(7,'QVCST-0005','MUHAMMAD IZZHAZIQ BIN MOHD RAJIL','Izz','Izzhaziq1117@gmail.com','+601126605294','A-404, Tingkat 3, Palma Perak Apartment, Jalan Cecawi 6/6, 47810,Petaling Jaya, Selangor','WhatsApp',NULL,'Pc build','Friend / Referral',NULL,'Najmi Zairul',1,1,'2026-01-11 07:05:43','420c2946-d5da-45d3-ac54-75f521152dbc',1,'2025-12-29 21:35:06','2026-01-11 07:05:43',NULL),
(9,'QVCST-0006','TEST DATA','test',NULL,'+602603123123','dasasd.12312312,123,daman','TikTok',NULL,'asdasdasd','Event / Booth',NULL,NULL,1,1,'2026-01-11 07:12:56','08bcdc01-a436-43ee-82f8-61d7f30f938d',1,'2025-12-31 09:22:52','2026-01-11 07:12:56',NULL),
(11,'QVCST-0007','AHMAD ALBAB BIN ISMAIL','Ahmad','ahmad@gmail.com','+600232323232','Damansara, 47810, Petaling jaya,Selangor','Facebook',NULL,'aswdasdasd','TikTok',NULL,NULL,1,1,'2025-10-01 05:23:04','1c422c11-129e-45f5-af66-6641cccc2525',1,'2026-01-01 05:16:58','2026-01-01 05:23:04',NULL),
(12,'QVCST-0008','SYED IQBAL','Iqbal','iqbal@mail.com','+60912121212','atas klang','Instagram',NULL,'sdasdsd','Friend / Referral',NULL,'megat',1,1,'2026-01-18 01:43:25','fee7c0b0-4b5e-49f0-ae7e-4db95a1022b6',1,'2026-01-18 01:40:54','2026-01-18 01:43:25',NULL),
(19,'QVCST-0009','WAWA FFF','wawa','wawa@gmail.com','+600234234234','werwrwerer',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'cde29dba-c6c9-4941-bb47-ec6b96a5f111',0,'2026-03-21 02:23:08','2026-03-21 02:23:08',NULL),
(20,'QVCST-0010','TEST','test1','test@gmail.com','+60123456789','test1','WhatsApp',NULL,NULL,NULL,NULL,NULL,1,1,'2026-07-09 14:07:03','dc0de823-f55f-430f-882e-85a6548cb116',1,'2026-07-09 14:03:54','2026-07-09 14:07:03',NULL),
(21,'QVCST-0011','FARIS BIN FARIS','Faris','faris@gmail.com','+601912312312','kota damansara seksyen 7','Discord',NULL,'mas amba','Instagram',NULL,NULL,1,1,'2026-07-09 15:33:07','93eb19ba-9628-48de-bb2c-668cc732a646',1,'2026-07-09 15:31:42','2026-07-09 15:33:07',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `destination`
--

DROP TABLE IF EXISTS `destination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `destination` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destination`
--

LOCK TABLES `destination` WRITE;
/*!40000 ALTER TABLE `destination` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `destination` VALUES
(1,'IE_QVSE',1,'2026-07-02 12:06:14','2026-07-02 12:06:14',NULL),
(2,'I_QVTD',1,'2026-07-02 12:07:01','2026-07-02 12:07:01',NULL),
(3,'I_QVMR',1,'2026-07-11 15:12:49','2026-07-11 15:12:49',NULL),
(4,'IE_QVMR',1,'2026-07-11 15:12:49','2026-07-11 15:12:49',NULL);
/*!40000 ALTER TABLE `destination` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(191) DEFAULT NULL,
  `file_path` varchar(191) NOT NULL,
  `file_name` varchar(191) NOT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(191) NOT NULL,
  `address` varchar(191) NOT NULL,
  `sallery` varchar(191) NOT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `nid` varchar(191) DEFAULT NULL,
  `join_date` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `details` text NOT NULL,
  `amount` varchar(191) NOT NULL,
  `expenses_date` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `extras`
--

DROP TABLE IF EXISTS `extras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `extras` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vat` int(11) DEFAULT NULL,
  `logo` varchar(191) NOT NULL,
  `favicon` varchar(191) DEFAULT NULL,
  `phone` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `address` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `extras`
--

LOCK TABLES `extras` WRITE;
/*!40000 ALTER TABLE `extras` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `extras` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_care`
--

DROP TABLE IF EXISTS `inv_care`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_care` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `inv_care` varchar(255) NOT NULL,
  `care_id` int(20) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit_cost` int(20) NOT NULL,
  `max_stock` int(20) NOT NULL,
  `current_stock` int(20) NOT NULL,
  `category` int(20) NOT NULL,
  `status` int(20) NOT NULL,
  `generate_id` int(20) NOT NULL,
  `serial_label` int(20) NOT NULL,
  `warranty_starts` datetime NOT NULL,
  `warranty_duration` int(20) NOT NULL,
  `warranty_ends` datetime NOT NULL,
  `manufacturer` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_care`
--

LOCK TABLES `inv_care` WRITE;
/*!40000 ALTER TABLE `inv_care` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `inv_care` VALUES
(1,'IC-0001',2,'QVSK-SPARE-CPU-001','AMD Ryzen 7 9800X3D (Spare)',2399,5,3,1,1,0,0,'2026-01-01 00:00:00',36,'2029-01-01 00:00:00','AMD','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL),
(2,'IC-0002',1,'QVSK-SPARE-CPU-002','INTEL Core Ultra 7 265 (Spare)',1699,4,2,1,1,0,0,'2026-01-01 00:00:00',36,'2029-01-01 00:00:00','INTEL','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL),
(3,'IC-0003',7,'QVSK-SPARE-GPU-001','MSI Trio X White RTX 5080 16GB (Spare)',4999,3,1,3,1,0,0,'2026-01-01 00:00:00',36,'2029-01-01 00:00:00','MSI','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL),
(4,'IC-0004',9,'QVSK-SPARE-GPU-002','ASUS ROG Strix RTX 5070 Ti 16GB (Spare)',3799,3,2,3,1,0,0,'2026-01-01 00:00:00',60,'2031-01-01 00:00:00','ASUS','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL),
(5,'IC-0005',8,'QVSK-SPARE-RAM-001','G.SKILL Trident Z5 32GB Kit (Spare)',899,6,4,5,1,0,0,'2026-01-01 00:00:00',24,'2028-01-01 00:00:00','G.Skill','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL),
(6,'IC-0006',10,'QVSK-SPARE-GPU-001','MSI Trio X White RTX 5080 16GB (Spare)',4999,3,1,3,1,0,0,'2026-01-01 00:00:00',36,'2029-01-01 00:00:00','MSI','2026-07-12 18:18:12','2026-07-12 18:18:12',NULL);
/*!40000 ALTER TABLE `inv_care` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_excl_merch`
--

DROP TABLE IF EXISTS `inv_excl_merch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_excl_merch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inv_excl_merch_id` varchar(50) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit_cost` int(11) NOT NULL DEFAULT 0,
  `max_stock` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `to_restock` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `generate_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_excl_merch_inv_excl_merch_id_unique` (`inv_excl_merch_id`),
  KEY `inv_excl_merch_sku_code_index` (`sku_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_excl_merch`
--

LOCK TABLES `inv_excl_merch` WRITE;
/*!40000 ALTER TABLE `inv_excl_merch` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `inv_excl_merch` VALUES
(1,'IE-QVMR-0001','QVSKU 0010','Quivitech Carbon Fiber Keychain',43,50,50,15,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(2,'IE-QVMR-0002','QVSKU 0011','Quivitech Full Grain Leather Keychain',16,52,52,16,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(3,'IE-QVMR-0003','QVSKU 0020','Quivitech Neoprene Pouch',8,200,200,60,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL);
/*!40000 ALTER TABLE `inv_excl_merch` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_excl_serve`
--

DROP TABLE IF EXISTS `inv_excl_serve`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_excl_serve` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inv_excl_serve` varchar(255) NOT NULL,
  `serve_data_id` int(11) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit_cost` int(11) NOT NULL,
  `max_stock` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `to_restock` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `generate_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_excl_serve`
--

LOCK TABLES `inv_excl_serve` WRITE;
/*!40000 ALTER TABLE `inv_excl_serve` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `inv_excl_serve` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_merch`
--

DROP TABLE IF EXISTS `inv_merch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_merch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inv_merch_id` varchar(50) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit_cost` int(11) NOT NULL DEFAULT 0,
  `max_stock` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `to_restock` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `generate_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_merch_inv_merch_id_unique` (`inv_merch_id`),
  KEY `inv_merch_sku_code_index` (`sku_code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_merch`
--

LOCK TABLES `inv_merch` WRITE;
/*!40000 ALTER TABLE `inv_merch` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `inv_merch` VALUES
(1,'I-QVMR-0001','QVSKU 0005','Quivitech White Embroidery Keychain',6,200,200,60,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(2,'I-QVMR-0002','QVSKU 0006','Quivitech Red Eagle Hook Keychain',8,50,50,15,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(3,'I-QVMR-0003','QVSKU 0007','Quivitech Yellow Eagle Hook Keychain',8,50,50,15,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(4,'I-QVMR-0004','QVSKU 0008','Quivitech Blue Eagle Hook Keychain',8,51,51,16,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(5,'I-QVMR-0005','QVSKU 0009','Quivitech Pink Eagle Hook Keychain',8,51,51,16,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(6,'I-QVMR-0006','QVSKU 0016','Quivitech 2cm x 15cm Velcro Back to Back',1,400,400,120,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(7,'I-QVMR-0007','QVSKU 0017','Quivitech 1\" x 6\" Velcro OneWrap',6,300,300,90,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(8,'I-QVMR-0008','QVSKU 0018','Quivitech Microfiber Pouch',7,200,200,60,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL),
(9,'I-QVMR-0009','QVSKU 0019','Quivitech Polymer Pouch',8,200,200,60,1,1,'2026-07-14 15:59:49','2026-07-14 15:59:49',NULL);
/*!40000 ALTER TABLE `inv_merch` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_move`
--

DROP TABLE IF EXISTS `inv_move`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_move` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `movement_id` varchar(50) NOT NULL,
  `date` datetime NOT NULL,
  `master_sku_id` int(10) unsigned NOT NULL,
  `destination_id` int(10) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(191) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'Inventory',
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `unit_cost` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_move_movement_id_unique` (`movement_id`),
  KEY `inv_move_master_sku_id_index` (`master_sku_id`),
  KEY `inv_move_destination_id_index` (`destination_id`),
  KEY `inv_move_order_id_index` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_move`
--

LOCK TABLES `inv_move` WRITE;
/*!40000 ALTER TABLE `inv_move` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `inv_move` VALUES
(1,'MVMT-0001','2025-11-12 00:00:00',6,1,NULL,'Quivitech Essential Kit Box','Inventory',300,19.0100,'2026-07-11 15:48:17','2026-07-12 05:08:00',NULL),
(2,'MVMT-0002','2025-11-12 00:00:00',7,1,NULL,'Quivitech Prime Series Box','Inventory',200,29.1800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(3,'MVMT-0003','2025-11-12 00:00:00',8,1,NULL,'Quivitech Collector\'s Edition Box','Inventory',200,47.8000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(4,'MVMT-0004','2025-11-12 00:00:00',9,1,NULL,'Quivitech The Stash Screw Box','Inventory',500,2.8100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(5,'MVMT-0005','2025-11-12 00:00:00',10,3,NULL,'Quivitech White Embroidery Keychain','Inventory',200,5.5000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(6,'MVMT-0006','2025-11-12 00:00:00',11,3,NULL,'Quivitech Red Eagle Hook Keychain','Inventory',50,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(7,'MVMT-0007','2025-11-12 00:00:00',12,3,NULL,'Quivitech Yellow Eagle Hook Keychain','Inventory',50,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(8,'MVMT-0008','2025-11-12 00:00:00',13,3,NULL,'Quivitech Blue Eagle Hook Keychain','Inventory',50,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(9,'MVMT-0009','2025-11-12 00:00:00',14,3,NULL,'Quivitech Pink Eagle Hook Keychain','Inventory',50,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(10,'MVMT-0010','2025-11-12 00:00:00',15,4,NULL,'Quivitech Carbon Fiber Keychain','Inventory',50,43.1800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(11,'MVMT-0011','2025-11-12 00:00:00',16,4,NULL,'Quivitech Full Grain Leather Keychain','Inventory',50,16.0000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(12,'MVMT-0012','2025-11-12 00:00:00',17,1,NULL,'Quivitech Essential Kit Perk Card','Inventory',200,2.9000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(13,'MVMT-0013','2025-11-12 00:00:00',18,1,NULL,'Quivitech Prime Series Perk Card','Inventory',200,2.9000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(14,'MVMT-0014','2025-11-12 00:00:00',19,1,NULL,'Quivitech Collector\'s Edition Perk Card','Inventory',200,2.9000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(15,'MVMT-0015','2025-11-12 00:00:00',20,3,NULL,'Quivitech 2cm x 15cm Velcro Back to Back','Inventory',400,0.7800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(16,'MVMT-0016','2025-11-12 00:00:00',21,3,NULL,'Quivitech 1\" x 6\" Velcro OneWrap','Inventory',300,6.3000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(17,'MVMT-0017','2025-11-12 00:00:00',22,3,NULL,'Quivitech Microfiber Pouch','Inventory',200,7.3500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(18,'MVMT-0018','2025-11-12 00:00:00',23,3,NULL,'Quivitech Polymer Pouch','Inventory',200,7.8000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(19,'MVMT-0019','2025-11-12 00:00:00',24,4,NULL,'Quivitech Neoprene Pouch','Inventory',200,7.7100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(20,'MVMT-0020','2025-11-12 00:00:00',6,1,NULL,'Quivitech Essential Kit Box','Inventory',10,19.0100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(21,'MVMT-0021','2025-11-12 00:00:00',7,1,NULL,'Quivitech Prime Series Box','Inventory',14,14.0000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(22,'MVMT-0022','2025-11-12 00:00:00',8,1,NULL,'Quivitech Collector\'s Edition Box','Inventory',8,47.8000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(23,'MVMT-0023','2025-11-12 00:00:00',9,1,NULL,'Quivitech The Stash Screw Box','Inventory',50,2.8100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(24,'MVMT-0024','2025-11-12 00:00:00',13,3,NULL,'Quivitech Blue Eagle Hook Keychain','Inventory',1,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(25,'MVMT-0025','2025-11-12 00:00:00',14,3,NULL,'Quivitech Pink Eagle Hook Keychain','Inventory',1,7.8500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(26,'MVMT-0026','2025-11-12 00:00:00',16,4,NULL,'Quivitech Full Grain Leather Keychain','Inventory',2,16.0000,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(27,'MVMT-0027','2025-11-12 00:00:00',25,2,NULL,'MOLEX Black 8 EPS Pin  ATX Connector','Inventory',100,1.8400,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(28,'MVMT-0028','2025-11-12 00:00:00',26,2,NULL,'MOLEX Blue 8 EPS Pin ATX Connector','Inventory',100,1.9758,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(29,'MVMT-0029','2025-11-12 00:00:00',27,2,NULL,'MOLEX Blue 10 MB Pin  ATX Connector','Inventory',100,1.8100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(30,'MVMT-0030','2025-11-12 00:00:00',28,2,NULL,'MOLEX Black 10 MB Pin ATX Connector','Inventory',100,1.0500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(31,'MVMT-0031','2025-11-12 00:00:00',29,2,NULL,'MOLEX Black 12V 2x6 PCIe Pin ATX Connector','Inventory',100,0.6600,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(32,'MVMT-0032','2025-11-12 00:00:00',30,2,NULL,'MDPC-X 12V 2x6 PCIe Pin ATX Connector','Inventory',10,17.9800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(33,'MVMT-0033','2025-11-12 00:00:00',31,2,NULL,'MOLEX Blue 18 MB Pin  ATX Connector','Inventory',100,3.9100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(34,'MVMT-0034','2025-11-12 00:00:00',32,2,NULL,'MDPC-X  18  MB Pin ATX Connector','Inventory',20,5.6500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(35,'MVMT-0035','2025-11-12 00:00:00',33,2,NULL,'MOLEX Blue 24 MB Pin  ATX Connector','Inventory',100,5.7600,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(36,'MVMT-0036','2025-11-12 00:00:00',34,2,NULL,'MDPC-X  24  MB Pin ATX Connector','Inventory',20,6.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(37,'MVMT-0037','2025-11-12 00:00:00',35,2,NULL,'MDPC-X 8 Pin Cable Comb','Inventory',100,4.8700,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(38,'MVMT-0038','2025-11-12 00:00:00',36,2,NULL,'MDPC-X 12V 2x6 PCIe Pin Cable Comb','Inventory',30,6.0900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(39,'MVMT-0039','2025-11-12 00:00:00',37,2,NULL,'MDPC-X 24 Pin Cable Comb','Inventory',35,7.3100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(40,'MVMT-0040','2025-11-12 00:00:00',38,2,NULL,'MDPC-X 4:1 Heatshrink Small','Inventory',204,0.2500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(41,'MVMT-0041','2025-11-12 00:00:00',39,2,NULL,'MDPC-X 15 AWG Pin Terminal','Inventory',2050,0.3900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(42,'MVMT-0042','2025-11-12 00:00:00',40,2,NULL,'MDPC-X 17 AWG Pin Terminal','Inventory',500,0.3900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(43,'MVMT-0043','2025-11-12 00:00:00',41,2,NULL,'MDPC-X Blackest Black Cable Sleeve XTC','Inventory',200,2.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(44,'MVMT-0044','2025-11-12 00:00:00',42,2,NULL,'MDPC-X XXX White Cable Sleeve XTC','Inventory',200,2.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(45,'MVMT-0045','2025-11-12 00:00:00',43,2,NULL,'MDPC-X Gold Cable Sleeve XTC','Inventory',100,2.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(46,'MVMT-0046','2025-11-12 00:00:00',44,2,NULL,'MDPC-X Blackest Black Cable Sleeve MICRO','Inventory',30,3.1700,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(47,'MVMT-0047','2025-11-12 00:00:00',45,2,NULL,'MDPC-X XXX White Cable Sleeve MICRO','Inventory',30,3.5100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(48,'MVMT-0048','2025-11-12 00:00:00',46,2,NULL,'MDPC-X Gold Cable Sleeve MICRO','Inventory',30,3.5100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(49,'MVMT-0049','2025-11-12 00:00:00',47,2,NULL,'MDPC-X Platinum X Cable Sleeve XTC','Inventory',100,2.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(50,'MVMT-0050','2025-11-12 00:00:00',48,2,NULL,'MDPC-X Perfect Pink Cable Sleeve XTC','Inventory',100,2.1900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(51,'MVMT-0051','2025-11-12 00:00:00',49,2,NULL,'MDPC-X White 15-AWG Wire','Inventory',500,3.5100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(52,'MVMT-0052','2025-11-12 00:00:00',50,2,NULL,'MDPC-X Grey 17-AWG Wire','Inventory',50,4.4800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(53,'MVMT-0053','2025-11-12 00:00:00',51,2,NULL,'MDPC-X Black 23-AWG Wire','Inventory',30,1.6600,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(54,'MVMT-0054','2025-11-12 00:00:00',52,2,NULL,'MDPC-X 3:1 Heatshrink Micro','Inventory',1,0.2500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(55,'MVMT-0055','2025-11-12 00:00:00',53,2,NULL,'MDPC-X 8 PCIe Pin  ATX Connector','Inventory',82,6.4300,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(56,'MVMT-0056','2025-11-12 00:00:00',54,2,NULL,'MOLEX Black 8 PCIe Pin ATX Connector','Inventory',100,1.8800,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(57,'MVMT-0057','2025-11-14 00:00:00',30,2,7,'MDPC-X 12V 2x6 PCIe Pin ATX Connector','Inventory',2,17.9800,'2026-07-11 15:48:17','2026-07-12 01:49:40',NULL),
(58,'MVMT-0058','2025-11-14 00:00:00',32,2,NULL,'MDPC-X  18  MB Pin ATX Connector','Inventory',3,5.6500,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(59,'MVMT-0059','2025-11-14 00:00:00',37,2,NULL,'MDPC-X 24 Pin Cable Comb','Inventory',2,7.3100,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(60,'MVMT-0060','2025-11-14 00:00:00',36,2,NULL,'MDPC-X 12V 2x6 PCIe Pin Cable Comb','Inventory',1,6.0900,'2026-07-11 15:48:17','2026-07-11 15:51:26',NULL),
(61,'MVMT-0061','2026-07-11 00:00:00',55,1,NULL,'Test Widget','Inventory',5,9.9900,'2026-07-11 15:53:44','2026-07-11 15:56:11','2026-07-11 15:56:11');
/*!40000 ALTER TABLE `inv_move` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `inv_thread`
--

DROP TABLE IF EXISTS `inv_thread`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_thread` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `inv_thread_id` varchar(50) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit_cost` int(11) NOT NULL DEFAULT 0,
  `max_stock` int(11) NOT NULL DEFAULT 0,
  `current_stock` int(11) NOT NULL DEFAULT 0,
  `to_restock` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `generate_id` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_thread_inv_thread_id_unique` (`inv_thread_id`),
  KEY `inv_thread_sku_code_index` (`sku_code`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inv_thread`
--

LOCK TABLES `inv_thread` WRITE;
/*!40000 ALTER TABLE `inv_thread` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `inv_thread` VALUES
(1,'I-QVTD-0001','QVSKU 0021','MOLEX Black 8 EPS Pin  ATX Connector',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(2,'I-QVTD-0002','QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(3,'I-QVTD-0003','QVSKU 0023','MOLEX Blue 10 MB Pin  ATX Connector',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(4,'I-QVTD-0004','QVSKU 0024','MOLEX Black 10 MB Pin ATX Connector',1,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(5,'I-QVTD-0005','QVSKU 0025','MOLEX Black 12V 2x6 PCIe Pin ATX Connector',1,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(6,'I-QVTD-0006','QVSKU 0026','MDPC-X 12V 2x6 PCIe Pin ATX Connector',18,12,12,4,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(7,'I-QVTD-0007','QVSKU 0027','MOLEX Blue 18 MB Pin  ATX Connector',4,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(8,'I-QVTD-0008','QVSKU 0028','MDPC-X  18  MB Pin ATX Connector',6,23,23,7,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(9,'I-QVTD-0009','QVSKU 0029','MOLEX Blue 24 MB Pin  ATX Connector',6,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(10,'I-QVTD-0010','QVSKU 0030','MDPC-X  24  MB Pin ATX Connector',6,20,20,6,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(11,'I-QVTD-0011','QVSKU 0031','MDPC-X 8 Pin Cable Comb',5,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(12,'I-QVTD-0012','QVSKU 0032','MDPC-X 12V 2x6 PCIe Pin Cable Comb',6,31,31,10,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(13,'I-QVTD-0013','QVSKU 0033','MDPC-X 24 Pin Cable Comb',7,37,37,12,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(14,'I-QVTD-0014','QVSKU 0034','MDPC-X 4:1 Heatshrink Small',0,204,204,62,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(15,'I-QVTD-0015','QVSKU 0035','MDPC-X 15 AWG Pin Terminal',0,2050,2050,615,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(16,'I-QVTD-0016','QVSKU 0036','MDPC-X 17 AWG Pin Terminal',0,500,500,150,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(17,'I-QVTD-0017','QVSKU 0038','MDPC-X Blackest Black Cable Sleeve XTC',2,200,200,60,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(18,'I-QVTD-0018','QVSKU 0039','MDPC-X XXX White Cable Sleeve XTC',2,200,200,60,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(19,'I-QVTD-0019','QVSKU 0040','MDPC-X Gold Cable Sleeve XTC',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(20,'I-QVTD-0020','QVSKU 0041','MDPC-X Blackest Black Cable Sleeve MICRO',3,30,30,9,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(21,'I-QVTD-0021','QVSKU 0042','MDPC-X XXX White Cable Sleeve MICRO',4,30,30,9,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(22,'I-QVTD-0022','QVSKU 0043','MDPC-X Gold Cable Sleeve MICRO',4,30,30,9,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(23,'I-QVTD-0023','QVSKU 0044','MDPC-X Platinum X Cable Sleeve XTC',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(24,'I-QVTD-0024','QVSKU 0045','MDPC-X Perfect Pink Cable Sleeve XTC',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(25,'I-QVTD-0025','QVSKU 0046','MDPC-X White 15-AWG Wire',4,500,500,150,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(26,'I-QVTD-0026','QVSKU 0047','MDPC-X Grey 17-AWG Wire',4,50,50,15,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(27,'I-QVTD-0027','QVSKU 0048','MDPC-X Black 23-AWG Wire',2,30,30,9,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(28,'I-QVTD-0028','QVSKU 0049','MDPC-X 3:1 Heatshrink Micro',0,1,1,1,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(29,'I-QVTD-0029','QVSKU 0050','MDPC-X 8 PCIe Pin  ATX Connector',6,82,82,25,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL),
(30,'I-QVTD-0030','QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',2,100,100,30,1,1,'2026-07-14 16:40:17','2026-07-14 16:40:17',NULL);
/*!40000 ALTER TABLE `inv_thread` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `master_sku`
--

DROP TABLE IF EXISTS `master_sku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_sku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_code` varchar(50) NOT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `product_raw_id` bigint(20) unsigned DEFAULT NULL,
  `product_name` varchar(50) DEFAULT NULL,
  `from` varchar(50) DEFAULT NULL,
  `cost` varchar(50) DEFAULT NULL,
  `unit_type` varchar(50) DEFAULT NULL,
  `lkp_status_sku` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_sku`
--

LOCK TABLES `master_sku` WRITE;
/*!40000 ALTER TABLE `master_sku` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `master_sku` VALUES
(4,'test',2,NULL,'Test 1','Malaysia','1000.00','pcs',4,'2026-07-09 14:57:30','2026-07-09 14:57:30',NULL),
(6,'QVSKU 0001',NULL,NULL,'Quivitech Essential Kit Box',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(7,'QVSKU 0002',NULL,NULL,'Quivitech Prime Series Box',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(8,'QVSKU 0003',NULL,NULL,'Quivitech Collector\'s Edition Box',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(9,'QVSKU 0004',NULL,NULL,'Quivitech The Stash Screw Box',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(10,'QVSKU 0005',NULL,NULL,'Quivitech White Embroidery Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(11,'QVSKU 0006',NULL,NULL,'Quivitech Red Eagle Hook Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(12,'QVSKU 0007',NULL,NULL,'Quivitech Yellow Eagle Hook Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(13,'QVSKU 0008',NULL,NULL,'Quivitech Blue Eagle Hook Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(14,'QVSKU 0009',NULL,NULL,'Quivitech Pink Eagle Hook Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(15,'QVSKU 0010',NULL,NULL,'Quivitech Carbon Fiber Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(16,'QVSKU 0011',NULL,NULL,'Quivitech Full Grain Leather Keychain',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(17,'QVSKU 0012',NULL,NULL,'Quivitech Essential Kit Perk Card',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(18,'QVSKU 0013',NULL,NULL,'Quivitech Prime Series Perk Card',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(19,'QVSKU 0014',NULL,NULL,'Quivitech Collector\'s Edition Perk Card',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(20,'QVSKU 0016',NULL,NULL,'Quivitech 2cm x 15cm Velcro Back to Back',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(21,'QVSKU 0017',NULL,NULL,'Quivitech 1\" x 6\" Velcro OneWrap',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(22,'QVSKU 0018',NULL,NULL,'Quivitech Microfiber Pouch',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(23,'QVSKU 0019',NULL,NULL,'Quivitech Polymer Pouch',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(24,'QVSKU 0020',NULL,NULL,'Quivitech Neoprene Pouch',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(25,'QVSKU 0021',NULL,NULL,'MOLEX Black 8 EPS Pin  ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(26,'QVSKU 0022',NULL,NULL,'MOLEX Blue 8 EPS Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(27,'QVSKU 0023',NULL,NULL,'MOLEX Blue 10 MB Pin  ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(28,'QVSKU 0024',NULL,NULL,'MOLEX Black 10 MB Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(29,'QVSKU 0025',NULL,NULL,'MOLEX Black 12V 2x6 PCIe Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(30,'QVSKU 0026',NULL,NULL,'MDPC-X 12V 2x6 PCIe Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(31,'QVSKU 0027',NULL,NULL,'MOLEX Blue 18 MB Pin  ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(32,'QVSKU 0028',NULL,NULL,'MDPC-X  18  MB Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(33,'QVSKU 0029',NULL,NULL,'MOLEX Blue 24 MB Pin  ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(34,'QVSKU 0030',NULL,NULL,'MDPC-X  24  MB Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(35,'QVSKU 0031',NULL,NULL,'MDPC-X 8 Pin Cable Comb',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(36,'QVSKU 0032',NULL,NULL,'MDPC-X 12V 2x6 PCIe Pin Cable Comb',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(37,'QVSKU 0033',NULL,NULL,'MDPC-X 24 Pin Cable Comb',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(38,'QVSKU 0034',NULL,NULL,'MDPC-X 4:1 Heatshrink Small',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(39,'QVSKU 0035',NULL,NULL,'MDPC-X 15 AWG Pin Terminal',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(40,'QVSKU 0036',NULL,NULL,'MDPC-X 17 AWG Pin Terminal',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(41,'QVSKU 0038',NULL,NULL,'MDPC-X Blackest Black Cable Sleeve XTC',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(42,'QVSKU 0039',NULL,NULL,'MDPC-X XXX White Cable Sleeve XTC',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(43,'QVSKU 0040',NULL,NULL,'MDPC-X Gold Cable Sleeve XTC',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(44,'QVSKU 0041',NULL,NULL,'MDPC-X Blackest Black Cable Sleeve MICRO',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(45,'QVSKU 0042',NULL,NULL,'MDPC-X XXX White Cable Sleeve MICRO',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(46,'QVSKU 0043',NULL,NULL,'MDPC-X Gold Cable Sleeve MICRO',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(47,'QVSKU 0044',NULL,NULL,'MDPC-X Platinum X Cable Sleeve XTC',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(48,'QVSKU 0045',NULL,NULL,'MDPC-X Perfect Pink Cable Sleeve XTC',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(49,'QVSKU 0046',NULL,NULL,'MDPC-X White 15-AWG Wire',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(50,'QVSKU 0047',NULL,NULL,'MDPC-X Grey 17-AWG Wire',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(51,'QVSKU 0048',NULL,NULL,'MDPC-X Black 23-AWG Wire',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(52,'QVSKU 0049',NULL,NULL,'MDPC-X 3:1 Heatshrink Micro',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(53,'QVSKU 0050',NULL,NULL,'MDPC-X 8 PCIe Pin  ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(54,'QVSKU 0051',NULL,NULL,'MOLEX Black 8 PCIe Pin ATX Connector',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
(56,'QVSK-SPARE-CPU-001',NULL,NULL,'AMD Ryzen 7 9800X3D (Spare/RMA Unit)',NULL,'2399','pcs',1,'2026-07-12 18:15:08','2026-07-12 18:15:08',NULL),
(57,'QVSK-SPARE-CPU-002',NULL,NULL,'INTEL Core Ultra 7 265 (Spare/RMA Unit)',NULL,'1699','pcs',1,'2026-07-12 18:15:08','2026-07-12 18:15:08',NULL),
(58,'QVSK-SPARE-GPU-001',NULL,NULL,'MSI Trio X White RTX 5080 16GB (Spare/RMA Unit)',NULL,'4999','pcs',1,'2026-07-12 18:15:09','2026-07-12 18:15:09',NULL),
(59,'QVSK-SPARE-GPU-002',NULL,NULL,'ASUS ROG Strix RTX 5070 Ti 16GB (Spare/RMA Unit)',NULL,'3799','pcs',1,'2026-07-12 18:15:09','2026-07-12 18:15:09',NULL),
(60,'QVSK-SPARE-RAM-001',NULL,NULL,'G.SKILL Trident Z5 32GB (Spare)',NULL,'899','pcs',1,'2026-07-12 18:17:12','2026-07-12 18:17:12',NULL);
/*!40000 ALTER TABLE `master_sku` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meeting_details`
--

DROP TABLE IF EXISTS `meeting_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `initial_budget` decimal(10,2) DEFAULT NULL,
  `reason` tinyint(1) NOT NULL COMMENT '1: Work, 2: Gaming',
  `play_mode` tinyint(1) DEFAULT NULL COMMENT '1: Multiplayer, 2: Singleplayer',
  `include_monitor` tinyint(1) DEFAULT NULL,
  `include_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `theme_style` varchar(191) DEFAULT NULL,
  `preference` varchar(191) DEFAULT NULL,
  `exemption` varchar(191) DEFAULT NULL,
  `future_proof` tinyint(1) DEFAULT 0,
  `case_size` tinyint(1) DEFAULT NULL,
  `okay_with_aio` tinyint(1) DEFAULT 0,
  `gpu_sag` tinyint(1) DEFAULT NULL,
  `need_rgb` tinyint(1) DEFAULT 0,
  `qvcrf_tag` tinyint(1) DEFAULT 0,
  `qvse` tinyint(1) DEFAULT 0,
  `qvca` tinyint(1) DEFAULT 0,
  `qvtd` tinyint(1) DEFAULT 0,
  `qvtd_notes` text DEFAULT NULL,
  `target_build_date` datetime DEFAULT NULL,
  `target_location` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_details_meeting_id_foreign` (`meeting_id`),
  CONSTRAINT `meeting_details_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_details`
--

LOCK TABLES `meeting_details` WRITE;
/*!40000 ALTER TABLE `meeting_details` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `meeting_details` VALUES
(1,13,10000.00,2,2,1,'all',NULL,'wood','none','none',1,3,1,1,0,1,1,1,0,NULL,'2026-07-29 22:30:00','no 2, jalan bangsar','2026-07-09 14:32:04','2026-07-09 14:32:04',NULL),
(2,14,6000.00,2,2,NULL,NULL,NULL,'premium minimal wood accent','rog but can go asus or giga','asrock',1,3,1,1,1,0,1,1,1,'gpu cable dual colour','2026-08-10 17:45:00','kota damansara seksyen 7','2026-07-09 17:05:55','2026-07-09 17:05:55',NULL),
(3,16,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,0,NULL,0,0,0,0,0,NULL,NULL,NULL,'2026-07-16 14:28:35','2026-07-16 14:28:35',NULL);
/*!40000 ALTER TABLE `meeting_details` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` varchar(191) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `meeting_notes` text DEFAULT NULL,
  `document` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meetings_customer_id_foreign` (`customer_id`),
  CONSTRAINT `meetings_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `meetings` VALUES
(13,'QV-MEET-0001',20,'custom gaming pc test','2026-07-09','9:00 pm',NULL,'2026-07-09 14:30:43','2026-07-09 14:30:43',NULL),
(14,'QV-MEET-0014',21,'first meeting','2026-07-10',NULL,NULL,'2026-07-09 16:09:24','2026-07-09 16:09:24',NULL),
(15,'QV-MEET-0015',21,'2nd meeting','2026-07-17',NULL,NULL,'2026-07-16 14:25:47','2026-07-16 14:25:47',NULL),
(16,'QV-MEET-0016',20,'first meeting','2026-07-17',NULL,NULL,'2026-07-16 14:28:17','2026-07-16 14:28:17',NULL);
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('link','group','header') NOT NULL,
  `label` varchar(191) NOT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `route` varchar(191) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `divider_before` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_parent_id_foreign` (`parent_id`),
  CONSTRAINT `menu_items_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `menu_items` VALUES
(1,NULL,'link','Dashboard','fas fa-fw fa-tachometer-alt','/dashboard',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(2,NULL,'group','Customer','fas fa-users',NULL,1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(3,2,'header','Customer Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(4,3,'link','Pre Register Customer',NULL,'/customer/create',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(5,3,'link','Customer List',NULL,'/customer',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(6,NULL,'group','Meeting','fas fa-calendar-alt',NULL,2,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(7,6,'header','Meeting Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(8,7,'link','Meeting List',NULL,'/meeting',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(9,7,'link','Create Meeting',NULL,'/meeting/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(10,6,'header','Requirement Meeting',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(11,10,'link','Require Meeting List',NULL,'/meeting-details',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(12,10,'link','Create Require Meeting',NULL,'/meeting-details/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(13,6,'header','UAT Meeting',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(14,13,'link','UAT Meeting List',NULL,'/uat-meeting',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(15,13,'link','Create UAT Meeting',NULL,'/uat-meeting/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(16,NULL,'group','Customer Progress','fas fa-fw fa-tasks',NULL,3,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(17,16,'header','Progress Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(18,17,'link','All Progress Entries',NULL,'/customer-progress',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(19,17,'link','Add Progress Entry',NULL,'/customer-progress/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(20,NULL,'group','QuiviCraft','fas fa-fw fa-tools',NULL,4,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(21,20,'header','QuiviCraft Operations',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(22,21,'link','Create QuiviCraft',NULL,'/pos',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(23,21,'link','Today\'s QuiviCraft',NULL,'/orders',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(24,21,'link','Order QuiviCraft',NULL,'/orders/all',2,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(25,20,'header','Lookup Tables',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(26,25,'link','QuiviCraft Lookup',NULL,'/craft',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(27,25,'link','Add QuiviCraft Lookup',NULL,'/craft/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(28,NULL,'group','QuiviServe','fas fa-fw fa-hammer',NULL,5,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(29,28,'header','QuiviServe Records',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(30,29,'link','All QuiviServe',NULL,'/serve-data',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(31,28,'header','QuiviServe BEK',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(32,31,'link','All QuiviServe BEK',NULL,'/serve-bek',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(33,31,'link','Add QuiviServe BEK',NULL,'/serve-bek/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(34,28,'header','QuiviServe MPS',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(35,34,'link','All QuiviServe MPS',NULL,'/serve-mps',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(36,34,'link','Add QuiviServe MPS',NULL,'/serve-mps/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(37,28,'header','QuiviServe PCE',NULL,NULL,3,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(38,37,'link','All QuiviServe PCE',NULL,'/serve-pce',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(39,37,'link','Add QuiviServe PCE',NULL,'/serve-pce/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(40,28,'header','Lookup Tables',NULL,NULL,4,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(41,40,'link','QuiviServe Lookup',NULL,'/serve',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(42,40,'link','Add QuiviServe Lookup',NULL,'/serve/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(43,NULL,'group','QuiviCare','fas fa-fw fa-stethoscope',NULL,6,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(44,43,'header','QuiviCare Records',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(45,44,'link','All QuiviCare',NULL,'/care-data',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(46,44,'link','Add QuiviCare',NULL,'/care-data/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(47,43,'header','QuiviCare Warranties',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(48,47,'link','All QuiviCare Warranties',NULL,'/care-warranty',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(49,47,'link','Add QuiviCare Warranties',NULL,'/care-warranty/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(50,43,'header','Lookup Tables',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(51,50,'link','QuiviCare Lookup',NULL,'/care',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(52,50,'link','Add QuiviCare Lookup',NULL,'/care/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(53,NULL,'group','QuiviPlus','fas fa-fw fa-tools',NULL,7,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(54,53,'header','Service Catalog',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(55,54,'link','All Services',NULL,'/plus-services',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(56,54,'link','Add Service',NULL,'/plus-services/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(57,53,'header','Plus Orders',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(58,57,'link','All Plus Orders',NULL,'/plus-orders',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(59,57,'link','Add Plus Order',NULL,'/plus-orders/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(60,NULL,'group','QuiviThread','fas fa-fw fa-plug',NULL,8,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(61,60,'header','Bill of Materials',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(62,61,'link','All BOMs',NULL,'/thread-bom',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(63,61,'link','Add BOM',NULL,'/thread-bom/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(64,60,'header','Thread Inventory',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(65,64,'link','All Thread Inventory',NULL,'/inv-thread',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(66,64,'link','Add Thread Inventory',NULL,'/inv-thread/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(67,60,'header','Thread Orders',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(68,67,'link','All Thread Orders',NULL,'/thread-orders',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(69,67,'link','Add Thread Order',NULL,'/thread-orders/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(70,NULL,'group','QuiviMerch','fas fa-fw fa-tshirt',NULL,9,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(71,70,'header','Merch Catalog',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(72,71,'link','All Merch Items',NULL,'/merch-items',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(73,71,'link','Add Merch Item',NULL,'/merch-items/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(74,70,'header','Merch Orders',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(75,74,'link','All Merch Orders',NULL,'/merch-orders',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(76,74,'link','Add Merch Order',NULL,'/merch-orders/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(77,NULL,'group','Inventory','fas fa-fw fa-truck',NULL,10,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(78,77,'header','Master SKU <br> Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(79,78,'link','All Master SKUs',NULL,'/master-sku',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(80,78,'link','Add Master SKU',NULL,'/master-sku/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(81,77,'header','PC Parts Management',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(82,81,'link','All PC Parts',NULL,'/product',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(83,81,'link','Add PC Part',NULL,'/product/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(84,77,'header','Stock Management',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(85,84,'link','All Stock',NULL,'/product/stock',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(86,77,'header','Product Brand <br> Management',NULL,NULL,3,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(87,86,'link','All Products Brand',NULL,'/brand',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(88,86,'link','Add Product Brand',NULL,'/brand/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(89,77,'header','Category Product',NULL,NULL,4,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(90,89,'link','Code Lookup',NULL,'/category',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(91,89,'link','Add Code Lookup',NULL,'/category/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(92,77,'header','Sub Category Management',NULL,NULL,5,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(93,92,'link','Sub Code Lookup',NULL,'/sub-category',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(94,92,'link','Add Sub Code Lookup',NULL,'/sub-category/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(95,77,'header','QuiviCare Inventory',NULL,NULL,6,1,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(96,95,'link','All QuiviCare Inventory',NULL,'/inv-care',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(97,95,'link','Add QuiviCare Inventory',NULL,'/inv-care/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(98,77,'header','QS Excl. Inventory',NULL,NULL,7,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(99,98,'link','All QS Excl. Inventory',NULL,'/inv-excl-serve',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(100,98,'link','Add QS Excl. Inventory',NULL,'/inv-excl-serve/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(101,77,'header','QM Inventory',NULL,NULL,8,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(102,101,'link','All QM Inventory',NULL,'/inv-merch',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(103,101,'link','Add QM Inventory',NULL,'/inv-merch/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(104,77,'header','QM Excl. Inventory',NULL,NULL,9,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(105,104,'link','All QM Excl. Inventory',NULL,'/inv-excl-merch',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(106,104,'link','Add QM Excl. Inventory',NULL,'/inv-excl-merch/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(107,77,'header','Inventory Movement',NULL,NULL,10,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(108,107,'link','All Movements',NULL,'/inventory-movements',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(109,107,'link','Add Movement',NULL,'/inventory-movements/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(110,NULL,'group','Suppliers','fas fa-fw fa-truck-loading',NULL,11,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(111,110,'header','Supplier Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(112,111,'link','All Suppliers',NULL,'/suppliers',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(113,111,'link','Add Supplier',NULL,'/supplier/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08');
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `merch_items`
--

DROP TABLE IF EXISTS `merch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merch_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `name` varchar(191) NOT NULL,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `member_discount_price` decimal(10,2) DEFAULT NULL,
  `is_exclusive` tinyint(1) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merch_items_item_code_unique` (`item_code`),
  KEY `merch_items_sku_code_index` (`sku_code`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merch_items`
--

LOCK TABLES `merch_items` WRITE;
/*!40000 ALTER TABLE `merch_items` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `merch_items` VALUES
(1,'MI-QVMR-0001','QVSKU 0005','Quivitech White Embroidery Keychain',19.90,10.90,1,1,'2026-07-14 16:03:44','2026-07-21 08:19:00',NULL),
(2,'MI-QVMR-0002','QVSKU 0006','Quivitech Red Eagle Hook Keychain',29.90,15.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(3,'MI-QVMR-0003','QVSKU 0007','Quivitech Yellow Eagle Hook Keychain',29.90,15.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(4,'MI-QVMR-0004','QVSKU 0008','Quivitech Blue Eagle Hook Keychain',29.90,15.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(5,'MI-QVMR-0005','QVSKU 0009','Quivitech Pink Eagle Hook Keychain',29.90,15.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(6,'MI-QVMR-0006','QVSKU 0016','Quivitech 2cm x 15cm Velcro Back to Back',9.90,6.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(7,'MI-QVMR-0007','QVSKU 0017','Quivitech 1\" x 6\" Velcro OneWrap',34.90,NULL,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(8,'MI-QVMR-0008','QVSKU 0018','Quivitech Microfiber Pouch',19.90,13.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(9,'MI-QVMR-0009','QVSKU 0019','Quivitech Polymer Pouch',29.90,15.90,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(10,'MI-QVMR-0010','QVSKU 0010','Quivitech Carbon Fiber Keychain',199.90,NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(11,'MI-QVMR-0011','QVSKU 0011','Quivitech Full Grain Leather Keychain',69.90,NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(12,'MI-QVMR-0012','QVSKU 0020','Quivitech Neoprene Pouch',79.90,NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL);
/*!40000 ALTER TABLE `merch_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `merch_order_items`
--

DROP TABLE IF EXISTS `merch_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merch_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `merch_order_id` bigint(20) unsigned NOT NULL,
  `merch_item_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_applied` tinyint(1) NOT NULL DEFAULT 0,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merch_order_items_merch_order_id_foreign` (`merch_order_id`),
  KEY `merch_order_items_merch_item_id_foreign` (`merch_item_id`),
  CONSTRAINT `merch_order_items_merch_item_id_foreign` FOREIGN KEY (`merch_item_id`) REFERENCES `merch_items` (`id`),
  CONSTRAINT `merch_order_items_merch_order_id_foreign` FOREIGN KEY (`merch_order_id`) REFERENCES `merch_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merch_order_items`
--

LOCK TABLES `merch_order_items` WRITE;
/*!40000 ALTER TABLE `merch_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `merch_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `merch_orders`
--

DROP TABLE IF EXISTS `merch_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `merch_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `merch_order_id` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `purchased_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `merch_orders_merch_order_id_unique` (`merch_order_id`),
  KEY `merch_orders_customer_id_index` (`customer_id`),
  KEY `merch_orders_order_id_index` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merch_orders`
--

LOCK TABLES `merch_orders` WRITE;
/*!40000 ALTER TABLE `merch_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `merch_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `migrations` VALUES
(17,'2014_10_12_000000_create_users_table',1),
(18,'2014_10_12_100000_create_password_resets_table',1),
(19,'2019_08_19_000000_create_failed_jobs_table',1),
(20,'2021_06_01_174301_create_employees_table',1),
(21,'2021_06_02_134411_create_suppliers_table',1),
(22,'2021_06_02_153225_create_categories_table',1),
(23,'2021_06_02_174502_create_products_table',1),
(24,'2021_06_03_033045_create_expenses_table',1),
(25,'2021_06_03_052049_create_salaries_table',1),
(26,'2021_06_04_175056_create_customers_table',1),
(27,'2021_06_05_113432_create_pos_table',1),
(28,'2021_06_05_113823_create_carts_table',1),
(29,'2021_06_06_040233_create_extras_table',1),
(30,'2021_06_06_073441_create_order_table',1),
(34,'2021_06_06_073520_create_order_details_table',2),
(37,'2025_12_25_140337_update_customers_table_add_registration_fields',3),
(39,'2025_12_29_124813_create_meetings_table',4),
(41,'2025_12_30_102944_add_colour_to_categories_table',5),
(42,'2025_12_30_113510_create_serve_table',6),
(43,'2025_12_30_125215_create_care_table',7),
(44,'2026_01_03_143720_create_pc_build_requests_table',8),
(45,'2026_01_04_153225_create_craft_table',9),
(46,'2026_01_03_143720_create_meeting_details_table',10),
(47,'2026_07_11_090000_restructure_serve_pce_annual_services',11),
(48,'2026_07_11_100000_replace_documents_with_customer_progress',12),
(49,'2026_07_11_110000_rebuild_inv_move_table',13),
(50,'2026_07_12_090000_inv_move_replace_reference_with_order',14),
(51,'2026_07_02_150000_fix_inventory_tables_schema',14),
(52,'2026_07_14_090000_add_cleaning_claim_dates_to_serve_mps',15),
(53,'2026_07_14_100000_create_merch_items_table',16),
(54,'2026_07_14_100001_create_inv_merch_table',17),
(55,'2026_07_14_100002_create_inv_excl_merch_table',18),
(56,'2026_07_14_100003_create_merch_orders_table',19),
(57,'2026_07_14_100004_create_merch_order_items_table',20),
(58,'2026_07_14_110000_create_plus_services_table',21),
(59,'2026_07_14_110001_create_plus_orders_table',22),
(60,'2026_07_14_110002_create_plus_order_items_table',23),
(61,'2026_07_14_120000_create_thread_bom_headers_table',24),
(62,'2026_07_14_120001_create_thread_bom_lines_table',25),
(63,'2026_07_14_120002_create_inv_thread_table',26),
(64,'2026_07_14_120003_create_thread_orders_table',27),
(65,'2026_07_14_120004_create_thread_order_items_table',28),
(66,'2026_07_20_090000_add_skip_quivicare_to_order_table',29),
(67,'2026_07_20_130000_create_uat_meeting_table',30),
(68,'2026_07_20_150000_create_menu_items_table',31);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(191) NOT NULL,
  `invoice_id` varchar(191) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `qty` varchar(191) DEFAULT NULL,
  `sub_total` varchar(191) DEFAULT NULL,
  `vat` varchar(191) DEFAULT NULL,
  `total` varchar(191) DEFAULT NULL,
  `pay` varchar(191) DEFAULT NULL,
  `due` varchar(191) DEFAULT NULL,
  `pay_by` varchar(191) DEFAULT NULL,
  `order_date` datetime DEFAULT NULL,
  `order_month` varchar(191) DEFAULT NULL,
  `order_year` varchar(191) DEFAULT NULL,
  `craft_id` int(11) DEFAULT NULL,
  `serve_id` int(11) DEFAULT NULL,
  `care_id` int(11) DEFAULT NULL,
  `skip_quivicare` tinyint(1) NOT NULL DEFAULT 0,
  `approve` tinyint(1) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_reason` tinyint(1) NOT NULL COMMENT '1: Work, 2: Gaming',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `order` VALUES
(1,'QV-ORDR-0001','QVT-INV-2607-1',4,'8','15432',NULL,'15432',NULL,NULL,NULL,'2026-03-19 14:36:42','March','2026',2,3,3,0,1,'2026-07-09 14:21:43','2026-03-19 14:36:42','2026-07-10 18:37:41',NULL,1),
(2,'QV-ORDR-0002','QVT-INV-2603-2',19,'7','13913',NULL,'13913',NULL,NULL,NULL,'2026-03-21 10:30:29','March','2026',2,3,3,0,1,'2026-03-21 05:11:40','2026-03-21 10:30:29','2026-07-10 18:37:41',NULL,2),
(3,'QV-ORDR-0003','QVT-INV-2607-3',20,'17','23333',NULL,'23333',NULL,NULL,NULL,'2026-07-09 14:08:47','July','2026',4,3,3,0,1,'2026-07-09 14:22:43','2026-07-09 14:08:47','2026-07-10 18:37:41',NULL,2),
(4,'QV-ORDR-0004','QVT-INV-2607-4',20,'8','7435',NULL,'7435',NULL,NULL,NULL,'2026-07-09 14:17:17','July','2026',3,2,1,0,1,'2026-07-12 11:40:41','2026-07-09 14:17:17','2026-07-12 11:40:41',NULL,2),
(5,'QV-ORDR-0005','QVT-INV-2607-5',20,'9','5198',NULL,'5198',NULL,NULL,NULL,'2026-07-09 14:19:06','July','2026',1,1,1,0,1,'2026-07-12 11:56:01','2026-07-09 14:19:06','2026-07-12 11:56:01',NULL,2),
(6,'QV-ORDR-0006','QVT-INV-2607-6',21,'14','10537',NULL,'10537',NULL,NULL,NULL,'2026-07-09 15:35:33','July','2026',2,3,3,0,1,'2026-07-09 15:44:53','2026-07-09 15:35:33','2026-07-10 17:15:04',NULL,2),
(7,'QV-ORDR-0007','QVT-INV-2607-7',4,'9','22411',NULL,'22411',NULL,NULL,NULL,'2026-07-10 16:16:33','July','2026',4,3,3,0,1,'2026-07-20 11:39:47','2026-07-10 16:16:33','2026-07-20 11:39:47',NULL,1),
(8,'QV-ORDR-0008','QVT-INV-2607-8',20,'14','3500.00',NULL,'3500.00',NULL,NULL,NULL,'2026-07-12 12:21:58','July','2026',1,1,1,0,1,'2026-07-12 12:24:24','2026-07-12 12:21:58','2026-07-20 09:58:33',NULL,1),
(9,'QV-ORDR-0009','QVT-INV-2607-9',20,'14','6200.00',NULL,'6200.00',NULL,NULL,NULL,'2026-07-12 12:21:58','July','2026',1,1,1,0,1,'2026-07-12 12:24:24','2026-07-12 12:21:58','2026-07-20 09:58:33',NULL,1),
(10,'QV-ORDR-0010','QVT-INV-2607-10',20,'14','8200.00',NULL,'8200.00',NULL,NULL,NULL,'2026-07-12 12:21:58','July','2026',3,2,1,0,1,'2026-07-12 12:24:24','2026-07-12 12:21:58','2026-07-20 09:58:33',NULL,1),
(11,'QV-ORDR-0011','QVT-INV-2607-11',20,'14','9750.00',NULL,'9750.00',NULL,NULL,NULL,'2026-07-12 12:21:58','July','2026',3,2,1,0,1,'2026-07-12 12:24:24','2026-07-12 12:21:58','2026-07-20 09:58:33',NULL,1);
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `order_details`
--

DROP TABLE IF EXISTS `order_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `pro_id` int(11) DEFAULT NULL,
  `pro_qty` varchar(191) DEFAULT NULL,
  `pro_price` varchar(191) DEFAULT NULL,
  `sub_total` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `serial_no` varchar(191) DEFAULT NULL,
  `start_warranty_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_details`
--

LOCK TABLES `order_details` WRITE;
/*!40000 ALTER TABLE `order_details` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `order_details` VALUES
(1,1,1,'1','2799.00','2799.00',NULL,NULL,NULL,NULL),
(2,1,8,'1','1899.00','1899.00',NULL,NULL,NULL,NULL),
(3,1,14,'2','959.00','1918',NULL,NULL,NULL,NULL),
(4,1,25,'1','6109.00','6109.00',NULL,NULL,NULL,NULL),
(5,1,22,'1','769.00','769.00',NULL,NULL,NULL,NULL),
(6,1,12,'1','1499.00','1499.00',NULL,NULL,NULL,NULL),
(7,1,30,'1','439.00','439.00',NULL,NULL,NULL,NULL),
(8,2,5,'1','1779.00','1779.00',NULL,NULL,NULL,NULL),
(9,2,8,'1','1899.00','1899.00',NULL,NULL,NULL,NULL),
(10,2,25,'1','6109.00','6109.00',NULL,NULL,NULL,NULL),
(11,2,14,'1','959.00','959.00',NULL,NULL,NULL,NULL),
(12,2,22,'1','769.00','769.00',NULL,NULL,NULL,NULL),
(13,2,12,'1','1499.00','1499.00',NULL,NULL,NULL,NULL),
(14,2,29,'1','899.00','899.00',NULL,NULL,NULL,NULL),
(15,3,1,'1','2799.00','2799.00',NULL,NULL,NULL,NULL),
(16,3,20,'1','639.00','639.00',NULL,NULL,NULL,NULL),
(17,3,28,'1','539.00','539.00',NULL,NULL,NULL,NULL),
(18,3,34,'3','199.00','597',NULL,NULL,NULL,NULL),
(19,3,35,'6','199.00','1194',NULL,NULL,NULL,NULL),
(20,3,8,'1','1899.00','1899.00',NULL,NULL,NULL,NULL),
(21,3,18,'1','7099.00','7099.00',NULL,NULL,NULL,NULL),
(22,3,25,'1','6109.00','6109.00',NULL,NULL,NULL,NULL),
(23,3,12,'1','1499.00','1499.00',NULL,NULL,NULL,NULL),
(24,3,14,'1','959.00','959.00',NULL,NULL,NULL,NULL),
(34,4,3,'1','0','0','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(35,4,8,'1','1899','1899','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(36,4,16,'1','0','0','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(37,4,24,'1','0','0','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(38,4,12,'1','1499','1499','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(39,4,20,'1','639','639','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(40,4,15,'1','2499','2499','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(41,4,29,'1','899','899','2026-07-09 14:17:47','2026-07-09 14:17:47',NULL,NULL),
(42,5,2,'1','3699.00','3699.00',NULL,NULL,NULL,NULL),
(43,5,7,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(44,5,16,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(45,5,23,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(46,5,12,'1','1499.00','1499.00',NULL,NULL,NULL,NULL),
(47,5,21,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(48,5,13,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(49,5,26,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(50,5,39,'1','0.00','0.00',NULL,NULL,NULL,NULL),
(60,6,1,'1','2799','2799','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(61,6,7,'1','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(62,6,23,'1','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(63,6,20,'1','639','639','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(64,6,13,'1','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(65,6,38,'6','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(66,6,10,'1','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(67,6,26,'1','0','0','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(68,6,18,'1','7099','7099','2026-07-09 15:36:25','2026-07-09 15:36:25',NULL,NULL),
(69,7,2,'1','3699.00','3699.00',NULL,NULL,NULL,NULL),
(70,7,8,'1','1899.00','1899.00',NULL,NULL,NULL,NULL),
(71,7,25,'2','6109.00','12218',NULL,NULL,NULL,NULL),
(72,7,14,'2','959.00','1918',NULL,NULL,NULL,NULL),
(73,7,20,'1','639.00','639.00',NULL,NULL,NULL,NULL),
(74,7,12,'1','1499.00','1499.00',NULL,NULL,NULL,NULL),
(75,7,28,'1','539.00','539.00',NULL,NULL,NULL,NULL),
(85,8,4,'1','1039','1039','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(86,8,7,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(87,8,23,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(88,8,20,'1','639','639','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(89,8,13,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(90,8,38,'6','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(91,8,10,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(92,8,26,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(93,8,19,'1','1822','1822','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(94,9,1,'1','2799','2799','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(95,9,9,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(96,9,24,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(97,9,22,'1','769','769','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(98,9,13,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(99,9,39,'6','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(100,9,11,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(101,9,27,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(102,9,19,'1','2632','2632','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(103,10,2,'1','3699','3699','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(104,10,7,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(105,10,23,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(106,10,20,'1','639','639','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(107,10,13,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(108,10,38,'6','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(109,10,10,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(110,10,26,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(111,10,19,'1','3862','3862','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(112,11,6,'1','1779','1779','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(113,11,9,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(114,11,24,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(115,11,22,'1','769','769','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(116,11,13,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(117,11,39,'6','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(118,11,11,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(119,11,27,'1','0','0','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL),
(120,11,18,'1','7202','7202','2026-07-20 09:54:13','2026-07-20 09:54:13',NULL,NULL);
/*!40000 ALTER TABLE `order_details` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `plus_order_items`
--

DROP TABLE IF EXISTS `plus_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plus_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plus_order_id` bigint(20) unsigned NOT NULL,
  `plus_service_id` bigint(20) unsigned NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plus_order_items_plus_order_id_foreign` (`plus_order_id`),
  KEY `plus_order_items_plus_service_id_foreign` (`plus_service_id`),
  CONSTRAINT `plus_order_items_plus_order_id_foreign` FOREIGN KEY (`plus_order_id`) REFERENCES `plus_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plus_order_items_plus_service_id_foreign` FOREIGN KEY (`plus_service_id`) REFERENCES `plus_services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plus_order_items`
--

LOCK TABLES `plus_order_items` WRITE;
/*!40000 ALTER TABLE `plus_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `plus_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `plus_orders`
--

DROP TABLE IF EXISTS `plus_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plus_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plus_order_id` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plus_orders_plus_order_id_unique` (`plus_order_id`),
  KEY `plus_orders_customer_id_index` (`customer_id`),
  KEY `plus_orders_order_id_index` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plus_orders`
--

LOCK TABLES `plus_orders` WRITE;
/*!40000 ALTER TABLE `plus_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `plus_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `plus_services`
--

DROP TABLE IF EXISTS `plus_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plus_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_code` varchar(50) NOT NULL,
  `name` varchar(191) NOT NULL,
  `category` varchar(30) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plus_services_service_code_unique` (`service_code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plus_services`
--

LOCK TABLES `plus_services` WRITE;
/*!40000 ALTER TABLE `plus_services` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `plus_services` VALUES
(1,'PS-QVPL-0001','Fan Installation','installation',18.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(2,'PS-QVPL-0002','Component Upgrade Service','upgrade',45.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(3,'PS-QVPL-0003','Onsite Troubleshooting','onsite',50.00,1,'2026-07-14 16:22:09','2026-07-21 06:44:29',NULL),
(4,'PS-QVPL-0004','Cable Management','cable_mgmt',40.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(5,'PS-QVPL-0005','Entry Cleaning','cleaning',30.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(6,'PS-QVPL-0006','Deep Cleaning','cleaning',60.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(7,'PS-QVPL-0007','GPU Thermal Paste Replacement','thermal_paste',60.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(8,'PS-QVPL-0008','Deep Cleaning + GPU Thermal Paste','combo',120.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL),
(9,'PS-QVPL-0009','Distance Fee','distance_fee',20.00,1,'2026-07-14 16:22:09','2026-07-14 16:22:09',NULL);
/*!40000 ALTER TABLE `plus_services` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `pos`
--

DROP TABLE IF EXISTS `pos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pro_id` int(11) NOT NULL,
  `pro_name` varchar(191) DEFAULT NULL,
  `pro_qty` varchar(191) DEFAULT NULL,
  `pro_price` varchar(191) DEFAULT NULL,
  `sub_total` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos`
--

LOCK TABLES `pos` WRITE;
/*!40000 ALTER TABLE `pos` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `pos` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `product_raw`
--

DROP TABLE IF EXISTS `product_raw`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_raw` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(191) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `category_name` varchar(191) DEFAULT NULL,
  `sub_cat_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `product_name` varchar(191) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `buying_date` varchar(191) DEFAULT NULL,
  `image` varchar(191) NOT NULL DEFAULT '/backend/products/1767110319.png',
  `product_qty` int(11) DEFAULT NULL,
  `product_loan` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_raw`
--

LOCK TABLES `product_raw` WRITE;
/*!40000 ALTER TABLE `product_raw` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `product_raw` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `product_warranty`
--

DROP TABLE IF EXISTS `product_warranty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_warranty` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_code` varchar(191) NOT NULL,
  `product_name` varchar(191) NOT NULL,
  `serial_no` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_warranty`
--

LOCK TABLES `product_warranty` WRITE;
/*!40000 ALTER TABLE `product_warranty` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `product_warranty` VALUES
(1,25,'QV-ITM-CPU-0025','G.SKILL Trident Z5 Royal Neo RGB DDR5 CL28 6000 (32GB X 2)','QV-WRTY-000001','2026-02-25 09:04:48','2026-02-25 23:46:33',NULL),
(5,20,'QV-ITM-CPU-0020','ARCTIC Liquid Freezer III Pro ARGB 360','QV-WRTY-000002','2026-02-26 00:56:48','2026-02-26 00:56:48',NULL),
(6,25,'QV-ITM-CPU-0026','NZXT H9 Elite','QV-WRTY-000003','2026-02-26 02:32:56','2026-02-28 01:49:56',NULL),
(7,4,'QV-ITM-CPU-0004','INTEL Core Ultra 5 245KF','QV-WRTY-000004','2026-02-28 00:34:59','2026-02-28 00:34:59',NULL),
(8,11,'QV-PROD-CPU-0011','SAMSUNG 9100 Pro 1TB','QV-WRTY-000005','2026-03-15 06:02:03','2026-07-09 14:56:38',NULL);
/*!40000 ALTER TABLE `product_warranty` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_care` int(11) DEFAULT NULL,
  `product_code` varchar(191) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `category_name` varchar(191) DEFAULT NULL,
  `sub_cat_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `product_name` varchar(191) NOT NULL,
  `core` int(11) NOT NULL DEFAULT 0,
  `threads` int(11) DEFAULT NULL,
  `max_usage` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `include_fans` int(11) DEFAULT NULL,
  `frequency` int(11) DEFAULT NULL,
  `support` varchar(191) DEFAULT NULL,
  `latency` varchar(191) DEFAULT NULL,
  `additional` varchar(191) DEFAULT NULL,
  `vram` varchar(191) DEFAULT NULL,
  `80_plus` varchar(191) DEFAULT NULL,
  `atx` varchar(191) DEFAULT NULL,
  `gen` varchar(191) DEFAULT NULL,
  `pcie` varchar(191) DEFAULT NULL,
  `storage` varchar(191) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `colour` varchar(255) DEFAULT NULL,
  `back_connect` int(11) DEFAULT NULL,
  `price` varchar(191) NOT NULL DEFAULT '0.00',
  `price_updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `available` varchar(191) DEFAULT NULL,
  `available_local` varchar(191) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `buying_date` varchar(191) DEFAULT NULL,
  `image` varchar(191) NOT NULL DEFAULT '/backend/products/1767110319.png',
  `product_qty` int(11) DEFAULT NULL,
  `product_loan` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `products` VALUES
(1,1,'QV-PROD-CPU-0001',1,'CPU',2,6,'AMD Ryzen 7 9800X3D',8,16,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2799.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103563.png',39,NULL,'2026-02-11 18:18:32','2026-07-09 15:36:25',NULL),
(2,1,'QV-PROD-CPU-0002',1,'CPU',2,6,'AMD Ryzen 9 9950X3D',16,32,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3699.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103599.png',30,NULL,'2026-02-11 18:18:32','2026-02-26 03:00:01',NULL),
(3,1,'QV-PROD-CPU-0003',1,'CPU',2,6,'AMD Ryzen 7 7800X3D',8,16,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103498.png',45,NULL,'2026-02-11 18:18:32','2026-07-09 14:17:47',NULL),
(4,1,'QV-PROD-CPU-0004',1,'CPU',4,7,'INTEL Core Ultra 5 245KF',14,20,'1440P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1039.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159321.png',44,NULL,'2026-02-11 18:18:32','2026-02-26 18:28:41',NULL),
(5,1,'QV-PROD-CPU-0005',1,'CPU',4,7,'INTEL Core Ultra 7 265',20,28,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1779.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159303.png',46,NULL,'2026-02-11 18:18:32','2026-02-26 18:28:23',NULL),
(6,1,'QV-PROD-CPU-0006',1,'CPU',4,7,'INTEL Core Ultra 7 265KF',20,28,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1779.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159286.png',50,NULL,'2026-02-11 18:18:32','2026-02-26 18:28:06',NULL),
(7,1,'QV-PROD-CPU-0007',6,'MBD',11,8,'GIGABYTE Aorus X870 ELite Ice',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,0,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772158386.png',41,NULL,'2026-02-11 18:53:57','2026-07-09 15:36:25',NULL),
(8,1,'QV-PROD-CPU-0008',6,'MBD',11,8,'GIGABYTE Aorus X870 Stealth Ice',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,1,'1899.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772158365.png',21,NULL,'2026-02-11 18:53:57','2026-07-09 14:17:47',NULL),
(9,1,'QV-PROD-CPU-0009',6,'MBD',11,9,'ASUS ROG Crosshair X870e Hero',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,0,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131661.png',48,NULL,'2026-02-11 18:53:57','2026-02-26 10:47:41',NULL),
(10,1,'QV-PROD-CPU-0010',2,'SSD',56,1,'SAMSUNG 990 Pro 2TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 4',NULL,'2TB',NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131569.png',43,NULL,'2026-02-11 19:04:35','2026-07-09 15:36:25',NULL),
(11,1,'QV-PROD-CPU-0011',2,'SSD',55,1,'SAMSUNG 9100 Pro 1TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 5',NULL,'1TB',NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103110.png',46,NULL,'2026-02-11 19:04:35','2026-02-26 02:51:52',NULL),
(12,1,'QV-PROD-CPU-0012',2,'SSD',56,1,'SAMSUNG 9100 Pro 2TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 5',NULL,'2TB',NULL,NULL,NULL,'1499.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103086.png',23,NULL,'2026-02-11 19:04:35','2026-07-09 14:17:47',NULL),
(13,1,'QV-PROD-CPU-0013',7,'PSU',50,8,'GIGABYTE Aorus Elite AE850W',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Platinum','3.1',NULL,'5.1',NULL,NULL,'White',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159225.png',43,NULL,'2026-02-11 19:12:30','2026-07-09 15:36:25',NULL),
(14,1,'QV-PROD-CPU-0014',7,'PSU',51,10,'CORSAIR RM1000X Shift',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Gold','3.1',NULL,'5.1',NULL,NULL,'White',NULL,'959.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159043.png',26,NULL,'2026-02-11 19:12:30','2026-02-26 18:24:03',NULL),
(15,1,'QV-PROD-CPU-0015',7,'PSU',52,9,'ASUS ROG Thor III 1200W',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Platinum','3.1',NULL,'5.1',NULL,NULL,'Black',NULL,'2499.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159078.png',39,NULL,'2026-02-11 19:12:30','2026-07-09 14:17:47',NULL),
(16,1,'QV-PROD-CPU-0016',3,'GPU',40,9,'ASUS ROG Strix RTX 5070 Ti 16GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'8GB',NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131755.png',42,NULL,'2026-02-12 02:36:59','2026-07-09 15:36:25',NULL),
(17,1,'QV-PROD-CPU-0017',3,'GPU',42,9,'ASUS ROG Astral RTX 5090 32GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'12GB',NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'19399.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131722.png',45,NULL,'2026-02-12 02:36:59','2026-02-26 10:48:42',NULL),
(18,1,'QV-PROD-CPU-0018',3,'GPU',41,11,'MSI Trio X White RTX 5080 16GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'16GB',NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'7099.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131106.png',32,NULL,'2026-02-12 02:36:59','2026-07-09 15:36:25',NULL),
(19,1,'QV-PROD-CPU-0019',3,'GPU',38,11,'MSI Ventus 2X OC PLUS RTX 5060 Ti 16GB',0,NULL,NULL,'AMD',NULL,NULL,NULL,'',NULL,'32GB',NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'2999.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772130424.png',45,NULL,'2026-02-12 02:36:59','2026-02-26 10:27:04',NULL),
(20,1,'QV-PROD-CPU-0020',11,'AIO',46,14,'ARCTIC Liquid Freezer III Pro ARGB 360',0,NULL,NULL,NULL,NULL,NULL,NULL,'','None',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'639.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131017.png',34,NULL,'2026-02-12 02:45:27','2026-07-09 15:36:25',NULL),
(21,1,'QV-PROD-CPU-0021',11,'AIO',46,12,'NZXT Kraken Elite 360',0,NULL,NULL,NULL,NULL,NULL,NULL,'','Screen',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103657.png',44,NULL,'2026-02-12 02:45:27','2026-02-26 03:00:59',NULL),
(22,1,'QV-PROD-CPU-0022',11,'AIO',46,13,'LIAN LI Hydroshift ii LCD-C 360 Fanless',0,NULL,NULL,NULL,NULL,NULL,NULL,'','Screen',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'769.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131504.png',34,NULL,'2026-02-12 02:45:27','2026-02-26 10:45:04',NULL),
(23,1,'QV-PROD-CPU-0023',5,'RAM',28,17,'G.SKILL Trident Z5 Neo RGB DDR5 CL30 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL30',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','White',NULL,'0.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158970.png',43,NULL,'2026-02-12 02:56:11','2026-07-09 15:36:25',NULL),
(24,1,'QV-PROD-CPU-0024',5,'RAM',28,17,'G.SKILL Trident Z5 Royal Neo GOLD RGB DDR5 CL26 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL26',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','Gold',NULL,'0.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158918.png',33,NULL,'2026-02-12 02:56:11','2026-07-09 14:17:47',NULL),
(25,1,'QV-PROD-CPU-0025',5,'RAM',28,17,'G.SKILL Trident Z5 Royal Neo SILVER RGB DDR5 CL28 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL28',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','White',NULL,'6109.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158950.png',13,NULL,'2026-02-12 02:56:11','2026-02-26 18:22:30',NULL),
(26,1,'QV-PROD-CPU-0026',9,'CSE',20,12,'NZXT H9 Elite',0,NULL,NULL,NULL,1,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772130298.png',43,NULL,'2026-02-12 03:06:31','2026-07-09 15:36:25',NULL),
(27,1,'QV-PROD-CPU-0027',9,'CSE',20,12,'NZXT H9 Flow RGB',0,NULL,NULL,NULL,1,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'0.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772103870.png',48,NULL,'2026-02-12 03:06:31','2026-02-26 03:04:31',NULL),
(28,1,'QV-PROD-CPU-0028',9,'CSE',20,13,'LIAN LI O11 Vision Compact',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX, ATX, E-ATX, Back Connect',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'539.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772131463.png',31,NULL,'2026-02-12 03:06:31','2026-02-26 10:44:23',NULL),
(29,1,'QV-PROD-CPU-0029',9,'CSE',19,16,'HAVN HS 420',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'899.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772159360.png',36,NULL,'2026-02-12 03:06:31','2026-07-09 14:17:47',NULL),
(30,1,'QV-PROD-CPU-0030',9,'CSE',21,15,'JONSBO D31 Screen',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'439.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772131185.png',46,NULL,'2026-02-12 03:06:31','2026-02-26 10:39:45',NULL),
(31,0,'QV-PROD-CPU-0031',10,'FAN',22,14,'ARCTIC P12 PWM',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'None',NULL,NULL,NULL,NULL,NULL,NULL,'22','Black',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130982.png',50,NULL,'2026-02-12 03:23:12','2026-02-26 10:36:22',NULL),
(32,0,'QV-PROD-CPU-0032',10,'FAN',22,12,'NZXT F360',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB, 3x',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'598.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130370.png',41,NULL,'2026-02-12 03:23:12','2026-02-26 10:26:10',NULL),
(33,0,'QV-PROD-CPU-0033',10,'FAN',22,12,'NZXT F120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'None',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130392.png',48,NULL,'2026-02-12 03:23:12','2026-02-26 10:26:32',NULL),
(34,0,'QV-PROD-CPU-0034',10,'FAN',22,13,'LIAN LI SL INF 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'199.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130933.png',35,NULL,'2026-02-12 03:23:12','2026-07-09 14:17:47',NULL),
(35,0,'QV-PROD-CPU-0035',10,'FAN',24,13,'LIAN LI SL INF REV 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB',NULL,NULL,NULL,NULL,NULL,NULL,'24','White',NULL,'199.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159471.png',36,NULL,'2026-02-12 03:23:12','2026-02-26 18:31:11',NULL),
(36,0,'QV-PROD-CPU-0036',10,'FAN',22,13,'LIAN LI TL LCD 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'269.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130903.png',50,NULL,'2026-02-12 03:23:12','2026-02-26 10:35:03',NULL),
(37,0,'QV-PROD-CPU-0037',10,'FAN',22,13,'LIAN LI TL LCD REV 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'269.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159443.png',46,NULL,'2026-02-12 03:23:12','2026-02-26 18:30:43',NULL),
(38,0,'QV-PROD-CPU-0038',10,'FAN',23,13,'LIAN LI TL LCD 140',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'23','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130678.png',32,NULL,'2026-02-12 03:23:12','2026-07-09 15:36:25',NULL),
(39,0,'QV-PROD-CPU-0039',10,'FAN',23,13,'LIAN LI TL LCD REV 140',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'23','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159425.png',46,NULL,'2026-02-12 03:23:12','2026-02-26 18:30:25',NULL),
(40,0,'QV-PROD-CPU-0040',12,'ACC',31,9,'ASUS ROG Wingwall',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130575.png',46,NULL,'2026-02-12 03:26:48','2026-02-26 10:29:35',NULL),
(41,0,'QV-PROD-CPU-0041',12,'ACC',33,13,'LIAN LI L- Wireless Controller',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'95.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130539.png',42,NULL,'2026-02-12 03:26:48','2026-02-26 10:28:59',NULL),
(42,0,'QV-PROD-CPU-0042',12,'ACC',32,13,'LIAN LI Edge Hub',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'89.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130511.png',37,NULL,'2026-02-12 03:26:48','2026-02-26 10:28:31',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `salaries`
--

DROP TABLE IF EXISTS `salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `salaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `amount` varchar(191) DEFAULT NULL,
  `salary_date` varchar(191) DEFAULT NULL,
  `salary_month` varchar(191) DEFAULT NULL,
  `salary_year` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salaries`
--

LOCK TABLES `salaries` WRITE;
/*!40000 ALTER TABLE `salaries` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `salaries` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `serve_bek`
--

DROP TABLE IF EXISTS `serve_bek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serve_bek` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serve_bek_id` varchar(50) NOT NULL,
  `serve_data_id` varchar(255) NOT NULL,
  `date_start` date DEFAULT NULL,
  `one_year_assembly_warranty` tinyint(1) DEFAULT 1,
  `one_free_onsite_troubleshooting_first_3_months` tinyint(1) DEFAULT 1,
  `one_free_onsite_troubleshooting_claim_1` tinyint(1) DEFAULT 0,
  `one_free_onsite_troubleshooting_claim_1_date` date DEFAULT NULL,
  `one_basic_cable_management_3_months` tinyint(1) DEFAULT 1,
  `one_basic_cable_management_claim_1` tinyint(1) DEFAULT 0,
  `one_basic_cable_management_claim_1_date` date DEFAULT NULL,
  `fifty_percent_off_dust_cleaning_first_year` tinyint(1) DEFAULT 1,
  `fifty_percent_off_dust_cleaning_claim_1` tinyint(1) DEFAULT NULL,
  `fifty_percent_off_dust_cleaning_claim_1_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_serve_data` (`serve_data_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serve_bek`
--

LOCK TABLES `serve_bek` WRITE;
/*!40000 ALTER TABLE `serve_bek` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `serve_bek` VALUES
(1,'BEK-2304-0001','27','2026-06-15',1,1,1,'2026-07-05',1,1,'2026-07-10',1,0,NULL,'2026-07-12 11:56:01','2026-07-12 12:26:32',NULL),
(2,'BEK-2304-0002','28','2026-06-20',1,1,1,'2026-07-12',1,1,'2026-07-12',1,0,NULL,'2026-07-12 12:24:24','2026-07-12 14:18:33',NULL),
(3,'BEK-2304-0003','29','2026-07-01',1,1,0,NULL,1,0,NULL,1,1,'2026-07-12','2026-07-12 12:24:24','2026-07-12 14:42:41',NULL);
/*!40000 ALTER TABLE `serve_bek` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `serve_data`
--

DROP TABLE IF EXISTS `serve_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serve_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `serve_id` varchar(50) NOT NULL,
  `qvse_cid` varchar(100) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `lkp_serve_id` bigint(20) unsigned NOT NULL,
  `start_serve_enabled` tinyint(1) DEFAULT 0,
  `start_serve_date` datetime DEFAULT NULL,
  `start_serve_timestamp` bigint(20) DEFAULT NULL,
  `upgrade_pce_enabled` tinyint(1) DEFAULT 0,
  `upgrade_pce_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serve_id` (`serve_id`),
  UNIQUE KEY `qvse_cid` (`qvse_cid`),
  KEY `idx_start_serve_enabled` (`start_serve_enabled`),
  KEY `idx_upgrade_pce_enabled` (`upgrade_pce_enabled`),
  KEY `idx_start_serve_date` (`start_serve_date`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serve_data`
--

LOCK TABLES `serve_data` WRITE;
/*!40000 ALTER TABLE `serve_data` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `serve_data` VALUES
(7,'QV-SRV-0001','PCE-2610-0001',19,2,3,0,NULL,NULL,0,NULL,'2026-03-21 05:11:40','2026-07-10 12:21:49',NULL),
(8,'QV-SRV-0002','PCE-2610-0002',4,1,3,0,NULL,NULL,0,NULL,'2026-03-21 05:12:20','2026-07-10 12:21:49',NULL),
(13,'QV-SRV-0003','PCE-2610-0003',20,3,3,1,'2026-07-09 14:34:00',1783607692,1,'Current Tier: Collectorâs Edition (RM400.00)\n\nCustomer shows high engagement potential. Recommend premium package upgrade with additional features.','2026-07-09 14:21:57','2026-07-10 12:21:49',NULL),
(23,'QV-SRV-0004','PCE-2610-0004',21,6,3,1,'2026-07-09 15:47:00',1783612136,1,'Current Tier: Collectorâs Edition (RM400.00)\n\nCustomer feedback positive. Recommend adding support for additional users/teams.','2026-07-09 15:44:53','2026-07-10 12:21:49',NULL),
(24,'QV-SRV-0005','MPS-0407-0001',20,4,2,0,NULL,NULL,0,NULL,'2026-07-12 11:40:41','2026-07-12 11:40:41',NULL),
(27,'QV-SRV-0006','BEK-2304-0001',20,5,1,0,NULL,NULL,0,NULL,'2026-07-12 11:56:01','2026-07-12 11:56:01',NULL),
(28,'QV-SRV-0007','BEK-2304-0002',20,8,1,0,NULL,NULL,0,NULL,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(29,'QV-SRV-0008','BEK-2304-0003',20,9,1,0,NULL,NULL,0,NULL,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(30,'QV-SRV-0009','MPS-0407-0002',20,10,2,0,NULL,NULL,0,NULL,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(31,'QV-SRV-0010','MPS-0407-0003',20,11,2,0,NULL,NULL,0,NULL,'2026-07-12 12:24:24','2026-07-12 12:24:24',NULL),
(33,'QV-SRV-0011','PCE-2610-0005',4,7,3,0,NULL,NULL,0,NULL,'2026-07-20 11:39:47','2026-07-20 11:39:47',NULL);
/*!40000 ALTER TABLE `serve_data` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `serve_mps`
--

DROP TABLE IF EXISTS `serve_mps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serve_mps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serve_mps_id` varchar(50) NOT NULL,
  `serve_data_id` varchar(255) NOT NULL,
  `date_start` date DEFAULT NULL,
  `two_year_assembly_warranty` tinyint(1) DEFAULT 1,
  `two_free_onsite_troubleshooting_first_6_months` tinyint(1) DEFAULT 1,
  `two_free_onsite_troubleshooting_claim_1` tinyint(1) DEFAULT 0,
  `two_free_onsite_troubleshooting_claim_1_date` date DEFAULT NULL,
  `two_free_onsite_troubleshooting_claim_2` tinyint(1) DEFAULT 0,
  `two_free_onsite_troubleshooting_claim_2_date` date DEFAULT NULL,
  `two_advance_cable_management_first_year` tinyint(1) DEFAULT 1,
  `two_advance_cable_management_claim_1` tinyint(1) DEFAULT 0,
  `two_advance_cable_management_claim_1_date` date DEFAULT NULL,
  `two_advance_cable_management_claim_2` tinyint(1) DEFAULT 0,
  `two_advance_cable_management_claim_2_date` date DEFAULT NULL,
  `one_free_dust_cleaning_first_year` tinyint(1) DEFAULT 1,
  `one_free_dust_cleaning_claim` tinyint(1) DEFAULT 0,
  `one_free_dust_cleaning_claim_date` date DEFAULT NULL,
  `fifty_percent_off_dust_cleaning_second_year` tinyint(1) DEFAULT 1,
  `fifty_percent_off_dust_cleaning_claim_date` date DEFAULT NULL,
  `thirty_percent_off_labour_fees_upgrade_first_year` tinyint(1) DEFAULT 1,
  `thirty_percent_off_labour_fees_claim_date` date DEFAULT NULL,
  `thirty_percent_off_dust_cleaning` tinyint(1) DEFAULT 0,
  `thirty_percent_off_dust_cleaning_claim_date` date DEFAULT NULL,
  `rm100_promo_code_next_build` varchar(100) DEFAULT NULL,
  `generate_code` tinyint(1) DEFAULT 0,
  `rm100_promo_code_claim` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qvse_cid` (`serve_data_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serve_mps`
--

LOCK TABLES `serve_mps` WRITE;
/*!40000 ALTER TABLE `serve_mps` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `serve_mps` VALUES
(1,'MPS-0407-0001','24','2026-05-10',1,1,1,'2026-06-09',0,NULL,1,1,'2026-06-14',0,NULL,1,1,NULL,1,NULL,1,NULL,0,NULL,'MPS-6QDKP5HL',1,0,'2026-07-12 11:40:41','2026-07-12 12:26:32',NULL),
(2,'MPS-0407-0002','30','2026-07-14',1,1,1,'2026-07-05',1,'2026-09-03',1,1,'2026-07-10',1,'2026-09-08',0,0,NULL,0,NULL,0,NULL,0,NULL,'MPS-NG072RZE',1,1,'2026-07-12 12:24:24','2026-07-14 01:24:09',NULL),
(3,'MPS-0407-0003','31','2026-07-05',1,1,0,NULL,0,NULL,1,0,NULL,0,NULL,1,0,NULL,1,NULL,1,NULL,0,NULL,'MPS-NRPCT4QS',1,0,'2026-07-12 12:24:24','2026-07-12 12:26:32',NULL);
/*!40000 ALTER TABLE `serve_mps` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `serve_pce`
--

DROP TABLE IF EXISTS `serve_pce`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serve_pce` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `serve_pce_id` varchar(50) NOT NULL,
  `serve_data_id` bigint(20) unsigned DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `three_year_warranty` varchar(255) DEFAULT NULL,
  `unlimited_troubleshooting` varchar(255) DEFAULT NULL,
  `troubleshooting` varchar(255) DEFAULT NULL,
  `cable_management` varchar(50) DEFAULT NULL,
  `cable_management_claim1` tinyint(4) DEFAULT NULL,
  `cable_management_claim1_date` date DEFAULT NULL,
  `cable_management_claim2` tinyint(4) DEFAULT NULL,
  `cable_management_claim2_date` date DEFAULT NULL,
  `cable_management_claim3` tinyint(4) DEFAULT NULL,
  `cable_management_claim3_date` date DEFAULT NULL,
  `cable_management_claim4` tinyint(4) DEFAULT NULL,
  `cable_management_claim4_date` date DEFAULT NULL,
  `annual_dust_cleaning` varchar(100) DEFAULT NULL,
  `annual_dust_cleaning_year1` tinyint(4) DEFAULT NULL,
  `claim_date_year1` date DEFAULT NULL,
  `annual_dust_cleaning_year2` tinyint(4) DEFAULT NULL,
  `claim_date_year2` date DEFAULT NULL,
  `annual_dust_cleaning_year3` tinyint(4) DEFAULT NULL,
  `claim_date_year3` date DEFAULT NULL,
  `dust_cleaning_50_description` varchar(100) DEFAULT NULL,
  `dust_cleaning_50_year4` tinyint(4) DEFAULT NULL,
  `dust_cleaning_50_claim_date_year4` date DEFAULT NULL,
  `dust_cleaning_50_year5` tinyint(4) DEFAULT NULL,
  `dust_cleaning_50_claim_date_year5` date DEFAULT NULL,
  `dust_cleaning_50_year6` tinyint(4) DEFAULT NULL,
  `dust_cleaning_50_claim_date_year6` date DEFAULT NULL,
  `dust_cleaning_50_year7` tinyint(4) DEFAULT NULL,
  `dust_cleaning_50_claim_date_year7` date DEFAULT NULL,
  `upgrade_service_50_description` varchar(100) DEFAULT NULL,
  `upgrade_service_50_year1` tinyint(4) DEFAULT NULL,
  `upgrade_service_50_claim_date_year1` date DEFAULT NULL,
  `upgrade_service_50_year2` tinyint(4) DEFAULT NULL,
  `upgrade_service_50_claim_date_year2` date DEFAULT NULL,
  `upgrade_service_50_year3` tinyint(4) DEFAULT NULL,
  `upgrade_service_50_claim_date_year3` date DEFAULT NULL,
  `upgrade_service_30_description` varchar(100) DEFAULT NULL,
  `upgrade_service_30_year4` tinyint(4) DEFAULT NULL,
  `upgrade_service_30_claim_date_year4` date DEFAULT NULL,
  `upgrade_service_30_year5` tinyint(4) DEFAULT NULL,
  `upgrade_service_30_claim_date_year5` date DEFAULT NULL,
  `upgrade_service_30_year6` tinyint(4) DEFAULT NULL,
  `upgrade_service_30_claim_date_year6` date DEFAULT NULL,
  `upgrade_service_30_year7` tinyint(4) DEFAULT NULL,
  `upgrade_service_30_claim_date_year7` date DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `generate_code` tinyint(4) DEFAULT NULL,
  `promo_claim` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serve_pce`
--

LOCK TABLES `serve_pce` WRITE;
/*!40000 ALTER TABLE `serve_pce` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `serve_pce` VALUES
(1,'PCE-2610-0001',7,'2026-01-15','Yes','Yes','Yes','Premium Cable Management',1,'2026-03-15',1,'2026-07-16',1,'2027-05-01',1,NULL,'Free Annual Deep Cleaning',1,'2026-11-11',1,'2026-07-22',0,NULL,'50% Off Annual Dust Cleaning',0,NULL,0,NULL,0,NULL,0,NULL,'50% Off Annual Upgrade Service',0,NULL,0,NULL,0,NULL,'30% Off Annual Upgrade Service',0,NULL,0,NULL,0,NULL,0,NULL,NULL,0,0,'2026-07-12 15:21:08','2026-07-12 15:21:08',NULL),
(2,'PCE-2610-0002',8,'2026-02-20','Yes','Yes','Yes','Premium Cable Management',1,'2026-04-21',0,NULL,NULL,NULL,NULL,NULL,'Free Annual Deep Cleaning',1,'2026-12-17',NULL,NULL,NULL,NULL,'50% Off Annual Dust Cleaning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'50% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,'30% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PCE-BM3J9I6K',1,1,'2026-07-12 12:26:32','2026-07-12 12:26:32',NULL),
(3,'PCE-2610-0003',13,'2026-07-09','Yes','Yes','Yes','Premium Cable Management',0,NULL,0,NULL,NULL,'2027-07-15',NULL,'2028-01-19','Free Annual Deep Cleaning',0,NULL,0,NULL,0,NULL,'50% Off Annual Dust Cleaning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'50% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,'30% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PCE-805R39V2',1,0,'2026-07-12 12:26:32','2026-07-12 12:26:32',NULL),
(4,'PCE-2610-0004',23,'2026-04-10','Yes','Yes','Yes','Premium Cable Management',1,'2026-06-09',0,NULL,NULL,NULL,NULL,NULL,'Free Annual Deep Cleaning',1,'2027-02-04',NULL,NULL,NULL,NULL,'50% Off Annual Dust Cleaning',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'50% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,'30% Off Annual Upgrade Service',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'PCE-1RZV7LU0',1,1,'2026-07-12 12:26:32','2026-07-12 12:26:32',NULL),
(5,'PCE-2610-0005',33,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-20 11:39:47','2026-07-20 11:39:47',NULL);
/*!40000 ALTER TABLE `serve_pce` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `serves`
--

DROP TABLE IF EXISTS `serves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `serves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `colour` varchar(191) DEFAULT NULL,
  `fee` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `serves`
--

LOCK TABLES `serves` WRITE;
/*!40000 ALTER TABLE `serves` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `serves` VALUES
(1,'Essential Kit','BEK-2304','#BF40BF','0.00','### **Eligibility:**\n`Total Build Price < RM 7,000`\n### **Customer-Facing ID Format:**\nBEK-2304-XXXX\n### **Perks:**\n* 1-year assembly warranty\n* 1Ã onsite troubleshoot (within 90 days)\n* 1Ã basic cable refresh\n* Remote support: 3â5 working days\n* 50% off 1Ã dust cleaning (Year 1)','2025-12-30 02:27:06','2025-12-31 18:28:01',NULL),
(2,'Prime Series','MPS-0407','#FFFFFF','200.00','### **Eligibility:**\n`RM 7,000 - RM 9,999`\n### **Customer-Facing ID Format:**\nMPS-0407-XXXX\n### **Perks:**\n* 2-year assembly warranty\n* 2Ã onsite troubleshoot sessions (within 6 months)\n* 2Ã advanced cable refresh\n* 1Ã free cleaning (Year 1), 50% off next year\n* 30% off upgrade labor (Year 1)\n* RM100 promo code\n* Merch discounts','2025-12-30 02:49:27','2026-07-10 17:41:51',NULL),
(3,'Collector\'s Edition','PCE-2610','#FFD700','400.00','### **Eligibility:**\n`>= RM 10,000`\n### **Customer-Facing ID Format:**\nPCE-2610-XXXX\n### **Perks:**\n* 3 years unlimited troubleshooting\n* Next 7 years = 50% off troubleshooting\n* 4Ã premium cable refresh (first 2 years)\n* Free annual cleaning (first 3 years)\n* Premium merch discounts\n* RM200 promo code\n* Express Lab access\n* Optional upgrade:\n  **Collector + Carbon Fiber Keychain = RM469.90**\n  (only for Collector customers)','2025-12-30 02:59:49','2026-07-10 17:41:36',NULL);
/*!40000 ALTER TABLE `serves` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sub_categories`
--

DROP TABLE IF EXISTS `sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_categories`
--

LOCK TABLES `sub_categories` WRITE;
/*!40000 ALTER TABLE `sub_categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `sub_categories` VALUES
(1,1,'AM4','AM4','2025-12-30 02:27:06','2026-01-03 21:49:00',NULL),
(2,1,'AM5','AM5','2025-12-30 02:49:27','2026-01-03 21:49:08',NULL),
(3,1,'LGA 1700','LGA 1700','2025-12-30 02:59:49','2026-01-03 22:34:45',NULL),
(4,1,'LGA 1851','LGA 1851','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(5,3,'RTX 5050','RTX 5050','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(6,6,'B550','B550','2026-01-10 08:58:09','2026-01-10 09:52:55',NULL),
(7,6,'X570','X570','2026-01-10 09:40:12','2026-01-10 09:53:05',NULL),
(8,6,'B650','B650','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(9,6,'B850','B850','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(10,6,'X670','X670','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(11,6,'X870','X870','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(12,6,'Z690','Z690','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(13,6,'Z790','Z790','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(14,6,'Z890','Z890','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(15,6,'B860','B860','2026-02-11 07:41:18','2026-02-11 07:41:18',NULL),
(18,9,'SFF','SFF','2026-02-11 15:52:48','2026-02-11 15:52:48',NULL),
(19,9,'Micro Tower','Micro Tower','2026-02-11 15:52:48','2026-02-11 15:52:48',NULL),
(20,9,'Mid Tower','Mid Tower','2026-02-11 15:52:48','2026-02-11 15:52:48',NULL),
(21,9,'Full Tower','Full Tower','2026-02-11 15:52:48','2026-02-11 15:52:48',NULL),
(22,10,'120mm','120mm','2026-02-11 15:56:01','2026-02-11 15:56:01',NULL),
(23,10,'140mm','140mm','2026-02-11 15:56:01','2026-02-11 15:56:01',NULL),
(24,10,'Reverse 120mm','Reverse 120mm','2026-02-11 15:56:01','2026-02-11 15:56:01',NULL),
(25,10,'Reverse 140mm','Reverse 140mm','2026-02-11 15:56:32','2026-02-11 15:56:32',NULL),
(26,5,'2 x 16GB','2 x 16GB','2026-02-11 16:03:43','2026-02-11 16:03:43',NULL),
(27,5,'2 x 24GB','2 x 24GB','2026-02-11 16:03:43','2026-02-11 16:03:43',NULL),
(28,5,'2 x 32GB','2 x 32GB','2026-02-11 16:03:43','2026-02-11 16:03:43',NULL),
(29,5,'2 x 48GB','2 x 48GB','2026-02-11 16:03:43','2026-02-11 16:03:43',NULL),
(30,5,'2 x 64GB','2 x 64GB','2026-02-11 16:03:43','2026-02-11 16:03:43',NULL),
(31,12,'GPU Stand','GPU Stand','2026-02-11 16:05:12','2026-02-11 16:05:12',NULL),
(32,12,'HUB','HUB','2026-02-11 16:05:12','2026-02-11 16:05:12',NULL),
(33,12,'Controller','Controller','2026-02-11 16:05:12','2026-02-11 16:05:12',NULL),
(34,10,'SFF','SFF','2026-02-11 16:07:16','2026-02-11 16:07:16',NULL),
(35,10,'Single Tower','Single Tower','2026-02-11 16:07:16','2026-02-11 16:07:16',NULL),
(36,10,'Dual Tower','Dual Tower','2026-02-11 16:07:16','2026-02-11 16:07:16',NULL),
(37,3,'RTX 5060','RTX 5060','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(38,3,'RTX 5060 Ti','RTX 5060 Ti','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(39,3,'RTX 5070','RTX 5070','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(40,3,'RTX 5070 Ti','RTX 5070 Ti','2026-01-03 21:49:40','2026-01-03 21:49:40',NULL),
(41,3,'RTX 5080','RTX 5080','2026-02-11 16:11:16','2026-02-11 16:11:16',NULL),
(42,3,'RTX 5090','RTX 5090','2026-02-11 16:11:16','2026-02-11 16:11:16',NULL),
(43,3,'RX 9060 XT','RX 9060 XT','2026-02-11 16:11:16','2026-02-11 16:11:16',NULL),
(44,3,'RX 9070 XT','RX 9070 XT','2026-02-11 16:11:16','2026-02-11 16:11:16',NULL),
(45,9,'240mm','240mm','2026-02-11 16:13:52','2026-02-11 16:13:52',NULL),
(46,9,'360mm','360mm','2026-02-11 16:13:52','2026-02-11 16:13:52',NULL),
(47,9,'420mm','420mm','2026-02-11 16:13:52','2026-02-11 16:13:52',NULL),
(48,5,'650W','650W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(49,5,'750W','750W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(50,5,'850W','850W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(51,5,'1000W','1000W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(52,5,'1200W','1200W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(53,5,'1600W','1600W','2026-02-11 16:16:22','2026-02-11 16:16:22',NULL),
(54,2,'500MB','500MB','2026-02-11 16:19:47','2026-02-11 16:19:47',NULL),
(55,2,'1TB','1TB','2026-02-11 16:19:47','2026-02-11 16:19:47',NULL),
(56,2,'2TB','2TB','2026-02-11 16:19:47','2026-02-11 16:19:47',NULL),
(57,2,'4TB','4TB','2026-02-11 16:19:47','2026-02-11 16:19:47',NULL),
(58,2,'8TB','8TB','2026-02-11 16:19:47','2026-02-11 16:19:47',NULL),
(59,123,'BOX','BOX-1','2026-03-11 13:12:47','2026-03-11 13:12:47',NULL);
/*!40000 ALTER TABLE `sub_categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `shopname` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `suppliers` VALUES
(1,'QV-SUPP-0001','Magic Print','sales@imagemagic.com.my','018-2388238','Malaysia','/backend/suppliers/1767057503.png','Magic Print','2026-03-18 06:45:39','2026-03-17 22:45:39',NULL),
(2,'QV-SUPP-0002','RaffleStag','sales@rafflestag.com.my','017-8496166','Malaysia','/backend/suppliers/1767057503.png','RaffleStag','2026-03-18 06:46:27','2026-03-17 22:46:27',NULL),
(3,'QV-SUPP-0003','CamiSasca','sales@camincusa.com','949-4520195','USA','/backend/suppliers/1767057503.png','CamiSasca','2026-03-18 06:46:05','2026-03-17 22:46:05',NULL),
(4,'QV-SUPP-0004','Popov Leather','custom@popovleather.com','018-3341524','Canada','/backend/suppliers/1767057503.png','Popov Leather','2026-03-18 06:46:16','2026-03-17 22:46:16',NULL),
(5,'QV-SUPP-0005','AEIOU Studio','enquiry@uylprinter.com','016-2632273','Malaysia','/backend/suppliers/1767057503.png','AEIOU Studio','2026-03-17 15:04:12','2026-03-17 05:31:30',NULL),
(6,'QV-SUPP-0006','2S Packaging','info@2Spackaging.com','012-2223202','Malaysia','/backend/suppliers/1767057503.png','2S Packaging','2026-03-17 15:04:12','2026-03-04 15:31:31',NULL),
(7,'QV-SUPP-0007','HookandLoop','traceyt@hookandloop.com',NULL,'USA','/backend/suppliers/1767057503.png','HookandLoop','2026-03-17 15:04:12','2026-02-11 18:47:06',NULL),
(8,'QV-SUPP-0008','BoardGameGeek Store','contact@boardgamegeekstore.com',NULL,'USA','/backend/suppliers/1767057503.png','BoardGameGeek Store','2026-03-17 15:04:12','2026-02-11 18:47:06',NULL),
(9,'QV-SUPP-0009','BS Gift','contact@bsgifts.com.my','017-8798548','Malaysia','/backend/suppliers/1767057503.png','BS Gift','2026-03-18 06:45:27','2026-03-17 22:45:27',NULL),
(10,'QV-SUPP-0010','Gift Market','hello@gifting.com.sg','019-2643897','Singapore','/backend/suppliers/1767057503.png','Gift Market','2026-03-18 06:47:28','2026-03-17 22:47:28',NULL),
(11,'QV-SUPP-0011','Digikey','orders@t.digikey.com',NULL,'USA','/backend/suppliers/1767057503.png','Digikey','2026-03-17 15:04:12','2026-02-11 18:49:25',NULL),
(12,'QV-SUPP-0012','MDPC-X','contact@Cable-Sleeving.com',NULL,'Germany','/backend/suppliers/1767057503.png','MDPC-X','2026-03-17 15:04:12','2026-02-11 18:49:25',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `thread_bom_headers`
--

DROP TABLE IF EXISTS `thread_bom_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_bom_headers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `psu_brand` varchar(100) NOT NULL,
  `cable_type` varchar(30) NOT NULL,
  `colour_variant` varchar(50) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_bom_headers_psu_brand_cable_type_index` (`psu_brand`,`cable_type`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thread_bom_headers`
--

LOCK TABLES `thread_bom_headers` WRITE;
/*!40000 ALTER TABLE `thread_bom_headers` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `thread_bom_headers` VALUES
(1,'Asus','24pin',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(2,'Asus','8eps',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(3,'Asus','8pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(4,'Asus','12v2x6pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(5,'SeaSonic','24pin',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(6,'SeaSonic','8eps',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(7,'SeaSonic','8pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(8,'SeaSonic','12v2x6pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(9,'Corsair','24pin',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(10,'Corsair','8eps',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(11,'Corsair','8pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL),
(12,'Corsair','12v2x6pcie',NULL,1,'2026-07-14 16:40:08','2026-07-14 16:40:08',NULL);
/*!40000 ALTER TABLE `thread_bom_headers` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `thread_bom_lines`
--

DROP TABLE IF EXISTS `thread_bom_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_bom_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_bom_header_id` bigint(20) unsigned NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `item_name` varchar(191) NOT NULL,
  `qty_per_cable` int(11) NOT NULL DEFAULT 1,
  `unit_cost` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_bom_lines_thread_bom_header_id_foreign` (`thread_bom_header_id`),
  KEY `thread_bom_lines_sku_code_index` (`sku_code`),
  CONSTRAINT `thread_bom_lines_thread_bom_header_id_foreign` FOREIGN KEY (`thread_bom_header_id`) REFERENCES `thread_bom_headers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thread_bom_lines`
--

LOCK TABLES `thread_bom_lines` WRITE;
/*!40000 ALTER TABLE `thread_bom_lines` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `thread_bom_lines` VALUES
(1,1,'QVSKU 0029','MOLEX Blue 24 MB Pin  ATX Connector',1,5.7600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(2,1,'QVSKU 0023','MOLEX Blue 10 MB Pin  ATX Connector',1,1.8100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(3,1,'QVSKU 0027','MOLEX Blue 18 MB Pin  ATX Connector',1,3.9100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(4,1,'QVSKU 0033','MDPC-X 24 Pin Cable Comb',2,7.3100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(5,1,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',50,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(6,1,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',50,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(7,2,'QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',1,1.9758,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(8,2,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',1,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(9,2,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(10,2,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',16,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(11,2,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',16,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(12,3,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',2,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(13,3,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(14,3,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',15,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(15,3,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',15,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(16,4,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',2,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(17,4,'QVSKU 0025','MOLEX Black 12V 2x6 PCIe Pin ATX Connector',1,0.6600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(18,4,'QVSKU 0032','MDPC-X 12V 2x6 PCIe Pin Cable Comb',2,6.0900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(19,4,'QVSKU 0036','MDPC-X 17 AWG Pin Terminal',26,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(20,4,'QVSKU 0049','MDPC-X 3:1 Heatshrink Micro',26,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(21,5,'QVSKU 0029','MOLEX Blue 24 MB Pin  ATX Connector',1,5.7600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(22,5,'QVSKU 0023','MOLEX Blue 10 MB Pin  ATX Connector',1,1.8100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(23,5,'QVSKU 0027','MOLEX Blue 18 MB Pin  ATX Connector',1,3.9100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(24,5,'QVSKU 0033','MDPC-X 24 Pin Cable Comb',2,7.3100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(25,5,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',50,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(26,5,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',50,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(27,6,'QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',1,1.9758,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(28,6,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',1,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(29,6,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(30,6,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',16,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(31,6,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',16,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(32,7,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',2,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(33,7,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(34,7,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',15,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(35,7,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',15,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(36,8,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',2,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(37,8,'QVSKU 0025','MOLEX Black 12V 2x6 PCIe Pin ATX Connector',1,0.6600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(38,8,'QVSKU 0032','MDPC-X 12V 2x6 PCIe Pin Cable Comb',2,6.0900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(39,8,'QVSKU 0036','MDPC-X 17 AWG Pin Terminal',26,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(40,8,'QVSKU 0049','MDPC-X 3:1 Heatshrink Micro',26,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(41,9,'QVSKU 0029','MOLEX Blue 24 MB Pin  ATX Connector',1,5.7600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(42,9,'QVSKU 0023','MOLEX Blue 10 MB Pin  ATX Connector',1,1.8100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(43,9,'QVSKU 0027','MOLEX Blue 18 MB Pin  ATX Connector',1,3.9100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(44,9,'QVSKU 0033','MDPC-X 24 Pin Cable Comb',2,7.3100,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(45,9,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',50,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(46,10,'QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',2,1.9758,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(47,10,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(48,10,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',16,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(49,10,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',16,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(50,11,'QVSKU 0051','MOLEX Black 8 PCIe Pin ATX Connector',1,1.8800,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(51,11,'QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',1,1.9758,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(52,11,'QVSKU 0031','MDPC-X 8 Pin Cable Comb',2,4.8700,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(53,11,'QVSKU 0034','MDPC-X 4:1 Heatshrink Small',15,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(54,11,'QVSKU 0035','MDPC-X 15 AWG Pin Terminal',15,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(55,12,'QVSKU 0022','MOLEX Blue 8 EPS Pin ATX Connector',2,1.9758,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(56,12,'QVSKU 0025','MOLEX Black 12V 2x6 PCIe Pin ATX Connector',1,0.6600,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(57,12,'QVSKU 0032','MDPC-X 12V 2x6 PCIe Pin Cable Comb',2,6.0900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(58,12,'QVSKU 0036','MDPC-X 17 AWG Pin Terminal',26,0.3900,'2026-07-14 16:40:08','2026-07-14 16:40:08'),
(59,12,'QVSKU 0049','MDPC-X 3:1 Heatshrink Micro',26,0.2500,'2026-07-14 16:40:08','2026-07-14 16:40:08');
/*!40000 ALTER TABLE `thread_bom_lines` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `thread_order_items`
--

DROP TABLE IF EXISTS `thread_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_order_id` bigint(20) unsigned NOT NULL,
  `cable_type` varchar(30) NOT NULL,
  `psu_brand` varchar(100) NOT NULL,
  `colour_variant` varchar(50) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `wire_length_cm` decimal(8,2) DEFAULT NULL,
  `sleeve_length_cm` decimal(8,2) DEFAULT NULL,
  `resolved_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resolved_components`)),
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `thread_order_items_thread_order_id_foreign` (`thread_order_id`),
  CONSTRAINT `thread_order_items_thread_order_id_foreign` FOREIGN KEY (`thread_order_id`) REFERENCES `thread_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thread_order_items`
--

LOCK TABLES `thread_order_items` WRITE;
/*!40000 ALTER TABLE `thread_order_items` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `thread_order_items` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `thread_orders`
--

DROP TABLE IF EXISTS `thread_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `thread_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_order_id` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `completed_at` timestamp NULL DEFAULT NULL,
  `warranty_ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `thread_orders_thread_order_id_unique` (`thread_order_id`),
  KEY `thread_orders_customer_id_index` (`customer_id`),
  KEY `thread_orders_order_id_index` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thread_orders`
--

LOCK TABLES `thread_orders` WRITE;
/*!40000 ALTER TABLE `thread_orders` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `thread_orders` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `uat_meeting`
--

DROP TABLE IF EXISTS `uat_meeting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `uat_meeting` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `initial_budget` decimal(10,2) DEFAULT NULL,
  `reason` tinyint(4) NOT NULL COMMENT '1: Work, 2: Gaming',
  `play_mode` tinyint(4) DEFAULT NULL COMMENT '1: Multiplayer, 2: Singleplayer',
  `include_monitor` tinyint(1) DEFAULT NULL,
  `include_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `theme_style` varchar(191) DEFAULT NULL,
  `preference` varchar(191) DEFAULT NULL,
  `exemption` varchar(191) DEFAULT NULL,
  `future_proof` tinyint(1) DEFAULT 0,
  `case_size` tinyint(1) DEFAULT NULL,
  `okay_with_aio` tinyint(1) DEFAULT 0,
  `gpu_sag` tinyint(1) DEFAULT NULL,
  `need_rgb` tinyint(1) DEFAULT 0,
  `qvcrf_tag` tinyint(1) DEFAULT 0,
  `qvse` tinyint(1) DEFAULT 0,
  `qvca` tinyint(1) DEFAULT 0,
  `qvtd` tinyint(1) DEFAULT 0,
  `qvtd_notes` text DEFAULT NULL,
  `target_build_date` datetime DEFAULT NULL,
  `target_location` varchar(191) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uat_meeting_meeting_id_foreign` (`meeting_id`),
  CONSTRAINT `uat_meeting_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uat_meeting`
--

LOCK TABLES `uat_meeting` WRITE;
/*!40000 ALTER TABLE `uat_meeting` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `uat_meeting` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'admin','admin@admin.com',NULL,'$2y$10$.A0YCAMkmd7ymLb94Vzfye88awFJPBytM4D/JdXsrQs18LqKRV3c6',NULL,'2025-12-26 03:23:11','2025-12-26 03:23:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Dumping routines for database 'quivi'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-21  9:57:26
