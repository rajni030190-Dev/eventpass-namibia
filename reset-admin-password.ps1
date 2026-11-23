<#
.SYNOPSIS
    EventPass Namibia - Administrator Password Reset Tool
#>

param(
    [string]$NewPassword = "EventPass@2026!"
)

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "    EVENTPASS NAMIBIA - ADMIN PASSWORD RESET UTILITY        " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

$php = "C:\Users\USER A\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe"
if (-not (Test-Path $php)) { $php = "php.exe" }

$ini = Join-Path $PSScriptRoot "php.ini"
$wpLoad = (Join-Path $PSScriptRoot "wordpress\wp-load.php") -replace '\\', '/'

$code = "require '$wpLoad'; wp_set_password('$NewPassword', 1); echo 'RESET_OK';"
$output = & $php -c $ini -r $code

if ($output -match "RESET_OK") {
    Write-Host "`n[SUCCESS] Administrator password updated successfully!" -ForegroundColor Green
    Write-Host "Username: admin (or admin@eventpass.com.na)" -ForegroundColor White
    Write-Host "Password: $NewPassword" -ForegroundColor Yellow
    Write-Host "Login URL: http://localhost:8080/login`n" -ForegroundColor Cyan
} else {
    Write-Host "`n[ERROR] Could not reset password. Ensure MySQL is running.`n" -ForegroundColor Red
}
