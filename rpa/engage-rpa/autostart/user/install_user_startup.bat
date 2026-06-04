@echo off
setlocal

set "STARTUP_FILE=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\Engage RPA Report Download.vbs"
set "PROJECT_DIR=%~dp0..\.."
for %%I in ("%PROJECT_DIR%") do set "PROJECT_DIR=%%~fI"
set "VBS_PATH=%PROJECT_DIR%\run_hidden.vbs"

echo Installing user startup launcher...
(
    echo Set shell = CreateObject("WScript.Shell"^)
    echo shell.Run "wscript.exe ""%VBS_PATH%""", 0, False
) > "%STARTUP_FILE%"

if errorlevel 1 (
    echo Failed to install user startup launcher.
    pause
    exit /b 1
)

echo Starting now...
wscript.exe "%VBS_PATH%"

echo Done. Startup file:
echo %STARTUP_FILE%
echo Logs:
echo %PROJECT_DIR%\logs\scheduler.log
pause
