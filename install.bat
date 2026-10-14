@echo off
setlocal enabledelayedexpansion
title ClassSense Installer
cd /d "%~dp0"

set "HASWARN=0"
set "HASFAIL=0"

echo.
echo ============================================================
echo   ClassSense - one-click install
echo ============================================================
echo.

REM ---------- 1. Locate XAMPP ----------
set "XAMPP=C:\xampp"
if not exist "%XAMPP%\php\php.exe" (
    set /p XAMPP=Enter your XAMPP folder [C:\xampp]: 
    if not defined XAMPP set "XAMPP=C:\xampp"
    if not exist "%XAMPP%\php\php.exe" (
        echo [ERROR] php.exe not found in %XAMPP%\php. Check the XAMPP install path.
        pause
        exit /b 1
    )
)
set "DEST=%XAMPP%\htdocs\ClassSense"
set "STAT1=OK (%XAMPP%)"
echo [1/7] XAMPP found: %XAMPP%
call :waitkey

REM ---------- 2. Copy project files ----------
echo [2/7] Copying project files to %DEST% ...
if not exist "%XAMPP%\htdocs" mkdir "%XAMPP%\htdocs"
robocopy "%~dp0ClassSense" "%DEST%" /E /R:1 /W:1 /NFL /NDL /NJH /NJS >nul
if %errorlevel% geq 8 (
    echo   [ERROR] Copying files failed.
    pause
    exit /b 1
)
set "STAT2=OK (files copied)"
echo       Files copied.
call :waitkey

REM ---------- 3. Firebase service account ----------
echo [3/7] Installing Firebase service account ...
set "SECRETS=C:\Users\Public\ClassSense-secrets"
if not exist "%SECRETS%" mkdir "%SECRETS%"
if exist "%~dp0firebase-service-account.json" (
    copy /y "%~dp0firebase-service-account.json" "%SECRETS%\firebase-service-account.json" >nul
    powershell -NoProfile -InputFormat None -ExecutionPolicy Bypass -File "%~dp0tools\set-fcm-path.ps1" -ConfigPath "%DEST%\api\config.json" -FcmPath "C:/Users/Public/ClassSense-secrets/firebase-service-account.json"
    set "STAT3=OK (installed)"
    echo       Service account installed.
) else (
    set "STAT3=WARNING - service account missing from package"
    set "HINT3=Push notifications will not work - the service-account file is missing from the package."
    set "HASWARN=1"
    echo       [WARN] Service account file missing from package.
)
call :waitkey

REM ---------- 4. PHP SQL Server driver ----------
echo [4/7] Checking PHP SQL Server driver ...
"%XAMPP%\php\php.exe" -m 2>nul | findstr /i "pdo_sqlsrv" >nul
if not errorlevel 1 (
    set "STAT4=OK (already installed)"
    echo       Driver already installed - skipping.
) else (
    echo       Driver missing - installing PHP SQL Server extension...
    powershell -NoProfile -InputFormat None -ExecutionPolicy Bypass -File "%~dp0tools\install-drivers.ps1" -XamppDir "%XAMPP%"
    set "DRVRC=!errorlevel!"
    set "STAT4=FAILED - driver did not load"
    if !DRVRC! equ 0 set "STAT4=OK (driver installed)"
    if !DRVRC! equ 2 set "STAT4=WARNING - needs admin: install ODBC driver"
    "%XAMPP%\php\php.exe" -m 2>nul | findstr /i "pdo_sqlsrv" >nul
    if not errorlevel 1 set "STAT4=OK (driver installed)"
)
echo !STAT4! | findstr /i "WARNING" >nul
if not errorlevel 1 (
    set "HASWARN=1"
    set "HINT4=Ask an administrator to install the Microsoft ODBC Driver for SQL Server, then run this installer again."
)
echo !STAT4! | findstr /i "FAILED" >nul
if not errorlevel 1 (
    set "HASFAIL=1"
    set "HINT4=The PHP SQL Server extension did not load - restart this PC and run this installer again."
)
call :waitkey

REM ---------- 5. Database setup (schema only) ----------
echo [5/7] Database setup ...
echo       SQL Server on this PC is detected automatically.
echo       Enter the SQL login YOU created for SQL Server:
echo.

set "SQLHOST=localhost"
set "SQLUSER=sa"
set "STAT5=FAILED - not completed"
set "DBERR="
set "HINT5="
set /a ATTEMPT=0
:DB_ASK
set /p SQLUSER=       Login name [%SQLUSER%]: 
set /p SQLPASS=       Password: 

