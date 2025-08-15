@echo off
REM Auto Sync Service for Vessel Logger
REM This script runs the auto sync every 30 minutes

title Vessel Logger - Auto Sync Service
echo.
echo ===============================================
echo    VESSEL LOGGER - AUTO SYNC SERVICE
echo ===============================================
echo.
echo Starting automatic sync service...
echo Sync will run every 30 minutes
echo.

:sync_loop
REM Run the auto sync
php "%~dp0app\auto_sync.php"

REM Wait 30 minutes (1800 seconds)
echo Next sync in 30 minutes...
timeout /t 1800 /nobreak >nul

REM Continue the loop
goto sync_loop
