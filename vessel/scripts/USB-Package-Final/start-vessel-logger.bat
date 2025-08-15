@echo off
title Vessel Logger
cd /d "%~dp0"

echo Starting Vessel Logger...
echo Please wait while the system initializes...

REM Check for required components
if not exist "xampp-portable\apache\bin\httpd.exe" (
    echo ERROR: Apache web server not found!
    echo Please contact IT support for complete installation package.
    pause
    exit /b 1
)

if not exist "xampp-portable\php\php.exe" (
    echo ERROR: PHP runtime not found!
    echo Please contact IT support for complete installation package.
    pause
    exit /b 1
)

REM Start Apache with vessel configuration
echo [1/3] Starting web server...
start /min "" "xampp-portable\apache\bin\httpd.exe" -D FOREGROUND -f "scripts\vessel-httpd.conf"

REM Wait for server startup
echo [2/3] Waiting for server initialization...
timeout /t 5 /nobreak >nul

REM Open application in browser
echo [3/3] Opening Vessel Logger...
start http://localhost:8080/

echo.
echo Vessel Logger is now running at: http://localhost:8080/
echo.
echo To stop the system:
echo - Close this window
echo - Or press Ctrl+C
echo.
echo Keep this window open while using Vessel Logger
pause >nul

REM Cleanup when stopping
echo Stopping Vessel Logger...
taskkill /F /IM httpd.exe 2>nul
echo System stopped. You may now safely remove the USB drive.
pause
