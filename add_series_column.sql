-- Add 'series' column to employee_documents table
-- This field represents the year (e.g., 2025) and will be auto-filled with the current year

ALTER TABLE `employee_documents` 
ADD COLUMN `series` YEAR(4) NULL AFTER `affidavit_type`;

-- Set default value for existing records to current year
UPDATE `employee_documents` 
SET `series` = YEAR(CURDATE()) 
WHERE `series` IS NULL;

