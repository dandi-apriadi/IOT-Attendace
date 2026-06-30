# Server Autostart Setup

Setup ini memastikan service penting langsung berjalan setelah server restart atau baru menyala.

## Service systemd yang wajib enabled

```bash
sudo systemctl enable apache2
sudo systemctl enable mysql
sudo systemctl enable ssh
sudo systemctl enable cron
sudo systemctl enable tailscaled
sudo systemctl enable iot-attendance-php-serve
```

Fungsi masing-masing:

- `apache2`: web server production untuk aplikasi Laravel di port `80`.
- `mysql`: database aplikasi.
- `ssh`: akses remote lewat port `22`.
- `cron`: menjalankan Laravel scheduler.
- `tailscaled`: akses remote lewat jaringan Tailscale, jika dipakai.
- `iot-attendance-php-serve`: Laravel development server di `127.0.0.1:8000`, dipertahankan untuk kompatibilitas tool/tunnel lokal.

## Laravel scheduler

File cron:

```text
/etc/cron.d/iot-attendance-laravel
```

Isi:

```cron
* * * * * www-data cd /home/teknik-komputer/IOT-Attendace && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler ini menjalankan task dari `routes/console.php`, termasuk:

- `backup:database-local --keep=7` setiap hari pukul `23:30`.
- `zkteco:pull` setiap menit.

## Catatan PHP server

Server production aplikasi ini memakai Apache dengan `DocumentRoot` ke:

```text
/home/teknik-komputer/IOT-Attendace/public
```

Proses `php -S 127.0.0.1:8000 -t public` adalah development server dan tidak diperlukan untuk akses domain production. Domain `elektropolimdo.com` dilayani oleh Apache.

Namun service berikut tetap dibuat agar port `8000` ikut hidup otomatis jika masih dipakai oleh tool lokal seperti tunnel:

```text
/etc/systemd/system/iot-attendance-php-serve.service
```

## Remote SSH dari jauh

Ada dua opsi:

1. Lewat Tailscale:

```bash
ssh teknik-komputer@100.122.92.78
```

2. Lewat internet publik:

Router/modem harus forward TCP `22` ke `192.168.0.108:22`.

Untuk web/domain, router/modem juga perlu forward:

- TCP `80` ke `192.168.0.108:80`
- TCP `443` ke `192.168.0.108:443`

## Verifikasi setelah reboot

```bash
systemctl status apache2 mysql ssh cron tailscaled iot-attendance-php-serve --no-pager
ss -ltnp | grep -E ':(22|80|443|3306|8000)\b'
curl -I -H 'Host: elektropolimdo.com' http://127.0.0.1
```
