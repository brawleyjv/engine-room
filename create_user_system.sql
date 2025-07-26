-- Create users table for authentication system
USE vesseldata;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    IsAdmin BOOLEAN DEFAULT FALSE,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastLogin TIMESTAMP NULL,
    ResetToken VARCHAR(100) NULL,
    ResetTokenExpiry TIMESTAMP NULL
);

-- Create default admin user (password will be 'admin123' - change this!)
INSERT INTO users (Username, Email, PasswordHash, FirstName, LastName, IsAdmin, IsActive) 
VALUES ('admin', 'admin@vessel.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', TRUE, TRUE);

-- Add RecordedBy column to existing tables to track who made each entry
ALTER TABLE mainengines ADD COLUMN RecordedBy INT NULL;
ALTER TABLE generators ADD COLUMN RecordedBy INT NULL;
ALTER TABLE gears ADD COLUMN RecordedBy INT NULL;

-- Add foreign key constraints
ALTER TABLE mainengines ADD FOREIGN KEY (RecordedBy) REFERENCES users(UserID);
ALTER TABLE generators ADD FOREIGN KEY (RecordedBy) REFERENCES users(UserID);
ALTER TABLE gears ADD FOREIGN KEY (RecordedBy) REFERENCES users(UserID);

SELECT 'User authentication tables created successfully!' as Status;
