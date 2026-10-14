@echo off
setlocal enabledelayedexpansion
REM ngrok-start.bat - Start a public ngrok tunnel for ClassSense
cd /d "C:\xampp\htdocs\ClassSense"

echo Checking Apache on http://localhost ...
curl -s -o NUL --max-time 5 http://localhost
if errorlevel 1 (
  echo [ERROR] Apache is not responding. Open the XAMPP Control Panel and Start Apache first.
  pause
  exit /b 1
)
echo [OK] Apache is running.

REM --- Locate ngrok.exe (full path first, then PATH, skipping the Store alias) ---
set "NGROK_EXE="
if exist "C:\Users\Public\ngrok\ngrok.exe" set "NGROK_EXE=C:\Users\Public\ngrok\ngrok.exe"
if not defined NGROK_EXE (
  for /f "delims=" %%P in ('where ngrok 2^>nul') do (
    echo %%P | findstr /i "WindowsApps" >nul
    if errorlevel 1 if not defined NGROK_EXE set "NGROK_EXE=%%P"
  )
)
if not defined NGROK_EXE (
  echo [ERROR] A usable ngrok.exe was not found.
  echo   Expected at C:\Users\Public\ngrok\ngrok.exe
  echo   Install the standalone ngrok from ngrok.com/download ^(the
  echo   Microsoft Store version cannot be launched by this script^).
  pause
  exit /b 1
)

REM --- If ngrok is already running (local API on port 4040), reuse it ---
curl -s -o NUL --max-time 2 http://127.0.0.1:4040/api/tunnels
if not errorlevel 1 (
  echo [INFO] ngrok is already running - grabbing its URL.
  goto :grab_url
)

echo Starting ngrok in a minimized window...
start "ClassSense ngrok" /min cmd /c ""%NGROK_EXE%" http 80 --url https://wriggle-traps-hankie.ngrok-free.dev"

:grab_url
set "TUNNEL_URL="
for /l %%i in (1,1,15) do (
  for /f "usebackq delims=" %%U in (`powershell -NoProfile -Command "try { $t = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2; if ($t.tunnels.Count -gt 0 -and $t.tunnels[0].public_url) { $t.tunnels[0].public_url } } catch { }"`) do set "TUNNEL_URL=%%U"
  if defined TUNNEL_URL goto :got_url
  timeout /t 2 /nobreak >nul
)

:no_url
echo.
echo [ERROR] ngrok did not produce a public URL.
echo   The minimized ngrok window shows the reason. Common causes:
echo     - the ngrok authtoken is not configured
echo     - this free account already has another ngrok running
echo       ^(only one ngrok agent at a time on a free account^)
echo     - no internet connection
pause
exit /b 1

:got_url
if not defined TUNNEL_URL goto :no_url
echo %TUNNEL_URL% | clip
echo.
echo ============================================================
echo  Tunnel URL: %TUNNEL_URL%
echo  URL copied to your clipboard - just paste it anywhere ^(Ctrl+V^).
echo.
echo  Website:   %TUNNEL_URL%/ClassSense/
echo  Mobile app: paste %TUNNEL_URL% in the Configure Server dialog
echo             ^(URL is different every start - always use the latest^)
echo.
echo  The ngrok window is minimized - close it to stop the tunnel.
echo ============================================================
echo.

start "" "%TUNNEL_URL%/ClassSense/"

pause