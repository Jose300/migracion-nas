<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogError extends Model
{
    use HasFactory;

    protected $table = 'log_errores';

    protected $fillable = [
        'nivel',
        'clase_error',
        'mensaje',
        'codigo',
        'archivo',
        'linea',
        'trace',
        'url',
        'metodo_http',
        'ip_address',
        'user_agent',
        'request_data',
        'headers',
        'user_id',
        'user_name',
        'user_email',
        'estado',
        'solucion_notas',
        'resuelto_por_user_id',
        'resuelto_at',
        'hash',
    ];

    protected $casts = [
        'request_data' => 'array',
        'headers' => 'array',
        'resuelto_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User who experienced the error.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User who marked the error as resolved.
     */
    public function resueltoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por_user_id');
    }

    /**
     * Short / relative file path for compact display.
     */
    public function getArchivoCortoAttribute(): string
    {
        if (empty($this->archivo)) {
            return 'Desconocido';
        }

        $base = base_path();
        $path = str_replace([$base . DIRECTORY_SEPARATOR, $base . '/'], '', $this->archivo);
        return str_replace('\\', '/', $path);
    }

    /**
     * Nivel badge styling & metadata.
     */
    public function getNivelBadgeAttribute(): array
    {
        $map = [
            'EMERGENCY' => [
                'label' => 'EMERGENCY',
                'class' => 'bg-purple-lt text-purple',
                'icon' => 'ti ti-alert-octagon',
            ],
            'ALERT' => [
                'label' => 'ALERT',
                'class' => 'bg-red-lt text-red',
                'icon' => 'ti ti-bell-ringing',
            ],
            'CRITICAL' => [
                'label' => 'CRITICAL',
                'class' => 'bg-danger text-white',
                'icon' => 'ti ti-flame',
            ],
            'ERROR' => [
                'label' => 'ERROR',
                'class' => 'bg-red-lt text-red',
                'icon' => 'ti ti-alert-circle',
            ],
            'WARNING' => [
                'label' => 'WARNING',
                'class' => 'bg-warning-lt text-warning',
                'icon' => 'ti ti-alert-triangle',
            ],
            'NOTICE' => [
                'label' => 'NOTICE',
                'class' => 'bg-azure-lt text-azure',
                'icon' => 'ti ti-info-circle',
            ],
            'INFO' => [
                'label' => 'INFO',
                'class' => 'bg-blue-lt text-blue',
                'icon' => 'ti ti-info-circle',
            ],
        ];

        $upper = strtoupper($this->nivel ?? 'ERROR');
        return $map[$upper] ?? [
            'label' => $upper,
            'class' => 'bg-secondary-lt text-secondary',
            'icon' => 'ti ti-bug',
        ];
    }

    /**
     * Estado badge styling & metadata.
     */
    public function getEstadoBadgeAttribute(): array
    {
        $map = [
            'pendiente' => [
                'label' => 'Pendiente',
                'class' => 'bg-danger-lt text-danger',
                'icon' => 'ti ti-clock',
            ],
            'en_revision' => [
                'label' => 'En Revisión',
                'class' => 'bg-warning-lt text-warning-fg',
                'icon' => 'ti ti-eye',
            ],
            'resuelto' => [
                'label' => 'Resuelto',
                'class' => 'bg-success-lt text-success',
                'icon' => 'ti ti-check',
            ],
            'ignorado' => [
                'label' => 'Ignorado',
                'class' => 'bg-secondary-lt text-secondary',
                'icon' => 'ti ti-ban',
            ],
        ];

        $state = strtolower($this->estado ?? 'pendiente');
        return $map[$state] ?? [
            'label' => ucfirst($state),
            'class' => 'bg-secondary-lt text-secondary',
            'icon' => 'ti ti-circle',
        ];
    }
}
