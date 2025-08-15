# Vessel Logger - USB Deployment Guide

## Overview
The Vessel Logger is a complete offline-capable logging system designed for deployment on USB drives. It provides a secure, portable solution for vessel logging that works independently of internet connectivity while offering synchronization capabilities when online.

## System Requirements

### Minimum Requirements
- **USB Drive**: 8GB minimum (16GB+ recommended)
- **Operating System**: Windows 10/11 (64-bit)
- **RAM**: 4GB minimum
- **Processor**: Intel Core i3 or equivalent
- **Available Ports**: 1 USB port for the logger drive

### Recommended Specifications
- **USB Drive**: 32GB+ high-speed USB 3.0
- **RAM**: 8GB or more
- **Processor**: Intel Core i5 or better
- **Network**: Ethernet or Wi-Fi for synchronization

## USB Package Structure

```
📁 Vessel-Logger-USB/
├── 📁 app/                          # Main vessel application
│   ├── 📁 config/                   # Configuration files
│   ├── 📁 includes/                 # Core PHP includes
│   ├── 📁 security/                 # Authentication & security
│   ├── 📁 sync/                     # Synchronization engine
│   ├── 📁 views/                    # User interface files
│   └── index.php                    # Application entry point
├── 📁 data/                         # Local data storage
│   ├── 📁 database/                 # SQLite database files
│   ├── 📁 logs/                     # System logs
│   ├── 📁 backups/                  # Automatic backups
│   └── 📁 temp/                     # Temporary files
├── 📁 xampp-portable/               # Portable web server
│   ├── 📁 apache/                   # Apache web server
│   ├── 📁 php/                      # PHP runtime
│   └── 📁 scripts/                  # Server scripts
├── 📁 scripts/                      # Utility scripts
│   ├── start-vessel-logger.bat      # Main startup script
│   ├── stop-vessel-logger.bat       # Shutdown script
│   ├── backup-data.bat              # Manual backup
│   └── reset-system.bat             # Factory reset
├── 📁 docs/                         # Documentation
│   ├── user-manual.pdf              # User manual
│   ├── quick-start.pdf              # Quick start guide
│   └── troubleshooting.pdf          # Troubleshooting guide
├── README.txt                       # Basic instructions
└── Vessel-Logger.exe                # Windows executable launcher
```

## Installation Instructions

### 1. USB Drive Preparation
1. **Format USB Drive**: Format as NTFS or exFAT for large file support
2. **Label Drive**: Rename to "VESSEL-LOGGER" for easy identification
3. **Extract Package**: Extract all vessel logger files to USB root

### 2. Initial Setup
1. **Insert USB Drive**: Insert into vessel computer
2. **Run Launcher**: Double-click `Vessel-Logger.exe` or `start-vessel-logger.bat`
3. **Wait for Startup**: Allow 30-60 seconds for server initialization
4. **Open Browser**: System will automatically open http://localhost:8080/

### 3. First-Time Configuration
The setup wizard will guide you through:

#### Step 1: Vessel Information
- **Vessel Name**: Official name of the vessel
- **IMO Number**: 7-digit IMO number (optional)
- **Company Name**: Shipping company name
- **Company Code**: Short company identifier

#### Step 2: Administrator Account
- **Username**: Primary admin username (minimum 3 characters)
- **Password**: Secure password (minimum 8 characters)
- **Full Name**: Administrator's full name
- **Email**: Contact email (optional)

#### Step 3: Server Configuration
- **Sync Enabled**: Enable/disable automatic synchronization
- **Server URL**: Main server API endpoint (if syncing)
- **Vessel Key**: Unique authentication key (provided by company)
- **Sync Interval**: How often to sync when online (5-1440 minutes)

## Daily Operations

### Starting the System
1. **Insert USB Drive**: Plug into vessel computer
2. **Launch Application**: Double-click `Vessel-Logger.exe`
3. **Wait for Ready**: Look for "Vessel Logger is now running" message
4. **Access Interface**: Browser opens automatically to dashboard

### Logging Operations
1. **Add New Log**: Click "Add New Log" on dashboard
2. **Fill Details**: Enter engine data, readings, observations
3. **Save Entry**: Data is automatically saved to local database
4. **View Logs**: Access all logs through "View Logs" section

