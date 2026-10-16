/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `adjust_machine_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `adjust_machine_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `adjust_logs_created_at_idx` (`created_at`),
  KEY `adjust_logs_user_id_idx` (`user_id`),
  KEY `adjust_logs_item_code_idx` (`item_code`),
  KEY `adjust_logs_pic_idx` (`pic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alc_pe_master_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alc_pe_master_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qad` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ukuran_label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alc_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci,
  `request_payload` json DEFAULT NULL,
  `response_payload` json DEFAULT NULL,
  `status_code` int DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'failed',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_logs_created_at_index` (`created_at`),
  KEY `api_logs_api_name_index` (`api_name`),
  KEY `api_logs_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asakai_corrective_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asakai_corrective_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asakai_id` bigint unsigned NOT NULL,
  `action` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_no` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asakai_corrective_actions_asakai_id_order_no_index` (`asakai_id`,`order_no`),
  CONSTRAINT `asakai_corrective_actions_asakai_id_foreign` FOREIGN KEY (`asakai_id`) REFERENCES `asakai_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asakai_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asakai_master` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `lot_shift` enum('Shift 1','Shift 2','Shift 3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `lot_date` date NOT NULL,
  `date_issue` date NOT NULL,
  `pokayoke` text COLLATE utf8mb4_unicode_ci,
  `verify` text COLLATE utf8mb4_unicode_ci,
  `fmea_cp` text COLLATE utf8mb4_unicode_ci,
  `std_work` text COLLATE utf8mb4_unicode_ci,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `audit_date` date DEFAULT NULL,
  `reply_date` date DEFAULT NULL,
  `status` enum('draft','submitted','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asakai_master_created_by_foreign` (`created_by`),
  KEY `asakai_master_updated_by_foreign` (`updated_by`),
  CONSTRAINT `asakai_master_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `asakai_master_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asakai_pic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asakai_pic` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asakai_id` bigint unsigned NOT NULL,
  `pic_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_no` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asakai_pic_asakai_id_order_no_index` (`asakai_id`,`order_no`),
  CONSTRAINT `asakai_pic_asakai_id_foreign` FOREIGN KEY (`asakai_id`) REFERENCES `asakai_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asakai_rca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asakai_rca` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asakai_id` bigint unsigned NOT NULL,
  `why_level` tinyint NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_no` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asakai_rca_asakai_id_why_level_index` (`asakai_id`,`why_level`),
  CONSTRAINT `asakai_rca_asakai_id_foreign` FOREIGN KEY (`asakai_id`) REFERENCES `asakai_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assembly_daily_processes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assembly_daily_processes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_date` date NOT NULL,
  `line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_packaging_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barcode_packaging_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `masterId` int NOT NULL,
  `noDokumen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `partNo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scantime` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `barcode_packaging_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `barcode_packaging_master` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `noDokumen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateScan` datetime NOT NULL,
  `tipeBarcode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bpm_so_number` (`so_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_department` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_department_branch_id_department_id_unique` (`branch_id`,`department_id`),
  KEY `branch_department_department_id_foreign` (`department_id`),
  CONSTRAINT `branch_department_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_department_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_forecast_divs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_forecast_divs` (
  `father_part` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `paired` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_forecast_finals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_forecast_finals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `completed` int DEFAULT NULL,
  `outstanding` int DEFAULT NULL,
  `department` int DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_forecast_sorteds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_forecast_sorteds` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_forecast_sums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_forecast_sums` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `departement` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_forecasts` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `quantity_next` int DEFAULT NULL,
  `total` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` int DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `cycle_time_raw` decimal(8,5) DEFAULT NULL,
  `cycle_time` decimal(8,5) DEFAULT NULL,
  `cavity` int DEFAULT NULL,
  `man_power` int DEFAULT NULL,
  `pair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_forecast_time` decimal(10,2) DEFAULT NULL,
  `counter_forecast` int DEFAULT NULL,
  `total_prior` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_line_capacities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_line_capacities` (
  `line_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departement` int DEFAULT NULL,
  `time_limit` decimal(8,2) DEFAULT NULL,
  `status` int DEFAULT NULL,
  PRIMARY KEY (`line_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_line_distributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_line_distributions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `line_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bom_level` int DEFAULT NULL,
  `priority` int DEFAULT NULL,
  `total_prior` int DEFAULT NULL,
  `order` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_line_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_line_summaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `department` int DEFAULT NULL,
  `line_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_quantity` int DEFAULT NULL,
  `work_day` decimal(5,2) DEFAULT NULL,
  `ready_time` int DEFAULT NULL,
  `efficiency` decimal(5,2) DEFAULT NULL,
  `max_capacity` int DEFAULT NULL,
  `capacity_req_hour` decimal(7,2) DEFAULT NULL,
  `capacity_req_percent` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cap_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cap_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `line_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mould_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forecast_qty` int DEFAULT NULL,
  `cycle_time` decimal(8,2) DEFAULT NULL,
  `production_time` decimal(10,2) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_barcode_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_barcode_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spk_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WFI',
  `shift` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_label` int NOT NULL,
  `end_label` int NOT NULL,
  `total_labels` int NOT NULL,
  `prod_date` date DEFAULT NULL,
  `operator` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `is_trial` tinyint(1) NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custom_barcode_logs_user_id_index` (`user_id`),
  KEY `custom_barcode_logs_item_code_index` (`item_code`),
  KEY `custom_barcode_logs_spk_number_index` (`spk_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `daily_item_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_item_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT NULL,
  `final_quantity` int DEFAULT NULL,
  `loss_package_quantity` int DEFAULT NULL,
  `actual_quantity` int NOT NULL,
  `shift` int NOT NULL,
  `start_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_date` date NOT NULL,
  `end_time` time NOT NULL,
  `schedule_date` date DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temporal_cycle_time` double DEFAULT NULL,
  `temporal_cavity` int DEFAULT NULL,
  `resin_usage` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_item_code_user_id_idx` (`user_id`),
  KEY `daily_item_code_item_code_idx` (`item_code`),
  KEY `daily_item_code_schedule_date_idx` (`schedule_date`),
  KEY `daily_item_code_user_schedule_done_idx` (`user_id`,`schedule_date`,`is_done`),
  KEY `dic_start_date_idx` (`start_date`),
  KEY `dic_start_date_user_idx` (`start_date`,`user_id`),
  KEY `dic_start_date_item_idx` (`start_date`,`item_code`),
  KEY `dic_start_date_shift_idx` (`start_date`,`shift`),
  KEY `daily_item_codes_branch_id_foreign` (`branch_id`),
  CONSTRAINT `daily_item_codes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_schedule_new`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_schedule_new` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `so_number` int DEFAULT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_delfilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_delfilter` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_delsum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_delsum` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `total_after` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_final`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_final` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_date` date DEFAULT NULL,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` int DEFAULT NULL,
  `delivery_qty` int DEFAULT NULL,
  `delivered` int DEFAULT NULL,
  `outstanding` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `balance` int DEFAULT NULL,
  `outstanding_stk` int DEFAULT NULL,
  `packaging_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `standar_pack` int DEFAULT NULL,
  `packaging_qty` int DEFAULT NULL,
  `doc_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colour` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_finalwip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_finalwip` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fglink_id` int DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outstanding_del` int DEFAULT NULL,
  `wip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wip_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` int DEFAULT NULL,
  `bom_level` int DEFAULT NULL,
  `bom_quantity` int DEFAULT NULL,
  `req_quantity` int DEFAULT NULL,
  `stock_wip` int DEFAULT NULL,
  `balance_wip` int DEFAULT NULL,
  `outstanding_wip` int DEFAULT NULL,
  `packaging_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `standar_pack` int DEFAULT NULL,
  `packaging_qty` int DEFAULT NULL,
  `doc_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_process_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_process_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `process_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_step` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'idle',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'idle',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delsched_process_logs_process_key_unique` (`process_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_solist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_solist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `so_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `so_qty` int DEFAULT NULL,
  `delivered_qty` int DEFAULT NULL,
  `row_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `total_after` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delsched_stockwip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delsched_stockwip` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `total_after` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dic_accessory_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dic_accessory_lots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dic_id` bigint unsigned NOT NULL,
  `accessory_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accessory_lot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dic_accessory_lots_dic_id_foreign` (`dic_id`),
  CONSTRAINT `dic_accessory_lots_dic_id_foreign` FOREIGN KEY (`dic_id`) REFERENCES `daily_item_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `files_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `first_piece_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `first_piece_inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paint_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thinner_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ink_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viscosity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cycle_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_results` json DEFAULT NULL,
  `overall_judgement` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NG',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `prepared_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prepared_at` timestamp NULL DEFAULT NULL,
  `checked_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `approved_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `first_piece_inspections_work_order_id_foreign` (`work_order_id`),
  CONSTRAINT `first_piece_inspections_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `sp_work_orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `holiday_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holiday_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `injection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `second_process` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assembly` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `moulding` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `half_day` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hourly_remarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hourly_remarks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dic_id` bigint unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `target` int NOT NULL,
  `actual` int NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `is_achieve` tinyint(1) NOT NULL DEFAULT '0',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pic_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic_3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `actual_production` int DEFAULT NULL,
  `NG` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hourly_remarks_dic_id_idx` (`dic_id`),
  KEY `hourly_remarks_created_at_idx` (`created_at`),
  KEY `hourly_remarks_pic_idx` (`pic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inv_line_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inv_line_list` (
  `line_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departement` int DEFAULT NULL,
  `daily_minutes` int DEFAULT NULL,
  `continue_running` int DEFAULT NULL,
  `jakarta` int DEFAULT NULL,
  `karawang` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipqc_check_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipqc_check_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipqc_inspection_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipqc_inspection_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inspection_id` bigint unsigned NOT NULL,
  `hour_ke` int NOT NULL,
  `appearance_checks` json DEFAULT NULL,
  `condition_checks` json DEFAULT NULL,
  `measurements` json DEFAULT NULL,
  `fitting_test` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tape_test_judgement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output_qty` int NOT NULL DEFAULT '0',
  `sample_qty` int NOT NULL DEFAULT '0',
  `reject_sample_qty` int NOT NULL DEFAULT '0',
  `reject_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `pass_qty` int NOT NULL DEFAULT '0',
  `reject_qty` int NOT NULL DEFAULT '0',
  `judgement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipqc_inspection_records_inspection_id_hour_ke_unique` (`inspection_id`,`hour_ke`),
  CONSTRAINT `ipqc_inspection_records_inspection_id_foreign` FOREIGN KEY (`inspection_id`) REFERENCES `ipqc_inspections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipqc_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipqc_inspections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `process_prod` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lot_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_glossy` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_viscosity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `std_oven_temp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_sample` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selected_measurements` json DEFAULT NULL,
  `total_output` int NOT NULL DEFAULT '0',
  `total_sample` int NOT NULL DEFAULT '0',
  `total_reject_sample` int NOT NULL DEFAULT '0',
  `total_reject_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_pass` int NOT NULL DEFAULT '0',
  `total_reject` int NOT NULL DEFAULT '0',
  `inspector_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checker_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overall_judgement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ongoing',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ipqc_unique_inspection` (`date`,`part_number`,`shift`,`unit_line`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ipqc_measurement_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ipqc_measurement_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `field_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `machine_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `machine_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `dic_id` bigint unsigned DEFAULT NULL,
  `shift` int DEFAULT NULL,
  `employee_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `machine_jobs_branch_id_foreign` (`branch_id`),
  CONSTRAINT `machine_jobs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_check_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_check_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `header_id` bigint unsigned NOT NULL,
  `item_id` bigint unsigned NOT NULL,
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_normal` tinyint(1) NOT NULL DEFAULT '1',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_check_details_header_id_foreign` (`header_id`),
  KEY `maintenance_check_details_item_id_foreign` (`item_id`),
  CONSTRAINT `maintenance_check_details_header_id_foreign` FOREIGN KEY (`header_id`) REFERENCES `maintenance_check_headers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_check_details_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `maintenance_check_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_check_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_check_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `machine_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `check_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prepared_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_check_headers_machine_id_date_unique` (`machine_id`,`date`),
  CONSTRAINT `maintenance_check_headers_machine_id_foreign` FOREIGN KEY (`machine_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_check_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_check_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kriteria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Predictive',
  `standard` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok_ng',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_machines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_machines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `tipe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mesin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kerusakan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perbaikan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lama_pengerjaan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_moulds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_moulds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `tipe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_kerusakan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perbaikan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lama_pengerjaan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_all_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_all_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bom_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_bom_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fg_id` bigint unsigned NOT NULL,
  `parent_item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode atasan langsung (immediate father)',
  `component_item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode part komponen ini',
  `component_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depth_level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'Kedalaman tingkat: 1=Lv1, 2=Lv2 (WIP), 3=Lv3 (WIP dlm WIP), dst',
  `item_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RAW_MATERIAL' COMMENT 'WIP, RESIN, CHEMICAL, PACKAGING, HARDWARE, RAW_MATERIAL',
  `is_wip` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'True jika butuh SPK sendiri (punya resep komponen lagi)',
  `unit_qty` decimal(16,6) NOT NULL DEFAULT '0.000000' COMMENT 'Kebutuhan per 1 unit parent langsungnya',
  `total_qty_per_fg` decimal(16,6) NOT NULL DEFAULT '0.000000' COMMENT 'Akumulasi kebutuhan total per 1 unit FG induk utama',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS',
  `lineage_path` text COLLATE utf8mb4_unicode_ci COMMENT 'Silsilah hierarki: FG > WIP_1 > WIP_2 > Component',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_bom_components_fg_id_is_wip_index` (`fg_id`,`is_wip`),
  KEY `master_bom_components_fg_id_depth_level_index` (`fg_id`,`depth_level`),
  KEY `master_bom_components_parent_item_index` (`parent_item`),
  KEY `master_bom_components_component_item_index` (`component_item`),
  KEY `master_bom_components_is_wip_index` (`is_wip`),
  KEY `master_bom_components_item_type_index` (`item_type`),
  CONSTRAINT `master_bom_components_fg_id_foreign` FOREIGN KEY (`fg_id`) REFERENCES `master_bom_fg_headers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bom_fg_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_bom_fg_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fg_item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode Part FG Resmi (e.g. D0B29100.)',
  `fg_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Deskripsi Part FG',
  `project_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama Project / Kategori SAP (e.g. SHAD TOP CASE, KULKAS SHARP)',
  `family` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family label dari Master BOM SAP',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Customer (e.g. SHAD, YAMAHA, HONDA)',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PCS' COMMENT 'Satuan FG (PCS, UNIT)',
  `total_wip_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Jumlah part WIP / sub-assembly di dalam FG ini',
  `total_raw_count` int unsigned NOT NULL DEFAULT '0' COMMENT 'Jumlah raw material / chemical / packaging',
  `max_depth_level` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'Tingkat kedalaman pohon terdalam (1, 2, 3, 4, ...)',
  `has_packaging` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Apakah memiliki komponen box/kemasan',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_bom_fg_headers_fg_item_code_unique` (`fg_item_code`),
  KEY `master_bom_fg_headers_project_code_index` (`project_code`),
  KEY `master_bom_fg_headers_customer_name_index` (`customer_name`),
  KEY `master_bom_fg_headers_is_active_index` (`is_active`),
  KEY `master_bom_fg_headers_family_index` (`family`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bom_material_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_bom_material_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'RAW_MATERIAL, RESIN, CHEMICAL, HARDWARE, PACKAGING',
  `updated_by` bigint unsigned DEFAULT NULL,
  `updated_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_bom_material_overrides_item_code_unique` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bom_verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_bom_verification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_at` timestamp NOT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_bom_verification_logs_parent_item_index` (`parent_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_bom_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_bom_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_at` timestamp NOT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_by_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_bom_verifications_parent_item_unique` (`parent_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_boms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_boms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sap_line_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Index/ID line dari SAP (#)',
  `parent_item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode FG atau WIP (Father Item)',
  `parent_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Deskripsi Parent Item',
  `component_item` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode Bahan/WIP Komponen (Child Item)',
  `component_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Deskripsi Komponen',
  `quantity` decimal(16,6) NOT NULL DEFAULT '0.000000' COMMENT 'Kebutuhan bahan per 1 unit Parent',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Satuan bahan (PCS, KG, LT, dll)',
  `family` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family label dari Excel SAP (Prioritas Kolom 1)',
  `family_2` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Family label cadangan dari Kolom 2 Excel',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Status aktif BOM',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_boms_parent_item_component_item_index` (`parent_item`,`component_item`),
  KEY `master_boms_parent_item_index` (`parent_item`),
  KEY `master_boms_component_item_index` (`component_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_business_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_business_partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bp_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bp_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VENDOR',
  `industry` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GENERAL',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foreign_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_employee` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_business_partners_bp_code_unique` (`bp_code`),
  KEY `master_business_partners_bp_name_index` (`bp_name`),
  KEY `master_business_partners_category_index` (`category`),
  KEY `master_business_partners_foreign_name_index` (`foreign_name`),
  KEY `master_business_partners_group_code_index` (`group_code`),
  KEY `master_business_partners_industry_index` (`industry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_customer_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_customer_delivery` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_customer_delivery_customer_code_unique` (`customer_code`),
  KEY `master_customer_delivery_customer_code_index` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_customer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_customer_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_customer_logs_user_id_foreign` (`user_id`),
  KEY `master_customer_logs_customer_code_index` (`customer_code`),
  CONSTRAINT `master_customer_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_data_rog_part_names`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_data_rog_part_names` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_delivery_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_delivery_schedule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `quantity` int NOT NULL,
  `so_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_delivery_schedule_customer_code_item_code_tanggal_index` (`customer_code`,`item_code`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_item_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_item_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_item_logs_user_id_foreign` (`user_id`),
  KEY `master_item_logs_item_code_index` (`item_code`),
  CONSTRAINT `master_item_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_item_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_item_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `standard_packaging` int DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_item_photos_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_list_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_list_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_mesin` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `standart_packaging_list` int NOT NULL,
  `setup_time_minute` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cavity` int NOT NULL DEFAULT '0',
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cycle_time` int DEFAULT NULL,
  `family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `part_weight` decimal(10,3) DEFAULT '0.000',
  `description_in_foreign_lang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `half_code_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `half_code_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `project_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_list_items_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_list_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_list_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` text COLLATE utf8mb4_unicode_ci,
  `preferred_supplier` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchasing_uom` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_list_materials_item_code_unique` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_zone` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mould_change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mould_change_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mould_logs_created_at_idx` (`created_at`),
  KEY `mould_logs_user_id_idx` (`user_id`),
  KEY `mould_logs_item_code_idx` (`item_code`),
  KEY `mould_logs_pic_idx` (`pic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_daily_audit_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_daily_audit_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `daily_audit_id` bigint unsigned NOT NULL,
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `position_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rack_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_lot_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KG',
  `actual_item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actual_qty` decimal(12,2) DEFAULT NULL,
  `diff_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discrepancy_reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mwh_daily_audit_items_daily_audit_id_foreign` (`daily_audit_id`),
  KEY `mwh_daily_audit_items_position_id_foreign` (`position_id`),
  KEY `mwh_daily_audit_items_pallet_id_index` (`pallet_id`),
  KEY `mwh_daily_audit_items_position_code_index` (`position_code`),
  KEY `mwh_daily_audit_items_system_item_code_index` (`system_item_code`),
  CONSTRAINT `mwh_daily_audit_items_daily_audit_id_foreign` FOREIGN KEY (`daily_audit_id`) REFERENCES `mwh_daily_audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mwh_daily_audit_items_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `mwh_positions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_daily_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_daily_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `audit_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `audit_date` date NOT NULL,
  `whse_id` bigint unsigned DEFAULT NULL,
  `auditor_id` bigint unsigned DEFAULT NULL,
  `auditor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_items` int NOT NULL DEFAULT '0',
  `matched_items` int NOT NULL DEFAULT '0',
  `discrepant_items` int NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_daily_audits_audit_code_unique` (`audit_code`),
  KEY `mwh_daily_audits_whse_id_foreign` (`whse_id`),
  KEY `mwh_daily_audits_auditor_id_foreign` (`auditor_id`),
  KEY `mwh_daily_audits_audit_date_whse_id_index` (`audit_date`,`whse_id`),
  CONSTRAINT `mwh_daily_audits_auditor_id_foreign` FOREIGN KEY (`auditor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mwh_daily_audits_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `mwh_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_incoming_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_incoming_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_id` bigint unsigned DEFAULT NULL,
  `document_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `incoming_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUPPLIER',
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `returned_from` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `po_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_outgoing_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_date` date NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_incoming_headers_document_no_unique` (`document_no`),
  KEY `mwh_incoming_headers_whse_id_foreign` (`whse_id`),
  CONSTRAINT `mwh_incoming_headers_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `mwh_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_outgoings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_outgoings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_id` bigint unsigned DEFAULT NULL,
  `outgoing_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qty_taken` decimal(12,2) NOT NULL DEFAULT '0.00',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KG',
  `outgoing_date` date NOT NULL,
  `issued_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_outgoings_outgoing_code_unique` (`outgoing_code`),
  KEY `mwh_outgoings_position_id_foreign` (`position_id`),
  KEY `mwh_outgoings_pallet_id_index` (`pallet_id`),
  KEY `mwh_outgoings_item_code_index` (`item_code`),
  KEY `mwh_outgoings_whse_id_foreign` (`whse_id`),
  CONSTRAINT `mwh_outgoings_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `mwh_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mwh_outgoings_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `mwh_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_pallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_pallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_id` bigint unsigned DEFAULT NULL,
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `incoming_header_id` bigint unsigned DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lot_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initial_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `current_qty` decimal(12,2) NOT NULL DEFAULT '0.00',
  `uom` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KG',
  `position_id` bigint unsigned DEFAULT NULL,
  `initial_position_id` bigint unsigned DEFAULT NULL,
  `status` enum('STORED','PARTIAL','EMPTY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STORED',
  `is_qc_hold` tinyint(1) NOT NULL DEFAULT '0',
  `qc_hold_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_pallets_pallet_id_unique` (`pallet_id`),
  KEY `mwh_pallets_incoming_header_id_foreign` (`incoming_header_id`),
  KEY `mwh_pallets_position_id_foreign` (`position_id`),
  KEY `mwh_pallets_item_code_index` (`item_code`),
  KEY `mwh_pallets_whse_id_foreign` (`whse_id`),
  KEY `mwh_pallets_initial_position_id_foreign` (`initial_position_id`),
  CONSTRAINT `mwh_pallets_incoming_header_id_foreign` FOREIGN KEY (`incoming_header_id`) REFERENCES `mwh_incoming_headers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mwh_pallets_initial_position_id_foreign` FOREIGN KEY (`initial_position_id`) REFERENCES `mwh_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mwh_pallets_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `mwh_positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mwh_pallets_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `mwh_warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rack_id` bigint unsigned NOT NULL,
  `level_no` int NOT NULL,
  `slot_no` int NOT NULL,
  `position_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slot_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMPTY',
  `last_item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_capacity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_positions_position_code_unique` (`position_code`),
  KEY `mwh_positions_rack_id_foreign` (`rack_id`),
  CONSTRAINT `mwh_positions_rack_id_foreign` FOREIGN KEY (`rack_id`) REFERENCES `mwh_racks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_racks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_racks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_id` bigint unsigned NOT NULL,
  `rack_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mwh_racks_whse_id_foreign` (`whse_id`),
  CONSTRAINT `mwh_racks_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `mwh_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mwh_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mwh_warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned DEFAULT NULL,
  `whse_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whse_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mwh_warehouses_whse_code_unique` (`whse_code`),
  KEY `mwh_warehouses_branch_id_foreign` (`branch_id`),
  CONSTRAINT `mwh_warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_recipients_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_types_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operator_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operator_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ppic_list_up_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ppic_list_up_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `list_up_id` bigint unsigned NOT NULL,
  `machine_id` bigint unsigned DEFAULT NULL,
  `machine_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_no` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spk_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cavity` int NOT NULL DEFAULT '1',
  `cycle_time` int NOT NULL DEFAULT '0',
  `target_per_hour` int NOT NULL DEFAULT '0',
  `operator_shift_1` int NOT NULL DEFAULT '0',
  `operator_shift_2` int NOT NULL DEFAULT '0',
  `operator_shift_3` int NOT NULL DEFAULT '0',
  `qty_to_run` int NOT NULL DEFAULT '0',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `is_generated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ppic_list_up_items_list_up_id_foreign` (`list_up_id`),
  KEY `ppic_list_up_items_machine_id_index` (`machine_id`),
  KEY `ppic_list_up_items_machine_name_index` (`machine_name`),
  KEY `ppic_list_up_items_part_no_index` (`part_no`),
  CONSTRAINT `ppic_list_up_items_list_up_id_foreign` FOREIGN KEY (`list_up_id`) REFERENCES `ppic_list_ups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ppic_list_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ppic_list_ups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `zone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ZONE A',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ppic_list_ups_date_index` (`date`),
  KEY `ppic_list_ups_zone_index` (`zone`),
  KEY `ppic_list_ups_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_bill_of_material_childs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_bill_of_material_childs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` double NOT NULL,
  `measure` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not Started',
  `action_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_bill_of_material_parents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_bill_of_material_parents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_broken_childs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_broken_childs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `child_id` int NOT NULL,
  `broken_quantity` int NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_list_all_master_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_list_all_master_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_type` int NOT NULL,
  `uom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_master_item_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_master_item_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_type` int NOT NULL,
  `type_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_material_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_material_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `child_id` int NOT NULL,
  `process_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scan_in` datetime DEFAULT NULL,
  `scan_start` datetime DEFAULT NULL,
  `scan_out` datetime DEFAULT NULL,
  `status` int NOT NULL DEFAULT '0',
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_draft` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prd_moulding_user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prd_moulding_user_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `material_log_id` int NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift` int NOT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jobs` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_ng_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_ng_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hourly_remark_id` int NOT NULL,
  `ng_type_id` int NOT NULL,
  `ng_quantity` int NOT NULL,
  `ng_remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ng_details_hourly_remark_id_idx` (`hourly_remark_id`),
  KEY `ng_details_ng_type_id_idx` (`ng_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_ng_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_ng_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ng_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_output_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_output_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dic_id` bigint unsigned NOT NULL,
  `operator_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `logged_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_output_logs_dic_id_foreign` (`dic_id`),
  CONSTRAINT `production_output_logs_dic_id_foreign` FOREIGN KEY (`dic_id`) REFERENCES `daily_item_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_payables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_payables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_number` bigint NOT NULL COMMENT 'Nomor dokumen utang dari SAP',
  `posting_date` date NOT NULL COMMENT 'Tanggal posting ke sistem',
  `value_date` date NOT NULL COMMENT 'Tanggal nilai untuk accounting',
  `item_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nomor material/item',
  `item_description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Deskripsi lengkap item/service',
  `quantity` int NOT NULL COMMENT 'Jumlah item',
  `remarks` text COLLATE utf8mb4_unicode_ci COMMENT 'Keterangan/catatan khusus',
  `status` enum('pending','received','invoiced','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Status pembayaran',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `uploaded_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User yang upload',
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_payables_document_number_unique` (`document_number`),
  KEY `production_payables_document_number_index` (`document_number`),
  KEY `production_payables_item_no_index` (`item_no`),
  KEY `production_payables_posting_date_index` (`posting_date`),
  KEY `production_payables_status_index` (`status`),
  KEY `production_payables_value_date_index` (`value_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `machine_id` bigint NOT NULL,
  `spk_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` int NOT NULL,
  `scanned` int NOT NULL,
  `outstanding` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `production_reports_spk_no_unique` (`spk_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_scanned_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_scanned_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spk_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dic_id` int NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT '0',
  `summary_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `psd_spk_label_unique` (`spk_code`,`label`),
  KEY `production_scanned_data_spk_code_index` (`spk_code`),
  KEY `production_scanned_data_item_code_index` (`item_code`),
  KEY `production_scanned_data_summary_id_index` (`summary_id`),
  KEY `production_scanned_data_dic_id_index` (`dic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `production_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `production_summary` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spk_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_quantity` int NOT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `sap_sent` tinyint(1) NOT NULL DEFAULT '0',
  `sap_sent_at` datetime DEFAULT NULL,
  `qc_status` tinyint NOT NULL DEFAULT '0',
  `created_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `production_summary_warehouse_index` (`warehouse`),
  KEY `production_summary_created_date_index` (`created_date`),
  KEY `production_summary_sap_sent_index` (`sap_sent`),
  KEY `production_summary_warehouse_created_date_sap_sent_index` (`warehouse`,`created_date`,`sap_sent`),
  KEY `production_summary_warehouse_created_date_index` (`warehouse`,`created_date`),
  KEY `production_summary_spk_code_index` (`spk_code`),
  KEY `production_summary_qc_status_index` (`qc_status`),
  KEY `ps_wh_sap_qc_date_idx` (`warehouse`,`sap_sent`,`qc_status`,`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qc_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qc_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attachable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qc_attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `qc_transfer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qc_transfer_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `production_summary_id` bigint unsigned NOT NULL,
  `scanned_data_id` bigint unsigned NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spk_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_qty` int NOT NULL,
  `ok_qty` int NOT NULL,
  `ng_qty` int NOT NULL,
  `ok_to_warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ok_sap_status` tinyint NOT NULL DEFAULT '0',
  `ok_sap_error` text COLLATE utf8mb4_unicode_ci,
  `ok_sap_sent_at` datetime DEFAULT NULL,
  `ng_to_warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ng_sap_status` tinyint NOT NULL DEFAULT '0',
  `ng_sap_error` text COLLATE utf8mb4_unicode_ci,
  `ng_sap_sent_at` datetime DEFAULT NULL,
  `inspected_by` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `qc_transfer_logs_production_summary_id_index` (`production_summary_id`),
  KEY `qc_transfer_logs_scanned_data_id_index` (`scanned_data_id`),
  KEY `qc_transfer_logs_ok_sap_status_ng_sap_status_index` (`ok_sap_status`,`ng_sap_status`),
  KEY `qctl_summary_scanned_idx` (`production_summary_id`,`scanned_data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `repair_machine_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_machine_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `problem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pic` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `finish_repair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions_configured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_bom_wip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_bom_wip` (
  `fg_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semi_first` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_first` int DEFAULT NULL,
  `semi_second` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_second` int DEFAULT NULL,
  `semi_third` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_third` int DEFAULT NULL,
  `level` int DEFAULT NULL,
  `item_group` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_delactual`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_delactual` (
  `item_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `so_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_delsched`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_delsched` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_qty` int NOT NULL,
  `so_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_delso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_delso` (
  `doc_num` int DEFAULT NULL,
  `doc_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `delivered_qty` int DEFAULT NULL,
  `line_num` int DEFAULT NULL,
  `row_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_fct_bom_wip`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_fct_bom_wip` (
  `fg_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semi_first` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semi_second` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semi_third` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` int DEFAULT NULL,
  `bom_quantity` int DEFAULT NULL,
  `item_group` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_fct_inventory_fgs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_fct_inventory_fgs` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_set_pps` int DEFAULT NULL,
  `setup_time` int DEFAULT NULL,
  `cycle_time` decimal(8,2) DEFAULT NULL,
  `cavity` int DEFAULT NULL,
  `safety_stock` int DEFAULT NULL,
  `daily_limit` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `total_spk` int DEFAULT NULL,
  `pair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `man_power` int DEFAULT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `process_owner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_code` int DEFAULT NULL,
  `special_condition` int DEFAULT NULL,
  `fg_code_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fg_code_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_percentage` int DEFAULT NULL,
  `continue_production` int DEFAULT NULL,
  `family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packaging` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bom_level` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_fct_lineproductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_fct_lineproductions` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_production` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_fg_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_fg_customers` (
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_forecast`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_forecast` (
  `forecast_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `forecast_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `forecast_date` date DEFAULT NULL,
  `quantity` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_inventory_fg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_inventory_fg` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_group` int DEFAULT NULL,
  `day_set_pps` int DEFAULT NULL,
  `setup_time` int DEFAULT NULL,
  `cycle_time` decimal(8,5) DEFAULT NULL,
  `cavity` int DEFAULT NULL,
  `safety_stock` int DEFAULT NULL,
  `daily_limit` int DEFAULT NULL,
  `stock` int DEFAULT NULL,
  `total_spk` int DEFAULT NULL,
  `production_min_qty` int DEFAULT NULL,
  `standar_packing` int DEFAULT NULL,
  `pair` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `man_power` int DEFAULT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `process_owner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_code` int DEFAULT NULL,
  `special_condition` int DEFAULT NULL,
  `fg_code_1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fg_code_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wip_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_percentage` int DEFAULT NULL,
  `continue_production` int DEFAULT NULL,
  `family` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_mould` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packaging` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bom_level` int DEFAULT NULL,
  PRIMARY KEY (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_inventory_mtr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_inventory_mtr` (
  `fg_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `material_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `material_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bom_quantity` decimal(12,5) DEFAULT NULL,
  `in_stock` decimal(12,5) DEFAULT NULL,
  `item_group` int DEFAULT NULL,
  `vendor_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_lineproduction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_lineproduction` (
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_production` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sap_reject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sap_reject` (
  `item_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `in_stock` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scanned_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scanned_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spk_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_scanned_data_doc_item` (`doc_num`,`item_code`),
  KEY `idx_scanned_data_doc_spk_label` (`doc_num`,`spk_code`,`label`),
  KEY `idx_scanned_data_spk_label` (`spk_code`,`label`),
  KEY `idx_scanned_data_doc_label` (`doc_num`,`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_hourly_productions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_hourly_productions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `hour_ke` int NOT NULL,
  `ok_qty` int NOT NULL DEFAULT '0',
  `ng_qty` int NOT NULL DEFAULT '0',
  `acumulasi_qty` int NOT NULL DEFAULT '0',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_hourly_productions_report_id_foreign` (`report_id`),
  CONSTRAINT `second_process_hourly_productions_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_manpowers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_manpowers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_manpowers_report_id_foreign` (`report_id`),
  CONSTRAINT `second_process_manpowers_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lot_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` decimal(12,4) DEFAULT NULL,
  `uom` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mixing_ratio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paint_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_materials_report_id_foreign` (`report_id`),
  CONSTRAINT `second_process_materials_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_ng_hourly_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_ng_hourly_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ng_record_id` bigint unsigned NOT NULL,
  `hour_ke` int NOT NULL,
  `qty` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_ng_hourly_details_ng_record_id_foreign` (`ng_record_id`),
  CONSTRAINT `second_process_ng_hourly_details_ng_record_id_foreign` FOREIGN KEY (`ng_record_id`) REFERENCES `second_process_ng_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_ng_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_ng_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `ng_category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ng_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_ng` int NOT NULL DEFAULT '0',
  `ng_input_item` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ng_input_qty` int DEFAULT NULL,
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_ng_records_report_id_foreign` (`report_id`),
  CONSTRAINT `second_process_ng_records_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sp_production_session_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `unit_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `output_destination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `process_prod` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_per_hour` int NOT NULL DEFAULT '0',
  `jml_input_wip` int NOT NULL DEFAULT '0',
  `repairan` int NOT NULL DEFAULT '0',
  `jumlah_output` int NOT NULL DEFAULT '0',
  `sisa_input` int NOT NULL DEFAULT '0',
  `jumlah_ok` int NOT NULL DEFAULT '0',
  `jumlah_ng` int NOT NULL DEFAULT '0',
  `ng_prosentase` decimal(5,2) NOT NULL DEFAULT '0.00',
  `jml_ng_lebur` int NOT NULL DEFAULT '0',
  `next_production_schedule` text COLLATE utf8mb4_unicode_ci,
  `absent_employees` text COLLATE utf8mb4_unicode_ci,
  `production_notes` text COLLATE utf8mb4_unicode_ci,
  `ng_remarks` text COLLATE utf8mb4_unicode_ci,
  `sisa_input_remark` text COLLATE utf8mb4_unicode_ci,
  `created_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_signed_at` timestamp NULL DEFAULT NULL,
  `pqc_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pqc_signed_at` timestamp NULL DEFAULT NULL,
  `leader_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leader_signed_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acknowledged_signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_reports_sp_production_session_id_foreign` (`sp_production_session_id`),
  CONSTRAINT `second_process_reports_sp_production_session_id_foreign` FOREIGN KEY (`sp_production_session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `second_process_troubles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `second_process_troubles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `masalah` text COLLATE utf8mb4_unicode_ci,
  `penyebab` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `penanganan` text COLLATE utf8mb4_unicode_ci,
  `loss_time_minutes` int NOT NULL DEFAULT '0',
  `loss_time` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `second_process_troubles_report_id_foreign` (`report_id`),
  CONSTRAINT `second_process_troubles_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `so_datas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `so_datas` (
  `doc_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posting_date` date NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `sales_uom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packaging_quantity` int NOT NULL,
  `sales_pack` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_finish` tinyint(1) DEFAULT NULL,
  `is_done` tinyint(1) DEFAULT NULL,
  `create_date` date NOT NULL,
  `update_date` date NOT NULL,
  `update_fulltime` bigint NOT NULL,
  KEY `idx_so_datas_doc_item` (`doc_num`,`item_code`),
  KEY `idx_so_datas_doc_num` (`doc_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_downtime_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_downtime_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `resume_time` timestamp NULL DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `countermeasure` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_downtime_entries_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_downtime_entries_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_input_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_input_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `pallet_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_input_entries_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_input_entries_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_production_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_production_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `recorded_at` timestamp NOT NULL,
  `good_qty` int NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_production_entries_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_production_entries_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_production_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_production_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_order_id` bigint unsigned NOT NULL,
  `operator_id` bigint unsigned DEFAULT NULL,
  `unit_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `second_process_report_id` bigint unsigned DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `total_input` int NOT NULL DEFAULT '0',
  `total_good` int NOT NULL DEFAULT '0',
  `total_reject` int NOT NULL DEFAULT '0',
  `total_rework_in` int NOT NULL DEFAULT '0',
  `total_rework_recovered` int NOT NULL DEFAULT '0',
  `total_scrap` int NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `production_notes` text COLLATE utf8mb4_unicode_ci,
  `ng_remarks` text COLLATE utf8mb4_unicode_ci,
  `absent_employees` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_production_schedule` json DEFAULT NULL,
  `output_destination` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_qc_bypassed` tinyint(1) NOT NULL DEFAULT '0',
  `qc_bypass_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qc_bypassed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `qc_bypassed_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_production_sessions_work_order_id_foreign` (`work_order_id`),
  KEY `sp_production_sessions_operator_id_foreign` (`operator_id`),
  KEY `sp_production_sessions_approved_by_foreign` (`approved_by`),
  KEY `sp_production_sessions_qc_bypassed_by_foreign` (`qc_bypassed_by`),
  KEY `sp_production_sessions_second_process_report_id_foreign` (`second_process_report_id`),
  CONSTRAINT `sp_production_sessions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_production_sessions_operator_id_foreign` FOREIGN KEY (`operator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_production_sessions_qc_bypassed_by_foreign` FOREIGN KEY (`qc_bypassed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_production_sessions_second_process_report_id_foreign` FOREIGN KEY (`second_process_report_id`) REFERENCES `second_process_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sp_production_sessions_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `sp_work_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_reject_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_reject_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `defect_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `cause` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_reject_entries_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_reject_entries_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_rework_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_rework_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `input_qty` int NOT NULL DEFAULT '0',
  `recovered_qty` int NOT NULL DEFAULT '0',
  `scrapped_qty` int NOT NULL DEFAULT '0',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_rework_entries_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_rework_entries_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_session_manpowers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_session_manpowers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `operator_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_session_manpowers_session_id_foreign` (`session_id`),
  KEY `sp_session_manpowers_user_id_foreign` (`user_id`),
  CONSTRAINT `sp_session_manpowers_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_session_manpowers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_session_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_session_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lot_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mixing_ratio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` decimal(10,2) DEFAULT NULL,
  `uom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sp_session_materials_session_id_foreign` (`session_id`),
  CONSTRAINT `sp_session_materials_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sp_production_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sp_work_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_work_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `wo_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `planned_date` date NOT NULL,
  `unit_line` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `process_prod` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_qty` int NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sp_work_orders_wo_number_unique` (`wo_number`),
  KEY `sp_work_orders_created_by_foreign` (`created_by`),
  CONSTRAINT `sp_work_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `specifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spk_bom_change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spk_bom_change_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spk_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replaced_item_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_qty` decimal(16,6) DEFAULT NULL,
  `plan_qty` decimal(16,4) DEFAULT NULL,
  `old_plan_qty` decimal(16,4) DEFAULT NULL,
  `warehouse` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUCCESS',
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `response` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_by_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spk_bom_change_logs_spk_number_created_at_index` (`spk_number`,`created_at`),
  KEY `spk_bom_change_logs_spk_number_index` (`spk_number`),
  KEY `spk_bom_change_logs_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spk_change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spk_change_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sync_batch_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spk_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `change_type` enum('NEW','QTY_CHANGE','STATUS_CHANGE','REMOVED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NEW',
  `old_planned_qty` int DEFAULT NULL,
  `new_planned_qty` int DEFAULT NULL,
  `old_completed_qty` int DEFAULT NULL,
  `new_completed_qty` int DEFAULT NULL,
  `old_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spk_change_logs_sync_batch_id_index` (`sync_batch_id`),
  KEY `spk_change_logs_spk_number_index` (`spk_number`),
  KEY `spk_change_logs_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spk_item_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spk_item_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spk_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `spk_item_histories_spk_number_index` (`spk_number`),
  KEY `spk_item_histories_item_code_index` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spk_masters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spk_masters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spk_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_date` date NOT NULL,
  `due_date` date NOT NULL,
  `production_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `planned_quantity` int NOT NULL,
  `completed_quantity` int NOT NULL,
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_box_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_box_data` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_box_data_part_no_unique` (`part_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `store_box_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_box_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` int NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remark` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `update_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `update_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `last_upload_time` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `specification_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` bigint NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `zone_id` int DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_branch_id_foreign` (`branch_id`),
  KEY `users_department_id_foreign` (`department_id`),
  KEY `users_last_activity_at_index` (`last_activity_at`),
  CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uti_date_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uti_date_list` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `date_interval` int DEFAULT NULL,
  `additional_value` int DEFAULT NULL,
  `additional_value_dec` decimal(7,2) DEFAULT NULL,
  `start_day` int DEFAULT NULL,
  `start_month` int DEFAULT NULL,
  `start_year` int DEFAULT NULL,
  `end_day` int DEFAULT NULL,
  `end_month` int DEFAULT NULL,
  `end_year` int DEFAULT NULL,
  `max_man_power` int DEFAULT NULL,
  `max_mould_change` int DEFAULT NULL,
  `lead_time_fg` int DEFAULT NULL,
  `lead_time_wip` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `waiting_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `waiting_purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mold_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `process` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double NOT NULL,
  `quotation_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL DEFAULT '1',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_pallet_form_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_pallet_form_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pallet_form_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `part_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spk_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `warehouse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_no_label` tinyint(1) NOT NULL DEFAULT '0',
  `no_label_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sap_sync_status` tinyint NOT NULL DEFAULT '0' COMMENT '0: Pending, 1: Success, 2: Error',
  `sap_error_msg` text COLLATE utf8mb4_unicode_ci,
  `sap_sync_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wms_pallet_form_details_pallet_form_id_foreign` (`pallet_form_id`),
  KEY `idx_wms_pfd_part_label` (`part_no`,`label`),
  KEY `idx_wms_pfd_spk_label` (`spk_no`,`label`),
  CONSTRAINT `wms_pallet_form_details_pallet_form_id_foreign` FOREIGN KEY (`pallet_form_id`) REFERENCES `wms_pallet_forms` (`pallet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_pallet_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_pallet_forms` (
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `part_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prod_date` date DEFAULT NULL,
  `lot_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_shift` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `box_qty` int NOT NULL DEFAULT '0',
  `total_pallet_qty` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STORED',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `sap_sync_status` int NOT NULL DEFAULT '0',
  `sap_error_msg` text COLLATE utf8mb4_unicode_ci,
  `sap_sync_at` timestamp NULL DEFAULT NULL,
  `sap_sync_duration` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pallet_id`),
  KEY `wms_pallet_forms_position_id_foreign` (`position_id`),
  CONSTRAINT `wms_pallet_forms_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `wms_positions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_pallet_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_pallet_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wms_pallet_logs_position_id_foreign` (`position_id`),
  KEY `wms_pallet_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `wms_pallet_logs_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `wms_positions` (`id`),
  CONSTRAINT `wms_pallet_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_picking_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_picking_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `picking_header_id` bigint unsigned NOT NULL,
  `item_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spk_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pallet_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_to_pick` double(15,2) NOT NULL DEFAULT '0.00',
  `qty_picked` double(15,2) NOT NULL DEFAULT '0.00',
  `is_picked` tinyint(1) NOT NULL DEFAULT '0',
  `fifo_seq` int DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AVAILABLE',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wms_picking_details_picking_header_id_foreign` (`picking_header_id`),
  CONSTRAINT `wms_picking_details_picking_header_id_foreign` FOREIGN KEY (`picking_header_id`) REFERENCES `wms_picking_headers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_picking_headers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_picking_headers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `picking_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `doc_num` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wms_picking_headers_picking_no_unique` (`picking_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rack_id` bigint unsigned NOT NULL,
  `level_no` int NOT NULL,
  `slot_no` int NOT NULL,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EMPTY',
  `last_item_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `max_capacity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wms_positions_position_code_unique` (`position_code`),
  KEY `wms_positions_rack_id_foreign` (`rack_id`),
  CONSTRAINT `wms_positions_rack_id_foreign` FOREIGN KEY (`rack_id`) REFERENCES `wms_racks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_racks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_racks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_id` bigint unsigned NOT NULL,
  `rack_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `x_pos` int NOT NULL DEFAULT '0',
  `y_pos` int NOT NULL DEFAULT '0',
  `width` int NOT NULL DEFAULT '200',
  `height` int NOT NULL DEFAULT '100',
  `orientation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HORIZONTAL',
  `zone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'DEFAULT',
  `sort_order` int NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wms_racks_whse_id_foreign` (`whse_id`),
  CONSTRAINT `wms_racks_whse_id_foreign` FOREIGN KEY (`whse_id`) REFERENCES `wms_warehouses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wms_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wms_warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `whse_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `whse_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `layout_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exit_location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOTTOM_RIGHT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wms_warehouses_whse_code_unique` (`whse_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zone_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zone_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int NOT NULL,
  `pengawas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shift` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zone_pengawas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zone_pengawas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int NOT NULL,
  `shift` int NOT NULL,
  `pengawas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_11_08_091003_create_prd_bill_of_material_parents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_11_08_091013_create_prd_bill_of_material_childs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_11_11_084359_add_transaction_type_in_prd_bill_of_material_childs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_11_14_073654_add_role_name_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_11_15_023919_create_prd_material_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_11_15_071318_create_prd_moulding_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_11_19_034006_add_material_id_in_prd_moulding_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_11_20_030859_create_specifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_11_20_032157_drop_role_name_column_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_11_20_034455_add_username_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_11_20_040212_add_pic_in_prd_material_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_11_21_021953_create_prd_moulding_user_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_11_22_013433_drop_prd_moulding_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2024_11_25_080843_add_new_field_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_11_29_021351_drop_specifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_11_29_021418_drop_specification_id_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_11_29_021451_add_role_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_11_29_022123_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_12_03_072256_create_specifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_12_03_072914_create_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2024_12_03_072958_create_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2024_12_03_073027_create_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2024_12_03_073110_add_item_name_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_12_03_073151_add_tipe_mesin_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_12_03_073441_rename_machine_name_to_item_no_in_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2024_12_03_073633_remove_item_group_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2024_12_03_073657_create_so_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2024_12_03_073723_create_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2024_12_03_073845_add_is_finish_and_is_complete_in_so_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2024_12_03_073921_remove_machine_name_from_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2024_12_03_074003_add_field_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2024_12_03_074040_add_standart_packaging_list_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2024_12_03_074111_add_is_done_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2024_12_03_074140_create_production_scan_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2024_12_03_074905_create_spk_masters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2024_12_03_075138_add_actual_quantity_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2024_12_03_075229_add_timestamps_and_soft_deletes_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2024_12_03_075251_create_machine_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2024_12_03_075408_add_shift_column_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2024_12_03_080656_add_start_time_column_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2024_12_03_080739_add_end_time_column_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2024_12_03_080946_add_schedule_date_column_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2024_12_03_081216_create_production_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2024_12_03_081345_drop_production_scan_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2024_12_03_081419_create_barcode_packaging_master_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2024_12_03_081449_create_barcode_packaging_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2024_12_03_081557_create_master_data_rog_part_names_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2024_12_03_082016_add_start_date_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2024_12_03_082230_add_end_date_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2024_12_03_082259_add_shift_in_machine_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2024_12_03_083029_change_null_on_uom_in_so_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2024_12_03_083100_create_failed_machine_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2024_12_03_083118_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2024_12_03_083151_rename_failed_machine_jobs_table_to_production_reports',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2024_12_03_083324_rename_outstanding_column_to_scanned_in_production_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2024_12_03_083414_add_outstanding_column_to_production_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2024_12_03_083630_drop_reason_column_in_production_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2024_12_03_083706_add_unique_constraint_to_spk_no_column_in_production_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2024_12_03_083737_add_create_date_in_so_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2024_12_03_083831_add_update_time_and_full_time_in_so_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2024_12_03_084100_create_update_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2024_12_03_084126_add_employee_name_in_machine_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2024_12_03_084208_add_dic_id_in_production_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2024_12_03_084235_create_master_all_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2024_12_03_084259_create_second_daily_processes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2024_12_03_084337_change_quality_hour_type_in_second_daily_processes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2024_12_03_084407_create_assembly_daily_processes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2024_12_03_084433_remove_id_from_so_datas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2024_12_04_082425_create_prd_broken_childs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2024_12_04_091933_add_customer_in_prd_bill_of_material_parents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2024_12_05_015949_create_prd_list_all_master_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2024_12_05_015957_rename_item_code_column_to_code_in_prd_bill_of_material_parents',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2024_12_05_020042_rename_item_description_column_to_description_in_prd_bill_of_material_parents',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2024_12_05_024047_create_prd_master_item_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2024_12_11_065554_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2024_12_20_050233_add_remark_field_in_prd_bill_of_material_childs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2024_12_20_050547_add_scan_start_in_prd_material_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2024_12_20_053316_add_remark_in_prd_material_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2024_12_20_072440_add_jobs_and_remark_in_prd_moulding_user_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2024_12_30_064056_create_waiting_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_01_02_045637_create_notification_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_01_02_062450_create_notification_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_01_02_062739_add_notification_type_id_to_notification_recipients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_01_07_073455_create_sap_bom_wip_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_01_07_073509_create_sap_delactual_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_01_07_073517_create_sap_delsched_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_01_07_073523_create_sap_delso_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_01_07_073535_create_sap_inventory_fg_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_01_07_073541_create_sap_inventory_mtr_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_01_07_073554_create_sap_lineproduction_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_01_07_073621_create_uti_date_list_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_01_07_073639_create_delsched_delfilter_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_01_07_073651_create_delsched_delsum_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_01_07_073702_create_delsched_final_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_01_07_073708_create_delsched_finalwip_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_01_07_073716_create_delsched_solist_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_01_07_073725_create_delsched_stock_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_01_07_073744_create_delsched_stockwip_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_01_07_074959_create_sap_reject_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_01_07_082956_make_remark_nullable_in_waiting_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_01_07_092304_create_sap_fg_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_01_08_083404_add_is_draft_in_prd_material_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_01_10_042032_add_doc_num_column_in_waiting_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_01_10_042141_drop_capture_photo_path_column_in_waiting_purchase_orders_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_01_10_085813_update_doc_num_for_waiting_purchase_orders',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_01_15_022235_add_column_in_sap_reject_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_01_16_014715_create_sap_forecast_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_01_20_072742_create_holiday_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_01_21_022337_change_date_in_holiday_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_01_23_021933_add_half_day_in_holiday_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_01_23_031045_create_inv_line_list_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_01_23_044049_create_cap_forecasts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_01_23_044144_create_cap_forecast_divs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_01_23_044157_create_cap_forecast_finals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_01_23_044216_create_cap_forecast_sorteds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_01_23_044238_create_cap_forecast_sums_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_01_23_044304_create_cap_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_01_23_044338_create_cap_line_capacities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_01_23_044355_create_cap_line_distributions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_01_23_044418_create_cap_line_summaries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_01_23_044747_create_cap_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_01_23_070348_create_sap_fct_bom_wip_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_01_23_070451_create_sap_fct_inventory_fgs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_01_23_070518_create_sap_fct_lineproductions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_01_23_085037_add_total_prior_in_cap_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_01_23_085723_add_total_prior_and_order_in_cap_line_distributions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_02_05_084818_add_remark_color_in_delsched_final_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_03_04_081422_add_user_in_production_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_03_04_084536_add_setup_mold_time_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_03_04_092055_create_mould_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_03_05_033151_add_item_code_in_mould_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_03_05_081732_create_operator_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_03_10_072526_create_delivery_schedule_new_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_03_17_030958_add_pic_in_mould_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_03_19_032536_add_profile_picture_to_operator_user',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_03_20_075725_add_processed_column_to_production_scanned_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_03_20_075948_create_production_summary_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_03_20_081848_add_department_table_in_operator_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_03_21_041858_create_adjust_machine_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_04_10_021828_add_zone_id_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_04_10_022054_create_master_zone_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_04_10_035602_create_zone_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_04_11_031009_delete_pengawas_from_master_zone_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_04_11_031301_create_zone_pengawas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_04_11_031723_add_shift_in_zone_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_04_24_084016_create_repair_machine_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_06_02_063555_add_remark_in_mould_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_06_02_063613_add_remark_in_adjust_machine_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_06_03_083111_add_pair_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_06_03_083554_add_remark_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_06_05_020314_create_hourly_remarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_07_10_020917_add_customer_in_barcode_packaging_master_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_07_10_022557_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_07_11_024907_add_temporal_cycle_time_in_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_07_11_025746_add_actual_production_in_hourly_remarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_07_23_014221_alter_remark_column_in_hourly_remarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_07_28_034944_make_quantity_nullable_in_barcode_packaging_details',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_08_06_022501_add_cavity_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_08_25_015618_add_ng_column_in_hourly_remarks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_08_28_020042_create_api_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2025_09_23_024819_create_store_box_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_09_23_033932_create_maintenance_machines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_09_24_013139_create_maintenance_moulds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2025_10_10_095139_create_alc_pe_master_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2025_10_17_015423_add_project_code_to_alc_pe_master_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2025_10_29_033130_create_production_ng_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2025_10_29_033157_create_production_ng_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2025_11_27_021556_create_master_item_photos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2025_12_02_015244_add_temporal_cavity_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2025_12_16_004556_create_spk_item_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_01_22_061650_create_master_delivery_schedule_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_01_22_061734_create_master_customer_delivery_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_02_03_013202_create_asakai_master_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_02_03_013316_create_asakai_pic_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_02_03_013335_create_asakai_rca_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_02_03_013347_create_asakai_corrective_actions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_02_03_023627_add_remark_to_asakai_master_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_02_06_021106_add_customer_code_in_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_02_11_022426_add_cycle_time_to_master_list_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_02_12_061738_add_remark_in_delsched_finalwip_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_02_19_013134_create_delsched_process_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_02_26_074831_add_sap_sent_to_production_summary_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_03_09_063723_add_indexes_to_production_summary',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_03_13_074832_add_summary_id_in_production_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_04_02_075100_create_store_box_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_04_16_015000_add_so_number_to_barcode_packaging_master_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_04_20_071322_add_spk_code_to_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_04_21_000001_create_wms_master_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_04_21_000002_create_wms_pallet_form_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_04_21_000003_create_wms_pallet_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_04_21_000004_add_spatial_to_wms_racks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_04_22_000001_update_wms_pallet_details_for_multi_item',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_04_28_155525_add_soft_deletes_to_wms_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_04_30_035209_change_update_fulltime_to_bigint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_05_04_000001_add_sap_fields_to_wms_pallet_form_details',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_05_06_000001_add_sap_fields_to_wms_pallet_forms',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_06_03_010238_add_indexes_to_master_list_items_and_files_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_06_03_034814_create_production_output_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_06_06_063513_add_indexes_to_hourly_remarks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_06_06_064422_add_indexes_to_daily_item_code_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_06_09_012433_create_production_payables_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_06_15_044137_add_resin_usage_to_daily_item_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_06_23_071611_add_indexes_to_api_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_06_23_081337_add_dic_id_index_to_production_scanned_data_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_06_23_083000_add_missing_indexes_to_hourly_remarks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_07_01_015921_add_dic_id_to_machine_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_07_06_070447_drop_second_daily_processes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2026_07_06_074506_create_second_process_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2026_07_06_074511_create_second_process_hourly_productions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2026_07_06_074511_create_second_process_materials_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2026_07_06_074512_create_second_process_manpowers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2026_07_06_074513_create_second_process_ng_records_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2026_07_06_074514_create_second_process_troubles_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2026_07_06_085000_create_master_item_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2026_07_06_085900_add_customer_code_and_cycle_time_to_master_list_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2026_07_07_090000_create_master_customer_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2026_07_07_133000_alter_second_process_tables_for_painting_redesign',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2026_07_07_134000_add_project_code_to_master_list_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2026_07_14_000000_create_wms_picking_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2026_07_16_071625_add_super_admin_and_user_status_fields',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2026_07_20_093200_add_unique_spk_label_to_production_scanned_data',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2026_07_21_000001_create_material_warehouse_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2026_07_22_000001_create_master_list_materials_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2026_07_22_000002_create_mwh_incoming_and_pallet_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2026_07_24_000001_add_sap_sync_duration_to_wms_pallet_forms',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2026_07_27_170001_create_ipqc_check_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2026_07_27_170002_create_ipqc_measurement_configs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2026_07_27_170003_create_qc_attachments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2026_07_27_170004_create_first_piece_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2026_07_27_170005_create_second_process_ipqc_records_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2026_07_27_170006_add_ipqc_fields_to_second_process_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2026_07_28_000001_add_qc_hold_to_mwh_pallets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2026_07_28_180000_create_ipqc_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2026_07_28_180001_create_ipqc_inspection_records_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2026_07_28_180002_cleanup_old_ipqc_from_second_process',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2026_07_29_000001_detach_zero_qty_wms_pallet_forms_from_positions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2026_07_29_000002_heal_mwh_position_empty_statuses',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2026_07_29_140001_create_sp_work_orders_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2026_07_29_140002_create_sp_production_sessions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2026_07_29_140003_create_sp_event_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2026_07_29_140005_add_approval_to_sp_production_sessions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2026_07_30_093800_add_qc_status_to_production_summary',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2026_07_30_093801_create_qc_transfer_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2026_07_31_140001_create_sp_session_manpowers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2026_07_31_170000_make_model_nullable_in_first_piece_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2026_08_03_000001_add_incoming_type_to_mwh_incoming_headers',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2026_08_05_000001_add_assigned_at_to_wms_pallet_forms',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2026_08_07_000001_create_maintenance_checklist_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2026_08_07_000002_add_material_lot_to_daily_item_codes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2026_08_07_000003_create_dic_accessory_lots_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2026_08_07_112400_add_qc_bypass_to_sp_production_sessions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2026_08_07_140000_add_uom_and_modify_qty_in_second_process_materials_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2026_08_10_092533_drop_shift_from_sp_work_orders_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2026_08_10_095543_add_work_order_id_to_first_piece_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2026_08_10_120000_drop_time_submit_from_first_piece_inspections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2026_08_11_051023_make_fields_nullable_on_second_process_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2026_08_11_133500_create_sp_session_materials_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2026_08_11_133501_add_category_countermeasure_to_sp_downtime_entries',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2026_08_11_133502_add_closeout_fields_to_sp_production_sessions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2026_08_12_000001_add_pic2_and_pic3_to_hourly_remarks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2026_08_12_000001_remove_reject_qty_from_sp_production_entries',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2026_08_12_000002_add_paused_at_to_sp_production_sessions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2026_08_12_000002_change_remark_column_to_text_in_hourly_remarks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2026_08_12_000003_create_spk_change_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2026_08_18_072000_create_custom_barcode_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2026_08_28_000001_add_barcode_type_to_custom_barcode_logs',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (361,'2026_08_28_000002_add_new_sap_columns_to_master_list_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2026_09_01_000001_setup_mwh_warehouses_and_branch_support',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2026_09_01_000002_add_performance_indexes_to_production_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (364,'2026_09_03_000001_make_ok_to_warehouse_nullable_in_qc_transfer_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (365,'2026_09_07_000001_add_initial_position_id_to_mwh_pallets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (366,'2026_09_07_140001_link_sp_sessions_and_second_process_reports',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (367,'2026_09_10_000001_create_branches_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (368,'2026_09_10_000002_create_departments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (369,'2026_09_10_000003_add_branch_and_department_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (370,'2026_09_10_000004_add_branch_id_to_production_and_warehouse_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (371,'2026_09_10_000004_create_branch_department_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (372,'2026_09_10_084000_add_qc_performance_indexes',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (373,'2026_09_21_000001_add_indexes_for_so_scan_performance',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (374,'2026_09_22_130000_add_sisa_input_and_remark_to_second_process_reports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (375,'2026_09_30_000001_create_master_boms_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (376,'2026_09_30_000002_create_master_bom_fg_headers_and_components_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (377,'2026_10_02_000001_add_layout_image_and_exit_to_wms_warehouses',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (378,'2026_10_02_000002_create_ppic_list_ups_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (379,'2026_10_02_000003_create_master_bom_audit_and_overrides_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (380,'2026_10_02_000004_add_family_to_master_boms_and_fg_headers',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (381,'2026_10_06_000001_create_spk_bom_change_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (382,'2026_10_06_000002_create_permissions_and_role_permissions_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (383,'2026_10_06_000003_consolidate_second_process_permissions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (384,'2026_10_07_000001_add_item_code_to_repair_machine_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (385,'2026_10_07_000001_create_master_business_partners_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (386,'2026_10_08_000001_simplify_master_business_partners_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (387,'2026_10_09_000001_add_zone_and_sort_order_to_wms_racks',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (388,'2026_10_09_000002_add_part_weight_to_master_list_items_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (389,'2026_10_09_050345_add_sales_employee_to_master_business_partners_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (390,'2026_10_09_055751_add_industry_to_master_business_partners_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (391,'2026_10_13_000001_create_mwh_daily_audits_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (392,'2026_10_16_025848_create_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (393,'2026_10_16_025849_add_event_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (394,'2026_10_16_025850_add_batch_uuid_column_to_activity_log_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (395,'2026_10_16_025851_add_last_activity_at_to_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (396,'2026_10_16_140000_make_activity_log_subject_and_causer_id_string',4);
