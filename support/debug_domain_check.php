<?php
/**
 * Debug Domain Availability Check
 */

// Include the config to get database settings
require_once __DIR__ . '/config_saas.php';

echo "<h2>Domain Availability Debug</h2>\n";

// Test domain to check
$test_domain = $_GET['domain'] ?? 'testcompany123';

echo "<p>Testing domain: <strong>$test_domain</strong></p>\n";

// Debug database config
echo "<h3>Database Config:</h3>\n";
echo "<pre>\n";
print_r($license_db_config);
echo "</pre>\n";

// Test database connection
echo "<h3>Database Connection Test:</h3>\n";
try {
    $license_conn = new mysqli(
        $license_db_config['host'],
        $license_db_config['username'],
        $license_db_config['password'],
        $license_db_config['database']
    );
    
    if ($license_conn->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $license_conn->connect_error . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Connection successful</p>\n";
        
        // Check if companies table exists
        $result = $license_conn->query("SHOW TABLES LIKE 'companies'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✅ Companies table exists</p>\n";
            
            // Check table structure
            $result = $license_conn->query("DESCRIBE companies");
            echo "<h4>Companies table structure:</h4>\n<pre>\n";
            while ($row = $result->fetch_assoc()) {
                echo "- {$row['Field']} ({$row['Type']})\n";
            }
            echo "</pre>\n";
            
            // Check current companies
            $result = $license_conn->query("SELECT company_domain FROM companies LIMIT 10");
            echo "<h4>Existing company domains:</h4>\n<ul>\n";
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<li>" . htmlspecialchars($row['company_domain']) . "</li>\n";
                }
            } else {
                echo "<li>No companies found</li>\n";
            }
            echo "</ul>\n";
            
            // Test the specific domain
            $stmt = $license_conn->prepare("SELECT id, company_name FROM companies WHERE company_domain = ?");
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
            echo "<p style='color: red;'>❌ Companies table does not exist</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='?domain=testcompany123'>Test 'testcompany123'</a> | ";
echo "<a href='?domain=newcompany456'>Test 'newcompany456'</a> | ";
echo "<a href='?domain=anothertest'>Test 'anothertest'</a></p>\n";
?>
