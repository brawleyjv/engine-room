@echo off 
echo Starting Vessel Logger... 
cd /d "%~dp0xampp-portable\apache\bin" 
start /min httpd.exe -f "..\conf\vessel-production.conf" 
echo Vessel Logger started on http://localhost:8080 
echo Press any key to continue... 
pause > nul 
