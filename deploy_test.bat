@echo off
chcp 932 >nul

echo ========================================
echo Support Report WEB Deploy to Test
echo ========================================
echo.

set SCRIPT_DIR=%~dp0
set SOURCE_DIR=%SCRIPT_DIR%04_製造
set TARGET_DIR=\\LPG-NEMA\C\inetpub\wwwroot\support-system

echo Script Dir: %SCRIPT_DIR%
echo Source: %SOURCE_DIR%
echo Target: %TARGET_DIR%
echo.

if not exist "%TARGET_DIR%\" (
    echo ERROR: Cannot connect to test environment
    pause
    exit /b 1
)
echo Connection OK
echo.

echo Creating directories...
if not exist "%TARGET_DIR%\includes\" mkdir "%TARGET_DIR%\includes"
if not exist "%TARGET_DIR%\templates\" mkdir "%TARGET_DIR%\templates"
if not exist "%TARGET_DIR%\js\" mkdir "%TARGET_DIR%\js"
if not exist "%TARGET_DIR%\css\" mkdir "%TARGET_DIR%\css"
if not exist "%TARGET_DIR%\logs\" mkdir "%TARGET_DIR%\logs"
if not exist "%TARGET_DIR%\images\" mkdir "%TARGET_DIR%\images"
echo Done
echo.

echo Copying PHP files...
xcopy "%SOURCE_DIR%\*.php" "%TARGET_DIR%\" /Y /Q
echo.

echo Copying includes...
xcopy "%SOURCE_DIR%\includes\*.*" "%TARGET_DIR%\includes\" /Y /Q
echo.

echo Copying templates...
xcopy "%SOURCE_DIR%\templates\*.*" "%TARGET_DIR%\templates\" /Y /Q
echo.

echo Copying js...
xcopy "%SOURCE_DIR%\js\*.js" "%TARGET_DIR%\js\" /Y /Q
echo.

echo Copying css...
xcopy "%SOURCE_DIR%\css\*.css" "%TARGET_DIR%\css\" /Y /Q
echo.

echo ============================================
echo DEPLOY COMPLETED
echo ============================================
echo URL: http://lpg-nema/support-system/login.php
echo.
pause
