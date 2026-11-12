<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NasFileVerification extends Model
{
    use HasFactory;

    protected $table = 'nas_file_verifications';

    protected $fillable = [
        'nas_migration_task_id',
        'user_id',
        'status',
        'current_orbit',
        'files_scanned',
        'total_files_to_scan',
        'started_at',
        'finished_at',
        'duration_seconds',
        'total_files_synology',
        'total_files_inspur',
        'total_confirmed',
        'total_mismatched_size',
        'total_mismatched_date',
        'total_missing_in_target',
        'total_bytes_synology',
        'total_bytes_inspur',
        'summary',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
        'total_files_synology' => 'integer',
        'total_files_inspur' => 'integer',
        'total_confirmed' => 'integer',
        'total_mismatched_size' => 'integer',
        'total_mismatched_date' => 'integer',
        'total_missing_in_target' => 'integer',
        'total_bytes_synology' => 'integer',
        'total_bytes_inspur' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(NasMigrationTask::class, 'nas_migration_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(NasFileVerificationItem::class, 'nas_file_verification_id');
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '-';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%02dh %02dm %02ds', $hours, $minutes, $seconds);
        }

        if ($minutes > 0) {
            return sprintf('%02dm %02ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    public function getConfirmedPercentageAttribute(): float
    {
        if ($this->total_files_synology <= 0) {
            return 0.0;
        }

        return round(($this->total_confirmed / $this->total_files_synology) * 100, 1);
    }

    public function getHumanSynologySizeAttribute(): string
    {
        return $this->formatBytes($this->total_bytes_synology);
    }

    public function getHumanInspurSizeAttribute(): string
    {
        return $this->formatBytes($this->total_bytes_inspur);
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

