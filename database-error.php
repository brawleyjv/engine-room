<?php
session_start();

// If we have a company context, we can show a more helpful message
$company_name = $_SESSION['company_name'] ?? 'your company';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Required - LogicDock</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
            text-align: center;
        }
        .error-icon {
            font-size: 48px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .error-title {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .error-message {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Database Setup in Progress</h1>
        <div class="error-message">
            <p>Thank you for signing up with LogicDock! We're currently setting up the database for <?php echo htmlspecialchars($company_name); ?>.</p>
            <p>This process typically takes just a few moments. Your account is being configured with:</p>
            <ul style="text-align: left; display: inline-block;">
                <li>Secure database environment</li>
                <li>Company-specific data isolation</li>
                <li>Full vessel management capabilities</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <a href="/welcome.php" class="btn btn-primary">Try Again</a>
            <a href="/support_dashboard.php" class="btn btn-secondary">Contact Support</a>
            <a href="/logout.php" class="btn btn-secondary">Sign Out</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 12px; color: #bdc3c7;">
            <p>If this issue persists, please contact our support team.<br>
            Error Code: DB_CONFIG_PENDING</p>
        </div>
    </div>
</body>
</html>
