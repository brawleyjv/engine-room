<?php
/**
 * Simple Domain Test - Standalone Version
 * Tests domain availability without full config auto-initialization
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the database config directly (matching our local setup)
$license_db_config = [
    'host' => 'localhost',
    'database' => 'vessel_license_master',
    'username' => 'license_admin',
    'password' => 'master_license_key_2024'
];

echo "<h2>Simple Domain Availability Test</h2>";

// Copy the checkDomainAvailability function here directly
function checkDomainAvailability($domain) {
    global $license_db_config;
    
    echo "<h3>Testing domain: $domain</h3>";
    
    try {
        echo "<p>Connecting to database...</p>";
        $license_conn = new mysqli(
            $license_db_config['host'],
            $license_db_config['username'],
            $license_db_config['password'],
            $license_db_config['database']
        );
        
        if ($license_conn->connect_error) {
            echo "<p style='color: red;'>❌ Connection failed: " . $license_conn->connect_error . "</p>";
            throw new Exception("License database connection failed: " . $license_conn->connect_error);
        }
        
        echo "<p style='color: green;'>✅ Database connected successfully</p>";
        
        $stmt = $license_conn->prepare("SELECT id, company_name FROM companies WHERE company_domain = ?");
        if (!$stmt) {
            echo "<p style='color: red;'>❌ SQL prepare failed: " . $license_conn->error . "</p>";
            throw new Exception("Database query failed");
        }
        
        echo "<p>Executing query: SELECT id, company_name FROM companies WHERE company_domain = '$domain'</p>";
        
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $is_available = $result->num_rows === 0;
        
        echo "<p><strong>Query result:</strong> Found " . $result->num_rows . " matching records</p>";
        
        if ($result->num_rows > 0) {
            echo "<p style='color: red;'>❌ Domain '$domain' is TAKEN</p>";
            echo "<h4>Existing companies:</h4><ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>ID: " . $row['id'] . " - " . htmlspecialchars($row['company_name']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: green;'>✅ Domain '$domain' is AVAILABLE</p>";
        }
        
        $stmt->close();
        $license_conn->close();
        
        return $is_available; // Available if no rows found
        
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Error: " . $e->getMessage() . "</p>";
        return false; // Assume not available on error
    }
}

// Test domains
$test_domains = ['testcompany123', 'newcompany456', 'mycompany', 'uniquetest789'];

foreach ($test_domains as $domain) {
    $available = checkDomainAvailability($domain);
    echo "<hr>";
}

// Custom test form
echo "<h3>Test Custom Domain</h3>";
if (isset($_GET['test'])) {
    checkDomainAvailability($_GET['test']);
    echo "<hr>";
}

echo "<form method='get'>";
echo "<input type='text' name='test' placeholder='Enter domain to test' value='" . ($_GET['test'] ?? '') . "'>";
echo "<button type='submit'>Test Domain</button>";
echo "</form>";

// Also show what's in the companies table
echo "<h3>Current Companies in Database</h3>";
try {
    $license_conn = new mysqli(
        $license_db_config['host'],
        $license_db_config['username'],
        $license_db_config['password'],
        $license_db_config['database']
    );
    
    if ($license_conn->connect_error) {
        echo "<p style='color: red;'>Cannot connect to show companies</p>";
    } else {
        $result = $license_conn->query("SELECT id, company_name, company_domain FROM companies ORDER BY id");
        
        if ($result && $result->num_rows > 0) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Company Name</th><th>Domain</th></tr>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['company_domain']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No companies found in database</p>";
        }
        
        $license_conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error showing companies: " . $e->getMessage() . "</p>";
}

?>
