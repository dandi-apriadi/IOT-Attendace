<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\User;
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
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'dosenList' => User::whereIn('role', ['admin', 'dosen'])->orderBy('name')->get(),
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

    public function storeKelas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas,nama_kelas'],
        ]);

        $kelas = Kelas::create($data);

        AuditLogger::log(
            $request,
            'tambah_kelas',
            'Menambahkan kelas ' . $kelas->nama_kelas,
            auth()->id()
        );

        return redirect()->route('kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function editKelas(string $id): View
    {
        $kelas = Kelas::findOrFail($id);
        return view('master.kelas-edit', [
            'kelas' => $kelas,
        ]);
    }

    public function updateKelas(Request $request, string $id): RedirectResponse
    {
        $kelas = Kelas::findOrFail($id);
        
        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', Rule::unique('kelas')->ignore($kelas->id)],
        ]);

        $kelas->update($data);

        AuditLogger::log(
            $request,
            'update_kelas',
            'Memperbarui kelas ' . $kelas->nama_kelas,
            auth()->id()
        );

        return redirect()->route('kelas')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroyKelas(Request $request, string $id): RedirectResponse
    {
        $kelas = Kelas::findOrFail($id);
        
        // Safety checks
        if ($kelas->mahasiswa()->count() > 0) {
            return redirect()->route('kelas')->with('error', 'Tidak dapat menghapus kelas yang masih memiliki mahasiswa.');
        }

        if ($kelas->jadwal()->count() > 0) {
            return redirect()->route('kelas')->with('error', 'Tidak dapat menghapus kelas yang sudah terdaftar dalam jadwal kuliah.');
        }

        $kelasName = $kelas->nama_kelas;
        $kelas->delete();

        AuditLogger::log(
            $request,
            'hapus_kelas',
            'Menghapus kelas ' . $kelasName,
            auth()->id()
        );

        return redirect()->route('kelas')->with('success', 'Kelas berhasil dihapus.');
    }

    public function storeJadwal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', 'exists:users,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        $jadwal = Jadwal::create($data);

        AuditLogger::log(
            $request,
            'tambah_jadwal',
            'Menambahkan jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' untuk kelas ' . $jadwal->kelas?->nama_kelas,
            auth()->id()
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function editJadwal(string $id): View
    {
        $jadwal = Jadwal::findOrFail($id);
        return view('master.jadwal-edit', [
            'jadwal' => $jadwal,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'dosenList' => User::whereIn('role', ['admin', 'dosen'])->orderBy('name')->get(),
        ]);
    }

    public function updateJadwal(Request $request, string $id): RedirectResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', 'exists:users,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        $jadwal->update($data);

        AuditLogger::log(
            $request,
            'update_jadwal',
            'Memperbarui jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' kelas ' . $jadwal->kelas?->nama_kelas,
            auth()->id()
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroyJadwal(Request $request, string $id): RedirectResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        
        // Safety check: Don't delete if there is attendance data
        if ($jadwal->absensi()->count() > 0) {
            return redirect()->route('jadwal')->with('error', 'Tidak dapat menghapus jadwal yang sudah memiliki data absensi.');
        }

        $info = $jadwal->mata_kuliah?->nama_mk . ' - ' . $jadwal->kelas?->nama_kelas;
        $jadwal->delete();

        AuditLogger::log(
            $request,
            'hapus_jadwal',
            'Menghapus jadwal ' . $info,
            auth()->id()
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dihapus.');
    }
}
