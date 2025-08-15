<?php
/**
 * Modules Showcase Page
 * Display all available modules and their features
 */

require_once __DIR__ . '/modules_config.php';

$basic_modules = getBasicPackageModules();
$addon_modules = getAddOnModules();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger Modules - Maritime Logging Features</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        
        .module-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .module-card.basic {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #f8fff8, #ffffff);
        }
        
        .module-card.addon {
            border-color: var(--secondary-color);
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .module-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-basic {
            background: var(--success-color);
            color: white;
        }
        
        .badge-addon {
            background: var(--secondary-color);
            color: white;
        }
        
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .icon-basic {
            background: var(--success-color);
            color: white;
        }
        
        .icon-addon {
            background: var(--secondary-color);
            color: white;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .pricing-summary {
            background: var(--light-color);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .category-section {
            margin: 3rem 0;
        }
        
        .category-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="font-weight: 700; color: var(--primary-color);">
                <i class="fas fa-ship me-2" style="color: var(--secondary-color);"></i>
                Vessel Logger
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="modules.php">Modules</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login_enhanced.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3 ms-2" href="signup.php">
                            Start Free Trial
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-10 mx-auto">
                    <h1 class="display-4 mb-3">Maritime Logging Modules</h1>
                    <p class="lead">
                        Choose the features that fit your operations. Start with our Basic Package ($49/vessel/month) 
                        and add specialized modules for $24.95 per module per vessel per month. Fleet discounts available for 5+ vessels.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Summary -->
    <section class="container my-5">
        <div class="pricing-summary text-center">
            <h3 class="mb-3">Simple, Transparent Pricing</h3>
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-gift text-success me-2"></i>30-Day Free Trial</h5>
                    <p>1 vessel, basic features, up to 5 users</p>
                </div>
                <div class="col-md-4">
                    <h5><i class="fas fa-ship text-primary me-2"></i>Basic Package: $49/vessel/month</h5>
                    <p>Core logging features per vessel</p>
                </div>
                <div class="col-md-4">
                    <h5><i class="fas fa-plus text-secondary me-2"></i>Add-Ons: $24.95/module/vessel/month</h5>
                    <p>Enhanced capabilities per vessel. Fleet discounts available.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Basic Package Modules -->
    <section class="container">
        <div class="category-section">
            <div class="category-header">
                <div class="category-icon">
                    <i class="fas fa-anchor"></i>
                </div>
                <h2>Basic Package - Included Features</h2>
                <p class="lead">Core maritime logging capabilities included in your $49/month subscription</p>
            </div>
            
            <div class="row">
                <?php foreach ($basic_modules as $id => $module): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="module-card basic position-relative">
                        <div class="module-badge badge-basic">Included</div>
                        <div class="module-icon icon-basic">
                            <?php 
                            $icons = [
                                'basic_wheelhouse' => 'compass',
                                'basic_crew' => 'users',
                                'basic_engineroom' => 'cogs'
                            ];
                            echo '<i class="fas fa-' . ($icons[$id] ?? 'star') . '"></i>';
                            ?>
                        </div>
                        <h4><?php echo htmlspecialchars($module['name']); ?></h4>
                        <ul class="feature-list">
                            <?php foreach ($module['features'] as $feature): ?>
                            <li><i class="fas fa-check text-success me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Add-On Modules by Category -->
    <?php 
    $categories = [
        'wheelhouse' => ['name' => 'Enhanced Wheelhouse', 'icon' => 'compass'],
        'crew' => ['name' => 'Enhanced Crew Management', 'icon' => 'users'],
        'engineroom' => ['name' => 'Enhanced Engine Room', 'icon' => 'cogs'],
        'analytics' => ['name' => 'Analytics & Reporting', 'icon' => 'chart-line'],
        'compliance' => ['name' => 'Compliance & Safety', 'icon' => 'shield-alt'],
        'mobile' => ['name' => 'Mobile & Offline', 'icon' => 'mobile-alt']
    ];
    
    foreach ($categories as $cat_id => $category):
        $cat_modules = getModulesByCategory($cat_id);
        if (empty($cat_modules)) continue;
    ?>
    <section class="container">
        <div class="category-section">
            <div class="category-header">
                <div class="category-icon">
                    <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                </div>
                <h2><?php echo $category['name']; ?> Modules</h2>
                <p class="lead">Advanced features to enhance your <?php echo strtolower($category['name']); ?> capabilities</p>
            </div>
            
            <div class="row">
                <?php foreach ($cat_modules as $id => $module): ?>
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="module-card addon position-relative">
                        <div class="module-badge badge-addon">$<?php echo number_format($module['price'], 2); ?>/month</div>
                        <div class="module-icon icon-addon">
                            <i class="fas fa-<?php echo $category['icon']; ?>"></i>
                        </div>
                        <h4><?php echo htmlspecialchars($module['name']); ?></h4>
                        <ul class="feature-list">
                            <?php foreach ($module['features'] as $feature): ?>
                            <li><i class="fas fa-plus text-primary me-2"></i><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- Call to Action -->
    <section class="container text-center my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">Ready to Get Started?</h2>
                <p class="lead mb-4">
                    Start with a 30-day free trial, then choose the Basic Package and add modules as your needs grow.
                </p>
                <a href="signup.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-rocket me-2"></i>Start Free Trial
                </a>
                <a href="login_enhanced.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: var(--dark-color); color: white; padding: 3rem 0;">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h5><i class="fas fa-ship me-2"></i>Vessel Logger</h5>
                    <p>Professional maritime logging and fleet management platform.</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <a href="index.php" class="text-light me-3">Home</a>
                    <a href="modules.php" class="text-light me-3">Modules</a>
                    <a href="login_enhanced.php" class="text-light me-3">Login</a>
                    <a href="signup.php" class="btn btn-primary btn-sm">Start Trial</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
