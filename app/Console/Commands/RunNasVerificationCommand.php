<?php

namespace App\Console\Commands;

use App\Models\NasMigrationTask;
use App\Services\NasVerificationService;
use Illuminate\Console\Command;

class RunNasVerificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nas:verify 
                            {task_id : ID de la tarea de migración a verificar} 
                            {--verification_id= : ID previo del registro de verificación}
                            {--user_id= : ID del usuario que disparó la verificación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica y coteja la integridad de archivos copiados entre NAS Synology e Inspur';

    /**
     * Execute the console command.
     */
    public function handle(NasVerificationService $service)
    {
        $taskId = (int) $this->argument('task_id');
        $verificationId = $this->option('verification_id') ? (int) $this->option('verification_id') : null;
        $userId = $this->option('user_id') ? (int) $this->option('user_id') : null;

        $task = NasMigrationTask::find($taskId);

        if (!$task) {
            $this->error("No se encontró la tarea de migración #{$taskId}");
            return 1;
        }

        $this->info("Iniciando cotejo de archivos para tarea #{$task->id}: {$task->name}...");
        $verification = $service->runVerification($task, $userId, $verificationId);

        $this->info("Verificación finalizada con estado: {$verification->status}");
        $this->line("Archivos analizados: {$verification->total_files_synology} | Confirmados: {$verification->total_confirmed} ({$verification->confirmed_percentage}%)");

        return $verification->status === 'completed' ? 0 : 1;
    }
}
