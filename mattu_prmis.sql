-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Database: `mattu_tbmis`
--
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
-- Database: `mattu_prmis`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` varchar(20) NOT NULL,
  `patient_id` varchar(20) DEFAULT NULL,
  `doctor_id` varchar(20) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diagnoses`
--

CREATE TABLE `diagnoses` (
  `diagnosis_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `doctor_id` varchar(20) DEFAULT NULL,
  `diagnosis_details` text NOT NULL,
  `diagnosis_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `requires_follow_up` tinyint(1) DEFAULT 0,
  `follow_up_scheduled` tinyint(1) DEFAULT 0,
  `needs_vitals_review` tinyint(1) DEFAULT 0,
  `vitals_reviewed` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnoses`
--

INSERT INTO `diagnoses` (`diagnosis_id`, `visit_id`, `doctor_id`, `diagnosis_details`, `diagnosis_date`, `created_at`, `requires_follow_up`, `follow_up_scheduled`, `needs_vitals_review`, `vitals_reviewed`, `updated_at`) VALUES
('DX-373063cb', 'VST-2026-0006', 'STF-001', 'ddddd', '2026-02-24', '2026-02-25 08:30:38', 0, 0, 0, 0, '2026-02-26 05:15:13'),
('DX-A4554FEC', 'VST-2026-0028', 'STF-082', 'Patient presents with significant high fever and elevated blood pressure, with stable heart rate. Priority is to investigate cause of hyperthermia and initiate appropriate management (antipyretics, hydration, infection workup). Continuous monitoring of blood pressure is advised', '2026-03-01', '2026-03-01 21:31:15', 0, 0, 0, 0, '2026-03-01 21:31:15'),
('DX-DDE951BA', 'VST-2026-0019', 'STF-082', 'this again new record', '2026-02-25', '2026-02-25 08:46:00', 0, 0, 0, 0, '2026-02-26 05:15:13'),
('DX-FF0C5778', 'VST-2026-0022', 'STF-082', 'I have seen his symptoms he very nurvous', '2026-02-27', '2026-02-27 05:39:13', 0, 0, 0, 0, '2026-02-27 05:39:13');

-- --------------------------------------------------------

--
-- Table structure for table `discharges`
--

CREATE TABLE `discharges` (
  `discharge_id` varchar(20) NOT NULL,
  `patient_id` varchar(20) DEFAULT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `summary_of_care` text DEFAULT NULL,
  `condition_at_discharge` text DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `discharged_by` varchar(20) DEFAULT NULL,
  `discharged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `clerical_processed` tinyint(1) DEFAULT 0,
  `COLUMNcreated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `discharges`
--

INSERT INTO `discharges` (`discharge_id`, `patient_id`, `visit_id`, `summary_of_care`, `condition_at_discharge`, `instructions`, `follow_up_date`, `discharged_by`, `discharged_at`, `clerical_processed`, `COLUMNcreated_at`, `created_at`) VALUES
('DS-0DD82C', 'PAT-7c331', 'VST-2026-0019', 'rrrrrrrrrrrrrr', 'rrrrrrrrrrrrrrrr', 'rrrrrrrrrrrrrrrr', '0000-00-00', 'STF-082', '2026-02-26 21:40:44', 1, '2026-02-26 21:40:44', '2026-02-26 21:40:44'),
('DS-1B972E', 'PAT-7c331', 'VST-2026-0019', 'mmmmmmmmmmmmmmm', 'mmmmmmmmmmmmmmm', 'mmmmmmmmmmmmmmm', '0000-00-00', 'STF-082', '2026-02-26 21:16:59', 1, '2026-02-26 21:16:59', '2026-02-26 21:16:59'),
('DS-23533B', 'PAT-7c331', 'VST-2026-0019', '777', '7777', '7777', '0000-00-00', 'STF-082', '2026-02-26 20:40:58', 1, '2026-02-26 20:40:58', '2026-02-26 20:40:58'),
('DS-26CE3E', 'PAT-7c331', 'VST-2026-0019', 'qqq', 'qqq', 'qqqq', '0000-00-00', 'STF-082', '2026-02-26 19:51:16', 0, '2026-02-26 19:51:16', '2026-02-26 19:51:16'),
('DS-46DC98', 'PAT-7c331', 'VST-2026-0019', '2222', '222', '222', '0000-00-00', 'STF-082', '2026-02-26 20:36:40', 1, '2026-02-26 20:36:40', '2026-02-26 20:36:40'),
('DS-4D3210', 'PAT-10057', 'VST-2026-0006', 'Biruk is discharge', 'Biruk is discharge', 'Biruk is discharge', '0000-00-00', 'STF-082', '2026-02-26 22:14:42', 1, '2026-02-26 22:14:42', '2026-02-26 22:14:42'),
('DS-4F8EDF', 'PAT-950de', 'VST-2026-0021', 'enough', 'enough', 'enougn', '0000-00-00', 'STF-082', '2026-02-26 19:32:40', 0, '2026-02-26 19:32:40', '2026-02-26 19:32:40'),
('DS-5462D6', 'PAT-7c331', 'VST-2026-0019', 'mmmmmmmmmmmmmmm', 'mmmmmmmmmmmmmmm', 'mmmmmmmmmmmmmmm', '0000-00-00', 'STF-082', '2026-02-26 21:18:42', 1, '2026-02-26 21:18:42', '2026-02-26 21:18:42'),
('DS-605202', 'PAT-7c331', 'VST-2026-0019', '55555555', '5555555555', '55555555', '0000-00-00', 'STF-082', '2026-02-26 20:46:31', 1, '2026-02-26 20:46:31', '2026-02-26 20:46:31'),
('DS-6892FF', 'PAT-7c331', 'VST-2026-0019', '4444', '444', '4444', '0000-00-00', 'STF-082', '2026-02-26 20:31:47', 1, '2026-02-26 20:31:47', '2026-02-26 20:31:47'),
('DS-6C40E1', 'PAT-10060', 'VST-2026-0020', '333', '3333', '3333', '0000-00-00', 'STF-082', '2026-02-26 20:06:25', 1, '2026-02-26 20:06:25', '2026-02-26 20:06:25'),
('DS-782781', 'PAT-7c331', 'VST-2026-0019', 'ttttt', 'tttt', 'ttttt', '2026-02-21', 'STF-082', '2026-02-26 20:39:28', 1, '2026-02-26 20:39:28', '2026-02-26 20:39:28'),
('DS-84232F', 'PAT-7c331', 'VST-2026-0019', 'aaa', 'aaaa', 'aaaa', '0000-00-00', 'STF-082', '2026-02-26 19:50:37', 0, '2026-02-26 19:50:37', '2026-02-26 19:50:37'),
('DS-869F05', 'PAT-7c331', 'VST-2026-0019', 'sssss', 'sss', 'aaa', '0000-00-00', 'STF-082', '2026-02-26 19:34:44', 0, '2026-02-26 19:34:44', '2026-02-26 19:34:44'),
('DS-8DC475', 'PAT-7c331', 'VST-2026-0019', '5555', '5555', '5555', '0000-00-00', 'STF-082', '2026-02-26 21:03:41', 1, '2026-02-26 21:03:41', '2026-02-26 21:03:41'),
('DS-981F28', 'PAT-7c331', 'VST-2026-0019', 'dddd', 'ddd', 'ddd', '0000-00-00', 'STF-082', '2026-02-26 20:34:24', 1, '2026-02-26 20:34:24', '2026-02-26 20:34:24'),
('DS-9E76D6', 'PAT-7c331', 'VST-2026-0019', 'x-ray,is checked ,', 'now the health codition is okay', 'follow-up', '2026-03-14', 'STF-082', '2026-02-26 19:29:53', 0, '2026-02-26 19:29:53', '2026-02-26 19:29:53'),
('DS-ADDE29', 'PAT-7c331', 'VST-2026-0019', '333', '33', '33', '0000-00-00', 'STF-082', '2026-02-26 19:55:07', 0, '2026-02-26 19:55:07', '2026-02-26 19:55:07'),
('DS-B20523', 'PAT-7c331', 'VST-2026-0019', '5555', '555555', '5555555', '2026-02-28', 'STF-082', '2026-02-26 19:57:34', 0, '2026-02-26 19:57:34', '2026-02-26 19:57:34'),
('DS-C01FB0', 'PAT-7c331', 'VST-2026-0019', '5555', '555', '555', '0000-00-00', 'STF-082', '2026-02-26 20:32:14', 1, '2026-02-26 20:32:14', '2026-02-26 20:32:14'),
('DS-C5E1F5', 'PAT-7c331', 'VST-2026-0019', '2222', '222', '222', '0000-00-00', 'STF-082', '2026-02-26 19:54:25', 0, '2026-02-26 19:54:25', '2026-02-26 19:54:25'),
('DS-D48CD8', 'PAT-7c331', 'VST-2026-0019', 'discharged', 'discharged', 'discharged', '0000-00-00', 'STF-082', '2026-02-26 19:37:15', 0, '2026-02-26 19:37:15', '2026-02-26 19:37:15'),
('DS-DBA1F0', 'PAT-7c331', 'VST-2026-0019', '999999999', '9999999999', '999999999', '0000-00-00', 'STF-082', '2026-02-26 21:15:09', 1, '2026-02-26 21:15:09', '2026-02-26 21:15:09'),
('DS-EE81B6', 'PAT-7c331', 'VST-2026-0019', 'Active Patient Encounters', 'Active Patient Encounters', 'Active Patient Encounters', '0000-00-00', 'STF-082', '2026-02-26 21:49:01', 1, '2026-02-26 21:49:01', '2026-02-26 21:49:01'),
('DS-F1DCD0', 'PAT-7c331', 'VST-2026-0019', 'mmmmmmmm', 'mmmmmmmmmm', 'vmmmmmmmmmmmmmmm', '0000-00-00', 'STF-082', '2026-02-26 21:17:59', 1, '2026-02-26 21:17:59', '2026-02-26 21:17:59'),
('DS-F45E2E', 'PAT-7c331', 'VST-2026-0019', '888', '888', '888', '0000-00-00', 'STF-082', '2026-02-26 21:10:23', 1, '2026-02-26 21:10:23', '2026-02-26 21:10:23'),
('DS-F4E7F1', 'PAT-950de', 'VST-2026-0021', 'ssss', 'sss', 'sss', '0000-00-00', 'STF-082', '2026-02-26 19:31:06', 0, '2026-02-26 19:31:06', '2026-02-26 19:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `dispensing_records`
--

CREATE TABLE `dispensing_records` (
  `dispense_id` varchar(20) NOT NULL,
  `prescription_id` varchar(20) DEFAULT NULL,
  `pharmacist_id` varchar(20) DEFAULT NULL,
  `dispense_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dispensing_records`
--

INSERT INTO `dispensing_records` (`dispense_id`, `prescription_id`, `pharmacist_id`, `dispense_date`, `created_at`) VALUES
('DSP-0338BD', 'RX-DEDF27', 'STF-085', '2026-03-01 22:20:02', '2026-03-01 22:20:02'),
('DSP-890C37', 'RX-ED2ECB', 'STF-001', '2026-02-24 05:40:46', '2026-02-26 08:37:50'),
('DSP-B0652C', 'RX-65BBD6', 'STF-001', '2026-02-24 05:03:18', '2026-02-26 08:37:50'),
('DSP-FCFBEF', 'RX-32AA34', 'STF-001', '2026-02-24 06:01:37', '2026-02-26 08:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `lab_requests`
--

CREATE TABLE `lab_requests` (
  `request_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `doctor_id` varchar(20) DEFAULT NULL,
  `test_type` varchar(100) NOT NULL,
  `request_date` date NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('normal','STAT') DEFAULT 'normal',
  `patient_notified` tinyint(1) DEFAULT 0,
  `results_viewed` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_tech_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_requests`
--

INSERT INTO `lab_requests` (`request_id`, `visit_id`, `doctor_id`, `test_type`, `request_date`, `status`, `created_at`, `priority`, `patient_notified`, `results_viewed`, `updated_at`, `assigned_tech_id`) VALUES
('RAD-8FDD40', 'VST-2026-0019', 'STF-082', 'Urinalisis please', '2026-02-25', 'completed', '2026-02-25 09:04:07', 'normal', 0, 0, '2026-02-26 05:15:13', NULL),
('RAD-FBD3B2', 'VST-2026-0019', 'STF-082', 'chest x-ray', '2026-02-26', 'completed', '2026-02-26 14:18:44', 'normal', 0, 0, '2026-02-27 05:58:35', NULL),
('REQ-1C7C71', 'VST-2026-0019', 'STF-082', 'Urinalisis please', '2026-02-25', 'completed', '2026-02-25 05:56:58', 'normal', 0, 0, '2026-02-26 05:15:13', NULL),
('REQ-6F9503', 'VST-2026-0022', 'STF-082', 'RBC test', '2026-02-27', 'completed', '2026-02-27 05:39:56', 'normal', 0, 0, '2026-02-27 05:57:42', NULL),
('REQ-8BA440', 'VST-2026-0006', 'STF-001', 'dddd', '2026-02-24', 'completed', '2026-02-24 08:43:19', 'normal', 0, 0, '2026-02-27 05:59:34', NULL),
('REQ-CE915E', 'VST-2026-0022', 'STF-082', 'RBC test', '2026-02-27', 'completed', '2026-02-27 05:46:02', 'normal', 0, 0, '2026-02-27 05:57:14', NULL),
('REQ-E5D815', 'VST-2026-0019', 'STF-082', 'cbc', '2026-02-25', 'completed', '2026-02-25 09:03:43', 'normal', 0, 0, '2026-02-26 05:15:13', NULL),
('REQ-F0A2D4', 'VST-2026-0019', 'STF-082', 'kk', '2026-02-25', 'completed', '2026-02-25 09:21:17', 'normal', 0, 0, '2026-02-26 05:15:13', NULL),
('REQ-F7CFB9', 'VST-2026-0028', 'STF-082', 'CRP,CBC,Blood Culture,Urinalysis (± Culture)', '2026-03-01', 'completed', '2026-03-01 21:34:19', 'normal', 0, 0, '2026-03-01 22:05:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `result_id` varchar(20) NOT NULL,
  `request_id` varchar(20) DEFAULT NULL,
  `technician_id` varchar(20) DEFAULT NULL,
  `result_details` text NOT NULL,
  `performed_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_results`
--

INSERT INTO `lab_results` (`result_id`, `request_id`, `technician_id`, `result_details`, `performed_date`, `created_at`) VALUES
('RES-0F3676', 'REQ-8BA440', 'STF-082', 'this is also done', '2026-02-27', '2026-02-27 05:59:32'),
('RES-280B6C', 'RAD-FBD3B2', 'STF-082', 'please fixed', '2026-02-27', '2026-02-27 05:58:35'),
('RES-383CFE', 'REQ-F0A2D4', 'STF-082', 'Malaria negative', '2026-02-25', '2026-02-26 08:38:22'),
('RES-3AFD06', 'REQ-F7CFB9', 'STF-084', 'results show elevated white blood cell count (leukocytosis) with neutrophilia and significantly increased C-reactive protein (CRP).', '2026-03-01', '2026-03-01 22:05:20'),
('RES-B9D416', 'REQ-1C7C71', 'STF-082', 'are you okay the test is found pleas this require more radiology test', '2026-02-25', '2026-02-26 08:38:22'),
('RES-C05E11', 'REQ-CE915E', 'STF-082', 'this amasing concern', '2026-02-27', '2026-02-27 05:57:14'),
('RES-C92F1D', 'REQ-6F9503', 'STF-082', 'This will be just a chest pain', '2026-02-27', '2026-02-27 05:57:42'),
('RES-EE8D14', 'REQ-E5D815', 'STF-082', 'this okay', '2026-02-25', '2026-02-26 08:38:22'),
('RES-F3F15C', 'RAD-8FDD40', 'STF-082', 'nothin is happen', '2026-02-25', '2026-02-26 08:38:22');

-- --------------------------------------------------------

--
-- Table structure for table `medical_visits`
--

CREATE TABLE `medical_visits` (
  `visit_id` varchar(20) NOT NULL,
  `patient_id` varchar(20) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_type` enum('Outpatient','Emergency','Inpatient') NOT NULL,
  `clinical_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `triage_level` enum('Critical','Urgent','Non-Urgent') DEFAULT NULL,
  `status` enum('active','completed','cancelled') NOT NULL DEFAULT 'active',
  `triage_completed` tinyint(1) DEFAULT 0,
  `doctor_assigned` varchar(20) DEFAULT NULL,
  `doctor_completed` tinyint(1) DEFAULT 0,
  `assigned_doctor_id` varchar(20) DEFAULT NULL,
  `assigned_nurse_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_visits`
--

INSERT INTO `medical_visits` (`visit_id`, `patient_id`, `visit_date`, `visit_type`, `clinical_notes`, `created_at`, `triage_level`, `status`, `triage_completed`, `doctor_assigned`, `doctor_completed`, `assigned_doctor_id`, `assigned_nurse_id`) VALUES
('VST-2026-0001', 'PAT-ec269', '2026-02-18', 'Emergency', 'please this emergency', '2026-02-18 14:28:34', NULL, 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0002', 'PAT-83f0f', '2026-02-24', 'Inpatient', 'tHIS PATIENT IS ACTIVE', '2026-02-24 06:03:54', 'Urgent', 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0003', 'PAT-10056', '2026-02-24', 'Outpatient', '', '2026-02-24 08:35:34', NULL, 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0004', 'PAT-10056', '2026-02-24', 'Outpatient', '', '2026-02-24 08:36:15', NULL, 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0005', 'PAT-10083', '2026-02-24', 'Outpatient', 'this new patient', '2026-02-24 08:37:02', NULL, 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0006', 'PAT-10057', '2026-02-24', 'Outpatient', 'follow up', '2026-02-24 08:41:00', 'Critical', 'completed', 0, NULL, 1, NULL, NULL),
('VST-2026-0007', 'PAT-10056', '2026-02-24', 'Emergency', '', '2026-02-24 08:45:18', NULL, 'active', 0, NULL, 0, NULL, NULL),
('VST-2026-0019', 'PAT-7c331', '2026-02-24', 'Outpatient', '', '2026-02-24 20:59:53', NULL, 'completed', 0, NULL, 1, NULL, NULL),
('VST-2026-0020', 'PAT-10060', '2026-02-25', 'Outpatient', 'she is pregnant', '2026-02-25 21:24:19', NULL, 'completed', 0, NULL, 1, NULL, NULL),
('VST-2026-0021', 'PAT-950de', '2026-02-26', 'Outpatient', 'Follow up', '2026-02-26 09:02:53', NULL, 'completed', 0, NULL, 1, NULL, NULL),
('VST-2026-0022', 'PAT-10057', '2026-02-27', 'Outpatient', 'check up', '2026-02-27 05:34:54', NULL, 'active', 0, NULL, 0, NULL, 'STF-047'),
('VST-2026-0023', 'PAT-10057', '2026-02-28', 'Inpatient', 'follow up', '2026-02-28 05:53:10', NULL, 'active', 0, NULL, 0, 'STF-023', 'STF-042'),
('VST-2026-0024', 'PAT-7c331', '2026-02-28', 'Outpatient', 'out patient', '2026-02-28 07:57:01', NULL, 'active', 0, NULL, 0, 'STF-008', NULL),
('VST-2026-0025', 'PAT-10058', '2026-02-28', 'Emergency', 'follow upd', '2026-02-28 08:04:44', NULL, 'active', 0, NULL, 0, 'STF-014', NULL),
('VST-2026-0026', 'PAT-aebed', '2026-02-28', 'Emergency', 'jjjj', '2026-02-28 08:06:00', NULL, 'active', 0, NULL, 0, 'STF-025', NULL),
('VST-2026-0027', 'PAT-10058', '2026-03-01', 'Outpatient', 'General check up', '2026-03-01 20:32:11', NULL, 'active', 0, NULL, 0, 'STF-015', NULL),
('VST-2026-0028', 'PAT-10058', '2026-03-01', 'Outpatient', 'Routine Check up', '2026-03-01 21:22:23', NULL, 'active', 0, NULL, 0, 'STF-082', 'STF-049'),
('VST-2026-0029', 'PAT-10057', '2026-03-01', 'Outpatient', 'follow-up', '2026-03-01 21:24:13', NULL, 'active', 0, NULL, 0, 'STF-026', NULL),
('VST-2026-0030', 'PAT-10056', '2026-03-01', 'Outpatient', 'follow up', '2026-03-01 21:25:15', NULL, 'active', 0, NULL, 0, 'STF-082', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` varchar(20) NOT NULL,
  `medical_record_number` varchar(30) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_details` varchar(100) DEFAULT NULL,
  `registered_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `medical_record_number`, `full_name`, `age`, `gender`, `address`, `contact_details`, `registered_date`, `created_at`) VALUES
