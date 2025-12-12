@echo off
REM Run PowerShell deploy script
powershell -ExecutionPolicy Bypass -File "%~dp0deploy_to_test.ps1"
