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
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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
        $this->assertContains($absensi->status, ['Hadir', 'Telat']);
    }

    public function test_api_absensi_menandai_telat_setelah_lewat_15_menit_dari_jadwal()
    {
        $tapAt = Carbon::parse('2026-06-11 08:16:00');
        $jadwal = Jadwal::firstOrFail();
        $jadwal->update([
            'hari' => $tapAt->format('l'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);

        Carbon::setTestNow($tapAt);

        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.keterangan', 'Telat');

        $this->assertDatabaseHas('absensi', [
            'jadwal_id' => $jadwal->id,
            'tanggal' => $tapAt->toDateString(),
            'waktu_tap' => '08:16:00',
            'status' => 'Telat',
        ]);

        Carbon::setTestNow();
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

    public function test_manual_session_tidak_mencatat_mahasiswa_dari_kelas_lain()
    {
        $activeJadwal = Jadwal::firstOrFail();
        $otherClass = Kelas::create(['nama_kelas' => 'TI-3B']);
        $otherStudent = Mahasiswa::create([
            'nim' => '20229988',
            'nama' => 'Mahasiswa Kelas Lain',
            'kelas_id' => $otherClass->id,
            'rfid_uid' => 'RFID-OTHER-CLASS',
        ]);

        Cache::put('active_attendance_session', [
            'mata_kuliah_id' => $activeJadwal->mata_kuliah_id,
            'kelas_id' => $activeJadwal->kelas_id,
            'jadwal_id' => $activeJadwal->id,
            'started_at' => now()->toDateTimeString(),
            'user_id' => null,
            'source' => 'schedule',
        ], now()->addHours(3));

        $response = $this->postJson('/api/absensi', [
            'identifier' => 'RFID-OTHER-CLASS',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Tidak ada jadwal/sesi aktif saat ini']);

        $this->assertDatabaseMissing('absensi', [
            'mahasiswa_id' => $otherStudent->id,
            'jadwal_id' => $activeJadwal->id,
        ]);
    }

    public function test_beberapa_sesi_manual_bisa_berjalan_bersamaan()
    {
        $firstJadwal = Jadwal::firstOrFail();
        $semester = SemesterAkademik::firstOrFail();
        $secondClass = Kelas::create(['nama_kelas' => 'TI-3B']);
        $secondCourse = MataKuliah::create([
            'kode_mk' => 'JAR001',
            'nama_mk' => 'Jaringan Komputer',
            'sks' => 3,
        ]);
        $secondDosen = User::create([
            'name' => 'Dosen Sesi Kedua',
            'email' => 'dosen-sesi-kedua@example.test',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);
        $secondJadwal = Jadwal::create([
            'kelas_id' => $secondClass->id,
            'mata_kuliah_id' => $secondCourse->id,
            'user_id' => $secondDosen->id,
            'semester_akademik_id' => $semester->id,
            'hari' => now()->format('l'),
            'jam_mulai' => now()->copy()->subHour()->format('H:i:s'),
            'jam_selesai' => now()->copy()->addHour()->format('H:i:s'),
        ]);
        $secondStudent = Mahasiswa::create([
            'nim' => '20229977',
            'nama' => 'Mahasiswa Sesi Kedua',
            'kelas_id' => $secondClass->id,
            'rfid_uid' => 'RFID-SECOND-SESSION',
        ]);

        $sessions = app(AttendanceSessionService::class);
        $sessions->putActiveSession([
            'mata_kuliah_id' => $firstJadwal->mata_kuliah_id,
            'kelas_id' => $firstJadwal->kelas_id,
            'jadwal_id' => $firstJadwal->id,
            'started_at' => now()->toDateTimeString(),
            'user_id' => null,
            'source' => 'schedule',
        ], now()->addHours(3));
        $sessions->putActiveSession([
            'mata_kuliah_id' => $secondJadwal->mata_kuliah_id,
            'kelas_id' => $secondJadwal->kelas_id,
            'jadwal_id' => $secondJadwal->id,
            'started_at' => now()->toDateTimeString(),
            'user_id' => null,
            'source' => 'schedule',
        ], now()->addHours(3));

        $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ])->assertStatus(200);

        $this->postJson('/api/absensi', [
            'identifier' => 'RFID-SECOND-SESSION',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ])->assertStatus(200);

        $this->assertDatabaseHas('absensi', [
            'jadwal_id' => $firstJadwal->id,
            'mahasiswa_id' => Mahasiswa::where('rfid_uid', 'RFID123456')->firstOrFail()->id,
        ]);
        $this->assertDatabaseHas('absensi', [
            'jadwal_id' => $secondJadwal->id,
            'mahasiswa_id' => $secondStudent->id,
        ]);
    }

    public function test_live_monitoring_data_refreshes_after_existing_attendance_is_updated()
    {
        $dosen = User::where('role', 'dosen')->firstOrFail();
        $date = Carbon::now()->toDateString();
        $firstTapAt = Carbon::now()->copy()->setSeconds(0);
        $secondTapAt = $firstTapAt->copy()->addSeconds(2);

        Carbon::setTestNow($firstTapAt);
        $this->postJson('/api/absensi', [
            'identifier' => 'RFID123456',
            'type' => 'RFID',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ])->assertStatus(200);

        $this->actingAs($dosen)
            ->getJson("/monitoring/live/data?date={$date}")
            ->assertStatus(200)
            ->assertJsonPath('records.0.metode_absensi', 'RFID');

        Carbon::setTestNow($secondTapAt);
        $this->postJson('/api/absensi', [
            'identifier' => 'FINGER123456',
            'type' => 'Fingerprint',
        ], [
            'X-Device-Token' => $this->deviceToken,
            'X-Device-Id' => $this->deviceId,
        ])->assertStatus(200);

        $this->actingAs($dosen)
            ->getJson("/monitoring/live/data?date={$date}")
            ->assertStatus(200)
            ->assertJsonPath('records.0.metode_absensi', 'Fingerprint')
            ->assertJsonPath('records.0.time', $secondTapAt->format('H:i:s'))
            ->assertJsonPath('records.0.waktu_tap', $secondTapAt->format('H:i:s'));

        Carbon::setTestNow();
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
