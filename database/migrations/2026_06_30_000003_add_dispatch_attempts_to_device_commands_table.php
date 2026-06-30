<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_commands', function (Blueprint $table): void {
            if (! Schema::hasColumn('device_commands', 'dispatch_attempts')) {
                $table->unsignedSmallInteger('dispatch_attempts')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_commands', function (Blueprint $table): void {
            if (Schema::hasColumn('device_commands', 'dispatch_attempts')) {
                $table->dropColumn('dispatch_attempts');
            }
        });
    }
};
