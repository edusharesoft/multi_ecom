-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 01, 2026 at 10:01 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `multibranch_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `account_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `methods` json DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `branch_id`, `name`, `type`, `account_number`, `bank_name`, `methods`, `opening_balance`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 4, 'Cash', 'cash', NULL, NULL, '[\"cash\", \"cod\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:58:50'),
(2, 4, 'Bank', 'bank', NULL, NULL, '[\"card\", \"bank\", \"cheque\", \"shatajpay\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:58:50'),
(3, 4, 'bKash', 'mobile', NULL, NULL, '[\"bkash\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:58:50'),
(4, 4, 'Nagad', 'mobile', NULL, NULL, '[\"nagad\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:58:50'),
(5, 4, 'Rocket', 'mobile', NULL, NULL, '[\"rocket\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:58:50'),
(6, 1, 'Cash', 'cash', NULL, NULL, '[\"cash\", \"cod\"]', 0.00, 1, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(7, 1, 'Bank', 'bank', NULL, NULL, '[\"card\", \"bank\", \"cheque\", \"shatajpay\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(8, 1, 'bKash', 'mobile', NULL, NULL, '[\"bkash\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(9, 1, 'Nagad', 'mobile', NULL, NULL, '[\"nagad\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(10, 1, 'Rocket', 'mobile', NULL, NULL, '[\"rocket\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(11, 2, 'Cash', 'cash', NULL, NULL, '[\"cash\", \"cod\"]', 0.00, 1, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(12, 2, 'Bank', 'bank', NULL, NULL, '[\"card\", \"bank\", \"cheque\", \"shatajpay\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(13, 2, 'bKash', 'mobile', NULL, NULL, '[\"bkash\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(14, 2, 'Nagad', 'mobile', NULL, NULL, '[\"nagad\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(15, 2, 'Rocket', 'mobile', NULL, NULL, '[\"rocket\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(16, 3, 'Cash', 'cash', NULL, NULL, '[\"cash\", \"cod\"]', 0.00, 1, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(17, 3, 'Bank', 'bank', NULL, NULL, '[\"card\", \"bank\", \"cheque\", \"shatajpay\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(18, 3, 'bKash', 'mobile', NULL, NULL, '[\"bkash\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(19, 3, 'Nagad', 'mobile', NULL, NULL, '[\"nagad\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(20, 3, 'Rocket', 'mobile', NULL, NULL, '[\"rocket\"]', 0.00, 0, 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(21, 4, 'Lubaba', 'mobile', '01676680455', 'bKash', '[\"cash\"]', 10000.00, 1, 1, '2026-09-23 09:58:50', '2026-09-23 09:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `account_categories`
--

CREATE TABLE `account_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_categories`
--

INSERT INTO `account_categories` (`id`, `branch_id`, `kind`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 4, 'expense', 'Rent', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(2, 4, 'expense', 'Salary', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(3, 4, 'expense', 'Electricity & Utility', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(4, 4, 'expense', 'Internet & Phone', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(5, 4, 'expense', 'Transport & Delivery', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(6, 4, 'expense', 'Marketing & Ads', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(7, 4, 'expense', 'Packaging', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(8, 4, 'expense', 'Repair & Maintenance', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(9, 4, 'expense', 'Office Supplies', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(10, 4, 'expense', 'Bank & Gateway Charges', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(11, 4, 'expense', 'Subscription Fee', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(12, 4, 'expense', 'Tax & VAT Paid', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(13, 4, 'expense', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(14, 4, 'income', 'Service Income', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(15, 4, 'income', 'Commission Received', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(16, 4, 'income', 'Interest', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(17, 4, 'income', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(18, 1, 'expense', 'Rent', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(19, 1, 'expense', 'Salary', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(20, 1, 'expense', 'Electricity & Utility', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(21, 1, 'expense', 'Internet & Phone', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(22, 1, 'expense', 'Transport & Delivery', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(23, 1, 'expense', 'Marketing & Ads', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(24, 1, 'expense', 'Packaging', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(25, 1, 'expense', 'Repair & Maintenance', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(26, 1, 'expense', 'Office Supplies', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(27, 1, 'expense', 'Bank & Gateway Charges', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(28, 1, 'expense', 'Subscription Fee', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(29, 1, 'expense', 'Tax & VAT Paid', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(30, 1, 'expense', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(31, 1, 'income', 'Service Income', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(32, 1, 'income', 'Commission Received', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(33, 1, 'income', 'Interest', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(34, 1, 'income', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(35, 2, 'expense', 'Rent', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(36, 2, 'expense', 'Salary', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(37, 2, 'expense', 'Electricity & Utility', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(38, 2, 'expense', 'Internet & Phone', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(39, 2, 'expense', 'Transport & Delivery', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(40, 2, 'expense', 'Marketing & Ads', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(41, 2, 'expense', 'Packaging', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(42, 2, 'expense', 'Repair & Maintenance', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(43, 2, 'expense', 'Office Supplies', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(44, 2, 'expense', 'Bank & Gateway Charges', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(45, 2, 'expense', 'Subscription Fee', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(46, 2, 'expense', 'Tax & VAT Paid', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(47, 2, 'expense', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(48, 2, 'income', 'Service Income', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(49, 2, 'income', 'Commission Received', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(50, 2, 'income', 'Interest', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(51, 2, 'income', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(52, 3, 'expense', 'Rent', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(53, 3, 'expense', 'Salary', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(54, 3, 'expense', 'Electricity & Utility', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(55, 3, 'expense', 'Internet & Phone', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(56, 3, 'expense', 'Transport & Delivery', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(57, 3, 'expense', 'Marketing & Ads', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(58, 3, 'expense', 'Packaging', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(59, 3, 'expense', 'Repair & Maintenance', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(60, 3, 'expense', 'Office Supplies', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(61, 3, 'expense', 'Bank & Gateway Charges', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(62, 3, 'expense', 'Subscription Fee', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(63, 3, 'expense', 'Tax & VAT Paid', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(64, 3, 'expense', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(65, 3, 'income', 'Service Income', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(66, 3, 'income', 'Commission Received', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(67, 3, 'income', 'Interest', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54'),
(68, 3, 'income', 'Others', 1, '2026-09-23 09:56:54', '2026-09-23 09:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `account_transactions`
--

CREATE TABLE `account_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `account_id` bigint UNSIGNED NOT NULL,
  `txn_date` date NOT NULL,
  `direction` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `account_transactions`
--

INSERT INTO `account_transactions` (`id`, `branch_id`, `account_id`, `txn_date`, `direction`, `amount`, `category`, `reference_type`, `reference_id`, `description`, `actor_name`, `created_at`, `updated_at`) VALUES
(1, 4, 21, '2026-09-24', 'in', 1100.00, 'sale', 'App\\Models\\Payment', 1, 'Sale ORD-260924-3BKJWG', 'Sonya Rahman', '2026-09-23 22:11:31', '2026-09-23 22:11:31'),
(2, 4, 2, '2026-09-30', 'in', 1260.00, 'sale', 'App\\Models\\Payment', 3, 'Sale ORD-260930-PSEPKM', 'System', '2026-09-30 05:04:19', '2026-09-30 05:04:19');

-- --------------------------------------------------------

--
-- Table structure for table `account_transfers`
--

CREATE TABLE `account_transfers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `from_account_id` bigint UNSIGNED NOT NULL,
  `to_account_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `transfer_date` date NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `actor_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_id` bigint UNSIGNED DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `branch_id`, `actor_type`, `actor_id`, `actor_name`, `action`, `description`, `subject_type`, `subject_id`, `properties`, `ip`, `user_agent`, `created_at`) VALUES
(1, NULL, 'admin', 1, 'Super Admin', 'plan.updated', 'Plan updated: Free', 'App\\Models\\Plan', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 07:58:08'),
(2, NULL, 'admin', 1, 'Super Admin', 'plan.updated', 'Plan updated: Professional', 'App\\Models\\Plan', 3, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 07:58:29'),
(3, 4, 'admin', 1, 'Super Admin', 'admin.impersonate', 'Super Admin opened branch backend: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 07:58:55'),
(4, 4, 'admin', 1, 'Super Admin', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 08:00:33'),
(5, 4, 'owner', 4, 'Sonya Rahman', 'pos.opened', 'POS register opened (Main Warehouse) with ৳ 1000', 'App\\Models\\PosSession', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 09:36:09'),
(6, 4, 'owner', 4, 'Sonya Rahman', 'account.created', 'Account added: Lubaba', 'App\\Models\\Account', 21, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-23 09:58:50'),
(7, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 21:59:35'),
(8, 4, 'owner', 4, 'Sonya Rahman', 'employee.created', 'Employee added: Masud Rahman', 'App\\Models\\Employee', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 22:20:25'),
(9, 4, 'owner', 4, 'Sonya Rahman', 'payroll.generated', 'Payroll 2026-07 generated', 'App\\Models\\Payroll', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 22:21:36'),
(10, 4, 'owner', 4, 'Sonya Rahman', 'product.updated', 'Product updated: White Female Outfit for Kids', 'App\\Models\\Product', 26, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-23 23:12:58'),
(11, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-24 04:17:26'),
(12, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 04:18:33'),
(13, 4, 'owner', 4, 'Sonya Rahman', 'post.created', 'Blog post: অনলাইনে ব্যবসায় অধিক লাভ', 'App\\Models\\Post', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 05:02:27'),
(14, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 08:51:57'),
(15, 4, 'owner', 4, 'Sonya Rahman', 'settings.updated', 'Store settings updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:12:05'),
(16, 4, 'owner', 4, 'Sonya Rahman', 'settings.updated', 'Store settings updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:37:10'),
(17, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:38:05'),
(18, 4, 'owner', 4, 'Sonya Rahman', 'settings.updated', 'Store settings updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 09:39:38'),
(19, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 20:59:02'),
(20, NULL, 'admin', 2, 'Super Admin', 'home_slider.added', 'Home slider image added', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-24 21:29:59'),
(21, NULL, 'admin', 2, 'Super Admin', 'home_slider.added', 'Home slider image added', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-24 21:30:22'),
(22, NULL, 'admin', 2, 'Super Admin', 'home_slider.added', 'Home slider image added', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-24 21:30:34'),
(23, 4, 'owner', 4, 'Sonya Rahman', 'settings.updated', 'Store settings updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 21:48:45'),
(24, 4, 'owner', 4, 'Sonya Rahman', 'invoice.created', 'Invoice INV-2609-0001 (Professional, monthly) ৳ 1499.00', 'App\\Models\\Invoice', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 21:58:40'),
(25, 4, 'owner', 4, 'Sonya Rahman', 'subscription.activated', 'Professional (monthly) active until 07 Nov 2026', 'App\\Models\\Subscription', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 21:59:00'),
(26, 4, 'owner', 4, 'Sonya Rahman', 'invoice.paid', 'Invoice INV-2609-0001 paid via shatajpay', 'App\\Models\\Invoice', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 21:59:00'),
(27, 4, 'owner', 4, 'Sonya Rahman', 'page.created', 'Page: Return & Refund Policy', 'App\\Models\\Page', 1, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 22:01:16'),
(28, 4, 'owner', 4, 'Sonya Rahman', 'page.created', 'Page: About Us', 'App\\Models\\Page', 2, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 22:01:29'),
(29, 4, 'admin', 2, 'Super Admin', 'admin.impersonate', 'Super Admin opened branch backend: সফট লিমিটেড', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-24 22:23:50'),
(30, 4, 'owner', 4, 'Sonya Rahman', 'website.theme', 'Store theme updated', NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', '2026-09-24 22:35:34'),
(31, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-PSEPKM (৳ 1260.00)', 'App\\Models\\Order', 2, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:04:09'),
(32, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-RQEWYT (৳ 2460.00)', 'App\\Models\\Order', 3, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:20:52'),
(33, 4, 'owner', 4, 'Sonya Rahman', 'auth.login', 'Owner login: Sonya Rahman', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 05:22:29'),
(34, 4, 'owner', 4, 'Sonya Rahman', 'settings.shatajpay_updated', 'Own ShatajPay account updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 05:24:46'),
(35, 4, 'owner', 4, 'Sonya Rahman', 'settings.shatajpay_updated', 'Own ShatajPay account updated', 'App\\Models\\Branch', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', '2026-09-30 05:25:29'),
(36, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-AP2Z6M (৳ 1260.00)', 'App\\Models\\Order', 4, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:26:20'),
(37, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-1FTV14 (৳ 410.00)', 'App\\Models\\Order', 5, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:27:12'),
(38, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-U9FBX6 (৳ 410.00)', 'App\\Models\\Order', 6, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:30:29'),
(39, 4, 'admin', 1, 'Super Admin', 'order.created', 'New order ORD-260930-FUDAEH (৳ 1160.00)', 'App\\Models\\Order', 7, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', '2026-09-30 05:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `ai_logs`
--

CREATE TABLE `ai_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `feature` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `input_tokens` int UNSIGNED NOT NULL DEFAULT '0',
  `output_tokens` int UNSIGNED NOT NULL DEFAULT '0',
  `success` tinyint(1) NOT NULL DEFAULT '1',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_ms` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_logs`
--

INSERT INTO `ai_logs` (`id`, `branch_id`, `feature`, `provider`, `model`, `input_tokens`, `output_tokens`, `success`, `error`, `duration_ms`, `created_at`) VALUES
(1, NULL, 'test', 'gemini', 'gemini-2.5-flash', 0, 0, 0, 'HTTP 404 This model models/gemini-2.5-flash is no longer available to new users. Please update your code to use models/gemini-3.6-flash for the latest features and improvements. We recommend you to use the Interactions API (https://ai.google.dev/gemi...', 2869, '2026-09-23 22:43:59'),
(2, 4, 'blog', 'gemini', 'gemini-2.5-flash', 0, 0, 0, 'HTTP 404 This model models/gemini-2.5-flash is no longer available to new users. Please update your code to use models/gemini-3.6-flash for the latest features and improvements. We recommend you to use the Interactions API (https://ai.google.dev/gemi...', 2508, '2026-09-24 04:59:08'),
(3, 4, 'insights', 'gemini', 'gemini-2.5-flash', 0, 0, 0, 'HTTP 404 This model models/gemini-2.5-flash is no longer available to new users. Please update your code to use models/gemini-3.6-flash for the latest features and improvements. We recommend you to use the Interactions API (https://ai.google.dev/gemi...', 845, '2026-09-24 05:06:39');

-- --------------------------------------------------------

--
-- Table structure for table `api_tokens`
--

CREATE TABLE `api_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` json DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_notifications`
--

CREATE TABLE `app_notifications` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_notifications`
--

INSERT INTO `app_notifications` (`id`, `branch_id`, `type`, `title`, `body`, `url`, `level`, `read_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'invoice.paid', 'পেমেন্ট সফল — সাবস্ক্রিপশন চালু', 'Professional (monthly) মেয়াদ 07 Nov 2026', 'http://127.0.0.1:8000/manager/billing', 'success', NULL, '2026-09-24 21:59:00', '2026-09-24 21:59:00'),
(2, NULL, 'invoice.paid', 'Payment received: সফট লিমিটেড', '৳ 1499.00 — INV-2609-0001', 'http://127.0.0.1:8000/admin/invoices', 'success', NULL, '2026-09-24 21:59:01', '2026-09-24 21:59:01'),
(3, 4, 'order.new', 'নতুন অর্ডার ORD-260930-PSEPKM', 'Mqasdud — ৳ 1,260.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-PSEPKM', 'success', NULL, '2026-09-30 05:04:09', '2026-09-30 05:04:09'),
(4, 4, 'order.new', 'নতুন অর্ডার ORD-260930-RQEWYT', 'Mqasdud — ৳ 2,460.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-RQEWYT', 'success', NULL, '2026-09-30 05:20:52', '2026-09-30 05:20:52'),
(5, 4, 'stock.low', 'স্টক কম: Blue Panjabi for Kids', 'বর্তমান স্টক 4 (সর্বনিম্ন 5)', 'http://127.0.0.1:8000/manager/products/25/edit', 'warning', NULL, '2026-09-30 05:20:53', '2026-09-30 05:20:53'),
(6, 4, 'order.new', 'নতুন অর্ডার ORD-260930-AP2Z6M', 'Mqasdud — ৳ 1,260.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-AP2Z6M', 'success', NULL, '2026-09-30 05:26:20', '2026-09-30 05:26:20'),
(7, 4, 'order.new', 'নতুন অর্ডার ORD-260930-1FTV14', 'Test — ৳ 410.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-1FTV14', 'success', NULL, '2026-09-30 05:27:12', '2026-09-30 05:27:12'),
(8, 4, 'order.new', 'নতুন অর্ডার ORD-260930-U9FBX6', 'Test — ৳ 410.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-U9FBX6', 'success', NULL, '2026-09-30 05:30:29', '2026-09-30 05:30:29'),
(9, 4, 'order.new', 'নতুন অর্ডার ORD-260930-FUDAEH', 'Test — ৳ 1,160.00', 'http://127.0.0.1:8000/manager/orders/ORD-260930-FUDAEH', 'success', NULL, '2026-09-30 05:36:39', '2026-09-30 05:36:39'),
(10, 4, 'stock.low', 'স্টক কম: Blue Panjabi for Kids', 'বর্তমান স্টক 2 (সর্বনিম্ন 5)', 'http://127.0.0.1:8000/manager/products/25/edit', 'warning', NULL, '2026-09-30 05:36:40', '2026-09-30 05:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `branch_id`, `employee_id`, `date`, `status`, `check_in`, `check_out`, `overtime_hours`, `note`, `actor_name`, `created_at`, `updated_at`) VALUES
(1, 4, 1, '2026-09-24', 'present', '10:20:00', NULL, 0.00, NULL, 'Sonya Rahman', '2026-09-23 22:21:01', '2026-09-23 22:21:01');

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(110) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `branch_id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 4, 'ICT News', 'ict-news', '2026-09-24 04:57:19', '2026-09-24 04:57:19');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_verified_at` timestamp NULL DEFAULT NULL,
  `onboarded_at` timestamp NULL DEFAULT NULL,
  `trial_used_at` timestamp NULL DEFAULT NULL,
  `storage_used` bigint UNSIGNED NOT NULL DEFAULT '0',
  `otp_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_sent_at` timestamp NULL DEFAULT NULL,
  `otp_attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `store_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspend_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` text COLLATE utf8mb4_unicode_ci,
  `store_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_hours` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `map_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bkash_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nagad_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cod_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `shatajpay_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT '60.00',
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `options` json DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `mobile`, `email`, `slug`, `address`, `password`, `mobile_verified_at`, `onboarded_at`, `trial_used_at`, `storage_used`, `otp_code`, `otp_expires_at`, `otp_sent_at`, `otp_attempts`, `store_name`, `status`, `suspended_at`, `suspend_reason`, `logo`, `favicon`, `banner`, `hero_title`, `hero_subtitle`, `about`, `store_phone`, `opening_hours`, `map_url`, `facebook_url`, `bkash_number`, `nagad_number`, `cod_enabled`, `shatajpay_enabled`, `shipping_charge`, `tax_percent`, `options`, `remember_token`, `created_at`, `updated_at`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`) VALUES
(1, 'Barishal Branch Manager', '01711000001', 'barishal@example.com', 'barishal', 'Sadar Road, Barishal', '$2y$12$ofQat9iLdi5R/9etoo8uFOEq0fiECE.FECCI4GZs0/J9WfZ.sEeOO', '2026-09-23 02:41:29', '2026-09-23 07:50:19', '2026-09-23 07:50:19', 0, NULL, NULL, NULL, 0, 'Barishal Branch', 'active', NULL, NULL, NULL, NULL, NULL, 'Barishal Branch', 'Enjoy flat 20% discount on all products', 'Welcome to Barishal Branch. Quality fashion for the whole family.', '01711000001', '10:00 AM – 10:00 PM', NULL, NULL, '01811000001', '01811000001', 1, 1, 60.00, 0.00, NULL, NULL, '2026-09-23 02:35:51', '2026-09-23 07:50:19', NULL, NULL, NULL),
(2, 'Bhola Branch Manager', '01711000002', 'bhola@example.com', 'bhola', 'Sadar Road, Bhola', '$2y$12$HVF1CaOicERb0cwT1cBNSuW7vmx3CL7EVJ9773Frh0NIOPvSM0kzy', '2026-09-23 02:41:30', '2026-09-23 07:50:19', '2026-09-23 07:50:19', 0, NULL, NULL, NULL, 0, 'Bhola Branch', 'active', NULL, NULL, NULL, NULL, NULL, 'Bhola Branch', 'Enjoy flat 20% discount on all products', 'Welcome to Bhola Branch. Quality fashion for the whole family.', '01711000002', '10:00 AM – 10:00 PM', NULL, NULL, '01811000002', '01811000002', 1, 1, 60.00, 0.00, NULL, NULL, '2026-09-23 02:35:52', '2026-09-23 07:50:19', NULL, NULL, NULL),
(3, 'Jhalokathi Branch Manager', '01711000003', 'jhalokathi@example.com', 'jhalokathi', 'Kalibari Road, Jhalokathi', '$2y$12$QFjf2uY06mF7Lbi8DgQezeUIjk2UrQQfPIRmvViQMeY2Ml3i2TcQ2', '2026-09-23 02:41:30', '2026-09-23 07:50:19', '2026-09-23 07:50:19', 0, NULL, NULL, NULL, 0, 'Jhalokathi Branch', 'active', NULL, NULL, NULL, NULL, NULL, 'Jhalokathi Branch', 'Enjoy flat 20% discount on all products', 'Welcome to Jhalokathi Branch. Quality fashion for the whole family.', '01711000003', '10:00 AM – 10:00 PM', NULL, NULL, NULL, NULL, 1, 1, 60.00, 0.00, NULL, NULL, '2026-09-23 02:35:53', '2026-09-23 07:50:19', NULL, NULL, NULL),
(4, 'Sonya Rahman', '01676680455', 'softltdbd@gmail.com', 'soft', 'House-9/31, Mirpur-13/B, Dhaka 1216', '$2y$12$EFZEidCjmu4cS9KOxBaNRO5DltQ0vjPRyFh2plObhFV.YijwPNJlm', '2026-09-23 03:02:23', '2026-09-23 07:50:19', '2026-09-23 07:50:19', 954677, NULL, NULL, '2026-09-23 02:48:11', 1, 'সফট লিমিটেড', 'active', NULL, NULL, 'branches/4/s6tfQA3Y4HurG9wi5Q5e9hFkSuvlPVCcWhgrQj1j.png', 'branches/4/kLclXYGOeLVkslbnWZq132PkzdZd00th4FxmrnVM.png', 'branches/4/tEjB9uugEk1PMjn5oMtar7lijaTavJU5K5rL1a9R.jpg', NULL, NULL, NULL, '01676680455', NULL, 'https://maps.app.goo.gl/DMRYg3ryQXz4wXqZ8', NULL, NULL, NULL, 1, 1, 60.00, 0.00, '{\"menu\": [], \"theme\": {\"font\": \"Poppins\", \"radius\": \"12\", \"tiktok\": \"https://notebook.google.com/\", \"primary\": \"#e41e26\", \"tagline\": \"Style for Every Style | Shop Now at সফট লিমিটেড\", \"youtube\": \"https://www.youtube.com/watch?v=OquLxlLWldw\", \"whatsapp\": \"https://notebook.google.com/\", \"instagram\": null, \"footer_about\": \"এই বাস্তবতা বিবেচনায় শাহতাজ সফট লিমিটেডের পরিকল্পনা, প্রযুক্তিগত দক্ষতা ও সহযোগিতায় \\\"DNCC Green Corridor & Smart Urban Landscape Program\\\" নামে একটি সমন্বিত, প্রযুক্তিনির্ভর ও ভবিষ্যতমুখী নগর উন্নয়ন কর্মসূচির প্রস্তাবনা উপস্থাপন করা হচ্ছে\", \"footer_links\": [{\"url\": \"https://notebook.google.com/\", \"label\": \"নোটবুক এলএম\"}, {\"url\": \"https://notebook.google.com/\", \"label\": \"নোটবুক এলএম\"}, {\"url\": \"https://notebook.google.com/\", \"label\": \"নোটবুক এলএম\"}, {\"url\": \"https://notebook.google.com/\", \"label\": \"নোটবুক এলএম\"}], \"announcement_bg\": \"#111827\", \"footer_show_pay\": true, \"show_newsletter\": true, \"footer_copyright\": null, \"announcement_link\": null, \"announcement_text\": null, \"footer_show_tiles\": true, \"show_all_branches\": true}, \"return_days\": \"7\", \"invoice_note\": \"ধন্যবাদ! আবার আসবেন।\", \"default_locale\": null, \"points_per_100\": \"1\", \"high_value_amount\": \"20000\", \"review_auto_approve\": true, \"allow_guest_checkout\": true}', NULL, '2026-09-23 02:48:10', '2026-09-24 22:35:34', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `branch_id`, `name`, `slug`, `logo`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Chandrabindu', 'chandrabindu', NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(2, 1, 'Aarong Style', 'aarong-style', NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(3, 1, 'Deshi Dosh', 'deshi-dosh', NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(4, 2, 'Chandrabindu', 'chandrabindu', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(5, 2, 'Aarong Style', 'aarong-style', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(6, 2, 'Deshi Dosh', 'deshi-dosh', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(7, 3, 'Chandrabindu', 'chandrabindu', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(8, 3, 'Aarong Style', 'aarong-style', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(9, 3, 'Deshi Dosh', 'deshi-dosh', NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(10, 4, 'Chandrabindu', 'chandrabindu', NULL, 1, '2026-09-23 09:00:59', '2026-09-23 09:00:59'),
(11, 4, 'Aarong Style', 'aarong-style', NULL, 1, '2026-09-23 09:00:59', '2026-09-23 09:00:59'),
(12, 4, 'Deshi Dosh', 'deshi-dosh', NULL, 1, '2026-09-23 09:00:59', '2026-09-23 09:00:59');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `custom_numbers` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `total` int UNSIGNED NOT NULL DEFAULT '0',
  `sent` int UNSIGNED NOT NULL DEFAULT '0',
  `failed` int UNSIGNED NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `campaign_recipients`
--

CREATE TABLE `campaign_recipients` (
  `id` bigint UNSIGNED NOT NULL,
  `campaign_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `to` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `tagline`, `image`, `sort`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Men\'s Fashion', 'mens-fashion', 'Stylish collection', 'categories/B1X0QbX8CzEXqApnVciZgMu2prkZmuhLG4UkSytz.png', 0, '2026-09-23 02:35:51', '2026-09-23 06:06:17'),
(2, NULL, 'Women\'s Fashion', 'womens-fashion', 'Latest trends', NULL, 1, '2026-09-23 02:35:51', '2026-09-23 02:35:51'),
(3, NULL, 'Kids Fashion', 'kids-fashion', 'Cute & comfortable', NULL, 2, '2026-09-23 02:35:51', '2026-09-23 02:35:51'),
(4, NULL, 'Accessories', 'accessories', 'Complete your look', NULL, 3, '2026-09-23 02:35:51', '2026-09-23 02:35:51'),
(5, NULL, 'Lifestyle', 'lifestyle', 'Home & more', NULL, 4, '2026-09-23 02:35:51', '2026-09-23 02:35:51'),
(6, 2, 'Saree', 'saree', NULL, NULL, 0, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(7, 2, 'Salwar Kameez', 'salwar-kameez', NULL, NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(8, 2, 'Kurti & Tops', 'kurti-tops', NULL, NULL, 2, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(9, 1, 'Panjabi', 'panjabi', NULL, NULL, 0, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(10, 1, 'Shirt', 'shirt', NULL, NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(11, 1, 'T-Shirt', 't-shirt', NULL, NULL, 2, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(12, 3, 'Boys', 'boys', NULL, NULL, 0, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(13, 3, 'Girls', 'girls', NULL, NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_discount` decimal(10,2) DEFAULT NULL,
  `scope` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `product_ids` json DEFAULT NULL,
  `category_ids` json DEFAULT NULL,
  `first_order_only` tinyint(1) NOT NULL DEFAULT '0',
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `usage_limit` int UNSIGNED DEFAULT NULL,
  `per_customer_limit` int UNSIGNED DEFAULT NULL,
  `used_count` int UNSIGNED NOT NULL DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `branch_id`, `code`, `description`, `type`, `value`, `min_order`, `max_discount`, `scope`, `product_ids`, `category_ids`, `first_order_only`, `customer_id`, `usage_limit`, `per_customer_limit`, `used_count`, `starts_at`, `expires_at`, `is_active`, `created_at`, `updated_at`) VALUES
(1, NULL, 'WELCOME10', 'Platform welcome offer — first order', 'percent', 10.00, 500.00, 300.00, 'all', NULL, NULL, 1, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(2, 1, 'SAVE100', '৳100 off on ৳1500+', 'fixed', 100.00, 1500.00, NULL, 'all', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-09-23 09:00:57', '2026-09-23 09:00:57'),
(3, 2, 'SAVE100', '৳100 off on ৳1500+', 'fixed', 100.00, 1500.00, NULL, 'all', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(4, 3, 'SAVE100', '৳100 off on ৳1500+', 'fixed', 100.00, 1500.00, NULL, 'all', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-09-23 09:00:58', '2026-09-23 09:00:58'),
(5, 4, 'SAVE100', '৳100 off on ৳1500+', 'fixed', 100.00, 1500.00, NULL, 'all', NULL, NULL, 0, NULL, NULL, NULL, 0, NULL, NULL, 1, '2026-09-23 09:00:59', '2026-09-23 09:00:59');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usages`
--

CREATE TABLE `coupon_usages` (
  `id` bigint UNSIGNED NOT NULL,
  `coupon_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courier_accounts`
--

CREATE TABLE `courier_accounts` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `courier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credentials` text COLLATE utf8mb4_unicode_ci,
  `mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `webhook_token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `wallet_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `loyalty_points` int UNSIGNED NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `marketing_opt_out` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `branch_id`, `name`, `phone`, `email`, `password`, `group`, `wallet_balance`, `loyalty_points`, `notes`, `is_active`, `last_login_at`, `remember_token`, `created_at`, `updated_at`, `marketing_opt_out`) VALUES
(1, 1, 'Demo Customer', '01900000001', 'customer@example.com', '$2y$12$wn.mvop4DgpVzjbL4Eh6d.9CDkxxnLRJbg5C2NMhQvpLuJ7oVvJy6', 'general', 0.00, 0, NULL, 1, NULL, NULL, '2026-09-23 09:00:58', '2026-09-23 09:00:58', 0),
(2, 2, 'Demo Customer', '01900000001', 'customer@example.com', '$2y$12$rapICu9KWSarpZ9U4wkIeOdt89d1rqFNqB7vcMXL.H1QsHC5fPJ2W', 'general', 0.00, 0, NULL, 1, NULL, NULL, '2026-09-23 09:00:58', '2026-09-23 09:00:58', 0),
(3, 3, 'Demo Customer', '01900000001', 'customer@example.com', '$2y$12$O1o2r6qjoaiYi6l5kis/eOL6PRqN.Zqe9iw5R7wDkf5ooOE5RowJO', 'general', 0.00, 0, NULL, 1, NULL, NULL, '2026-09-23 09:00:59', '2026-09-23 09:00:59', 0),
(4, 4, 'Demo Customer', '01900000001', 'customer@example.com', '$2y$12$0.NV0f09/k5B8NDtyfUR9OT0FaB9GNSY.PZcsVftl0xjt2cVutS.K', 'general', 0.00, 0, NULL, 1, NULL, NULL, '2026-09-23 09:00:59', '2026-09-23 09:00:59', 0);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `label` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Home',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upazila` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `domains`
--

CREATE TABLE `domains` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `host` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verify_token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `redirect_to_domain` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `check_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `staff_id` bigint UNSIGNED DEFAULT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `salary_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `basic_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allowance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `branch_id`, `staff_id`, `code`, `name`, `phone`, `email`, `designation`, `department`, `join_date`, `salary_type`, `basic_salary`, `allowance`, `address`, `emergency_contact`, `photo`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, '421', 'Masud Rahman', '01730051790', 'allbizhubweb@gmail.com', 'Salas Man', NULL, '2026-07-01', 'monthly', 10000.00, 500.00, 'House-9/31, Mirpur-13/B', 'Lubaba- 01676804545', 'branches/4/employees/isfcg7Tx5iaOemw2xePO95CJnzmorsMkvR9SRniJ.jpg', 1, 'Test this one', '2026-09-23 22:20:25', '2026-09-23 22:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `plan_id` bigint UNSIGNED DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `billing_cycle` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `period_start` timestamp NULL DEFAULT NULL,
  `period_end` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `number`, `branch_id`, `plan_id`, `type`, `billing_cycle`, `amount`, `discount`, `total`, `status`, `payment_method`, `transaction_id`, `sender_number`, `notes`, `due_date`, `paid_at`, `period_start`, `period_end`, `created_at`, `updated_at`) VALUES
(1, 'INV-2609-0001', 4, 3, 'new', 'monthly', 1499.00, 0.00, 1499.00, 'paid', 'shatajpay', 'SPTJASISBIFSU', NULL, NULL, '2026-09-28', '2026-09-24 21:59:00', '2026-10-07 07:50:19', '2026-11-07 07:50:19', '2026-09-24 21:58:40', '2026-09-24 21:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `type` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` smallint UNSIGNED NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `decided_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_logs`
--

CREATE TABLE `message_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `channel` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campaign_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `segments` smallint UNSIGNED NOT NULL DEFAULT '1',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_logs`
--

INSERT INTO `message_logs` (`id`, `branch_id`, `channel`, `to`, `subject`, `body`, `status`, `context`, `campaign_id`, `order_id`, `segments`, `error`, `created_at`) VALUES
(1, 4, 'sms', '01733919791', NULL, 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-PSEPKM (৳1,260) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-PSEPKM - সফট লিমিটেড', 'sent', 'order_placed', NULL, 2, 3, NULL, '2026-09-30 05:04:10'),
(2, 4, 'email', 'masud@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-PSEPKM', 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-PSEPKM (৳1,260) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-PSEPKM - সফট লিমিটেড', 'sent', 'order_placed', NULL, 2, 1, NULL, '2026-09-30 05:04:11'),
(3, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-PSEPKM', 'নতুন অর্ডার ORD-260930-PSEPKM: Mqasdud 01676680455, ৳1,260\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-PSEPKM', 'sent', 'manager_new_order', NULL, 2, 1, NULL, '2026-09-30 05:04:11'),
(4, 4, 'email', 'masud@gmai.com', 'পেমেন্ট পাওয়া গেছে — ORD-260930-PSEPKM', 'ORD-260930-PSEPKM এর ৳1,260 পেমেন্ট পাওয়া গেছে। ধন্যবাদ - সফট লিমিটেড', 'sent', 'payment_received', NULL, 2, 1, NULL, '2026-09-30 05:04:19'),
(5, 4, 'sms', '01733919791', NULL, 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-RQEWYT (৳2,460) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-RQEWYT - সফট লিমিটেড', 'sent', 'order_placed', NULL, 3, 3, NULL, '2026-09-30 05:20:53'),
(6, 4, 'email', 'masud@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-RQEWYT', 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-RQEWYT (৳2,460) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-RQEWYT - সফট লিমিটেড', 'sent', 'order_placed', NULL, 3, 1, NULL, '2026-09-30 05:20:53'),
(7, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-RQEWYT', 'নতুন অর্ডার ORD-260930-RQEWYT: Mqasdud 01676680455, ৳2,460\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-RQEWYT', 'sent', 'manager_new_order', NULL, 3, 1, NULL, '2026-09-30 05:20:53'),
(8, 4, 'sms', '01733919791', NULL, 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-AP2Z6M (৳1,260) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-AP2Z6M - সফট লিমিটেড', 'sent', 'order_placed', NULL, 4, 3, NULL, '2026-09-30 05:26:21'),
(9, 4, 'email', 'masud@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-AP2Z6M', 'প্রিয় Mqasdud, আপনার অর্ডার ORD-260930-AP2Z6M (৳1,260) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-AP2Z6M - সফট লিমিটেড', 'sent', 'order_placed', NULL, 4, 1, NULL, '2026-09-30 05:26:21'),
(10, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-AP2Z6M', 'নতুন অর্ডার ORD-260930-AP2Z6M: Mqasdud 01676680455, ৳1,260\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-AP2Z6M', 'sent', 'manager_new_order', NULL, 4, 1, NULL, '2026-09-30 05:26:21'),
(11, 4, 'sms', '01733919745', NULL, 'প্রিয় Test, আপনার অর্ডার ORD-260930-1FTV14 (৳410) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-1FTV14 - সফট লিমিটেড', 'sent', 'order_placed', NULL, 5, 3, NULL, '2026-09-30 05:27:13'),
(12, 4, 'email', 'tses@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-1FTV14', 'প্রিয় Test, আপনার অর্ডার ORD-260930-1FTV14 (৳410) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-1FTV14 - সফট লিমিটেড', 'sent', 'order_placed', NULL, 5, 1, NULL, '2026-09-30 05:27:13'),
(13, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-1FTV14', 'নতুন অর্ডার ORD-260930-1FTV14: Test 01676680455, ৳410\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-1FTV14', 'sent', 'manager_new_order', NULL, 5, 1, NULL, '2026-09-30 05:27:13'),
(14, 4, 'sms', '01733919745', NULL, 'প্রিয় Test, আপনার অর্ডার ORD-260930-U9FBX6 (৳410) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-U9FBX6 - সফট লিমিটেড', 'sent', 'order_placed', NULL, 6, 3, NULL, '2026-09-30 05:30:30'),
(15, 4, 'email', 'tses@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-U9FBX6', 'প্রিয় Test, আপনার অর্ডার ORD-260930-U9FBX6 (৳410) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-U9FBX6 - সফট লিমিটেড', 'sent', 'order_placed', NULL, 6, 1, NULL, '2026-09-30 05:30:30'),
(16, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-U9FBX6', 'নতুন অর্ডার ORD-260930-U9FBX6: Test 01676680455, ৳410\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-U9FBX6', 'sent', 'manager_new_order', NULL, 6, 1, NULL, '2026-09-30 05:30:30'),
(17, 4, 'sms', '01733919745', NULL, 'প্রিয় Test, আপনার অর্ডার ORD-260930-FUDAEH (৳1,160) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-FUDAEH - সফট লিমিটেড', 'sent', 'order_placed', NULL, 7, 3, NULL, '2026-09-30 05:36:40'),
(18, 4, 'email', 'tses@gmai.com', 'অর্ডার গ্রহণ (কাস্টমার) — ORD-260930-FUDAEH', 'প্রিয় Test, আপনার অর্ডার ORD-260930-FUDAEH (৳1,160) গ্রহণ করা হয়েছে। ট্র্যাক: http://127.0.0.1:8000/soft/track?order_number=ORD-260930-FUDAEH - সফট লিমিটেড', 'sent', 'order_placed', NULL, 7, 1, NULL, '2026-09-30 05:36:40'),
(19, 4, 'email', 'softltdbd@gmail.com', 'নতুন অর্ডার ORD-260930-FUDAEH', 'নতুন অর্ডার ORD-260930-FUDAEH: Test 01676680455, ৳1,160\n\nhttp://127.0.0.1:8000/manager/orders/ORD-260930-FUDAEH', 'sent', 'manager_new_order', NULL, 7, 1, NULL, '2026-09-30 05:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_01_000001_create_branches_table', 1),
(5, '2026_01_01_000002_create_categories_table', 1),
(6, '2026_01_01_000003_create_products_table', 1),
(7, '2026_01_01_000004_create_orders_table', 1),
(8, '2026_01_01_000005_create_settings_and_admin_fields', 1),
(9, '2026_02_01_000001_create_saas_billing_tables', 2),
(10, '2026_02_01_000002_create_staff_roles_logs_tables', 2),
(11, '2026_03_01_000001_ecommerce_catalog_upgrade', 3),
(12, '2026_03_01_000002_customers_coupons_orders_returns', 3),
(13, '2026_04_01_000001_create_inventory_tables', 4),
(14, '2026_04_01_000002_create_supplier_purchase_tables', 4),
(15, '2026_04_01_000003_create_pos_tables', 4),
(16, '2026_05_01_000001_create_accounting_tables', 5),
(17, '2026_06_01_000001_create_courier_marketing_tables', 6),
(18, '2026_07_01_000001_create_website_blog_domain_tables', 7),
(19, '2026_08_01_000001_create_api_2fa_hrm_tables', 8),
(20, '2026_09_01_000001_create_ai_logs_table', 9),
(21, '2026_10_01_000001_add_performance_indexes', 10),
(22, '2026_10_01_000002_add_client_ref_to_orders', 11),
(23, '2026_10_05_000001_create_payment_gateways_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED DEFAULT NULL,
  `pos_session_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `order_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `note` text COLLATE utf8mb4_unicode_ci,
  `subtotal` decimal(12,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `coupon_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `wallet_used` decimal(10,2) NOT NULL DEFAULT '0.00',
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `points_earned` int UNSIGNED NOT NULL DEFAULT '0',
  `points_credited` tinyint(1) NOT NULL DEFAULT '0',
  `commission_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `commission_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `client_ref` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tendered` decimal(12,2) DEFAULT NULL,
  `change_due` decimal(12,2) DEFAULT NULL,
  `staff_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `branch_id`, `warehouse_id`, `pos_session_id`, `customer_id`, `order_number`, `customer_name`, `customer_phone`, `customer_email`, `shipping_address`, `billing_address`, `note`, `subtotal`, `discount`, `coupon_code`, `shipping`, `tax`, `total`, `wallet_used`, `refunded_amount`, `points_earned`, `points_credited`, `commission_percent`, `commission_amount`, `payment_method`, `payment_status`, `status`, `source`, `client_ref`, `transaction_id`, `sender_number`, `paid_at`, `created_at`, `updated_at`, `tendered`, `change_due`, `staff_name`) VALUES
(1, 4, 1, 1, NULL, 'ORD-260924-3BKJWG', 'Walk-in Customer', '-', NULL, 'POS — Main Warehouse', NULL, NULL, 1200.00, 100.00, NULL, 0.00, 0.00, 1100.00, 0.00, 0.00, 0, 0, 0.00, 0.00, 'cash', 'paid', 'completed', 'pos', NULL, NULL, NULL, '2026-09-23 22:11:31', '2026-09-23 22:11:31', '2026-09-23 22:11:31', 1100.00, 0.00, 'Sonya Rahman'),
(2, 4, NULL, NULL, NULL, 'ORD-260930-PSEPKM', 'Mqasdud', '01733919791', 'masud@gmai.com', 'Dhaka', 'Dhaka', NULL, 1200.00, 0.00, NULL, 60.00, 0.00, 1260.00, 0.00, 0.00, 12, 0, 0.00, 0.00, 'shatajpay', 'paid', 'confirmed', 'online', NULL, 'be5c01c9-1f88-45ae-a4d9-a85b7f7649a5', NULL, '2026-09-30 05:04:19', '2026-09-30 05:04:09', '2026-09-30 05:04:19', NULL, NULL, NULL),
(3, 4, NULL, NULL, NULL, 'ORD-260930-RQEWYT', 'Mqasdud', '01733919791', 'masud@gmai.com', 'Dhaka', 'Dhaka', NULL, 2400.00, 0.00, NULL, 60.00, 0.00, 2460.00, 0.00, 0.00, 24, 0, 0.00, 0.00, 'shatajpay', 'unpaid', 'new', 'online', NULL, NULL, NULL, NULL, '2026-09-30 05:20:52', '2026-09-30 05:20:52', NULL, NULL, NULL),
(4, 4, NULL, NULL, NULL, 'ORD-260930-AP2Z6M', 'Mqasdud', '01733919791', 'masud@gmai.com', 'Dhaka', 'Dhaka', NULL, 1200.00, 0.00, NULL, 60.00, 0.00, 1260.00, 0.00, 0.00, 12, 0, 0.00, 0.00, 'shatajpay', 'unpaid', 'new', 'online', NULL, NULL, NULL, NULL, '2026-09-30 05:26:20', '2026-09-30 05:26:20', NULL, NULL, NULL),
(5, 4, NULL, NULL, NULL, 'ORD-260930-1FTV14', 'Test', '01733919745', 'tses@gmai.com', 'Dhaka', 'Dhaka', NULL, 350.00, 0.00, NULL, 60.00, 0.00, 410.00, 0.00, 0.00, 4, 0, 0.00, 0.00, 'shatajpay', 'unpaid', 'new', 'online', NULL, NULL, NULL, NULL, '2026-09-30 05:27:12', '2026-09-30 05:27:12', NULL, NULL, NULL),
(6, 4, NULL, NULL, NULL, 'ORD-260930-U9FBX6', 'Test', '01733919745', 'tses@gmai.com', 'Dhaka', 'Dhaka', NULL, 350.00, 0.00, NULL, 60.00, 0.00, 410.00, 0.00, 0.00, 4, 0, 0.00, 0.00, 'shatajpay', 'unpaid', 'new', 'online', NULL, NULL, NULL, NULL, '2026-09-30 05:30:29', '2026-09-30 05:30:29', NULL, NULL, NULL),
(7, 4, NULL, NULL, NULL, 'ORD-260930-FUDAEH', 'Test', '01733919745', 'tses@gmai.com', 'Dhaka', 'Dhaka', NULL, 1100.00, 0.00, NULL, 60.00, 0.00, 1160.00, 0.00, 0.00, 11, 0, 0.00, 0.00, 'shatajpay', 'unpaid', 'new', 'online', NULL, NULL, NULL, NULL, '2026-09-30 05:36:39', '2026-09-30 05:36:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED DEFAULT NULL,
  `product_variant_id` bigint UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int UNSIGNED NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `returned_qty` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_variant_id`, `product_name`, `variant_name`, `price`, `quantity`, `total`, `created_at`, `updated_at`, `returned_qty`) VALUES
(1, 1, 25, 106, 'Blue Panjabi for Kids', 'Black', 1200.00, 1, 1200.00, '2026-09-23 22:11:31', '2026-09-23 22:11:31', 0),
(2, 2, 25, 106, 'Blue Panjabi for Kids', 'Black', 1200.00, 1, 1200.00, '2026-09-30 05:04:09', '2026-09-30 05:04:09', 0),
(3, 3, 25, 106, 'Blue Panjabi for Kids', 'Black', 1200.00, 2, 2400.00, '2026-09-30 05:20:52', '2026-09-30 05:20:52', 0),
(4, 4, 25, 106, 'Blue Panjabi for Kids', 'Black', 1200.00, 1, 1200.00, '2026-09-30 05:26:20', '2026-09-30 05:26:20', 0),
(5, 5, 26, NULL, 'White Female Outfit for Kids', NULL, 350.00, 1, 350.00, '2026-09-30 05:27:12', '2026-09-30 05:27:12', 0),
(6, 6, 26, NULL, 'White Female Outfit for Kids', NULL, 350.00, 1, 350.00, '2026-09-30 05:30:29', '2026-09-30 05:30:29', 0),
(7, 7, 25, 107, 'Blue Panjabi for Kids', 'Red', 1100.00, 1, 1100.00, '2026-09-30 05:36:39', '2026-09-30 05:36:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_histories`
--

CREATE TABLE `order_status_histories` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_histories`
--

INSERT INTO `order_status_histories` (`id`, `order_id`, `status`, `note`, `actor_name`, `created_at`) VALUES
(1, 1, 'completed', 'POS sale', 'Sonya Rahman', '2026-09-23 22:11:31'),
(2, 2, 'new', 'Order placed', 'Mqasdud', '2026-09-30 05:04:09'),
(3, 3, 'new', 'Order placed', 'Mqasdud', '2026-09-30 05:20:52'),
(4, 4, 'new', 'Order placed', 'Mqasdud', '2026-09-30 05:26:20'),
(5, 5, 'new', 'Order placed', 'Test', '2026-09-30 05:27:12'),
(6, 6, 'new', 'Order placed', 'Test', '2026-09-30 05:30:29'),
(7, 7, 'new', 'Order placed', 'Test', '2026-09-30 05:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `show_in_header` tinyint(1) NOT NULL DEFAULT '0',
  `show_in_footer` tinyint(1) NOT NULL DEFAULT '1',
  `sort` smallint UNSIGNED NOT NULL DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `branch_id`, `title`, `slug`, `content`, `is_published`, `show_in_header`, `show_in_footer`, `sort`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 4, 'Return & Refund Policy', 'return-policy', '<h2>রিটার্ন ও রিফান্ড নীতি</h2><p>পণ্য হাতে পাওয়ার ৭ দিনের মধ্যে অব্যবহৃত অবস্থায় রিটার্ন করা যাবে। রিফান্ড ৩-৭ কর্মদিবসের মধ্যে দেওয়া হয়।</p>', 1, 1, 1, 0, NULL, NULL, '2026-09-24 22:01:16', '2026-09-24 22:01:16'),
(2, 4, 'About Us', 'about-us', '<h2>আমাদের সম্পর্কে</h2><p>আমরা মানসম্মত পণ্য সবচেয়ে ভালো দামে আপনার দোরগোড়ায় পৌঁছে দিই।</p>', 1, 1, 1, 0, NULL, NULL, '2026-09-24 22:01:29', '2026-09-24 22:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `invoice_id` bigint UNSIGNED DEFAULT NULL,
  `gateway` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initiated',
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `invoice_id`, `gateway`, `reference`, `amount`, `currency`, `transaction_id`, `status`, `payload`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'cash', 'ORD-260924-3BKJWG-POS', 1100.00, 'BDT', NULL, 'success', NULL, '2026-09-23 22:11:31', '2026-09-23 22:11:31'),
(2, NULL, 1, 'shatajpay', 'INV-2609-0001-Q1B5J', 1499.00, 'BDT', 'SPTJASISBIFSU', 'success', NULL, '2026-09-24 21:58:46', '2026-09-24 21:59:00'),
(3, 2, NULL, 'shatajpay', '92286fc0-2053-443b-98a4-e56b6029934c', 1260.00, 'BDT', 'be5c01c9-1f88-45ae-a4d9-a85b7f7649a5', 'success', '{\"ref\": \"ORD-260930-PSEPKM\", \"mode\": \"fake\", \"account\": \"env\", \"callback\": {\"at\": \"2026-09-30 11:04:19\", \"data\": {\"status\": \"Complete\", \"orderId\": \"92286fc0-2053-443b-98a4-e56b6029934c\"}, \"http\": 200}}', '2026-09-30 05:04:11', '2026-09-30 05:04:19'),
(4, 3, NULL, 'shatajpay', '263f103c-0e09-4685-a910-ba751282ec13', 2460.00, 'BDT', '95d2aba0-83c6-4452-8629-4d53100801da', 'failed', '{\"ref\": \"ORD-260930-RQEWYT\", \"mode\": \"live\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"platform\"}', '2026-09-30 05:20:53', '2026-09-30 05:20:53'),
(5, 3, NULL, 'shatajpay', 'bfd08892-37e7-401d-bd6a-bf756908a1b1', 2460.00, 'BDT', '71113df9-4d24-4d21-8535-9227eecec5b2', 'failed', '{\"ref\": \"ORD-260930-RQEWYT\", \"mode\": \"live\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"platform\"}', '2026-09-30 05:20:58', '2026-09-30 05:20:58'),
(6, 3, NULL, 'shatajpay', 'ad20adf6-3112-43eb-8f28-7f2b6f77ed11', 2460.00, 'BDT', '7f4e8462-c4ff-4530-9aa1-4bd717762d8a', 'failed', '{\"ref\": \"ORD-260930-RQEWYT\", \"mode\": \"live\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"platform\"}', '2026-09-30 05:21:13', '2026-09-30 05:21:14'),
(7, 4, NULL, 'shatajpay', '4ceece44-d01f-463e-a389-f20fbe088da2', 1260.00, 'BDT', '85bd549c-7e25-49ef-981c-88c34341a61c', 'failed', '{\"ref\": \"ORD-260930-AP2Z6M\", \"mode\": \"sandbox\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"branch\"}', '2026-09-30 05:26:21', '2026-09-30 05:26:22'),
(8, 4, NULL, 'shatajpay', '59cff729-3784-4717-adba-403625b54a02', 1260.00, 'BDT', 'b4d3e014-fa83-4c56-89b1-8c82b7c2d4f9', 'failed', '{\"ref\": \"ORD-260930-AP2Z6M\", \"mode\": \"sandbox\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"branch\"}', '2026-09-30 05:26:25', '2026-09-30 05:26:26'),
(9, 5, NULL, 'shatajpay', 'e2cb5623-0fcd-438e-9c9b-03afb310ceb7', 410.00, 'BDT', 'e3f552f9-d7e3-432b-8562-a948525ab127', 'failed', '{\"ref\": \"ORD-260930-1FTV14\", \"mode\": \"sandbox\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"branch\"}', '2026-09-30 05:27:13', '2026-09-30 05:27:13'),
(10, 5, NULL, 'shatajpay', 'c79ce109-ad90-414d-a8a0-5319744083aa', 410.00, 'BDT', '84375f93-8673-4cf7-af82-0d09f31c7f75', 'failed', '{\"ref\": \"ORD-260930-1FTV14\", \"mode\": \"sandbox\", \"error\": \"cURL error 6: Could not resolve host: sandbox.shatajpay.com (see https://curl.se/libcurl/c/libcurl-errors.html) for https://sandbox.shatajpay.com/api/v1/user/payment-system\", \"account\": \"branch\"}', '2026-09-30 05:27:16', '2026-09-30 05:27:16'),
(11, 6, NULL, 'shatajpay', 'e1d191a4-c64b-40d0-be53-d332155d81ac', 410.00, 'BDT', 'db6d7296-5de8-4761-9c6f-50f3f96aa49d', 'failed', '{\"ref\": \"ORD-260930-U9FBX6\", \"init\": {\"at\": \"2026-09-30 11:30:31\", \"data\": {\"message\": \"Error! Missing or null value for required header: sandbox.\"}, \"http\": 400}, \"mode\": \"sandbox\", \"account\": \"branch\"}', '2026-09-30 05:30:30', '2026-09-30 05:30:31'),
(12, 6, NULL, 'shatajpay', '858bd680-3475-4fd5-a764-0f7fa8abbb5b', 410.00, 'BDT', '2c270495-fe03-48a2-9516-6f790bb4b4d1', 'failed', '{\"ref\": \"ORD-260930-U9FBX6\", \"init\": {\"at\": \"2026-09-30 11:30:35\", \"data\": {\"message\": \"Error! Missing or null value for required header: sandbox.\"}, \"http\": 400}, \"mode\": \"sandbox\", \"account\": \"branch\"}', '2026-09-30 05:30:34', '2026-09-30 05:30:35'),
(13, 6, NULL, 'shatajpay', '6961434c-c76b-45d3-bfc8-1cb99d9943de', 410.00, 'BDT', 'b51cbef9-5bcb-4cd7-aaf7-5f8bd96a6b4c', 'failed', '{\"ref\": \"ORD-260930-U9FBX6\", \"init\": {\"at\": \"2026-09-30 11:30:56\", \"data\": {\"message\": \"Error! Missing or null value for required header: sandbox.\"}, \"http\": 400}, \"mode\": \"sandbox\", \"account\": \"branch\"}', '2026-09-30 05:30:55', '2026-09-30 05:30:56'),
(14, 7, NULL, 'shatajpay', '0108c419-3e51-40c7-af2f-a89fd92dcdfe', 1160.00, 'BDT', '3aebf6ed-a473-40b4-89aa-cd7a9c607b06', 'failed', '{\"ref\": \"ORD-260930-FUDAEH\", \"init\": {\"at\": \"2026-09-30 11:36:41\", \"data\": {\"message\": \"Error! Missing or null value for required header: sandbox.\"}, \"http\": 400}, \"mode\": \"sandbox\", \"account\": \"branch\"}', '2026-09-30 05:36:40', '2026-09-30 05:36:41'),
(15, 7, NULL, 'shatajpay', 'fce3a872-cc83-4e80-8b14-18a63ecd206c', 1160.00, 'BDT', '709013b0-d030-4506-970d-230c60a881ed', 'failed', '{\"ref\": \"ORD-260930-FUDAEH\", \"init\": {\"at\": \"2026-09-30 11:36:45\", \"data\": {\"message\": \"Error! Missing or null value for required header: sandbox.\"}, \"http\": 400}, \"mode\": \"sandbox\", \"account\": \"branch\"}', '2026-09-30 05:36:45', '2026-09-30 05:36:46');

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateways`
--

CREATE TABLE `payment_gateways` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `gateway` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shatajpay',
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_key` text COLLATE utf8mb4_unicode_ci,
  `app_secret` text COLLATE utf8mb4_unicode_ci,
  `app_password` text COLLATE utf8mb4_unicode_ci,
  `app_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `base_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateways`
--

INSERT INTO `payment_gateways` (`id`, `branch_id`, `gateway`, `display_name`, `logo`, `gateway_id`, `app_key`, `app_secret`, `app_password`, `app_username`, `mode`, `base_url`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'shatajpay', 'ShatajPay', NULL, 'sha-0d68c9b9-85a5-4563-a644-c4808ca751c7', 'eyJpdiI6IjYxMWNJVWxwcmYzNktXREpjT3VrQmc9PSIsInZhbHVlIjoiSXlZSTZIc1VmZTJUV3ROdXo4UWhwRFN3U3luNENRcmxhZmF3TTQyQkdmcz0iLCJtYWMiOiJiNTBiODJlZjIxMzQ0YWIzMzY2YmZjYzlmOWRjMWE1NjI3MTVmMzI0OGJlMjkzZTdkODA4MDExODQzMGYwYjMxIiwidGFnIjoiIn0=', 'eyJpdiI6IktOVThQdHltWXhBbGpCcU4wZzJpalE9PSIsInZhbHVlIjoiZDlxYVlXMEJIV0QvNy9ib1hONmw0TTJ2akhyaDNrN3FQYTdSdnhCQ3JMOWxLNlk1TXREbTlBUUx1RDV3TTZnNyIsIm1hYyI6IjhkZmZiN2VjYjdmODkzODQ5NDNmMDNkMTEwZGE2ZjBmYjFkY2IzNTgwMzNhNzgzZmFhYTBlNDg5NDA2NGZjYWYiLCJ0YWciOiIifQ==', 'eyJpdiI6InJPQmwzYkpFaVBiSTcyemlJTmxjakE9PSIsInZhbHVlIjoia3RBYWFQaklkZk8wZFNqZk5zNG5EQUVqNTQwU3BwYnVSMGJnZjNKQWVNbHJ3OGpUMlhhMi9VZEViWDAxd1B4SCIsIm1hYyI6Ijk2NmEyYTI5NzY2NmJhNzM1OWU4YTI5ZDFhOGQyOGE4MDExY2NkZWI5MDYxZDg4Y2ZjY2Q5OGNhYTlkYTg5MzgiLCJ0YWciOiIifQ==', 'softltdlive', 'live', 'https://pay.shataj.com', 1, '2026-09-30 05:16:55', '2026-09-30 05:30:04'),
(2, 4, 'shatajpay', 'ShatajPay', NULL, 'sha-0d68c9b9-85a5-4563-a644-c4808ca751c7', 'eyJpdiI6InFmUzZ5aHZ1eFBtQ2R0b09iYnlMemc9PSIsInZhbHVlIjoidU9TaFR4N3RNSEYvSmtaMndoOHpyS2R4Yk9MMm9vckdLRXN4dFc2REorYz0iLCJtYWMiOiI5YThiYjAzNzUyN2M1ZjhkYzk5Y2FkOGIxOGMxYjFiY2ZkYThmYjg0Yjg3ZTZlYjJlNmU4NzVjZWQyNjRjYzcyIiwidGFnIjoiIn0=', 'eyJpdiI6ImljdGJML084bGZIWTJUUXp6cHFTcFE9PSIsInZhbHVlIjoiTTlkNVV0NldjcUUxNEdrS09KMkYzazk0c3UraVZ2WVpJV3ZTb3VTMFU1ME9CU1Z6R2NZYnJiQUYyWVRMVE5RaSIsIm1hYyI6ImRlNTViMGY4ODkwNzI0ZmYyZGFlNjRkZDIwMmVmZTJmODYyYWI5ZGE2MDRiODcwZGM5MDM3MzJiYjRlZTc2NDUiLCJ0YWciOiIifQ==', 'eyJpdiI6IjZYQnpiSHFxY1dRMVl6c0ViUy9idVE9PSIsInZhbHVlIjoiTXBWWUorU0hFK2xBZ2dZVDhNejNjdlNUelYya3ZDc0RMNUN4cUxWK3VibXlrSXpHTlcvdTdhaUl4eXRyaWxudSIsIm1hYyI6IjNhZjJkNzliOGMxODcwY2FkYmEwOWFjMmFjYmQzZWNjYjVkN2Q1ZWZlZWE1MmUzNGVlNmMxNTU1YWFiNzQyYTAiLCJ0YWciOiIifQ==', 'softltdlive', 'sandbox', NULL, 1, '2026-09-30 05:24:46', '2026-09-30 05:25:29');

-- --------------------------------------------------------

--
-- Table structure for table `payrolls`
--

CREATE TABLE `payrolls` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `month` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `working_days` tinyint UNSIGNED NOT NULL DEFAULT '30',
  `total_net` decimal(14,2) NOT NULL DEFAULT '0.00',
  `paid_at` timestamp NULL DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payrolls`
--

INSERT INTO `payrolls` (`id`, `branch_id`, `month`, `status`, `working_days`, `total_net`, `paid_at`, `actor_name`, `created_at`, `updated_at`) VALUES
(1, 4, '2026-07', 'draft', 26, 10500.00, NULL, 'Sonya Rahman', '2026-09-23 22:21:36', '2026-09-23 22:21:36');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_items`
--

CREATE TABLE `payroll_items` (
  `id` bigint UNSIGNED NOT NULL,
  `payroll_id` bigint UNSIGNED NOT NULL,
  `employee_id` bigint UNSIGNED NOT NULL,
  `present_days` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `absent_days` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `unpaid_leave_days` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `overtime_hours` decimal(6,2) NOT NULL DEFAULT '0.00',
  `basic` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allowance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `overtime_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bonus` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deduction` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_deduction` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net` decimal(12,2) NOT NULL DEFAULT '0.00',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `voucher_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_items`
--

INSERT INTO `payroll_items` (`id`, `payroll_id`, `employee_id`, `present_days`, `absent_days`, `unpaid_leave_days`, `overtime_hours`, `basic`, `allowance`, `overtime_amount`, `bonus`, `deduction`, `other_deduction`, `net`, `note`, `paid_at`, `voucher_number`) VALUES
(1, 1, 1, 0, 0, 0, 0.00, 10000.00, 500.00, 0.00, 0.00, 0.00, 0.00, 10500.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_monthly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_yearly` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_lifetime` decimal(10,2) DEFAULT NULL,
  `trial_days` smallint UNSIGNED NOT NULL DEFAULT '0',
  `commission_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_free` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `limits` json NOT NULL,
  `features` json NOT NULL,
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `slug`, `description`, `price_monthly`, `price_yearly`, `price_lifetime`, `trial_days`, `commission_percent`, `is_free`, `is_active`, `is_featured`, `branch_id`, `limits`, `features`, `sort`, `created_at`, `updated_at`) VALUES
(1, 'Free', 'free', 'শুরু করার জন্য', 0.00, 0.00, NULL, 0, 2.00, 1, 1, 0, NULL, '{\"max_staff\": 0, \"sms_month\": 0, \"storage_mb\": 100, \"max_products\": 10, \"max_customers\": 100, \"max_warehouses\": 1, \"max_orders_month\": 30, \"ai_requests_month\": 0, \"monthly_sales_limit\": 50000}', '{\"ai\": false, \"api\": false, \"crm\": false, \"hrm\": false, \"pos\": false, \"sms\": false, \"blog\": false, \"coupon\": false, \"courier\": false, \"reports\": true, \"whatsapp\": false, \"inventory\": false, \"accounting\": false, \"custom_domain\": false, \"email_marketing\": false, \"advanced_reports\": false, \"advanced_marketing\": false}', 0, '2026-09-23 07:50:19', '2026-09-23 22:39:28'),
(2, 'Basic', 'basic', 'ছোট দোকানের জন্য', 499.00, 4990.00, NULL, 14, 1.00, 0, 1, 0, NULL, '{\"max_staff\": 2, \"sms_month\": 500, \"storage_mb\": 500, \"max_products\": 50, \"max_customers\": 1000, \"max_warehouses\": 1, \"max_orders_month\": 300, \"ai_requests_month\": 30, \"monthly_sales_limit\": -1}', '{\"ai\": true, \"api\": false, \"crm\": false, \"hrm\": false, \"pos\": true, \"sms\": true, \"blog\": true, \"coupon\": false, \"courier\": true, \"reports\": true, \"whatsapp\": false, \"inventory\": false, \"accounting\": false, \"custom_domain\": false, \"email_marketing\": false, \"advanced_reports\": false, \"advanced_marketing\": false}', 1, '2026-09-23 07:50:19', '2026-09-23 22:39:28'),
(3, 'Professional', 'professional', 'বাড়তে থাকা ব্যবসার জন্য', 1499.00, 14990.00, NULL, 14, 0.00, 0, 1, 1, NULL, '{\"max_staff\": 10, \"sms_month\": 3000, \"storage_mb\": 5120, \"max_products\": 1000, \"max_customers\": -1, \"max_warehouses\": 3, \"max_orders_month\": -1, \"ai_requests_month\": 300, \"monthly_sales_limit\": -1}', '{\"ai\": true, \"api\": false, \"crm\": true, \"hrm\": true, \"pos\": true, \"sms\": true, \"blog\": true, \"coupon\": true, \"courier\": true, \"reports\": true, \"whatsapp\": true, \"inventory\": true, \"accounting\": true, \"custom_domain\": false, \"email_marketing\": true, \"advanced_reports\": false, \"advanced_marketing\": false}', 2, '2026-09-23 07:50:19', '2026-09-23 22:39:28'),
(4, 'Enterprise', 'enterprise', 'বড় ব্যবসা ও চেইন শপ', 3999.00, 39990.00, 99990.00, 14, 0.00, 0, 1, 0, NULL, '{\"max_staff\": -1, \"sms_month\": -1, \"storage_mb\": -1, \"max_products\": -1, \"max_customers\": -1, \"max_warehouses\": -1, \"max_orders_month\": -1, \"ai_requests_month\": -1, \"monthly_sales_limit\": -1}', '{\"ai\": true, \"api\": true, \"crm\": true, \"hrm\": true, \"pos\": true, \"sms\": true, \"blog\": true, \"coupon\": true, \"courier\": true, \"reports\": true, \"whatsapp\": true, \"inventory\": true, \"accounting\": true, \"custom_domain\": true, \"email_marketing\": true, \"advanced_reports\": true, \"advanced_marketing\": true}', 3, '2026-09-23 07:50:19', '2026-09-23 22:39:28');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `blog_category_id` bigint UNSIGNED DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(210) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `cover` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `views` int UNSIGNED NOT NULL DEFAULT '0',
  `author_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `branch_id`, `blog_category_id`, `title`, `slug`, `excerpt`, `content`, `cover`, `tags`, `status`, `published_at`, `views`, `author_name`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'অনলাইনে ব্যবসায় অধিক লাভ', 'onlaine-bzbsay-odhik-lav', NULL, '<p>অনলাইনে অধিক লাভজনক ব্যবসা শুরু করতে সঠিক পণ্য নির্বাচন এবং কার্যকর কৌশল অপরিহার্য। কম পুঁজি ও উচ্চ মুনাফার কিছু সেরা অনলাইন ব্যবসার আইডিয়া নিচে দেওয়া হলো:</p><p>লাভজনক অনলাইন ব্যবসার আইডিয়া</p><ol><li><span class=\"ql-ui\"></span><strong>ডিজিটাল পণ্য (Digital Products):</strong> ই-বুক, অনলাইন কোর্স, গ্রাফিক্স ডিজাইন টেমপ্লেট বা সফটওয়্যার বিক্রি। এতে কোনো ইনভেন্টরি বা স্টক রাখার ঝামেলা নেই এবং লাভের হার সবচেয়ে বেশি (প্রায় ৮০-৯৫%)</li><li><span class=\"ql-ui\"></span><strong>পোশাক ও ফ্যাশন এক্সেসরিজ (Apparel &amp; Fashion):</strong> [হিজাব, কসমেটিকস, গহনা বা ট্রেন্ডি টি-শার্ট] খুবই কম পুঁজির পাইকারি বাজার বা বুটিক শপ থেকে সংগ্রহ করে ফেসবুক পেজ বা ইনস্টাগ্রামের মাধ্যমে বিক্রি করা যায়।</li><li><span class=\"ql-ui\"></span><strong>অর্গানিক ও হোমমেড ফুড (Organic &amp; Homemade Food):</strong> খাঁটি মধু, ঘি, ঘরে তৈরি আচার, স্ন্যাকস বা অর্গানিক গ্রোসারি আইটেমের অনলাইনে চাহিদা দিন দিন দ্রুত বাড়ছে।</li><li><span class=\"ql-ui\"></span><strong>ড্রপশিপিং বা রিসেলিং (Dropshipping &amp; Reselling):</strong> নিজের কোনো পণ্য বা গুদাম ছাড়াই তৃতীয় পক্ষের সাপ্লাইয়ারের পণ্য নিজের পেজে বিক্রি করা। অর্ডার পেলে সাপ্লাইয়ার সরাসরি গ্রাহکের কাছে পণ্য পাঠিয়ে দেন।</li></ol><p><br></p><p>অধিক লাভের কার্যকর কৌশল</p><ol><li><span class=\"ql-ui\"></span><strong>নির্দিষ্ট নিশ (Niche) নির্ধারণ:</strong> সবার জন্য সবকিছু বিক্রি না করে একটি নির্দিষ্ট পণ্যের ক্যাটাগরি বা টার্গেট অডিয়েন্স বেছে নিন।</li><li><span class=\"ql-ui\"></span><strong>ডিজিটাল ম্যানেজমেন্ট ব্যবহার:</strong> অর্ডার ও স্টক ঠিক রাখতে আধুনিক ম্যানেজমেন্ট সিস্টেম বা হিসাবি সফটওয়্যার ব্যবহার করুন, যা সময় ও শ্রম বাঁচাবে।</li><li><span class=\"ql-ui\"></span><strong>কাস্টমার রিটেনশন (Customer Retention):</strong> একবার পণ্য বিক্রি শেষ নয়; বরং পুনরায় ক্রয়ের জন্য ডিসকাউন্ট, লয়্যালটি প্রোগ্রাম ও চমৎকার কাস্টমার সার্ভিস নিশ্চিত করুন।</li></ol><p><br></p><p>আপনি কি নির্দিষ্ট কোনো ক্যাটাগরি নিয়ে ভাবছেন, যেমন—<strong>পোশাক</strong>, <strong>খাবার</strong>, নাকি <strong>ডিজিটাল সার্ভিস</strong>? তাহলে আমি সেটির প্রাথমিক খরচ ও বিপণন কৌশল সম্পর্কে বিস্তারিত জানাতে পারব।</p>', 'branches/4/blog/K7ON7gOpEFJdd0NpM5p1JP73X0Yr6A16gtgy57UO.jpg', NULL, 'published', '2026-09-24 10:58:00', 1, 'Sonya Rahman', 'অনলাইনে ব্যবসায় অধিক লাভ', 'অনলাইনে ব্যবসায় অধিক লাভ', '2026-09-24 05:02:27', '2026-09-24 21:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `pos_cash_movements`
--

CREATE TABLE `pos_cash_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `pos_session_id` bigint UNSIGNED NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_holds`
--

CREATE TABLE `pos_holds` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cart` json NOT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_sessions`
--

CREATE TABLE `pos_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED NOT NULL,
  `opened_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `closed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_cash` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expected_cash` decimal(12,2) DEFAULT NULL,
  `counted_cash` decimal(12,2) DEFAULT NULL,
  `difference` decimal(12,2) DEFAULT NULL,
  `summary` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `opened_at` timestamp NOT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pos_sessions`
--

INSERT INTO `pos_sessions` (`id`, `branch_id`, `warehouse_id`, `opened_by`, `closed_by`, `opening_cash`, `expected_cash`, `counted_cash`, `difference`, `summary`, `note`, `opened_at`, `closed_at`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'Sonya Rahman', NULL, 1000.00, NULL, NULL, NULL, NULL, NULL, '2026-09-23 09:36:09', NULL, '2026-09-23 09:36:09', '2026-09-23 09:36:09');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `brand_id` bigint UNSIGNED DEFAULT NULL,
  `subcategory_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'physical',
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_code` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barcode` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `specifications` json DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sale_starts_at` timestamp NULL DEFAULT NULL,
  `sale_ends_at` timestamp NULL DEFAULT NULL,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `stock` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '5',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sold_count` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `branch_id`, `category_id`, `brand_id`, `subcategory_id`, `name`, `slug`, `product_type`, `sku`, `product_code`, `barcode`, `unit`, `description`, `specifications`, `price`, `purchase_price`, `old_price`, `sale_price`, `sale_starts_at`, `sale_ends_at`, `tax_percent`, `stock`, `min_stock`, `image`, `video_url`, `is_featured`, `is_active`, `created_at`, `updated_at`, `meta_title`, `meta_description`, `meta_keywords`, `sold_count`) VALUES
(1, 1, 2, 3, NULL, 'Premium Embroidered 3-Piece Salwar Kameez', 'premium-embroidered-3-piece-salwar-kameez', 'physical', 'SKU-01001', NULL, NULL, 'pcs', 'Premium Embroidered 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 3240.00, 2106.00, 3800.00, 2592.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:51', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(2, 1, 2, 1, NULL, 'Elegant Printed 3-Piece Salwar Kameez', 'elegant-printed-3-piece-salwar-kameez', 'physical', 'SKU-01002', NULL, NULL, 'pcs', 'Elegant Printed 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2520.00, 1638.00, NULL, 2016.00, '2026-09-23 09:00:57', '2026-09-26 09:00:57', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:52', '2026-09-23 09:00:57', NULL, NULL, NULL, 0),
(3, 1, 2, 2, NULL, 'Cotton 3pcs', 'cotton-3pcs', 'physical', 'SKU-01003', NULL, NULL, 'pcs', 'Cotton 3pcs\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2880.00, 1872.00, 3200.00, 2304.00, '2026-09-23 09:00:57', '2026-09-26 09:00:57', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:52', '2026-09-23 09:00:57', NULL, NULL, NULL, 0),
(4, 1, 2, 1, NULL, 'Turquoise Blue Floral Designer Saree', 'turquoise-blue-floral-designer-saree', 'physical', 'SKU-01004', NULL, NULL, 'pcs', 'Turquoise Blue Floral Designer Saree\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1710.00, 1112.00, 2100.00, 1368.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(5, 1, 1, 1, NULL, 'Premium Cotton Panjabi', 'premium-cotton-panjabi', 'physical', 'SKU-01005', NULL, NULL, 'pcs', 'Premium Cotton Panjabi\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2350.00, 1528.00, NULL, 1880.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(6, 1, 1, 2, NULL, 'Slim Fit Casual Shirt', 'slim-fit-casual-shirt', 'physical', 'SKU-01006', NULL, NULL, 'pcs', 'Slim Fit Casual Shirt\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1450.00, 943.00, 1650.00, 1160.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(7, 1, 3, 3, NULL, 'Kids Party Frock', 'kids-party-frock', 'physical', 'SKU-01007', NULL, NULL, 'pcs', 'Kids Party Frock\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1250.00, 813.00, NULL, 1000.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(8, 1, 4, 2, NULL, 'Leather Hand Bag', 'leather-hand-bag', 'physical', 'SKU-01008', NULL, NULL, 'pcs', 'Leather Hand Bag\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1990.00, 1294.00, 2400.00, 1592.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(9, 2, 2, 6, NULL, 'Premium Embroidered 3-Piece Salwar Kameez', 'premium-embroidered-3-piece-salwar-kameez', 'physical', 'SKU-02001', NULL, NULL, 'pcs', 'Premium Embroidered 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 3240.00, 2106.00, 3800.00, 2592.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:52', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(10, 2, 2, 4, NULL, 'Elegant Printed 3-Piece Salwar Kameez', 'elegant-printed-3-piece-salwar-kameez', 'physical', 'SKU-02002', NULL, NULL, 'pcs', 'Elegant Printed 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2520.00, 1638.00, NULL, 2016.00, '2026-09-23 09:00:58', '2026-09-26 09:00:58', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:52', '2026-09-23 09:00:58', NULL, NULL, NULL, 0),
(11, 2, 2, 5, NULL, 'Cotton 3pcs', 'cotton-3pcs', 'physical', 'SKU-02003', NULL, NULL, 'pcs', 'Cotton 3pcs\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2880.00, 1872.00, 3200.00, 2304.00, '2026-09-23 09:00:58', '2026-09-26 09:00:58', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:52', '2026-09-23 09:00:58', NULL, NULL, NULL, 0),
(12, 2, 2, 4, NULL, 'Turquoise Blue Floral Designer Saree', 'turquoise-blue-floral-designer-saree', 'physical', 'SKU-02004', NULL, NULL, 'pcs', 'Turquoise Blue Floral Designer Saree\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1710.00, 1112.00, 2100.00, 1368.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(13, 2, 1, 4, NULL, 'Premium Cotton Panjabi', 'premium-cotton-panjabi', 'physical', 'SKU-02005', NULL, NULL, 'pcs', 'Premium Cotton Panjabi\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2350.00, 1528.00, NULL, 1880.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(14, 2, 1, 5, NULL, 'Slim Fit Casual Shirt', 'slim-fit-casual-shirt', 'physical', 'SKU-02006', NULL, NULL, 'pcs', 'Slim Fit Casual Shirt\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1450.00, 943.00, 1650.00, 1160.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(15, 2, 3, 6, NULL, 'Kids Party Frock', 'kids-party-frock', 'physical', 'SKU-02007', NULL, NULL, 'pcs', 'Kids Party Frock\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1250.00, 813.00, NULL, 1000.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(16, 2, 4, 5, NULL, 'Leather Hand Bag', 'leather-hand-bag', 'physical', 'SKU-02008', NULL, NULL, 'pcs', 'Leather Hand Bag\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1990.00, 1294.00, 2400.00, 1592.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:52', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(17, 3, 2, 9, NULL, 'Premium Embroidered 3-Piece Salwar Kameez', 'premium-embroidered-3-piece-salwar-kameez', 'physical', 'SKU-03001', NULL, NULL, 'pcs', 'Premium Embroidered 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 3240.00, 2106.00, 3800.00, 2592.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:53', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(18, 3, 2, 7, NULL, 'Elegant Printed 3-Piece Salwar Kameez', 'elegant-printed-3-piece-salwar-kameez', 'physical', 'SKU-03002', NULL, NULL, 'pcs', 'Elegant Printed 3-Piece Salwar Kameez\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2520.00, 1638.00, NULL, 2016.00, '2026-09-23 09:00:58', '2026-09-26 09:00:58', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:53', '2026-09-23 09:00:58', NULL, NULL, NULL, 0),
(19, 3, 2, 8, NULL, 'Cotton 3pcs', 'cotton-3pcs', 'physical', 'SKU-03003', NULL, NULL, 'pcs', 'Cotton 3pcs\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2880.00, 1872.00, 3200.00, 2304.00, '2026-09-23 09:00:58', '2026-09-26 09:00:58', 0.00, 20, 5, NULL, NULL, 1, 1, '2026-09-23 02:35:53', '2026-09-23 09:00:58', NULL, NULL, NULL, 0),
(20, 3, 2, 7, NULL, 'Turquoise Blue Floral Designer Saree', 'turquoise-blue-floral-designer-saree', 'physical', 'SKU-03004', NULL, NULL, 'pcs', 'Turquoise Blue Floral Designer Saree\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1710.00, 1112.00, 2100.00, 1368.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:53', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(21, 3, 1, 7, NULL, 'Premium Cotton Panjabi', 'premium-cotton-panjabi', 'physical', 'SKU-03005', NULL, NULL, 'pcs', 'Premium Cotton Panjabi\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 2350.00, 1528.00, NULL, 1880.00, '2026-09-23 09:56:56', '2026-09-26 09:56:56', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:53', '2026-09-23 09:56:56', NULL, NULL, NULL, 0),
(22, 3, 1, 8, NULL, 'Slim Fit Casual Shirt', 'slim-fit-casual-shirt', 'physical', 'SKU-03006', NULL, NULL, 'pcs', 'Slim Fit Casual Shirt\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1450.00, 943.00, 1650.00, 1160.00, '2026-09-23 10:13:06', '2026-09-26 10:13:06', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:53', '2026-09-23 10:13:06', NULL, NULL, NULL, 0),
(23, 3, 3, 9, NULL, 'Kids Party Frock', 'kids-party-frock', 'physical', 'SKU-03007', NULL, NULL, 'pcs', 'Kids Party Frock\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1250.00, 813.00, NULL, 1000.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:53', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(24, 3, 4, 8, NULL, 'Leather Hand Bag', 'leather-hand-bag', 'physical', 'SKU-03008', NULL, NULL, 'pcs', 'Leather Hand Bag\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash', NULL, 1990.00, 1294.00, 2400.00, 1592.00, '2026-09-23 09:35:01', '2026-09-26 09:35:01', 0.00, 20, 5, NULL, NULL, 0, 1, '2026-09-23 02:35:53', '2026-09-23 09:35:01', NULL, NULL, NULL, 0),
(25, 4, 3, 11, NULL, 'Blue Panjabi for Kids', 'blue-panjabi-for-kids', 'physical', 'GP1745', NULL, NULL, 'pcs', 'This is a test products', NULL, 1000.00, 650.00, 1200.00, 800.00, '2026-09-23 09:00:59', '2026-09-26 09:00:59', 0.00, 2, 5, 'products/4/pacuxYNnMjSEAltzuVPN8wx225DXMAqz7psp9LLi.jpg', NULL, 1, 1, '2026-09-23 03:08:13', '2026-09-30 05:36:39', NULL, NULL, NULL, 6),
(26, 4, 3, 10, NULL, 'White Female Outfit for Kids', 'white-female-outfit-for-kids', 'physical', '565AU1', NULL, NULL, 'pcs', 'White simple outfit for toddler girls', NULL, 350.00, 228.00, 400.00, 280.00, '2026-09-23 09:00:00', '2026-09-26 09:00:00', 0.00, 98, 5, 'products/4/zmCzmyjDtEfPRUYhjQmY4rypLb8W7VXF0YFPfs0B.jpg', NULL, 0, 1, '2026-09-23 03:31:35', '2026-09-30 05:30:29', NULL, NULL, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `path`, `sort`, `created_at`, `updated_at`) VALUES
(1, 25, 'products/4/QE2bfDNLIGXS7CxqIDPy67vC8cX0b6EnasHkXiqw.jpg', 1, '2026-09-23 03:08:13', '2026-09-23 03:08:13'),
(2, 25, 'products/4/UVrYyJcrlDB81v3DcReXrB6iPXCSelhqDTSKjrk5.jpg', 2, '2026-09-23 03:08:13', '2026-09-23 03:08:13'),
(3, 25, 'products/4/62RfKTvmZqyB4psIdydVnLehjlrIgd0MRqx1f8aR.jpg', 3, '2026-09-23 03:08:13', '2026-09-23 03:08:13');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `name`, `price`, `stock`, `created_at`, `updated_at`) VALUES
(1, 1, 'S: 34-36 - Maroon', NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(2, 1, 'M: 38 - Maroon', NULL, 5, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(3, 1, 'L: 40 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(4, 1, 'XL: 42 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(5, 1, 'XXL: 44 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(6, 2, 'S: 34-36 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(7, 2, 'M: 38 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(8, 2, 'L: 40 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(9, 2, 'XL: 42 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(10, 2, 'XXL: 44 - Maroon', NULL, 0, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(11, 3, 'S: 34-36 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(12, 3, 'M: 38 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(13, 3, 'L: 40 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(14, 3, 'XL: 42 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(15, 3, 'XXL: 44 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(16, 4, 'S: 34-36 - Maroon', NULL, 5, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(17, 4, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(18, 4, 'L: 40 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(19, 4, 'XL: 42 - Maroon', NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(20, 4, 'XXL: 44 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(21, 5, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(22, 5, 'M: 38 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(23, 5, 'L: 40 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(24, 5, 'XL: 42 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(25, 5, 'XXL: 44 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(26, 6, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(27, 6, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(28, 6, 'L: 40 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(29, 6, 'XL: 42 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(30, 6, 'XXL: 44 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(31, 7, 'S: 34-36 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(32, 7, 'M: 38 - Maroon', NULL, 5, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(33, 7, 'L: 40 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(34, 7, 'XL: 42 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(35, 7, 'XXL: 44 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(36, 9, 'S: 34-36 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(37, 9, 'M: 38 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(38, 9, 'L: 40 - Maroon', NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(39, 9, 'XL: 42 - Maroon', NULL, 5, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(40, 9, 'XXL: 44 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(41, 10, 'S: 34-36 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(42, 10, 'M: 38 - Maroon', NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(43, 10, 'L: 40 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(44, 10, 'XL: 42 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(45, 10, 'XXL: 44 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(46, 11, 'S: 34-36 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(47, 11, 'M: 38 - Maroon', NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(48, 11, 'L: 40 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(49, 11, 'XL: 42 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(50, 11, 'XXL: 44 - Maroon', NULL, 0, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(51, 12, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(52, 12, 'M: 38 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(53, 12, 'L: 40 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(54, 12, 'XL: 42 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(55, 12, 'XXL: 44 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(56, 13, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(57, 13, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(58, 13, 'L: 40 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(59, 13, 'XL: 42 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(60, 13, 'XXL: 44 - Maroon', NULL, 0, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(61, 14, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(62, 14, 'M: 38 - Maroon', NULL, 0, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(63, 14, 'L: 40 - Maroon', NULL, 8, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(64, 14, 'XL: 42 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(65, 14, 'XXL: 44 - Maroon', NULL, 2, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(66, 15, 'S: 34-36 - Maroon', NULL, 7, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(67, 15, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(68, 15, 'L: 40 - Maroon', NULL, 5, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(69, 15, 'XL: 42 - Maroon', NULL, 3, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(70, 15, 'XXL: 44 - Maroon', NULL, 4, '2026-09-23 02:35:52', '2026-09-23 02:35:52'),
(71, 17, 'S: 34-36 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(72, 17, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(73, 17, 'L: 40 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(74, 17, 'XL: 42 - Maroon', NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(75, 17, 'XXL: 44 - Maroon', NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(76, 18, 'S: 34-36 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(77, 18, 'M: 38 - Maroon', NULL, 7, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(78, 18, 'L: 40 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(79, 18, 'XL: 42 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(80, 18, 'XXL: 44 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(81, 19, 'S: 34-36 - Maroon', NULL, 8, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(82, 19, 'M: 38 - Maroon', NULL, 7, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(83, 19, 'L: 40 - Maroon', NULL, 7, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(84, 19, 'XL: 42 - Maroon', NULL, 0, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(85, 19, 'XXL: 44 - Maroon', NULL, 8, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(86, 20, 'S: 34-36 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(87, 20, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(88, 20, 'L: 40 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(89, 20, 'XL: 42 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(90, 20, 'XXL: 44 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(91, 21, 'S: 34-36 - Maroon', NULL, 0, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(92, 21, 'M: 38 - Maroon', NULL, 4, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(93, 21, 'L: 40 - Maroon', NULL, 8, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(94, 21, 'XL: 42 - Maroon', NULL, 5, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(95, 21, 'XXL: 44 - Maroon', NULL, 4, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(96, 22, 'S: 34-36 - Maroon', NULL, 8, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(97, 22, 'M: 38 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(98, 22, 'L: 40 - Maroon', NULL, 5, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(99, 22, 'XL: 42 - Maroon', NULL, 2, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(100, 22, 'XXL: 44 - Maroon', NULL, 8, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(101, 23, 'S: 34-36 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(102, 23, 'M: 38 - Maroon', NULL, 6, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(103, 23, 'L: 40 - Maroon', NULL, 3, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(104, 23, 'XL: 42 - Maroon', NULL, 7, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(105, 23, 'XXL: 44 - Maroon', NULL, 7, '2026-09-23 02:35:53', '2026-09-23 02:35:53'),
(106, 25, 'Black', 1200.00, 0, '2026-09-23 03:08:13', '2026-09-30 05:26:20'),
(107, 25, 'Red', 1100.00, 2, '2026-09-23 03:08:13', '2026-09-30 05:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `shipping` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `returned` decimal(12,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `returned_qty` int NOT NULL DEFAULT '0',
  `unit_cost` decimal(10,2) NOT NULL,
  `total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `return_date` date NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_items`
--

CREATE TABLE `purchase_return_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_return_id` bigint UNSIGNED NOT NULL,
  `purchase_item_id` bigint UNSIGNED NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'return',
  `reason` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `items` json NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `refund_method` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restock` tinyint(1) NOT NULL DEFAULT '1',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reply` text COLLATE utf8mb4_unicode_ci,
  `replied_at` timestamp NULL DEFAULT NULL,
  `reports` int UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `customer_id`, `order_id`, `name`, `rating`, `comment`, `is_verified`, `image`, `is_approved`, `created_at`, `updated_at`, `reply`, `replied_at`, `reports`) VALUES
(1, 1, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(2, 2, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(3, 3, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(4, 4, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(5, 5, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(6, 6, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(7, 7, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(8, 8, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(9, 9, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(10, 10, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(11, 11, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(12, 12, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(13, 13, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(14, 14, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(15, 15, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(16, 16, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:52', '2026-09-23 02:35:52', NULL, NULL, 0),
(17, 17, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(18, 18, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(19, 19, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(20, 20, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(21, 21, NULL, NULL, 'Rahim', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(22, 22, NULL, NULL, 'Sadia', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(23, 23, NULL, NULL, 'Nusrat', 5, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0),
(24, 24, NULL, NULL, 'Karim', 4, 'Good quality product! Delivery was fast.', 0, NULL, 1, '2026-09-23 02:35:53', '2026-09-23 02:35:53', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `branch_id`, `name`, `description`, `permissions`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 1, 'Manager', 'Full access except billing & staff', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.delete\", \"orders.export\", \"orders.print\", \"customers.view\", \"customers.add\", \"customers.edit\", \"customers.delete\", \"customers.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"staff.view\", \"staff.add\", \"staff.edit\", \"settings.view\", \"settings.edit\", \"subscription.view\", \"activity.view\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"accounts.delete\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(2, 1, 'Salesman', 'Orders and products (view)', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(3, 1, 'Cashier', 'Take payments, print invoices', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(4, 1, 'Inventory Manager', 'Products & stock', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(5, 1, 'Accountant', 'Payments & reports', '[\"dashboard.view\", \"orders.view\", \"orders.approve\", \"orders.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"purchases.view\", \"suppliers.view\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(6, 1, 'Delivery Manager', 'Delivery status updates', '[\"dashboard.view\", \"orders.view\", \"orders.edit\", \"orders.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(7, 1, 'Customer Support', 'Customers & order lookups', '[\"dashboard.view\", \"orders.view\", \"customers.view\", \"customers.edit\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(8, 2, 'Manager', 'Full access except billing & staff', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.delete\", \"orders.export\", \"orders.print\", \"customers.view\", \"customers.add\", \"customers.edit\", \"customers.delete\", \"customers.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"staff.view\", \"staff.add\", \"staff.edit\", \"settings.view\", \"settings.edit\", \"subscription.view\", \"activity.view\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"accounts.delete\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(9, 2, 'Salesman', 'Orders and products (view)', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(10, 2, 'Cashier', 'Take payments, print invoices', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(11, 2, 'Inventory Manager', 'Products & stock', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(12, 2, 'Accountant', 'Payments & reports', '[\"dashboard.view\", \"orders.view\", \"orders.approve\", \"orders.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"purchases.view\", \"suppliers.view\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(13, 2, 'Delivery Manager', 'Delivery status updates', '[\"dashboard.view\", \"orders.view\", \"orders.edit\", \"orders.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(14, 2, 'Customer Support', 'Customers & order lookups', '[\"dashboard.view\", \"orders.view\", \"customers.view\", \"customers.edit\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(15, 3, 'Manager', 'Full access except billing & staff', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.delete\", \"orders.export\", \"orders.print\", \"customers.view\", \"customers.add\", \"customers.edit\", \"customers.delete\", \"customers.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"staff.view\", \"staff.add\", \"staff.edit\", \"settings.view\", \"settings.edit\", \"subscription.view\", \"activity.view\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"accounts.delete\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(16, 3, 'Salesman', 'Orders and products (view)', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(17, 3, 'Cashier', 'Take payments, print invoices', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(18, 3, 'Inventory Manager', 'Products & stock', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(19, 3, 'Accountant', 'Payments & reports', '[\"dashboard.view\", \"orders.view\", \"orders.approve\", \"orders.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"purchases.view\", \"suppliers.view\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(20, 3, 'Delivery Manager', 'Delivery status updates', '[\"dashboard.view\", \"orders.view\", \"orders.edit\", \"orders.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(21, 3, 'Customer Support', 'Customers & order lookups', '[\"dashboard.view\", \"orders.view\", \"customers.view\", \"customers.edit\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(22, 4, 'Manager', 'Full access except billing & staff', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.delete\", \"orders.export\", \"orders.print\", \"customers.view\", \"customers.add\", \"customers.edit\", \"customers.delete\", \"customers.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"staff.view\", \"staff.add\", \"staff.edit\", \"settings.view\", \"settings.edit\", \"subscription.view\", \"activity.view\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"accounts.delete\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(23, 4, 'Salesman', 'Orders and products (view)', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(24, 4, 'Cashier', 'Take payments, print invoices', '[\"dashboard.view\", \"products.view\", \"orders.view\", \"orders.edit\", \"orders.approve\", \"orders.print\", \"customers.view\", \"customers.add\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(25, 4, 'Inventory Manager', 'Products & stock', '[\"dashboard.view\", \"products.view\", \"products.add\", \"products.edit\", \"products.delete\", \"products.export\", \"products.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(26, 4, 'Accountant', 'Payments & reports', '[\"dashboard.view\", \"orders.view\", \"orders.approve\", \"orders.export\", \"reports.view\", \"reports.export\", \"reports.print\", \"accounts.view\", \"accounts.add\", \"accounts.edit\", \"purchases.view\", \"suppliers.view\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(27, 4, 'Delivery Manager', 'Delivery status updates', '[\"dashboard.view\", \"orders.view\", \"orders.edit\", \"orders.print\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20'),
(28, 4, 'Customer Support', 'Customers & order lookups', '[\"dashboard.view\", \"orders.view\", \"customers.view\", \"customers.edit\"]', 1, '2026-09-23 07:50:20', '2026-09-23 07:50:20');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('4NwIufFaRsah7h9JiFfG4EKhkGucJX6nmxo1DYbW', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRDV1dnhaWGFjOHBSRmZwbWRsSDJjWjlaQU1qa2xyUjR1VGRUSlVSMSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9tYW5hZ2VyL3N3LmpzIjtzOjU6InJvdXRlIjtzOjE0OiJwd2EubWFuYWdlci5zdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTM6ImxvZ2luX2JyYW5jaF81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjQ7fQ==', 1790767532),
('EhjZJr6meXj37oIAWT9HFkSNshjZoNE04Gjm296g', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', 'YTo3OntzOjY6Il90b2tlbiI7czo0MDoiaWJSWGd2TWRTTTVnUmEwVzZaMnJxMDU4U2JpMjNJc09Yd29LdUFERyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzI6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zb2Z0L3N3LmpzIjtzOjU6InJvdXRlIjtzOjEyOiJzdG9yZS5wd2Euc3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6NDoiY2FydCI7YTowOnt9czo4OiJjdXN0b21lciI7YTo0OntzOjEzOiJjdXN0b21lcl9uYW1lIjtzOjQ6IlRlc3QiO3M6MTQ6ImN1c3RvbWVyX3Bob25lIjtzOjExOiIwMTczMzkxOTc0NSI7czoxNDoiY3VzdG9tZXJfZW1haWwiO3M6MTM6InRzZXNAZ21haS5jb20iO3M6MTY6InNoaXBwaW5nX2FkZHJlc3MiO3M6NToiRGhha2EiO31zOjk6Im15X29yZGVycyI7YTo2OntpOjA7czoxNzoiT1JELTI2MDkzMC1QU0VQS00iO2k6MTtzOjE3OiJPUkQtMjYwOTMwLVJRRVdZVCI7aToyO3M6MTc6Ik9SRC0yNjA5MzAtQVAyWjZNIjtpOjM7czoxNzoiT1JELTI2MDkzMC0xRlRWMTQiO2k6NDtzOjE3OiJPUkQtMjYwOTMwLVU5RkJYNiI7aTo1O3M6MTc6Ik9SRC0yNjA5MzAtRlVEQUVIIjt9fQ==', 1790768260),
('Fma6qSlvurNFq4FOFfarKGxkg2XsQ4d04ge2vVsy', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ2pQckV2Q2RxMk1jSnlXTzhxZ2t6Smd3YUR0M0Z5QUNuV2I0dkFUTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1790766830);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Proyjon Multi User Online Shop', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(2, 'site_phone', '+880 1676680455', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(3, 'site_email', 'proyjonshop@gmail.com', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(4, 'site_address', 'Bangladesh', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(5, 'hero_title', 'Registration Now Sell Now', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(6, 'hero_badge', 'UP TO 30% OFF', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(7, 'promo_title', 'End of Season Sale', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(8, 'promo_text', 'Don\'t miss out on our biggest sale of the year! Up to 70% off on selected items.', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(9, 'facebook_url', NULL, '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(10, 'footer_text', 'Developed by Shataj Soft', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(11, 'hero_image', 'site/7ZGC52cmxNOKVxAja17LojwoBW97euNaCFOLIQ8g.jpg', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(12, 'allow_registration', '1', '2026-09-23 06:03:33', '2026-09-23 06:03:33'),
(13, 'ai_provider', 'gemini', '2026-09-23 22:43:27', '2026-09-23 22:43:27'),
(14, 'ai_model', '', '2026-09-23 22:43:27', '2026-09-23 22:43:27'),
(15, 'ai_api_key', 'eyJpdiI6ImJpUUN5QVNFSzVRemtWSGNSUEdranc9PSIsInZhbHVlIjoiSHZkd0RsUnYwWWlwNXFzL1h6dGwwQitoYUdtbVNEL1VsUW5odHRwMDBmN2Y0T3R5bURRWWtyRStROUk0SDB2RFpWMncya0haWFh3QWtwWHlyVWIrYmc9PSIsIm1hYyI6ImU3ZjFjZWUxYzk1MWFkZmRkMTE3OGYwZTBiNmUyZmVhNThiZDg1MGYyZGY2ZWU5MDRhOWUzNjI4ZDhkYWVhOWIiLCJ0YWciOiIifQ==', '2026-09-23 22:43:27', '2026-09-23 22:43:27'),
(16, 'default_locale', 'en', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(17, 'auth_register_bg', 'site/KUofP8zwWySsf5fytF1Tqy7RuUDNy8mpXekpKZym.jpg', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(18, 'auth_login_video', 'https://www.youtube.com/watch?v=tEkCvDyjqj8', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(19, 'auth_register_video', 'https://www.youtube.com/watch?v=tEkCvDyjqj8', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(20, 'grace_days', '3', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(21, 'expiry_action', 'downgrade', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(22, 'reminder_days', '7,3,1,0', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(23, 'saas_bkash_number', NULL, '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(24, 'saas_nagad_number', NULL, '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(25, 'saas_bank_details', NULL, '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(26, 'sms_notifications', '1', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(27, 'auth_show_video', '1', '2026-09-24 20:50:17', '2026-09-24 20:50:17'),
(28, 'site_logo', 'site/naakaIT5lUrgTytrvnLMnZFxDGCg7KqoXJHukXTo.png', '2026-09-24 20:52:50', '2026-09-24 20:52:50'),
(29, 'auth_login_bg', 'site/knmJ47ZvCU1kBTRMCOlB6r6v1kUJrB3J6ovuqURt.jpg', '2026-09-24 20:52:50', '2026-09-24 20:52:50'),
(30, 'home_slides', '[{\"id\":\"6db7bd36-c8ea-447e-9962-04a3e58580f4\",\"image\":\"site/slides/iGfkgWojR1xrxNaY1letqKPDqoRLeCCvvSLTWhqE.jpg\",\"link\":\"https://www.youtube.com/watch?v=YpIerYN17yg\",\"sort\":1,\"active\":true},{\"id\":\"acad2732-7a32-44d6-854a-5b3958213835\",\"image\":\"site/slides/CGUGig76GrsYMf2B94Vw7eQEpC9VWYOuxoXfA3x3.jpg\",\"link\":\"https://www.youtube.com/watch?v=tEkCvDyjqj8\",\"sort\":2,\"active\":true},{\"id\":\"0bf37369-17b8-46f8-9429-7032e0efcaff\",\"image\":\"site/slides/n2PXKdlHDXm1bAZoyvr2KYrg9GdCmhuODZEGs4J4.jpg\",\"link\":\"https://www.youtube.com/watch?v=tEkCvDyjqj8\",\"sort\":3,\"active\":true}]', '2026-09-24 21:29:59', '2026-09-24 21:30:34'),
(31, 'sms_driver', 'shataj', '2026-09-30 05:18:15', '2026-09-30 05:18:15'),
(32, 'sms_server', '', '2026-09-30 05:18:15', '2026-09-30 05:18:15'),
(33, 'sms_device', '', '2026-09-30 05:18:15', '2026-09-30 05:18:15'),
(34, 'sms_api_key', 'eyJpdiI6ImFUZlhDNThyV0JLSW50elZMT095MGc9PSIsInZhbHVlIjoidEhvZHNXOW45NnhtLy9GbUdob1MrMGZMdmlIZ3EzNUFGZmVEZUx5L0JuVmF1Vjh2OGpxS3VRc3NZbWJ6dUJ4ViIsIm1hYyI6IjZkZjUzMGI4NTVhZTZjZmUwZWE4N2RmYWY3MmYwOTQxYzZjMWNiZDRmOTBkMjcwZDg0ZThkYjc0Zjk1NDk2MWMiLCJ0YWciOiIifQ==', '2026-09-30 05:18:15', '2026-09-30 05:18:15');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `courier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `courier_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consignment_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_code` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status_group` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `cod_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `delivery_charge` decimal(10,2) DEFAULT NULL,
  `weight` decimal(6,2) NOT NULL DEFAULT '0.50',
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_text` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'slider',
  `sort` int UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `branch_id`, `title`, `subtitle`, `image`, `link`, `button_text`, `position`, `sort`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, NULL, 'sliders/4/ZAtjKTZPUP9elwkL5buyLFg0kYfPUXYUYbE7cbBz.jpg', 'https://www.youtube.com/watch?v=OquLxlLWldw', NULL, 'slider', 1, 1, '2026-09-24 21:50:29', '2026-09-24 21:50:29'),
(2, 4, NULL, NULL, 'sliders/4/v3Ll7dcXtxFoVkwWKseD3kggdrvbjfyOG3TUbvZ1.jpg', 'https://www.youtube.com/watch?v=OquLxlLWldw', NULL, 'slider', 2, 1, '2026-09-24 21:50:44', '2026-09-24 21:50:44'),
(3, 4, NULL, NULL, 'sliders/4/3Ehmre9m2kPObSi4T1NmEcjwLZD5CeZ7eo3vPVdJ.jpg', 'https://www.youtube.com/watch?v=OquLxlLWldw', NULL, 'slider', 3, 1, '2026-09-24 21:50:55', '2026-09-24 21:50:55'),
(4, 4, NULL, NULL, 'sliders/4/0M3dEqFWRsEGkiBnW0pQCXOr0JGQq00Ylz9PqNja.jpg', 'https://www.youtube.com/watch?v=OquLxlLWldw', NULL, 'banner', 5, 1, '2026-09-24 21:51:13', '2026-09-24 21:51:13');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustment_items`
--

CREATE TABLE `stock_adjustment_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_adjustment_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `balance_after` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `branch_id`, `warehouse_id`, `product_id`, `variant_id`, `type`, `quantity`, `balance_after`, `unit_cost`, `reference_type`, `reference_id`, `note`, `actor_name`, `created_at`) VALUES
(1, 4, 1, 25, 106, 'opening', 5, 5, 650.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(2, 4, 1, 25, 107, 'opening', 3, 3, 650.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(3, 1, 2, 3, 11, 'opening', 4, 4, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(4, 1, 2, 3, 12, 'opening', 3, 3, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(5, 1, 2, 3, 13, 'opening', 8, 8, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(6, 1, 2, 3, 14, 'opening', 2, 2, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(7, 1, 2, 3, 15, 'opening', 2, 2, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(8, 1, 2, 2, 6, 'opening', 7, 7, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(9, 1, 2, 2, 7, 'opening', 4, 4, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(10, 1, 2, 2, 8, 'opening', 6, 6, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(11, 1, 2, 2, 9, 'opening', 4, 4, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(12, 1, 2, 7, 31, 'opening', 2, 2, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(13, 1, 2, 7, 32, 'opening', 5, 5, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(14, 1, 2, 7, 33, 'opening', 6, 6, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(15, 1, 2, 7, 34, 'opening', 4, 4, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(16, 1, 2, 7, 35, 'opening', 4, 4, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(17, 1, 2, 8, 0, 'opening', 20, 20, 1294.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(18, 1, 2, 5, 21, 'opening', 3, 3, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(19, 1, 2, 5, 22, 'opening', 8, 8, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(20, 1, 2, 5, 23, 'opening', 6, 6, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(21, 1, 2, 5, 24, 'opening', 2, 2, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(22, 1, 2, 5, 25, 'opening', 7, 7, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(23, 1, 2, 1, 1, 'opening', 1, 1, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(24, 1, 2, 1, 2, 'opening', 5, 5, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(25, 1, 2, 1, 3, 'opening', 8, 8, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(26, 1, 2, 1, 4, 'opening', 3, 3, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(27, 1, 2, 1, 5, 'opening', 8, 8, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(28, 1, 2, 6, 26, 'opening', 3, 3, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(29, 1, 2, 6, 27, 'opening', 6, 6, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(30, 1, 2, 6, 28, 'opening', 8, 8, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(31, 1, 2, 6, 29, 'opening', 2, 2, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(32, 1, 2, 6, 30, 'opening', 7, 7, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(33, 1, 2, 4, 16, 'opening', 5, 5, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(34, 1, 2, 4, 17, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(35, 1, 2, 4, 18, 'opening', 4, 4, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(36, 1, 2, 4, 19, 'opening', 1, 1, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(37, 1, 2, 4, 20, 'opening', 7, 7, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(38, 2, 3, 11, 46, 'opening', 7, 7, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(39, 2, 3, 11, 47, 'opening', 1, 1, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(40, 2, 3, 11, 48, 'opening', 7, 7, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(41, 2, 3, 11, 49, 'opening', 4, 4, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(42, 2, 3, 10, 41, 'opening', 4, 4, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(43, 2, 3, 10, 42, 'opening', 1, 1, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(44, 2, 3, 10, 43, 'opening', 4, 4, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(45, 2, 3, 10, 44, 'opening', 7, 7, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(46, 2, 3, 10, 45, 'opening', 2, 2, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(47, 2, 3, 15, 66, 'opening', 7, 7, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(48, 2, 3, 15, 67, 'opening', 6, 6, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(49, 2, 3, 15, 68, 'opening', 5, 5, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(50, 2, 3, 15, 69, 'opening', 3, 3, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(51, 2, 3, 15, 70, 'opening', 4, 4, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(52, 2, 3, 16, 0, 'opening', 20, 20, 1294.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(53, 2, 3, 13, 56, 'opening', 3, 3, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(54, 2, 3, 13, 57, 'opening', 6, 6, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(55, 2, 3, 13, 58, 'opening', 2, 2, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(56, 2, 3, 13, 59, 'opening', 6, 6, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(57, 2, 3, 9, 36, 'opening', 2, 2, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(58, 2, 3, 9, 37, 'opening', 7, 7, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(59, 2, 3, 9, 38, 'opening', 1, 1, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(60, 2, 3, 9, 39, 'opening', 5, 5, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(61, 2, 3, 9, 40, 'opening', 2, 2, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(62, 2, 3, 14, 61, 'opening', 3, 3, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(63, 2, 3, 14, 63, 'opening', 8, 8, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(64, 2, 3, 14, 64, 'opening', 4, 4, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(65, 2, 3, 14, 65, 'opening', 2, 2, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(66, 2, 3, 12, 51, 'opening', 3, 3, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(67, 2, 3, 12, 52, 'opening', 8, 8, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(68, 2, 3, 12, 53, 'opening', 7, 7, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(69, 2, 3, 12, 54, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(70, 2, 3, 12, 55, 'opening', 4, 4, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(71, 3, 4, 19, 81, 'opening', 8, 8, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(72, 3, 4, 19, 82, 'opening', 7, 7, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(73, 3, 4, 19, 83, 'opening', 7, 7, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(74, 3, 4, 19, 85, 'opening', 8, 8, 1872.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(75, 3, 4, 18, 76, 'opening', 6, 6, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(76, 3, 4, 18, 77, 'opening', 7, 7, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(77, 3, 4, 18, 78, 'opening', 3, 3, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(78, 3, 4, 18, 79, 'opening', 6, 6, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(79, 3, 4, 18, 80, 'opening', 6, 6, 1638.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(80, 3, 4, 23, 101, 'opening', 6, 6, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(81, 3, 4, 23, 102, 'opening', 6, 6, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(82, 3, 4, 23, 103, 'opening', 3, 3, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(83, 3, 4, 23, 104, 'opening', 7, 7, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(84, 3, 4, 23, 105, 'opening', 7, 7, 813.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(85, 3, 4, 24, 0, 'opening', 20, 20, 1294.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(86, 3, 4, 21, 92, 'opening', 4, 4, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(87, 3, 4, 21, 93, 'opening', 8, 8, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(88, 3, 4, 21, 94, 'opening', 5, 5, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(89, 3, 4, 21, 95, 'opening', 4, 4, 1528.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(90, 3, 4, 17, 71, 'opening', 3, 3, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(91, 3, 4, 17, 72, 'opening', 6, 6, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(92, 3, 4, 17, 73, 'opening', 3, 3, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(93, 3, 4, 17, 74, 'opening', 1, 1, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(94, 3, 4, 17, 75, 'opening', 1, 1, 2106.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(95, 3, 4, 22, 96, 'opening', 8, 8, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(96, 3, 4, 22, 97, 'opening', 3, 3, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(97, 3, 4, 22, 98, 'opening', 5, 5, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(98, 3, 4, 22, 99, 'opening', 2, 2, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(99, 3, 4, 22, 100, 'opening', 8, 8, 943.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(100, 3, 4, 20, 86, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(101, 3, 4, 20, 87, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(102, 3, 4, 20, 88, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(103, 3, 4, 20, 89, 'opening', 6, 6, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(104, 3, 4, 20, 90, 'opening', 3, 3, 1112.00, NULL, NULL, 'Opening stock', 'System', '2026-09-23 09:34:56'),
(105, 4, 1, 25, 106, 'pos_sale', -1, 4, 650.00, 'App\\Models\\Order', 1, NULL, 'Sonya Rahman', '2026-09-23 22:11:31'),
(106, 4, 1, 26, 0, 'opening', 100, 100, 228.00, 'App\\Models\\Product', 26, 'Stock edited from product form', 'Sonya Rahman', '2026-09-23 23:12:58'),
(107, 4, 1, 25, 106, 'sale', -1, 3, 650.00, 'App\\Models\\Order', 2, NULL, 'System', '2026-09-30 05:04:09'),
(108, 4, 1, 25, 106, 'sale', -2, 1, 650.00, 'App\\Models\\Order', 3, NULL, 'System', '2026-09-30 05:20:52'),
(109, 4, 1, 25, 106, 'sale', -1, 0, 650.00, 'App\\Models\\Order', 4, NULL, 'System', '2026-09-30 05:26:20'),
(110, 4, 1, 26, 0, 'sale', -1, 99, 228.00, 'App\\Models\\Order', 5, NULL, 'System', '2026-09-30 05:27:12'),
(111, 4, 1, 26, 0, 'sale', -1, 98, 228.00, 'App\\Models\\Order', 6, NULL, 'System', '2026-09-30 05:30:29'),
(112, 4, 1, 25, 107, 'sale', -1, 2, 650.00, 'App\\Models\\Order', 7, NULL, 'System', '2026-09-30 05:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfers`
--

CREATE TABLE `stock_transfers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_warehouse_id` bigint UNSIGNED NOT NULL,
  `to_warehouse_id` bigint UNSIGNED NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `id` bigint UNSIGNED NOT NULL,
  `stock_transfer_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscribers`
--

INSERT INTO `subscribers` (`id`, `branch_id`, `email`, `created_at`, `updated_at`) VALUES
(1, 4, 'allbizhubweb@gmail.com', '2026-09-24 22:02:07', '2026-09-24 22:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `plan_id` bigint UNSIGNED NOT NULL,
  `billing_cycle` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `grace_ends_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `next_plan_id` bigint UNSIGNED DEFAULT NULL,
  `next_cycle` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reminders_sent` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `branch_id`, `plan_id`, `billing_cycle`, `status`, `amount`, `starts_at`, `ends_at`, `trial_ends_at`, `grace_ends_at`, `cancelled_at`, `auto_renew`, `next_plan_id`, `next_cycle`, `reminders_sent`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'monthly', 'trial', 1499.00, '2026-09-23 07:50:19', '2026-10-07 07:50:19', '2026-10-07 07:50:19', NULL, NULL, 1, NULL, NULL, NULL, '2026-09-23 07:50:19', '2026-09-23 07:50:19'),
(2, 2, 3, 'monthly', 'trial', 1499.00, '2026-09-23 07:50:19', '2026-10-07 07:50:19', '2026-10-07 07:50:19', NULL, NULL, 1, NULL, NULL, NULL, '2026-09-23 07:50:19', '2026-09-23 07:50:19'),
(3, 3, 3, 'monthly', 'trial', 1499.00, '2026-09-23 07:50:19', '2026-10-07 07:50:19', '2026-10-07 07:50:19', NULL, NULL, 1, NULL, NULL, NULL, '2026-09-23 07:50:19', '2026-09-23 07:50:19'),
(4, 4, 3, 'monthly', 'active', 1499.00, '2026-09-24 21:59:00', '2026-11-07 07:50:19', NULL, NULL, NULL, 1, NULL, NULL, NULL, '2026-09-23 07:50:19', '2026-09-24 21:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `purchase_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` date NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `is_super_admin`, `remember_token`, `created_at`, `updated_at`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`) VALUES
(1, 'Super Admin', 'shataj99@gmail.com', NULL, '$2y$12$OUIWTKG1rGOb8Yjj./iDHukULjE.ST65Gbk.cKagVwPY/BnGpRt6y', 1, NULL, '2026-09-23 02:35:51', '2026-09-23 10:38:55', NULL, NULL, NULL),
(2, 'Super Admin', 'admin@example.com', NULL, '$2y$12$8egWeOi9lxOYhVXd3sACc.gyiyzx35qKz5bDmW2cH.zw8tt1z2EOe', 1, 'PjUhv5Cvaqe1Y85IaBNvIByQrHtBmjlIQVvqYDgq4JEWaX69Bg7wCoDB6taH', '2026-09-24 20:42:48', '2026-09-24 20:42:48', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_category_id` bigint UNSIGNED NOT NULL,
  `account_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `voucher_date` date NOT NULL,
  `party` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_id` bigint UNSIGNED DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `branch_id`, `name`, `code`, `type`, `address`, `phone`, `manager_id`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 4, 'Main Warehouse', 'MAIN', 'main', NULL, NULL, NULL, 1, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(2, 1, 'Main Warehouse', 'MAIN', 'main', NULL, NULL, NULL, 1, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(3, 2, 'Main Warehouse', 'MAIN', 'main', NULL, NULL, NULL, 1, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(4, 3, 'Main Warehouse', 'MAIN', 'main', NULL, NULL, NULL, 1, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_stocks`
--

CREATE TABLE `warehouse_stocks` (
  `id` bigint UNSIGNED NOT NULL,
  `warehouse_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `variant_id` bigint UNSIGNED NOT NULL DEFAULT '0',
  `quantity` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `warehouse_stocks`
--

INSERT INTO `warehouse_stocks` (`id`, `warehouse_id`, `product_id`, `variant_id`, `quantity`, `created_at`, `updated_at`) VALUES
(1, 1, 25, 106, 0, '2026-09-23 09:34:56', '2026-09-30 05:26:20'),
(2, 1, 25, 107, 2, '2026-09-23 09:34:56', '2026-09-30 05:36:39'),
(3, 1, 26, 0, 98, '2026-09-23 09:34:56', '2026-09-30 05:30:29'),
(4, 2, 3, 11, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(5, 2, 3, 12, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(6, 2, 3, 13, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(7, 2, 3, 14, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(8, 2, 3, 15, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(9, 2, 2, 6, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(10, 2, 2, 7, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(11, 2, 2, 8, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(12, 2, 2, 9, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(13, 2, 2, 10, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(14, 2, 7, 31, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(15, 2, 7, 32, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(16, 2, 7, 33, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(17, 2, 7, 34, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(18, 2, 7, 35, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(19, 2, 8, 0, 20, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(20, 2, 5, 21, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(21, 2, 5, 22, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(22, 2, 5, 23, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(23, 2, 5, 24, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(24, 2, 5, 25, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(25, 2, 1, 1, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(26, 2, 1, 2, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(27, 2, 1, 3, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(28, 2, 1, 4, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(29, 2, 1, 5, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(30, 2, 6, 26, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(31, 2, 6, 27, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(32, 2, 6, 28, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(33, 2, 6, 29, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(34, 2, 6, 30, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(35, 2, 4, 16, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(36, 2, 4, 17, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(37, 2, 4, 18, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(38, 2, 4, 19, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(39, 2, 4, 20, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(40, 3, 11, 46, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(41, 3, 11, 47, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(42, 3, 11, 48, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(43, 3, 11, 49, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(44, 3, 11, 50, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(45, 3, 10, 41, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(46, 3, 10, 42, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(47, 3, 10, 43, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(48, 3, 10, 44, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(49, 3, 10, 45, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(50, 3, 15, 66, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(51, 3, 15, 67, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(52, 3, 15, 68, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(53, 3, 15, 69, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(54, 3, 15, 70, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(55, 3, 16, 0, 20, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(56, 3, 13, 56, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(57, 3, 13, 57, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(58, 3, 13, 58, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(59, 3, 13, 59, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(60, 3, 13, 60, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(61, 3, 9, 36, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(62, 3, 9, 37, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(63, 3, 9, 38, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(64, 3, 9, 39, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(65, 3, 9, 40, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(66, 3, 14, 61, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(67, 3, 14, 62, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(68, 3, 14, 63, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(69, 3, 14, 64, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(70, 3, 14, 65, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(71, 3, 12, 51, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(72, 3, 12, 52, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(73, 3, 12, 53, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(74, 3, 12, 54, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(75, 3, 12, 55, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(76, 4, 19, 81, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(77, 4, 19, 82, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(78, 4, 19, 83, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(79, 4, 19, 84, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(80, 4, 19, 85, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(81, 4, 18, 76, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(82, 4, 18, 77, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(83, 4, 18, 78, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(84, 4, 18, 79, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(85, 4, 18, 80, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(86, 4, 23, 101, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(87, 4, 23, 102, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(88, 4, 23, 103, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(89, 4, 23, 104, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(90, 4, 23, 105, 7, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(91, 4, 24, 0, 20, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(92, 4, 21, 91, 0, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(93, 4, 21, 92, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(94, 4, 21, 93, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(95, 4, 21, 94, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(96, 4, 21, 95, 4, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(97, 4, 17, 71, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(98, 4, 17, 72, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(99, 4, 17, 73, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(100, 4, 17, 74, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(101, 4, 17, 75, 1, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(102, 4, 22, 96, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(103, 4, 22, 97, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(104, 4, 22, 98, 5, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(105, 4, 22, 99, 2, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(106, 4, 22, 100, 8, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(107, 4, 20, 86, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(108, 4, 20, 87, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(109, 4, 20, 88, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(110, 4, 20, 89, 6, '2026-09-23 09:34:56', '2026-09-23 09:34:56'),
(111, 4, 20, 90, 3, '2026-09-23 09:34:56', '2026-09-23 09:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `webhook_deliveries`
--

CREATE TABLE `webhook_deliveries` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `event` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_code` smallint UNSIGNED DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `response` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_ms` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `accounts_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `account_categories`
--
ALTER TABLE `account_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_categories_branch_id_kind_name_unique` (`branch_id`,`kind`,`name`);

--
-- Indexes for table `account_transactions`
--
ALTER TABLE `account_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `acct_txn_ref_unique` (`reference_type`,`reference_id`,`category`,`account_id`),
  ADD KEY `account_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `account_transactions_branch_id_txn_date_index` (`branch_id`,`txn_date`),
  ADD KEY `account_transactions_account_id_txn_date_index` (`account_id`,`txn_date`);

--
-- Indexes for table `account_transfers`
--
ALTER TABLE `account_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_transfers_branch_id_foreign` (`branch_id`),
  ADD KEY `account_transfers_from_account_id_foreign` (`from_account_id`),
  ADD KEY `account_transfers_to_account_id_foreign` (`to_account_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  ADD KEY `activity_logs_branch_id_created_at_index` (`branch_id`,`created_at`),
  ADD KEY `activity_logs_action_index` (`action`);

--
-- Indexes for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ai_logs_branch_id_created_at_index` (`branch_id`,`created_at`);

--
-- Indexes for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_tokens_token_hash_unique` (`token_hash`),
  ADD KEY `api_tokens_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `app_notifications`
--
ALTER TABLE `app_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_notifications_branch_id_read_at_index` (`branch_id`,`read_at`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attendances_employee_id_date_unique` (`employee_id`,`date`),
  ADD KEY `attendances_branch_id_date_index` (`branch_id`,`date`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `blog_categories_branch_id_slug_unique` (`branch_id`,`slug`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branches_mobile_unique` (`mobile`),
  ADD UNIQUE KEY `branches_email_unique` (`email`),
  ADD UNIQUE KEY `branches_slug_unique` (`slug`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brands_branch_id_slug_unique` (`branch_id`,`slug`);

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
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaigns_branch_id_foreign` (`branch_id`),
  ADD KEY `perf_campaigns_status` (`status`);

--
-- Indexes for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `campaign_recipients_campaign_id_to_unique` (`campaign_id`,`to`),
  ADD KEY `campaign_recipients_customer_id_foreign` (`customer_id`),
  ADD KEY `campaign_recipients_campaign_id_status_index` (`campaign_id`,`status`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coupons_branch_id_code_unique` (`branch_id`,`code`),
  ADD KEY `coupons_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coupon_usages_coupon_id_foreign` (`coupon_id`),
  ADD KEY `coupon_usages_order_id_foreign` (`order_id`),
  ADD KEY `coupon_usages_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `courier_accounts`
--
ALTER TABLE `courier_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courier_accounts_branch_id_courier_unique` (`branch_id`,`courier`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_branch_id_phone_unique` (`branch_id`,`phone`),
  ADD UNIQUE KEY `customers_branch_id_email_unique` (`branch_id`,`email`),
  ADD KEY `perf_customers_branch_created` (`branch_id`,`created_at`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_addresses_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `domains`
--
ALTER TABLE `domains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `domains_host_unique` (`host`),
  ADD KEY `perf_domains_branch_status` (`branch_id`,`status`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employees_branch_id_foreign` (`branch_id`),
  ADD KEY `employees_staff_id_foreign` (`staff_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_number_unique` (`number`),
  ADD KEY `invoices_plan_id_foreign` (`plan_id`),
  ADD KEY `invoices_branch_id_status_index` (`branch_id`,`status`),
  ADD KEY `perf_invoices_status_paid` (`status`,`paid_at`);

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
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leave_requests_branch_id_foreign` (`branch_id`),
  ADD KEY `leave_requests_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `message_logs`
--
ALTER TABLE `message_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_logs_campaign_id_foreign` (`campaign_id`),
  ADD KEY `message_logs_order_id_foreign` (`order_id`),
  ADD KEY `message_logs_branch_id_channel_created_at_index` (`branch_id`,`channel`,`created_at`),
  ADD KEY `perf_msglogs_branch_status` (`branch_id`,`channel`,`status`,`created_at`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD UNIQUE KEY `orders_branch_client_ref_unique` (`branch_id`,`client_ref`),
  ADD KEY `orders_branch_id_status_index` (`branch_id`,`status`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`),
  ADD KEY `orders_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `orders_pos_session_id_foreign` (`pos_session_id`),
  ADD KEY `perf_orders_branch_created` (`branch_id`,`created_at`),
  ADD KEY `perf_orders_branch_payment` (`branch_id`,`payment_status`),
  ADD KEY `perf_orders_branch_phone` (`branch_id`,`customer_phone`),
  ADD KEY `perf_orders_branch_source` (`branch_id`,`source`,`created_at`),
  ADD KEY `perf_orders_created` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`),
  ADD KEY `order_items_product_variant_id_foreign` (`product_variant_id`);

--
-- Indexes for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_status_histories_order_id_foreign` (`order_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pages_branch_id_slug_unique` (`branch_id`,`slug`),
  ADD KEY `perf_pages_branch_published` (`branch_id`,`is_published`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_reference_unique` (`reference`),
  ADD KEY `payments_invoice_id_foreign` (`invoice_id`),
  ADD KEY `perf_payments_txn` (`transaction_id`),
  ADD KEY `perf_payments_order_status` (`order_id`,`status`);

--
-- Indexes for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_gateways_branch_id_gateway_unique` (`branch_id`,`gateway`);

--
-- Indexes for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payrolls_branch_id_month_unique` (`branch_id`,`month`);

--
-- Indexes for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payroll_items_payroll_id_employee_id_unique` (`payroll_id`,`employee_id`),
  ADD KEY `payroll_items_employee_id_foreign` (`employee_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plans_slug_unique` (`slug`),
  ADD KEY `plans_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `posts_branch_id_slug_unique` (`branch_id`,`slug`),
  ADD KEY `posts_blog_category_id_foreign` (`blog_category_id`),
  ADD KEY `posts_branch_id_status_published_at_index` (`branch_id`,`status`,`published_at`);

--
-- Indexes for table `pos_cash_movements`
--
ALTER TABLE `pos_cash_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pos_cash_movements_pos_session_id_foreign` (`pos_session_id`);

--
-- Indexes for table `pos_holds`
--
ALTER TABLE `pos_holds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pos_holds_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `pos_sessions`
--
ALTER TABLE `pos_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pos_sessions_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `pos_sessions_branch_id_closed_at_index` (`branch_id`,`closed_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_branch_id_slug_unique` (`branch_id`,`slug`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_branch_id_is_active_index` (`branch_id`,`is_active`),
  ADD KEY `products_brand_id_foreign` (`brand_id`),
  ADD KEY `products_subcategory_id_foreign` (`subcategory_id`),
  ADD KEY `products_branch_id_barcode_index` (`branch_id`,`barcode`),
  ADD KEY `perf_products_active_created` (`branch_id`,`is_active`,`created_at`),
  ADD KEY `perf_products_active_sold` (`branch_id`,`is_active`,`sold_count`),
  ADD KEY `perf_products_active_price` (`branch_id`,`is_active`,`price`),
  ADD KEY `perf_products_branch_sku` (`branch_id`,`sku`),
  ADD KEY `perf_products_branch_code` (`branch_id`,`product_code`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_images_product_id_foreign` (`product_id`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_variants_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchases_number_unique` (`number`),
  ADD KEY `purchases_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchases_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `perf_purchases_branch_date` (`branch_id`,`purchase_date`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_returns_number_unique` (`number`),
  ADD KEY `purchase_returns_branch_id_foreign` (`branch_id`),
  ADD KEY `purchase_returns_purchase_id_foreign` (`purchase_id`),
  ADD KEY `purchase_returns_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_return_items_purchase_return_id_foreign` (`purchase_return_id`),
  ADD KEY `purchase_return_items_purchase_item_id_foreign` (`purchase_item_id`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_requests_number_unique` (`number`),
  ADD KEY `return_requests_order_id_foreign` (`order_id`),
  ADD KEY `return_requests_customer_id_foreign` (`customer_id`),
  ADD KEY `perf_returns_branch_status` (`branch_id`,`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `perf_reviews_product_approved` (`product_id`,`is_approved`,`created_at`),
  ADD KEY `perf_reviews_customer` (`customer_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_branch_id_name_unique` (`branch_id`,`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shipments_order_id_foreign` (`order_id`),
  ADD KEY `shipments_branch_id_status_group_index` (`branch_id`,`status_group`),
  ADD KEY `shipments_courier_consignment_id_index` (`courier`,`consignment_id`),
  ADD KEY `shipments_tracking_code_index` (`tracking_code`),
  ADD KEY `perf_shipments_status_group` (`status_group`,`courier`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `perf_sliders_branch_active` (`branch_id`,`is_active`,`position`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_email_unique` (`email`),
  ADD KEY `staff_branch_id_foreign` (`branch_id`),
  ADD KEY `staff_role_id_foreign` (`role_id`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_adjustments_number_unique` (`number`),
  ADD KEY `stock_adjustments_branch_id_foreign` (`branch_id`),
  ADD KEY `stock_adjustments_warehouse_id_foreign` (`warehouse_id`);

--
-- Indexes for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  ADD KEY `stock_adjustment_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_warehouse_id_foreign` (`warehouse_id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  ADD KEY `stock_movements_branch_id_product_id_variant_id_index` (`branch_id`,`product_id`,`variant_id`),
  ADD KEY `stock_movements_branch_id_type_created_at_index` (`branch_id`,`type`,`created_at`);

--
-- Indexes for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stock_transfers_number_unique` (`number`),
  ADD KEY `stock_transfers_branch_id_foreign` (`branch_id`),
  ADD KEY `stock_transfers_from_warehouse_id_foreign` (`from_warehouse_id`),
  ADD KEY `stock_transfers_to_warehouse_id_foreign` (`to_warehouse_id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transfer_items_stock_transfer_id_foreign` (`stock_transfer_id`),
  ADD KEY `stock_transfer_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscribers_branch_id_email_unique` (`branch_id`,`email`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscriptions_branch_id_unique` (`branch_id`),
  ADD KEY `subscriptions_plan_id_foreign` (`plan_id`),
  ADD KEY `subscriptions_next_plan_id_foreign` (`next_plan_id`),
  ADD KEY `subscriptions_status_ends_at_index` (`status`,`ends_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_payments_branch_id_foreign` (`branch_id`),
  ADD KEY `supplier_payments_supplier_id_foreign` (`supplier_id`),
  ADD KEY `supplier_payments_purchase_id_foreign` (`purchase_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vouchers_number_unique` (`number`),
  ADD KEY `vouchers_account_category_id_foreign` (`account_category_id`),
  ADD KEY `vouchers_account_id_foreign` (`account_id`),
  ADD KEY `vouchers_branch_id_kind_voucher_date_index` (`branch_id`,`kind`,`voucher_date`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_transactions_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouses_branch_id_foreign` (`branch_id`),
  ADD KEY `warehouses_manager_id_foreign` (`manager_id`);

--
-- Indexes for table `warehouse_stocks`
--
ALTER TABLE `warehouse_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `warehouse_stocks_warehouse_id_product_id_variant_id_unique` (`warehouse_id`,`product_id`,`variant_id`),
  ADD KEY `warehouse_stocks_product_id_foreign` (`product_id`);

--
-- Indexes for table `webhook_deliveries`
--
ALTER TABLE `webhook_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `webhook_deliveries_branch_id_created_at_index` (`branch_id`,`created_at`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlists_customer_id_product_id_unique` (`customer_id`,`product_id`),
  ADD KEY `wishlists_product_id_foreign` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `account_categories`
--
ALTER TABLE `account_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `account_transactions`
--
ALTER TABLE `account_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `account_transfers`
--
ALTER TABLE `account_transfers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `api_tokens`
--
ALTER TABLE `api_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `app_notifications`
--
ALTER TABLE `app_notifications`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courier_accounts`
--
ALTER TABLE `courier_accounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `domains`
--
ALTER TABLE `domains`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_logs`
--
ALTER TABLE `message_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payrolls`
--
ALTER TABLE `payrolls`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_items`
--
ALTER TABLE `payroll_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pos_cash_movements`
--
ALTER TABLE `pos_cash_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_holds`
--
ALTER TABLE `pos_holds`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_sessions`
--
ALTER TABLE `pos_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `warehouse_stocks`
--
ALTER TABLE `warehouse_stocks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `webhook_deliveries`
--
ALTER TABLE `webhook_deliveries`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_categories`
--
ALTER TABLE `account_categories`
  ADD CONSTRAINT `account_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_transactions`
--
ALTER TABLE `account_transactions`
  ADD CONSTRAINT `account_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `account_transactions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_transfers`
--
ALTER TABLE `account_transfers`
  ADD CONSTRAINT `account_transfers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `account_transfers_from_account_id_foreign` FOREIGN KEY (`from_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `account_transfers_to_account_id_foreign` FOREIGN KEY (`to_account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD CONSTRAINT `ai_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_tokens`
--
ALTER TABLE `api_tokens`
  ADD CONSTRAINT `api_tokens_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `app_notifications`
--
ALTER TABLE `app_notifications`
  ADD CONSTRAINT `app_notifications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD CONSTRAINT `blog_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `campaign_recipients`
--
ALTER TABLE `campaign_recipients`
  ADD CONSTRAINT `campaign_recipients_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_recipients_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupons_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usages`
--
ALTER TABLE `coupon_usages`
  ADD CONSTRAINT `coupon_usages_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usages_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coupon_usages_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courier_accounts`
--
ALTER TABLE `courier_accounts`
  ADD CONSTRAINT `courier_accounts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `domains`
--
ALTER TABLE `domains`
  ADD CONSTRAINT `domains_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employees_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_logs`
--
ALTER TABLE `message_logs`
  ADD CONSTRAINT `message_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_logs_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `message_logs_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_pos_session_id_foreign` FOREIGN KEY (`pos_session_id`) REFERENCES `pos_sessions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_status_histories`
--
ALTER TABLE `order_status_histories`
  ADD CONSTRAINT `order_status_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD CONSTRAINT `payment_gateways_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payrolls`
--
ALTER TABLE `payrolls`
  ADD CONSTRAINT `payrolls_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD CONSTRAINT `payroll_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_items_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `plans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_blog_category_id_foreign` FOREIGN KEY (`blog_category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_cash_movements`
--
ALTER TABLE `pos_cash_movements`
  ADD CONSTRAINT `pos_cash_movements_pos_session_id_foreign` FOREIGN KEY (`pos_session_id`) REFERENCES `pos_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_holds`
--
ALTER TABLE `pos_holds`
  ADD CONSTRAINT `pos_holds_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_sessions`
--
ALTER TABLE `pos_sessions`
  ADD CONSTRAINT `pos_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pos_sessions_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchases_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `purchase_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD CONSTRAINT `purchase_returns_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_returns_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `purchase_return_items`
--
ALTER TABLE `purchase_return_items`
  ADD CONSTRAINT `purchase_return_items_purchase_item_id_foreign` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_return_items_purchase_return_id_foreign` FOREIGN KEY (`purchase_return_id`) REFERENCES `purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `return_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `return_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shipments`
--
ALTER TABLE `shipments`
  ADD CONSTRAINT `shipments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shipments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sliders`
--
ALTER TABLE `sliders`
  ADD CONSTRAINT `sliders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustment_items`
--
ALTER TABLE `stock_adjustment_items`
  ADD CONSTRAINT `stock_adjustment_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfers`
--
ALTER TABLE `stock_transfers`
  ADD CONSTRAINT `stock_transfers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfers_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD CONSTRAINT `stock_transfer_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transfer_items_stock_transfer_id_foreign` FOREIGN KEY (`stock_transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD CONSTRAINT `subscribers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_next_plan_id_foreign` FOREIGN KEY (`next_plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `supplier_payments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `supplier_payments_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `supplier_payments_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_account_category_id_foreign` FOREIGN KEY (`account_category_id`) REFERENCES `account_categories` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `vouchers_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `vouchers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD CONSTRAINT `warehouses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouses_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `warehouse_stocks`
--
ALTER TABLE `warehouse_stocks`
  ADD CONSTRAINT `warehouse_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `warehouse_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `webhook_deliveries`
--
ALTER TABLE `webhook_deliveries`
  ADD CONSTRAINT `webhook_deliveries_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
