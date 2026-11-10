<?php

namespace App\Services;

use App\Models\NasMigrationLog;
use App\Models\NasMigrationTask;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class NasMigrationService
{
    /**
     * Lista directorios de manera recursiva o en vivo desde la NAS Synology.
     * Si la NAS está accesible por SSH, ejecuta 'find' para leer los subdirectorios reales.
     * Si no está accesible, devuelve el árbol estático completo de los volúmenes configurados.
     */
    public function listSynologyDirectories(NasMigrationTask $task, ?string $baseVolume = null): array
    {
        $defaultDirectories = NasMigrationTask::SYNOLOGY_AVAILABLE_SOURCES;

        // Comprobar socket rápido antes de intentar SSH
        $port = $task->nas_port ?: 22;
        $fp = @fsockopen($task->nas_host, $port, $errno, $errstr, 2);
        if (!$fp) {
            return $defaultDirectories;
        }
        fclose($fp);

        // Si el puerto responde, intentar listar por SSH
        $pathsToScan = $baseVolume
            ? escapeshellarg(rtrim($baseVolume, '/'))
            : "'/volume2/dae-Volumen2_IPS' '/volume4/dae-Volumen4_Respaldo_IPS'";

        // Comando que busca directorios hasta profundidad 4 excluyendo @eaDir, #recycle y carpetas del sistema
        // y filtrando subcarpetas de órbitas individuales (patrón de 6 dígitos con guión bajo) y canales CH1/CH2
        $findCmd = "find {$pathsToScan} -maxdepth 4 -type d ! -path '*/#recycle*' ! -path '*/@eaDir*' ! -path '*/.*' 2>/dev/null | grep -vE '/[0-9]{6}_|/CH[0-9]' | sort";

        try {
            $result = $this->executeRemoteSsh($task, $findCmd, 10);
            if ($result['exit_code'] === 0 && !empty(trim($result['output']))) {
                $lines = explode("\n", trim($result['output']));
                $dirs = [];
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $dirs[] = rtrim($trimmed, '/') . '/';
                    }
                }
                if (!empty($dirs)) {
                    return array_values(array_unique($dirs));
                }
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudieron listar directorios en vivo vía SSH: " . $e->getMessage());
        }

        return $defaultDirectories;
    }

    /**
     * Prueba la conectividad ICMP (Ping) y SSH con el host de la NAS.
     */
    public function testConnection(NasMigrationTask $task): array
    {
        $host = $task->nas_host;
        $port = $task->nas_port ?: 22;

        // 1. Probar Ping (ICMP)
        $pingCmd = PHP_OS_FAMILY === 'Windows'
            ? "ping -n 1 -w 2000 {$host}"
            : "ping -c 1 -W 2 {$host}";

        $pingResult = Process::run($pingCmd);
        $pingOk = $pingResult->successful();

        // 2. Probar Puerto TCP (SSH)
        $socketOk = false;
        $socketError = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 3);
        if ($fp) {
            $socketOk = true;
            fclose($fp);
        } else {
            $socketError = "Puerto {$port} no responde: {$errstr} ({$errno})";
        }

        // 3. Probar Autenticación SSH si el socket responde
        $sshAuthOk = false;
        $sshAuthOutput = '';

        if ($socketOk) {
            $testCmd = 'echo "CONEXION_OK"';
            $sshRun = $this->executeRemoteSsh($task, $testCmd, 15);
            $sshAuthOk = str_contains($sshRun['output'], 'CONEXION_OK');
            $sshAuthOutput = $sshRun['output'];
        }

        return [
            'ping' => $pingOk,
            'port_open' => $socketOk,
            'port_error' => $socketError,
            'ssh_auth' => $sshAuthOk,
            'ssh_output' => trim($sshAuthOutput),
            'success' => $pingOk && $socketOk && $sshAuthOk,
        ];
    }

    /**
     * Ejecuta una sincronización de migración completa o por lotes (ej. 500 archivos).
     * Soporta recibir un $logId existente (creado previamente por el orquestador).
     */
    public function runSync(
        NasMigrationTask $task,
        string $triggerType = 'manual',
        ?int $userId = null,
        ?int $logId = null,
        ?int $limit = null,
        ?string $overrideSource = null,
        ?string $overrideInspurDir = null
    ): NasMigrationLog {
        if ($logId) {
            $log = NasMigrationLog::find($logId);
            if (!$log) {
                $log = $this->createInitialLog($task, $triggerType, $userId);
            }
        } else {
            $log = $this->createInitialLog($task, $triggerType, $userId);
        }

        $task->update([
            'status' => 'running',
            'last_run_at' => now(),
        ]);

        $startTime = microtime(true);

        try {
            // Pre-verificación de conectividad antes de iniciar la migración real
            $connTest = $this->testConnection($task);
            if (!$connTest['success']) {
                $motivo = !$connTest['ping']
                    ? "Host inalcanzable: {$task->nas_host} no responde al ping (ICMP)."
                    : (!$connTest['port_open']
                        ? "Puerto SSH {$task->nas_port} cerrado o inaccesible en {$task->nas_host}."
                        : "Autenticación SSH fallida hacia {$task->nas_host} (revisa usuario y credenciales).");

                $duration = (int) round(microtime(true) - $startTime);

                $log->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'duration_seconds' => $duration,
                    'files_transferred' => 0,
                    'error_message' => "Fallo de conexión previo: {$motivo}",
                    'output' => "Prueba de conectividad fallida:\n" . json_encode($connTest, JSON_PRETTY_PRINT),
                ]);

                $task->update([
                    'status' => 'idle',
                    'last_run_status' => 'failed',
                ]);

                return $log;
            }

            // Origen configurable en Synology y destino en carpeta compartida MIGRACION_SYNOLOGY (dae-RowData > MIGRACION_SYNOLOGY)
            $source = rtrim($overrideSource ?: $task->source_path, '/') . '/';
            $target = rtrim($task->target_path ?: '/volume1/dae-RowData/MIGRACION_SYNOLOGY/', '/') . '/';
            $options = trim($task->rsync_options ?: '-avh --stats --progress');

            // Asegurar parámetros anti-cuelgues por defecto si no están presentes
            if (!str_contains($options, '--timeout')) {
                $options .= ' --timeout=300'; // Cancela si una conexión se congela más de 5 minutos
            }
            if (!str_contains($options, '--partial')) {
                $options .= ' --partial'; // Permite reanudar transferencias cortadas sin reiniciar desde 0
            }
            if ($task->delete_after_sync && !str_contains($options, '--delete')) {
                $options .= ' --delete';
            }

            // Aplicar tamaño de lote (por defecto 500 archivos por clic / sincronización)
            $batchLimit = $limit !== null ? $limit : ($task->batch_size ?: 500);

            // Subdirectorio destino final en Inspur
            $inspurTargetDir = $overrideInspurDir ?: ($task->inspur_target_dir ?: 'VRSS-1');
            $inspurFullFinalPath = rtrim($task->inspur_base_path ?: '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA', '/') . '/' . trim($inspurTargetDir, '/') . '/';
            $inspurSharedFolder = rtrim($task->inspur_shared_folder ?: '/HS_SHARE/MIGRACION_SYNOLOGY', '/') . '/';

            // Última órbita transferida previamente registrada en la tarea
            $lastOrbit = $task->last_migrated_orbit ? trim($task->last_migrated_orbit) : '';

            // Script de sincronización inteligente:
            // 1. Identifica órbitas (carpetas 6 dígitos o subdirectorios) en el origen.
            // 2. Si existe un punto de partida ($lastOrbit), filtra para procesar únicamente las carpetas mayores a ella.
            // 3. Verifica en el destino Inspur y en la carpeta intermedia para ignorar órbitas ya copiadas.
            // 4. Selecciona los archivos de las órbitas pendientes hasta alcanzar el lote.
            // 5. Registra la última órbita procesada para continuar exactamente desde allí.
            if ($batchLimit > 0) {
                $rsyncCommand = "sh -c '" .
                    "BATCH_FILE=\"/tmp/nas_batch_{$task->id}_\$\$.txt\"; " .
                    "ORBITS_FILE=\"/tmp/nas_orbits_{$task->id}_\$\$.txt\"; " .
                    "SRC=\"{$source}\"; " .
                    "TGT=\"{$target}\"; " .
                    "INSPUR_FINAL=\"{$inspurFullFinalPath}\"; " .
                    "INSPUR_SHARED=\"{$inspurSharedFolder}\"; " .
                    "LAST_ORBIT=\"{$lastOrbit}\"; " .
                    "echo \"=== INICIANDO DETECCIÓN DE ÓRBITAS Y ARCHIVOS PENDIENTES ===\"; " .
                    "if [ -n \"\$LAST_ORBIT\" ]; then " .
                    "  echo \"[ÍNDICE DE REANUDACIÓN] Última órbita previamente copiada: \$LAST_ORBIT\"; " .
                    "fi; " .
                    // Obtener lista ordenada de carpetas de primer nivel (órbitas) en origen
                    "find \"\$SRC\" -mindepth 1 -maxdepth 1 -type d ! -name \"#recycle*\" ! -name \"@eaDir*\" -printf \"%f\\n\" 2>/dev/null | sort > \"\$ORBITS_FILE\"; " .
                    "PENDING_COUNT=0; " .
                    "SKIPPED_ORBITS=0; " .
                    "COPIED_ORBITS=0; " .
                    "LATEST_PROCESSED_ORBIT=\"\"; " .
                    "> \"\$BATCH_FILE\"; " .
                    "while IFS= read -r ORB; do " .
                    "  [ -z \"\$ORB\" ] && continue; " .
                    // Si hay un índice de última órbita, saltar las anteriores o iguales en orden alfabético/numérico
                    "  if [ -n \"\$LAST_ORBIT\" ] && [ \"\$ORB\" \\< \"\$LAST_ORBIT\" -o \"\$ORB\" = \"\$LAST_ORBIT\" ]; then " .
                    "    continue; " .
                    "  fi; " .
                    // Verificar si la órbita ya existe en el destino de Inspur o en la carpeta intermedia
                    "  if [ -d \"\${INSPUR_FINAL}\${ORB}\" ] || [ -d \"\${INSPUR_SHARED}\${ORB}\" ] || [ -d \"\${TGT}\${ORB}\" ]; then " .
                    "    SKIPPED_ORBITS=\$((SKIPPED_ORBITS + 1)); " .
                    "    continue; " .
                    "  fi; " .
                    // Si no existe, incluir los archivos de esta órbita en el lote
                    "  COPIED_ORBITS=\$((COPIED_ORBITS + 1)); " .
                    "  LATEST_PROCESSED_ORBIT=\"\$ORB\"; " .
                    "  find \"\$SRC\$ORB\" -type f -printf \"%P\\n\" 2>/dev/null | sed \"s|^|\$ORB/|\" >> \"\$BATCH_FILE\"; " .
                    "  CURR_FILES=\$(wc -l < \"\$BATCH_FILE\"); " .
                    "  if [ \"\$CURR_FILES\" -ge {$batchLimit} ]; then " .
                    "    break; " .
                    "  fi; " .
                    "done < \"\$ORBITS_FILE\"; " .
                    "rm -f \"\$ORBITS_FILE\"; " .
                    // Si se encontraron archivos por órbitas, recortar al límite del lote
                    "if [ -s \"\$BATCH_FILE\" ]; then " .
                    "  head -n {$batchLimit} \"\$BATCH_FILE\" > \"\${BATCH_FILE}.tmp\" && mv \"\${BATCH_FILE}.tmp\" \"\$BATCH_FILE\"; " .
                    "  TOTAL_IN_BATCH=\$(wc -l < \"\$BATCH_FILE\"); " .
                    "  echo \"[ÓRBITAS IGNORADAS POR DUPLICADO EN DESTINO: \$SKIPPED_ORBITS]\"; " .
                    "  echo \"[ÓRBITAS NUEVAS SELECCIONADAS: \$COPIED_ORBITS]\"; " .
                    "  echo \"[ÚLTIMA ÓRBITA EN ESTE LOTE: \$LATEST_PROCESSED_ORBIT]\"; " .
                    "  echo \"[LOTE DE \$TOTAL_IN_BATCH ARCHIVOS] Transfiriendo a carpeta intermedia compartida {\$TGT}...\"; " .
                    "  rsync {$options} --ignore-existing --out-format=\"PROGRESS_FILE:%n\" --files-from=\"\$BATCH_FILE\" \"\$SRC\" \"\$TGT\"; " .
                    "  RC=\$?; " .
                    "  echo \"ORBIT_SUMMARY:last=\$LATEST_PROCESSED_ORBIT:copied=\$COPIED_ORBITS:skipped=\$SKIPPED_ORBITS\"; " .
                    "  rm -f \"\$BATCH_FILE\"; exit \$RC; " .
                    "else " .
                    // Fallback directo por rsync --ignore-existing si no hay estructura por subcarpetas
                    "  echo \"[SINCRONIZACIÓN] Verificando archivos individuales omitiendo duplicados...\"; " .
                    "  rsync -r -n --ignore-existing --out-format=\"%n\" \"\$SRC\" \"\$TGT\" | grep -v \"/\$\" | grep -v \"^\$\" | head -n {$batchLimit} > \"\$BATCH_FILE\"; " .
                    "  if [ -s \"\$BATCH_FILE\" ]; then " .
                    "    rsync {$options} --ignore-existing --out-format=\"PROGRESS_FILE:%n\" --files-from=\"\$BATCH_FILE\" \"\$SRC\" \"\$TGT\"; " .
                    "    RC=\$?; rm -f \"\$BATCH_FILE\"; exit \$RC; " .
                    "  else " .
                    "    echo \"[SINCRONIZADO] No hay órbitas o archivos pendientes nuevos en este lote de {$batchLimit} (todos existen en destino o fueron omitidos).\"; " .
                    "    rm -f \"\$BATCH_FILE\"; exit 0; " .
                    "  fi; " .
                    "fi'";
            } else {
                $rsyncCommand = "rsync {$options} --ignore-existing --out-format=\"PROGRESS_FILE:%n\" \"{$source}\" \"{$target}\"";
            }

            // Contador de archivos en tiempo real para actualización de barra de progreso
            $filesCount = 0;
            $lastProgressUpdate = microtime(true);

            $onOutput = function ($type, $buffer) use (&$filesCount, &$lastProgressUpdate, $log, $batchLimit) {
                if (preg_match_all('/PROGRESS_FILE:\s*(.+)/', $buffer, $matches)) {
                    $filesCount += count($matches[1]);
                    $now = microtime(true);
                    if ($now - $lastProgressUpdate >= 0.5 || $filesCount >= $batchLimit) {
                        $log->update(['files_transferred' => min($batchLimit, $filesCount)]);
                        $lastProgressUpdate = $now;
                    }
                }
            };

            // Ejecución remota vía SSH sin timeout local de PHP (el timeout lo controla rsync internamente)
            $execResult = $this->executeRemoteSsh($task, $rsyncCommand, 0, $onOutput);

            $duration = (int) round(microtime(true) - $startTime);
            $output = $execResult['output'];
            $success = $execResult['exit_code'] === 0;

            // Extraer estadísticas de rsync
            $stats = $this->parseRsyncStats($output);

            // Fase 2: Mover archivos dentro de NAS Inspur al subdirectorio seleccionado en /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{dir}
            $inspurTargetDir = $overrideInspurDir ?: ($task->inspur_target_dir ?: 'VRSS-1');
            $filesMoved = 0;
            $moveSummary = '';

            if ($success && ($task->auto_move_to_inspur ?? true)) {
                $filesMoved = $this->moveFilesToInspurRawdata($task, $inspurTargetDir);
                $moveSummary = " | Movidos a Inspur (/HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{$inspurTargetDir}): {$filesMoved}";
                $output .= "\n\n[FASE 2 - INSPUR]: Archivos trasladados a /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{$inspurTargetDir}/ ({$filesMoved} archivos movidos exitosamente).";
            }

            // Parsear datos de órbitas procesadas en la salida
            $lastProcessedOrbit = null;
            $orbitsCopied = 0;
            $orbitsSkipped = 0;
            if (preg_match('/ORBIT_SUMMARY:last=([^:]*):copied=([0-9]+):skipped=([0-9]+)/', $output, $mOrb)) {
                $lastProcessedOrbit = !empty(trim($mOrb[1])) ? trim($mOrb[1]) : null;
                $orbitsCopied = (int) $mOrb[2];
                $orbitsSkipped = (int) $mOrb[3];
            }

            // Si se transfirieron archivos con éxito y se detectó una última órbita en el lote, actualizar el índice de la tarea
            if ($success && $lastProcessedOrbit) {
                $task->update([
                    'last_migrated_orbit' => $lastProcessedOrbit,
                    'last_migrated_orbit_at' => now(),
                ]);
            }

            $orbitSummaryText = '';
            if ($orbitsCopied > 0 || $orbitsSkipped > 0 || $lastProcessedOrbit) {
                $orbitSummaryText = " | Órbitas transferidas: {$orbitsCopied}, Omitidas (ya existían): {$orbitsSkipped}" .
                    ($lastProcessedOrbit ? " (Última: {$lastProcessedOrbit})" : '');
            }

            $log->update([
                'status' => $success ? 'completed' : 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'files_transferred' => $stats['files_transferred'],
                'source_path_used' => $source,
                'inspur_target_dir_used' => $inspurTargetDir,
                'files_moved' => $filesMoved,
                'last_migrated_orbit_in_batch' => $lastProcessedOrbit,
                'orbits_transferred' => $orbitsCopied,
                'orbits_skipped' => $orbitsSkipped,
                'total_bytes_transferred' => $stats['total_bytes'],
                'human_transferred_size' => $stats['human_size'],
                'speed' => $stats['speed'],
                'summary' => $stats['summary'] . $moveSummary . $orbitSummaryText,
                'output' => $output,
                'error_message' => $success ? null : ($execResult['error'] ?: 'El proceso rsync finalizó con código de error.'),
            ]);

            $task->update([
                'status' => 'idle',
                'last_run_status' => $success ? 'completed' : 'failed',
            ]);
        } catch (\Throwable $e) {
            $duration = (int) round(microtime(true) - $startTime);

            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage(),
                'output' => $e->getTraceAsString(),
            ]);

            $task->update([
                'status' => 'idle',
                'last_run_status' => 'failed',
            ]);

            Log::error("Error en migración NAS [Task #{$task->id}]: " . $e->getMessage());
        }

        return $log;
    }

    /**
     * Mueve los archivos copiados en la carpeta compartida MIGRACION_SYNOLOGY
     * hacia la ubicación final en NAS INSPUR: /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{inspurDir}/
     */
    public function moveFilesToInspurRawdata(NasMigrationTask $task, string $inspurDir): int
    {
        $base = rtrim($task->inspur_base_path ?: '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA', '/');
        $cleanSubdir = trim($inspurDir, '/');
        $targetInspurPath = "{$base}/{$cleanSubdir}";

        $sharedInspurFolder = rtrim($task->inspur_shared_folder ?: '/HS_SHARE/MIGRACION_SYNOLOGY', '/');
        $sharedSynologyFolder = rtrim($task->target_path ?: '/volume1/dae-RowData/MIGRACION_SYNOLOGY', '/');

        // Comando que crea la carpeta de destino si no existe y traslada los archivos
        $moveCmd = "sh -c '" .
            "mkdir -p \"{$targetInspurPath}\"; " .
            "if [ -d \"{$sharedInspurFolder}\" ]; then " .
            "SRC_DIR=\"{$sharedInspurFolder}\"; " .
            "else " .
            "SRC_DIR=\"{$sharedSynologyFolder}\"; " .
            "fi; " .
            "COUNT=$(find \"\$SRC_DIR\" -mindepth 1 -maxdepth 1 2>/dev/null | wc -l); " .
            "if [ \"\$COUNT\" -gt 0 ]; then " .
            "find \"\$SRC_DIR\" -mindepth 1 -maxdepth 1 -exec mv -t \"{$targetInspurPath}/\" {} + 2>/dev/null || true; " .
            "echo \"[MOVED:\$COUNT]\"; " .
            "else " .
            "echo \"[MOVED:0]\"; " .
            "fi'";

        // Si Inspur tiene un host SSH propio definido diferente a Synology, ejecutar en Inspur
        if (!empty($task->inspur_host) && $task->inspur_host !== $task->nas_host) {
            $result = $this->executeInspurSsh($task, $moveCmd);
        } else {
            $result = $this->executeRemoteSsh($task, $moveCmd);
        }

        if (preg_match('/\[MOVED:(\d+)\]/', $result['output'] ?? '', $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Ejecución remota vía SSH directamente en la NAS INSPUR.
     */
    public function executeInspurSsh(NasMigrationTask $task, string $remoteCommand, int $timeout = 60): array
    {
        $host = $task->inspur_host ?: '192.168.10.180';
        $port = $task->inspur_port ?: 22;
        $user = $task->inspur_user ?: 'root';
        $password = $task->inspur_password;

        $plinkPath = 'C:\\Program Files\\PuTTY\\plink.exe';
        if (PHP_OS_FAMILY === 'Windows' && file_exists($plinkPath) && !empty($password)) {
            $cmd = [
                $plinkPath,
                '-ssh',
                '-batch',
                '-P',
                (string)$port,
                '-l',
                $user,
                '-pw',
                (string)$password,
                $host,
                $remoteCommand,
            ];
            $proc = Process::timeout($timeout)->run($cmd);
            return [
                'exit_code' => $proc->exitCode(),
                'output' => $proc->output(),
                'error' => $proc->errorOutput(),
            ];
        }

        $cmd = [
            'ssh',
            '-p',
            (string)$port,
            '-o',
            'StrictHostKeyChecking=no',
            '-o',
            'ConnectTimeout=5',
            '-o',
            'BatchMode=yes',
            "{$user}@{$host}",
            $remoteCommand,
        ];
        $proc = Process::timeout($timeout)->run($cmd);
        return [
            'exit_code' => $proc->exitCode(),
            'output' => $proc->output(),
            'error' => $proc->errorOutput(),
        ];
    }

    protected function createInitialLog(NasMigrationTask $task, string $triggerType, ?int $userId): NasMigrationLog
    {
        return NasMigrationLog::create([
            'nas_migration_task_id' => $task->id,
            'status' => 'running',
            'trigger_type' => $triggerType,
            'user_id' => $userId,
            'started_at' => now(),
        ]);
    }

    /**
     * Ejecuta un comando en el host SSH de la NAS utilizando las herramientas nativas del sistema.
     */
    public function executeRemoteSsh(NasMigrationTask $task, string $remoteCommand, int $timeout = 60, ?callable $onOutput = null): array
    {
        $host = $task->nas_host;
        $port = $task->nas_port ?: 22;
        $user = $task->ssh_user ?: 'admin';
        $password = $task->ssh_password;
        $keyPath = $task->ssh_key_path;

        // Si tenemos clave privada
        if ($task->ssh_auth_type === 'key' && !empty($keyPath) && file_exists($keyPath)) {
            $cmd = [
                'ssh',
                '-i',
                $keyPath,
                '-p',
                (string)$port,
                '-o',
                'StrictHostKeyChecking=no',
                '-o',
                'UserKnownHostsFile=/dev/null',
                "{$user}@{$host}",
                $remoteCommand
            ];

            $proc = Process::timeout($timeout)->run($cmd, function ($type, $buffer) use ($onOutput) {
                if ($onOutput) {
                    $onOutput($type, $buffer);
                }
            });
            return [
                'exit_code' => $proc->exitCode(),
                'output' => $proc->output(),
                'error' => $proc->errorOutput(),
            ];
        }

        // Si es por contraseña en Windows, buscar plink.exe (PuTTY CLI)
        $plinkPath = 'C:\\Program Files\\PuTTY\\plink.exe';
        if (PHP_OS_FAMILY === 'Windows' && file_exists($plinkPath)) {
            $cmd = [
                $plinkPath,
                '-ssh',
                '-batch',
                '-P',
                (string)$port,
                '-l',
                $user,
                '-pw',
                (string)$password,
                $host,
                $remoteCommand,
            ];

            $proc = Process::timeout($timeout)->run($cmd, function ($type, $buffer) use ($onOutput) {
                if ($onOutput) {
                    $onOutput($type, $buffer);
                }
            });
            return [
                'exit_code' => $proc->exitCode(),
                'output' => $proc->output(),
                'error' => $proc->errorOutput(),
            ];
        }

        // Si no hay plink o en sistemas Linux con sshpass
        if (PHP_OS_FAMILY !== 'Windows') {
            $cmd = "sshpass -p '{$password}' ssh -p {$port} -o StrictHostKeyChecking=no {$user}@{$host} '{$remoteCommand}'";
            $proc = Process::timeout($timeout)->run($cmd, function ($type, $buffer) use ($onOutput) {
                if ($onOutput) {
                    $onOutput($type, $buffer);
                }
            });
            return [
                'exit_code' => $proc->exitCode(),
                'output' => $proc->output(),
                'error' => $proc->errorOutput(),
            ];
        }

        // Fallback: Ejecución directa con ssh (usará llaves por defecto o agente local)
        $cmd = [
            'ssh',
            '-p',
            (string)$port,
            '-o',
            'StrictHostKeyChecking=no',
            '-o',
            'ConnectTimeout=5',
            '-o',
            'BatchMode=yes',
            "{$user}@{$host}",
            $remoteCommand,
        ];

        $proc = Process::timeout($timeout)->run($cmd, function ($type, $buffer) use ($onOutput) {
            if ($onOutput) {
                $onOutput($type, $buffer);
            }
        });
        return [
            'exit_code' => $proc->exitCode(),
            'output' => $proc->output(),
            'error' => $proc->errorOutput(),
        ];
    }

    /**
     * Parsea la salida de texto de rsync --stats para extraer métricas claras.
     */
    protected function parseRsyncStats(string $output): array
    {
        $filesTransferred = 0;
        $totalBytes = 0;
        $speed = null;
        $humanSize = '0 B';

        // Extraer número de archivos transferidos
        if (preg_match('/Number of regular files transferred:\s*([0-9,]+)/i', $output, $m)) {
            $filesTransferred = (int) str_replace(',', '', $m[1]);
        } elseif (preg_match('/Number of files transferred:\s*([0-9,]+)/i', $output, $m)) {
            $filesTransferred = (int) str_replace(',', '', $m[1]);
        }

        // Extraer total de bytes transferidos
        if (preg_match('/Total transferred file size:\s*([0-9,]+)\s*bytes/i', $output, $m)) {
            $totalBytes = (int) str_replace(',', '', $m[1]);
            $humanSize = $this->formatBytes($totalBytes);
        } elseif (preg_match('/Literal data:\s*([0-9,]+)\s*bytes/i', $output, $m)) {
            $totalBytes = (int) str_replace(',', '', $m[1]);
            $humanSize = $this->formatBytes($totalBytes);
        }

        // Extraer velocidad si está en la salida
        if (preg_match('/([0-9\.]+\s*[KMGT]?B\/s)/i', $output, $m)) {
            $speed = $m[1];
        }

        // Extraer bloque de resumen
        $summary = '';
        if (preg_match('/(Number of files:.*?speedup is .*?)\n/s', $output, $m)) {
            $summary = trim($m[1]);
        }

        return [
            'files_transferred' => $filesTransferred,
            'total_bytes' => $totalBytes,
            'human_size' => $humanSize,
            'speed' => $speed,
            'summary' => $summary ?: (substr($output, -500)),
        ];
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
