<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\StudentSemesterPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SemesterPromotionService
{
    public const STATUS_ACTIVE = 'aktif';

    public function preview(?int $kelasId = null): SemesterPromotionResult
    {
        $eligible = collect();
        $blocked = collect();

        $this->baseQuery($kelasId)
            ->with(['kelas.nextKelas'])
            ->orderBy('nama')
            ->get()
            ->each(function (Mahasiswa $mahasiswa) use ($eligible, $blocked): void {
                $targetKelas = $mahasiswa->kelas?->nextKelas;

                if (! $this->isEligible($mahasiswa)) {
                    $blocked->push([
                        'mahasiswa' => $mahasiswa,
                        'reason' => $this->blockedReason($mahasiswa),
                    ]);

                    return;
                }

                if (! $targetKelas) {
                    $blocked->push([
                        'mahasiswa' => $mahasiswa,
                        'reason' => 'Kelas berikutnya belum diatur.',
                    ]);

                    return;
                }

                $eligible->push([
                    'mahasiswa' => $mahasiswa,
                    'target_kelas' => $targetKelas,
                    'target_semester_level' => $this->targetSemesterLevel($mahasiswa, $targetKelas->semester_level),
                ]);
            });

        return new SemesterPromotionResult($eligible, $blocked);
    }

    public function execute(?string $note = null, ?int $kelasId = null, string $mode = 'execute'): SemesterPromotionResult
    {
        return DB::transaction(function () use ($note, $kelasId, $mode): SemesterPromotionResult {
            $preview = $this->preview($kelasId);
            $promoted = 0;
            $promotedAt = now();

            foreach ($preview->eligible as $item) {
                /** @var Mahasiswa $mahasiswa */
                $mahasiswa = $item['mahasiswa'];
                $targetKelas = $item['target_kelas'];
                $fromKelasId = $mahasiswa->kelas_id;
                $fromSemesterLevel = $mahasiswa->semester_level;
                $toSemesterLevel = $item['target_semester_level'];

                $mahasiswa->forceFill([
                    'kelas_id' => $targetKelas->id,
                    'semester_level' => $toSemesterLevel,
                    'last_promoted_at' => $promotedAt,
                ])->save();

                StudentSemesterPromotion::create([
                    'mahasiswa_id' => $mahasiswa->id,
                    'from_kelas_id' => $fromKelasId,
                    'to_kelas_id' => $targetKelas->id,
                    'from_semester_level' => $fromSemesterLevel,
                    'to_semester_level' => $toSemesterLevel,
                    'mode' => $mode,
                    'note' => $note,
                    'promoted_at' => $promotedAt,
                ]);

                $promoted++;
            }

            return new SemesterPromotionResult($preview->eligible, $preview->blocked, $promoted);
        });
    }

    private function baseQuery(?int $kelasId): Builder
    {
        return Mahasiswa::query()
            ->when($kelasId, fn (Builder $query): Builder => $query->where('kelas_id', $kelasId));
    }

    private function isEligible(Mahasiswa $mahasiswa): bool
    {
        return $this->normalizedStatus($mahasiswa) === self::STATUS_ACTIVE
            && ! $mahasiswa->promotion_paused
            && ! $this->wasPromotedToday($mahasiswa)
            && trim((string) $mahasiswa->nama) !== '';
    }

    private function blockedReason(Mahasiswa $mahasiswa): string
    {
        if ($this->normalizedStatus($mahasiswa) !== self::STATUS_ACTIVE) {
            return 'Status mahasiswa bukan aktif.';
        }

        if ($mahasiswa->promotion_paused) {
            return $mahasiswa->promotion_note ?: 'Kenaikan semester ditahan admin.';
        }

        if ($this->wasPromotedToday($mahasiswa)) {
            return 'Sudah dinaikkan hari ini.';
        }

        return 'Nama mahasiswa belum lengkap.';
    }

    private function normalizedStatus(Mahasiswa $mahasiswa): string
    {
        $status = strtolower(trim((string) $mahasiswa->status_akademik));

        return $status !== '' ? $status : self::STATUS_ACTIVE;
    }

    private function wasPromotedToday(Mahasiswa $mahasiswa): bool
    {
        return $mahasiswa->last_promoted_at !== null
            && $mahasiswa->last_promoted_at->isSameDay(now());
    }

    private function targetSemesterLevel(Mahasiswa $mahasiswa, ?int $targetKelasLevel): int
    {
        if ($targetKelasLevel !== null) {
            return $targetKelasLevel;
        }

        return ((int) ($mahasiswa->semester_level ?: 0)) + 1;
    }
}