('PAT-00875', 'MRN-2026-0010', 'Tekle Berhan', 67, 'Male', 'Gondar', '0911111115', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-07404', 'MRN-2026-0013', 'Hirut Wolde', 41, 'Female', 'Dire Dawa', '0911111118', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-10056', 'MRN-2026-0061', 'Abebech Ayele', 68, 'Female', 'Addis Ababa, Bole', '0911111166', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10057', 'MRN-2026-0062', 'Biruk Tsegaye', 27, 'Male', 'Adama', '0911111167', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10058', 'MRN-2026-0063', 'Dinknesh Mekonnen', 31, 'Female', 'Bahir Dar', '0911111168', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10059', 'MRN-2026-0064', 'Ephrem Tesfaye', 46, 'Male', 'Gondar', '0911111169', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10060', 'MRN-2026-0065', 'Fikirte Hailu', 24, 'Female', 'Hawassa', '0911111170', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10061', 'MRN-2026-0066', 'Gebre Kristos', 71, 'Male', 'Jimma', '0911111171', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10062', 'MRN-2026-0067', 'Hiwot Ayele', 33, 'Female', 'Dire Dawa', '0911111172', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10063', 'MRN-2026-0068', 'Isayas Assefa', 49, 'Male', 'Mekelle', '0911111173', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10064', 'MRN-2026-0069', 'Kidist Desta', 38, 'Female', 'Debre Zeit', '0911111174', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10065', 'MRN-2026-0070', 'Lensa Tadesse', 29, 'Female', 'Shashemene', '0911111175', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10066', 'MRN-2026-0071', 'Mulugeta Assefa', 52, 'Male', 'Nekemte', '0911111176', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10067', 'MRN-2026-0072', 'Nardos Hailu', 26, 'Female', 'Assosa', '0911111177', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10068', 'MRN-2026-0073', 'Obsa Chala', 35, 'Male', 'Gambella', '0911111178', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10069', 'MRN-2026-0074', 'Penyel Fikre', 42, 'Female', 'Harar', '0911111179', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10070', 'MRN-2026-0075', 'Rediet Wondimu', 23, 'Female', 'Jijiga', '0911111180', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10071', 'MRN-2026-0076', 'Sisay Demeke', 44, 'Male', 'Dilla', '0911111181', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10072', 'MRN-2026-0077', 'Tirunesh Ayele', 57, 'Female', 'Wollega', '0911111182', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10073', 'MRN-2026-0078', 'Urgessa Fikadu', 39, 'Male', 'Arsi', '0911111183', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10074', 'MRN-2026-0079', 'Wagaye Desta', 31, 'Female', 'Bale', '0911111184', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10075', 'MRN-2026-0080', 'Yared Gebre', 45, 'Male', 'Sidama', '0911111185', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10076', 'MRN-2026-0081', 'Zufan Teshome', 28, 'Female', 'Afar', '0911111186', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10077', 'MRN-2026-0082', 'Alemitu Bekele', 62, 'Female', 'Benishangul', '0911111187', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10078', 'MRN-2026-0083', 'Biniyam Ayele', 33, 'Male', 'Somali', '0911111188', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10079', 'MRN-2026-0084', 'Bontu Olana', 27, 'Female', 'Addis Ababa, Bole', '0911111189', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10080', 'MRN-2026-0085', 'Chernet Asfaw', 41, 'Male', 'Addis Ababa, Kirkos', '0911111190', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10081', 'MRN-2026-0086', 'Desta Berhe', 55, 'Male', 'Addis Ababa, Yeka', '0911111191', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10082', 'MRN-2026-0087', 'Eden Yitbarek', 24, 'Female', 'Addis Ababa, Lideta', '0911111192', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10083', 'MRN-2026-0088', 'Fasika Tadesse', 36, 'Female', 'Addis Ababa, Arada', '0911111193', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10084', 'MRN-2026-0089', 'Genet Wondimu', 47, 'Female', 'Addis Ababa, Gulele', '0911111194', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10085', 'MRN-2026-0090', 'Henok Desta', 29, 'Male', 'Addis Ababa, Kolfe', '0911111195', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10086', 'MRN-2026-0091', 'Iman Ahmed', 34, 'Female', 'Addis Ababa, Nifas Silk', '0911111196', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10087', 'MRN-2026-0092', 'Jemal Hussein', 48, 'Male', 'Addis Ababa, Akaki', '0911111197', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10088', 'MRN-2026-0093', 'Kalkidan Mulugeta', 25, 'Female', 'Adama', '0911111198', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10089', 'MRN-2026-0094', 'Lioul Fikre', 32, 'Male', 'Bahir Dar', '0911111199', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10090', 'MRN-2026-0095', 'Mahlet Ayele', 28, 'Female', 'Gondar', '0911111200', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10091', 'MRN-2026-0096', 'Kidist Mengistu', 5, 'Female', 'Hawassa', '0911111201', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10092', 'MRN-2026-0097', 'Natnael Tekle', 8, 'Male', 'Jimma', '0911111202', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10093', 'MRN-2026-0098', 'Meron Tadesse', 3, 'Female', 'Dire Dawa', '0911111203', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10094', 'MRN-2026-0099', 'Yonatan Ayele', 12, 'Male', 'Mekelle', '0911111204', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10095', 'MRN-2026-0100', 'Betelhem Assefa', 15, 'Female', 'Debre Zeit', '0911111205', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10096', 'MRN-2026-0101', 'Zewdie Ayele', 82, 'Male', 'Shashemene', '0911111206', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10097', 'MRN-2026-0102', 'Worknesh Desta', 79, 'Female', 'Nekemte', '0911111207', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10098', 'MRN-2026-0103', 'Ayele Tadesse', 91, 'Male', 'Assosa', '0911111208', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10099', 'MRN-2026-0104', 'Almaz Worku', 85, 'Female', 'Gambella', '0911111209', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-10100', 'MRN-2026-0105', 'Gashaw Mengistu', 77, 'Male', 'Harar', '0911111210', '2026-02-24', '2026-02-24 06:42:12'),
('PAT-19921', 'MRN-2026-0001', 'Mengistu Tadesse', 18, 'Male', 'jimma', '091244444444444', '2026-02-17', '2026-02-17 19:02:05'),
('PAT-32751', 'MRN-2026-0011', 'Selam Tesfaye', 22, 'Female', 'Hawassa', '0911111116', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-45928', 'MRN-2026-0015', 'Azeb Hailu', 29, 'Female', 'Debre Zeit', '0911111120', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-53628', 'MRN-2026-0014', 'Gashaw Mengistu', 53, 'Male', 'Mekelle', '0911111119', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-57208', 'MRN-2026-0009', 'Meron Asfaw', 28, 'Female', 'Bahir Dar', '0911111114', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-61132', 'MRN-2026-0012', 'Yonas Desta', 35, 'Male', 'Jimma', '0911111117', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-72690', 'MRN-2026-0007', 'Tigist Haile', 32, 'Female', 'Addis Ababa, Kirkos', '0911111112', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-7c331', 'MRN-2026-0107', 'Bilise hailu', 10, 'Female', 'Geera ', '0923359252', '2026-02-24', '2026-02-24 09:37:57'),
('PAT-83f0f', 'MRN-2026-0005', 'Mootimoy tadesse', 23, 'Male', 'mattu', '0923359252', '2026-02-24', '2026-02-24 04:13:13'),
('PAT-89465', 'MRN-2026-0006', 'Abebe Kebede', 25, 'Male', 'Addis Ababa, Bole', '0911111111', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-95055', 'MRN-2026-0008', 'Dawit Mekonnen', 45, 'Male', 'Adama', '0911111113', '2026-02-24', '2026-02-24 06:39:37'),
('PAT-950de', 'MRN-2026-0110', 'New user', 10, 'Female', 'mattu', '0912825559', '2026-02-26', '2026-02-26 09:02:23'),
('PAT-aebed', 'MRN-2026-0108', 'Mengistu Tadesse', 17, 'Male', 'addis abeba', '0923359251', '2026-02-25', '2026-02-25 22:19:43'),
('PAT-c7e29', 'MRN-2026-0106', 'Waan ofii ofkalii', 120, '', '', '0912833333', '2026-02-24', '2026-02-24 09:35:21'),
('PAT-d4e74', 'MRN-2026-0109', 'of kalii ', 10, 'Male', 'Geera ', '0912444444', '2026-02-25', '2026-02-25 22:21:14'),
('PAT-ec269', 'MRN-2026-0004', 'Toolera Ayyanaa', 34, 'Male', 'addis abeba', '0912825555', '2026-02-18', '2026-02-18 13:09:22');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `medication_name` varchar(100) NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `prescribed_by` varchar(20) DEFAULT NULL,
  `is_dispensed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority` enum('normal','STAT') DEFAULT 'normal',
  `patient_notified` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_phr_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `visit_id`, `medication_name`, `dosage`, `prescribed_by`, `is_dispensed`, `created_at`, `priority`, `patient_notified`, `updated_at`, `assigned_phr_id`) VALUES