:DB_RETRY
set /a ATTEMPT+=1
powershell -NoProfile -InputFormat None -ExecutionPolicy Bypass -File "%~dp0tools\patch-config.ps1" -ConfigPath "%DEST%\api\config.json" -DbHost "%SQLHOST%" -DbUser "%SQLUSER%" -DbPass "%SQLPASS%"
"%XAMPP%\php\php.exe" "%DEST%\api\setup.php" > "%TEMP%\cs-setup-out.txt" 2>&1
type "%TEMP%\cs-setup-out.txt" | findstr /i /c:"DATABASE READY" >nul
if not errorlevel 1 goto :DB_OK
type "%TEMP%\cs-setup-out.txt" | findstr /i /c:"PDO SQLSRV extension is missing" >nul
if not errorlevel 1 (
    echo.
    echo [ERROR] The PHP SQL Server driver did not load correctly.
    echo   If the installer above warned that the Microsoft ODBC Driver
    echo   for SQL Server is missing, ask an administrator to install it
    echo   ^(see the link above^), then run this installer again.
    echo   Otherwise restart this PC, then run this installer again.
    set "STAT5=FAILED - driver not loaded"
    set "HINT5=The SQL Server driver did not load - see step 4 for how to fix it."
    set "HASFAIL=1"
    goto :DB_DONE
)

echo.
echo       Connection failed. SQL Server said:
set "DBERR="
for /f "usebackq delims=" %%E in (`powershell -NoProfile -InputFormat None -Command "$m = Select-String -Path '%TEMP%\cs-setup-out.txt' -Pattern '-&gt;' | Where-Object { $_.Line -notlike '*class=*' } | Select-Object -First 1; if ($m) { [System.Net.WebUtility]::HtmlDecode($m.Line) }"`) do set "DBERR=%%E"
if not defined DBERR for /f "usebackq delims=" %%E in (`powershell -NoProfile -InputFormat None -Command "$m = [regex]::Match((Get-Content -Raw '%TEMP%\cs-setup-out.txt'), 'class=.err.>([^<]{3,})'); if ($m.Success) { [System.Net.WebUtility]::HtmlDecode($m.Groups[1].Value) }"`) do set "DBERR=%%E"
if defined DBERR (echo         !DBERR!) else (echo         - no details available)
echo.

if %ATTEMPT% geq 3 (
    echo [ERROR] Could not connect after 3 tries. Please check that:
    echo     - the SQL Server service is running
    echo       ^(SQL Server Configuration Manager -^> SQL Server Services^)
    echo     - SQL Server Authentication ^(mixed mode^) is enabled
    echo     - your login name and password are correct
    echo.
    echo   Note: too many wrong attempts can temporarily lock the login.
    echo   If you are sure the password is right, wait 10 minutes and
    echo   run this installer again.
    echo.
    echo   Or try once manually in a browser:
    echo     http://localhost/ClassSense/api/setup.php?user=%SQLUSER%^&pass=YOURPASSWORD
    set "STAT5=FAILED - could not connect"
    set "HINT5=Check your SQL login and password, that the SQL Server service is running, and that mixed-mode authentication is enabled."
    set "HASFAIL=1"
    goto :DB_DONE
)
echo       Please re-check your login name and password.
goto :DB_ASK

:DB_OK
set "STAT5=OK (database ready)"
set "DBERR="
set "HINT5="
set "CONNECTED="
for /f "usebackq delims=" %%H in (`powershell -NoProfile -InputFormat None -Command "$m = Select-String -Path '%TEMP%\cs-setup-out.txt' -Pattern 'instance: <code>([^<]+)</code>'; if ($m) { $m.Matches[0].Groups[1].Value }"`) do set "CONNECTED=%%H"
if defined CONNECTED (
    set "STAT5=OK (connected to !CONNECTED!)"
    echo       Connected to: !CONNECTED!
)
echo       Database ready - schema created, no data imported.
:DB_DONE
call :waitkey

