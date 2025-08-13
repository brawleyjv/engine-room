#!/bin/bash
# LogicDock Automated Database Backup Script
# Creates compressed backups of all customer databases

# Configuration
BACKUP_DIR="/opt/logicdock/backups"
DATE=$(date +%Y%m%d_%H%M%S)
MYSQL_USER="root"
MYSQL_PASS=""  # Set your MySQL root password
RETENTION_DAYS=30
LOG_FILE="/var/log/logicdock_backup.log"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

log_message "=== LogicDock Database Backup Started ==="

# Get list of customer databases
DATABASES=$(mysql -u"$MYSQL_USER" -p"$MYSQL_PASS" -e "SHOW DATABASES LIKE 'vessel_%';" | grep vessel_)

if [ -z "$DATABASES" ]; then
    log_message "No customer databases found to backup"
    exit 0
fi

# Backup each customer database
for DB in $DATABASES; do
    log_message "Backing up database: $DB"
    
    BACKUP_FILE="$BACKUP_DIR/${DB}_${DATE}.sql.gz"
    
    # Create compressed backup
    mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB" | gzip > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        log_message "✅ Successfully backed up $DB ($BACKUP_SIZE)"
    else
        log_message "❌ Failed to backup $DB"
    fi
done

# Backup master database
log_message "Backing up master database: vessellogger_master"
MASTER_BACKUP="$BACKUP_DIR/vessellogger_master_${DATE}.sql.gz"
mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASS" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases vessellogger_master | gzip > "$MASTER_BACKUP"

if [ $? -eq 0 ]; then
    MASTER_SIZE=$(du -h "$MASTER_BACKUP" | cut -f1)
    log_message "✅ Successfully backed up vessellogger_master ($MASTER_SIZE)"
else
    log_message "❌ Failed to backup vessellogger_master"
fi

# Clean up old backups (older than retention period)
log_message "Cleaning up backups older than $RETENTION_DAYS days"
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
DELETED_COUNT=$(find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS | wc -l)
log_message "Deleted $DELETED_COUNT old backup files"

# Generate backup summary
TOTAL_BACKUPS=$(ls -1 "$BACKUP_DIR"/*${DATE}.sql.gz 2>/dev/null | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)

log_message "=== Backup Summary ==="
log_message "Total backups created: $TOTAL_BACKUPS"
log_message "Total backup directory size: $TOTAL_SIZE"
log_message "Backup location: $BACKUP_DIR"
log_message "=== LogicDock Database Backup Completed ==="

# Optional: Send notification to LogicDock API
# curl -X POST https://api.logicdock.org/backup-notifications \
#      -H "Content-Type: application/json" \
#      -d "{\"status\":\"completed\",\"backups\":$TOTAL_BACKUPS,\"size\":\"$TOTAL_SIZE\",\"date\":\"$DATE\"}"

echo "Backup completed. Check $LOG_FILE for details."
