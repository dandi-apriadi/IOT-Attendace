<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function audit()
    {
        $logs = [];
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $lines = explode("\n", $content);
                foreach (array_reverse($lines) as $line) {
                    if (trim($line)) {
                        // Parse log line
                        if (strpos($line, 'ERROR') !== false) {
                            $logs[] = ['type' => 'error', 'message' => trim($line)];
                        } elseif (strpos($line, 'WARNING') !== false) {
                            $logs[] = ['type' => 'unauthorized', 'message' => trim($line)];
                        } else {
                            $logs[] = ['type' => 'auth', 'message' => trim($line)];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error reading logs: ' . $e->getMessage());
        }

        return view('reports.audit', ['logs' => array_slice($logs, 0, 50)]);
    }

    public function correction()
    {
        $corrections = \App\Models\Correction::with(['mahasiswa'])
            ->when(request('status'), function ($q) {
                return $q->where('status', request('status'));
            })
            ->latest()
            ->paginate(10);

        return view('reports.correction', ['corrections' => $corrections]);
    }

    public function correctionCreate()
    {
        $mahasiswas = \App\Models\Mahasiswa::with('kelas')->get();
        return view('reports.correction-edit', ['mahasiswas' => $mahasiswas]);
    }

    public function correctionStore(Request $request)
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

        \App\Models\Correction::create($validated);

        return redirect()
            ->route('correction')
            ->with('success', 'Permintaan koreksi berhasil dibuat');
    }

    public function correctionEdit(\App\Models\Correction $correction)
    {
        $this->authorize('update', $correction);
        $mahasiswas = \App\Models\Mahasiswa::with('kelas')->get();
        return view('reports.correction-edit', [
            'correction' => $correction,
            'mahasiswas' => $mahasiswas
        ]);
    }

    public function correctionUpdate(Request $request, \App\Models\Correction $correction)
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
