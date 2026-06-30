# Setup Domain elektropolimdo.com

Dokumen ini mencatat setup production untuk menghubungkan aplikasi Laravel IoT Attendance ke domain `elektropolimdo.com`.

## Kondisi server saat ini

- Aplikasi: Laravel 11 / PHP 8.2+
- Web server: Apache
- Project path: `/home/teknik-komputer/IOT-Attendace`
- Document root wajib: `/home/teknik-komputer/IOT-Attendace/public`
- IP lokal server: `192.168.0.108`
- IP Tailscale server: `100.122.92.78`
- IP publik internet saat dicek: `36.91.191.53`

Jangan arahkan DNS publik ke `100.122.92.78`, karena itu IP Tailscale/private dan tidak bisa diakses umum lewat internet.

## Konfigurasi Laravel

Nilai penting di `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://elektropolimdo.com
SESSION_DOMAIN=.elektropolimdo.com
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## Konfigurasi Apache

VirtualHost HTTP:

```apache
<VirtualHost *:80>
    ServerName elektropolimdo.com
    ServerAlias www.elektropolimdo.com
    ServerAdmin webmaster@elektropolimdo.com
    DocumentRoot /home/teknik-komputer/IOT-Attendace/public

    <Directory /home/teknik-komputer/IOT-Attendace/public>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/iot-attendance-error.log
    CustomLog /var/log/apache2/iot-attendance-access.log combined
</VirtualHost>
```

Aktifkan modul dan reload Apache:

```bash
sudo a2enmod rewrite
sudo apache2ctl configtest
sudo systemctl reload apache2
```

File `public/.htaccess` juga harus ada karena dipakai untuk URL routing Laravel. Di server ini file tersebut juga menonaktifkan `pcre.jit` untuk Apache PHP agar request web tidak error pada environment yang membatasi alokasi JIT memory.

## SSL HTTPS

Setelah DNS sudah mengarah ke server dan port `80` bisa diakses dari internet, buat SSL Let's Encrypt:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d elektropolimdo.com -d www.elektropolimdo.com
```

Pilih opsi redirect HTTP ke HTTPS saat diminta Certbot.

## Setting di Hostinger Dashboard

Di Hostinger hPanel, buka domain `elektropolimdo.com`, lalu masuk ke menu DNS/Nameservers atau DNS Zone Editor.

Tambahkan atau edit record berikut:

| Type | Name | Points to | TTL |
| --- | --- | --- | --- |
| A | `@` | `36.91.191.53` | `14400` atau default |
| A | `www` | `36.91.191.53` | `14400` atau default |

Hapus record lama yang konflik:

- A record `@` yang mengarah ke IP lain.
- A record `www` yang mengarah ke IP lain.
- CNAME `www` jika ingin memakai A record langsung untuk `www`.
- AAAA record untuk `@` atau `www` jika server belum punya IPv6 publik.

Hostinger menyebut perubahan DNS bisa membutuhkan waktu propagasi sampai 24 jam.

## Catatan jaringan

Karena server ini berada di jaringan lokal (`192.168.0.108`), router/modem kampus harus meneruskan traffic internet ke server:

- TCP `80` -> `192.168.0.108:80`
- TCP `443` -> `192.168.0.108:443`

Jika IP publik `36.91.191.53` berubah secara berkala dari ISP, DNS di Hostinger harus diperbarui lagi atau gunakan layanan DDNS.

## Verifikasi

Gunakan perintah berikut:

```bash
dig +short elektropolimdo.com
dig +short www.elektropolimdo.com
curl -I http://elektropolimdo.com
curl -I https://elektropolimdo.com
php artisan about
```
