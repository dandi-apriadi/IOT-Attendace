@echo off
set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
set COMPOSER_PATH=C:\laragon\bin\composer\composer.phar

echo [1/5] Memastikan Direktori Sistem...
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"

echo [2/5] Menginstal Library (Composer)...
@echo PASTIKAN INTERNET AKTIF.
"%PHP_PATH%" "%COMPOSER_PATH%" install --no-interaction --no-progress
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal menginstal library. 
    echo Pastikan ekstensi 'zip' aktif di PHP Laragon (Menu Laragon -> PHP -> Extensions -> zip).
    pause
    exit /b %ERRORLEVEL%
)

echo [3/5] Membuat App Key...
"%PHP_PATH%" artisan key:generate

echo [4/5] Migrasi Database...
echo Pastikan MySQL aktif dan database 'absensi_iot' sudah dibuat di HeidiSQL/Laragon.
"%PHP_PATH%" artisan migrate --force

echo [5/5] Selesai! Menjalankan Server...
echo Buka browser di http://localhost:8000
"%PHP_PATH%" artisan serve
pause
