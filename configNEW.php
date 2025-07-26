<?php
$host = 'db5018286956.hosting-data.io';
$db = 'dbs14497521';
$username = 'dbu4692741';
$pass = '!vesselmanagement2025!';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>