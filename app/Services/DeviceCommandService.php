<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Mahasiswa;
use App\Services\ZktecoService;
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
     * Payload untuk push_all_users: seluruh mahasiswa dalam format alat.
     * Mapping identik dengan ZktecoService::pushUser
     * (uid = id, userid = nim, name = nama maks 24 char).
     *
     * @return array{users: array<int, array{uid: int, userid: string, name: string}>}
     */
    public function buildAllUsersPayload(): array
    {
        $users = [];

        Mahasiswa::query()
            ->select(['id', 'nim', 'nama'])
            ->orderBy('id')
            ->chunk(500, function ($mahasiswas) use (&$users): void {
                foreach ($mahasiswas as $m) {
                    $users[] = $this->mapUser($m);
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
            'users' => [$this->mapUser($mahasiswa)],
            'mahasiswa_id' => (int) $mahasiswa->id,
            'method' => $method,
        ];
    }

    /**
     * @return array{uid: int, userid: string, name: string}
     */
    private function mapUser(Mahasiswa $m): array
    {
        return [
            'uid' => (int) $m->id,
            'userid' => (string) $m->nim,
            'name' => mb_substr((string) $m->nama, 0, 24),
        ];
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

        (new ZktecoService($command->device))->syncRegisteredStudentBiometricsFromUsers(
            $users,
            fn (int $uid): bool => $fingerprintFlags[$uid] ?? false
        );
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