('RX-050A62', 'VST-2026-0019', 'HEMOGLOBIN', '500gm - Stat (Immediate)', 'STF-082', 0, '2026-02-25 07:37:31', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-19C944', 'VST-2026-0002', 'HEMOGLOBIN', '500gm - Stat (Immediate)', 'STF-001', 0, '2026-02-24 06:04:43', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-2B7BE1', 'VST-2026-0019', 'HEMOGLOBIN', '500gm - 1x1 (Once Daily)', 'STF-082', 0, '2026-02-25 07:41:33', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-2DF411', 'VST-2026-0019', 'HEMOGLOBIN', '500gm - 1x1 (Once Daily)', 'STF-082', 0, '2026-02-25 07:40:00', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-32AA34', 'VST-2026-0001', 'amoxaciling', '500gm - 1x1 (Once Daily)', 'STF-001', 1, '2026-02-18 14:39:23', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-4722E9', 'VST-2026-0019', 'amoxaciling', '500gm - Before Meals', 'STF-082', 0, '2026-02-25 07:26:15', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-65BBD6', 'VST-2026-0001', 'amoxaciling', '500gm - 1x2 (Twice Daily)', 'STF-001', 1, '2026-02-24 04:15:21', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-ACC184', 'VST-2026-0019', 'HEMOGLOBIN', '500gm - 1x1 (Once Daily)', 'STF-082', 0, '2026-02-25 07:22:59', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-CE284E', 'VST-2026-0019', 'amoxaciling', '500gm - 1x3 (Three Daily)', 'STF-082', 0, '2026-02-25 08:00:24', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-D142BA', 'VST-2026-0019', 'amoxaciling', '500gm - 1x1 (Once Daily)', 'STF-082', 0, '2026-02-25 07:50:40', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-DEDF27', 'VST-2026-0028', 'Ceftriaxone', '500gm - 1x1 (Once Daily)', 'STF-082', 1, '2026-03-01 22:16:53', 'normal', 0, '2026-03-01 22:20:02', NULL),
('RX-E73C59', 'VST-2026-0001', 'amoxaciling', '500gm - 1x1 (Once Daily)', 'STF-001', 0, '2026-02-18 14:44:18', 'normal', 0, '2026-02-26 05:15:13', NULL),
('RX-ED2ECB', 'VST-2026-0001', 'amoxaciling', '500gm - 1x1 (Once Daily)', 'STF-001', 1, '2026-02-18 14:45:54', 'normal', 0, '2026-02-26 05:15:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `radiology_requests`
--

CREATE TABLE `radiology_requests` (
  `request_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `doctor_id` varchar(20) DEFAULT NULL,
  `exam_type` varchar(100) NOT NULL,
  `body_part` varchar(100) DEFAULT NULL,
  `clinical_history` text DEFAULT NULL,
  `priority` enum('normal','STAT') DEFAULT 'normal',
  `status` enum('pending','processing','completed') DEFAULT 'pending',
  `request_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_rad_id` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `radiology_requests`
--

INSERT INTO `radiology_requests` (`request_id`, `visit_id`, `doctor_id`, `exam_type`, `body_part`, `clinical_history`, `priority`, `status`, `request_date`, `created_at`, `updated_at`, `assigned_rad_id`) VALUES
('RAD-205FE615', 'VST-2026-0019', 'STF-082', 'she is very happy', 'brain', 'she might be obseessed with happinees', 'normal', 'pending', '2026-02-26', '2026-02-26 14:38:14', '2026-02-26 14:38:14', NULL),
('RAD-34166B0C', 'VST-2026-0019', 'STF-082', 'what about her braind', 'have you seen that', '', 'normal', 'completed', '2026-02-26', '2026-02-26 19:01:54', '2026-02-26 19:03:00', NULL),
('RAD-5CA5784C', 'VST-2026-0028', 'STF-082', 'Chest X-ray (PA View)', 'Abdominal Ultrasound', 'cause of hyperthermia', 'STAT', 'completed', '2026-03-01', '2026-03-01 21:36:26', '2026-03-01 22:00:54', NULL),
('RAD-685B1F51', 'VST-2026-0022', 'STF-082', 'chest x-ray', 'brain', 'this my consern', 'normal', 'completed', '2026-02-27', '2026-02-27 05:55:52', '2026-02-27 06:01:04', NULL),
('RAD-9C08F4E7', 'VST-2026-0019', 'STF-082', 'chest x-ray', 'chest', 'the clinical history indicate that  his chest is hurt', 'normal', 'completed', '2026-02-26', '2026-02-26 14:27:36', '2026-02-26 18:57:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `radiology_results`
--

CREATE TABLE `radiology_results` (
  `result_id` varchar(20) NOT NULL,
  `request_id` varchar(20) DEFAULT NULL,
  `radiologist_id` varchar(20) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `impression` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `performed_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `radiology_results`
--

INSERT INTO `radiology_results` (`result_id`, `request_id`, `radiologist_id`, `findings`, `impression`, `image_path`, `performed_date`, `created_at`) VALUES
('RADRES-120809C0', 'RAD-34166B0C', 'STF-082', 'the brain also okay', 'The impression is fine', NULL, '2026-02-26', '2026-02-26 19:02:59'),
('RADRES-2FDDA039', 'RAD-5CA5784C', 'STF-087', 'Patchy consolidation noted in the right lower lung zone', 'indings suggest right lower lobe pneumonia.', 'uploads/radiology/RAD-5CA5784C_1772402454.jpg', '2026-03-01', '2026-03-01 22:00:54'),
('RADRES-4036FE74', 'RAD-685B1F51', 'STF-082', 'amasing finding g', '10%', 'uploads/radiology/RAD-685B1F51_1772172063.png', '2026-02-27', '2026-02-27 06:01:03'),
('RADRES-942EDD13', 'RAD-9C08F4E7', 'STF-082', 'sss', 'sss', 'uploads/radiology/RAD-9C08F4E7_1772132255.png', '2026-02-26', '2026-02-26 18:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` varchar(20) NOT NULL,
  `patient_id` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `visit_id` varchar(20) NOT NULL,
  `referring_facility` varchar(255) DEFAULT NULL,
  `referring_focal_point` varchar(100) DEFAULT NULL,
  `referring_phone` varchar(50) DEFAULT NULL,
  `source_doctor_id` varchar(20) NOT NULL,
  `target_facility` varchar(255) DEFAULT NULL,
  `target_focal_point` varchar(100) DEFAULT NULL,
  `target_phone` varchar(50) DEFAULT NULL,
  `target_department` varchar(100) NOT NULL,
  `priority` enum('Routine','Urgent','Emergency') DEFAULT 'Routine',
  `referral_type` enum('Inpatient','Outpatient','Community') DEFAULT 'Outpatient',
  `transportation_needs` text DEFAULT NULL,
  `follow_up_requirements` text DEFAULT NULL,
  `reason` text NOT NULL,
  `diagnoses_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diagnoses_json`)),
  `treatments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`treatments_json`)),
  `functional_status_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`functional_status_json`)),
  `compiled_by` varchar(100) DEFAULT NULL,
  `compiled_position` varchar(100) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Processed','Canceled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `referral_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `patient_id`, `date_of_birth`, `visit_id`, `referring_facility`, `referring_focal_point`, `referring_phone`, `source_doctor_id`, `target_facility`, `target_focal_point`, `target_phone`, `target_department`, `priority`, `referral_type`, `transportation_needs`, `follow_up_requirements`, `reason`, `diagnoses_json`, `treatments_json`, `functional_status_json`, `compiled_by`, `compiled_position`, `signature`, `status`, `created_at`, `referral_date`) VALUES
('REF-281FF5', 'PAT-10057', '1999-02-27', 'VST-2026-0022', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'To addis abeba', 'chest pain', '092434454555', 'Internal Medicine', 'Routine', 'Outpatient', 'fff', '', 'ffff', '{\"items\":[\"I have seen his symptoms he very nurvous\",\"ddddd\"],\"other\":\"f\"}', '{\"initiated\":\"\",\"items\":[],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"ffff\"},\"self_care\":\"Carer dependent\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"ff\",\"required\":\"ffff\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-02-27 14:48:22', '2026-02-27'),
('REF-3CA2A2', 'PAT-10058', '1995-03-01', 'VST-2026-0028', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'Jimma General hospital', 'chest pain', '092434454555', 'Orthopedics', 'Routine', 'Outpatient', '', '', 'Patient presenting with high-grade persistent fever (40.0°C) and laboratory findings suggestive of acute bacterial infection, with radiologic evidence of possible pneumonia. Referral made for specialist evaluation, further management, and advanced care.', '{\"items\":[\"Patient presents with significant high fever and elevated blood pressure, with stable heart rate. Priority is to investigate cause of hyperthermia and initiate appropriate management (antipyretics, hydration, infection workup). Continuous monitoring of blood pressure is advised\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[\"Monitor vital signs regularly,Ensure adequate hydration,Administer antipyretics.\"],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"\"},\"self_care\":\"\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-03-01 22:34:16', '2026-03-01'),
('REF-4C888E', 'PAT-10057', '1999-02-27', 'VST-2026-0022', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'To addis abeba', 'chest pain', '092434454555', 'Internal Medicine', 'Routine', 'Outpatient', '', '', 'we can\'t affor this please', '{\"items\":[\"I have seen his symptoms he very nurvous\",\"ddddd\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"Bed bound\",\"precautions\":\"is okay\"},\"self_care\":\"\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-02-27 10:08:21', '2026-02-27'),
('REF-7F95AE', 'PAT-10056', '1958-03-01', 'VST-2026-0030', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'To addis abeba', 'chest pain', '092434454555', 'ICU', 'Routine', 'Outpatient', 'y', '', 'This impossible', '{\"items\":[\"no diagnois\"],\"other\":\"\"}', '{\"initiated\":\"no treatment\",\"items\":[],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"Bed bound\",\"precautions\":\"\"},\"self_care\":\"Carer dependent\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-03-01 22:43:59', '2026-03-01'),
('REF-A5DCA8', 'PAT-10058', '1995-03-01', 'VST-2026-0028', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'Jimma General hospital', 'chest pain', '092434454555', 'Orthopedics', 'Routine', 'Outpatient', '', '', 'Patient presenting with high-grade persistent fever (40.0°C) and laboratory findings suggestive of acute bacterial infection, with radiologic evidence of possible pneumonia. Referral made for specialist evaluation, further management, and advanced care.', '{\"items\":[\"Patient presents with significant high fever and elevated blood pressure, with stable heart rate. Priority is to investigate cause of hyperthermia and initiate appropriate management (antipyretics, hydration, infection workup). Continuous monitoring of blood pressure is advised\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[\"Monitor vital signs regularly,Ensure adequate hydration,Administer antipyretics.\"],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"\"},\"self_care\":\"\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-03-01 22:41:23', '2026-03-01'),
('REF-B41439', 'PAT-10058', '1995-03-01', 'VST-2026-0028', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'Jimma General hospital', 'chest pain', '092434454555', 'Orthopedics', 'Routine', 'Outpatient', '', '', 'Patient presenting with high-grade persistent fever (40.0°C) and laboratory findings suggestive of acute bacterial infection, with radiologic evidence of possible pneumonia. Referral made for specialist evaluation, further management, and advanced care.', '{\"items\":[\"Patient presents with significant high fever and elevated blood pressure, with stable heart rate. Priority is to investigate cause of hyperthermia and initiate appropriate management (antipyretics, hydration, infection workup). Continuous monitoring of blood pressure is advised\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[\"Monitor vital signs regularly,Ensure adequate hydration,Administer antipyretics.\"],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"\"},\"self_care\":\"\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-03-01 22:39:08', '2026-03-01'),
('REF-B86FB0', 'PAT-10058', '1995-03-01', 'VST-2026-0028', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'Jimma General hospital', 'chest pain', '092434454555', 'Orthopedics', 'Routine', 'Outpatient', '', '', 'Patient presenting with high-grade persistent fever (40.0°C) and laboratory findings suggestive of acute bacterial infection, with radiologic evidence of possible pneumonia. Referral made for specialist evaluation, further management, and advanced care.', '{\"items\":[\"Patient presents with significant high fever and elevated blood pressure, with stable heart rate. Priority is to investigate cause of hyperthermia and initiate appropriate management (antipyretics, hydration, infection workup). Continuous monitoring of blood pressure is advised\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[\"Monitor vital signs regularly,Ensure adequate hydration,Administer antipyretics.\"],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"\"},\"self_care\":\"\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-03-01 22:22:25', '2026-03-01'),
('REF-EF67F4', 'PAT-10057', '1999-02-27', 'VST-2026-0022', 'Mattu Karl Specialized Hospital', 'Test Doctor User', NULL, 'STF-082', 'To addis abeba', 'chest pain', '092434454555', 'Internal Medicine', 'Routine', 'Inpatient', 'ambulance', 'surger review', 'less body mass index', '{\"items\":[\"I have seen his symptoms he very nurvous\",\"ddddd\"],\"other\":\"\"}', '{\"initiated\":\"\",\"items\":[],\"medication_chart_attached\":true}', '{\"mobility\":{\"status\":\"\",\"precautions\":\"\"},\"self_care\":\"Carer dependent\",\"cognitive_impairment\":true,\"assistive_devices\":{\"provided\":\"\",\"required\":\"\"}}', 'Test Doctor User', 'Doctor', 'Test Doctor User', 'Pending', '2026-02-27 14:29:53', '2026-02-27');

-- --------------------------------------------------------

--
-- Table structure for table `status_logs`
--

CREATE TABLE `status_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `admin_id` varchar(20) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_logs`
--

INSERT INTO `status_logs` (`log_id`, `user_id`, `admin_id`, `old_status`, `new_status`, `reason`, `changed_at`) VALUES
(4, 'STF-002', 'STF-081', 'active', 'disabled', 'Shift_End_Day', '2026-03-01 18:12:40'),
(5, 'STF-096', 'STF-081', 'active', 'disabled', 'Shift_End_Day', '2026-03-01 20:25:08'),
(6, 'STF-096', 'STF-081', 'disabled', 'active', 'Shift_End_Night', '2026-03-01 20:25:36');

-- --------------------------------------------------------

--
-- Table structure for table `treatment_plans`
--

CREATE TABLE `treatment_plans` (
  `plan_id` varchar(20) NOT NULL,
  `diagnosis_id` varchar(20) DEFAULT NULL,
  `description` text NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment_plans`
