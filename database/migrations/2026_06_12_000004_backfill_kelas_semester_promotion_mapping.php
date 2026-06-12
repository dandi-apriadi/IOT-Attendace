<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $classes = DB::table('kelas')->get(['id', 'nama_kelas']);
        $parsed = [];

        foreach ($classes as $kelas) {
            if (! preg_match('/^(.*?)(\d+)([A-Za-z]*)$/', trim((string) $kelas->nama_kelas), $matches)) {
                continue;
            }

            $prefix = strtoupper(trim($matches[1]));
            $level = (int) $matches[2];
            $suffix = strtoupper($matches[3] ?? '');
            $key = $prefix . '|' . $level . '|' . $suffix;

            $parsed[$key] = [
                'id' => $kelas->id,
                'prefix' => $prefix,
                'level' => $level,
                'suffix' => $suffix,
            ];
        }

        foreach ($parsed as $item) {
            $nextKey = $item['prefix'] . '|' . ($item['level'] + 1) . '|' . $item['suffix'];
            DB::table('kelas')
                ->where('id', $item['id'])
                ->update([
                    'semester_level' => $item['level'],
                    'next_kelas_id' => $parsed[$nextKey]['id'] ?? null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        DB::table('kelas')->update([
            'semester_level' => null,
            'next_kelas_id' => null,
            'updated_at' => now(),
        ]);
    }
};
