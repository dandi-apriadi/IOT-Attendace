<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\MataKuliahDosenAssignment;
use Illuminate\Http\Request;

trait ScopesByDosen
{
    /**
     * Returns null for admin (no scoping), or the list of mata_kuliah ids
     * assigned to the authenticated dosen.
     *
     * @return array<int, int>|null
     */
    private function allowedMataKuliahIds(Request $request): ?array
    {
        $user = $request->user();

        if (! $user || $user->role !== 'dosen') {
            return null;
        }

        return MataKuliahDosenAssignment::query()
            ->where('user_id', $user->id)
            ->pluck('mata_kuliah_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
