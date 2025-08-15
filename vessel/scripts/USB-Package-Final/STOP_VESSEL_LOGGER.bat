@echo off 
echo Stopping Vessel Logger... 
taskkill /f /im httpd.exe 2>nul 
echo Vessel Logger stopped. 
timeout /t 2 > nul 
