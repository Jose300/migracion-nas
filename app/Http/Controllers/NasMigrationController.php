<?php

namespace App\Http\Controllers;

use App\Models\NasMigrationLog;
use App\Models\NasMigrationTask;
use App\Services\NasMigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NasMigrationController extends Controller
{
    public function __construct(
        protected NasMigrationService $migrationService
    ) {}

    /**
     * Panel principal de migración NAS
     */
    public function index(Request $request): View
    {
        $tasks = NasMigrationTask::with('latestLog')->get();

        // Métricas globales
        $totalTasks = $tasks->count();
        $tasksActivas = $tasks->where('is_active', true)->count();
        $totalExitosas = NasMigrationLog::where('status', 'completed')->count();
        $totalFallidas = NasMigrationLog::where('status', 'failed')->count();
        $totalEnEjecucion = NasMigrationLog::where('status', 'running')->count();

        // Directorios disponibles en Inspur y Synology
        $inspurAvailableDirs = NasMigrationTask::INSPUR_AVAILABLE_DIRS;
        $firstTask = $tasks->first();
        $synologyAvailableSources = $firstTask
            ? $this->migrationService->listSynologyDirectories($firstTask)
            : NasMigrationTask::SYNOLOGY_AVAILABLE_SOURCES;
        $synologyAvailableTargets = NasMigrationTask::SYNOLOGY_AVAILABLE_TARGETS;

        // Logs filtrados
        $logsQuery = NasMigrationLog::with(['task', 'user'])->latest('started_at');

        if ($request->filled('task_id') && $request->task_id !== 'all') {
            $logsQuery->where('nas_migration_task_id', $request->task_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $logsQuery->where('status', $request->status);
        }

        if ($request->filled('trigger_type') && $request->trigger_type !== 'all') {
            $logsQuery->where('trigger_type', $request->trigger_type);
        }

        $logs = $logsQuery->paginate(10)->withQueryString();

        return view('nas-migracion.index', compact(
            'tasks',
            'logs',
            'totalTasks',
            'tasksActivas',
            'totalExitosas',
            'totalFallidas',
            'totalEnEjecucion',
            'inspurAvailableDirs',
            'synologyAvailableSources',
            'synologyAvailableTargets'
        ));
    }

    /**
     * Guarda una nueva tarea de migración.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'nas_host' => 'required|string|max:100',
            'nas_port' => 'required|integer|min:1|max:65535',
            'ssh_user' => 'required|string|max:100',
            'ssh_auth_type' => 'required|in:password,key',
            'ssh_password' => 'nullable|string',
            'ssh_key_path' => 'nullable|string',
            'source_path' => 'required|string|max:255',
            'target_path' => 'nullable|string|max:255',
            'inspur_base_path' => 'nullable|string|max:255',
            'inspur_target_dir' => 'required|string|max:255',
            'last_migrated_orbit' => 'nullable|string|max:255',
            'inspur_host' => 'nullable|string|max:100',
            'inspur_port' => 'nullable|integer|min:1|max:65535',
            'inspur_user' => 'nullable|string|max:100',
            'inspur_password' => 'nullable|string',
            'inspur_shared_folder' => 'nullable|string|max:255',
            'auto_move_to_inspur' => 'nullable|boolean',
            'rsync_options' => 'nullable|string|max:255',
            'delete_after_sync' => 'nullable|boolean',
            'batch_size' => 'nullable|integer|min:1|max:10000',
            'schedule_frequency' => 'required|in:manual,hourly,daily,twiceDaily,weekly',
            'schedule_time' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['target_path'] = (!empty($validated['target_path'])) ? $validated['target_path'] : '/volume3/dae-RowData/MIGRACION_SYNOLOGY/';
        $validated['inspur_base_path'] = (!empty($validated['inspur_base_path'])) ? $validated['inspur_base_path'] : '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA';
        $validated['inspur_shared_folder'] = (!empty($validated['inspur_shared_folder'])) ? $validated['inspur_shared_folder'] : '/HS_SHARE/MIGRACION_SYNOLOGY';
        $validated['inspur_host'] = (!empty($validated['inspur_host'])) ? $validated['inspur_host'] : '192.168.10.180';
        $validated['inspur_port'] = (!empty($validated['inspur_port'])) ? (int)$validated['inspur_port'] : 22;
        $validated['inspur_user'] = (!empty($validated['inspur_user'])) ? $validated['inspur_user'] : 'root';
        $validated['delete_after_sync'] = $request->has('delete_after_sync');
        $validated['auto_move_to_inspur'] = $request->boolean('auto_move_to_inspur', true);
        $validated['is_active'] = $request->has('is_active');
        $validated['batch_size'] = $validated['batch_size'] ?? 500;

        // Garantizar opciones seguras por defecto
        if (empty($validated['rsync_options'])) {
            $validated['rsync_options'] = '-avh --stats --progress --timeout=300 --partial';
        }

        NasMigrationTask::create($validated);

        return redirect()->route('nas-migracion.index')->with('success', 'Tarea de migración creada exitosamente.');
    }

    /**
     * Actualiza una tarea existente.
     */
    public function update(Request $request, NasMigrationTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'nas_host' => 'required|string|max:100',
            'nas_port' => 'required|integer|min:1|max:65535',
            'ssh_user' => 'required|string|max:100',
            'ssh_auth_type' => 'required|in:password,key',
            'ssh_password' => 'nullable|string',
            'ssh_key_path' => 'nullable|string',
            'source_path' => 'required|string|max:255',
            'target_path' => 'nullable|string|max:255',
            'inspur_base_path' => 'nullable|string|max:255',
            'inspur_target_dir' => 'required|string|max:255',
            'last_migrated_orbit' => 'nullable|string|max:255',
            'inspur_host' => 'nullable|string|max:100',
            'inspur_port' => 'nullable|integer|min:1|max:65535',
            'inspur_user' => 'nullable|string|max:100',
            'inspur_password' => 'nullable|string',
            'inspur_shared_folder' => 'nullable|string|max:255',
            'auto_move_to_inspur' => 'nullable|boolean',
            'rsync_options' => 'nullable|string|max:255',
            'delete_after_sync' => 'nullable|boolean',
            'batch_size' => 'nullable|integer|min:1|max:10000',
            'schedule_frequency' => 'required|in:manual,hourly,daily,twiceDaily,weekly',
            'schedule_time' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['target_path'] = (!empty($validated['target_path'])) ? $validated['target_path'] : '/volume3/dae-RowData/MIGRACION_SYNOLOGY/';
        $validated['inspur_base_path'] = (!empty($validated['inspur_base_path'])) ? $validated['inspur_base_path'] : '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA';
        $validated['inspur_shared_folder'] = (!empty($validated['inspur_shared_folder'])) ? $validated['inspur_shared_folder'] : '/HS_SHARE/MIGRACION_SYNOLOGY';
        $validated['inspur_host'] = (!empty($validated['inspur_host'])) ? $validated['inspur_host'] : '192.168.10.180';
        $validated['inspur_port'] = (!empty($validated['inspur_port'])) ? (int)$validated['inspur_port'] : 22;
        $validated['inspur_user'] = (!empty($validated['inspur_user'])) ? $validated['inspur_user'] : 'root';
        $validated['delete_after_sync'] = $request->has('delete_after_sync');
        $validated['auto_move_to_inspur'] = $request->boolean('auto_move_to_inspur', true);
        $validated['is_active'] = $request->has('is_active');
        $validated['batch_size'] = $validated['batch_size'] ?? 500;

        // Si la contraseña viene vacía, no sobreescribir la existente
        if (empty($validated['ssh_password'])) {
            unset($validated['ssh_password']);
        }
        if (empty($validated['inspur_password'])) {
            unset($validated['inspur_password']);
        }

        $task->update($validated);

        return redirect()->route('nas-migracion.index')->with('success', 'Tarea de migración actualizada correctamente.');
    }

    /**
     * Elimina una tarea.
     */
    public function destroy(NasMigrationTask $task): RedirectResponse
    {
        $task->delete();
        return redirect()->route('nas-migracion.index')->with('success', 'Tarea de migración eliminada.');
    }

    /**
     * Dispara la sincronización asíncrona en SEGUNDO PLANO (sin timeout de navegador).
     * Por defecto procesa un lote de 500 archivos por clic.
     */
    public function startSync(Request $request, NasMigrationTask $task): JsonResponse
    {
        // 0. Si ya figura en ejecución, verificar si realmente está activa o si fue un cuelgue previo
        if ($task->status === 'running') {
            $latestLog = $task->latestLog;
            if (!$latestLog || in_array($latestLog->status, ['completed', 'failed']) || ($latestLog->started_at && $latestLog->started_at->diffInMinutes(now()) >= 5)) {
                $task->update(['status' => 'idle', 'last_run_status' => 'failed']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta tarea ya se encuentra en ejecución en segundo plano.',
                ], 422);
            }
        }

        // 1. Pre-verificación de conectividad en tiempo real (Ping y SSH)
        // Evita que el sistema o la interfaz se queden colgados si la NAS está inaccesible o apagada
        $connTest = $this->migrationService->testConnection($task);
        if (!$connTest['success']) {
            $task->update([
                'status' => 'idle',
                'last_run_status' => 'failed',
            ]);

            $motivo = '';
            if (!$connTest['ping']) {
                $motivo = "La IP {$task->nas_host} no responde al ping (host inalcanzable o desconectado).";
            } elseif (!$connTest['port_open']) {
                $motivo = "El puerto SSH {$task->nas_port} en {$task->nas_host} no responde o está cerrado.";
            } else {
                $motivo = "Autenticación SSH fallida en {$task->nas_host}. Verifica el usuario y la contraseña o llave SSH.";
            }

            // Registrar log del intento fallido para trazabilidad
            NasMigrationLog::create([
                'nas_migration_task_id' => $task->id,
                'status' => 'failed',
                'trigger_type' => 'manual',
                'user_id' => auth()->id(),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_seconds' => 0,
                'files_transferred' => 0,
                'error_message' => "Fallo de conexión previo a la migración: {$motivo}",
                'output' => "Diagnóstico de conexión:\n" . json_encode($connTest, JSON_PRETTY_PRINT),
            ]);

            return response()->json([
                'success' => false,
                'error_type' => 'connection_error',
                'message' => "No se pudo iniciar la sincronización: {$motivo}",
                'details' => $connTest,
            ], 422);
        }

        $limit = (int) ($request->input('limit') ?: ($task->batch_size ?: 500));
        $source = $request->input('source_path') ?: $task->source_path;
        $inspurDir = $request->input('inspur_target_dir') ?: ($task->inspur_target_dir ?: 'VRSS-1');

        // Actualizar la tarea con los parámetros de la sincronización
        $task->update([
            'source_path' => $source,
            'inspur_target_dir' => $inspurDir,
            'status' => 'running',
            'last_run_at' => now(),
        ]);

        // 2. Crear el registro inicial de log y marcar la tarea como 'running'
        $log = NasMigrationLog::create([
            'nas_migration_task_id' => $task->id,
            'status' => 'running',
            'trigger_type' => 'manual',
            'user_id' => auth()->id(),
            'started_at' => now(),
            'source_path_used' => $source,
            'inspur_target_dir_used' => $inspurDir,
        ]);

        // 3. Despachar el comando Artisan de forma desacoplada en segundo plano
        // Localizar de forma robusta el ejecutable de php.exe en Windows (Laragon / Apache mod_php)
        $php = PHP_BINARY;
        if (PHP_OS_FAMILY === 'Windows') {
            if (!str_ends_with(strtolower($php), 'php.exe') || !file_exists($php)) {
                // 1. Probar desde extension_dir (C:/laragon/bin/php/php-X.X/ext -> C:/laragon/bin/php/php-X.X/php.exe)
                $extDir = ini_get('extension_dir');
                if ($extDir) {
                    $cand = dirname($extDir) . DIRECTORY_SEPARATOR . 'php.exe';
                    if (file_exists($cand)) {
                        $php = $cand;
                    }
                }
            }

            // 2. Probar buscar en las carpetas comunes de Laragon si aún no se ha resuelto
            if (!str_ends_with(strtolower($php), 'php.exe') || !file_exists($php)) {
                $laragonPhpDirs = glob('C:\\laragon\\bin\\php\\php*\\php.exe');
                if (!empty($laragonPhpDirs)) {
                    rsort($laragonPhpDirs); // Tomar la versión más reciente
                    $php = $laragonPhpDirs[0];
                }
            }

            // 3. Fallback a 'php.exe' en caso de que esté en el PATH del sistema
            if (!file_exists($php)) {
                $php = 'php.exe';
            }
        }
        $artisan = base_path('artisan');
        $userId = auth()->id() ?? 0;

        if (PHP_OS_FAMILY === 'Windows') {
            // En Apache sobre Windows, popen directo de 'start' no abre cmd.exe; se requiere cmd /c start /B
            $bgLog = storage_path('logs/nas_sync_background.log');
            $command = "cmd /c start /B \"\" \"{$php}\" \"{$artisan}\" nas:sync {$task->id} --log_id={$log->id} --user_id={$userId} --limit={$limit} --source=\"{$source}\" --inspur_dir=\"{$inspurDir}\" > \"{$bgLog}\" 2>&1";
            pclose(popen($command, 'r'));
        } else {
            // En Linux / Unix
            $command = "{$php} \"{$artisan}\" nas:sync {$task->id} --log_id={$log->id} --user_id={$userId} --limit={$limit} --source=\"{$source}\" --inspur_dir=\"{$inspurDir}\" > /dev/null 2>&1 &";
            exec($command);
        }

        // Respuesta inmediata en menos de 100ms: el navegador no se cuelga
        return response()->json([
            'success' => true,
            'status' => 'running',
            'message' => "La tarea de migración se ha iniciado en segundo plano. Se transferirá un lote de hasta {$limit} archivos de forma asíncrona.",
            'log_id' => $log->id,
            'task_id' => $task->id,
            'limit' => $limit,
        ]);
    }

    /**
     * Paraliza y detiene inmediatamente una tarea de sincronización en ejecución.
     */
    public function stopSync(NasMigrationTask $task): JsonResponse
    {
        $task->update([
            'status' => 'idle',
            'last_run_status' => 'failed',
        ]);

        $runningLogs = $task->logs()->where('status', 'running')->get();
        foreach ($runningLogs as $log) {
            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => 'Ejecución paralizada y cancelada por el usuario desde el panel.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "La ejecución de la tarea '{$task->name}' ha sido paralizada correctamente.",
        ]);
    }

    /**
     * Consulta el estado en tiempo real de una tarea (para el polling del frontend).
     */
    public function checkStatus(NasMigrationTask $task): JsonResponse
    {
        $task->refresh();
        $latestLog = $task->latestLog;

        // Auto-recuperación 1: Si la tarea figura 'running' pero el último log ya finalizó o falló
        if ($task->status === 'running' && $latestLog && in_array($latestLog->status, ['completed', 'failed'])) {
            $task->update([
                'status' => 'idle',
                'last_run_status' => $latestLog->status,
            ]);
            $task->refresh();
        }

        // Auto-recuperación 2: Detección inteligente por PROCESOS ACTIVOS
        // Solo se paraliza si la tarea lleva más de 3 minutos sin reportar actividad Y no hay ningún proceso en la NAS
        if ($task->status === 'running' && $latestLog && $latestLog->status === 'running') {
            $lastActivity = $latestLog->updated_at ?? $latestLog->started_at;
            if ($lastActivity && $lastActivity->diffInMinutes(now()) >= 3) {
                $isNasTransferActive = $this->migrationService->isTransferProcessRunningOnNas($task);
                if ($isNasTransferActive) {
                    // El proceso de copiado/traslado sigue vivo en la NAS transfiriendo; no interrumpir y refrescar marca de vida
                    $latestLog->touch();
                } else {
                    // No hay procesos de copiado ejecutándose en la NAS: matamos y cerramos la tarea huérfana
                    $this->migrationService->killOrphanTransferProcesses($task);
                    $latestLog->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'error_message' => 'Proceso de sincronización finalizado o inactivo en la NAS remota (sin procesos de copiado activos).',
                    ]);
                    $task->update([
                        'status' => 'idle',
                        'last_run_status' => 'failed',
                    ]);
                    $task->refresh();
                    $latestLog->refresh();
                }
            }
        }

        $filesTransferred = $latestLog ? (int) ($latestLog->files_transferred ?? 0) : 0;
        $totalFilesInBatch = $latestLog ? (int) ($latestLog->total_files_in_batch ?? 0) : 0;
        $batchLimitOrbits = (int) ($task->batch_size ?: 5);

        if ($totalFilesInBatch > 0) {
            $percentage = min(100, round(($filesTransferred / $totalFilesInBatch) * 100, 1));
        } else {
            $percentage = $filesTransferred > 0 ? 10 : 0;
        }

        return response()->json([
            'task_id' => $task->id,
            'status' => $task->status,
            'last_run_status' => $task->last_run_status,
            'files_transferred' => $filesTransferred,
            'total_files_in_batch' => $totalFilesInBatch,
            'batch_size' => $batchLimitOrbits,
            'percentage' => $percentage,
            'current_orbit' => $latestLog->latest_processed_orbit ?? $task->last_migrated_orbit,
            'live_output' => $latestLog ? substr($latestLog->output ?? '', -300) : '',
            'raw_output' => $latestLog ? ($latestLog->output ?? '') : '',
            'latest_log' => $latestLog ? [
                'id' => $latestLog->id,
                'status' => $latestLog->status,
                'files' => $latestLog->files_transferred,
                'total_files' => $latestLog->total_files_in_batch,
                'size' => $latestLog->human_transferred_size,
                'duration' => $latestLog->duration_formatted,
                'error_message' => $latestLog->error_message,
                'output' => $latestLog->output,
            ] : null,
        ]);
    }

    /**
     * Prueba de conectividad (Ping y SSH) hacia el host de la NAS.
     */
    public function testConnection(NasMigrationTask $task): JsonResponse
    {
        $result = $this->migrationService->testConnection($task);

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
            'message' => $result['success']
                ? "Conexión exitosa con {$task->nas_host}: Ping respondido y autenticación SSH verificada."
                : "Fallo de conexión con {$task->nas_host}. Revisa el host, puerto o credenciales.",
        ]);
    }

    /**
     * Devuelve el detalle y terminal log de una ejecución para el visor modal.
     */
    public function showLog(NasMigrationLog $log): JsonResponse
    {
        $log->load(['task', 'user']);

        return response()->json([
            'id' => $log->id,
            'task_name' => $log->task->name ?? 'N/A',
            'status' => $log->status,
            'trigger_type' => $log->trigger_type,
            'user' => $log->user->name ?? 'Sistema (Programado)',
            'started_at' => $log->started_at->format('d/m/Y H:i:s'),
            'finished_at' => $log->finished_at ? $log->finished_at->format('d/m/Y H:i:s') : '-',
            'duration' => $log->duration_formatted,
            'files' => $log->files_transferred,
            'total_files' => $log->total_files_in_batch,
            'size' => $log->human_transferred_size,
            'speed' => $log->speed ?: '-',
            'summary' => $log->summary,
            'output' => $log->output ?: 'Sin salida registrada.',
            'error_message' => $log->error_message,
        ]);
    }
}
