<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peran Aplikasi (Application Role)
    |--------------------------------------------------------------------------
    |
    | Menentukan bagaimana aplikasi berkomunikasi dengan alat ZKTeco:
    |
    | - 'standalone' : Aplikasi berada satu jaringan dengan alat (LAN). Semua
    |                  operasi ZKTeco dijalankan langsung via UDP, dan command
    |                  terjadwal `zkteco:pull` aktif. Ini perilaku default agar
    |                  pemasangan lokal all-in-one tetap berjalan tanpa agent.
    |
    | - 'server'     : Aplikasi di-deploy di VPS (mis. elektropolimdo.com) dan
    |                  TIDAK bisa menjangkau alat di LAN. Operasi ZKTeco dari UI
    |                  diantrekan sebagai DeviceCommand; agent lokal yang akan
    |                  mengeksekusinya. Command `zkteco:pull` tidak dijadwalkan
    |                  (absensi masuk via push dari agent).
    |
    */

    'role' => env('APP_ROLE', 'standalone'),

    /*
    |--------------------------------------------------------------------------
    | Batas Waktu Dispatch Command (detik)
    |--------------------------------------------------------------------------
    |
    | Command yang sudah berstatus 'dispatched' namun tak kunjung dilaporkan
    | hasilnya oleh agent dianggap kedaluwarsa setelah durasi ini, lalu boleh
    | diambil ulang oleh agent berikutnya.
    |
    */

    'command_dispatch_ttl' => (int) env('AGENT_COMMAND_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Maksimum Retry Dispatch Command
    |--------------------------------------------------------------------------
    |
    | Jika agent mengambil command tetapi tidak pernah mengirim result, command
    | lama tidak boleh terus memblokir command baru. Setelah batas ini tercapai,
    | command akan ditandai failed agar antrean perangkat bisa lanjut.
    |
    */

    'command_max_dispatch_attempts' => (int) env('AGENT_COMMAND_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Auto Sync Biometrik ZKTeco
    |--------------------------------------------------------------------------
    |
    | Pada mode server, VPS akan mengantrekan pull_biometrics secara berkala
    | untuk alat ZKTeco yang agent-nya baru terlihat online. Hasil pull akan
    | dipakai untuk memperbarui database lalu dipush ke seluruh alat aktif.
    |
    */

    'biometric_auto_sync_enabled' => env('AGENT_BIOMETRIC_AUTO_SYNC_ENABLED', true),
    'biometric_auto_sync_online_minutes' => (int) env('AGENT_BIOMETRIC_AUTO_SYNC_ONLINE_MINUTES', 10),

];
