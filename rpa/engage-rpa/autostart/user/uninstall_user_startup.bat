@echo off
setlocal

set "STARTUP_FILE=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\Engage RPA Report Download.vbs"

echo Removing user startup launcher...
if exist "%STARTUP_FILE%" del "%STARTUP_FILE%"

echo Done.
pause
