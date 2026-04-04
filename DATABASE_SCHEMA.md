# 📊 Database Schema — IoT Attendance System

Dokumentasi lengkap skema database dan relasi untuk sistem absensi IoT berbasis Laravel.

---

## 📋 Daftar Tabel

| No | Tabel | Deskripsi |
|----|-------|-----------|
| 1 | `users` | Data pengguna sistem (Admin & Dosen) |
| 2 | `kelas` | Data kelas akademik (TI-1A, TI-1B, dll) |
| 3 | `mahasiswa` | Data mahasiswa beserta identitas biometrik |
| 4 | `mata_kuliah` | Data mata kuliah |
| 5 | `semester_akademik` | Periode semester akademik |
| 6 | `jadwal` | Jadwal perkuliahan |
| 7 | `absensi` | Data kehadiran mahasiswa |
| 8 | `devices` | Perangkat IoT terdaftar |
| 9 | `device_enrollment_jobs` | Job pendaftaran perangkat biometrik mahasiswa |
| 10 | `corrections` | Permohonan koreksi status kehadiran |
| 11 | `audit_logs` | Log aktivitas pengguna |
| 12 | `performance_metrics` | Metrik performa endpoint API |
| 13 | `mata_kuliah_dosen_assignments` | Penugasan dosen pengampu mata kuliah |

---

## 🔗 Relasi Antar Tabel

```
users (1) ──< jadwal (N)
users (1) ──< corrections (N) [user_id & approved_by]
users (1) ──< audit_logs (N)
users (1) ──< performance_metrics (N)
users (1) ──< device_enrollment_jobs (N) [requested_by]
users (1) ──< mata_kuliah_dosen_assignments (N)

kelas (1) ──< mahasiswa (N)
kelas (1) ──< jadwal (N)
kelas (1) ──< performance_metrics (N)

mahasiswa (1) ──< absensi (N)
mahasiswa (1) ──< corrections (N)
mahasiswa (1) ──< device_enrollment_jobs (N)

mata_kuliah (1) ──< jadwal (N)
mata_kuliah (1) ──< performance_metrics (N)
mata_kuliah (1) ──< mata_kuliah_dosen_assignments (1) [unique]

semester_akademik (1) ──< jadwal (N)
semester_akademik (1) ──< mata_kuliah (N)

jadwal (1) ──< absensi (N)
jadwal (1) ──< corrections (N)

devices (1) ──< device_enrollment_jobs (N)
```

---

## 🗺️ ERD — dbdiagram.io

