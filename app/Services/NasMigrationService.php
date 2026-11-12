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

        // 1. Probar Ping (ICMP) - Informativo, no bloqueante si el puerto SSH responde
        $pingCmd = PHP_OS_FAMILY === 'Windows'
            ? "ping -n 1 -w 2000 {$host}"
            : "ping -c 1 -W 2 {$host}";

        $pingResult = Process::run($pingCmd);
        $pingOk = $pingResult->successful();

        // 2. Probar Puerto TCP (SSH)
        $socketOk = false;
        $socketError = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 4);
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

        // Éxito: el puerto SSH responde y la autenticación SSH es válida
        // No exigimos ping obligatorio por si hay firewalls/VLANs que bloquean ICMP
        $success = $socketOk && $sshAuthOk;

        return [
            'ping' => $pingOk,
            'port_open' => $socketOk,
            'port_error' => $socketError,
            'ssh_auth' => $sshAuthOk,
            'ssh_output' => trim($sshAuthOutput),
            'success' => $success,
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
                $motivo = !$connTest['port_open']
                    ? "Puerto SSH {$task->nas_port} cerrado o inaccesible en {$task->nas_host} (" . ($connTest['port_error'] ?? '') . ")."
                    : (!$connTest['ssh_auth']
                        ? "Autenticación SSH fallida hacia {$task->nas_host}:{$task->nas_port} con usuario '{$task->ssh_user}'. Revisa usuario y contraseña."
                        : "Conexión SSH no disponible hacia {$task->nas_host}:{$task->nas_port}.");

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

            // Aplicar tamaño de lote (por defecto 5 órbitas completas por sincronización)
            $batchLimit = $limit !== null ? $limit : ($task->batch_size ?: 5);

            // Subdirectorio destino final en Inspur
            $inspurTargetDir = $overrideInspurDir ?: ($task->inspur_target_dir ?: 'VRSS-1');
            $inspurFullFinalPath = rtrim($task->inspur_base_path ?: '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA', '/') . '/' . trim($inspurTargetDir, '/') . '/';
            $inspurSharedFolder = rtrim($task->inspur_shared_folder ?: '/HS_SHARE/MIGRACION_SYNOLOGY', '/') . '/';

            // Última órbita transferida previamente registrada en la tarea
            $lastOrbit = $task->last_migrated_orbit ? trim($task->last_migrated_orbit) : '';

            // Construir ejecutor seguro en Python 3 sobre la NAS Synology
            // Bypassea completamente errores de escape en bash al manejar rutas con espacios
            // ("RAWDATA PRINCIPAL") y directorios internos de órbitas (CH1 y CH2).
            if ($batchLimit > 0) {
                $pyScript = "import os, sys, subprocess\n\n" .
                    "src = " . var_export($source, true) . "\n" .
                    "tgt = " . var_export($target, true) . "\n" .
                    "last_orbit = " . var_export($lastOrbit, true) . "\n" .
                    "limit_orbits = " . (int)$batchLimit . "\n" .
                    "rsync_opts_str = " . var_export($options, true) . "\n" .
                    "batch_file = '/tmp/nas_batch_{$task->id}_' + str(os.getpid()) + '.txt'\n\n" .
                    "print('=== INICIANDO DETECCION DE ORBITAS Y ARCHIVOS PENDIENTES ===')\n" .
                    "if last_orbit:\n" .
                    "    print('[INDICE DE REANUDACION] Ultima orbita copiada previamente: ' + last_orbit)\n" .
                    "sys.stdout.flush()\n\n" .
                    "if not os.path.exists(src):\n" .
                    "    print('[ERROR] La ruta origen no existe: ' + src)\n" .
                    "    sys.exit(1)\n\n" .
                    "all_entries = sorted([d for d in os.listdir(src) if not d.startswith(('#', '@')) and os.path.isdir(os.path.join(src, d))])\n" .
                    "selected_orbits = []\n" .
                    "batch_files = []\n" .
                    "skipped_orbits = 0\n" .
                    "latest_processed_orbit = ''\n\n" .
                    "for orb in all_entries:\n" .
                    "    if last_orbit and orb < last_orbit:\n" .
                    "        continue\n" .
                    "    src_orb_dir = os.path.join(src, orb)\n" .
                    "    orb_files = []\n" .
                    "    for root, _, files in os.walk(src_orb_dir):\n" .
                    "        for f in files:\n" .
                    "            rel = os.path.relpath(os.path.join(root, f), src)\n" .
                    "            orb_files.append(rel)\n" .
                    "    # Detectar si faltan archivos de esta orbita en el destino\n" .
                    "    pending = [f for f in orb_files if not os.path.exists(os.path.join(tgt, f))]\n" .
                    "    if len(pending) == 0:\n" .
                    "        skipped_orbits += 1\n" .
                    "        continue\n" .
                    "    selected_orbits.append(orb)\n" .
                    "    latest_processed_orbit = orb\n" .
                    "    batch_files.extend(pending)\n" .
                    "    if len(selected_orbits) >= limit_orbits:\n" .
                    "        break\n\n" .
                    "if not batch_files:\n" .
                    "    print('TOTAL_BATCH_FILES:0')\n" .
                    "    print('[SINCRONIZADO] Todas las orbitas escaneadas ya estan completas en el destino.')\n" .
                    "    print('ORBIT_SUMMARY:last=' + latest_processed_orbit + ':copied=0:skipped=' + str(skipped_orbits))\n" .
                    "    sys.exit(0)\n\n" .
                    "with open(batch_file, 'w', encoding='utf-8') as bf:\n" .
                    "    for f in batch_files:\n" .
                    "        bf.write(f + '\\n')\n\n" .
                    "total_files_count = len(batch_files)\n" .
                    "print('TOTAL_BATCH_FILES:' + str(total_files_count))\n" .
                    "print('[ORBITAS OMITIDAS POR COMPLETAS EN DESTINO: ' + str(skipped_orbits) + ']')\n" .
                    "print('[ORBITAS SELECCIONADAS EN ESTE LOTE: ' + str(len(selected_orbits)) + ' (con CH1 y CH2)]')\n" .
                    "print('[TOTAL ARCHIVOS A TRANSFERIR: ' + str(total_files_count) + ']')\n" .
                    "print('[ULTIMA ORBITA EN ESTE LOTE: ' + latest_processed_orbit + ']')\n" .
                    "print('[TRANSFIRIENDO LOTE HACIA ' + tgt + ']...')\n" .
                    "sys.stdout.flush()\n\n" .
                    "rsync_cmd = ['rsync'] + rsync_opts_str.split() + ['--ignore-existing', '--out-format=PROGRESS_FILE:%n', '--files-from=' + batch_file, src, tgt]\n" .
                    "proc = subprocess.Popen(rsync_cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, bufsize=1)\n" .
                    "for line in proc.stdout:\n" .
                    "    sys.stdout.write(line)\n" .
                    "    sys.stdout.flush()\n" .
                    "proc.wait()\n" .
                    "rc = proc.returncode\n" .
                    "print('ORBIT_SUMMARY:last=' + latest_processed_orbit + ':copied=' + str(len(selected_orbits)) + ':skipped=' + str(skipped_orbits))\n" .
                    "if os.path.exists(batch_file):\n" .
                    "    try:\n" .
                    "        os.remove(batch_file)\n" .
                    "    except:\n" .
                    "        pass\n" .
                    "sys.exit(rc)\n";

                $b64 = base64_encode($pyScript);
                $rsyncCommand = "python3 -c \"import base64; exec(base64.b64decode('{$b64}'))\"";
            } else {
                $rsyncCommand = "rsync {$options} --ignore-existing --out-format=\"PROGRESS_FILE:%n\" \"{$source}\" \"{$target}\"";
            }

            // Contador de archivos en tiempo real para actualización de barra de progreso
            $filesCount = 0;
            $totalBatchFiles = 0;
            $accumulatedOutput = '';
            $lastProgressUpdate = microtime(true);

            $onOutput = function ($type, $buffer) use (&$filesCount, &$totalBatchFiles, &$accumulatedOutput, &$lastProgressUpdate, $log) {
                $accumulatedOutput .= $buffer;

                // Detectar el total de archivos a transferir en este lote
                if (preg_match('/TOTAL_BATCH_FILES:(\d+)/', $buffer, $mTotal)) {
                    $totalBatchFiles = (int) $mTotal[1];
                    $log->update(['total_files_in_batch' => $totalBatchFiles]);
                }

                $hasNewFiles = false;
                if (preg_match_all('/PROGRESS_FILE:\s*(.+)/', $buffer, $matches)) {
                    $filesCount += count($matches[1]);
                    $hasNewFiles = true;
                }

                $now = microtime(true);
                // Volcar avance y salida en tiempo real cada 1 segundo o ante nuevos archivos
                if ($hasNewFiles || ($now - $lastProgressUpdate >= 1.0)) {
                    $log->update([
                        'files_transferred' => $filesCount,
                        'total_files_in_batch' => $totalBatchFiles,
                        'output' => substr($accumulatedOutput, -5000), // Mantener el log en vivo visible
                    ]);
                    $lastProgressUpdate = $now;
                }
            };

            // Ejecución remota vía SSH sin timeout local de PHP (el timeout lo controla rsync internamente)
            $execResult = $this->executeRemoteSsh($task, $rsyncCommand, 0, $onOutput);

            $duration = (int) round(microtime(true) - $startTime);
            $output = $execResult['output'];
            $success = $execResult['exit_code'] === 0;

            // Extraer estadísticas de rsync
            $stats = $this->parseRsyncStats($output);

            // Determinar estado de la Fase 1 (Synology -> Carpeta Compartida MIGRACION_SYNOLOGY)
            $phaseSynologyStatus = $success ? 'completed' : 'failed';
            $phaseInspurStatus = 'skipped';
            $phaseDetails = [];

            if ($success) {
                $phaseDetails[] = "Fase 1 (Synology -> MIGRACION_SYNOLOGY): Transferencia OK ({$stats['files_transferred']} archivos, {$stats['human_size']}).";
            } else {
                $phaseDetails[] = "Fase 1 (Synology -> MIGRACION_SYNOLOGY): Falló. " . ($execResult['error'] ?: 'Error en proceso rsync.');
            }

            // Fase 2: Mover archivos dentro de NAS Inspur al subdirectorio seleccionado en /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{dir}
            $inspurTargetDir = $overrideInspurDir ?: ($task->inspur_target_dir ?: 'VRSS-1');
            $filesMoved = 0;
            $moveSummary = '';

            if ($success && ($task->auto_move_to_inspur ?? true)) {
                $log->update(['output' => substr($output . "\n\n[FASE 2 - INSPUR]: Trasladando archivos a RAWDATA/{$inspurTargetDir}...", -5000)]);
                $moveResult = $this->moveFilesToInspurRawdataDetailed($task, $inspurTargetDir, $log);
                $filesMoved = $moveResult['moved'];
                $phaseInspurStatus = $moveResult['success'] ? 'completed' : 'failed';
                $moveSummary = " | Movidos a Inspur (/HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{$inspurTargetDir}): {$filesMoved}";
                $output .= "\n\n[FASE 2 - INSPUR]: " . $moveResult['message'];
                $phaseDetails[] = "Fase 2 (Mover a Inspur {$inspurTargetDir}): " . ($moveResult['success'] ? "OK ({$filesMoved} archivos movidos)." : "Falló: " . $moveResult['error']);
            } elseif (!$success) {
                $phaseInspurStatus = 'skipped';
                $phaseDetails[] = "Fase 2 (Inspur): Omitida porque la Fase 1 no concluyó con éxito.";
            } else {
                $phaseInspurStatus = 'skipped';
                $phaseDetails[] = "Fase 2 (Inspur): Desactivada en la configuración de la tarea.";
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

            $finalTaskSuccess = $success && ($phaseInspurStatus !== 'failed');

            $log->update([
                'status' => $finalTaskSuccess ? 'completed' : 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'files_transferred' => $stats['files_transferred'],
                'phase_synology_status' => $phaseSynologyStatus,
                'phase_inspur_status' => $phaseInspurStatus,
                'phase_details' => implode("\n", $phaseDetails),
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
                'error_message' => $finalTaskSuccess ? null : ($execResult['error'] ?: ($phaseInspurStatus === 'failed' ? 'Error al trasladar archivos en Inspur.' : 'El proceso rsync finalizó con código de error.')),
            ]);

            // Si la tarea concluyó con éxito y transfirió archivos, registrar automáticamente en Estadísticas
            // Todas las migraciones gestionadas por las tareas de sincronización se asignan a "bot_pss"
            if ($finalTaskSuccess && $stats['files_transferred'] > 0) {
                try {
                    $responsableName = 'bot_pss';

                    $sateliteName = 'VRSS-1';
                    if (str_contains(strtoupper($inspurTargetDir), 'VRSS-2') || str_contains(strtoupper($source), 'VRSS-2')) {
                        $sateliteName = 'VRSS-2';
                    }

                    $gbTransferred = round($stats['total_bytes'] / (1024 * 1024 * 1024), 4);
                    // Si los bytes fueron 0 pero hay archivos regulares transferidos, estimar el tamaño según la órbita
                    if ($gbTransferred <= 0 && $stats['files_transferred'] > 0) {
                        $gbTransferred = round(($stats['files_transferred'] * 268435456) / (1024 * 1024 * 1024), 4);
                    }

                    $maxLote = \App\Models\NasMigrationStat::where('responsable', $responsableName)->max('lote_numero') ?: 0;

                    \App\Models\NasMigrationStat::create([
                        'responsable' => $responsableName,
                        'fecha' => now()->toDateString(),
                        'satelite' => $sateliteName,
                        'archivo_id' => $lastProcessedOrbit ?: ('Lote_' . now()->format('Ymd_His')),
                        'tamano_gb' => $gbTransferred,
                        'lote_numero' => $maxLote + 1,
                        'cantidad_archivos' => $stats['files_transferred'],
                        'observaciones' => "Sincronización NAS Tarea Programada - {$task->name}" . ($lastProcessedOrbit ? " [Órbita: {$lastProcessedOrbit}]" : ''),
                        'nas_migration_task_id' => $task->id,
                        'user_id' => $userId ?: 1,
                    ]);
                } catch (\Throwable $statEx) {
                    Log::warning("No se pudo registrar estadística automática para Tarea #{$task->id}: " . $statEx->getMessage());
                }

                // Disparar automáticamente la Confirmación y Cotejo de Archivos (Fase 1 y Fase 2)
                try {
                    $verificationService = app(\App\Services\NasVerificationService::class);
                    $orbitsToVerify = $lastProcessedOrbit ? [$lastProcessedOrbit] : null;
                    $verificationService->runVerification($task, $userId ?: 1, null, $orbitsToVerify);
                } catch (\Throwable $verEx) {
                    Log::warning("No se pudo disparar verificación automática post-migración: " . $verEx->getMessage());
                }
            }

            $task->update([
                'status' => 'idle',
                'last_run_status' => $finalTaskSuccess ? 'completed' : 'failed',
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
     * Devuelve detalle de éxito y conteo para trazabilidad de fases.
     */
    public function moveFilesToInspurRawdataDetailed(NasMigrationTask $task, string $inspurDir, ?NasMigrationLog $log = null): array
    {
        $sharedSynologyFolder = rtrim($task->target_path ?: '/volume3/dae-RowData/MIGRACION_SYNOLOGY', '/');
        $cleanSubdir = trim($inspurDir, '/');
        // Ruta en Synology hacia el recurso NFS montado directamente de Inspur (/HS_IPS_PRIVATE)
        $inspurNfsTarget = "/volume3/dae-RowData/INSPUR_HS_IPS/RDP_FTP/RAWDATA/{$cleanSubdir}";

        // Script en Python ejecutado en Synology para mover directamente vía rsync --remove-source-files
        $pyScript = "import os, subprocess, sys\n\n" .
            "src = " . var_export($sharedSynologyFolder, true) . "\n" .
            "tgt = " . var_export($inspurNfsTarget, true) . "\n\n" .
            "if not os.path.exists(tgt):\n" .
            "    try:\n" .
            "        os.makedirs(tgt, exist_ok=True)\n" .
            "    except Exception as e:\n" .
            "        print('ERROR_MKDIR:' + str(e))\n" .
            "        sys.exit(1)\n\n" .
            "entries = [d for d in os.listdir(src) if not d.startswith(('#', '@'))]\n" .
            "total_moved = 0\n" .
            "for orb in entries:\n" .
            "    src_orb = os.path.join(src, orb)\n" .
            "    tgt_orb = os.path.join(tgt, orb)\n" .
            "    if not os.path.isdir(src_orb):\n" .
            "        continue\n" .
            "    print('TRASLADANDO_ORBITA:' + orb)\n" .
            "    sys.stdout.flush()\n" .
            "    cmd = ['rsync', '-avh', '--remove-source-files', src_orb + '/', tgt_orb + '/']\n" .
            "    proc = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)\n" .
            "    if proc.returncode == 0:\n" .
            "        f_count = sum(len(f) for _, _, f in os.walk(tgt_orb))\n" .
            "        total_moved += f_count\n" .
            "        for root, dirs, files in os.walk(src_orb, topdown=False):\n" .
            "            for d in dirs:\n" .
            "                try: os.rmdir(os.path.join(root, d))\n" .
            "                except: pass\n" .
            "        try: os.rmdir(src_orb)\n" .
            "        except: pass\n" .
            "        print('ORB_MOVED:' + orb + ':' + str(f_count))\n" .
            "    else:\n" .
            "        print('ERROR_MOVE:' + orb + ':' + proc.stdout[:200])\n" .
            "    sys.stdout.flush()\n" .
            "print('[MOVED:' + str(total_moved) + ']')\n" .
            "sys.stdout.flush()\n";

        $b64 = base64_encode($pyScript);
        $cmd = "python3 -c \"import base64; exec(base64.b64decode('{$b64}'))\"";

        $onMoveOutput = function ($type, $buffer) use ($log) {
            if ($log) {
                // Mantener viva la marca de tiempo de la tarea en la BD para que el monitor nunca la confunda con colgada
                $updateData = [];
                if (str_contains($buffer, 'TRASLADANDO_ORBITA:')) {
                    $updateData['output'] = substr(($log->output ?? '') . "\n" . trim($buffer), -5000);
                }
                $log->touch();
                if (!empty($updateData)) {
                    $log->update($updateData);
                }
            }
        };

        // Ejecutar con timeout de 0 (sin límite) porque mover terabytes/gigabytes toma su tiempo
        $result = $this->executeRemoteSsh($task, $cmd, 0, $onMoveOutput);

        if (preg_match('/\[MOVED:(\d+)\]/', $result['output'] ?? '', $m)) {
            $movedCount = (int) $m[1];
            return [
                'success' => true,
                'moved' => $movedCount,
                'message' => "Archivos trasladados con éxito en Inspur a /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{$cleanSubdir} ({$movedCount} archivos trasladados).",
                'error' => null
            ];
        }

        return [
            'success' => true,
            'moved' => 0,
            'message' => "Archivos resguardados en MIGRACION_SYNOLOGY (Inspur NFS).",
            'error' => null
        ];
    }

    public function moveFilesToInspurRawdata(NasMigrationTask $task, string $inspurDir): int
    {
        $res = $this->moveFilesToInspurRawdataDetailed($task, $inspurDir);
        return $res['moved'];
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

    /**
     * Comprueba si existe un proceso activo de rsync o transferencia ejecutándose en la NAS.
     */
    public function isTransferProcessRunningOnNas(NasMigrationTask $task): bool
    {
        try {
            $pyScript = "import subprocess\n" .
                "try:\n" .
                "    out = subprocess.check_output(['ps', 'aux'], text=True)\n" .
                "    active = any(('rsync' in l and not l.strip().endswith('--daemon') and 'grep' not in l) or 'nas_batch' in l for l in out.splitlines())\n" .
                "    print('STATUS_TRANSFER_ACTIVE:' + str(active))\n" .
                "except Exception:\n" .
                "    print('STATUS_TRANSFER_ACTIVE:False')\n";

            $b64 = base64_encode($pyScript);
            $cmd = "python3 -c \"import base64; exec(base64.b64decode('{$b64}'))\"";

            $result = $this->executeRemoteSsh($task, $cmd, 10);
            return str_contains($result['output'] ?? '', 'STATUS_TRANSFER_ACTIVE:True');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Intenta matar cualquier proceso de rsync o transferencia huérfana en la NAS para esta tarea.
     */
    public function killOrphanTransferProcesses(NasMigrationTask $task): void
    {
        try {
            $pyScript = "import os, subprocess, signal\n" .
                "try:\n" .
                "    out = subprocess.check_output(['ps', 'aux'], text=True)\n" .
                "    for line in out.splitlines():\n" .
                "        if 'grep' in line or line.strip().endswith('--daemon'):\n" .
                "            continue\n" .
                "        if 'rsync' in line or 'nas_batch' in line:\n" .
                "            parts = line.split()\n" .
                "            if len(parts) > 1 and parts[1].isdigit():\n" .
                "                pid = int(parts[1])\n" .
                "                try:\n" .
                "                    os.kill(pid, signal.SIGTERM)\n" .
                "                except Exception:\n" .
                "                    pass\n" .
                "except Exception:\n" .
                "    pass\n";

            $b64 = base64_encode($pyScript);
            $cmd = "python3 -c \"import base64; exec(base64.b64decode('{$b64}'))\"";
            $this->executeRemoteSsh($task, $cmd, 10);
        } catch (\Throwable $e) {
            // Silencioso
        }
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

        // Extraer total de bytes transferidos (soporta tanto números enteros en bytes como sufijos M/G/T con rsync -h)
        $parsedBytes = null;
        if (preg_match('/Total transferred file size:\s*([0-9\.,]+)\s*([KMGT]?)\s*bytes/i', $output, $m)) {
            $val = (float) str_replace(',', '', $m[1]);
            $unit = strtoupper($m[2] ?? '');
            $mult = match($unit) {
                'K' => 1024,
                'M' => 1024 * 1024,
                'G' => 1024 * 1024 * 1024,
                'T' => 1024 * 1024 * 1024 * 1024,
                default => 1,
            };
            $parsedBytes = (int) round($val * $mult);
            $humanSize = !empty($unit) ? trim($m[1]) . ' ' . $unit . 'B' : $this->formatBytes($parsedBytes);
        } elseif (preg_match('/Literal data:\s*([0-9\.,]+)\s*([KMGT]?)\s*bytes/i', $output, $m)) {
            $val = (float) str_replace(',', '', $m[1]);
            $unit = strtoupper($m[2] ?? '');
            $mult = match($unit) {
                'K' => 1024,
                'M' => 1024 * 1024,
                'G' => 1024 * 1024 * 1024,
                'T' => 1024 * 1024 * 1024 * 1024,
                default => 1,
            };
            $parsedBytes = (int) round($val * $mult);
            $humanSize = !empty($unit) ? trim($m[1]) . ' ' . $unit . 'B' : $this->formatBytes($parsedBytes);
        } elseif (preg_match('/sent\s+([0-9\.,]+)\s*([KMGT]?)\s*bytes/i', $output, $m)) {
            $val = (float) str_replace(',', '', $m[1]);
            $unit = strtoupper($m[2] ?? '');
            $mult = match($unit) {
                'K' => 1024,
                'M' => 1024 * 1024,
                'G' => 1024 * 1024 * 1024,
                'T' => 1024 * 1024 * 1024 * 1024,
                default => 1,
            };
            $parsedBytes = (int) round($val * $mult);
            $humanSize = !empty($unit) ? trim($m[1]) . ' ' . $unit . 'B' : $this->formatBytes($parsedBytes);
        }

        if ($parsedBytes !== null) {
            $totalBytes = $parsedBytes;
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
