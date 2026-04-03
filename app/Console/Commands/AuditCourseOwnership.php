<?php

namespace App\Console\Commands;

use App\Models\MataKuliahDosenAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCourseOwnership extends Command
{
    protected $signature = 'audit:course-ownership
        {--fix : Apply safe automatic fixes for mismatches}
        {--fallback-dosen-id= : Lecturer user ID used when no single safe candidate can be inferred}';

    protected $description = 'Audit one-course-one-lecturer ownership consistency between assignments and schedules';

    public function handle(): int
    {
        $shouldFix = (bool) $this->option('fix');
        $fallbackDosenIdOption = $this->option('fallback-dosen-id');
        $fallbackDosenId = $fallbackDosenIdOption !== null && $fallbackDosenIdOption !== ''
            ? (int) $fallbackDosenIdOption
            : null;

        $this->info('Starting ownership audit...');
        $this->line('Mode: ' . ($shouldFix ? 'FIX' : 'READ-ONLY'));

        $courseStats = DB::table('jadwal as j')
            ->leftJoin('mata_kuliah as mk', 'mk.id', '=', 'j.mata_kuliah_id')
            ->leftJoin('mata_kuliah_dosen_assignments as a', 'a.mata_kuliah_id', '=', 'j.mata_kuliah_id')
            ->selectRaw('j.mata_kuliah_id, mk.kode_mk, mk.nama_mk, a.user_id as assignment_user_id, COUNT(*) as total_jadwal, COUNT(DISTINCT j.user_id) as distinct_jadwal_user_count')
            ->groupBy('j.mata_kuliah_id', 'mk.kode_mk', 'mk.nama_mk', 'a.user_id')
            ->orderBy('mk.nama_mk')
            ->get();

        if ($courseStats->isEmpty()) {
            $this->warn('No schedule data found in jadwal.');
            return self::SUCCESS;
        }

        $missingAssignments = [];
        $multipleLecturerCourses = [];
        $mismatchCourses = [];
        $invalidAssignmentCourses = [];

        foreach ($courseStats as $row) {
            $courseUserIds = DB::table('jadwal')
                ->where('mata_kuliah_id', $row->mata_kuliah_id)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            $assignmentUserId = $row->assignment_user_id !== null ? (int) $row->assignment_user_id : null;
            $assignmentRole = null;

            if ($assignmentUserId !== null) {
                $assignmentRole = DB::table('users')
                    ->where('id', $assignmentUserId)
                    ->value('role');
            }

            if ($assignmentUserId === null) {
                $missingAssignments[] = [
                    'mata_kuliah_id' => (int) $row->mata_kuliah_id,
                    'kode_mk' => (string) ($row->kode_mk ?? '-'),
                    'nama_mk' => (string) ($row->nama_mk ?? '-'),
                    'distinct_users' => $courseUserIds->implode(','),
                ];
            }

            if ($courseUserIds->count() > 1) {
                $multipleLecturerCourses[] = [
                    'mata_kuliah_id' => (int) $row->mata_kuliah_id,
                    'kode_mk' => (string) ($row->kode_mk ?? '-'),
                    'nama_mk' => (string) ($row->nama_mk ?? '-'),
                    'jadwal_user_ids' => $courseUserIds->implode(','),
                    'assignment_user_id' => $assignmentUserId !== null ? (string) $assignmentUserId : '-',
                ];
            }

            if ($assignmentUserId !== null) {
                $mismatchCount = DB::table('jadwal')
                    ->where('mata_kuliah_id', $row->mata_kuliah_id)
                    ->where(function ($q) use ($assignmentUserId) {
                        $q->whereNull('user_id')
                            ->orWhere('user_id', '!=', $assignmentUserId);
                    })
                    ->count();

                if ($mismatchCount > 0) {
                    $mismatchCourses[] = [
                        'mata_kuliah_id' => (int) $row->mata_kuliah_id,
                        'kode_mk' => (string) ($row->kode_mk ?? '-'),
                        'nama_mk' => (string) ($row->nama_mk ?? '-'),
                        'assignment_user_id' => (string) $assignmentUserId,
                        'mismatch_rows' => $mismatchCount,
                    ];
                }

                if ($assignmentRole !== 'dosen') {
                    $roleMap = DB::table('jadwal as j')
                        ->leftJoin('users as u', 'u.id', '=', 'j.user_id')
                        ->where('j.mata_kuliah_id', $row->mata_kuliah_id)
                        ->selectRaw("GROUP_CONCAT(DISTINCT CONCAT(COALESCE(j.user_id, 0), ':', COALESCE(u.role, 'null')) ORDER BY j.user_id SEPARATOR '|') as role_map")
                        ->value('role_map');

                    $candidateDosenIds = DB::table('jadwal as j')
                        ->join('users as u', 'u.id', '=', 'j.user_id')
                        ->where('j.mata_kuliah_id', $row->mata_kuliah_id)
                        ->where('u.role', 'dosen')
                        ->whereNotNull('j.user_id')
                        ->distinct()
                        ->pluck('j.user_id')
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $invalidAssignmentCourses[] = [
                        'mata_kuliah_id' => (int) $row->mata_kuliah_id,
                        'kode_mk' => (string) ($row->kode_mk ?? '-'),
                        'nama_mk' => (string) ($row->nama_mk ?? '-'),
                        'assignment_user_id' => (string) $assignmentUserId,
                        'assignment_role' => (string) ($assignmentRole ?? 'unknown'),
                        'jadwal_user_roles' => (string) ($roleMap ?? '-'),
                        'candidate_dosen_ids' => $candidateDosenIds->isNotEmpty() ? $candidateDosenIds->implode(',') : '-',
                    ];
                }
            }
        }

        $this->newLine();
        $this->info('Audit summary');
        $this->line('- Courses checked: ' . $courseStats->count());
        $this->line('- Missing assignments: ' . count($missingAssignments));
        $this->line('- Multi-lecturer courses in jadwal: ' . count($multipleLecturerCourses));
        $this->line('- Courses with row mismatches vs assignment: ' . count($mismatchCourses));
        $this->line('- Courses with non-dosen assignment owner: ' . count($invalidAssignmentCourses));

        if (! empty($missingAssignments)) {
            $this->newLine();
            $this->warn('Missing assignments');
            $this->table(
                ['mata_kuliah_id', 'kode_mk', 'nama_mk', 'distinct_users'],
                $missingAssignments
            );
        }

        if (! empty($multipleLecturerCourses)) {
            $this->newLine();
            $this->warn('Courses with >1 lecturer in jadwal');
            $this->table(
                ['mata_kuliah_id', 'kode_mk', 'nama_mk', 'jadwal_user_ids', 'assignment_user_id'],
                $multipleLecturerCourses
            );
        }

        if (! empty($mismatchCourses)) {
            $this->newLine();
            $this->warn('Courses with mismatched jadwal rows');
            $this->table(
                ['mata_kuliah_id', 'kode_mk', 'nama_mk', 'assignment_user_id', 'mismatch_rows'],
                $mismatchCourses
            );
        }

        if (! empty($invalidAssignmentCourses)) {
            $this->newLine();
            $this->warn('Courses with non-dosen assignment owner');
            $this->table(
                ['mata_kuliah_id', 'kode_mk', 'nama_mk', 'assignment_user_id', 'assignment_role', 'jadwal_user_roles', 'candidate_dosen_ids'],
                $invalidAssignmentCourses
            );
        }

        if (! $shouldFix) {
            $this->newLine();
            $this->line('Run with --fix to apply safe automatic corrections.');
            return self::SUCCESS;
        }

        if ($fallbackDosenId !== null && ! $this->isValidDosenId($fallbackDosenId)) {
            $this->error('Invalid --fallback-dosen-id. The provided user ID is not a valid dosen user.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Applying automatic fixes...');

        $fixStats = [
            'assignments_created' => 0,
            'assignments_reassigned_to_dosen' => 0,
            'jadwal_rows_aligned' => 0,
            'fix_skipped_ambiguous' => 0,
        ];
        $skippedAmbiguousDetails = [];

        DB::transaction(function () use (&$fixStats, &$skippedAmbiguousDetails, $fallbackDosenId): void {
            $missingByCourse = DB::table('jadwal as j')
                ->leftJoin('mata_kuliah_dosen_assignments as a', 'a.mata_kuliah_id', '=', 'j.mata_kuliah_id')
                ->whereNull('a.id')
                ->select('j.mata_kuliah_id')
                ->distinct()
                ->pluck('mata_kuliah_id')
                ->map(fn ($id) => (int) $id);

            foreach ($missingByCourse as $mataKuliahId) {
                $candidateDosenIds = DB::table('jadwal as j')
                    ->join('users as u', 'u.id', '=', 'j.user_id')
                    ->where('j.mata_kuliah_id', $mataKuliahId)
                    ->where('u.role', 'dosen')
                    ->whereNotNull('j.user_id')
                    ->distinct()
                    ->pluck('j.user_id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($candidateDosenIds->count() !== 1) {
                    if ($fallbackDosenId !== null) {
                        MataKuliahDosenAssignment::query()->updateOrCreate(
                            ['mata_kuliah_id' => $mataKuliahId],
                            ['user_id' => $fallbackDosenId]
                        );

                        $fixStats['assignments_reassigned_to_dosen']++;
                        continue;
                    }

                    $fixStats['fix_skipped_ambiguous']++;
                    $skippedAmbiguousDetails[] = [
                        'mata_kuliah_id' => $mataKuliahId,
                        'reason' => 'missing-assignment',
                        'candidate_dosen_ids' => $candidateDosenIds->isNotEmpty() ? $candidateDosenIds->implode(',') : '-',
                    ];
                    continue;
                }

                MataKuliahDosenAssignment::query()->updateOrCreate(
                    ['mata_kuliah_id' => $mataKuliahId],
                    ['user_id' => (int) $candidateDosenIds->first()]
                );

                $fixStats['assignments_created']++;
            }

            $invalidOwnerAssignments = MataKuliahDosenAssignment::query()
                ->join('users', 'users.id', '=', 'mata_kuliah_dosen_assignments.user_id')
                ->where('users.role', '!=', 'dosen')
                ->select('mata_kuliah_dosen_assignments.mata_kuliah_id')
                ->get();

            foreach ($invalidOwnerAssignments as $invalidOwnerAssignment) {
                $mataKuliahId = (int) $invalidOwnerAssignment->mata_kuliah_id;

                $candidateDosenIds = DB::table('jadwal as j')
                    ->join('users as u', 'u.id', '=', 'j.user_id')
                    ->where('j.mata_kuliah_id', $mataKuliahId)
                    ->where('u.role', 'dosen')
                    ->whereNotNull('j.user_id')
                    ->distinct()
                    ->pluck('j.user_id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                if ($candidateDosenIds->count() !== 1) {
                    if ($fallbackDosenId !== null) {
                        MataKuliahDosenAssignment::query()
                            ->where('mata_kuliah_id', $mataKuliahId)
                            ->update(['user_id' => $fallbackDosenId]);

                        $fixStats['assignments_reassigned_to_dosen']++;
                        continue;
                    }

                    $fixStats['fix_skipped_ambiguous']++;
                    $skippedAmbiguousDetails[] = [
                        'mata_kuliah_id' => $mataKuliahId,
                        'reason' => 'invalid-owner-assignment',
                        'candidate_dosen_ids' => $candidateDosenIds->isNotEmpty() ? $candidateDosenIds->implode(',') : '-',
                    ];
                    continue;
                }

                MataKuliahDosenAssignment::query()
                    ->where('mata_kuliah_id', $mataKuliahId)
                    ->update(['user_id' => (int) $candidateDosenIds->first()]);

                $fixStats['assignments_reassigned_to_dosen']++;
            }

            $assignments = MataKuliahDosenAssignment::query()
                ->select('mata_kuliah_id', 'user_id')
                ->get();

            foreach ($assignments as $assignment) {
                $updatedRows = DB::table('jadwal')
                    ->where('mata_kuliah_id', $assignment->mata_kuliah_id)
                    ->where(function ($q) use ($assignment) {
                        $q->whereNull('user_id')
                            ->orWhere('user_id', '!=', $assignment->user_id);
                    })
                    ->update(['user_id' => $assignment->user_id]);

                $fixStats['jadwal_rows_aligned'] += (int) $updatedRows;
            }
        });

        $this->newLine();
        $this->info('Fix summary');
        $this->line('- Assignments created: ' . $fixStats['assignments_created']);
        $this->line('- Assignments reassigned to dosen: ' . $fixStats['assignments_reassigned_to_dosen']);
        $this->line('- Jadwal rows aligned: ' . $fixStats['jadwal_rows_aligned']);
        $this->line('- Ambiguous courses skipped: ' . $fixStats['fix_skipped_ambiguous']);

        if (! empty($skippedAmbiguousDetails)) {
            $this->newLine();
            $this->warn('Skipped ambiguous courses (manual review needed)');
            $this->table(
                ['mata_kuliah_id', 'reason', 'candidate_dosen_ids'],
                $skippedAmbiguousDetails
            );
        }

        return self::SUCCESS;
    }

    private function isValidDosenId(int $userId): bool
    {
        return DB::table('users')
            ->where('id', $userId)
            ->where('role', 'dosen')
            ->exists();
    }
}
