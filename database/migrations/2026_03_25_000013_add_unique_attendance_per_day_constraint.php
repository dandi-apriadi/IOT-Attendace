<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->unique(
                ['mahasiswa_id', 'jadwal_id', 'tanggal'],
                'absensi_unique_mahasiswa_jadwal_tanggal'
            );
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropUnique('absensi_unique_mahasiswa_jadwal_tanggal');
        });
    }
};