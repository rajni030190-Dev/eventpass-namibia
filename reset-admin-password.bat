@echo off
setlocal
set "ERRORLEVEL="
title EventPass Namibia - Admin Password Reset Utility
color 0B

echo ============================================================================
echo         EVENTPASS NAMIBIA - ADMINISTRATOR PASSWORD RESET UTILITY
echo ============================================================================
echo.

set "NEW_PASS=%~1"
if "%NEW_PASS%"=="" (
    set "NEW_PASS=EventPass@2026!"
)

set "PHP_BIN=C:\Users\USER A\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe"
set "PHP_INI=%~dp0php.ini"

if not exist "%PHP_BIN%" (
    set "PHP_BIN=php.exe"
)

echo [INFO] Resetting administrator (ID: 1) password...
"%PHP_BIN%" -c "%PHP_INI%" -r "require '%~dp0wordpress/wp-load.php'; wp_set_password('%NEW_PASS%', 1); echo 'SUCCESS';" | find "SUCCESS" >nul 2>&1

if not errorlevel 1 (
    echo.
    echo ============================================================================
    echo   [SUCCESS] Admin Password Updated Successfully!
    echo.
    echo   Login URL:   http://localhost:8080/login
    echo   Username:    admin (or admin@eventpass.com.na)
    echo   Password:    %NEW_PASS%
    echo ============================================================================
) else (
    echo [ERROR] Failed to update password. Check MySQL service status.
)
echo.
pause
