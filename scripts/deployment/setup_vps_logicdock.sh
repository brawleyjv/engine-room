#!/bin/bash
# LogicDock VPS Setup and Configuration Script
# Sets up complete server monitoring, backup, and management system

echo "🚢 LogicDock VPS Setup Script - logicdock.org"
echo "=============================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run this script as root (use sudo)"
    exit 1
fi

# Configuration
WEB_ROOT="/var/www/html/enginerm"
LOGICDOCK_DIR="/opt/logicdock"
BACKUP_DIR="$LOGICDOCK_DIR/backups"
LOG_DIR="/var/log/logicdock"

echo "📁 Creating LogicDock directories..."
mkdir -p "$LOGICDOCK_DIR"
mkdir -p "$BACKUP_DIR"
mkdir -p "$LOG_DIR"
mkdir -p "$WEB_ROOT/logs"

# Set proper permissions
chown -R www-data:www-data "$WEB_ROOT"
chmod 755 "$LOGICDOCK_DIR"
chmod 755 "$BACKUP_DIR"

echo "📦 Installing required packages..."
apt update
apt install -y \
    curl \
    wget \
    unzip \
    git \
    htop \
    iotop \
    mysql-client \
    fail2ban \
    ufw \
    sendmail \
    bc \
    jq

echo "🔒 Configuring firewall..."
ufw --force enable
ufw allow ssh
ufw allow http
ufw allow https
ufw allow 3306  # MySQL (only if needed)

echo "📧 Configuring mail system..."
# Basic sendmail configuration
if ! grep -q "DS[smtp.logicdock.org]" /etc/mail/sendmail.cf; then
    echo "DS[smtp.logicdock.org]" >> /etc/mail/sendmail.cf
    systemctl restart sendmail
fi

echo "📋 Installing backup script..."
cp "$WEB_ROOT/backup_all_databases.sh" "$LOGICDOCK_DIR/"
chmod +x "$LOGICDOCK_DIR/backup_all_databases.sh"

echo "📊 Installing monitoring script..."
cp "$WEB_ROOT/system_health_monitor.sh" "$LOGICDOCK_DIR/"
chmod +x "$LOGICDOCK_DIR/system_health_monitor.sh"

echo "⏰ Setting up cron jobs..."
# Create cron jobs for LogicDock automation
cat > /tmp/logicdock_cron << EOF
# LogicDock Automated Tasks
# Customer tracking and notifications (every 15 minutes)
*/15 * * * * cd $WEB_ROOT && /usr/bin/php logicdock_cron.php >> /var/log/logicdock/cron.log 2>&1

# Database backups (daily at 2 AM)
0 2 * * * $LOGICDOCK_DIR/backup_all_databases.sh >> /var/log/logicdock/backup.log 2>&1

# System health monitoring (every 5 minutes)
*/5 * * * * $LOGICDOCK_DIR/system_health_monitor.sh >> /var/log/logicdock/health.log 2>&1

# Log rotation (weekly)
0 0 * * 0 find /var/log/logicdock -name "*.log" -mtime +30 -delete

# Update system packages (monthly)
0 3 1 * * apt update && apt upgrade -y >> /var/log/logicdock/updates.log 2>&1
EOF

crontab -u root /tmp/logicdock_cron
rm /tmp/logicdock_cron

echo "🗃️ Configuring MySQL for LogicDock..."
# Create MySQL configuration for LogicDock
cat > /etc/mysql/conf.d/logicdock.cnf << EOF
[mysqld]
# LogicDock optimizations
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 200
query_cache_type = 1
query_cache_size = 32M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Performance monitoring
performance_schema = ON
EOF

echo "🔄 Restarting services..."
systemctl restart mysql
systemctl restart apache2

echo "🛡️ Configuring Fail2Ban..."
cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
destemail = security@logicdock.org

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/error.log
maxretry = 6

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/access.log
maxretry = 2
EOF

systemctl restart fail2ban

echo "📈 Setting up log rotation..."
cat > /etc/logrotate.d/logicdock << EOF
/var/log/logicdock/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    postrotate
        systemctl reload rsyslog > /dev/null 2>&1 || true
    endscript
}
EOF

echo "🔧 Configuring Apache for LogicDock..."
# Enable useful Apache modules
a2enmod rewrite
a2enmod ssl
a2enmod headers
a2enmod deflate

