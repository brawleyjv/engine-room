<?php
/**
 * Vessel Management SaaS Installation Wizard
 * Similar to WordPress wp-admin/install.php
 */

session_start();

// Installation steps
$steps = [
    1 => 'Welcome & Requirements Check',
    2 => 'Company Information',
    3 => 'Admin User Setup',
    4 => 'Vessel Configuration', 
    5 => 'Database Creation',
    6 => 'Module Selection',
    7 => 'Trial Activation'
];

$current_step = $_GET['step'] ?? 1;

// Check if already installed
if (file_exists('../config_installed.php') && $current_step == 1) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Management System - Installation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .step-indicator { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .btn { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .btn:hover { background: #005a87; }
        .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 3px; margin-bottom: 15px; }
        .success { background: #e8f5e8; color: #2e7d32; padding: 10px; border-radius: 3px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>🚢 Vessel Management System</h1>
    <p>Professional Vessel Operations Management Platform</p>
    
    <div class="step-indicator">
        <strong>Step <?php echo $current_step; ?> of <?php echo count($steps); ?>:</strong> 
        <?php echo $steps[$current_step]; ?>
    </div>

    <?php
    switch($current_step) {
        case 1:
            include 'step1_welcome.php';
            break;
        case 2:
            include 'step2_company.php';
            break;
        case 3:
            include 'step3_admin.php';
            break;
        case 4:
            include 'step4_vessel.php';
            break;
        case 5:
            include 'step5_database.php';
            break;
        case 6:
            include 'step6_modules.php';
            break;
        case 7:
            include 'step7_complete.php';
            break;
        default:
            echo "<p>Invalid installation step.</p>";
    }
    ?>
</body>
</html>
