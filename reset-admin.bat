@echo off
chcp 65001 >nul
title Multi Branch - Reset Super Admin
cd /d "%~dp0"
if not exist artisan ( echo [ERROR] artisan not found - this file must be inside D:\laragon\www\multibranch & pause & exit /b 1 )
echo Super Admin login will be reset to: admin@example.com / password
php artisan admin:reset admin@example.com --password=password --no-2fa
php artisan optimize:clear
echo.
echo DONE! Now login at /admin/login with admin@example.com / password
pause
