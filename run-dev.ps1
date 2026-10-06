Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "              Starting LaraGym Ecosystem               " -ForegroundColor Yellow
Write-Host "========================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "1. Launching Laravel API on http://localhost:8000 ..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "php artisan serve --port=8000"

Write-Host "2. Launching Admin Panel on http://localhost:5173 ..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd resources\apps\admin; npm run dev -- --port 5173 --open"

Write-Host "3. Launching Member PWA on http://localhost:5174 ..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd resources\apps\member; npm run dev -- --port 5174 --open"

Write-Host ""
Write-Host "All services started successfully!" -ForegroundColor Cyan
Write-Host " - Admin Dashboard: http://localhost:5173" -ForegroundColor White
Write-Host " - Member PWA:      http://localhost:5174" -ForegroundColor White
Write-Host " - Laravel REST API: http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "Admin Credentials: admin@admin.com / password" -ForegroundColor Yellow
