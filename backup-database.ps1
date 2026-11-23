<#
.SYNOPSIS
    EventPass Namibia - Enterprise MySQL Database Backup Automation
.DESCRIPTION
    Performs full logical backup of the eventpass_namibia database, updates the master snapshot,
    and rotates historical backups by pruning archives older than 14 days.
#>

param(
    [string]$DbName = "eventpass_namibia",
    [string]$DbUser = "root",
    [string]$DbPass = "Email99!",
    [string]$DbHost = "127.0.0.1",
    [int]$DbPort = 3306,
    [string]$BackupDir = "$PSScriptRoot\backups",
    [string]$LatestSnapshot = "$PSScriptRoot\eventpass_namibia_backup.sql",
    [int]$RetentionDays = 14
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $BackupDir)) {
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$TargetFile = Join-Path $BackupDir "eventpass_namibia_$Timestamp.sql"

$mysqldump = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"
if (-not (Test-Path $mysqldump)) {
    $mysqldump = (Get-Command mysqldump -ErrorAction SilentlyContinue).Source
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " [EventPass Namibia] Automated Database Backup" -ForegroundColor Cyan
Write-Host " Database:   $DbName"
Write-Host " Host:       $DbHost`:$DbPort"
Write-Host " Target:     $TargetFile"
Write-Host "============================================================" -ForegroundColor Cyan

$dumpArgs = @(
    "-h", $DbHost,
    "-P", $DbPort,
    "-u", $DbUser,
    "-p$DbPass",
    "--routines",
    "--triggers",
    "--single-transaction",
    "--default-character-set=utf8mb4",
    $DbName
)

try {
    $process = Start-Process -FilePath $mysqldump -ArgumentList $dumpArgs -NoNewWindow -Wait -PassThru -RedirectStandardOutput $TargetFile
    if ($process.ExitCode -eq 0) {
        $fileInfo = Get-Item $TargetFile
        Write-Host "[SUCCESS] Backup generated successfully! ($($fileInfo.Length) bytes)" -ForegroundColor Green
        Copy-Item -Path $TargetFile -Destination $LatestSnapshot -Force
        Write-Host "[SUCCESS] Master snapshot updated: $LatestSnapshot" -ForegroundColor Green

        # Prune old backups older than $RetentionDays
        $cutoffDate = (Get-Date).AddDays(-$RetentionDays)
        $oldBackups = Get-ChildItem -Path $BackupDir -Filter "eventpass_namibia_*.sql" | Where-Object { $_.LastWriteTime -lt $cutoffDate }
        foreach ($old in $oldBackups) {
            Remove-Item $old.FullName -Force
            Write-Host "[ROTATION] Pruned aged archive: $($old.Name)" -ForegroundColor Yellow
        }
    } else {
        Write-Error "mysqldump failed with exit code: $($process.ExitCode)"
    }
} catch {
    Write-Error "Database backup failed: $_"
}
