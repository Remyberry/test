-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2026 at 10:20 AM
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
-- Database: `epms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `head_id`, `created_at`, `updated_at`) VALUES
(1, 'ICSLIS - Institute of Computing Studies and Library Information Sciences', 'Department for computing and library science programs', 2, '2025-04-18 04:51:59', '2025-11-03 22:38:08'),
(2, 'Human Resources', 'Manages employee relations and workforce planning', 5, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'Finance', 'Handles financial operations and budgeting', NULL, '2025-04-18 04:51:59', '2025-04-18 04:52:48'),
(4, 'Academic Affairs', 'Oversees academic programs and policies', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'Student Services', 'Provides support services for students', 74, '2025-04-18 04:51:59', '2025-11-03 13:45:53'),
(6, 'Academic Affairs', 'Oversees academic programs, faculty, and educational policies', 7, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'Institute of Business and Management', 'Department for business programs and management education', 8, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'Institute of Computing Studies and Library Information Science', 'Department for computing and library science programs', 9, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(9, 'Institute of Education, Arts and Sciences', 'Department for education, arts and sciences programs', 10, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(10, 'Student Affairs and Services Office', 'Provides support services for students', 11, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(11, 'Admissions and Registrar\'s Office', 'Manages student admissions and registration', 12, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(12, 'College Library', 'Provides library resources and services', 13, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(13, 'College Guidance and Formation Office', 'Provides guidance and counseling services', 14, '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `dpcr_entries`
--

CREATE TABLE `dpcr_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `major_output` text NOT NULL,
  `success_indicators` text NOT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `accountable` text DEFAULT NULL,
  `accomplishments` text DEFAULT NULL,
  `category` enum('Strategic','Core','Support') NOT NULL DEFAULT 'Core',
  `q1_rating` decimal(5,2) DEFAULT NULL,
  `q2_rating` decimal(5,2) DEFAULT NULL,
  `q3_rating` decimal(5,2) DEFAULT NULL,
  `q4_rating` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actual_accomplishments` text DEFAULT NULL,
  `q_rating` decimal(3,2) DEFAULT NULL,
  `e_rating` decimal(3,2) DEFAULT NULL,
  `t_rating` decimal(3,2) DEFAULT NULL,
  `a_rating` decimal(3,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dpcr_entries`
--

