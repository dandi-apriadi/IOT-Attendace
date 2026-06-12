<?php

/**
 * Agent Relay ZKTeco — jembatan antara alat di LAN dan server elektropolimdo.com.
 *
 * Alur sekali jalan (idempoten, aman dijadwalkan tiap ~1 menit):
 *   1. Connect UDP ke alat (retry 3x).
 *   2. Push absensi: getAttendance() -> POST /api/agent/attendance.
 *   3. Poll perintah: GET /api/agent/commands/next -> eksekusi -> POST result.
 *      Diulang hingga POLL_LIMIT atau antrean kosong.
 *
 * Penggunaan:
 *   php agent.php            # satu siklus lalu keluar (untuk cron / Task Scheduler)
 *   php agent.php --loop     # berjalan terus dengan jeda antar siklus
 *   php agent.php --loop=30  # loop dengan jeda 30 detik
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use Jmrashed\Zkteco\Lib\Helper\Util;
use Jmrashed\Zkteco\Lib\ZKTeco;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
@set_time_limit(0);

Dotenv::createImmutable(__DIR__)->safeLoad();

function env_val(string $key, $default = null)
{
    $v = $_ENV[$key] ?? getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : $v;
}

$config = [
    'server_url' => rtrim((string) env_val('SERVER_URL', 'http://localhost:8000'), '/'),
    'device_id' => (string) env_val('DEVICE_ID', ''),
    'device_token' => (string) env_val('DEVICE_TOKEN', ''),
    'device_ip' => (string) env_val('DEVICE_IP', '192.168.0.10'),
    'device_port' => (int) env_val('DEVICE_PORT', 4370),
    'scan_subnet' => (string) env_val('SCAN_SUBNET', ''),
    'scan_ips' => (string) env_val('SCAN_IPS', ''),
    'scan_port' => (int) env_val('SCAN_PORT', 4370),
    'poll_limit' => (int) env_val('POLL_LIMIT', 5),
    'http_timeout' => (int) env_val('HTTP_TIMEOUT', 30),
];

if ($config['device_id'] === '' || $config['device_token'] === '') {
    fwrite(STDERR, "[FATAL] DEVICE_ID dan DEVICE_TOKEN wajib diisi di .env\n");
    exit(1);
}

// ---- Parse argumen loop ----
$loop = false;
$loopDelay = 60;
foreach ($argv as $arg) {
    if ($arg === '--loop') {
        $loop = true;
    } elseif (str_starts_with($arg, '--loop=')) {
        $loop = true;
        $loopDelay = max(5, (int) substr($arg, 7));
    }
}

$http = new Client([
    'base_uri' => $config['server_url'] . '/',
    'timeout' => $config['http_timeout'],
    'headers' => [
        'Accept' => 'application/json',
        'X-Device-Id' => $config['device_id'],
        'X-Device-Token' => $config['device_token'],
    ],
    'http_errors' => false,
]);

function logline(string $msg): void
{
    echo '[' . date('Y-m-d H:i:s') . "] {$msg}\n";
}

/**
 * Membersihkan respons firmware dari prefix "~Key=" dan null byte.
 */
function clean_value(string $value): string
{
    $value = trim(str_replace("\0", '', $value));
    if (str_starts_with($value, '~') && str_contains($value, '=')) {
        $value = substr($value, strpos($value, '=') + 1);
    }
    return trim($value);
}

/**
 * Membuka koneksi ke alat dengan retry hingga 3x.
 */
function connect_device(string $ip, int $port): ?ZKTeco
{
    for ($i = 0; $i < 3; $i++) {
        $zk = new ZKTeco($ip, $port);
        if (@$zk->connect()) {
            return $zk;
        }
        usleep(500000);
    }
    return null;
}

function scan_candidates(array $config): array
{
    if (trim($config['scan_ips']) !== '') {
        return array_values(array_filter(array_map('trim', explode(',', $config['scan_ips']))));
    }

    $subnet = trim($config['scan_subnet']);
    if ($subnet === '') {
        $parts = explode('.', $config['device_ip']);
        if (count($parts) === 4) {
            $subnet = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
        }
    }

    if (! preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.0\/24$/', $subnet, $m)) {
        return [$config['device_ip']];
    }

    $prefix = $m[1] . '.' . $m[2] . '.' . $m[3] . '.';
    $ips = [];
    for ($i = 1; $i <= 254; $i++) {
        $ips[] = $prefix . $i;
    }

    return $ips;
}

