<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceEnrollmentJob;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use App\Services\ZktecoService;
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
        $statusAkademik = (string) $request->query('status_akademik', '');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nim', 'like', '%' . $search . '%');
            });
        }

        if ($kelasId !== '') {
            $query->where('kelas_id', $kelasId);
        }

        if ($statusAkademik !== '') {
            $query->where('status_akademik', $statusAkademik);
        }

        return view('master.mahasiswa', [
            'mahasiswaList' => $query->paginate(10)->withQueryString(),
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'statusOptions' => $this->statusAkademikOptions(),
            'zktecoDevices' => Device::query()
                ->where('is_active', true)
                ->where('type', 'zkteco')
                ->whereNotNull('ip_address')
                ->orderBy('name')
                ->get(['id', 'device_id', 'name', 'ip_address', 'port']),
            'search' => $search,
            'kelasId' => $kelasId,
            'statusAkademik' => $statusAkademik,
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

    public function pullBiometrics(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'integer', 'exists:devices,id'],
        ]);

        $device = Device::query()
            ->whereKey($validated['device_id'])
            ->where('is_active', true)
            ->where('type', 'zkteco')
            ->whereNotNull('ip_address')
            ->first();

        if (! $device) {
            return back()->with('error', 'Pilih perangkat ZKTeco aktif yang memiliki IP.');
        }

        try {
            $result = (new ZktecoService($device))->syncRegisteredStudentBiometrics();

            AuditLogger::log(
                $request,
                'pull_biometrics_mahasiswa',
                "Tarik biometrik mahasiswa: {$result['updated']} diperbarui dari perangkat " . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            $message = "Tarik biometrik selesai: {$result['updated']} mahasiswa diperbarui, "
                . "{$result['matched']} cocok dari {$result['scanned']} user alat";

            if ($result['rfid_updated'] > 0 || $result['fingerprint_updated'] > 0) {
                $message .= " ({$result['rfid_updated']} RFID, {$result['fingerprint_updated']} sidik jari)";
            }
            if ($result['unmatched'] > 0) {
                $message .= ", {$result['unmatched']} belum ada di sistem";
            }
            if ($result['conflicts'] > 0) {
                $message .= ", {$result['conflicts']} konflik kartu";

                return back()->with('error', $message . '. ' . implode(' ', $result['errors']));
            }

            return back()->with('success', $message . '.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menarik biometrik: ' . $e->getMessage());
        }
    }

    public function edit(Mahasiswa $mahasiswa): View
    {
        $onlineThreshold = now()->subMinutes(2);

        return view('master.mahasiswa-edit', [
            'mahasiswa' => $mahasiswa,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'statusOptions' => $this->statusAkademikOptions(),
            'activeDevices' => Device::query()
                ->where('is_active', true)
                ->where('type', 'custom_iot')
                ->whereNotNull('last_seen_at')
                ->where('last_seen_at', '>=', $onlineThreshold)
                ->orderBy('name')
                ->get(['id', 'device_id', 'name', 'last_seen_at']),
            // Perangkat ZKTeco (mis. X609) — koneksi langsung, tidak bergantung heartbeat.
            'zktecoDevices' => Device::query()
                ->where('is_active', true)
                ->where('type', 'zkteco')
                ->whereNotNull('ip_address')
                ->orderBy('name')
                ->get(['id', 'device_id', 'name', 'ip_address', 'port', 'last_seen_at']),
        ]);
    }

    /**
     * Mendaftarkan (push) mahasiswa ke perangkat ZKTeco agar bisa tap & enroll.
     */
    public function registerToDevice(Request $request, Mahasiswa $mahasiswa, Device $device): JsonResponse
    {
        $data = $request->validate([
            'registration_method' => ['nullable', Rule::in(['rfid', 'fingerprint', 'rfid_fingerprint'])],
        ]);
        $method = $data['registration_method'] ?? 'rfid_fingerprint';
        $methodLabel = $this->zktecoRegistrationMethodLabel($method);

        if ($device->type !== 'zkteco' || empty($device->ip_address)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat bukan tipe ZKTeco / tanpa IP.'], 422);
        }

        try {
            (new ZktecoService($device))->pushUser($mahasiswa);

            AuditLogger::log(
                $request,
                'register_mahasiswa_device',
                'Mendaftarkan ' . $mahasiswa->nama . ' (' . $mahasiswa->nim . ') ke perangkat ' . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return response()->json([
                'ok' => true,
                'message' => 'Mahasiswa terdaftar di alat untuk metode ' . $methodLabel . ' (UID ' . $mahasiswa->id . ', userid ' . $mahasiswa->nim . '). Silakan enroll di mesin, lalu klik "Sinkronkan dari Alat".',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Gagal mendaftarkan: ' . $e->getMessage()], 200);
        }
    }

    /**
     * Membaca data biometrik/kartu mahasiswa dari perangkat ZKTeco dan menyimpannya.
     */
    public function syncFromDevice(Request $request, Mahasiswa $mahasiswa, Device $device): JsonResponse
    {
        $data = $request->validate([
            'registration_method' => ['nullable', Rule::in(['rfid', 'fingerprint', 'rfid_fingerprint'])],
        ]);
        $method = $data['registration_method'] ?? 'rfid_fingerprint';

        if ($device->type !== 'zkteco' || empty($device->ip_address)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat bukan tipe ZKTeco / tanpa IP.'], 422);
        }

        try {
            $data = (new ZktecoService($device))->readUser((int) $mahasiswa->id);

            if (! $data['found']) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Mahasiswa belum terdaftar di alat. Klik "Daftarkan ke Alat" dulu.',
                ], 200);
            }

            $updates = [];
            $requestedLabels = [];
            if (in_array($method, ['rfid', 'rfid_fingerprint'], true)) {
                $requestedLabels[] = 'Kartu RFID';
                if (! empty($data['cardno'])) {
                    $updates['rfid_uid'] = $data['cardno'];
                }
            }
            if (in_array($method, ['fingerprint', 'rfid_fingerprint'], true)) {
                $requestedLabels[] = 'Sidik Jari';
                if ($data['has_fingerprint']) {
                    $updates['fingerprint_data'] = 'enrolled@' . $device->device_id;
                }
            }

            if (! empty($updates)) {
                $mahasiswa->update($updates);
            }

            AuditLogger::log(
                $request,
                'sync_mahasiswa_device',
                'Sinkronisasi data ' . $mahasiswa->nama . ' dari perangkat ' . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return response()->json([
                'ok' => true,
                'card_no' => $data['cardno'],
                'has_fingerprint' => $data['has_fingerprint'],
                'updated' => $updates,
                'message' => empty($updates)
                    ? 'Mahasiswa terdaftar, tapi data ' . implode(' dan ', $requestedLabels) . ' belum di-enroll di alat.'
                    : 'Data tersinkron dari alat: ' . implode(', ', $this->zktecoUpdatedLabels($updates)) . '.',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Gagal sinkronisasi: ' . $e->getMessage()], 200);
        }
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

    public function show(Request $request, Mahasiswa $mahasiswa): View
    {
        return app(StudentDetailController::class)->show($request, $mahasiswa->id);
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
            'status_akademik' => ['required', Rule::in(array_keys($this->statusAkademikOptions()))],
            'semester_level' => ['nullable', 'integer', 'min:1', 'max:14'],
            'promotion_paused' => ['nullable', 'boolean'],
            'promotion_note' => ['nullable', 'string', 'max:255'],
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

        $data['promotion_paused'] = $request->boolean('promotion_paused');
        if (! $data['promotion_paused']) {
            $data['promotion_note'] = null;
        }

        return $data;
    }

    private function statusAkademikOptions(): array
    {
        return [
            'aktif' => 'Aktif',
            'cuti' => 'Cuti',
            'nonaktif' => 'Nonaktif',
            'lulus' => 'Lulus',
        ];
    }

    private function zktecoRegistrationMethodLabel(string $method): string
    {
        return match ($method) {
            'rfid' => 'Kartu RFID',
            'fingerprint' => 'Sidik Jari',
            default => 'Kartu RFID + Sidik Jari',
        };
    }

    /**
     * @param array<string, string> $updates
     * @return array<int, string>
     */
    private function zktecoUpdatedLabels(array $updates): array
    {
        $labels = [];
        if (array_key_exists('rfid_uid', $updates)) {
            $labels[] = 'Kartu RFID';
        }
        if (array_key_exists('fingerprint_data', $updates)) {
            $labels[] = 'Sidik Jari';
        }

        return $labels;
    }
}
