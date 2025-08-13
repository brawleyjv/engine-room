<?php
/**
 * Simple ping endpoint for connection testing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode([
    'status' => 'online',
    'timestamp' => time(),
    'server_time' => date('Y-m-d H:i:s')
]);
?>
