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

];
