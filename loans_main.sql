-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 26, 2026 at 04:21 PM
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
(63, NULL, 40, '0', 1, '2025-07-14 11:35:57', '2025-07-14 11:35:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
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
(77, NULL, 13, '0', 1, '2025-10-23 19:15:21', '2025-10-23 19:15:21', 'Employment', 130000.00, 100000.00, NULL, 'Kisumu', 'iPas', 'info@ipas.org', 'Senior Accountant', 'Accounts and Finance'),
(78, NULL, 56, 'individual', 1, '2025-10-24 03:31:10', '2026-02-01 19:45:11', 'Government', NULL, NULL, 'Dept Head Communications', 'City Hall', 'County Government Nairobi', 'info@nairobi.go.ke', 'Chief Officer/Director', 'Executive'),
(79, NULL, 57, '0', 0, '2025-10-24 09:49:49', '2025-10-24 13:12:24', 'Business', 1768.00, 1226.00, 'Personal Assistant', 'Kileleshwa, Kangundo rd', 'Relay Services', 'yeggynick@gmail.com', 'Chief Executive Officer', 'office of the ceo'),
(80, NULL, 58, 'individual', 0, '2025-10-25 13:22:18', '2026-04-26 06:57:39', 'employed', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(81, NULL, 59, '0', 1, '2025-10-27 13:40:41', '2025-10-27 13:59:08', 'Employment', 1200.00, 1200.00, 'Business development manager', '24 markets', 'Julie', 'juliekwa@gmail.com', 'Group of head partnerships', 'Trading'),
(82, NULL, 21, '0', 1, '2025-10-29 09:45:23', '2025-10-29 09:48:42', 'Employment', 68500.00, 50000.00, 'Engineer', 'Nairobi', 'Paralgin', 'sales@paralgin.co.ke', 'Sir', 'Engineering'),
(83, NULL, 62, 'individual', 1, '2025-12-22 10:11:02', '2026-02-03 05:07:58', 'Employment', 90000.00, 65000.00, 'Project Management Consultant', 'Swarm Initiative', 'Swarm Initiative', 'operations@learnersway.co.ke', 'Head of operations', 'Operations'),
(84, NULL, 63, '0', 0, '2025-12-22 10:31:24', '2025-12-22 10:31:24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(85, NULL, 64, '0', 1, '2026-01-20 09:03:36', '2026-04-14 11:15:46', 'Government', 80000.00, 60000.00, 'Admin Assistant', 'JK Executive Solutions', 'JK Executive Solutions', 'jkexecutivesolutions@gmail.com', 'Ms', 'Management'),
(86, NULL, 65, 'individual', 0, '2026-01-21 06:27:38', '2026-01-29 09:48:32', 'employed', 450000.00, 300000.00, 'Communications Officer', 'Public Service', 'Government of Kenya', NULL, NULL, 'Communication'),
(87, NULL, 66, '0', 0, '2026-01-21 07:15:44', '2026-01-21 07:15:44', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(88, NULL, 67, 'individual', 0, '2026-02-18 08:27:11', '2026-02-18 08:50:25', 'Employment', NULL, 100000.00, 'COO', NULL, 'Sichangi', NULL, NULL, NULL),
(89, NULL, 68, '0', 0, '2026-02-18 08:56:28', '2026-02-18 08:56:28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, NULL, 69, '0', 1, '2026-02-18 14:51:36', '2026-02-19 10:09:08', 'Employment', NULL, 45000.00, 'ICT officer', 'Nyayo House', 'Directorate of eCitizen', NULL, 'Immigration', NULL),
(91, NULL, 70, '0', 1, '2026-02-23 13:47:01', '2026-02-23 14:09:18', 'Government', NULL, 51000.00, 'ICT officer', 'Nyayo House', 'Immigration', 'nigel.yegon@ecitizen.go.ke', 'eCitizen', 'eCitizen'),
(92, NULL, 71, '0', 0, '2026-03-06 04:57:07', '2026-03-07 11:48:57', 'Business', 350000.00, 250000.00, 'GIS Consultant', 'Nairobi', 'ExceedIT Systems Limited', 'info@exceedit.co.ke', 'Private Limited Company', 'Environment and Climate Change'),
(93, NULL, 72, '0', 0, '2026-03-16 09:02:56', '2026-03-16 09:02:56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache_deborahmurgor7@gmail.com|105.164.45.137', 'i:3;', 1771408103),
('laravel_cache_deborahmurgor7@gmail.com|105.164.45.137:timer', 'i:1771408103;', 1771408103);

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
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction` varchar(255) DEFAULT NULL,
  `mode` varchar(255) NOT NULL,
  `disburse_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `loan_id`, `amount`, `transaction`, `mode`, `disburse_date`, `payment_date`, `created_at`, `updated_at`) VALUES
(1, 1, 80000.00, '089092507904', 'bank_transfer', '2025-03-07', '2025-03-17', '2025-04-17 12:15:36', '2025-04-17 12:15:36'),
(2, 1, 40000.00, '774132228868', 'bank_transfer', '2025-03-10', '2025-03-17', '2025-04-17 12:17:59', '2025-04-17 12:17:59'),
(3, 2, 30000.00, '089092507904', 'bank_transfer', '2025-03-07', '2025-03-17', '2025-04-17 12:23:21', '2025-04-17 12:23:21'),
(4, 5, 10000.00, 'TC327JWW4I', 'bank_transfer', '2025-03-02', '2025-04-02', '2025-04-17 13:05:38', '2025-04-17 13:05:38'),
(5, 7, 2000.00, 'TDA285BOZO', 'bank_transfer', '2025-04-10', '2025-04-20', '2025-04-17 13:21:15', '2025-04-17 13:21:15'),
(6, 8, 10000.00, 'TDC6HFHSZO', 'bank_transfer', '2025-04-12', '2025-04-22', '2025-04-17 13:24:20', '2025-04-17 13:24:20'),
(7, 10, 30000.00, 'TDG1YOIU2X', 'bank_transfer', '2025-04-16', '2025-04-26', '2025-04-17 13:28:18', '2025-04-17 13:28:18'),
(8, 11, 15000.00, 'TD88X9BH3K', 'bank_transfer', '2025-04-08', '2025-04-18', '2025-04-17 13:30:49', '2025-04-17 13:30:49'),
(9, 12, 24000.00, '395833072695', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-17 14:09:26', '2025-04-17 14:09:26'),
(10, 12, 3000.00, 'TDG6XXEGTQ', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-17 14:10:02', '2025-04-17 14:10:02'),
(11, 13, 30000.00, 'TDD7JVSCKR', 'bank_transfer', '2025-04-13', '2025-04-23', '2025-04-17 14:34:42', '2025-04-17 14:34:42'),
(12, 4, 35000.00, '217335052085', 'bank_transfer', '2025-03-31', '2025-04-09', '2025-04-17 15:53:10', '2025-04-17 15:53:10'),
(13, 3, 50000.00, '558970234000', 'bank_transfer', '2025-03-18', '2025-04-28', '2025-04-19 17:52:00', '2025-04-19 17:52:00'),
(14, 14, 20000.00, 'TCP62JTJXE', 'bank_transfer', '2025-03-25', '2025-04-05', '2025-04-20 12:28:49', '2025-04-20 12:28:49'),
(15, 15, 50000.00, 'TCP4ZP7SRW', 'bank_transfer', '2025-03-25', '2025-04-03', '2025-04-20 12:43:18', '2025-04-20 12:43:18'),
(16, 16, 50000.00, '292498090877', 'bank_transfer', '2025-04-01', '2025-04-03', '2025-04-20 12:44:03', '2025-04-20 12:44:03'),
(17, 18, 35000.00, '217335052085', 'bank_transfer', '2025-03-31', '2025-04-09', '2025-04-20 13:27:14', '2025-04-20 13:27:14'),
(18, 19, 20000.00, 'TD75RQ3FKJ', 'bank_transfer', '2025-04-07', '2025-04-17', '2025-04-20 13:46:23', '2025-04-20 13:46:23'),
(19, 20, 5000.00, 'TDB3BIBKHB', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:02:11', '2025-04-20 14:02:11'),
(20, 20, 28000.00, 'CARRY FORWARD', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:02:34', '2025-04-20 14:02:34'),
(21, 21, 42000.00, 'CARRY FORWARD', 'bank_transfer', '2025-04-15', '2025-04-25', '2025-04-20 14:03:16', '2025-04-20 14:03:16'),
(22, 22, 10000.00, 'TBO7A3XVLJ', 'bank_transfer', '2025-03-05', '2025-03-10', '2025-04-20 16:21:15', '2025-04-20 16:21:15'),
(23, 23, 5000.00, 'TBQ0LAB684', 'bank_transfer', '2025-02-26', '2025-03-06', '2025-04-20 16:22:38', '2025-04-20 16:22:38'),
(24, 31, 50000.00, 'TD77RZENUN', 'bank_transfer', '2025-04-07', '2025-04-17', '2025-04-20 17:16:41', '2025-04-20 17:16:41'),
(25, 32, 8000.00, '924920143186', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-04-20 17:23:18', '2025-04-20 17:23:18'),
(27, 32, 11000.00, 'TD41DEBIRX', 'bank_transfer', '2025-04-05', '2025-06-04', '2025-04-20 17:27:58', '2025-04-20 17:27:58'),
(28, 28, 50000.00, '931800872968', 'bank_transfer', '2025-04-04', '2025-04-14', '2025-04-20 17:34:03', '2025-04-20 17:34:03'),
(29, 25, 1500.00, '953302924475', 'bank_transfer', '2025-03-17', '2025-04-17', '2025-04-20 17:51:03', '2025-04-20 17:51:03'),
(30, 25, 2000.00, '631607883431', 'bank_transfer', '2025-03-10', '2025-03-10', '2025-04-20 17:52:34', '2025-04-20 17:52:34'),
(31, 25, 7500.00, 'NA', 'bank_transfer', '2025-03-27', '2025-04-27', '2025-04-20 17:56:30', '2025-04-20 17:56:30'),
(32, 17, 20000.00, 'ROLL OVER', 'bank_transfer', '2025-04-12', '2025-04-25', '2025-04-21 06:13:22', '2025-04-21 06:13:22'),
(33, 33, 10000.00, '201606029320', 'bank_transfer', '2025-03-07', '2025-04-07', '2025-04-21 06:44:17', '2025-04-21 06:44:17'),
(34, 29, 11000.00, 'TD48EV29NE', 'bank_transfer', '2025-04-04', '2025-05-05', '2025-04-21 06:46:22', '2025-04-21 06:46:22'),
(35, 36, 44000.00, 'ROLL OVER', 'bank_transfer', '2025-03-21', '2025-04-01', '2025-04-21 09:03:06', '2025-04-21 09:03:06'),
(36, 26, 600.00, 'I&M Bank', 'bank_transfer', '2025-04-21', '2025-05-21', '2025-04-21 10:06:22', '2025-04-21 10:06:22'),
(37, 34, 9000.00, 'TC72SFKDOC', 'bank_transfer', '2025-03-07', '2025-04-07', '2025-04-21 10:15:26', '2025-04-21 10:15:26'),
(38, 35, 18000.00, 'TD88X9BH3K', 'bank_transfer', '2025-04-08', '2025-04-18', '2025-04-26 12:18:51', '2025-04-26 12:18:51'),
(39, 38, 50000.00, 'TDO6ZOZCH8', 'bank_transfer', '2025-04-24', '2025-05-04', '2025-04-27 06:13:42', '2025-04-27 06:13:42'),
(40, 9, 15000.00, 'TDH944K3NZ', 'bank_transfer', '2025-04-17', '2025-04-27', '2025-04-27 06:24:40', '2025-04-27 06:24:40'),
(41, 37, 5000.00, 'TDP54QZWX5', 'bank_transfer', '2025-04-25', '2025-05-04', '2025-04-27 06:35:50', '2025-04-27 06:35:50'),
(42, 39, 15000.00, 'ROLL OVER', 'bank_transfer', '2025-04-27', '2025-05-06', '2025-04-27 06:36:44', '2025-04-27 06:36:44'),
(43, 45, 50000.00, 'TDT4NOO6SO', 'bank_transfer', '2025-04-29', '2025-05-08', '2025-04-29 06:15:33', '2025-04-29 06:15:33'),
(44, 46, 50000.00, 'ROLL OVER', 'bank_transfer', '2025-05-03', '2025-05-13', '2025-05-02 19:35:39', '2025-05-02 19:35:39'),
(45, 24, 5000.00, 'TC81UVVMLL', 'bank_transfer', '2025-03-08', '2025-04-08', '2025-05-05 09:54:54', '2025-05-05 09:54:54'),
(46, 30, 6000.00, 'TD40EV16DK', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-05 09:57:17', '2025-05-05 09:57:17'),
(47, 27, 2000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-03-15', '2025-04-15', '2025-05-05 19:10:42', '2025-05-05 19:10:42'),
(48, 41, 8000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-05 19:24:01', '2025-05-05 19:24:01'),
(49, 49, 50000.00, 'ROLL OVER', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-06 08:08:58', '2025-05-06 08:08:58'),
(50, 48, 5000.00, 'ROLL OVER', 'bank_transfer', '2025-05-05', '2025-05-15', '2025-05-06 08:10:53', '2025-05-06 08:10:53'),
(51, 55, 15000.00, 'ROLL OVER', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-06 08:11:13', '2025-05-06 08:11:13'),
(52, 50, 100000.00, '708629756357', 'bank_transfer', '2025-05-06', '2025-05-16', '2025-05-06 08:19:23', '2025-05-06 08:19:23'),
(53, 6, 13000.00, 'TD51HKJVG3', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-06 08:47:13', '2025-05-06 08:47:13'),
(54, 52, 8000.00, 'TE69MVN0YJ', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-07 12:08:49', '2025-05-07 12:08:49'),
(55, 54, 10000.00, '870655202723', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-07 12:11:21', '2025-05-07 12:11:21'),
(56, 56, 20000.00, '861188683363', 'bank_transfer', '2025-05-07', '2025-05-17', '2025-05-07 14:55:21', '2025-05-07 14:55:21'),
(57, 56, 10000.00, '033759695090', 'bank_transfer', '2025-05-09', '2025-05-17', '2025-05-09 13:12:08', '2025-05-09 13:12:08'),
(58, 57, 20000.00, '033759695090', 'bank_transfer', '2025-05-10', '2025-05-15', '2025-05-10 10:39:09', '2025-05-10 10:39:09'),
(59, 57, 5000.00, '395608495147', 'bank_transfer', '2025-05-10', '2025-05-20', '2025-05-11 19:39:14', '2025-05-11 19:39:14'),
(60, 51, 15600.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-04', '2025-05-14', '2025-05-12 09:09:46', '2025-05-12 09:09:46'),
(61, 60, 10000.00, 'TED5J8LN27', 'bank_transfer', '2025-05-13', '2025-05-23', '2025-05-13 06:52:42', '2025-05-13 06:52:42'),
(62, 60, 40000.00, 'TEE8PETPNM', 'bank_transfer', '2025-05-14', '2025-05-24', '2025-05-15 07:47:47', '2025-05-15 07:47:47'),
(63, 64, 18720.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-15', '2025-06-15', '2025-05-15 07:51:38', '2025-05-15 07:51:38'),
(64, 61, 72000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-15', '2025-05-25', '2025-05-15 07:53:56', '2025-05-15 07:53:56'),
(65, 65, 3500.00, 'TEE3PLCZMX', 'bank_transfer', '2025-05-14', '2025-05-28', '2025-05-15 07:58:42', '2025-05-15 07:58:42'),
(66, 65, 7500.00, 'TEE9PLMODT', 'bank_transfer', '2025-05-14', '2025-05-28', '2025-05-15 07:59:10', '2025-05-15 07:59:10'),
(67, 65, 2000.00, 'TEF3T3HX6F', 'bank_transfer', '2025-05-15', '2025-05-28', '2025-05-15 08:06:48', '2025-05-15 08:06:48'),
(68, 66, 2500.00, 'TEE7POIQX1', 'bank_transfer', '2025-05-14', '2025-05-25', '2025-05-15 08:11:40', '2025-05-15 08:11:40'),
(69, 59, 78000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-12', '2025-05-22', '2025-05-18 08:53:52', '2025-05-18 08:53:52'),
(70, 69, 50000.00, '656947222762', 'bank_transfer', '2025-05-19', '2025-06-02', '2025-05-19 12:48:06', '2025-05-19 12:48:06'),
(71, 70, 50000.00, 'TEK7JGH64Z', 'bank_transfer', '2025-05-20', '2025-06-03', '2025-05-20 13:04:27', '2025-05-20 13:04:27'),
(72, 57, 20000.00, 'BROKER', 'bank_transfer', '2025-05-10', '2025-05-20', '2025-05-22 05:25:22', '2025-05-22 05:25:22'),
(73, 71, 15000.00, 'I&M BANK', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 10:08:25', '2025-05-26 10:08:25'),
(74, 72, 27840.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 10:33:49', '2025-05-26 10:33:49'),
(75, 73, 10000.00, 'TEP73R8FOP', 'bank_transfer', '2025-05-24', '2025-06-03', '2025-05-26 11:53:15', '2025-05-26 11:53:15'),
(76, 74, 50000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-26', '2025-06-05', '2025-05-26 18:05:53', '2025-05-26 18:05:53'),
(77, 75, 30000.00, 'TER3DM6HRD', 'Mpesa', '2025-05-27', '2025-06-27', '2025-05-27 06:22:50', '2025-05-28 16:24:08'),
(78, 76, 15000.00, 'TER5G1S7T9', 'bank_transfer', '2025-05-27', '2025-06-10', '2025-05-27 13:52:11', '2025-05-27 13:52:11'),
(79, 32, 10000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-04-04', '2025-05-04', '2025-05-27 16:54:01', '2025-05-27 16:54:01'),
(80, 68, 10000.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-17', '2025-05-27', '2025-05-27 16:55:40', '2025-05-27 16:55:40'),
(81, 40, 15600.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-06', '2025-06-06', '2025-05-27 16:57:04', '2025-05-27 16:57:04'),
(82, 77, 30000.00, 'TET8NAA9S8', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-29 07:39:23', '2025-05-29 07:39:23'),
(83, 78, 100000.00, 'TET4ON8Q6C', 'bank_transfer', '2025-05-29', '2025-08-29', '2025-05-29 12:11:50', '2025-05-29 12:11:50'),
(84, 79, 20000.00, 'TET7P9LW5Z', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-29 13:50:53', '2025-05-29 13:50:53'),
(85, 80, 27340.00, 'TEU8TS3A9E', 'Mpesa', '2025-05-30', '2025-06-30', '2025-05-30 15:30:37', '2025-05-30 15:31:08'),
(86, 80, 2660.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-30', '2025-06-30', '2025-05-30 15:31:43', '2025-05-30 15:31:43'),
(87, 82, 30000.00, 'TEV8WMXR72', 'bank_transfer', '2025-05-31', '2025-06-09', '2025-05-31 09:42:00', '2025-05-31 09:42:00'),
(88, 83, 8200.00, 'CREDIT DISCOUNT', 'bank_transfer', '2025-05-29', '2025-06-08', '2025-05-31 11:17:58', '2025-05-31 11:17:58'),
(89, 84, 20000.00, 'TF258AV9LV', 'bank_transfer', '2025-06-02', '2025-06-16', '2025-06-02 11:20:46', '2025-06-02 11:20:46'),
(90, 85, 50000.00, '004637299558', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 07:26:03', '2025-06-03 07:26:03'),
(91, 86, 10000.00, 'ROLL OVER', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 08:12:46', '2025-06-03 08:12:46'),
(92, 79, 10000.00, 'TF34EJ4JV2', 'bank_transfer', '2025-06-03', '2025-06-08', '2025-06-03 15:40:48', '2025-06-03 15:40:48'),
(93, 87, 50000.00, 'ROLL OVER', 'bank_transfer', '2025-06-04', '2025-06-18', '2025-06-03 15:43:43', '2025-06-03 15:43:43'),
(94, 85, 50000.00, '611992535256', 'bank_transfer', '2025-06-03', '2025-06-13', '2025-06-03 19:06:07', '2025-06-03 19:06:07'),
(95, 88, 18000.00, 'CREDIT DISCOUNT', 'cash', '2025-06-06', '2025-06-16', '2025-06-06 08:01:13', '2025-06-06 08:01:13'),
(96, 88, 1500.00, 'TF55OO6MNL', 'bank_transfer', '2025-06-05', '2025-06-16', '2025-06-06 08:01:55', '2025-06-06 08:01:55'),
(97, 88, 8500.00, 'to update', 'Mpesa', '2025-06-06', '2025-06-16', '2025-06-06 08:03:55', '2025-06-07 09:43:30'),
(98, 79, 5000.00, 'TF75W10V2T', 'bank_transfer', '2025-06-07', '2025-06-08', '2025-06-07 09:46:01', '2025-06-07 09:46:01'),
(99, 90, 39600.00, 'ROLL OVER', 'cash', '2025-06-09', '2025-06-19', '2025-06-10 05:09:19', '2025-06-10 05:09:19'),
(100, 91, 15000.00, 'TFE9WO49JV', 'Mpesa', '2025-06-14', '2025-06-24', '2025-06-15 14:50:21', '2025-07-15 11:47:48'),
(101, 93, 17000.00, 'TFD3PFNLOP', 'cash', '2025-06-13', '2025-06-23', '2025-06-15 13:51:14', '2025-06-15 13:51:14'),
(102, 92, 10000.00, 'ROLL OVER', 'cash', '2025-06-13', '2025-06-23', '2025-06-15 13:52:15', '2025-06-15 13:52:15'),
(103, 89, 8280.00, 'ROLL OVER', 'Mpesa', '2025-06-08', '2025-06-18', '2025-06-15 13:54:20', '2025-06-20 07:02:39'),
(104, 94, 4950.00, 'TFC5M5SV59', 'cash', '2025-06-12', '2025-06-12', '2025-06-15 13:57:45', '2025-06-15 13:57:45'),
(105, 94, 50.00, 'TFF2179XC0', 'Mpesa', '2025-06-15', '2025-06-22', '2025-06-15 14:03:08', '2025-06-15 14:06:12'),
(106, 96, 33600.00, 'ROLL OVER', 'Cash', '2025-06-18', '2025-06-29', '2025-06-18 09:59:17', '2025-06-18 10:01:05'),
(107, 96, 47520.00, 'ROLL OVER', 'cash', '2025-06-19', '2025-06-29', '2025-06-18 09:59:54', '2025-06-18 09:59:54'),
(108, 95, 2264.00, 'ROLL OVER', 'cash', '2025-06-18', '2025-07-18', '2025-06-18 14:04:44', '2025-06-18 14:04:44'),
(109, 98, 14000.00, 'TFL9QT05P9', 'Mpesa', '2025-06-21', '2025-07-04', '2025-06-22 15:14:45', '2025-06-22 15:15:31'),
(110, 98, 4000.00, 'TFK9PL1O67', 'bank_transfer', '2025-06-20', '2025-07-04', '2025-06-22 15:15:20', '2025-06-22 15:15:20'),
(111, 98, 5000.00, 'TFL9R15GV9', 'bank_transfer', '2025-06-21', '2025-07-04', '2025-06-22 15:16:20', '2025-06-22 15:16:20'),
(112, 98, 5000.00, 'TFL1S1NODH', 'bank_transfer', '2025-06-21', '2025-07-04', '2025-06-22 15:16:26', '2025-06-22 15:16:26'),
(113, 100, 5000.00, 'TFM1Y12185', 'cash', '2025-06-22', '2025-07-02', '2025-06-23 10:28:41', '2025-06-23 10:28:41'),
(114, 101, 10000.00, 'ROLL OVER', 'bank_transfer', '2025-06-24', '2025-07-04', '2025-06-24 06:22:21', '2025-06-24 06:22:21'),
(115, 102, 10000.00, 'ROLL OVER', 'cash', '2025-06-24', '2025-07-04', '2025-06-25 10:45:03', '2025-06-25 10:45:03'),
(116, 103, 5000.00, 'ROLL OVER', 'cash', '2025-06-27', '2025-07-08', '2025-06-27 17:20:56', '2025-06-27 17:20:56'),
(117, 104, 5000.00, 'TFT5VGPQL1', 'bank_transfer', '2025-06-29', '2025-07-09', '2025-06-30 07:12:08', '2025-06-30 07:12:08'),
(118, 105, 120000.00, 'TFU7YU7PK1', 'bank_transfer', '2025-06-30', '2025-07-10', '2025-06-30 08:28:48', '2025-06-30 08:28:48'),
(119, 106, 3936.00, 'ROLL OVER', 'cash', '2025-06-18', '2025-06-28', '2025-06-30 08:33:26', '2025-06-30 08:33:26'),
(120, 107, 30000.00, 'ROLL OVER', 'cash', '2025-06-30', '2025-07-30', '2025-07-01 07:30:22', '2025-07-01 07:30:22'),
(121, 108, 10000.00, 'ROLL OVER', 'cash', '2025-07-05', '2025-07-15', '2025-07-05 05:39:18', '2025-07-05 05:39:18'),
(122, 109, 12000.00, 'ROLL OVER', 'cash', '2025-07-05', '2025-07-15', '2025-07-05 05:41:12', '2025-07-05 05:41:12'),
(123, 110, 10000.00, 'TG74XZW748', 'cash', '2025-07-07', '2025-07-17', '2025-07-07 08:52:33', '2025-07-07 08:52:33'),
(124, 111, 8000.00, 'TG72XWWD34', 'bank_transfer', '2025-07-07', '2025-07-17', '2025-07-07 08:53:35', '2025-07-07 08:53:35'),
(125, 112, 10000.00, 'TG8845IBKA', 'bank_transfer', '2025-07-08', '2025-07-18', '2025-07-08 08:30:37', '2025-07-08 08:30:37'),
(126, 113, 10000.00, 'TG886TM5S8', 'bank_transfer', '2025-07-08', '2025-07-18', '2025-07-08 16:29:32', '2025-07-08 16:29:32'),
(127, 114, 6000.00, 'ROLL OVER', 'cash', '2025-07-10', '2025-07-20', '2025-07-10 04:42:47', '2025-07-10 04:42:47'),
(129, 115, 97344.00, 'ROLL OVER', 'cash', '2025-06-30', '0025-07-10', '2025-07-10 04:55:55', '2025-07-10 04:55:55'),
(130, 116, 39000.00, 'ROLL OVER', 'cash', '2025-07-02', '2025-08-02', '2025-07-10 05:06:13', '2025-07-10 05:06:13'),
(131, 117, 120000.00, 'ROLL OVER', 'cash', '2025-07-11', '2025-07-21', '2025-07-13 16:06:56', '2025-07-13 16:06:56'),
(132, 118, 2000.00, 'TGC5Q6M32B', 'bank_transfer', '2025-07-12', '2025-07-22', '2025-07-13 16:12:43', '2025-07-13 16:12:43'),
(133, 119, 1000.00, 'TGE5Y2T5OB', 'bank_transfer', '2025-07-14', '2025-07-24', '2025-07-14 11:37:26', '2025-07-14 11:37:26'),
(134, 120, 25000.00, 'TGF33QRGEH', 'bank_transfer', '2025-07-15', '2025-07-29', '2025-07-15 11:05:03', '2025-07-15 11:05:03'),
(135, 121, 70000.00, '917753788306', 'bank_transfer', '2025-07-15', '2025-07-29', '2025-07-15 11:06:40', '2025-07-15 11:06:40'),
(136, 122, 14400.00, 'ROLL OVER', 'cash', '2025-07-15', '2025-07-25', '2025-07-17 08:16:54', '2025-07-17 08:16:54'),
(137, 123, 42900.00, 'ROLL OVER', 'cash', '2025-07-14', '2025-07-28', '2025-07-17 08:24:01', '2025-07-17 08:24:01'),
(138, 124, 56628.00, 'ROLL OVER', 'cash', '2025-07-14', '2025-07-28', '2025-07-17 08:27:43', '2025-07-17 08:27:43'),
(139, 125, 10000.00, 'ROLL OVER', 'cash', '2025-07-17', '2025-07-27', '2025-07-17 09:52:26', '2025-07-17 09:52:26'),
(140, 97, 50000.00, 'ROLL OVER', 'cash', '2025-06-18', '2025-07-18', '2025-07-17 09:53:12', '2025-07-17 09:53:12'),
(141, 126, 5000.00, 'TGG08OUQHU', 'cash', '2025-07-16', '2025-07-26', '2025-07-17 09:56:54', '2025-07-17 09:56:54'),
(142, 127, 20000.00, 'TGH9DWFM6L', 'bank_transfer', '2025-07-17', '2025-07-27', '2025-07-18 08:34:36', '2025-07-18 08:34:36'),
(143, 128, 10000.00, 'ROLL OVER', 'cash', '2025-07-18', '2025-07-28', '2025-07-18 08:36:34', '2025-07-18 08:36:34'),
(144, 129, 7200.00, 'ROLL OVER', 'cash', '2025-07-21', '2025-07-31', '2025-07-21 06:45:05', '2025-07-21 06:45:05'),
(145, 130, 17280.00, 'ROLL OVER', 'cash', '2025-07-25', '2025-08-05', '2025-07-27 09:00:46', '2025-07-27 09:00:46'),
(146, 131, 4000.00, 'TGM2ZLSC7O', 'bank_transfer', '2025-07-20', '2025-08-01', '2025-07-28 14:11:15', '2025-07-28 14:11:15'),
(147, 134, 30000.00, 'ROLL OVER', 'cash', '2025-07-30', '2025-08-31', '2025-07-30 07:39:59', '2025-07-30 07:39:59'),
(148, 135, 2400.00, 'ROLL OVER', 'cash', '2025-07-29', '2025-08-09', '2025-07-30 14:22:07', '2025-07-30 14:22:07'),
(149, 136, 12000.00, 'ROLL OVER', 'cash', '2025-07-28', '2025-08-08', '2025-07-30 15:35:17', '2025-07-30 15:35:17'),
(150, 137, 35000.00, 'TGU25NSDJE', 'bank_transfer', '2025-07-30', '2025-08-09', '2025-07-31 08:46:01', '2025-07-31 08:46:01'),
(151, 138, 45000.00, 'TH16EFCEEM', 'bank_transfer', '2025-08-01', '2025-09-01', '2025-08-01 07:01:43', '2025-08-01 07:01:43'),
(152, 139, 30000.00, '028150735663', 'bank_transfer', '2025-07-31', '2025-08-31', '2025-08-01 10:18:09', '2025-08-01 10:18:09'),
(153, 140, 3000.00, 'TH27MT6MTT', 'bank_transfer', '2025-08-02', '2025-08-12', '2025-08-02 16:04:51', '2025-08-02 16:04:51'),
(154, 141, 6000.00, 'ROLL OVER', 'Mpesa', '2025-08-04', '2025-08-14', '2025-08-05 08:12:07', '2025-08-15 05:31:45'),
(155, 142, 3000.00, 'ROLL OVER', 'cash', '2025-07-27', '2025-08-06', '2025-08-06 05:43:16', '2025-08-06 05:43:16'),
(156, 143, 30000.00, 'ROLL OVER', 'Mpesa', '2025-07-30', '2025-08-13', '2025-08-06 11:02:51', '2025-08-06 11:03:28'),
(157, 144, 50000.00, 'TH654PRH61', 'Bank', '2025-08-06', '2025-08-20', '2025-08-06 11:26:52', '2025-08-06 11:27:04'),
(158, 144, 60000.00, 'TH6151984J', 'bank_transfer', '2025-08-06', '2025-08-20', '2025-08-06 11:27:40', '2025-08-06 11:27:40'),
(159, 145, 3000.00, 'MPESA', 'bank_transfer', '2025-08-06', '2025-09-06', '2025-08-07 03:43:10', '2025-08-07 03:43:10'),
(160, 144, 150000.00, 'TH71BUDC7N', 'bank_transfer', '2025-08-07', '2025-08-21', '2025-08-07 11:19:46', '2025-08-07 11:19:46'),
(161, 146, 14400.00, 'ROLL OVER', 'cash', '2025-08-08', '2025-08-18', '2025-08-07 17:09:22', '2025-08-07 17:09:22'),
(162, 147, 67954.00, 'ROLL OVER', 'cash', '2025-07-29', '2025-08-11', '2025-08-07 18:48:37', '2025-08-07 18:48:37'),
(163, 144, 40000.00, 'TH82GJ735C', 'bank_transfer', '2025-08-08', '2025-08-20', '2025-08-08 10:24:16', '2025-08-08 10:24:16'),
(164, 146, 2000.00, 'TH87GJ96CZ', 'bank_transfer', '2025-08-08', '2025-08-18', '2025-08-08 10:26:40', '2025-08-08 10:26:40'),
(165, 148, 10000.00, 'TH73ARDOL3', 'bank_transfer', '2025-08-07', '2025-08-17', '2025-08-08 10:42:00', '2025-08-08 10:42:00'),
(166, 149, 10000.00, 'TH91M02HW3', 'bank_transfer', '2025-08-09', '2025-08-19', '2025-08-09 10:26:30', '2025-08-09 10:26:30'),
(167, 148, 5000.00, 'TH93M0B8ZR', 'bank_transfer', '2025-08-09', '2025-08-19', '2025-08-09 10:27:20', '2025-08-09 10:27:20'),
(168, 150, 126548.00, 'ROLL OVER', 'cash', '2025-07-31', '2025-08-31', '2025-08-11 08:06:54', '2025-08-11 08:06:54'),
(169, 151, 66545.00, 'ROLL OVER', 'cash', '2025-08-12', '2025-08-26', '2025-08-12 06:52:44', '2025-08-12 06:52:44'),
(170, 152, 50700.00, 'ROLL OVER', 'cash', '2025-08-03', '2025-09-03', '2025-08-12 06:56:10', '2025-08-12 06:56:10'),
(171, 158, 1600.00, 'ROLL OVER', 'cash', '2025-08-06', '2025-08-16', '2025-08-14 10:12:14', '2025-08-14 10:12:14'),
(173, 159, 6000.00, 'ROLL OVER', 'cash', '2025-08-14', '2025-08-24', '2025-08-15 05:34:47', '2025-08-15 05:34:47'),
(174, 161, 10000.00, 'THF9HDYCIF', 'bank_transfer', '2025-08-15', '2025-08-22', '2025-08-15 12:22:47', '2025-08-15 12:22:47'),
(175, 137, 2500.00, 'THI2XVXDZC', 'Mpesa', '2025-08-19', '2025-08-19', '2025-08-19 07:58:17', '2025-08-19 07:59:16'),
(176, 161, 30000.00, 'THJ71BMJLX', 'bank_transfer', '2025-08-19', '2025-08-22', '2025-08-19 10:50:33', '2025-08-19 10:50:33'),
(177, 163, 19680.00, 'ROLL OVER', 'cash', '2025-08-19', '2025-08-29', '2025-08-20 07:28:57', '2025-08-20 07:28:57'),
(178, 164, 18000.00, 'ROLL OVER', 'cash', '2025-08-20', '2025-08-30', '2025-08-21 09:22:46', '2025-08-21 09:22:46'),
(179, 161, 5000.00, 'THL1AB5SIJ', 'bank_transfer', '2025-08-21', '2025-08-29', '2025-08-21 09:23:57', '2025-08-21 09:23:57'),
(180, 161, 5000.00, 'THM7FTOHU7', 'bank_transfer', '2025-08-22', '2025-09-15', '2025-08-22 12:53:08', '2025-08-22 12:53:08'),
(181, 156, 10000.00, 'THM3FTUGBR', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:01:00', '2025-08-22 13:01:00'),
(182, 165, 10000.00, 'THM1GDWOKL', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:02:12', '2025-08-22 13:02:12'),
(183, 165, 5000.00, 'THM1EJPTW7', 'bank_transfer', '2025-08-22', '2025-09-01', '2025-08-22 13:02:39', '2025-08-22 13:02:39'),
(184, 162, 1920.00, 'CREDIT DISCOUNT', 'cash', '2025-08-17', '2025-08-27', '2025-08-23 06:29:23', '2025-08-23 06:29:23'),
(185, 166, 7200.00, 'ROLL OVER', 'cash', '2025-08-25', '2025-09-05', '2025-08-28 15:11:05', '2025-08-28 15:11:05'),
(186, 167, 50000.00, 'THS2CLKZUO', 'bank_transfer', '2025-08-28', '2025-09-08', '2025-08-28 15:56:45', '2025-08-28 15:56:45'),
(187, 168, 30000.00, 'ROLL OVER', 'Mpesa', '2025-08-30', '2025-09-30', '2025-09-01 18:27:55', '2025-09-01 18:28:44'),
(188, 169, 42000.00, 'ROLL OVER', 'cash', '2025-08-14', '2025-08-28', '2025-09-01 18:30:49', '2025-09-01 18:30:49'),
(189, 170, 30000.00, 'ROLL OVER', 'cash', '2025-09-01', '2025-10-01', '2025-09-02 04:31:52', '2025-09-02 04:31:52'),
(190, 171, 45000.00, 'ROLL OVER', 'cash', '2025-09-02', '2025-10-02', '2025-09-03 11:14:32', '2025-09-03 11:14:32'),
(191, 172, 21600.00, 'ROLL OVER', 'cash', '2025-08-31', '2025-09-11', '2025-09-04 07:04:08', '2025-09-04 07:04:08'),
(192, 173, 7200.00, 'ROLL OVER', 'cash', '2025-09-05', '2025-09-15', '2025-09-04 07:39:18', '2025-09-04 07:39:18'),
(193, 174, 15000.00, 'TI85XYSRUX', 'bank_transfer', '2025-09-08', '2025-09-18', '2025-09-08 11:05:29', '2025-09-08 11:05:29'),
(194, 175, 12000.00, 'ROLL OVER', 'cash', '2025-09-02', '2025-09-12', '2025-09-08 15:32:00', '2025-09-08 15:32:00'),
(195, 176, 23616.00, 'ROLL OVER', 'cash', '2025-08-30', '2025-08-08', '2025-09-08 15:39:16', '2025-09-08 15:39:16'),
(196, 177, 50600.00, 'ROLL OVER', 'cash', '2025-08-29', '2025-08-13', '2025-09-08 16:36:08', '2025-09-08 16:36:08'),
(197, 178, 65910.00, 'ROLL OVER', 'cash', '2025-09-04', '2025-10-04', '2025-09-08 16:40:16', '2025-09-08 16:40:16'),
(198, 179, 28340.00, 'ROLL OVER', 'cash', '2025-09-10', '2025-09-20', '2025-09-10 02:51:00', '2025-09-10 02:51:00'),
(199, 181, 40000.00, '272763294080', 'cash', '2025-09-10', '2025-09-24', '2025-09-11 10:24:38', '2025-09-11 10:24:38'),
(200, 182, 21600.00, 'ROLL OVER', 'cash', '2025-09-11', '2025-09-21', '2025-09-11 17:53:06', '2025-09-11 17:53:06'),
(201, 183, 18000.00, 'ROLL OVER', 'Mpesa', '2025-09-02', '2025-09-12', '2025-09-12 13:50:07', '2025-09-14 11:06:37'),
(202, 180, 50000.00, 'TIC9LSKIOD', 'bank_transfer', '2025-09-12', '2025-12-04', '2025-09-12 14:28:15', '2025-09-12 14:28:15'),
(203, 184, 10000.00, 'ROLL OVER', 'cash', '2025-09-12', '2025-09-22', '2025-09-13 09:16:09', '2025-09-13 09:16:09'),
(204, 185, 21600.00, 'ROLL OVER', 'cash', '2025-09-12', '2025-09-22', '2025-09-14 11:07:56', '2025-09-14 11:07:56'),
(205, 186, 10000.00, 'TIE2TBFRTQ', 'bank_transfer', '2025-09-13', '2025-10-13', '2025-09-15 06:48:23', '2025-09-15 06:48:23'),
(206, 187, 40000.00, 'TIF9ZOIXFJ', 'bank_transfer', '2025-09-15', '2025-09-25', '2025-09-15 08:45:05', '2025-09-15 08:45:05'),
(207, 171, 8640.00, 'ROLL OVER', 'cash', '2025-09-15', '2025-10-02', '2025-09-16 05:55:06', '2025-09-16 05:55:06'),
(208, 188, 1000.00, 'TI881S6WWE', 'bank_transfer', '2025-09-08', '2025-10-08', '2025-09-16 09:28:28', '2025-09-16 09:28:28'),
(209, 189, 136000.00, '426867260195', 'Mpesa', '2025-09-19', '2025-10-03', '2025-09-19 08:20:10', '2025-09-19 08:22:04'),
(210, 186, 50000.00, 'TIJ8LX4I02', 'bank_transfer', '2025-09-19', '2025-10-20', '2025-09-20 15:18:14', '2025-09-20 15:18:14'),
(211, 190, 3000.00, 'TIL1VWBZHZ', 'bank_transfer', '2025-09-22', '2025-10-01', '2025-09-22 07:06:38', '2025-09-22 07:06:38'),
(212, 191, 34008.00, 'ROLL OVER', 'cash', '2025-09-21', '2025-10-01', '2025-09-23 03:54:35', '2025-09-23 03:54:35'),
(213, 190, 2000.00, 'TIO4A5J94K', 'bank_transfer', '2025-09-24', '2025-10-01', '2025-09-24 07:04:55', '2025-09-24 07:04:55'),
(214, 192, 40000.00, 'ROLL OVER', 'cash', '2025-09-26', '2025-10-03', '2025-09-25 14:55:05', '2025-09-25 14:55:05'),
(215, 190, 5000.00, 'TIP6O5L39G', 'bank_transfer', '2025-09-25', '2025-10-04', '2025-09-25 14:56:13', '2025-09-25 14:56:13'),
(216, 186, 100000.00, 'TIP3X5NHPQ', 'bank_transfer', '2025-09-25', '2025-12-01', '2025-09-25 14:57:29', '2025-09-25 14:57:29'),
(217, 193, 178000.00, '440397553004', 'bank_transfer', '2025-09-27', '2025-10-11', '2025-09-29 11:23:23', '2025-09-29 11:23:23'),
(218, 194, 4000.00, 'TIRGC5T329', 'bank_transfer', '2025-09-27', '2025-10-05', '2025-09-29 11:25:35', '2025-09-29 11:25:35'),
(219, 194, 6000.00, 'TIR6O5QWIU', 'bank_transfer', '2025-09-27', '2025-10-04', '2025-09-29 11:26:06', '2025-09-29 11:26:06'),
(220, 195, 10000.00, 'ROLL OVER', 'cash', '2025-10-02', '2025-10-12', '2025-10-01 07:11:52', '2025-10-01 07:11:52'),
(221, 196, 17000.00, 'ROLL OVER', 'cash', '2025-10-01', '2025-11-01', '2025-10-01 07:22:36', '2025-10-01 07:22:36'),
(222, 197, 23000.00, 'ROLL OVER', 'cash', '2025-10-01', '2025-11-01', '2025-10-01 07:24:14', '2025-10-01 07:24:14'),
(223, 198, 50000.00, 'TJ18O635GS', 'bank_transfer', '2025-10-01', '2025-10-15', '2025-10-01 09:13:35', '2025-10-01 09:13:35'),
(224, 199, 12000.00, 'ROLL OVER', 'cash', '2025-09-24', '2025-10-03', '2025-10-03 06:53:37', '2025-10-03 06:53:37'),
(225, 200, 14400.00, 'ROLL OVER', 'Mpesa', '2025-10-04', '2025-10-14', '2025-10-03 06:55:33', '2025-10-21 04:31:57'),
(226, 186, 40000.00, 'TJ33X6EGSR', 'bank_transfer', '2025-10-03', '2025-12-03', '2025-10-03 07:36:54', '2025-10-03 07:36:54'),
(227, 201, 5000.00, 'TJ36B6D9HZ', 'bank_transfer', '2025-10-03', '2025-10-13', '2025-10-03 07:46:21', '2025-10-03 07:46:21'),
(228, 202, 10000.00, 'MPESA', 'bank_transfer', '2025-10-05', '2025-10-09', '2025-10-06 07:56:01', '2025-10-06 07:56:01'),
(229, 203, 54368.00, 'ROLL OVER', 'Mpesa', '2025-10-02', '2025-11-02', '2025-10-06 08:10:39', '2025-10-21 20:05:24'),
(230, 204, 15000.00, 'TJ6GC6NEIJ', 'bank_transfer', '2025-10-06', '2025-10-16', '2025-10-06 09:02:51', '2025-10-06 09:02:51'),
(231, 205, 10000.00, 'TJ99X6XEQE', 'Mpesa', '2025-10-09', '2025-10-18', '2025-10-09 14:56:53', '2025-10-09 14:58:08'),
(232, 205, 40000.00, 'TJ8336W4WF', 'bank_transfer', '2025-10-08', '2025-10-18', '2025-10-09 14:57:55', '2025-10-09 14:57:55'),
(233, 206, 1200.00, 'ROLL OVER', 'cash', '2025-10-08', '2025-10-18', '2025-10-13 11:17:09', '2025-10-13 11:17:09'),
(234, 207, 30000.00, '0100006578378', 'bank_transfer', '2025-10-14', '2025-10-24', '2025-10-13 15:46:44', '2025-10-13 15:46:44'),
(235, 208, 55835.00, 'ROLL OVER', 'cash', '2025-09-26', '2025-11-26', '2025-10-14 06:42:56', '2025-10-14 06:42:56'),
(236, 209, 30810.00, 'ROLL OVER', 'Mpesa', '2025-10-11', '2025-10-21', '2025-10-14 07:43:56', '2025-10-14 07:44:51'),
(237, 210, 36972.00, 'ROLL OVER', 'cash', '2025-10-11', '2025-10-21', '2025-10-14 07:46:11', '2025-10-14 07:46:11'),
(238, 211, 20000.00, 'TJFGC7FLBA', 'bank_transfer', '2025-10-15', '2025-10-25', '2025-10-15 08:39:21', '2025-10-15 08:39:21'),
(239, 212, 100000.00, 'TJFGZ7GUDB', 'bank_transfer', '2025-10-15', '2025-10-25', '2025-10-16 16:28:34', '2025-10-16 16:28:34'),
(240, 213, 10000.00, 'TJGKB7KWG0', 'bank_transfer', '2025-10-16', '2025-10-26', '2025-10-16 16:30:43', '2025-10-16 16:30:43'),
(241, 214, 72000.00, 'ROLL OVER', 'cash', '2025-10-21', '2025-11-02', '2025-10-22 09:36:49', '2025-10-22 09:36:49'),
(242, 215, 10000.00, 'TJNH586RLF', 'bank_transfer', '2025-10-23', '2025-11-02', '2025-10-23 12:46:28', '2025-10-23 12:46:28'),
(243, 216, 30000.00, 'TJOFG89PMJ', 'bank_transfer', '2025-10-24', '2025-11-02', '2025-10-24 08:11:59', '2025-10-24 08:11:59'),
(244, 219, 3700.00, 'TJR6B8GSJ0', 'bank_transfer', '2025-10-27', '2025-11-07', '2025-10-27 09:51:23', '2025-10-27 09:51:23'),
(245, 222, 25000.00, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-06', '2025-10-27 18:01:04', '2025-10-27 18:01:04'),
(246, 224, 10000.00, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-06', '2025-10-29 07:22:59', '2025-10-29 07:22:59'),
(247, 220, 10000.00, 'TJO4A8E9KQ', 'bank_transfer', '2025-10-24', '2025-10-27', '2025-10-29 07:29:33', '2025-10-29 07:29:33'),
(248, 225, 100000.00, 'ROLL OVER', 'cash', '2025-10-29', '2025-11-29', '2025-10-30 07:36:04', '2025-10-30 07:36:04'),
(249, 225, 100000.00, 'ROLL OVER', 'cash', '2025-10-29', '2025-11-29', '2025-10-30 07:36:05', '2025-10-30 07:36:05'),
(250, 217, 50000.00, 'TJV5T8X5F9', 'bank_transfer', '2025-10-31', '2025-11-09', '2025-10-31 08:30:20', '2025-10-31 08:30:20'),
(251, 186, 50000.00, 'TJV3X8VY6X', 'bank_transfer', '2025-10-31', '2025-11-09', '2025-10-31 08:34:51', '2025-10-31 08:34:51'),
(252, 226, 18700.00, 'ROLL OVER', 'cash', '2025-11-02', '2025-12-02', '2025-11-03 09:25:59', '2025-11-03 09:25:59'),
(253, 227, 10000.00, 'TK59X9ASRT', 'bank_transfer', '2025-11-04', '2025-11-14', '2025-11-05 13:27:46', '2025-11-05 13:27:46'),
(254, 228, 10000.00, 'ROLL OVER', 'cash', '2025-11-03', '2025-11-13', '2025-11-07 09:42:41', '2025-11-07 09:42:41'),
(255, 229, 25000.00, 'ROLL OVER', 'cash', '2025-11-07', '2025-11-17', '2025-11-07 09:48:01', '2025-11-07 09:48:01'),
(256, 230, 3000.00, 'ROLL OVER', 'cash', '2025-11-07', '2025-11-17', '2025-11-07 09:59:58', '2025-11-07 09:59:58'),
(257, 231, 12000.00, 'ROLL OVER', 'cash', '2025-11-06', '2025-11-16', '2025-11-08 08:11:40', '2025-11-08 08:11:40'),
(258, 232, 20000.00, 'TKAAL9RIRR', 'bank_transfer', '2025-11-10', '2025-11-20', '2025-11-10 15:19:19', '2025-11-10 15:19:19'),
(259, 233, 35242.00, 'ROLL OVER', 'cash', '2025-11-03', '2025-12-03', '2025-11-10 15:21:25', '2025-11-10 15:21:25'),
(260, 234, 66000.00, 'ROLL OVER', 'cash', '2025-11-01', '2025-11-11', '2025-11-10 15:24:30', '2025-11-10 15:24:30'),
(261, 235, 79200.00, 'ROLL OVER', 'cash', '2025-11-09', '2025-11-19', '2025-11-13 17:54:04', '2025-11-13 17:54:04'),
(262, 236, 36000.00, 'ROLL OVER', 'cash', '2025-11-03', '2025-11-13', '2025-11-17 09:31:53', '2025-11-17 09:31:53'),
(263, 237, 43200.00, 'ROLL OVER', 'cash', '2025-11-13', '2025-11-23', '2025-11-17 09:34:37', '2025-11-17 09:34:37'),
(264, 238, 12000.00, 'ROLL OVER', 'cash', '2025-11-13', '2025-11-23', '2025-11-17 09:43:03', '2025-11-17 09:43:03'),
(265, 239, 25000.00, 'ROLL OVER', 'cash', '2025-11-18', '2025-11-28', '2025-11-17 18:51:15', '2025-11-17 18:51:15'),
(266, 240, 14400.00, 'ROLL OVER', 'Mpesa', '2025-11-16', '2025-11-26', '2025-11-18 05:04:53', '2025-11-18 05:06:51'),
(267, 241, 10000.00, 'TKIN1AGBQG', 'bank_transfer', '2025-11-18', '2025-12-18', '2025-11-19 05:10:26', '2025-11-19 05:10:26'),
(268, 221, 100000.00, '150139401927', 'Mpesa', '2025-11-18', '2025-11-27', '2025-11-19 05:14:26', '2025-11-19 05:14:42'),
(269, 221, 15300.00, 'ROLL OVER', 'cash', '2025-10-27', '2025-11-27', '2025-11-19 05:15:31', '2025-11-19 05:15:31'),
(270, 242, 30000.00, '123114002557', 'bank_transfer', '2025-11-18', '2025-12-02', '2025-11-19 05:22:46', '2025-11-19 05:22:46'),
(271, 243, 2700.00, 'ROLL OVER', 'cash', '2025-11-17', '2025-11-27', '2025-11-19 09:26:27', '2025-11-19 09:26:27'),
(272, 244, 600000.00, '269164405448', 'bank_transfer', '2025-11-22', '2025-12-22', '2025-11-22 19:27:35', '2025-11-22 19:27:35'),
(273, 245, 20000.00, 'TKM4WAUJ46', 'bank_transfer', '2025-11-22', '2025-12-01', '2025-11-22 19:29:06', '2025-11-22 19:29:06'),
(274, 246, 20000.00, 'TKL9XAS81M', 'bank_transfer', '2025-11-21', '2025-12-01', '2025-11-22 19:34:48', '2025-11-22 19:34:48'),
(275, 248, 14400.00, 'ROLL OVER', 'cash', '2025-11-23', '2025-12-03', '2025-11-26 19:34:30', '2025-11-26 19:34:30'),
(276, 249, 25000.00, 'ROLL OVER', 'cash', '2025-11-28', '2025-12-08', '2025-11-28 18:27:20', '2025-11-28 18:27:20'),
(277, 251, 43840.00, 'ROLL OVER', 'cash', '2025-11-23', '2025-12-03', '2025-11-29 14:19:48', '2025-11-29 14:19:48'),
(278, 247, 95040.00, 'ROLL OVER', 'cash', '2025-11-20', '2025-11-30', '2025-11-29 15:12:38', '2025-11-29 15:12:38'),
(279, 252, 20000.00, 'ROLL OVER', 'cash', '2025-12-01', '2025-12-11', '2025-12-01 19:13:19', '2025-12-01 19:13:19'),
(280, 253, 114048.00, 'ROLL OVER', 'cash', '2025-11-30', '2025-12-10', '2025-12-03 04:33:15', '2025-12-03 04:33:15'),
(281, 254, 100000.00, '588867370679', 'bank_transfer', '2025-11-24', '2025-12-24', '2025-12-03 04:44:16', '2025-12-03 04:44:16'),
(282, 255, 2680.00, 'ROLL OVER', 'cash', '2025-11-27', '2025-12-07', '2025-12-03 04:48:19', '2025-12-03 04:48:19'),
(283, 250, 126830.00, 'ROLL OVER', 'cash', '2025-11-27', '2026-02-27', '2025-12-03 04:49:32', '2025-12-03 04:49:32'),
(284, 256, 50000.00, 'TL5GC03C33', 'bank_transfer', '2025-12-05', '2026-12-05', '2025-12-06 08:46:58', '2025-12-06 08:46:58'),
(285, 257, 42290.00, 'ROLL OVER', 'cash', '2025-12-03', '2025-01-03', '2025-12-06 08:55:30', '2025-12-06 08:55:30'),
(286, 258, 130000.00, 'ROLL OVER', 'cash', '2025-11-29', '2025-12-29', '2025-12-08 05:29:48', '2025-12-08 05:29:48'),
(287, 259, 37608.00, 'ROLL OVER', 'cash', '2025-12-03', '2025-12-13', '2025-12-09 06:19:21', '2025-12-09 06:19:21'),
(288, 260, 25000.00, 'ROLL OVER', 'cash', '2025-12-09', '2025-12-19', '2025-12-09 06:23:21', '2025-12-09 06:23:21'),
(289, 261, 8780.00, 'ROLL OVER', 'cash', '2025-12-03', '2025-12-13', '2025-12-09 06:27:35', '2025-12-09 06:27:35'),
(290, 262, 100000.00, 'ROLL OVER', 'cash', '2025-12-24', '2026-01-24', '2025-12-26 08:54:16', '2025-12-26 08:54:16'),
(292, 263, 10536.00, 'ROLL OVER', 'cash', '2025-12-13', '2025-12-23', '2025-12-26 08:58:45', '2025-12-26 08:58:45'),
(293, 264, 12644.00, 'ROLL OVER', 'cash', '2025-12-23', '2026-01-02', '2025-12-26 09:00:42', '2025-12-26 09:00:42'),
(294, 265, 45130.00, 'ROLL OVER', 'Mpesa', '2025-12-13', '2025-12-23', '2025-12-26 09:03:58', '2025-12-26 09:04:45'),
(295, 266, 54156.00, 'ROLL OVER', 'cash', '2025-12-23', '2026-01-02', '2025-12-26 09:06:31', '2025-12-26 09:06:31'),
(296, 268, 5000.00, 'TLQ9X25GGH', 'cash', '2025-12-26', '2025-12-26', '2025-12-26 18:07:39', '2025-12-26 18:07:39'),
(297, 267, 5000.00, 'TLM9X1QZKV', 'bank_transfer', '2025-12-22', '2025-01-01', '2025-12-26 18:08:57', '2025-12-26 18:08:57'),
(298, 270, 30000.00, '683213014684', 'Mpesa', '2025-12-29', '2025-01-12', '2025-12-31 04:37:08', '2025-12-31 04:38:00'),
(299, 270, 50000.00, '281652744481', 'bank_transfer', '2025-12-29', '2025-01-12', '2025-12-31 04:37:52', '2025-12-31 04:37:52'),
(300, 269, 720000.00, 'ROLL OVER', 'cash', '2025-12-22', '2026-01-22', '2026-01-04 09:32:50', '2026-01-04 09:32:50'),
(301, 272, 20000.00, 'TLQBU25Y2F', 'bank_transfer', '2026-12-26', '2026-12-05', '2026-01-05 02:00:02', '2026-01-05 02:00:02'),
(302, 273, 15172.00, 'ROLL OVER', 'cash', '2026-01-02', '2026-01-12', '2026-01-08 14:14:22', '2026-01-08 14:14:22'),
(303, 274, 40000.00, 'UA98O3BJJI', 'bank_transfer', '2026-01-09', '2026-01-19', '2026-01-09 07:02:50', '2026-01-09 07:02:50'),
(304, 275, 100000.00, 'UA99X3DR0I', 'bank_transfer', '2026-01-09', '2026-02-09', '2026-01-12 06:01:09', '2026-01-12 06:01:09'),
(305, 276, 50000.00, '922272948696', 'bank_transfer', '2026-01-09', '2026-01-19', '2026-01-12 06:04:57', '2026-01-12 06:04:57'),
(306, 277, 35000.00, '435076301514', 'bank_transfer', '2026-01-12', '2026-01-22', '2026-01-12 07:01:23', '2026-01-12 07:01:23'),
(307, 278, 20000.00, '723597734036', 'bank_transfer', '2025-12-19', '2026-12-02', '2026-01-12 07:12:50', '2026-01-12 07:12:50'),
(308, 278, 30000.00, 'TLFFH13IRG', 'bank_transfer', '2025-12-15', '2026-02-15', '2026-01-12 07:14:20', '2026-01-12 07:14:20'),
(309, 278, 26000.00, '118781109392', 'bank_transfer', '2025-12-02', '2026-02-02', '2026-01-12 07:15:02', '2026-01-12 07:15:02'),
(310, 278, 5000.00, 'TLC6O0PBJ1', 'bank_transfer', '2025-12-10', '2026-02-10', '2026-01-12 07:16:06', '2026-01-12 07:16:06'),
(311, 278, 17000.00, '2987OUKN5391', 'bank_transfer', '2025-12-12', '2026-02-12', '2026-01-12 07:16:49', '2026-01-12 07:16:49'),
(312, 278, 2000.00, '276508285588', 'bank_transfer', '2025-12-02', '2026-02-12', '2026-01-12 07:18:31', '2026-01-12 07:18:31'),
(313, 279, 169000.00, 'ROLL OVER', 'cash', '2025-12-29', '2026-01-29', '2026-01-12 13:29:44', '2026-01-12 13:29:44'),
(315, 280, 50748.00, 'ROLL OVER', 'cash', '2026-01-03', '2026-02-03', '2026-01-15 16:41:41', '2026-01-15 16:41:41'),
(317, 281, 6000.00, 'UAG9X4074M', 'bank_transfer', '2026-01-16', '2026-01-16', '2026-01-16 13:43:51', '2026-01-16 13:43:51'),
(318, 282, 250000.00, 'UAL644MPFT', 'bank_transfer', '2026-01-21', '2026-02-01', '2026-01-22 07:29:17', '2026-01-22 07:29:17'),
(319, 283, 8000.00, 'UAM9X4KZIR', 'bank_transfer', '2026-01-22', '2026-02-02', '2026-01-27 15:14:54', '2026-01-27 15:14:54'),
(320, 285, 30000.00, 'UAQ9X4X754', 'bank_transfer', '2026-01-26', '2026-01-31', '2026-01-29 08:26:13', '2026-01-29 08:26:13'),
(321, 286, 8000.00, 'UAT9X57N3S', 'bank_transfer', '2026-01-29', '2026-02-03', '2026-01-29 08:27:16', '2026-01-29 08:27:16'),
(322, 287, 100000.00, 'ROLL OVER', 'Mpesa', '2026-01-24', '2026-02-24', '2026-01-29 10:09:35', '2026-02-26 17:54:48'),
(323, 288, 364000.00, 'ROLL OVER', 'Mpesa', '2026-01-22', '2026-02-22', '2026-02-01 19:39:20', '2026-02-24 16:14:14'),
(324, 289, 300000.00, 'ROLL OVER', 'cash', '2026-01-31', '2026-02-10', '2026-02-03 14:43:15', '2026-02-03 14:43:15'),
(326, 290, 100000.00, 'PESALINK', 'bank_transfer', '2026-02-05', '2026-02-10', '2026-02-09 13:33:15', '2026-02-09 13:33:15'),
(327, 291, 65560.00, 'ROLL OVER', 'bank_transfer', '2026-02-09', '2026-03-09', '2026-02-09 13:41:01', '2026-02-09 13:41:01'),
(328, 292, 100000.00, 'UB88O66J8B', 'bank_transfer', '2026-02-08', '2026-12-08', '2026-02-09 13:43:00', '2026-02-09 13:43:00'),
(329, 293, 252000.00, '042411941846', 'bank_transfer', '2026-02-05', '2026-02-05', '2026-02-09 13:47:35', '2026-02-09 13:47:35'),
(331, 307, 150000.00, '176497116960', 'bank_transfer', '2026-02-11', '2026-02-22', '2026-02-11 16:38:00', '2026-02-11 16:38:00'),
(332, 308, 15000.00, 'UBGGC6VCBR', 'bank_transfer', '2026-02-16', '2026-03-16', '2026-02-17 13:40:43', '2026-02-17 13:40:43'),
(333, 309, 12000.00, '325695589697', 'bank_transfer', '2026-02-13', '2026-02-23', '2026-02-17 13:46:15', '2026-02-17 13:46:15'),
(334, 310, 360000.00, 'ROLL OVER', 'cash', '2026-02-10', '2026-02-20', '2026-02-17 13:48:14', '2026-02-17 13:48:14'),
(335, 311, 219700.00, 'ROLL OVER', 'cash', '2026-01-31', '2026-02-28', '2026-02-18 05:28:12', '2026-02-18 05:28:12'),
(336, 312, 60898.00, 'ROLL OVER', 'cash', '2026-02-03', '2026-03-03', '2026-02-18 05:36:10', '2026-02-18 05:36:10'),
(337, 314, 30000.00, 'UBGBM6YYRR', 'bank_transfer', '2026-02-16', '2026-02-26', '2026-02-19 09:20:32', '2026-02-19 09:20:32'),
(338, 315, 50000.00, 'UB94A6N8EF', 'bank_transfer', '2026-02-09', '2026-02-19', '2026-02-19 09:40:20', '2026-02-19 09:40:20'),
(339, 316, 60000.00, 'ROLL OVER', 'cash', '2026-02-19', '2026-02-28', '2026-02-19 09:42:18', '2026-02-19 09:42:18'),
(340, 316, 70000.00, 'UBI4A7GERR', 'cash', '2026-02-18', '2026-02-28', '2026-02-19 09:42:34', '2026-02-19 09:42:34'),
(341, 313, 5000.00, 'UBIOI77N54', 'bank_transfer', '2026-02-18', '2026-02-28', '2026-02-19 09:47:18', '2026-02-19 09:47:18'),
(342, 317, 100000.00, '438432145363', 'bank_transfer', '2026-02-19', '2026-02-19', '2026-02-19 10:02:53', '2026-02-19 10:02:53'),
(343, 318, 2000.00, 'UBJBD76WGN', 'bank_transfer', '2026-02-19', '2026-03-01', '2026-02-19 10:07:44', '2026-02-19 10:07:44'),
(344, 318, 2000.00, 'UBLBD7EE0Y', 'bank_transfer', '2026-02-19', '2026-02-28', '2026-02-21 21:11:19', '2026-02-21 21:11:19'),
(345, 319, 40000.00, 'ROLL OVER', 'cash', '2026-02-21', '2026-03-02', '2026-02-21 21:15:00', '2026-02-21 21:15:00'),
(346, 320, 1000.00, 'UBIIT6X3J4', 'bank_transfer', '2026-02-18', '2026-02-28', '2026-02-23 14:11:45', '2026-02-23 14:11:45'),
(347, 320, 1000.00, 'UBNIT7F3ZB', 'bank_transfer', '2026-02-23', '2026-02-28', '2026-02-23 14:12:02', '2026-02-23 14:12:02'),
(348, 321, 168000.00, 'ROLL OVER', 'cash', '2026-02-22', '2026-02-27', '2026-02-24 16:05:48', '2026-02-24 16:05:48'),
(349, 321, 13248.00, 'ROLL OVER', 'cash', '2026-02-22', '2026-02-27', '2026-02-24 16:06:00', '2026-02-24 16:06:00'),
(350, 316, 30000.00, 'UBP4A85Z7N', 'bank_transfer', '2026-02-26', '2026-02-28', '2026-02-26 04:14:19', '2026-02-26 04:14:19'),
(351, 322, 100000.00, 'ROLL OVER', 'cash', '2026-02-26', '2026-03-26', '2026-02-26 18:02:11', '2026-02-26 18:02:11'),
(352, 323, 192000.00, 'ROLL OVER', 'cash', '2026-02-28', '2026-02-28', '2026-02-27 19:34:52', '2026-02-27 19:34:52'),
(353, 324, 150000.00, 'ROLL OVER', 'cash', '2026-02-27', '2026-03-13', '2026-03-03 06:43:07', '2026-03-03 06:43:07'),
(354, 325, 285610.00, 'ROLL OVER', 'cash', '2026-03-03', '2026-03-03', '2026-03-04 06:07:25', '2026-03-04 06:07:25'),
(355, 326, 3500.00, 'UC46O88G7H', 'bank_transfer', '2026-03-04', '2026-03-14', '2026-03-04 07:47:46', '2026-03-04 07:47:46'),
(356, 327, 15000.00, 'UC3KB83095', 'bank_transfer', '2026-03-03', '2026-03-03', '2026-03-04 07:49:36', '2026-03-04 07:49:36'),
(357, 329, 55000.00, 'ROLL OVER', 'cash', '2026-03-07', '2026-03-17', '2026-03-06 12:58:14', '2026-03-06 12:58:14'),
(358, 330, 432000.00, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-02', '2026-03-06 13:02:54', '2026-03-06 13:02:54'),
(359, 331, 518400.00, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-12', '2026-03-06 13:04:57', '2026-03-06 13:04:57'),
(360, 332, 20000.00, '3719CFJF5903', 'bank_transfer', '2026-03-08', '2026-03-18', '2026-03-08 13:58:22', '2026-03-08 13:58:22'),
(361, 333, 15000.00, '3728HHVL2707', 'bank_transfer', '2026-03-08', '2026-03-18', '2026-03-08 13:59:48', '2026-03-08 13:59:48'),
(362, 334, 50000.00, '3719XVBL5990', 'bank_transfer', '2026-03-07', '2026-03-17', '2026-03-08 14:01:41', '2026-03-08 14:01:41'),
(363, 328, 3000.00, 'UC66B8GN86', 'bank_transfer', '2026-03-06', '2026-03-16', '2026-03-08 14:32:38', '2026-03-08 14:32:38'),
(364, 335, 85228.00, 'ROLL OVER', 'cash', '2026-03-09', '2026-04-09', '2026-03-12 11:22:39', '2026-03-12 11:22:39'),
(365, 336, 2000.00, 'UCC6B91DXT', 'bank_transfer', '2026-03-12', '2026-03-22', '2026-03-13 08:18:04', '2026-03-13 08:18:04'),
(366, 337, 50000.00, '620830073841', 'Mpesa', '2026-03-14', '2026-03-24', '2026-03-14 19:46:56', '2026-03-14 19:47:33'),
(367, 338, 18000.00, 'ROLL OVER', 'cash', '2026-03-13', '2026-03-23', '2026-03-14 19:54:51', '2026-03-14 19:54:51'),
(368, 339, 5000.00, 'UCDHD9CMWM', 'bank_transfer', '2026-03-13', '2026-03-13', '2026-03-14 20:03:12', '2026-03-14 20:03:12'),
(369, 339, 32500.00, 'ROLL OVER', 'Mpesa', '2026-03-13', '2026-03-13', '2026-03-14 20:03:42', '2026-03-14 20:04:08'),
(370, 340, 6000.00, 'MPESA', 'bank_transfer', '2026-02-20', '2026-02-20', '2026-03-15 18:46:40', '2026-03-15 18:46:40'),
(371, 341, 7200.00, 'ROLL OVER', 'cash', '2026-03-02', '2026-03-12', '2026-03-15 18:48:15', '2026-03-15 18:48:15'),
(372, 342, 8640.00, 'ROLL OVER', 'cash', '2026-03-12', '2026-03-22', '2026-03-15 18:50:15', '2026-03-15 18:50:15'),
(373, 343, 10000.00, 'UCGEV9I4AF', 'bank_transfer', '2026-03-16', '2026-03-26', '2026-03-16 15:16:56', '2026-03-16 15:16:56'),
(374, 344, 5000.00, 'UCI6B9O9OT', 'bank_transfer', '2026-03-18', '2026-03-28', '2026-03-19 05:18:56', '2026-03-19 05:18:56'),
(375, 345, 30000.00, 'UCH9X9PH8Y', 'bank_transfer', '2026-03-18', '2026-03-28', '2026-03-19 07:15:01', '2026-03-19 07:15:01'),
(376, 346, 5000.00, 'UCJAL9R6WA', 'bank_transfer', '2026-03-19', '2026-03-29', '2026-03-20 15:16:16', '2026-03-20 15:16:16'),
(377, 347, 50000.00, 'ROLL OVER', 'cash', '2026-02-28', '2026-03-10', '2026-03-20 15:22:44', '2026-03-20 15:22:44'),
(378, 349, 114440.00, 'ROLL OVER', 'cash', '2026-02-27', '2026-05-27', '2026-03-20 18:25:16', '2026-03-20 18:25:16'),
(379, 350, 8000.00, 'UCOGCABJKN', 'bank_transfer', '2026-03-24', '2026-04-03', '2026-03-25 07:28:57', '2026-03-25 07:28:57'),
(380, 351, 10000.00, 'UCPBMAL8MF', 'bank_transfer', '2026-03-25', '2026-04-03', '2026-03-25 07:31:48', '2026-03-25 07:31:48'),
(381, 352, 1500.00, 'UCP6OABVYQ', 'bank_transfer', '2026-03-25', '2026-04-03', '2026-03-25 07:55:23', '2026-03-25 07:55:23'),
(382, 353, 10000.00, 'ROLL OVER', 'cash', '2026-03-26', '2026-04-04', '2026-03-26 20:57:35', '2026-03-26 20:57:35'),
(383, 354, 20000.00, 'UCQKBAE0DG', 'bank_transfer', '2026-03-26', '2026-04-04', '2026-03-27 08:36:15', '2026-03-27 08:36:15'),
(384, 355, 1000.00, 'UCR6OAKFLQ', 'bank_transfer', '2026-03-27', '2026-04-03', '2026-03-29 16:49:36', '2026-03-29 16:49:36'),
(385, 356, 5000.00, 'UCSOIB1OMC', 'bank_transfer', '2026-03-28', '2026-04-06', '2026-03-29 16:51:49', '2026-03-29 16:51:49'),
(386, 357, 45000.00, 'UCTBMB1ZDN', 'bank_transfer', '2026-03-30', '2026-03-09', '2026-03-29 16:55:42', '2026-03-29 16:55:42'),
(387, 358, 10000.00, 'UCR4AB9NS5', 'bank_transfer', '2026-03-27', '2026-04-05', '2026-03-29 17:15:01', '2026-03-29 17:15:01'),
(388, 359, 10000.00, 'UD132BBR99', 'bank_transfer', '2026-04-01', '2026-04-06', '2026-04-02 04:48:03', '2026-04-02 04:48:03'),
(389, 360, 100000.00, 'ROLL OVER', 'cash', '2026-03-26', '2026-04-26', '2026-04-02 04:57:35', '2026-04-02 04:57:35'),
(390, 361, 3200.00, 'UCV6BB0M8H', 'bank_transfer', '2026-03-31', '2026-04-10', '2026-04-02 04:59:35', '2026-04-02 04:59:35'),
(391, 362, 5000.00, 'UD1HDBDUSY', 'bank_transfer', '2026-03-31', '2026-04-10', '2026-04-02 05:02:02', '2026-04-02 05:02:02'),
(392, 363, 25000.00, 'UCU9XB5HFB', 'bank_transfer', '2026-03-30', '2026-04-09', '2026-04-02 05:08:53', '2026-04-02 05:08:53'),
(393, 364, 73078.00, 'ROLL OVER', 'cash', '2026-03-18', '2026-04-18', '2026-04-02 05:18:04', '2026-04-02 05:18:04'),
(394, 365, 30000.00, 'UD4ALBIZKO', 'bank_transfer', '2026-04-04', '2026-04-14', '2026-04-04 11:04:56', '2026-04-04 11:04:56'),
(395, 366, 4200.00, 'ROLL OVER', 'bank_transfer', '2026-03-14', '2026-03-24', '2026-04-04 11:07:14', '2026-04-04 11:07:14'),
(396, 367, 5040.00, 'ROLL OVER', 'cash', '2026-03-24', '2026-04-03', '2026-04-04 11:08:35', '2026-04-04 11:08:35'),
(397, 368, 10368.00, 'ROLL OVER', 'cash', '2026-03-21', '2026-04-01', '2026-04-04 11:11:57', '2026-04-04 11:11:57'),
(398, 369, 10000.00, 'roll over', 'cash', '2026-04-05', '2026-04-15', '2026-04-05 15:44:24', '2026-04-05 15:44:24'),
(399, 370, 24000.00, 'ROLL OVER', 'cash', '2026-04-05', '2026-04-15', '2026-04-08 06:07:49', '2026-04-08 06:07:49'),
(400, 371, 1500.00, 'UD86BBWAX7', 'bank_transfer', '2026-04-08', '2026-04-18', '2026-04-08 06:12:29', '2026-04-08 06:12:29'),
(401, 372, 5000.00, 'ROLL OVER', 'cash', '2026-04-10', '2026-04-20', '2026-04-13 16:26:02', '2026-04-13 16:26:02'),
(402, 373, 3040.00, 'ROLL OVER', 'cash', '2026-04-10', '2026-04-20', '2026-04-13 16:30:44', '2026-04-13 16:30:44'),
(403, 374, 342732.00, 'ROLL OVER', 'cash', '2026-04-03', '2026-05-03', '2026-04-13 16:43:53', '2026-04-13 16:43:53'),
(404, 375, 12441.00, 'ROLL OVER', 'cash', '2026-03-31', '2026-04-09', '2026-04-13 17:20:15', '2026-04-13 17:20:15'),
(405, 375, 6048.00, 'ROLL OVER', 'Mpesa', '2026-04-03', '2026-04-09', '2026-04-13 17:20:28', '2026-04-13 17:21:33'),
(406, 376, 22187.00, 'ROLL OVER', 'cash', '2026-03-10', '2026-03-20', '2026-04-13 17:25:34', '2026-04-13 17:25:34'),
(407, 377, 10000.00, 'UDFAI12TXH', 'bank_transfer', '2026-04-15', '2026-04-15', '2026-04-15 10:12:00', '2026-04-15 10:12:00'),
(408, 378, 7500.00, 'UDEOI142NU', 'bank_transfer', '2026-04-14', '2026-04-24', '2026-04-15 10:21:15', '2026-04-15 10:21:15'),
(409, 379, 10000.00, 'UDFBM13SLE', 'bank_transfer', '2026-04-15', '2026-04-25', '2026-04-15 10:23:08', '2026-04-15 10:23:08'),
(410, 380, 66000.00, 'ROLL OVER', 'cash', '2026-03-20', '2026-06-20', '2026-04-15 10:56:34', '2026-04-15 10:56:34'),
(411, 381, 10000.00, 'ROLL OVER', 'cash', '2026-04-15', '2026-04-25', '2026-04-16 15:08:55', '2026-04-16 15:08:55'),
(412, 382, 30000.00, 'ROLL OVER', 'cash', '2026-04-09', '2026-04-19', '2026-04-17 18:15:28', '2026-04-17 18:15:28'),
(413, 383, 110797.00, 'ROLL OVER', 'cash', '2026-04-09', '2026-05-09', '2026-04-17 18:18:23', '2026-04-17 18:18:23'),
(414, 384, 6000.00, 'ROLL OVER', 'cash', '2026-04-20', '2026-04-30', '2026-04-21 04:06:09', '2026-04-21 04:06:09'),
(415, 385, 15000.00, 'UDKBM1Q6KM', 'bank_transfer', '2026-04-20', '2026-04-30', '2026-04-22 06:46:20', '2026-04-22 06:46:20'),
(416, 385, 5000.00, 'UDLBM1RM8F', 'Mpesa', '2026-04-22', '2026-04-30', '2026-04-22 06:46:36', '2026-04-22 06:46:54'),
(417, 386, 1000.00, 'UDMIT1JYA4', 'bank_transfer', '2026-04-22', '2026-05-02', '2026-04-22 07:00:46', '2026-04-22 07:00:46'),
(418, 387, 10000.00, 'UDM9X1UI8J', 'bank_transfer', '2026-04-22', '2026-05-02', '2026-04-23 05:54:39', '2026-04-23 05:54:39'),
(419, 388, 10000.00, 'UDN321V2ZZ', 'bank_transfer', '2026-04-23', '2026-04-28', '2026-04-23 06:52:58', '2026-04-23 06:52:58'),
(420, 388, 10000.00, 'UDN321V2ZZ', 'bank_transfer', '2026-04-23', '2026-04-28', '2026-04-23 06:52:58', '2026-04-23 06:52:58'),
(421, 389, 3300.00, 'UDN6B1PHVO', 'bank_transfer', '2026-04-23', '2026-05-02', '2026-04-26 06:43:35', '2026-04-26 06:43:35');
INSERT INTO `disbursements` (`id`, `loan_id`, `amount`, `transaction`, `mode`, `disburse_date`, `payment_date`, `created_at`, `updated_at`) VALUES
(422, 389, 1700.00, 'UDN6B1RC19', 'bank_transfer', '2026-04-23', '2026-05-02', '2026-04-26 06:43:49', '2026-04-26 06:43:49');

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
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `loan_type_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `borrow_date` date NOT NULL,
  `broker_status` tinyint(4) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','disbursed','rejected','repaid') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `guarantor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `guarantor_relationship` varchar(255) DEFAULT NULL,
  `loan_officer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `consent` tinyint(1) DEFAULT 0,
  `consent_date` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `borrow_date`, `broker_status`, `status`, `created_at`, `updated_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`) VALUES
(1, 2, 1, 120000.00, '2025-03-07', 1, 'repaid', '2025-04-17 12:12:56', '2025-04-17 12:12:56', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(2, 6, 1, 30000.00, '2025-03-07', 1, 'repaid', '2025-04-17 12:22:35', '2025-04-17 12:22:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(3, 6, 1, 50000.00, '2025-03-28', 1, 'repaid', '2025-04-17 12:26:11', '2025-04-17 12:26:11', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(4, 2, 1, 35000.00, '2025-03-31', 1, 'repaid', '2025-04-17 12:42:17', '2025-04-17 12:42:17', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(5, 7, 2, 10000.00, '2025-03-03', 0, 'repaid', '2025-04-17 13:03:34', '2025-04-17 13:03:34', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(6, 7, 2, 13000.00, '2025-04-05', 0, 'repaid', '2025-04-17 13:11:52', '2025-05-06 08:47:42', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(7, 8, 1, 2000.00, '2025-04-10', 0, 'repaid', '2025-04-17 13:18:22', '2025-05-05 19:19:58', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(8, 9, 1, 10000.00, '2025-04-12', 0, 'repaid', '2025-04-17 13:23:26', '2025-04-17 13:23:26', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(9, 9, 1, 15000.00, '2025-04-17', 0, 'repaid', '2025-04-17 13:25:52', '2025-04-17 13:25:52', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(10, 10, 3, 30000.00, '2025-04-16', 0, 'repaid', '2025-04-17 13:27:33', '2025-05-07 14:45:50', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(11, 11, 1, 15000.00, '2025-04-08', 0, 'repaid', '2025-04-17 13:29:39', '2025-04-17 13:29:39', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(12, 14, 1, 27000.00, '2025-04-17', 1, 'repaid', '2025-04-17 14:08:51', '2025-05-06 08:45:23', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(13, 16, 1, 30000.00, '2025-04-13', 1, 'repaid', '2025-04-17 14:33:51', '2025-04-17 14:33:51', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(14, 2, 1, 20000.00, '2025-03-25', 0, 'repaid', '2025-04-20 12:26:24', '2025-04-20 12:26:24', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(15, 18, 1, 50000.00, '2025-03-25', 0, 'repaid', '2025-04-20 12:42:26', '2025-04-20 12:42:26', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(16, 18, 1, 50000.00, '2025-04-01', 1, 'repaid', '2025-04-20 12:50:23', '2025-04-20 12:50:23', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(17, 18, 1, 20000.00, '2025-04-12', 1, 'repaid', '2025-04-20 13:06:04', '2025-04-20 13:06:04', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(18, 2, 1, 35000.00, '2025-03-31', 1, 'repaid', '2025-04-20 13:24:14', '2025-04-20 13:24:14', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(19, 2, 1, 20000.00, '2025-04-07', 0, 'repaid', '2025-04-20 13:45:24', '2025-04-20 13:45:24', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(20, 2, 1, 33000.00, '2025-04-15', 0, 'repaid', '2025-04-20 13:49:21', '2025-04-20 13:49:21', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(21, 2, 1, 42000.00, '2025-04-15', 1, 'repaid', '2025-04-20 13:56:32', '2025-04-20 13:56:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(22, 3, 1, 10000.00, '2025-03-03', 1, 'repaid', '2025-04-20 16:19:58', '2025-04-20 16:19:58', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(23, 3, 1, 5000.00, '2025-02-26', 1, 'repaid', '2025-04-20 16:29:18', '2025-04-20 16:29:18', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(24, 15, 2, 5000.00, '2025-03-08', 0, 'repaid', '2025-04-20 16:32:20', '2025-05-05 19:06:36', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(25, 13, 2, 11000.00, '2025-03-10', 0, 'repaid', '2025-04-20 16:33:54', '2025-04-20 16:33:54', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(26, 13, 2, 600.00, '2025-04-21', 0, 'repaid', '2025-04-20 16:38:29', '2025-05-05 19:25:00', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(27, 15, 2, 2000.00, '2025-03-15', 0, 'repaid', '2025-04-20 16:39:13', '2025-05-05 19:14:42', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(28, 3, 1, 50000.00, '2025-04-04', 1, 'repaid', '2025-04-20 17:10:46', '2025-04-20 17:10:46', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(29, 17, 2, 11000.00, '2025-04-04', 0, 'repaid', '2025-04-20 17:11:49', '2025-04-20 17:11:49', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(30, 15, 2, 6000.00, '2025-04-04', 0, 'repaid', '2025-04-20 17:12:15', '2025-05-05 19:20:31', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(31, 19, 1, 50000.00, '2025-04-07', 0, 'repaid', '2025-04-20 17:15:30', '2025-04-20 17:15:30', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(32, 13, 2, 29000.00, '2025-04-04', 0, 'repaid', '2025-04-20 17:18:16', '2025-05-05 19:21:04', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(33, 17, 2, 10000.00, '2025-03-07', 0, 'repaid', '2025-04-20 17:19:04', '2025-04-20 17:19:04', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(34, 20, 2, 9000.00, '2025-03-07', 0, 'repaid', '2025-04-20 17:39:54', '2025-04-20 17:39:54', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(35, 11, 1, 18000.00, '2025-04-18', 0, 'repaid', '2025-04-21 06:17:23', '2025-04-21 06:17:23', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(36, 2, 1, 44000.00, '2025-03-21', 1, 'repaid', '2025-04-21 09:01:00', '2025-04-21 09:01:00', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(37, 9, 1, 5000.00, '2025-04-25', 0, 'repaid', '2025-04-21 10:05:13', '2025-05-05 19:27:40', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(38, 21, 1, 50000.00, '2025-04-24', 0, 'repaid', '2025-04-24 06:57:09', '2025-04-24 06:57:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(39, 9, 1, 15000.00, '2025-04-27', 0, 'repaid', '2025-04-27 06:33:31', '2025-05-07 14:48:02', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(40, 15, 2, 15600.00, '2025-05-06', 0, 'repaid', '2025-04-27 07:53:49', '2025-06-06 08:13:05', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(41, 13, 2, 8000.00, '2025-04-27', 0, 'repaid', '2025-04-27 07:55:09', '2025-05-05 19:25:52', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(45, 2, 1, 50000.00, '2025-04-29', 0, 'repaid', '2025-04-29 06:05:48', '2025-05-12 09:03:29', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(46, 21, 1, 50000.00, '2025-05-03', 0, 'repaid', '2025-05-02 19:31:23', '2025-05-15 07:46:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(48, 9, 1, 5000.00, '2025-05-05', 0, 'repaid', '2025-05-05 18:02:20', '2025-05-09 17:56:42', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(49, 13, 2, 50000.00, '2025-05-06', 0, 'repaid', '2025-05-05 19:30:08', '2025-06-06 08:12:05', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(50, 16, 1, 100000.00, '2025-05-06', 1, 'repaid', '2025-05-06 08:14:19', '2025-05-16 19:26:14', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(51, 7, 1, 15600.00, '2025-05-04', 0, 'repaid', '2025-05-06 08:48:50', '2025-05-15 07:48:42', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(52, 8, 2, 8000.00, '2025-05-06', 0, 'pending', '2025-05-07 10:46:08', '2025-07-18 08:37:59', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(54, 22, 1, 10000.00, '2025-05-07', 1, 'repaid', '2025-05-07 12:10:55', '2025-05-18 08:10:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(55, 9, 1, 15000.00, '2025-05-07', 0, 'repaid', '2025-05-07 14:50:08', '2025-05-08 18:48:43', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(56, 2, 1, 30000.00, '2025-05-07', 1, 'repaid', '2025-05-07 14:53:46', '2025-05-16 12:53:45', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(57, 14, 1, 45000.00, '2025-05-10', 1, 'repaid', '2025-05-10 10:37:30', '2025-05-22 05:24:47', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(59, 2, 1, 78000.00, '2025-05-12', 0, 'repaid', '2025-05-12 09:05:41', '2025-05-20 04:29:27', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(60, 24, 1, 50000.00, '2025-05-14', 1, 'repaid', '2025-05-13 06:50:44', '2025-05-26 16:54:50', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(61, 21, 1, 72000.00, '2025-05-15', 0, 'repaid', '2025-05-15 07:46:55', '2025-05-26 10:30:57', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(64, 7, 2, 18720.00, '2025-05-15', 0, 'repaid', '2025-05-15 07:49:33', '2025-06-16 13:26:11', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(65, 11, 3, 13000.00, '2025-05-14', 0, 'repaid', '2025-05-15 07:57:40', '2025-05-30 15:21:08', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(66, 25, 1, 2500.00, '2025-05-14', 1, 'repaid', '2025-05-15 08:09:40', '2025-05-24 11:58:24', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(68, 22, 1, 10000.00, '2025-05-17', 1, 'repaid', '2025-05-18 08:11:18', '2025-05-31 11:07:01', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(69, 26, 3, 50000.00, '2025-05-19', 0, 'repaid', '2025-05-19 12:46:30', '2025-06-03 19:11:06', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(70, 27, 3, 50000.00, '2025-05-20', 0, 'repaid', '2025-05-20 13:03:31', '2025-06-03 15:42:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(71, 28, 1, 15000.00, '2025-05-26', 0, 'repaid', '2025-05-25 18:37:01', '2025-06-06 07:59:54', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(72, 21, 1, 27840.00, '2025-05-26', 0, 'pending', '2025-05-26 10:33:15', '2025-05-26 10:36:03', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(73, 9, 1, 10000.00, '2025-05-24', 0, 'repaid', '2025-05-26 11:51:57', '2025-06-03 08:13:25', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(74, 24, 1, 50000.00, '2025-05-26', 1, 'repaid', '2025-05-26 16:55:48', '2025-06-04 07:57:27', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(75, 17, 7, 30000.00, '2025-05-27', 0, 'repaid', '2025-05-27 06:20:52', '2025-07-01 07:28:21', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(76, 29, 3, 15000.00, '2025-05-27', 0, 'repaid', '2025-05-27 13:48:17', '2025-06-10 09:26:58', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(77, 28, 1, 30000.00, '2025-05-29', 0, 'repaid', '2025-05-29 06:41:34', '2025-06-10 05:06:45', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(78, 2, 4, 100000.00, '2025-05-29', 0, 'repaid', '2025-05-29 12:06:58', '2025-10-21 19:59:01', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(79, 31, 1, 35000.00, '2025-05-29', 0, 'repaid', '2025-05-29 13:49:38', '2025-07-07 21:08:58', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(80, 11, 5, 30000.00, '2025-05-30', 0, 'repaid', '2025-05-30 15:28:35', '2025-07-17 08:18:25', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(82, 28, 5, 30000.00, '2025-05-31', 0, 'repaid', '2025-05-31 09:41:29', '2025-07-10 05:04:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(83, 22, 1, 8200.00, '2025-05-29', 1, 'repaid', '2025-05-31 11:16:30', '2025-06-20 07:03:33', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(84, 32, 6, 20000.00, '2025-06-02', 0, 'repaid', '2025-06-02 11:19:18', '2025-06-20 11:25:28', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(85, 16, 1, 100000.00, '2025-06-03', 1, 'repaid', '2025-06-03 07:25:00', '2025-07-10 04:44:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(86, 9, 1, 10000.00, '2025-06-03', 0, 'repaid', '2025-06-03 08:12:16', '2025-06-15 05:14:44', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(87, 27, 3, 50000.00, '2025-06-04', 0, 'repaid', '2025-06-03 15:43:14', '2025-06-20 07:06:21', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(88, 28, 1, 28000.00, '2025-06-06', 0, 'repaid', '2025-06-06 08:00:44', '2025-06-18 09:54:17', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(89, 22, 1, 8280.00, '2025-06-08', 1, 'repaid', '2025-06-09 03:41:42', '2025-06-30 08:37:52', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(90, 28, 1, 39600.00, '2025-06-09', 0, 'repaid', '2025-06-10 05:08:08', '2025-06-18 10:01:51', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(91, 33, 1, 15000.00, '2025-06-14', 0, 'repaid', '2025-06-15 05:15:29', '2025-06-25 10:43:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(92, 9, 1, 10000.00, '2025-06-13', 0, 'repaid', '2025-06-15 05:15:29', '2025-06-25 10:43:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(93, 29, 1, 17000.00, '2025-06-13', 1, 'repaid', '2025-06-15 05:19:36', '2025-06-23 18:10:20', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(94, 34, 3, 5000.00, '2025-06-12', 1, 'repaid', '2025-06-15 13:56:47', '2025-06-27 07:43:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(95, 7, 2, 2264.00, '2025-06-16', 0, 'repaid', '2025-06-16 13:29:00', '2025-07-01 09:14:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(96, 28, 1, 81120.00, '2025-06-19', 0, 'repaid', '2025-06-18 09:58:47', '2025-07-10 04:52:11', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(97, 27, 2, 50000.00, '2025-06-18', 0, 'repaid', '2025-06-20 07:07:15', '2025-12-23 10:16:48', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(98, 35, 3, 28000.00, '2025-06-20', 0, 'repaid', '2025-06-20 18:32:40', '2025-06-25 08:24:49', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(99, 36, 1, 5000.00, '2025-06-21', 0, 'pending', '2025-06-21 06:22:09', '2025-06-21 06:22:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(100, 37, 1, 5000.00, '2025-06-22', 0, 'repaid', '2025-06-23 10:27:04', '2025-07-15 09:11:51', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(101, 33, 1, 10000.00, '2025-06-24', 0, 'repaid', '2025-06-24 06:21:52', '2025-07-15 11:13:08', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(102, 9, 1, 10000.00, '2025-06-24', 0, 'repaid', '2025-06-25 10:44:35', '2025-07-05 05:39:53', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(103, 34, 1, 5000.00, '2025-06-27', 0, 'repaid', '2025-06-27 07:45:19', '2025-07-04 12:07:10', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(104, 9, 1, 5000.00, '2025-06-29', 0, 'repaid', '2025-06-30 07:08:30', '2025-07-10 04:41:22', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(105, 35, 1, 120000.00, '2025-06-30', 0, 'repaid', '2025-06-30 08:23:02', '2025-07-13 16:05:06', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(106, 22, 1, 4732.00, '2025-06-18', 1, 'repaid', '2025-06-30 08:32:22', '2025-06-30 08:35:45', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(107, 17, 7, 30000.00, '2025-06-30', 0, 'repaid', '2025-07-01 07:29:54', '2025-07-30 07:37:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(108, 33, 1, 10000.00, '2025-07-05', 0, 'repaid', '2025-07-05 05:38:52', '2025-07-15 10:45:11', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(109, 9, 1, 12000.00, '2025-07-05', 0, 'repaid', '2025-07-05 05:40:47', '2025-07-17 08:15:07', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(110, 29, 1, 10000.00, '2025-07-07', 0, 'repaid', '2025-07-07 08:48:13', '2025-07-17 09:47:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(111, 38, 1, 8000.00, '2025-07-07', 0, 'repaid', '2025-07-07 08:51:43', '2025-07-13 16:07:49', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(112, 34, 1, 10000.00, '2025-07-08', 0, 'repaid', '2025-07-08 07:54:40', '2025-07-18 08:31:47', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(113, 32, 3, 10000.00, '2025-07-08', 0, 'repaid', '2025-07-08 16:28:51', '2025-09-03 11:19:19', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(114, 9, 1, 6000.00, '2025-07-10', 0, 'repaid', '2025-07-10 04:42:14', '2025-07-21 18:04:26', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(115, 28, 5, 97344.00, '2025-06-30', 0, 'repaid', '2025-07-10 04:53:20', '2025-08-11 08:04:24', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(116, 28, 5, 39000.00, '2025-07-02', 0, 'repaid', '2025-07-10 05:04:53', '2025-08-12 06:54:31', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(117, 35, 1, 120000.00, '2025-07-11', 0, 'repaid', '2025-07-13 16:06:21', '2025-07-24 03:49:29', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(118, 39, 1, 2000.00, '2025-07-12', 0, 'repaid', '2025-07-13 16:12:10', '2025-07-23 18:12:25', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(119, 40, 1, 1000.00, '2025-07-14', 0, 'repaid', '2025-07-14 11:36:52', '2025-07-17 09:55:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(120, 33, 3, 25000.00, '2025-07-15', 0, 'repaid', '2025-07-15 11:04:15', '2025-08-06 11:00:34', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(121, 41, 3, 70000.00, '2025-07-15', 0, 'repaid', '2025-07-15 11:06:01', '2025-07-30 04:28:10', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(122, 9, 1, 14400.00, '2025-07-15', 0, 'repaid', '2025-07-17 08:16:16', '2025-07-27 08:59:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(123, 11, 3, 42900.00, '2025-06-30', 0, 'repaid', '2025-07-17 08:21:14', '2025-07-17 08:24:56', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(124, 11, 3, 56628.00, '2025-07-14', 0, 'repaid', '2025-07-17 08:26:55', '2025-08-07 18:46:49', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(125, 29, 1, 10000.00, '2025-07-17', 0, 'repaid', '2025-07-17 09:51:35', '2025-07-28 14:07:40', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(126, 40, 1, 5000.00, '2025-07-16', 0, 'repaid', '2025-07-17 09:56:15', '2025-08-06 05:30:36', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(127, 42, 1, 20000.00, '2025-07-17', 0, 'repaid', '2025-07-17 10:55:06', '2025-07-30 14:20:12', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(128, 34, 1, 10000.00, '2025-07-18', 0, 'repaid', '2025-07-18 08:33:17', '2025-07-30 15:33:27', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(129, 9, 1, 7200.00, '2025-07-21', 0, 'repaid', '2025-07-21 06:44:38', '2025-07-31 06:17:42', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(130, 9, 1, 17280.00, '2025-07-25', 0, 'repaid', '2025-07-27 09:00:07', '2025-08-05 08:10:54', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(131, 38, 1, 4000.00, '2025-07-20', 0, 'repaid', '2025-07-28 14:10:31', '2025-07-28 14:12:00', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(132, 43, 1, 50000.00, '2025-07-29', 0, 'pending', '2025-07-29 16:16:57', '2025-07-30 08:12:18', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(134, 17, 7, 30000.00, '2025-07-30', 0, 'repaid', '2025-07-30 07:39:09', '2025-09-01 18:26:23', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(135, 42, 1, 2400.00, '2025-07-29', 0, 'repaid', '2025-07-30 14:21:37', '2025-08-15 12:23:49', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(136, 34, 1, 12000.00, '2025-07-28', 0, 'repaid', '2025-07-30 15:34:39', '2025-08-07 17:08:07', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(137, 31, 1, 37500.00, '2025-07-30', 0, 'repaid', '2025-07-31 08:43:45', '2025-08-20 07:31:15', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(138, 9, 2, 45000.00, '2025-08-01', 0, 'repaid', '2025-08-01 06:49:04', '2025-09-03 11:13:11', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(139, 15, 7, 30000.00, '2025-08-01', 0, 'repaid', '2025-08-01 10:17:34', '2025-09-02 04:32:37', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(140, 39, 1, 3000.00, '2025-08-02', 0, 'repaid', '2025-08-02 16:04:03', '2025-08-12 18:20:30', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(141, 9, 1, 6000.00, '2025-08-04', 0, 'repaid', '2025-08-05 08:11:38', '2025-08-15 05:33:31', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(142, 40, 1, 3000.00, '2025-07-27', 0, 'repaid', '2025-08-06 05:42:30', '2025-08-14 10:08:00', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(143, 33, 3, 30000.00, '2025-07-30', 0, 'repaid', '2025-08-06 11:01:44', '2025-09-01 18:29:30', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(144, 45, 8, 300000.00, '2025-08-06', 0, 'repaid', '2025-08-06 11:26:02', '2025-08-27 10:40:44', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(145, 7, 2, 3000.00, '2025-08-06', 0, 'repaid', '2025-08-07 03:41:48', '2025-09-08 10:26:04', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(146, 34, 1, 16400.00, '2025-08-08', 0, 'repaid', '2025-08-07 17:08:54', '2025-08-20 07:29:27', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(147, 11, 3, 67953.60, '2025-07-29', 0, 'repaid', '2025-08-07 18:47:45', '2025-08-12 06:51:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(148, 29, 1, 15000.00, '2025-08-09', 0, 'repaid', '2025-08-08 10:40:52', '2025-08-21 09:20:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(149, 38, 1, 10000.00, '2025-08-09', 0, 'repaid', '2025-08-09 10:25:48', '2025-08-20 10:21:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(150, 28, 2, 126547.20, '2025-07-31', 0, 'pending', '2025-08-11 08:06:15', '2025-09-17 07:25:50', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(151, 11, 3, 66545.00, '2025-08-12', 0, 'repaid', '2025-08-12 06:52:11', '2026-03-14 19:58:21', 13, 'Colleague', 49, 0, NULL, NULL, NULL),
(152, 28, 5, 50700.00, '2025-08-03', 0, 'repaid', '2025-08-12 06:55:08', '2025-09-08 16:38:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(156, 39, 1, 10000.00, '2025-08-22', 0, 'repaid', '2025-08-13 04:11:07', '2025-09-08 15:30:31', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(158, 40, 1, 1600.00, '2025-08-06', 0, 'repaid', '2025-08-14 10:09:55', '2025-08-20 06:37:25', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(159, 9, 1, 6000.00, '2025-08-15', 0, 'repaid', '2025-08-15 05:34:14', '2025-08-28 15:08:21', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(160, 48, 1, 5000.00, '2025-08-19', 0, 'pending', '2025-08-19 15:00:21', '2025-08-19 15:00:21', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(161, 45, 8, 50000.00, '2025-08-15', 0, 'repaid', '2025-08-20 05:59:03', '2025-08-27 10:41:08', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(162, 40, 1, 1920.00, '2025-08-17', 0, 'repaid', '2025-08-20 06:38:06', '2025-08-23 06:29:55', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(163, 34, 1, 19680.00, '2025-08-19', 0, 'repaid', '2025-08-20 07:28:25', '2025-09-08 15:36:07', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(164, 29, 1, 18000.00, '2025-08-20', 0, 'repaid', '2025-08-21 09:21:11', '2025-09-04 07:02:54', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(165, 2, 1, 15000.00, '2025-08-22', 0, 'repaid', '2025-08-22 12:56:44', '2025-09-12 13:47:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(166, 9, 1, 7200.00, '2025-08-25', 0, 'repaid', '2025-08-28 15:10:28', '2025-09-04 07:38:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(167, 35, 1, 50000.00, '2025-08-28', 0, 'repaid', '2025-08-28 15:55:30', '2025-09-08 15:21:55', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(168, 17, 7, 30000.00, '2025-08-30', 0, 'repaid', '2025-09-01 18:27:10', '2025-10-01 07:15:33', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(169, 33, 3, 42000.00, '2025-08-14', 0, 'repaid', '2025-09-01 18:30:13', '2025-09-08 16:34:13', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(170, 15, 7, 30000.00, '2025-09-01', 0, 'repaid', '2025-09-02 04:31:23', '2025-10-01 07:23:06', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(171, 9, 2, 53640.00, '2025-09-02', 0, 'repaid', '2025-09-03 11:14:06', '2025-10-06 08:05:27', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(172, 29, 1, 18000.00, '2025-08-31', 0, 'repaid', '2025-09-04 07:03:32', '2025-09-11 17:51:22', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(173, 9, 1, 7200.00, '2025-09-05', 0, 'repaid', '2025-09-04 07:38:53', '2025-09-16 05:53:59', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(174, 48, 1, 15000.00, '2025-09-08', 0, 'repaid', '2025-09-08 11:04:54', '2025-09-10 07:22:24', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(175, 39, 1, 12000.00, '2025-09-02', 0, 'repaid', '2025-09-08 15:31:29', '2025-09-13 09:18:26', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(176, 34, 1, 23616.00, '2025-08-30', 0, 'repaid', '2025-09-08 15:38:24', '2025-09-10 02:49:32', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(177, 33, 3, 50600.00, '2025-08-29', 0, 'disbursed', '2025-09-08 16:35:25', '2025-09-08 16:35:25', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(178, 28, 5, 65910.00, '2025-09-04', 0, 'repaid', '2025-09-08 16:39:40', '2025-10-14 06:35:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(179, 34, 1, 28340.00, '2025-09-10', 0, 'repaid', '2025-09-10 02:50:23', '2025-09-23 03:52:29', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(180, 50, 9, 50000.00, '2025-09-12', 0, 'repaid', '2025-09-10 11:18:10', '2025-12-06 08:42:59', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(181, 41, 3, 40000.00, '2025-09-10', 0, 'repaid', '2025-09-11 10:22:55', '2025-09-25 14:52:00', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(182, 29, 1, 21600.00, '2025-09-11', 0, 'repaid', '2025-09-11 17:52:29', '2025-09-25 04:13:52', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(183, 2, 1, 18000.00, '2025-09-02', 0, 'repaid', '2025-09-12 13:48:39', '2025-09-14 11:07:03', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(184, 39, 1, 10000.00, '2025-09-13', 0, 'repaid', '2025-09-13 09:15:34', '2025-10-03 06:52:15', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(185, 2, 1, 21600.00, '2025-09-13', 0, 'repaid', '2025-09-14 11:07:33', '2025-09-25 10:29:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(186, 51, 10, 250000.00, '2025-09-13', 0, 'repaid', '2025-09-15 06:47:07', '2025-12-12 06:37:37', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(187, 35, 1, 40000.00, '2025-09-15', 0, 'repaid', '2025-09-15 08:44:32', '2025-09-25 14:53:31', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(188, 7, 2, 1000.00, '2025-09-08', 0, 'repaid', '2025-09-16 09:27:55', '2025-10-13 11:15:17', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(189, 45, 8, 136000.00, '2025-09-19', 0, 'disbursed', '2025-09-19 08:19:34', '2025-10-09 10:03:41', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(190, 31, 1, 10000.00, '2025-09-21', 0, 'repaid', '2025-09-22 07:03:35', '2025-10-01 07:10:35', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(191, 34, 1, 34008.00, '2025-09-21', 0, 'repaid', '2025-09-23 03:53:51', '2025-10-14 07:41:22', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(192, 35, 1, 40000.00, '2025-09-26', 0, 'repaid', '2025-09-25 14:54:12', '2025-10-05 00:38:40', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(193, 45, 8, 178000.00, '2025-09-29', 1, 'disbursed', '2025-09-29 11:22:47', '2025-10-23 13:12:33', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(194, 50, 1, 10000.00, '2025-09-27', 0, 'repaid', '2025-09-29 11:24:57', '2025-10-06 08:12:10', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(195, 31, 1, 12000.00, '2025-10-02', 0, 'repaid', '2025-10-01 07:11:11', '2025-10-31 07:55:12', NULL, NULL, 1, 1, '2025-10-02 21:01:08', NULL, NULL),
(196, 17, 7, 17000.00, '2025-10-01', 0, 'repaid', '2025-10-01 07:22:02', '2025-11-03 09:24:16', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(197, 15, 7, 23000.00, '2025-10-02', 0, 'repaid', '2025-10-01 07:23:40', '2025-10-27 09:48:40', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(198, 52, 3, 50000.00, '2025-10-01', 0, 'repaid', '2025-10-01 09:12:17', '2025-10-13 12:39:28', NULL, NULL, 1, 1, '2025-10-01 21:00:06', NULL, NULL),
(199, 39, 1, 12000.00, '2025-09-24', 0, 'repaid', '2025-10-03 06:52:56', '2025-10-03 06:54:09', NULL, NULL, 1, 1, '2025-09-24 21:00:00', NULL, NULL),
(200, 39, 1, 14400.00, '2025-10-04', 0, 'disbursed', '2025-10-03 06:55:07', '2025-10-03 06:55:07', NULL, NULL, 1, 1, '2025-09-25 20:55:03', NULL, NULL),
(201, 53, 1, 5000.00, '2025-10-03', 0, 'repaid', '2025-10-03 07:37:33', '2025-10-19 07:33:41', NULL, NULL, 49, 1, '2025-10-14 20:56:47', NULL, NULL),
(202, 2, 1, 10000.00, '2025-10-05', 0, 'repaid', '2025-10-06 07:55:16', '2025-10-09 14:59:08', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(203, 9, 2, 54368.00, '2025-10-02', 0, 'repaid', '2025-10-06 08:10:06', '2025-11-03 14:32:18', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(204, 50, 1, 15000.00, '2025-10-06', 0, 'repaid', '2025-10-06 09:02:10', '2025-10-15 04:06:09', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(205, 54, 1, 50000.00, '2025-10-08', 0, 'repaid', '2025-10-08 06:23:57', '2025-10-22 09:33:43', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(206, 7, 2, 1200.00, '2025-10-08', 0, 'repaid', '2025-10-13 11:16:33', '2025-10-25 08:57:48', NULL, NULL, NULL, 0, NULL, NULL, NULL),
(207, 41, 3, 30000.00, '2025-10-13', 0, 'repaid', '2025-10-13 15:46:13', '2025-10-24 09:01:05', 1, NULL, 15, 0, NULL, 'emergency', NULL),
(208, 28, 12, 55835.00, '2025-09-30', 0, 'disbursed', '2025-10-14 06:40:13', '2025-11-26 04:15:53', NULL, NULL, 49, 0, NULL, NULL, NULL),
(209, 34, 1, 30809.60, '2025-10-01', 0, 'repaid', '2025-10-14 07:42:25', '2025-10-14 07:44:21', NULL, NULL, 49, 1, '2025-10-10 20:55:29', NULL, NULL),
(210, 34, 1, 36972.00, '2025-10-11', 0, 'disbursed', '2025-10-14 07:45:38', '2025-10-14 07:49:06', 1, 'Friend', 49, 1, '2025-10-14 20:51:26', NULL, NULL),
(211, 50, 1, 20000.00, '2025-10-15', 0, 'repaid', '2025-10-15 04:07:39', '2025-10-26 17:57:02', 13, 'Friend', 49, 1, '2025-10-15 04:07:39', 'Emergency loan for 10 days', NULL),
(212, 21, 1, 100000.00, '2025-10-15', 0, 'repaid', '2025-10-15 11:58:47', '2025-10-28 09:13:54', 13, 'Collegue', NULL, 0, NULL, 'Outstanding bill', NULL),
(213, 29, 1, 10000.00, '2025-10-16', 0, 'repaid', '2025-10-16 16:28:33', '2025-10-27 04:57:35', NULL, 'Colleague', NULL, 0, NULL, 'Facilitation', NULL),
(214, 54, 1, 72000.00, '2025-10-21', 0, 'repaid', '2025-10-22 09:36:22', '2025-11-04 03:46:11', 13, 'Friend', 49, 1, '2025-10-22 09:36:22', 'roll over for the previous amount paid', NULL),
(215, 55, 1, 10000.00, '2025-10-23', 0, 'repaid', '2025-10-23 05:43:05', '2025-11-07 09:40:52', 13, 'Brother', 49, 1, '2025-10-23 05:43:05', 'Hospital Emergency', NULL),
(216, 56, 1, 30000.00, '2025-10-24', 0, 'repaid', '2025-10-24 06:01:04', '2025-11-17 09:29:27', 13, 'Friend', 49, 0, '2025-10-24 09:20:06', 'Personal Emergency', NULL),
(217, 57, 1, 50000.00, '2025-10-31', 0, 'disbursed', '2025-10-24 13:16:46', '2025-10-31 08:29:38', 2, 'Friend', 49, 1, '2025-10-31 14:35:59', 'Offsetting bills.', NULL),
(218, 53, 3, 5000.00, '2025-10-25', 0, 'pending', '2025-10-25 04:48:26', '2025-10-25 04:48:26', NULL, NULL, NULL, 1, '2025-10-25 04:48:26', 'I need more capital to grow my business profit margins right now it\'s doing okay but if possible if I get some funds the margins will increase', NULL),
(219, 53, 1, 3700.00, '2025-10-27', 0, 'repaid', '2025-10-25 06:26:04', '2025-11-07 09:58:31', NULL, NULL, NULL, 0, NULL, 'I need some more capital foe my business', NULL),
(220, 31, 1, 100000.00, '2025-10-24', 0, 'repaid', '2025-10-25 09:01:45', '2025-10-31 07:56:43', 13, 'Neighbour', 15, 0, NULL, 'Very much needed emergency and sorting KES 100,000 in a day', NULL),
(221, 15, 7, 115300.00, '2025-10-27', 0, 'repaid', '2025-10-27 09:50:27', '2025-11-28 18:28:24', 13, 'Brother', 49, 0, NULL, 'school fees payment', NULL),
(222, 50, 1, 25000.00, '2025-10-27', 0, 'repaid', '2025-10-27 10:20:21', '2025-11-07 09:45:53', 13, 'Friend', NULL, 0, NULL, 'For purchase of good for the farm', NULL),
(223, 59, 1, 5000.00, '2025-10-27', 0, 'pending', '2025-10-27 15:24:52', '2025-10-27 15:24:52', NULL, NULL, NULL, 1, '2025-10-27 15:24:52', 'I need to add some cash to support my business', NULL),
(224, 29, 1, 10000.00, '2025-10-27', 0, 'repaid', '2025-10-29 07:11:51', '2025-11-08 08:09:56', 13, NULL, NULL, 0, NULL, 'Emergency loan', NULL),
(225, 21, 5, 100000.00, '2025-10-29', 0, 'repaid', '2025-10-29 12:42:31', '2025-12-08 05:28:17', NULL, NULL, NULL, 0, NULL, 'emergency bailout', NULL),
(226, 17, 10, 18700.00, '2025-11-02', 0, 'repaid', '2025-11-03 09:25:31', '2026-02-19 10:10:11', 13, 'Son', 49, 0, NULL, 'roll over from previous loan', NULL),
(227, 2, 1, 10000.00, '2025-11-04', 0, 'repaid', '2025-11-05 13:26:50', '2025-11-19 05:17:04', 13, 'Friend', 15, 1, '2025-11-05 13:26:50', 'emergency facility', NULL),
(228, 55, 1, 10000.00, '2025-11-03', 0, 'repaid', '2025-11-07 09:42:16', '2025-11-17 09:40:58', 13, 'Friend', 49, 1, '2025-11-07 09:42:16', 'Roll over from previous loan', NULL),
(229, 50, 1, 25000.00, '2025-11-07', 0, 'repaid', '2025-11-07 09:47:34', '2025-11-17 18:48:33', 13, 'Friend', 15, 1, '2025-11-07 09:47:34', 'Roll over for previous?', NULL),
(230, 53, 1, 3000.00, '2025-11-07', 0, 'repaid', '2025-11-07 09:59:28', '2025-11-19 09:24:28', NULL, 'Friend', 15, 1, '2025-11-07 09:59:28', 'roll over from previous loan', NULL),
(231, 29, 1, 12000.00, '2025-11-06', 0, 'repaid', '2025-11-08 08:11:10', '2025-11-18 05:02:28', 15, 'Work Colleague', 1, 1, '2025-11-08 08:11:10', 'roll over from the previous loan', NULL),
(232, 48, 1, 20000.00, '2025-11-10', 0, 'repaid', '2025-11-10 15:18:48', '2025-11-25 07:02:36', 13, 'Friend', 49, 1, '2025-11-10 15:18:48', 'emergency loan', NULL),
(233, 9, 2, 35241.60, '2025-11-03', 0, 'repaid', '2025-11-10 15:20:47', '2025-12-06 08:56:13', NULL, NULL, 1, 0, NULL, 'monthly roll over', NULL),
(234, 54, 1, 66000.00, '2025-11-01', 0, 'repaid', '2025-11-10 15:23:50', '2025-11-13 17:52:10', NULL, NULL, NULL, 1, '2025-11-10 15:23:50', 'emergency loan roll over', NULL),
(235, 54, 1, 79200.00, '2025-11-10', 0, 'repaid', '2025-11-13 17:53:31', '2025-11-25 10:27:52', NULL, NULL, 49, 0, NULL, 'Emergency roll over', NULL),
(236, 56, 1, 36000.00, '2025-11-03', 0, 'repaid', '2025-11-17 09:31:07', '2025-11-17 09:32:35', 13, 'Friend', 49, 1, '2025-11-17 09:31:07', 'rolled over 10 days loan', NULL),
(237, 56, 1, 43200.00, '2025-11-13', 0, 'repaid', '2025-11-17 09:33:56', '2025-11-29 14:18:03', 13, 'Friend', 49, 1, '2025-11-17 09:33:56', 'rolled over 10 days loan', NULL),
(238, 55, 1, 12000.00, '2025-11-13', 0, 'repaid', '2025-11-17 09:42:06', '2025-11-26 19:33:06', 13, 'Friend', 49, 1, '2025-11-17 09:42:06', 'Emergency loan roll over', NULL),
(239, 50, 1, 25000.00, '2025-11-18', 0, 'repaid', '2025-11-17 18:50:45', '2025-11-28 18:25:12', 13, 'Friend', 49, 1, '2025-11-17 18:50:45', 'roll over of previous loan', NULL),
(240, 29, 1, 14400.00, '2025-11-16', 0, 'repaid', '2025-11-18 05:04:27', '2025-11-28 09:11:58', NULL, 'Friend', 1, 0, NULL, 'roll over of previous loan', NULL),
(241, 49, 2, 10000.00, '2025-11-18', 0, 'repaid', '2025-11-19 05:09:39', '2026-01-09 07:06:03', 13, 'Brother', 15, 0, NULL, 'Payment for Good Conduct and NTSA', NULL),
(242, 41, 3, 30000.00, '2025-11-18', 0, 'repaid', '2025-11-19 05:21:43', '2025-11-25 07:02:02', NULL, NULL, 1, 1, '2025-11-19 05:21:43', 'emergency loan', NULL),
(243, 53, 1, 2700.00, '2025-11-17', 0, 'repaid', '2025-11-19 09:25:49', '2025-12-03 04:46:46', NULL, NULL, 49, 0, NULL, 'Emergency roll over', NULL),
(244, 51, 2, 600000.00, '2025-11-22', 0, 'repaid', '2025-11-22 19:26:47', '2025-12-28 09:46:29', NULL, NULL, 1, 1, '2025-11-22 19:26:47', 'Affordable Housing Marsabit logistics', NULL),
(245, 52, 1, 20000.00, '2025-11-22', 0, 'repaid', '2025-11-22 19:28:34', '2025-11-28 07:47:35', NULL, NULL, 1, 1, '2025-11-22 19:28:34', 'Emergency loan for a friend', NULL),
(246, 2, 1, 20000.00, '2025-11-21', 0, 'repaid', '2025-11-22 19:33:43', '2025-12-01 19:12:08', 13, 'Friend', 15, 0, NULL, 'steph\'s birthday party', NULL),
(247, 54, 1, 95040.00, '2025-11-20', 0, 'repaid', '2025-11-25 10:28:41', '2025-12-03 04:31:52', NULL, NULL, 1, 1, '2025-11-25 10:28:41', 'ROLLED over from previous loan', NULL),
(248, 55, 1, 14400.00, '2025-11-23', 0, 'repaid', '2025-11-26 19:33:56', '2025-12-09 06:24:58', NULL, NULL, 1, 1, '2025-11-26 19:33:56', 'EMERGENCY ROLL OVER', NULL),
(249, 50, 1, 25000.00, '2025-11-28', 0, 'repaid', '2025-11-28 18:26:26', '2025-12-09 06:22:13', NULL, NULL, 1, 1, '2025-11-28 18:26:26', 'Emergency roll over', NULL),
(250, 15, 15, 126830.00, '2025-11-27', 0, 'repaid', '2025-11-28 18:29:57', '2026-03-20 18:22:22', NULL, NULL, 1, 0, NULL, 'emergency roll over', NULL),
(251, 56, 1, 43840.00, '2025-11-23', 0, 'repaid', '2025-11-29 14:19:17', '2025-12-09 06:18:14', NULL, NULL, 49, 1, '2025-11-29 14:19:17', 'emergency roll over', NULL),
(252, 2, 1, 20000.00, '2025-12-01', 0, 'repaid', '2025-12-01 19:12:52', '2025-12-23 07:31:48', NULL, NULL, 1, 1, '2025-12-01 19:12:52', 'Rolled over from previous loan', NULL),
(253, 54, 1, 114048.00, '2025-11-30', 0, 'disbursed', '2025-12-03 04:32:39', '2025-12-03 04:32:39', 1, 'FRIEND', 49, 1, '2025-12-03 04:32:39', 'Emergency roll over', NULL),
(254, 26, 2, 100000.00, '2025-11-24', 0, 'repaid', '2025-12-03 04:43:37', '2025-12-26 08:52:13', NULL, NULL, 15, 1, '2025-12-03 04:43:37', 'emergency job use', NULL),
(255, 53, 1, 2680.00, '2025-11-27', 0, 'repaid', '2025-12-03 04:47:40', '2025-12-15 06:11:10', NULL, NULL, NULL, 1, '2025-12-03 04:47:40', 'emergency rolled over', NULL),
(256, 50, 13, 50000.00, '2025-12-06', 0, 'repaid', '2025-12-06 08:45:46', '2026-03-06 12:56:45', NULL, NULL, 1, 1, '2025-12-06 08:45:46', 'repair of greenhouse in isinya', NULL),
(257, 9, 2, 42290.00, '2025-12-03', 0, 'repaid', '2025-12-06 08:54:39', '2026-01-15 16:40:05', NULL, NULL, NULL, 1, '2025-12-06 08:54:39', 'roll over from previous loan', NULL),
(258, 21, 5, 130000.00, '2025-11-29', 0, 'repaid', '2025-12-08 05:29:12', '2026-01-12 13:25:25', NULL, NULL, 1, 1, '2025-12-08 05:29:12', 'emergency roll over loan facility', NULL),
(259, 56, 1, 37608.00, '2025-12-03', 0, 'repaid', '2025-12-09 06:18:55', '2025-12-26 09:01:17', NULL, NULL, 1, 1, '2025-12-09 06:18:55', 'previous loan roll over', NULL),
(260, 50, 1, 25000.00, '2025-12-09', 0, 'repaid', '2025-12-09 06:22:55', '2025-12-23 07:29:57', NULL, NULL, 1, 1, '2025-12-09 06:22:55', 'roll over from previous loan', NULL),
(261, 55, 1, 8780.00, '2025-12-03', 0, 'repaid', '2025-12-09 06:26:57', '2025-12-26 08:57:12', NULL, NULL, 1, 1, '2025-12-09 06:26:57', 'rolled over', NULL),
(262, 26, 2, 100000.00, '2025-12-28', 0, 'repaid', '2025-12-26 08:53:48', '2026-02-26 17:58:06', 13, 'Colleague', 1, 0, NULL, 'emergency loan roll over', NULL),
(263, 55, 1, 10536.00, '2025-12-13', 0, 'repaid', '2025-12-26 08:58:13', '2025-12-26 08:59:13', NULL, NULL, 1, 1, '2025-12-26 08:58:13', 'rolled over loan', NULL),
(264, 55, 1, 12643.20, '2025-12-23', 0, 'repaid', '2025-12-26 09:00:07', '2026-01-08 14:16:05', NULL, NULL, 1, 0, NULL, 'rolled over loan', NULL),
(265, 56, 1, 45129.60, '2025-12-13', 0, 'repaid', '2025-12-26 09:03:12', '2025-12-26 09:10:15', NULL, NULL, NULL, 0, NULL, 'rolled over facility', NULL),
(266, 56, 1, 54155.50, '2025-12-23', 0, 'pending', '2025-12-26 09:05:38', '2026-04-20 08:15:32', NULL, NULL, 1, 0, NULL, 'rolled over facility', NULL),
(267, 62, 1, 5000.00, '2025-12-22', 0, 'repaid', '2025-12-26 18:05:43', '2025-12-26 18:09:31', NULL, NULL, 15, 1, '2025-12-26 18:05:43', 'emergency facility for salary', NULL),
(268, 62, 1, 5000.00, '2025-12-26', 0, 'repaid', '2025-12-26 18:06:44', '2026-01-06 09:23:35', NULL, NULL, 15, 1, '2025-12-26 18:06:44', 'emergency facility for salary', NULL),
(269, 51, 2, 720000.00, '2025-12-22', 0, 'repaid', '2025-12-28 09:47:55', '2026-02-01 19:37:16', NULL, NULL, 1, 0, NULL, 'Yes... let\'s roll over the loan. Atalipwa 20% at end of Jan.', NULL),
(270, 41, 3, 80000.00, '2025-12-29', 0, 'repaid', '2025-12-31 04:36:18', '2026-01-04 09:31:39', NULL, NULL, 1, 1, '2025-12-31 04:36:18', 'for business emergency', NULL),
(271, 53, 3, 5000.00, '2026-01-02', 0, 'pending', '2026-01-02 06:54:02', '2026-01-02 06:54:02', 13, 'Friend', NULL, 1, '2026-01-02 06:54:02', 'Added capital for my business', NULL),
(272, 2, 1, 20000.00, '2025-12-26', 0, 'repaid', '2026-01-05 01:59:34', '2026-01-05 02:00:37', NULL, NULL, 15, 1, '2026-01-05 01:59:34', 'Emergency facility', NULL),
(273, 55, 1, 15171.84, '2026-01-02', 0, 'repaid', '2026-01-08 14:13:15', '2026-01-08 14:15:42', NULL, NULL, 1, 1, '2026-01-08 14:13:15', 'rolled over', NULL),
(274, 52, 1, 40000.00, '2026-01-09', 0, 'repaid', '2026-01-09 07:01:46', '2026-01-20 08:03:10', NULL, NULL, 1, 1, '2026-01-09 07:01:46', 'emergency loan', NULL),
(275, 2, 2, 100000.00, '2026-01-09', 0, 'repaid', '2026-01-12 06:00:36', '2026-02-09 13:39:03', NULL, NULL, 15, 1, '2026-01-12 06:00:36', 'emergency loan for the month', NULL),
(276, 41, 1, 50000.00, '2026-01-09', 0, 'repaid', '2026-01-12 06:04:19', '2026-01-15 16:39:17', NULL, NULL, 1, 1, '2026-01-12 06:04:19', 'emergency loan 5-10 days', NULL),
(277, 48, 1, 35000.00, '2026-01-12', 0, 'repaid', '2026-01-12 07:00:44', '2026-01-20 08:02:39', NULL, NULL, 1, 1, '2026-01-12 07:00:44', 'china orders', NULL),
(278, 13, 10, 100000.00, '2025-12-22', 0, 'repaid', '2026-01-12 07:10:34', '2026-02-09 13:44:54', NULL, NULL, NULL, 0, NULL, 'emergency loan', NULL),
(279, 21, 5, 169000.00, '2025-12-31', 0, 'repaid', '2026-01-12 13:27:03', '2026-02-18 05:26:27', NULL, NULL, NULL, 0, NULL, 'Emergency roll over', NULL),
(280, 9, 2, 50748.00, '2026-01-03', 0, 'repaid', '2026-01-15 16:41:01', '2026-02-18 05:34:39', NULL, NULL, 1, 1, '2026-01-15 16:41:01', 'rolled over', NULL),
(281, 38, 1, 6000.00, '2026-01-16', 0, 'repaid', '2026-01-16 13:43:08', '2026-01-22 07:32:02', NULL, NULL, 1, 1, '2026-01-16 13:43:08', 'emergency loan', NULL),
(282, 65, 1, 250000.00, '2026-01-21', 0, 'repaid', '2026-01-21 14:23:34', '2026-02-03 14:41:38', 13, 'Friend', 15, 1, '2026-01-21 10:52:47', 'Emergency contact', NULL),
(283, 62, 1, 8000.00, '2026-01-22', 0, 'repaid', '2026-01-27 15:13:33', '2026-02-11 10:01:29', NULL, NULL, 15, 1, '2026-01-27 15:13:33', 'emergency facility', NULL),
(285, 2, 14, 30000.00, '2026-01-26', 0, 'repaid', '2026-01-29 08:25:29', '2026-01-29 08:26:42', NULL, NULL, 15, 1, '2026-01-29 08:25:29', 'emergency loan 1 day', NULL),
(286, 2, 1, 8000.00, '2026-01-29', 0, 'repaid', '2026-01-29 08:27:58', '2026-02-09 13:38:21', NULL, NULL, 15, 0, NULL, 'Emergency loan', NULL),
(287, 26, 2, 100000.00, '2026-01-26', 0, 'repaid', '2026-01-29 10:08:54', '2026-02-26 17:58:54', NULL, NULL, 15, 0, NULL, 'roll over emergency', NULL),
(288, 51, 2, 364000.00, '2026-01-22', 0, 'repaid', '2026-02-01 19:38:18', '2026-03-08 13:55:57', NULL, NULL, NULL, 0, NULL, 'rolled over facility', NULL),
(289, 65, 1, 300000.00, '2026-01-31', 0, 'repaid', '2026-02-03 14:42:30', '2026-02-17 13:46:53', NULL, NULL, 15, 1, '2026-02-03 14:42:30', 'Roll over loan', NULL),
(290, 3, 14, 100000.00, '2026-02-05', 0, 'repaid', '2026-02-09 13:30:31', '2026-02-09 13:36:43', 13, 'Friend', 15, 0, NULL, 'emergency brokered loan', NULL),
(291, 2, 5, 65560.00, '2026-02-09', 0, 'repaid', '2026-02-09 13:40:18', '2026-03-12 11:18:49', NULL, NULL, NULL, 0, NULL, 'ROLLED over 1 more month', NULL),
(292, 52, 1, 100000.00, '2026-02-08', 0, 'repaid', '2026-02-09 13:42:05', '2026-02-21 21:12:59', NULL, NULL, NULL, 0, NULL, 'Emergency loan', NULL),
(293, 13, 15, 252000.00, '2026-02-05', 0, 'disbursed', '2026-02-09 13:46:57', '2026-03-05 10:47:54', NULL, NULL, NULL, 0, NULL, 'New house rent 252000 - 75000 stipend', NULL),
(299, 13, 2, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 17:39:49', '2026-02-09 17:39:49', NULL, NULL, NULL, 1, '2026-02-09 17:39:49', '65789 fghjkjlk hjklk', NULL),
(300, 13, 2, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 17:47:21', '2026-02-09 17:47:21', NULL, NULL, NULL, 1, '2026-02-09 17:47:21', '65789 fghjkjlk hjklk', NULL),
(301, 13, 2, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 17:47:52', '2026-02-09 17:47:52', NULL, NULL, NULL, 1, '2026-02-09 17:47:52', '65789 fghjkjlk hjklk', NULL),
(302, 13, 1, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 17:49:25', '2026-02-09 17:49:25', NULL, NULL, NULL, 1, '2026-02-09 17:49:25', 'iojhgkjbnn ifhiloj;l', NULL),
(303, 13, 1, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 17:54:40', '2026-02-09 17:54:40', NULL, NULL, NULL, 1, '2026-02-09 17:54:39', 'iojhgkjbnn ifhiloj;l', NULL),
(304, 13, 1, 1.00, '2026-02-09', 0, 'pending', '2026-02-09 18:06:23', '2026-02-09 18:06:23', NULL, NULL, NULL, 1, '2026-02-09 18:06:23', 'iuytgkjvn jk', NULL),
(307, 3, 1, 150000.00, '2026-02-11', 0, 'repaid', '2026-02-11 16:36:36', '2026-02-24 15:56:27', NULL, NULL, NULL, 1, '2026-02-11 16:36:36', 'brokered loans', NULL),
(308, 50, 2, 15000.00, '2026-02-16', 0, 'repaid', '2026-02-17 13:40:07', '2026-04-08 05:54:18', NULL, NULL, NULL, 1, '2026-02-17 13:40:07', 'emergency loan', NULL),
(309, 3, 1, 12000.00, '2026-02-13', 0, 'repaid', '2026-02-17 13:45:43', '2026-02-24 15:57:31', NULL, NULL, NULL, 1, '2026-02-17 13:45:43', 'emergency client lan', NULL),
(310, 65, 1, 360000.00, '2026-02-10', 0, 'repaid', '2026-02-17 13:47:39', '2026-03-06 12:59:17', NULL, NULL, NULL, 1, '2026-02-17 13:47:39', 'rolled over loan', NULL),
(311, 21, 5, 219700.00, '2026-01-31', 0, 'repaid', '2026-02-18 05:27:29', '2026-03-04 06:05:40', NULL, NULL, 1, 1, '2026-02-18 05:27:29', 'rolled over loan', NULL),
(312, 9, 2, 60897.60, '2026-02-18', 0, 'repaid', '2026-02-18 05:35:31', '2026-04-02 05:16:16', NULL, NULL, 1, 1, '2026-02-18 05:35:31', 'rolled over again', NULL),
(313, 67, 1, 5000.00, '2026-02-18', 0, 'repaid', '2026-02-18 09:00:21', '2026-02-27 17:05:12', NULL, NULL, 15, 1, '2026-02-18 09:00:21', 'emergency loan', NULL),
(314, 68, 1, 30000.00, '2026-02-16', 0, 'repaid', '2026-02-19 09:20:01', '2026-02-26 17:20:28', 48, 'Friend', 1, 1, '2026-02-19 09:20:01', 'emergency loan', NULL),
(315, 31, 1, 50000.00, '2026-02-09', 0, 'repaid', '2026-02-19 09:39:32', '2026-02-19 09:40:36', NULL, NULL, 1, 0, NULL, 'emergency loan', NULL),
(316, 31, 1, 160000.00, '2026-02-18', 0, 'repaid', '2026-02-19 09:41:38', '2026-02-27 19:31:32', NULL, NULL, 1, 0, NULL, 'emergency funds 70k + roll over 30k', NULL),
(317, 41, 2, 100000.00, '2026-02-19', 0, 'repaid', '2026-02-19 10:01:24', '2026-02-28 10:22:28', NULL, NULL, 1, 0, NULL, 'Hey uko poa? Please Top me up 100k in my back  account to be paid back next week. I need to pay suppliers asap', NULL),
(318, 69, 1, 4000.00, '2026-02-19', 0, 'repaid', '2026-02-19 10:06:48', '2026-03-25 11:38:15', NULL, NULL, 1, 0, NULL, 'emergency loan', NULL),
(319, 52, 1, 40000.00, '2026-02-20', 0, 'repaid', '2026-02-21 21:14:12', '2026-02-26 06:41:37', NULL, NULL, 1, 1, '2026-02-21 21:14:12', 'roll over loan', NULL),
(320, 70, 1, 2000.00, '2026-02-18', 0, 'repaid', '2026-02-23 14:09:11', '2026-03-14 19:48:28', NULL, NULL, 1, 1, '2026-02-23 14:09:11', '1000 + 1000 due on 28th Feb', NULL),
(321, 3, 14, 181248.00, '2026-02-22', 0, 'repaid', '2026-02-24 16:04:45', '2026-03-03 06:40:19', NULL, NULL, 1, 1, '2026-02-24 16:04:45', 'Rolled over', NULL),
(322, 26, 2, 100000.00, '2026-02-26', 0, 'repaid', '2026-02-26 18:00:03', '2026-04-02 04:49:56', NULL, NULL, 15, 1, '2026-02-26 18:00:03', 'roll over and to be paid in 4 tranches', NULL),
(323, 31, 1, 192000.00, '2026-02-28', 0, 'repaid', '2026-02-27 19:33:49', '2026-03-06 12:56:13', NULL, NULL, 15, 1, '2026-02-27 19:33:49', 'rolled over loan', NULL),
(324, 3, 3, 150000.00, '2026-02-27', 0, 'repaid', '2026-03-03 06:42:10', '2026-03-14 19:49:56', NULL, NULL, 1, 1, '2026-03-03 06:42:10', 'rolled over loan', NULL),
(325, 21, 2, 285610.00, '2026-03-03', 0, 'repaid', '2026-03-04 06:06:40', '2026-04-13 16:42:26', NULL, NULL, 1, 1, '2026-03-04 06:06:40', 'rolled over', NULL),
(326, 62, 1, 3500.00, '2026-03-04', 0, 'repaid', '2026-03-04 07:39:24', '2026-04-04 11:05:46', NULL, NULL, 1, 1, '2026-03-04 07:39:24', 'emergency loan', NULL),
(327, 29, 1, 15000.00, '2026-03-03', 0, 'repaid', '2026-03-04 07:48:59', '2026-03-14 19:53:12', NULL, NULL, 1, 1, '2026-03-04 07:48:59', 'emergency lon', NULL),
(328, 53, 1, 3000.00, '2026-03-06', 0, 'repaid', '2026-03-06 10:13:12', '2026-03-12 11:24:05', NULL, NULL, 1, 1, '2026-03-06 10:13:12', 'emergency loan', NULL),
(329, 50, 3, 55000.00, '2026-03-06', 0, 'repaid', '2026-03-06 12:57:32', '2026-04-15 10:24:29', NULL, NULL, 1, 1, '2026-03-06 12:57:32', 'rolled over for ramadhan', NULL),
(330, 65, 1, 432000.00, '2026-02-20', 0, 'repaid', '2026-03-06 13:02:18', '2026-03-06 13:03:28', NULL, NULL, 15, 1, '2026-03-06 13:02:18', 'rolled over due to non-payment', NULL),
(331, 65, 1, 518400.00, '2026-03-02', 0, 'disbursed', '2026-03-06 13:04:24', '2026-03-06 13:04:24', NULL, NULL, 1, 1, '2026-03-06 13:04:24', 'rolled over due to lying and non-payment', NULL),
(332, 68, 1, 20000.00, '2026-03-08', 0, 'repaid', '2026-03-08 13:57:33', '2026-03-19 05:17:03', NULL, NULL, 1, 1, '2026-03-08 13:57:33', 'FOR a phone', NULL),
(333, 48, 1, 15000.00, '2026-03-08', 0, 'repaid', '2026-03-08 13:59:09', '2026-03-19 05:16:34', NULL, NULL, 1, 1, '2026-03-08 13:59:09', 'emergency float', NULL),
(334, 71, 14, 50000.00, '2026-03-07', 0, 'repaid', '2026-03-08 14:01:06', '2026-03-12 11:17:58', NULL, NULL, 1, 1, '2026-03-08 14:01:06', 'emergency loan facility for 5 days', NULL),
(335, 2, 5, 85228.00, '2026-03-09', 0, 'repaid', '2026-03-12 11:21:50', '2026-04-17 18:16:19', NULL, NULL, 15, 1, '2026-03-12 11:21:50', 'rolled over facility', NULL),
(336, 53, 1, 2000.00, '2026-03-12', 0, 'repaid', '2026-03-13 08:14:46', '2026-03-18 16:36:16', NULL, NULL, 1, 1, '2026-03-13 08:14:46', 'emergency loan', NULL),
(337, 41, 1, 50000.00, '2026-03-15', 0, 'repaid', '2026-03-14 19:46:05', '2026-03-26 20:58:07', NULL, NULL, 1, 0, NULL, 'emergency loan', NULL),
(338, 29, 1, 18000.00, '2026-03-13', 0, 'repaid', '2026-03-14 19:54:06', '2026-03-24 14:35:05', NULL, NULL, 1, 1, '2026-03-14 19:54:06', 'rolled over loan', NULL),
(339, 11, 1, 37500.00, '2026-03-13', 0, 'repaid', '2026-03-14 20:02:29', '2026-03-24 19:28:31', NULL, NULL, 1, 0, NULL, 'emergency loan', NULL),
(340, 62, 1, 6000.00, '2026-02-20', 0, 'repaid', '2026-03-15 18:43:08', '2026-03-15 18:47:02', NULL, NULL, 1, 1, '2026-03-15 18:43:08', 'emergency loan', NULL),
(341, 62, 1, 7200.00, '2026-03-02', 0, 'repaid', '2026-03-15 18:47:37', '2026-03-15 18:48:37', NULL, NULL, 1, 1, '2026-03-15 18:47:37', 'rolled over loan', NULL),
(342, 62, 1, 8640.00, '2026-03-12', 0, 'repaid', '2026-03-15 18:49:24', '2026-04-04 11:10:34', NULL, NULL, 1, 0, NULL, 'rolled over', NULL),
(343, 72, 1, 10000.00, '2026-03-16', 0, 'repaid', '2026-03-16 15:15:01', '2026-03-26 20:56:19', NULL, NULL, 1, 1, '2026-03-16 15:15:01', 'emergency loan', NULL),
(344, 53, 1, 5000.00, '2026-03-18', 0, 'repaid', '2026-03-19 05:18:26', '2026-03-29 16:42:41', NULL, NULL, 1, 1, '2026-03-19 05:18:26', 'Bank to M-PESA transfer of KES 5,000.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3816TGLK5577. M-PESA Ref ID: UCI6B9O9OT\r\n\r\nTsuma 10 days 20% due March 28th', NULL),
(345, 2, 1, 30000.00, '2026-03-18', 0, 'repaid', '2026-03-19 07:14:22', '2026-04-02 05:07:20', NULL, NULL, 15, 1, '2026-03-19 07:14:22', 'UCH9X9PH8Y Confirmed. Ksh30,000.00 sent to Edward  Kipsanai 0710920629 on 17/3/26 at 2:10 PM.', NULL),
(346, 48, 1, 5000.00, '2026-03-19', 0, 'repaid', '2026-03-20 15:14:52', '2026-03-26 06:04:45', NULL, NULL, 1, 0, NULL, 'emergency loan', NULL),
(347, 31, 1, 50000.00, '2026-02-28', 0, 'disbursed', '2026-03-20 15:21:35', '2026-03-20 15:21:35', NULL, NULL, 1, 1, '2026-03-20 15:21:35', 'Kengen are paying me today we have nothing however next week it’s done plus the interest I always pay when I’m stuck it’s just a payment issue. So now $34K being paid on Wednesday I will sort you. I have just said Wednesday coz of any issues one thing you see I pay I cannot default on 50K plus interest. I humbly ask you work with me I cannot default share all documentation for the same you see I can put you in my account. Chief I’m humble and asking kindly give me to then funds are being disbursed however I don’t have anything with banks on disbursing your chums is guaranteed next week those funds are paying my rent and my everything $34K is good money', NULL),
(349, 15, 15, 114439.80, '2026-02-27', 0, 'disbursed', '2026-03-20 18:24:30', '2026-03-20 18:24:30', NULL, NULL, 1, 1, '2026-03-20 18:24:30', 'rolled over loan', NULL),
(350, 50, 1, 8000.00, '2026-03-24', 0, 'repaid', '2026-03-25 07:28:16', '2026-04-02 05:09:52', NULL, NULL, 1, 1, '2026-03-25 07:28:16', 'Bank to M-PESA transfer of KES 8,000.00 to 254721544928 - Mohamed Abdirahim Abdi successfully processed. Transaction Ref ID: 3864TTHG3204. M-PESA Ref ID: UCOGCABJKN\r\n\r\n10 days at 20% facility to be paid earliest before 10 days as KES 9,600', NULL),
(351, 68, 1, 10000.00, '2026-03-25', 0, 'repaid', '2026-03-25 07:30:42', '2026-04-05 09:21:56', NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 10,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 3872ZVOL8239. M-PESA Ref ID: UCPBMAL8MF\r\n\r\n10 days 20% payable on 3rd April', NULL),
(352, 7, 2, 1500.00, '2026-03-25', 0, 'disbursed', '2026-03-25 07:54:35', '2026-03-25 07:54:47', NULL, NULL, 1, 0, NULL, 'UCP6OABVYQ Confirmed. KSH. 1,500 sent to Keneth Owino,  via MySafaricom App on 25-03-2026 11:15.', NULL),
(353, 72, 1, 10000.00, '2026-03-26', 0, 'repaid', '2026-03-26 20:57:06', '2026-04-05 15:42:55', NULL, NULL, 1, 1, '2026-03-26 20:57:06', 'rolled over loan for 5 days', NULL);
INSERT INTO `loans` (`id`, `user_id`, `loan_type_id`, `amount`, `borrow_date`, `broker_status`, `status`, `created_at`, `updated_at`, `guarantor_id`, `guarantor_relationship`, `loan_officer_id`, `consent`, `consent_date`, `reason`, `due_date`) VALUES
(354, 29, 1, 20000.00, '2026-03-26', 0, 'repaid', '2026-03-27 08:35:47', '2026-04-08 06:05:43', NULL, NULL, 1, 1, '2026-03-27 08:35:47', 'Bank to M-PESA transfer of KES 20,000.00 to 254721655906 - OTIENO NIGEL successfully processed. Transaction Ref ID: 3883JNTY9707. M-PESA Ref ID: UCQKBAE0DG', NULL),
(355, 53, 1, 1000.00, '2026-03-27', 0, 'repaid', '2026-03-29 16:48:58', '2026-04-08 06:09:41', NULL, NULL, 1, 1, '2026-03-29 16:48:58', 'UCR6OAKFLQ Confirmed. KSH. 1,000 sent to EMMANUEL TSUMA,  via MySafaricom App on 27-03-2026 14:35.', NULL),
(356, 67, 1, 5000.00, '2026-03-28', 0, 'repaid', '2026-03-29 16:50:57', '2026-04-13 16:38:31', NULL, NULL, 1, 1, '2026-03-29 16:50:57', 'Bank to M-PESA transfer of KES 5,000.00 to 254700742394 - MICHAEL NZUKA MUSYIMI successfully processed. Transaction Ref ID: 3902DEVE0397. M-PESA Ref ID: UCSOIB1OMC', NULL),
(357, 68, 1, 45000.00, '2026-03-30', 0, 'repaid', '2026-03-29 16:54:56', '2026-04-14 06:39:52', NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 45,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 3907OPGV8266. M-PESA Ref ID: UCTBMB1ZDN', NULL),
(358, 31, 1, 10000.00, '2026-03-27', 0, 'disbursed', '2026-03-29 17:14:25', '2026-03-29 17:14:37', NULL, NULL, 1, 0, NULL, 'Dear DENNIS, MPESA transfer of KES 10000 to LEON MUSAU-254720747652 at 27-03-2026 10:05 PM was successful.MPESA Ref:UCR4AB9NS5.\r\n\r\nRepayable on Monday as KES 12000', NULL),
(359, 58, 14, 10000.00, '2026-04-01', 0, 'repaid', '2026-04-02 04:47:36', '2026-04-04 11:13:51', NULL, NULL, 1, 1, '2026-04-02 04:47:36', 'Bank to M-PESA transfer of KES 10,000.00 to 254722778298 - VIVIAN NEKESA SIMIYU successfully processed. Transaction Ref ID: 3936JYNW2747. M-PESA Ref ID: UD132BBR99', NULL),
(360, 26, 2, 100000.00, '2026-03-26', 0, 'disbursed', '2026-04-02 04:57:05', '2026-04-02 04:57:05', NULL, NULL, 15, 1, '2026-04-02 04:57:05', 'rolled over loan', NULL),
(361, 53, 1, 3200.00, '2026-03-31', 0, 'repaid', '2026-04-02 04:59:02', '2026-04-13 16:27:29', NULL, NULL, 1, 1, '2026-04-02 04:59:02', 'Bank to M-PESA transfer of KES 3,200.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3926CFQD3972. M-PESA Ref ID: UCV6BB0M8H\r\n\r\nDue in 10 days 20%', NULL),
(362, 11, 1, 5000.00, '2026-03-31', 0, 'repaid', '2026-04-02 05:01:37', '2026-04-13 16:22:56', NULL, NULL, 1, 1, '2026-04-02 05:01:37', 'Bank to M-PESA transfer of KES 5,000.00 to 254724606690 - SHADRACK CHERUIYOT successfully processed. Transaction Ref ID: 3936VBGY2825. M-PESA Ref ID: UD1HDBDUSY', NULL),
(363, 2, 1, 25000.00, '2026-03-30', 0, 'repaid', '2026-04-02 05:08:26', '2026-04-17 18:13:28', NULL, NULL, 15, 1, '2026-04-02 05:08:26', 'UCU9XB5HFB Confirmed. Ksh25,000.00 sent to Edward  Kipsanai 0710920629 on 30/3/26 at 6:35 PM.', NULL),
(364, 9, 2, 73077.12, '2026-03-18', 0, 'disbursed', '2026-04-02 05:17:32', '2026-04-02 05:18:17', NULL, NULL, 1, 0, NULL, 'rolled over facility because of an office scandal and no payment for a month', NULL),
(365, 48, 1, 25000.00, '2026-04-04', 0, 'repaid', '2026-04-04 10:58:42', '2026-04-18 18:09:11', NULL, NULL, 1, 1, '2026-04-04 10:58:42', 'Bank to M-PESA transfer of KES 25,000.00 to 254704815115 - Sharon Chemurgor successfully processed. Transaction Ref ID: 3960XHRB9386. M-PESA Ref ID: UD4ALBIZKO\r\n\r\nDue in 10 days at 20% interest \r\n\r\nPayable on or before 14/4/2026 KES 30,000', NULL),
(366, 62, 1, 4200.00, '2026-03-14', 0, 'repaid', '2026-04-04 11:06:48', '2026-04-04 11:07:35', NULL, NULL, 1, 1, '2026-04-04 11:06:48', 'rolled over', NULL),
(367, 62, 1, 5040.00, '2026-03-24', 0, 'repaid', '2026-04-04 11:08:07', '2026-04-13 17:18:59', NULL, NULL, 1, 1, '2026-04-04 11:08:07', 'rolled over', NULL),
(368, 62, 1, 10368.00, '2026-03-21', 0, 'repaid', '2026-04-04 11:10:14', '2026-04-13 17:17:21', NULL, NULL, 1, 1, '2026-04-04 11:10:14', 'rolled over', NULL),
(369, 72, 1, 10000.00, '2026-04-05', 0, 'repaid', '2026-04-05 15:43:36', '2026-04-16 15:07:20', 68, NULL, 1, 1, '2026-04-05 15:43:36', 'rolled over loan', NULL),
(370, 29, 1, 24000.00, '2026-04-05', 0, 'repaid', '2026-04-08 06:06:39', '2026-04-16 15:00:18', NULL, NULL, 1, 1, '2026-04-08 06:06:39', '[21:48, 07/04/2026] Dennis Kibet: We will have to roll over\r\n[22:41, 07/04/2026] Nigel Cecil Otieno Loans Client: Niaje, nikama itabidi', NULL),
(371, 53, 1, 1500.00, '2026-04-08', 0, 'repaid', '2026-04-08 06:12:05', '2026-04-20 07:41:07', NULL, NULL, 1, 1, '2026-04-08 06:12:05', 'Bank to M-PESA transfer of KES 1,500.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 3993UINQ7428. M-PESA Ref ID: UD86BBWAX7', NULL),
(372, 11, 1, 5000.00, '2026-04-10', 0, 'repaid', '2026-04-13 16:24:48', '2026-04-21 04:04:50', NULL, NULL, 1, 0, NULL, 'roll over for the next 10 days', NULL),
(373, 53, 1, 3040.00, '2026-04-10', 0, 'repaid', '2026-04-13 16:30:11', '2026-04-22 06:42:33', NULL, NULL, 1, 1, '2026-04-13 16:30:11', 'rolled over for the next 10 days', NULL),
(374, 21, 2, 342732.00, '2026-04-03', 0, 'disbursed', '2026-04-13 16:43:27', '2026-04-13 16:43:27', NULL, NULL, 1, 1, '2026-04-13 16:43:27', 'rolled over facility', NULL),
(375, 62, 1, 18489.00, '2026-03-31', 0, 'repaid', '2026-04-13 17:18:24', '2026-04-13 17:23:12', NULL, NULL, 1, 0, NULL, 'roll over facility', NULL),
(376, 62, 1, 22186.80, '2026-04-10', 0, 'repaid', '2026-04-13 17:24:58', '2026-04-22 06:40:46', NULL, NULL, 1, 0, NULL, 'rolled over', NULL),
(377, 64, 1, 10000.00, '2026-04-15', 0, 'disbursed', '2026-04-15 10:11:31', '2026-04-15 10:11:31', NULL, NULL, 1, 1, '2026-04-15 10:11:31', 'UDFAI12TXH Confirmed. You have received Ksh10,000.00 from IM BANK LIMITED- APP on 15/4/26 at 2:35 PM. New M-PESA balance is Ksh10,392.57. Buy goods with M-PESA.', NULL),
(378, 67, 1, 7500.00, '2026-04-14', 0, 'repaid', '2026-04-15 10:20:49', '2026-04-23 06:21:46', NULL, NULL, 1, 1, '2026-04-15 10:20:49', 'Bank to M-PESA transfer of KES 7,500.00 to 254700742394 - MICHAEL NZUKA MUSYIMI successfully processed. Transaction Ref ID: 4049LFRF6379. M-PESA Ref ID: UDEOI142NU', NULL),
(379, 68, 1, 10000.00, '2026-04-15', 0, 'disbursed', '2026-04-15 10:22:37', '2026-04-15 10:22:37', 48, 'Friend', 1, 1, '2026-04-15 10:22:37', 'Bank to M-PESA transfer of KES 10,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 4055ZDCU6256. M-PESA Ref ID: UDFBM13SLE', NULL),
(380, 50, 13, 66000.00, '2026-03-20', 0, 'disbursed', '2026-04-15 10:25:47', '2026-04-15 10:26:22', NULL, NULL, 1, 0, NULL, 'roll over facility for 3 months', NULL),
(381, 72, 1, 10000.00, '2026-04-15', 0, 'disbursed', '2026-04-16 15:08:27', '2026-04-16 15:08:27', 68, 'Friend', 1, 1, '2026-04-16 15:08:27', 'rolled over loan', NULL),
(382, 2, 1, 30000.00, '2026-04-09', 0, 'repaid', '2026-04-17 18:14:40', '2026-04-22 07:01:57', NULL, NULL, 15, 1, '2026-04-17 18:14:40', 'rolled over facility', NULL),
(383, 2, 5, 110796.40, '2026-04-09', 0, 'disbursed', '2026-04-17 18:17:37', '2026-04-17 19:00:26', NULL, NULL, 15, 0, NULL, 'Rolled over', NULL),
(384, 11, 1, 6000.00, '2026-04-20', 0, 'disbursed', '2026-04-21 04:05:45', '2026-04-21 04:05:45', NULL, NULL, 1, 1, '2026-04-21 04:05:45', '[09:03, 21/04/2026] Dennis Kibet: We roll over?\r\n[09:04, 21/04/2026] Shady Kip Cheruiyot eCitizen: Yew sir, currently it\'s bad\r\n[09:04, 21/04/2026] Dennis Kibet: Sawa chief, you will sort when paid', NULL),
(385, 68, 1, 20000.00, '2026-04-20', 0, 'disbursed', '2026-04-22 06:45:45', '2026-04-22 06:45:56', NULL, NULL, 1, 0, NULL, 'Bank to M-PESA transfer of KES 15,000.00 to 254791733405 - DEBORAH FAITH MURGOR successfully processed. Transaction Ref ID: 4102UYKB3384. M-PESA Ref ID: UDKBM1Q6KM\r\n\r\nPayable 30/4/2016 as KES 18,000', NULL),
(386, 70, 1, 1000.00, '2026-04-22', 0, 'disbursed', '2026-04-22 07:00:21', '2026-04-22 07:00:21', NULL, NULL, 1, 1, '2026-04-22 07:00:21', 'Bank to M-PESA transfer of KES 1,000.00 to 0725408209 - nigel kimutai yegon successfully processed. Transaction Ref ID: 4114DTIN9494. M-PESA Ref ID: UDMIT1JYA4 \r\n\r\n10 DAYS 20% INTEREST FACILITY \r\nPayable on or before 1st May as KES 1200', NULL),
(387, 2, 1, 10000.00, '2026-04-22', 0, 'disbursed', '2026-04-23 05:54:09', '2026-04-23 05:54:09', NULL, NULL, 15, 1, '2026-04-23 05:54:09', 'UDM9X1UI8J Confirmed. Ksh10,000.00 sent to Edward  Kipsanai 0710920629 on 22/4/26 at 12:57 PM.', NULL),
(388, 58, 14, 10000.00, '2026-04-23', 0, 'disbursed', '2026-04-23 06:52:30', '2026-04-23 06:52:30', NULL, NULL, 1, 1, '2026-04-23 06:52:30', 'Bank to M-PESA transfer of KES 10,000.00 to 254722778298 - VIVIAN NEKESA SIMIYU successfully processed. Transaction Ref ID: 4124CXRR4189. M-PESA Ref ID: UDN321V2ZZ\r\n\r\n5 days 11k', NULL),
(389, 53, 1, 5000.00, '2026-04-23', 0, 'disbursed', '2026-04-26 06:43:02', '2026-04-26 06:43:02', NULL, NULL, 1, 1, '2026-04-26 06:43:02', 'Bank to M-PESA transfer of KES 3,300.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4125JYFE4889. M-PESA Ref ID: UDN6B1PHVO\r\n\r\nPayable 3rd May 2026 as KES 3,960\r\n\r\nBank to M-PESA transfer of KES 1,700.00 to 0768384462 - EMMANUEL RUWA TSUMA successfully processed. Transaction Ref ID: 4127SODY1166. M-PESA Ref ID: UDN6B1RC19\r\n\r\n5k total', NULL);

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `loan_types`
--

INSERT INTO `loan_types` (`id`, `name`, `period`, `unit`, `interest_rate`, `penalty_rate`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Emergency 10 Days', 10, 'days', 20.00, 10.00, 'Upto 250k!', NULL, NULL),
(2, 'Quick Loans 1 month 20%', 1, 'months', 20.00, 10.00, 'Upto 250k! Monthly', NULL, NULL),
(3, 'Quick Loans 14', 2, 'weeks', 20.00, 10.00, 'Upto 250k! 2 weeks!', NULL, NULL),
(4, 'Quick Loans 3 months', 3, 'months', 95.00, 10.00, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(5, 'Quick Loans 1 months 30%', 1, 'months', 30.00, 10.00, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(6, 'Emergency Loans 2 weeks 35%', 2, 'weeks', 35.00, 10.00, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(7, 'Quick Loans 1 month 10%', 1, 'months', 10.00, 1.00, 'Upto 250k! Monthly', '2025-06-30 10:40:26', '2025-06-30 10:40:26'),
(8, 'Quick Loans 2 weeks +1 repayment week at 20%', 3, 'weeks', 20.00, 10.00, 'Upto 1m! Monthly', '2025-06-30 10:40:26', '2025-06-30 10:40:26'),
(9, 'Quick Loans 3 months 50% ', 3, 'months', 50.00, 10.00, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(10, 'Quick Loans 3 months 10% monthly', 3, 'months', 30.00, 10.00, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(11, 'Quick Loans 2 months 15% monthly', 2, 'months', 30.00, 10.00, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(12, 'Quick Loans 2 months 20% monthly', 2, 'months', 20.00, 10.00, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(13, 'Quick Loans 3 months 60% ', 3, 'months', 60.00, 10.00, 'Upto 250k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07'),
(14, 'Emergency 5 Days', 5, 'days', 10.00, 7.50, 'Upto 250k!', '2026-01-29 11:23:31', '2026-01-29 11:23:23'),
(15, 'Family 3 months 2% monthly', 3, 'months', 6.00, 10.00, 'Upto 300k! 2 weeks!', '2025-05-29 15:05:07', '2025-05-29 15:05:07');

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
(17, '2025_04_18_125222_add_broker_status_to_loans_table', 3);

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
('michellesese99@gmail.com', '$2y$12$BZy//467z3uCC2crpZ5CFukt7hZI.Bm2NHvXxC8JOtjSdmdOa5sae', '2025-05-07 09:51:10');

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
-- Table structure for table `repayments`
--

CREATE TABLE `repayments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `loan_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction` varchar(255) DEFAULT NULL,
  `repayment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `repayments`
--

INSERT INTO `repayments` (`id`, `loan_id`, `amount`, `transaction`, `repayment_date`, `created_at`, `updated_at`) VALUES
(1, 2, 36000.00, 'EQA5089A5329AF1', '2025-03-17', '2025-04-17 12:24:27', '2025-04-17 12:24:27'),
(2, 3, 60000.00, 'U95AD8AD7E6EA', '2025-04-07', '2025-04-17 12:32:04', '2025-04-17 12:32:04'),
(3, 1, 14400.00, 'EQA189AC2D294C4', '2025-03-19', '2025-04-17 12:35:37', '2025-04-17 12:35:37'),
(4, 1, 14400.00, 'EQAC17C9FA5FC3B', '2025-03-20', '2025-04-17 12:36:30', '2025-04-17 12:36:30'),
(5, 1, 14400.00, 'EQAEC70D699D3EB', '2025-03-21', '2025-04-17 12:36:58', '2025-04-17 12:36:58'),
(6, 1, 14400.00, 'EQA8DAD4708443D', '2025-03-21', '2025-04-17 12:37:36', '2025-04-17 12:37:36'),
(7, 1, 31000.00, 'EQA3A0AE105D065', '2025-03-21', '2025-04-17 12:38:41', '2025-04-17 12:40:31'),
(8, 1, 44000.00, 'ROLL OVER', '2025-03-21', '2025-04-17 12:40:14', '2025-04-17 12:40:25'),
(9, 4, 8400.00, '01104493106350', '2025-04-10', '2025-04-17 12:44:18', '2025-04-17 12:44:18'),
(10, 4, 4200.00, '01104493106350', '2025-04-12', '2025-04-17 12:45:49', '2025-04-17 12:45:49'),
(11, 4, 4200.00, 'TDA87V3XFY', '2025-04-12', '2025-04-17 12:47:06', '2025-04-17 12:47:06'),
(12, 5, 12000.00, 'TD20632GE4', '2025-04-02', '2025-04-17 13:09:39', '2025-04-17 13:09:39'),
(13, 8, 12000.00, 'TDH6324N9U', '2025-04-17', '2025-04-17 13:25:21', '2025-04-17 13:25:21'),
(14, 1, 69000.00, 'TCM9P0JW2B', '2025-03-21', '2025-04-17 14:46:53', '2025-04-17 14:46:53'),
(15, 7, 500.00, 'TDI4BPKKSU', '2025-04-18', '2025-04-19 18:32:33', '2025-04-19 18:32:33'),
(16, 14, 20000.00, 'TCQ476125S', '2025-03-26', '2025-04-20 12:37:10', '2025-04-20 12:37:10'),
(17, 14, 4000.00, 'TD14WWGMI4', '2025-04-01', '2025-04-20 12:38:27', '2025-04-20 12:38:27'),
(18, 16, 40000.00, 'TDC1HB4VZX', '2025-04-11', '2025-04-20 12:54:22', '2025-04-20 12:54:22'),
(19, 15, 60000.00, 'TD14WWGMI4', '2025-04-03', '2025-04-20 12:59:21', '2025-04-20 12:59:21'),
(20, 16, 20000.00, 'ROLL OVER', '2025-04-11', '2025-04-20 13:05:18', '2025-04-20 13:05:18'),
(21, 19, 24000.00, 'ROLL OVER', '2025-04-17', '2025-04-20 13:46:55', '2025-04-20 13:46:55'),
(22, 4, 42000.00, 'ROLL OVER', '2025-04-14', '2025-04-20 13:55:40', '2025-04-20 13:55:40'),
(23, 22, 10000.00, 'TC69M0KVRT', '2025-03-06', '2025-04-20 16:23:47', '2025-04-20 16:23:47'),
(24, 22, 2000.00, 'TC54HGZ58I', '2025-03-05', '2025-04-20 16:25:47', '2025-04-20 16:25:47'),
(25, 23, 6000.00, 'EQAC4978BCD7F1F', '2025-03-11', '2025-04-20 16:35:46', '2025-04-20 16:35:46'),
(26, 28, 60000.00, 'PESALINK', '2025-04-08', '2025-04-20 17:34:42', '2025-04-20 17:34:42'),
(27, 25, 14000.00, '01104493106350', '2025-03-27', '2025-04-20 18:00:21', '2025-04-20 18:00:21'),
(28, 31, 60000.00, 'EQA390888BD09E7', '2025-04-15', '2025-04-20 18:18:46', '2025-04-20 18:18:46'),
(29, 11, 18000.00, 'ROLL OVER', '2025-04-18', '2025-04-21 06:16:54', '2025-04-21 06:16:54'),
(30, 29, 13200.00, 'CREDIT DISCOUNT', '2025-05-04', '2025-04-21 06:47:07', '2025-04-21 06:47:07'),
(31, 33, 12000.00, 'CREDIT DISCOUNT', '2025-04-07', '2025-04-21 06:47:41', '2025-04-21 06:47:41'),
(32, 36, 52800.00, 'EQA3409E6C019A9', '2025-03-30', '2025-04-21 09:12:48', '2025-04-21 09:12:48'),
(33, 17, 26400.00, 'TDO6YKKJLM', '2025-04-23', '2025-04-24 06:33:14', '2025-04-24 06:33:14'),
(34, 7, 500.00, 'TDN9UG73HL', '2025-04-23', '2025-04-24 06:36:45', '2025-04-24 06:36:45'),
(35, 13, 36000.00, 'TDO9ZB5EZ9', '2025-04-23', '2025-04-24 06:44:49', '2025-04-24 06:44:49'),
(36, 7, 500.00, 'TDP67PUETG', '2025-04-25', '2025-04-26 07:35:08', '2025-04-26 07:35:08'),
(37, 21, 10000.00, 'TDP56YZ8C5', '2025-04-25', '2025-04-26 12:11:42', '2025-04-26 12:11:42'),
(38, 35, 18000.00, 'TDO614OO2G', '2025-04-24', '2025-04-26 12:21:02', '2025-04-26 12:21:02'),
(39, 35, 3600.00, 'CREDIT DISCOUNT', '2025-04-24', '2025-04-26 12:22:05', '2025-04-26 12:22:05'),
(40, 21, 10000.00, 'TDQ5C2VU9B', '2025-04-26', '2025-04-26 14:13:28', '2025-04-26 14:13:28'),
(41, 9, 15000.00, 'ROLL OVER', '2025-04-27', '2025-04-27 06:25:11', '2025-04-27 06:25:11'),
(42, 9, 3000.00, 'ROLL OVER', '2025-04-27', '2025-04-27 06:25:20', '2025-04-27 06:25:20'),
(43, 34, 23400.00, 'CREDIT DISCOUNT', '2025-04-27', '2025-04-27 06:39:33', '2025-05-09 17:58:47'),
(44, 20, 39600.00, 'TDS6JQVNWK', '2025-04-28', '2025-04-28 09:22:16', '2025-04-28 09:22:16'),
(45, 20, 11880.00, 'TDS1KBHGED', '2025-04-28', '2025-04-29 06:08:00', '2025-04-29 06:08:00'),
(46, 21, 45520.00, 'EQAB4681ED6B0C6', '2025-04-28', '2025-04-29 06:10:14', '2025-04-29 06:10:14'),
(47, 38, 10000.00, 'TE296HCIJ7', '2025-05-03', '2025-05-02 19:30:28', '2025-05-02 19:30:28'),
(48, 38, 50000.00, 'ROLL OVER', '2025-05-03', '2025-05-02 19:30:41', '2025-05-02 19:30:41'),
(49, 7, 500.00, 'TE24681AZY', '2025-05-02', '2025-05-02 19:34:39', '2025-05-02 19:34:39'),
(50, 10, 10000.00, 'TE399DAFX9', '2025-05-03', '2025-05-03 13:03:04', '2025-05-03 13:03:04'),
(51, 10, 10000.00, 'TE36WXX7Q', '2025-05-04', '2025-05-04 09:19:52', '2025-05-04 09:19:52'),
(52, 30, 7200.00, 'ROLL OVER', '2025-05-04', '2025-05-05 09:58:22', '2025-05-05 09:58:22'),
(53, 37, 1000.00, 'TE53I056SL', '2025-05-05', '2025-05-05 09:59:54', '2025-05-05 09:59:54'),
(54, 37, 5000.00, 'CREDIT DISCOUNT', '2025-05-05', '2025-05-05 16:19:15', '2025-05-05 16:19:15'),
(55, 24, 6000.00, 'CREDIT DISCOUNT', '2025-04-08', '2025-05-05 17:39:16', '2025-05-05 19:09:25'),
(56, 12, 32400.00, 'TE63KRXDKP', '2025-05-05', '2025-05-06 08:45:23', '2025-05-06 08:45:23'),
(57, 12, 25920.00, 'CREDIT DISCOUNT', '2025-05-05', '2025-05-06 08:45:40', '2025-05-06 08:45:40'),
(58, 6, 15600.00, 'CREDIT DISCOUNT', '2025-05-04', '2025-05-06 08:47:42', '2025-05-06 08:47:42'),
(59, 10, 22000.00, 'TE71S62LDL', '2025-05-07', '2025-05-07 14:47:00', '2025-05-07 14:47:00'),
(60, 10, 19200.00, 'CREDIT DISCOUNT', '2025-05-07', '2025-05-07 14:47:21', '2025-05-07 14:47:21'),
(61, 39, 3000.00, 'TE76SX0DTS', '2025-05-07', '2025-05-07 14:48:34', '2025-05-07 14:48:34'),
(62, 39, 15000.00, 'CREDIT DISCOUNT', '2025-05-07', '2025-05-07 14:48:54', '2025-05-07 14:48:54'),
(63, 55, 18000.00, 'TE89Y1GGWP', '2025-05-08', '2025-05-08 18:47:00', '2025-05-08 18:47:09'),
(64, 48, 5000.00, 'TE89Y1GGWP', '2025-05-08', '2025-05-08 18:47:50', '2025-05-08 18:47:50'),
(65, 48, 1000.00, 'CREDIT DISCOUNT', '2025-05-08', '2025-05-09 17:56:42', '2025-05-09 17:56:42'),
(66, 45, 78000.00, 'CREDIT DISCOUNT', '2025-05-12', '2025-05-12 09:03:29', '2025-05-12 09:03:29'),
(67, 46, 72000.00, 'CREDIT DISCOUNT', '2025-05-15', '2025-05-15 07:46:09', '2025-05-15 07:46:09'),
(68, 51, 18720.00, 'CREDIT DISCOUNT', '2025-05-14', '2025-05-15 07:48:42', '2025-05-15 07:48:42'),
(69, 56, 36000.00, 'TEG0Z39NQ4', '2025-05-16', '2025-05-16 12:52:38', '2025-05-16 12:52:55'),
(70, 50, 120000.00, 'TEG41W5OJ8', '2025-05-16', '2025-05-16 19:26:14', '2025-05-16 19:26:14'),
(71, 54, 2000.00, 'TEH26DU1HU', '2025-05-17', '2025-05-18 08:10:16', '2025-05-18 08:10:16'),
(72, 54, 10000.00, 'CREDIT DISCOUNT', '2025-05-17', '2025-05-18 08:10:32', '2025-05-18 08:10:32'),
(73, 57, 24000.00, 'EQA4124F758F614', '2025-05-17', '2025-05-18 08:12:12', '2025-05-22 05:25:33'),
(74, 18, 8400.00, 'TDA87V3XFY', '2025-04-10', '2025-05-18 08:27:58', '2025-05-18 08:27:58'),
(75, 18, 8400.00, 'TDC6HOPL40', '2025-04-13', '2025-05-18 08:28:46', '2025-05-18 08:30:44'),
(76, 18, 42000.00, 'CREDIT DISCOUNT', '2025-04-14', '2025-05-18 08:30:30', '2025-05-18 08:31:10'),
(77, 64, 5200.00, 'TEJ3FEKDDD', '2025-05-19', '2025-05-19 17:12:32', '2025-05-19 17:12:32'),
(78, 59, 93600.00, 'TEK2G9HY6U', '2025-05-20', '2025-05-20 04:29:27', '2025-05-20 04:29:27'),
(79, 57, 30000.00, 'EQA36E31C95BF09', '2025-05-20', '2025-05-22 05:21:27', '2025-05-22 05:26:31'),
(80, 49, 15000.00, 'BANK TRANSFER', '2025-05-21', '2025-05-22 05:31:20', '2025-05-22 05:31:20'),
(81, 66, 3000.00, 'TEN5WSPDFH', '2025-05-23', '2025-05-24 11:58:24', '2025-05-24 11:58:24'),
(82, 61, 60000.00, 'TEQ4ACH32U', '2025-05-26', '2025-05-26 10:28:30', '2025-05-26 10:28:30'),
(83, 61, 7200.00, 'CREDIT DISCOUNT', '2025-05-26', '2025-05-26 10:29:13', '2025-05-26 10:29:13'),
(84, 61, 27840.00, 'CREDIT DISCOUNT', '2025-05-26', '2025-05-26 10:30:57', '2025-05-26 10:32:24'),
(85, 60, 10000.00, 'TEQ3CNJ40F', '2025-05-26', '2025-05-26 16:54:02', '2025-05-26 16:54:02'),
(86, 60, 12000.00, 'CREDIT DISCOUNT', '2025-05-26', '2025-05-26 16:54:30', '2025-05-26 16:55:04'),
(87, 60, 50000.00, 'CREDIT DISCOUNT', '2025-05-26', '2025-05-26 16:54:50', '2025-05-26 16:54:50'),
(88, 26, 720.00, 'CREDIT DISCOUNT', '2025-05-21', '2025-05-27 16:47:54', '2025-05-27 16:47:54'),
(89, 32, 34800.00, 'CREDIT DISCOUNT', '2025-05-04', '2025-05-27 16:49:17', '2025-05-27 16:49:17'),
(90, 41, 9600.00, 'CREDIT DISCOUNT', '2025-05-27', '2025-05-27 16:51:47', '2025-05-27 16:52:03'),
(91, 65, 14500.00, 'TEU3TMO5M1', '2025-05-30', '2025-05-30 15:21:08', '2025-05-30 15:21:08'),
(92, 65, 2660.00, 'CREDIT DISCOUNT', '2025-05-30', '2025-05-30 15:21:30', '2025-05-30 15:22:13'),
(93, 65, 1560.00, 'CREDIT DISCOUNT', '2025-05-30', '2025-05-30 15:22:27', '2025-05-30 15:23:12'),
(94, 68, 5000.00, 'CREDIT DISCOUNT', '2025-05-29', '2025-05-31 11:06:24', '2025-05-31 11:06:24'),
(95, 68, 8200.00, 'CREDIT DISCOUNT', '2025-05-29', '2025-05-31 11:07:01', '2025-05-31 11:07:01'),
(96, 68, 1200.00, 'CREDIT DISCOUNT', '2025-05-29', '2025-05-31 11:08:18', '2025-05-31 11:08:18'),
(97, 73, 2000.00, 'TF31C62QNH', '2025-06-03', '2025-06-03 08:11:11', '2025-06-03 08:11:11'),
(98, 73, 10000.00, 'ROLL OVER', '2025-06-03', '2025-06-03 08:11:11', '2025-06-03 08:11:42'),
(99, 70, 5000.00, 'TF33ENE499', '2025-06-03', '2025-06-03 15:41:36', '2025-06-05 05:43:49'),
(100, 70, 55000.00, 'ROLL OVER', '2025-06-03', '2025-06-03 15:42:03', '2025-06-03 15:42:17'),
(101, 69, 35000.00, 'TF30FA9W36', '2025-06-03', '2025-06-03 19:10:38', '2025-06-03 19:10:38'),
(102, 69, 25000.00, 'TF48H5UIGW', '2025-06-03', '2025-06-03 19:11:06', '2025-06-04 08:47:48'),
(103, 69, 6000.00, 'CREDIT DISCOUNT', '2025-06-03', '2025-06-03 19:11:37', '2025-06-03 19:11:37'),
(104, 74, 60000.00, 'TF46HA3GPK', '2025-06-04', '2025-06-04 07:57:27', '2025-06-04 09:30:28'),
(105, 71, 18000.00, 'ROLL OVER', '2025-06-05', '2025-06-06 07:59:54', '2025-06-06 07:59:54'),
(106, 49, 45000.00, 'BANK TRANSFER', '2025-06-06', '2025-06-06 08:12:05', '2025-06-06 08:12:05'),
(107, 40, 18720.00, 'BANK TRANSFER', '2025-06-06', '2025-06-06 08:13:05', '2025-06-06 08:13:05'),
(108, 83, 3000.00, 'TF861RBVNS', '2025-06-08', '2025-06-09 03:36:45', '2025-06-09 03:36:45'),
(109, 83, 6840.00, 'ROLL OVER', '2025-06-08', '2025-06-09 03:38:08', '2025-06-09 03:38:08'),
(110, 77, 39600.00, 'ROLL OVER', '2025-06-09', '2025-06-10 05:06:45', '2025-06-10 05:07:10'),
(111, 76, 18000.00, 'TFA1B0OMEZ', '2025-06-10', '2025-06-10 09:26:58', '2025-06-10 09:26:58'),
(112, 86, 10000.00, 'ROLL OVER', '2025-06-13', '2025-06-15 05:14:44', '2025-06-15 14:31:27'),
(113, 86, 2000.00, 'TFF5XZSFDV', '2025-06-15', '2025-06-15 14:32:10', '2025-06-15 14:32:10'),
(114, 86, 2400.00, 'CREDIT DISCOUNT', '2025-06-15', '2025-06-15 14:32:24', '2025-06-15 14:33:08'),
(115, 64, 12000.00, 'TFG03QKUBU', '2025-06-16', '2025-06-16 13:09:33', '2025-06-16 13:09:33'),
(116, 64, 5179.20, 'CREDIT DISCOUNT', '2025-06-18', '2025-06-16 13:24:36', '2025-06-18 14:03:50'),
(117, 64, 2264.00, 'ROLL OVER', '2025-06-16', '2025-06-16 13:26:11', '2025-06-18 13:59:07'),
(118, 88, 33600.00, 'ROLL OVER', '2025-06-18', '2025-06-18 09:54:17', '2025-06-18 09:54:55'),
(119, 88, 6720.00, 'CREDIT DISCOUNT', '2025-06-18', '2025-06-18 09:54:39', '2025-06-18 09:56:27'),
(120, 90, 47520.00, 'ROLL OVER', '2025-06-18', '2025-06-18 10:01:51', '2025-06-18 10:01:59'),
(121, 64, 3000.00, 'TFI6EL20O0', '2025-06-18', '2025-06-18 14:00:07', '2025-06-18 14:02:15'),
(122, 89, 3000.00, 'ISIRO AGENCIES', '2025-06-19', '2025-06-20 07:02:27', '2025-06-20 07:02:27'),
(123, 87, 60000.00, 'ROLL OVER', '2025-06-18', '2025-06-20 07:06:21', '2025-06-20 07:06:21'),
(124, 84, 35100.00, 'TFK4NENN4M', '2025-06-20', '2025-06-20 11:25:28', '2025-06-20 11:25:28'),
(125, 84, 2700.00, 'CREDIT DISCOUNT', '2025-06-20', '2025-06-20 11:25:44', '2025-06-20 11:25:44'),
(126, 89, 3000.00, 'TFN8ZC5AYM', '2025-06-23', '2025-06-23 10:30:44', '2025-06-23 10:30:44'),
(127, 93, 20400.00, 'TFN61UDXXQ', '2025-06-23', '2025-06-23 18:10:20', '2025-06-23 18:10:32'),
(128, 91, 8000.00, 'TFO85K544K', '2025-06-24', '2025-06-24 06:20:01', '2025-06-24 06:20:01'),
(129, 91, 10000.00, 'ROLL OVER', '2025-06-24', '2025-06-24 06:20:30', '2025-06-24 06:20:30'),
(131, 98, 33600.00, 'TFO48SG6KQ', '2025-06-24', '2025-06-25 08:24:49', '2025-06-25 08:24:49'),
(132, 92, 2000.00, 'TFP9AVR6YV', '2025-06-25', '2025-06-25 10:42:58', '2025-06-25 10:42:58'),
(133, 92, 2400.00, 'CREDIT DISCOUNT', '2025-06-25', '2025-06-25 10:43:18', '2025-06-25 10:43:18'),
(134, 92, 10000.00, 'ROLL OVER', '2025-06-23', '2025-06-25 10:43:35', '2025-06-25 10:43:35'),
(135, 94, 1000.00, 'TFR1JUGFVF', '2025-06-27', '2025-06-27 07:42:43', '2025-06-27 07:42:43'),
(136, 94, 5000.00, 'ROLL OVER', '2025-06-27', '2025-06-27 07:43:09', '2025-06-27 07:43:09'),
(137, 94, 600.00, 'CREDIT DISCOUNT', '2025-06-27', '2025-06-27 07:44:28', '2025-06-27 07:44:28'),
(138, 106, 3000.00, 'EQAFB41A2B2B655', '2025-06-29', '2025-06-30 08:34:20', '2025-06-30 08:35:58'),
(139, 106, 3246.24, 'BAD DEBT', '2025-06-29', '2025-06-30 08:35:45', '2025-06-30 11:39:27'),
(140, 89, 3936.00, 'ROLL OVER', '2025-06-18', '2025-06-30 08:37:52', '2025-06-30 08:37:52'),
(141, 89, 4968.00, 'CREDIT DISCOUNT', '2025-06-23', '2025-06-30 08:38:25', '2025-06-30 08:38:25'),
(142, 75, 3500.00, 'TFU328PAAN', '2025-06-30', '2025-07-01 07:26:44', '2025-07-01 07:26:44'),
(143, 75, 490.00, 'CREDIT DISCOUNT', '2025-06-30', '2025-07-01 07:27:32', '2025-07-01 07:29:00'),
(144, 75, 30000.00, 'ROLL OVER', '2025-06-30', '2025-07-01 07:28:21', '2025-07-01 07:28:21'),
(145, 95, 2716.80, 'BAD DEBT', '2025-07-01', '2025-07-01 09:14:16', '2025-07-03 06:30:05'),
(147, 27, 2400.00, 'CREDIT DISCOUNT', '2025-04-15', '2025-07-01 10:18:32', '2025-07-01 10:18:39'),
(148, 103, 6000.00, 'TG48KXTDLM', '2025-07-04', '2025-07-04 12:07:10', '2025-07-04 12:07:10'),
(149, 100, 3000.00, 'TG41LFZ3DT', '2025-07-04', '2025-07-04 12:46:49', '2025-07-04 12:46:49'),
(150, 101, 1000.00, 'TG54OKPQN6', '2025-07-05', '2025-07-05 06:37:14', '2025-07-05 06:37:14'),
(151, 101, 2200.00, 'CREDIT DISCOUNT', '2025-07-05', '2025-07-05 06:38:10', '2025-07-05 06:38:10'),
(152, 101, 10000.00, 'ROLL OVER', '2025-07-05', '2025-07-05 06:37:47', '2025-07-05 06:37:47'),
(153, 102, 12000.00, 'ROLL OVER', '2025-07-04', '2025-07-05 05:39:53', '2025-07-05 05:39:53'),
(154, 78, 35000.00, 'TG73XH8WDP', '2025-07-07', '2025-07-07 08:45:17', '2025-07-07 08:45:17'),
(155, 79, 100000.00, 'TG812F3N3Z', '2025-07-08', '2025-07-07 21:08:58', '2025-07-07 21:09:14'),
(156, 79, 48000.00, 'BAD DEBT', '2025-07-08', '2025-07-07 21:13:06', '2025-07-10 17:11:23'),
(157, 79, 20000.00, 'TO BE PAID', '2025-07-08', '2025-07-07 21:13:33', '2025-07-07 21:13:33'),
(158, 85, 120000.00, 'TG989H843C', '2025-07-08', '2025-07-08 18:17:15', '2025-07-09 13:04:30'),
(159, 100, 2300.00, 'TG917B2RBN', '2025-07-09', '2025-07-09 08:41:40', '2025-07-09 08:41:40'),
(160, 111, 8000.00, 'TG929BVOBG', '2025-07-09', '2025-07-09 13:48:43', '2025-07-09 13:48:43'),
(161, 104, 6000.00, 'ROLL OVER', '2025-07-09', '2025-07-10 04:41:22', '2025-07-10 04:41:22'),
(162, 85, 60000.00, 'TO BE PAID', '2025-07-09', '2025-07-10 04:44:16', '2025-07-10 04:44:16'),
(163, 85, 240000.00, 'BAD DEBT', '2025-07-09', '2025-07-10 04:44:51', '2025-07-10 04:44:51'),
(164, 96, 97344.00, 'ROLL OVER', '2025-06-29', '2025-07-10 04:52:11', '2025-07-10 04:52:11'),
(165, 82, 39000.00, 'ROLL OVER', '2025-07-01', '2025-07-10 05:04:16', '2025-07-10 05:04:16'),
(166, 105, 24000.00, 'TGB8KTHG42', '2025-07-11', '2025-07-13 16:04:36', '2025-07-13 16:04:36'),
(167, 105, 120000.00, 'ROLL OVER', '2025-07-11', '2025-07-13 16:05:06', '2025-07-13 16:05:06'),
(168, 105, 14400.00, 'CREDIT DISCOUNT', '2025-07-11', '2025-07-13 16:05:34', '2025-07-13 16:05:34'),
(169, 111, 1600.00, 'TGD4RP45Q2', '2025-07-13', '2025-07-13 16:07:49', '2025-07-13 16:07:49'),
(170, 100, 1000.00, 'TGF93BU25D', '2025-07-15', '2025-07-15 09:11:51', '2025-07-15 09:11:51'),
(171, 100, 7500.00, 'BAD DEBT', '2025-07-15', '2025-07-15 09:12:41', '2025-07-15 09:12:41'),
(173, 108, 12000.00, 'TGF03NGDLO', '2025-07-15', '2025-07-15 10:45:11', '2025-07-15 10:45:11'),
(174, 109, 14400.00, 'ROLL OVER', '2025-07-15', '2025-07-17 08:15:07', '2025-07-17 08:15:07'),
(175, 80, 39000.00, 'CREDIT DISCOUNT', '2025-06-30', '2025-07-17 08:18:25', '2025-07-17 08:18:25'),
(176, 123, 51480.00, 'ROLL OVER', '2025-07-14', '2025-07-17 08:24:56', '2025-07-17 08:24:56'),
(177, 110, 2000.00, 'TGH4D2TMKI', '2025-07-17', '2025-07-17 09:46:37', '2025-07-17 09:46:37'),
(178, 110, 10000.00, 'ROLL OVER', '2025-07-17', '2025-07-17 09:47:16', '2025-07-17 09:47:16'),
(179, 119, 1200.00, 'TGG36Q3DV9', '2025-07-16', '2025-07-17 09:55:32', '2025-07-17 09:55:32'),
(180, 126, 3000.00, 'TGH7DBLIVD', '2025-07-17', '2025-07-17 11:21:09', '2025-07-17 11:21:09'),
(181, 112, 2000.00, 'TGH1EUV1UF', '2025-07-17', '2025-07-18 08:31:21', '2025-07-18 08:31:21'),
(182, 112, 10000.00, 'ROLL OVER', '2025-07-18', '2025-07-18 08:31:47', '2025-07-18 08:31:47'),
(184, 114, 7200.00, 'ROLL OVER', '2025-07-20', '2025-07-21 06:43:36', '2025-07-21 06:43:50'),
(185, 117, 79200.00, 'TGN76VV9JB', '2025-07-23', '2025-07-23 10:56:23', '2025-07-23 10:56:23'),
(186, 117, 7200.00, 'BAD DEBT', '2025-07-23', '2025-07-23 10:56:52', '2025-07-24 03:52:14'),
(187, 118, 2640.00, 'TGN783IWP1', '2025-07-23', '2025-07-23 18:12:25', '2025-07-23 18:12:25'),
(188, 117, 60000.00, 'TGO99UCAMB', '2025-07-24', '2025-07-24 03:49:29', '2025-07-24 03:49:29'),
(189, 117, 40800.00, 'CREDIT DISCOUNT', '2025-07-24', '2025-07-24 03:51:08', '2025-07-24 03:51:48'),
(190, 122, 17280.00, 'ROLL OVER', '2025-07-25', '2025-07-27 08:59:16', '2025-07-27 08:59:16'),
(191, 125, 13200.00, 'TGS2VOL12I', '2025-07-28', '2025-07-28 14:07:40', '2025-07-28 14:07:40'),
(192, 131, 4800.00, 'TGS3VFV9FH', '2025-07-28', '2025-07-28 14:12:00', '2025-07-28 14:12:00'),
(193, 121, 4000.00, 'CREDIT DISCOUNT', '2025-07-29', '2025-07-30 04:23:57', '2025-07-30 04:23:57'),
(194, 121, 80000.00, 'TGT0YU1U8C', '2025-07-29', '2025-07-30 04:28:10', '2025-07-30 04:28:10'),
(195, 127, 24000.00, 'TGT21SZIQ4', '2025-07-29', '2025-07-30 04:29:34', '2025-07-30 04:29:34'),
(196, 107, 33000.00, 'ROLL OVER', '2025-07-30', '2025-07-30 07:37:35', '2025-07-30 07:37:35'),
(197, 127, 2400.00, 'ROLL OVER', '2025-07-29', '2025-07-30 14:20:12', '2025-07-30 14:20:12'),
(198, 127, 2400.00, 'CREDIT DISCOUNT', '2025-07-29', '2025-07-30 14:20:49', '2025-07-30 14:20:49'),
(199, 128, 12000.00, 'ROLL OVER', '2025-07-27', '2025-07-30 15:33:27', '2025-07-30 15:33:27'),
(200, 129, 8640.00, 'TGV69IEZ1Y', '2025-07-31', '2025-07-31 06:17:42', '2025-07-31 06:17:42'),
(201, 130, 12280.00, 'TGV5A7XZF5', '2025-07-31', '2025-07-31 08:46:54', '2025-07-31 08:46:54'),
(202, 147, 15000.00, 'TH19DLONXP', '2025-08-01', '2025-08-01 07:23:58', '2025-08-01 07:23:58'),
(203, 130, 5000.00, 'ROLL OVER', '2025-08-04', '2025-08-05 08:10:11', '2025-08-05 08:10:11'),
(204, 130, 3456.00, 'CREDIT DISCOUNT', '2025-08-04', '2025-08-05 08:10:54', '2025-08-05 08:10:54'),
(205, 78, 30000.00, 'TH48U71CW6', '2025-08-04', '2025-08-05 08:16:03', '2025-08-05 08:16:03'),
(206, 142, 2000.00, 'TH684202CS', '2025-08-05', '2025-08-06 05:30:36', '2025-08-06 05:30:36'),
(207, 126, 3000.00, 'ROLL OVER', '2025-07-27', '2025-08-06 05:44:18', '2025-08-06 05:44:18'),
(208, 120, 30000.00, 'ROLL OVER', '2025-07-29', '2025-08-06 11:00:34', '2025-08-06 11:00:34'),
(209, 136, 14400.00, 'ROLL OVER', '2025-08-07', '2025-08-07 17:08:07', '2025-08-07 17:08:07'),
(210, 124, 67953.60, 'ROLL OVER', '2025-07-28', '2025-08-07 18:46:49', '2025-08-07 18:46:49'),
(211, 115, 126547.20, 'ROLL OVER', '2025-07-30', '2025-08-11 08:04:24', '2025-08-11 08:04:24'),
(212, 147, 66544.32, 'ROLL OVER', '2025-08-12', '2025-08-12 06:51:32', '2025-08-12 06:51:32'),
(213, 116, 50700.00, 'ROLL OVER', '2025-08-02', '2025-08-12 06:54:31', '2025-08-12 06:54:31'),
(214, 140, 3600.00, 'THC04B0UYQ', '2025-08-12', '2025-08-12 18:20:30', '2025-08-12 18:20:30'),
(215, 142, 1600.00, 'ROLL OVER', '2025-08-12', '2025-08-14 10:08:00', '2025-08-14 10:08:00'),
(216, 141, 1200.00, 'THF6EWQ6OE', '2025-08-14', '2025-08-15 05:32:14', '2025-08-15 05:32:14'),
(217, 141, 6000.00, 'ROLL OVER', '2025-08-14', '2025-08-15 05:33:31', '2025-08-15 05:33:31'),
(218, 135, 2800.00, 'THF0GZ1XFK', '2025-08-15', '2025-08-15 12:23:49', '2025-08-15 12:23:49'),
(219, 135, 2096.00, 'BAD DEBT', '2025-08-15', '2025-08-15 12:24:14', '2025-08-15 12:24:14'),
(220, 158, 1920.00, 'ROLL OVER', '2025-08-16', '2025-08-20 06:37:25', '2025-08-20 06:37:25'),
(221, 146, 19680.00, 'ROLL OVER', '2025-08-17', '2025-08-20 07:25:46', '2025-08-20 07:25:46'),
(222, 137, 5000.00, 'THK95CUUJD', '2025-08-20', '2025-08-20 07:31:15', '2025-08-20 07:31:15'),
(223, 149, 12500.00, 'THK367HO3V', '2025-08-19', '2025-08-20 10:21:32', '2025-08-20 10:21:32'),
(224, 149, 700.00, 'CREDIT DISCOUNT', '2025-08-19', '2025-08-20 10:21:33', '2025-08-20 10:21:33'),
(225, 148, 18000.00, 'ROLL OVER', '2025-08-19', '2025-08-21 09:20:35', '2025-08-21 09:20:35'),
(226, 162, 2304.00, 'CREDIT DISCOUNT', '2025-08-23', '2025-08-23 06:29:55', '2025-08-23 06:29:55'),
(227, 161, 60000.00, 'X82C9AB04FC7D', '2025-08-27', '2025-08-27 10:35:57', '2025-08-27 10:35:57'),
(228, 144, 360000.00, 'X82C9AB04FC7D', '2025-08-27', '2025-08-27 10:37:14', '2025-08-27 10:37:14'),
(229, 159, 7200.00, 'ROLL OVER', '2025-08-25', '2025-08-27 10:40:21', '2025-08-27 10:40:21'),
(230, 137, 80000.00, 'THS5CJ90C3', '2025-08-28', '2025-08-28 12:41:01', '2025-08-28 12:41:01'),
(231, 137, 41000.00, 'CREDIT DISCOUNT', '2025-08-28', '2025-08-28 12:41:41', '2025-08-28 12:41:41'),
(232, 139, 33000.00, 'ROLL OVER', '2025-08-31', '2025-08-28 15:08:21', '2025-08-28 15:08:21'),
(233, 138, 9000.00, 'TI12WYRJKU', '2025-09-01', '2025-09-01 18:22:22', '2025-09-01 18:22:22'),
(234, 134, 33000.00, 'ROLL OVER', '2025-08-30', '2025-09-01 18:26:23', '2025-09-01 18:26:23'),
(235, 143, 42000.00, 'ROLL OVER', '2025-08-13', '2025-09-01 18:29:30', '2025-09-01 18:29:30'),
(236, 169, 50600.00, 'ROLL OVER', '2025-08-28', '2025-09-01 18:31:21', '2025-09-01 18:31:21'),
(237, 138, 45000.00, 'ROLL OVER', '2025-09-01', '2025-09-01 18:31:22', '2025-09-01 18:31:22'),
(238, 113, 62500.00, 'BAD DEBT', '2025-09-03', '2025-09-03 11:19:19', '2025-09-03 11:19:19'),
(239, 164, 18000.00, 'ROLL OVER', '2025-08-30', '2025-09-04 07:02:54', '2025-09-04 07:02:54'),
(240, 166, 1440.00, 'TI48CH4LX6', '2025-09-04', '2025-09-04 07:37:58', '2025-09-04 07:37:58'),
(241, 166, 7200.00, 'ROLL OVER', '2025-09-04', '2025-09-04 07:38:16', '2025-09-04 07:38:16'),
(242, 145, 3500.00, 'TI71TO854L', '2025-09-07', '2025-09-08 10:26:04', '2025-09-08 10:26:04'),
(243, 164, 3000.00, 'TI69MHL50T', '2025-09-06', '2025-09-08 10:30:44', '2025-09-08 10:30:44'),
(244, 145, 100.00, 'TI85ZU3WYV', '2025-09-08', '2025-09-08 15:20:48', '2025-09-08 15:20:48'),
(245, 145, 360.00, 'CREDIT DISCOUNT', '2025-09-08', '2025-09-08 15:21:10', '2025-09-08 15:21:10'),
(246, 167, 60000.00, 'TI86YXY2J4', '2025-09-08', '2025-09-08 15:21:55', '2025-09-08 15:21:55'),
(247, 156, 12000.00, 'ROLL OVER', '2025-09-01', '2025-09-08 15:30:31', '2025-09-08 15:30:31'),
(248, 163, 23616.00, 'ROLL OVER', '2025-08-29', '2025-09-08 15:36:07', '2025-09-08 15:36:07'),
(249, 152, 65910.00, 'ROLL OVER', '2025-09-03', '2025-09-08 16:38:16', '2025-09-08 16:38:16'),
(250, 176, 28339.20, 'ROLL OVER', '2025-09-09', '2025-09-10 02:49:32', '2025-09-10 02:49:32'),
(251, 168, 10000.00, 'TI8624GQ4O', '2025-09-08', '2025-09-10 02:53:00', '2025-09-10 02:53:00'),
(252, 174, 18000.00, 'TIA2942J12', '2025-09-10', '2025-09-10 07:22:24', '2025-09-10 07:22:24'),
(253, 178, 16000.00, 'TIA0A2UJ2M', '2025-09-10', '2025-09-10 11:14:25', '2025-09-10 11:14:25'),
(254, 178, 16000.00, 'TID1PND5A5', '2025-09-13', '2025-09-10 11:14:25', '2025-09-10 11:14:25'),
(255, 172, 21600.00, 'ROLL OVER', '2025-09-10', '2025-09-11 17:51:22', '2025-09-11 17:51:22'),
(256, 165, 18000.00, 'ROLL OVER', '2025-09-01', '2025-09-12 13:47:09', '2025-09-12 13:47:09'),
(257, 175, 2000.00, 'TIC3LSPPL3', '2025-09-12', '2025-09-12 14:23:43', '2025-09-12 14:23:43'),
(258, 175, 2400.00, 'TIC7N0C51L', '2025-09-12', '2025-09-13 09:09:51', '2025-09-13 09:09:51'),
(259, 175, 10000.00, 'ROLL OVER', '2025-09-12', '2025-09-13 09:09:53', '2025-09-13 09:09:53'),
(260, 178, 7000.00, 'TID1RQ4GWL', '2025-09-13', '2025-09-13 09:52:20', '2025-09-13 09:52:20'),
(261, 183, 21600.00, 'ROLL OVER', '2025-09-12', '2025-09-14 11:07:03', '2025-09-14 11:07:03'),
(262, 78, 91000.00, 'CREDIT DISCOUNT', '2025-08-30', '2025-09-14 11:29:31', '2025-09-14 11:29:31'),
(263, 78, 40000.00, 'TIF6ZK4P66', '2025-09-15', '2025-09-15 08:21:09', '2025-09-15 08:21:09'),
(264, 168, 3000.00, 'MPESAA', '2025-09-01', '2025-09-15 17:35:14', '2025-09-15 17:35:24'),
(265, 173, 8640.00, 'ROLL OVER', '2025-09-15', '2025-09-16 05:53:59', '2025-09-16 05:53:59'),
(266, 179, 34008.00, 'ROLL OVER', '2025-09-20', '2025-09-23 03:52:29', '2025-09-23 03:52:47'),
(267, 191, 2500.00, 'TIMMK5DPW1', '2025-09-22', '2025-09-23 03:55:00', '2025-09-23 03:55:00'),
(268, 170, 10000.00, 'TIN9X5F2XP', '2025-09-23', '2025-09-23 15:28:40', '2025-09-23 15:28:40'),
(269, 191, 2500.00, 'TIOMK5J02A', '2025-09-24', '2025-09-25 04:12:30', '2025-09-25 04:12:30'),
(270, 182, 10000.00, 'TIOKB5JWNH', '2025-09-24', '2025-09-25 04:13:31', '2025-09-25 04:13:31'),
(271, 182, 5000.00, 'TIOKB5JWWH', '2025-09-24', '2025-09-25 04:13:52', '2025-09-25 04:13:52'),
(272, 182, 18696.00, 'CREDIT DISCOUNT', '2025-09-24', '2025-09-25 04:15:59', '2025-09-25 04:15:59'),
(273, 181, 40000.00, 'TIP9Q5KLW6', '2025-09-25', '2025-09-25 10:29:02', '2025-09-25 10:29:02'),
(274, 185, 25920.00, 'TIPPX5JNBZ', '2025-09-25', '2025-09-25 10:29:35', '2025-09-25 10:29:35'),
(275, 185, 2592.00, 'CREDIT DISCOUNT', '2025-09-25', '2025-09-25 10:30:09', '2025-09-25 10:30:09'),
(276, 181, 8000.00, 'TIP9Q5L7NH', '2025-09-25', '2025-09-25 14:52:00', '2025-09-25 14:52:00'),
(277, 187, 8000.00, 'TIP8X5JO2D', '2025-09-25', '2025-09-25 14:53:11', '2025-09-25 14:53:11'),
(278, 187, 40000.00, 'ROLL OVER', '2025-09-25', '2025-09-25 14:53:31', '2025-09-25 14:53:31'),
(279, 171, 10000.00, 'TIUKQ65WUD', '2025-09-30', '2025-10-01 06:59:03', '2025-10-01 06:59:03'),
(280, 190, 12000.00, 'ROLL OVER', '2025-10-01', '2025-10-01 07:10:35', '2025-10-01 07:10:35'),
(281, 168, 17000.00, 'ROLL OVER', '2025-09-30', '2025-10-01 07:15:33', '2025-10-01 07:21:14'),
(282, 168, 3000.00, 'TIL9Y4ZGNH', '2025-09-21', '2025-10-01 07:20:58', '2025-10-01 07:20:58'),
(283, 170, 23000.00, 'ROLL OVER', '2025-10-01', '2025-10-01 07:23:06', '2025-10-01 07:23:06'),
(284, 151, 20000.00, 'TJ1HD6AK4Y', '2025-10-01', '2025-10-01 16:33:26', '2025-10-01 16:33:26'),
(285, 184, 12000.00, 'ROLL OVER', '2025-09-23', '2025-10-03 06:52:15', '2025-10-03 06:52:15'),
(286, 199, 14400.00, 'ROLL OVER', '2025-10-03', '2025-10-03 06:54:09', '2025-10-03 06:54:29'),
(287, 192, 48000.00, 'TJ48X6GF99', '2025-10-04', '2025-10-05 00:38:40', '2025-10-05 00:38:40'),
(288, 201, 500.00, 'TJ66B6MZ1S', '2025-10-06', '2025-10-06 07:45:55', '2025-10-06 07:45:55'),
(289, 201, 1000.00, 'TJ66B6LWA6', '2025-10-05', '2025-10-06 07:46:13', '2025-10-06 07:46:13'),
(290, 171, 29368.00, 'ROLL OVER', '2025-10-02', '2025-10-06 08:04:19', '2025-10-06 08:04:19'),
(291, 171, 25000.00, 'MPESA', '2025-10-02', '2025-10-06 08:05:27', '2025-10-06 08:05:27'),
(292, 194, 12000.00, 'TJ6GC6NI17', '2025-10-06', '2025-10-06 08:12:10', '2025-10-06 08:12:10'),
(293, 201, 950.00, 'TJ66B6N4EJ', '2025-10-06', '2025-10-06 09:29:20', '2025-10-06 09:29:20'),
(294, 180, 25000.00, 'CHECK OFF', '2025-10-06', '2025-10-09 06:48:53', '2025-10-09 06:48:53'),
(295, 202, 12000.00, 'TJ9PX6W157', '2025-10-09', '2025-10-09 14:59:08', '2025-10-09 14:59:08'),
(296, 201, 550.00, 'TJ96B6YGS3', '2025-10-10', '2025-10-11 13:16:13', '2025-10-11 13:16:13'),
(297, 198, 55000.00, 'TJC8O75YS4', '2025-10-12', '2025-10-13 06:34:28', '2025-10-13 06:34:28'),
(298, 188, 1200.00, 'ROLL OVER', '2025-10-08', '2025-10-13 11:15:17', '2025-10-13 11:15:17'),
(299, 201, 1500.00, 'TJD6B79QKJ', '2025-10-13', '2025-10-13 11:53:03', '2025-10-13 11:53:03'),
(300, 198, 5000.00, 'TJD8O78J5N', '2025-10-13', '2025-10-13 12:39:28', '2025-10-13 12:39:28'),
(301, 178, 46683.00, 'ROLL OVER', '2025-10-04', '2025-10-14 06:35:35', '2025-10-14 06:35:35'),
(302, 191, 35809.60, 'ROLL OVER', '2025-10-01', '2025-10-14 07:41:22', '2025-10-14 07:41:22'),
(303, 209, 36972.00, 'ROLL OVER', '2025-10-11', '2025-10-14 07:44:21', '2025-10-14 07:44:21'),
(304, 210, 5000.00, 'TJEMK7DIXZ', '2025-10-14', '2025-10-14 07:46:58', '2025-10-14 07:46:58'),
(305, 200, 5000.00, 'TJEJ67D6FW', '2025-10-14', '2025-10-15 03:52:28', '2025-10-15 03:52:28'),
(306, 204, 18000.00, 'TJFGC7FE42', '2025-10-15', '2025-10-15 04:06:09', '2025-10-15 04:06:09'),
(307, 201, 1000.00, 'TJJ6B7RK3D', '2025-10-19', '2025-10-19 07:33:41', '2025-10-19 07:33:41'),
(308, 201, 1000.00, 'TJJ6B7RKA8', '2025-10-19', '2025-10-19 07:33:53', '2025-10-19 07:33:53'),
(309, 201, 1300.00, 'CREDIT DISCOUNT', '2025-10-19', '2025-10-19 07:34:51', '2025-10-19 07:34:51'),
(310, 78, 181000.00, 'TJLPX7X5JA', '2025-10-21', '2025-10-21 19:59:01', '2025-10-21 19:59:01'),
(311, 205, 72000.00, 'ROLL OVER', '2025-10-20', '2025-10-22 09:33:43', '2025-10-22 09:35:05'),
(312, 207, 36000.00, 'TJO9Q87A53', '2025-10-24', '2025-10-24 09:01:05', '2025-10-24 09:01:05'),
(313, 206, 1800.00, 'TJPFN8C647', '2025-10-25', '2025-10-25 08:57:48', '2025-10-25 08:57:48'),
(314, 211, 24000.00, 'TJQGC8ELR5', '2025-10-26', '2025-10-26 17:57:02', '2025-10-26 17:57:02'),
(315, 213, 10000.00, 'ROLL OVER', '2025-10-27', '2025-10-27 04:57:17', '2025-10-27 04:57:17'),
(316, 213, 2000.00, 'TJRKB8GQ65', '2025-10-27', '2025-10-27 04:57:35', '2025-10-27 05:03:18'),
(317, 197, 10000.00, 'TJR9X8HHM6', '2025-10-27', '2025-10-27 09:47:04', '2025-10-27 09:47:04'),
(318, 197, 15300.00, 'ROLL OVER', '2025-10-27', '2025-10-27 09:48:40', '2025-10-27 09:48:40'),
(319, 212, 56000.00, 'TJSGZ8KIC5', '2025-10-28', '2025-10-28 09:13:30', '2025-10-28 09:13:30'),
(320, 212, 100000.00, 'ROLL OVER', '2025-10-28', '2025-10-28 09:13:54', '2025-10-28 09:13:54'),
(321, 195, 41760.00, 'TJV4A8Z943', '2025-10-31', '2025-10-31 07:55:12', '2025-10-31 07:55:12'),
(322, 220, 8240.00, 'TJV4A8Z943', '2025-10-31', '2025-10-31 07:56:15', '2025-10-31 07:56:15'),
(323, 220, 130000.00, 'TJV4A8ZAQZ', '2025-10-31', '2025-10-31 07:56:43', '2025-10-31 07:56:43'),
(324, 196, 18700.00, 'ROLL OVER', '2025-11-01', '2025-11-03 09:24:16', '2025-11-03 09:24:16'),
(325, 203, 10000.00, 'TK3KQ98LGK', '2025-11-03', '2025-11-03 09:42:26', '2025-11-03 09:55:29'),
(326, 203, 10000.00, 'TK3KQ99299', '2025-11-03', '2025-11-03 09:55:45', '2025-11-03 11:44:37'),
(327, 203, 10000.00, 'TK3KQ9963A', '2025-11-03', '2025-11-03 14:31:27', '2025-11-03 14:31:27'),
(328, 203, 35241.60, 'ROLL OVER', '2025-11-03', '2025-11-03 14:32:18', '2025-11-03 14:32:18'),
(329, 219, 740.00, 'TK36B94B2Q', '2025-11-03', '2025-11-03 14:34:31', '2025-11-03 14:34:31'),
(330, 208, 30000.00, 'TK46K94DNK', '2025-11-04', '2025-11-04 03:31:17', '2025-11-04 03:31:17'),
(331, 219, 700.00, 'TK36B95BUL', '2025-11-03', '2025-11-04 03:31:45', '2025-11-04 03:31:45'),
(332, 214, 6000.00, 'TK433995RT', '2025-10-31', '2025-11-04 03:46:11', '2025-11-05 09:22:23'),
(333, 214, 14400.00, 'TK433995KP', '2025-10-31', '2025-11-04 07:36:40', '2025-11-04 07:36:40'),
(334, 214, 66000.00, 'ROLL OVER', '2025-10-31', '2025-11-05 09:23:13', '2025-11-05 09:23:13'),
(335, 215, 3200.00, 'TK5H59EO5Q', '2025-11-05', '2025-11-07 09:40:13', '2025-11-07 09:40:13'),
(336, 215, 4800.00, 'CREDIT DISCOUNT', '2025-11-05', '2025-11-07 09:40:52', '2025-11-07 09:40:52'),
(337, 215, 10000.00, 'ROLL OVER', '2025-11-05', '2025-11-07 09:41:01', '2025-11-07 09:41:01'),
(338, 222, 5000.00, 'TK7GC9H6FQ', '2025-11-07', '2025-11-07 09:45:10', '2025-11-07 09:45:10'),
(339, 222, 25000.00, 'ROLL OVER', '2025-11-07', '2025-11-07 09:45:53', '2025-11-07 09:45:53'),
(340, 219, 3000.00, 'ROLL OVER', '2025-11-07', '2025-11-07 09:58:31', '2025-11-07 09:58:31'),
(341, 224, 12000.00, 'ROLL OVER', '2025-11-06', '2025-11-08 08:09:56', '2025-11-08 08:09:56'),
(342, 186, 160000.00, '000100572025111219503777350nxl', '2025-11-12', '2025-11-12 15:57:35', '2025-11-12 15:57:35'),
(343, 186, 100000.00, '0001005720251113144749610ceq6b', '2025-11-13', '2025-11-13 10:50:05', '2025-11-13 10:50:05'),
(344, 234, 79200.00, 'ROLL OVER', '2025-11-09', '2025-11-13 17:52:10', '2025-11-13 17:52:10'),
(345, 216, 36000.00, 'ROLL OVER', '2025-11-03', '2025-11-17 09:29:27', '2025-11-17 09:29:27'),
(346, 236, 43200.00, 'ROLL OVER', '2025-11-03', '2025-11-17 09:32:35', '2025-11-17 09:32:35'),
(347, 237, 8000.00, 'TKEFGA7ODI', '2025-11-14', '2025-11-17 09:35:01', '2025-11-17 09:35:01'),
(348, 228, 12000.00, 'ROLL OVER', '2025-11-13', '2025-11-17 09:40:58', '2025-11-17 09:40:58'),
(349, 229, 5000.00, 'TKHGCAFNUG', '2025-11-17', '2025-11-17 18:48:14', '2025-11-17 18:48:14'),
(350, 229, 25000.00, 'ROLL OVER', '2025-11-17', '2025-11-17 18:48:33', '2025-11-17 18:49:09'),
(351, 230, 900.00, 'TKH6BAE3SR', '2025-11-17', '2025-11-17 18:52:55', '2025-11-17 18:52:55'),
(352, 231, 14400.00, 'ROLL OVER', '2025-11-16', '2025-11-18 05:02:28', '2025-11-18 05:02:28'),
(353, 241, 2000.00, 'SALARY', '2025-11-18', '2025-11-19 05:11:40', '2025-11-19 05:11:40'),
(354, 227, 15000.00, 'TKIPXAF63W', '2025-11-18', '2025-11-19 05:17:04', '2025-11-19 05:17:41'),
(355, 227, 600.00, 'CREDIT DISCOUNT', '2025-11-18', '2025-11-19 05:18:41', '2025-11-19 05:18:41'),
(356, 230, 2700.00, 'ROLL OVER', '2025-11-17', '2025-11-19 09:24:28', '2025-11-19 09:24:28'),
(357, 151, 10000.00, 'TKKHDAP5VD', '2025-11-20', '2025-11-21 04:47:38', '2025-11-21 04:47:38'),
(358, 243, 560.00, 'TKN6BAVC9H', '2025-11-23', '2025-11-23 09:12:54', '2025-11-23 09:12:54'),
(359, 242, 36000.00, 'TKOT7422V0', '2025-11-24', '2025-11-25 07:02:02', '2025-11-25 07:02:02'),
(360, 232, 28800.00, 'TKOALAZQ6F', '2025-11-24', '2025-11-25 07:02:36', '2025-11-25 07:02:46'),
(361, 232, 2400.00, 'CREDIT DISCOUNT', '2025-11-24', '2025-11-25 07:03:06', '2025-11-25 07:03:06'),
(362, 235, 95040.00, 'ROLL OVER', '2025-11-20', '2025-11-25 10:27:52', '2025-11-25 10:27:52'),
(363, 238, 14400.00, 'ROLL OVER', '2025-11-23', '2025-11-26 19:33:06', '2025-11-26 19:33:06'),
(364, 248, 2500.00, 'TKQH5BA8E1', '2025-11-26', '2025-11-26 19:35:00', '2025-11-26 19:35:00'),
(365, 221, 10000.00, 'TKR9XB9O6P', '2025-11-27', '2025-11-27 06:35:02', '2025-11-27 06:35:02'),
(366, 251, 15000.00, 'TKRFGBBPGA', '2025-11-27', '2025-11-27 18:28:36', '2025-11-27 18:28:36'),
(367, 245, 24000.00, 'TKS8OBCK99', '2025-11-28', '2025-11-28 07:47:35', '2025-11-28 09:12:29'),
(368, 240, 19008.00, 'TKSKBBB9EO', '2025-11-28', '2025-11-28 09:11:58', '2025-11-28 09:11:58'),
(369, 239, 5000.00, 'TKSGCBDF47', '2025-11-28', '2025-11-28 18:24:45', '2025-11-28 18:24:45'),
(370, 239, 25000.00, 'ROLL OVER', '2025-11-28', '2025-11-28 18:25:12', '2025-11-28 18:25:12'),
(371, 221, 126830.00, 'ROLL OVER', '2025-11-27', '2025-11-28 18:28:24', '2025-11-28 18:28:24'),
(372, 237, 43840.00, 'ROLL OVER', '2025-11-23', '2025-11-29 14:18:03', '2025-11-29 14:18:03'),
(373, 246, 4000.00, 'TL1PXBKFLJ', '2025-12-01', '2025-12-01 19:11:54', '2025-12-01 19:11:54'),
(374, 246, 20000.00, 'ROLL OVER', '2025-12-01', '2025-12-01 19:12:08', '2025-12-01 19:12:08'),
(375, 180, 25000.00, 'TL1GCBMIDI', '2025-12-01', '2025-12-01 19:14:12', '2025-12-01 19:14:12'),
(376, 247, 114048.00, 'ROLL OVER', '2025-11-30', '2025-12-03 04:31:52', '2025-12-03 04:31:52'),
(377, 226, 6000.00, 'TL28UBMXFK', '2025-12-02', '2025-12-03 04:41:26', '2025-12-03 04:41:26'),
(378, 243, 2680.00, 'ROLL OVER', '2025-11-27', '2025-12-03 04:46:46', '2025-12-03 04:46:46'),
(379, 248, 6000.00, 'TL3H500V3Y', '2025-12-03', '2025-12-04 04:11:36', '2025-12-04 04:24:02'),
(380, 180, 25000.00, 'TL5GC0344S', '2025-12-06', '2025-12-06 08:42:59', '2025-12-06 08:42:59'),
(381, 233, 42290.00, 'ROLL OVER', '2025-12-02', '2025-12-06 08:51:49', '2025-12-06 08:53:27'),
(382, 225, 130000.00, 'ROLL OVER', '2025-11-29', '2025-12-08 05:28:17', '2025-12-08 05:28:17'),
(383, 251, 37608.00, 'ROLL OVER', '2025-12-03', '2025-12-09 06:18:14', '2025-12-09 06:18:14'),
(384, 249, 5000.00, 'TL8GC0FLYZ', '2025-12-09', '2025-12-09 06:21:52', '2025-12-09 06:21:52'),
(385, 249, 25000.00, 'ROLL OVER', '2025-12-09', '2025-12-09 06:22:13', '2025-12-09 06:22:13'),
(386, 248, 8780.00, 'ROLL OVER', '2025-12-03', '2025-12-09 06:24:58', '2025-12-09 06:25:40'),
(387, 186, 65000.00, '00010057202512120842243339uvd3', '2025-12-12', '2025-12-12 06:37:37', '2025-12-12 06:37:37'),
(388, 255, 3000.00, 'TLD6B0VAFD', '2025-12-13', '2025-12-15 06:11:10', '2025-12-15 06:11:10'),
(389, 255, 2145.60, 'CREDIT DISCOUNT', '2025-12-13', '2025-12-15 06:11:39', '2025-12-15 06:11:39'),
(390, 260, 30000.00, 'TLMGC1PXT7', '2025-12-22', '2025-12-23 07:29:57', '2025-12-23 07:29:57'),
(391, 260, 6000.00, 'ROLL OVER', '2025-12-22', '2025-12-23 07:30:09', '2025-12-23 07:30:09'),
(392, 252, 36000.00, 'TLMPX1N079', '2025-12-22', '2025-12-23 07:31:48', '2025-12-23 07:31:48'),
(393, 252, 12000.00, 'CREDIT DISCOUNT', '2025-12-22', '2025-12-23 07:32:55', '2025-12-23 07:33:10'),
(394, 97, 20000.00, 'TLJSG5L062', '2025-12-19', '2025-12-23 10:16:48', '2025-12-23 10:16:48'),
(395, 254, 20000.00, 'TLQBU25Y2F', '2025-12-26', '2025-12-26 08:51:51', '2025-12-26 08:51:51'),
(396, 254, 100000.00, 'ROLL OVER', '2025-12-26', '2025-12-26 08:52:13', '2025-12-26 08:52:13'),
(397, 254, 12000.00, 'CREDIT DISCOUNT', '2025-12-26', '2025-12-26 08:52:27', '2025-12-26 08:52:27'),
(398, 261, 10536.00, 'ROLL OVER', '2025-12-13', '2025-12-26 08:57:12', '2025-12-26 08:57:12'),
(399, 263, 12643.20, 'ROLL OVER', '2025-12-23', '2025-12-26 08:59:13', '2025-12-26 08:59:13'),
(400, 259, 45129.60, 'ROLL OVER', '2025-12-13', '2025-12-26 09:01:17', '2025-12-26 09:01:17'),
(401, 265, 54155.52, 'ROLL OVER', '2025-12-22', '2025-12-26 09:05:04', '2025-12-26 09:08:56'),
(402, 267, 6000.00, 'TLO9X1XQ9A', '2025-12-24', '2025-12-26 18:09:31', '2025-12-26 18:09:31'),
(403, 250, 20000.00, 'TLO9X1XV6I', '2025-12-24', '2025-12-27 08:20:49', '2025-12-27 08:20:49'),
(404, 244, 720000.00, 'ROLL OVER', '2025-12-22', '2025-12-28 09:46:29', '2025-12-28 09:46:29'),
(405, 270, 79000.00, 'TLVT75E8QI', '2025-12-31', '2025-12-31 08:48:37', '2025-12-31 08:48:37'),
(406, 270, 11000.00, 'UA39Q2SDH7', '2026-01-04', '2026-01-04 09:31:27', '2026-01-04 09:31:27'),
(407, 270, 6000.00, 'CREDIT DISCOUNT', '2026-01-04', '2026-01-04 09:31:39', '2026-01-04 09:31:39'),
(408, 272, 24000.00, 'UA5PX2UUFZ', '2026-01-05', '2026-01-05 02:00:37', '2026-01-05 02:00:37'),
(409, 268, 6600.00, 'UA61E2XHDT', '2026-01-06', '2026-01-06 09:23:35', '2026-01-06 09:23:35'),
(410, 264, 15171.84, 'ROLL OVER', '2026-01-01', '2026-01-08 14:10:27', '2026-01-08 14:11:43'),
(411, 273, 14206.21, 'BAD DEBT', '2026-01-08', '2026-01-08 14:15:06', '2026-01-12 06:40:15'),
(412, 273, 4000.00, 'UA8H53AZO4', '2026-01-08', '2026-01-08 14:15:42', '2026-01-08 14:15:42'),
(413, 241, 10000.00, 'SALARY DEC', '2025-12-18', '2026-01-09 07:06:03', '2026-01-09 07:06:20'),
(414, 258, 169000.00, 'ROLL OVER', '2025-12-29', '2026-01-12 13:25:25', '2026-01-12 13:25:25'),
(415, 276, 55000.00, 'UAF9Q3UZHE', '2026-01-15', '2026-01-15 16:39:02', '2026-01-15 16:39:02'),
(416, 276, 5000.00, 'CREDIT DISCOUNT', '2026-01-15', '2026-01-15 16:39:17', '2026-01-15 16:39:17'),
(417, 257, 50748.00, 'ROLL OVER', '2026-01-03', '2026-01-15 16:40:05', '2026-01-15 16:40:05'),
(418, 277, 42000.00, '00070057202601191709408dcb2a08', '2026-01-19', '2026-01-20 08:02:39', '2026-01-20 08:02:39'),
(419, 274, 48000.00, 'UAJ8O4BE8Z', '2026-01-19', '2026-01-20 08:03:10', '2026-01-20 08:03:10'),
(420, 278, 83000.00, '165097985236', '2026-01-21', '2026-01-21 06:07:00', '2026-01-21 06:07:00'),
(421, 281, 7000.00, 'UAL5X4JNT1', '2026-01-21', '2026-01-22 07:31:41', '2026-01-22 07:31:41'),
(422, 281, 200.00, 'CREDIT DISCOUNT', '2026-01-21', '2026-01-22 07:32:02', '2026-01-22 07:32:02'),
(423, 269, 500000.00, '0007005720260127095017de75acd2', '2026-01-27', '2026-01-27 06:14:17', '2026-01-27 06:14:17'),
(424, 278, 5000.00, 'UAQ9X4X5YI', '2026-01-26', '2026-01-27 06:17:16', '2026-01-27 06:17:16'),
(425, 278, 8000.00, 'MBNHE9DTRFHI7TO3', '2026-01-22', '2026-01-27 06:28:04', '2026-01-27 06:28:04'),
(426, 262, 20000.00, 'UAS6O4Z741', '2026-01-28', '2026-01-28 11:59:08', '2026-01-28 11:59:08'),
(427, 285, 33000.00, 'UARPX4WGMF', '2026-01-27', '2026-01-29 08:26:42', '2026-01-29 08:26:42'),
(428, 262, 100000.00, 'ROLL OVER', '2026-01-24', '2026-01-29 10:07:56', '2026-02-26 17:56:28'),
(429, 278, 30000.00, 'UAQ9X4X754', '2026-01-26', '2026-01-30 19:24:33', '2026-01-30 19:24:33'),
(430, 269, 364000.00, 'ROLL OVER', '2026-01-22', '2026-02-01 19:37:16', '2026-02-24 16:10:46'),
(431, 151, 15000.00, '197739595312', '2026-02-03', '2026-02-03 14:34:38', '2026-02-03 14:36:35'),
(432, 282, 300000.00, 'ROLL OVER', '2026-01-31', '2026-02-03 14:41:38', '2026-02-03 14:41:38'),
(433, 290, 106000.00, 'UB6D85XEMG', '2026-02-06', '2026-02-09 13:35:53', '2026-02-09 13:35:53'),
(434, 290, 6000.00, 'BROKER FEES', '2026-02-06', '2026-02-09 13:36:43', '2026-02-09 13:36:43'),
(435, 286, 10560.00, 'UB9PX665CY', '2026-02-09', '2026-02-09 13:38:21', '2026-02-09 13:38:21'),
(436, 275, 54440.00, 'UB9PX665CY', '2026-02-09', '2026-02-09 13:38:47', '2026-02-09 13:38:47'),
(437, 275, 65560.00, 'ROLL OVER', '2026-02-09', '2026-02-09 13:39:03', '2026-02-09 13:39:03'),
(438, 278, 3000.00, 'UB59X5V43U', '2026-02-04', '2026-02-09 13:44:37', '2026-02-09 13:44:37'),
(439, 278, 1000.00, 'UB49X5UFY6', '2026-02-04', '2026-02-09 13:44:53', '2026-02-09 13:44:53'),
(440, 293, 25000.00, 'SALARY', '2026-02-05', '2026-02-09 13:48:00', '2026-02-09 13:48:00'),
(441, 283, 17280.00, 'UBB6O6AE13', '2026-02-10', '2026-02-11 10:01:29', '2026-02-11 10:01:52'),
(442, 256, 25000.00, 'UBDTZ6TVNS', '2026-02-10', '2026-02-17 13:42:28', '2026-02-17 13:42:28'),
(443, 289, 360000.00, 'ROLL OVER', '2026-02-10', '2026-02-17 13:46:53', '2026-02-17 13:46:53'),
(444, 279, 219700.00, 'ROLL OVER', '2026-01-31', '2026-02-18 05:26:27', '2026-02-18 05:26:27'),
(445, 280, 60897.60, 'ROLL OVER', '2026-02-03', '2026-02-18 05:34:39', '2026-02-18 05:34:39'),
(446, 292, 80000.00, 'UBJ8O76R0T', '2026-02-19', '2026-02-19 08:59:20', '2026-02-19 08:59:20'),
(447, 315, 60000.00, 'ROLL OVER', '2026-02-19', '2026-02-19 09:40:36', '2026-02-19 09:40:36'),
(448, 226, 18310.00, 'CREDIT DISCOUNT', '2026-02-02', '2026-02-19 10:10:11', '2026-02-19 10:10:11'),
(449, 292, 40000.00, 'ROLL OVER', '2026-02-19', '2026-02-21 21:12:59', '2026-02-21 21:12:59'),
(450, 319, 20000.00, 'UBL8O7D9EU', '2026-02-21', '2026-02-21 21:15:22', '2026-02-21 21:15:22'),
(451, 288, 100000.00, '0001005720260224162117447c4ymz', '2026-02-24', '2026-02-24 14:47:08', '2026-02-24 14:47:08'),
(452, 288, 200000.00, '0001005720260224162356457ihiu1', '2026-02-24', '2026-02-24 14:47:35', '2026-02-24 14:47:35'),
(453, 307, 168000.00, 'ROLL OVER', '2026-02-21', '2026-02-24 15:56:11', '2026-02-24 15:56:11'),
(454, 307, 12000.00, 'BROKER FEES', '2026-02-21', '2026-02-24 15:56:27', '2026-02-24 15:56:27'),
(455, 309, 13248.00, 'ROLL OVER', '2026-02-21', '2026-02-24 15:57:31', '2026-02-24 16:00:49'),
(456, 309, 1152.00, 'BROKER FEES', '2026-02-21', '2026-02-24 15:58:28', '2026-02-24 15:58:28'),
(457, 321, 8212.80, 'BROKER FEES', '2026-02-27', '2026-02-24 16:06:40', '2026-03-03 06:41:17'),
(458, 269, 432000.00, 'CREDIT DISCOUNT', '2026-01-22', '2026-02-24 16:13:14', '2026-02-24 16:13:28'),
(459, 319, 30000.00, 'UBQS88E6WC', '2026-02-26', '2026-02-26 06:41:37', '2026-02-26 06:41:37'),
(460, 314, 36000.00, 'UBQBM7YL7I', '2026-02-26', '2026-02-26 17:20:28', '2026-02-26 17:20:28'),
(461, 287, 20000.00, 'UBQ6O7P73Y', '2026-02-26', '2026-02-26 17:55:06', '2026-02-26 17:55:06'),
(462, 287, 100000.00, 'ROLL OVER', '2026-02-26', '2026-02-26 17:58:54', '2026-02-26 17:58:54'),
(463, 313, 6000.00, 'UBROI82WIW', '2026-02-27', '2026-02-27 17:05:12', '2026-02-27 17:05:12'),
(464, 321, 41160.00, 'UBRD87VOUZ', '2026-02-27', '2026-02-27 19:24:19', '2026-02-27 19:24:19'),
(465, 316, 192000.00, 'ROLL OVER', '2026-02-28', '2026-02-27 19:31:32', '2026-02-27 19:31:32'),
(466, 317, 120000.00, 'UBS9Q7YQJQ', '2026-02-28', '2026-02-28 10:22:28', '2026-02-28 10:22:28'),
(467, 321, 150000.00, 'ROLL OVER', '2026-02-27', '2026-03-03 06:40:19', '2026-03-03 06:40:19'),
(468, 324, 12000.00, 'BROKER FEES', '2026-03-13', '2026-03-03 06:43:55', '2026-03-03 06:43:55'),
(469, 311, 285610.00, 'ROLL OVER', '2026-03-03', '2026-03-04 06:05:40', '2026-03-04 06:05:40'),
(470, 323, 250000.00, 'UC64A90L08', '2026-03-06', '2026-03-06 12:56:13', '2026-03-06 12:56:13'),
(471, 256, 55000.00, 'ROLL OVER', '2026-03-06', '2026-03-06 12:56:45', '2026-03-06 12:56:45'),
(472, 310, 432000.00, 'ROLL OVER', '2026-02-20', '2026-03-06 12:59:17', '2026-03-06 12:59:17'),
(473, 330, 518400.00, 'ROLL OVER', '2026-03-02', '2026-03-06 13:03:28', '2026-03-06 13:03:28'),
(474, 288, 64000.00, 'PESALINK', '2026-03-08', '2026-03-08 13:55:57', '2026-03-08 13:55:57'),
(475, 288, 684320.00, 'CREDIT DISCOUNT', '2026-03-08', '2026-03-08 13:56:21', '2026-03-08 13:56:21'),
(476, 328, 600.00, 'UC86B8O2US', '2026-03-08', '2026-03-08 14:31:44', '2026-03-08 14:31:44'),
(477, 328, 1000.00, 'UC86B8OH18', '2026-03-08', '2026-03-11 18:56:04', '2026-03-11 18:56:04'),
(478, 328, 1000.00, 'UC86B8OLGX', '2026-03-08', '2026-03-11 18:56:23', '2026-03-11 18:56:23'),
(479, 334, 55000.00, 'UCCFZ933Y7', '2026-03-12', '2026-03-12 11:17:58', '2026-03-12 11:17:58'),
(480, 291, 85228.00, 'ROLL OVER', '2026-03-09', '2026-03-12 11:18:49', '2026-03-12 11:18:49'),
(481, 328, 1000.00, 'UC86B8OB4R', '2026-03-08', '2026-03-12 11:24:05', '2026-03-12 11:24:05'),
(482, 320, 4000.00, 'UCD6O93ZED', '2026-03-13', '2026-03-14 19:48:28', '2026-03-14 19:48:43'),
(483, 320, 1280.00, 'CREDIT DISCOUNT', '2026-03-13', '2026-03-14 19:49:10', '2026-03-14 19:49:10'),
(484, 324, 168000.00, 'UCDD895ZAZ', '2026-03-13', '2026-03-14 19:49:56', '2026-03-14 19:49:56'),
(485, 327, 18000.00, 'ROLL OVER', '2026-03-13', '2026-03-14 19:53:12', '2026-03-14 19:53:12'),
(486, 151, 1357518.00, 'ROLL OVER', '2026-02-03', '2026-03-14 19:58:21', '2026-03-14 20:00:44'),
(487, 340, 7200.00, 'ROLL OVER', '2026-03-02', '2026-03-15 18:47:02', '2026-03-15 18:47:02'),
(488, 341, 8640.00, 'ROLL OVER', '2026-03-12', '2026-03-15 18:48:37', '2026-03-15 18:48:37'),
(489, 342, 10368.00, 'ROLL OVER', '2026-03-21', '2026-03-15 18:50:55', '2026-04-04 11:09:31'),
(490, 336, 500.00, 'UCG6B9H4WP', '2026-03-16', '2026-03-18 16:36:00', '2026-03-18 16:36:00'),
(491, 336, 1900.00, 'UCH6B9H6IA', '2026-03-16', '2026-03-18 16:36:16', '2026-03-18 16:36:16'),
(492, 333, 18000.00, 'UCIAL9PG2R', '2026-03-19', '2026-03-19 05:16:34', '2026-03-19 05:16:34'),
(493, 332, 24000.00, 'UCIBM9XI4K', '2026-03-18', '2026-03-19 05:17:03', '2026-03-19 05:17:03'),
(494, 250, 114439.80, 'ROLL OVER', '2026-02-27', '2026-03-20 18:22:22', '2026-03-20 18:23:15'),
(495, 338, 21600.00, 'UCNKBA2UAO', '2026-03-23', '2026-03-24 14:35:05', '2026-03-24 14:35:05'),
(496, 339, 49500.00, 'UCOHDAJFL9', '2026-03-24', '2026-03-24 19:28:31', '2026-03-24 19:28:31'),
(497, 318, 6000.00, 'UCOBDAI1OC', '2026-03-24', '2026-03-25 11:38:15', '2026-03-25 11:38:15'),
(498, 346, 6000.00, 'UCPALAGDK9', '2026-03-25', '2026-03-26 06:04:45', '2026-03-26 06:04:45'),
(499, 343, 2000.00, 'UCQEVAMP9Y', '2026-03-26', '2026-03-26 20:56:09', '2026-03-26 20:56:09'),
(500, 343, 10000.00, 'ROLL OVER', '2026-03-26', '2026-03-26 20:56:19', '2026-03-26 20:56:19'),
(501, 337, 60000.00, 'UCQ9QAL1NG', '2026-03-26', '2026-03-26 20:58:07', '2026-03-26 20:58:07'),
(502, 344, 2800.00, 'UCS6BAR30I', '2026-03-28', '2026-03-29 16:41:28', '2026-03-29 16:41:28'),
(503, 344, 1200.00, 'UCS6BAN50W', '2026-03-28', '2026-03-29 16:42:19', '2026-03-29 16:42:19'),
(504, 369, 2000.00, 'UCS6BAN8ZZ', '2026-03-28', '2026-03-29 16:42:41', '2026-03-29 16:42:41'),
(505, 322, 160000.00, 'ROLL OVER', '2026-03-26', '2026-04-02 04:49:56', '2026-04-02 04:54:47'),
(506, 322, 20000.00, 'UCV9XB859B', '2026-03-31', '2026-04-02 04:54:18', '2026-04-02 04:54:18'),
(507, 345, 39600.00, 'UCU9XB3ZQX', '2026-03-30', '2026-04-02 05:07:20', '2026-04-02 05:07:20'),
(508, 350, 9800.00, 'UCUGCAZTET', '2026-03-30', '2026-04-02 05:09:52', '2026-04-02 05:09:52'),
(509, 312, 73077.12, 'ROLL OVER', '2026-03-18', '2026-04-02 05:16:16', '2026-04-02 05:16:26'),
(510, 326, 4200.00, 'ROLL OVER', '2026-03-14', '2026-04-04 11:05:46', '2026-04-04 11:05:59'),
(511, 366, 5040.00, 'ROLL OVER', '2026-03-24', '2026-04-04 11:07:35', '2026-04-04 11:07:35'),
(512, 359, 11000.00, '00630057202604032133052fb2e439', '2026-04-03', '2026-04-04 11:13:51', '2026-04-04 11:13:51'),
(513, 351, 12000.00, 'UD5SGALUXT', '2026-04-05', '2026-04-05 09:21:56', '2026-04-05 09:21:56'),
(514, 353, 2000.00, 'UD5EVBR6UM', '2026-04-05', '2026-04-05 15:42:40', '2026-04-05 15:42:40'),
(515, 353, 10000.00, 'ROLL OVER', '2026-04-05', '2026-04-05 15:42:55', '2026-04-05 15:42:55'),
(516, 308, 18000.00, 'UD7GCBW9X8', '2026-03-16', '2026-04-08 05:54:18', '2026-04-08 05:54:18'),
(517, 354, 24000.00, 'ROLL OVER', '2026-04-05', '2026-04-08 06:05:43', '2026-04-08 06:05:43'),
(518, 355, 1200.00, 'UD76BBT9AX', '2026-04-05', '2026-04-08 06:09:41', '2026-04-08 06:10:09'),
(519, 361, 800.00, 'UD76BBT9AX', '2026-04-07', '2026-04-08 06:10:57', '2026-04-08 06:10:57'),
(520, 362, 1000.00, 'UDAHD0JY97', '2026-04-10', '2026-04-13 16:22:38', '2026-04-13 16:22:38'),
(521, 362, 5000.00, 'ROLL OVER', '2026-04-10', '2026-04-13 16:22:56', '2026-04-13 16:22:56'),
(522, 361, 3040.00, 'ROLL OVER', '2026-04-10', '2026-04-13 16:27:29', '2026-04-13 16:27:29'),
(523, 357, 11000.00, 'UDABM0LERC', '2026-04-10', '2026-04-13 16:34:15', '2026-04-13 16:34:15'),
(524, 356, 6600.00, 'UD9OI0J2U5', '2026-04-09', '2026-04-13 16:38:31', '2026-04-13 16:38:31'),
(525, 325, 342732.00, 'ROLL OVER', '2026-04-03', '2026-04-13 16:42:26', '2026-04-13 16:42:26'),
(526, 368, 12441.60, 'ROLL OVER', '2026-03-31', '2026-04-13 17:17:21', '2026-04-13 17:17:21'),
(527, 367, 6048.00, 'ROLL OVER', '2026-04-03', '2026-04-13 17:18:59', '2026-04-13 17:19:12'),
(528, 375, 22186.80, 'ROLL OVER', '2026-04-10', '2026-04-13 17:23:12', '2026-04-13 17:23:36'),
(529, 357, 41000.00, 'UDDBM0XT1E', '2026-04-13', '2026-04-14 06:39:52', '2026-04-14 06:40:13'),
(530, 357, 2000.00, 'UDEBM0Z49A', '2026-04-14', '2026-04-14 06:54:18', '2026-04-14 06:54:18'),
(531, 357, 21600.00, 'CREDIT DISCOUNT', '2026-04-14', '2026-04-14 06:54:47', '2026-04-14 06:54:47'),
(532, 365, 16000.00, 'UDFAL0TXHB', '2026-04-15', '2026-04-15 10:12:36', '2026-04-15 10:12:36'),
(533, 370, 10000.00, 'UDFKB0ONZY', '2026-03-15', '2026-04-15 10:16:30', '2026-04-15 10:16:30'),
(534, 329, 66000.00, 'ROLL OVER', '2026-03-20', '2026-04-15 10:24:29', '2026-04-15 10:24:29'),
(535, 370, 18800.00, 'UDGKB0S6QI', '2026-04-16', '2026-04-16 15:00:18', '2026-04-16 15:04:25');
INSERT INTO `repayments` (`id`, `loan_id`, `amount`, `transaction`, `repayment_date`, `created_at`, `updated_at`) VALUES
(536, 370, 2000.00, 'UDFEV0ZRS1', '2026-04-15', '2026-04-16 15:00:47', '2026-04-16 15:05:41'),
(537, 369, 10000.00, 'ROLL OVER', '2026-04-15', '2026-04-16 15:07:20', '2026-04-16 15:07:20'),
(538, 381, 1000.00, 'UDGEV13YEB', '2026-04-17', '2026-04-17 04:26:56', '2026-04-17 04:26:56'),
(539, 363, 30000.00, 'ROLL OVER', '2026-04-09', '2026-04-17 18:13:28', '2026-04-17 18:13:28'),
(540, 335, 110796.40, 'ROLL OVER', '2026-04-09', '2026-04-17 18:16:19', '2026-04-17 18:16:19'),
(541, 371, 1448.00, 'UDH6B137T1', '2026-04-17', '2026-04-17 18:41:14', '2026-04-17 18:41:14'),
(542, 365, 16000.00, 'UDIAL15ZLB', '2026-04-17', '2026-04-18 18:09:11', '2026-04-18 18:09:11'),
(543, 365, 800.00, 'UDIAL16LCX', '2026-04-18', '2026-04-18 18:09:40', '2026-04-18 18:10:09'),
(544, 365, 3200.00, 'CREDIT DISCOUNT', '2026-04-18', '2026-04-18 18:10:29', '2026-04-18 18:10:29'),
(545, 371, 400.00, 'UDK6B1C0JB', '2026-04-20', '2026-04-20 07:41:07', '2026-04-20 07:41:31'),
(546, 266, 10000.00, 'UDKFG1GB48', '2026-04-18', '2026-04-20 08:13:46', '2026-04-20 08:13:46'),
(547, 266, 54155.50, 'CREDIT DISCOUNT', '2026-01-02', '2026-04-20 08:14:46', '2026-04-20 08:16:21'),
(548, 373, 648.00, 'UDK6B1DFIB', '2026-04-20', '2026-04-20 16:21:51', '2026-04-20 16:21:51'),
(549, 372, 6000.00, 'ROLL OVER', '2026-04-20', '2026-04-21 04:04:50', '2026-04-21 04:04:50'),
(550, 376, 26700.00, 'UDL1U1MSJM', '2026-04-21', '2026-04-22 06:40:46', '2026-04-22 06:40:46'),
(551, 373, 2500.00, 'UDL6B1HSIL', '2026-04-21', '2026-04-22 06:42:33', '2026-04-22 06:42:33'),
(552, 373, 500.00, 'UDL6B1I22V', '2026-04-21', '2026-04-22 06:42:57', '2026-04-22 06:42:57'),
(553, 373, 364.80, 'CREDIT DISCOUNT', '2026-04-21', '2026-04-22 06:43:37', '2026-04-22 06:43:37'),
(554, 382, 36000.00, 'UDLPX1KFBE', '2026-04-21', '2026-04-22 07:01:57', '2026-04-22 07:01:57'),
(555, 382, 3600.00, 'CREDIT DISCOUNT', '2026-04-21', '2026-04-22 07:02:12', '2026-04-22 07:02:12'),
(556, 378, 9000.00, 'CREDIT DISCOUNT', '2026-04-24', '2026-04-23 06:21:46', '2026-04-23 06:22:08'),
(557, 377, 9000.00, 'UDPAI2A2WO', '2026-04-25', '2026-04-26 06:23:55', '2026-04-26 06:23:55');

-- --------------------------------------------------------

--
-- Table structure for table `repayment_overflows`
--

CREATE TABLE `repayment_overflows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_loan_id` bigint(20) UNSIGNED NOT NULL,
  `to_loan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('CskuLIkb1PW8SoD1FT0ohn7S76MWYNxwfsDZktr2', 1, '41.90.172.224', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ3dNZ05SaEJ0OUNQWTl0Vlk1VHUyOTlnN2xOYmczRlVLYzJJbzBGSCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mzc6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9kYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1777217702),
('nKILNYhwwam4CXsMYhRlSUauyAPtJlaIMkvgWqVY', 1, '105.160.126.142', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYnBSNElvNE9XWmlodVF5UFNFSm5zUEN3bXk2dTMzam5xdVdtQU9FSyI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czozNzoiaHR0cHM6Ly9sb2Fucy5zaGFyZXQuYWZyaWNhL2Rhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1777193906),
('pJLYGqCrkVausYbIcNp6Sq6uhBDG6CBtJgjhMKmB', NULL, '74.7.227.24', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.3; +https://openai.com/gptbot)', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUWJJVnJHbkx1czVPNmV1bmFVS2k3ZXVWNHJhNlNjV3ZBUkRHUEVwciI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vbG9hbnMuc2hhcmV0LmFmcmljYS9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1777213661);

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
(1, 'Aiyo Invites', 'logo.svg', NULL, NULL, NULL, 'Your trusted application', 'UTC', 'd-m-Y', 'H:i:s', 'KES', 'KSh', '#3A57E8', '#08B1BA', NULL, NULL, NULL, '{\"country\":\"\",\"city\":\"\",\"name\":\"\",\"latitude\":\"\",\"longitude\":\"\"}', NULL, NULL, 0, 15, NULL, NULL, '{\"notifications\":{\"email_notifications\":true,\"push_notifications\":true,\"sms_notifications\":false,\"notification_sound\":true},\"security\":{\"two_factor_auth\":false,\"login_attempts\":5,\"session_timeout\":30,\"password_expiry\":90},\"integrations\":{\"google_analytics\":\"\",\"google_maps_key\":\"\",\"mail_driver\":\"smtp\",\"mail_host\":\"\",\"mail_port\":\"587\",\"mail_username\":\"\",\"mail_password\":\"\"},\"backup\":{\"auto_backup\":true,\"backup_frequency\":\"daily\",\"backup_retention\":30,\"backup_to_cloud\":false},\"company\":{\"website\":\"\",\"phone\":\"\",\"email\":\"\",\"address\":\"\",\"about\":\"\",\"mission\":\"\",\"vision\":\"\",\"values\":\"\"},\"currency_position\":\"before\"}', '{\"home\":{\"enabled\":true,\"title\":\"Home\",\"slug\":\"\",\"show_in_menu\":true,\"order\":1},\"about\":{\"enabled\":true,\"title\":\"About Us\",\"slug\":\"about\",\"show_in_menu\":true,\"order\":2},\"services\":{\"enabled\":true,\"title\":\"Services\",\"slug\":\"services\",\"show_in_menu\":true,\"order\":3},\"contact\":{\"enabled\":true,\"title\":\"Contact Us\",\"slug\":\"contact\",\"show_in_menu\":true,\"order\":4}}', '{\"facebook\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-facebook-fill\",\"name\":\"Facebook\",\"color\":\"#1877F2\",\"order\":1},\"twitter\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-twitter-fill\",\"name\":\"Twitter\",\"color\":\"#1DA1F2\",\"order\":2},\"instagram\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-instagram-fill\",\"name\":\"Instagram\",\"color\":\"#E4405F\",\"order\":3},\"linkedin\":{\"enabled\":false,\"url\":\"\",\"icon\":\"ri-linkedin-fill\",\"name\":\"LinkedIn\",\"color\":\"#0A66C2\",\"order\":4}}', '2026-01-18 09:44:28', '2026-03-06 11:41:39');

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
  `role` enum('admin','borrower','broker','teller') NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
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

INSERT INTO `users` (`id`, `name`, `avatar`, `phone`, `email`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`, `gender`, `dob`, `pob`, `nationality`, `marital_status`, `religion`, `disability`, `education`, `kin_name`, `kin_email`, `kin_phone`, `kin_occupation`, `kin_relation`, `kin_id_type`, `kin_id_number`, `signature`, `id_type`, `id_number`, `id_front_path`, `id_back_path`) VALUES
(1, 'Dennis Kibett', 'avatars/HdBHuvf6S6ibGQh84Asue6XvRcgPiRMw68fLaXnC.png', '0717492048', 'kibettdennis@gmail.com', NULL, '$2y$12$2CR8.J2PitDIJoOoZZAfuuvcM0lU9zLj6kLxVksy2RiU7aPySXxQK', 'admin', 0, 'Vdf4DyGIcRaAYNSvI7m5z8ofhpoizS2lJvBoRoEgQUwEjp4bNmed1IfBvJ9E', '2025-04-17 11:41:51', '2026-01-27 19:19:09', 'male', '1994-03-24', NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', 'Joseph Koech', 'josephkkoech@gmail.com', '+254722580928', 'Regional Administrator', 'Father', 'national_id', '0587257', 'signatures/signature_Dennis_Kibett.png', 'National ID', '31425580', 'id_cards/gi21rr49gf83kPyfu5RidoXExHg4ryw7nNVQAx11.jpg', 'id_cards/EUFh2ypgIKD3mVPhDJCRam2qZXKjytJBuU2UWJL7.jpg'),
(2, 'Edward Kibet', '', '+254 710 920629', 'edwardkipsanai94@gmail.com', NULL, '$2y$12$HNUj2oDPc4GNbBKze3XbB.gsZFsEp7W2VOXu.wcKU8AIpBsK9E9jq', 'borrower', 0, NULL, '2025-04-17 11:42:36', '2026-01-22 20:30:33', 'male', NULL, NULL, 'Kenya', 'single', 'Christianity', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Isiro Agencies', '', '0727088262', 'isiroagencies@gmail.com', NULL, '$2y$12$JMXtud5UquYiEDMC7CRhyOEMvsX70.oUG8s/AsbRu4O285gIGQwfG', 'broker', 0, 'm3WqL8GFLzcquzxgt4Zl8hdNdomO3dJM8kRhWdGrG154O6VFbK202EFz0rfW', '2025-04-17 11:43:36', '2025-04-17 16:41:05', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'BILDAD WAMBUA', '', '0712345678', 'bildadwambua@gmail.com', NULL, '$2y$12$MQaKkMN8wDjjbcbWmAuFGeYl9ZWZXP11b1.jw5JYamJ1nqLPDc8Pq', 'borrower', 1, NULL, '2025-04-17 12:21:34', '2025-04-17 12:21:34', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Keneth Otieno Owino', '', '0790113034', 'kenowino@gmail.com', NULL, '$2y$12$TxyyVlWqdhMwBK1E2Vc1..GyPQzFRvy7icMJOgJuthUAmB1D/y6pW', 'borrower', 0, NULL, '2025-04-17 13:02:01', '2025-04-17 13:02:01', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'Michelle moraa sese', '', '254793867121', 'michellesese99@gmail.com', NULL, '$2y$12$4VGUFOjrMXOxqpY1i2HNPegukv9eI91aj9JOfikUSwEdyiBu4VQQu', 'borrower', 1, NULL, '2025-04-17 13:12:47', '2025-04-17 13:12:47', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'MARKLEWIS WACHIRA GATUTHA', '', '+254742421530', 'markwachira07@gmail.com', NULL, '$2y$12$Ef0YDy2KjAYZeV7vs2kyU.0nBIV0BMoG8UkTNrX5byblnm2p/txJq', 'borrower', 0, 'CxyThXOGCUwzfLeCjjeeN8QDUJZ2YiN1IfTcJdf8Azboi9ql3gpKTklFv6wy', '2025-04-17 13:22:24', '2025-04-17 13:22:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'CHRISTOPHER EPARA ISURA', '', '+254713985762', 'isuracarhire@gmail.com', NULL, '$2y$12$1/aF94HNoHf2SXwbYh/.xOgdT2qWkkQjNm0uwdX6TSf03pXnDyd.2', 'borrower', 1, NULL, '2025-04-17 13:27:08', '2025-04-17 13:27:08', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Shadrack Cheruiyot', '', '0719744247', 'shadykipkorir@gmail.com', NULL, '$2y$12$omwtR4JZbfdt/JvbD/i2yubuzZ1lYf2lRnL58fK29kdxiIRYyYOw6', 'borrower', 0, NULL, '2025-04-17 13:29:11', '2026-02-03 06:19:24', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '31586471', NULL, NULL),
(13, 'Dennis Kibet', '', '+254717492048', 'karleighdeno@gmail.com', NULL, '$2y$12$2CR8.J2PitDIJoOoZZAfuuvcM0lU9zLj6kLxVksy2RiU7aPySXxQK', 'borrower', 0, 'iGo37kDejLgb1rkvJ2PoOO4ixpfeQb6zALak8nusiajJDktzx9zeW7nhHGKf', '2025-04-17 13:32:34', '2026-02-09 17:54:40', 'male', '1994-03-24', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Samson Kiplangat', 'samsontanui25@gmail.com', '0717492048', 'Accountant', 'Brother', 'national_id', '3400000', 'signatures/signature_Dennis_Kibet.png', 'national_id', '31425580', NULL, NULL),
(14, 'MOSES OKURUTU BARASA', '', '254726104495', 'mosesbarasa@gmail.com', NULL, '$2y$12$STSR.E/TpRR86bXF3Jdo3OXldtd7YAPk183NisR1HD7eRbXB6.54G', 'borrower', 0, NULL, '2025-04-17 14:07:25', '2025-04-17 14:07:25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'Samson Kiplangat', '', '0701607959', 'samsontanui25@gmail.com', NULL, '$2y$12$pBYO6eGOW1r2bgxqG9ePJ..06DxMb1bZGeJ/UVp7DtAaB8bO2Ngya', 'admin', 0, '6g4lcvyGHhKnziD43gRsHznruRgY5FHGEwDCXgc0E24K2FoWScT5hPKDZGPS', '2025-04-17 14:20:54', '2025-12-27 08:12:41', 'male', '2025-05-21', NULL, 'KE', 'single', 'Christian', 0, 'Master\'s Degree', 'Joseph Koech', 'josephkkoech@gmail.com', '254722580928', 'Business man', 'Father', 'national_id', '0587257', 'signatures/signature_Samson_Kiplangat.png', 'national_id', '34418665', NULL, NULL),
(16, 'DENIS LEVIS NGIRA', '', '+254721381582', 'ngirangira@gmail.com', NULL, '$2y$12$XZ8sfnO/wYVqOo6AApuyxOMsfDHGBaIgT2IhHtCA.DyN6GGFWQoS.', 'borrower', 0, NULL, '2025-04-17 14:24:34', '2025-04-17 14:24:34', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, 'LINNER JEPKEMBOI KOECH', '', '0720540112', 'ljkoech@gmail.com', NULL, '$2y$12$ORh0wj9X0ste0XVVc5wa3edhnpkCgZrpqURDczj5t0OLsGU32dX4u', 'borrower', 0, NULL, '2025-04-17 14:29:23', '2025-04-17 14:29:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 'Stephanie Kanyeki', '', '+254727088262', 'stephaniekanyeki@gmail.com', NULL, '$2y$12$5hzR.H/wPVoMr9Qz95VW7u.L6lvnIbe.mr5xd7xumiMD7uJjz3b0W', 'borrower', 1, NULL, '2025-04-20 10:39:32', '2025-04-20 10:39:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'CFO County Busia', '', '0700123456', 'busiacfo@gmail.com', NULL, '$2y$12$h896m0HMnTjHiXNj5JiIvOkh7EDLFqpmn9iYs0aqoD7BJeY0NCN16', 'borrower', 0, NULL, '2025-04-20 17:14:25', '2025-04-20 17:14:25', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 'JOSEPH KOECH', '', '0722580928', 'josephkkoech@gmail.com', NULL, '$2y$12$FwGXheD3OexInOl/tWVxluF4rJDTh6l2loNnD5I0OIYlpQ//0NQ/C', 'borrower', 1, NULL, '2025-04-20 17:45:22', '2025-04-20 17:45:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 'David Ihaji', '', '0717465550', 'davidihaji@gmail.com', NULL, '$2y$12$tgVICqfy81Ab75UjvFirCOe3sl5H74yR9fhhSXTKMGpscRpeEt/Jy', 'borrower', 1, NULL, '2025-04-22 13:34:37', '2026-02-01 19:34:19', 'male', '1995-04-23', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Peter', 'nyenzo@gmail.com', '0796952247', 'Tech', 'Brother', 'national_id', '31930122', NULL, 'national_id', '31930121', 'id-documents/0MftMeNEPFqhlUSs5aH8WOjRH4gZo7by4J9RHDRD.png', 'id-documents/Ay2f4tFEcT9J0qje0Gm7wbDMqqS9YbTB1YvtAJtY.png'),
(22, 'Justine Omori', '', '0721855878', 'justineomori@gmail.com', NULL, '$2y$12$WklPbJc9w7aDMncNznJ70.ikODU0icUKLaBr5a6WBn0yb0l6PR2ia', 'borrower', 1, NULL, '2025-05-07 12:10:19', '2025-05-07 12:10:19', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'Joseph', '', '+255758556562', 'lamearkaaya@gmail.com', NULL, '$2y$12$oRdMr9kL9rqxBJK0v3TQv.pvflSdVOwgwUtCmMaG.CY7c0GlCCE0i', 'admin', 1, NULL, '2025-05-08 10:53:11', '2025-05-08 10:53:11', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'Steve Omwenga', '', '+254 727 665808', 'steveomwenga@gmail.com', NULL, '$2y$12$1sd9TZHAnikJzo9u5IQLOOubmQ0AFrlyoafY9MLajVN3XHtMFRaHK', 'borrower', 0, NULL, '2025-05-13 06:49:55', '2025-05-13 06:49:55', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 'DOREEN NTARANGWI', '', '+254710438971', 'doreenntarangwi@gmail.com', NULL, '$2y$12$z6QRzg8ktp/YWvd33nvhYeUQxv2YB109l3gZjpElykM/.o5WxgX7u', 'borrower', 1, NULL, '2025-05-15 08:08:59', '2025-05-15 08:08:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 'Ann Njora', '', '+254 722 596985', 'kirajsupplierskenya@gmail.com', NULL, '$2y$12$JTGPNwFVmH3kFCQffkVLlePz0/qaFV7c7JsSSVnujmHI/XYtxLga6', 'borrower', 0, 'WU70YAjW4SXP4j2m4Ys6nM1C91lEpQzULs442bpFhR02gmD06yUatuxFUXYN', '2025-05-19 12:45:53', '2026-01-28 11:58:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 'JOSEPH KINGORI WAMBUGU', '', '+254 714 390696', 'josephkingori@gmail.com', NULL, '$2y$12$8cGto1IdjyPNIiM0HN48IuRmUHcarG8rjmGqXVaDMJhlkvomvO10O', 'borrower', 0, NULL, '2025-05-20 13:03:03', '2025-05-20 13:03:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, '28539464', NULL, NULL),
(28, 'Njeri Nduati', '', '0720517386', 'njerinduati@gmail.com', NULL, '$2y$12$C.r6Fgc1lc8xmkJtTWMTe.r/UR2qhW5S6qohBouDgOKU3d9Oo.lHe', 'borrower', 0, NULL, '2025-05-25 18:36:15', '2025-05-25 18:36:15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '29753954', NULL, NULL),
(29, 'Nigel Cecil Otieno', '', '+254 721 655906', 'cecilmash@gmail.com', NULL, '$2y$12$5Sbd9JS17UvgL6rgTh4dwO7ciB./oJOHau9jtLxUDWGqRyTKuscFC', 'borrower', 0, NULL, '2025-05-27 13:47:29', '2025-10-29 07:03:27', 'male', '1985-03-25', NULL, 'KENYAN', 'single', NULL, 0, NULL, 'Barry Macharia', 'barryblacks@gmail.com', '0720899750', 'Businessman', 'Brother', 'national_id', '22445566', 'signatures/signature_Nigel_Cecil_Otieno.png', 'national_id', '24011567', 'id-documents/mZ0nHUoGwxYUruZ1ioNvZJpnm9JUY82EFqTF6OPK.jpg', 'id-documents/2uoCUuOyIDQCAenJLrPuH1gbgfLdizW83cdyknWi.jpg'),
(30, 'Ian Kipkorir Cheruiyot', '', '0710911168', 'iankcheruiyot@gmail.com', NULL, '$2y$12$QUvaA6TvKXP1xf3/3GU00.TGelMgTOAVPml2Vljx1chW/2CGYU0gG', 'admin', 1, NULL, '2025-05-29 05:14:33', '2025-05-29 05:14:33', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 'Leon Musau', '', '0720747652', 'musau.mumo@teflontradingltd.co.ke', NULL, '$2y$12$WU2gPKt9m5qTKZPhoPzUIeEkyTMPKK3QCUwB.2rwe46cBJsQif1/G', 'borrower', 1, 'VH3X52MKGcLon0bybPJmawH46jKpP4YatLdpyC57HBhYGpSaVLOiOqkKZao0', '2025-05-29 13:47:36', '2026-02-09 17:16:38', 'male', '1991-07-25', NULL, 'KE', 'single', NULL, 0, 'Master\'s Degree', 'Micahel Musau', 'mukikiilumusau@gmail.com', '+254721374196', 'coffee trader', 'Brother', 'national_id', '32313915', 'signatures/signature_Leon_Musau_1770660998.png', 'national_id', '32313898', 'id-documents/xACFanBfJtr4nUIOMm4XGMtsv5gbiWhIqksinlaL.jpg', 'id-documents/OsxovFf5SSZznQOKEmA78jEmMU2QwbXNaMFgF43p.jpg'),
(32, 'Brian Kiprop Kiprono', '', '0720098561', 'briankiprop@gmail.com', NULL, '$2y$12$GxQC0YyruW8LgBwdU7O8zuhxDjvsCzRtM8qoq34LZkD8fWQlK482O', 'borrower', 1, NULL, '2025-06-02 11:17:53', '2025-06-02 11:17:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 'Alan Kipng\'etich Limo', '', '0705596198', 'limoalan@gmail.com', NULL, '$2y$12$TZ3mZ4LycemIe6Urg8X2oeYfD6Bdk6VsXnLR6sCNvzKSE15soyHFa', 'borrower', 0, NULL, '2025-06-14 10:26:20', '2025-06-14 10:26:20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '31667416', NULL, NULL),
(34, 'MOLUK AWAD', '', '0725833011', 'molukawad519@gmail.com', NULL, '$2y$12$dozzobeZBqXkor14yj8lJuRuHOH601FKA9iI4yB48hpdNg1UZjMw2', 'borrower', 0, 'A2jaRU9ng7dGHKMdOdPHcXcAvl1HSiuWmxTaEaDsnJ0lGWqSwyDPoxxffxuu', '2025-06-15 13:55:59', '2025-06-15 13:55:59', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 'Lewis Kimutai', '', '0725697837', 'leuville6@gmail.com', NULL, '$2y$12$VQsSkFy5bS.wpUElVPh8y.n7D2WKd.1DQlAgS1AOg8kz1Ua5tZgRC', 'borrower', 0, NULL, '2025-06-20 18:30:23', '2025-06-20 18:30:23', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 'Newton Wesonga Okumu', '', '0703654828', 'newtoncarloz@gmail.com', NULL, '$2y$12$Wxj00avSUmFTTVsquTqRouBQY0.akLqfj.Nl.czNQ7ZlmFbYILDnm', 'borrower', 1, NULL, '2025-06-21 06:15:40', '2025-06-21 06:15:40', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 'BRIAN KIPLANGAT', '', '0706099588', 'briankiplangatbk@gmail.com', NULL, '$2y$12$ArbwFMHaiL/rCrXnx.VHounCl/3rOUbPO2OwRJYu9ErQVDRsCU5/S', 'borrower', 1, NULL, '2025-06-23 07:43:27', '2025-06-23 07:43:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 'HONOURINE NZISA MUTUNGA', '', '+254729080190', 'honourinenzisa@gmail.com', NULL, '$2y$12$nP6kWDrHZrMx6A3GkycO5.VNP7S.f3CoHmpUlcCJgRv3zs9FeE4K6', 'borrower', 0, NULL, '2025-07-07 08:51:03', '2025-07-07 08:51:03', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 'Faith Chepngetich', '', '0700701380', 'chepngetichfaith981@gmail.com', NULL, '$2y$12$12yyIb3Y0op09RDuG.GBn.U2NYXkp.9.Y2xCmmXrcccZsfZTfTrA.', 'borrower', 0, 'G3w4zV027oUDqDFWomDWq2YgILmyVHttMBQS9wnsa3Cb38tclTkT21umNDgQ', '2025-07-13 16:11:22', '2025-07-13 16:11:22', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 'Ian Otieno Okoth', '', '0719795614', 'ianokoth@gmail.com', NULL, '$2y$12$bfTyyCGeKrzCEtU1WQZh5em967lE2kU0fLRvQ8jgPCzgDOxvy.llC', 'borrower', 0, NULL, '2025-07-14 11:35:57', '2025-07-14 11:35:57', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 'Cynthia Wanjiru Muhia', '', '+254 717 459536', 'cynthiashiro78@gmail.com', NULL, '$2y$12$bO3E9MoR5kXMmYxKfZ1IJ.JU8NgFR283MNCG/lIiiZ3KOWza0O4UC', 'borrower', 0, 'mbWJ31V0W1EcyI5A0eckNUDTEbTgk92YCbihO5jmtUcZVlroH33jUU77JWbr', '2025-07-15 10:00:13', '2025-07-15 10:00:13', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 'Francis Kabutu', '', '0728167511', 'kabutu.frank@gmail.com', NULL, '$2y$12$VknLy1ceuXycC3ZgvpjDfum6b6FNMMHiIq6YT6CuMDGhLPYesmPm6', 'borrower', 1, NULL, '2025-07-17 10:52:30', '2025-07-17 10:52:30', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 'Brian', '', '0717503595', 'gachugubrian8@gmail.com', NULL, '$2y$12$GNKcltFvv5DmYlB6yGtf6.iYjL4fx5FKbT.oEigQktpAxOvTgsw52', 'borrower', 0, NULL, '2025-07-28 06:05:53', '2025-07-28 06:05:53', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 'Arnold Maina', '', '+254724574681', 'arnold.x.maina@gmail.com', NULL, '$2y$12$dXOkpwX5CB9Ft2iaL6lUYOEqhex5utpQBJbhXrtEbHsIsb/7qsVjC', 'admin', 0, NULL, '2025-08-06 05:11:15', '2025-08-06 05:11:15', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 'Ruth Sudoi Makalla', '', '+254 721 440306', 'rsmakallah@gmail.com', NULL, '$2y$12$EHzV094qb9bZ88BvsxYkLuiccJ8ZUc4AxXGudlWvhGQ6Y423Wk9.O', 'borrower', 0, NULL, '2025-08-06 11:25:20', '2025-08-06 11:25:20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 'Michelle Muriithi', '', '0732792642', 'michthi.mm@gmail.com', NULL, '$2y$12$JjG3FMRdhKucKdYVblAOouvAREuzGHoB4aLLgIFwlZYZ1CUqh8oHS', 'admin', 0, NULL, '2025-08-12 14:29:27', '2025-08-12 14:29:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 'Kelvin Keter', '', '0727025050', 'kipchirchir101@gmail.com', NULL, '$2y$12$8QZw5VeEAiX6E2GIu6M5UOFAQswu0TAER1ht22HcLvTvHJ3UetubC', 'borrower', 0, NULL, '2025-08-14 07:30:39', '2025-08-14 07:30:39', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 'Sharon Chemurgor', '', '0704815115', 'chemurgorsharon@gmail.com', NULL, '$2y$12$lIKTaV/PMy9/MbWW55kO7eKfVeHmygm3Ug9uyg8B6NzGd8FMg9Hdy', 'borrower', 0, NULL, '2025-08-19 14:58:14', '2025-08-19 14:58:14', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 'Peter Tanui', '', '0701134508', 'thetanlit6@gmail.com', NULL, '$2y$12$tjpGbnZbXxtIIv53nEp4L.d.bcEoXsfhNXJToCiXtYTP669H0pVw2', 'teller', 0, 'aX6h8TUgu4woLFSU8Vncbfr43G0J4ZRcd3omSLIRZqXH0eYKSfRSDTeY5ADj', '2025-09-02 07:51:46', '2025-10-15 10:45:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Peter_Tanui.png', NULL, NULL, NULL, NULL),
(50, 'Mohamed Abdirahim Abdi', '', '0721544928', 'mohaabdi41@gmail.com', NULL, '$2y$12$CVQWIt6FGp..1WoA8tbBL.hnoxaDIDhFrVhHoZtjaUEoOKiGsXZZi', 'borrower', 0, 'Av2fj49VRHgKc6WUG6hEivJXyLIw1XCKCMp1yQFDQVazAmaXxll8bzPjSKsk', '2025-09-10 06:39:51', '2025-10-27 10:13:24', 'male', '1993-04-20', NULL, 'Kenyan', 'single', 'Muslim', 0, NULL, 'TAYIB haithar', 'mohaabdi41@gmail.com', '0723597683', 'Businessman', 'Brother', 'national_id', '36827361', 'signatures/signature_Mohamed_Abdirahim_Abdi.png', 'national_id', '36827364', 'id-documents/DNLFpZa2hXxTmhI7GUJSaxXby9KkNCqMm5Sel8rU.jpg', 'id-documents/VQnXyNlVCCJpKKz74z6CPnGufSVqrALa9Pwl81YR.jpg'),
(51, 'Kelvin', '', '0726471918', 'c.kelvinrotich@gmail.com', NULL, '$2y$12$Lu9zm/hSdT2PT08yOzz.9.yz/ZqEjg3UAEg.q1Vx5VLkEDmS5.1s.', 'borrower', 0, NULL, '2025-09-14 12:24:32', '2025-09-14 12:24:32', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 'Marion Lewenei', '', '0705254257', 'leweneimarion@gmail.com', NULL, '$2y$12$4/HAJTqnsZ/.QW/BRleIsuS.jT.rvb3tLqyE1aFfyAsZvA8awbMbO', 'borrower', 0, NULL, '2025-10-01 09:11:31', '2025-11-21 05:42:00', 'female', '2025-05-15', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Maureen Mathithi', 'irenejunnie@gmail.com', '+254705254257', 'businessperson', 'Business Partner', 'national_id', '36340048', 'signatures/signature_Marion_Lewenei.png', 'national_id', '40543156', NULL, NULL),
(53, 'EMMANUEL Ruwa Tsuma', '', '0768384462', 'emmanueltsuma19@gmail.com', NULL, '$2y$12$FdDOhmJWdlP4u0JRcT3sBOftNNL2Rl0MPGUNz1Q1Q6h0/SIY02bmm', 'borrower', 1, NULL, '2025-10-03 06:04:44', '2026-03-06 10:05:01', 'male', '2000-06-27', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Ronald ruwa', 'ronaldtsuma21@gmail.com', '0705110637', 'Human resource officer', 'Father', 'national_id', '8435579', 'signatures/signature_EMMANUEL_Ruwa_Tsuma.png', 'national_id', '38276910', 'id-documents/YE5aPHeTXZaDSZagkvH8rPOs5bNyYR5WkK9sebFZ.jpg', 'id-documents/u1AdJo50lkrTAohkTQcS3q2QuLLrYQoLVnrLCnQI.jpg'),
(54, 'Isaac Ngugi', '', '0711499655', 'isaacngugibaker@gmail.com', NULL, '$2y$12$/IyJDyyBqHgyN10RhOw/u.oaFnjcibWlI3dgu7/uFyo7kxIjCvvme', 'borrower', 0, NULL, '2025-10-08 04:06:13', '2025-11-04 03:59:42', 'male', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Isaac_Ngugi.png', NULL, NULL, NULL, NULL),
(55, 'Lawrence Kipngetich Byegon', 'avatars/l3VJhlbbZj8ipDk8RnLcF6y40LExyflK4TomEmUL.jpg', '0722778422', 'lawrencebyegon@gmail.com', NULL, '$2y$12$RDyjh/WTwbe2EumZLmeMMO3QApL/QXqDlv8srp.WzycTAOdOJdpOS', 'borrower', 1, NULL, '2025-10-23 05:37:11', '2025-10-26 07:54:26', 'male', '1994-05-01', NULL, 'Kenyan', 'married', 'Christian', 0, NULL, 'Mildred Asili', 'mildredasili21@gmail.com', '0717633016', 'Businesswoman', 'Business Partner', 'national_id', '38353237', 'signatures/signature_Lawrence_Kipngetich_Byegon.png', 'national_id', '31757677', 'id-documents/naw4em7mhBqObFUwTZIbCkCkoPMSgK3cgCHvMTEg.jpg', 'id-documents/yIAOFuUMIkIpqPT71ITNriTVI5C76L6M0XuKBGCn.jpg'),
(56, 'Francis Ndungu', '', '0742386797', 'frankietheuri@gmail.com', NULL, '$2y$12$9qYfC4yjURl5XAjRDxPavOm5ooZdf4NPZBdXlWG44MOuKErGKbC/i', 'borrower', 0, NULL, '2025-10-24 03:31:10', '2026-02-01 19:45:48', 'male', '1996-10-05', NULL, 'Kenya', 'single', 'Christian', 0, NULL, 'Julie Wanjiru', 'julie.wanjiru1@gmail.com', '0114204599', 'Logistician', 'Sister', 'national_id', '30146007', NULL, 'National ID', '33103892', 'id-documents/kkzoNCDiAeejsCYixVoQ7PH3tR7cjZdQFaZ1zFVo.jpg', 'id-documents/djfdMdje61nUNps5Q6anDSyxBH13hf0eEF7hND18.jpg'),
(57, 'Douglas Kipchirchir Yegon', 'avatars/1pU9bhJ1n7D1pOchC8HX3sjPjyQvqQYROnL8cGZe.jpg', '0701849455', 'yegondouglas@gmail.com', NULL, '$2y$12$N.ac.IwoRAc/ahSex9kMkeu/yqbq8UwDiq/wxXUWZ8nfAOQbscLPe', 'borrower', 0, NULL, '2025-10-24 09:49:49', '2025-10-24 13:12:24', 'male', '1996-01-04', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Elvis Kipkoech Yegon', 'elviskyegon@gmail.com', '0717752055', 'Businessman', 'Brother', 'national_id', '0734970', 'signatures/signature_Douglas_Kipchirchir_Yegon.png', 'national_id', '32507485', 'id-documents/EMC78WWzte1K4r4SUGczhNcnZEzZ3tRSBnEydcAO.jpg', 'id-documents/OpYuy3P29xfoGaqZ5sSH5aJLjGaC5KUcPtIYHmlS.jpg'),
(58, 'Vivian Simiyu', '', '722778298', 'viviansimiyu77@gmail.com', NULL, '$2y$12$8bYJi.ZeyquI/Xcp87hiuOncTWxq4YXHcLQ/iL/41JIwj1Gryri0i', 'borrower', 0, NULL, '2025-10-25 13:22:18', '2026-04-26 06:57:39', 'female', NULL, NULL, 'Kenya', 'single', 'Christianity', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'National ID', NULL, NULL, NULL),
(59, 'Justin nyarindo', 'avatars/1jLvUxRf2uVvqvNN7EgmR3hAKMVz99hRceMqMGeY.jpg', '0796165555', 'aguilanmilano@gmail.com', NULL, '$2y$12$DHM3FZFQQPbaRPchxlxifOZC9iXDb8yfWiK8Z38b6OfZXSmL5KEg2', 'borrower', 0, NULL, '2025-10-27 13:40:41', '2026-02-02 13:16:40', 'male', '1996-07-03', NULL, 'Kenyan', 'single', 'Christian', 0, NULL, 'Stella nyarindo', 'stelanyarindo@yahoo.com', '0723396150', 'Secretary', 'Mother', 'national_id', '15247846', 'signatures/signature_Justin_Nyarindo.png', 'national_id', '34760573', 'id-documents/dWt8NmMfqdXOtIIoibeyQMbeNemkVBfQ0o4nE5og.jpg', 'id-documents/ABumIl2Hs09QcHVrYKMsmTRiIPpQjUkoYvDaEy8T.jpg'),
(62, 'Douglas Lutomia', '', '0799918736', 'douglaslutomia13@gmail.com', NULL, '$2y$12$rpm9tsP1K9SgQytf3biZQ.jTFR5uS2yflzOMWfMcveQ5Y.bqZvDh6', 'borrower', 0, '5uuAXM5Bb58xcg32nyteRR1qpwcMQAutd2JAEx0zi54Sb8x9Lvz38qwwHsda', '2025-12-22 10:11:02', '2026-02-03 05:07:58', 'male', '1997-06-03', NULL, 'Kenya', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Eric Odhiambo', 'ericodhiambo@gmail.com', '0718664393', 'Dog trainer', 'Brother', 'national_id', '28472342', 'signatures/signature_Douglas_Lutomia.png', 'national_id', '33555254', 'id-documents/6VgExuInfnnT9taPNmQejLcRNq8HZxI450CiisF9.jpg', 'id-documents/m4c2eTCLo1Uj6RZeQ6UyzfFXORYImZLSZY09VDLH.jpg'),
(63, 'Morris Hafare Segelan', '', '0716893824', 'morrishafare@gmail.com', NULL, '$2y$12$XYydzEfQvPnIIxNRdbQPeexqnbjdr2/.P4wXT0FFcmK/F7FOBCMuS', 'borrower', 0, NULL, '2025-12-22 10:31:24', '2025-12-22 10:34:20', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Morris_Hafare_Segelan.png', NULL, NULL, NULL, NULL),
(64, 'Judy Kerebi', '', '0741 960 917', 'judykerebi23@gmail.com', NULL, '$2y$12$KS9qsb9J9YPaFwvJc34SMesmXKyRq17SaL8gnzN0H/Q/trFazHWhu', 'borrower', 1, 'bmdTBflv37S49rppyYH4VM30PeNdc4nO8aKFdHvsyNWQ72ULwFMsXpj71TmB', '2026-01-20 09:03:36', '2026-04-11 09:41:21', 'female', '1999-02-09', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Farida Kebaso', 'faridakebaso1@gmail.com', '+254718534504', 'Business', 'Business Partner', 'national_id', '42191432', 'signatures/signature_Judy_Kerebi.png', 'national_id', '36138998', 'id-documents/kuPygKRuhL6oj6jqkF69AQeDwy4b69DykWwx4nUg.jpg', 'id-documents/xtp2kK9k157Np83ZMRQ2unxYSdzPzTmAK82vMycX.jpg'),
(65, 'Hesbon Kiprotich Kerewo', 'avatars/Dw2FhaoXteB6YjHy4fzIxvLxX5me5wLGsm8xT91x.jpg', '0706077705', 'hezkerewo@gmail.com', NULL, '$2y$12$NT4nA1qQn24bsrfJWeykd.gOJYuPwOVL64PMYP7iK7bchPDk.zA8C', 'borrower', 0, 'UO8Fx3w8IPnIAVVJ4ZhjpWnZ7WBKNzeRfOVyH9DYW0KPKCPI1hXeni9Hd7DQ', '2026-01-21 06:27:38', '2026-02-02 13:32:56', 'male', NULL, NULL, NULL, 'married', 'Christian', 0, 'Bachelor\'s Degree', 'Tony Kimosop', NULL, '0794864738', 'Business man', 'Brother', 'national_id', '30317319', 'signatures/signature_Hesbon_Kiprotich_Kerewo.png', 'National_Id', '30319319', 'id-documents/gb4dwWWVxiSqhSCvqdYvuJRwx7JUK3oxjzqhCE2F.png', 'id-documents/BDiGmMGsxQHGk6K9aCEELSqEepQFNxMkA5CIKlIT.png'),
(66, 'Jacob', '', '0791250828', 'jacobgimachombe@gmail.com', NULL, '$2y$12$sDuHMFS3QUNPrqY6frCOlOT0BmlvsCjEhO2B0Za4mfAGGtp5VF4RK', 'borrower', 0, NULL, '2026-01-21 07:15:44', '2026-01-21 07:35:13', 'male', NULL, NULL, NULL, 'married', 'Christian', 0, 'Bachelor\'s Degree', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'signatures/signature_Jacob.png', NULL, NULL, NULL, NULL),
(67, 'Michael Nzuka', NULL, '0700742393', 'musyiminzuka3@gmail.com', NULL, '$2y$12$TyQr75ASmFeVbqXtdtCvIepyt45m2U4UXrpOF7v71/VgSoSmYtYGC', 'borrower', 0, NULL, '2026-02-18 08:27:11', '2026-02-18 08:50:25', 'male', '1994-11-04', NULL, 'Kenya', 'single', 'Christianity', 0, 'Master\'s Degree', 'Grace Musyimi', 'gracemusyimi72@gmail.com', '+254716815488', 'Policies and Governance', 'Sister', 'national_id', '35658056', 'signatures/signature_Michael_Nzuka.png', 'National ID', '31529341', NULL, NULL),
(68, 'Deborah', NULL, '0791733405', 'deborahmurgor7@gmail.com', NULL, '$2y$12$aAEBH17kgQP9w6dCjtev/eHuQtWNAlUX6YwMsGXTo556LoPkJpLBq', 'borrower', 0, NULL, '2026-02-18 08:56:28', '2026-02-18 08:56:28', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 'Ronald Kemei', NULL, '+254714995946', 'kemeirnld@gmail.com', NULL, '$2y$12$euP7hNWhVfQ3m7Fr4vGAXuiL7REKowJlxqZKLmC2iDkzCFQZCvmvW', 'borrower', 1, 'xOcQhyfWId9jkeLqjmj8cg2w8AT4vYth4dCJ34a9h9lh8zNZoBYrzzyJOEMd', '2026-02-18 14:51:36', '2026-02-19 10:09:08', 'male', '1990-05-12', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Bethwel Kiprono', 'felixmaru@yahoo.com', '+254 790 819905', 'Business/freelancer', 'Business Partner', 'national_id', '29480845', 'signatures/signature_Ronald_Kemei.png', 'national_id', '29762505', 'id-documents/zMn7oIMMtSc1CSuwDDc2vunirYzWFYWbaz2XKuR4.jpg', 'id-documents/hJetnNYA1fjKx321pOBe7eUA0TEuUCAq7zazvI0b.jpg'),
(70, 'Nigel Kimutai', NULL, '0725408209', 'nigelkimutai@gmail.com', NULL, '$2y$12$tRL3hw45tFpda00vVb4xB.vdQIG3kn9LoDmGfZVB67/1TQ3v9DFjK', 'borrower', 1, NULL, '2026-02-23 13:47:01', '2026-02-23 14:09:18', 'male', '1994-03-23', NULL, 'KE', 'single', NULL, 0, NULL, 'Prisca Choge', 'priscachoge@gmail.com', '0720978776', 'Business', 'Mother', 'national_id', '30953061', 'signatures/signature_Nigel_Kimutai.png', 'national_id', '30953061', 'id-documents/1MNy81qcXk0NFSHa1lWdKQoupc6qrj877ecKALMV.jpg', 'id-documents/BoT1TvQ4J39KP2Y2CjT6oz3QqT75L8CwgmazR1Qa.jpg'),
(71, 'Yvonne Jemutai', NULL, '0701986999', 'yvonnejemutai3967@gmail.com', NULL, '$2y$12$dxZg4KvdGlZyZnB.nQIIl.JPwe34GxL3dxbXHKZIjKOyUmbrqtNj6', 'borrower', 0, NULL, '2026-03-06 04:57:07', '2026-03-07 11:53:44', 'female', '1996-10-28', NULL, 'KE', 'single', 'Christian', 0, 'Bachelor\'s Degree', 'Vicky Jelagat', 'vickyjelagat@gmail.com', '254757869890', 'Pharmacist', 'Sister', 'national_id', '38086262', 'signatures/signature_Yvonne_Jemutai.png', 'national_id', '33246104', 'id-documents/FECg9du15dXA9E1rgjetCVbap6wPmPJRZqKpONHb.jpg', 'id-documents/nGLk6DhAwWnc5HXZkamQsShN0C5HduhVCrlwobfK.jpg'),
(72, 'Teresa waitherero Ndirangu', NULL, '0745878281', 'teresawaitherero@gmail.com', NULL, '$2y$12$O8X6ZphiU339Q7iGcq5YC.58i9gm8hVAKC/L.YPOccS/yT9CEAecC', 'borrower', 0, NULL, '2026-03-16 09:02:56', '2026-03-16 09:02:56', NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admins_user_id_foreign` (`user_id`);

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
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disbursements_loan_id_foreign` (`loan_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loans_user_id_foreign` (`user_id`),
  ADD KEY `loans_loan_type_id_foreign` (`loan_type_id`),
  ADD KEY `fk_loans_guarantor` (`guarantor_id`),
  ADD KEY `fk_loans_officer` (`loan_officer_id`);

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
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `repayments`
--
ALTER TABLE `repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repayments_loan_id_foreign` (`loan_id`);

--
-- Indexes for table `repayment_overflows`
--
ALTER TABLE `repayment_overflows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repayment_overflows_from_loan_id_foreign` (`from_loan_id`),
  ADD KEY `repayment_overflows_to_loan_id_foreign` (`to_loan_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `brokers`
--
ALTER TABLE `brokers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=423;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=390;

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
-- AUTO_INCREMENT for table `loan_risk_assessments`
--
ALTER TABLE `loan_risk_assessments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_types`
--
ALTER TABLE `loan_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `repayments`
--
ALTER TABLE `repayments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=558;

--
-- AUTO_INCREMENT for table `repayment_overflows`
--
ALTER TABLE `repayment_overflows`
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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_guarantor` FOREIGN KEY (`guarantor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_loans_officer` FOREIGN KEY (`loan_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `loans_loan_type_id_foreign` FOREIGN KEY (`loan_type_id`) REFERENCES `loan_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_agreement_sections`
--
ALTER TABLE `loan_agreement_sections`
  ADD CONSTRAINT `fk_agreement_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_risk_assessments`
--
ALTER TABLE `loan_risk_assessments`
  ADD CONSTRAINT `fk_risk_assessor` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_risk_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `repayments`
--
ALTER TABLE `repayments`
  ADD CONSTRAINT `repayments_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `repayment_overflows`
--
ALTER TABLE `repayment_overflows`
  ADD CONSTRAINT `repayment_overflows_from_loan_id_foreign` FOREIGN KEY (`from_loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repayment_overflows_to_loan_id_foreign` FOREIGN KEY (`to_loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tellers`
--
ALTER TABLE `tellers`
  ADD CONSTRAINT `tellers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
