-- LogicDock Vessel Management Database Schema
-- Auto-generated for company installations

-- Create users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','user','readonly') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `password_must_change` tinyint(1) DEFAULT 1,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user with standard default password
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `password_must_change`) 
VALUES ('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1);
-- Default password is 'ChangePwd101' - user must change on first login

-- Create vessels table
CREATE TABLE `vessels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `imo_number` varchar(20) DEFAULT NULL,
  `flag` varchar(50) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `imo_number` (`imo_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create engines table
CREATE TABLE `engines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vessel_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `position` enum('port','starboard','center','auxiliary') DEFAULT 'port',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vessel_id` (`vessel_id`),
  CONSTRAINT `engines_vessel_fk` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create equipment table
CREATE TABLE `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vessel_id` int(11) NOT NULL,
  `engine_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vessel_id` (`vessel_id`),
  KEY `engine_id` (`engine_id`),
  CONSTRAINT `equipment_vessel_fk` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `equipment_engine_fk` FOREIGN KEY (`engine_id`) REFERENCES `engines` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create logs table
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vessel_id` int(11) NOT NULL,
  `engine_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `log_time` time DEFAULT NULL,
  `hours` decimal(8,2) DEFAULT NULL,
  `temperature` decimal(5,2) DEFAULT NULL,
  `pressure` decimal(8,2) DEFAULT NULL,
  `oil_level` decimal(5,2) DEFAULT NULL,
  `fuel_consumption` decimal(8,2) DEFAULT NULL,
  `notes` text,
  `status` enum('normal','warning','critical') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vessel_id` (`vessel_id`),
  KEY `engine_id` (`engine_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `user_id` (`user_id`),
  KEY `log_date` (`log_date`),
  CONSTRAINT `logs_vessel_fk` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `logs_engine_fk` FOREIGN KEY (`engine_id`) REFERENCES `engines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logs_equipment_fk` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL,
  CONSTRAINT `logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create maintenance_schedules table
CREATE TABLE `maintenance_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vessel_id` int(11) NOT NULL,
  `engine_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `task_name` varchar(200) NOT NULL,
  `description` text,
  `frequency_type` enum('hours','days','months') DEFAULT 'hours',
  `frequency_value` int(11) NOT NULL,
  `last_completed` date DEFAULT NULL,
  `next_due` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `vessel_id` (`vessel_id`),
  KEY `engine_id` (`engine_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `maintenance_vessel_fk` FOREIGN KEY (`vessel_id`) REFERENCES `vessels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maintenance_engine_fk` FOREIGN KEY (`engine_id`) REFERENCES `engines` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_equipment_fk` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sessions table for session management
CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `data` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create company_settings table
CREATE TABLE `company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default company settings
INSERT INTO `company_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('company_name', 'Marine Solutions Inc.', 'string', 'Company display name'),
('timezone', 'UTC', 'string', 'Company timezone'),
('date_format', 'Y-m-d', 'string', 'Date display format'),
('time_format', 'H:i:s', 'string', 'Time display format'),
('currency', 'USD', 'string', 'Default currency'),
('max_vessels', '10', 'number', 'Maximum number of vessels allowed'),
('trial_mode', '1', 'boolean', 'Whether company is in trial mode'),
('backup_enabled', '1', 'boolean', 'Whether automatic backups are enabled');