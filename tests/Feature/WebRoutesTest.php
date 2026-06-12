<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Mahasiswa;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $dosen;
    protected $mahasiswa;
    protected $jadwal;

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
        // Create admin user
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@poltek.ac.id',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create dosen user
        $this->dosen = User::create([
            'name' => 'Dosan User',
            'email' => 'dosen@poltek.ac.id',
            'password' => bcrypt('password'),
            'role' => 'dosen',
        ]);

        // Create kelas
        $kelas = Kelas::create(['nama_kelas' => 'TI-3A']);

        // Create mata kuliah
        $mataKuliah = MataKuliah::create([
            'kode_mk' => 'PBO001',
            'nama_mk' => 'Pemrograman Berorientasi Objek',
            'sks' => 3,
        ]);

        // Create jadwal
        $this->jadwal = Jadwal::create([
            'kelas_id' => $kelas->id,
            'mata_kuliah_id' => $mataKuliah->id,
            'user_id' => $this->dosen->id,
            'hari' => 'Monday',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);

        // Create assignment so dosen owns this course
        MataKuliahDosenAssignment::create([
            'mata_kuliah_id' => $mataKuliah->id,
            'user_id' => $this->dosen->id,
        ]);

        // Create mahasiswa
        $this->mahasiswa = Mahasiswa::create([
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
     * Test guest dapat mengakses halaman login
     */
    public function test_guest_dapat_akses_login()
    {
        $response = $this->get('/');
        $response->assertStatus(200)
                 ->assertViewIs('login');
    }

    /**
     * Test login dengan kredensial yang benar
     */
    public function test_login_dengan_kredensial_benar()
    {
        $response = $this->post('/login', [
            'email' => 'admin@poltek.ac.id',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->admin);
    }

    /**
     * Test login dengan kredensial yang salah
     */
    public function test_login_dengan_kredensial_salah()
    {
        $response = $this->post('/login', [
            'email' => 'admin@poltek.ac.id',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
    }

    /**
     * Test dashboard dapat diakses setelah login
     */
    public function test_dashboard_hanya_untuk_authenticated()
    {
        // Tidak login
        $response = $this->get('/dashboard');
        $response->assertRedirect('/');

        // Login sebagai admin
        $this->actingAs($this->admin);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }

    /**
     * Test dosen courses route
     */
    public function test_dosen_courses_route()
    {
        $this->actingAs($this->dosen);
        $response = $this->get('/dosen/mata-kuliah');
        $response->assertStatus(200);
    }

    /**
     * Test monitoring live route
     */
    public function test_monitoring_live_route()
    {
        $this->actingAs($this->dosen);
        $response = $this->get('/monitoring/live');
        $response->assertStatus(200);
    }

    public function test_monitoring_live_menandai_alpa_untuk_mahasiswa_tanpa_absensi_pada_jadwal_selesai()
    {
        $finishedAt = Carbon::parse('2026-06-08 10:01:00');
        $this->jadwal->update([
            'hari' => $finishedAt->format('l'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);

        Mahasiswa::create([
            'nim' => '20220002',
            'nama' => 'Siti Tanpa Tap',
            'kelas_id' => $this->mahasiswa->kelas_id,
            'rfid_uid' => 'RFID654321',
            'fingerprint_data' => 'FINGER654321',
            'face_model_data' => 'FACE654321',
            'barcode_id' => 'BARCODE654321',
        ]);

        Absensi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'jadwal_id' => $this->jadwal->id,
            'tanggal' => $finishedAt->toDateString(),
            'waktu_tap' => '08:05:00',
            'metode_absensi' => 'RFID',
            'status' => 'Hadir',
        ]);

        Carbon::setTestNow($finishedAt);

        $this->actingAs($this->dosen);
        $response = $this->getJson("/monitoring/live/data?date={$finishedAt->toDateString()}&jadwal_id={$this->jadwal->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Siti Tanpa Tap',
                'status' => 'Alpa',
                'is_pending' => false,
                'editable' => false,
            ]);

        Carbon::setTestNow();
    }

    public function test_detail_sesi_jadwal_menandai_alpa_setelah_jadwal_selesai()
    {
        $finishedAt = Carbon::parse('2026-06-08 10:01:00');
        $this->jadwal->update([
            'hari' => $finishedAt->format('l'),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '10:00:00',
        ]);

        Mahasiswa::create([
            'nim' => '20220002',
            'nama' => 'Siti Tanpa Tap',
            'kelas_id' => $this->mahasiswa->kelas_id,
            'rfid_uid' => 'RFID654321',
            'fingerprint_data' => 'FINGER654321',
            'face_model_data' => 'FACE654321',
            'barcode_id' => 'BARCODE654321',
        ]);

        Carbon::setTestNow($finishedAt);

        $this->actingAs($this->dosen);
        $response = $this->get('/dosen/schedule/detail?' . http_build_query([
            'date' => $finishedAt->toDateString(),
            'mata_kuliah_id' => $this->jadwal->mata_kuliah_id,
            'kelas_id' => $this->jadwal->kelas_id,
        ]));

        $response->assertStatus(200)
            ->assertSee('Siti Tanpa Tap')
            ->assertSee('Alpa')
            ->assertDontSee('Belum Absensi');

        Carbon::setTestNow();
    }

    /**
     * Test monitoring health route
     */
    public function test_monitoring_health_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/monitoring/health');
        $response->assertStatus(200);
    }

    /**
     * Test monitoring performance reports route
     */
    public function test_monitoring_performance_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/monitoring/performance/reports');
        $response->assertStatus(200);
    }

    /**
     * Test reports index route
     */
    public function test_reports_index_route()
    {
        $this->actingAs($this->dosen);
        $response = $this->get('/reports');
        $response->assertStatus(200);
    }

    /**
     * Test audit log route (admin only)
     */
    public function test_audit_log_route_admin_only()
    {
        // Dosen tidak boleh akses
        $this->actingAs($this->dosen);
        $response = $this->get('/reports/audit');
        $response->assertStatus(403);

        // Admin bisa akses
        $this->actingAs($this->admin);
        $response = $this->get('/reports/audit');
        $response->assertStatus(200);
    }

    /**
     * Test correction report route
     */
    public function test_correction_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/reports/correction');
        $response->assertStatus(200);
    }

    /**
     * Test master data mahasiswa route
     */
    public function test_master_mahasiswa_admin_only()
    {
        // Dosen tidak boleh akses
        $this->actingAs($this->dosen);
        $response = $this->get('/master/mahasiswa');
        $response->assertStatus(403);

        // Admin bisa akses
        $this->actingAs($this->admin);
        $response = $this->get('/master/mahasiswa');
        $response->assertStatus(200);
    }

    /**
     * Test show mahasiswa route
     */
    public function test_show_mahasiswa_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get("/master/mahasiswa/{$this->mahasiswa->id}");
        $response->assertStatus(200);
    }

    /**
     * Test master mahasiswa menyediakan aksi tarik biometrik dari alat ZKTeco
     */
    public function test_master_mahasiswa_menampilkan_aksi_tarik_biometrik_zkteco()
    {
        Device::create([
            'device_id' => 'ZK_X609_1',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/master/mahasiswa');

        $response->assertStatus(200)
            ->assertSee('Tarik Biometrik dari Alat')
            ->assertSee('ZKTeco X609 #1')
            ->assertSee('/master/mahasiswa/pull-biometrics', false)
            ->assertDontSee('Daftarkan ke Alat');
    }

    /**
     * Test perangkat ZKTeco menyediakan aksi pull biometrik mahasiswa terdaftar
     */
    public function test_devices_zkteco_menampilkan_aksi_pull_biometrik()
    {
        Device::create([
            'device_id' => 'ZK_X609_1',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
        $response = $this->get('/master/devices');

        $response->assertStatus(200)
            ->assertSee('Pull Biometrik')
            ->assertSee('/master/devices/1/pull-biometrics', false);
    }

    public function test_zkteco_agent_command_status_memberikan_respons_realtime(): void
    {
        config(['agent.role' => 'server']);

        $device = Device::create([
            'device_id' => 'ZK_X609_1',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $queued = $this->postJson("/master/devices/{$device->id}/test");
        $queued->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $command = DeviceCommand::findOrFail($queued->json('command_id'));

        $this->getJson("/master/devices/{$device->id}/commands/{$command->id}/status")
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'queued',
                'pending' => true,
                'done' => false,
            ]);

        $command->forceFill([
            'status' => 'done',
            'result' => [
                'version' => 'Ver 6.60',
                'serial_number' => 'SN123',
                'device_name' => 'X609',
                'platform' => 'ZMM',
                'device_time' => '2026-06-12 20:00:00',
            ],
            'completed_at' => now(),
        ])->save();

        $this->getJson("/master/devices/{$device->id}/commands/{$command->id}/status")
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'status' => 'done',
                'pending' => false,
                'done' => true,
                'message' => 'Koneksi berhasil. Info perangkat sudah diterima dari agent.',
                'result' => [
                    'serial_number' => 'SN123',
                    'device_name' => 'X609',
                ],
            ]);
    }

    public function test_scan_agent_mendaftarkan_perangkat_zkteco_yang_ditemukan(): void
    {
        config(['agent.role' => 'server']);

        $agentDevice = Device::create([
            'device_id' => 'ZKTECO-X609-01',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($this->admin);

        $queued = $this->postJson('/master/devices/scan-agent');
        $queued->assertOk()
            ->assertJson([
                'ok' => true,
                'queued' => true,
            ])
            ->assertJsonStructure(['command_id', 'status_url']);

        $command = DeviceCommand::findOrFail($queued->json('command_id'));
        $this->assertSame('scan_devices', $command->type);
        $this->assertSame($agentDevice->id, $command->device_id);

        $this->postJson("/api/agent/commands/{$command->id}/result", [
            'success' => true,
            'result' => [
                'devices' => [
                    [
                        'ip_address' => '192.168.0.10',
                        'port' => 4370,
                        'serial_number' => 'SN-001',
                        'device_name' => 'X609 Lab 1',
                        'platform' => 'ZMM',
                    ],
                    [
                        'ip_address' => '192.168.0.11',
                        'port' => 4370,
                        'serial_number' => 'SN-002',
                        'device_name' => 'X609 Lab 2',
                        'platform' => 'ZMM',
                    ],
                ],
            ],
        ], [
            'X-Device-Id' => 'ZKTECO-X609-01',
            'X-Device-Token' => 'test-token',
        ])->assertOk()
            ->assertJson(['status' => 'done']);

        $this->assertDatabaseHas('devices', [
            'device_id' => 'ZKTECO-X609-01',
            'name' => 'X609 Lab 1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('devices', [
            'device_id' => 'ZKTECO-SN-002',
            'name' => 'X609 Lab 2',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.11',
            'port' => 4370,
            'is_active' => true,
        ]);

        $this->assertSame(2, Device::where('type', 'zkteco')->count());
    }

    public function test_update_device_tidak_bisa_mengubah_identitas_alat(): void
    {
        $device = Device::create([
            'device_id' => 'ZK_X609_1',
            'name' => 'ZKTeco X609 #1',
            'type' => 'zkteco',
            'ip_address' => '192.168.0.10',
            'port' => 4370,
            'token_hash' => hash('sha256', 'test-token'),
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
        $response = $this->put("/master/devices/{$device->id}", [
            'device_id' => 'CUSTOM-01',
            'name' => 'Ruang Lab Baru',
            'type' => 'custom_iot',
            'ip_address' => '192.168.0.20',
            'port' => 1234,
            'token_hash' => '',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/master/devices');
        $device->refresh();

        $this->assertSame('ZK_X609_1', $device->device_id);
        $this->assertSame('zkteco', $device->type);
        $this->assertSame('Ruang Lab Baru', $device->name);
        $this->assertSame('192.168.0.20', $device->ip_address);
        $this->assertSame(1234, $device->port);
    }

    public function test_update_mahasiswa_tidak_bisa_mengubah_identitas_dan_biometrik_manual(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put("/master/mahasiswa/{$this->mahasiswa->id}", [
            'nim' => '20999999',
            'nama' => 'Budi Update',
            'kelas_id' => $this->mahasiswa->kelas_id,
            'status_akademik' => 'nonaktif',
            'semester_level' => 4,
            'promotion_paused' => '1',
            'promotion_note' => 'Verifikasi admin',
            'rfid_uid' => 'RFID-EDITED',
            'barcode_id' => 'BARCODE-EDITED',
            'fingerprint_data' => 'FINGER-EDITED',
            'face_model_data' => 'FACE-EDITED',
        ]);

        $response->assertRedirect('/master/mahasiswa');
        $this->mahasiswa->refresh();

        $this->assertSame('20220001', $this->mahasiswa->nim);
        $this->assertSame('RFID123456', $this->mahasiswa->rfid_uid);
        $this->assertSame('BARCODE123456', $this->mahasiswa->barcode_id);
        $this->assertSame('FINGER123456', $this->mahasiswa->fingerprint_data);
        $this->assertSame('FACE123456', $this->mahasiswa->face_model_data);
        $this->assertSame('Budi Update', $this->mahasiswa->nama);
        $this->assertSame('nonaktif', $this->mahasiswa->status_akademik);
        $this->assertTrue($this->mahasiswa->promotion_paused);
        $this->assertSame('Verifikasi admin', $this->mahasiswa->promotion_note);
    }

    public function test_tambah_mahasiswa_mengabaikan_biometrik_manual(): void
    {
        $kelas = Kelas::firstOrFail();

        $this->actingAs($this->admin);
        $response = $this->post('/master/mahasiswa', [
            'nim' => '20229999',
            'nama' => 'Mahasiswa Dari Form',
            'kelas_id' => $kelas->id,
            'status_akademik' => 'aktif',
            'semester_level' => 1,
            'rfid_uid' => 'RFID-FORM',
            'barcode_id' => 'BARCODE-FORM',
            'fingerprint_data' => 'FINGER-FORM',
            'face_model_data' => 'FACE-FORM',
        ]);

        $response->assertRedirect('/master/mahasiswa');

        $this->assertDatabaseHas('mahasiswa', [
            'nim' => '20229999',
            'nama' => 'Mahasiswa Dari Form',
            'rfid_uid' => null,
            'barcode_id' => null,
            'fingerprint_data' => null,
            'face_model_data' => null,
        ]);
    }

    public function test_admin_hanya_membuat_semester_awal_dari_form(): void
    {
        SemesterAkademik::query()->delete();

        $this->actingAs($this->admin);

        $response = $this->post('/master/semester', [
            'nama_semester' => 'Semester Ganjil',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => '2025-08-01',
            'tanggal_selesai' => '2026-01-31',
        ]);

        $response->assertRedirect('/master/semester');

        $this->assertSame(1, SemesterAkademik::count());
        $semester = SemesterAkademik::firstOrFail();
        $this->assertSame('Semester Ganjil', $semester->nama_semester);
        $this->assertSame('2025/2026', $semester->tahun_ajaran);
        $this->assertSame('2025-08-01', $semester->tanggal_mulai->toDateString());
        $this->assertSame('2026-01-31', $semester->tanggal_selesai->toDateString());
        $this->assertTrue($semester->is_active);
    }

    public function test_submit_semester_setelah_awal_menghasilkan_semester_berikutnya_otomatis(): void
    {
        SemesterAkademik::query()->delete();
        SemesterAkademik::create([
            'nama_semester' => 'Semester Ganjil',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => '2025-08-01',
            'tanggal_selesai' => '2026-01-31',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/master/semester', [
            'nama_semester' => 'Semester Manual Salah',
            'tahun_ajaran' => '2030/2031',
            'tanggal_mulai' => '2030-01-01',
            'tanggal_selesai' => '2030-06-30',
        ]);

        $response->assertRedirect('/master/semester');

        $this->assertSame(2, SemesterAkademik::count());
        $generatedSemester = SemesterAkademik::query()
            ->orderByDesc('tanggal_mulai')
            ->firstOrFail();
        $this->assertSame('Semester Genap', $generatedSemester->nama_semester);
        $this->assertSame('2025/2026', $generatedSemester->tahun_ajaran);
        $this->assertSame('2026-02-01', $generatedSemester->tanggal_mulai->toDateString());
        $this->assertSame('2026-07-31', $generatedSemester->tanggal_selesai->toDateString());
        $this->assertFalse($generatedSemester->is_active);
        $this->assertDatabaseMissing('semester_akademik', [
            'nama_semester' => 'Semester Manual Salah',
            'tahun_ajaran' => '2030/2031',
        ]);
    }

    public function test_generate_semester_berikutnya_menaikkan_tahun_ajaran_setelah_genap(): void
    {
        SemesterAkademik::query()->delete();
        SemesterAkademik::create([
            'nama_semester' => 'Semester Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => '2026-02-01',
            'tanggal_selesai' => '2026-07-31',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $this->post('/master/semester', [
            'nama_semester' => 'Tidak Dipakai',
            'tahun_ajaran' => '2030/2031',
            'tanggal_mulai' => '2030-01-01',
            'tanggal_selesai' => '2030-06-30',
        ])->assertRedirect('/master/semester');

        $generatedSemester = SemesterAkademik::query()
            ->orderByDesc('tanggal_mulai')
            ->firstOrFail();

        $this->assertSame('Semester Ganjil', $generatedSemester->nama_semester);
        $this->assertSame('2026/2027', $generatedSemester->tahun_ajaran);
        $this->assertSame('2026-08-01', $generatedSemester->tanggal_mulai->toDateString());
        $this->assertSame('2027-01-31', $generatedSemester->tanggal_selesai->toDateString());
    }

    /**
     * Test master data mata kuliah route
     */
    public function test_master_matakuliah_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/master/matakuliah');
        $response->assertStatus(200);
    }

    /**
     * Test master data kelas route
     */
    public function test_master_kelas_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/master/kelas');
        $response->assertStatus(200);
    }

    /**
     * Test master data jadwal route
     */
    public function test_master_jadwal_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/master/jadwal');
        $response->assertStatus(200);
    }

    /**
     * Test master data users route
     */
    public function test_master_users_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/master/users');
        $response->assertStatus(200);
    }

    /**
     * Test student detail route
     */
    public function test_student_detail_route()
    {
        $this->actingAs($this->dosen);
        $response = $this->get("/student/{$this->mahasiswa->id}");
        $response->assertStatus(200);
    }

    /**
     * Test settings route
     */
    public function test_settings_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/profile/settings');
        $response->assertStatus(200);
    }

    /**
     * Test logout
     */
    public function test_logout()
    {
        $this->actingAs($this->admin);
        $response = $this->post('/logout');
        
        $this->assertGuest();
    }

    /**
     * Test public billboard route
     */
    public function test_public_billboard_route()
    {
        $response = $this->get('/public/billboard');
        $response->assertStatus(200);
    }

    /**
     * Test create user route
     */
    public function test_create_user_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/master/users/create');
        $response->assertStatus(200);
    }

    /**
     * Test store user route
     */
    public function test_store_user()
    {
        $this->actingAs($this->admin);
        $response = $this->post('/master/users', [
            'name' => 'New User',
            'email' => 'newuser@poltek.ac.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'dosen',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@poltek.ac.id',
            'role' => 'dosen',
        ]);
    }

    /**
     * Test edit user route
     */
    public function test_edit_user_route()
    {
        $this->actingAs($this->admin);
        $response = $this->get("/master/users/{$this->dosen->id}/edit");
        $response->assertStatus(200);
    }
}
