@echo off
setlocal
set "ERRORLEVEL="

:: ============================================================================
:: EventPass Namibia - Database Disaster Recovery & Restore Utility
:: ============================================================================

set DB_NAME=eventpass_namibia
set DB_USER=root
set DB_PASS=Email99!
set DB_HOST=127.0.0.1
set DB_PORT=3306

set SNAPSHOT_FILE=%1
if "%SNAPSHOT_FILE%"=="" (
    set "SNAPSHOT_FILE=%~dp0eventpass_namibia_backup.sql"
)

if not exist "%SNAPSHOT_FILE%" (
    echo [ERROR] Backup snapshot file not found: %SNAPSHOT_FILE%
    exit /b 1
)

set MYSQL_BIN="C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
if not exist %MYSQL_BIN% (
    set MYSQL_BIN=mysql.exe
)

echo ============================================================================
echo [EventPass Namibia] Restoring Database from Snapshot...
echo Database:   %DB_NAME%
echo Snapshot:   %SNAPSHOT_FILE%
echo Host:       %DB_HOST%:%DB_PORT%
echo ============================================================================

%MYSQL_BIN% -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p"%DB_PASS%" -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

%MYSQL_BIN% -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p"%DB_PASS%" %DB_NAME% < "%SNAPSHOT_FILE%"

if %ERRORLEVEL% equ 0 (
    echo [SUCCESS] Database %DB_NAME% restored successfully!
    echo ============================================================================
) else (
    echo [ERROR] Restore failed with error code %ERRORLEVEL%!
    echo ============================================================================
    exit /b %ERRORLEVEL%
)

endlocal
