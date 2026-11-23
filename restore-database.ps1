<#
.SYNOPSIS
    EventPass Namibia - Database Disaster Recovery & Restore Utility
#>

param(
    [string]$DbName = "eventpass_namibia",
    [string]$DbUser = "root",
    [string]$DbPass = "Email99!",
    [string]$DbHost = "127.0.0.1",
    [int]$DbPort = 3306,
    [string]$SnapshotFile = "$PSScriptRoot\eventpass_namibia_backup.sql"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $SnapshotFile)) {
    Write-Error "Backup snapshot file not found: $SnapshotFile"
    exit 1
}

$mysql = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
if (-not (Test-Path $mysql)) {
    $mysql = (Get-Command mysql -ErrorAction SilentlyContinue).Source
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " [EventPass Namibia] Database Restore / Disaster Recovery" -ForegroundColor Cyan
Write-Host " Database:   $DbName"
Write-Host " Snapshot:   $SnapshotFile"
Write-Host " Host:       $DbHost`:$DbPort"
Write-Host "============================================================" -ForegroundColor Cyan

cmd.exe /c "`"$mysql`" -h $DbHost -P $DbPort -u $DbUser -p$DbPass -e `"CREATE DATABASE IF NOT EXISTS $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`""
cmd.exe /c "`"$mysql`" -h $DbHost -P $DbPort -u $DbUser -p$DbPass $DbName < `"$SnapshotFile`""

if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Database $DbName restored successfully!" -ForegroundColor Green
} else {
    Write-Error "Database restore failed with exit code $LASTEXITCODE"
}
