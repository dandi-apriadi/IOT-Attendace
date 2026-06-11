<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $devicesList = Device::orderByDesc('last_seen_at')
            ->orderBy('name')
            ->paginate(12);

        return view('master.devices', [
            'devicesList' => $devicesList,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:50', 'unique:devices,device_id'],
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['custom_iot', 'zkteco'])],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'token_hash' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = ! empty($data['is_active']);
        [$data['token_hash'], $generatedToken] = $this->normalizeDeviceTokenHash($data['token_hash'] ?? null);

        if ($data['type'] === 'zkteco' && empty($data['port'])) {
            $data['port'] = 4370;
        }

        $device = Device::create($data);

        AuditLogger::log(
            $request,
            'tambah_device',
            'Menambahkan perangkat ' . ($device->name ?: $device->device_id),
            $request->user()?->id
        );

        $message = 'Perangkat berhasil ditambahkan.';
        if ($data['type'] === 'custom_iot' && $generatedToken !== null) {
            $message .= ' Token perangkat dibuat otomatis: ' . $generatedToken;
        }

        return redirect()->route('devices.index')->with('success', $message);
    }

    public function edit(string $id): View
    {
        $device = Device::findOrFail($id);

        return view('master.devices-edit', [
            'device' => $device,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $device = Device::findOrFail($id);

        $data = $request->validate([
            'device_id' => ['required', 'string', 'max:50', Rule::unique('devices')->ignore($device->id)],
            'name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['custom_iot', 'zkteco'])],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'token_hash' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = ! empty($data['is_active']);
        [$data['token_hash']] = $this->normalizeDeviceTokenHash($data['token_hash'] ?? null, $device->token_hash);

        if ($data['type'] === 'zkteco' && empty($data['port'])) {
            $data['port'] = 4370;
        }

        $device->update($data);

        AuditLogger::log(
            $request,
            'update_device',
            'Memperbarui perangkat ' . ($device->name ?: $device->device_id),
            $request->user()?->id
        );

        return redirect()->route('devices.index')->with('success', 'Data perangkat berhasil diperbarui.');
    }

    /**
     * Form menerima token asli agar mudah dipakai firmware. Database tetap
     * menyimpan SHA-256 karena middleware membandingkan hash token masuk.
     *
     * @return array{0: string, 1: ?string}
     */
    private function normalizeDeviceTokenHash(?string $input, ?string $existingHash = null): array
    {
        $token = trim((string) $input);

        if ($token === '' && $existingHash) {
            return [$existingHash, null];
        }

        $generatedToken = null;
        if ($token === '') {
            $token = Str::random(40);
            $generatedToken = $token;
        }

        if (preg_match('/^[a-f0-9]{64}$/i', $token) === 1) {
            return [strtolower($token), $generatedToken];
        }

        return [hash('sha256', $token), $generatedToken];
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $device = Device::findOrFail($id);

        $deviceName = $device->name ?: $device->device_id;
        $device->delete();

        AuditLogger::log(
            $request,
            'hapus_device',
            'Menghapus perangkat ' . $deviceName,
            $request->user()?->id
        );

        return redirect()->route('devices.index')->with('success', 'Perangkat berhasil dihapus.');
    }

    // ====================== ZKTeco Operations ======================

    /**
     * Tes koneksi ke perangkat (dipanggil via fetch dari UI).
     */
    public function testConnection(Device $device): JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat ini bukan tipe ZKTeco.'], 422);
        }

        $result = (new ZktecoService($device))->testConnection();

        return response()->json($result, $result['ok'] ? 200 : 200);
    }

    /**
     * Mengambil info perangkat (versi, serial, nama, waktu).
     */
    public function info(Device $device): JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat ini bukan tipe ZKTeco.'], 422);
        }

        try {
            $info = (new ZktecoService($device))->getInfo();

            return response()->json(['ok' => true, 'info' => $info]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Mendorong seluruh mahasiswa ke perangkat.
     */
    public function syncUsers(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        try {
            $result = (new ZktecoService($device))->pushAllUsers();

            AuditLogger::log(
                $request,
                'sync_users_device',
                "Sinkronisasi {$result['success']} user ke perangkat " . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            $msg = "Sinkronisasi selesai: {$result['success']} user terverifikasi di perangkat (dari {$result['total']} mahasiswa)";
            if (($result['retried'] ?? 0) > 0) {
                $msg .= ", {$result['retried']} di-retry";
            }
            if ($result['failed'] > 0) {
                $msg .= ", {$result['failed']} GAGAL";

                return back()->with('error', $msg . '. Coba Sync Users lagi.');
            }

            return back()->with('success', $msg . '.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan daftar user yang ada di perangkat.
     */
    public function users(Device $device): View
    {
        if (! $this->isZkteco($device)) {
            abort(404);
        }

        $deviceUsers = [];
        $error = null;

        try {
            $deviceUsers = (new ZktecoService($device))->pullUsers();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('master.devices-users', [
            'device' => $device,
            'deviceUsers' => $deviceUsers,
            'error' => $error,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
        ]);
    }

    /**
     * Mengimpor user perangkat yang belum terdaftar menjadi Mahasiswa baru.
     */
    public function importUsers(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
        ]);

        try {
            $users = (new ZktecoService($device))->pullUsers();
            $created = 0;

            foreach ($users as $u) {
                if ($u['matched'] || empty($u['userid'])) {
                    continue;
                }

                // Lewati jika nim/rfid sudah ada (race-safe).
                $exists = Mahasiswa::where('nim', $u['userid'])
                    ->orWhere('rfid_uid', $u['userid'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                Mahasiswa::create([
                    'nim' => $u['userid'],
                    'nama' => $u['name'] ?: ('User ' . $u['userid']),
                    'kelas_id' => $validated['kelas_id'],
                    'rfid_uid' => $u['userid'],
                ]);
                $created++;
            }

            AuditLogger::log(
                $request,
                'import_users_device',
                "Import {$created} user dari perangkat " . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return back()->with('success', "Berhasil mengimpor {$created} mahasiswa baru dari perangkat.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Menarik absensi dari perangkat secara manual.
     */
    public function pullAttendance(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        try {
            $result = (new ZktecoService($device))->importAttendance();

            AuditLogger::log(
                $request,
                'pull_attendance_device',
                "Tarik absensi: {$result['inserted']} baru dari perangkat " . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return back()->with('success', "Berhasil menarik absensi: {$result['inserted']} record baru (dari total {$result['total']}).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menarik absensi: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus satu user dari perangkat.
     */
    public function removeUser(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        $validated = $request->validate([
            'uid' => ['required', 'integer', 'min:1'],
        ]);

        try {
            (new ZktecoService($device))->removeUser((int) $validated['uid']);

            AuditLogger::log(
                $request,
                'remove_user_device',
                "Hapus user uid {$validated['uid']} dari perangkat " . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return back()->with('success', "User uid {$validated['uid']} berhasil dihapus dari perangkat.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }

    /**
     * Mengosongkan log absensi di perangkat.
     */
    public function clearAttendance(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        try {
            (new ZktecoService($device))->clearAttendance();

            AuditLogger::log(
                $request,
                'clear_attendance_device',
                'Mengosongkan log absensi perangkat ' . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return back()->with('success', 'Log absensi di perangkat berhasil dikosongkan.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengosongkan log: ' . $e->getMessage());
        }
    }

    /**
     * Menyinkronkan waktu perangkat dengan server.
     */
    public function syncTime(Request $request, Device $device): RedirectResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        try {
            (new ZktecoService($device))->syncTime();

            AuditLogger::log(
                $request,
                'sync_time_device',
                'Sinkronisasi waktu perangkat ' . ($device->name ?: $device->device_id),
                $request->user()?->id
            );

            return back()->with('success', 'Waktu perangkat berhasil disinkronkan dengan server.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal sinkronisasi waktu: ' . $e->getMessage());
        }
    }

    private function isZkteco(Device $device): bool
    {
        return $device->type === 'zkteco' && ! empty($device->ip_address);
    }
}
