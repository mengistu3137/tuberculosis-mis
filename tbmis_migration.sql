-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 11:25 PM
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
-- Database: `mattu_tbmis`
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tb_classification` varchar(50) DEFAULT NULL,
  `tb_treatment_status` varchar(50) DEFAULT NULL,
  `mdr_tb_status` enum('Yes','No') DEFAULT 'No',
  `diagnosis_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diagnoses`
--

INSERT INTO `diagnoses` (`diagnosis_id`, `visit_id`, `doctor_id`, `diagnosis_details`, `diagnosis_date`, `created_at`, `requires_follow_up`, `follow_up_scheduled`, `needs_vitals_review`, `vitals_reviewed`, `updated_at`, `tb_classification`, `tb_treatment_status`, `mdr_tb_status`, `diagnosis_method`) VALUES
('DX-FCDD4327', 'VST-2026-0002', 'STF-101', 'thank youu', '2026-03-10', '2026-03-10 21:43:38', 0, 0, 0, 0, '2026-03-10 21:43:38', 'Pulmonary TB', 'On Treatment', 'No', 'GeneXpert');

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
  `assigned_tech_id` varchar(20) DEFAULT NULL,
  `tb_test_category` varchar(50) DEFAULT NULL,
  `specimen_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_requests`
--

INSERT INTO `lab_requests` (`request_id`, `visit_id`, `doctor_id`, `test_type`, `request_date`, `status`, `created_at`, `priority`, `patient_notified`, `results_viewed`, `updated_at`, `assigned_tech_id`, `tb_test_category`, `specimen_type`) VALUES
('REQ-690899', 'VST-2026-0002', 'STF-101', 'Generate', '2026-03-10', 'pending', '2026-03-10 21:44:47', 'normal', 0, 0, '2026-03-10 21:44:47', 'STF-103', 'Bacteriological', 'Gastric Aspirate');

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
('VST-2026-0001', 'PAT-51151', '2026-03-10', 'Outpatient', 'come for tuber closis', '2026-03-10 21:32:34', NULL, 'active', 0, NULL, 0, 'STF-101', NULL),
('VST-2026-0002', 'PAT-51151', '2026-03-10', 'Outpatient', 'checkedn in', '2026-03-10 21:34:28', NULL, 'active', 0, NULL, 0, 'STF-101', 'STF-102');

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
('PAT-51151', 'MRN-2026-0001', 'Keebekii Guddeta', 30, 'Male', 'mattu', '0923359251', '2026-03-10', '2026-03-10 21:10:40');

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
  `assigned_phr_id` varchar(20) DEFAULT NULL,
  `tb_phase` varchar(50) DEFAULT NULL,
  `next_followup_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('RAD-FCF69D34', 'VST-2026-0002', 'STF-101', 'yes dear ', 'have you seen that', 'we got it', 'normal', 'pending', '2026-03-10', '2026-03-10 21:45:57', '2026-03-10 21:45:57', 'STF-106');

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

-- --------------------------------------------------------

--
-- Table structure for table `tb_followups`
--

CREATE TABLE `tb_followups` (
  `followup_id` varchar(20) NOT NULL,
  `visit_id` varchar(20) NOT NULL,
  `follow_up_date` date NOT NULL,
  `adherence_note` text DEFAULT NULL,
  `outcome_status` varchar(40) DEFAULT 'Scheduled',
  `created_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tb_phase` varchar(50) DEFAULT NULL,
  `drug_regimen` varchar(100) DEFAULT NULL,
  `adherence_status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('STF-100', 'demo.admin@mattu.edu', NULL, '$2y$10$.tHoZQwoU/SfVG7eIlxwQuSuy5zmAPsgkhhGK0fk9rUHlv0MF4n6i', 'Admin Admaasu', 'Admin', 'active', NULL, '2026-03-10 14:06:23'),
('STF-101', 'demo.doctor@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Dr. John Doe', 'Doctor', 'active', NULL, '2026-03-10 14:06:23'),
('STF-102', 'demo.nurse@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Nurse Jane Smith', 'Nurse', 'active', NULL, '2026-03-10 14:06:23'),
('STF-103', 'demo.lab@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Lab Tech Mike Johnson', 'Lab Technician', 'active', NULL, '2026-03-10 14:06:23'),
('STF-104', 'demo.pharmacist@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Pharmacist Lisa Williams', 'Pharmacist', 'active', NULL, '2026-03-10 14:06:23'),
('STF-105', 'demo.clerk@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Clerk Tom Brown', 'Clerk', 'active', NULL, '2026-03-10 14:06:23'),
('STF-106', 'demo.radiologist@mattu.edu', NULL, '$2y$10$dev4OsycQFWS3bDVFq5OzuhM2dd2b9SgolKMik7U1bTBCj1ZxH6qi', 'Radiologist Sarah Davis', 'Radiologist', 'active', NULL, '2026-03-10 14:06:23');

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
('VIT-2f6d9007', 'VST-2026-0002', 40.0, '140', 60, 'STF-101', '2026-03-10 21:41:44', '2026-03-10 21:41:44', 0, 0);

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
-- Indexes for table `tb_followups`
--
ALTER TABLE `tb_followups`
  ADD PRIMARY KEY (`followup_id`),
  ADD KEY `idx_tb_followups_visit` (`visit_id`),
  ADD KEY `idx_tb_followups_date` (`follow_up_date`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
