<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->string('status_akademik', 30)->default('aktif')->after('kelas_id');
            $table->unsignedTinyInteger('semester_level')->nullable()->after('status_akademik');
            $table->boolean('promotion_paused')->default(false)->after('semester_level');
            $table->string('promotion_note')->nullable()->after('promotion_paused');
            $table->timestamp('last_promoted_at')->nullable()->after('promotion_note');
            $table->index(['status_akademik', 'promotion_paused'], 'mahasiswa_promotion_status_idx');
            $table->index('semester_level', 'mahasiswa_semester_level_idx');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $table) {
            $table->dropIndex('mahasiswa_promotion_status_idx');
            $table->dropIndex('mahasiswa_semester_level_idx');
            $table->dropColumn([
                'status_akademik',
                'semester_level',
                'promotion_paused',
                'promotion_note',
                'last_promoted_at',
            ]);
        });
    }
};
