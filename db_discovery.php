<?php
/**
 * Database Configuration Discovery
 */

echo "<h2>Database Configuration Discovery</h2>\n";

// Check if MySQL is running and what users exist
echo "<h3>MySQL Connection Tests</h3>\n";

// Common database users on VPS servers
$test_users = [
    // Standard configurations
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => 'localhost', 'user' => 'mysql', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'admin', 'pass' => ''],
    
    // Check if vessel_license_master database exists with different users
    ['host' => 'localhost', 'user' => 'vessel_admin', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'vessel_user', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'license_user', 'pass' => ''],
    
    // Common VPS setups
    ['host' => 'localhost', 'user' => 'debian-sys-maint', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
];

$successful_connection = null;

foreach ($test_users as $i => $config) {
    echo "<h4>Test " . ($i + 1) . ": {$config['user']}@{$config['host']}</h4>\n";
    
    try {
        // Try to connect without specifying a database first
        $conn = new mysqli($config['host'], $config['user'], $config['pass']);
        
        if ($conn->connect_error) {
            echo "<p style='color: orange;'>❌ Connection failed: " . $conn->connect_error . "</p>\n";
        } else {
            echo "<p style='color: green;'>✅ Connection successful!</p>\n";
            $successful_connection = $config;
            
            // List all databases
            $result = $conn->query("SHOW DATABASES");
            if ($result) {
                echo "<p><strong>Available databases:</strong></p><ul>\n";
                while ($row = $result->fetch_array()) {
                    $db_name = $row[0];
                    echo "<li>$db_name";
                    
                    // Check if this is our license database
                    if (strpos($db_name, 'vessel') !== false || strpos($db_name, 'license') !== false) {
                        echo " <strong>(⭐ Potential match!)</strong>";
                    }
                    echo "</li>\n";
                }
                echo "</ul>\n";
                
                // Try to access vessel_license_master specifically
                $conn->select_db('vessel_license_master');
                if ($conn->error) {
                    echo "<p style='color: orange;'>vessel_license_master database does not exist</p>\n";
                } else {
                    echo "<p style='color: green;'>✅ vessel_license_master database accessible!</p>\n";
                    
                    // Check tables in this database
                    $result = $conn->query("SHOW TABLES");
                    if ($result) {
                        echo "<p><strong>Tables in vessel_license_master:</strong></p><ul>\n";
                        while ($row = $result->fetch_array()) {
                            echo "<li>" . $row[0] . "</li>\n";
                        }
                        echo "</ul>\n";
                    }
                }
            }
            
            $conn->close();
            echo "<hr>\n";
            break; // Stop after first successful connection
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    }
}

if (!$successful_connection) {
    echo "<h3>❌ No database connections successful</h3>\n";
    echo "<p>This suggests that:</p>\n";
    echo "<ul>\n";
    echo "<li>MySQL might not be running</li>\n";
    echo "<li>MySQL might be configured to only allow specific users</li>\n";
    echo "<li>You might need to create the database user manually</li>\n";
    echo "</ul>\n";
    
    echo "<h4>Next steps:</h4>\n";
    echo "<ol>\n";
    echo "<li>SSH into your VPS server</li>\n";
    echo "<li>Run: <code>sudo mysql -u root -p</code></li>\n";
    echo "<li>Create the license database and user with these commands:</li>\n";
    echo "</ol>\n";
    
    echo "<pre>\n";
    echo "CREATE DATABASE vessel_license_master;\n";
    echo "CREATE USER 'license_admin'@'localhost' IDENTIFIED BY 'license_secure_2024';\n";
    echo "GRANT ALL PRIVILEGES ON vessel_license_master.* TO 'license_admin'@'localhost';\n";
    echo "FLUSH PRIVILEGES;\n";
    echo "</pre>\n";
} else {
    echo "<h3>✅ Working database configuration found!</h3>\n";
    echo "<p>Use these credentials in your config_saas.php:</p>\n";
    echo "<pre>\n";
    echo "Host: {$successful_connection['host']}\n";
    echo "User: {$successful_connection['user']}\n";
    echo "Password: {$successful_connection['pass']}\n";
    echo "</pre>\n";
}
?>
