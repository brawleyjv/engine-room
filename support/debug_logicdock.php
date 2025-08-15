<?php
// Debug script to check logicdock company configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>LogicDock Company Debug</h2>\n";

try {
    // Connect using root (working credentials)
    $conn = new mysqli('localhost', 'root', '', 'vessel_license_master');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database successfully</p>\n";
    
    // Check for logicdock company
    $sql = "SELECT 
                id,
                company_name,
                company_domain,
                database_host,
                database_name,
                database_username,
                database_password,
                subscription_status,
                trial_start_date,
                trial_end_date
            FROM companies 
            WHERE company_domain = 'logicdock' OR company_name LIKE '%logicdock%'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<h3>Found LogicDock Company:</h3>\n";
        while ($company = $result->fetch_assoc()) {
            echo "<table border='1' style='border-collapse: collapse;'>\n";
            echo "<tr><th>Field</th><th>Value</th></tr>\n";
            echo "<tr><td>ID</td><td>" . htmlspecialchars($company['id']) . "</td></tr>\n";
            echo "<tr><td>Company Name</td><td>" . htmlspecialchars($company['company_name']) . "</td></tr>\n";
            echo "<tr><td>Domain</td><td>" . htmlspecialchars($company['company_domain']) . "</td></tr>\n";
            echo "<tr><td>DB Host</td><td>" . htmlspecialchars($company['database_host'] ?: 'NULL') . "</td></tr>\n";
            echo "<tr><td>DB Name</td><td>" . htmlspecialchars($company['database_name'] ?: 'NULL') . "</td></tr>\n";
            echo "<tr><td>DB Username</td><td>" . htmlspecialchars($company['database_username'] ?: 'NULL') . "</td></tr>\n";
            echo "<tr><td>DB Password</td><td>" . htmlspecialchars($company['database_password'] ?: 'NULL') . "</td></tr>\n";
            echo "<tr><td>Status</td><td>" . htmlspecialchars($company['subscription_status']) . "</td></tr>\n";
            echo "<tr><td>Trial Start</td><td>" . htmlspecialchars($company['trial_start_date']) . "</td></tr>\n";
            echo "<tr><td>Trial End</td><td>" . htmlspecialchars($company['trial_end_date']) . "</td></tr>\n";
            echo "</table>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ No company found with domain 'logicdock'</p>\n";
        
        // List all companies
        echo "<h3>All Companies:</h3>\n";
        $sql = "SELECT id, company_name, company_domain, subscription_status FROM companies ORDER BY id DESC";
        $result = $conn->query($sql);
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Company Name</th><th>Domain</th><th>Status</th></tr>\n";
        while ($company = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($company['id']) . "</td>";
            echo "<td>" . htmlspecialchars($company['company_name']) . "</td>";
            echo "<td>" . htmlspecialchars($company['company_domain']) . "</td>";
            echo "<td>" . htmlspecialchars($company['subscription_status']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
