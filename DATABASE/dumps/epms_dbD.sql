-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: epms_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `head_id` (`head_id`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'ICSLIS - Institute of Computing Studies and Library Information Sciences','Department for computing and library science programs',NULL,'2025-04-18 04:51:59','2025-04-18 04:53:17'),(2,'Human Resources','Manages employee relations and workforce planning',5,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'Finance','Handles financial operations and budgeting',NULL,'2025-04-18 04:51:59','2025-04-18 04:52:48'),(4,'Academic Affairs','Oversees academic programs and policies',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(5,'Student Services','Provides support services for students',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'Academic Affairs','Oversees academic programs, faculty, and educational policies',7,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'Institute of Business and Management','Department for business programs and management education',8,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'Institute of Computing Studies and Library Information Science','Department for computing and library science programs',9,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'Institute of Education, Arts and Sciences','Department for education, arts and sciences programs',10,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(10,'Student Affairs and Services Office','Provides support services for students',11,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(11,'Admissions and Registrar\'s Office','Manages student admissions and registration',12,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(12,'College Library','Provides library resources and services',13,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(13,'College Guidance and Formation Office','Provides guidance and counseling services',14,'2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dpcr_entries`
--

DROP TABLE IF EXISTS `dpcr_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dpcr_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `dpcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dpcr_entries`
--

LOCK TABLES `dpcr_entries` WRITE;
/*!40000 ALTER TABLE `dpcr_entries` DISABLE KEYS */;
INSERT INTO `dpcr_entries` VALUES (1,1,'Curriculum Development','Update CS curriculum by Q2',50000.00,'ICSLIS Department','Curriculum updated ahead of schedule','Strategic',5.00,0.00,0.00,0.00,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,1,'Faculty Training Program','Train faculty on new technologies',75000.00,'ICSLIS Department','Training completed with 95% attendance','Core',4.00,0.00,0.00,0.00,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,7,'Sample','wa',10000.00,'32',NULL,'Strategic',NULL,NULL,NULL,NULL,NULL,'2025-04-18 04:55:16','2025-04-18 04:55:16'),(5,7,'same','oo',505005.00,'3123',NULL,'Core',NULL,NULL,NULL,NULL,NULL,'2025-04-18 04:55:16','2025-04-18 04:55:16'),(6,11,'EWAN','WALA PA',50000.00,'5000',NULL,'Strategic',NULL,NULL,NULL,NULL,NULL,'2025-04-18 06:16:16','2025-04-18 06:16:16'),(7,11,'HI MAYOR!','DIKO ALAM',100000.00,'3000',NULL,'Core',NULL,NULL,NULL,NULL,NULL,'2025-04-18 06:16:16','2025-04-18 06:16:16');
/*!40000 ALTER TABLE `dpcr_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `idp_entries`
--

DROP TABLE IF EXISTS `idp_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `idp_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `idp_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `idp_entries`
--

LOCK TABLES `idp_entries` WRITE;
/*!40000 ALTER TABLE `idp_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `idp_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ipcr_entries`
--

DROP TABLE IF EXISTS `ipcr_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ipcr_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `record_id` (`record_id`),
  CONSTRAINT `ipcr_entries_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ipcr_entries`
--

LOCK TABLES `ipcr_entries` WRITE;
/*!40000 ALTER TABLE `ipcr_entries` DISABLE KEYS */;
INSERT INTO `ipcr_entries` VALUES (1,2,'Course Materials Development','Develop 3 new lab exercises','Developed 4 new lab exercises with comprehensive guides',4.00,5.00,4.00,4.00,NULL,'Core',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,2,'Student Mentorship','Mentor at least 5 students','Mentored 7 students, with 2 winning in competitions',5.00,5.00,5.00,5.00,NULL,'Strategic',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,5,'Technology Workshop','Conduct 2 workshops for students','Conducted 1 workshop with 30 attendees',3.00,4.00,3.00,3.00,NULL,'Support',NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,8,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(5,8,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(6,8,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 04:55:58','2025-04-18 04:55:58'),(7,9,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(8,9,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(9,9,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 04:56:10','2025-04-18 04:56:10'),(10,10,'HAHAHAHAHA','HAHAHAH','OK NA TO',4.00,3.00,5.00,4.00,'OK','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(11,10,'AHHAHAHAH','2','OK NA TO',5.00,5.00,5.00,5.00,'OK','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(12,10,'sample','2','OK NA TO',1.00,2.00,1.00,1.00,'GG','Core',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(13,10,'HAHAHAHHAHA','OKOKOK','OK NA TO',1.00,4.00,2.00,2.00,'NO','Support',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13'),(14,10,'OK NA TO','OK NA TO','OK NA TO',1.00,1.00,3.00,1.00,'OK NA TO','Support',NULL,'2025-04-18 06:10:13','2025-04-18 06:10:13');
/*!40000 ALTER TABLE `ipcr_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,9,'New form submission requires your review','view_record.php?id=14',0,'2025-10-13 22:31:55');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pds_records`
--

DROP TABLE IF EXISTS `pds_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pds_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pds_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Stores the complete PDS data as a JSON object' CHECK (json_valid(`pds_data`)),
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pds_records`
--

LOCK TABLES `pds_records` WRITE;
/*!40000 ALTER TABLE `pds_records` DISABLE KEYS */;
INSERT INTO `pds_records` VALUES (1,69,'{\n    \"personal_info\": {\n        \"surname\": \"qwe\",\n        \"first_name\": \"qwe\",\n        \"middle_name\": \"qwe\",\n        \"dob\": \"2025-01-14\",\n        \"pob\": \"qwe\",\n        \"sex\": \"Male\",\n        \"civil_status\": \"Single\",\n        \"height\": \"123\",\n        \"weight\": \"123\",\n        \"blood_type\": \"123\",\n        \"gsis_id\": \"123\",\n        \"pagibig_id\": \"13\",\n        \"philhealth_no\": \"123\",\n        \"sss_no\": \"123\",\n        \"tin_no\": \"123\",\n        \"agency_employee_no\": \"123\",\n        \"residential_address\": \"123\",\n        \"res_zip_code\": \"123\",\n        \"permanent_address\": \"123\",\n        \"perm_zip_code\": \"123\",\n        \"tel_no_res\": \"123\",\n        \"mobile_no\": \"123\",\n        \"email_address\": \"123@gmail.com\"\n    },\n    \"family_background\": {\n        \"spouse_surname\": \"123\",\n        \"spouse_first_name\": \"123\",\n        \"spouse_middle_name\": \"123\",\n        \"spouse_occupation\": \"\",\n        \"spouse_employer\": \"\",\n        \"father_surname\": \"123\",\n        \"father_first_name\": \"123\",\n        \"father_middle_name\": \"123\",\n        \"mother_surname\": \"123\",\n        \"mother_first_name\": \"123\",\n        \"mother_middle_name\": \"123\"\n    },\n    \"conditional\": {\n        \"q34\": \"No\",\n        \"q34_details\": \"\",\n        \"q35a\": \"No\",\n        \"q35a_details\": \"\",\n        \"q35b\": \"No\",\n        \"q35b_details\": \"\",\n        \"q36\": \"No\",\n        \"q36_details\": \"\",\n        \"q37\": \"No\",\n        \"q37_details\": \"\",\n        \"q38\": \"No\",\n        \"q38_details\": \"\",\n        \"q39\": \"No\",\n        \"q39_details\": \"\",\n        \"q40\": \"No\",\n        \"q40_details\": \"\",\n        \"q41\": \"No\",\n        \"q41_details\": \"\",\n        \"q42a\": \"No\",\n        \"q42a_details\": \"\",\n        \"q42b\": \"No\",\n        \"q42b_details\": \"\",\n        \"q42c\": \"No\",\n        \"q42c_details\": \"\"\n    },\n    \"references\": {\n        \"1\": {\n            \"name\": \"123\",\n            \"address\": \"123\",\n            \"tel\": \"123\"\n        },\n        \"2\": {\n            \"name\": \"123\",\n            \"address\": \"213\",\n            \"tel\": \"13\"\n        },\n        \"3\": {\n            \"name\": \"231\",\n            \"address\": \"123123\",\n            \"tel\": \"123\"\n        }\n    },\n    \"children\": [],\n    \"educational_background\": [],\n    \"eligibility\": [],\n    \"work_experience\": [],\n    \"voluntary_work\": [],\n    \"learning_dev\": [],\n    \"other_skills\": [],\n    \"non_academic_distinctions\": [],\n    \"membership_in_assoc\": []\n}','2025-10-13 22:22:31','2025-10-13 22:34:38');
/*!40000 ALTER TABLE `pds_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BSCS - Bachelor of Science in Computer Science',1,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,'BSIS - Bachelor of Science in Information Systems',1,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'BLIS - Bachelor of Library and Information Science',1,'2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `records`
--

DROP TABLE IF EXISTS `records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `records_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `records_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `records`
--

LOCK TABLES `records` WRITE;
/*!40000 ALTER TABLE `records` DISABLE KEYS */;
INSERT INTO `records` VALUES (1,2,'DPCR','Q1 2023',NULL,'Approved','2023-03-15 02:00:00',NULL,NULL,NULL,'Type1'),(2,2,'IPCR','Q1 2023',NULL,'Approved','2023-03-20 03:30:00',NULL,NULL,NULL,'Type1'),(5,6,'IPCR','Q2 2023',NULL,'Pending','2023-06-10 08:45:00',NULL,NULL,NULL,'Type1'),(6,2,'IPCR','Q3 2023',NULL,'Draft',NULL,NULL,NULL,NULL,'Type1'),(7,9,'DPCR','Q1 2025',NULL,'Pending','2025-04-17 22:55:16',NULL,NULL,NULL,'Type1'),(8,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1'),(9,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"},{\"mfo\":\"\",\"indicators\":\"\",\"accomplishments\":\"\",\"q\":\"\",\"e\":\"\",\"t\":\"\",\"a\":\"\",\"remarks\":\"\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1'),(10,16,'IPCR','Q1 2025','{\"period\":\"Q1 2025\",\"core_functions\":[{\"mfo\":\"HAHAHAHAHA\",\"indicators\":\"HAHAHAH\",\"accomplishments\":\"OK NA TO\",\"q\":\"4\",\"e\":\"3\",\"t\":\"5\",\"a\":\"4.00\",\"remarks\":\"OK\"},{\"mfo\":\"AHHAHAHAH\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"5\",\"e\":\"5\",\"t\":\"5\",\"a\":\"5.00\",\"remarks\":\"OK\"},{\"mfo\":\"sample\",\"indicators\":\"2\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"2\",\"t\":\"1\",\"a\":\"1.33\",\"remarks\":\"GG\"}],\"support_functions\":[{\"mfo\":\"HAHAHAHHAHA\",\"indicators\":\"OKOKOK\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"4\",\"t\":\"2\",\"a\":\"2.33\",\"remarks\":\"NO\"},{\"mfo\":\"OK NA TO\",\"indicators\":\"OK NA TO\",\"accomplishments\":\"OK NA TO\",\"q\":\"1\",\"e\":\"1\",\"t\":\"3\",\"a\":\"1.67\",\"remarks\":\"OK NA TO\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1'),(11,9,'DPCR','Q1 2025',NULL,'Approved','2025-04-18 00:16:16',17,'2025-04-18 06:17:02','pwede na nak','Type1'),(12,69,'IDP','Annual 2026','{\"period\":\"Annual 2026\",\"professional_development\":[{\"goals\":\"123\",\"competencies\":\"231\",\"actions\":\"123\",\"timeline\":\"ewr\",\"status\":\"Not Started\"},{\"goals\":\"213\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"ewr\",\"status\":\"Not Started\"}],\"personal_development\":[{\"goals\":\"123\",\"competencies\":\"123\",\"actions\":\"123\",\"timeline\":\"wer\",\"status\":\"Not Started\"}]}','Pending',NULL,NULL,NULL,NULL,'Type1'),(14,69,'IPCR','Q2 2025','{\"period\":\"Q2 2025\",\"strategic_functions\":[{\"mfo\":\"wdas\",\"indicators\":\"123\",\"accomplishments\":\"123\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"}],\"core_functions\":[{\"mfo\":\"312\",\"indicators\":\"213\",\"accomplishments\":\"213\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"}],\"support_functions\":[{\"mfo\":\"dfaw\",\"indicators\":\"ad\",\"accomplishments\":\"da\",\"q\":\"1\",\"e\":\"1\",\"t\":\"1\",\"a\":\"1.00\",\"remarks\":\"\"}],\"final_rating\":\"1.00\",\"strategic_average\":\"1.00\",\"core_average\":\"1.00\",\"support_average\":\"0.00\",\"rating_interpretation\":\"Poor\"}','Pending','2025-10-13 22:31:55',NULL,NULL,NULL,'Type1');
/*!40000 ALTER TABLE `records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'strategic_weight_type1','45','Strategic category weight for Type1 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(2,'core_weight_type1','55','Core category weight for Type1 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(3,'strategic_weight_type2','45','Strategic category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(4,'core_weight_type2','45','Core category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(5,'support_weight_type2','10','Support category weight for Type2 computation (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'quality_weight','35','Quality criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'efficiency_weight','35','Efficiency criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'timeliness_weight','30','Timeliness criteria weight for IPCR (%)','2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'dpcr_computation_type','Type1','DPCR computation type: Type1 = Strategic (45%) and Core (55%), Type2 = Strategic (45%), Core (45%), and Support (10%)','2025-04-18 04:51:59','2025-04-18 04:51:59');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','president','department_head','regular_employee','user') NOT NULL DEFAULT 'user',
  `department_id` int(11) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_department` (`department_id`),
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','asantos@gmail.com','asd','admin',1,NULL,NULL,'2025-04-18 04:51:59','2025-10-13 23:27:19'),(2,'Arnie Santos','arniesantos@cca.edu.ph','$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea','regular_employee',1,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(5,'HR Manager','hrmanager@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',2,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(6,'Faculty Member','faculty@cca.edu.ph','$2y$10$5M1sLMEQfuw9A4Xmn0n8g.YUEnfGBEw0W3Pn8KG2v7tCgY6l9A5Ea','regular_employee',1,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(7,'DR. CAROLINA A. SARMIENTO','carolinasarmiento@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','president',6,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(8,'MS. AMOR L. BARBA','amorbarba@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',7,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(9,'MS. MAIKA V. GARBES','maikagarbes@cca.edu.ph','asd','department_head',8,NULL,'ea0a7298bf7433bf30e7ab2390577223cbad5f7f73a51342de01782b1128d7b6','2025-04-18 04:51:59','2025-10-13 22:32:13'),(10,'DR. LEVITA DE GUZMAN','levitaguzman@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',9,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(11,'MS. MARIA TERESSA G. LAPUZ','mariateressalapuz@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',10,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(12,'MR. LESSANDRO YUCON','lessandroyucon@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',11,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(13,'MS. JASMINE ANGELICA MARIE CANLAS','jasmineangelicacanlas@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',12,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(14,'DR. RHENAN ESTACIO','rhenanestacio@cca.edu.ph','$2y$10$/YPShKmJ3eyhIugceCmqvOzwr8FncHsV7fjUKN8ewL56/cLiCcele','department_head',13,NULL,NULL,'2025-04-18 04:51:59','2025-04-18 04:51:59'),(15,'ADMIN NGA KOOO','admin@cca.edu.ph','admin123','admin',1,'uploads/avatars/7_6801c5d913a3f.jpg','','2025-04-18 02:34:36','2025-04-18 03:24:09'),(16,'Benedict Ortiz','jortiz@cca.edu.ph','$2y$10$ucBn/VaiNqycSyxaxSYGrOGkFSWuaXicSVT/Ftcmu8XQQG9pjHxWm','regular_employee',1,'uploads/avatars/9_6801cd09df7d1.jpg','','2025-04-18 02:43:16','2025-04-18 03:54:49'),(17,'Antonio Luna','henlun@cca.edu.ph','qwe','president',2,'uploads/avatars/12_6801c17a2cd64.jpg','','2025-04-18 03:04:29','2025-10-13 22:27:43'),(69,'test','test@test.com','qwe','regular_employee',8,'uploads/avatars/9_6801cd09df7d1.jpg','','2025-04-18 02:43:16','2025-10-20 20:22:10'),(70,'qwe','qwe@gmail.com','$2y$10$x9qJ91AMMJ.7KwXFi0w3He8tqEbWP.dwRowFQ7xDFOj18QFCX/p.O','regular_employee',9,NULL,NULL,'2025-10-13 22:36:38','2025-10-13 22:37:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-21  4:29:19
