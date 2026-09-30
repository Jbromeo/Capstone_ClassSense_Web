@echo off
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

REM If ngrok is already running (local API on port 4040), reuse it
curl -s -o NUL --max-time 2 http://127.0.0.1:4040/api/tunnels
if not errorlevel 1 (
  echo [INFO] ngrok is already running - grabbing its URL.
  goto :grab_url
)

echo Starting ngrok in a minimized window...
start "ClassSense ngrok" /min cmd /c "ngrok http 80"

:grab_url
REM Wait for the tunnel, then read the public URL from ngrok's local API
set "TUNNEL_URL="
for /l %%i in (1,1,10) do (
  for /f "usebackq delims=" %%U in (`powershell -NoProfile -Command "$t = Invoke-RestMethod -Uri 'http://127.0.0.1:4040/api/tunnels' -TimeoutSec 2; if ($t.tunnels.Count -gt 0) { $t.tunnels[0].public_url } else { '' }"`) do set "TUNNEL_URL=%%U"
  if defined TUNNEL_URL goto :got_url
  timeout /t 2 /nobreak >nul
)
echo [ERROR] Could not read the ngrok URL. Check the minimized ngrok window.
pause
exit /b 1

:got_url
REM Copy the URL to the clipboard and open the site in the browser
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