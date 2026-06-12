<?php

namespace App\Services;

use App\Models\SemesterAkademik;
use Carbon\Carbon;

class AcademicSemesterSequenceService
{
    public function createInitial(array $data): SemesterAkademik
    {
        return SemesterAkademik::create([
            'nama_semester' => $data['nama_semester'],
            'tahun_ajaran' => $data['tahun_ajaran'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'is_active' => true,
        ]);
    }

    public function createNext(): SemesterAkademik
    {
        $latest = SemesterAkademik::query()
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->firstOrFail();

        $nextStart = Carbon::parse($latest->tanggal_selesai)->addDay()->startOfDay();

        return SemesterAkademik::create([
            'nama_semester' => $this->nextSemesterName((string) $latest->nama_semester),
            'tahun_ajaran' => $this->nextAcademicYear((string) $latest->nama_semester, (string) $latest->tahun_ajaran),
            'tanggal_mulai' => $nextStart->toDateString(),
            'tanggal_selesai' => $nextStart->copy()->addMonthsNoOverflow(6)->subDay()->toDateString(),
            'is_active' => false,
        ]);
    }

    private function nextSemesterName(string $currentName): string
    {
        return str_contains(strtolower($currentName), 'ganjil')
            ? 'Semester Genap'
            : 'Semester Ganjil';
    }

    private function nextAcademicYear(string $currentName, string $currentYear): string
    {
        if (str_contains(strtolower($currentName), 'ganjil')) {
            return $currentYear;
        }

        if (preg_match('/^(\d{4})\/(\d{4})$/', $currentYear, $matches)) {
            return ((int) $matches[1] + 1) . '/' . ((int) $matches[2] + 1);
        }

        return $currentYear;
    }
}
