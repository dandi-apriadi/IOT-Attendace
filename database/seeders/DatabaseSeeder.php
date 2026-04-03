<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            TeacherUserSeeder::class,
            AcademicDemoSeeder::class,
            DeviceSeeder::class,
        ]);
    }
}