### Synchronization
- **Automatic**: Syncs automatically when online (if configured)
- **Manual**: Click "Sync Now" button on dashboard
- **Status**: Monitor sync status in header bar
- **Offline**: All operations work normally without internet

### Shutting Down
1. **Close Browser**: Close the vessel logger browser window
2. **Stop Server**: Press any key in the command window
3. **Wait for Shutdown**: Allow system to stop gracefully
4. **Eject USB**: Safely remove USB drive when "safely remove" appears

## Security Features

### Authentication
- **User Accounts**: Multi-user support with role-based access
- **Session Management**: Secure session handling with timeouts
- **Password Security**: Hashed passwords with strong encryption
- **CSRF Protection**: Cross-site request forgery protection

### Data Protection
- **Local Storage**: All data stored locally in encrypted SQLite database
- **Automatic Backups**: Daily automatic backups of critical data
- **Access Control**: File-level permissions prevent unauthorized access
- **Audit Trail**: Complete logging of all user activities

### Network Security
- **HTTPS Ready**: SSL/TLS encryption for network communications
- **API Authentication**: Secure API keys for server synchronization
- **Rate Limiting**: Protection against brute force attacks
- **Input Validation**: Comprehensive input sanitization

## Maintenance

### Regular Maintenance (Weekly)
- **Check Storage**: Monitor available disk space
- **Review Logs**: Check system logs for any issues
- **Test Sync**: Verify synchronization is working properly
- **Backup Verification**: Confirm automatic backups are running

### Monthly Maintenance
- **Full Backup**: Create complete backup using backup script
- **Performance Check**: Monitor system performance and responsiveness
- **Update Check**: Check for software updates (when online)
- **Security Review**: Review user accounts and access logs

### Troubleshooting

#### Common Issues

**System Won't Start**
- Check USB drive is properly inserted
- Verify USB drive has sufficient free space (>1GB)
- Try different USB port
- Run as Administrator if needed

**Cannot Access Dashboard**
- Wait longer for server startup (up to 2 minutes)
- Check Windows Firewall isn't blocking port 8080
- Try manually opening http://localhost:8080/
- Restart the system using startup script

**Sync Not Working**
- Verify internet connection is active
- Check server URL and vessel key configuration
- Review sync logs in system status
- Contact IT administrator for server issues

**Performance Issues**
- Check available USB drive space
- Close other applications to free RAM
- Use faster USB 3.0 drive
- Consider using internal storage for better performance

#### Error Codes
- **DB001**: Database connection error - restart system
- **AUTH002**: Authentication failure - check credentials
- **SYNC003**: Synchronization error - check network/config
- **DISK004**: Low disk space - backup and purge old data

## Data Management

### Backup Strategy
- **Automatic**: Daily backups of database and configuration
- **Manual**: On-demand backups using backup script
- **Retention**: Keeps 30 days of automatic backups
- **Verification**: Automatic backup integrity checking

### Data Purging
- **Automatic**: Configurable retention periods
- **Post-Sync**: Option to purge data after successful sync
- **Manual**: Selective purging through admin interface
- **Archives**: Long-term archival options

### Export Options
- **CSV Export**: Standard format for external analysis
- **PDF Reports**: Formatted reports for official use
- **Database Export**: Full SQLite database export
- **API Export**: Programmatic data access

## Updates and Upgrades

### Update Process
1. **Backup Data**: Always backup before updating
2. **Download Update**: Get latest version from company server
3. **Stop System**: Shut down vessel logger completely
4. **Apply Update**: Replace application files (preserve data folder)
5. **Restart**: Launch updated system
6. **Verify**: Confirm all functions work properly

### Version Management
- **Current Version**: Displayed in dashboard footer
- **Update Notifications**: Alerts when updates available
- **Changelog**: Documentation of changes and improvements
- **Rollback**: Ability to revert to previous version if needed

## Support and Contact

### Technical Support
- **Internal IT**: Contact vessel IT administrator first
- **Company Support**: Use company help desk for application issues
- **Documentation**: Refer to user manual and troubleshooting guide
- **Emergency**: Emergency contact information in user manual

### Training Resources
- **User Manual**: Complete operation guide
- **Video Tutorials**: Step-by-step video guides
- **Quick Reference**: Laminated quick reference cards
- **Training Sessions**: Regular crew training sessions

---

**Important**: This system contains sensitive vessel operational data. Always follow company data security policies and ensure the USB drive is stored securely when not in use.
