<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'device_id' => 'ROOM_101',
                'name' => 'ESP32 Room 101',
                'plain_token' => 'room101-token-please-change',
            ],
            [
                'device_id' => 'ROOM_102',
                'name' => 'ESP32 Room 102',
                'plain_token' => 'room102-token-please-change',
            ],
            [
                'device_id' => 'LAB_GATE_1',
                'name' => 'RFID Gate Lab 1',
                'plain_token' => 'labgate1-token-please-change',
            ],
        ];

        foreach ($defaults as $device) {
            DB::table('devices')->updateOrInsert(
                ['device_id' => $device['device_id']],
                [
                    'name' => $device['name'],
                    'token_hash' => hash('sha256', $device['plain_token']),
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
