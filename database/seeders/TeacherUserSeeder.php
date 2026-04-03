<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'dosen@admin.com'],
            [
                'name' => 'Dosen Utama',
                'password' => Hash::make('password'),
                'role' => 'dosen',
            ]
        );
    }
}
