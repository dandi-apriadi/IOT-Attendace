<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\SemesterAkademik;
use App\Models\User;
use App\Services\SemesterPromotionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemesterPromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dry_run_lists_active_students_with_next_class_without_changing_database(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();

        $student = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $result = app(SemesterPromotionService::class)->preview();

        $this->assertSame(1, $result->eligible->count());
        $this->assertSame($student->id, $result->eligible->first()['mahasiswa']->id);
        $this->assertSame($kelasDua->id, $result->eligible->first()['target_kelas']->id);
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
        ]);
    }

    public function test_execute_promotes_only_active_unpaused_students_and_records_history(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();

        $eligible = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $paused = Mahasiswa::create([
            'nim' => '23022002',
            'nama' => 'Mahasiswa Ditahan',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
            'promotion_paused' => true,
        ]);

        $inactive = Mahasiswa::create([
            'nim' => '23022003',
            'nama' => 'Mahasiswa Nonaktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'nonaktif',
        ]);

        $result = app(SemesterPromotionService::class)->execute('tes kenaikan');

        $this->assertSame(1, $result->promoted);
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $eligible->id,
            'kelas_id' => $kelasDua->id,
            'semester_level' => 2,
        ]);
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $paused->id,
            'kelas_id' => $kelasSatu->id,
            'promotion_paused' => true,
        ]);
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $inactive->id,
            'kelas_id' => $kelasSatu->id,
            'status_akademik' => 'nonaktif',
        ]);
        $this->assertDatabaseHas('student_semester_promotions', [
            'mahasiswa_id' => $eligible->id,
            'from_kelas_id' => $kelasSatu->id,
            'to_kelas_id' => $kelasDua->id,
            'from_semester_level' => 1,
            'to_semester_level' => 2,
            'mode' => 'execute',
            'note' => 'tes kenaikan',
        ]);
    }

    public function test_blank_legacy_status_is_treated_as_active_for_named_students(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();

        $student = Mahasiswa::create([
            'nim' => '23022009',
            'nama' => 'Mahasiswa Data Lama',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
        ]);
        $student->forceFill(['status_akademik' => ''])->save();

        $result = app(SemesterPromotionService::class)->preview();

        $this->assertSame(1, $result->eligible->count());
        $this->assertSame($kelasDua->id, $result->eligible->first()['target_kelas']->id);
    }

    public function test_console_command_defaults_to_dry_run(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();

        $student = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $this->artisan('students:promote-semester')
            ->expectsOutputToContain('Mode: preview')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasSatu->id,
        ]);
        $this->assertDatabaseMissing('student_semester_promotions', [
            'mahasiswa_id' => $student->id,
            'to_kelas_id' => $kelasDua->id,
        ]);
    }

    public function test_console_command_execute_promotes_students(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();

        $student = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $this->artisan('students:promote-semester', ['--execute' => true, '--note' => 'akhir semester'])
            ->expectsOutputToContain('Mode: execute')
            ->expectsOutputToContain('Dipromosikan: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasDua->id,
            'semester_level' => 2,
        ]);
        $this->assertDatabaseHas('student_semester_promotions', [
            'mahasiswa_id' => $student->id,
            'note' => 'akhir semester',
        ]);
    }

    public function test_scheduler_runs_semester_promotion_in_execute_mode(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('students:promote-semester --execute --due-only')
            ->assertExitCode(0);
    }

    public function test_due_only_command_skips_before_active_semester_end_date(): void
    {
        Carbon::setTestNow('2026-06-12 00:31:00');
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();
        $this->makeActiveSemester('2026-02-01', '2026-06-30');

        $student = Mahasiswa::create([
            'nim' => '23022010',
            'nama' => 'Belum Waktunya',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $this->artisan('students:promote-semester', ['--execute' => true, '--due-only' => true])
            ->expectsOutputToContain('Belum waktunya kenaikan semester otomatis')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
        ]);
        $this->assertDatabaseMissing('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasDua->id,
        ]);
    }

    public function test_due_only_command_does_not_repeat_for_same_active_semester(): void
    {
        Carbon::setTestNow('2026-06-30 00:31:00');
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();
        $kelasTiga = Kelas::create([
            'nama_kelas' => 'TI-3A',
            'semester_level' => 3,
        ]);
        $kelasDua->update(['next_kelas_id' => $kelasTiga->id]);
        $this->makeActiveSemester('2026-02-01', '2026-06-30');

        $student = Mahasiswa::create([
            'nim' => '23022011',
            'nama' => 'Sekali Naik',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $this->artisan('students:promote-semester', ['--execute' => true, '--due-only' => true])
            ->expectsOutputToContain('Dipromosikan: 1')
            ->assertExitCode(0);

        Carbon::setTestNow('2026-07-01 00:31:00');

        $this->artisan('students:promote-semester', ['--execute' => true, '--due-only' => true])
            ->expectsOutputToContain('Kenaikan semester otomatis sudah pernah dijalankan')
            ->assertExitCode(0);

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasDua->id,
            'semester_level' => 2,
        ]);
        $this->assertDatabaseMissing('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasTiga->id,
        ]);
    }

    public function test_execute_is_idempotent_for_students_promoted_today(): void
    {
        [$kelasSatu, $kelasDua] = $this->makePromotionClasses();
        $kelasTiga = Kelas::create([
            'nama_kelas' => 'TI-3A',
            'semester_level' => 3,
        ]);
        $kelasDua->update(['next_kelas_id' => $kelasTiga->id]);

        $student = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $service = app(SemesterPromotionService::class);

        $first = $service->execute('klik pertama');
        $second = $service->execute('klik kedua');

        $this->assertSame(1, $first->promoted);
        $this->assertSame(0, $second->promoted);
        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'kelas_id' => $kelasDua->id,
            'semester_level' => 2,
        ]);
        $this->assertSame(1, \App\Models\StudentSemesterPromotion::where('mahasiswa_id', $student->id)->count());
    }

    public function test_admin_can_access_semester_promotion_preview_page(): void
    {
        $this->makePromotionClasses();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('semester.promotion'))
            ->assertStatus(200)
            ->assertSee('Review Kenaikan Semester')
            ->assertSee('Kandidat Siap Naik')
            ->assertSee('Perlu Dicek Admin');
    }

    public function test_admin_can_update_student_academic_status_and_promotion_hold(): void
    {
        [$kelasSatu] = $this->makePromotionClasses();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $student = Mahasiswa::create([
            'nim' => '23022001',
            'nama' => 'Mahasiswa Aktif',
            'kelas_id' => $kelasSatu->id,
            'semester_level' => 1,
            'status_akademik' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->put(route('mahasiswa.update', $student), [
                'nim' => '23022001',
                'nama' => 'Mahasiswa Aktif',
                'kelas_id' => $kelasSatu->id,
                'status_akademik' => 'nonaktif',
                'semester_level' => 1,
                'promotion_paused' => '1',
                'promotion_note' => 'Cuti sementara',
            ])
            ->assertRedirect(route('mahasiswa'));

        $this->assertDatabaseHas('mahasiswa', [
            'id' => $student->id,
            'status_akademik' => 'nonaktif',
            'promotion_paused' => true,
            'promotion_note' => 'Cuti sementara',
        ]);
    }

    /**
     * @return array{0: Kelas, 1: Kelas}
     */
    private function makePromotionClasses(): array
    {
        $kelasSatu = Kelas::create([
            'nama_kelas' => 'TI-1A',
            'semester_level' => 1,
        ]);

        $kelasDua = Kelas::create([
            'nama_kelas' => 'TI-2A',
            'semester_level' => 2,
        ]);

        $kelasSatu->update(['next_kelas_id' => $kelasDua->id]);

        return [$kelasSatu->fresh(), $kelasDua];
    }

    private function makeActiveSemester(string $startDate, string $endDate): SemesterAkademik
    {
        return SemesterAkademik::create([
            'nama_semester' => 'Semester Genap',
            'tahun_ajaran' => '2025/2026',
            'tanggal_mulai' => $startDate,
            'tanggal_selesai' => $endDate,
            'is_active' => true,
        ]);
    }
}
