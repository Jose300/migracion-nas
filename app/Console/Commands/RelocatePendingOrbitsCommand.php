<?php

namespace App\Console\Commands;

use App\Models\NasMigrationTask;
use App\Services\NasMigrationService;
use Illuminate\Console\Command;

class RelocatePendingOrbitsCommand extends Command
{
    protected $signature = 'nas:relocate-pending
                            {task_id : ID de la tarea de migración NAS}
                            {--log_id= : ID previo del registro de log para seguimiento en vivo}
                            {--user_id= : ID del usuario que ejecutó el traslado}';

    protected $description = 'Traslada de forma segura todas las órbitas pendientes (carpeta local de rescate y MIGRACION_SYNOLOGY) hacia el destino NFS final en Inspur RAWDATA';

    public function handle(NasMigrationService $service): int
    {
        $taskId = (int) $this->argument('task_id');
        $logId = $this->option('log_id') ? (int) $this->option('log_id') : null;
        $userId = $this->option('user_id') ? (int) $this->option('user_id') : null;

        $task = NasMigrationTask::find($taskId);
        if (!$task) {
            $this->error("No se encontró la tarea de migración #{$taskId}");
            return 1;
        }

        $this->info("Iniciando traslado definitivo de órbitas pendientes para {$task->name} (#{$task->id})...");
        $log = $service->relocateAllPendingOrbits($task, $userId, $logId);
        $this->info("Finalizado con estado: {$log->status}. Archivos movidos: {$log->files_moved} ({$log->human_transferred_size}).");

        return $log->status === 'completed' ? 0 : 1;
    }
}