--

INSERT INTO `treatment_plans` (`plan_id`, `diagnosis_id`, `description`, `start_date`, `end_date`, `created_at`) VALUES
('PLN-10301088', 'DX-DDE951BA', 'yes', '2026-02-25', NULL, '2026-02-25 09:39:03'),
('PLN-29D6F539', 'DX-A4554FEC', 'Monitor vital signs regularly,\r\nEnsure adequate hydration,\r\nAdminister antipyretics.', '2026-03-01', '2026-03-12', '2026-03-01 22:14:45'),
('PLN-3E6293C5', 'DX-DDE951BA', 'the plan is perfect', '2026-02-25', NULL, '2026-02-25 12:53:48'),
('PLN-507980F7', 'DX-DDE951BA', 'The best treatment plan is staying home for week is compulsory and  making fun of it.', '2026-02-25', '2026-03-07', '2026-02-25 12:48:48'),
('PLN-63EB165F', 'DX-DDE951BA', 'A medical history (or anamnesis) is a comprehensive, structured record of a patient\'s personal health information, including current symptoms, past illnesses, surgeries, allergies, medications, and family medical history. It is a critical', '2026-02-25', NULL, '2026-02-25 13:36:33'),
('PLN-781213BD', 'DX-DDE951BA', 'A medical history (or anamnesis) is a comprehensive, structured record of a patient\'s personal health information, including current symptoms, past illnesses, surgeries, allergies, medications, and family medical history. It is a critical', '2026-02-25', '2026-02-26', '2026-02-25 13:35:53'),
('PLN-7F8C5F8F', 'DX-DDE951BA', 'It is okay if you leave now', '2026-02-26', '2026-02-27', '2026-02-26 19:11:59'),
('PLN-99E811C3', 'DX-DDE951BA', 'A medical history (or anamnesis) is a comprehensive, structured record of a patient\'s personal health information, including current symptoms, past illnesses, surgeries, allergies, medications, and family medical history. It is a critical', '2026-02-25', '2026-02-28', '2026-02-25 13:36:48'),
('PLN-A8665F37', 'DX-DDE951BA', 'dddddd', '2026-02-25', NULL, '2026-02-25 09:51:57'),
('PLN-B1678D71', 'DX-DDE951BA', 'jjjj', '2026-02-25', '2026-02-26', '2026-02-25 08:58:35'),
('PLN-BDF5506C', 'DX-DDE951BA', 'yes', '2026-02-25', NULL, '2026-02-25 09:29:03'),
('PLN-DCA54E87', 'DX-DDE951BA', 'the plan is perfect', '2026-02-25', NULL, '2026-02-25 12:50:57'),
('PLN-E0442BB6', 'DX-DDE951BA', 'yes', '2026-02-25', NULL, '2026-02-25 09:51:12'),
('PLN-F0A23B25', 'DX-DDE951BA', 'A medical history (or anamnesis) is a comprehensive, structured record of a patient\'s personal health information, including current symptoms, past illnesses, surgeries, allergies, medications, and family medical history. It is a critical', '2026-02-25', '2026-02-28', '2026-02-25 13:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(30) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `status_reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `phone`, `password_hash`, `full_name`, `role`, `status`, `status_reason`, `created_at`) VALUES
('STF-001', 'admin@mattu.edu', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'System Administrator', 'Admin', 'active', NULL, '2026-02-17 12:09:32'),
('STF-002', 'samuel.bekele@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Dr. Samuel Bekele', 'Doctor', 'disabled', 'Shift_End_Day', '2026-02-17 13:50:27'),
('STF-004', 'dawit.alemu@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Mr. Dawit Alemu', 'Lab Technician', 'active', NULL, '2026-02-17 13:50:27'),
('STF-005', 'selamawit.girma@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Ms. Selamawit Girma', 'Pharmacist', 'active', NULL, '2026-02-17 13:50:27'),
('STF-006', 'yohannes.kebede@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Mr. Yohannes Kebede', 'Clerk', 'active', NULL, '2026-02-17 13:50:27'),
('STF-026', 'dr.elsabet@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Dr. Elsabet Haile', 'Doctor', 'active', NULL, '2026-02-24 06:24:12'),
('STF-049', 'nurse.leul@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Mr. Leul Gebre', 'Nurse', 'active', NULL, '2026-02-24 06:24:12'),
('STF-050', 'nurse.misrak@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Sr. Misrak Tsegaye', 'Nurse', 'active', NULL, '2026-02-24 06:24:12'),
('STF-055', 'lab.chala@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Mr. Chala Bekele', 'Lab Technician', 'active', NULL, '2026-02-24 06:24:12'),
('STF-062', 'pharm.bizuayehu@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Ms. Bizuayehu Mulu', 'Pharmacist', 'active', NULL, '2026-02-24 06:24:12'),
('STF-063', 'pharm.chaltu2@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Ms. Chaltu Abdi', 'Pharmacist', 'active', NULL, '2026-02-24 06:24:12'),
('STF-071', 'rad.abel@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Dr. Abel Wondimu', 'Radiologist', 'active', NULL, '2026-02-24 06:24:12'),
('STF-072', 'rad.betty@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Dr. Betty Demissie', 'Radiologist', 'active', NULL, '2026-02-24 06:24:12'),
('STF-082', 'doctor.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Test Doctor User', 'Doctor', 'active', NULL, '2026-02-24 08:57:43'),
('STF-084', 'lab.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Test Lab Technician', 'Lab Technician', 'active', NULL, '2026-02-24 08:57:43'),
('STF-085', 'pharmacist.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Test Pharmacist', 'Pharmacist', 'active', NULL, '2026-02-24 08:57:43'),
('STF-086', 'clerk.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Test Clerk User', 'Clerk', 'active', NULL, '2026-02-24 08:57:43'),
('STF-087', 'radiologist.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Test Radiologist', 'Radiologist', 'active', NULL, '2026-02-24 08:57:43'),
('STF-088', 'nurse.test@hospital.com', NULL, '$2y$10$vXOO7qH7ZkioQ.0fA0XO2OmnAvfjfh0AIlJQQmbzQqKEsh9bhBIJu', 'Sis.Nurse Abebech Alemu', 'Nurse', 'active', NULL, '2026-03-01 21:05:47'),
('STF-089', 'admin.test@hospital.com', NULL, '$2y$10$wdYCqKTTjKWJs6r5WLRKGud/vmOwLU4vds4UVbbD9fr5b2dWzrzpq', 'Mr.Teka Gemechu', 'Admin', 'active', NULL, '2026-03-01 21:05:47');

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `vital_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) DEFAULT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `pulse` int(11) DEFAULT NULL,
  `recorded_by` varchar(20) DEFAULT NULL,
  `recorded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `requires_clerical_action` tinyint(1) DEFAULT 0,
  `clerical_action_completed` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vital_signs`
--

INSERT INTO `vital_signs` (`vital_id`, `visit_id`, `temperature`, `blood_pressure`, `pulse`, `recorded_by`, `recorded_date`, `recorded_at`, `requires_clerical_action`, `clerical_action_completed`) VALUES
('VIT-3e30795f', 'VST-2026-0022', 70.0, '120', 60, 'STF-082', '2026-02-27 18:24:12', '2026-02-27 18:24:12', 0, 0),
('VIT-51ec0477', 'VST-2026-0002', 99.0, '120', 0, 'STF-083', '2026-02-24 18:13:04', '2026-02-25 08:40:53', 0, 0),
('VIT-54bf4b04', 'VST-2026-0002', 99.0, '120', 0, 'STF-083', '2026-02-24 18:22:53', '2026-02-25 08:40:53', 0, 0),
('VIT-99889aa1', 'VST-2026-0028', 40.0, '140', 80, 'STF-082', '2026-03-01 21:29:14', '2026-03-01 21:29:14', 0, 0),
('VIT-a441c8dd', 'VST-2026-0019', 40.0, '123', 50, 'STF-083', '2026-02-25 06:25:43', '2026-02-25 08:40:53', 0, 0),
('VIT-abd42c7a', 'VST-2026-0002', 99.4, '120', 0, 'STF-083', '2026-02-24 18:22:47', '2026-02-25 08:40:53', 0, 0),
('VIT-c0eb1ee3', 'VST-2026-0002', 99.0, '120', 0, 'STF-083', '2026-02-24 11:29:12', '2026-02-25 08:40:53', 0, 0),
('VIT-c1c349a3', 'VST-2026-0006', 45.0, '120', 0, 'STF-083', '2026-02-24 11:32:20', '2026-02-25 08:40:53', 0, 0),
('VIT-c8adbe36', 'VST-2026-0019', 37.0, '120/80', 75, 'STF-083', '2026-02-25 19:05:42', '2026-02-25 19:05:42', 0, 0),
('VIT-df62f304', 'VST-2026-0006', 45.0, '120', 0, 'STF-001', '2026-02-24 08:41:59', '2026-02-25 08:40:53', 0, 0),
('VIT-f2b15d35', 'VST-2026-0022', 35.0, '140', 79, 'STF-083', '2026-02-27 05:36:20', '2026-02-27 05:36:20', 0, 0),
('VIT-fe4a074c', 'VST-2026-0019', 10.0, '120', 40, 'STF-083', '2026-02-25 06:23:54', '2026-02-25 08:40:53', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `discharges`
--
ALTER TABLE `discharges`
  ADD PRIMARY KEY (`discharge_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `discharged_by` (`discharged_by`);

--
-- Indexes for table `dispensing_records`
--
ALTER TABLE `dispensing_records`
  ADD PRIMARY KEY (`dispense_id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `pharmacist_id` (`pharmacist_id`);

--
-- Indexes for table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `fk_lab_tech` (`assigned_tech_id`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `medical_visits`
--
ALTER TABLE `medical_visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `doctor_assigned` (`doctor_assigned`),
  ADD KEY `fk_visit_doctor` (`assigned_doctor_id`),
  ADD KEY `fk_visit_nurse` (`assigned_nurse_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `medical_record_number` (`medical_record_number`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `prescribed_by` (`prescribed_by`),
  ADD KEY `fk_phr_assign` (`assigned_phr_id`);

--
-- Indexes for table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `fk_rad` (`assigned_rad_id`);

--
-- Indexes for table `radiology_results`
--
ALTER TABLE `radiology_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `radiologist_id` (`radiologist_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `fk_referral_patient` (`patient_id`),
  ADD KEY `fk_referral_visit` (`visit_id`),
  ADD KEY `fk_referral_doctor` (`source_doctor_id`);

--
-- Indexes for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD PRIMARY KEY (`plan_id`),
  ADD KEY `diagnosis_id` (`diagnosis_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD PRIMARY KEY (`vital_id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `status_logs`
--
ALTER TABLE `status_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `diagnoses`
--
ALTER TABLE `diagnoses`
  ADD CONSTRAINT `diagnoses_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `diagnoses_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `discharges`
--
ALTER TABLE `discharges`
  ADD CONSTRAINT `discharges_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `discharges_ibfk_2` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`),
  ADD CONSTRAINT `discharges_ibfk_3` FOREIGN KEY (`discharged_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `dispensing_records`
--
ALTER TABLE `dispensing_records`
  ADD CONSTRAINT `dispensing_records_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dispensing_records_ibfk_2` FOREIGN KEY (`pharmacist_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD CONSTRAINT `fk_lab_tech` FOREIGN KEY (`assigned_tech_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `lab_requests_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_requests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `lab_results_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `lab_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lab_results_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `medical_visits`
--
ALTER TABLE `medical_visits`
  ADD CONSTRAINT `fk_visit_doctor` FOREIGN KEY (`assigned_doctor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_visit_nurse` FOREIGN KEY (`assigned_nurse_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `medical_visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_visits_ibfk_2` FOREIGN KEY (`doctor_assigned`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_phr_assign` FOREIGN KEY (`assigned_phr_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescriptions_ibfk_2` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `radiology_requests`
--
ALTER TABLE `radiology_requests`
  ADD CONSTRAINT `fk_rad` FOREIGN KEY (`assigned_rad_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `radiology_requests_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `radiology_requests_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `radiology_results`
--
ALTER TABLE `radiology_results`
  ADD CONSTRAINT `radiology_results_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `radiology_requests` (`request_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `radiology_results_ibfk_2` FOREIGN KEY (`radiologist_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_referral_doctor` FOREIGN KEY (`source_doctor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_referral_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_referral_visit` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE;

--
-- Constraints for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD CONSTRAINT `status_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `status_logs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `treatment_plans`
--
ALTER TABLE `treatment_plans`
  ADD CONSTRAINT `treatment_plans_ibfk_1` FOREIGN KEY (`diagnosis_id`) REFERENCES `diagnoses` (`diagnosis_id`) ON DELETE CASCADE;

--
-- Constraints for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD CONSTRAINT `vital_signs_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `medical_visits` (`visit_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vital_signs_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
