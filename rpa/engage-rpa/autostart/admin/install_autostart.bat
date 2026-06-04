@echo off
setlocal

set "TASK_NAME=Engage RPA Report Download"
set "PROJECT_DIR=%~dp0..\.."
for %%I in ("%PROJECT_DIR%") do set "PROJECT_DIR=%%~fI"
set "VBS_PATH=%PROJECT_DIR%\run_hidden.vbs"

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
