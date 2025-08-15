@echo off
title Database Backup
cd /d "%~dp0"

echo Creating database backup...

if not exist "data\database\vessel.db" (
    echo No database found to backup.
    pause
    exit /b 1
)

set BACKUP_DATE=%DATE:~6,4%%DATE:~0,2%%DATE:~3,2%
set BACKUP_TIME=%TIME:~0,2%%TIME:~3,2%
set BACKUP_TIME=%BACKUP_TIME: =0%

copy "data\database\vessel.db" "data\backups\vessel_%BACKUP_DATE%_%BACKUP_TIME%.db"

if %errorlevel% equ 0 (
    echo Backup created successfully!
    echo File: data\backups\vessel_%BACKUP_DATE%_%BACKUP_TIME%.db
) else (
    echo Backup failed!
)

pause
