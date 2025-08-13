# Vessel Management System - Installation Guide

## 🚢 Complete SaaS Installation System

This WordPress-style installation system creates isolated, customer-specific vessel management platforms with built-in licensing and subscription management.

## 📁 Installation Files

```
install/
├── index.php           # Main installation wizard
├── step1_welcome.php   # Requirements check & welcome
├── step2_company.php   # Company information
├── step3_admin.php     # Admin user setup
├── step4_vessel.php    # Primary vessel configuration
├── step5_database.php  # Database creation & setup
├── step6_modules.php   # Module selection & pricing
├── step7_complete.php  # Installation completion
└── cleanup.php         # Session cleanup
```

## 🎯 Installation Process

### Customer Experience:
1. **Navigate to:** `yourserver.com/install/`
2. **Follow 7-step wizard** (just like WordPress)
3. **Automatic setup:** Database, admin user, vessel, licensing
4. **30-day trial** automatically activated

### What Happens Automatically:
- ✅ **Unique database creation** per customer
- ✅ **Database user creation** with restricted permissions
- ✅ **Schema installation** (all tables and structure)
- ✅ **Admin user creation** with secure password hashing
- ✅ **Primary vessel setup** with engine configuration
- ✅ **License activation** with trial period
- ✅ **Module configuration** based on selections
- ✅ **Config file generation** with database credentials

## 🔐 Security Features

### Database Isolation:
- Each customer gets unique database: `vessel_cust_12345678`
- Dedicated database user with limited permissions
- No cross-customer data access possible

### License Management:
- Trial period enforcement (30 days)
- Module access control
- Vessel limit enforcement
- Subscription status tracking

## 💰 Pricing Structure

### Base Plan: $99/month
- Engine Room (Basic)
- Wheelhouse (Basic)
- 1 Vessel included
- 30-day free trial

### Add-on Modules:
- Engine Extended: +$25/month
- Wheelhouse Extended: +$35/month
- Deck Operations: +$45/month
- Fuel Management: +$30/month
- Maintenance Pro: +$40/month
- Fishing Operations: +$50/month (fishing vessels only)

## 🚀 Usage

### For New Customers:
1. Point them to: `yourserver.com/install/`
2. They complete the 7-step wizard
3. System automatically creates their isolated platform
4. They can start using immediately

### For Support:
- Each customer has unique Customer ID
- Database isolation ensures security
- Licensing system tracks all permissions
- Built-in trial and subscription management

## 🔧 Server Requirements

- PHP 7.4+ with mysqli and cURL
- MySQL/MariaDB 5.7+
- Apache with mod_rewrite
- SSL certificate (recommended)

## 📊 Customer Database Structure

Each customer gets complete schema including:
- Vessels and engine configurations
- Users and permissions
- Engine room logging tables
- Wheelhouse navigation logs
- Licensing and module tables
- Subscription tracking

## 🎛️ Admin Features

### License Management:
```php
$license = new LicenseManager($conn, $customer_id);
$license->isModuleEnabled('fuel_management');
$license->canAddVessel();
$license->hasActiveSubscription();
```

### Automatic Features:
- Trial expiration warnings
- Module access enforcement
- Vessel limit validation
- Subscription status updates

## 🔄 Integration Ready

- **Stripe/PayPal:** Ready for payment integration
- **API:** Built for boat-to-server sync
- **Multi-tenant:** Scales to unlimited customers
- **Modular:** Easy to add new features

This installation system provides enterprise-grade SaaS functionality with the simplicity of WordPress installation!
