@echo off
setlocal

set "TASK_NAME=Master RPA Report Download"
set "OLD_TASK_NAME=Engage RPA Report Download"
set "PROJECT_DIR=%~dp0..\.."
for %%I in ("%PROJECT_DIR%") do set "PROJECT_DIR=%%~fI"
set "VBS_PATH=%PROJECT_DIR%\run_scheduler.vbs"

echo Removing old autostart task if exists: %OLD_TASK_NAME%
schtasks /Delete /TN "%OLD_TASK_NAME%" /F >nul 2>&1

echo Removing old user startup file if exists...
set "OLD_STARTUP_FILE=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\Engage RPA Report Download.vbs"
if exist "%OLD_STARTUP_FILE%" del "%OLD_STARTUP_FILE%"

echo Installing autostart task: %TASK_NAME%
schtasks /Create /TN "%TASK_NAME%" /TR "wscript.exe \"%VBS_PATH%\"" /SC ONLOGON /F

if errorlevel 1 (
    echo Failed to install autostart task.
    pause
    exit /b 1
)

echo Starting task now...
schtasks /Run /TN "%TASK_NAME%"

echo Done. Logs:
echo %PROJECT_DIR%\logs\scheduler.log
pause
