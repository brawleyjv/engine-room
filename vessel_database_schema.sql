-- Vessel Database Schema for Main Server
-- Creates tables to store vessel data synced from vessel logger devices

-- Vessels table - master vessel registry
CREATE TABLE IF NOT EXISTS vessels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_name VARCHAR(255) NOT NULL UNIQUE,
    hin VARCHAR(100),
    external_vessel_id VARCHAR(100),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_hin (hin)
);

-- Vessel logs table - stores log entries from vessels
CREATE TABLE IF NOT EXISTS vessel_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(255) NOT NULL,
    log_entry TEXT NOT NULL,
    logged_by VARCHAR(255) NOT NULL,
    vessel_timestamp TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_vessel_timestamp (vessel_timestamp),
    INDEX idx_created_at (created_at)
);

-- Navigation data table - stores destination/ETA from vessels
CREATE TABLE IF NOT EXISTS navigation_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vessel_id INT NOT NULL,
    vessel_name VARCHAR(255) NOT NULL,
    destination VARCHAR(255),
    eta DATETIME,
    vessel_timestamp TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
    INDEX idx_vessel_id (vessel_id),
    INDEX idx_vessel_name (vessel_name),
    INDEX idx_updated_at (updated_at)
);

-- Insert a sample vessel for testing (if not exists)
INSERT IGNORE INTO vessels (vessel_name, hin, status) 
VALUES ('Test Vessel', 'TEST123', 'active');
