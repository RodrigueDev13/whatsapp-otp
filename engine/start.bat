@echo off
REM Runs the WhatsApp engine, automatically restarting it if it crashes.
REM Close this window (or Ctrl+C, then N) to stop it for good.
cd /d "%~dp0"

:loop
echo [%date% %time%] starting whatsapp-otp engine...
node src\server.js
echo [%date% %time%] engine exited (code %errorlevel%) - restarting in 3s... (Ctrl+C to stop)
timeout /t 3 /nobreak >nul
goto loop
