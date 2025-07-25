<?php
$host = 'localhost';
$db = 'VesselData';
$user = 'chief';
$pass = 'rustyzeller';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>