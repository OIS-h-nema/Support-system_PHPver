# ============================================
# Support Report WEB - Test Environment Deploy Script
# PowerShell Version
# ============================================

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "========================================"
Write-Host "Support Report WEB Deploy to Test"
Write-Host "========================================"
Write-Host ""

# Set directories - use wildcard to find source directory
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$SourceDir = Get-ChildItem -Path $ScriptDir -Directory | Where-Object { $_.Name -like "04_*" } | Select-Object -First 1 -ExpandProperty FullName
$TargetDir = "\\LPG-NEMA\C\inetpub\wwwroot\support-system"

Write-Host "Script Dir: $ScriptDir"
Write-Host "Source: $SourceDir"
Write-Host "Target: $TargetDir"
Write-Host ""

# Check source directory
Write-Host "Checking source directory..."
if (-not $SourceDir -or -not (Test-Path $SourceDir)) {
    Write-Host "ERROR: Source directory not found (04_*)" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "Source OK" -ForegroundColor Green
Write-Host ""

# Check target directory
Write-Host "Checking connection to test environment..."
if (-not (Test-Path $TargetDir)) {
    Write-Host "ERROR: Cannot connect to test environment" -ForegroundColor Red
    Write-Host "Path: $TargetDir"
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host "Connection OK" -ForegroundColor Green
Write-Host ""

# Create directories
Write-Host "Creating directories..."
$dirs = @("includes", "templates", "js", "css", "logs", "images")
foreach ($dir in $dirs) {
    $path = Join-Path $TargetDir $dir
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path -Force | Out-Null
    }
}
Write-Host "Done" -ForegroundColor Green
Write-Host ""

# Copy PHP files
Write-Host "Copying PHP files..."
$phpFiles = Get-ChildItem -Path $SourceDir -Filter "*.php" -File
foreach ($file in $phpFiles) {
    Copy-Item -Path $file.FullName -Destination $TargetDir -Force
    Write-Host "  $($file.Name)"
}
Write-Host "Done ($($phpFiles.Count) files)" -ForegroundColor Green
Write-Host ""

# Copy includes
Write-Host "Copying includes..."
$includesDir = Join-Path $SourceDir "includes"
if (Test-Path $includesDir) {
    $files = Get-ChildItem -Path $includesDir -File
    foreach ($file in $files) {
        Copy-Item -Path $file.FullName -Destination (Join-Path $TargetDir "includes") -Force
        Write-Host "  $($file.Name)"
    }
    Write-Host "Done ($($files.Count) files)" -ForegroundColor Green
} else {
    Write-Host "Directory not found" -ForegroundColor Yellow
}
Write-Host ""

# Copy templates
Write-Host "Copying templates..."
$templatesDir = Join-Path $SourceDir "templates"
if (Test-Path $templatesDir) {
    $files = Get-ChildItem -Path $templatesDir -File
    foreach ($file in $files) {
        Copy-Item -Path $file.FullName -Destination (Join-Path $TargetDir "templates") -Force
        Write-Host "  $($file.Name)"
    }
    Write-Host "Done ($($files.Count) files)" -ForegroundColor Green
} else {
    Write-Host "Directory not found" -ForegroundColor Yellow
}
Write-Host ""

# Copy js
Write-Host "Copying js..."
$jsDir = Join-Path $SourceDir "js"
if (Test-Path $jsDir) {
    $files = Get-ChildItem -Path $jsDir -Filter "*.js" -File
    foreach ($file in $files) {
        Copy-Item -Path $file.FullName -Destination (Join-Path $TargetDir "js") -Force
        Write-Host "  $($file.Name)"
    }
    Write-Host "Done ($($files.Count) files)" -ForegroundColor Green
} else {
    Write-Host "Directory not found" -ForegroundColor Yellow
}
Write-Host ""

# Copy css
Write-Host "Copying css..."
$cssDir = Join-Path $SourceDir "css"
if (Test-Path $cssDir) {
    $files = Get-ChildItem -Path $cssDir -Filter "*.css" -File
    foreach ($file in $files) {
        Copy-Item -Path $file.FullName -Destination (Join-Path $TargetDir "css") -Force
        Write-Host "  $($file.Name)"
    }
    Write-Host "Done ($($files.Count) files)" -ForegroundColor Green
} else {
    Write-Host "Directory not found" -ForegroundColor Yellow
}
Write-Host ""

# Copy images (if exist)
Write-Host "Copying images..."
$imagesDir = Join-Path $SourceDir "images"
if (Test-Path $imagesDir) {
    $files = Get-ChildItem -Path $imagesDir -File -ErrorAction SilentlyContinue
    if ($files) {
        foreach ($file in $files) {
            Copy-Item -Path $file.FullName -Destination (Join-Path $TargetDir "images") -Force
            Write-Host "  $($file.Name)"
        }
        Write-Host "Done ($($files.Count) files)" -ForegroundColor Green
    } else {
        Write-Host "No files" -ForegroundColor Yellow
    }
} else {
    Write-Host "Directory not found" -ForegroundColor Yellow
}
Write-Host ""

Write-Host "========================================"
Write-Host "DEPLOY COMPLETED SUCCESSFULLY" -ForegroundColor Green
Write-Host "========================================"
Write-Host ""
Write-Host "Target: $TargetDir"
Write-Host ""
Write-Host "URL: http://lpg-nema/support-system/login.php"
Write-Host ""

Read-Host "Press Enter to exit"
