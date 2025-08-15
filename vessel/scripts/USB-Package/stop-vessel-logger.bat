@echo off
title Stopping Vessel Logger
echo Stopping Vessel Logger web server...
taskkill /F /IM httpd.exe 2>nul
if %errorlevel% equ 0 (
    echo Vessel Logger stopped successfully.
) else (
    echo No Vessel Logger processes found.
)
echo You may now safely remove the USB drive.
pause
