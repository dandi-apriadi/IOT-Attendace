<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Mengelola antrean perintah (DeviceCommand) dari SERVER (VPS) menuju AGENT
 * lokal yang berkomunikasi langsung dengan alat ZKTeco.
 *
 * Agent bersifat stateless (tanpa DB), jadi setiap perintah yang butuh data
 * (mis. daftar user untuk di-push) harus membawa data lengkap di payload.
 */
class DeviceCommandService
{
    /**
     * Membuat satu perintah baru pada antrean perangkat.
     *
     * @param array<string, mixed> $payload
     */
    public function enqueue(Device $device, string $type, array $payload = [], ?int $userId = null): DeviceCommand
    {
        return DeviceCommand::create([
            'device_id' => $device->id,
            'type' => $type,
            'payload' => $payload ?: null,
            'status' => 'queued',
            'requested_by' => $userId,
        ]);
    }

    /**
     * Payload untuk push_all_users: seluruh mahasiswa dan dosen dalam format alat.
     *
     * @return array{users: array<int, array<string, mixed>>}
     */
    public function buildAllUsersPayload(): array
    {
        $users = [];

        Mahasiswa::query()
            ->select(['id', 'nim', 'nama', 'fingerprint_data'])
            ->orderBy('id')
            ->chunk(500, function ($mahasiswas) use (&$users): void {
                foreach ($mahasiswas as $m) {
                    $users[] = $this->mapMahasiswaUser($m);
                }
            });

        User::query()
            ->where('role', 'dosen')
            ->select(['id', 'name', 'zk_uid', 'fingerprint_data'])
            ->orderBy('id')
            ->chunk(500, function ($dosens) use (&$users): void {
                foreach ($dosens as $dosen) {
                    $users[] = $this->mapDosenUser($dosen);
                }
            });

        return ['users' => $users];
    }

