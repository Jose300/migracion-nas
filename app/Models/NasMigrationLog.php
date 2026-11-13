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
        'total_files_in_batch',
        'phase_synology_status',
        'phase_inspur_status',
        'phase_details',
        'source_path_used',
        'inspur_target_dir_used',
        'files_moved',
        'last_migrated_orbit_in_batch',
        'orbits_transferred',
        'orbits_skipped',
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

    /**
     * Extrae el listado de órbitas procesadas en esta ejecución a partir de la salida y campos del log.
     */
    public function getCopiedOrbitsAttribute(): array
    {
        $orbits = [];

        if (!empty($this->output)) {
            if (preg_match_all('/PROGRESS_FILE:\s*([0-9]{6}_[0-9]{14})/', $this->output, $matches)) {
                $orbits = array_values(array_unique($matches[1]));
            }
        }

        if (empty($orbits) && !empty($this->last_migrated_orbit_in_batch)) {
            $orbits = [$this->last_migrated_orbit_in_batch];
        }

        return $orbits;
    }

    /**
     * Extrae el listado de archivos agrupados por órbita transferida en esta ejecución.
     */
    public function getOrbitsWithFilesAttribute(): array
    {
        $orbitsData = [];

        if (!empty($this->output)) {
            if (preg_match_all('/PROGRESS_FILE:\s*([^\r\n]+)/', $this->output, $matches)) {
                foreach ($matches[1] as $line) {
                    $trimmed = trim($line);
                    if (!empty($trimmed) && !str_ends_with($trimmed, '/')) {
                        $parts = explode('/', $trimmed);
                        $orbitName = $parts[0] ?? 'Varios';
                        $fileName = end($parts);

                        if (!isset($orbitsData[$orbitName])) {
                            $orbitsData[$orbitName] = [
                                'orbit' => $orbitName,
                                'files_count' => 0,
                                'files' => [],
                            ];
                        }

                        $orbitsData[$orbitName]['files_count']++;
                        $orbitsData[$orbitName]['files'][] = [
                            'relative_path' => $trimmed,
                            'file_name' => $fileName,
                        ];
                    }
                }
            }
        }

        // Si no hubo detalle PROGRESS_FILE pero sí una última órbita registrada
        if (empty($orbitsData) && !empty($this->last_migrated_orbit_in_batch)) {
            $orb = $this->last_migrated_orbit_in_batch;
            $orbitsData[$orb] = [
                'orbit' => $orb,
                'files_count' => $this->files_transferred ?: 0,
                'files' => [],
            ];
        }

        return array_values($orbitsData);
    }
}
