@echo off
title Open Vessel Logger in Browser
echo Opening Vessel Logger in browser...
echo URL: http://localhost:8080
start http://localhost:8080
timeout /t 2 /nobreak >nul
