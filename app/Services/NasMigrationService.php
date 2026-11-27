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

        // 3. Probar Autenticación SSH y Estado de Montajes NFS hacia Inspur si el socket responde
        $sshAuthOk = false;
        $sshAuthOutput = '';
        $nfsSharedMounted = false;
        $nfsSharedSource = null;
        $nfsInspurMounted = false;
        $nfsInspurSource = null;

        $sharedMountPoint = rtrim($task->target_path ?: '/volume3/dae-RowData/MIGRACION_SYNOLOGY', '/');
        $inspurMountPoint = '/volume3/dae-RowData/INSPUR_HS_IPS';
        $requireInspurMount = (bool) ($task->auto_move_to_inspur ?? true);
        $pendingLocalOrbits = 0;
        $pendingSharedOrbits = 0;

        if ($socketOk) {
            $mountCheckPy = "import os\n" .
                "def check_nfs_exact(mp):\n" .
                "    mp = mp.rstrip('/')\n" .
                "    try:\n" .
                "        with open('/proc/mounts', 'r') as f:\n" .
                "            for line in f:\n" .
                "                p = line.split()\n" .
                "                if len(p) >= 3 and p[1].rstrip('/') == mp and p[2].startswith('nfs'):\n" .
                "                    return True, p[0], p[1].rstrip('/')\n" .
                "    except Exception:\n" .
                "        pass\n" .
                "    return False, '', mp\n" .
                "def resolve_inspur_nfs():\n" .
                "    candidates = ['/volume3/dae-RowData/INSPUR_HS_IPS', '/volume3/dae-RowData/HS_IPS_PRIVATE']\n" .
                "    try:\n" .
                "        with open('/proc/mounts', 'r') as f:\n" .
                "            lines = [l.split() for l in f if len(l.split()) >= 3]\n" .
                "        for c in candidates:\n" .
                "            for p in lines:\n" .
                "                if p[1].rstrip('/') == c and p[2].startswith('nfs'):\n" .
                "                    return True, p[0], p[1].rstrip('/')\n" .
                "        for p in lines:\n" .
                "            if p[2].startswith('nfs') and p[0].rstrip('/').endswith(':/HS_IPS_PRIVATE'):\n" .
                "                return True, p[0], p[1].rstrip('/')\n" .
                "    except Exception:\n" .
                "        pass\n" .
                "    return False, '', candidates[0]\n" .
                "s_ok, s_src, s_mp = check_nfs_exact(" . var_export($sharedMountPoint, true) . ")\n" .
                "i_ok, i_src, i_mp = resolve_inspur_nfs()\n" .
                "pend_local = 0\n" .
                "local_dir = '/volume3/dae-RowData/INSPUR_HS_IPS_LOCAL_PENDIENTE/RDP_FTP/RAWDATA'\n" .
                "if os.path.isdir(local_dir):\n" .
                "    for sub in os.listdir(local_dir):\n" .
                "        sp = os.path.join(local_dir, sub)\n" .
                "        if os.path.isdir(sp):\n" .
                "            pend_local += len([d for d in os.listdir(sp) if not d.startswith(('#','@','.')) and os.path.isdir(os.path.join(sp, d))])\n" .
                "pend_shared = 0\n" .
                "if s_ok and os.path.isdir(s_mp):\n" .
                "    pend_shared = len([d for d in os.listdir(s_mp) if not d.startswith(('#','@','.')) and os.path.isdir(os.path.join(s_mp, d))])\n" .
                "print('CONEXION_OK')\n" .
                "print(f'NFS_SHARED:{int(s_ok)}:{s_src}|{s_mp}')\n" .
                "print(f'NFS_INSPUR:{int(i_ok)}:{i_src}|{i_mp}')\n" .
                "print(f'PENDING_ORBITS:{pend_local}:{pend_shared}')\n";

            $b64Mount = base64_encode($mountCheckPy);
            $testCmd = "python3 -c \"import base64; exec(base64.b64decode('{$b64Mount}'))\"";
            $sshRun = $this->executeRemoteSsh($task, $testCmd, 15);
            $sshAuthOk = str_contains($sshRun['output'], 'CONEXION_OK');
            $sshAuthOutput = $sshRun['output'];

            if (preg_match('/NFS_SHARED:([01]):([^|\r\n]*)\|([^\r\n]*)/', $sshAuthOutput, $mShared)) {
                $nfsSharedMounted = $mShared[1] === '1';
                $nfsSharedSource = trim($mShared[2]) ?: null;
                $sharedMountPoint = trim($mShared[3]) ?: $sharedMountPoint;
            }
            if (preg_match('/NFS_INSPUR:([01]):([^|\r\n]*)\|([^\r\n]*)/', $sshAuthOutput, $mInspur)) {
                $nfsInspurMounted = $mInspur[1] === '1';
                $nfsInspurSource = trim($mInspur[2]) ?: null;
                $inspurMountPoint = trim($mInspur[3]) ?: $inspurMountPoint;
            }
            if (preg_match('/PENDING_ORBITS:(\d+):(\d+)/', $sshAuthOutput, $mPend)) {
                $pendingLocalOrbits = (int) $mPend[1];
                $pendingSharedOrbits = (int) $mPend[2];
            }
        }

        // Validar montajes NFS requeridos para evitar escritura accidental en disco local de Synology
        $mountsOk = $nfsSharedMounted && (!$requireInspurMount || $nfsInspurMounted);
        $mountError = null;
        if ($sshAuthOk && !$mountsOk) {
            $missingMounts = [];
            if (!$nfsSharedMounted) {
                $missingMounts[] = "Carpeta intermedia '{$sharedMountPoint}' (recurso NFS MIGRACION_SYNOLOGY)";
            }
            if ($requireInspurMount && !$nfsInspurMounted) {
                $missingMounts[] = "Destino final Inspur (recurso NFS {$task->inspur_host}:/HS_IPS_PRIVATE en /volume3/dae-RowData/HS_IPS_PRIVATE o INSPUR_HS_IPS)";
            }
            $mountError = 'Montaje NFS caído o desconectado en Synology: ' . implode(' y ', $missingMounts) . '. Remonta la carpeta remota NFS en Synology File Station antes de continuar para evitar escribir en el volumen local.';
        }

        // Éxito: puerto SSH abierto, autenticación válida y montajes NFS activos
        $success = $socketOk && $sshAuthOk && $mountsOk;

        return [
            'ping' => $pingOk,
            'port_open' => $socketOk,
            'port_error' => $socketError,
            'ssh_auth' => $sshAuthOk,
            'ssh_output' => trim($sshAuthOutput),
            'nfs_shared_mounted' => $nfsSharedMounted,
            'nfs_shared_source' => $nfsSharedSource,
            'nfs_shared_path' => $sharedMountPoint,
            'nfs_inspur_mounted' => $nfsInspurMounted,
            'nfs_inspur_source' => $nfsInspurSource,
            'nfs_inspur_path' => $inspurMountPoint,
            'pending_local_orbits' => $pendingLocalOrbits,
            'pending_shared_orbits' => $pendingSharedOrbits,
            'mounts_ok' => $mountsOk,
            'mount_error' => $mountError,
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
                        : (!empty($connTest['mount_error'])
                            ? "Protección Anti-Desmontaje NFS activada: {$connTest['mount_error']}"
                            : "Conexión SSH no disponible hacia {$task->nas_host}:{$task->nas_port}."));

                $duration = (int) round(microtime(true) - $startTime);

                $log->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'duration_seconds' => $duration,
                    'files_transferred' => 0,
                    'error_message' => "Ejecución bloqueada preventivamente: {$motivo}",
                    'output' => "Verificación previa de seguridad y montajes NFS fallida:\n" . json_encode($connTest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
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
            $cleanSubdir = trim($inspurTargetDir, '/');
            $inspurFullFinalPath = rtrim($task->inspur_base_path ?: '/HS_IPS_PRIVATE/RDP_FTP/RAWDATA', '/') . '/' . $cleanSubdir . '/';
            $inspurSharedFolder = rtrim($task->inspur_shared_folder ?: '/HS_SHARE/MIGRACION_SYNOLOGY', '/') . '/';
            // Ruta del montaje NFS activo en Synology hacia el RAWDATA de Inspur (resuelta dinámicamente desde testConnection)
            $resolvedInspurRoot = $connTest['nfs_inspur_path'] ?: '/volume3/dae-RowData/HS_IPS_PRIVATE';
            $inspurNfsTarget = "{$resolvedInspurRoot}/RDP_FTP/RAWDATA/{$cleanSubdir}";

            // Última órbita transferida previamente registrada en la tarea
            $lastOrbit = $task->last_migrated_orbit ? trim($task->last_migrated_orbit) : '';

            // Construir ejecutor seguro en Python 3 sobre la NAS Synology
            // Bypassea completamente errores de escape en bash al manejar rutas con espacios
            // ("RAWDATA PRINCIPAL") y directorios internos de órbitas (CH1 y CH2).
            if ($batchLimit > 0) {
                $inspurMountRoot = $resolvedInspurRoot;
                $pyScript = "import os, sys, subprocess\n\n" .
                    "def is_nfs_mounted(mp):\n" .
                    "    mp = mp.rstrip('/')\n" .
                    "    try:\n" .
                    "        with open('/proc/mounts', 'r') as f:\n" .
                    "            for line in f:\n" .
                    "                p = line.split()\n" .
                    "                if len(p) >= 3 and p[1].rstrip('/') == mp and p[2].startswith('nfs'):\n" .
                    "                    return True\n" .
                    "    except Exception:\n" .
                    "        pass\n" .
                    "    return False\n\n" .
                    "def resolve_inspur_mount():\n" .
                    "    for cand in [" . var_export($inspurMountRoot, true) . ", '/volume3/dae-RowData/HS_IPS_PRIVATE', '/volume3/dae-RowData/INSPUR_HS_IPS']:\n" .
                    "        if is_nfs_mounted(cand):\n" .
                    "            return True, cand.rstrip('/')\n" .
                    "    return False, " . var_export($inspurMountRoot, true) . "\n\n" .
                    "src = " . var_export($source, true) . "\n" .
                    "tgt = " . var_export($target, true) . "\n" .
                    "clean_sub = " . var_export($cleanSubdir, true) . "\n" .
                    "inspur_mounted, inspur_mount_root = resolve_inspur_mount()\n" .
                    "inspur_final = os.path.join(inspur_mount_root, 'RDP_FTP', 'RAWDATA', clean_sub)\n" .
                    "local_pending_dir = os.path.join('/volume3/dae-RowData/INSPUR_HS_IPS_LOCAL_PENDIENTE/RDP_FTP/RAWDATA', clean_sub)\n" .
                    "last_orbit = " . var_export($lastOrbit, true) . "\n" .
                    "limit_orbits = " . (int)$batchLimit . "\n" .
                    "rsync_opts_str = " . var_export($options, true) . "\n" .
                    "batch_file = '/tmp/nas_batch_{$task->id}_' + str(os.getpid()) + '.txt'\n\n" .
                    "if not is_nfs_mounted(tgt):\n" .
                    "    print('[PROTECCION ANTI-DESMONTAJE] ERROR CRITICO: El recurso NFS intermedio ' + tgt + ' NO esta montado en Synology. Abortando Fase 1 para no llenar el volumen local.')\n" .
                    "    sys.exit(2)\n\n" .
                    "if not inspur_mounted:\n" .
                    "    print('[AVISO MONTAJE] HS_IPS_PRIVATE no esta montado por NFS; se ignorara cualquier carpeta local fantasma al verificar duplicados.')\n\n" .
                    "print('=== INICIANDO DETECCION AUTOMATICA DE ORBITAS Y ARCHIVOS PENDIENTES ===')\n" .
                    "if last_orbit:\n" .
                    "    print('[INDICE DE REANUDACION] Ultima orbita registrada: ' + last_orbit)\n" .
                    "else:\n" .
                    "    print('[MODO AUTOMATICO] Sin indice manual: escaneando pendientes en RAWDATA e Intermedia...')\n" .
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
                    "    # Detectar si faltan archivos considerando tanto la carpeta intermedia como el destino final Inspur RAWDATA (solo si esta montado por NFS)\n" .
                    "    pending = []\n" .
                    "    for rel_f in orb_files:\n" .
                    "        full_src = os.path.join(src, rel_f)\n" .
                    "        full_inter = os.path.join(tgt, rel_f)\n" .
                    "        full_inspur = os.path.join(inspur_final, rel_f)\n" .
                    "        src_sz = os.path.getsize(full_src) if os.path.exists(full_src) else -1\n\n" .
                    "        # Si existe en Inspur RAWDATA (con montaje NFS activo) y tamano identico -> Ya esta migrado\n" .
                    "        if inspur_mounted and os.path.exists(full_inspur) and os.path.getsize(full_inspur) == src_sz:\n" .
                    "            continue\n" .
                    "        # Si existe en la carpeta compartida intermedia con tamano identico -> Ya se copio en Fase 1\n" .
                    "        if os.path.exists(full_inter) and os.path.getsize(full_inter) == src_sz:\n" .
                    "            continue\n" .
                    "        pending.append(rel_f)\n\n" .
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
                if (app()->runningInConsole()) {
                    echo $buffer;
                }

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
                $log->update([
                    'output' => substr($output . "\n\n[FASE 2 - INSPUR]: Trasladando archivos a RAWDATA/{$inspurTargetDir}...", -5000),
                    'phase_inspur_status' => 'running',
                ]);
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

            // Si se transfirieron archivos con éxito en AMBAS fases y se detectó una última órbita en el lote, actualizar el índice de la tarea
            $finalTaskSuccess = $success && ($phaseInspurStatus !== 'failed');

            if ($finalTaskSuccess && $lastProcessedOrbit) {
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

            $phase2ErrorMsg = ($phaseInspurStatus === 'failed' && isset($moveResult['error']))
                ? $moveResult['error']
                : 'Error al trasladar archivos en Inspur.';

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
                'error_message' => $finalTaskSuccess ? null : ($execResult['error'] ?: ($phaseInspurStatus === 'failed' ? $phase2ErrorMsg : 'El proceso rsync finalizó con código de error.')),
            ]);

            // Si la tarea concluyó con éxito y transfirió archivos, registrar automáticamente en Estadísticas
            // Todas las migraciones gestionadas por las tareas de sincronización se asignan a "bot_pss"
            // Se registra cada órbita transferida de manera individual en las estadísticas históricas
            if ($finalTaskSuccess && $stats['files_transferred'] > 0) {
                try {
                    $responsableName = 'bot_pss';

                    $sateliteName = 'VRSS-1';
                    if (str_contains(strtoupper($inspurTargetDir), 'VRSS-2') || str_contains(strtoupper($source), 'VRSS-2')) {
                        $sateliteName = 'VRSS-2';
                    }

                    // Extraer desglose de órbitas individuales transferidas a partir del log
                    $orbitsFound = [];
                    if (!empty($output) && preg_match_all('/PROGRESS_FILE:\s*([^\r\n]+)/', $output, $matches)) {
                        foreach ($matches[1] as $path) {
                            $trimmed = trim($path);
                            if (!empty($trimmed) && !str_ends_with($trimmed, '/')) {
                                $parts = explode('/', $trimmed);
                                $orbName = $parts[0] ?? null;
                                if ($orbName) {
                                    if (!isset($orbitsFound[$orbName])) {
                                        $orbitsFound[$orbName] = 0;
                                    }
                                    $orbitsFound[$orbName]++;
                                }
                            }
                        }
                    }

                    // Si no hubo líneas PROGRESS_FILE con formato de órbita, usar la última órbita del lote
                    if (empty($orbitsFound) && !empty($lastProcessedOrbit)) {
                        $orbitsFound[$lastProcessedOrbit] = $stats['files_transferred'];
                    }

                    $totalFilesBatch = array_sum($orbitsFound) ?: $stats['files_transferred'] ?: 1;
                    $totalBytesBatch = $stats['total_bytes'] ?: ($stats['files_transferred'] * 268435456);

                    $maxLote = \App\Models\NasMigrationStat::where('responsable', $responsableName)->max('lote_numero') ?: 0;

                    foreach ($orbitsFound as $orbName => $orbFilesCount) {
                        $maxLote++;
                        // Proporción de tamaño en GB para esta órbita
                        $orbBytes = ($totalFilesBatch > 0) ? ($totalBytesBatch * ($orbFilesCount / $totalFilesBatch)) : 0;
                        $gbTransferred = round($orbBytes / (1024 * 1024 * 1024), 4);
                        if ($gbTransferred <= 0 && $orbFilesCount > 0) {
                            $gbTransferred = round(($orbFilesCount * 268435456) / (1024 * 1024 * 1024), 4);
                        }

                        \App\Models\NasMigrationStat::create([
                            'responsable' => $responsableName,
                            'fecha' => now()->toDateString(),
                            'satelite' => $sateliteName,
                            'archivo_id' => $orbName,
                            'tamano_gb' => $gbTransferred,
                            'lote_numero' => $maxLote,
                            'cantidad_archivos' => $orbFilesCount,
                            'observaciones' => "Sincronización NAS Tarea Programada - {$task->name} [Órbita: {$orbName}]",
                            'nas_migration_task_id' => $task->id,
                            'user_id' => $userId ?: 1,
                        ]);
                    }
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

        // Script en Python ejecutado en Synology para mover directamente vía rsync --remove-source-files
        // Resuelve dinámicamente el punto de montaje NFS activo de Inspur (/HS_IPS_PRIVATE) e incluye PROTECCIÓN ESTRICTA ANTI-DESMONTAJE NFS.
        $pyScript = "import os, subprocess, sys\n\n" .
            "def is_nfs_mounted(mp):\n" .
            "    mp = mp.rstrip('/')\n" .
            "    try:\n" .
            "        with open('/proc/mounts', 'r') as f:\n" .
            "            for line in f:\n" .
            "                p = line.split()\n" .
            "                if len(p) >= 3 and p[1].rstrip('/') == mp and p[2].startswith('nfs'):\n" .
            "                    return True\n" .
            "    except Exception:\n" .
            "        pass\n" .
            "    return False\n\n" .
            "def resolve_inspur_mount():\n" .
            "    candidates = ['/volume3/dae-RowData/INSPUR_HS_IPS', '/volume3/dae-RowData/HS_IPS_PRIVATE']\n" .
            "    try:\n" .
            "        with open('/proc/mounts', 'r') as f:\n" .
            "            lines = [l.split() for l in f if len(l.split()) >= 3]\n" .
            "        for c in candidates:\n" .
            "            for p in lines:\n" .
            "                if p[1].rstrip('/') == c and p[2].startswith('nfs'):\n" .
            "                    return True, p[1].rstrip('/')\n" .
            "        for p in lines:\n" .
            "            if p[2].startswith('nfs') and p[0].rstrip('/').endswith(':/HS_IPS_PRIVATE'):\n" .
            "                return True, p[1].rstrip('/')\n" .
            "    except Exception:\n" .
            "        pass\n" .
            "    return False, candidates[0]\n\n" .
            "src = " . var_export($sharedSynologyFolder, true) . "\n" .
            "clean_sub = " . var_export($cleanSubdir, true) . "\n" .
            "mounted_ok, mount_root = resolve_inspur_mount()\n" .
            "tgt = os.path.join(mount_root, 'RDP_FTP', 'RAWDATA', clean_sub)\n\n" .
            "if not is_nfs_mounted(src):\n" .
            "    print('ERROR_MOUNT_SRC:El recurso NFS origen ' + src + ' no esta montado en Synology.')\n" .
            "    sys.exit(3)\n\n" .
            "if not mounted_ok:\n" .
            "    print('ERROR_MOUNT_DEST:PROTECCION ANTI-DESMONTAJE ACTIVADA: El recurso NFS de Inspur (/HS_IPS_PRIVATE) NO esta montado en Synology. Se aborta Fase 2 y las orbitas quedan resguardadas en ' + src + ' para evitar escribir en el disco local de Synology.')\n" .
            "    sys.exit(4)\n\n" .
            "print('INSPUR_MOUNT_ACTIVE:' + mount_root)\n" .
            "if not os.path.exists(tgt):\n" .
            "    try:\n" .
            "        os.makedirs(tgt, exist_ok=True)\n" .
            "    except Exception as e:\n" .
            "        print('ERROR_MKDIR:' + str(e))\n" .
            "        sys.exit(1)\n\n" .
            "entries = sorted([d for d in os.listdir(src) if not d.startswith(('#', '@', '.'))])\n" .
            "total_moved = 0\n" .
            "move_errors = []\n" .
            "for orb in entries:\n" .
            "    # Re-verificar en cada orbita que el montaje NFS no se haya caido a mitad de transferencia\n" .
            "    if not is_nfs_mounted(mount_root):\n" .
            "        move_errors.append('Montaje NFS ' + mount_root + ' se desconecto durante el traslado de ' + orb)\n" .
            "        print('ERROR_MOUNT_LOST:' + orb)\n" .
            "        break\n" .
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
            "        err_line = proc.stdout[:200].replace('\\n', ' ')\n" .
            "        move_errors.append(orb + ': ' + err_line)\n" .
            "        print('ERROR_MOVE:' + orb + ':' + err_line)\n" .
            "    sys.stdout.flush()\n" .
            "if move_errors:\n" .
            "    print('ERROR_SUMMARY:' + ' | '.join(move_errors))\n" .
            "    print('[MOVED:' + str(total_moved) + ']')\n" .
            "    sys.exit(5)\n" .
            "print('[MOVED:' + str(total_moved) + ']')\n" .
            "sys.stdout.flush()\n";

        $b64 = base64_encode($pyScript);
        $cmd = "python3 -c \"import base64; exec(base64.b64decode('{$b64}'))\"";

        $onMoveOutput = function ($type, $buffer) use ($log) {
            if ($log) {
                // Mantener viva la marca de tiempo de la tarea en la BD para que el monitor nunca la confunda con colgada
                $updateData = [];
                if (str_contains($buffer, 'TRASLADANDO_ORBITA:') || str_contains($buffer, 'ERROR_MOUNT')) {
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
        $out = $result['output'] ?? '';
        $exitCode = $result['exit_code'] ?? 1;

        if (preg_match('/ERROR_MOUNT_DEST:([^\r\n]+)/', $out, $mDest)) {
            return [
                'success' => false,
                'moved' => 0,
                'message' => trim($mDest[1]),
                'error' => trim($mDest[1]),
            ];
        }

        if (preg_match('/ERROR_MOUNT_SRC:([^\r\n]+)/', $out, $mSrc)) {
            return [
                'success' => false,
                'moved' => 0,
                'message' => trim($mSrc[1]),
                'error' => trim($mSrc[1]),
            ];
        }

        $movedCount = 0;
        if (preg_match('/\[MOVED:(\d+)\]/', $out, $m)) {
            $movedCount = (int) $m[1];
        }

        if ($exitCode !== 0) {
            $errDetail = 'Fallo durante el traslado a Inspur RAWDATA.';
            if (preg_match('/ERROR_SUMMARY:([^\r\n]+)/', $out, $mSum)) {
                $errDetail = trim($mSum[1]);
            } elseif (preg_match('/ERROR_MKDIR:([^\r\n]+)/', $out, $mMk)) {
                $errDetail = 'No se pudo crear directorio destino en Inspur: ' . trim($mMk[1]);
            } elseif (!empty(trim($result['error'] ?? ''))) {
                $errDetail = trim($result['error']);
            }

            return [
                'success' => false,
                'moved' => $movedCount,
                'message' => "Traslado interrumpido ({$movedCount} archivos movidos). Motivo: {$errDetail}",
                'error' => $errDetail,
            ];
        }

        return [
            'success' => true,
            'moved' => $movedCount,
            'message' => "Archivos trasladados con éxito en Inspur a /HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{$cleanSubdir} ({$movedCount} archivos trasladados).",
            'error' => null,
        ];
    }

    public function moveFilesToInspurRawdata(NasMigrationTask $task, string $inspurDir): int
    {
        $res = $this->moveFilesToInspurRawdataDetailed($task, $inspurDir);
        return $res['moved'];
    }

    /**
     * Traslada en segundo plano TODAS las órbitas pendientes tanto de la carpeta local de rescate
     * (/volume3/dae-RowData/INSPUR_HS_IPS_LOCAL_PENDIENTE/RDP_FTP/RAWDATA/*) como de la carpeta
     * compartida intermedia (MIGRACION_SYNOLOGY) hacia el recurso NFS final de Inspur (/HS_IPS_PRIVATE),
     * eliminando archivos origen tras confirmar copia íntegra y limpiando directorios vacíos.
     */
    public function relocateAllPendingOrbits(
        NasMigrationTask $task,
        ?int $userId = null,
        ?int $logId = null
    ): NasMigrationLog {
        $log = $logId ? NasMigrationLog::find($logId) : null;
        if (!$log) {
            $log = $this->createInitialLog($task, 'manual', $userId);
        }

        $task->update([
            'status' => 'running',
            'last_run_at' => now(),
        ]);

        $startTime = microtime(true);

        try {
            $connTest = $this->testConnection($task);
            if (!$connTest['success']) {
                $motivo = $connTest['mount_error'] ?: ($connTest['port_error'] ?: 'Fallo de conexión o montaje NFS no disponible.');
                $duration = (int) round(microtime(true) - $startTime);
                $log->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'duration_seconds' => $duration,
                    'error_message' => "Reubicación bloqueada preventivamente: {$motivo}",
                    'output' => "Diagnóstico de seguridad:\n" . json_encode($connTest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ]);
                $task->update([
                    'status' => 'idle',
                    'last_run_status' => 'failed',
                ]);
                return $log;
            }

            $sharedFolder = rtrim($task->target_path ?: '/volume3/dae-RowData/MIGRACION_SYNOLOGY', '/');
            $defaultSubdir = trim($task->inspur_target_dir ?: 'VRSS-2', '/');
            $localRescueRoot = '/volume3/dae-RowData/INSPUR_HS_IPS_LOCAL_PENDIENTE';

            // Embebemos las rutas directamente con var_export dentro del script Python (sin pasar argumentos CLI externos
            // para evitar que escapeshellarg() en Windows rompa la cadena SSH con comillas dobles en plink.exe)
            $pyHeader = "import os, sys, subprocess\n" .
                "# TAG_ACTIVE_PROCESS: nas_relocate_active\n" .
                "shared_src = " . var_export($sharedFolder, true) . ".rstrip('/')\n" .
                "default_subdir = " . var_export($defaultSubdir, true) . ".strip('/')\n" .
                "rescue_root = " . var_export($localRescueRoot, true) . ".rstrip('/')\n\n";

            $pyBody = <<<'PYEOF'
def is_nfs_mounted(mp):
    mp = mp.rstrip('/')
    try:
        with open('/proc/mounts', 'r') as f:
            for line in f:
                p = line.split()
                if len(p) >= 3 and p[1].rstrip('/') == mp and p[2].startswith('nfs'):
                    return True
    except Exception:
        pass
    return False

def resolve_inspur_mount():
    candidates = ['/volume3/dae-RowData/INSPUR_HS_IPS', '/volume3/dae-RowData/HS_IPS_PRIVATE']
    try:
        with open('/proc/mounts', 'r') as f:
            lines = [l.split() for l in f if len(l.split()) >= 3]
        for c in candidates:
            for p in lines:
                if p[1].rstrip('/') == c and p[2].startswith('nfs'):
                    return True, p[1].rstrip('/')
        for p in lines:
            if p[2].startswith('nfs') and p[0].rstrip('/').endswith(':/HS_IPS_PRIVATE'):
                return True, p[1].rstrip('/')
    except Exception:
        pass
    return False, candidates[0]

mounted_ok, inspur_root = resolve_inspur_mount()
if not mounted_ok:
    print("[ERROR_MOUNT] PROTECCION ANTI-DESMONTAJE: /HS_IPS_PRIVATE no esta montado por NFS en Synology.", flush=True)
    sys.exit(4)

print("=== INICIANDO TRASLADO DEFINITIVO DE ORBITAS PENDIENTES A INSPUR ===", flush=True)
print(f"[FASE 2 - INSPUR] Destino NFS verificado: {inspur_root}/RDP_FTP/RAWDATA/", flush=True)

# 1) FASE 1: Pasar orbitas de INSPUR_HS_IPS_LOCAL_PENDIENTE -> MIGRACION_SYNOLOGY
rescue_jobs = []
rescue_rawdata = os.path.join(rescue_root, 'RDP_FTP', 'RAWDATA')
if os.path.isdir(rescue_rawdata):
    for sub in sorted(os.listdir(rescue_rawdata)):
        if sub.startswith(('#', '@', '.')):
            continue
        sub_path = os.path.join(rescue_rawdata, sub)
        if os.path.isdir(sub_path):
            for orb in sorted(os.listdir(sub_path)):
                if orb.startswith(('#', '@', '.')):
                    continue
                orb_p = os.path.join(sub_path, orb)
                if os.path.isdir(orb_p):
                    rescue_jobs.append((orb_p, sub, orb))

if len(rescue_jobs) > 0:
    if not os.path.isdir(shared_src):
        print(f"[ERROR_MOUNT] La carpeta destino MIGRACION_SYNOLOGY ({shared_src}) no existe o no esta accesible.", flush=True)
        sys.exit(4)
    os.makedirs(shared_src, exist_ok=True)
    print(f"[FASE 1 - RESCATE] Pasando {len(rescue_jobs)} orbitas de INSPUR_HS_IPS_LOCAL_PENDIENTE hacia MIGRACION_SYNOLOGY ({shared_src})...", flush=True)
    for r_idx, (src_orb, sub, orb) in enumerate(rescue_jobs, 1):
        tgt_shared_orb = os.path.join(shared_src, orb)
        print(f"[FASE 1: {r_idx}/{len(rescue_jobs)}] MOVIENDO_A_SHARED:{orb} -> MIGRACION_SYNOLOGY/{orb}", flush=True)
        cmd_r = ['rsync', '-avh', '--timeout=300', '--partial', '--remove-source-files', '--out-format=PROGRESS_FILE:' + orb + '/%n', src_orb + '/', tgt_shared_orb + '/']
        proc_r = subprocess.Popen(cmd_r, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, bufsize=1)
        out_r = []
        for line in proc_r.stdout:
            sys.stdout.write(line)
            sys.stdout.flush()
            if len(out_r) < 30:
                out_r.append(line.strip())
        proc_r.wait()
        if proc_r.returncode == 0:
            for root, dirs, _ in os.walk(src_orb, topdown=False):
                for d in dirs:
                    try:
                        os.rmdir(os.path.join(root, d))
                    except Exception:
                        pass
            try:
                os.rmdir(src_orb)
            except Exception:
                pass
        else:
            err_snip = ' '.join(out_r[-3:])[:220]
            print(f"[ERROR_FASE_1] {orb}: {err_snip}", flush=True)
            sys.exit(5)

# 2) FASE 2: Pasar todas las orbitas desde MIGRACION_SYNOLOGY -> NAS Inspur (/HS_IPS_PRIVATE)
jobs = []
if is_nfs_mounted(shared_src) and os.path.isdir(shared_src):
    for orb in sorted(os.listdir(shared_src)):
        if orb.startswith(('#', '@', '.')):
            continue
        orb_p = os.path.join(shared_src, orb)
        if os.path.isdir(orb_p):
            jobs.append(('MIGRACION_SYNOLOGY', orb_p, default_subdir, orb))

total_orbits = len(jobs)
if total_orbits == 0:
    print("TOTAL_BATCH_FILES:0", flush=True)
    print("[COMPLETADO] No existen orbitas pendientes por trasladar en local ni en MIGRACION_SYNOLOGY.", flush=True)
    print("RELOCATE_SUMMARY:orbits=0:files=0:bytes=0:last=", flush=True)
    sys.exit(0)

# Estimacion inicial rapida (~110 archivos promedio por orbita) que se ajusta dinamicamente conforme avanza
estimated_files = total_orbits * 110
print(f"TOTAL_BATCH_FILES:{estimated_files}", flush=True)
print(f"[RESUMEN LOTE] Orbitas pendientes detectadas: {total_orbits} | Iniciando transferencia inmediata...", flush=True)

moved_orbits = 0
moved_files = 0
moved_bytes = 0
scanned_files_so_far = 0
last_orbit = ''
errors = []

for idx, (origin_lbl, src_orb, sub, orb) in enumerate(jobs, 1):
    # Re-verificar montaje NFS antes de cada orbita
    if not is_nfs_mounted(inspur_root):
        msg = f"Montaje NFS {inspur_root} se desconecto antes de trasladar {orb}. Abortando de inmediato."
        print("[ERROR_MOUNT] " + msg, flush=True)
        errors.append(msg)
        break

    # Contar archivos y peso solo de la orbita actual (instantaneo)
    f_cnt = 0
    b_cnt = 0
    for r, _, files in os.walk(src_orb):
        for fn in files:
            f_cnt += 1
            try:
                b_cnt += os.path.getsize(os.path.join(r, fn))
            except Exception:
                pass

    scanned_files_so_far += f_cnt
    remaining_orbits = total_orbits - idx
    avg_per_orb = max(1, int(round(scanned_files_so_far / idx)))
    dynamic_total = scanned_files_so_far + (remaining_orbits * avg_per_orb)
    print(f"TOTAL_BATCH_FILES:{dynamic_total}", flush=True)

    tgt_base = os.path.join(inspur_root, 'RDP_FTP', 'RAWDATA', sub)
    os.makedirs(tgt_base, exist_ok=True)
    tgt_orb = os.path.join(tgt_base, orb)

    print(f"[{idx}/{total_orbits}] TRASLADANDO_ORBITA:{orb} ({origin_lbl} -> RAWDATA/{sub}, {f_cnt} arch.)", flush=True)

    cmd = ['rsync', '-avh', '--timeout=300', '--partial', '--remove-source-files', '--out-format=PROGRESS_FILE:' + orb + '/%n', src_orb + '/', tgt_orb + '/']
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, bufsize=1)
    out_lines = []
    for line in proc.stdout:
        sys.stdout.write(line)
        sys.stdout.flush()
        if len(out_lines) < 30:
            out_lines.append(line.strip())
    proc.wait()

    if proc.returncode == 0:
        moved_orbits += 1
        moved_files += f_cnt
        moved_bytes += b_cnt
        last_orbit = orb
        # Limpiar subcarpetas vacias (CH1, CH2 y carpeta de orbita)
        for root, dirs, _ in os.walk(src_orb, topdown=False):
            for d in dirs:
                try:
                    os.rmdir(os.path.join(root, d))
                except Exception:
                    pass
        try:
            os.rmdir(src_orb)
        except Exception:
            pass
    else:
        err_snip = ' '.join(out_lines[-3:])[:220]
        errors.append(f"{orb}: rc={proc.returncode} {err_snip}")
        print(f"[ERROR_ORBITA] {orb}: {err_snip}", flush=True)

# Ajustar total final exacto al 100%
print(f"TOTAL_BATCH_FILES:{moved_files}", flush=True)

# Si la carpeta local de rescate quedo completamente vacia, limpiar su arbol vacio
if os.path.isdir(rescue_root):
    for root, dirs, _ in os.walk(rescue_root, topdown=False):
        for d in dirs:
            try:
                os.rmdir(os.path.join(root, d))
            except Exception:
                pass
    try:
        os.rmdir(rescue_root)
        print(f"[LIMPIEZA] Carpeta temporal {rescue_root} eliminada tras quedar 100% vacia.", flush=True)
    except Exception:
        pass

print(f"RELOCATE_SUMMARY:orbits={moved_orbits}:files={moved_files}:bytes={moved_bytes}:last={last_orbit}", flush=True)
if errors:
    print("RELOCATE_ERRORS:" + " | ".join(errors), flush=True)
    sys.exit(5)
sys.exit(0)
PYEOF;

            $pyScript = $pyHeader . $pyBody;
            $b64 = base64_encode($pyScript);
            // Exactamente el mismo formato probado de runSync + marcador nas_relocate_active visible en ps aux
            $remoteCmd = "python3 -c \"# nas_relocate_active\nimport base64; exec(base64.b64decode('{$b64}').decode('utf-8'))\"";

            $filesCount = 0;
            $totalBatchFiles = 0;
            $accumulatedOutput = "[FASE 2 - INSPUR] Iniciando traslado masivo de órbitas pendientes hacia Inspur RAWDATA...\n";
            $lastProgressUpdate = microtime(true);

            $log->update([
                'phase_synology_status' => 'completed',
                'phase_inspur_status' => 'running',
                'source_path_used' => 'Rescate Local + MIGRACION_SYNOLOGY',
                'inspur_target_dir_used' => $defaultSubdir,
                'output' => $accumulatedOutput,
            ]);

            $onOutput = function ($type, $buffer) use (&$filesCount, &$totalBatchFiles, &$accumulatedOutput, &$lastProgressUpdate, $log) {
                $accumulatedOutput .= $buffer;
                if (app()->runningInConsole()) {
                    echo $buffer;
                }

                if (preg_match('/TOTAL_BATCH_FILES:(\d+)/', $buffer, $mTotal)) {
                    $totalBatchFiles = (int) $mTotal[1];
                    $log->update(['total_files_in_batch' => $totalBatchFiles]);
                }

                $hasNewFiles = false;
                if (preg_match_all('/PROGRESS_FILE:\s*([^\r\n]+)/', $buffer, $matches)) {
                    foreach ($matches[1] as $p) {
                        if (!str_ends_with(trim($p), '/')) {
                            $filesCount++;
                            $hasNewFiles = true;
                        }
                    }
                }

                $now = microtime(true);
                if ($hasNewFiles || ($now - $lastProgressUpdate >= 1.2)) {
                    $log->update([
                        'files_transferred' => $filesCount,
                        'files_moved' => $filesCount,
                        'total_files_in_batch' => $totalBatchFiles,
                        'output' => substr($accumulatedOutput, -5000),
                    ]);
                    $lastProgressUpdate = $now;
                }
            };

            $execResult = $this->executeRemoteSsh($task, $remoteCmd, 0, $onOutput);
            $duration = (int) round(microtime(true) - $startTime);
            $output = $execResult['output'] ?? '';
            $success = ($execResult['exit_code'] ?? 1) === 0;

            $movedOrbits = 0;
            $movedFiles = $filesCount;
            $movedBytes = 0;
            $lastOrbit = null;

            if (preg_match('/RELOCATE_SUMMARY:orbits=(\d+):files=(\d+):bytes=(\d+):last=([^\r\n]*)/', $output, $mSum)) {
                $movedOrbits = (int) $mSum[1];
                $movedFiles = (int) $mSum[2];
                $movedBytes = (int) $mSum[3];
                $lastOrbit = trim($mSum[4]) ?: null;
            }

            $errMsg = null;
            if (!$success) {
                if (preg_match('/RELOCATE_ERRORS:([^\r\n]+)/', $output, $mErr)) {
                    $errMsg = trim($mErr[1]);
                } elseif (preg_match('/\[ERROR_MOUNT\]\s*([^\r\n]+)/', $output, $mMnt)) {
                    $errMsg = trim($mMnt[1]);
                } else {
                    $errMsg = $execResult['error'] ?: 'El proceso de traslado de órbitas pendientes finalizó con errores.';
                }
            }

            if ($success && $lastOrbit) {
                $currentLast = $task->last_migrated_orbit ?: '';
                if ($lastOrbit > $currentLast) {
                    $task->update([
                        'last_migrated_orbit' => $lastOrbit,
                        'last_migrated_orbit_at' => now(),
                    ]);
                }
            }

            $humanSize = $this->formatBytes($movedBytes);
            $speedBps = $duration > 0 ? (int) round($movedBytes / $duration) : 0;
            $speedStr = $speedBps > 0 ? $this->formatBytes($speedBps) . '/s' : null;

            $log->update([
                'status' => $success ? 'completed' : 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'files_transferred' => $movedFiles,
                'files_moved' => $movedFiles,
                'orbits_transferred' => $movedOrbits,
                'orbits_skipped' => 0,
                'last_migrated_orbit_in_batch' => $lastOrbit,
                'total_bytes_transferred' => $movedBytes,
                'human_transferred_size' => $humanSize,
                'speed' => $speedStr,
                'phase_synology_status' => 'completed',
                'phase_inspur_status' => $success ? 'completed' : 'failed',
                'phase_details' => $success
                    ? "Reubicación Definitiva a Inspur RAWDATA/{$defaultSubdir}: OK ({$movedOrbits} órbitas, {$movedFiles} archivos, {$humanSize} trasladados y limpiados del origen temporal)."
                    : "Reubicación interrumpida: {$errMsg}",
                'summary' => "Reubicación de Órbitas Pendientes a Inspur RAWDATA | Órbitas movidas: {$movedOrbits} | Archivos: {$movedFiles} | Volumen: {$humanSize}" . ($lastOrbit ? " | Última: {$lastOrbit}" : ''),
                'output' => substr($output, -15000),
                'error_message' => $errMsg,
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
                'phase_inspur_status' => 'failed',
                'error_message' => $e->getMessage(),
                'output' => $e->getTraceAsString(),
            ]);
            $task->update([
                'status' => 'idle',
                'last_run_status' => 'failed',
            ]);
            Log::error("Error en reubicación de órbitas pendientes [Task #{$task->id}]: " . $e->getMessage());
        }

        return $log;
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
                "    active = any(('rsync' in l and not l.strip().endswith('--daemon') and 'grep' not in l) or 'nas_batch' in l or 'nas_relocate_active' in l for l in out.splitlines())\n" .
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
                "        if 'rsync' in line or 'nas_batch' in line or 'nas_relocate_active' in line:\n" .
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
            $mult = match ($unit) {
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
            $mult = match ($unit) {
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
            $mult = match ($unit) {
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
