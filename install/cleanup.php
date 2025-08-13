<?php
/**
 * Installation Cleanup Script
 * Removes installation session data after successful completion
 */
session_start();

if ($_POST && isset($_SESSION['install_data'])) {
    // Log successful installation (optional)
    $customer_id = $_SESSION['install_data']['customer_id'] ?? 'unknown';
    $company_name = $_SESSION['install_data']['company_name'] ?? 'Unknown Company';
    
    // Clear installation session data
    unset($_SESSION['install_data']);
    
    // Optional: Log to installation log file
    $log_entry = date('Y-m-d H:i:s') . " - Installation completed for Customer ID: $customer_id ($company_name)\n";
    file_put_contents('../logs/installations.log', $log_entry, FILE_APPEND | LOCK_EX);
    
    echo json_encode(['status' => 'success', 'message' => 'Installation data cleaned up']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No installation data to clean']);
}
?>
