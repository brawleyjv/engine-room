# VPS Deployment Guide
## Professional Vessel Management SaaS Platform

This guide will help you deploy your multi-tenant vessel management platform on a VPS server for production use.

## 🏗️ Server Requirements

### Minimum Specifications
- **CPU**: 2+ cores
- **RAM**: 4GB minimum, 8GB recommended
- **Storage**: 40GB SSD minimum
- **Bandwidth**: 1TB/month
- **OS**: Ubuntu 20.04 LTS or CentOS 8

### Recommended VPS Providers
- **DigitalOcean**: $20-40/month droplets
- **Linode**: Similar pricing, excellent support
- **Vultr**: Good performance, global locations
- **AWS Lightsail**: Easy integration with other AWS services

## 🔧 Initial Server Setup

### 1. Connect to Your Server
```bash
ssh root@your-server-ip
```

### 2. Update System
```bash
# Ubuntu/Debian
apt update && apt upgrade -y

# CentOS/RHEL
yum update -y
```

### 3. Create Non-Root User
```bash
adduser vessellogger
usermod -aG sudo vessellogger
su - vessellogger
```

### 4. Configure Firewall
```bash
# Ubuntu UFW
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable

# CentOS Firewalld
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 📦 Install LAMP Stack

### 1. Install Apache
```bash
# Ubuntu
sudo apt install apache2 -y

# CentOS
sudo yum install httpd -y
sudo systemctl start httpd
sudo systemctl enable httpd
```

### 2. Install MySQL/MariaDB
```bash
# Ubuntu
sudo apt install mariadb-server mariadb-client -y

# CentOS
sudo yum install mariadb-server mariadb -y
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Secure installation
sudo mysql_secure_installation
```

### 3. Install PHP 7.4+
```bash
# Ubuntu
sudo apt install php php-mysql php-curl php-json php-mbstring php-xml php-zip -y

# CentOS
sudo yum install php php-mysqlnd php-curl php-json php-mbstring php-xml php-zip -y
```

### 4. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## 🚀 Application Deployment

### 1. Upload Application Files
```bash
# Create application directory
sudo mkdir -p /var/www/vessellogger
sudo chown vessellogger:vessellogger /var/www/vessellogger

