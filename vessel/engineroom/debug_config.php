<?php
// TEMPORARY DEBUG CONFIG - REMOVE AFTER FIXING
// Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Your IONOS Database Configuration
$host = 'db5018286956.hosting-data.io
';           // Replace with actual IONOS host
$db = 'dbs14497521';             // Replace with actual database name
$user = 'dbu4692741';           // Replace with actual username
$pass = '@RGT8Be6YefjKUH';       // Replace with actual password

// Test connection
try {
    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
    echo "✅ Database connected successfully!<br>";
    echo "Host: " . $host . "<br>";
    echo "Database: " . $db . "<br>";
    echo "User: " . $user . "<br>";
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Test basic query
try {
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "✅ Database query successful!<br>";
        echo "Tables found: " . $result->num_rows . "<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
}
?>
