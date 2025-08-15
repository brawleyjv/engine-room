@echo off 
echo Checking Vessel Logger status... 
curl -I "http://localhost:8080/" 2>nul | findstr "HTTP/1.1 200" >nul 
if %errorlevel% equ 0 ( 
    echo ✓ Vessel Logger is running on http://localhost:8080 
) else ( 
    echo ✗ Vessel Logger is not running 
) 
pause 
