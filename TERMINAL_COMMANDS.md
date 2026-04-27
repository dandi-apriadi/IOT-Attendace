# Perintah Terminal Project

Dokumen ini berisi daftar perintah untuk menjalankan proyek Laravel IoT-Attendance di beberapa terminal.

## Terminal 0 - Install Vendor dan Komponen Dasar

```bash
composer install
composer dump-autoload
```

Jika file environment belum ada, jalankan juga:

```bash
copy .env.example .env
php artisan key:generate
```

## Terminal 1 - Jalankan Server Aplikasi

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

## Terminal 2 - Jalankan Migrasi Database

```bash
php artisan migrate
```

## Terminal 3 - Seed Data Awal

```bash
php artisan db:seed
```

## Terminal 4 - Jalankan Queue Worker

Jika ada job background seperti notifikasi, sync, atau proses antrian lain:

```bash
php artisan queue:work
```

## Terminal 5 - Bersihkan Cache Konfigurasi Jika Diperlukan

```bash
php artisan optimize:clear
```

## Terminal 6 - Jalankan Test

```bash
php artisan test
```

## Catatan

1. Pastikan file `.env` sudah ada dan database MySQL/MariaDB aktif.
2. Jika database belum siap, jalankan `php artisan migrate` sebelum `php artisan db:seed`.
3. Untuk proyek ini, command utama yang wajib dijalankan biasanya hanya `serve`, `migrate`, dan `db:seed`.
4. Proyek ini tidak memiliki `package.json`, jadi tidak memerlukan `npm install` untuk komponen frontend.