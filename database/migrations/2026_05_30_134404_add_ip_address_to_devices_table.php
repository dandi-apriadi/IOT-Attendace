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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('type')->default('custom_iot')->after('name'); // 'custom_iot' or 'zkteco'
            $table->string('ip_address')->nullable()->after('type');
            $table->integer('port')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('devices', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('devices', 'port')) {
                $table->dropColumn('port');
            }
        });
    }
};
