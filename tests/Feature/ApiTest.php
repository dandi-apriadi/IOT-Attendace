<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Mahasiswa;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Jadwal;
use App\Models\User;
use App\Models\Device;
use App\Models\Absensi;
use App\Models\SemesterAkademik;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $deviceToken = 'change-this-token-for-iot-devices';
    protected $deviceId = 'test-device-001';

    public function setUp(): void
    {
        parent::setUp();
        $this->setupTestData();
    }

    /**
     * Setup test data
     */
    private function setupTestData(): void
    {
        // Create a test device
        Device::create([
            'device_id' => $this->deviceId,
            'name' => 'Test IoT Device',
            'token_hash' => hash('sha256', $this->deviceToken),
            'is_active' => true,
        ]);

        // Create kelas
        $kelas = Kelas::create(['nama_kelas' => 'TI-3A']);

        // Create mata kuliah
        $mataKuliah = MataKuliah::create([
            'kode_mk' => 'PBO001',
            'nama_mk' => 'Pemrograman Berorientasi Objek',
            'sks' => 3,
        ]);

        // Create semester akademik aktif yang mencakup tanggal tes
        $semester = SemesterAkademik::create([
            'nama_semester' => 'Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => Carbon::now()->copy()->subMonth()->toDateString(),
            'tanggal_selesai' => Carbon::now()->copy()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        // Create dosen
        $dosen = User::create([
            'name' => 'Drs. Ahmad Wijaya',
            'email' => 'ahmad@poltek.ac.id',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);

        // Create jadwal (schedule for today at current time)
        $now = Carbon::now();
        $dayName = match ($now->dayOfWeek) {
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        };

        Jadwal::create([
            'kelas_id' => $kelas->id,
            'mata_kuliah_id' => $mataKuliah->id,
            'user_id' => $dosen->id,
            'semester_akademik_id' => $semester->id,
            'hari' => $dayName,
            'jam_mulai' => $now->copy()->subHours(1)->format('H:i:s'),
            'jam_selesai' => $now->copy()->addHours(2)->format('H:i:s'),
        ]);

        // Create mahasiswa with RFID
        Mahasiswa::create([
            'nim' => '20220001',
            'nama' => 'Budi Santoso',
            'kelas_id' => $kelas->id,
            'rfid_uid' => 'RFID123456',
            'fingerprint_data' => 'FINGER123456',
            'face_model_data' => 'FACE123456',
            'barcode_id' => 'BARCODE123456',
        ]);
    }

    /**
     * Test POST /api/absensi dengan RFID
     */
    public function test_api_absensi_dengan_rfid()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'data' => [
                    'nama',
                    'mata_kuliah',
                    'waktu',
                    'keterangan',
                ]
            ]);
    }

    /**
     * Test POST /api/absensi dengan Fingerprint
     */
    public function test_api_absensi_dengan_fingerprint()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'FINGER123456',
            'type' => 'Fingerprint',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    /**
     * Test POST /api/absensi dengan Face Recognition
     */
    public function test_api_absensi_dengan_face_recognition()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'FACE123456',
            'type' => 'Face Recognition',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    /**
     * Test POST /api/absensi dengan Barcode
     */
    public function test_api_absensi_dengan_barcode()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'BARCODE123456',
            'type' => 'Barcode',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    /**
     * Test API without device token - should be rejected
     */
    public function test_api_absensi_tanpa_token_ditolak()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized device token.']);
    }

    /**
     * Test API dengan token yang salah - should be rejected
     */
    public function test_api_absensi_dengan_token_salah_ditolak()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => 'wrong-token',
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized device token.']);
    }

    /**
     * Test API dengan mahasiswa tidak terdaftar
     */
    public function test_api_absensi_mahasiswa_tidak_terdaftar()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'UNKNOWN_ID',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Mahasiswa tidak terdaftar']);
    }

    /**
     * Test API dengan tipe yang tidak valid
     */
    public function test_api_absensi_tipe_tidak_valid()
    {
        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'Invalid Type',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test API tanpa identifier
     */
    public function test_api_absensi_tanpa_identifier()
    {
        $response = $this->postJson('/api/absensi', [
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['identifier']);
    }

    /**
     * Test data absensi tersimpan dengan benar
     */
    public function test_data_absensi_tersimpan()
    {
        $mahasiswa = Mahasiswa::where('rfid_uid', 'RFID123456')->first();

        $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $absensi = Absensi::where('mahasiswa_id', $mahasiswa->id)->first();
        
        $this->assertNotNull($absensi);
        $this->assertEquals('RFID', $absensi->metode_absensi);
        $this->assertIn($absensi->status, ['Hadir', 'Telat']);
    }

    /**
     * Test multiple tap dari mahasiswa yang sama hari yang sama
     */
    public function test_multiple_tap_same_student_same_day()
    {
        $mahasiswa = Mahasiswa::where('rfid_uid', 'RFID123456')->first();
        $jadwal = Jadwal::first();

        // First tap
        $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        // Second tap (should update, not create new)
        $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'Fingerprint',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $count = Absensi::where('mahasiswa_id', $mahasiswa->id)
                         ->where('jadwal_id', $jadwal->id)
                         ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * Test batas maksimal 16 pertemuan per mata kuliah
     */
    public function test_api_absensi_menolak_pertemuan_ke_17()
    {
        $mahasiswa = Mahasiswa::where('rfid_uid', 'RFID123456')->first();
        $jadwal = Jadwal::first();
        $now = Carbon::now();

        for ($i = 1; $i <= 16; $i++) {
            Absensi::create([
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $now->copy()->subDays($i)->toDateString(),
                'waktu_tap' => '08:00:00',
                'metode_absensi' => 'RFID',
                'status' => 'Hadir',
            ]);
        }

        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(422);

        $count = Absensi::where('mahasiswa_id', $mahasiswa->id)
            ->where('jadwal_id', $jadwal->id)
            ->count();

        $this->assertEquals(16, $count);
    }
}
