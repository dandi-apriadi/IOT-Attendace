<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\DeviceCommandService;
use App\Services\ZktecoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jembatan antara SERVER (VPS) dan AGENT lokal.
 *
 * Agent (script standalone di sisi alat) memakai endpoint ini untuk:
 *   - mengirim (push) absensi mentah yang ditarik dari alat,
 *   - menarik perintah berikutnya dari antrean,
 *   - melaporkan hasil eksekusi perintah.
 *
 * Semua endpoint diproteksi middleware `device.token` (X-Device-Id +
 * X-Device-Token); device hasil autentikasi tersedia di request attributes.
 */
class AgentBridgeController extends Controller
{
    /**
     * Menerima batch absensi mentah dari agent dan menyimpannya.
     * Memakai ulang importAttendanceFromRecords yang sudah batch-optimized.
     */
    public function ingestAttendance(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device instanceof Device) {
            return response()->json(['message' => 'Perangkat tidak terautentikasi.'], 401);
        }

        $validated = $request->validate([
            'records' => ['present', 'array'],
            'records.*.id' => ['nullable'],
            'records.*.timestamp' => ['nullable', 'string'],
        ]);

        $records = $validated['records'] ?? [];

        $result = (new ZktecoService($device))->importAttendanceFromRecords($records);

        return response()->json([
            'status' => 'ok',
            'inserted' => $result['inserted'],
            'skipped' => $result['skipped'],
            'total' => $result['total'],
        ]);
    }

    /**
     * Mengembalikan satu perintah berikutnya untuk dieksekusi agent.
     */
    public function nextCommand(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device instanceof Device) {
            return response()->json(['message' => 'Perangkat tidak terautentikasi.'], 401);
        }

        // Command yang diambil agent tetapi tidak dilaporkan kembali tidak boleh
        // memblokir antrean selamanya.
        $ttl = (int) config('agent.command_dispatch_ttl', 300);
        $maxAttempts = max(1, (int) config('agent.command_max_dispatch_attempts', 3));

        $expiredDispatched = DeviceCommand::query()
            ->where('device_id', $device->id)
            ->where('status', 'dispatched')
            ->where('dispatched_at', '<', now()->subSeconds($ttl))
            ->get();

        foreach ($expiredDispatched as $expired) {
            if ((int) $expired->dispatch_attempts >= $maxAttempts) {
                $expired->forceFill([
                    'status' => 'failed',
                    'error' => 'Agent mengambil perintah tetapi tidak mengirim hasil sampai batas retry tercapai.',
                    'completed_at' => now(),
                ])->save();

                continue;
            }

            $expired->forceFill([
                'status' => 'queued',
                'dispatched_at' => null,
            ])->save();
        }

        $command = DeviceCommand::query()
            ->where('device_id', $device->id)
            ->where('status', 'queued')
            ->orderBy('id')
            ->first();

        if (! $command) {
            return response()->json(['status' => 'idle']);
        }

        $command->forceFill([
            'status' => 'dispatched',
            'dispatch_attempts' => ((int) $command->dispatch_attempts) + 1,
            'dispatched_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'command',
            'command' => [
                'id' => $command->id,
                'type' => $command->type,
                'payload' => $command->payload ?? (object) [],
            ],
        ]);
    }

    /**
     * Menerima hasil eksekusi sebuah perintah dari agent.
     */
    public function submitResult(Request $request, DeviceCommand $command, DeviceCommandService $commands): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device instanceof Device || (int) $command->device_id !== (int) $device->id) {
            return response()->json(['message' => 'Perangkat tidak berhak mengubah perintah ini.'], 403);
        }

        if (in_array($command->status, ['done', 'failed'], true)) {
            return response()->json(['message' => 'Perintah sudah selesai.'], 422);
        }

        $validated = $request->validate([
            'success' => ['required', 'boolean'],
            'result' => ['nullable', 'array'],
            'error' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $validated['success']) {
            $command->forceFill([
                'status' => 'failed',
                'result' => $validated['result'] ?? null,
                'error' => $validated['error'] ?? 'Eksekusi gagal di agent.',
                'completed_at' => now(),
            ])->save();

            return response()->json(['status' => 'failed']);
        }

        $resultData = $validated['result'] ?? [];

        $command->forceFill([
            'status' => 'done',
            'result' => $resultData ?: null,
            'error' => null,
            'completed_at' => now(),
        ])->save();

        // Terapkan hasil ke sistem untuk perintah yang mengembalikan data.
        $commands->applyResult($command, $resultData);

        return response()->json(['status' => 'done']);
    }
}
