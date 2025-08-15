<?php
/**
 * Company Context Troubleshooting
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Company Context Troubleshooting</h2>\n";

// Include the SaaS config to test the functions
require_once 'config_saas.php';

echo "<h3>1. Environment Check</h3>\n";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "</p>\n";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "</p>\n";
echo "<p><strong>Current Domain:</strong> " . $_SERVER['HTTP_HOST'] . "</p>\n";

echo "<h3>2. Company Context Determination</h3>\n";
$company_context = determineCompanyContext();
echo "<p><strong>Determined Company Context:</strong> " . ($company_context ?: 'NULL') . "</p>\n";

echo "<h3>3. Session Check</h3>\n";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>4. Company Database Config Test</h3>\n";
if ($company_context) {
    echo "<p>Testing database config for: <strong>$company_context</strong></p>\n";
    $config_result = getCompanyDatabaseConfig($company_context);
    echo "<pre>";
    print_r($config_result);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No company context to test</p>\n";
}

echo "<h3>5. Direct Database Check</h3>\n";
try {
    $conn = new mysqli('localhost', 'root', '', 'vessel_license_master');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database</p>\n";
    
    // Check for companies
    $sql = "SELECT id, company_name, company_domain, subscription_status FROM companies ORDER BY id DESC";
    $result = $conn->query($sql);
    
    echo "<h4>All Companies in Database:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Domain</th><th>Status</th></tr>\n";
    while ($company = $result->fetch_assoc()) {
        $highlight = ($company['company_domain'] === 'logicdock') ? 'style="background-color: yellow;"' : '';
        echo "<tr $highlight>";
        echo "<td>" . htmlspecialchars($company['id']) . "</td>";
        echo "<td>" . htmlspecialchars($company['company_name']) . "</td>";
        echo "<td>" . htmlspecialchars($company['company_domain']) . "</td>";
        echo "<td>" . htmlspecialchars($company['subscription_status']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<h3>6. Test Actions</h3>\n";
echo "<p><a href='/index.php'>Test Index Page</a></p>\n";
echo "<p><a href='/welcome.php'>Test Welcome Page</a></p>\n";
echo "<p><a href='/signup.php'>Test Signup Page</a></p>\n";
echo "<p><a href='/login_enhanced.php'>Test Login Page</a></p>\n";
?>
