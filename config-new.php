<?php
// IONOS Database Configuration
// Production database settings for live deployment

$host = 'db5018286956.hosting-data.io';
$db = 'dbs14497521';
$user = 'dbu4692741';
$pass = '!vesselmanagement2025!';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8");
?>
