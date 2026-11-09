<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NasMigrationTask extends Model
{
    use HasFactory;

    protected $table = 'nas_migration_tasks';

    public const INSPUR_AVAILABLE_DIRS = [
        '045974_20260420122113',
        '046395_20260518235137',
        '046472_20260523010845',
        '046855_20260619000257',
        '047195_20260711231844',
        '047788_20260821004042',
        '006956_20190326140607',
        'prueba',
        'VRSS-1',
        'VRSS-2',
    ];

    public const SYNOLOGY_AVAILABLE_SOURCES = [
        // Volumen 2 IPS y sus subdirectorios recursivos
        '/volume1/dae-Volumen2_IPS/',
        '/volume1/dae-Volumen2_IPS/RAWDATA PRINCIPAL/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS1/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2019/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2020/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2021/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2022/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2023/',
        '/volume1/dae-Volumen2_IPS/Respaldo RAWDATA/VRSS2/2024/',

        // Volumen 4 Respaldo IPS y sus subdirectorios recursivos
        '/volume1/dae-Volumen4_Respaldo_IPS/',
        '/volume1/dae-Volumen4_Respaldo_IPS/RAWDATA BACKUP/',
        '/volume1/dae-Volumen4_Respaldo_IPS/RAWDATA BACKUP/RAW DATA 2/',
        '/volume1/dae-Volumen4_Respaldo_IPS/RAWDATA PRINCIPAL/',
    ];

    public const SYNOLOGY_AVAILABLE_TARGETS = [
        '/volume1/dae-RowData/MIGRACION_SYNOLOGY/',
    ];

    protected $fillable = [
        'name',
        'description',
        'nas_host',
        'nas_port',
        'ssh_user',
        'ssh_auth_type',
        'ssh_password',
        'ssh_key_path',
        'source_path',
        'target_path',
        'inspur_base_path',
        'inspur_target_dir',
        'last_migrated_orbit',
        'last_migrated_orbit_at',
        'inspur_host',
        'inspur_port',
        'inspur_user',
        'inspur_password',
        'inspur_shared_folder',
        'auto_move_to_inspur',
        'rsync_options',
        'delete_after_sync',
        'batch_size',
        'schedule_frequency',
        'schedule_time',
        'status',
        'last_run_at',
        'last_run_status',
        'is_active',
    ];

    protected $casts = [
        'nas_port' => 'integer',
        'inspur_port' => 'integer',
        'batch_size' => 'integer',
        'delete_after_sync' => 'boolean',
        'auto_move_to_inspur' => 'boolean',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'ssh_password' => 'encrypted',
        'inspur_password' => 'encrypted',
    ];

    public function getFullInspurPathAttribute(): string
    {
        $base = rtrim($this->inspur_base_path ?: '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA', '/');
        $sub = trim($this->inspur_target_dir ?: 'VRSS-1', '/');
        return "{$base}/{$sub}/";
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NasMigrationLog::class, 'nas_migration_task_id')->latest('created_at');
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(NasMigrationLog::class, 'nas_migration_task_id')->latestOfMany();
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(NasFileVerification::class, 'nas_migration_task_id')->latest('started_at');
    }

    public function latestVerification(): HasOne
    {
        return $this->hasOne(NasFileVerification::class, 'nas_migration_task_id')->latestOfMany('started_at');
    }
}
