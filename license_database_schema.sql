-- Comprehensive SaaS License Database Schema
-- This replaces the old customer_licenses table with a full multi-tenant system

-- Create the master license database
CREATE DATABASE IF NOT EXISTS vessel_license_master;
USE vessel_license_master;

-- Drop old tables if they exist (upgrade path)
DROP TABLE IF EXISTS customer_modules;
DROP TABLE IF EXISTS trial_management_logs;
DROP TABLE IF EXISTS installation_tracking;
DROP TABLE IF EXISTS customer_licenses;

-- Companies/Tenants - the core of the multi-tenant system
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    company_domain VARCHAR(100) UNIQUE NOT NULL, -- URL-safe identifier: 'acme-marine'
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    
    -- Company address/billing info
    billing_address TEXT,
    billing_city VARCHAR(100),
    billing_state VARCHAR(50),
    billing_country VARCHAR(50) DEFAULT 'USA',
    billing_postal VARCHAR(20),
    
    -- Database connection info for this company
    database_host VARCHAR(255) DEFAULT 'localhost',
    database_name VARCHAR(100) NOT NULL,
    database_username VARCHAR(100) NOT NULL, 
    database_password TEXT NOT NULL, -- Base64 encoded for security
    
    -- Subscription details
    subscription_plan ENUM('trial', 'basic', 'professional', 'enterprise') DEFAULT 'trial',
    subscription_status ENUM('active', 'suspended', 'expired', 'cancelled') DEFAULT 'active',
    trial_start_date DATE NOT NULL,
    trial_end_date DATE NULL,
    subscription_start_date DATE NULL,
    subscription_end_date DATE NULL,
    
    -- Limits based on plan
    max_vessels INT DEFAULT 1, -- 0 = unlimited
    max_users INT DEFAULT 5,   -- 0 = unlimited
    max_storage_mb INT DEFAULT 1000, -- MB, 0 = unlimited
    
    -- Features (JSON array of enabled features)
    enabled_features JSON,
    
    -- Billing
    monthly_price DECIMAL(10,2) DEFAULT 0.00,
    annual_price DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50),
    stripe_customer_id VARCHAR(100), -- For Stripe integration
    
    -- System tracking
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_access_at DATETIME,
    
    -- Company type/industry
    company_type ENUM('towboat', 'fishing', 'workboat', 'tug', 'supply', 'passenger', 'other') DEFAULT 'other',
    fleet_size_estimate INT DEFAULT 1,
    
    -- Status tracking
    setup_completed BOOLEAN DEFAULT FALSE,
    onboarding_step VARCHAR(50) DEFAULT 'welcome',
    
    INDEX idx_domain (company_domain),
    INDEX idx_status (subscription_status),
    INDEX idx_trial_end (trial_end_date),
    INDEX idx_subscription_end (subscription_end_date)
);

-- Subscription plans - predefined templates
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_code VARCHAR(50) UNIQUE NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    description TEXT,
    monthly_price DECIMAL(10,2) NOT NULL,
    annual_price DECIMAL(10,2),
    annual_discount_percent DECIMAL(5,2) DEFAULT 0.00,
    
    -- Limits
    max_vessels INT DEFAULT 0, -- 0 = unlimited
    max_users INT DEFAULT 0,
    max_storage_mb INT DEFAULT 0, -- MB
    
    -- Features included
    included_features JSON,
    
    -- Display settings
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Default subscription plans
INSERT INTO subscription_plans (plan_code, plan_name, description, monthly_price, annual_price, annual_discount_percent, max_vessels, max_users, max_storage_mb, included_features, is_featured) VALUES
(
    'trial', 
    'Free Trial', 
    '30-day free trial with basic features - perfect for testing', 
    0.00, 
    0.00, 
    0.00,
    1, 
    3, 
    100, 
    '["basic_logging", "offline_sync", "basic_reports"]',
    FALSE
),
(
    'basic', 
    'Basic Plan', 
    'Perfect for single vessel operations', 
    49.99, 
    499.99, 
    16.67,
    2, 
    10, 
    1000, 
    '["basic_logging", "offline_sync", "basic_reports", "crew_management"]',
    FALSE
),
(
    'professional', 
    'Professional Plan', 
    'Advanced features for growing fleets', 
    149.99, 
    1499.99, 
    16.67,
    10, 
    50, 
    5000, 
    '["basic_logging", "offline_sync", "basic_reports", "crew_management", "advanced_reports", "api_access", "maintenance_tracking"]',
    TRUE
),
(
    'enterprise', 
    'Enterprise Plan', 
    'Unlimited access for large fleet operations', 
    499.99, 
    4999.99, 
    16.67,
    0, 
    0, 
    0, 
    '["basic_logging", "offline_sync", "basic_reports", "crew_management", "advanced_reports", "api_access", "maintenance_tracking", "priority_support", "custom_integrations", "white_label"]',
    FALSE
);