INSERT INTO `dpcr_entries` (`id`, `record_id`, `major_output`, `success_indicators`, `budget`, `accountable`, `accomplishments`, `category`, `q1_rating`, `q2_rating`, `q3_rating`, `q4_rating`, `weight`, `created_at`, `updated_at`, `actual_accomplishments`, `q_rating`, `e_rating`, `t_rating`, `a_rating`, `remarks`) VALUES
(21, 45, 'asdasdasd\r\nasdasdasd\r\nasdasdasd', 'asdasdasd', 2222222.00, 'dwasd', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-11-03 01:24:22', '2025-11-03 01:24:22', 'asdasdasd\r\nasdasdasd\r\nasdasdasd', 1.00, 1.00, 1.00, 1.00, ''),
(22, 45, 'asdasdasdasdasdasd\r\nasdasdasd', 'asdasdasd', 9999999999999.99, '3rw', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-11-03 01:24:22', '2025-11-03 01:24:22', 'asdasdasd', 1.00, 1.00, 1.00, 1.00, 'asdasdasd'),
(23, 53, 'SCP', 'SCP', 234.00, 'dxswd', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-11-03 22:21:05', '2025-11-03 22:21:05', 'SCP', 2.00, 3.00, 4.00, 3.00, 'SCP'),
(24, 53, 'SCP', 'SCP', 124.00, 'daswd', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-11-03 22:21:05', '2025-11-03 22:21:05', 'SCP', 4.00, 5.00, 5.00, 4.67, 'cSCP'),
(25, 53, 'SCP', 'SCP', 24.00, 'dswd', NULL, 'Support', NULL, NULL, NULL, NULL, NULL, '2025-11-03 22:21:05', '2025-11-03 22:21:05', 'SCP', 3.00, 2.00, 1.00, 2.00, ''),
(26, 56, 'asd', 'asd', 12.00, '123qwe', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-12-04 16:10:40', '2025-12-04 16:10:40', 'qwe', 2.00, 2.00, 2.00, 2.00, 'ge'),
(27, 56, 'qwe', 'qwe', 123.00, '3rw', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-12-04 16:10:40', '2025-12-04 16:10:40', 'qwe', 2.00, 2.00, 2.00, 2.00, 'wqadawd'),
(28, 56, 'test', 'etset', 432.00, '123123awedqwdwqeqweqw', NULL, 'Support', NULL, NULL, NULL, NULL, NULL, '2025-12-04 16:10:40', '2025-12-04 16:10:40', 'dsa', 3.00, 3.00, 3.00, 3.00, 'qwe'),
(29, 65, 'PERSONAL DEVELOPMENT', 'PERSONAL DEVELOPMENT', 123.00, 'dwasd dawsd dawdasd wads dawd dawd as dawdsaawd  wad', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-12-15 11:09:15', '2025-12-15 11:09:15', 'PERSONAL DEVELOPMENTPERSONAL DEVELOPMENTPERSONAL DEVELOPMENTPERSONAL DEVELOPMENTPERSONAL DEVELOPMENT', 2.00, 3.00, 4.00, 3.00, ''),
(30, 65, '.', 'PERSONAL DEVELOPMENT', 1234.00, 'dwasd s dfv fzdvc sgvc se sfvd sefg vsd', NULL, 'Strategic', NULL, NULL, NULL, NULL, NULL, '2025-12-15 11:09:15', '2025-12-15 11:09:15', 'dasvdfPERSONAL DEVELOPMENTPERSONAL DEVELOPMENT', NULL, NULL, NULL, NULL, ''),
(31, 65, 'PERSONAL DEVELOPMENTPERSONAL DEVELOPMENT', 'PERSONAL DEVELOPMENTPERSONAL DEVELOPMENT', 3452.00, 'gd fghn  dfth  fthj  fg jnh df th', NULL, 'Core', NULL, NULL, NULL, NULL, NULL, '2025-12-15 11:09:15', '2025-12-15 11:09:15', 'PERSONAL DEVELOPMENTPERSONAL DEVELOPMENTPERSONAL DEVELOPMENT', 2.00, 1.00, 4.00, 2.33, ' vgdfsfv');

-- --------------------------------------------------------

--
-- Table structure for table `idp_entries`
--

CREATE TABLE `idp_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `development_needs` text NOT NULL,
  `development_interventions` text NOT NULL,
  `target_competency_level` int(11) DEFAULT NULL,
  `success_indicators` text NOT NULL,
  `timeline_start` date DEFAULT NULL,
  `timeline_end` date DEFAULT NULL,
  `resources_needed` text DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ipcr_entries`
--

CREATE TABLE `ipcr_entries` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `major_output` text NOT NULL,
  `success_indicators` text NOT NULL,
  `actual_accomplishments` text DEFAULT NULL,
  `q_rating` decimal(5,2) DEFAULT NULL,
  `e_rating` decimal(5,2) DEFAULT NULL,
  `t_rating` decimal(5,2) DEFAULT NULL,
  `final_rating` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `category` enum('Strategic','Core','Support') NOT NULL DEFAULT 'Core',
  `weight` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 9, 'New form submission requires your review', 'view_record.php?id=14', 0, '2025-10-13 22:31:55'),
(2, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=14', 0, '2025-10-21 05:59:06'),
(3, 9, 'New form submission requires your review', 'view_record.php?id=15', 0, '2025-10-21 06:31:25'),
(4, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=15', 0, '2025-10-21 06:38:11'),
(5, 9, 'New form submission requires your review', 'view_record.php?id=18', 0, '2025-10-22 10:17:51'),
(6, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=18', 0, '2025-10-22 10:18:45'),
(7, 9, 'New form submission requires your review', 'view_record.php?id=19', 0, '2025-10-29 14:11:58'),
(8, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=19', 0, '2025-10-29 14:13:07'),
(9, 9, 'New form submission requires your review', 'view_record.php?id=20', 0, '2025-10-29 14:27:56'),
(10, 9, 'New form submission requires your review', 'view_record.php?id=24', 0, '2025-10-31 14:02:49'),
(11, 9, 'New form submission requires your review', 'view_record.php?id=25', 0, '2025-10-31 14:12:52'),
(12, 9, 'New form submission requires your review', 'view_record.php?id=35', 0, '2025-11-02 08:12:12'),
(13, 9, 'New form submission requires your review', 'view_record.php?id=36', 0, '2025-11-02 11:59:43'),
(14, 9, 'New form submission requires your review', 'view_record.php?id=37', 0, '2025-11-02 12:03:05'),
(15, 9, 'New form submission requires your review', 'view_record.php?id=38', 0, '2025-11-02 12:03:12'),
(16, 8, 'New form submission requires your review', 'view_record.php?id=39', 0, '2025-11-02 13:11:43'),
(17, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=38', 0, '2025-11-02 13:29:56'),
(18, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=37', 0, '2025-11-02 18:14:11'),
(19, 9, 'New form submission requires your review', 'view_record.php?id=40', 0, '2025-11-02 19:57:00'),
(20, 9, 'New form submission requires your review', 'view_record.php?id=41', 0, '2025-11-02 19:57:07'),
(21, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=40', 0, '2025-11-02 20:08:35'),
(22, 9, 'New form submission requires your review', 'view_record.php?id=42', 0, '2025-11-02 22:02:51'),
(23, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=42', 0, '2025-11-02 22:33:24'),
(24, 9, 'New form submission requires your review', 'view_record.php?id=43', 0, '2025-11-02 23:46:58'),
(25, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=43', 0, '2025-11-02 23:49:37'),
(26, 9, 'New form submission requires your review', 'view_record.php?id=44', 0, '2025-11-02 23:55:46'),
(27, 9, 'New form submission requires your review', 'view_record.php?id=46', 0, '2025-11-03 13:08:48'),
(28, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=46', 0, '2025-11-03 13:09:57'),
(29, 74, 'New form submission requires your review', 'view_record.php?id=48', 0, '2025-11-03 13:50:38'),
(30, 74, 'New form submission requires your review', 'view_record.php?id=49', 0, '2025-11-03 13:57:10'),
(31, 73, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=49', 0, '2025-11-03 14:01:51'),
(32, 74, 'New form submission requires your review', 'view_record.php?id=50', 0, '2025-11-03 14:08:57'),
(33, 74, 'New form submission requires your review', 'view_record.php?id=51', 0, '2025-11-03 14:14:15'),
(34, 9, 'New form submission requires your review', 'view_record.php?id=52', 0, '2025-11-03 22:13:53'),
(35, 69, 'Your IPCR for Q4 2025 has been APPROVED', 'view_record.php?id=44', 0, '2025-11-03 22:18:09'),
(36, 9, 'New form submission requires your review', 'view_record.php?id=54', 0, '2025-11-03 22:24:36'),
(37, 69, 'Your IPCR for Q3 2025 has been For Revision', 'view_record.php?id=47', 0, '2025-11-03 23:05:17'),
(38, 9, 'New form submission requires your review', 'view_record.php?id=55', 0, '2025-12-04 16:07:15'),
(39, 69, 'Your IPCR for Q2 2025 has been APPROVED', 'view_record.php?id=55', 0, '2025-12-14 09:57:23'),
(40, 9, 'New form submission requires your review', 'view_record.php?id=57', 0, '2025-12-15 09:33:14'),
(41, 9, 'New form submission requires your review', 'view_record.php?id=58', 0, '2025-12-15 09:33:28'),
(42, 2, 'New form submission requires your review', 'view_record.php?id=59', 0, '2025-12-15 09:44:35'),
(43, 9, 'New form submission requires your review', 'view_record.php?id=76', 0, '2026-01-01 20:46:46'),
(44, 9, 'New form submission requires your review', 'view_record.php?id=77', 0, '2026-01-01 21:37:35');

-- --------------------------------------------------------

--
-- Table structure for table `pds_records`
--

CREATE TABLE `pds_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pds_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Stores the complete PDS data as a JSON object' CHECK (json_valid(`pds_data`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pds_records`
--

INSERT INTO `pds_records` (`id`, `user_id`, `pds_data`, `submitted_at`, `updated_at`) VALUES
(1, 69, '{\n    \"personal_info\": {\n        \"surname\": \"Dela Cruz\",\n        \"first_name\": \"Juan\",\n        \"middle_name\": \"Daireksyon\",\n        \"dob\": \"2025-01-14\",\n        \"pob\": \"Madrid\",\n        \"sex\": \"Male\",\n        \"civil_status\": \"Single\",\n        \"height\": \"123\",\n        \"weight\": \"69\",\n        \"blood_type\": \"O\",\n        \"gsis_id\": \"0088008\",\n        \"pagibig_id\": \"999099909\",\n        \"philhealth_no\": \"090911099911\",\n        \"sss_no\": \"321321321321\",\n        \"tin_no\": \"11111111\",\n        \"agency_employee_no\": \"\",\n        \"residential_address\": \"Mabuhay Street, Gumamela, Mars, Philippines\",\n        \"res_zip_code\": \"999\",\n        \"permanent_address\": \"Mabuhay Street, Gumamela, Mars, Philippines\",\n        \"perm_zip_code\": \"999\",\n        \"tel_no_res\": \"\",\n        \"mobile_no\": \"090909990909\",\n        \"email_address\": \"JuanDelaCrux@gmail.com\"\n    },\n    \"family_background\": {\n        \"spouse_surname\": \"\",\n        \"spouse_first_name\": \"\",\n        \"spouse_middle_name\": \"\",\n        \"spouse_occupation\": \"\",\n        \"spouse_employer\": \"\",\n        \"father_surname\": \"Dela Cruz\",\n        \"father_first_name\": \"Jun\",\n        \"father_middle_name\": \"July\",\n        \"mother_surname\": \"Daireksyon\",\n        \"mother_first_name\": \"Sun\",\n        \"mother_middle_name\": \"Ang\"\n    },\n    \"conditional\": {\n        \"q34\": \"No\",\n        \"q34_details\": \"\",\n        \"q35a\": \"No\",\n        \"q35a_details\": \"\",\n        \"q35b\": \"No\",\n        \"q35b_details\": \"\",\n        \"q36\": \"Yes\",\n        \"q36_details\": \"I got caught refilling water gallon from faucet water.\",\n        \"q37\": \"No\",\n        \"q37_details\": \"\",\n        \"q38\": \"No\",\n        \"q38_details\": \"\",\n        \"q39\": \"No\",\n        \"q39_details\": \"\",\n        \"q40\": \"No\",\n        \"q40_details\": \"\",\n        \"q41\": \"No\",\n        \"q41_details\": \"\",\n        \"q42a\": \"Yes\",\n        \"q42a_details\": \"967619\",\n        \"q42b\": \"No\",\n        \"q42b_details\": \"\",\n        \"q42c\": \"Yes\",\n        \"q42c_details\": \"AmazonPrime\"\n    },\n    \"references\": {\n        \"1\": {\n            \"name\": \"Viktor Magtanggol\",\n            \"address\": \"Sa tabi\",\n            \"tel\": \"0999999999\"\n        },\n        \"2\": {\n            \"name\": \"Gagambino Spooderinii\",\n            \"address\": \"Viva Italia\",\n            \"tel\": \"09111111111\"\n        },\n        \"3\": {\n            \"name\": \"Manong Guard\",\n            \"address\": \"Sa gates\",\n            \"tel\": \"09000000000\"\n        }\n    },\n    \"children\": [],\n    \"educational_background\": [],\n    \"eligibility\": [],\n    \"work_experience\": [],\n    \"voluntary_work\": [],\n    \"learning_dev\": [],\n    \"other_skills\": [],\n    \"non_academic_distinctions\": [],\n    \"membership_in_assoc\": []\n}', '2025-10-13 22:22:31', '2025-10-29 20:47:52');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `name`, `department_id`, `created_at`, `updated_at`) VALUES
(1, 'BSCS - Bachelor of Science in Computer Science', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 'BSIS - Bachelor of Science in Information Systems', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'BLIS - Bachelor of Library and Information Science', 1, '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `form_type` enum('DPCR','IPCR','IDP') NOT NULL,
  `period` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `status` enum('Draft','Pending','Approved','For Revision') NOT NULL DEFAULT 'Draft',
  `date_submitted` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `computation_type` enum('Type1','Type2') DEFAULT 'Type1',
  `feedback` varchar(255) DEFAULT NULL,
  `confidential_remarks` varchar(255) DEFAULT NULL,
  `position` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `user_id`, `form_type`, `period`, `content`, `status`, `date_submitted`, `reviewed_by`, `date_reviewed`, `comments`, `computation_type`, `feedback`, `confidential_remarks`, `position`) VALUES
(42, 69, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"success_indicators\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"accomplishments\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"q\":\"3\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.67\",\"supervisor_q\":\"3\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"success_indicators\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"accomplishments\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"q\":\"3\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.00\",\"supervisor_q\":\"3\",\"supervisor_e\":\"3\",\"supervisor_t\":\"1\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"},{\"mfo\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.2\",\"success_indicators\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.2\",\"accomplishments\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.2\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"supervisor_q\":\"2\",\"supervisor_e\":\"3\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"success_indicators\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"accomplishments\":\"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\",\"q\":\"4\",\"e\":\"4\",\"t\":\"2\",\"a\":\"3.33\",\"supervisor_q\":\"2\",\"supervisor_e\":\"3\",\"supervisor_t\":\"3\",\"supervisor_a\":\"2.67\",\"remarks\":\"\"},{\"mfo\":\"dolore magna aliqua.\",\"success_indicators\":\"dolore magna aliqua.\",\"accomplishments\":\"dolore magna aliqua.\",\"q\":\"2\",\"e\":\"3\",\"t\":\"3\",\"a\":\"2.67\",\"supervisor_q\":\"3\",\"supervisor_e\":\"5\",\"supervisor_t\":\"3\",\"supervisor_a\":\"3.67\",\"remarks\":\"\"},{\"mfo\":\"dolore magna aliqua.\",\"success_indicators\":\"dolore magna aliqua.\",\"accomplishments\":\"dolore magna aliqua.\",\"q\":\"2\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.33\",\"supervisor_q\":\"3\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"}],\"final_rating\":\"3.25\",\"strategic_average\":\"2.67\",\"core_average\":\"4.00\",\"support_average\":\"2.44\",\"rating_interpretation\":\"Satisfactory\",\"supervisor_strategic_average\":\"2.33\",\"supervisor_core_average\":\"2.33\",\"supervisor_support_average\":\"2.89\",\"supervisor_final_rating\":\"2.39\",\"supervisor_rating_interpretation\":\"Unsatisfactory\"}', 'Approved', '2025-11-02 22:02:51', 9, '2025-11-02 22:33:24', NULL, 'Type1', 'Very Lorem', 'Confidential Lorem', 'Employee'),
(43, 69, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"Type 1\\nType 1\",\"success_indicators\":\"Type 1\",\"accomplishments\":\"Type 1\",\"q\":\"3\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.00\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"Type 1\",\"success_indicators\":\"Type 1\",\"accomplishments\":\"Type 1\",\"q\":\"4\",\"e\":\"4\",\"t\":\"4\",\"a\":\"4.00\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"4.00\",\"remarks\":\"OK\"}],\"core_functions\":[{\"mfo\":\"Type 1\",\"success_indicators\":\"Type 1\",\"accomplishments\":\"Type 1\",\"q\":\"3\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.00\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"4.00\",\"remarks\":\"OKS\"}],\"support_functions\":[],\"final_rating\":\"3.23\",\"strategic_average\":\"3.50\",\"core_average\":\"3.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Satisfactory\",\"supervisor_strategic_average\":\"4.00\",\"supervisor_core_average\":\"4.00\",\"supervisor_support_average\":\"0.00\",\"supervisor_final_rating\":\"3.60\",\"supervisor_rating_interpretation\":\"Very Satisfactory\"}', 'Approved', '2025-11-02 23:46:58', 9, '2025-11-02 23:49:37', NULL, 'Type1', 'Test Feedback', 'Test confidential remark', ''),
(44, 69, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"Type2\",\"success_indicators\":\"Type2\",\"accomplishments\":\"Type2\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.00\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"Type2\",\"success_indicators\":\"Type2\",\"accomplishments\":\"Type2\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.00\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"Type2\",\"success_indicators\":\"Type2\",\"accomplishments\":\"Type2\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.00\",\"remarks\":\"\"},{\"mfo\":\"Type2\",\"success_indicators\":\"Type2\",\"accomplishments\":\"Type2\",\"q\":\"3\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.00\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.00\",\"remarks\":\"\"}],\"final_rating\":\"2.05\",\"strategic_average\":\"2.00\",\"core_average\":\"2.00\",\"support_average\":\"2.50\",\"rating_interpretation\":\"Unsatisfactory\",\"supervisor_strategic_average\":\"2.00\",\"supervisor_core_average\":\"2.00\",\"supervisor_support_average\":\"2.00\",\"supervisor_final_rating\":\"2.00\",\"supervisor_rating_interpretation\":\"Unsatisfactory\"}', 'Approved', '2025-11-02 23:55:46', 9, '2025-11-03 22:18:09', NULL, 'Type1', '', '', ''),
(45, 9, 'DPCR', 'Q1 2025', NULL, 'Pending', '2025-11-03 01:24:22', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(46, 69, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"3\",\"supervisor_e\":\"3\",\"supervisor_t\":\"3\",\"supervisor_a\":\"3.00\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office\\/department.\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"3\",\"supervisor_e\":\"3\",\"supervisor_t\":\"3\",\"supervisor_a\":\"3.00\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"2.00\",\"strategic_average\":\"2.00\",\"core_average\":\"2.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Unsatisfactory\",\"supervisor_strategic_average\":\"3.00\",\"supervisor_core_average\":\"3.00\",\"supervisor_support_average\":\"0.00\",\"supervisor_final_rating\":\"2.70\",\"supervisor_rating_interpretation\":\"Satisfactory\"}', 'Approved', '2025-11-03 13:08:48', 9, '2025-11-03 13:09:57', NULL, 'Type1', '', '', ''),
(47, 69, 'IPCR', 'Q3 2025', '{\"period\":\"Q3 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"test1\",\"success_indicators\":\"test1\",\"accomplishments\":\"test1\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"test2\",\"success_indicators\":\"test2\",\"accomplishments\":\"test2\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"4.55\",\"strategic_average\":\"4.00\",\"core_average\":\"5.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Outstanding\"}', 'For Revision', '2025-11-03 13:28:41', 9, '2025-11-03 23:05:17', NULL, 'Type1', 'on\'t want to', 'on\'t want to', ''),
(48, 73, 'IPCR', 'Q1 2025', '{\"period\":\"Q1 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"te\",\"success_indicators\":\"te\",\"accomplishments\":\"te\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"te\",\"success_indicators\":\"te\",\"accomplishments\":\"te\",\"q\":\"4\",\"e\":\"4\",\"t\":\"3\",\"a\":\"3.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"3.07\",\"strategic_average\":\"2.33\",\"core_average\":\"3.67\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Satisfactory\"}', 'Pending', '2025-11-03 13:50:38', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(49, 73, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"type 2\",\"success_indicators\":\"type 2\",\"accomplishments\":\"type 2\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"3\",\"supervisor_a\":\"2.33\",\"remarks\":\"r1\"}],\"core_functions\":[{\"mfo\":\"type 2\",\"success_indicators\":\"type 2\",\"accomplishments\":\"type 2\",\"q\":\"4\",\"e\":\"4\",\"t\":\"3\",\"a\":\"3.67\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"3\",\"supervisor_a\":\"3.67\",\"remarks\":\"r2\"}],\"support_functions\":[{\"mfo\":\"type 2\",\"success_indicators\":\"type 2\",\"accomplishments\":\"type 2\",\"q\":\"4\",\"e\":\"4\",\"t\":\"3\",\"a\":\"3.67\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"3\",\"supervisor_a\":\"3.67\",\"remarks\":\"r3\"}],\"final_rating\":\"3.07\",\"strategic_average\":\"2.33\",\"core_average\":\"3.67\",\"support_average\":\"3.67\",\"rating_interpretation\":\"Satisfactory\",\"supervisor_strategic_average\":\"2.33\",\"supervisor_core_average\":\"3.67\",\"supervisor_support_average\":\"3.67\",\"supervisor_final_rating\":\"3.07\",\"supervisor_rating_interpretation\":\"Satisfactory\"}', 'Approved', '2025-11-03 13:57:10', 74, '2025-11-03 14:01:51', NULL, 'Type1', 'my feedback to justine', 'satisfactory', ''),
(50, 74, 'IPCR', 'Q1 2025', '{\"period\":\"Q1 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"dep head ipcr test 1\",\"success_indicators\":\"dep head ipcr test 1\",\"accomplishments\":\"dep head ipcr test 1\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"dep head ipcr test 1\",\"success_indicators\":\"dep head ipcr test 1\",\"accomplishments\":\"dep head ipcr test 1\",\"q\":\"4\",\"e\":\"4\",\"t\":\"3\",\"a\":\"3.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"3.07\",\"strategic_average\":\"2.33\",\"core_average\":\"3.67\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Satisfactory\"}', 'Pending', '2025-11-03 14:08:57', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(51, 74, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"SCP\",\"success_indicators\":\"SCP\",\"accomplishments\":\"SCP\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"SCP\",\"success_indicators\":\"SCP\",\"accomplishments\":\"SCP\",\"q\":\"4\",\"e\":\"4\",\"t\":\"3\",\"a\":\"3.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"SCP\",\"success_indicators\":\"SCP\",\"accomplishments\":\"SCP\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"final_rating\":\"2.93\",\"strategic_average\":\"2.33\",\"core_average\":\"3.67\",\"support_average\":\"2.33\",\"rating_interpretation\":\"Satisfactory\"}', 'Pending', '2025-11-03 14:14:15', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(53, 9, 'DPCR', 'Q1 2025', NULL, 'Pending', '2025-11-03 22:21:05', NULL, NULL, NULL, 'Type2', NULL, NULL, ''),
(54, 9, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"head dept ipcr\",\"success_indicators\":\"head dept ipcr\",\"accomplishments\":\"head dept ipcr\",\"q\":\"1\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"head dept ipcr\",\"success_indicators\":\"head dept ipcrhead dept ipcr\",\"accomplishments\":\"head dept ipcr\",\"q\":\"1\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"2.00\",\"strategic_average\":\"2.00\",\"core_average\":\"2.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Unsatisfactory\"}', 'Pending', '2025-11-03 22:24:36', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(55, 69, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"accomplishments\":\"qweqe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"supervisor_q\":\"1\",\"supervisor_e\":\"5\",\"supervisor_t\":\"1\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"accomplishments\":\"qweqwe\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"5\",\"supervisor_a\":\"2.33\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"1.00\",\"strategic_average\":\"1.00\",\"core_average\":\"1.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\",\"supervisor_strategic_average\":\"2.33\",\"supervisor_core_average\":\"2.33\",\"supervisor_support_average\":\"0.00\",\"supervisor_final_rating\":\"2.10\",\"supervisor_rating_interpretation\":\"Unsatisfactory\"}', 'Approved', '2025-12-04 16:07:15', 9, '2025-12-14 09:57:23', NULL, 'Type1', '', 'xdfsdfsdf', ''),
(56, 9, 'DPCR', 'Q1 2025', NULL, 'Pending', '2025-12-04 16:10:39', NULL, NULL, NULL, 'Type2', NULL, NULL, ''),
(57, 9, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"1\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"3\",\"e\":\"4\",\"t\":\"1\",\"a\":\"2.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"2\",\"e\":\"3\",\"t\":\"1\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"final_rating\":\"2.30\",\"strategic_average\":\"2.00\",\"core_average\":\"2.67\",\"support_average\":\"2.00\",\"rating_interpretation\":\"Unsatisfactory\"}', 'Pending', '2025-12-15 09:33:14', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(58, 9, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"1\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"3\",\"e\":\"4\",\"t\":\"1\",\"a\":\"2.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"success_indicators\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"accomplishments\":\"100% of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"q\":\"2\",\"e\":\"3\",\"t\":\"1\",\"a\":\"2.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"final_rating\":\"2.30\",\"strategic_average\":\"2.00\",\"core_average\":\"2.67\",\"support_average\":\"2.00\",\"rating_interpretation\":\"Unsatisfactory\"}', 'Pending', '2025-12-15 09:33:28', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(59, 6, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type2\",\"strategic_functions\":[{\"mfo\":\"asd\",\"success_indicators\":\"asd\",\"accomplishments\":\"gesd\",\"q\":\"3\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"wda\",\"success_indicators\":\"fas\",\"accomplishments\":\"gfaed\",\"q\":\"3\",\"e\":\"1\",\"t\":\"5\",\"a\":\"3.00\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"awd\",\"success_indicators\":\"aewgf\",\"accomplishments\":\"gasd\",\"q\":\"5\",\"e\":\"1\",\"t\":\"2\",\"a\":\"2.67\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"final_rating\":\"2.67\",\"strategic_average\":\"2.33\",\"core_average\":\"3.00\",\"support_average\":\"2.67\",\"rating_interpretation\":\"Satisfactory\"}', 'Pending', '2025-12-15 09:44:35', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(60, 6, 'IDP', 'Annual 2026', '{\"period\":\"Annual 2026\",\"professional_development\":[{\"goals\":\"123\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"\",\"status\":\"Not Started\"},{\"goals\":\"123\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"\",\"status\":\"Not Started\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(61, 6, 'IDP', 'Annual 2026', '{\"period\":\"Annual 2026\",\"professional_development\":[{\"goals\":\"123\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"\",\"status\":\"Not Started\"},{\"goals\":\"123\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"\",\"status\":\"Not Started\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(62, 9, 'IDP', 'Annual 2025', '{\"period\":\"Annual 2025\",\"professional_development\":[{\"goals\":\"50% 100/200f s\",\"competencies\":\"50% 100/200f saplings allotted to the office successfully planted in Sitio Target, Sapangbato during th\",\"actions\":\"50% 100/200f saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"timeline\":\"qq\",\"status\":\"In Progress\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(63, 9, 'IDP', 'Annual 2025', '{\"period\":\"Annual 2025\",\"professional_development\":[{\"goals\":\"50% 100/200f s\",\"competencies\":\"50% 100/200f saplings allotted to the office successfully planted in Sitio Target, Sapangbato during th\",\"actions\":\"50% 100/200f saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting of the office/department.\",\"timeline\":\"qq\",\"status\":\"In Progress\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(64, 6, 'IDP', 'Annual 2025', '{\"period\":\"Annual 2025\",\"professional_development\":[{\"goals\":\"asd\",\"competencies\":\"asd\",\"actions\":\"asd\",\"timeline\":\"asd\",\"status\":\"In Progress\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(65, 9, 'DPCR', 'Q1 2025', NULL, 'Pending', '2025-12-15 11:09:15', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(66, 9, 'IDP', 'Annual 2025', '{\"period\":\"Annual 2025\",\"professional_development\":[{\"goals\":\"PERSONAL DEVELOPMENTv\",\"competencies\":\"PERSONAL DEVELOPMENT\",\"actions\":\"PERSONAL DEVELOPMENT\",\"timeline\":\"\",\"status\":\"Not Started\"},{\"goals\":\"PERSONAL DEVELOPMENT\",\"competencies\":\"PERSONAL DEVELOPMENT\",\"actions\":\"PERSONAL DEVELOPMENT\",\"timeline\":\"\",\"status\":\"Not Started\"}],\"personal_development\":[],\"career_advancement\":[]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(67, 9, 'IPCR', '0', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"test\",\"success_indicators\":\"tes\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"3\",\"supervisor_e\":\"3\",\"supervisor_t\":\"5\",\"supervisor_a\":\"\",\"remarks\":\"qwe\"}],\"core_functions\":[{\"mfo\":\"tes\",\"success_indicators\":\"tes\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"3\",\"supervisor_e\":\"3\",\"supervisor_t\":\"5\",\"supervisor_a\":\"\",\"remarks\":\"qwe\"}],\"support_functions\":[],\"dh_comments\":\"vvv\"}', '', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(68, 69, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"terst\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"\",\"remarks\":\"2qweqweqwe\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"test\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"\",\"remarks\":\"2123aeqweqw\"}],\"support_functions\":[],\"dh_comments\":\"oya\"}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(69, 71, 'IPCR', 'Q4 2025', '{\"period\":\"Q4 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"terst\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"\",\"remarks\":\"2qweqweqwe\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"test\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"\",\"remarks\":\"2123aeqweqw\"}],\"support_functions\":[],\"dh_comments\":\"oya\"}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(70, 6, 'IDP', 'January to June 2025', '{\"idp_goals\":[{\"objective\":\"To review and improve my knowledge and expertise in computing sciences reserach to be used in my classes and dissertation.\",\"action_plan\":\"Improvement\\/Enhancement of Knowledge\\/Skills\\/Attitude (KSA) or Development in Addressing the Gap in Service):\\r\\n\\r\\n•\\tParticipate in seminars, conferences, and training sessions that focus on research.\\r\\n•\\tAccommodate research paneling and advising\",\"target_date\":\"January to June 2025\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(71, 6, 'IDP', 'January to June 2025', '{\"idp_goals\":[{\"objective\":\"qweqe\",\"action_plan\":\"asdasd\",\"target_date\":\"January to June 2025\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(72, 9, 'IDP', 'January to June 2025', '{\"idp_goals\":[{\"objective\":\"To review and improve my knowledge and expertise in computing sciences reserach to be used in my classes and dissertation.\",\"action_plan\":\"Improvement\\/Enhancement of Knowledge\\/Skills\\/Attitude (KSA) or Development in Addressing the Gap in Service):\\r\\n\\r\\n•\\tParticipate in seminars, conferences, and training sessions that focus on research.\\r\\n•\\tAccommodate research paneling and advising\",\"target_date\":\"January to June 2025\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(73, 69, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"January to June 2025January to June 2025January to June 2025January to June 2025\",\"success_indicators\":\"January to June 2025January to June 2025January to June 2025January to June 2025January to June 2025\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"January to June 2025January to June 2025January to June 2025\",\"success_indicators\":\"January to June 2025January to June 2025January to June 2025January to June 2025January to June 2025\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"dh_comments\":\"\"}', '', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(74, 71, 'IPCR', 'Q2 2025', '{\"period\":\"Q2 2025\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"January to June 2025January to June 2025January to June 2025January to June 2025\",\"success_indicators\":\"January to June 2025January to June 2025January to June 2025January to June 2025January to June 2025\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"January to June 2025January to June 2025January to June 2025\",\"success_indicators\":\"January to June 2025January to June 2025January to June 2025January to June 2025January to June 2025\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"dh_comments\":\"\"}', '', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(75, 69, 'IDP', 'January to June 2025', '{\"idp_goals\":[{\"objective\":\"January to June 2025January to June 2025January to June 2025January to June 2025v\",\"action_plan\":\"January to June 2025vJanuary to June 2025vvJanuary to June 2025January to June 2025\",\"target_date\":\"January to June 2025\"}]}', 'Pending', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(76, 69, 'IPCR', 'Q2 2026', '{\"period\":\"Q2 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"123\",\"success_indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"123\",\"success_indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"0.00\",\"strategic_average\":\"0.00\",\"core_average\":\"0.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\"}', 'Pending', '2026-01-01 20:46:46', NULL, NULL, NULL, 'Type1', NULL, NULL, ''),
(77, 69, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"212\",\"success_indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\",\"remarks\":\"\"}],\"support_functions\":[],\"final_rating\":\"0.00\",\"strategic_average\":\"0.00\",\"core_average\":\"0.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\"}', 'Pending', '2026-01-01 21:37:35', NULL, NULL, NULL, 'Type1', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'strategic_weight_type1', '45', 'Strategic category weight for Type1 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(2, 'core_weight_type1', '55', 'Core category weight for Type1 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'strategic_weight_type2', '45', 'Strategic category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(4, 'core_weight_type2', '45', 'Core category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'support_weight_type2', '10', 'Support category weight for Type2 computation (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'quality_weight', '35', 'Quality criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'efficiency_weight', '35', 'Efficiency criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'timeliness_weight', '30', 'Timeliness criteria weight for IPCR (%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(9, 'dpcr_computation_type', 'Type1', 'DPCR computation type: Type1 = Strategic (45%) and Core (55%), Type2 = Strategic (45%), Core (45%), and Support (10%)', '2025-04-18 04:51:59', '2025-04-18 04:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','president','department_head','regular_employee','user') NOT NULL DEFAULT 'user',
  `department_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `department_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'asantos@gmail.com', 'asd', 'admin', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-10-13 23:27:19'),
(2, 'Arnie Santos', 'arniesantos@cca.edu.ph', 'qwe', 'department_head', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-12-15 09:44:58'),
(5, 'HR Manager', 'hrmanager@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 2, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'Faculty Member', 'faculty@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'DR. CAROLINA A. SARMIENTO', 'carolinasarmiento@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'president', 6, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'MS. AMOR L. BARBA', 'amorbarba@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 4, NULL, NULL, '2025-04-18 04:51:59', '2025-11-02 13:10:41'),
(9, 'MS. MAIKA V. GARBES', 'maikagarbes@cca.edu.ph', 'asd', 'department_head', 8, NULL, 'ea0a7298bf7433bf30e7ab2390577223cbad5f7f73a51342de01782b1128d7b6', '2025-04-18 04:51:59', '2025-10-13 22:32:13'),
(10, 'DR. LEVITA DE GUZMAN', 'levitaguzman@cca.edu.ph', 'qwe', 'department_head', 7, NULL, NULL, '2025-04-18 04:51:59', '2025-11-02 13:33:11'),
(11, 'MS. MARIA TERESSA G. LAPUZ', 'mariateressalapuz@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 10, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(12, 'MR. LESSANDRO YUCON', 'lessandroyucon@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 11, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(13, 'MS. JASMINE ANGELICA MARIE CANLAS', 'jasmineangelicacanlas@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 12, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(14, 'DR. RHENAN ESTACIO', 'rhenanestacio@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', 13, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(15, 'ADMIN NGA KOOO', 'admin@cca.edu.ph', 'admin123', 'admin', 1, 'uploads/avatars/7_6801c5d913a3f.jpg', '', '2025-04-18 02:34:36', '2025-04-18 03:24:09'),
(16, 'Benedict Ortiz', 'jortiz@cca.edu.ph', '$2y$10$ucBn/VaiNqycSyxaxSYGrOGkFSWuaXicSVT/Ftcmu8XQQG9pjHxWm', 'regular_employee', 1, 'uploads/avatars/9_6801cd09df7d1.jpg', '', '2025-04-18 02:43:16', '2025-04-18 03:54:49'),
(17, 'Antonio Luna', 'henlun@cca.edu.ph', 'qwe', 'president', 2, 'uploads/avatars/12_6801c17a2cd64.jpg', '9bb4466b58b414e07f04261cd68158941888a531e08de034221229958b63057d', '2025-04-18 03:04:29', '2025-12-15 09:19:58'),
(69, 'Qwe', 'test@test.com', 'qwe', 'regular_employee', 8, 'uploads/avatars/9_6801cd09df7d1.jpg', '4e5b540b435ef51a3151dfc7079bc577e02bbe7f17eabb6c42925d77b8e74aab', '2025-04-18 02:43:16', '2025-11-03 13:33:36'),
(70, 'qwe', 'qwe@gmail.com', 'qwe', 'department_head', 8, NULL, NULL, '2025-10-13 22:36:38', '2025-10-20 20:56:36'),
(71, 'reset', 'reset@gmail.com', '$2y$10$wgGFje6boxFPRoW/wEq0V.DUZUyg0OV/F48Rv7hvsv/Tb7.u.J2KS', 'regular_employee', 8, NULL, '05d080eaf55cacad5e692bdf21ecbf1879d44bcfc144533a18cce37c58ece08c', '2025-10-21 16:35:36', '2025-10-21 17:58:30'),
(72, 'wda', 'wdasd@gmail.com', '$2y$10$K3IoEFwHAXaE3idzO.rtouWAohfiIeib22vaMMl0JUeQyWt3vRO.e', 'regular_employee', 7, NULL, NULL, '2025-11-02 13:11:14', '2025-11-02 13:11:14'),
(73, 'Justine Employee', 'jus.emp@email.com', '$2y$10$6xMckugbjRFdftZfERT2KusW7wuKkaZub.36iDxX6nlP6m77EIxIu', 'regular_employee', 5, NULL, NULL, '2025-11-03 13:42:44', '2025-11-03 13:44:19'),
(74, 'Mark Dep Head', 'mark.dep.head@email.com', '$2y$10$Lxg3tUNpndMPNyffidqiZ.HI.R/hz/PGSOjCrgCH8ahfHSiAyU6LC', 'department_head', 5, NULL, NULL, '2025-11-03 13:45:53', '2025-11-03 13:45:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `head_id` (`head_id`);

--
-- Indexes for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `idp_entries`
--
ALTER TABLE `idp_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pds_records`
--
ALTER TABLE `pds_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `records`
--
ALTER TABLE `records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `idp_entries`
--
ALTER TABLE `idp_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `pds_records`
--
ALTER TABLE `pds_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dpcr_entries`
--
ALTER TABLE `dpcr_entries`
  ADD CONSTRAINT `dpcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `idp_entries`
--
ALTER TABLE `idp_entries`
  ADD CONSTRAINT `idp_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  ADD CONSTRAINT `ipcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `records`
--
ALTER TABLE `records`
  ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
