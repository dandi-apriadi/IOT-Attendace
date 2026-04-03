<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->foreignId('semester_akademik_id')
                ->nullable()
                ->after('user_id')
                ->constrained('semester_akademik')
                ->nullOnDelete();
        });

        $defaultSemesterId = DB::table('semester_akademik')->orderBy('id')->value('id');

        if ($defaultSemesterId) {
            DB::table('jadwal')
                ->whereNull('semester_akademik_id')
                ->update(['semester_akademik_id' => $defaultSemesterId]);
        }
    }

    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_akademik_id');
        });
    }
};