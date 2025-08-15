<?php
$host = 'localhost';
$db = 'vesseldata';
$user = 'license_admin';
$pass = 'rustyzeller';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>