    /**
     * Payload untuk push_user: satu mahasiswa.
     *
     * @return array{users: array<int, array{uid: int, userid: string, name: string}>, mahasiswa_id: int, method: string}
     */
    public function buildSingleUserPayload(Mahasiswa $mahasiswa, string $method = 'rfid_fingerprint'): array
    {
        return [
            'users' => [$this->mapMahasiswaUser($mahasiswa)],
            'mahasiswa_id' => (int) $mahasiswa->id,
            'method' => $method,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMahasiswaUser(Mahasiswa $m): array
    {
        $row = [
            'uid' => (int) $m->id,
            'userid' => (string) $m->nim,
            'name' => mb_substr((string) $m->nama, 0, 24),
            'kind' => 'mahasiswa',
        ];

        $fingerprintData = $this->decodeStoredFingerprintPayload($m->fingerprint_data);
        if ($fingerprintData !== []) {
            $row['fingerprint_data'] = $fingerprintData;
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDosenUser(User $dosen): array
    {
        $uid = $dosen->zktecoUid();
        $row = [
            'uid' => $uid,
            'userid' => (string) $uid,
            'name' => mb_substr((string) $dosen->name, 0, 24),
            'kind' => 'dosen',
        ];

        if (is_array($dosen->fingerprint_data) && $dosen->fingerprint_data !== []) {
            $row['fingerprint_data'] = $dosen->fingerprint_data;
        }

        return $row;
    }

    /**
     * Menerapkan hasil eksekusi agent ke sistem untuk perintah yang
     * mengembalikan data. Untuk perintah lain, hasil cukup tersimpan di
     * kolom result (ditangani controller submitResult).
     *
     * @param array<string, mixed> $result
     */
    public function applyResult(DeviceCommand $command, array $result): void
    {
        match ($command->type) {
            'scan_devices' => $this->applyScanDevicesResult($command, $result),
            'read_user' => $this->applyReadUserResult($command, $result),
            'pull_biometrics' => $this->applyBiometricsResult($command, $result),
            default => null,
        };
    }

    /**
     * Mendaftarkan atau memperbarui perangkat ZKTeco yang ditemukan agent.
     *
     * @param array<string, mixed> $result
     */
    private function applyScanDevicesResult(DeviceCommand $command, array $result): void
    {
        $devices = $result['devices'] ?? null;
        if (! is_array($devices)) {
            return;
        }

        foreach ($devices as $row) {
            if (! is_array($row)) {
                continue;
            }

            $ip = trim((string) ($row['ip_address'] ?? $row['ip'] ?? ''));
            if ($ip === '') {
                continue;
            }

            $port = (int) ($row['port'] ?? 4370);
            $serial = trim((string) ($row['serial_number'] ?? ''));
            $deviceId = $serial !== ''
                ? 'ZKTECO-' . strtoupper(Str::slug($serial, '-'))
                : 'ZKTECO-' . str_replace(['.', ':'], '-', $ip) . '-' . $port;

            $device = Device::query()->where('device_id', $deviceId)->first()
                ?: Device::query()
                    ->where('type', 'zkteco')
                    ->where('ip_address', $ip)
                    ->where('port', $port)
                    ->first();

            if (! $device) {
                $device = new Device([
                    'device_id' => $deviceId,
                    'token_hash' => hash('sha256', Str::random(40)),
                ]);
            }

            $deviceName = trim((string) ($row['device_name'] ?? ''));
            $device->fill([
                'name' => $deviceName !== '' ? $deviceName : ($device->name ?: 'ZKTeco ' . $ip),
                'type' => 'zkteco',
                'ip_address' => $ip,
                'port' => $port,
                'is_active' => true,
                'last_seen_at' => now(),
            ]);
            $device->save();
        }
    }

    /**
     * Menjalankan pencocokan biometrik (kartu/sidik jari) dari data user mentah
     * yang dikirim agent. Memakai ulang logika ZktecoService tanpa koneksi UDP.
     *
     * Agent menyertakan flag has_fingerprint per user, jadi resolver hanya
     * membaca nilai itu — tidak perlu query ulang ke alat.
     *
     * @param array<string, mixed> $result
     */
    private function applyBiometricsResult(DeviceCommand $command, array $result): void
    {
        $users = $result['users'] ?? null;
        if (! is_array($users)) {
            return;
        }

        $fingerprintFlags = [];
        foreach ($users as $u) {
            $fingerprintFlags[(int) ($u['uid'] ?? 0)] = ! empty($u['has_fingerprint']);
        }

        $studentResult = (new ZktecoService($command->device))->syncRegisteredStudentBiometricsFromUsers(
            $users,
            fn (int $uid): bool => $fingerprintFlags[$uid] ?? false
        );

        $dosenUpdated = $this->applyDosenBiometricsFromUsers($users);

        if ((int) ($studentResult['fingerprint_updated'] ?? 0) > 0 || $dosenUpdated > 0) {
            $this->enqueueAllActiveZktecoUserSyncs($command->requested_by);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $users
     */
    public function applyDosenBiometricsFromUsers(array $users): int
    {
        $dosenByUid = User::query()
            ->where('role', 'dosen')
            ->get(['id', 'zk_uid'])
            ->keyBy(fn (User $dosen) => $dosen->zktecoUid());

        $updated = 0;
        foreach ($users as $user) {
            $uid = (int) ($user['uid'] ?? 0);
            $fingerprintData = $user['fingerprint_data'] ?? null;

            if ($uid <= 0 || empty($user['has_fingerprint']) || ! is_array($fingerprintData) || $fingerprintData === []) {
                continue;
            }

            $dosen = $dosenByUid->get($uid);
            if (! $dosen) {
                continue;
            }

            $dosen->forceFill([
                'zk_uid' => $uid,
                'fingerprint_data' => $fingerprintData,
                'fingerprint_synced_at' => now(),
            ])->save();

            $updated++;
        }

        return $updated;
    }

    public function enqueueAllActiveZktecoUserSyncs(?int $userId = null): int
    {
        $payload = $this->buildAllUsersPayload();
        $queued = 0;

        Device::query()
            ->where('type', 'zkteco')
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->each(function (Device $device) use ($payload, $userId, &$queued): void {
                $this->enqueue($device, 'push_all_users', $payload, $userId);
                $queued++;
            });

        return $queued;
    }

    /**
     * @return array<string, string>
     */
    private function decodeStoredFingerprintPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return array_filter($payload, fn ($value) => is_string($value) && $value !== '');
        }

        if (! is_string($payload) || $payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return [];
        }

        return array_filter($decoded, fn ($value) => is_string($value) && $value !== '');
    }

    /**
     * Menyimpan kartu/sidik jari hasil pembacaan alat ke mahasiswa terkait.
     * Sejalan dengan MahasiswaController::syncFromDevice (mode standalone).
     *
     * @param array<string, mixed> $result
     */
    private function applyReadUserResult(DeviceCommand $command, array $result): void
    {
        $payload = $command->payload ?? [];
        $mahasiswaId = (int) ($payload['mahasiswa_id'] ?? 0);
        $method = (string) ($payload['method'] ?? 'rfid_fingerprint');

        if ($mahasiswaId <= 0 || empty($result['found'])) {
            return;
        }

        $mahasiswa = Mahasiswa::find($mahasiswaId);
        if (! $mahasiswa) {
            return;
        }

        $device = $command->device;
        $marker = 'enrolled@' . ($device?->device_id ?: $device?->ip_address ?: 'device');

        $updates = [];
        if (in_array($method, ['rfid', 'rfid_fingerprint'], true) && ! empty($result['cardno'])) {
            $updates['rfid_uid'] = (string) $result['cardno'];
        }
        if (in_array($method, ['fingerprint', 'rfid_fingerprint'], true) && ! empty($result['has_fingerprint'])) {
            $updates['fingerprint_data'] = $marker;
        }

        if ($updates !== []) {
            $mahasiswa->update($updates);
        }
    }
}
