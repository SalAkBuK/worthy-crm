-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 13, 2026 at 05:15 AM
-- Server version: 11.4.9-MariaDB-cll-lve
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wortuckd_CRM`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_code` varchar(10) NOT NULL,
  `employee_name` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_name` varchar(120) NOT NULL,
  `contact_email` varchar(190) NOT NULL,
  `contact_phone` varchar(40) NOT NULL,
  `interested_in_property` varchar(190) NOT NULL,
  `property_type` enum('OFF_PLAN','READY_TO_MOVE','NONE') NOT NULL,
  `property_interest_types` varchar(255) DEFAULT NULL,
  `area` varchar(120) DEFAULT NULL,
  `budget_aed_min` decimal(12,2) DEFAULT NULL,
  `budget_aed_max` decimal(12,2) DEFAULT NULL,
  `budget_aed_range` varchar(60) DEFAULT NULL,
  `lead_status` varchar(60) DEFAULT NULL,
  `assigned_agent_user_id` int(10) UNSIGNED NOT NULL,
  `created_by_user_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status_overall` enum('NEW','IN_PROGRESS','CLOSED') NOT NULL DEFAULT 'NEW',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lead_followups`
--

CREATE TABLE `lead_followups` (
  `id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `agent_user_id` int(10) UNSIGNED NOT NULL,
  `attempt_no` int(10) UNSIGNED NOT NULL,
  `contact_datetime` datetime NOT NULL,
  `next_followup_at` datetime DEFAULT NULL,
  `call_status` enum('NO_RESPONSE','RESPONDED','ASK_CONTACT_LATER') NOT NULL,
  `interested_status` enum('INTERESTED','NOT_INTERESTED') NOT NULL,
  `intent` enum('RENT','BUY') DEFAULT NULL,
  `buy_property_type` enum('READY_TO_MOVE','OFF_PLAN') DEFAULT NULL,
  `if_not_interested_property_type` enum('OFF_PLAN','READY_TO_MOVE') DEFAULT NULL,
  `unit_type` enum('VILLA','APARTMENT') DEFAULT NULL,
  `size_sqft` int(10) UNSIGNED DEFAULT NULL,
  `location` varchar(190) DEFAULT NULL,
  `building` varchar(190) DEFAULT NULL,
  `beds` tinyint(3) UNSIGNED DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `downpayment` decimal(12,2) DEFAULT NULL,
  `cheques` tinyint(3) UNSIGNED DEFAULT NULL,
  `rent_per_month` decimal(12,2) DEFAULT NULL,
  `rent_per_year_budget` decimal(12,2) DEFAULT NULL,
  `notes` text NOT NULL,
  `call_screenshot_path` varchar(255) NOT NULL,
  `whatsapp_contacted` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_screenshot_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE `listings` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `area` varchar(255) NOT NULL,
  `developer` varchar(255) DEFAULT NULL,
  `unit_ref` varchar(120) DEFAULT NULL,
  `property_type` varchar(120) DEFAULT NULL,
  `beds_raw` varchar(50) DEFAULT NULL,
  `beds` tinyint(4) DEFAULT NULL,
  `baths_raw` varchar(50) DEFAULT NULL,
  `baths` tinyint(4) DEFAULT NULL,
  `size_raw` varchar(50) DEFAULT NULL,
  `size_sqft` decimal(10,2) DEFAULT NULL,
  `price_raw` varchar(80) DEFAULT NULL,
  `price_amount` decimal(14,2) DEFAULT NULL,
  `price_furnished_raw` varchar(64) DEFAULT NULL,
  `price_unfurnished_raw` varchar(64) DEFAULT NULL,
  `price_furnished_amount` bigint(20) DEFAULT NULL,
  `price_unfurnished_amount` bigint(20) DEFAULT NULL,
  `price_display_type` enum('FURNISHED','UNFURNISHED','SINGLE') NOT NULL DEFAULT 'SINGLE',
  `price_display_label` varchar(32) NOT NULL DEFAULT 'Price',
  `status` varchar(80) DEFAULT NULL,
  `payment_plan` text DEFAULT NULL,
  `brochure_url` varchar(500) DEFAULT NULL,
  `maps_url` varchar(500) DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `details_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details_json`)),
  `source` enum('MANUAL','PDF') NOT NULL DEFAULT 'MANUAL',
  `dataset_id` int(10) UNSIGNED DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_data`)),
  `created_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listing_datasets`
