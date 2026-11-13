<?php

namespace App\Services;

use App\Models\NasMigrationStat;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NasStatsExcelExporter
{
    /**
     * Genera un libro de Excel (.xlsx) nativo con tablas estilizadas y gráficos incrustados.
     */
    public static function exportWithCharts(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // -------------------------------------------------------------
        // HOJA 1: RESUMEN GENERAL & DONUTS (Responsable y Satélite)
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Resumen y Satélites');
        $sheet1->setShowGridLines(true);

        // Estilos base
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']], // Azul oscuro
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];
        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D9488']], // Teal
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];
        $totalStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
        ];

        // 1.1 Tabla Responsables
        $sheet1->setCellValue('A1', '1. RESUMEN DE MIGRACIÓN POR RESPONSABLE');
        $sheet1->mergeCells('A1:D1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet1->fromArray([
            ['Responsable / Ejecutor', 'Total GB Migrados', '% del Total', 'Archivos Migrados']
        ], null, 'A2');
        $sheet1->getStyle('A2:D2')->applyFromArray($headerStyle);

        $totalGbGlobal = (float) NasMigrationStat::sum('tamano_gb');
        $responsables = NasMigrationStat::select(
            'responsable',
            DB::raw('SUM(tamano_gb) as total_gb'),
            DB::raw('SUM(cantidad_archivos) as total_archivos')
        )->groupBy('responsable')->orderByDesc('total_gb')->get();

        $rRow = 3;
        foreach ($responsables as $r) {
            $pct = $totalGbGlobal > 0 ? round(($r->total_gb / $totalGbGlobal), 4) : 0;
            $sheet1->setCellValue("A{$rRow}", $r->responsable);
            $sheet1->setCellValue("B{$rRow}", (float) $r->total_gb);
            $sheet1->setCellValue("C{$rRow}", $pct);
            $sheet1->setCellValue("D{$rRow}", (int) $r->total_archivos);

            $sheet1->getStyle("B{$rRow}")->getNumberFormat()->setFormatCode('#,##0.00 "GB"');
            $sheet1->getStyle("C{$rRow}")->getNumberFormat()->setFormatCode('0.00%');
            $sheet1->getStyle("D{$rRow}")->getNumberFormat()->setFormatCode('#,##0');
            $rRow++;
        }
        $rEndRow = $rRow - 1;

        // Fila Total Responsables
        $sheet1->setCellValue("A{$rRow}", 'TOTAL GENERAL');
        $sheet1->setCellValue("B{$rRow}", "=SUM(B3:B{$rEndRow})");
        $sheet1->setCellValue("C{$rRow}", 1.0);
        $sheet1->setCellValue("D{$rRow}", "=SUM(D3:D{$rEndRow})");
        $sheet1->getStyle("A{$rRow}:D{$rRow}")->applyFromArray($totalStyle);
        $sheet1->getStyle("B{$rRow}")->getNumberFormat()->setFormatCode('#,##0.00 "GB"');
        $sheet1->getStyle("C{$rRow}")->getNumberFormat()->setFormatCode('0.00%');
        $sheet1->getStyle("D{$rRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Gráfico Donut de Responsables
        if ($rEndRow >= 3) {
            $catResp = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Resumen y Satélites'!\$A\$3:\$A\${$rEndRow}", null, $rEndRow - 2)];
            $lblResp = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Resumen y Satélites'!\$B\$2", null, 1)];
            $valResp = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Resumen y Satélites'!\$B\$3:\$B\${$rEndRow}", null, $rEndRow - 2)];

            $seriesResp = new DataSeries(
                DataSeries::TYPE_DOUGHNUTCHART,
                null,
                range(0, count($valResp) - 1),
                $lblResp,
                $catResp,
                $valResp
            );
            $plotResp = new PlotArea(null, [$seriesResp]);
            $legendResp = new Legend(Legend::POSITION_RIGHT, null, false);
            $titleResp = new Title('Porcentaje de GB Migrados por Responsable');
            $chartResp = new Chart('chart_donut_responsable', $titleResp, $legendResp, $plotResp, true, DataSeries::EMPTY_AS_GAP);
            $chartResp->setTopLeftPosition('F2');
            $chartResp->setBottomRightPosition('M16');
            $sheet1->addChart($chartResp);
        }

        // 1.2 Tabla Satélites
        $satStartRow = max($rRow + 4, 18);
        $sheet1->setCellValue("A{$satStartRow}", '2. DISTRIBUCIÓN POR SATÉLITE');
        $sheet1->mergeCells("A{$satStartRow}:D{$satStartRow}");
        $sheet1->getStyle("A{$satStartRow}")->getFont()->setBold(true)->setSize(12);

        $satHRow = $satStartRow + 1;
        $sheet1->fromArray([
            ['Satélite', 'Total GB Migrados', '% del Total', 'Total Archivos']
        ], null, "A{$satHRow}");
        $sheet1->getStyle("A{$satHRow}:D{$satHRow}")->applyFromArray($subHeaderStyle);

        $vrss1Gb = (float) NasMigrationStat::where('satelite', 'VRSS-1')->sum('tamano_gb');
        $vrss1Files = (int) NasMigrationStat::where('satelite', 'VRSS-1')->sum('cantidad_archivos');
        $vrss1Pct = $totalGbGlobal > 0 ? ($vrss1Gb / $totalGbGlobal) : 0;

        $vrss2Gb = (float) NasMigrationStat::where('satelite', 'VRSS-2')->sum('tamano_gb');
        $vrss2Files = (int) NasMigrationStat::where('satelite', 'VRSS-2')->sum('cantidad_archivos');
        $vrss2Pct = $totalGbGlobal > 0 ? ($vrss2Gb / $totalGbGlobal) : 0;

        $sRow1 = $satHRow + 1;
        $sheet1->setCellValue("A{$sRow1}", 'VRSS-1');
        $sheet1->setCellValue("B{$sRow1}", $vrss1Gb);
        $sheet1->setCellValue("C{$sRow1}", $vrss1Pct);
        $sheet1->setCellValue("D{$sRow1}", $vrss1Files);

        $sRow2 = $satHRow + 2;
        $sheet1->setCellValue("A{$sRow2}", 'VRSS-2');
        $sheet1->setCellValue("B{$sRow2}", $vrss2Gb);
        $sheet1->setCellValue("C{$sRow2}", $vrss2Pct);
        $sheet1->setCellValue("D{$sRow2}", $vrss2Files);

        foreach ([$sRow1, $sRow2] as $sr) {
            $sheet1->getStyle("B{$sr}")->getNumberFormat()->setFormatCode('#,##0.00 "GB"');
            $sheet1->getStyle("C{$sr}")->getNumberFormat()->setFormatCode('0.00%');
            $sheet1->getStyle("D{$sr}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Gráfico Donut Satélites
        $catSat = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Resumen y Satélites'!\$A\${$sRow1}:\$A\${$sRow2}", null, 2)];
        $lblSat = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Resumen y Satélites'!\$B\${$satHRow}", null, 1)];
        $valSat = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Resumen y Satélites'!\$B\${$sRow1}:\$B\${$sRow2}", null, 2)];

        $seriesSat = new DataSeries(
            DataSeries::TYPE_DOUGHNUTCHART,
            null,
            range(0, count($valSat) - 1),
            $lblSat,
            $catSat,
            $valSat
        );
        $plotSat = new PlotArea(null, [$seriesSat]);
        $legendSat = new Legend(Legend::POSITION_RIGHT, null, false);
        $titleSat = new Title('Distribución por Satélite (VRSS-1 vs VRSS-2)');
        $chartSat = new Chart('chart_donut_satelite', $titleSat, $legendSat, $plotSat, true, DataSeries::EMPTY_AS_GAP);
        $chartSat->setTopLeftPosition("F{$satStartRow}");
        $chartSat->setBottomRightPosition("M" . ($satStartRow + 14));
        $sheet1->addChart($chartSat);

        // Anchos de columna automáticos
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // HOJA 2: RITMO DIARIO DE MIGRACIÓN
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ritmo Diario');
        $sheet2->setShowGridLines(true);

        $sheet2->setCellValue('A1', 'RITMO DIARIO DE MIGRACIÓN (GB POR DÍA)');
        $sheet2->mergeCells('A1:E1');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet2->fromArray([
            ['Fecha', 'VRSS-1 (GB)', 'VRSS-2 (GB)', 'Total Día (GB)', 'Archivos']
        ], null, 'A2');
        $sheet2->getStyle('A2:E2')->applyFromArray($headerStyle);

        $allStats = NasMigrationStat::all();
        $byDay = $allStats->groupBy(fn($i) => is_string($i->fecha) ? substr($i->fecha, 0, 10) : $i->fecha->format('Y-m-d'))->sortKeys();

        $dRow = 3;
        foreach ($byDay as $d => $items) {
            $v1 = (float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb');
            $v2 = (float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb');
            $filesDia = (int) $items->sum('cantidad_archivos');

            $sheet2->setCellValue("A{$dRow}", $d);
            $sheet2->setCellValue("B{$dRow}", $v1);
            $sheet2->setCellValue("C{$dRow}", $v2);
            $sheet2->setCellValue("D{$dRow}", "=B{$dRow}+C{$dRow}");
            $sheet2->setCellValue("E{$dRow}", $filesDia);

            $sheet2->getStyle("B{$dRow}:D{$dRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet2->getStyle("E{$dRow}")->getNumberFormat()->setFormatCode('#,##0');
            $dRow++;
        }
        $dEndRow = $dRow - 1;

        if ($dEndRow >= 3) {
            $catDaily = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ritmo Diario'!\$A\$3:\$A\${$dEndRow}", null, $dEndRow - 2)];
            $lblDaily = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ritmo Diario'!\$B\$2", null, 1),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Ritmo Diario'!\$C\$2", null, 1),
            ];
            $valDaily = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Ritmo Diario'!\$B\$3:\$B\${$dEndRow}", null, $dEndRow - 2),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Ritmo Diario'!\$C\$3:\$C\${$dEndRow}", null, $dEndRow - 2),
            ];

            $seriesDaily = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STACKED,
                range(0, count($valDaily) - 1),
                $lblDaily,
                $catDaily,
                $valDaily
            );
            $seriesDaily->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotDaily = new PlotArea(null, [$seriesDaily]);
            $legendDaily = new Legend(Legend::POSITION_TOP, null, false);
            $titleDaily = new Title('Ritmo Diario de Migración (VRSS-1 vs VRSS-2)');
            $chartDaily = new Chart('chart_ritmo_diario', $titleDaily, $legendDaily, $plotDaily, true, DataSeries::EMPTY_AS_GAP);
            $chartDaily->setTopLeftPosition('G2');
            $chartDaily->setBottomRightPosition('P18');
            $sheet2->addChart($chartDaily);
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // HOJA 3: EVOLUCIÓN SEMANAL (Global y Por Operador)
        // -------------------------------------------------------------
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Evolución Semanal');
        $sheet3->setShowGridLines(true);

        $sheet3->setCellValue('A1', 'EVOLUCIÓN SEMANAL SEGÚN RESPONSABLE (GB POR SEMANA)');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $allResponsables = $responsables->pluck('responsable')->values()->toArray();
        $headerWeekly = array_merge(['Semana', 'Rango Fechas'], $allResponsables, ['Total Semana (GB)']);

        $sheet3->fromArray([$headerWeekly], null, 'A2');
        $lastWeeklyColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headerWeekly));
        $sheet3->getStyle("A2:{$lastWeeklyColLetter}2")->applyFromArray($headerStyle);

        $byWeek = $allStats->groupBy(function ($item) {
            $c = \Carbon\Carbon::parse($item->fecha);
            return $c->isoWeekYear() . '-S' . str_pad($c->isoWeek(), 2, '0', STR_PAD_LEFT);
        })->sortKeys();

        $wRow = 3;
        foreach ($byWeek as $w => $items) {
            $min = $items->min('fecha');
            $max = $items->max('fecha');
            $rango = date('d/m', strtotime($min)) . ' - ' . date('d/m', strtotime($max));

            $sheet3->setCellValue("A{$wRow}", $w);
            $sheet3->setCellValue("B{$wRow}", $rango);

            $colIdx = 3; // Columna C
            $firstRespCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            foreach ($allResponsables as $rName) {
                $gbR = round((float) $items->where('responsable', $rName)->sum('tamano_gb'), 2);
                $currCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
                $sheet3->setCellValue("{$currCol}{$wRow}", $gbR);
                $sheet3->getStyle("{$currCol}{$wRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $colIdx++;
            }
            $lastRespCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx - 1);
            $totalCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);

            $sheet3->setCellValue("{$totalCol}{$wRow}", "=SUM({$firstRespCol}{$wRow}:{$lastRespCol}{$wRow})");
            $sheet3->getStyle("{$totalCol}{$wRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("{$totalCol}{$wRow}")->getFont()->setBold(true);

            $wRow++;
        }
        $wEndRow = $wRow - 1;

        if ($wEndRow >= 3 && count($allResponsables) > 0) {
            $catWeekly = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Evolución Semanal'!\$A\$3:\$A\${$wEndRow}", null, $wEndRow - 2)];
            $lblWeekly = [];
            $valWeekly = [];

            $cIdx = 3;
            foreach ($allResponsables as $rName) {
                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx);
                $lblWeekly[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Evolución Semanal'!\${$cLetter}\$2", null, 1);
                $valWeekly[] = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Evolución Semanal'!\${$cLetter}\$3:\${$cLetter}\${$wEndRow}", null, $wEndRow - 2);
                $cIdx++;
            }

            $seriesWeekly = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STACKED,
                range(0, count($valWeekly) - 1),
                $lblWeekly,
                $catWeekly,
                $valWeekly
            );
            $seriesWeekly->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotWeekly = new PlotArea(null, [$seriesWeekly]);
            $legendWeekly = new Legend(Legend::POSITION_TOP, null, false);
            $titleWeekly = new Title('Evolución Semanal de Migración por Responsable');
            $chartWeekly = new Chart('chart_semanal_responsables', $titleWeekly, $legendWeekly, $plotWeekly, true, DataSeries::EMPTY_AS_GAP);

            $chartStartCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headerWeekly) + 2);
            $chartEndCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headerWeekly) + 12);
            $chartWeekly->setTopLeftPosition("{$chartStartCol}2");
            $chartWeekly->setBottomRightPosition("{$chartEndCol}18");
            $sheet3->addChart($chartWeekly);
        }

        for ($c = 1; $c <= count($headerWeekly); $c++) {
            $colL = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet3->getColumnDimension($colL)->setAutoSize(true);
        }

        // -------------------------------------------------------------
        // HOJA 4: EVOLUCIÓN MENSUAL
        // -------------------------------------------------------------
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Evolución Mensual');
        $sheet4->setShowGridLines(true);

        $sheet4->setCellValue('A1', 'EVOLUCIÓN MENSUAL DE MIGRACIÓN (GB POR MES)');
        $sheet4->mergeCells('A1:E1');
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet4->fromArray([
            ['Mes / Año', 'VRSS-1 (GB)', 'VRSS-2 (GB)', 'Total Mes (GB)', 'Archivos']
        ], null, 'A2');
        $sheet4->getStyle('A2:E2')->applyFromArray($subHeaderStyle);

        $byMonth = $allStats->groupBy(function ($item) {
            return substr(is_string($item->fecha) ? $item->fecha : $item->fecha->format('Y-m-d'), 0, 7);
        })->sortKeys();

        $mesesEsp = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];

        $mRow = 3;
        foreach ($byMonth as $m => $items) {
            $p = explode('-', $m);
            $mesNombre = ($mesesEsp[$p[1]] ?? $p[1]) . ' ' . $p[0];
            $v1 = (float) $items->where('satelite', 'VRSS-1')->sum('tamano_gb');
            $v2 = (float) $items->where('satelite', 'VRSS-2')->sum('tamano_gb');
            $filesM = (int) $items->sum('cantidad_archivos');

            $sheet4->setCellValue("A{$mRow}", $mesNombre);
            $sheet4->setCellValue("B{$mRow}", $v1);
            $sheet4->setCellValue("C{$mRow}", $v2);
            $sheet4->setCellValue("D{$mRow}", "=B{$mRow}+C{$mRow}");
            $sheet4->setCellValue("E{$mRow}", $filesM);

            $sheet4->getStyle("B{$mRow}:D{$mRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet4->getStyle("E{$mRow}")->getNumberFormat()->setFormatCode('#,##0');
            $mRow++;
        }
        $mEndRow = $mRow - 1;

        if ($mEndRow >= 3) {
            $catMonthly = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Evolución Mensual'!\$A\$3:\$A\${$mEndRow}", null, $mEndRow - 2)];
            $lblMonthly = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Evolución Mensual'!\$B\$2", null, 1),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Evolución Mensual'!\$C\$2", null, 1),
            ];
            $valMonthly = [
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Evolución Mensual'!\$B\$3:\$B\${$mEndRow}", null, $mEndRow - 2),
                new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Evolución Mensual'!\$C\$3:\$C\${$mEndRow}", null, $mEndRow - 2),
            ];

            $seriesMonthly = new DataSeries(
                DataSeries::TYPE_BARCHART,
                DataSeries::GROUPING_STACKED,
                range(0, count($valMonthly) - 1),
                $lblMonthly,
                $catMonthly,
                $valMonthly
            );
            $seriesMonthly->setPlotDirection(DataSeries::DIRECTION_COL);

            $plotMonthly = new PlotArea(null, [$seriesMonthly]);
            $legendMonthly = new Legend(Legend::POSITION_TOP, null, false);
            $titleMonthly = new Title('Ritmo Mensual de Migración');
            $chartMonthly = new Chart('chart_mensual_migracion', $titleMonthly, $legendMonthly, $plotMonthly, true, DataSeries::EMPTY_AS_GAP);
            $chartMonthly->setTopLeftPosition('G2');
            $chartMonthly->setBottomRightPosition('P18');
            $sheet4->addChart($chartMonthly);
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet4->getColumnDimension($col)->setAutoSize(true);
        }

        // Devolver la primera hoja activa al abrir el Excel
        $spreadsheet->setActiveSheetIndex(0);

        // Salida a Streaming Response con Xlsx
        $filename = 'Graficos_Estadisticas_Migracion_NAS_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
