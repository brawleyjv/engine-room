@echo off
REM Database Export Script for IONOS Deployment (Windows)
REM Run this from your local XAMPP environment

echo 🗄️ Exporting Vessel Logger Database for IONOS Deployment...

REM Set MySQL path (adjust if different)
set MYSQL_PATH=C:\xampp\mysql\bin

REM Export complete database (using your actual database name and credentials)
"%MYSQL_PATH%\mysqldump.exe" -u chief -prustyzeller VesselData > vessel_logger_complete.sql

REM Export structure only
"%MYSQL_PATH%\mysqldump.exe" -u chief -prustyzeller --no-data VesselData > vessel_logger_structure.sql

REM Export data only
"%MYSQL_PATH%\mysqldump.exe" -u chief -prustyzeller --no-create-info VesselData > vessel_logger_data.sql

echo ✅ Export complete! Files created:
echo    - vessel_logger_complete.sql    (Full database)
echo    - vessel_logger_structure.sql   (Structure only)
echo    - vessel_logger_data.sql        (Data only)
echo.
echo 📤 Upload the appropriate .sql file to your IONOS hosting and import via phpMyAdmin
pause
