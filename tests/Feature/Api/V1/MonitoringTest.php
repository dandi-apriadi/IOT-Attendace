<?php

namespace Tests\Feature\Api\V1;

use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(string $kodeMk, string $namaKelas): array
    {
        $kelas = Kelas::create(['nama_kelas' => $namaKelas]);
        $mataKuliah = MataKuliah::create(['kode_mk' => $kodeMk, 'nama_mk' => $kodeMk . ' Course', 'sks' => 3]);
        $semester = SemesterAkademik::create([
            'nama_semester' => 'Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => Carbon::now()->subMonth()->toDateString(),
            'tanggal_selesai' => Carbon::now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);
        $dosen = User::create([
            'name' => 'Dosen ' . $kodeMk,
            'email' => strtolower($kodeMk) . '@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'dosen',
        ]);
        $jadwal = Jadwal::create([
            'kelas_id' => $kelas->id,
            'mata_kuliah_id' => $mataKuliah->id,
            'user_id' => $dosen->id,
            'semester_akademik_id' => $semester->id,
            'hari' => Carbon::now()->format('l'),
            'jam_mulai' => Carbon::now()->subHour()->format('H:i:s'),
            'jam_selesai' => Carbon::now()->addHour()->format('H:i:s'),
        ]);

        return compact('kelas', 'mataKuliah', 'semester', 'dosen', 'jadwal');
    }

    public function test_endpoint_monitoring_menolak_tanpa_token(): void
    {
        $this->getJson('/api/v1/monitoring/live')->assertStatus(401);
        $this->getJson('/api/v1/devices')->assertStatus(401);
        $this->getJson('/api/v1/dashboard/summary')->assertStatus(401);
    }

    public function test_admin_melihat_semua_sesi_pada_live_monitoring(): void
    {
        $courseA = $this->makeCourse('AAA', 'Kelas-A');
        $courseB = $this->makeCourse('BBB', 'Kelas-B');

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/monitoring/live');

        $response->assertStatus(200);
        $jadwalIds = collect($response->json('sessions'))->pluck('id')->all();
        $this->assertContains($courseA['jadwal']->id, $jadwalIds);
        $this->assertContains($courseB['jadwal']->id, $jadwalIds);
    }

    public function test_dosen_hanya_melihat_sesi_mata_kuliah_miliknya(): void
    {
        $courseA = $this->makeCourse('CCC', 'Kelas-C');
        $courseB = $this->makeCourse('DDD', 'Kelas-D');

        MataKuliahDosenAssignment::create([
            'mata_kuliah_id' => $courseA['mataKuliah']->id,
            'user_id' => $courseA['dosen']->id,
        ]);

        Sanctum::actingAs($courseA['dosen'], ['*']);

        $response = $this->getJson('/api/v1/monitoring/live');

        $response->assertStatus(200);
        $jadwalIds = collect($response->json('sessions'))->pluck('id')->all();
        $this->assertContains($courseA['jadwal']->id, $jadwalIds);
        $this->assertNotContains($courseB['jadwal']->id, $jadwalIds);
    }

    public function test_devices_endpoint_mengembalikan_status_terhitung(): void
    {
        Device::create([
            'device_id' => 'DEV-ONLINE',
            'name' => 'Device Online',
            'token_hash' => hash('sha256', 'x'),
            'is_active' => true,
            'last_seen_at' => now(),
        ]);
        Device::create([
            'device_id' => 'DEV-DISABLED',
            'name' => 'Device Disabled',
            'token_hash' => hash('sha256', 'y'),
            'is_active' => false,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.online', 1)
            ->assertJsonPath('meta.disabled', 1);
    }

    public function test_attendance_history_dosen_hanya_melihat_mata_kuliah_miliknya(): void
    {
        $courseA = $this->makeCourse('EEE', 'Kelas-E');
        $courseB = $this->makeCourse('FFF', 'Kelas-F');

        MataKuliahDosenAssignment::create([
            'mata_kuliah_id' => $courseA['mataKuliah']->id,
            'user_id' => $courseA['dosen']->id,
        ]);

        $mhsA = Mahasiswa::create(['nim' => '1001', 'nama' => 'Mahasiswa A', 'kelas_id' => $courseA['kelas']->id]);
        $mhsB = Mahasiswa::create(['nim' => '1002', 'nama' => 'Mahasiswa B', 'kelas_id' => $courseB['kelas']->id]);

        Absensi::create([
            'mahasiswa_id' => $mhsA->id,
            'jadwal_id' => $courseA['jadwal']->id,
            'tanggal' => now()->toDateString(),
            'waktu_tap' => now()->format('H:i:s'),
            'metode_absensi' => 'RFID',
            'status' => 'Hadir',
        ]);
        Absensi::create([
            'mahasiswa_id' => $mhsB->id,
            'jadwal_id' => $courseB['jadwal']->id,
            'tanggal' => now()->toDateString(),
            'waktu_tap' => now()->format('H:i:s'),
            'metode_absensi' => 'RFID',
            'status' => 'Hadir',
        ]);

        Sanctum::actingAs($courseA['dosen'], ['*']);

        $response = $this->getJson('/api/v1/attendance/history');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('mahasiswa_id')->all();
        $this->assertContains($mhsA->id, $ids);
        $this->assertNotContains($mhsB->id, $ids);
    }
}
