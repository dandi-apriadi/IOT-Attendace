<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::once($credentials)) {
            AuditLogger::log(
                $request,
                'login_mobile_failed',
                'Login mobile gagal untuk email: ' . $request->input('email')
            );

            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $user = Auth::user();

        if (! in_array($user->role, ['admin', 'dosen'], true)) {
            AuditLogger::log(
                $request,
                'login_mobile_failed',
                'Login mobile ditolak (role tidak diizinkan): ' . $user->email,
                $user->id
            );

            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak memiliki akses ke aplikasi monitoring.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        AuditLogger::log(
            $request,
            'login_mobile',
            'Login mobile berhasil untuk email: ' . $user->email,
            $user->id
        );

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
