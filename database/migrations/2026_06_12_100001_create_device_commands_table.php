<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_commands')) {
            return;
        }

        Schema::create('device_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            // push_all_users|push_user|remove_user|sync_time|clear_attendance
            // |get_info|pull_users|read_user|pull_attendance
            $table->string('type', 40);
            $table->json('payload')->nullable();
            // queued|dispatched|done|failed
            $table->string('status', 20)->default('queued');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_commands');
    }
};
