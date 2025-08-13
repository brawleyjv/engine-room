# LogicDock Tracking Database Tables
# Add these tables to your master database for comprehensive tracking

USE vessellogger_master;

-- Main tracking table for LogicDock events
CREATE TABLE IF NOT EXISTS logicdock_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    event_type ENUM('trial_started', 'subscription_started', 'subscription_canceled', 'subscription_upgraded', 'database_activity', 'payment_failed', 'account_suspended') NOT NULL,
    event_data JSON,
    sent_to_logicdock BOOLEAN DEFAULT FALSE,
    api_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id),
    INDEX idx_customer_event (customer_id, event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_sent_status (sent_to_logicdock, created_at)
);

-- Customer activity logs for database usage tracking
CREATE TABLE IF NOT EXISTS customer_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    activity_type ENUM('user_login', 'log_entry_created', 'vessel_added', 'user_added', 'report_generated', 'data_export', 'settings_changed', 'module_activated') NOT NULL,
    activity_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id),
    INDEX idx_customer_activity (customer_id, activity_type),
    INDEX idx_activity_date (created_at),
    INDEX idx_activity_type (activity_type)
);

-- Update customer_licenses table with database name tracking
ALTER TABLE customer_licenses 
ADD COLUMN IF NOT EXISTS database_name VARCHAR(100) NULL AFTER customer_id,
ADD COLUMN IF NOT EXISTS database_prefix VARCHAR(4) NULL AFTER database_name,
ADD COLUMN IF NOT EXISTS database_user VARCHAR(100) NULL AFTER database_prefix,
ADD COLUMN IF NOT EXISTS support_username VARCHAR(50) DEFAULT 'logicdock_support' AFTER database_user,
ADD COLUMN IF NOT EXISTS support_password_hint VARCHAR(100) NULL AFTER support_username,
ADD COLUMN IF NOT EXISTS signup_ip VARCHAR(45) NULL,
ADD COLUMN IF NOT EXISTS signup_user_agent TEXT NULL,
ADD COLUMN IF NOT EXISTS installation_completed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS first_login_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS last_activity_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS total_logins INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS total_log_entries INT DEFAULT 0;

-- API response logging
CREATE TABLE IF NOT EXISTS api_response_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(255) NOT NULL,
    request_data JSON,
    response_code INT,
    response_data TEXT,
    execution_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint_date (endpoint, created_at),
    INDEX idx_response_code (response_code)
);

-- Revenue tracking table
CREATE TABLE IF NOT EXISTS revenue_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    transaction_type ENUM('subscription', 'module', 'upgrade', 'refund') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50),
    payment_processor VARCHAR(50),
    transaction_id VARCHAR(255),
    subscription_period_start DATE,
    subscription_period_end DATE,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id),
    INDEX idx_customer_revenue (customer_id, processed_at),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_processed_date (processed_at)
);

-- Daily summary statistics for LogicDock dashboard
CREATE TABLE IF NOT EXISTS daily_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE UNIQUE NOT NULL,
    total_customers INT DEFAULT 0,
    active_trials INT DEFAULT 0,
    active_subscriptions INT DEFAULT 0,
    new_trials_today INT DEFAULT 0,
    trial_conversions_today INT DEFAULT 0,
    daily_revenue DECIMAL(10,2) DEFAULT 0.00,
    monthly_recurring_revenue DECIMAL(10,2) DEFAULT 0.00,
    database_activity_count INT DEFAULT 0,
    total_log_entries_today INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stat_date (stat_date)
);

-- Views for easy reporting
CREATE OR REPLACE VIEW customer_overview AS
SELECT 
    cl.customer_id,
    cl.company_name,
    cl.database_name,
    cl.database_prefix,
    cl.admin_email,
    cl.plan,
    cl.status,
    cl.created_at as signup_date,
    cl.expires_at,
    cl.installation_completed_at,
    cl.first_login_at,
    cl.last_activity_at,
    cl.total_logins,
    cl.total_log_entries,
    DATEDIFF(cl.expires_at, NOW()) as days_remaining,
    COUNT(DISTINCT cm.module_code) as active_modules,
    SUM(md.monthly_price) as monthly_module_revenue,
    CASE 
        WHEN cl.plan = 'trial' AND cl.expires_at < NOW() THEN 'EXPIRED'
        WHEN cl.plan = 'trial' AND DATEDIFF(cl.expires_at, NOW()) <= 3 THEN 'EXPIRING_SOON'
        WHEN cl.plan = 'trial' THEN 'ACTIVE_TRIAL'
        WHEN cl.status = 'active' THEN 'PAYING_CUSTOMER'
        ELSE cl.status
    END as customer_status
