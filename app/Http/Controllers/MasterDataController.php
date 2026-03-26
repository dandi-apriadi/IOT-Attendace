<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function storeMataKuliah(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_mk' => ['required', 'string', 'max:20', 'unique:mata_kuliah,kode_mk'],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $mk = MataKuliah::create($data);

        AuditLogger::log(
            $request,
            'tambah_matakuliah',
            'Menambahkan mata kuliah ' . $mk->nama_mk . ' (' . $mk->kode_mk . ')',
            auth()->id()
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function editMataKuliah(string $id): View
    {
        $mk = MataKuliah::findOrFail($id);
        return view('master.matakuliah-edit', [
            'mk' => $mk,
        ]);
    }

    public function updateMataKuliah(Request $request, string $id): RedirectResponse
    {
        $mk = MataKuliah::findOrFail($id);
        
        $data = $request->validate([
            'kode_mk' => ['required', 'string', 'max:20', Rule::unique('mata_kuliah')->ignore($mk->id)],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $mk->update($data);

        AuditLogger::log(
            $request,
            'update_matakuliah',
            'Memperbarui mata kuliah ' . $mk->nama_mk . ' (' . $mk->kode_mk . ')',
            auth()->id()
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroyMataKuliah(Request $request, string $id): RedirectResponse
    {
        $mk = MataKuliah::findOrFail($id);
        
        // Optional: Check if used in Jadwal
        if ($mk->jadwal()->count() > 0) {
            return redirect()->route('matakuliah')->with('error', 'Tidak dapat menghapus mata kuliah yang sudah ada di jadwal kuliah.');
        }

        $mkName = $mk->nama_mk;
        $mkCode = $mk->kode_mk;
        $mk->delete();

        AuditLogger::log(
            $request,
            'hapus_matakuliah',
            'Menghapus mata kuliah ' . $mkName . ' (' . $mkCode . ')',
            auth()->id()
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
