@echo off
REM auto-push.bat - Commit and push ClassSense to GitHub
cd /d "C:\xampp\htdocs\ClassSense"

REM Check if a commit message was provided
if "%1"=="" (
  set msg=Auto-update %DATE% %TIME%
) else (
  set msg=%*
)

git add -A
git commit -m "%msg%"
git pull origin main --rebase
git push origin main

pause
