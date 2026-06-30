# Dosen Fingerprint Room Access Design

## Tujuan

Dosen harus bisa membuka atau masuk ke semua ruangan yang memakai perangkat ZKTeco tanpa perlu didaftarkan sebagai mahasiswa. Untuk tahap ini, metode identitas yang dipakai hanya sidik jari. Dosen cukup enroll di satu alat utama, lalu sistem menarik template sidik jari dosen dan menyebarkannya ke semua perangkat ZKTeco aktif.

## Keputusan Utama

- Dosen memakai data dari tabel `users` dengan `role = dosen`.
- Mahasiswa tetap memakai data dari tabel `mahasiswa`.
- UID alat untuk dosen harus berada di range berbeda dari UID mahasiswa agar tidak bentrok.
- Dosen boleh masuk ke semua ruangan, sehingga fitur ini tidak memakai pembatasan jadwal, kelas, atau device tertentu.
- Template sidik jari dosen disimpan di database agar bisa disinkronkan ulang ke perangkat lain.

## Model Data

Tabel `users` perlu ditambah kolom:

- `zk_uid`: UID numerik yang dipakai di perangkat ZKTeco.
- `fingerprint_data`: template sidik jari dosen dari alat.
- `fingerprint_synced_at`: waktu terakhir template dosen berhasil ditarik atau diperbarui.

Nilai `zk_uid` dibuat deterministik dari `users.id`, misalnya `50000 + users.id`. Range ini menjaga data dosen tidak bertabrakan dengan UID mahasiswa yang sekarang memakai `mahasiswa.id`.

## Alur Enroll Dosen

1. Admin membuat atau memilih akun dosen di master user.
2. Sistem memastikan dosen punya `zk_uid`.
3. Admin mengirim data dosen ke alat utama dengan `setUser()`.
4. Dosen enroll sidik jari langsung di alat utama.
5. Admin menjalankan pull biometrik.
6. Sistem membaca template dengan `getFingerprint(zk_uid)`.
7. Sistem menyimpan template ke `users.fingerprint_data`.
8. Admin menjalankan sync users ke semua alat.
9. Agent menjalankan `setUser()` dan `setFingerprint()` untuk dosen di setiap alat ZKTeco.

## Alur Sinkronisasi Semua User

Payload `push_all_users` perlu berisi dua tipe identitas:

- Mahasiswa: `uid = mahasiswa.id`, `userid = mahasiswa.nim`, `name = mahasiswa.nama`.
- Dosen: `uid = users.zk_uid`, `userid = users.zk_uid`, `name = users.name`, disertai fingerprint template jika tersedia.

Agent harus memproses setiap user dengan urutan:

1. Jalankan `setUser()`.
2. Jika ada `fingerprint_data`, jalankan `setFingerprint(uid, fingerprint_data)`.
3. Laporkan jumlah user berhasil, jumlah fingerprint berhasil, dan error ringkas bila ada.

## Perubahan Komponen

- `DeviceCommandService`: membangun payload gabungan mahasiswa dan dosen.
- `ZktecoService`: mendukung push user generic dan push fingerprint dosen.
- `tools/agent/agent.php`: menerima payload fingerprint dan menerapkannya ke alat.
- `User` model dan migration: menyimpan UID serta template biometrik dosen.
- Controller device atau user: menyediakan aksi untuk push/pull biometrik dosen sesuai pola command agent yang sudah ada.

## Error Handling

- Jika dosen belum punya template sidik jari, sistem tetap boleh push data user dosen, tetapi menandai fingerprint sebagai belum tersedia.
- Jika `setFingerprint()` gagal di satu alat, proses sinkronisasi user lain tetap berjalan dan error dikembalikan di result command.
- Jika UID dosen bentrok dengan user alat yang sudah ada, sistem harus memakai UID deterministik dari database dan tidak memakai UID acak.
- Jika template sidik jari kosong saat pull, database tidak diubah dan admin diberi pesan bahwa dosen belum enroll di alat.

## Pengujian

Implementasi harus memakai test-first untuk perilaku inti:

- Payload `push_all_users` berisi mahasiswa dan dosen aktif.
- UID dosen memakai range aman dan stabil.
- Dosen tanpa fingerprint tetap masuk payload user, tetapi tanpa template.
- Dosen dengan fingerprint mengirim template ke payload agent.
- Agent memanggil `setUser()` sebelum `setFingerprint()`.
- Pull biometrik dapat menyimpan template dosen ke tabel `users`.

## Di Luar Scope Tahap Ini

- Pembatasan dosen berdasarkan jadwal atau ruangan tertentu.
- Metode kartu RFID, wajah, PIN, atau barcode untuk dosen.
- UI enrollment dosen yang kompleks.
- Enkripsi template fingerprint di level aplikasi. Data tetap harus diperlakukan sensitif dan tidak ditampilkan di UI.
