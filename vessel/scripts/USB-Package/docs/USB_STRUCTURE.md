# 🚢 Vessel Logger - USB Deployment Structure

## Directory Structure (as it would appear on USB drive):
```
USB Drive (E:\)
├── vessel-logger/                 # Main application folder
│   ├── app/                      # Core application files
│   │   ├── config/               # Configuration files
│   │   ├── includes/             # Shared PHP includes
│   │   ├── security/             # Security and authentication
│   │   ├── sync/                 # Synchronization system
│   │   └── views/                # User interface pages
│   ├── data/                     # SQLite database and local data
│   │   ├── vessel.db             # Main SQLite database
│   │   ├── logs/                 # Application logs
│   │   └── temp/                 # Temporary files
│   ├── xampp-portable/           # Portable XAMPP installation
│   │   ├── apache/               # Apache web server
│   │   ├── php/                  # PHP runtime
│   │   └── config/               # XAMPP configuration
│   ├── scripts/                  # Startup and utility scripts
│   │   ├── start-vessel-logger.bat
│   │   ├── stop-vessel-logger.bat
│   │   └── setup-vessel.bat
│   └── docs/                     # Documentation
├── autorun.inf                   # Windows autorun configuration
└── README.txt                    # Quick start instructions
```

## Development Structure (Current XAMPP setup):
```
C:\xampp\htdocs\enginerm\vessel\
├── app/                          # Mirrors USB structure
├── data/
├── scripts/
└── docs/
```

This structure allows us to:
1. Develop and test locally in XAMPP
2. Package easily for USB deployment
3. Maintain organization and security
4. Support easy updates and maintenance
