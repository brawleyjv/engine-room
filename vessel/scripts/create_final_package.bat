@echo off
REM Vessel Logger USB Deployment - Final Package Creation
REM This script prepares the USB package for production deployment

echo ===============================================
echo    VESSEL LOGGER USB DEPLOYMENT PACKAGER
echo ===============================================
echo.

REM Set deployment directory
set DEPLOY_DIR=%~dp0USB-Package-Final
set SOURCE_DIR=%~dp0USB-Package

echo Creating final deployment package...

REM Remove old deployment if exists
if exist "%DEPLOY_DIR%" (
    echo Removing old deployment...
    rmdir /s /q "%DEPLOY_DIR%"
)

REM Create deployment directory
mkdir "%DEPLOY_DIR%"

echo Copying USB package...
xcopy "%SOURCE_DIR%\*" "%DEPLOY_DIR%\" /s /e /i /h /y

REM Create production-ready batch files
echo Creating production startup scripts...

REM Create start script
echo @echo off > "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo echo Starting Vessel Logger... >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo cd /d "%%~dp0xampp-portable\apache\bin" >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo start /min httpd.exe -f "..\conf\vessel-production.conf" >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo echo Vessel Logger started on http://localhost:8080 >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo echo Press any key to continue... >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"
echo pause ^> nul >> "%DEPLOY_DIR%\START_VESSEL_LOGGER.bat"

REM Create stop script
echo @echo off > "%DEPLOY_DIR%\STOP_VESSEL_LOGGER.bat"
echo echo Stopping Vessel Logger... >> "%DEPLOY_DIR%\STOP_VESSEL_LOGGER.bat"
echo taskkill /f /im httpd.exe 2^>nul >> "%DEPLOY_DIR%\STOP_VESSEL_LOGGER.bat"
echo echo Vessel Logger stopped. >> "%DEPLOY_DIR%\STOP_VESSEL_LOGGER.bat"
echo timeout /t 2 ^> nul >> "%DEPLOY_DIR%\STOP_VESSEL_LOGGER.bat"

REM Create status check script
echo @echo off > "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo echo Checking Vessel Logger status... >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo curl -I "http://localhost:8080/" 2^>nul ^| findstr "HTTP/1.1 200" ^>nul >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo if %%errorlevel%% equ 0 ( >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo     echo ✓ Vessel Logger is running on http://localhost:8080 >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo ^) else ( >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo     echo ✗ Vessel Logger is not running >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo ^) >> "%DEPLOY_DIR%\CHECK_STATUS.bat"
echo pause >> "%DEPLOY_DIR%\CHECK_STATUS.bat"

REM Create README file
echo Creating README file...
echo VESSEL LOGGER USB DEPLOYMENT > "%DEPLOY_DIR%\README.txt"
echo ============================== >> "%DEPLOY_DIR%\README.txt"
echo. >> "%DEPLOY_DIR%\README.txt"
echo This is a portable vessel logger system that runs entirely from USB. >> "%DEPLOY_DIR%\README.txt"
echo. >> "%DEPLOY_DIR%\README.txt"
echo QUICK START: >> "%DEPLOY_DIR%\README.txt"
echo 1. Double-click START_VESSEL_LOGGER.bat >> "%DEPLOY_DIR%\README.txt"
echo 2. Open browser to http://localhost:8080 >> "%DEPLOY_DIR%\README.txt"
echo 3. Follow setup wizard on first run >> "%DEPLOY_DIR%\README.txt"
echo. >> "%DEPLOY_DIR%\README.txt"
echo SCRIPTS: >> "%DEPLOY_DIR%\README.txt"
echo - START_VESSEL_LOGGER.bat: Start the vessel logger >> "%DEPLOY_DIR%\README.txt"
echo - STOP_VESSEL_LOGGER.bat: Stop the vessel logger >> "%DEPLOY_DIR%\README.txt"
echo - CHECK_STATUS.bat: Check if logger is running >> "%DEPLOY_DIR%\README.txt"
echo. >> "%DEPLOY_DIR%\README.txt"
echo SECURITY: >> "%DEPLOY_DIR%\README.txt"
echo - Only runs on port 8080 (port 80 blocked) >> "%DEPLOY_DIR%\README.txt"
echo - No external network access by default >> "%DEPLOY_DIR%\README.txt"
echo - SQLite database for offline operation >> "%DEPLOY_DIR%\README.txt"
echo - Session-based authentication >> "%DEPLOY_DIR%\README.txt"
echo. >> "%DEPLOY_DIR%\README.txt"
echo TROUBLESHOOTING: >> "%DEPLOY_DIR%\README.txt"
echo If port 8080 is in use, edit vessel-production.conf and change Listen port >> "%DEPLOY_DIR%\README.txt"

REM Remove test files from production package
echo Cleaning up test files...
del /q "%DEPLOY_DIR%\app\test.php" 2>nul
del /q "%DEPLOY_DIR%\app\info.php" 2>nul
del /q "%DEPLOY_DIR%\app\db_test.php" 2>nul
del /q "%DEPLOY_DIR%\app\vessel_test.php" 2>nul

REM Set production configuration
echo Configuring for production...
powershell -Command "(Get-Content '%DEPLOY_DIR%\xampp-portable\apache\conf\vessel-production.conf') -replace 'vessel-working.conf', 'vessel-production.conf' | Set-Content '%DEPLOY_DIR%\xampp-portable\apache\conf\vessel-production.conf'"

echo.
echo ===============================================
echo    DEPLOYMENT PACKAGE CREATED SUCCESSFULLY!
echo ===============================================
echo.
echo Location: %DEPLOY_DIR%
echo.
echo Ready for USB deployment to vessels.
echo.
echo To test: Run START_VESSEL_LOGGER.bat in the final package
echo.
pause
