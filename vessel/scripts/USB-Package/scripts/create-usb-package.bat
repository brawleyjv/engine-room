@echo off
REM Vessel Logger - USB Package Creator
REM This script creates a complete USB deployment package

title Vessel Logger - Package Creator

echo.
echo ========================================
echo    VESSEL LOGGER - PACKAGE CREATOR
echo ========================================
echo.

set PACKAGE_ROOT=%~dp0USB-Package
set SOURCE_ROOT=%~dp0..\

echo Creating USB package in: %PACKAGE_ROOT%
echo Source directory: %SOURCE_ROOT%
echo.

REM Create main package directory
if exist "%PACKAGE_ROOT%" (
    echo Removing existing package directory...
    rmdir /s /q "%PACKAGE_ROOT%"
)

echo [1/8] Creating directory structure...
mkdir "%PACKAGE_ROOT%"
mkdir "%PACKAGE_ROOT%\app"
mkdir "%PACKAGE_ROOT%\data"
mkdir "%PACKAGE_ROOT%\data\database"
mkdir "%PACKAGE_ROOT%\data\logs"
mkdir "%PACKAGE_ROOT%\data\backups"
mkdir "%PACKAGE_ROOT%\data\temp"
mkdir "%PACKAGE_ROOT%\xampp-portable"
mkdir "%PACKAGE_ROOT%\tools"
mkdir "%PACKAGE_ROOT%\scripts"
mkdir "%PACKAGE_ROOT%\docs"

echo [2/8] Copying vessel application...
xcopy "%SOURCE_ROOT%app" "%PACKAGE_ROOT%\app" /E /I /Y
copy "%SOURCE_ROOT%index.php" "%PACKAGE_ROOT%\app\" /Y
copy "%SOURCE_ROOT%setup.php" "%PACKAGE_ROOT%\app\" /Y
copy "%SOURCE_ROOT%login.php" "%PACKAGE_ROOT%\app\" /Y
copy "%SOURCE_ROOT%dashboard.php" "%PACKAGE_ROOT%\app\" /Y

echo [3/8] Copying scripts and configuration...
copy "%SOURCE_ROOT%scripts\*" "%PACKAGE_ROOT%\scripts\" /Y

echo [4/8] Copying documentation...
copy "%SOURCE_ROOT%*.md" "%PACKAGE_ROOT%\docs\" /Y

echo [5/8] Creating README and launcher files...

REM Create README.txt
echo Creating README.txt...
(
echo VESSEL LOGGER - USB DEPLOYMENT PACKAGE
echo =======================================
echo.
echo QUICK START:
echo 1. Insert this USB drive into vessel computer
echo 2. Double-click 'start-vessel-logger.bat' 
echo 3. Wait for browser to open automatically
echo 4. Follow setup wizard on first use
echo.
echo REQUIREMENTS:
echo - Windows 10/11 ^(64-bit^)
echo - 4GB RAM minimum
echo - Administrator privileges may be required
echo.
echo SUPPORT:
echo - User Manual: docs\USB_DEPLOYMENT_GUIDE.md  
echo - Troubleshooting: See documentation folder
echo - Emergency: Contact vessel IT administrator
echo.
echo IMPORTANT:
echo - Do not remove USB while system is running
echo - Always use 'stop' before ejecting USB drive
echo - Keep USB drive secure when not in use
echo.
echo Version: 1.0.0
echo Build Date: %DATE%
) > "%PACKAGE_ROOT%\README.txt"

REM Create simplified launcher batch file
echo Creating start-vessel-logger.bat...
(
echo @echo off
echo title Vessel Logger
echo cd /d "%%~dp0"
echo.
echo echo Starting Vessel Logger...
echo echo Please wait while the system initializes...
echo.
echo REM Check for required components
echo if not exist "xampp-portable\apache\bin\httpd.exe" ^(
echo     echo ERROR: Apache web server not found!
echo     echo Please contact IT support for complete installation package.
echo     pause
echo     exit /b 1
echo ^)
echo.
echo if not exist "xampp-portable\php\php.exe" ^(
echo     echo ERROR: PHP runtime not found!
echo     echo Please contact IT support for complete installation package.
echo     pause
echo     exit /b 1
echo ^)
echo.
echo REM Start Apache with vessel configuration
echo echo [1/3] Starting web server...
echo start /min "" "xampp-portable\apache\bin\httpd.exe" -D FOREGROUND -f "scripts\vessel-httpd.conf"
echo.
echo REM Wait for server startup
echo echo [2/3] Waiting for server initialization...
echo timeout /t 5 /nobreak ^>nul
echo.
echo REM Open application in browser
echo echo [3/3] Opening Vessel Logger...
echo start http://localhost:8080/
echo.
echo echo.
echo echo Vessel Logger is now running at: http://localhost:8080/
echo echo.
echo echo To stop the system:
echo echo - Close this window
echo echo - Or press Ctrl+C
echo echo.
echo echo Keep this window open while using Vessel Logger
echo pause ^>nul
echo.
echo REM Cleanup when stopping
echo echo Stopping Vessel Logger...
echo taskkill /F /IM httpd.exe 2^>nul
echo echo System stopped. You may now safely remove the USB drive.
echo pause
) > "%PACKAGE_ROOT%\start-vessel-logger.bat"

echo [6/8] Creating utility scripts...

