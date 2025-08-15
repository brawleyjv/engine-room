<?php
// Debug script to trace what's happening
session_start();

echo "<h2>Debug: Root Access Trace</h2>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>HTTP Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p><strong>Query String:</strong> " . ($_SERVER['QUERY_STRING'] ?? 'none') . "</p>";

echo "<h3>Session Data:</h3>";
if (empty($_SESSION)) {
    echo "<p>No session data</p>";
} else {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h3>GET Parameters:</h3>";
if (empty($_GET)) {
    echo "<p>No GET parameters</p>";
} else {
    echo "<pre>";
    print_r($_GET);
    echo "</pre>";
}

echo "<h3>Files in Directory:</h3>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

echo "<h3>Test Links:</h3>";
echo "<p><a href='index.php'>Direct to index.php</a></p>";
echo "<p><a href='simple_landing.php'>Direct to simple_landing.php</a></p>";
echo "<p><a href='signup.php'>Direct to signup.php</a></p>";
?>
