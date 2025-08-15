# 🚀 Production Server Database Setup Guide

## The Problem
Your signup is failing with "domain already taken" because the license database doesn't exist on the production server (logicdock.org), so the domain availability check is failing.

## Quick Fix Steps

### 1. Test Current Status
Upload `test_production_db.php` to your server and visit:
```
https://logicdock.org/test_production_db.php
```

This will show you exactly what's missing.

### 2. SSH Into Your Server
```bash
ssh your_username@logicdock.org
```

### 3. Create the License Database
```sql
mysql -u root -p
CREATE DATABASE vessel_license_master;
CREATE USER 'license_admin'@'localhost' IDENTIFIED BY 'LogicDock_License_2024!';
GRANT ALL PRIVILEGES ON vessel_license_master.* TO 'license_admin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Upload and Import Schema
Upload `database_sql/license_database_schema.sql` to your server, then:
```bash
# Import schema (upload database_sql/license_database_schema.sql first)
mysql -u license_admin -pLogicDock_License_2024! vessel_license_master < database_sql/license_database_schema.sql
```

### 5. Test Again
Visit `https://logicdock.org/test_production_db.php` to verify everything is working.

### 6. Try Signup
Go to `https://logicdock.org/signup_test.php` and test company registration.

## Files to Upload
1. `test_production_db.php` - Database connection tester
2. `database_sql/license_database_schema.sql` - Database schema
3. `config_saas.php` - Updated with production detection

## Production Configuration
The system will automatically detect `logicdock.org` and use:
- Database: `vessel_license_master`
- Username: `license_admin` 
- Password: `LogicDock_License_2024!`

## Alternative Quick Test
If you want to test immediately, you can temporarily modify the `checkDomainAvailability` function in `signup_test.php` to always return `true` for testing:

```php
function checkDomainAvailability($domain) {
    // Temporary bypass for testing
    return true;
    
    // Original code below...
}
```

But this should only be temporary - you need the real database for production use.

## What's Happening
1. User enters company name
2. System generates domain from company name
3. `checkDomainAvailability()` tries to connect to license database
4. Connection fails (database doesn't exist)
5. Function returns false (domain not available)
6. User sees "domain already taken" error

Once you set up the database, this will work correctly! 🎯
