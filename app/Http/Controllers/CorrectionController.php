<?php

namespace App\Http\Controllers;

use App\Models\Correction;
use App\Models\Mahasiswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;

class CorrectionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Correction::with('mahasiswa');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $corrections = $query->latest()->paginate(10);

        return view('reports.correction', compact('corrections'));
    }

    public function create(): View
    {
        $mahasiswas = Mahasiswa::with('kelas')->orderBy('nama_mahasiswa')->get();
        return view('reports.correction-edit', compact('mahasiswas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'tanggal' => 'required|date',
            'status_lama' => 'required|in:hadir,sakit_izin,alpa',
            'status_baru' => 'required|in:hadir,sakit_izin,alpa',
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
        $mahasiswas = Mahasiswa::with('kelas')->orderBy('nama_mahasiswa')->get();
        return view('reports.correction-edit', compact('correction', 'mahasiswas'));
    }

    public function update(Request $request, Correction $correction): RedirectResponse
    {
        $this->authorize('update', $correction);

        $validated = $request->validate([
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'tanggal' => 'required|date',
            'status_lama' => 'required|in:hadir,sakit_izin,alpa',
            'status_baru' => 'required|in:hadir,sakit_izin,alpa',
            'alasan' => 'required|string|min:10',
            'dokumen' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'approval_notes' => 'nullable|string',
        ]);

        if ($request->hasFile('dokumen')) {
            if ($correction->dokumen) {
                \Storage::disk('public')->delete($correction->dokumen);
            }
            $validated['dokumen'] = $request->file('dokumen')->store('corrections', 'public');
        }

        if ($request->filled('approval_status')) {
            $validated['status'] = $request->input('approval_status');
            $validated['approved_by'] = auth()->id();
            $validated['approved_at'] = now();
        }

        $correction->update($validated);

        return redirect()
            ->route('correction')
            ->with('success', 'Permintaan koreksi berhasil diperbarui');
    }
}
