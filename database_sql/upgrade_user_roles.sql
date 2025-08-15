-- Database Schema Update for Multi-Tier Role System
-- Add new columns to support enhanced user management

-- Update users table to include role and vessel assignment
ALTER TABLE users 
ADD COLUMN Role VARCHAR(50) DEFAULT 'deck_crew' AFTER LastName,
ADD COLUMN AssignedVesselID INT NULL AFTER Role,
ADD COLUMN LastLogin DATETIME NULL AFTER AssignedVesselID,
ADD COLUMN CreatedBy INT NULL AFTER LastLogin,
ADD COLUMN CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP AFTER CreatedBy,
ADD COLUMN UpdatedDate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER CreatedDate;

-- Add foreign key constraint for vessel assignment
ALTER TABLE users 
ADD CONSTRAINT fk_users_vessel 
FOREIGN KEY (AssignedVesselID) REFERENCES vessels(VesselID) ON DELETE SET NULL;

-- Create user role assignments table for more complex role management (future use)
CREATE TABLE IF NOT EXISTS user_role_assignments (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Role VARCHAR(50) NOT NULL,
    VesselID INT NULL,
    IsActive BOOLEAN DEFAULT TRUE,
    AssignedBy INT NOT NULL,
    AssignedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpiryDate DATETIME NULL,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (VesselID) REFERENCES vessels(VesselID) ON DELETE CASCADE,
    FOREIGN KEY (AssignedBy) REFERENCES users(UserID),
    UNIQUE KEY unique_user_vessel_role (UserID, VesselID, Role)
);

-- Create user login log for audit purposes
CREATE TABLE IF NOT EXISTS user_login_log (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    LoginTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    IPAddress VARCHAR(45),
    UserAgent TEXT,
    LoginSuccess BOOLEAN DEFAULT TRUE,
    LogoutTime DATETIME NULL,
    SessionDuration INT NULL, -- in minutes
    VesselID INT NULL, -- which vessel they were working on
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (VesselID) REFERENCES vessels(VesselID) ON DELETE SET NULL,
    INDEX idx_user_login_time (UserID, LoginTime),
    INDEX idx_login_time (LoginTime)
);

-- Update existing users with default roles based on IsAdmin flag
UPDATE users 
SET Role = CASE 
    WHEN IsAdmin = 1 THEN 'office_admin'
    ELSE 'engineer'
END
WHERE Role IS NULL OR Role = '';

-- Create some sample users for testing (you can remove this in production)
INSERT INTO users (Username, Email, PasswordHash, FirstName, LastName, Role, AssignedVesselID, IsActive)
VALUES 
-- Shore-based users
('fleet_mgr', 'fleet@company.com', '$2y$10$example_hash_fleet_manager', 'John', 'Fleet', 'fleet_manager', NULL, 1),
('port_eng', 'port@company.com', '$2y$10$example_hash_port_engineer', 'Sarah', 'Port', 'port_engineer', NULL, 1),
('support', 'support@company.com', '$2y$10$example_hash_support', 'Mike', 'Support', 'support_tech', NULL, 1),

-- Vessel-based users (assign to vessel ID 1 if it exists)
('chief_eng', 'chief@vessel.com', '$2y$10$example_hash_chief', 'Robert', 'Chief', 'chief_engineer', 1, 1),
('engineer1', 'eng1@vessel.com', '$2y$10$example_hash_eng1', 'James', 'Engineer', 'engineer', 1, 1),
('wheelman1', 'wheel@vessel.com', '$2y$10$example_hash_wheel', 'David', 'Helm', 'wheelman', 1, 1),
('deck1', 'deck@vessel.com', '$2y$10$example_hash_deck', 'Carlos', 'Deck', 'deck_crew', 1, 1)
ON DUPLICATE KEY UPDATE Username = VALUES(Username);

-- Add vessel-specific database credentials table
CREATE TABLE IF NOT EXISTS vessel_database_configs (
    VesselID INT PRIMARY KEY,
    DatabaseName VARCHAR(100) NOT NULL,
    Username VARCHAR(50) NOT NULL,
    Password VARCHAR(255) NOT NULL, -- Encrypted
    CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedDate DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (VesselID) REFERENCES vessels(VesselID) ON DELETE CASCADE
);

-- Sample vessel database configurations
INSERT INTO vessel_database_configs (VesselID, DatabaseName, Username, Password)
SELECT 
    VesselID,
    CONCAT('vessel_', LPAD(VesselID, 3, '0'), '_data') as DatabaseName,
    CONCAT('vessel_', LPAD(VesselID, 3, '0'), '_user') as Username,
    CONCAT('vessel_', LPAD(VesselID, 3, '0'), '_pass_2024') as Password
FROM vessels 
WHERE VesselID <= 10 -- First 10 vessels
ON DUPLICATE KEY UPDATE VesselID = VALUES(VesselID);

-- Create permissions audit table
CREATE TABLE IF NOT EXISTS user_permissions_log (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Permission VARCHAR(100) NOT NULL,
    Granted BOOLEAN NOT NULL,
    RequestedBy INT NOT NULL,
    RequestTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    Reason TEXT,
    FOREIGN KEY (UserID) REFERENCES users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (RequestedBy) REFERENCES users(UserID),
    INDEX idx_user_permission (UserID, Permission),
    INDEX idx_request_time (RequestTime)
);

-- Add indexes for better performance
ALTER TABLE users ADD INDEX idx_role (Role);
ALTER TABLE users ADD INDEX idx_assigned_vessel (AssignedVesselID);
ALTER TABLE users ADD INDEX idx_active_users (IsActive, Role);

COMMIT;
