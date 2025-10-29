-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2025 at 06:31 AM
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
-- Database: `lawfirm`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_messages`
--

CREATE TABLE `admin_messages` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attorney_cases`
--

CREATE TABLE `attorney_cases` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `client_id` int(11) DEFAULT NULL,
  `case_type` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `next_hearing` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attorney_documents`
--

CREATE TABLE `attorney_documents` (
  `id` int(11) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attorney_document_activity`
--

CREATE TABLE `attorney_document_activity` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attorney_messages`
--

CREATE TABLE `attorney_messages` (
  `id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_type` enum('admin','attorney','client','employee') NOT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('success','failed','warning','info') DEFAULT 'success',
  `priority` enum('low','medium','high','critical') DEFAULT 'low',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `available_colors`
--

CREATE TABLE `available_colors` (
  `id` int(11) NOT NULL,
  `schedule_card_color` varchar(7) NOT NULL,
  `calendar_event_color` varchar(7) NOT NULL,
  `color_name` varchar(50) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_documents`
--

CREATE TABLE `case_documents` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `category` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `case_schedules`
--

CREATE TABLE `case_schedules` (
  `id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `attorney_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `walkin_client_name` varchar(255) DEFAULT NULL,
  `walkin_client_contact` varchar(50) DEFAULT NULL,
  `type` enum('Hearing','Appointment','Free Legal Advice') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_attorney_assignments`
--

CREATE TABLE `client_attorney_assignments` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `seen_status` enum('Not Seen','Seen') DEFAULT 'Not Seen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_attorney_conversations`
--

CREATE TABLE `client_attorney_conversations` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `conversation_status` enum('Active','Completed','Closed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_attorney_messages`
--

CREATE TABLE `client_attorney_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('client','attorney') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_seen` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_document_generation`
--

CREATE TABLE `client_document_generation` (
  `id` int(11) NOT NULL,
  `request_id` varchar(100) NOT NULL,
  `client_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_data` text NOT NULL,
  `pdf_file_path` varchar(500) DEFAULT NULL,
  `pdf_filename` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_employee_conversations`
--

CREATE TABLE `client_employee_conversations` (
  `id` int(11) NOT NULL,
  `request_form_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `conversation_status` enum('Active','Completed','Closed') DEFAULT 'Active',
  `concern_identified` tinyint(1) DEFAULT 0,
  `concern_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_employee_messages`
--

CREATE TABLE `client_employee_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('client','employee') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_seen` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_messages`
--

CREATE TABLE `client_messages` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_request_form`
--

CREATE TABLE `client_request_form` (
  `id` int(11) NOT NULL,
  `request_id` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `valid_id_path` varchar(500) NOT NULL,
  `valid_id_filename` varchar(255) NOT NULL,
  `valid_id_front_path` varchar(500) NOT NULL,
  `valid_id_front_filename` varchar(255) NOT NULL,
  `valid_id_back_path` varchar(500) NOT NULL,
  `valid_id_back_filename` varchar(255) NOT NULL,
  `privacy_consent` tinyint(1) NOT NULL DEFAULT 0,
  `concern_description` text DEFAULT NULL,
  `legal_category` varchar(100) DEFAULT NULL,
  `urgency_level` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_requests`
--

CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Requested','Submitted','Reviewed','Approved','Rejected','Cancelled') DEFAULT 'Requested',
  `attorney_comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_request_comments`
--

CREATE TABLE `document_request_comments` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_request_files`
--

CREATE TABLE `document_request_files` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `efiling_history`
--

CREATE TABLE `efiling_history` (
  `id` int(11) NOT NULL,
  `attorney_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `document_category` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_file_name` varchar(255) DEFAULT NULL,
  `stored_file_path` varchar(500) DEFAULT NULL,
  `receiver_email` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Sent','Failed') NOT NULL DEFAULT 'Sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL,
  `doc_number` int(11) NOT NULL,
  `book_number` int(11) NOT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `affidavit_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_document_activity`
--

CREATE TABLE `employee_document_activity` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `form_number` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_messages`
--

CREATE TABLE `employee_messages` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_request_reviews`
--

CREATE TABLE `employee_request_reviews` (
  `id` int(11) NOT NULL,
  `request_form_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `action` enum('Approved','Rejected') NOT NULL,
  `review_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','attorney','client','employee') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_colors`
--

CREATE TABLE `user_colors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','attorney','client','employee') NOT NULL,
  `schedule_card_color` varchar(7) NOT NULL COMMENT 'Hex color for schedule cards',
  `calendar_event_color` varchar(7) NOT NULL COMMENT 'Hex color for calendar events',
  `color_name` varchar(50) DEFAULT NULL COMMENT 'Human readable color name',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1 if color is in use, 0 if freed',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `freed_at` timestamp NULL DEFAULT NULL COMMENT 'When color was freed due to user deletion'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_colors`
--

INSERT INTO `user_colors` (`id`, `user_id`, `user_type`, `schedule_card_color`, `calendar_event_color`, `color_name`, `is_active`, `assigned_at`, `freed_at`) VALUES
(0, 1, 'admin', '#E6B0AA', '#800000', 'Admin Maroon', 1, '2025-10-04 15:44:05', NULL),
(0, 2, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-04 15:46:15', NULL),
(0, 3, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-04 15:47:36', NULL),
(0, 7, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-05 06:39:26', NULL),
(0, 8, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-05 06:40:12', NULL),
(0, 12, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-06 05:54:19', NULL),
(0, 13, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-06 05:55:26', NULL),
(0, 17, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-07 09:47:14', NULL),
(0, 19, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-07 09:50:58', NULL),
(0, 22, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-08 01:10:33', NULL),
(0, 23, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-08 01:11:25', NULL),
(0, 1, 'admin', '#E6B0AA', '#800000', 'Admin Maroon', 1, '2025-10-04 15:44:05', NULL),
(0, 2, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-04 15:46:15', NULL),
(0, 3, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-04 15:47:36', NULL),
(0, 7, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-05 06:39:26', NULL),
(0, 8, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-05 06:40:12', NULL),
(0, 12, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-06 05:54:19', NULL),
(0, 13, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-06 05:55:26', NULL),
(0, 17, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-07 09:47:14', NULL),
(0, 19, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-07 09:50:58', NULL),
(0, 22, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-08 01:10:33', NULL),
(0, 23, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-08 01:11:25', NULL),
(0, 27, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-10 16:21:18', NULL),
(0, 28, 'attorney', '#90EE90', '#008000', 'Attorney Light Green', 1, '2025-10-10 18:45:25', NULL),
(0, 35, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-12 08:40:06', NULL),
(0, 36, 'attorney', '#ADD8E6', '#87CEEB', 'Attorney Sky Blue', 1, '2025-10-14 11:51:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_form`
--

CREATE TABLE `user_form` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','attorney','client','employee') DEFAULT 'client',
  `login_attempts` int(11) DEFAULT 0,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  `account_locked` tinyint(1) DEFAULT 0,
  `lockout_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_form`
--

INSERT INTO `user_form` (`id`, `name`, `profile_image`, `last_login`, `email`, `phone_number`, `password`, `user_type`, `login_attempts`, `last_failed_login`, `account_locked`, `lockout_until`, `created_at`, `created_by`) VALUES
(1, 'Opiña, Leif Laiglon Abriz', 'uploads/admin/1_1759828076_093758914f59d137.jpg', '2025-10-21 11:32:20', 'leifopina25@gmail.com', '09283262333', '$2y$10$VFyQmcbe/.cdjVY7DWDxS.40nxC8.wRe7pBFX5zVoYxPHAM2DzrA2', 'admin', 0, NULL, 0, NULL, '2025-10-04 18:16:17', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_messages`
--
ALTER TABLE `admin_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attorney_cases`
--
ALTER TABLE `attorney_cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attorney_id` (`attorney_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `attorney_documents`
--
ALTER TABLE `attorney_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attorney_document_activity`
--
ALTER TABLE `attorney_document_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attorney_messages`
--
ALTER TABLE `attorney_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `case_documents`
--
ALTER TABLE `case_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `case_id` (`case_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `case_schedules`
--
ALTER TABLE `case_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_attorney_assignments`
--
ALTER TABLE `client_attorney_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `attorney_id` (`attorney_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `client_attorney_conversations`
--
ALTER TABLE `client_attorney_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `attorney_id` (`attorney_id`);

--
-- Indexes for table `client_attorney_messages`
--
ALTER TABLE `client_attorney_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `client_document_generation`
--
ALTER TABLE `client_document_generation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_document_type` (`document_type`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `client_employee_conversations`
--
ALTER TABLE `client_employee_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_form_id` (`request_form_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `client_employee_messages`
--
ALTER TABLE `client_employee_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `client_messages`
--
ALTER TABLE `client_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_request_form`
--
ALTER TABLE `client_request_form`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `efiling_history`
--
ALTER TABLE `efiling_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_document_activity`
--
ALTER TABLE `employee_document_activity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_messages`
--
ALTER TABLE `employee_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_request_reviews`
--
ALTER TABLE `employee_request_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_form_id` (`request_form_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `user_form`
--
ALTER TABLE `user_form`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_messages`
--
ALTER TABLE `admin_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `attorney_cases`
--
ALTER TABLE `attorney_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `attorney_documents`
--
ALTER TABLE `attorney_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `attorney_document_activity`
--
ALTER TABLE `attorney_document_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `attorney_messages`
--
ALTER TABLE `attorney_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6486;

--
-- AUTO_INCREMENT for table `case_documents`
--
ALTER TABLE `case_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `case_schedules`
--
ALTER TABLE `case_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `client_attorney_assignments`
--
ALTER TABLE `client_attorney_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `client_attorney_conversations`
--
ALTER TABLE `client_attorney_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `client_attorney_messages`
--
ALTER TABLE `client_attorney_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `client_document_generation`
--
ALTER TABLE `client_document_generation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `client_employee_conversations`
--
ALTER TABLE `client_employee_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `client_employee_messages`
--
ALTER TABLE `client_employee_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `client_messages`
--
ALTER TABLE `client_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_request_form`
--
ALTER TABLE `client_request_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `efiling_history`
--
ALTER TABLE `efiling_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `employee_document_activity`
--
ALTER TABLE `employee_document_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employee_messages`
--
ALTER TABLE `employee_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_request_reviews`
--
ALTER TABLE `employee_request_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=335;

--
-- AUTO_INCREMENT for table `user_form`
--
ALTER TABLE `user_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `case_documents`
--
ALTER TABLE `case_documents`
  ADD CONSTRAINT `case_documents_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `attorney_cases` (`id`),
  ADD CONSTRAINT `case_documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `user_form` (`id`);

--
-- Constraints for table `client_attorney_assignments`
--
ALTER TABLE `client_attorney_assignments`
  ADD CONSTRAINT `client_attorney_assignments_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `client_employee_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_assignments_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_assignments_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_assignments_ibfk_4` FOREIGN KEY (`attorney_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_attorney_conversations`
--
ALTER TABLE `client_attorney_conversations`
  ADD CONSTRAINT `client_attorney_conversations_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `client_attorney_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_conversations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_conversations_ibfk_3` FOREIGN KEY (`attorney_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_attorney_messages`
--
ALTER TABLE `client_attorney_messages`
  ADD CONSTRAINT `client_attorney_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `client_attorney_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_attorney_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_document_generation`
--
ALTER TABLE `client_document_generation`
  ADD CONSTRAINT `client_document_generation_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_document_generation_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `user_form` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `client_employee_conversations`
--
ALTER TABLE `client_employee_conversations`
  ADD CONSTRAINT `client_employee_conversations_ibfk_1` FOREIGN KEY (`request_form_id`) REFERENCES `client_request_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_employee_conversations_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_employee_conversations_ibfk_3` FOREIGN KEY (`employee_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_employee_messages`
--
ALTER TABLE `client_employee_messages`
  ADD CONSTRAINT `client_employee_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `client_employee_conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_employee_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_request_form`
--
ALTER TABLE `client_request_form`
  ADD CONSTRAINT `client_request_form_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_request_reviews`
--
ALTER TABLE `employee_request_reviews`
  ADD CONSTRAINT `employee_request_reviews_ibfk_1` FOREIGN KEY (`request_form_id`) REFERENCES `client_request_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_request_reviews_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
