<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NasMigrationLog extends Model
{
    use HasFactory;

    protected $table = 'nas_migration_logs';

    protected $fillable = [
        'nas_migration_task_id',
        'status',
        'trigger_type',
        'user_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'files_transferred',
        'source_path_used',
        'inspur_target_dir_used',
        'files_moved',
        'total_bytes_transferred',
        'human_transferred_size',
        'speed',
        'summary',
        'output',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
        'files_transferred' => 'integer',
        'total_bytes_transferred' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(NasMigrationTask::class, 'nas_migration_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
}
