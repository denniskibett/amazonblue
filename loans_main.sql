-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 15, 2026 at 11:14 AM
-- Server version: 10.3.39-MariaDB-0ubuntu0.20.04.2
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `loans_main`
--

-- --------------------------------------------------------

--
-- Table structure for table `action_types`
--

CREATE TABLE `action_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `action_types`
--

INSERT INTO `action_types` (`id`, `name`, `slug`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Phone Call', 'phone_call', 1, 10, NULL, NULL),
(2, 'SMS', 'sms', 1, 20, NULL, NULL),
(3, 'Email', 'email', 1, 30, NULL, NULL),
(4, 'Visit', 'visit', 1, 40, NULL, NULL),
(5, 'Letter', 'letter', 1, 50, NULL, NULL),
(6, 'Legal Notice', 'legal_notice', 1, 60, NULL, NULL),
(7, 'Negotiation', 'negotiation', 1, 70, NULL, NULL),
(8, 'Payment Arrangement', 'payment_arrangement', 1, 80, NULL, NULL),
(9, 'Field Visit', 'field_visit', 1, 90, NULL, NULL),
(10, 'Other', 'other', 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `address_type_id` bigint(20) UNSIGNED NOT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `address_types`
--

CREATE TABLE `address_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `address_types`
--

INSERT INTO `address_types` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Current', 'current', NULL, 1, 10, NULL, NULL),
(2, 'Previous', 'previous', NULL, 1, 20, NULL, NULL),
(3, 'Permanent', 'permanent', NULL, 1, 30, NULL, NULL),
(4, 'Postal', 'postal', NULL, 1, 40, NULL, NULL),
(5, 'Business', 'business', NULL, 1, 50, NULL, NULL),
(6, 'Other', 'other', NULL, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agency_case_assignments`
--

CREATE TABLE `agency_case_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `agency_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_date` date NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `recovery_amount` decimal(15,2) DEFAULT NULL,
  `agency_fees` decimal(15,2) DEFAULT NULL,
  `status` enum('assigned','in_progress','recovered','returned') DEFAULT 'assigned',
  `return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agency_contacts`
--

CREATE TABLE `agency_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agency_id` bigint(20) UNSIGNED NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_2` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `asset_type_id` bigint(20) UNSIGNED NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `estimated_value` decimal(15,2) DEFAULT NULL,
  `lien_holder` varchar(255) DEFAULT NULL,
  `lien_amount` decimal(15,2) DEFAULT NULL,
  `is_collateral` tinyint(1) DEFAULT 0,
  `collateral_for_loan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `valuation_date` date DEFAULT NULL,
  `valuation_by` varchar(255) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `status` enum('owned','financed','leased','repossessed','sold') DEFAULT 'owned',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_types`
--

CREATE TABLE `asset_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `requires_registration` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `asset_types`
--

INSERT INTO `asset_types` (`id`, `name`, `slug`, `requires_registration`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Vehicle', 'vehicle', 1, 1, 10, NULL, NULL),
(2, 'Property', 'property', 1, 1, 20, NULL, NULL),
(3, 'Equipment', 'equipment', 0, 1, 30, NULL, NULL),
(4, 'Inventory', 'inventory', 0, 1, 40, NULL, NULL),
(5, 'Bank Account', 'bank_account', 0, 1, 50, NULL, NULL),
(6, 'Investment', 'investment', 0, 1, 60, NULL, NULL),
(7, 'Crypto Wallet', 'crypto_wallet', 0, 1, 70, NULL, NULL),
(8, 'Other', 'other', 0, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('create','update','delete','restore','force_delete') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` varchar(255) NOT NULL,
  `branch` varchar(255) NOT NULL,
  `accountable_type` varchar(255) NOT NULL,
  `accountable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `borrowers`
--

CREATE TABLE `borrowers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `broker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `client_type` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `income_type` varchar(100) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `workplace` varchar(255) DEFAULT NULL,
  `employer_name` varchar(255) DEFAULT NULL,
  `employer_email` varchar(255) DEFAULT NULL,
  `employer_title` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrowers`
--

INSERT INTO `borrowers` (`id`, `broker_id`, `user_id`, `client_type`, `status`, `created_at`, `updated_at`, `income_type`, `gross_salary`, `net_salary`, `job_title`, `workplace`, `employer_name`, `employer_email`, `employer_title`, `department`) VALUES
(1, 1, 14, '1', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 2, 'individual', 1, NULL, '2026-01-22 20:30:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, NULL, 9, '0', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, NULL, 3, '0', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, NULL, 6, '0', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, NULL, 7, '0', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 1, 16, '1', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 1, 6, '0', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 1, 18, '0', 1, '2025-04-20 10:39:32', '2025-04-20 10:39:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 1, 19, '1', 1, '2025-04-20 17:14:25', '2025-04-20 17:14:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(50, NULL, 20, '0', 1, '2025-04-20 17:45:22', '2025-04-20 17:45:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 1, 22, '1', 1, '2025-05-07 12:10:19', '2025-05-07 12:10:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 1, 24, '1', 1, '2025-05-13 06:49:55', '2025-05-13 06:49:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 1, 25, '1', 1, '2025-05-15 08:08:59', '2025-05-15 08:08:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, NULL, 26, '0', 1, '2025-05-19 12:45:53', '2025-05-19 12:45:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(55, NULL, 27, '0', 1, '2025-05-20 13:03:03', '2025-05-20 13:03:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(56, NULL, 28, '0', 1, '2025-05-25 18:36:15', '2025-05-25 18:36:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, NULL, 29, '0', 1, '2025-05-27 13:47:29', '2025-10-29 07:03:27', 'Employment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, NULL, 31, '0', 1, '2025-05-29 13:47:29', '2026-02-09 17:11:09', 'Business', 4000000.00, 1200000.00, 'CEO', NULL, 'Teflon Trading Limited', 'musau.mumo@teflontradingltd.co.ke', 'CEO', 'CEO OFFICE'),
(59, NULL, 34, '0', 1, '2025-06-15 13:55:59', '2025-06-15 13:55:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, NULL, 37, '0', 1, '2025-06-23 07:43:27', '2025-06-23 07:43:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(61, NULL, 38, '0', 1, '2025-07-07 08:51:03', '2025-07-07 08:51:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(62, NULL, 39, '0', 1, '2025-07-13 16:11:22', '2025-07-13 16:11:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, NULL, 40, 'individual', 1, '2025-07-14 11:35:57', '2026-08-31 13:14:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, NULL, 41, '0', 1, '2025-07-15 10:00:13', '2025-07-15 10:00:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, NULL, 42, '0', 0, '2025-07-17 10:52:30', '2025-07-17 10:52:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, NULL, 43, '0', 0, '2025-07-28 06:05:53', '2025-07-28 06:05:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, NULL, 44, '0', 0, '2025-08-06 05:11:15', '2025-08-06 05:11:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, NULL, 45, '0', 1, '2025-08-06 11:25:20', '2025-08-06 11:25:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, NULL, 47, '0', 0, '2025-08-14 07:30:39', '2025-08-14 07:30:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, NULL, 48, '0', 0, '2025-08-19 14:58:14', '2025-08-19 14:58:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, NULL, 50, '1', 1, '2025-09-10 06:39:51', '2025-10-27 10:13:24', 'Business', 150000.00, 105000.00, 'director', 'Mokka city', 'Mohammed Abdirahim', 'mohamed.a@mokkacity.com', 'Director', 'Management'),
(72, NULL, 51, '0', 0, '2025-09-14 12:24:33', '2025-09-14 12:24:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, NULL, 52, '0', 1, '2025-10-01 09:11:31', '2025-11-21 05:31:32', 'Employment', NULL, 800.00, 'IT consultant', NULL, 'robert Macharia', NULL, NULL, NULL),
(74, NULL, 53, '1', 1, '2025-10-03 06:04:44', '2026-03-06 10:08:02', 'Employment', 20000.00, 20000.00, 'IT Assistant', 'Iebc', 'Government of kenya', 'emmanueltsuma19@gmail.com', 'Iebc', 'IT'),
(75, NULL, 54, '0', 0, '2025-10-08 04:06:13', '2025-10-08 04:06:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(76, NULL, 55, '0', 1, '2025-10-23 05:37:11', '2025-10-26 07:54:26', 'Business', 50000.00, 38500.00, 'Director', 'Nairobi', 'Trademag Solutions', 'trademagsolutions@gmail.com', 'Director', 'Sourcing'),
(77, NULL, 13, 'individual', 1, '2025-10-23 19:15:21', '2026-07-16 15:51:14', 'Employment', 130000.00, 100000.00, 'ICTO', 'Kisumu', 'iPas', 'info@ipas.org', NULL, 'Accounts and Finance'),
(78, NULL, 56, 'individual', 1, '2025-10-24 03:31:10', '2026-02-01 19:45:11', 'Government', NULL, NULL, 'Dept Head Communications', 'City Hall', 'County Government Nairobi', 'info@nairobi.go.ke', 'Chief Officer/Director', 'Executive'),
(79, NULL, 57, '0', 0, '2025-10-24 09:49:49', '2025-10-24 13:12:24', 'Business', 1768.00, 1226.00, 'Personal Assistant', 'Kileleshwa, Kangundo rd', 'Relay Services', 'yeggynick@gmail.com', 'Chief Executive Officer', 'office of the ceo'),
(80, NULL, 58, 'individual', 0, '2025-10-25 13:22:18', '2026-04-26 06:57:39', 'employed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, NULL, 59, '0', 1, '2025-10-27 13:40:41', '2025-10-27 13:59:08', 'Employment', 1200.00, 1200.00, 'Business development manager', '24 markets', 'Julie', 'juliekwa@gmail.com', 'Group of head partnerships', 'Trading'),
(82, NULL, 21, '0', 1, '2025-10-29 09:45:23', '2025-10-29 09:48:42', 'Employment', 68500.00, 50000.00, 'Engineer', 'Nairobi', 'Paralgin', 'sales@paralgin.co.ke', 'Sir', 'Engineering'),
(83, NULL, 62, 'individual', 1, '2025-12-22 10:11:02', '2026-02-03 05:07:58', 'Employment', 90000.00, 65000.00, 'Project Management Consultant', 'Swarm Initiative', 'Swarm Initiative', 'operations@learnersway.co.ke', 'Head of operations', 'Operations'),
(84, NULL, 63, 'individual', 0, '2025-12-22 10:31:24', '2026-09-16 06:45:42', 'employed', NULL, 65000.00, 'Lead Culture and Entertainment Writer', 'Nairobi', 'Wakilisha Africa', NULL, NULL, NULL),
(85, NULL, 64, '0', 1, '2026-01-20 09:03:36', '2026-04-14 11:15:46', 'Government', 80000.00, 60000.00, 'Admin Assistant', 'JK Executive Solutions', 'JK Executive Solutions', 'jkexecutivesolutions@gmail.com', 'Ms', 'Management'),
(86, NULL, 65, 'individual', 0, '2026-01-21 06:27:38', '2026-01-29 09:48:32', 'employed', 450000.00, 300000.00, 'Communications Officer', 'Public Service', 'Government of Kenya', NULL, NULL, 'Communication'),
(87, NULL, 66, '0', 0, '2026-01-21 07:15:44', '2026-01-21 07:15:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(88, NULL, 67, 'individual', 0, '2026-02-18 08:27:11', '2026-02-18 08:50:25', 'Employment', NULL, 100000.00, 'COO', NULL, 'Sichangi', NULL, NULL, NULL),
(89, NULL, 68, '0', 0, '2026-02-18 08:56:28', '2026-02-18 08:56:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, NULL, 69, '0', 1, '2026-02-18 14:51:36', '2026-02-19 10:09:08', 'Employment', NULL, 45000.00, 'ICT officer', 'Nyayo House', 'Directorate of eCitizen', NULL, 'Immigration', NULL),
(91, NULL, 70, '0', 1, '2026-02-23 13:47:01', '2026-02-23 14:09:18', 'Government', NULL, 51000.00, 'ICT officer', 'Nyayo House', 'Immigration', 'nigel.yegon@ecitizen.go.ke', 'eCitizen', 'eCitizen'),
(92, NULL, 71, '0', 0, '2026-03-06 04:57:07', '2026-03-07 11:48:57', 'Business', 350000.00, 250000.00, 'GIS Consultant', 'Nairobi', 'ExceedIT Systems Limited', 'info@exceedit.co.ke', 'Private Limited Company', 'Environment and Climate Change'),
(93, NULL, 72, '0', 0, '2026-03-16 09:02:56', '2026-03-16 09:02:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(94, NULL, 73, '0', 0, '2026-05-05 09:59:58', '2026-05-05 10:04:03', 'Business', NULL, 10.00, 'na', NULL, 'na', NULL, NULL, NULL),
(95, NULL, 74, '0', 1, '2026-05-11 05:17:56', '2026-05-11 05:39:09', 'Employment', 76000.00, 70000.00, 'Sales Manager', 'Nairobi', 'Tropikal', NULL, 'Manufacturing', 'Sales'),
(96, NULL, 75, '0', 0, '2026-05-11 06:03:12', '2026-05-11 06:03:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(97, NULL, 76, 'individual', 0, '2026-06-20 08:55:31', '2026-06-20 11:41:31', 'employed', NULL, 80000.00, 'Business Assistant', NULL, 'Susan Mutinda', 'suzzyndanu@yahoo.com', NULL, NULL),
(98, NULL, 77, '0', 0, '2026-06-24 10:24:46', '2026-06-24 10:24:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, NULL, 84, 'individual', 0, '2026-06-26 14:40:16', '2026-09-29 14:04:40', NULL, 70000.00, 50000.00, 'Procurement Officer', 'Nyayo House 19th Floor', 'State Department of Immigration and Citizen Services', 'brian.tanui@usajili.go.ke', NULL, 'Procurement/Supply Chain Immigration'),
(100, NULL, 91, '0', 1, '2026-07-16 13:04:57', '2026-07-16 13:23:48', 'Employment', NULL, 10000.00, 'INTERN', NULL, 'Sharet Enterprise', NULL, NULL, NULL),
(101, NULL, 92, '0', 1, '2026-07-27 10:50:00', '2026-07-27 11:35:54', 'Employment', NULL, 250.00, 'Accountant', NULL, 'Bringetony ventures', NULL, NULL, NULL),
(102, NULL, 93, '0', 0, '2026-07-30 05:23:26', '2026-07-30 05:23:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, NULL, 94, '0', 0, '2026-08-04 07:28:29', '2026-08-04 07:28:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, NULL, 95, '0', 0, '2026-08-11 09:36:28', '2026-08-11 09:36:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, NULL, 97, 'individual', 1, '2026-08-24 08:00:37', '2026-08-24 08:00:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(106, NULL, 98, 'individual', 1, '2026-08-24 10:55:41', '2026-08-24 10:55:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, NULL, 99, 'individual', 1, '2026-08-24 10:59:42', '2026-08-24 10:59:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, NULL, 100, 'individual', 0, '2026-08-28 04:23:30', '2026-08-31 08:50:27', 'employed', NULL, NULL, 'Mobile and Front End Engineer', 'The Atrium 1st Floor Lenana Rd', 'Cloud9 Payments Limited', 'info@cloud9.money', NULL, 'Product'),
(109, NULL, 101, 'individual', 1, '2026-08-31 08:33:03', '2026-08-31 08:33:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(110, NULL, 102, 'individual', 1, '2026-08-31 13:48:37', '2026-08-31 13:48:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, NULL, 104, 'individual', 0, '2026-09-11 04:57:42', '2026-09-11 05:33:11', 'employed', 100000.00, 100000.00, 'Education Consultant', 'Gitanga Road', 'UTU Education Center', NULL, NULL, 'Education data analysis'),
(113, NULL, 106, '0', 0, '2026-09-26 14:02:07', '2026-09-26 14:02:07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(114, NULL, 107, 'individual', 1, '2026-09-27 10:22:48', '2026-09-27 10:22:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(115, NULL, 108, 'individual', 1, '2026-10-12 08:38:36', '2026-10-12 08:38:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(116, NULL, 109, '0', 0, '2026-10-13 18:05:44', '2026-10-13 18:05:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, NULL, 110, 'individual', 0, '2026-10-14 23:40:12', '2026-10-14 23:40:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `borrower_contact_network`
-- (See below for the actual view)
--
CREATE TABLE `borrower_contact_network` (
`user_id` bigint(20) unsigned
,`borrower_name` varchar(255)
,`contact_id` bigint(20) unsigned
,`contact_name` varchar(255)
,`contact_phone` varchar(50)
,`contact_email` varchar(255)
,`contact_type` varchar(100)
,`relationship_specific` varchar(100)
,`is_primary_contact` tinyint(1)
,`priority` tinyint(4)
);

-- --------------------------------------------------------

--
-- Table structure for table `brokers`
--

CREATE TABLE `brokers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `interest_client` decimal(5,2) NOT NULL,
  `interest_broker` decimal(5,2) NOT NULL,
  `penalty_client` decimal(5,2) NOT NULL,
  `penalty_broker` decimal(5,2) NOT NULL,
  `cert_no` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brokers`
--

INSERT INTO `brokers` (`id`, `user_id`, `interest_client`, `interest_broker`, `penalty_client`, `penalty_broker`, `cert_no`, `created_at`, `updated_at`) VALUES
(1, 3, 20.00, 40.00, 50.00, 50.00, '0001', '2025-05-10 13:44:39', '2025-05-10 13:44:49');

-- --------------------------------------------------------

--
-- Stand-in structure for view `broker_performance_report`
-- (See below for the actual view)
--
CREATE TABLE `broker_performance_report` (
`broker_user_id` bigint(20) unsigned
,`broker_name` varchar(255)
,`clients_referred` bigint(21)
,`loans_from_clients` bigint(21)
,`total_principal_from_clients` decimal(37,2)
,`client_rollovers` decimal(22,0)
,`client_discounts` decimal(22,0)
,`client_bad_debts` decimal(22,0)
,`estimated_broker_interest_commission` decimal(55,14)
,`estimated_broker_penalty_commission` decimal(55,14)
);

-- --------------------------------------------------------

--
-- Table structure for table `bureau_names`
--

CREATE TABLE `bureau_names` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bureau_names`
--

INSERT INTO `bureau_names` (`id`, `name`, `slug`, `website`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CRB Kenya', 'crb_kenya', NULL, 1, NULL, NULL),
(2, 'TransUnion', 'transunion', NULL, 1, NULL, NULL),
(3, 'Equifax', 'equifax', NULL, 1, NULL, NULL),
(4, 'Experian', 'experian', NULL, 1, NULL, NULL),
(5, 'Other', 'other', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_type` varchar(255) NOT NULL,
  `categoryable_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `category_type`, `categoryable_id`, `created_at`, `updated_at`) VALUES
(1, 'Christian', 'religion', 1, NULL, NULL),
(2, 'Muslim', 'religion', 1, NULL, NULL),
(3, 'Atheist', 'religion', 1, NULL, NULL),
(4, 'Hindu', 'religion', 1, NULL, NULL),
(5, 'Traditional', 'religion', 1, NULL, NULL),
(11, 'Primary', 'education', 2, NULL, NULL),
(12, 'Secondary', 'education', 2, NULL, NULL),
(13, 'Diploma', 'education', 2, NULL, NULL),
(14, 'Bachelor\'s Degree', 'education', 2, NULL, NULL),
(15, 'Master\'s Degree', 'education', 2, NULL, NULL),
(16, 'PhD', 'education', 2, NULL, NULL),
(27, 'Brother', 'relationship', 3, NULL, NULL),
(28, 'Sister', 'relationship', 3, NULL, NULL),
(29, 'Father', 'relationship', 3, NULL, NULL),
(30, 'Mother', 'relationship', 3, NULL, NULL),
(31, 'Aunt', 'relationship', 3, NULL, NULL),
(32, 'Uncle', 'relationship', 3, NULL, NULL),
(33, 'Aunt', 'relationship', 3, NULL, NULL),
(34, 'Grand Mother', 'relationship', 3, NULL, NULL),
(35, 'Grand Father', 'relationship', 3, NULL, NULL),
(36, 'Friend', 'relationship', 3, NULL, NULL),
(37, 'Business Partner', 'relationship', 3, NULL, NULL),
(38, 'Work Colleague', 'relationship', 3, NULL, NULL),
(39, 'Employment', 'income_type', 4, NULL, NULL),
(40, 'Business', 'income_type', 4, NULL, NULL),
(41, 'Asset', 'income_type', 4, NULL, NULL),
(42, 'Government', 'income_type', 4, NULL, NULL),
(43, 'Remittances', 'income_type', 4, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--

CREATE TABLE `communications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `communication_type_id` bigint(20) UNSIGNED NOT NULL,
  `communication_status_id` bigint(20) UNSIGNED DEFAULT NULL,
  `direction` enum('outbound','inbound') NOT NULL,
  `recipient` varchar(255) DEFAULT NULL,
  `recipient_phone` varchar(50) DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `provider_response` text DEFAULT NULL,
  `delivery_attempts` int(11) DEFAULT 1,
  `sent_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_statuses`
--

CREATE TABLE `communication_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communication_statuses`
--

INSERT INTO `communication_statuses` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Sent', 'sent', NULL, 1, NULL, NULL),
(2, 'Delivered', 'delivered', NULL, 1, NULL, NULL),
(3, 'Failed', 'failed', NULL, 1, NULL, NULL),
(4, 'Read', 'read', NULL, 1, NULL, NULL),
(5, 'Replied', 'replied', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `communication_types`
--

CREATE TABLE `communication_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communication_types`
--

INSERT INTO `communication_types` (`id`, `name`, `slug`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'SMS', 'sms', 1, 10, NULL, NULL),
(2, 'Email', 'email', 1, 20, NULL, NULL),
(3, 'WhatsApp', 'whatsapp', 1, 30, NULL, NULL),
(4, 'Letter', 'letter', 1, 40, NULL, NULL),
(5, 'Phone Call', 'phone_call', 1, 50, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `contact_type_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `phone_2` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `relationship_specific` varchar(100) DEFAULT NULL COMMENT 'e.g., Mother, Father, Brother',
  `is_primary_contact` tinyint(1) DEFAULT 0,
  `priority` tinyint(4) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_types`
--

CREATE TABLE `contact_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_types`
--

INSERT INTO `contact_types` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Parent', 'parent', NULL, 1, 10, NULL, NULL),
(2, 'Sibling', 'sibling', NULL, 1, 20, NULL, NULL),
(3, 'Spouse', 'spouse', NULL, 1, 30, NULL, NULL),
(4, 'Child', 'child', NULL, 1, 40, NULL, NULL),
(5, 'Relative', 'relative', NULL, 1, 50, NULL, NULL),
(6, 'Friend', 'friend', NULL, 1, 60, NULL, NULL),
(7, 'Business Partner', 'business_partner', NULL, 1, 70, NULL, NULL),
(8, 'Landlord', 'landlord', NULL, 1, 80, NULL, NULL),
(9, 'Tenant', 'tenant', NULL, 1, 90, NULL, NULL),
(10, 'Employer', 'employer', NULL, 1, 100, NULL, NULL),
(11, 'Colleague', 'colleague', NULL, 1, 110, NULL, NULL),
(12, 'Supervisor', 'supervisor', NULL, 1, 120, NULL, NULL),
(13, 'Chief', 'chief', NULL, 1, 130, NULL, NULL),
(14, 'HOA', 'hoa', NULL, 1, 140, NULL, NULL),
(15, 'Neighbor', 'neighbor', NULL, 1, 150, NULL, NULL),
(16, 'Other', 'other', NULL, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `court_hearings`
--

CREATE TABLE `court_hearings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `legal_proceeding_id` bigint(20) UNSIGNED NOT NULL,
  `hearing_date` date NOT NULL,
  `hearing_time` time DEFAULT NULL,
  `court_room` varchar(100) DEFAULT NULL,
  `judge_name` varchar(255) DEFAULT NULL,
  `result` text DEFAULT NULL,
  `next_hearing_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_bureau_reports`
--

CREATE TABLE `credit_bureau_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bureau_name_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` enum('initial','updated','dispute','clearance') DEFAULT 'initial',
  `report_date` date NOT NULL,
  `report_reference` varchar(100) DEFAULT NULL,
  `credit_score` int(11) DEFAULT NULL,
  `credit_rating` varchar(50) DEFAULT NULL,
  `default_amount` decimal(15,2) DEFAULT NULL,
  `default_date` date DEFAULT NULL,
  `settlement_date` date DEFAULT NULL,
  `settlement_amount` decimal(15,2) DEFAULT NULL,
  `is_disputed` tinyint(1) DEFAULT 0,
  `dispute_reason` text DEFAULT NULL,
  `dispute_resolution` text DEFAULT NULL,
  `reporting_status` enum('reported','disputed','resolved','cleared') DEFAULT 'reported',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `customer_health_scorecard`
-- (See below for the actual view)
--
CREATE TABLE `customer_health_scorecard` (
`user_id` bigint(20) unsigned
,`name` varchar(255)
,`phone` varchar(255)
,`total_loans` bigint(21)
,`total_principal_borrowed` decimal(37,2)
,`count_rollovers` decimal(22,0)
,`count_discounts` decimal(22,0)
,`count_bad_debts` decimal(22,0)
,`health_score` decimal(25,0)
,`health_grade` varchar(18)
,`is_borrower_active` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `debt_recovery_cases`
--

CREATE TABLE `debt_recovery_cases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `case_number` varchar(50) NOT NULL,
  `total_debt_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `principal_outstanding` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_outstanding` decimal(15,2) NOT NULL DEFAULT 0.00,
  `penalty_outstanding` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fees_outstanding` decimal(15,2) NOT NULL DEFAULT 0.00,
  `default_date` date NOT NULL,
  `days_in_default` int(11) NOT NULL DEFAULT 0,
  `status_id` bigint(20) UNSIGNED NOT NULL,
  `priority_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `last_contact_date` date DEFAULT NULL,
  `next_action_date` date DEFAULT NULL,
  `recovery_strategy` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `debt_recovery_cases`
--

INSERT INTO `debt_recovery_cases` (`id`, `user_id`, `loan_id`, `case_number`, `total_debt_amount`, `principal_outstanding`, `interest_outstanding`, `penalty_outstanding`, `fees_outstanding`, `default_date`, `days_in_default`, `status_id`, `priority_id`, `assigned_to`, `last_contact_date`, `next_action_date`, `recovery_strategy`, `notes`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 13, 299, 'DR-2026-0001', 16111.99, 53000.00, 3000.00, 0.00, 0.00, '2026-08-06', 0, 5, 3, NULL, NULL, NULL, NULL, 'Recovery case created from defaulted loan #299', 1, 1, '2026-08-06 13:12:50', '2026-09-09 17:22:27', NULL),
(2, 50, 461, 'DR-2026-0002', 188609.40, 136960.00, 51360.00, 0.00, 0.00, '2026-08-06', 0, 1, 3, NULL, NULL, NULL, NULL, 'Recovery case created from defaulted loan #461', 1, NULL, '2026-08-06 13:12:50', '2026-08-06 13:12:50', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `debt_recovery_summary`
-- (See below for the actual view)
--
CREATE TABLE `debt_recovery_summary` (
`case_id` bigint(20) unsigned
,`case_number` varchar(50)
,`user_id` bigint(20) unsigned
,`debtor_name` varchar(255)
,`debtor_email` varchar(255)
,`debtor_phone` varchar(255)
,`total_debt_amount` decimal(15,2)
,`principal_outstanding` decimal(15,2)
,`interest_outstanding` decimal(15,2)
,`penalty_outstanding` decimal(15,2)
,`fees_outstanding` decimal(15,2)
,`default_date` date
,`days_in_default` int(11)
,`status` varchar(100)
,`priority` varchar(100)
,`assigned_to` bigint(20) unsigned
,`last_contact_date` date
,`next_action_date` date
,`total_recovered` decimal(37,2)
,`total_actions` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `loan_cycle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `processing_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT NULL,
  `transaction` varchar(255) DEFAULT NULL,
  `mode` varchar(255) NOT NULL,
  `disburse_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `partner_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `funding_source` enum('internal','partner','mixed') DEFAULT 'internal',
  `investment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `disburse_date`, `payment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `funding_source`, `investment_id`, `notes`) VALUES
(1, 1, 1, 80000.00, 0.00, NULL, '089092507904', 'bank_transfer', '2025-03-07', '2025-03-17', '2025-04-17 12:15:36', '2025-04-17 12:15:36', NULL, NULL, 'internal', NULL, NULL),
(2, 1, 1, 40000.00, 0.00, NULL, '774132228868', 'bank_transfer', '2025-03-10', '2025-03-17', '2025-04-17 12:17:59', '2025-04-17 12:17:59', NULL, NULL, 'internal', NULL, NULL),
(3, 2, 2, 30000.00, 0.00, NULL, '089092507904', 'bank_transfer', '2025-03-07', '2025-03-17', '2025-04-17 12:23:21', '2025-04-17 12:23:21', NULL, NULL, 'internal', NULL, NULL),
(4, 5, 5, 10000.00, 0.00, NULL, 'TC327JWW4I', 'bank_transfer', '2025-03-02', '2025-04-02', '2025-04-17 13:05:38', '2025-04-17 13:05:38', NULL, NULL, 'internal', NULL, NULL),
(5, 7, 7, 2000.00, 0.00, NULL, 'TDA285BOZO', 'bank_transfer', '2025-04-10', '2025-04-20', '2025-04-17 13:21:15', '2025-04-17 13:21:15', NULL, NULL, 'internal', NULL, NULL),
(6, 8, 8, 10000.00, 0.00, NULL, 'TDC6HFHSZO', 'bank_transfer', '2025-04-12', '2025-04-22', '2025-04-17 13:24:20', '2025-04-17 13:24:20', NULL, NULL, 'internal', NULL, NULL),
(7, 10, 10, 30000.00, 0.00, NULL, 'TDG1YOIU2X', 'bank_transfer', '2025-04-16', '2025-04-26', '2025-04-17 13:28:18', '2025-04-17 13:28:18', NULL, NULL, 'internal', NULL, NULL),
(8, 11, 11, 15000.00, 0.00, NULL, 'TD88X9BH3K', 'bank_transfer', '2025-04-08', '2025-04-18', '2025-04-17 13:30:49', '2025-04-17 13:30:49', NULL, NULL, 'internal', NULL, NULL),
(9, 12, 12, 24000.00, 0.00, NULL, '395833072695', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-17 14:09:26', '2025-04-17 14:09:26', NULL, NULL, 'internal', NULL, NULL),
(10, 12, 12, 3000.00, 0.00, NULL, 'TDG6XXEGTQ', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-17 14:10:02', '2025-04-17 14:10:02', NULL, NULL, 'internal', NULL, NULL),
(11, 13, 13, 30000.00, 0.00, NULL, 'TDD7JVSCKR', 'bank_transfer', '2025-04-13', '2025-04-23', '2025-04-17 14:34:42', '2025-04-17 14:34:42', NULL, NULL, 'internal', NULL, NULL),
(12, 4, 4, 35000.00, 0.00, NULL, '217335052085', 'bank_transfer', '2025-03-31', '2025-04-09', '2025-04-17 15:53:10', '2025-04-17 15:53:10', NULL, NULL, 'internal', NULL, NULL),
(13, 3, 3, 50000.00, 0.00, NULL, '558970234000', 'bank_transfer', '2025-03-18', '2025-04-28', '2025-04-19 17:52:00', '2025-04-19 17:52:00', NULL, NULL, 'internal', NULL, NULL),
(14, 14, 14, 20000.00, 0.00, NULL, 'TCP62JTJXE', 'bank_transfer', '2025-03-25', '2025-04-05', '2025-04-20 12:28:49', '2025-04-20 12:28:49', NULL, NULL, 'internal', NULL, NULL),
(15, 15, 15, 50000.00, 0.00, NULL, 'TCP4ZP7SRW', 'bank_transfer', '2025-03-25', '2025-04-03', '2025-04-20 12:43:18', '2025-04-20 12:43:18', NULL, NULL, 'internal', NULL, NULL),
(16, 16, 16, 50000.00, 0.00, NULL, '292498090877', 'bank_transfer', '2025-04-01', '2025-04-03', '2025-04-20 12:44:03', '2025-04-20 12:44:03', NULL, NULL, 'internal', NULL, NULL),
(17, 18, 18, 35000.00, 0.00, NULL, '217335052085', 'bank_transfer', '2025-03-31', '2025-04-09', '2025-04-20 13:27:14', '2025-04-20 13:27:14', NULL, NULL, 'internal', NULL, NULL),
(18, 19, 19, 20000.00, 0.00, NULL, 'TD75RQ3FKJ', 'bank_transfer', '2025-04-07', '2025-04-17', '2025-04-20 13:46:23', '2025-04-20 13:46:23', NULL, NULL, 'internal', NULL, NULL),
(19, 20, 20, 5000.00, 0.00, NULL, 'TDB3BIBKHB', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:02:11', '2025-04-20 14:02:11', NULL, NULL, 'internal', NULL, NULL),
(20, 20, 20, 28000.00, 0.00, NULL, 'CARRY FORWARD', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:02:34', '2025-04-20 14:02:34', NULL, NULL, 'internal', NULL, NULL),
(21, 21, 21, 42000.00, 0.00, NULL, 'CARRY FORWARD', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:03:16', '2025-04-20 14:03:16', NULL, NULL, 'internal', NULL, NULL),
(22, 22, 22, 10000.00, 0.00, NULL, 'TBO7A3XVLJ', 'bank_transfer', '2025-03-05', '2025-03-10', '2025-04-20 16:21:15', '2025-04-20 16:21:15', NULL, NULL, 'internal', NULL, NULL),
(23, 23, 23, 5000.00, 0.00, NULL, 'TBQ0LAB684', 'bank_transfer', '2025-02-26', '2025-03-06', '2025-04-20 16:22:38', '2025-04-20 16:22:38', NULL, NULL, 'internal', NULL, NULL),
(24, 31, 31, 50000.00, 0.00, NULL, 'TD77RZENUN', 'bank_transfer', '2025-04-07', '2025-04-17', '2025-04-20 17:16:41', '2025-04-20 17:16:41', NULL, NULL, 'internal', NULL, NULL),
(25, 32, 32, 8000.00, 0.00, NULL, '924920143186', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-04-20 17:23:18', '2025-04-20 17:23:18', NULL, NULL, 'internal', NULL, NULL),
(27, 32, 32, 11000.00, 0.00, NULL, 'TD41DEBIRX', 'bank_transfer', '2025-04-05', '2025-06-04', '2025-04-20 17:27:58', '2025-04-20 17:27:58', NULL, NULL, 'internal', NULL, NULL),
(28, 28, 28, 50000.00, 0.00, NULL, '931800872968', 'bank_transfer', '2025-04-04', '2025-04-14', '2025-04-20 17:34:03', '2025-04-20 17:34:03', NULL, NULL, 'internal', NULL, NULL),
(29, 25, 25, 1500.00, 0.00, NULL, '953302924475', 'bank_transfer', '2025-03-17', '2025-04-17', '2025-04-20 17:51:03', '2025-04-20 17:51:03', NULL, NULL, 'internal', NULL, NULL),
(30, 25, 25, 2000.00, 0.00, NULL, '631607883431', 'bank_transfer', '2025-03-10', '2025-03-10', '2025-04-20 17:52:34', '2025-04-20 17:52:34', NULL, NULL, 'internal', NULL, NULL),
(31, 25, 25, 7500.00, 0.00, NULL, 'NA', 'bank_transfer', '2025-03-27', '2025-04-27', '2025-04-20 17:56:30', '2025-04-20 17:56:30', NULL, NULL, 'internal', NULL, NULL),
(32, 17, 17, 20000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-04-12', '2025-04-25', '2025-04-21 06:13:22', '2025-04-21 06:13:22', NULL, NULL, 'internal', NULL, NULL),
(33, 33, 33, 10000.00, 0.00, NULL, '201606029320', 'bank_transfer', '2025-03-07', '2025-04-07', '2025-04-21 06:44:17', '2025-04-21 06:44:17', NULL, NULL, 'internal', NULL, NULL),
(34, 29, 29, 11000.00, 0.00, NULL, 'TD48EV29NE', 'bank_transfer', '2025-04-04', '2025-05-05', '2025-04-21 06:46:22', '2025-04-21 06:46:22', NULL, NULL, 'internal', NULL, NULL),
(35, 36, 36, 44000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-03-21', '2025-04-01', '2025-04-21 09:03:06', '2025-04-21 09:03:06', NULL, NULL, 'internal', NULL, NULL),
(36, 26, 26, 600.00, 0.00, NULL, 'I&M Bank', 'bank_transfer', '2025-04-21', '2025-05-21', '2025-04-21 10:06:22', '2025-04-21 10:06:22', NULL, NULL, 'internal', NULL, NULL),
(37, 34, 34, 9000.00, 0.00, NULL, 'TC72SFKDOC', 'bank_transfer', '2025-03-07', '2025-04-07', '2025-04-21 10:15:26', '2025-04-21 10:15:26', NULL, NULL, 'internal', NULL, NULL),
(38, 35, 35, 18000.00, 0.00, NULL, 'TD88X9BH3K', 'bank_transfer', '2025-04-08', '2025-04-18', '2025-04-26 12:18:51', '2025-04-26 12:18:51', NULL, NULL, 'internal', NULL, NULL),
(39, 38, 38, 50000.00, 0.00, NULL, 'TDO6ZOZCH8', 'bank_transfer', '2025-04-24', '2025-05-04', '2025-04-27 06:13:42', '2025-04-27 06:13:42', NULL, NULL, 'internal', NULL, NULL),
(40, 9, 9, 15000.00, 0.00, NULL, 'TDH944K3NZ', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-27 06:24:40', '2025-04-27 06:24:40', NULL, NULL, 'internal', NULL, NULL),
(41, 37, 37, 5000.00, 0.00, NULL, 'TDP54QZWX5', 'bank_transfer', '2025-04-25', '2025-05-04', '2025-04-27 06:35:50', '2025-04-27 06:35:50', NULL, NULL, 'internal', NULL, NULL),
(42, 39, 39, 15000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-04-27', '2025-05-06', '2025-04-27 06:36:44', '2025-04-27 06:36:44', NULL, NULL, 'internal', NULL, NULL),
(43, 45, 42, 50000.00, 0.00, NULL, 'TDT4NOO6SO', 'bank_transfer', '2025-04-29', '2025-05-08', '2025-04-29 06:15:33', '2025-04-29 06:15:33', NULL, NULL, 'internal', NULL, NULL),
(44, 46, 43, 50000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-05-03', '2025-05-13', '2025-05-02 19:35:39', '2025-05-02 19:35:39', NULL, NULL, 'internal', NULL, NULL),
(45, 24, 24, 5000.00, 0.00, NULL, 'TC81UVVMLL', 'bank_transfer', '2025-03-08', '2025-04-08', '2025-05-05 09:54:54', '2025-05-05 09:54:54', NULL, NULL, 'internal', NULL, NULL),
(46, 30, 30, 6000.00, 0.00, NULL, 'TD40EV16DK', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-05 09:57:17', '2025-05-05 09:57:17', NULL, NULL, 'internal', NULL, NULL),
(47, 27, 27, 2000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-03-15', '2025-04-15', '2025-05-05 19:10:42', '2025-05-05 19:10:42', NULL, NULL, 'internal', NULL, NULL),
(48, 41, 41, 8000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-05 19:24:01', '2025-05-05 19:24:01', NULL, NULL, 'internal', NULL, NULL),
(49, 49, 45, 50000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-06 08:08:58', '2025-05-06 08:08:58', NULL, NULL, 'internal', NULL, NULL),
(50, 48, 44, 5000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-05-05', '2025-05-15', '2025-05-06 08:10:53', '2025-05-06 08:10:53', NULL, NULL, 'internal', NULL, NULL),
(51, 55, 50, 15000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-06 08:11:13', '2025-05-06 08:11:13', NULL, NULL, 'internal', NULL, NULL),
(52, 50, 46, 100000.00, 0.00, NULL, '708629756357', 'bank_transfer', '2025-05-06', '2025-05-16', '2025-05-06 08:19:23', '2025-05-06 08:19:23', NULL, NULL, 'internal', NULL, NULL),
(53, 6, 6, 13000.00, 0.00, NULL, 'TD51HKJVG3', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-06 08:47:13', '2025-05-06 08:47:13', NULL, NULL, 'internal', NULL, NULL),
(54, 52, 48, 8000.00, 0.00, NULL, 'TE69MVN0YJ', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-07 12:08:49', '2025-05-07 12:08:49', NULL, NULL, 'internal', NULL, NULL),
(55, 54, 49, 10000.00, 0.00, NULL, '870655202723', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-07 12:11:21', '2025-05-07 12:11:21', NULL, NULL, 'internal', NULL, NULL),
(56, 56, 51, 20000.00, 0.00, NULL, '861188683363', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-07 14:55:21', '2025-05-07 14:55:21', NULL, NULL, 'internal', NULL, NULL),
(57, 56, 51, 10000.00, 0.00, NULL, '033759695090', 'bank_transfer', '2025-05-09', '2025-05-17', '2025-05-09 13:12:08', '2025-05-09 13:12:08', NULL, NULL, 'internal', NULL, NULL),
(58, 57, 52, 20000.00, 0.00, NULL, '033759695090', 'bank_transfer', '2025-05-10', '2025-05-15', '2025-05-10 10:39:09', '2025-05-10 10:39:09', NULL, NULL, 'internal', NULL, NULL),
(59, 57, 52, 5000.00, 0.00, NULL, '395608495147', 'bank_transfer', '2025-05-10', '2025-05-20', '2025-05-11 19:39:14', '2025-05-11 19:39:14', NULL, NULL, 'internal', NULL, NULL),
(60, 51, 47, 15600.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-04', '2025-05-14', '2025-05-12 09:09:46', '2025-05-12 09:09:46', NULL, NULL, 'internal', NULL, NULL),
(61, 60, 54, 10000.00, 0.00, NULL, 'TED5J8LN27', 'bank_transfer', '2025-05-13', '2025-05-23', '2025-05-13 06:52:42', '2025-05-13 06:52:42', NULL, NULL, 'internal', NULL, NULL),
(62, 60, 54, 40000.00, 0.00, NULL, 'TEE8PETPNM', 'bank_transfer', '2025-05-14', '2025-05-24', '2025-05-15 07:47:47', '2025-05-15 07:47:47', NULL, NULL, 'internal', NULL, NULL),
(63, 64, 56, 18720.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-15', '2025-06-15', '2025-05-15 07:51:38', '2025-05-15 07:51:38', NULL, NULL, 'internal', NULL, NULL),
(64, 61, 55, 72000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-15', '2025-05-25', '2025-05-15 07:53:56', '2025-05-15 07:53:56', NULL, NULL, 'internal', NULL, NULL),
(65, 65, 57, 3500.00, 0.00, NULL, 'TEE3PLCZMX', 'bank_transfer', '2025-05-14', '2025-05-28', '2025-05-15 07:58:42', '2025-05-15 07:58:42', NULL, NULL, 'internal', NULL, NULL),
(66, 65, 57, 7500.00, 0.00, NULL, 'TEE9PLMODT', 'bank_transfer', '2025-05-14', '2025-05-28', '2025-05-15 07:59:10', '2025-05-15 07:59:10', NULL, NULL, 'internal', NULL, NULL),
(67, 65, 57, 2000.00, 0.00, NULL, 'TEF3T3HX6F', 'bank_transfer', '2025-05-15', '2025-05-28', '2025-05-15 08:06:48', '2025-05-15 08:06:48', NULL, NULL, 'internal', NULL, NULL),
(68, 66, 58, 2500.00, 0.00, NULL, 'TEE7POIQX1', 'bank_transfer', '2025-05-14', '2025-05-25', '2025-05-15 08:11:40', '2025-05-15 08:11:40', NULL, NULL, 'internal', NULL, NULL),
(69, 59, 53, 78000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-12', '2025-05-22', '2025-05-18 08:53:52', '2025-05-18 08:53:52', NULL, NULL, 'internal', NULL, NULL),
(70, 69, 60, 50000.00, 0.00, NULL, '656947222762', 'bank_transfer', '2025-05-19', '2025-06-02', '2025-05-19 12:48:06', '2025-05-19 12:48:06', NULL, NULL, 'internal', NULL, NULL),
(71, 70, 61, 50000.00, 0.00, NULL, 'TEK7JGH64Z', 'bank_transfer', '2025-05-20', '2025-06-03', '2025-05-20 13:04:27', '2025-05-20 13:04:27', NULL, NULL, 'internal', NULL, NULL),
(72, 57, 52, 20000.00, 0.00, NULL, 'BROKER', 'bank_transfer', '2025-05-10', '2025-05-20', '2025-05-22 05:25:22', '2025-05-22 05:25:22', NULL, NULL, 'internal', NULL, NULL),
(73, 71, 62, 15000.00, 0.00, NULL, 'I&M BANK', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 10:08:25', '2025-05-26 10:08:25', NULL, NULL, 'internal', NULL, NULL),
(74, 72, 63, 27840.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 10:33:49', '2025-05-26 10:33:49', NULL, NULL, 'internal', NULL, NULL),
(75, 73, 64, 10000.00, 0.00, NULL, 'TEP73R8FOP', 'bank_transfer', '2025-05-24', '2025-06-03', '2025-05-26 11:53:15', '2025-05-26 11:53:15', NULL, NULL, 'internal', NULL, NULL),
(76, 74, 65, 50000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 18:05:53', '2025-05-26 18:05:53', NULL, NULL, 'internal', NULL, NULL),
(77, 75, 66, 30000.00, 0.00, NULL, 'TER3DM6HRD', 'Mpesa', '2025-05-27', '2025-06-27', '2025-05-27 06:22:50', '2025-05-28 16:24:08', NULL, NULL, 'internal', NULL, NULL),
(78, 76, 67, 15000.00, 0.00, NULL, 'TER5G1S7T9', 'bank_transfer', '2025-05-27', '2025-06-10', '2025-05-27 13:52:11', '2025-05-27 13:52:11', NULL, NULL, 'internal', NULL, NULL),
(79, 32, 32, 10000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-27 16:54:01', '2025-05-27 16:54:01', NULL, NULL, 'internal', NULL, NULL),
(80, 68, 59, 10000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-17', '2025-05-27', '2025-05-27 16:55:40', '2025-05-27 16:55:40', NULL, NULL, 'internal', NULL, NULL),
(81, 40, 40, 15600.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-27 16:57:04', '2025-05-27 16:57:04', NULL, NULL, 'internal', NULL, NULL),
(82, 77, 68, 30000.00, 0.00, NULL, 'TET8NAA9S8', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-29 07:39:23', '2025-05-29 07:39:23', NULL, NULL, 'internal', NULL, NULL),
(83, 78, 69, 100000.00, 0.00, NULL, 'TET4ON8Q6C', 'bank_transfer', '2025-05-29', '2025-08-29', '2025-05-29 12:11:50', '2025-05-29 12:11:50', NULL, NULL, 'internal', NULL, NULL),
(84, 79, 70, 20000.00, 0.00, NULL, 'TET7P9LW5Z', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-29 13:50:53', '2025-05-29 13:50:53', NULL, NULL, 'internal', NULL, NULL),
(85, 80, 71, 27340.00, 0.00, NULL, 'TEU8TS3A9E', 'Mpesa', '2025-05-30', '2025-06-30', '2025-05-30 15:30:37', '2025-05-30 15:31:08', NULL, NULL, 'internal', NULL, NULL),
(86, 80, 71, 2660.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-30', '2025-06-30', '2025-05-30 15:31:43', '2025-05-30 15:31:43', NULL, NULL, 'internal', NULL, NULL),
(87, 82, 72, 30000.00, 0.00, NULL, 'TEV8WMXR72', 'bank_transfer', '2025-05-31', '2025-06-09', '2025-05-31 09:42:00', '2025-05-31 09:42:00', NULL, NULL, 'internal', NULL, NULL),
(88, 83, 73, 8200.00, 0.00, NULL, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-31 11:17:58', '2025-05-31 11:17:58', NULL, NULL, 'internal', NULL, NULL),
(89, 84, 74, 20000.00, 0.00, NULL, 'TF258AV9LV', 'bank_transfer', '2025-06-02', '2025-06-16', '2025-06-02 11:20:46', '2025-06-02 11:20:46', NULL, NULL, 'internal', NULL, NULL),
(90, 85, 75, 50000.00, 0.00, NULL, '004637299558', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 07:26:03', '2025-06-03 07:26:03', NULL, NULL, 'internal', NULL, NULL),
(91, 86, 76, 10000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 08:12:46', '2025-06-03 08:12:46', NULL, NULL, 'internal', NULL, NULL),
(92, 79, 70, 10000.00, 0.00, NULL, 'TF34EJ4JV2', 'bank_transfer', '2025-06-03', '2025-06-08', '2025-06-03 15:40:48', '2025-06-03 15:40:48', NULL, NULL, 'internal', NULL, NULL),
(93, 87, 77, 50000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-06-04', '2025-06-18', '2025-06-03 15:43:43', '2025-06-03 15:43:43', NULL, NULL, 'internal', NULL, NULL),
(94, 85, 75, 50000.00, 0.00, NULL, '611992535256', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 19:06:07', '2025-06-03 19:06:07', NULL, NULL, 'internal', NULL, NULL),
(95, 88, 78, 18000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'cash', '2025-06-06', '2025-06-16', '2025-06-06 08:01:13', '2025-06-06 08:01:13', NULL, NULL, 'internal', NULL, NULL),
(96, 88, 78, 1500.00, 0.00, NULL, 'TF55OO6MNL', 'bank_transfer', '2025-06-05', '2025-06-16', '2025-06-06 08:01:55', '2025-06-06 08:01:55', NULL, NULL, 'internal', NULL, NULL),
(97, 88, 78, 8500.00, 0.00, NULL, 'to update', 'Mpesa', '2025-06-06', '2025-06-16', '2025-06-06 08:03:55', '2025-06-07 09:43:30', NULL, NULL, 'internal', NULL, NULL),
(98, 79, 70, 5000.00, 0.00, NULL, 'TF75W10V2T', 'bank_transfer', '2025-06-07', '2025-06-08', '2025-06-07 09:46:01', '2025-06-07 09:46:01', NULL, NULL, 'internal', NULL, NULL),
(99, 90, 80, 39600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-09', '2025-06-19', '2025-06-10 05:09:19', '2025-06-10 05:09:19', NULL, NULL, 'internal', NULL, NULL),
(100, 91, 81, 15000.00, 0.00, NULL, 'TFE9WO49JV', 'Mpesa', '2025-06-14', '2025-06-24', '2025-06-15 14:50:21', '2025-07-15 11:47:48', NULL, NULL, 'internal', NULL, NULL),
(101, 93, 83, 17000.00, 0.00, NULL, 'TFD3PFNLOP', 'cash', '2025-06-13', '2025-06-23', '2025-06-15 13:51:14', '2025-06-15 13:51:14', NULL, NULL, 'internal', NULL, NULL),
(102, 92, 82, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-13', '2025-06-23', '2025-06-15 13:52:15', '2025-06-15 13:52:15', NULL, NULL, 'internal', NULL, NULL),
(103, 89, 79, 8280.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-06-08', '2025-06-18', '2025-06-15 13:54:20', '2025-06-20 07:02:39', NULL, NULL, 'internal', NULL, NULL),
(104, 94, 84, 4950.00, 0.00, NULL, 'TFC5M5SV59', 'cash', '2025-06-12', '2025-06-12', '2025-06-15 13:57:45', '2025-06-15 13:57:45', NULL, NULL, 'internal', NULL, NULL),
(105, 94, 84, 50.00, 0.00, NULL, 'TFF2179XC0', 'Mpesa', '2025-06-15', '2025-06-22', '2025-06-15 14:03:08', '2025-06-15 14:06:12', NULL, NULL, 'internal', NULL, NULL),
(106, 96, 86, 33600.00, 0.00, NULL, 'ROLL OVER', 'Cash', '2025-06-18', '2025-06-29', '2025-06-18 09:59:17', '2025-06-18 10:01:05', NULL, NULL, 'internal', NULL, NULL),
(107, 96, 86, 47520.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-19', '2025-06-29', '2025-06-18 09:59:54', '2025-06-18 09:59:54', NULL, NULL, 'internal', NULL, NULL),
(108, 95, 85, 2264.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-18', '2025-07-18', '2025-06-18 14:04:44', '2025-06-18 14:04:44', NULL, NULL, 'internal', NULL, NULL),
(109, 98, 88, 14000.00, 0.00, NULL, 'TFL9QT05P9', 'Mpesa', '2025-06-21', '2025-07-04', '2025-06-22 15:14:45', '2025-06-22 15:15:31', NULL, NULL, 'internal', NULL, NULL),
(110, 98, 88, 4000.00, 0.00, NULL, 'TFK9PL1O67', 'bank_transfer', '2025-06-20', '2025-07-04', '2025-06-22 15:15:20', '2025-06-22 15:15:20', NULL, NULL, 'internal', NULL, NULL),
(111, 98, 88, 5000.00, 0.00, NULL, 'TFL9R15GV9', 'bank_transfer', '2025-06-21', '2025-07-04', '2025-06-22 15:16:20', '2025-06-22 15:16:20', NULL, NULL, 'internal', NULL, NULL),
(112, 98, 88, 5000.00, 0.00, NULL, 'TFL1S1NODH', 'bank_transfer', '2025-06-21', '2025-07-04', '2025-06-22 15:16:26', '2025-06-22 15:16:26', NULL, NULL, 'internal', NULL, NULL),
(113, 100, 90, 5000.00, 0.00, NULL, 'TFM1Y12185', 'cash', '2025-06-22', '2025-07-02', '2025-06-23 10:28:41', '2025-06-23 10:28:41', NULL, NULL, 'internal', NULL, NULL),
(114, 101, 91, 10000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2025-06-24', '2025-07-04', '2025-06-24 06:22:21', '2025-06-24 06:22:21', NULL, NULL, 'internal', NULL, NULL),
(115, 102, 92, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-24', '2025-07-04', '2025-06-25 10:45:03', '2025-06-25 10:45:03', NULL, NULL, 'internal', NULL, NULL),
(116, 103, 93, 5000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-27', '2025-07-08', '2025-06-27 17:20:56', '2025-06-27 17:20:56', NULL, NULL, 'internal', NULL, NULL),
(117, 104, 94, 5000.00, 0.00, NULL, 'TFT5VGPQL1', 'bank_transfer', '2025-06-29', '2025-07-09', '2025-06-30 07:12:08', '2025-06-30 07:12:08', NULL, NULL, 'internal', NULL, NULL),
(118, 105, 95, 120000.00, 0.00, NULL, 'TFU7YU7PK1', 'bank_transfer', '2025-06-30', '2025-07-10', '2025-06-30 08:28:48', '2025-06-30 08:28:48', NULL, NULL, 'internal', NULL, NULL),
(119, 106, 96, 3936.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-18', '2025-06-28', '2025-06-30 08:33:26', '2025-06-30 08:33:26', NULL, NULL, 'internal', NULL, NULL),
(120, 107, 97, 30000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-30', '2025-07-30', '2025-07-01 07:30:22', '2025-07-01 07:30:22', NULL, NULL, 'internal', NULL, NULL),
(121, 108, 98, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-05', '2025-07-15', '2025-07-05 05:39:18', '2025-07-05 05:39:18', NULL, NULL, 'internal', NULL, NULL),
(122, 109, 99, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-05', '2025-07-15', '2025-07-05 05:41:12', '2025-07-05 05:41:12', NULL, NULL, 'internal', NULL, NULL),
(123, 110, 100, 10000.00, 0.00, NULL, 'TG74XZW748', 'cash', '2025-07-07', '2025-07-17', '2025-07-07 08:52:33', '2025-07-07 08:52:33', NULL, NULL, 'internal', NULL, NULL),
(124, 111, 101, 8000.00, 0.00, NULL, 'TG72XWWD34', 'bank_transfer', '2025-07-07', '2025-07-17', '2025-07-07 08:53:35', '2025-07-07 08:53:35', NULL, NULL, 'internal', NULL, NULL),
(125, 112, 102, 10000.00, 0.00, NULL, 'TG8845IBKA', 'bank_transfer', '2025-07-08', '2025-07-18', '2025-07-08 08:30:37', '2025-07-08 08:30:37', NULL, NULL, 'internal', NULL, NULL),
(126, 113, 103, 10000.00, 0.00, NULL, 'TG886TM5S8', 'bank_transfer', '2025-07-08', '2025-07-18', '2025-07-08 16:29:32', '2025-07-08 16:29:32', NULL, NULL, 'internal', NULL, NULL),
(127, 114, 104, 6000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-10', '2025-07-20', '2025-07-10 04:42:47', '2025-07-10 04:42:47', NULL, NULL, 'internal', NULL, NULL),
(129, 115, 105, 97344.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-30', '0025-07-10', '2025-07-10 04:55:55', '2025-07-10 04:55:55', NULL, NULL, 'internal', NULL, NULL),
(130, 116, 106, 39000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-02', '2025-08-02', '2025-07-10 05:06:13', '2025-07-10 05:06:13', NULL, NULL, 'internal', NULL, NULL),
(131, 117, 107, 120000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-11', '2025-07-21', '2025-07-13 16:06:56', '2025-07-13 16:06:56', NULL, NULL, 'internal', NULL, NULL),
(132, 118, 108, 2000.00, 0.00, NULL, 'TGC5Q6M32B', 'bank_transfer', '2025-07-12', '2025-07-22', '2025-07-13 16:12:43', '2025-07-13 16:12:43', NULL, NULL, 'internal', NULL, NULL),
(133, 119, 109, 1000.00, 0.00, NULL, 'TGE5Y2T5OB', 'bank_transfer', '2025-07-14', '2025-07-24', '2025-07-14 11:37:26', '2025-07-14 11:37:26', NULL, NULL, 'internal', NULL, NULL),
(134, 120, 110, 25000.00, 0.00, NULL, 'TGF33QRGEH', 'bank_transfer', '2025-07-15', '2025-07-29', '2025-07-15 11:05:03', '2025-07-15 11:05:03', NULL, NULL, 'internal', NULL, NULL),
(135, 121, 111, 70000.00, 0.00, NULL, '917753788306', 'bank_transfer', '2025-07-15', '2025-07-29', '2025-07-15 11:06:40', '2025-07-15 11:06:40', NULL, NULL, 'internal', NULL, NULL),
(136, 122, 112, 14400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-15', '2025-07-25', '2025-07-17 08:16:54', '2025-07-17 08:16:54', NULL, NULL, 'internal', NULL, NULL),
(137, 123, 113, 42900.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-14', '2025-07-28', '2025-07-17 08:24:01', '2025-07-17 08:24:01', NULL, NULL, 'internal', NULL, NULL),
(138, 124, 114, 56628.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-14', '2025-07-28', '2025-07-17 08:27:43', '2025-07-17 08:27:43', NULL, NULL, 'internal', NULL, NULL),
(139, 125, 115, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-17', '2025-07-27', '2025-07-17 09:52:26', '2025-07-17 09:52:26', NULL, NULL, 'internal', NULL, NULL),
(140, 97, 87, 50000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-06-18', '2025-07-18', '2025-07-17 09:53:12', '2025-07-17 09:53:12', NULL, NULL, 'internal', NULL, NULL),
(141, 126, 116, 5000.00, 0.00, NULL, 'TGG08OUQHU', 'cash', '2025-07-16', '2025-07-26', '2025-07-17 09:56:54', '2025-07-17 09:56:54', NULL, NULL, 'internal', NULL, NULL),
(142, 127, 117, 20000.00, 0.00, NULL, 'TGH9DWFM6L', 'bank_transfer', '2025-07-17', '2025-07-27', '2025-07-18 08:34:36', '2025-07-18 08:34:36', NULL, NULL, 'internal', NULL, NULL),
(143, 128, 118, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-18', '2025-07-28', '2025-07-18 08:36:34', '2025-07-18 08:36:34', NULL, NULL, 'internal', NULL, NULL),
(144, 129, 119, 7200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-21', '2025-07-31', '2025-07-21 06:45:05', '2025-07-21 06:45:05', NULL, NULL, 'internal', NULL, NULL),
(145, 130, 120, 17280.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-25', '2025-08-05', '2025-07-27 09:00:46', '2025-07-27 09:00:46', NULL, NULL, 'internal', NULL, NULL),
(146, 131, 121, 4000.00, 0.00, NULL, 'TGM2ZLSC7O', 'bank_transfer', '2025-07-20', '2025-08-01', '2025-07-28 14:11:15', '2025-07-28 14:11:15', NULL, NULL, 'internal', NULL, NULL),
(147, 134, 123, 30000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-30', '2025-08-31', '2025-07-30 07:39:59', '2025-07-30 07:39:59', NULL, NULL, 'internal', NULL, NULL),
(148, 135, 124, 2400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-29', '2025-08-09', '2025-07-30 14:22:07', '2025-07-30 14:22:07', NULL, NULL, 'internal', NULL, NULL),
(149, 136, 125, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-28', '2025-08-08', '2025-07-30 15:35:17', '2025-07-30 15:35:17', NULL, NULL, 'internal', NULL, NULL),
(150, 137, 126, 35000.00, 0.00, NULL, 'TGU25NSDJE', 'bank_transfer', '2025-07-30', '2025-08-09', '2025-07-31 08:46:01', '2025-07-31 08:46:01', NULL, NULL, 'internal', NULL, NULL),
(151, 138, 127, 45000.00, 0.00, NULL, 'TH16EFCEEM', 'bank_transfer', '2025-08-01', '2025-09-01', '2025-08-01 07:01:43', '2025-08-01 07:01:43', NULL, NULL, 'internal', NULL, NULL),
(152, 139, 128, 30000.00, 0.00, NULL, '028150735663', 'bank_transfer', '2025-07-31', '2025-08-31', '2025-08-01 10:18:09', '2025-08-01 10:18:09', NULL, NULL, 'internal', NULL, NULL),
(153, 140, 129, 3000.00, 0.00, NULL, 'TH27MT6MTT', 'bank_transfer', '2025-08-02', '2025-08-12', '2025-08-02 16:04:51', '2025-08-02 16:04:51', NULL, NULL, 'internal', NULL, NULL),
(154, 141, 130, 6000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-08-04', '2025-08-14', '2025-08-05 08:12:07', '2025-08-15 05:31:45', NULL, NULL, 'internal', NULL, NULL),
(155, 142, 131, 3000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-27', '2025-08-06', '2025-08-06 05:43:16', '2025-08-06 05:43:16', NULL, NULL, 'internal', NULL, NULL),
(156, 143, 132, 30000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-07-30', '2025-08-13', '2025-08-06 11:02:51', '2025-08-06 11:03:28', NULL, NULL, 'internal', NULL, NULL),
(157, 144, 133, 50000.00, 0.00, NULL, 'TH654PRH61', 'Bank', '2025-08-06', '2025-08-20', '2025-08-06 11:26:52', '2025-08-06 11:27:04', NULL, NULL, 'internal', NULL, NULL),
(158, 144, 133, 60000.00, 0.00, NULL, 'TH6151984J', 'bank_transfer', '2025-08-06', '2025-08-20', '2025-08-06 11:27:40', '2025-08-06 11:27:40', NULL, NULL, 'internal', NULL, NULL),
(159, 145, 134, 3000.00, 0.00, NULL, 'MPESA', 'bank_transfer', '2025-08-06', '2025-09-06', '2025-08-07 03:43:10', '2025-08-07 03:43:10', NULL, NULL, 'internal', NULL, NULL),
(160, 144, 133, 150000.00, 0.00, NULL, 'TH71BUDC7N', 'bank_transfer', '2025-08-07', '2025-08-21', '2025-08-07 11:19:46', '2025-08-07 11:19:46', NULL, NULL, 'internal', NULL, NULL),
(161, 146, 135, 14400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-08', '2025-08-18', '2025-08-07 17:09:22', '2025-08-07 17:09:22', NULL, NULL, 'internal', NULL, NULL),
(162, 147, 136, 67954.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-29', '2025-08-11', '2025-08-07 18:48:37', '2025-08-07 18:48:37', NULL, NULL, 'internal', NULL, NULL),
(163, 144, 133, 40000.00, 0.00, NULL, 'TH82GJ735C', 'bank_transfer', '2025-08-08', '2025-08-20', '2025-08-08 10:24:16', '2025-08-08 10:24:16', NULL, NULL, 'internal', NULL, NULL),
(164, 146, 135, 2000.00, 0.00, NULL, 'TH87GJ96CZ', 'bank_transfer', '2025-08-08', '2025-08-18', '2025-08-08 10:26:40', '2025-08-08 10:26:40', NULL, NULL, 'internal', NULL, NULL),
(165, 148, 137, 10000.00, 0.00, NULL, 'TH73ARDOL3', 'bank_transfer', '2025-08-07', '2025-08-17', '2025-08-08 10:42:00', '2025-08-08 10:42:00', NULL, NULL, 'internal', NULL, NULL),
(166, 149, 138, 10000.00, 0.00, NULL, 'TH91M02HW3', 'bank_transfer', '2025-08-09', '2025-08-19', '2025-08-09 10:26:30', '2025-08-09 10:26:30', NULL, NULL, 'internal', NULL, NULL),
(167, 148, 137, 5000.00, 0.00, NULL, 'TH93M0B8ZR', 'bank_transfer', '2025-08-09', '2025-08-19', '2025-08-09 10:27:20', '2025-08-09 10:27:20', NULL, NULL, 'internal', NULL, NULL),
(168, 150, 139, 126548.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-07-31', '2025-08-31', '2025-08-11 08:06:54', '2025-08-11 08:06:54', NULL, NULL, 'internal', NULL, NULL),
(169, 151, 140, 66545.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-12', '2025-08-26', '2025-08-12 06:52:44', '2025-08-12 06:52:44', NULL, NULL, 'internal', NULL, NULL),
(170, 152, 141, 50700.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-03', '2025-09-03', '2025-08-12 06:56:10', '2025-08-12 06:56:10', NULL, NULL, 'internal', NULL, NULL),
(171, 158, 143, 1600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-06', '2025-08-16', '2025-08-14 10:12:14', '2025-08-14 10:12:14', NULL, NULL, 'internal', NULL, NULL),
(173, 159, 144, 6000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-14', '2025-08-24', '2025-08-15 05:34:47', '2025-08-15 05:34:47', NULL, NULL, 'internal', NULL, NULL),
(174, 161, 146, 10000.00, 0.00, NULL, 'THF9HDYCIF', 'bank_transfer', '2025-08-15', '2025-08-22', '2025-08-15 12:22:47', '2025-08-15 12:22:47', NULL, NULL, 'internal', NULL, NULL),
(175, 137, 126, 2500.00, 0.00, NULL, 'THI2XVXDZC', 'Mpesa', '2025-08-19', '2025-08-19', '2025-08-19 07:58:17', '2025-08-19 07:59:16', NULL, NULL, 'internal', NULL, NULL),
(176, 161, 146, 30000.00, 0.00, NULL, 'THJ71BMJLX', 'bank_transfer', '2025-08-19', '2025-08-22', '2025-08-19 10:50:33', '2025-08-19 10:50:33', NULL, NULL, 'internal', NULL, NULL),
(177, 163, 148, 19680.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-19', '2025-08-29', '2025-08-20 07:28:57', '2025-08-20 07:28:57', NULL, NULL, 'internal', NULL, NULL),
(178, 164, 149, 18000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-20', '2025-08-30', '2025-08-21 09:22:46', '2025-08-21 09:22:46', NULL, NULL, 'internal', NULL, NULL),
(179, 161, 146, 5000.00, 0.00, NULL, 'THL1AB5SIJ', 'bank_transfer', '2025-08-21', '2025-08-29', '2025-08-21 09:23:57', '2025-08-21 09:23:57', NULL, NULL, 'internal', NULL, NULL),
(180, 161, 146, 5000.00, 0.00, NULL, 'THM7FTOHU7', 'bank_transfer', '2025-08-22', '2025-09-15', '2025-08-22 12:53:08', '2025-08-22 12:53:08', NULL, NULL, 'internal', NULL, NULL),
(181, 156, 142, 10000.00, 0.00, NULL, 'THM3FTUGBR', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:01:00', '2025-08-22 13:01:00', NULL, NULL, 'internal', NULL, NULL),
(182, 165, 150, 10000.00, 0.00, NULL, 'THM1GDWOKL', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:02:12', '2025-08-22 13:02:12', NULL, NULL, 'internal', NULL, NULL),
(183, 165, 150, 5000.00, 0.00, NULL, 'THM1EJPTW7', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:02:39', '2025-08-22 13:02:39', NULL, NULL, 'internal', NULL, NULL),
(184, 162, 147, 1920.00, 0.00, NULL, 'CREDIT DISCOUNT', 'cash', '2025-08-17', '2025-08-27', '2025-08-23 06:29:23', '2025-08-23 06:29:23', NULL, NULL, 'internal', NULL, NULL),
(185, 166, 151, 7200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-25', '2025-09-05', '2025-08-28 15:11:05', '2025-08-28 15:11:05', NULL, NULL, 'internal', NULL, NULL),
(186, 167, 152, 50000.00, 0.00, NULL, 'THS2CLKZUO', 'bank_transfer', '2025-08-28', '2025-09-08', '2025-08-28 15:56:45', '2025-08-28 15:56:45', NULL, NULL, 'internal', NULL, NULL),
(187, 168, 153, 30000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-08-30', '2025-09-30', '2025-09-01 18:27:55', '2025-09-01 18:28:44', NULL, NULL, 'internal', NULL, NULL),
(188, 169, 154, 42000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-14', '2025-08-28', '2025-09-01 18:30:49', '2025-09-01 18:30:49', NULL, NULL, 'internal', NULL, NULL),
(189, 170, 155, 30000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-01', '2025-10-01', '2025-09-02 04:31:52', '2025-09-02 04:31:52', NULL, NULL, 'internal', NULL, NULL),
(190, 171, 156, 45000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-02', '2025-10-02', '2025-09-03 11:14:32', '2025-09-03 11:14:32', NULL, NULL, 'internal', NULL, NULL),
(191, 172, 157, 21600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-31', '2025-09-11', '2025-09-04 07:04:08', '2025-09-04 07:04:08', NULL, NULL, 'internal', NULL, NULL),
(192, 173, 158, 7200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-05', '2025-09-15', '2025-09-04 07:39:18', '2025-09-04 07:39:18', NULL, NULL, 'internal', NULL, NULL),
(193, 174, 159, 15000.00, 0.00, NULL, 'TI85XYSRUX', 'bank_transfer', '2025-09-08', '2025-09-18', '2025-09-08 11:05:29', '2025-09-08 11:05:29', NULL, NULL, 'internal', NULL, NULL),
(194, 175, 160, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-02', '2025-09-12', '2025-09-08 15:32:00', '2025-09-08 15:32:00', NULL, NULL, 'internal', NULL, NULL),
(195, 176, 161, 23616.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-30', '2025-08-08', '2025-09-08 15:39:16', '2025-09-08 15:39:16', NULL, NULL, 'internal', NULL, NULL),
(196, 177, 162, 50600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-08-29', '2025-08-13', '2025-09-08 16:36:08', '2025-09-08 16:36:08', NULL, NULL, 'internal', NULL, NULL),
(197, 178, 163, 65910.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-04', '2025-10-04', '2025-09-08 16:40:16', '2025-09-08 16:40:16', NULL, NULL, 'internal', NULL, NULL),
(198, 179, 164, 28340.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-10', '2025-09-20', '2025-09-10 02:51:00', '2025-09-10 02:51:00', NULL, NULL, 'internal', NULL, NULL),
(199, 181, 166, 40000.00, 0.00, NULL, '272763294080', 'cash', '2025-09-10', '2025-09-24', '2025-09-11 10:24:38', '2025-09-11 10:24:38', NULL, NULL, 'internal', NULL, NULL),
(200, 182, 167, 21600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-11', '2025-09-21', '2025-09-11 17:53:06', '2025-09-11 17:53:06', NULL, NULL, 'internal', NULL, NULL),
(201, 183, 168, 18000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-09-02', '2025-09-12', '2025-09-12 13:50:07', '2025-09-14 11:06:37', NULL, NULL, 'internal', NULL, NULL),
(202, 180, 165, 50000.00, 0.00, NULL, 'TIC9LSKIOD', 'bank_transfer', '2025-09-12', '2025-12-04', '2025-09-12 14:28:15', '2025-09-12 14:28:15', NULL, NULL, 'internal', NULL, NULL),
(203, 184, 169, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-12', '2025-09-22', '2025-09-13 09:16:09', '2025-09-13 09:16:09', NULL, NULL, 'internal', NULL, NULL),
(204, 185, 170, 21600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-12', '2025-09-22', '2025-09-14 11:07:56', '2025-09-14 11:07:56', NULL, NULL, 'internal', NULL, NULL),
(205, 186, 171, 10000.00, 0.00, NULL, 'TIE2TBFRTQ', 'bank_transfer', '2025-09-13', '2025-10-13', '2025-09-15 06:48:23', '2025-09-15 06:48:23', NULL, NULL, 'internal', NULL, NULL),
(206, 187, 172, 40000.00, 0.00, NULL, 'TIF9ZOIXFJ', 'bank_transfer', '2025-09-15', '2025-09-25', '2025-09-15 08:45:05', '2025-09-15 08:45:05', NULL, NULL, 'internal', NULL, NULL),
(207, 171, 156, 8640.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-15', '2025-10-02', '2025-09-16 05:55:06', '2025-09-16 05:55:06', NULL, NULL, 'internal', NULL, NULL),
(208, 188, 173, 1000.00, 0.00, NULL, 'TI881S6WWE', 'bank_transfer', '2025-09-08', '2025-10-08', '2025-09-16 09:28:28', '2025-09-16 09:28:28', NULL, NULL, 'internal', NULL, NULL),
(209, 189, 174, 136000.00, 0.00, NULL, '426867260195', 'Mpesa', '2025-09-19', '2025-10-03', '2025-09-19 08:20:10', '2025-09-19 08:22:04', NULL, NULL, 'internal', NULL, NULL),
(210, 186, 171, 50000.00, 0.00, NULL, 'TIJ8LX4I02', 'bank_transfer', '2025-09-19', '2025-10-20', '2025-09-20 15:18:14', '2025-09-20 15:18:14', NULL, NULL, 'internal', NULL, NULL),
(211, 190, 175, 3000.00, 0.00, NULL, 'TIL1VWBZHZ', 'bank_transfer', '2025-09-22', '2025-10-01', '2025-09-22 07:06:38', '2025-09-22 07:06:38', NULL, NULL, 'internal', NULL, NULL),
(212, 191, 176, 34008.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-21', '2025-10-01', '2025-09-23 03:54:35', '2025-09-23 03:54:35', NULL, NULL, 'internal', NULL, NULL),
(213, 190, 175, 2000.00, 0.00, NULL, 'TIO4A5J94K', 'bank_transfer', '2025-09-24', '2025-10-01', '2025-09-24 07:04:55', '2025-09-24 07:04:55', NULL, NULL, 'internal', NULL, NULL),
(214, 192, 177, 40000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-26', '2025-10-03', '2025-09-25 14:55:05', '2025-09-25 14:55:05', NULL, NULL, 'internal', NULL, NULL),
(215, 190, 175, 5000.00, 0.00, NULL, 'TIP6O5L39G', 'bank_transfer', '2025-09-25', '2025-10-04', '2025-09-25 14:56:13', '2025-09-25 14:56:13', NULL, NULL, 'internal', NULL, NULL),
(216, 186, 171, 100000.00, 0.00, NULL, 'TIP3X5NHPQ', 'bank_transfer', '2025-09-25', '2025-12-01', '2025-09-25 14:57:29', '2025-09-25 14:57:29', NULL, NULL, 'internal', NULL, NULL),
(217, 193, 178, 178000.00, 0.00, NULL, '440397553004', 'bank_transfer', '2025-09-27', '2025-10-11', '2025-09-29 11:23:23', '2025-09-29 11:23:23', NULL, NULL, 'internal', NULL, NULL),
(218, 194, 179, 4000.00, 0.00, NULL, 'TIRGC5T329', 'bank_transfer', '2025-09-27', '2025-10-05', '2025-09-29 11:25:35', '2025-09-29 11:25:35', NULL, NULL, 'internal', NULL, NULL),
(219, 194, 179, 6000.00, 0.00, NULL, 'TIR6O5QWIU', 'bank_transfer', '2025-09-27', '2025-10-04', '2025-09-29 11:26:06', '2025-09-29 11:26:06', NULL, NULL, 'internal', NULL, NULL),
(220, 195, 180, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-02', '2025-10-12', '2025-10-01 07:11:52', '2025-10-01 07:11:52', NULL, NULL, 'internal', NULL, NULL),
(221, 196, 181, 17000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-01', '2025-11-01', '2025-10-01 07:22:36', '2025-10-01 07:22:36', NULL, NULL, 'internal', NULL, NULL),
(222, 197, 182, 23000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-01', '2025-11-01', '2025-10-01 07:24:14', '2025-10-01 07:24:14', NULL, NULL, 'internal', NULL, NULL),
(223, 198, 183, 50000.00, 0.00, NULL, 'TJ18O635GS', 'bank_transfer', '2025-10-01', '2025-10-15', '2025-10-01 09:13:35', '2025-10-01 09:13:35', NULL, NULL, 'internal', NULL, NULL),
(224, 199, 184, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-24', '2025-10-03', '2025-10-03 06:53:37', '2025-10-03 06:53:37', NULL, NULL, 'internal', NULL, NULL),
(225, 200, 185, 14400.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-10-04', '2025-10-14', '2025-10-03 06:55:33', '2025-10-21 04:31:57', NULL, NULL, 'internal', NULL, NULL),
(226, 186, 171, 40000.00, 0.00, NULL, 'TJ33X6EGSR', 'bank_transfer', '2025-10-03', '2025-12-03', '2025-10-03 07:36:54', '2025-10-03 07:36:54', NULL, NULL, 'internal', NULL, NULL),
(227, 201, 186, 5000.00, 0.00, NULL, 'TJ36B6D9HZ', 'bank_transfer', '2025-10-03', '2025-10-13', '2025-10-03 07:46:21', '2025-10-03 07:46:21', NULL, NULL, 'internal', NULL, NULL),
(228, 202, 187, 10000.00, 0.00, NULL, 'MPESA', 'bank_transfer', '2025-10-05', '2025-10-09', '2025-10-06 07:56:01', '2025-10-06 07:56:01', NULL, NULL, 'internal', NULL, NULL),
(229, 203, 188, 54368.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-10-02', '2025-11-02', '2025-10-06 08:10:39', '2025-10-21 20:05:24', NULL, NULL, 'internal', NULL, NULL),
(230, 204, 189, 15000.00, 0.00, NULL, 'TJ6GC6NEIJ', 'bank_transfer', '2025-10-06', '2025-10-16', '2025-10-06 09:02:51', '2025-10-06 09:02:51', NULL, NULL, 'internal', NULL, NULL),
(231, 205, 190, 10000.00, 0.00, NULL, 'TJ99X6XEQE', 'Mpesa', '2025-10-09', '2025-10-18', '2025-10-09 14:56:53', '2025-10-09 14:58:08', NULL, NULL, 'internal', NULL, NULL),
(232, 205, 190, 40000.00, 0.00, NULL, 'TJ8336W4WF', 'bank_transfer', '2025-10-08', '2025-10-18', '2025-10-09 14:57:55', '2025-10-09 14:57:55', NULL, NULL, 'internal', NULL, NULL),
(233, 206, 191, 1200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-08', '2025-10-18', '2025-10-13 11:17:09', '2025-10-13 11:17:09', NULL, NULL, 'internal', NULL, NULL),
(234, 207, 192, 30000.00, 0.00, NULL, '0100006578378', 'bank_transfer', '2025-10-14', '2025-10-24', '2025-10-13 15:46:44', '2025-10-13 15:46:44', NULL, NULL, 'internal', NULL, NULL),
(235, 208, 193, 55835.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-09-26', '2025-11-26', '2025-10-14 06:42:56', '2025-10-14 06:42:56', NULL, NULL, 'internal', NULL, NULL),
(236, 209, 194, 30810.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-10-11', '2025-10-21', '2025-10-14 07:43:56', '2025-10-14 07:44:51', NULL, NULL, 'internal', NULL, NULL),
(237, 210, 195, 36972.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-11', '2025-10-21', '2025-10-14 07:46:11', '2025-10-14 07:46:11', NULL, NULL, 'internal', NULL, NULL),
(238, 211, 196, 20000.00, 0.00, NULL, 'TJFGC7FLBA', 'bank_transfer', '2025-10-15', '2025-10-25', '2025-10-15 08:39:21', '2025-10-15 08:39:21', NULL, NULL, 'internal', NULL, NULL),
(239, 212, 197, 100000.00, 0.00, NULL, 'TJFGZ7GUDB', 'bank_transfer', '2025-10-15', '2025-10-25', '2025-10-16 16:28:34', '2025-10-16 16:28:34', NULL, NULL, 'internal', NULL, NULL),
(240, 213, 198, 10000.00, 0.00, NULL, 'TJGKB7KWG0', 'bank_transfer', '2025-10-16', '2025-10-26', '2025-10-16 16:30:43', '2025-10-16 16:30:43', NULL, NULL, 'internal', NULL, NULL),
(241, 214, 199, 72000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-21', '2025-11-02', '2025-10-22 09:36:49', '2025-10-22 09:36:49', NULL, NULL, 'internal', NULL, NULL),
(242, 215, 200, 10000.00, 0.00, NULL, 'TJNH586RLF', 'bank_transfer', '2025-10-23', '2025-11-02', '2025-10-23 12:46:28', '2025-10-23 12:46:28', NULL, NULL, 'internal', NULL, NULL),
(243, 216, 201, 30000.00, 0.00, NULL, 'TJOFG89PMJ', 'bank_transfer', '2025-10-24', '2025-11-02', '2025-10-24 08:11:59', '2025-10-24 08:11:59', NULL, NULL, 'internal', NULL, NULL),
(244, 219, 204, 3700.00, 0.00, NULL, 'TJR6B8GSJ0', 'bank_transfer', '2025-10-27', '2025-11-07', '2025-10-27 09:51:23', '2025-10-27 09:51:23', NULL, NULL, 'internal', NULL, NULL),
(245, 222, 207, 25000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-06', '2025-10-27 18:01:04', '2025-10-27 18:01:04', NULL, NULL, 'internal', NULL, NULL),
(246, 224, 209, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-06', '2025-10-29 07:22:59', '2025-10-29 07:22:59', NULL, NULL, 'internal', NULL, NULL),
(247, 220, 205, 10000.00, 0.00, NULL, 'TJO4A8E9KQ', 'bank_transfer', '2025-10-24', '2025-10-27', '2025-10-29 07:29:33', '2025-10-29 07:29:33', NULL, NULL, 'internal', NULL, NULL),
(248, 225, 210, 100000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-29', '2025-11-29', '2025-10-30 07:36:04', '2025-10-30 07:36:04', NULL, NULL, 'internal', NULL, NULL),
(249, 225, 210, 100000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-29', '2025-11-29', '2025-10-30 07:36:05', '2025-10-30 07:36:05', NULL, NULL, 'internal', NULL, NULL),
(250, 217, 202, 50000.00, 0.00, NULL, 'TJV5T8X5F9', 'bank_transfer', '2025-10-31', '2025-11-09', '2025-10-31 08:30:20', '2025-10-31 08:30:20', NULL, NULL, 'internal', NULL, NULL),
(251, 186, 171, 50000.00, 0.00, NULL, 'TJV3X8VY6X', 'bank_transfer', '2025-10-31', '2025-11-09', '2025-10-31 08:34:51', '2025-10-31 08:34:51', NULL, NULL, 'internal', NULL, NULL),
(252, 226, 211, 18700.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-02', '2025-12-02', '2025-11-03 09:25:59', '2025-11-03 09:25:59', NULL, NULL, 'internal', NULL, NULL),
(253, 227, 212, 10000.00, 0.00, NULL, 'TK59X9ASRT', 'bank_transfer', '2025-11-04', '2025-11-14', '2025-11-05 13:27:46', '2025-11-05 13:27:46', NULL, NULL, 'internal', NULL, NULL),
(254, 228, 213, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-03', '2025-11-13', '2025-11-07 09:42:41', '2025-11-07 09:42:41', NULL, NULL, 'internal', NULL, NULL),
(255, 229, 214, 25000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-07', '2025-11-17', '2025-11-07 09:48:01', '2025-11-07 09:48:01', NULL, NULL, 'internal', NULL, NULL),
(256, 230, 215, 3000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-07', '2025-11-17', '2025-11-07 09:59:58', '2025-11-07 09:59:58', NULL, NULL, 'internal', NULL, NULL),
(257, 231, 216, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-06', '2025-11-16', '2025-11-08 08:11:40', '2025-11-08 08:11:40', NULL, NULL, 'internal', NULL, NULL),
(258, 232, 217, 20000.00, 0.00, NULL, 'TKAAL9RIRR', 'bank_transfer', '2025-11-10', '2025-11-20', '2025-11-10 15:19:19', '2025-11-10 15:19:19', NULL, NULL, 'internal', NULL, NULL),
(259, 233, 218, 35242.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-03', '2025-12-03', '2025-11-10 15:21:25', '2025-11-10 15:21:25', NULL, NULL, 'internal', NULL, NULL),
(260, 234, 219, 66000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-01', '2025-11-11', '2025-11-10 15:24:30', '2025-11-10 15:24:30', NULL, NULL, 'internal', NULL, NULL),
(261, 235, 220, 79200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-09', '2025-11-19', '2025-11-13 17:54:04', '2025-11-13 17:54:04', NULL, NULL, 'internal', NULL, NULL),
(262, 236, 221, 36000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-03', '2025-11-13', '2025-11-17 09:31:53', '2025-11-17 09:31:53', NULL, NULL, 'internal', NULL, NULL),
(263, 237, 222, 43200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-13', '2025-11-23', '2025-11-17 09:34:37', '2025-11-17 09:34:37', NULL, NULL, 'internal', NULL, NULL),
(264, 238, 223, 12000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-13', '2025-11-23', '2025-11-17 09:43:03', '2025-11-17 09:43:03', NULL, NULL, 'internal', NULL, NULL),
(265, 239, 224, 25000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-18', '2025-11-28', '2025-11-17 18:51:15', '2025-11-17 18:51:15', NULL, NULL, 'internal', NULL, NULL),
(266, 240, 225, 14400.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-11-16', '2025-11-26', '2025-11-18 05:04:53', '2025-11-18 05:06:51', NULL, NULL, 'internal', NULL, NULL),
(267, 241, 226, 10000.00, 0.00, NULL, 'TKIN1AGBQG', 'bank_transfer', '2025-11-18', '2025-12-18', '2025-11-19 05:10:26', '2025-11-19 05:10:26', NULL, NULL, 'internal', NULL, NULL),
(268, 221, 206, 100000.00, 0.00, NULL, '150139401927', 'Mpesa', '2025-11-18', '2025-11-27', '2025-11-19 05:14:26', '2025-11-19 05:14:42', NULL, NULL, 'internal', NULL, NULL),
(269, 221, 206, 15300.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-27', '2025-11-19 05:15:31', '2025-11-19 05:15:31', NULL, NULL, 'internal', NULL, NULL),
(270, 242, 227, 30000.00, 0.00, NULL, '123114002557', 'bank_transfer', '2025-11-18', '2025-12-02', '2025-11-19 05:22:46', '2025-11-19 05:22:46', NULL, NULL, 'internal', NULL, NULL),
(271, 243, 228, 2700.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-17', '2025-11-27', '2025-11-19 09:26:27', '2025-11-19 09:26:27', NULL, NULL, 'internal', NULL, NULL),
(272, 244, 229, 600000.00, 0.00, NULL, '269164405448', 'bank_transfer', '2025-11-22', '2025-12-22', '2025-11-22 19:27:35', '2025-11-22 19:27:35', NULL, NULL, 'internal', NULL, NULL),
(273, 245, 230, 20000.00, 0.00, NULL, 'TKM4WAUJ46', 'bank_transfer', '2025-11-22', '2025-12-01', '2025-11-22 19:29:06', '2025-11-22 19:29:06', NULL, NULL, 'internal', NULL, NULL),
(274, 246, 231, 20000.00, 0.00, NULL, 'TKL9XAS81M', 'bank_transfer', '2025-11-21', '2025-12-01', '2025-11-22 19:34:48', '2025-11-22 19:34:48', NULL, NULL, 'internal', NULL, NULL),
(275, 248, 233, 14400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-23', '2025-12-03', '2025-11-26 19:34:30', '2025-11-26 19:34:30', NULL, NULL, 'internal', NULL, NULL),
(276, 249, 234, 25000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-28', '2025-12-08', '2025-11-28 18:27:20', '2025-11-28 18:27:20', NULL, NULL, 'internal', NULL, NULL),
(277, 251, 236, 43840.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-23', '2025-12-03', '2025-11-29 14:19:48', '2025-11-29 14:19:48', NULL, NULL, 'internal', NULL, NULL),
(278, 247, 232, 95040.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-20', '2025-11-30', '2025-11-29 15:12:38', '2025-11-29 15:12:38', NULL, NULL, 'internal', NULL, NULL),
(279, 252, 237, 20000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-01', '2025-12-11', '2025-12-01 19:13:19', '2025-12-01 19:13:19', NULL, NULL, 'internal', NULL, NULL),
(280, 253, 238, 114048.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-30', '2025-12-10', '2025-12-03 04:33:15', '2025-12-03 04:33:15', NULL, NULL, 'internal', NULL, NULL),
(281, 254, 239, 100000.00, 0.00, NULL, '588867370679', 'bank_transfer', '2025-11-24', '2025-12-24', '2025-12-03 04:44:16', '2025-12-03 04:44:16', NULL, NULL, 'internal', NULL, NULL),
(282, 255, 240, 2680.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-27', '2025-12-07', '2025-12-03 04:48:19', '2025-12-03 04:48:19', NULL, NULL, 'internal', NULL, NULL),
(283, 250, 235, 126830.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-27', '2026-02-27', '2025-12-03 04:49:32', '2025-12-03 04:49:32', NULL, NULL, 'internal', NULL, NULL),
(284, 256, 241, 50000.00, 0.00, NULL, 'TL5GC03C33', 'bank_transfer', '2025-12-05', '2026-12-05', '2025-12-06 08:46:58', '2025-12-06 08:46:58', NULL, NULL, 'internal', NULL, NULL),
(285, 257, 242, 42290.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-03', '2025-01-03', '2025-12-06 08:55:30', '2025-12-06 08:55:30', NULL, NULL, 'internal', NULL, NULL),
(286, 258, 243, 130000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-11-29', '2025-12-29', '2025-12-08 05:29:48', '2025-12-08 05:29:48', NULL, NULL, 'internal', NULL, NULL),
(287, 259, 244, 37608.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-03', '2025-12-13', '2025-12-09 06:19:21', '2025-12-09 06:19:21', NULL, NULL, 'internal', NULL, NULL),
(288, 260, 245, 25000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-09', '2025-12-19', '2025-12-09 06:23:21', '2025-12-09 06:23:21', NULL, NULL, 'internal', NULL, NULL),
(289, 261, 246, 8780.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-03', '2025-12-13', '2025-12-09 06:27:35', '2025-12-09 06:27:35', NULL, NULL, 'internal', NULL, NULL),
(290, 262, 247, 100000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-24', '2026-01-24', '2025-12-26 08:54:16', '2025-12-26 08:54:16', NULL, NULL, 'internal', NULL, NULL);
INSERT INTO `disbursements` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `disburse_date`, `payment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `funding_source`, `investment_id`, `notes`) VALUES
(292, 263, 248, 10536.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-13', '2025-12-23', '2025-12-26 08:58:45', '2025-12-26 08:58:45', NULL, NULL, 'internal', NULL, NULL),
(293, 264, 249, 12644.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-23', '2026-01-02', '2025-12-26 09:00:42', '2025-12-26 09:00:42', NULL, NULL, 'internal', NULL, NULL),
(294, 265, 250, 45130.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2025-12-13', '2025-12-23', '2025-12-26 09:03:58', '2025-12-26 09:04:45', NULL, NULL, 'internal', NULL, NULL),
(295, 266, 251, 54156.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-23', '2026-01-02', '2025-12-26 09:06:31', '2025-12-26 09:06:31', NULL, NULL, 'internal', NULL, NULL),
(296, 268, 253, 5000.00, 0.00, NULL, 'TLQ9X25GGH', 'cash', '2025-12-26', '2025-12-26', '2025-12-26 18:07:39', '2025-12-26 18:07:39', NULL, NULL, 'internal', NULL, NULL),
(297, 267, 252, 5000.00, 0.00, NULL, 'TLM9X1QZKV', 'bank_transfer', '2025-12-22', '2025-01-01', '2025-12-26 18:08:57', '2025-12-26 18:08:57', NULL, NULL, 'internal', NULL, NULL),
(298, 270, 255, 30000.00, 0.00, NULL, '683213014684', 'Mpesa', '2025-12-29', '2025-01-12', '2025-12-31 04:37:08', '2025-12-31 04:38:00', NULL, NULL, 'internal', NULL, NULL),
(299, 270, 255, 50000.00, 0.00, NULL, '281652744481', 'bank_transfer', '2025-12-29', '2025-01-12', '2025-12-31 04:37:52', '2025-12-31 04:37:52', NULL, NULL, 'internal', NULL, NULL),
(300, 269, 254, 720000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-22', '2026-01-22', '2026-01-04 09:32:50', '2026-01-04 09:32:50', NULL, NULL, 'internal', NULL, NULL),
(301, 272, 257, 20000.00, 0.00, NULL, 'TLQBU25Y2F', 'bank_transfer', '2026-12-26', '2026-12-05', '2026-01-05 02:00:02', '2026-01-05 02:00:02', NULL, NULL, 'internal', NULL, NULL),
(302, 273, 258, 15172.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-01-02', '2026-01-12', '2026-01-08 14:14:22', '2026-01-08 14:14:22', NULL, NULL, 'internal', NULL, NULL),
(303, 274, 259, 40000.00, 0.00, NULL, 'UA98O3BJJI', 'bank_transfer', '2026-01-09', '2026-01-19', '2026-01-09 07:02:50', '2026-01-09 07:02:50', NULL, NULL, 'internal', NULL, NULL),
(304, 275, 260, 100000.00, 0.00, NULL, 'UA99X3DR0I', 'bank_transfer', '2026-01-09', '2026-02-09', '2026-01-12 06:01:09', '2026-01-12 06:01:09', NULL, NULL, 'internal', NULL, NULL),
(305, 276, 261, 50000.00, 0.00, NULL, '922272948696', 'bank_transfer', '2026-01-09', '2026-01-19', '2026-01-12 06:04:57', '2026-01-12 06:04:57', NULL, NULL, 'internal', NULL, NULL),
(306, 277, 262, 35000.00, 0.00, NULL, '435076301514', 'bank_transfer', '2026-01-12', '2026-01-22', '2026-01-12 07:01:23', '2026-01-12 07:01:23', NULL, NULL, 'internal', NULL, NULL),
(307, 278, 263, 20000.00, 0.00, NULL, '723597734036', 'bank_transfer', '2025-12-19', '2026-12-02', '2026-01-12 07:12:50', '2026-01-12 07:12:50', NULL, NULL, 'internal', NULL, NULL),
(308, 278, 263, 30000.00, 0.00, NULL, 'TLFFH13IRG', 'bank_transfer', '2025-12-15', '2026-02-15', '2026-01-12 07:14:20', '2026-01-12 07:14:20', NULL, NULL, 'internal', NULL, NULL),
(309, 278, 263, 26000.00, 0.00, NULL, '118781109392', 'bank_transfer', '2025-12-02', '2026-02-02', '2026-01-12 07:15:02', '2026-01-12 07:15:02', NULL, NULL, 'internal', NULL, NULL),
(310, 278, 263, 5000.00, 0.00, NULL, 'TLC6O0PBJ1', 'bank_transfer', '2025-12-10', '2026-02-10', '2026-01-12 07:16:06', '2026-01-12 07:16:06', NULL, NULL, 'internal', NULL, NULL),
(311, 278, 263, 17000.00, 0.00, NULL, '2987OUKN5391', 'bank_transfer', '2025-12-12', '2026-02-12', '2026-01-12 07:16:49', '2026-01-12 07:16:49', NULL, NULL, 'internal', NULL, NULL),
(312, 278, 263, 2000.00, 0.00, NULL, '276508285588', 'bank_transfer', '2025-12-02', '2026-02-12', '2026-01-12 07:18:31', '2026-01-12 07:18:31', NULL, NULL, 'internal', NULL, NULL),
(313, 279, 264, 169000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2025-12-29', '2026-01-29', '2026-01-12 13:29:44', '2026-01-12 13:29:44', NULL, NULL, 'internal', NULL, NULL),
(315, 280, 265, 50748.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-01-03', '2026-02-03', '2026-01-15 16:41:41', '2026-01-15 16:41:41', NULL, NULL, 'internal', NULL, NULL),
(317, 281, 266, 6000.00, 0.00, NULL, 'UAG9X4074M', 'bank_transfer', '2026-01-16', '2026-01-16', '2026-01-16 13:43:51', '2026-01-16 13:43:51', NULL, NULL, 'internal', NULL, NULL),
(318, 282, 267, 250000.00, 0.00, NULL, 'UAL644MPFT', 'bank_transfer', '2026-01-21', '2026-02-01', '2026-01-22 07:29:17', '2026-01-22 07:29:17', NULL, NULL, 'internal', NULL, NULL),
(319, 283, 268, 8000.00, 0.00, NULL, 'UAM9X4KZIR', 'bank_transfer', '2026-01-22', '2026-02-02', '2026-01-27 15:14:54', '2026-01-27 15:14:54', NULL, NULL, 'internal', NULL, NULL),
(320, 285, 269, 30000.00, 0.00, NULL, 'UAQ9X4X754', 'bank_transfer', '2026-01-26', '2026-01-31', '2026-01-29 08:26:13', '2026-01-29 08:26:13', NULL, NULL, 'internal', NULL, NULL),
(321, 286, 270, 8000.00, 0.00, NULL, 'UAT9X57N3S', 'bank_transfer', '2026-01-29', '2026-02-03', '2026-01-29 08:27:16', '2026-01-29 08:27:16', NULL, NULL, 'internal', NULL, NULL),
(322, 287, 271, 100000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-01-24', '2026-02-24', '2026-01-29 10:09:35', '2026-02-26 17:54:48', NULL, NULL, 'internal', NULL, NULL),
(323, 288, 272, 364000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-01-22', '2026-02-22', '2026-02-01 19:39:20', '2026-02-24 16:14:14', NULL, NULL, 'internal', NULL, NULL),
(324, 289, 273, 300000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-01-31', '2026-02-10', '2026-02-03 14:43:15', '2026-02-03 14:43:15', NULL, NULL, 'internal', NULL, NULL),
(326, 290, 274, 100000.00, 0.00, NULL, 'PESALINK', 'bank_transfer', '2026-02-05', '2026-02-10', '2026-02-09 13:33:15', '2026-02-09 13:33:15', NULL, NULL, 'internal', NULL, NULL),
(327, 291, 275, 65560.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2026-02-09', '2026-03-09', '2026-02-09 13:41:01', '2026-02-09 13:41:01', NULL, NULL, 'internal', NULL, NULL),
(328, 292, 276, 100000.00, 0.00, NULL, 'UB88O66J8B', 'bank_transfer', '2026-02-08', '2026-12-08', '2026-02-09 13:43:00', '2026-02-09 13:43:00', NULL, NULL, 'internal', NULL, NULL),
(329, 293, 277, 252000.00, 0.00, NULL, '042411941846', 'bank_transfer', '2026-02-05', '2026-02-05', '2026-02-09 13:47:35', '2026-02-09 13:47:35', NULL, NULL, 'internal', NULL, NULL),
(331, 307, 284, 150000.00, 0.00, NULL, '176497116960', 'bank_transfer', '2026-02-11', '2026-02-22', '2026-02-11 16:38:00', '2026-02-11 16:38:00', NULL, NULL, 'internal', NULL, NULL),
(332, 308, 285, 15000.00, 0.00, NULL, 'UBGGC6VCBR', 'bank_transfer', '2026-02-16', '2026-03-16', '2026-02-17 13:40:43', '2026-02-17 13:40:43', NULL, NULL, 'internal', NULL, NULL),
(333, 309, 286, 12000.00, 0.00, NULL, '325695589697', 'bank_transfer', '2026-02-13', '2026-02-23', '2026-02-17 13:46:15', '2026-02-17 13:46:15', NULL, NULL, 'internal', NULL, NULL),
(334, 310, 287, 360000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-10', '2026-02-20', '2026-02-17 13:48:14', '2026-02-17 13:48:14', NULL, NULL, 'internal', NULL, NULL),
(335, 311, 288, 219700.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-01-31', '2026-02-28', '2026-02-18 05:28:12', '2026-02-18 05:28:12', NULL, NULL, 'internal', NULL, NULL),
(336, 312, 289, 60898.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-03', '2026-03-03', '2026-02-18 05:36:10', '2026-02-18 05:36:10', NULL, NULL, 'internal', NULL, NULL),
(337, 314, 291, 30000.00, 0.00, NULL, 'UBGBM6YYRR', 'bank_transfer', '2026-02-16', '2026-02-26', '2026-02-19 09:20:32', '2026-02-19 09:20:32', NULL, NULL, 'internal', NULL, NULL),
(338, 315, 292, 50000.00, 0.00, NULL, 'UB94A6N8EF', 'bank_transfer', '2026-02-09', '2026-02-19', '2026-02-19 09:40:20', '2026-02-19 09:40:20', NULL, NULL, 'internal', NULL, NULL),
(339, 316, 293, 60000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-19', '2026-02-28', '2026-02-19 09:42:18', '2026-02-19 09:42:18', NULL, NULL, 'internal', NULL, NULL),
(340, 316, 293, 70000.00, 0.00, NULL, 'UBI4A7GERR', 'cash', '2026-02-18', '2026-02-28', '2026-02-19 09:42:34', '2026-02-19 09:42:34', NULL, NULL, 'internal', NULL, NULL),
(341, 313, 290, 5000.00, 0.00, NULL, 'UBIOI77N54', 'bank_transfer', '2026-02-18', '2026-02-28', '2026-02-19 09:47:18', '2026-02-19 09:47:18', NULL, NULL, 'internal', NULL, NULL),
(342, 317, 294, 100000.00, 0.00, NULL, '438432145363', 'bank_transfer', '2026-02-19', '2026-02-19', '2026-02-19 10:02:53', '2026-02-19 10:02:53', NULL, NULL, 'internal', NULL, NULL),
(343, 318, 295, 2000.00, 0.00, NULL, 'UBJBD76WGN', 'bank_transfer', '2026-02-19', '2026-03-01', '2026-02-19 10:07:44', '2026-02-19 10:07:44', NULL, NULL, 'internal', NULL, NULL),
(344, 318, 295, 2000.00, 0.00, NULL, 'UBLBD7EE0Y', 'bank_transfer', '2026-02-19', '2026-02-28', '2026-02-21 21:11:19', '2026-02-21 21:11:19', NULL, NULL, 'internal', NULL, NULL),
(345, 319, 296, 40000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-21', '2026-03-02', '2026-02-21 21:15:00', '2026-02-21 21:15:00', NULL, NULL, 'internal', NULL, NULL),
(346, 320, 297, 1000.00, 0.00, NULL, 'UBIIT6X3J4', 'bank_transfer', '2026-02-18', '2026-02-28', '2026-02-23 14:11:45', '2026-02-23 14:11:45', NULL, NULL, 'internal', NULL, NULL),
(347, 320, 297, 1000.00, 0.00, NULL, 'UBNIT7F3ZB', 'bank_transfer', '2026-02-23', '2026-02-28', '2026-02-23 14:12:02', '2026-02-23 14:12:02', NULL, NULL, 'internal', NULL, NULL),
(348, 321, 298, 168000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-22', '2026-02-27', '2026-02-24 16:05:48', '2026-02-24 16:05:48', NULL, NULL, 'internal', NULL, NULL),
(349, 321, 298, 13248.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-22', '2026-02-27', '2026-02-24 16:06:00', '2026-02-24 16:06:00', NULL, NULL, 'internal', NULL, NULL),
(350, 316, 293, 30000.00, 0.00, NULL, 'UBP4A85Z7N', 'bank_transfer', '2026-02-26', '2026-02-28', '2026-02-26 04:14:19', '2026-02-26 04:14:19', NULL, NULL, 'internal', NULL, NULL),
(351, 322, 299, 100000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-26', '2026-03-26', '2026-02-26 18:02:11', '2026-02-26 18:02:11', NULL, NULL, 'internal', NULL, NULL),
(352, 323, 300, 192000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-28', '2026-02-28', '2026-02-27 19:34:52', '2026-02-27 19:34:52', NULL, NULL, 'internal', NULL, NULL),
(353, 324, 301, 150000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-27', '2026-03-13', '2026-03-03 06:43:07', '2026-03-03 06:43:07', NULL, NULL, 'internal', NULL, NULL),
(354, 325, 302, 285610.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-03', '2026-03-03', '2026-03-04 06:07:25', '2026-03-04 06:07:25', NULL, NULL, 'internal', NULL, NULL),
(355, 326, 303, 3500.00, 0.00, NULL, 'UC46O88G7H', 'bank_transfer', '2026-03-04', '2026-03-14', '2026-03-04 07:47:46', '2026-03-04 07:47:46', NULL, NULL, 'internal', NULL, NULL),
(356, 327, 304, 15000.00, 0.00, NULL, 'UC3KB83095', 'bank_transfer', '2026-03-03', '2026-03-03', '2026-03-04 07:49:36', '2026-03-04 07:49:36', NULL, NULL, 'internal', NULL, NULL),
(357, 329, 306, 55000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-07', '2026-03-17', '2026-03-06 12:58:14', '2026-03-06 12:58:14', NULL, NULL, 'internal', NULL, NULL),
(358, 330, 307, 432000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-02', '2026-03-06 13:02:54', '2026-03-06 13:02:54', NULL, NULL, 'internal', NULL, NULL),
(359, 331, 308, 518400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-12', '2026-03-06 13:04:57', '2026-03-06 13:04:57', NULL, NULL, 'internal', NULL, NULL),
(360, 332, 309, 20000.00, 0.00, NULL, '3719CFJF5903', 'bank_transfer', '2026-03-08', '2026-03-18', '2026-03-08 13:58:22', '2026-03-08 13:58:22', NULL, NULL, 'internal', NULL, NULL),
(361, 333, 310, 15000.00, 0.00, NULL, '3728HHVL2707', 'bank_transfer', '2026-03-08', '2026-03-18', '2026-03-08 13:59:48', '2026-03-08 13:59:48', NULL, NULL, 'internal', NULL, NULL),
(362, 334, 311, 50000.00, 0.00, NULL, '3719XVBL5990', 'bank_transfer', '2026-03-07', '2026-03-17', '2026-03-08 14:01:41', '2026-03-08 14:01:41', NULL, NULL, 'internal', NULL, NULL),
(363, 328, 305, 3000.00, 0.00, NULL, 'UC66B8GN86', 'bank_transfer', '2026-03-06', '2026-03-16', '2026-03-08 14:32:38', '2026-03-08 14:32:38', NULL, NULL, 'internal', NULL, NULL),
(364, 335, 312, 85228.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-09', '2026-04-09', '2026-03-12 11:22:39', '2026-03-12 11:22:39', NULL, NULL, 'internal', NULL, NULL),
(365, 336, 313, 2000.00, 0.00, NULL, 'UCC6B91DXT', 'bank_transfer', '2026-03-12', '2026-03-22', '2026-03-13 08:18:04', '2026-03-13 08:18:04', NULL, NULL, 'internal', NULL, NULL),
(366, 337, 314, 50000.00, 0.00, NULL, '620830073841', 'Mpesa', '2026-03-14', '2026-03-24', '2026-03-14 19:46:56', '2026-03-14 19:47:33', NULL, NULL, 'internal', NULL, NULL),
(367, 338, 315, 18000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-13', '2026-03-23', '2026-03-14 19:54:51', '2026-03-14 19:54:51', NULL, NULL, 'internal', NULL, NULL),
(368, 339, 316, 5000.00, 0.00, NULL, 'UCDHD9CMWM', 'bank_transfer', '2026-03-13', '2026-03-13', '2026-03-14 20:03:12', '2026-03-14 20:03:12', NULL, NULL, 'internal', NULL, NULL),
(369, 339, 316, 32500.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-03-13', '2026-03-13', '2026-03-14 20:03:42', '2026-03-14 20:04:08', NULL, NULL, 'internal', NULL, NULL),
(370, 340, 317, 6000.00, 0.00, NULL, 'MPESA', 'bank_transfer', '2026-02-20', '2026-02-20', '2026-03-15 18:46:40', '2026-03-15 18:46:40', NULL, NULL, 'internal', NULL, NULL),
(371, 341, 318, 7200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-12', '2026-03-15 18:48:15', '2026-03-15 18:48:15', NULL, NULL, 'internal', NULL, NULL),
(372, 342, 319, 8640.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-12', '2026-03-22', '2026-03-15 18:50:15', '2026-03-15 18:50:15', NULL, NULL, 'internal', NULL, NULL),
(373, 343, 320, 10000.00, 0.00, NULL, 'UCGEV9I4AF', 'bank_transfer', '2026-03-16', '2026-03-26', '2026-03-16 15:16:56', '2026-03-16 15:16:56', NULL, NULL, 'internal', NULL, NULL),
(374, 344, 321, 5000.00, 0.00, NULL, 'UCI6B9O9OT', 'bank_transfer', '2026-03-18', '2026-03-28', '2026-03-19 05:18:56', '2026-03-19 05:18:56', NULL, NULL, 'internal', NULL, NULL),
(375, 345, 322, 30000.00, 0.00, NULL, 'UCH9X9PH8Y', 'bank_transfer', '2026-03-18', '2026-03-28', '2026-03-19 07:15:01', '2026-03-19 07:15:01', NULL, NULL, 'internal', NULL, NULL),
(376, 346, 323, 5000.00, 0.00, NULL, 'UCJAL9R6WA', 'bank_transfer', '2026-03-19', '2026-03-29', '2026-03-20 15:16:16', '2026-03-20 15:16:16', NULL, NULL, 'internal', NULL, NULL),
(377, 347, 324, 50000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-28', '2026-03-10', '2026-03-20 15:22:44', '2026-03-20 15:22:44', NULL, NULL, 'internal', NULL, NULL),
(378, 349, 325, 114440.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-02-27', '2026-05-27', '2026-03-20 18:25:16', '2026-03-20 18:25:16', NULL, NULL, 'internal', NULL, NULL),
(379, 350, 326, 8000.00, 0.00, NULL, 'UCOGCABJKN', 'bank_transfer', '2026-03-24', '2026-04-03', '2026-03-25 07:28:57', '2026-03-25 07:28:57', NULL, NULL, 'internal', NULL, NULL),
(380, 351, 327, 10000.00, 0.00, NULL, 'UCPBMAL8MF', 'bank_transfer', '2026-03-25', '2026-04-03', '2026-03-25 07:31:48', '2026-03-25 07:31:48', NULL, NULL, 'internal', NULL, NULL),
(381, 352, 328, 1500.00, 0.00, NULL, 'UCP6OABVYQ', 'bank_transfer', '2026-03-25', '2026-04-03', '2026-03-25 07:55:23', '2026-03-25 07:55:23', NULL, NULL, 'internal', NULL, NULL),
(382, 353, 329, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-26', '2026-04-04', '2026-03-26 20:57:35', '2026-03-26 20:57:35', NULL, NULL, 'internal', NULL, NULL),
(383, 354, 330, 20000.00, 0.00, NULL, 'UCQKBAE0DG', 'bank_transfer', '2026-03-26', '2026-04-04', '2026-03-27 08:36:15', '2026-03-27 08:36:15', NULL, NULL, 'internal', NULL, NULL),
(384, 355, 331, 1000.00, 0.00, NULL, 'UCR6OAKFLQ', 'bank_transfer', '2026-03-27', '2026-04-03', '2026-03-29 16:49:36', '2026-03-29 16:49:36', NULL, NULL, 'internal', NULL, NULL),
(385, 356, 332, 5000.00, 0.00, NULL, 'UCSOIB1OMC', 'bank_transfer', '2026-03-28', '2026-04-06', '2026-03-29 16:51:49', '2026-03-29 16:51:49', NULL, NULL, 'internal', NULL, NULL),
(386, 357, 333, 45000.00, 0.00, NULL, 'UCTBMB1ZDN', 'bank_transfer', '2026-03-30', '2026-03-09', '2026-03-29 16:55:42', '2026-03-29 16:55:42', NULL, NULL, 'internal', NULL, NULL),
(387, 358, 334, 10000.00, 0.00, NULL, 'UCR4AB9NS5', 'bank_transfer', '2026-03-27', '2026-04-05', '2026-03-29 17:15:01', '2026-03-29 17:15:01', NULL, NULL, 'internal', NULL, NULL),
(388, 359, 335, 10000.00, 0.00, NULL, 'UD132BBR99', 'bank_transfer', '2026-04-01', '2026-04-06', '2026-04-02 04:48:03', '2026-04-02 04:48:03', NULL, NULL, 'internal', NULL, NULL),
(389, 360, 336, 100000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-26', '2026-04-26', '2026-04-02 04:57:35', '2026-04-02 04:57:35', NULL, NULL, 'internal', NULL, NULL),
(390, 361, 337, 3200.00, 0.00, NULL, 'UCV6BB0M8H', 'bank_transfer', '2026-03-31', '2026-04-10', '2026-04-02 04:59:35', '2026-04-02 04:59:35', NULL, NULL, 'internal', NULL, NULL),
(391, 362, 338, 5000.00, 0.00, NULL, 'UD1HDBDUSY', 'bank_transfer', '2026-03-31', '2026-04-10', '2026-04-02 05:02:02', '2026-04-02 05:02:02', NULL, NULL, 'internal', NULL, NULL),
(392, 363, 339, 25000.00, 0.00, NULL, 'UCU9XB5HFB', 'bank_transfer', '2026-03-30', '2026-04-09', '2026-04-02 05:08:53', '2026-04-02 05:08:53', NULL, NULL, 'internal', NULL, NULL),
(393, 364, 340, 73078.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-18', '2026-04-18', '2026-04-02 05:18:04', '2026-04-02 05:18:04', NULL, NULL, 'internal', NULL, NULL),
(394, 365, 341, 30000.00, 0.00, NULL, 'UD4ALBIZKO', 'bank_transfer', '2026-04-04', '2026-04-14', '2026-04-04 11:04:56', '2026-04-04 11:04:56', NULL, NULL, 'internal', NULL, NULL),
(395, 366, 342, 4200.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2026-03-14', '2026-03-24', '2026-04-04 11:07:14', '2026-04-04 11:07:14', NULL, NULL, 'internal', NULL, NULL),
(396, 367, 343, 5040.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-24', '2026-04-03', '2026-04-04 11:08:35', '2026-04-04 11:08:35', NULL, NULL, 'internal', NULL, NULL),
(397, 368, 344, 10368.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-21', '2026-04-01', '2026-04-04 11:11:57', '2026-04-04 11:11:57', NULL, NULL, 'internal', NULL, NULL),
(398, 369, 345, 10000.00, 0.00, NULL, 'roll over', 'cash', '2026-04-05', '2026-04-15', '2026-04-05 15:44:24', '2026-04-05 15:44:24', NULL, NULL, 'internal', NULL, NULL),
(399, 370, 346, 24000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-05', '2026-04-15', '2026-04-08 06:07:49', '2026-04-08 06:07:49', NULL, NULL, 'internal', NULL, NULL),
(400, 371, 347, 1500.00, 0.00, NULL, 'UD86BBWAX7', 'bank_transfer', '2026-04-08', '2026-04-18', '2026-04-08 06:12:29', '2026-04-08 06:12:29', NULL, NULL, 'internal', NULL, NULL),
(401, 372, 348, 5000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-10', '2026-04-20', '2026-04-13 16:26:02', '2026-04-13 16:26:02', NULL, NULL, 'internal', NULL, NULL),
(402, 373, 349, 3040.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-10', '2026-04-20', '2026-04-13 16:30:44', '2026-04-13 16:30:44', NULL, NULL, 'internal', NULL, NULL),
(403, 374, 350, 342732.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-03', '2026-05-03', '2026-04-13 16:43:53', '2026-04-13 16:43:53', NULL, NULL, 'internal', NULL, NULL),
(404, 375, 351, 12441.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-31', '2026-04-09', '2026-04-13 17:20:15', '2026-04-13 17:20:15', NULL, NULL, 'internal', NULL, NULL),
(405, 375, 351, 6048.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-04-03', '2026-04-09', '2026-04-13 17:20:28', '2026-04-13 17:21:33', NULL, NULL, 'internal', NULL, NULL),
(406, 376, 352, 22187.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-10', '2026-03-20', '2026-04-13 17:25:34', '2026-04-13 17:25:34', NULL, NULL, 'internal', NULL, NULL),
(407, 377, 353, 10000.00, 0.00, NULL, 'UDFAI12TXH', 'bank_transfer', '2026-04-15', '2026-04-15', '2026-04-15 10:12:00', '2026-04-15 10:12:00', NULL, NULL, 'internal', NULL, NULL),
(408, 378, 354, 7500.00, 0.00, NULL, 'UDEOI142NU', 'bank_transfer', '2026-04-14', '2026-04-24', '2026-04-15 10:21:15', '2026-04-15 10:21:15', NULL, NULL, 'internal', NULL, NULL),
(409, 379, 355, 10000.00, 0.00, NULL, 'UDFBM13SLE', 'bank_transfer', '2026-04-15', '2026-04-25', '2026-04-15 10:23:08', '2026-04-15 10:23:08', NULL, NULL, 'internal', NULL, NULL),
(410, 380, 356, 66000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-03-20', '2026-06-20', '2026-04-15 10:56:34', '2026-04-15 10:56:34', NULL, NULL, 'internal', NULL, NULL),
(411, 381, 357, 10000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-15', '2026-04-25', '2026-04-16 15:08:55', '2026-04-16 15:08:55', NULL, NULL, 'internal', NULL, NULL),
(412, 382, 358, 30000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-09', '2026-04-19', '2026-04-17 18:15:28', '2026-04-17 18:15:28', NULL, NULL, 'internal', NULL, NULL),
(413, 383, 359, 110797.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-09', '2026-05-09', '2026-04-17 18:18:23', '2026-04-17 18:18:23', NULL, NULL, 'internal', NULL, NULL),
(414, 384, 360, 6000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-20', '2026-04-30', '2026-04-21 04:06:09', '2026-04-21 04:06:09', NULL, NULL, 'internal', NULL, NULL),
(415, 385, 361, 15000.00, 0.00, NULL, 'UDKBM1Q6KM', 'bank_transfer', '2026-04-20', '2026-04-30', '2026-04-22 06:46:20', '2026-04-22 06:46:20', NULL, NULL, 'internal', NULL, NULL),
(416, 385, 361, 5000.00, 0.00, NULL, 'UDLBM1RM8F', 'Mpesa', '2026-04-22', '2026-04-30', '2026-04-22 06:46:36', '2026-04-22 06:46:54', NULL, NULL, 'internal', NULL, NULL),
(417, 386, 362, 1000.00, 0.00, NULL, 'UDMIT1JYA4', 'bank_transfer', '2026-04-22', '2026-05-02', '2026-04-22 07:00:46', '2026-04-22 07:00:46', NULL, NULL, 'internal', NULL, NULL),
(418, 387, 363, 10000.00, 0.00, NULL, 'UDM9X1UI8J', 'bank_transfer', '2026-04-22', '2026-05-02', '2026-04-23 05:54:39', '2026-04-23 05:54:39', NULL, NULL, 'internal', NULL, NULL),
(419, 388, 364, 10000.00, 0.00, NULL, 'UDN321V2ZZ', 'bank_transfer', '2026-04-23', '2026-04-28', '2026-04-23 06:52:58', '2026-04-23 06:52:58', NULL, NULL, 'internal', NULL, NULL),
(420, 388, 364, 10000.00, 0.00, NULL, 'UDN321V2ZZ', 'bank_transfer', '2026-04-23', '2026-04-28', '2026-04-23 06:52:58', '2026-04-23 06:52:58', NULL, NULL, 'internal', NULL, NULL),
(421, 389, 365, 3300.00, 0.00, NULL, 'UDN6B1PHVO', 'bank_transfer', '2026-04-23', '2026-05-02', '2026-04-26 06:43:35', '2026-04-26 06:43:35', NULL, NULL, 'internal', NULL, NULL),
(422, 389, 365, 1700.00, 0.00, NULL, 'UDN6B1RC19', 'bank_transfer', '2026-04-23', '2026-05-02', '2026-04-26 06:43:49', '2026-04-26 06:43:49', NULL, NULL, 'internal', NULL, NULL),
(423, 390, 366, 3000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-04-25', '2026-05-05', '2026-04-27 05:00:58', '2026-05-05 10:28:22', NULL, NULL, 'internal', NULL, NULL),
(424, 391, 367, 7200.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-04-30', '2026-05-10', '2026-05-01 15:17:55', '2026-05-01 15:18:09', NULL, NULL, 'internal', NULL, NULL),
(425, 392, 368, 5400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-27', '2026-05-07', '2026-05-01 15:31:54', '2026-05-01 15:31:54', NULL, NULL, 'internal', NULL, NULL),
(426, 393, 369, 1800.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-25', '2026-05-25', '2026-05-01 15:53:06', '2026-05-01 15:53:06', NULL, NULL, 'internal', NULL, NULL),
(427, 394, 370, 5000.00, 0.00, NULL, 'UDP5B28S4C', 'bank_transfer', '2026-04-25', '2026-05-05', '2026-05-02 13:22:23', '2026-05-02 13:22:23', NULL, NULL, 'internal', NULL, NULL),
(428, 395, 371, 35000.00, 0.00, NULL, 'UE2KB2NLQH', 'bank_transfer', '2026-05-02', '2026-05-16', '2026-05-02 14:44:25', '2026-05-02 14:44:25', NULL, NULL, 'internal', NULL, NULL),
(429, 396, 372, 50000.00, 0.00, NULL, 'UE28O30YKZ', 'bank_transfer', '2026-05-02', '2026-05-12', '2026-05-05 10:07:10', '2026-05-05 10:07:10', NULL, NULL, 'internal', NULL, NULL),
(430, 398, 374, 1000.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2026-05-04', '2026-05-18', '2026-05-05 10:19:56', '2026-05-05 10:19:56', NULL, NULL, 'internal', NULL, NULL),
(431, 398, 374, 6000.00, 0.00, NULL, 'UE46B30ZNW', 'bank_transfer', '2026-05-04', '2026-05-14', '2026-05-05 10:20:55', '2026-05-05 10:20:55', NULL, NULL, 'internal', NULL, NULL),
(432, 397, 373, 3000.00, 0.00, NULL, 'UE46B2ZWG1', 'bank_transfer', '2026-05-04', '2026-05-14', '2026-05-05 10:21:34', '2026-05-05 10:21:34', NULL, NULL, 'internal', NULL, NULL),
(433, 399, 375, 25000.00, 0.00, NULL, 'UE49X38M5M', 'bank_transfer', '2026-05-04', '2026-05-11', '2026-05-05 10:33:46', '2026-05-05 10:33:46', NULL, NULL, 'internal', NULL, NULL),
(434, 400, 376, 3600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-06', '2026-05-16', '2026-05-07 06:40:53', '2026-05-07 06:40:53', NULL, NULL, 'internal', NULL, NULL),
(435, 401, 377, 75000.00, 0.00, NULL, 'UE78O3LYYG', 'bank_transfer', '2026-05-07', '2026-05-17', '2026-05-08 07:18:10', '2026-05-08 07:18:10', NULL, NULL, 'internal', NULL, NULL),
(436, 402, 378, 411279.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-03', '2026-06-03', '2026-05-08 07:21:06', '2026-05-08 07:21:06', NULL, NULL, 'internal', NULL, NULL),
(437, 403, 379, 1200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-02', '2026-05-10', '2026-05-08 09:43:25', '2026-05-08 09:43:25', NULL, NULL, 'internal', NULL, NULL),
(438, 404, 380, 120000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-26', '2026-05-26', '2026-05-15 07:25:41', '2026-05-15 07:25:41', NULL, NULL, 'internal', NULL, NULL),
(439, 405, 381, 8640.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-10', '2026-05-20', '2026-05-15 07:31:49', '2026-05-15 07:31:49', NULL, NULL, 'internal', NULL, NULL),
(440, 406, 382, 87693.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-04-18', '2026-05-18', '2026-05-15 07:42:31', '2026-05-15 07:42:31', NULL, NULL, 'internal', NULL, NULL),
(441, 407, 383, 1728.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-12', '2026-05-22', '2026-05-15 07:51:25', '2026-05-15 07:51:25', NULL, NULL, 'internal', NULL, NULL),
(442, 408, 384, 31250.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-11', '2026-05-21', '2026-05-15 07:55:18', '2026-05-15 07:55:18', NULL, NULL, 'internal', NULL, NULL),
(443, 409, 385, 144036.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-09', '2026-05-19', '2026-05-15 08:02:50', '2026-05-15 08:02:50', NULL, NULL, 'internal', NULL, NULL),
(444, 408, 384, 40000.00, 0.00, NULL, 'UEB9X431LT', 'bank_transfer', '2026-05-11', '2026-05-22', '2026-05-15 08:04:09', '2026-05-15 08:04:09', NULL, NULL, 'internal', NULL, NULL),
(445, 410, 386, 15000.00, 0.00, NULL, 'UEE1U49IYE', 'Bank', '2026-05-14', '2026-05-24', '2026-05-15 08:13:39', '2026-05-15 08:13:53', NULL, NULL, 'internal', NULL, NULL),
(446, 411, 387, 100000.00, 0.00, NULL, '828122140763', 'Mpesa', '2026-05-11', '2026-05-25', '2026-05-15 08:16:39', '2026-05-15 08:23:56', NULL, NULL, 'internal', NULL, NULL),
(447, 412, 388, 35000.00, 0.00, NULL, 'UEBFR3O053', 'Mpesa', '2026-05-15', '2026-06-05', '2026-05-15 08:22:22', '2026-05-15 08:25:22', NULL, NULL, 'internal', NULL, NULL),
(448, 413, 389, 25000.00, 0.00, NULL, '297966502215', 'bank_transfer', '2026-05-11', '2026-05-21', '2026-05-15 08:47:11', '2026-05-15 08:47:11', NULL, NULL, 'internal', NULL, NULL),
(449, 414, 390, 30000.00, 0.00, NULL, '388220519542', 'bank_transfer', '2026-05-15', '2026-05-29', '2026-05-15 08:48:29', '2026-05-15 08:48:29', NULL, NULL, 'internal', NULL, NULL),
(450, 415, 391, 12000.00, 0.00, NULL, '643458978305', 'bank_transfer', '2026-05-14', '2026-05-24', '2026-05-15 08:50:13', '2026-05-15 08:50:13', NULL, NULL, 'internal', NULL, NULL),
(451, 416, 392, 1800.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-15', '2026-05-25', '2026-05-16 17:37:33', '2026-05-16 17:37:33', NULL, NULL, 'internal', NULL, NULL),
(452, 417, 393, 4320.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-05-16', '2026-05-26', '2026-05-19 10:07:34', '2026-05-27 05:50:30', NULL, NULL, 'internal', NULL, NULL),
(453, 418, 394, 30000.00, 0.00, NULL, 'UEHC14TQ1T', 'bank_transfer', '2026-05-17', '2026-05-27', '2026-05-19 10:12:20', '2026-05-19 10:12:20', NULL, NULL, 'internal', NULL, NULL),
(454, 419, 395, 8400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-17', '2026-05-27', '2026-05-19 10:15:19', '2026-05-19 10:15:19', NULL, NULL, 'internal', NULL, NULL),
(455, 421, 397, 22000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-16', '2026-05-26', '2026-05-19 10:22:49', '2026-05-19 10:22:49', NULL, NULL, 'internal', NULL, NULL),
(456, 422, 398, 90000.00, 0.00, NULL, 'ROLL OVER', 'Cash', '2026-05-16', '2026-05-26', '2026-05-20 07:00:46', '2026-05-20 07:01:03', NULL, NULL, 'internal', NULL, NULL),
(457, 423, 399, 3000.00, 0.00, NULL, 'UELAL4X9AF', 'bank_transfer', '2026-05-21', '2026-05-31', '2026-05-21 09:27:00', '2026-05-21 09:27:00', NULL, NULL, 'internal', NULL, NULL),
(458, 419, 395, 1600.00, 0.00, NULL, 'SPLIT', 'Mpesa', '2026-05-25', '2026-05-27', '2026-05-25 06:28:57', '2026-05-25 06:31:17', NULL, NULL, 'internal', NULL, NULL),
(459, 424, 400, 5184.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-26', '2026-06-05', '2026-05-27 05:54:33', '2026-05-27 05:54:33', NULL, NULL, 'internal', NULL, NULL),
(460, 425, 401, 1728.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-22', '2026-06-01', '2026-05-27 05:56:12', '2026-05-27 05:56:12', NULL, NULL, 'internal', NULL, NULL),
(461, 426, 402, 18000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-24', '2026-06-03', '2026-05-27 06:03:06', '2026-05-27 06:03:06', NULL, NULL, 'internal', NULL, NULL),
(462, 427, 403, 85500.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-21', '2026-05-31', '2026-05-27 06:10:34', '2026-05-27 06:10:34', NULL, NULL, 'internal', NULL, NULL),
(463, 428, 404, 2160.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-25', '2026-06-25', '2026-05-27 06:15:13', '2026-05-27 06:15:13', NULL, NULL, 'internal', NULL, NULL),
(464, 429, 405, 111307.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-27', '2026-08-27', '2026-05-28 06:28:25', '2026-05-28 06:28:25', NULL, NULL, 'internal', NULL, NULL),
(465, 430, 406, 3000.00, 0.00, NULL, 'UESHD5YGSA', 'bank_transfer', '2026-05-27', '2026-06-06', '2026-05-28 19:42:28', '2026-05-28 19:42:28', NULL, NULL, 'internal', NULL, NULL),
(466, 430, 406, 3000.00, 0.00, NULL, 'UF2HD6KKB8', 'Mpesa', '2026-06-02', '2026-06-06', '2026-06-03 07:07:10', '2026-06-03 07:09:43', NULL, NULL, 'internal', NULL, NULL),
(467, 430, 406, 3000.00, 0.00, NULL, 'UETHD6417C', 'Mpesa', '2026-05-29', '2026-06-06', '2026-06-03 07:09:09', '2026-06-03 07:09:29', NULL, NULL, 'internal', NULL, NULL),
(468, 431, 407, 1500.00, 0.00, NULL, 'UET6B5TQ81', 'bank_transfer', '2026-05-28', '2026-06-06', '2026-06-03 07:27:51', '2026-06-03 07:27:51', NULL, NULL, 'internal', NULL, NULL),
(469, 431, 407, 1500.00, 0.00, NULL, 'ROLL OVER', 'bank_transfer', '2026-05-27', '2026-06-06', '2026-06-03 07:28:00', '2026-06-03 07:28:00', NULL, NULL, 'internal', NULL, NULL),
(470, 432, 408, 102600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-31', '2026-06-10', '2026-06-03 07:34:30', '2026-06-03 07:34:30', NULL, NULL, 'internal', NULL, NULL),
(471, 433, 409, 14000.00, 0.00, NULL, 'UEUAL5ZRUE', 'bank_transfer', '2026-05-30', '2026-06-09', '2026-06-03 07:42:59', '2026-06-03 07:42:59', NULL, NULL, 'internal', NULL, NULL),
(472, 420, 396, 105231.05, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-05-18', '2026-06-18', '2026-06-03 07:43:57', '2026-06-03 07:44:22', NULL, NULL, 'internal', NULL, NULL),
(473, 434, 410, 21000.00, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-05-27', '2026-06-06', '2026-06-03 07:47:56', '2026-06-03 07:48:09', NULL, NULL, 'internal', NULL, NULL),
(474, 435, 411, 2074.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-01', '2026-06-11', '2026-06-03 10:02:02', '2026-06-03 10:02:02', NULL, NULL, 'internal', NULL, NULL),
(475, 436, 412, 124000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-05-26', '2026-06-26', '2026-06-03 10:16:51', '2026-06-03 10:16:51', NULL, NULL, 'internal', NULL, NULL),
(476, 437, 413, 10000.00, 0.00, NULL, 'UF66B6QSCC', 'bank_transfer', '2026-06-06', '2026-06-16', '2026-06-07 09:46:26', '2026-06-07 09:46:26', NULL, NULL, 'internal', NULL, NULL),
(477, 437, 413, 600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-06', '2026-06-16', '2026-06-07 09:46:40', '2026-06-07 09:46:40', NULL, NULL, 'internal', NULL, NULL),
(478, 438, 414, 10800.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-06', '2026-06-16', '2026-06-08 07:29:28', '2026-06-08 07:29:28', NULL, NULL, 'internal', NULL, NULL),
(479, 439, 415, 17200.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-06', '2026-06-16', '2026-06-08 07:31:44', '2026-06-08 07:31:44', NULL, NULL, 'internal', NULL, NULL),
(480, 440, 416, 6220.80, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-06-05', '2026-06-15', '2026-06-08 07:38:53', '2026-06-08 07:39:07', NULL, NULL, 'internal', NULL, NULL),
(481, 441, 417, 42000.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-05', '2026-06-15', '2026-06-08 09:35:52', '2026-06-26 13:08:20', NULL, NULL, 'internal', NULL, NULL),
(482, 442, 418, 493534.10, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-06-03', '2026-07-03', '2026-06-08 09:38:07', '2026-06-08 09:38:20', NULL, NULL, 'internal', NULL, NULL),
(483, 443, 419, 21600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-03', '2026-06-13', '2026-06-09 07:10:59', '2026-06-09 07:10:59', NULL, NULL, 'internal', NULL, NULL),
(484, 443, 419, 21600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-03', '2026-06-13', '2026-06-09 07:11:00', '2026-06-09 07:11:00', NULL, NULL, 'internal', NULL, NULL),
(485, 438, 414, 3000.00, 0.00, NULL, 'UF8HD79QT8', 'bank_transfer', '2026-06-09', '2026-06-16', '2026-06-12 04:08:45', '2026-06-12 04:08:45', NULL, NULL, 'internal', NULL, NULL),
(486, 299, 278, 50000.00, 0.00, NULL, '557091649422', 'bank_transfer', '2026-06-09', '2026-09-09', '2026-06-14 03:11:27', '2026-06-14 03:11:27', NULL, NULL, 'internal', NULL, NULL),
(487, 429, 405, 19000.00, 0.00, NULL, '4567IUGE6242', 'bank_transfer', '2026-06-13', '2026-08-27', '2026-06-14 03:12:52', '2026-06-14 03:12:52', NULL, NULL, 'internal', NULL, NULL),
(488, 444, 420, 25920.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-13', '2026-06-23', '2026-06-14 03:16:29', '2026-06-14 03:16:29', NULL, NULL, 'internal', NULL, NULL),
(489, 445, 421, 123120.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-10', '2026-06-20', '2026-06-14 03:21:49', '2026-06-14 03:21:49', NULL, NULL, 'internal', NULL, NULL),
(490, 445, 421, 187245.92, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-06-10', '2026-06-20', '2026-06-14 03:22:09', '2026-06-14 03:22:20', NULL, NULL, 'internal', NULL, NULL),
(491, 446, 422, 2488.32, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-06-11', '2026-06-21', '2026-06-14 03:27:20', '2026-06-14 03:27:31', NULL, NULL, 'internal', NULL, NULL),
(492, 447, 423, 7465.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-15', '2026-06-25', '2026-06-16 02:35:27', '2026-06-16 02:35:27', NULL, NULL, 'internal', NULL, NULL),
(493, 448, 424, 16560.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-16', '2026-06-26', '2026-06-17 06:58:06', '2026-06-17 06:58:06', NULL, NULL, 'internal', NULL, NULL),
(494, 449, 425, 12720.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-16', '2026-06-26', '2026-06-18 11:13:26', '2026-06-18 11:13:26', NULL, NULL, 'internal', NULL, NULL),
(495, 450, 426, 40000.00, 0.00, NULL, 'UFKCJ8GUVT', 'bank_transfer', '2026-06-20', '2026-06-30', '2026-06-20 12:01:32', '2026-06-20 12:01:32', NULL, NULL, 'internal', NULL, NULL),
(496, 451, 427, 10000.00, 0.00, NULL, 'UFAAL79NHH', 'bank_transfer', '2026-06-10', '2026-06-20', '2026-06-21 09:19:56', '2026-06-21 09:19:56', NULL, NULL, 'internal', NULL, NULL),
(497, 452, 428, 2986.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-21', '2026-07-01', '2026-06-25 08:41:14', '2026-06-25 08:41:14', NULL, NULL, 'internal', NULL, NULL),
(498, 453, 429, 10000.00, 0.00, NULL, 'UFO6O8SPHT', 'bank_transfer', '2026-06-24', '2026-07-03', '2026-06-25 08:42:42', '2026-06-25 08:42:42', NULL, NULL, 'internal', NULL, NULL),
(499, 454, 430, 50000.00, 0.00, NULL, '241310635800', 'bank_transfer', '2026-06-23', '2026-07-02', '2026-06-25 08:44:15', '2026-06-25 08:44:15', NULL, NULL, 'internal', NULL, NULL),
(500, 455, 431, 10000.00, 0.00, NULL, 'UFMD88NRVB', 'bank_transfer', '2026-06-22', '2026-07-02', '2026-06-25 08:46:02', '2026-06-25 08:46:02', NULL, NULL, 'internal', NULL, NULL),
(501, 456, 432, 126277.30, 0.00, NULL, 'ROLL OVER', 'Mpesa', '2026-05-18', '2026-06-18', '2026-06-25 09:00:41', '2026-06-25 09:00:50', NULL, NULL, 'internal', NULL, NULL),
(502, 457, 433, 5000.00, 0.00, NULL, '4680UJKH0648', 'mpesa', '2026-06-29', '2026-07-06', '2026-06-29 04:43:33', '2026-06-29 04:44:30', NULL, NULL, 'internal', NULL, NULL),
(503, 457, 433, 19872.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-07-06', '2026-06-29 04:44:18', '2026-06-29 04:44:18', NULL, NULL, 'internal', NULL, NULL),
(504, 457, 433, 3000.00, 0.00, NULL, '4706POUJ0720', 'mpesa', '2026-06-30', '2026-07-06', '2026-06-30 15:02:31', '2026-06-30 15:02:31', NULL, NULL, 'internal', NULL, NULL),
(505, 457, 433, 3000.00, 0.00, NULL, '4707UVIY5297', 'mpesa', '2026-06-30', '2026-07-06', '2026-06-30 15:02:53', '2026-06-30 15:02:53', NULL, NULL, 'internal', NULL, NULL),
(506, 458, 434, 50000.00, 0.00, NULL, '971324547580', 'pesalink', '2026-06-30', '2026-07-10', '2026-06-30 15:06:17', '2026-06-30 15:06:17', NULL, NULL, 'internal', NULL, NULL),
(507, 458, 434, 50000.00, 0.00, NULL, '903781824669', 'pesalink', '2026-06-30', '2026-07-10', '2026-06-30 15:06:38', '2026-06-30 15:06:38', NULL, NULL, 'internal', NULL, NULL),
(508, 458, 434, 50000.00, 0.00, NULL, '481253015962', 'pesalink', '2026-06-30', '2026-07-10', '2026-06-30 15:07:01', '2026-06-30 15:07:01', NULL, NULL, 'internal', NULL, NULL),
(509, 459, 435, 15264.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-07-10', '2026-07-01 11:42:18', '2026-07-01 11:42:18', NULL, NULL, 'internal', NULL, NULL),
(510, 460, 436, 21400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-07-06', '2026-07-01 11:46:37', '2026-07-01 11:46:37', NULL, NULL, 'internal', NULL, NULL),
(511, 461, 437, 85600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-20', '2026-09-20', '2026-07-01 11:50:46', '2026-07-01 11:50:46', NULL, NULL, 'internal', NULL, NULL),
(512, 462, 438, 151532.80, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-18', '2026-07-18', '2026-07-01 11:53:18', '2026-07-01 11:53:18', NULL, NULL, 'internal', NULL, NULL),
(513, 463, 439, 592240.92, 0.00, NULL, 'ROLL OVER', 'cash', '2026-07-03', '2026-08-03', '2026-07-03 12:36:39', '2026-07-03 12:36:39', NULL, NULL, 'internal', NULL, NULL),
(514, 464, 440, 3583.20, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-01', '2026-07-11', '2026-07-04 16:33:56', '2026-07-04 16:33:56', NULL, NULL, 'internal', NULL, NULL),
(515, 465, 441, 8958.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-06-25', '2026-07-05', '2026-07-04 16:38:05', '2026-07-04 16:38:05', NULL, NULL, 'internal', NULL, NULL),
(516, 466, 442, 20000.00, 0.00, NULL, '4686EOJL2669', 'mpesa', '2026-06-26', '2026-07-05', '2026-07-04 16:42:26', '2026-07-04 16:42:26', NULL, NULL, 'internal', NULL, NULL),
(517, 467, 443, 30000.00, 0.00, NULL, '4747XQNR1529', 'mpesa', '2026-07-04', '2026-07-04', '2026-07-04 16:46:41', '2026-07-04 16:46:41', NULL, NULL, 'internal', NULL, NULL),
(518, 468, 444, 100000.00, 0.00, NULL, '4746FPNN3173', 'mpesa', '2026-07-04', '2026-07-04', '2026-07-04 16:48:04', '2026-07-04 16:48:04', NULL, NULL, 'internal', NULL, NULL),
(519, 469, 445, 10000.00, 0.00, NULL, '718191439198', 'pesalink', '2026-07-04', '2026-07-14', '2026-07-04 16:50:52', '2026-07-04 16:52:28', NULL, NULL, 'internal', NULL, NULL),
(520, 469, 445, 50000.00, 0.00, NULL, '025301778612', 'pesalink', '2026-07-04', '2026-07-14', '2026-07-04 16:52:18', '2026-07-04 16:52:18', NULL, NULL, 'internal', NULL, NULL),
(521, 470, 446, 2592.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-06-25', '2026-07-25', '2026-07-04 17:07:14', '2026-07-04 17:07:14', NULL, NULL, 'internal', NULL, NULL),
(522, 471, 447, 60000.00, 0.00, NULL, 'UG63XAM1SV', 'mpesa', '2026-07-06', '2026-07-06', '2026-07-06 18:06:18', '2026-07-06 18:06:18', NULL, NULL, 'internal', NULL, NULL),
(523, 472, 448, 240000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-10', '2026-07-20', '2026-07-10 08:36:34', '2026-07-10 08:36:34', NULL, NULL, 'internal', NULL, NULL),
(524, 473, 449, 18316.80, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-06', '2026-07-16', '2026-07-10 08:43:43', '2026-07-10 08:43:43', NULL, NULL, 'internal', NULL, NULL),
(525, 474, 450, 10000.00, 0.00, NULL, 'UGD6OAX82C', 'mpesa', '2026-07-13', '2026-07-23', '2026-07-13 08:50:54', '2026-07-13 08:50:54', NULL, NULL, 'internal', NULL, NULL),
(526, 475, 451, 37046.40, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-06', '2026-07-16', '2026-07-13 09:03:09', '2026-07-13 09:03:09', NULL, NULL, 'internal', NULL, NULL),
(527, 476, 452, 5159.81, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-11', '2026-07-21', '2026-07-13 09:06:24', '2026-07-13 09:06:24', NULL, NULL, 'internal', NULL, NULL),
(528, 477, 453, 36000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-14', '2026-07-28', '2026-07-16 06:04:44', '2026-07-16 06:04:44', NULL, NULL, 'internal', NULL, NULL),
(529, 478, 454, 44455.68, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-16', '2026-07-26', '2026-07-17 03:54:07', '2026-07-17 03:54:07', NULL, NULL, 'internal', NULL, NULL),
(530, 479, 455, 21980.16, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-07-26', '2026-07-17 07:51:49', '2026-07-17 07:51:49', NULL, NULL, 'internal', NULL, NULL),
(531, 480, 456, 32000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-16', '2026-07-26', '2026-07-18 15:13:13', '2026-07-18 15:13:13', NULL, NULL, 'internal', NULL, NULL),
(532, 481, 457, 288000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-20', '2026-07-30', '2026-07-20 09:16:47', '2026-07-20 09:16:47', NULL, NULL, 'internal', NULL, NULL),
(533, 482, 458, 50000.00, 0.00, NULL, '833201967079', 'bank_transfer', '2026-07-20', '2026-07-30', '2026-07-21 08:28:32', '2026-07-21 08:28:32', NULL, NULL, 'internal', NULL, NULL),
(534, 483, 459, 6191.80, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-21', '2026-07-31', '2026-07-22 06:29:01', '2026-07-22 06:29:01', NULL, NULL, 'internal', NULL, NULL),
(535, 484, 460, 25000.00, 0.00, NULL, '4921XQHA9734', 'mpesa', '2026-07-24', '2026-07-25', '2026-07-25 12:13:19', '2026-07-25 12:13:19', NULL, NULL, 'internal', NULL, NULL),
(536, 482, 458, 10000.00, 0.00, NULL, '715243356247', 'bank_transfer', '2026-07-25', '2026-08-04', '2026-07-25 12:15:25', '2026-07-25 12:15:25', NULL, NULL, 'internal', NULL, NULL),
(537, 485, 461, 6200.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-25', '2026-08-04', '2026-07-25 12:18:55', '2026-07-25 12:18:55', NULL, NULL, 'internal', NULL, NULL),
(538, 487, 463, 38400.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-08-05', '2026-07-30 07:05:36', '2026-07-30 07:05:36', NULL, NULL, 'internal', NULL, NULL),
(539, 488, 464, 374400.00, 0.00, NULL, 'ROLLED OVER', 'other', '2026-07-30', '2026-08-09', '2026-08-03 14:22:02', '2026-08-03 14:22:02', NULL, NULL, 'internal', NULL, NULL),
(540, 489, 465, 53346.82, 0.00, NULL, 'ROLLED OVER', 'other', '2026-07-26', '2026-08-07', '2026-08-03 15:54:23', '2026-08-03 15:54:23', NULL, NULL, 'internal', NULL, NULL),
(541, 489, 465, 2300.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-08-06', '2026-08-03 15:55:30', '2026-08-03 15:55:30', NULL, NULL, 'internal', NULL, NULL),
(542, 490, 466, 40000.00, 0.00, NULL, '176506656852', 'bank_transfer', '2026-07-31', '2026-08-09', '2026-08-03 16:00:44', '2026-08-03 16:00:44', NULL, NULL, 'internal', NULL, NULL),
(543, 482, 458, 10000.00, 0.00, NULL, '303581162325', 'other', '2026-08-27', '2026-08-09', '2026-08-03 16:02:08', '2026-08-03 16:02:08', NULL, NULL, 'internal', NULL, NULL),
(544, 490, 466, 84000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-30', '2026-08-09', '2026-08-03 16:05:52', '2026-08-03 16:06:12', NULL, NULL, 'internal', NULL, NULL),
(545, 491, 467, 7430.16, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-31', '2026-08-10', '2026-08-03 16:26:12', '2026-08-03 16:26:12', NULL, NULL, 'internal', NULL, NULL),
(546, 486, 462, 31651.43, 0.00, 31651.43, 'ROLL OVER', 'other', '2026-07-26', '2026-08-05', '2026-08-06 15:16:10', '2026-08-06 15:16:19', NULL, NULL, NULL, NULL, NULL),
(547, 492, 512, 66776.18, 0.00, 66776.18, 'ROLL OVER', 'other', '2026-08-05', '2026-08-15', '2026-08-07 18:57:18', '2026-08-07 18:57:18', NULL, NULL, NULL, NULL, NULL),
(548, 492, 512, 500.00, 0.00, 500.00, 'UH66O1QDOC', 'mpesa', '2026-08-08', '2026-08-15', '2026-08-07 19:04:29', '2026-08-07 19:04:29', NULL, NULL, NULL, NULL, NULL),
(549, 492, 512, 500.00, 0.00, 500.00, '5041TPTR3250', 'mpesa', '2026-08-08', '2026-08-15', '2026-08-07 19:04:58', '2026-08-07 19:04:58', NULL, NULL, NULL, NULL, NULL),
(550, 492, 512, 350.00, 0.00, 350.00, '4962FLZC1339', 'mpesa', '2026-07-31', '2026-08-15', '2026-08-07 19:06:33', '2026-08-07 19:06:33', NULL, NULL, NULL, NULL, NULL),
(551, 492, 512, 4650.00, 0.00, 4650.00, '4981LNRT9006', 'mpesa', '2026-07-31', '2026-08-15', '2026-08-07 19:07:05', '2026-08-07 19:07:05', NULL, NULL, NULL, NULL, NULL),
(552, 493, 522, 6000.00, 0.00, 6000.00, '631609340677', 'pesalink', '2026-07-08', '2026-07-08', '2026-08-11 15:41:48', '2026-08-11 15:42:32', NULL, NULL, NULL, NULL, NULL),
(553, 494, 523, 40000.00, 0.00, 40000.00, '5042WPHI6999', 'mpesa', '2026-08-07', '2026-08-11', '2026-08-11 15:53:34', '2026-08-11 15:53:34', NULL, NULL, NULL, NULL, NULL),
(554, 495, 524, 5000.00, 0.00, 5000.00, 'UH93K2Z5RP', 'mpesa', '2026-08-09', '2026-08-19', '2026-08-11 15:59:09', '2026-08-11 15:59:09', NULL, NULL, NULL, NULL, NULL),
(555, 496, 525, 12000.00, 0.00, 12000.00, '5042UGAC1109', 'mpesa', '2026-08-11', '2026-08-11', '2026-08-11 16:01:15', '2026-08-11 16:01:15', NULL, NULL, NULL, NULL, NULL),
(556, 490, 466, 8000.00, 0.00, 8000.00, '554114567016', 'mpesa', '2026-08-09', '2026-08-19', '2026-08-11 16:09:01', '2026-08-11 16:09:01', NULL, NULL, NULL, NULL, NULL),
(557, 486, 462, 1000.00, 0.00, 1000.00, 'UHI6O349NX', 'mpesa', '2026-08-18', '2026-08-28', '2026-08-20 09:47:08', '2026-08-20 09:47:08', NULL, NULL, NULL, NULL, NULL),
(558, 497, 530, 5000.00, 0.00, 5000.00, '5144AWPI3425', 'mpesa', '2026-08-19', '2026-08-29', '2026-08-24 08:14:43', '2026-08-24 08:14:43', NULL, NULL, NULL, NULL, NULL),
(559, 514, 566, 50000.00, 0.00, 50000.00, '5085FFVN6168', 'mpesa', '2026-08-12', '2026-08-26', '2026-08-24 08:17:59', '2026-08-24 08:17:59', NULL, NULL, NULL, NULL, NULL),
(560, 499, 532, 50000.00, 0.00, 50000.00, '5085SNZP7491', 'mpesa', '2026-08-12', '2026-09-12', '2026-08-24 08:20:56', '2026-08-24 08:20:56', NULL, NULL, NULL, NULL, NULL),
(561, 500, 533, 10000.00, 0.00, 10000.00, 'UHKHD3RXAI', 'mpesa', '2026-08-20', '2026-08-24', '2026-08-24 10:54:24', '2026-08-24 10:54:24', NULL, NULL, NULL, NULL, NULL),
(562, 501, 534, 5000.00, 0.00, 5000.00, 'UHOMY3NZEZ', 'mpesa', '2026-08-24', '2026-09-03', '2026-08-24 10:57:35', '2026-08-24 10:57:35', NULL, NULL, NULL, NULL, NULL),
(563, 501, 534, 5000.00, 0.00, 5000.00, 'UHORJ3G9LJ', 'mpesa', '2026-08-24', '2026-09-03', '2026-08-24 10:58:00', '2026-08-24 10:58:00', NULL, NULL, NULL, NULL, NULL),
(564, 502, 535, 10000.00, 0.00, 10000.00, 'UHNMY3MYT8', 'mpesa', '2026-08-23', '2026-09-02', '2026-08-24 11:00:48', '2026-08-24 11:00:48', NULL, NULL, NULL, NULL, NULL),
(565, 503, 536, 20000.00, 0.00, 20000.00, '5178IKXR5460', 'mpesa', '2026-08-23', '2026-09-02', '2026-08-24 11:17:34', '2026-08-24 11:17:34', NULL, NULL, NULL, NULL, NULL),
(566, 498, 531, 50000.00, 0.00, 50000.00, '5178DZNG4811', 'mpesa', '2026-08-23', '2026-09-02', '2026-08-24 11:18:40', '2026-08-24 11:18:40', NULL, NULL, NULL, NULL, NULL),
(567, 504, 537, 11500.00, 0.00, 11500.00, '5091ZTOV3699', 'mpesa', '2026-08-13', '2026-08-23', '2026-08-24 11:53:48', '2026-08-24 11:53:48', NULL, NULL, NULL, NULL, NULL),
(568, 505, 545, 10000.00, 0.00, 10000.00, '5246CCKI8407', 'mpesa', '2026-08-31', '2026-08-31', '2026-08-31 08:30:41', '2026-08-31 08:30:41', NULL, NULL, NULL, NULL, NULL),
(569, 506, 547, 8000.00, 0.00, 8000.00, '5245HNFG8643', 'mpesa', '2026-08-31', '2026-08-31', '2026-08-31 08:35:10', '2026-08-31 08:35:10', NULL, NULL, NULL, NULL, NULL),
(570, 507, 548, 5000.00, 0.00, 5000.00, '5216TFND6933', 'mpesa', '2026-08-27', '2026-09-06', '2026-08-31 08:52:21', '2026-08-31 08:53:12', NULL, NULL, NULL, NULL, NULL),
(571, 508, 552, 30000.00, 0.00, 30000.00, 'UHS6O478QA', 'mpesa', '2026-08-28', '2026-09-07', '2026-08-31 09:12:24', '2026-08-31 09:12:24', NULL, NULL, NULL, NULL, NULL),
(572, 508, 552, 7500.00, 0.00, 7500.00, 'ROLL OVER', 'other', '2026-08-28', '2026-09-07', '2026-08-31 09:12:58', '2026-08-31 14:09:56', NULL, NULL, NULL, NULL, NULL),
(573, 509, 553, 6000.00, 0.00, 6000.00, '5241AMUV7360', 'mpesa', '2026-08-30', '2026-08-31', '2026-08-31 13:50:49', '2026-08-31 13:50:49', NULL, NULL, NULL, NULL, NULL),
(574, 505, 545, 5000.00, 0.00, 5000.00, '5256FOEC6592', 'mpesa', '2026-09-01', '2026-09-10', '2026-09-01 12:05:50', '2026-09-01 12:05:50', NULL, NULL, NULL, NULL, NULL),
(575, 505, 545, 5000.00, 0.00, 5000.00, '5265EGFK2786', 'mpesa', '2026-09-02', '2026-09-02', '2026-09-02 07:56:36', '2026-09-02 07:56:36', NULL, NULL, NULL, NULL, NULL),
(576, 510, 556, 30000.00, 0.00, 30000.00, '981188631990', 'bank_transfer', '2026-09-03', '2026-09-24', '2026-09-03 07:14:20', '2026-09-03 07:14:33', NULL, NULL, NULL, NULL, NULL),
(577, 511, 557, 49000.00, 0.00, 49000.00, 'UI16O4QJBR', 'mpesa', '2026-09-01', '2027-05-29', '2026-09-03 07:24:06', '2026-09-03 07:24:06', NULL, NULL, NULL, NULL, NULL),
(578, 511, 557, 50000.00, 0.00, 50000.00, 'UET6O5QG5M', 'mpesa', '2026-06-01', '2027-05-29', '2026-09-03 07:25:48', '2026-09-03 07:25:48', NULL, NULL, NULL, NULL, NULL),
(579, 512, 561, 20000.00, 0.00, 20000.00, '5282YNTU6981', 'mpesa', '2026-09-04', '2026-09-04', '2026-09-04 11:15:57', '2026-09-04 11:15:57', NULL, NULL, NULL, NULL, NULL),
(580, 513, 562, 25000.00, 0.00, 25000.00, '5282XBFF6427', 'mpesa', '2026-09-04', '2026-09-04', '2026-09-04 11:18:56', '2026-09-04 11:18:56', NULL, NULL, NULL, NULL, NULL),
(581, 513, 562, 15000.00, 0.00, 15000.00, '5284ZGCD5788', 'mpesa', '2026-09-04', '2026-09-04', '2026-09-04 13:50:14', '2026-09-04 13:50:14', NULL, NULL, NULL, NULL, NULL),
(582, 513, 562, 10000.00, 0.00, 10000.00, '5291DJTU6274', 'mpesa', '2026-09-05', '2026-09-15', '2026-09-07 06:21:26', '2026-09-07 06:21:26', NULL, NULL, NULL, NULL, NULL),
(583, 515, 567, 30000.00, 0.00, 30000.00, '5309NGJG5645', 'mpesa', '2026-09-07', '2026-09-17', '2026-09-09 09:14:48', '2026-09-09 09:14:48', NULL, NULL, NULL, NULL, NULL),
(584, 510, 556, 10000.00, 0.00, 10000.00, '5281WGZP1721', 'mpesa', '2026-09-04', '2026-09-23', '2026-09-09 09:16:24', '2026-09-09 09:16:24', NULL, NULL, NULL, NULL, NULL);
INSERT INTO `disbursements` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `disburse_date`, `payment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `funding_source`, `investment_id`, `notes`) VALUES
(585, 510, 556, 15000.00, 0.00, 15000.00, '210357649169', 'pesalink', '2026-07-07', '2026-07-23', '2026-09-09 09:16:49', '2026-09-09 09:16:49', NULL, NULL, NULL, NULL, NULL),
(586, 515, 567, 5000.00, 0.00, 5000.00, '5335OIDS0723', 'mpesa', '2026-09-10', '2026-09-19', '2026-09-11 05:10:08', '2026-09-11 05:10:08', NULL, NULL, NULL, NULL, NULL),
(587, 510, 556, 15000.00, 0.00, 15000.00, '808318175592', 'pesalink', '2026-10-09', '2026-10-09', '2026-09-11 05:11:09', '2026-09-11 05:11:09', NULL, NULL, NULL, NULL, NULL),
(588, 510, 556, 5000.00, 0.00, 5000.00, '002207088354', 'pesalink', '2026-09-12', '2026-09-22', '2026-09-14 07:28:22', '2026-09-14 07:28:22', NULL, NULL, NULL, NULL, NULL),
(589, 517, 570, 5000.00, 0.00, 5000.00, '728113386117', 'pesalink', '2026-09-12', '2026-09-22', '2026-09-14 07:41:24', '2026-09-14 07:41:24', NULL, NULL, NULL, NULL, NULL),
(590, 517, 570, 15000.00, 0.00, 15000.00, '487532155591', 'pesalink', '2026-09-12', '2026-09-22', '2026-09-14 07:41:59', '2026-09-14 07:41:59', NULL, NULL, NULL, NULL, NULL),
(591, 515, 567, 10000.00, 0.00, 10000.00, '5350MPJG1726', 'mpesa', '2026-09-12', '2026-09-22', '2026-09-14 07:46:43', '2026-09-14 07:46:43', NULL, NULL, NULL, NULL, NULL),
(592, 516, 569, 20000.00, 0.00, 20000.00, '5343WFOB0762', 'mpesa', '2026-09-11', '2026-09-21', '2026-09-14 07:49:59', '2026-09-14 07:49:59', NULL, NULL, NULL, NULL, NULL),
(593, 510, 556, 5000.00, 0.00, 5000.00, '462251139952', 'pesalink', '2026-09-14', '2026-09-14', '2026-09-14 14:05:33', '2026-09-14 14:05:33', NULL, NULL, NULL, NULL, NULL),
(594, 510, 556, 10000.00, 0.00, 10000.00, '879947519433', 'pesalink', '2026-09-14', '2026-09-14', '2026-09-14 14:06:29', '2026-09-14 14:06:29', NULL, NULL, NULL, NULL, NULL),
(595, 518, 571, 10000.00, 0.00, 10000.00, '5371RDKT0555', 'mpesa', '2026-09-14', '2026-09-24', '2026-09-14 14:09:26', '2026-09-14 14:09:26', NULL, NULL, NULL, NULL, NULL),
(596, 519, 572, 1000.00, 0.00, 1000.00, '5343BLII9290', 'mpesa', '2026-09-11', '2026-09-14', '2026-09-14 14:23:44', '2026-09-14 14:23:44', NULL, NULL, NULL, NULL, NULL),
(597, 519, 572, 9000.00, 0.00, 9000.00, 'EQA2B3538E82181', 'mpesa', '2026-09-12', '2026-09-21', '2026-09-14 14:24:25', '2026-09-14 14:24:25', NULL, NULL, NULL, NULL, NULL),
(598, 510, 556, 20000.00, 0.00, 20000.00, '889221618005', 'pesalink', '2026-09-15', '2026-09-23', '2026-09-15 17:53:46', '2026-09-15 17:53:46', NULL, NULL, NULL, NULL, NULL),
(599, 510, 556, 50000.00, 0.00, 50000.00, '191809662337', 'pesalink', '2026-09-15', '2026-09-23', '2026-09-15 17:56:01', '2026-09-15 17:56:01', NULL, NULL, NULL, NULL, NULL),
(600, 520, 574, 30000.00, 0.00, 30000.00, '5379FQPK2634', 'mpesa', '2026-09-15', '2026-09-25', '2026-09-15 17:57:52', '2026-09-15 17:57:52', NULL, NULL, NULL, NULL, NULL),
(601, 521, 575, 5000.00, 0.00, 5000.00, '5379GDZE2677', 'mpesa', '2026-09-15', '2026-09-25', '2026-09-15 17:59:03', '2026-09-15 17:59:03', NULL, NULL, NULL, NULL, NULL),
(602, 511, 557, 30000.00, 0.00, 30000.00, 'UIG6O6FFLJ', 'mpesa', '2026-09-16', '2026-09-16', '2026-09-16 06:28:00', '2026-09-16 06:28:00', NULL, NULL, NULL, NULL, NULL),
(603, 522, 578, 2000.00, 0.00, 2000.00, '5380EXFQ4215', 'mpesa', '2026-09-16', '2026-09-16', '2026-09-16 07:08:01', '2026-09-16 07:08:01', NULL, NULL, NULL, NULL, NULL),
(604, 522, 578, 2000.00, 0.00, 2000.00, '5388AKEA2620', 'mpesa', '2026-09-16', '2026-09-26', '2026-09-17 13:09:30', '2026-09-17 13:09:30', NULL, NULL, NULL, NULL, NULL),
(605, 523, 580, 10000.00, 0.00, 10000.00, '5399WSLM3604', 'mpesa', '2026-09-17', '2026-09-27', '2026-09-18 14:04:05', '2026-09-18 14:04:05', NULL, NULL, NULL, NULL, NULL),
(606, 524, 581, 10000.00, 0.00, 10000.00, '5394STZV9925', 'mpesa', '2026-09-17', '2026-09-27', '2026-09-18 14:06:31', '2026-09-18 14:06:44', NULL, NULL, NULL, NULL, NULL),
(607, 525, 588, 2000.00, 0.00, 2000.00, '5444VWSI8612', 'mpesa', '2026-09-23', '2026-10-03', '2026-09-23 06:17:42', '2026-09-23 06:17:42', NULL, NULL, NULL, NULL, NULL),
(608, 526, 590, 20000.00, 0.00, 20000.00, '976022556995', 'pesalink', '2026-09-24', '2026-10-04', '2026-09-25 09:38:06', '2026-09-25 09:38:06', NULL, NULL, NULL, NULL, NULL),
(609, 526, 590, 3800.00, 0.00, 3800.00, '856986072258', 'pesalink', '2026-09-24', '2026-10-04', '2026-09-25 09:38:55', '2026-09-25 09:38:55', NULL, NULL, NULL, NULL, NULL),
(610, 526, 590, 1200.00, 0.00, 1200.00, 'UIOLP79PSH', 'mpesa', '2026-09-24', '2026-10-04', '2026-09-25 09:39:28', '2026-09-25 09:39:28', NULL, NULL, NULL, NULL, NULL),
(611, 511, 557, 30000.00, 0.00, 30000.00, 'UIN6O7BGRB', 'mpesa', '2026-09-23', '2026-09-23', '2026-09-25 09:59:16', '2026-09-25 09:59:16', NULL, NULL, NULL, NULL, NULL),
(612, 511, 557, 1555.00, 0.00, 1555.00, '5428CNQF2335', 'mpesa', '2026-09-21', '2026-09-25', '2026-09-25 09:59:59', '2026-09-25 09:59:59', NULL, NULL, NULL, NULL, NULL),
(613, 527, 591, 1000.00, 0.00, 1000.00, '5423QIKQ0240', 'mpesa', '2026-09-20', '2026-09-30', '2026-09-25 10:19:18', '2026-09-25 10:19:18', NULL, NULL, NULL, NULL, NULL),
(614, 510, 556, 12520.00, 0.00, 12520.00, 'CASH', 'cash', '2026-09-23', '2026-09-23', '2026-09-25 10:27:30', '2026-09-25 10:27:30', NULL, NULL, NULL, NULL, NULL),
(615, 528, 592, 10000.00, 0.00, 10000.00, '500582912466', 'pesalink', '2026-09-26', '2026-10-05', '2026-09-26 14:50:55', '2026-09-26 14:50:55', NULL, NULL, NULL, NULL, NULL),
(616, 529, 593, 12000.00, 0.00, 12000.00, '5464LBAC0477', 'mpesa', '2026-09-26', '2026-10-06', '2026-09-26 14:52:08', '2026-09-26 14:52:08', NULL, NULL, NULL, NULL, NULL),
(617, 530, 595, 10000.00, 0.00, 10000.00, '5474QDKZ5845', 'mpesa', '2026-09-26', '2026-10-06', '2026-09-28 08:29:34', '2026-09-28 08:29:34', NULL, NULL, NULL, NULL, NULL),
(618, 531, 596, 5000.00, 0.00, 5000.00, '5482OPZQ2442', 'mpesa', '2026-09-27', '2026-10-07', '2026-09-28 08:35:13', '2026-09-28 08:35:13', NULL, NULL, NULL, NULL, NULL),
(619, 531, 596, 4000.00, 0.00, 4000.00, '5484LTRF0571', 'mpesa', '2026-09-27', '2026-10-07', '2026-09-28 08:35:29', '2026-09-28 08:35:29', NULL, NULL, NULL, NULL, NULL),
(620, 532, 598, 50000.00, 0.00, 50000.00, '140421570155', 'mpesa', '2026-09-30', '2026-10-10', '2026-09-30 12:41:59', '2026-09-30 12:41:59', NULL, NULL, NULL, NULL, NULL),
(621, 511, 557, 26000.00, 0.00, 26000.00, 'UIU6O85HM2', 'mpesa', '2026-09-30', '2026-09-30', '2026-09-30 13:22:25', '2026-09-30 13:22:25', NULL, NULL, NULL, NULL, NULL),
(622, 510, 597, 30000.00, 0.00, 30000.00, '486981739188', 'bank_transfer', '2026-10-01', '2026-10-01', '2026-10-01 06:56:38', '2026-10-01 06:57:06', NULL, NULL, NULL, NULL, NULL),
(623, 510, 597, 22000.00, 0.00, 22000.00, '365114536204', 'mpesa', '2026-10-01', '2026-10-20', '2026-10-02 11:27:56', '2026-10-02 11:27:56', NULL, NULL, NULL, NULL, NULL),
(624, 533, 602, 7000.00, 0.00, 7000.00, '5516OLGC1997', 'mpesa', '2026-10-01', '2026-10-10', '2026-10-03 13:26:33', '2026-10-03 13:26:33', NULL, NULL, NULL, NULL, NULL),
(625, 533, 602, 3000.00, 0.00, 3000.00, '5517QHRD8595', 'mpesa', '2026-10-01', '2026-10-10', '2026-10-03 13:27:07', '2026-10-03 13:27:07', NULL, NULL, NULL, NULL, NULL),
(626, 533, 602, 5000.00, 0.00, 5000.00, '5544MENR0809', 'mpesa', '2026-10-04', '2026-10-10', '2026-10-05 12:35:04', '2026-10-05 12:35:04', NULL, NULL, NULL, NULL, NULL),
(627, 510, 597, 50000.00, 0.00, 50000.00, '199104399470', 'mpesa', '2026-10-05', '2026-10-20', '2026-10-05 12:36:25', '2026-10-05 12:36:25', NULL, NULL, NULL, NULL, NULL),
(628, 534, 607, 20000.00, 0.00, 20000.00, '5576ILZV9028', 'mpesa', '2026-10-08', '2026-10-08', '2026-10-08 09:34:07', '2026-10-08 09:34:35', NULL, NULL, 'partner', 1, NULL),
(629, 510, 597, 10000.00, 0.00, 10000.00, '883434793807', 'pesalink', '2026-12-10', '2026-12-10', '2026-10-12 08:13:52', '2026-10-12 08:13:52', NULL, NULL, 'partner', 1, NULL),
(630, 535, 610, 30000.00, 0.00, 30000.00, '5610ZJJE7860', 'mpesa', '2026-10-12', '2026-10-12', '2026-10-12 08:39:58', '2026-10-12 08:39:58', NULL, NULL, 'partner', 1, NULL),
(631, 510, 597, 10000.00, 0.00, 10000.00, '574677151597', 'bank_transfer', '2026-10-13', '2026-10-13', '2026-10-13 06:33:12', '2026-10-13 06:33:12', NULL, NULL, 'partner', 1, NULL),
(632, 536, 614, 20000.00, 0.00, 20000.00, '5618ATKG9067', 'mpesa', '2026-10-13', '2026-10-13', '2026-10-13 06:34:51', '2026-10-13 06:34:51', NULL, NULL, 'partner', 1, NULL),
(633, 510, 597, 20000.00, 0.00, 20000.00, '282570018760', 'mpesa', '2026-10-13', '2026-10-13', '2026-10-13 12:49:08', '2026-10-13 12:49:08', NULL, NULL, 'partner', 1, NULL),
(634, 537, 615, 50000.00, 0.00, 50000.00, '5627WBBD5146', 'mpesa', '2026-10-14', '2026-10-25', '2026-10-15 06:59:11', '2026-10-15 06:59:26', NULL, NULL, 'partner', 1, NULL),
(635, 537, 615, 10000.00, 0.00, 10000.00, '5630BPME2964', 'mpesa', '2026-10-14', '2026-10-25', '2026-10-15 06:59:45', '2026-10-15 07:00:01', NULL, NULL, 'partner', 1, NULL),
(636, 510, 597, 15000.00, 0.00, 15000.00, '663538135949', 'mpesa', '2026-10-14', '2026-10-15', '2026-10-15 07:02:51', '2026-10-15 07:02:51', NULL, NULL, 'partner', 1, NULL),
(637, 538, 616, 1000.00, 0.00, 1000.00, '5634TPWM8100', 'mpesa', '2026-10-14', '2026-10-15', '2026-10-15 07:04:38', '2026-10-15 07:04:38', NULL, NULL, 'partner', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_statuses`
--

CREATE TABLE `document_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_statuses`
--

INSERT INTO `document_statuses` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Pending', 'pending', NULL, 1, NULL, NULL),
(2, 'Verified', 'verified', NULL, 1, NULL, NULL),
(3, 'Rejected', 'rejected', NULL, 1, NULL, NULL),
(4, 'Expired', 'expired', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `requires_verification` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `slug`, `requires_verification`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Loan Agreement', 'loan_agreement', 1, 1, NULL, NULL),
(2, 'ID Copy', 'id_copy', 1, 1, NULL, NULL),
(3, 'Income Proof', 'income_proof', 1, 1, NULL, NULL),
(4, 'Address Proof', 'address_proof', 1, 1, NULL, NULL),
(5, 'Letter', 'letter', 0, 1, NULL, NULL),
(6, 'Court Document', 'court_document', 1, 1, NULL, NULL),
(7, 'Payment Receipt', 'payment_receipt', 1, 1, NULL, NULL),
(8, 'Other', 'other', 0, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employments`
--

CREATE TABLE `employments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `employer_name` varchar(255) DEFAULT NULL,
  `employer_address` text DEFAULT NULL,
  `employer_phone` varchar(50) DEFAULT NULL,
  `employer_email` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_phone` varchar(50) DEFAULT NULL,
  `supervisor_email` varchar(255) DEFAULT NULL,
  `hr_contact_name` varchar(255) DEFAULT NULL,
  `hr_contact_phone` varchar(50) DEFAULT NULL,
  `hr_contact_email` varchar(255) DEFAULT NULL,
  `employment_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_types`
--

CREATE TABLE `employment_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employment_types`
--

INSERT INTO `employment_types` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Full Time', 'full_time', NULL, 1, 10, NULL, NULL),
(2, 'Part Time', 'part_time', NULL, 1, 20, NULL, NULL),
(3, 'Contract', 'contract', NULL, 1, 30, NULL, NULL),
(4, 'Casual', 'casual', NULL, 1, 40, NULL, NULL),
(5, 'Self Employed', 'self_employed', NULL, 1, 50, NULL, NULL),
(6, 'Business Owner', 'business_owner', NULL, 1, 60, NULL, NULL),
(7, 'Other', 'other', NULL, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `topic` varchar(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `organisation` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `investor_type` varchar(100) DEFAULT NULL,
  `allocation` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `route_to` varchar(255) NOT NULL COMMENT 'Desk inbox that handles this topic',
  `status` enum('new','in_progress','closed') NOT NULL DEFAULT 'new',
  `consented_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_assessments`
--

CREATE TABLE `financial_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `monthly_income` decimal(15,2) DEFAULT NULL,
  `monthly_expenses` decimal(15,2) DEFAULT NULL,
  `monthly_surplus` decimal(15,2) DEFAULT NULL,
  `other_obligations` decimal(15,2) DEFAULT NULL,
  `dependents_count` int(11) DEFAULT 0,
  `hardship_reason_id` bigint(20) UNSIGNED DEFAULT NULL,
  `hardship_description` text DEFAULT NULL,
  `supporting_documents` text DEFAULT NULL,
  `recommended_plan` text DEFAULT NULL,
  `assessed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `approval_date` date DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hardship_reasons`
--

CREATE TABLE `hardship_reasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hardship_reasons`
--

INSERT INTO `hardship_reasons` (`id`, `name`, `slug`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Job Loss', 'job_loss', NULL, 1, NULL, NULL),
(2, 'Medical Emergency', 'medical_emergency', NULL, 1, NULL, NULL),
(3, 'Business Failure', 'business_failure', NULL, 1, NULL, NULL),
(4, 'Natural Disaster', 'natural_disaster', NULL, 1, NULL, NULL),
(5, 'Death in Family', 'death_in_family', NULL, 1, NULL, NULL),
(6, 'Divorce', 'divorce', NULL, 1, NULL, NULL),
(7, 'Other', 'other', NULL, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('commodity','equity','bond','real_estate','startup','infrastructure','technology','agriculture','energy','other') NOT NULL,
  `facility_type` enum('short_term','long_term') NOT NULL DEFAULT 'long_term',
  `duration_days` int(11) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `sub_sector` varchar(100) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `incorporation_date` date DEFAULT NULL,
  `legal_structure` enum('sole_proprietorship','partnership','llc','corporation','non_profit') DEFAULT NULL,
  `ebitda_pre_investment` decimal(15,2) DEFAULT NULL,
  `revenue_pre_investment` decimal(15,2) DEFAULT NULL,
  `net_profit_pre_investment` decimal(15,2) DEFAULT NULL,
  `total_assets_pre_investment` decimal(15,2) DEFAULT NULL,
  `total_liabilities_pre_investment` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expected_return` decimal(5,2) DEFAULT NULL,
  `actual_return` decimal(5,2) DEFAULT NULL,
  `revenue_current` decimal(15,2) DEFAULT NULL,
  `profit_current` decimal(15,2) DEFAULT NULL,
  `valuation_current` decimal(15,2) DEFAULT NULL,
  `initial_amount` decimal(15,2) NOT NULL,
  `irr` decimal(5,2) DEFAULT NULL,
  `payback_period_months` int(11) DEFAULT NULL,
  `break_even_point` decimal(15,2) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `maturity_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `risk_rating` enum('A','AA','AAA','BBB','BB','B','C') DEFAULT NULL,
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `stakeholders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `pitch_deck_path` varchar(255) DEFAULT NULL,
  `financial_model_path` varchar(255) DEFAULT NULL,
  `due_diligence_path` varchar(255) DEFAULT NULL,
  `legal_docs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `market_research` text DEFAULT NULL,
  `competitive_landscape` text DEFAULT NULL,
  `swot_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `key_assumptions` text DEFAULT NULL,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('pipeline','due_diligence','active','matured','liquidated','write_off') DEFAULT 'pipeline',
  `stage` enum('ideation','seed','startup','growth','expansion','mature') DEFAULT NULL,
  `milestones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `total_funding_raised` decimal(15,2) DEFAULT 0.00,
  `funding_partners` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `updates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `investments`
--

INSERT INTO `investments` (`id`, `name`, `type`, `facility_type`, `duration_days`, `interest_rate`, `sector`, `sub_sector`, `country`, `region`, `city`, `address`, `company_name`, `registration_number`, `incorporation_date`, `legal_structure`, `ebitda_pre_investment`, `revenue_pre_investment`, `net_profit_pre_investment`, `total_assets_pre_investment`, `total_liabilities_pre_investment`, `current_value`, `expected_return`, `actual_return`, `revenue_current`, `profit_current`, `valuation_current`, `initial_amount`, `irr`, `payback_period_months`, `break_even_point`, `purchase_date`, `maturity_date`, `exit_date`, `risk_rating`, `risk_factors`, `stakeholders`, `pitch_deck_path`, `financial_model_path`, `due_diligence_path`, `legal_docs`, `market_research`, `competitive_landscape`, `swot_analysis`, `key_assumptions`, `notes`, `status`, `stage`, `milestones`, `total_funding_raised`, `funding_partners`, `updates`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'General Lending Book', 'other', 'long_term', NULL, NULL, NULL, NULL, 'Kenya', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, NULL, '2026-10-08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', NULL, NULL, 0.00, '[]', NULL, '2026-10-08 09:32:55', '2026-10-08 09:32:55', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `investment_fundings`
--

CREATE TABLE `investment_fundings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `investment_id` bigint(20) UNSIGNED NOT NULL,
  `partner_id` bigint(20) UNSIGNED NOT NULL,
  `partner_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount_committed` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_disbursed` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_returned` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','settled','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `investment_fundings`
--

INSERT INTO `investment_fundings` (`id`, `investment_id`, `partner_id`, `partner_transaction_id`, `amount_committed`, `amount_disbursed`, `amount_returned`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 6, NULL, 0.00, 20000.00, 0.00, 'settled', 'Auto-linked to House Principal (fallback).', '2026-10-08 09:34:07', '2026-10-08 09:34:07');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `legal_deadlines`
--

CREATE TABLE `legal_deadlines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `deadline_type` enum('statute_of_limitations','court_filing','hearing','response_due','payment_due','other') NOT NULL,
  `deadline_date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `extension_date` date DEFAULT NULL,
  `extension_reason` text DEFAULT NULL,
  `status` enum('pending','met','extended','missed','waived') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `legal_proceedings`
--

CREATE TABLE `legal_proceedings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `proceeding_type_id` bigint(20) UNSIGNED NOT NULL,
  `filing_date` date NOT NULL,
  `court_name` varchar(255) DEFAULT NULL,
  `case_number` varchar(100) DEFAULT NULL,
  `judge_name` varchar(255) DEFAULT NULL,
  `plaintiff` varchar(255) DEFAULT NULL,
  `defendant` varchar(255) DEFAULT NULL,
  `amount_claimed` decimal(15,2) DEFAULT NULL,
  `amount_awarded` decimal(15,2) DEFAULT NULL,
  `judgment_date` date DEFAULT NULL,
  `status` enum('filed','pending','active','resolved','appealed','dismissed') DEFAULT 'filed',
  `next_hearing_date` date DEFAULT NULL,
  `lawyer_name` varchar(255) DEFAULT NULL,
  `lawyer_contact` varchar(255) DEFAULT NULL,
  `lawyer_fees` decimal(15,2) DEFAULT NULL,
  `costs` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `legal_proceeding_types`
--

CREATE TABLE `legal_proceeding_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `legal_proceeding_types`
--

INSERT INTO `legal_proceeding_types` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Demand Letter', 'demand_letter', NULL, 1, 10, NULL, NULL),
(2, 'Court Filing', 'court_filing', NULL, 1, 20, NULL, NULL),
(3, 'Judgment', 'judgment', NULL, 1, 30, NULL, NULL),
(4, 'Writ of Attachment', 'writ_of_attachment', NULL, 1, 40, NULL, NULL),
(5, 'Garnishment', 'garnishment', NULL, 1, 50, NULL, NULL),
(6, 'Bankruptcy Notice', 'bankruptcy_notice', NULL, 1, 60, NULL, NULL),
(7, 'Settlement', 'settlement', NULL, 1, 70, NULL, NULL),
(8, 'Appeal', 'appeal', NULL, 1, 80, NULL, NULL),
(9, 'Other', 'other', NULL, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `loan_type_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `processing_fee_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_processing_fees` decimal(15,2) NOT NULL DEFAULT 0.00,
  `borrow_date` date NOT NULL,
  `broker_status` tinyint(4) NOT NULL DEFAULT 0,
  `partner_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Broker/partner of record when broker_status=1; null for pure house loans',
  `broker_rate` decimal(5,2) NOT NULL DEFAULT 40.00 COMMENT 'Broker share of interest for this loan, percent',
  `status` enum('pending','approved','disbursed','rejected','repaid','overdue','defaulted','recovery','forbearance') NOT NULL DEFAULT 'pending',
  `cycle` int(11) NOT NULL DEFAULT 1,
  `original_amount` decimal(15,2) DEFAULT NULL,
  `capitalized_interest` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grace_period_days` int(11) NOT NULL DEFAULT 0,
  `grace_period_end_date` date DEFAULT NULL,
  `grace_days_balance` int(11) NOT NULL DEFAULT 0,
  `grace_days_earned` int(11) NOT NULL DEFAULT 0,
  `grace_days_used` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `guarantor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guarantor_relationship` varchar(255) DEFAULT NULL,
  `loan_officer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `consent` tinyint(1) DEFAULT 0,
  `consent_date` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `is_non_performing` tinyint(1) DEFAULT 0 COMMENT 'Flag for NPL status',
  `default_date` date DEFAULT NULL COMMENT 'Date when loan went into default',
  `days_in_default` int(11) NOT NULL DEFAULT 0,
  `default_triggered_at` timestamp NULL DEFAULT NULL,
  `recovery_started_at` timestamp NULL DEFAULT NULL,
  `forbearance_until` timestamp NULL DEFAULT NULL,
  `recovery_notes` text DEFAULT NULL,
  `days_overdue` int(11) DEFAULT 0 COMMENT 'Number of days overdue',
  `last_overdue_check` datetime DEFAULT NULL COMMENT 'Last time overdue status was checked',
  `default_triggered` tinyint(1) DEFAULT 0 COMMENT 'Whether NPL trigger has fired',
  `calculated_due_date` date DEFAULT NULL COMMENT 'Calculated due date (derived from loan_types)',
  `npl_trigger_threshold` int(11) DEFAULT 0 COMMENT 'Days overdue when NPL triggered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `processing_fee_rate`, `total_processing_fees`, `borrow_date`, `broker_status`, `partner_id`, `broker_rate`, `status`, `cycle`, `original_amount`, `capitalized_interest`, `grace_period_days`, `grace_period_end_date`, `grace_days_balance`, `grace_days_earned`, `grace_days_used`, `created_at`, `updated_at`, `deleted_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`, `is_non_performing`, `default_date`, `days_in_default`, `default_triggered_at`, `recovery_started_at`, `forbearance_until`, `recovery_notes`, `days_overdue`, `last_overdue_check`, `default_triggered`, `calculated_due_date`, `npl_trigger_threshold`) VALUES
(1, 2, 1, 120000.00, 0.00, 0.00, '2025-03-07', 1, 5, 40.00, 'repaid', 1, NULL, 24000.00, 0, NULL, 0, 0, 0, '2025-04-17 12:12:56', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-03-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-03-17', 30),
(2, 6, 1, 30000.00, 0.00, 0.00, '2025-03-07', 1, 5, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-04-17 12:22:35', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-03-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-03-17', 30),
(3, 6, 1, 50000.00, 0.00, 0.00, '2025-03-28', 1, 5, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-17 12:26:11', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-07', 30),
(4, 2, 1, 35000.00, 0.00, 0.00, '2025-03-31', 1, 5, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2025-04-17 12:42:17', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-10', 30),
(5, 7, 2, 10000.00, 0.00, 0.00, '2025-03-03', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-04-17 13:03:34', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-03', 90),
(6, 7, 2, 13000.00, 0.00, 0.00, '2025-04-05', 0, NULL, 40.00, 'repaid', 1, NULL, 2600.00, 0, NULL, 0, 0, 0, '2025-04-17 13:11:52', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-05', 90),
(7, 8, 1, 2000.00, 0.00, 0.00, '2025-04-10', 0, NULL, 40.00, 'repaid', 1, NULL, 400.00, 0, NULL, 0, 0, 0, '2025-04-17 13:18:22', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-20', 30),
(8, 9, 1, 10000.00, 0.00, 0.00, '2025-04-12', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-04-17 13:23:26', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-22', 30),
(9, 9, 1, 15000.00, 0.00, 0.00, '2025-04-17', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-04-17 13:25:52', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-27', 30),
(10, 10, 3, 30000.00, 0.00, 0.00, '2025-04-16', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-04-17 13:27:33', '2026-08-06 13:11:44', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-30', 42),
(11, 11, 1, 15000.00, 0.00, 0.00, '2025-04-08', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-04-17 13:29:39', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-18', 30),
(12, 14, 1, 27000.00, 0.00, 0.00, '2025-04-17', 1, 5, 40.00, 'repaid', 1, NULL, 5400.00, 0, NULL, 0, 0, 0, '2025-04-17 14:08:51', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-27', 30),
(13, 16, 1, 30000.00, 0.00, 0.00, '2025-04-13', 1, 5, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-04-17 14:33:51', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-23', 30),
(14, 2, 1, 20000.00, 0.00, 0.00, '2025-03-25', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-04-20 12:26:24', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-04', 30),
(15, 18, 1, 50000.00, 0.00, 0.00, '2025-03-25', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-20 12:42:26', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-04', 30),
(16, 18, 1, 50000.00, 0.00, 0.00, '2025-04-01', 1, 5, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-20 12:50:23', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-11', 30),
(17, 18, 1, 20000.00, 0.00, 0.00, '2025-04-12', 1, 5, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-04-20 13:06:04', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-22', 30),
(18, 2, 1, 35000.00, 0.00, 0.00, '2025-03-31', 1, 5, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2025-04-20 13:24:14', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-10', 30),
(19, 2, 1, 20000.00, 0.00, 0.00, '2025-04-07', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-04-20 13:45:24', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-17', 30),
(20, 2, 1, 33000.00, 0.00, 0.00, '2025-04-15', 0, NULL, 40.00, 'repaid', 1, NULL, 6600.00, 0, NULL, 0, 0, 0, '2025-04-20 13:49:21', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-25', 30),
(21, 2, 1, 42000.00, 0.00, 0.00, '2025-04-15', 1, 5, 40.00, 'repaid', 1, NULL, 8400.00, 0, NULL, 0, 0, 0, '2025-04-20 13:56:32', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-25', 30),
(22, 3, 1, 10000.00, 0.00, 0.00, '2025-03-03', 1, 5, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-04-20 16:19:58', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-03-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-03-13', 30),
(23, 3, 1, 5000.00, 0.00, 0.00, '2025-02-26', 1, 5, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-04-20 16:29:18', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-03-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-03-08', 30),
(24, 15, 2, 5000.00, 0.00, 0.00, '2025-03-08', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-04-20 16:32:20', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-08', 90),
(25, 13, 2, 11000.00, 0.00, 0.00, '2025-03-10', 0, NULL, 40.00, 'repaid', 1, NULL, 2200.00, 0, NULL, 0, 0, 0, '2025-04-20 16:33:54', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-10', 90),
(26, 13, 2, 600.00, 0.00, 0.00, '2025-04-21', 0, NULL, 40.00, 'repaid', 1, NULL, 120.00, 0, NULL, 0, 0, 0, '2025-04-20 16:38:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-21', 90),
(27, 15, 2, 2000.00, 0.00, 0.00, '2025-03-15', 0, NULL, 40.00, 'repaid', 1, NULL, 400.00, 0, NULL, 0, 0, 0, '2025-04-20 16:39:13', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-15', 90),
(28, 3, 1, 50000.00, 0.00, 0.00, '2025-04-04', 1, 5, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-20 17:10:46', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-14', 30),
(29, 17, 2, 11000.00, 0.00, 0.00, '2025-04-04', 0, NULL, 40.00, 'repaid', 1, NULL, 2200.00, 0, NULL, 0, 0, 0, '2025-04-20 17:11:49', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-04', 90),
(30, 15, 2, 6000.00, 0.00, 0.00, '2025-04-04', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2025-04-20 17:12:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-04', 90),
(31, 19, 1, 50000.00, 0.00, 0.00, '2025-04-07', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-20 17:15:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-17', 30),
(32, 13, 2, 29000.00, 0.00, 0.00, '2025-04-04', 0, NULL, 40.00, 'repaid', 1, NULL, 5800.00, 0, NULL, 0, 0, 0, '2025-04-20 17:18:16', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-04', 90),
(33, 17, 2, 10000.00, 0.00, 0.00, '2025-03-07', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-04-20 17:19:04', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-07', 90),
(34, 20, 2, 9000.00, 0.00, 0.00, '2025-03-07', 0, NULL, 40.00, 'repaid', 1, NULL, 1800.00, 0, NULL, 0, 0, 0, '2025-04-20 17:39:54', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-07', 90),
(35, 11, 1, 18000.00, 0.00, 0.00, '2025-04-18', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2025-04-21 06:17:23', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-04-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-04-28', 30),
(36, 2, 1, 44000.00, 0.00, 0.00, '2025-03-21', 1, 5, 40.00, 'repaid', 1, NULL, 8800.00, 0, NULL, 0, 0, 0, '2025-04-21 09:01:00', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-03-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-03-31', 30),
(37, 9, 1, 5000.00, 0.00, 0.00, '2025-04-25', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-04-21 10:05:13', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-05', 30),
(38, 21, 1, 50000.00, 0.00, 0.00, '2025-04-24', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-24 06:57:09', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-04', 30),
(39, 9, 1, 15000.00, 0.00, 0.00, '2025-04-27', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-04-27 06:33:31', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-07', 30),
(40, 15, 2, 15600.00, 0.00, 0.00, '2025-05-06', 0, NULL, 40.00, 'repaid', 1, NULL, 3120.00, 0, NULL, 0, 0, 0, '2025-04-27 07:53:49', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-06', 90),
(41, 13, 2, 8000.00, 0.00, 0.00, '2025-04-27', 0, NULL, 40.00, 'repaid', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2025-04-27 07:55:09', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-27', 90),
(45, 2, 1, 50000.00, 0.00, 0.00, '2025-04-29', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-04-29 06:05:48', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-09', 30),
(46, 21, 1, 50000.00, 0.00, 0.00, '2025-05-03', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-02 19:31:23', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-13', 30),
(48, 9, 1, 5000.00, 0.00, 0.00, '2025-05-05', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-05-05 18:02:20', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-15', 30),
(49, 13, 2, 50000.00, 0.00, 0.00, '2025-05-06', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-05 19:30:08', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-06', 90),
(50, 16, 1, 100000.00, 0.00, 0.00, '2025-05-06', 1, 5, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-05-06 08:14:19', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-16', 30),
(51, 7, 1, 15600.00, 0.00, 0.00, '2025-05-04', 0, NULL, 40.00, 'repaid', 1, NULL, 3120.00, 0, NULL, 0, 0, 0, '2025-05-06 08:48:50', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-14', 30),
(52, 8, 2, 8000.00, 0.00, 0.00, '2025-05-06', 0, NULL, 40.00, 'pending', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2025-05-07 10:46:08', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-06', 90),
(54, 22, 1, 10000.00, 0.00, 0.00, '2025-05-07', 1, 5, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-05-07 12:10:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-17', 30),
(55, 9, 1, 15000.00, 0.00, 0.00, '2025-05-07', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-05-07 14:50:08', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-17', 30),
(56, 2, 1, 30000.00, 0.00, 0.00, '2025-05-07', 1, 5, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-05-07 14:53:46', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-17', 30),
(57, 14, 1, 45000.00, 0.00, 0.00, '2025-05-10', 1, 5, 40.00, 'repaid', 1, NULL, 9000.00, 0, NULL, 0, 0, 0, '2025-05-10 10:37:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-20', 30),
(59, 2, 1, 78000.00, 0.00, 0.00, '2025-05-12', 0, NULL, 40.00, 'repaid', 1, NULL, 15600.00, 0, NULL, 0, 0, 0, '2025-05-12 09:05:41', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-22', 30),
(60, 24, 1, 50000.00, 0.00, 0.00, '2025-05-14', 1, 5, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-13 06:50:44', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-24', 30),
(61, 21, 1, 72000.00, 0.00, 0.00, '2025-05-15', 0, NULL, 40.00, 'repaid', 1, NULL, 14400.00, 0, NULL, 0, 0, 0, '2025-05-15 07:46:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-25', 30),
(64, 7, 2, 18720.00, 0.00, 0.00, '2025-05-15', 0, NULL, 40.00, 'repaid', 1, NULL, 3744.00, 0, NULL, 0, 0, 0, '2025-05-15 07:49:33', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-15', 90),
(65, 11, 3, 13000.00, 0.00, 0.00, '2025-05-14', 0, NULL, 40.00, 'repaid', 1, NULL, 2600.00, 0, NULL, 0, 0, 0, '2025-05-15 07:57:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-28', 42),
(66, 25, 1, 2500.00, 0.00, 0.00, '2025-05-14', 1, 5, 40.00, 'repaid', 1, NULL, 500.00, 0, NULL, 0, 0, 0, '2025-05-15 08:09:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-24', 30),
(68, 22, 1, 10000.00, 0.00, 0.00, '2025-05-17', 1, 5, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-05-18 08:11:18', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-05-27', 30),
(69, 26, 3, 50000.00, 0.00, 0.00, '2025-05-19', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-19 12:46:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-02', 42),
(70, 27, 3, 50000.00, 0.00, 0.00, '2025-05-20', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-20 13:03:31', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-03', 42),
(71, 28, 1, 15000.00, 0.00, 0.00, '2025-05-26', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-05-25 18:37:01', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-05', 30),
(72, 21, 1, 27840.00, 0.00, 0.00, '2025-05-26', 0, NULL, 40.00, 'pending', 1, NULL, 5568.00, 0, NULL, 0, 0, 0, '2025-05-26 10:33:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-05', 30),
(73, 9, 1, 10000.00, 0.00, 0.00, '2025-05-24', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-05-26 11:51:57', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-03', 30),
(74, 24, 1, 50000.00, 0.00, 0.00, '2025-05-26', 1, 5, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-05-26 16:55:48', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-05', 30),
(75, 17, 7, 30000.00, 0.00, 0.00, '2025-05-27', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-05-27 06:20:52', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-27', 90),
(76, 29, 3, 15000.00, 0.00, 0.00, '2025-05-27', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-05-27 13:48:17', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-10', 42),
(77, 28, 1, 30000.00, 0.00, 0.00, '2025-05-29', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-05-29 06:41:34', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-08', 30),
(78, 2, 4, 100000.00, 0.00, 0.00, '2025-05-29', 0, NULL, 40.00, 'repaid', 1, NULL, 95000.00, 0, NULL, 0, 0, 0, '2025-05-29 12:06:58', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-29', 150),
(79, 31, 1, 35000.00, 0.00, 0.00, '2025-05-29', 0, NULL, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2025-05-29 13:49:38', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-08', 30),
(80, 11, 5, 30000.00, 0.00, 0.00, '2025-05-30', 0, NULL, 40.00, 'repaid', 1, NULL, 9000.00, 0, NULL, 0, 0, 0, '2025-05-30 15:28:35', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-30', 90),
(82, 28, 5, 30000.00, 0.00, 0.00, '2025-05-31', 0, NULL, 40.00, 'repaid', 1, NULL, 9000.00, 0, NULL, 0, 0, 0, '2025-05-31 09:41:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-01', 90),
(83, 22, 1, 8200.00, 0.00, 0.00, '2025-05-29', 1, 5, 40.00, 'repaid', 1, NULL, 1640.00, 0, NULL, 0, 0, 0, '2025-05-31 11:16:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-08', 30),
(84, 32, 6, 20000.00, 0.00, 0.00, '2025-06-02', 0, NULL, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2025-06-02 11:19:18', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-16', 42),
(85, 16, 1, 100000.00, 0.00, 0.00, '2025-06-03', 1, 5, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-06-03 07:25:00', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-13', 30),
(86, 9, 1, 10000.00, 0.00, 0.00, '2025-06-03', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-06-03 08:12:16', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-13', 30),
(87, 27, 3, 50000.00, 0.00, 0.00, '2025-06-04', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-06-03 15:43:14', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-18', 42),
(88, 28, 1, 28000.00, 0.00, 0.00, '2025-06-06', 0, NULL, 40.00, 'repaid', 1, NULL, 5600.00, 0, NULL, 0, 0, 0, '2025-06-06 08:00:44', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-16', 30),
(89, 22, 1, 8280.00, 0.00, 0.00, '2025-06-08', 1, 5, 40.00, 'repaid', 1, NULL, 1656.00, 0, NULL, 0, 0, 0, '2025-06-09 03:41:42', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-18', 30),
(90, 28, 1, 39600.00, 0.00, 0.00, '2025-06-09', 0, NULL, 40.00, 'repaid', 1, NULL, 7920.00, 0, NULL, 0, 0, 0, '2025-06-10 05:08:08', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-19', 30),
(91, 33, 1, 15000.00, 0.00, 0.00, '2025-06-14', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-06-15 05:15:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-24', 30),
(92, 9, 1, 10000.00, 0.00, 0.00, '2025-06-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-06-15 05:15:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-23', 30),
(93, 29, 1, 17000.00, 0.00, 0.00, '2025-06-13', 0, NULL, 0.00, 'repaid', 1, NULL, 3400.00, 0, NULL, 0, 0, 0, '2025-06-15 05:19:36', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-23', 30),
(94, 34, 3, 5000.00, 0.00, 0.00, '2025-06-12', 0, NULL, 0.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-06-15 13:56:47', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-26', 42),
(95, 7, 2, 2264.00, 0.00, 0.00, '2025-06-16', 0, NULL, 40.00, 'repaid', 1, NULL, 452.80, 0, NULL, 0, 0, 0, '2025-06-16 13:29:00', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-16', 90),
(96, 28, 1, 81120.00, 0.00, 0.00, '2025-06-19', 0, NULL, 40.00, 'repaid', 1, NULL, 16224.00, 0, NULL, 0, 0, 0, '2025-06-18 09:58:47', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-29', 30),
(97, 27, 2, 50000.00, 0.00, 0.00, '2025-06-18', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-06-20 07:07:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-18', 90),
(98, 35, 3, 28000.00, 0.00, 0.00, '2025-06-20', 0, NULL, 40.00, 'repaid', 1, NULL, 5600.00, 0, NULL, 0, 0, 0, '2025-06-20 18:32:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-04', 42),
(99, 36, 1, 6000.00, 0.00, 0.00, '2025-06-21', 0, NULL, 40.00, 'pending', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-06-21 06:22:09', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-01', 30),
(100, 37, 1, 5000.00, 0.00, 0.00, '2025-06-22', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-06-23 10:27:04', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-02', 30),
(101, 33, 1, 10000.00, 0.00, 0.00, '2025-06-24', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-06-24 06:21:52', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-04', 30),
(102, 9, 1, 10000.00, 0.00, 0.00, '2025-06-24', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-06-25 10:44:35', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-04', 30),
(103, 34, 1, 5000.00, 0.00, 0.00, '2025-06-27', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-06-27 07:45:19', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-07', 30),
(104, 9, 1, 5000.00, 0.00, 0.00, '2025-06-29', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-06-30 07:08:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-09', 30),
(105, 35, 1, 120000.00, 0.00, 0.00, '2025-06-30', 0, NULL, 40.00, 'repaid', 1, NULL, 24000.00, 0, NULL, 0, 0, 0, '2025-06-30 08:23:02', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-10', 30),
(106, 22, 1, 3936.00, 0.00, 0.00, '2025-06-18', 1, 5, 40.00, 'repaid', 1, NULL, 946.40, 0, NULL, 0, 0, 0, '2025-06-30 08:32:22', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-06-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-06-28', 30),
(107, 17, 7, 30000.00, 0.00, 0.00, '2025-06-30', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-07-01 07:29:54', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-30', 90),
(108, 33, 1, 10000.00, 0.00, 0.00, '2025-07-05', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-05 05:38:52', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-15', 30),
(109, 9, 1, 12000.00, 0.00, 0.00, '2025-07-05', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-07-05 05:40:47', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-15', 30),
(110, 29, 1, 10000.00, 0.00, 0.00, '2025-07-07', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-07 08:48:13', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-17', 30),
(111, 38, 1, 8000.00, 0.00, 0.00, '2025-07-07', 0, NULL, 40.00, 'repaid', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2025-07-07 08:51:43', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-17', 30),
(112, 34, 1, 10000.00, 0.00, 0.00, '2025-07-08', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-08 07:54:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-18', 30),
(113, 32, 3, 10000.00, 0.00, 0.00, '2025-07-08', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-08 16:28:51', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-22', 42),
(114, 9, 1, 6000.00, 0.00, 0.00, '2025-07-10', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2025-07-10 04:42:14', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-20', 30),
(115, 28, 5, 97344.00, 0.00, 0.00, '2025-06-30', 0, NULL, 40.00, 'repaid', 1, NULL, 29203.20, 0, NULL, 0, 0, 0, '2025-07-10 04:53:20', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-30', 90),
(116, 28, 5, 39000.00, 0.00, 0.00, '2025-07-02', 0, NULL, 40.00, 'repaid', 1, NULL, 11700.00, 0, NULL, 0, 0, 0, '2025-07-10 05:04:53', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-02', 90),
(117, 35, 1, 120000.00, 0.00, 0.00, '2025-07-11', 0, NULL, 40.00, 'repaid', 1, NULL, 24000.00, 0, NULL, 0, 0, 0, '2025-07-13 16:06:21', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-21', 30),
(118, 39, 1, 2000.00, 0.00, 0.00, '2025-07-12', 0, NULL, 40.00, 'repaid', 1, NULL, 400.00, 0, NULL, 0, 0, 0, '2025-07-13 16:12:10', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-22', 30),
(119, 40, 1, 1000.00, 0.00, 0.00, '2025-07-14', 0, NULL, 40.00, 'repaid', 1, NULL, 200.00, 0, NULL, 0, 0, 0, '2025-07-14 11:36:52', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-24', 30),
(120, 33, 3, 25000.00, 0.00, 0.00, '2025-07-15', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-07-15 11:04:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-29', 42),
(121, 41, 3, 70000.00, 0.00, 0.00, '2025-07-15', 0, NULL, 40.00, 'repaid', 1, NULL, 14000.00, 0, NULL, 0, 0, 0, '2025-07-15 11:06:01', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-29', 42),
(122, 9, 1, 14400.00, 0.00, 0.00, '2025-07-15', 0, NULL, 40.00, 'repaid', 1, NULL, 2880.00, 0, NULL, 0, 0, 0, '2025-07-17 08:16:16', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-25', 30),
(123, 11, 3, 42900.00, 0.00, 0.00, '2025-06-30', 0, NULL, 40.00, 'repaid', 1, NULL, 8580.00, 0, NULL, 0, 0, 0, '2025-07-17 08:21:14', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-14', 42),
(124, 11, 3, 56628.00, 0.00, 0.00, '2025-07-14', 0, NULL, 40.00, 'repaid', 1, NULL, 11325.60, 0, NULL, 0, 0, 0, '2025-07-17 08:26:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-28', 42),
(125, 29, 1, 10000.00, 0.00, 0.00, '2025-07-17', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-17 09:51:35', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-27', 30),
(126, 40, 1, 5000.00, 0.00, 0.00, '2025-07-16', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-07-17 09:56:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-26', 30),
(127, 42, 1, 20000.00, 0.00, 0.00, '2025-07-17', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-07-17 10:55:06', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-27', 30),
(128, 34, 1, 10000.00, 0.00, 0.00, '2025-07-18', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-07-18 08:33:17', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-28', 30),
(129, 9, 1, 7200.00, 0.00, 0.00, '2025-07-21', 0, NULL, 40.00, 'repaid', 1, NULL, 1440.00, 0, NULL, 0, 0, 0, '2025-07-21 06:44:38', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-31', 30),
(130, 9, 1, 17280.00, 0.00, 0.00, '2025-07-25', 0, NULL, 40.00, 'repaid', 1, NULL, 3456.00, 0, NULL, 0, 0, 0, '2025-07-27 09:00:07', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-04', 30),
(131, 38, 1, 4000.00, 0.00, 0.00, '2025-07-20', 0, NULL, 40.00, 'repaid', 1, NULL, 800.00, 0, NULL, 0, 0, 0, '2025-07-28 14:10:31', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-07-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-07-30', 30),
(132, 43, 1, 60000.00, 0.00, 0.00, '2025-07-29', 0, NULL, 40.00, 'pending', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-07-29 16:16:57', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-08', 30),
(134, 17, 7, 30000.00, 0.00, 0.00, '2025-07-30', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-07-30 07:39:09', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-30', 90),
(135, 42, 1, 2400.00, 0.00, 0.00, '2025-07-29', 0, NULL, 40.00, 'repaid', 1, NULL, 480.00, 0, NULL, 0, 0, 0, '2025-07-30 14:21:37', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-08', 30),
(136, 34, 1, 12000.00, 0.00, 0.00, '2025-07-28', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-07-30 15:34:39', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-07', 30),
(137, 31, 1, 37500.00, 0.00, 0.00, '2025-07-30', 0, NULL, 40.00, 'repaid', 1, NULL, 7500.00, 0, NULL, 0, 0, 0, '2025-07-31 08:43:45', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-09', 30),
(138, 9, 2, 45000.00, 0.00, 0.00, '2025-08-01', 0, NULL, 40.00, 'repaid', 1, NULL, 9000.00, 0, NULL, 0, 0, 0, '2025-08-01 06:49:04', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-01', 90),
(139, 15, 7, 30000.00, 0.00, 0.00, '2025-08-01', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-08-01 10:17:34', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-01', 90),
(140, 39, 1, 3000.00, 0.00, 0.00, '2025-08-02', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2025-08-02 16:04:03', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-12', 30),
(141, 9, 1, 6000.00, 0.00, 0.00, '2025-08-04', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2025-08-05 08:11:38', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-14', 30),
(142, 40, 1, 3000.00, 0.00, 0.00, '2025-07-27', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2025-08-06 05:42:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-06', 30),
(143, 33, 3, 30000.00, 0.00, 0.00, '2025-07-30', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-08-06 11:01:44', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-13', 42),
(144, 45, 8, 300000.00, 0.00, 0.00, '2025-08-06', 0, NULL, 40.00, 'repaid', 1, NULL, 60000.00, 0, NULL, 0, 0, 0, '2025-08-06 11:26:02', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-27', 63),
(145, 7, 2, 3000.00, 0.00, 0.00, '2025-08-06', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2025-08-07 03:41:48', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-06', 90),
(146, 34, 1, 16400.00, 0.00, 0.00, '2025-08-08', 0, NULL, 40.00, 'repaid', 1, NULL, 3280.00, 0, NULL, 0, 0, 0, '2025-08-07 17:08:54', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-18', 30),
(147, 11, 3, 67954.00, 0.00, 0.00, '2025-07-29', 0, NULL, 40.00, 'repaid', 1, NULL, 13590.72, 0, NULL, 0, 0, 0, '2025-08-07 18:47:45', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-12', 42),
(148, 29, 1, 15000.00, 0.00, 0.00, '2025-08-09', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-08-08 10:40:52', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-19', 30),
(149, 38, 1, 10000.00, 0.00, 0.00, '2025-08-09', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-08-09 10:25:48', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-19', 30),
(150, 28, 2, 126548.00, 0.00, 0.00, '2025-07-31', 0, NULL, 40.00, 'pending', 1, NULL, 25309.44, 0, NULL, 0, 0, 0, '2025-08-11 08:06:15', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-31', 90),
(151, 11, 3, 66545.00, 0.00, 0.00, '2025-08-12', 0, NULL, 40.00, 'repaid', 1, NULL, 13309.00, 0, NULL, 0, 0, 0, '2025-08-12 06:52:11', '2026-08-06 13:11:45', NULL, 13, 'Colleague', 49, 0, NULL, NULL, '2025-08-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-26', 42),
(152, 28, 5, 50700.00, 0.00, 0.00, '2025-08-03', 0, NULL, 40.00, 'repaid', 1, NULL, 15210.00, 0, NULL, 0, 0, 0, '2025-08-12 06:55:08', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-03', 90),
(156, 39, 1, 10000.00, 0.00, 0.00, '2025-08-22', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-08-13 04:11:07', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-01', 30),
(158, 40, 1, 1600.00, 0.00, 0.00, '2025-08-06', 0, NULL, 40.00, 'repaid', 1, NULL, 320.00, 0, NULL, 0, 0, 0, '2025-08-14 10:09:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-16', 30),
(159, 9, 1, 6000.00, 0.00, 0.00, '2025-08-15', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2025-08-15 05:34:14', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-25', 30),
(160, 48, 1, 6000.00, 0.00, 0.00, '2025-08-19', 0, NULL, 40.00, 'pending', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-08-19 15:00:21', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-29', 30),
(161, 45, 8, 50000.00, 0.00, 0.00, '2025-08-15', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-08-20 05:59:03', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-05', 63),
(162, 40, 1, 1920.00, 0.00, 0.00, '2025-08-17', 0, NULL, 40.00, 'repaid', 1, NULL, 384.00, 0, NULL, 0, 0, 0, '2025-08-20 06:38:06', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-27', 30),
(163, 34, 1, 19680.00, 0.00, 0.00, '2025-08-19', 0, NULL, 40.00, 'repaid', 1, NULL, 3936.00, 0, NULL, 0, 0, 0, '2025-08-20 07:28:25', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-29', 30),
(164, 29, 1, 18000.00, 0.00, 0.00, '2025-08-20', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2025-08-21 09:21:11', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-30', 30),
(165, 2, 1, 15000.00, 0.00, 0.00, '2025-08-22', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-08-22 12:56:44', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-01', 30),
(166, 9, 1, 7200.00, 0.00, 0.00, '2025-08-25', 0, NULL, 40.00, 'repaid', 1, NULL, 1440.00, 0, NULL, 0, 0, 0, '2025-08-28 15:10:28', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-04', 30),
(167, 35, 1, 50000.00, 0.00, 0.00, '2025-08-28', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-08-28 15:55:30', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-07', 30),
(168, 17, 7, 30000.00, 0.00, 0.00, '2025-08-30', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-09-01 18:27:10', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-30', 90),
(169, 33, 3, 42000.00, 0.00, 0.00, '2025-08-14', 0, NULL, 40.00, 'repaid', 1, NULL, 8400.00, 0, NULL, 0, 0, 0, '2025-09-01 18:30:13', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-08-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-08-28', 42),
(170, 15, 7, 30000.00, 0.00, 0.00, '2025-09-01', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-09-02 04:31:23', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-01', 90),
(171, 9, 2, 53640.00, 0.00, 0.00, '2025-09-02', 0, NULL, 40.00, 'repaid', 1, NULL, 10728.00, 0, NULL, 0, 0, 0, '2025-09-03 11:14:06', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-02', 90),
(172, 29, 1, 21600.00, 0.00, 0.00, '2025-08-31', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2025-09-04 07:03:32', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-10', 30),
(173, 9, 1, 7200.00, 0.00, 0.00, '2025-09-05', 0, NULL, 40.00, 'repaid', 1, NULL, 1440.00, 0, NULL, 0, 0, 0, '2025-09-04 07:38:53', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-15', 30),
(174, 48, 1, 15000.00, 0.00, 0.00, '2025-09-08', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-09-08 11:04:54', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-18', 30),
(175, 39, 1, 12000.00, 0.00, 0.00, '2025-09-02', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-09-08 15:31:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-12', 30),
(176, 34, 1, 23616.00, 0.00, 0.00, '2025-08-30', 0, NULL, 40.00, 'repaid', 1, NULL, 4723.20, 0, NULL, 0, 0, 0, '2025-09-08 15:38:24', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-09', 30),
(177, 33, 3, 50600.00, 0.00, 0.00, '2025-08-29', 0, NULL, 40.00, 'disbursed', 1, NULL, 10120.00, 0, NULL, 0, 0, 0, '2025-09-08 16:35:25', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-12', 42),
(178, 28, 5, 65910.00, 0.00, 0.00, '2025-09-04', 0, NULL, 40.00, 'repaid', 1, NULL, 19773.00, 0, NULL, 0, 0, 0, '2025-09-08 16:39:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-04', 90),
(179, 34, 1, 28340.00, 0.00, 0.00, '2025-09-10', 0, NULL, 40.00, 'repaid', 1, NULL, 5668.00, 0, NULL, 0, 0, 0, '2025-09-10 02:50:23', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-20', 30),
(180, 50, 9, 50000.00, 0.00, 0.00, '2025-09-12', 0, NULL, 40.00, 'repaid', 1, NULL, 25000.00, 0, NULL, 0, 0, 0, '2025-09-10 11:18:10', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-12-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-12', 150),
(181, 41, 3, 40000.00, 0.00, 0.00, '2025-09-10', 0, NULL, 40.00, 'repaid', 1, NULL, 8000.00, 0, NULL, 0, 0, 0, '2025-09-11 10:22:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-24', 42),
(182, 29, 1, 21600.00, 0.00, 0.00, '2025-09-11', 0, NULL, 40.00, 'repaid', 1, NULL, 4320.00, 0, NULL, 0, 0, 0, '2025-09-11 17:52:29', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-21', 30),
(183, 2, 1, 18000.00, 0.00, 0.00, '2025-09-02', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2025-09-12 13:48:39', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-12', 30),
(184, 39, 1, 10000.00, 0.00, 0.00, '2025-09-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-09-13 09:15:34', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-23', 30),
(185, 2, 1, 21600.00, 0.00, 0.00, '2025-09-13', 0, NULL, 40.00, 'repaid', 1, NULL, 4320.00, 0, NULL, 0, 0, 0, '2025-09-14 11:07:33', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-23', 30),
(186, 51, 10, 250000.00, 0.00, 0.00, '2025-09-13', 0, NULL, 40.00, 'repaid', 1, NULL, 75000.00, 0, NULL, 0, 0, 0, '2025-09-15 06:47:07', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-12-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-13', 150),
(187, 35, 1, 40000.00, 0.00, 0.00, '2025-09-15', 0, NULL, 40.00, 'repaid', 1, NULL, 8000.00, 0, NULL, 0, 0, 0, '2025-09-15 08:44:32', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-09-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-09-25', 30),
(188, 7, 2, 1000.00, 0.00, 0.00, '2025-09-08', 0, NULL, 40.00, 'repaid', 1, NULL, 200.00, 0, NULL, 0, 0, 0, '2025-09-16 09:27:55', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-08', 90),
(189, 45, 8, 163200.00, 0.00, 0.00, '2025-09-19', 0, NULL, 40.00, 'repaid', 2, 136000.00, 27200.00, 0, NULL, 0, 0, 0, '2025-09-19 08:19:34', '2026-09-15 18:07:12', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-18', 0, NULL, 0, NULL, NULL, '2026-09-18 18:07:12', NULL, 0, NULL, 0, '2026-09-18', 63),
(190, 31, 1, 10000.00, 0.00, 0.00, '2025-09-21', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-09-22 07:03:35', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-01', 30),
(191, 34, 1, 34008.00, 0.00, 0.00, '2025-09-21', 0, NULL, 40.00, 'repaid', 1, NULL, 6801.60, 0, NULL, 0, 0, 0, '2025-09-23 03:53:51', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-01', 30),
(192, 35, 1, 40000.00, 0.00, 0.00, '2025-09-26', 0, NULL, 40.00, 'repaid', 1, NULL, 8000.00, 0, NULL, 0, 0, 0, '2025-09-25 14:54:12', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-06', 30),
(193, 45, 8, 213600.00, 0.00, 0.00, '2025-09-29', 0, NULL, 0.00, 'repaid', 2, 178000.00, 35600.00, 0, NULL, 0, 0, 0, '2025-09-29 11:22:47', '2026-09-15 18:04:57', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-09-18', 0, NULL, 0, NULL, NULL, '2026-09-18 18:03:48', NULL, 0, NULL, 0, '2026-09-18', 63),
(194, 50, 1, 10000.00, 0.00, 0.00, '2025-09-27', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-09-29 11:24:57', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-07', 30);
INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `processing_fee_rate`, `total_processing_fees`, `borrow_date`, `broker_status`, `partner_id`, `broker_rate`, `status`, `cycle`, `original_amount`, `capitalized_interest`, `grace_period_days`, `grace_period_end_date`, `grace_days_balance`, `grace_days_earned`, `grace_days_used`, `created_at`, `updated_at`, `deleted_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`, `is_non_performing`, `default_date`, `days_in_default`, `default_triggered_at`, `recovery_started_at`, `forbearance_until`, `recovery_notes`, `days_overdue`, `last_overdue_check`, `default_triggered`, `calculated_due_date`, `npl_trigger_threshold`) VALUES
(195, 31, 1, 10000.00, 0.00, 0.00, '2025-10-02', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-10-01 07:11:11', '2026-08-06 13:11:45', NULL, NULL, NULL, 1, 1, '2025-10-02 21:01:08', NULL, '2025-10-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-12', 30),
(196, 17, 7, 17000.00, 0.00, 0.00, '2025-10-01', 0, NULL, 40.00, 'repaid', 1, NULL, 1700.00, 0, NULL, 0, 0, 0, '2025-10-01 07:22:02', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-11-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-01', 90),
(197, 15, 7, 23000.00, 0.00, 0.00, '2025-10-02', 0, NULL, 40.00, 'repaid', 1, NULL, 2300.00, 0, NULL, 0, 0, 0, '2025-10-01 07:23:40', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-11-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-02', 90),
(198, 52, 3, 50000.00, 0.00, 0.00, '2025-10-01', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-10-01 09:12:17', '2026-08-06 13:11:45', NULL, NULL, NULL, 1, 1, '2025-10-01 21:00:06', NULL, '2025-10-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-15', 42),
(199, 39, 1, 12000.00, 0.00, 0.00, '2025-09-24', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-10-03 06:52:56', '2026-08-06 13:11:45', NULL, NULL, NULL, 1, 1, '2025-09-24 21:00:00', NULL, '2025-10-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-04', 30),
(200, 39, 1, 14400.00, 0.00, 0.00, '2025-10-04', 0, NULL, 40.00, 'disbursed', 1, NULL, 2880.00, 0, NULL, 0, 0, 0, '2025-10-03 06:55:07', '2026-08-06 13:11:45', NULL, NULL, NULL, 1, 1, '2025-09-25 20:55:03', NULL, '2025-10-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-14', 30),
(201, 53, 1, 5000.00, 0.00, 0.00, '2025-10-03', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-10-03 07:37:33', '2026-08-06 13:11:45', NULL, NULL, NULL, 49, 1, '2025-10-14 20:56:47', NULL, '2025-10-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-13', 30),
(202, 2, 1, 10000.00, 0.00, 0.00, '2025-10-05', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-10-06 07:55:16', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-15', 30),
(203, 9, 2, 54368.00, 0.00, 0.00, '2025-10-02', 0, NULL, 40.00, 'repaid', 1, NULL, 10873.60, 0, NULL, 0, 0, 0, '2025-10-06 08:10:06', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-11-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-02', 90),
(204, 50, 1, 15000.00, 0.00, 0.00, '2025-10-06', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2025-10-06 09:02:10', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-16', 30),
(205, 54, 1, 50000.00, 0.00, 0.00, '2025-10-08', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-10-08 06:23:57', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-10-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-18', 30),
(206, 7, 2, 1200.00, 0.00, 0.00, '2025-10-08', 0, NULL, 40.00, 'repaid', 1, NULL, 240.00, 0, NULL, 0, 0, 0, '2025-10-13 11:16:33', '2026-08-06 13:11:45', NULL, NULL, NULL, NULL, 0, NULL, NULL, '2025-11-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-08', 90),
(207, 41, 3, 30000.00, 0.00, 0.00, '2025-10-13', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-10-13 15:46:13', '2026-08-06 13:11:45', NULL, 1, NULL, 15, 0, NULL, 'emergency', '2025-10-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-27', 42),
(208, 28, 12, 55835.00, 0.00, 0.00, '2025-09-30', 0, NULL, 40.00, 'disbursed', 1, NULL, 11167.00, 0, NULL, 0, 0, 0, '2025-10-14 06:40:13', '2026-08-06 13:11:45', NULL, NULL, NULL, 49, 0, NULL, NULL, '2025-11-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-30', 120),
(209, 34, 1, 30810.00, 0.00, 0.00, '2025-10-01', 0, NULL, 40.00, 'repaid', 1, NULL, 6161.92, 0, NULL, 0, 0, 0, '2025-10-14 07:42:25', '2026-08-06 13:11:45', NULL, NULL, NULL, 49, 1, '2025-10-10 20:55:29', NULL, '2025-10-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-11', 30),
(210, 34, 1, 14400.00, 0.00, 0.00, '2025-10-11', 0, NULL, 40.00, 'forbearance', 2, NULL, 9794.40, 0, NULL, 0, 0, 0, '2025-10-14 07:45:38', '2026-10-12 16:51:28', NULL, 1, 'Friend', 49, 1, '2025-10-14 20:51:26', NULL, '2026-10-22', 0, NULL, 0, NULL, NULL, '2026-10-22 16:51:28', NULL, 0, NULL, 0, '2026-10-22', 30),
(211, 50, 1, 20000.00, 0.00, 0.00, '2025-10-15', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-10-15 04:07:39', '2026-08-06 13:11:45', NULL, 13, 'Friend', 49, 1, '2025-10-15 04:07:39', 'Emergency loan for 10 days', '2025-10-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-25', 30),
(212, 21, 1, 100000.00, 0.00, 0.00, '2025-10-15', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-10-15 11:58:47', '2026-08-06 13:11:45', NULL, 13, 'Collegue', NULL, 0, NULL, 'Outstanding bill', '2025-10-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-25', 30),
(213, 29, 1, 10000.00, 0.00, 0.00, '2025-10-16', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-10-16 16:28:33', '2026-08-06 13:11:45', NULL, NULL, 'Colleague', NULL, 0, NULL, 'Facilitation', '2025-10-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-26', 30),
(214, 54, 1, 72000.00, 0.00, 0.00, '2025-10-21', 0, NULL, 40.00, 'repaid', 1, NULL, 14400.00, 0, NULL, 0, 0, 0, '2025-10-22 09:36:22', '2026-08-06 13:11:45', NULL, 13, 'Friend', 49, 1, '2025-10-22 09:36:22', 'roll over for the previous amount paid', '2025-10-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-10-31', 30),
(215, 55, 1, 10000.00, 0.00, 0.00, '2025-10-23', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-10-23 05:43:05', '2026-08-06 13:11:45', NULL, 13, 'Brother', 49, 1, '2025-10-23 05:43:05', 'Hospital Emergency', '2025-11-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-02', 30),
(216, 56, 1, 30000.00, 0.00, 0.00, '2025-10-24', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-10-24 06:01:04', '2026-08-06 13:11:45', NULL, 13, 'Friend', 49, 0, '2025-10-24 09:20:06', 'Personal Emergency', '2025-11-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-03', 30),
(217, 57, 1, 50000.00, 0.00, 0.00, '2025-10-31', 0, NULL, 40.00, 'disbursed', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2025-10-24 13:16:46', '2026-08-06 13:11:45', NULL, 2, 'Friend', 49, 1, '2025-10-31 14:35:59', 'Offsetting bills.', '2025-11-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-10', 30),
(218, 53, 3, 6000.00, 0.00, 0.00, '2025-10-25', 0, NULL, 40.00, 'pending', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-10-25 04:48:26', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2025-10-25 04:48:26', 'I need more capital to grow my business profit margins right now it\'s doing okay but if possible if I get some funds the margins will increase', '2025-11-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-08', 42),
(219, 53, 1, 3700.00, 0.00, 0.00, '2025-10-27', 0, NULL, 40.00, 'repaid', 1, NULL, 740.00, 0, NULL, 0, 0, 0, '2025-10-25 06:26:04', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'I need some more capital foe my business', '2025-11-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-06', 30),
(220, 31, 1, 10000.00, 0.00, 0.00, '2025-10-24', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-10-25 09:01:45', '2026-08-06 13:11:46', NULL, 13, 'Neighbour', 15, 0, NULL, 'Very much needed emergency and sorting KES 100,000 in a day', '2025-11-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-03', 30),
(221, 15, 7, 115300.00, 0.00, 0.00, '2025-10-27', 0, NULL, 40.00, 'repaid', 1, NULL, 11530.00, 0, NULL, 0, 0, 0, '2025-10-27 09:50:27', '2026-08-06 13:11:46', NULL, 13, 'Brother', 49, 0, NULL, 'school fees payment', '2025-11-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-27', 90),
(222, 50, 1, 25000.00, 0.00, 0.00, '2025-10-27', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-10-27 10:20:21', '2026-08-06 13:11:46', NULL, 13, 'Friend', NULL, 0, NULL, 'For purchase of good for the farm', '2025-11-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-06', 30),
(223, 59, 1, 6000.00, 0.00, 0.00, '2025-10-27', 0, NULL, 40.00, 'pending', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-10-27 15:24:52', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2025-10-27 15:24:52', 'I need to add some cash to support my business', '2025-11-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-06', 30),
(224, 29, 1, 10000.00, 0.00, 0.00, '2025-10-27', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-10-29 07:11:51', '2026-08-06 13:11:46', NULL, 13, NULL, NULL, 0, NULL, 'Emergency loan', '2025-11-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-06', 30),
(225, 21, 5, 200000.00, 0.00, 0.00, '2025-10-29', 0, NULL, 40.00, 'repaid', 1, NULL, 30000.00, 0, NULL, 0, 0, 0, '2025-10-29 12:42:31', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'emergency bailout', '2025-11-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-29', 90),
(226, 17, 10, 18700.00, 0.00, 0.00, '2025-11-02', 0, NULL, 40.00, 'repaid', 1, NULL, 5610.00, 0, NULL, 0, 0, 0, '2025-11-03 09:25:31', '2026-08-06 13:11:46', NULL, 13, 'Son', 49, 0, NULL, 'roll over from previous loan', '2026-02-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-02', 150),
(227, 2, 1, 10000.00, 0.00, 0.00, '2025-11-04', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-11-05 13:26:50', '2026-08-06 13:11:46', NULL, 13, 'Friend', 15, 1, '2025-11-05 13:26:50', 'emergency facility', '2025-11-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-14', 30),
(228, 55, 1, 10000.00, 0.00, 0.00, '2025-11-03', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-11-07 09:42:16', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-07 09:42:16', 'Roll over from previous loan', '2025-11-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-13', 30),
(229, 50, 1, 25000.00, 0.00, 0.00, '2025-11-07', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-11-07 09:47:34', '2026-08-06 13:11:46', NULL, 13, 'Friend', 15, 1, '2025-11-07 09:47:34', 'Roll over for previous?', '2025-11-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-17', 30),
(230, 53, 1, 3000.00, 0.00, 0.00, '2025-11-07', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2025-11-07 09:59:28', '2026-08-06 13:11:46', NULL, NULL, 'Friend', 15, 1, '2025-11-07 09:59:28', 'roll over from previous loan', '2025-11-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-17', 30),
(231, 29, 1, 12000.00, 0.00, 0.00, '2025-11-06', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-11-08 08:11:10', '2026-08-06 13:11:46', NULL, 15, 'Work Colleague', 1, 1, '2025-11-08 08:11:10', 'roll over from the previous loan', '2025-11-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-16', 30),
(232, 48, 1, 20000.00, 0.00, 0.00, '2025-11-10', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-11-10 15:18:48', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-10 15:18:48', 'emergency loan', '2025-11-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-20', 30),
(233, 9, 2, 35242.00, 0.00, 0.00, '2025-11-03', 0, NULL, 40.00, 'repaid', 1, NULL, 7048.32, 0, NULL, 0, 0, 0, '2025-11-10 15:20:47', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'monthly roll over', '2025-12-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-03', 90),
(234, 54, 1, 66000.00, 0.00, 0.00, '2025-11-01', 0, NULL, 40.00, 'repaid', 1, NULL, 13200.00, 0, NULL, 0, 0, 0, '2025-11-10 15:23:50', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2025-11-10 15:23:50', 'emergency loan roll over', '2025-11-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-11', 30),
(235, 54, 1, 79200.00, 0.00, 0.00, '2025-11-10', 0, NULL, 40.00, 'repaid', 1, NULL, 15840.00, 0, NULL, 0, 0, 0, '2025-11-13 17:53:31', '2026-08-06 13:11:46', NULL, NULL, NULL, 49, 0, NULL, 'Emergency roll over', '2025-11-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-20', 30),
(236, 56, 1, 36000.00, 0.00, 0.00, '2025-11-03', 0, NULL, 40.00, 'repaid', 1, NULL, 7200.00, 0, NULL, 0, 0, 0, '2025-11-17 09:31:07', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-17 09:31:07', 'rolled over 10 days loan', '2025-11-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-13', 30),
(237, 56, 1, 43200.00, 0.00, 0.00, '2025-11-13', 0, NULL, 40.00, 'repaid', 1, NULL, 8640.00, 0, NULL, 0, 0, 0, '2025-11-17 09:33:56', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-17 09:33:56', 'rolled over 10 days loan', '2025-11-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-23', 30),
(238, 55, 1, 12000.00, 0.00, 0.00, '2025-11-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2025-11-17 09:42:06', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-17 09:42:06', 'Emergency loan roll over', '2025-11-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-23', 30),
(239, 50, 1, 25000.00, 0.00, 0.00, '2025-11-18', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-11-17 18:50:45', '2026-08-06 13:11:46', NULL, 13, 'Friend', 49, 1, '2025-11-17 18:50:45', 'roll over of previous loan', '2025-11-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-28', 30),
(240, 29, 1, 14400.00, 0.00, 0.00, '2025-11-16', 0, NULL, 40.00, 'repaid', 1, NULL, 2880.00, 0, NULL, 0, 0, 0, '2025-11-18 05:04:27', '2026-08-06 13:11:46', NULL, NULL, 'Friend', 1, 0, NULL, 'roll over of previous loan', '2025-11-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-26', 30),
(241, 49, 2, 10000.00, 0.00, 0.00, '2025-11-18', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2025-11-19 05:09:39', '2026-08-06 13:11:46', NULL, 13, 'Brother', 15, 0, NULL, 'Payment for Good Conduct and NTSA', '2025-12-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-18', 90),
(242, 41, 3, 30000.00, 0.00, 0.00, '2025-11-18', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2025-11-19 05:21:43', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-19 05:21:43', 'emergency loan', '2025-12-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-02', 42),
(243, 53, 1, 2700.00, 0.00, 0.00, '2025-11-17', 0, NULL, 40.00, 'repaid', 1, NULL, 540.00, 0, NULL, 0, 0, 0, '2025-11-19 09:25:49', '2026-08-06 13:11:46', NULL, NULL, NULL, 49, 0, NULL, 'Emergency roll over', '2025-11-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-27', 30),
(244, 51, 2, 600000.00, 0.00, 0.00, '2025-11-22', 0, NULL, 40.00, 'repaid', 1, NULL, 120000.00, 0, NULL, 0, 0, 0, '2025-11-22 19:26:47', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-22 19:26:47', 'Affordable Housing Marsabit logistics', '2025-12-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-22', 90),
(245, 52, 1, 20000.00, 0.00, 0.00, '2025-11-22', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-11-22 19:28:34', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-22 19:28:34', 'Emergency loan for a friend', '2025-12-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-02', 30),
(246, 2, 1, 20000.00, 0.00, 0.00, '2025-11-21', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-11-22 19:33:43', '2026-08-06 13:11:46', NULL, 13, 'Friend', 15, 0, NULL, 'steph\'s birthday party', '2025-12-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-01', 30),
(247, 54, 1, 95040.00, 0.00, 0.00, '2025-11-20', 0, NULL, 40.00, 'repaid', 1, NULL, 19008.00, 0, NULL, 0, 0, 0, '2025-11-25 10:28:41', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-25 10:28:41', 'ROLLED over from previous loan', '2025-11-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-11-30', 30),
(248, 55, 1, 14400.00, 0.00, 0.00, '2025-11-23', 0, NULL, 40.00, 'repaid', 1, NULL, 2880.00, 0, NULL, 0, 0, 0, '2025-11-26 19:33:56', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-26 19:33:56', 'EMERGENCY ROLL OVER', '2025-12-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-03', 30),
(249, 50, 1, 25000.00, 0.00, 0.00, '2025-11-28', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-11-28 18:26:26', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-11-28 18:26:26', 'Emergency roll over', '2025-12-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-08', 30),
(250, 15, 15, 126830.00, 0.00, 0.00, '2025-11-27', 0, NULL, 40.00, 'repaid', 1, NULL, 7609.80, 0, NULL, 0, 0, 0, '2025-11-28 18:29:57', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency roll over', '2026-02-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-27', 150),
(251, 56, 1, 43840.00, 0.00, 0.00, '2025-11-23', 0, NULL, 40.00, 'repaid', 1, NULL, 8768.00, 0, NULL, 0, 0, 0, '2025-11-29 14:19:17', '2026-08-06 13:11:46', NULL, NULL, NULL, 49, 1, '2025-11-29 14:19:17', 'emergency roll over', '2025-12-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-03', 30),
(252, 2, 1, 20000.00, 0.00, 0.00, '2025-12-01', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2025-12-01 19:12:52', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-01 19:12:52', 'Rolled over from previous loan', '2025-12-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-11', 30),
(253, 54, 1, 114048.00, 0.00, 0.00, '2025-11-30', 0, NULL, 40.00, 'repaid', 1, NULL, 22809.60, 0, NULL, 0, 0, 0, '2025-12-03 04:32:39', '2026-08-06 13:11:46', NULL, 1, 'FRIEND', 49, 1, '2025-12-03 04:32:39', 'Emergency roll over', '2025-12-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-10', 30),
(254, 26, 2, 100000.00, 0.00, 0.00, '2025-11-24', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-12-03 04:43:37', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2025-12-03 04:43:37', 'emergency job use', '2025-12-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-24', 90),
(255, 53, 1, 2680.00, 0.00, 0.00, '2025-11-27', 0, NULL, 40.00, 'repaid', 1, NULL, 536.00, 0, NULL, 0, 0, 0, '2025-12-03 04:47:40', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2025-12-03 04:47:40', 'emergency rolled over', '2025-12-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-07', 30),
(256, 50, 13, 50000.00, 0.00, 0.00, '2025-12-06', 0, NULL, 40.00, 'repaid', 1, NULL, 30000.00, 0, NULL, 0, 0, 0, '2025-12-06 08:45:46', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-06 08:45:46', 'repair of greenhouse in isinya', '2026-03-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-06', 150),
(257, 9, 2, 42290.00, 0.00, 0.00, '2025-12-03', 0, NULL, 40.00, 'repaid', 1, NULL, 8458.00, 0, NULL, 0, 0, 0, '2025-12-06 08:54:39', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2025-12-06 08:54:39', 'roll over from previous loan', '2026-01-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-03', 90),
(258, 21, 5, 130000.00, 0.00, 0.00, '2025-11-29', 0, NULL, 40.00, 'repaid', 1, NULL, 39000.00, 0, NULL, 0, 0, 0, '2025-12-08 05:29:12', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-08 05:29:12', 'emergency roll over loan facility', '2025-12-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-29', 90),
(259, 56, 1, 37608.00, 0.00, 0.00, '2025-12-03', 0, NULL, 40.00, 'repaid', 1, NULL, 7521.60, 0, NULL, 0, 0, 0, '2025-12-09 06:18:55', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-09 06:18:55', 'previous loan roll over', '2025-12-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-13', 30),
(260, 50, 1, 25000.00, 0.00, 0.00, '2025-12-09', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2025-12-09 06:22:55', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-09 06:22:55', 'roll over from previous loan', '2025-12-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-19', 30),
(261, 55, 1, 8780.00, 0.00, 0.00, '2025-12-03', 0, NULL, 40.00, 'repaid', 1, NULL, 1756.00, 0, NULL, 0, 0, 0, '2025-12-09 06:26:57', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-09 06:26:57', 'rolled over', '2025-12-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-13', 30),
(262, 26, 2, 100000.00, 0.00, 0.00, '2025-12-28', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2025-12-26 08:53:48', '2026-08-06 13:11:46', NULL, 13, 'Colleague', 1, 0, NULL, 'emergency loan roll over', '2026-01-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-28', 90),
(263, 55, 1, 10536.00, 0.00, 0.00, '2025-12-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2107.20, 0, NULL, 0, 0, 0, '2025-12-26 08:58:13', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-26 08:58:13', 'rolled over loan', '2025-12-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-23', 30),
(264, 55, 1, 12644.00, 0.00, 0.00, '2025-12-23', 0, NULL, 40.00, 'repaid', 1, NULL, 2528.64, 0, NULL, 0, 0, 0, '2025-12-26 09:00:07', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over loan', '2026-01-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-02', 30),
(265, 56, 1, 45130.00, 0.00, 0.00, '2025-12-13', 0, NULL, 40.00, 'repaid', 1, NULL, 9025.92, 0, NULL, 0, 0, 0, '2025-12-26 09:03:12', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'rolled over facility', '2025-12-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2025-12-23', 30),
(266, 56, 1, 54156.00, 0.00, 0.00, '2025-12-23', 0, NULL, 40.00, 'pending', 1, NULL, 10831.10, 0, NULL, 0, 0, 0, '2025-12-26 09:05:38', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over facility', '2026-01-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-02', 30),
(267, 62, 1, 5000.00, 0.00, 0.00, '2025-12-22', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-12-26 18:05:43', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2025-12-26 18:05:43', 'emergency facility for salary', '2026-01-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-01', 30),
(268, 62, 1, 5000.00, 0.00, 0.00, '2025-12-26', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2025-12-26 18:06:44', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2025-12-26 18:06:44', 'emergency facility for salary', '2026-01-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-05', 30),
(269, 51, 2, 720000.00, 0.00, 0.00, '2025-12-22', 0, NULL, 40.00, 'repaid', 1, NULL, 144000.00, 0, NULL, 0, 0, 0, '2025-12-28 09:47:55', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Yes... let\'s roll over the loan. Atalipwa 20% at end of Jan.', '2026-01-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-22', 90),
(270, 41, 3, 80000.00, 0.00, 0.00, '2025-12-29', 0, NULL, 40.00, 'repaid', 1, NULL, 16000.00, 0, NULL, 0, 0, 0, '2025-12-31 04:36:18', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2025-12-31 04:36:18', 'for business emergency', '2026-01-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-12', 42),
(271, 53, 2, 7200.00, 0.00, 0.00, '2026-01-06', 0, NULL, 40.00, 'pending', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2026-01-02 06:54:02', '2026-08-06 13:11:46', NULL, 13, 'Friend', NULL, 0, NULL, 'Added capital for my business', '2026-02-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-06', 90),
(272, 2, 1, 20000.00, 0.00, 0.00, '2025-12-26', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2026-01-05 01:59:34', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-01-05 01:59:34', 'Emergency facility', '2026-01-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-05', 30),
(273, 55, 1, 15172.00, 0.00, 0.00, '2026-01-02', 0, NULL, 40.00, 'repaid', 1, NULL, 3034.37, 0, NULL, 0, 0, 0, '2026-01-08 14:13:15', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-08 14:13:15', 'rolled over', '2026-01-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-12', 30),
(274, 52, 1, 40000.00, 0.00, 0.00, '2026-01-09', 0, NULL, 40.00, 'repaid', 1, NULL, 8000.00, 0, NULL, 0, 0, 0, '2026-01-09 07:01:46', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-09 07:01:46', 'emergency loan', '2026-01-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-19', 30),
(275, 2, 2, 100000.00, 0.00, 0.00, '2026-01-09', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-01-12 06:00:36', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-01-12 06:00:36', 'emergency loan for the month', '2026-02-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-09', 90),
(276, 41, 1, 50000.00, 0.00, 0.00, '2026-01-09', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-01-12 06:04:19', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-12 06:04:19', 'emergency loan 5-10 days', '2026-01-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-19', 30),
(277, 48, 1, 35000.00, 0.00, 0.00, '2026-01-12', 0, NULL, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2026-01-12 07:00:44', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-12 07:00:44', 'china orders', '2026-01-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-22', 30),
(278, 13, 10, 100000.00, 0.00, 0.00, '2025-12-22', 0, NULL, 40.00, 'repaid', 1, NULL, 30000.00, 0, NULL, 0, 0, 0, '2026-01-12 07:10:34', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'emergency loan', '2026-03-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-22', 150),
(279, 21, 5, 169000.00, 0.00, 0.00, '2025-12-31', 0, NULL, 40.00, 'repaid', 1, NULL, 50700.00, 0, NULL, 0, 0, 0, '2026-01-12 13:27:03', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'Emergency roll over', '2026-01-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-31', 90),
(280, 9, 2, 50748.00, 0.00, 0.00, '2026-01-03', 0, NULL, 40.00, 'repaid', 1, NULL, 10149.60, 0, NULL, 0, 0, 0, '2026-01-15 16:41:01', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-15 16:41:01', 'rolled over', '2026-02-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-03', 90),
(281, 38, 1, 6000.00, 0.00, 0.00, '2026-01-16', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2026-01-16 13:43:08', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-01-16 13:43:08', 'emergency loan', '2026-01-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-26', 30),
(282, 65, 1, 250000.00, 0.00, 0.00, '2026-01-21', 0, NULL, 40.00, 'repaid', 1, NULL, 50000.00, 0, NULL, 0, 0, 0, '2026-01-21 14:23:34', '2026-08-06 13:11:46', NULL, 13, 'Friend', 15, 1, '2026-01-21 10:52:47', 'Emergency contact', '2026-01-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-31', 30),
(283, 62, 1, 8000.00, 0.00, 0.00, '2026-01-22', 0, NULL, 40.00, 'repaid', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2026-01-27 15:13:33', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-01-27 15:13:33', 'emergency facility', '2026-02-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-01', 30),
(285, 2, 14, 30000.00, 0.00, 0.00, '2026-01-26', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-01-29 08:25:29', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-01-29 08:25:29', 'emergency loan 1 day', '2026-01-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-01-31', 19),
(286, 2, 1, 8000.00, 0.00, 0.00, '2026-01-29', 0, NULL, 40.00, 'repaid', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2026-01-29 08:27:58', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'Emergency loan', '2026-02-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-08', 30),
(287, 26, 2, 100000.00, 0.00, 0.00, '2026-01-26', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-01-29 10:08:54', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'roll over emergency', '2026-02-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-26', 90),
(288, 51, 2, 364000.00, 0.00, 0.00, '2026-01-22', 0, NULL, 40.00, 'repaid', 1, NULL, 72800.00, 0, NULL, 0, 0, 0, '2026-02-01 19:38:18', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'rolled over facility', '2026-02-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-22', 90),
(289, 65, 1, 300000.00, 0.00, 0.00, '2026-01-31', 0, NULL, 40.00, 'repaid', 1, NULL, 60000.00, 0, NULL, 0, 0, 0, '2026-02-03 14:42:30', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-02-03 14:42:30', 'Roll over loan', '2026-02-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-10', 30),
(290, 3, 14, 100000.00, 0.00, 0.00, '2026-02-05', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-02-09 13:30:31', '2026-08-06 13:11:46', NULL, 13, 'Friend', 15, 0, NULL, 'emergency brokered loan', '2026-02-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-10', 19),
(291, 2, 5, 65560.00, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'repaid', 1, NULL, 19668.00, 0, NULL, 0, 0, 0, '2026-02-09 13:40:18', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'ROLLED over 1 more month', '2026-03-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-09', 90),
(292, 52, 1, 100000.00, 0.00, 0.00, '2026-02-08', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-02-09 13:42:05', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'Emergency loan', '2026-02-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-18', 30),
(293, 13, 15, 252000.00, 0.00, 0.00, '2026-02-05', 0, NULL, 40.00, 'repaid', 1, NULL, 15120.00, 0, NULL, 0, 0, 0, '2026-02-09 13:46:57', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 0, NULL, 'New house rent 252000 - 75000 stipend', '2026-05-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-05', 150),
(299, 13, 15, 50000.00, 0.00, 0.00, '2026-06-09', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-02-09 17:39:49', '2026-09-09 17:22:27', NULL, NULL, NULL, NULL, 0, NULL, '65789 fghjkjlk hjklk', '2026-09-09', 0, '2026-08-06', 0, '2026-08-06 13:12:50', NULL, NULL, 'Default triggered: Loan overdue for 33.366086789456 days on 2026-08-06 15:12', 33, '2026-08-06 18:13:35', 1, '2026-09-09', 150),
(300, 13, 2, 1.20, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'pending', 1, NULL, 0.20, 0, NULL, 0, 0, 0, '2026-02-09 17:47:21', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-09 17:47:21', '65789 fghjkjlk hjklk', '2026-03-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-09', 90),
(301, 13, 2, 1.20, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'pending', 1, NULL, 0.20, 0, NULL, 0, 0, 0, '2026-02-09 17:47:52', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-09 17:47:52', '65789 fghjkjlk hjklk', '2026-03-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-09', 90),
(302, 13, 1, 1.20, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'pending', 1, NULL, 0.20, 0, NULL, 0, 0, 0, '2026-02-09 17:49:25', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-09 17:49:25', 'iojhgkjbnn ifhiloj;l', '2026-02-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-19', 30),
(303, 13, 1, 1.20, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'pending', 1, NULL, 0.20, 0, NULL, 0, 0, 0, '2026-02-09 17:54:40', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-09 17:54:39', 'iojhgkjbnn ifhiloj;l', '2026-02-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-19', 30),
(304, 13, 1, 1.20, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'pending', 1, NULL, 0.20, 0, NULL, 0, 0, 0, '2026-02-09 18:06:23', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-09 18:06:23', 'iuytgkjvn jk', '2026-02-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-19', 30),
(307, 3, 1, 150000.00, 0.00, 0.00, '2026-02-11', 0, NULL, 40.00, 'repaid', 1, NULL, 30000.00, 0, NULL, 0, 0, 0, '2026-02-11 16:36:36', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-11 16:36:36', 'brokered loans', '2026-02-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-21', 30),
(308, 50, 2, 15000.00, 0.00, 0.00, '2026-02-16', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-02-17 13:40:07', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-17 13:40:07', 'emergency loan', '2026-03-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-16', 90),
(309, 3, 1, 12000.00, 0.00, 0.00, '2026-02-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2400.00, 0, NULL, 0, 0, 0, '2026-02-17 13:45:43', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-17 13:45:43', 'emergency client lan', '2026-02-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-23', 30),
(310, 65, 1, 360000.00, 0.00, 0.00, '2026-02-10', 0, NULL, 40.00, 'repaid', 1, NULL, 72000.00, 0, NULL, 0, 0, 0, '2026-02-17 13:47:39', '2026-08-06 13:11:46', NULL, NULL, NULL, NULL, 1, '2026-02-17 13:47:39', 'rolled over loan', '2026-02-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-20', 30),
(311, 21, 5, 219700.00, 0.00, 0.00, '2026-01-31', 0, NULL, 40.00, 'repaid', 1, NULL, 65910.00, 0, NULL, 0, 0, 0, '2026-02-18 05:27:29', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-02-18 05:27:29', 'rolled over loan', '2026-03-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-03', 90),
(312, 9, 2, 60898.00, 0.00, 0.00, '2026-02-18', 0, NULL, 40.00, 'repaid', 1, NULL, 12179.52, 0, NULL, 0, 0, 0, '2026-02-18 05:35:31', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-02-18 05:35:31', 'rolled over again', '2026-03-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-18', 90),
(313, 67, 1, 5000.00, 0.00, 0.00, '2026-02-18', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-02-18 09:00:21', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-02-18 09:00:21', 'emergency loan', '2026-02-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-28', 30),
(314, 68, 1, 30000.00, 0.00, 0.00, '2026-02-16', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-02-19 09:20:01', '2026-08-06 13:11:46', NULL, 48, 'Friend', 1, 1, '2026-02-19 09:20:01', 'emergency loan', '2026-02-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-26', 30),
(315, 31, 1, 50000.00, 0.00, 0.00, '2026-02-09', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-02-19 09:39:32', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency loan', '2026-02-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-19', 30),
(316, 31, 1, 160000.00, 0.00, 0.00, '2026-02-18', 0, NULL, 40.00, 'repaid', 1, NULL, 32000.00, 0, NULL, 0, 0, 0, '2026-02-19 09:41:38', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency funds 70k + roll over 30k', '2026-02-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-28', 30),
(317, 41, 2, 100000.00, 0.00, 0.00, '2026-02-19', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-02-19 10:01:24', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Hey uko poa? Please Top me up 100k in my back  account to be paid back next week. I need to pay suppliers asap', '2026-03-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-19', 90),
(318, 69, 1, 4000.00, 0.00, 0.00, '2026-02-19', 0, NULL, 40.00, 'repaid', 1, NULL, 800.00, 0, NULL, 0, 0, 0, '2026-02-19 10:06:48', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency loan', '2026-03-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-01', 30),
(319, 52, 1, 40000.00, 0.00, 0.00, '2026-02-20', 0, NULL, 40.00, 'repaid', 1, NULL, 8000.00, 0, NULL, 0, 0, 0, '2026-02-21 21:14:12', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-02-21 21:14:12', 'roll over loan', '2026-03-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-02', 30),
(320, 70, 1, 2000.00, 0.00, 0.00, '2026-02-18', 0, NULL, 40.00, 'repaid', 1, NULL, 400.00, 0, NULL, 0, 0, 0, '2026-02-23 14:09:11', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-02-23 14:09:11', '1000 + 1000 due on 28th Feb', '2026-02-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-28', 30),
(321, 3, 14, 181248.00, 0.00, 0.00, '2026-02-22', 0, NULL, 40.00, 'repaid', 1, NULL, 18124.80, 0, NULL, 0, 0, 0, '2026-02-24 16:04:45', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-02-24 16:04:45', 'Rolled over', '2026-02-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-02-27', 19),
(322, 26, 2, 100000.00, 0.00, 0.00, '2026-02-26', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-02-26 18:00:03', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-02-26 18:00:03', 'roll over and to be paid in 4 tranches', '2026-03-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-26', 90),
(323, 31, 1, 192000.00, 0.00, 0.00, '2026-02-28', 0, NULL, 40.00, 'repaid', 1, NULL, 38400.00, 0, NULL, 0, 0, 0, '2026-02-27 19:33:49', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-02-27 19:33:49', 'rolled over loan', '2026-03-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-10', 30),
(324, 3, 3, 150000.00, 0.00, 0.00, '2026-02-27', 0, NULL, 40.00, 'repaid', 1, NULL, 30000.00, 0, NULL, 0, 0, 0, '2026-03-03 06:42:10', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-03 06:42:10', 'rolled over loan', '2026-03-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-13', 42),
(325, 21, 2, 285610.00, 0.00, 0.00, '2026-03-03', 0, NULL, 40.00, 'repaid', 1, NULL, 57122.00, 0, NULL, 0, 0, 0, '2026-03-04 06:06:40', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-04 06:06:40', 'rolled over', '2026-04-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-03', 90),
(326, 62, 1, 3500.00, 0.00, 0.00, '2026-03-04', 0, NULL, 40.00, 'repaid', 1, NULL, 700.00, 0, NULL, 0, 0, 0, '2026-03-04 07:39:24', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-04 07:39:24', 'emergency loan', '2026-03-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-14', 30),
(327, 29, 1, 15000.00, 0.00, 0.00, '2026-03-03', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-03-04 07:48:59', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-04 07:48:59', 'emergency lon', '2026-03-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-13', 30),
(328, 53, 1, 3000.00, 0.00, 0.00, '2026-03-06', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2026-03-06 10:13:12', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-06 10:13:12', 'emergency loan', '2026-03-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-16', 30),
(329, 50, 3, 55000.00, 0.00, 0.00, '2026-03-06', 0, NULL, 40.00, 'repaid', 1, NULL, 11000.00, 0, NULL, 0, 0, 0, '2026-03-06 12:57:32', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-06 12:57:32', 'rolled over for ramadhan', '2026-03-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-20', 42),
(330, 65, 1, 432000.00, 0.00, 0.00, '2026-02-20', 0, NULL, 40.00, 'repaid', 1, NULL, 86400.00, 0, NULL, 0, 0, 0, '2026-03-06 13:02:18', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-03-06 13:02:18', 'rolled over due to non-payment', '2026-03-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-02', 30),
(331, 65, 1, 518400.00, 0.00, 0.00, '2026-03-02', 0, NULL, 40.00, 'disbursed', 1, NULL, 103680.00, 0, NULL, 0, 0, 0, '2026-03-06 13:04:24', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-06 13:04:24', 'rolled over due to lying and non-payment', '2026-03-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-12', 30),
(332, 68, 1, 20000.00, 0.00, 0.00, '2026-03-08', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2026-03-08 13:57:33', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-08 13:57:33', 'FOR a phone', '2026-03-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-18', 30),
(333, 48, 1, 15000.00, 0.00, 0.00, '2026-03-08', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-03-08 13:59:09', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-08 13:59:09', 'emergency float', '2026-03-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-18', 30),
(334, 71, 14, 50000.00, 0.00, 0.00, '2026-03-07', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2026-03-08 14:01:06', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-08 14:01:06', 'emergency loan facility for 5 days', '2026-03-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-12', 19),
(335, 2, 5, 85228.00, 0.00, 0.00, '2026-03-09', 0, NULL, 40.00, 'repaid', 1, NULL, 25568.40, 0, NULL, 0, 0, 0, '2026-03-12 11:21:50', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-03-12 11:21:50', 'rolled over facility', '2026-04-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-09', 90),
(336, 53, 1, 2000.00, 0.00, 0.00, '2026-03-12', 0, NULL, 40.00, 'repaid', 1, NULL, 400.00, 0, NULL, 0, 0, 0, '2026-03-13 08:14:46', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-13 08:14:46', 'emergency loan', '2026-03-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-22', 30),
(337, 41, 1, 50000.00, 0.00, 0.00, '2026-03-15', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-03-14 19:46:05', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency loan', '2026-03-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-25', 30),
(338, 29, 1, 18000.00, 0.00, 0.00, '2026-03-13', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2026-03-14 19:54:06', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-14 19:54:06', 'rolled over loan', '2026-03-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-23', 30),
(339, 11, 1, 37500.00, 0.00, 0.00, '2026-03-13', 0, NULL, 40.00, 'repaid', 1, NULL, 7500.00, 0, NULL, 0, 0, 0, '2026-03-14 20:02:29', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency loan', '2026-03-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-23', 30),
(340, 62, 1, 6000.00, 0.00, 0.00, '2026-02-20', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2026-03-15 18:43:08', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-15 18:43:08', 'emergency loan', '2026-03-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-02', 30),
(341, 62, 1, 7200.00, 0.00, 0.00, '2026-03-02', 0, NULL, 40.00, 'repaid', 1, NULL, 1440.00, 0, NULL, 0, 0, 0, '2026-03-15 18:47:37', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-15 18:47:37', 'rolled over loan', '2026-03-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-12', 30),
(342, 62, 1, 8640.00, 0.00, 0.00, '2026-03-12', 0, NULL, 40.00, 'repaid', 1, NULL, 1728.00, 0, NULL, 0, 0, 0, '2026-03-15 18:49:24', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-03-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-22', 30),
(343, 72, 1, 10000.00, 0.00, 0.00, '2026-03-16', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-03-16 15:15:01', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-16 15:15:01', 'emergency loan', '2026-03-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-26', 30),
(344, 53, 1, 5000.00, 0.00, 0.00, '2026-03-18', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-03-19 05:18:26', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-19 05:18:26', 'Bank to M-PESA transfer of KES 5,000.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3816TGLK5577. M-PESA Ref ID: UCI6B9O9OT\r\n\r\nTsuma 10 days 20% due March 28th', '2026-03-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-28', 30),
(345, 2, 1, 30000.00, 0.00, 0.00, '2026-03-18', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-03-19 07:14:22', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-03-19 07:14:22', 'UCH9X9PH8Y Confirmed. Ksh30,000.00 sent to Edward  Kipsanai 0710920629 on 17/3/26 at 2:10 PM.', '2026-03-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-28', 30),
(346, 48, 1, 5000.00, 0.00, 0.00, '2026-03-19', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-03-20 15:14:52', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'emergency loan', '2026-03-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-29', 30),
(347, 31, 1, 50000.00, 0.00, 0.00, '2026-02-28', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-03-20 15:21:35', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-20 15:21:35', 'Kengen are paying me today we have nothing however next week it’s done plus the interest I always pay when I’m stuck it’s just a payment issue. So now $34K being paid on Wednesday I will sort you. I have just said Wednesday coz of any issues one thing you see I pay I cannot default on 50K plus interest. I humbly ask you work with me I cannot default share all documentation for the same you see I can put you in my account. Chief I’m humble and asking kindly give me to then funds are being disbursed however I don’t have anything with banks on disbursing your chums is guaranteed next week those funds are paying my rent and my everything $34K is good money', '2026-03-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-10', 30),
(349, 15, 15, 114440.00, 0.00, 0.00, '2026-02-27', 0, NULL, 40.00, 'repaid', 1, NULL, 6866.39, 0, NULL, 0, 0, 0, '2026-03-20 18:24:30', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-20 18:24:30', 'rolled over loan', '2026-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-27', 150),
(350, 50, 1, 8000.00, 0.00, 0.00, '2026-03-24', 0, NULL, 40.00, 'repaid', 1, NULL, 1600.00, 0, NULL, 0, 0, 0, '2026-03-25 07:28:16', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-25 07:28:16', 'Bank to M-PESA transfer of KES 8,000.00 to 254721544928 - Mohamed Abdirahim Abdi successfully processed. Transaction Ref ID: 3864TTHG3204. M-PESA Ref ID: UCOGCABJKN\r\n\r\n10 days at 20% facility to be paid earliest before 10 days as KES 9,600', '2026-04-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-03', 30),
(351, 68, 1, 10000.00, 0.00, 0.00, '2026-03-25', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-03-25 07:30:42', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 10,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 3872ZVOL8239. M-PESA Ref ID: UCPBMAL8MF\r\n\r\n10 days 20% payable on 3rd April', '2026-04-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-04', 30),
(352, 7, 2, 1500.00, 0.00, 0.00, '2026-03-25', 0, NULL, 40.00, 'repaid', 1, NULL, 300.00, 0, NULL, 0, 0, 0, '2026-03-25 07:54:35', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'UCP6OABVYQ Confirmed. KSH. 1,500 sent to Keneth Owino,  via MySafaricom App on 25-03-2026 11:15.', '2026-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-25', 90),
(353, 72, 1, 10000.00, 0.00, 0.00, '2026-03-26', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-03-26 20:57:06', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-26 20:57:06', 'rolled over loan for 5 days', '2026-04-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-05', 30),
(354, 29, 1, 20000.00, 0.00, 0.00, '2026-03-26', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2026-03-27 08:35:47', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-27 08:35:47', 'Bank to M-PESA transfer of KES 20,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 3883JNTY9707. M-PESA Ref ID: UCQKBAE0DG', '2026-04-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-05', 30),
(355, 53, 1, 1000.00, 0.00, 0.00, '2026-03-27', 0, NULL, 40.00, 'repaid', 1, NULL, 200.00, 0, NULL, 0, 0, 0, '2026-03-29 16:48:58', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-29 16:48:58', 'UCR6OAKFLQ Confirmed. KSH. 1,000 sent to EMMANUEL TSUMA,  via MySafaricom App on 27-03-2026 14:35.', '2026-04-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-06', 30),
(356, 67, 1, 5000.00, 0.00, 0.00, '2026-03-28', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-03-29 16:50:57', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-03-29 16:50:57', 'Bank to M-PESA transfer of KES 5,000.00 to 254700742394 - MICHAEL NZUKA MUSYIMI successfully processed. Transaction Ref ID: 3902DEVE0397. M-PESA Ref ID: UCSOIB1OMC', '2026-04-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-07', 30),
(357, 68, 1, 45000.00, 0.00, 0.00, '2026-03-30', 0, NULL, 40.00, 'repaid', 1, NULL, 9000.00, 0, NULL, 0, 0, 0, '2026-03-29 16:54:56', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 45,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 3907OPGV8266. M-PESA Ref ID: UCTBMB1ZDN', '2026-04-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-09', 30),
(358, 31, 1, 10000.00, 0.00, 0.00, '2026-03-27', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-03-29 17:14:25', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Dear DENNIS, MPESA transfer of KES 10000 to LEON MUSAU-254720747652 at 27-03-2026 10:05 PM was successful.MPESA Ref:UCR4AB9NS5.\r\n\r\nRepayable on Monday as KES 12000', '2026-04-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-06', 30);
INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `processing_fee_rate`, `total_processing_fees`, `borrow_date`, `broker_status`, `partner_id`, `broker_rate`, `status`, `cycle`, `original_amount`, `capitalized_interest`, `grace_period_days`, `grace_period_end_date`, `grace_days_balance`, `grace_days_earned`, `grace_days_used`, `created_at`, `updated_at`, `deleted_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`, `is_non_performing`, `default_date`, `days_in_default`, `default_triggered_at`, `recovery_started_at`, `forbearance_until`, `recovery_notes`, `days_overdue`, `last_overdue_check`, `default_triggered`, `calculated_due_date`, `npl_trigger_threshold`) VALUES
(359, 58, 14, 10000.00, 0.00, 0.00, '2026-04-01', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-04-02 04:47:36', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-02 04:47:36', 'Bank to M-PESA transfer of KES 10,000.00 to 254722778298 - VIVIAN NEKESA SIMIYU successfully processed. Transaction Ref ID: 3936JYNW2747. M-PESA Ref ID: UD132BBR99', '2026-04-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-06', 19),
(360, 26, 2, 100000.00, 0.00, 0.00, '2026-03-26', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-04-02 04:57:05', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-04-02 04:57:05', 'rolled over loan', '2026-04-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-26', 90),
(361, 53, 1, 3200.00, 0.00, 0.00, '2026-03-31', 0, NULL, 40.00, 'repaid', 1, NULL, 640.00, 0, NULL, 0, 0, 0, '2026-04-02 04:59:02', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-02 04:59:02', 'Bank to M-PESA transfer of KES 3,200.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3926CFQD3972. M-PESA Ref ID: UCV6BB0M8H\r\n\r\nDue in 10 days 20%', '2026-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-10', 30),
(362, 11, 1, 5000.00, 0.00, 0.00, '2026-03-31', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-04-02 05:01:37', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-02 05:01:37', 'Bank to M-PESA transfer of KES 5,000.00 to 254724606690 - SHADRACK CHERUIYOT successfully processed. Transaction Ref ID: 3936VBGY2825. M-PESA Ref ID: UD1HDBDUSY', '2026-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-10', 30),
(363, 2, 1, 25000.00, 0.00, 0.00, '2026-03-30', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2026-04-02 05:08:26', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-04-02 05:08:26', 'UCU9XB5HFB Confirmed. Ksh25,000.00 sent to Edward  Kipsanai 0710920629 on 30/3/26 at 6:35 PM.', '2026-04-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-09', 30),
(364, 9, 2, 73078.00, 0.00, 0.00, '2026-03-18', 0, NULL, 40.00, 'repaid', 1, NULL, 14615.42, 0, NULL, 0, 0, 0, '2026-04-02 05:17:32', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over facility because of an office scandal and no payment for a month', '2026-04-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-18', 90),
(365, 48, 1, 30000.00, 0.00, 0.00, '2026-04-04', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2026-04-04 10:58:42', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-04 10:58:42', 'Bank to M-PESA transfer of KES 25,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 3960XHRB9386. M-PESA Ref ID: UD4ALBIZKO\r\n\r\nDue in 10 days at 20% interest \r\n\r\nPayable on or before 14/4/2026 KES 30,000', '2026-04-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-14', 30),
(366, 62, 1, 4200.00, 0.00, 0.00, '2026-03-14', 0, NULL, 40.00, 'repaid', 1, NULL, 840.00, 0, NULL, 0, 0, 0, '2026-04-04 11:06:48', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-04 11:06:48', 'rolled over', '2026-03-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-24', 30),
(367, 62, 1, 5040.00, 0.00, 0.00, '2026-03-24', 0, NULL, 40.00, 'repaid', 1, NULL, 1008.00, 0, NULL, 0, 0, 0, '2026-04-04 11:08:07', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-04 11:08:07', 'rolled over', '2026-04-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-03', 30),
(368, 62, 1, 10368.00, 0.00, 0.00, '2026-03-21', 0, NULL, 40.00, 'repaid', 1, NULL, 2073.60, 0, NULL, 0, 0, 0, '2026-04-04 11:10:14', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-04 11:10:14', 'rolled over', '2026-03-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-03-31', 30),
(369, 72, 1, 10000.00, 0.00, 0.00, '2026-04-05', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-05 15:43:36', '2026-08-06 13:11:46', NULL, 68, NULL, 1, 1, '2026-04-05 15:43:36', 'rolled over loan', '2026-04-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-15', 30),
(370, 29, 1, 24000.00, 0.00, 0.00, '2026-04-05', 0, NULL, 40.00, 'repaid', 1, NULL, 4800.00, 0, NULL, 0, 0, 0, '2026-04-08 06:06:39', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-08 06:06:39', '[21:48, 07/04/2026] Dennis Kibet: We will have to roll over\r\n[22:41, 07/04/2026] Nigel Cecil Otieno Loans Client: Niaje, nikama itabidi', '2026-04-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-15', 30),
(371, 53, 1, 1500.00, 0.00, 0.00, '2026-04-08', 0, NULL, 40.00, 'repaid', 1, NULL, 300.00, 0, NULL, 0, 0, 0, '2026-04-08 06:12:05', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-08 06:12:05', 'Bank to M-PESA transfer of KES 1,500.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3993UINQ7428. M-PESA Ref ID: UD86BBWAX7', '2026-04-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-18', 30),
(372, 11, 1, 5000.00, 0.00, 0.00, '2026-04-10', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-04-13 16:24:48', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'roll over for the next 10 days', '2026-04-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-20', 30),
(373, 53, 1, 3040.00, 0.00, 0.00, '2026-04-10', 0, NULL, 40.00, 'repaid', 1, NULL, 608.00, 0, NULL, 0, 0, 0, '2026-04-13 16:30:11', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-13 16:30:11', 'rolled over for the next 10 days', '2026-04-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-20', 30),
(374, 21, 2, 342732.00, 0.00, 0.00, '2026-04-03', 0, NULL, 40.00, 'repaid', 1, NULL, 68546.40, 0, NULL, 0, 0, 0, '2026-04-13 16:43:27', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-13 16:43:27', 'rolled over facility', '2026-05-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-03', 90),
(375, 62, 1, 18489.00, 0.00, 0.00, '2026-03-31', 0, NULL, 40.00, 'repaid', 1, NULL, 3697.80, 0, NULL, 0, 0, 0, '2026-04-13 17:18:24', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'roll over facility', '2026-04-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-10', 30),
(376, 62, 1, 22187.00, 0.00, 0.00, '2026-04-10', 0, NULL, 40.00, 'repaid', 1, NULL, 4437.36, 0, NULL, 0, 0, 0, '2026-04-13 17:24:58', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-04-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-20', 30),
(377, 64, 1, 10000.00, 0.00, 0.00, '2026-04-15', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-15 10:11:31', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-15 10:11:31', 'UDFAI12TXH Confirmed. You have received Ksh10,000.00 from IM BANK LIMITED- APP on 15/4/26 at 2:35 PM. New M-PESA balance is Ksh10,392.57. Buy goods with M-PESA.', '2026-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-25', 30),
(378, 67, 1, 7500.00, 0.00, 0.00, '2026-04-14', 0, NULL, 40.00, 'repaid', 1, NULL, 1500.00, 0, NULL, 0, 0, 0, '2026-04-15 10:20:49', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-15 10:20:49', 'Bank to M-PESA transfer of KES 7,500.00 to 254700742394 - MICHAEL NZUKA MUSYIMI successfully processed. Transaction Ref ID: 4049LFRF6379. M-PESA Ref ID: UDEOI142NU', '2026-04-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-24', 30),
(379, 68, 1, 10000.00, 0.00, 0.00, '2026-04-15', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-15 10:22:37', '2026-08-06 13:11:46', NULL, 48, 'Friend', 1, 1, '2026-04-15 10:22:37', 'Bank to M-PESA transfer of KES 10,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 4055ZDCU6256. M-PESA Ref ID: UDFBM13SLE', '2026-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-25', 30),
(380, 50, 13, 66000.00, 0.00, 0.00, '2026-03-20', 0, NULL, 40.00, 'repaid', 1, NULL, 39600.00, 0, NULL, 0, 0, 0, '2026-04-15 10:25:47', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'roll over facility for 3 months', '2026-06-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-20', 150),
(381, 72, 1, 10000.00, 0.00, 0.00, '2026-04-15', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-16 15:08:27', '2026-08-06 13:11:46', NULL, 68, 'Friend', 1, 1, '2026-04-16 15:08:27', 'rolled over loan', '2026-04-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-25', 30),
(382, 2, 1, 30000.00, 0.00, 0.00, '2026-04-09', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-04-17 18:14:40', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-04-17 18:14:40', 'rolled over facility', '2026-04-19', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-19', 30),
(383, 2, 5, 110797.00, 0.00, 0.00, '2026-04-09', 0, NULL, 40.00, 'repaid', 1, NULL, 33238.92, 0, NULL, 0, 0, 0, '2026-04-17 18:17:37', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'Rolled over', '2026-05-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-09', 90),
(384, 11, 1, 6000.00, 0.00, 0.00, '2026-04-20', 0, NULL, 40.00, 'repaid', 1, NULL, 1200.00, 0, NULL, 0, 0, 0, '2026-04-21 04:05:45', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-21 04:05:45', '[09:03, 21/04/2026] Dennis Kibet: We roll over?\r\n[09:04, 21/04/2026] Shady Kip Cheruiyot eCitizen: Yew sir, currently it\'s bad\r\n[09:04, 21/04/2026] Dennis Kibet: Sawa chief, you will sort when paid', '2026-04-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-30', 30),
(385, 68, 1, 20000.00, 0.00, 0.00, '2026-04-20', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2026-04-22 06:45:45', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 15,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 4102UYKB3384. M-PESA Ref ID: UDKBM1Q6KM\r\n\r\nPayable 30/4/2016 as KES 18,000', '2026-04-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-04-30', 30),
(386, 70, 1, 1000.00, 0.00, 0.00, '2026-04-22', 0, NULL, 40.00, 'repaid', 1, NULL, 200.00, 0, NULL, 0, 0, 0, '2026-04-22 07:00:21', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-22 07:00:21', 'Bank to M-PESA transfer of KES 1,000.00 to 0725408209 - nigel kimutai yegon successfully processed. Transaction Ref ID: 4114DTIN9494. M-PESA Ref ID: UDMIT1JYA4 \r\n\r\n10 DAYS 20% INTEREST FACILITY \r\nPayable on or before 1st May as KES 1200', '2026-05-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-02', 30),
(387, 2, 1, 10000.00, 0.00, 0.00, '2026-04-22', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-23 05:54:09', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-04-23 05:54:09', 'UDM9X1UI8J Confirmed. Ksh10,000.00 sent to Edward  Kipsanai 0710920629 on 22/4/26 at 12:57 PM.', '2026-05-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-02', 30),
(388, 58, 1, 20000.00, 0.00, 0.00, '2026-04-23', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-04-23 06:52:30', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 10,000.00 to 254722778298 - VIVIAN NEKESA SIMIYU successfully processed. Transaction Ref ID: 4124CXRR4189. M-PESA Ref ID: UDN321V2ZZ\r\n\r\n5 days 11k', '2026-05-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-03', 30),
(389, 53, 1, 5000.00, 0.00, 0.00, '2026-04-23', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-04-26 06:43:02', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-04-26 06:43:02', 'Bank to M-PESA transfer of KES 3,300.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4125JYFE4889. M-PESA Ref ID: UDN6B1PHVO\r\n\r\nPayable 3rd May 2026 as KES 3,960\r\n\r\nBank to M-PESA transfer of KES 1,700.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4127SODY1166. M-PESA Ref ID: UDN6B1RC19\r\n\r\n5k total', '2026-05-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-03', 30),
(390, 64, 1, 3000.00, 0.00, 0.00, '2026-04-25', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2026-04-27 05:00:23', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, '[18:36, 26/04/2026] Judy Kerebi: Hi\r\n[18:36, 26/04/2026] Dennis Kibet: Umemanage?\r\n[18:36, 26/04/2026] Judy Kerebi: Just roll it over\r\n[18:36, 26/04/2026] Judy Kerebi: Umemanage?\r\nNaaah', '2026-05-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-05', 30),
(391, 11, 1, 7200.00, 0.00, 0.00, '2026-04-30', 0, NULL, 40.00, 'repaid', 1, NULL, 1440.00, 0, NULL, 0, 0, 0, '2026-05-01 15:17:21', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-01 15:17:21', '[16:08, 30/04/2026] Dennis Kibet: Hey\r\n[16:08, 30/04/2026] Dennis Kibet: 7,200 due today\r\n[05:20, 01/05/2026] Shady Kip Cheruiyot eCitizen: Is it possible we roll over once again things are not good from my side, hii nikutafutie hiyo interest ya juu', '2026-05-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-10', 30),
(392, 68, 1, 5400.00, 0.00, 0.00, '2026-04-27', 0, NULL, 40.00, 'repaid', 1, NULL, 1080.00, 0, NULL, 0, 0, 0, '2026-05-01 15:31:20', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-01 15:31:20', 'rolled over', '2026-05-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-07', 30),
(393, 7, 2, 1800.00, 0.00, 0.00, '2026-04-25', 0, NULL, 40.00, 'repaid', 1, NULL, 360.00, 0, NULL, 0, 0, 0, '2026-05-01 15:52:39', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'rolled over', '2026-05-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-25', 90),
(394, 58, 1, 5000.00, 0.00, 0.00, '2026-04-25', 0, NULL, 40.00, 'repaid', 1, NULL, 1000.00, 0, NULL, 0, 0, 0, '2026-05-02 13:21:26', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-02 13:21:26', 'Please NEVER share your PIN, PASSWORD, any codes or CARD details with ANYONE! not even people who may claim to be bank staff. Ref:ABFFAF765026: Dear DENNIS, MPESA transfer of KES 5000 to SAMMY MWASHIGHADI MWAMBURI-0112952244 at 25-04-2026 01:39 PM was successful.MPESA Ref:UDP5B28S4C', '2026-05-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-05', 30),
(395, 29, 3, 35000.00, 0.00, 0.00, '2026-05-02', 0, NULL, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2026-05-02 14:43:56', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-02 14:43:56', 'Bank to M-PESA transfer of KES 35,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 4204OKRD2406. M-PESA Ref ID: UE2KB2NLQH\r\n\r\n14 days 20% to be paid on or befofr 16th May 2026 as KES 42,000', '2026-05-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-16', 42),
(396, 73, 3, 50000.00, 0.00, 0.00, '2026-05-02', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-05-05 10:06:22', '2026-08-06 13:11:46', NULL, 52, 'Friend', 1, 0, NULL, 'Bank to M-PESA transfer of KES 50,000.00 to 254705254257 - MARION CLARE CHEROP successfully processed. Transaction Ref ID: 4204WKPF2468. M-PESA Ref ID: UE28O30YKZ\r\n\r\nI Anita Nanyokie ID No 37489362 Pledge 50000 loan to be paid in 2 weeks and failure to pay will attract a 10% penalty daily until the facility is settled and should the facility go bad, recovery measures at your own cost will take effect', '2026-05-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-16', 42),
(397, 53, 1, 3000.00, 0.00, 0.00, '2026-05-04', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2026-05-05 10:16:53', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-05 10:16:53', 'Bank to M-PESA transfer of KES 3,000.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4221LRVG6821. M-PESA Ref ID: UE46B2ZWG1', '2026-05-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-14', 30),
(398, 53, 3, 7000.00, 0.00, 0.00, '2026-05-04', 0, NULL, 40.00, 'repaid', 1, NULL, 1400.00, 0, NULL, 0, 0, 0, '2026-05-05 10:19:13', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 6,000.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4223GMLX1416. M-PESA Ref ID: UE46B30ZNW\r\n\r\n2nd facility for 14 days', '2026-05-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-18', 42),
(399, 2, 16, 25000.00, 0.00, 0.00, '2026-05-04', 0, NULL, 40.00, 'repaid', 1, NULL, 6250.00, 0, NULL, 0, 0, 0, '2026-05-05 10:32:45', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'UE49X38M5M Confirmed. Ksh25,000.00 sent to Edward  Kipsanai 0710920629 on 4/5/26 at 11:17 AM.', '2026-05-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-11', 21),
(400, 64, 1, 3600.00, 0.00, 0.00, '2026-05-06', 0, NULL, 40.00, 'repaid', 1, NULL, 720.00, 0, NULL, 0, 0, 0, '2026-05-07 06:40:23', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-07 06:40:23', 'rolled over facility', '2026-05-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-16', 30),
(401, 52, 1, 75000.00, 0.00, 0.00, '2026-05-07', 0, NULL, 40.00, 'repaid', 1, NULL, 15000.00, 0, NULL, 0, 0, 0, '2026-05-08 07:17:31', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-08 07:17:31', 'Bank to M-PESA transfer of KES 75,000.00 to 254705254257 - MARION CLARE CHEROP successfully processed. Transaction Ref ID: 4247ZUJJ2691. M-PESA Ref ID: UE78O3LYYG\r\n\r\nFacility for 10 days 20% due on 17th May 2026', '2026-05-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-17', 30),
(402, 21, 2, 411279.00, 0.00, 0.00, '2026-05-03', 0, NULL, 40.00, 'repaid', 1, NULL, 82255.68, 0, NULL, 0, 0, 0, '2026-05-08 07:20:14', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over awaiting cash', '2026-06-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-03', 90),
(403, 70, 1, 1200.00, 0.00, 0.00, '2026-05-02', 0, NULL, 40.00, 'repaid', 1, NULL, 240.00, 0, NULL, 0, 0, 0, '2026-05-08 09:43:02', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-08 09:43:02', 'rolled over facility', '2026-05-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-12', 30),
(404, 26, 2, 120000.00, 0.00, 0.00, '2026-04-26', 0, NULL, 40.00, 'repaid', 1, NULL, 24000.00, 0, NULL, 0, 0, 0, '2026-05-15 07:25:06', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'inactivity so we rolled over', '2026-05-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-26', 90),
(405, 11, 1, 8640.00, 0.00, 0.00, '2026-05-10', 0, NULL, 40.00, 'repaid', 1, NULL, 1728.00, 0, NULL, 0, 0, 0, '2026-05-15 07:31:18', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-15 07:31:18', 'rolled over', '2026-05-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-20', 30),
(406, 9, 2, 87693.00, 0.00, 0.00, '2026-04-18', 0, NULL, 40.00, 'repaid', 1, NULL, 17538.51, 0, NULL, 0, 0, 0, '2026-05-15 07:40:27', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-05-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-18', 90),
(407, 70, 1, 1728.00, 0.00, 0.00, '2026-05-12', 0, NULL, 40.00, 'repaid', 1, NULL, 288.00, 0, NULL, 0, 0, 0, '2026-05-15 07:51:01', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-15 07:51:01', 'rolled over', '2026-05-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-22', 30),
(408, 2, 1, 71250.00, 0.00, 0.00, '2026-05-11', 0, NULL, 40.00, 'repaid', 1, NULL, 14250.00, 0, NULL, 0, 0, 0, '2026-05-15 07:54:36', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'rolled over', '2026-05-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-21', 30),
(409, 2, 5, 144036.00, 0.00, 0.00, '2026-05-09', 0, NULL, 40.00, 'repaid', 1, NULL, 43210.60, 0, NULL, 0, 0, 0, '2026-05-15 08:01:30', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'rolled over', '2026-06-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-09', 90),
(410, 62, 1, 15000.00, 0.00, 0.00, '2026-05-14', 0, NULL, 40.00, 'repaid', 1, NULL, 3000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:12:49', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 15,000.00 to 254799388138 - DOUGLAS IMBOYWA LUTOMIA successfully processed. Transaction Ref ID: 4305WBTS8934. M-PESA Ref ID: UEE1U49IYE\r\n\r\n10 days 20% Facility for 20 days payable on 3rd June 2025', '2026-05-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-24', 30),
(411, 75, 3, 100000.00, 0.00, 0.00, '2026-05-11', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:15:58', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Pesalink transfer of KES 100,000.00 to A/c 01116367542800-Dennis O Zereta on 11/05/2026 11:26 processed successfully.\r\nTransaction Ref ID:828122140763', '2026-05-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-25', 42),
(412, 74, 17, 35000.00, 0.00, 0.00, '2026-05-15', 0, NULL, 40.00, 'repaid', 1, NULL, 7000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:21:42', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 35,000.00 to 254728688805 - Diana Jerotich successfully processed. Transaction Ref ID: 4279YIQS7240. M-PESA Ref ID: UEBFR3O053', '2026-06-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-05', 63),
(413, 3, 1, 25000.00, 0.00, 0.00, '2026-05-11', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:46:29', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-15 08:46:28', 'Pesalink transfer of KES 25,000.00 to A/c 0780283328011-Isiro Agencies on 11/05/2026 20:13 processed successfully.\r\nTransaction Ref ID:297966502215\r\n\r\n10 days facility 20% interest 60% broker fees on interest. Payable on 21st May 2026', '2026-05-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-21', 30),
(414, 3, 3, 30000.00, 0.00, 0.00, '2026-05-13', 1, 5, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:48:02', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Pesalink transfer of KES 30,000.00 to A/c 0780283328011-Isiro Agencies on 13/05/2026 16:12 processed successfully.\r\nTransaction Ref ID:388220519542\r\n\r\nFacility for 14 days 20% interest 60% broker fees on interest. Total payable on 27th May 2026 KES 33,600.00', '2026-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-27', 42),
(415, 3, 1, 12000.00, 0.00, 0.00, '2026-05-14', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-05-15 08:49:42', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-15 08:49:42', 'Pesalink transfer of KES 10,000.00 to A/c 0780283328011-Isiro Agencies on 14/05/2026 21:12 processed successfully.\r\nTransaction Ref ID:643458978305\r\n\r\nFacility for 10 days 20% interest 60% broker fees on interest. Total payable on 24th May 2026 KES 11,200.00', '2026-05-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-24', 30),
(416, 53, 1, 1800.00, 0.00, 0.00, '2026-05-15', 0, NULL, 40.00, 'repaid', 1, NULL, 372.00, 0, NULL, 0, 0, 0, '2026-05-16 17:36:50', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-16 17:36:50', 'Niaje denno imekua ngumu kiasi tunaeza fanya rollover ndo nilipe yote this week ju payment yangu itaingia in-between hii wiki ndo nikue safe na penalties', '2026-05-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-25', 30),
(417, 64, 1, 4320.00, 0.00, 0.00, '2026-05-16', 0, NULL, 40.00, 'repaid', 1, NULL, 864.00, 0, NULL, 0, 0, 0, '2026-05-19 10:07:05', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-19 10:07:05', 'ROLLED Over facility', '2026-05-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-26', 30),
(418, 73, 1, 30000.00, 0.00, 0.00, '2026-05-17', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-05-19 10:11:55', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-19 10:11:55', 'Bank to M-PESA transfer of KES 30,000.00 to 0708530169 - ANITA SOINA NANYOKIE successfully processed. Transaction Ref ID: 4333KMXG4064. M-PESA Ref ID: UEHC14TQ1T', '2026-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-27', 30),
(419, 53, 1, 10000.00, 0.00, 0.00, '2026-05-17', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-05-19 10:14:47', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-05-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-27', 30),
(420, 9, 1, 105231.05, 0.00, 0.00, '2026-05-18', 0, NULL, 40.00, 'repaid', 1, NULL, 21046.21, 0, NULL, 0, 0, 0, '2026-05-19 10:19:03', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-19 10:19:03', 'ROLLED OVER', '2026-05-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-28', 30),
(421, 29, 1, 22000.00, 0.00, 0.00, '2026-05-16', 0, NULL, 40.00, 'repaid', 1, NULL, 4400.00, 0, NULL, 0, 0, 0, '2026-05-19 10:20:37', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-05-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-26', 30),
(422, 52, 1, 90000.00, 0.00, 0.00, '2026-05-16', 0, NULL, 40.00, 'repaid', 1, NULL, 18000.00, 0, NULL, 0, 0, 0, '2026-05-20 07:00:14', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-20 07:00:14', 'ROLLED OVER', '2026-05-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-26', 30),
(423, 48, 1, 3000.00, 0.00, 0.00, '2026-05-21', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2026-05-21 09:26:34', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-21 09:26:34', 'Bank to M-PESA transfer of KES 3,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 4365WIYG0039. M-PESA Ref ID: UELAL4X9AF\r\n\r\n10 days 20% and 10% daily penalties on outstanding amount after due date of 31st May 2026.\r\n\r\nPayable KES 3,600', '2026-05-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-31', 30),
(424, 64, 1, 5184.00, 0.00, 0.00, '2026-05-26', 0, NULL, 40.00, 'repaid', 1, NULL, 1036.80, 0, NULL, 0, 0, 0, '2026-05-27 05:53:43', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-27 05:53:43', 'Good morning, Kibet....Kindly  roll it over', '2026-06-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-05', 30),
(425, 70, 1, 1728.00, 0.00, 0.00, '2026-05-22', 0, NULL, 40.00, 'repaid', 1, NULL, 345.60, 0, NULL, 0, 0, 0, '2026-05-27 05:55:48', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-27 05:55:48', 'Rolled over', '2026-06-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-01', 30),
(426, 62, 1, 18000.00, 0.00, 0.00, '2026-05-24', 0, NULL, 40.00, 'repaid', 1, NULL, 3600.00, 0, NULL, 0, 0, 0, '2026-05-27 06:02:32', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-27 06:02:32', 'rolled over', '2026-06-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-03', 30),
(427, 2, 1, 85500.00, 0.00, 0.00, '2026-05-21', 0, NULL, 40.00, 'repaid', 1, NULL, 17100.00, 0, NULL, 0, 0, 0, '2026-05-27 06:10:07', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-05-27 06:10:07', 'rolled over', '2026-05-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-05-31', 30),
(428, 7, 2, 2160.00, 0.00, 0.00, '2026-05-25', 0, NULL, 40.00, 'repaid', 1, NULL, 432.00, 0, NULL, 0, 0, 0, '2026-05-27 06:14:46', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Rolled over', '2026-06-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-25', 90),
(429, 15, 15, 146412.95, 0.00, 0.00, '2026-05-27', 0, NULL, 40.00, 'disbursed', 2, NULL, 16105.90, 0, NULL, 0, 0, 0, '2026-05-28 06:27:50', '2026-08-31 08:23:31', NULL, NULL, NULL, NULL, 0, NULL, 'rolled over', '2026-11-27', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, '2026-08-06 15:12:50', 0, '2026-11-27', 150),
(430, 11, 1, 9000.00, 0.00, 0.00, '2026-05-27', 0, NULL, 40.00, 'repaid', 1, NULL, 1800.00, 0, NULL, 0, 0, 0, '2026-05-28 19:41:49', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 3,000.00 to 254724606690 - SHADRACK CHERUIYOT successfully processed. Transaction Ref ID: 4427RILA2427. M-PESA Ref ID: UESHD5YGSA\r\n\r\nDue 7th June 2026 as KES 3,600', '2026-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-06', 30),
(431, 53, 1, 3000.00, 0.00, 0.00, '2026-05-27', 0, NULL, 40.00, 'repaid', 1, NULL, 600.00, 0, NULL, 0, 0, 0, '2026-06-03 07:26:48', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-06-03 07:26:48', 'Rolled over + 1500 loan\r\nBank to M-PESA transfer of KES 1,500.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4438AIME0767. M-PESA Ref ID: UET6B5TQ81', '2026-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-06', 30),
(432, 2, 1, 102600.00, 0.00, 0.00, '2026-05-31', 0, NULL, 40.00, 'repaid', 1, NULL, 20520.00, 0, NULL, 0, 0, 0, '2026-06-03 07:34:05', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 0, NULL, 'rolled over', '2026-06-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-10', 30),
(433, 48, 1, 14000.00, 0.00, 0.00, '2026-05-30', 0, NULL, 40.00, 'repaid', 1, NULL, 2800.00, 0, NULL, 0, 0, 0, '2026-06-03 07:42:35', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-06-03 07:42:35', 'Bank to M-PESA transfer of KES 14,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 4443MMKK9478. M-PESA Ref ID: UEUAL5ZRUE\r\n\r\nDue in 10 days at 20% interest 16,800 payable on or before 9th June 2026', '2026-06-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-09', 30),
(434, 73, 1, 21000.00, 0.00, 0.00, '2026-05-27', 0, NULL, 40.00, 'repaid', 1, NULL, 4200.00, 0, NULL, 0, 0, 0, '2026-06-03 07:47:32', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-06-03 07:47:32', 'rolled over', '2026-06-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-06', 30),
(435, 70, 1, 2074.00, 0.00, 0.00, '2026-06-01', 0, NULL, 40.00, 'repaid', 1, NULL, 414.72, 0, NULL, 0, 0, 0, '2026-06-03 10:01:23', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-06-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-11', 30),
(436, 26, 2, 124000.00, 0.00, 0.00, '2026-05-26', 0, NULL, 40.00, 'disbursed', 1, NULL, 24800.00, 0, NULL, 0, 0, 0, '2026-06-03 10:16:03', '2026-08-06 13:11:46', NULL, NULL, NULL, 15, 1, '2026-06-03 10:16:03', 'ROLLED OVER', '2026-06-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-26', 90),
(437, 53, 1, 10600.00, 0.00, 0.00, '2026-06-06', 0, NULL, 40.00, 'repaid', 1, NULL, 2120.00, 0, NULL, 0, 0, 0, '2026-06-07 09:46:02', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-06-07 09:46:02', '[14:45, 07/06/2026] Dennis Kibet: Bank to M-PESA transfer of KES 10,000.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4505TVIV3259. M-PESA Ref ID: UF66B6QSCC\r\n[14:45, 07/06/2026] Dennis Kibet: 10 days 20% interest due 16/06/2026\r\n[14:45, 07/06/2026] Dennis Kibet: 600 haujaweka\r\n[14:45, 07/06/2026] Dennis Kibet: Nimeweka kwa 10k', '2026-06-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-16', 30),
(438, 11, 1, 13800.00, 0.00, 0.00, '2026-06-06', 0, NULL, 40.00, 'repaid', 1, NULL, 2760.00, 0, NULL, 0, 0, 0, '2026-06-08 07:28:55', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-06-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-16', 30),
(439, 73, 1, 17200.00, 0.00, 0.00, '2026-06-06', 0, NULL, 40.00, 'repaid', 1, NULL, 3440.00, 0, NULL, 0, 0, 0, '2026-06-08 07:31:15', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 1, '2026-06-08 07:31:15', 'rolled over', '2026-06-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-16', 30),
(440, 64, 1, 6220.80, 0.00, 0.00, '2026-06-05', 0, NULL, 40.00, 'repaid', 1, NULL, 1244.16, 0, NULL, 0, 0, 0, '2026-06-08 07:38:23', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-06-15', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-15', 30),
(441, 74, 17, 42000.00, 0.00, 0.00, '2026-06-05', 0, NULL, 40.00, 'repaid', 1, NULL, 8400.00, 0, NULL, 0, 0, 0, '2026-06-08 09:35:18', '2026-08-06 13:11:46', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-06-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-26', 63),
(442, 21, 2, 493534.10, 0.00, 0.00, '2026-06-03', 0, NULL, 40.00, 'repaid', 1, NULL, 98706.82, 0, NULL, 0, 0, 0, '2026-06-08 09:37:33', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-07-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-03', 90),
(443, 62, 1, 43200.00, 0.00, 0.00, '2026-06-03', 0, NULL, 40.00, 'repaid', 1, NULL, 4320.00, 0, NULL, 0, 0, 0, '2026-06-09 07:10:24', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-09 07:10:24', 'rolled over', '2026-06-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-13', 30),
(444, 62, 1, 25920.00, 0.00, 0.00, '2026-06-13', 0, NULL, 40.00, 'repaid', 1, NULL, 5184.00, 0, NULL, 0, 0, 0, '2026-06-14 03:15:53', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-14 03:15:53', 'roll over loan', '2026-06-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-23', 30),
(445, 2, 1, 310365.92, 0.00, 0.00, '2026-06-10', 0, NULL, 40.00, 'disbursed', 1, NULL, 62073.18, 0, NULL, 0, 0, 0, '2026-06-14 03:21:06', '2026-08-06 13:11:47', NULL, NULL, NULL, 15, 1, '2026-06-14 03:21:06', 'roll over to clear once', '2026-06-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-20', 30),
(446, 70, 1, 2488.32, 0.00, 0.00, '2026-06-11', 0, NULL, 40.00, 'repaid', 1, NULL, 497.66, 0, NULL, 0, 0, 0, '2026-06-14 03:26:46', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-14 03:26:46', 'ROLLED OVER', '2026-06-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-21', 30),
(447, 64, 1, 7465.00, 0.00, 0.00, '2026-06-15', 0, NULL, 40.00, 'repaid', 1, NULL, 1493.00, 0, NULL, 0, 0, 0, '2026-06-16 02:34:54', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-16 02:34:54', 'rolled over loan', '2026-06-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-25', 30),
(448, 11, 1, 16560.00, 0.00, 0.00, '2026-06-16', 0, NULL, 40.00, 'repaid', 1, NULL, 3312.00, 0, NULL, 0, 0, 0, '2026-06-17 06:57:31', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, '[11:15, 17/06/2026] Shady Kip Cheruiyot eCitizen: fayiaa, hatukupata dhoo unaeza roll over , kindly\r\n[11:54, 17/06/2026] Dennis Kibet: fayiaa, hatukupata dhoo unaeza roll over , kindly\r\nSawa', '2026-06-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-26', 30),
(449, 53, 1, 12720.00, 0.00, 0.00, '2026-06-16', 0, NULL, 40.00, 'repaid', 1, NULL, 2544.00, 0, NULL, 0, 0, 0, '2026-06-18 11:11:03', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-18 11:11:03', 'roll over loan', '2026-06-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-26', 30),
(450, 76, 1, 33600.00, 0.00, 0.00, '2026-06-20', 0, NULL, 40.00, 'repaid', 8, NULL, 108927.23, 6, NULL, 6, 6, 6, '2026-06-20 11:10:25', '2026-10-01 08:08:38', NULL, NULL, NULL, 1, 0, NULL, 'new loan facility', '2026-10-11', 0, NULL, 0, NULL, NULL, '2026-10-11 07:00:45', NULL, 0, NULL, 0, '2026-10-11', 30),
(451, 48, 1, 10000.00, 0.00, 0.00, '2026-06-10', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-06-21 09:19:31', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-21 09:19:31', 'Bank to M-PESA transfer of KES 10,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 4540EMLQ4038. M-PESA Ref ID: UFAAL79NHH', '2026-06-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-20', 30),
(452, 70, 1, 2986.00, 0.00, 0.00, '2026-06-21', 0, NULL, 40.00, 'repaid', 1, NULL, 597.20, 0, NULL, 0, 0, 0, '2026-06-25 08:40:23', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-25 08:40:23', 'ROLLED OVER', '2026-07-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-01', 30),
(453, 48, 1, 10000.00, 0.00, 0.00, '2026-06-24', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-06-25 08:42:18', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-25 08:42:18', 'UFO6O8SPHT Confirmed. KSH. 10,000 sent to Sharon Chemurgor,  via MySafaricom App on 24-06-2026 18:22. \r\n\r\n10 days 20% interest', '2026-07-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-04', 30),
(454, 3, 1, 50000.00, 0.00, 0.00, '2026-06-23', 0, NULL, 40.00, 'repaid', 1, NULL, 10000.00, 0, NULL, 0, 0, 0, '2026-06-25 08:43:53', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-25 08:43:53', 'Pesalink transfer of KES 50,000.00 to EQUITY BANK A/c 0780283328011 on 23/06/2026 09:39 processed successfully. Transaction Ref ID: 241310635800.\r\n\r\n10 days', '2026-07-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-03', 30),
(455, 3, 17, 10000.00, 0.00, 0.00, '2026-06-22', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-06-25 08:45:31', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-25 08:45:31', 'Bank to M-PESA transfer of KES 10,000.00 to 254727459357 - FRANCIS MUKHWANA OKWARA successfully processed. Transaction Ref ID: 4643QUOS5381. M-PESA Ref ID: UFMD88NRVB\r\n\r\n3 weeks', '2026-07-13', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-13', 63),
(456, 9, 2, 126277.30, 0.00, 0.00, '2026-05-18', 0, NULL, 40.00, 'repaid', 1, NULL, 25255.46, 0, NULL, 0, 0, 0, '2026-06-25 08:59:56', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'rolled over', '2026-06-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-06-18', 90),
(457, 11, 1, 30872.00, 0.00, 0.00, '2026-06-26', 0, NULL, 40.00, 'repaid', 1, NULL, 6174.40, 0, NULL, 0, 0, 0, '2026-06-29 04:43:16', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'ROLL OVER + Bank to M-PESA transfer of KES 5,000.00 to 254724606690 - SHADRACK CHERUIYOT successfully processed. Transaction Ref ID: 4680UJKH0648. M-PESA Ref ID: UFQHD9E6GZ', '2026-07-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-06', 30),
(458, 3, 1, 150000.00, 0.00, 0.00, '2026-06-30', 0, NULL, 40.00, 'repaid', 1, NULL, 40000.00, 0, NULL, 0, 0, 0, '2026-06-30 15:04:06', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-06-30 15:04:06', 'emergency assistance', '2026-07-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-10', 30),
(459, 53, 1, 15264.00, 0.00, 0.00, '2026-06-26', 0, NULL, 40.00, 'repaid', 1, NULL, 3052.80, 0, NULL, 0, 0, 0, '2026-07-01 11:41:33', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-01 11:41:33', 'ROLLED OVER', '2026-07-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-06', 30),
(460, 74, 17, 21400.00, 0.00, 0.00, '2026-06-26', 0, NULL, 40.00, 'repaid', 1, NULL, 4280.00, 0, NULL, 0, 0, 0, '2026-07-01 11:44:17', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-07-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-17', 63),
(461, 50, 13, 44352.00, 0.00, 0.00, '2026-06-20', 0, NULL, 40.00, 'disbursed', 2, NULL, 58752.00, 0, NULL, 0, 0, 0, '2026-07-01 11:49:51', '2026-10-09 18:24:22', NULL, NULL, NULL, 1, 1, '2026-07-01 11:49:51', 'ROLLED OVER', '2026-12-20', 0, '2026-08-06', 0, '2026-08-06 13:12:50', NULL, NULL, 'Default triggered: Loan overdue for 44.366086534155 days on 2026-08-06 15:12', 0, '2026-08-06 18:13:35', 0, '2026-12-20', 150),
(462, 9, 2, 218207.23, 0.00, 0.00, '2026-06-18', 0, NULL, 40.00, 'disbursed', 2, NULL, 66674.43, 0, NULL, 0, 0, 0, '2026-07-01 11:52:50', '2026-08-07 20:49:15', NULL, NULL, NULL, 1, 1, '2026-07-01 11:52:50', 'ROLLED OVER', '2026-08-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-18', 90),
(463, 21, 2, 1023392.30, 0.00, 0.00, '2026-07-03', 0, NULL, 40.00, 'disbursed', 3, NULL, 431151.38, 0, NULL, 0, 0, 0, '2026-07-03 12:35:47', '2026-09-04 12:10:50', NULL, NULL, NULL, 1, 1, '2026-07-03 12:35:47', 'ROLLED OVER', '2026-10-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-03', 90),
(464, 70, 1, 3583.20, 0.00, 0.00, '2026-07-01', 0, NULL, 40.00, 'repaid', 1, NULL, 716.64, 0, NULL, 0, 0, 0, '2026-07-04 16:33:20', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-04 16:33:20', 'ROLLED OVER', '2026-07-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-11', 30),
(465, 64, 2, 2749.60, 0.00, 0.00, '2026-06-25', 0, NULL, 40.00, 'forbearance', 2, NULL, 2341.52, 0, NULL, 0, 0, 0, '2026-07-04 16:37:27', '2026-09-03 10:59:57', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-07-25', 0, NULL, 0, NULL, NULL, '2026-10-03 10:59:57', NULL, 0, NULL, 0, '2026-07-25', 90),
(466, 84, 1, 20000.00, 0.00, 0.00, '2026-06-26', 0, NULL, 40.00, 'repaid', 1, NULL, 4000.00, 0, NULL, 0, 0, 0, '2026-07-04 16:41:09', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-04 16:41:09', 'Bank to M-PESA transfer of KES 20,000.00 to 0722559067 - BRIAN KIPLAGAT TANUI successfully processed. Transaction Ref ID: 4686EOJL2669. M-PESA Ref ID: UFRLJ9N1YY', '2026-07-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-06', 30),
(467, 48, 1, 30000.00, 0.00, 0.00, '2026-07-04', 0, NULL, 40.00, 'repaid', 1, NULL, 6000.00, 0, NULL, 0, 0, 0, '2026-07-04 16:45:52', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-04 16:45:52', 'Bank to M-PESA transfer of KES 30,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 4747XQNR1529. M-PESA Ref ID: UG4ALA02O5', '2026-07-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-14', 30),
(468, 3, 3, 100000.00, 0.00, 0.00, '2026-07-04', 0, NULL, 40.00, 'repaid', 1, NULL, 20000.00, 0, NULL, 0, 0, 0, '2026-07-04 16:47:45', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 100,000.00 to 254703731558 - MAKOKO NASENYA ANJELA successfully processed. Transaction Ref ID: 4746FPNN3173. M-PESA Ref ID: UG40NAAR1R', '2026-07-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-18', 42),
(469, 3, 1, 60000.00, 0.00, 0.00, '2026-07-04', 0, NULL, 40.00, 'repaid', 1, NULL, 12000.00, 0, NULL, 0, 0, 0, '2026-07-04 16:50:11', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-04 16:50:11', 'Pesalink transfer of KES 10,000.00 to EQUITY BANK A/c 0780283328011 on 04/07/2026 11:06 processed successfully. Transaction Ref ID: 718191439198.\r\n\r\nPesalink transfer of KES 50,000.00 to EQUITY BANK A/c 0780283328011 on 04/07/2026 11:05 processed successfully. Transaction Ref ID: 025301778612.\r\n\r\nFacility 10 days 12% interest \r\n\r\nPayable KES 67,200 on 14th July', '2026-07-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-14', 30),
(470, 7, 2, 2592.00, 0.00, 0.00, '2026-06-25', 0, NULL, 40.00, 'repaid', 1, NULL, 518.40, 0, NULL, 0, 0, 0, '2026-07-04 17:06:38', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-04 17:06:38', 'ROLLED OVER', '2026-07-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-25', 90),
(471, 51, 1, 60000.00, 0.00, 0.00, '2026-07-06', 0, NULL, 40.00, 'repaid', 1, NULL, 12000.00, 0, NULL, 0, 0, 0, '2026-07-06 18:05:28', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-06 18:05:28', 'Bank to M-PESA transfer of KES 60,000.00 to 254726471918 - KELVIN ROTICH successfully processed. Transaction Ref ID: 4766RIOG0469. M-PESA Ref ID: UG63XAM1SV\r\n\r\n10 days facility 20% payable on 16th July 2026 as KES 72,000', '2026-07-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-16', 30),
(472, 3, 1, 240000.00, 0.00, 0.00, '2026-07-10', 0, NULL, 40.00, 'repaid', 1, NULL, 48000.00, 0, NULL, 0, 0, 0, '2026-07-10 08:35:53', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-10 08:35:53', 'ROLLED OVER FACILITY', '2026-07-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-20', 30),
(473, 53, 1, 18316.80, 0.00, 0.00, '2026-07-06', 0, NULL, 40.00, 'repaid', 1, NULL, 3663.36, 0, NULL, 0, 0, 0, '2026-07-10 08:43:09', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-10 08:43:09', 'ROLLED OVER FACILITY', '2026-07-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-16', 30),
(474, 73, 1, 10000.00, 0.00, 0.00, '2026-07-13', 0, NULL, 40.00, 'repaid', 1, NULL, 2000.00, 0, NULL, 0, 0, 0, '2026-07-13 08:49:25', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-13 08:49:25', 'UGD6OAX82C Confirmed. KSH. 10,000 sent to ANITA NANYOKIE,  via MySafaricom App on 13-07-2026 13:35.', '2026-07-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-23', 30),
(475, 11, 1, 37046.40, 0.00, 0.00, '2026-07-06', 0, NULL, 40.00, 'repaid', 1, NULL, 7409.28, 0, NULL, 0, 0, 0, '2026-07-13 09:02:34', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-13 09:02:34', 'ROLLED OVER', '2026-07-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-16', 30),
(476, 70, 1, 5159.81, 0.00, 0.00, '2026-07-11', 0, NULL, 40.00, 'repaid', 1, NULL, 1031.96, 0, NULL, 0, 0, 0, '2026-07-13 09:05:16', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-13 09:05:16', 'ROLLED OVER', '2026-07-21', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-21', 30),
(477, 48, 3, 62208.00, 0.00, 0.00, '2026-07-14', 0, NULL, 40.00, 'forbearance', 3, NULL, 26208.00, 0, NULL, 0, 0, 0, '2026-07-16 06:04:14', '2026-09-03 11:01:58', NULL, NULL, NULL, 1, 1, '2026-07-16 06:04:14', 'ROLLED OVER', '2026-08-11', 0, NULL, 0, NULL, NULL, '2026-10-03 11:01:58', NULL, 0, NULL, 0, '2026-08-11', 42),
(478, 11, 1, 44455.68, 0.00, 0.00, '2026-07-16', 0, NULL, 40.00, 'repaid', 1, NULL, 8891.14, 0, NULL, 0, 0, 0, '2026-07-17 03:53:21', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-17 03:53:21', 'Rolled over facility', '2026-07-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-26', 30),
(479, 53, 1, 21980.16, 0.00, 0.00, '2026-07-16', 0, NULL, 40.00, 'repaid', 1, NULL, 4396.03, 0, NULL, 0, 0, 0, '2026-07-17 07:51:20', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-17 07:51:20', 'ROLLED OVER', '2026-07-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-26', 30),
(480, 51, 1, 32000.00, 0.00, 0.00, '2026-07-16', 0, NULL, 40.00, 'repaid', 1, NULL, 6400.00, 0, NULL, 0, 0, 0, '2026-07-18 15:12:18', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-07-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-26', 30),
(481, 3, 18, 288000.00, 0.00, 0.00, '2026-07-20', 0, NULL, 40.00, 'repaid', 1, NULL, 86400.00, 0, NULL, 0, 0, 0, '2026-07-20 09:15:48', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-07-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-30', 30),
(482, 31, 1, 70000.00, 0.00, 0.00, '2026-07-20', 0, NULL, 40.00, 'repaid', 1, NULL, 14000.00, 0, NULL, 0, 0, 0, '2026-07-21 08:28:04', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 0, NULL, 'Local Funds Transfer of KES 50,000.00 to I & M BANK LTD A/c 01005685246350 on 20/07/2026 19:13 processed successfully. Transaction Ref ID: 833201967079.\r\n10 days 20% facility 2 cycles', '2026-07-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-30', 30),
(483, 70, 1, 6191.80, 0.00, 0.00, '2026-07-21', 0, NULL, 40.00, 'repaid', 1, NULL, 1238.36, 0, NULL, 0, 0, 0, '2026-07-22 06:28:27', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-22 06:28:27', 'ROLLED OVER', '2026-07-31', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-07-31', 30),
(484, 3, 1, 25000.00, 0.00, 0.00, '2026-07-24', 0, NULL, 40.00, 'repaid', 1, NULL, 5000.00, 0, NULL, 0, 0, 0, '2026-07-25 12:12:51', '2026-08-06 13:11:47', NULL, NULL, NULL, 1, 1, '2026-07-25 12:12:51', 'Bank to M-PESA transfer of KES 25,000.00 to 254707486975 - Evelyn Mumbua Mutia successfully processed. Transaction Ref ID: 4921XQHA9734. M-PESA Ref ID: UGOCY0NR4H\r\n\r\nFacility for 10 days 20% interest 10% daily penalty fees on outstanding amounts after due date', '2026-08-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-03', 30),
(485, 73, 1, 4447.59, 0.00, 0.00, '2026-07-25', 0, NULL, 40.00, 'repaid', 3, NULL, 3617.52, 0, NULL, 0, 0, 0, '2026-07-25 12:18:10', '2026-08-25 12:03:43', NULL, NULL, NULL, 1, 1, '2026-07-25 12:18:10', 'ROLLED OVER', '2026-08-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-24', 30),
(486, 53, 2, 29651.43, 0.00, 0.00, '2026-07-26', 0, NULL, 40.00, 'forbearance', 2, NULL, 5275.24, 0, NULL, 0, 0, 0, '2026-07-28 10:03:55', '2026-09-03 11:03:31', NULL, NULL, NULL, 1, 1, '2026-07-28 10:03:55', 'ROLLED OVER', '2026-08-26', 0, NULL, 0, NULL, NULL, '2026-10-08 11:03:31', NULL, 19, '2026-08-06 17:16:10', 0, '2026-08-26', 90),
(487, 51, 3, 38400.00, 0.00, 0.00, '2026-07-26', 0, NULL, 40.00, 'repaid', 1, NULL, 7680.00, 0, NULL, 0, 0, 0, '2026-07-30 07:05:13', '2026-08-07 16:53:40', NULL, NULL, NULL, 1, 1, '2026-07-30 07:05:13', 'ROLLED OVER FACILITY', '2026-08-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-05', 30),
(488, 3, 18, 1011681.80, 0.00, 0.00, '2026-07-30', 0, NULL, 40.00, 'disbursed', 5, NULL, 757281.00, 0, NULL, 0, 0, 0, '2026-08-03 14:20:14', '2026-08-31 08:55:36', NULL, NULL, NULL, 1, 1, '2026-08-03 14:20:14', 'ROLLED OVER', '2026-09-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, '2026-08-06 15:12:50', 0, '2026-09-18', 30),
(489, 11, 1, 55646.82, 0.00, 0.00, '2026-07-26', 0, NULL, 40.00, 'repaid', 1, NULL, 11129.36, 0, NULL, 0, 0, 0, '2026-08-03 15:53:24', '2026-08-07 16:55:08', NULL, NULL, NULL, 1, 0, NULL, 'ROLLED OVER', '2026-08-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-05', 30),
(490, 31, 1, 132000.00, 0.00, 0.00, '2026-07-30', 0, NULL, 40.00, 'disbursed', 1, NULL, 24800.00, 0, NULL, 0, 0, 0, '2026-08-03 15:58:34', '2026-08-11 16:09:37', NULL, NULL, NULL, 1, 1, '2026-08-11 16:09:37', 'ROLL OVER LOAN', '2026-08-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 2, '2026-08-06 15:12:50', 0, '2026-08-09', 30);
INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `processing_fee_rate`, `total_processing_fees`, `borrow_date`, `broker_status`, `partner_id`, `broker_rate`, `status`, `cycle`, `original_amount`, `capitalized_interest`, `grace_period_days`, `grace_period_end_date`, `grace_days_balance`, `grace_days_earned`, `grace_days_used`, `created_at`, `updated_at`, `deleted_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`, `is_non_performing`, `default_date`, `days_in_default`, `default_triggered_at`, `recovery_started_at`, `forbearance_until`, `recovery_notes`, `days_overdue`, `last_overdue_check`, `default_triggered`, `calculated_due_date`, `npl_trigger_threshold`) VALUES
(491, 70, 1, 15407.18, 0.00, 0.00, '2026-07-31', 0, NULL, 40.00, 'disbursed', 4, NULL, 7977.02, 0, NULL, 0, 0, 0, '2026-08-03 16:25:33', '2026-08-31 08:31:25', NULL, NULL, NULL, 1, 1, '2026-08-03 16:25:33', 'ROLLED OVERed', '2026-09-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, '2026-08-06 15:12:50', 0, '2026-09-09', 30),
(492, 11, 1, 150908.69, 0.00, 0.00, '2026-08-05', 0, NULL, 40.00, 'forbearance', 4, 66776.18, 76932.51, 0, NULL, 0, 0, 0, '2026-08-07 18:56:21', '2026-09-07 06:07:49', NULL, NULL, NULL, 1, 1, '2026-08-07 18:56:20', '66776.18 + 5000 + 1000', '2026-10-22', 0, NULL, 0, NULL, NULL, '2026-10-22 06:07:49', NULL, 0, NULL, 0, '2026-10-22', 30),
(493, 3, 1, 6000.00, 0.00, 0.00, '2026-08-06', 0, NULL, 40.00, 'repaid', 1, 6000.00, 1200.00, 0, NULL, 0, 0, 0, '2026-08-11 15:41:25', '2026-08-19 04:51:56', NULL, NULL, NULL, 1, 1, '2026-08-11 15:41:25', 'Pesalink transfer of KES 11,600.00 to EQUITY BANK A/c 0780283328011 on 07/08/2026 11:33 processed successfully. Transaction Ref ID: 631609340677.\r\n\r\n5,600 settled facility \r\n\r\n6,000 Shaq facility 10 days at 12% payable 6,720', '2026-08-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-16', 0),
(494, 75, 3, 69120.00, 0.00, 0.00, '2026-08-07', 0, NULL, 40.00, 'repaid', 3, 40000.00, 29120.00, 7, '2026-09-18', 0, 7, 7, '2026-08-11 15:53:01', '2026-09-25 09:48:19', NULL, NULL, NULL, 1, 1, '2026-08-11 15:53:01', 'Bank to M-PESA transfer of KES 40,000.00 to 254728064636 - DENNIS OSORO ZERETA successfully processed. Transaction Ref ID: 5042WPHI6999. M-PESA Ref ID: UH75R28VNB\r\n\r\nFacility for 10 days 20% payable 17th August', '2026-09-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-18', 42),
(495, 3, 1, 5000.00, 0.00, 0.00, '2026-08-09', 0, NULL, 40.00, 'repaid', 2, 5000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-08-11 15:58:25', '2026-08-19 11:29:42', NULL, NULL, NULL, 1, 1, '2026-08-11 15:58:25', 'UH93K2Z5RP Confirmed. Ksh5,000.00 sent to CHRISTINE  GIKENYI 0714116482 on 9/8/26 at 4:30 PM.\r\nObiri\r\nFacility 5000\r\nInterest 12% 600 broker 8% 400\r\n\r\nTotal pay out 5,600 to Amazonblue capital in 19th August 2026', '2026-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-29', 0),
(496, 3, 1, 12000.00, 0.00, 0.00, '2026-08-08', 0, NULL, 40.00, 'repaid', 1, 12000.00, 2400.00, 1, '2026-08-20', 0, 0, 1, '2026-08-11 16:00:52', '2026-08-20 07:42:11', NULL, NULL, NULL, NULL, 1, '2026-08-11 16:00:52', 'Bank to M-PESA transfer of KES 12,000.00 to 254719134823 - NATHANIEL AHAO ONONO successfully processed. Transaction Ref ID: 5042UGAC1109. M-PESA Ref ID: UH7NA25GIJ\r\n\r\nFacility to be cleared in 10 days 20%(broker fee 112%)\r\n\r\nPayable 960 to Obiri\r\nPayable 12,440 to Amazonblue Capital', '2026-08-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-18', 0),
(497, 97, 1, 5000.00, 0.00, 0.00, '2026-08-19', 0, NULL, 40.00, 'repaid', 1, 5000.00, 1000.00, 0, NULL, 0, 0, 0, '2026-08-24 08:13:46', '2026-08-24 08:15:26', NULL, NULL, NULL, 1, 1, '2026-08-24 08:13:45', 'Bank to M-PESA transfer of KES 5,000.00 to 254723639684 - MOTURI KAYAGA ELIAS successfully processed. Transaction Ref ID: 5144AWPI3425. M-PESA Ref ID: UHJ1X3M5UX\r\n\r\nFacility for 10 days at 12% broker rate. Payable KES 5,600 on August 29th', '2026-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-29', 30),
(498, 94, 3, 50000.00, 0.00, 0.00, '2026-08-23', 0, NULL, 40.00, 'repaid', 1, 50000.00, 10000.00, 0, NULL, 0, 0, 0, '2026-08-24 08:17:13', '2026-09-07 07:50:05', NULL, NULL, NULL, 1, 1, '2026-08-24 08:17:13', 'Bank to M-PESA transfer of KES 50,000.00 to 254703731558 - MAKOKO NASENYA ANJELA successfully processed. Transaction Ref ID: 5085FFVN6168. M-PESA Ref ID: UHC0N2TWW7', '2026-09-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-02', 42),
(499, 95, 2, 50000.00, 0.00, 0.00, '2026-08-12', 0, NULL, 40.00, 'repaid', 1, 50000.00, 10000.00, 2, '2026-09-14', 0, 0, 2, '2026-08-24 08:19:59', '2026-09-14 14:10:38', NULL, NULL, NULL, 1, 1, '2026-08-24 08:19:59', 'Bank to M-PESA transfer of KES 50,000.00 to 254706926230 - Cynthia Wanjuki Kaweru successfully processed. Transaction Ref ID: 5085SNZP7491. M-PESA Ref ID: UHCCL2TEDO\r\n\r\nTopping up the 50,000 tomorrow \r\n\r\nFacility 30% 1 month. Payable 12th September as 130,000', '2026-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-12', 90),
(500, 11, 5, 16900.00, 0.00, 0.00, '2026-08-20', 0, NULL, 40.00, 'disbursed', 2, 10000.00, 6900.00, 0, NULL, 0, 0, 0, '2026-08-24 10:53:49', '2026-09-23 05:31:21', NULL, NULL, NULL, 1, 1, '2026-08-24 10:53:49', 'Bank to M-PESA transfer of KES 10,000.00 to 254724606690 - SHADRACK KIPKORIR CHERUIYOT successfully processed. Transaction Ref ID: 5155NUEI1646. M-PESA Ref ID: UHKHD3RXAI\r\n\r\nFacility 1 month at 30%', '2026-10-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-20', 0),
(501, 98, 1, 10000.00, 0.00, 0.00, '2026-08-24', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-08-24 10:56:49', '2026-09-03 13:21:03', NULL, NULL, NULL, 1, 1, '2026-08-24 10:56:49', 'UHOMY3NZEZ Confirmed. Ksh5,000.00 sent to CHRISTINE  GIKENYI 0714116482 on 24/8/26 at 10:26 AM.\r\nUHORJ3G9LJ Confirmed. Ksh5,000.00 sent to CHRISTINE  GIKENYI 0714116482 on 24/8/26 at 1:58 PM.', '2026-09-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-03', 0),
(502, 99, 1, 12000.00, 0.00, 0.00, '2026-08-23', 0, NULL, 40.00, 'repaid', 2, 10000.00, 4000.00, 0, NULL, 0, 0, 0, '2026-08-24 11:00:20', '2026-09-04 11:12:51', NULL, NULL, NULL, 1, 1, '2026-08-24 11:00:20', 'UHNMY3MYT8 Confirmed. Ksh10,000.00 sent to Juliet  Akayi 0706785038 on 23/8/26 at 9:16 PM.', '2026-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-12', 0),
(503, 84, 1, 20000.00, 0.00, 0.00, '2026-08-23', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-08-24 11:17:00', '2026-09-01 12:04:21', NULL, NULL, NULL, 1, 1, '2026-08-24 11:16:59', 'Bank to M-PESA transfer of KES 20,000.00 to 0722559067 - BRIAN KIPLAGAT TANUI successfully processed. Transaction Ref ID: 5178IKXR5460. M-PESA Ref ID: UHNLJ4B4PH\r\n\r\nFacility 10 days 20% interest payable KES 24000 1st September', '2026-09-02', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-02', 30),
(504, 62, 1, 41206.58, 0.00, 0.00, '2026-08-13', 0, NULL, 40.00, 'disbursed', 7, 11500.00, 29706.58, 0, NULL, 0, 0, 0, '2026-08-24 11:53:26', '2026-10-13 03:44:07', NULL, NULL, NULL, 1, 1, '2026-08-24 11:53:26', 'Bank to M-PESA transfer of KES 11,500.00 to 254799388138 - DOUGLAS IMBOYWA LUTOMIA successfully processed. Transaction Ref ID: 5091ZTOV3699. M-PESA Ref ID: UHD1U2O9FZ\r\n\r\nFacility for 10 days 20% interest and 10% daily on outstanding amounts after due date', '2026-10-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-22', 0),
(505, 29, 1, 26400.00, 0.00, 0.00, '2026-08-31', 0, NULL, 40.00, 'repaid', 3, 20000.00, 10400.00, 0, NULL, 0, 0, 0, '2026-08-31 08:30:12', '2026-09-25 10:03:47', NULL, NULL, NULL, 1, 1, '2026-08-31 08:30:12', 'Bank to M-PESA transfer of KES 20,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 5246CCKI8407. M-PESA Ref ID: UHVKB4IFAQ', '2026-09-30', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-30', 30),
(506, 40, 2, 9600.00, 0.00, 0.00, '2026-08-31', 0, NULL, 40.00, 'disbursed', 2, 8000.00, 3200.00, 0, NULL, 0, 0, 0, '2026-08-31 08:34:47', '2026-10-03 13:18:26', NULL, NULL, NULL, 1, 1, '2026-08-31 08:34:47', 'Bank to M-PESA transfer of KES 10,450.00 to 0758229006 - IAN OTIENO  OKOTH  successfully processed. Transaction Ref ID: 5245HNFG8643. M-PESA Ref ID: UHVC24BS0U', '2026-11-01', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-11-01', 0),
(507, 100, 1, 5000.00, 0.00, 0.00, '2026-08-28', 0, NULL, 40.00, 'repaid', 1, 5000.00, 1000.00, 0, NULL, 0, 0, 0, '2026-08-31 08:43:17', '2026-08-31 08:52:21', NULL, NULL, NULL, 1, 1, '2026-08-31 08:43:17', 'Bank to M-PESA transfer of KES 5,000.00 to 254703988016 - ERICK KIIYA MUTUKU successfully processed. Transaction Ref ID: 5216TFND6933. M-PESA Ref ID: UHROQ4DA7F', '2026-09-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-07', 0),
(508, 52, 1, 37500.00, 0.00, 0.00, '2026-08-28', 0, NULL, 40.00, 'repaid', 1, 37500.00, 7500.00, 0, NULL, 0, 0, 0, '2026-08-31 09:11:18', '2026-09-09 09:13:39', NULL, NULL, NULL, 1, 1, '2026-08-31 09:11:18', 'UHS6O478QA Confirmed. KSH. 30,000 sent to MARION CHEROP,  via MySafaricom App on 28-08-2026 09:15.', '2026-09-10', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-10', 30),
(509, 102, 1, 6000.00, 0.00, 0.00, '2026-08-30', 0, NULL, 40.00, 'repaid', 1, 6000.00, 1200.00, 0, NULL, 0, 0, 0, '2026-08-31 13:49:59', '2026-09-09 17:03:05', NULL, NULL, NULL, 1, 1, '2026-08-31 13:49:59', 'Bank to M-PESA transfer of KES 6,000.00 to 254725575799 - VICTOR OBIRI OSIEMO successfully processed. Transaction Ref ID: 5241AMUV7360. M-PESA Ref ID: UHVBD52RVZ\r\n\r\nFacility 10 days 20pc payable on 9th September', '2026-09-09', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-09', 0),
(510, 75, 2, 274520.00, 0.00, 0.00, '2026-09-03', 0, NULL, 40.00, 'disbursed', 2, 300000.00, 94504.00, 0, NULL, 0, 0, 0, '2026-09-03 07:13:51', '2026-10-15 07:02:51', NULL, NULL, NULL, 1, 1, '2026-09-03 07:13:51', 'Local Funds Transfer of KES 30,000.00 to I & M BANK LTD A/c 01403853516350 on 03/09/2026 08:15 processed successfully. Transaction Ref ID: 981188631990.', '2026-11-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-03', 63),
(511, 23, 19, 100000.00, 0.00, 0.00, '2026-05-29', 0, NULL, 40.00, 'disbursed', 1, 100000.00, 50000.00, 0, NULL, 0, 0, 0, '2026-09-03 07:21:02', '2026-09-30 13:22:25', NULL, NULL, NULL, 1, 1, '2026-09-03 07:21:02', 'UET6O5QG5M Confirmed. Ksh50,000.00 sent to M-PESA  GLOBAL for account 255758556562 on 29/5/26 at 10:45 AM New M-PESA balance is Ksh107.34. Transaction cost, Ksh0.00.Amount you can transact within the day is 450,000.00. Download My OneApp on https://saf.cx/kWQpy\r\n\r\nDear customer, you have successfully sent Ksh. 49000.00 to JOSEPH KAAYA in Tanzania. Kindly share transaction UI16O4QJBR with recipient. For assistance, Call: 100 or 200 or Email: customercare@safaricom.co.ke\r\n\r\n960k Tzs. More cash coming', '2026-08-29', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-08-29', 0),
(512, 84, 1, 20000.00, 0.00, 0.00, '2026-09-04', 0, NULL, 40.00, 'repaid', 1, 20000.00, 4000.00, 0, NULL, 0, 0, 0, '2026-09-04 11:15:32', '2026-09-14 16:51:44', NULL, NULL, NULL, 1, 1, '2026-09-04 11:15:32', 'Bank to M-PESA transfer of KES 20,000.00 to 0722559067 - BRIAN KIPLAGAT TANUI successfully processed. Transaction Ref ID: 5282YNTU6981. M-PESA Ref ID: UI4LJ5PNXK', '2026-09-14', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-14', 0),
(513, 100, 1, 103680.00, 0.00, 0.00, '2026-09-04', 0, NULL, 40.00, 'disbursed', 4, 50000.00, 43680.00, 0, NULL, 0, 0, 0, '2026-09-04 11:18:31', '2026-10-05 09:13:49', NULL, NULL, NULL, 1, 1, '2026-09-04 11:18:31', 'Bank to M-PESA transfer of KES 25,000.00 to 254703988016 - ERICK KIIYA MUTUKU successfully processed. Transaction Ref ID: 5282XBFF6427. M-PESA Ref ID: UI4OQ584YO', '2026-10-17', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-17', 30),
(514, 94, 5, 50000.00, 0.00, 0.00, '2026-08-12', 0, NULL, 40.00, 'repaid', 1, 50000.00, 15000.00, 0, NULL, 0, 0, 0, '2026-09-07 07:51:30', '2026-09-14 07:25:21', NULL, NULL, NULL, 1, 1, '2026-09-07 07:51:30', '1st facility', '2026-09-12', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-12', 0),
(515, 52, 1, 75600.00, 0.00, 0.00, '2026-09-12', 0, NULL, 40.00, 'disbursed', 4, 45000.00, 45360.00, 0, NULL, 0, 0, 0, '2026-09-09 09:14:25', '2026-10-12 08:06:28', NULL, NULL, NULL, 1, 1, '2026-09-09 09:14:25', 'Bank to M-PESA transfer of KES 30,000.00 to 254705254257 - MARION CLARE CHEROP successfully processed. Transaction Ref ID: 5309NGJG5645. M-PESA Ref ID: UI78O5YX1P', '2026-10-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-22', 0),
(516, 104, 1, 24480.00, 0.00, 0.00, '2026-09-12', 0, NULL, 40.00, 'repaid', 3, 20000.00, 12880.00, 0, NULL, 0, 0, 0, '2026-09-11 05:51:57', '2026-10-02 10:00:43', NULL, NULL, NULL, 1, 1, '2026-09-11 05:51:57', 'pending receivables', '2026-10-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-11', 30),
(517, 41, 14, 20000.00, 0.00, 0.00, '2026-09-12', 0, NULL, 40.00, 'repaid', 1, 20000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-09-14 07:37:51', '2026-09-18 13:59:49', NULL, NULL, NULL, 1, 1, '2026-09-14 07:37:51', 'Pesalink transfer of KES 5,000.00 to CYNTHIA WANJIRU MUHIA at STANBIC BANK A/c 0100006578378 on 12/09/2026 11:42 processed successfully. Transaction Ref ID: 728113386117.', '2026-09-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-22', 30),
(518, 73, 1, 10000.00, 0.00, 0.00, '2026-09-14', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-09-14 14:08:56', '2026-09-25 09:28:37', NULL, NULL, NULL, 1, 1, '2026-09-14 14:08:56', 'Bank to M-PESA transfer of KES 10,000.00 to 254708530169 - ANITA SOINA NANYOKIE successfully processed. Transaction Ref ID: 5371RDKT0555. M-PESA Ref ID: UIEC16UO1L', '2026-09-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-24', 0),
(519, 3, 1, 10000.00, 0.00, 0.00, '2026-09-11', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-09-14 14:22:31', '2026-09-25 09:34:17', NULL, NULL, NULL, 1, 1, '2026-09-14 14:22:31', 'Bank to M-PESA transfer of KES 1,000.00 to 254703836128 - SHAQUILL ONEILL ODUK successfully processed. Transaction Ref ID: 5343BLII9290. M-PESA Ref ID: UIBLP5O8T7\r\n\r\nYour transaction of Kshs. 9000.0  has been credited to  254703836128  SHAQUILL ONEILL ODUK. Ref.  EQA2B3538E82181. MPESA Ref.  UICLP5QO57 on  12/09/2026 at 0:01:55.', '2026-09-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-22', 30),
(520, 84, 14, 36000.00, 0.00, 0.00, '2026-09-15', 0, NULL, 40.00, 'repaid', 2, 30000.00, 6000.00, 0, NULL, 0, 0, 0, '2026-09-15 17:57:17', '2026-09-30 10:06:59', NULL, 71, 'Business Partner', 1, 1, '2026-09-15 17:57:17', 'Bank to M-PESA transfer of KES 30,000.00 to 0722559067 - BRIAN KIPLAGAT TANUI successfully processed. Transaction Ref ID: 5379FQPK2634. M-PESA Ref ID: UIFLJ72G2X', '2026-10-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-25', 30),
(521, 63, 1, 5000.00, 0.00, 0.00, '2026-09-15', 0, NULL, 40.00, 'repaid', 1, 5000.00, 1000.00, 0, NULL, 0, 0, 0, '2026-09-15 17:58:42', '2026-09-23 03:40:54', NULL, NULL, NULL, 1, 1, '2026-09-15 17:58:42', 'Bank to M-PESA transfer of KES 5,000.00 to 254716893824 - MORRIS HAFARE SEGELAN successfully processed. Transaction Ref ID: 5379GDZE2677. M-PESA Ref ID: UIFCQ6WN8D', '2026-09-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-25', 0),
(522, 102, 1, 4000.00, 0.00, 0.00, '2026-09-16', 0, NULL, 40.00, 'repaid', 1, 2000.00, 400.00, 0, NULL, 0, 0, 0, '2026-09-16 07:07:42', '2026-09-28 08:42:39', NULL, NULL, NULL, 1, 1, '2026-09-16 07:07:42', 'Bank to M-PESA transfer of KES 2,000.00 to 254725575799 - VICTOR OBIRI OSIEMO successfully processed. Transaction Ref ID: 5380EXFQ4215. M-PESA Ref ID: UIFBD6ZUKD', '2026-09-26', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-26', 30),
(523, 24, 1, 14400.00, 0.00, 0.00, '2026-09-18', 1, 8, 40.00, 'disbursed', 2, 10000.00, 4400.00, 0, NULL, 0, 0, 0, '2026-09-18 14:03:42', '2026-10-09 18:10:17', NULL, NULL, NULL, 1, 1, '2026-09-18 14:03:42', 'Bank to M-PESA transfer of KES 10,000.00 to 254727665808 - STEPHEN OMWENGA successfully processed. Transaction Ref ID: 5399WSLM3604. M-PESA Ref ID: UII7G6UYMM', '2026-10-08', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-08', 0),
(524, 3, 1, 10000.00, 0.00, 0.00, '2026-09-17', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-09-18 14:05:54', '2026-09-28 08:41:47', NULL, NULL, NULL, 1, 1, '2026-09-18 14:05:54', 'Bank to M-PESA transfer of KES 10,000.00 to 254726088540 - Samson Ojuma Odwali successfully processed. Transaction Ref ID: 5394STZV9925. M-PESA Ref ID: UIHGH76JTR', '2026-09-28', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-09-28', 30),
(525, 21, 1, 2000.00, 0.00, 0.00, '2026-09-23', 0, NULL, 40.00, 'repaid', 1, 2000.00, 400.00, 0, NULL, 0, 0, 0, '2026-09-23 06:17:17', '2026-09-30 10:06:14', NULL, NULL, NULL, 1, 1, '2026-09-23 06:17:17', 'Bank to M-PESA transfer of KES 2,000.00 to 254717465550 - DAVID IHAJI ISABWA successfully processed. Transaction Ref ID: 5444VWSI8612. M-PESA Ref ID: UINGZ7DWS3', '2026-10-03', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-03', 0),
(526, 3, 1, 25000.00, 0.00, 0.00, '2026-09-24', 0, NULL, 40.00, 'repaid', 1, 25000.00, 5000.00, 0, NULL, 0, 0, 0, '2026-09-25 09:37:40', '2026-10-03 13:36:54', NULL, NULL, NULL, 1, 1, '2026-09-25 09:37:40', 'Pesalink transfer of KES 20,000.00 to ISIRO AGENCIES at EQUITY BANK A/c 0780283328011 on 24/09/2026 18:48 processed successfully. Transaction Ref ID: 976022556995.', '2026-10-04', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-04', 0),
(527, 3, 1, 1000.00, 0.00, 0.00, '2026-09-20', 0, NULL, 40.00, 'repaid', 1, 1000.00, 200.00, 0, NULL, 0, 0, 0, '2026-09-25 10:18:30', '2026-09-28 08:52:56', NULL, NULL, NULL, 1, 1, '2026-09-25 10:18:30', 'Bank to M-PESA transfer of KES 1,000.00 to 254727459357 - FRANCIS MUKHWANA OKWARA successfully processed. Transaction Ref ID: 5423QIKQ0240. M-PESA Ref ID: UIKD873T5N', '2026-10-05', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-05', 30),
(528, 3, 1, 10000.00, 0.00, 0.00, '2026-09-26', 0, NULL, 40.00, 'repaid', 1, 10000.00, 0.00, 0, NULL, 0, 0, 0, '2026-09-26 14:50:00', '2026-09-28 08:44:47', NULL, NULL, NULL, 1, 1, '2026-09-26 14:50:00', 'Pesalink transfer of KES 15,000.00 to ISIRO AGENCIES at EQUITY BANK A/c 0780283328011 on 25/09/2026 18:13 processed successfully. Transaction Ref ID: 500582912466.', '2026-10-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-06', 0),
(529, 63, 1, 17280.00, 0.00, 0.00, '2026-09-26', 0, NULL, 40.00, 'disbursed', 2, 12000.00, 5280.00, 0, NULL, 0, 0, 0, '2026-09-26 14:51:46', '2026-10-07 08:50:16', NULL, NULL, NULL, 1, 1, '2026-09-26 14:51:46', 'Bank to M-PESA transfer of KES 12,000.00 to 254716893824 - MORRIS HAFARE SEGELAN successfully processed. Transaction Ref ID: 5464LBAC0477. M-PESA Ref ID: UIQCQ89ML0', '2026-10-16', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-16', 0),
(530, 106, 1, 10000.00, 0.00, 0.00, '2026-09-26', 0, NULL, 40.00, 'repaid', 1, 10000.00, 2000.00, 0, NULL, 0, 0, 0, '2026-09-28 08:29:05', '2026-10-09 07:42:46', NULL, 100, NULL, 1, 1, '2026-09-28 08:29:05', 'Bank to M-PESA transfer of KES 10,000.00 to 254703988016 - ERICK KIIYA MUTUKU successfully processed. Transaction Ref ID: 5474QDKZ5845. M-PESA Ref ID: UIQOQ7WTQX', '2026-10-06', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-06', 30),
(531, 107, 1, 9000.00, 0.00, 0.00, '2026-09-27', 0, NULL, 40.00, 'repaid', 1, 9000.00, 1800.00, 0, NULL, 0, 0, 0, '2026-09-28 08:34:46', '2026-10-03 13:23:20', NULL, 2, 'Friend', 1, 1, '2026-09-28 08:34:46', 'Bank to M-PESA transfer of KES 5,000.00 to 254702981402 - JAMAL LOMU successfully processed. Transaction Ref ID: 5482OPZQ2442. M-PESA Ref ID: UIRCZ7OOCO\r\n\r\nBank to M-PESA transfer of KES 4,000.00 to 254702981402 - JAMAL LOMU successfully processed. Transaction Ref ID: 5484LTRF0571. M-PESA Ref ID: UIRCZ7QE29', '2026-10-07', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-07', 0),
(532, 71, 1, 72000.00, 0.00, 0.00, '2026-09-30', 0, NULL, 40.00, 'disbursed', 2, 50000.00, 22000.00, 0, NULL, 0, 0, 0, '2026-09-30 12:19:58', '2026-10-13 06:30:28', NULL, NULL, NULL, 1, 1, '2026-09-30 12:19:58', 'Local Funds Transfer of KES 50,000.00 to YVONNE JEMUTAI ROTICH  KES Acc at I & M BANK LTD A/c 01702707633450 on 30/09/2026 17:16 processed successfully. Transaction Ref ID: 140421570155.\n\nFacility 5 days 10% payable KES 55,000 on 5th October', '2026-10-20', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-20', 19),
(533, 29, 1, 15000.00, 0.00, 0.00, '2026-10-01', 0, NULL, 40.00, 'repaid', 1, 15000.00, 3000.00, 0, NULL, 0, 0, 0, '2026-10-03 13:26:07', '2026-10-12 06:12:15', NULL, NULL, NULL, 1, 1, '2026-10-03 13:26:07', 'Bank to M-PESA transfer of KES 7,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 5516OLGC1997. M-PESA Ref ID: UJ1KB898O0', '2026-10-11', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-11', 0),
(534, 73, 1, 30000.00, 0.00, 0.00, '2026-10-08', 0, NULL, 40.00, 'disbursed', 1, 30000.00, 6000.00, 0, NULL, 0, 0, 0, '2026-10-08 09:30:53', '2026-10-08 09:34:07', NULL, NULL, NULL, 1, 1, '2026-10-08 09:30:53', 'Bank to M-PESA transfer of KES 20,000.00 to 254708530169 - ANITA SOINA NANYOKIE successfully processed. Transaction Ref ID: 5576ILZV9028. M-PESA Ref ID: UJ8C19RUH4', '2026-10-18', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-18', 0),
(535, 108, 1, 30000.00, 0.00, 0.00, '2026-10-12', 1, 5, 40.00, 'disbursed', 1, 30000.00, 6000.00, 0, NULL, 0, 0, 0, '2026-10-12 08:39:36', '2026-10-12 08:39:58', NULL, NULL, NULL, 1, 1, '2026-10-12 08:39:36', 'Bank to M-PESA transfer of KES 30,000.00 to 254742273253 - Priska Dianga Mbuya successfully processed. Transaction Ref ID: 5610ZJJE7860. M-PESA Ref ID: UJCLHA0LJA.\r\n\r\nFacility 10 days 20% interest 40% broker fees payable KES 33,600', '2026-10-22', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-22', 0),
(536, 29, 1, 20000.00, 0.00, 0.00, '2026-10-13', 0, NULL, 40.00, 'disbursed', 1, 20000.00, 4000.00, 0, NULL, 0, 0, 0, '2026-10-13 06:34:21', '2026-10-13 06:34:51', NULL, NULL, NULL, 1, 1, '2026-10-13 06:34:21', 'Bank to M-PESA transfer of KES 20,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 5618ATKG9067. M-PESA Ref ID: UJDKB9P7YP.', '2026-10-23', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-23', 0),
(537, 110, 1, 100000.00, 0.00, 0.00, '2026-10-14', 0, NULL, 40.00, 'disbursed', 1, 100000.00, 20000.00, 0, NULL, 0, 0, 0, '2026-10-15 06:58:30', '2026-10-15 06:59:45', NULL, NULL, NULL, 1, 1, '2026-10-15 06:58:30', 'Bank to M-PESA transfer of KES 17,400.00 to 254724542452 - BRIAN OKINYI successfully processed. Transaction Ref ID: 5627WBBD5146. M-PESA Ref ID: UJEICA8KRD.', '2026-10-24', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-24', 0),
(538, 102, 1, 1000.00, 0.00, 0.00, '2026-10-15', 0, NULL, 40.00, 'disbursed', 1, 1000.00, 200.00, 0, NULL, 0, 0, 0, '2026-10-15 07:04:16', '2026-10-15 07:04:38', NULL, NULL, NULL, 1, 1, '2026-10-15 07:04:16', 'Bank to M-PESA transfer of KES 1,000.00 to 254725575799 - VICTOR OBIRI OSIEMO successfully processed. Transaction Ref ID: 5634TPWM8100. M-PESA Ref ID: UJFBDALK63.', '2026-10-25', 0, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, '2026-10-25', 0);

-- --------------------------------------------------------

--
-- Table structure for table `loan_agreement_sections`
--

CREATE TABLE `loan_agreement_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `section_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `is_editable` tinyint(1) DEFAULT 1,
  `status` varchar(50) DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_agreement_templates`
--

CREATE TABLE `loan_agreement_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_cycles`
--

CREATE TABLE `loan_cycles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `cycle_number` int(11) NOT NULL DEFAULT 1,
  `previous_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_capitalized` decimal(15,2) NOT NULL DEFAULT 0.00,
  `new_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('active','completed','defaulted','repaid') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_cycles`
--

INSERT INTO `loan_cycles` (`id`, `loan_id`, `cycle_number`, `previous_balance`, `interest_capitalized`, `new_balance`, `interest_rate`, `start_date`, `due_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 0.00, 24000.00, 144000.00, 20.00, '2025-03-07', '2025-03-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(2, 2, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-03-07', '2025-03-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(3, 3, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-03-28', '2025-04-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(4, 4, 1, 0.00, 7000.00, 42000.00, 20.00, '2025-03-31', '2025-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(5, 5, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-03-03', '2025-04-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(6, 6, 1, 0.00, 2600.00, 15600.00, 20.00, '2025-04-05', '2025-05-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(7, 7, 1, 0.00, 400.00, 2400.00, 20.00, '2025-04-10', '2025-04-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(8, 8, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-04-12', '2025-04-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(9, 9, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-04-17', '2025-04-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(10, 10, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-04-16', '2025-04-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:44'),
(11, 11, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-04-08', '2025-04-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(12, 12, 1, 0.00, 5400.00, 32400.00, 20.00, '2025-04-17', '2025-04-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(13, 13, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-04-13', '2025-04-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(14, 14, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-03-25', '2025-04-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(15, 15, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-03-25', '2025-04-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(16, 16, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-04-01', '2025-04-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(17, 17, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-04-12', '2025-04-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(18, 18, 1, 0.00, 7000.00, 42000.00, 20.00, '2025-03-31', '2025-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(19, 19, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-04-07', '2025-04-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(20, 20, 1, 0.00, 6600.00, 39600.00, 20.00, '2025-04-15', '2025-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(21, 21, 1, 0.00, 8400.00, 50400.00, 20.00, '2025-04-15', '2025-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(22, 22, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-03-03', '2025-03-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(23, 23, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-02-26', '2025-03-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(24, 24, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-03-08', '2025-04-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(25, 25, 1, 0.00, 2200.00, 13200.00, 20.00, '2025-03-10', '2025-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(26, 26, 1, 0.00, 120.00, 720.00, 20.00, '2025-04-21', '2025-05-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(27, 27, 1, 0.00, 400.00, 2400.00, 20.00, '2025-03-15', '2025-04-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(28, 28, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-04-04', '2025-04-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(29, 29, 1, 0.00, 2200.00, 13200.00, 20.00, '2025-04-04', '2025-05-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(30, 30, 1, 0.00, 1200.00, 7200.00, 20.00, '2025-04-04', '2025-05-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(31, 31, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-04-07', '2025-04-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(32, 32, 1, 0.00, 5800.00, 34800.00, 20.00, '2025-04-04', '2025-05-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(33, 33, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-03-07', '2025-04-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(34, 34, 1, 0.00, 1800.00, 10800.00, 20.00, '2025-03-07', '2025-04-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(35, 35, 1, 0.00, 3600.00, 21600.00, 20.00, '2025-04-18', '2025-04-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(36, 36, 1, 0.00, 8800.00, 52800.00, 20.00, '2025-03-21', '2025-03-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(37, 37, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-04-25', '2025-05-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(38, 38, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-04-24', '2025-05-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(39, 39, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-04-27', '2025-05-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(40, 40, 1, 0.00, 3120.00, 18720.00, 20.00, '2025-05-06', '2025-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(41, 41, 1, 0.00, 1600.00, 9600.00, 20.00, '2025-04-27', '2025-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(42, 45, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-04-29', '2025-05-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(43, 46, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-03', '2025-05-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(44, 48, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-05-05', '2025-05-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(45, 49, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-06', '2025-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(46, 50, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-05-06', '2025-05-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(47, 51, 1, 0.00, 3120.00, 18720.00, 20.00, '2025-05-04', '2025-05-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(48, 52, 1, 0.00, 1600.00, 9600.00, 20.00, '2025-05-06', '2025-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(49, 54, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-05-07', '2025-05-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(50, 55, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-05-07', '2025-05-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(51, 56, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-05-07', '2025-05-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(52, 57, 1, 0.00, 9000.00, 54000.00, 20.00, '2025-05-10', '2025-05-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(53, 59, 1, 0.00, 15600.00, 93600.00, 20.00, '2025-05-12', '2025-05-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(54, 60, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-14', '2025-05-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(55, 61, 1, 0.00, 14400.00, 86400.00, 20.00, '2025-05-15', '2025-05-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(56, 64, 1, 0.00, 3744.00, 22464.00, 20.00, '2025-05-15', '2025-06-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(57, 65, 1, 0.00, 2600.00, 15600.00, 20.00, '2025-05-14', '2025-05-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(58, 66, 1, 0.00, 500.00, 3000.00, 20.00, '2025-05-14', '2025-05-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(59, 68, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-05-17', '2025-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(60, 69, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-19', '2025-06-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(61, 70, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-20', '2025-06-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(62, 71, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-05-26', '2025-06-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(63, 72, 1, 0.00, 5568.00, 33408.00, 20.00, '2025-05-26', '2025-06-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(64, 73, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-05-24', '2025-06-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(65, 74, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-05-26', '2025-06-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(66, 75, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-05-27', '2025-06-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(67, 76, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-05-27', '2025-06-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(68, 77, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-05-29', '2025-06-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(69, 78, 1, 0.00, 95000.00, 195000.00, 95.00, '2025-05-29', '2025-08-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(70, 79, 1, 0.00, 7000.00, 42000.00, 20.00, '2025-05-29', '2025-06-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(71, 80, 1, 0.00, 9000.00, 39000.00, 30.00, '2025-05-30', '2025-06-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(72, 82, 1, 0.00, 9000.00, 39000.00, 30.00, '2025-05-31', '2025-07-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(73, 83, 1, 0.00, 1640.00, 9840.00, 20.00, '2025-05-29', '2025-06-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(74, 84, 1, 0.00, 7000.00, 27000.00, 35.00, '2025-06-02', '2025-06-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(75, 85, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-06-03', '2025-06-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(76, 86, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-06-03', '2025-06-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(77, 87, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-06-04', '2025-06-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(78, 88, 1, 0.00, 5600.00, 33600.00, 20.00, '2025-06-06', '2025-06-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(79, 89, 1, 0.00, 1656.00, 9936.00, 20.00, '2025-06-08', '2025-06-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(80, 90, 1, 0.00, 7920.00, 47520.00, 20.00, '2025-06-09', '2025-06-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(81, 91, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-06-14', '2025-06-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(82, 92, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-06-13', '2025-06-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(83, 93, 1, 0.00, 3400.00, 20400.00, 20.00, '2025-06-13', '2025-06-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(84, 94, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-06-12', '2025-06-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(85, 95, 1, 0.00, 452.80, 2716.80, 20.00, '2025-06-16', '2025-07-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(86, 96, 1, 0.00, 16224.00, 97344.00, 20.00, '2025-06-19', '2025-06-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(87, 97, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-06-18', '2025-07-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(88, 98, 1, 0.00, 5600.00, 33600.00, 20.00, '2025-06-20', '2025-07-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(89, 99, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-06-21', '2025-07-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(90, 100, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-06-22', '2025-07-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(91, 101, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-06-24', '2025-07-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(92, 102, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-06-24', '2025-07-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(93, 103, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-06-27', '2025-07-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(94, 104, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-06-29', '2025-07-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(95, 105, 1, 0.00, 24000.00, 144000.00, 20.00, '2025-06-30', '2025-07-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(96, 106, 1, 0.00, 946.40, 5678.40, 20.00, '2025-06-18', '2025-06-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(97, 107, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-06-30', '2025-07-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(98, 108, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-05', '2025-07-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(99, 109, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-07-05', '2025-07-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(100, 110, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-07', '2025-07-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(101, 111, 1, 0.00, 1600.00, 9600.00, 20.00, '2025-07-07', '2025-07-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(102, 112, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-08', '2025-07-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(103, 113, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-08', '2025-07-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(104, 114, 1, 0.00, 1200.00, 7200.00, 20.00, '2025-07-10', '2025-07-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(105, 115, 1, 0.00, 29203.20, 126547.20, 30.00, '2025-06-30', '2025-07-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(106, 116, 1, 0.00, 11700.00, 50700.00, 30.00, '2025-07-02', '2025-08-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(107, 117, 1, 0.00, 24000.00, 144000.00, 20.00, '2025-07-11', '2025-07-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(108, 118, 1, 0.00, 400.00, 2400.00, 20.00, '2025-07-12', '2025-07-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(109, 119, 1, 0.00, 200.00, 1200.00, 20.00, '2025-07-14', '2025-07-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(110, 120, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-07-15', '2025-07-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(111, 121, 1, 0.00, 14000.00, 84000.00, 20.00, '2025-07-15', '2025-07-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(112, 122, 1, 0.00, 2880.00, 17280.00, 20.00, '2025-07-15', '2025-07-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(113, 123, 1, 0.00, 8580.00, 51480.00, 20.00, '2025-06-30', '2025-07-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(114, 124, 1, 0.00, 11325.60, 67953.60, 20.00, '2025-07-14', '2025-07-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(115, 125, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-17', '2025-07-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(116, 126, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-07-16', '2025-07-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(117, 127, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-07-17', '2025-07-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(118, 128, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-07-18', '2025-07-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(119, 129, 1, 0.00, 1440.00, 8640.00, 20.00, '2025-07-21', '2025-07-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(120, 130, 1, 0.00, 3456.00, 20736.00, 20.00, '2025-07-25', '2025-08-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(121, 131, 1, 0.00, 800.00, 4800.00, 20.00, '2025-07-20', '2025-07-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(122, 132, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-07-29', '2025-08-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(123, 134, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-07-30', '2025-08-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(124, 135, 1, 0.00, 480.00, 2880.00, 20.00, '2025-07-29', '2025-08-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(125, 136, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-07-28', '2025-08-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(126, 137, 1, 0.00, 7500.00, 45000.00, 20.00, '2025-07-30', '2025-08-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(127, 138, 1, 0.00, 9000.00, 54000.00, 20.00, '2025-08-01', '2025-09-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(128, 139, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-08-01', '2025-09-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(129, 140, 1, 0.00, 600.00, 3600.00, 20.00, '2025-08-02', '2025-08-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(130, 141, 1, 0.00, 1200.00, 7200.00, 20.00, '2025-08-04', '2025-08-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(131, 142, 1, 0.00, 600.00, 3600.00, 20.00, '2025-07-27', '2025-08-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(132, 143, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-07-30', '2025-08-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(133, 144, 1, 0.00, 60000.00, 360000.00, 20.00, '2025-08-06', '2025-08-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(134, 145, 1, 0.00, 600.00, 3600.00, 20.00, '2025-08-06', '2025-09-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(135, 146, 1, 0.00, 3280.00, 19680.00, 20.00, '2025-08-08', '2025-08-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(136, 147, 1, 0.00, 13590.72, 81544.32, 20.00, '2025-07-29', '2025-08-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(137, 148, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-08-09', '2025-08-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(138, 149, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-08-09', '2025-08-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(139, 150, 1, 0.00, 25309.44, 151856.64, 20.00, '2025-07-31', '2025-08-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(140, 151, 1, 0.00, 13309.00, 79854.00, 20.00, '2025-08-12', '2025-08-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(141, 152, 1, 0.00, 15210.00, 65910.00, 30.00, '2025-08-03', '2025-09-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(142, 156, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-08-22', '2025-09-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(143, 158, 1, 0.00, 320.00, 1920.00, 20.00, '2025-08-06', '2025-08-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(144, 159, 1, 0.00, 1200.00, 7200.00, 20.00, '2025-08-15', '2025-08-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(145, 160, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-08-19', '2025-08-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(146, 161, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-08-15', '2025-09-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(147, 162, 1, 0.00, 384.00, 2304.00, 20.00, '2025-08-17', '2025-08-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(148, 163, 1, 0.00, 3936.00, 23616.00, 20.00, '2025-08-19', '2025-08-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(149, 164, 1, 0.00, 3600.00, 21600.00, 20.00, '2025-08-20', '2025-08-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(150, 165, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-08-22', '2025-09-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(151, 166, 1, 0.00, 1440.00, 8640.00, 20.00, '2025-08-25', '2025-09-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(152, 167, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-08-28', '2025-09-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(153, 168, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-08-30', '2025-09-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(154, 169, 1, 0.00, 8400.00, 50400.00, 20.00, '2025-08-14', '2025-08-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(155, 170, 1, 0.00, 3000.00, 33000.00, 10.00, '2025-09-01', '2025-10-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(156, 171, 1, 0.00, 10728.00, 64368.00, 20.00, '2025-09-02', '2025-10-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(157, 172, 1, 0.00, 3600.00, 21600.00, 20.00, '2025-08-31', '2025-09-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(158, 173, 1, 0.00, 1440.00, 8640.00, 20.00, '2025-09-05', '2025-09-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(159, 174, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-09-08', '2025-09-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(160, 175, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-09-02', '2025-09-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(161, 176, 1, 0.00, 4723.20, 28339.20, 20.00, '2025-08-30', '2025-09-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(162, 177, 1, 0.00, 10120.00, 60720.00, 20.00, '2025-08-29', '2025-09-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(163, 178, 1, 0.00, 19773.00, 85683.00, 30.00, '2025-09-04', '2025-10-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(164, 179, 1, 0.00, 5668.00, 34008.00, 20.00, '2025-09-10', '2025-09-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(165, 180, 1, 0.00, 25000.00, 75000.00, 50.00, '2025-09-12', '2025-12-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(166, 181, 1, 0.00, 8000.00, 48000.00, 20.00, '2025-09-10', '2025-09-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(167, 182, 1, 0.00, 4320.00, 25920.00, 20.00, '2025-09-11', '2025-09-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(168, 183, 1, 0.00, 3600.00, 21600.00, 20.00, '2025-09-02', '2025-09-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(169, 184, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-09-13', '2025-09-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(170, 185, 1, 0.00, 4320.00, 25920.00, 20.00, '2025-09-13', '2025-09-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(171, 186, 1, 0.00, 75000.00, 325000.00, 30.00, '2025-09-13', '2025-12-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(172, 187, 1, 0.00, 8000.00, 48000.00, 20.00, '2025-09-15', '2025-09-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(173, 188, 1, 0.00, 200.00, 1200.00, 20.00, '2025-09-08', '2025-10-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:15'),
(174, 189, 1, 0.00, 27200.00, 163200.00, 20.00, '2025-09-19', '2025-10-10', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-09-15 18:07:12'),
(175, 190, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-09-21', '2025-10-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(176, 191, 1, 0.00, 6801.60, 40809.60, 20.00, '2025-09-21', '2025-10-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(177, 192, 1, 0.00, 8000.00, 48000.00, 20.00, '2025-09-26', '2025-10-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(178, 193, 1, 0.00, 35600.00, 213600.00, 20.00, '2025-09-29', '2025-10-20', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-09-15 18:03:48'),
(179, 194, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-09-27', '2025-10-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(180, 195, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-10-02', '2025-10-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(181, 196, 1, 0.00, 1700.00, 18700.00, 10.00, '2025-10-01', '2025-11-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(182, 197, 1, 0.00, 2300.00, 25300.00, 10.00, '2025-10-02', '2025-11-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(183, 198, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-10-01', '2025-10-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(184, 199, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-09-24', '2025-10-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(185, 200, 1, 0.00, 2880.00, 17280.00, 20.00, '2025-10-04', '2025-10-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(186, 201, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-10-03', '2025-10-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(187, 202, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-10-05', '2025-10-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(188, 203, 1, 0.00, 10873.60, 65241.60, 20.00, '2025-10-02', '2025-11-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(189, 204, 1, 0.00, 3000.00, 18000.00, 20.00, '2025-10-06', '2025-10-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(190, 205, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-10-08', '2025-10-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(191, 206, 1, 0.00, 240.00, 1440.00, 20.00, '2025-10-08', '2025-11-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(192, 207, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-10-13', '2025-10-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(193, 208, 1, 0.00, 11167.00, 67002.00, 20.00, '2025-09-30', '2025-11-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(194, 209, 1, 0.00, 6161.92, 36971.52, 20.00, '2025-10-01', '2025-10-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(195, 210, 1, 0.00, 7394.40, 44366.40, 20.00, '2025-10-11', '2025-10-21', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-10-12 16:51:28'),
(196, 211, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-10-15', '2025-10-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(197, 212, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-10-15', '2025-10-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(198, 213, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-10-16', '2025-10-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(199, 214, 1, 0.00, 14400.00, 86400.00, 20.00, '2025-10-21', '2025-10-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(200, 215, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-10-23', '2025-11-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(201, 216, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-10-24', '2025-11-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(202, 217, 1, 0.00, 10000.00, 60000.00, 20.00, '2025-10-31', '2025-11-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:45'),
(203, 218, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-10-25', '2025-11-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(204, 219, 1, 0.00, 740.00, 4440.00, 20.00, '2025-10-27', '2025-11-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(205, 220, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-10-24', '2025-11-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(206, 221, 1, 0.00, 11530.00, 126830.00, 10.00, '2025-10-27', '2025-11-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(207, 222, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-10-27', '2025-11-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(208, 223, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-10-27', '2025-11-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(209, 224, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-10-27', '2025-11-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(210, 225, 1, 0.00, 30000.00, 130000.00, 30.00, '2025-10-29', '2025-11-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(211, 226, 1, 0.00, 5610.00, 24310.00, 30.00, '2025-11-02', '2026-02-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(212, 227, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-11-04', '2025-11-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(213, 228, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-11-03', '2025-11-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(214, 229, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-11-07', '2025-11-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(215, 230, 1, 0.00, 600.00, 3600.00, 20.00, '2025-11-07', '2025-11-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(216, 231, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-11-06', '2025-11-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(217, 232, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-11-10', '2025-11-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(218, 233, 1, 0.00, 7048.32, 42289.92, 20.00, '2025-11-03', '2025-12-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(219, 234, 1, 0.00, 13200.00, 79200.00, 20.00, '2025-11-01', '2025-11-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(220, 235, 1, 0.00, 15840.00, 95040.00, 20.00, '2025-11-10', '2025-11-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(221, 236, 1, 0.00, 7200.00, 43200.00, 20.00, '2025-11-03', '2025-11-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(222, 237, 1, 0.00, 8640.00, 51840.00, 20.00, '2025-11-13', '2025-11-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(223, 238, 1, 0.00, 2400.00, 14400.00, 20.00, '2025-11-13', '2025-11-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(224, 239, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-11-18', '2025-11-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(225, 240, 1, 0.00, 2880.00, 17280.00, 20.00, '2025-11-16', '2025-11-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(226, 241, 1, 0.00, 2000.00, 12000.00, 20.00, '2025-11-18', '2025-12-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(227, 242, 1, 0.00, 6000.00, 36000.00, 20.00, '2025-11-18', '2025-12-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(228, 243, 1, 0.00, 540.00, 3240.00, 20.00, '2025-11-17', '2025-11-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(229, 244, 1, 0.00, 120000.00, 720000.00, 20.00, '2025-11-22', '2025-12-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(230, 245, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-11-22', '2025-12-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(231, 246, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-11-21', '2025-12-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(232, 247, 1, 0.00, 19008.00, 114048.00, 20.00, '2025-11-20', '2025-11-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(233, 248, 1, 0.00, 2880.00, 17280.00, 20.00, '2025-11-23', '2025-12-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(234, 249, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-11-28', '2025-12-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(235, 250, 1, 0.00, 7609.80, 134439.80, 6.00, '2025-11-27', '2026-02-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(236, 251, 1, 0.00, 8768.00, 52608.00, 20.00, '2025-11-23', '2025-12-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(237, 252, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-12-01', '2025-12-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(238, 253, 1, 0.00, 22809.60, 136857.60, 20.00, '2025-11-30', '2025-12-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(239, 254, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-11-24', '2025-12-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(240, 255, 1, 0.00, 536.00, 3216.00, 20.00, '2025-11-27', '2025-12-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(241, 256, 1, 0.00, 30000.00, 80000.00, 60.00, '2025-12-06', '2026-03-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(242, 257, 1, 0.00, 8458.00, 50748.00, 20.00, '2025-12-03', '2026-01-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(243, 258, 1, 0.00, 39000.00, 169000.00, 30.00, '2025-11-29', '2025-12-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(244, 259, 1, 0.00, 7521.60, 45129.60, 20.00, '2025-12-03', '2025-12-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(245, 260, 1, 0.00, 5000.00, 30000.00, 20.00, '2025-12-09', '2025-12-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(246, 261, 1, 0.00, 1756.00, 10536.00, 20.00, '2025-12-03', '2025-12-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(247, 262, 1, 0.00, 20000.00, 120000.00, 20.00, '2025-12-28', '2026-01-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(248, 263, 1, 0.00, 2107.20, 12643.20, 20.00, '2025-12-13', '2025-12-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(249, 264, 1, 0.00, 2528.64, 15171.84, 20.00, '2025-12-23', '2026-01-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(250, 265, 1, 0.00, 9025.92, 54155.52, 20.00, '2025-12-13', '2025-12-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(251, 266, 1, 0.00, 10831.10, 64986.60, 20.00, '2025-12-23', '2026-01-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(252, 267, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-12-22', '2026-01-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(253, 268, 1, 0.00, 1000.00, 6000.00, 20.00, '2025-12-26', '2026-01-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(254, 269, 1, 0.00, 144000.00, 864000.00, 20.00, '2025-12-22', '2026-01-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(255, 270, 1, 0.00, 16000.00, 96000.00, 20.00, '2025-12-29', '2026-01-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(256, 271, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-01-06', '2026-02-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(257, 272, 1, 0.00, 4000.00, 24000.00, 20.00, '2025-12-26', '2026-01-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(258, 273, 1, 0.00, 3034.37, 18206.21, 20.00, '2026-01-02', '2026-01-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(259, 274, 1, 0.00, 8000.00, 48000.00, 20.00, '2026-01-09', '2026-01-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(260, 275, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-01-09', '2026-02-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(261, 276, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-01-09', '2026-01-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(262, 277, 1, 0.00, 7000.00, 42000.00, 20.00, '2026-01-12', '2026-01-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(263, 278, 1, 0.00, 30000.00, 130000.00, 30.00, '2025-12-22', '2026-03-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(264, 279, 1, 0.00, 50700.00, 219700.00, 30.00, '2025-12-31', '2026-01-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(265, 280, 1, 0.00, 10149.60, 60897.60, 20.00, '2026-01-03', '2026-02-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(266, 281, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-01-16', '2026-01-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(267, 282, 1, 0.00, 50000.00, 300000.00, 20.00, '2026-01-21', '2026-01-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(268, 283, 1, 0.00, 1600.00, 9600.00, 20.00, '2026-01-22', '2026-02-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(269, 285, 1, 0.00, 3000.00, 33000.00, 10.00, '2026-01-26', '2026-01-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(270, 286, 1, 0.00, 1600.00, 9600.00, 20.00, '2026-01-29', '2026-02-08', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(271, 287, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-01-26', '2026-02-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(272, 288, 1, 0.00, 72800.00, 436800.00, 20.00, '2026-01-22', '2026-02-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(273, 289, 1, 0.00, 60000.00, 360000.00, 20.00, '2026-01-31', '2026-02-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(274, 290, 1, 0.00, 10000.00, 110000.00, 10.00, '2026-02-05', '2026-02-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(275, 291, 1, 0.00, 19668.00, 85228.00, 30.00, '2026-02-09', '2026-03-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(276, 292, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-02-08', '2026-02-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(277, 293, 1, 0.00, 15120.00, 267120.00, 6.00, '2026-02-05', '2026-05-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(278, 299, 1, 0.00, 3000.00, 53000.00, 6.00, '2026-06-09', '2026-09-09', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-09-07 06:32:47'),
(279, 300, 1, 0.00, 0.20, 1.20, 20.00, '2026-02-09', '2026-03-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(280, 301, 1, 0.00, 0.20, 1.20, 20.00, '2026-02-09', '2026-03-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(281, 302, 1, 0.00, 0.20, 1.20, 20.00, '2026-02-09', '2026-02-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(282, 303, 1, 0.00, 0.20, 1.20, 20.00, '2026-02-09', '2026-02-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(283, 304, 1, 0.00, 0.20, 1.20, 20.00, '2026-02-09', '2026-02-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(284, 307, 1, 0.00, 30000.00, 180000.00, 20.00, '2026-02-11', '2026-02-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(285, 308, 1, 0.00, 3000.00, 18000.00, 20.00, '2026-02-16', '2026-03-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(286, 309, 1, 0.00, 2400.00, 14400.00, 20.00, '2026-02-13', '2026-02-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(287, 310, 1, 0.00, 72000.00, 432000.00, 20.00, '2026-02-10', '2026-02-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(288, 311, 1, 0.00, 65910.00, 285610.00, 30.00, '2026-01-31', '2026-03-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(289, 312, 1, 0.00, 12179.52, 73077.12, 20.00, '2026-02-18', '2026-03-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(290, 313, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-02-18', '2026-02-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(291, 314, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-02-16', '2026-02-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(292, 315, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-02-09', '2026-02-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(293, 316, 1, 0.00, 32000.00, 192000.00, 20.00, '2026-02-18', '2026-02-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(294, 317, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-02-19', '2026-03-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(295, 318, 1, 0.00, 800.00, 4800.00, 20.00, '2026-02-19', '2026-03-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(296, 319, 1, 0.00, 8000.00, 48000.00, 20.00, '2026-02-20', '2026-03-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(297, 320, 1, 0.00, 400.00, 2400.00, 20.00, '2026-02-18', '2026-02-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(298, 321, 1, 0.00, 18124.80, 199372.80, 10.00, '2026-02-22', '2026-02-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(299, 322, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-02-26', '2026-03-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(300, 323, 1, 0.00, 38400.00, 230400.00, 20.00, '2026-02-28', '2026-03-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(301, 324, 1, 0.00, 30000.00, 180000.00, 20.00, '2026-02-27', '2026-03-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(302, 325, 1, 0.00, 57122.00, 342732.00, 20.00, '2026-03-03', '2026-04-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(303, 326, 1, 0.00, 700.00, 4200.00, 20.00, '2026-03-04', '2026-03-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(304, 327, 1, 0.00, 3000.00, 18000.00, 20.00, '2026-03-03', '2026-03-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(305, 328, 1, 0.00, 600.00, 3600.00, 20.00, '2026-03-06', '2026-03-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(306, 329, 1, 0.00, 11000.00, 66000.00, 20.00, '2026-03-06', '2026-03-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(307, 330, 1, 0.00, 86400.00, 518400.00, 20.00, '2026-02-20', '2026-03-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(308, 331, 1, 0.00, 103680.00, 622080.00, 20.00, '2026-03-02', '2026-03-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(309, 332, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-03-08', '2026-03-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(310, 333, 1, 0.00, 3000.00, 18000.00, 20.00, '2026-03-08', '2026-03-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46');
INSERT INTO `loan_cycles` (`id`, `loan_id`, `cycle_number`, `previous_balance`, `interest_capitalized`, `new_balance`, `interest_rate`, `start_date`, `due_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(311, 334, 1, 0.00, 5000.00, 55000.00, 10.00, '2026-03-07', '2026-03-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(312, 335, 1, 0.00, 25568.40, 110796.40, 30.00, '2026-03-09', '2026-04-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(313, 336, 1, 0.00, 400.00, 2400.00, 20.00, '2026-03-12', '2026-03-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(314, 337, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-03-15', '2026-03-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(315, 338, 1, 0.00, 3600.00, 21600.00, 20.00, '2026-03-13', '2026-03-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(316, 339, 1, 0.00, 7500.00, 45000.00, 20.00, '2026-03-13', '2026-03-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(317, 340, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-02-20', '2026-03-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(318, 341, 1, 0.00, 1440.00, 8640.00, 20.00, '2026-03-02', '2026-03-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(319, 342, 1, 0.00, 1728.00, 10368.00, 20.00, '2026-03-12', '2026-03-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(320, 343, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-03-16', '2026-03-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(321, 344, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-03-18', '2026-03-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(322, 345, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-03-18', '2026-03-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(323, 346, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-03-19', '2026-03-29', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(324, 347, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-02-28', '2026-03-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(325, 349, 1, 0.00, 6866.39, 121306.19, 6.00, '2026-02-27', '2026-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(326, 350, 1, 0.00, 1600.00, 9600.00, 20.00, '2026-03-24', '2026-04-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(327, 351, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-03-25', '2026-04-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(328, 352, 1, 0.00, 300.00, 1800.00, 20.00, '2026-03-25', '2026-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(329, 353, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-03-26', '2026-04-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(330, 354, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-03-26', '2026-04-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(331, 355, 1, 0.00, 200.00, 1200.00, 20.00, '2026-03-27', '2026-04-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(332, 356, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-03-28', '2026-04-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(333, 357, 1, 0.00, 9000.00, 54000.00, 20.00, '2026-03-30', '2026-04-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(334, 358, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-03-27', '2026-04-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(335, 359, 1, 0.00, 1000.00, 11000.00, 10.00, '2026-04-01', '2026-04-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(336, 360, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-03-26', '2026-04-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(337, 361, 1, 0.00, 640.00, 3840.00, 20.00, '2026-03-31', '2026-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(338, 362, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-03-31', '2026-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(339, 363, 1, 0.00, 5000.00, 30000.00, 20.00, '2026-03-30', '2026-04-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(340, 364, 1, 0.00, 14615.42, 87692.54, 20.00, '2026-03-18', '2026-04-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(341, 365, 1, 0.00, 5000.00, 30000.00, 20.00, '2026-04-04', '2026-04-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(342, 366, 1, 0.00, 840.00, 5040.00, 20.00, '2026-03-14', '2026-03-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(343, 367, 1, 0.00, 1008.00, 6048.00, 20.00, '2026-03-24', '2026-04-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(344, 368, 1, 0.00, 2073.60, 12441.60, 20.00, '2026-03-21', '2026-03-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(345, 369, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-05', '2026-04-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(346, 370, 1, 0.00, 4800.00, 28800.00, 20.00, '2026-04-05', '2026-04-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(347, 371, 1, 0.00, 300.00, 1800.00, 20.00, '2026-04-08', '2026-04-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(348, 372, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-04-10', '2026-04-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(349, 373, 1, 0.00, 608.00, 3648.00, 20.00, '2026-04-10', '2026-04-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(350, 374, 1, 0.00, 68546.40, 411278.40, 20.00, '2026-04-03', '2026-05-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(351, 375, 1, 0.00, 3697.80, 22186.80, 20.00, '2026-03-31', '2026-04-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(352, 376, 1, 0.00, 4437.36, 26624.16, 20.00, '2026-04-10', '2026-04-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(353, 377, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-15', '2026-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(354, 378, 1, 0.00, 1500.00, 9000.00, 20.00, '2026-04-14', '2026-04-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(355, 379, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-15', '2026-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(356, 380, 1, 0.00, 39600.00, 105600.00, 60.00, '2026-03-20', '2026-06-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(357, 381, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-15', '2026-04-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(358, 382, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-04-09', '2026-04-19', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(359, 383, 1, 0.00, 33238.92, 144035.32, 30.00, '2026-04-09', '2026-05-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(360, 384, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-04-20', '2026-04-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(361, 385, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-04-20', '2026-04-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(362, 386, 1, 0.00, 200.00, 1200.00, 20.00, '2026-04-22', '2026-05-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(363, 387, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-22', '2026-05-02', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(364, 388, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-04-23', '2026-05-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(365, 389, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-04-23', '2026-05-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(366, 390, 1, 0.00, 600.00, 3600.00, 20.00, '2026-04-25', '2026-05-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(367, 391, 1, 0.00, 1440.00, 8640.00, 20.00, '2026-04-30', '2026-05-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(368, 392, 1, 0.00, 1080.00, 6480.00, 20.00, '2026-04-27', '2026-05-07', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(369, 393, 1, 0.00, 360.00, 2160.00, 20.00, '2026-04-25', '2026-05-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(370, 394, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-04-25', '2026-05-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(371, 395, 1, 0.00, 7000.00, 42000.00, 20.00, '2026-05-02', '2026-05-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(372, 396, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-05-02', '2026-05-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(373, 397, 1, 0.00, 600.00, 3600.00, 20.00, '2026-05-04', '2026-05-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(374, 398, 1, 0.00, 1400.00, 8400.00, 20.00, '2026-05-04', '2026-05-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(375, 399, 1, 0.00, 6250.00, 31250.00, 25.00, '2026-05-04', '2026-05-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(376, 400, 1, 0.00, 720.00, 4320.00, 20.00, '2026-05-06', '2026-05-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(377, 401, 1, 0.00, 15000.00, 90000.00, 20.00, '2026-05-07', '2026-05-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(378, 402, 1, 0.00, 82255.68, 493534.08, 20.00, '2026-05-03', '2026-06-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(379, 403, 1, 0.00, 240.00, 1440.00, 20.00, '2026-05-02', '2026-05-12', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(380, 404, 1, 0.00, 24000.00, 144000.00, 20.00, '2026-04-26', '2026-05-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(381, 405, 1, 0.00, 1728.00, 10368.00, 20.00, '2026-05-10', '2026-05-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(382, 406, 1, 0.00, 17538.51, 105231.05, 20.00, '2026-04-18', '2026-05-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(383, 407, 1, 0.00, 288.00, 1728.00, 20.00, '2026-05-12', '2026-05-22', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(384, 408, 1, 0.00, 14250.00, 85500.00, 20.00, '2026-05-11', '2026-05-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(385, 409, 1, 0.00, 43210.60, 187245.92, 30.00, '2026-05-09', '2026-06-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(386, 410, 1, 0.00, 3000.00, 18000.00, 20.00, '2026-05-14', '2026-05-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(387, 411, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-05-11', '2026-05-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(388, 412, 1, 0.00, 7000.00, 42000.00, 20.00, '2026-05-15', '2026-06-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(389, 413, 1, 0.00, 5000.00, 30000.00, 20.00, '2026-05-11', '2026-05-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(390, 414, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-05-13', '2026-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(391, 415, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-05-14', '2026-05-24', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(392, 416, 1, 0.00, 372.00, 2232.00, 20.00, '2026-05-15', '2026-05-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(393, 417, 1, 0.00, 864.00, 5184.00, 20.00, '2026-05-16', '2026-05-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(394, 418, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-05-17', '2026-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(395, 419, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-05-17', '2026-05-27', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(396, 420, 1, 0.00, 21046.21, 126277.26, 20.00, '2026-05-18', '2026-05-28', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(397, 421, 1, 0.00, 4400.00, 26400.00, 20.00, '2026-05-16', '2026-05-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(398, 422, 1, 0.00, 18000.00, 108000.00, 20.00, '2026-05-16', '2026-05-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(399, 423, 1, 0.00, 600.00, 3600.00, 20.00, '2026-05-21', '2026-05-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(400, 424, 1, 0.00, 1036.80, 6220.80, 20.00, '2026-05-26', '2026-06-05', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(401, 425, 1, 0.00, 345.60, 2073.60, 20.00, '2026-05-22', '2026-06-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(402, 426, 1, 0.00, 3600.00, 21600.00, 20.00, '2026-05-24', '2026-06-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(403, 427, 1, 0.00, 17100.00, 102600.00, 20.00, '2026-05-21', '2026-05-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(404, 428, 1, 0.00, 432.00, 2592.00, 20.00, '2026-05-25', '2026-06-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(405, 429, 1, 0.00, 7818.37, 138124.57, 6.00, '2026-05-27', '2026-08-27', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-31 08:23:31'),
(406, 430, 1, 0.00, 1800.00, 10800.00, 20.00, '2026-05-27', '2026-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(407, 431, 1, 0.00, 600.00, 3600.00, 20.00, '2026-05-27', '2026-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(408, 432, 1, 0.00, 20520.00, 123120.00, 20.00, '2026-05-31', '2026-06-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(409, 433, 1, 0.00, 2800.00, 16800.00, 20.00, '2026-05-30', '2026-06-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(410, 434, 1, 0.00, 4200.00, 25200.00, 20.00, '2026-05-27', '2026-06-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(411, 435, 1, 0.00, 414.72, 2488.32, 20.00, '2026-06-01', '2026-06-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(412, 436, 1, 0.00, 24800.00, 148800.00, 20.00, '2026-05-26', '2026-06-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(413, 437, 1, 0.00, 2120.00, 12720.00, 20.00, '2026-06-06', '2026-06-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(414, 438, 1, 0.00, 2760.00, 16560.00, 20.00, '2026-06-06', '2026-06-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(415, 439, 1, 0.00, 3440.00, 20640.00, 20.00, '2026-06-06', '2026-06-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(416, 440, 1, 0.00, 1244.16, 7464.96, 20.00, '2026-06-05', '2026-06-15', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(417, 441, 1, 0.00, 8400.00, 50400.00, 20.00, '2026-06-05', '2026-06-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:46'),
(418, 442, 1, 0.00, 98706.82, 592240.92, 20.00, '2026-06-03', '2026-07-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:16'),
(419, 443, 1, 0.00, 4320.00, 25920.00, 20.00, '2026-06-03', '2026-06-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(420, 444, 1, 0.00, 5184.00, 31104.00, 20.00, '2026-06-13', '2026-06-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(421, 445, 1, 0.00, 62073.18, 372439.10, 20.00, '2026-06-10', '2026-06-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(422, 446, 1, 0.00, 497.66, 2985.98, 20.00, '2026-06-11', '2026-06-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(423, 447, 1, 0.00, 1493.00, 8958.00, 20.00, '2026-06-15', '2026-06-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(424, 448, 1, 0.00, 3312.00, 19872.00, 20.00, '2026-06-16', '2026-06-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(425, 449, 1, 0.00, 2544.00, 15264.00, 20.00, '2026-06-16', '2026-06-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(426, 450, 1, 0.00, 8000.00, 48000.00, 20.00, '2026-06-20', '2026-06-30', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-08 16:02:09'),
(427, 451, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-06-10', '2026-06-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(428, 452, 1, 0.00, 597.20, 3583.20, 20.00, '2026-06-21', '2026-07-01', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(429, 453, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-06-24', '2026-07-04', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(430, 454, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-06-23', '2026-07-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(431, 455, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-06-22', '2026-07-13', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(432, 456, 1, 0.00, 25255.46, 151532.76, 20.00, '2026-05-18', '2026-06-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(433, 457, 1, 0.00, 6174.40, 37046.40, 20.00, '2026-06-26', '2026-07-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(434, 458, 1, 0.00, 40000.00, 240000.00, 20.00, '2026-06-30', '2026-07-10', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(435, 459, 1, 0.00, 3052.80, 18316.80, 20.00, '2026-06-26', '2026-07-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(436, 460, 1, 0.00, 4280.00, 25680.00, 20.00, '2026-06-26', '2026-07-17', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(437, 461, 1, 0.00, 51360.00, 136960.00, 60.00, '2026-06-20', '2026-09-20', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-10-09 18:24:22'),
(438, 462, 1, 0.00, 30306.56, 181839.36, 20.00, '2026-06-18', '2026-07-18', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 20:49:15'),
(439, 463, 1, 0.00, 118448.18, 710689.10, 20.00, '2026-07-03', '2026-08-03', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 20:45:51'),
(440, 464, 1, 0.00, 716.64, 4299.84, 20.00, '2026-07-01', '2026-07-11', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(441, 465, 1, 0.00, 1791.60, 10749.60, 20.00, '2026-06-25', '2026-07-25', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-09-03 10:59:57'),
(442, 466, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-06-26', '2026-07-06', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(443, 467, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-07-04', '2026-07-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(444, 468, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-07-04', '2026-07-18', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(445, 469, 1, 0.00, 12000.00, 72000.00, 20.00, '2026-07-04', '2026-07-14', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(446, 470, 1, 0.00, 518.40, 3110.40, 20.00, '2026-06-25', '2026-07-25', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 12:23:17'),
(447, 471, 1, 0.00, 12000.00, 72000.00, 20.00, '2026-07-06', '2026-07-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(448, 472, 1, 0.00, 48000.00, 288000.00, 20.00, '2026-07-10', '2026-07-20', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(449, 473, 1, 0.00, 3663.36, 21980.16, 20.00, '2026-07-06', '2026-07-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(450, 474, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-07-13', '2026-07-23', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(451, 475, 1, 0.00, 7409.28, 44455.68, 20.00, '2026-07-06', '2026-07-16', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(452, 476, 1, 0.00, 1031.96, 6191.77, 20.00, '2026-07-11', '2026-07-21', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(453, 477, 1, 0.00, 7200.00, 43200.00, 20.00, '2026-07-14', '2026-07-28', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 20:46:29'),
(454, 478, 1, 0.00, 8891.14, 53346.82, 20.00, '2026-07-16', '2026-07-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(455, 479, 1, 0.00, 4396.03, 26376.19, 20.00, '2026-07-16', '2026-07-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(456, 480, 1, 0.00, 6400.00, 38400.00, 20.00, '2026-07-16', '2026-07-26', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(457, 481, 1, 0.00, 86400.00, 374400.00, 30.00, '2026-07-20', '2026-07-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(458, 482, 1, 0.00, 14000.00, 84000.00, 20.00, '2026-07-20', '2026-07-30', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(459, 483, 1, 0.00, 1238.36, 7430.16, 20.00, '2026-07-21', '2026-07-31', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(460, 484, 1, 0.00, 5000.00, 30000.00, 20.00, '2026-07-24', '2026-08-03', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(461, 485, 1, 0.00, 1240.00, 7440.00, 20.00, '2026-07-25', '2026-08-04', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 19:13:37'),
(462, 486, 1, 0.00, 5275.24, 31651.43, 20.00, '2026-07-26', '2026-08-26', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-09-03 11:03:31'),
(463, 487, 1, 0.00, 7680.00, 46080.00, 20.00, '2026-07-26', '2026-08-05', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 16:52:37'),
(464, 488, 1, 0.00, 112320.00, 486720.00, 30.00, '2026-07-30', '2026-08-09', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-10 10:23:03'),
(465, 489, 1, 0.00, 11129.36, 66776.18, 20.00, '2026-07-26', '2026-08-05', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-07 16:55:08'),
(466, 490, 1, 0.00, 24800.00, 148800.00, 20.00, '2026-07-30', '2026-08-09', 'active', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-06 13:11:47'),
(467, 491, 1, 0.00, 1486.03, 8916.19, 20.00, '2026-07-31', '2026-08-10', 'completed', 'Initial cycle (auto-created)', '2026-08-05 13:04:15', '2026-08-10 10:14:31'),
(512, 492, 1, 0.00, 13355.24, 80131.42, 20.00, '2026-08-05', '2026-08-15', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-07 18:56:21', '2026-08-31 03:52:23'),
(513, 485, 2, 7440.00, 1488.00, 8928.00, 20.00, '2026-08-04', '2026-08-14', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-07 20:27:46', '2026-08-18 06:29:33'),
(514, 463, 2, 710689.10, 142137.82, 852826.92, 20.00, '2026-08-03', '2026-09-03', 'completed', 'Loan rollover - Cycle 2 (Quick Loans 1 month 20%)', '2026-08-07 20:45:51', '2026-09-04 12:10:50'),
(515, 477, 2, 43200.00, 8640.00, 51840.00, 20.00, '2026-07-28', '2026-08-11', 'completed', 'Loan rollover - Cycle 2 (Quick Loans 14)', '2026-08-07 20:46:29', '2026-09-03 11:01:58'),
(516, 462, 2, 181839.36, 36367.87, 218207.23, 20.00, '2026-07-18', '2026-08-18', 'active', 'Loan rollover - Cycle 2 (Quick Loans 1 month 20%)', '2026-08-07 20:49:15', '2026-08-07 20:49:15'),
(517, 450, 2, 48000.00, 9600.00, 57600.00, 20.00, '2026-06-30', '2026-07-10', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-08 16:02:09', '2026-08-08 16:02:18'),
(518, 450, 3, 57600.00, 11520.00, 69120.00, 20.00, '2026-07-10', '2026-07-20', 'completed', 'Loan rollover - Cycle 3 (Emergency 10 Days)', '2026-08-08 16:02:18', '2026-08-08 16:02:25'),
(519, 450, 4, 69120.00, 13824.00, 82944.00, 20.00, '2026-07-20', '2026-07-30', 'completed', 'Loan rollover - Cycle 4 (Emergency 10 Days)', '2026-08-08 16:02:25', '2026-08-16 12:19:27'),
(520, 491, 2, 8916.19, 1783.24, 10699.43, 20.00, '2026-08-10', '2026-08-20', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-10 10:14:31', '2026-08-31 08:24:19'),
(521, 488, 2, 486720.00, 146016.00, 632736.00, 30.00, '2026-08-09', '2026-08-19', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days at 30%)', '2026-08-10 10:23:03', '2026-08-25 14:38:12'),
(522, 493, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-08-06', '2026-08-16', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-11 15:41:25', '2026-08-19 04:51:56'),
(523, 494, 1, 0.00, 8000.00, 48000.00, 20.00, '2026-08-07', '2026-08-21', 'completed', 'Initial loan cycle - Quick Loans 14', '2026-08-11 15:53:01', '2026-08-26 09:51:04'),
(524, 495, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-08-09', '2026-08-19', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-11 15:58:25', '2026-08-19 11:29:42'),
(525, 496, 1, 0.00, 2400.00, 14400.00, 20.00, '2026-08-08', '2026-08-18', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-11 16:00:52', '2026-08-20 07:38:58'),
(526, 450, 5, 82944.00, 16588.80, 99532.80, 20.00, '2026-07-30', '2026-08-09', 'completed', 'Loan rollover - Cycle 5 (Emergency 10 Days)', '2026-08-16 12:19:27', '2026-08-16 12:19:52'),
(527, 450, 6, 99532.80, 19906.56, 119439.36, 20.00, '2026-08-09', '2026-08-19', 'completed', 'Loan rollover - Cycle 6 (Emergency 10 Days)', '2026-08-16 12:19:52', '2026-08-26 10:45:55'),
(528, 485, 3, 4447.59, 889.52, 5337.11, 20.00, '2026-08-14', '2026-08-24', 'completed', 'Loan rollover - Cycle 3 (Emergency 10 Days)', '2026-08-18 06:29:33', '2026-08-25 12:03:43'),
(529, 495, 2, 5000.00, 1000.00, 6000.00, 20.00, '2026-08-19', '2026-08-29', 'active', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-19 11:29:42', '2026-08-19 11:29:42'),
(530, 497, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-08-19', '2026-08-29', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-24 08:13:46', '2026-08-24 08:15:26'),
(531, 498, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-08-12', '2026-09-02', 'completed', 'Initial loan cycle - 3 weeks 20%', '2026-08-24 08:17:13', '2026-09-02 07:29:45'),
(532, 499, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-08-12', '2026-09-12', 'completed', 'Quick Loans 1 month 20%', '2026-08-24 08:19:59', '2026-09-14 14:10:38'),
(533, 500, 1, 0.00, 3000.00, 13000.00, 30.00, '2026-08-20', '2026-09-20', 'completed', 'Initial loan cycle - Quick Loans 1 months 30%', '2026-08-24 10:53:50', '2026-09-23 05:31:21'),
(534, 501, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-08-24', '2026-09-03', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-24 10:56:49', '2026-09-03 13:21:22'),
(535, 502, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-08-23', '2026-09-02', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-24 11:00:20', '2026-09-02 13:21:50'),
(536, 503, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-08-23', '2026-09-02', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-24 11:17:00', '2026-09-02 13:16:36'),
(537, 504, 1, 0.00, 2300.00, 13800.00, 20.00, '2026-08-13', '2026-08-23', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-24 11:53:26', '2026-08-25 14:31:30'),
(538, 504, 2, 13800.00, 2760.00, 16560.00, 20.00, '2026-08-23', '2026-09-02', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-25 14:31:30', '2026-09-04 11:36:55'),
(539, 488, 3, 632736.00, 189820.00, 822556.80, 30.00, '2026-08-19', '2026-08-29', 'completed', 'Loan rollover - Cycle 3 (Emergency 10 Days at 30%)', '2026-08-25 14:38:12', '2026-08-31 08:55:12'),
(540, 494, 2, 48000.00, 9600.00, 57600.00, 20.00, '2026-08-21', '2026-09-04', 'completed', 'Loan rollover - Cycle 2 (Quick Loans 14)', '2026-08-26 09:51:04', '2026-09-03 06:49:16'),
(541, 450, 7, 119439.36, 23887.87, 143327.23, 20.00, '2026-08-19', '2026-08-29', 'completed', 'Loan rollover - Cycle 7 (Emergency 10 Days)', '2026-08-26 10:45:55', '2026-10-01 07:00:45'),
(542, 492, 2, 87331.42, 17466.28, 104797.70, 20.00, '2026-08-15', '2026-08-25', 'completed', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-08-31 03:52:23', '2026-08-31 09:01:28'),
(543, 429, 2, 138125.42, 8287.53, 146412.95, 6.00, '2026-08-27', '2026-11-27', 'active', 'Loan rollover - Cycle 2 (Family 3 months 2% monthly)', '2026-08-31 08:23:31', '2026-08-31 08:23:31'),
(544, 491, 3, 10699.43, 2139.89, 12839.31, 20.00, '2026-08-20', '2026-08-30', 'completed', 'Loan rollover - Cycle 3 (Emergency 10 Days)', '2026-08-31 08:24:19', '2026-08-31 08:31:25'),
(545, 505, 1, 0.00, 4000.00, 20000.00, 20.00, '2026-08-31', '2026-09-10', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-31 08:30:12', '2026-09-11 05:08:09'),
(546, 491, 4, 12839.32, 2567.86, 15407.18, 20.00, '2026-08-30', '2026-09-09', 'active', 'Loan rollover - Cycle 4 (Emergency 10 Days)', '2026-08-31 08:31:25', '2026-08-31 08:31:25'),
(547, 506, 1, 0.00, 1600.00, 9600.00, 20.00, '2026-08-31', '2026-10-01', 'completed', 'Initial loan cycle - Quick Loans 1 month 20%', '2026-08-31 08:34:47', '2026-10-03 13:18:26'),
(548, 507, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-08-28', '2026-09-07', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-31 08:43:17', '2026-08-31 08:53:44'),
(549, 488, 4, 702556.80, 140511.36, 843068.16, 20.00, '2026-08-29', '2026-09-08', 'completed', 'Loan rollover - Cycle 4 (Emergency 10 Days at 20%)', '2026-08-31 08:55:12', '2026-08-31 08:55:36'),
(550, 488, 5, 843068.16, 168613.64, 1011681.80, 20.00, '2026-09-08', '2026-09-18', 'active', 'Loan rollover - Cycle 5 (Emergency 10 Days at 20%)', '2026-08-31 08:55:36', '2026-08-31 08:55:36'),
(551, 492, 3, 104797.70, 20959.54, 125757.24, 20.00, '2026-08-25', '2026-09-04', 'completed', 'Loan rollover - Cycle 3 (Emergency 10 Days)', '2026-08-31 09:01:28', '2026-09-07 06:07:49'),
(552, 508, 1, 0.00, 7500.00, 45000.00, 20.00, '2026-08-31', '2026-09-10', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-31 09:11:18', '2026-09-09 09:13:39'),
(553, 509, 1, 0.00, 1200.00, 7200.00, 20.00, '2026-08-30', '2026-09-09', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-08-31 13:49:59', '2026-09-09 17:03:27'),
(554, 502, 2, 10000.00, 2000.00, 12000.00, 20.00, '2026-09-02', '2026-09-12', 'active', 'Loan rollover - Cycle 2 (Emergency 10 Days)', '2026-09-02 13:21:50', '2026-09-02 13:21:50'),
(555, 494, 3, 57600.00, 11520.00, 69120.00, 20.00, '2026-09-04', '2026-09-23', 'completed', 'Loan rollover - Cycle 3 (Quick Loans 14)', '2026-09-03 06:49:16', '2026-09-25 09:48:19'),
(556, 510, 1, 0.00, 34504.00, 172520.00, 20.00, '2026-09-03', '2026-10-03', 'completed', 'Initial loan cycle - 1 month 20%', '2026-09-03 07:13:51', '2026-09-29 12:37:41'),
(557, 511, 1, 0.00, 50000.00, 150000.00, 50.00, '2026-05-29', '2027-05-29', 'active', 'Initial loan cycle - Trade Finance 1 year 50% ', '2026-09-03 07:21:02', '2026-09-03 07:21:02'),
(558, 465, 2, 2749.60, 0.00, 2749.60, 0.00, '2026-09-03', '2026-10-03', 'active', 'PAYMENT PLAN: Interest 0%, Period 30 days, Penalties WAIVED. Previous repayments: KES 8,000.00', '2026-09-03 10:59:57', '2026-09-03 10:59:57'),
(559, 477, 3, 51840.00, 10368.00, 62208.00, 20.00, '2026-09-03', '2026-10-03', 'active', 'PAYMENT PLAN: Interest 20%, Period 30 days, Penalties WAIVED. Previous repayments: KES 0.00', '2026-09-03 11:01:58', '2026-09-03 11:01:58'),
(560, 486, 2, 29651.43, 0.00, 29651.43, 0.00, '2026-09-03', '2026-10-08', 'active', 'PAYMENT PLAN: Interest 0%, Period 35 days, Penalties WAIVED. Previous repayments: KES 3,000.00', '2026-09-03 11:03:31', '2026-09-03 11:03:31'),
(561, 512, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-09-04', '2026-09-14', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-04 11:15:32', '2026-09-14 16:51:44'),
(562, 513, 1, 0.00, 10000.00, 50000.00, 20.00, '2026-09-04', '2026-09-14', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-04 11:18:31', '2026-09-17 13:05:22'),
(563, 504, 3, 16560.00, 3312.00, 19872.00, 20.00, '2026-09-02', '2026-09-12', 'completed', 'Loan rollover - Cycle 3', '2026-09-04 11:36:55', '2026-09-14 14:32:32'),
(564, 463, 3, 852826.92, 170565.38, 1023392.30, 20.00, '2026-09-03', '2026-10-03', 'active', 'Loan rollover - Cycle 3', '2026-09-04 12:10:50', '2026-09-04 12:10:50'),
(565, 492, 4, 125757.24, 25151.45, 150908.69, 20.00, '2026-09-07', '2026-10-22', 'active', 'PAYMENT PLAN: Interest 20%, Period 45 days, Penalties WAIVED. Previous repayments: KES 0.00', '2026-09-07 06:07:49', '2026-09-07 06:07:49'),
(566, 514, 1, 0.00, 15000.00, 65000.00, 30.00, '2026-08-12', '2026-09-12', 'completed', 'Initial loan cycle - Quick Loans 1 months 30%', '2026-09-07 07:51:30', '2026-09-14 07:25:21'),
(567, 515, 1, 0.00, 9000.00, 54000.00, 20.00, '2026-09-12', '2026-09-22', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-09 09:14:25', '2026-09-25 09:25:01'),
(568, 505, 2, 20000.00, 4000.00, 24000.00, 20.00, '2026-09-10', '2026-09-20', 'completed', 'Loan rollover - Cycle 2', '2026-09-11 05:08:09', '2026-09-23 03:42:29'),
(569, 516, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-09-11', '2026-09-21', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-11 05:51:57', '2026-09-23 05:19:26'),
(570, 517, 1, 0.00, 2000.00, 22000.00, 20.00, '2026-09-12', '2026-09-22', 'active', 'Initial loan cycle - Emergency 5 Days', '2026-09-14 07:37:51', '2026-09-14 07:37:51'),
(571, 518, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-09-14', '2026-09-24', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-14 14:08:56', '2026-09-25 09:28:37'),
(572, 519, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-09-12', '2026-09-22', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-14 14:22:31', '2026-09-25 09:34:46'),
(573, 504, 4, 19872.00, 3974.40, 23846.40, 20.00, '2026-09-12', '2026-09-22', 'completed', 'Loan rollover - Cycle 4', '2026-09-14 14:32:32', '2026-09-23 05:30:25'),
(574, 520, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-09-15', '2026-09-25', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-15 17:57:17', '2026-09-28 08:27:15'),
(575, 521, 1, 0.00, 1000.00, 6000.00, 20.00, '2026-09-15', '2026-09-25', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-15 17:58:42', '2026-09-23 03:40:54'),
(576, 193, 2, 213600.00, 23200.00, 236800.00, 10.00, '2026-09-15', '2026-09-18', 'completed', 'MANUAL ADJUSTMENT: New principal set to KES 213,600.00 | Interest: 10% | Reason: ', '2026-09-15 18:03:48', '2026-09-15 18:04:57'),
(577, 189, 2, 136000.00, 27200.00, 163200.00, 0.00, '2026-09-15', '2026-09-18', 'active', 'MANUAL ADJUSTMENT: New principal set to KES 136,000.00 | Interest: 20% | Reason: KES 400,000 received from RUTH SUDOI MAKALLAH into A/C **6350. TUMA DIRECT na Pesalink. Ambia HR atume salo Direct from bank, 24/7, NO CUT-OFF. Download receipt here: https://r.pesalink.co.ke/r1=jZQ6lKXk 0068005720260915181600APF9A247', '2026-09-15 18:07:12', '2026-09-15 18:07:12'),
(578, 522, 1, 0.00, 400.00, 2400.00, 20.00, '2026-09-16', '2026-09-26', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-16 07:07:42', '2026-09-28 08:42:39'),
(579, 513, 2, 60000.00, 12000.00, 72000.00, 20.00, '2026-09-14', '2026-09-24', 'completed', 'Loan rollover - Cycle 2', '2026-09-17 13:05:22', '2026-09-23 03:41:19'),
(580, 523, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-09-18', '2026-09-29', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-18 14:03:42', '2026-09-30 14:02:43'),
(581, 524, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-09-18', '2026-09-28', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-18 14:05:54', '2026-09-28 08:41:47'),
(582, 513, 3, 72000.00, 14400.00, 86400.00, 20.00, '2026-09-24', '2026-10-04', 'completed', 'Loan rollover - Cycle 3', '2026-09-23 03:41:19', '2026-10-05 09:13:49'),
(583, 505, 3, 24000.00, 2400.00, 26400.00, 10.00, '2026-09-20', '2026-09-30', 'active', 'Loan rollover - Cycle 3', '2026-09-23 03:42:29', '2026-09-23 03:42:29'),
(584, 516, 2, 20400.00, 4080.00, 24480.00, 20.00, '2026-09-23', '2026-10-03', 'completed', 'Loan rollover - Cycle 2', '2026-09-23 05:19:26', '2026-09-23 05:21:01'),
(586, 504, 5, 23846.40, 4769.28, 28615.68, 20.00, '2026-09-22', '2026-10-02', 'completed', 'Loan rollover - Cycle 5', '2026-09-23 05:30:25', '2026-10-05 12:33:31'),
(587, 500, 2, 13000.00, 3900.00, 16900.00, 30.00, '2026-09-20', '2026-10-20', 'active', 'Loan rollover - Cycle 2', '2026-09-23 05:31:21', '2026-09-23 05:31:21'),
(588, 525, 1, 0.00, 400.00, 2400.00, 20.00, '2026-09-23', '2026-10-03', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-23 06:17:17', '2026-09-30 10:06:14'),
(589, 515, 2, 54000.00, 10800.00, 64800.00, 20.00, '2026-09-22', '2026-10-02', 'completed', 'Loan rollover - Cycle 2', '2026-09-25 09:25:01', '2026-10-05 13:47:02'),
(590, 526, 1, 0.00, 5000.00, 30000.00, 20.00, '2026-09-24', '2026-10-04', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-25 09:37:40', '2026-10-03 13:37:20'),
(591, 527, 1, 0.00, 200.00, 1200.00, 20.00, '2026-09-25', '2026-10-05', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-25 10:18:30', '2026-09-28 08:52:56'),
(592, 528, 1, 0.00, 0.00, 10000.00, 0.00, '2026-09-26', '2026-10-06', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-09-26 14:50:00', '2026-09-26 14:50:00'),
(593, 529, 1, 0.00, 2400.00, 14400.00, 20.00, '2026-09-26', '2026-10-06', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-26 14:51:46', '2026-10-07 08:50:16'),
(594, 520, 2, 36000.00, 0.00, 36000.00, 0.00, '2026-09-25', '2026-09-30', 'active', 'Loan rollover - Cycle 2', '2026-09-28 08:27:15', '2026-09-28 08:27:15'),
(595, 530, 1, 0.00, 2000.00, 12000.00, 20.00, '2026-09-26', '2026-10-06', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-28 08:29:05', '2026-10-09 07:42:46'),
(596, 531, 1, 0.00, 1800.00, 10800.00, 20.00, '2026-09-27', '2026-10-07', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-28 08:34:46', '2026-10-03 13:23:19'),
(597, 510, 2, 274520.00, 54904.00, 329424.00, 20.00, '2026-10-03', '2026-11-03', 'active', 'Loan rollover - Cycle 2', '2026-09-29 12:37:41', '2026-09-29 12:37:41'),
(598, 532, 1, 0.00, 10000.00, 60000.00, 20.00, '2026-09-30', '2026-10-10', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-09-30 12:19:58', '2026-10-13 06:30:28'),
(599, 523, 2, 11200.00, 2240.00, 13440.00, 20.00, '2026-09-29', '2026-10-09', 'completed', 'Loan rollover - Cycle 2', '2026-09-30 14:02:43', '2026-10-09 18:10:17'),
(600, 450, 8, 28000.00, 0.00, 28000.00, 0.00, '2026-10-01', '2026-10-11', 'completed', 'MANUAL ADJUSTMENT: New principal set to KES 28,000.00 | Interest: 0% | Reason: UIUCJ8DO32 Confirmed. KES 28,000.00 received from LUCY KERUBO MOMANYI for account 23849 on 30/09/26 at 08:07 PM. Enquiries, call 0719088000.', '2026-10-01 07:00:45', '2026-10-01 08:08:38'),
(601, 506, 2, 8000.00, 1600.00, 9600.00, 20.00, '2026-10-01', '2026-11-01', 'active', 'Loan rollover - Cycle 2', '2026-10-03 13:18:26', '2026-10-03 13:18:26'),
(602, 533, 1, 0.00, 3000.00, 18000.00, 20.00, '2026-10-01', '2026-10-11', 'completed', 'Initial loan cycle - Emergency 10 Days', '2026-10-03 13:26:07', '2026-10-12 06:12:15'),
(603, 513, 4, 86400.00, 17280.00, 103680.00, 20.00, '2026-10-04', '2026-10-17', 'active', 'Loan rollover - Cycle 4', '2026-10-05 09:13:49', '2026-10-05 09:13:49'),
(604, 504, 6, 28615.68, 5723.14, 34338.82, 20.00, '2026-10-02', '2026-10-12', 'completed', 'Loan rollover - Cycle 6', '2026-10-05 12:33:31', '2026-10-13 03:44:07'),
(605, 515, 3, 64800.00, 12960.00, 77760.00, 20.00, '2026-10-02', '2026-10-12', 'completed', 'Loan rollover - Cycle 3', '2026-10-05 13:47:02', '2026-10-12 08:06:28'),
(606, 529, 2, 14400.00, 2880.00, 17280.00, 20.00, '2026-10-06', '2026-10-16', 'active', 'Loan rollover - Cycle 2', '2026-10-07 08:50:16', '2026-10-07 08:50:16'),
(607, 534, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-10-08', '2026-10-18', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-10-08 09:30:53', '2026-10-08 09:30:53'),
(608, 461, 2, 36960.00, 7392.00, 44352.00, 20.00, '2026-09-20', '2026-12-20', 'active', 'Loan rollover - Cycle 2', '2026-10-09 18:24:22', '2026-10-09 18:24:22'),
(609, 515, 4, 63000.00, 12600.00, 75600.00, 20.00, '2026-10-12', '2026-10-22', 'active', 'Loan rollover - Cycle 4', '2026-10-12 08:06:28', '2026-10-12 08:06:28'),
(610, 535, 1, 0.00, 6000.00, 36000.00, 20.00, '2026-10-12', '2026-10-22', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-10-12 08:39:36', '2026-10-12 08:39:36'),
(611, 210, 2, 12000.00, 2400.00, 14400.00, 20.00, '2026-10-12', '2026-10-22', 'active', 'MANUAL ADJUSTMENT: New principal set to KES 12,000.00 | Interest: 20% | Reason: ', '2026-10-12 16:51:28', '2026-10-12 16:51:28'),
(612, 504, 7, 34338.82, 6867.76, 41206.58, 20.00, '2026-10-12', '2026-10-22', 'active', 'Loan rollover - Cycle 7', '2026-10-13 03:44:07', '2026-10-13 03:44:07'),
(613, 532, 2, 60000.00, 12000.00, 72000.00, 20.00, '2026-10-10', '2026-10-20', 'active', 'Loan rollover - Cycle 2', '2026-10-13 06:30:28', '2026-10-13 06:30:28'),
(614, 536, 1, 0.00, 4000.00, 24000.00, 20.00, '2026-10-13', '2026-10-23', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-10-13 06:34:21', '2026-10-13 06:34:21'),
(615, 537, 1, 0.00, 20000.00, 120000.00, 20.00, '2026-10-14', '2026-10-24', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-10-15 06:58:30', '2026-10-15 06:58:30'),
(616, 538, 1, 0.00, 200.00, 1200.00, 20.00, '2026-10-15', '2026-10-25', 'active', 'Initial loan cycle - Emergency 10 Days', '2026-10-15 07:04:16', '2026-10-15 07:04:16');

-- --------------------------------------------------------

--
-- Table structure for table `loan_ledger`
--

CREATE TABLE `loan_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `entry_type` enum('debit','credit','interest','penalty','fee','adjustment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_risk_assessments`
--

CREATE TABLE `loan_risk_assessments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `character_score` int(11) DEFAULT NULL,
  `capacity_score` int(11) DEFAULT NULL,
  `capital_score` int(11) DEFAULT NULL,
  `conditions_score` int(11) DEFAULT NULL,
  `overall_score` int(11) DEFAULT NULL,
  `risk_category` varchar(100) DEFAULT NULL,
  `assessed_by` bigint(20) UNSIGNED NOT NULL,
  `assessment_notes` text DEFAULT NULL,
  `recommendation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_types`
--

CREATE TABLE `loan_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `period` int(11) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `penalty_rate` decimal(5,2) NOT NULL,
  `grace_period_days` int(11) NOT NULL DEFAULT 0,
  `default_threshold_days` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_types`
--

INSERT INTO `loan_types` (`id`, `name`, `period`, `unit`, `interest_rate`, `penalty_rate`, `grace_period_days`, `default_threshold_days`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Emergency 10 Days', 10, 'days', 20.00, 10.00, 0, 30, 'Upto 250k!', NULL, NULL),
(2, 'Quick Loans 1 month 20%', 1, 'months', 20.00, 10.00, 0, 30, 'Upto 250k! Monthly', NULL, NULL),
(3, 'Quick Loans 14', 2, 'weeks', 20.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', NULL, NULL),
(4, 'Quick Loans 3 months', 3, 'months', 95.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(5, 'Quick Loans 1 months 30%', 1, 'months', 30.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(6, 'Emergency Loans 2 weeks 35%', 2, 'weeks', 35.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(7, 'Quick Loans 1 month 10%', 1, 'months', 10.00, 1.00, 0, 30, 'Upto 250k! Monthly', '2025-06-30 10:40:26', '2025-06-30 10:40:26'),
(8, 'Quick Loans 2 weeks +1 repayment week at 20%', 3, 'weeks', 20.00, 10.00, 0, 30, 'Upto 1m! Monthly', '2025-06-30 10:40:26', '2025-06-30 10:40:26'),
(9, 'Quick Loans 3 months 50% ', 3, 'months', 50.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(10, 'Quick Loans 3 months 10% monthly', 3, 'months', 30.00, 10.00, 0, 30, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(11, 'Quick Loans 2 months 15% monthly', 2, 'months', 30.00, 10.00, 0, 30, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(12, 'Quick Loans 2 months 20% monthly', 2, 'months', 20.00, 10.00, 0, 30, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(13, 'Quick Loans 3 months 60% ', 3, 'months', 60.00, 10.00, 0, 30, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(14, 'Emergency 5 Days', 5, 'days', 10.00, 7.50, 0, 30, 'Upto 250k!', '2026-01-29 11:23:31', '2026-01-29 11:23:23'),
(15, 'Family 3 months 2% monthly', 3, 'months', 6.00, 10.00, 0, 30, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(16, '7 days 25%', 7, 'days', 25.00, 10.00, 0, 30, 'Upto 300k! 2 weeks!', '2026-05-05 15:05:07', '2026-05-05 15:05:07'),
(17, '3 weeks 20%', 3, 'weeks', 20.00, 10.00, 0, 30, 'Upto 300k! 3 weeks!', '2026-05-15 15:05:07', '2026-05-15 15:05:07'),
(18, 'Emergency 10 Days at 30%', 10, 'days', 30.00, 10.00, 0, 30, 'Upto 250k!', NULL, NULL),
(19, 'Trade Finance per year', 1, 'year', 50.00, 2.00, 0, 45, 'From 500k a year!', '2026-09-07 09:43:19', '2026-09-07 09:43:19');

-- --------------------------------------------------------

--
-- Stand-in structure for view `loan_type_analysis`
-- (See below for the actual view)
--
CREATE TABLE `loan_type_analysis` (
`id` bigint(20) unsigned
,`name` varchar(255)
,`interest_rate` decimal(5,2)
,`times_issued` bigint(21)
,`total_principal` decimal(37,2)
,`avg_principal_size` decimal(19,6)
,`percentage_with_problems` decimal(28,2)
,`avg_borrower_health_score` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_04_14_152237_create_borrowers_table', 1),
(5, '2025_04_14_152238_create_admins_table', 1),
(6, '2025_04_14_152238_create_brokers_table', 1),
(7, '2025_04_14_152238_create_loan_types_table', 1),
(8, '2025_04_14_152238_create_tellers_table', 1),
(9, '2025_04_14_152239_create_loans_table', 1),
(10, '2025_04_14_152240_create_disbursements_table', 1),
(11, '2025_04_14_152241_create_repayments_table', 1),
(12, '2025_04_14_152242_create_bank_accounts_table', 1),
(13, '2025_04_14_152243_create_repayment_overflows_table', 1),
(14, '2025_04_14_152244_create_categories_table', 1),
(16, '2025_04_17_164920_add_broker_id_to_borrowers_table', 2),
(17, '2025_04_18_125222_add_broker_status_to_loans_table', 3),
(18, '2026_09_04_113054_create_permission_tables', 4),
(19, '2018_11_06_222923_create_transactions_table', 5),
(20, '2018_11_07_192923_create_transfers_table', 5),
(21, '2018_11_15_124230_create_wallets_table', 5),
(22, '2021_11_02_202021_update_wallets_uuid_table', 5),
(23, '2023_12_30_113122_extra_columns_removed', 5),
(24, '2023_12_30_204610_soft_delete', 5),
(25, '2024_01_24_185401_add_extra_column_in_transfer', 5);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(2, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 30),
(2, 'App\\Models\\User', 44),
(2, 'App\\Models\\User', 46),
(3, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 6),
(3, 'App\\Models\\User', 7),
(3, 'App\\Models\\User', 8),
(3, 'App\\Models\\User', 9),
(3, 'App\\Models\\User', 10),
(3, 'App\\Models\\User', 11),
(3, 'App\\Models\\User', 13),
(3, 'App\\Models\\User', 14),
(3, 'App\\Models\\User', 15),
(3, 'App\\Models\\User', 16),
(3, 'App\\Models\\User', 17),
(3, 'App\\Models\\User', 18),
(3, 'App\\Models\\User', 19),
(3, 'App\\Models\\User', 20),
(3, 'App\\Models\\User', 21),
(3, 'App\\Models\\User', 22),
(3, 'App\\Models\\User', 23),
(3, 'App\\Models\\User', 24),
(3, 'App\\Models\\User', 25),
(3, 'App\\Models\\User', 26),
(3, 'App\\Models\\User', 27),
(3, 'App\\Models\\User', 28),
(3, 'App\\Models\\User', 29),
(3, 'App\\Models\\User', 31),
(3, 'App\\Models\\User', 32),
(3, 'App\\Models\\User', 33),
(3, 'App\\Models\\User', 34),
(3, 'App\\Models\\User', 35),
(3, 'App\\Models\\User', 36),
(3, 'App\\Models\\User', 37),
(3, 'App\\Models\\User', 38),
(3, 'App\\Models\\User', 39),
(3, 'App\\Models\\User', 40),
(3, 'App\\Models\\User', 41),
(3, 'App\\Models\\User', 42),
(3, 'App\\Models\\User', 43),
(3, 'App\\Models\\User', 45),
(3, 'App\\Models\\User', 47),
(3, 'App\\Models\\User', 48),
(3, 'App\\Models\\User', 50),
(3, 'App\\Models\\User', 51),
(3, 'App\\Models\\User', 52),
(3, 'App\\Models\\User', 53),
(3, 'App\\Models\\User', 54),
(3, 'App\\Models\\User', 55),
(3, 'App\\Models\\User', 56),
(3, 'App\\Models\\User', 57),
(3, 'App\\Models\\User', 58),
(3, 'App\\Models\\User', 59),
(3, 'App\\Models\\User', 62),
(3, 'App\\Models\\User', 63),
(3, 'App\\Models\\User', 64),
(3, 'App\\Models\\User', 65),
(3, 'App\\Models\\User', 66),
(3, 'App\\Models\\User', 67),
(3, 'App\\Models\\User', 68),
(3, 'App\\Models\\User', 69),
(3, 'App\\Models\\User', 70),
(3, 'App\\Models\\User', 71),
(3, 'App\\Models\\User', 72),
(3, 'App\\Models\\User', 73),
(3, 'App\\Models\\User', 74),
(3, 'App\\Models\\User', 75),
(3, 'App\\Models\\User', 76),
(3, 'App\\Models\\User', 77),
(3, 'App\\Models\\User', 84),
(3, 'App\\Models\\User', 91),
(3, 'App\\Models\\User', 92),
(3, 'App\\Models\\User', 93),
(3, 'App\\Models\\User', 94),
(3, 'App\\Models\\User', 95),
(3, 'App\\Models\\User', 97),
(3, 'App\\Models\\User', 98),
(3, 'App\\Models\\User', 99),
(3, 'App\\Models\\User', 100),
(3, 'App\\Models\\User', 101),
(3, 'App\\Models\\User', 102),
(3, 'App\\Models\\User', 110),
(5, 'App\\Models\\User', 49),
(5, 'App\\Models\\User', 103),
(6, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `type` enum('corporate','partner','business','company','individual') NOT NULL DEFAULT 'partner',
  `account_type` enum('principal','interest','operating','reserve') NOT NULL DEFAULT 'principal',
  `is_house_account` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `profit_share_rate` decimal(5,2) DEFAULT 0.00,
  `broker_rate` decimal(5,2) NOT NULL DEFAULT 40.00 COMMENT 'Default broker fee share of interest, percent. Per-loan override on loans.broker_rate.',
  `max_loan_to_value` decimal(5,2) DEFAULT 75.00,
  `risk_tolerance` enum('conservative','moderate','aggressive') DEFAULT 'moderate',
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `user_id`, `name`, `email`, `phone`, `company_name`, `registration_number`, `type`, `account_type`, `is_house_account`, `status`, `profit_share_rate`, `broker_rate`, `max_loan_to_value`, `risk_tolerance`, `bank_account_name`, `bank_account_number`, `bank_name`, `swift_code`, `tax_id`, `notes`, `created_at`, `updated_at`, `current_balance`) VALUES
(5, 3, 'ISIRO AGENCIES', 'isiroagencies@gmail.com', NULL, NULL, NULL, 'business', 'principal', 0, 'active', 8.00, 40.00, 75.00, 'moderate', 'ISIRO AGENCIES', NULL, 'EQUITY BANK', NULL, NULL, NULL, '2026-07-16 19:24:44', '2026-07-16 19:24:44', 0.00),
(6, NULL, 'House Account', 'house@internal', NULL, NULL, NULL, 'company', 'principal', 1, 'active', 0.00, 40.00, 100.00, 'conservative', NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-06 18:20:17', '2026-10-06 18:20:17', 0.00),
(7, NULL, 'IMAGINE-NATION AGENCY LIMITED', 'info@imaginenation.co.ke', '+254717492048', 'IMAGINE-NATION AGENCY LIMITED', NULL, 'corporate', 'principal', 0, 'active', 40.00, 40.00, 75.00, 'moderate', 'IMAGINE-NATION AGENCY LIMITED', '01104493106350', 'I&M BANK KENYA LIMITED', NULL, 'P051794027I', NULL, '2026-10-07 09:06:19', '2026-10-07 09:06:19', 0.00),
(8, NULL, 'Obiri Victor', 'victor.obiri@gmail.com', '+254 725 575799', 'Victor Obiri', NULL, 'individual', 'principal', 0, 'active', 0.00, 40.00, 75.00, 'moderate', NULL, NULL, 'Absa Bank', NULL, NULL, NULL, '2026-10-09 18:01:27', '2026-10-09 18:01:27', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `partner_transactions`
--

CREATE TABLE `partner_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `partner_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_slug` varchar(32) NOT NULL DEFAULT 'principal' COMMENT 'principal|interest|broker_fee|investment_tracker|borrower_loan',
  `type` varchar(32) NOT NULL COMMENT 'contribution|withdrawal|principal_deployed|principal_returned|profit_distribution|broker_fee_earned|penalty_share|bonus|repayment|facility_funded|facility_settled|adjustment',
  `amount` decimal(19,4) NOT NULL COMMENT 'Signed from partner perspective: credit=+, debit=-',
  `balance_after` decimal(19,4) DEFAULT NULL COMMENT 'Snapshot only; bavix wallet is source of truth',
  `reference` varchar(255) DEFAULT NULL,
  `loan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `repayment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `investment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `investment_funding_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `posted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `partner_transactions`
--

INSERT INTO `partner_transactions` (`id`, `partner_id`, `wallet_slug`, `type`, `amount`, `balance_after`, `reference`, `loan_id`, `repayment_id`, `investment_id`, `investment_funding_id`, `notes`, `transaction_date`, `posted_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 5, 'broker_fee', 'broker_fee_earned', 9600.0000, 9600.0000, 'BACKFILL-FEE-L1', 1, NULL, NULL, NULL, 'Backfilled broker interest share for loan #1', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(2, 5, 'broker_fee', 'penalty_share', 6800.0000, 16400.0000, 'BACKFILL-PEN-L1', 1, NULL, NULL, NULL, 'Backfilled penalty share (50% of last-cycle penalty collected) for loan #1', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(3, 5, 'broker_fee', 'broker_fee_earned', 2400.0000, 18800.0000, 'BACKFILL-FEE-L2', 2, NULL, NULL, NULL, 'Backfilled broker interest share for loan #2', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(4, 5, 'broker_fee', 'broker_fee_earned', 4000.0000, 22800.0000, 'BACKFILL-FEE-L3', 3, NULL, NULL, NULL, 'Backfilled broker interest share for loan #3', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(5, 5, 'broker_fee', 'broker_fee_earned', 2800.0000, 25600.0000, 'BACKFILL-FEE-L4', 4, NULL, NULL, NULL, 'Backfilled broker interest share for loan #4', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(6, 5, 'broker_fee', 'broker_fee_earned', 2160.0000, 27760.0000, 'BACKFILL-FEE-L12', 12, NULL, NULL, NULL, 'Backfilled broker interest share for loan #12', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(7, 5, 'broker_fee', 'broker_fee_earned', 2400.0000, 30160.0000, 'BACKFILL-FEE-L13', 13, NULL, NULL, NULL, 'Backfilled broker interest share for loan #13', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(8, 5, 'broker_fee', 'broker_fee_earned', 4000.0000, 34160.0000, 'BACKFILL-FEE-L16', 16, NULL, NULL, NULL, 'Backfilled broker interest share for loan #16', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(9, 5, 'broker_fee', 'broker_fee_earned', 1600.0000, 35760.0000, 'BACKFILL-FEE-L17', 17, NULL, NULL, NULL, 'Backfilled broker interest share for loan #17', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(10, 5, 'broker_fee', 'penalty_share', 1200.0000, 36960.0000, 'BACKFILL-PEN-L17', 17, NULL, NULL, NULL, 'Backfilled penalty share (50% of last-cycle penalty collected) for loan #17', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(11, 5, 'broker_fee', 'broker_fee_earned', 2800.0000, 39760.0000, 'BACKFILL-FEE-L18', 18, NULL, NULL, NULL, 'Backfilled broker interest share for loan #18', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(12, 5, 'broker_fee', 'broker_fee_earned', 3360.0000, 43120.0000, 'BACKFILL-FEE-L21', 21, NULL, NULL, NULL, 'Backfilled broker interest share for loan #21', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(13, 5, 'broker_fee', 'penalty_share', 7560.0000, 50680.0000, 'BACKFILL-PEN-L21', 21, NULL, NULL, NULL, 'Backfilled penalty share (50% of last-cycle penalty collected) for loan #21', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(14, 5, 'broker_fee', 'broker_fee_earned', 3520.0000, 54200.0000, 'BACKFILL-FEE-L36', 36, NULL, NULL, NULL, 'Backfilled broker interest share for loan #36', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(15, 5, 'broker_fee', 'broker_fee_earned', 8000.0000, 62200.0000, 'BACKFILL-FEE-L50', 50, NULL, NULL, NULL, 'Backfilled broker interest share for loan #50', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(16, 5, 'broker_fee', 'broker_fee_earned', 800.0000, 63000.0000, 'BACKFILL-FEE-L54', 54, NULL, NULL, NULL, 'Backfilled broker interest share for loan #54', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(17, 5, 'broker_fee', 'broker_fee_earned', 2400.0000, 65400.0000, 'BACKFILL-FEE-L56', 56, NULL, NULL, NULL, 'Backfilled broker interest share for loan #56', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(18, 5, 'broker_fee', 'broker_fee_earned', 3600.0000, 69000.0000, 'BACKFILL-FEE-L57', 57, NULL, NULL, NULL, 'Backfilled broker interest share for loan #57', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(19, 5, 'broker_fee', 'broker_fee_earned', 4000.0000, 73000.0000, 'BACKFILL-FEE-L60', 60, NULL, NULL, NULL, 'Backfilled broker interest share for loan #60', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(20, 5, 'broker_fee', 'broker_fee_earned', 200.0000, 73200.0000, 'BACKFILL-FEE-L66', 66, NULL, NULL, NULL, 'Backfilled broker interest share for loan #66', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(21, 5, 'broker_fee', 'broker_fee_earned', 800.0000, 74000.0000, 'BACKFILL-FEE-L68', 68, NULL, NULL, NULL, 'Backfilled broker interest share for loan #68', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(22, 5, 'broker_fee', 'broker_fee_earned', 4000.0000, 78000.0000, 'BACKFILL-FEE-L74', 74, NULL, NULL, NULL, 'Backfilled broker interest share for loan #74', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(23, 5, 'broker_fee', 'broker_fee_earned', 656.0000, 78656.0000, 'BACKFILL-FEE-L83', 83, NULL, NULL, NULL, 'Backfilled broker interest share for loan #83', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(24, 5, 'broker_fee', 'broker_fee_earned', 8000.0000, 86656.0000, 'BACKFILL-FEE-L85', 85, NULL, NULL, NULL, 'Backfilled broker interest share for loan #85', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(25, 5, 'broker_fee', 'penalty_share', 30000.0000, 116656.0000, 'BACKFILL-PEN-L85', 85, NULL, NULL, NULL, 'Backfilled penalty share (50% of last-cycle penalty collected) for loan #85', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(26, 5, 'broker_fee', 'broker_fee_earned', 662.4000, 117318.4000, 'BACKFILL-FEE-L89', 89, NULL, NULL, NULL, 'Backfilled broker interest share for loan #89', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(27, 5, 'broker_fee', 'broker_fee_earned', 378.5600, 117696.9600, 'BACKFILL-FEE-L106', 106, NULL, NULL, NULL, 'Backfilled broker interest share for loan #106', '2026-10-07', '2026-10-07 13:17:45', NULL, '2026-10-07 11:17:45', '2026-10-07 11:17:45'),
(29, 6, 'principal', 'profit_distribution', 2000.0000, 2000.0000, 'BACKFILL-HOUSE-INT-L5', 5, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #5', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(30, 6, 'principal', 'profit_distribution', 2600.0000, 4600.0000, 'BACKFILL-HOUSE-INT-L6', 6, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #6', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(31, 6, 'principal', 'profit_distribution', 400.0000, 5000.0000, 'BACKFILL-HOUSE-INT-L7', 7, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #7', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(32, 6, 'principal', 'profit_distribution', 2000.0000, 7000.0000, 'BACKFILL-HOUSE-INT-L8', 8, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #8', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(33, 6, 'principal', 'profit_distribution', 3000.0000, 10000.0000, 'BACKFILL-HOUSE-INT-L9', 9, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #9', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(34, 6, 'principal', 'profit_distribution', 6000.0000, 16000.0000, 'BACKFILL-HOUSE-INT-L10', 10, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #10', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(35, 6, 'principal', 'profit_distribution', 3000.0000, 19000.0000, 'BACKFILL-HOUSE-INT-L11', 11, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #11', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(36, 6, 'principal', 'profit_distribution', 4000.0000, 23000.0000, 'BACKFILL-HOUSE-INT-L14', 14, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #14', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(37, 6, 'principal', 'profit_distribution', 10000.0000, 33000.0000, 'BACKFILL-HOUSE-INT-L15', 15, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #15', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(38, 6, 'principal', 'profit_distribution', 4000.0000, 37000.0000, 'BACKFILL-HOUSE-INT-L19', 19, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #19', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(39, 6, 'principal', 'profit_distribution', 6600.0000, 43600.0000, 'BACKFILL-HOUSE-INT-L20', 20, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #20', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(40, 6, 'principal', 'profit_distribution', 1000.0000, 44600.0000, 'BACKFILL-HOUSE-INT-L24', 24, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #24', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(41, 6, 'principal', 'profit_distribution', 2200.0000, 46800.0000, 'BACKFILL-HOUSE-INT-L25', 25, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #25', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(42, 6, 'principal', 'profit_distribution', 120.0000, 46920.0000, 'BACKFILL-HOUSE-INT-L26', 26, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #26', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(43, 6, 'principal', 'profit_distribution', 400.0000, 47320.0000, 'BACKFILL-HOUSE-INT-L27', 27, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #27', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(44, 6, 'principal', 'profit_distribution', 2200.0000, 49520.0000, 'BACKFILL-HOUSE-INT-L29', 29, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #29', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(45, 6, 'principal', 'profit_distribution', 1200.0000, 50720.0000, 'BACKFILL-HOUSE-INT-L30', 30, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #30', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(46, 6, 'principal', 'profit_distribution', 10000.0000, 60720.0000, 'BACKFILL-HOUSE-INT-L31', 31, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #31', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(47, 6, 'principal', 'profit_distribution', 5800.0000, 66520.0000, 'BACKFILL-HOUSE-INT-L32', 32, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #32', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(48, 6, 'principal', 'profit_distribution', 2000.0000, 68520.0000, 'BACKFILL-HOUSE-INT-L33', 33, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #33', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(49, 6, 'principal', 'profit_distribution', 1800.0000, 70320.0000, 'BACKFILL-HOUSE-INT-L34', 34, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #34', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(50, 6, 'principal', 'profit_distribution', 3600.0000, 73920.0000, 'BACKFILL-HOUSE-INT-L35', 35, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #35', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(51, 6, 'principal', 'profit_distribution', 1000.0000, 74920.0000, 'BACKFILL-HOUSE-INT-L37', 37, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #37', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(52, 6, 'principal', 'profit_distribution', 10000.0000, 84920.0000, 'BACKFILL-HOUSE-INT-L38', 38, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #38', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(53, 6, 'principal', 'profit_distribution', 3000.0000, 87920.0000, 'BACKFILL-HOUSE-INT-L39', 39, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #39', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(54, 6, 'principal', 'profit_distribution', 3120.0000, 91040.0000, 'BACKFILL-HOUSE-INT-L40', 40, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #40', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(55, 6, 'principal', 'profit_distribution', 1600.0000, 92640.0000, 'BACKFILL-HOUSE-INT-L41', 41, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #41', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(56, 6, 'principal', 'profit_distribution', 10000.0000, 102640.0000, 'BACKFILL-HOUSE-INT-L45', 45, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #45', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(57, 6, 'principal', 'profit_distribution', 10000.0000, 112640.0000, 'BACKFILL-HOUSE-INT-L46', 46, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #46', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(58, 6, 'principal', 'profit_distribution', 1000.0000, 113640.0000, 'BACKFILL-HOUSE-INT-L48', 48, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #48', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(59, 6, 'principal', 'profit_distribution', 10000.0000, 123640.0000, 'BACKFILL-HOUSE-INT-L49', 49, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #49', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(60, 6, 'principal', 'profit_distribution', 3120.0000, 126760.0000, 'BACKFILL-HOUSE-INT-L51', 51, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #51', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(61, 6, 'principal', 'profit_distribution', 3000.0000, 129760.0000, 'BACKFILL-HOUSE-INT-L55', 55, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #55', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(62, 6, 'principal', 'profit_distribution', 15600.0000, 145360.0000, 'BACKFILL-HOUSE-INT-L59', 59, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #59', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(63, 6, 'principal', 'profit_distribution', 14400.0000, 159760.0000, 'BACKFILL-HOUSE-INT-L61', 61, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #61', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(64, 6, 'principal', 'profit_distribution', 3744.0000, 163504.0000, 'BACKFILL-HOUSE-INT-L64', 64, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #64', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(65, 6, 'principal', 'profit_distribution', 2600.0000, 166104.0000, 'BACKFILL-HOUSE-INT-L65', 65, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #65', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(66, 6, 'principal', 'profit_distribution', 10000.0000, 176104.0000, 'BACKFILL-HOUSE-INT-L69', 69, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #69', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(67, 6, 'principal', 'profit_distribution', 10000.0000, 186104.0000, 'BACKFILL-HOUSE-INT-L70', 70, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #70', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(68, 6, 'principal', 'profit_distribution', 3000.0000, 189104.0000, 'BACKFILL-HOUSE-INT-L71', 71, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #71', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(69, 6, 'principal', 'profit_distribution', 2000.0000, 191104.0000, 'BACKFILL-HOUSE-INT-L73', 73, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #73', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(70, 6, 'principal', 'profit_distribution', 3000.0000, 194104.0000, 'BACKFILL-HOUSE-INT-L75', 75, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #75', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(71, 6, 'principal', 'profit_distribution', 3000.0000, 197104.0000, 'BACKFILL-HOUSE-INT-L76', 76, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #76', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(72, 6, 'principal', 'profit_distribution', 6000.0000, 203104.0000, 'BACKFILL-HOUSE-INT-L77', 77, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #77', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(73, 6, 'principal', 'profit_distribution', 95000.0000, 298104.0000, 'BACKFILL-HOUSE-INT-L78', 78, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #78', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(74, 6, 'principal', 'profit_distribution', 7000.0000, 305104.0000, 'BACKFILL-HOUSE-INT-L79', 79, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #79', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(75, 6, 'principal', 'profit_distribution', 9000.0000, 314104.0000, 'BACKFILL-HOUSE-INT-L80', 80, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #80', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(76, 6, 'principal', 'profit_distribution', 9000.0000, 323104.0000, 'BACKFILL-HOUSE-INT-L82', 82, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #82', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(77, 6, 'principal', 'profit_distribution', 7000.0000, 330104.0000, 'BACKFILL-HOUSE-INT-L84', 84, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #84', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(78, 6, 'principal', 'profit_distribution', 2000.0000, 332104.0000, 'BACKFILL-HOUSE-INT-L86', 86, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #86', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(79, 6, 'principal', 'profit_distribution', 10000.0000, 342104.0000, 'BACKFILL-HOUSE-INT-L87', 87, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #87', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(80, 6, 'principal', 'profit_distribution', 5600.0000, 347704.0000, 'BACKFILL-HOUSE-INT-L88', 88, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #88', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(81, 6, 'principal', 'profit_distribution', 7920.0000, 355624.0000, 'BACKFILL-HOUSE-INT-L90', 90, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #90', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(82, 6, 'principal', 'profit_distribution', 3000.0000, 358624.0000, 'BACKFILL-HOUSE-INT-L91', 91, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #91', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(83, 6, 'principal', 'profit_distribution', 2000.0000, 360624.0000, 'BACKFILL-HOUSE-INT-L92', 92, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #92', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(84, 6, 'principal', 'profit_distribution', 3400.0000, 364024.0000, 'BACKFILL-HOUSE-INT-L93', 93, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #93', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(85, 6, 'principal', 'profit_distribution', 1000.0000, 365024.0000, 'BACKFILL-HOUSE-INT-L94', 94, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #94', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(86, 6, 'principal', 'profit_distribution', 452.8000, 365476.8000, 'BACKFILL-HOUSE-INT-L95', 95, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #95', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(87, 6, 'principal', 'profit_distribution', 16224.0000, 381700.8000, 'BACKFILL-HOUSE-INT-L96', 96, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #96', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(88, 6, 'principal', 'profit_distribution', 10000.0000, 391700.8000, 'BACKFILL-HOUSE-INT-L97', 97, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #97', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(89, 6, 'principal', 'profit_distribution', 5600.0000, 397300.8000, 'BACKFILL-HOUSE-INT-L98', 98, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #98', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(90, 6, 'principal', 'profit_distribution', 1000.0000, 398300.8000, 'BACKFILL-HOUSE-INT-L100', 100, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #100', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(91, 6, 'principal', 'profit_distribution', 2000.0000, 400300.8000, 'BACKFILL-HOUSE-INT-L101', 101, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #101', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(92, 6, 'principal', 'profit_distribution', 2000.0000, 402300.8000, 'BACKFILL-HOUSE-INT-L102', 102, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #102', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(93, 6, 'principal', 'profit_distribution', 1000.0000, 403300.8000, 'BACKFILL-HOUSE-INT-L103', 103, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #103', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(94, 6, 'principal', 'profit_distribution', 1000.0000, 404300.8000, 'BACKFILL-HOUSE-INT-L104', 104, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #104', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(95, 6, 'principal', 'profit_distribution', 24000.0000, 428300.8000, 'BACKFILL-HOUSE-INT-L105', 105, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #105', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(96, 6, 'principal', 'profit_distribution', 3000.0000, 431300.8000, 'BACKFILL-HOUSE-INT-L107', 107, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #107', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(97, 6, 'principal', 'profit_distribution', 2000.0000, 433300.8000, 'BACKFILL-HOUSE-INT-L108', 108, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #108', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(98, 6, 'principal', 'profit_distribution', 2400.0000, 435700.8000, 'BACKFILL-HOUSE-INT-L109', 109, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #109', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(99, 6, 'principal', 'profit_distribution', 2000.0000, 437700.8000, 'BACKFILL-HOUSE-INT-L110', 110, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #110', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(100, 6, 'principal', 'profit_distribution', 1600.0000, 439300.8000, 'BACKFILL-HOUSE-INT-L111', 111, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #111', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(101, 6, 'principal', 'profit_distribution', 2000.0000, 441300.8000, 'BACKFILL-HOUSE-INT-L112', 112, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #112', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(102, 6, 'principal', 'profit_distribution', 2000.0000, 443300.8000, 'BACKFILL-HOUSE-INT-L113', 113, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #113', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(103, 6, 'principal', 'profit_distribution', 1200.0000, 444500.8000, 'BACKFILL-HOUSE-INT-L114', 114, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #114', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(104, 6, 'principal', 'profit_distribution', 29203.2000, 473704.0000, 'BACKFILL-HOUSE-INT-L115', 115, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #115', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(105, 6, 'principal', 'profit_distribution', 11700.0000, 485404.0000, 'BACKFILL-HOUSE-INT-L116', 116, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #116', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(106, 6, 'principal', 'profit_distribution', 24000.0000, 509404.0000, 'BACKFILL-HOUSE-INT-L117', 117, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #117', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(107, 6, 'principal', 'profit_distribution', 400.0000, 509804.0000, 'BACKFILL-HOUSE-INT-L118', 118, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #118', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(108, 6, 'principal', 'profit_distribution', 200.0000, 510004.0000, 'BACKFILL-HOUSE-INT-L119', 119, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #119', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(109, 6, 'principal', 'profit_distribution', 5000.0000, 515004.0000, 'BACKFILL-HOUSE-INT-L120', 120, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #120', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(110, 6, 'principal', 'profit_distribution', 14000.0000, 529004.0000, 'BACKFILL-HOUSE-INT-L121', 121, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #121', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(111, 6, 'principal', 'profit_distribution', 2880.0000, 531884.0000, 'BACKFILL-HOUSE-INT-L122', 122, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #122', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(112, 6, 'principal', 'profit_distribution', 8580.0000, 540464.0000, 'BACKFILL-HOUSE-INT-L123', 123, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #123', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(113, 6, 'principal', 'profit_distribution', 11325.6000, 551789.6000, 'BACKFILL-HOUSE-INT-L124', 124, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #124', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(114, 6, 'principal', 'profit_distribution', 2000.0000, 553789.6000, 'BACKFILL-HOUSE-INT-L125', 125, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #125', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(115, 6, 'principal', 'profit_distribution', 1000.0000, 554789.6000, 'BACKFILL-HOUSE-INT-L126', 126, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #126', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(116, 6, 'principal', 'profit_distribution', 4000.0000, 558789.6000, 'BACKFILL-HOUSE-INT-L127', 127, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #127', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(117, 6, 'principal', 'profit_distribution', 2000.0000, 560789.6000, 'BACKFILL-HOUSE-INT-L128', 128, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #128', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(118, 6, 'principal', 'profit_distribution', 1440.0000, 562229.6000, 'BACKFILL-HOUSE-INT-L129', 129, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #129', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(119, 6, 'principal', 'profit_distribution', 3456.0000, 565685.6000, 'BACKFILL-HOUSE-INT-L130', 130, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #130', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(120, 6, 'principal', 'profit_distribution', 800.0000, 566485.6000, 'BACKFILL-HOUSE-INT-L131', 131, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #131', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(121, 6, 'principal', 'profit_distribution', 3000.0000, 569485.6000, 'BACKFILL-HOUSE-INT-L134', 134, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #134', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(122, 6, 'principal', 'profit_distribution', 480.0000, 569965.6000, 'BACKFILL-HOUSE-INT-L135', 135, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #135', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(123, 6, 'principal', 'profit_distribution', 2400.0000, 572365.6000, 'BACKFILL-HOUSE-INT-L136', 136, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #136', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(124, 6, 'principal', 'profit_distribution', 7500.0000, 579865.6000, 'BACKFILL-HOUSE-INT-L137', 137, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #137', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(125, 6, 'principal', 'profit_distribution', 9000.0000, 588865.6000, 'BACKFILL-HOUSE-INT-L138', 138, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #138', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(126, 6, 'principal', 'profit_distribution', 3000.0000, 591865.6000, 'BACKFILL-HOUSE-INT-L139', 139, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #139', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(127, 6, 'principal', 'profit_distribution', 600.0000, 592465.6000, 'BACKFILL-HOUSE-INT-L140', 140, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #140', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(128, 6, 'principal', 'profit_distribution', 1200.0000, 593665.6000, 'BACKFILL-HOUSE-INT-L141', 141, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #141', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(129, 6, 'principal', 'profit_distribution', 600.0000, 594265.6000, 'BACKFILL-HOUSE-INT-L142', 142, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #142', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(130, 6, 'principal', 'profit_distribution', 6000.0000, 600265.6000, 'BACKFILL-HOUSE-INT-L143', 143, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #143', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(131, 6, 'principal', 'profit_distribution', 60000.0000, 660265.6000, 'BACKFILL-HOUSE-INT-L144', 144, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #144', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(132, 6, 'principal', 'profit_distribution', 600.0000, 660865.6000, 'BACKFILL-HOUSE-INT-L145', 145, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #145', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(133, 6, 'principal', 'profit_distribution', 3280.0000, 664145.6000, 'BACKFILL-HOUSE-INT-L146', 146, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #146', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(134, 6, 'principal', 'profit_distribution', 13590.7200, 677736.3200, 'BACKFILL-HOUSE-INT-L147', 147, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #147', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(135, 6, 'principal', 'profit_distribution', 3000.0000, 680736.3200, 'BACKFILL-HOUSE-INT-L148', 148, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #148', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(136, 6, 'principal', 'profit_distribution', 2000.0000, 682736.3200, 'BACKFILL-HOUSE-INT-L149', 149, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #149', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(137, 6, 'principal', 'profit_distribution', 13309.0000, 696045.3200, 'BACKFILL-HOUSE-INT-L151', 151, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #151', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(138, 6, 'principal', 'profit_distribution', 15210.0000, 711255.3200, 'BACKFILL-HOUSE-INT-L152', 152, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #152', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(139, 6, 'principal', 'profit_distribution', 2000.0000, 713255.3200, 'BACKFILL-HOUSE-INT-L156', 156, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #156', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(140, 6, 'principal', 'profit_distribution', 320.0000, 713575.3200, 'BACKFILL-HOUSE-INT-L158', 158, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #158', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(141, 6, 'principal', 'profit_distribution', 1200.0000, 714775.3200, 'BACKFILL-HOUSE-INT-L159', 159, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #159', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(142, 6, 'principal', 'profit_distribution', 10000.0000, 724775.3200, 'BACKFILL-HOUSE-INT-L161', 161, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #161', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(143, 6, 'principal', 'profit_distribution', 384.0000, 725159.3200, 'BACKFILL-HOUSE-INT-L162', 162, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #162', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(144, 6, 'principal', 'profit_distribution', 3936.0000, 729095.3200, 'BACKFILL-HOUSE-INT-L163', 163, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #163', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(145, 6, 'principal', 'profit_distribution', 3600.0000, 732695.3200, 'BACKFILL-HOUSE-INT-L164', 164, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #164', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(146, 6, 'principal', 'profit_distribution', 3000.0000, 735695.3200, 'BACKFILL-HOUSE-INT-L165', 165, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #165', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(147, 6, 'principal', 'profit_distribution', 1440.0000, 737135.3200, 'BACKFILL-HOUSE-INT-L166', 166, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #166', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(148, 6, 'principal', 'profit_distribution', 10000.0000, 747135.3200, 'BACKFILL-HOUSE-INT-L167', 167, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #167', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(149, 6, 'principal', 'profit_distribution', 3000.0000, 750135.3200, 'BACKFILL-HOUSE-INT-L168', 168, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #168', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(150, 6, 'principal', 'profit_distribution', 8400.0000, 758535.3200, 'BACKFILL-HOUSE-INT-L169', 169, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #169', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(151, 6, 'principal', 'profit_distribution', 3000.0000, 761535.3200, 'BACKFILL-HOUSE-INT-L170', 170, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #170', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(152, 6, 'principal', 'profit_distribution', 10728.0000, 772263.3200, 'BACKFILL-HOUSE-INT-L171', 171, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #171', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(153, 6, 'principal', 'profit_distribution', 3600.0000, 775863.3200, 'BACKFILL-HOUSE-INT-L172', 172, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #172', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(154, 6, 'principal', 'profit_distribution', 1440.0000, 777303.3200, 'BACKFILL-HOUSE-INT-L173', 173, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #173', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(155, 6, 'principal', 'profit_distribution', 3000.0000, 780303.3200, 'BACKFILL-HOUSE-INT-L174', 174, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #174', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(156, 6, 'principal', 'profit_distribution', 2400.0000, 782703.3200, 'BACKFILL-HOUSE-INT-L175', 175, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #175', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(157, 6, 'principal', 'profit_distribution', 4723.2000, 787426.5200, 'BACKFILL-HOUSE-INT-L176', 176, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #176', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(158, 6, 'principal', 'profit_distribution', 19773.0000, 807199.5200, 'BACKFILL-HOUSE-INT-L178', 178, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #178', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(159, 6, 'principal', 'profit_distribution', 5668.0000, 812867.5200, 'BACKFILL-HOUSE-INT-L179', 179, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #179', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(160, 6, 'principal', 'profit_distribution', 25000.0000, 837867.5200, 'BACKFILL-HOUSE-INT-L180', 180, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #180', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(161, 6, 'principal', 'profit_distribution', 8000.0000, 845867.5200, 'BACKFILL-HOUSE-INT-L181', 181, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #181', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(162, 6, 'principal', 'profit_distribution', 4320.0000, 850187.5200, 'BACKFILL-HOUSE-INT-L182', 182, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #182', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(163, 6, 'principal', 'profit_distribution', 3600.0000, 853787.5200, 'BACKFILL-HOUSE-INT-L183', 183, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #183', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(164, 6, 'principal', 'profit_distribution', 2000.0000, 855787.5200, 'BACKFILL-HOUSE-INT-L184', 184, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #184', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(165, 6, 'principal', 'profit_distribution', 4320.0000, 860107.5200, 'BACKFILL-HOUSE-INT-L185', 185, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #185', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(166, 6, 'principal', 'profit_distribution', 75000.0000, 935107.5200, 'BACKFILL-HOUSE-INT-L186', 186, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #186', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(167, 6, 'principal', 'profit_distribution', 8000.0000, 943107.5200, 'BACKFILL-HOUSE-INT-L187', 187, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #187', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(168, 6, 'principal', 'profit_distribution', 200.0000, 943307.5200, 'BACKFILL-HOUSE-INT-L188', 188, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #188', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(169, 6, 'principal', 'profit_distribution', 27200.0000, 970507.5200, 'BACKFILL-HOUSE-INT-L189', 189, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #189', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(170, 6, 'principal', 'profit_distribution', 2000.0000, 972507.5200, 'BACKFILL-HOUSE-INT-L190', 190, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #190', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(171, 6, 'principal', 'profit_distribution', 6801.6000, 979309.1200, 'BACKFILL-HOUSE-INT-L191', 191, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #191', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(172, 6, 'principal', 'profit_distribution', 8000.0000, 987309.1200, 'BACKFILL-HOUSE-INT-L192', 192, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #192', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(173, 6, 'principal', 'profit_distribution', 35600.0000, 1022909.1200, 'BACKFILL-HOUSE-INT-L193', 193, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #193', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(174, 6, 'principal', 'profit_distribution', 2000.0000, 1024909.1200, 'BACKFILL-HOUSE-INT-L194', 194, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #194', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(175, 6, 'principal', 'profit_distribution', 2400.0000, 1027309.1200, 'BACKFILL-HOUSE-INT-L195', 195, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #195', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(176, 6, 'principal', 'profit_distribution', 1700.0000, 1029009.1200, 'BACKFILL-HOUSE-INT-L196', 196, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #196', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(177, 6, 'principal', 'profit_distribution', 2300.0000, 1031309.1200, 'BACKFILL-HOUSE-INT-L197', 197, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #197', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(178, 6, 'principal', 'profit_distribution', 10000.0000, 1041309.1200, 'BACKFILL-HOUSE-INT-L198', 198, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #198', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(179, 6, 'principal', 'profit_distribution', 2400.0000, 1043709.1200, 'BACKFILL-HOUSE-INT-L199', 199, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #199', '2026-10-07', '2026-10-07 14:40:04', NULL, '2026-10-07 12:40:04', '2026-10-07 12:40:04'),
(180, 6, 'principal', 'profit_distribution', 1000.0000, 1044709.1200, 'BACKFILL-HOUSE-INT-L201', 201, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #201', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(181, 6, 'principal', 'profit_distribution', 2000.0000, 1046709.1200, 'BACKFILL-HOUSE-INT-L202', 202, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #202', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(182, 6, 'principal', 'profit_distribution', 10873.6000, 1057582.7200, 'BACKFILL-HOUSE-INT-L203', 203, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #203', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(183, 6, 'principal', 'profit_distribution', 3000.0000, 1060582.7200, 'BACKFILL-HOUSE-INT-L204', 204, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #204', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(184, 6, 'principal', 'profit_distribution', 10000.0000, 1070582.7200, 'BACKFILL-HOUSE-INT-L205', 205, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #205', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(185, 6, 'principal', 'profit_distribution', 240.0000, 1070822.7200, 'BACKFILL-HOUSE-INT-L206', 206, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #206', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(186, 6, 'principal', 'profit_distribution', 6000.0000, 1076822.7200, 'BACKFILL-HOUSE-INT-L207', 207, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #207', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(187, 6, 'principal', 'profit_distribution', 6161.9200, 1082984.6400, 'BACKFILL-HOUSE-INT-L209', 209, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #209', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(188, 6, 'principal', 'profit_distribution', 4000.0000, 1086984.6400, 'BACKFILL-HOUSE-INT-L211', 211, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #211', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(189, 6, 'principal', 'profit_distribution', 20000.0000, 1106984.6400, 'BACKFILL-HOUSE-INT-L212', 212, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #212', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(190, 6, 'principal', 'profit_distribution', 2000.0000, 1108984.6400, 'BACKFILL-HOUSE-INT-L213', 213, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #213', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(191, 6, 'principal', 'profit_distribution', 14400.0000, 1123384.6400, 'BACKFILL-HOUSE-INT-L214', 214, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #214', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(192, 6, 'principal', 'profit_distribution', 2000.0000, 1125384.6400, 'BACKFILL-HOUSE-INT-L215', 215, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #215', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(193, 6, 'principal', 'profit_distribution', 6000.0000, 1131384.6400, 'BACKFILL-HOUSE-INT-L216', 216, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #216', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(194, 6, 'principal', 'profit_distribution', 740.0000, 1132124.6400, 'BACKFILL-HOUSE-INT-L219', 219, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #219', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(195, 6, 'principal', 'profit_distribution', 20000.0000, 1152124.6400, 'BACKFILL-HOUSE-INT-L220', 220, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #220', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(196, 6, 'principal', 'profit_distribution', 11530.0000, 1163654.6400, 'BACKFILL-HOUSE-INT-L221', 221, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #221', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05');
INSERT INTO `partner_transactions` (`id`, `partner_id`, `wallet_slug`, `type`, `amount`, `balance_after`, `reference`, `loan_id`, `repayment_id`, `investment_id`, `investment_funding_id`, `notes`, `transaction_date`, `posted_at`, `created_by`, `created_at`, `updated_at`) VALUES
(197, 6, 'principal', 'profit_distribution', 5000.0000, 1168654.6400, 'BACKFILL-HOUSE-INT-L222', 222, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #222', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(198, 6, 'principal', 'profit_distribution', 2000.0000, 1170654.6400, 'BACKFILL-HOUSE-INT-L224', 224, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #224', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(199, 6, 'principal', 'profit_distribution', 30000.0000, 1200654.6400, 'BACKFILL-HOUSE-INT-L225', 225, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #225', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(200, 6, 'principal', 'profit_distribution', 5610.0000, 1206264.6400, 'BACKFILL-HOUSE-INT-L226', 226, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #226', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(201, 6, 'principal', 'profit_distribution', 2000.0000, 1208264.6400, 'BACKFILL-HOUSE-INT-L227', 227, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #227', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(202, 6, 'principal', 'profit_distribution', 2000.0000, 1210264.6400, 'BACKFILL-HOUSE-INT-L228', 228, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #228', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(203, 6, 'principal', 'profit_distribution', 5000.0000, 1215264.6400, 'BACKFILL-HOUSE-INT-L229', 229, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #229', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(204, 6, 'principal', 'profit_distribution', 600.0000, 1215864.6400, 'BACKFILL-HOUSE-INT-L230', 230, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #230', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(205, 6, 'principal', 'profit_distribution', 2400.0000, 1218264.6400, 'BACKFILL-HOUSE-INT-L231', 231, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #231', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(206, 6, 'principal', 'profit_distribution', 4000.0000, 1222264.6400, 'BACKFILL-HOUSE-INT-L232', 232, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #232', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(207, 6, 'principal', 'profit_distribution', 7048.3200, 1229312.9600, 'BACKFILL-HOUSE-INT-L233', 233, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #233', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(208, 6, 'principal', 'profit_distribution', 13200.0000, 1242512.9600, 'BACKFILL-HOUSE-INT-L234', 234, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #234', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(209, 6, 'principal', 'profit_distribution', 15840.0000, 1258352.9600, 'BACKFILL-HOUSE-INT-L235', 235, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #235', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(210, 6, 'principal', 'profit_distribution', 7200.0000, 1265552.9600, 'BACKFILL-HOUSE-INT-L236', 236, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #236', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(211, 6, 'principal', 'profit_distribution', 8640.0000, 1274192.9600, 'BACKFILL-HOUSE-INT-L237', 237, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #237', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(212, 6, 'principal', 'profit_distribution', 2400.0000, 1276592.9600, 'BACKFILL-HOUSE-INT-L238', 238, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #238', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(213, 6, 'principal', 'profit_distribution', 5000.0000, 1281592.9600, 'BACKFILL-HOUSE-INT-L239', 239, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #239', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(214, 6, 'principal', 'profit_distribution', 2880.0000, 1284472.9600, 'BACKFILL-HOUSE-INT-L240', 240, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #240', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(215, 6, 'principal', 'profit_distribution', 2000.0000, 1286472.9600, 'BACKFILL-HOUSE-INT-L241', 241, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #241', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(216, 6, 'principal', 'profit_distribution', 6000.0000, 1292472.9600, 'BACKFILL-HOUSE-INT-L242', 242, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #242', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(217, 6, 'principal', 'profit_distribution', 540.0000, 1293012.9600, 'BACKFILL-HOUSE-INT-L243', 243, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #243', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(218, 6, 'principal', 'profit_distribution', 120000.0000, 1413012.9600, 'BACKFILL-HOUSE-INT-L244', 244, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #244', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(219, 6, 'principal', 'profit_distribution', 4000.0000, 1417012.9600, 'BACKFILL-HOUSE-INT-L245', 245, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #245', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(220, 6, 'principal', 'profit_distribution', 4000.0000, 1421012.9600, 'BACKFILL-HOUSE-INT-L246', 246, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #246', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(221, 6, 'principal', 'profit_distribution', 19008.0000, 1440020.9600, 'BACKFILL-HOUSE-INT-L247', 247, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #247', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(222, 6, 'principal', 'profit_distribution', 2880.0000, 1442900.9600, 'BACKFILL-HOUSE-INT-L248', 248, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #248', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(223, 6, 'principal', 'profit_distribution', 5000.0000, 1447900.9600, 'BACKFILL-HOUSE-INT-L249', 249, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #249', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(224, 6, 'principal', 'profit_distribution', 7609.8000, 1455510.7600, 'BACKFILL-HOUSE-INT-L250', 250, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #250', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(225, 6, 'principal', 'profit_distribution', 8768.0000, 1464278.7600, 'BACKFILL-HOUSE-INT-L251', 251, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #251', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(226, 6, 'principal', 'profit_distribution', 4000.0000, 1468278.7600, 'BACKFILL-HOUSE-INT-L252', 252, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #252', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(227, 6, 'principal', 'profit_distribution', 22809.6000, 1491088.3600, 'BACKFILL-HOUSE-INT-L253', 253, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #253', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(228, 6, 'principal', 'profit_distribution', 20000.0000, 1511088.3600, 'BACKFILL-HOUSE-INT-L254', 254, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #254', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(229, 6, 'principal', 'profit_distribution', 536.0000, 1511624.3600, 'BACKFILL-HOUSE-INT-L255', 255, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #255', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(230, 6, 'principal', 'profit_distribution', 30000.0000, 1541624.3600, 'BACKFILL-HOUSE-INT-L256', 256, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #256', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(231, 6, 'principal', 'profit_distribution', 8458.0000, 1550082.3600, 'BACKFILL-HOUSE-INT-L257', 257, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #257', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(232, 6, 'principal', 'profit_distribution', 39000.0000, 1589082.3600, 'BACKFILL-HOUSE-INT-L258', 258, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #258', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(233, 6, 'principal', 'profit_distribution', 7521.6000, 1596603.9600, 'BACKFILL-HOUSE-INT-L259', 259, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #259', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(234, 6, 'principal', 'profit_distribution', 5000.0000, 1601603.9600, 'BACKFILL-HOUSE-INT-L260', 260, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #260', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(235, 6, 'principal', 'profit_distribution', 1756.0000, 1603359.9600, 'BACKFILL-HOUSE-INT-L261', 261, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #261', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(236, 6, 'principal', 'profit_distribution', 20000.0000, 1623359.9600, 'BACKFILL-HOUSE-INT-L262', 262, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #262', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(237, 6, 'principal', 'profit_distribution', 2107.2000, 1625467.1600, 'BACKFILL-HOUSE-INT-L263', 263, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #263', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(238, 6, 'principal', 'profit_distribution', 2528.6400, 1627995.8000, 'BACKFILL-HOUSE-INT-L264', 264, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #264', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(239, 6, 'principal', 'profit_distribution', 9025.9200, 1637021.7200, 'BACKFILL-HOUSE-INT-L265', 265, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #265', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(240, 6, 'principal', 'profit_distribution', 1000.0000, 1638021.7200, 'BACKFILL-HOUSE-INT-L267', 267, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #267', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(241, 6, 'principal', 'profit_distribution', 1000.0000, 1639021.7200, 'BACKFILL-HOUSE-INT-L268', 268, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #268', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(242, 6, 'principal', 'profit_distribution', 144000.0000, 1783021.7200, 'BACKFILL-HOUSE-INT-L269', 269, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #269', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(243, 6, 'principal', 'profit_distribution', 16000.0000, 1799021.7200, 'BACKFILL-HOUSE-INT-L270', 270, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #270', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(244, 6, 'principal', 'profit_distribution', 4000.0000, 1803021.7200, 'BACKFILL-HOUSE-INT-L272', 272, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #272', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(245, 6, 'principal', 'profit_distribution', 3034.3700, 1806056.0900, 'BACKFILL-HOUSE-INT-L273', 273, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #273', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(246, 6, 'principal', 'profit_distribution', 8000.0000, 1814056.0900, 'BACKFILL-HOUSE-INT-L274', 274, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #274', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(247, 6, 'principal', 'profit_distribution', 20000.0000, 1834056.0900, 'BACKFILL-HOUSE-INT-L275', 275, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #275', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(248, 6, 'principal', 'profit_distribution', 10000.0000, 1844056.0900, 'BACKFILL-HOUSE-INT-L276', 276, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #276', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(249, 6, 'principal', 'profit_distribution', 7000.0000, 1851056.0900, 'BACKFILL-HOUSE-INT-L277', 277, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #277', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(250, 6, 'principal', 'profit_distribution', 30000.0000, 1881056.0900, 'BACKFILL-HOUSE-INT-L278', 278, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #278', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(251, 6, 'principal', 'profit_distribution', 50700.0000, 1931756.0900, 'BACKFILL-HOUSE-INT-L279', 279, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #279', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(252, 6, 'principal', 'profit_distribution', 10149.6000, 1941905.6900, 'BACKFILL-HOUSE-INT-L280', 280, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #280', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(253, 6, 'principal', 'profit_distribution', 1200.0000, 1943105.6900, 'BACKFILL-HOUSE-INT-L281', 281, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #281', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(254, 6, 'principal', 'profit_distribution', 50000.0000, 1993105.6900, 'BACKFILL-HOUSE-INT-L282', 282, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #282', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(255, 6, 'principal', 'profit_distribution', 1600.0000, 1994705.6900, 'BACKFILL-HOUSE-INT-L283', 283, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #283', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(256, 6, 'principal', 'profit_distribution', 3000.0000, 1997705.6900, 'BACKFILL-HOUSE-INT-L285', 285, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #285', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(257, 6, 'principal', 'profit_distribution', 1600.0000, 1999305.6900, 'BACKFILL-HOUSE-INT-L286', 286, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #286', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(258, 6, 'principal', 'profit_distribution', 20000.0000, 2019305.6900, 'BACKFILL-HOUSE-INT-L287', 287, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #287', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(259, 6, 'principal', 'profit_distribution', 72800.0000, 2092105.6900, 'BACKFILL-HOUSE-INT-L288', 288, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #288', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(260, 6, 'principal', 'profit_distribution', 60000.0000, 2152105.6900, 'BACKFILL-HOUSE-INT-L289', 289, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #289', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(261, 6, 'principal', 'profit_distribution', 10000.0000, 2162105.6900, 'BACKFILL-HOUSE-INT-L290', 290, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #290', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(262, 6, 'principal', 'profit_distribution', 19668.0000, 2181773.6900, 'BACKFILL-HOUSE-INT-L291', 291, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #291', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(263, 6, 'principal', 'profit_distribution', 20000.0000, 2201773.6900, 'BACKFILL-HOUSE-INT-L292', 292, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #292', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(264, 6, 'principal', 'profit_distribution', 15120.0000, 2216893.6900, 'BACKFILL-HOUSE-INT-L293', 293, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #293', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(265, 6, 'principal', 'profit_distribution', 3000.0000, 2219893.6900, 'BACKFILL-HOUSE-INT-L299', 299, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #299', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(266, 6, 'principal', 'profit_distribution', 30000.0000, 2249893.6900, 'BACKFILL-HOUSE-INT-L307', 307, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #307', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(267, 6, 'principal', 'profit_distribution', 3000.0000, 2252893.6900, 'BACKFILL-HOUSE-INT-L308', 308, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #308', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(268, 6, 'principal', 'profit_distribution', 2400.0000, 2255293.6900, 'BACKFILL-HOUSE-INT-L309', 309, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #309', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(269, 6, 'principal', 'profit_distribution', 72000.0000, 2327293.6900, 'BACKFILL-HOUSE-INT-L310', 310, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #310', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(270, 6, 'principal', 'profit_distribution', 65910.0000, 2393203.6900, 'BACKFILL-HOUSE-INT-L311', 311, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #311', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(271, 6, 'principal', 'profit_distribution', 12179.5200, 2405383.2100, 'BACKFILL-HOUSE-INT-L312', 312, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #312', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(272, 6, 'principal', 'profit_distribution', 1000.0000, 2406383.2100, 'BACKFILL-HOUSE-INT-L313', 313, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #313', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(273, 6, 'principal', 'profit_distribution', 6000.0000, 2412383.2100, 'BACKFILL-HOUSE-INT-L314', 314, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #314', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(274, 6, 'principal', 'profit_distribution', 10000.0000, 2422383.2100, 'BACKFILL-HOUSE-INT-L315', 315, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #315', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(275, 6, 'principal', 'profit_distribution', 32000.0000, 2454383.2100, 'BACKFILL-HOUSE-INT-L316', 316, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #316', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(276, 6, 'principal', 'profit_distribution', 20000.0000, 2474383.2100, 'BACKFILL-HOUSE-INT-L317', 317, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #317', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(277, 6, 'principal', 'profit_distribution', 800.0000, 2475183.2100, 'BACKFILL-HOUSE-INT-L318', 318, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #318', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(278, 6, 'principal', 'profit_distribution', 8000.0000, 2483183.2100, 'BACKFILL-HOUSE-INT-L319', 319, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #319', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(279, 6, 'principal', 'profit_distribution', 400.0000, 2483583.2100, 'BACKFILL-HOUSE-INT-L320', 320, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #320', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(280, 6, 'principal', 'profit_distribution', 18124.8000, 2501708.0100, 'BACKFILL-HOUSE-INT-L321', 321, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #321', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(281, 6, 'principal', 'profit_distribution', 20000.0000, 2521708.0100, 'BACKFILL-HOUSE-INT-L322', 322, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #322', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(282, 6, 'principal', 'profit_distribution', 38400.0000, 2560108.0100, 'BACKFILL-HOUSE-INT-L323', 323, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #323', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(283, 6, 'principal', 'profit_distribution', 30000.0000, 2590108.0100, 'BACKFILL-HOUSE-INT-L324', 324, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #324', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(284, 6, 'principal', 'profit_distribution', 57122.0000, 2647230.0100, 'BACKFILL-HOUSE-INT-L325', 325, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #325', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(285, 6, 'principal', 'profit_distribution', 700.0000, 2647930.0100, 'BACKFILL-HOUSE-INT-L326', 326, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #326', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(286, 6, 'principal', 'profit_distribution', 3000.0000, 2650930.0100, 'BACKFILL-HOUSE-INT-L327', 327, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #327', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(287, 6, 'principal', 'profit_distribution', 600.0000, 2651530.0100, 'BACKFILL-HOUSE-INT-L328', 328, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #328', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(288, 6, 'principal', 'profit_distribution', 11000.0000, 2662530.0100, 'BACKFILL-HOUSE-INT-L329', 329, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #329', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(289, 6, 'principal', 'profit_distribution', 86400.0000, 2748930.0100, 'BACKFILL-HOUSE-INT-L330', 330, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #330', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(290, 6, 'principal', 'profit_distribution', 4000.0000, 2752930.0100, 'BACKFILL-HOUSE-INT-L332', 332, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #332', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(291, 6, 'principal', 'profit_distribution', 3000.0000, 2755930.0100, 'BACKFILL-HOUSE-INT-L333', 333, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #333', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(292, 6, 'principal', 'profit_distribution', 5000.0000, 2760930.0100, 'BACKFILL-HOUSE-INT-L334', 334, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #334', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(293, 6, 'principal', 'profit_distribution', 25568.4000, 2786498.4100, 'BACKFILL-HOUSE-INT-L335', 335, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #335', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(294, 6, 'principal', 'profit_distribution', 400.0000, 2786898.4100, 'BACKFILL-HOUSE-INT-L336', 336, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #336', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(295, 6, 'principal', 'profit_distribution', 10000.0000, 2796898.4100, 'BACKFILL-HOUSE-INT-L337', 337, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #337', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(296, 6, 'principal', 'profit_distribution', 3600.0000, 2800498.4100, 'BACKFILL-HOUSE-INT-L338', 338, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #338', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(297, 6, 'principal', 'profit_distribution', 7500.0000, 2807998.4100, 'BACKFILL-HOUSE-INT-L339', 339, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #339', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(298, 6, 'principal', 'profit_distribution', 1200.0000, 2809198.4100, 'BACKFILL-HOUSE-INT-L340', 340, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #340', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(299, 6, 'principal', 'profit_distribution', 1440.0000, 2810638.4100, 'BACKFILL-HOUSE-INT-L341', 341, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #341', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(300, 6, 'principal', 'profit_distribution', 1728.0000, 2812366.4100, 'BACKFILL-HOUSE-INT-L342', 342, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #342', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(301, 6, 'principal', 'profit_distribution', 2000.0000, 2814366.4100, 'BACKFILL-HOUSE-INT-L343', 343, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #343', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(302, 6, 'principal', 'profit_distribution', 1000.0000, 2815366.4100, 'BACKFILL-HOUSE-INT-L344', 344, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #344', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(303, 6, 'principal', 'profit_distribution', 6000.0000, 2821366.4100, 'BACKFILL-HOUSE-INT-L345', 345, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #345', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(304, 6, 'principal', 'profit_distribution', 1000.0000, 2822366.4100, 'BACKFILL-HOUSE-INT-L346', 346, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #346', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(305, 6, 'principal', 'profit_distribution', 10000.0000, 2832366.4100, 'BACKFILL-HOUSE-INT-L347', 347, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #347', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(306, 6, 'principal', 'profit_distribution', 6866.3900, 2839232.8000, 'BACKFILL-HOUSE-INT-L349', 349, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #349', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(307, 6, 'principal', 'profit_distribution', 1600.0000, 2840832.8000, 'BACKFILL-HOUSE-INT-L350', 350, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #350', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(308, 6, 'principal', 'profit_distribution', 2000.0000, 2842832.8000, 'BACKFILL-HOUSE-INT-L351', 351, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #351', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(309, 6, 'principal', 'profit_distribution', 300.0000, 2843132.8000, 'BACKFILL-HOUSE-INT-L352', 352, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #352', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(310, 6, 'principal', 'profit_distribution', 2000.0000, 2845132.8000, 'BACKFILL-HOUSE-INT-L353', 353, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #353', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(311, 6, 'principal', 'profit_distribution', 4000.0000, 2849132.8000, 'BACKFILL-HOUSE-INT-L354', 354, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #354', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(312, 6, 'principal', 'profit_distribution', 200.0000, 2849332.8000, 'BACKFILL-HOUSE-INT-L355', 355, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #355', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(313, 6, 'principal', 'profit_distribution', 1000.0000, 2850332.8000, 'BACKFILL-HOUSE-INT-L356', 356, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #356', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(314, 6, 'principal', 'profit_distribution', 9000.0000, 2859332.8000, 'BACKFILL-HOUSE-INT-L357', 357, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #357', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(315, 6, 'principal', 'profit_distribution', 2000.0000, 2861332.8000, 'BACKFILL-HOUSE-INT-L358', 358, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #358', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(316, 6, 'principal', 'profit_distribution', 1000.0000, 2862332.8000, 'BACKFILL-HOUSE-INT-L359', 359, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #359', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(317, 6, 'principal', 'profit_distribution', 20000.0000, 2882332.8000, 'BACKFILL-HOUSE-INT-L360', 360, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #360', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(318, 6, 'principal', 'profit_distribution', 640.0000, 2882972.8000, 'BACKFILL-HOUSE-INT-L361', 361, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #361', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(319, 6, 'principal', 'profit_distribution', 1000.0000, 2883972.8000, 'BACKFILL-HOUSE-INT-L362', 362, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #362', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(320, 6, 'principal', 'profit_distribution', 5000.0000, 2888972.8000, 'BACKFILL-HOUSE-INT-L363', 363, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #363', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(321, 6, 'principal', 'profit_distribution', 14615.4200, 2903588.2200, 'BACKFILL-HOUSE-INT-L364', 364, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #364', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(322, 6, 'principal', 'profit_distribution', 5000.0000, 2908588.2200, 'BACKFILL-HOUSE-INT-L365', 365, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #365', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(323, 6, 'principal', 'profit_distribution', 840.0000, 2909428.2200, 'BACKFILL-HOUSE-INT-L366', 366, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #366', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(324, 6, 'principal', 'profit_distribution', 1008.0000, 2910436.2200, 'BACKFILL-HOUSE-INT-L367', 367, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #367', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(325, 6, 'principal', 'profit_distribution', 2073.6000, 2912509.8200, 'BACKFILL-HOUSE-INT-L368', 368, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #368', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(326, 6, 'principal', 'profit_distribution', 2000.0000, 2914509.8200, 'BACKFILL-HOUSE-INT-L369', 369, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #369', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(327, 6, 'principal', 'profit_distribution', 4800.0000, 2919309.8200, 'BACKFILL-HOUSE-INT-L370', 370, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #370', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(328, 6, 'principal', 'profit_distribution', 300.0000, 2919609.8200, 'BACKFILL-HOUSE-INT-L371', 371, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #371', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(329, 6, 'principal', 'profit_distribution', 1000.0000, 2920609.8200, 'BACKFILL-HOUSE-INT-L372', 372, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #372', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(330, 6, 'principal', 'profit_distribution', 608.0000, 2921217.8200, 'BACKFILL-HOUSE-INT-L373', 373, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #373', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(331, 6, 'principal', 'profit_distribution', 68546.4000, 2989764.2200, 'BACKFILL-HOUSE-INT-L374', 374, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #374', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(332, 6, 'principal', 'profit_distribution', 3697.8000, 2993462.0200, 'BACKFILL-HOUSE-INT-L375', 375, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #375', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(333, 6, 'principal', 'profit_distribution', 4437.3600, 2997899.3800, 'BACKFILL-HOUSE-INT-L376', 376, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #376', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(334, 6, 'principal', 'profit_distribution', 2000.0000, 2999899.3800, 'BACKFILL-HOUSE-INT-L377', 377, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #377', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(335, 6, 'principal', 'profit_distribution', 1500.0000, 3001399.3800, 'BACKFILL-HOUSE-INT-L378', 378, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #378', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(336, 6, 'principal', 'profit_distribution', 2000.0000, 3003399.3800, 'BACKFILL-HOUSE-INT-L379', 379, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #379', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(337, 6, 'principal', 'profit_distribution', 39600.0000, 3042999.3800, 'BACKFILL-HOUSE-INT-L380', 380, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #380', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(338, 6, 'principal', 'profit_distribution', 2000.0000, 3044999.3800, 'BACKFILL-HOUSE-INT-L381', 381, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #381', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(339, 6, 'principal', 'profit_distribution', 6000.0000, 3050999.3800, 'BACKFILL-HOUSE-INT-L382', 382, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #382', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(340, 6, 'principal', 'profit_distribution', 33238.9200, 3084238.3000, 'BACKFILL-HOUSE-INT-L383', 383, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #383', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(341, 6, 'principal', 'profit_distribution', 1200.0000, 3085438.3000, 'BACKFILL-HOUSE-INT-L384', 384, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #384', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(342, 6, 'principal', 'profit_distribution', 4000.0000, 3089438.3000, 'BACKFILL-HOUSE-INT-L385', 385, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #385', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(343, 6, 'principal', 'profit_distribution', 200.0000, 3089638.3000, 'BACKFILL-HOUSE-INT-L386', 386, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #386', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(344, 6, 'principal', 'profit_distribution', 2000.0000, 3091638.3000, 'BACKFILL-HOUSE-INT-L387', 387, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #387', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(345, 6, 'principal', 'profit_distribution', 2000.0000, 3093638.3000, 'BACKFILL-HOUSE-INT-L388', 388, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #388', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(346, 6, 'principal', 'profit_distribution', 1000.0000, 3094638.3000, 'BACKFILL-HOUSE-INT-L389', 389, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #389', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(347, 6, 'principal', 'profit_distribution', 600.0000, 3095238.3000, 'BACKFILL-HOUSE-INT-L390', 390, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #390', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(348, 6, 'principal', 'profit_distribution', 1440.0000, 3096678.3000, 'BACKFILL-HOUSE-INT-L391', 391, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #391', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(349, 6, 'principal', 'profit_distribution', 1080.0000, 3097758.3000, 'BACKFILL-HOUSE-INT-L392', 392, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #392', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(350, 6, 'principal', 'profit_distribution', 360.0000, 3098118.3000, 'BACKFILL-HOUSE-INT-L393', 393, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #393', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(351, 6, 'principal', 'profit_distribution', 1000.0000, 3099118.3000, 'BACKFILL-HOUSE-INT-L394', 394, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #394', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(352, 6, 'principal', 'profit_distribution', 7000.0000, 3106118.3000, 'BACKFILL-HOUSE-INT-L395', 395, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #395', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(353, 6, 'principal', 'profit_distribution', 10000.0000, 3116118.3000, 'BACKFILL-HOUSE-INT-L396', 396, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #396', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(354, 6, 'principal', 'profit_distribution', 600.0000, 3116718.3000, 'BACKFILL-HOUSE-INT-L397', 397, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #397', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(355, 6, 'principal', 'profit_distribution', 1400.0000, 3118118.3000, 'BACKFILL-HOUSE-INT-L398', 398, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #398', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(356, 6, 'principal', 'profit_distribution', 6250.0000, 3124368.3000, 'BACKFILL-HOUSE-INT-L399', 399, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #399', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(357, 6, 'principal', 'profit_distribution', 720.0000, 3125088.3000, 'BACKFILL-HOUSE-INT-L400', 400, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #400', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(358, 6, 'principal', 'profit_distribution', 15000.0000, 3140088.3000, 'BACKFILL-HOUSE-INT-L401', 401, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #401', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(359, 6, 'principal', 'profit_distribution', 82255.6800, 3222343.9800, 'BACKFILL-HOUSE-INT-L402', 402, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #402', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(360, 6, 'principal', 'profit_distribution', 240.0000, 3222583.9800, 'BACKFILL-HOUSE-INT-L403', 403, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #403', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(361, 6, 'principal', 'profit_distribution', 24000.0000, 3246583.9800, 'BACKFILL-HOUSE-INT-L404', 404, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #404', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(362, 6, 'principal', 'profit_distribution', 1728.0000, 3248311.9800, 'BACKFILL-HOUSE-INT-L405', 405, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #405', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(363, 6, 'principal', 'profit_distribution', 17538.5100, 3265850.4900, 'BACKFILL-HOUSE-INT-L406', 406, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #406', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(364, 6, 'principal', 'profit_distribution', 288.0000, 3266138.4900, 'BACKFILL-HOUSE-INT-L407', 407, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #407', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(365, 6, 'principal', 'profit_distribution', 14250.0000, 3280388.4900, 'BACKFILL-HOUSE-INT-L408', 408, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #408', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(366, 6, 'principal', 'profit_distribution', 43210.6000, 3323599.0900, 'BACKFILL-HOUSE-INT-L409', 409, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #409', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(367, 6, 'principal', 'profit_distribution', 3000.0000, 3326599.0900, 'BACKFILL-HOUSE-INT-L410', 410, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #410', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(368, 6, 'principal', 'profit_distribution', 20000.0000, 3346599.0900, 'BACKFILL-HOUSE-INT-L411', 411, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #411', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(369, 6, 'principal', 'profit_distribution', 7000.0000, 3353599.0900, 'BACKFILL-HOUSE-INT-L412', 412, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #412', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(370, 6, 'principal', 'profit_distribution', 5000.0000, 3358599.0900, 'BACKFILL-HOUSE-INT-L413', 413, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #413', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(371, 6, 'principal', 'profit_distribution', 2000.0000, 3360599.0900, 'BACKFILL-HOUSE-INT-L415', 415, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #415', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(372, 6, 'principal', 'profit_distribution', 372.0000, 3360971.0900, 'BACKFILL-HOUSE-INT-L416', 416, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #416', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(373, 6, 'principal', 'profit_distribution', 864.0000, 3361835.0900, 'BACKFILL-HOUSE-INT-L417', 417, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #417', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(374, 6, 'principal', 'profit_distribution', 6000.0000, 3367835.0900, 'BACKFILL-HOUSE-INT-L418', 418, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #418', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(375, 6, 'principal', 'profit_distribution', 2000.0000, 3369835.0900, 'BACKFILL-HOUSE-INT-L419', 419, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #419', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(376, 6, 'principal', 'profit_distribution', 21046.2100, 3390881.3000, 'BACKFILL-HOUSE-INT-L420', 420, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #420', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(377, 6, 'principal', 'profit_distribution', 4400.0000, 3395281.3000, 'BACKFILL-HOUSE-INT-L421', 421, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #421', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(378, 6, 'principal', 'profit_distribution', 18000.0000, 3413281.3000, 'BACKFILL-HOUSE-INT-L422', 422, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #422', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(379, 6, 'principal', 'profit_distribution', 600.0000, 3413881.3000, 'BACKFILL-HOUSE-INT-L423', 423, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #423', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(380, 6, 'principal', 'profit_distribution', 1036.8000, 3414918.1000, 'BACKFILL-HOUSE-INT-L424', 424, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #424', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(381, 6, 'principal', 'profit_distribution', 345.6000, 3415263.7000, 'BACKFILL-HOUSE-INT-L425', 425, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #425', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(382, 6, 'principal', 'profit_distribution', 3600.0000, 3418863.7000, 'BACKFILL-HOUSE-INT-L426', 426, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #426', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(383, 6, 'principal', 'profit_distribution', 17100.0000, 3435963.7000, 'BACKFILL-HOUSE-INT-L427', 427, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #427', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(384, 6, 'principal', 'profit_distribution', 432.0000, 3436395.7000, 'BACKFILL-HOUSE-INT-L428', 428, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #428', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(385, 6, 'principal', 'profit_distribution', 1800.0000, 3438195.7000, 'BACKFILL-HOUSE-INT-L430', 430, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #430', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(386, 6, 'principal', 'profit_distribution', 600.0000, 3438795.7000, 'BACKFILL-HOUSE-INT-L431', 431, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #431', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(387, 6, 'principal', 'profit_distribution', 20520.0000, 3459315.7000, 'BACKFILL-HOUSE-INT-L432', 432, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #432', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(388, 6, 'principal', 'profit_distribution', 2800.0000, 3462115.7000, 'BACKFILL-HOUSE-INT-L433', 433, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #433', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05');
INSERT INTO `partner_transactions` (`id`, `partner_id`, `wallet_slug`, `type`, `amount`, `balance_after`, `reference`, `loan_id`, `repayment_id`, `investment_id`, `investment_funding_id`, `notes`, `transaction_date`, `posted_at`, `created_by`, `created_at`, `updated_at`) VALUES
(389, 6, 'principal', 'profit_distribution', 4200.0000, 3466315.7000, 'BACKFILL-HOUSE-INT-L434', 434, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #434', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(390, 6, 'principal', 'profit_distribution', 414.7200, 3466730.4200, 'BACKFILL-HOUSE-INT-L435', 435, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #435', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(391, 6, 'principal', 'profit_distribution', 2120.0000, 3468850.4200, 'BACKFILL-HOUSE-INT-L437', 437, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #437', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(392, 6, 'principal', 'profit_distribution', 2760.0000, 3471610.4200, 'BACKFILL-HOUSE-INT-L438', 438, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #438', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(393, 6, 'principal', 'profit_distribution', 3440.0000, 3475050.4200, 'BACKFILL-HOUSE-INT-L439', 439, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #439', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(394, 6, 'principal', 'profit_distribution', 1244.1600, 3476294.5800, 'BACKFILL-HOUSE-INT-L440', 440, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #440', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(395, 6, 'principal', 'profit_distribution', 8400.0000, 3484694.5800, 'BACKFILL-HOUSE-INT-L441', 441, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #441', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(396, 6, 'principal', 'profit_distribution', 98706.8200, 3583401.4000, 'BACKFILL-HOUSE-INT-L442', 442, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #442', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(397, 6, 'principal', 'profit_distribution', 4320.0000, 3587721.4000, 'BACKFILL-HOUSE-INT-L443', 443, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #443', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(398, 6, 'principal', 'profit_distribution', 5184.0000, 3592905.4000, 'BACKFILL-HOUSE-INT-L444', 444, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #444', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(399, 6, 'principal', 'profit_distribution', 497.6600, 3593403.0600, 'BACKFILL-HOUSE-INT-L446', 446, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #446', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(400, 6, 'principal', 'profit_distribution', 1493.0000, 3594896.0600, 'BACKFILL-HOUSE-INT-L447', 447, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #447', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(401, 6, 'principal', 'profit_distribution', 3312.0000, 3598208.0600, 'BACKFILL-HOUSE-INT-L448', 448, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #448', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(402, 6, 'principal', 'profit_distribution', 2544.0000, 3600752.0600, 'BACKFILL-HOUSE-INT-L449', 449, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #449', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(403, 6, 'principal', 'profit_distribution', 108927.2300, 3709679.2900, 'BACKFILL-HOUSE-INT-L450', 450, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #450', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(404, 6, 'principal', 'profit_distribution', 2000.0000, 3711679.2900, 'BACKFILL-HOUSE-INT-L451', 451, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #451', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(405, 6, 'principal', 'profit_distribution', 597.2000, 3712276.4900, 'BACKFILL-HOUSE-INT-L452', 452, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #452', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(406, 6, 'principal', 'profit_distribution', 2000.0000, 3714276.4900, 'BACKFILL-HOUSE-INT-L453', 453, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #453', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(407, 6, 'principal', 'profit_distribution', 10000.0000, 3724276.4900, 'BACKFILL-HOUSE-INT-L454', 454, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #454', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(408, 6, 'principal', 'profit_distribution', 2000.0000, 3726276.4900, 'BACKFILL-HOUSE-INT-L455', 455, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #455', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(409, 6, 'principal', 'profit_distribution', 25255.4600, 3751531.9500, 'BACKFILL-HOUSE-INT-L456', 456, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #456', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(410, 6, 'principal', 'profit_distribution', 6174.4000, 3757706.3500, 'BACKFILL-HOUSE-INT-L457', 457, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #457', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(411, 6, 'principal', 'profit_distribution', 40000.0000, 3797706.3500, 'BACKFILL-HOUSE-INT-L458', 458, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #458', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(412, 6, 'principal', 'profit_distribution', 3052.8000, 3800759.1500, 'BACKFILL-HOUSE-INT-L459', 459, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #459', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(413, 6, 'principal', 'profit_distribution', 4280.0000, 3805039.1500, 'BACKFILL-HOUSE-INT-L460', 460, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #460', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(414, 6, 'principal', 'profit_distribution', 51360.0000, 3856399.1500, 'BACKFILL-HOUSE-INT-L461', 461, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #461', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(415, 6, 'principal', 'profit_distribution', 716.6400, 3857115.7900, 'BACKFILL-HOUSE-INT-L464', 464, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #464', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(416, 6, 'principal', 'profit_distribution', 4000.0000, 3861115.7900, 'BACKFILL-HOUSE-INT-L466', 466, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #466', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(417, 6, 'principal', 'profit_distribution', 6000.0000, 3867115.7900, 'BACKFILL-HOUSE-INT-L467', 467, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #467', '2026-10-07', '2026-10-07 14:40:05', NULL, '2026-10-07 12:40:05', '2026-10-07 12:40:05'),
(418, 6, 'principal', 'profit_distribution', 20000.0000, 3887115.7900, 'BACKFILL-HOUSE-INT-L468', 468, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #468', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(419, 6, 'principal', 'profit_distribution', 12000.0000, 3899115.7900, 'BACKFILL-HOUSE-INT-L469', 469, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #469', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(420, 6, 'principal', 'profit_distribution', 518.4000, 3899634.1900, 'BACKFILL-HOUSE-INT-L470', 470, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #470', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(421, 6, 'principal', 'profit_distribution', 12000.0000, 3911634.1900, 'BACKFILL-HOUSE-INT-L471', 471, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #471', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(422, 6, 'principal', 'profit_distribution', 48000.0000, 3959634.1900, 'BACKFILL-HOUSE-INT-L472', 472, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #472', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(423, 6, 'principal', 'profit_distribution', 3663.3600, 3963297.5500, 'BACKFILL-HOUSE-INT-L473', 473, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #473', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(424, 6, 'principal', 'profit_distribution', 2000.0000, 3965297.5500, 'BACKFILL-HOUSE-INT-L474', 474, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #474', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(425, 6, 'principal', 'profit_distribution', 7409.2800, 3972706.8300, 'BACKFILL-HOUSE-INT-L475', 475, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #475', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(426, 6, 'principal', 'profit_distribution', 1031.9600, 3973738.7900, 'BACKFILL-HOUSE-INT-L476', 476, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #476', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(427, 6, 'principal', 'profit_distribution', 8891.1400, 3982629.9300, 'BACKFILL-HOUSE-INT-L478', 478, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #478', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(428, 6, 'principal', 'profit_distribution', 4396.0300, 3987025.9600, 'BACKFILL-HOUSE-INT-L479', 479, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #479', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(429, 6, 'principal', 'profit_distribution', 6400.0000, 3993425.9600, 'BACKFILL-HOUSE-INT-L480', 480, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #480', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(430, 6, 'principal', 'profit_distribution', 86400.0000, 4079825.9600, 'BACKFILL-HOUSE-INT-L481', 481, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #481', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(431, 6, 'principal', 'profit_distribution', 14000.0000, 4093825.9600, 'BACKFILL-HOUSE-INT-L482', 482, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #482', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(432, 6, 'principal', 'profit_distribution', 1238.3600, 4095064.3200, 'BACKFILL-HOUSE-INT-L483', 483, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #483', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(433, 6, 'principal', 'profit_distribution', 5000.0000, 4100064.3200, 'BACKFILL-HOUSE-INT-L484', 484, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #484', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(434, 6, 'principal', 'profit_distribution', 3617.5200, 4103681.8400, 'BACKFILL-HOUSE-INT-L485', 485, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #485', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(435, 6, 'principal', 'profit_distribution', 7680.0000, 4111361.8400, 'BACKFILL-HOUSE-INT-L487', 487, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #487', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(436, 6, 'principal', 'profit_distribution', 11129.3600, 4122491.2000, 'BACKFILL-HOUSE-INT-L489', 489, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #489', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(437, 6, 'principal', 'profit_distribution', 1200.0000, 4123691.2000, 'BACKFILL-HOUSE-INT-L493', 493, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #493', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(438, 6, 'principal', 'profit_distribution', 29120.0000, 4152811.2000, 'BACKFILL-HOUSE-INT-L494', 494, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #494', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(439, 6, 'principal', 'profit_distribution', 2000.0000, 4154811.2000, 'BACKFILL-HOUSE-INT-L495', 495, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #495', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(440, 6, 'principal', 'profit_distribution', 2400.0000, 4157211.2000, 'BACKFILL-HOUSE-INT-L496', 496, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #496', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(441, 6, 'principal', 'profit_distribution', 1000.0000, 4158211.2000, 'BACKFILL-HOUSE-INT-L497', 497, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #497', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(442, 6, 'principal', 'profit_distribution', 10000.0000, 4168211.2000, 'BACKFILL-HOUSE-INT-L498', 498, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #498', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(443, 6, 'principal', 'profit_distribution', 10000.0000, 4178211.2000, 'BACKFILL-HOUSE-INT-L499', 499, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #499', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(444, 6, 'principal', 'profit_distribution', 2000.0000, 4180211.2000, 'BACKFILL-HOUSE-INT-L501', 501, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #501', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(445, 6, 'principal', 'profit_distribution', 4000.0000, 4184211.2000, 'BACKFILL-HOUSE-INT-L502', 502, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #502', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(446, 6, 'principal', 'profit_distribution', 2000.0000, 4186211.2000, 'BACKFILL-HOUSE-INT-L503', 503, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #503', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(447, 6, 'principal', 'profit_distribution', 10400.0000, 4196611.2000, 'BACKFILL-HOUSE-INT-L505', 505, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #505', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(448, 6, 'principal', 'profit_distribution', 1000.0000, 4197611.2000, 'BACKFILL-HOUSE-INT-L507', 507, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #507', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(449, 6, 'principal', 'profit_distribution', 7500.0000, 4205111.2000, 'BACKFILL-HOUSE-INT-L508', 508, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #508', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(450, 6, 'principal', 'profit_distribution', 1200.0000, 4206311.2000, 'BACKFILL-HOUSE-INT-L509', 509, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #509', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(451, 6, 'principal', 'profit_distribution', 4000.0000, 4210311.2000, 'BACKFILL-HOUSE-INT-L512', 512, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #512', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(452, 6, 'principal', 'profit_distribution', 15000.0000, 4225311.2000, 'BACKFILL-HOUSE-INT-L514', 514, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #514', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(453, 6, 'principal', 'profit_distribution', 12880.0000, 4238191.2000, 'BACKFILL-HOUSE-INT-L516', 516, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #516', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(454, 6, 'principal', 'profit_distribution', 2000.0000, 4240191.2000, 'BACKFILL-HOUSE-INT-L517', 517, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #517', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(455, 6, 'principal', 'profit_distribution', 2000.0000, 4242191.2000, 'BACKFILL-HOUSE-INT-L518', 518, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #518', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(456, 6, 'principal', 'profit_distribution', 2000.0000, 4244191.2000, 'BACKFILL-HOUSE-INT-L519', 519, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #519', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(457, 6, 'principal', 'profit_distribution', 1000.0000, 4245191.2000, 'BACKFILL-HOUSE-INT-L521', 521, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #521', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(458, 6, 'principal', 'profit_distribution', 400.0000, 4245591.2000, 'BACKFILL-HOUSE-INT-L522', 522, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #522', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(459, 6, 'principal', 'profit_distribution', 2000.0000, 4247591.2000, 'BACKFILL-HOUSE-INT-L524', 524, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #524', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(460, 6, 'principal', 'profit_distribution', 400.0000, 4247991.2000, 'BACKFILL-HOUSE-INT-L525', 525, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #525', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(461, 6, 'principal', 'profit_distribution', 5000.0000, 4252991.2000, 'BACKFILL-HOUSE-INT-L526', 526, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #526', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(462, 6, 'principal', 'profit_distribution', 200.0000, 4253191.2000, 'BACKFILL-HOUSE-INT-L527', 527, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #527', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(463, 6, 'principal', 'profit_distribution', 1800.0000, 4254991.2000, 'BACKFILL-HOUSE-INT-L531', 531, NULL, NULL, NULL, 'Backfilled house interest (100%) for loan #531', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(464, 6, 'principal', 'profit_distribution', 14400.0000, 4269391.2000, 'BACKFILL-HOUSE-BRK-L1', 1, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #1', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(465, 6, 'principal', 'profit_distribution', 3600.0000, 4272991.2000, 'BACKFILL-HOUSE-BRK-L2', 2, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #2', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(466, 6, 'principal', 'profit_distribution', 6000.0000, 4278991.2000, 'BACKFILL-HOUSE-BRK-L3', 3, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #3', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(467, 6, 'principal', 'profit_distribution', 4200.0000, 4283191.2000, 'BACKFILL-HOUSE-BRK-L4', 4, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #4', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(468, 6, 'principal', 'profit_distribution', 3240.0000, 4286431.2000, 'BACKFILL-HOUSE-BRK-L12', 12, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #12', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(469, 6, 'principal', 'profit_distribution', 3600.0000, 4290031.2000, 'BACKFILL-HOUSE-BRK-L13', 13, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #13', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(470, 6, 'principal', 'profit_distribution', 6000.0000, 4296031.2000, 'BACKFILL-HOUSE-BRK-L16', 16, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #16', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(471, 6, 'principal', 'profit_distribution', 2400.0000, 4298431.2000, 'BACKFILL-HOUSE-BRK-L17', 17, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #17', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(472, 6, 'principal', 'profit_distribution', 4200.0000, 4302631.2000, 'BACKFILL-HOUSE-BRK-L18', 18, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #18', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(473, 6, 'principal', 'profit_distribution', 5040.0000, 4307671.2000, 'BACKFILL-HOUSE-BRK-L21', 21, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #21', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(474, 6, 'principal', 'profit_distribution', 1200.0000, 4308871.2000, 'BACKFILL-HOUSE-BRK-L22', 22, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #22', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(475, 6, 'principal', 'profit_distribution', 600.0000, 4309471.2000, 'BACKFILL-HOUSE-BRK-L23', 23, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #23', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(476, 6, 'principal', 'profit_distribution', 6000.0000, 4315471.2000, 'BACKFILL-HOUSE-BRK-L28', 28, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #28', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(477, 6, 'principal', 'profit_distribution', 5280.0000, 4320751.2000, 'BACKFILL-HOUSE-BRK-L36', 36, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #36', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(478, 6, 'principal', 'profit_distribution', 12000.0000, 4332751.2000, 'BACKFILL-HOUSE-BRK-L50', 50, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #50', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(479, 6, 'principal', 'profit_distribution', 1200.0000, 4333951.2000, 'BACKFILL-HOUSE-BRK-L54', 54, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #54', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(480, 6, 'principal', 'profit_distribution', 3600.0000, 4337551.2000, 'BACKFILL-HOUSE-BRK-L56', 56, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #56', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(481, 6, 'principal', 'profit_distribution', 5400.0000, 4342951.2000, 'BACKFILL-HOUSE-BRK-L57', 57, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #57', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(482, 6, 'principal', 'profit_distribution', 6000.0000, 4348951.2000, 'BACKFILL-HOUSE-BRK-L60', 60, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #60', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(483, 6, 'principal', 'profit_distribution', 300.0000, 4349251.2000, 'BACKFILL-HOUSE-BRK-L66', 66, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #66', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(484, 6, 'principal', 'profit_distribution', 1200.0000, 4350451.2000, 'BACKFILL-HOUSE-BRK-L68', 68, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #68', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(485, 6, 'principal', 'profit_distribution', 6000.0000, 4356451.2000, 'BACKFILL-HOUSE-BRK-L74', 74, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #74', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(486, 6, 'principal', 'profit_distribution', 984.0000, 4357435.2000, 'BACKFILL-HOUSE-BRK-L83', 83, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #83', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(487, 6, 'principal', 'profit_distribution', 12000.0000, 4369435.2000, 'BACKFILL-HOUSE-BRK-L85', 85, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #85', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(488, 6, 'principal', 'profit_distribution', 993.6000, 4370428.8000, 'BACKFILL-HOUSE-BRK-L89', 89, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #89', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(489, 6, 'principal', 'profit_distribution', 567.8400, 4370996.6400, 'BACKFILL-HOUSE-BRK-L106', 106, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #106', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(490, 6, 'principal', 'profit_distribution', 3600.0000, 4374596.6400, 'BACKFILL-HOUSE-BRK-L414', 414, NULL, NULL, NULL, 'Backfilled house interest (non-broker portion) for loan #414', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(491, 6, 'principal', 'penalty_share', 6800.0000, 4381396.6400, 'BACKFILL-HOUSE-PEN-L1', 1, NULL, NULL, NULL, 'Backfilled house penalty share (50%) for loan #1', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(492, 6, 'principal', 'penalty_share', 1200.0000, 4382596.6400, 'BACKFILL-HOUSE-PEN-L17', 17, NULL, NULL, NULL, 'Backfilled house penalty share (50%) for loan #17', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(493, 6, 'principal', 'penalty_share', 7560.0000, 4390156.6400, 'BACKFILL-HOUSE-PEN-L21', 21, NULL, NULL, NULL, 'Backfilled house penalty share (50%) for loan #21', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(494, 6, 'principal', 'penalty_share', 30000.0000, 4420156.6400, 'BACKFILL-HOUSE-PEN-L85', 85, NULL, NULL, NULL, 'Backfilled house penalty share (50%) for loan #85', '2026-10-07', '2026-10-07 14:40:06', NULL, '2026-10-07 12:40:06', '2026-10-07 12:40:06'),
(497, 5, 'broker_fee', 'broker_fee_earned', 800.0000, 118496.9600, 'BACKFILL-FEE-L22', 22, NULL, NULL, NULL, 'Backfilled broker interest share for loan #22', '2026-10-07', '2026-10-07 14:53:03', NULL, '2026-10-07 12:53:03', '2026-10-07 12:53:03'),
(498, 5, 'broker_fee', 'broker_fee_earned', 400.0000, 118896.9600, 'BACKFILL-FEE-L23', 23, NULL, NULL, NULL, 'Backfilled broker interest share for loan #23', '2026-10-07', '2026-10-07 14:53:03', NULL, '2026-10-07 12:53:03', '2026-10-07 12:53:03'),
(499, 5, 'broker_fee', 'broker_fee_earned', 4000.0000, 122896.9600, 'BACKFILL-FEE-L28', 28, NULL, NULL, NULL, 'Backfilled broker interest share for loan #28', '2026-10-07', '2026-10-07 14:53:03', NULL, '2026-10-07 12:53:03', '2026-10-07 12:53:03'),
(500, 5, 'broker_fee', 'broker_fee_earned', 2400.0000, 125296.9600, 'BACKFILL-FEE-L414', 414, NULL, NULL, NULL, 'Backfilled broker interest share for loan #414', '2026-10-07', '2026-10-07 14:53:03', NULL, '2026-10-07 12:53:03', '2026-10-07 12:53:03');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('anjelanasenya@gmail.com', '$2y$12$QbpjNRoiSegD0u17HsqZd.RogNjYmA1nimy.jWevQmEt5uMWF7KtK', '2026-08-13 15:01:16'),
('kibettdennis@gmail.com', '$2y$12$/VTw6vzlVc4bfdCHfbPfreRfMJNbGbal19xJwBlAfChQA9iCIEzTa', '2026-07-22 10:22:41'),
('michellesese99@gmail.com', '$2y$12$BZy//467z3uCC2crpZ5CFukt7hZI.Bm2NHvXxC8JOtjSdmdOa5sae', '2025-05-07 09:51:10'),
('musau.mumo@teflontradingltd.co.ke', '$2y$12$hQrQlGBbprPspsWytT.SO.AYeFfhThM/xTiS5QL5cQ70LSqmD0hiO', '2026-07-22 10:22:28'),
('nicholaskamau172@gmail.com', '$2y$12$5yfm9gQCGepOCTAmHFojPeYCD5PrezMtv9cXt/dQENFWzMhsfltX6', '2026-08-07 04:19:33'),
('nicholuskamau172@gmail.com', '$2y$12$6lbtCn3hODxTWK4MWqU0ZOCy3IAgWzR9blGglkH5Y/8E1Y5sq.5Su', '2026-08-07 04:19:24');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `method_type_id` bigint(20) UNSIGNED NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `swift_code` varchar(50) DEFAULT NULL,
  `mobile_network` varchar(50) DEFAULT NULL,
  `mobile_number` varchar(50) DEFAULT NULL,
  `crypto_currency` varchar(50) DEFAULT NULL,
  `wallet_address` varchar(500) DEFAULT NULL,
  `wallet_provider` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_date` date DEFAULT NULL,
  `status` enum('active','inactive','pending_verification','suspended') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_method_types`
--

CREATE TABLE `payment_method_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `requires_verification` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_method_types`
--

INSERT INTO `payment_method_types` (`id`, `name`, `slug`, `requires_verification`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Bank Account', 'bank_account', 1, 1, 10, NULL, NULL),
(2, 'Mobile Money', 'mobile_money', 1, 1, 20, NULL, NULL),
(3, 'Crypto Wallet', 'crypto_wallet', 0, 1, 30, NULL, NULL),
(4, 'PayPal', 'paypal', 1, 1, 40, NULL, NULL),
(5, 'Other', 'other', 0, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(2, 'create loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(3, 'edit loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(4, 'delete loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(5, 'approve loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(6, 'disburse loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(7, 'rollover loans', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(8, 'view users', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(9, 'create users', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(10, 'edit users', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(11, 'delete users', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(12, 'manage roles', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(13, 'view reports', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(14, 'export reports', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(15, 'view disbursements', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(16, 'create disbursements', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(17, 'edit disbursements', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(18, 'delete disbursements', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(19, 'view repayments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(20, 'create repayments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(21, 'edit repayments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(22, 'delete repayments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(23, 'view recovery cases', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(24, 'create recovery cases', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(25, 'edit recovery cases', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(26, 'delete recovery cases', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(27, 'view partners', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(28, 'create partners', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(29, 'edit partners', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(30, 'delete partners', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(31, 'view investments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(32, 'create investments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(33, 'edit investments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(34, 'delete investments', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(35, 'view dashboard', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(36, 'grant forbearance', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(37, 'end forbearance', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(38, 'access system settings', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(39, 'clear cache', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(40, 'view logs', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(41, 'view wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(42, 'create wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(43, 'edit wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(44, 'delete wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(45, 'transfer between wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(46, 'adjust wallet balance', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(47, 'view ledger', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(48, 'post to ledger', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(49, 'reverse ledger entry', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(50, 'reconcile wallets', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(51, 'view broker fees', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(52, 'set broker rate', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(53, 'settle broker fees', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(54, 'view partner statements', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(55, 'view house accounts', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(56, 'post to house accounts', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(57, 'view house reconciliation', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(58, 'manage house settings', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(59, 'view investment fundings', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(60, 'create investment funding', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(61, 'edit investment funding', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(62, 'settle investment funding', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(63, 'assign recovery case', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(64, 'log recovery action', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(65, 'close recovery case', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(66, 'write off loan', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(67, 'view audit log', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(68, 'view financial reports', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(69, 'view regulatory reports', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(70, 'export financial data', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(71, 'manage loan types', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(72, 'manage loan cycles', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(73, 'manage notifications', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(74, 'manage backups', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(75, 'manage API tokens', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(76, 'impersonate users', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(77, 'view system health', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(78, 'manage feature flags', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02');

-- --------------------------------------------------------

--
-- Stand-in structure for view `portfolio_summary`
-- (See below for the actual view)
--
CREATE TABLE `portfolio_summary` (
`total_loans_issued` bigint(21)
,`total_principal_disbursed` decimal(37,2)
,`total_principal_repaid` decimal(37,2)
,`total_principal_outstanding` decimal(37,2)
,`total_expected_interest` decimal(46,8)
,`total_actual_revenue_approx` decimal(38,2)
,`total_write_offs` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `recovery_actions`
--

CREATE TABLE `recovery_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `contact_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_type_id` bigint(20) UNSIGNED NOT NULL,
  `action_date` datetime NOT NULL DEFAULT current_timestamp(),
  `performed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_relationship` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `outcome` enum('successful','partial','failed','promise_to_pay','no_answer','wrong_number','refused','pending') DEFAULT 'pending',
  `promised_amount` decimal(15,2) DEFAULT NULL,
  `promised_date` date DEFAULT NULL,
  `amount_collected` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_agencies`
--

CREATE TABLE `recovery_agencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agency_name` varchar(255) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_case_notes`
--

CREATE TABLE `recovery_case_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `note_type` enum('general','action','reminder','alert','legal') DEFAULT 'general',
  `note` text NOT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recovery_case_notes`
--

INSERT INTO `recovery_case_notes` (`id`, `case_id`, `note_type`, `note`, `created_by`, `is_private`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'alert', 'Case marked as recovered by Dennis Kibett', 1, 0, '2026-09-09 17:22:27', '2026-09-09 17:22:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recovery_documents`
--

CREATE TABLE `recovery_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `document_type_id` bigint(20) UNSIGNED NOT NULL,
  `document_status_id` bigint(20) UNSIGNED DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_installments`
--

CREATE TABLE `recovery_installments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payment_plan_id` bigint(20) UNSIGNED NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','paid','partial','overdue','waived') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `recovery_officer_workload`
-- (See below for the actual view)
--
CREATE TABLE `recovery_officer_workload` (
`officer_id` bigint(20) unsigned
,`officer_name` varchar(255)
,`officer_email` varchar(255)
,`total_cases` bigint(21)
,`active_cases` bigint(21)
,`urgent_cases` bigint(21)
,`total_debt_value` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `recovery_payment_plans`
--

CREATE TABLE `recovery_payment_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `down_payment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `installment_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `number_of_installments` int(11) NOT NULL DEFAULT 0,
  `installment_frequency` enum('weekly','bi_weekly','monthly','quarterly') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','proposed','accepted','rejected','completed','defaulted') DEFAULT 'draft',
  `agreed_by_debtor` tinyint(1) DEFAULT 0,
  `agreed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_performance_metrics`
--

CREATE TABLE `recovery_performance_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `officer_id` bigint(20) UNSIGNED NOT NULL,
  `metric_date` date NOT NULL,
  `cases_assigned` int(11) DEFAULT 0,
  `cases_resolved` int(11) DEFAULT 0,
  `recovery_amount` decimal(15,2) DEFAULT 0.00,
  `collection_rate` decimal(5,2) DEFAULT 0.00,
  `avg_days_to_resolve` int(11) DEFAULT 0,
  `contact_rate` decimal(5,2) DEFAULT 0.00,
  `promise_to_pay_rate` decimal(5,2) DEFAULT 0.00,
  `performance_score` decimal(5,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recovery_priorities`
--

CREATE TABLE `recovery_priorities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `priority_level` int(11) NOT NULL DEFAULT 1,
  `color_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recovery_priorities`
--

INSERT INTO `recovery_priorities` (`id`, `name`, `slug`, `priority_level`, `color_code`, `created_at`, `updated_at`) VALUES
(1, 'Low', 'low', 1, '#6B7280', NULL, NULL),
(2, 'Medium', 'medium', 2, '#F59E0B', NULL, NULL),
(3, 'High', 'high', 3, '#EF4444', NULL, NULL),
(4, 'Urgent', 'urgent', 4, '#DC2626', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recovery_statuses`
--

CREATE TABLE `recovery_statuses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recovery_statuses`
--

INSERT INTO `recovery_statuses` (`id`, `name`, `slug`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Open', 'open', 1, 10, NULL, NULL),
(2, 'In Progress', 'in_progress', 1, 20, NULL, NULL),
(3, 'Negotiation', 'negotiation', 1, 30, NULL, NULL),
(4, 'Legal', 'legal', 1, 40, NULL, NULL),
(5, 'Recovered', 'recovered', 1, 50, NULL, NULL),
(6, 'Written Off', 'written_off', 1, 60, NULL, NULL),
(7, 'Closed', 'closed', 1, 70, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recovery_templates`
--

CREATE TABLE `recovery_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_type` enum('email','sms','letter','legal_notice','whatsapp') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `repayments`
--

CREATE TABLE `repayments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `loan_cycle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `processing_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT NULL,
  `transaction` varchar(255) DEFAULT NULL,
  `mode` varchar(100) DEFAULT NULL,
  `repayment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `partner_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `investment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `repayments`
--

INSERT INTO `repayments` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `repayment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `investment_id`, `notes`) VALUES
(1, 2, 2, 36000.00, 0.00, NULL, 'EQA5089A5329AF1', NULL, '2025-03-17', '2025-04-17 12:24:27', '2025-04-17 12:24:27', NULL, NULL, NULL, NULL),
(2, 3, 3, 60000.00, 0.00, NULL, 'U95AD8AD7E6EA', NULL, '2025-04-07', '2025-04-17 12:32:04', '2025-04-17 12:32:04', NULL, NULL, NULL, NULL),
(3, 1, 1, 14400.00, 0.00, NULL, 'EQA189AC2D294C4', NULL, '2025-03-19', '2025-04-17 12:35:37', '2025-04-17 12:35:37', NULL, NULL, NULL, NULL),
(4, 1, 1, 14400.00, 0.00, NULL, 'EQAC17C9FA5FC3B', NULL, '2025-03-20', '2025-04-17 12:36:30', '2025-04-17 12:36:30', NULL, NULL, NULL, NULL),
(5, 1, 1, 14400.00, 0.00, NULL, 'EQAEC70D699D3EB', NULL, '2025-03-21', '2025-04-17 12:36:58', '2025-04-17 12:36:58', NULL, NULL, NULL, NULL),
(6, 1, 1, 14400.00, 0.00, NULL, 'EQA8DAD4708443D', NULL, '2025-03-21', '2025-04-17 12:37:36', '2025-04-17 12:37:36', NULL, NULL, NULL, NULL),
(7, 1, 1, 31000.00, 0.00, NULL, 'EQA3A0AE105D065', NULL, '2025-03-21', '2025-04-17 12:38:41', '2025-04-17 12:40:31', NULL, NULL, NULL, NULL),
(8, 1, 1, 44000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-03-21', '2025-04-17 12:40:14', '2025-04-17 12:40:25', NULL, NULL, NULL, NULL),
(9, 4, 4, 8400.00, 0.00, NULL, '01104493106350', NULL, '2025-04-10', '2025-04-17 12:44:18', '2025-04-17 12:44:18', NULL, NULL, NULL, NULL),
(10, 4, 4, 4200.00, 0.00, NULL, '01104493106350', NULL, '2025-04-12', '2025-04-17 12:45:49', '2025-04-17 12:45:49', NULL, NULL, NULL, NULL),
(11, 4, 4, 4200.00, 0.00, NULL, 'TDA87V3XFY', NULL, '2025-04-12', '2025-04-17 12:47:06', '2025-04-17 12:47:06', NULL, NULL, NULL, NULL),
(12, 5, 5, 12000.00, 0.00, NULL, 'TD20632GE4', NULL, '2025-04-02', '2025-04-17 13:09:39', '2025-04-17 13:09:39', NULL, NULL, NULL, NULL),
(13, 8, 8, 12000.00, 0.00, NULL, 'TDH6324N9U', NULL, '2025-04-17', '2025-04-17 13:25:21', '2025-04-17 13:25:21', NULL, NULL, NULL, NULL),
(14, 1, 1, 69000.00, 0.00, NULL, 'TCM9P0JW2B', NULL, '2025-03-21', '2025-04-17 14:46:53', '2025-04-17 14:46:53', NULL, NULL, NULL, NULL),
(15, 7, 7, 500.00, 0.00, NULL, 'TDI4BPKKSU', NULL, '2025-04-18', '2025-04-19 18:32:33', '2025-04-19 18:32:33', NULL, NULL, NULL, NULL),
(16, 14, 14, 20000.00, 0.00, NULL, 'TCQ476125S', NULL, '2025-03-26', '2025-04-20 12:37:10', '2025-04-20 12:37:10', NULL, NULL, NULL, NULL),
(17, 14, 14, 4000.00, 0.00, NULL, 'TD14WWGMI4', NULL, '2025-04-01', '2025-04-20 12:38:27', '2025-04-20 12:38:27', NULL, NULL, NULL, NULL),
(18, 16, 16, 40000.00, 0.00, NULL, 'TDC1HB4VZX', NULL, '2025-04-11', '2025-04-20 12:54:22', '2025-04-20 12:54:22', NULL, NULL, NULL, NULL),
(19, 15, 15, 60000.00, 0.00, NULL, 'TD14WWGMI4', NULL, '2025-04-03', '2025-04-20 12:59:21', '2025-04-20 12:59:21', NULL, NULL, NULL, NULL),
(20, 16, 16, 20000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-11', '2025-04-20 13:05:18', '2025-04-20 13:05:18', NULL, NULL, NULL, NULL),
(21, 19, 19, 24000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-17', '2025-04-20 13:46:55', '2025-04-20 13:46:55', NULL, NULL, NULL, NULL),
(22, 4, 4, 42000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-14', '2025-04-20 13:55:40', '2025-04-20 13:55:40', NULL, NULL, NULL, NULL),
(23, 22, 22, 10000.00, 0.00, NULL, 'TC69M0KVRT', NULL, '2025-03-06', '2025-04-20 16:23:47', '2025-04-20 16:23:47', NULL, NULL, NULL, NULL),
(24, 22, 22, 2000.00, 0.00, NULL, 'TC54HGZ58I', NULL, '2025-03-05', '2025-04-20 16:25:47', '2025-04-20 16:25:47', NULL, NULL, NULL, NULL),
(25, 23, 23, 6000.00, 0.00, NULL, 'EQAC4978BCD7F1F', NULL, '2025-03-11', '2025-04-20 16:35:46', '2025-04-20 16:35:46', NULL, NULL, NULL, NULL),
(26, 28, 28, 60000.00, 0.00, NULL, 'PESALINK', NULL, '2025-04-08', '2025-04-20 17:34:42', '2025-04-20 17:34:42', NULL, NULL, NULL, NULL),
(27, 25, 25, 14000.00, 0.00, NULL, '01104493106350', NULL, '2025-03-27', '2025-04-20 18:00:21', '2025-04-20 18:00:21', NULL, NULL, NULL, NULL),
(28, 31, 31, 60000.00, 0.00, NULL, 'EQA390888BD09E7', NULL, '2025-04-15', '2025-04-20 18:18:46', '2025-04-20 18:18:46', NULL, NULL, NULL, NULL),
(29, 11, 11, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-18', '2025-04-21 06:16:54', '2025-04-21 06:16:54', NULL, NULL, NULL, NULL),
(30, 29, 29, 13200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-04', '2025-04-21 06:47:07', '2025-04-21 06:47:07', NULL, NULL, NULL, NULL),
(31, 33, 33, 12000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-07', '2025-04-21 06:47:41', '2025-04-21 06:47:41', NULL, NULL, NULL, NULL),
(32, 36, 36, 52800.00, 0.00, NULL, 'EQA3409E6C019A9', NULL, '2025-03-30', '2025-04-21 09:12:48', '2025-04-21 09:12:48', NULL, NULL, NULL, NULL),
(33, 17, 17, 26400.00, 0.00, NULL, 'TDO6YKKJLM', NULL, '2025-04-23', '2025-04-24 06:33:14', '2025-04-24 06:33:14', NULL, NULL, NULL, NULL),
(34, 7, 7, 500.00, 0.00, NULL, 'TDN9UG73HL', NULL, '2025-04-23', '2025-04-24 06:36:45', '2025-04-24 06:36:45', NULL, NULL, NULL, NULL),
(35, 13, 13, 36000.00, 0.00, NULL, 'TDO9ZB5EZ9', NULL, '2025-04-23', '2025-04-24 06:44:49', '2025-04-24 06:44:49', NULL, NULL, NULL, NULL),
(36, 7, 7, 500.00, 0.00, NULL, 'TDP67PUETG', NULL, '2025-04-25', '2025-04-26 07:35:08', '2025-04-26 07:35:08', NULL, NULL, NULL, NULL),
(37, 21, 21, 10000.00, 0.00, NULL, 'TDP56YZ8C5', NULL, '2025-04-25', '2025-04-26 12:11:42', '2025-04-26 12:11:42', NULL, NULL, NULL, NULL),
(38, 35, 35, 18000.00, 0.00, NULL, 'TDO614OO2G', NULL, '2025-04-24', '2025-04-26 12:21:02', '2025-04-26 12:21:02', NULL, NULL, NULL, NULL),
(39, 35, 35, 3600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-24', '2025-04-26 12:22:05', '2025-04-26 12:22:05', NULL, NULL, NULL, NULL),
(40, 21, 21, 10000.00, 0.00, NULL, 'TDQ5C2VU9B', NULL, '2025-04-26', '2025-04-26 14:13:28', '2025-04-26 14:13:28', NULL, NULL, NULL, NULL),
(41, 9, 9, 15000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-27', '2025-04-27 06:25:11', '2025-04-27 06:25:11', NULL, NULL, NULL, NULL),
(42, 9, 9, 3000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-04-27', '2025-04-27 06:25:20', '2025-04-27 06:25:20', NULL, NULL, NULL, NULL),
(43, 34, 34, 23400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-27', '2025-04-27 06:39:33', '2025-05-09 17:58:47', NULL, NULL, NULL, NULL),
(44, 20, 20, 39600.00, 0.00, NULL, 'TDS6JQVNWK', NULL, '2025-04-28', '2025-04-28 09:22:16', '2025-04-28 09:22:16', NULL, NULL, NULL, NULL),
(45, 20, 20, 11880.00, 0.00, NULL, 'TDS1KBHGED', NULL, '2025-04-28', '2025-04-29 06:08:00', '2025-04-29 06:08:00', NULL, NULL, NULL, NULL),
(46, 21, 21, 45520.00, 0.00, NULL, 'EQAB4681ED6B0C6', NULL, '2025-04-28', '2025-04-29 06:10:14', '2025-04-29 06:10:14', NULL, NULL, NULL, NULL),
(47, 38, 38, 10000.00, 0.00, NULL, 'TE296HCIJ7', NULL, '2025-05-03', '2025-05-02 19:30:28', '2025-05-02 19:30:28', NULL, NULL, NULL, NULL),
(48, 38, 38, 50000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-05-03', '2025-05-02 19:30:41', '2025-05-02 19:30:41', NULL, NULL, NULL, NULL),
(49, 7, 7, 500.00, 0.00, NULL, 'TE24681AZY', NULL, '2025-05-02', '2025-05-02 19:34:39', '2025-05-02 19:34:39', NULL, NULL, NULL, NULL),
(50, 10, 10, 10000.00, 0.00, NULL, 'TE399DAFX9', NULL, '2025-05-03', '2025-05-03 13:03:04', '2025-05-03 13:03:04', NULL, NULL, NULL, NULL),
(51, 10, 10, 10000.00, 0.00, NULL, 'TE36WXX7Q', NULL, '2025-05-04', '2025-05-04 09:19:52', '2025-05-04 09:19:52', NULL, NULL, NULL, NULL),
(52, 30, 30, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-05-04', '2025-05-05 09:58:22', '2025-05-05 09:58:22', NULL, NULL, NULL, NULL),
(53, 37, 37, 1000.00, 0.00, NULL, 'TE53I056SL', NULL, '2025-05-05', '2025-05-05 09:59:54', '2025-05-05 09:59:54', NULL, NULL, NULL, NULL),
(54, 37, 37, 5000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-05', '2025-05-05 16:19:15', '2025-05-05 16:19:15', NULL, NULL, NULL, NULL),
(55, 24, 24, 6000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-08', '2025-05-05 17:39:16', '2025-05-05 19:09:25', NULL, NULL, NULL, NULL),
(56, 12, 12, 32400.00, 0.00, NULL, 'TE63KRXDKP', NULL, '2025-05-05', '2025-05-06 08:45:23', '2025-05-06 08:45:23', NULL, NULL, NULL, NULL),
(57, 12, 12, 25920.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-05', '2025-05-06 08:45:40', '2025-05-06 08:45:40', NULL, NULL, NULL, NULL),
(58, 6, 6, 15600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-04', '2025-05-06 08:47:42', '2025-05-06 08:47:42', NULL, NULL, NULL, NULL),
(59, 10, 10, 22000.00, 0.00, NULL, 'TE71S62LDL', NULL, '2025-05-07', '2025-05-07 14:47:00', '2025-05-07 14:47:00', NULL, NULL, NULL, NULL),
(60, 10, 10, 19200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-07', '2025-05-07 14:47:21', '2025-05-07 14:47:21', NULL, NULL, NULL, NULL),
(61, 39, 39, 3000.00, 0.00, NULL, 'TE76SX0DTS', NULL, '2025-05-07', '2025-05-07 14:48:34', '2025-05-07 14:48:34', NULL, NULL, NULL, NULL),
(62, 39, 39, 15000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-07', '2025-05-07 14:48:54', '2025-05-07 14:48:54', NULL, NULL, NULL, NULL),
(63, 55, 50, 18000.00, 0.00, NULL, 'TE89Y1GGWP', NULL, '2025-05-08', '2025-05-08 18:47:00', '2025-05-08 18:47:09', NULL, NULL, NULL, NULL),
(64, 48, 44, 5000.00, 0.00, NULL, 'TE89Y1GGWP', NULL, '2025-05-08', '2025-05-08 18:47:50', '2025-05-08 18:47:50', NULL, NULL, NULL, NULL),
(65, 48, 44, 1000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-08', '2025-05-09 17:56:42', '2025-05-09 17:56:42', NULL, NULL, NULL, NULL),
(66, 45, 42, 78000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-12', '2025-05-12 09:03:29', '2025-05-12 09:03:29', NULL, NULL, NULL, NULL),
(67, 46, 43, 72000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-15', '2025-05-15 07:46:09', '2025-05-15 07:46:09', NULL, NULL, NULL, NULL),
(68, 51, 47, 18720.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-14', '2025-05-15 07:48:42', '2025-05-15 07:48:42', NULL, NULL, NULL, NULL),
(69, 56, 51, 36000.00, 0.00, NULL, 'TEG0Z39NQ4', NULL, '2025-05-16', '2025-05-16 12:52:38', '2025-05-16 12:52:55', NULL, NULL, NULL, NULL),
(70, 50, 46, 120000.00, 0.00, NULL, 'TEG41W5OJ8', NULL, '2025-05-16', '2025-05-16 19:26:14', '2025-05-16 19:26:14', NULL, NULL, NULL, NULL),
(71, 54, 49, 2000.00, 0.00, NULL, 'TEH26DU1HU', NULL, '2025-05-17', '2025-05-18 08:10:16', '2025-05-18 08:10:16', NULL, NULL, NULL, NULL),
(72, 54, 49, 10000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-17', '2025-05-18 08:10:32', '2025-05-18 08:10:32', NULL, NULL, NULL, NULL),
(73, 57, 52, 24000.00, 0.00, NULL, 'EQA4124F758F614', NULL, '2025-05-17', '2025-05-18 08:12:12', '2025-05-22 05:25:33', NULL, NULL, NULL, NULL),
(74, 18, 18, 8400.00, 0.00, NULL, 'TDA87V3XFY', NULL, '2025-04-10', '2025-05-18 08:27:58', '2025-05-18 08:27:58', NULL, NULL, NULL, NULL),
(75, 18, 18, 8400.00, 0.00, NULL, 'TDC6HOPL40', NULL, '2025-04-13', '2025-05-18 08:28:46', '2025-05-18 08:30:44', NULL, NULL, NULL, NULL),
(76, 18, 18, 42000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-14', '2025-05-18 08:30:30', '2025-05-18 08:31:10', NULL, NULL, NULL, NULL),
(77, 64, 56, 5200.00, 0.00, NULL, 'TEJ3FEKDDD', NULL, '2025-05-19', '2025-05-19 17:12:32', '2025-05-19 17:12:32', NULL, NULL, NULL, NULL),
(78, 59, 53, 93600.00, 0.00, NULL, 'TEK2G9HY6U', NULL, '2025-05-20', '2025-05-20 04:29:27', '2025-05-20 04:29:27', NULL, NULL, NULL, NULL),
(79, 57, 52, 30000.00, 0.00, NULL, 'EQA36E31C95BF09', NULL, '2025-05-20', '2025-05-22 05:21:27', '2025-05-22 05:26:31', NULL, NULL, NULL, NULL),
(80, 49, 45, 15000.00, 0.00, NULL, 'BANK TRANSFER', NULL, '2025-05-21', '2025-05-22 05:31:20', '2025-05-22 05:31:20', NULL, NULL, NULL, NULL),
(81, 66, 58, 3000.00, 0.00, NULL, 'TEN5WSPDFH', NULL, '2025-05-23', '2025-05-24 11:58:24', '2025-05-24 11:58:24', NULL, NULL, NULL, NULL),
(82, 61, 55, 60000.00, 0.00, NULL, 'TEQ4ACH32U', NULL, '2025-05-26', '2025-05-26 10:28:30', '2025-05-26 10:28:30', NULL, NULL, NULL, NULL),
(83, 61, 55, 7200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-26', '2025-05-26 10:29:13', '2025-05-26 10:29:13', NULL, NULL, NULL, NULL),
(84, 61, 55, 27840.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-26', '2025-05-26 10:30:57', '2025-05-26 10:32:24', NULL, NULL, NULL, NULL),
(85, 60, 54, 10000.00, 0.00, NULL, 'TEQ3CNJ40F', NULL, '2025-05-26', '2025-05-26 16:54:02', '2025-05-26 16:54:02', NULL, NULL, NULL, NULL),
(86, 60, 54, 12000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-26', '2025-05-26 16:54:30', '2025-05-26 16:55:04', NULL, NULL, NULL, NULL),
(87, 60, 54, 50000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-26', '2025-05-26 16:54:50', '2025-05-26 16:54:50', NULL, NULL, NULL, NULL),
(88, 26, 26, 720.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-21', '2025-05-27 16:47:54', '2025-05-27 16:47:54', NULL, NULL, NULL, NULL),
(89, 32, 32, 34800.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-04', '2025-05-27 16:49:17', '2025-05-27 16:49:17', NULL, NULL, NULL, NULL),
(90, 41, 41, 9600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-27', '2025-05-27 16:51:47', '2025-05-27 16:52:03', NULL, NULL, NULL, NULL),
(91, 65, 57, 14500.00, 0.00, NULL, 'TEU3TMO5M1', NULL, '2025-05-30', '2025-05-30 15:21:08', '2025-05-30 15:21:08', NULL, NULL, NULL, NULL),
(92, 65, 57, 2660.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-30', '2025-05-30 15:21:30', '2025-05-30 15:22:13', NULL, NULL, NULL, NULL),
(93, 65, 57, 1560.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-30', '2025-05-30 15:22:27', '2025-05-30 15:23:12', NULL, NULL, NULL, NULL),
(94, 68, 59, 5000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-29', '2025-05-31 11:06:24', '2025-05-31 11:06:24', NULL, NULL, NULL, NULL),
(95, 68, 59, 8200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-29', '2025-05-31 11:07:01', '2025-05-31 11:07:01', NULL, NULL, NULL, NULL),
(96, 68, 59, 1200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-05-29', '2025-05-31 11:08:18', '2025-05-31 11:08:18', NULL, NULL, NULL, NULL),
(97, 73, 64, 2000.00, 0.00, NULL, 'TF31C62QNH', NULL, '2025-06-03', '2025-06-03 08:11:11', '2025-06-03 08:11:11', NULL, NULL, NULL, NULL),
(98, 73, 64, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-03', '2025-06-03 08:11:11', '2025-06-03 08:11:42', NULL, NULL, NULL, NULL),
(99, 70, 61, 5000.00, 0.00, NULL, 'TF33ENE499', NULL, '2025-06-03', '2025-06-03 15:41:36', '2025-06-05 05:43:49', NULL, NULL, NULL, NULL),
(100, 70, 61, 55000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-03', '2025-06-03 15:42:03', '2025-06-03 15:42:17', NULL, NULL, NULL, NULL),
(101, 69, 60, 35000.00, 0.00, NULL, 'TF30FA9W36', NULL, '2025-06-03', '2025-06-03 19:10:38', '2025-06-03 19:10:38', NULL, NULL, NULL, NULL),
(102, 69, 60, 25000.00, 0.00, NULL, 'TF48H5UIGW', NULL, '2025-06-03', '2025-06-03 19:11:06', '2025-06-04 08:47:48', NULL, NULL, NULL, NULL),
(103, 69, 60, 6000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-03', '2025-06-03 19:11:37', '2025-06-03 19:11:37', NULL, NULL, NULL, NULL),
(104, 74, 65, 60000.00, 0.00, NULL, 'TF46HA3GPK', NULL, '2025-06-04', '2025-06-04 07:57:27', '2025-06-04 09:30:28', NULL, NULL, NULL, NULL),
(105, 71, 62, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-05', '2025-06-06 07:59:54', '2025-06-06 07:59:54', NULL, NULL, NULL, NULL),
(106, 49, 45, 45000.00, 0.00, NULL, 'BANK TRANSFER', NULL, '2025-06-06', '2025-06-06 08:12:05', '2025-06-06 08:12:05', NULL, NULL, NULL, NULL),
(107, 40, 40, 18720.00, 0.00, NULL, 'BANK TRANSFER', NULL, '2025-06-06', '2025-06-06 08:13:05', '2025-06-06 08:13:05', NULL, NULL, NULL, NULL),
(108, 83, 73, 3000.00, 0.00, NULL, 'TF861RBVNS', NULL, '2025-06-08', '2025-06-09 03:36:45', '2025-06-09 03:36:45', NULL, NULL, NULL, NULL),
(109, 83, 73, 6840.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-08', '2025-06-09 03:38:08', '2025-06-09 03:38:08', NULL, NULL, NULL, NULL),
(110, 77, 68, 39600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-09', '2025-06-10 05:06:45', '2025-06-10 05:07:10', NULL, NULL, NULL, NULL),
(111, 76, 67, 18000.00, 0.00, NULL, 'TFA1B0OMEZ', NULL, '2025-06-10', '2025-06-10 09:26:58', '2025-06-10 09:26:58', NULL, NULL, NULL, NULL),
(112, 86, 76, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-13', '2025-06-15 05:14:44', '2025-06-15 14:31:27', NULL, NULL, NULL, NULL),
(113, 86, 76, 2000.00, 0.00, NULL, 'TFF5XZSFDV', NULL, '2025-06-15', '2025-06-15 14:32:10', '2025-06-15 14:32:10', NULL, NULL, NULL, NULL),
(114, 86, 76, 2400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-15', '2025-06-15 14:32:24', '2025-06-15 14:33:08', NULL, NULL, NULL, NULL),
(115, 64, 56, 12000.00, 0.00, NULL, 'TFG03QKUBU', NULL, '2025-06-16', '2025-06-16 13:09:33', '2025-06-16 13:09:33', NULL, NULL, NULL, NULL),
(116, 64, 56, 5179.20, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-18', '2025-06-16 13:24:36', '2025-06-18 14:03:50', NULL, NULL, NULL, NULL),
(117, 64, 56, 2264.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-16', '2025-06-16 13:26:11', '2025-06-18 13:59:07', NULL, NULL, NULL, NULL),
(118, 88, 78, 33600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-18', '2025-06-18 09:54:17', '2025-06-18 09:54:55', NULL, NULL, NULL, NULL),
(119, 88, 78, 6720.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-18', '2025-06-18 09:54:39', '2025-06-18 09:56:27', NULL, NULL, NULL, NULL),
(120, 90, 80, 47520.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-18', '2025-06-18 10:01:51', '2025-06-18 10:01:59', NULL, NULL, NULL, NULL),
(121, 64, 56, 3000.00, 0.00, NULL, 'TFI6EL20O0', NULL, '2025-06-18', '2025-06-18 14:00:07', '2025-06-18 14:02:15', NULL, NULL, NULL, NULL),
(122, 89, 79, 3000.00, 0.00, NULL, 'ISIRO AGENCIES', NULL, '2025-06-19', '2025-06-20 07:02:27', '2025-06-20 07:02:27', NULL, NULL, NULL, NULL),
(123, 87, 77, 60000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-18', '2025-06-20 07:06:21', '2025-06-20 07:06:21', NULL, NULL, NULL, NULL),
(124, 84, 74, 35100.00, 0.00, NULL, 'TFK4NENN4M', NULL, '2025-06-20', '2025-06-20 11:25:28', '2025-06-20 11:25:28', NULL, NULL, NULL, NULL),
(125, 84, 74, 2700.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-20', '2025-06-20 11:25:44', '2025-06-20 11:25:44', NULL, NULL, NULL, NULL),
(126, 89, 79, 3000.00, 0.00, NULL, 'TFN8ZC5AYM', NULL, '2025-06-23', '2025-06-23 10:30:44', '2025-06-23 10:30:44', NULL, NULL, NULL, NULL),
(127, 93, 83, 20400.00, 0.00, NULL, 'TFN61UDXXQ', NULL, '2025-06-23', '2025-06-23 18:10:20', '2025-06-23 18:10:32', NULL, NULL, NULL, NULL),
(128, 91, 81, 8000.00, 0.00, NULL, 'TFO85K544K', NULL, '2025-06-24', '2025-06-24 06:20:01', '2025-06-24 06:20:01', NULL, NULL, NULL, NULL),
(129, 91, 81, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-24', '2025-06-24 06:20:30', '2025-06-24 06:20:30', NULL, NULL, NULL, NULL),
(131, 98, 88, 33600.00, 0.00, NULL, 'TFO48SG6KQ', NULL, '2025-06-24', '2025-06-25 08:24:49', '2025-06-25 08:24:49', NULL, NULL, NULL, NULL),
(132, 92, 82, 2000.00, 0.00, NULL, 'TFP9AVR6YV', NULL, '2025-06-25', '2025-06-25 10:42:58', '2025-06-25 10:42:58', NULL, NULL, NULL, NULL),
(133, 92, 82, 2400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-25', '2025-06-25 10:43:18', '2025-06-25 10:43:18', NULL, NULL, NULL, NULL),
(134, 92, 82, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-23', '2025-06-25 10:43:35', '2025-06-25 10:43:35', NULL, NULL, NULL, NULL),
(135, 94, 84, 1000.00, 0.00, NULL, 'TFR1JUGFVF', NULL, '2025-06-27', '2025-06-27 07:42:43', '2025-06-27 07:42:43', NULL, NULL, NULL, NULL),
(136, 94, 84, 5000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-27', '2025-06-27 07:43:09', '2025-06-27 07:43:09', NULL, NULL, NULL, NULL),
(137, 94, 84, 600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-27', '2025-06-27 07:44:28', '2025-06-27 07:44:28', NULL, NULL, NULL, NULL),
(138, 106, 96, 3000.00, 0.00, NULL, 'EQAFB41A2B2B655', NULL, '2025-06-29', '2025-06-30 08:34:20', '2025-06-30 08:35:58', NULL, NULL, NULL, NULL),
(139, 106, 96, 3246.24, 0.00, NULL, 'BAD DEBT', NULL, '2025-06-29', '2025-06-30 08:35:45', '2025-06-30 11:39:27', NULL, NULL, NULL, NULL),
(140, 89, 79, 3936.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-18', '2025-06-30 08:37:52', '2025-06-30 08:37:52', NULL, NULL, NULL, NULL),
(141, 89, 79, 4968.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-23', '2025-06-30 08:38:25', '2025-06-30 08:38:25', NULL, NULL, NULL, NULL),
(142, 75, 66, 3500.00, 0.00, NULL, 'TFU328PAAN', NULL, '2025-06-30', '2025-07-01 07:26:44', '2025-07-01 07:26:44', NULL, NULL, NULL, NULL),
(143, 75, 66, 490.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-30', '2025-07-01 07:27:32', '2025-07-01 07:29:00', NULL, NULL, NULL, NULL),
(144, 75, 66, 30000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-30', '2025-07-01 07:28:21', '2025-07-01 07:28:21', NULL, NULL, NULL, NULL),
(145, 95, 85, 2716.80, 0.00, NULL, 'BAD DEBT', NULL, '2025-07-01', '2025-07-01 09:14:16', '2025-07-03 06:30:05', NULL, NULL, NULL, NULL),
(147, 27, 27, 2400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-04-15', '2025-07-01 10:18:32', '2025-07-01 10:18:39', NULL, NULL, NULL, NULL),
(148, 103, 93, 6000.00, 0.00, NULL, 'TG48KXTDLM', NULL, '2025-07-04', '2025-07-04 12:07:10', '2025-07-04 12:07:10', NULL, NULL, NULL, NULL),
(149, 100, 90, 3000.00, 0.00, NULL, 'TG41LFZ3DT', NULL, '2025-07-04', '2025-07-04 12:46:49', '2025-07-04 12:46:49', NULL, NULL, NULL, NULL),
(150, 101, 91, 1000.00, 0.00, NULL, 'TG54OKPQN6', NULL, '2025-07-05', '2025-07-05 06:37:14', '2025-07-05 06:37:14', NULL, NULL, NULL, NULL),
(151, 101, 91, 2200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-07-05', '2025-07-05 06:38:10', '2025-07-05 06:38:10', NULL, NULL, NULL, NULL),
(152, 101, 91, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-05', '2025-07-05 06:37:47', '2025-07-05 06:37:47', NULL, NULL, NULL, NULL),
(153, 102, 92, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-04', '2025-07-05 05:39:53', '2025-07-05 05:39:53', NULL, NULL, NULL, NULL),
(154, 78, 69, 35000.00, 0.00, NULL, 'TG73XH8WDP', NULL, '2025-07-07', '2025-07-07 08:45:17', '2025-07-07 08:45:17', NULL, NULL, NULL, NULL),
(155, 79, 70, 100000.00, 0.00, NULL, 'TG812F3N3Z', NULL, '2025-07-08', '2025-07-07 21:08:58', '2025-07-07 21:09:14', NULL, NULL, NULL, NULL),
(156, 79, 70, 48000.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-07-08', '2025-07-07 21:13:06', '2025-07-10 17:11:23', NULL, NULL, NULL, NULL),
(157, 79, 70, 20000.00, 0.00, NULL, 'TO BE PAID', NULL, '2025-07-08', '2025-07-07 21:13:33', '2025-07-07 21:13:33', NULL, NULL, NULL, NULL),
(158, 85, 75, 120000.00, 0.00, NULL, 'TG989H843C', NULL, '2025-07-08', '2025-07-08 18:17:15', '2025-07-09 13:04:30', NULL, NULL, NULL, NULL),
(159, 100, 90, 2300.00, 0.00, NULL, 'TG917B2RBN', NULL, '2025-07-09', '2025-07-09 08:41:40', '2025-07-09 08:41:40', NULL, NULL, NULL, NULL),
(160, 111, 101, 8000.00, 0.00, NULL, 'TG929BVOBG', NULL, '2025-07-09', '2025-07-09 13:48:43', '2025-07-09 13:48:43', NULL, NULL, NULL, NULL),
(161, 104, 94, 6000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-09', '2025-07-10 04:41:22', '2025-07-10 04:41:22', NULL, NULL, NULL, NULL),
(162, 85, 75, 60000.00, 0.00, NULL, 'TO BE PAID', NULL, '2025-07-09', '2025-07-10 04:44:16', '2025-07-10 04:44:16', NULL, NULL, NULL, NULL),
(163, 85, 75, 240000.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-07-09', '2025-07-10 04:44:51', '2025-07-10 04:44:51', NULL, NULL, NULL, NULL),
(164, 96, 86, 97344.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-06-29', '2025-07-10 04:52:11', '2025-07-10 04:52:11', NULL, NULL, NULL, NULL),
(165, 82, 72, 39000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-01', '2025-07-10 05:04:16', '2025-07-10 05:04:16', NULL, NULL, NULL, NULL),
(166, 105, 95, 24000.00, 0.00, NULL, 'TGB8KTHG42', NULL, '2025-07-11', '2025-07-13 16:04:36', '2025-07-13 16:04:36', NULL, NULL, NULL, NULL),
(167, 105, 95, 120000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-11', '2025-07-13 16:05:06', '2025-07-13 16:05:06', NULL, NULL, NULL, NULL),
(168, 105, 95, 14400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-07-11', '2025-07-13 16:05:34', '2025-07-13 16:05:34', NULL, NULL, NULL, NULL),
(169, 111, 101, 1600.00, 0.00, NULL, 'TGD4RP45Q2', NULL, '2025-07-13', '2025-07-13 16:07:49', '2025-07-13 16:07:49', NULL, NULL, NULL, NULL),
(170, 100, 90, 1000.00, 0.00, NULL, 'TGF93BU25D', NULL, '2025-07-15', '2025-07-15 09:11:51', '2025-07-15 09:11:51', NULL, NULL, NULL, NULL),
(171, 100, 90, 7500.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-07-15', '2025-07-15 09:12:41', '2025-07-15 09:12:41', NULL, NULL, NULL, NULL),
(173, 108, 98, 12000.00, 0.00, NULL, 'TGF03NGDLO', NULL, '2025-07-15', '2025-07-15 10:45:11', '2025-07-15 10:45:11', NULL, NULL, NULL, NULL),
(174, 109, 99, 14400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-15', '2025-07-17 08:15:07', '2025-07-17 08:15:07', NULL, NULL, NULL, NULL),
(175, 80, 71, 39000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-06-30', '2025-07-17 08:18:25', '2025-07-17 08:18:25', NULL, NULL, NULL, NULL),
(176, 123, 113, 51480.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-14', '2025-07-17 08:24:56', '2025-07-17 08:24:56', NULL, NULL, NULL, NULL),
(177, 110, 100, 2000.00, 0.00, NULL, 'TGH4D2TMKI', NULL, '2025-07-17', '2025-07-17 09:46:37', '2025-07-17 09:46:37', NULL, NULL, NULL, NULL),
(178, 110, 100, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-17', '2025-07-17 09:47:16', '2025-07-17 09:47:16', NULL, NULL, NULL, NULL),
(179, 119, 109, 1200.00, 0.00, NULL, 'TGG36Q3DV9', NULL, '2025-07-16', '2025-07-17 09:55:32', '2025-07-17 09:55:32', NULL, NULL, NULL, NULL),
(180, 126, 116, 3000.00, 0.00, NULL, 'TGH7DBLIVD', NULL, '2025-07-17', '2025-07-17 11:21:09', '2025-07-17 11:21:09', NULL, NULL, NULL, NULL),
(181, 112, 102, 2000.00, 0.00, NULL, 'TGH1EUV1UF', NULL, '2025-07-17', '2025-07-18 08:31:21', '2025-07-18 08:31:21', NULL, NULL, NULL, NULL),
(182, 112, 102, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-18', '2025-07-18 08:31:47', '2025-07-18 08:31:47', NULL, NULL, NULL, NULL),
(184, 114, 104, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-20', '2025-07-21 06:43:36', '2025-07-21 06:43:50', NULL, NULL, NULL, NULL),
(185, 117, 107, 79200.00, 0.00, NULL, 'TGN76VV9JB', NULL, '2025-07-23', '2025-07-23 10:56:23', '2025-07-23 10:56:23', NULL, NULL, NULL, NULL),
(186, 117, 107, 7200.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-07-23', '2025-07-23 10:56:52', '2025-07-24 03:52:14', NULL, NULL, NULL, NULL),
(187, 118, 108, 2640.00, 0.00, NULL, 'TGN783IWP1', NULL, '2025-07-23', '2025-07-23 18:12:25', '2025-07-23 18:12:25', NULL, NULL, NULL, NULL),
(188, 117, 107, 60000.00, 0.00, NULL, 'TGO99UCAMB', NULL, '2025-07-24', '2025-07-24 03:49:29', '2025-07-24 03:49:29', NULL, NULL, NULL, NULL),
(189, 117, 107, 40800.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-07-24', '2025-07-24 03:51:08', '2025-07-24 03:51:48', NULL, NULL, NULL, NULL),
(190, 122, 112, 17280.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-25', '2025-07-27 08:59:16', '2025-07-27 08:59:16', NULL, NULL, NULL, NULL),
(191, 125, 115, 13200.00, 0.00, NULL, 'TGS2VOL12I', NULL, '2025-07-28', '2025-07-28 14:07:40', '2025-07-28 14:07:40', NULL, NULL, NULL, NULL),
(192, 131, 121, 4800.00, 0.00, NULL, 'TGS3VFV9FH', NULL, '2025-07-28', '2025-07-28 14:12:00', '2025-07-28 14:12:00', NULL, NULL, NULL, NULL),
(193, 121, 111, 4000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-07-29', '2025-07-30 04:23:57', '2025-07-30 04:23:57', NULL, NULL, NULL, NULL),
(194, 121, 111, 80000.00, 0.00, NULL, 'TGT0YU1U8C', NULL, '2025-07-29', '2025-07-30 04:28:10', '2025-07-30 04:28:10', NULL, NULL, NULL, NULL),
(195, 127, 117, 24000.00, 0.00, NULL, 'TGT21SZIQ4', NULL, '2025-07-29', '2025-07-30 04:29:34', '2025-07-30 04:29:34', NULL, NULL, NULL, NULL),
(196, 107, 97, 33000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-30', '2025-07-30 07:37:35', '2025-07-30 07:37:35', NULL, NULL, NULL, NULL),
(197, 127, 117, 2400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-29', '2025-07-30 14:20:12', '2025-07-30 14:20:12', NULL, NULL, NULL, NULL),
(198, 127, 117, 2400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-07-29', '2025-07-30 14:20:49', '2025-07-30 14:20:49', NULL, NULL, NULL, NULL),
(199, 128, 118, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-27', '2025-07-30 15:33:27', '2025-07-30 15:33:27', NULL, NULL, NULL, NULL),
(200, 129, 119, 8640.00, 0.00, NULL, 'TGV69IEZ1Y', NULL, '2025-07-31', '2025-07-31 06:17:42', '2025-07-31 06:17:42', NULL, NULL, NULL, NULL),
(201, 130, 120, 12280.00, 0.00, NULL, 'TGV5A7XZF5', NULL, '2025-07-31', '2025-07-31 08:46:54', '2025-07-31 08:46:54', NULL, NULL, NULL, NULL),
(202, 147, 136, 15000.00, 0.00, NULL, 'TH19DLONXP', NULL, '2025-08-01', '2025-08-01 07:23:58', '2025-08-01 07:23:58', NULL, NULL, NULL, NULL),
(203, 130, 120, 5000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-04', '2025-08-05 08:10:11', '2025-08-05 08:10:11', NULL, NULL, NULL, NULL),
(204, 130, 120, 3456.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-08-04', '2025-08-05 08:10:54', '2025-08-05 08:10:54', NULL, NULL, NULL, NULL),
(205, 78, 69, 30000.00, 0.00, NULL, 'TH48U71CW6', NULL, '2025-08-04', '2025-08-05 08:16:03', '2025-08-05 08:16:03', NULL, NULL, NULL, NULL),
(206, 142, 131, 2000.00, 0.00, NULL, 'TH684202CS', NULL, '2025-08-05', '2025-08-06 05:30:36', '2025-08-06 05:30:36', NULL, NULL, NULL, NULL),
(207, 126, 116, 3000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-27', '2025-08-06 05:44:18', '2025-08-06 05:44:18', NULL, NULL, NULL, NULL),
(208, 120, 110, 30000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-29', '2025-08-06 11:00:34', '2025-08-06 11:00:34', NULL, NULL, NULL, NULL),
(209, 136, 125, 14400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-07', '2025-08-07 17:08:07', '2025-08-07 17:08:07', NULL, NULL, NULL, NULL),
(210, 124, 114, 67953.60, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-28', '2025-08-07 18:46:49', '2025-08-07 18:46:49', NULL, NULL, NULL, NULL),
(211, 115, 105, 126547.20, 0.00, NULL, 'ROLL OVER', NULL, '2025-07-30', '2025-08-11 08:04:24', '2025-08-11 08:04:24', NULL, NULL, NULL, NULL),
(212, 147, 136, 66544.32, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-12', '2025-08-12 06:51:32', '2025-08-12 06:51:32', NULL, NULL, NULL, NULL),
(213, 116, 106, 50700.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-02', '2025-08-12 06:54:31', '2025-08-12 06:54:31', NULL, NULL, NULL, NULL),
(214, 140, 129, 3600.00, 0.00, NULL, 'THC04B0UYQ', NULL, '2025-08-12', '2025-08-12 18:20:30', '2025-08-12 18:20:30', NULL, NULL, NULL, NULL),
(215, 142, 131, 1600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-12', '2025-08-14 10:08:00', '2025-08-14 10:08:00', NULL, NULL, NULL, NULL),
(216, 141, 130, 1200.00, 0.00, NULL, 'THF6EWQ6OE', NULL, '2025-08-14', '2025-08-15 05:32:14', '2025-08-15 05:32:14', NULL, NULL, NULL, NULL),
(217, 141, 130, 6000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-14', '2025-08-15 05:33:31', '2025-08-15 05:33:31', NULL, NULL, NULL, NULL),
(218, 135, 124, 2800.00, 0.00, NULL, 'THF0GZ1XFK', NULL, '2025-08-15', '2025-08-15 12:23:49', '2025-08-15 12:23:49', NULL, NULL, NULL, NULL),
(219, 135, 124, 2096.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-08-15', '2025-08-15 12:24:14', '2025-08-15 12:24:14', NULL, NULL, NULL, NULL),
(220, 158, 143, 1920.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-16', '2025-08-20 06:37:25', '2025-08-20 06:37:25', NULL, NULL, NULL, NULL),
(221, 146, 135, 19680.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-17', '2025-08-20 07:25:46', '2025-08-20 07:25:46', NULL, NULL, NULL, NULL),
(222, 137, 126, 5000.00, 0.00, NULL, 'THK95CUUJD', NULL, '2025-08-20', '2025-08-20 07:31:15', '2025-08-20 07:31:15', NULL, NULL, NULL, NULL),
(223, 149, 138, 12500.00, 0.00, NULL, 'THK367HO3V', NULL, '2025-08-19', '2025-08-20 10:21:32', '2025-08-20 10:21:32', NULL, NULL, NULL, NULL),
(224, 149, 138, 700.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-08-19', '2025-08-20 10:21:33', '2025-08-20 10:21:33', NULL, NULL, NULL, NULL),
(225, 148, 137, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-19', '2025-08-21 09:20:35', '2025-08-21 09:20:35', NULL, NULL, NULL, NULL),
(226, 162, 147, 2304.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-08-23', '2025-08-23 06:29:55', '2025-08-23 06:29:55', NULL, NULL, NULL, NULL),
(227, 161, 146, 60000.00, 0.00, NULL, 'X82C9AB04FC7D', NULL, '2025-08-27', '2025-08-27 10:35:57', '2025-08-27 10:35:57', NULL, NULL, NULL, NULL),
(228, 144, 133, 360000.00, 0.00, NULL, 'X82C9AB04FC7D', NULL, '2025-08-27', '2025-08-27 10:37:14', '2025-08-27 10:37:14', NULL, NULL, NULL, NULL),
(229, 159, 144, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-25', '2025-08-27 10:40:21', '2025-08-27 10:40:21', NULL, NULL, NULL, NULL),
(230, 137, 126, 80000.00, 0.00, NULL, 'THS5CJ90C3', NULL, '2025-08-28', '2025-08-28 12:41:01', '2025-08-28 12:41:01', NULL, NULL, NULL, NULL),
(231, 137, 126, 41000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-08-28', '2025-08-28 12:41:41', '2025-08-28 12:41:41', NULL, NULL, NULL, NULL),
(232, 139, 128, 33000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-31', '2025-08-28 15:08:21', '2025-08-28 15:08:21', NULL, NULL, NULL, NULL),
(233, 138, 127, 9000.00, 0.00, NULL, 'TI12WYRJKU', NULL, '2025-09-01', '2025-09-01 18:22:22', '2025-09-01 18:22:22', NULL, NULL, NULL, NULL),
(234, 134, 123, 33000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-30', '2025-09-01 18:26:23', '2025-09-01 18:26:23', NULL, NULL, NULL, NULL),
(235, 143, 132, 42000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-13', '2025-09-01 18:29:30', '2025-09-01 18:29:30', NULL, NULL, NULL, NULL),
(236, 169, 154, 50600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-28', '2025-09-01 18:31:21', '2025-09-01 18:31:21', NULL, NULL, NULL, NULL),
(237, 138, 127, 45000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-01', '2025-09-01 18:31:22', '2025-09-01 18:31:22', NULL, NULL, NULL, NULL),
(238, 113, 103, 62500.00, 0.00, NULL, 'BAD DEBT', NULL, '2025-09-03', '2025-09-03 11:19:19', '2025-09-03 11:19:19', NULL, NULL, NULL, NULL),
(239, 164, 149, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-30', '2025-09-04 07:02:54', '2025-09-04 07:02:54', NULL, NULL, NULL, NULL),
(240, 166, 151, 1440.00, 0.00, NULL, 'TI48CH4LX6', NULL, '2025-09-04', '2025-09-04 07:37:58', '2025-09-04 07:37:58', NULL, NULL, NULL, NULL),
(241, 166, 151, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-04', '2025-09-04 07:38:16', '2025-09-04 07:38:16', NULL, NULL, NULL, NULL),
(242, 145, 134, 3500.00, 0.00, NULL, 'TI71TO854L', NULL, '2025-09-07', '2025-09-08 10:26:04', '2025-09-08 10:26:04', NULL, NULL, NULL, NULL),
(243, 164, 149, 3000.00, 0.00, NULL, 'TI69MHL50T', NULL, '2025-09-06', '2025-09-08 10:30:44', '2025-09-08 10:30:44', NULL, NULL, NULL, NULL),
(244, 145, 134, 100.00, 0.00, NULL, 'TI85ZU3WYV', NULL, '2025-09-08', '2025-09-08 15:20:48', '2025-09-08 15:20:48', NULL, NULL, NULL, NULL),
(245, 145, 134, 360.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-09-08', '2025-09-08 15:21:10', '2025-09-08 15:21:10', NULL, NULL, NULL, NULL),
(246, 167, 152, 60000.00, 0.00, NULL, 'TI86YXY2J4', NULL, '2025-09-08', '2025-09-08 15:21:55', '2025-09-08 15:21:55', NULL, NULL, NULL, NULL),
(247, 156, 142, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-01', '2025-09-08 15:30:31', '2025-09-08 15:30:31', NULL, NULL, NULL, NULL),
(248, 163, 148, 23616.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-08-29', '2025-09-08 15:36:07', '2025-09-08 15:36:07', NULL, NULL, NULL, NULL),
(249, 152, 141, 65910.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-03', '2025-09-08 16:38:16', '2025-09-08 16:38:16', NULL, NULL, NULL, NULL),
(250, 176, 161, 28339.20, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-09', '2025-09-10 02:49:32', '2025-09-10 02:49:32', NULL, NULL, NULL, NULL),
(251, 168, 153, 10000.00, 0.00, NULL, 'TI8624GQ4O', NULL, '2025-09-08', '2025-09-10 02:53:00', '2025-09-10 02:53:00', NULL, NULL, NULL, NULL),
(252, 174, 159, 18000.00, 0.00, NULL, 'TIA2942J12', NULL, '2025-09-10', '2025-09-10 07:22:24', '2025-09-10 07:22:24', NULL, NULL, NULL, NULL),
(253, 178, 163, 16000.00, 0.00, NULL, 'TIA0A2UJ2M', NULL, '2025-09-10', '2025-09-10 11:14:25', '2025-09-10 11:14:25', NULL, NULL, NULL, NULL),
(254, 178, 163, 16000.00, 0.00, NULL, 'TID1PND5A5', NULL, '2025-09-13', '2025-09-10 11:14:25', '2025-09-10 11:14:25', NULL, NULL, NULL, NULL),
(255, 172, 157, 21600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-10', '2025-09-11 17:51:22', '2025-09-11 17:51:22', NULL, NULL, NULL, NULL),
(256, 165, 150, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-01', '2025-09-12 13:47:09', '2025-09-12 13:47:09', NULL, NULL, NULL, NULL),
(257, 175, 160, 2000.00, 0.00, NULL, 'TIC3LSPPL3', NULL, '2025-09-12', '2025-09-12 14:23:43', '2025-09-12 14:23:43', NULL, NULL, NULL, NULL),
(258, 175, 160, 2400.00, 0.00, NULL, 'TIC7N0C51L', NULL, '2025-09-12', '2025-09-13 09:09:51', '2025-09-13 09:09:51', NULL, NULL, NULL, NULL),
(259, 175, 160, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-12', '2025-09-13 09:09:53', '2025-09-13 09:09:53', NULL, NULL, NULL, NULL),
(260, 178, 163, 7000.00, 0.00, NULL, 'TID1RQ4GWL', NULL, '2025-09-13', '2025-09-13 09:52:20', '2025-09-13 09:52:20', NULL, NULL, NULL, NULL),
(261, 183, 168, 21600.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-12', '2025-09-14 11:07:03', '2025-09-14 11:07:03', NULL, NULL, NULL, NULL),
(262, 78, 69, 91000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-08-30', '2025-09-14 11:29:31', '2025-09-14 11:29:31', NULL, NULL, NULL, NULL),
(263, 78, 69, 40000.00, 0.00, NULL, 'TIF6ZK4P66', NULL, '2025-09-15', '2025-09-15 08:21:09', '2025-09-15 08:21:09', NULL, NULL, NULL, NULL),
(264, 168, 153, 3000.00, 0.00, NULL, 'MPESAA', NULL, '2025-09-01', '2025-09-15 17:35:14', '2025-09-15 17:35:24', NULL, NULL, NULL, NULL),
(265, 173, 158, 8640.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-15', '2025-09-16 05:53:59', '2025-09-16 05:53:59', NULL, NULL, NULL, NULL),
(266, 179, 164, 34008.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-20', '2025-09-23 03:52:29', '2025-09-23 03:52:47', NULL, NULL, NULL, NULL),
(267, 191, 176, 2500.00, 0.00, NULL, 'TIMMK5DPW1', NULL, '2025-09-22', '2025-09-23 03:55:00', '2025-09-23 03:55:00', NULL, NULL, NULL, NULL),
(268, 170, 155, 10000.00, 0.00, NULL, 'TIN9X5F2XP', NULL, '2025-09-23', '2025-09-23 15:28:40', '2025-09-23 15:28:40', NULL, NULL, NULL, NULL),
(269, 191, 176, 2500.00, 0.00, NULL, 'TIOMK5J02A', NULL, '2025-09-24', '2025-09-25 04:12:30', '2025-09-25 04:12:30', NULL, NULL, NULL, NULL),
(270, 182, 167, 10000.00, 0.00, NULL, 'TIOKB5JWNH', NULL, '2025-09-24', '2025-09-25 04:13:31', '2025-09-25 04:13:31', NULL, NULL, NULL, NULL),
(271, 182, 167, 5000.00, 0.00, NULL, 'TIOKB5JWWH', NULL, '2025-09-24', '2025-09-25 04:13:52', '2025-09-25 04:13:52', NULL, NULL, NULL, NULL),
(272, 182, 167, 18696.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-09-24', '2025-09-25 04:15:59', '2025-09-25 04:15:59', NULL, NULL, NULL, NULL),
(273, 181, 166, 40000.00, 0.00, NULL, 'TIP9Q5KLW6', NULL, '2025-09-25', '2025-09-25 10:29:02', '2025-09-25 10:29:02', NULL, NULL, NULL, NULL),
(274, 185, 170, 25920.00, 0.00, NULL, 'TIPPX5JNBZ', NULL, '2025-09-25', '2025-09-25 10:29:35', '2025-09-25 10:29:35', NULL, NULL, NULL, NULL),
(275, 185, 170, 2592.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-09-25', '2025-09-25 10:30:09', '2025-09-25 10:30:09', NULL, NULL, NULL, NULL),
(276, 181, 166, 8000.00, 0.00, NULL, 'TIP9Q5L7NH', NULL, '2025-09-25', '2025-09-25 14:52:00', '2025-09-25 14:52:00', NULL, NULL, NULL, NULL),
(277, 187, 172, 8000.00, 0.00, NULL, 'TIP8X5JO2D', NULL, '2025-09-25', '2025-09-25 14:53:11', '2025-09-25 14:53:11', NULL, NULL, NULL, NULL),
(278, 187, 172, 40000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-25', '2025-09-25 14:53:31', '2025-09-25 14:53:31', NULL, NULL, NULL, NULL),
(279, 171, 156, 10000.00, 0.00, NULL, 'TIUKQ65WUD', NULL, '2025-09-30', '2025-10-01 06:59:03', '2025-10-01 06:59:03', NULL, NULL, NULL, NULL),
(280, 190, 175, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-01', '2025-10-01 07:10:35', '2025-10-01 07:10:35', NULL, NULL, NULL, NULL),
(281, 168, 153, 17000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-30', '2025-10-01 07:15:33', '2025-10-01 07:21:14', NULL, NULL, NULL, NULL),
(282, 168, 153, 3000.00, 0.00, NULL, 'TIL9Y4ZGNH', NULL, '2025-09-21', '2025-10-01 07:20:58', '2025-10-01 07:20:58', NULL, NULL, NULL, NULL),
(283, 170, 155, 23000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-01', '2025-10-01 07:23:06', '2025-10-01 07:23:06', NULL, NULL, NULL, NULL),
(284, 151, 140, 20000.00, 0.00, NULL, 'TJ1HD6AK4Y', NULL, '2025-10-01', '2025-10-01 16:33:26', '2025-10-01 16:33:26', NULL, NULL, NULL, NULL),
(285, 184, 169, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-09-23', '2025-10-03 06:52:15', '2025-10-03 06:52:15', NULL, NULL, NULL, NULL),
(286, 199, 184, 14400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-03', '2025-10-03 06:54:09', '2025-10-03 06:54:29', NULL, NULL, NULL, NULL),
(287, 192, 177, 48000.00, 0.00, NULL, 'TJ48X6GF99', NULL, '2025-10-04', '2025-10-05 00:38:40', '2025-10-05 00:38:40', NULL, NULL, NULL, NULL),
(288, 201, 186, 500.00, 0.00, NULL, 'TJ66B6MZ1S', NULL, '2025-10-06', '2025-10-06 07:45:55', '2025-10-06 07:45:55', NULL, NULL, NULL, NULL),
(289, 201, 186, 1000.00, 0.00, NULL, 'TJ66B6LWA6', NULL, '2025-10-05', '2025-10-06 07:46:13', '2025-10-06 07:46:13', NULL, NULL, NULL, NULL),
(290, 171, 156, 29368.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-02', '2025-10-06 08:04:19', '2025-10-06 08:04:19', NULL, NULL, NULL, NULL),
(291, 171, 156, 25000.00, 0.00, NULL, 'MPESA', NULL, '2025-10-02', '2025-10-06 08:05:27', '2025-10-06 08:05:27', NULL, NULL, NULL, NULL),
(292, 194, 179, 12000.00, 0.00, NULL, 'TJ6GC6NI17', NULL, '2025-10-06', '2025-10-06 08:12:10', '2025-10-06 08:12:10', NULL, NULL, NULL, NULL),
(293, 201, 186, 950.00, 0.00, NULL, 'TJ66B6N4EJ', NULL, '2025-10-06', '2025-10-06 09:29:20', '2025-10-06 09:29:20', NULL, NULL, NULL, NULL),
(294, 180, 165, 25000.00, 0.00, NULL, 'CHECK OFF', NULL, '2025-10-06', '2025-10-09 06:48:53', '2025-10-09 06:48:53', NULL, NULL, NULL, NULL),
(295, 202, 187, 12000.00, 0.00, NULL, 'TJ9PX6W157', NULL, '2025-10-09', '2025-10-09 14:59:08', '2025-10-09 14:59:08', NULL, NULL, NULL, NULL),
(296, 201, 186, 550.00, 0.00, NULL, 'TJ96B6YGS3', NULL, '2025-10-10', '2025-10-11 13:16:13', '2025-10-11 13:16:13', NULL, NULL, NULL, NULL),
(297, 198, 183, 55000.00, 0.00, NULL, 'TJC8O75YS4', NULL, '2025-10-12', '2025-10-13 06:34:28', '2025-10-13 06:34:28', NULL, NULL, NULL, NULL),
(298, 188, 173, 1200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-08', '2025-10-13 11:15:17', '2025-10-13 11:15:17', NULL, NULL, NULL, NULL),
(299, 201, 186, 1500.00, 0.00, NULL, 'TJD6B79QKJ', NULL, '2025-10-13', '2025-10-13 11:53:03', '2025-10-13 11:53:03', NULL, NULL, NULL, NULL),
(300, 198, 183, 5000.00, 0.00, NULL, 'TJD8O78J5N', NULL, '2025-10-13', '2025-10-13 12:39:28', '2025-10-13 12:39:28', NULL, NULL, NULL, NULL),
(301, 178, 163, 46683.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-04', '2025-10-14 06:35:35', '2025-10-14 06:35:35', NULL, NULL, NULL, NULL),
(302, 191, 176, 35809.60, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-01', '2025-10-14 07:41:22', '2025-10-14 07:41:22', NULL, NULL, NULL, NULL),
(303, 209, 194, 36972.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-11', '2025-10-14 07:44:21', '2025-10-14 07:44:21', NULL, NULL, NULL, NULL),
(304, 210, 195, 5000.00, 0.00, NULL, 'TJEMK7DIXZ', NULL, '2025-10-14', '2025-10-14 07:46:58', '2025-10-14 07:46:58', NULL, NULL, NULL, NULL),
(305, 200, 185, 5000.00, 0.00, NULL, 'TJEJ67D6FW', NULL, '2025-10-14', '2025-10-15 03:52:28', '2025-10-15 03:52:28', NULL, NULL, NULL, NULL),
(306, 204, 189, 18000.00, 0.00, NULL, 'TJFGC7FE42', NULL, '2025-10-15', '2025-10-15 04:06:09', '2025-10-15 04:06:09', NULL, NULL, NULL, NULL),
(307, 201, 186, 1000.00, 0.00, NULL, 'TJJ6B7RK3D', NULL, '2025-10-19', '2025-10-19 07:33:41', '2025-10-19 07:33:41', NULL, NULL, NULL, NULL),
(308, 201, 186, 1000.00, 0.00, NULL, 'TJJ6B7RKA8', NULL, '2025-10-19', '2025-10-19 07:33:53', '2025-10-19 07:33:53', NULL, NULL, NULL, NULL),
(309, 201, 186, 1300.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-10-19', '2025-10-19 07:34:51', '2025-10-19 07:34:51', NULL, NULL, NULL, NULL),
(310, 78, 69, 181000.00, 0.00, NULL, 'TJLPX7X5JA', NULL, '2025-10-21', '2025-10-21 19:59:01', '2025-10-21 19:59:01', NULL, NULL, NULL, NULL),
(311, 205, 190, 72000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-20', '2025-10-22 09:33:43', '2025-10-22 09:35:05', NULL, NULL, NULL, NULL),
(312, 207, 192, 36000.00, 0.00, NULL, 'TJO9Q87A53', NULL, '2025-10-24', '2025-10-24 09:01:05', '2025-10-24 09:01:05', NULL, NULL, NULL, NULL),
(313, 206, 191, 1800.00, 0.00, NULL, 'TJPFN8C647', NULL, '2025-10-25', '2025-10-25 08:57:48', '2025-10-25 08:57:48', NULL, NULL, NULL, NULL),
(314, 211, 196, 24000.00, 0.00, NULL, 'TJQGC8ELR5', NULL, '2025-10-26', '2025-10-26 17:57:02', '2025-10-26 17:57:02', NULL, NULL, NULL, NULL),
(315, 213, 198, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-27', '2025-10-27 04:57:17', '2025-10-27 04:57:17', NULL, NULL, NULL, NULL),
(316, 213, 198, 2000.00, 0.00, NULL, 'TJRKB8GQ65', NULL, '2025-10-27', '2025-10-27 04:57:35', '2025-10-27 05:03:18', NULL, NULL, NULL, NULL),
(317, 197, 182, 10000.00, 0.00, NULL, 'TJR9X8HHM6', NULL, '2025-10-27', '2025-10-27 09:47:04', '2025-10-27 09:47:04', NULL, NULL, NULL, NULL),
(318, 197, 182, 15300.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-27', '2025-10-27 09:48:40', '2025-10-27 09:48:40', NULL, NULL, NULL, NULL),
(319, 212, 197, 56000.00, 0.00, NULL, 'TJSGZ8KIC5', NULL, '2025-10-28', '2025-10-28 09:13:30', '2025-10-28 09:13:30', NULL, NULL, NULL, NULL),
(320, 212, 197, 100000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-28', '2025-10-28 09:13:54', '2025-10-28 09:13:54', NULL, NULL, NULL, NULL),
(321, 195, 180, 41760.00, 0.00, NULL, 'TJV4A8Z943', NULL, '2025-10-31', '2025-10-31 07:55:12', '2025-10-31 07:55:12', NULL, NULL, NULL, NULL),
(322, 220, 205, 8240.00, 0.00, NULL, 'TJV4A8Z943', NULL, '2025-10-31', '2025-10-31 07:56:15', '2025-10-31 07:56:15', NULL, NULL, NULL, NULL),
(323, 220, 205, 130000.00, 0.00, NULL, 'TJV4A8ZAQZ', NULL, '2025-10-31', '2025-10-31 07:56:43', '2025-10-31 07:56:43', NULL, NULL, NULL, NULL),
(324, 196, 181, 18700.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-01', '2025-11-03 09:24:16', '2025-11-03 09:24:16', NULL, NULL, NULL, NULL),
(325, 203, 188, 10000.00, 0.00, NULL, 'TK3KQ98LGK', NULL, '2025-11-03', '2025-11-03 09:42:26', '2025-11-03 09:55:29', NULL, NULL, NULL, NULL),
(326, 203, 188, 10000.00, 0.00, NULL, 'TK3KQ99299', NULL, '2025-11-03', '2025-11-03 09:55:45', '2025-11-03 11:44:37', NULL, NULL, NULL, NULL),
(327, 203, 188, 10000.00, 0.00, NULL, 'TK3KQ9963A', NULL, '2025-11-03', '2025-11-03 14:31:27', '2025-11-03 14:31:27', NULL, NULL, NULL, NULL),
(328, 203, 188, 35241.60, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-03', '2025-11-03 14:32:18', '2025-11-03 14:32:18', NULL, NULL, NULL, NULL),
(329, 219, 204, 740.00, 0.00, NULL, 'TK36B94B2Q', NULL, '2025-11-03', '2025-11-03 14:34:31', '2025-11-03 14:34:31', NULL, NULL, NULL, NULL),
(330, 208, 193, 30000.00, 0.00, NULL, 'TK46K94DNK', NULL, '2025-11-04', '2025-11-04 03:31:17', '2025-11-04 03:31:17', NULL, NULL, NULL, NULL),
(331, 219, 204, 700.00, 0.00, NULL, 'TK36B95BUL', NULL, '2025-11-03', '2025-11-04 03:31:45', '2025-11-04 03:31:45', NULL, NULL, NULL, NULL),
(332, 214, 199, 6000.00, 0.00, NULL, 'TK433995RT', NULL, '2025-10-31', '2025-11-04 03:46:11', '2025-11-05 09:22:23', NULL, NULL, NULL, NULL),
(333, 214, 199, 14400.00, 0.00, NULL, 'TK433995KP', NULL, '2025-10-31', '2025-11-04 07:36:40', '2025-11-04 07:36:40', NULL, NULL, NULL, NULL),
(334, 214, 199, 66000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-10-31', '2025-11-05 09:23:13', '2025-11-05 09:23:13', NULL, NULL, NULL, NULL),
(335, 215, 200, 3200.00, 0.00, NULL, 'TK5H59EO5Q', NULL, '2025-11-05', '2025-11-07 09:40:13', '2025-11-07 09:40:13', NULL, NULL, NULL, NULL),
(336, 215, 200, 4800.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-11-05', '2025-11-07 09:40:52', '2025-11-07 09:40:52', NULL, NULL, NULL, NULL),
(337, 215, 200, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-05', '2025-11-07 09:41:01', '2025-11-07 09:41:01', NULL, NULL, NULL, NULL),
(338, 222, 207, 5000.00, 0.00, NULL, 'TK7GC9H6FQ', NULL, '2025-11-07', '2025-11-07 09:45:10', '2025-11-07 09:45:10', NULL, NULL, NULL, NULL),
(339, 222, 207, 25000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-07', '2025-11-07 09:45:53', '2025-11-07 09:45:53', NULL, NULL, NULL, NULL),
(340, 219, 204, 3000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-07', '2025-11-07 09:58:31', '2025-11-07 09:58:31', NULL, NULL, NULL, NULL),
(341, 224, 209, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-06', '2025-11-08 08:09:56', '2025-11-08 08:09:56', NULL, NULL, NULL, NULL),
(342, 186, 171, 160000.00, 0.00, NULL, '000100572025111219503777350nxl', NULL, '2025-11-12', '2025-11-12 15:57:35', '2025-11-12 15:57:35', NULL, NULL, NULL, NULL),
(343, 186, 171, 100000.00, 0.00, NULL, '0001005720251113144749610ceq6b', NULL, '2025-11-13', '2025-11-13 10:50:05', '2025-11-13 10:50:05', NULL, NULL, NULL, NULL),
(344, 234, 219, 79200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-09', '2025-11-13 17:52:10', '2025-11-13 17:52:10', NULL, NULL, NULL, NULL),
(345, 216, 201, 36000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-03', '2025-11-17 09:29:27', '2025-11-17 09:29:27', NULL, NULL, NULL, NULL),
(346, 236, 221, 43200.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-03', '2025-11-17 09:32:35', '2025-11-17 09:32:35', NULL, NULL, NULL, NULL),
(347, 237, 222, 8000.00, 0.00, NULL, 'TKEFGA7ODI', NULL, '2025-11-14', '2025-11-17 09:35:01', '2025-11-17 09:35:01', NULL, NULL, NULL, NULL),
(348, 228, 213, 12000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-13', '2025-11-17 09:40:58', '2025-11-17 09:40:58', NULL, NULL, NULL, NULL),
(349, 229, 214, 5000.00, 0.00, NULL, 'TKHGCAFNUG', NULL, '2025-11-17', '2025-11-17 18:48:14', '2025-11-17 18:48:14', NULL, NULL, NULL, NULL),
(350, 229, 214, 25000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-17', '2025-11-17 18:48:33', '2025-11-17 18:49:09', NULL, NULL, NULL, NULL),
(351, 230, 215, 900.00, 0.00, NULL, 'TKH6BAE3SR', NULL, '2025-11-17', '2025-11-17 18:52:55', '2025-11-17 18:52:55', NULL, NULL, NULL, NULL),
(352, 231, 216, 14400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-16', '2025-11-18 05:02:28', '2025-11-18 05:02:28', NULL, NULL, NULL, NULL),
(353, 241, 226, 2000.00, 0.00, NULL, 'SALARY', NULL, '2025-11-18', '2025-11-19 05:11:40', '2025-11-19 05:11:40', NULL, NULL, NULL, NULL),
(354, 227, 212, 15000.00, 0.00, NULL, 'TKIPXAF63W', NULL, '2025-11-18', '2025-11-19 05:17:04', '2025-11-19 05:17:41', NULL, NULL, NULL, NULL),
(355, 227, 212, 600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-11-18', '2025-11-19 05:18:41', '2025-11-19 05:18:41', NULL, NULL, NULL, NULL),
(356, 230, 215, 2700.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-17', '2025-11-19 09:24:28', '2025-11-19 09:24:28', NULL, NULL, NULL, NULL),
(357, 151, 140, 10000.00, 0.00, NULL, 'TKKHDAP5VD', NULL, '2025-11-20', '2025-11-21 04:47:38', '2025-11-21 04:47:38', NULL, NULL, NULL, NULL),
(358, 243, 228, 560.00, 0.00, NULL, 'TKN6BAVC9H', NULL, '2025-11-23', '2025-11-23 09:12:54', '2025-11-23 09:12:54', NULL, NULL, NULL, NULL),
(359, 242, 227, 36000.00, 0.00, NULL, 'TKOT7422V0', NULL, '2025-11-24', '2025-11-25 07:02:02', '2025-11-25 07:02:02', NULL, NULL, NULL, NULL);
INSERT INTO `repayments` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `repayment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `investment_id`, `notes`) VALUES
(360, 232, 217, 28800.00, 0.00, NULL, 'TKOALAZQ6F', NULL, '2025-11-24', '2025-11-25 07:02:36', '2025-11-25 07:02:46', NULL, NULL, NULL, NULL),
(361, 232, 217, 2400.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-11-24', '2025-11-25 07:03:06', '2025-11-25 07:03:06', NULL, NULL, NULL, NULL),
(362, 235, 220, 95040.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-20', '2025-11-25 10:27:52', '2025-11-25 10:27:52', NULL, NULL, NULL, NULL),
(363, 238, 223, 14400.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-23', '2025-11-26 19:33:06', '2025-11-26 19:33:06', NULL, NULL, NULL, NULL),
(364, 248, 233, 2500.00, 0.00, NULL, 'TKQH5BA8E1', NULL, '2025-11-26', '2025-11-26 19:35:00', '2025-11-26 19:35:00', NULL, NULL, NULL, NULL),
(365, 221, 206, 10000.00, 0.00, NULL, 'TKR9XB9O6P', NULL, '2025-11-27', '2025-11-27 06:35:02', '2025-11-27 06:35:02', NULL, NULL, NULL, NULL),
(366, 251, 236, 15000.00, 0.00, NULL, 'TKRFGBBPGA', NULL, '2025-11-27', '2025-11-27 18:28:36', '2025-11-27 18:28:36', NULL, NULL, NULL, NULL),
(367, 245, 230, 24000.00, 0.00, NULL, 'TKS8OBCK99', NULL, '2025-11-28', '2025-11-28 07:47:35', '2025-11-28 09:12:29', NULL, NULL, NULL, NULL),
(368, 240, 225, 19008.00, 0.00, NULL, 'TKSKBBB9EO', NULL, '2025-11-28', '2025-11-28 09:11:58', '2025-11-28 09:11:58', NULL, NULL, NULL, NULL),
(369, 239, 224, 5000.00, 0.00, NULL, 'TKSGCBDF47', NULL, '2025-11-28', '2025-11-28 18:24:45', '2025-11-28 18:24:45', NULL, NULL, NULL, NULL),
(370, 239, 224, 25000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-28', '2025-11-28 18:25:12', '2025-11-28 18:25:12', NULL, NULL, NULL, NULL),
(371, 221, 206, 126830.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-27', '2025-11-28 18:28:24', '2025-11-28 18:28:24', NULL, NULL, NULL, NULL),
(372, 237, 222, 43840.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-23', '2025-11-29 14:18:03', '2025-11-29 14:18:03', NULL, NULL, NULL, NULL),
(373, 246, 231, 4000.00, 0.00, NULL, 'TL1PXBKFLJ', NULL, '2025-12-01', '2025-12-01 19:11:54', '2025-12-01 19:11:54', NULL, NULL, NULL, NULL),
(374, 246, 231, 20000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-01', '2025-12-01 19:12:08', '2025-12-01 19:12:08', NULL, NULL, NULL, NULL),
(375, 180, 165, 25000.00, 0.00, NULL, 'TL1GCBMIDI', NULL, '2025-12-01', '2025-12-01 19:14:12', '2025-12-01 19:14:12', NULL, NULL, NULL, NULL),
(376, 247, 232, 114048.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-30', '2025-12-03 04:31:52', '2025-12-03 04:31:52', NULL, NULL, NULL, NULL),
(377, 226, 211, 6000.00, 0.00, NULL, 'TL28UBMXFK', NULL, '2025-12-02', '2025-12-03 04:41:26', '2025-12-03 04:41:26', NULL, NULL, NULL, NULL),
(378, 243, 228, 2680.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-27', '2025-12-03 04:46:46', '2025-12-03 04:46:46', NULL, NULL, NULL, NULL),
(379, 248, 233, 6000.00, 0.00, NULL, 'TL3H500V3Y', NULL, '2025-12-03', '2025-12-04 04:11:36', '2025-12-04 04:24:02', NULL, NULL, NULL, NULL),
(380, 180, 165, 25000.00, 0.00, NULL, 'TL5GC0344S', NULL, '2025-12-06', '2025-12-06 08:42:59', '2025-12-06 08:42:59', NULL, NULL, NULL, NULL),
(381, 233, 218, 42290.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-02', '2025-12-06 08:51:49', '2025-12-06 08:53:27', NULL, NULL, NULL, NULL),
(382, 225, 210, 130000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-11-29', '2025-12-08 05:28:17', '2025-12-08 05:28:17', NULL, NULL, NULL, NULL),
(383, 251, 236, 37608.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-03', '2025-12-09 06:18:14', '2025-12-09 06:18:14', NULL, NULL, NULL, NULL),
(384, 249, 234, 5000.00, 0.00, NULL, 'TL8GC0FLYZ', NULL, '2025-12-09', '2025-12-09 06:21:52', '2025-12-09 06:21:52', NULL, NULL, NULL, NULL),
(385, 249, 234, 25000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-09', '2025-12-09 06:22:13', '2025-12-09 06:22:13', NULL, NULL, NULL, NULL),
(386, 248, 233, 8780.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-03', '2025-12-09 06:24:58', '2025-12-09 06:25:40', NULL, NULL, NULL, NULL),
(387, 186, 171, 65000.00, 0.00, NULL, '00010057202512120842243339uvd3', NULL, '2025-12-12', '2025-12-12 06:37:37', '2025-12-12 06:37:37', NULL, NULL, NULL, NULL),
(388, 255, 240, 3000.00, 0.00, NULL, 'TLD6B0VAFD', NULL, '2025-12-13', '2025-12-15 06:11:10', '2025-12-15 06:11:10', NULL, NULL, NULL, NULL),
(389, 255, 240, 2145.60, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-12-13', '2025-12-15 06:11:39', '2025-12-15 06:11:39', NULL, NULL, NULL, NULL),
(390, 260, 245, 30000.00, 0.00, NULL, 'TLMGC1PXT7', NULL, '2025-12-22', '2025-12-23 07:29:57', '2025-12-23 07:29:57', NULL, NULL, NULL, NULL),
(391, 260, 245, 6000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-22', '2025-12-23 07:30:09', '2025-12-23 07:30:09', NULL, NULL, NULL, NULL),
(392, 252, 237, 36000.00, 0.00, NULL, 'TLMPX1N079', NULL, '2025-12-22', '2025-12-23 07:31:48', '2025-12-23 07:31:48', NULL, NULL, NULL, NULL),
(393, 252, 237, 12000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-12-22', '2025-12-23 07:32:55', '2025-12-23 07:33:10', NULL, NULL, NULL, NULL),
(394, 97, 87, 20000.00, 0.00, NULL, 'TLJSG5L062', NULL, '2025-12-19', '2025-12-23 10:16:48', '2025-12-23 10:16:48', NULL, NULL, NULL, NULL),
(395, 254, 239, 20000.00, 0.00, NULL, 'TLQBU25Y2F', NULL, '2025-12-26', '2025-12-26 08:51:51', '2025-12-26 08:51:51', NULL, NULL, NULL, NULL),
(396, 254, 239, 100000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-26', '2025-12-26 08:52:13', '2025-12-26 08:52:13', NULL, NULL, NULL, NULL),
(397, 254, 239, 12000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2025-12-26', '2025-12-26 08:52:27', '2025-12-26 08:52:27', NULL, NULL, NULL, NULL),
(398, 261, 246, 10536.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-13', '2025-12-26 08:57:12', '2025-12-26 08:57:12', NULL, NULL, NULL, NULL),
(399, 263, 248, 12643.20, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-23', '2025-12-26 08:59:13', '2025-12-26 08:59:13', NULL, NULL, NULL, NULL),
(400, 259, 244, 45129.60, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-13', '2025-12-26 09:01:17', '2025-12-26 09:01:17', NULL, NULL, NULL, NULL),
(401, 265, 250, 54155.52, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-22', '2025-12-26 09:05:04', '2025-12-26 09:08:56', NULL, NULL, NULL, NULL),
(402, 267, 252, 6000.00, 0.00, NULL, 'TLO9X1XQ9A', NULL, '2025-12-24', '2025-12-26 18:09:31', '2025-12-26 18:09:31', NULL, NULL, NULL, NULL),
(403, 250, 235, 20000.00, 0.00, NULL, 'TLO9X1XV6I', NULL, '2025-12-24', '2025-12-27 08:20:49', '2025-12-27 08:20:49', NULL, NULL, NULL, NULL),
(404, 244, 229, 720000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-22', '2025-12-28 09:46:29', '2025-12-28 09:46:29', NULL, NULL, NULL, NULL),
(405, 270, 255, 79000.00, 0.00, NULL, 'TLVT75E8QI', NULL, '2025-12-31', '2025-12-31 08:48:37', '2025-12-31 08:48:37', NULL, NULL, NULL, NULL),
(406, 270, 255, 11000.00, 0.00, NULL, 'UA39Q2SDH7', NULL, '2026-01-04', '2026-01-04 09:31:27', '2026-01-04 09:31:27', NULL, NULL, NULL, NULL),
(407, 270, 255, 6000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-01-04', '2026-01-04 09:31:39', '2026-01-04 09:31:39', NULL, NULL, NULL, NULL),
(408, 272, 257, 24000.00, 0.00, NULL, 'UA5PX2UUFZ', NULL, '2026-01-05', '2026-01-05 02:00:37', '2026-01-05 02:00:37', NULL, NULL, NULL, NULL),
(409, 268, 253, 6600.00, 0.00, NULL, 'UA61E2XHDT', NULL, '2026-01-06', '2026-01-06 09:23:35', '2026-01-06 09:23:35', NULL, NULL, NULL, NULL),
(410, 264, 249, 15171.84, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-01', '2026-01-08 14:10:27', '2026-01-08 14:11:43', NULL, NULL, NULL, NULL),
(411, 273, 258, 14206.21, 0.00, NULL, 'BAD DEBT', NULL, '2026-01-08', '2026-01-08 14:15:06', '2026-01-12 06:40:15', NULL, NULL, NULL, NULL),
(412, 273, 258, 4000.00, 0.00, NULL, 'UA8H53AZO4', NULL, '2026-01-08', '2026-01-08 14:15:42', '2026-01-08 14:15:42', NULL, NULL, NULL, NULL),
(413, 241, 226, 10000.00, 0.00, NULL, 'SALARY DEC', NULL, '2025-12-18', '2026-01-09 07:06:03', '2026-01-09 07:06:20', NULL, NULL, NULL, NULL),
(414, 258, 243, 169000.00, 0.00, NULL, 'ROLL OVER', NULL, '2025-12-29', '2026-01-12 13:25:25', '2026-01-12 13:25:25', NULL, NULL, NULL, NULL),
(415, 276, 261, 55000.00, 0.00, NULL, 'UAF9Q3UZHE', NULL, '2026-01-15', '2026-01-15 16:39:02', '2026-01-15 16:39:02', NULL, NULL, NULL, NULL),
(416, 276, 261, 5000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-01-15', '2026-01-15 16:39:17', '2026-01-15 16:39:17', NULL, NULL, NULL, NULL),
(417, 257, 242, 50748.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-03', '2026-01-15 16:40:05', '2026-01-15 16:40:05', NULL, NULL, NULL, NULL),
(418, 277, 262, 42000.00, 0.00, NULL, '00070057202601191709408dcb2a08', NULL, '2026-01-19', '2026-01-20 08:02:39', '2026-01-20 08:02:39', NULL, NULL, NULL, NULL),
(419, 274, 259, 48000.00, 0.00, NULL, 'UAJ8O4BE8Z', NULL, '2026-01-19', '2026-01-20 08:03:10', '2026-01-20 08:03:10', NULL, NULL, NULL, NULL),
(420, 278, 263, 83000.00, 0.00, NULL, '165097985236', NULL, '2026-01-21', '2026-01-21 06:07:00', '2026-01-21 06:07:00', NULL, NULL, NULL, NULL),
(421, 281, 266, 7000.00, 0.00, NULL, 'UAL5X4JNT1', NULL, '2026-01-21', '2026-01-22 07:31:41', '2026-01-22 07:31:41', NULL, NULL, NULL, NULL),
(422, 281, 266, 200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-01-21', '2026-01-22 07:32:02', '2026-01-22 07:32:02', NULL, NULL, NULL, NULL),
(423, 269, 254, 500000.00, 0.00, NULL, '0007005720260127095017de75acd2', NULL, '2026-01-27', '2026-01-27 06:14:17', '2026-01-27 06:14:17', NULL, NULL, NULL, NULL),
(424, 278, 263, 5000.00, 0.00, NULL, 'UAQ9X4X5YI', NULL, '2026-01-26', '2026-01-27 06:17:16', '2026-01-27 06:17:16', NULL, NULL, NULL, NULL),
(425, 278, 263, 8000.00, 0.00, NULL, 'MBNHE9DTRFHI7TO3', NULL, '2026-01-22', '2026-01-27 06:28:04', '2026-01-27 06:28:04', NULL, NULL, NULL, NULL),
(426, 262, 247, 20000.00, 0.00, NULL, 'UAS6O4Z741', NULL, '2026-01-28', '2026-01-28 11:59:08', '2026-01-28 11:59:08', NULL, NULL, NULL, NULL),
(427, 285, 269, 33000.00, 0.00, NULL, 'UARPX4WGMF', NULL, '2026-01-27', '2026-01-29 08:26:42', '2026-01-29 08:26:42', NULL, NULL, NULL, NULL),
(428, 262, 247, 100000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-24', '2026-01-29 10:07:56', '2026-02-26 17:56:28', NULL, NULL, NULL, NULL),
(429, 278, 263, 30000.00, 0.00, NULL, 'UAQ9X4X754', NULL, '2026-01-26', '2026-01-30 19:24:33', '2026-01-30 19:24:33', NULL, NULL, NULL, NULL),
(430, 269, 254, 364000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-22', '2026-02-01 19:37:16', '2026-02-24 16:10:46', NULL, NULL, NULL, NULL),
(431, 151, 140, 15000.00, 0.00, NULL, '197739595312', NULL, '2026-02-03', '2026-02-03 14:34:38', '2026-02-03 14:36:35', NULL, NULL, NULL, NULL),
(432, 282, 267, 300000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-31', '2026-02-03 14:41:38', '2026-02-03 14:41:38', NULL, NULL, NULL, NULL),
(433, 290, 274, 106000.00, 0.00, NULL, 'UB6D85XEMG', NULL, '2026-02-06', '2026-02-09 13:35:53', '2026-02-09 13:35:53', NULL, NULL, NULL, NULL),
(434, 290, 274, 6000.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-02-06', '2026-02-09 13:36:43', '2026-02-09 13:36:43', NULL, NULL, NULL, NULL),
(435, 286, 270, 10560.00, 0.00, NULL, 'UB9PX665CY', NULL, '2026-02-09', '2026-02-09 13:38:21', '2026-02-09 13:38:21', NULL, NULL, NULL, NULL),
(436, 275, 260, 54440.00, 0.00, NULL, 'UB9PX665CY', NULL, '2026-02-09', '2026-02-09 13:38:47', '2026-02-09 13:38:47', NULL, NULL, NULL, NULL),
(437, 275, 260, 65560.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-09', '2026-02-09 13:39:03', '2026-02-09 13:39:03', NULL, NULL, NULL, NULL),
(438, 278, 263, 3000.00, 0.00, NULL, 'UB59X5V43U', NULL, '2026-02-04', '2026-02-09 13:44:37', '2026-02-09 13:44:37', NULL, NULL, NULL, NULL),
(439, 278, 263, 1000.00, 0.00, NULL, 'UB49X5UFY6', NULL, '2026-02-04', '2026-02-09 13:44:53', '2026-02-09 13:44:53', NULL, NULL, NULL, NULL),
(440, 293, 277, 25000.00, 0.00, NULL, 'SALARY', NULL, '2026-02-05', '2026-02-09 13:48:00', '2026-02-09 13:48:00', NULL, NULL, NULL, NULL),
(441, 283, 268, 17280.00, 0.00, NULL, 'UBB6O6AE13', NULL, '2026-02-10', '2026-02-11 10:01:29', '2026-02-11 10:01:52', NULL, NULL, NULL, NULL),
(442, 256, 241, 25000.00, 0.00, NULL, 'UBDTZ6TVNS', NULL, '2026-02-10', '2026-02-17 13:42:28', '2026-02-17 13:42:28', NULL, NULL, NULL, NULL),
(443, 289, 273, 360000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-10', '2026-02-17 13:46:53', '2026-02-17 13:46:53', NULL, NULL, NULL, NULL),
(444, 279, 264, 219700.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-01-31', '2026-02-18 05:26:27', '2026-02-18 05:26:27', NULL, NULL, NULL, NULL),
(445, 280, 265, 60897.60, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-03', '2026-02-18 05:34:39', '2026-02-18 05:34:39', NULL, NULL, NULL, NULL),
(446, 292, 276, 80000.00, 0.00, NULL, 'UBJ8O76R0T', NULL, '2026-02-19', '2026-02-19 08:59:20', '2026-02-19 08:59:20', NULL, NULL, NULL, NULL),
(447, 315, 292, 60000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-19', '2026-02-19 09:40:36', '2026-02-19 09:40:36', NULL, NULL, NULL, NULL),
(448, 226, 211, 18310.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-02-02', '2026-02-19 10:10:11', '2026-02-19 10:10:11', NULL, NULL, NULL, NULL),
(449, 292, 276, 40000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-19', '2026-02-21 21:12:59', '2026-02-21 21:12:59', NULL, NULL, NULL, NULL),
(450, 319, 296, 20000.00, 0.00, NULL, 'UBL8O7D9EU', NULL, '2026-02-21', '2026-02-21 21:15:22', '2026-02-21 21:15:22', NULL, NULL, NULL, NULL),
(451, 288, 272, 100000.00, 0.00, NULL, '0001005720260224162117447c4ymz', NULL, '2026-02-24', '2026-02-24 14:47:08', '2026-02-24 14:47:08', NULL, NULL, NULL, NULL),
(452, 288, 272, 200000.00, 0.00, NULL, '0001005720260224162356457ihiu1', NULL, '2026-02-24', '2026-02-24 14:47:35', '2026-02-24 14:47:35', NULL, NULL, NULL, NULL),
(453, 307, 284, 168000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-21', '2026-02-24 15:56:11', '2026-02-24 15:56:11', NULL, NULL, NULL, NULL),
(454, 307, 284, 12000.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-02-21', '2026-02-24 15:56:27', '2026-02-24 15:56:27', NULL, NULL, NULL, NULL),
(455, 309, 286, 13248.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-21', '2026-02-24 15:57:31', '2026-02-24 16:00:49', NULL, NULL, NULL, NULL),
(456, 309, 286, 1152.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-02-21', '2026-02-24 15:58:28', '2026-02-24 15:58:28', NULL, NULL, NULL, NULL),
(457, 321, 298, 8212.80, 0.00, NULL, 'BROKER FEES', NULL, '2026-02-27', '2026-02-24 16:06:40', '2026-03-03 06:41:17', NULL, NULL, NULL, NULL),
(458, 269, 254, 432000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-01-22', '2026-02-24 16:13:14', '2026-02-24 16:13:28', NULL, NULL, NULL, NULL),
(459, 319, 296, 30000.00, 0.00, NULL, 'UBQS88E6WC', NULL, '2026-02-26', '2026-02-26 06:41:37', '2026-02-26 06:41:37', NULL, NULL, NULL, NULL),
(460, 314, 291, 36000.00, 0.00, NULL, 'UBQBM7YL7I', NULL, '2026-02-26', '2026-02-26 17:20:28', '2026-02-26 17:20:28', NULL, NULL, NULL, NULL),
(461, 287, 271, 20000.00, 0.00, NULL, 'UBQ6O7P73Y', NULL, '2026-02-26', '2026-02-26 17:55:06', '2026-02-26 17:55:06', NULL, NULL, NULL, NULL),
(462, 287, 271, 100000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-26', '2026-02-26 17:58:54', '2026-02-26 17:58:54', NULL, NULL, NULL, NULL),
(463, 313, 290, 6000.00, 0.00, NULL, 'UBROI82WIW', NULL, '2026-02-27', '2026-02-27 17:05:12', '2026-02-27 17:05:12', NULL, NULL, NULL, NULL),
(464, 321, 298, 41160.00, 0.00, NULL, 'UBRD87VOUZ', NULL, '2026-02-27', '2026-02-27 19:24:19', '2026-02-27 19:24:19', NULL, NULL, NULL, NULL),
(465, 316, 293, 192000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-28', '2026-02-27 19:31:32', '2026-02-27 19:31:32', NULL, NULL, NULL, NULL),
(466, 317, 294, 120000.00, 0.00, NULL, 'UBS9Q7YQJQ', NULL, '2026-02-28', '2026-02-28 10:22:28', '2026-02-28 10:22:28', NULL, NULL, NULL, NULL),
(467, 321, 298, 150000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-27', '2026-03-03 06:40:19', '2026-03-03 06:40:19', NULL, NULL, NULL, NULL),
(468, 324, 301, 12000.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-03-13', '2026-03-03 06:43:55', '2026-03-03 06:43:55', NULL, NULL, NULL, NULL),
(469, 311, 288, 285610.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-03', '2026-03-04 06:05:40', '2026-03-04 06:05:40', NULL, NULL, NULL, NULL),
(470, 323, 300, 250000.00, 0.00, NULL, 'UC64A90L08', NULL, '2026-03-06', '2026-03-06 12:56:13', '2026-03-06 12:56:13', NULL, NULL, NULL, NULL),
(471, 256, 241, 55000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-06', '2026-03-06 12:56:45', '2026-03-06 12:56:45', NULL, NULL, NULL, NULL),
(472, 310, 287, 432000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-20', '2026-03-06 12:59:17', '2026-03-06 12:59:17', NULL, NULL, NULL, NULL),
(473, 330, 307, 518400.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-02', '2026-03-06 13:03:28', '2026-03-06 13:03:28', NULL, NULL, NULL, NULL),
(474, 288, 272, 64000.00, 0.00, NULL, 'PESALINK', NULL, '2026-03-08', '2026-03-08 13:55:57', '2026-03-08 13:55:57', NULL, NULL, NULL, NULL),
(475, 288, 272, 684320.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-03-08', '2026-03-08 13:56:21', '2026-03-08 13:56:21', NULL, NULL, NULL, NULL),
(476, 328, 305, 600.00, 0.00, NULL, 'UC86B8O2US', NULL, '2026-03-08', '2026-03-08 14:31:44', '2026-03-08 14:31:44', NULL, NULL, NULL, NULL),
(477, 328, 305, 1000.00, 0.00, NULL, 'UC86B8OH18', NULL, '2026-03-08', '2026-03-11 18:56:04', '2026-03-11 18:56:04', NULL, NULL, NULL, NULL),
(478, 328, 305, 1000.00, 0.00, NULL, 'UC86B8OLGX', NULL, '2026-03-08', '2026-03-11 18:56:23', '2026-03-11 18:56:23', NULL, NULL, NULL, NULL),
(479, 334, 311, 55000.00, 0.00, NULL, 'UCCFZ933Y7', NULL, '2026-03-12', '2026-03-12 11:17:58', '2026-03-12 11:17:58', NULL, NULL, NULL, NULL),
(480, 291, 275, 85228.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-09', '2026-03-12 11:18:49', '2026-03-12 11:18:49', NULL, NULL, NULL, NULL),
(481, 328, 305, 1000.00, 0.00, NULL, 'UC86B8OB4R', NULL, '2026-03-08', '2026-03-12 11:24:05', '2026-03-12 11:24:05', NULL, NULL, NULL, NULL),
(482, 320, 297, 4000.00, 0.00, NULL, 'UCD6O93ZED', NULL, '2026-03-13', '2026-03-14 19:48:28', '2026-03-14 19:48:43', NULL, NULL, NULL, NULL),
(483, 320, 297, 1280.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-03-13', '2026-03-14 19:49:10', '2026-03-14 19:49:10', NULL, NULL, NULL, NULL),
(484, 324, 301, 168000.00, 0.00, NULL, 'UCDD895ZAZ', NULL, '2026-03-13', '2026-03-14 19:49:56', '2026-03-14 19:49:56', NULL, NULL, NULL, NULL),
(485, 327, 304, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-13', '2026-03-14 19:53:12', '2026-03-14 19:53:12', NULL, NULL, NULL, NULL),
(486, 151, 140, 1357518.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-03', '2026-03-14 19:58:21', '2026-03-14 20:00:44', NULL, NULL, NULL, NULL),
(487, 340, 317, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-02', '2026-03-15 18:47:02', '2026-03-15 18:47:02', NULL, NULL, NULL, NULL),
(488, 341, 318, 8640.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-12', '2026-03-15 18:48:37', '2026-03-15 18:48:37', NULL, NULL, NULL, NULL),
(489, 342, 319, 10368.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-21', '2026-03-15 18:50:55', '2026-04-04 11:09:31', NULL, NULL, NULL, NULL),
(490, 336, 313, 500.00, 0.00, NULL, 'UCG6B9H4WP', NULL, '2026-03-16', '2026-03-18 16:36:00', '2026-03-18 16:36:00', NULL, NULL, NULL, NULL),
(491, 336, 313, 1900.00, 0.00, NULL, 'UCH6B9H6IA', NULL, '2026-03-16', '2026-03-18 16:36:16', '2026-03-18 16:36:16', NULL, NULL, NULL, NULL),
(492, 333, 310, 18000.00, 0.00, NULL, 'UCIAL9PG2R', NULL, '2026-03-19', '2026-03-19 05:16:34', '2026-03-19 05:16:34', NULL, NULL, NULL, NULL),
(493, 332, 309, 24000.00, 0.00, NULL, 'UCIBM9XI4K', NULL, '2026-03-18', '2026-03-19 05:17:03', '2026-03-19 05:17:03', NULL, NULL, NULL, NULL),
(494, 250, 235, 114439.80, 0.00, NULL, 'ROLL OVER', NULL, '2026-02-27', '2026-03-20 18:22:22', '2026-03-20 18:23:15', NULL, NULL, NULL, NULL),
(495, 338, 315, 21600.00, 0.00, NULL, 'UCNKBA2UAO', NULL, '2026-03-23', '2026-03-24 14:35:05', '2026-03-24 14:35:05', NULL, NULL, NULL, NULL),
(496, 339, 316, 49500.00, 0.00, NULL, 'UCOHDAJFL9', NULL, '2026-03-24', '2026-03-24 19:28:31', '2026-03-24 19:28:31', NULL, NULL, NULL, NULL),
(497, 318, 295, 6000.00, 0.00, NULL, 'UCOBDAI1OC', NULL, '2026-03-24', '2026-03-25 11:38:15', '2026-03-25 11:38:15', NULL, NULL, NULL, NULL),
(498, 346, 323, 6000.00, 0.00, NULL, 'UCPALAGDK9', NULL, '2026-03-25', '2026-03-26 06:04:45', '2026-03-26 06:04:45', NULL, NULL, NULL, NULL),
(499, 343, 320, 2000.00, 0.00, NULL, 'UCQEVAMP9Y', NULL, '2026-03-26', '2026-03-26 20:56:09', '2026-03-26 20:56:09', NULL, NULL, NULL, NULL),
(500, 343, 320, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-26', '2026-03-26 20:56:19', '2026-03-26 20:56:19', NULL, NULL, NULL, NULL),
(501, 337, 314, 60000.00, 0.00, NULL, 'UCQ9QAL1NG', NULL, '2026-03-26', '2026-03-26 20:58:07', '2026-03-26 20:58:07', NULL, NULL, NULL, NULL),
(502, 344, 321, 2800.00, 0.00, NULL, 'UCS6BAR30I', NULL, '2026-03-28', '2026-03-29 16:41:28', '2026-03-29 16:41:28', NULL, NULL, NULL, NULL),
(503, 344, 321, 1200.00, 0.00, NULL, 'UCS6BAN50W', NULL, '2026-03-28', '2026-03-29 16:42:19', '2026-03-29 16:42:19', NULL, NULL, NULL, NULL),
(504, 369, 345, 2000.00, 0.00, NULL, 'UCS6BAN8ZZ', NULL, '2026-03-28', '2026-03-29 16:42:41', '2026-03-29 16:42:41', NULL, NULL, NULL, NULL),
(505, 322, 299, 160000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-26', '2026-04-02 04:49:56', '2026-04-02 04:54:47', NULL, NULL, NULL, NULL),
(506, 322, 299, 20000.00, 0.00, NULL, 'UCV9XB859B', NULL, '2026-03-31', '2026-04-02 04:54:18', '2026-04-02 04:54:18', NULL, NULL, NULL, NULL),
(507, 345, 322, 39600.00, 0.00, NULL, 'UCU9XB3ZQX', NULL, '2026-03-30', '2026-04-02 05:07:20', '2026-04-02 05:07:20', NULL, NULL, NULL, NULL),
(508, 350, 326, 9800.00, 0.00, NULL, 'UCUGCAZTET', NULL, '2026-03-30', '2026-04-02 05:09:52', '2026-04-02 05:09:52', NULL, NULL, NULL, NULL),
(509, 312, 289, 73077.12, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-18', '2026-04-02 05:16:16', '2026-04-02 05:16:26', NULL, NULL, NULL, NULL),
(510, 326, 303, 4200.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-14', '2026-04-04 11:05:46', '2026-04-04 11:05:59', NULL, NULL, NULL, NULL),
(511, 366, 342, 5040.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-24', '2026-04-04 11:07:35', '2026-04-04 11:07:35', NULL, NULL, NULL, NULL),
(512, 359, 335, 11000.00, 0.00, NULL, '00630057202604032133052fb2e439', NULL, '2026-04-03', '2026-04-04 11:13:51', '2026-04-04 11:13:51', NULL, NULL, NULL, NULL),
(513, 351, 327, 12000.00, 0.00, NULL, 'UD5SGALUXT', NULL, '2026-04-05', '2026-04-05 09:21:56', '2026-04-05 09:21:56', NULL, NULL, NULL, NULL),
(514, 353, 329, 2000.00, 0.00, NULL, 'UD5EVBR6UM', NULL, '2026-04-05', '2026-04-05 15:42:40', '2026-04-05 15:42:40', NULL, NULL, NULL, NULL),
(515, 353, 329, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-05', '2026-04-05 15:42:55', '2026-04-05 15:42:55', NULL, NULL, NULL, NULL),
(516, 308, 285, 18000.00, 0.00, NULL, 'UD7GCBW9X8', NULL, '2026-03-16', '2026-04-08 05:54:18', '2026-04-08 05:54:18', NULL, NULL, NULL, NULL),
(517, 354, 330, 24000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-05', '2026-04-08 06:05:43', '2026-04-08 06:05:43', NULL, NULL, NULL, NULL),
(518, 355, 331, 1200.00, 0.00, NULL, 'UD76BBT9AX', NULL, '2026-04-05', '2026-04-08 06:09:41', '2026-04-08 06:10:09', NULL, NULL, NULL, NULL),
(519, 361, 337, 800.00, 0.00, NULL, 'UD76BBT9AX', NULL, '2026-04-07', '2026-04-08 06:10:57', '2026-04-08 06:10:57', NULL, NULL, NULL, NULL),
(520, 362, 338, 1000.00, 0.00, NULL, 'UDAHD0JY97', NULL, '2026-04-10', '2026-04-13 16:22:38', '2026-04-13 16:22:38', NULL, NULL, NULL, NULL),
(521, 362, 338, 5000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-10', '2026-04-13 16:22:56', '2026-04-13 16:22:56', NULL, NULL, NULL, NULL),
(522, 361, 337, 3040.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-10', '2026-04-13 16:27:29', '2026-04-13 16:27:29', NULL, NULL, NULL, NULL),
(523, 357, 333, 11000.00, 0.00, NULL, 'UDABM0LERC', NULL, '2026-04-10', '2026-04-13 16:34:15', '2026-04-13 16:34:15', NULL, NULL, NULL, NULL),
(524, 356, 332, 6600.00, 0.00, NULL, 'UD9OI0J2U5', NULL, '2026-04-09', '2026-04-13 16:38:31', '2026-04-13 16:38:31', NULL, NULL, NULL, NULL),
(525, 325, 302, 342732.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-03', '2026-04-13 16:42:26', '2026-04-13 16:42:26', NULL, NULL, NULL, NULL),
(526, 368, 344, 12441.60, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-31', '2026-04-13 17:17:21', '2026-04-13 17:17:21', NULL, NULL, NULL, NULL),
(527, 367, 343, 6048.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-03', '2026-04-13 17:18:59', '2026-04-13 17:19:12', NULL, NULL, NULL, NULL),
(528, 375, 351, 22186.80, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-10', '2026-04-13 17:23:12', '2026-04-13 17:23:36', NULL, NULL, NULL, NULL),
(529, 357, 333, 41000.00, 0.00, NULL, 'UDDBM0XT1E', NULL, '2026-04-13', '2026-04-14 06:39:52', '2026-04-14 06:40:13', NULL, NULL, NULL, NULL),
(530, 357, 333, 2000.00, 0.00, NULL, 'UDEBM0Z49A', NULL, '2026-04-14', '2026-04-14 06:54:18', '2026-04-14 06:54:18', NULL, NULL, NULL, NULL),
(531, 357, 333, 21600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-14', '2026-04-14 06:54:47', '2026-04-14 06:54:47', NULL, NULL, NULL, NULL),
(532, 365, 341, 16000.00, 0.00, NULL, 'UDFAL0TXHB', NULL, '2026-04-15', '2026-04-15 10:12:36', '2026-04-15 10:12:36', NULL, NULL, NULL, NULL),
(533, 370, 346, 10000.00, 0.00, NULL, 'UDFKB0ONZY', NULL, '2026-03-15', '2026-04-15 10:16:30', '2026-04-15 10:16:30', NULL, NULL, NULL, NULL),
(534, 329, 306, 66000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-03-20', '2026-04-15 10:24:29', '2026-04-15 10:24:29', NULL, NULL, NULL, NULL),
(535, 370, 346, 18800.00, 0.00, NULL, 'UDGKB0S6QI', NULL, '2026-04-16', '2026-04-16 15:00:18', '2026-04-16 15:04:25', NULL, NULL, NULL, NULL),
(536, 370, 346, 2000.00, 0.00, NULL, 'UDFEV0ZRS1', NULL, '2026-04-15', '2026-04-16 15:00:47', '2026-04-16 15:05:41', NULL, NULL, NULL, NULL),
(537, 369, 345, 10000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-15', '2026-04-16 15:07:20', '2026-04-16 15:07:20', NULL, NULL, NULL, NULL),
(538, 381, 357, 1000.00, 0.00, NULL, 'UDGEV13YEB', NULL, '2026-04-17', '2026-04-17 04:26:56', '2026-04-17 04:26:56', NULL, NULL, NULL, NULL),
(539, 363, 339, 30000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-09', '2026-04-17 18:13:28', '2026-04-17 18:13:28', NULL, NULL, NULL, NULL),
(540, 335, 312, 110796.40, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-09', '2026-04-17 18:16:19', '2026-04-17 18:16:19', NULL, NULL, NULL, NULL),
(541, 371, 347, 1448.00, 0.00, NULL, 'UDH6B137T1', NULL, '2026-04-17', '2026-04-17 18:41:14', '2026-04-17 18:41:14', NULL, NULL, NULL, NULL),
(542, 365, 341, 16000.00, 0.00, NULL, 'UDIAL15ZLB', NULL, '2026-04-17', '2026-04-18 18:09:11', '2026-04-18 18:09:11', NULL, NULL, NULL, NULL),
(543, 365, 341, 800.00, 0.00, NULL, 'UDIAL16LCX', NULL, '2026-04-18', '2026-04-18 18:09:40', '2026-04-18 18:10:09', NULL, NULL, NULL, NULL),
(544, 365, 341, 3200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-18', '2026-04-18 18:10:29', '2026-04-18 18:10:29', NULL, NULL, NULL, NULL),
(545, 371, 347, 400.00, 0.00, NULL, 'UDK6B1C0JB', NULL, '2026-04-20', '2026-04-20 07:41:07', '2026-04-20 07:41:31', NULL, NULL, NULL, NULL),
(546, 266, 251, 10000.00, 0.00, NULL, 'UDKFG1GB48', NULL, '2026-04-18', '2026-04-20 08:13:46', '2026-04-20 08:13:46', NULL, NULL, NULL, NULL),
(547, 266, 251, 54155.50, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-01-02', '2026-04-20 08:14:46', '2026-04-20 08:16:21', NULL, NULL, NULL, NULL),
(548, 373, 349, 648.00, 0.00, NULL, 'UDK6B1DFIB', NULL, '2026-04-20', '2026-04-20 16:21:51', '2026-04-20 16:21:51', NULL, NULL, NULL, NULL),
(549, 372, 348, 6000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-20', '2026-04-21 04:04:50', '2026-04-21 04:04:50', NULL, NULL, NULL, NULL),
(550, 376, 352, 26700.00, 0.00, NULL, 'UDL1U1MSJM', NULL, '2026-04-21', '2026-04-22 06:40:46', '2026-04-22 06:40:46', NULL, NULL, NULL, NULL),
(551, 373, 349, 2500.00, 0.00, NULL, 'UDL6B1HSIL', NULL, '2026-04-21', '2026-04-22 06:42:33', '2026-04-22 06:42:33', NULL, NULL, NULL, NULL),
(552, 373, 349, 500.00, 0.00, NULL, 'UDL6B1I22V', NULL, '2026-04-21', '2026-04-22 06:42:57', '2026-04-22 06:42:57', NULL, NULL, NULL, NULL),
(553, 373, 349, 364.80, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-21', '2026-04-22 06:43:37', '2026-04-22 06:43:37', NULL, NULL, NULL, NULL),
(554, 382, 358, 36000.00, 0.00, NULL, 'UDLPX1KFBE', NULL, '2026-04-21', '2026-04-22 07:01:57', '2026-04-22 07:01:57', NULL, NULL, NULL, NULL),
(555, 382, 358, 3600.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-21', '2026-04-22 07:02:12', '2026-04-22 07:02:12', NULL, NULL, NULL, NULL),
(556, 378, 354, 9000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-24', '2026-04-23 06:21:46', '2026-04-23 06:22:08', NULL, NULL, NULL, NULL),
(557, 377, 353, 9000.00, 0.00, NULL, 'UDPAI2A2WO', NULL, '2026-04-25', '2026-04-26 06:23:55', '2026-04-26 06:23:55', NULL, NULL, NULL, NULL),
(558, 377, 353, 3000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-25', '2026-04-27 04:58:17', '2026-04-27 04:58:17', NULL, NULL, NULL, NULL),
(559, 381, 357, 3150.00, 0.00, NULL, 'UDQEV287UH', NULL, '2026-04-26', '2026-04-27 05:01:34', '2026-04-27 05:06:55', NULL, NULL, NULL, NULL),
(560, 381, 357, 3000.00, 0.00, NULL, 'UDREV2CAIN', NULL, '2026-04-27', '2026-04-28 10:48:41', '2026-04-28 10:48:41', NULL, NULL, NULL, NULL),
(561, 384, 360, 7200.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-30', '2026-05-01 15:16:21', '2026-05-01 15:16:21', NULL, NULL, NULL, NULL),
(562, 379, 355, 8000.00, 0.00, NULL, 'UDRBM2IP9Q', NULL, '2026-04-27', '2026-05-01 15:29:23', '2026-05-01 15:29:23', NULL, NULL, NULL, NULL),
(563, 379, 355, 5400.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-27', '2026-05-01 15:30:03', '2026-05-01 15:30:14', NULL, NULL, NULL, NULL),
(564, 381, 357, 5000.00, 0.00, NULL, 'UDTEV2KLYS', NULL, '2026-04-29', '2026-05-01 15:32:46', '2026-05-01 15:32:46', NULL, NULL, NULL, NULL),
(565, 381, 357, 1500.00, 0.00, NULL, 'UDTEV2GG9P', NULL, '2026-04-29', '2026-05-01 15:33:24', '2026-05-01 15:33:24', NULL, NULL, NULL, NULL),
(566, 381, 357, 1650.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-04-29', '2026-05-01 15:33:43', '2026-05-01 15:33:43', NULL, NULL, NULL, NULL),
(567, 352, 328, 1800.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-25', '2026-05-01 15:48:02', '2026-05-01 15:48:02', NULL, NULL, NULL, NULL),
(568, 389, 365, 500.00, 0.00, NULL, 'UDU6B2GL31', NULL, '2026-04-30', '2026-05-01 15:54:28', '2026-05-01 15:54:28', NULL, NULL, NULL, NULL),
(569, 389, 365, 500.00, 0.00, NULL, 'UDU6B2GL4T', NULL, '2026-04-30', '2026-05-01 15:54:33', '2026-05-01 15:54:33', NULL, NULL, NULL, NULL),
(570, 389, 365, 300.00, 0.00, NULL, 'UDU6B2GGRH', NULL, '2026-04-30', '2026-05-01 15:54:49', '2026-05-01 15:54:49', NULL, NULL, NULL, NULL),
(571, 349, 325, 8000.00, 0.00, NULL, 'UDT9X2OX17', NULL, '2026-04-29', '2026-05-01 16:00:14', '2026-05-01 16:00:14', NULL, NULL, NULL, NULL),
(572, 349, 325, 2000.00, 0.00, NULL, 'UDT9X2P4EJ', NULL, '2026-04-29', '2026-05-01 16:00:29', '2026-05-01 16:00:29', NULL, NULL, NULL, NULL),
(573, 388, 364, 12000.00, 0.00, NULL, 'UE53238D7X', NULL, '2026-05-05', '2026-05-05 09:41:31', '2026-05-05 09:41:31', NULL, NULL, NULL, NULL),
(574, 394, 370, 6000.00, 0.00, NULL, 'UE45B3AR2F', NULL, '2026-05-04', '2026-05-05 09:42:20', '2026-05-05 09:42:20', NULL, NULL, NULL, NULL),
(575, 389, 365, 3000.00, 0.00, NULL, 'UE36B2UZBK', NULL, '2026-05-03', '2026-05-05 09:44:33', '2026-05-05 09:56:50', NULL, NULL, NULL, NULL),
(576, 385, 361, 24000.00, 0.00, NULL, 'UE4BM391CB', NULL, '2026-05-04', '2026-05-05 09:45:49', '2026-05-05 09:51:22', NULL, NULL, NULL, NULL),
(577, 385, 361, 7200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-05-04', '2026-05-05 09:51:50', '2026-05-05 09:52:03', NULL, NULL, NULL, NULL),
(578, 392, 368, 5400.00, 0.00, NULL, 'UE4BM391CB', NULL, '2026-05-04', '2026-05-05 09:52:34', '2026-05-05 09:52:34', NULL, NULL, NULL, NULL),
(579, 389, 365, 700.00, 0.00, NULL, 'UE36B2TK9B', NULL, '2026-05-03', '2026-05-05 09:57:46', '2026-05-05 09:57:46', NULL, NULL, NULL, NULL),
(580, 389, 365, 1000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-03', '2026-05-05 10:15:28', '2026-05-05 10:15:28', NULL, NULL, NULL, NULL),
(581, 387, 363, 13200.00, 0.00, NULL, 'UE49X38M5M', NULL, '2026-05-04', '2026-05-05 10:25:03', '2026-05-05 10:25:03', NULL, NULL, NULL, NULL),
(582, 293, 277, 75000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-03-05', '2026-05-05 17:12:34', '2026-05-05 17:12:50', NULL, NULL, NULL, NULL),
(583, 293, 277, 167120.00, 0.00, NULL, 'PAYMENT', NULL, '2026-05-05', '2026-05-05 17:14:33', '2026-05-05 17:14:33', NULL, NULL, NULL, NULL),
(584, 388, 364, 1200.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-05-05', '2026-05-06 15:22:06', '2026-05-06 15:22:15', NULL, NULL, NULL, NULL),
(585, 390, 366, 3600.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-06', '2026-05-07 06:39:33', '2026-05-07 06:39:33', NULL, NULL, NULL, NULL),
(586, 392, 368, 1080.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-05-07', '2026-05-08 07:16:16', '2026-05-08 07:16:16', NULL, NULL, NULL, NULL),
(587, 374, 350, 411278.40, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-03', '2026-05-08 07:19:00', '2026-05-08 07:19:00', NULL, NULL, NULL, NULL),
(588, 386, 362, 1200.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-02', '2026-05-08 09:42:20', '2026-05-08 09:42:20', NULL, NULL, NULL, NULL),
(589, 360, 336, 120000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-26', '2026-05-15 07:24:07', '2026-05-15 07:24:07', NULL, NULL, NULL, NULL),
(590, 391, 367, 8640.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-10', '2026-05-15 07:26:27', '2026-05-15 07:26:27', NULL, NULL, NULL, NULL),
(591, 364, 340, 87692.54, 0.00, NULL, 'ROLL OVER', NULL, '2026-04-18', '2026-05-15 07:32:36', '2026-05-15 07:32:51', NULL, NULL, NULL, NULL),
(592, 403, 379, 1440.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-12', '2026-05-15 07:50:18', '2026-05-15 07:50:18', NULL, NULL, NULL, NULL),
(593, 399, 375, 31250.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-11', '2026-05-15 07:53:56', '2026-05-15 07:53:56', NULL, NULL, NULL, NULL),
(594, 383, 359, 144035.32, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-08', '2026-05-15 07:57:39', '2026-05-15 07:59:09', NULL, NULL, NULL, NULL),
(595, 396, 372, 15000.00, 0.00, NULL, 'UEFC14JLYT', NULL, '2026-05-15', '2026-05-15 08:08:27', '2026-05-15 08:08:27', NULL, NULL, NULL, NULL),
(596, 397, 373, 1600.00, 0.00, NULL, 'UEF6B46HY1', NULL, '2026-05-15', '2026-05-15 08:09:17', '2026-05-15 08:09:17', NULL, NULL, NULL, NULL),
(597, 397, 373, 500.00, 0.00, NULL, 'UEF6B48NW5', NULL, '2026-05-15', '2026-05-15 11:37:25', '2026-05-15 11:37:25', NULL, NULL, NULL, NULL),
(598, 396, 372, 49500.00, 0.00, NULL, 'UEGC14PJ2J', NULL, '2026-05-16', '2026-05-16 17:32:07', '2026-05-16 17:32:07', NULL, NULL, NULL, NULL),
(599, 395, 371, 20000.00, 0.00, NULL, 'UEGKB4AV3Z', NULL, '2026-05-16', '2026-05-16 17:32:46', '2026-05-16 17:32:46', NULL, NULL, NULL, NULL),
(600, 397, 373, 1860.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-15', '2026-05-16 17:34:12', '2026-05-16 17:34:12', NULL, NULL, NULL, NULL),
(601, 405, 381, 8368.00, 0.00, NULL, 'UEGHD4KWUR', NULL, '2026-05-16', '2026-05-16 17:51:51', '2026-05-16 17:51:51', NULL, NULL, NULL, NULL),
(602, 405, 381, 2000.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-05-16', '2026-05-16 17:52:20', '2026-05-16 17:52:20', NULL, NULL, NULL, NULL),
(603, 400, 376, 4320.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-16', '2026-05-19 10:06:10', '2026-05-19 10:06:22', NULL, NULL, NULL, NULL),
(604, 398, 374, 8400.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-17', '2026-05-19 10:12:48', '2026-05-19 10:12:48', NULL, NULL, NULL, NULL),
(605, 406, 382, 105231.05, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-17', '2026-05-19 10:17:06', '2026-05-19 10:17:54', NULL, NULL, NULL, NULL),
(606, 395, 371, 22000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-16', '2026-05-19 10:19:50', '2026-05-19 10:19:50', NULL, NULL, NULL, NULL),
(607, 401, 377, 90000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-17', '2026-05-20 06:47:52', '2026-05-20 06:47:52', NULL, NULL, NULL, NULL),
(608, 413, 389, 2000.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-05-21', '2026-05-21 07:17:51', '2026-05-21 07:17:51', NULL, NULL, NULL, NULL),
(609, 421, 397, 22000.00, 0.00, NULL, 'UEKKB4OOIA', NULL, '2026-05-20', '2026-05-21 07:36:19', '2026-05-21 07:36:19', NULL, NULL, NULL, NULL),
(610, 413, 389, 26800.00, 0.00, NULL, 'UELD84YBXA', NULL, '2026-05-21', '2026-05-21 07:40:18', '2026-05-21 07:40:18', NULL, NULL, NULL, NULL),
(611, 413, 389, 1200.00, 0.00, NULL, 'UELD84YB8S', NULL, '2026-05-21', '2026-05-21 07:40:46', '2026-05-21 07:41:26', NULL, NULL, NULL, NULL),
(612, 414, 390, 200.00, 0.00, NULL, 'UELD84YB8S', NULL, '2026-05-21', '2026-05-21 07:42:02', '2026-05-21 07:42:02', NULL, NULL, NULL, NULL),
(613, 414, 390, 2400.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-05-27', '2026-05-21 07:42:42', '2026-05-21 07:42:42', NULL, NULL, NULL, NULL),
(614, 419, 395, 1500.00, 0.00, NULL, 'UEM6B50I4E', NULL, '2026-05-22', '2026-05-23 09:52:39', '2026-05-23 09:53:46', NULL, NULL, NULL, NULL),
(615, 419, 395, 3500.00, 0.00, NULL, 'UEM6B51FWV', NULL, '2026-05-22', '2026-05-23 09:53:01', '2026-05-23 09:53:01', NULL, NULL, NULL, NULL),
(616, 419, 395, 3000.00, 0.00, NULL, 'UEM6B51TIB', NULL, '2026-05-22', '2026-05-23 09:53:17', '2026-05-23 09:53:17', NULL, NULL, NULL, NULL),
(617, 419, 395, 1000.00, 0.00, NULL, 'UEM6B528H6', NULL, '2026-05-22', '2026-05-23 09:53:36', '2026-05-23 09:53:36', NULL, NULL, NULL, NULL),
(618, 415, 391, 800.00, 0.00, NULL, 'BROKER FEES', NULL, '2026-05-24', '2026-05-25 04:28:36', '2026-05-25 04:30:21', NULL, NULL, NULL, NULL),
(619, 411, 387, 120000.00, 0.00, NULL, 'UEP6O59T1H', NULL, '2026-05-25', '2026-05-25 06:26:23', '2026-05-25 06:26:23', NULL, NULL, NULL, NULL),
(620, 416, 392, 312.00, 0.00, NULL, 'UEP6B5AH40', NULL, '2026-05-24', '2026-05-25 06:27:23', '2026-05-25 06:27:23', NULL, NULL, NULL, NULL),
(621, 416, 392, 1920.00, 0.00, NULL, 'SPLIT', NULL, '2026-05-25', '2026-05-25 06:28:13', '2026-05-25 06:28:13', NULL, NULL, NULL, NULL),
(622, 422, 398, 90000.00, 0.00, NULL, 'UENS80AAKF', NULL, '2026-05-23', '2026-05-25 06:36:34', '2026-05-25 06:36:34', NULL, NULL, NULL, NULL),
(623, 415, 391, 11200.00, 0.00, NULL, 'UEPD85ER7Y', NULL, '2026-05-24', '2026-05-25 07:41:23', '2026-05-25 07:41:43', NULL, NULL, NULL, NULL),
(624, 417, 393, 5184.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-26', '2026-05-27 05:44:50', '2026-05-27 05:44:50', NULL, NULL, NULL, NULL),
(625, 407, 383, 1728.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-22', '2026-05-27 05:55:08', '2026-05-27 05:55:08', NULL, NULL, NULL, NULL),
(626, 410, 386, 18000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-24', '2026-05-27 06:01:50', '2026-05-27 06:01:50', NULL, NULL, NULL, NULL),
(627, 419, 395, 500.00, 0.00, NULL, 'UEP6B5EJDN', NULL, '2026-05-25', '2026-05-27 06:05:00', '2026-05-27 06:05:00', NULL, NULL, NULL, NULL),
(628, 419, 395, 500.00, 0.00, NULL, 'UEQ6B5EBCR', NULL, '2026-05-26', '2026-05-27 06:05:12', '2026-05-27 06:05:12', NULL, NULL, NULL, NULL),
(629, 421, 397, 2840.00, 0.00, NULL, 'CREDIT DISCOUNT', NULL, '2026-05-26', '2026-05-27 06:07:50', '2026-05-27 08:48:01', NULL, NULL, NULL, NULL),
(630, 408, 384, 85500.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-21', '2026-05-27 06:09:01', '2026-05-27 06:09:01', NULL, NULL, NULL, NULL),
(631, 404, 380, 10000.00, 0.00, NULL, 'UEQ9X5UK7N', NULL, '2026-05-25', '2026-05-27 06:11:41', '2026-06-03 10:14:56', NULL, NULL, NULL, NULL),
(632, 404, 380, 10000.00, 0.00, NULL, 'UEM9X5AXNZ', NULL, '2026-05-21', '2026-05-27 06:13:20', '2026-05-27 06:13:20', NULL, NULL, NULL, NULL),
(633, 393, 369, 2160.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-25', '2026-05-27 06:14:03', '2026-05-27 06:14:03', NULL, NULL, NULL, NULL),
(634, 421, 397, 2000.00, 0.00, NULL, 'UERKB5FR0A', NULL, '2026-05-27', '2026-05-27 08:47:42', '2026-05-27 08:47:42', NULL, NULL, NULL, NULL),
(635, 419, 395, 500.00, 0.00, NULL, 'UEQ9B5G69Y', NULL, '2026-05-26', '2026-05-27 09:04:24', '2026-05-27 09:04:24', NULL, NULL, NULL, NULL),
(636, 414, 390, 33400.00, 0.00, NULL, 'UERD85OLFU', NULL, '2026-05-27', '2026-05-28 03:43:05', '2026-05-28 03:43:05', NULL, NULL, NULL, NULL),
(637, 418, 394, 15000.00, 0.00, NULL, 'UESC1606GM', NULL, '2026-05-28', '2026-05-28 03:43:47', '2026-05-28 03:43:47', NULL, NULL, NULL, NULL),
(638, 380, 356, 20000.00, 0.00, NULL, 'UERGC5R2N7', NULL, '2026-05-27', '2026-05-28 04:20:35', '2026-05-28 04:20:35', NULL, NULL, NULL, NULL),
(639, 349, 325, 111306.20, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-27', '2026-05-28 06:26:49', '2026-05-28 06:26:49', NULL, NULL, NULL, NULL),
(640, 253, 238, 50000.00, 0.00, NULL, 'UET6O5QG5M', NULL, '2026-05-29', '2026-05-29 08:06:53', '2026-05-29 08:06:53', NULL, NULL, NULL, NULL),
(641, 419, 395, 1500.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-27', '2026-06-03 07:25:36', '2026-06-03 07:25:36', NULL, NULL, NULL, NULL),
(642, 431, 407, 1000.00, 0.00, NULL, 'UF26B69UKQ', NULL, '2026-06-02', '2026-06-03 07:28:21', '2026-06-03 07:28:21', NULL, NULL, NULL, NULL),
(643, 427, 403, 102600.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-31', '2026-06-03 07:33:23', '2026-06-03 07:33:23', NULL, NULL, NULL, NULL),
(644, 423, 399, 3600.00, 0.00, NULL, 'UEUAL5YKBX', NULL, '2026-05-30', '2026-06-03 07:41:09', '2026-06-03 07:41:09', NULL, NULL, NULL, NULL),
(645, 418, 394, 21000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-28', '2026-06-03 07:45:58', '2026-06-03 07:46:12', NULL, NULL, NULL, NULL),
(646, 434, 410, 8000.00, 0.00, NULL, 'UEUC16CFOB', NULL, '2026-05-30', '2026-06-03 07:49:59', '2026-06-03 07:49:59', NULL, NULL, NULL, NULL),
(647, 420, 396, 126277.30, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-28', '2026-06-03 07:52:08', '2026-06-03 07:52:42', NULL, NULL, NULL, NULL),
(648, 425, 401, 2073.60, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-01', '2026-06-03 09:59:59', '2026-06-03 09:59:59', NULL, NULL, NULL, NULL),
(649, 404, 380, 124000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-05-25', '2026-06-03 10:02:47', '2026-06-03 10:15:08', NULL, NULL, NULL, NULL),
(650, 431, 407, 1000.00, 0.00, NULL, 'UF56B6LEBY', NULL, '2026-06-05', '2026-06-07 09:43:15', '2026-06-07 09:43:15', NULL, NULL, NULL, NULL),
(651, 431, 407, 1000.00, 0.00, NULL, 'UF56B6LKQZ', NULL, '2026-06-05', '2026-06-07 09:43:26', '2026-06-07 09:43:26', NULL, NULL, NULL, NULL),
(652, 431, 407, 600.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-05', '2026-06-07 09:44:17', '2026-06-07 09:44:17', NULL, NULL, NULL, NULL),
(653, 430, 406, 10800.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-06', '2026-06-08 07:28:14', '2026-06-08 07:28:14', NULL, NULL, NULL, NULL),
(654, 434, 410, 17200.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-06', '2026-06-08 07:30:31', '2026-06-08 07:30:31', NULL, NULL, NULL, NULL),
(655, 424, 400, 6220.80, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-05', '2026-06-08 07:37:29', '2026-06-08 07:37:29', NULL, NULL, NULL, NULL),
(656, 412, 388, 42000.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-05', '2026-06-08 09:34:38', '2026-06-08 09:34:38', NULL, NULL, NULL, NULL),
(657, 402, 378, 493534.10, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-03', '2026-06-08 09:36:53', '2026-06-08 09:36:53', NULL, NULL, NULL, NULL),
(658, 433, 409, 16800.00, 0.00, NULL, 'UF9AL73GVV', NULL, '2026-06-09', '2026-06-09 07:09:12', '2026-06-09 07:09:12', NULL, NULL, NULL, NULL),
(659, 426, 402, 21600.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-03', '2026-06-09 07:09:43', '2026-06-09 07:09:43', NULL, NULL, NULL, NULL),
(660, 439, 415, 20000.00, 0.00, NULL, 'UFBC17P6SL', NULL, '2026-06-11', '2026-06-11 10:26:51', '2026-06-11 10:26:51', NULL, NULL, NULL, NULL),
(661, 441, 417, 21000.00, 0.00, NULL, 'UFCFR7DCQ4', NULL, '2026-06-12', '2026-06-14 03:08:11', '2026-06-14 03:08:11', NULL, NULL, NULL, NULL),
(662, 443, 419, 25920.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-13', '2026-06-14 03:14:51', '2026-06-14 03:14:51', NULL, NULL, NULL, NULL),
(663, 409, 385, 187245.92, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-08', '2026-06-14 03:17:33', '2026-06-14 03:18:22', NULL, NULL, NULL, NULL),
(664, 432, 408, 123120.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-10', '2026-06-14 03:19:48', '2026-06-14 03:19:48', NULL, NULL, NULL, NULL),
(665, 435, 411, 2488.32, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-10', '2026-06-14 03:24:46', '2026-06-14 03:25:26', NULL, NULL, NULL, NULL),
(666, 440, 416, 7465.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-15', '2026-06-16 02:33:43', '2026-06-16 02:33:43', NULL, NULL, NULL, NULL),
(667, 439, 415, 640.00, 0.00, NULL, 'UFHC18DMB6', NULL, '2026-06-17', '2026-06-17 06:48:16', '2026-06-17 06:48:16', NULL, NULL, NULL, NULL),
(668, 438, 414, 16560.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-16', '2026-06-17 06:55:15', '2026-06-17 06:55:15', NULL, NULL, NULL, NULL),
(669, 437, 413, 12720.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-16', '2026-06-18 11:10:14', '2026-06-18 11:10:14', NULL, NULL, NULL, NULL),
(670, 451, 427, 12000.00, 0.00, NULL, 'UFLAL8IK0C', NULL, '2026-06-21', '2026-06-21 09:20:24', '2026-06-21 09:20:32', NULL, NULL, NULL, NULL),
(671, 444, 420, 31104.00, 0.00, NULL, 'UFM1U8Q181', NULL, '2026-06-22', '2026-06-23 11:20:34', '2026-06-23 11:20:34', NULL, NULL, NULL, NULL),
(672, 446, 422, 2986.00, 0.00, NULL, 'ROLL OVER', NULL, '2026-06-21', '2026-06-25 08:39:35', '2026-06-25 08:39:35', NULL, NULL, NULL, NULL),
(673, 441, 417, 8000.00, 0.00, NULL, 'UFPBZ92LSW', NULL, '2026-06-25', '2026-06-25 17:21:33', '2026-06-25 17:21:33', NULL, NULL, NULL, NULL),
(674, 448, 424, 19872.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-06-29 04:41:37', '2026-06-29 04:41:37', NULL, NULL, NULL, NULL),
(675, 454, 430, 56000.00, 0.00, NULL, 'UFTD89HO7C', 'mpesa', '2026-06-29', '2026-06-30 15:04:43', '2026-06-30 15:04:43', NULL, NULL, NULL, NULL),
(676, 454, 430, 4000.00, 0.00, NULL, 'BROKER FEE', 'cash', '2026-06-29', '2026-06-30 15:05:03', '2026-06-30 15:05:03', NULL, NULL, NULL, NULL),
(677, 449, 425, 15264.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-07-01 11:39:26', '2026-07-01 11:40:34', NULL, NULL, NULL, NULL),
(678, 441, 417, 21400.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-26', '2026-07-01 11:43:35', '2026-07-01 11:43:35', NULL, NULL, NULL, NULL),
(679, 380, 356, 85600.00, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-20', '2026-07-01 11:48:50', '2026-07-01 11:48:50', NULL, NULL, NULL, NULL),
(680, 456, 432, 151532.80, 0.00, NULL, 'ROLL OVER', 'cash', '2026-06-18', '2026-07-01 11:51:53', '2026-07-01 11:51:53', NULL, NULL, NULL, NULL),
(681, 442, 418, 592240.92, 0.00, NULL, 'ROLL OVER', 'cash', '2026-07-03', '2026-07-03 12:35:07', '2026-07-03 12:35:07', NULL, NULL, NULL, NULL),
(682, 453, 429, 12000.00, 0.00, NULL, 'UG3AL9V2DH', 'mpesa', '2026-07-03', '2026-07-04 16:31:24', '2026-07-04 16:31:24', NULL, NULL, NULL, NULL),
(683, 452, 428, 3583.20, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-01', '2026-07-04 16:32:31', '2026-07-04 16:32:31', NULL, NULL, NULL, NULL),
(684, 447, 423, 8958.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-06-25', '2026-07-04 16:36:50', '2026-07-04 16:36:50', NULL, NULL, NULL, NULL),
(685, 466, 442, 24000.00, 0.00, NULL, 'UG3MDA4JL4', 'mpesa', '2026-07-03', '2026-07-04 16:43:46', '2026-07-04 16:43:46', NULL, NULL, NULL, NULL),
(686, 428, 404, 2592.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-06-25', '2026-07-04 17:05:39', '2026-07-04 17:05:39', NULL, NULL, NULL, NULL),
(687, 458, 434, 240000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-10', '2026-07-10 08:35:03', '2026-07-10 08:35:03', NULL, NULL, NULL, NULL),
(688, 299, 278, 40000.00, 0.00, NULL, 'I&M BANK LOAN', 'bank_transfer', '2026-06-23', '2026-07-10 08:40:43', '2026-07-10 08:40:43', NULL, NULL, NULL, NULL),
(689, 459, 435, 18316.80, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-06', '2026-07-10 08:42:22', '2026-07-10 08:42:22', NULL, NULL, NULL, NULL),
(690, 358, 334, 12000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'other', '2026-04-06', '2026-07-13 08:52:30', '2026-07-13 08:53:27', NULL, NULL, NULL, NULL),
(691, 347, 324, 50000.00, 0.00, NULL, 'UFM4A9ISXQ', 'mpesa', '2026-06-22', '2026-07-13 08:54:27', '2026-07-13 08:54:27', NULL, NULL, NULL, NULL),
(692, 347, 324, 50000.00, 0.00, NULL, 'UFJ4A96KAW', 'mpesa', '2026-06-19', '2026-07-13 08:55:35', '2026-07-13 08:55:35', NULL, NULL, NULL, NULL),
(693, 347, 324, 578000.00, 0.00, NULL, 'BAD DEBT', 'other', '2026-06-22', '2026-07-13 08:56:16', '2026-07-13 08:56:16', NULL, NULL, NULL, NULL),
(694, 457, 433, 37046.40, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-06', '2026-07-13 09:01:49', '2026-07-13 09:01:49', NULL, NULL, NULL, NULL),
(695, 464, 440, 5159.81, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-11', '2026-07-13 09:04:42', '2026-07-13 09:04:42', NULL, NULL, NULL, NULL),
(696, 455, 431, 9104.00, 0.00, NULL, 'UGE6OB0KZB', 'mpesa', '2026-07-13', '2026-07-15 07:59:52', '2026-07-15 08:01:20', NULL, NULL, NULL, NULL),
(697, 455, 431, 2896.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-07-13', '2026-07-15 08:01:40', '2026-07-15 08:01:40', NULL, NULL, NULL, NULL),
(698, 469, 445, 5000.00, 0.00, NULL, 'UGE6OB0WBJ', 'mpesa', '2026-07-14', '2026-07-15 08:02:40', '2026-07-15 08:02:40', NULL, NULL, NULL, NULL),
(699, 469, 445, 34400.00, 0.00, NULL, 'UGF6OB5I3A', 'pesalink', '2026-07-14', '2026-07-15 12:43:36', '2026-07-15 12:50:01', NULL, NULL, NULL, NULL),
(700, 469, 445, 9000.00, 0.00, NULL, 'UGD6OAXEJ4', 'mpesa', '2026-07-13', '2026-07-15 12:45:00', '2026-07-15 12:45:00', NULL, NULL, NULL, NULL),
(701, 469, 445, 11000.00, 0.00, NULL, 'UGD6OAWZYL', 'mpesa', '2026-07-13', '2026-07-15 12:47:06', '2026-07-15 12:47:06', NULL, NULL, NULL, NULL),
(702, 469, 445, 6000.00, 0.00, NULL, 'UGD6OAYB94', 'mpesa', '2026-07-13', '2026-07-15 12:47:44', '2026-07-15 12:47:44', NULL, NULL, NULL, NULL),
(703, 469, 445, 6600.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-07-13', '2026-07-15 12:48:22', '2026-07-15 12:50:56', NULL, NULL, NULL, NULL),
(704, 467, 443, 36000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-14', '2026-07-16 06:03:25', '2026-07-16 06:03:25', NULL, NULL, NULL, NULL),
(705, 422, 398, 18000.00, 0.00, NULL, 'BAD DEBT', 'other', '2026-05-23', '2026-07-16 07:10:40', '2026-07-16 07:14:36', NULL, NULL, NULL, NULL),
(706, 471, 447, 40000.00, 0.00, NULL, 'UGG3XBQ6UD', 'mpesa', '2026-07-16', '2026-07-16 11:18:53', '2026-07-16 11:18:53', NULL, NULL, NULL, NULL),
(707, 475, 451, 44455.68, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-16', '2026-07-17 03:51:49', '2026-07-17 03:52:08', NULL, NULL, NULL, NULL),
(708, 460, 436, 13000.00, 0.00, NULL, 'UGH16BGZ9P', 'mpesa', '2026-07-16', '2026-07-17 07:49:38', '2026-07-17 07:49:38', NULL, NULL, NULL, NULL),
(709, 473, 449, 21980.16, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-16', '2026-07-17 07:50:20', '2026-07-17 07:50:31', NULL, NULL, NULL, NULL),
(710, 471, 447, 32000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-16', '2026-07-18 15:11:34', '2026-07-18 15:11:34', NULL, NULL, NULL, NULL),
(711, 468, 444, 120000.00, 0.00, NULL, 'UGJ0N01XWP', 'mpesa', '2026-07-19', '2026-07-19 14:39:02', '2026-07-19 14:39:02', NULL, NULL, NULL, NULL);
INSERT INTO `repayments` (`id`, `loan_id`, `loan_cycle_id`, `amount`, `processing_fee`, `net_amount`, `transaction`, `mode`, `repayment_date`, `created_at`, `updated_at`, `deleted_at`, `partner_transaction_id`, `investment_id`, `notes`) VALUES
(712, 472, 448, 288000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-20', '2026-07-20 09:13:36', '2026-07-20 09:13:36', NULL, NULL, NULL, NULL),
(713, 476, 452, 6191.80, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-21', '2026-07-22 06:27:47', '2026-07-22 06:27:47', NULL, NULL, NULL, NULL),
(715, 474, 450, 7000.00, 0.00, NULL, 'UGPC10SGK8', 'mpesa', '2026-07-25', '2026-07-25 12:16:22', '2026-07-25 12:16:22', NULL, NULL, NULL, NULL),
(716, 474, 450, 6200.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-25', '2026-07-25 12:16:44', '2026-07-25 12:17:11', NULL, NULL, NULL, NULL),
(717, 470, 446, 3110.40, 0.00, NULL, 'CREDIT DISCOUNT', 'other', '2026-07-25', '2026-07-28 09:56:31', '2026-07-28 09:56:31', NULL, NULL, NULL, NULL),
(718, 479, 455, 26376.20, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-07-28 10:01:08', '2026-07-28 10:01:44', NULL, NULL, NULL, NULL),
(719, 480, 456, 38400.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-07-30 07:04:41', '2026-07-30 07:04:41', NULL, NULL, NULL, NULL),
(720, 481, 457, 374400.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-30', '2026-08-03 14:19:21', '2026-08-03 14:19:21', NULL, NULL, NULL, NULL),
(721, 478, 454, 53346.82, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-26', '2026-08-03 15:52:20', '2026-08-03 15:52:35', NULL, NULL, NULL, NULL),
(722, 482, 458, 120000.00, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-30', '2026-08-03 15:57:15', '2026-08-03 15:57:15', NULL, NULL, NULL, NULL),
(723, 460, 436, 12680.00, 0.00, NULL, 'BAD DEBT', 'other', '2026-07-17', '2026-08-03 16:08:08', '2026-08-03 16:08:33', NULL, NULL, NULL, NULL),
(724, 483, 459, 7430.16, 0.00, NULL, 'ROLL OVER', 'other', '2026-07-31', '2026-08-03 16:24:22', '2026-08-03 16:24:37', NULL, NULL, NULL, NULL),
(726, 484, 460, 28000.00, 0.00, 28000.00, 'UH5D81PY6D', 'mpesa', '2026-08-05', '2026-08-06 06:53:04', '2026-08-06 06:53:04', NULL, NULL, NULL, NULL),
(727, 484, 460, 2000.00, 0.00, 2000.00, 'BROKER FEES', 'other', '2026-08-05', '2026-08-06 06:53:31', '2026-08-06 06:56:15', NULL, NULL, NULL, NULL),
(728, 484, 460, 3000.00, 0.00, 3000.00, 'BAD DEBT', 'other', '2026-08-05', '2026-08-06 06:54:17', '2026-08-06 06:54:17', NULL, NULL, NULL, NULL),
(729, 485, 513, 4500.00, 0.00, 4500.00, 'UH6C127I8I', 'mpesa', '2026-08-06', '2026-08-06 10:09:34', '2026-08-06 10:09:34', NULL, NULL, NULL, NULL),
(730, 487, 463, 46080.00, 0.00, 46080.00, 'UH73X2AE0G', 'mpesa', '2026-08-07', '2026-08-07 16:52:37', '2026-08-07 16:52:37', NULL, NULL, NULL, NULL),
(731, 487, 463, 4608.00, 0.00, 4608.00, 'CREDIT DISCOUNT', 'other', '2026-08-07', '2026-08-07 16:53:40', '2026-08-07 16:53:40', NULL, NULL, NULL, NULL),
(732, 489, 465, 66776.20, 0.00, 66776.20, 'ROLL OVER', 'other', '2026-08-05', '2026-08-07 16:55:08', '2026-08-07 16:55:08', NULL, NULL, NULL, NULL),
(733, 465, 441, 8000.00, 0.00, 8000.00, 'UHDAI2Z66Q', 'mpesa', '2026-08-13', '2026-08-16 13:10:58', '2026-08-16 13:10:58', NULL, NULL, NULL, NULL),
(734, 493, 522, 8712.00, 0.00, 8712.00, 'UHJLP2YPY0', 'mpesa', '2026-08-19', '2026-08-19 04:51:56', '2026-08-19 04:52:26', NULL, NULL, NULL, NULL),
(735, 495, 524, 1000.00, 0.00, 1000.00, 'UHJRC3EL3S', 'mpesa', '2026-08-19', '2026-08-19 11:27:51', '2026-08-19 11:27:51', NULL, NULL, NULL, NULL),
(736, 496, 525, 13440.00, 0.00, 13440.00, 'UHKBD3SLSL', 'mpesa', '2026-08-19', '2026-08-20 07:38:58', '2026-08-20 07:54:42', NULL, NULL, NULL, NULL),
(737, 496, 525, 960.00, 0.00, 960.00, 'BROKER FEE', 'cash', '2026-08-18', '2026-08-20 07:42:11', '2026-08-20 07:42:29', NULL, NULL, NULL, NULL),
(738, 497, 530, 6000.00, 0.00, 6000.00, 'UHN1X423XN', 'mpesa', '2026-08-23', '2026-08-24 08:15:26', '2026-08-24 08:15:26', NULL, NULL, NULL, NULL),
(739, 488, 539, 120000.00, 0.00, 120000.00, 'UHMEX43CEF', 'mpesa', '2026-08-23', '2026-08-24 13:55:59', '2026-08-24 13:55:59', NULL, NULL, NULL, NULL),
(740, 485, 528, 5340.00, 0.00, 5340.00, 'UHPC14FQIN', 'mpesa', '2026-08-25', '2026-08-25 12:03:43', '2026-08-25 12:03:43', NULL, NULL, NULL, NULL),
(741, 450, 541, 20000.00, 0.00, NULL, 'UHRCJ4CA3O', 'mpesa', '2026-08-27', '2026-08-28 05:46:39', '2026-08-28 05:46:39', NULL, NULL, NULL, NULL),
(742, 495, 529, 5600.00, 0.00, NULL, 'UHTBD4Y1QE', 'mpesa', '2026-08-29', '2026-08-31 08:26:28', '2026-08-31 08:26:28', NULL, NULL, NULL, NULL),
(743, 495, 529, 400.00, 0.00, NULL, 'BROKER FEES', 'other', '2026-08-29', '2026-08-31 08:26:55', '2026-08-31 08:26:55', NULL, NULL, NULL, NULL),
(744, 507, 548, 6000.00, 0.00, NULL, 'UHSOQ4DWIY', 'mpesa', '2026-08-28', '2026-08-31 08:53:44', '2026-08-31 08:53:44', NULL, NULL, NULL, NULL),
(745, 450, 541, 5000.00, 0.00, NULL, 'UI1CJ4UEFP', 'mpesa', '2026-09-01', '2026-09-01 12:07:30', '2026-09-01 12:07:30', NULL, NULL, NULL, NULL),
(746, 486, 462, 1000.00, 0.00, NULL, 'UI16B4PGWM', 'mpesa', '2026-09-01', '2026-09-01 12:17:34', '2026-09-01 12:17:34', NULL, NULL, NULL, NULL),
(747, 486, 462, 1000.00, 0.00, NULL, 'UI16B4SQM4', 'mpesa', '2026-09-01', '2026-09-01 17:19:43', '2026-09-01 17:19:43', NULL, NULL, NULL, NULL),
(748, 499, 532, 40000.00, 0.00, NULL, 'UI21G568FH', 'mpesa', '2026-09-02', '2026-09-02 07:20:03', '2026-09-02 07:20:03', NULL, NULL, NULL, NULL),
(749, 498, 531, 60000.00, 0.00, NULL, 'UI10N55JL6', 'mpesa', '2026-09-01', '2026-09-02 07:29:45', '2026-09-02 07:29:45', NULL, NULL, NULL, NULL),
(750, 503, 536, 24000.00, 0.00, NULL, 'UI26O4T739', 'mpesa', '2026-09-02', '2026-09-02 13:16:36', '2026-09-02 13:16:36', NULL, NULL, NULL, NULL),
(751, 502, 535, 1200.00, 0.00, NULL, 'UI26O4SZO7', 'mpesa', '2026-09-02', '2026-09-02 13:21:12', '2026-09-02 13:21:12', NULL, NULL, NULL, NULL),
(752, 502, 535, 800.00, 0.00, NULL, 'BROKER FEES', 'mpesa', '2026-09-02', '2026-09-02 13:21:30', '2026-09-02 13:21:30', NULL, NULL, NULL, NULL),
(753, 486, 462, 1000.00, 0.00, NULL, 'UI36B4ZMHT', 'mpesa', '2026-09-03', '2026-09-03 10:52:14', '2026-09-03 10:52:14', NULL, NULL, NULL, NULL),
(754, 501, 534, 11940.00, 0.00, NULL, 'UI3RC560ZJ', 'mpesa', '2026-09-03', '2026-09-03 13:21:03', '2026-09-03 13:21:03', NULL, NULL, NULL, NULL),
(755, 501, 534, 60.00, 0.00, NULL, 'TRANSACTION FEES', 'mpesa', '2026-09-03', '2026-09-03 13:21:22', '2026-09-03 13:21:22', NULL, NULL, NULL, NULL),
(756, 486, 560, 1000.00, 0.00, NULL, 'UI36B50VCE', 'mpesa', '2026-09-03', '2026-09-03 14:38:05', '2026-09-03 14:38:05', NULL, NULL, NULL, NULL),
(757, 502, 554, 10000.00, 0.00, NULL, 'UI4D855TLE', 'mpesa', '2026-09-04', '2026-09-04 11:12:50', '2026-09-04 11:12:50', NULL, NULL, NULL, NULL),
(758, 299, 278, 9900.00, 0.00, NULL, 'UHK6O3ARCA', 'mpesa', '2026-08-20', '2026-09-07 06:30:28', '2026-09-07 06:30:28', NULL, NULL, NULL, NULL),
(759, 299, 278, 3100.00, 0.00, NULL, 'I&M Bank Loan', 'cash', '2026-09-20', '2026-09-07 06:32:47', '2026-09-07 06:32:47', NULL, NULL, NULL, NULL),
(760, 508, 552, 45000.00, 0.00, NULL, 'UI78O5YYIN', 'mpesa', '2026-09-07', '2026-09-09 09:13:39', '2026-09-09 09:13:39', NULL, NULL, NULL, NULL),
(761, 509, 553, 6720.00, 0.00, NULL, 'UI9BD69ZCP', 'mpesa', '2026-09-09', '2026-09-09 17:03:05', '2026-09-09 17:03:05', NULL, NULL, NULL, NULL),
(762, 509, 553, 480.00, 0.00, NULL, 'BROKER FEES', 'other', '2026-09-09', '2026-09-09 17:03:27', '2026-09-09 17:03:27', NULL, NULL, NULL, NULL),
(763, 505, 545, 4000.00, 0.00, NULL, 'UIAKB5PYSC', 'mpesa', '2026-09-10', '2026-09-11 05:07:55', '2026-09-11 05:07:55', NULL, NULL, NULL, NULL),
(764, 461, 437, 100000.00, 0.00, NULL, 'UIAGC5Z6RF', 'mpesa', '2026-09-10', '2026-09-11 05:12:33', '2026-09-11 05:12:33', NULL, NULL, NULL, NULL),
(765, 514, 566, 65000.00, 0.00, NULL, 'UIB0N6DBUD', 'mpesa', '2026-09-11', '2026-09-14 07:25:21', '2026-09-14 07:25:21', NULL, NULL, NULL, NULL),
(766, 510, 556, 34500.00, 0.00, 34500.00, '483429722817', 'bank_transfer', '2026-09-29', '2026-09-14 07:27:16', '2026-09-29 12:35:15', NULL, NULL, NULL, NULL),
(767, 486, 560, 1000.00, 0.00, NULL, 'UIC6B645BU', 'mpesa', '2026-09-12', '2026-09-14 07:43:57', '2026-09-14 07:43:57', NULL, NULL, NULL, NULL),
(768, 499, 532, 20000.00, 0.00, NULL, 'UIECL6R3TO', 'mpesa', '2026-09-14', '2026-09-14 14:10:38', '2026-09-14 14:10:38', NULL, NULL, NULL, NULL),
(769, 512, 561, 24000.00, 0.00, NULL, 'UIEMD6Q33J', 'mpesa', '2026-09-14', '2026-09-14 16:51:44', '2026-09-14 16:51:44', NULL, NULL, NULL, NULL),
(770, 193, 576, 236800.00, 0.00, NULL, '0068005720260915181600APF9A247', 'pesalink', '2026-09-15', '2026-09-15 18:04:57', '2026-09-15 18:04:57', NULL, NULL, NULL, NULL),
(771, 189, 577, 163200.00, 0.00, NULL, '0068005720260915181600APF9A247', 'pesalink', '2026-09-15', '2026-09-15 18:07:51', '2026-09-15 18:07:51', NULL, NULL, NULL, NULL),
(772, 517, 570, 22000.00, 0.00, NULL, 'UIH9Q6UQSA', 'mpesa', '2026-09-17', '2026-09-18 13:59:49', '2026-09-18 13:59:49', NULL, NULL, NULL, NULL),
(773, 521, 575, 3000.00, 0.00, NULL, 'UIJCQ7CPAU', 'mpesa', '2026-09-19', '2026-09-23 03:40:30', '2026-09-23 03:40:30', NULL, NULL, NULL, NULL),
(774, 521, 575, 3000.00, 0.00, NULL, 'UINCQ7TY1W', 'mpesa', '2026-09-23', '2026-09-23 03:40:54', '2026-09-23 03:40:54', NULL, NULL, NULL, NULL),
(775, 516, 569, 8400.00, 0.00, NULL, 'UINB47QDSQ', 'mpesa', '2026-09-23', '2026-09-23 05:20:41', '2026-09-23 05:20:41', NULL, NULL, NULL, NULL),
(776, 518, 571, 13000.00, 0.00, 13000.00, 'UIOC181SVD', 'mpesa', '2026-09-24', '2026-09-25 09:28:37', '2026-09-25 09:29:37', NULL, NULL, NULL, NULL),
(777, 519, 572, 11200.00, 0.00, NULL, 'EQA5F9ECB6F0FF2', 'mpesa', '2026-09-23', '2026-09-25 09:34:17', '2026-09-25 09:34:17', NULL, NULL, NULL, NULL),
(778, 519, 572, 800.00, 0.00, NULL, 'BROKER FEES', 'other', '2026-09-23', '2026-09-25 09:34:46', '2026-09-25 09:34:46', NULL, NULL, NULL, NULL),
(779, 519, 572, 2400.00, 0.00, NULL, 'UIOLP79PSH', 'mpesa', '2026-09-24', '2026-09-25 09:35:29', '2026-09-25 09:35:29', NULL, NULL, NULL, NULL),
(780, 494, 555, 43600.00, 0.00, NULL, '051771616664', 'bank_transfer', '2026-09-23', '2026-09-25 09:42:36', '2026-09-25 09:42:36', NULL, NULL, NULL, NULL),
(781, 494, 555, 14000.00, 0.00, NULL, 'CREDIT DISCOUNT', 'cash', '2026-09-23', '2026-09-25 09:43:42', '2026-09-25 09:43:42', NULL, NULL, NULL, NULL),
(782, 494, 555, 11520.00, 0.00, NULL, 'INTEREST FEES', 'other', '2026-09-23', '2026-09-25 09:48:19', '2026-09-25 09:48:19', NULL, NULL, NULL, NULL),
(783, 505, 583, 26400.00, 0.00, NULL, 'UINKB7BK3J', 'mpesa', '2026-09-23', '2026-09-25 10:03:47', '2026-09-25 10:03:47', NULL, NULL, NULL, NULL),
(784, 523, 580, 2000.00, 0.00, NULL, 'UIR7G8158J', 'mpesa', '2026-09-27', '2026-09-28 08:36:31', '2026-09-28 08:36:31', NULL, NULL, NULL, NULL),
(785, 524, 581, 11200.00, 0.00, NULL, '0068005720260927135043AP9F5A32', 'pesalink', '2026-09-27', '2026-09-28 08:38:43', '2026-09-28 08:38:43', NULL, NULL, NULL, NULL),
(786, 524, 581, 800.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-09-27', '2026-09-28 08:41:47', '2026-09-28 08:41:47', NULL, NULL, NULL, NULL),
(787, 522, 578, 4800.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-09-26', '2026-09-28 08:42:39', '2026-09-28 08:42:39', NULL, NULL, NULL, NULL),
(788, 528, 592, 10000.00, 0.00, NULL, 'A7E6D14E0E4E1', 'mpesa', '2026-09-27', '2026-09-28 08:44:47', '2026-09-28 08:44:47', NULL, NULL, NULL, NULL),
(789, 527, 591, 1200.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-09-28', '2026-09-28 08:52:56', '2026-09-28 08:52:56', NULL, NULL, NULL, NULL),
(790, 525, 588, 2400.00, 0.00, NULL, 'UISGZ80QQZ', 'mpesa', '2026-09-28', '2026-09-30 10:06:14', '2026-09-30 10:06:14', NULL, NULL, NULL, NULL),
(791, 520, 594, 36000.00, 0.00, NULL, 'UIUMD8K4YQ', 'mpesa', '2026-09-30', '2026-09-30 10:10:33', '2026-09-30 10:10:33', NULL, NULL, NULL, NULL),
(792, 511, 557, 1.00, 0.00, 1.00, 'UIU6O85HM2', 'mpesa', '2026-09-30', '2026-09-30 13:21:40', '2026-09-30 13:22:40', NULL, NULL, NULL, NULL),
(793, 450, 600, 28000.00, 0.00, NULL, 'UIUCJ8DO32', 'mpesa', '2026-09-30', '2026-10-01 08:08:38', '2026-10-01 08:08:38', NULL, NULL, NULL, NULL),
(794, 516, 584, 20000.00, 0.00, NULL, 'UJ1B48QQI4', 'mpesa', '2026-10-01', '2026-10-02 10:00:43', '2026-10-02 10:00:43', NULL, NULL, NULL, NULL),
(795, 516, 584, 4480.00, 0.00, NULL, 'UJ1B48QSB3', 'mpesa', '2026-10-01', '2026-10-02 10:01:05', '2026-10-02 10:01:05', NULL, NULL, NULL, NULL),
(796, 506, 547, 1600.00, 0.00, NULL, 'UJ2C286DO0', 'mpesa', '2026-10-02', '2026-10-03 13:18:11', '2026-10-03 13:18:11', NULL, NULL, NULL, NULL),
(797, 531, 596, 10800.00, 0.00, NULL, 'UJ2CZ8BVRI', 'mpesa', '2026-10-02', '2026-10-03 13:23:19', '2026-10-03 13:23:19', NULL, NULL, NULL, NULL),
(798, 526, 590, 26800.00, 0.00, NULL, 'UISD8817IH', 'mpesa', '2026-09-28', '2026-10-03 13:36:54', '2026-10-03 13:36:54', NULL, NULL, NULL, NULL),
(799, 526, 590, 3200.00, 0.00, NULL, 'BROKER FEES', 'cash', '2026-09-28', '2026-10-03 13:37:20', '2026-10-03 13:37:20', NULL, NULL, NULL, NULL),
(800, 529, 606, 2500.00, 0.00, NULL, 'UJ7CQ9KQCR', 'mpesa', '2026-10-07', '2026-10-07 08:49:23', '2026-10-07 08:49:23', NULL, NULL, NULL, NULL),
(801, 530, 595, 14000.00, 0.00, NULL, 'UJ94W9FMW9', 'mpesa', '2026-10-09', '2026-10-09 07:42:46', '2026-10-09 07:42:46', NULL, NULL, NULL, NULL),
(802, 523, 599, 11200.00, 0.00, NULL, 'UJ9BD9XTEI', 'mpesa', '2026-10-09', '2026-10-09 18:09:21', '2026-10-09 18:09:21', NULL, NULL, NULL, NULL),
(803, 523, 599, 800.00, 0.00, NULL, 'BROKER FEE', 'cash', '2026-10-09', '2026-10-09 18:10:17', '2026-10-09 18:10:17', NULL, NULL, NULL, NULL),
(804, 533, 602, 18000.00, 0.00, NULL, 'UJCKB9KS51', 'mpesa', '2026-10-11', '2026-10-12 06:12:15', '2026-10-12 06:12:15', NULL, NULL, NULL, NULL),
(805, 515, 605, 14760.00, 0.00, NULL, 'UJC8OA845H', 'mpesa', '2026-10-12', '2026-10-12 07:18:30', '2026-10-12 07:18:30', NULL, NULL, NULL, NULL),
(806, 532, 613, 25000.00, 0.00, NULL, 'UJEMSA664A', 'mpesa', '2026-10-14', '2026-10-15 07:01:50', '2026-10-15 07:01:50', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `residence_types`
--

CREATE TABLE `residence_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `residence_types`
--

INSERT INTO `residence_types` (`id`, `name`, `slug`, `description`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Owned', 'owned', NULL, 1, 10, NULL, NULL),
(2, 'Rented', 'rented', NULL, 1, 20, NULL, NULL),
(3, 'Family', 'family', NULL, 1, 30, NULL, NULL),
(4, 'Employer Housing', 'employer_housing', NULL, 1, 40, NULL, NULL),
(5, 'Government Housing', 'government_housing', NULL, 1, 50, NULL, NULL),
(6, 'Other', 'other', NULL, 1, 999, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `risk_categories`
--

CREATE TABLE `risk_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `min_score` int(11) NOT NULL,
  `max_score` int(11) NOT NULL,
  `color_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_categories`
--

INSERT INTO `risk_categories` (`id`, `name`, `slug`, `min_score`, `max_score`, `color_code`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Very High Risk', 'very_high_risk', 0, 49, '#EF4444', NULL, NULL, NULL),
(2, 'High Risk', 'high_risk', 50, 64, '#F59E0B', NULL, NULL, NULL),
(3, 'Medium Risk', 'medium_risk', 65, 79, '#FBBF24', NULL, NULL, NULL),
(4, 'Low Risk', 'low_risk', 80, 100, '#10B981', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super-admin', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(2, 'admin', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(3, 'borrower', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(4, 'broker', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(5, 'teller', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(6, 'partner', 'web', '2026-09-04 12:04:20', '2026-10-06 19:16:02'),
(7, 'manager', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(8, 'loan-officer', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(9, 'accountant', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(10, 'recovery-officer', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02'),
(11, 'auditor', 'web', '2026-10-06 19:16:02', '2026-10-06 19:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 7),
(1, 8),
(1, 10),
(1, 11),
(2, 1),
(2, 2),
(2, 4),
(2, 5),
(2, 7),
(2, 8),
(3, 1),
(3, 2),
(3, 4),
(3, 5),
(3, 7),
(3, 8),
(3, 10),
(4, 1),
(4, 2),
(4, 7),
(5, 1),
(5, 2),
(5, 7),
(6, 1),
(6, 2),
(6, 5),
(6, 7),
(7, 1),
(7, 2),
(7, 7),
(7, 8),
(8, 1),
(8, 2),
(8, 4),
(8, 7),
(8, 10),
(8, 11),
(9, 1),
(9, 2),
(9, 7),
(10, 1),
(10, 2),
(10, 7),
(11, 1),
(11, 2),
(12, 1),
(13, 1),
(13, 2),
(13, 7),
(13, 9),
(13, 11),
(14, 1),
(14, 2),
(14, 7),
(14, 9),
(14, 11),
(15, 1),
(15, 2),
(15, 4),
(15, 5),
(15, 7),
(15, 8),
(15, 9),
(15, 11),
(16, 1),
(16, 2),
(16, 5),
(16, 7),
(17, 1),
(17, 2),
(17, 7),
(17, 9),
(18, 1),
(18, 2),
(18, 7),
(19, 1),
(19, 2),
(19, 3),
(19, 4),
(19, 5),
(19, 7),
(19, 8),
(19, 9),
(19, 10),
(19, 11),
(20, 1),
(20, 2),
(20, 5),
(20, 7),
(20, 10),
(21, 1),
(21, 2),
(21, 7),
(21, 9),
(22, 1),
(22, 2),
(22, 7),
(23, 1),
(23, 2),
(23, 7),
(23, 10),
(23, 11),
(24, 1),
(24, 2),
(24, 7),
(24, 10),
(25, 1),
(25, 2),
(25, 7),
(25, 10),
(26, 1),
(26, 2),
(26, 7),
(27, 1),
(27, 2),
(27, 6),
(27, 7),
(27, 11),
(28, 1),
(28, 2),
(28, 7),
(29, 1),
(29, 2),
(29, 7),
(30, 1),
(30, 2),
(30, 7),
(31, 1),
(31, 2),
(31, 6),
(31, 7),
(31, 11),
(32, 1),
(32, 2),
(32, 7),
(33, 1),
(33, 2),
(33, 7),
(34, 1),
(34, 2),
(34, 7),
(35, 1),
(35, 2),
(35, 3),
(35, 4),
(35, 5),
(35, 6),
(35, 7),
(35, 8),
(35, 9),
(35, 10),
(35, 11),
(36, 1),
(36, 2),
(36, 7),
(36, 8),
(36, 10),
(37, 1),
(37, 2),
(37, 7),
(37, 8),
(37, 10),
(38, 1),
(39, 1),
(40, 1),
(40, 2),
(41, 1),
(41, 2),
(41, 4),
(41, 5),
(41, 6),
(41, 7),
(41, 9),
(41, 11),
(42, 1),
(42, 2),
(42, 7),
(43, 1),
(43, 2),
(43, 7),
(43, 9),
(44, 1),
(44, 2),
(45, 1),
(45, 2),
(45, 7),
(45, 9),
(46, 1),
(46, 2),
(46, 9),
(47, 1),
(47, 2),
(47, 4),
(47, 5),
(47, 6),
(47, 7),
(47, 9),
(47, 11),
(48, 1),
(48, 2),
(48, 7),
(48, 9),
(49, 1),
(49, 2),
(49, 9),
(50, 1),
(50, 2),
(50, 7),
(50, 9),
(51, 1),
(51, 2),
(51, 4),
(51, 5),
(51, 7),
(51, 9),
(51, 11),
(52, 1),
(52, 2),
(52, 7),
(53, 1),
(53, 2),
(53, 7),
(53, 9),
(54, 1),
(54, 2),
(54, 4),
(54, 6),
(54, 7),
(54, 9),
(54, 11),
(55, 1),
(55, 2),
(55, 7),
(55, 9),
(55, 11),
(56, 1),
(56, 2),
(56, 7),
(56, 9),
(57, 1),
(57, 2),
(57, 7),
(57, 9),
(57, 11),
(58, 1),
(58, 2),
(59, 1),
(59, 2),
(59, 5),
(59, 6),
(59, 7),
(59, 9),
(59, 11),
(60, 1),
(60, 2),
(60, 7),
(61, 1),
(61, 2),
(61, 7),
(61, 9),
(62, 1),
(62, 2),
(62, 7),
(62, 9),
(63, 1),
(63, 2),
(63, 7),
(63, 8),
(63, 10),
(64, 1),
(64, 2),
(64, 7),
(64, 8),
(64, 10),
(65, 1),
(65, 2),
(65, 7),
(65, 10),
(66, 1),
(66, 2),
(66, 10),
(67, 1),
(67, 2),
(67, 11),
(68, 1),
(68, 2),
(68, 7),
(68, 9),
(68, 11),
(69, 1),
(69, 2),
(69, 7),
(69, 9),
(69, 11),
(70, 1),
(70, 2),
(70, 7),
(70, 9),
(70, 11),
(71, 1),
(71, 2),
(71, 7),
(72, 1),
(72, 2),
(72, 7),
(73, 1),
(73, 2),
(73, 7),
(74, 1),
(75, 1),
(75, 7),
(76, 1),
(77, 1),
(77, 11),
(78, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('6AZOJPhzJD6mSkMWKxUNGooQkn7wjUbSRDmne3I8', NULL, '41.60.250.33', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Mobile Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoia2dwSXZnaXhCcmZVYzQ2VzBwNHZ3eTJTOHhHbDlvREpqT0hscUhkbCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9hc3NldC1maW5hbmNlIjtzOjU6InJvdXRlIjtzOjk6InNpdGUucGFnZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1792058248),
('9lB6rlUggE3vQ2XWJrQXniKNUldaPtO85gatGOvH', NULL, '41.60.250.33', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRkpxN0Y1Z0FpcjZzOG5CSlhCcjdqVVFBVG5WWDczaGdRQmhSdkJEUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1792055730),
('aAThZAXoSloR35y4nFtAtUQCzA3nt2etd5qNoZx6', NULL, '41.60.250.33', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNHNuV2FZaXNxa3F2QVR5TjJYUEhXOXo4bVdXWkRBcDZkNnFiZWlXYiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9jcmItc29sdXRpb25zIjtzOjU6InJvdXRlIjtzOjk6InNpdGUucGFnZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1792058172),
('cbcl45szrokZiE3tap7IWzdTbw3Q1Q3knK7JGFVh', NULL, '41.60.250.33', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicXVoZ09GVU5JVHNPeXBBR1J0RE03dFBnVmNjVTlqdE1ScWJjQzhRbSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1792052072),
('fLwOwdZziePcGV4E7xOTbth3OtpjYo7mHgrEMa0f', NULL, '41.60.250.33', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiM0JvUWs3MlRZMURVZDZIcDN2d0J1a25oTG9TQnZ3YzNGTVZEdTVlbiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDE6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9hc3NldC1maW5hbmNlIjtzOjU6InJvdXRlIjtzOjk6InNpdGUucGFnZSI7fX0=', 1792058062),
('fWIWav8JYtKZ5hOHSwf60NQDeBSBpi8iizsnI4sf', NULL, '172.239.144.108', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibHN0ZDZPRzE4c3I2R2t1cGM5RjlIbU50b3lxN2s1Q3dpNms4djNBNSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9sb2Fucy5zaGFyZXQuYWZyaWNhIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1792057297),
('MqtqOK4Fh0z2oXVOxE4D19d7mwLtDxRVSbsfia6D', NULL, '41.57.111.27', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.4 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.4 facebookexternalhit/1.1 Facebot Twitterbot/1.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoidVRhQzA5RVdkRDFJc2FhT2oyek5sMTBabWtUbGo3dTA0MERHYlZDMiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMzoiaHR0cHM6Ly9sb2Fucy5zaGFyZXQuYWZyaWNhL2xvYW5zIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1792055259),
('oTyvYoZBCczolPcjHelgCW5UF6eoMM3marSdo95z', NULL, '185.113.249.146', 'curl/7.68.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoidFU3NXNNRjdORW5LMlRuYXRRQ0ZldENGeUdxNzcxVW5pNlRxSVg4QiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1792057797),
('P5tVZjKHamHfpQSrbFltuzVVkmjS4dNXMd5sYS6i', NULL, '185.113.249.146', 'curl/7.68.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiVXh3M1paQnhkWUFYY21maFR6d0o0bGt5RWZJV2VqZTAyaUlxell6YSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1792058074),
('Y3juEkUTm81DN330Tfff9yjqXg4Hw80LQacOFeFg', 63, '41.57.111.27', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.6.1 Safari/605.1.15', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNlpBbko0S3AxcWIwaFVldldScGFvR1hzc3lWdThGcGZxR242R29kMCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NjM7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS91c2Vycy82My9sb2Fucy81MjkiO3M6NToicm91dGUiO3M6MTY6InVzZXJzLmxvYW5zLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1792053056),
('zLQaOdR0lLjvNu3LXMK6kztp9gsrIALyIXTyXFMb', NULL, '205.210.31.217', 'Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiRE42dE43NldTN1JIRWZncGNxOHlqOHNGSGJjUEVPRUVKWUNocUtlcSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9sb2Fucy5zaGFyZXQuYWZyaWNhIjtzOjU6InJvdXRlIjtzOjQ6ImhvbWUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19', 1792054237);

-- --------------------------------------------------------

--
-- Table structure for table `skip_tracing`
--

CREATE TABLE `skip_tracing` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `case_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tracing_type` enum('phone','address','employment','social_media','database_search','field_investigation','other') NOT NULL,
  `search_date` date NOT NULL,
  `search_provider` varchar(255) DEFAULT NULL,
  `search_result` text DEFAULT NULL,
  `verification_method` varchar(255) DEFAULT NULL,
  `verification_result` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `source_contact` varchar(255) DEFAULT NULL,
  `source_phone` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system`
--

CREATE TABLE `system` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `logo_dark` varchar(255) DEFAULT NULL,
  `logo_icon` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'UTC',
  `date_format` varchar(50) NOT NULL DEFAULT 'd-m-Y',
  `time_format` varchar(50) NOT NULL DEFAULT 'H:i:s',
  `currency` varchar(50) NOT NULL DEFAULT 'KES',
  `currency_symbol` varchar(50) NOT NULL DEFAULT 'KSh',
  `primary_color` varchar(50) DEFAULT NULL,
  `secondary_color` varchar(50) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `pagination_limit` int(11) NOT NULL DEFAULT 15,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `website_pages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `social_media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system`
--

INSERT INTO `system` (`id`, `name`, `logo`, `logo_dark`, `logo_icon`, `favicon`, `slogan`, `timezone`, `date_format`, `time_format`, `currency`, `currency_symbol`, `primary_color`, `secondary_color`, `contact_email`, `contact_phone`, `address`, `location`, `meta_description`, `meta_keywords`, `maintenance_mode`, `pagination_limit`, `custom_css`, `custom_js`, `settings`, `website_pages`, `social_media`, `created_at`, `updated_at`) VALUES
(1, 'Amazonblue Capital', 'logo.svg', 'logo.svg', 'logo.svg', NULL, 'Your trusted application', 'UTC', 'd-m-Y', 'H:i:s', 'KES', 'KSh', '#3A57E8', '#08B1BA', NULL, NULL, NULL, '{\"country\":\"\",\"city\":\"\",\"name\":\"\",\"latitude\":\"\",\"longitude\":\"\"}', NULL, NULL, 0, 15, NULL, NULL, '{\"notifications\":{\"email_notifications\":true,\"push_notifications\":true,\"sms_notifications\":false,\"notification_sound\":true},\"security\":{\"two_factor_auth\":false,\"login_attempts\":5,\"session_timeout\":30,\"password_expiry\":90},\"integrations\":{\"google_analytics\":\"\",\"google_maps_key\":\"\",\"mail_driver\":\"smtp\",\"mail_host\":\"\",\"mail_port\":\"587\",\"mail_username\":\"\",\"mail_password\":\"\"},\"backup\":{\"auto_backup\":true,\"backup_frequency\":\"daily\",\"backup_retention\":30,\"backup_to_cloud\":false},\"company\":{\"website\":\"\",\"phone\":\"\",\"email\":\"\",\"address\":\"\",\"about\":\"\",\"mission\":\"\",\"vision\":\"\",\"values\":\"\"},\"currency_position\":\"before\"}', '{\"home\":{\"enabled\":true,\"title\":\"Home\",\"slug\":\"\",\"show_in_menu\":true,\"order\":1},\"about\":{\"enabled\":true,\"title\":\"About Us\",\"slug\":\"about\",\"show_in_menu\":true,\"order\":2},\"services\":{\"enabled\":true,\"title\":\"Services\",\"slug\":\"services\",\"show_in_menu\":true,\"order\":3},\"contact\":{\"enabled\":true,\"title\":\"Contact Us\",\"slug\":\"contact\",\"show_in_menu\":true,\"order\":4}}', '{\"facebook\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-facebook-fill\",\"name\":\"Facebook\",\"color\":\"#1877F2\",\"order\":1},\"twitter\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-twitter-fill\",\"name\":\"Twitter\",\"color\":\"#1DA1F2\",\"order\":2},\"instagram\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-instagram-fill\",\"name\":\"Instagram\",\"color\":\"#E4405F\",\"order\":3},\"linkedin\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-linkedin-fill\",\"name\":\"LinkedIn\",\"color\":\"#0A66C2\",\"order\":4}}', '2026-01-18 09:44:28', '2026-06-26 17:39:10');

-- --------------------------------------------------------

--
-- Table structure for table `tellers`
--

CREATE TABLE `tellers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `branch` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tellers`
--

INSERT INTO `tellers` (`id`, `user_id`, `branch`, `created_at`, `updated_at`) VALUES
(1, 49, 'main', '2025-09-02 07:51:46', '2025-09-02 07:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payable_type` varchar(255) NOT NULL,
  `payable_id` bigint(20) UNSIGNED NOT NULL,
  `wallet_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('deposit','withdraw') NOT NULL,
  `amount` decimal(64,0) NOT NULL,
  `confirmed` tinyint(1) NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `payable_type`, `payable_id`, `wallet_id`, `type`, `amount`, `confirmed`, `meta`, `uuid`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'App\\Models\\Partner', 5, 3, 'deposit', 960000, 1, '{\"reference\":\"BACKFILL-FEE-L1\",\"description\":\"Backfilled broker interest share for loan #1\"}', '01a11683-4b35-71f4-9655-f4fcc2a2bbd0', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(2, 'App\\Models\\Partner', 5, 3, 'deposit', 680000, 1, '{\"reference\":\"BACKFILL-PEN-L1\",\"description\":\"Backfilled penalty share (50% of last-cycle penalty collected) for loan #1\"}', '01a11683-4b4b-71c0-b0ea-490ba8a37554', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(3, 'App\\Models\\Partner', 5, 3, 'deposit', 240000, 1, '{\"reference\":\"BACKFILL-FEE-L2\",\"description\":\"Backfilled broker interest share for loan #2\"}', '01a11683-4b5b-737e-8505-b1fccbb20252', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(4, 'App\\Models\\Partner', 5, 3, 'deposit', 400000, 1, '{\"reference\":\"BACKFILL-FEE-L3\",\"description\":\"Backfilled broker interest share for loan #3\"}', '01a11683-4b62-7116-9cc2-6a92ffdc550a', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(5, 'App\\Models\\Partner', 5, 3, 'deposit', 280000, 1, '{\"reference\":\"BACKFILL-FEE-L4\",\"description\":\"Backfilled broker interest share for loan #4\"}', '01a11683-4b6a-7065-a792-f706f7829251', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(6, 'App\\Models\\Partner', 5, 3, 'deposit', 216000, 1, '{\"reference\":\"BACKFILL-FEE-L12\",\"description\":\"Backfilled broker interest share for loan #12\"}', '01a11683-4b72-7227-97dd-103a5d96d03a', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(7, 'App\\Models\\Partner', 5, 3, 'deposit', 240000, 1, '{\"reference\":\"BACKFILL-FEE-L13\",\"description\":\"Backfilled broker interest share for loan #13\"}', '01a11683-4b7a-718c-8376-74c56b579b6b', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(8, 'App\\Models\\Partner', 5, 3, 'deposit', 400000, 1, '{\"reference\":\"BACKFILL-FEE-L16\",\"description\":\"Backfilled broker interest share for loan #16\"}', '01a11683-4b80-70cb-9d5c-c601b5250483', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(9, 'App\\Models\\Partner', 5, 3, 'deposit', 160000, 1, '{\"reference\":\"BACKFILL-FEE-L17\",\"description\":\"Backfilled broker interest share for loan #17\"}', '01a11683-4b86-72f0-932f-0e2210484219', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(10, 'App\\Models\\Partner', 5, 3, 'deposit', 120000, 1, '{\"reference\":\"BACKFILL-PEN-L17\",\"description\":\"Backfilled penalty share (50% of last-cycle penalty collected) for loan #17\"}', '01a11683-4b89-70c7-bba0-b96938818cd5', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(11, 'App\\Models\\Partner', 5, 3, 'deposit', 280000, 1, '{\"reference\":\"BACKFILL-FEE-L18\",\"description\":\"Backfilled broker interest share for loan #18\"}', '01a11683-4b8f-7010-be24-46e059f95b17', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(12, 'App\\Models\\Partner', 5, 3, 'deposit', 336000, 1, '{\"reference\":\"BACKFILL-FEE-L21\",\"description\":\"Backfilled broker interest share for loan #21\"}', '01a11683-4b94-7045-bca0-0e96be21fa74', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(13, 'App\\Models\\Partner', 5, 3, 'deposit', 756000, 1, '{\"reference\":\"BACKFILL-PEN-L21\",\"description\":\"Backfilled penalty share (50% of last-cycle penalty collected) for loan #21\"}', '01a11683-4b98-7007-a457-a28063648591', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(14, 'App\\Models\\Partner', 5, 3, 'deposit', 352000, 1, '{\"reference\":\"BACKFILL-FEE-L36\",\"description\":\"Backfilled broker interest share for loan #36\"}', '01a11683-4b9d-713a-9dc8-21cb07abb00d', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(15, 'App\\Models\\Partner', 5, 3, 'deposit', 800000, 1, '{\"reference\":\"BACKFILL-FEE-L50\",\"description\":\"Backfilled broker interest share for loan #50\"}', '01a11683-4ba2-70ef-965f-15021bd337ad', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(16, 'App\\Models\\Partner', 5, 3, 'deposit', 80000, 1, '{\"reference\":\"BACKFILL-FEE-L54\",\"description\":\"Backfilled broker interest share for loan #54\"}', '01a11683-4ba7-7158-a9c3-2613fece438d', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(17, 'App\\Models\\Partner', 5, 3, 'deposit', 240000, 1, '{\"reference\":\"BACKFILL-FEE-L56\",\"description\":\"Backfilled broker interest share for loan #56\"}', '01a11683-4bad-7100-ac14-750b60f49fd6', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(18, 'App\\Models\\Partner', 5, 3, 'deposit', 360000, 1, '{\"reference\":\"BACKFILL-FEE-L57\",\"description\":\"Backfilled broker interest share for loan #57\"}', '01a11683-4bb2-72f9-9998-2a7115528a60', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(19, 'App\\Models\\Partner', 5, 3, 'deposit', 400000, 1, '{\"reference\":\"BACKFILL-FEE-L60\",\"description\":\"Backfilled broker interest share for loan #60\"}', '01a11683-4bb8-70e1-8e99-c21c23b30450', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(20, 'App\\Models\\Partner', 5, 3, 'deposit', 20000, 1, '{\"reference\":\"BACKFILL-FEE-L66\",\"description\":\"Backfilled broker interest share for loan #66\"}', '01a11683-4bbd-72a2-b385-2818a812437c', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(21, 'App\\Models\\Partner', 5, 3, 'deposit', 80000, 1, '{\"reference\":\"BACKFILL-FEE-L68\",\"description\":\"Backfilled broker interest share for loan #68\"}', '01a11683-4bc2-732f-9256-008af87a8c67', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(22, 'App\\Models\\Partner', 5, 3, 'deposit', 400000, 1, '{\"reference\":\"BACKFILL-FEE-L74\",\"description\":\"Backfilled broker interest share for loan #74\"}', '01a11683-4bc8-7132-a0f0-6ba2bfb71284', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(23, 'App\\Models\\Partner', 5, 3, 'deposit', 65600, 1, '{\"reference\":\"BACKFILL-FEE-L83\",\"description\":\"Backfilled broker interest share for loan #83\"}', '01a11683-4bcd-706e-a2ae-774faa51a7e2', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(24, 'App\\Models\\Partner', 5, 3, 'deposit', 800000, 1, '{\"reference\":\"BACKFILL-FEE-L85\",\"description\":\"Backfilled broker interest share for loan #85\"}', '01a11683-4bd3-71a4-85f0-b710591b6714', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(25, 'App\\Models\\Partner', 5, 3, 'deposit', 3000000, 1, '{\"reference\":\"BACKFILL-PEN-L85\",\"description\":\"Backfilled penalty share (50% of last-cycle penalty collected) for loan #85\"}', '01a11683-4bd6-73ec-ab78-cbfc48e7e476', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(26, 'App\\Models\\Partner', 5, 3, 'deposit', 66240, 1, '{\"reference\":\"BACKFILL-FEE-L89\",\"description\":\"Backfilled broker interest share for loan #89\"}', '01a11683-4bdc-707b-bbe7-d396c50831a3', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(27, 'App\\Models\\Partner', 5, 3, 'deposit', 37856, 1, '{\"reference\":\"BACKFILL-FEE-L106\",\"description\":\"Backfilled broker interest share for loan #106\"}', '01a11683-4be2-707a-bef3-76a8217bf072', '2026-10-07 11:17:45', '2026-10-07 11:17:45', NULL),
(29, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L5\",\"description\":\"Backfilled house interest (100%) for loan #5\"}', '01a116ce-a7b3-7012-8b5d-b6f97891ed7d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(30, 'App\\Models\\Partner', 6, 6, 'deposit', 26000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L6\",\"description\":\"Backfilled house interest (100%) for loan #6\"}', '01a116ce-a7bd-7205-b3e3-a5dbf0c69b9c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(31, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L7\",\"description\":\"Backfilled house interest (100%) for loan #7\"}', '01a116ce-a7c4-70f7-b589-74e33938cf9f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(32, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L8\",\"description\":\"Backfilled house interest (100%) for loan #8\"}', '01a116ce-a7c8-736d-a6a8-4089e74e28e0', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(33, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L9\",\"description\":\"Backfilled house interest (100%) for loan #9\"}', '01a116ce-a7cd-7106-9ddf-213fbcfbad00', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(34, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L10\",\"description\":\"Backfilled house interest (100%) for loan #10\"}', '01a116ce-a7d0-71c9-9194-ed175cb36828', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(35, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L11\",\"description\":\"Backfilled house interest (100%) for loan #11\"}', '01a116ce-a7d3-73ef-a128-e09a8bf90b6d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(36, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L14\",\"description\":\"Backfilled house interest (100%) for loan #14\"}', '01a116ce-a7d6-7020-ac26-db2ec535305f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(37, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L15\",\"description\":\"Backfilled house interest (100%) for loan #15\"}', '01a116ce-a7d9-71f3-8e6c-2872765e620c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(38, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L19\",\"description\":\"Backfilled house interest (100%) for loan #19\"}', '01a116ce-a7dc-73ee-9fa3-ae321c155d8d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(39, 'App\\Models\\Partner', 6, 6, 'deposit', 66000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L20\",\"description\":\"Backfilled house interest (100%) for loan #20\"}', '01a116ce-a7de-72ad-8af2-c1306fa7c452', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(40, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L24\",\"description\":\"Backfilled house interest (100%) for loan #24\"}', '01a116ce-a7e1-71cc-9f62-84c90944772c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(41, 'App\\Models\\Partner', 6, 6, 'deposit', 22000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L25\",\"description\":\"Backfilled house interest (100%) for loan #25\"}', '01a116ce-a7e4-738a-943b-bc866442f00b', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(42, 'App\\Models\\Partner', 6, 6, 'deposit', 1200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L26\",\"description\":\"Backfilled house interest (100%) for loan #26\"}', '01a116ce-a7e6-7193-a9e5-f690693836bb', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(43, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L27\",\"description\":\"Backfilled house interest (100%) for loan #27\"}', '01a116ce-a7e9-7271-af4a-6d68639f35f0', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(44, 'App\\Models\\Partner', 6, 6, 'deposit', 22000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L29\",\"description\":\"Backfilled house interest (100%) for loan #29\"}', '01a116ce-a7ec-70ba-b4f7-c57fa111a311', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(45, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L30\",\"description\":\"Backfilled house interest (100%) for loan #30\"}', '01a116ce-a7ef-729a-acb0-3c341d5654db', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(46, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L31\",\"description\":\"Backfilled house interest (100%) for loan #31\"}', '01a116ce-a7f1-7374-adb4-55d60d17efa7', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(47, 'App\\Models\\Partner', 6, 6, 'deposit', 58000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L32\",\"description\":\"Backfilled house interest (100%) for loan #32\"}', '01a116ce-a7f4-70db-a400-dca8efeef9d1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(48, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L33\",\"description\":\"Backfilled house interest (100%) for loan #33\"}', '01a116ce-a7f7-71c8-96ce-0b8924b6717c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(49, 'App\\Models\\Partner', 6, 6, 'deposit', 18000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L34\",\"description\":\"Backfilled house interest (100%) for loan #34\"}', '01a116ce-a7fa-714c-b26f-56b80ceb5285', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(50, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L35\",\"description\":\"Backfilled house interest (100%) for loan #35\"}', '01a116ce-a7fd-729a-8f05-ee4f6cbe6587', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(51, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L37\",\"description\":\"Backfilled house interest (100%) for loan #37\"}', '01a116ce-a7ff-71c4-826c-5f96381d98a7', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(52, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L38\",\"description\":\"Backfilled house interest (100%) for loan #38\"}', '01a116ce-a802-71ec-b2a6-9b2883f14a48', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(53, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L39\",\"description\":\"Backfilled house interest (100%) for loan #39\"}', '01a116ce-a805-7308-a3fe-4e1f9daead55', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(54, 'App\\Models\\Partner', 6, 6, 'deposit', 31200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L40\",\"description\":\"Backfilled house interest (100%) for loan #40\"}', '01a116ce-a808-72a0-b11f-52ee9cc70e04', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(55, 'App\\Models\\Partner', 6, 6, 'deposit', 16000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L41\",\"description\":\"Backfilled house interest (100%) for loan #41\"}', '01a116ce-a80b-722c-a568-f7fdacf446aa', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(56, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L45\",\"description\":\"Backfilled house interest (100%) for loan #45\"}', '01a116ce-a80d-72d8-af11-5c2cc75910c3', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(57, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L46\",\"description\":\"Backfilled house interest (100%) for loan #46\"}', '01a116ce-a810-7191-bff9-b4f950723bac', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(58, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L48\",\"description\":\"Backfilled house interest (100%) for loan #48\"}', '01a116ce-a813-70ea-92d9-dd5a1c4dc8d7', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(59, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L49\",\"description\":\"Backfilled house interest (100%) for loan #49\"}', '01a116ce-a816-7301-9b97-67a8b6b8cae6', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(60, 'App\\Models\\Partner', 6, 6, 'deposit', 31200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L51\",\"description\":\"Backfilled house interest (100%) for loan #51\"}', '01a116ce-a81a-7370-af6a-333d87000a49', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(61, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L55\",\"description\":\"Backfilled house interest (100%) for loan #55\"}', '01a116ce-a81d-7341-888b-9b347c313db4', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(62, 'App\\Models\\Partner', 6, 6, 'deposit', 156000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L59\",\"description\":\"Backfilled house interest (100%) for loan #59\"}', '01a116ce-a820-7377-921e-716f39bff061', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(63, 'App\\Models\\Partner', 6, 6, 'deposit', 144000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L61\",\"description\":\"Backfilled house interest (100%) for loan #61\"}', '01a116ce-a824-72ee-aaa2-ff079bb2d5ff', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(64, 'App\\Models\\Partner', 6, 6, 'deposit', 37440000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L64\",\"description\":\"Backfilled house interest (100%) for loan #64\"}', '01a116ce-a827-7048-bc3f-44e2797e0668', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(65, 'App\\Models\\Partner', 6, 6, 'deposit', 26000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L65\",\"description\":\"Backfilled house interest (100%) for loan #65\"}', '01a116ce-a82a-7028-a2be-63efd9a27483', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(66, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L69\",\"description\":\"Backfilled house interest (100%) for loan #69\"}', '01a116ce-a82f-73f5-897c-bfb41a4d90f2', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(67, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L70\",\"description\":\"Backfilled house interest (100%) for loan #70\"}', '01a116ce-a832-7205-a652-5e797e82c6e8', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(68, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L71\",\"description\":\"Backfilled house interest (100%) for loan #71\"}', '01a116ce-a835-7013-a9b3-8b31e6240a63', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(69, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L73\",\"description\":\"Backfilled house interest (100%) for loan #73\"}', '01a116ce-a838-70e2-a5d2-d3bf7df00778', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(70, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L75\",\"description\":\"Backfilled house interest (100%) for loan #75\"}', '01a116ce-a83b-703e-ba75-da4e581b13aa', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(71, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L76\",\"description\":\"Backfilled house interest (100%) for loan #76\"}', '01a116ce-a83e-739e-a3a1-9d549d6446c1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(72, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L77\",\"description\":\"Backfilled house interest (100%) for loan #77\"}', '01a116ce-a841-72cc-bc03-761c159ed8a3', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(73, 'App\\Models\\Partner', 6, 6, 'deposit', 950000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L78\",\"description\":\"Backfilled house interest (100%) for loan #78\"}', '01a116ce-a844-73e6-b93a-5b29a59cee53', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(74, 'App\\Models\\Partner', 6, 6, 'deposit', 70000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L79\",\"description\":\"Backfilled house interest (100%) for loan #79\"}', '01a116ce-a846-7274-badf-e5ce184743e3', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(75, 'App\\Models\\Partner', 6, 6, 'deposit', 90000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L80\",\"description\":\"Backfilled house interest (100%) for loan #80\"}', '01a116ce-a849-71d9-a7a5-84a095943e38', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(76, 'App\\Models\\Partner', 6, 6, 'deposit', 90000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L82\",\"description\":\"Backfilled house interest (100%) for loan #82\"}', '01a116ce-a84c-7006-a4ce-1eab91b31049', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(77, 'App\\Models\\Partner', 6, 6, 'deposit', 70000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L84\",\"description\":\"Backfilled house interest (100%) for loan #84\"}', '01a116ce-a84f-71c0-92d2-f8dc785245ec', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(78, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L86\",\"description\":\"Backfilled house interest (100%) for loan #86\"}', '01a116ce-a852-7377-98e2-b3ff532a91ea', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(79, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L87\",\"description\":\"Backfilled house interest (100%) for loan #87\"}', '01a116ce-a854-708e-bc9b-d0cecdb70d9f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(80, 'App\\Models\\Partner', 6, 6, 'deposit', 56000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L88\",\"description\":\"Backfilled house interest (100%) for loan #88\"}', '01a116ce-a857-73e2-83a0-07ae0b384a67', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(81, 'App\\Models\\Partner', 6, 6, 'deposit', 79200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L90\",\"description\":\"Backfilled house interest (100%) for loan #90\"}', '01a116ce-a85a-7352-9bb3-6207bc84a10e', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(82, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L91\",\"description\":\"Backfilled house interest (100%) for loan #91\"}', '01a116ce-a85d-7026-8abb-252b7f546112', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(83, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L92\",\"description\":\"Backfilled house interest (100%) for loan #92\"}', '01a116ce-a860-72bc-89f0-094ee6b2af94', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(84, 'App\\Models\\Partner', 6, 6, 'deposit', 34000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L93\",\"description\":\"Backfilled house interest (100%) for loan #93\"}', '01a116ce-a863-721c-84f6-bc24822fd245', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(85, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L94\",\"description\":\"Backfilled house interest (100%) for loan #94\"}', '01a116ce-a866-735f-bd45-b839669603e2', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(86, 'App\\Models\\Partner', 6, 6, 'deposit', 4528000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L95\",\"description\":\"Backfilled house interest (100%) for loan #95\"}', '01a116ce-a869-70b2-b436-f9139be40383', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(87, 'App\\Models\\Partner', 6, 6, 'deposit', 162240000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L96\",\"description\":\"Backfilled house interest (100%) for loan #96\"}', '01a116ce-a86b-720b-a7c5-b03f7df1da0f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(88, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L97\",\"description\":\"Backfilled house interest (100%) for loan #97\"}', '01a116ce-a86e-72eb-a7b8-242ec5a96b1f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(89, 'App\\Models\\Partner', 6, 6, 'deposit', 56000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L98\",\"description\":\"Backfilled house interest (100%) for loan #98\"}', '01a116ce-a871-70ac-91cf-1824e36aa851', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(90, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L100\",\"description\":\"Backfilled house interest (100%) for loan #100\"}', '01a116ce-a876-70d9-8a75-a3935b3ba38b', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(91, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L101\",\"description\":\"Backfilled house interest (100%) for loan #101\"}', '01a116ce-a879-718b-9792-3d9854e83f5d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(92, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L102\",\"description\":\"Backfilled house interest (100%) for loan #102\"}', '01a116ce-a87c-7037-bfbf-8f89ffb181e1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(93, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L103\",\"description\":\"Backfilled house interest (100%) for loan #103\"}', '01a116ce-a87f-733c-a2b5-a4509a167a83', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(94, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L104\",\"description\":\"Backfilled house interest (100%) for loan #104\"}', '01a116ce-a883-702a-9180-c5deedf5550e', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(95, 'App\\Models\\Partner', 6, 6, 'deposit', 240000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L105\",\"description\":\"Backfilled house interest (100%) for loan #105\"}', '01a116ce-a886-71da-b9a8-1c82356ba0e2', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(96, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L107\",\"description\":\"Backfilled house interest (100%) for loan #107\"}', '01a116ce-a889-7146-89dc-486958b67389', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(97, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L108\",\"description\":\"Backfilled house interest (100%) for loan #108\"}', '01a116ce-a88d-7020-b0d1-569cdbea8e86', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(98, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L109\",\"description\":\"Backfilled house interest (100%) for loan #109\"}', '01a116ce-a890-7357-a6fd-eb991d430efd', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(99, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L110\",\"description\":\"Backfilled house interest (100%) for loan #110\"}', '01a116ce-a893-7254-8a81-06d7e6779a13', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(100, 'App\\Models\\Partner', 6, 6, 'deposit', 16000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L111\",\"description\":\"Backfilled house interest (100%) for loan #111\"}', '01a116ce-a896-7243-acbf-07b6237a18ce', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(101, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L112\",\"description\":\"Backfilled house interest (100%) for loan #112\"}', '01a116ce-a89a-71dd-9187-e196cb149bfa', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(102, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L113\",\"description\":\"Backfilled house interest (100%) for loan #113\"}', '01a116ce-a89d-72a9-b944-c06623eb255a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(103, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L114\",\"description\":\"Backfilled house interest (100%) for loan #114\"}', '01a116ce-a8a0-72c8-9b6a-3288c9239106', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(104, 'App\\Models\\Partner', 6, 6, 'deposit', 292032000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L115\",\"description\":\"Backfilled house interest (100%) for loan #115\"}', '01a116ce-a8a2-727f-a89e-235056adb6e3', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(105, 'App\\Models\\Partner', 6, 6, 'deposit', 117000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L116\",\"description\":\"Backfilled house interest (100%) for loan #116\"}', '01a116ce-a8a5-70c2-8235-784bdaac8b98', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(106, 'App\\Models\\Partner', 6, 6, 'deposit', 240000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L117\",\"description\":\"Backfilled house interest (100%) for loan #117\"}', '01a116ce-a8a8-732c-a7e5-3c97251f1c9f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(107, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L118\",\"description\":\"Backfilled house interest (100%) for loan #118\"}', '01a116ce-a8ab-7367-a543-80b3020fb814', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(108, 'App\\Models\\Partner', 6, 6, 'deposit', 2000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L119\",\"description\":\"Backfilled house interest (100%) for loan #119\"}', '01a116ce-a8ae-71ea-b823-16106816ffb4', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(109, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L120\",\"description\":\"Backfilled house interest (100%) for loan #120\"}', '01a116ce-a8b0-71ef-af05-94912512921e', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(110, 'App\\Models\\Partner', 6, 6, 'deposit', 140000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L121\",\"description\":\"Backfilled house interest (100%) for loan #121\"}', '01a116ce-a8b3-70c4-a4dc-5467ce9c04fd', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(111, 'App\\Models\\Partner', 6, 6, 'deposit', 28800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L122\",\"description\":\"Backfilled house interest (100%) for loan #122\"}', '01a116ce-a8b6-7046-84b2-b8c0d2f9fea1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(112, 'App\\Models\\Partner', 6, 6, 'deposit', 85800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L123\",\"description\":\"Backfilled house interest (100%) for loan #123\"}', '01a116ce-a8b9-7147-9d05-79d93e521371', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(113, 'App\\Models\\Partner', 6, 6, 'deposit', 113256000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L124\",\"description\":\"Backfilled house interest (100%) for loan #124\"}', '01a116ce-a8bc-728e-a4b1-7f466913eaa6', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(114, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L125\",\"description\":\"Backfilled house interest (100%) for loan #125\"}', '01a116ce-a8be-72f0-a346-60258c7e4875', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(115, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L126\",\"description\":\"Backfilled house interest (100%) for loan #126\"}', '01a116ce-a8c1-7369-aca3-d28ed85f3699', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(116, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L127\",\"description\":\"Backfilled house interest (100%) for loan #127\"}', '01a116ce-a8c4-71cd-abb8-a2f49126a712', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(117, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L128\",\"description\":\"Backfilled house interest (100%) for loan #128\"}', '01a116ce-a8c7-704a-83e5-3ed236a5662c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(118, 'App\\Models\\Partner', 6, 6, 'deposit', 14400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L129\",\"description\":\"Backfilled house interest (100%) for loan #129\"}', '01a116ce-a8ca-7033-b93f-9be8776562b6', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(119, 'App\\Models\\Partner', 6, 6, 'deposit', 34560000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L130\",\"description\":\"Backfilled house interest (100%) for loan #130\"}', '01a116ce-a8cd-7104-82f4-f9e12c7558f2', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(120, 'App\\Models\\Partner', 6, 6, 'deposit', 8000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L131\",\"description\":\"Backfilled house interest (100%) for loan #131\"}', '01a116ce-a8cf-7039-ba3b-383782b426eb', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(121, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L134\",\"description\":\"Backfilled house interest (100%) for loan #134\"}', '01a116ce-a8d3-7110-a161-f5f5cf2585ad', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(122, 'App\\Models\\Partner', 6, 6, 'deposit', 4800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L135\",\"description\":\"Backfilled house interest (100%) for loan #135\"}', '01a116ce-a8d6-7397-9089-59f065d86ea6', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(123, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L136\",\"description\":\"Backfilled house interest (100%) for loan #136\"}', '01a116ce-a8d9-70f4-9eee-83fea1bcd90e', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(124, 'App\\Models\\Partner', 6, 6, 'deposit', 75000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L137\",\"description\":\"Backfilled house interest (100%) for loan #137\"}', '01a116ce-a8dc-72c3-896a-7f684c7b1789', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(125, 'App\\Models\\Partner', 6, 6, 'deposit', 90000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L138\",\"description\":\"Backfilled house interest (100%) for loan #138\"}', '01a116ce-a8df-704b-a039-d07b0acc79a7', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(126, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L139\",\"description\":\"Backfilled house interest (100%) for loan #139\"}', '01a116ce-a8e3-73a3-bab9-823df279b900', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(127, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L140\",\"description\":\"Backfilled house interest (100%) for loan #140\"}', '01a116ce-a8e7-73d1-9b3c-686a3d5a0e65', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(128, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L141\",\"description\":\"Backfilled house interest (100%) for loan #141\"}', '01a116ce-a8ea-717b-8a87-f09d6bfa0b9f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(129, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L142\",\"description\":\"Backfilled house interest (100%) for loan #142\"}', '01a116ce-a8ed-70b2-a41c-b099791ad877', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(130, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L143\",\"description\":\"Backfilled house interest (100%) for loan #143\"}', '01a116ce-a8f1-724f-86ac-1a1b1dd782e8', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(131, 'App\\Models\\Partner', 6, 6, 'deposit', 600000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L144\",\"description\":\"Backfilled house interest (100%) for loan #144\"}', '01a116ce-a8f5-70c1-9454-f25e07259ca8', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(132, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L145\",\"description\":\"Backfilled house interest (100%) for loan #145\"}', '01a116ce-a8f8-72eb-b56f-0c10faf04c46', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(133, 'App\\Models\\Partner', 6, 6, 'deposit', 32800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L146\",\"description\":\"Backfilled house interest (100%) for loan #146\"}', '01a116ce-a8fb-71e6-9f94-70651d04ea4d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(134, 'App\\Models\\Partner', 6, 6, 'deposit', 135907200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L147\",\"description\":\"Backfilled house interest (100%) for loan #147\"}', '01a116ce-a8fe-7162-92ae-3b67eb62384b', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(135, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L148\",\"description\":\"Backfilled house interest (100%) for loan #148\"}', '01a116ce-a901-71d8-bc0f-ed5910b1b330', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(136, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L149\",\"description\":\"Backfilled house interest (100%) for loan #149\"}', '01a116ce-a905-70cd-9b93-a89b7d59f5d1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(137, 'App\\Models\\Partner', 6, 6, 'deposit', 133090000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L151\",\"description\":\"Backfilled house interest (100%) for loan #151\"}', '01a116ce-a907-7155-b327-4f9ffa9fa53c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(138, 'App\\Models\\Partner', 6, 6, 'deposit', 152100000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L152\",\"description\":\"Backfilled house interest (100%) for loan #152\"}', '01a116ce-a90a-7277-8a79-2a5fee48ec9b', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(139, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L156\",\"description\":\"Backfilled house interest (100%) for loan #156\"}', '01a116ce-a90d-70cb-9b8e-e00645410d3d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(140, 'App\\Models\\Partner', 6, 6, 'deposit', 3200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L158\",\"description\":\"Backfilled house interest (100%) for loan #158\"}', '01a116ce-a910-72ea-9681-9a695cced1d0', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(141, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L159\",\"description\":\"Backfilled house interest (100%) for loan #159\"}', '01a116ce-a913-700a-900f-8d9c42d5a0eb', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(142, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L161\",\"description\":\"Backfilled house interest (100%) for loan #161\"}', '01a116ce-a916-7141-92f5-0ef28ca3a647', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(143, 'App\\Models\\Partner', 6, 6, 'deposit', 3840000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L162\",\"description\":\"Backfilled house interest (100%) for loan #162\"}', '01a116ce-a919-71e9-ad24-62e00cdc5ff2', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(144, 'App\\Models\\Partner', 6, 6, 'deposit', 39360000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L163\",\"description\":\"Backfilled house interest (100%) for loan #163\"}', '01a116ce-a91c-72fc-99ac-0c25434bdc95', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(145, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L164\",\"description\":\"Backfilled house interest (100%) for loan #164\"}', '01a116ce-a91f-734f-bfdc-e0864cfb7c0d', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(146, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L165\",\"description\":\"Backfilled house interest (100%) for loan #165\"}', '01a116ce-a922-70e7-bc9d-f5c5e41752c8', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(147, 'App\\Models\\Partner', 6, 6, 'deposit', 14400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L166\",\"description\":\"Backfilled house interest (100%) for loan #166\"}', '01a116ce-a925-71bc-b373-1d4d8db8b42c', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(148, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L167\",\"description\":\"Backfilled house interest (100%) for loan #167\"}', '01a116ce-a928-701c-9bad-5b45eadca1a6', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(149, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L168\",\"description\":\"Backfilled house interest (100%) for loan #168\"}', '01a116ce-a92a-73d6-90d8-8a784d44ea25', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(150, 'App\\Models\\Partner', 6, 6, 'deposit', 84000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L169\",\"description\":\"Backfilled house interest (100%) for loan #169\"}', '01a116ce-a92d-73c6-971f-04f192e40c43', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(151, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L170\",\"description\":\"Backfilled house interest (100%) for loan #170\"}', '01a116ce-a930-7032-86e0-2f3c13b70763', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(152, 'App\\Models\\Partner', 6, 6, 'deposit', 107280000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L171\",\"description\":\"Backfilled house interest (100%) for loan #171\"}', '01a116ce-a933-727e-834e-d6352b11a948', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(153, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L172\",\"description\":\"Backfilled house interest (100%) for loan #172\"}', '01a116ce-a936-7342-84a7-76ad64354055', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(154, 'App\\Models\\Partner', 6, 6, 'deposit', 14400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L173\",\"description\":\"Backfilled house interest (100%) for loan #173\"}', '01a116ce-a939-73c9-a75a-2fe1fddfaeae', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(155, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L174\",\"description\":\"Backfilled house interest (100%) for loan #174\"}', '01a116ce-a93c-7340-a0fb-da6311221b08', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(156, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L175\",\"description\":\"Backfilled house interest (100%) for loan #175\"}', '01a116ce-a93f-71c2-9715-8879e34f856a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(157, 'App\\Models\\Partner', 6, 6, 'deposit', 47232000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L176\",\"description\":\"Backfilled house interest (100%) for loan #176\"}', '01a116ce-a942-72c5-a41b-804ef9cf7035', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(158, 'App\\Models\\Partner', 6, 6, 'deposit', 197730000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L178\",\"description\":\"Backfilled house interest (100%) for loan #178\"}', '01a116ce-a946-71d2-92af-c267c6e02aad', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(159, 'App\\Models\\Partner', 6, 6, 'deposit', 56680000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L179\",\"description\":\"Backfilled house interest (100%) for loan #179\"}', '01a116ce-a949-703a-a2de-b64acc55521a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(160, 'App\\Models\\Partner', 6, 6, 'deposit', 250000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L180\",\"description\":\"Backfilled house interest (100%) for loan #180\"}', '01a116ce-a94d-7236-9fd4-0ef942e64ecd', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(161, 'App\\Models\\Partner', 6, 6, 'deposit', 80000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L181\",\"description\":\"Backfilled house interest (100%) for loan #181\"}', '01a116ce-a950-739e-82d4-e79b46bdef2f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(162, 'App\\Models\\Partner', 6, 6, 'deposit', 43200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L182\",\"description\":\"Backfilled house interest (100%) for loan #182\"}', '01a116ce-a953-704b-a740-8a0079d9a0b0', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(163, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L183\",\"description\":\"Backfilled house interest (100%) for loan #183\"}', '01a116ce-a957-70dc-a2e2-5fdda91de60a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(164, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L184\",\"description\":\"Backfilled house interest (100%) for loan #184\"}', '01a116ce-a95a-72a1-a654-cf1467507b14', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(165, 'App\\Models\\Partner', 6, 6, 'deposit', 43200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L185\",\"description\":\"Backfilled house interest (100%) for loan #185\"}', '01a116ce-a95d-713e-938d-172d0588e4db', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(166, 'App\\Models\\Partner', 6, 6, 'deposit', 750000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L186\",\"description\":\"Backfilled house interest (100%) for loan #186\"}', '01a116ce-a960-702d-8cac-ea81f36a5552', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(167, 'App\\Models\\Partner', 6, 6, 'deposit', 80000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L187\",\"description\":\"Backfilled house interest (100%) for loan #187\"}', '01a116ce-a963-70a1-80b1-37aed256a5a4', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(168, 'App\\Models\\Partner', 6, 6, 'deposit', 2000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L188\",\"description\":\"Backfilled house interest (100%) for loan #188\"}', '01a116ce-a967-70c6-a559-a8aaefff17bc', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(169, 'App\\Models\\Partner', 6, 6, 'deposit', 272000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L189\",\"description\":\"Backfilled house interest (100%) for loan #189\"}', '01a116ce-a96a-715f-9b37-6a96b9b34404', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(170, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L190\",\"description\":\"Backfilled house interest (100%) for loan #190\"}', '01a116ce-a96c-70ae-865e-5d3d85c75248', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(171, 'App\\Models\\Partner', 6, 6, 'deposit', 68016000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L191\",\"description\":\"Backfilled house interest (100%) for loan #191\"}', '01a116ce-a96f-732d-ae7f-d44cb2478b65', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(172, 'App\\Models\\Partner', 6, 6, 'deposit', 80000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L192\",\"description\":\"Backfilled house interest (100%) for loan #192\"}', '01a116ce-a972-73f3-b8d3-2c7076e3edba', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(173, 'App\\Models\\Partner', 6, 6, 'deposit', 356000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L193\",\"description\":\"Backfilled house interest (100%) for loan #193\"}', '01a116ce-a975-7200-ac8a-69a1aff4c97a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(174, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L194\",\"description\":\"Backfilled house interest (100%) for loan #194\"}', '01a116ce-a977-707a-93f3-411a651af35b', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(175, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L195\",\"description\":\"Backfilled house interest (100%) for loan #195\"}', '01a116ce-a97a-73a0-aba2-89f4bd28eb7f', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(176, 'App\\Models\\Partner', 6, 6, 'deposit', 17000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L196\",\"description\":\"Backfilled house interest (100%) for loan #196\"}', '01a116ce-a97d-7265-982a-c45fda3a36c1', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(177, 'App\\Models\\Partner', 6, 6, 'deposit', 23000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L197\",\"description\":\"Backfilled house interest (100%) for loan #197\"}', '01a116ce-a97f-72cd-a507-adc2ea207b33', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(178, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L198\",\"description\":\"Backfilled house interest (100%) for loan #198\"}', '01a116ce-a982-71b4-b3d5-c606ca998a36', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(179, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L199\",\"description\":\"Backfilled house interest (100%) for loan #199\"}', '01a116ce-a985-7234-bb7b-b55c7fa4691a', '2026-10-07 12:40:04', '2026-10-07 12:40:04', NULL),
(180, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L201\",\"description\":\"Backfilled house interest (100%) for loan #201\"}', '01a116ce-a988-702a-b60c-5c3e00a9abbf', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(181, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L202\",\"description\":\"Backfilled house interest (100%) for loan #202\"}', '01a116ce-a98b-730a-bf1a-54ac660d040d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(182, 'App\\Models\\Partner', 6, 6, 'deposit', 108736000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L203\",\"description\":\"Backfilled house interest (100%) for loan #203\"}', '01a116ce-a98e-7074-a043-9bc9bf90fef7', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(183, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L204\",\"description\":\"Backfilled house interest (100%) for loan #204\"}', '01a116ce-a991-71a0-b7b8-a358a795fe78', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(184, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L205\",\"description\":\"Backfilled house interest (100%) for loan #205\"}', '01a116ce-a993-7208-a5b2-6a70f6d28550', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(185, 'App\\Models\\Partner', 6, 6, 'deposit', 2400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L206\",\"description\":\"Backfilled house interest (100%) for loan #206\"}', '01a116ce-a998-7233-af05-904acc101cfc', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(186, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L207\",\"description\":\"Backfilled house interest (100%) for loan #207\"}', '01a116ce-a99b-707c-bce0-dc275311d76b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(187, 'App\\Models\\Partner', 6, 6, 'deposit', 61619200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L209\",\"description\":\"Backfilled house interest (100%) for loan #209\"}', '01a116ce-a99e-7258-824d-63e5261b8d22', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(188, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L211\",\"description\":\"Backfilled house interest (100%) for loan #211\"}', '01a116ce-a9a1-7263-b4c9-02fa0406129e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(189, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L212\",\"description\":\"Backfilled house interest (100%) for loan #212\"}', '01a116ce-a9a3-701c-92f6-6c089e6c2e18', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(190, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L213\",\"description\":\"Backfilled house interest (100%) for loan #213\"}', '01a116ce-a9a6-70b6-aeae-1b6b3aa842b4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL);
INSERT INTO `transactions` (`id`, `payable_type`, `payable_id`, `wallet_id`, `type`, `amount`, `confirmed`, `meta`, `uuid`, `created_at`, `updated_at`, `deleted_at`) VALUES
(191, 'App\\Models\\Partner', 6, 6, 'deposit', 144000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L214\",\"description\":\"Backfilled house interest (100%) for loan #214\"}', '01a116ce-a9aa-70bf-9b57-ca8039d13922', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(192, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L215\",\"description\":\"Backfilled house interest (100%) for loan #215\"}', '01a116ce-a9ad-731d-bbf7-ac9a779a5cec', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(193, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L216\",\"description\":\"Backfilled house interest (100%) for loan #216\"}', '01a116ce-a9b0-7160-b78a-5c3dd5639f24', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(194, 'App\\Models\\Partner', 6, 6, 'deposit', 7400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L219\",\"description\":\"Backfilled house interest (100%) for loan #219\"}', '01a116ce-a9b3-71f0-9fc4-7bf772881fe4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(195, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L220\",\"description\":\"Backfilled house interest (100%) for loan #220\"}', '01a116ce-a9b5-725f-b37f-1bace6928dad', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(196, 'App\\Models\\Partner', 6, 6, 'deposit', 115300000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L221\",\"description\":\"Backfilled house interest (100%) for loan #221\"}', '01a116ce-a9b9-703c-aa0a-4e1d91fe3aa7', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(197, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L222\",\"description\":\"Backfilled house interest (100%) for loan #222\"}', '01a116ce-a9bc-719b-a937-0fd48f1bbcea', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(198, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L224\",\"description\":\"Backfilled house interest (100%) for loan #224\"}', '01a116ce-a9bf-729c-b0d9-dd58ebc06e7a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(199, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L225\",\"description\":\"Backfilled house interest (100%) for loan #225\"}', '01a116ce-a9c2-73b0-877b-16e5f75c2c1a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(200, 'App\\Models\\Partner', 6, 6, 'deposit', 56100000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L226\",\"description\":\"Backfilled house interest (100%) for loan #226\"}', '01a116ce-a9c5-73c7-aae5-50ff68f1fd4d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(201, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L227\",\"description\":\"Backfilled house interest (100%) for loan #227\"}', '01a116ce-a9c8-739a-8d76-ff579d11720e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(202, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L228\",\"description\":\"Backfilled house interest (100%) for loan #228\"}', '01a116ce-a9cb-73b5-9188-1cfe92624390', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(203, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L229\",\"description\":\"Backfilled house interest (100%) for loan #229\"}', '01a116ce-a9ce-73f3-9e32-a9d85b066663', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(204, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L230\",\"description\":\"Backfilled house interest (100%) for loan #230\"}', '01a116ce-a9d0-70c6-a7d8-ff9262245186', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(205, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L231\",\"description\":\"Backfilled house interest (100%) for loan #231\"}', '01a116ce-a9d3-70f1-8f45-703fbd72fa61', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(206, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L232\",\"description\":\"Backfilled house interest (100%) for loan #232\"}', '01a116ce-a9d6-701c-be69-e03fbfa5b586', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(207, 'App\\Models\\Partner', 6, 6, 'deposit', 70483200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L233\",\"description\":\"Backfilled house interest (100%) for loan #233\"}', '01a116ce-a9d9-70bc-b4d3-30863dc97e83', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(208, 'App\\Models\\Partner', 6, 6, 'deposit', 132000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L234\",\"description\":\"Backfilled house interest (100%) for loan #234\"}', '01a116ce-a9db-733c-8bd9-995c0bd103db', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(209, 'App\\Models\\Partner', 6, 6, 'deposit', 158400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L235\",\"description\":\"Backfilled house interest (100%) for loan #235\"}', '01a116ce-a9de-71c4-a9f2-5f125d356227', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(210, 'App\\Models\\Partner', 6, 6, 'deposit', 72000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L236\",\"description\":\"Backfilled house interest (100%) for loan #236\"}', '01a116ce-a9e1-7394-9f67-05aa23b54209', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(211, 'App\\Models\\Partner', 6, 6, 'deposit', 86400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L237\",\"description\":\"Backfilled house interest (100%) for loan #237\"}', '01a116ce-a9e4-72d8-9377-93378f2e65f3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(212, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L238\",\"description\":\"Backfilled house interest (100%) for loan #238\"}', '01a116ce-a9e7-7280-9b7f-0a7e734945ce', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(213, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L239\",\"description\":\"Backfilled house interest (100%) for loan #239\"}', '01a116ce-a9ea-73a7-9cf0-cbd565678601', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(214, 'App\\Models\\Partner', 6, 6, 'deposit', 28800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L240\",\"description\":\"Backfilled house interest (100%) for loan #240\"}', '01a116ce-a9ec-7049-9865-7aba169348aa', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(215, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L241\",\"description\":\"Backfilled house interest (100%) for loan #241\"}', '01a116ce-a9ef-71f2-96e0-44f78c5cae16', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(216, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L242\",\"description\":\"Backfilled house interest (100%) for loan #242\"}', '01a116ce-a9f3-73be-bdb1-e86c82f8dab9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(217, 'App\\Models\\Partner', 6, 6, 'deposit', 5400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L243\",\"description\":\"Backfilled house interest (100%) for loan #243\"}', '01a116ce-a9f6-73d5-bbbd-28d20ac36254', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(218, 'App\\Models\\Partner', 6, 6, 'deposit', 1200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L244\",\"description\":\"Backfilled house interest (100%) for loan #244\"}', '01a116ce-a9f9-7227-98fd-60aee7c2bc25', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(219, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L245\",\"description\":\"Backfilled house interest (100%) for loan #245\"}', '01a116ce-a9fc-7203-bff2-0bb6c9620c5c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(220, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L246\",\"description\":\"Backfilled house interest (100%) for loan #246\"}', '01a116ce-a9ff-7165-87c1-75db9c989e4f', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(221, 'App\\Models\\Partner', 6, 6, 'deposit', 190080000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L247\",\"description\":\"Backfilled house interest (100%) for loan #247\"}', '01a116ce-aa02-70d4-85aa-cca4b66bcf5e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(222, 'App\\Models\\Partner', 6, 6, 'deposit', 28800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L248\",\"description\":\"Backfilled house interest (100%) for loan #248\"}', '01a116ce-aa05-70ea-b5e8-064b1e319abd', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(223, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L249\",\"description\":\"Backfilled house interest (100%) for loan #249\"}', '01a116ce-aa09-7002-99f6-6375819644f3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(224, 'App\\Models\\Partner', 6, 6, 'deposit', 76098000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L250\",\"description\":\"Backfilled house interest (100%) for loan #250\"}', '01a116ce-aa0b-73bf-be84-f0958dbd8835', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(225, 'App\\Models\\Partner', 6, 6, 'deposit', 87680000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L251\",\"description\":\"Backfilled house interest (100%) for loan #251\"}', '01a116ce-aa0e-7080-b102-987cff8d989d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(226, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L252\",\"description\":\"Backfilled house interest (100%) for loan #252\"}', '01a116ce-aa11-73e1-8763-a1acdfeb4dce', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(227, 'App\\Models\\Partner', 6, 6, 'deposit', 228096000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L253\",\"description\":\"Backfilled house interest (100%) for loan #253\"}', '01a116ce-aa15-7268-8a19-2ceda5dc4cf3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(228, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L254\",\"description\":\"Backfilled house interest (100%) for loan #254\"}', '01a116ce-aa18-72f6-af6e-63ca9a2cfc2f', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(229, 'App\\Models\\Partner', 6, 6, 'deposit', 5360000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L255\",\"description\":\"Backfilled house interest (100%) for loan #255\"}', '01a116ce-aa1a-70c2-82ce-b393ade1daa2', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(230, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L256\",\"description\":\"Backfilled house interest (100%) for loan #256\"}', '01a116ce-aa1d-7295-a363-3cb835656ced', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(231, 'App\\Models\\Partner', 6, 6, 'deposit', 84580000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L257\",\"description\":\"Backfilled house interest (100%) for loan #257\"}', '01a116ce-aa21-71c6-a3ae-b806c0434de6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(232, 'App\\Models\\Partner', 6, 6, 'deposit', 390000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L258\",\"description\":\"Backfilled house interest (100%) for loan #258\"}', '01a116ce-aa24-73de-a99a-09da42154cda', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(233, 'App\\Models\\Partner', 6, 6, 'deposit', 75216000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L259\",\"description\":\"Backfilled house interest (100%) for loan #259\"}', '01a116ce-aa28-71cb-884a-e5b71931a0e9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(234, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L260\",\"description\":\"Backfilled house interest (100%) for loan #260\"}', '01a116ce-aa2b-73d3-a0ea-d8fafea85ece', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(235, 'App\\Models\\Partner', 6, 6, 'deposit', 17560000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L261\",\"description\":\"Backfilled house interest (100%) for loan #261\"}', '01a116ce-aa2e-7349-aa01-eb684a7e80e7', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(236, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L262\",\"description\":\"Backfilled house interest (100%) for loan #262\"}', '01a116ce-aa31-73bb-966d-0885aeb43587', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(237, 'App\\Models\\Partner', 6, 6, 'deposit', 21072000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L263\",\"description\":\"Backfilled house interest (100%) for loan #263\"}', '01a116ce-aa34-705e-8ef6-e68ba4b093b3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(238, 'App\\Models\\Partner', 6, 6, 'deposit', 25286400, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L264\",\"description\":\"Backfilled house interest (100%) for loan #264\"}', '01a116ce-aa36-7300-b13b-202d84435a60', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(239, 'App\\Models\\Partner', 6, 6, 'deposit', 90259200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L265\",\"description\":\"Backfilled house interest (100%) for loan #265\"}', '01a116ce-aa39-7088-ad43-0b1f5d64113b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(240, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L267\",\"description\":\"Backfilled house interest (100%) for loan #267\"}', '01a116ce-aa3c-7054-a40a-31d9e08220f6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(241, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L268\",\"description\":\"Backfilled house interest (100%) for loan #268\"}', '01a116ce-aa3f-71e4-8d35-5ecef49b7b64', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(242, 'App\\Models\\Partner', 6, 6, 'deposit', 1440000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L269\",\"description\":\"Backfilled house interest (100%) for loan #269\"}', '01a116ce-aa42-7012-acd9-38dc221a1e99', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(243, 'App\\Models\\Partner', 6, 6, 'deposit', 160000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L270\",\"description\":\"Backfilled house interest (100%) for loan #270\"}', '01a116ce-aa44-7371-8d67-2506befeb789', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(244, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L272\",\"description\":\"Backfilled house interest (100%) for loan #272\"}', '01a116ce-aa47-7299-b536-bab835d053c2', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(245, 'App\\Models\\Partner', 6, 6, 'deposit', 30343700, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L273\",\"description\":\"Backfilled house interest (100%) for loan #273\"}', '01a116ce-aa4a-7077-9e49-0679cd2048ae', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(246, 'App\\Models\\Partner', 6, 6, 'deposit', 80000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L274\",\"description\":\"Backfilled house interest (100%) for loan #274\"}', '01a116ce-aa4d-7172-b9b9-a4767990dd08', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(247, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L275\",\"description\":\"Backfilled house interest (100%) for loan #275\"}', '01a116ce-aa4f-704b-af4c-25d86725afed', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(248, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L276\",\"description\":\"Backfilled house interest (100%) for loan #276\"}', '01a116ce-aa52-7090-83b9-9bcf6a60e1f5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(249, 'App\\Models\\Partner', 6, 6, 'deposit', 70000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L277\",\"description\":\"Backfilled house interest (100%) for loan #277\"}', '01a116ce-aa55-70f7-8688-7cf817ec1436', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(250, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L278\",\"description\":\"Backfilled house interest (100%) for loan #278\"}', '01a116ce-aa58-72c4-b86d-644806c3d989', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(251, 'App\\Models\\Partner', 6, 6, 'deposit', 507000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L279\",\"description\":\"Backfilled house interest (100%) for loan #279\"}', '01a116ce-aa5b-710a-be63-97bfe0b66f23', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(252, 'App\\Models\\Partner', 6, 6, 'deposit', 101496000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L280\",\"description\":\"Backfilled house interest (100%) for loan #280\"}', '01a116ce-aa5e-731d-9b05-e5c3dcfed1af', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(253, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L281\",\"description\":\"Backfilled house interest (100%) for loan #281\"}', '01a116ce-aa60-7214-96ea-10fa349801d7', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(254, 'App\\Models\\Partner', 6, 6, 'deposit', 500000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L282\",\"description\":\"Backfilled house interest (100%) for loan #282\"}', '01a116ce-aa63-7011-8c4b-017409b6fee0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(255, 'App\\Models\\Partner', 6, 6, 'deposit', 16000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L283\",\"description\":\"Backfilled house interest (100%) for loan #283\"}', '01a116ce-aa67-709e-bc7c-93de9eccbe70', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(256, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L285\",\"description\":\"Backfilled house interest (100%) for loan #285\"}', '01a116ce-aa6a-7059-a010-cc49945a8442', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(257, 'App\\Models\\Partner', 6, 6, 'deposit', 16000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L286\",\"description\":\"Backfilled house interest (100%) for loan #286\"}', '01a116ce-aa6d-716d-bcd9-84b5f61db1b1', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(258, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L287\",\"description\":\"Backfilled house interest (100%) for loan #287\"}', '01a116ce-aa6f-7233-b8df-1c43505ce245', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(259, 'App\\Models\\Partner', 6, 6, 'deposit', 728000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L288\",\"description\":\"Backfilled house interest (100%) for loan #288\"}', '01a116ce-aa72-7018-886f-0abdef940ea5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(260, 'App\\Models\\Partner', 6, 6, 'deposit', 600000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L289\",\"description\":\"Backfilled house interest (100%) for loan #289\"}', '01a116ce-aa76-7350-828d-102f1082ea42', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(261, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L290\",\"description\":\"Backfilled house interest (100%) for loan #290\"}', '01a116ce-aa79-7116-a99d-70912ea72789', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(262, 'App\\Models\\Partner', 6, 6, 'deposit', 196680000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L291\",\"description\":\"Backfilled house interest (100%) for loan #291\"}', '01a116ce-aa7c-73e2-8291-91e9a57c83f3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(263, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L292\",\"description\":\"Backfilled house interest (100%) for loan #292\"}', '01a116ce-aa7f-7381-a515-fa886de7f45d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(264, 'App\\Models\\Partner', 6, 6, 'deposit', 151200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L293\",\"description\":\"Backfilled house interest (100%) for loan #293\"}', '01a116ce-aa82-73ff-849a-88c680f80374', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(265, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L299\",\"description\":\"Backfilled house interest (100%) for loan #299\"}', '01a116ce-aa85-7324-9fd5-54068db296ed', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(266, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L307\",\"description\":\"Backfilled house interest (100%) for loan #307\"}', '01a116ce-aa88-73d3-95d1-53bcc8339973', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(267, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L308\",\"description\":\"Backfilled house interest (100%) for loan #308\"}', '01a116ce-aa8b-70c2-823e-3df88507a7b6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(268, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L309\",\"description\":\"Backfilled house interest (100%) for loan #309\"}', '01a116ce-aa8e-73c0-8e6a-895afd2748a4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(269, 'App\\Models\\Partner', 6, 6, 'deposit', 720000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L310\",\"description\":\"Backfilled house interest (100%) for loan #310\"}', '01a116ce-aa91-7088-98de-c240cb8eaa07', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(270, 'App\\Models\\Partner', 6, 6, 'deposit', 659100000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L311\",\"description\":\"Backfilled house interest (100%) for loan #311\"}', '01a116ce-aa94-71b9-a988-e5a22f0fe241', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(271, 'App\\Models\\Partner', 6, 6, 'deposit', 121795200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L312\",\"description\":\"Backfilled house interest (100%) for loan #312\"}', '01a116ce-aa97-70b4-8686-08312990acda', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(272, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L313\",\"description\":\"Backfilled house interest (100%) for loan #313\"}', '01a116ce-aa99-7140-900c-9906cdca72b5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(273, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L314\",\"description\":\"Backfilled house interest (100%) for loan #314\"}', '01a116ce-aa9c-70bb-972f-150c5844bafa', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(274, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L315\",\"description\":\"Backfilled house interest (100%) for loan #315\"}', '01a116ce-aa9f-73db-8cf6-60f8f879248d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(275, 'App\\Models\\Partner', 6, 6, 'deposit', 320000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L316\",\"description\":\"Backfilled house interest (100%) for loan #316\"}', '01a116ce-aaa2-73dc-b3b8-52c3d0a5a725', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(276, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L317\",\"description\":\"Backfilled house interest (100%) for loan #317\"}', '01a116ce-aaa5-7146-911d-35a7f9ebcc4d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(277, 'App\\Models\\Partner', 6, 6, 'deposit', 8000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L318\",\"description\":\"Backfilled house interest (100%) for loan #318\"}', '01a116ce-aaa7-721c-a7c0-ffaec25bd20f', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(278, 'App\\Models\\Partner', 6, 6, 'deposit', 80000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L319\",\"description\":\"Backfilled house interest (100%) for loan #319\"}', '01a116ce-aaaa-7176-9ee1-8cfd590907d4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(279, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L320\",\"description\":\"Backfilled house interest (100%) for loan #320\"}', '01a116ce-aaad-7397-87be-81c9880e023a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(280, 'App\\Models\\Partner', 6, 6, 'deposit', 181248000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L321\",\"description\":\"Backfilled house interest (100%) for loan #321\"}', '01a116ce-aab0-72fb-ad99-5223aa972c0b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(281, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L322\",\"description\":\"Backfilled house interest (100%) for loan #322\"}', '01a116ce-aab2-713e-80a6-0b2947bb91e6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(282, 'App\\Models\\Partner', 6, 6, 'deposit', 384000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L323\",\"description\":\"Backfilled house interest (100%) for loan #323\"}', '01a116ce-aab5-70e2-ad34-8e09414a06fe', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(283, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L324\",\"description\":\"Backfilled house interest (100%) for loan #324\"}', '01a116ce-aab8-71fc-b004-c9620ca63fc6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(284, 'App\\Models\\Partner', 6, 6, 'deposit', 571220000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L325\",\"description\":\"Backfilled house interest (100%) for loan #325\"}', '01a116ce-aabb-70f6-a3bc-29160fcf771a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(285, 'App\\Models\\Partner', 6, 6, 'deposit', 7000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L326\",\"description\":\"Backfilled house interest (100%) for loan #326\"}', '01a116ce-aabe-72e0-9331-5c121b9861a0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(286, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L327\",\"description\":\"Backfilled house interest (100%) for loan #327\"}', '01a116ce-aac2-704b-a036-a9cf3a5c5407', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(287, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L328\",\"description\":\"Backfilled house interest (100%) for loan #328\"}', '01a116ce-aac5-7061-9d47-a46bb462eaff', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(288, 'App\\Models\\Partner', 6, 6, 'deposit', 110000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L329\",\"description\":\"Backfilled house interest (100%) for loan #329\"}', '01a116ce-aac8-737b-a1ae-0b60bfdc7c8d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(289, 'App\\Models\\Partner', 6, 6, 'deposit', 864000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L330\",\"description\":\"Backfilled house interest (100%) for loan #330\"}', '01a116ce-aacb-7182-86d8-6ec8aa39bae4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(290, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L332\",\"description\":\"Backfilled house interest (100%) for loan #332\"}', '01a116ce-aace-726d-93a7-4df7003b9d7f', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(291, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L333\",\"description\":\"Backfilled house interest (100%) for loan #333\"}', '01a116ce-aad1-72ab-ad70-60a18b4cfa70', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(292, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L334\",\"description\":\"Backfilled house interest (100%) for loan #334\"}', '01a116ce-aad4-70ea-8f0e-cec9a79bcd02', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(293, 'App\\Models\\Partner', 6, 6, 'deposit', 255684000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L335\",\"description\":\"Backfilled house interest (100%) for loan #335\"}', '01a116ce-aad7-7003-88e3-9b6329e8f221', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(294, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L336\",\"description\":\"Backfilled house interest (100%) for loan #336\"}', '01a116ce-aad9-72a5-9e2b-b183e5420aae', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(295, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L337\",\"description\":\"Backfilled house interest (100%) for loan #337\"}', '01a116ce-aadc-7074-adf2-20aa1310db86', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(296, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L338\",\"description\":\"Backfilled house interest (100%) for loan #338\"}', '01a116ce-aadf-71f1-bab1-bf853737c916', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(297, 'App\\Models\\Partner', 6, 6, 'deposit', 75000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L339\",\"description\":\"Backfilled house interest (100%) for loan #339\"}', '01a116ce-aae2-711e-a5da-6d7f2eb4cedf', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(298, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L340\",\"description\":\"Backfilled house interest (100%) for loan #340\"}', '01a116ce-aae5-71da-b5b0-1f64d51eb66d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(299, 'App\\Models\\Partner', 6, 6, 'deposit', 14400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L341\",\"description\":\"Backfilled house interest (100%) for loan #341\"}', '01a116ce-aae8-70fa-94c4-e68d11352d15', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(300, 'App\\Models\\Partner', 6, 6, 'deposit', 17280000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L342\",\"description\":\"Backfilled house interest (100%) for loan #342\"}', '01a116ce-aaeb-73c1-8da5-88c345d73358', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(301, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L343\",\"description\":\"Backfilled house interest (100%) for loan #343\"}', '01a116ce-aaef-7247-a2fe-b419cc1ecb40', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(302, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L344\",\"description\":\"Backfilled house interest (100%) for loan #344\"}', '01a116ce-aaf2-71aa-abd7-9a1dfaa89355', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(303, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L345\",\"description\":\"Backfilled house interest (100%) for loan #345\"}', '01a116ce-aaf5-712f-b227-01034fcc254e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(304, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L346\",\"description\":\"Backfilled house interest (100%) for loan #346\"}', '01a116ce-aaf8-7113-bf41-852aa45a1d3c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(305, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L347\",\"description\":\"Backfilled house interest (100%) for loan #347\"}', '01a116ce-aafb-7345-b9d4-b763737fa1e1', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(306, 'App\\Models\\Partner', 6, 6, 'deposit', 68663900, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L349\",\"description\":\"Backfilled house interest (100%) for loan #349\"}', '01a116ce-aafe-71d5-8295-188fb067db3c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(307, 'App\\Models\\Partner', 6, 6, 'deposit', 16000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L350\",\"description\":\"Backfilled house interest (100%) for loan #350\"}', '01a116ce-ab00-7084-87ff-a494e21f5c0e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(308, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L351\",\"description\":\"Backfilled house interest (100%) for loan #351\"}', '01a116ce-ab03-7210-92a7-321c13e7c169', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(309, 'App\\Models\\Partner', 6, 6, 'deposit', 3000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L352\",\"description\":\"Backfilled house interest (100%) for loan #352\"}', '01a116ce-ab06-73e3-b454-2bd4b4a0dc6b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(310, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L353\",\"description\":\"Backfilled house interest (100%) for loan #353\"}', '01a116ce-ab08-7287-9c52-b86b107349ad', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(311, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L354\",\"description\":\"Backfilled house interest (100%) for loan #354\"}', '01a116ce-ab0b-73b3-a66f-c074ec0b2403', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(312, 'App\\Models\\Partner', 6, 6, 'deposit', 2000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L355\",\"description\":\"Backfilled house interest (100%) for loan #355\"}', '01a116ce-ab0e-7050-8bdd-405c03c01d9b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(313, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L356\",\"description\":\"Backfilled house interest (100%) for loan #356\"}', '01a116ce-ab11-7108-b01d-b756deba16ab', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(314, 'App\\Models\\Partner', 6, 6, 'deposit', 90000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L357\",\"description\":\"Backfilled house interest (100%) for loan #357\"}', '01a116ce-ab13-72b9-a577-566348454b7e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(315, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L358\",\"description\":\"Backfilled house interest (100%) for loan #358\"}', '01a116ce-ab16-71f6-8fca-38b1e8c63c1c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(316, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L359\",\"description\":\"Backfilled house interest (100%) for loan #359\"}', '01a116ce-ab19-706b-bacd-39e2b5f0bfb3', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(317, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L360\",\"description\":\"Backfilled house interest (100%) for loan #360\"}', '01a116ce-ab1c-7364-b77a-a8f4000cde79', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(318, 'App\\Models\\Partner', 6, 6, 'deposit', 6400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L361\",\"description\":\"Backfilled house interest (100%) for loan #361\"}', '01a116ce-ab1f-7208-b657-35b82aa8c48d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(319, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L362\",\"description\":\"Backfilled house interest (100%) for loan #362\"}', '01a116ce-ab21-73fa-9387-3846dd7074e4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(320, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L363\",\"description\":\"Backfilled house interest (100%) for loan #363\"}', '01a116ce-ab24-701b-8919-00450415bd86', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(321, 'App\\Models\\Partner', 6, 6, 'deposit', 146154200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L364\",\"description\":\"Backfilled house interest (100%) for loan #364\"}', '01a116ce-ab27-7379-9a3d-8937bebc4fa5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(322, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L365\",\"description\":\"Backfilled house interest (100%) for loan #365\"}', '01a116ce-ab2a-70ae-82ae-df278d433c6f', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(323, 'App\\Models\\Partner', 6, 6, 'deposit', 8400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L366\",\"description\":\"Backfilled house interest (100%) for loan #366\"}', '01a116ce-ab2d-72b7-a366-5607b2a53cc9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(324, 'App\\Models\\Partner', 6, 6, 'deposit', 10080000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L367\",\"description\":\"Backfilled house interest (100%) for loan #367\"}', '01a116ce-ab32-7174-aae8-9db0001c67ea', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(325, 'App\\Models\\Partner', 6, 6, 'deposit', 20736000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L368\",\"description\":\"Backfilled house interest (100%) for loan #368\"}', '01a116ce-ab35-706b-9b01-efbd80e7a06b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(326, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L369\",\"description\":\"Backfilled house interest (100%) for loan #369\"}', '01a116ce-ab38-735d-b31c-4c48594e68fa', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(327, 'App\\Models\\Partner', 6, 6, 'deposit', 48000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L370\",\"description\":\"Backfilled house interest (100%) for loan #370\"}', '01a116ce-ab3b-721d-827a-a136e3922385', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(328, 'App\\Models\\Partner', 6, 6, 'deposit', 3000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L371\",\"description\":\"Backfilled house interest (100%) for loan #371\"}', '01a116ce-ab3e-7322-b7b3-1a29735bd5c9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(329, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L372\",\"description\":\"Backfilled house interest (100%) for loan #372\"}', '01a116ce-ab42-72d3-98f1-cf9b65a3b4e8', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(330, 'App\\Models\\Partner', 6, 6, 'deposit', 6080000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L373\",\"description\":\"Backfilled house interest (100%) for loan #373\"}', '01a116ce-ab45-72b0-9c48-c0d4b6252a6e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(331, 'App\\Models\\Partner', 6, 6, 'deposit', 685464000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L374\",\"description\":\"Backfilled house interest (100%) for loan #374\"}', '01a116ce-ab48-7114-a583-5e4fe53b2636', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(332, 'App\\Models\\Partner', 6, 6, 'deposit', 36978000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L375\",\"description\":\"Backfilled house interest (100%) for loan #375\"}', '01a116ce-ab4c-72a4-aa70-0da75afc465c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(333, 'App\\Models\\Partner', 6, 6, 'deposit', 44373600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L376\",\"description\":\"Backfilled house interest (100%) for loan #376\"}', '01a116ce-ab4f-7183-9d3f-6056fb45a7d8', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(334, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L377\",\"description\":\"Backfilled house interest (100%) for loan #377\"}', '01a116ce-ab52-73ef-bd48-8bd0c917eb72', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(335, 'App\\Models\\Partner', 6, 6, 'deposit', 15000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L378\",\"description\":\"Backfilled house interest (100%) for loan #378\"}', '01a116ce-ab55-7348-8ad3-bf84f724a642', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(336, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L379\",\"description\":\"Backfilled house interest (100%) for loan #379\"}', '01a116ce-ab58-70d8-b1cb-713e015c3159', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(337, 'App\\Models\\Partner', 6, 6, 'deposit', 396000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L380\",\"description\":\"Backfilled house interest (100%) for loan #380\"}', '01a116ce-ab5b-71c9-af21-34b5d363fc26', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(338, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L381\",\"description\":\"Backfilled house interest (100%) for loan #381\"}', '01a116ce-ab5e-7023-8bff-bb52fef3a5ae', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(339, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L382\",\"description\":\"Backfilled house interest (100%) for loan #382\"}', '01a116ce-ab61-73b6-b3e5-b6b6a1760ff4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(340, 'App\\Models\\Partner', 6, 6, 'deposit', 332389200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L383\",\"description\":\"Backfilled house interest (100%) for loan #383\"}', '01a116ce-ab64-736b-bd97-7bf628771a4a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(341, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L384\",\"description\":\"Backfilled house interest (100%) for loan #384\"}', '01a116ce-ab67-7269-9646-ef8e53278bfb', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(342, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L385\",\"description\":\"Backfilled house interest (100%) for loan #385\"}', '01a116ce-ab6a-73c2-a474-4eefbb33ed66', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(343, 'App\\Models\\Partner', 6, 6, 'deposit', 2000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L386\",\"description\":\"Backfilled house interest (100%) for loan #386\"}', '01a116ce-ab6e-7326-8943-ef1412c31382', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(344, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L387\",\"description\":\"Backfilled house interest (100%) for loan #387\"}', '01a116ce-ab71-71f5-9623-cb324f45ac71', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(345, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L388\",\"description\":\"Backfilled house interest (100%) for loan #388\"}', '01a116ce-ab74-724b-8201-d036fc47da23', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(346, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L389\",\"description\":\"Backfilled house interest (100%) for loan #389\"}', '01a116ce-ab77-72a9-a7b0-8d171b05037c', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(347, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L390\",\"description\":\"Backfilled house interest (100%) for loan #390\"}', '01a116ce-ab7a-7182-b9ca-9c99e5d82ea0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(348, 'App\\Models\\Partner', 6, 6, 'deposit', 14400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L391\",\"description\":\"Backfilled house interest (100%) for loan #391\"}', '01a116ce-ab7d-73eb-aeeb-a6d18c6dac32', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(349, 'App\\Models\\Partner', 6, 6, 'deposit', 10800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L392\",\"description\":\"Backfilled house interest (100%) for loan #392\"}', '01a116ce-ab81-71d4-b6e8-77388b507cd9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(350, 'App\\Models\\Partner', 6, 6, 'deposit', 3600000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L393\",\"description\":\"Backfilled house interest (100%) for loan #393\"}', '01a116ce-ab84-7182-bd7b-80674f473509', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(351, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L394\",\"description\":\"Backfilled house interest (100%) for loan #394\"}', '01a116ce-ab87-71d9-88ee-e851301cca6e', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(352, 'App\\Models\\Partner', 6, 6, 'deposit', 70000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L395\",\"description\":\"Backfilled house interest (100%) for loan #395\"}', '01a116ce-ab8a-7299-a469-03630ea0eb8b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(353, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L396\",\"description\":\"Backfilled house interest (100%) for loan #396\"}', '01a116ce-ab8d-73b9-9aaa-6f927632fc87', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(354, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L397\",\"description\":\"Backfilled house interest (100%) for loan #397\"}', '01a116ce-ab91-7177-99a3-6c3e41cf04f2', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(355, 'App\\Models\\Partner', 6, 6, 'deposit', 14000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L398\",\"description\":\"Backfilled house interest (100%) for loan #398\"}', '01a116ce-ac91-712c-93ca-e62a00e0951b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(356, 'App\\Models\\Partner', 6, 6, 'deposit', 62500000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L399\",\"description\":\"Backfilled house interest (100%) for loan #399\"}', '01a116ce-ac9b-713f-9e46-f99e3a536b30', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(357, 'App\\Models\\Partner', 6, 6, 'deposit', 7200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L400\",\"description\":\"Backfilled house interest (100%) for loan #400\"}', '01a116ce-ac9e-7248-b44c-e14e36cf3899', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(358, 'App\\Models\\Partner', 6, 6, 'deposit', 150000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L401\",\"description\":\"Backfilled house interest (100%) for loan #401\"}', '01a116ce-aca1-7250-8c23-1b2951b328c6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(359, 'App\\Models\\Partner', 6, 6, 'deposit', 822556800, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L402\",\"description\":\"Backfilled house interest (100%) for loan #402\"}', '01a116ce-aca4-73ef-96f4-51ebd22be2c1', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(360, 'App\\Models\\Partner', 6, 6, 'deposit', 2400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L403\",\"description\":\"Backfilled house interest (100%) for loan #403\"}', '01a116ce-aca8-709b-be7d-61959c6899b0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(361, 'App\\Models\\Partner', 6, 6, 'deposit', 240000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L404\",\"description\":\"Backfilled house interest (100%) for loan #404\"}', '01a116ce-acaa-72f7-8bf3-fa781c8a8690', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(362, 'App\\Models\\Partner', 6, 6, 'deposit', 17280000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L405\",\"description\":\"Backfilled house interest (100%) for loan #405\"}', '01a116ce-acad-7283-8e14-9d024c123784', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(363, 'App\\Models\\Partner', 6, 6, 'deposit', 175385100, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L406\",\"description\":\"Backfilled house interest (100%) for loan #406\"}', '01a116ce-acb0-7018-b7a2-89923b401c1a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(364, 'App\\Models\\Partner', 6, 6, 'deposit', 2880000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L407\",\"description\":\"Backfilled house interest (100%) for loan #407\"}', '01a116ce-acb3-72a3-9d21-bdaca4844df6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(365, 'App\\Models\\Partner', 6, 6, 'deposit', 142500000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L408\",\"description\":\"Backfilled house interest (100%) for loan #408\"}', '01a116ce-acb7-7121-a5f1-30e1352170ba', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(366, 'App\\Models\\Partner', 6, 6, 'deposit', 432106000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L409\",\"description\":\"Backfilled house interest (100%) for loan #409\"}', '01a116ce-acba-709d-a2e7-f4327b6b72e2', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(367, 'App\\Models\\Partner', 6, 6, 'deposit', 30000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L410\",\"description\":\"Backfilled house interest (100%) for loan #410\"}', '01a116ce-acbd-7030-b624-63638cf286b2', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(368, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L411\",\"description\":\"Backfilled house interest (100%) for loan #411\"}', '01a116ce-acc0-70df-91cb-46e296b4f2bd', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(369, 'App\\Models\\Partner', 6, 6, 'deposit', 70000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L412\",\"description\":\"Backfilled house interest (100%) for loan #412\"}', '01a116ce-acc4-7384-8cb3-e7d3a10fb2b5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(370, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L413\",\"description\":\"Backfilled house interest (100%) for loan #413\"}', '01a116ce-acc7-734c-9c51-1030197138ba', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(371, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L415\",\"description\":\"Backfilled house interest (100%) for loan #415\"}', '01a116ce-acca-7363-82cc-2a569a102120', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(372, 'App\\Models\\Partner', 6, 6, 'deposit', 3720000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L416\",\"description\":\"Backfilled house interest (100%) for loan #416\"}', '01a116ce-accd-73d6-b2eb-86915f7b5f49', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(373, 'App\\Models\\Partner', 6, 6, 'deposit', 8640000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L417\",\"description\":\"Backfilled house interest (100%) for loan #417\"}', '01a116ce-acd0-71dc-b9f7-1ec04b80c0d6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(374, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L418\",\"description\":\"Backfilled house interest (100%) for loan #418\"}', '01a116ce-acd4-7390-bef1-d5aadfd4432d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(375, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L419\",\"description\":\"Backfilled house interest (100%) for loan #419\"}', '01a116ce-acd7-73a0-83d1-9c67402d363b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(376, 'App\\Models\\Partner', 6, 6, 'deposit', 210462100, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L420\",\"description\":\"Backfilled house interest (100%) for loan #420\"}', '01a116ce-acdb-709b-806f-694bc1945054', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(377, 'App\\Models\\Partner', 6, 6, 'deposit', 44000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L421\",\"description\":\"Backfilled house interest (100%) for loan #421\"}', '01a116ce-acde-73ca-ab6f-7283d5c71acf', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL);
INSERT INTO `transactions` (`id`, `payable_type`, `payable_id`, `wallet_id`, `type`, `amount`, `confirmed`, `meta`, `uuid`, `created_at`, `updated_at`, `deleted_at`) VALUES
(378, 'App\\Models\\Partner', 6, 6, 'deposit', 180000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L422\",\"description\":\"Backfilled house interest (100%) for loan #422\"}', '01a116ce-ace2-71c4-b1ca-f41ad369de57', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(379, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L423\",\"description\":\"Backfilled house interest (100%) for loan #423\"}', '01a116ce-ace5-707d-bb32-30cbc716761a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(380, 'App\\Models\\Partner', 6, 6, 'deposit', 10368000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L424\",\"description\":\"Backfilled house interest (100%) for loan #424\"}', '01a116ce-ace8-704c-9646-a08d279f3125', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(381, 'App\\Models\\Partner', 6, 6, 'deposit', 3456000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L425\",\"description\":\"Backfilled house interest (100%) for loan #425\"}', '01a116ce-aceb-7368-b7a1-e161cd71871d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(382, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L426\",\"description\":\"Backfilled house interest (100%) for loan #426\"}', '01a116ce-acee-7280-add2-f97b0dafc136', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(383, 'App\\Models\\Partner', 6, 6, 'deposit', 171000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L427\",\"description\":\"Backfilled house interest (100%) for loan #427\"}', '01a116ce-acf1-737c-b8d2-bd12e53f4596', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(384, 'App\\Models\\Partner', 6, 6, 'deposit', 4320000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L428\",\"description\":\"Backfilled house interest (100%) for loan #428\"}', '01a116ce-acf4-72f7-a9cc-5d221fa64f54', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(385, 'App\\Models\\Partner', 6, 6, 'deposit', 18000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L430\",\"description\":\"Backfilled house interest (100%) for loan #430\"}', '01a116ce-acf8-7078-93dc-157206ca88a0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(386, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L431\",\"description\":\"Backfilled house interest (100%) for loan #431\"}', '01a116ce-acfc-73cc-a0b5-279075861c98', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(387, 'App\\Models\\Partner', 6, 6, 'deposit', 205200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L432\",\"description\":\"Backfilled house interest (100%) for loan #432\"}', '01a116ce-acff-71f5-a78a-f9152b54dd43', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(388, 'App\\Models\\Partner', 6, 6, 'deposit', 28000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L433\",\"description\":\"Backfilled house interest (100%) for loan #433\"}', '01a116ce-ad02-70b7-b896-64eb8b5d3552', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(389, 'App\\Models\\Partner', 6, 6, 'deposit', 42000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L434\",\"description\":\"Backfilled house interest (100%) for loan #434\"}', '01a116ce-ad04-70ca-8d1b-05a2273765d4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(390, 'App\\Models\\Partner', 6, 6, 'deposit', 4147200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L435\",\"description\":\"Backfilled house interest (100%) for loan #435\"}', '01a116ce-ad07-708a-b346-e5270119f9ba', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(391, 'App\\Models\\Partner', 6, 6, 'deposit', 21200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L437\",\"description\":\"Backfilled house interest (100%) for loan #437\"}', '01a116ce-ad0a-7158-8114-7837d8fa270b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(392, 'App\\Models\\Partner', 6, 6, 'deposit', 27600000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L438\",\"description\":\"Backfilled house interest (100%) for loan #438\"}', '01a116ce-ad0c-70a2-ae03-7f5c47b52b77', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(393, 'App\\Models\\Partner', 6, 6, 'deposit', 34400000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L439\",\"description\":\"Backfilled house interest (100%) for loan #439\"}', '01a116ce-ad0f-72b7-b263-42bcc11704ed', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(394, 'App\\Models\\Partner', 6, 6, 'deposit', 12441600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L440\",\"description\":\"Backfilled house interest (100%) for loan #440\"}', '01a116ce-ad14-720c-8b24-ef760dc89ee1', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(395, 'App\\Models\\Partner', 6, 6, 'deposit', 84000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L441\",\"description\":\"Backfilled house interest (100%) for loan #441\"}', '01a116ce-ad17-7252-8569-1410d1a1422a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(396, 'App\\Models\\Partner', 6, 6, 'deposit', 987068200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L442\",\"description\":\"Backfilled house interest (100%) for loan #442\"}', '01a116ce-ad1a-70b6-bbd2-99f4a57a032d', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(397, 'App\\Models\\Partner', 6, 6, 'deposit', 43200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L443\",\"description\":\"Backfilled house interest (100%) for loan #443\"}', '01a116ce-ad1c-734e-a4af-f0ef7d5f74ff', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(398, 'App\\Models\\Partner', 6, 6, 'deposit', 51840000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L444\",\"description\":\"Backfilled house interest (100%) for loan #444\"}', '01a116ce-ad1f-71b1-a571-5f418dec36a4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(399, 'App\\Models\\Partner', 6, 6, 'deposit', 4976600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L446\",\"description\":\"Backfilled house interest (100%) for loan #446\"}', '01a116ce-ad22-7323-978f-298339f03cd1', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(400, 'App\\Models\\Partner', 6, 6, 'deposit', 14930000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L447\",\"description\":\"Backfilled house interest (100%) for loan #447\"}', '01a116ce-ad25-7322-8ac5-6d8548f01917', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(401, 'App\\Models\\Partner', 6, 6, 'deposit', 33120000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L448\",\"description\":\"Backfilled house interest (100%) for loan #448\"}', '01a116ce-ad28-720c-a3da-4fcba4e6cef4', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(402, 'App\\Models\\Partner', 6, 6, 'deposit', 25440000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L449\",\"description\":\"Backfilled house interest (100%) for loan #449\"}', '01a116ce-ad2b-7360-9bd7-8e1531f9a362', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(403, 'App\\Models\\Partner', 6, 6, 'deposit', 1089272300, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L450\",\"description\":\"Backfilled house interest (100%) for loan #450\"}', '01a116ce-ad2e-71e8-a114-0944cc95d8e5', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(404, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L451\",\"description\":\"Backfilled house interest (100%) for loan #451\"}', '01a116ce-ad33-7140-b8c5-58d6317f9493', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(405, 'App\\Models\\Partner', 6, 6, 'deposit', 5972000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L452\",\"description\":\"Backfilled house interest (100%) for loan #452\"}', '01a116ce-ad37-7344-af13-a138451028ff', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(406, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L453\",\"description\":\"Backfilled house interest (100%) for loan #453\"}', '01a116ce-ad3a-71b6-a6d5-2353b8559214', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(407, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L454\",\"description\":\"Backfilled house interest (100%) for loan #454\"}', '01a116ce-ad3d-70d0-aebe-693aca8d1169', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(408, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L455\",\"description\":\"Backfilled house interest (100%) for loan #455\"}', '01a116ce-ad40-733e-90c8-a188e06d6366', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(409, 'App\\Models\\Partner', 6, 6, 'deposit', 252554600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L456\",\"description\":\"Backfilled house interest (100%) for loan #456\"}', '01a116ce-ad43-702b-aea0-39d5c74d47d0', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(410, 'App\\Models\\Partner', 6, 6, 'deposit', 61744000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L457\",\"description\":\"Backfilled house interest (100%) for loan #457\"}', '01a116ce-ad46-7050-8db9-7c6330136426', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(411, 'App\\Models\\Partner', 6, 6, 'deposit', 400000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L458\",\"description\":\"Backfilled house interest (100%) for loan #458\"}', '01a116ce-ad49-7177-a149-9b0d5a34070b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(412, 'App\\Models\\Partner', 6, 6, 'deposit', 30528000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L459\",\"description\":\"Backfilled house interest (100%) for loan #459\"}', '01a116ce-ad4d-7312-8b0b-7874f6523f1b', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(413, 'App\\Models\\Partner', 6, 6, 'deposit', 42800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L460\",\"description\":\"Backfilled house interest (100%) for loan #460\"}', '01a116ce-ad50-712e-ac26-7f18e8ddc6ea', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(414, 'App\\Models\\Partner', 6, 6, 'deposit', 513600000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L461\",\"description\":\"Backfilled house interest (100%) for loan #461\"}', '01a116ce-ad53-7287-b368-155631372668', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(415, 'App\\Models\\Partner', 6, 6, 'deposit', 7166400, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L464\",\"description\":\"Backfilled house interest (100%) for loan #464\"}', '01a116ce-ad56-7095-be47-eb63f899a39a', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(416, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L466\",\"description\":\"Backfilled house interest (100%) for loan #466\"}', '01a116ce-ad61-7331-8974-6aae233b12a9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(417, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L467\",\"description\":\"Backfilled house interest (100%) for loan #467\"}', '01a116ce-ad65-7227-8335-efbf794fa0a6', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(418, 'App\\Models\\Partner', 6, 6, 'deposit', 200000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L468\",\"description\":\"Backfilled house interest (100%) for loan #468\"}', '01a116ce-ad6b-72f4-a389-f2f24fb351d9', '2026-10-07 12:40:05', '2026-10-07 12:40:05', NULL),
(419, 'App\\Models\\Partner', 6, 6, 'deposit', 120000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L469\",\"description\":\"Backfilled house interest (100%) for loan #469\"}', '01a116ce-ad73-72e5-965a-503df3db60eb', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(420, 'App\\Models\\Partner', 6, 6, 'deposit', 5184000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L470\",\"description\":\"Backfilled house interest (100%) for loan #470\"}', '01a116ce-ad77-70e0-8a11-b6a3ea0b962c', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(421, 'App\\Models\\Partner', 6, 6, 'deposit', 120000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L471\",\"description\":\"Backfilled house interest (100%) for loan #471\"}', '01a116ce-ad7f-7295-9473-84898dc4a3e9', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(422, 'App\\Models\\Partner', 6, 6, 'deposit', 480000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L472\",\"description\":\"Backfilled house interest (100%) for loan #472\"}', '01a116ce-ad86-7229-9c4b-90a669ff9524', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(423, 'App\\Models\\Partner', 6, 6, 'deposit', 36633600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L473\",\"description\":\"Backfilled house interest (100%) for loan #473\"}', '01a116ce-ad8d-73e2-b016-440ce1a0deb2', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(424, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L474\",\"description\":\"Backfilled house interest (100%) for loan #474\"}', '01a116ce-ad94-736c-96c6-9f1b83fcd783', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(425, 'App\\Models\\Partner', 6, 6, 'deposit', 74092800, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L475\",\"description\":\"Backfilled house interest (100%) for loan #475\"}', '01a116ce-ad9b-7041-a364-def9da6f2ea3', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(426, 'App\\Models\\Partner', 6, 6, 'deposit', 10319600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L476\",\"description\":\"Backfilled house interest (100%) for loan #476\"}', '01a116ce-ada4-716c-9696-a9d2b7956186', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(427, 'App\\Models\\Partner', 6, 6, 'deposit', 88911400, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L478\",\"description\":\"Backfilled house interest (100%) for loan #478\"}', '01a116ce-adad-71b7-81e9-8302bedfb1b4', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(428, 'App\\Models\\Partner', 6, 6, 'deposit', 43960300, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L479\",\"description\":\"Backfilled house interest (100%) for loan #479\"}', '01a116ce-adb3-73a7-86b3-cbe3510dfdc6', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(429, 'App\\Models\\Partner', 6, 6, 'deposit', 64000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L480\",\"description\":\"Backfilled house interest (100%) for loan #480\"}', '01a116ce-adbb-7146-87b0-c7be1b51879d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(430, 'App\\Models\\Partner', 6, 6, 'deposit', 864000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L481\",\"description\":\"Backfilled house interest (100%) for loan #481\"}', '01a116ce-adc2-7327-9a43-8f4b25175fc1', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(431, 'App\\Models\\Partner', 6, 6, 'deposit', 140000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L482\",\"description\":\"Backfilled house interest (100%) for loan #482\"}', '01a116ce-adc9-712a-be85-b9b72eebfa28', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(432, 'App\\Models\\Partner', 6, 6, 'deposit', 12383600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L483\",\"description\":\"Backfilled house interest (100%) for loan #483\"}', '01a116ce-add0-710c-8bc6-6effce28db33', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(433, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L484\",\"description\":\"Backfilled house interest (100%) for loan #484\"}', '01a116ce-add4-7325-b7f3-ea1ac6639fa2', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(434, 'App\\Models\\Partner', 6, 6, 'deposit', 36175200, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L485\",\"description\":\"Backfilled house interest (100%) for loan #485\"}', '01a116ce-add9-7275-8a9b-90cb4be408ee', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(435, 'App\\Models\\Partner', 6, 6, 'deposit', 76800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L487\",\"description\":\"Backfilled house interest (100%) for loan #487\"}', '01a116ce-addc-704b-b19c-4400b404217a', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(436, 'App\\Models\\Partner', 6, 6, 'deposit', 111293600, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L489\",\"description\":\"Backfilled house interest (100%) for loan #489\"}', '01a116ce-addf-715b-b845-29da07e2da20', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(437, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L493\",\"description\":\"Backfilled house interest (100%) for loan #493\"}', '01a116ce-ade2-700d-a285-07130a197a9f', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(438, 'App\\Models\\Partner', 6, 6, 'deposit', 291200000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L494\",\"description\":\"Backfilled house interest (100%) for loan #494\"}', '01a116ce-ade5-729d-ae12-f9c879ffc336', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(439, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L495\",\"description\":\"Backfilled house interest (100%) for loan #495\"}', '01a116ce-ade8-7374-b1b3-71a8cce0cc37', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(440, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L496\",\"description\":\"Backfilled house interest (100%) for loan #496\"}', '01a116ce-adeb-727a-96ef-8b3501f74589', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(441, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L497\",\"description\":\"Backfilled house interest (100%) for loan #497\"}', '01a116ce-adef-72f7-a779-e7b5064a7e0d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(442, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L498\",\"description\":\"Backfilled house interest (100%) for loan #498\"}', '01a116ce-adf2-73ba-98b9-2b12c9a2e47f', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(443, 'App\\Models\\Partner', 6, 6, 'deposit', 100000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L499\",\"description\":\"Backfilled house interest (100%) for loan #499\"}', '01a116ce-adf5-71b3-8803-42aaef5f188d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(444, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L501\",\"description\":\"Backfilled house interest (100%) for loan #501\"}', '01a116ce-adf8-72ab-91ee-618ba3e0d1be', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(445, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L502\",\"description\":\"Backfilled house interest (100%) for loan #502\"}', '01a116ce-adfc-7278-89c8-e52641cc6149', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(446, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L503\",\"description\":\"Backfilled house interest (100%) for loan #503\"}', '01a116ce-adff-73b4-8ab7-76b503d6117a', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(447, 'App\\Models\\Partner', 6, 6, 'deposit', 104000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L505\",\"description\":\"Backfilled house interest (100%) for loan #505\"}', '01a116ce-ae02-734b-a6ab-6e5565c8cc88', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(448, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L507\",\"description\":\"Backfilled house interest (100%) for loan #507\"}', '01a116ce-ae05-7386-87c6-e6030e194486', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(449, 'App\\Models\\Partner', 6, 6, 'deposit', 75000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L508\",\"description\":\"Backfilled house interest (100%) for loan #508\"}', '01a116ce-ae09-703c-a346-110d2cdf0162', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(450, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L509\",\"description\":\"Backfilled house interest (100%) for loan #509\"}', '01a116ce-ae0c-716d-a213-93fa8b19b107', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(451, 'App\\Models\\Partner', 6, 6, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L512\",\"description\":\"Backfilled house interest (100%) for loan #512\"}', '01a116ce-ae0f-722a-b5b9-d6acce7f91d0', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(452, 'App\\Models\\Partner', 6, 6, 'deposit', 150000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L514\",\"description\":\"Backfilled house interest (100%) for loan #514\"}', '01a116ce-ae12-72d9-b0d0-344f198c7182', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(453, 'App\\Models\\Partner', 6, 6, 'deposit', 128800000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L516\",\"description\":\"Backfilled house interest (100%) for loan #516\"}', '01a116ce-ae15-704c-aa76-64b73a6727af', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(454, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L517\",\"description\":\"Backfilled house interest (100%) for loan #517\"}', '01a116ce-ae17-738e-8dd8-68cf09611f63', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(455, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L518\",\"description\":\"Backfilled house interest (100%) for loan #518\"}', '01a116ce-ae1a-7323-9a2c-6fff74df73b6', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(456, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L519\",\"description\":\"Backfilled house interest (100%) for loan #519\"}', '01a116ce-ae1d-728e-9e1a-0707477a04d8', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(457, 'App\\Models\\Partner', 6, 6, 'deposit', 10000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L521\",\"description\":\"Backfilled house interest (100%) for loan #521\"}', '01a116ce-ae20-7187-880c-8285ec1ab150', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(458, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L522\",\"description\":\"Backfilled house interest (100%) for loan #522\"}', '01a116ce-ae23-727b-baaf-c6766eb64fa6', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(459, 'App\\Models\\Partner', 6, 6, 'deposit', 20000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L524\",\"description\":\"Backfilled house interest (100%) for loan #524\"}', '01a116ce-ae25-7388-9ac3-acd78ed8bfa3', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(460, 'App\\Models\\Partner', 6, 6, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L525\",\"description\":\"Backfilled house interest (100%) for loan #525\"}', '01a116ce-ae28-7220-891b-54a85dfdf8f9', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(461, 'App\\Models\\Partner', 6, 6, 'deposit', 50000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L526\",\"description\":\"Backfilled house interest (100%) for loan #526\"}', '01a116ce-ae2b-714e-98a7-6368a6920c2a', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(462, 'App\\Models\\Partner', 6, 6, 'deposit', 2000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L527\",\"description\":\"Backfilled house interest (100%) for loan #527\"}', '01a116ce-ae2e-7026-8622-d18a2cab081d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(463, 'App\\Models\\Partner', 6, 6, 'deposit', 18000000, 1, '{\"reference\":\"BACKFILL-HOUSE-INT-L531\",\"description\":\"Backfilled house interest (100%) for loan #531\"}', '01a116ce-ae30-70d1-8a88-68d684a46f80', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(464, 'App\\Models\\Partner', 6, 6, 'deposit', 144000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L1\",\"description\":\"Backfilled house interest (non-broker portion) for loan #1\"}', '01a116ce-ae33-714a-ac09-3018b59f1680', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(465, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L2\",\"description\":\"Backfilled house interest (non-broker portion) for loan #2\"}', '01a116ce-ae36-7164-995a-de8f1af7c0de', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(466, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L3\",\"description\":\"Backfilled house interest (non-broker portion) for loan #3\"}', '01a116ce-ae39-715d-bbc6-6faf03e9c98d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(467, 'App\\Models\\Partner', 6, 6, 'deposit', 42000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L4\",\"description\":\"Backfilled house interest (non-broker portion) for loan #4\"}', '01a116ce-ae3b-7355-a490-9a04bf31d164', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(468, 'App\\Models\\Partner', 6, 6, 'deposit', 32400000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L12\",\"description\":\"Backfilled house interest (non-broker portion) for loan #12\"}', '01a116ce-ae3e-717a-aa59-c3c2eba72215', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(469, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L13\",\"description\":\"Backfilled house interest (non-broker portion) for loan #13\"}', '01a116ce-ae41-7131-ba2b-cc021948317b', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(470, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L16\",\"description\":\"Backfilled house interest (non-broker portion) for loan #16\"}', '01a116ce-ae44-726d-8d0f-3dcf0cdeef9e', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(471, 'App\\Models\\Partner', 6, 6, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L17\",\"description\":\"Backfilled house interest (non-broker portion) for loan #17\"}', '01a116ce-ae47-730d-a46a-6b45f938a2fd', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(472, 'App\\Models\\Partner', 6, 6, 'deposit', 42000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L18\",\"description\":\"Backfilled house interest (non-broker portion) for loan #18\"}', '01a116ce-ae4a-71ba-956f-02059cd40b23', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(473, 'App\\Models\\Partner', 6, 6, 'deposit', 50400000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L21\",\"description\":\"Backfilled house interest (non-broker portion) for loan #21\"}', '01a116ce-ae4d-7205-99b2-497b48634c28', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(474, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L22\",\"description\":\"Backfilled house interest (non-broker portion) for loan #22\"}', '01a116ce-ae53-7023-b84a-8ad71f5c6e06', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(475, 'App\\Models\\Partner', 6, 6, 'deposit', 6000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L23\",\"description\":\"Backfilled house interest (non-broker portion) for loan #23\"}', '01a116ce-ae56-7135-a08f-46eaddf811f1', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(476, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L28\",\"description\":\"Backfilled house interest (non-broker portion) for loan #28\"}', '01a116ce-ae5a-73bf-bed5-1fececa4f7c0', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(477, 'App\\Models\\Partner', 6, 6, 'deposit', 52800000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L36\",\"description\":\"Backfilled house interest (non-broker portion) for loan #36\"}', '01a116ce-ae5d-71ee-9e9e-9c7af43e6b56', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(478, 'App\\Models\\Partner', 6, 6, 'deposit', 120000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L50\",\"description\":\"Backfilled house interest (non-broker portion) for loan #50\"}', '01a116ce-ae60-7022-ac56-bde2136eec4e', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(479, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L54\",\"description\":\"Backfilled house interest (non-broker portion) for loan #54\"}', '01a116ce-ae63-7093-bbd0-fc6fe3d74650', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(480, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L56\",\"description\":\"Backfilled house interest (non-broker portion) for loan #56\"}', '01a116ce-ae66-70e6-b51a-b013d3870b56', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(481, 'App\\Models\\Partner', 6, 6, 'deposit', 54000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L57\",\"description\":\"Backfilled house interest (non-broker portion) for loan #57\"}', '01a116ce-ae6a-73bb-bfa3-ecfeeb2ab1dd', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(482, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L60\",\"description\":\"Backfilled house interest (non-broker portion) for loan #60\"}', '01a116ce-ae6d-71c9-aaa5-e7d9a8c93e3a', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(483, 'App\\Models\\Partner', 6, 6, 'deposit', 3000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L66\",\"description\":\"Backfilled house interest (non-broker portion) for loan #66\"}', '01a116ce-ae70-739a-947e-3b5c738f5df1', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(484, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L68\",\"description\":\"Backfilled house interest (non-broker portion) for loan #68\"}', '01a116ce-ae75-7307-8421-261d231cd1fa', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(485, 'App\\Models\\Partner', 6, 6, 'deposit', 60000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L74\",\"description\":\"Backfilled house interest (non-broker portion) for loan #74\"}', '01a116ce-ae78-70fb-b079-511789cbaafd', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(486, 'App\\Models\\Partner', 6, 6, 'deposit', 9840000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L83\",\"description\":\"Backfilled house interest (non-broker portion) for loan #83\"}', '01a116ce-ae7b-703a-8544-0891075422ba', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(487, 'App\\Models\\Partner', 6, 6, 'deposit', 120000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L85\",\"description\":\"Backfilled house interest (non-broker portion) for loan #85\"}', '01a116ce-ae7e-705a-8e38-e4d35efead75', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(488, 'App\\Models\\Partner', 6, 6, 'deposit', 9936000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L89\",\"description\":\"Backfilled house interest (non-broker portion) for loan #89\"}', '01a116ce-ae81-7313-a211-be4ac389d23b', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(489, 'App\\Models\\Partner', 6, 6, 'deposit', 5678400, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L106\",\"description\":\"Backfilled house interest (non-broker portion) for loan #106\"}', '01a116ce-ae84-73d1-8fa9-0fa96b6a549b', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(490, 'App\\Models\\Partner', 6, 6, 'deposit', 36000000, 1, '{\"reference\":\"BACKFILL-HOUSE-BRK-L414\",\"description\":\"Backfilled house interest (non-broker portion) for loan #414\"}', '01a116ce-ae87-7269-b2cb-8032ceae40c1', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(491, 'App\\Models\\Partner', 6, 6, 'deposit', 68000000, 1, '{\"reference\":\"BACKFILL-HOUSE-PEN-L1\",\"description\":\"Backfilled house penalty share (50%) for loan #1\"}', '01a116ce-ae8c-73b9-b22f-da4a157b618d', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(492, 'App\\Models\\Partner', 6, 6, 'deposit', 12000000, 1, '{\"reference\":\"BACKFILL-HOUSE-PEN-L17\",\"description\":\"Backfilled house penalty share (50%) for loan #17\"}', '01a116ce-ae9b-7202-98e5-f665cfeb6b44', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(493, 'App\\Models\\Partner', 6, 6, 'deposit', 75600000, 1, '{\"reference\":\"BACKFILL-HOUSE-PEN-L21\",\"description\":\"Backfilled house penalty share (50%) for loan #21\"}', '01a116ce-aea2-710d-a541-a6ac6e584e8b', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(494, 'App\\Models\\Partner', 6, 6, 'deposit', 300000000, 1, '{\"reference\":\"BACKFILL-HOUSE-PEN-L85\",\"description\":\"Backfilled house penalty share (50%) for loan #85\"}', '01a116ce-aebf-7201-96fd-ac4c484ad753', '2026-10-07 12:40:06', '2026-10-07 12:40:06', NULL),
(497, 'App\\Models\\Partner', 5, 3, 'deposit', 8000000, 1, '{\"reference\":\"BACKFILL-FEE-L22\",\"description\":\"Backfilled broker interest share for loan #22\"}', '01a116da-8936-719a-8a6f-3dfde45ea34c', '2026-10-07 12:53:03', '2026-10-07 12:53:03', NULL),
(498, 'App\\Models\\Partner', 5, 3, 'deposit', 4000000, 1, '{\"reference\":\"BACKFILL-FEE-L23\",\"description\":\"Backfilled broker interest share for loan #23\"}', '01a116da-8948-72e7-b3d5-4e75ee79767f', '2026-10-07 12:53:03', '2026-10-07 12:53:03', NULL),
(499, 'App\\Models\\Partner', 5, 3, 'deposit', 40000000, 1, '{\"reference\":\"BACKFILL-FEE-L28\",\"description\":\"Backfilled broker interest share for loan #28\"}', '01a116da-8958-7207-8e6b-2f506a117610', '2026-10-07 12:53:03', '2026-10-07 12:53:03', NULL),
(500, 'App\\Models\\Partner', 5, 3, 'deposit', 24000000, 1, '{\"reference\":\"BACKFILL-FEE-L414\",\"description\":\"Backfilled broker interest share for loan #414\"}', '01a116da-89a5-7084-9719-b3291289dd49', '2026-10-07 12:53:03', '2026-10-07 12:53:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transfers`
--

CREATE TABLE `transfers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_id` bigint(20) UNSIGNED NOT NULL,
  `to_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('exchange','transfer','paid','refund','gift') NOT NULL DEFAULT 'transfer',
  `status_last` enum('exchange','transfer','paid','refund','gift') DEFAULT NULL,
  `deposit_id` bigint(20) UNSIGNED NOT NULL,
  `withdraw_id` bigint(20) UNSIGNED NOT NULL,
  `discount` decimal(64,0) NOT NULL DEFAULT 0,
  `fee` decimal(64,0) NOT NULL DEFAULT 0,
  `extra` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','borrower','broker','teller','partner') NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `pob` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `marital_status` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `disability` tinyint(1) DEFAULT 0,
  `education` varchar(255) DEFAULT NULL,
  `kin_name` varchar(255) DEFAULT NULL,
  `kin_email` varchar(255) DEFAULT NULL,
  `kin_phone` varchar(50) DEFAULT NULL,
  `kin_occupation` varchar(255) DEFAULT NULL,
  `kin_relation` varchar(100) DEFAULT NULL,
  `kin_id_type` varchar(100) DEFAULT NULL,
  `kin_id_number` varchar(100) DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `id_front_path` varchar(255) DEFAULT NULL,
  `id_back_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `avatar`, `phone`, `email`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `gender`, `dob`, `pob`, `nationality`, `marital_status`, `religion`, `disability`, `education`, `kin_name`, `kin_email`, `kin_phone`, `kin_occupation`, `kin_relation`, `kin_id_type`, `kin_id_number`, `signature`, `id_type`, `id_number`, `id_front_path`, `id_back_path`) VALUES
(1, 'Dennis Kibett', 'avatars/HdBHuvf6S6ibGQh84Asue6XvRcgPiRMw68fLaXnC.png', '0717492048', 'kibettdennis@gmail.com', NULL, '$2y$12$2CR8.J2PitDIJoOoZZAfuuvcM0lU9zLj6kLxVksy2RiU7aPySXxQK', 'admin', 0, 'ZN8H0D89dhfkDjwhiAS9tHQGCVrQ5tzHIkpk2NoFYC51I6dufZ8rQpGMp43h', '2025-04-17 11:41:51', '2026-01-27 19:19:09', NULL, 'male', '1994-03-24', NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', 'Joseph Koech', 'josephkkoech@gmail.com', '+254722580928', 'Regional Administrator', 'Father', 'national_id', '0587257', 'signatures/signature_Dennis_Kibett.png', 'National ID', '31425580', 'id_cards/gi21rr49gf83kPyfu5RidoXExHg4ryw7nNVQAx11.jpg', 'id_cards/EUFh2ypgIKD3mVPhDJCRam2qZXKjytJBuU2UWJL7.jpg'),
(2, 'Edward Kibet', '', '+254 710 920629', 'edwardkipsanai94@gmail.com', NULL, '$2y$12$HNUj2oDPc4GNbBKze3XbB.gsZFsEp7W2VOXu.wcKU8AIpBsK9E9jq', 'borrower', 0, NULL, '2025-04-17 11:42:36', '2026-01-22 20:30:33', NULL, 'male', NULL, NULL, 'Kenya', 'single', 'Christianity', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Isiro Agencies', '', '0727088262', 'isiroagencies@gmail.com', NULL, '$2y$12$JMXtud5UquYiEDMC7CRhyOEMvsX70.oUG8s/AsbRu4O285gIGQwfG', 'partner', 0, 'zIK3QLzpq7E11LlGo94aOKJuH3HEllvK1mAhMD6t3TVxwhTOizpOpbzq8Ycj', '2025-04-17 11:43:36', '2026-08-11 15:41:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Isiro_Agencies.png', NULL, NULL, NULL, NULL),
(6, 'BILDAD WAMBUA', '', '0712345678', 'bildadwambua@gmail.com', NULL, '$2y$12$MQaKkMN8wDjjbcbWmAuFGeYl9ZWZXP11b1.jw5JYamJ1nqLPDc8Pq', 'borrower', 1, NULL, '2025-04-17 12:21:34', '2025-04-17 12:21:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Keneth Otieno Owino', '', '0790113034', 'kenowino@gmail.com', NULL, '$2y$12$TxyyVlWqdhMwBK1E2Vc1..GyPQzFRvy7icMJOgJuthUAmB1D/y6pW', 'borrower', 0, NULL, '2025-04-17 13:02:01', '2025-04-17 13:02:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Michelle moraa sese', '', '254793867121', 'michellesese99@gmail.com', NULL, '$2y$12$4VGUFOjrMXOxqpY1i2HNPegukv9eI91aj9JOfikUSwEdyiBu4VQQu', 'borrower', 1, NULL, '2025-04-17 13:12:47', '2025-04-17 13:12:47', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'MARKLEWIS WACHIRA GATUTHA', '', '+254742421530', 'markwachira07@gmail.com', NULL, '$2y$12$Ef0YDy2KjAYZeV7vs2kyU.0nBIV0BMoG8UkTNrX5byblnm2p/txJq', 'borrower', 0, 'CxyThXOGCUwzfLeCjjeeN8QDUJZ2YiN1IfTcJdf8Azboi9ql3gpKTklFv6wy', '2025-04-17 13:22:24', '2025-04-17 13:22:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'CHRISTOPHER EPARA ISURA', '', '+254713985762', 'isuracarhire@gmail.com', NULL, '$2y$12$1/aF94HNoHf2SXwbYh/.xOgdT2qWkkQjNm0uwdX6TSf03pXnDyd.2', 'borrower', 1, NULL, '2025-04-17 13:27:08', '2025-04-17 13:27:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Shadrack Cheruiyot', '', '0719744247', 'shadykipkorir@gmail.com', NULL, '$2y$12$omwtR4JZbfdt/JvbD/i2yubuzZ1lYf2lRnL58fK29kdxiIRYyYOw6', 'borrower', 0, NULL, '2025-04-17 13:29:11', '2026-08-07 18:56:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Shadrack_Cheruiyot.png', NULL, '31586471', NULL, NULL),
(13, 'Dennis Kibet', 'avatars/oOFtsUvlWWTcaQEvdTq5H9z6zK2KKedqq6NZ8iJG.jpg', '+254717492048', 'karleighdeno@gmail.com', NULL, '$2y$12$2CR8.J2PitDIJoOoZZAfuuvcM0lU9zLj6kLxVksy2RiU7aPySXxQK', 'borrower', 1, 'TzqbAq78elhSgKxgTSPHBOPbgR80LEZyUgrKnVNliG5ZirWMOoZk9xJAaLlF', '2025-04-17 13:32:34', '2026-07-16 15:51:14', NULL, 'male', '1994-03-24', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Samson Kiplangat', 'samsontanui25@gmail.com', '0717492048', 'Accountant', 'Brother', 'national_id', '3400000', 'signatures/signature_Dennis_Kibet.png', 'national_id', '31425580', 'id-documents/uYMpJZqnPGTdYbDn4KGq42BodGbLh14GzhtrzzMj.jpg', 'id-documents/5fqj7VAVtdaM1Jt5JbnhHhQNvrZUwoi2rBPbNOaj.jpg'),
(14, 'MOSES OKURUTU BARASA', '', '254726104495', 'mosesbarasa@gmail.com', NULL, '$2y$12$STSR.E/TpRR86bXF3Jdo3OXldtd7YAPk183NisR1HD7eRbXB6.54G', 'borrower', 0, NULL, '2025-04-17 14:07:25', '2025-04-17 14:07:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Samson Kiplangat', '', '0701607959', 'samsontanui25@gmail.com', NULL, '$2y$12$pBYO6eGOW1r2bgxqG9ePJ..06DxMb1bZGeJ/UVp7DtAaB8bO2Ngya', 'borrower', 0, '6g4lcvyGHhKnziD43gRsHznruRgY5FHGEwDCXgc0E24K2FoWScT5hPKDZGPS', '2025-04-17 14:20:54', '2025-12-27 08:12:41', NULL, 'male', '2025-05-21', NULL, 'KE', 'single', 'Christian', 0, 'Master\'s Degree', 'Joseph Koech', 'josephkkoech@gmail.com', '254722580928', 'Business man', 'Father', 'national_id', '0587257', 'signatures/signature_Samson_Kiplangat.png', 'national_id', '34418665', NULL, NULL),
(16, 'DENIS LEVIS NGIRA', '', '+254721381582', 'ngirangira@gmail.com', NULL, '$2y$12$XZ8sfnO/wYVqOo6AApuyxOMsfDHGBaIgT2IhHtCA.DyN6GGFWQoS.', 'borrower', 0, NULL, '2025-04-17 14:24:34', '2025-04-17 14:24:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'LINNER JEPKEMBOI KOECH', '', '0720540112', 'ljkoech@gmail.com', NULL, '$2y$12$ORh0wj9X0ste0XVVc5wa3edhnpkCgZrpqURDczj5t0OLsGU32dX4u', 'borrower', 0, NULL, '2025-04-17 14:29:23', '2025-04-17 14:29:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'Stephanie Kanyeki', '', '+254727088262', 'stephaniekanyeki@gmail.com', NULL, '$2y$12$5hzR.H/wPVoMr9Qz95VW7u.L6lvnIbe.mr5xd7xumiMD7uJjz3b0W', 'borrower', 1, NULL, '2025-04-20 10:39:32', '2025-04-20 10:39:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'CFO County Busia', '', '0700123456', 'busiacfo@gmail.com', NULL, '$2y$12$h896m0HMnTjHiXNj5JiIvOkh7EDLFqpmn9iYs0aqoD7BJeY0NCN16', 'borrower', 0, NULL, '2025-04-20 17:14:25', '2025-04-20 17:14:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'JOSEPH KOECH', '', '0722580928', 'josephkkoech@gmail.com', NULL, '$2y$12$FwGXheD3OexInOl/tWVxluF4rJDTh6l2loNnD5I0OIYlpQ//0NQ/C', 'borrower', 1, NULL, '2025-04-20 17:45:22', '2025-04-20 17:45:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'David Ihaji', '', '0717465550', 'davidihaji@gmail.com', NULL, '$2y$12$tgVICqfy81Ab75UjvFirCOe3sl5H74yR9fhhSXTKMGpscRpeEt/Jy', 'borrower', 1, NULL, '2025-04-22 13:34:37', '2026-05-08 07:20:14', NULL, 'male', '1995-04-23', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Peter', 'nyenzo@gmail.com', '0796952247', 'Tech', 'Brother', 'national_id', '31930122', 'signatures/signature_David_Ihaji.png', 'national_id', '31930121', 'id-documents/0MftMeNEPFqhlUSs5aH8WOjRH4gZo7by4J9RHDRD.png', 'id-documents/Ay2f4tFEcT9J0qje0Gm7wbDMqqS9YbTB1YvtAJtY.png'),
(22, 'Justine Omori', '', '0721855878', 'justineomori@gmail.com', NULL, '$2y$12$WklPbJc9w7aDMncNznJ70.ikODU0icUKLaBr5a6WBn0yb0l6PR2ia', 'borrower', 1, NULL, '2025-05-07 12:10:19', '2025-05-07 12:10:19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Joseph', '', '+255758556562', 'lamearkaaya@gmail.com', NULL, '$2y$12$oRdMr9kL9rqxBJK0v3TQv.pvflSdVOwgwUtCmMaG.CY7c0GlCCE0i', 'borrower', 1, NULL, '2025-05-08 10:53:11', '2026-09-03 07:21:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Joseph.png', NULL, NULL, NULL, NULL),
(24, 'Steve Omwenga', '', '+254 727 665808', 'steveomwenga@gmail.com', NULL, '$2y$12$1sd9TZHAnikJzo9u5IQLOOubmQ0AFrlyoafY9MLajVN3XHtMFRaHK', 'borrower', 0, NULL, '2025-05-13 06:49:55', '2026-09-18 14:03:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Steve_Omwenga.png', NULL, NULL, NULL, NULL),
(25, 'DOREEN NTARANGWI', '', '+254710438971', 'doreenntarangwi@gmail.com', NULL, '$2y$12$z6QRzg8ktp/YWvd33nvhYeUQxv2YB109l3gZjpElykM/.o5WxgX7u', 'borrower', 1, NULL, '2025-05-15 08:08:59', '2025-05-15 08:08:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'Ann Njora', '', '+254 722 596985', 'kirajsupplierskenya@gmail.com', NULL, '$2y$12$JTGPNwFVmH3kFCQffkVLlePz0/qaFV7c7JsSSVnujmHI/XYtxLga6', 'borrower', 0, 'WU70YAjW4SXP4j2m4Ys6nM1C91lEpQzULs442bpFhR02gmD06yUatuxFUXYN', '2025-05-19 12:45:53', '2026-01-28 11:58:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'JOSEPH KINGORI WAMBUGU', '', '+254 714 390696', 'josephkingori@gmail.com', NULL, '$2y$12$8cGto1IdjyPNIiM0HN48IuRmUHcarG8rjmGqXVaDMJhlkvomvO10O', 'borrower', 0, NULL, '2025-05-20 13:03:03', '2025-05-20 13:03:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, '28539464', NULL, NULL),
(28, 'Njeri Nduati', '', '0720517386', 'njerinduati@gmail.com', NULL, '$2y$12$C.r6Fgc1lc8xmkJtTWMTe.r/UR2qhW5S6qohBouDgOKU3d9Oo.lHe', 'borrower', 0, NULL, '2025-05-25 18:36:15', '2025-05-25 18:36:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '29753954', NULL, NULL),
(29, 'Nigel Cecil Otieno', '', '+254 721 655906', 'cecilmash@gmail.com', NULL, '$2y$12$5Sbd9JS17UvgL6rgTh4dwO7ciB./oJOHau9jtLxUDWGqRyTKuscFC', 'borrower', 0, NULL, '2025-05-27 13:47:29', '2025-10-29 07:03:27', NULL, 'male', '1985-03-25', NULL, 'KENYAN', 'single', NULL, 0, NULL, 'Barry Macharia', 'barryblacks@gmail.com', '0720899750', 'Businessman', 'Brother', 'national_id', '22445566', 'signatures/signature_Nigel_Cecil_Otieno.png', 'national_id', '24011567', 'id-documents/mZ0nHUoGwxYUruZ1ioNvZJpnm9JUY82EFqTF6OPK.jpg', 'id-documents/2uoCUuOyIDQCAenJLrPuH1gbgfLdizW83cdyknWi.jpg'),
(30, 'Ian Kipkorir Cheruiyot', '', '0710911168', 'iankcheruiyot@gmail.com', NULL, '$2y$12$QUvaA6TvKXP1xf3/3GU00.TGelMgTOAVPml2Vljx1chW/2CGYU0gG', 'admin', 1, NULL, '2025-05-29 05:14:33', '2025-05-29 05:14:33', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Leon Musau', '', '0720747652', 'musau.mumo@teflontradingltd.co.ke', NULL, '$2y$12$WU2gPKt9m5qTKZPhoPzUIeEkyTMPKK3QCUwB.2rwe46cBJsQif1/G', 'borrower', 1, 'VH3X52MKGcLon0bybPJmawH46jKpP4YatLdpyC57HBhYGpSaVLOiOqkKZao0', '2025-05-29 13:47:36', '2026-02-09 17:16:38', NULL, 'male', '1991-07-25', NULL, 'KE', 'single', NULL, 0, 'Master\'s Degree', 'Micahel Musau', 'mukikiilumusau@gmail.com', '+254721374196', 'coffee trader', 'Brother', 'national_id', '32313915', 'signatures/signature_Leon_Musau_1770660998.png', 'national_id', '32313898', 'id-documents/xACFanBfJtr4nUIOMm4XGMtsv5gbiWhIqksinlaL.jpg', 'id-documents/OsxovFf5SSZznQOKEmA78jEmMU2QwbXNaMFgF43p.jpg'),
(32, 'Brian Kiprop Kiprono', '', '0720098561', 'briankiprop@gmail.com', NULL, '$2y$12$GxQC0YyruW8LgBwdU7O8zuhxDjvsCzRtM8qoq34LZkD8fWQlK482O', 'borrower', 1, NULL, '2025-06-02 11:17:53', '2025-06-02 11:17:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'Alan Kipng\'etich Limo', '', '0705596198', 'limoalan@gmail.com', NULL, '$2y$12$TZ3mZ4LycemIe6Urg8X2oeYfD6Bdk6VsXnLR6sCNvzKSE15soyHFa', 'borrower', 0, NULL, '2025-06-14 10:26:20', '2025-06-14 10:26:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '31667416', NULL, NULL),
(34, 'MOLUK AWAD', '', '0725833011', 'molukawad519@gmail.com', NULL, '$2y$12$dozzobeZBqXkor14yj8lJuRuHOH601FKA9iI4yB48hpdNg1UZjMw2', 'borrower', 0, 'A2jaRU9ng7dGHKMdOdPHcXcAvl1HSiuWmxTaEaDsnJ0lGWqSwyDPoxxffxuu', '2025-06-15 13:55:59', '2025-06-15 13:55:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'Lewis Kimutai', '', '0725697837', 'leuville6@gmail.com', NULL, '$2y$12$VQsSkFy5bS.wpUElVPh8y.n7D2WKd.1DQlAgS1AOg8kz1Ua5tZgRC', 'borrower', 0, NULL, '2025-06-20 18:30:23', '2025-06-20 18:30:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'Newton Wesonga Okumu', '', '0703654828', 'newtoncarloz@gmail.com', NULL, '$2y$12$Wxj00avSUmFTTVsquTqRouBQY0.akLqfj.Nl.czNQ7ZlmFbYILDnm', 'borrower', 1, NULL, '2025-06-21 06:15:40', '2025-06-21 06:15:40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'BRIAN KIPLANGAT', '', '0706099588', 'briankiplangatbk@gmail.com', NULL, '$2y$12$ArbwFMHaiL/rCrXnx.VHounCl/3rOUbPO2OwRJYu9ErQVDRsCU5/S', 'borrower', 1, NULL, '2025-06-23 07:43:27', '2025-06-23 07:43:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'HONOURINE NZISA MUTUNGA', '', '+254729080190', 'honourinenzisa@gmail.com', NULL, '$2y$12$nP6kWDrHZrMx6A3GkycO5.VNP7S.f3CoHmpUlcCJgRv3zs9FeE4K6', 'borrower', 0, NULL, '2025-07-07 08:51:03', '2025-07-07 08:51:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'Faith Chepngetich', '', '0700701380', 'chepngetichfaith981@gmail.com', NULL, '$2y$12$12yyIb3Y0op09RDuG.GBn.U2NYXkp.9.Y2xCmmXrcccZsfZTfTrA.', 'borrower', 0, 'G3w4zV027oUDqDFWomDWq2YgILmyVHttMBQS9wnsa3Cb38tclTkT21umNDgQ', '2025-07-13 16:11:22', '2025-07-13 16:11:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'Ian Otieno Okoth', '', '0719795614', 'iansandootieno@gmail.com', NULL, '$2y$12$bfTyyCGeKrzCEtU1WQZh5em967lE2kU0fLRvQ8jgPCzgDOxvy.llC', 'borrower', 1, NULL, '2025-07-14 11:35:57', '2026-08-31 13:29:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Ian_Otieno_Okoth.png', 'National ID', '38705774', NULL, NULL),
(41, 'Cynthia Wanjiru Muhia', '', '+254 717 459536', 'cynthiashiro78@gmail.com', NULL, '$2y$12$bO3E9MoR5kXMmYxKfZ1IJ.JU8NgFR283MNCG/lIiiZ3KOWza0O4UC', 'borrower', 0, 'mbWJ31V0W1EcyI5A0eckNUDTEbTgk92YCbihO5jmtUcZVlroH33jUU77JWbr', '2025-07-15 10:00:13', '2026-09-14 07:37:51', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Cynthia_Wanjiru_Muhia.png', NULL, NULL, NULL, NULL),
(42, 'Francis Kabutu', '', '0728167511', 'kabutu.frank@gmail.com', NULL, '$2y$12$VknLy1ceuXycC3ZgvpjDfum6b6FNMMHiIq6YT6CuMDGhLPYesmPm6', 'borrower', 1, NULL, '2025-07-17 10:52:30', '2025-07-17 10:52:30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 'Brian', '', '0717503595', 'gachugubrian8@gmail.com', NULL, '$2y$12$GNKcltFvv5DmYlB6yGtf6.iYjL4fx5FKbT.oEigQktpAxOvTgsw52', 'borrower', 0, NULL, '2025-07-28 06:05:53', '2025-07-28 06:05:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'Arnold Maina', '', '+254724574681', 'arnold.x.maina@gmail.com', NULL, '$2y$12$dXOkpwX5CB9Ft2iaL6lUYOEqhex5utpQBJbhXrtEbHsIsb/7qsVjC', 'admin', 0, NULL, '2025-08-06 05:11:15', '2025-08-06 05:11:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'Ruth Sudoi Makalla', '', '+254 721 440306', 'rsmakallah@gmail.com', NULL, '$2y$12$EHzV094qb9bZ88BvsxYkLuiccJ8ZUc4AxXGudlWvhGQ6Y423Wk9.O', 'borrower', 0, NULL, '2025-08-06 11:25:20', '2025-08-06 11:25:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'Michelle Muriithi', '', '0732792642', 'michthi.mm@gmail.com', NULL, '$2y$12$JjG3FMRdhKucKdYVblAOouvAREuzGHoB4aLLgIFwlZYZ1CUqh8oHS', 'admin', 0, NULL, '2025-08-12 14:29:27', '2025-08-12 14:29:27', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 'Kelvin Keter', '', '0727025050', 'kipchirchir101@gmail.com', NULL, '$2y$12$8QZw5VeEAiX6E2GIu6M5UOFAQswu0TAER1ht22HcLvTvHJ3UetubC', 'borrower', 0, NULL, '2025-08-14 07:30:39', '2025-08-14 07:30:39', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'Sharon Chemurgor', '', '0704815115', 'chemurgorsharon@gmail.com', NULL, '$2y$12$lIKTaV/PMy9/MbWW55kO7eKfVeHmygm3Ug9uyg8B6NzGd8FMg9Hdy', 'borrower', 0, NULL, '2025-08-19 14:58:14', '2025-08-19 14:58:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'Peter Tanui', '', '0701134508', 'thetanlit6@gmail.com', NULL, '$2y$12$tjpGbnZbXxtIIv53nEp4L.d.bcEoXsfhNXJToCiXtYTP669H0pVw2', 'teller', 0, 'aX6h8TUgu4woLFSU8Vncbfr43G0J4ZRcd3omSLIRZqXH0eYKSfRSDTeY5ADj', '2025-09-02 07:51:46', '2025-10-15 10:45:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Peter_Tanui.png', NULL, NULL, NULL, NULL),
(50, 'Mohamed Abdirahim Abdi', '', '0721544928', 'mohaabdi41@gmail.com', NULL, '$2y$12$CVQWIt6FGp..1WoA8tbBL.hnoxaDIDhFrVhHoZtjaUEoOKiGsXZZi', 'borrower', 0, 'Av2fj49VRHgKc6WUG6hEivJXyLIw1XCKCMp1yQFDQVazAmaXxll8bzPjSKsk', '2025-09-10 06:39:51', '2025-10-27 10:13:24', NULL, 'male', '1993-04-20', NULL, 'Kenyan', 'single', 'Muslim', 0, NULL, 'TAYIB haithar', 'mohaabdi41@gmail.com', '0723597683', 'Businessman', 'Brother', 'national_id', '36827361', 'signatures/signature_Mohamed_Abdirahim_Abdi.png', 'national_id', '36827364', 'id-documents/DNLFpZa2hXxTmhI7GUJSaxXby9KkNCqMm5Sel8rU.jpg', 'id-documents/VQnXyNlVCCJpKKz74z6CPnGufSVqrALa9Pwl81YR.jpg'),
(51, 'Kelvin', '', '0726471918', 'c.kelvinrotich@gmail.com', NULL, '$2y$12$Lu9zm/hSdT2PT08yOzz.9.yz/ZqEjg3UAEg.q1Vx5VLkEDmS5.1s.', 'borrower', 0, NULL, '2025-09-14 12:24:32', '2025-09-14 12:24:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 'Marion Lewenei', '', '0705254257', 'leweneimarion@gmail.com', NULL, '$2y$12$4/HAJTqnsZ/.QW/BRleIsuS.jT.rvb3tLqyE1aFfyAsZvA8awbMbO', 'borrower', 0, NULL, '2025-10-01 09:11:31', '2025-11-21 05:42:00', NULL, 'female', '2025-05-15', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Maureen Mathithi', 'irenejunnie@gmail.com', '+254705254257', 'businessperson', 'Business Partner', 'national_id', '36340048', 'signatures/signature_Marion_Lewenei.png', 'national_id', '40543156', NULL, NULL),
(53, 'EMMANUEL Ruwa Tsuma', '', '0768384462', 'emmanueltsuma19@gmail.com', NULL, '$2y$12$FdDOhmJWdlP4u0JRcT3sBOftNNL2Rl0MPGUNz1Q1Q6h0/SIY02bmm', 'borrower', 1, NULL, '2025-10-03 06:04:44', '2026-03-06 10:05:01', NULL, 'male', '2000-06-27', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Ronald ruwa', 'ronaldtsuma21@gmail.com', '0705110637', 'Human resource officer', 'Father', 'national_id', '8435579', 'signatures/signature_EMMANUEL_Ruwa_Tsuma.png', 'national_id', '38276910', 'id-documents/YE5aPHeTXZaDSZagkvH8rPOs5bNyYR5WkK9sebFZ.jpg', 'id-documents/u1AdJo50lkrTAohkTQcS3q2QuLLrYQoLVnrLCnQI.jpg'),
(54, 'Isaac Ngugi', '', '0711499655', 'isaacngugibaker@gmail.com', NULL, '$2y$12$/IyJDyyBqHgyN10RhOw/u.oaFnjcibWlI3dgu7/uFyo7kxIjCvvme', 'borrower', 0, NULL, '2025-10-08 04:06:13', '2025-11-04 03:59:42', NULL, 'male', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Isaac_Ngugi.png', NULL, NULL, NULL, NULL),
(55, 'Lawrence Kipngetich Byegon', 'avatars/l3VJhlbbZj8ipDk8RnLcF6y40LExyflK4TomEmUL.jpg', '0722778422', 'lawrencebyegon@gmail.com', NULL, '$2y$12$RDyjh/WTwbe2EumZLmeMMO3QApL/QXqDlv8srp.WzycTAOdOJdpOS', 'borrower', 1, NULL, '2025-10-23 05:37:11', '2025-10-26 07:54:26', NULL, 'male', '1994-05-01', NULL, 'Kenyan', 'married', 'Christian', 0, NULL, 'Mildred Asili', 'mildredasili21@gmail.com', '0717633016', 'Businesswoman', 'Business Partner', 'national_id', '38353237', 'signatures/signature_Lawrence_Kipngetich_Byegon.png', 'national_id', '31757677', 'id-documents/naw4em7mhBqObFUwTZIbCkCkoPMSgK3cgCHvMTEg.jpg', 'id-documents/yIAOFuUMIkIpqPT71ITNriTVI5C76L6M0XuKBGCn.jpg'),
(56, 'Francis Ndungu', '', '0742386797', 'frankietheuri@gmail.com', NULL, '$2y$12$9qYfC4yjURl5XAjRDxPavOm5ooZdf4NPZBdXlWG44MOuKErGKbC/i', 'borrower', 0, NULL, '2025-10-24 03:31:10', '2026-02-01 19:45:48', NULL, 'male', '1996-10-05', NULL, 'Kenya', 'single', 'Christian', 0, NULL, 'Julie Wanjiru', 'julie.wanjiru1@gmail.com', '0114204599', 'Logistician', 'Sister', 'national_id', '30146007', NULL, 'National ID', '33103892', 'id-documents/kkzoNCDiAeejsCYixVoQ7PH3tR7cjZdQFaZ1zFVo.jpg', 'id-documents/djfdMdje61nUNps5Q6anDSyxBH13hf0eEF7hND18.jpg'),
(57, 'Douglas Kipchirchir Yegon', 'avatars/1pU9bhJ1n7D1pOchC8HX3sjPjyQvqQYROnL8cGZe.jpg', '0701849455', 'yegondouglas@gmail.com', NULL, '$2y$12$N.ac.IwoRAc/ahSex9kMkeu/yqbq8UwDiq/wxXUWZ8nfAOQbscLPe', 'borrower', 0, NULL, '2025-10-24 09:49:49', '2025-10-24 13:12:24', NULL, 'male', '1996-01-04', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Elvis Kipkoech Yegon', 'elviskyegon@gmail.com', '0717752055', 'Businessman', 'Brother', 'national_id', '0734970', 'signatures/signature_Douglas_Kipchirchir_Yegon.png', 'national_id', '32507485', 'id-documents/EMC78WWzte1K4r4SUGczhNcnZEzZ3tRSBnEydcAO.jpg', 'id-documents/OpYuy3P29xfoGaqZ5sSH5aJLjGaC5KUcPtIYHmlS.jpg'),
(58, 'Vivian Simiyu', '', '722778298', 'viviansimiyu77@gmail.com', NULL, '$2y$12$8bYJi.ZeyquI/Xcp87hiuOncTWxq4YXHcLQ/iL/41JIwj1Gryri0i', 'borrower', 0, NULL, '2025-10-25 13:22:18', '2026-04-27 18:32:52', NULL, 'female', NULL, NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Vivian_Simiyu.png', 'National ID', NULL, NULL, NULL),
(59, 'Justin nyarindo', 'avatars/1jLvUxRf2uVvqvNN7EgmR3hAKMVz99hRceMqMGeY.jpg', '0796165555', 'aguilanmilano@gmail.com', NULL, '$2y$12$DHM3FZFQQPbaRPchxlxifOZC9iXDb8yfWiK8Z38b6OfZXSmL5KEg2', 'borrower', 0, NULL, '2025-10-27 13:40:41', '2026-02-02 13:16:40', NULL, 'male', '1996-07-03', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Stella nyarindo', 'stelanyarindo@yahoo.com', '0723396150', 'Secretary', 'Mother', 'national_id', '15247846', 'signatures/signature_Justin_Nyarindo.png', 'national_id', '34760573', 'id-documents/dWt8NmMfqdXOtIIoibeyQMbeNemkVBfQ0o4nE5og.jpg', 'id-documents/ABumIl2Hs09QcHVrYKMsmTRiIPpQjUkoYvDaEy8T.jpg'),
(62, 'Douglas Lutomia', '', '0799918736', 'douglaslutomia13@gmail.com', NULL, '$2y$12$rpm9tsP1K9SgQytf3biZQ.jTFR5uS2yflzOMWfMcveQ5Y.bqZvDh6', 'borrower', 0, '5uuAXM5Bb58xcg32nyteRR1qpwcMQAutd2JAEx0zi54Sb8x9Lvz38qwwHsda', '2025-12-22 10:11:02', '2026-02-03 05:07:58', NULL, 'male', '1997-06-03', NULL, 'Kenya', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Eric Odhiambo', 'ericodhiambo@gmail.com', '0718664393', 'Dog trainer', 'Brother', 'national_id', '28472342', 'signatures/signature_Douglas_Lutomia.png', 'national_id', '33555254', 'id-documents/6VgExuInfnnT9taPNmQejLcRNq8HZxI450CiisF9.jpg', 'id-documents/m4c2eTCLo1Uj6RZeQ6UyzfFXORYImZLSZY09VDLH.jpg'),
(63, 'Morris Hafare Segelan', 'avatars/PVeaenxfq4ubbtlwSeO7GXvl3dbz5Le4PzVmoB4I.jpg', '0716893824', 'habibati97@gmail.com', NULL, '$2y$12$XYydzEfQvPnIIxNRdbQPeexqnbjdr2/.P4wXT0FFcmK/F7FOBCMuS', 'borrower', 0, 'Fz55akxGU6m5JRbELmDQfM7t55BD0Rv83lT6HGQRXdGze68jU36M4SvBxejf', '2025-12-22 10:31:24', '2026-09-16 07:46:51', NULL, 'male', '1992-09-22', NULL, 'Kenya', 'single', 'Christianity', 0, 'Master\'s Degree', 'Cliff Halowa Segelan', 'cliffsegelan@gmail.com', '0701737592', 'driver', 'brother', NULL, NULL, 'signatures/signature_Morris_Hafare_Segelan.png', 'National ID', '29615142', 'id_cards/9TNOVyJcXpKLOptdbqkak889nDLVvfNIDuwyh9X0.jpg', 'id_cards/LDkmrg2DOMc7W28TvtcBWGfghsOPkijYYQ4OfBag.jpg'),
(64, 'Judy Kerebi', '', '0741 960 917', 'judykerebi23@gmail.com', NULL, '$2y$12$KS9qsb9J9YPaFwvJc34SMesmXKyRq17SaL8gnzN0H/Q/trFazHWhu', 'borrower', 1, 'bmdTBflv37S49rppyYH4VM30PeNdc4nO8aKFdHvsyNWQ72ULwFMsXpj71TmB', '2026-01-20 09:03:36', '2026-04-11 09:41:21', NULL, 'female', '1999-02-09', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Farida Kebaso', 'faridakebaso1@gmail.com', '+254718534504', 'Business', 'Business Partner', 'national_id', '42191432', 'signatures/signature_Judy_Kerebi.png', 'national_id', '36138998', 'id-documents/kuPygKRuhL6oj6jqkF69AQeDwy4b69DykWwx4nUg.jpg', 'id-documents/xtp2kK9k157Np83ZMRQ2unxYSdzPzTmAK82vMycX.jpg'),
(65, 'Hesbon Kiprotich Kerewo', 'avatars/Dw2FhaoXteB6YjHy4fzIxvLxX5me5wLGsm8xT91x.jpg', '0706077705', 'hezkerewo@gmail.com', NULL, '$2y$12$NT4nA1qQn24bsrfJWeykd.gOJYuPwOVL64PMYP7iK7bchPDk.zA8C', 'borrower', 0, 'UO8Fx3w8IPnIAVVJ4ZhjpWnZ7WBKNzeRfOVyH9DYW0KPKCPI1hXeni9Hd7DQ', '2026-01-21 06:27:38', '2026-02-02 13:32:56', NULL, 'male', NULL, NULL, NULL, 'married', 'Christian', 0, 'Bachelor\'s Degree', 'Tony Kimosop', NULL, '0794864738', 'Business man', 'Brother', 'national_id', '30317319', 'signatures/signature_Hesbon_Kiprotich_Kerewo.png', 'National_Id', '30319319', 'id-documents/gb4dwWWVxiSqhSCvqdYvuJRwx7JUK3oxjzqhCE2F.png', 'id-documents/BDiGmMGsxQHGk6K9aCEELSqEepQFNxMkA5CIKlIT.png'),
(66, 'Jacob', '', '0791250828', 'jacobgimachombe@gmail.com', NULL, '$2y$12$sDuHMFS3QUNPrqY6frCOlOT0BmlvsCjEhO2B0Za4mfAGGtp5VF4RK', 'borrower', 0, NULL, '2026-01-21 07:15:44', '2026-01-21 07:35:13', NULL, 'male', NULL, NULL, NULL, 'married', 'Christian', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Jacob.png', NULL, NULL, NULL, NULL),
(67, 'Michael Nzuka', NULL, '0700742393', 'musyiminzuka3@gmail.com', NULL, '$2y$12$TyQr75ASmFeVbqXtdtCvIepyt45m2U4UXrpOF7v71/VgSoSmYtYGC', 'borrower', 0, NULL, '2026-02-18 08:27:11', '2026-02-18 08:50:25', NULL, 'male', '1994-11-04', NULL, 'Kenya', 'single', 'Christianity', 0, 'Master\'s Degree', 'Grace Musyimi', 'gracemusyimi72@gmail.com', '+254716815488', 'Policies and Governance', 'Sister', 'national_id', '35658056', 'signatures/signature_Michael_Nzuka.png', 'National ID', '31529341', NULL, NULL),
(68, 'Deborah', NULL, '0791733405', 'deborahmurgor7@gmail.com', NULL, '$2y$12$aAEBH17kgQP9w6dCjtev/eHuQtWNAlUX6YwMsGXTo556LoPkJpLBq', 'borrower', 0, NULL, '2026-02-18 08:56:28', '2026-02-18 08:56:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 'Ronald Kemei', NULL, '+254714995946', 'kemeirnld@gmail.com', NULL, '$2y$12$euP7hNWhVfQ3m7Fr4vGAXuiL7REKowJlxqZKLmC2iDkzCFQZCvmvW', 'borrower', 1, 'xOcQhyfWId9jkeLqjmj8cg2w8AT4vYth4dCJ34a9h9lh8zNZoBYrzzyJOEMd', '2026-02-18 14:51:36', '2026-02-19 10:09:08', NULL, 'male', '1990-05-12', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Bethwel Kiprono', 'felixmaru@yahoo.com', '+254 790 819905', 'Business/freelancer', 'Business Partner', 'national_id', '29480845', 'signatures/signature_Ronald_Kemei.png', 'national_id', '29762505', 'id-documents/zMn7oIMMtSc1CSuwDDc2vunirYzWFYWbaz2XKuR4.jpg', 'id-documents/hJetnNYA1fjKx321pOBe7eUA0TEuUCAq7zazvI0b.jpg'),
(70, 'Nigel Kimutai', NULL, '0725408209', 'nigelkimutai@gmail.com', NULL, '$2y$12$tRL3hw45tFpda00vVb4xB.vdQIG3kn9LoDmGfZVB67/1TQ3v9DFjK', 'borrower', 1, NULL, '2026-02-23 13:47:01', '2026-02-23 14:09:18', NULL, 'male', '1994-03-23', NULL, 'KE', 'single', NULL, 0, NULL, 'Prisca Choge', 'priscachoge@gmail.com', '0720978776', 'Business', 'Mother', 'national_id', '30953061', 'signatures/signature_Nigel_Kimutai.png', 'national_id', '30953061', 'id-documents/1MNy81qcXk0NFSHa1lWdKQoupc6qrj877ecKALMV.jpg', 'id-documents/BoT1TvQ4J39KP2Y2CjT6oz3QqT75L8CwgmazR1Qa.jpg'),
(71, 'Yvonne Jemutai', NULL, '0701986999', 'yvonnejemutai3967@gmail.com', NULL, '$2y$12$dxZg4KvdGlZyZnB.nQIIl.JPwe34GxL3dxbXHKZIjKOyUmbrqtNj6', 'borrower', 0, NULL, '2026-03-06 04:57:07', '2026-03-07 11:53:44', NULL, 'female', '1996-10-28', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Vicky Jelagat', 'vickyjelagat@gmail.com', '254757869890', 'Pharmacist', 'Sister', 'national_id', '38086262', 'signatures/signature_Yvonne_Jemutai.png', 'national_id', '33246104', 'id-documents/FECg9du15dXA9E1rgjetCVbap6wPmPJRZqKpONHb.jpg', 'id-documents/nGLk6DhAwWnc5HXZkamQsShN0C5HduhVCrlwobfK.jpg'),
(72, 'Teresa waitherero Ndirangu', NULL, '0745878281', 'teresawaitherero@gmail.com', NULL, '$2y$12$O8X6ZphiU339Q7iGcq5YC.58i9gm8hVAKC/L.YPOccS/yT9CEAecC', 'borrower', 0, NULL, '2026-03-16 09:02:56', '2026-03-16 09:02:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 'Anita Soina Nanyokie', NULL, '+254 708 530169', 'anitasoina@gmail.com', NULL, '$2y$12$kwl.yLpYwf08AlvQKx3wi.nC3WxXqGELI1SMESpNvDxpJBUdHHOHe', 'borrower', 0, NULL, '2026-05-05 09:59:58', '2026-09-14 14:08:56', NULL, 'female', '2000-01-03', NULL, 'KE', 'single', NULL, 0, NULL, 'N/A', 'na@gmail.com', 'na', 'n/a', 'Work Colleague', 'national_id', 'n/a', 'signatures/signature_Anita_Soina_Nanyokie.png', 'national_id', '37489362', 'id-documents/63Hty7l8SaxdzjAbO0daOdLLNxxPn0JVXByGKazP.jpg', 'id-documents/AYRz7eKjKxgnYIQJ4NkaRXgPGRGgePUs0K7j7g9P.jpg'),
(74, 'Diana Jerotich', NULL, '0728688805', 'jerotichdiana2@gmail.com', NULL, '$2y$12$7rAzZk9UhQYCKHCAwsP3muU7hQ4uB8VDeFLKvfJKYlSRQvEEUL0XS', 'borrower', 1, NULL, '2026-05-11 05:17:56', '2026-05-11 05:37:05', NULL, 'female', '1998-07-14', NULL, 'KE', 'married', 'Christian', 0, 'Bachelor\'s Degree', 'Lydiah Chesang', 'lydiahchesang36@gmail.com', '+254710762005', 'Business person', 'Sister', 'national_id', '34651360', 'signatures/signature_Diana_Jerotich.png', 'national_id', '35591135', 'id-documents/iUVocFrbx2gYfCvCuvMj8RcDvoe1AhvenEZiuJCF.jpg', 'id-documents/vdvrSDgZlcHNljjSUm6gXKGMyAnFElwFmKUI2ov5.jpg'),
(75, 'Dennis', NULL, '0728064636', 'dennisosoro44@gmail.com', NULL, '$2y$12$kU4R7uBGobd2E7YFos25IO7yp2KxLWPRYGoBxSh3froU1injX49vm', 'borrower', 0, NULL, '2026-05-11 06:03:12', '2026-08-11 15:53:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Dennis.png', NULL, NULL, NULL, NULL),
(76, 'Lucy Kerubo', 'avatars/aY8NS1LiAjdRdxrYzp9rYS13eWiXk8UHTXxvbxQs.jpg', '0113978372', 'kerubolucy106@gmail.com', NULL, '$2y$12$YoSLFO/B3lsNBXGlvraqoOiVBCnuijJ48BEgzqNQ2C.bsPvLb.CqK', 'borrower', 0, NULL, '2026-06-20 08:55:31', '2026-06-20 11:41:31', NULL, 'female', '2005-07-18', NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', 'Elizabeth Mutinda', 'lizanzisah@gmaail.com', '0718489101', 'Employed', 'Aunt', NULL, NULL, 'signatures/signature_Lucy_Kerubo.png', 'National ID', '178556676', 'id-documents/37WUH08TYXmytvwFSi70LMU8IIaOtgteeIrnVmdu.jpg', 'id-documents/moJHBkiivtK6v9806rJOMDQCcoKvZdlYkY3xj0uC.jpg'),
(77, 'Nicholas', NULL, '0717758500', 'nicholuskamau172@gmail.com', NULL, '$2y$12$wZ1.motCkgnKRTkDzGDdHebltzsn0pt77DcfR9ablPwlVEL5IITla', 'borrower', 0, NULL, '2026-06-24 10:24:46', '2026-06-24 10:24:46', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(84, 'Brian Tanui', 'avatars/fJ5FgzIMRomhfS2e4kBp7KiDzfuDpA3VGYv96xx3.jpg', '0729887927', 'briantanui2030@gmail.com', NULL, '$2y$12$seelGyySijUTwC9HpOMmbev5.YVM89vYnMgYHG2PfKehs3eaRJIJm', 'borrower', 0, NULL, '2026-06-26 14:40:16', '2026-09-29 14:04:40', NULL, 'male', '1996-10-31', NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Brian_Tanui.png', 'National ID', '35738540', 'id-documents/laIoXY9XLu3uvQL6opz9qXmZeukDE3TnvYCrsPth.jpg', NULL),
(91, 'Kipngetich Kevin', NULL, '0727371496', 'kipngetichkevin97@gmail.com', NULL, '$2y$12$FGBQ2dAV9KYU9QEKfq45Ku0w999YbU4ozhe7XIR/5avlbxYUy6krO', 'borrower', 1, NULL, '2026-07-16 13:04:57', '2026-07-16 15:00:25', NULL, 'male', '2026-06-10', NULL, 'KE', 'married', 'Christian', 0, 'Bachelor\'s Degree', 'Tyson kipngetich', 'kipngetichkevin94@gmail.com', '0712065427', 'Self employed', 'Brother', 'national_id', '34547480', 'signatures/signature_Kipngetich_Kevin.png', 'national_id', '34527481', 'id-documents/b8HcU1dc8LAQzlwotgKCalcgADIXfb2wddxrhrbc.png', 'id-documents/SLUTQb2LDl4XOjSOgG9waoi5CYSvIxrKyGIYZ6Ne.png'),
(92, 'Brian Chepkwony', NULL, '0704938645', 'briancheps3@gmail.com', NULL, '$2y$12$1dhhC1xRW7Sm3gAWDfZszuQZ1XRaBveJ3m/D2Y/3Bni2oPCAQ6rFq', 'borrower', 1, NULL, '2026-07-27 10:50:00', '2026-07-27 11:35:54', NULL, 'male', '2026-06-21', NULL, 'KE', 'single', NULL, 0, NULL, 'Ruth Chebet', 'ruth28ron@gmail.com', '0769523866', 'Marketing strategist', 'Sister', 'national_id', '493580396', 'signatures/signature_Brian_Chepkwony.png', 'national_id', '36411666', 'id-documents/ZgU6oyfeCSJC4ZmaVVms1fPEq6XoI0g1HmYtRG6R.jpg', 'id-documents/4aYlr0bBjD57cpzIZR6ZTBZpUzgczGGxlYfRtm20.jpg'),
(93, 'Nicholas Kamau', NULL, '28026398', 'nicholaskamau172@gmail.com', NULL, '$2y$12$RlheHR3uZwB/NKU6zp.nDeTDv1Sc/8Gb8UA5WXVmih1Ivvcc1Y4Gm', 'borrower', 0, NULL, '2026-07-30 05:23:26', '2026-07-30 05:23:26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(94, 'Anjela Nasenya Makoko', NULL, '0703731558', 'anjelanasenya@gmail.com', NULL, '$2y$12$91nM6rLtZJy4/uWzfb9OLus6lRo/BitK1euBDQgPyxiTGj9H.7rDC', 'borrower', 0, NULL, '2026-08-04 07:28:29', '2026-08-24 11:02:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Anjela_Nasenya_Makoko.png', NULL, NULL, NULL, NULL),
(95, 'Cynthia Kaweru', NULL, '0706926230', 'cynthiakaweru@gmail.com', NULL, '$2y$12$vvgUxAa1dU3IDdGOLfNfKO13mv556Tv6TJNae9IrEMcP3xZ3QXLZi', 'borrower', 0, NULL, '2026-08-11 09:36:28', '2026-09-02 07:19:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Cynthia_Kaweru.png', NULL, NULL, NULL, NULL),
(97, 'MOTURI KAYAGA ELIAS', NULL, '0723639684', 'moturi@gmail.com', NULL, '$2y$12$oE9pzW8KGxk6rBDhBWpxWu07Ntrqii0KCh9.Z2txQx3vr.QuwQnx2', 'borrower', 1, NULL, '2026-08-24 08:00:37', '2026-08-24 08:00:37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(98, 'CHRISTINE  GIKENYI', NULL, '0714116482', 'gikenyi@gmail.com', NULL, '$2y$12$Xo6vGdWEbby26J/L4gSVsususpd99fu2jLPcOnehWzxc5u3Eqz8O6', 'borrower', 1, NULL, '2026-08-24 10:55:41', '2026-08-24 10:55:41', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 'Juliet  Akayi', NULL, '0706785038', 'akayi@gmail.com', NULL, '$2y$12$NTz.b/vaoNJIdX8z6tDbJ.AbOE1Q1q.ZreH8YwZtNAx5aNxk/ZSv6', 'borrower', 1, NULL, '2026-08-24 10:59:42', '2026-08-24 10:59:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(100, 'Erick Kiiya Mutuku', NULL, '0703988016', 'kiiyaerick@gmail.com', NULL, '$2y$12$2kivblx.K3kjX9MEHS2lnO25T8/cyUClHSmo.803kw0l5EGIo0.JS', 'borrower', 0, NULL, '2026-08-28 04:23:30', '2026-08-31 08:50:27', NULL, 'male', NULL, NULL, 'Kenya', 'married', 'Christianity', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Erick_Kiiya_Mutuku.png', NULL, NULL, NULL, NULL),
(101, 'IAN OTIENO  OKOTH', NULL, '0758229006', 'ianotieno@gmail.com', NULL, '$2y$12$BkeaSFKF9o7lzgKp3QgTFOTSkLJjgQxNV8KXQkFfnjCgViOVIH3yq', 'borrower', 1, NULL, '2026-08-31 08:33:03', '2026-08-31 08:35:44', '2026-08-31 08:35:44', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 'Victor Osiemo Obiri', NULL, '+254725575799', 'vosiemoobiri@gmail.com', NULL, '$2y$12$.FPVzHbyDr3Fx6RcC2L71.3rvusaZQhOkA2mh.admyFho7BMChEQ2', 'borrower', 1, NULL, '2026-08-31 13:48:37', '2026-09-16 07:07:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Victor_Osiemo_Obiri.png', NULL, NULL, NULL, NULL),
(103, 'Mercy Nduko', NULL, '+254 715 712815', 'mngila50@gmail.com', NULL, '$2y$12$Lr4C.KbTywwXT.BpsgMlc.dyXt7VX8LsvYGdkpa6pMqZHU4hHVD.2', 'teller', 1, NULL, '2026-09-03 14:18:01', '2026-09-03 14:18:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 'Gabriel Evarest Muindi', 'avatars/oCaCRfyg4RuByLKVceVCuVCfu7xu0kI4EDSxkGg8.jpg', '0794000699', 'gabrielevaresto@gmail.com', NULL, '$2y$12$jBo2FEM3ED0wU7U7tsyDSOtt8o9iRUrq5n0r2wK/P5K1yCjM1dnZi', 'borrower', 0, 'NP8WlSmyE69QP5Mr9UHAkDChmNMobIoqOsJAwZjVQHbUdBpETpWZktETN5TA', '2026-09-11 04:57:42', '2026-09-11 05:51:57', NULL, 'male', '1995-07-04', NULL, 'Kenya', 'single', NULL, 0, 'Bachelor\'s Degree', 'Augustine Misiko Naulikha', 'augustinemisiko@gmail.com', '0740869345', 'banker', 'Brother', NULL, NULL, 'signatures/signature_Gabriel_Evarest_Muindi.png', 'National ID', '36917401', 'id_cards/gZ96ijz35BDkXZteshAvxo1y0PnONfNjFum5pSyP.jpg', 'id_cards/zXOJsRTj5L5HqpV6bcsPEq0O4kWtQGax6YHxpnv8.jpg'),
(106, 'Daniel Gitau', NULL, '0708496035', 'danielmwangigitau@gmail.com', NULL, '$2y$12$ntSiiyGrwxKGBx2O3UtCeumI3w4o5zaCyc0Tv.Oi061mvsaomnoVy', 'borrower', 0, NULL, '2026-09-26 14:02:07', '2026-09-28 08:31:06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Daniel_Gitau.png', NULL, NULL, NULL, NULL),
(107, 'Jamal Lomu', NULL, '0702981402', 'jamalomu10@gmail.com', NULL, '$2y$12$dt8dVd/9pdJ7jqylxRwAF.1x3MYi7NSXBOfRf7twTp6Z1OfyMpSlK', 'borrower', 1, NULL, '2026-09-27 10:22:48', '2026-09-27 10:22:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, 'Priska Dianga Mbuya', NULL, '+254742273253', 'priskadianga@gmail.com', NULL, '$2y$12$/qGNXftwOBQQl9MKac5yluqtBTHXPpqufpSMKQ0klDzDpHJcwk4g6', 'borrower', 1, NULL, '2026-10-12 08:38:36', '2026-10-12 08:38:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(109, 'Reagan Ogonda', NULL, '0796116248', 'rygoh35@gmail.com', NULL, '$2y$12$XxRxzSbwSTh0miIoGDx22.lrlob1zATd28wtLLDUSvSdKNPDaAE36', 'borrower', 0, NULL, '2026-10-13 18:05:44', '2026-10-13 18:05:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(110, 'Brian Bogonko', NULL, '+254724542452', 'brianbogonko@gmail.com', NULL, '$2y$12$Lc/AzvitgNmVDpWiweYSl.I/vUZls.iFOaYbYkOF/UkD/byfxe3vS', 'borrower', 0, NULL, '2026-10-14 23:40:12', '2026-10-14 23:40:12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'References users.id',
  `current_residence` varchar(255) DEFAULT NULL,
  `current_residence_from` date DEFAULT NULL,
  `current_residence_to` date DEFAULT NULL,
  `residence_type_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'References residence_types.id',
  `residence_notes` text DEFAULT NULL,
  `general_notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `holder_type` varchar(255) NOT NULL,
  `holder_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `uuid` char(36) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `balance` decimal(64,0) NOT NULL DEFAULT 0,
  `decimal_places` smallint(5) UNSIGNED NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `holder_type`, `holder_id`, `name`, `slug`, `uuid`, `description`, `meta`, `balance`, `decimal_places`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'App\\Models\\Partner', 5, 'Principal', 'principal', '01a11298-e5e2-708b-8166-5300d963584c', NULL, '{\"partner_id\":5}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(2, 'App\\Models\\Partner', 5, 'Interest', 'interest', '01a11298-e5ee-7008-9e98-3a2d7b25fb3f', NULL, '{\"partner_id\":5}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(3, 'App\\Models\\Partner', 5, 'Broker fee', 'broker_fee', '01a11298-e5f2-735c-8b65-546a049f322e', NULL, '{\"partner_id\":5}', 1252969600, 4, '2026-10-06 17:02:52', '2026-10-07 12:53:03', NULL),
(4, 'App\\Models\\Partner', 5, 'Investment tracker', 'investment_tracker', '01a11298-e5fa-7201-8524-47ed45132230', NULL, '{\"partner_id\":5}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(5, 'App\\Models\\Partner', 6, 'Principal', 'principal', '01a11298-e5fe-7360-8ef3-0e256ee6775a', NULL, '{\"partner_id\":6}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(6, 'App\\Models\\Partner', 6, 'Interest', 'interest', '01a11298-e600-7282-afac-3d345334e293', NULL, '{\"partner_id\":6}', 44201566400, 4, '2026-10-06 17:02:52', '2026-10-07 12:40:06', NULL),
(7, 'App\\Models\\Partner', 6, 'Broker fee', 'broker_fee', '01a11298-e603-7341-8091-12ffee5fbe61', NULL, '{\"partner_id\":6}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(8, 'App\\Models\\Partner', 6, 'Investment tracker', 'investment_tracker', '01a11298-e605-7135-acb9-2682c7c5bef1', NULL, '{\"partner_id\":6}', 0, 4, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(9, 'App\\Models\\User', 2, 'Loan Wallet', 'borrower_loan', '01a11298-e607-72b7-bb48-640668009352', NULL, '{\"user_id\":2}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(10, 'App\\Models\\User', 6, 'Loan Wallet', 'borrower_loan', '01a11298-e609-723c-86d9-c926cf9f9622', NULL, '{\"user_id\":6}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(11, 'App\\Models\\User', 7, 'Loan Wallet', 'borrower_loan', '01a11298-e60b-72f1-b6eb-8ea10d495608', NULL, '{\"user_id\":7}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(12, 'App\\Models\\User', 8, 'Loan Wallet', 'borrower_loan', '01a11298-e60d-71b2-be72-61e6d4775bba', NULL, '{\"user_id\":8}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(13, 'App\\Models\\User', 9, 'Loan Wallet', 'borrower_loan', '01a11298-e60f-73a6-a1d2-a66d36fa88fd', NULL, '{\"user_id\":9}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(14, 'App\\Models\\User', 10, 'Loan Wallet', 'borrower_loan', '01a11298-e611-7230-a1c1-cc434dc7c178', NULL, '{\"user_id\":10}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(15, 'App\\Models\\User', 11, 'Loan Wallet', 'borrower_loan', '01a11298-e614-722d-94c1-83426e3c717c', NULL, '{\"user_id\":11}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(16, 'App\\Models\\User', 13, 'Loan Wallet', 'borrower_loan', '01a11298-e616-70a4-80a6-a0aefd974130', NULL, '{\"user_id\":13}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(17, 'App\\Models\\User', 14, 'Loan Wallet', 'borrower_loan', '01a11298-e618-72fc-b93e-9036a807900d', NULL, '{\"user_id\":14}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(18, 'App\\Models\\User', 15, 'Loan Wallet', 'borrower_loan', '01a11298-e61a-739e-9f82-462c081680eb', NULL, '{\"user_id\":15}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(19, 'App\\Models\\User', 16, 'Loan Wallet', 'borrower_loan', '01a11298-e61c-705c-88ee-294d341e7a6a', NULL, '{\"user_id\":16}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(20, 'App\\Models\\User', 17, 'Loan Wallet', 'borrower_loan', '01a11298-e61e-70ee-b612-f627124321be', NULL, '{\"user_id\":17}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(21, 'App\\Models\\User', 18, 'Loan Wallet', 'borrower_loan', '01a11298-e620-7039-9119-3750f0be813a', NULL, '{\"user_id\":18}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(22, 'App\\Models\\User', 19, 'Loan Wallet', 'borrower_loan', '01a11298-e622-7098-b3bf-156f149c851d', NULL, '{\"user_id\":19}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(23, 'App\\Models\\User', 20, 'Loan Wallet', 'borrower_loan', '01a11298-e624-73dd-ad9c-173710d03a27', NULL, '{\"user_id\":20}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(24, 'App\\Models\\User', 21, 'Loan Wallet', 'borrower_loan', '01a11298-e625-72ac-aa12-59e8d814ba58', NULL, '{\"user_id\":21}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(25, 'App\\Models\\User', 22, 'Loan Wallet', 'borrower_loan', '01a11298-e628-728c-9d0e-141099e4f35c', NULL, '{\"user_id\":22}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(26, 'App\\Models\\User', 23, 'Loan Wallet', 'borrower_loan', '01a11298-e629-70ec-af7f-0e5b35da9187', NULL, '{\"user_id\":23}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(27, 'App\\Models\\User', 24, 'Loan Wallet', 'borrower_loan', '01a11298-e62b-7261-84a9-0fedf57a7ead', NULL, '{\"user_id\":24}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(28, 'App\\Models\\User', 25, 'Loan Wallet', 'borrower_loan', '01a11298-e62d-7025-9f93-edf0608d2f1f', NULL, '{\"user_id\":25}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(29, 'App\\Models\\User', 26, 'Loan Wallet', 'borrower_loan', '01a11298-e62f-7069-a00e-876395cb97df', NULL, '{\"user_id\":26}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(30, 'App\\Models\\User', 27, 'Loan Wallet', 'borrower_loan', '01a11298-e631-7176-a37a-a5886aa2cd58', NULL, '{\"user_id\":27}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(31, 'App\\Models\\User', 28, 'Loan Wallet', 'borrower_loan', '01a11298-e633-7184-8e4d-8625b477fa3a', NULL, '{\"user_id\":28}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(32, 'App\\Models\\User', 29, 'Loan Wallet', 'borrower_loan', '01a11298-e635-7037-a0af-abade7d8e399', NULL, '{\"user_id\":29}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(33, 'App\\Models\\User', 31, 'Loan Wallet', 'borrower_loan', '01a11298-e637-739a-aeb9-5ba3b4c90404', NULL, '{\"user_id\":31}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(34, 'App\\Models\\User', 32, 'Loan Wallet', 'borrower_loan', '01a11298-e639-7100-b535-80395fc57d4a', NULL, '{\"user_id\":32}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(35, 'App\\Models\\User', 33, 'Loan Wallet', 'borrower_loan', '01a11298-e63b-70f8-b54c-e4cdbe0558a7', NULL, '{\"user_id\":33}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(36, 'App\\Models\\User', 34, 'Loan Wallet', 'borrower_loan', '01a11298-e63e-7268-a4fd-e31640028ddc', NULL, '{\"user_id\":34}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(37, 'App\\Models\\User', 35, 'Loan Wallet', 'borrower_loan', '01a11298-e640-73c1-8915-7cf61a062ff5', NULL, '{\"user_id\":35}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(38, 'App\\Models\\User', 36, 'Loan Wallet', 'borrower_loan', '01a11298-e642-7082-ba9d-44dd9b164883', NULL, '{\"user_id\":36}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(39, 'App\\Models\\User', 37, 'Loan Wallet', 'borrower_loan', '01a11298-e644-7095-9315-c3b9e5355a36', NULL, '{\"user_id\":37}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(40, 'App\\Models\\User', 38, 'Loan Wallet', 'borrower_loan', '01a11298-e646-73bd-8e85-a8f7b2b8b59a', NULL, '{\"user_id\":38}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(41, 'App\\Models\\User', 39, 'Loan Wallet', 'borrower_loan', '01a11298-e648-73d5-9725-cce4f0d10f41', NULL, '{\"user_id\":39}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(42, 'App\\Models\\User', 40, 'Loan Wallet', 'borrower_loan', '01a11298-e64b-71e9-8240-8e91816d57c7', NULL, '{\"user_id\":40}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(43, 'App\\Models\\User', 41, 'Loan Wallet', 'borrower_loan', '01a11298-e64d-73ac-9a03-48c78875a9ab', NULL, '{\"user_id\":41}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(44, 'App\\Models\\User', 42, 'Loan Wallet', 'borrower_loan', '01a11298-e650-73c1-a4fc-568105c71650', NULL, '{\"user_id\":42}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(45, 'App\\Models\\User', 43, 'Loan Wallet', 'borrower_loan', '01a11298-e653-7240-97ed-1040caaea92c', NULL, '{\"user_id\":43}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(46, 'App\\Models\\User', 45, 'Loan Wallet', 'borrower_loan', '01a11298-e655-7294-8483-458f65ad269c', NULL, '{\"user_id\":45}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(47, 'App\\Models\\User', 47, 'Loan Wallet', 'borrower_loan', '01a11298-e658-7260-86d0-990eccd305ab', NULL, '{\"user_id\":47}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(48, 'App\\Models\\User', 48, 'Loan Wallet', 'borrower_loan', '01a11298-e65b-7037-a737-f39b839ab3fd', NULL, '{\"user_id\":48}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(49, 'App\\Models\\User', 50, 'Loan Wallet', 'borrower_loan', '01a11298-e65c-7153-b9b4-e0b6af94aad4', NULL, '{\"user_id\":50}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(50, 'App\\Models\\User', 51, 'Loan Wallet', 'borrower_loan', '01a11298-e65f-716f-bc5b-9738c4dbc38b', NULL, '{\"user_id\":51}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(51, 'App\\Models\\User', 52, 'Loan Wallet', 'borrower_loan', '01a11298-e661-738a-ae65-ffb5124011a2', NULL, '{\"user_id\":52}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(52, 'App\\Models\\User', 53, 'Loan Wallet', 'borrower_loan', '01a11298-e663-7076-8328-af3c116e0882', NULL, '{\"user_id\":53}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(53, 'App\\Models\\User', 54, 'Loan Wallet', 'borrower_loan', '01a11298-e665-7026-839b-5c89708be536', NULL, '{\"user_id\":54}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(54, 'App\\Models\\User', 55, 'Loan Wallet', 'borrower_loan', '01a11298-e667-72e3-ab15-425cc1e93509', NULL, '{\"user_id\":55}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(55, 'App\\Models\\User', 56, 'Loan Wallet', 'borrower_loan', '01a11298-e669-7069-afe1-ae532585119a', NULL, '{\"user_id\":56}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(56, 'App\\Models\\User', 57, 'Loan Wallet', 'borrower_loan', '01a11298-e66b-738b-86cf-9a4cb1ac6b7e', NULL, '{\"user_id\":57}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(57, 'App\\Models\\User', 58, 'Loan Wallet', 'borrower_loan', '01a11298-e66c-7260-90bd-51f5a10542cf', NULL, '{\"user_id\":58}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(58, 'App\\Models\\User', 59, 'Loan Wallet', 'borrower_loan', '01a11298-e66e-73f7-b113-4405dd5042ce', NULL, '{\"user_id\":59}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(59, 'App\\Models\\User', 62, 'Loan Wallet', 'borrower_loan', '01a11298-e670-7346-99a6-f4c4e07177f7', NULL, '{\"user_id\":62}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(60, 'App\\Models\\User', 63, 'Loan Wallet', 'borrower_loan', '01a11298-e672-732c-853a-75d690159d0d', NULL, '{\"user_id\":63}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(61, 'App\\Models\\User', 64, 'Loan Wallet', 'borrower_loan', '01a11298-e674-707b-9a8c-64b4c82014a7', NULL, '{\"user_id\":64}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(62, 'App\\Models\\User', 65, 'Loan Wallet', 'borrower_loan', '01a11298-e676-71c9-94bf-35e3a811ff39', NULL, '{\"user_id\":65}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(63, 'App\\Models\\User', 66, 'Loan Wallet', 'borrower_loan', '01a11298-e678-71a3-9e58-76b8187ca393', NULL, '{\"user_id\":66}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(64, 'App\\Models\\User', 67, 'Loan Wallet', 'borrower_loan', '01a11298-e67a-70c3-8dcc-b82cca733f0d', NULL, '{\"user_id\":67}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(65, 'App\\Models\\User', 68, 'Loan Wallet', 'borrower_loan', '01a11298-e67c-7000-a926-099fd7a1728d', NULL, '{\"user_id\":68}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(66, 'App\\Models\\User', 69, 'Loan Wallet', 'borrower_loan', '01a11298-e67d-7298-83cc-a569f025c52c', NULL, '{\"user_id\":69}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(67, 'App\\Models\\User', 70, 'Loan Wallet', 'borrower_loan', '01a11298-e67f-7199-a0f8-8cd5b0fa2f3d', NULL, '{\"user_id\":70}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(68, 'App\\Models\\User', 71, 'Loan Wallet', 'borrower_loan', '01a11298-e681-73bc-b59c-bf44e4a57763', NULL, '{\"user_id\":71}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(69, 'App\\Models\\User', 72, 'Loan Wallet', 'borrower_loan', '01a11298-e683-703d-a8a6-7d0d97e8b289', NULL, '{\"user_id\":72}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(70, 'App\\Models\\User', 73, 'Loan Wallet', 'borrower_loan', '01a11298-e685-72c6-a685-403e01ce546d', NULL, '{\"user_id\":73}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(71, 'App\\Models\\User', 74, 'Loan Wallet', 'borrower_loan', '01a11298-e687-7171-b464-a1a8e756ddb6', NULL, '{\"user_id\":74}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(72, 'App\\Models\\User', 75, 'Loan Wallet', 'borrower_loan', '01a11298-e688-7119-bb14-828774c75d30', NULL, '{\"user_id\":75}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(73, 'App\\Models\\User', 76, 'Loan Wallet', 'borrower_loan', '01a11298-e68a-724e-814d-cc6b4304c67e', NULL, '{\"user_id\":76}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(74, 'App\\Models\\User', 77, 'Loan Wallet', 'borrower_loan', '01a11298-e68c-70c8-93d3-8d3d232084fc', NULL, '{\"user_id\":77}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(75, 'App\\Models\\User', 84, 'Loan Wallet', 'borrower_loan', '01a11298-e68e-72a1-bd11-8b903b13d4f9', NULL, '{\"user_id\":84}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(76, 'App\\Models\\User', 91, 'Loan Wallet', 'borrower_loan', '01a11298-e690-703b-a757-290e74b3f94d', NULL, '{\"user_id\":91}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(77, 'App\\Models\\User', 92, 'Loan Wallet', 'borrower_loan', '01a11298-e691-71c6-bdcb-80da11d57233', NULL, '{\"user_id\":92}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(78, 'App\\Models\\User', 93, 'Loan Wallet', 'borrower_loan', '01a11298-e693-72bc-bc25-3d73bd02e944', NULL, '{\"user_id\":93}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(79, 'App\\Models\\User', 94, 'Loan Wallet', 'borrower_loan', '01a11298-e695-727e-bb1f-193cc674941e', NULL, '{\"user_id\":94}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(80, 'App\\Models\\User', 95, 'Loan Wallet', 'borrower_loan', '01a11298-e697-72d1-bde0-de78c6e28009', NULL, '{\"user_id\":95}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(81, 'App\\Models\\User', 97, 'Loan Wallet', 'borrower_loan', '01a11298-e699-70c4-839b-9486f08f2cbd', NULL, '{\"user_id\":97}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(82, 'App\\Models\\User', 98, 'Loan Wallet', 'borrower_loan', '01a11298-e69b-7185-a34e-f5d115783ccf', NULL, '{\"user_id\":98}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(83, 'App\\Models\\User', 99, 'Loan Wallet', 'borrower_loan', '01a11298-e69d-7098-bd7e-4d0acdef9edf', NULL, '{\"user_id\":99}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(84, 'App\\Models\\User', 100, 'Loan Wallet', 'borrower_loan', '01a11298-e69f-731b-b4a3-b93a55a46664', NULL, '{\"user_id\":100}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(85, 'App\\Models\\User', 102, 'Loan Wallet', 'borrower_loan', '01a11298-e6a1-730e-8dfa-2df1522235b1', NULL, '{\"user_id\":102}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(86, 'App\\Models\\User', 104, 'Loan Wallet', 'borrower_loan', '01a11298-e6a3-7253-9427-ec8ffe96ce77', NULL, '{\"user_id\":104}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(87, 'App\\Models\\User', 106, 'Loan Wallet', 'borrower_loan', '01a11298-e6a5-72ac-9b36-9531f771cc95', NULL, '{\"user_id\":106}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL),
(88, 'App\\Models\\User', 107, 'Loan Wallet', 'borrower_loan', '01a11298-e6a7-71ce-b33e-09b6e2fdea9b', NULL, '{\"user_id\":107}', 0, 2, '2026-10-06 17:02:52', '2026-10-06 17:02:52', NULL);

-- --------------------------------------------------------

--
-- Structure for view `borrower_contact_network`
--
DROP TABLE IF EXISTS `borrower_contact_network`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `borrower_contact_network`  AS SELECT `u`.`id` AS `user_id`, `u`.`name` AS `borrower_name`, `c`.`id` AS `contact_id`, `c`.`name` AS `contact_name`, `c`.`phone` AS `contact_phone`, `c`.`email` AS `contact_email`, `ct`.`name` AS `contact_type`, `c`.`relationship_specific` AS `relationship_specific`, `c`.`is_primary_contact` AS `is_primary_contact`, `c`.`priority` AS `priority` FROM ((`users` `u` left join `contacts` `c` on(`u`.`id` = `c`.`user_id`)) left join `contact_types` `ct` on(`c`.`contact_type_id` = `ct`.`id`)) WHERE `u`.`role` in ('borrower','partner') ;

-- --------------------------------------------------------

--
-- Structure for view `broker_performance_report`
--
DROP TABLE IF EXISTS `broker_performance_report`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `broker_performance_report`  AS SELECT `brok`.`user_id` AS `broker_user_id`, `u`.`name` AS `broker_name`, count(distinct `b`.`id`) AS `clients_referred`, count(distinct `l`.`id`) AS `loans_from_clients`, sum(`l`.`amount`) AS `total_principal_from_clients`, sum(case when `r`.`transaction` = 'ROLL OVER' then 1 else 0 end) AS `client_rollovers`, sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 1 else 0 end) AS `client_discounts`, sum(case when `r`.`transaction` = 'BAD DEBT' then 1 else 0 end) AS `client_bad_debts`, sum(`l`.`amount` * (`lt`.`interest_rate` / 100) * (`brok`.`interest_broker` / 100)) AS `estimated_broker_interest_commission`, sum(`l`.`amount` * (`lt`.`penalty_rate` / 100) * (`brok`.`penalty_broker` / 100)) AS `estimated_broker_penalty_commission` FROM (((((`brokers` `brok` join `users` `u` on(`u`.`id` = `brok`.`user_id`)) left join `borrowers` `b` on(`b`.`broker_id` = `brok`.`id`)) left join `loans` `l` on(`l`.`user_id` = `b`.`user_id`)) left join `loan_types` `lt` on(`l`.`loan_type_id` = `lt`.`id`)) left join `repayments` `r` on(`r`.`loan_id` = `l`.`id`)) GROUP BY `brok`.`user_id`, `u`.`name` ORDER BY sum(`l`.`amount`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `customer_health_scorecard`
--
DROP TABLE IF EXISTS `customer_health_scorecard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `customer_health_scorecard`  AS SELECT `b`.`user_id` AS `user_id`, `u`.`name` AS `name`, `u`.`phone` AS `phone`, count(distinct `l`.`id`) AS `total_loans`, sum(`l`.`amount`) AS `total_principal_borrowed`, sum(case when `r`.`transaction` = 'ROLL OVER' then 1 else 0 end) AS `count_rollovers`, sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 1 else 0 end) AS `count_discounts`, sum(case when `r`.`transaction` = 'BAD DEBT' then 1 else 0 end) AS `count_bad_debts`, 100 - sum(case when `r`.`transaction` = 'ROLL OVER' then 5 else 0 end) - sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 15 else 0 end) - sum(case when `r`.`transaction` = 'BAD DEBT' then 30 else 0 end) AS `health_score`, CASE WHEN 100 - (sum(case when `r`.`transaction` = 'ROLL OVER' then 5 else 0 end) - sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 15 else 0 end) - sum(case when `r`.`transaction` = 'BAD DEBT' then 30 else 0 end)) >= 80 THEN 'A (Low Risk)' WHEN 100 - (sum(case when `r`.`transaction` = 'ROLL OVER' then 5 else 0 end) - sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 15 else 0 end) - sum(case when `r`.`transaction` = 'BAD DEBT' then 30 else 0 end)) >= 60 THEN 'B (Medium Risk)' WHEN 100 - (sum(case when `r`.`transaction` = 'ROLL OVER' then 5 else 0 end) - sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 15 else 0 end) - sum(case when `r`.`transaction` = 'BAD DEBT' then 30 else 0 end)) >= 40 THEN 'C (High Risk)' ELSE 'D (Very High Risk)' END AS `health_grade`, `b`.`status` AS `is_borrower_active` FROM (((`borrowers` `b` join `users` `u` on(`u`.`id` = `b`.`user_id`)) left join `loans` `l` on(`l`.`user_id` = `u`.`id`)) left join `repayments` `r` on(`r`.`loan_id` = `l`.`id`)) GROUP BY `b`.`user_id`, `u`.`name`, `u`.`phone`, `b`.`status` ORDER BY 100 - sum(case when `r`.`transaction` = 'ROLL OVER' then 5 else 0 end) - sum(case when `r`.`transaction` = 'CREDIT DISCOUNT' then 15 else 0 end) - sum(case when `r`.`transaction` = 'BAD DEBT' then 30 else 0 end) ASC ;

-- --------------------------------------------------------

--
-- Structure for view `debt_recovery_summary`
--
DROP TABLE IF EXISTS `debt_recovery_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `debt_recovery_summary`  AS SELECT `drc`.`id` AS `case_id`, `drc`.`case_number` AS `case_number`, `u`.`id` AS `user_id`, `u`.`name` AS `debtor_name`, `u`.`email` AS `debtor_email`, `u`.`phone` AS `debtor_phone`, `drc`.`total_debt_amount` AS `total_debt_amount`, `drc`.`principal_outstanding` AS `principal_outstanding`, `drc`.`interest_outstanding` AS `interest_outstanding`, `drc`.`penalty_outstanding` AS `penalty_outstanding`, `drc`.`fees_outstanding` AS `fees_outstanding`, `drc`.`default_date` AS `default_date`, `drc`.`days_in_default` AS `days_in_default`, `rs`.`name` AS `status`, `rp`.`name` AS `priority`, `drc`.`assigned_to` AS `assigned_to`, `drc`.`last_contact_date` AS `last_contact_date`, `drc`.`next_action_date` AS `next_action_date`, (select sum(`recovery_actions`.`amount_collected`) from `recovery_actions` where `recovery_actions`.`case_id` = `drc`.`id` and `recovery_actions`.`outcome` = 'successful') AS `total_recovered`, (select count(0) from `recovery_actions` where `recovery_actions`.`case_id` = `drc`.`id`) AS `total_actions` FROM (((`debt_recovery_cases` `drc` join `users` `u` on(`drc`.`user_id` = `u`.`id`)) join `recovery_statuses` `rs` on(`drc`.`status_id` = `rs`.`id`)) join `recovery_priorities` `rp` on(`drc`.`priority_id` = `rp`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `loan_type_analysis`
--
DROP TABLE IF EXISTS `loan_type_analysis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `loan_type_analysis`  AS SELECT `lt`.`id` AS `id`, `lt`.`name` AS `name`, `lt`.`interest_rate` AS `interest_rate`, count(`l`.`id`) AS `times_issued`, sum(`l`.`amount`) AS `total_principal`, avg(`l`.`amount`) AS `avg_principal_size`, round(sum(case when `r`.`transaction` is not null then 1 else 0 end) / count(`l`.`id`) * 100,2) AS `percentage_with_problems`, round(avg(`chs`.`health_score`),2) AS `avg_borrower_health_score` FROM (((`loan_types` `lt` left join `loans` `l` on(`l`.`loan_type_id` = `lt`.`id`)) left join `repayments` `r` on(`r`.`loan_id` = `l`.`id` and `r`.`transaction` in ('ROLL OVER','CREDIT DISCOUNT','BAD DEBT'))) left join `customer_health_scorecard` `chs` on(`l`.`user_id` = `chs`.`user_id`)) GROUP BY `lt`.`id`, `lt`.`name`, `lt`.`interest_rate` ORDER BY sum(`l`.`amount`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `portfolio_summary`
--
DROP TABLE IF EXISTS `portfolio_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `portfolio_summary`  AS SELECT count(0) AS `total_loans_issued`, sum(`l`.`amount`) AS `total_principal_disbursed`, sum(case when `l`.`status` = 'repaid' then `l`.`amount` else 0 end) AS `total_principal_repaid`, sum(case when `l`.`status` in ('pending','approved','disbursed') then `l`.`amount` else 0 end) AS `total_principal_outstanding`, sum(`l`.`amount` * (`lt`.`interest_rate` / 100)) AS `total_expected_interest`, (select sum(`repayments`.`amount`) from `repayments`) - sum(case when `l`.`status` = 'repaid' then `l`.`amount` else 0 end) AS `total_actual_revenue_approx`, (select sum(`repayments`.`amount`) from `repayments` where `repayments`.`transaction` in ('BAD DEBT','CREDIT DISCOUNT')) AS `total_write_offs` FROM (`loans` `l` join `loan_types` `lt` on(`l`.`loan_type_id` = `lt`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `recovery_officer_workload`
--
DROP TABLE IF EXISTS `recovery_officer_workload`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `recovery_officer_workload`  AS SELECT `u`.`id` AS `officer_id`, `u`.`name` AS `officer_name`, `u`.`email` AS `officer_email`, count(distinct `drc`.`id`) AS `total_cases`, count(distinct case when `rs`.`slug` in ('open','in_progress','negotiation') then `drc`.`id` end) AS `active_cases`, count(distinct case when `rp`.`slug` = 'urgent' then `drc`.`id` end) AS `urgent_cases`, sum(`drc`.`total_debt_amount`) AS `total_debt_value` FROM (((`users` `u` left join `debt_recovery_cases` `drc` on(`u`.`id` = `drc`.`assigned_to`)) left join `recovery_statuses` `rs` on(`drc`.`status_id` = `rs`.`id`)) left join `recovery_priorities` `rp` on(`drc`.`priority_id` = `rp`.`id`)) WHERE `u`.`role` in ('admin','teller') GROUP BY `u`.`id`, `u`.`name`, `u`.`email` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `action_types`
--
ALTER TABLE `action_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_address_type_id` (`address_type_id`),
  ADD KEY `idx_is_primary` (`is_primary`),
  ADD KEY `addresses_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `address_types`
--
ALTER TABLE `address_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admins_user_id_foreign` (`user_id`);

--
-- Indexes for table `agency_case_assignments`
--
ALTER TABLE `agency_case_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_agency_id` (`agency_id`);

--
-- Indexes for table `agency_contacts`
--
ALTER TABLE `agency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agency_id` (`agency_id`),
  ADD KEY `idx_is_primary` (`is_primary`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `collateral_for_loan_id` (`collateral_for_loan_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_asset_type_id` (`asset_type_id`),
  ADD KEY `idx_is_collateral` (`is_collateral`),
  ADD KEY `assets_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `asset_types`
--
ALTER TABLE `asset_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bank_accounts_accountable_type_accountable_id_index` (`accountable_type`,`accountable_id`);

--
-- Indexes for table `borrowers`
--
ALTER TABLE `borrowers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrowers_user_id_foreign` (`user_id`),
  ADD KEY `borrowers_broker_id_foreign` (`broker_id`);

--
-- Indexes for table `brokers`
--
ALTER TABLE `brokers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brokers_cert_no_unique` (`cert_no`),
  ADD KEY `brokers_user_id_foreign` (`user_id`);

--
-- Indexes for table `bureau_names`
--
ALTER TABLE `bureau_names`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_categoryable_type_categoryable_id_index` (`category_type`,`categoryable_id`);

--
-- Indexes for table `communications`
--
ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_communication_type_id` (`communication_type_id`),
  ADD KEY `idx_communication_status_id` (`communication_status_id`);

--
-- Indexes for table `communication_statuses`
--
ALTER TABLE `communication_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `communication_types`
--
ALTER TABLE `communication_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_contact_type_id` (`contact_type_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `contacts_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `contact_types`
--
ALTER TABLE `contact_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `court_hearings`
--
ALTER TABLE `court_hearings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_legal_proceeding_id` (`legal_proceeding_id`),
  ADD KEY `idx_hearing_date` (`hearing_date`);

--
-- Indexes for table `credit_bureau_reports`
--
ALTER TABLE `credit_bureau_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_bureau_name_id` (`bureau_name_id`);

--
-- Indexes for table `debt_recovery_cases`
--
ALTER TABLE `debt_recovery_cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_case_number` (`case_number`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_loan_id` (`loan_id`),
  ADD KEY `idx_status_id` (`status_id`),
  ADD KEY `idx_priority_id` (`priority_id`),
  ADD KEY `idx_default_date` (`default_date`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disbursements_loan_id_foreign` (`loan_id`),
  ADD KEY `partner_transaction_id` (`partner_transaction_id`),
  ADD KEY `investment_id` (`investment_id`);

--
-- Indexes for table `document_statuses`
--
ALTER TABLE `document_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `employments`
--
ALTER TABLE `employments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employment_type_id` (`employment_type_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_employer_name` (`employer_name`),
  ADD KEY `idx_is_current` (`is_current`);

--
-- Indexes for table `employment_types`
--
ALTER TABLE `employment_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enquiries_email_topic_unique` (`email`,`topic`),
  ADD UNIQUE KEY `enquiries_phone_topic_unique` (`phone`,`topic`),
  ADD KEY `enquiries_topic_index` (`topic`),
  ADD KEY `enquiries_status_index` (`status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `financial_assessments`
--
ALTER TABLE `financial_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hardship_reason_id` (`hardship_reason_id`),
  ADD KEY `assessed_by` (`assessed_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_case_id` (`case_id`);

--
-- Indexes for table `hardship_reasons`
--
ALTER TABLE `hardship_reasons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `investment_fundings`
--
ALTER TABLE `investment_fundings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investment_fundings_investment_id_foreign` (`investment_id`),
  ADD KEY `investment_fundings_partner_id_foreign` (`partner_id`),
  ADD KEY `investment_fundings_partner_transaction_id_foreign` (`partner_transaction_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `legal_deadlines`
--
ALTER TABLE `legal_deadlines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_deadline_date` (`deadline_date`);

--
-- Indexes for table `legal_proceedings`
--
ALTER TABLE `legal_proceedings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proceeding_type_id` (`proceeding_type_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `legal_proceeding_types`
--
ALTER TABLE `legal_proceeding_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loans_user_id_foreign` (`user_id`),
  ADD KEY `loans_loan_type_id_foreign` (`loan_type_id`),
  ADD KEY `fk_loans_guarantor` (`guarantor_id`),
  ADD KEY `fk_loans_officer` (`loan_officer_id`),
  ADD KEY `loans_partner_id_idx` (`partner_id`);

--
-- Indexes for table `loan_agreement_sections`
--
ALTER TABLE `loan_agreement_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_loan_section` (`loan_id`,`section_type`);

--
-- Indexes for table `loan_agreement_templates`
--
ALTER TABLE `loan_agreement_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_cycles`
--
ALTER TABLE `loan_cycles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_loan_cycle_number` (`loan_id`,`cycle_number`),
  ADD KEY `idx_loan_cycles_loan_id` (`loan_id`),
  ADD KEY `idx_loan_cycles_status` (`status`),
  ADD KEY `idx_loan_cycles_due_date` (`due_date`),
  ADD KEY `idx_loan_cycles_cycle_number` (`cycle_number`),
  ADD KEY `idx_loan_cycle_active` (`loan_id`,`status`),
  ADD KEY `idx_loan_cycle_dates` (`loan_id`,`start_date`,`due_date`);

--
-- Indexes for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_loan_id` (`loan_id`),
  ADD KEY `idx_entry_type` (`entry_type`),
  ADD KEY `idx_entry_date` (`entry_date`);

--
-- Indexes for table `loan_risk_assessments`
--
ALTER TABLE `loan_risk_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_risk_loan` (`loan_id`),
  ADD KEY `fk_risk_assessor` (`assessed_by`);

--
-- Indexes for table `loan_types`
--
ALTER TABLE `loan_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `partner_transactions`
--
ALTER TABLE `partner_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pt_reference_wallet_unique` (`reference`,`wallet_slug`),
  ADD KEY `pt_partner_wallet_date_idx` (`partner_id`,`wallet_slug`,`transaction_date`),
  ADD KEY `pt_loan_idx` (`loan_id`),
  ADD KEY `pt_repayment_idx` (`repayment_id`),
  ADD KEY `pt_investment_idx` (`investment_id`),
  ADD KEY `pt_investment_funding_idx` (`investment_funding_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_method_type_id` (`method_type_id`),
  ADD KEY `idx_is_primary` (`is_primary`),
  ADD KEY `payment_methods_deleted_at_index` (`deleted_at`);

--
-- Indexes for table `payment_method_types`
--
ALTER TABLE `payment_method_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `recovery_actions`
--
ALTER TABLE `recovery_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_contact_id` (`contact_id`),
  ADD KEY `idx_action_type_id` (`action_type_id`),
  ADD KEY `idx_action_date` (`action_date`);

--
-- Indexes for table `recovery_agencies`
--
ALTER TABLE `recovery_agencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agency_name` (`agency_name`);

--
-- Indexes for table `recovery_case_notes`
--
ALTER TABLE `recovery_case_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_id` (`case_id`);

--
-- Indexes for table `recovery_documents`
--
ALTER TABLE `recovery_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_type_id` (`document_type_id`),
  ADD KEY `document_status_id` (`document_status_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_case_id` (`case_id`);

--
-- Indexes for table `recovery_installments`
--
ALTER TABLE `recovery_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_plan_id` (`payment_plan_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `recovery_payment_plans`
--
ALTER TABLE `recovery_payment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `recovery_performance_metrics`
--
ALTER TABLE `recovery_performance_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_officer_id` (`officer_id`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `recovery_priorities`
--
ALTER TABLE `recovery_priorities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `recovery_statuses`
--
ALTER TABLE `recovery_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `recovery_templates`
--
ALTER TABLE `recovery_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_template_type` (`template_type`);

--
-- Indexes for table `repayments`
--
ALTER TABLE `repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repayments_loan_id_foreign` (`loan_id`),
  ADD KEY `partner_transaction_id` (`partner_transaction_id`),
  ADD KEY `investment_id` (`investment_id`),
  ADD KEY `idx_repayments_loan_cycle_id` (`loan_cycle_id`);

--
-- Indexes for table `residence_types`
--
ALTER TABLE `residence_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `risk_categories`
--
ALTER TABLE `risk_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_slug` (`slug`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `skip_tracing`
--
ALTER TABLE `skip_tracing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_case_id` (`case_id`);

--
-- Indexes for table `system`
--
ALTER TABLE `system`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tellers`
--
ALTER TABLE `tellers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tellers_user_id_foreign` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_uuid_unique` (`uuid`),
  ADD KEY `transactions_payable_type_payable_id_index` (`payable_type`,`payable_id`),
  ADD KEY `payable_type_payable_id_ind` (`payable_type`,`payable_id`),
  ADD KEY `payable_type_ind` (`payable_type`,`payable_id`,`type`),
  ADD KEY `payable_confirmed_ind` (`payable_type`,`payable_id`,`confirmed`),
  ADD KEY `payable_type_confirmed_ind` (`payable_type`,`payable_id`,`type`,`confirmed`),
  ADD KEY `transactions_type_index` (`type`),
  ADD KEY `transactions_wallet_id_foreign` (`wallet_id`);

--
-- Indexes for table `transfers`
--
ALTER TABLE `transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transfers_uuid_unique` (`uuid`),
  ADD KEY `transfers_deposit_id_foreign` (`deposit_id`),
  ADD KEY `transfers_withdraw_id_foreign` (`withdraw_id`),
  ADD KEY `transfers_from_id_index` (`from_id`),
  ADD KEY `transfers_to_id_index` (`to_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_profile` (`user_id`),
  ADD KEY `residence_type_id` (`residence_type_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wallets_holder_type_holder_id_slug_unique` (`holder_type`,`holder_id`,`slug`),
  ADD UNIQUE KEY `wallets_uuid_unique` (`uuid`),
  ADD KEY `wallets_holder_type_holder_id_index` (`holder_type`,`holder_id`),
  ADD KEY `wallets_slug_index` (`slug`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `action_types`
--
ALTER TABLE `action_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `address_types`
--
ALTER TABLE `address_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agency_case_assignments`
--
ALTER TABLE `agency_case_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agency_contacts`
--
ALTER TABLE `agency_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_types`
--
ALTER TABLE `asset_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `borrowers`
--
ALTER TABLE `borrowers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bureau_names`
--
ALTER TABLE `bureau_names`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `communications`
--
ALTER TABLE `communications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_statuses`
--
ALTER TABLE `communication_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `communication_types`
--
ALTER TABLE `communication_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_types`
--
ALTER TABLE `contact_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `court_hearings`
--
ALTER TABLE `court_hearings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_bureau_reports`
--
ALTER TABLE `credit_bureau_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `debt_recovery_cases`
--
ALTER TABLE `debt_recovery_cases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=638;

--
-- AUTO_INCREMENT for table `document_statuses`
--
ALTER TABLE `document_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employments`
--
ALTER TABLE `employments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_types`
--
ALTER TABLE `employment_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_assessments`
--
ALTER TABLE `financial_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hardship_reasons`
--
ALTER TABLE `hardship_reasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `investment_fundings`
--
ALTER TABLE `investment_fundings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `legal_deadlines`
--
ALTER TABLE `legal_deadlines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `legal_proceedings`
--
ALTER TABLE `legal_proceedings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `legal_proceeding_types`
--
ALTER TABLE `legal_proceeding_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=539;

--
-- AUTO_INCREMENT for table `loan_agreement_sections`
--
ALTER TABLE `loan_agreement_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_agreement_templates`
--
ALTER TABLE `loan_agreement_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_cycles`
--
ALTER TABLE `loan_cycles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=617;

--
-- AUTO_INCREMENT for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_risk_assessments`
--
ALTER TABLE `loan_risk_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_types`
--
ALTER TABLE `loan_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `partner_transactions`
--
ALTER TABLE `partner_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=504;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_method_types`
--
ALTER TABLE `payment_method_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `recovery_actions`
--
ALTER TABLE `recovery_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_agencies`
--
ALTER TABLE `recovery_agencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_case_notes`
--
ALTER TABLE `recovery_case_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recovery_documents`
--
ALTER TABLE `recovery_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_installments`
--
ALTER TABLE `recovery_installments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_payment_plans`
--
ALTER TABLE `recovery_payment_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_performance_metrics`
--
ALTER TABLE `recovery_performance_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recovery_priorities`
--
ALTER TABLE `recovery_priorities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `recovery_statuses`
--
ALTER TABLE `recovery_statuses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `recovery_templates`
--
ALTER TABLE `recovery_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `repayments`
--
ALTER TABLE `repayments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=807;

--
-- AUTO_INCREMENT for table `residence_types`
--
ALTER TABLE `residence_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `risk_categories`
--
ALTER TABLE `risk_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `skip_tracing`
--
ALTER TABLE `skip_tracing`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system`
--
ALTER TABLE `system`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tellers`
--
ALTER TABLE `tellers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=504;

--
-- AUTO_INCREMENT for table `transfers`
--
ALTER TABLE `transfers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `addresses_ibfk_2` FOREIGN KEY (`address_type_id`) REFERENCES `address_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `agency_case_assignments`
--
ALTER TABLE `agency_case_assignments`
  ADD CONSTRAINT `agency_case_assignments_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agency_case_assignments_ibfk_2` FOREIGN KEY (`agency_id`) REFERENCES `recovery_agencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agency_case_assignments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `agency_contacts`
--
ALTER TABLE `agency_contacts`
  ADD CONSTRAINT `agency_contacts_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `recovery_agencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`asset_type_id`) REFERENCES `asset_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`collateral_for_loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `borrowers`
--
ALTER TABLE `borrowers`
  ADD CONSTRAINT `borrowers_broker_id_foreign` FOREIGN KEY (`broker_id`) REFERENCES `brokers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrowers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `brokers`
--
ALTER TABLE `brokers`
  ADD CONSTRAINT `brokers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `communications`
--
ALTER TABLE `communications`
  ADD CONSTRAINT `communications_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `communications_ibfk_2` FOREIGN KEY (`communication_type_id`) REFERENCES `communication_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `communications_ibfk_3` FOREIGN KEY (`communication_status_id`) REFERENCES `communication_statuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `communications_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_2` FOREIGN KEY (`contact_type_id`) REFERENCES `contact_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contacts_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contacts_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `court_hearings`
--
ALTER TABLE `court_hearings`
  ADD CONSTRAINT `court_hearings_ibfk_1` FOREIGN KEY (`legal_proceeding_id`) REFERENCES `legal_proceedings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `credit_bureau_reports`
--
ALTER TABLE `credit_bureau_reports`
  ADD CONSTRAINT `credit_bureau_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_bureau_reports_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `credit_bureau_reports_ibfk_3` FOREIGN KEY (`bureau_name_id`) REFERENCES `bureau_names` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_bureau_reports_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `debt_recovery_cases`
--
ALTER TABLE `debt_recovery_cases`
  ADD CONSTRAINT `debt_recovery_cases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `recovery_statuses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_4` FOREIGN KEY (`priority_id`) REFERENCES `recovery_priorities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_5` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `debt_recovery_cases_ibfk_7` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_ibfk_1` FOREIGN KEY (`partner_transaction_id`) REFERENCES `partner_transactions` (`id`),
  ADD CONSTRAINT `disbursements_ibfk_2` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  ADD CONSTRAINT `disbursements_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employments`
--
ALTER TABLE `employments`
  ADD CONSTRAINT `employments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employments_ibfk_2` FOREIGN KEY (`employment_type_id`) REFERENCES `employment_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `financial_assessments`
--
ALTER TABLE `financial_assessments`
  ADD CONSTRAINT `financial_assessments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `financial_assessments_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_assessments_ibfk_3` FOREIGN KEY (`hardship_reason_id`) REFERENCES `hardship_reasons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_assessments_ibfk_4` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_assessments_ibfk_5` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `investments_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `investment_fundings`
--
ALTER TABLE `investment_fundings`
  ADD CONSTRAINT `investment_fundings_investment_id_foreign` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `investment_fundings_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `investment_fundings_partner_transaction_id_foreign` FOREIGN KEY (`partner_transaction_id`) REFERENCES `partner_transactions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `legal_deadlines`
--
ALTER TABLE `legal_deadlines`
  ADD CONSTRAINT `legal_deadlines_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `legal_deadlines_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `legal_proceedings`
--
ALTER TABLE `legal_proceedings`
  ADD CONSTRAINT `legal_proceedings_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `legal_proceedings_ibfk_2` FOREIGN KEY (`proceeding_type_id`) REFERENCES `legal_proceeding_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `legal_proceedings_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_guarantor` FOREIGN KEY (`guarantor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_officer` FOREIGN KEY (`loan_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loans_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_partner_id_fk` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_agreement_sections`
--
ALTER TABLE `loan_agreement_sections`
  ADD CONSTRAINT `fk_agreement_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_cycles`
--
ALTER TABLE `loan_cycles`
  ADD CONSTRAINT `fk_loan_cycles_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_ledger`
--
ALTER TABLE `loan_ledger`
  ADD CONSTRAINT `loan_ledger_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_ledger_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_risk_assessments`
--
ALTER TABLE `loan_risk_assessments`
  ADD CONSTRAINT `fk_risk_assessor` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_risk_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partners`
--
ALTER TABLE `partners`
  ADD CONSTRAINT `partners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `partner_transactions`
--
ALTER TABLE `partner_transactions`
  ADD CONSTRAINT `partner_transactions_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`),
  ADD CONSTRAINT `partner_transactions_ibfk_2` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  ADD CONSTRAINT `partner_transactions_ibfk_3` FOREIGN KEY (`repayment_id`) REFERENCES `repayments` (`id`),
  ADD CONSTRAINT `partner_transactions_ibfk_4` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  ADD CONSTRAINT `pt_investment_funding_fk` FOREIGN KEY (`investment_funding_id`) REFERENCES `investment_fundings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD CONSTRAINT `payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_methods_ibfk_2` FOREIGN KEY (`method_type_id`) REFERENCES `payment_method_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recovery_actions`
--
ALTER TABLE `recovery_actions`
  ADD CONSTRAINT `recovery_actions_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_actions_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `recovery_actions_ibfk_3` FOREIGN KEY (`action_type_id`) REFERENCES `action_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_actions_ibfk_4` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recovery_case_notes`
--
ALTER TABLE `recovery_case_notes`
  ADD CONSTRAINT `recovery_case_notes_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_case_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recovery_documents`
--
ALTER TABLE `recovery_documents`
  ADD CONSTRAINT `recovery_documents_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_documents_ibfk_2` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_documents_ibfk_3` FOREIGN KEY (`document_status_id`) REFERENCES `document_statuses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `recovery_documents_ibfk_4` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recovery_installments`
--
ALTER TABLE `recovery_installments`
  ADD CONSTRAINT `recovery_installments_ibfk_1` FOREIGN KEY (`payment_plan_id`) REFERENCES `recovery_payment_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recovery_payment_plans`
--
ALTER TABLE `recovery_payment_plans`
  ADD CONSTRAINT `recovery_payment_plans_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recovery_payment_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `recovery_payment_plans_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `recovery_performance_metrics`
--
ALTER TABLE `recovery_performance_metrics`
  ADD CONSTRAINT `recovery_performance_metrics_ibfk_1` FOREIGN KEY (`officer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recovery_templates`
--
ALTER TABLE `recovery_templates`
  ADD CONSTRAINT `recovery_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `repayments`
--
ALTER TABLE `repayments`
  ADD CONSTRAINT `fk_repayments_loan_cycle` FOREIGN KEY (`loan_cycle_id`) REFERENCES `loan_cycles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `repayments_ibfk_1` FOREIGN KEY (`partner_transaction_id`) REFERENCES `partner_transactions` (`id`),
  ADD CONSTRAINT `repayments_ibfk_2` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`),
  ADD CONSTRAINT `repayments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skip_tracing`
--
ALTER TABLE `skip_tracing`
  ADD CONSTRAINT `skip_tracing_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `skip_tracing_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `debt_recovery_cases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `skip_tracing_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tellers`
--
ALTER TABLE `tellers`
  ADD CONSTRAINT `tellers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transfers`
--
ALTER TABLE `transfers`
  ADD CONSTRAINT `transfers_deposit_id_foreign` FOREIGN KEY (`deposit_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transfers_withdraw_id_foreign` FOREIGN KEY (`withdraw_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_profiles_ibfk_2` FOREIGN KEY (`residence_type_id`) REFERENCES `residence_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `user_profiles_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
