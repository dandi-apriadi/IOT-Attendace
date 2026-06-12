<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('kelas')
            ->whereNotNull('semester_level')
            ->orderBy('id')
            ->get(['id', 'semester_level'])
            ->each(function ($kelas): void {
                DB::table('mahasiswa')
                    ->where('kelas_id', $kelas->id)
                    ->whereNull('semester_level')
                    ->update([
                        'semester_level' => $kelas->semester_level,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('mahasiswa')->update([
            'semester_level' => null,
            'updated_at' => now(),
        ]);
    }
};
