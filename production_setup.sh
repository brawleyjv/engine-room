#!/bin/bash
# Production Server Setup Script for LogicDock.org
# Run this script on your production server to set up the license database

echo "🚀 Setting up LogicDock SaaS License Database on Production Server"
echo "============================================================="

# 1. Create the license database and user
echo "📊 Creating license database and user..."

mysql -u root -p << 'EOF'
-- Create the master license database
CREATE DATABASE IF NOT EXISTS vessel_license_master;

-- Create dedicated user for license management
CREATE USER IF NOT EXISTS 'license_admin'@'localhost' IDENTIFIED BY 'LogicDock_License_2024!';
GRANT ALL PRIVILEGES ON vessel_license_master.* TO 'license_admin'@'localhost';

-- Also allow access from webapp user if needed
GRANT SELECT, INSERT, UPDATE ON vessel_license_master.* TO 'webapp_user'@'localhost';

FLUSH PRIVILEGES;
EOF

echo "✅ Database and user created successfully!"

# 2. Import the license database schema
echo "📋 Importing license database schema..."

# You'll need to upload the database_sql/license_database_schema.sql file to the server first
mysql -u license_admin -p vessel_license_master < /path/to/database_sql/license_database_schema.sql

echo "✅ Schema imported successfully!"

# 3. Update production config
echo "🔧 Updating production configuration..."

# Create production config override
cat > /var/www/html/config_production.php << 'EOF'
<?php
/**
 * Production Configuration Override
 * This file overrides local development settings for production
 */

// Production license database configuration
$license_db_config = [
    'host' => 'localhost',
    'database' => 'vessel_license_master',
    'username' => 'license_admin',
    'password' => 'LogicDock_License_2024!'  // Use secure password
];

// Production-specific settings
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('ERROR_LOGGING', true);

// Production database defaults for new companies
$production_db_defaults = [
    'host' => 'localhost',
    'username_prefix' => 'company_',
    'password_length' => 16
];
EOF

echo "✅ Production config created!"

# 4. Set proper permissions
echo "🔒 Setting file permissions..."
chown www-data:www-data /var/www/html/config_production.php
chmod 640 /var/www/html/config_production.php

# 5. Test database connection
echo "🧪 Testing database connection..."

php << 'EOF'
<?php
$config = [
    'host' => 'localhost',
    'database' => 'vessel_license_master', 
    'username' => 'license_admin',
    'password' => 'LogicDock_License_2024!'
];

try {
    $conn = new mysqli($config['host'], $config['username'], $config['password'], $config['database']);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "✅ License database connection successful!\n";
    
    // Test if companies table exists
    $result = $conn->query("SHOW TABLES LIKE 'companies'");
    if ($result->num_rows > 0) {
        echo "✅ Companies table exists!\n";
    } else {
        echo "❌ Companies table missing - please import schema\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
EOF

echo ""
echo "🎉 Production setup complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Upload database_sql/license_database_schema.sql to the server"
echo "2. Import the schema: mysql -u license_admin -p vessel_license_master < database_sql/license_database_schema.sql"
echo "3. Update config_saas.php to include production config"
echo "4. Test signup at https://logicdock.org/signup_test.php"
echo ""
echo "🔧 Database Configuration:"
echo "Host: localhost"
echo "Database: vessel_license_master"
echo "Username: license_admin"
echo "Password: LogicDock_License_2024!"
