-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2026 at 11:45 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `panda_payroll`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(60) NOT NULL,
  `record_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-19 14:40:38'),
(2, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-19 17:07:10'),
(3, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-19 17:15:01'),
(4, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 11:19:39'),
(5, 1, 'create', 'users', NULL, 'Added user: kelum', '::1', '2026-05-20 15:03:30'),
(6, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-05-20 15:05:14'),
(7, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-05-20 15:05:23'),
(8, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-05-20 15:06:32'),
(9, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-05-20 15:06:40'),
(10, 3, 'login', 'users', 3, 'User logged in', '::1', '2026-05-20 15:08:42'),
(11, 3, 'logout', 'users', 3, 'User logged out', '::1', '2026-05-20 15:08:47'),
(12, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 15:37:57'),
(13, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 15:38:03'),
(14, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 15:38:13'),
(15, 1, 'create', 'users', NULL, 'Added user: kelum', '::1', '2026-05-20 15:38:32'),
(16, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 15:38:50'),
(17, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 15:38:58'),
(18, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 15:39:16'),
(19, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 15:39:24'),
(20, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 15:39:31'),
(21, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 15:39:40'),
(22, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 15:39:49'),
(23, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 15:40:09'),
(24, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 15:40:58'),
(25, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 15:41:06'),
(26, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:03:06'),
(27, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:03:26'),
(28, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:03:31'),
(29, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:03:38'),
(30, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:05:36'),
(31, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:05:53'),
(32, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:06:25'),
(33, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:06:42'),
(34, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:07:33'),
(35, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:07:41'),
(36, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:11:06'),
(37, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:11:14'),
(38, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:15:25'),
(39, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:15:36'),
(40, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:16:59'),
(41, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:17:07'),
(42, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:17:13'),
(43, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:17:21'),
(44, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:20:47'),
(45, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:20:58'),
(46, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:22:01'),
(47, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-20 16:22:21'),
(48, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-20 16:24:19'),
(49, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:24:30'),
(50, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:25:02'),
(51, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:29:15'),
(52, 1, 'create', 'users', NULL, 'Added user: rasika', '::1', '2026-05-20 16:29:59'),
(53, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:30:09'),
(54, 5, 'login', 'users', 5, 'User logged in', '::1', '2026-05-20 16:30:19'),
(55, 5, 'logout', 'users', 5, 'User logged out', '::1', '2026-05-20 16:36:27'),
(56, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-20 16:36:51'),
(57, 1, 'create', 'employees', NULL, 'Added tharu', '::1', '2026-05-20 16:38:26'),
(58, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-20 16:39:02'),
(59, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-21 08:32:12'),
(60, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-21 08:32:19'),
(61, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-21 08:32:28'),
(62, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-21 08:32:49'),
(63, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-21 08:32:56'),
(64, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-22 09:39:16'),
(65, 1, 'logout', 'users', 1, 'User logged out', '::1', '2026-05-22 10:36:57'),
(66, 4, 'login', 'users', 4, 'User logged in', '::1', '2026-05-22 10:37:10'),
(67, 4, 'logout', 'users', 4, 'User logged out', '::1', '2026-05-22 10:40:55'),
(68, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-22 10:41:10'),
(69, 1, 'login', 'users', 1, 'User logged in', '::1', '2026-05-22 12:24:33');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`) VALUES
(1, 'Production', '2026-05-19 11:11:19'),
(2, 'Packing', '2026-05-19 11:11:19'),
(3, 'Quality Control', '2026-05-19 11:11:19'),
(4, 'Warehouse', '2026-05-19 11:11:19'),
(5, 'Administration', '2026-05-19 11:11:19');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `emp_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `nic` varchar(20) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `join_date` date NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `designation` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `emergency_name` varchar(150) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `emergency_relation` varchar(60) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `emp_code`, `full_name`, `nic`, `gender`, `date_of_birth`, `join_date`, `department_id`, `designation`, `phone`, `email`, `address`, `bank_name`, `bank_branch`, `account_number`, `emergency_name`, `emergency_phone`, `emergency_relation`, `photo`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'EMP-001', 'Duleepa', '12345678', 'male', '2014-02-20', '2026-05-04', 5, 'Manager', '07723625589', 'duleepa@gmail.com', 'Morrontota', '', '', '', '', '', '', '3b75fafcd484a226.png', 1, 1, '2026-05-19 18:22:34', '2026-05-19 22:08:04'),
(2, 'EMP-002', 'Kumara', '785556622333', 'male', '2019-07-11', '2026-05-04', 1, 'Developer', '0774589222', '', 'Atugoda', '', '', '', '', '', '', NULL, 1, 1, '2026-05-19 18:23:57', '2026-05-19 18:23:57'),
(3, 'EMP-003', 'W.R.M.S.P.BANDARA', '953660016V', 'male', '1995-12-31', '2026-01-01', 1, 'Assistant', '0715143132', '', 'Panana,menikkadawara', 'Boc', 'Nelundeniya', '', '', '', '', NULL, 1, 1, '2026-05-20 06:32:19', '2026-05-20 09:41:42'),
(4, 'EMP-004', 'M.A.K.Chandima', '721461076V', 'male', '1972-05-25', '2026-01-01', 1, 'Assistant', '0775970864', '', 'Dewalegama', 'BOC', 'Nelundeniya', '', '', '', '', NULL, 1, 1, '2026-05-20 08:16:13', '2026-05-20 08:16:13'),
(5, 'EMP-005', 'D.S.L.Udawaththa', '621713345V', 'male', '1962-06-19', '2026-01-01', 1, 'Assistant', '0763969843', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 08:50:12', '2026-05-20 08:50:12'),
(6, 'EMP-006', 'P.P.J.S.L.P.Kumara', '921402864V', 'male', '1992-05-19', '2026-01-01', 1, 'Assistant', '0704898184', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 08:52:42', '2026-05-20 08:57:23'),
(7, 'EMP-007', 'G.T.K.Kumara', '913510690V', 'male', '1991-12-16', '2026-01-01', 1, 'Assistant', '0779491289', '', 'Imbulowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 08:56:08', '2026-05-20 08:56:08'),
(8, 'EMP-008', 'W.A.P.Chandraruwan', '811590843V', 'male', '1981-06-07', '2026-01-01', 1, 'Assistant', '0772638091', '', 'Imbulowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:02:29', '2026-05-20 09:02:29'),
(9, 'EMP-009', 'P.K.H.Wickramasinghe', '200310600333', 'male', '2003-04-15', '2026-01-01', 1, 'Assistant', '0754301095', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:04:43', '2026-05-20 09:04:43'),
(10, 'EMP-010', 'S.P.K.G.Wikramasinghe', '200213503770', 'male', '2002-05-14', '2026-01-01', 1, 'Assistant', '0769234934', '', 'imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:07:27', '2026-05-20 09:07:27'),
(11, 'EMP-011', 'V.H.P.L.Dissanayake', '20031614005', 'male', '2003-06-09', '2026-01-01', 1, 'Assistant', '0775108096', '', 'imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:09:34', '2026-05-20 09:17:51'),
(12, 'EMP-012', 'B.P.K.N.Batuwahtha', '806122742V', 'female', '1985-07-16', '2026-01-01', 1, 'Assistant', '0774665977', '', 'Imbulowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:12:08', '2026-05-20 09:15:44'),
(13, 'EMP-013', 'I.A.M.V.Dayananda', '197457503345V', 'female', '1974-03-15', '2026-01-01', 1, 'Assistant', '072737152', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:14:51', '2026-05-20 09:14:51'),
(14, 'EMP-014', 'H.R.P.Gunathilaka', '851980440V', 'male', '1985-07-05', '2026-01-01', 1, 'Assistant', '0775108096', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:36:59', '2026-05-20 09:36:59'),
(15, 'EMP-015', 'K.D.S.M.Minipura', '912692140V', 'male', '1993-09-25', '2026-01-01', 1, 'Assistant', '0704665977', '', 'Imbullowita', '', '', '', '', '', '', NULL, 1, 1, '2026-05-20 09:40:06', '2026-05-20 09:40:06');


-- --------------------------------------------------------

--
-- Table structure for table `payroll_summaries`
--

CREATE TABLE `payroll_summaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `payroll_year` smallint(5) UNSIGNED NOT NULL,
  `payroll_month` tinyint(3) UNSIGNED NOT NULL,
  `days_worked` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `days_leave` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `total_production` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_ot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_day_duty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_travelling` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_other` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_by` int(10) UNSIGNED DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `target_weekday` smallint(5) UNSIGNED NOT NULL DEFAULT 65,
  `target_saturday` smallint(5) UNSIGNED NOT NULL DEFAULT 33,
  `rate_above` decimal(8,2) NOT NULL DEFAULT 40.00,
  `rate_below` decimal(8,2) NOT NULL DEFAULT 15.00,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `target_weekday`, `target_saturday`, `rate_above`, `rate_below`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Panda 100', 65, 33, 40.00, 15.00, 1, 1, '2026-05-17 15:36:36', '2026-05-17 15:36:36'),
(2, 'Leafy', 70, 35, 40.00, 15.00, 2, 1, '2026-05-17 15:36:36', '2026-05-17 15:36:36'),
(3, 'Panda 50', 65, 33, 40.00, 15.00, 3, 1, '2026-05-17 15:36:36', '2026-05-17 15:36:36'),
(4, 'Softfeel', 75, 38, 40.00, 15.00, 4, 1, '2026-05-17 15:36:36', '2026-05-17 15:36:36'),
(5, 'Elegant', 80, 40, 40.00, 15.00, 5, 1, '2026-05-19 22:17:20', '2026-05-19 22:17:41'),
(6, 'Toilet Role (P)', 25, 13, 70.00, 40.00, 7, 1, '2026-05-20 08:47:07', '2026-05-20 08:48:33'),
(7, 'Toilet Role (T)', 50, 25, 50.00, 30.00, 6, 1, '2026-05-20 08:47:52', '2026-05-20 08:48:14');

-- --------------------------------------------------------

--
-- Table structure for table `timecards`
--

CREATE TABLE `timecards` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `status` enum('work','leave','off','holiday') NOT NULL DEFAULT 'work',
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `ot_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `day_duty_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `travelling` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `updated_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timecard_products`
--

CREATE TABLE `timecard_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `timecard_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '4de93544234adffbb681ed60ffcfb941', 'System Administrator', 'admin', 1, '2026-05-19 11:11:19', '2026-05-20 15:02:43'),
(2, 'payroll ', '83eca6a1fb73bcd7f8e4ca336c160d1a', 'Payroll Assistant', 'Payroll Staff', 1, '2026-05-20 11:48:53', '2026-05-20 11:48:53'),
(4, 'kelum', '25d55ad283aa400af464c76d713c07ad', 'kelum Bandara', 'payroll_staff', 1, '2026-05-20 15:38:32', '2026-05-20 15:38:32'),
(5, 'rasika', '25d55ad283aa400af464c76d713c07ad', 'rakila bandara', 'payroll_staff', 1, '2026-05-20 16:29:58', '2026-05-20 16:29:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_code` (`emp_code`),
  ADD UNIQUE KEY `nic` (`nic`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payroll_summaries`
--
ALTER TABLE `payroll_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_month` (`employee_id`,`payroll_year`,`payroll_month`),
  ADD KEY `locked_by` (`locked_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `timecards`
--
ALTER TABLE `timecards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_emp_date` (`employee_id`,`work_date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `timecard_products`
--
ALTER TABLE `timecard_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tc_prod` (`timecard_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_summaries`
--
ALTER TABLE `payroll_summaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timecards`
--
ALTER TABLE `timecards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timecard_products`
--
ALTER TABLE `timecard_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
