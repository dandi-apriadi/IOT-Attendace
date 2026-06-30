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
    private const SOCKET_TIMEOUT_SECONDS = 3;

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
            // Tutup socket lama sebelum membuat koneksi baru agar tidak leak.
            $this->disconnect();

            $this->zk = new ZKTeco($this->ip, $this->port);
            $this->configureSocketTimeout($this->zk);

            if (@$this->zk->connect()) {
                if ($this->device->exists) {
                    $this->device->forceFill(['last_seen_at' => now()])->save();
                }

                return true;
            }

            // Jeda sebelum mencoba lagi (semakin lama per percobaan).
            usleep(500_000 * ($i + 1));
        }

        $this->zk = null;

        return false;
    }

    private function configureSocketTimeout(ZKTeco $zk): void
    {
        $timeout = ['sec' => self::SOCKET_TIMEOUT_SECONDS, 'usec' => 0];

        @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);
        @socket_set_option($zk->_zkclient, SOL_SOCKET, SO_SNDTIMEO, $timeout);
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
            if (! $this->connect(3)) {
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
     * Mendorong SELURUH mahasiswa dan dosen ke perangkat dalam satu sesi koneksi,
     * lalu memverifikasi dengan membaca ulang daftar user dan mengulang
     * (retry) yang belum terdaftar — koneksi UDP kadang men-drop paket
     * sehingga sebagian setUser tidak benar-benar tersimpan.
     *
     * @return array{success: int, failed: int, total: int, retried: int, fingerprints: int, errors: array<string>}
     */
    public function pushAllUsers(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            $errors = [];
            $users = app(DeviceCommandService::class)->buildAllUsersPayload()['users'];
            $fingerprints = 0;

            $push = function (array $user) use ($zk, &$errors, &$fingerprints): bool {
                try {
                    $zk->setUser(
                        (int) ($user['uid'] ?? 0),
                        (string) ($user['userid'] ?? ''),
                        mb_substr((string) ($user['name'] ?? ''), 0, 24),
                        '',
                        Util::LEVEL_USER,
                        0
                    );

                    $fingerprintData = $this->decodeFingerprintPayload($user['fingerprint_data'] ?? null);
                    if ($fingerprintData !== []) {
                        $fingerprints += (int) $zk->setFingerprint((int) ($user['uid'] ?? 0), $fingerprintData);
                    }

                    return true;
                } catch (\Throwable $e) {
                    if (count($errors) < 10) {
                        $label = (string) ($user['userid'] ?? $user['uid'] ?? '-');
                        $errors[] = "{$label}: {$e->getMessage()}";
                    }

                    return false;
                }
            };

            $total = count($users);
            foreach ($users as $user) {
                $push($user);
            }

            // Verifikasi: baca kembali uid yang benar-benar terdaftar.
            $registered = collect($zk->getUser() ?: [])
                ->pluck('uid')
                ->map(fn ($u) => (int) $u)
                ->all();

            // Pass 2: ulangi user yang belum terdaftar (mis. drop UDP).
            $retried = 0;
            foreach ($users as $user) {
                if (! in_array((int) ($user['uid'] ?? 0), $registered, true)) {
                    $retried++;
                    $push($user);
                }
            }

            // Hitung jumlah terverifikasi akhir.
            $registeredAfterRetry = collect($zk->getUser() ?: [])
                ->pluck('uid')
                ->map(fn ($u) => (int) $u)
                ->all();
            $expectedUids = array_map(fn (array $user) => (int) ($user['uid'] ?? 0), $users);
            $verified = count(array_intersect($expectedUids, $registeredAfterRetry));

            return [
                'success' => $verified,
                'failed' => max(0, $total - $verified),
                'total' => $total,
                'retried' => $retried,
                'fingerprints' => $fingerprints,
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
     * Menarik data biometrik user yang sudah ada di alat, lalu memperbarui
     * mahasiswa yang sudah terdaftar di sistem. Matching utama memakai NIM
     * dari userid alat, fallback memakai uid = id mahasiswa.
     *
     * @return array{scanned: int, matched: int, updated: int, rfid_updated: int, fingerprint_updated: int, unmatched: int, conflicts: int, errors: array<string>}
     */
    public function syncRegisteredStudentBiometrics(): array
    {
        return $this->withConnection(function (ZKTeco $zk) {
            $users = $zk->getUser();
            $users = is_array($users) ? $users : [];
            $fingerprintDataByUid = [];

            foreach ($users as $index => $user) {
                $uid = (int) ($user['uid'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }

                try {
                    $fp = $zk->getFingerprint($uid);
                    $fingerprintData = $this->encodeFingerprintPayload($fp);

                    if ($fingerprintData !== []) {
                        $fingerprintDataByUid[$uid] = $fingerprintData;
                        $users[$index]['has_fingerprint'] = true;
                        $users[$index]['fingerprint_data'] = $fingerprintData;
                    }
                } catch (\Throwable $e) {
                    $users[$index]['has_fingerprint'] = false;
                }
            }

            $result = $this->syncRegisteredStudentBiometricsFromUsers(
                $users,
                fn (int $uid): bool => isset($fingerprintDataByUid[$uid])
            );

            $result['dosen_fingerprint_updated'] = app(DeviceCommandService::class)
                ->applyDosenBiometricsFromUsers($users);

            return $result;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array{scanned: int, matched: int, updated: int, rfid_updated: int, fingerprint_updated: int, unmatched: int, conflicts: int, errors: array<string>}
     */
    public function syncRegisteredStudentBiometricsFromUsers(array $users, ?callable $fingerprintResolver = null): array
    {
        $result = [
            'scanned'             => count($users),
            'matched'             => 0,
            'updated'             => 0,
            'rfid_updated'        => 0,
            'fingerprint_updated' => 0,
            'unmatched'           => 0,
            'conflicts'           => 0,
            'errors'              => [],
        ];

        if (empty($users)) {
            return $result;
        }

        // Batch pre-load: kumpulkan semua userid/uid/cardno terlebih dahulu.
        $allUserids = [];
        $allUids    = [];
        $allCardNos = [];

        foreach ($users as $user) {
            $userid  = trim($this->clean((string) ($user['userid'] ?? '')));
            $uid     = (int) ($user['uid'] ?? 0);
            $cardno  = $this->normalizeCardNo($user['cardno'] ?? null);

            if ($userid !== '') {
                $allUserids[] = $userid;
            }
            if ($uid > 0) {
                $allUids[] = $uid;
            }
            if ($cardno !== null) {
                $allCardNos[] = $cardno;
            }
        }

        $allUserids = array_values(array_unique($allUserids));
        $allUids    = array_values(array_unique($allUids));
        $allCardNos = array_values(array_unique($allCardNos));

        // 3 query pengganti N query mahasiswa + N query conflict.
        $byNim      = $allUserids ? Mahasiswa::whereIn('nim', $allUserids)->get()->keyBy('nim')  : collect();
        $byId       = $allUids    ? Mahasiswa::whereIn('id', $allUids)->get()->keyBy('id')       : collect();
        $cardOwners = $allCardNos ? Mahasiswa::whereIn('rfid_uid', $allCardNos)->get(['id', 'rfid_uid'])->keyBy('rfid_uid') : collect();

        $fingerprintMarker = 'enrolled@' . ($this->device->device_id ?: $this->ip);

        foreach ($users as $user) {
            $uid    = (int) ($user['uid'] ?? 0);
            $userid = trim($this->clean((string) ($user['userid'] ?? '')));
            $cardno = $this->normalizeCardNo($user['cardno'] ?? null);

            // Match mahasiswa: coba NIM dulu, fallback ke ID numerik.
            $mahasiswa = $userid !== '' ? $byNim->get($userid) : null;
            if (! $mahasiswa && $uid > 0) {
                $mahasiswa = $byId->get($uid);
            }

            if (! $mahasiswa) {
                $result['unmatched']++;
                continue;
            }

            $result['matched']++;
            $updates = [];

            if ($cardno !== null) {
                $cardOwner      = $cardOwners->get($cardno);
                $cardOwnerExists = $cardOwner && $cardOwner->id !== $mahasiswa->id;

                if ($cardOwnerExists) {
                    $result['conflicts']++;
                    if (count($result['errors']) < 10) {
                        $result['errors'][] = "{$mahasiswa->nim}: kartu {$cardno} sudah dipakai mahasiswa lain.";
                    }
                } elseif ((string) $mahasiswa->rfid_uid !== $cardno) {
                    $updates['rfid_uid'] = $cardno;
                    $result['rfid_updated']++;
                }
            }

            $hasFingerprint = $fingerprintResolver ? (bool) $fingerprintResolver($uid) : false;
            if ($hasFingerprint && (string) $mahasiswa->fingerprint_data !== $fingerprintMarker) {
                $updates['fingerprint_data'] = $fingerprintMarker;
                $result['fingerprint_updated']++;
            }

            if ($updates !== []) {
                $mahasiswa->update($updates);
                $result['updated']++;
            }
        }

        return $result;
    }

    /**
     * Membaca satu user dari perangkat berdasarkan uid, termasuk nomor kartu
     * (cardno) dan status sidik jari (apakah sudah ter-enroll).
     *
     * @return array{found: bool, uid: int, userid: ?string, name: ?string, cardno: ?string, has_fingerprint: bool}
     */
    public function readUser(int $uid): array
    {
        return $this->withConnection(function (ZKTeco $zk) use ($uid) {
            $users = $zk->getUser();
            $match = null;

            if (is_array($users)) {
                foreach ($users as $u) {
                    if ((int) ($u['uid'] ?? 0) === $uid) {
                        $match = $u;
                        break;
                    }
                }
            }

            if (! $match) {
                return [
                    'found' => false,
                    'uid' => $uid,
                    'userid' => null,
                    'name' => null,
                    'cardno' => null,
                    'has_fingerprint' => false,
                ];
            }

            // Nomor kartu: "0000000000" / 0 berarti belum ada kartu.
            $cardno = trim($this->clean((string) ($match['cardno'] ?? '')));
            if ($cardno === '' || (int) $cardno === 0) {
                $cardno = null;
            }

            // Cek sidik jari (template) — bungkus try/catch karena bisa lambat/gagal.
            $hasFingerprint = false;
            try {
                $fp = $zk->getFingerprint($uid);
                $hasFingerprint = is_array($fp) ? count(array_filter($fp)) > 0 : ! empty($fp);
            } catch (\Throwable $e) {
                $hasFingerprint = false;
            }

            return [
                'found' => true,
                'uid' => $uid,
                'userid' => trim($this->clean((string) ($match['userid'] ?? ''))),
                'name' => trim($this->clean((string) ($match['name'] ?? ''))),
                'cardno' => $cardno,
                'has_fingerprint' => $hasFingerprint,
            ];
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

        return $this->importAttendanceFromRecords($attendances);
    }

    /**
     * Memproses log absensi mentah yang sudah ditarik dari alat.
     * Menggunakan batch pre-loading untuk menghindari N+1 query:
     *   - 2 query untuk lookup mahasiswa (by rfid_uid + by nim)
     *   - 1 query untuk pre-load semua jadwal relevan
     *   - 1 query untuk batch-check absensi yang sudah ada
     *
     * @param array<int, array<string, mixed>> $attendances
     * @return array{inserted: int, skipped: int, total: int}
     */
    public function importAttendanceFromRecords(array $attendances): array
    {
        if (empty($attendances)) {
            return ['inserted' => 0, 'skipped' => 0, 'total' => 0];
        }

        $attendanceSessions = app(AttendanceSessionService::class);
        $inserted = 0;
        $skipped = 0;
        $affectedLiveCaches = [];

        // Kumpulkan semua UID untuk batch lookup mahasiswa.
        $allUids = array_values(array_filter(array_unique(
            array_map(fn ($r) => $r['id'] ?? null, $attendances)
        )));

        // Batch load mahasiswas (2 query, bukan N query).
        $byRfid = $allUids ? Mahasiswa::whereIn('rfid_uid', $allUids)->get()->keyBy('rfid_uid') : collect();
        $byNim  = $allUids ? Mahasiswa::whereIn('nim', $allUids)->get()->keyBy('nim')           : collect();

        // Kumpulkan nama hari dari semua timestamp untuk pre-load jadwal.
        $dayNameSet = [];
        foreach ($attendances as $record) {
            $ts = $record['timestamp'] ?? null;
            if ($ts) {
                foreach ($attendanceSessions->dayNames(Carbon::parse($ts)) as $dn) {
                    $dayNameSet[$dn] = true;
                }
            }
        }

        // Pre-load semua jadwal yang mungkin relevan beserta semesternya (1 query).
        $jadwals = $dayNameSet
            ? Jadwal::query()->with('semesterAkademik')->whereIn('hari', array_keys($dayNameSet))->get()
            : collect();

        // Cache hasil matching jadwal per (kelas_id, date, time) agar tidak dihitung ulang.
        $jadwalCache = [];

        $findJadwal = function (int $kelasId, Carbon $time) use ($jadwals, &$jadwalCache, $attendanceSessions): ?Jadwal {
            $date    = $time->toDateString();
            $timeStr = $time->toTimeString();
            $key     = "{$kelasId}|{$date}|{$timeStr}";

            if (array_key_exists($key, $jadwalCache)) {
                return $jadwalCache[$key];
            }

            $dayNames = $attendanceSessions->dayNames($time);

            $match = $jadwals->first(function (Jadwal $j) use ($kelasId, $dayNames, $timeStr, $date) {
                if ((int) $j->kelas_id !== $kelasId || ! in_array($j->hari, $dayNames, true)) {
                    return false;
                }
                if ($timeStr < (string) $j->jam_mulai || $timeStr > (string) $j->jam_selesai) {
                    return false;
                }
                $sem = $j->semesterAkademik;

                return $sem
                    && $date >= $sem->tanggal_mulai->toDateString()
                    && $date <= $sem->tanggal_selesai->toDateString();
            });

            $jadwalCache[$key] = $match;

            return $match;
        };

        // Pass 1: resolve mahasiswa + jadwal untuk tiap record, kumpulkan kandidat insert.
        $candidates = [];
        foreach ($attendances as $record) {
            $uid       = $record['id'] ?? null;
            $timestamp = $record['timestamp'] ?? null;

            if (! $uid || ! $timestamp) {
                $skipped++;
                continue;
            }

            $time      = Carbon::parse($timestamp);
            $mahasiswa = $byRfid->get($uid) ?? $byNim->get($uid);

            if (! $mahasiswa) {
                $skipped++;
                continue;
            }

            $jadwal = $findJadwal((int) $mahasiswa->kelas_id, $time);

            if (! $jadwal) {
                $skipped++;
                continue;
            }

            $candidates[] = [
                'mahasiswa' => $mahasiswa,
                'jadwal'    => $jadwal,
                'time'      => $time,
                'date'      => $time->toDateString(),
                'status'    => $attendanceSessions->statusForTap($time->toTimeString(), $jadwal->jam_mulai),
            ];
        }

        // Batch check absensi yang sudah ada (1 query, bukan N query).
        $existingKeySet = [];
        if (! empty($candidates)) {
            $mIds  = array_unique(array_map(fn ($c) => $c['mahasiswa']->id, $candidates));
            $jIds  = array_unique(array_map(fn ($c) => $c['jadwal']->id,    $candidates));
            $dates = array_unique(array_map(fn ($c) => $c['date'],          $candidates));

            Absensi::query()
                ->whereIn('mahasiswa_id', $mIds)
                ->whereIn('jadwal_id',    $jIds)
                ->whereIn('tanggal',      $dates)
                ->get(['mahasiswa_id', 'tanggal', 'jadwal_id'])
                ->each(function ($a) use (&$existingKeySet): void {
                    $existingKeySet["{$a->mahasiswa_id}|{$a->tanggal}|{$a->jadwal_id}"] = true;
                });
        }

        // Pass 2: insert record baru saja.
        $insertedInBatch = [];
        foreach ($candidates as $c) {
            $key = "{$c['mahasiswa']->id}|{$c['date']}|{$c['jadwal']->id}";

            if (isset($existingKeySet[$key]) || isset($insertedInBatch[$key])) {
                $skipped++;
                continue;
            }

            Absensi::create([
                'mahasiswa_id'   => $c['mahasiswa']->id,
                'jadwal_id'      => $c['jadwal']->id,
                'tanggal'        => $c['date'],
                'waktu_tap'      => $c['time']->toTimeString(),
                'metode_absensi' => 'Fingerprint',
                'status'         => $c['status'],
            ]);

            $insertedInBatch[$key] = true;
            $affectedLiveCaches[$c['date'] . '|' . $c['jadwal']->id] = [$c['date'], (int) $c['jadwal']->id];
            $inserted++;
        }

        foreach ($affectedLiveCaches as [$date, $jadwalId]) {
            $attendanceSessions->forgetLiveMonitoringCache($date, $jadwalId);
        }

        if ($inserted > 0) {
            Log::info("ZKTeco import: {$inserted} absensi baru dari {$this->ip}");
        }

        return [
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'total'    => count($attendances),
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

    private function normalizeCardNo(mixed $cardNo): ?string
    {
        $value = trim($this->clean((string) ($cardNo ?? '')));

        if ($value === '' || (int) $value === 0) {
            return null;
        }

        return $value;
    }

    /**
     * @return array<int|string, string>
     */
    private function decodeFingerprintPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $decoded = [];
        foreach ($payload as $fingerId => $template) {
            if (! is_string($template) || $template === '') {
                continue;
            }

            $binary = base64_decode($template, true);
            $decoded[$fingerId] = $binary !== false ? $binary : $template;
        }

        return $decoded;
    }

    /**
     * @return array<int|string, string>
     */
    private function encodeFingerprintPayload(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $encoded = [];
        foreach ($payload as $fingerId => $template) {
            if (! is_string($template) || $template === '') {
                continue;
            }

            $encoded[$fingerId] = base64_encode($template);
        }

        return $encoded;
    }
}
