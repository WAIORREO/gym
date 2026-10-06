@echo off
title LaraGym Development Launcher
echo ========================================================
echo               Starting LaraGym Ecosystem
echo ========================================================
echo.
echo 1. Launching Laravel API on http://localhost:8000 ...
start "LaraGym - Laravel API (Port 8000)" cmd /k "php artisan serve --port=8000"

echo 2. Launching Admin Panel on http://localhost:5173 ...
start "LaraGym - Admin Panel (Port 5173)" cmd /k "cd resources\apps\admin && npm run dev -- --port 5173 --open"

echo 3. Launching Member PWA on http://localhost:5174 ...
start "LaraGym - Member PWA (Port 5174)" cmd /k "cd resources\apps\member && npm run dev -- --port 5174 --open"

echo.
echo All services launched!
echo Admin Panel: http://localhost:5173
echo Member PWA:  http://localhost:5174
echo Laravel API: http://localhost:8000
echo.
pause
