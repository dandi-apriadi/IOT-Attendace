<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa');
            $table->foreignId('jadwal_id')->constrained('jadwal');
            $table->date('tanggal');
            $table->time('waktu_tap');
            $table->enum('metode_absensi', ['RFID', 'Fingerprint', 'Face Recognition', 'Barcode']);
            $table->enum('status', ['Hadir', 'Telat', 'Sakit', 'Izin', 'Alpa'])->default('Alpa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
