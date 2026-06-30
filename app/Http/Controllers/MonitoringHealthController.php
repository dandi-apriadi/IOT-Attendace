<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\PerformanceMetric;
use App\Services\DeviceCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Jmrashed\Zkteco\Lib\Helper\Util as ZkUtil;

class MonitoringHealthController extends Controller
{
    public function index(): View
    {
        $now = now();
        $today = $now->toDateString();
        $onlineThreshold = $now->copy()->subMinutes(5);

        $allDevices = Device::query()
            ->orderByDesc('is_active')
            ->orderByDesc('last_seen_at')
            ->get();

        $usesAgentRelay = $this->usesAgentRelay();
        $zkReachable = $usesAgentRelay ? [] : $this->probeZktecoDevices($allDevices);

        $devices = $allDevices->map(function (Device $device) use ($onlineThreshold, $zkReachable, $usesAgentRelay): Device {
            $status = 'unknown';
            $statusLabel = 'Unknown';

            if (! $device->is_active) {
                $status = 'disabled';
                $statusLabel = 'Disabled';
            } elseif ($device->type === 'zkteco' && ! $usesAgentRelay) {
                $key = $device->ip_address . ':' . ($device->port ?: 4370);
                if (isset($zkReachable[$key])) {
                    $status = 'online';
                    $statusLabel = 'Online';
                } else {
                    $status = 'offline';
                    $statusLabel = 'Offline';
                }
            } elseif ($device->last_seen_at && $device->last_seen_at->gte($onlineThreshold)) {
                $status = 'online';
                $statusLabel = 'Online';
            } elseif ($device->last_seen_at) {
                $status = 'stale';
                $statusLabel = 'Stale';
            }

            $device->computed_status = $status;
            $device->computed_status_label = $statusLabel;

            return $device;
        });

        $onlineDevices = $devices->where('computed_status', 'online')->count();
        $activeDevices = $devices->where('is_active', true)->count();
        $staleDevices = $devices->where('computed_status', 'stale')->count();
        $totalDevices = $devices->count();

        $avgLatency = (float) (PerformanceMetric::query()
            ->where('endpoint', 'api.absensi')
            ->whereDate('created_at', $today)
            ->avg('total_duration_ms') ?? 0);

        if ($avgLatency <= 0) {
            $avgLatency = (float) (PerformanceMetric::query()
                ->where('endpoint', 'api.absensi')
                ->whereDate('created_at', $today)
                ->avg('query_duration_ms') ?? 0);
        }

        $todaysPayloads = Absensi::query()
            ->whereDate('tanggal', $today)
            ->count();

        $latencyRows = PerformanceMetric::query()
            ->where('endpoint', 'api.absensi')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('created_at')
            ->limit(300)
            ->get(['total_duration_ms', 'query_duration_ms', 'created_at']);

        $latencies = $latencyRows
            ->map(function ($row): float {
                $total = (float) ($row->total_duration_ms ?? 0);
                if ($total > 0) {
                    return $total;
                }

                return (float) ($row->query_duration_ms ?? 0);
            })
            ->filter(fn (float $value): bool => $value > 0)
            ->sort()
            ->values();

        $latencyCount = $latencies->count();
        $avgLatency24h = $latencyCount > 0 ? round($latencies->sum() / $latencyCount, 2) : 0.0;
        $p95Latency24h = $latencyCount > 0 ? round((float) $latencies[(int) floor(($latencyCount - 1) * 0.95)], 2) : 0.0;
        $latestLatencyMs = $latencyRows->isNotEmpty()
            ? round((float) (($latencyRows->first()->total_duration_ms ?? 0) ?: ($latencyRows->first()->query_duration_ms ?? 0)), 2)
            : 0.0;

        $audit24h = AuditLog::query()
            ->where('created_at', '>=', $now->copy()->subDay());

        $errorEvents24h = (clone $audit24h)
            ->where(function ($query) {
                $query->where('action', 'like', '%failed%')
                    ->orWhere('description', 'like', '%failed%')
                    ->orWhere('description', 'like', '%error%');
            })
            ->count();

        $successEvents24h = (clone $audit24h)
            ->where(function ($query) {
                $query->where('action', 'like', '%success%')
                    ->orWhere('action', 'login');
            })
            ->count();

        $recentEvents = AuditLog::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('monitoring.health', [
            'onlineDevices' => $onlineDevices,
            'activeDevices' => $activeDevices,
            'staleDevices' => $staleDevices,
            'totalDevices' => $totalDevices,
            'avgLatency' => round($avgLatency, 2),
            'todaysPayloads' => $todaysPayloads,
            'devices' => $devices,
            'recentEvents' => $recentEvents,
            'eventsSummary24h' => [
                'errors' => $errorEvents24h,
                'success' => $successEvents24h,
                'total' => (clone $audit24h)->count(),
            ],
            'latencyStats24h' => [
                'sample_count' => $latencyCount,
                'avg_ms' => $avgLatency24h,
                'p95_ms' => $p95Latency24h,
                'latest_ms' => $latestLatencyMs,
            ],
        ]);
    }

