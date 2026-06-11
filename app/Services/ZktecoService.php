<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jmrashed\Zkteco\Lib\Helper\Util;
use Jmrashed\Zkteco\Lib\ZKTeco;

/**
 * Pusat seluruh komunikasi dengan perangkat ZKTeco (mis. Solution X609).
 *
 * Membungkus library jmrashed/zkteco dengan retry koneksi (koneksi UDP via
 * Wi-Fi terbukti flaky) dan penanganan error agar aman dipakai baik dari
 * console command (terjadwal) maupun dari controller (aksi UI).
 */
class ZktecoService
{
    private Device $device;
    private string $ip;
    private int $port;
    private ?ZKTeco $zk = null;

    public function __construct(Device $device)
    {
        $this->device = $device;
        $this->ip = (string) $device->ip_address;
        $this->port = (int) ($device->port ?: 4370);
    }

    /**
     * Membuat instance dari IP/port langsung (tanpa model Device).
     */
    public static function fromAddress(string $ip, int $port = 4370): self
    {
        $device = new Device(['ip_address' => $ip, 'port' => $port]);

        return new self($device);
    }

    /**
     * Membuka koneksi ke perangkat dengan retry hingga 3x.
     * Memperbarui last_seen_at saat berhasil.
     */
    public function connect(int $attempts = 3): bool
    {
        if (empty($this->ip)) {
            return false;
        }

        for ($i = 0; $i < $attempts; $i++) {
            $this->zk = new ZKTeco($this->ip, $this->port);

            if (@$this->zk->connect()) {
                if ($this->device->exists) {
                    $this->device->forceFill(['last_seen_at' => now()])->save();
                }

                return true;
            }

            // Jeda singkat sebelum mencoba lagi.
            usleep(500000);
        }

        $this->zk = null;

        return false;
    }

    public function disconnect(): void
    {
        if ($this->zk) {
            @$this->zk->disconnect();
            $this->zk = null;
        }
    }

    /**
     * Menjalankan callback dalam sesi koneksi (connect -> callback -> disconnect).
     * Menahan deprecation notice dari vendor agar tidak merusak output JSON.
     *
     * @throws \RuntimeException jika gagal terhubung.
     */
    public function withConnection(callable $fn)
    {
        // Operasi massal (mis. push 200+ user) bisa berjalan lebih dari batas
        // default max_execution_time. Lepaskan batas selama sesi perangkat.
        @set_time_limit(0);

        $previous = error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

        try {
            if (! $this->connect()) {
                throw new \RuntimeException("Gagal terhubung ke perangkat di {$this->ip}:{$this->port}.");
            }

            return $fn($this->zk);
        } finally {
            $this->disconnect();
            error_reporting($previous);
        }
    }

