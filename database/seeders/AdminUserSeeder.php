<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['role' => 'admin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@gmail.com',
                'password' => '123',
                'role' => 'admin',
            ]
        );
    }
}
