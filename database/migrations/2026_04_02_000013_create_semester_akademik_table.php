<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_akademik', function (Blueprint $table) {
            $table->id();
            $table->string('nama_semester');
            $table->string('tahun_ajaran');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $now = now();
        $academicYearStart = $now->month >= 8 ? $now->year : $now->year - 1;
        $academicYearEnd = $academicYearStart + 1;
        $isGanjil = $now->month >= 8;

        DB::table('semester_akademik')->insert([
            'nama_semester' => $isGanjil ? 'Semester Ganjil' : 'Semester Genap',
            'tahun_ajaran' => $academicYearStart . '/' . $academicYearEnd,
            'tanggal_mulai' => $isGanjil
                ? $academicYearStart . '-08-01'
                : $academicYearStart . '-02-01',
            'tanggal_selesai' => $isGanjil
                ? $academicYearEnd . '-01-31'
                : $academicYearEnd . '-07-31',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_akademik');
    }
};