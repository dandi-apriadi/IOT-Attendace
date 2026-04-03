<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah_dosen_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->unique()->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Backfill assignments for courses that currently have exactly one distinct lecturer.
        $singleLecturerCourses = DB::table('jadwal')
            ->select('mata_kuliah_id', DB::raw('MIN(user_id) as user_id'))
            ->groupBy('mata_kuliah_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 1')
            ->get();

        foreach ($singleLecturerCourses as $row) {
            DB::table('mata_kuliah_dosen_assignments')->insert([
                'mata_kuliah_id' => (int) $row->mata_kuliah_id,
                'user_id' => (int) $row->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mata_kuliah_dosen_assignments');
    }
};
