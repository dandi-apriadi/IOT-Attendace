<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corrections', function (Blueprint $table) {
            $table->foreignId('jadwal_id')
                ->nullable()
                ->after('mahasiswa_id')
                ->constrained('jadwal')
                ->nullOnDelete();

            $table->index(['mahasiswa_id', 'status'], 'corrections_mahasiswa_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('corrections', function (Blueprint $table) {
            $table->dropIndex('corrections_mahasiswa_status_idx');
            $table->dropConstrainedForeignId('jadwal_id');
        });
    }
};
