-- Quivi Database Dump
-- Generated: 2026-07-14
SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `brand`;
CREATE TABLE `brand` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'SAMSUNG', '2026-01-04 14:55:58', '2026-01-04 14:55:58', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'WESTERN DIGITAL', '2026-01-04 14:55:58', '2026-01-04 14:55:58', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'ACER', '2026-01-04 14:55:58', '2026-01-04 14:55:58', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'KINGSTON', '2026-01-04 14:55:58', '2026-01-04 14:55:58', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'CRUCIAL', '2026-01-04 14:55:58', '2026-01-04 14:55:58', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'AMD', '2026-02-11 17:41:49', '2026-02-11 17:41:49', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'INTEL', '2026-02-11 17:42:10', '2026-02-11 17:42:10', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'GIGABYTE', '2026-02-11 17:42:54', '2026-02-11 17:42:54', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'ASUS ROG', '2026-02-11 17:42:54', '2026-02-11 17:42:54', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'CORSAIR', '2026-02-11 17:43:37', '2026-02-11 17:43:37', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'MSI', '2026-02-11 17:44:28', '2026-02-11 17:44:28', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'NZXT', '2026-02-11 17:44:28', '2026-02-11 17:44:28', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'LIANLI', '2026-02-11 17:44:48', '2026-02-11 17:44:48', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'ARCTIC', '2026-02-11 17:45:04', '2026-02-11 17:45:04', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 'JONSBO', '2026-02-11 17:47:57', '2026-02-11 17:47:57', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (16, 'HAVN', '2026-02-11 17:47:57', '2026-02-11 17:47:57', NULL);
INSERT INTO `brand` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES (17, 'G.SKILL', '2026-02-11 17:47:57', '2026-02-11 17:47:57', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `care` (`id`, `name`, `code`, `fee`, `period`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'COR3', 'COR3-1402', '1479.00', '3 years', '2025-12-30 05:16:42', '2026-01-22 01:01:40', NULL);
INSERT INTO `care` (`id`, `name`, `code`, `fee`, `period`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'RI5E', 'RI5E-2109', '1499.00', '5 years', '2025-12-30 05:16:42', '2026-01-21 06:54:59', NULL);
INSERT INTO `care` (`id`, `name`, `code`, `fee`, `period`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'VIS10N', 'VIS10N-2712', '2499.00', '10 years', '2025-12-30 05:16:42', '2026-01-21 06:55:15', NULL);

DROP TABLE IF EXISTS `care_data`;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'VIS10N-2712-0001', 19, 2, 3, '13913', '1489', 1, '2026-03-21 04:13:39', '2026-03-23 04:45:36', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'VIS10N-2712-0002', 4, 1, 3, '15432', '1709', 0, '2026-03-21 05:12:21', '2026-07-09 14:21:43', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'VIS10N-2712-0003', 20, 3, 3, '23333', '2479', 1, '2026-07-09 14:21:57', '2026-07-09 14:46:58', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'COR3-1402-0001', 20, 5, 1, '5198', '379', 0, '2026-07-09 14:22:08', '2026-07-12 11:56:01', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'RI5E-2109-0001', 20, 4, 2, '7435', '689', 0, '2026-07-09 14:22:11', '2026-07-12 11:40:41', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'VIS10N-2712-0004', 21, 6, 3, '10537', '1159', 1, '2026-07-09 15:44:53', '2026-07-09 15:49:19', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'COR3-1402-0002', 20, 8, 1, '3500', '379', 0, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'COR3-1402-0003', 20, 9, 1, '6200', '379', 0, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'COR3-1402-0004', 20, 10, 1, '8200', '379', 0, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `care_data` (`id`, `care_id`, `customer_id`, `order_id`, `lkp_care_id`, `total_part`, `price`, `update_membership`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'COR3-1402-0005', 20, 11, 1, '9750', '379', 0, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'QV-CLA-0001', 2, 'QVT-INV-2607-1', 1, 1, 1, 1, 'IC-0001', NULL, NULL, '2026-03-19', NULL, 0, '2026-07-12 17:56:57', '2026-07-12 18:18:49', NULL);
INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'QV-CLA-0002', 1, 'QVT-INV-2603-2', 5, 1, 1, 0, 'QVCA-0002', NULL, NULL, '2026-03-21', NULL, 0, '2026-07-12 17:56:57', '2026-07-12 17:56:57', NULL);
INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'QV-CLA-0003', 7, 'QVT-INV-2607-3', 18, 3, 1, 1, 'IC-0003', NULL, NULL, '2026-07-09', NULL, 1, '2026-07-12 17:56:57', '2026-07-12 18:18:49', NULL);
INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'QV-CLA-0004', 9, 'QVT-INV-2607-4', 16, 3, 1, 0, 'QVCA-0004', NULL, NULL, '2026-07-09', NULL, 0, '2026-07-12 17:56:57', '2026-07-12 17:56:57', NULL);
INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'QV-CLA-0005', 8, 'QVT-INV-2607-5', 2, 1, 0, 0, NULL, NULL, NULL, '2026-07-09', NULL, 0, '2026-07-12 17:56:57', '2026-07-12 17:56:57', NULL);
INSERT INTO `care_warranty` (`id`, `care_warranty_id`, `care_data_id`, `care_invoice_id`, `product_id`, `category_id`, `eligible_warranty`, `eligible_qvca`, `i_qvca_id`, `spare_item_name`, `spare_category_id`, `date_start`, `loan_date_end`, `reset_status`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'QV-CLA-0006', 10, 'QVT-INV-2607-6', 18, 3, 1, 1, 'IC-0006', 'Loaner GPU - MSI Trio X White RTX 5080 16GB', 3, '2026-07-09', '2026-07-23', 0, '2026-07-12 17:56:58', '2026-07-12 18:18:49', NULL);

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

INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'CPU', 'QV-PROD-CPU', '2025-12-30 02:49:27', '2026-02-05 08:01:28', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'SSD', 'QV-PROD-SSD', '2026-01-03 21:49:40', '2026-02-05 08:03:10', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'GPU', 'QV-PROD-GPU', '2026-01-03 21:49:40', '2026-02-05 08:01:38', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'HDD', 'QV-PROD-HDD', '2026-01-03 21:49:40', '2026-02-05 08:03:10', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'RAM', 'QV-PROD-RAM', '2026-01-10 08:49:34', '2026-02-05 08:01:03', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'MBD', 'QV-PROD-MDB', '2026-01-10 09:52:37', '2026-02-05 08:01:17', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'PSU', 'QV-PROD-PSU', '2026-02-05 08:02:47', '2026-02-05 08:02:47', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'HSF', 'QV-PROD-HSF', '2026-02-05 08:03:39', '2026-02-05 08:03:39', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'CSE', 'QV-PROD-CSE', '2026-02-05 08:03:58', '2026-02-11 07:38:52', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'FAN', 'QV-PROD-FAN', '2026-02-05 08:03:58', '2026-02-11 07:38:52', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'AIO', 'QV-PROD-AIO', '2026-02-05 08:03:39', '2026-02-05 08:03:39', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'ACC-SAG', 'QV-PROD-ACC-SAG', '2026-02-05 08:03:58', '2026-02-11 07:38:52', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'ACC-CTL ', 'QV-PROD-ACC-CTL', '2025-12-30 02:27:06', '2026-02-28 07:02:48', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'ACC-HUB', 'QV-PROD-ACC-HUB', '2025-12-30 02:59:49', '2026-02-28 07:02:56', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 'PER-MON', 'QV-PROD-PER-MON', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (16, 'PER-MOU', 'QV-PROD-PER-MOU', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (17, 'PER-HDS', 'QV-PROD-PER-HDS', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (18, 'PER-MIC', 'QV-PROD-PER-MIC', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 'PER-MSP', 'QV-PROD-PER-MSP', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 'PER-KEY', 'QV-PROD-PER-KEY', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);
INSERT INTO `categories` (`id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 'PER-CAM', 'QV-PROD-PER-CAM', '2026-02-28 07:02:21', '2026-02-28 07:02:21', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `craft` (`id`, `name`, `code`, `fee`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'BASIC', 'BASIC', '600.00', '2026-01-03 21:47:46', '2026-03-17 23:42:03', NULL);
INSERT INTO `craft` (`id`, `name`, `code`, `fee`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'PREMIUM', 'PREMIUM', '600.00', '2026-01-03 21:48:07', '2026-03-17 23:42:27', NULL);
INSERT INTO `craft` (`id`, `name`, `code`, `fee`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'MEDIUM', 'MEDIUM', '600.00', '2026-01-03 21:48:29', '2026-03-17 23:42:16', NULL);
INSERT INTO `craft` (`id`, `name`, `code`, `fee`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'ULTRA', 'ULTRA', '600.00', '2026-03-17 23:42:46', '2026-03-17 23:42:46', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 1, 'cpu', 8, '{"model":"AMD Ryzen 7 9800X3D","serial":"9MP6309Q50163","batch":"CF 2519PGEn","visual":"sound","pins":"sound"}', 1, 1, 1, 1, 'sound', NULL, '["craft-inspections\\/iAviTspUSTlaoMiI0yPT99Ap6SThWOpH33uwFYSg.jpg"]', 'intact', NULL, '["craft-inspections\\/7hX4AJNsSenXBubkCl07clivaeJ69ut35lFOLEfp.png"]', 'sound_pristine', NULL, '["craft-inspections\\/cMgeFn6GLPDVZ7WfXbLBP2XXDfD1KmvEXA193KvL.jpg"]', '2026-07-07 12:17:37', '2026-07-08 13:42:26', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 1, 'mbd', 9, '{"model":"Gigabyte X870 Aorus Stealth ICE","serial":"SN254750054000","cpu_socket":"sound","dimm_slot":"sound","pcie_slots":"sound","m2_slots":"sound","vrm_heatsinks":"sound","rear_io":"sound","cmos_batt":"sound","accessories":"sound"}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 1, 'gpu', NULL, '{"model":"GeForce RTX 5080 16G Gaming Trio OC White","serial":"602-V531-290B2507000452","connector_pin":"sound","pcie_connector":"sound","power_connector":"sound","fan_rotation":"sound","vrm_heatsinks":"sound","backplate":"sound","rgb":"sound"}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 1, 'ram', 10, '{"model":"G.Skill Trident Z5 Royal Neo","serial":"F5-6000J2836G32GX2-TR5NS","capacity":"64GB (32GB x 2)","speed":"DDR5-6000 MHz","timing":"CL28-36-36-96","voltage":"1.40V","quantity":"2 modules","heatspreader":"sound","gold_contacts":"sound"}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 1, 'ssd', 13, '{"type":"NVMe PCIe 5.0 SSD","model":"Samsung 9100 Pro","serial":"S7YFNJ0Y604707V","capacity":"2TB","connector":"M.2 2280","contact_pins":"sound","label_condition":"sound"}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 1, 'aio', 12, '{"model":"HydroShift II LCD-C 360N","serial":"H236NW250601375","radiator":"sound","pump_housing":"sound","cold_plate":"sound","tubes":"sound","fans":"fanless","accessories":"sound"}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 1, 'psu', 11, '{"model":"Corsair RM1000x Shift","serial":"A6GLA512K00M98","wattage":"1000 Watt","efficiency_rating":"80 Plus Gold","modularity":"Fully Modular","cables_inclusion":"Complete","housing":"","fan":""}', 1, 1, 1, 1, 'sound', NULL, '[]', 'intact', NULL, '[]', 'sound_pristine', NULL, '[]', '2026-07-07 12:17:37', '2026-07-07 12:17:37', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 4, 'cpu', 15, '{"model":"AMD Ryzen 7 9800X3D","serial":"test","batch":"test","visual":"test","pins":"test"}', 1, 1, 1, 1, 'sound', NULL, '["craft-inspections\\/EOtzpnKcQ81BjUZe5kzzf24CWlLgAg6tYsl4g4FK.gif"]', 'intact', NULL, '["craft-inspections\\/ZP2xsG5xBn0sV1dEb6v0awzQSG3MoBYJ4Jqo1m33.gif"]', 'sound_pristine', NULL, '["craft-inspections\\/SklxAQIyVT7ljzjzJfvummpiYxY82TwdbobLJxob.gif"]', '2026-07-09 14:25:23', '2026-07-09 14:25:23', NULL);
INSERT INTO `craft_inspection_items` (`id`, `craft_inspection_id`, `component_type`, `order_detail_id`, `fields`, `model_verified`, `serial_recorded`, `factory_seal`, `qc_pass`, `inspection_status`, `inspection_note`, `inspection_photos`, `packaging_status`, `packaging_note`, `packaging_photos`, `condition_status`, `condition_note`, `condition_photos`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 6, 'cpu', 60, '{"model":"AMD Ryzen 7 9800X3D","serial":"1324123123123","batch":"bsdw","visual":"sound","pins":"sound"}', 1, 1, 1, 1, 'sound', NULL, '["craft-inspections\\/LYSCzcG7nTAzELA07PO8UI7RV6woV2lrgT8YLRJa.gif"]', 'intact', NULL, '["craft-inspections\\/eaO2wmdjIFVnCBlh53UgKbjYN8mSTK3c6PXOCyeF.gif"]', 'issue', 'dent on the top side of box', '[]', '2026-07-09 15:46:41', '2026-07-09 15:46:41', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 2, 2, 1, 'draft', '2026-07-07 04:19:17', '2026-07-07 04:19:17', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 2, 2, 2, 'draft', '2026-07-07 12:44:35', '2026-07-07 12:44:35', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 1, 2, 1, 'draft', '2026-07-07 15:00:18', '2026-07-07 15:00:18', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 3, 2, 1, 'completed', '2026-07-09 14:23:47', '2026-07-09 14:27:13', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 3, 2, 2, 'completed', '2026-07-09 14:27:33', '2026-07-09 14:29:39', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 6, 2, 1, 'completed', '2026-07-09 15:45:14', '2026-07-09 15:47:05', NULL);
INSERT INTO `craft_inspections` (`id`, `order_id`, `phase`, `round`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 6, 2, 2, 'draft', '2026-07-09 15:47:13', '2026-07-09 15:47:13', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_progress` (`id`, `customer_id`, `order_id`, `title`, `description`, `status`, `progress_percentage`, `file_path`, `file_name`, `file_type`, `file_size`, `updated_by`, `completed_at`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 4, 7, 'Cable Management', 'Routing PSU and GPU cables', 'in_progress', 40, NULL, NULL, NULL, NULL, 'Test Staff', NULL, '2026-07-11 14:20:44', '2026-07-11 14:20:53', '2026-07-11 14:20:53');
INSERT INTO `customer_progress` (`id`, `customer_id`, `order_id`, `title`, `description`, `status`, `progress_percentage`, `file_path`, `file_name`, `file_type`, `file_size`, `updated_by`, `completed_at`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 4, NULL, 'Cable Management', NULL, 'completed', 100, NULL, NULL, NULL, NULL, NULL, '2026-07-11 14:24:29', '2026-07-11 14:24:29', '2026-07-11 14:24:29', '2026-07-11 14:24:29');

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'QVCST-0001', 'MUHAMMAD FARIS ISKANDAR BIN SHAMSIR', 'BruhRis', 'fariskandar99@gmail.com', '+60172109876', '47810', 'WhatsApp', NULL, 'Custom PC build', 'Friend / Referral', NULL, 'Najmi Zairul', 1, 1, '2026-07-09 15:30:49', 'ac450a3c-93a4-4e70-aaac-5a46e5d11578', 1, '2025-12-29 20:54:33', '2026-07-09 15:30:49', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'QVCST-0002', 'MUHAMMAD NAJMI NOOR ZAIRUL', 'Najmi', 'najminoorzairul@gmail.com', '+60197017321', 'A-1-10, Cita Damansara, Jalan PJU 3/27, Sunway Damansara', 'WhatsApp', NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, '711774c2-478a-4c85-808e-18848a78e45a', 1, '2025-12-29 21:32:12', '2025-12-30 00:04:11', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'QVCST-0003', 'NURSYAZWANI BINTI AHMAD NIZAM', 'Wani', 'wannieq8@gmail.com', '+60197266130', 'A-1-10', 'WhatsApp', NULL, NULL, 'TikTok', NULL, NULL, 1, 1, '2026-07-09 15:30:43', '9f8be78c-9ba9-4218-9e1c-c3028a74a8a6', 1, '2025-12-29 21:33:04', '2026-07-09 15:30:43', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'QVCST-0004', 'MUHAMMAD EIRFAN BIN NOOR ZAIRUL', 'Epan', 'eirfan019@gmail.com', '+60197091129', 'No 2&4, Jalan Perdana 2/42, Taman Bukit Perdana 2, 83000, Batu Pahat,Johor', 'WhatsApp', NULL, 'Nice', NULL, NULL, NULL, 1, 1, '2026-01-11 07:13:03', '01064af6-083e-4e5e-9722-b05921e9876f', 1, '2025-12-29 22:30:09', '2026-01-11 07:13:03', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'QVCST-0005', 'MUHAMMAD IZZHAZIQ BIN MOHD RAJIL', 'Izz', 'Izzhaziq1117@gmail.com', '+601126605294', 'A-404, Tingkat 3, Palma Perak Apartment, Jalan Cecawi 6/6, 47810,Petaling Jaya, Selangor', 'WhatsApp', NULL, 'Pc build', 'Friend / Referral', NULL, 'Najmi Zairul', 1, 1, '2026-01-11 07:05:43', '420c2946-d5da-45d3-ac54-75f521152dbc', 1, '2025-12-29 21:35:06', '2026-01-11 07:05:43', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'QVCST-0006', 'TEST DATA', 'test', NULL, '+602603123123', 'dasasd.12312312,123,daman', 'TikTok', NULL, 'asdasdasd', 'Event / Booth', NULL, NULL, 1, 1, '2026-01-11 07:12:56', '08bcdc01-a436-43ee-82f8-61d7f30f938d', 1, '2025-12-31 09:22:52', '2026-01-11 07:12:56', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'QVCST-0007', 'AHMAD ALBAB BIN ISMAIL', 'Ahmad', 'ahmad@gmail.com', '+600232323232', 'Damansara, 47810, Petaling jaya,Selangor', 'Facebook', NULL, 'aswdasdasd', 'TikTok', NULL, NULL, 1, 1, '2025-10-01 05:23:04', '1c422c11-129e-45f5-af66-6641cccc2525', 1, '2026-01-01 05:16:58', '2026-01-01 05:23:04', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'QVCST-0008', 'SYED IQBAL', 'Iqbal', 'iqbal@mail.com', '+60912121212', 'atas klang', 'Instagram', NULL, 'sdasdsd', 'Friend / Referral', NULL, 'megat', 1, 1, '2026-01-18 01:43:25', 'fee7c0b0-4b5e-49f0-ae7e-4db95a1022b6', 1, '2026-01-18 01:40:54', '2026-01-18 01:43:25', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 'QVCST-0009', 'WAWA FFF', 'wawa', 'wawa@gmail.com', '+600234234234', 'werwrwerer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'cde29dba-c6c9-4941-bb47-ec6b96a5f111', 0, '2026-03-21 02:23:08', '2026-03-21 02:23:08', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 'QVCST-0010', 'TEST', 'test1', 'test@gmail.com', '+60123456789', 'test1', 'WhatsApp', NULL, NULL, NULL, NULL, NULL, 1, 1, '2026-07-09 14:07:03', 'dc0de823-f55f-430f-882e-85a6548cb116', 1, '2026-07-09 14:03:54', '2026-07-09 14:07:03', NULL);
INSERT INTO `customers` (`id`, `customer_id`, `full_name`, `preferred_name`, `email`, `phone`, `address`, `contact_method`, `contact_other`, `feedback`, `hear_about`, `hear_about_other`, `referred_by`, `consent`, `approve`, `approved_at`, `update_token`, `update_used`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 'QVCST-0011', 'FARIS BIN FARIS', 'Faris', 'faris@gmail.com', '+601912312312', 'kota damansara seksyen 7', 'Discord', NULL, 'mas amba', 'Instagram', NULL, NULL, 1, 1, '2026-07-09 15:33:07', '93eb19ba-9628-48de-bb2c-668cc732a646', 1, '2026-07-09 15:31:42', '2026-07-09 15:33:07', NULL);

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

INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'IE_QVSE', 1, '2026-07-02 12:06:14', '2026-07-02 12:06:14', NULL);
INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'I_QVTD', 1, '2026-07-02 12:07:01', '2026-07-02 12:07:01', NULL);
INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'I_QVMR', 1, '2026-07-11 15:12:49', '2026-07-11 15:12:49', NULL);
INSERT INTO `destination` (`id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'IE_QVMR', 1, '2026-07-11 15:12:49', '2026-07-11 15:12:49', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `details` text NOT NULL,
  `amount` varchar(191) NOT NULL,
  `expenses_date` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'IC-0001', 2, 'QVSK-SPARE-CPU-001', 'AMD Ryzen 7 9800X3D (Spare)', 2399, 5, 3, 1, 1, 0, 0, '2026-01-01 00:00:00', 36, '2029-01-01 00:00:00', 'AMD', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);
INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'IC-0002', 1, 'QVSK-SPARE-CPU-002', 'INTEL Core Ultra 7 265 (Spare)', 1699, 4, 2, 1, 1, 0, 0, '2026-01-01 00:00:00', 36, '2029-01-01 00:00:00', 'INTEL', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);
INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'IC-0003', 7, 'QVSK-SPARE-GPU-001', 'MSI Trio X White RTX 5080 16GB (Spare)', 4999, 3, 1, 3, 1, 0, 0, '2026-01-01 00:00:00', 36, '2029-01-01 00:00:00', 'MSI', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);
INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'IC-0004', 9, 'QVSK-SPARE-GPU-002', 'ASUS ROG Strix RTX 5070 Ti 16GB (Spare)', 3799, 3, 2, 3, 1, 0, 0, '2026-01-01 00:00:00', 60, '2031-01-01 00:00:00', 'ASUS', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);
INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'IC-0005', 8, 'QVSK-SPARE-RAM-001', 'G.SKILL Trident Z5 32GB Kit (Spare)', 899, 6, 4, 5, 1, 0, 0, '2026-01-01 00:00:00', 24, '2028-01-01 00:00:00', 'G.Skill', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);
INSERT INTO `inv_care` (`id`, `inv_care`, `care_id`, `sku_code`, `item_name`, `unit_cost`, `max_stock`, `current_stock`, `category`, `status`, `generate_id`, `serial_label`, `warranty_starts`, `warranty_duration`, `warranty_ends`, `manufacturer`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'IC-0006', 10, 'QVSK-SPARE-GPU-001', 'MSI Trio X White RTX 5080 16GB (Spare)', 4999, 3, 1, 3, 1, 0, 0, '2026-01-01 00:00:00', 36, '2029-01-01 00:00:00', 'MSI', '2026-07-12 18:18:12', '2026-07-12 18:18:12', NULL);

DROP TABLE IF EXISTS `inv_excl_serve`;
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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'MVMT-0001', '2025-11-12 00:00:00', 6, 1, NULL, 'Quivitech Essential Kit Box', 'Inventory', 300, '19.0100', '2026-07-11 15:48:17', '2026-07-12 05:08:00', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'MVMT-0002', '2025-11-12 00:00:00', 7, 1, NULL, 'Quivitech Prime Series Box', 'Inventory', 200, '29.1800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'MVMT-0003', '2025-11-12 00:00:00', 8, 1, NULL, 'Quivitech Collector\'s Edition Box', 'Inventory', 200, '47.8000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'MVMT-0004', '2025-11-12 00:00:00', 9, 1, NULL, 'Quivitech The Stash Screw Box', 'Inventory', 500, '2.8100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'MVMT-0005', '2025-11-12 00:00:00', 10, 3, NULL, 'Quivitech White Embroidery Keychain', 'Inventory', 200, '5.5000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'MVMT-0006', '2025-11-12 00:00:00', 11, 3, NULL, 'Quivitech Red Eagle Hook Keychain', 'Inventory', 50, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'MVMT-0007', '2025-11-12 00:00:00', 12, 3, NULL, 'Quivitech Yellow Eagle Hook Keychain', 'Inventory', 50, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'MVMT-0008', '2025-11-12 00:00:00', 13, 3, NULL, 'Quivitech Blue Eagle Hook Keychain', 'Inventory', 50, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'MVMT-0009', '2025-11-12 00:00:00', 14, 3, NULL, 'Quivitech Pink Eagle Hook Keychain', 'Inventory', 50, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'MVMT-0010', '2025-11-12 00:00:00', 15, 4, NULL, 'Quivitech Carbon Fiber Keychain', 'Inventory', 50, '43.1800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'MVMT-0011', '2025-11-12 00:00:00', 16, 4, NULL, 'Quivitech Full Grain Leather Keychain', 'Inventory', 50, '16.0000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'MVMT-0012', '2025-11-12 00:00:00', 17, 1, NULL, 'Quivitech Essential Kit Perk Card', 'Inventory', 200, '2.9000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'MVMT-0013', '2025-11-12 00:00:00', 18, 1, NULL, 'Quivitech Prime Series Perk Card', 'Inventory', 200, '2.9000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'MVMT-0014', '2025-11-12 00:00:00', 19, 1, NULL, 'Quivitech Collector\'s Edition Perk Card', 'Inventory', 200, '2.9000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 'MVMT-0015', '2025-11-12 00:00:00', 20, 3, NULL, 'Quivitech 2cm x 15cm Velcro Back to Back', 'Inventory', 400, '0.7800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (16, 'MVMT-0016', '2025-11-12 00:00:00', 21, 3, NULL, 'Quivitech 1" x 6" Velcro OneWrap', 'Inventory', 300, '6.3000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (17, 'MVMT-0017', '2025-11-12 00:00:00', 22, 3, NULL, 'Quivitech Microfiber Pouch', 'Inventory', 200, '7.3500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (18, 'MVMT-0018', '2025-11-12 00:00:00', 23, 3, NULL, 'Quivitech Polymer Pouch', 'Inventory', 200, '7.8000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 'MVMT-0019', '2025-11-12 00:00:00', 24, 4, NULL, 'Quivitech Neoprene Pouch', 'Inventory', 200, '7.7100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 'MVMT-0020', '2025-11-12 00:00:00', 6, 1, NULL, 'Quivitech Essential Kit Box', 'Inventory', 10, '19.0100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 'MVMT-0021', '2025-11-12 00:00:00', 7, 1, NULL, 'Quivitech Prime Series Box', 'Inventory', 14, '14.0000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (22, 'MVMT-0022', '2025-11-12 00:00:00', 8, 1, NULL, 'Quivitech Collector\'s Edition Box', 'Inventory', 8, '47.8000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (23, 'MVMT-0023', '2025-11-12 00:00:00', 9, 1, NULL, 'Quivitech The Stash Screw Box', 'Inventory', 50, '2.8100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (24, 'MVMT-0024', '2025-11-12 00:00:00', 13, 3, NULL, 'Quivitech Blue Eagle Hook Keychain', 'Inventory', 1, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (25, 'MVMT-0025', '2025-11-12 00:00:00', 14, 3, NULL, 'Quivitech Pink Eagle Hook Keychain', 'Inventory', 1, '7.8500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (26, 'MVMT-0026', '2025-11-12 00:00:00', 16, 4, NULL, 'Quivitech Full Grain Leather Keychain', 'Inventory', 2, '16.0000', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (27, 'MVMT-0027', '2025-11-12 00:00:00', 25, 2, NULL, 'MOLEX Black 8 EPS Pin  ATX Connector', 'Inventory', 100, '1.8400', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (28, 'MVMT-0028', '2025-11-12 00:00:00', 26, 2, NULL, 'MOLEX Blue 8 EPS Pin ATX Connector', 'Inventory', 100, '1.9758', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (29, 'MVMT-0029', '2025-11-12 00:00:00', 27, 2, NULL, 'MOLEX Blue 10 MB Pin  ATX Connector', 'Inventory', 100, '1.8100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (30, 'MVMT-0030', '2025-11-12 00:00:00', 28, 2, NULL, 'MOLEX Black 10 MB Pin ATX Connector', 'Inventory', 100, '1.0500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (31, 'MVMT-0031', '2025-11-12 00:00:00', 29, 2, NULL, 'MOLEX Black 12V 2x6 PCIe Pin ATX Connector', 'Inventory', 100, '0.6600', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (32, 'MVMT-0032', '2025-11-12 00:00:00', 30, 2, NULL, 'MDPC-X 12V 2x6 PCIe Pin ATX Connector', 'Inventory', 10, '17.9800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (33, 'MVMT-0033', '2025-11-12 00:00:00', 31, 2, NULL, 'MOLEX Blue 18 MB Pin  ATX Connector', 'Inventory', 100, '3.9100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (34, 'MVMT-0034', '2025-11-12 00:00:00', 32, 2, NULL, 'MDPC-X  18  MB Pin ATX Connector', 'Inventory', 20, '5.6500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (35, 'MVMT-0035', '2025-11-12 00:00:00', 33, 2, NULL, 'MOLEX Blue 24 MB Pin  ATX Connector', 'Inventory', 100, '5.7600', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (36, 'MVMT-0036', '2025-11-12 00:00:00', 34, 2, NULL, 'MDPC-X  24  MB Pin ATX Connector', 'Inventory', 20, '6.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (37, 'MVMT-0037', '2025-11-12 00:00:00', 35, 2, NULL, 'MDPC-X 8 Pin Cable Comb', 'Inventory', 100, '4.8700', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (38, 'MVMT-0038', '2025-11-12 00:00:00', 36, 2, NULL, 'MDPC-X 12V 2x6 PCIe Pin Cable Comb', 'Inventory', 30, '6.0900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (39, 'MVMT-0039', '2025-11-12 00:00:00', 37, 2, NULL, 'MDPC-X 24 Pin Cable Comb', 'Inventory', 35, '7.3100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (40, 'MVMT-0040', '2025-11-12 00:00:00', 38, 2, NULL, 'MDPC-X 4:1 Heatshrink Small', 'Inventory', 204, '0.2500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (41, 'MVMT-0041', '2025-11-12 00:00:00', 39, 2, NULL, 'MDPC-X 15 AWG Pin Terminal', 'Inventory', 2050, '0.3900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (42, 'MVMT-0042', '2025-11-12 00:00:00', 40, 2, NULL, 'MDPC-X 17 AWG Pin Terminal', 'Inventory', 500, '0.3900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (43, 'MVMT-0043', '2025-11-12 00:00:00', 41, 2, NULL, 'MDPC-X Blackest Black Cable Sleeve XTC', 'Inventory', 200, '2.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (44, 'MVMT-0044', '2025-11-12 00:00:00', 42, 2, NULL, 'MDPC-X XXX White Cable Sleeve XTC', 'Inventory', 200, '2.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (45, 'MVMT-0045', '2025-11-12 00:00:00', 43, 2, NULL, 'MDPC-X Gold Cable Sleeve XTC', 'Inventory', 100, '2.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (46, 'MVMT-0046', '2025-11-12 00:00:00', 44, 2, NULL, 'MDPC-X Blackest Black Cable Sleeve MICRO', 'Inventory', 30, '3.1700', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (47, 'MVMT-0047', '2025-11-12 00:00:00', 45, 2, NULL, 'MDPC-X XXX White Cable Sleeve MICRO', 'Inventory', 30, '3.5100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (48, 'MVMT-0048', '2025-11-12 00:00:00', 46, 2, NULL, 'MDPC-X Gold Cable Sleeve MICRO', 'Inventory', 30, '3.5100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (49, 'MVMT-0049', '2025-11-12 00:00:00', 47, 2, NULL, 'MDPC-X Platinum X Cable Sleeve XTC', 'Inventory', 100, '2.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (50, 'MVMT-0050', '2025-11-12 00:00:00', 48, 2, NULL, 'MDPC-X Perfect Pink Cable Sleeve XTC', 'Inventory', 100, '2.1900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (51, 'MVMT-0051', '2025-11-12 00:00:00', 49, 2, NULL, 'MDPC-X White 15-AWG Wire', 'Inventory', 500, '3.5100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (52, 'MVMT-0052', '2025-11-12 00:00:00', 50, 2, NULL, 'MDPC-X Grey 17-AWG Wire', 'Inventory', 50, '4.4800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (53, 'MVMT-0053', '2025-11-12 00:00:00', 51, 2, NULL, 'MDPC-X Black 23-AWG Wire', 'Inventory', 30, '1.6600', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (54, 'MVMT-0054', '2025-11-12 00:00:00', 52, 2, NULL, 'MDPC-X 3:1 Heatshrink Micro', 'Inventory', 1, '0.2500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (55, 'MVMT-0055', '2025-11-12 00:00:00', 53, 2, NULL, 'MDPC-X 8 PCIe Pin  ATX Connector', 'Inventory', 82, '6.4300', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (56, 'MVMT-0056', '2025-11-12 00:00:00', 54, 2, NULL, 'MOLEX Black 8 PCIe Pin ATX Connector', 'Inventory', 100, '1.8800', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (57, 'MVMT-0057', '2025-11-14 00:00:00', 30, 2, 7, 'MDPC-X 12V 2x6 PCIe Pin ATX Connector', 'Inventory', 2, '17.9800', '2026-07-11 15:48:17', '2026-07-12 01:49:40', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (58, 'MVMT-0058', '2025-11-14 00:00:00', 32, 2, NULL, 'MDPC-X  18  MB Pin ATX Connector', 'Inventory', 3, '5.6500', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (59, 'MVMT-0059', '2025-11-14 00:00:00', 37, 2, NULL, 'MDPC-X 24 Pin Cable Comb', 'Inventory', 2, '7.3100', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (60, 'MVMT-0060', '2025-11-14 00:00:00', 36, 2, NULL, 'MDPC-X 12V 2x6 PCIe Pin Cable Comb', 'Inventory', 1, '6.0900', '2026-07-11 15:48:17', '2026-07-11 15:51:26', NULL);
INSERT INTO `inv_move` (`id`, `movement_id`, `date`, `master_sku_id`, `destination_id`, `order_id`, `item_name`, `type`, `quantity`, `unit_cost`, `created_at`, `updated_at`, `deleted_at`) VALUES (61, 'MVMT-0061', '2026-07-11 00:00:00', 55, 1, NULL, 'Test Widget', 'Inventory', 5, '9.9900', '2026-07-11 15:53:44', '2026-07-11 15:56:11', '2026-07-11 15:56:11');

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

INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'test', 2, NULL, 'Test 1', 'Malaysia', '1000.00', 'pcs', 4, '2026-07-09 14:57:30', '2026-07-09 14:57:30', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'QVSKU 0001', NULL, NULL, 'Quivitech Essential Kit Box', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'QVSKU 0002', NULL, NULL, 'Quivitech Prime Series Box', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'QVSKU 0003', NULL, NULL, 'Quivitech Collector\'s Edition Box', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'QVSKU 0004', NULL, NULL, 'Quivitech The Stash Screw Box', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'QVSKU 0005', NULL, NULL, 'Quivitech White Embroidery Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'QVSKU 0006', NULL, NULL, 'Quivitech Red Eagle Hook Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'QVSKU 0007', NULL, NULL, 'Quivitech Yellow Eagle Hook Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'QVSKU 0008', NULL, NULL, 'Quivitech Blue Eagle Hook Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'QVSKU 0009', NULL, NULL, 'Quivitech Pink Eagle Hook Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 'QVSKU 0010', NULL, NULL, 'Quivitech Carbon Fiber Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (16, 'QVSKU 0011', NULL, NULL, 'Quivitech Full Grain Leather Keychain', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (17, 'QVSKU 0012', NULL, NULL, 'Quivitech Essential Kit Perk Card', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (18, 'QVSKU 0013', NULL, NULL, 'Quivitech Prime Series Perk Card', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 'QVSKU 0014', NULL, NULL, 'Quivitech Collector\'s Edition Perk Card', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 'QVSKU 0016', NULL, NULL, 'Quivitech 2cm x 15cm Velcro Back to Back', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 'QVSKU 0017', NULL, NULL, 'Quivitech 1" x 6" Velcro OneWrap', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (22, 'QVSKU 0018', NULL, NULL, 'Quivitech Microfiber Pouch', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (23, 'QVSKU 0019', NULL, NULL, 'Quivitech Polymer Pouch', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (24, 'QVSKU 0020', NULL, NULL, 'Quivitech Neoprene Pouch', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (25, 'QVSKU 0021', NULL, NULL, 'MOLEX Black 8 EPS Pin  ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (26, 'QVSKU 0022', NULL, NULL, 'MOLEX Blue 8 EPS Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (27, 'QVSKU 0023', NULL, NULL, 'MOLEX Blue 10 MB Pin  ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (28, 'QVSKU 0024', NULL, NULL, 'MOLEX Black 10 MB Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (29, 'QVSKU 0025', NULL, NULL, 'MOLEX Black 12V 2x6 PCIe Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (30, 'QVSKU 0026', NULL, NULL, 'MDPC-X 12V 2x6 PCIe Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (31, 'QVSKU 0027', NULL, NULL, 'MOLEX Blue 18 MB Pin  ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (32, 'QVSKU 0028', NULL, NULL, 'MDPC-X  18  MB Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (33, 'QVSKU 0029', NULL, NULL, 'MOLEX Blue 24 MB Pin  ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (34, 'QVSKU 0030', NULL, NULL, 'MDPC-X  24  MB Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (35, 'QVSKU 0031', NULL, NULL, 'MDPC-X 8 Pin Cable Comb', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (36, 'QVSKU 0032', NULL, NULL, 'MDPC-X 12V 2x6 PCIe Pin Cable Comb', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (37, 'QVSKU 0033', NULL, NULL, 'MDPC-X 24 Pin Cable Comb', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (38, 'QVSKU 0034', NULL, NULL, 'MDPC-X 4:1 Heatshrink Small', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (39, 'QVSKU 0035', NULL, NULL, 'MDPC-X 15 AWG Pin Terminal', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (40, 'QVSKU 0036', NULL, NULL, 'MDPC-X 17 AWG Pin Terminal', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (41, 'QVSKU 0038', NULL, NULL, 'MDPC-X Blackest Black Cable Sleeve XTC', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (42, 'QVSKU 0039', NULL, NULL, 'MDPC-X XXX White Cable Sleeve XTC', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (43, 'QVSKU 0040', NULL, NULL, 'MDPC-X Gold Cable Sleeve XTC', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (44, 'QVSKU 0041', NULL, NULL, 'MDPC-X Blackest Black Cable Sleeve MICRO', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (45, 'QVSKU 0042', NULL, NULL, 'MDPC-X XXX White Cable Sleeve MICRO', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (46, 'QVSKU 0043', NULL, NULL, 'MDPC-X Gold Cable Sleeve MICRO', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (47, 'QVSKU 0044', NULL, NULL, 'MDPC-X Platinum X Cable Sleeve XTC', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (48, 'QVSKU 0045', NULL, NULL, 'MDPC-X Perfect Pink Cable Sleeve XTC', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (49, 'QVSKU 0046', NULL, NULL, 'MDPC-X White 15-AWG Wire', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (50, 'QVSKU 0047', NULL, NULL, 'MDPC-X Grey 17-AWG Wire', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (51, 'QVSKU 0048', NULL, NULL, 'MDPC-X Black 23-AWG Wire', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (52, 'QVSKU 0049', NULL, NULL, 'MDPC-X 3:1 Heatshrink Micro', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (53, 'QVSKU 0050', NULL, NULL, 'MDPC-X 8 PCIe Pin  ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (54, 'QVSKU 0051', NULL, NULL, 'MOLEX Black 8 PCIe Pin ATX Connector', NULL, NULL, NULL, 1, '2026-07-11 15:48:17', '2026-07-11 15:48:17', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (56, 'QVSK-SPARE-CPU-001', NULL, NULL, 'AMD Ryzen 7 9800X3D (Spare/RMA Unit)', NULL, '2399', 'pcs', 1, '2026-07-12 18:15:08', '2026-07-12 18:15:08', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (57, 'QVSK-SPARE-CPU-002', NULL, NULL, 'INTEL Core Ultra 7 265 (Spare/RMA Unit)', NULL, '1699', 'pcs', 1, '2026-07-12 18:15:08', '2026-07-12 18:15:08', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (58, 'QVSK-SPARE-GPU-001', NULL, NULL, 'MSI Trio X White RTX 5080 16GB (Spare/RMA Unit)', NULL, '4999', 'pcs', 1, '2026-07-12 18:15:09', '2026-07-12 18:15:09', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (59, 'QVSK-SPARE-GPU-002', NULL, NULL, 'ASUS ROG Strix RTX 5070 Ti 16GB (Spare/RMA Unit)', NULL, '3799', 'pcs', 1, '2026-07-12 18:15:09', '2026-07-12 18:15:09', NULL);
INSERT INTO `master_sku` (`id`, `sku_code`, `supplier_id`, `product_raw_id`, `product_name`, `from`, `cost`, `unit_type`, `lkp_status_sku`, `created_at`, `updated_at`, `deleted_at`) VALUES (60, 'QVSK-SPARE-RAM-001', NULL, NULL, 'G.SKILL Trident Z5 32GB (Spare)', NULL, '899', 'pcs', 1, '2026-07-12 18:17:12', '2026-07-12 18:17:12', NULL);

DROP TABLE IF EXISTS `meeting_details`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `meeting_details` (`id`, `meeting_id`, `initial_budget`, `reason`, `play_mode`, `include_monitor`, `include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size`, `okay_with_aio`, `gpu_sag`, `need_rgb`, `qvcrf_tag`, `qvse`, `qvca`, `qvtd`, `qvtd_notes`, `target_build_date`, `target_location`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 13, '10000.00', 2, 2, 1, 'all', NULL, 'wood', 'none', 'none', 1, 3, 1, 1, 0, 1, 1, 1, 0, NULL, '2026-07-29 22:30:00', 'no 2, jalan bangsar', '2026-07-09 14:32:04', '2026-07-09 14:32:04', NULL);
INSERT INTO `meeting_details` (`id`, `meeting_id`, `initial_budget`, `reason`, `play_mode`, `include_monitor`, `include_notes`, `notes`, `theme_style`, `preference`, `exemption`, `future_proof`, `case_size`, `okay_with_aio`, `gpu_sag`, `need_rgb`, `qvcrf_tag`, `qvse`, `qvca`, `qvtd`, `qvtd_notes`, `target_build_date`, `target_location`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 14, '6000.00', 2, 2, NULL, NULL, NULL, 'premium minimal wood accent', 'rog but can go asus or giga', 'asrock', 1, 3, 1, 1, 1, 0, 1, 1, 1, 'gpu cable dual colour', '2026-08-10 17:45:00', 'kota damansara seksyen 7', '2026-07-09 17:05:55', '2026-07-09 17:05:55', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `meetings` (`id`, `meeting_id`, `customer_id`, `title`, `meeting_date`, `meeting_notes`, `document`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'QV-MEET-0001', 20, 'custom gaming pc test', '2026-07-09', '9:00 pm', NULL, '2026-07-09 14:30:43', '2026-07-09 14:30:43', NULL);
INSERT INTO `meetings` (`id`, `meeting_id`, `customer_id`, `title`, `meeting_date`, `meeting_notes`, `document`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 'QV-MEET-0014', 21, 'first meeting', '2026-07-10', NULL, NULL, '2026-07-09 16:09:24', '2026-07-09 16:09:24', NULL);

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17, '2014_10_12_000000_create_users_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18, '2014_10_12_100000_create_password_resets_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19, '2019_08_19_000000_create_failed_jobs_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20, '2021_06_01_174301_create_employees_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21, '2021_06_02_134411_create_suppliers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22, '2021_06_02_153225_create_categories_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23, '2021_06_02_174502_create_products_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24, '2021_06_03_033045_create_expenses_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25, '2021_06_03_052049_create_salaries_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26, '2021_06_04_175056_create_customers_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27, '2021_06_05_113432_create_pos_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28, '2021_06_05_113823_create_carts_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29, '2021_06_06_040233_create_extras_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30, '2021_06_06_073441_create_order_table', 1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34, '2021_06_06_073520_create_order_details_table', 2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37, '2025_12_25_140337_update_customers_table_add_registration_fields', 3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39, '2025_12_29_124813_create_meetings_table', 4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41, '2025_12_30_102944_add_colour_to_categories_table', 5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42, '2025_12_30_113510_create_serve_table', 6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43, '2025_12_30_125215_create_care_table', 7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44, '2026_01_03_143720_create_pc_build_requests_table', 8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45, '2026_01_04_153225_create_craft_table', 9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46, '2026_01_03_143720_create_meeting_details_table', 10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47, '2026_07_11_090000_restructure_serve_pce_annual_services', 11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48, '2026_07_11_100000_replace_documents_with_customer_progress', 12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49, '2026_07_11_110000_rebuild_inv_move_table', 13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50, '2026_07_12_090000_inv_move_replace_reference_with_order', 14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51, '2026_07_02_150000_fix_inventory_tables_schema', 14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52, '2026_07_14_090000_add_cleaning_claim_dates_to_serve_mps', 15);

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
  `approve` tinyint(1) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `is_reason` tinyint(1) NOT NULL COMMENT '1: Work, 2: Gaming',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (1, 'QV-ORDR-0001', 'QVT-INV-2607-1', 4, '8', '15432', NULL, '15432', NULL, NULL, NULL, '2026-03-19 14:36:42', 'March', '2026', 2, 3, 3, 1, '2026-07-09 14:21:43', '2026-03-19 14:36:42', '2026-07-10 18:37:41', NULL, 1);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (2, 'QV-ORDR-0002', 'QVT-INV-2603-2', 19, '7', '13913', NULL, '13913', NULL, NULL, NULL, '2026-03-21 10:30:29', 'March', '2026', 2, 3, 3, 1, '2026-03-21 05:11:40', '2026-03-21 10:30:29', '2026-07-10 18:37:41', NULL, 2);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (3, 'QV-ORDR-0003', 'QVT-INV-2607-3', 20, '17', '23333', NULL, '23333', NULL, NULL, NULL, '2026-07-09 14:08:47', 'July', '2026', 4, 3, 3, 1, '2026-07-09 14:22:43', '2026-07-09 14:08:47', '2026-07-10 18:37:41', NULL, 2);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (4, 'QV-ORDR-0004', 'QVT-INV-2607-4', 20, '8', '7435', NULL, '7435', NULL, NULL, NULL, '2026-07-09 14:17:17', 'July', '2026', 3, 2, 1, 1, '2026-07-12 11:40:41', '2026-07-09 14:17:17', '2026-07-12 11:40:41', NULL, 2);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (5, 'QV-ORDR-0005', 'QVT-INV-2607-5', 20, '9', '5198', NULL, '5198', NULL, NULL, NULL, '2026-07-09 14:19:06', 'July', '2026', 1, 1, 1, 1, '2026-07-12 11:56:01', '2026-07-09 14:19:06', '2026-07-12 11:56:01', NULL, 2);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (6, 'QV-ORDR-0006', 'QVT-INV-2607-6', 21, '14', '10537', NULL, '10537', NULL, NULL, NULL, '2026-07-09 15:35:33', 'July', '2026', 2, 3, 3, 1, '2026-07-09 15:44:53', '2026-07-09 15:35:33', '2026-07-10 17:15:04', NULL, 2);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (7, 'QV-ORDR-0007', NULL, 4, '9', '22411', NULL, '22411', NULL, NULL, NULL, '2026-07-10 16:16:33', 'July', '2026', 4, 3, 3, NULL, NULL, '2026-07-10 16:16:33', '2026-07-10 18:37:41', NULL, 1);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (8, 'QV-ORDR-0008', 'QVT-INV-2607-8', 20, '3', '3500.00', NULL, '3500.00', NULL, NULL, NULL, '2026-07-12 12:21:58', 'July', '2026', 1, 1, 1, 1, '2026-07-12 12:24:24', '2026-07-12 12:21:58', '2026-07-12 12:24:24', NULL, 1);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (9, 'QV-ORDR-0009', 'QVT-INV-2607-9', 20, '4', '6200.00', NULL, '6200.00', NULL, NULL, NULL, '2026-07-12 12:21:58', 'July', '2026', 1, 1, 1, 1, '2026-07-12 12:24:24', '2026-07-12 12:21:58', '2026-07-12 12:24:24', NULL, 1);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (10, 'QV-ORDR-0010', 'QVT-INV-2607-10', 20, '5', '8200.00', NULL, '8200.00', NULL, NULL, NULL, '2026-07-12 12:21:58', 'July', '2026', 3, 2, 1, 1, '2026-07-12 12:24:24', '2026-07-12 12:21:58', '2026-07-12 12:24:24', NULL, 1);
INSERT INTO `order` (`id`, `order_id`, `invoice_id`, `customer_id`, `qty`, `sub_total`, `vat`, `total`, `pay`, `due`, `pay_by`, `order_date`, `order_month`, `order_year`, `craft_id`, `serve_id`, `care_id`, `approve`, `approved_at`, `created_at`, `updated_at`, `deleted_at`, `is_reason`) VALUES (11, 'QV-ORDR-0011', 'QVT-INV-2607-11', 20, '6', '9750.00', NULL, '9750.00', NULL, NULL, NULL, '2026-07-12 12:21:58', 'July', '2026', 3, 2, 1, 1, '2026-07-12 12:24:24', '2026-07-12 12:21:58', '2026-07-12 12:24:24', NULL, 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (1, 1, 1, '1', '2799.00', '2799.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (2, 1, 8, '1', '1899.00', '1899.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (3, 1, 14, '2', '959.00', '1918', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (4, 1, 25, '1', '6109.00', '6109.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (5, 1, 22, '1', '769.00', '769.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (6, 1, 12, '1', '1499.00', '1499.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (7, 1, 30, '1', '439.00', '439.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (8, 2, 5, '1', '1779.00', '1779.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (9, 2, 8, '1', '1899.00', '1899.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (10, 2, 25, '1', '6109.00', '6109.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (11, 2, 14, '1', '959.00', '959.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (12, 2, 22, '1', '769.00', '769.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (13, 2, 12, '1', '1499.00', '1499.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (14, 2, 29, '1', '899.00', '899.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (15, 3, 1, '1', '2799.00', '2799.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (16, 3, 20, '1', '639.00', '639.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (17, 3, 28, '1', '539.00', '539.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (18, 3, 34, '3', '199.00', '597', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (19, 3, 35, '6', '199.00', '1194', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (20, 3, 8, '1', '1899.00', '1899.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (21, 3, 18, '1', '7099.00', '7099.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (22, 3, 25, '1', '6109.00', '6109.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (23, 3, 12, '1', '1499.00', '1499.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (24, 3, 14, '1', '959.00', '959.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (34, 4, 3, '1', '0', '0', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (35, 4, 8, '1', '1899', '1899', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (36, 4, 16, '1', '0', '0', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (37, 4, 24, '1', '0', '0', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (38, 4, 12, '1', '1499', '1499', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (39, 4, 20, '1', '639', '639', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (40, 4, 15, '1', '2499', '2499', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (41, 4, 29, '1', '899', '899', '2026-07-09 14:17:47', '2026-07-09 14:17:47', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (42, 5, 2, '1', '3699.00', '3699.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (43, 5, 7, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (44, 5, 16, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (45, 5, 23, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (46, 5, 12, '1', '1499.00', '1499.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (47, 5, 21, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (48, 5, 13, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (49, 5, 26, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (50, 5, 39, '1', '0.00', '0.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (60, 6, 1, '1', '2799', '2799', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (61, 6, 7, '1', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (62, 6, 23, '1', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (63, 6, 20, '1', '639', '639', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (64, 6, 13, '1', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (65, 6, 38, '6', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (66, 6, 10, '1', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (67, 6, 26, '1', '0', '0', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (68, 6, 18, '1', '7099', '7099', '2026-07-09 15:36:25', '2026-07-09 15:36:25', NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (69, 7, 2, '1', '3699.00', '3699.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (70, 7, 8, '1', '1899.00', '1899.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (71, 7, 25, '2', '6109.00', '12218', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (72, 7, 14, '2', '959.00', '1918', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (73, 7, 20, '1', '639.00', '639.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (74, 7, 12, '1', '1499.00', '1499.00', NULL, NULL, NULL, NULL);
INSERT INTO `order_details` (`id`, `order_id`, `pro_id`, `pro_qty`, `pro_price`, `sub_total`, `created_at`, `updated_at`, `serial_no`, `start_warranty_at`) VALUES (75, 7, 28, '1', '539.00', '539.00', NULL, NULL, NULL, NULL);

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

INSERT INTO `product_warranty` (`id`, `product_id`, `product_code`, `product_name`, `serial_no`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 25, 'QV-ITM-CPU-0025', 'G.SKILL Trident Z5 Royal Neo RGB DDR5 CL28 6000 (32GB X 2)', 'QV-WRTY-000001', '2026-02-25 09:04:48', '2026-02-25 23:46:33', NULL);
INSERT INTO `product_warranty` (`id`, `product_id`, `product_code`, `product_name`, `serial_no`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 20, 'QV-ITM-CPU-0020', 'ARCTIC Liquid Freezer III Pro ARGB 360', 'QV-WRTY-000002', '2026-02-26 00:56:48', '2026-02-26 00:56:48', NULL);
INSERT INTO `product_warranty` (`id`, `product_id`, `product_code`, `product_name`, `serial_no`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 25, 'QV-ITM-CPU-0026', 'NZXT H9 Elite', 'QV-WRTY-000003', '2026-02-26 02:32:56', '2026-02-28 01:49:56', NULL);
INSERT INTO `product_warranty` (`id`, `product_id`, `product_code`, `product_name`, `serial_no`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 4, 'QV-ITM-CPU-0004', 'INTEL Core Ultra 5 245KF', 'QV-WRTY-000004', '2026-02-28 00:34:59', '2026-02-28 00:34:59', NULL);
INSERT INTO `product_warranty` (`id`, `product_id`, `product_code`, `product_name`, `serial_no`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 11, 'QV-PROD-CPU-0011', 'SAMSUNG 9100 Pro 1TB', 'QV-WRTY-000005', '2026-03-15 06:02:03', '2026-07-09 14:56:38', NULL);

DROP TABLE IF EXISTS `products`;
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

INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 1, 'QV-PROD-CPU-0001', 1, 'CPU', 2, 6, 'AMD Ryzen 7 9800X3D', 8, 16, '2160P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2799.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103563.png', 39, NULL, '2026-02-11 18:18:32', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 1, 'QV-PROD-CPU-0002', 1, 'CPU', 2, 6, 'AMD Ryzen 9 9950X3D', 16, 32, '2160P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '3699.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103599.png', 30, NULL, '2026-02-11 18:18:32', '2026-02-26 03:00:01', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 1, 'QV-PROD-CPU-0003', 1, 'CPU', 2, 6, 'AMD Ryzen 7 7800X3D', 8, 16, '2160P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103498.png', 47, NULL, '2026-02-11 18:18:32', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 1, 'QV-PROD-CPU-0004', 1, 'CPU', 4, 7, 'INTEL Core Ultra 5 245KF', 14, 20, '1440P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1039.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159321.png', 44, NULL, '2026-02-11 18:18:32', '2026-02-26 18:28:41', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 1, 'QV-PROD-CPU-0005', 1, 'CPU', 4, 7, 'INTEL Core Ultra 7 265', 20, 28, '2160P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1779.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159303.png', 46, NULL, '2026-02-11 18:18:32', '2026-02-26 18:28:23', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 1, 'QV-PROD-CPU-0006', 1, 'CPU', 4, 7, 'INTEL Core Ultra 7 265KF', 20, 28, '2160P', NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1779.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159286.png', 50, NULL, '2026-02-11 18:18:32', '2026-02-26 18:28:06', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 1, 'QV-PROD-CPU-0007', 6, 'MBD', 11, 8, 'GIGABYTE Aorus X870 ELite Ice', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ATX', NULL, 0, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772158386.png', 41, NULL, '2026-02-11 18:53:57', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 1, 'QV-PROD-CPU-0008', 6, 'MBD', 11, 8, 'GIGABYTE Aorus X870 Stealth Ice', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ATX', NULL, 1, '1899.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772158365.png', 21, NULL, '2026-02-11 18:53:57', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 1, 'QV-PROD-CPU-0009', 6, 'MBD', 11, 9, 'ASUS ROG Crosshair X870e Hero', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ATX', NULL, 0, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131661.png', 50, NULL, '2026-02-11 18:53:57', '2026-02-26 10:47:41', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 1, 'QV-PROD-CPU-0010', 2, 'SSD', 56, 1, 'SAMSUNG 990 Pro 2TB', 0, NULL, NULL, 'NVME', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, 'Gen 4', NULL, '2TB', NULL, NULL, NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131569.png', 43, NULL, '2026-02-11 19:04:35', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 1, 'QV-PROD-CPU-0011', 2, 'SSD', 55, 1, 'SAMSUNG 9100 Pro 1TB', 0, NULL, NULL, 'NVME', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, 'Gen 5', NULL, '1TB', NULL, NULL, NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103110.png', 48, NULL, '2026-02-11 19:04:35', '2026-02-26 02:51:52', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 1, 'QV-PROD-CPU-0012', 2, 'SSD', 56, 1, 'SAMSUNG 9100 Pro 2TB', 0, NULL, NULL, 'NVME', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, 'Gen 5', NULL, '2TB', NULL, NULL, NULL, '1499.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103086.png', 23, NULL, '2026-02-11 19:04:35', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 1, 'QV-PROD-CPU-0013', 7, 'PSU', 50, 8, 'GIGABYTE Aorus Elite AE850W', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, 'Platinum', '3.1', NULL, '5.1', NULL, NULL, 'White', NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159225.png', 43, NULL, '2026-02-11 19:12:30', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 1, 'QV-PROD-CPU-0014', 7, 'PSU', 51, 10, 'CORSAIR RM1000X Shift', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, 'Gold', '3.1', NULL, '5.1', NULL, NULL, 'White', NULL, '959.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159043.png', 26, NULL, '2026-02-11 19:12:30', '2026-02-26 18:24:03', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 1, 'QV-PROD-CPU-0015', 7, 'PSU', 52, 9, 'ASUS ROG Thor III 1200W', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, 'Platinum', '3.1', NULL, '5.1', NULL, NULL, 'Black', NULL, '2499.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772159078.png', 41, NULL, '2026-02-11 19:12:30', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (16, 1, 'QV-PROD-CPU-0016', 3, 'GPU', 40, 9, 'ASUS ROG Strix RTX 5070 Ti 16GB', 0, NULL, NULL, 'NVIDIA', NULL, NULL, NULL, '', NULL, '8GB', NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131755.png', 42, NULL, '2026-02-12 02:36:59', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (17, 1, 'QV-PROD-CPU-0017', 3, 'GPU', 42, 9, 'ASUS ROG Astral RTX 5090 32GB', 0, NULL, NULL, 'NVIDIA', NULL, NULL, NULL, '', NULL, '12GB', NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '19399.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131722.png', 45, NULL, '2026-02-12 02:36:59', '2026-02-26 10:48:42', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (18, 1, 'QV-PROD-CPU-0018', 3, 'GPU', 41, 11, 'MSI Trio X White RTX 5080 16GB', 0, NULL, NULL, 'NVIDIA', NULL, NULL, NULL, '', NULL, '16GB', NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '7099.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131106.png', 32, NULL, '2026-02-12 02:36:59', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 1, 'QV-PROD-CPU-0019', 3, 'GPU', 38, 11, 'MSI Ventus 2X OC PLUS RTX 5060 Ti 16GB', 0, NULL, NULL, 'AMD', NULL, NULL, NULL, '', NULL, '32GB', NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '2999.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772130424.png', 45, NULL, '2026-02-12 02:36:59', '2026-02-26 10:27:04', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 1, 'QV-PROD-CPU-0020', 11, 'AIO', 46, 14, 'ARCTIC Liquid Freezer III Pro ARGB 360', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', 'None', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '639.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131017.png', 34, NULL, '2026-02-12 02:45:27', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 1, 'QV-PROD-CPU-0021', 11, 'AIO', 46, 12, 'NZXT Kraken Elite 360', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', 'Screen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '0.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772103657.png', 46, NULL, '2026-02-12 02:45:27', '2026-02-26 03:00:59', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (22, 1, 'QV-PROD-CPU-0022', 11, 'AIO', 46, 13, 'LIAN LI Hydroshift ii LCD-C 360 Fanless', 0, NULL, NULL, NULL, NULL, NULL, NULL, '', 'Screen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '769.00', '2026-02-12 02:48:35', NULL, NULL, 1, NULL, '/backend/products/1772131504.png', 34, NULL, '2026-02-12 02:45:27', '2026-02-26 10:45:04', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (23, 1, 'QV-PROD-CPU-0023', 5, 'RAM', 28, 17, 'G.SKILL Trident Z5 Neo RGB DDR5 CL30 6000 (32GB X 2)', 0, NULL, NULL, NULL, NULL, 6000, NULL, 'CL30', NULL, NULL, NULL, NULL, 'DDR5', NULL, NULL, '2 x 32GB', 'White', NULL, '0.00', '2026-02-12 02:56:11', NULL, NULL, 1, NULL, '/backend/products/1772158970.png', 43, NULL, '2026-02-12 02:56:11', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (24, 1, 'QV-PROD-CPU-0024', 5, 'RAM', 28, 17, 'G.SKILL Trident Z5 Royal Neo GOLD RGB DDR5 CL26 6000 (32GB X 2)', 0, NULL, NULL, NULL, NULL, 6000, NULL, 'CL26', NULL, NULL, NULL, NULL, 'DDR5', NULL, NULL, '2 x 32GB', 'Gold', NULL, '0.00', '2026-02-12 02:56:11', NULL, NULL, 1, NULL, '/backend/products/1772158918.png', 37, NULL, '2026-02-12 02:56:11', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (25, 1, 'QV-PROD-CPU-0025', 5, 'RAM', 28, 17, 'G.SKILL Trident Z5 Royal Neo SILVER RGB DDR5 CL28 6000 (32GB X 2)', 0, NULL, NULL, NULL, NULL, 6000, NULL, 'CL28', NULL, NULL, NULL, NULL, 'DDR5', NULL, NULL, '2 x 32GB', 'White', NULL, '6109.00', '2026-02-12 02:56:11', NULL, NULL, 1, NULL, '/backend/products/1772158950.png', 13, NULL, '2026-02-12 02:56:11', '2026-02-26 18:22:30', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (26, 1, 'QV-PROD-CPU-0026', 9, 'CSE', 20, 12, 'NZXT H9 Elite', 0, NULL, NULL, NULL, 1, NULL, 'ITX, M-ATX, ATX, E-ATX', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '0.00', '2026-02-12 03:06:31', NULL, NULL, 1, NULL, '/backend/products/1772130298.png', 43, NULL, '2026-02-12 03:06:31', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (27, 1, 'QV-PROD-CPU-0027', 9, 'CSE', 20, 12, 'NZXT H9 Flow RGB', 0, NULL, NULL, NULL, 1, NULL, 'ITX, M-ATX, ATX, E-ATX', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '0.00', '2026-02-12 03:06:31', NULL, NULL, 1, NULL, '/backend/products/1772103870.png', 48, NULL, '2026-02-12 03:06:31', '2026-02-26 03:04:31', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (28, 1, 'QV-PROD-CPU-0028', 9, 'CSE', 20, 13, 'LIAN LI O11 Vision Compact', 0, NULL, NULL, NULL, 0, NULL, 'ITX, M-ATX, ATX, E-ATX, Back Connect', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '539.00', '2026-02-12 03:06:31', NULL, NULL, 1, NULL, '/backend/products/1772131463.png', 31, NULL, '2026-02-12 03:06:31', '2026-02-26 10:44:23', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (29, 1, 'QV-PROD-CPU-0029', 9, 'CSE', 19, 16, 'HAVN HS 420', 0, NULL, NULL, NULL, 0, NULL, 'ITX, M-ATX, ATX, E-ATX', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '899.00', '2026-02-12 03:06:31', NULL, NULL, 1, NULL, '/backend/products/1772159360.png', 36, NULL, '2026-02-12 03:06:31', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (30, 1, 'QV-PROD-CPU-0030', 9, 'CSE', 21, 15, 'JONSBO D31 Screen', 0, NULL, NULL, NULL, 0, NULL, 'ITX, M-ATX', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '439.00', '2026-02-12 03:06:31', NULL, NULL, 1, NULL, '/backend/products/1772131185.png', 48, NULL, '2026-02-12 03:06:31', '2026-02-26 10:39:45', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (31, 0, 'QV-PROD-CPU-0031', 10, 'FAN', 22, 14, 'ARCTIC P12 PWM', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'None', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'Black', NULL, '0.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130982.png', 50, NULL, '2026-02-12 03:23:12', '2026-02-26 10:36:22', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (32, 0, 'QV-PROD-CPU-0032', 10, 'FAN', 22, 12, 'NZXT F360', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RGB, 3x', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'White', NULL, '598.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130370.png', 41, NULL, '2026-02-12 03:23:12', '2026-02-26 10:26:10', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (33, 0, 'QV-PROD-CPU-0033', 10, 'FAN', 22, 12, 'NZXT F120', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'None', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'White', NULL, '0.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130392.png', 48, NULL, '2026-02-12 03:23:12', '2026-02-26 10:26:32', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (34, 0, 'QV-PROD-CPU-0034', 10, 'FAN', 22, 13, 'LIAN LI SL INF 120', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RGB', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'White', NULL, '199.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130933.png', 35, NULL, '2026-02-12 03:23:12', '2026-07-09 14:17:47', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (35, 0, 'QV-PROD-CPU-0035', 10, 'FAN', 24, 13, 'LIAN LI SL INF REV 120', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'RGB', NULL, NULL, NULL, NULL, NULL, NULL, '24', 'White', NULL, '199.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772159471.png', 36, NULL, '2026-02-12 03:23:12', '2026-02-26 18:31:11', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (36, 0, 'QV-PROD-CPU-0036', 10, 'FAN', 22, 13, 'LIAN LI TL LCD 120', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Screen, RGB', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'White', NULL, '269.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130903.png', 50, NULL, '2026-02-12 03:23:12', '2026-02-26 10:35:03', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (37, 0, 'QV-PROD-CPU-0037', 10, 'FAN', 22, 13, 'LIAN LI TL LCD REV 120', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Screen, RGB', NULL, NULL, NULL, NULL, NULL, NULL, '22', 'White', NULL, '269.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772159443.png', 46, NULL, '2026-02-12 03:23:12', '2026-02-26 18:30:43', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (38, 0, 'QV-PROD-CPU-0038', 10, 'FAN', 23, 13, 'LIAN LI TL LCD 140', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Screen, RGB', NULL, NULL, NULL, NULL, NULL, NULL, '23', 'White', NULL, '0.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772130678.png', 32, NULL, '2026-02-12 03:23:12', '2026-07-09 15:36:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (39, 0, 'QV-PROD-CPU-0039', 10, 'FAN', 23, 13, 'LIAN LI TL LCD REV 140', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Screen, RGB', NULL, NULL, NULL, NULL, NULL, NULL, '23', 'White', NULL, '0.00', '2026-02-12 03:23:12', NULL, NULL, 1, NULL, '/backend/products/1772159425.png', 46, NULL, '2026-02-12 03:23:12', '2026-02-26 18:30:25', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (40, 0, 'QV-PROD-CPU-0040', 12, 'ACC', 31, 9, 'ASUS ROG Wingwall', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Black', NULL, '0.00', '2026-02-12 03:26:48', NULL, NULL, 1, NULL, '/backend/products/1772130575.png', 48, NULL, '2026-02-12 03:26:48', '2026-02-26 10:29:35', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (41, 0, 'QV-PROD-CPU-0041', 12, 'ACC', 33, 13, 'LIAN LI L- Wireless Controller', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '95.00', '2026-02-12 03:26:48', NULL, NULL, 1, NULL, '/backend/products/1772130539.png', 42, NULL, '2026-02-12 03:26:48', '2026-02-26 10:28:59', NULL);
INSERT INTO `products` (`id`, `is_care`, `product_code`, `cat_id`, `category_name`, `sub_cat_id`, `brand_id`, `product_name`, `core`, `threads`, `max_usage`, `type`, `include_fans`, `frequency`, `support`, `latency`, `additional`, `vram`, `80_plus`, `atx`, `gen`, `pcie`, `storage`, `size`, `colour`, `back_connect`, `price`, `price_updated_at`, `available`, `available_local`, `supplier_id`, `buying_date`, `image`, `product_qty`, `product_loan`, `created_at`, `updated_at`, `deleted_at`) VALUES (42, 0, 'QV-PROD-CPU-0042', 12, 'ACC', 32, 13, 'LIAN LI Edge Hub', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'White', NULL, '89.00', '2026-02-12 03:26:48', NULL, NULL, 1, NULL, '/backend/products/1772130511.png', 37, NULL, '2026-02-12 03:26:48', '2026-02-26 10:28:31', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `serve_bek` (`id`, `serve_bek_id`, `serve_data_id`, `date_start`, `one_year_assembly_warranty`, `one_free_onsite_troubleshooting_first_3_months`, `one_free_onsite_troubleshooting_claim_1`, `one_free_onsite_troubleshooting_claim_1_date`, `one_basic_cable_management_3_months`, `one_basic_cable_management_claim_1`, `one_basic_cable_management_claim_1_date`, `fifty_percent_off_dust_cleaning_first_year`, `fifty_percent_off_dust_cleaning_claim_1`, `fifty_percent_off_dust_cleaning_claim_1_date`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'BEK-2304-0001', '27', '2026-06-15', 1, 1, 1, '2026-07-05', 1, 1, '2026-07-10', 1, 0, NULL, '2026-07-12 11:56:01', '2026-07-12 12:26:32', NULL);
INSERT INTO `serve_bek` (`id`, `serve_bek_id`, `serve_data_id`, `date_start`, `one_year_assembly_warranty`, `one_free_onsite_troubleshooting_first_3_months`, `one_free_onsite_troubleshooting_claim_1`, `one_free_onsite_troubleshooting_claim_1_date`, `one_basic_cable_management_3_months`, `one_basic_cable_management_claim_1`, `one_basic_cable_management_claim_1_date`, `fifty_percent_off_dust_cleaning_first_year`, `fifty_percent_off_dust_cleaning_claim_1`, `fifty_percent_off_dust_cleaning_claim_1_date`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'BEK-2304-0002', '28', '2026-06-20', 1, 1, 1, '2026-07-12', 1, 1, '2026-07-12', 1, 0, NULL, '2026-07-12 12:24:24', '2026-07-12 14:18:33', NULL);
INSERT INTO `serve_bek` (`id`, `serve_bek_id`, `serve_data_id`, `date_start`, `one_year_assembly_warranty`, `one_free_onsite_troubleshooting_first_3_months`, `one_free_onsite_troubleshooting_claim_1`, `one_free_onsite_troubleshooting_claim_1_date`, `one_basic_cable_management_3_months`, `one_basic_cable_management_claim_1`, `one_basic_cable_management_claim_1_date`, `fifty_percent_off_dust_cleaning_first_year`, `fifty_percent_off_dust_cleaning_claim_1`, `fifty_percent_off_dust_cleaning_claim_1_date`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'BEK-2304-0003', '29', '2026-07-01', 1, 1, 0, NULL, 1, 0, NULL, 1, 1, '2026-07-12', '2026-07-12 12:24:24', '2026-07-12 14:42:41', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'QV-SRV-0001', 'PCE-2610-0001', 19, 2, 3, 0, NULL, NULL, 0, NULL, '2026-03-21 05:11:40', '2026-07-10 12:21:49', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'QV-SRV-0002', 'PCE-2610-0002', 4, 1, 3, 0, NULL, NULL, 0, NULL, '2026-03-21 05:12:20', '2026-07-10 12:21:49', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 'QV-SRV-0003', 'PCE-2610-0003', 20, 3, 3, 1, '2026-07-09 14:34:00', 1783607692, 1, 'Current Tier: Collectorâs Edition (RM400.00)

Customer shows high engagement potential. Recommend premium package upgrade with additional features.', '2026-07-09 14:21:57', '2026-07-10 12:21:49', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (23, 'QV-SRV-0004', 'PCE-2610-0004', 21, 6, 3, 1, '2026-07-09 15:47:00', 1783612136, 1, 'Current Tier: Collectorâs Edition (RM400.00)

Customer feedback positive. Recommend adding support for additional users/teams.', '2026-07-09 15:44:53', '2026-07-10 12:21:49', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (24, 'QV-SRV-0005', 'MPS-0407-0001', 20, 4, 2, 0, NULL, NULL, 0, NULL, '2026-07-12 11:40:41', '2026-07-12 11:40:41', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (27, 'QV-SRV-0006', 'BEK-2304-0001', 20, 5, 1, 0, NULL, NULL, 0, NULL, '2026-07-12 11:56:01', '2026-07-12 11:56:01', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (28, 'QV-SRV-0007', 'BEK-2304-0002', 20, 8, 1, 0, NULL, NULL, 0, NULL, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (29, 'QV-SRV-0008', 'BEK-2304-0003', 20, 9, 1, 0, NULL, NULL, 0, NULL, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (30, 'QV-SRV-0009', 'MPS-0407-0002', 20, 10, 2, 0, NULL, NULL, 0, NULL, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);
INSERT INTO `serve_data` (`id`, `serve_id`, `qvse_cid`, `customer_id`, `order_id`, `lkp_serve_id`, `start_serve_enabled`, `start_serve_date`, `start_serve_timestamp`, `upgrade_pce_enabled`, `upgrade_pce_notes`, `created_at`, `updated_at`, `deleted_at`) VALUES (31, 'QV-SRV-0010', 'MPS-0407-0003', 20, 11, 2, 0, NULL, NULL, 0, NULL, '2026-07-12 12:24:24', '2026-07-12 12:24:24', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `serve_mps` (`id`, `serve_mps_id`, `serve_data_id`, `date_start`, `two_year_assembly_warranty`, `two_free_onsite_troubleshooting_first_6_months`, `two_free_onsite_troubleshooting_claim_1`, `two_free_onsite_troubleshooting_claim_1_date`, `two_free_onsite_troubleshooting_claim_2`, `two_free_onsite_troubleshooting_claim_2_date`, `two_advance_cable_management_first_year`, `two_advance_cable_management_claim_1`, `two_advance_cable_management_claim_1_date`, `two_advance_cable_management_claim_2`, `two_advance_cable_management_claim_2_date`, `one_free_dust_cleaning_first_year`, `one_free_dust_cleaning_claim`, `one_free_dust_cleaning_claim_date`, `fifty_percent_off_dust_cleaning_second_year`, `fifty_percent_off_dust_cleaning_claim_date`, `thirty_percent_off_labour_fees_upgrade_first_year`, `thirty_percent_off_labour_fees_claim_date`, `thirty_percent_off_dust_cleaning`, `thirty_percent_off_dust_cleaning_claim_date`, `rm100_promo_code_next_build`, `generate_code`, `rm100_promo_code_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'MPS-0407-0001', '24', '2026-05-10', 1, 1, 1, '2026-06-09', 0, NULL, 1, 1, '2026-06-14', 0, NULL, 1, 1, NULL, 1, NULL, 1, NULL, 0, NULL, 'MPS-6QDKP5HL', 1, 0, '2026-07-12 11:40:41', '2026-07-12 12:26:32', NULL);
INSERT INTO `serve_mps` (`id`, `serve_mps_id`, `serve_data_id`, `date_start`, `two_year_assembly_warranty`, `two_free_onsite_troubleshooting_first_6_months`, `two_free_onsite_troubleshooting_claim_1`, `two_free_onsite_troubleshooting_claim_1_date`, `two_free_onsite_troubleshooting_claim_2`, `two_free_onsite_troubleshooting_claim_2_date`, `two_advance_cable_management_first_year`, `two_advance_cable_management_claim_1`, `two_advance_cable_management_claim_1_date`, `two_advance_cable_management_claim_2`, `two_advance_cable_management_claim_2_date`, `one_free_dust_cleaning_first_year`, `one_free_dust_cleaning_claim`, `one_free_dust_cleaning_claim_date`, `fifty_percent_off_dust_cleaning_second_year`, `fifty_percent_off_dust_cleaning_claim_date`, `thirty_percent_off_labour_fees_upgrade_first_year`, `thirty_percent_off_labour_fees_claim_date`, `thirty_percent_off_dust_cleaning`, `thirty_percent_off_dust_cleaning_claim_date`, `rm100_promo_code_next_build`, `generate_code`, `rm100_promo_code_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'MPS-0407-0002', '30', '2026-07-14', 1, 1, 1, '2026-07-05', 1, '2026-09-03', 1, 1, '2026-07-10', 1, '2026-09-08', 0, 0, NULL, 0, NULL, 0, NULL, 0, NULL, 'MPS-NG072RZE', 1, 1, '2026-07-12 12:24:24', '2026-07-14 01:24:09', NULL);
INSERT INTO `serve_mps` (`id`, `serve_mps_id`, `serve_data_id`, `date_start`, `two_year_assembly_warranty`, `two_free_onsite_troubleshooting_first_6_months`, `two_free_onsite_troubleshooting_claim_1`, `two_free_onsite_troubleshooting_claim_1_date`, `two_free_onsite_troubleshooting_claim_2`, `two_free_onsite_troubleshooting_claim_2_date`, `two_advance_cable_management_first_year`, `two_advance_cable_management_claim_1`, `two_advance_cable_management_claim_1_date`, `two_advance_cable_management_claim_2`, `two_advance_cable_management_claim_2_date`, `one_free_dust_cleaning_first_year`, `one_free_dust_cleaning_claim`, `one_free_dust_cleaning_claim_date`, `fifty_percent_off_dust_cleaning_second_year`, `fifty_percent_off_dust_cleaning_claim_date`, `thirty_percent_off_labour_fees_upgrade_first_year`, `thirty_percent_off_labour_fees_claim_date`, `thirty_percent_off_dust_cleaning`, `thirty_percent_off_dust_cleaning_claim_date`, `rm100_promo_code_next_build`, `generate_code`, `rm100_promo_code_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'MPS-0407-0003', '31', '2026-07-05', 1, 1, 0, NULL, 0, NULL, 1, 0, NULL, 0, NULL, 1, 0, NULL, 1, NULL, 1, NULL, 0, NULL, 'MPS-NRPCT4QS', 1, 0, '2026-07-12 12:24:24', '2026-07-12 12:26:32', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

INSERT INTO `serve_pce` (`id`, `serve_pce_id`, `serve_data_id`, `date_start`, `three_year_warranty`, `unlimited_troubleshooting`, `troubleshooting`, `cable_management`, `cable_management_claim1`, `cable_management_claim1_date`, `cable_management_claim2`, `cable_management_claim2_date`, `cable_management_claim3`, `cable_management_claim3_date`, `cable_management_claim4`, `cable_management_claim4_date`, `annual_dust_cleaning`, `annual_dust_cleaning_year1`, `claim_date_year1`, `annual_dust_cleaning_year2`, `claim_date_year2`, `annual_dust_cleaning_year3`, `claim_date_year3`, `dust_cleaning_50_description`, `dust_cleaning_50_year4`, `dust_cleaning_50_claim_date_year4`, `dust_cleaning_50_year5`, `dust_cleaning_50_claim_date_year5`, `dust_cleaning_50_year6`, `dust_cleaning_50_claim_date_year6`, `dust_cleaning_50_year7`, `dust_cleaning_50_claim_date_year7`, `upgrade_service_50_description`, `upgrade_service_50_year1`, `upgrade_service_50_claim_date_year1`, `upgrade_service_50_year2`, `upgrade_service_50_claim_date_year2`, `upgrade_service_50_year3`, `upgrade_service_50_claim_date_year3`, `upgrade_service_30_description`, `upgrade_service_30_year4`, `upgrade_service_30_claim_date_year4`, `upgrade_service_30_year5`, `upgrade_service_30_claim_date_year5`, `upgrade_service_30_year6`, `upgrade_service_30_claim_date_year6`, `upgrade_service_30_year7`, `upgrade_service_30_claim_date_year7`, `promo_code`, `generate_code`, `promo_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'PCE-2610-0001', 7, '2026-01-15', 'Yes', 'Yes', 'Yes', 'Premium Cable Management', 1, '2026-03-15', 1, '2026-07-16', 1, '2027-05-01', 1, NULL, 'Free Annual Deep Cleaning', 1, '2026-11-11', 1, '2026-07-22', 0, NULL, '50% Off Annual Dust Cleaning', 0, NULL, 0, NULL, 0, NULL, 0, NULL, '50% Off Annual Upgrade Service', 0, NULL, 0, NULL, 0, NULL, '30% Off Annual Upgrade Service', 0, NULL, 0, NULL, 0, NULL, 0, NULL, NULL, 0, 0, '2026-07-12 15:21:08', '2026-07-12 15:21:08', NULL);
INSERT INTO `serve_pce` (`id`, `serve_pce_id`, `serve_data_id`, `date_start`, `three_year_warranty`, `unlimited_troubleshooting`, `troubleshooting`, `cable_management`, `cable_management_claim1`, `cable_management_claim1_date`, `cable_management_claim2`, `cable_management_claim2_date`, `cable_management_claim3`, `cable_management_claim3_date`, `cable_management_claim4`, `cable_management_claim4_date`, `annual_dust_cleaning`, `annual_dust_cleaning_year1`, `claim_date_year1`, `annual_dust_cleaning_year2`, `claim_date_year2`, `annual_dust_cleaning_year3`, `claim_date_year3`, `dust_cleaning_50_description`, `dust_cleaning_50_year4`, `dust_cleaning_50_claim_date_year4`, `dust_cleaning_50_year5`, `dust_cleaning_50_claim_date_year5`, `dust_cleaning_50_year6`, `dust_cleaning_50_claim_date_year6`, `dust_cleaning_50_year7`, `dust_cleaning_50_claim_date_year7`, `upgrade_service_50_description`, `upgrade_service_50_year1`, `upgrade_service_50_claim_date_year1`, `upgrade_service_50_year2`, `upgrade_service_50_claim_date_year2`, `upgrade_service_50_year3`, `upgrade_service_50_claim_date_year3`, `upgrade_service_30_description`, `upgrade_service_30_year4`, `upgrade_service_30_claim_date_year4`, `upgrade_service_30_year5`, `upgrade_service_30_claim_date_year5`, `upgrade_service_30_year6`, `upgrade_service_30_claim_date_year6`, `upgrade_service_30_year7`, `upgrade_service_30_claim_date_year7`, `promo_code`, `generate_code`, `promo_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'PCE-2610-0002', 8, '2026-02-20', 'Yes', 'Yes', 'Yes', 'Premium Cable Management', 1, '2026-04-21', 0, NULL, NULL, NULL, NULL, NULL, 'Free Annual Deep Cleaning', 1, '2026-12-17', NULL, NULL, NULL, NULL, '50% Off Annual Dust Cleaning', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, '30% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PCE-BM3J9I6K', 1, 1, '2026-07-12 12:26:32', '2026-07-12 12:26:32', NULL);
INSERT INTO `serve_pce` (`id`, `serve_pce_id`, `serve_data_id`, `date_start`, `three_year_warranty`, `unlimited_troubleshooting`, `troubleshooting`, `cable_management`, `cable_management_claim1`, `cable_management_claim1_date`, `cable_management_claim2`, `cable_management_claim2_date`, `cable_management_claim3`, `cable_management_claim3_date`, `cable_management_claim4`, `cable_management_claim4_date`, `annual_dust_cleaning`, `annual_dust_cleaning_year1`, `claim_date_year1`, `annual_dust_cleaning_year2`, `claim_date_year2`, `annual_dust_cleaning_year3`, `claim_date_year3`, `dust_cleaning_50_description`, `dust_cleaning_50_year4`, `dust_cleaning_50_claim_date_year4`, `dust_cleaning_50_year5`, `dust_cleaning_50_claim_date_year5`, `dust_cleaning_50_year6`, `dust_cleaning_50_claim_date_year6`, `dust_cleaning_50_year7`, `dust_cleaning_50_claim_date_year7`, `upgrade_service_50_description`, `upgrade_service_50_year1`, `upgrade_service_50_claim_date_year1`, `upgrade_service_50_year2`, `upgrade_service_50_claim_date_year2`, `upgrade_service_50_year3`, `upgrade_service_50_claim_date_year3`, `upgrade_service_30_description`, `upgrade_service_30_year4`, `upgrade_service_30_claim_date_year4`, `upgrade_service_30_year5`, `upgrade_service_30_claim_date_year5`, `upgrade_service_30_year6`, `upgrade_service_30_claim_date_year6`, `upgrade_service_30_year7`, `upgrade_service_30_claim_date_year7`, `promo_code`, `generate_code`, `promo_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'PCE-2610-0003', 13, '2026-07-09', 'Yes', 'Yes', 'Yes', 'Premium Cable Management', 0, NULL, 0, NULL, NULL, '2027-07-15', NULL, '2028-01-19', 'Free Annual Deep Cleaning', 0, NULL, 0, NULL, 0, NULL, '50% Off Annual Dust Cleaning', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, '30% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PCE-805R39V2', 1, 0, '2026-07-12 12:26:32', '2026-07-12 12:26:32', NULL);
INSERT INTO `serve_pce` (`id`, `serve_pce_id`, `serve_data_id`, `date_start`, `three_year_warranty`, `unlimited_troubleshooting`, `troubleshooting`, `cable_management`, `cable_management_claim1`, `cable_management_claim1_date`, `cable_management_claim2`, `cable_management_claim2_date`, `cable_management_claim3`, `cable_management_claim3_date`, `cable_management_claim4`, `cable_management_claim4_date`, `annual_dust_cleaning`, `annual_dust_cleaning_year1`, `claim_date_year1`, `annual_dust_cleaning_year2`, `claim_date_year2`, `annual_dust_cleaning_year3`, `claim_date_year3`, `dust_cleaning_50_description`, `dust_cleaning_50_year4`, `dust_cleaning_50_claim_date_year4`, `dust_cleaning_50_year5`, `dust_cleaning_50_claim_date_year5`, `dust_cleaning_50_year6`, `dust_cleaning_50_claim_date_year6`, `dust_cleaning_50_year7`, `dust_cleaning_50_claim_date_year7`, `upgrade_service_50_description`, `upgrade_service_50_year1`, `upgrade_service_50_claim_date_year1`, `upgrade_service_50_year2`, `upgrade_service_50_claim_date_year2`, `upgrade_service_50_year3`, `upgrade_service_50_claim_date_year3`, `upgrade_service_30_description`, `upgrade_service_30_year4`, `upgrade_service_30_claim_date_year4`, `upgrade_service_30_year5`, `upgrade_service_30_claim_date_year5`, `upgrade_service_30_year6`, `upgrade_service_30_claim_date_year6`, `upgrade_service_30_year7`, `upgrade_service_30_claim_date_year7`, `promo_code`, `generate_code`, `promo_claim`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'PCE-2610-0004', 23, '2026-04-10', 'Yes', 'Yes', 'Yes', 'Premium Cable Management', 1, '2026-06-09', 0, NULL, NULL, NULL, NULL, NULL, 'Free Annual Deep Cleaning', 1, '2027-02-04', NULL, NULL, NULL, NULL, '50% Off Annual Dust Cleaning', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '50% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, '30% Off Annual Upgrade Service', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'PCE-1RZV7LU0', 1, 1, '2026-07-12 12:26:32', '2026-07-12 12:26:32', NULL);

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

INSERT INTO `serves` (`id`, `name`, `code`, `colour`, `fee`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'Essential Kit', 'BEK-2304', '#BF40BF', '0.00', '### **Eligibility:**
`Total Build Price < RM 7,000`
### **Customer-Facing ID Format:**
BEK-2304-XXXX
### **Perks:**
* 1-year assembly warranty
* 1Ã onsite troubleshoot (within 90 days)
* 1Ã basic cable refresh
* Remote support: 3â5 working days
* 50% off 1Ã dust cleaning (Year 1)', '2025-12-30 02:27:06', '2025-12-31 18:28:01', NULL);
INSERT INTO `serves` (`id`, `name`, `code`, `colour`, `fee`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'Prime Series', 'MPS-0407', '#FFFFFF', '200.00', '### **Eligibility:**
`RM 7,000 - RM 9,999`
### **Customer-Facing ID Format:**
MPS-0407-XXXX
### **Perks:**
* 2-year assembly warranty
* 2Ã onsite troubleshoot sessions (within 6 months)
* 2Ã advanced cable refresh
* 1Ã free cleaning (Year 1), 50% off next year
* 30% off upgrade labor (Year 1)
* RM100 promo code
* Merch discounts', '2025-12-30 02:49:27', '2026-07-10 17:41:51', NULL);
INSERT INTO `serves` (`id`, `name`, `code`, `colour`, `fee`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'Collector\'s Edition', 'PCE-2610', '#FFD700', '400.00', '### **Eligibility:**
`>= RM 10,000`
### **Customer-Facing ID Format:**
PCE-2610-XXXX
### **Perks:**
* 3 years unlimited troubleshooting
* Next 7 years = 50% off troubleshooting
* 4Ã premium cable refresh (first 2 years)
* Free annual cleaning (first 3 years)
* Premium merch discounts
* RM200 promo code
* Express Lab access
* Optional upgrade:
  **Collector + Carbon Fiber Keychain = RM469.90**
  (only for Collector customers)', '2025-12-30 02:59:49', '2026-07-10 17:41:36', NULL);

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

INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 1, 'AM4', 'AM4', '2025-12-30 02:27:06', '2026-01-03 21:49:00', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 1, 'AM5', 'AM5', '2025-12-30 02:49:27', '2026-01-03 21:49:08', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 1, 'LGA 1700', 'LGA 1700', '2025-12-30 02:59:49', '2026-01-03 22:34:45', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 1, 'LGA 1851', 'LGA 1851', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 3, 'RTX 5050', 'RTX 5050', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 6, 'B550', 'B550', '2026-01-10 08:58:09', '2026-01-10 09:52:55', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 6, 'X570', 'X570', '2026-01-10 09:40:12', '2026-01-10 09:53:05', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 6, 'B650', 'B650', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 6, 'B850', 'B850', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 6, 'X670', 'X670', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 6, 'X870', 'X870', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 6, 'Z690', 'Z690', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (13, 6, 'Z790', 'Z790', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (14, 6, 'Z890', 'Z890', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (15, 6, 'B860', 'B860', '2026-02-11 07:41:18', '2026-02-11 07:41:18', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (18, 9, 'SFF', 'SFF', '2026-02-11 15:52:48', '2026-02-11 15:52:48', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (19, 9, 'Micro Tower', 'Micro Tower', '2026-02-11 15:52:48', '2026-02-11 15:52:48', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (20, 9, 'Mid Tower', 'Mid Tower', '2026-02-11 15:52:48', '2026-02-11 15:52:48', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (21, 9, 'Full Tower', 'Full Tower', '2026-02-11 15:52:48', '2026-02-11 15:52:48', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (22, 10, '120mm', '120mm', '2026-02-11 15:56:01', '2026-02-11 15:56:01', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (23, 10, '140mm', '140mm', '2026-02-11 15:56:01', '2026-02-11 15:56:01', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (24, 10, 'Reverse 120mm', 'Reverse 120mm', '2026-02-11 15:56:01', '2026-02-11 15:56:01', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (25, 10, 'Reverse 140mm', 'Reverse 140mm', '2026-02-11 15:56:32', '2026-02-11 15:56:32', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (26, 5, '2 x 16GB', '2 x 16GB', '2026-02-11 16:03:43', '2026-02-11 16:03:43', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (27, 5, '2 x 24GB', '2 x 24GB', '2026-02-11 16:03:43', '2026-02-11 16:03:43', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (28, 5, '2 x 32GB', '2 x 32GB', '2026-02-11 16:03:43', '2026-02-11 16:03:43', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (29, 5, '2 x 48GB', '2 x 48GB', '2026-02-11 16:03:43', '2026-02-11 16:03:43', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (30, 5, '2 x 64GB', '2 x 64GB', '2026-02-11 16:03:43', '2026-02-11 16:03:43', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (31, 12, 'GPU Stand', 'GPU Stand', '2026-02-11 16:05:12', '2026-02-11 16:05:12', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (32, 12, 'HUB', 'HUB', '2026-02-11 16:05:12', '2026-02-11 16:05:12', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (33, 12, 'Controller', 'Controller', '2026-02-11 16:05:12', '2026-02-11 16:05:12', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (34, 10, 'SFF', 'SFF', '2026-02-11 16:07:16', '2026-02-11 16:07:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (35, 10, 'Single Tower', 'Single Tower', '2026-02-11 16:07:16', '2026-02-11 16:07:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (36, 10, 'Dual Tower', 'Dual Tower', '2026-02-11 16:07:16', '2026-02-11 16:07:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (37, 3, 'RTX 5060', 'RTX 5060', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (38, 3, 'RTX 5060 Ti', 'RTX 5060 Ti', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (39, 3, 'RTX 5070', 'RTX 5070', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (40, 3, 'RTX 5070 Ti', 'RTX 5070 Ti', '2026-01-03 21:49:40', '2026-01-03 21:49:40', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (41, 3, 'RTX 5080', 'RTX 5080', '2026-02-11 16:11:16', '2026-02-11 16:11:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (42, 3, 'RTX 5090', 'RTX 5090', '2026-02-11 16:11:16', '2026-02-11 16:11:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (43, 3, 'RX 9060 XT', 'RX 9060 XT', '2026-02-11 16:11:16', '2026-02-11 16:11:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (44, 3, 'RX 9070 XT', 'RX 9070 XT', '2026-02-11 16:11:16', '2026-02-11 16:11:16', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (45, 9, '240mm', '240mm', '2026-02-11 16:13:52', '2026-02-11 16:13:52', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (46, 9, '360mm', '360mm', '2026-02-11 16:13:52', '2026-02-11 16:13:52', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (47, 9, '420mm', '420mm', '2026-02-11 16:13:52', '2026-02-11 16:13:52', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (48, 5, '650W', '650W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (49, 5, '750W', '750W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (50, 5, '850W', '850W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (51, 5, '1000W', '1000W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (52, 5, '1200W', '1200W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (53, 5, '1600W', '1600W', '2026-02-11 16:16:22', '2026-02-11 16:16:22', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (54, 2, '500MB', '500MB', '2026-02-11 16:19:47', '2026-02-11 16:19:47', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (55, 2, '1TB', '1TB', '2026-02-11 16:19:47', '2026-02-11 16:19:47', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (56, 2, '2TB', '2TB', '2026-02-11 16:19:47', '2026-02-11 16:19:47', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (57, 2, '4TB', '4TB', '2026-02-11 16:19:47', '2026-02-11 16:19:47', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (58, 2, '8TB', '8TB', '2026-02-11 16:19:47', '2026-02-11 16:19:47', NULL);
INSERT INTO `sub_categories` (`id`, `cat_id`, `name`, `code`, `created_at`, `updated_at`, `deleted_at`) VALUES (59, 123, 'BOX', 'BOX-1', '2026-03-11 13:12:47', '2026-03-11 13:12:47', NULL);

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

INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (1, 'QV-SUPP-0001', 'Magic Print', 'sales@imagemagic.com.my', '018-2388238', 'Malaysia', '/backend/suppliers/1767057503.png', 'Magic Print', '2026-03-18 06:45:39', '2026-03-17 22:45:39', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (2, 'QV-SUPP-0002', 'RaffleStag', 'sales@rafflestag.com.my', '017-8496166', 'Malaysia', '/backend/suppliers/1767057503.png', 'RaffleStag', '2026-03-18 06:46:27', '2026-03-17 22:46:27', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (3, 'QV-SUPP-0003', 'CamiSasca', 'sales@camincusa.com', '949-4520195', 'USA', '/backend/suppliers/1767057503.png', 'CamiSasca', '2026-03-18 06:46:05', '2026-03-17 22:46:05', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (4, 'QV-SUPP-0004', 'Popov Leather', 'custom@popovleather.com', '018-3341524', 'Canada', '/backend/suppliers/1767057503.png', 'Popov Leather', '2026-03-18 06:46:16', '2026-03-17 22:46:16', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (5, 'QV-SUPP-0005', 'AEIOU Studio', 'enquiry@uylprinter.com', '016-2632273', 'Malaysia', '/backend/suppliers/1767057503.png', 'AEIOU Studio', '2026-03-17 15:04:12', '2026-03-17 05:31:30', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (6, 'QV-SUPP-0006', '2S Packaging', 'info@2Spackaging.com', '012-2223202', 'Malaysia', '/backend/suppliers/1767057503.png', '2S Packaging', '2026-03-17 15:04:12', '2026-03-04 15:31:31', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (7, 'QV-SUPP-0007', 'HookandLoop', 'traceyt@hookandloop.com', NULL, 'USA', '/backend/suppliers/1767057503.png', 'HookandLoop', '2026-03-17 15:04:12', '2026-02-11 18:47:06', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (8, 'QV-SUPP-0008', 'BoardGameGeek Store', 'contact@boardgamegeekstore.com', NULL, 'USA', '/backend/suppliers/1767057503.png', 'BoardGameGeek Store', '2026-03-17 15:04:12', '2026-02-11 18:47:06', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (9, 'QV-SUPP-0009', 'BS Gift', 'contact@bsgifts.com.my', '017-8798548', 'Malaysia', '/backend/suppliers/1767057503.png', 'BS Gift', '2026-03-18 06:45:27', '2026-03-17 22:45:27', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (10, 'QV-SUPP-0010', 'Gift Market', 'hello@gifting.com.sg', '019-2643897', 'Singapore', '/backend/suppliers/1767057503.png', 'Gift Market', '2026-03-18 06:47:28', '2026-03-17 22:47:28', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (11, 'QV-SUPP-0011', 'Digikey', 'orders@t.digikey.com', NULL, 'USA', '/backend/suppliers/1767057503.png', 'Digikey', '2026-03-17 15:04:12', '2026-02-11 18:49:25', NULL);
INSERT INTO `suppliers` (`id`, `supplier_id`, `name`, `email`, `phone`, `address`, `photo`, `shopname`, `created_at`, `updated_at`, `deleted_at`) VALUES (12, 'QV-SUPP-0012', 'MDPC-X', 'contact@Cable-Sleeving.com', NULL, 'Germany', '/backend/suppliers/1767057503.png', 'MDPC-X', '2026-03-17 15:04:12', '2026-02-11 18:49:25', NULL);

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

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1, 'admin', 'admin@admin.com', NULL, '$2y$10$.A0YCAMkmd7ymLb94Vzfye88awFJPBytM4D/JdXsrQs18LqKRV3c6', NULL, '2025-12-26 03:23:11', '2025-12-26 03:23:11');

SET FOREIGN_KEY_CHECKS=1;