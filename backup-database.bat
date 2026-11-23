@echo off
setlocal
set "ERRORLEVEL="

:: ============================================================================
:: EventPass Namibia - Automated Database Backup Script (MySQL 8.0)
:: ============================================================================

set DB_NAME=eventpass_namibia
set DB_USER=root
set DB_PASS=Email99!
set DB_HOST=127.0.0.1
set DB_PORT=3306

set "BACKUP_DIR=%~dp0backups"
set "LATEST_BACKUP=%~dp0eventpass_namibia_backup.sql"

:: Resolve MySQLDump Binary
set MYSQLDUMP="C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"
if not exist %MYSQLDUMP% (
    set MYSQLDUMP=mysqldump.exe
)

:: Generate YYYYMMDD_HHMMSS Timestamp via PowerShell
for /f "usebackq tokens=*" %%i in (`powershell -NoProfile -Command "Get-Date -Format 'yyyyMMdd_HHmmss'"`) do set TIMESTAMP=%%i
set TARGET_FILE=%BACKUP_DIR%\eventpass_namibia_%TIMESTAMP%.sql

if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
)

echo ============================================================================
echo [EventPass Namibia] Starting Automated Database Backup...
echo Database: %DB_NAME%
echo Host: %DB_HOST%:%DB_PORT%
echo Timestamp: %TIMESTAMP%
echo Target File: %TARGET_FILE%
echo ============================================================================

%MYSQLDUMP% -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p"%DB_PASS%" --routines --triggers --single-transaction --default-character-set=utf8mb4 %DB_NAME% > "%TARGET_FILE%"

if %ERRORLEVEL% equ 0 (
    echo [SUCCESS] Backup completed successfully!
    echo Copying snapshot to %LATEST_BACKUP%...
    copy /Y "%TARGET_FILE%" "%LATEST_BACKUP%" >nul
    for %%F in ("%TARGET_FILE%") do echo File Size: %%~zF bytes
    echo ============================================================================
) else (
    echo [ERROR] Backup failed with exit code %ERRORLEVEL%!
    echo Check database connectivity and credentials.
    echo ============================================================================
    exit /b %ERRORLEVEL%
)