Salin kode di bawah ini ke [dbdiagram.io](https://dbdiagram.io/d) untuk menghasilkan diagram ERD visual.

```dbml
// ----------------------------------
// Skema Database IoT Attendance System
// ----------------------------------

// Tabel Pengguna & Autentikasi
Table users {
  id bigint [pk, increment]
  name varchar(255) [not null]
  email varchar(191) [unique, not null]
  password varchar(255) [not null]
  role enum('admin','dosen') [not null, default: 'dosen']
  remember_token varchar(100)
  created_at timestamp
  updated_at timestamp
}

Table sessions {
  id varchar(255) [pk]
  user_id bigint [ref: > users.id]
  ip_address varchar(45)
  user_agent text
  payload text
  last_activity int(11) [not null]
}

Table audit_logs {
  id bigint [pk, increment]
  user_id bigint [ref: > users.id]
  action varchar(100) [not null]
  description text
  ip_address varchar(45)
  created_at timestamp [not null]
}

// Tabel Struktur Akademik
Table kelas {
  id bigint [pk, increment]
  nama_kelas varchar(50) [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table semester_akademik {
  id bigint [pk, increment]
  nama_semester varchar(50) [not null]
  tahun_ajaran varchar(20) [not null]
  tanggal_mulai date [not null]
  tanggal_selesai date [not null]
  is_active tinyint(1) [default: 0]
  created_at timestamp
  updated_at timestamp
}

Table mata_kuliah {
  id bigint [pk, increment]
  kode_mk varchar(20) [unique, not null]
  nama_mk varchar(255) [not null]
  sks int(11) [not null]
  semester_akademik_id bigint [ref: > semester_akademik.id]
  created_at timestamp
  updated_at timestamp
}

Table mahasiswa {
  id bigint [pk, increment]
  nim varchar(20) [unique, not null]
  nama varchar(255) [not null]
  kelas_id bigint [not null, ref: > kelas.id]
  rfid_uid varchar(50) [unique]
  fingerprint_data text
  face_model_data text
  barcode_id varchar(50) [unique]
  created_at timestamp
  updated_at timestamp
}

// Tabel Jadwal & Penugasan
Table jadwal {
  id bigint [pk, increment]
  kelas_id bigint [not null, ref: > kelas.id]
  mata_kuliah_id bigint [not null, ref: > mata_kuliah.id]
  user_id bigint [not null, ref: > users.id]
  semester_akademik_id bigint [ref: > semester_akademik.id]
  hari varchar(20) [not null]
  jam_mulai time [not null]
  jam_selesai time [not null]
  created_at timestamp
  updated_at timestamp
}

Table mata_kuliah_dosen_assignments {
  id bigint [pk, increment]
  mata_kuliah_id bigint [unique, not null, ref: > mata_kuliah.id]
  user_id bigint [not null, ref: > users.id]
  created_at timestamp
  updated_at timestamp
}

// ----- TABEL TRANSAKSI INTI -----

Table absensi {
  id bigint [pk, increment]
  mahasiswa_id bigint [not null, ref: > mahasiswa.id]
  jadwal_id bigint [not null, ref: > jadwal.id]
  tanggal date [not null]
  waktu_tap time [not null]
  metode_absensi enum('RFID','Fingerprint','Face Recognition','Barcode') [not null]
  status enum('Hadir','Telat','Sakit','Izin','Alpa') [not null, default: 'Alpa']
  created_at timestamp
  updated_at timestamp
}

Table corrections {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  mahasiswa_id bigint [not null, ref: > mahasiswa.id]
  jadwal_id bigint [ref: > jadwal.id]
  tanggal date [not null]
  status_lama enum('hadir','sakit_izin','alpa') [not null]
  status_baru enum('hadir','sakit_izin','alpa') [not null]
  status enum('pending','approved','rejected') [not null, default: 'pending']
  alasan text [not null]
  dokumen varchar(255)
  approval_status enum('pending','approved','rejected') [not null, default: 'pending']
  approval_notes text
  approved_by bigint [ref: > users.id]
  approved_at timestamp
  created_at timestamp
  updated_at timestamp
}

// Tabel Perangkat IoT
Table devices {
  id bigint [pk, increment]
  device_id varchar(50) [unique, not null]
  name varchar(255)
  token_hash varchar(255) [not null]
  is_active tinyint(1) [default: 1]
  last_seen_at timestamp
  created_at timestamp
  updated_at timestamp
}

Table device_enrollment_jobs {
  id bigint [pk, increment]
  mahasiswa_id bigint [not null, ref: > mahasiswa.id]
  device_id bigint [not null, ref: > devices.id]
  capture_type enum('rfid','fingerprint','face','barcode') [not null]
  status enum('pending_device','capturing','completed','failed','cancelled','expired') [not null, default: 'pending_device']
  captured_value text
  error_message text
  result_payload json
  requested_by bigint [ref: > users.id]
  expires_at timestamp
  started_at timestamp
  completed_at timestamp
  created_at timestamp
  updated_at timestamp
}

// Tabel Utilitas & Monitoring
Table performance_metrics {
  id bigint [pk, increment]
  endpoint varchar(120) [not null]
  query_duration_ms decimal(10,3) [not null]
  total_duration_ms decimal(10,3)
  result_count bigint [default: 0]
  page int(11) [default: 1]
  period_month varchar(7)
  kelas_id bigint [ref: > kelas.id]
  mata_kuliah_id bigint [ref: > mata_kuliah.id]
  user_id bigint [ref: > users.id]
  created_at timestamp
  updated_at timestamp
}

Table cache {
  key varchar(255) [pk]
  value mediumtext [not null]
  expiration int(11) [not null]
}

Table cache_locks {
  key varchar(255) [pk]
  owner varchar(255) [not null]
  expiration int(11) [not null]
}

Table jobs {
  id bigint [pk, increment]
  queue varchar(255) [not null]
  payload longtext [not null]
  attempts tinyint(3) unsigned [not null]
  reserved_at int(10) unsigned
  available_at int(10) unsigned [not null]
  created_at int(10) unsigned [not null]
}

Table job_batches {
  id varchar(255) [pk]
  name varchar(255) [not null]
  total_jobs int(11) [not null]
  pending_jobs int(11) [not null]
  failed_jobs int(11) [not null]
  failed_job_ids longtext [not null]
  options mediumtext
  cancelled_at int(11)
  created_at int(11) [not null]
  finished_at int(11)
}

Table failed_jobs {
  id bigint [pk, increment]
  uuid varchar(255) [unique, not null]
  connection text [not null]
  queue text [not null]
  payload longtext [not null]
  exception longtext [not null]
  failed_at timestamp [not null, default: `CURRENT_TIMESTAMP`]
}

Table password_reset_tokens {
  email varchar(255) [pk]
  token varchar(255) [not null]
  created_at timestamp
}
```

---

## 📐 Detail Kolom per Tabel

### `users`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key auto-increment |
| `name` | VARCHAR | No | Nama lengkap |
| `email` | VARCHAR | No | Email unik (login) |
| `password` | VARCHAR | No | Hash bcrypt |
| `role` | ENUM | No | `admin` atau `dosen` |
| `remember_token` | VARCHAR | Yes | Token "remember me" |
| `created_at` | TIMESTAMP | Yes | Waktu pembuatan |
| `updated_at` | TIMESTAMP | Yes | Waktu terakhir diubah |

### `kelas`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `nama_kelas` | VARCHAR | No | Nama kelas (TI-1A, TI-1B, dll) |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `semester_akademik`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `nama_semester` | VARCHAR | No | Semester Ganjil / Genap |
| `tahun_ajaran` | VARCHAR | No | Format `2025/2026` |
| `tanggal_mulai` | DATE | No | Tanggal mulai semester |
| `tanggal_selesai` | DATE | No | Tanggal selesai semester |
| `is_active` | BOOLEAN | No | Apakah semester ini aktif |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `mata_kuliah`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `kode_mk` | VARCHAR | No | Kode unik mata kuliah |
| `nama_mk` | VARCHAR | No | Nama mata kuliah |
| `sks` | INT | No | Jumlah SKS |
| `semester_akademik_id` | BIGINT | Yes | FK → `semester_akademik.id` |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `mahasiswa`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `nim` | VARCHAR | No | NIM unik |
| `nama` | VARCHAR | No | Nama mahasiswa |
| `kelas_id` | BIGINT | No | FK → `kelas.id` (CASCADE DELETE) |
| `rfid_uid` | VARCHAR | Yes | UID kartu RFID (unik) |
| `fingerprint_data` | TEXT | Yes | Template sidik jari |
| `face_model_data` | TEXT | Yes | Model pengenalan wajah |
| `barcode_id` | VARCHAR | Yes | ID barcode (unik) |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `jadwal`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `kelas_id` | BIGINT | No | FK → `kelas.id` |
| `mata_kuliah_id` | BIGINT | No | FK → `mata_kuliah.id` |
| `user_id` | BIGINT | No | FK → `users.id` (dosen) |
| `semester_akademik_id` | BIGINT | Yes | FK → `semester_akademik.id` |
| `hari` | VARCHAR | No | Hari (Monday, Tuesday, dll) |
| `jam_mulai` | TIME | No | Waktu mulai kuliah |
| `jam_selesai` | TIME | No | Waktu selesai kuliah |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `absensi`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `mahasiswa_id` | BIGINT | No | FK → `mahasiswa.id` |
| `jadwal_id` | BIGINT | No | FK → `jadwal.id` |
| `tanggal` | DATE | No | Tanggal kehadiran |
| `waktu_tap` | TIME | No | Waktu tap di perangkat |
| `metode_absensi` | ENUM | No | `RFID`, `Fingerprint`, `Face Recognition`, `Barcode` |
| `status` | ENUM | No | `Hadir`, `Telat`, `Sakit`, `Izin`, `Alpa` |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `devices`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `device_id` | VARCHAR | No | ID unik perangkat (unik) |
| `name` | VARCHAR | Yes | Nama deskriptif |
| `token_hash` | VARCHAR | No | Hash token autentikasi |
| `is_active` | BOOLEAN | No | Status aktif perangkat |
| `last_seen_at` | TIMESTAMP | Yes | Terakhir terhubung |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `device_enrollment_jobs`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `mahasiswa_id` | BIGINT | No | FK → `mahasiswa.id` (CASCADE) |
| `device_id` | BIGINT | No | FK → `devices.id` (CASCADE) |
| `capture_type` | VARCHAR(30) | No | `rfid`, `fingerprint`, `face`, `barcode` |
| `status` | VARCHAR(30) | No | `pending_device`, `capturing`, `completed`, `failed`, `cancelled`, `expired` |
| `captured_value` | TEXT | Yes | Nilai yang berhasil ditangkap |
| `error_message` | TEXT | Yes | Pesan error jika gagal |
| `result_payload` | JSON | Yes | Payload hasil enrollment |
| `requested_by` | BIGINT | Yes | FK → `users.id` (NULL ON DELETE) |
| `expires_at` | TIMESTAMP | Yes | Waktu kedaluwarsa job |
| `started_at` | TIMESTAMP | Yes | Waktu mulai capture |
| `completed_at` | TIMESTAMP | Yes | Waktu selesai capture |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `corrections`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `user_id` | BIGINT | No | FK → `users.id` (pemohon) |
| `mahasiswa_id` | BIGINT | No | FK → `mahasiswa.id` (CASCADE) |
| `jadwal_id` | BIGINT | Yes | FK → `jadwal.id` (NULL ON DELETE) |
| `tanggal` | DATE | No | Tanggal yang dikoreksi |
| `status_lama` | ENUM | No | `hadir`, `sakit_izin`, `alpa` |
| `status_baru` | ENUM | No | `hadir`, `sakit_izin`, `alpa` |
| `status` | ENUM | No | `pending`, `approved`, `rejected` |
| `alasan` | TEXT | No | Alasan pengajuan |
| `dokumen` | VARCHAR | Yes | File bukti pendukung |
| `approval_status` | ENUM | No | `pending`, `approved`, `rejected` |
| `approval_notes` | TEXT | Yes | Catatan persetujuan |
| `approved_by` | BIGINT | Yes | FK → `users.id` (NULL ON DELETE) |
| `approved_at` | TIMESTAMP | Yes | Waktu persetujuan |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `audit_logs`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `user_id` | BIGINT | Yes | FK → `users.id` (NULL ON DELETE) |
| `action` | VARCHAR(100) | No | Jenis aksi |
| `description` | TEXT | Yes | Deskripsi detail |
| `ip_address` | VARCHAR(45) | Yes | IP address pelaku |
| `created_at` | TIMESTAMP | No | Waktu aksi |

### `performance_metrics`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `endpoint` | VARCHAR(120) | No | Endpoint API |
| `query_duration_ms` | DECIMAL(10,3) | No | Durasi query (ms) |
| `total_duration_ms` | DECIMAL(10,3) | Yes | Total durasi request |
| `result_count` | BIGINT | No | Jumlah hasil |
| `page` | INT | No | Halaman request |
| `period_month` | VARCHAR(7) | Yes | Periode `YYYY-MM` |
| `kelas_id` | BIGINT | Yes | FK → `kelas.id` (NULL ON DELETE) |
| `mata_kuliah_id` | BIGINT | Yes | FK → `mata_kuliah.id` (NULL ON DELETE) |
| `user_id` | BIGINT | Yes | FK → `users.id` (NULL ON DELETE) |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

### `mata_kuliah_dosen_assignments`
| Kolom | Tipe | Nullable | Keterangan |
|-------|------|----------|------------|
| `id` | BIGINT | No | Primary key |
| `mata_kuliah_id` | BIGINT | No | FK → `mata_kuliah.id` (UNIQUE, CASCADE) |
| `user_id` | BIGINT | No | FK → `users.id` (CASCADE) |
| `created_at` | TIMESTAMP | Yes | |
| `updated_at` | TIMESTAMP | Yes | |

---

## 🗂️ Indexes

| Tabel | Index | Kolom | Tipe |
|-------|-------|-------|------|
| `users` | `users_email_unique` | `email` | UNIQUE |
| `mahasiswa` | `mahasiswa_nim_unique` | `nim` | UNIQUE |
| `mahasiswa` | `mahasiswa_rfid_uid_unique` | `rfid_uid` | UNIQUE |
| `mahasiswa` | `mahasiswa_barcode_id_unique` | `barcode_id` | UNIQUE |
| `mata_kuliah` | `mata_kuliah_kode_mk_unique` | `kode_mk` | UNIQUE |
| `devices` | `devices_device_id_unique` | `device_id` | UNIQUE |
| `mata_kuliah_dosen_assignments` | `unique` | `mata_kuliah_id` | UNIQUE |
| `absensi` | `absensi_tanggal_index` | `tanggal` | INDEX |
| `absensi` | `absensi_status_index` | `status` | INDEX |
| `absensi` | `absensi_mahasiswa_tanggal_unique` | `mahasiswa_id`, `tanggal` | UNIQUE |
| `corrections` | `corrections_mahasiswa_id_index` | `mahasiswa_id` | INDEX |
| `corrections` | `corrections_status_index` | `status` | INDEX |
| `corrections` | `corrections_approval_status_index` | `approval_status` | INDEX |
| `corrections` | `corrections_tanggal_index` | `tanggal` | INDEX |
| `corrections` | `corrections_mahasiswa_status_idx` | `mahasiswa_id`, `status` | COMPOSITE |
| `audit_logs` | `audit_logs_action_index` | `action` | INDEX |
| `audit_logs` | `audit_logs_created_at_index` | `created_at` | INDEX |
| `performance_metrics` | `perf_endpoint_created_idx` | `endpoint`, `created_at` | COMPOSITE |
| `device_enrollment_jobs` | `device_enrollment_jobs_device_id_status_index` | `device_id`, `status` | COMPOSITE |
| `device_enrollment_jobs` | `device_enroll_mhs_type_status_idx` | `mahasiswa_id`, `capture_type`, `status` | COMPOSITE |
| `device_enrollment_jobs` | `device_enrollment_jobs_expires_at_status_index` | `expires_at`, `status` | COMPOSITE |

---

## 🔄 Alur Data Utama

### 1. Absensi IoT
```
Perangkat IoT → POST /api/absensi → Identifikasi mahasiswa (RFID/Face/Fingerprint/Barcode)
  → Cari jadwal aktif hari ini → Catat ke tabel `absensi`
```

### 2. Koreksi Kehadiran
```
Dosen/Admin → Ajukan koreksi → `corrections` (status: pending)
  → Approval → `corrections` (status: approved) → Update `absensi.status`
```

### 3. Enrollment Perangkat
```
Admin → Buat job enrollment → `device_enrollment_jobs` (status: pending_device)
  → Mahasiswa tap perangkat → Capture data → `device_enrollment_jobs` (status: completed)
  → Update `mahasiswa.rfid_uid` / `fingerprint_data` / `face_model_data`
```

---

## 📝 Catatan

- **Cascade Delete**: `mahasiswa` → `absensi`, `corrections`, `device_enrollment_jobs`
- **Soft References**: `performance_metrics` menggunakan `nullOnDelete` untuk menjaga data historis
- **Unique Constraint**: Satu mahasiswa hanya bisa memiliki satu record absensi per hari (`mahasiswa_id` + `tanggal`)
- **Role-based Access**: `users.role` menentukan akses (`admin` = full access, `dosen` = limited access)
