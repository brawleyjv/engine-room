@echo off
REM Vessel Logger - USB Startup Script (Corrected)
REM This script starts the portable vessel logging system with proper paths

title Vessel Logger - Starting...

echo.
echo ========================================
echo    VESSEL LOGGER - STARTING SYSTEM
echo ========================================
echo.

REM Get the actual USB drive path
set VESSEL_ROOT=%~dp0
set VESSEL_ROOT=%VESSEL_ROOT:~0,-1%

echo Vessel Logger Location: %VESSEL_ROOT%
echo.

REM Check for required components
if not exist "%VESSEL_ROOT%\xampp-portable\apache\bin\httpd.exe" (
    echo ERROR: Apache web server not found!
    echo Expected: %VESSEL_ROOT%\xampp-portable\apache\bin\httpd.exe
    echo Please ensure Apache is properly installed.
    pause
    exit /b 1
)

if not exist "%VESSEL_ROOT%\xampp-portable\php\php.exe" (
    echo ERROR: PHP runtime not found!
    echo Expected: %VESSEL_ROOT%\xampp-portable\php\php.exe
    echo Please ensure PHP is properly installed.
    pause
    exit /b 1
)

if not exist "%VESSEL_ROOT%\app\index.php" (
    echo ERROR: Vessel application not found!
    echo Expected: %VESSEL_ROOT%\app\index.php
    echo Please ensure the application is properly installed.
    pause
    exit /b 1
)

echo [1/5] Checking system requirements...
REM Check if port 8080 is available
netstat -an | find "LISTENING" | find ":8080" >nul
if %errorlevel% equ 0 (
    echo WARNING: Port 8080 is already in use.
    echo Another web server may be running.
    echo.
    set /p CONTINUE="Continue anyway? (y/n): "
    if /i not "%CONTINUE%"=="y" exit /b 1
)

echo [2/5] Creating dynamic configuration...
REM Create Apache config with correct paths
(
echo # Vessel Logger - Dynamic Apache Configuration
echo # Generated automatically with correct USB paths
echo.
echo # Basic server settings
echo ServerRoot "%VESSEL_ROOT%/xampp-portable/apache"
echo Listen 8080
echo ServerName localhost:8080
echo.
echo # Modules - minimal set for PHP and basic functionality
echo LoadModule rewrite_module modules/mod_rewrite.so
echo LoadModule dir_module modules/mod_dir.so
echo LoadModule mime_module modules/mod_mime.so
echo LoadModule php_module modules/mod_php8.so
echo.
echo # PHP Configuration
echo PHPIniDir "%VESSEL_ROOT%/xampp-portable/php"
echo AddType application/x-httpd-php .php
echo.
echo # Document root points to vessel application
echo DocumentRoot "%VESSEL_ROOT%/app"
echo.
echo # Directory settings for vessel application
echo ^<Directory "%VESSEL_ROOT%/app"^>
echo     Options Indexes FollowSymLinks
echo     AllowOverride All
echo     Require all granted
echo ^</Directory^>
echo.
echo # Error and access logs
echo ErrorLog "%VESSEL_ROOT%/data/logs/apache_error.log"
echo CustomLog "%VESSEL_ROOT%/data/logs/apache_access.log" combined
echo.
echo # Security - disable server signature
echo ServerTokens Prod
echo ServerSignature Off
echo.
echo # MIME types
echo TypesConfig conf/mime.types
echo.
echo # Directory index
echo DirectoryIndex index.php index.html
echo.
echo # Timeout settings
echo Timeout 300
echo KeepAlive On
echo MaxKeepAliveRequests 100
echo KeepAliveTimeout 5
) > "%VESSEL_ROOT%\xampp-portable\apache\conf\vessel-dynamic.conf"

echo [3/5] Starting Apache web server...
cd /d "%VESSEL_ROOT%\xampp-portable\apache\bin"

REM Start Apache with dynamic configuration
echo Starting Apache server with dynamic configuration...
start /min "" "%VESSEL_ROOT%\xampp-portable\apache\bin\httpd.exe" -D FOREGROUND -f "%VESSEL_ROOT%\xampp-portable\apache\conf\vessel-dynamic.conf"

REM Wait for server to start
echo [4/5] Waiting for server initialization...
timeout /t 8 /nobreak >nul

REM Test if server is running
echo [5/5] Testing server connection...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost:8080/' -TimeoutSec 10 -ErrorAction Stop; Write-Host 'Server responding: HTTP' $response.StatusCode } catch { Write-Host 'Server test failed:' $_.Exception.Message; exit 1 }"

if %errorlevel% neq 0 (
    echo WARNING: Server may not have started properly.
    echo Check the error log: %VESSEL_ROOT%\data\logs\apache_error.log
    echo Continuing anyway...
)

REM Wait a moment for everything to settle
timeout /t 2 /nobreak >nul

REM Open the application in default browser
echo Opening Vessel Logger in browser...
start http://localhost:8080/

echo.
echo ========================================
echo   VESSEL LOGGER IS NOW RUNNING
echo ========================================
echo.
echo Application URL: http://localhost:8080/
echo Configuration: %VESSEL_ROOT%\xampp-portable\apache\conf\vessel-dynamic.conf
echo Error Log: %VESSEL_ROOT%\data\logs\apache_error.log
echo.
echo The vessel logger is now ready for use.
echo.
echo To stop the system:
echo   - Close this command window
echo   - Or press Ctrl+C
echo.
echo IMPORTANT NOTES:
echo - Keep this USB drive connected while logging
echo - Data is saved locally and will sync when online
echo - Do not remove USB drive while system is running
echo.

REM Keep the window open
echo Press any key to stop the vessel logger...
pause >nul

REM Cleanup when script ends
echo.
echo Stopping vessel logger...

REM Kill Apache processes
taskkill /F /IM httpd.exe 2>nul
if %errorlevel% equ 0 (
    echo Apache server stopped.
) else (
    echo No Apache processes found.
)

echo.
echo Vessel logger stopped safely.
echo You may now safely remove the USB drive.
echo.
pause
