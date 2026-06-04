@echo off
setlocal

set "STARTUP_FILE=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\Master RPA Report Download.vbs"
set "OLD_STARTUP_FILE=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\Engage RPA Report Download.vbs"
set "STARTUP_DIR=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"

set "PROJECT_DIR=%~dp0..\.."
for %%I in ("%PROJECT_DIR%") do set "PROJECT_DIR=%%~fI"
set "VBS_PATH=%PROJECT_DIR%\run_scheduler.vbs"

echo Removing old user startup file...
if exist "%OLD_STARTUP_FILE%" del "%OLD_STARTUP_FILE%"

echo Removing old admin task if exists...
schtasks /Delete /TN "Engage RPA Report Download" /F >nul 2>&1

echo Installing user startup launcher...
if not exist "%STARTUP_DIR%" mkdir "%STARTUP_DIR%"
(
    echo Set shell = CreateObject("WScript.Shell"^)
    echo shell.Run "wscript.exe ""%VBS_PATH%""", 0, False
) > "%STARTUP_FILE%"

if not exist "%STARTUP_FILE%" (
    echo Failed to install user startup launcher.
    pause
    exit /b 1
)

echo Starting now...
wscript.exe "%VBS_PATH%"

echo Done. Startup file:
echo "%STARTUP_FILE%"
echo Logs:
echo %PROJECT_DIR%\logs\scheduler.log
pause
