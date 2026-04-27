<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\SemesterAkademik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['role' => 'admin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => '123',
                'role' => 'admin',
            ]
        );

        $dosen = User::updateOrCreate(
            ['role' => 'dosen'],
            [
                'name' => 'Dosen Utama',
                'email' => 'dosen@gmail.com',
                'password' => '123',
                'role' => 'dosen',
            ]
        );

        $kelasList = collect([
            'TI-1A',
            'TI-1B',
            'TI-2A',
        ])->mapWithKeys(function (string $namaKelas): array {
            $kelas = Kelas::updateOrCreate(
                ['nama_kelas' => $namaKelas],
                ['nama_kelas' => $namaKelas]
            );

            return [$namaKelas => $kelas];
        });

        $mataKuliahList = collect([
            ['kode' => 'IF101', 'nama' => 'Pemrograman Dasar', 'sks' => 3],
            ['kode' => 'IF102', 'nama' => 'Basis Data', 'sks' => 3],
            ['kode' => 'IF201', 'nama' => 'Jaringan Komputer', 'sks' => 3],
            ['kode' => 'IF202', 'nama' => 'Sistem Embedded', 'sks' => 2],
        ])->mapWithKeys(function (array $item): array {
            $mataKuliah = MataKuliah::updateOrCreate(
                ['kode_mk' => $item['kode']],
                [
                    'kode_mk' => $item['kode'],
                    'nama_mk' => $item['nama'],
                    'sks' => $item['sks'],
                ]
            );

            return [$item['kode'] => $mataKuliah];
        });

        $now = now();
        $academicYearStart = $now->month >= 8 ? $now->year : $now->year - 1;
        $academicYearEnd = $academicYearStart + 1;

        $activeSemester = SemesterAkademik::updateOrCreate(
            [
                'nama_semester' => $now->month >= 8 ? 'Semester Ganjil' : 'Semester Genap',
                'tahun_ajaran' => $academicYearStart . '/' . $academicYearEnd,
            ],
            [
                'tanggal_mulai' => $now->month >= 8
                    ? $academicYearStart . '-08-01'
                    : $academicYearStart . '-02-01',
                'tanggal_selesai' => $now->month >= 8
                    ? $academicYearEnd . '-01-31'
                    : $academicYearEnd . '-07-31',
                'is_active' => true,
            ]
        );

        SemesterAkademik::query()
            ->whereKeyNot($activeSemester->id)
            ->update(['is_active' => false]);

        $previousSemester = SemesterAkademik::updateOrCreate(
            [
                'nama_semester' => $now->month >= 8 ? 'Semester Genap' : 'Semester Ganjil',
                'tahun_ajaran' => ($academicYearStart - 1) . '/' . $academicYearStart,
            ],
            [
                'tanggal_mulai' => ($academicYearStart - 1) . '-08-01',
                'tanggal_selesai' => $academicYearStart . '-01-31',
                'is_active' => false,
            ]
        );

        $scheduleData = [
            [$activeSemester->id, $kelasList['TI-1A']->id, $mataKuliahList['IF101']->id, $dosen->id, 'Monday', '08:00:00', '09:40:00'],
            [$activeSemester->id, $kelasList['TI-1B']->id, $mataKuliahList['IF102']->id, $dosen->id, 'Tuesday', '10:00:00', '11:40:00'],
            [$activeSemester->id, $kelasList['TI-2A']->id, $mataKuliahList['IF201']->id, $dosen->id, 'Wednesday', '13:00:00', '14:40:00'],
            [$previousSemester->id, $kelasList['TI-1A']->id, $mataKuliahList['IF202']->id, $dosen->id, 'Thursday', '08:00:00', '09:40:00'],
        ];

        foreach ($scheduleData as [$semesterId, $kelasId, $mataKuliahId, $userId, $hari, $jamMulai, $jamSelesai]) {
            Jadwal::updateOrCreate(
                [
                    'semester_akademik_id' => $semesterId,
                    'kelas_id' => $kelasId,
                    'mata_kuliah_id' => $mataKuliahId,
                    'user_id' => $userId,
                    'hari' => $hari,
                ],
                [
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                ]
            );
        }
    }
}