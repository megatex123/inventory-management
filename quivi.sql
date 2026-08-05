-- Quivi DB dump
-- Generated: 2026-08-05T10:04:36.532991
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `brand`;
CREATE TABLE `brand` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `brand` WRITE;
INSERT INTO `brand` (`id`,`name`,`created_at`,`updated_at`,`deleted_at`) VALUES
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
UNLOCK TABLES;

DROP TABLE IF EXISTS `care`;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `care` WRITE;
INSERT INTO `care` (`id`,`name`,`code`,`fee`,`period`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'COR3','COR3-1402','1479.00','3 years','2025-12-30 05:16:42','2026-01-22 01:01:40',NULL),
(2,'RI5E','RI5E-2109','1499.00','5 years','2025-12-30 05:16:42','2026-01-21 06:54:59',NULL),
(3,'VIS10N','VIS10N-2712','2499.00','10 years','2025-12-30 05:16:42','2026-01-21 06:55:15',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `care_data`;
CREATE TABLE `care_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `care_id` varchar(50) NOT NULL,
  `care_data_id` varchar(191) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `care_warranty`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `categories` WRITE;
INSERT INTO `categories` (`id`,`name`,`code`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'CPU','PART-CPU','2025-12-30 02:49:27','2026-02-05 08:01:28',NULL),
(2,'SSD','PART-SSD','2026-01-03 21:49:40','2026-02-05 08:03:10',NULL),
(3,'GPU','PART-GPU','2026-01-03 21:49:40','2026-02-05 08:01:38',NULL),
(4,'HDD','PART-HDD','2026-01-03 21:49:40','2026-02-05 08:03:10',NULL),
(5,'RAM','PART-RAM','2026-01-10 08:49:34','2026-02-05 08:01:03',NULL),
(6,'MBD','PART-MBD','2026-01-10 09:52:37','2026-02-05 08:01:17',NULL),
(7,'PSU','PART-PSU','2026-02-05 08:02:47','2026-02-05 08:02:47',NULL),
(8,'HSF','PART-HSF','2026-02-05 08:03:39','2026-02-05 08:03:39',NULL),
(9,'CSE','PART-CSE','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(10,'FAN','PART-FAN','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(11,'AIO','PART-AIO','2026-02-05 08:03:39','2026-02-05 08:03:39',NULL),
(12,'ACC-SAG','PART-ACC-SAG','2026-02-05 08:03:58','2026-02-11 07:38:52',NULL),
(13,'ACC-CTL ','PART-ACC-CTL','2025-12-30 02:27:06','2026-02-28 07:02:48',NULL),
(14,'ACC-HUB','PART-ACC-HUB','2025-12-30 02:59:49','2026-02-28 07:02:56',NULL),
(15,'PER-MON','PART-PER-MON','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(16,'PER-MOU','PART-PER-MOU','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(17,'PER-HDS','PART-PER-HDS','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(18,'PER-MIC','PART-PER-MIC','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(19,'PER-MSP','PART-PER-MSP','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(20,'PER-KEY','PART-PER-KEY','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL),
(21,'PER-CAM','PART-PER-CAM','2026-02-28 07:02:21','2026-02-28 07:02:21',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `craft`;
CREATE TABLE `craft` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `code` varchar(191) NOT NULL,
  `fee` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `craft` WRITE;
INSERT INTO `craft` (`id`,`name`,`code`,`fee`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'BASIC','BASIC','600.00','2026-01-03 21:47:46','2026-03-17 23:42:03',NULL),
(2,'PREMIUM','PREMIUM','600.00','2026-01-03 21:48:07','2026-03-17 23:42:27',NULL),
(3,'MEDIUM','MEDIUM','600.00','2026-01-03 21:48:29','2026-03-17 23:42:16',NULL),
(4,'ULTRA','ULTRA','600.00','2026-03-17 23:42:46','2026-03-17 23:42:46',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `craft_inspection_items`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `craft_inspections`;
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


DROP TABLE IF EXISTS `customer_progress`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `customers`;
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
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `customers` WRITE;
INSERT INTO `customers` (`id`,`customer_id`,`full_name`,`preferred_name`,`email`,`phone`,`address`,`contact_method`,`contact_other`,`feedback`,`hear_about`,`hear_about_other`,`referred_by`,`consent`,`approve`,`approved_at`,`update_token`,`update_used`,`created_at`,`updated_at`,`deleted_at`) VALUES
(3,'QV-VIES-000001','MUHAMMAD FARIS ISKANDAR BIN SHAMSIR','BruhRis','fariskandar99@gmail.com','+60172109876','47810','WhatsApp',NULL,'Custom PC build','Friend / Referral',NULL,'Najmi Zairul',1,1,'2026-07-09 15:30:49','ac450a3c-93a4-4e70-aaac-5a46e5d11578',1,'2025-12-29 20:54:33','2026-07-09 15:30:49',NULL),
(4,'QV-VIES-000002','MUHAMMAD NAJMI NOOR ZAIRUL','Najmi','najminoorzairul@gmail.com','+60197017321','A-1-10, Cita Damansara, Jalan PJU 3/27, Sunway Damansara','WhatsApp',NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,'711774c2-478a-4c85-808e-18848a78e45a',1,'2025-12-29 21:32:12','2025-12-30 00:04:11',NULL),
(5,'QV-VIES-000003','NURSYAZWANI BINTI AHMAD NIZAM','Wani','wannieq8@gmail.com','+60197266130','A-1-10','WhatsApp',NULL,NULL,'TikTok',NULL,NULL,1,1,'2026-07-09 15:30:43','9f8be78c-9ba9-4218-9e1c-c3028a74a8a6',1,'2025-12-29 21:33:04','2026-07-09 15:30:43',NULL),
(6,'QV-VIES-000004','MUHAMMAD EIRFAN BIN NOOR ZAIRUL','Epan','eirfan019@gmail.com','+60197091129','No 2&4, Jalan Perdana 2/42, Taman Bukit Perdana 2, 83000, Batu Pahat,Johor','WhatsApp',NULL,'Nice',NULL,NULL,NULL,1,1,'2026-01-11 07:13:03','01064af6-083e-4e5e-9722-b05921e9876f',1,'2025-12-29 22:30:09','2026-01-11 07:13:03',NULL),
(7,'QV-VIES-000005','MUHAMMAD IZZHAZIQ BIN MOHD RAJIL','Izz','Izzhaziq1117@gmail.com','+601126605294','A-404, Tingkat 3, Palma Perak Apartment, Jalan Cecawi 6/6, 47810,Petaling Jaya, Selangor','WhatsApp',NULL,'Pc build','Friend / Referral',NULL,'Najmi Zairul',1,1,'2026-01-11 07:05:43','420c2946-d5da-45d3-ac54-75f521152dbc',1,'2025-12-29 21:35:06','2026-01-11 07:05:43',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `destination`;
CREATE TABLE `destination` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

LOCK TABLES `destination` WRITE;
INSERT INTO `destination` (`id`,`description`,`status`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'IE_QVSE',1,'2026-07-02 12:06:14','2026-07-02 12:06:14',NULL),
(2,'I_QVTD',1,'2026-07-02 12:07:01','2026-07-02 12:07:01',NULL),
(3,'I_QVMR',1,'2026-07-11 15:12:49','2026-07-11 15:12:49',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `documents`;
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


DROP TABLE IF EXISTS `employees`;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `details` text NOT NULL,
  `amount` varchar(191) NOT NULL,
  `expenses_date` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `extras`;
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


DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `inv_care`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `inv_merch`;
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
  `is_exclusive` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inv_merch_inv_merch_id_unique` (`inv_merch_id`),
  KEY `inv_merch_sku_code_index` (`sku_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `inv_move`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `inv_thread`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `master_sku`;
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

LOCK TABLES `master_sku` WRITE;
INSERT INTO `master_sku` (`id`,`sku_code`,`supplier_id`,`product_raw_id`,`product_name`,`from`,`cost`,`unit_type`,`lkp_status_sku`,`created_at`,`updated_at`,`deleted_at`) VALUES
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
(21,'QVSKU 0017',NULL,NULL,'Quivitech 1" x 6" Velcro OneWrap',NULL,NULL,NULL,1,'2026-07-11 15:48:17','2026-07-11 15:48:17',NULL),
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
(56,'CINV-CPU-000001',NULL,NULL,'AMD Ryzen 7 9800X3D (Spare/RMA Unit)',NULL,'2399','pcs',1,'2026-08-02 04:19:02','2026-08-02 04:19:02',NULL),
(57,'CINV-CPU-000002',NULL,NULL,'INTEL Core Ultra 7 265 (Spare/RMA Unit)',NULL,'1699','pcs',1,'2026-08-02 04:19:16','2026-08-02 04:19:16',NULL),
(58,'CINV-GPU-000002',NULL,NULL,'MSI Trio X White RTX 5080 16GB (Spare/RMA Unit)',NULL,'4999','pcs',1,'2026-08-02 04:18:32','2026-08-02 04:18:32',NULL),
(59,'CINV-GPU-000001',NULL,NULL,'ASUS ROG Strix RTX 5070 Ti 16GB (Spare/RMA Unit)',NULL,'3799','pcs',1,'2026-08-02 04:16:47','2026-08-02 04:16:47',NULL),
(60,'CINV-RAM-000001',NULL,NULL,'G.SKILL Trident Z5 32GB (Spare)',NULL,'899','pcs',1,'2026-08-02 04:14:07','2026-08-02 04:14:07',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `meeting_details`;
CREATE TABLE `meeting_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `requirement_id` varchar(255) NOT NULL,
  `initial_budget` decimal(10,2) DEFAULT 0.00,
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
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_details_meeting_id_foreign` (`meeting_id`),
  CONSTRAINT `meeting_details_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `meetings`;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `menu_items`;
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
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `menu_items` WRITE;
INSERT INTO `menu_items` (`id`,`parent_id`,`type`,`label`,`icon`,`route`,`sort_order`,`divider_before`,`is_active`,`created_at`,`updated_at`) VALUES
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
(61,60,'header','Bill of Materials',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(62,61,'link','All Bill Of Materials',NULL,'/thread-bom',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(63,61,'link','Add Bill Of Materials',NULL,'/thread-bom/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(64,77,'header','Thread Inventory',NULL,NULL,6,0,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(65,64,'link','All Thread Inventory',NULL,'/inv-thread',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(66,64,'link','Add Thread Inventory',NULL,'/inv-thread/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(67,60,'header','Thread Orders',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
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
(78,77,'header','Master SKU <br> Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(79,78,'link','All Master SKUs',NULL,'/master-sku',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(80,78,'link','Add Master SKU',NULL,'/master-sku/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(81,77,'header','PC Parts Management',NULL,NULL,1,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(82,81,'link','All PC Parts',NULL,'/product',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(83,81,'link','Add PC Part',NULL,'/product/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(84,114,'header','Stock Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(85,84,'link','All Stock',NULL,'/product/stock',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(86,114,'header','Product Brand <br> Management',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(87,86,'link','All Products Brand',NULL,'/brand',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(88,86,'link','Add Product Brand',NULL,'/brand/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(89,114,'header','Category Product',NULL,NULL,3,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(90,89,'link','Code Lookup',NULL,'/category',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(91,89,'link','Add Code Lookup',NULL,'/category/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(92,114,'header','Sub Category Management',NULL,NULL,4,0,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(93,92,'link','Sub Code Lookup',NULL,'/sub-category',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(94,92,'link','Add Sub Code Lookup',NULL,'/sub-category/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(95,77,'header','QuiviCare Inventory',NULL,NULL,2,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(96,95,'link','All QuiviCare Inventory',NULL,'/inv-care',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(97,95,'link','Add QuiviCare Inventory',NULL,'/inv-care/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(101,77,'header','QuiviMerch Inventory',NULL,NULL,4,0,1,'2026-07-20 12:56:08','2026-08-01 06:01:00'),
(102,101,'link','All QuiviMerch Inventory',NULL,'/inv-merch',0,0,1,'2026-07-20 12:56:08','2026-08-01 06:01:00'),
(103,101,'link','Add QM Inventory',NULL,'/inv-merch/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(107,77,'header','Inventory Movement',NULL,NULL,10,1,1,'2026-07-20 12:56:08','2026-07-23 17:27:23'),
(108,107,'link','All Movements',NULL,'/inventory-movements',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(109,107,'link','Add Movement',NULL,'/inventory-movements/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(110,NULL,'group','Suppliers','fas fa-fw fa-truck-loading',NULL,11,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(111,110,'header','Supplier Management',NULL,NULL,0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(112,111,'link','All Suppliers',NULL,'/suppliers',0,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(113,111,'link','Add Supplier',NULL,'/supplier/create',1,0,1,'2026-07-20 12:56:08','2026-07-20 12:56:08'),
(114,NULL,'group','Stock','fas fa-fw fa-boxes',NULL,12,0,1,'2026-07-23 17:27:23','2026-07-23 18:40:59'),
(115,NULL,'group','Refund','fas fa-fw fa-undo-alt',NULL,13,0,1,'2026-07-27 13:40:04','2026-07-27 13:40:04'),
(116,115,'header','Refund Management',NULL,NULL,0,0,1,'2026-07-27 13:40:04','2026-07-27 13:40:04'),
(117,116,'link','All Refunds',NULL,'/refunds',0,0,1,'2026-07-27 13:40:04','2026-07-27 13:40:04'),
(118,116,'link','Add Refund',NULL,'/refunds/create',1,0,1,'2026-07-27 13:40:04','2026-07-27 13:40:04');
UNLOCK TABLES;

DROP TABLE IF EXISTS `merch_items`;
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

LOCK TABLES `merch_items` WRITE;
INSERT INTO `merch_items` (`id`,`item_code`,`sku_code`,`name`,`retail_price`,`member_discount_price`,`is_exclusive`,`status`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'MI-QVMR-0001','QVSKU 0005','Quivitech White Embroidery Keychain','19.90','10.90',1,1,'2026-07-14 16:03:44','2026-07-21 08:19:00',NULL),
(2,'MI-QVMR-0002','QVSKU 0006','Quivitech Red Eagle Hook Keychain','29.90','15.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(3,'MI-QVMR-0003','QVSKU 0007','Quivitech Yellow Eagle Hook Keychain','29.90','15.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(4,'MI-QVMR-0004','QVSKU 0008','Quivitech Blue Eagle Hook Keychain','29.90','15.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(5,'MI-QVMR-0005','QVSKU 0009','Quivitech Pink Eagle Hook Keychain','29.90','15.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(6,'MI-QVMR-0006','QVSKU 0016','Quivitech 2cm x 15cm Velcro Back to Back','9.90','6.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(7,'MI-QVMR-0007','QVSKU 0017','Quivitech 1" x 6" Velcro OneWrap','34.90',NULL,0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(8,'MI-QVMR-0008','QVSKU 0018','Quivitech Microfiber Pouch','19.90','13.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(9,'MI-QVMR-0009','QVSKU 0019','Quivitech Polymer Pouch','29.90','15.90',0,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(10,'MI-QVMR-0010','QVSKU 0010','Quivitech Carbon Fiber Keychain','199.90',NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(11,'MI-QVMR-0011','QVSKU 0011','Quivitech Full Grain Leather Keychain','69.90',NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL),
(12,'MI-QVMR-0012','QVSKU 0020','Quivitech Neoprene Pouch','79.90',NULL,1,1,'2026-07-14 16:03:44','2026-07-14 16:03:44',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `merch_order_items`;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `merch_orders`;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `migrations` WRITE;
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES
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
(68,'2026_07_20_150000_create_menu_items_table',31),
(69,'2021_06_02_153225_create_sub_categories_table',1),
(70,'2025_12_30_120000_create_serve_data_table',1),
(71,'2025_12_30_120001_create_serve_bek_table',1),
(72,'2025_12_30_120002_create_serve_mps_table',1),
(73,'2025_12_30_120003_create_serve_pce_table',1),
(74,'2025_12_30_130000_create_care_data_table',1),
(75,'2025_12_30_130001_create_care_warranty_table',1),
(76,'2026_02_25_051317_create_product_warranty_table',1),
(77,'2026_06_15_000001_create_brand_table',1),
(78,'2026_06_15_000002_create_destination_table',1),
(79,'2026_06_20_000000_create_product_raw_table',1),
(80,'2026_06_20_000001_create_master_sku_table',1),
(81,'2026_06_20_000002_create_inv_care_table',1),
(82,'2026_06_20_000003_create_inv_excl_serve_table',1),
(83,'2026_06_20_000004_create_inv_move_table',1),
(84,'2026_07_03_100000_create_craft_inspections_table',1),
(85,'2026_07_07_120000_add_round_to_craft_inspections_table',1),
(86,'2026_07_08_090000_create_documents_table',1),
(87,'2026_07_11_120000_add_missing_columns_to_order_table',1),
(88,'2026_07_25_100000_create_performance_tests_table',32),
(89,'2026_07_25_100001_create_performance_test_checklist_items_table',32),
(90,'2026_07_25_110000_create_performance_test_cpu_results_table',33),
(91,'2026_07_25_110001_create_performance_test_gpu_results_table',33),
(92,'2026_07_25_110002_create_performance_test_system_stability_results_table',34),
(93,'2026_07_26_100000_create_performance_test_memory_results_table',35),
(94,'2026_07_26_100001_create_performance_test_storage_results_table',35),
(95,'2026_07_26_100002_create_performance_test_cooling_performance_results_table',35),
(96,'2026_07_26_100003_create_performance_test_cooling_system_results_table',35),
(97,'2026_07_27_100000_create_performance_test_display_results_table',36),
(98,'2026_07_27_100001_create_performance_test_network_results_table',36),
(99,'2026_07_27_100002_create_performance_test_usb_ports_table',36),
(100,'2026_07_27_100003_create_performance_test_usb_results_table',36),
(101,'2026_07_27_200000_create_onsite_handovers_table',37),
(102,'2026_07_28_100000_create_onsite_handovers_studio_table',38),
(103,'2026_07_27_300000_add_care_data_id_to_care_data_table',39),
(104,'2026_07_28_150000_create_refunds_table',40),
(105,'2026_07_28_160000_add_refunds_menu_item',41),
(106,'2026_08_01_000000_create_order_drafts_table',42),
(107,'2026_08_01_010000_merge_inv_excl_merch_into_inv_merch',43),
(108,'2026_08_02_000000_add_business_id_columns_missing_from_history',44),
(109,'2026_08_02_010000_remove_inv_excl_serve',45),
(110,'2026_08_03_000000_fix_mbd_category_code_and_accessory_cat_ids',46),
(111,'2026_08_03_010000_make_products_sub_cat_id_and_brand_id_nullable',47);
UNLOCK TABLES;

DROP TABLE IF EXISTS `onsite_handovers`;
CREATE TABLE `onsite_handovers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `round` int(10) unsigned NOT NULL DEFAULT 1,
  `report_id` varchar(191) NOT NULL,
  `report_version` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'in_progress',
  `service_date` date DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `work_start_time` time DEFAULT NULL,
  `work_completion_time` time DEFAULT NULL,
  `technician_name` varchar(191) DEFAULT NULL,
  `assistant_technician` varchar(191) DEFAULT NULL,
  `service_location` varchar(191) DEFAULT NULL,
  `service_type` varchar(191) DEFAULT NULL,
  `customer_present_during_assembly` varchar(191) DEFAULT NULL,
  `authorised_representative` varchar(191) DEFAULT NULL,
  `service_address` varchar(191) DEFAULT NULL,
  `pc_purpose` varchar(191) DEFAULT NULL,
  `operating_system` varchar(191) DEFAULT NULL,
  `operating_system_version` varchar(191) DEFAULT NULL,
  `component_serial_numbers_matched` tinyint(1) DEFAULT NULL,
  `customer_order_specification_verified` tinyint(1) DEFAULT NULL,
  `required_components_present` tinyint(1) DEFAULT NULL,
  `required_tools_present` tinyint(1) DEFAULT NULL,
  `required_consumables_present` tinyint(1) DEFAULT NULL,
  `studio_docs_notes` text DEFAULT NULL,
  `service_environment` varchar(191) DEFAULT NULL,
  `workspace_available` tinyint(1) DEFAULT NULL,
  `adequate_lighting` tinyint(1) DEFAULT NULL,
  `stable_work_surface` tinyint(1) DEFAULT NULL,
  `sufficient_working_space` tinyint(1) DEFAULT NULL,
  `power_outlet_available` tinyint(1) DEFAULT NULL,
  `internet_available` tinyint(1) DEFAULT NULL,
  `customer_present_at_arrival` tinyint(1) DEFAULT NULL,
  `assembly_area_approved_by_customer` tinyint(1) DEFAULT NULL,
  `arrival_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`arrival_photos`)),
  `arrival_notes` text DEFAULT NULL,
  `transport_case_note` varchar(191) DEFAULT NULL,
  `transport_case_status` varchar(191) DEFAULT NULL,
  `transport_case_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transport_case_photos`)),
  `component_packaging_note` varchar(191) DEFAULT NULL,
  `component_packaging_status` varchar(191) DEFAULT NULL,
  `component_packaging_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`component_packaging_photos`)),
  `security_seal_intact` tinyint(1) DEFAULT NULL,
  `no_signs_of_transit_damage` tinyint(1) DEFAULT NULL,
  `accessories_present` tinyint(1) DEFAULT NULL,
  `documentation_present` tinyint(1) DEFAULT NULL,
  `transportation_notes` text DEFAULT NULL,
  `transportation_verdict` varchar(191) DEFAULT NULL,
  `cpu_installed` tinyint(1) DEFAULT NULL,
  `memory_installed` tinyint(1) DEFAULT NULL,
  `storage_installed` tinyint(1) DEFAULT NULL,
  `cpu_cooler_installed` tinyint(1) DEFAULT NULL,
  `motherboard_installed` tinyint(1) DEFAULT NULL,
  `power_supply_installed` tinyint(1) DEFAULT NULL,
  `case_fans_installed` tinyint(1) DEFAULT NULL,
  `graphics_card_installed` tinyint(1) DEFAULT NULL,
  `cable_management_completed` tinyint(1) DEFAULT NULL,
  `assembly_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`assembly_photos`)),
  `assembly_notes` text DEFAULT NULL,
  `system_powered_on` tinyint(1) DEFAULT NULL,
  `post_successful` tinyint(1) DEFAULT NULL,
  `bios_accessible` tinyint(1) DEFAULT NULL,
  `cpu_detected` tinyint(1) DEFAULT NULL,
  `memory_detected` tinyint(1) DEFAULT NULL,
  `storage_detected` tinyint(1) DEFAULT NULL,
  `graphics_card_detected` tinyint(1) DEFAULT NULL,
  `cpu_cooler_operating` tinyint(1) DEFAULT NULL,
  `case_fans_operating` tinyint(1) DEFAULT NULL,
  `no_abnormal_noise` tinyint(1) DEFAULT NULL,
  `post_build_hardware_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`post_build_hardware_photos`)),
  `post_build_hardware_notes` text DEFAULT NULL,
  `windows_boot_successful` tinyint(1) DEFAULT NULL,
  `windows_activation_verified` tinyint(1) DEFAULT NULL,
  `display_output_verified` tinyint(1) DEFAULT NULL,
  `network_connected` tinyint(1) DEFAULT NULL,
  `internet_accessible` tinyint(1) DEFAULT NULL,
  `audio_output_verified` tinyint(1) DEFAULT NULL,
  `usb_ports_verified` tinyint(1) DEFAULT NULL,
  `rgb_lighting_verified` tinyint(1) DEFAULT NULL,
  `post_build_software_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`post_build_software_photos`)),
  `post_build_software_notes` text DEFAULT NULL,
  `physical_condition_accepted` tinyint(1) DEFAULT NULL,
  `system_boot_verified` tinyint(1) DEFAULT NULL,
  `display_verified` tinyint(1) DEFAULT NULL,
  `peripherals_verified` tinyint(1) DEFAULT NULL,
  `accessories_received` tinyint(1) DEFAULT NULL,
  `documentation_received` tinyint(1) DEFAULT NULL,
  `customer_demonstration_completed` tinyint(1) DEFAULT NULL,
  `customer_acceptance_notes` text DEFAULT NULL,
  `customer_ack_name` varchar(191) DEFAULT NULL,
  `customer_acknowledged` tinyint(1) DEFAULT NULL,
  `technician_ack_name` varchar(191) DEFAULT NULL,
  `technician_acknowledged` tinyint(1) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onsite_handovers_order_id_round_unique` (`order_id`,`round`),
  UNIQUE KEY `onsite_handovers_report_id_unique` (`report_id`),
  CONSTRAINT `onsite_handovers_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `onsite_handovers_studio`;
CREATE TABLE `onsite_handovers_studio` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `round` int(10) unsigned NOT NULL DEFAULT 1,
  `report_id` varchar(191) NOT NULL,
  `report_version` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'in_progress',
  `service_date` date DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `handover_completion_time` time DEFAULT NULL,
  `technician_name` varchar(191) DEFAULT NULL,
  `assistant_technician` varchar(191) DEFAULT NULL,
  `service_location` varchar(191) DEFAULT NULL,
  `service_type` varchar(191) DEFAULT NULL,
  `operating_system` varchar(191) DEFAULT NULL,
  `operating_system_version` varchar(191) DEFAULT NULL,
  `security_seal_verified_before_delivery` tinyint(1) DEFAULT NULL,
  `studio_docs_notes` text DEFAULT NULL,
  `workspace_available` tinyint(1) DEFAULT NULL,
  `power_outlet_available` tinyint(1) DEFAULT NULL,
  `display_available` tinyint(1) DEFAULT NULL,
  `keyboard_available` tinyint(1) DEFAULT NULL,
  `mouse_available` tinyint(1) DEFAULT NULL,
  `internet_available` tinyint(1) DEFAULT NULL,
  `arrival_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`arrival_photos`)),
  `arrival_notes` text DEFAULT NULL,
  `gpu_securely_installed` tinyint(1) DEFAULT NULL,
  `memory_fully_seated` tinyint(1) DEFAULT NULL,
  `cpu_cooler_secure` tinyint(1) DEFAULT NULL,
  `power_connections_secure` tinyint(1) DEFAULT NULL,
  `storage_secure` tinyint(1) DEFAULT NULL,
  `no_loose_cables` tinyint(1) DEFAULT NULL,
  `no_loose_screws` tinyint(1) DEFAULT NULL,
  `post_transport_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`post_transport_photos`)),
  `post_transport_notes` text DEFAULT NULL,
  `system_powered_on` tinyint(1) DEFAULT NULL,
  `post_successful` tinyint(1) DEFAULT NULL,
  `windows_boot_successful` tinyint(1) DEFAULT NULL,
  `display_output_verified` tinyint(1) DEFAULT NULL,
  `network_connected` tinyint(1) DEFAULT NULL,
  `internet_accessible` tinyint(1) DEFAULT NULL,
  `audio_verified` tinyint(1) DEFAULT NULL,
  `usb_ports_verified` tinyint(1) DEFAULT NULL,
  `rgb_lighting_verified` tinyint(1) DEFAULT NULL,
  `post_handover_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`post_handover_photos`)),
  `post_handover_notes` text DEFAULT NULL,
  `physical_condition_accepted` tinyint(1) DEFAULT NULL,
  `system_boot_verified` tinyint(1) DEFAULT NULL,
  `display_verified` tinyint(1) DEFAULT NULL,
  `accessories_received` tinyint(1) DEFAULT NULL,
  `documentation_received` tinyint(1) DEFAULT NULL,
  `customer_demonstration_completed` tinyint(1) DEFAULT NULL,
  `customer_questions_addressed` tinyint(1) DEFAULT NULL,
  `customer_acceptance_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `onsite_handovers_studio_order_id_round_unique` (`order_id`,`round`),
  UNIQUE KEY `onsite_handovers_studio_report_id_unique` (`report_id`),
  CONSTRAINT `onsite_handovers_studio_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `order`;
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
  `craft_tag_id` varchar(255) DEFAULT NULL,
  `reject_id` varchar(255) DEFAULT NULL,
  `craft_data_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `order_details`;
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
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `order_drafts`;
CREATE TABLE `order_drafts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `draft_id` varchar(50) NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `sub_total` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `craft_id` int(10) unsigned DEFAULT NULL,
  `serve_id` int(10) unsigned DEFAULT NULL,
  `care_id` int(10) unsigned DEFAULT NULL,
  `order_details_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`order_details_snapshot`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_drafts_draft_id_unique` (`draft_id`),
  KEY `order_drafts_order_id_index` (`order_id`),
  CONSTRAINT `order_drafts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_checklist_items`;
CREATE TABLE `performance_test_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `section` varchar(30) NOT NULL,
  `item_key` varchar(60) NOT NULL,
  `item_label` varchar(191) NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pass',
  `note` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_test_checklist_items_performance_test_id_foreign` (`performance_test_id`),
  CONSTRAINT `performance_test_checklist_items_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_cooling_performance_results`;
CREATE TABLE `performance_test_cooling_performance_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `cooling_solution` varchar(191) DEFAULT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `ambient_temp_c` decimal(5,2) DEFAULT NULL,
  `cpu_idle_temp_c` decimal(5,2) DEFAULT NULL,
  `cpu_load_temp_c` decimal(5,2) DEFAULT NULL,
  `gpu_idle_temp_c` decimal(5,2) DEFAULT NULL,
  `gpu_load_temp_c` decimal(5,2) DEFAULT NULL,
  `vrm_idle_temp_c` decimal(5,2) DEFAULT NULL,
  `vrm_load_temp_c` decimal(5,2) DEFAULT NULL,
  `chipset_idle_temp_c` decimal(5,2) DEFAULT NULL,
  `chipset_load_temp_c` decimal(5,2) DEFAULT NULL,
  `cpu_temp_within_range` tinyint(1) DEFAULT NULL,
  `gpu_temp_within_range` tinyint(1) DEFAULT NULL,
  `vrm_temp_within_range` tinyint(1) DEFAULT NULL,
  `chipset_temp_within_range` tinyint(1) DEFAULT NULL,
  `cooling_operating_normally` tinyint(1) DEFAULT NULL,
  `no_thermal_throttling` tinyint(1) DEFAULT NULL,
  `temps_stable_under_load` tinyint(1) DEFAULT NULL,
  `cpu_cooling_performance` tinyint(1) DEFAULT NULL,
  `gpu_cooling_performance` tinyint(1) DEFAULT NULL,
  `motherboard_cooling_performance` tinyint(1) DEFAULT NULL,
  `overall_cpu_cooling_performance` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_cooling_performance_results_pt_id_unique` (`performance_test_id`),
  CONSTRAINT `pt_cooling_performance_results_pt_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_cooling_system_results`;
CREATE TABLE `performance_test_cooling_system_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `cooling_solution` varchar(191) DEFAULT NULL,
  `fan_control_mode` varchar(191) DEFAULT NULL,
  `fan_curve` varchar(191) DEFAULT NULL,
  `cpu_fan_rpm` int(11) DEFAULT NULL,
  `cpu_pump_rpm` int(11) DEFAULT NULL,
  `front_fans_rpm` int(11) DEFAULT NULL,
  `rear_fans_rpm` int(11) DEFAULT NULL,
  `top_fans_rpm` int(11) DEFAULT NULL,
  `bottom_fans_rpm` int(11) DEFAULT NULL,
  `cpu_fan_detected` tinyint(1) DEFAULT NULL,
  `cpu_pump_detected` tinyint(1) DEFAULT NULL,
  `all_case_fans_detected` tinyint(1) DEFAULT NULL,
  `cpu_fan_rpm_stable` tinyint(1) DEFAULT NULL,
  `cpu_pump_rpm_stable` tinyint(1) DEFAULT NULL,
  `front_fan_rpm_stable` tinyint(1) DEFAULT NULL,
  `rear_fan_rpm_stable` tinyint(1) DEFAULT NULL,
  `top_fan_rpm_stable` tinyint(1) DEFAULT NULL,
  `bottom_fan_rpm_stable` tinyint(1) DEFAULT NULL,
  `all_devices_operational` tinyint(1) DEFAULT NULL,
  `no_fan_failures` tinyint(1) DEFAULT NULL,
  `stable_rpm_monitoring` tinyint(1) DEFAULT NULL,
  `front_fan_direction` varchar(191) DEFAULT NULL,
  `rear_fan_direction` varchar(191) DEFAULT NULL,
  `top_fan_direction` varchar(191) DEFAULT NULL,
  `bottom_fan_direction` varchar(191) DEFAULT NULL,
  `cpu_cooler_operation` tinyint(1) DEFAULT NULL,
  `pump_operation` tinyint(1) DEFAULT NULL,
  `chassis_fan_cooling_operation` tinyint(1) DEFAULT NULL,
  `overall_cooling_system` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_cooling_system_results_pt_id_unique` (`performance_test_id`),
  CONSTRAINT `pt_cooling_system_results_pt_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_cpu_results`;
CREATE TABLE `performance_test_cpu_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `threads_mode` varchar(191) DEFAULT NULL,
  `avg_temp_c` decimal(5,2) DEFAULT NULL,
  `max_temp_c` decimal(5,2) DEFAULT NULL,
  `avg_clock_mhz` int(11) DEFAULT NULL,
  `peak_package_power_w` decimal(6,2) DEFAULT NULL,
  `thermal_throttling` tinyint(1) DEFAULT NULL,
  `whea_errors` tinyint(1) DEFAULT NULL,
  `system_crash` tinyint(1) DEFAULT NULL,
  `no_thermal_throttling` tinyint(1) DEFAULT NULL,
  `no_whea_errors` tinyint(1) DEFAULT NULL,
  `no_application_crash` tinyint(1) DEFAULT NULL,
  `stable_clock_speed` tinyint(1) DEFAULT NULL,
  `temperature_within_range` tinyint(1) DEFAULT NULL,
  `single_core_score` int(11) DEFAULT NULL,
  `multi_core_score` int(11) DEFAULT NULL,
  `benchmark_temp_c` decimal(5,2) DEFAULT NULL,
  `benchmark_peak_power_w` decimal(6,2) DEFAULT NULL,
  `benchmark_completed` tinyint(1) DEFAULT NULL,
  `performance_within_range` tinyint(1) DEFAULT NULL,
  `no_thermal_throttling_benchmark` tinyint(1) DEFAULT NULL,
  `idle_temp_c` decimal(5,2) DEFAULT NULL,
  `load_temp_c` decimal(5,2) DEFAULT NULL,
  `ccd_temp_c` decimal(5,2) DEFAULT NULL,
  `core_voltage_v` decimal(5,3) DEFAULT NULL,
  `avg_effective_clock_mhz` int(11) DEFAULT NULL,
  `peak_package_power_benchmark_w` decimal(6,2) DEFAULT NULL,
  `stability_test_passed` tinyint(1) DEFAULT NULL,
  `benchmark_test_passed` tinyint(1) DEFAULT NULL,
  `thermal_performance_passed` tinyint(1) DEFAULT NULL,
  `clock_stability_passed` tinyint(1) DEFAULT NULL,
  `power_delivery_passed` tinyint(1) DEFAULT NULL,
  `overall_cpu_validation` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_cpu_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_cpu_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_display_results`;
CREATE TABLE `performance_test_display_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `connection_type` varchar(191) DEFAULT NULL,
  `graphic_driver_version` varchar(191) DEFAULT NULL,
  `benchmark_display` varchar(191) DEFAULT NULL,
  `display_detected` tinyint(1) DEFAULT NULL,
  `resolution` varchar(191) DEFAULT NULL,
  `refresh_rate_hz` int(10) unsigned DEFAULT NULL,
  `hdr_status` varchar(191) DEFAULT NULL,
  `output_port_tested` varchar(191) DEFAULT NULL,
  `display_detected_successfully` tinyint(1) DEFAULT NULL,
  `correct_resolution_applied` tinyint(1) DEFAULT NULL,
  `correct_refresh_rate_applied` tinyint(1) DEFAULT NULL,
  `hdr_functions_correctly` tinyint(1) DEFAULT NULL,
  `stable_video_output` tinyint(1) DEFAULT NULL,
  `display_detection` tinyint(1) DEFAULT NULL,
  `resolution_verification` tinyint(1) DEFAULT NULL,
  `refresh_rate_verification` tinyint(1) DEFAULT NULL,
  `video_output_verification` tinyint(1) DEFAULT NULL,
  `overall_display_output` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_display_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_display_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_gpu_results`;
CREATE TABLE `performance_test_gpu_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `vram_test` tinyint(1) DEFAULT NULL,
  `avg_temp_c` decimal(5,2) DEFAULT NULL,
  `max_temp_c` decimal(5,2) DEFAULT NULL,
  `max_hotspot_temp_c` decimal(5,2) DEFAULT NULL,
  `avg_clock_mhz` int(11) DEFAULT NULL,
  `peak_power_draw_w` decimal(6,2) DEFAULT NULL,
  `thermal_throttling` tinyint(1) DEFAULT NULL,
  `visual_artifacts` tinyint(1) DEFAULT NULL,
  `driver_crash` tinyint(1) DEFAULT NULL,
  `no_visual_artifacts` tinyint(1) DEFAULT NULL,
  `no_driver_crash` tinyint(1) DEFAULT NULL,
  `stable_clock_speed` tinyint(1) DEFAULT NULL,
  `temperature_within_range` tinyint(1) DEFAULT NULL,
  `gpu_score` int(11) DEFAULT NULL,
  `overall_score` int(11) DEFAULT NULL,
  `benchmark_temp_c` decimal(5,2) DEFAULT NULL,
  `benchmark_peak_power_w` decimal(6,2) DEFAULT NULL,
  `benchmark_completed` tinyint(1) DEFAULT NULL,
  `performance_within_range` tinyint(1) DEFAULT NULL,
  `no_performance_anomalies` tinyint(1) DEFAULT NULL,
  `idle_temp_c` decimal(5,2) DEFAULT NULL,
  `load_temp_c` decimal(5,2) DEFAULT NULL,
  `hotspot_temp_c` decimal(5,2) DEFAULT NULL,
  `core_clock_mhz` int(11) DEFAULT NULL,
  `memory_clock_mhz` int(11) DEFAULT NULL,
  `power_draw_w` decimal(6,2) DEFAULT NULL,
  `fan_speed_rpm` int(11) DEFAULT NULL,
  `stability_test_passed` tinyint(1) DEFAULT NULL,
  `benchmark_test_passed` tinyint(1) DEFAULT NULL,
  `thermal_performance_passed` tinyint(1) DEFAULT NULL,
  `clock_stability_passed` tinyint(1) DEFAULT NULL,
  `cooling_performance_passed` tinyint(1) DEFAULT NULL,
  `overall_gpu_validation` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_gpu_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_gpu_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_memory_results`;
CREATE TABLE `performance_test_memory_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `memory_capacity` varchar(191) DEFAULT NULL,
  `memory_configuration` varchar(191) DEFAULT NULL,
  `expo_xmp_profile` varchar(191) DEFAULT NULL,
  `memory_frequency_mts` int(11) DEFAULT NULL,
  `memory_timings` varchar(191) DEFAULT NULL,
  `memory_passes` varchar(191) DEFAULT NULL,
  `total_passes_completed` int(11) DEFAULT NULL,
  `total_tests_completed` int(11) DEFAULT NULL,
  `memory_errors_detected` int(11) DEFAULT NULL,
  `test_completed_successfully` tinyint(1) DEFAULT NULL,
  `zero_memory_errors` tinyint(1) DEFAULT NULL,
  `stable_expo_xmp_operation` tinyint(1) DEFAULT NULL,
  `capacity_expected` varchar(191) DEFAULT NULL,
  `capacity_detected` varchar(191) DEFAULT NULL,
  `capacity_status` tinyint(1) DEFAULT NULL,
  `configuration_expected` varchar(191) DEFAULT NULL,
  `configuration_detected` varchar(191) DEFAULT NULL,
  `configuration_status` tinyint(1) DEFAULT NULL,
  `frequency_expected` varchar(191) DEFAULT NULL,
  `frequency_detected` varchar(191) DEFAULT NULL,
  `frequency_status` tinyint(1) DEFAULT NULL,
  `expo_xmp_expected` varchar(191) DEFAULT NULL,
  `expo_xmp_detected` varchar(191) DEFAULT NULL,
  `expo_xmp_status` tinyint(1) DEFAULT NULL,
  `memory_stability_test` tinyint(1) DEFAULT NULL,
  `memory_frequency_verified` tinyint(1) DEFAULT NULL,
  `error_detection` tinyint(1) DEFAULT NULL,
  `overall_memory_validation` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_memory_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_memory_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_network_results`;
CREATE TABLE `performance_test_network_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `wired_network` tinyint(1) DEFAULT NULL,
  `wireless_network` varchar(191) DEFAULT NULL,
  `internet_access_available` varchar(191) DEFAULT NULL,
  `bluetooth_device_tested` varchar(191) DEFAULT NULL,
  `lan_detected` tinyint(1) DEFAULT NULL,
  `lan_connected` tinyint(1) DEFAULT NULL,
  `wifi_adapter_detected` tinyint(1) DEFAULT NULL,
  `wifi_connected` tinyint(1) DEFAULT NULL,
  `internet_access` tinyint(1) DEFAULT NULL,
  `bluetooth_adapter_detected` tinyint(1) DEFAULT NULL,
  `bluetooth_pairing_successful` tinyint(1) DEFAULT NULL,
  `lan_operating_normally` tinyint(1) DEFAULT NULL,
  `wifi_operating_normally` tinyint(1) DEFAULT NULL,
  `internet_connection_verified` tinyint(1) DEFAULT NULL,
  `bluetooth_pairing_confirmed` tinyint(1) DEFAULT NULL,
  `wifi_antenna_installed_correctly` tinyint(1) DEFAULT NULL,
  `lan_verification` tinyint(1) DEFAULT NULL,
  `wifi_verification` tinyint(1) DEFAULT NULL,
  `internet_connectivity` tinyint(1) DEFAULT NULL,
  `bluetooth_verification` tinyint(1) DEFAULT NULL,
  `overall_network_wireless` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_network_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_network_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_storage_results`;
CREATE TABLE `performance_test_storage_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `storage_device` varchar(191) DEFAULT NULL,
  `interface` varchar(191) DEFAULT NULL,
  `capacity` varchar(191) DEFAULT NULL,
  `firmware_version` varchar(191) DEFAULT NULL,
  `health_status` varchar(191) DEFAULT NULL,
  `drive_temp_c` decimal(5,2) DEFAULT NULL,
  `power_on_hours` varchar(191) DEFAULT NULL,
  `interface_mode` varchar(191) DEFAULT NULL,
  `health_status_good` tinyint(1) DEFAULT NULL,
  `drive_detected_correctly` tinyint(1) DEFAULT NULL,
  `firmware_verified` tinyint(1) DEFAULT NULL,
  `temperature_within_range` tinyint(1) DEFAULT NULL,
  `sequential_read_speed_mbs` decimal(8,2) DEFAULT NULL,
  `sequential_write_speed_mbs` decimal(8,2) DEFAULT NULL,
  `benchmark_completed` tinyint(1) DEFAULT NULL,
  `read_performance_within_range` tinyint(1) DEFAULT NULL,
  `write_performance_within_range` tinyint(1) DEFAULT NULL,
  `driver_expected` varchar(191) DEFAULT NULL,
  `driver_detected` varchar(191) DEFAULT NULL,
  `driver_status` tinyint(1) DEFAULT NULL,
  `storage_health_verification` tinyint(1) DEFAULT NULL,
  `firmware_verification` tinyint(1) DEFAULT NULL,
  `performance_verification` tinyint(1) DEFAULT NULL,
  `temperature_verification` tinyint(1) DEFAULT NULL,
  `overall_storage_validation` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_storage_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_storage_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_system_stability_results`;
CREATE TABLE `performance_test_system_stability_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `duration` varchar(191) DEFAULT NULL,
  `ambient_temp_c` decimal(5,2) DEFAULT NULL,
  `windows_power_plan` varchar(191) DEFAULT NULL,
  `max_cpu_temp_c` decimal(5,2) DEFAULT NULL,
  `max_gpu_temp_c` decimal(5,2) DEFAULT NULL,
  `cpu_package_power_w` decimal(6,2) DEFAULT NULL,
  `gpu_power_draw_w` decimal(6,2) DEFAULT NULL,
  `total_system_power_w` decimal(6,2) DEFAULT NULL,
  `cpu_clock_stability` varchar(191) DEFAULT NULL,
  `gpu_clock_stability` varchar(191) DEFAULT NULL,
  `unexpected_shutdown` tinyint(1) DEFAULT NULL,
  `bsod` tinyint(1) DEFAULT NULL,
  `application_crash` tinyint(1) DEFAULT NULL,
  `whea_errors` tinyint(1) DEFAULT NULL,
  `thermal_throttling` tinyint(1) DEFAULT NULL,
  `test_completed_successfully` tinyint(1) DEFAULT NULL,
  `no_shutdowns` tinyint(1) DEFAULT NULL,
  `no_bsod` tinyint(1) DEFAULT NULL,
  `no_whea_errors` tinyint(1) DEFAULT NULL,
  `no_thermal_throttling` tinyint(1) DEFAULT NULL,
  `stable_cpu_gpu_operation` tinyint(1) DEFAULT NULL,
  `cpu_temp_c` decimal(5,2) DEFAULT NULL,
  `gpu_temp_c` decimal(5,2) DEFAULT NULL,
  `motherboard_temp_c` decimal(5,2) DEFAULT NULL,
  `vrm_temp_c` decimal(5,2) DEFAULT NULL,
  `chipset_temp_c` decimal(5,2) DEFAULT NULL,
  `cpu_fan_speed_rpm` int(11) DEFAULT NULL,
  `pump_speed_rpm` int(11) DEFAULT NULL,
  `combined_load_stability` tinyint(1) DEFAULT NULL,
  `thermal_performance` tinyint(1) DEFAULT NULL,
  `power_delivery` tinyint(1) DEFAULT NULL,
  `cooling_performance` tinyint(1) DEFAULT NULL,
  `overall_system_stability` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pt_system_stability_results_pt_id_unique` (`performance_test_id`),
  CONSTRAINT `pt_system_stability_results_pt_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_usb_ports`;
CREATE TABLE `performance_test_usb_ports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `location` varchar(191) NOT NULL,
  `label` varchar(191) NOT NULL,
  `device_detected` tinyint(1) DEFAULT NULL,
  `data_transfer` tinyint(1) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_test_usb_ports_performance_test_id_foreign` (`performance_test_id`),
  CONSTRAINT `performance_test_usb_ports_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_test_usb_results`;
CREATE TABLE `performance_test_usb_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_test_id` bigint(20) unsigned NOT NULL,
  `test_device` varchar(191) DEFAULT NULL,
  `usb_device_capacity` varchar(191) DEFAULT NULL,
  `front_usb_ports_operational` tinyint(1) DEFAULT NULL,
  `rear_usb_ports_operational` tinyint(1) DEFAULT NULL,
  `stable_device_detection` tinyint(1) DEFAULT NULL,
  `successful_data_transfer` tinyint(1) DEFAULT NULL,
  `front_usb_verification` tinyint(1) DEFAULT NULL,
  `rear_usb_verification` tinyint(1) DEFAULT NULL,
  `data_transfer_verification` tinyint(1) DEFAULT NULL,
  `overall_usb_ports` tinyint(1) DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `performance_test_usb_results_performance_test_id_unique` (`performance_test_id`),
  CONSTRAINT `performance_test_usb_results_performance_test_id_foreign` FOREIGN KEY (`performance_test_id`) REFERENCES `performance_tests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `performance_tests`;
CREATE TABLE `performance_tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `round` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `status` varchar(191) NOT NULL DEFAULT 'draft',
  `cooling_solution` varchar(191) DEFAULT NULL,
  `overall_cpu_performance` tinyint(1) DEFAULT NULL,
  `overall_gpu_performance` tinyint(1) DEFAULT NULL,
  `overall_system_stability` tinyint(1) DEFAULT NULL,
  `overall_memory_validation` tinyint(1) DEFAULT NULL,
  `overall_storage_validation` tinyint(1) DEFAULT NULL,
  `overall_cpu_cooling_performance` tinyint(1) DEFAULT NULL,
  `overall_cooling_system` tinyint(1) DEFAULT NULL,
  `overall_display_output` tinyint(1) DEFAULT NULL,
  `overall_network_wireless` tinyint(1) DEFAULT NULL,
  `overall_usb_ports` tinyint(1) DEFAULT NULL,
  `overall_notes` text DEFAULT NULL,
  `thermal_paste_brand` varchar(191) DEFAULT NULL,
  `thermal_paste_batch` varchar(191) DEFAULT NULL,
  `thermal_paste_application_method` varchar(191) DEFAULT NULL,
  `ready_for_first_boot` tinyint(1) NOT NULL DEFAULT 0,
  `ready_for_bios_configuration` tinyint(1) NOT NULL DEFAULT 0,
  `ready_for_stability_testing` tinyint(1) NOT NULL DEFAULT 0,
  `ready_for_performance_testing` tinyint(1) NOT NULL DEFAULT 0,
  `ready_for_stress_testing` tinyint(1) NOT NULL DEFAULT 0,
  `os_installed` varchar(191) DEFAULT NULL,
  `windows_activation` tinyint(1) NOT NULL DEFAULT 0,
  `windows_update` tinyint(1) NOT NULL DEFAULT 0,
  `os_config_note` text DEFAULT NULL,
  `os_config_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`os_config_photos`)),
  `driver_chipset` tinyint(1) NOT NULL DEFAULT 0,
  `driver_wifi` tinyint(1) NOT NULL DEFAULT 0,
  `driver_gpu` tinyint(1) NOT NULL DEFAULT 0,
  `driver_bluetooth` tinyint(1) NOT NULL DEFAULT 0,
  `driver_lan` tinyint(1) NOT NULL DEFAULT 0,
  `driver_audio` tinyint(1) NOT NULL DEFAULT 0,
  `drivers_note` text DEFAULT NULL,
  `drivers_photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`drivers_photos`)),
  `applications_installed` text DEFAULT NULL,
  `applications_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_tests_order_id_foreign` (`order_id`),
  CONSTRAINT `performance_tests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `plus_order_items`;
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


DROP TABLE IF EXISTS `plus_orders`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `plus_services`;
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


DROP TABLE IF EXISTS `pos`;
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
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `product_raw`;
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


DROP TABLE IF EXISTS `product_warranty`;
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


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `is_care` int(11) DEFAULT NULL,
  `product_code` varchar(191) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `category_name` varchar(191) DEFAULT NULL,
  `sub_cat_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `products` WRITE;
INSERT INTO `products` (`id`,`is_care`,`product_code`,`cat_id`,`category_name`,`sub_cat_id`,`brand_id`,`product_name`,`core`,`threads`,`max_usage`,`type`,`include_fans`,`frequency`,`support`,`latency`,`additional`,`vram`,`80_plus`,`atx`,`gen`,`pcie`,`storage`,`size`,`colour`,`back_connect`,`price`,`price_updated_at`,`available`,`available_local`,`supplier_id`,`buying_date`,`image`,`product_qty`,`product_loan`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,1,'PART-CPU-000001',1,NULL,2,6,'AMD Ryzen 7 9800X3D',8,16,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2799.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103563.png',34,NULL,'2026-02-11 18:18:32','2026-08-03 08:13:16',NULL),
(2,1,'PART-CPU-000002',1,'CPU',2,6,'AMD Ryzen 9 9950X3D',16,32,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3699.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103599.png',26,NULL,'2026-02-11 18:18:32','2026-02-26 03:00:01',NULL),
(3,1,'PART-CPU-000003',1,'CPU',2,6,'AMD Ryzen 7 7800X3D',8,16,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103498.png',45,NULL,'2026-02-11 18:18:32','2026-07-09 14:17:47',NULL),
(4,1,'PART-CPU-000004',1,'CPU',4,7,'INTEL Core Ultra 5 245KF',14,20,'1440P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1039.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159321.png',44,NULL,'2026-02-11 18:18:32','2026-02-26 18:28:41',NULL),
(5,1,'PART-CPU-000005',1,'CPU',4,7,'INTEL Core Ultra 7 265',20,28,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1779.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159303.png',46,NULL,'2026-02-11 18:18:32','2026-02-26 18:28:23',NULL),
(6,1,'PART-CPU-000006',1,'CPU',4,7,'INTEL Core Ultra 7 265KF',20,28,'2160P',NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1779.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159286.png',41,NULL,'2026-02-11 18:18:32','2026-08-01 05:23:51',NULL),
(7,1,'PART-MBD-000001',6,'MBD',11,8,'GIGABYTE Aorus X870 ELite Ice',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,0,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772158386.png',41,NULL,'2026-02-11 18:53:57','2026-07-09 15:36:25',NULL),
(8,1,'PART-MBD-000002',6,'MBD',11,8,'GIGABYTE Aorus X870 Stealth Ice',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,1,'1899.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772158365.png',17,NULL,'2026-02-11 18:53:57','2026-07-09 14:17:47',NULL),
(9,1,'PART-MBD-000003',6,'MBD',11,9,'ASUS ROG Crosshair X870e Hero',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ATX',NULL,0,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131661.png',40,NULL,'2026-02-11 18:53:57','2026-08-01 05:23:51',NULL),
(10,1,'PART-SSD-000001',2,'SSD',56,1,'SAMSUNG 990 Pro 2TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 4',NULL,'2TB',NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131569.png',43,NULL,'2026-02-11 19:04:35','2026-07-09 15:36:25',NULL),
(11,1,'PART-MBD-000002',2,'SSD',55,1,'SAMSUNG 9100 Pro 1TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 5',NULL,'1TB',NULL,NULL,NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103110.png',39,NULL,'2026-02-11 19:04:35','2026-08-01 05:23:51',NULL),
(12,1,'PART-MBD-000003',2,'SSD',56,1,'SAMSUNG 9100 Pro 2TB',0,NULL,NULL,'NVME',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,'Gen 5',NULL,'2TB',NULL,NULL,NULL,'1499.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103086.png',19,NULL,'2026-02-11 19:04:35','2026-07-09 14:17:47',NULL),
(13,1,'PART-PSU-000001',7,'PSU',50,8,'GIGABYTE Aorus Elite AE850W',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Platinum','3.1',NULL,'5.1',NULL,NULL,'White',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159225.png',36,NULL,'2026-02-11 19:12:30','2026-08-01 05:23:51',NULL),
(14,1,'PART-PSU-000002',7,'PSU',51,10,'CORSAIR RM1000X Shift',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Gold','3.1',NULL,'5.1',NULL,NULL,'White',NULL,'959.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159043.png',26,NULL,'2026-02-11 19:12:30','2026-02-26 18:24:03',NULL),
(15,1,'PART-PSU-000003',7,'PSU',52,9,'ASUS ROG Thor III 1200W',0,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,NULL,'Platinum','3.1',NULL,'5.1',NULL,NULL,'Black',NULL,'2499.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772159078.png',35,NULL,'2026-02-11 19:12:30','2026-07-09 14:17:47',NULL),
(16,1,'PART-GPU-000001',3,'GPU',40,9,'ASUS ROG Strix RTX 5070 Ti 16GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'8GB',NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131755.png',42,NULL,'2026-02-12 02:36:59','2026-07-09 15:36:25',NULL),
(17,1,'PART-GPU-000002',3,'GPU',42,9,'ASUS ROG Astral RTX 5090 32GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'12GB',NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'19399.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131722.png',38,NULL,'2026-02-12 02:36:59','2026-08-01 05:23:51',NULL),
(18,1,'PART-GPU-000003',3,'GPU',41,11,'MSI Trio X White RTX 5080 16GB',0,NULL,NULL,'NVIDIA',NULL,NULL,NULL,'',NULL,'16GB',NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'7099.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131106.png',32,NULL,'2026-02-12 02:36:59','2026-08-01 04:54:25',NULL),
(19,1,'PART-GPU-000004',3,'GPU',38,11,'MSI Ventus 2X OC PLUS RTX 5060 Ti 16GB',0,NULL,NULL,'AMD',NULL,NULL,NULL,'',NULL,'32GB',NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'2999.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772130424.png',45,NULL,'2026-02-12 02:36:59','2026-02-26 10:27:04',NULL),
(20,1,'PART-AIO-000001',11,'AIO',46,14,'ARCTIC Liquid Freezer III Pro ARGB 360',0,NULL,NULL,NULL,NULL,NULL,NULL,'','None',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'639.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131017.png',34,NULL,'2026-02-12 02:45:27','2026-07-09 15:36:25',NULL),
(21,1,'PART-AIO-000002',11,'AIO',46,12,'NZXT Kraken Elite 360',0,NULL,NULL,NULL,NULL,NULL,NULL,'','Screen',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'0.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772103657.png',44,NULL,'2026-02-12 02:45:27','2026-02-26 03:00:59',NULL),
(22,1,'PART-AIO-000003',11,'AIO',46,13,'LIAN LI Hydroshift ii LCD-C 360 Fanless',0,NULL,NULL,NULL,NULL,NULL,NULL,'','Screen',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'769.00','2026-02-12 02:48:35',NULL,NULL,1,NULL,'/backend/products/1772131504.png',19,NULL,'2026-02-12 02:45:27','2026-08-01 05:23:51',NULL),
(23,1,'PART-RAM-000001',5,'RAM',28,17,'G.SKILL Trident Z5 Neo RGB DDR5 CL30 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL30',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','White',NULL,'0.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158970.png',43,NULL,'2026-02-12 02:56:11','2026-07-09 15:36:25',NULL),
(24,1,'PART-RAM-000002',5,'RAM',28,17,'G.SKILL Trident Z5 Royal Neo GOLD RGB DDR5 CL26 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL26',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','Gold',NULL,'0.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158918.png',23,NULL,'2026-02-12 02:56:11','2026-08-01 05:23:51',NULL),
(25,1,'PART-RAM-000003',5,'RAM',28,17,'G.SKILL Trident Z5 Royal Neo SILVER RGB DDR5 CL28 6000 (32GB X 2)',0,NULL,NULL,NULL,NULL,6000,NULL,'CL28',NULL,NULL,NULL,NULL,'DDR5',NULL,NULL,'2 x 32GB','White',NULL,'6109.00','2026-02-12 02:56:11',NULL,NULL,1,NULL,'/backend/products/1772158950.png',11,NULL,'2026-02-12 02:56:11','2026-02-26 18:22:30',NULL),
(26,1,'PART-CSE-000001',9,'CSE',20,12,'NZXT H9 Elite',0,NULL,NULL,NULL,1,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772130298.png',43,NULL,'2026-02-12 03:06:31','2026-07-09 15:36:25',NULL),
(27,1,'PART-CSE-000002',9,'CSE',20,12,'NZXT H9 Flow RGB',0,NULL,NULL,NULL,1,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'0.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772103870.png',40,NULL,'2026-02-12 03:06:31','2026-08-01 05:23:51',NULL),
(28,1,'PART-CSE-000003',9,'CSE',20,13,'LIAN LI O11 Vision Compact',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX, ATX, E-ATX, Back Connect',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'539.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772131463.png',29,NULL,'2026-02-12 03:06:31','2026-02-26 10:44:23',NULL),
(29,1,'PART-CSE-000004',9,'CSE',19,16,'HAVN HS 420',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX, ATX, E-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'899.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772159360.png',34,NULL,'2026-02-12 03:06:31','2026-07-09 14:17:47',NULL),
(30,1,'PART-CSE-000005',9,'CSE',21,15,'JONSBO D31 Screen',0,NULL,NULL,NULL,0,NULL,'ITX, M-ATX',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'439.00','2026-02-12 03:06:31',NULL,NULL,1,NULL,'/backend/products/1772131185.png',46,NULL,'2026-02-12 03:06:31','2026-02-26 10:39:45',NULL),
(31,0,'PART-FAN-000001',10,'FAN',22,14,'ARCTIC P12 PWM',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'None',NULL,NULL,NULL,NULL,NULL,NULL,'22','Black',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130982.png',50,NULL,'2026-02-12 03:23:12','2026-02-26 10:36:22',NULL),
(32,0,'PART-FAN-000002',10,'FAN',22,12,'NZXT F360',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB, 3x',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'598.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130370.png',41,NULL,'2026-02-12 03:23:12','2026-02-26 10:26:10',NULL),
(33,0,'PART-FAN-000003',10,'FAN',22,12,'NZXT F120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'None',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130392.png',48,NULL,'2026-02-12 03:23:12','2026-02-26 10:26:32',NULL),
(34,0,'PART-FAN-000004',10,'FAN',22,13,'LIAN LI SL INF 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'199.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130933.png',35,NULL,'2026-02-12 03:23:12','2026-07-09 14:17:47',NULL),
(35,0,'PART-FAN-000005',10,'FAN',24,13,'LIAN LI SL INF REV 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RGB',NULL,NULL,NULL,NULL,NULL,NULL,'24','White',NULL,'199.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159471.png',36,NULL,'2026-02-12 03:23:12','2026-02-26 18:31:11',NULL),
(36,0,'PART-FAN-000006',10,'FAN',22,13,'LIAN LI TL LCD 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'269.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130903.png',50,NULL,'2026-02-12 03:23:12','2026-02-26 10:35:03',NULL),
(37,0,'PART-FAN-000007',10,'FAN',22,13,'LIAN LI TL LCD REV 120',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'22','White',NULL,'269.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159443.png',46,NULL,'2026-02-12 03:23:12','2026-02-26 18:30:43',NULL),
(38,0,'PART-FAN-000008',10,'FAN',23,13,'LIAN LI TL LCD 140',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'23','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772130678.png',32,NULL,'2026-02-12 03:23:12','2026-07-09 15:36:25',NULL),
(39,0,'PART-FAN-000009',10,'FAN',23,13,'LIAN LI TL LCD REV 140',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Screen, RGB',NULL,NULL,NULL,NULL,NULL,NULL,'23','White',NULL,'0.00','2026-02-12 03:23:12',NULL,NULL,1,NULL,'/backend/products/1772159425.png',9,NULL,'2026-02-12 03:23:12','2026-08-01 05:23:51',NULL),
(40,0,'PART-ACC-SAG-000001',12,'ACC',31,9,'ASUS ROG Wingwall',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Black',NULL,'0.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130575.png',46,NULL,'2026-02-12 03:26:48','2026-02-26 10:29:35',NULL),
(41,0,'PART-ACC-CTL-000001',13,'ACC',33,13,'LIAN LI L- Wireless Controller',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'95.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130539.png',42,NULL,'2026-02-12 03:26:48','2026-02-26 10:28:59',NULL),
(42,0,'PART-ACC-HUB-000001',14,'ACC',32,13,'LIAN LI Edge Hub',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'White',NULL,'89.00','2026-02-12 03:26:48',NULL,NULL,1,NULL,'/backend/products/1772130511.png',37,NULL,'2026-02-12 03:26:48','2026-02-26 10:28:31',NULL),
(53,NULL,'PART-GPU-000005',3,'GPU',NULL,6,'test 1',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3000.00','2026-08-03 10:36:33',NULL,NULL,9,NULL,'/backend/products/1785754947.png',23,NULL,'2026-08-03 10:36:33','2026-08-03 11:02:27',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refund_id` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `plus_order_id` bigint(20) unsigned DEFAULT NULL,
  `merch_order_id` bigint(20) unsigned DEFAULT NULL,
  `thread_order_id` bigint(20) unsigned DEFAULT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `cash_journal` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `refunds_refund_id_unique` (`refund_id`),
  KEY `refunds_customer_id_index` (`customer_id`),
  KEY `refunds_order_id_index` (`order_id`),
  KEY `refunds_plus_order_id_index` (`plus_order_id`),
  KEY `refunds_merch_order_id_index` (`merch_order_id`),
  KEY `refunds_thread_order_id_index` (`thread_order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `salaries`;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `serve_bek`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `serve_data`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `serve_mps`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `serve_pce`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


DROP TABLE IF EXISTS `serves`;
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

LOCK TABLES `serves` WRITE;
INSERT INTO `serves` (`id`,`name`,`code`,`colour`,`fee`,`description`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'Essential Kit','BEK-2304','#BF40BF','0.00','### **Eligibility:**\n`Total Build Price < RM 7,000`\n### **Customer-Facing ID Format:**\nBEK-2304-XXXX\n### **Perks:**\n* 1-year assembly warranty\n* 1Ã onsite troubleshoot (within 90 days)\n* 1Ã basic cable refresh\n* Remote support: 3â5 working days\n* 50% off 1Ã dust cleaning (Year 1)','2025-12-30 02:27:06','2025-12-31 18:28:01',NULL),
(2,'Prime Series','MPS-0407','#FFFFFF','200.00','### **Eligibility:**\n`RM 7,000 - RM 9,999`\n### **Customer-Facing ID Format:**\nMPS-0407-XXXX\n### **Perks:**\n* 2-year assembly warranty\n* 2Ã onsite troubleshoot sessions (within 6 months)\n* 2Ã advanced cable refresh\n* 1Ã free cleaning (Year 1), 50% off next year\n* 30% off upgrade labor (Year 1)\n* RM100 promo code\n* Merch discounts','2025-12-30 02:49:27','2026-07-10 17:41:51',NULL),
(3,'Collector\'s Edition','PCE-2610','#FFD700','400.00','### **Eligibility:**\n`>= RM 10,000`\n### **Customer-Facing ID Format:**\nPCE-2610-XXXX\n### **Perks:**\n* 3 years unlimited troubleshooting\n* Next 7 years = 50% off troubleshooting\n* 4Ã premium cable refresh (first 2 years)\n* Free annual cleaning (first 3 years)\n* Premium merch discounts\n* RM200 promo code\n* Express Lab access\n* Optional upgrade:\n  **Collector + Carbon Fiber Keychain = RM469.90**\n  (only for Collector customers)','2025-12-30 02:59:49','2026-07-10 17:41:36',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `sub_categories`;
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

LOCK TABLES `sub_categories` WRITE;
INSERT INTO `sub_categories` (`id`,`cat_id`,`name`,`code`,`created_at`,`updated_at`,`deleted_at`) VALUES
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
UNLOCK TABLES;

DROP TABLE IF EXISTS `suppliers`;
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

LOCK TABLES `suppliers` WRITE;
INSERT INTO `suppliers` (`id`,`supplier_id`,`name`,`email`,`phone`,`address`,`photo`,`shopname`,`created_at`,`updated_at`,`deleted_at`) VALUES
(1,'QV-SUPP-000001','Magic Print','sales@imagemagic.com.my','018-2388238','Malaysia','/backend/suppliers/1784743462.jpeg','Magic Print','2026-07-27 08:40:49','2026-07-22 18:04:22',NULL),
(2,'QV-SUPP-000002','RaffleStag','sales@rafflestag.com.my','017-8496166','Malaysia','/backend/suppliers/1784742955.png','RaffleStag','2026-07-27 08:40:49','2026-07-22 17:55:55',NULL),
(3,'QV-SUPP-000003','CamiSasca','sales@camincusa.com','949-4520195','USA','/backend/suppliers/1784742769.jpeg','CamiSasca','2026-07-27 08:40:49','2026-07-22 17:52:49',NULL),
(4,'QV-SUPP-000004','Popov Leather','custom@popovleather.com','018-3341524','Canada','/backend/suppliers/1784743008.png','Popov Leather','2026-07-27 08:40:49','2026-07-22 17:56:48',NULL),
(5,'QV-SUPP-000005','AEIOU Studio','enquiry@uylprinter.com','016-2632273','Malaysia','/backend/suppliers/1784742223.jpeg','AEIOU Studio','2026-07-27 08:40:49','2026-07-22 17:43:43',NULL),
(6,'QV-SUPP-000006','2S Packaging','info@2Spackaging.com','012-2223202','Malaysia','/backend/suppliers/1784742147.png','2S Packaging','2026-07-27 08:40:49','2026-07-22 17:42:27',NULL),
(7,'QV-SUPP-000007','HookandLoop','traceyt@hookandloop.com','180-0940693','USA','/backend/suppliers/1784742912.png','HookandLoop','2026-07-27 08:40:49','2026-07-22 17:55:12',NULL),
(8,'QV-SUPP-000008','BoardGameGeek Store','contact@boardgamegeekstore.com','121-4321773','USA','/backend/suppliers/1784742318.png','BoardGameGeek Store','2026-07-27 08:40:49','2026-07-22 17:45:18',NULL),
(9,'QV-SUPP-000009','BS Gift','contact@bsgifts.com.my','017-8798548','Malaysia','/backend/suppliers/1784742422.jpeg','BS Gift','2026-07-27 08:40:49','2026-07-22 17:47:02',NULL),
(10,'QV-SUPP-000010','Gift Market','hello@gifting.com.sg','019-2643897','Singapore','/backend/suppliers/1784743554.jpeg','Gift Market','2026-07-27 08:40:49','2026-07-22 18:05:54',NULL),
(11,'QV-SUPP-000011','Digikey','orders@t.digikey.com','180-0344453','USA','/backend/suppliers/1784742835.png','Digikey','2026-07-27 08:40:49','2026-07-22 17:53:55',NULL),
(12,'QV-SUPP-000012','MDPC-X','contact@Cable-Sleeving.com','491-7697416','Germany','/backend/suppliers/1784743105.png','MDPC-X','2026-07-27 08:40:49','2026-07-22 17:58:25',NULL);
UNLOCK TABLES;

DROP TABLE IF EXISTS `thread_bom_headers`;
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


DROP TABLE IF EXISTS `thread_bom_lines`;
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


DROP TABLE IF EXISTS `thread_order_items`;
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


DROP TABLE IF EXISTS `thread_orders`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `uat_meeting`;
CREATE TABLE `uat_meeting` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `uat_id` varchar(255) NOT NULL DEFAULT 'NULL',
  `initial_budget` decimal(10,2) DEFAULT 0.00,
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
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uat_meeting_meeting_id_foreign` (`meeting_id`),
  CONSTRAINT `uat_meeting_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `users`;
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

LOCK TABLES `users` WRITE;
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`remember_token`,`created_at`,`updated_at`) VALUES
(1,'admin','admin@admin.com',NULL,'$2y$10$.A0YCAMkmd7ymLb94Vzfye88awFJPBytM4D/JdXsrQs18LqKRV3c6',NULL,'2025-12-26 03:23:11','2025-12-26 03:23:11');
UNLOCK TABLES;

SET FOREIGN_KEY_CHECKS=1;