<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['role' => 'dosen'],
            [
                'name' => 'Dosen Utama',
                'email' => 'dosen@gmail.com',
                'password' => '123',
                'role' => 'dosen',
            ]
        );
    }
}
