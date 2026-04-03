<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
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
