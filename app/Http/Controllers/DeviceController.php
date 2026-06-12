<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Services\AuditLogger;
use App\Services\DeviceCommandService;
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
            'name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'token_hash' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = ! empty($data['is_active']);
        [$data['token_hash']] = $this->normalizeDeviceTokenHash($data['token_hash'] ?? null, $device->token_hash);

        if ($device->type === 'zkteco' && empty($data['port'])) {
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
    public function testConnection(Request $request, Device $device): JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat ini bukan tipe ZKTeco.'], 422);
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue($device, 'get_info', [], $request->user()?->id);

            return response()->json([
                'ok' => true,
                'queued' => true,
                'command_id' => $command->id,
                'status_url' => route('devices.commands.status', [$device, $command]),
                'message' => 'Perintah cek koneksi dikirim ke agent lokal. Hasil tersedia setelah agent menjalankannya.',
            ]);
        }

        $result = (new ZktecoService($device))->testConnection();

        return response()->json($result, 200);
    }

    /**
     * Mengambil info perangkat (versi, serial, nama, waktu).
     */
    public function info(Request $request, Device $device): JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return response()->json(['ok' => false, 'message' => 'Perangkat ini bukan tipe ZKTeco.'], 422);
        }

        if ($this->usesAgentRelay()) {
            // Tampilkan hasil get_info terakhir bila ada, lalu antrekan yang baru.
            $last = $this->latestDoneResult($device, 'get_info');
            $command = app(DeviceCommandService::class)->enqueue($device, 'get_info', [], $request->user()?->id);

            return response()->json([
                'ok' => true,
                'queued' => true,
                'command_id' => $command->id,
                'status_url' => route('devices.commands.status', [$device, $command]),
                'info' => $last,
                'message' => $last
                    ? 'Menampilkan info terakhir dari agent. Perintah refresh dikirim.'
                    : 'Perintah ambil info dikirim ke agent lokal. Muat ulang sebentar lagi.',
            ]);
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
    public function syncUsers(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        if ($this->usesAgentRelay()) {
            $commands = app(DeviceCommandService::class);
            $payload = $commands->buildAllUsersPayload();
            $command = $commands->enqueue($device, 'push_all_users', $payload, $request->user()?->id);

            AuditLogger::log(
                $request,
                'sync_users_device',
                'Antre sinkronisasi ' . count($payload['users']) . ' user ke perangkat ' . ($device->name ?: $device->device_id) . ' (via agent)',
                $request->user()?->id
            );

            if ($request->expectsJson()) {
                return $this->queuedCommandResponse($device, $command, 'Perintah sinkronisasi ' . count($payload['users']) . ' user dikirim ke agent lokal, menunggu eksekusi.');
            }

            return back()->with('success', 'Perintah sinkronisasi ' . count($payload['users']) . ' user dikirim ke agent lokal, menunggu eksekusi.');
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
    public function users(Request $request, Device $device): View
    {
        if (! $this->isZkteco($device)) {
            abort(404);
        }

        $deviceUsers = [];
        $error = null;

        if ($this->usesAgentRelay()) {
            // Ambil daftar user dari hasil pull_users terakhir; antrekan refresh.
            $result = $this->latestDoneResult($device, 'pull_users');
            $deviceUsers = $this->matchDeviceUsers($result['users'] ?? []);
            app(DeviceCommandService::class)->enqueue($device, 'pull_users', [], $request->user()?->id);

            if (empty($deviceUsers)) {
                $error = 'Belum ada data user dari agent. Perintah ambil user dikirim — muat ulang halaman ini sebentar lagi.';
            }
        } else {
            try {
                $deviceUsers = (new ZktecoService($device))->pullUsers();
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
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
            if ($this->usesAgentRelay()) {
                $result = $this->latestDoneResult($device, 'pull_users');
                $users = $this->matchDeviceUsers($result['users'] ?? []);

                if (empty($users)) {
                    return back()->with('error', 'Belum ada data user dari agent. Buka "Lihat Users" dulu agar agent menariknya, lalu coba lagi.');
                }
            } else {
                $users = (new ZktecoService($device))->pullUsers();
            }
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
    public function pullAttendance(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue($device, 'pull_attendance', [], $request->user()?->id);

            if ($request->expectsJson()) {
                return $this->queuedCommandResponse($device, $command, 'Perintah tarik absensi dikirim ke agent lokal. Menunggu hasil eksekusi agent.');
            }

            return back()->with('success', 'Perintah tarik absensi dikirim ke agent lokal. Absensi akan masuk otomatis setelah agent menjalankannya.');
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
     * Menarik data kartu dan fingerprint dari perangkat untuk mahasiswa yang
     * sudah terdaftar di sistem.
     */
    public function pullBiometrics(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue($device, 'pull_biometrics', [], $request->user()?->id);

            if ($request->expectsJson()) {
                return $this->queuedCommandResponse($device, $command, 'Perintah tarik biometrik dikirim ke agent lokal. Menunggu hasil eksekusi agent.');
            }

            return back()->with('success', 'Perintah tarik biometrik dikirim ke agent lokal. Data kartu/sidik jari mahasiswa akan diperbarui setelah agent membaca alat.');
        }

        try {
            $result = (new ZktecoService($device))->syncRegisteredStudentBiometrics();

            AuditLogger::log(
                $request,
                'pull_biometrics_device',
                "Tarik biometrik: {$result['updated']} mahasiswa diperbarui dari perangkat " . ($device->name ?: $device->device_id),
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

        if ($this->usesAgentRelay()) {
            app(DeviceCommandService::class)->enqueue($device, 'remove_user', ['uid' => (int) $validated['uid']], $request->user()?->id);

            AuditLogger::log(
                $request,
                'remove_user_device',
                "Antre hapus user uid {$validated['uid']} dari perangkat " . ($device->name ?: $device->device_id) . ' (via agent)',
                $request->user()?->id
            );

            return back()->with('success', "Perintah hapus user uid {$validated['uid']} dikirim ke agent lokal.");
        }

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
    public function clearAttendance(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue($device, 'clear_attendance', [], $request->user()?->id);

            AuditLogger::log(
                $request,
                'clear_attendance_device',
                'Antre kosongkan log absensi perangkat ' . ($device->name ?: $device->device_id) . ' (via agent)',
                $request->user()?->id
            );

            if ($request->expectsJson()) {
                return $this->queuedCommandResponse($device, $command, 'Perintah kosongkan log absensi dikirim ke agent lokal. Menunggu hasil eksekusi agent.');
            }

            return back()->with('success', 'Perintah kosongkan log absensi dikirim ke agent lokal.');
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
    public function syncTime(Request $request, Device $device): RedirectResponse|JsonResponse
    {
        if (! $this->isZkteco($device)) {
            return back()->with('error', 'Perangkat ini bukan tipe ZKTeco.');
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue($device, 'sync_time', [], $request->user()?->id);

            AuditLogger::log(
                $request,
                'sync_time_device',
                'Antre sinkronisasi waktu perangkat ' . ($device->name ?: $device->device_id) . ' (via agent)',
                $request->user()?->id
            );

            if ($request->expectsJson()) {
                return $this->queuedCommandResponse($device, $command, 'Perintah sinkronisasi waktu dikirim ke agent lokal. Menunggu hasil eksekusi agent.');
            }

            return back()->with('success', 'Perintah sinkronisasi waktu dikirim ke agent lokal.');
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

    /**
     * Mengambil payload result dari command tipe tertentu yang terakhir selesai.
     *
     * @return array<string, mixed>|null
     */
    private function latestDoneResult(Device $device, string $type): ?array
    {
        $command = DeviceCommand::query()
            ->where('device_id', $device->id)
            ->where('type', $type)
            ->where('status', 'done')
            ->latest('completed_at')
            ->first();

        return $command?->result;
    }

    public function commandStatus(Device $device, DeviceCommand $command): JsonResponse
    {
        if ((int) $command->device_id !== (int) $device->id) {
            return response()->json(['ok' => false, 'message' => 'Perintah tidak cocok dengan perangkat ini.'], 404);
        }

        return response()->json($this->commandStatusPayload($command->fresh()));
    }

    private function queuedCommandResponse(Device $device, DeviceCommand $command, string $message): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'queued' => true,
            'command_id' => $command->id,
            'status_url' => route('devices.commands.status', [$device, $command]),
            'message' => $message,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function commandStatusPayload(DeviceCommand $command): array
    {
        $isDone = $command->status === 'done';
        $isFailed = $command->status === 'failed';

        return [
            'ok' => ! $isFailed,
            'command_id' => $command->id,
            'type' => $command->type,
            'status' => $command->status,
            'pending' => in_array($command->status, ['queued', 'dispatched'], true),
            'done' => $isDone,
            'failed' => $isFailed,
            'message' => $this->commandStatusMessage($command),
            'result' => $command->result,
            'error' => $command->error,
            'created_at' => $command->created_at?->toIso8601String(),
            'dispatched_at' => $command->dispatched_at?->toIso8601String(),
            'completed_at' => $command->completed_at?->toIso8601String(),
        ];
    }

    private function commandStatusMessage(DeviceCommand $command): string
    {
        if ($command->status === 'queued') {
            return 'Menunggu agent lokal mengambil perintah.';
        }

        if ($command->status === 'dispatched') {
            return 'Agent lokal sedang menjalankan perintah.';
        }

        if ($command->status === 'failed') {
            return $command->error ?: 'Perintah gagal dijalankan agent.';
        }

        $result = $command->result ?? [];

        return match ($command->type) {
            'get_info' => 'Koneksi berhasil. Info perangkat sudah diterima dari agent.',
            'push_all_users' => 'Sinkronisasi selesai: ' . (int) ($result['pushed'] ?? 0) . ' user dikirim ke perangkat.',
            'pull_attendance' => 'Tarik absensi selesai. ' . (string) ($result['note'] ?? 'Agent sudah menjalankan sinkronisasi absensi.'),
            'pull_biometrics' => 'Tarik biometrik selesai. Data yang cocok sudah diperbarui di sistem.',
            'clear_attendance' => ! empty($result['cleared']) ? 'Log absensi perangkat berhasil dikosongkan.' : 'Perintah kosongkan log selesai.',
            'sync_time' => 'Waktu perangkat berhasil disinkronkan' . (! empty($result['device_time']) ? ': ' . $result['device_time'] : '.') ,
            default => 'Perintah selesai dijalankan agent.',
        };
    }

    /**
     * Menandai daftar user mentah (dari agent) dengan status match terhadap
     * data Mahasiswa — setara hasil ZktecoService::pullUsers untuk view.
     *
     * @param array<int, array<string, mixed>> $users
     * @return array<int, array<string, mixed>>
     */
    private function matchDeviceUsers(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $userIds = array_values(array_filter(array_unique(
            array_map(fn ($u) => trim((string) ($u['userid'] ?? '')), $users)
        )));

        $matched = Mahasiswa::query()
            ->whereIn('nim', $userIds)
            ->orWhereIn('rfid_uid', $userIds)
            ->get(['id', 'nim', 'nama', 'rfid_uid']);

        $rows = [];
        foreach ($users as $u) {
            $userid = trim((string) ($u['userid'] ?? ''));
            $mhs = $matched->first(fn ($m) => (string) $m->nim === $userid || (string) $m->rfid_uid === $userid);

            $rows[] = [
                'uid' => (int) ($u['uid'] ?? 0),
                'userid' => $userid,
                'name' => trim((string) ($u['name'] ?? '')),
                'role' => (int) ($u['role'] ?? 0),
                'matched' => (bool) $mhs,
                'mahasiswa_nama' => $mhs?->nama,
            ];
        }

        return $rows;
    }
}
