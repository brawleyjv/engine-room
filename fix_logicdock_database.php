<?php
/**
 * Fix Company Database Configuration
 * This script updates the LogicDock company record with proper database settings
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing LogicDock Company Database Configuration</h2>\n";

try {
    // Connect using root (working credentials)
    $conn = new mysqli('localhost', 'root', '', 'vessel_license_master');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database successfully</p>\n";
    
    // Find the LogicDock company
    $sql = "SELECT id, company_name, company_domain FROM companies WHERE company_domain = 'logicdock' OR company_name LIKE '%logicdock%' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $company = $result->fetch_assoc();
        $company_id = $company['id'];
        $company_name = $company['company_name'];
        
        echo "<p>Found company: <strong>{$company_name}</strong> (ID: {$company_id})</p>\n";
        
        // For a shared database setup, we'll use the same database but with tenant isolation
        // This is more practical than separate databases for each company
        $database_config = [
            'database_host' => 'localhost',
            'database_name' => 'vessel_license_master', // Using the same database for simplicity
            'database_username' => 'root', // Using working credentials
            'database_password' => '' // Empty password for root on local XAMPP
        ];
        
        // Update the company record
        $update_sql = "UPDATE companies SET 
                        database_host = ?,
                        database_name = ?,
                        database_username = ?,
                        database_password = ?
                      WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('ssssi', 
            $database_config['database_host'],
            $database_config['database_name'],
            $database_config['database_username'],
            $database_config['database_password'],
            $company_id
        );
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Successfully updated database configuration for {$company_name}</p>\n";
            
            // Verify the update
            $verify_sql = "SELECT database_host, database_name, database_username FROM companies WHERE id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param('i', $company_id);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $updated_company = $verify_result->fetch_assoc();
            
            echo "<h3>Updated Configuration:</h3>\n";
            echo "<ul>\n";
            echo "<li>Database Host: " . htmlspecialchars($updated_company['database_host']) . "</li>\n";
            echo "<li>Database Name: " . htmlspecialchars($updated_company['database_name']) . "</li>\n";
            echo "<li>Database Username: " . htmlspecialchars($updated_company['database_username']) . "</li>\n";
            echo "</ul>\n";
            
            echo "<p style='color: green; font-weight: bold;'>🎉 Database configuration complete! You can now try logging in again.</p>\n";
            echo "<p><a href='/welcome.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Dashboard</a></p>\n";
            
        } else {
            echo "<p style='color: red;'>❌ Failed to update database configuration: " . $stmt->error . "</p>\n";
        }
        
    } else {
        echo "<p style='color: red;'>❌ LogicDock company not found in database</p>\n";
        
        // Show available companies for debugging
        echo "<h3>Available companies:</h3>\n";
        $list_sql = "SELECT id, company_name, company_domain FROM companies ORDER BY id DESC LIMIT 10";
        $list_result = $conn->query($list_sql);
        echo "<ul>\n";
        while ($comp = $list_result->fetch_assoc()) {
            echo "<li>ID: {$comp['id']} - {$comp['company_name']} ({$comp['company_domain']})</li>\n";
        }
        echo "</ul>\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='/debug_logicdock.php'>View Company Debug Info</a> | <a href='/welcome.php'>Try Dashboard</a></p>\n";
?>
