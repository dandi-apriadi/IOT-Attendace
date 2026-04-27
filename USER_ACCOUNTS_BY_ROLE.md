# Akun User per Role

Dokumen ini merangkum akun default yang disediakan oleh seeder proyek.

## Role `admin`

| Nama | Email | Password |
| --- | --- | --- |
| Super Admin | admin@gmail.com | 123 |

## Role `dosen`

| Nama | Email | Password |
| --- | --- | --- |
| Dosen Utama | dosen@gmail.com | 123 |

## Catatan

1. Schema `users` pada project ini saat ini hanya mendukung role `admin` dan `dosen`.
2. Akun di atas dibuat oleh seeder `AdminUserSeeder` dan `TeacherUserSeeder`, lalu dipanggil dari `DatabaseSeeder`.
3. Jika menjalankan `php artisan db:seed`, akun ini akan dibuat atau diperbarui otomatis.