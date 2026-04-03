<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $semesterList = SemesterAkademik::orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $activeSemesterId = (int) ($semesterList->firstWhere('is_active', true)?->id ?? $semesterList->first()?->id ?? 0);

        $jadwalList = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah', 'dosen'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(20);

        return view('master.jadwal', [
            'jadwalList' => $jadwalList,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'dosenList' => User::where('role', 'dosen')->orderBy('name')->get(),
            'semesterList' => $semesterList,
            'activeSemesterId' => $activeSemesterId,
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
            $request->user()?->id
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
            $request->user()?->id
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
            $request->user()?->id
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
            $request->user()?->id
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
            $request->user()?->id
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
            $request->user()?->id
        );

        return redirect()->route('kelas')->with('success', 'Kelas berhasil dihapus.');
    }

    public function storeJadwal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', Rule::exists('users', 'id')->where(static fn ($query) => $query->where('role', 'dosen'))],
            'semester_akademik_id' => ['required', 'exists:semester_akademik,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        if ($ownershipConflict = $this->singleDosenOwnershipConflict($data, null)) {
            return redirect()->route('jadwal')->with('error', $ownershipConflict);
        }

        $jadwal = DB::transaction(function () use ($data) {
            $this->ensureMataKuliahAssignment((int) $data['mata_kuliah_id'], (int) $data['user_id']);
            return Jadwal::create($data);
        });

        AuditLogger::log(
            $request,
            'tambah_jadwal',
            'Menambahkan jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' untuk kelas ' . $jadwal->kelas?->nama_kelas,
            $request->user()?->id
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
            'dosenList' => User::where('role', 'dosen')->orderBy('name')->get(),
            'semesterList' => SemesterAkademik::orderByDesc('is_active')->orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function updateJadwal(Request $request, string $id): RedirectResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', Rule::exists('users', 'id')->where(static fn ($query) => $query->where('role', 'dosen'))],
            'semester_akademik_id' => ['required', 'exists:semester_akademik,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        if ($ownershipConflict = $this->singleDosenOwnershipConflict($data, (int) $jadwal->id)) {
            return redirect()->route('jadwal.edit', $jadwal->id)->with('error', $ownershipConflict);
        }

        $oldMataKuliahId = (int) $jadwal->mata_kuliah_id;

        DB::transaction(function () use ($jadwal, $data, $oldMataKuliahId): void {
            $this->ensureMataKuliahAssignment((int) $data['mata_kuliah_id'], (int) $data['user_id']);
            $jadwal->update($data);

            if ($oldMataKuliahId !== (int) $data['mata_kuliah_id']) {
                $this->cleanupOrphanAssignment($oldMataKuliahId);
            }
        });

        AuditLogger::log(
            $request,
            'update_jadwal',
            'Memperbarui jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' kelas ' . $jadwal->kelas?->nama_kelas,
            $request->user()?->id
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
        $mataKuliahId = (int) $jadwal->mata_kuliah_id;

        DB::transaction(function () use ($jadwal, $mataKuliahId): void {
            $jadwal->delete();
            $this->cleanupOrphanAssignment($mataKuliahId);
        });

        AuditLogger::log(
            $request,
            'hapus_jadwal',
            'Menghapus jadwal ' . $info,
            $request->user()?->id
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dihapus.');
    }

    private function singleDosenOwnershipConflict(array $data, ?int $ignoreJadwalId): ?string
    {
        $assignmentConflict = MataKuliahDosenAssignment::query()
            ->where('mata_kuliah_id', (int) $data['mata_kuliah_id'])
            ->where('user_id', '!=', (int) $data['user_id'])
            ->with(['mataKuliah', 'dosen'])
            ->first();

        if ($assignmentConflict) {
            return 'Mata kuliah ' . ($assignmentConflict->mataKuliah?->nama_mk ?? 'terpilih')
                . ' sudah dimiliki oleh dosen ' . ($assignmentConflict->dosen?->name ?? '-')
                . '. Satu mata kuliah hanya boleh diampu oleh satu dosen.';
        }

        $query = Jadwal::query()
            ->where('mata_kuliah_id', (int) $data['mata_kuliah_id'])
            ->where('user_id', '!=', (int) $data['user_id']);

        if ($ignoreJadwalId !== null) {
            $query->where('id', '!=', $ignoreJadwalId);
        }

        $conflict = $query->with(['mata_kuliah', 'dosen'])->first();
        if (! $conflict) {
            return null;
        }

        return 'Mata kuliah ' . ($conflict->mata_kuliah?->nama_mk ?? 'terpilih')
            . ' sudah dimiliki oleh dosen ' . ($conflict->dosen?->name ?? '-')
            . '. Satu mata kuliah hanya boleh diampu oleh satu dosen.';
    }

    private function ensureMataKuliahAssignment(int $mataKuliahId, int $dosenId): void
    {
        MataKuliahDosenAssignment::query()->updateOrCreate(
            ['mata_kuliah_id' => $mataKuliahId],
            ['user_id' => $dosenId]
        );
    }

    private function cleanupOrphanAssignment(int $mataKuliahId): void
    {
        if ($mataKuliahId <= 0) {
            return;
        }

        $stillUsed = Jadwal::query()
            ->where('mata_kuliah_id', $mataKuliahId)
            ->exists();

        if (! $stillUsed) {
            MataKuliahDosenAssignment::query()
                ->where('mata_kuliah_id', $mataKuliahId)
                ->delete();
        }
    }
}
