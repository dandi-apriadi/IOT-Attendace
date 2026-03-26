<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->index('created_at', 'absensi_created_at_idx');
            $table->index(['created_at', 'status'], 'absensi_created_at_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropIndex('absensi_created_at_idx');
            $table->dropIndex('absensi_created_at_status_idx');
        });
    }
};
