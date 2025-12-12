@echo off
chcp 932 >nul
setlocal enabledelayedexpansion

REM ============================================
REM Support Report WEB - Test Environment Deploy Script
REM Created: 2025-12-10
REM Location: Project Root Directory
REM ============================================

echo ========================================
echo Support Report WEB Deploy to Test
echo ========================================
echo.

REM Set directories (use short path or quotes properly)
set "SOURCE_DIR=D:\#M\PG_DATA\evo_新規開発課\Support-system_PHPver\04_製造"
set "TARGET_DIR=\\LPG-NEMA\C\inetpub\wwwroot\support-system"

echo Source: %SOURCE_DIR%
echo Target: %TARGET_DIR%
echo.

REM Check source directory
echo Checking source directory...
if not exist "%SOURCE_DIR%\" (
    echo ERROR: Source directory not found
    echo Path: %SOURCE_DIR%
    pause
    exit /b 1
)
echo Source OK
echo.

REM Connection Check
echo Checking connection to test environment...
if not exist "%TARGET_DIR%\" (
    echo ERROR: Cannot connect to test environment
    echo Path: %TARGET_DIR%
    echo.
    echo Please check:
    echo  - Network connection
    echo  - Shared folder permissions
    echo.
    pause
    exit /b 1
)
echo Connection OK
echo.

REM Create Directories
echo Creating directories...
if not exist "%TARGET_DIR%\includes\" mkdir "%TARGET_DIR%\includes"
if not exist "%TARGET_DIR%\templates\" mkdir "%TARGET_DIR%\templates"
if not exist "%TARGET_DIR%\js\" mkdir "%TARGET_DIR%\js"
if not exist "%TARGET_DIR%\css\" mkdir "%TARGET_DIR%\css"
if not exist "%TARGET_DIR%\logs\" mkdir "%TARGET_DIR%\logs"
if not exist "%TARGET_DIR%\images\" mkdir "%TARGET_DIR%\images"
echo Directories created
echo.

REM Copy PHP files (root)
echo Copying PHP files...
xcopy "%SOURCE_DIR%\*.php" "%TARGET_DIR%\" /Y /Q
if errorlevel 1 (
    echo WARNING: Some PHP files may not have been copied
)
echo PHP files copied
echo.

REM Copy includes directory
echo Copying includes directory...
xcopy "%SOURCE_DIR%\includes\*.*" "%TARGET_DIR%\includes\" /Y /Q
if errorlevel 1 (
    echo WARNING: Some includes files may not have been copied
)
echo includes copied
echo.

REM Copy templates directory
echo Copying templates directory...
xcopy "%SOURCE_DIR%\templates\*.*" "%TARGET_DIR%\templates\" /Y /Q
if errorlevel 1 (
    echo WARNING: Some templates files may not have been copied
)
echo templates copied
echo.

REM Copy js directory
echo Copying js directory...
xcopy "%SOURCE_DIR%\js\*.js" "%TARGET_DIR%\js\" /Y /Q
if errorlevel 1 (
    echo WARNING: Some js files may not have been copied
)
echo js copied
echo.

REM Copy css directory
echo Copying css directory...
xcopy "%SOURCE_DIR%\css\*.css" "%TARGET_DIR%\css\" /Y /Q
if errorlevel 1 (
    echo WARNING: Some css files may not have been copied
)
echo css copied
echo.

REM Copy images directory (if exists)
echo Copying images directory...
if exist "%SOURCE_DIR%\images\*.*" (
    xcopy "%SOURCE_DIR%\images\*.*" "%TARGET_DIR%\images\" /Y /Q
    echo images copied
) else (
    echo No images to copy
)
echo.

REM Initialize logs directory
echo Initializing logs directory...
if exist "%SOURCE_DIR%\logs\README.md" (
    xcopy "%SOURCE_DIR%\logs\README.md" "%TARGET_DIR%\logs\" /Y /Q
)
echo logs initialized
echo.

REM Deploy Complete
echo ============================================
echo DEPLOY COMPLETED SUCCESSFULLY
echo ============================================
echo.
echo Target: %TARGET_DIR%
echo.
echo Deployed files:
echo  - PHP files (login.php, support_main.php, master_*.php, etc.)
echo  - includes\*.php
echo  - templates\*.php
echo  - js\*.js
echo  - css\*.css
echo.
echo Next Steps:
echo 1. Access test in browser:
echo    URL: http://lpg-nema/support-system/login.php
echo 2. Test login functionality
echo 3. Test master screens (product, category, content, template)
echo 4. Test input dialog and template selection
echo.

endlocal
pause