--

CREATE TABLE `listing_datasets` (
  `id` int(10) UNSIGNED NOT NULL,
  `uploaded_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  `file_size_bytes` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('UPLOADED','PROCESSING','COMPLETED','FAILED') NOT NULL DEFAULT 'UPLOADED',
  `parsed_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `extracted_text_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_attempt_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(60) NOT NULL,
  `title` varchar(190) NOT NULL,
  `body` text DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `meta_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_json`)),
  `dedup_key` varchar(190) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `contact_phone` varchar(40) DEFAULT NULL,
  `agent_name` varchar(120) DEFAULT NULL,
  `rera_number` varchar(50) DEFAULT NULL,
  `properties_scope` enum('OFF_PLAN','SECONDARY','BOTH') DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('ADMIN','CEO','AGENT') NOT NULL,
  `employee_code` varchar(10) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_code`),
  ADD KEY `idx_employees_name` (`employee_name`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leads_assigned_agent` (`assigned_agent_user_id`),
  ADD KEY `idx_leads_created_at` (`created_at`),
  ADD KEY `idx_leads_status` (`status_overall`),
  ADD KEY `fk_leads_created_by` (`created_by_user_id`),
  ADD KEY `idx_leads_active` (`is_active`);

--
-- Indexes for table `lead_followups`
--
ALTER TABLE `lead_followups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_followup_attempt` (`lead_id`,`agent_user_id`,`attempt_no`),
  ADD KEY `idx_followups_lead_id` (`lead_id`),
  ADD KEY `idx_followups_agent_id` (`agent_user_id`),
  ADD KEY `idx_followups_created_at` (`created_at`);

--
-- Indexes for table `listings`
--
ALTER TABLE `listings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_listings_dataset` (`dataset_id`),
  ADD KEY `idx_listings_source` (`source`),
  ADD KEY `idx_listings_project` (`project_name`),
  ADD KEY `idx_listings_area` (`area`),
  ADD KEY `idx_listings_status` (`status`),
  ADD KEY `idx_listings_created` (`created_at`),
  ADD KEY `idx_listings_created_by` (`created_by_user_id`);

--
-- Indexes for table `listing_datasets`
--
ALTER TABLE `listing_datasets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_listing_datasets_user` (`uploaded_by_user_id`),
  ADD KEY `idx_listing_datasets_status` (`status`),
  ADD KEY `idx_listing_datasets_created` (`created_at`),
  ADD KEY `idx_listing_datasets_hash` (`file_hash`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_login_attempts` (`username`,`ip_address`),
  ADD KEY `idx_login_locked` (`locked_until`),
  ADD KEY `idx_login_last_attempt` (`last_attempt_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_notifications_dedup` (`user_id`,`dedup_key`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`read_at`),
  ADD KEY `idx_notifications_created` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_employee_code` (`employee_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lead_followups`
--
ALTER TABLE `lead_followups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listings`
--
ALTER TABLE `listings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `listing_datasets`
--
ALTER TABLE `listing_datasets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `leads`
--
ALTER TABLE `leads`
  ADD CONSTRAINT `fk_leads_assigned_agent` FOREIGN KEY (`assigned_agent_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `lead_followups`
--
ALTER TABLE `lead_followups`
  ADD CONSTRAINT `fk_followups_agent` FOREIGN KEY (`agent_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_followups_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_employee_code` FOREIGN KEY (`employee_code`) REFERENCES `employees` (`employee_code`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
