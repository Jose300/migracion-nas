<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $fillable = [
        'user_id',
        'user_name',
        'user_email',
        'user_role',
        'modulo',
        'tabla',
        'accion',
        'registro_id',
        'registro_codigo',
        'registro_nombre',
        'data_anterior',
        'data_nueva',
        'campos_modificados',
        'ip_address',
        'user_agent',
        'url',
        'metodo_http',
    ];

    protected $casts = [
        'data_anterior' => 'array',
        'data_nueva' => 'array',
        'campos_modificados' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parse User Agent into friendly browser & OS description.
     */
    public function getBrowserAttribute(): string
    {
        $ua = $this->user_agent ?? '';

        if (empty($ua)) {
            return 'Navegador no identificado';
        }

        // Platform / OS detection
        $os = 'Dispositivo';
        if (preg_match('/windows nt 10\.0/i', $ua)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/iphone/i', $ua)) {
            $os = 'iPhone iOS';
        } elseif (preg_match('/ipad/i', $ua)) {
            $os = 'iPad iPadOS';
        } elseif (preg_match('/android/i', $ua)) {
            $os = 'Android';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        // Browser detection
        $browser = 'Navegador Web';
        if (preg_match('/edg/i', $ua)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $ua)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $ua)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/opera|opr/i', $ua)) {
            $browser = 'Opera';
        }

        return "{$browser} en {$os}";
    }

    /**
     * Accion badge configuration.
     */
    public function getAccionBadgeAttribute(): array
    {
        $map = [
            'CREAR' => [
                'label' => '+ Creación',
                'class' => 'bg-green-lt text-green',
                'icon' => 'ti ti-plus',
            ],
            'ACTUALIZAR' => [
                'label' => 'Modificación',
                'class' => 'bg-blue-lt text-blue',
                'icon' => 'ti ti-edit',
            ],
            'CAMBIO_ESTADO' => [
                'label' => 'Cambio Estado',
                'class' => 'bg-cyan-lt text-cyan',
                'icon' => 'ti ti-toggle-right',
            ],
            'ELIMINAR' => [
                'label' => 'Eliminación',
                'class' => 'bg-red-lt text-red',
                'icon' => 'ti ti-trash',
            ],
            'RESTAURAR' => [
                'label' => 'Restauración',
                'class' => 'bg-purple-lt text-purple',
                'icon' => 'ti ti-rotate-clockwise',
            ],
        ];

        return $map[$this->accion] ?? [
            'label' => $this->accion,
            'class' => 'bg-secondary-lt text-secondary',
            'icon' => 'ti ti-activity',
        ];
    }
}
