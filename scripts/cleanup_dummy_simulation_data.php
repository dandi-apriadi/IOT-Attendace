<?php

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $simUser = User::where('email', 'simulasi.dosen@kampus.local')->first();
    $simKelas = Kelas::where('nama_kelas', 'TI-REG-SIM')->first();
    $simMk = MataKuliah::where('kode_mk', 'SIM101')->first();

    $jadwalIds = collect();

    if ($simKelas) {
        $jadwalIds = $jadwalIds->merge(Jadwal::where('kelas_id', $simKelas->id)->pluck('id'));
    }

    if ($simMk) {
        $jadwalIds = $jadwalIds->merge(Jadwal::where('mata_kuliah_id', $simMk->id)->pluck('id'));
    }

    if ($simUser) {
        $jadwalIds = $jadwalIds->merge(Jadwal::where('user_id', $simUser->id)->pluck('id'));
    }

    $jadwalIds = $jadwalIds->unique()->values();

    $deletedAbsensi = 0;
    if ($jadwalIds->isNotEmpty()) {
        $deletedAbsensi = Absensi::whereIn('jadwal_id', $jadwalIds)->delete();
    }

    $deletedJadwal = 0;
    if ($jadwalIds->isNotEmpty()) {
        $deletedJadwal = Jadwal::whereIn('id', $jadwalIds)->delete();
    }

    $deletedMahasiswa = 0;
    if ($simKelas) {
        $deletedMahasiswa = Mahasiswa::where('kelas_id', $simKelas->id)->delete();
    }

    $deletedKelas = 0;
    if ($simKelas) {
        $deletedKelas = Kelas::where('id', $simKelas->id)->delete();
    }

    $deletedMk = 0;
    if ($simMk) {
        $deletedMk = MataKuliah::where('id', $simMk->id)->delete();
    }

    $deletedUser = 0;
    if ($simUser) {
        $deletedUser = User::where('id', $simUser->id)->delete();
    }

    DB::commit();

    echo json_encode([
        'deleted_absensi' => $deletedAbsensi,
        'deleted_jadwal' => $deletedJadwal,
        'deleted_mahasiswa' => $deletedMahasiswa,
        'deleted_kelas' => $deletedKelas,
        'deleted_mata_kuliah' => $deletedMk,
        'deleted_user' => $deletedUser,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    DB::rollBack();

    echo json_encode([
        'error' => $e->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
