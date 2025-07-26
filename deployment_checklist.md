# Vessel Data Logger - IONOS Deployment Guide

## 🗄️ Database Setup

### Step 1: Create Database on IONOS
1. Log into your IONOS hosting control panel
2. Navigate to "Databases" → "MySQL Databases"
3. Create a new database (e.g., `vessel_logger`)
4. Create a database user with full privileges
5. Note down: database name, username, password, and host

### Step 2: Export Local Database
```bash
# From your local XAMPP MySQL
mysqldump -u root -p vessel_logger > vessel_logger_export.sql
```

### Step 3: Import to IONOS
- Use phpMyAdmin or MySQL command line on IONOS
- Import the `vessel_logger_export.sql` file

## ⚙️ Configuration Changes

### Update config.php for Production
```php
<?php
// Production database configuration
$servername = "your-ionos-db-host";  // Usually something like db5000123456.db.1and1.com
$username = "your-db-username";
$password = "your-db-password";
$dbname = "your-db-name";

// Create connection with error handling
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

## 📁 File Upload

### Files to Upload via FTP/SFTP:
- All .php files
- style.css
- Any .sql files (for reference)
- README.md (optional)

### Files NOT to upload:
- Local database files
- XAMPP-specific configurations
- .git folder (if deploying manually)

## 🔒 Security Considerations

### 1. Update Passwords
- Change default admin password immediately
- Use strong passwords for database users

### 2. SSL Certificate
- Enable HTTPS through IONOS control panel
- Update any absolute URLs to use https://

### 3. File Permissions
- Ensure PHP files have proper permissions (usually 644)
- Directories should be 755

## 🌐 Domain Setup

### 1. Point Domain to IONOS
- Update DNS settings if using external domain
- Configure subdomain if desired (e.g., vessels.yourdomain.com)

### 2. Directory Structure
```
public_html/
├── vessels/          # Your app folder
│   ├── index.php
│   ├── config.php
│   ├── add_log.php
│   └── ... (all other files)
```

## ✅ Post-Deployment Testing

### 1. Database Connection
- Visit yoursite.com/vessels/check_schema.php
- Ensure all tables exist and are accessible

### 2. User System
- Test login/logout functionality
- Verify password reset emails work
- Check admin user management

### 3. Core Features
- Test data entry for all equipment types
- Verify vessel switching works
- Check graph generation
- Test multi-user access

## 📧 Email Configuration

### For Password Reset Emails
Update any email settings in your PHP configuration or use IONOS SMTP:

```php
// In forgot_password.php and reset_password.php
// Configure SMTP settings for IONOS
ini_set('SMTP', 'smtp.ionos.com');
ini_set('smtp_port', '587');
```

## 🔧 Performance Optimization

### 1. PHP Settings
- Increase memory_limit if needed
- Set appropriate max_execution_time
- Configure upload_max_filesize

### 2. Database Optimization
- Add indexes to frequently queried columns
- Regular database maintenance

## 🆘 Troubleshooting

### Common Issues:
1. **Database connection errors** - Check credentials and host
2. **File permission errors** - Verify file permissions
3. **Email not working** - Configure SMTP settings
4. **Session issues** - Check PHP session configuration

### Debug Mode
Temporarily enable error reporting:
```php
// Add to top of config.php for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## 📋 Go-Live Checklist

- [ ] Database created and imported
- [ ] config.php updated with production credentials
- [ ] All files uploaded via FTP
- [ ] SSL certificate enabled
- [ ] Domain/subdomain configured
- [ ] Admin user created and tested
- [ ] All core features tested
- [ ] Email functionality verified
- [ ] Security review completed
- [ ] Backup strategy in place

## 🎯 Final Steps

1. **Remove debug settings** - Disable error reporting
2. **Create backup schedule** - Regular database backups
3. **Monitor performance** - Check server resources
4. **User training** - Provide access to team members
5. **Documentation** - Share user guides with crew

## 📞 IONOS Support Resources

- IONOS Help Center: help.ionos.com
- Database documentation: Usually in hosting control panel
- FTP/SFTP connection details: In hosting dashboard
