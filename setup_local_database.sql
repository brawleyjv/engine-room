-- Setup local XAMPP database to match production
-- Run this script in phpMyAdmin or MySQL command line

-- Create the database
CREATE DATABASE IF NOT EXISTS vessel_license_master;

-- Use the database
USE vessel_license_master;

-- Create the license_admin user (for local development to match production)
-- Drop user if exists (MySQL 5.7+ syntax)
DROP USER IF EXISTS 'license_admin'@'localhost';

-- Create the user with the development password
CREATE USER 'license_admin'@'localhost' IDENTIFIED BY 'master_license_key_2024';

-- Grant all privileges on the vessel_license_master database
GRANT ALL PRIVILEGES ON vessel_license_master.* TO 'license_admin'@'localhost';

-- Flush privileges to ensure changes take effect
FLUSH PRIVILEGES;

-- Create the companies table if it doesn't exist
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_domain VARCHAR(100) NOT NULL UNIQUE,
    subscription_status ENUM('trial', 'active', 'suspended', 'cancelled') DEFAULT 'trial',
    trial_start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    trial_end_date DATETIME,
    database_host VARCHAR(255) DEFAULT 'localhost',
    database_name VARCHAR(100),
    database_user VARCHAR(100),
    database_password VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    admin_email VARCHAR(255),
    admin_password VARCHAR(255),
    INDEX idx_company_domain (company_domain),
    INDEX idx_subscription_status (subscription_status)
);

-- Show what we created
SELECT 'Database and user setup complete!' as Status;
SHOW TABLES;
DESCRIBE companies;