-- Company contacts - people associated with each company
CREATE TABLE company_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_role ENUM('owner', 'admin', 'billing', 'technical', 'user') DEFAULT 'user',
    phone VARCHAR(50),
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_email (company_id, contact_email),
    INDEX idx_company_contacts (company_id, is_active)
);

-- Usage tracking for billing and limits
CREATE TABLE usage_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    tracking_date DATE NOT NULL,
    
    -- Usage counts
    active_vessels INT DEFAULT 0,
    active_users INT DEFAULT 0,
    storage_used_mb INT DEFAULT 0,
    api_calls INT DEFAULT 0,
    
    -- Activity metrics
    daily_logins INT DEFAULT 0,
    logs_created INT DEFAULT 0,
    reports_generated INT DEFAULT 0,
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_date (company_id, tracking_date),
    INDEX idx_tracking_date (tracking_date)
);

-- Payment history
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    
    -- Payment details
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    
    -- External payment processor info
    stripe_payment_intent_id VARCHAR(100),
    stripe_charge_id VARCHAR(100),
    
    -- Status
    status ENUM('pending', 'completed', 'failed', 'refunded', 'disputed') DEFAULT 'pending',
    
    -- Billing period this payment covers
    billing_period_start DATE,
    billing_period_end DATE,
    
    -- Invoice details
    invoice_number VARCHAR(50),
    invoice_url VARCHAR(500),
    
    notes TEXT,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_payments (company_id, payment_date),
    INDEX idx_payment_status (status)
);

-- License events log (for audit trail)
CREATE TABLE license_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    event_type ENUM('trial_started', 'trial_extended', 'trial_expired', 'subscription_started', 'subscription_renewed', 'subscription_cancelled', 'payment_received', 'account_suspended', 'account_reactivated', 'plan_upgraded', 'plan_downgraded') NOT NULL,
    event_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Event details
    old_value VARCHAR(255),
    new_value VARCHAR(255),
    details JSON,
    
    -- Who triggered this event
    triggered_by VARCHAR(100), -- 'system', 'admin', or user email
    ip_address VARCHAR(45),
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_company_events (company_id, event_date),
    INDEX idx_event_type (event_type)
);

-- Database prefixes (for tracking unique database names)
CREATE TABLE database_prefixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prefix VARCHAR(10) UNIQUE NOT NULL,
    company_id INT NULL,
    status ENUM('available', 'reserved', 'in_use', 'archived') DEFAULT 'available',
    assigned_at DATETIME NULL,
    
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    INDEX idx_prefix_status (status)
);

-- Pre-populate some reserved prefixes
INSERT INTO database_prefixes (prefix, status) VALUES 
('sys', 'reserved'),
('admin', 'reserved'), 
('log', 'reserved'),
('temp', 'reserved'),
('test', 'reserved'),
('dev', 'reserved'),
('api', 'reserved'),
('web', 'reserved'),
('backup', 'reserved'),
('archive', 'reserved');

