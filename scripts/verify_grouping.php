<?php

use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Support\Facades\Request;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Test as Dosen Utama
$dosen = User::where('email', 'dosen@admin.com')->first();
if (!$dosen) {
    echo "Dosen Utama not found!\n";
    exit(1);
}

echo "Testing as Dosen Utama (ID: {$dosen->id})\n";

$query = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah'])
    ->where('user_id', $dosen->id)
    ->orderByDesc('semester_akademik_id')
    ->get();

$groupedSchedules = $query->groupBy(function (Jadwal $jadwal): string {
        return $jadwal->semesterAkademik?->display_name ?? 'Belum ditentukan';
    })
    ->map(function ($items, string $semesterLabel): array {
        return [
            'semester' => $semesterLabel,
            'total' => $items->count(),
            'items' => $items->values(),
        ];
    })
    ->values();

echo "Grouped Semesters: " . $groupedSchedules->count() . "\n";
foreach ($groupedSchedules as $group) {
    echo "- Semester: {$group['semester']} (Total: {$group['total']})\n";
    foreach ($group['items'] as $item) {
        echo "  * {$item->mata_kuliah->nama_mk} ({$item->kelas->nama_kelas}) - {$item->hari}\n";
    }
}

// 2. Test as Admin (seeing all)
$admin = User::where('role', 'admin')->first();
echo "\nTesting as Admin (ID: {$admin->id})\n";

$adminQuery = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah'])
    ->orderByDesc('semester_akademik_id')
    ->get();

$adminGrouped = $adminQuery->groupBy(function (Jadwal $jadwal): string {
        return $jadwal->semesterAkademik?->display_name ?? 'Belum ditentukan';
    });

echo "Total Groups for Admin: " . $adminGrouped->count() . "\n";
foreach ($adminGrouped as $label => $items) {
    echo "- {$label}: {$items->count()} items\n";
}
