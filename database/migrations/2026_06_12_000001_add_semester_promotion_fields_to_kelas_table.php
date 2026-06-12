<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->unsignedTinyInteger('semester_level')->nullable()->after('nama_kelas');
            $table->foreignId('next_kelas_id')
                ->nullable()
                ->after('semester_level')
                ->constrained('kelas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_kelas_id');
            $table->dropColumn('semester_level');
        });
    }
};
