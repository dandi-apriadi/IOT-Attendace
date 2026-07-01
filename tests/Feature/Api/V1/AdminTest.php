<?php

namespace Tests\Feature\Api\V1;

use App\Models\Absensi;
use App\Models\AuditLog;
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

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_ditolak_akses_audit_log(): void
    {
        $dosen = User::create([
            'name' => 'Dosen',
            'email' => 'dosen@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'dosen',
        ]);

        Sanctum::actingAs($dosen, ['*']);

        $this->getJson('/api/v1/audit-log')->assertStatus(403);
    }

    public function test_admin_bisa_akses_audit_log_dengan_summary(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@poltek.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        AuditLog::create(['user_id' => $admin->id, 'action' => 'login', 'description' => 'ok', 'created_at' => now()]);
        AuditLog::create(['user_id' => null, 'action' => 'login_failed', 'description' => 'gagal', 'created_at' => now()]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/v1/audit-log');

        $response->assertStatus(200)
            ->assertJsonPath('summary.total_events', 2)
            ->assertJsonPath('summary.error_events', 1);
    }

    public function test_dosen_hanya_melihat_laporan_mata_kuliah_miliknya(): void
    {
        $semester = SemesterAkademik::create([
            'nama_semester' => 'Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => Carbon::now()->subMonth()->toDateString(),
            'tanggal_selesai' => Carbon::now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $kelasA = Kelas::create(['nama_kelas' => 'A']);
        $mkA = MataKuliah::create(['kode_mk' => 'AAA', 'nama_mk' => 'Course A', 'sks' => 3]);
        $dosen = User::create(['name' => 'Dosen A', 'email' => 'dosena@poltek.ac.id', 'password' => bcrypt('x'), 'role' => 'dosen']);
        MataKuliahDosenAssignment::create(['mata_kuliah_id' => $mkA->id, 'user_id' => $dosen->id]);
        $jadwalA = Jadwal::create([
            'kelas_id' => $kelasA->id, 'mata_kuliah_id' => $mkA->id, 'user_id' => $dosen->id,
            'semester_akademik_id' => $semester->id, 'hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
        ]);
        $mhsA = Mahasiswa::create(['nim' => '1', 'nama' => 'Mhs A', 'kelas_id' => $kelasA->id]);
        Absensi::create(['mahasiswa_id' => $mhsA->id, 'jadwal_id' => $jadwalA->id, 'tanggal' => now()->toDateString(), 'waktu_tap' => '08:05:00', 'metode_absensi' => 'RFID', 'status' => 'Hadir']);

        $kelasB = Kelas::create(['nama_kelas' => 'B']);
        $mkB = MataKuliah::create(['kode_mk' => 'BBB', 'nama_mk' => 'Course B', 'sks' => 3]);
        $dosenB = User::create(['name' => 'Dosen B', 'email' => 'dosenb@poltek.ac.id', 'password' => bcrypt('x'), 'role' => 'dosen']);
        $jadwalB = Jadwal::create([
            'kelas_id' => $kelasB->id, 'mata_kuliah_id' => $mkB->id, 'user_id' => $dosenB->id,
            'semester_akademik_id' => $semester->id, 'hari' => 'Senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '10:00:00',
        ]);
        $mhsB = Mahasiswa::create(['nim' => '2', 'nama' => 'Mhs B', 'kelas_id' => $kelasB->id]);
        Absensi::create(['mahasiswa_id' => $mhsB->id, 'jadwal_id' => $jadwalB->id, 'tanggal' => now()->toDateString(), 'waktu_tap' => '08:05:00', 'metode_absensi' => 'RFID', 'status' => 'Hadir']);

        Sanctum::actingAs($dosen, ['*']);

        $response = $this->getJson('/api/v1/reports/student-summary?semester_id=' . $semester->id);

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('nama')->all();
        $this->assertContains('Mhs A', $names);
        $this->assertNotContains('Mhs B', $names);
    }
}