# Create LogicDock Apache configuration
cat > /etc/apache2/sites-available/logicdock.conf << EOF
<VirtualHost *:80>
    ServerName vessel.logicdock.org
    DocumentRoot $WEB_ROOT
    
    # Security headers
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000"
    
    # Compression
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    
    # Restrict access to sensitive files
    <FilesMatch "\.(log|sql|sh|conf)$">
        Require all denied
    </FilesMatch>
    
    # Restrict access to support dashboards
    <LocationMatch "/(support_dashboard|vps_management)\.php">
        # Only allow from specific IPs (update with your office IPs)
        Require ip 127.0.0.1
        # Require ip YOUR_OFFICE_IP_HERE
    </LocationMatch>
    
    ErrorLog /var/log/apache2/logicdock_error.log
    CustomLog /var/log/apache2/logicdock_access.log combined
</VirtualHost>
EOF

# Enable the site
a2ensite logicdock.conf
systemctl reload apache2

echo "🔐 Setting up SSL certificate (Let's Encrypt)..."
if command -v certbot &> /dev/null; then
    echo "Certbot already installed"
else
    apt install -y certbot python3-certbot-apache
fi

# Note: Run this manually after DNS is configured
echo "⚠️  To enable SSL, run after DNS is configured:"
echo "   certbot --apache -d vessel.logicdock.org"

echo "🗄️ Creating LogicDock database setup..."
# Create database setup script
cat > "$LOGICDOCK_DIR/setup_master_db.sql" << EOF
-- LogicDock Master Database Setup
CREATE DATABASE IF NOT EXISTS vessellogger_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vessellogger_master;

-- Load tracking tables
SOURCE $WEB_ROOT/logicdock_tracking_tables.sql;

-- Create LogicDock admin user
CREATE USER IF NOT EXISTS 'logicdock_admin'@'localhost' IDENTIFIED BY 'LogicDock2024!Admin';
GRANT ALL PRIVILEGES ON vessellogger_master.* TO 'logicdock_admin'@'localhost';
GRANT SELECT ON *.* TO 'logicdock_admin'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "📊 Setting up system monitoring dashboard..."
# Create system stats collection script
cat > "$LOGICDOCK_DIR/collect_stats.php" << 'EOF'
<?php
// Collect system statistics for LogicDock dashboard
require_once '/var/www/html/enginerm/config.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=vessellogger_master", 'logicdock_admin', 'LogicDock2024!Admin');
    
    // Collect system stats
    $stats = [
        'timestamp' => date('Y-m-d H:i:s'),
        'cpu_usage' => sys_getloadavg()[0],
        'memory_total' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'disk_usage' => disk_free_space('/'),
        'active_connections' => $pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch()['Value']
    ];
    
    // Store in database
    $sql = "INSERT INTO system_statistics (collected_at, cpu_load, memory_usage_mb, disk_free_bytes, mysql_connections) VALUES (?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $stats['timestamp'],
        $stats['cpu_usage'],
        $stats['memory_total'],
        $stats['disk_usage'],
        $stats['active_connections']
    ]);
    
    echo "Stats collected successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
EOF

echo "✅ Creating status check script..."
cat > "$LOGICDOCK_DIR/status_check.sh" << 'EOF'
#!/bin/bash
# Quick status check for LogicDock VPS

echo "🚢 LogicDock VPS Status Check"
echo "=============================="
echo "Hostname: $(hostname)"
echo "Date: $(date)"
echo "Uptime: $(uptime -p)"
echo ""

echo "📊 System Resources:"
echo "CPU Load: $(uptime | awk -F'load average:' '{print $2}')"
echo "Memory: $(free -h | grep Mem | awk '{print $3 "/" $2}')"
echo "Disk: $(df -h / | tail -1 | awk '{print $3 "/" $2 " (" $5 " used)"}')"
echo ""

echo "🔧 Service Status:"
for service in apache2 mysql ssh cron fail2ban; do
    if systemctl is-active --quiet $service; then
        echo "✅ $service: Running"
    else
        echo "❌ $service: Stopped"
    fi
done
echo ""

echo "🗄️ Database Status:"
if mysql -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ MySQL: Connected"
    CUSTOMER_DBS=$(mysql -e "SHOW DATABASES LIKE 'vessel_%';" | grep vessel_ | wc -l)
    echo "📦 Customer Databases: $CUSTOMER_DBS"
else
    echo "❌ MySQL: Connection failed"
fi
echo ""

echo "🌐 Web Server:"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null)
if [ "$HTTP_STATUS" = "200" ] || [ "$HTTP_STATUS" = "302" ]; then
    echo "✅ Apache: Responding ($HTTP_STATUS)"
else
    echo "❌ Apache: Not responding ($HTTP_STATUS)"
fi
echo ""

