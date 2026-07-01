<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesByDosen;
use App\Http\Resources\JadwalResource;
use App\Http\Resources\KelasResource;
use App\Http\Resources\MataKuliahResource;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    use ScopesByDosen;

    public function kelas(): JsonResponse
    {
        $kelas = Kelas::query()->orderBy('nama_kelas')->get();

        return response()->json(['data' => KelasResource::collection($kelas)]);
    }

    public function mataKuliah(Request $request): JsonResponse
    {
        $query = MataKuliah::query()->orderBy('nama_mk');

        $allowedIds = $this->allowedMataKuliahIds($request);
        if ($allowedIds !== null) {
            $query->whereIn('id', $allowedIds);
        }

        return response()->json(['data' => MataKuliahResource::collection($query->get())]);
    }

    public function jadwal(Request $request): JsonResponse
    {
        $query = Jadwal::query()->with(['kelas:id,nama_kelas', 'mata_kuliah:id,kode_mk,nama_mk', 'dosen:id,name']);

        $allowedIds = $this->allowedMataKuliahIds($request);
        if ($allowedIds !== null) {
            $query->whereIn('mata_kuliah_id', $allowedIds);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->query('kelas_id'));
        }

        return response()->json(['data' => JadwalResource::collection($query->orderBy('hari')->orderBy('jam_mulai')->get())]);
    }
}
