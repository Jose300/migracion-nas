<?php

namespace App\Console\Commands;

use App\Models\NasMigrationTask;
use App\Services\NasMigrationService;
use Illuminate\Console\Command;

class RunNasSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nas:sync 
                            {task_id? : ID específico de la tarea a sincronizar} 
                            {--all : Ejecutar todas las tareas activas programadas}
                            {--log_id= : ID previo del registro de log para actualizar}
                            {--user_id= : ID del usuario que disparó la sincronización}
                            {--limit= : Límite de archivos a copiar en este lote (ej. 500)}
                            {--source= : Ruta origen configurable en Synology (ej. /volume1/datos/)}
                            {--inspur_dir= : Subdirectorio destino en Inspur /HS_IPS_PRIVATE/RDP_FTP/RAWDATA}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta la sincronización automática de datos entre NAS Synology e Inspur';

    /**
     * Execute the console command.
     */
    public function handle(NasMigrationService $service)
    {
        $taskId = $this->argument('task_id');
        $runAll = $this->option('all');
        $logId = $this->option('log_id') ? (int) $this->option('log_id') : null;
        $userId = $this->option('user_id') ? (int) $this->option('user_id') : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $source = $this->option('source');
        $inspurDir = $this->option('inspur_dir');

        if ($taskId) {
            $task = NasMigrationTask::find($taskId);
            if (!$task) {
                $this->error("No se encontró la tarea de migración #{$taskId}");
                return 1;
            }

            $effectiveLimit = $limit !== null ? $limit : ($task->batch_size ?: 500);
            $this->info("Iniciando sincronización para: {$task->name} (#{$task->id}) [Lote: {$effectiveLimit} archivos]...");
            $log = $service->runSync($task, $userId ? 'manual' : 'scheduled', $userId, $logId, $effectiveLimit, $source, $inspurDir);
            $this->info("Finalizado con estado: {$log->status}. Duración: {$log->duration_formatted}");
            return $log->status === 'completed' ? 0 : 1;
        }

        // Si se ejecuta sin ID o con --all (ejecución automática programada)
        $tasks = NasMigrationTask::where('is_active', true)->get();

        if ($tasks->isEmpty()) {
            $this->comment('No hay tareas de migración NAS activas.');
            return 0;
        }

        $this->info("Procesando {$tasks->count()} tarea(s) de migración programadas...");

        foreach ($tasks as $task) {
            // Evitar solapamientos si ya está corriendo
            if ($task->status === 'running') {
                $this->warn("Saltando tarea {$task->name} (#{$task->id}): Ya se encuentra en ejecución.");
                continue;
            }

            $effectiveLimit = $limit !== null ? $limit : ($task->batch_size ?: 500);
            $this->line("-> Sincronizando {$task->name} [Lote: {$effectiveLimit} archivos]...");
            $log = $service->runSync($task, 'scheduled', null, null, $effectiveLimit);
            $this->info("   Resultado: {$log->status} (Transferidos: {$log->human_transferred_size})");
        }

        $this->info('Todas las sincronizaciones programadas han concluido.');
        return 0;
    }
}
