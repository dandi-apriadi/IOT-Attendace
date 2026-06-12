<?php

namespace App\Console\Commands;

use App\Services\SemesterPromotionService;
use Illuminate\Console\Command;

class PromoteStudentSemesters extends Command
{
    protected $signature = 'students:promote-semester
        {--execute : Terapkan kenaikan semester. Tanpa flag ini hanya preview}
        {--kelas= : Batasi ke ID kelas tertentu}
        {--note= : Catatan yang disimpan pada histori promosi}';

    protected $description = 'Preview atau eksekusi kenaikan semester mahasiswa aktif berdasarkan mapping kelas berikutnya';

    public function handle(SemesterPromotionService $service): int
    {
        $kelasId = $this->option('kelas') !== null ? (int) $this->option('kelas') : null;
        $execute = (bool) $this->option('execute');
        $note = $this->option('note') !== null ? (string) $this->option('note') : null;

        $result = $execute
            ? $service->execute($note, $kelasId)
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
