<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Basic Database Test</h2>";

// Simple database connection test
$host = 'localhost';
$username = 'license_admin';
$password = 'master_license_key_2024';
$database = 'vessel_license_master';

echo "<p>Testing connection to: $host / $database</p>";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo "<p style='color: red;'>Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Connected!</p>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM companies");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Companies found: " . $row['count'] . "</p>";
        
        if ($row['count'] > 0) {
            echo "<h3>Companies:</h3>";
            $companies = $conn->query("SELECT id, company_name, company_domain FROM companies");
            while ($company = $companies->fetch_assoc()) {
                echo "<p>" . $company['id'] . ": " . $company['company_name'] . " (" . $company['company_domain'] . ")</p>";
            }
        }
    }
    
    $conn->close();
}

echo "<p>Done.</p>";
?>
