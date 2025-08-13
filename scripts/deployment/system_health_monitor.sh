#!/bin/bash
# LogicDock System Health Monitor
# Monitors server health and sends alerts for critical issues

# Configuration
ALERT_EMAIL="alerts@logicdock.org"
LOG_FILE="/var/log/logicdock_monitor.log"
WEBHOOK_URL="https://api.logicdock.org/system-alerts"

# Thresholds
CPU_THRESHOLD=80
MEMORY_THRESHOLD=85
DISK_THRESHOLD=90
LOAD_THRESHOLD=3.0

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local severity=$1
    local message=$2
    local metric=$3
    local value=$4
    
    log_message "[$severity] $message"
    
    # Send email alert
    echo "Subject: LogicDock VPS Alert - $severity
    
Server: $(hostname)
Time: $(date)
Alert: $message
Metric: $metric
Current Value: $value

Please check the VPS management dashboard for details.
https://vessel.logicdock.org/vps_management.php

Automated monitoring system" | sendmail "$ALERT_EMAIL"

    # Send webhook notification
    curl -s -X POST "$WEBHOOK_URL" \
        -H "Content-Type: application/json" \
        -d "{
            \"severity\": \"$severity\",
            \"message\": \"$message\",
            \"metric\": \"$metric\",
            \"value\": \"$value\",
            \"hostname\": \"$(hostname)\",
            \"timestamp\": \"$(date -Iseconds)\"
        }" > /dev/null
}

log_message "=== LogicDock System Health Check Started ==="

# Check CPU usage
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
CPU_USAGE_INT=${CPU_USAGE%.*}
if [ "$CPU_USAGE_INT" -gt "$CPU_THRESHOLD" ]; then
    send_alert "CRITICAL" "High CPU usage detected" "CPU Usage" "${CPU_USAGE}%"
fi

# Check memory usage
MEMORY_INFO=$(free | grep Mem)
TOTAL_MEM=$(echo $MEMORY_INFO | awk '{print $2}')
USED_MEM=$(echo $MEMORY_INFO | awk '{print $3}')
MEMORY_PERCENT=$((USED_MEM * 100 / TOTAL_MEM))
if [ "$MEMORY_PERCENT" -gt "$MEMORY_THRESHOLD" ]; then
    send_alert "CRITICAL" "High memory usage detected" "Memory Usage" "${MEMORY_PERCENT}%"
fi

# Check disk usage
DISK_USAGE=$(df / | tail -1 | awk '{print $5}' | cut -d'%' -f1)
if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    send_alert "CRITICAL" "High disk usage detected" "Disk Usage" "${DISK_USAGE}%"
fi

# Check load average
LOAD_AVG=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | cut -d',' -f1)
LOAD_AVG_COMPARE=$(echo "$LOAD_AVG > $LOAD_THRESHOLD" | bc -l)
if [ "$LOAD_AVG_COMPARE" -eq 1 ]; then
    send_alert "WARNING" "High load average detected" "Load Average" "$LOAD_AVG"
fi

# Check critical services
SERVICES=("apache2" "mysql" "ssh" "cron")
for SERVICE in "${SERVICES[@]}"; do
    if ! systemctl is-active --quiet "$SERVICE"; then
        send_alert "CRITICAL" "Service $SERVICE is not running" "Service Status" "DOWN"
        
        # Attempt to restart the service
        log_message "Attempting to restart $SERVICE"
        if systemctl restart "$SERVICE"; then
            log_message "Successfully restarted $SERVICE"
            send_alert "INFO" "Service $SERVICE automatically restarted" "Service Recovery" "UP"
        else
            log_message "Failed to restart $SERVICE"
            send_alert "CRITICAL" "Failed to restart service $SERVICE" "Service Recovery" "FAILED"
        fi
    fi
done

# Check MySQL connection
if ! mysql -e "SELECT 1;" > /dev/null 2>&1; then
    send_alert "CRITICAL" "MySQL database is not responding" "Database Connection" "FAILED"
fi

# Check Apache response
if ! curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|301\|302"; then
    send_alert "CRITICAL" "Apache web server is not responding" "Web Server Response" "FAILED"
fi

# Check available disk space for backups
BACKUP_DISK_USAGE=$(df /opt/logicdock/backups 2>/dev/null | tail -1 | awk '{print $5}' | cut -d'%' -f1)
if [ ! -z "$BACKUP_DISK_USAGE" ] && [ "$BACKUP_DISK_USAGE" -gt 95 ]; then
    send_alert "WARNING" "Backup directory is almost full" "Backup Disk Usage" "${BACKUP_DISK_USAGE}%"
fi

# Check for failed login attempts (potential security issue)
FAILED_LOGINS=$(grep "Failed password" /var/log/auth.log | grep "$(date '+%b %d')" | wc -l)
if [ "$FAILED_LOGINS" -gt 50 ]; then
    send_alert "WARNING" "High number of failed login attempts detected" "Failed Logins" "$FAILED_LOGINS"
fi

# Check LogicDock cron job
LAST_CRON_RUN=$(grep "LogicDock Cron Job Completed" /var/www/html/enginerm/logs/logicdock_cron.log | tail -1 | cut -d']' -f1 | cut -d'[' -f2)
if [ ! -z "$LAST_CRON_RUN" ]; then
    LAST_RUN_TIMESTAMP=$(date -d "$LAST_CRON_RUN" +%s)
    CURRENT_TIMESTAMP=$(date +%s)
    TIME_DIFF=$((CURRENT_TIMESTAMP - LAST_RUN_TIMESTAMP))
    
    # Alert if cron hasn't run in over 30 minutes
    if [ "$TIME_DIFF" -gt 1800 ]; then
        send_alert "WARNING" "LogicDock cron job hasn't run recently" "Cron Job Status" "Last run: $LAST_CRON_RUN"
    fi
fi

# Check customer database connections
CUSTOMER_DB_COUNT=$(mysql -e "SHOW DATABASES LIKE 'vessel_%';" | grep vessel_ | wc -l)
if [ "$CUSTOMER_DB_COUNT" -eq 0 ]; then
    send_alert "WARNING" "No customer databases found" "Customer Databases" "0"
fi

# Log completion
log_message "Health check completed - CPU: ${CPU_USAGE}%, Memory: ${MEMORY_PERCENT}%, Disk: ${DISK_USAGE}%, Load: ${LOAD_AVG}"
log_message "=== LogicDock System Health Check Completed ==="
