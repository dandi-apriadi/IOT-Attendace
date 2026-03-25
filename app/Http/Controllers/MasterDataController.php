<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function kelas(): View
    {
        $kelasList = Kelas::withCount('mahasiswa')
            ->orderBy('nama_kelas')
            ->paginate(12);

        return view('master.kelas', [
            'kelasList' => $kelasList,
        ]);
    }

    public function mataKuliah(): View
    {
        $mataKuliahList = MataKuliah::withCount('jadwal')
            ->orderBy('kode_mk')
            ->paginate(12);

        return view('master.matakuliah', [
            'mataKuliahList' => $mataKuliahList,
        ]);
    }

    public function jadwal(): View
    {
        $jadwalList = Jadwal::with(['kelas', 'mata_kuliah', 'dosen'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(20);

        return view('master.jadwal', [
            'jadwalList' => $jadwalList,
        ]);
    }
}
