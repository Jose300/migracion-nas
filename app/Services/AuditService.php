<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Sensitive fields that must be masked or omitted in audit data.
     */
    protected static array $hiddenFields = [
        'password',
        'remember_token',
    ];

    /**
     * Record an audit event.
     */
    public static function log(array $params): ?Auditoria
    {
        try {
            $user = Auth::user();
            $request = request();

            // Sanitize data anterior and data nueva
            $dataAnterior = isset($params['data_anterior']) ? self::sanitize($params['data_anterior']) : null;
            $dataNueva = isset($params['data_nueva']) ? self::sanitize($params['data_nueva']) : null;

            return Auditoria::create([
                'user_id' => $params['user_id'] ?? $user?->id,
                'user_name' => $params['user_name'] ?? ($user?->name ?? 'Sistema'),
                'user_email' => $params['user_email'] ?? ($user?->email ?? null),
                'user_role' => $params['user_role'] ?? ($user?->getRoleNames()->first() ?? ($user ? 'Usuario' : 'Sistema')),
                'modulo' => $params['modulo'] ?? 'General',
                'tabla' => $params['tabla'] ?? 'desconocida',
                'accion' => strtoupper($params['accion'] ?? 'ACTUALIZAR'),
                'registro_id' => $params['registro_id'] ?? null,
                'registro_codigo' => $params['registro_codigo'] ?? null,
                'registro_nombre' => $params['registro_nombre'] ?? null,
                'data_anterior' => $dataAnterior,
                'data_nueva' => $dataNueva,
                'campos_modificados' => $params['campos_modificados'] ?? null,
                'ip_address' => $params['ip_address'] ?? ($request?->ip() ?? '127.0.0.1'),
                'user_agent' => $params['user_agent'] ?? ($request?->userAgent() ?? 'Sistema / CLI'),
                'url' => $params['url'] ?? ($request?->fullUrl() ?? ''),
                'metodo_http' => $params['metodo_http'] ?? ($request?->method() ?? 'CLI'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Error registrando auditoría: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize array attributes removing or masking secrets.
     */
    protected static function sanitize($data): ?array
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $val) {
            if (in_array(strtolower($key), self::$hiddenFields, true)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
