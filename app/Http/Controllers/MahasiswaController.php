<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MahasiswaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Mahasiswa::with('kelas')->orderBy('nama');

        $search = (string) $request->query('q', '');
        $kelasId = (string) $request->query('kelas_id', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nim', 'like', '%' . $search . '%');
            });
        }

        if ($kelasId !== '') {
            $query->where('kelas_id', $kelasId);
        }

        return view('master.mahasiswa', [
            'mahasiswaList' => $query->paginate(10)->withQueryString(),
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'search' => $search,
            'kelasId' => $kelasId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $mahasiswa = Mahasiswa::create($data);

        AuditLogger::log(
            $request,
            'tambah_mahasiswa',
            'Menambahkan mahasiswa ' . $mahasiswa->nama . ' (' . $mahasiswa->nim . ')',
            $request->user()?->id
        );

        return redirect()->route('mahasiswa')->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function edit(Mahasiswa $mahasiswa): View
    {
        return view('master.mahasiswa-edit', [
            'mahasiswa' => $mahasiswa,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    public function update(Request $request, Mahasiswa $mahasiswa): RedirectResponse
    {
        $data = $this->validatePayload($request, $mahasiswa->id);

        $mahasiswa->update($data);

        return redirect()->route('mahasiswa')->with('success', 'Data mahasiswa berhasil diperbarui.');
    }

    public function destroy(Mahasiswa $mahasiswa): RedirectResponse
    {
        $mahasiswa->delete();

        return redirect()->route('mahasiswa')->with('success', 'Data mahasiswa berhasil dihapus.');
    }

    public function show(Mahasiswa $mahasiswa): View
    {
        $presentStatuses = (array) config('attendance.absensi_present_statuses', ['Hadir']);
        $excusedStatuses = (array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']);
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        $absensiHistory = $mahasiswa->absensi()
            ->with(['jadwal.mataKuliah'])
            ->latest()
            ->paginate(15);

        $totalAbsensi = $mahasiswa->absensi()->count();
        $hadirCount = $mahasiswa->absensi()->whereIn('status', $presentStatuses)->count();
        $sabitIzinCount = $mahasiswa->absensi()->whereIn('status', $excusedStatuses)->count();
        $alpaCount = $mahasiswa->absensi()->where('status', $absentStatus)->count();

        $persentaseHadir = $totalAbsensi > 0 ? round(($hadirCount / $totalAbsensi) * 100, 2) : 0;

        $thisMonthAbsensi = $mahasiswa->absensi()
            ->whereMonth('created_at', now()->month)
            ->count();
        $thisMonthHadir = $mahasiswa->absensi()
            ->whereIn('status', $presentStatuses)
            ->whereMonth('created_at', now()->month)
            ->count();

        return view('master.student-detail', [
            'mahasiswa' => $mahasiswa,
            'absensiHistory' => $absensiHistory,
            'totalAbsensi' => $totalAbsensi,
            'hadirCount' => $hadirCount,
            'sabitIzinCount' => $sabitIzinCount,
            'alpaCount' => $alpaCount,
            'persentaseHadir' => $persentaseHadir,
            'thisMonthAbsensi' => $thisMonthAbsensi,
            'thisMonthHadir' => $thisMonthHadir,
        ]);
    }

    private function validatePayload(Request $request, ?int $mahasiswaId = null): array
    {
        return $request->validate([
            'nim' => [
                'required',
                'string',
                'max:30',
                Rule::unique('mahasiswa', 'nim')->ignore($mahasiswaId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'rfid_uid' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('mahasiswa', 'rfid_uid')->ignore($mahasiswaId),
            ],
            'fingerprint_data' => ['nullable', 'string'],
            'face_model_data' => ['nullable', 'string'],
            'barcode_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('mahasiswa', 'barcode_id')->ignore($mahasiswaId),
            ],
        ]);
    }
}
