<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Correction;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $approvalStatusOptions = (array) config('attendance.correction_approval_statuses', []);
        $approvalStatusKeys = array_keys($approvalStatusOptions);

        $query = Correction::with(['mahasiswa', 'jadwal.mata_kuliah']);

        $selectedStatus = (string) $request->input('status', '');
        if ($selectedStatus !== '' && in_array($selectedStatus, $approvalStatusKeys, true)) {
            $query->where('status', $selectedStatus);
        } else {
            $selectedStatus = '';
        }

        $corrections = $query->latest()->paginate(10);

        $summaryCounts = [
            'total' => Correction::query()->count(),
        ];

        foreach ($approvalStatusKeys as $statusKey) {
            $summaryCounts[$statusKey] = Correction::query()
                ->where('status', $statusKey)
                ->count();
        }

        return view('reports.correction', compact('corrections', 'approvalStatusOptions', 'summaryCounts', 'selectedStatus'));
    }

    public function create(): View
    {
        $statusOptions = (array) config('attendance.correction_statuses', []);
        $approvalStatusOptions = (array) config('attendance.correction_approval_statuses', []);

        $mahasiswas = Mahasiswa::with('kelas')->orderBy('nama')->get();
        $jadwals = Jadwal::with(['kelas', 'mata_kuliah'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view('reports.correction-edit', compact('mahasiswas', 'jadwals', 'statusOptions', 'approvalStatusOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $statusKeys = array_keys((array) config('attendance.correction_statuses', []));

        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'jadwal_id' => 'required|exists:jadwal,id',
            'tanggal' => 'required|date',
            'status_lama' => ['required', Rule::in($statusKeys)],
            'status_baru' => ['required', Rule::in($statusKeys)],
            'alasan' => 'required|string|min:10',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('dokumen')) {
            $validated['dokumen'] = $request->file('dokumen')->store('corrections', 'public');
        }

        $validated['status'] = 'pending';
        $validated['approval_status'] = 'pending';
        $validated['user_id'] = auth()->id();

        Correction::create($validated);

        return redirect()
            ->route('correction')
            ->with('success', 'Permintaan koreksi berhasil dibuat');
    }

    public function edit(Correction $correction): View
    {
        $this->authorize('update', $correction);
        $statusOptions = (array) config('attendance.correction_statuses', []);
        $approvalStatusOptions = (array) config('attendance.correction_approval_statuses', []);

        $mahasiswas = Mahasiswa::with('kelas')->orderBy('nama')->get();
        $jadwals = Jadwal::with(['kelas', 'mata_kuliah'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();

        return view('reports.correction-edit', compact('correction', 'mahasiswas', 'jadwals', 'statusOptions', 'approvalStatusOptions'));
    }

    public function update(Request $request, Correction $correction): RedirectResponse
    {
        $this->authorize('update', $correction);

        $statusKeys = array_keys((array) config('attendance.correction_statuses', []));
        $approvalStatusKeys = array_keys((array) config('attendance.correction_approval_statuses', []));

        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'jadwal_id' => 'required|exists:jadwal,id',
            'tanggal' => 'required|date',
            'status_lama' => ['required', Rule::in($statusKeys)],
            'status_baru' => ['required', Rule::in($statusKeys)],
            'alasan' => 'required|string|min:10',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approval_status' => ['nullable', Rule::in($approvalStatusKeys)],
            'approval_notes' => 'nullable|string',
        ]);

        $previousStatus = (string) $correction->status;

        if ($request->hasFile('dokumen')) {
            if ($correction->dokumen) {
                \Storage::disk('public')->delete($correction->dokumen);
            }
            $validated['dokumen'] = $request->file('dokumen')->store('corrections', 'public');
        }

        if ($request->filled('approval_status')) {
            $validated['status'] = $request->input('approval_status');

            if ($request->input('approval_status') === 'approved') {
                $validated['approved_by'] = auth()->id();
                $validated['approved_at'] = now();
            } else {
                $validated['approved_by'] = null;
                $validated['approved_at'] = null;
            }
        }

        $correction->update($validated);

        $isNowApproved = ($validated['status'] ?? $correction->status) === 'approved';
        $justApproved = $isNowApproved && $previousStatus !== 'approved';

        if ($justApproved) {
            $approvedCorrection = $correction->fresh();
            $this->syncCorrectionToAbsensi($approvedCorrection);

            AuditLogger::log(
                $request,
                'setujui_koreksi',
                'Menyetujui koreksi absensi mahasiswa_id=' . $approvedCorrection->mahasiswa_id .
                    ' pada tanggal ' . $approvedCorrection->tanggal->format('Y-m-d'),
                $request->user()?->id
            );
        }

        return redirect()
            ->route('correction')
            ->with('success', 'Permintaan koreksi berhasil diperbarui');
    }

    private function syncCorrectionToAbsensi(Correction $correction): void
    {
        $statusAbsensi = $this->mapCorrectionStatusToAbsensi($correction->status_baru);

        $existingAbsensi = Absensi::query()
            ->where('mahasiswa_id', $correction->mahasiswa_id)
            ->where('jadwal_id', $correction->jadwal_id)
            ->whereDate('tanggal', $correction->tanggal)
            ->first();

        if ($existingAbsensi) {
            $existingAbsensi->update([
                'status' => $statusAbsensi,
            ]);

            return;
        }

        Absensi::create([
            'mahasiswa_id' => $correction->mahasiswa_id,
            'jadwal_id' => $correction->jadwal_id,
            'tanggal' => $correction->tanggal->format('Y-m-d'),
            'waktu_tap' => now()->format('H:i:s'),
            'metode_absensi' => 'Barcode',
            'status' => $statusAbsensi,
        ]);
    }

    private function mapCorrectionStatusToAbsensi(string $status): string
    {
        $statusMap = (array) config('attendance.correction_to_absensi', []);

        return (string) ($statusMap[$status] ?? 'Alpa');
    }
}
