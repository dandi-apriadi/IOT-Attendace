<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceEnrollmentJob;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceEnrollmentController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat tidak terautentikasi.',
            ], 401);
        }

        $device->forceFill([
            'last_seen_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function nextCommand(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat tidak terautentikasi.',
            ], 401);
        }

        DeviceEnrollmentJob::query()
            ->where('device_id', $device->id)
            ->whereIn('status', ['pending_device', 'capturing'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => 'expired',
                'error_message' => 'Job kadaluarsa sebelum proses capture selesai.',
                'completed_at' => now(),
            ]);

        $job = DeviceEnrollmentJob::query()
            ->where('device_id', $device->id)
            ->where('status', 'pending_device')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('created_at')
            ->first();

        if (! $job) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Tidak ada perintah sinkronisasi.',
            ]);
        }

        $job->forceFill([
            'status' => 'capturing',
            'started_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'capture',
            'job_id' => $job->id,
            'capture_type' => $job->capture_type,
            'mahasiswa' => [
                'id' => $job->mahasiswa_id,
                'nim' => $job->mahasiswa->nim,
                'nama' => $job->mahasiswa->nama,
            ],
            'expires_at' => optional($job->expires_at)?->toIso8601String(),
        ]);
    }

    public function submitResult(Request $request, DeviceEnrollmentJob $job): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device || (int) $job->device_id !== (int) $device->id) {
            return response()->json([
                'message' => 'Perangkat tidak berhak mengubah job ini.',
            ], 403);
        }

        if (! in_array($job->status, ['capturing', 'pending_device'], true)) {
            return response()->json([
                'message' => 'Job sudah selesai atau tidak valid untuk diupdate.',
            ], 422);
        }

        $data = $request->validate([
            'success' => ['required', 'boolean'],
            'captured_value' => ['nullable', 'string'],
            'error_message' => ['nullable', 'string', 'max:500'],
            'payload' => ['nullable', 'array'],
        ]);

        if (! empty($job->expires_at) && now()->greaterThan($job->expires_at)) {
            $job->forceFill([
                'status' => 'expired',
                'error_message' => 'Hasil scan datang setelah batas waktu sinkronisasi.',
                'completed_at' => now(),
            ])->save();

            return response()->json([
                'message' => 'Job sudah kadaluarsa.',
            ], 422);
        }

        if (! $data['success']) {
            $job->forceFill([
                'status' => 'failed',
                'error_message' => $data['error_message'] ?? 'Capture gagal pada perangkat IoT.',
                'result_payload' => $data['payload'] ?? null,
                'completed_at' => now(),
            ])->save();

            return response()->json([
                'status' => 'failed',
                'message' => 'Hasil gagal tersimpan.',
            ]);
        }

        $capturedValue = trim((string) ($data['captured_value'] ?? ''));

        if ($capturedValue === '') {
            return response()->json([
                'message' => 'captured_value wajib diisi saat success=true.',
            ], 422);
        }

        $targetColumn = match ($job->capture_type) {
            'rfid' => 'rfid_uid',
            'fingerprint' => 'fingerprint_data',
            'face' => 'face_model_data',
            'barcode' => 'barcode_id',
            default => null,
        };

        if (! $targetColumn) {
            return response()->json([
                'message' => 'capture_type tidak didukung.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($job, $targetColumn, $capturedValue, $data): void {
                $mahasiswa = Mahasiswa::query()->lockForUpdate()->findOrFail($job->mahasiswa_id);

                if (in_array($targetColumn, ['rfid_uid', 'barcode_id'], true)) {
                    $alreadyUsed = Mahasiswa::query()
                        ->where($targetColumn, $capturedValue)
                        ->where('id', '!=', $mahasiswa->id)
                        ->exists();

                    if ($alreadyUsed) {
                        throw new \RuntimeException('Identifier sudah dipakai mahasiswa lain.');
                    }
                }

                $mahasiswa->forceFill([
                    $targetColumn => $capturedValue,
                ])->save();

                $job->forceFill([
                    'status' => 'completed',
                    'captured_value' => $capturedValue,
                    'error_message' => null,
                    'result_payload' => $data['payload'] ?? null,
                    'completed_at' => now(),
                ])->save();
            });
        } catch (\RuntimeException $exception) {
            $job->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ])->save();

            return response()->json([
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'completed',
            'message' => 'Data berhasil disinkronkan.',
        ]);
    }
}
