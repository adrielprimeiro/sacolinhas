@echo off
cd /d "c:\serv\sacolinhas\tiktok-service"
:loop
"C:\Program Files\nodejs\node.exe" server.js
timeout /t 5 >nul
goto loop
