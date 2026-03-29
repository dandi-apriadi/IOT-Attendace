<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_enrollment_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('capture_type', 30); // rfid|fingerprint|face|barcode
            $table->string('status', 30)->default('pending_device'); // pending_device|capturing|completed|failed|cancelled|expired
            $table->text('captured_value')->nullable();
            $table->text('error_message')->nullable();
            $table->json('result_payload')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['mahasiswa_id', 'capture_type', 'status'], 'device_enroll_mhs_type_status_idx');
            $table->index(['expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_enrollment_jobs');
    }
};
