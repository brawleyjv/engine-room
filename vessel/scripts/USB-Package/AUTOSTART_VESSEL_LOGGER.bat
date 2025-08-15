@echo off
title Vessel Logger - Auto Startup
echo.
echo ===============================================
echo    VESSEL LOGGER - AUTO STARTUP
echo ===============================================
echo.
echo Starting Vessel Logger automatically...
echo.

REM Change to the correct directory
cd /d "%~dp0"

REM Check if Apache is already running on port 8080
netstat -an | findstr ":8080" >nul
if %errorlevel% equ 0 (
    echo Vessel Logger is already running!
    echo Opening browser...
    start http://localhost:8080
    echo.
    echo Press any key to exit...
    pause >nul
    exit
)

REM Start Apache in the background
echo Starting Apache server...
set "CONFIG_PATH=%~dp0xampp-portable\apache\conf\vessel-working.conf"
cd xampp-portable\apache\bin
start /min httpd.exe -f "%CONFIG_PATH%"

REM Wait a moment for Apache to start
echo Waiting for server to start...
timeout /t 3 /nobreak >nul

REM Check if server started successfully
echo Checking server status...
curl -s -I "http://localhost:8080/" | findstr "200 OK" >nul
if %errorlevel% equ 0 (
    echo ✓ Vessel Logger started successfully!
    echo.
    echo Opening browser...
    echo.
    
    REM Check if vessel is registered, if not open registration page
    if exist "%~dp0app\vessel_config.json" (
        echo Vessel already registered, opening logger...
        start http://localhost:8080/vessel_login.php
    ) else (
        echo Vessel not registered, opening registration setup...
        start http://localhost:8080/vessel_registration.php
    )
    
    echo ===============================================
    echo   VESSEL LOGGER IS NOW RUNNING
    echo ===============================================
    echo.
    echo URL: http://localhost:8080
    echo.
    echo To stop the server, run STOP_VESSEL_LOGGER.bat
    echo.
    echo This window will close in 10 seconds...
    timeout /t 10 /nobreak >nul
    
) else (
    echo ✗ Failed to start Vessel Logger
    echo.
    echo Please check the configuration and try again.
    echo Press any key to exit...
    pause >nul
)

exit
