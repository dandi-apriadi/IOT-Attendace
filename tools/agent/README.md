# Agent Relay ZKTeco

Jembatan antara alat ZKTeco (mis. Solution X609) di jaringan lokal dan server
`elektropolimdo.com`. Karena VPS tidak bisa menjangkau alat di LAN, agent ini
berjalan di **server lokal on-site** dan:

1. Menarik absensi dari alat (UDP) lalu **push** ke server (HTTPS).
2. Menarik **perintah** dari server (Sync Users, Sync Waktu, Hapus User, dll.)
   lalu mengeksekusinya di alat dan melaporkan hasilnya.

Agent bersifat **stateless** — tidak punya database. Semua data dibawa lewat
payload perintah / respons HTTP.

## Prasyarat

- PHP 8.1+ dengan ekstensi `sockets` aktif (untuk UDP ke alat).
- Composer.
- Server lokal satu jaringan dengan alat (bisa ping `DEVICE_IP`).

## Pemasangan

```bash
cd tools/agent
composer install
cp .env.example .env
# edit .env sesuai data perangkat
```

Isi `.env`:

| Variabel       | Keterangan                                                        |
|----------------|-------------------------------------------------------------------|
| `SERVER_URL`   | URL server, mis. `https://elektropolimdo.com` (tanpa slash akhir) |
| `DEVICE_ID`    | Kolom `device_id` perangkat di DB server                          |
| `DEVICE_TOKEN` | Token mentah perangkat (di server tersimpan sebagai hash SHA-256) |
| `DEVICE_IP`    | IP alat di LAN, mis. `192.168.0.10`                               |
| `DEVICE_PORT`  | Port alat, default `4370`                                         |
| `POLL_LIMIT`   | Maksimum perintah diproses per siklus                             |
| `HTTP_TIMEOUT` | Timeout HTTP ke server (detik)                                    |

> **Token:** di server, buat/edit perangkat bertipe ZKTeco dan isi token. Server
> menyimpan SHA-256-nya; agent mengirim token mentah yang sama.

## Menjalankan

Sekali jalan (cocok untuk penjadwal OS):

```bash
php agent.php
```

Loop terus-menerus (jeda 60 detik / sesuai argumen):

```bash
php agent.php --loop
php agent.php --loop=30
```

## Penjadwalan

### Windows (Task Scheduler)

Buat task yang menjalankan tiap 1 menit:

```
Program/script : C:\path\to\php.exe
Arguments      : C:\laragon\www\IOT-Attendace\tools\agent\agent.php
Start in       : C:\laragon\www\IOT-Attendace\tools\agent
```

Atau jalankan satu proses loop saat startup: `php agent.php --loop=30`.

### Linux (cron)

```cron
* * * * * cd /path/tools/agent && /usr/bin/php agent.php >> agent.log 2>&1
```

Atau jalankan sebagai service (systemd) dengan `php agent.php --loop=30`.

## Catatan

- Koneksi UDP ke alat kadang flaky (Wi-Fi) — agent sudah retry 3x per siklus.
- Dedup absensi ditangani server; aman jika agent mengirim ulang record yang sama.
- Perintah yang gagal dieksekusi dilaporkan `failed` ke server beserta pesan
  error, dan bisa diantre ulang dari UI admin.
