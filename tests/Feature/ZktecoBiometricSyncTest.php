<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\ZktecoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZktecoBiometricSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_registered_student_biometrics_updates_existing_students_only(): void
    {
        $kelas = Kelas::create(['nama_kelas' => 'TI-3A']);
        $mahasiswa = Mahasiswa::create([
            'nim' => '20220001',
            'nama' => 'Budi Santoso',
            'kelas_id' => $kelas->id,
        ]);

        $device = Device::create([
            'device_id' => 'ZK_X609_1',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $result = (new ZktecoService($device))->syncRegisteredStudentBiometricsFromUsers(
            [
                [
                    'uid' => $mahasiswa->id,
                    'userid' => $mahasiswa->nim,
                    'name' => $mahasiswa->nama,
                    'cardno' => '99887766',
                ],
                [
                    'uid' => 999,
                    'userid' => 'BELUM_ADA',
                    'name' => 'User Belum Ada',
                    'cardno' => '12345678',
                ],
            ],
            fn (int $uid): bool => $uid === (int) $mahasiswa->id
        );

        $this->assertSame(2, $result['scanned']);
        $this->assertSame(1, $result['matched']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['rfid_updated']);
        $this->assertSame(1, $result['fingerprint_updated']);
        $this->assertSame(1, $result['unmatched']);

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $mahasiswa->id,
            'rfid_uid' => '99887766',
            'fingerprint_data' => 'enrolled@ZK_X609_1',
        ]);
        $this->assertDatabaseMissing('mahasiswa', [
            'nim' => 'BELUM_ADA',
        ]);
    }
}
