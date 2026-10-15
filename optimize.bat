@echo off
chcp 65001 >nul
title Multi Branch - Speed up (production mode)
cd /d "%~dp0"
if not exist artisan ( echo [ERROR] artisan not found - this file must be inside D:\laragon\www\multibranch & pause & exit /b 1 )
echo ==== 1/3 Clear old caches ====
php artisan optimize:clear
echo ==== 2/3 Build config + route + view + event cache ====
php artisan optimize
if errorlevel 1 (
    echo.
    echo [WARNING] cache build failed - take a screenshot of the text above and send it.
    echo The site keeps working in normal mode.
    php artisan optimize:clear
    pause
    exit /b 1
)
echo ==== 3/3 Optimised class autoloader ====
where composer >nul 2>nul && composer dump-autoload -o --no-interaction
echo.
echo DONE! The site is now in fast mode.
echo NOTE: after editing .env or any file, run update.bat (normal mode) or this file again.
pause
