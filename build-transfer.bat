@echo off
setlocal enabledelayedexpansion
title ClassSense Transfer Builder
cd /d "%~dp0"

echo ============================================================
echo   ClassSense Transfer Builder
echo   Packages this project into a single self-extracting .exe
echo   Your files are never modified - only read and copied.
echo ============================================================
echo.

set /p VER=Version label [v1]: 
if not defined VER set "VER=v1"
for /l %%i in (1,1,10) do if "!VER:~-1!"==" " set "VER=!VER:~0,-1!"

set "OUT=%~dp0ClassSense-Transfer-%VER%.exe"
if exist "%OUT%" (
    set /p OK=  "%OUT%" already exists. Overwrite? [y/N]: 
    for /l %%i in (1,1,10) do if "!OK:~-1!"==" " set "OK=!OK:~0,-1!"
    if /i not "!OK!"=="y" (
        echo Cancelled.
        pause
        exit /b 1
    )
)

set "STAGE=%TEMP%\ClassSense-Transfer-pkg"
if exist "%STAGE%" rmdir /s /q "%STAGE%"
mkdir "%STAGE%" 2>nul

echo.
echo [1/5] Copying lean project files...
robocopy "%~dp0." "%STAGE%\ClassSense" /E /XD .git tools /XF db_repair.sql db_fix_attendance_grading.sql db_super_admin.sql db_upsert.sql auto-push.bat build-transfer.bat install.bat ClassSense-Transfer*.exe /NFL /NDL /NJH /NJS /R:1 /W:1 >nul
if %errorlevel% geq 8 (
    echo   [ERROR] File copy failed.
    pause
    exit /b 1
)

echo [2/5] Adding installer payload...
copy /y "%~dp0install.bat" "%STAGE%\install.bat" >nul
mkdir "%STAGE%\tools" 2>nul
copy /y "%~dp0tools\patch-config.ps1" "%STAGE%\tools\" >nul
copy /y "%~dp0tools\install-drivers.ps1" "%STAGE%\tools\" >nul
copy /y "%~dp0tools\set-fcm-path.ps1" "%STAGE%\tools\" >nul

echo [3/5] Packaging secrets...
if exist "C:\ClassSense-secrets\firebase-service-account.json" (
    copy /y "C:\ClassSense-secrets\firebase-service-account.json" "%STAGE%\firebase-service-account.json" >nul
    echo   - Firebase service account: included
) else (
    echo   [WARN] firebase-service-account.json not found in C:\ClassSense-secrets - notifications will need manual setup on the other device.
)

set "NGR="
for /d %%d in ("%LOCALAPPDATA%\Packages\ngrok*") do (
    if exist "%%d\LocalCache\Local\ngrok\ngrok.yml" set "NGR=%%d\LocalCache\Local\ngrok\ngrok.yml"
)
if defined NGR (
    copy /y "!NGR!" "%STAGE%\ngrok.yml" >nul
    echo   - ngrok authtoken: included
) else (
    echo   [WARN] ngrok authtoken not found - the other device will need to run "ngrok config add-authtoken" manually.
)

echo [4/5] Writing SFX options...
> "%TEMP%\cs-sfx-options.txt" (
    echo Title=ClassSense Transfer
    echo Path=%TEMP%\ClassSense-Transfer-Run
    echo Setup=install.bat
    echo Silent=1
    echo Overwrite=1
)

echo [5/5] Building "%OUT%"...
if exist "%OUT%" del "%OUT%" >nul 2>&1
if exist "C:\Program Files\WinRAR\rar.exe" (
    "C:\Program Files\WinRAR\rar.exe" a -ep1 -r -sfx -z"%TEMP%\cs-sfx-options.txt" -idq "%OUT%" "%STAGE%\*"
) else (
    echo   [ERROR] rar.exe not found at "C:\Program Files\WinRAR\rar.exe"
    pause
    exit /b 1
)
if not exist "%OUT%" (
    echo   [ERROR] Build failed - is WinRAR installed at "C:\Program Files\WinRAR\WinRAR.exe"?
    pause
    exit /b 1
)

for %%f in ("%OUT%") do set "SZ=%%~zf"
echo.
echo ============================================================
echo   Done!  ClassSense-Transfer-%VER%.exe  (%SZ% bytes)
echo   Location: %OUT%
echo.
echo   Send this ONE file to the other device. They double-click
echo   it and everything installs automatically.
echo ============================================================
pause