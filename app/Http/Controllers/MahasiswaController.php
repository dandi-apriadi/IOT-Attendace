<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceEnrollmentJob;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $onlineThreshold = now()->subMinutes(2);

        return view('master.mahasiswa-edit', [
            'mahasiswa' => $mahasiswa,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'activeDevices' => Device::query()
                ->where('is_active', true)
                ->whereNotNull('last_seen_at')
                ->where('last_seen_at', '>=', $onlineThreshold)
                ->orderBy('name')
                ->get(['id', 'device_id', 'name', 'last_seen_at']),
        ]);
    }

    public function startEnrollment(Request $request, Mahasiswa $mahasiswa): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
            'capture_type' => ['required', Rule::in(['rfid', 'fingerprint', 'face', 'barcode'])],
        ]);

        $device = Device::query()
            ->where('id', $data['device_id'])
            ->where('is_active', true)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat IoT tidak aktif atau tidak ditemukan.',
            ], 422);
        }

        DB::transaction(function () use ($mahasiswa, $data): void {
            DeviceEnrollmentJob::query()
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('capture_type', $data['capture_type'])
                ->whereIn('status', ['pending_device', 'capturing'])
                ->update([
                    'status' => 'cancelled',
                    'error_message' => 'Dibatalkan karena ada request sinkronisasi baru.',
                    'completed_at' => now(),
                ]);
        });

        $job = DeviceEnrollmentJob::create([
            'mahasiswa_id' => $mahasiswa->id,
            'device_id' => $device->id,
            'capture_type' => $data['capture_type'],
            'status' => 'pending_device',
            'requested_by' => $request->user()?->id,
            'expires_at' => now()->addSeconds(90),
        ]);

        AuditLogger::log(
            $request,
            'start_enrollment',
            'Memulai sinkronisasi ' . $data['capture_type'] . ' untuk mahasiswa ' . $mahasiswa->nama . ' via perangkat ' . $device->device_id,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Sinkronisasi dimulai. Perangkat masuk mode standby.',
            'job_id' => $job->id,
            'status_url' => route('mahasiswa.enrollment.status', [$mahasiswa, $job]),
        ]);
    }

    public function enrollmentStatus(Mahasiswa $mahasiswa, DeviceEnrollmentJob $job): JsonResponse
    {
        if ((int) $job->mahasiswa_id !== (int) $mahasiswa->id) {
            abort(404);
        }

        if (
            in_array($job->status, ['pending_device', 'capturing'], true)
            && $job->expires_at
            && now()->greaterThan($job->expires_at)
        ) {
            $job->forceFill([
                'status' => 'expired',
                'error_message' => 'Waktu sinkronisasi habis. Silakan ulangi proses registrasi.',
                'completed_at' => now(),
            ])->save();
        }

        return response()->json([
            'job_id' => $job->id,
            'status' => $job->status,
            'capture_type' => $job->capture_type,
            'captured_value' => $job->captured_value,
            'error_message' => $job->error_message,
            'updated_at' => optional($job->updated_at)?->toDateTimeString(),
        ]);
    }

    public function cancelEnrollment(Request $request, Mahasiswa $mahasiswa, DeviceEnrollmentJob $job): JsonResponse
    {
        if ((int) $job->mahasiswa_id !== (int) $mahasiswa->id) {
            abort(404);
        }

        if (in_array($job->status, ['completed', 'failed', 'cancelled', 'expired'], true)) {
            return response()->json([
                'message' => 'Job sudah selesai dan tidak bisa dibatalkan.',
                'status' => $job->status,
            ], 422);
        }

        $job->forceFill([
            'status' => 'cancelled',
            'error_message' => 'Dibatalkan oleh admin.',
            'completed_at' => now(),
        ])->save();

        AuditLogger::log(
            $request,
            'cancel_enrollment',
            'Membatalkan sinkronisasi ' . $job->capture_type . ' untuk mahasiswa ' . $mahasiswa->nama,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Sinkronisasi dibatalkan.',
            'status' => 'cancelled',
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
            'historyQuery' => $mahasiswa->absensi()->with(['jadwal.mataKuliah']),
            'totalAbsensi' => $totalAbsensi,
            'hadirCount' => $hadirCount,
            'sabitIzinCount' => $sabitIzinCount,
            'alpaCount' => $alpaCount,
            'persentaseHadir' => $persentaseHadir,
            'thisMonthAbsensi' => $thisMonthAbsensi,
            'thisMonthHadir' => $thisMonthHadir,
            'hasReportContext' => false,
            'reportBackUrl' => route('mahasiswa'),
            'selectedSemesterLabel' => null,
            'selectedMataKuliahLabel' => null,
            'selectedKelasLabel' => null,
            'selectedStartDate' => '',
            'selectedEndDate' => '',
            'selectedStatusFilter' => '',
            'statusFilterOptions' => [
                ['value' => '', 'label' => 'Semua Status'],
                ['value' => 'present', 'label' => 'Hadir'],
                ['value' => 'excused', 'label' => 'Sakit/Izin'],
                ['value' => 'absent', 'label' => 'Alpa'],
            ],
            'filtersQuery' => [],
            'baseFilterQuery' => ['id' => $mahasiswa->id],
            'quickDateRanges' => [],
            'weeklyTrend' => [],
            'trendInsight' => ['delta' => 0.0, 'direction' => 'flat', 'text' => 'Belum ada pembanding tren.'],
            'statusLabels' => (array) config('attendance.absensi_statuses', []),
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
