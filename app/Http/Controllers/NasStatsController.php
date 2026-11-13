<?php

namespace App\Http\Controllers;

use App\Models\NasMigrationStat;
use App\Models\NasMigrationTask;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NasStatsController extends Controller
{
    /**
     * Vista principal del módulo de estadísticas de migración.
     */
    public function index(Request $request)
    {
        // 1. Métricas Globales
        $totalGb = (float) NasMigrationStat::sum('tamano_gb');
        $totalFiles = (int) NasMigrationStat::sum('cantidad_archivos');
        $totalExecutors = (int) NasMigrationStat::distinct('responsable')->count('responsable');

        $totalVrss1Gb = (float) NasMigrationStat::where('satelite', 'VRSS-1')->sum('tamano_gb');
        $totalVrss1Files = (int) NasMigrationStat::where('satelite', 'VRSS-1')->sum('cantidad_archivos');

        $totalVrss2Gb = (float) NasMigrationStat::where('satelite', 'VRSS-2')->sum('tamano_gb');
        $totalVrss2Files = (int) NasMigrationStat::where('satelite', 'VRSS-2')->sum('cantidad_archivos');

        // 2. Resumen por Responsable (Cotejado con hoja "Resumen por Responsable" del Excel)
        $responsablesStats = NasMigrationStat::select(
            'responsable',
            DB::raw('SUM(tamano_gb) as total_gb'),
            DB::raw('SUM(cantidad_archivos) as total_archivos'),
            DB::raw('COUNT(*) as total_registros'),
            DB::raw('MIN(fecha) as fecha_inicio'),
            DB::raw('MAX(fecha) as fecha_fin')
        )
            ->groupBy('responsable')
            ->orderByDesc('total_gb')
            ->get()
            ->map(function ($row) use ($totalGb) {
                $row->porcentaje = $totalGb > 0 ? round(($row->total_gb / $totalGb) * 100, 2) : 0;
                $row->satelites = NasMigrationStat::where('responsable', $row->responsable)
                    ->distinct()
                    ->pluck('satelite')
                    ->toArray();
                return $row;
            });

        // 3. Preparación de datos para Gráficas ApexCharts
        // Donut: Distribución de GB por Ejecutor
        $chartExecutorsLabels = $responsablesStats->pluck('responsable')->toArray();
        $chartExecutorsSeries = $responsablesStats->map(fn($r) => round((float)$r->total_gb, 2))->toArray();

        // Donut: Satélites (VRSS-1 vs VRSS-2)
        $chartSatLabels = ['VRSS-1', 'VRSS-2'];
        $chartSatSeries = [round($totalVrss1Gb, 2), round($totalVrss2Gb, 2)];

        // Serie de Tiempo: GB Migrados por Día
        $dailyData = NasMigrationStat::select(
            'fecha',
            'satelite',
            DB::raw('SUM(tamano_gb) as dia_gb'),
            DB::raw('SUM(cantidad_archivos) as dia_archivos')
        )
            ->groupBy('fecha', 'satelite')
            ->orderBy('fecha')
            ->get();

        $allDates = $dailyData->pluck('fecha')->map(fn($d) => is_string($d) ? substr($d, 0, 10) : $d->format('Y-m-d'))->unique()->values()->toArray();

        $timelineVrss1 = [];
        $timelineVrss2 = [];
        foreach ($allDates as $dateStr) {
            $v1 = $dailyData->first(fn($item) => (is_string($item->fecha) ? substr($item->fecha, 0, 10) : $item->fecha->format('Y-m-d')) === $dateStr && $item->satelite === 'VRSS-1');
            $v2 = $dailyData->first(fn($item) => (is_string($item->fecha) ? substr($item->fecha, 0, 10) : $item->fecha->format('Y-m-d')) === $dateStr && $item->satelite === 'VRSS-2');
            $timelineVrss1[] = $v1 ? round((float)$v1->dia_gb, 2) : 0;
            $timelineVrss2[] = $v2 ? round((float)$v2->dia_gb, 2) : 0;
        }

        // Agrupación Semanal (Lunes a Domingo con rango de fechas)
        $allStatsForPeriod = NasMigrationStat::all(['fecha', 'satelite', 'tamano_gb']);
        $weeksGrouped = $allStatsForPeriod->groupBy(function($item) {
            $c = \Carbon\Carbon::parse($item->fecha);
            return $c->isoWeekYear() . '-S' . str_pad($c->isoWeek(), 2, '0', STR_PAD_LEFT);
        })->sortKeys();

        $weeklyLabels = [];
        $weeklyVrss1 = [];
        $weeklyVrss2 = [];
        foreach ($weeksGrouped as $wKey => $items) {
            $min = $items->min('fecha');
            $max = $items->max('fecha');
            $minStr = date('d/m', strtotime($min));
            $maxStr = date('d/m', strtotime($max));
            $weeklyLabels[] = $wKey . ' (' . $minStr . ' - ' . $maxStr . ')';
            $weeklyVrss1[] = round((float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb'), 2);
            $weeklyVrss2[] = round((float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb'), 2);
        }

        // Agrupación Mensual (Nombre de Mes y Año)
        $monthsGrouped = $allStatsForPeriod->groupBy(function($item) {
            return substr(is_string($item->fecha) ? $item->fecha : $item->fecha->format('Y-m-d'), 0, 7);
        })->sortKeys();

        $monthlyLabels = [];
        $monthlyVrss1 = [];
        $monthlyVrss2 = [];
        $mesesEsp = [
            '01'=>'Ene', '02'=>'Feb', '03'=>'Mar', '04'=>'Abr', '05'=>'May', '06'=>'Jun',
            '07'=>'Jul', '08'=>'Ago', '09'=>'Sep', '10'=>'Oct', '11'=>'Nov', '12'=>'Dic'
        ];
        foreach ($monthsGrouped as $mKey => $items) {
            $p = explode('-', $mKey);
            $monthlyLabels[] = ($mesesEsp[$p[1]] ?? $p[1]) . ' ' . $p[0];
            $monthlyVrss1[] = round((float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb'), 2);
            $monthlyVrss2[] = round((float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb'), 2);
        }

        // 4. Listado Detallado de Registros con Filtros
        $query = NasMigrationStat::with(['task', 'user'])->latest('fecha')->latest('id');

        if ($request->filled('responsable') && $request->responsable !== 'all') {
            $query->where('responsable', $request->responsable);
        }

        if ($request->filled('satelite') && $request->satelite !== 'all') {
            $query->where('satelite', $request->satelite);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('archivo_id', 'like', "%{$s}%")
                    ->orWhere('responsable', 'like', "%{$s}%")
                    ->orWhere('observaciones', 'like', "%{$s}%");
            });
        }

        $records = $query->paginate(10)->withQueryString();

        // Lista de ejecutores registrados para selectores y datalists
        $availableExecutors = NasMigrationStat::distinct()->orderBy('responsable')->pluck('responsable')->toArray();

        return view('nas-migracion.estadisticas.index', compact(
            'totalGb',
            'totalFiles',
            'totalExecutors',
            'totalVrss1Gb',
            'totalVrss1Files',
            'totalVrss2Gb',
            'totalVrss2Files',
            'responsablesStats',
            'chartExecutorsLabels',
            'chartExecutorsSeries',
            'chartSatLabels',
            'chartSatSeries',
            'allDates',
            'timelineVrss1',
            'timelineVrss2',
            'weeklyLabels',
            'weeklyVrss1',
            'weeklyVrss2',
            'monthlyLabels',
            'monthlyVrss1',
            'monthlyVrss2',
            'records',
            'availableExecutors'
        ));
    }

    /**
     * Retorna el resumen consolidado y desglose de un ejecutor para modal AJAX.
     */
    public function getResponsableSummary(Request $request): JsonResponse
    {
        $responsable = trim($request->input('responsable', ''));

        if (empty($responsable)) {
            return response()->json([
                'success' => false,
                'message' => 'El responsable es requerido.',
            ], 422);
        }

        $globalTotalGb = (float) NasMigrationStat::sum('tamano_gb');
        $query = NasMigrationStat::where('responsable', $responsable);

        $totalGb = (float) (clone $query)->sum('tamano_gb');
        $totalArchivos = (int) (clone $query)->sum('cantidad_archivos');
        $totalRegistros = (int) (clone $query)->count();
        $minFecha = (clone $query)->min('fecha');
        $maxFecha = (clone $query)->max('fecha');

        $porcentaje = $globalTotalGb > 0 ? round(($totalGb / $globalTotalGb) * 100, 2) : 0;

        // Desglose por Satélite
        $satBreakdown = NasMigrationStat::where('responsable', $responsable)
            ->select(
                'satelite',
                DB::raw('SUM(tamano_gb) as total_gb'),
                DB::raw('SUM(cantidad_archivos) as total_archivos'),
                DB::raw('COUNT(*) as total_registros')
            )
            ->groupBy('satelite')
            ->orderBy('satelite')
            ->get()
            ->map(function ($row) use ($totalGb) {
                $row->total_gb = round((float) $row->total_gb, 4);
                $row->porcentaje_sat = $totalGb > 0 ? round(($row->total_gb / $totalGb) * 100, 1) : 0;
                return $row;
            });

        // Últimos 8 registros transferidos por este responsable
        $recentRecords = NasMigrationStat::with('user')
            ->where('responsable', $responsable)
            ->latest('fecha')
            ->latest('id')
            ->take(8)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'fecha' => is_string($r->fecha) ? substr($r->fecha, 0, 10) : ($r->fecha ? $r->fecha->format('d/m/Y') : '-'),
                    'satelite' => $r->satelite,
                    'archivo_id' => $r->archivo_id,
                    'tamano_gb' => number_format((float) $r->tamano_gb, 4),
                    'cantidad_archivos' => (int) $r->cantidad_archivos,
                    'observaciones' => $r->observaciones ?: 'Sin observaciones',
                    'operador' => $r->user?->name,
                ];
            });

        return response()->json([
            'success' => true,
            'responsable' => $responsable,
            'is_bot' => strtolower($responsable) === 'bot_pss',
            'total_gb' => number_format($totalGb, 2),
            'total_tb' => number_format($totalGb / 1024, 2),
            'porcentaje_global' => $porcentaje,
            'total_archivos' => number_format($totalArchivos),
            'total_registros' => $totalRegistros,
            'fecha_inicio' => $minFecha ? (is_string($minFecha) ? substr($minFecha, 0, 10) : $minFecha->format('d/m/Y')) : null,
            'fecha_fin' => $maxFecha ? (is_string($maxFecha) ? substr($maxFecha, 0, 10) : $maxFecha->format('d/m/Y')) : null,
            'satelites' => $satBreakdown,
            'recent_records' => $recentRecords,
            'filter_url' => route('nas-migracion.estadisticas.index', ['responsable' => $responsable]),
        ]);
    }

    /**
     * Registra un nuevo ejecutor o una nueva guardia/lote de migración.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'responsable' => 'required|string|max:150',
            'fecha' => 'required|date',
            'satelite' => 'required|in:VRSS-1,VRSS-2',
            'modo_registro' => 'required|in:lote,individual',
            'tamano_gb' => 'required|numeric|min:0.0001|max:999999',
            'cantidad_archivos' => 'nullable|integer|min:1|max:50000',
            'archivo_id' => 'nullable|string|max:150',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $defaultTask = NasMigrationTask::first();

        // Determinar correlativo para este ejecutor
        $maxLote = NasMigrationStat::where('responsable', $validated['responsable'])->max('lote_numero') ?: 0;

        if ($validated['modo_registro'] === 'individual') {
            $archivoId = $validated['archivo_id'] ?: 'IMG_' . date('Ymd_His');
            $cantidad = 1;
        } else {
            $cantidad = $validated['cantidad_archivos'] ?: 1;
            $archivoId = $validated['archivo_id'] ?: "LOTE_{$cantidad}_ARCHIVOS";
        }

        $stat = NasMigrationStat::create([
            'responsable' => trim($validated['responsable']),
            'fecha' => $validated['fecha'],
            'satelite' => $validated['satelite'],
            'archivo_id' => $archivoId,
            'tamano_gb' => (float) $validated['tamano_gb'],
            'lote_numero' => $maxLote + 1,
            'cantidad_archivos' => $cantidad,
            'observaciones' => $validated['observaciones'] ?: 'Registro manual de guardia',
            'nas_migration_task_id' => $defaultTask ? $defaultTask->id : null,
            'user_id' => auth()->id(),
        ]);

        AuditService::log([
            'modulo' => 'Migración NAS',
            'tabla' => 'nas_migration_stats',
            'accion' => 'CREAR',
            'registro_id' => $stat->id,
            'registro_codigo' => $stat->archivo_id,
            'registro_nombre' => "Estadística Guardia: {$stat->responsable} ({$stat->tamano_gb} GB)",
            'data_nueva' => $stat->toArray(),
        ]);

        return redirect()->route('nas-migracion.estadisticas.index')
            ->with('success', "Ejecutor '{$stat->responsable}' y sus datos de guardia registrados exitosamente ({$stat->tamano_gb} GB migrados).");
    }

    /**
     * Elimina un registro de estadística si fue cargado por error.
     */
    public function destroy(NasMigrationStat $stat): RedirectResponse
    {
        $backup = $stat->toArray();
        $resp = $stat->responsable;
        $gb = $stat->tamano_gb;

        $stat->delete();

        AuditService::log([
            'modulo' => 'Migración NAS',
            'tabla' => 'nas_migration_stats',
            'accion' => 'ELIMINAR',
            'registro_id' => $backup['id'],
            'registro_codigo' => $backup['archivo_id'],
            'registro_nombre' => "Eliminada Guardia de {$resp} ({$gb} GB)",
            'data_anterior' => $backup,
        ]);

        return redirect()->route('nas-migracion.estadisticas.index')
            ->with('success', "Registro de {$resp} ({$gb} GB) eliminado correctamente.");
    }

    /**
     * Exporta los datos de estadísticas a formato CSV con codificación UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = NasMigrationStat::orderBy('fecha')->orderBy('id');

        if ($request->filled('responsable') && $request->responsable !== 'all') {
            $query->where('responsable', $request->responsable);
        }

        if ($request->filled('satelite') && $request->satelite !== 'all') {
            $query->where('satelite', $request->satelite);
        }

        $tipo = $request->input('tipo', 'general');

        if ($tipo === 'graficos') {
            $filename = 'Graficos_Estadisticas_Migracion_NAS_' . date('Ymd_His') . '.csv';

            return response()->streamDownload(function () {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");

                // SECCIÓN 1: RESUMEN POR RESPONSABLE (GRÁFICO 1)
                fputcsv($handle, ['=== 1. RESUMEN DE MIGRACIÓN POR RESPONSABLE (GRÁFICO DONUT) ==='], ';');
                fputcsv($handle, ['Responsable / Ejecutor', 'Total GB Migrados', '% sobre el Total', 'Archivos Migrados', 'Satélites Asignados'], ';');

                $totalGbGlobal = (float) NasMigrationStat::sum('tamano_gb');
                $responsables = NasMigrationStat::select(
                    'responsable',
                    DB::raw('SUM(tamano_gb) as total_gb'),
                    DB::raw('SUM(cantidad_archivos) as total_archivos')
                )->groupBy('responsable')->orderByDesc('total_gb')->get();

                foreach ($responsables as $r) {
                    $pct = $totalGbGlobal > 0 ? round(($r->total_gb / $totalGbGlobal) * 100, 2) : 0;
                    $sats = NasMigrationStat::where('responsable', $r->responsable)->distinct()->pluck('satelite')->implode(', ');
                    fputcsv($handle, [
                        $r->responsable,
                        number_format($r->total_gb, 4, ',', ''),
                        number_format($pct, 2, ',', '') . '%',
                        $r->total_archivos,
                        $sats
                    ], ';');
                }

                fputcsv($handle, [], ';');
                fputcsv($handle, ['TOTAL CONSOLIDADO', number_format($totalGbGlobal, 4, ',', ''), '100,00%', NasMigrationStat::sum('cantidad_archivos'), 'VRSS-1, VRSS-2'], ';');
                fputcsv($handle, [], ';');
                fputcsv($handle, [], ';');

                // SECCIÓN 2: DISTRIBUCIÓN POR SATÉLITE (GRÁFICO 2)
                fputcsv($handle, ['=== 2. DISTRIBUCIÓN POR SATÉLITE (VRSS-1 vs VRSS-2) ==='], ';');
                fputcsv($handle, ['Satélite', 'Total GB Migrados', '% sobre el Total', 'Total Archivos'], ';');

                $vrss1Gb = (float) NasMigrationStat::where('satelite', 'VRSS-1')->sum('tamano_gb');
                $vrss1Files = (int) NasMigrationStat::where('satelite', 'VRSS-1')->sum('cantidad_archivos');
                $vrss1Pct = $totalGbGlobal > 0 ? round(($vrss1Gb / $totalGbGlobal) * 100, 2) : 0;

                $vrss2Gb = (float) NasMigrationStat::where('satelite', 'VRSS-2')->sum('tamano_gb');
                $vrss2Files = (int) NasMigrationStat::where('satelite', 'VRSS-2')->sum('cantidad_archivos');
                $vrss2Pct = $totalGbGlobal > 0 ? round(($vrss2Gb / $totalGbGlobal) * 100, 2) : 0;

                fputcsv($handle, ['VRSS-1', number_format($vrss1Gb, 4, ',', ''), number_format($vrss1Pct, 2, ',', '') . '%', $vrss1Files], ';');
                fputcsv($handle, ['VRSS-2', number_format($vrss2Gb, 4, ',', ''), number_format($vrss2Pct, 2, ',', '') . '%', $vrss2Files], ';');
                fputcsv($handle, [], ';');
                fputcsv($handle, [], ';');

                // SECCIÓN 3: SERIE TEMPORAL POR DÍA
                fputcsv($handle, ['=== 3. EVOLUCIÓN DIARIA DE MIGRACIÓN (GB POR DÍA) ==='], ';');
                fputcsv($handle, ['Fecha', 'VRSS-1 (GB)', 'VRSS-2 (GB)', 'Total Día (GB)', 'Archivos del Día'], ';');

                $allStats = NasMigrationStat::all();
                $byDay = $allStats->groupBy(fn($i) => is_string($i->fecha) ? substr($i->fecha, 0, 10) : $i->fecha->format('Y-m-d'))->sortKeys();

                foreach ($byDay as $d => $items) {
                    $v1 = (float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb');
                    $v2 = (float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb');
                    $totDia = $v1 + $v2;
                    $filesDia = (int) $items->sum('cantidad_archivos');
                    fputcsv($handle, [
                        $d,
                        number_format($v1, 4, ',', ''),
                        number_format($v2, 4, ',', ''),
                        number_format($totDia, 4, ',', ''),
                        $filesDia
                    ], ';');
                }

                fputcsv($handle, [], ';');
                fputcsv($handle, [], ';');

                // SECCIÓN 4: SERIE TEMPORAL POR SEMANA
                fputcsv($handle, ['=== 4. EVOLUCIÓN SEMANAL DE MIGRACIÓN ==='], ';');
                fputcsv($handle, ['Semana (Año-S)', 'Rango de Fechas', 'VRSS-1 (GB)', 'VRSS-2 (GB)', 'Total Semana (GB)', 'Archivos Semana'], ';');

                $byWeek = $allStats->groupBy(function($item) {
                    $c = \Carbon\Carbon::parse($item->fecha);
                    return $c->isoWeekYear() . '-S' . str_pad($c->isoWeek(), 2, '0', STR_PAD_LEFT);
                })->sortKeys();

                foreach ($byWeek as $w => $items) {
                    $min = $items->min('fecha');
                    $max = $items->max('fecha');
                    $rango = date('d/m/Y', strtotime($min)) . ' - ' . date('d/m/Y', strtotime($max));
                    $v1 = (float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb');
                    $v2 = (float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb');
                    $totW = $v1 + $v2;
                    $filesW = (int) $items->sum('cantidad_archivos');
                    fputcsv($handle, [
                        $w,
                        $rango,
                        number_format($v1, 4, ',', ''),
                        number_format($v2, 4, ',', ''),
                        number_format($totW, 4, ',', ''),
                        $filesW
                    ], ';');
                }

                fputcsv($handle, [], ';');
                fputcsv($handle, [], ';');

                // SECCIÓN 5: SERIE TEMPORAL POR MES
                fputcsv($handle, ['=== 5. EVOLUCIÓN MENSUAL DE MIGRACIÓN ==='], ';');
                fputcsv($handle, ['Mes / Año', 'VRSS-1 (GB)', 'VRSS-2 (GB)', 'Total Mes (GB)', 'Archivos Mes'], ';');

                $byMonth = $allStats->groupBy(function($item) {
                    return substr(is_string($item->fecha) ? $item->fecha : $item->fecha->format('Y-m-d'), 0, 7);
                })->sortKeys();

                $mesesEsp = [
                    '01'=>'Enero', '02'=>'Febrero', '03'=>'Marzo', '04'=>'Abril', '05'=>'Mayo', '06'=>'Junio',
                    '07'=>'Julio', '08'=>'Agosto', '09'=>'Septiembre', '10'=>'Octubre', '11'=>'Noviembre', '12'=>'Diciembre'
                ];

                foreach ($byMonth as $m => $items) {
                    $p = explode('-', $m);
                    $mesNombre = ($mesesEsp[$p[1]] ?? $p[1]) . ' ' . $p[0];
                    $v1 = (float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb');
                    $v2 = (float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb');
                    $totM = $v1 + $v2;
                    $filesM = (int) $items->sum('cantidad_archivos');
                    fputcsv($handle, [
                        $mesNombre,
                        number_format($v1, 4, ',', ''),
                        number_format($v2, 4, ',', ''),
                        number_format($totM, 4, ',', ''),
                        $filesM
                    ], ';');
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        $filename = 'Estadisticas_Migracion_NAS_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM para que Excel abra acentos y símbolos correctamente
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '#',
                'Responsable / Ejecutor',
                'Fecha',
                'Satelite',
                'ID del Archivo',
                'Tamano (GB)',
                'Cantidad Archivos',
                'Observaciones'
            ], ';');

            $i = 1;
            $query->chunk(500, function ($rows) use ($handle, &$i) {
                foreach ($rows as $r) {
                    fputcsv($handle, [
                        $i++,
                        $r->responsable,
                        is_string($r->fecha) ? substr($r->fecha, 0, 10) : $r->fecha->format('Y-m-d'),
                        $r->satelite,
                        $r->archivo_id,
                        number_format($r->tamano_gb, 4, ',', ''),
                        $r->cantidad_archivos,
                        $r->observaciones
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
