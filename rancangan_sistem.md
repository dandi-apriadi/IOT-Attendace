# Rancangan Proyek: Website Absensi Kelas dengan Integrasi IoT

## 1. Analisis & Pendahuluan
Sistem absensi kelas ini (merujuk pada Politeknik Negeri Manado dalam konsep gambar) merupakan pengembangan dari sistem absensi manual menjadi otomatis menggunakan perangkat IoT. Sistem berbasis web framework **Laravel** dan menggunakan **MySQL** sebagai database utama. Sesuai dengan permintaan tambahan, sistem ini juga mengakomodasi entitas **Mata Kuliah** yang sebelumnya tidak ada di konsep awal, agar data presensi lebih terstruktur berdasarkan pembelajaran yang diikutinya.

## 2. Arsitektur Sistem
*   **Backend & Frontend Web:** Laravel (PHP) dengan arsitektur MVC (Model-View-Controller).
*   **Database Server:** MySQL.
*   **Hardware IoT:** Alat pembaca multi-input (RFID, Fingerprint, Kamera untuk Face Recognition, atau Barcode Scanner + NodeMCU/ESP32).
*   **Komunikasi IoT & Web:** HTTP POST Request berbasis REST API. Alat IoT akan mengirimkan *payload* berupa ID unik atau hasil komputasi wajah ke server.

## 3. Alur Sistem (System Flow) yang Diperbarui
Berdasarkan gambar awal ditambah dengan integrasi Mata Kuliah dan IoT:
1.  **Mengelola Data (Master Data):** Admin / Dosen login ke sistem dan memasukkan data Mahasiswa, termasuk pendaftaran **UID RFID**, **Template Sidik Jari**, atau **Foto Wajah** (untuk Face Recognition).
2.  **Pemilihan Mata Kuliah (Sesi Kuliah):** Dosen membuka sesi mata kuliah di web untuk kelas tertentu.
3.  **Proses Absensi IoT:** 
    *   Mahasiswa melakukan identifikasi (Tap Kartu, Scan Jari, atau Scan Wajah).
    *   Alat IOT mengirimkan data ke web server secara real-time via API.
    *   Sistem mencocokkan data input dengan database menggunakan algoritma pencocokan yang sesuai.
4.  **Tampilan & Rekap Absensi:** Admin/Dosen dapat melihat visualisasi data yang masuk ke halaman Tampilan Absensi secara otomatis, serta dapat mencetak Detail Rekap Kehadiran dan Hasil Laporan.

## 4. Rancangan Database Tambahan (Skema)
Penambahan Mata Kuliah mengharuskan kita memiliki tabel relasi/jadwal. Berikut rancangan utamanya:

1.  **users** (Untuk Web Server Halaman Login)
    *   `id`, `name`, `email`, `password`, `role` (admin/dosen), `timestamps`
2.  **kelas** (Data Kelas)
    *   `id`, `nama_kelas`, `timestamps`
3.  **mata_kuliah** (Penambahan Fitur)
    *   `id`, `kode_mk`, `nama_mk`, `sks`, `timestamps`
4.  **mahasiswa** 
    *   `id`, `nim`, `nama`, `kelas_id` (FK), `rfid_uid`, `fingerprint_data`, `face_model_data`, `barcode_id`, `timestamps`
5.  **jadwal** 
    *   `id`, `kelas_id`, `mata_kuliah_id`, `user_id` (Dosen), `hari`, `jam_mulai`, `jam_selesai`, `timestamps`
6.  **absensi** 
    *   `id`, `mahasiswa_id`, `jadwal_id`, `tanggal`, `waktu_tap`, `metode_absensi` (RFID/Finger/Face/Barcode), `status` (Hadir/Telat/Sakit/Izin/Alpa), `timestamps`

## 5. Fitur Utama pada Web Aplikasi
1.  **Autentikasi:** Login untuk Admin dan Dosen.
2.  **Manajemen Data Master (CRUD):** 
    *   Mahasiswa & Registrasi Identitas (RFID/Finger/Face/Barcode).
    *   Data Kelas, Mata Kuliah, dan Jadwal Pembelajaran.
3.  **Endpoint API IoT (`/api/absensi`):** API Controller yang mendukung berbagai tipe payload (UID, Hash Biometrik, atau Barcode Data).
4.  **Monitoring Kehadiran Hari ini (Real time):** Dosen dapat memantau siapa saja yang sudah masuk saat kelasnya berjalan.
5.  **Laporan / Rekapitulasi (Statistik):** Cetak rekap bulanan/per semester per Mahasiswa atau per Mata Kuliah.