FROM customer_licenses cl
LEFT JOIN customer_modules cm ON cl.customer_id = cm.customer_id AND cm.status = 'active'
LEFT JOIN module_definitions md ON cm.module_code = md.module_code
GROUP BY cl.customer_id;

CREATE OR REPLACE VIEW logicdock_dashboard AS
SELECT 
    'summary' as report_type,
    COUNT(*) as total_customers,
    SUM(CASE WHEN plan = 'trial' AND status = 'active' THEN 1 ELSE 0 END) as active_trials,
    SUM(CASE WHEN plan != 'trial' AND status = 'active' THEN 1 ELSE 0 END) as paying_customers,
    SUM(CASE WHEN created_at >= CURDATE() THEN 1 ELSE 0 END) as signups_today,
    SUM(CASE WHEN plan = 'trial' AND expires_at < NOW() AND status = 'active' THEN 1 ELSE 0 END) as expired_trials,
    AVG(total_log_entries) as avg_log_entries_per_customer,
    COUNT(DISTINCT database_prefix) as unique_prefixes_used
FROM customer_licenses;

-- Trigger to update customer activity stats
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_customer_activity 
AFTER INSERT ON customer_activity_logs
FOR EACH ROW
BEGIN
    IF NEW.activity_type = 'user_login' THEN
        UPDATE customer_licenses 
        SET 
            total_logins = total_logins + 1,
            last_activity_at = NOW(),
            first_login_at = COALESCE(first_login_at, NOW())
        WHERE customer_id = NEW.customer_id;
    ELSEIF NEW.activity_type = 'log_entry_created' THEN
        UPDATE customer_licenses 
        SET 
            total_log_entries = total_log_entries + 1,
            last_activity_at = NOW()
        WHERE customer_id = NEW.customer_id;
    ELSE
        UPDATE customer_licenses 
        SET last_activity_at = NOW()
        WHERE customer_id = NEW.customer_id;
    END IF;
END//
DELIMITER ;

-- Function to get customer database activity summary
DELIMITER //
CREATE FUNCTION IF NOT EXISTS get_customer_activity_score(cust_id VARCHAR(50)) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE score INT DEFAULT 0;
    DECLARE login_count INT DEFAULT 0;
    DECLARE log_count INT DEFAULT 0;
    DECLARE days_active INT DEFAULT 0;
    
    SELECT 
        total_logins,
        total_log_entries,
        DATEDIFF(COALESCE(last_activity_at, created_at), created_at) + 1
    INTO login_count, log_count, days_active
    FROM customer_licenses 
    WHERE customer_id = cust_id;
    
    -- Calculate activity score (0-100)
    SET score = LEAST(100, 
        (login_count * 2) + 
        (log_count * 0.1) + 
        (days_active * 1)
    );
    
    RETURN score;
END//
DELIMITER ;

-- Indexes for performance
CREATE INDEX idx_customer_database_name ON customer_licenses(database_name);
CREATE INDEX idx_customer_prefix ON customer_licenses(database_prefix);
CREATE INDEX idx_customer_activity_customer ON customer_activity_logs(customer_id, created_at);
CREATE INDEX idx_logicdock_tracking_unsent ON logicdock_tracking(sent_to_logicdock, created_at);

-- Sample data population (for testing)
-- INSERT INTO logicdock_tracking (customer_id, event_type, event_data) VALUES
-- ('CBL001', 'trial_started', '{"company_name": "Canal Barge Line", "database_name": "CBL_logicdoc"}'),
-- ('MTC002', 'subscription_started', '{"company_name": "Marquette Towing", "plan": "professional", "amount": 149}');
