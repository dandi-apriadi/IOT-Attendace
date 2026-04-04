<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->foreignId('semester_akademik_id')
                ->nullable()
                ->after('sks')
                ->constrained('semester_akademik')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->dropForeign(['semester_akademik_id']);
            $table->dropColumn('semester_akademik_id');
        });
    }
};
