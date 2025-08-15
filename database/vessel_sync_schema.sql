-- Vessel Data Sync Database Schema
-- Creates tables for storing vessel data synced from vessel logger devices

-- Main vessels table - stores vessel information
CREATE TABLE IF NOT EXISTS vessels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_name VARCHAR(255) NOT NULL UNIQUE,
    hin VARCHAR(50),
    external_vessel_id VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_hin (hin)
);

-- Vessel logs table - stores log entries from vessels
CREATE TABLE IF NOT EXISTS vessel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(255) NOT NULL,
    log_entry TEXT NOT NULL,
    logged_by VARCHAR(100) NOT NULL,
    vessel_timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_vessel_timestamp (vessel_timestamp),
    INDEX idx_created_at (created_at)
);

-- Navigation data table - stores destination/ETA information
CREATE TABLE IF NOT EXISTS navigation_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(255) NOT NULL,
    destination VARCHAR(255),
    eta DATETIME,
    vessel_timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_vessel_timestamp (vessel_timestamp)
);
