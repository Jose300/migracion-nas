<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Conexion extends Model
{
    use HasFactory;

    protected $table = 'conexiones';

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'dispositivo',
        'pais',
        'ciudad',
        'codigo_pais',
        'login_at',
        'last_activity_at',
        'logout_at',
        'duracion_segundos',
        'status',
        'origen_cierre',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'logout_at' => 'datetime',
        'duracion_segundos' => 'integer',
    ];

    /**
     * Relación con el Usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Registra un nuevo inicio de sesión.
     */
    public static function registrarInicioSesion($user, Request $request, string $sessionId): self
    {
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();

        $ubicacion = self::detectarUbicacion($ip);
        $dispositivo = self::detectarDispositivo($userAgent);

        $now = Carbon::now();

        return self::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $ip,
            'user_agent' => substr($userAgent, 0, 500),
            'dispositivo' => $dispositivo,
            'pais' => $ubicacion['pais'] ?? 'Venezuela',
            'ciudad' => $ubicacion['ciudad'] ?? 'Caracas',
            'codigo_pais' => $ubicacion['codigo_pais'] ?? 'VE',
            'login_at' => $now,
            'last_activity_at' => $now,
            'status' => 'Activa',
            'duracion_segundos' => 0,
        ]);
    }

    /**
     * Actualiza la fecha/hora de última actividad y duración transcurrida.
     * Throttled a una vez cada 45 segundos por sesión para no saturar la base de datos.
     */
    public static function actualizarActividad(?string $sessionId): void
    {
        if (empty($sessionId)) {
            return;
        }

        $cacheKey = "conexion_actividad_{$sessionId}";
        if (Cache::has($cacheKey)) {
            return;
        }

        // Cache 45 segundos para evitar escrituras repetitivas en cada petición
        Cache::put($cacheKey, true, 45);

        $conexion = self::where('session_id', $sessionId)
            ->where('status', 'Activa')
            ->first();

        if ($conexion) {
            $now = Carbon::now();
            $duracion = max(0, $now->diffInSeconds($conexion->login_at));

            $conexion->update([
                'last_activity_at' => $now,
                'duracion_segundos' => $duracion,
            ]);
        }
    }

    /**
     * Registra el cierre de una sesión (por logout, inactividad o forzado).
     */
    public static function registrarCierreSesion(?string $sessionId, ?int $userId = null, string $status = 'Cerrada', ?string $origen = null): void
    {
        $query = self::where('status', 'Activa');

        if (!empty($sessionId)) {
            $query->where('session_id', $sessionId);
        } elseif (!empty($userId)) {
            $query->where('user_id', $userId);
        } else {
            return;
        }

        $conexiones = $query->get();
        $now = Carbon::now();

        foreach ($conexiones as $conexion) {
            $cierreAt = ($status === 'Expirada' && $conexion->last_activity_at)
                ? $conexion->last_activity_at
                : $now;

            $duracion = max(0, $cierreAt->diffInSeconds($conexion->login_at));

            $conexion->update([
                'logout_at' => $cierreAt,
                'duracion_segundos' => $duracion,
                'status' => $status,
                'origen_cierre' => $origen ?? match ($status) {
                    'Cerrada' => 'Cierre de sesión manual',
                    'Expirada' => 'Expiración por inactividad',
                    'Forzada' => 'Inicio de sesión en otro dispositivo',
                    default => 'Desconexión',
                },
            ]);
        }
    }

    /**
     * Detecta la ubicación geográfica (País y Ciudad) a partir de la dirección IP.
     * Maneja IPs privadas y de red local sin latencia, y cachea IPs públicas.
     */
    public static function detectarUbicacion(string $ip): array
    {
        // 1. Detección de IPs locales, privadas o loopback
        if (
            empty($ip) ||
            $ip === '127.0.0.1' ||
            $ip === '::1' ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip)
        ) {
            return [
                'pais' => 'Venezuela',
                'ciudad' => 'Caracas (Red Local / LAN)',
                'codigo_pais' => 'VE',
            ];
        }

        // 2. Consulta en Caché para no repetir llamadas a servicios externos
        $cacheKey = "geo_ip_" . md5($ip);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 3. Consulta de geolocalización IP con timeout corto (1.5s)
        try {
            $response = Http::timeout(1.5)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city");
            if ($response->successful()) {
                $data = $response->json();
                if (($data['status'] ?? '') === 'success') {
                    $ciudad = !empty($data['city']) ? $data['city'] : 'Desconocida';
                    if (!empty($data['regionName']) && $data['regionName'] !== $ciudad) {
                        $ciudad .= ' (' . $data['regionName'] . ')';
                    }

                    $resultado = [
                        'pais' => $data['country'] ?? 'Venezuela',
                        'ciudad' => $ciudad,
                        'codigo_pais' => $data['countryCode'] ?? 'VE',
                    ];

                    Cache::put($cacheKey, $resultado, now()->addDays(3));
                    return $resultado;
                }
            }
        } catch (\Throwable $e) {
            // Silencioso ante fallos de conexión o timeout
        }

        // 4. Fallback por defecto en caso de fallo externo
        $fallback = [
            'pais' => 'Venezuela',
            'ciudad' => 'Ubicación IP Estándar',
            'codigo_pais' => 'VE',
        ];
        Cache::put($cacheKey, $fallback, now()->addHours(6));
        return $fallback;
    }

    /**
     * Parsea el User Agent para extraer el Dispositivo, Navegador y Sistema Operativo.
     */
    public static function detectarDispositivo(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Dispositivo No Identificado';
        }

        $os = 'Sistema Desconocido';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $userAgent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/i', $userAgent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $os = 'iOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }

        $browser = 'Navegador Web';
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $userAgent) && !preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$os} · {$browser}";
    }

    /**
     * Accesor para el tiempo de duración formateado legible en español.
     */
    public function getDuracionFormateadaAttribute(): string
    {
        $segundos = $this->duracion_segundos;

        if ($this->status === 'Activa') {
            $segundos = max(0, Carbon::now()->diffInSeconds($this->login_at));
        }

        if ($segundos <= 0) {
            return '< 1 min';
        }

        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $seg = $segundos % 60;

        if ($horas > 0) {
            return "{$horas}h {$minutos}m {$seg}s";
        }

        if ($minutos > 0) {
            return "{$minutos}m {$seg}s";
        }

        return "{$seg}s";
    }

    /**
     * Bandera emoji o código del país.
     */
    public function getBanderaEmojiAttribute(): string
    {
        $codigo = strtoupper($this->codigo_pais ?? 'VE');
        if (strlen($codigo) === 2) {
            $first = ord($codigo[0]) - 65 + 0x1F1E6;
            $second = ord($codigo[1]) - 65 + 0x1F1E6;
            return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
        }
        return '🇻🇪';
    }

    /**
     * URL de la imagen de la bandera del país (Windows no renderiza emojis de banderas).
     */
    public function getBanderaUrlAttribute(): string
    {
        $codigo = strtolower(trim($this->codigo_pais ?? 'VE'));
        if (!preg_match('/^[a-z]{2}$/', $codigo)) {
            $codigo = 've';
        }
        return "https://flagcdn.com/w40/{$codigo}.png";
    }
}