function probe_zkteco(string $ip, int $port): ?array
{
    $zk = new ZKTeco($ip, $port);
    if (! @$zk->connect()) {
        return null;
    }

    try {
        return [
            'ip_address' => $ip,
            'port' => $port,
            'version' => clean_value((string) $zk->version()),
            'serial_number' => clean_value((string) $zk->serialNumber()),
            'device_name' => clean_value((string) $zk->deviceName()),
            'platform' => clean_value((string) $zk->platform()),
            'device_time' => clean_value((string) $zk->getTime()),
        ];
    } catch (\Throwable $e) {
        return [
            'ip_address' => $ip,
            'port' => $port,
            'error' => $e->getMessage(),
        ];
    } finally {
        @$zk->disconnect();
    }
}

function scan_zkteco_devices(array $config): array
{
    $found = [];
    foreach (scan_candidates($config) as $ip) {
        $info = probe_zkteco($ip, $config['scan_port']);
        if ($info !== null) {
            $found[] = $info;
        }
    }

    return $found;
}

/**
 * Mengecek apakah uid punya template sidik jari.
 */
function has_fingerprint(ZKTeco $zk, int $uid): bool
{
    try {
        $fp = $zk->getFingerprint($uid);
        return is_array($fp) ? count(array_filter($fp)) > 0 : ! empty($fp);
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Push absensi mentah ke server.
 */
function push_attendance(Client $http, ZKTeco $zk): void
{
    try {
        $records = $zk->getAttendance();
        $records = is_array($records) ? array_values($records) : [];
    } catch (\Throwable $e) {
        logline('[WARN] Gagal membaca absensi dari alat: ' . $e->getMessage());
        return;
    }

    $res = $http->post('api/agent/attendance', ['json' => ['records' => $records]]);
    $code = $res->getStatusCode();
    $body = json_decode((string) $res->getBody(), true) ?: [];

    if ($code === 200) {
        $ins = $body['inserted'] ?? 0;
        $skip = $body['skipped'] ?? 0;
        $total = $body['total'] ?? count($records);
        logline("[PUSH] Absensi: {$ins} baru, {$skip} dilewati dari {$total} record.");
    } else {
        logline("[WARN] Push absensi gagal (HTTP {$code}): " . ($body['message'] ?? ''));
    }
}

/**
 * Mengeksekusi satu perintah di alat. Mengembalikan [success, result, error].
 *
 * @return array{0: bool, 1: array<string,mixed>|null, 2: ?string}
 */
function execute_command(ZKTeco $zk, string $type, array $payload): array
{
    try {
        switch ($type) {
            case 'push_all_users':
            case 'push_user':
                $users = $payload['users'] ?? [];
                $count = 0;
                foreach ($users as $u) {
                    $zk->setUser(
                        (int) ($u['uid'] ?? 0),
                        (string) ($u['userid'] ?? ''),
                        mb_substr((string) ($u['name'] ?? ''), 0, 24),
                        '',
                        Util::LEVEL_USER,
                        0
                    );
                    $count++;
                }
                return [true, ['pushed' => $count], null];

            case 'remove_user':
                $zk->removeUser((int) ($payload['uid'] ?? 0));
                return [true, ['removed' => (int) ($payload['uid'] ?? 0)], null];

            case 'sync_time':
                $zk->setTime(date('Y-m-d H:i:s'));
                return [true, ['device_time' => date('Y-m-d H:i:s')], null];

            case 'clear_attendance':
                $zk->clearAttendance();
                return [true, ['cleared' => true], null];

            case 'get_info':
                return [true, [
                    'version' => clean_value((string) $zk->version()),
                    'serial_number' => clean_value((string) $zk->serialNumber()),
                    'device_name' => clean_value((string) $zk->deviceName()),
                    'platform' => clean_value((string) $zk->platform()),
                    'device_time' => clean_value((string) $zk->getTime()),
                ], null];

            case 'scan_devices':
                return [true, [
                    'devices' => scan_zkteco_devices($GLOBALS['config']),
                ], null];

            case 'pull_users':
                $rows = [];
                foreach ((array) $zk->getUser() as $u) {
                    $rows[] = [
                        'uid' => (int) ($u['uid'] ?? 0),
                        'userid' => clean_value((string) ($u['userid'] ?? '')),
                        'name' => clean_value((string) ($u['name'] ?? '')),
                        'role' => (int) ($u['role'] ?? 0),
                        'cardno' => clean_value((string) ($u['cardno'] ?? '')),
                    ];
                }
                return [true, ['users' => $rows], null];

            case 'read_user':
                $target = (int) ($payload['uid'] ?? 0);
                $match = null;
                foreach ((array) $zk->getUser() as $u) {
                    if ((int) ($u['uid'] ?? 0) === $target) {
                        $match = $u;
                        break;
                    }
                }
                if (! $match) {
                    return [true, ['found' => false, 'uid' => $target], null];
                }
                $cardno = clean_value((string) ($match['cardno'] ?? ''));
                if ($cardno === '' || (int) $cardno === 0) {
                    $cardno = null;
                }
                return [true, [
                    'found' => true,
                    'uid' => $target,
                    'userid' => clean_value((string) ($match['userid'] ?? '')),
                    'name' => clean_value((string) ($match['name'] ?? '')),
                    'cardno' => $cardno,
                    'has_fingerprint' => has_fingerprint($zk, $target),
                ], null];

            case 'pull_biometrics':
                $rows = [];
                foreach ((array) $zk->getUser() as $u) {
                    $uid = (int) ($u['uid'] ?? 0);
                    $rows[] = [
                        'uid' => $uid,
                        'userid' => clean_value((string) ($u['userid'] ?? '')),
                        'cardno' => clean_value((string) ($u['cardno'] ?? '')),
                        'has_fingerprint' => has_fingerprint($zk, $uid),
                    ];
                }
                return [true, ['users' => $rows], null];

            case 'pull_attendance':
                // Tidak ada aksi khusus; absensi sudah di-push di awal siklus.
                return [true, ['note' => 'attendance pushed at cycle start'], null];

            default:
                return [false, null, "Tipe perintah tidak dikenal: {$type}"];
        }
    } catch (\Throwable $e) {
        return [false, null, $e->getMessage()];
    }
}

/**
 * Memproses antrean perintah hingga kosong atau mencapai batas.
 */
function process_commands(Client $http, ZKTeco $zk, int $limit): void
{
    for ($i = 0; $i < $limit; $i++) {
        $res = $http->get('api/agent/commands/next');
        if ($res->getStatusCode() !== 200) {
            logline('[WARN] Gagal mengambil perintah (HTTP ' . $res->getStatusCode() . ').');
            return;
        }

        $body = json_decode((string) $res->getBody(), true) ?: [];
        if (($body['status'] ?? '') !== 'command') {
            return; // idle
        }

        $cmd = $body['command'] ?? [];
        $id = (int) ($cmd['id'] ?? 0);
        $type = (string) ($cmd['type'] ?? '');
        $payload = is_array($cmd['payload'] ?? null) ? $cmd['payload'] : [];

        logline("[CMD] #{$id} {$type} — menjalankan...");
        [$success, $result, $error] = execute_command($zk, $type, $payload);

        $http->post("api/agent/commands/{$id}/result", [
            'json' => array_filter([
                'success' => $success,
                'result' => $result,
                'error' => $error,
            ], fn ($v) => $v !== null),
        ]);

        logline($success
            ? "[CMD] #{$id} {$type} — selesai."
            : "[CMD] #{$id} {$type} — GAGAL: {$error}");
    }
}

/**
 * Satu siklus penuh: connect, push absensi, proses perintah, disconnect.
 */
function run_cycle(Client $http, array $config): void
{
    $zk = connect_device($config['device_ip'], $config['device_port']);
    if (! $zk) {
        logline("[ERROR] Tidak bisa terhubung ke alat {$config['device_ip']}:{$config['device_port']}.");
        return;
    }

    try {
        push_attendance($http, $zk);
        process_commands($http, $zk, $config['poll_limit']);
    } finally {
        @$zk->disconnect();
    }
}

// ---- Entrypoint ----
logline("Agent mulai. Server={$config['server_url']} Device={$config['device_id']} Alat={$config['device_ip']}:{$config['device_port']}");

if ($loop) {
    logline("Mode loop aktif (jeda {$loopDelay}s). Tekan Ctrl+C untuk berhenti.");
    while (true) {
        run_cycle($http, $config);
        sleep($loopDelay);
    }
} else {
    run_cycle($http, $config);
    logline('Siklus selesai.');
}
