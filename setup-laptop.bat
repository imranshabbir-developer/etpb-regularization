@echo off
REM One-shot setup for a fresh Windows / XAMPP laptop.
REM Double-click or run: setup-laptop.bat

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-laptop.ps1"
if errorlevel 1 (
  echo.
  echo Setup failed. See messages above.
  pause
  exit /b 1
)
pause
