<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('zk_uid')->nullable()->unique()->after('role');
            $table->json('fingerprint_data')->nullable()->after('zk_uid');
            $table->timestamp('fingerprint_synced_at')->nullable()->after('fingerprint_data');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['zk_uid', 'fingerprint_data', 'fingerprint_synced_at']);
        });
    }
};
