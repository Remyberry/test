-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 16, 2026 at 02:11 AM
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
(1, 'Test Department', 'Department for computing and library science programs', 2, '2025-04-18 04:51:59', '2026-02-15 22:23:41'),
(2, 'Human Resources', 'Manages employee relations and workforce planning', 5, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(3, 'Finance', 'Handles financial operations and budgeting', NULL, '2025-04-18 04:51:59', '2025-04-18 04:52:48'),
(4, 'Academic Affairs', 'Oversees academic programs and policies', NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(5, 'Student Services', 'Provides support services for students', 74, '2025-04-18 04:51:59', '2025-11-03 13:45:53'),
(6, 'Academic Affairs', 'Oversees academic programs, faculty, and educational policies', 7, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'Institute of Business and Management', 'Department for business programs and management education', 8, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'Institute of Computing Studies and Library Information Science', 'Department for computing and library science programs', 9, '2025-04-18 04:51:59', '2026-02-15 22:21:45'),
(9, 'Institute of Education, Arts and Sciences', 'Department for education, arts and sciences programs', 10, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(10, 'Student Affairs and Services Office', 'Provides support services for students', 11, '2025-04-18 04:51:59', '2026-02-15 22:32:38'),
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
(1, 9, 'New form submission requires your review', 'view_record.php?id=1', 0, '2026-01-03 04:02:31'),
(2, 9, 'New form submission requires your review', 'view_record.php?id=2', 0, '2026-01-03 04:58:51'),
(3, 69, 'Your IPCR for Q1 2026 has been APPROVED', 'view_record.php?id=2', 0, '2026-01-03 05:01:50'),
(4, 9, 'New form submission requires your review', 'view_record.php?id=3', 0, '2026-01-03 05:57:00'),
(5, 69, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=3', 0, '2026-01-03 06:17:21'),
(6, 9, 'New form submission requires your review', 'view_record.php?id=4', 0, '2026-01-03 06:17:51'),
(7, 69, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=4', 0, '2026-01-03 06:18:52'),
(8, 9, 'New form submission requires your review', 'view_record.php?id=5', 0, '2026-01-03 06:19:12'),
(9, 69, 'Your IPCR for Q1 2026 has been APPROVED', 'view_record.php?id=5', 0, '2026-01-03 09:10:02'),
(10, 9, 'New form submission requires your review', 'view_record.php?id=6', 0, '2026-01-04 13:15:24'),
(11, 9, 'New form submission requires your review', 'view_record.php?id=7', 0, '2026-01-04 13:16:28'),
(12, 9, 'New form submission requires your review', 'view_record.php?id=8', 0, '2026-01-06 16:36:43'),
(13, 69, 'Your IPCR for Q1 2026 has been APPROVED', 'view_record.php?id=8', 0, '2026-01-06 16:38:13'),
(14, 9, 'New form submission requires your review', 'view_record.php?id=9', 0, '2026-01-06 20:53:22'),
(15, 9, 'New form submission requires your review', 'view_record.php?id=43', 0, '2026-01-09 01:35:07'),
(16, 9, 'New form submission requires your review', 'view_record.php?id=44', 0, '2026-01-11 18:55:16'),
(17, 9, 'New form submission requires your review', 'view_record.php?id=45', 0, '2026-02-04 02:46:52'),
(18, 9, 'New form submission requires your review', 'view_record.php?id=46', 0, '2026-02-04 04:08:25'),
(19, 9, 'New form submission requires your review', 'view_record.php?id=47', 0, '2026-02-04 04:14:11'),
(20, 9, 'New form submission requires your review', 'view_record.php?id=49', 0, '2026-02-10 17:28:08'),
(21, 9, 'New form submission requires your review', 'view_record.php?id=50', 0, '2026-02-10 21:52:33'),
(22, 9, 'New form submission requires your review', 'view_record.php?id=54', 0, '2026-02-11 01:04:10'),
(23, 75, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=53', 0, '2026-02-15 19:14:34'),
(24, 75, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=53', 0, '2026-02-15 19:18:53'),
(25, 75, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=53', 0, '2026-02-15 19:22:49'),
(26, 75, 'Your IPCR for Q1 2026 has been For Revision', 'view_record.php?id=53', 0, '2026-02-15 19:23:33'),
(27, 75, 'Your IPCR for Q1 2026 has been APPROVED', 'view_record.php?id=53', 0, '2026-02-15 19:49:53'),
(28, 69, 'Your IPCR for Q2 2026 has been For Revision', 'view_record.php?id=39', 0, '2026-02-15 19:57:47');

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
  `document_status` varchar(50) NOT NULL DEFAULT 'Draft',
  `date_submitted` timestamp NULL DEFAULT NULL,
  `date_approved` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `date_reviewed` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `computation_type` enum('Type1','Type2') DEFAULT 'Type1',
  `feedback` varchar(255) DEFAULT NULL,
  `confidential_remarks` varchar(255) DEFAULT NULL,
  `position` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `records`
--

INSERT INTO `records` (`id`, `user_id`, `form_type`, `period`, `content`, `document_status`, `date_submitted`, `date_approved`, `reviewed_by`, `date_reviewed`, `comments`, `computation_type`, `feedback`, `confidential_remarks`, `position`, `created_by`, `date_created`) VALUES
(35, 69, 'IPCR', 'Q4 2026', '{\"period\":\"Q4 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"2\",\"success_indicators\":\"23123wqeqweqw\",\"remarks\":\"eewqe123\",\"accomplishments\":\"231123\",\"q\":\"4\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.33\",\"supervisor_q\":\"2\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.00\"}],\"core_functions\":[{\"mfo\":\"\",\"success_indicators\":\"<-emptyqweqweqw\",\"remarks\":\"e2312asdqweqw3wqe\",\"accomplishments\":\"23123123\",\"q\":\"2\",\"e\":\"5\",\"t\":\"1\",\"a\":\"2.67\",\"supervisor_q\":\"4\",\"supervisor_e\":\"2\",\"supervisor_t\":\"4\",\"supervisor_a\":\"3.33\"}],\"support_functions\":[],\"summary\":{\"strategic_average\":\"1.35\",\"core_average\":\"1.47\",\"support_average\":\"0.00\",\"final_rating\":\"2.82\",\"adjectival_rating\":\"Satisfactory\"},\"dh_comments\":\"asdasdasd\",\"supervisor_summary\":{\"strategic_average\":\"0.90\",\"core_average\":\"1.83\",\"support_average\":\"0.00\",\"final_rating\":\"2.73\",\"adjectival_rating\":\"Satisfactory\"}}', 'Approved', '2026-01-08 21:03:33', '2026-01-08 21:03:33', NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-01-09 03:42:03'),
(39, 69, 'IPCR', 'Q2 2026', '{\"period\":\"Q2 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwew\",\"success_indicators\":\"wqeqwe\",\"remarks\":\"wqe\",\"accomplishments\":\"wqeqwe\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[],\"support_functions\":[],\"summary\":{\"strategic_average\":\"1.05\",\"core_average\":\"0.00\",\"support_average\":\"0.00\",\"final_rating\":\"1.05\",\"adjectival_rating\":\"Poor\"}}', 'For Revision', '2026-01-09 01:24:59', NULL, 9, '2026-02-15 19:57:47', NULL, 'Type1', NULL, '', '', 9, '2026-01-09 05:21:11'),
(41, 69, 'IPCR', 'Q3 2026', '{\"period\":\"Q3 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"remarks\":\"qwe\",\"accomplishments\":\"gotit\",\"q\":\"2\",\"e\":\"2\",\"t\":\"3\",\"a\":\"2.33\",\"supervisor_q\":\"1\",\"supervisor_e\":\"1\",\"supervisor_t\":\"2\",\"supervisor_a\":\"1.33\"}],\"core_functions\":[{\"mfo\":\"asdas\",\"success_indicators\":\"qwe\",\"remarks\":\"qwe\",\"accomplishments\":\"ok\",\"q\":\"2\",\"e\":\"3\",\"t\":\"3\",\"a\":\"2.67\",\"supervisor_q\":\"5\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"4.33\"}],\"support_functions\":[],\"summary\":{\"strategic_average\":\"1.05\",\"core_average\":\"1.47\",\"support_average\":\"0.00\",\"final_rating\":\"2.52\",\"adjectival_rating\":\"Satisfactory\"},\"dh_comments\":\"testsetse\",\"supervisor_summary\":{\"strategic_average\":\"0.60\",\"core_average\":\"2.38\",\"support_average\":\"0.00\",\"final_rating\":\"2.98\",\"adjectival_rating\":\"Satisfactory\"}}', 'For Revision', '2026-01-09 01:54:21', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-01-09 05:28:40'),
(50, 75, 'IDP', 'January 2026 - June 2026', '{\"idp_goals\":[{\"objective\":\"Test Main Objective\",\"action_plan\":\"Test Plan of Action\",\"status\":\"Not Started\",\"date_accomplished\":\"February 12 2026\",\"results\":\"Accomplished.\\r\\nI did it.\"}]}', 'Approved', '2026-02-10 21:52:33', '2026-02-10 22:08:07', 9, '2026-02-10 22:08:07', NULL, 'Type1', NULL, NULL, '', NULL, '2026-02-11 05:52:33'),
(54, 9, 'IDP', 'July 2025 - December 2025', '{\"idp_goals\":[{\"objective\":\"dh idp test\",\"action_plan\":\"dh idp test action\",\"status\":\"Not Started\"}]}', 'Pending', '2026-02-11 01:04:10', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', NULL, '2026-02-11 09:04:10'),
(57, 9, 'DPCR', 'Q1 2026', '{\"computation_type\":\"Type1\",\"strategic_functions\":[{\"major_output\":\"dpcr test consolidate\",\"success_indicators\":\"dpcr test consolidate\",\"budget\":123,\"accountable\":\"dpcr\",\"category\":\"Strategic\",\"actual_accomplishments\":\"dpcr test consolidate\",\"q_rating\":1,\"e_rating\":1,\"t_rating\":1,\"a_rating\":1,\"remarks\":\"dpcr test consolidate\"}],\"core_functions\":[{\"major_output\":\"dpcr test consolidate\",\"success_indicators\":\"dpcr test consolidate\",\"budget\":321,\"accountable\":\"dpcr\",\"category\":\"Core\",\"actual_accomplishments\":\"dpcr test consolidate\",\"q_rating\":3,\"e_rating\":3,\"t_rating\":3,\"a_rating\":3,\"remarks\":\"dpcr test consolidate\"}],\"support_functions\":[]}', 'Pending', '2026-02-10 20:19:26', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', NULL, '2026-02-11 11:19:26'),
(58, 9, 'DPCR', 'January-June 2026', '{\"computation_type\":\"Type1\",\"strategic_functions\":[{\"major_output\":\"Governance and Administration\\r\\n\\r\\nEvaluation of the current organizational structure\",\"indicators\":[{\"success_indicators\":\"Formation of one technical working group to undertake the evaluation of the current organizational structure\",\"budget\":123,\"accountable\":\"OP  OVPA  OVPAA  OVPREQA  HRMO  BFO\",\"actual_accomplishments\":\"test\",\"q_rating\":1,\"e_rating\":1,\"t_rating\":1,\"a_rating\":1,\"remarks\":\"ttt\"},{\"success_indicators\":\"Creation of one evaluation report by the technical working group\",\"budget\":123,\"accountable\":\"OP  OVPA  OVPAA  OVPREQA  HRMO  BFO\",\"actual_accomplishments\":\"test\",\"q_rating\":2,\"e_rating\":1,\"t_rating\":2,\"a_rating\":1.67,\"remarks\":\"ttt\"}]}],\"core_functions\":[{\"major_output\":\"Community Extension\\r\\n\\r\\nSupport to the Angeles Watershed Reforestation Project through tree planting activity\",\"indicators\":[{\"success_indicators\":\"100%o of saplings allotted to the office successfully planted in Sitio Target, Sapangbato during the scheduled tree planting activity of the office\\/department\",\"budget\":3333,\"accountable\":\"CEO\",\"actual_accomplishments\":\"test\",\"q_rating\":5,\"e_rating\":5,\"t_rating\":5,\"a_rating\":5,\"remarks\":\"asdwasd\"}]}],\"support_functions\":[]}', 'Pending', '2026-02-10 21:42:06', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', NULL, '2026-02-11 12:42:06'),
(64, 75, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"test\",\"success_indicators\":\"qwe\",\"remarks\":\"qweqwe\",\"accomplishments\":\"2\",\"q\":\"3\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.33\",\"supervisor_q\":\"3\",\"supervisor_e\":\"2\",\"supervisor_t\":\"2\",\"supervisor_a\":\"2.33\"}],\"core_functions\":[{\"mfo\":\"test\",\"success_indicators\":\"qweqwe\",\"remarks\":\"qweqwqweqeqe\",\"accomplishments\":\"2\",\"q\":\"2\",\"e\":\"1\",\"t\":\"2\",\"a\":\"1.67\",\"supervisor_q\":\"2\",\"supervisor_e\":\"1\",\"supervisor_t\":\"1\",\"supervisor_a\":\"1.33\"}],\"support_functions\":[],\"summary\":{\"strategic_average\":\"1.05\",\"core_average\":\"0.92\",\"support_average\":\"0.00\",\"final_rating\":\"1.97\",\"adjectival_rating\":\"Unsatisfactory\"},\"dh_comments\":\"test dh comment\",\"supervisor_summary\":{\"strategic_average\":\"1.05\",\"core_average\":\"0.73\",\"support_average\":\"0.00\",\"final_rating\":\"1.78\",\"adjectival_rating\":\"Unsatisfactory\"}}', 'Approved', '2026-02-15 20:12:49', '2026-02-15 20:12:49', NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 04:07:26'),
(67, 75, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"final test\",\"success_indicators\":\"final test\",\"remarks\":\"final test remark\",\"accomplishments\":\"i test this\",\"q\":\"3\",\"e\":\"3\",\"t\":\"3\",\"a\":\"3.00\",\"supervisor_q\":\"4\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"4.00\"}],\"core_functions\":[{\"mfo\":\"final test\",\"success_indicators\":\"final test\",\"remarks\":\"final test remark\",\"accomplishments\":\"i test this\",\"q\":\"2\",\"e\":\"2\",\"t\":\"2\",\"a\":\"2.00\",\"supervisor_q\":\"1\",\"supervisor_e\":\"4\",\"supervisor_t\":\"4\",\"supervisor_a\":\"3.00\"}],\"support_functions\":[],\"summary\":{\"strategic_average\":\"1.35\",\"core_average\":\"1.10\",\"support_average\":\"0.00\",\"final_rating\":\"2.45\",\"adjectival_rating\":\"Unsatisfactory\"},\"dh_comments\":\"hi comment\",\"supervisor_summary\":{\"strategic_average\":\"1.80\",\"core_average\":\"1.65\",\"support_average\":\"0.00\",\"final_rating\":\"3.45\",\"adjectival_rating\":\"Satisfactory\"}}', 'Approved', '2026-02-15 20:18:42', '2026-02-15 20:18:42', NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 04:16:05'),
(69, 71, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"2\",\"success_indicators\":\"2\",\"remarks\":\"qwe\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"2\",\"success_indicators\":\"2\",\"remarks\":\"qwe\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 04:52:18'),
(70, 75, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"2\",\"success_indicators\":\"2\",\"remarks\":\"qwe\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"2\",\"success_indicators\":\"2\",\"remarks\":\"qwe\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 04:52:18'),
(71, 9, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qqq\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 05:01:32'),
(72, 69, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qqq\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 05:01:32'),
(73, 71, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qqq\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 05:01:32'),
(74, 75, 'IPCR', 'Q1 2026', '{\"period\":\"Q1 2026\",\"computation_type\":\"Type1\",\"strategic_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qwe\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"core_functions\":[{\"mfo\":\"qwe\",\"success_indicators\":\"qqq\",\"remarks\":\"123\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"supervisor_q\":\"\",\"supervisor_e\":\"\",\"supervisor_t\":\"\",\"supervisor_a\":\"\"}],\"support_functions\":[]}', 'Distributed', NULL, NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', 9, '2026-02-16 05:01:32'),
(75, 2, 'DPCR', 'January-June 2026', '{\"computation_type\":\"Type1\",\"strategic_functions\":[{\"major_output\":\"test DPCR\",\"indicators\":[{\"success_indicators\":\"test DPCR\",\"budget\":1000,\"accountable\":\"test DPCR\",\"actual_accomplishments\":\"test DPCR\",\"q_rating\":3,\"e_rating\":3,\"t_rating\":3,\"a_rating\":3,\"remarks\":\"test DPCR\"},{\"success_indicators\":\"test DPCR\",\"budget\":1000,\"accountable\":\"OVA\",\"actual_accomplishments\":\"test DPCR\",\"q_rating\":2,\"e_rating\":2,\"t_rating\":2,\"a_rating\":2,\"remarks\":\"test DPCR\"}]}],\"core_functions\":[{\"major_output\":\"Testing\",\"indicators\":[{\"success_indicators\":\"test DPCR\",\"budget\":5000,\"accountable\":\"Mark\",\"actual_accomplishments\":\"test DPCR\",\"q_rating\":4,\"e_rating\":4,\"t_rating\":4,\"a_rating\":4,\"remarks\":\"test DPCR\"}]}],\"support_functions\":[]}', 'Pending', '2026-02-15 14:58:14', NULL, NULL, NULL, NULL, 'Type1', NULL, NULL, '', NULL, '2026-02-16 05:58:14'),
(76, 11, 'DPCR', 'January-June 2026', '{\"computation_type\":\"Type2\",\"strategic_functions\":[{\"major_output\":\"mfo dpcr\",\"indicators\":[{\"success_indicators\":\"si dpcr\",\"budget\":123,\"accountable\":\"a dpcr\",\"actual_accomplishments\":\"aa dpcr\",\"q_rating\":2,\"e_rating\":2,\"t_rating\":2,\"a_rating\":2,\"remarks\":\"r dpcr\"}]},{\"major_output\":\"test\",\"indicators\":[{\"success_indicators\":\"test\",\"budget\":444,\"accountable\":\"test\",\"actual_accomplishments\":\"test\",\"q_rating\":2,\"e_rating\":1,\"t_rating\":2,\"a_rating\":1.67,\"remarks\":\"test\"}]}],\"core_functions\":[{\"major_output\":\"testtesttest\",\"indicators\":[{\"success_indicators\":\"test\",\"budget\":1000,\"accountable\":\"qweqwe\",\"actual_accomplishments\":\"test\",\"q_rating\":5,\"e_rating\":5,\"t_rating\":5,\"a_rating\":5,\"remarks\":\"test\"}]}],\"support_functions\":[{\"major_output\":\"SF test\",\"indicators\":[{\"success_indicators\":\"SF test\",\"budget\":51231,\"accountable\":\"SF test\",\"actual_accomplishments\":\"SF test\",\"q_rating\":3,\"e_rating\":3,\"t_rating\":3,\"a_rating\":3,\"remarks\":\"SF test\"}]}]}', 'Pending', '2026-02-15 15:35:00', NULL, NULL, NULL, NULL, 'Type2', NULL, NULL, '', NULL, '2026-02-16 06:35:00');

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
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `position`, `department_id`, `avatar`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'asantos@cca.edu.ph', 'asd', 'admin', '', 1, NULL, '72a75718bd6480c31455f7a051486ddf33631d0b7d443652d1e78afe321032c8', '2025-04-18 04:51:59', '2026-02-10 19:11:36'),
(2, 'Arnie Santos', 'arniesantos@cca.edu.ph', 'qwe', 'department_head', '', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-12-15 09:44:58'),
(5, 'HR Manager', 'hrmanager@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', '', 2, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(6, 'Faculty Member', 'faculty@cca.edu.ph', '$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea', 'regular_employee', '', 1, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(7, 'DR. CAROLINA A. SARMIENTO', 'carolinasarmiento@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'president', '', 6, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(8, 'MS. AMOR L. BARBA', 'amorbarba@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', '', 4, NULL, NULL, '2025-04-18 04:51:59', '2025-11-02 13:10:41'),
(9, 'MS. MAIKA V. GARBES', 'maikagarbes@cca.edu.ph', 'asd', 'department_head', 'ICSLIS Department Head', 8, NULL, 'ea0a7298bf7433bf30e7ab2390577223cbad5f7f73a51342de01782b1128d7b6', '2025-04-18 04:51:59', '2026-01-06 17:41:38'),
(10, 'DR. LEVITA DE GUZMAN', 'levitaguzman@cca.edu.ph', 'qwe', 'department_head', '', 7, NULL, NULL, '2025-04-18 04:51:59', '2025-11-02 13:33:11'),
(11, 'MS. MARIA TERESSA G. LAPUZ', 'mariateressalapuz@cca.edu.ph', '$2y$10$0KYo3agSEk/yYFMNCFTpT.pkAarD7Y5xfLVeswf7YpbTpVz7vIb2m', 'department_head', 'SASO Department Head', 10, NULL, NULL, '2025-04-18 04:51:59', '2026-02-15 22:32:38'),
(12, 'MR. LESSANDRO YUCON', 'lessandroyucon@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', '', 11, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(13, 'MS. JASMINE ANGELICA MARIE CANLAS', 'jasmineangelicacanlas@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', '', 12, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(14, 'DR. RHENAN ESTACIO', 'rhenanestacio@cca.edu.ph', '$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele', 'department_head', '', 13, NULL, NULL, '2025-04-18 04:51:59', '2025-04-18 04:51:59'),
(15, 'ADMIN NGA KOOO', 'admin@cca.edu.ph', 'admin123', 'admin', '', 1, 'uploads/avatars/7_6801c5d913a3f.jpg', '', '2025-04-18 02:34:36', '2025-04-18 03:24:09'),
(16, 'Benedict Ortiz', 'jortiz@cca.edu.ph', '$2y$10$ucBn/VaiNqycSyxaxSYGrOGkFSWuaXicSVT/Ftcmu8XQQG9pjHxWm', 'regular_employee', '', 1, 'uploads/avatars/9_6801cd09df7d1.jpg', '', '2025-04-18 02:43:16', '2025-04-18 03:54:49'),
(17, 'Antonio Luna', 'henlun@cca.edu.ph', 'qwe', 'president', '', 2, 'uploads/avatars/12_6801c17a2cd64.jpg', '9bb4466b58b414e07f04261cd68158941888a531e08de034221229958b63057d', '2025-04-18 03:04:29', '2025-12-15 09:19:58'),
(69, 'Qweqweqwe', 'test@cca.edu.ph', 'qwe', 'regular_employee', 'BSCS Program Coordinator', 8, 'uploads/avatars/9_6801cd09df7d1.jpg', '4e5b540b435ef51a3151dfc7079bc577e02bbe7f17eabb6c42925d77b8e74aab', '2025-04-18 02:43:16', '2026-02-04 00:49:35'),
(70, 'qwe', 'qwe@gmail.com', 'qwe', 'department_head', '', 3, NULL, NULL, '2025-10-13 22:36:38', '2026-02-15 20:55:30'),
(71, 'reset', 'reset@gmail.com', '$2y$10$wgGFje6boxFPRoW/wEq0V.DUZUyg0OV/F48Rv7hvsv/Tb7.u.J2KS', 'regular_employee', '', 8, NULL, '05d080eaf55cacad5e692bdf21ecbf1879d44bcfc144533a18cce37c58ece08c', '2025-10-21 16:35:36', '2025-10-21 17:58:30'),
(72, 'wda', 'wdasd@gmail.com', '$2y$10$K3IoEFwHAXaE3idzO.rtouWAohfiIeib22vaMMl0JUeQyWt3vRO.e', 'regular_employee', '', 7, NULL, NULL, '2025-11-02 13:11:14', '2025-11-02 13:11:14'),
(73, 'Justine Employee', 'jus.emp@email.com', '$2y$10$6xMckugbjRFdftZfERT2KusW7wuKkaZub.36iDxX6nlP6m77EIxIu', 'regular_employee', '', 5, NULL, NULL, '2025-11-03 13:42:44', '2025-11-03 13:44:19'),
(74, 'Mark Dep Head', 'mark.dep.head@email.com', '$2y$10$Lxg3tUNpndMPNyffidqiZ.HI.R/hz/PGSOjCrgCH8ahfHSiAyU6LC', 'department_head', '', 5, NULL, NULL, '2025-11-03 13:45:53', '2025-11-03 13:45:53'),
(75, 'Mark Pagado', 'mapagado@cca.edu.ph', '$2y$10$m7Z0ufVloIraiH9GeLLKn.gAymTBJKVvIJtfOwf72hiIKVl9SterW', 'regular_employee', '', 8, NULL, NULL, '2026-02-10 21:19:10', '2026-02-10 21:28:45');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `idp_entries`
--
ALTER TABLE `idp_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ipcr_entries`
--
ALTER TABLE `ipcr_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `pds_records`
--
ALTER TABLE `pds_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `records`
--
ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

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
