<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\SemesterAkademik;
use App\Models\User;
use App\Services\DeviceCommandService;
use App\Services\ZktecoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ZktecoBiometricSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_has_stable_zkteco_uid_and_can_store_fingerprint_template(): void
    {
        $dosen = User::create([
            'name' => 'Dosen Fingerprint',
            'email' => 'dosen-fingerprint@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);

        $this->assertSame(50000 + $dosen->id, $dosen->fresh()->zktecoUid());

        $dosen->update([
            'zk_uid' => $dosen->zktecoUid(),
            'fingerprint_data' => ['0' => 'template-a'],
            'fingerprint_synced_at' => now(),
        ]);

        $this->assertSame(['0' => 'template-a'], $dosen->fresh()->fingerprint_data);
        $this->assertNotNull($dosen->fresh()->fingerprint_synced_at);
    }

    public function test_agent_pull_biometrics_stores_registered_lecturer_fingerprint_template(): void
    {
        $device = Device::create([
            'device_id' => 'ZK_DOSEN_PULL',
            'name' => 'ZKTeco Dosen Pull',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.30',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);
        $dosen = User::create([
            'name' => 'Dosen Pull',
            'email' => 'dosen-pull@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);
        $command = DeviceCommand::create([
            'device_id' => $device->id,
            'type' => 'pull_biometrics',
            'status' => 'queued',
        ]);

        app(DeviceCommandService::class)->applyResult($command, [
            'users' => [[
                'uid' => 50000 + $dosen->id,
                'userid' => (string) (50000 + $dosen->id),
                'has_fingerprint' => true,
                'fingerprint_data' => ['0' => 'template-pull'],
            ]],
        ]);

        $dosen->refresh();
        $this->assertSame(50000 + $dosen->id, $dosen->zk_uid);
        $this->assertSame(['0' => 'template-pull'], $dosen->fingerprint_data);
        $this->assertNotNull($dosen->fingerprint_synced_at);
    }

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

    public function test_import_attendance_from_device_uses_shared_schedule_status_and_per_schedule_duplicate_rules(): void
    {
        Cache::flush();

        $kelas = Kelas::create(['nama_kelas' => 'TI-3A']);
        $mahasiswa = Mahasiswa::create([
            'nim' => '20220002',
            'nama' => 'Sari Wulandari',
            'kelas_id' => $kelas->id,
            'rfid_uid' => '20220002',
        ]);

        $dosen = User::create([
            'name' => 'Dosen ZKTeco',
            'email' => 'zk-dosen@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);

        $semester = SemesterAkademik::create([
            'nama_semester' => 'Semester Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => '2026-02-01',
            'tanggal_selesai' => '2026-07-31',
            'is_active' => true,
        ]);

        $firstCourse = MataKuliah::create([
            'kode_mk' => 'ZK001',
            'nama_mk' => 'Jaringan Komputer',
            'sks' => 3,
        ]);

        $secondCourse = MataKuliah::create([
            'kode_mk' => 'ZK002',
            'nama_mk' => 'Sistem Tertanam',
            'sks' => 3,
        ]);

        $firstSchedule = Jadwal::create([
            'kelas_id' => $kelas->id,
            'mata_kuliah_id' => $firstCourse->id,
            'user_id' => $dosen->id,
            'semester_akademik_id' => $semester->id,
            'hari' => 'Jumat',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);

        $secondSchedule = Jadwal::create([
            'kelas_id' => $kelas->id,
            'mata_kuliah_id' => $secondCourse->id,
            'user_id' => $dosen->id,
            'semester_akademik_id' => $semester->id,
            'hari' => 'Jumat',
            'jam_mulai' => '13:00:00',
            'jam_selesai' => '15:00:00',
        ]);

        Absensi::create([
            'mahasiswa_id' => $mahasiswa->id,
            'jadwal_id' => $firstSchedule->id,
            'tanggal' => '2026-06-12',
            'waktu_tap' => '08:05:00',
            'metode_absensi' => 'Fingerprint',
            'status' => 'Hadir',
        ]);

        $device = Device::create([
            'device_id' => 'ZK_IMPORT_1',
            'name' => 'ZKTeco Import Test',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.20',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $result = (new ZktecoService($device))->importAttendanceFromRecords([
            [
                'id' => '20220002',
                'timestamp' => '2026-06-12 13:16:00',
            ],
        ]);

        $this->assertSame(1, $result['inserted']);
        $this->assertSame(0, $result['skipped']);

        $this->assertDatabaseHas('absensi', [
            'mahasiswa_id' => $mahasiswa->id,
            'jadwal_id' => $secondSchedule->id,
            'tanggal' => '2026-06-12',
            'waktu_tap' => '13:16:00',
            'metode_absensi' => 'Fingerprint',
            'status' => 'Telat',
        ]);

        $this->assertSame(2, Absensi::where('mahasiswa_id', $mahasiswa->id)->count());
    }
}
