
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
DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name_ur` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parentage_type` enum('FATHER','HUSBAND') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FATHER',
  `parentage_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnic` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alternate_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `postal_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_district_id` bigint unsigned DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumb_impression_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_indigent` tinyint(1) NOT NULL DEFAULT '0',
  `is_widow` tinyint(1) NOT NULL DEFAULT '0',
  `is_orphan` tinyint(1) NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `applicants_user_id_foreign` (`user_id`),
  KEY `applicants_address_district_id_foreign` (`address_district_id`),
  KEY `applicants_cnic_index` (`cnic`),
  CONSTRAINT `applicants_address_district_id_foreign` FOREIGN KEY (`address_district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `application_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `document_type_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_certified_copy` tinyint(1) NOT NULL DEFAULT '0',
  `issuing_authority` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_date` date DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PENDING','VERIFIED','DEFICIENT','REJECTED','WAIVED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_remarks` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_documents_document_type_id_foreign` (`document_type_id`),
  KEY `idx_doc_app_type` (`application_id`,`document_type_id`),
  KEY `application_documents_sha256_index` (`sha256`),
  KEY `application_documents_status_index` (`status`),
  CONSTRAINT `application_documents_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_documents_document_type_id_foreign` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `application_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `from_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_role` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_hist_app_time` (`application_id`,`occurred_at`),
  CONSTRAINT `application_status_history_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_id` bigint unsigned NOT NULL,
  `property_id` bigint unsigned NOT NULL,
  `district_id` bigint unsigned NOT NULL,
  `office_id` bigint unsigned DEFAULT NULL,
  `unit_profile_id` bigint unsigned NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `previous_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_remarks` text COLLATE utf8mb4_unicode_ci,
  `assigned_do_id` bigint unsigned DEFAULT NULL,
  `assigned_admin_id` bigint unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `scrutiny_started_at` timestamp NULL DEFAULT NULL,
  `first_notice_date` date DEFAULT NULL,
  `assessment_due_date` date DEFAULT NULL,
  `assessment_extended_to` date DEFAULT NULL,
  `extension_order_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_approval_due_date` date DEFAULT NULL,
  `rent_fixed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `regularized_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `is_sub_judice` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `assessed_monthly_rent` decimal(15,2) DEFAULT NULL,
  `total_arrears` decimal(15,2) NOT NULL DEFAULT '0.00',
  `arrears_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `arrears_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_application_no_unique` (`application_no`),
  KEY `applications_applicant_id_foreign` (`applicant_id`),
  KEY `applications_property_id_foreign` (`property_id`),
  KEY `applications_office_id_foreign` (`office_id`),
  KEY `applications_unit_profile_id_foreign` (`unit_profile_id`),
  KEY `idx_app_district_status` (`district_id`,`status`),
  KEY `applications_status_index` (`status`),
  KEY `applications_assigned_do_id_index` (`assigned_do_id`),
  KEY `applications_assigned_admin_id_index` (`assigned_admin_id`),
  KEY `applications_is_sub_judice_index` (`is_sub_judice`),
  CONSTRAINT `applications_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `applications_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `applications_office_id_foreign` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `applications_unit_profile_id_foreign` FOREIGN KEY (`unit_profile_id`) REFERENCES `unit_conversion_profiles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `level` enum('DISTRICT_OFFICER','ADMINISTRATOR','CHAIRMAN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` enum('APPROVE','REJECT','RETURN','DEFER','REMAND') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reasons` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `acted_by` bigint unsigned NOT NULL,
  `acted_at` timestamp NOT NULL,
  `due_by` date DEFAULT NULL,
  `is_within_sla` tinyint(1) NOT NULL DEFAULT '1',
  `days_taken` smallint unsigned DEFAULT NULL,
  `order_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_approval_app_level` (`application_id`,`level`),
  CONSTRAINT `approvals_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `arrears_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `arrears_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `assessment_round_id` bigint unsigned DEFAULT NULL,
  `period_year` smallint unsigned NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `monthly_rent` decimal(15,2) NOT NULL,
  `months_applicable` decimal(8,4) NOT NULL DEFAULT '12.0000',
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remission_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(15,2) NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ledger_app_year` (`application_id`,`period_year`),
  KEY `arrears_ledger_assessment_round_id_foreign` (`assessment_round_id`),
  CONSTRAINT `arrears_ledger_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `arrears_ledger_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_comparables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_comparables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_round_id` bigint unsigned NOT NULL,
  `property_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_sqft` decimal(18,4) DEFAULT NULL,
  `monthly_rent` decimal(15,2) NOT NULL,
  `usage_type` enum('RESIDENTIAL','COMMERCIAL','RESIDENTIAL_CUM_COMMERCIAL','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RESIDENTIAL',
  `distance_meters` decimal(10,2) DEFAULT NULL,
  `information_source` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observed_on` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_comparables_assessment_round_id_foreign` (`assessment_round_id`),
  CONSTRAINT `assessment_comparables_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_round_id` bigint unsigned NOT NULL,
  `determined_monthly_rent` decimal(15,2) NOT NULL,
  `rate_per_sqft` decimal(15,4) DEFAULT NULL,
  `reasons` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `objections_considered` text COLLATE utf8mb4_unicode_ci,
  `decided_by` bigint unsigned NOT NULL,
  `decided_at` timestamp NOT NULL,
  `is_superseded` tinyint(1) NOT NULL DEFAULT '0',
  `superseded_by_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_decisions_assessment_round_id_foreign` (`assessment_round_id`),
  CONSTRAINT `assessment_decisions_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_rate_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_rate_inputs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_round_id` bigint unsigned NOT NULL,
  `rate_source_id` bigint unsigned NOT NULL,
  `rate_value` decimal(15,2) NOT NULL,
  `rate_unit` enum('PER_SQFT_PER_MONTH','PER_MARLA_PER_MONTH','PER_MONTH_TOTAL','PER_SQFT_VALUE','PER_MARLA_VALUE','TOTAL_VALUE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PER_SQFT_PER_MONTH',
  `notification_no` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_date` date DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `valuator_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valuator_licence_no` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_no` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assessment_rate_inputs_assessment_round_id_foreign` (`assessment_round_id`),
  KEY `assessment_rate_inputs_rate_source_id_foreign` (`rate_source_id`),
  CONSTRAINT `assessment_rate_inputs_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_rate_inputs_rate_source_id_foreign` FOREIGN KEY (`rate_source_id`) REFERENCES `rate_sources` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assessment_rounds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_rounds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `property_id` bigint unsigned NOT NULL,
  `round_no` smallint unsigned NOT NULL DEFAULT '1',
  `round_type` enum('INITIAL','PERIODICAL','REVISION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INITIAL',
  `base_date` date NOT NULL,
  `effective_from` date NOT NULL,
  `enhancement_rate` decimal(5,2) NOT NULL DEFAULT '8.00',
  `enhancement_method` enum('SIMPLE','COMPOUND') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPOUND',
  `reassessment_cycle_years` smallint unsigned NOT NULL DEFAULT '6',
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `district_officer_id` bigint unsigned DEFAULT NULL,
  `first_notice_date` date DEFAULT NULL,
  `completion_due_date` date DEFAULT NULL,
  `extended_to` date DEFAULT NULL,
  `extension_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `extension_approved_by` bigint unsigned DEFAULT NULL,
  `proposed_monthly_rent` decimal(15,2) DEFAULT NULL,
  `determined_monthly_rent` decimal(15,2) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_app_round` (`application_id`,`round_no`),
  KEY `assessment_rounds_property_id_foreign` (`property_id`),
  KEY `assessment_rounds_status_index` (`status`),
  CONSTRAINT `assessment_rounds_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessment_rounds_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attachable_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attachable` (`attachable_type`,`attachable_id`),
  KEY `attachments_sha256_index` (`sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `auditable_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auditable_id` bigint unsigned DEFAULT NULL,
  `table_name` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_role` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditable` (`auditable_type`,`auditable_id`),
  KEY `idx_audit_user_time` (`user_id`,`created_at`),
  KEY `idx_audit_event` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province_id` bigint unsigned NOT NULL,
  `division_id` bigint unsigned DEFAULT NULL,
  `code` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_profile_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `districts_code_unique` (`code`),
  KEY `districts_province_id_foreign` (`province_id`),
  KEY `districts_division_id_foreign` (`division_id`),
  KEY `districts_unit_profile_id_foreign` (`unit_profile_id`),
  CONSTRAINT `districts_division_id_foreign` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `districts_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `districts_unit_profile_id_foreign` FOREIGN KEY (`unit_profile_id`) REFERENCES `unit_conversion_profiles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province_id` bigint unsigned NOT NULL,
  `code` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `divisions_code_unique` (`code`),
  KEY `divisions_province_id_foreign` (`province_id`),
  CONSTRAINT `divisions_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EVIDENCE',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_certified_copy_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `is_waivable` tinyint(1) NOT NULL DEFAULT '1',
  `proves_possession_date` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_mime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'application/pdf,image/jpeg,image/png,image/tiff',
  `max_size_kb` int unsigned NOT NULL DEFAULT '10240',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_types_code_unique` (`code`),
  KEY `document_types_category_index` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `document_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_document_id` bigint unsigned NOT NULL,
  `action` enum('VERIFIED','MARKED_DEFICIENT','REJECTED','WAIVED','RE_OPENED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_role` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_verifications_application_document_id_foreign` (`application_document_id`),
  CONSTRAINT `document_verifications_application_document_id_foreign` FOREIGN KEY (`application_document_id`) REFERENCES `application_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ejectment_proceedings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ejectment_proceedings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned DEFAULT NULL,
  `property_id` bigint unsigned DEFAULT NULL,
  `proceeding_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ground_of_ejectment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `show_cause_issued_on` date NOT NULL,
  `show_cause_days` smallint unsigned NOT NULL DEFAULT '7',
  `show_cause_deadline` date NOT NULL,
  `cause_shown` longtext COLLATE utf8mb4_unicode_ci,
  `cause_shown_on` date DEFAULT NULL,
  `hearing_id` bigint unsigned DEFAULT NULL,
  `is_satisfied_with_cause` tinyint(1) DEFAULT NULL,
  `ejectment_order_date` date DEFAULT NULL,
  `ejectment_order_text` longtext COLLATE utf8mb4_unicode_ci,
  `vacation_period_days` smallint unsigned DEFAULT NULL,
  `vacate_by` date DEFAULT NULL,
  `vacated_on` date DEFAULT NULL,
  `status` enum('SHOW_CAUSE_ISSUED','CAUSE_RECEIVED','HEARING','ORDER_PASSED','VACATED','DROPPED','UNDER_APPEAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SHOW_CAUSE_ISSUED',
  `initiated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ejectment_proceedings_proceeding_no_unique` (`proceeding_no`),
  KEY `ejectment_proceedings_application_id_foreign` (`application_id`),
  KEY `ejectment_proceedings_property_id_foreign` (`property_id`),
  KEY `ejectment_proceedings_hearing_id_foreign` (`hearing_id`),
  KEY `ejectment_proceedings_status_index` (`status`),
  CONSTRAINT `ejectment_proceedings_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ejectment_proceedings_hearing_id_foreign` FOREIGN KEY (`hearing_id`) REFERENCES `hearings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ejectment_proceedings_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `instrument_type` enum('PAY_ORDER','BANKERS_CHEQUE','DEMAND_DRAFT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAY_ORDER',
  `instrument_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instrument_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '5000.00',
  `payee` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chairman ETPB',
  `bank_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `depositor_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `depositor_cnic` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `depositor_contact` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_date` date NOT NULL,
  `scan_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','BOUNCED','REJECTED','REFUNDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `bank_confirmation_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verification_remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_payments_application_id_foreign` (`application_id`),
  KEY `fee_payments_district_id_foreign` (`district_id`),
  KEY `fee_payments_status_index` (`status`),
  CONSTRAINT `fee_payments_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_payments_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hearings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hearings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `assessment_round_id` bigint unsigned DEFAULT NULL,
  `hearing_no` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_for` datetime NOT NULL,
  `venue` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `presiding_officer_id` bigint unsigned DEFAULT NULL,
  `presiding_designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parties_summoned` json DEFAULT NULL,
  `attendance` json DEFAULT NULL,
  `proceedings` longtext COLLATE utf8mb4_unicode_ci,
  `adjourned_to` date DEFAULT NULL,
  `adjournment_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('SCHEDULED','HELD','ADJOURNED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SCHEDULED',
  `minutes_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hearings_application_id_foreign` (`application_id`),
  KEY `hearings_assessment_round_id_foreign` (`assessment_round_id`),
  KEY `hearings_status_index` (`status`),
  CONSTRAINT `hearings_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hearings_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `instalment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instalment_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `instalment_count` smallint unsigned NOT NULL,
  `instalment_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PROPOSED','APPROVED','REJECTED','COMPLETED','DEFAULTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROPOSED',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_reasons` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instalment_plans_application_id_foreign` (`application_id`),
  KEY `instalment_plans_status_index` (`status`),
  CONSTRAINT `instalment_plans_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `instalment_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `instalment_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `instalment_plan_id` bigint unsigned NOT NULL,
  `instalment_no` smallint unsigned NOT NULL,
  `due_date` date NOT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_on` date DEFAULT NULL,
  `receipt_id` bigint unsigned DEFAULT NULL,
  `status` enum('PENDING','PAID','PARTIAL','OVERDUE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_instalment` (`instalment_plan_id`,`instalment_no`),
  KEY `instalment_schedules_receipt_id_foreign` (`receipt_id`),
  KEY `instalment_schedules_status_index` (`status`),
  CONSTRAINT `instalment_schedules_instalment_plan_id_foreign` FOREIGN KEY (`instalment_plan_id`) REFERENCES `instalment_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `instalment_schedules_receipt_id_foreign` FOREIGN KEY (`receipt_id`) REFERENCES `payment_receipts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
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
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `litigations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `litigations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned DEFAULT NULL,
  `property_id` bigint unsigned DEFAULT NULL,
  `court_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `case_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `case_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `case_type` enum('CIVIL_SUIT','WRIT_PETITION','APPEAL','REVISION','EXECUTION','CONTEMPT','DIRECTION_CASE','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CIVIL_SUIT',
  `filed_on` date DEFAULT NULL,
  `petitioner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `respondent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pending` tinyint(1) NOT NULL DEFAULT '1',
  `has_restraining_order` tinyint(1) NOT NULL DEFAULT '0',
  `restraining_order_date` date DEFAULT NULL,
  `restraining_order_text` text COLLATE utf8mb4_unicode_ci,
  `is_direction_case` tinyint(1) NOT NULL DEFAULT '0',
  `direction_summary` text COLLATE utf8mb4_unicode_ci,
  `next_hearing_date` date DEFAULT NULL,
  `last_order_summary` text COLLATE utf8mb4_unicode_ci,
  `last_order_date` date DEFAULT NULL,
  `disposal_date` date DEFAULT NULL,
  `outcome` enum('ALLOWED','DISMISSED','WITHDRAWN','COMPROMISED','REMANDED','ABATED','PENDING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `outcome_detail` text COLLATE utf8mb4_unicode_ci,
  `counsel_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `litigations_application_id_foreign` (`application_id`),
  KEY `litigations_property_id_foreign` (`property_id`),
  KEY `idx_litigation_case` (`case_no`,`court_name`),
  KEY `litigations_is_pending_index` (`is_pending`),
  KEY `litigations_has_restraining_order_index` (`has_restraining_order`),
  CONSTRAINT `litigations_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `litigations_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT '0',
  `failure_reason` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_identifier_time` (`identifier`,`attempted_at`),
  KEY `login_attempts_identifier_index` (`identifier`),
  KEY `login_attempts_ip_address_index` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mouzas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mouzas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tehsil_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hadbast_no` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mouza_tehsil_name` (`tehsil_id`,`name`),
  CONSTRAINT `mouzas_tehsil_id_foreign` FOREIGN KEY (`tehsil_id`) REFERENCES `tehsils` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nominee_heirs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nominee_heirs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nominee_id` bigint unsigned NOT NULL,
  `heir_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnic` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nominee_heirs_nominee_id_foreign` (`nominee_id`),
  CONSTRAINT `nominee_heirs_nominee_id_foreign` FOREIGN KEY (`nominee_id`) REFERENCES `nominees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nominees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nominees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `nominee_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominee_parentage` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominee_cnic` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominee_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominee_address` text COLLATE utf8mb4_unicode_ci,
  `share_percentage` decimal(5,2) DEFAULT NULL,
  `form_received_on` date NOT NULL,
  `form_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nominees_application_id_foreign` (`application_id`),
  CONSTRAINT `nominees_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `application_id` bigint unsigned DEFAULT NULL,
  `channel` enum('IN_APP','EMAIL','SMS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IN_APP',
  `recipient` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('QUEUED','SENT','FAILED','READ') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'QUEUED',
  `attempts` smallint unsigned NOT NULL DEFAULT '0',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_queue_application_id_foreign` (`application_id`),
  KEY `notifications_queue_user_id_index` (`user_id`),
  KEY `notifications_queue_status_index` (`status`),
  CONSTRAINT `notifications_queue_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `objection_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objection_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `objection_id` bigint unsigned NOT NULL,
  `hearing_id` bigint unsigned DEFAULT NULL,
  `decision` enum('ACCEPTED','REJECTED','PARTIALLY_ACCEPTED','WITHDRAWN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reasons` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rent_impact` decimal(15,2) DEFAULT NULL,
  `decided_by` bigint unsigned NOT NULL,
  `decided_at` timestamp NOT NULL,
  `order_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `objection_decisions_objection_id_foreign` (`objection_id`),
  KEY `objection_decisions_hearing_id_foreign` (`hearing_id`),
  CONSTRAINT `objection_decisions_hearing_id_foreign` FOREIGN KEY (`hearing_id`) REFERENCES `hearings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `objection_decisions_objection_id_foreign` FOREIGN KEY (`objection_id`) REFERENCES `objections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `objections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `objections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `public_notice_id` bigint unsigned DEFAULT NULL,
  `objection_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objector_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objector_parentage` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objector_cnic` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objector_address` text COLLATE utf8mb4_unicode_ci,
  `objector_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship_to_property` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `plea` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `filed_on` date NOT NULL,
  `is_within_time` tinyint(1) NOT NULL DEFAULT '1',
  `late_filing_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('FILED','UNDER_HEARING','DECIDED','WITHDRAWN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FILED',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `objections_objection_no_unique` (`objection_no`),
  KEY `objections_application_id_foreign` (`application_id`),
  KEY `objections_public_notice_id_foreign` (`public_notice_id`),
  KEY `objections_status_index` (`status`),
  CONSTRAINT `objections_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `objections_public_notice_id_foreign` FOREIGN KEY (`public_notice_id`) REFERENCES `public_notices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `occupant_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `occupant_offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `occupant_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupant_parentage` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupant_cnic` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupant_contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupant_address` text COLLATE utf8mb4_unicode_ci,
  `portion_occupied` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_sqft` decimal(18,4) DEFAULT NULL,
  `rent_offered` decimal(15,2) NOT NULL,
  `offer_date` date NOT NULL,
  `terms_offered` text COLLATE utf8mb4_unicode_ci,
  `possession_since` date DEFAULT NULL,
  `status` enum('RECORDED','UNDER_CONSIDERATION','ACCEPTED','REJECTED','WITHDRAWN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RECORDED',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `occupant_offers_application_id_foreign` (`application_id`),
  KEY `occupant_offers_status_index` (`status`),
  CONSTRAINT `occupant_offers_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `offices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `office_type` enum('HEAD_OFFICE','ZONAL','DISTRICT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DISTRICT',
  `district_id` bigint unsigned DEFAULT NULL,
  `province_id` bigint unsigned DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offices_code_unique` (`code`),
  KEY `offices_district_id_foreign` (`district_id`),
  KEY `offices_province_id_foreign` (`province_id`),
  CONSTRAINT `offices_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offices_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
DROP TABLE IF EXISTS `payment_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `receipt_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_mode` enum('CASH','PAY_ORDER','BANKERS_CHEQUE','DEMAND_DRAFT','BANK_TRANSFER','CHALLAN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `instrument_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applied_to` enum('ARREARS','CURRENT_RENT','PENALTY','PROCESSING_FEE','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ARREARS',
  `applied_year` smallint unsigned DEFAULT NULL,
  `scan_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `status` enum('POSTED','BOUNCED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POSTED',
  `received_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_receipts_receipt_no_unique` (`receipt_no`),
  KEY `payment_receipts_application_id_foreign` (`application_id`),
  KEY `payment_receipts_status_index` (`status`),
  CONSTRAINT `payment_receipts_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `penalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned DEFAULT NULL,
  `property_id` bigint unsigned DEFAULT NULL,
  `penalty_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `breach_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_rectifiable` tinyint(1) NOT NULL DEFAULT '1',
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `imposed_on` date NOT NULL,
  `imposed_by` bigint unsigned NOT NULL,
  `show_cause_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_cause_date` date DEFAULT NULL,
  `hearing_id` bigint unsigned DEFAULT NULL,
  `order_text` text COLLATE utf8mb4_unicode_ci,
  `rectification_deadline` date DEFAULT NULL,
  `status` enum('SHOW_CAUSE_ISSUED','IMPOSED','PAID','WAIVED','TENANCY_CANCELLED','UNDER_APPEAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SHOW_CAUSE_ISSUED',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penalties_penalty_no_unique` (`penalty_no`),
  KEY `penalties_application_id_foreign` (`application_id`),
  KEY `penalties_property_id_foreign` (`property_id`),
  KEY `penalties_hearing_id_foreign` (`hearing_id`),
  KEY `penalties_status_index` (`status`),
  CONSTRAINT `penalties_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `penalties_hearing_id_foreign` FOREIGN KEY (`hearing_id`) REFERENCES `hearings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `penalties_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_code_unique` (`code`),
  KEY `permissions_module_index` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `possession_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `possession_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `date_of_possession` date NOT NULL,
  `possession_nature` enum('SELF','INHERITED','PURCHASED','ALLOTTED','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SELF',
  `possession_description` text COLLATE utf8mb4_unicode_ci,
  `date_of_judicial_verdict` date DEFAULT NULL,
  `judicial_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrears_from` date NOT NULL,
  `arrears_from_basis` enum('STATUTORY_2000','DATE_OF_OCCUPATION','JUDICIAL_VERDICT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STATUTORY_2000',
  `is_eligible` tinyint(1) NOT NULL DEFAULT '0',
  `eligibility_reason` text COLLATE utf8mb4_unicode_ci,
  `cutoff_applied` date NOT NULL,
  `is_pre_independence_plot` tinyint(1) NOT NULL DEFAULT '0',
  `is_colony_cluster` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `possession_details_application_id_foreign` (`application_id`),
  CONSTRAINT `possession_details_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_unit_no` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_type` enum('HOUSE','SHOP','BUILDING','PLOT','AGRI_LAND','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HOUSE',
  `usage_type` enum('RESIDENTIAL','COMMERCIAL','RESIDENTIAL_CUM_COMMERCIAL','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RESIDENTIAL',
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `province_id` bigint unsigned NOT NULL,
  `district_id` bigint unsigned NOT NULL,
  `tehsil_id` bigint unsigned DEFAULT NULL,
  `mouza_id` bigint unsigned DEFAULT NULL,
  `city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `khewat_no` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `khatooni_no` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `khasra_no` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `boundaries` text COLLATE utf8mb4_unicode_ci,
  `is_rural_agricultural` tinyint(1) NOT NULL DEFAULT '0',
  `land_reforms_note` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `properties_province_id_foreign` (`province_id`),
  KEY `properties_district_id_foreign` (`district_id`),
  KEY `properties_tehsil_id_foreign` (`tehsil_id`),
  KEY `properties_mouza_id_foreign` (`mouza_id`),
  KEY `idx_property_identity` (`property_no`,`sub_unit_no`,`district_id`),
  CONSTRAINT `properties_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `properties_mouza_id_foreign` FOREIGN KEY (`mouza_id`) REFERENCES `mouzas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `properties_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `properties_tehsil_id_foreign` FOREIGN KEY (`tehsil_id`) REFERENCES `tehsils` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `unit_profile_id` bigint unsigned NOT NULL,
  `entry_mode` enum('SINGLE','COMPOUND') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SINGLE',
  `entered_unit_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entered_value` decimal(18,4) DEFAULT NULL,
  `acres` decimal(18,4) DEFAULT NULL,
  `kanals` decimal(18,4) DEFAULT NULL,
  `marlas` decimal(18,4) DEFAULT NULL,
  `sarsais` decimal(18,4) DEFAULT NULL,
  `square_yards` decimal(18,4) DEFAULT NULL,
  `square_feet_direct` decimal(18,4) DEFAULT NULL,
  `area_sqft` decimal(18,4) NOT NULL,
  `covered_area_sqft` decimal(18,4) DEFAULT NULL,
  `conversion_trace` json DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_areas_property_id_foreign` (`property_id`),
  KEY `property_areas_unit_profile_id_foreign` (`unit_profile_id`),
  CONSTRAINT `property_areas_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_areas_unit_profile_id_foreign` FOREIGN KEY (`unit_profile_id`) REFERENCES `unit_conversion_profiles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_geo_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_geo_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `accuracy_meters` decimal(8,2) DEFAULT NULL,
  `source` enum('GPS_DEVICE','MOBILE','MANUAL','SATELLITE','SURVEY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
  `polygon` json DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `captured_at` timestamp NULL DEFAULT NULL,
  `captured_by` bigint unsigned DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_geo_tags_property_id_foreign` (`property_id`),
  CONSTRAINT `property_geo_tags_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provinces_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `public_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `assessment_round_id` bigint unsigned DEFAULT NULL,
  `notice_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notice_type` enum('PUBLIC','TENANT','OBJECTOR','SHOW_CAUSE','HEARING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PUBLIC',
  `issued_on` date NOT NULL,
  `served_on` date DEFAULT NULL,
  `service_mode` enum('HAND','REGISTERED_POST','COURIER','NEWSPAPER','NOTICE_BOARD','AFFIXATION','EMAIL','SMS') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'HAND',
  `newspaper_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_on` date DEFAULT NULL,
  `publication_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `objection_deadline` date NOT NULL,
  `subject` text COLLATE utf8mb4_unicode_ci,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','ISSUED','SERVED','PUBLISHED','EXPIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `issued_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `public_notices_notice_no_unique` (`notice_no`),
  KEY `public_notices_application_id_foreign` (`application_id`),
  KEY `public_notices_assessment_round_id_foreign` (`assessment_round_id`),
  KEY `public_notices_status_index` (`status`),
  CONSTRAINT `public_notices_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `public_notices_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_operative` tinyint(1) NOT NULL DEFAULT '0',
  `requires_reference_no` tinyint(1) NOT NULL DEFAULT '0',
  `requires_reasons` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rate_sources_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regularization_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regularization_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `order_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order_date` date NOT NULL,
  `issued_by` bigint unsigned NOT NULL,
  `issued_by_designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `regularized_area_sqft` decimal(18,4) DEFAULT NULL,
  `monthly_rent_fixed` decimal(15,2) DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','ISSUED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regularization_orders_order_no_unique` (`order_no`),
  KEY `regularization_orders_application_id_foreign` (`application_id`),
  KEY `regularization_orders_status_index` (`status`),
  CONSTRAINT `regularization_orders_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `ground` enum('INDIGENT','ORPHAN','WIDOW','INCAPABLE','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remission_type` enum('NOMINAL_RENT','REMIT_RENT','REMIT_ARREARS','PARTIAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nominal_monthly_rent` decimal(15,2) DEFAULT NULL,
  `remitted_amount` decimal(15,2) DEFAULT NULL,
  `remitted_percentage` decimal(5,2) DEFAULT NULL,
  `grounds_detail` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supporting_evidence` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PROPOSED','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROPOSED',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_reasons` text COLLATE utf8mb4_unicode_ci,
  `order_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `remissions_application_id_foreign` (`application_id`),
  KEY `remissions_status_index` (`status`),
  CONSTRAINT `remissions_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rent_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rent_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_round_id` bigint unsigned NOT NULL,
  `application_id` bigint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `period_from` date NOT NULL,
  `period_to` date NOT NULL,
  `monthly_rent` decimal(15,2) NOT NULL,
  `annual_rent` decimal(15,2) NOT NULL,
  `enhancement_applied_pct` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `years_elapsed` smallint unsigned NOT NULL DEFAULT '0',
  `is_reassessment_year` tinyint(1) NOT NULL DEFAULT '0',
  `is_milestone_year` tinyint(1) NOT NULL DEFAULT '0',
  `computation_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_round_year` (`assessment_round_id`,`year`),
  KEY `idx_sched_app_year` (`application_id`,`year`),
  CONSTRAINT `rent_schedules_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rent_schedules_assessment_round_id_foreign` FOREIGN KEY (`assessment_round_id`) REFERENCES `assessment_rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters` json DEFAULT NULL,
  `application_id` bigint unsigned DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `format` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PDF',
  `generated_by` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_snapshots_application_id_foreign` (`application_id`),
  KEY `report_snapshots_report_code_index` (`report_code`),
  CONSTRAINT `report_snapshots_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permission` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`,`permission_id`),
  KEY `role_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permission_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `hierarchy_level` smallint unsigned NOT NULL DEFAULT '50',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_type` enum('STRING','INT','DECIMAL','DATE','BOOL','JSON') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STRING',
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `label` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `legal_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_editable` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key_effective` (`key`,`effective_from`),
  KEY `settings_key_index` (`key`),
  KEY `settings_group_index` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tehsils`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tehsils` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `district_id` bigint unsigned NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ur` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tehsils_code_unique` (`code`),
  KEY `tehsils_district_id_foreign` (`district_id`),
  CONSTRAINT `tehsils_district_id_foreign` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenancy_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenancy_agreements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `agreement_no` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_on` date NOT NULL,
  `executed_by` bigint unsigned NOT NULL,
  `applicant_id` bigint unsigned NOT NULL,
  `monthly_rent` decimal(15,2) NOT NULL,
  `security_amount` decimal(15,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `valid_till` date DEFAULT NULL,
  `terms` longtext COLLATE utf8mb4_unicode_ci,
  `stamp_paper_no` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stamp_paper_value` decimal(15,2) DEFAULT NULL,
  `stamp_paper_date` date DEFAULT NULL,
  `signed_scan_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','EXECUTED','ACTIVE','TERMINATED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenancy_agreements_agreement_no_unique` (`agreement_no`),
  KEY `tenancy_agreements_application_id_foreign` (`application_id`),
  KEY `tenancy_agreements_applicant_id_foreign` (`applicant_id`),
  KEY `tenancy_agreements_status_index` (`status`),
  CONSTRAINT `tenancy_agreements_applicant_id_foreign` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `tenancy_agreements_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_conversion_factors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_conversion_factors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unit_profile_id` bigint unsigned NOT NULL,
  `unit_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_name_ur` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sqft_per_unit` decimal(18,4) NOT NULL,
  `display_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_compound_component` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profile_unit` (`unit_profile_id`,`unit_code`),
  CONSTRAINT `unit_conversion_factors_unit_profile_id_foreign` FOREIGN KEY (`unit_profile_id`) REFERENCES `unit_conversion_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unit_conversion_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_conversion_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_conversion_profiles_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`,`role_id`),
  KEY `user_role_role_id_foreign` (`role_id`),
  CONSTRAINT `user_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `user_role_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnic` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_id` bigint unsigned DEFAULT NULL,
  `district_id` bigint unsigned DEFAULT NULL,
  `status` enum('ACTIVE','SUSPENDED','LOCKED','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `force_password_change` tinyint(1) NOT NULL DEFAULT '1',
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failed_login_count` smallint unsigned NOT NULL DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_cnic_unique` (`cnic`),
  KEY `users_office_id_index` (`office_id`),
  KEY `users_district_id_index` (`district_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

