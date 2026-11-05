<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $ip = $request->ip();
        $email = mb_strtolower($data['email']);
        $key = 'security:block:'.hash('sha256', $ip);
        $accountKey = 'security:block:account:'.hash('sha256', $email);

        if (Cache::has($key) || Cache::has($accountKey)) {
            $this->record($request, 'blocked');
            Log::channel('security')->warning(
                'Intento desde IP bloqueada', ['ip' => $ip]
            );

            return response()->json([
                'message' => 'Acceso temporalmente restringido.',
            ], 429);
        }

        if (Auth::attempt($data)) {
            $request->session()->regenerate();
            $this->record($request, 'success');
            Log::channel('security')->info(
                'Inicio de sesión exitoso',
                ['user_id' => Auth::id(), 'ip' => $ip]
            );

            return response()->json([
                'message' => 'Autenticación correcta',
            ]);
        }

        $this->record($request, 'failed');
        $count = LoginAttempt::where('ip_address', $ip)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        Log::channel('security')->warning(
            'Autenticación fallida',
            ['ip' => $ip, 'failed_attempts' => $count]
        );

        if ($count >= 5) {
            Cache::put($key, true, now()->addMinutes(10));
            Log::channel('security')->critical(
                'Bloqueo temporal por intentos fallidos',
                [
                    'ip' => $ip,
                    'failed_attempts' => $count,
                    'duration_minutes' => 10,
                ]
            );
        } elseif ($count >= 3) {
            Log::channel('security')->alert(
                'Posible ataque de fuerza bruta',
                ['ip' => $ip, 'failed_attempts' => $count]
            );
        }

        $accountCount = LoginAttempt::where('identifier', $email)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($accountCount >= 5 && $count < 5) {
            Cache::put($accountKey, true, now()->addMinutes(10));
            Log::channel('security')->critical(
                'Bloqueo temporal de cuenta por intentos fallidos',
                [
                    'email' => $email,
                    'ip' => $ip,
                    'failed_attempts' => $accountCount,
                    'duration_minutes' => 10,
                ]
            );
        }

        return response()->json([
            'message' => 'Credenciales inválidas',
        ], 401);
    }

    private function record(Request $request, string $status): void
    {
        LoginAttempt::create([
            'identifier' => mb_strtolower(
                $request->input('email', '')
            ),
            'ip_address' => $request->ip(),
            'status' => $status,
            'user_agent' => substr(
                $request->userAgent() ?? '', 0, 255
            ),
            'created_at' => now(),
        ]);
    }
}