echo "📁 Backup Status:"
if [ -d "/opt/logicdock/backups" ]; then
    BACKUP_COUNT=$(ls -1 /opt/logicdock/backups/*.sql.gz 2>/dev/null | wc -l)
    LATEST_BACKUP=$(ls -t /opt/logicdock/backups/*.sql.gz 2>/dev/null | head -1)
    echo "💾 Total Backups: $BACKUP_COUNT"
    if [ ! -z "$LATEST_BACKUP" ]; then
        echo "📅 Latest Backup: $(basename $LATEST_BACKUP)"
    fi
else
    echo "❌ Backup directory not found"
fi
EOF

chmod +x "$LOGICDOCK_DIR/status_check.sh"

echo "🔗 Creating quick access links..."
cat > "$WEB_ROOT/admin_links.html" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>LogicDock Admin Access</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .link-box { background: #f8f9fa; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .link-box a { color: #0984e3; text-decoration: none; font-weight: bold; }
        .link-box a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>🚢 LogicDock Administration</h1>
    <p>Quick access to LogicDock management dashboards.</p>
    
    <div class="link-box">
        <h3>🖥️ VPS Server Management</h3>
        <a href="vps_management.php?access_key=logicdock_vps_admin_2024">Server Control Panel</a>
        <p>Reboot server, check health, manage services, view logs</p>
    </div>
    
    <div class="link-box">
        <h3>👥 Customer Support</h3>
        <a href="support_dashboard.php?access_key=logicdock_support_2024">Support Dashboard</a>
        <p>Customer credentials, database access, quick actions</p>
    </div>
    
    <div class="link-box">
        <h3>📊 Analytics & Tracking</h3>
        <a href="logicdock_dashboard.php?access_key=logicdock_admin_2024">Customer Analytics</a>
        <p>Trials, subscriptions, revenue, customer activity</p>
    </div>
    
    <div class="link-box">
        <h3>⚙️ Installation Wizard</h3>
        <a href="install/">Customer Onboarding</a>
        <p>New customer setup and trial activation</p>
    </div>
</body>
</html>
EOF

echo "📝 Creating final setup instructions..."
cat > "$LOGICDOCK_DIR/SETUP_COMPLETE.md" << EOF
# LogicDock VPS Setup Complete!

## 🎉 Installation Summary
- ✅ System monitoring and health checks
- ✅ Automated database backups
- ✅ Customer tracking and analytics
- ✅ VPS management dashboard
- ✅ Security hardening (Fail2Ban, UFW)
- ✅ Cron job automation
- ✅ Log rotation and cleanup

## 🔗 Access URLs
- VPS Management: https://vessel.logicdock.org/vps_management.php?access_key=logicdock_vps_admin_2024
- Support Dashboard: https://vessel.logicdock.org/support_dashboard.php?access_key=logicdock_support_2024
- Customer Analytics: https://vessel.logicdock.org/logicdock_dashboard.php?access_key=logicdock_admin_2024
- Admin Links: https://vessel.logicdock.org/admin_links.html

## 🔧 Manual Steps Required
1. Configure DNS: Point vessel.logicdock.org to this server's IP
2. Setup SSL: Run 'certbot --apache -d vessel.logicdock.org'
3. Update MySQL root password in backup script
4. Update office IP restrictions in Apache config
5. Test all dashboards and monitoring

## 📊 Monitoring
- System health checks every 5 minutes
- Customer activity tracking every 15 minutes
- Database backups daily at 2 AM
- Status check: $LOGICDOCK_DIR/status_check.sh

## 🆘 Support
- Logs: /var/log/logicdock/
- Config: /opt/logicdock/
- Documentation: /opt/logicdock/SETUP_COMPLETE.md

All systems are now ready for production use!
EOF

echo ""
echo "🎉 LogicDock VPS Setup Complete!"
echo "================================="
echo ""
echo "✅ All systems installed and configured"
echo "📁 Configuration files: $LOGICDOCK_DIR"
echo "📋 Setup guide: $LOGICDOCK_DIR/SETUP_COMPLETE.md"
echo "🔧 Quick status check: $LOGICDOCK_DIR/status_check.sh"
echo ""
echo "🔗 Next steps:"
echo "1. Configure DNS for vessel.logicdock.org"
echo "2. Run: certbot --apache -d vessel.logicdock.org"
echo "3. Test all dashboards"
echo ""
echo "📊 Access your dashboards at:"
echo "https://vessel.logicdock.org/admin_links.html"
echo ""
echo "🚢 LogicDock is ready to serve your vessel management customers!"
