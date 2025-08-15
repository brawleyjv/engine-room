<?php
/**
 * LogicDock SaaS Landing Page
 * Pure marketing page for trial subscriptions - NO user/company logic
 */

// This is a pure marketing landing page
// No session logic, no company determination, no user checks
// Just show the landing page and let users start their trial
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogicDock - Professional Maritime Vessel Management SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
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
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff10"><polygon points="0,0 1000,100 1000,0"/></svg>');
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            margin-top: 2rem;
        }
        
        .btn-cta {
            padding: 15px 30px;
            font-size: 1.1rem;
            border-radius: 50px;
            margin: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-primary-cta {
            background: var(--success-color);
            color: white;
            border: none;
        }
        
        .btn-secondary-cta {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .feature-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Industries Section */
        .industries-section {
            padding: 80px 0;
            background: white;
        }
        
        .industry-item {
            text-align: center;
            padding: 2rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--light-color), #ffffff);
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .industry-item:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
        }
        
        .industry-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .industry-item:hover .industry-icon {
            color: white;
        }
        
        /* Stats Section */
        .stats-section {
            background: var(--primary-color);
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            display: block;
            color: var(--success-color);
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* Pricing Section */
        .pricing-section {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            border: 3px solid transparent;
        }
        
        .pricing-card.featured {
            border-color: var(--success-color);
            transform: scale(1.05);
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        
        .pricing-badge {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success-color);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .pricing-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .pricing-price {
            font-size: 3rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .pricing-period {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .pricing-features li {
            padding: 0.5rem 0;
            color: #555;
        }
        
        .pricing-features i {
            color: var(--success-color);
            margin-right: 0.5rem;
        }
        
        /* Footer */
        .footer {
            background: var(--dark-color);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-title {
            color: var(--secondary-color);
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 0.5rem;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: var(--secondary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .btn-cta {
                display: block;
                margin: 0.5rem 0;
            }
            
            .pricing-card.featured {
                transform: none;
            }
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in-left {
            animation: slideInLeft 0.8s ease-out;
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .slide-in-right {
            animation: slideInRight 0.8s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight: 700; color: var(--primary-color);">
                <i class="fas fa-ship me-2" style="color: var(--secondary-color);"></i>
                LogicDock
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules.php">Modules</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#industries">Industries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pricing">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3 ms-2" href="signup.php">
                            <i class="fas fa-rocket me-1"></i>Start Free Trial
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 hero-content fade-in">
                    <h1 class="hero-title">Professional Maritime Logging for Your Fleet</h1>
                    <p class="hero-subtitle">
                        Streamline engine room operations, track equipment performance, and manage your entire fleet 
                        with the most trusted vessel logging platform in the maritime industry.
                    </p>
                    <div class="cta-buttons">
                        <a href="signup.php" class="btn-cta btn-primary-cta">
                            <i class="fas fa-rocket me-2"></i>Start 30-Day Free Trial
                        </a>
                        <a href="#features" class="btn-cta btn-secondary-cta">
                            <i class="fas fa-play-circle me-2"></i>See How It Works
                        </a>
                    </div>
                    <div class="mt-4">
                        <small style="opacity: 0.8;">
                            <i class="fas fa-check-circle me-1"></i>No credit card required
                            <i class="fas fa-check-circle me-1 ms-3"></i>30-day free trial
                            <i class="fas fa-check-circle me-1 ms-3"></i>Cancel anytime
                        </small>
                    </div>
                </div>
                <div class="col-lg-4 text-center slide-in-right">
                    <i class="fas fa-ship" style="font-size: 15rem; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto fade-in">
                    <h2 class="display-4 mb-3" style="color: var(--primary-color);">Everything You Need to Manage Your Fleet</h2>
                    <p class="lead">Professional tools designed specifically for maritime operations and engine room management.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-4 mb-4 slide-in-left">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h4 class="feature-title">Engine Room Logging</h4>
                        <p>Track RPM, temperatures, pressures, fuel consumption, and maintenance schedules across all your equipment with precision.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4 fade-in">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="feature-title">Performance Analytics</h4>
                        <p>Generate detailed reports, identify trends, and optimize equipment performance with advanced analytics and visual dashboards.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4 slide-in-right">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4 class="feature-title">Offline Access</h4>
                        <p>Continue logging even without internet connection. Data syncs automatically when connectivity is restored.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4 slide-in-left">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="feature-title">Team Management</h4>
                        <p>Add crew members, assign roles, and manage access permissions with comprehensive user management tools.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4 fade-in">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="feature-title">Data Security</h4>
                        <p>Your data is isolated and secure with enterprise-grade encryption, role-based access control, and comprehensive audit logging.</p>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4 slide-in-right">
                    <div class="feature-card text-center">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="feature-title">24/7 Support</h4>
                        <p>Get help when you need it with professional support, documentation, and training resources available around the clock.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Industries Section -->
    <section id="industries" class="industries-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto fade-in">
                    <h2 class="display-4 mb-3" style="color: var(--primary-color);">Trusted by Maritime Professionals</h2>
                    <p class="lead">From towboats to fishing fleets, Vessel Logger serves diverse maritime operations worldwide.</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="industry-item">
                        <div class="industry-icon">
                            <i class="fas fa-anchor"></i>
                        </div>
                        <h5>Towboat Operations</h5>
                        <p>River and coastal towing with precision logging</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="industry-item">
                        <div class="industry-icon">
                            <i class="fas fa-fish"></i>
                        </div>
                        <h5>Commercial Fishing</h5>
                        <p>Fishing fleet management and equipment tracking</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="industry-item">
                        <div class="industry-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h5>Workboat Services</h5>
                        <p>Offshore support and marine construction</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="industry-item">
                        <div class="industry-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5>Passenger Vessels</h5>
                        <p>Ferry operations and passenger transport</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item fade-in">
                        <span class="stat-number">500+</span>
                        <div class="stat-label">Active Vessels</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item fade-in">
                        <span class="stat-number">50+</span>
                        <div class="stat-label">Maritime Companies</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item fade-in">
                        <span class="stat-number">1M+</span>
                        <div class="stat-label">Log Entries</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item fade-in">
                        <span class="stat-number">99.9%</span>
                        <div class="stat-label">Uptime</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto fade-in">
                    <h2 class="display-4 mb-3" style="color: var(--primary-color);">Simple, Transparent Pricing</h2>
                    <p class="lead">Per-vessel pricing that scales with your fleet. Volume discounts available for 5+ vessels.</p>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card slide-in-left">
                        <h3 class="pricing-title">Trial</h3>
                        <div class="pricing-price">Free</div>
                        <div class="pricing-period">30-day trial</div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> 1 vessel only</li>
                            <li><i class="fas fa-check"></i> Basic logging features</li>
                            <li><i class="fas fa-check"></i> Up to 5 users</li>
                            <li><i class="fas fa-check"></i> Email support</li>
                        </ul>
                        <a href="signup.php" class="btn btn-outline-primary btn-lg w-100">Start Free Trial</a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card featured fade-in">
                        <div class="pricing-badge">Most Popular</div>
                        <h3 class="pricing-title">Basic Package</h3>
                        <div class="pricing-price">$49</div>
                        <div class="pricing-period">per vessel/month</div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-check"></i> Basic Wheelhouse (position & activities)</li>
                            <li><i class="fas fa-check"></i> Basic Crew (TWIC, positions, dates)</li>
                            <li><i class="fas fa-check"></i> Basic Engine Room (RPM, temps, pressures)</li>
                            <li><i class="fas fa-check"></i> Unlimited users per vessel</li>
                            <li><i class="fas fa-check"></i> Priority support</li>
                        </ul>
                        <small class="text-muted mb-2 d-block">Fleet discounts available for 5+ vessels</small>
                        <a href="signup.php" class="btn btn-primary btn-lg w-100">Get Started</a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="pricing-card slide-in-right">
                        <h3 class="pricing-title">Add-On Modules</h3>
                        <div class="pricing-price">$24.95</div>
                        <div class="pricing-period">per module/vessel/month</div>
                        <ul class="pricing-features">
                            <li><i class="fas fa-plus"></i> Analytics & Graphs Module</li>
                            <li><i class="fas fa-plus"></i> Enhanced Wheelhouse Module</li>
                            <li><i class="fas fa-plus"></i> Enhanced Crew Management</li>
                            <li><i class="fas fa-plus"></i> Enhanced Engine Room Module</li>
                            <li><i class="fas fa-plus"></i> Fuel Management Module</li>
                        </ul>
                        <small class="text-muted mb-2 d-block">Fleet discounts available for 5+ vessels</small>
                        <a href="signup.php" class="btn btn-secondary btn-lg w-100">Start with Basic</a>
                    </div>
                </div>
            </div>
            
            <!-- Modular Pricing Info -->
            <div class="row mt-5">
                <div class="col-lg-10 mx-auto text-center">
                    <div class="alert alert-info" style="background: rgba(52, 152, 219, 0.1); border: 2px solid var(--secondary-color); border-radius: 15px;">
                        <h5 class="mb-3"><i class="fas fa-puzzle-piece me-2"></i>Build Your Perfect Solution</h5>
                        <p class="mb-2">
                            Start with our <strong>Basic Package ($49/vessel/month)</strong> which includes core wheelhouse, crew, and engine room logging.
                            Then add specialized modules for <strong>$24.95 per module per vessel per month</strong> to enhance your capabilities.
                        </p>
                        <p class="mb-3">
                            <strong>Fleet Discounts:</strong> Save money with volume pricing for fleets of 5+ vessels. 
                            <a href="mailto:sales@vessellogger.com" class="text-decoration-none">Contact us for fleet pricing</a>.
                        </p>
                        <a href="modules.php" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i>View All Modules & Features
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4 class="footer-title">
                        <i class="fas fa-ship me-2"></i>LogicDock
                    </h4>
                    <p class="mb-3">Professional maritime logging and fleet management platform trusted by maritime professionals worldwide.</p>
                    <div>
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Product</h5>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#">API</a></li>
                        <li><a href="#">Security</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Company</h5>
                    <ul class="footer-links">
                        <li><a href="#">About</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Support</h5>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Training</a></li>
                        <li><a href="#">Status</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5 class="footer-title">Legal</h5>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">GDPR</a></li>
                        <li><a href="#">Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <hr style="border-color: #555; margin: 2rem 0;">
            
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 LogicDock. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="signup.php" class="btn btn-primary btn-sm">Start Free Trial</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate statistics on scroll
        const animateStats = () => {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const rect = stat.getBoundingClientRect();
                if (rect.top >= 0 && rect.bottom <= window.innerHeight) {
                    const finalText = stat.textContent;
                    if (!stat.dataset.animated) {
                        stat.dataset.animated = 'true';
                        animateNumber(stat, finalText);
                    }
                }
            });
        };

        const animateNumber = (element, finalText) => {
            const isPercentage = finalText.includes('%');
            const isPlus = finalText.includes('+');
            const number = parseInt(finalText.replace(/[^\d]/g, ''));
            const duration = 2000;
            const increment = number / (duration / 16);
            let current = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= number) {
                    element.textContent = finalText;
                    clearInterval(timer);
                } else {
                    let displayText = Math.floor(current).toString();
                    if (isPercentage) displayText += '%';
                    if (isPlus) displayText += '+';
                    element.textContent = displayText;
                }
            }, 16);
        };

        // Listen for scroll events
        window.addEventListener('scroll', animateStats);
        
        // Add hover effects to cards
        document.querySelectorAll('.feature-card, .pricing-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
