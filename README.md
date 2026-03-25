# IoT-Attendance System (Sistem Absensi Terintegrasi)

Sistem absensi otomatis berbasis IoT dan Framework Laravel yang mendukung berbagai metode identifikasi (RFID, Fingerprint, Barcode, dan Face Recognition). Dirancang untuk transparansi dan efisiensi kehadiran mahasiswa di lingkungan kampus.

---

## 🚀 Fitur Utama

- **Multi-Identification Support:** Fleksibilitas input menggunakan Kartu RFID, Sidik Jari, Scan Barcode, atau Pengenalan Wajah.
- **Integrasi Mata Kuliah (Courses):** Absensi tercatat berdasarkan jadwal sesi kuliah yang sedang aktif secara otomatis.
- **Monitoring Real-time:** Dashboard yang menampilkan siapa saja yang sedang "tapping" secara langsung.
- **Statistik & Rekapitulasi:** Visualisasi kehadiran harian/mingguan dan rekap data untuk laporan dosen.
- **IoT-Ready API:** Endpoint REST API yang ringan guna mempermudah integrasi dengan perangkat ESP32/NodeMCU.

---

## 🛠️ Tech Stack

*   **Backend:** PHP 8.x + Laravel
*   **Database:** MySQL / MariaDB
*   **IoT Communication:** REST API (JSON Payload)
*   **Design System:** Stitch (Google Design Standard)

---

## ⚙️ Cara Instalasi (Development)

1.  **Clone Repository:**
    ```bash
    git clone https://github.com/dandi-apriadi/IOT-Attendace.git
    cd IOT-Attendace
    ```

2.  **Install Dependencies:**
    *(Pastikan sudah menginstal [Laragon](https://laragon.org/) atau Composer)*
    ```bash
    composer install
    ```

3.  **Setup Environment:**
    Salin `.env.example` ke `.env` dan sesuaikan pengaturan database.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```

5.  **Seed Default IoT Devices:**
    ```bash
    php artisan db:seed
    ```

6.  **Generate Device Token Baru (Opsional):**
    ```bash
    php artisan device:token ROOM_201 --name="ESP32 Room 201" --store
    ```

7.  **Run Dev Server:**
    ```bash
    php artisan serve
    ```

---

## 📡 API Endpoint (IoT)

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/absensi` | Mengirim data absensi dari perangkat IoT. |

### Header Keamanan API (Wajib)

- `X-Device-Token`: Token autentikasi perangkat.
- `X-Device-Id`: ID perangkat unik (mode per-device dari tabel `devices`).

Jika `X-Device-Id` tidak dikirim, sistem akan fallback ke token global `.env` (`DEVICE_API_TOKEN`) untuk kompatibilitas.

**Contoh Payload JSON:**
```json
{
  "identifier": "E2 80 8A 12",
  "type": "RFID",
  "device_id": "ROOM_101"
}
```

---

## 📄 Lisensi
[MIT License](LICENSE) - Projek ini dikembangkan untuk kebutuhan akademik Politeknik Negeri Manado.