    /**
     * Endpoint JSON: cek ulang koneksi satu perangkat ZKTeco dari UI.
     */
    public function pingDevice(Request $request, Device $device): JsonResponse
    {
        if ($device->type !== 'zkteco' || empty($device->ip_address)) {
            return response()->json(['ok' => false, 'message' => 'Bukan perangkat ZKTeco atau IP tidak ada.'], 422);
        }

        if ($this->usesAgentRelay()) {
            $command = app(DeviceCommandService::class)->enqueue(
                $device,
                'get_info',
                [],
                $request->user()?->id
            );

            return response()->json([
                'ok' => true,
                'queued' => true,
                'command_id' => $command->id,
                'status_url' => route('devices.commands.status', [$device, $command]),
                'status' => 'pending',
                'label' => 'Menunggu',
                'message' => 'Perintah cek koneksi dikirim ke agent lokal.',
            ]);
        }

        $port      = (int) ($device->port ?: 4370);
        $reachable = $this->udpProbe($device->ip_address, $port, 2000);

        if ($reachable) {
            $device->forceFill(['last_seen_at' => now()])->save();
        }

        return response()->json([
            'ok'     => $reachable,
            'status' => $reachable ? 'online' : 'offline',
            'label'  => $reachable ? 'Online' : 'Offline',
            'message' => $reachable
                ? 'Perangkat merespons di ' . $device->ip_address . ':' . $port . '.'
                : 'Perangkat tidak merespons di ' . $device->ip_address . ':' . $port . '.',
        ]);
    }

    /**
     * Probe semua perangkat ZKTeco aktif secara paralel menggunakan paket
     * UDP CMD_CONNECT — protokol asli ZKTeco, bukan TCP.
     *
     * @param  \Illuminate\Support\Collection<int, Device>  $devices
     * @return array<string, true>  key = "ip:port"
     */
    private function probeZktecoDevices(\Illuminate\Support\Collection $devices): array
    {
        $targets = $devices->filter(
            fn (Device $d) => $d->type === 'zkteco' && $d->is_active && ! empty($d->ip_address)
        );

        if ($targets->isEmpty()) {
            return [];
        }

        $connectPacket = $this->buildZkConnectPacket();

        // Simpan pasangan [sock, key] — hindari (int)$sock karena PHP 8+
        // mengembalikan objek Socket bukan resource, sehingga cast ke int gagal.
        /** @var array<int, array{sock: \Socket, key: string}> $entries */
        $entries = [];

        foreach ($targets as $device) {
            $port = (int) ($device->port ?: 4370);
            $key  = $device->ip_address . ':' . $port;

            $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($sock === false) {
                continue;
            }

            socket_set_nonblock($sock);
            @socket_sendto($sock, $connectPacket, strlen($connectPacket), 0, $device->ip_address, $port);

            $entries[] = ['sock' => $sock, 'key' => $key];
        }

        if (empty($entries)) {
            return [];
        }

        // Tunggu respons masuk — maks 1.5 detik.
        $read   = array_column($entries, 'sock');
        $write  = null;
        $except = null;
        @socket_select($read, $write, $except, 1, 500_000);

        // $read sekarang hanya berisi socket yang punya data masuk.
        $reachable = [];
        foreach ($read as $readySock) {
            foreach ($entries as $entry) {
                if ($entry['sock'] === $readySock) {
                    $reachable[$entry['key']] = true;
                    break;
                }
            }
        }

        foreach ($entries as $entry) {
            @socket_close($entry['sock']);
        }

        return $reachable;
    }

    /**
     * Probe satu perangkat ZKTeco via UDP dengan batas waktu $timeoutMs.
     */
    private function udpProbe(string $ip, int $port, int $timeoutMs = 1500): bool
    {
        $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($sock === false) {
            return false;
        }

        socket_set_nonblock($sock);

        $packet = $this->buildZkConnectPacket();
        @socket_sendto($sock, $packet, strlen($packet), 0, $ip, $port);

        $read   = [$sock];
        $write  = [];
        $except = [];
        $sec    = intdiv($timeoutMs, 1000);
        $usec   = ($timeoutMs % 1000) * 1_000;
        $result = @socket_select($read, $write, $except, $sec, $usec);

        @socket_close($sock);

        return $result > 0 && ! empty($read);
    }

    /**
     * Membangun paket UDP CMD_CONNECT menggunakan helper library ZKTeco
     * agar format dan checksum dijamin identik dengan protokol aslinya.
     */
    private function buildZkConnectPacket(): string
    {
        return ZkUtil::createHeader(
            ZkUtil::CMD_CONNECT,  // 1000
            0,                    // chksum awal (akan dihitung ulang di dalam)
            0,                    // session_id
            ZkUtil::USHRT_MAX - 1, // reply_id awal
            ''
        );
    }
}
