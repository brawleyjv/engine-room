<?php
/**
 * Company Not Found Error Page
 * Shown when a company domain cannot be found or accessed
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Not Found - Vessel Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 500px;
            background: white;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 4rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="mb-3">Company Not Found</h2>
        <p class="text-muted mb-4">
            We couldn't find the company you're looking for. This could be because:
        </p>
        <ul class="text-start text-muted">
            <li>The company domain is incorrect</li>
            <li>The company account hasn't been set up yet</li>
            <li>There was an issue during registration</li>
        </ul>
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary me-2">
                <i class="fas fa-home me-2"></i>Go Home
            </a>
            <a href="signup.php" class="btn btn-outline-secondary">
                <i class="fas fa-plus me-2"></i>Sign Up
            </a>
        </div>
        <div class="mt-3">
            <small class="text-muted">
                Need help? <a href="mailto:support@vessellogger.com">Contact Support</a>
            </small>
        </div>
    </div>
</body>
</html>
