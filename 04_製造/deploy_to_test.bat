@echo off
chcp 932 >nul
REM ============================================
REM Support Report WEB - Test Environment Deploy Script
REM Created: 2025-11-26
REM ============================================

echo ========================================
echo Support Report WEB Deploy to Test
echo ========================================
echo.

REM Get the directory where this batch file is located
set SCRIPT_DIR=%~dp0
set SOURCE_DIR=%SCRIPT_DIR%
set TARGET_DIR=\\LPG-NEMA\C\inetpub\wwwroot\support-system

echo Source: %SOURCE_DIR%
echo Target: %TARGET_DIR%
echo.

REM Connection Check
echo Checking connection to test environment...
if not exist "%TARGET_DIR%" (
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
if not exist "%TARGET_DIR%\includes" mkdir "%TARGET_DIR%\includes"
if not exist "%TARGET_DIR%\templates" mkdir "%TARGET_DIR%\templates"
if not exist "%TARGET_DIR%\js" mkdir "%TARGET_DIR%\js"
if not exist "%TARGET_DIR%\css" mkdir "%TARGET_DIR%\css"
if not exist "%TARGET_DIR%\logs" mkdir "%TARGET_DIR%\logs"
if not exist "%TARGET_DIR%\images" mkdir "%TARGET_DIR%\images"
echo Directories created
echo.

REM Copy PHP files (root)
echo Copying PHP files...
xcopy "%SOURCE_DIR%*.php" "%TARGET_DIR%\" /Y /Q
if errorlevel 1 (
    echo ERROR: Failed to copy PHP files
    pause
    exit /b 1
)
echo PHP files copied
echo.

REM Copy includes directory
echo Copying includes directory...
xcopy "%SOURCE_DIR%includes\*.*" "%TARGET_DIR%\includes\" /Y /Q
if errorlevel 1 (
    echo ERROR: Failed to copy includes
    pause
    exit /b 1
)
echo includes copied
echo.

REM Copy templates directory
echo Copying templates directory...
xcopy "%SOURCE_DIR%templates\*.*" "%TARGET_DIR%\templates\" /Y /Q
if errorlevel 1 (
    echo ERROR: Failed to copy templates
    pause
    exit /b 1
)
echo templates copied
echo.

REM Copy js directory
echo Copying js directory...
xcopy "%SOURCE_DIR%js\*.js" "%TARGET_DIR%\js\" /Y /Q
if errorlevel 1 (
    echo ERROR: Failed to copy js
    pause
    exit /b 1
)
echo js copied
echo.

REM Copy css directory
echo Copying css directory...
xcopy "%SOURCE_DIR%css\*.css" "%TARGET_DIR%\css\" /Y /Q
if errorlevel 1 (
    echo ERROR: Failed to copy css
    pause
    exit /b 1
)
echo css copied
echo.

REM Initialize logs directory
echo Initializing logs directory...
if exist "%SOURCE_DIR%logs\README.md" (
    xcopy "%SOURCE_DIR%logs\README.md" "%TARGET_DIR%\logs\" /Y /Q
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
echo Next Steps:
echo 1. Grant write permission to logs directory
echo    (IIS_IUSRS or IUSR needs write access)
echo 2. Verify IIS settings
echo 3. Access test in browser:
echo    URL: http://LPG-NEMA/support-system/login.php
echo.

pause
