<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\View\View;

class DosenSessionController extends Controller
{
    public function create(): View
    {
        // Fetch all kelas and mata_kuliah for dropdown options
        $kelasOptions = Kelas::all()->map(fn ($k) => [
            'id' => $k->id,
            'label' => $k->nama_kelas,
        ])->values();
        
        $mataKuliahOptions = MataKuliah::all()->map(fn ($mk) => [
            'id' => $mk->id,
            'label' => "{$mk->nama_mk} ({$mk->kode_mk})",
        ])->values();
        
        return view('dosen.session', [
            'kelasOptions' => $kelasOptions,
            'mataKuliahOptions' => $mataKuliahOptions,
        ]);
    }
}
