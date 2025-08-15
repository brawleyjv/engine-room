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
set "CONFIG_PATH=%~dp0xampp-portable\apache\conf\vessel-production.conf"
cd xampp-portable\apache\bin
start /min httpd.exe -f "%CONFIG_PATH%"

REM Wait a moment for Apache to start
echo Waiting for server to start...
timeout /t 5 /nobreak >nul

REM Check if server started successfully (try multiple times)
echo Checking server status...
set "attempts=0"
:check_server
set /a attempts+=1
curl -s -I "http://localhost:8080/" 2>nul | findstr "200 OK" >nul
if %errorlevel% equ 0 goto server_success
if %attempts% lss 5 (
    timeout /t 2 /nobreak >nul
    goto check_server
)

echo ✗ Failed to start Vessel Logger after 5 attempts
echo.
echo Please check the configuration and try manually with START_VESSEL_LOGGER.bat
echo Press any key to exit...
pause >nul
exit

:server_success
echo ✓ Vessel Logger started successfully!
echo.
echo Opening browser to http://localhost:8080
echo.

REM Open default browser to the vessel logger
start http://localhost:8080

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

exit
