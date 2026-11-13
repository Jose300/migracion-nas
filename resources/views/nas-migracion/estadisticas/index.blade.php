@extends('layouts.app')

@section('title', 'Estadísticas de Migración NAS')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    ALMACENAMIENTO &amp; INFRAESTRUCTURA / ANÁLISIS &amp; MÉTRICAS
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-chart-dots me-2 text-warning" style="font-size: 1.6rem;"></i>
                    Estadísticas de Migración NAS (Synology FAO &rarr; Inspur AS13000)
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                <a href="{{ route('nas-migracion.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="ti ti-arrows-transfer-down me-1"></i> Tareas de Sincronización
                </a>
                <a href="{{ route('nas-migracion.confirmacion.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="ti ti-checklist me-1"></i> Confirmación de Archivos
                </a>

                @can('exportar-estadisticas-nas')
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-file-spreadsheet me-1"></i> Exportar a Excel / CSV
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('nas-migracion.estadisticas.export', ['tipo' => 'graficos']) }}">
                                <i class="ti ti-chart-bar text-primary me-2 fs-3"></i>
                                <div>
                                    <div class="fw-bold">Exportar Métricas de Gráficos</div>
                                    <small class="text-muted">Por Responsable, Satélite, Días, Semanas y Meses</small>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('nas-migracion.estadisticas.export', request()->all()) }}">
                                <i class="ti ti-table text-success me-2 fs-3"></i>
                                <div>
                                    <div class="fw-bold">Exportar Detalle de Archivos</div>
                                    <small class="text-muted">Listado completo filtrado con observaciones</small>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
                @endcan

                @can('crear-estadisticas-nas')
                <button type="button" class="btn btn-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-nuevo-ejecutor">
                    <i class="ti ti-user-plus me-1 fs-3"></i> + Nuevo Ejecutor / Registrar Guardia
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Alertas Flash --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-circle-check fs-2 me-2"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        @endif

        {{-- Tarjetas de Estadísticas Principales --}}
        <div class="row row-cards g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0 border-start border-primary border-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-primary-lt text-primary rounded-circle">
                                    <i class="ti ti-database fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">TOTAL VOLUMEN MIGRADO</div>
                                <div class="h2 mb-0 fw-bold text-primary">
                                    {{ number_format($totalGb, 2) }} <span class="fs-4 text-muted fw-normal">GB</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="ti ti-server me-1"></i> {{ number_format($totalGb / 1024, 2) }} Terabytes
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm shadow-sm border-0 border-start border-success border-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-success-lt text-success rounded-circle">
                                    <i class="ti ti-files fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">TOTAL ARCHIVOS TRANSFERIDOS</div>
                                <div class="h2 mb-0 fw-bold text-success">
                                    {{ number_format($totalFiles) }} <span class="fs-4 text-muted fw-normal">archivos</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="ti ti-check me-1"></i> Registros consolidados
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm shadow-sm border-0 border-start border-warning border-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-warning-lt text-warning rounded-circle">
                                    <i class="ti ti-users fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">EJECUTORES</div>
                                <div class="h2 mb-0 fw-bold text-warning">
                                    {{ $totalExecutors }} <span class="fs-4 text-muted fw-normal">guardias</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="ti ti-user-check me-1"></i> Responsables
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm shadow-sm border-0 border-start border-info border-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-info-lt text-info rounded-circle">
                                    <i class="ti ti-satellite fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">SATÉLITE VRSS-1</div>
                                <div class="h2 mb-0 fw-bold text-info">
                                    {{ number_format($totalVrss1Gb, 2) }} <span class="fs-4 text-muted fw-normal">GB</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ number_format($totalVrss1Files) }} arch. ({{ $totalGb > 0 ? round(($totalVrss1Gb / $totalGb) * 100, 1) : 0 }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-2">
                <div class="card card-sm shadow-sm border-0 border-start border-teal border-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-teal-lt text-teal rounded-circle">
                                    <i class="ti ti-satellite fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.7rem;">SATÉLITE VRSS-2</div>
                                <div class="h2 mb-0 fw-bold text-teal">
                                    {{ number_format($totalVrss2Gb, 2) }} <span class="fs-4 text-muted fw-normal">GB</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    {{ number_format($totalVrss2Files) }} arch. ({{ $totalGb > 0 ? round(($totalVrss2Gb / $totalGb) * 100, 1) : 0 }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Gráficos Estadísticos Interactivos (ApexCharts) --}}
        <div class="row row-cards g-3 mb-4">
            {{-- Gráfico 1: Porcentaje de GB migrados por responsable (Exacto a Hoja Resumen) --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold d-flex align-items-center">
                            <i class="ti ti-chart-pie me-2 text-primary"></i> Porcentaje de GB Migrados por Responsable
                        </h3>
                        <span class="badge bg-blue-lt">Resumen General</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-executors-pie" style="min-height: 290px;"></div>
                    </div>
                </div>
            </div>

            {{-- Gráfico 2: Distribución por Satélite & Métricas Clave --}}
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h3 class="card-title fw-bold d-flex align-items-center">
                            <i class="ti ti-satellite me-2 text-teal"></i> Por Satélite
                        </h3>
                        <span class="badge bg-teal-lt">VRSS-1 vs VRSS-2</span>
                    </div>
                    <div class="card-body">
                        <div id="chart-satellite-donut" style="min-height: 290px;"></div>
                    </div>
                </div>
            </div>

            {{-- Gráfico 3: Evolución Temporal de Migración (Diario, Semanal, Mensual) --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-1 py-2">
                        <h3 class="card-title fw-bold d-flex align-items-center mb-0" id="timeline-card-title">
                            <i class="ti ti-chart-bar me-2 text-azure"></i> Ritmo de Migración
                        </h3>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Periodicidad de Migración">
                            <button type="button" class="btn btn-outline-primary active btn-timeline-period" data-period="daily" title="Ver por Día">
                                Diario
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-timeline-period" data-period="weekly" title="Ver por Semana">
                                Semanal
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-timeline-period" data-period="monthly" title="Ver por Mes">
                                Mensual
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="chart-daily-timeline" style="min-height: 290px;"></div>
                    </div>
                </div>
            </div>

            {{-- Gráfico 4: Evolución de Migración según Responsable con Filtros (Día, Semana, Mes y Filtro por Responsable) --}}
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                        <div>
                            <h3 class="card-title fw-bold d-flex align-items-center mb-0" id="chart-resp-card-title">
                                <i class="ti ti-chart-arrows-vertical me-2 text-warning fs-2"></i> Evolución de Migración por Responsable
                            </h3>
                            <small class="text-secondary" id="chart-resp-card-subtitle">Comparativa de volumen transferido (GB) por cada operador y bot_pss por período</small>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            {{-- Filtro por Responsable específico --}}
                            <div class="d-flex align-items-center gap-1">
                                <label class="text-secondary small fw-bold text-nowrap"><i class="ti ti-user me-1"></i>Responsable:</label>
                                <select id="select-filter-chart-resp" class="form-select form-select-sm" style="min-width: 170px;">
                                    <option value="all" selected>Todos los Responsables</option>
                                    @foreach($availableExecutors as $exec)
                                        <option value="{{ $exec }}">{{ $exec }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filtro por Período: Día, Semana, Mes --}}
                            <div class="btn-group btn-group-sm" role="group" aria-label="Periodicidad por Responsable">
                                <button type="button" class="btn btn-outline-warning btn-resp-period" data-period="daily" title="Ver por Día">
                                    Diario
                                </button>
                                <button type="button" class="btn btn-outline-warning active btn-resp-period" data-period="weekly" title="Ver por Semana">
                                    Semanal
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-resp-period" data-period="monthly" title="Ver por Mes">
                                    Mensual
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="chart-weekly-responsables" style="min-height: 340px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla: Resumen por Responsable (Reflejo fiel de la Hoja de Excel) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title fw-bold d-flex align-items-center">
                        <i class="ti ti-table me-2 text-primary"></i> Total por Responsable (Cuadro de Guardia)
                    </h3>
                    <p class="text-secondary small mb-0">
                        Cálculo automático de volumen migrado, porcentaje sobre el total y satélites asignados.
                    </p>
                </div>
                @can('crear-estadisticas-nas')
                <button type="button" class="btn btn-outline-success btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modal-nuevo-ejecutor">
                    <i class="ti ti-plus me-1"></i> Agregar Ejecutor
                </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 28%;">Responsable / Ejecutor</th>
                            <th class="text-end" style="width: 15%;">GB Migrados</th>
                            <th style="width: 25%;">% del Total</th>
                            <th class="text-center" style="width: 10%;">Archivos</th>
                            <th class="text-center" style="width: 12%;">Satélites</th>
                            <th class="text-center" style="width: 10%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($responsablesStats as $index => $resp)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if(strtolower($resp->responsable) === 'bot_pss')
                                    <span class="avatar avatar-sm me-2 bg-purple-lt fw-bold" title="Tarea Programada Automatizada">
                                        <i class="ti ti-robot"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold text-purple d-flex align-items-center">
                                            {{ $resp->responsable }}
                                            <span class="badge bg-purple-lt ms-2" style="font-size: 0.65rem;">Automático</span>
                                        </div>
                                        <div class="text-secondary small">
                                            Tarea programada (daemon / cron)
                                        </div>
                                    </div>
                                    @else
                                    <span class="avatar avatar-sm me-2 bg-primary-lt fw-bold">
                                        {{ strtoupper(substr($resp->responsable, 0, 2)) }}
                                    </span>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $resp->responsable }}</div>
                                        <div class="text-secondary small">
                                            @if($resp->fecha_inicio && $resp->fecha_fin)
                                                {{ is_string($resp->fecha_inicio) ? substr($resp->fecha_inicio, 0, 10) : $resp->fecha_inicio->format('d/m/Y') }} 
                                                &rarr; 
                                                {{ is_string($resp->fecha_fin) ? substr($resp->fecha_fin, 0, 10) : $resp->fecha_fin->format('d/m/Y') }}
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold font-monospace fs-4 text-primary">
                                    {{ number_format($resp->total_gb, 2) }}
                                </span>
                                <span class="text-muted small">GB</span>
                                <div class="text-secondary" style="font-size: 0.72rem;">
                                    ({{ number_format($resp->total_gb / 1024, 2) }} TB)
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress progress-sm flex-grow-1 me-2" style="height: 8px;">
                                        <div class="progress-bar bg-primary" style="width: {{ $resp->porcentaje }}%" role="progressbar"></div>
                                    </div>
                                    <span class="fw-bold font-monospace text-dark" style="min-width: 48px;">
                                        {{ $resp->porcentaje }}%
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-lt fw-bold font-monospace">
                                    {{ number_format($resp->total_archivos) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @foreach($resp->satelites as $sat)
                                    <span class="badge {{ $sat === 'VRSS-1' ? 'bg-info-lt' : 'bg-teal-lt' }} me-1 font-monospace">
                                        {{ $sat }}
                                    </span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary btn-ver-resumen-responsable" 
                                        data-responsable="{{ $resp->responsable }}"
                                        title="Ver información resumida de este ejecutor">
                                    <i class="ti ti-eye me-1"></i> Ver Datos
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-secondary">
                                No se encontraron ejecutores registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td>TOTALES CONSOLIDADOS:</td>
                            <td class="text-end text-primary font-monospace fs-4">
                                {{ number_format($totalGb, 2) }} <span class="fs-5 text-muted fw-normal">GB</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress progress-sm flex-grow-1 me-2" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: 100%" role="progressbar"></div>
                                    </div>
                                    <span class="fw-bold font-monospace text-success">100.00%</span>
                                </div>
                            </td>
                            <td class="text-center font-monospace">
                                {{ number_format($totalFiles) }}
                            </td>
                            <td class="text-center text-muted small">Todos</td>
                            <td class="text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Tabla de Registros Detallados con Filtros y Buscador --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="card-title fw-bold d-flex align-items-center">
                        <i class="ti ti-list-details me-2 text-primary"></i> Detalle de Archivos y Guardias Migradas
                    </h3>
                    <p class="text-secondary small mb-0">
                        Historial individual de archivos transferidos con tamaño exacto en GB, satélite y responsable.
                    </p>
                </div>
                <div>
                    @if(request()->anyFilled(['responsable', 'satelite', 'fecha_desde', 'fecha_hasta', 'search']))
                        <a href="{{ route('nas-migracion.estadisticas.index') }}" class="btn btn-outline-danger btn-sm fw-bold">
                            <i class="ti ti-x me-1"></i> Limpiar Filtros
                        </a>
                    @endif
                </div>
            </div>

            {{-- Barra de Filtros --}}
            <div class="card-body border-bottom bg-light py-2">
                <form action="{{ route('nas-migracion.estadisticas.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Buscar por ID de archivo...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="responsable" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all">Todos los Responsables</option>
                            @foreach($availableExecutors as $exec)
                                <option value="{{ $exec }}" {{ request('responsable') === $exec ? 'selected' : '' }}>
                                    {{ $exec }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="satelite" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="all">Todos los Satélites</option>
                            <option value="VRSS-1" {{ request('satelite') === 'VRSS-1' ? 'selected' : '' }}>VRSS-1</option>
                            <option value="VRSS-2" {{ request('satelite') === 'VRSS-2' ? 'selected' : '' }}>VRSS-2</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}" title="Fecha desde" placeholder="Desde">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm fw-bold flex-grow-1">
                            <i class="ti ti-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter table-striped card-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Responsable</th>
                            <th>Fecha</th>
                            <th>Satélite</th>
                            <th>ID del Archivo</th>
                            <th class="text-end">Tamaño (GB)</th>
                            <th>Observaciones</th>
                            @can('crear-estadisticas-nas')
                            <th class="text-end" style="width: 70px;">Acción</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $index => $row)
                        <tr>
                            <td class="text-secondary font-monospace">
                                {{ $records->firstItem() + $index }}
                            </td>
                            <td class="fw-bold text-dark">
                                @if(strtolower($row->responsable) === 'bot_pss')
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-purple-lt text-purple fw-bold me-1">bot_pss</span>
                                        @if($row->user)
                                            <span class="badge bg-light text-dark border small fw-normal" title="Operador que ejecutó la tarea">
                                                <i class="ti ti-user me-1 text-primary"></i>{{ $row->user->name }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    {{ $row->responsable }}
                                @endif
                            </td>
                            <td class="text-secondary small">
                                <i class="ti ti-calendar me-1"></i>
                                {{ is_string($row->fecha) ? substr($row->fecha, 0, 10) : $row->fecha->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="badge {{ $row->satelite === 'VRSS-1' ? 'bg-info-lt' : 'bg-teal-lt' }} font-monospace fw-bold">
                                    {{ $row->satelite }}
                                </span>
                            </td>
                            <td>
                                <code class="text-primary fw-bold">{{ $row->archivo_id ?: 'N/A' }}</code>
                                @if($row->cantidad_archivos > 1)
                                    <span class="badge bg-secondary-lt ms-1">{{ $row->cantidad_archivos }} archivos</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="fw-bold font-monospace text-dark">{{ number_format($row->tamano_gb, 4) }}</span>
                                <span class="text-muted small">GB</span>
                            </td>
                            <td class="text-secondary small text-truncate" style="max-width: 250px;">
                                {{ $row->observaciones ?: '-' }}
                            </td>
                            @can('crear-estadisticas-nas')
                            <td class="text-end">
                                <form action="{{ route('nas-migracion.estadisticas.destroy', $row) }}" method="POST" class="d-inline form-delete-stat">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn btn-icon btn-sm btn-outline-danger btn-delete-stat" 
                                            data-name="{{ $row->responsable }} - {{ $row->tamano_gb }} GB"
                                            title="Eliminar este registro">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user() && auth()->user()->can('crear-estadisticas-nas') ? 8 : 7 }}" class="text-center py-4 text-secondary">
                                No se encontraron registros coincidentes con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.datatable-pagination', ['paginator' => $records, 'label' => 'registros'])
        </div>

    </div>
</div>

{{-- Modal: Agregar Nuevo Ejecutor / Registrar Guardia --}}
@can('crear-estadisticas-nas')
<div class="modal fade" id="modal-nuevo-ejecutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('nas-migracion.estadisticas.store') }}" method="POST" id="form-nuevo-ejecutor">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-user-plus me-2 text-success"></i> Registrar Nuevo Ejecutor o Guardia de Migración
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" style="font-size: 0.85rem;">
                        <i class="ti ti-info-circle me-1"></i> Puedes escribir el nombre de un <b>nuevo ejecutor</b> (se agregará automáticamente a las gráficas y resúmenes) o seleccionar uno de los existentes.
                    </div>

                    <div class="row g-3">
                        {{-- Nombre del Ejecutor / Responsable --}}
                        <div class="col-md-6">
                            <label class="form-label required">Nombre del Responsable / Ejecutor</label>
                            <input type="text" 
                                   name="responsable" 
                                   id="input-responsable" 
                                   class="form-control" 
                                   list="executors-datalist" 
                                   placeholder="Ej. Carlos Mendoza, Dayana Sanchez..." 
                                   required>
                            <datalist id="executors-datalist">
                                @foreach($availableExecutors as $exec)
                                    <option value="{{ $exec }}"></option>
                                @endforeach
                            </datalist>
                            <small class="text-secondary">Escribe un nombre nuevo o selecciona uno ya registrado.</small>
                        </div>

                        {{-- Fecha de la Guardia --}}
                        <div class="col-md-3">
                            <label class="form-label required">Fecha de Guardia</label>
                            <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Satélite --}}
                        <div class="col-md-3">
                            <label class="form-label required">Satélite Operado</label>
                            <select name="satelite" class="form-select" required>
                                <option value="VRSS-1">VRSS-1</option>
                                <option value="VRSS-2" selected>VRSS-2</option>
                            </select>
                        </div>

                        {{-- Modo de Carga --}}
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Tipo de Registro de la Guardia</label>
                            <div class="form-selectgroup form-selectgroup-pills">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="modo_registro" value="lote" class="form-selectgroup-input" checked id="radio-modo-lote">
                                    <span class="form-selectgroup-label">
                                        <i class="ti ti-folders me-1"></i> Lote Consolidado de Guardia (Cantidad de archivos + Total GB)
                                    </span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="modo_registro" value="individual" class="form-selectgroup-input" id="radio-modo-individual">
                                    <span class="form-selectgroup-label">
                                        <i class="ti ti-file me-1"></i> Archivo Individual (ID de archivo + Tamaño)
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Tamaño en GB --}}
                        <div class="col-md-4">
                            <label class="form-label required" id="label-tamano">Total Volumen Migrado (GB)</label>
                            <div class="input-group">
                                <input type="number" 
                                       step="0.0001" 
                                       min="0.0001" 
                                       name="tamano_gb" 
                                       id="input-tamano" 
                                       class="form-control font-monospace" 
                                       placeholder="Ej. 750.50" 
                                       required>
                                <span class="input-group-text">GB</span>
                            </div>
                            <small class="text-secondary">Expresado en Gigabytes (ej. 3114.42).</small>
                        </div>

                        {{-- Cantidad de Archivos (Modo Lote) --}}
                        <div class="col-md-4" id="col-cantidad-archivos">
                            <label class="form-label required">Cantidad de Archivos en Lote</label>
                            <div class="input-group">
                                <input type="number" min="1" max="50000" name="cantidad_archivos" class="form-control font-monospace" value="1">
                                <span class="input-group-text">archivos</span>
                            </div>
                            <small class="text-secondary">Total transferidos durante la guardia.</small>
                        </div>

                        {{-- ID de Archivo (Modo Individual o Identificador) --}}
                        <div class="col-md-4" id="col-archivo-id">
                            <label class="form-label" id="label-archivo-id">Identificador / ID de Archivo</label>
                            <input type="text" name="archivo_id" class="form-control font-monospace" placeholder="Ej. 015862_20200918142318">
                            <small class="text-secondary" id="help-archivo-id">Opcional si es lote consolidado.</small>
                        </div>

                        {{-- Observaciones --}}
                        <div class="col-12">
                            <label class="form-label">Observaciones / Notas de la Guardia</label>
                            <input type="text" name="observaciones" class="form-control" placeholder="Ej. Turno de noche, migración RAWDATA satelital...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success fw-bold">
                        <i class="ti ti-device-floppy me-1"></i> Guardar y Actualizar Estadísticas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Modal Resumen del Responsable / Ejecutor --}}
