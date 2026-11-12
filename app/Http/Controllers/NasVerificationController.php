<?php

namespace App\Http\Controllers;

use App\Models\NasFileVerification;
use App\Models\NasFileVerificationItem;
use App\Models\NasMigrationTask;
use App\Services\NasVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NasVerificationController extends Controller
{
    public function __construct(
        protected NasVerificationService $verificationService
    ) {}

    /**
     * Panel principal de confirmación y cotejo de archivos.
     */
    public function index(Request $request): View
    {
        $tasks = NasMigrationTask::all();
        $selectedTaskId = $request->input('task_id', $tasks->first()?->id);
        $selectedTask = $tasks->firstWhere('id', $selectedTaskId) ?? $tasks->first();

        // Obtener la verificación seleccionada o la más reciente de la tarea
        if ($request->filled('verification_id')) {
            $verification = NasFileVerification::with(['task', 'user'])->find($request->verification_id);
        } elseif ($selectedTask) {
            $verification = NasFileVerification::with(['task', 'user'])
                ->where('nas_migration_task_id', $selectedTask->id)
                ->latest('started_at')
                ->first();
        } else {
            $verification = null;
        }

        // Historial de las últimas sesiones de verificación
        $history = NasFileVerification::with(['task', 'user'])
            ->latest('started_at')
            ->take(15)
            ->get();

        // Consulta de items agrupados por órbita (conglomerado histórico consolidado)
        $orbitGroups = collect();
        if ($selectedTask) {
            // Traer todos los items de cotejo de esta tarea ordenados cronológicamente
            // Para que si una órbita fue evaluada en múltiples sesiones, conserve el último estado evaluado
            $rawItems = NasFileVerificationItem::whereHas('verification', function ($q) use ($selectedTask) {
                $q->where('nas_migration_task_id', $selectedTask->id);
            })->latest('id')->get();

            $grouped = [];
            foreach ($rawItems as $item) {
                $parts = explode('/', trim(str_replace('\\', '/', $item->relative_path), '/'));
                $orbName = $parts[0] ?? 'Varios';

                // Usamos relative_path como clave única para no duplicar archivos si se evaluaron más de una vez
                if (!isset($grouped[$orbName])) {
                    $grouped[$orbName] = [
                        'orbit' => $orbName,
                        'total_files' => 0,
                        'confirmed_files' => 0,
                        'failed_files' => 0,
                        'size_mismatch' => 0,
                        'missing' => 0,
                        'phase1_ok' => 0,
                        'phase2_ok' => 0,
                        'total_bytes' => 0,
                        'processed_paths' => [],
                    ];
                }

                // Si este archivo ya se contabilizó desde la sesión más reciente, lo ignoramos
                if (isset($grouped[$orbName]['processed_paths'][$item->relative_path])) {
                    continue;
                }
                $grouped[$orbName]['processed_paths'][$item->relative_path] = true;

                $grouped[$orbName]['total_files']++;
                $grouped[$orbName]['total_bytes'] += ($item->synology_size ?: $item->inspur_size ?: 0);

                if ($item->phase1_status === 'confirmed') {
                    $grouped[$orbName]['phase1_ok']++;
                }
                if ($item->phase2_status === 'confirmed' || $item->status === 'confirmed') {
                    $grouped[$orbName]['phase2_ok']++;
                }

                if ($item->status === 'confirmed') {
                    $grouped[$orbName]['confirmed_files']++;
                } else {
                    $grouped[$orbName]['failed_files']++;
                    if ($item->status === 'size_mismatch') {
                        $grouped[$orbName]['size_mismatch']++;
                    } elseif ($item->status === 'missing_target') {
                        $grouped[$orbName]['missing']++;
                    }
                }
            }

            // Calcular porcentajes y peso humano
            foreach ($grouped as $orb => $data) {
                $tot = $data['total_files'];
                $data['percentage'] = $tot > 0 ? round(($data['confirmed_files'] / $tot) * 100, 1) : 0;
                $data['human_size'] = $this->formatBytes($data['total_bytes']);
                unset($data['processed_paths']);
                $orbitGroups->push((object) $data);
            }

            // Filtrar si el usuario buscó por texto
            if ($request->filled('search')) {
                $search = mb_strtolower($request->search);
                $orbitGroups = $orbitGroups->filter(fn($o) => str_contains(mb_strtolower($o->orbit), $search));
            }

            // Filtrar por estado si seleccionó uno
            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'confirmed') {
                    $orbitGroups = $orbitGroups->filter(fn($o) => $o->failed_files === 0);
                } elseif ($request->status === 'mismatch' || $request->status === 'failed') {
                    $orbitGroups = $orbitGroups->filter(fn($o) => $o->failed_files > 0);
                }
            }

            // Ordenar alfabéticamente
            $orbitGroups = $orbitGroups->sortBy('orbit')->values();
        }

        return view('nas-migracion.confirmacion.index', compact(
            'tasks',
            'selectedTask',
            'selectedTaskId',
            'verification',
            'orbitGroups',
            'history'
        ));
    }

    /**
     * Inicia la verificación asíncrona en segundo plano sin bloquear el navegador.
     */
    public function startVerification(Request $request, NasMigrationTask $task): JsonResponse
    {
        // 1. Crear el registro inicial en estado 'running'
        $verification = NasFileVerification::create([
            'nas_migration_task_id' => $task->id,
            'user_id' => auth()->id(),
            'status' => 'running',
            'started_at' => now(),
        ]);

        $orbit = $request->input('orbit', $task->last_migrated_orbit);
        $orbitOpt = !empty($orbit) ? ' --orbit="' . addslashes(trim($orbit)) . '"' : '';

        // 2. Despachar comando Artisan desacoplado en segundo plano
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $userId = auth()->id() ?? 0;

        if (PHP_OS_FAMILY === 'Windows') {
            $command = "start /B \"\" \"{$php}\" \"{$artisan}\" nas:verify {$task->id} --verification_id={$verification->id} --user_id={$userId}{$orbitOpt} > NUL 2>&1";
            pclose(popen($command, 'r'));
        } else {
            $command = "{$php} \"{$artisan}\" nas:verify {$task->id} --verification_id={$verification->id} --user_id={$userId}{$orbitOpt} > /dev/null 2>&1 &";
            exec($command);
        }

        return response()->json([
            'success' => true,
            'status' => 'running',
            'verification_id' => $verification->id,
            'message' => 'El proceso de cotejo y verificación se ha iniciado en segundo plano' . (!empty($orbit) ? " para la órbita {$orbit}." : '. Evaluando archivos entre Synology e Inspur.'),
        ]);
    }

    /**
     * Retorna los archivos de una órbita específica con sus estados y diferencias.
     */
    public function getOrbitFiles(Request $request, NasFileVerification $verification, string $orbit): JsonResponse
    {
        $orbitClean = trim($orbit);
        
        // Buscar los archivos de esta órbita en la sesión indicada o en el histórico de la tarea
        $items = NasFileVerificationItem::whereHas('verification', function ($q) use ($verification) {
                $q->where('nas_migration_task_id', $verification->nas_migration_task_id);
            })
            ->where(function ($q) use ($orbitClean) {
                $q->where('relative_path', 'like', "{$orbitClean}/%")
                  ->orWhere('relative_path', 'like', "{$orbitClean}\\%")
                  ->orWhere('file_name', 'like', "%{$orbitClean}%");
            })
            ->latest('id')
            ->get()
            ->unique('relative_path');

        $formatted = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'file_name' => $item->file_name,
                'relative_path' => $item->relative_path,
                'status' => $item->status,
                'status_label' => $item->status_label,
                'status_badge_class' => $item->status_badge_class,
                'synology_size' => $item->synology_size,
                'human_synology_size' => $item->human_synology_size,
                'synology_mtime' => $item->synology_mtime ? $item->synology_mtime->format('d/m/Y H:i:s') : '-',
                'inspur_size' => $item->inspur_size,
                'human_inspur_size' => $item->human_inspur_size,
                'inspur_mtime' => $item->inspur_mtime ? $item->inspur_mtime->format('d/m/Y H:i:s') : '-',
                'phase1_status' => $item->phase1_status,
                'phase2_status' => $item->phase2_status,
                'size_match' => $item->size_match,
                'name_match' => $item->name_match,
                'is_failed' => $item->status !== 'confirmed',
            ];
        });

        $correctCount = $formatted->where('is_failed', false)->count();
        $failedCount = $formatted->where('is_failed', true)->count();

        return response()->json([
            'success' => true,
            'orbit' => $orbitClean,
            'total_files' => $formatted->count(),
            'correct_count' => $correctCount,
            'failed_count' => $failedCount,
            'files' => $formatted->values(),
        ]);
    }

    /**
     * Retorna el resumen agrupado por órbita de una verificación para mostrar en modal.
     */
    public function getOrbitSummary(NasFileVerification $verification): JsonResponse
    {
        $verification->load(['task', 'user']);
        $items = $verification->items()->get();

        $orbits = [];
        foreach ($items as $item) {
            $parts = explode('/', trim(str_replace('\\', '/', $item->relative_path), '/'));
            $orbitName = $parts[0] ?? 'Raíz / Varios';

            if (!isset($orbits[$orbitName])) {
                $orbits[$orbitName] = [
                    'orbit' => $orbitName,
                    'total_files' => 0,
                    'confirmed_files' => 0,
                    'mismatched_size' => 0,
                    'missing_target' => 0,
                    'phase1_ok' => 0,
                    'phase2_ok' => 0,
                    'total_bytes' => 0,
                ];
            }

            $orbits[$orbitName]['total_files']++;
            $orbits[$orbitName]['total_bytes'] += ($item->synology_size ?: $item->inspur_size ?: 0);

            if ($item->phase1_status === 'confirmed') {
                $orbits[$orbitName]['phase1_ok']++;
            }
            if ($item->phase2_status === 'confirmed' || $item->status === 'confirmed') {
                $orbits[$orbitName]['phase2_ok']++;
            }

            if ($item->status === 'confirmed') {
                $orbits[$orbitName]['confirmed_files']++;
            } elseif ($item->status === 'size_mismatch') {
                $orbits[$orbitName]['mismatched_size']++;
            } elseif ($item->status === 'missing_target') {
                $orbits[$orbitName]['missing_target']++;
            }
        }

        // Calcular porcentajes y tamaños formateados
        $orbitsList = [];
        foreach ($orbits as $orb => $data) {
            $tot = $data['total_files'];
            $data['percentage'] = $tot > 0 ? round(($data['confirmed_files'] / $tot) * 100, 1) : 0;
            $data['human_size'] = $this->formatBytes($data['total_bytes']);
            $orbitsList[] = $data;
        }

        // Ordenar órbitas alfabéticamente
        usort($orbitsList, fn($a, $b) => strcmp($a['orbit'], $b['orbit']));

        return response()->json([
            'success' => true,
            'verification' => [
                'id' => $verification->id,
                'task_name' => $verification->task?->name ?? 'Tarea General',
                'executed_at' => $verification->started_at ? $verification->started_at->format('d/m/Y H:i:s') : '-',
                'duration' => $verification->duration_formatted,
                'status' => $verification->status,
                'total_files' => $verification->total_files_synology,
                'total_confirmed' => $verification->total_confirmed,
                'total_missing' => $verification->total_missing_in_target,
                'percentage' => $verification->confirmed_percentage,
                'error_message' => $verification->error_message,
            ],
            'orbits' => $orbitsList,
            'total_orbits_count' => count($orbitsList),
        ]);
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

    /**
     * Consulta el estado de una verificación en tiempo real para el polling del frontend.
     */
    public function checkStatus(NasFileVerification $verification): JsonResponse
    {
        $verification->refresh();

        return response()->json([
            'id' => $verification->id,
            'status' => $verification->status,
            'duration' => $verification->duration_formatted,
            'current_orbit' => $verification->current_orbit,
            'files_scanned' => $verification->files_scanned,
            'total_files_to_scan' => $verification->total_files_to_scan,
            'total_files' => $verification->total_files_synology,
            'confirmed' => $verification->total_confirmed,
            'mismatched_size' => $verification->total_mismatched_size,
            'mismatched_date' => $verification->total_mismatched_date,
            'missing' => $verification->total_missing_in_target,
            'percentage' => $verification->confirmed_percentage,
            'error_message' => $verification->error_message,
        ]);
    }

    /**
     * Genera una verificación simulada/demostrativa de cotejo Synology vs Inspur.
     */
    public function simulate(NasMigrationTask $task): JsonResponse
    {
        $verification = $this->verificationService->simulateVerification($task, auth()->id());

        return response()->json([
            'success' => true,
            'verification_id' => $verification->id,
            'message' => 'Simulación de confirmación y corroboración de archivos completada exitosamente.',
        ]);
    }

    /**
     * Exporta el reporte comparativo en formato CSV para descarga directa.
     */
    public function export(NasFileVerification $verification): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"reporte_confirmacion_nas_{$verification->id}.csv\"",
        ];

        return response()->stream(function () use ($verification) {
            $handle = fopen('php://output', 'w');
            
            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($handle, [
                'ID',
                'Ruta Relativa',
                'Nombre de Archivo',
                'Synology Tamaño (Bytes)',
                'Synology Tamaño',
                'Synology Fecha Modificación',
                'Inspur Tamaño (Bytes)',
                'Inspur Tamaño',
                'Inspur Fecha Modificación',
                'Nombre Coincide',
                'Tamaño Coincide',
                'Fecha Coincide',
                'Estado Veredicto',
            ]);

            $verification->items()->chunk(1000, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->id,
                        $item->relative_path,
                        $item->file_name,
                        $item->synology_size,
                        $item->human_synology_size,
                        $item->synology_mtime ? $item->synology_mtime->format('d/m/Y H:i:s') : '-',
                        $item->inspur_size !== null ? $item->inspur_size : 'No existe',
                        $item->human_inspur_size,
                        $item->inspur_mtime ? $item->inspur_mtime->format('d/m/Y H:i:s') : '-',
                        $item->name_match ? 'SI' : 'NO',
                        $item->size_match ? 'SI' : 'NO',
                        $item->date_match ? 'SI' : 'NO',
                        $item->status_label,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}