REM ---------- 6. ngrok ----------
echo [6/7] Checking ngrok ...
set "STAT6=OK (already installed)"
curl -s -o nul --max-time 2 http://127.0.0.1:4040/api/tunnels >nul 2>&1
if not errorlevel 1 (
    set "STAT6=OK (already running)"
    echo       ngrok is already running - will reuse it.
    goto :NGROK_DONE
)
set "NGROK_REAL="
for /f "delims=" %%P in ('where ngrok 2^>nul') do (
    echo %%P | findstr /i "WindowsApps" >nul
    if errorlevel 1 if not defined NGROK_REAL set "NGROK_REAL=%%P"
)
if not defined NGROK_REAL if exist "C:\Users\Public\ngrok\ngrok.exe" set "NGROK_REAL=C:\Users\Public\ngrok\ngrok.exe"
if defined NGROK_REAL (
    echo       ngrok is already installed.
    goto :NGROK_DONE
)
echo       ngrok not found - downloading and installing to C:\Users\Public\ngrok ...
curl -s -L -o "%TEMP%\ngrok.zip" https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip
if not exist "%TEMP%\ngrok.zip" (
    set "STAT6=WARNING - download failed, install ngrok later"
    set "HINT6=ngrok could not be installed automatically - download it from ngrok.com and add it to PATH."
    set "HASWARN=1"
    echo       [WARN] Download failed - install ngrok manually later.
    goto :NGROK_DONE
)
if not exist "C:\Users\Public\ngrok" mkdir "C:\Users\Public\ngrok"
powershell -NoProfile -Command "Expand-Archive -Force -Path '%TEMP%\ngrok.zip' -DestinationPath 'C:\Users\Public\ngrok'"
if exist "C:\Users\Public\ngrok\ngrok.exe" (
    set "STAT6=OK (installed)"
    echo       ngrok installed to C:\Users\Public\ngrok - adding to PATH...
    powershell -NoProfile -Command "$p=[Environment]::GetEnvironmentVariable('Path','User'); if ($p -notlike '*ngrok*') { [Environment]::SetEnvironmentVariable('Path', $p + ';C:\Users\Public\ngrok', 'User') }"
) else (
    set "STAT6=WARNING - install failed, install ngrok later"
    set "HINT6=ngrok could not be installed automatically - download it from ngrok.com and add it to PATH."
    set "HASWARN=1"
)
if exist "%~dp0ngrok.yml" (
    if not exist "%LOCALAPPDATA%\ngrok" mkdir "%LOCALAPPDATA%\ngrok"
    copy /y "%~dp0ngrok.yml" "%LOCALAPPDATA%\ngrok\ngrok.yml" >nul
    echo       ngrok authtoken installed.
)
:NGROK_DONE
call :waitkey

REM ---------- 7. Local AI model (Ollama) ----------
echo [7/8] Local AI model (Ollama) ...
set "STAT7=SKIPPED"
set "HINT7="
set /p OLLAMA=       Install local AI model (Ollama)? [y/N]: 
if /i not "!OLLAMA!"=="y" goto :OLLAMA_DONE

set "OLLAMA_EXE=%LOCALAPPDATA%\Programs\Ollama\ollama.exe"
if not exist "%OLLAMA_EXE%" (
    echo       Ollama not found - downloading and installing...
    curl -s -L -o "%TEMP%\OllamaSetup.exe" https://ollama.com/download/OllamaSetup.exe
    if not exist "%TEMP%\OllamaSetup.exe" (
        set "STAT7=WARNING - download failed"
        set "HINT7=Ollama could not be downloaded - install it manually from ollama.com/download."
        set "HASWARN=1"
        goto :OLLAMA_DONE
    )
    start /wait "" "%TEMP%\OllamaSetup.exe"
    if not exist "%OLLAMA_EXE%" (
        set "STAT7=WARNING - install failed"
        set "HINT7=Ollama did not install - run the installer manually from ollama.com/download."
        set "HASWARN=1"
        goto :OLLAMA_DONE
    )
)

echo       Downloading local AI model (qwen2.5:3b, ~1.9 GB)...
"%OLLAMA_EXE%" pull qwen2.5:3b
if not errorlevel 1 (
    set "STAT7=OK (model ready)"
) else (
    set "STAT7=WARNING - model pull failed"
    set "HINT7=Ollama installed but the model did not download - run: ollama pull qwen2.5:3b"
    set "HASWARN=1"
)
:OLLAMA_DONE
call :waitkey

REM ---------- 8. Summary ----------
echo [8/8] Summary
echo.
set "OVERALL=GOOD TO GO"
if !HASWARN!==1 set "OVERALL=READY WITH WARNINGS - check the WARN items"
if !HASFAIL!==1 set "OVERALL=NOT READY - fix the FAILED items and run again"
echo ============================================================
echo   INSTALL SUMMARY
echo ============================================================
echo   [1/8] XAMPP ................ !STAT1!
echo   [2/8] Project files ........ !STAT2!
echo   [3/8] Firebase account ..... !STAT3!
if defined HINT3 echo         hint: !HINT3!
echo   [4/8] SQL Server driver .... !STAT4!
if defined HINT4 echo         hint: !HINT4!
echo   [5/8] Database ............. !STAT5!
if defined DBERR echo         error: !DBERR!
if defined HINT5 echo         hint: !HINT5!
echo   [6/8] ngrok ................ !STAT6!
if defined HINT6 echo         hint: !HINT6!
echo   [7/8] Local AI model ....... !STAT7!
if defined HINT7 echo         hint: !HINT7!
echo ------------------------------------------------------------
echo   Overall: !OVERALL!
echo.
if !HASFAIL!==0 (
    echo   Website:  http://localhost/ClassSense/
    echo   Login:    super_admin  /  123456
    echo   ngrok:    double-click ngrok-start.bat to open a public tunnel
    echo.
    start "" "http://localhost/ClassSense/"
) else (
    echo   Fix the FAILED step^(s^) above, then run this installer again.
    echo.
)
echo ============================================================
echo.
pause
exit /b 0

:waitkey
echo.
echo       Press any key to continue...
pause >nul
goto :eof