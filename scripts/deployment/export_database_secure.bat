@echo off
REM Database Export Script for IONOS Deployment (Windows) - Secure Version
REM Run this from your local XAMPP environment

echo 🗄️ Exporting Vessel Logger Database for IONOS Deployment...
echo.
echo This script will prompt for the database password (rustyzeller)
echo.

REM Set MySQL path (adjust if different)
set MYSQL_PATH=C:\xampp\mysql\bin

REM Export complete database (will prompt for password)
echo Exporting complete database...
"%MYSQL_PATH%\mysqldump.exe" -u chief -p VesselData > vessel_logger_complete.sql

REM Export structure only (will prompt for password again)
echo Exporting structure only...
"%MYSQL_PATH%\mysqldump.exe" -u chief -p --no-data VesselData > vessel_logger_structure.sql

REM Export data only (will prompt for password again)
echo Exporting data only...
"%MYSQL_PATH%\mysqldump.exe" -u chief -p --no-create-info VesselData > vessel_logger_data.sql

echo ✅ Export complete! Files created:
echo    - vessel_logger_complete.sql    (Full database)
echo    - vessel_logger_structure.sql   (Structure only)
echo    - vessel_logger_data.sql        (Data only)
echo.
echo 📤 Upload the appropriate .sql file to your IONOS hosting and import via phpMyAdmin
pause
