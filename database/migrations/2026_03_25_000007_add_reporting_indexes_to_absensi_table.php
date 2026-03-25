<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->index('tanggal', 'absensi_tanggal_idx');
            $table->index(['tanggal', 'mahasiswa_id'], 'absensi_tanggal_mahasiswa_idx');
            $table->index(['tanggal', 'jadwal_id'], 'absensi_tanggal_jadwal_idx');
            $table->index(['tanggal', 'status'], 'absensi_tanggal_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropIndex('absensi_tanggal_idx');
            $table->dropIndex('absensi_tanggal_mahasiswa_idx');
            $table->dropIndex('absensi_tanggal_jadwal_idx');
            $table->dropIndex('absensi_tanggal_status_idx');
        });
    }
};
