<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_semester_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa')->cascadeOnDelete();
            $table->foreignId('from_kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('to_kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->unsignedTinyInteger('from_semester_level')->nullable();
            $table->unsignedTinyInteger('to_semester_level')->nullable();
            $table->string('mode', 20)->default('execute');
            $table->string('note')->nullable();
            $table->timestamp('promoted_at');
            $table->timestamps();

            $table->index(['mahasiswa_id', 'promoted_at'], 'student_promotions_mahasiswa_date_idx');
            $table->index(['mode', 'promoted_at'], 'student_promotions_mode_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_semester_promotions');
    }
};
