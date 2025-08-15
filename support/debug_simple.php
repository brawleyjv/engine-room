<?php
/**
 * Simple Debug - No Config Includes
 */

// Prevent any redirects or session issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Domain Debug</h2>\n";

// Test domain
$test_domain = $_GET['domain'] ?? 'testcompany123';
echo "<p>Testing domain: <strong>$test_domain</strong></p>\n";

// Manual database connection (hardcoded for testing)
echo "<h3>Database Connection Test:</h3>\n";

// Try different database configurations
$db_configs = [
    ['host' => 'localhost', 'user' => 'license_admin', 'pass' => 'Zhq4VNrT', 'db' => 'vessel_license_master'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'vessel_license_master'],
    ['host' => '127.0.0.1', 'user' => 'license_admin', 'pass' => 'Zhq4VNrT', 'db' => 'vessel_license_master']
];

foreach ($db_configs as $i => $config) {
    echo "<h4>Config " . ($i + 1) . ": {$config['user']}@{$config['host']}/{$config['db']}</h4>\n";
    
    try {
        $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
        
        if ($conn->connect_error) {
            echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>\n";
        } else {
            echo "<p style='color: green;'>✅ Connection successful!</p>\n";
            
            // Check if companies table exists
            $result = $conn->query("SHOW TABLES LIKE 'companies'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ Companies table exists</p>\n";
                
                // Count companies
                $result = $conn->query("SELECT COUNT(*) as count FROM companies");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p>Total companies: {$row['count']}</p>\n";
                }
                
                // Check specific domain
                $stmt = $conn->prepare("SELECT id, company_name FROM companies WHERE company_domain = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $test_domain);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $company = $result->fetch_assoc();
                        echo "<p style='color: red;'>❌ Domain '$test_domain' is TAKEN by: " . htmlspecialchars($company['company_name']) . "</p>\n";
                    } else {
                        echo "<p style='color: green;'>✅ Domain '$test_domain' is AVAILABLE</p>\n";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Query prepare failed</p>\n";
                }
                
            } else {
                echo "<p style='color: red;'>❌ Companies table does not exist</p>\n";
                
                // Show available tables
                $result = $conn->query("SHOW TABLES");
                if ($result) {
                    echo "<p>Available tables:</p><ul>\n";
                    while ($row = $result->fetch_array()) {
                        echo "<li>" . $row[0] . "</li>\n";
                    }
                    echo "</ul>\n";
                }
            }
            
            $conn->close();
            break; // Stop after first successful connection
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    }
}

echo "<hr>\n";
echo "<p><a href='?domain=testcompany123'>Test 'testcompany123'</a> | ";
echo "<a href='?domain=newcompany456'>Test 'newcompany456'</a> | ";
echo "<a href='?domain=anothertest'>Test 'anothertest'</a></p>\n";
?>
