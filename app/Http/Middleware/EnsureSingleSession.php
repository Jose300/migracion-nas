<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleSession
{
    /**
     * Handle an incoming request.
     * Enforce single active session per user, auto-close inactive sessions,
     * and purge closed/expired sessions from the database.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('session.driver') !== 'database' || app()->runningUnitTests()) {
            return $next($request);
        }

        $lifetimeMinutes = (int) config('session.lifetime', 120);
        $cutoff = time() - ($lifetimeMinutes * 60);

        // 1. Purgar automáticamente sesiones vencidas/inactivas de la base de datos
        try {
            DB::table('sessions')->where('last_activity', '<', $cutoff)->delete();
        } catch (\Throwable $e) {
            // Silencioso ante concurrencia
        }

        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = $request->session()->getId();

            // 2. Verificar si la sesión actual excedió el tiempo límite de inactividad
            try {
                $currentSessionRecord = DB::table('sessions')
                    ->where('id', $currentSessionId)
                    ->first();

                if ($currentSessionRecord && $currentSessionRecord->last_activity < $cutoff) {
                    try {
                        \App\Models\Conexion::registrarCierreSesion(
                            $currentSessionId,
                            $user->id,
                            'Expirada',
                            "Expiración por inactividad ({$lifetimeMinutes} minutos)"
                        );
                    } catch (\Throwable $e) {}

                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    try {
                        DB::table('sessions')->where('id', $currentSessionId)->delete();
                    } catch (\Throwable $e) {}

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'message' => "Tu sesión ha expirado por inactividad ({$lifetimeMinutes} minutos). Por favor, inicia sesión nuevamente.",
                            'session_expired' => true,
                            'redirect' => route('login')
                        ], 401);
                    }

                    return redirect()->route('login')
                        ->with('warning', "Tu sesión ha sido cerrada automáticamente por inactividad ({$lifetimeMinutes} minutos). Por favor, inicia sesión nuevamente.");
                }

                // 3. Verificar concurrencia de sesiones del usuario
                $userSessionsCount = DB::table('sessions')->where('user_id', $user->id)->count();

                // Solo evaluar si ya existen registros de sesión persistidos en la base de datos
                if ($userSessionsCount > 0) {
                    $sessionExists = DB::table('sessions')
                        ->where('id', $currentSessionId)
                        ->where('user_id', $user->id)
                        ->exists();

                    // Si existen sesiones pero la actual fue eliminada (por un nuevo inicio de sesión en otro dispositivo)
                    if (!$sessionExists) {
                        try {
                            \App\Models\Conexion::registrarCierreSesion(
                                $currentSessionId,
                                $user->id,
                                'Forzada',
                                'Inicio de sesión concurrente en otro dispositivo'
                            );
                        } catch (\Throwable $e) {}

                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'message' => 'Tu sesión ha sido cerrada automáticamente porque se inició sesión desde otro dispositivo o navegador.',
                                'session_expired' => true,
                                'redirect' => route('login')
                            ], 401);
                        }

                        return redirect()->route('login')->with('warning', 'Tu sesión ha sido cerrada automáticamente porque se inició sesión desde otro dispositivo o navegador.');
                    }
                }
            } catch (\Throwable $e) {}

            // 4. Actualizar heartbeat o auto-registrar la sesión si aún no existe en conexiones
            try {
                $conexionExiste = \App\Models\Conexion::where('session_id', $currentSessionId)->exists();
                if (!$conexionExiste) {
                    \App\Models\Conexion::registrarInicioSesion($user, $request, $currentSessionId);
                } else {
                    \App\Models\Conexion::actualizarActividad($currentSessionId);
                }
            } catch (\Throwable $e) {}
        }

        /** @var Response $response */
        $response = $next($request);

        // 5. Prevenir que el navegador almacene en caché páginas autenticadas
        if (Auth::check()) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
        }

        return $response;
    }
}
