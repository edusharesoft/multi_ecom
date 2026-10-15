@echo off
chcp 65001 >nul
title Multi Branch E-commerce - Setup
cd /d "%~dp0"

echo ==== 1/6 Checking PHP (8.2+ required) ====
php -r "exit(version_compare(PHP_VERSION,'8.2.0','>=')?0:1);"
if errorlevel 1 (
  echo [ERROR] PHP 8.2 or newer is required. Laragon: Menu ^> PHP ^> Version ^> choose 8.2/8.3
  pause & exit /b 1
)
php -v | findstr /b PHP

echo ==== 2/6 Installing Composer packages (needs internet) ====
call composer install --no-interaction --prefer-dist
if errorlevel 1 ( echo [ERROR] composer install failed & pause & exit /b 1 )

echo ==== 3/6 App key ====
php artisan key:generate --force

echo ==== 4/6 Creating MySQL database ====
php setup\create-database.php
if errorlevel 1 ( pause & exit /b 1 )

echo ==== 5/6 Migrating + demo data ====
php artisan migrate --seed --force
if errorlevel 1 ( echo [ERROR] migration failed & pause & exit /b 1 )

echo ==== 6/6 Storage link + cache clear ====
php artisan storage:link
php artisan optimize:clear

echo.
echo ============================================================
echo  DONE!  Laragon: Menu ^> Apache ^> Reload  (or Stop/Start All)
echo  Website      : http://multibranch.test
echo  Super Admin  : http://multibranch.test/admin/login
echo                 admin@example.com / password
echo  Branch login : http://multibranch.test/login
echo                 barishal@example.com / password
echo  Demo store   : http://multibranch.test/barishal
echo  OTP (test)   : storage\logs\laravel.log
echo ============================================================
pause
