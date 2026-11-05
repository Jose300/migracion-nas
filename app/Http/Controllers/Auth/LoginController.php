<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Conexion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form with anti-cache headers and expired session handling.
     */
    public function showLoginForm(Request $request)
    {
        // Si el usuario llega redirigido por inactividad o expiración de sesión
        if ($request->has('expired')) {
            $userId = Auth::id();
            $sessionId = $request->session()->getId();

            if (Auth::check()) {
                Auth::logout();
            }

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            try {
                if ($sessionId) {
                    DB::table('sessions')->where('id', $sessionId)->delete();
                }
                if ($userId) {
                    DB::table('sessions')->where('user_id', $userId)->delete();
                }
                Conexion::registrarCierreSesion($sessionId, $userId, 'Expirada', 'Expiración por inactividad');
            } catch (\Throwable $e) {}

            session()->flash('warning', 'Tu sesión se cerró automáticamente por inactividad. Por favor, inicia sesión nuevamente.');
        } elseif (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }

    /**
     * Handle an authentication attempt with status verification and session tracking.
     */
    public function login(Request $request)
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->email)),
            ]);
        }

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $email = $request->email;
        $password = $request->password;
        $remember = $request->boolean('remember');

        // 1. Verificar si el usuario existe en el sistema
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Estas credenciales no coinciden con nuestros registros.',
            ]);
        }

        // 2. Verificar si la cuenta está suspendida o inactiva
        if ($user->status === 'Inactivo') {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta de usuario se encuentra inactiva o suspendida. Comuníquese con el administrador.',
            ]);
        }

        // 3. Intentar autenticar credenciales
        if (!Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'El correo electrónico o la contraseña ingresada son incorrectos.',
            ]);
        }

        // 4. Regenerar sesión y gestionar cookie de recordatorio
        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $currentSessionId = $request->session()->getId();

        if ($remember) {
            Cookie::queue('remember_email', $request->email, 60 * 24 * 365);
        } else {
            Cookie::queue(Cookie::forget('remember_email'));
        }

        // 5. Cerrar otras sesiones activas del mismo usuario y registrar conexión
        try {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();

            $cutoff = time() - (config('session.lifetime', 120) * 60);
            DB::table('sessions')->where('last_activity', '<', $cutoff)->delete();

            // Registrar cierre forzado de sesiones anteriores y registrar la nueva conexión activa
            Conexion::registrarCierreSesion(null, $user->id, 'Forzada', 'Inicio de sesión concurrente en otro navegador');
            Conexion::registrarInicioSesion($user, $request, $currentSessionId);
        } catch (\Throwable $e) {}

        return redirect()->intended('/dashboard');
    }

    /**
     * Log the user out of the application and purge closed session.
     */
    public function logout(Request $request)
    {
        $isExpired = $request->boolean('expired');
        $userId = Auth::id();
        $sessionId = $request->session()->getId();

        // Registrar cierre de la conexión
        try {
            Conexion::registrarCierreSesion(
                $sessionId,
                $userId,
                $isExpired ? 'Expirada' : 'Cerrada',
                $isExpired ? 'Expiración por inactividad' : 'Cierre de sesión manual'
            );
        } catch (\Throwable $e) {}

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        try {
            if ($sessionId) {
                DB::table('sessions')->where('id', $sessionId)->delete();
            }
        } catch (\Throwable $e) {}

        return redirect('/');
    }
}
