@echo off
setlocal

set "TASK_NAME=Master RPA Report Download"

echo Removing autostart task: %TASK_NAME%
schtasks /Delete /TN "%TASK_NAME%" /F

pause
