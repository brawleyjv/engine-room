@echo off
REM Vessel Logger - USB Startup Script
REM This script starts the portable vessel logging system

title Vessel Logger - Starting...

echo.
echo ========================================
echo    VESSEL LOGGER - STARTING SYSTEM
echo ========================================
echo.

REM Get the drive letter where this script is located
set VESSEL_DRIVE=%~d0
set VESSEL_ROOT=%~dp0

echo Vessel Logger Location: %VESSEL_ROOT%
echo.

REM Check if XAMPP portable exists
if not exist "%VESSEL_ROOT%xampp-portable" (
    echo ERROR: XAMPP Portable not found!
    echo Please ensure the complete vessel logger package is installed.
    pause
    exit /b 1
)

REM Check if vessel application exists
if not exist "%VESSEL_ROOT%app" (
    echo ERROR: Vessel application not found!
    echo Please ensure the complete vessel logger package is installed.
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

echo [2/5] Starting Apache web server...
cd /d "%VESSEL_ROOT%xampp-portable"

REM Start Apache on port 8080
echo Starting Apache server...
start /min "" "%VESSEL_ROOT%xampp-portable\apache\bin\httpd.exe" -D FOREGROUND -f "%VESSEL_ROOT%xampp-portable\apache\conf\httpd.conf"

REM Wait for server to start
echo Waiting for server to start...
timeout /t 5 /nobreak >nul

REM Test if server is running
echo [3/5] Testing server connection...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost:8080/vessel-test' -TimeoutSec 5 -ErrorAction Stop } catch { exit 1 }"
if %errorlevel% neq 0 (
    echo WARNING: Server may not have started properly.
    echo Continuing anyway...
)

echo [4/5] Initializing vessel database...
REM Database will be initialized automatically when first accessed

echo [5/5] Opening vessel logger...
REM Wait a moment for everything to settle
timeout /t 2 /nobreak >nul

REM Open the application in default browser
start http://localhost:8080/

echo.
echo ========================================
echo   VESSEL LOGGER IS NOW RUNNING
echo ========================================
echo.
echo Application URL: http://localhost:8080/
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
