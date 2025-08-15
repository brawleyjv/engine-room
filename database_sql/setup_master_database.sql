# Database Management SQL Script
# Run this to set up the master database and trial management tables

USE vessellogger_master;

-- Add additional fields to customer_licenses for trial management
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS suspended_at TIMESTAMP NULL;
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS database_name VARCHAR(100) NULL;
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS archived_database_name VARCHAR(100) NULL;
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS database_archived_at TIMESTAMP NULL;
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS database_deleted_at TIMESTAMP NULL;
ALTER TABLE customer_licenses ADD COLUMN IF NOT EXISTS notes TEXT NULL;

-- Create trial management logs table
CREATE TABLE IF NOT EXISTS trial_management_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    action ENUM('reminder_sent', 'final_notice_sent', 'suspended_expired', 'database_backed_up', 'database_archived', 'reactivated') NOT NULL,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id)
);

-- Create database prefix tracking table
CREATE TABLE IF NOT EXISTS database_prefixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prefix VARCHAR(4) UNIQUE NOT NULL,
    customer_id VARCHAR(50) NULL,
    status ENUM('active', 'reserved', 'suspended', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id)
);

-- Insert reserved prefixes
INSERT IGNORE INTO database_prefixes (prefix, status) VALUES 
('SYS', 'reserved'),
('ADM', 'reserved'), 
('LOG', 'reserved'),
('TMP', 'reserved'),
('TST', 'reserved'),
('DEV', 'reserved'),
('API', 'reserved'),
('WEB', 'reserved');

-- Create company types table for better organization
CREATE TABLE IF NOT EXISTS company_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(20) UNIQUE NOT NULL,
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    default_modules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO company_types (type_code, type_name, description, default_modules) VALUES
('towboat', 'Towboat/Barge', 'Inland waterway towing and barge operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('fishing', 'Commercial Fishing', 'Commercial fishing vessel operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('workboat', 'Workboat Services', 'Offshore and harbor workboat services', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('tug', 'Tugboat Services', 'Harbor and ocean tugboat operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('supply', 'Supply Vessel', 'Offshore supply vessel operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('passenger', 'Passenger Vessel', 'Passenger ferry and cruise operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]'),
('other', 'Other', 'Other vessel operations', '["engine_basic", "wheelhouse_basic", "crew_basic"]');

-- Create module definitions table
CREATE TABLE IF NOT EXISTS module_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(50) UNIQUE NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('basic', 'premium') DEFAULT 'premium',
    monthly_price DECIMAL(10,2) DEFAULT 0.00,
    features JSON,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO module_definitions (module_code, module_name, description, category, monthly_price, features) VALUES
('engine_basic', 'Engine Room Basic', 'Basic engine logging, RPM, temperatures, pressures', 'basic', 0.00, '["Basic engine logs", "RPM tracking", "Temperature monitoring", "Pressure readings"]'),
('wheelhouse_basic', 'Wheelhouse Basic', 'Navigation logs, position tracking, basic watch logs', 'basic', 0.00, '["Navigation logs", "GPS position", "Watch logs", "Weather entry"]'),
('crew_basic', 'Crew Management Basic', 'Crew roster, basic scheduling, contact info', 'basic', 0.00, '["Crew roster", "Basic scheduling", "Contact information", "Certification tracking"]'),
('engine_advanced', 'Engine Room Advanced', 'Advanced diagnostics, fuel optimization, predictive maintenance', 'premium', 39.00, '["Fuel efficiency tracking", "Maintenance predictions", "Performance analytics", "Custom alerts"]'),
('wheelhouse_advanced', 'Wheelhouse Advanced', 'Weather routing, advanced navigation, AIS integration', 'premium', 49.00, '["Weather integration", "Route optimization", "AIS data", "Electronic charts"]'),
('crew_advanced', 'Crew Management Advanced', 'Advanced scheduling, certifications, payroll integration', 'premium', 29.00, '["Advanced scheduling", "Certification tracking", "Payroll export", "Performance reviews"]'),
('maintenance', 'Maintenance Management', 'Comprehensive maintenance tracking and scheduling', 'premium', 59.00, '["Preventive maintenance", "Work order management", "Parts inventory", "Vendor management"]'),
('compliance', 'Regulatory Compliance', 'Coast Guard reporting, inspection tracking, documentation', 'premium', 79.00, '["USCG reporting", "Inspection schedules", "Document management", "Audit trails"]'),
('analytics', 'Business Analytics', 'Fleet performance analytics and business intelligence', 'premium', 69.00, '["Performance dashboards", "Cost analysis", "Efficiency reports", "Custom KPIs"]');

-- Create customer modules junction table
CREATE TABLE IF NOT EXISTS customer_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    module_code VARCHAR(50) NOT NULL,
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deactivated_at TIMESTAMP NULL,
    status ENUM('active', 'suspended', 'canceled') DEFAULT 'active',
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id),
    FOREIGN KEY (module_code) REFERENCES module_definitions(module_code),
    UNIQUE KEY unique_customer_module (customer_id, module_code)
);

-- Create installation tracking table
CREATE TABLE IF NOT EXISTS installation_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    installation_step VARCHAR(20) NOT NULL,
    step_data JSON,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id)
);

-- Views for easier reporting
CREATE OR REPLACE VIEW active_customers AS
SELECT 
    cl.*,
    COUNT(cm.module_code) as active_modules,
    SUM(md.monthly_price) as monthly_module_cost
FROM customer_licenses cl
LEFT JOIN customer_modules cm ON cl.customer_id = cm.customer_id AND cm.status = 'active'
LEFT JOIN module_definitions md ON cm.module_code = md.module_code
WHERE cl.status = 'active'
GROUP BY cl.customer_id;

CREATE OR REPLACE VIEW trial_status AS
SELECT 
    customer_id,
    company_name,
    admin_email,
    expires_at,
    DATEDIFF(expires_at, NOW()) as days_remaining,
    CASE 
        WHEN expires_at < NOW() THEN 'EXPIRED'
        WHEN DATEDIFF(expires_at, NOW()) <= 3 THEN 'EXPIRING_SOON'
        ELSE 'ACTIVE'
    END as trial_status
FROM customer_licenses 
WHERE plan = 'trial' AND status = 'active';

-- Indexes for performance
CREATE INDEX idx_customer_expires ON customer_licenses(expires_at, status, plan);
CREATE INDEX idx_customer_modules_status ON customer_modules(customer_id, status);
CREATE INDEX idx_trial_logs_customer ON trial_management_logs(customer_id, created_at);
