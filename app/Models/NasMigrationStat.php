<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasMigrationStat extends Model
{
    use HasFactory;

    protected $table = 'nas_migration_stats';

    protected $fillable = [
        'responsable',
        'fecha',
        'satelite',
        'archivo_id',
        'tamano_gb',
        'lote_numero',
        'cantidad_archivos',
        'observaciones',
        'nas_migration_task_id',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'tamano_gb' => 'float',
        'lote_numero' => 'integer',
        'cantidad_archivos' => 'integer',
    ];

    /**
     * Tarea de migración asociada si aplica.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(NasMigrationTask::class, 'nas_migration_task_id');
    }

    /**
     * Usuario que registró el dato si aplica.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tamaño legible (GB o TB).
     */
    public function getTamanoLegibleAttribute(): string
    {
        if ($this->tamano_gb >= 1024) {
            return number_format($this->tamano_gb / 1024, 2) . ' TB';
        }
        return number_format($this->tamano_gb, 2) . ' GB';
    }
}

