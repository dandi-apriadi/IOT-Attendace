<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidDeviceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $incomingToken = (string) $request->header('X-Device-Token', '');
        $incomingDeviceId = (string) $request->header('X-Device-Id', '');

        if ($incomingToken === '') {
            return new JsonResponse([
                'message' => 'Unauthorized device token.',
            ], 401);
        }

        // Preferred: per-device token validation from database.
        if ($incomingDeviceId !== '') {
            $device = Device::where('device_id', $incomingDeviceId)
                ->where('is_active', true)
                ->first();

            if (! $device) {
                Log::warning('API akses ditolak: device_id tidak valid/nonaktif', [
                    'device_id' => $incomingDeviceId,
                    'ip' => $request->ip(),
                ]);

                return new JsonResponse([
                    'message' => 'Unauthorized device token.',
                ], 401);
            }

            $incomingHash = hash('sha256', $incomingToken);

            if (! hash_equals((string) $device->token_hash, $incomingHash)) {
                Log::warning('API akses ditolak: token device tidak cocok', [
                    'device_id' => $incomingDeviceId,
                    'ip' => $request->ip(),
                ]);

                return new JsonResponse([
                    'message' => 'Unauthorized device token.',
                ], 401);
            }

            $device->forceFill(['last_seen_at' => now()])->save();
            $request->attributes->set('device', $device);

            return $next($request);
        }

        // Backward-compatible fallback: global token from env.
        $expectedToken = (string) env('DEVICE_API_TOKEN', '');

        if ($expectedToken === '' || $incomingToken === '' || ! hash_equals($expectedToken, $incomingToken)) {
            Log::warning('API akses ditolak: token global env tidak valid', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return new JsonResponse([
                'message' => 'Unauthorized device token.',
            ], 401);
        }

        return $next($request);
    }
}
