<?php

namespace App\Console\Commands;

use App\Models\SemesterAkademik;
use App\Models\StudentSemesterPromotion;
use App\Services\SemesterPromotionService;
use Illuminate\Console\Command;

class PromoteStudentSemesters extends Command
{
    protected $signature = 'students:promote-semester
        {--execute : Terapkan kenaikan semester. Tanpa flag ini hanya preview}
        {--due-only : Eksekusi otomatis hanya saat semester aktif sudah mencapai tanggal selesai}
        {--kelas= : Batasi ke ID kelas tertentu}
        {--note= : Catatan yang disimpan pada histori promosi}';

    protected $description = 'Preview atau eksekusi kenaikan semester mahasiswa aktif berdasarkan mapping kelas berikutnya';

    public function handle(SemesterPromotionService $service): int
    {
        $kelasId = $this->option('kelas') !== null ? (int) $this->option('kelas') : null;
        $execute = (bool) $this->option('execute');
        $dueOnly = (bool) $this->option('due-only');
        $note = $this->option('note') !== null ? (string) $this->option('note') : null;
        $mode = 'execute';

        if ($dueOnly) {
            $activeSemester = SemesterAkademik::query()
                ->where('is_active', true)
                ->orderByDesc('tanggal_mulai')
                ->first();

            if (! $activeSemester) {
                $this->warn('Belum ada semester aktif untuk kenaikan semester otomatis.');
                return self::SUCCESS;
            }

            $dueDate = $activeSemester->tanggal_selesai?->copy()->startOfDay();
            if (! $dueDate || now()->copy()->startOfDay()->lt($dueDate)) {
                $this->info('Belum waktunya kenaikan semester otomatis. Semester aktif selesai pada ' . ($activeSemester->tanggal_selesai?->toDateString() ?? '-'));
                return self::SUCCESS;
            }

            $alreadyExecuted = StudentSemesterPromotion::query()
                ->where('mode', 'auto')
                ->where('promoted_at', '>=', $dueDate)
                ->exists();

            if ($alreadyExecuted) {
                $this->info('Kenaikan semester otomatis sudah pernah dijalankan untuk semester aktif ini.');
                return self::SUCCESS;
            }

            $execute = true;
            $mode = 'auto';
            $note ??= 'Kenaikan semester otomatis setelah ' . $activeSemester->display_name;
        }

        $result = $execute
            ? $service->execute($note, $kelasId, $mode)
            : $service->preview($kelasId);

        $this->info('Mode: ' . ($execute ? 'execute' : 'preview'));
        $this->info('Kandidat naik: ' . $result->eligible->count());
        $this->info('Ditahan/bermasalah: ' . $result->blocked->count());

        if ($execute) {
            $this->info('Dipromosikan: ' . $result->promoted);
        }

        if ($result->eligible->isNotEmpty()) {
            $this->table(
                ['NIM', 'Nama', 'Dari Kelas', 'Ke Kelas', 'Semester Baru'],
                $result->eligible->map(function (array $item): array {
                    return [
                        $item['mahasiswa']->nim,
                        $item['mahasiswa']->nama,
                        $item['mahasiswa']->kelas?->nama_kelas ?? '-',
                        $item['target_kelas']->nama_kelas ?? '-',
                        $item['target_semester_level'],
                    ];
                })->all()
            );
        }

        if ($result->blocked->isNotEmpty()) {
            $this->warn('Mahasiswa yang perlu dicek admin:');
            $this->table(
                ['NIM', 'Nama', 'Kelas', 'Alasan'],
                $result->blocked->map(function (array $item): array {
                    return [
                        $item['mahasiswa']->nim,
                        $item['mahasiswa']->nama,
                        $item['mahasiswa']->kelas?->nama_kelas ?? '-',
                        $item['reason'],
                    ];
                })->all()
            );
        }

        return self::SUCCESS;
    }
}