    /**
     * Tes koneksi sederhana.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        try {
            $ok = $this->withConnection(fn () => true);

            return ['ok' => (bool) $ok, 'message' => 'Terhubung ke perangkat.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mengambil informasi perangkat (versi, serial, nama, waktu).
     */
    public function getInfo(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            return [
                'version' => $this->clean((string) $zk->version()),
                'serial_number' => $this->clean((string) $zk->serialNumber()),
                'device_name' => $this->clean((string) $zk->deviceName()),
                'platform' => $this->clean((string) $zk->platform()),
                'device_time' => $this->clean((string) $zk->getTime()),
            ];
        });
    }

    /**
     * Mendaftarkan/memperbarui satu mahasiswa ke perangkat.
     * Mapping: uid = id mahasiswa, userid = nim, name = nama (maks 24 char).
     */
    public function pushUser(Mahasiswa $m): bool
    {
        return (bool) $this->withConnection(function (ZKTeco $zk) use ($m) {
            $zk->setUser(
                (int) $m->id,
                (string) $m->nim,
                mb_substr((string) $m->nama, 0, 24),
                '',
                Util::LEVEL_USER,
                0
            );

            return true;
        });
    }

    /**
     * Mendorong SELURUH mahasiswa ke perangkat dalam satu sesi koneksi,
     * lalu memverifikasi dengan membaca ulang daftar user dan mengulang
     * (retry) yang belum terdaftar — koneksi UDP kadang men-drop paket
     * sehingga sebagian setUser tidak benar-benar tersimpan.
     *
     * @return array{success: int, failed: int, total: int, retried: int, errors: array<string>}
     */
    public function pushAllUsers(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            $errors = [];

            $push = function ($m) use ($zk, &$errors): bool {
                try {
                    $zk->setUser(
                        (int) $m->id,
                        (string) $m->nim,
                        mb_substr((string) $m->nama, 0, 24),
                        '',
                        Util::LEVEL_USER,
                        0
                    );

                    return true;
                } catch (\Throwable $e) {
                    if (count($errors) < 10) {
                        $errors[] = "{$m->nim}: {$e->getMessage()}";
                    }

                    return false;
                }
            };

            // Pass 1: dorong semua mahasiswa.
            $total = 0;
            Mahasiswa::query()
                ->select(['id', 'nim', 'nama'])
                ->orderBy('id')
                ->chunk(200, function ($mahasiswas) use ($push, &$total) {
                    foreach ($mahasiswas as $m) {
                        $push($m);
                        $total++;
                    }
                });

            // Verifikasi: baca kembali uid yang benar-benar terdaftar.
            $registered = collect($zk->getUser() ?: [])
                ->pluck('uid')
                ->map(fn ($u) => (int) $u)
                ->all();

            // Pass 2: ulangi mahasiswa yang belum terdaftar (mis. drop UDP).
            $retried = 0;
            Mahasiswa::query()
                ->select(['id', 'nim', 'nama'])
                ->whereNotIn('id', $registered ?: [0])
                ->orderBy('id')
                ->chunk(100, function ($mahasiswas) use ($push, &$retried) {
                    foreach ($mahasiswas as $m) {
                        $retried++;
                        $push($m);
                    }
                });

            // Hitung jumlah terverifikasi akhir.
            $verified = collect($zk->getUser() ?: [])->count();

            return [
                'success' => $verified,
                'failed' => max(0, $total - $verified),
                'total' => $total,
                'retried' => $retried,
                'errors' => $errors,
            ];
        });
    }

    /**
     * Membaca daftar user dari perangkat, lengkap dengan status match
     * terhadap data Mahasiswa (berdasarkan nim atau rfid_uid = userid).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullUsers(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            $users = $zk->getUser();
            $rows = [];

            if (! is_array($users)) {
                return $rows;
            }

            // Kumpulkan userid untuk dicocokkan sekali jalan.
            $userIds = [];
            foreach ($users as $u) {
                $userIds[] = trim($this->clean((string) ($u['userid'] ?? '')));
            }
            $userIds = array_filter(array_unique($userIds));

            $matched = Mahasiswa::query()
                ->whereIn('nim', $userIds)
                ->orWhereIn('rfid_uid', $userIds)
                ->get(['id', 'nim', 'nama', 'rfid_uid']);

            foreach ($users as $u) {
                $userid = trim($this->clean((string) ($u['userid'] ?? '')));
                $name = trim($this->clean((string) ($u['name'] ?? '')));

                $mhs = $matched->first(function ($m) use ($userid) {
                    return (string) $m->nim === $userid || (string) $m->rfid_uid === $userid;
                });

                $rows[] = [
                    'uid' => (int) ($u['uid'] ?? 0),
                    'userid' => $userid,
                    'name' => $name,
                    'role' => (int) ($u['role'] ?? 0),
                    'matched' => (bool) $mhs,
                    'mahasiswa_nama' => $mhs?->nama,
                ];
            }

            return $rows;
        });
    }

    /**
     * Menghapus satu user dari perangkat berdasarkan uid.
     */
    public function removeUser(int $uid): bool
    {
        return (bool) $this->withConnection(function (ZKTeco $zk) use ($uid) {
            $zk->removeUser($uid);

            return true;
        });
    }

    /**
     * Menyinkronkan waktu perangkat dengan waktu server.
     */
    public function syncTime(): bool
    {
        return (bool) $this->withConnection(function (ZKTeco $zk) {
            $zk->setTime(now()->format('Y-m-d H:i:s'));

            return true;
        });
    }

    /**
     * Mengosongkan log absensi pada perangkat.
     */
    public function clearAttendance(): bool
    {
        return (bool) $this->withConnection(function (ZKTeco $zk) {
            $zk->clearAttendance();

            return true;
        });
    }

    /**
     * Mengambil log absensi mentah dari perangkat (sudah dibersihkan).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pullAttendanceRaw(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            $att = $zk->getAttendance();

            return is_array($att) ? $att : [];
        });
    }

    /**
     * Menarik absensi dari perangkat dan menyimpannya ke tabel absensi.
     * Logika matching mahasiswa -> jadwal dipindahkan dari command lama.
     *
     * @return array{inserted: int, skipped: int, total: int}
     */
    public function importAttendance(): array
    {
        $attendances = $this->pullAttendanceRaw();

        $inserted = 0;
        $skipped = 0;

        foreach ($attendances as $record) {
            $uid = $record['id'] ?? null;
            $timestamp = $record['timestamp'] ?? null;

            if (! $uid || ! $timestamp) {
                $skipped++;
                continue;
            }

            $time = Carbon::parse($timestamp);
            $date = $time->toDateString();

            $mahasiswa = Mahasiswa::where('rfid_uid', $uid)
                ->orWhere('nim', $uid)
                ->first();

            if (! $mahasiswa) {
                $skipped++;
                continue;
            }

            $hariInggris = $time->format('l');
            $jadwal = Jadwal::where('kelas_id', $mahasiswa->kelas_id)
                ->where('hari', $hariInggris)
                ->whereTime('jam_mulai', '<=', $time->toTimeString())
                ->whereTime('jam_selesai', '>=', $time->toTimeString())
                ->first();

            if (! $jadwal) {
                $skipped++;
                continue;
            }

            $exists = Absensi::where('mahasiswa_id', $mahasiswa->id)
                ->where('tanggal', $date)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            Absensi::create([
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $date,
                'waktu_tap' => $time->toTimeString(),
                'metode_absensi' => 'Fingerprint',
                'status' => 'Hadir',
            ]);
            $inserted++;
        }

        if ($inserted > 0) {
            Log::info("ZKTeco import: {$inserted} absensi baru dari {$this->ip}");
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
            'total' => count($attendances),
        ];
    }

    /**
     * Membersihkan nilai respons perangkat dari prefix "~Key=" dan null byte.
     */
    private function clean(string $value): string
    {
        $value = str_replace("\0", '', $value);
        $value = trim($value);

        // Respons firmware kerap berbentuk "~SerialNumber=JHG..." -> ambil setelah '='.
        if (str_starts_with($value, '~') && str_contains($value, '=')) {
            $value = substr($value, strpos($value, '=') + 1);
        }

        return trim($value);
    }
}