# Upload files (use SCP, SFTP, or Git)
scp -r /path/to/your/app/* vessellogger@your-server:/var/www/vessellogger/

# Or clone from Git
cd /var/www/vessellogger
git clone https://github.com/yourusername/vessel-logger.git .
```

### 2. Set Proper Permissions
```bash
sudo chown -R www-data:www-data /var/www/vessellogger
sudo chmod -R 755 /var/www/vessellogger
sudo chmod -R 775 /var/www/vessellogger/install
sudo chmod 664 /var/www/vessellogger/config*.php
```

### 3. Configure Apache Virtual Host
```bash
sudo nano /etc/apache2/sites-available/vessellogger.conf
```

Add this configuration:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/vessellogger
    
    <Directory /var/www/vessellogger>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/vessellogger_error.log
    CustomLog ${APACHE_LOG_DIR}/vessellogger_access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite vessellogger.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

## 🔒 SSL Certificate Setup

### Using Let's Encrypt (Free)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

## 🗄️ Database Configuration

### 1. Create Master Database
```bash
sudo mysql -u root -p
```

```sql
-- Create master database for customer management
CREATE DATABASE vessellogger_master;

-- Create admin user
CREATE USER 'vl_admin'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON vessellogger_master.* TO 'vl_admin'@'localhost';

-- Grant privileges to create customer databases
GRANT CREATE ON *.* TO 'vl_admin'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Initialize Master Database
```sql
USE vessellogger_master;

-- Customer licenses table
CREATE TABLE customer_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) UNIQUE NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    admin_email VARCHAR(255) NOT NULL,
    plan ENUM('trial', 'basic', 'professional', 'enterprise') DEFAULT 'trial',
    status ENUM('active', 'suspended', 'canceled', 'expired') DEFAULT 'active',
    vessel_limit INT DEFAULT 1,
    user_limit INT DEFAULT 3,
    active_modules JSON,
    subscription_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payment logs table
CREATE TABLE payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer_licenses(customer_id)
);

-- System logs table
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🔧 Production Configuration

### 1. Update PHP Configuration
```bash
sudo nano /etc/php/7.4/apache2/php.ini
```

Important settings:
```ini
memory_limit = 256M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
date.timezone = UTC
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

### 2. Create Error Log Directory
```bash
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

### 3. Configure Log Rotation
```bash
sudo nano /etc/logrotate.d/vessellogger
```

```
/var/log/php/*.log {
    weekly
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## 🔐 Security Hardening

### 1. Hide Apache Version
```bash
sudo nano /etc/apache2/conf-available/security.conf
```

Update:
```apache
ServerTokens Prod
ServerSignature Off
```

### 2. Install Fail2Ban
```bash
sudo apt install fail2ban -y
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true

[apache-auth]
enabled = true

[apache-badbots]
enabled = true
```

### 3. Configure ModSecurity (Optional)
```bash
sudo apt install libapache2-mod-security2 -y
sudo a2enmod security2
```

## 📧 Email Configuration

### Using SendGrid (Recommended)
1. Sign up for SendGrid account
2. Get API key
3. Update your config files with SMTP settings

### Using Local Mail (Basic)
```bash
sudo apt install postfix -y
# Configure during installation
```

## 🔄 Backup Strategy

### 1. Database Backup Script
```bash
sudo nano /usr/local/bin/backup-vessel-databases.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/vessellogger"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup master database
mysqldump -u vl_admin -p vessellogger_master > $BACKUP_DIR/master_$DATE.sql

# Backup all customer databases
mysql -u vl_admin -p -e "SHOW DATABASES;" | grep "^vl_customer_" | while read dbname; do
    mysqldump -u vl_admin -p $dbname > $BACKUP_DIR/${dbname}_$DATE.sql
done

# Compress backups older than 1 day
find $BACKUP_DIR -name "*.sql" -mtime +1 -exec gzip {} \;

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete
```

Make executable and add to cron:
```bash
sudo chmod +x /usr/local/bin/backup-vessel-databases.sh
sudo crontab -e
```

Add daily backup at 2 AM:
```
0 2 * * * /usr/local/bin/backup-vessel-databases.sh
```

### 2. File Backup
```bash
# Add to daily backup script
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/vessellogger --exclude=/var/www/vessellogger/install
```

## 📊 Monitoring Setup

### 1. Install System Monitoring
```bash
# Install htop for system monitoring
sudo apt install htop iotop -y

# Install logwatch for log analysis
sudo apt install logwatch -y
```

### 2. Apache Monitoring
Enable status module:
```bash
sudo a2enmod status
```

Add to Apache config:
```apache
<Location "/server-status">
    SetHandler server-status
    Require local
</Location>
```

### 3. Database Monitoring
Add monitoring queries to check database health:
```sql
-- Check database sizes
SELECT 
    table_schema as 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema LIKE 'vl_customer_%'
GROUP BY table_schema
ORDER BY SUM(data_length + index_length) DESC;
```

## 🚀 Go Live Checklist

### Pre-Launch
- [ ] DNS pointing to server IP
- [ ] SSL certificate installed and working
- [ ] Database backups configured
- [ ] Email sending configured
- [ ] Error logging working
- [ ] Payment webhooks configured
- [ ] All security measures in place

### Launch Day
- [ ] Test customer signup process
- [ ] Test installation wizard
- [ ] Verify payment processing
- [ ] Check email notifications
- [ ] Monitor error logs
- [ ] Test mobile responsiveness

### Post-Launch
- [ ] Monitor server resources
- [ ] Check backup integrity
- [ ] Review error logs daily
- [ ] Monitor payment processing
- [ ] Customer support setup

## 📞 Maintenance

### Daily Tasks
- Check error logs
- Monitor server resources
- Verify backups completed

### Weekly Tasks
- Review system logs
- Check security updates
- Analyze user activity

### Monthly Tasks
- Security patches
- Performance optimization
- Backup testing
- Invoice reconciliation

## 🆘 Troubleshooting

### Common Issues

**Permission Errors**
```bash
sudo chown -R www-data:www-data /var/www/vessellogger
sudo chmod -R 755 /var/www/vessellogger
```

**Database Connection Issues**
```bash
# Check MySQL service
sudo systemctl status mariadb
sudo systemctl restart mariadb
```

**High Memory Usage**
```bash
# Check processes
htop
# Optimize MySQL
sudo mysql_secure_installation
```

**SSL Certificate Issues**
```bash
sudo certbot renew
sudo systemctl reload apache2
```

## 📋 Performance Optimization

### 1. Enable Caching
```bash
sudo a2enmod expires
sudo a2enmod headers
```

Add to .htaccess:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
```

### 2. Enable Compression
```bash
sudo a2enmod deflate
```

### 3. Optimize MySQL
```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

Add optimizations:
```ini
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
query_cache_type = 1
query_cache_size = 64M
```

## 🎯 Scaling Considerations

### When to Scale Up
- Server CPU > 80% consistently
- Memory usage > 85%
- Database response time > 2 seconds
- Customer growth > 100 active tenants

### Scaling Options
1. **Vertical Scaling**: Upgrade server resources
2. **Database Separation**: Dedicated database server
3. **Load Balancing**: Multiple web servers
4. **CDN**: Content delivery network for static assets

---

## 🏁 Conclusion

Your vessel management SaaS platform is now ready for production! This setup provides:

- ✅ Multi-tenant architecture with isolated customer databases
- ✅ Professional installation wizard
- ✅ Subscription and licensing management
- ✅ Payment processing integration
- ✅ Security hardening
- ✅ Automated backups
- ✅ Monitoring and maintenance procedures

Remember to regularly update your system, monitor performance, and provide excellent customer support to grow your SaaS business successfully!

For technical support with this deployment, contact: support@vessellogger.com
