<?php

namespace App\Services;

use App\Models\NasFileVerification;
use App\Models\NasFileVerificationItem;
use App\Models\NasMigrationTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NasVerificationService
{
    public function __construct(
        protected NasMigrationService $migrationService
    ) {}

    /**
     * Ejecuta la verificación y corroboración comparativa entre Synology e Inspur.
     */
    public function runVerification(
        NasMigrationTask $task,
        ?int $userId = null,
        ?int $verificationId = null
    ): NasFileVerification {
        if ($verificationId) {
            $verification = NasFileVerification::find($verificationId);
            if (!$verification) {
                $verification = $this->createInitialVerification($task, $userId);
            }
        } else {
            $verification = $this->createInitialVerification($task, $userId);
        }

        $startTime = microtime(true);

        try {
            $source = rtrim($task->source_path, '/');
            // Si los archivos se mueven a Inspur RAWDATA, cotejar contra esa ubicación final; de lo contrario contra MIGRACION_SYNOLOGY
            $target = rtrim($task->auto_move_to_inspur ? ($task->full_inspur_path ?: $task->target_path) : $task->target_path, '/');

            // Script remoto en Python3 optimizado con tolerancia para timestamps de NFS/SMB
            // y ejecución dentro de la Synology
            $pyScript = <<<'PYEOF'
import os, sys, json

src = sys.argv[1].rstrip('/')
tgt = sys.argv[2].rstrip('/')

if not os.path.exists(src):
    print(json.dumps({'success': False, 'error': f'Ruta origen no encontrada en Synology: {src}'}))
    sys.exit(0)

if not os.path.exists(tgt):
    print(json.dumps({'success': False, 'error': f'Ruta destino Inspur no encontrada o no montada: {tgt}'}))
    sys.exit(0)

items = []
total_src_bytes = 0
total_tgt_bytes = 0

for root, dirs, files in os.walk(src):
    for f in files:
        full_src = os.path.join(root, f)
        rel = os.path.relpath(full_src, src).replace('\\', '/')
        full_tgt = os.path.join(tgt, rel)

        try:
            st_src = os.stat(full_src)
            src_size = st_src.st_size
            src_mtime = int(st_src.st_mtime)
            total_src_bytes += src_size
        except Exception:
            continue

        tgt_size = None
        tgt_mtime = None
        status = 'missing_target'

        if os.path.exists(full_tgt):
            try:
                st_tgt = os.stat(full_tgt)
                tgt_size = st_tgt.st_size
                tgt_mtime = int(st_tgt.st_mtime)
                total_tgt_bytes += tgt_size

                size_ok = (src_size == tgt_size)
                # Tolerancia de 2 segundos para variaciones de redondeo en timestamps entre NFS/EXT4/Btrfs
                date_ok = abs(src_mtime - tgt_mtime) <= 2

                if size_ok and date_ok:
                    status = 'confirmed'
                elif not size_ok:
                    status = 'size_mismatch'
                else:
                    status = 'date_mismatch'
            except Exception:
                status = 'missing_target'

        items.append({
            'path': rel,
            'name': f,
            'src_size': src_size,
            'src_mtime': src_mtime,
            'tgt_size': tgt_size,
            'tgt_mtime': tgt_mtime,
            'status': status
        })

print(json.dumps({
    'success': True,
    'total_src_bytes': total_src_bytes,
    'total_tgt_bytes': total_tgt_bytes,
    'items': items
}))
PYEOF;

            // Codificar el script en Base64 para evitar problemas de comillas en SSH
            $base64Script = base64_encode($pyScript);
            $remoteCmd = "python3 -c \"import base64; exec(base64.b64decode('{$base64Script}').decode('utf-8'))\" \"{$source}\" \"{$target}\"";

            $execResult = $this->migrationService->executeRemoteSsh($task, $remoteCmd, 0);

            if ($execResult['exit_code'] !== 0 && empty($execResult['output'])) {
                throw new \Exception($execResult['error'] ?: 'Error de conexión o fallo al ejecutar script en la NAS Synology.');
            }

            $rawOutput = trim($execResult['output']);
            $parsed = json_decode($rawOutput, true);

            if (!$parsed || !isset($parsed['success'])) {
                // Si la NAS no tiene python3 o hubo un error de parseo, verificar salida
                throw new \Exception('Respuesta inválida de la NAS Synology. Salida: ' . substr($rawOutput, 0, 300));
            }

            if (!$parsed['success']) {
                throw new \Exception($parsed['error'] ?? 'Fallo reportado durante la lectura de rutas en la NAS.');
            }

            $itemsData = $parsed['items'] ?? [];
            $this->saveVerificationItems($verification, $itemsData);

            $duration = (int) round(microtime(true) - $startTime);

            $verification->update([
                'status' => 'completed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'total_bytes_synology' => $parsed['total_src_bytes'] ?? 0,
                'total_bytes_inspur' => $parsed['total_tgt_bytes'] ?? 0,
                'summary' => "Verificación completada: {$verification->total_confirmed} confirmados de {$verification->total_files_synology} archivos examinados.",
                'error_message' => null,
            ]);

        } catch (\Throwable $e) {
            $duration = (int) round(microtime(true) - $startTime);

            $verification->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_seconds' => $duration,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Error en verificación NAS [Task #{$task->id}]: " . $e->getMessage());
        }

        return $verification;
    }

    /**
     * Genera una verificación de prueba/simulación con archivos representativos
     * (permite validar la corroboración de nombres, tamaños y fechas sin conexión física).
     */
    public function simulateVerification(NasMigrationTask $task, ?int $userId = null): NasFileVerification
    {
        $verification = $this->createInitialVerification($task, $userId);
        $startTime = microtime(true);

        $dummyFiles = [
            // Archivos 100% Confirmados (Idénticos en tamaño, nombre y fecha)
            ['docs/Finanzas_2026/Balance_Q1.xlsx', 1048576, -3600, 1048576, -3600, 'confirmed'],
            ['docs/Finanzas_2026/Presupuesto_Anual.pdf', 2450300, -7200, 2450300, -7200, 'confirmed'],
            ['docs/Legal/Contrato_Marco_Inspur.docx', 850200, -10000, 850200, -10000, 'confirmed'],
            ['multimedia/Logos/Logo_Institucional_Vector.svg', 125400, -15000, 125400, -15000, 'confirmed'],
            ['multimedia/Videos/Capacitacion_NAS_01.mp4', 85400100, -86400, 85400100, -86400, 'confirmed'],
            ['multimedia/Videos/Induccion_Sistemas.mp4', 124500000, -90000, 124500000, -90000, 'confirmed'],
            ['sistemas/backups/db_produccion_20261105.sql.gz', 354000100, -12000, 354000100, -12000, 'confirmed'],
            ['sistemas/certificados/ssl_wildcard_2026.crt', 4096, -20000, 4096, -20000, 'confirmed'],
            ['sistemas/certificados/bundle_chain.pem', 8192, -20000, 8192, -20000, 'confirmed'],
            ['usuarios/jperera/reportes/Gestion_TI_Octubre.pdf', 540200, -5000, 540200, -5000, 'confirmed'],
            ['usuarios/mrodriguez/proyectos/Plan_Migracion.pptx', 4500000, -25000, 4500000, -25000, 'confirmed'],
            ['archivos_historicos/2025/Actas_Reuniones.zip', 45000000, -180000, 45000000, -180000, 'confirmed'],
            ['archivos_historicos/2025/Auditoria_Externa.pdf', 8400000, -200000, 8400000, -200000, 'confirmed'],

            // Archivos con discrepancia de tamaño (ej. transferencia truncada)
            ['sistemas/backups/db_analytics_20261104.tar.gz', 620000000, -15000, 540000000, -15000, 'size_mismatch'],
            ['multimedia/Videos/Promo_Institucional_4K.mov', 450000000, -80000, 310000000, -80000, 'size_mismatch'],

            // Archivos con discrepancia de fecha (mismo tamaño pero mtime distinto)
            ['docs/RRHH/Politicas_Seguridad_2026.pdf', 1200500, -60000, 1200500, -120000, 'date_mismatch'],
            ['sistemas/scripts/deploy_nas_storage.sh', 15200, -10000, 15200, -40000, 'date_mismatch'],

            // Archivos faltantes en destino Inspur (aún no migrados)
            ['sistemas/firmwares/inspur_in_storage_v4.2.bin', 98000000, -4000, null, null, 'missing_target'],
            ['docs/Finanzas_2026/Nomina_Noviembre_Borrador.xlsx', 740100, -1200, null, null, 'missing_target'],
            ['usuarios/admin/claves_encriptadas_recuperacion.gpg', 2048, -800, null, null, 'missing_target'],
        ];

        // Añadir 30 archivos confirmados adicionales para completar una muestra variada
        for ($i = 1; $i <= 30; $i++) {
            $num = str_pad($i, 3, '0', STR_PAD_LEFT);
            $dummyFiles[] = [
                "documentos_digitalizados/lote_general/expediente_{$num}.pdf",
                rand(150000, 3500000),
                -rand(1000, 150000),
                null, // Se asignará idéntico
                null,
                'confirmed',
            ];
        }

        $now = now();
        $itemsData = [];
        $totalSrcBytes = 0;
        $totalTgtBytes = 0;

        foreach ($dummyFiles as $row) {
            $path = $row[0];
            $name = basename($path);
            $srcSize = $row[1];
            $srcOffset = $row[2];
            $tgtSize = $row[3] ?? $srcSize;
            $tgtOffset = $row[4] ?? $srcOffset;
            $status = $row[5];

            if ($status === 'confirmed') {
                $tgtSize = $srcSize;
                $tgtOffset = $srcOffset;
            } elseif ($status === 'missing_target') {
                $tgtSize = null;
                $tgtOffset = null;
            }

            $srcMtime = (clone $now)->addSeconds($srcOffset)->getTimestamp();
            $tgtMtime = $tgtOffset !== null ? (clone $now)->addSeconds($tgtOffset)->getTimestamp() : null;

            $totalSrcBytes += $srcSize;
            if ($tgtSize !== null) {
                $totalTgtBytes += $tgtSize;
            }

            $itemsData[] = [
                'path' => $path,
                'name' => $name,
                'src_size' => $srcSize,
                'src_mtime' => $srcMtime,
                'tgt_size' => $tgtSize,
                'tgt_mtime' => $tgtMtime,
                'status' => $status,
            ];
        }

        $this->saveVerificationItems($verification, $itemsData);

        $duration = (int) round(microtime(true) - $startTime);

        $verification->update([
            'status' => 'completed',
            'finished_at' => now(),
            'duration_seconds' => max(1, $duration),
            'total_bytes_synology' => $totalSrcBytes,
            'total_bytes_inspur' => $totalTgtBytes,
            'summary' => "Simulación de verificación finalizada: {$verification->total_confirmed} confirmados de {$verification->total_files_synology} archivos analizados.",
            'error_message' => null,
        ]);

        return $verification;
    }

    /**
     * Guarda los items en bloques para alto rendimiento y actualiza los contadores.
     */
    protected function saveVerificationItems(NasFileVerification $verification, array $itemsData): void
    {
        // Limpiar items previos si se está reutilizando la sesión
        $verification->items()->delete();

        $chunks = array_chunk($itemsData, 500);
        $totalConfirmed = 0;
        $totalMismatchedSize = 0;
        $totalMismatchedDate = 0;
        $totalMissing = 0;
        $totalInspurFiles = 0;

        foreach ($chunks as $chunk) {
            $insertBatch = [];

            foreach ($chunk as $item) {
                $status = $item['status'] ?? 'confirmed';

                if ($status === 'confirmed') {
                    $totalConfirmed++;
                    $totalInspurFiles++;
                } elseif ($status === 'size_mismatch') {
                    $totalMismatchedSize++;
                    $totalInspurFiles++;
                } elseif ($status === 'date_mismatch') {
                    $totalMismatchedDate++;
                    $totalInspurFiles++;
                } elseif ($status === 'missing_target') {
                    $totalMissing++;
                }

                $insertBatch[] = [
                    'nas_file_verification_id' => $verification->id,
                    'relative_path' => $item['path'],
                    'file_name' => $item['name'] ?? basename($item['path']),
                    'synology_size' => $item['src_size'],
                    'synology_mtime' => isset($item['src_mtime']) ? Carbon::createFromTimestamp($item['src_mtime']) : null,
                    'inspur_size' => $item['tgt_size'],
                    'inspur_mtime' => isset($item['tgt_mtime']) ? Carbon::createFromTimestamp($item['tgt_mtime']) : null,
                    'status' => $status,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            NasFileVerificationItem::insert($insertBatch);
        }

        $verification->update([
            'total_files_synology' => count($itemsData),
            'total_files_inspur' => $totalInspurFiles,
            'total_confirmed' => $totalConfirmed,
            'total_mismatched_size' => $totalMismatchedSize,
            'total_mismatched_date' => $totalMismatchedDate,
            'total_missing_in_target' => $totalMissing,
        ]);
    }

    protected function createInitialVerification(NasMigrationTask $task, ?int $userId): NasFileVerification
    {
        return NasFileVerification::create([
            'nas_migration_task_id' => $task->id,
            'user_id' => $userId,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }
}

