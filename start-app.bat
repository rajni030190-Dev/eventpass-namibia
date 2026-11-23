@echo off
setlocal
set "ERRORLEVEL="
title EventPass Namibia - Application Server
color 0B

echo ============================================================================
echo                     EVENTPASS NAMIBIA - APPLICATION SERVER
echo               Every Event. One Pass.  (WordPress 6.x + MySQL 8.0)
echo ============================================================================
echo.

:: 1. Verify MySQL Service
echo [1/3] Checking MySQL Database Service...
sc query "MySQL_Server" | find "RUNNING" >nul 2>&1
if not errorlevel 1 (
    echo [OK] MySQL Service [MySQL_Server] is RUNNING.
) else (
    echo [INFO] Starting MySQL_Server service...
    net start "MySQL_Server" >nul 2>&1
    echo [INFO] Continuing to launch web server...
)
echo.

:: 2. Launch PHP Web Server
echo [2/3] Starting EventPass Web Server on http://localhost:8080 ...
set "PHP_BIN=C:\Users\USER A\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe"
set "PHP_INI=%~dp0php.ini"
set "WP_DIR=%~dp0wordpress"
set "ROUTER=%~dp0wordpress\router.php"

if not exist "%PHP_BIN%" (
    set "PHP_BIN=php.exe"
)

:: 3. Open Browser
echo [3/3] Launching EventPass Namibia in your default browser...
start http://localhost:8080/

echo.
echo ============================================================================
echo   APPLICATION RUNNING AT:   http://localhost:8080
echo.
echo   Key URLs:
echo   - Public Website:         http://localhost:8080/
echo   - Explore Events:         http://localhost:8080/explore-events/
echo   - Platform Admin Hub:     http://localhost:8080/admin-dashboard/
echo   - Organiser Dashboard:    http://localhost:8080/organiser-dashboard/
echo   - Gate QR Scanner:        http://localhost:8080/gate-scanner/
echo.
echo   Press Ctrl+C to stop the server at any time.
echo ============================================================================
echo.

"%PHP_BIN%" -c "%PHP_INI%" -S 127.0.0.1:8080 -t "%WP_DIR%" "%ROUTER%"

pause
