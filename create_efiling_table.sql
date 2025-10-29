-- eFiling Feature Database Table
-- Run this SQL to create the efiling_history table

CREATE TABLE IF NOT EXISTS `efiling_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `attorney_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `document_category` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_file_name` varchar(255) DEFAULT NULL,
  `stored_file_path` varchar(500) DEFAULT NULL,
  `receiver_email` varchar(255) NOT NULL,
  `message` text,
  `status` enum('Sent','Failed') NOT NULL DEFAULT 'Sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `attorney_id` (`attorney_id`),
  KEY `case_id` (`case_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraints
ALTER TABLE `efiling_history`
  ADD CONSTRAINT `efiling_history_ibfk_1` FOREIGN KEY (`attorney_id`) REFERENCES `user_form` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `efiling_history_ibfk_2` FOREIGN KEY (`case_id`) REFERENCES `attorney_cases` (`id`) ON DELETE CASCADE;

