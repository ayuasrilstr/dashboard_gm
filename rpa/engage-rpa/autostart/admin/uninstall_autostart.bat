@echo off
setlocal

set "TASK_NAME=Engage RPA Report Download"

echo Removing autostart task: %TASK_NAME%
schtasks /Delete /TN "%TASK_NAME%" /F

pause