<div class="modal fade" id="modal-resumen-responsable" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                    <i class="ti ti-user-circle fs-2 me-2"></i>
                    <span>Resumen Consolidado: <span id="modal-resp-title-name" class="text-white text-decoration-underline">-</span></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Spinner de Carga --}}
                <div id="modal-resp-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted mt-2 small">Cargando métricas y registros del ejecutor...</div>
                </div>

                {{-- Contenido de Métricas --}}
                <div id="modal-resp-content" class="d-none">
                    {{-- Encabezado con Avatar y Período --}}
                    <div class="d-flex align-items-center justify-content-between p-3 rounded bg-light mb-3 border">
                        <div class="d-flex align-items-center">
                            <span id="modal-resp-avatar" class="avatar avatar-md me-3 bg-primary text-white fw-bold fs-3">--</span>
                            <div>
                                <h4 class="mb-0 fw-bold text-dark" id="modal-resp-name">-</h4>
                                <div class="text-secondary small mt-1">
                                    <i class="ti ti-calendar me-1"></i> Período de guardias: <span id="modal-resp-periodo" class="fw-semibold text-dark">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <span id="modal-resp-badge-tipo" class="badge bg-blue-lt px-2 py-1 fs-4">Guardia Humana</span>
                        </div>
                    </div>

                    {{-- Tarjetas KPI --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-3 col-6">
                            <div class="card card-sm border bg-primary-lt">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Volumen Total</div>
                                    <div class="fs-2 fw-bold text-primary my-1 font-monospace" id="modal-resp-gb">0 GB</div>
                                    <div class="text-secondary" style="font-size: 0.72rem;" id="modal-resp-tb">(0 TB)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card card-sm border bg-success-lt">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Participación</div>
                                    <div class="fs-2 fw-bold text-success my-1 font-monospace" id="modal-resp-pct">0%</div>
                                    <div class="text-secondary" style="font-size: 0.72rem;">del total general</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card card-sm border bg-azure-lt">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Archivos</div>
                                    <div class="fs-2 fw-bold text-azure my-1 font-monospace" id="modal-resp-archivos">0</div>
                                    <div class="text-secondary" style="font-size: 0.72rem;">transferidos</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="card card-sm border bg-teal-lt">
                                <div class="card-body p-2 text-center">
                                    <div class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem;">Guardias / Lotes</div>
                                    <div class="fs-2 fw-bold text-teal my-1 font-monospace" id="modal-resp-registros">0</div>
                                    <div class="text-secondary" style="font-size: 0.72rem;">registros</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Desglose por Satélite --}}
                    <div class="card mb-3 border">
                        <div class="card-header py-2 bg-light">
                            <h6 class="card-title mb-0 fw-bold fs-4 text-dark">
                                <i class="ti ti-satellite me-1 text-teal"></i> Desglose por Satélite
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-vcenter mb-0">
                                <thead>
                                    <tr>
                                        <th>Satélite</th>
                                        <th class="text-end">GB Migrados</th>
                                        <th class="text-center">Archivos</th>
                                        <th class="text-center">Registros</th>
                                        <th style="width: 30%;">Proporción</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-resp-sat-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Últimos Registros Transferidos --}}
                    <div class="card border">
                        <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 fw-bold fs-4 text-dark">
                                <i class="ti ti-history me-1 text-primary"></i> Últimas Transferencias Realizadas
                            </h6>
                            <span class="badge bg-secondary-lt" style="font-size: 0.7rem;">Hasta 8 más recientes</span>
                        </div>
                        <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                            <table class="table table-sm table-hover table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Satélite</th>
                                        <th>ID Archivo / Órbita</th>
                                        <th class="text-end">GB</th>
                                        <th class="text-center">Arch.</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-resp-recent-tbody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <a href="#" id="modal-resp-btn-filter" class="btn btn-outline-primary">
                    <i class="ti ti-filter me-1"></i> Ver todos los registros en la tabla
                </a>
                <button type="button" class="btn btn-danger fw-bold" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{-- ApexCharts CDN para Gráficas Interactivas --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // 1. Gráfica Donut: Porcentaje de GB Migrados por Responsable
    const executorsLabels = @json($chartExecutorsLabels);
    const executorsSeries = @json($chartExecutorsSeries);

    if (document.getElementById('chart-executors-pie') && executorsLabels.length > 0) {
        const pieOptions = {
            series: executorsSeries,
            labels: executorsLabels,
            chart: {
                type: 'donut',
                height: 290,
                fontFamily: 'inherit'
            },
            colors: ['#206bc4', '#4299e1', '#0ca678', '#f59f00', '#d63939', '#ae3ec9', '#6366f1', '#17a2b8'],
            stroke: {
                width: 2,
                colors: ['#fff']
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + "%";
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('es-VE') + " GB";
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '12px',
                markers: {
                    radius: 12
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total GB',
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total.toLocaleString('es-VE', { maximumFractionDigits: 1 }) + " GB";
                                }
                            }
                        }
                    }
                }
            }
        };

        const chartPie = new ApexCharts(document.getElementById('chart-executors-pie'), pieOptions);
        chartPie.render();
    }

    // 2. Gráfica Donut: Por Satélite (VRSS-1 vs VRSS-2)
    const satLabels = @json($chartSatLabels);
    const satSeries = @json($chartSatSeries);

    if (document.getElementById('chart-satellite-donut')) {
        const satOptions = {
            series: satSeries,
            labels: satLabels,
            chart: {
                type: 'donut',
                height: 290,
                fontFamily: 'inherit'
            },
            colors: ['#4299e1', '#0ca678'],
            stroke: {
                width: 2,
                colors: ['#fff']
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + "%";
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('es-VE') + " GB";
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Satélites',
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return total.toLocaleString('es-VE', { maximumFractionDigits: 1 }) + " GB";
                                }
                            }
                        }
                    }
                }
            }
        };

        const chartSat = new ApexCharts(document.getElementById('chart-satellite-donut'), satOptions);
        chartSat.render();
    }

    // 3. Gráfica de Barras Apiladas: Ritmo Temporal de Migración (Diario, Semanal, Mensual)
    const datasetsTimeline = {
        daily: {
            title: '<i class="ti ti-chart-bar me-2 text-azure"></i> Ritmo Diario de Migración',
            categories: @json($allDates),
            vrss1: @json($timelineVrss1),
            vrss2: @json($timelineVrss2),
            columnWidth: '60%',
            rotate: -45
        },
        weekly: {
            title: '<i class="ti ti-chart-bar me-2 text-azure"></i> Ritmo Semanal de Migración',
            categories: @json($weeklyLabels),
            vrss1: @json($weeklyVrss1),
            vrss2: @json($weeklyVrss2),
            columnWidth: '45%',
            rotate: -20
        },
        monthly: {
            title: '<i class="ti ti-chart-bar me-2 text-azure"></i> Ritmo Mensual de Migración',
            categories: @json($monthlyLabels),
            vrss1: @json($monthlyVrss1),
            vrss2: @json($monthlyVrss2),
            columnWidth: '35%',
            rotate: 0
        }
    };

    let currentTimelinePeriod = 'daily';
    const initialDataset = datasetsTimeline.daily;

    if (document.getElementById('chart-daily-timeline') && initialDataset.categories.length > 0) {
        const timelineOptions = {
            series: [
                {
                    name: 'VRSS-1',
                    data: initialDataset.vrss1
                },
                {
                    name: 'VRSS-2',
                    data: initialDataset.vrss2
                }
            ],
            chart: {
                type: 'bar',
                height: 290,
                stacked: true,
                fontFamily: 'inherit',
                toolbar: {
                    show: false
                }
            },
            colors: ['#4299e1', '#0ca678'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 3,
                    columnWidth: initialDataset.columnWidth
                }
            },
            xaxis: {
                categories: initialDataset.categories,
                labels: {
                    rotate: initialDataset.rotate,
                    style: {
                        fontSize: '10px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(0) + " GB";
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('es-VE') + " GB";
                    }
                }
            }
        };

        const chartTimeline = new ApexCharts(document.getElementById('chart-daily-timeline'), timelineOptions);
        chartTimeline.render();

        // Controladores para conmutar entre Diario, Semanal y Mensual
        document.querySelectorAll('.btn-timeline-period').forEach(button => {
            button.addEventListener('click', function () {
                const period = this.getAttribute('data-period');
                if (period === currentTimelinePeriod) return;

                document.querySelectorAll('.btn-timeline-period').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentTimelinePeriod = period;

                const ds = datasetsTimeline[period];
                if (!ds) return;

                // Actualizar título del card
                const titleEl = document.getElementById('timeline-card-title');
                if (titleEl) {
                    titleEl.innerHTML = ds.title;
                }

                // Actualizar gráfica dinámicamente con animación
                chartTimeline.updateOptions({
                    xaxis: {
                        categories: ds.categories,
                        labels: {
                            rotate: ds.rotate,
                            style: {
                                fontSize: '10px'
                            }
                        }
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: ds.columnWidth
                        }
                    }
                });

                chartTimeline.updateSeries([
                    {
                        name: 'VRSS-1',
                        data: ds.vrss1
                    },
                    {
                        name: 'VRSS-2',
                        data: ds.vrss2
                    }
                ]);
            });
        });
    }

    // 3.1. Gráfica de Barras: Evolución de Migración por Responsable con Filtros (Día, Semana, Mes y Filtro por Responsable)
    const datasetsResp = {
        daily: {
            periodName: 'Diario',
            categories: @json($allDates),
            series: @json($dailyRespSeries),
            rotate: -45,
            columnWidth: '55%'
        },
        weekly: {
            periodName: 'Semanal',
            categories: @json($weeklyLabels),
            series: @json($weeklyRespSeries),
            rotate: -15,
            columnWidth: '50%'
        },
        monthly: {
            periodName: 'Mensual',
            categories: @json($monthlyLabels),
            series: @json($monthlyRespSeries),
            rotate: 0,
            columnWidth: '35%'
        }
    };

    let currentRespPeriod = 'weekly';
    let currentSelectedResp = 'all';

    function getFilteredRespSeries(period, selectedResp) {
        const ds = datasetsResp[period];
        if (!ds) return [];
        if (selectedResp === 'all') {
            return ds.series;
        }
        return ds.series.filter(s => s.name === selectedResp);
    }

    if (document.getElementById('chart-weekly-responsables')) {
        const initialRespDs = datasetsResp[currentRespPeriod];
        const initialSeries = getFilteredRespSeries(currentRespPeriod, currentSelectedResp);

        const respChartOptions = {
            series: initialSeries,
            chart: {
                type: 'bar',
                height: 340,
                stacked: true,
                fontFamily: 'inherit',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                }
            },
            colors: ['#206bc4', '#4299e1', '#0ca678', '#f59f00', '#ae3ec9', '#d63939', '#17a2b8', '#6366f1'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 3,
                    columnWidth: initialRespDs.columnWidth
                }
            },
            xaxis: {
                categories: initialRespDs.categories,
                labels: {
                    rotate: initialRespDs.rotate,
                    style: {
                        fontSize: '11px',
                        fontWeight: 500
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(0) + " GB";
                    }
                },
                title: {
                    text: 'Volumen Migrado (GB)'
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '12px',
                markers: {
                    radius: 12
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val) {
                        return (val || 0).toLocaleString('es-VE') + " GB";
                    }
                }
            }
        };

        const chartRespInstance = new ApexCharts(document.getElementById('chart-weekly-responsables'), respChartOptions);
        chartRespInstance.render();

        function updateRespChart() {
            const ds = datasetsResp[currentRespPeriod];
            if (!ds) return;

            const isSingle = currentSelectedResp !== 'all';
            const filteredSeries = getFilteredRespSeries(currentRespPeriod, currentSelectedResp);

            // Actualizar subtítulo
            const subtitleEl = document.getElementById('chart-resp-card-subtitle');
            if (subtitleEl) {
                const targetText = isSingle ? `Mostrando datos de <b>${currentSelectedResp}</b>` : 'Comparativa de todos los operadores';
                subtitleEl.innerHTML = `${targetText} (${ds.periodName})`;
            }

            chartRespInstance.updateOptions({
                chart: {
                    stacked: !isSingle
                },
                xaxis: {
                    categories: ds.categories,
                    labels: {
                        rotate: ds.rotate,
                        style: {
                            fontSize: '11px',
                            fontWeight: 500
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        columnWidth: ds.columnWidth
                    }
                }
            });

            chartRespInstance.updateSeries(filteredSeries);
        }

        // Filtro por Período: Día, Semana, Mes
        document.querySelectorAll('.btn-resp-period').forEach(btn => {
            btn.addEventListener('click', function () {
                const period = this.getAttribute('data-period');
                if (period === currentRespPeriod) return;

                document.querySelectorAll('.btn-resp-period').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentRespPeriod = period;

                updateRespChart();
            });
        });

        // Filtro por Responsable
        const selectResp = document.getElementById('select-filter-chart-resp');
        if (selectResp) {
            selectResp.addEventListener('change', function () {
                currentSelectedResp = this.value;
                updateRespChart();
            });
        }
    }

    // 4. Modal: Alternar entre modo Lote e Individual
    const radioLote = document.getElementById('radio-modo-lote');
    const radioIndividual = document.getElementById('radio-modo-individual');
    const colCantidad = document.getElementById('col-cantidad-archivos');
    const labelTamano = document.getElementById('label-tamano');
    const labelArchivoId = document.getElementById('label-archivo-id');
    const helpArchivoId = document.getElementById('help-archivo-id');

    if (radioLote && radioIndividual) {
        function toggleModo() {
            if (radioIndividual.checked) {
                if (colCantidad) colCantidad.style.display = 'none';
                if (labelTamano) labelTamano.textContent = 'Tamaño del Archivo (GB)';
                if (labelArchivoId) labelArchivoId.textContent = 'ID del Archivo (Obligatorio)';
                if (helpArchivoId) helpArchivoId.textContent = 'Ej. 006956_20190326140607';
            } else {
                if (colCantidad) colCantidad.style.display = 'block';
                if (labelTamano) labelTamano.textContent = 'Total Volumen Migrado (GB)';
                if (labelArchivoId) labelArchivoId.textContent = 'Identificador / ID de Archivo';
                if (helpArchivoId) helpArchivoId.textContent = 'Opcional si es lote consolidado.';
            }
        }

        radioLote.addEventListener('change', toggleModo);
        radioIndividual.addEventListener('change', toggleModo);
    }

    // 5. Confirmación con SweetAlert2 para eliminar registros
    document.querySelectorAll('.btn-delete-stat').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const name = this.getAttribute('data-name');

            Swal.fire({
                title: '¿Eliminar este registro?',
                html: `Se removerá la guardia de <b>${name}</b> de las estadísticas.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939'
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // 6. Modal AJAX de Resumen por Responsable
    const modalResumenElement = document.getElementById('modal-resumen-responsable');
    const modalResumen = modalResumenElement ? new bootstrap.Modal(modalResumenElement) : null;

    document.querySelectorAll('.btn-ver-resumen-responsable').forEach(button => {
        button.addEventListener('click', function () {
            const responsable = this.getAttribute('data-responsable');
            if (!responsable || !modalResumen) return;

            document.getElementById('modal-resp-title-name').textContent = responsable;
            document.getElementById('modal-resp-loading').classList.remove('d-none');
            document.getElementById('modal-resp-content').classList.add('d-none');

            modalResumen.show();

            fetch(`{{ route('nas-migracion.estadisticas.responsable-resumen') }}?responsable=${encodeURIComponent(responsable)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'No se pudieron cargar los datos.',
                        confirmButtonColor: '#2fb344'
                    });
                    modalResumen.hide();
                    return;
                }

                // Llenar Datos Principales
                document.getElementById('modal-resp-name').textContent = data.responsable;
                document.getElementById('modal-resp-gb').textContent = `${data.total_gb} GB`;
                document.getElementById('modal-resp-tb').textContent = `(${data.total_tb} TB)`;
                document.getElementById('modal-resp-pct').textContent = `${data.porcentaje_global}%`;
                document.getElementById('modal-resp-archivos').textContent = data.total_archivos;
                document.getElementById('modal-resp-registros').textContent = data.total_registros;

                const periodo = (data.fecha_inicio && data.fecha_fin) 
                    ? `${data.fecha_inicio} al ${data.fecha_fin}` 
                    : (data.fecha_inicio || 'Sin fechas registradas');
                document.getElementById('modal-resp-periodo').textContent = periodo;

                // Avatar y Tipo
                const avatarEl = document.getElementById('modal-resp-avatar');
                const badgeTipoEl = document.getElementById('modal-resp-badge-tipo');

                if (data.is_bot) {
                    avatarEl.className = 'avatar avatar-md me-3 bg-purple text-white fw-bold fs-3';
                    avatarEl.innerHTML = '<i class="ti ti-robot"></i>';
                    badgeTipoEl.className = 'badge bg-purple-lt px-2 py-1 fs-4';
                    badgeTipoEl.textContent = 'Proceso Automático (bot_pss)';
                } else {
                    avatarEl.className = 'avatar avatar-md me-3 bg-primary text-white fw-bold fs-3';
                    avatarEl.textContent = (data.responsable || '--').substring(0, 2).toUpperCase();
                    badgeTipoEl.className = 'badge bg-blue-lt px-2 py-1 fs-4';
                    badgeTipoEl.textContent = 'Guardia Humana';
                }

                // Desglose Satélites
                const satTbody = document.getElementById('modal-resp-sat-tbody');
                satTbody.innerHTML = '';
                if (data.satelites && data.satelites.length > 0) {
                    data.satelites.forEach(s => {
                        const satBadge = s.satelite === 'VRSS-1' ? 'bg-info-lt' : 'bg-teal-lt';
                        const barColor = s.satelite === 'VRSS-1' ? 'bg-info' : 'bg-teal';
                        satTbody.innerHTML += `
                            <tr>
                                <td>
                                    <span class="badge ${satBadge} font-monospace">${s.satelite}</span>
                                </td>
                                <td class="text-end fw-bold font-monospace text-primary">${parseFloat(s.total_gb).toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 4})} GB</td>
                                <td class="text-center font-monospace">${parseInt(s.total_archivos).toLocaleString()}</td>
                                <td class="text-center font-monospace">${s.total_registros}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress progress-sm flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar ${barColor}" style="width: ${s.porcentaje_sat}%"></div>
                                        </div>
                                        <span class="small font-monospace text-muted">${s.porcentaje_sat}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    satTbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-2">Sin desglose disponible</td></tr>`;
                }

                // Últimos Registros
                const recentTbody = document.getElementById('modal-resp-recent-tbody');
                recentTbody.innerHTML = '';
                if (data.recent_records && data.recent_records.length > 0) {
                    data.recent_records.forEach(r => {
                        const satBadge = r.satelite === 'VRSS-1' ? 'bg-info-lt' : 'bg-teal-lt';
                        const operadorBadge = r.operador ? `<span class="badge bg-light text-dark border small ms-1"><i class="ti ti-user me-1 text-primary"></i>${r.operador}</span>` : '';
                        recentTbody.innerHTML += `
                            <tr style="font-size: 0.8rem;">
                                <td>${r.fecha}</td>
                                <td><span class="badge ${satBadge} font-monospace" style="font-size: 0.7rem;">${r.satelite}</span></td>
                                <td><code class="text-primary">${r.archivo_id || '-'}</code> ${operadorBadge}</td>
                                <td class="text-end font-monospace fw-bold text-dark">${r.tamano_gb}</td>
                                <td class="text-center font-monospace">${r.cantidad_archivos}</td>
                                <td class="text-truncate text-secondary" style="max-width: 180px;" title="${r.observaciones}">${r.observaciones}</td>
                            </tr>
                        `;
                    });
                } else {
                    recentTbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-2">Sin registros recientes</td></tr>`;
                }

                // Botón Enlace Filtrado
                const btnFilter = document.getElementById('modal-resp-btn-filter');
                if (btnFilter) {
                    btnFilter.href = data.filter_url;
                }

                document.getElementById('modal-resp-loading').classList.add('d-none');
                document.getElementById('modal-resp-content').classList.remove('d-none');
            })
            .catch(err => {
                document.getElementById('modal-resp-loading').classList.add('d-none');
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Red',
                    text: 'No se pudo comunicar con el servidor: ' + err.message,
                    confirmButtonColor: '#2fb344'
                });
                modalResumen.hide();
            });
        });
    });

});
</script>
@endpush

