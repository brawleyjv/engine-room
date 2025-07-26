<?php
require_once 'config.php';

echo "Creating users table and setting up authentication system...\n\n";

// Step 1: Create users table
echo "1. Creating users table...\n";
$sql_users = "
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
)";

if ($conn->query($sql_users)) {
    echo "✓ Users table created successfully\n";
} else {
    echo "✗ Error creating users table: " . $conn->error . "\n";
}

// Step 2: Create admin user
echo "\n2. Creating default admin user...\n";
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql_admin = "
INSERT IGNORE INTO users (Username, Email, PasswordHash, FirstName, LastName, IsAdmin, IsActive) 
VALUES ('admin', 'admin@vessel.local', ?, 'Admin', 'User', TRUE, TRUE)
";

$stmt = $conn->prepare($sql_admin);
$stmt->bind_param('s', $admin_password);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "✓ Admin user created successfully\n";
    } else {
        echo "✓ Admin user already exists\n";
    }
} else {
    echo "✗ Error creating admin user: " . $conn->error . "\n";
}

// Step 3: Add RecordedBy columns (if they don't exist)
echo "\n3. Adding RecordedBy columns to equipment tables...\n";

$tables = ['mainengines', 'generators', 'gears'];
foreach ($tables as $table) {
    echo "   Adding RecordedBy to $table table...\n";
    
    // Check if column exists first
    $check_sql = "SHOW COLUMNS FROM $table LIKE 'RecordedBy'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        $add_column_sql = "ALTER TABLE $table ADD COLUMN RecordedBy INT NULL";
        if ($conn->query($add_column_sql)) {
            echo "   ✓ RecordedBy column added to $table\n";
        } else {
            echo "   ✗ Error adding RecordedBy to $table: " . $conn->error . "\n";
        }
    } else {
        echo "   ✓ RecordedBy column already exists in $table\n";
    }
}

// Step 4: Add foreign key constraints (optional - might fail if there's existing data)
echo "\n4. Adding foreign key constraints...\n";
foreach ($tables as $table) {
    $fk_sql = "ALTER TABLE $table ADD FOREIGN KEY (RecordedBy) REFERENCES users(UserID)";
    if ($conn->query($fk_sql)) {
        echo "   ✓ Foreign key added to $table\n";
    } else {
        echo "   ⚠ Could not add foreign key to $table (this is OK if there's existing data): " . $conn->error . "\n";
    }
}

echo "\n=== Setup Complete! ===\n";
echo "Default login credentials:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "\nPlease change the admin password after first login!\n";

$conn->close();
?>
