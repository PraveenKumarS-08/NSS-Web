-- ============================================================================
-- NSS Portal - Tamil Nadu Government Polytechnic College, Madurai-11
-- Database Schema & Initial Seed Data
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `nss_tngptc` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nss_tngptc`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Drop Existing Tables for Clean Rebuild
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_activity_logs`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `hero_slides`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `event_registrations`;
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `alumni`;
DROP TABLE IF EXISTS `volunteers`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------------------
-- Table: users (admin, volunteer, alumni)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','volunteer','alumni') NOT NULL DEFAULT 'volunteer',
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: volunteers (profile details)
-- ----------------------------------------------------------------------------
CREATE TABLE `volunteers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `department` VARCHAR(100) NOT NULL,
    `year` ENUM('I','II','III') NOT NULL,
    `blood_group` ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `register_number` VARCHAR(50) NOT NULL UNIQUE,
    CONSTRAINT `fk_volunteers_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: alumni (alumni profile details)
-- ----------------------------------------------------------------------------
CREATE TABLE `alumni` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `batch_year` VARCHAR(10) NOT NULL,
    `current_position` VARCHAR(100) DEFAULT NULL,
    `company` VARCHAR(100) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `mobile` VARCHAR(15) DEFAULT NULL,
    CONSTRAINT `fk_alumni_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: events (camps and activities)
-- ----------------------------------------------------------------------------
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `event_date` DATETIME NOT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `location` VARCHAR(200) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `max_participants` INT DEFAULT 0,
    `status` ENUM('upcoming','ongoing','completed','postponed','cancelled') DEFAULT 'upcoming',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: gallery (photo records)
-- ----------------------------------------------------------------------------
CREATE TABLE `gallery` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT 'General',
    `year` INT DEFAULT 2026,
    `uploaded_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_gallery_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: event_registrations (volunteer enrollment in events)
-- ----------------------------------------------------------------------------
CREATE TABLE `event_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `student_mobile` VARCHAR(20) DEFAULT NULL,
    `parent_mobile` VARCHAR(20) DEFAULT NULL,
    `age` INT DEFAULT NULL,
    `year` VARCHAR(10) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `remarks` TEXT DEFAULT NULL,
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_reg` (`event_id`, `user_id`),
    CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: attendance (service hours log)
-- ----------------------------------------------------------------------------
CREATE TABLE `attendance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `event_id` INT DEFAULT NULL,
    `type` VARCHAR(50) DEFAULT 'event',
    `attendance_date` DATE DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT 'Regular',
    `description` VARCHAR(255) DEFAULT NULL,
    `hours` DECIMAL(4,1) DEFAULT 0,
    `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_att_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: announcements (broadcast notices)
-- ----------------------------------------------------------------------------
CREATE TABLE `announcements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `content` TEXT NOT NULL,
    `target_role` ENUM('all','volunteer','alumni') DEFAULT 'all',
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: contact_messages (public inquiry inbox)
-- ----------------------------------------------------------------------------
CREATE TABLE `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `subject` VARCHAR(200) DEFAULT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: hero_slides (homepage carousel slides)
-- ----------------------------------------------------------------------------
CREATE TABLE `hero_slides` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_path` VARCHAR(255) NOT NULL,
    `title` VARCHAR(200) DEFAULT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `order_num` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: site_settings (dynamic counters & labels)
-- ----------------------------------------------------------------------------
CREATE TABLE `site_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: user_activity_logs (security audit log)
-- ----------------------------------------------------------------------------
CREATE TABLE `user_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `user_role` VARCHAR(50) DEFAULT 'volunteer',
    `action_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Initial Admin Accounts (Zero placeholder students, alumni, events, or photos)
-- ============================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'NSS System Admin', 'admin@nss.com', 'admin123', 'admin', 'approved'),
(5, 'NSS Web Admin (Santosh N)', 'admin@tngptcmadurai.com', 'NSS@Admin2024', 'admin', 'approved')
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`), `status`='approved';

-- Dynamic Homepage Counters Default Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('stat_1_label', 'ACTIVE VOLUNTEERS'),
('stat_1_val', ''),
('stat_2_label', 'CAMPS & DRIVES'),
('stat_2_val', ''),
('stat_3_label', 'YEARS OF SERVICE'),
('stat_3_val', '75+'),
('stat_4_label', 'ALUMNI NETWORK'),
('stat_4_val', '')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

SET FOREIGN_KEY_CHECKS = 1;