REM Create stop script
(
echo @echo off
echo title Stopping Vessel Logger
echo echo Stopping Vessel Logger web server...
echo taskkill /F /IM httpd.exe 2^>nul
echo if %%errorlevel%% equ 0 ^(
echo     echo Vessel Logger stopped successfully.
echo ^) else ^(
echo     echo No Vessel Logger processes found.
echo ^)
echo echo You may now safely remove the USB drive.
echo pause
) > "%PACKAGE_ROOT%\stop-vessel-logger.bat"

REM Create database backup script
(
echo @echo off
echo title Database Backup
echo cd /d "%%~dp0"
echo.
echo echo Creating database backup...
echo.
echo if not exist "data\database\vessel.db" ^(
echo     echo No database found to backup.
echo     pause
echo     exit /b 1
echo ^)
echo.
echo set BACKUP_DATE=%%DATE:~6,4%%%%DATE:~0,2%%%%DATE:~3,2%%
echo set BACKUP_TIME=%%TIME:~0,2%%%%TIME:~3,2%%
echo set BACKUP_TIME=%%BACKUP_TIME: =0%%
echo.
echo copy "data\database\vessel.db" "data\backups\vessel_%%BACKUP_DATE%%_%%BACKUP_TIME%%.db"
echo.
echo if %%errorlevel%% equ 0 ^(
echo     echo Backup created successfully!
echo     echo File: data\backups\vessel_%%BACKUP_DATE%%_%%BACKUP_TIME%%.db
echo ^) else ^(
echo     echo Backup failed!
echo ^)
echo.
echo pause
) > "%PACKAGE_ROOT%\backup-database.bat"

echo [7/8] Creating PHP configuration...

REM Create minimal php.ini for vessel logger
(
echo ; PHP Configuration for Vessel Logger
echo ; Minimal configuration optimized for vessel logging
echo.
echo [PHP]
echo max_execution_time = 300
echo memory_limit = 256M
echo post_max_size = 50M
echo upload_max_filesize = 50M
echo.
echo ; Error reporting
echo display_errors = Off
echo log_errors = On
echo error_log = "../data/logs/php_errors.log"
echo.
echo ; Extensions required for vessel logger
echo extension=sqlite3
echo extension=pdo_sqlite
echo extension=openssl
echo extension=curl
echo extension=mbstring
echo extension=json
echo.
echo ; Session settings
echo session.cookie_httponly = 1
echo session.use_strict_mode = 1
echo session.cookie_samesite = "Strict"
echo.
echo ; Security
echo expose_php = Off
echo allow_url_fopen = Off
echo allow_url_include = Off
) > "%PACKAGE_ROOT%\xampp-portable\php.ini"

echo [8/8] Creating installation notes...

REM Create installation notes for IT department
(
echo VESSEL LOGGER - IT INSTALLATION NOTES
echo =====================================
echo.
echo This package contains the Vessel Logger application but requires
echo additional components to be fully functional:
echo.
echo REQUIRED COMPONENTS NOT INCLUDED:
echo.
echo 1. APACHE WEB SERVER
echo    - Download: Apache HTTP Server 2.4.x Windows binaries
echo    - Extract to: xampp-portable\apache\
echo    - Required files:
echo      * bin\httpd.exe
echo      * conf\httpd.conf ^(will be overwritten by vessel config^)
echo      * modules\*.so ^(all standard modules^)
echo.
echo 2. PHP RUNTIME  
echo    - Download: PHP 8.2.x Windows binaries ^(Thread Safe^)
echo    - Extract to: xampp-portable\php\
echo    - Required extensions: sqlite3, pdo_sqlite, openssl, curl, mbstring
echo    - Use provided php.ini configuration
echo.
echo 3. SQLITE COMMAND LINE ^(Optional^)
echo    - Download: sqlite3.exe from sqlite.org
echo    - Place in: tools\sqlite3.exe
echo    - Used for database maintenance and debugging
echo.
echo DOWNLOAD SOURCES:
echo - Apache: https://httpd.apache.org/download.cgi
echo - PHP: https://windows.php.net/download/
echo - SQLite: https://www.sqlite.org/download.html
echo.
echo PACKAGE SIZE ESTIMATE:
echo - Apache: ~15MB
echo - PHP: ~35MB  
echo - SQLite: ~1MB
echo - Total additional: ~51MB
echo.
echo TESTING:
echo After adding components, test with: start-vessel-logger.bat
echo Should open browser to http://localhost:8080/
echo.
echo Created: %DATE% %TIME%
) > "%PACKAGE_ROOT%\INSTALLATION_NOTES_FOR_IT.txt"

echo.
echo ========================================
echo         PACKAGE CREATION COMPLETE
echo ========================================
echo.
echo Package created in: %PACKAGE_ROOT%
echo.
echo NEXT STEPS:
echo 1. Add Apache web server binaries to xampp-portable\apache\
echo 2. Add PHP runtime binaries to xampp-portable\php\  
echo 3. Add sqlite3.exe to tools\ ^(optional^)
echo 4. Test complete package with start-vessel-logger.bat
echo 5. Copy entire folder to USB drive for deployment
echo.
echo PACKAGE CONTENTS:
echo - Vessel Logger application ^(complete^)
echo - Configuration files ^(ready^)
echo - Startup scripts ^(ready^)
echo - Documentation ^(complete^)
echo - Directory structure ^(ready^)
echo.
echo Missing components noted in INSTALLATION_NOTES_FOR_IT.txt
echo.
pause