-- Sample companies for development/testing
INSERT INTO companies (
    company_name, company_domain, contact_email, 
    database_name, database_username, database_password,
    subscription_plan, subscription_status, trial_start_date, trial_end_date, 
    enabled_features, company_type, setup_completed
) VALUES 
(
    'Demo Marine Company', 
    'demo-marine', 
    'admin@demo-marine.com',
    'demo_marine_vessels',
    'demo_marine_user',
    'ZGVtb19tYXJpbmVfcGFzc18yMDI0', -- Base64: 'demo_marine_pass_2024'
    'trial',
    'active',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    '["basic_logging", "offline_sync", "basic_reports"]',
    'towboat',
    TRUE
),
(
    'Acme Shipping Corporation',
    'acme-shipping', 
    'it@acmeshipping.com',
    'acme_shipping_vessels',
    'acme_shipping_user',
    'YWNtZV9zaGlwcGluZ19wYXNzXzIwMjQ=', -- Base64: 'acme_shipping_pass_2024'
    'professional',
    'active', 
    DATE_SUB(CURDATE(), INTERVAL 60 DAY),
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    '["basic_logging", "offline_sync", "basic_reports", "crew_management", "advanced_reports", "api_access", "maintenance_tracking"]',
    'supply',
    TRUE
);

-- Add sample contacts
INSERT INTO company_contacts (company_id, contact_name, contact_email, contact_role, is_primary) VALUES
(1, 'Demo Admin', 'admin@demo-marine.com', 'owner', TRUE),
(1, 'Demo User', 'user@demo-marine.com', 'user', FALSE),
(2, 'John Smith', 'it@acmeshipping.com', 'admin', TRUE),
(2, 'Jane Billing', 'billing@acmeshipping.com', 'billing', FALSE);

-- Add sample prefixes in use
INSERT INTO database_prefixes (prefix, company_id, status, assigned_at) VALUES
('demo', 1, 'in_use', NOW()),
('acme', 2, 'in_use', NOW());

-- Useful views for reporting
CREATE VIEW company_summary AS
SELECT 
    c.id,
    c.company_name,
    c.company_domain,
    c.contact_email,
    c.subscription_plan,
    c.subscription_status,
    c.trial_end_date,
    c.subscription_end_date,
    c.monthly_price,
    c.max_vessels,
    c.max_users,
    c.setup_completed,
    c.created_at,
    c.last_access_at,
    DATEDIFF(COALESCE(c.subscription_end_date, c.trial_end_date), CURDATE()) as days_until_expiry,
    (SELECT COUNT(*) FROM company_contacts WHERE company_id = c.id AND is_active = TRUE) as contact_count,
    (SELECT COUNT(*) FROM payments WHERE company_id = c.id AND status = 'completed') as payment_count
FROM companies c;

CREATE VIEW trial_companies AS
SELECT 
    c.*,
    DATEDIFF(c.trial_end_date, CURDATE()) as days_remaining,
    CASE 
        WHEN c.trial_end_date < CURDATE() THEN 'EXPIRED'
        WHEN DATEDIFF(c.trial_end_date, CURDATE()) <= 3 THEN 'EXPIRING_SOON'
        WHEN DATEDIFF(c.trial_end_date, CURDATE()) <= 7 THEN 'EXPIRING_THIS_WEEK'
        ELSE 'ACTIVE'
    END as trial_status
FROM companies c 
WHERE c.subscription_plan = 'trial' AND c.subscription_status = 'active';

CREATE VIEW subscription_revenue AS
SELECT 
    subscription_plan,
    COUNT(*) as company_count,
    SUM(monthly_price) as monthly_revenue,
    SUM(annual_price) as potential_annual_revenue
FROM companies 
WHERE subscription_status IN ('active', 'suspended')
GROUP BY subscription_plan;

COMMIT;
