<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 120)->index();
            $table->decimal('query_duration_ms', 10, 3);
            $table->decimal('total_duration_ms', 10, 3)->nullable();
            $table->unsignedBigInteger('result_count')->default(0);
            $table->unsignedInteger('page')->default(1);
            $table->string('period_month', 7)->nullable();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('mata_kuliah_id')->nullable()->constrained('mata_kuliah')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['endpoint', 'created_at'], 'perf_endpoint_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
