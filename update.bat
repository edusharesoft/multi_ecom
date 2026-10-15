@echo off
chcp 65001 >nul
title Multi Branch - Update database
cd /d "%~dp0"
echo Project folder: %cd%
if not exist artisan ( echo [ERROR] artisan not found - this file must be inside D:\laragon\www\multibranch & pause & exit /b 1 )
echo ==== 1/4 Database migration ====
php artisan migrate --force
if errorlevel 1 ( echo. & echo [ERROR] migration failed - take a screenshot of the text above and send it & pause & exit /b 1 )
echo ==== 2/4 Migration status (all should say Ran) ====
php artisan migrate:status
echo ==== 3/4 Demo data (plans, roles, brands, coupons) ====
php artisan db:seed --class=SaasSeeder --force
php artisan db:seed --class=EcommerceSeeder --force
rem dynamic robots.txt (per store / custom domain) replaces the static file
if exist public\robots.txt ren public\robots.txt robots.txt.bak
if not exist public\storage php artisan storage:link
echo ==== 4/4 Clear cache ====
php artisan optimize:clear
echo.
echo DONE! Refresh the browser with Ctrl+F5
pause
