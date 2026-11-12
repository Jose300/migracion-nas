<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasFileVerificationItem extends Model
{
    use HasFactory;

    protected $table = 'nas_file_verification_items';

    protected $fillable = [
        'nas_file_verification_id',
        'relative_path',
        'file_name',
        'synology_size',
        'synology_mtime',
        'shared_size',
        'shared_mtime',
        'phase1_status',
        'inspur_size',
        'inspur_mtime',
        'phase2_status',
        'status',
        'notes',
    ];

    protected $casts = [
        'synology_size' => 'integer',
        'shared_size' => 'integer',
        'inspur_size' => 'integer',
        'synology_mtime' => 'datetime',
        'shared_mtime' => 'datetime',
        'inspur_mtime' => 'datetime',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(NasFileVerification::class, 'nas_file_verification_id');
    }

    public function getHumanSynologySizeAttribute(): string
    {
        if ($this->synology_size === null) {
            return '-';
        }
        return $this->formatBytes($this->synology_size);
    }

    public function getHumanSharedSizeAttribute(): string
    {
        if ($this->shared_size === null) {
            return '-';
        }
        return $this->formatBytes($this->shared_size);
    }

    public function getHumanInspurSizeAttribute(): string
    {
        if ($this->inspur_size === null) {
            return 'No existe';
        }
        return $this->formatBytes($this->inspur_size);
    }
        return $this->formatBytes($this->inspur_size);
    }

    public function getNameMatchAttribute(): bool
    {
        return !empty($this->file_name);
    }

    public function getSizeMatchAttribute(): bool
    {
        return $this->synology_size !== null 
            && $this->inspur_size !== null 
            && $this->synology_size === $this->inspur_size;
    }

    public function getDateMatchAttribute(): bool
    {
        if (!$this->synology_mtime || !$this->inspur_mtime) {
            return false;
        }

        // Tolerancia de hasta 2 segundos para variaciones de sistemas de archivos
        return abs($this->synology_mtime->getTimestamp() - $this->inspur_mtime->getTimestamp()) <= 2;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'Confirmado (Idéntico)',
            'size_mismatch' => 'Diferencia de Tamaño',
            'date_mismatch' => 'Diferencia de Fecha',
            'missing_target' => 'Faltante en Inspur',
            'missing_source' => 'Solo en Inspur',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'bg-green-lt text-green',
            'size_mismatch' => 'bg-danger-lt text-danger',
            'date_mismatch' => 'bg-warning-lt text-warning',
            'missing_target' => 'bg-danger-lt text-danger',
            'missing_source' => 'bg-azure-lt text-azure',
            default => 'bg-secondary-lt text-secondary',
        };
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

