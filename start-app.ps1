<#
.SYNOPSIS
    EventPass Namibia - Application Server Launcher (PowerShell)
#>

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "      EVENTPASS NAMIBIA - APPLICATION SERVER LAUNCHER       " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

# 1. Check MySQL Service
$mysqlService = Get-Service -Name "MySQL_Server" -ErrorAction SilentlyContinue
if ($mysqlService -and $mysqlService.Status -ne 'Running') {
    Write-Host "[INFO] Starting MySQL_Server service..." -ForegroundColor Yellow
    Start-Service -Name "MySQL_Server"
}
Write-Host "[OK] MySQL 8.0 service is active on port 3306." -ForegroundColor Green

# 2. PHP Paths
$php = "C:\Users\USER A\AppData\Roaming\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe"
$ini = Join-Path $PSScriptRoot "php.ini"
$docroot = Join-Path $PSScriptRoot "wordpress"
$router = Join-Path $PSScriptRoot "wordpress\router.php"

# 3. Open browser only once port 8080 is listening
Start-Job -ScriptBlock {
    for ($i = 0; $i -lt 60; $i++) {
        try {
            $client = New-Object System.Net.Sockets.TcpClient('127.0.0.1', 8080)
            $client.Close()
            Start-Process "http://localhost:8080/"
            break
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
} | Out-Null

Write-Host "`nEventPass Namibia is running at http://localhost:8080" -ForegroundColor Green
Write-Host "Press Ctrl+C to terminate the web server.`n" -ForegroundColor DarkGray

& $php -c $ini -S 127.0.0.1:8080 -t $docroot $router
