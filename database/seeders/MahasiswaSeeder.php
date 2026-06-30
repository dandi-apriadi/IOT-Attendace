<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MahasiswaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // TI-1A (kelas_id = 1)
            ['nim' => '23010001', 'nama' => 'Andi Pratama',        'kelas_id' => 1],
            ['nim' => '23010002', 'nama' => 'Budi Santoso',        'kelas_id' => 1],
            ['nim' => '23010003', 'nama' => 'Citra Dewi',          'kelas_id' => 1],
            ['nim' => '23010004', 'nama' => 'Dian Kusuma',         'kelas_id' => 1],
            ['nim' => '23010005', 'nama' => 'Eko Wahyudi',         'kelas_id' => 1],
            ['nim' => '23010006', 'nama' => 'Fajar Ramadhan',      'kelas_id' => 1],
            ['nim' => '23010007', 'nama' => 'Gita Puspita',        'kelas_id' => 1],
            ['nim' => '23010008', 'nama' => 'Hendra Setiawan',     'kelas_id' => 1],
            ['nim' => '23010009', 'nama' => 'Indah Lestari',       'kelas_id' => 1],
            ['nim' => '23010010', 'nama' => 'Joko Susilo',         'kelas_id' => 1],

            // TI-1B (kelas_id = 2)
            ['nim' => '23020001', 'nama' => 'Kevin Maulana',       'kelas_id' => 2],
            ['nim' => '23020002', 'nama' => 'Linda Hartati',       'kelas_id' => 2],
            ['nim' => '23020003', 'nama' => 'Muhammad Rizki',      'kelas_id' => 2],
            ['nim' => '23020004', 'nama' => 'Nadia Putri',         'kelas_id' => 2],
            ['nim' => '23020005', 'nama' => 'Oscar Firmansyah',    'kelas_id' => 2],
            ['nim' => '23020006', 'nama' => 'Putri Rahayu',        'kelas_id' => 2],
            ['nim' => '23020007', 'nama' => 'Qori Ananda',         'kelas_id' => 2],
            ['nim' => '23020008', 'nama' => 'Rudi Hermawan',       'kelas_id' => 2],
            ['nim' => '23020009', 'nama' => 'Sari Indrawati',      'kelas_id' => 2],
            ['nim' => '23020010', 'nama' => 'Taufik Hidayat',      'kelas_id' => 2],

            // TI-2A (kelas_id = 3)
            ['nim' => '22030001', 'nama' => 'Umar Faruq',          'kelas_id' => 3],
            ['nim' => '22030002', 'nama' => 'Vina Amelia',         'kelas_id' => 3],
            ['nim' => '22030003', 'nama' => 'Wahyu Nugroho',       'kelas_id' => 3],
            ['nim' => '22030004', 'nama' => 'Xena Claudia',        'kelas_id' => 3],
            ['nim' => '22030005', 'nama' => 'Yoga Pratama',        'kelas_id' => 3],
            ['nim' => '22030006', 'nama' => 'Zahra Aulia',         'kelas_id' => 3],
            ['nim' => '22030007', 'nama' => 'Agus Kurniawan',      'kelas_id' => 3],
            ['nim' => '22030008', 'nama' => 'Bella Safitri',       'kelas_id' => 3],
            ['nim' => '22030009', 'nama' => 'Chandra Wijaya',      'kelas_id' => 3],
            ['nim' => '22030010', 'nama' => 'Dewi Anggraeni',      'kelas_id' => 3],
        ];

        $now = now();
        foreach ($data as $m) {
            DB::table('mahasiswa')->updateOrInsert(
                ['nim' => $m['nim']],
                array_merge($m, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
