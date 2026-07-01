<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');
        $selectedKelasId = $request->query('kelas_id');
        $payload = $this->buildLivePayload($selectedDate, $selectedJadwalId, $selectedKelasId);

        // Resolve ALL active sessions from cache into enriched info arrays.
        $rawActiveSessions = $this->attendanceSessions()->activeSessions();
        $activeSessionsInfo = [];

        foreach ($rawActiveSessions as $rawSession) {
            $jadwalQuery = Jadwal::query()
                ->with(['mata_kuliah:id,kode_mk,nama_mk', 'kelas:id,nama_kelas'])
                ->where('mata_kuliah_id', $rawSession['mata_kuliah_id'] ?? null)
                ->where('kelas_id', $rawSession['kelas_id'] ?? null);

            if (! empty($rawSession['jadwal_id'])) {
                $jadwalQuery->whereKey((int) $rawSession['jadwal_id']);
            }

            $jadwal = $jadwalQuery->first();
            if (! $jadwal) {
                continue;
            }

            $activeSessionsInfo[] = [
                'mata_kuliah_id' => $jadwal->mata_kuliah_id,
                'kelas_id' => $jadwal->kelas_id,
                'mk_name' => $jadwal->mata_kuliah?->nama_mk ?? 'N/A',
                'mk_kode' => $jadwal->mata_kuliah?->kode_mk ?? 'N/A',
                'kelas_name' => $jadwal->kelas?->nama_kelas ?? 'N/A',
                'source' => ($rawSession['source'] ?? 'manual') === 'auto_schedule' ? 'AUTO' : 'MANUAL',
                'started_at' => $rawSession['started_at'] ?? null,
                'jadwal_id' => $jadwal->id,
            ];
        }

        return view('monitoring.live', [
            'records' => $payload['records'],
            'todayTotal' => $payload['today_total'],
            'thisHourTotal' => $payload['this_hour_total'],
            'lastUpdatedAt' => $payload['last_updated_at'],
            'selectedDate' => $payload['selected_date'],
            'selectedJadwalId' => $payload['selected_jadwal_id'],
            'selectedKelasId' => $payload['selected_kelas_id'],
            'sessions' => $payload['sessions'],
            'sessionSummary' => $payload['session_summary'],
            'selectedSession' => $payload['selected_session'],
            'activeSessions' => $activeSessionsInfo,
            'kelases' => $payload['kelases'],
            'jadwalList' => $payload['jadwal_list'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');
        $selectedKelasId = $request->query('kelas_id');

        return response()->json($this->buildLivePayload($selectedDate, $selectedJadwalId, $selectedKelasId));
    }

    public function edit(Request $request, Absensi $absensi): View
    {
        $absensi->load([
            'mahasiswa:id,nama,nim',
            'jadwal:id,mata_kuliah_id,kelas_id,hari,jam_mulai,jam_selesai',
            'jadwal.mata_kuliah:id,kode_mk,nama_mk',
            'jadwal.kelas:id,nama_kelas',
        ]);

        return view('monitoring.live-edit', [
            'absensi' => $absensi,
            'statusOptions' => array_values(config('attendance.absensi_statuses', [])),
            'methodOptions' => ['RFID', 'Fingerprint', 'Face Recognition', 'Barcode'],
            'returnDate' => $this->normalizeDate((string) $request->query('date', (string) $absensi->tanggal)),
            'returnJadwalId' => $request->query('jadwal_id', $absensi->jadwal_id),
        ]);
    }

    public function update(Request $request, Absensi $absensi): RedirectResponse
    {
        $statusOptions = array_values(config('attendance.absensi_statuses', []));
        $methodOptions = ['RFID', 'Fingerprint', 'Face Recognition', 'Barcode'];

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', $statusOptions)],
            'metode_absensi' => ['required', 'in:' . implode(',', $methodOptions)],
            'waktu_tap' => ['required', 'date_format:H:i'],
            'return_date' => ['required', 'date'],
            'return_jadwal_id' => ['nullable', 'integer'],
        ]);

        $normalizedDate = $this->normalizeDate((string) $validated['return_date']);
        $normalizedTime = Carbon::createFromFormat('H:i', (string) $validated['waktu_tap'])->format('H:i:s');

        $absensi->update([
            'status' => $validated['status'],
            'metode_absensi' => $validated['metode_absensi'],
            'waktu_tap' => $normalizedTime,
        ]);

        $this->forgetLiveCache($normalizedDate, (int) $absensi->jadwal_id);

        return redirect()
            ->route('monitoring', [
                'date' => $normalizedDate,
                'jadwal_id' => $validated['return_jadwal_id'] ?: null,
            ])
            ->with('success', 'Data live monitoring berhasil diperbarui.');
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function buildLivePayload(string $selectedDate, mixed $selectedJadwalId, mixed $selectedKelasId = null): array
    {
        return $this->attendanceSessions()->buildLivePayload($selectedDate, $selectedJadwalId, $selectedKelasId);
    }

    private function forgetLiveCache(string $selectedDate, int $jadwalId): void
    {
        $this->attendanceSessions()->forgetLiveMonitoringCache($selectedDate, $jadwalId);
    }

    private function attendanceSessions(): AttendanceSessionService
    {
        return app(AttendanceSessionService::class);
    }
}
