@extends('layouts.app')

@section('title', 'Confirmación de Archivos - Synology vs Inspur')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    ALMACENAMIENTO &amp; INFRAESTRUCTURA / AUDITORÍA TÉCNICA
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-checklist me-2 text-success" style="font-size: 1.6rem;"></i>
                    Confirmación y Cotejo de Archivos Copiados (Synology vs Inspur)
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                @can('ver-estadisticas-nas')
                <a href="{{ route('nas-migracion.estadisticas.index') }}" class="btn btn-outline-warning btn-sm fw-bold">
                    <i class="ti ti-chart-bar me-1"></i> Estadísticas
                </a>
                @endcan
                @can('exportar-confirmacion-nas')
                @if($verification)
                <a href="{{ route('nas-migracion.confirmacion.export', $verification) }}" class="btn btn-outline-primary btn-sm fw-bold">
                    <i class="ti ti-file-spreadsheet me-1"></i> Exportar CSV
                </a>
                @endif
                @endcan

                @can('ejecutar-confirmacion-nas')
                @if($selectedTask)
                <button type="button" 
                        class="btn btn-outline-success btn-sm fw-bold btn-simulate-verification"
                        data-task-id="{{ $selectedTask->id }}"
                        data-task-name="{{ $selectedTask->name }}"
                        data-url="{{ route('nas-migracion.confirmacion.simulate', $selectedTask) }}"
                        title="Generar verificación demostrativa de corroboración">
                    <i class="ti ti-flask me-1"></i> Simular Cotejo
                </button>

                <button type="button" 
                        class="btn btn-success btn-sm fw-bold shadow-sm btn-start-verification"
                        data-task-id="{{ $selectedTask->id }}"
                        data-task-name="{{ $selectedTask->name }}"
                        data-url="{{ route('nas-migracion.confirmacion.start', $selectedTask) }}"
                        {{ ($verification && $verification->status === 'running') ? 'disabled' : '' }}>
                    @if($verification && $verification->status === 'running')
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> Cotejando...
                    @else
                        <i class="ti ti-scan me-1"></i> Verificar y Comparar Ahora
                    @endif
                </button>
                @endif
                @endcan
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Stat Cards de Verificación --}}
        @if($verification)
        <div class="row row-cards g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-primary text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-files fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-dark">{{ number_format($verification->total_files_synology) }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">
                                    EXAMINADOS EN SYNOLOGY
                                </div>
                                <small class="text-muted">{{ $verification->human_synology_size }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-success text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-circle-check fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-success d-flex align-items-center">
                                    {{ number_format($verification->total_confirmed) }}
                                    <span class="badge bg-green-lt ms-2 fs-4">{{ $verification->confirmed_percentage }}%</span>
                                </div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">
                                    CONFIRMADOS (IDÉNTICOS)
                                </div>
                                <small class="text-success fw-semibold">Nombre, tamaño y fecha OK</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-danger text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-alert-circle fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-danger">{{ number_format($verification->total_missing_in_target) }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">
                                    FALTANTES EN INSPUR
                                </div>
                                <small class="text-danger fw-semibold">No encontrados en destino</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-warning text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-alert-triangle fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-warning">
                                    {{ number_format($verification->total_mismatched_size + $verification->total_mismatched_date) }}
                                </div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">
                                    CON DISCREPANCIAS
                                </div>
                                <small class="text-secondary">
                                    {{ $verification->total_mismatched_size }} tamaño / {{ $verification->total_mismatched_date }} fecha
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estado de Verificación en Ejecución --}}
        @if($verification->status === 'running')
        <div class="alert alert-warning border-0 shadow-sm mb-4" id="alert-running-box" data-status-url="{{ route('nas-migracion.confirmacion.status', $verification) }}">
            <div class="d-flex align-items-center">
                <div class="spinner-border text-warning me-3" role="status" style="width: 2.2rem; height: 2.2rem;"></div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h4 class="alert-title mb-0 fw-bold d-flex align-items-center">
                            <i class="ti ti-scan me-2 text-warning fs-2"></i>
                            <span>Proceso de Cotejo y Comparación en Curso</span>
                            @if($verification->current_orbit)
                                <span class="badge bg-dark text-white font-monospace ms-2" id="running-orbit-badge">
                                    <i class="ti ti-satellite me-1 text-warning"></i>Órbita: {{ $verification->current_orbit }}
                                </span>
                            @endif
                        </h4>
                        <span class="badge bg-warning-lt text-warning fw-bold font-monospace" id="running-progress-counter">
                            {{ $verification->files_scanned }} / {{ $verification->total_files_to_scan ?: 'Calculando' }} archivos
                        </span>
                    </div>

                    <div class="text-secondary small mb-2" id="running-description-text">
                        Analizando metadatos e integridad binaria de archivos en <b>NAS Synology</b> frente a <b>Inspur RAWDATA</b>.
                    </div>

                    @php
                        $calcPercent = ($verification->total_files_to_scan > 0) 
                            ? min(100, round(($verification->files_scanned / $verification->total_files_to_scan) * 100)) 
                            : null;
                    @endphp
                    <div class="progress progress-sm">
                        <div class="progress-bar {{ $calcPercent ? 'bg-warning progress-bar-striped progress-bar-animated' : 'progress-bar-indeterminate bg-warning' }}" 
                             id="running-progress-bar"
                             style="width: {{ $calcPercent ? $calcPercent . '%' : '100%' }}"></div>
                    </div>
                </div>
            </div>
        </div>
        @elseif($verification->status === 'failed')
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="d-flex">
                <i class="ti ti-alert-triangle fs-1 text-danger me-3"></i>
                <div>
                    <h4 class="alert-title mb-1 fw-bold">Fallo en la Sesión de Verificación</h4>
                    <div class="text-secondary small">{{ $verification->error_message ?: 'Ocurrió un error al contactar la NAS Synology.' }}</div>
                </div>
            </div>
        </div>
        @endif
        @endif

        {{-- Banner informativo de criterios de confirmación --}}
        <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="ti ti-shield-check fs-1 text-info"></i>
                </div>
                <div>
                    <h4 class="alert-title mb-1 fw-bold">Criterio de Corroboración Exhaustiva de Parámetros</h4>
                    <div class="text-secondary small">
                        Un archivo es declarado <strong>Confirmado (Idéntico)</strong> únicamente cuando se cumplen rigurosamente los 3 criterios:
                        <strong>1. Nombre y Ruta Relativa:</strong> Coincidencia exacta de ubicación jerárquica.
                        <strong>2. Tamaño en Bytes:</strong> Longitud binaria idéntica (garantiza que no hubo cortes ni truncamientos).
                        <strong>3. Fecha de Modificación:</strong> Timestamps sincronizados entre ambos sistemas de almacenamiento.
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de Cotejo Agrupado por Órbita (Conglomerado) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center g-2">
                    <div class="col-md-4">
                        <h3 class="card-title text-dark fw-bold mb-0 d-flex align-items-center">
                            <i class="ti ti-folders me-2 text-primary fs-2"></i> Resumen de Cotejo por Órbita
                        </h3>
                        @if($verification)
                        <small class="text-secondary d-block mt-1">
                            Sesión #{{ $verification->id }} &bull; Ejecutado el {{ $verification->started_at ? $verification->started_at->format('d/m/Y H:i:s') : '-' }} 
                            ({{ $verification->duration_formatted }})
                        </small>
                        @endif
                    </div>

                    {{-- Filtros y Buscador --}}
                    <div class="col-md-8 text-md-end">
                        <form method="GET" action="{{ route('nas-migracion.confirmacion.index') }}" class="d-flex flex-wrap justify-content-md-end align-items-center gap-2 m-0">
                            <input type="hidden" name="task_id" value="{{ $selectedTaskId }}">
                            @if(request('verification_id'))
                            <input type="hidden" name="verification_id" value="{{ request('verification_id') }}">
                            @endif

                            <div class="input-icon" style="width: 240px;">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por órbita..." value="{{ request('search') }}">
                            </div>

                            <select name="status" class="form-select form-select-sm" style="width: 190px;" onchange="this.form.submit()">
                                <option value="all">Todas las Órbitas</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>✅ 100% Correctas</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>⚠️ Con Archivos Fallidos</option>
                            </select>

                            <button type="submit" class="btn btn-outline-secondary btn-sm">Filtrar</button>
                            @if(request('search') || (request('status') && request('status') !== 'all'))
                            <a href="{{ route('nas-migracion.confirmacion.index', ['task_id' => $selectedTaskId, 'verification_id' => request('verification_id')]) }}" class="btn btn-outline-danger btn-sm" title="Limpiar filtros">
                                <i class="ti ti-x"></i>
                            </a>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th style="min-width: 220px;">Órbita / Directorio</th>
                            <th class="text-center" style="min-width: 110px;">Total Archivos</th>
                            <th class="text-center" style="min-width: 110px;">
                                <span class="badge bg-green-lt text-green d-block py-1">
                                    <i class="ti ti-check me-1"></i> Correctos
                                </span>
                            </th>
                            <th class="text-center" style="min-width: 130px;">
                                <span class="badge bg-danger-lt text-danger d-block py-1">
                                    <i class="ti ti-alert-triangle me-1"></i> Fallidos / Faltantes
                                </span>
                            </th>
                            <th class="text-center" style="min-width: 150px;">Fase 1 (Intermedia)</th>
                            <th class="text-center" style="min-width: 150px;">Fase 2 (Inspur RAWDATA)</th>
                            <th class="text-center" style="min-width: 100px;">Tamaño</th>
                            <th class="text-center" style="min-width: 120px;">Estado</th>
                            <th class="text-end" style="width: 110px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orbitGroups as $orb)
                        @php
                            $isOk = ($orb->failed_files === 0 && $orb->total_files > 0);
                            $p1Percent = $orb->total_files > 0 ? round(($orb->phase1_ok / $orb->total_files) * 100) : 0;
                            $p2Percent = $orb->total_files > 0 ? round(($orb->phase2_ok / $orb->total_files) * 100) : 0;
                        @endphp
                        <tr>
                            {{-- Órbita --}}
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs bg-primary-lt text-primary rounded me-2">
                                        <i class="ti ti-satellite fs-4"></i>
                                    </span>
                                    <div>
                                        <span class="fw-bold font-monospace text-dark d-block" style="font-size: 0.95rem;">
                                            {{ $orb->orbit }}
                                        </span>
                                        <small class="text-secondary">Directorio de órbita</small>
                                    </div>
                                </div>
                            </td>

                            {{-- Total Archivos --}}
                            <td class="text-center">
                                <span class="badge bg-secondary-lt fw-bold fs-3">
                                    {{ number_format($orb->total_files) }}
                                </span>
                            </td>

                            {{-- Correctos --}}
                            <td class="text-center">
                                <span class="badge bg-green-lt text-green fw-bold fs-3">
                                    <i class="ti ti-circle-check me-1"></i> {{ number_format($orb->confirmed_files) }}
                                </span>
                            </td>

                            {{-- Fallidos --}}
                            <td class="text-center">
                                @if($orb->failed_files > 0)
                                    <span class="badge bg-danger text-white fw-bold fs-3">
                                        <i class="ti ti-alert-triangle me-1"></i> {{ number_format($orb->failed_files) }}
                                    </span>
                                @else
                                    <span class="badge bg-green-lt text-green">0</span>
                                @endif
                            </td>

                            {{-- Fase 1 --}}
                            <td>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-secondary">Synology &rarr; Intermedia</small>
                                    <span class="badge bg-green-lt text-green fw-bold" style="font-size: 0.72rem;">
                                        {{ $orb->phase1_ok }} / {{ $orb->total_files }}
                                    </span>
                                </div>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-success" style="width: {{ $p1Percent }}%"></div>
                                </div>
                            </td>

                            {{-- Fase 2 --}}
                            <td>
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-secondary">Intermedia &rarr; Inspur</small>
                                    <span class="badge {{ $p2Percent === 100 ? 'bg-green-lt text-green' : 'bg-azure-lt text-azure' }} fw-bold" style="font-size: 0.72rem;">
                                        {{ $orb->phase2_ok }} / {{ $orb->total_files }}
                                    </span>
                                </div>
                                <div class="progress progress-xs">
                                    <div class="progress-bar {{ $p2Percent === 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $p2Percent }}%"></div>
                                </div>
                            </td>

                            {{-- Tamaño --}}
                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-monospace fw-bold">{{ $orb->human_size }}</span>
                            </td>

                            {{-- Estado --}}
                            <td class="text-center">
                                @if($isOk)
                                    <span class="badge bg-green-lt text-green fw-bold px-2 py-1">
                                        <i class="ti ti-check me-1"></i> 100% OK
                                    </span>
                                @else
                                    <span class="badge bg-warning-lt text-warning fw-bold px-2 py-1">
                                        <i class="ti ti-alert-triangle me-1"></i> Incompleta ({{ $orb->percentage }}%)
                                    </span>
                                @endif
                            </td>

                            {{-- Acción: Ver detalle de archivos de la órbita --}}
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-sm fw-bold btn-ver-detalle-orbita"
                                        data-orbit="{{ $orb->orbit }}"
                                        data-url="{{ route('nas-migracion.confirmacion.orbit-files', ['verification' => $verification->id, 'orbit' => $orb->orbit]) }}">
                                    <i class="ti ti-list-details me-1"></i> Ver Detalle
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary">
                                @if(!$verification)
                                    <i class="ti ti-scan text-secondary fs-1 mb-2 d-block"></i>
                                    <p class="fw-semibold mb-1">Aún no se ha realizado ninguna verificación para esta tarea.</p>
                                    <small class="text-muted">Haz clic en <strong>"Verificar y Comparar Ahora"</strong> o <strong>"Simular Cotejo"</strong> para contrastar los archivos.</small>
                                @else
                                    <i class="ti ti-folder-off text-secondary fs-1 mb-2 d-block"></i>
                                    <p class="fw-semibold mb-0">No se encontraron órbitas que coincidan con los filtros aplicados.</p>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Historial de Verificaciones Previas --}}
        @if($history->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title text-dark fw-bold mb-0 d-flex align-items-center">
                    <i class="ti ti-history me-2 text-primary fs-2"></i> Historial de Cotejos y Verificaciones
                </h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th>Fecha de Ejecución</th>
                            <th>Tarea</th>
                            <th>Usuario</th>
                            <th>Duración</th>
                            <th>Total Examinados</th>
                            <th>Confirmados</th>
                            <th>Faltantes</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $h)
                        <tr class="{{ $verification && $verification->id === $h->id ? 'table-active' : '' }}">
                            <td class="text-nowrap fw-semibold">
                                {{ $h->started_at->format('d/m/Y H:i:s') }}
                                <div class="text-secondary small">{{ $h->started_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $h->task->name ?? 'N/A' }}</div>
                                <small class="text-secondary">{{ $h->task->nas_host ?? '-' }}</small>
                            </td>
                            <td class="text-secondary">
                                {{ $h->user->name ?? 'Sistema' }}
                            </td>
                            <td class="font-monospace text-secondary">
                                {{ $h->duration_formatted }}
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt fw-bold">{{ number_format($h->total_files_synology) }} archivos</span>
                            </td>
                            <td>
                                <span class="badge bg-green-lt text-green fw-bold">
                                    {{ number_format($h->total_confirmed) }} ({{ $h->confirmed_percentage }}%)
                                </span>
                            </td>
                            <td>
                                @if($h->total_missing_in_target > 0)
                                    <span class="badge bg-danger-lt text-danger fw-bold">{{ number_format($h->total_missing_in_target) }}</span>
                                @else
                                    <span class="badge bg-green-lt text-green">0</span>
                                @endif
                            </td>
                            <td>
                                @if($h->status === 'completed')
                                    <span class="badge bg-green-lt text-green fw-bold">Completado</span>
                                @elseif($h->status === 'running')
                                    <span class="badge bg-warning-lt text-warning fw-bold">En curso</span>
                                @else
                                    <span class="badge bg-danger-lt text-danger fw-bold">Falló</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-outline-dark btn-sm fw-bold btn-ver-reporte-orbita"
                                        data-url="{{ route('nas-migracion.confirmacion.orbit-summary', $h->id) }}">
                                    <i class="ti ti-file-text me-1"></i> Ver Reporte
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- Modal Inspección Técnica de Archivo --}}
<div class="modal fade" id="modal-inspeccion-archivo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="ti ti-file-check me-2 text-primary fs-2"></i> Inspección Técnica de Archivo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Nombre y Ruta Relativa</label>
                    <div class="p-2 bg-light rounded border font-monospace text-dark text-break" id="inspect-path"></div>
                </div>

                <div class="row g-3">
                    {{-- Synology --}}
                    <div class="col-md-6">
                        <div class="card border border-primary h-100 shadow-none">
                            <div class="card-header bg-primary-lt py-2">
                                <h4 class="card-title text-primary fw-bold mb-0">
                                    <i class="ti ti-server me-1"></i> NAS Synology (Origen)
                                </h4>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <span class="text-secondary small d-block">Tamaño:</span>
                                    <strong class="text-dark" id="inspect-src-size">-</strong>
                                </div>
                                <div>
                                    <span class="text-secondary small d-block">Última Modificación:</span>
                                    <strong class="font-monospace text-dark" id="inspect-src-date">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Inspur --}}
                    <div class="col-md-6">
                        <div class="card border border-teal h-100 shadow-none">
                            <div class="card-header bg-teal-lt py-2">
                                <h4 class="card-title text-teal fw-bold mb-0">
                                    <i class="ti ti-database me-1"></i> NAS Inspur (Destino)
                                </h4>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <span class="text-secondary small d-block">Tamaño:</span>
                                    <strong class="text-dark" id="inspect-tgt-size">-</strong>
                                </div>
                                <div>
                                    <span class="text-secondary small d-block">Última Modificación:</span>
                                    <strong class="font-monospace text-dark" id="inspect-tgt-date">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 p-3 bg-light rounded border d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary small text-uppercase fw-bold d-block">Veredicto de Cotejo:</span>
                        <div class="fw-bold" id="inspect-veredicto-text">Confirmado</div>
                    </div>
                    <span id="inspect-veredicto-badge" class="badge bg-green-lt text-green fs-3 px-3 py-2"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Resumen por Órbita Verificada --}}
<div class="modal fade" id="modal-resumen-orbitas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center mb-0">
                        <i class="ti ti-report-analytics me-2 text-cyan fs-2"></i> 
                        Resumen de Cotejo por Órbita
                    </h5>
                    <small class="text-white-50" id="m-orbit-subtitle">Cargando reporte de verificación...</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                {{-- Spinner de carga --}}
                <div id="m-orbit-loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-secondary mt-3 fw-bold">Analizando y consolidando archivos por órbita...</p>
                </div>

                {{-- Contenido cuando está listo --}}
                <div id="m-orbit-content" style="display: none;">
                    {{-- KPIs Resumen Superior --}}
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Órbitas Examinadas</span>
                                    <h2 class="text-dark fw-bold mb-0" id="m-kpi-orbits">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Total Archivos</span>
                                    <h2 class="text-primary fw-bold mb-0" id="m-kpi-files">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Confirmados (100%)</span>
                                    <h2 class="text-success fw-bold mb-0" id="m-kpi-confirmed">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <span class="text-secondary small fw-bold text-uppercase d-block mb-1">Cumplimiento Global</span>
                                    <h2 class="text-azure fw-bold mb-0" id="m-kpi-percent">0%</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Órbitas --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                            <h4 class="card-title fw-bold text-dark mb-0">
                                <i class="ti ti-folders me-1 text-primary"></i> Desglose Detallado por Órbita
                            </h4>
                            <span class="badge bg-primary-lt" id="m-badge-total-count">0 órbitas</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 220px;">Órbita / Directorio</th>
                                        <th class="text-center" style="width: 120px;">Total Archivos</th>
                                        <th class="text-center" style="min-width: 180px;">Fase 1 (Intermedia)</th>
                                        <th class="text-center" style="min-width: 180px;">Fase 2 (Inspur RAWDATA)</th>
                                        <th class="text-center" style="width: 130px;">Peso Total</th>
                                        <th class="text-center" style="width: 150px;">Estado General</th>
                                    </tr>
                                </thead>
                                <tbody id="m-orbit-tbody">
                                    {{-- Se rellena vía JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-danger fw-bold" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Detalle de Archivos de una Órbita Específica --}}
<div class="modal fade" id="modal-detalle-archivos-orbita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center mb-0">
                        <i class="ti ti-satellite me-2 fs-2"></i> 
                        Detalle de Archivos: <span id="m-det-orbit-title" class="font-monospace ms-2 text-warning"></span>
                    </h5>
                    <small class="text-white-50">Listado exhaustivo de archivos cotejados, estados de replicación y fallos individuales</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                {{-- Spinner de carga --}}
                <div id="m-det-loader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-secondary mt-3 fw-bold">Obteniendo archivos de la órbita...</p>
                </div>

                {{-- Contenido cuando está listo --}}
                <div id="m-det-content" style="display: none;">
                    {{-- KPIs de la Órbita --}}
                    <div class="row g-3 mb-3">
                        <div class="col-sm-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-secondary small fw-bold text-uppercase d-block">Total Archivos</span>
                                        <h3 class="text-dark fw-bold mb-0" id="m-det-total">0</h3>
                                    </div>
                                    <span class="avatar avatar-md bg-secondary-lt text-secondary rounded">
                                        <i class="ti ti-files fs-2"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-secondary small fw-bold text-uppercase d-block">Correctos (100% OK)</span>
                                        <h3 class="text-success fw-bold mb-0" id="m-det-correct">0</h3>
                                    </div>
                                    <span class="avatar avatar-md bg-green-lt text-green rounded">
                                        <i class="ti ti-circle-check fs-2"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-secondary small fw-bold text-uppercase d-block">Fallidos / Con Diferencias</span>
                                        <h3 class="text-danger fw-bold mb-0" id="m-det-failed">0</h3>
                                    </div>
                                    <span class="avatar avatar-md bg-danger-lt text-danger rounded">
                                        <i class="ti ti-alert-triangle fs-2"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filtro rápido de la tabla en vivo --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active btn-filter-orbit-files" data-filter="all">Todos (<span id="count-all">0</span>)</button>
                            <button type="button" class="btn btn-outline-danger btn-filter-orbit-files" data-filter="failed">Solo Fallidos (<span id="count-failed">0</span>)</button>
                            <button type="button" class="btn btn-outline-success btn-filter-orbit-files" data-filter="ok">Solo Correctos (<span id="count-ok">0</span>)</button>
                        </div>
                        <div class="input-icon" style="width: 250px;">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" id="m-det-search-input" class="form-control form-control-sm" placeholder="Buscar en esta órbita...">
                        </div>
                    </div>

                    {{-- Tabla de Archivos Individuales --}}
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive" style="max-height: 480px;">
                            <table class="table table-vcenter table-hover mb-0 card-table" id="m-det-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Archivo / Subcanal</th>
                                        <th>Synology (Origen)</th>
                                        <th class="text-center">Fase 1 (Intermedia)</th>
                                        <th class="text-center">Fase 2 (Inspur RAWDATA)</th>
                                        <th class="text-center">Parámetros</th>
                                        <th class="text-center">Veredicto</th>
                                    </tr>
                                </thead>
                                <tbody id="m-det-tbody">
                                    {{-- Se rellena vía JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-danger fw-bold" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 1. Iniciar Verificación en Vivo
    document.querySelectorAll('.btn-start-verification').forEach(button => {
        button.addEventListener('click', function () {
            const btn = this;
            const taskName = btn.getAttribute('data-task-name');
            const url = btn.getAttribute('data-url');

            Swal.fire({
                title: '¿Iniciar Verificación y Cotejo?',
                html: `Se examinarán todos los archivos de la NAS Synology para contrastar contra la NAS Inspur en la tarea <b>${taskName}</b>.<br><br>
                       <div class="alert alert-info text-start p-2 mb-0" style="font-size: 0.85rem;">
                           <i class="ti ti-scan me-1"></i> Se compararán parámetros exactos de <b>Nombre</b>, <b>Tamaño en Bytes</b> y <b>Fecha de Modificación</b>.
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, verificar y comparar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    // Bloquear inmediatamente el botón
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Iniciando...';

                    Swal.fire({
                        title: 'Despachando Verificación...',
                        html: 'Conectando con la NAS en segundo plano.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Cotejando...';
                            Swal.fire({
                                icon: 'success',
                                title: 'Cotejo en Marcha',
                                text: data.message,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="ti ti-scan me-1"></i> Verificar y Comparar Ahora';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al iniciar',
                                text: data.message || 'No se pudo iniciar la verificación.',
                                confirmButtonText: 'Cerrar'
                            });
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ti ti-scan me-1"></i> Verificar y Comparar Ahora';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de comunicación',
                            text: err.message,
                            confirmButtonText: 'Cerrar'
                        });
                    });
                }
            });
        });
    });

    // 2. Simular Cotejo Demostrativo
    document.querySelectorAll('.btn-simulate-verification').forEach(button => {
        button.addEventListener('click', function () {
            const btn = this;
            const taskName = btn.getAttribute('data-task-name');
            const url = btn.getAttribute('data-url');

            Swal.fire({
                title: '¿Simular Cotejo de Verificación?',
                html: `Se generará un conjunto representativo de archivos analizados para <b>${taskName}</b> contrastando tamaños, nombres y fechas.<br><br>
                       <div class="alert alert-success text-start p-2 mb-0" style="font-size: 0.85rem;">
                           <i class="ti ti-check me-1"></i> Permite evaluar la corroboración técnica de archivos de forma inmediata.
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, simular verificación',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Simulando...';

                    Swal.fire({
                        title: 'Generando Simulación...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Simulación Completada!',
                                text: data.message,
                                confirmButtonText: 'Ver Resultados'
                            }).then(() => {
                                window.location.href = `{{ route('nas-migracion.confirmacion.index') }}?task_id={{ $selectedTaskId }}&verification_id=${data.verification_id}`;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonText: 'Cerrar'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al simular',
                            text: err.message,
                            confirmButtonText: 'Cerrar'
                        });
                    });
                }
            });
        });
    });

    // 3. Polling automático si la verificación está en estado 'running'
    const runningBox = document.getElementById('alert-running-box');
    if (runningBox) {
        const statusUrl = runningBox.getAttribute('data-status-url');
        const pollInterval = setInterval(() => {
            fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    // Actualizar en vivo la órbita activa y el conteo de escaneo
                    const orbitBadge = document.getElementById('running-orbit-badge');
                    const counter = document.getElementById('running-progress-counter');
                    const pbar = document.getElementById('running-progress-bar');

                    if (data.current_orbit && orbitBadge) {
                        orbitBadge.innerHTML = `<i class="ti ti-satellite me-1 text-warning"></i>Órbita: ${data.current_orbit}`;
                    }

                    if (counter && data.total_files_to_scan > 0) {
                        counter.textContent = `${data.files_scanned} / ${data.total_files_to_scan} archivos`;
                        if (pbar) {
                            const pct = Math.min(100, Math.round((data.files_scanned / data.total_files_to_scan) * 100));
                            pbar.className = 'progress-bar bg-warning progress-bar-striped progress-bar-animated';
                            pbar.style.width = `${pct}%`;
                        }
                    }

                    if (data.status === 'completed') {
                        clearInterval(pollInterval);
                        Swal.fire({
                            icon: 'success',
                            title: '¡Verificación Concluida!',
                            html: `Se completó el cotejo de <b>${data.total_files} archivos</b>.<br>
                                   <div class="badge bg-green-lt p-2 fs-3 mt-2">
                                       Confirmados: ${data.confirmed} (${data.percentage}%)
                                   </div>`,
                            confirmButtonText: 'Actualizar Vista'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else if (data.status === 'failed') {
                        clearInterval(pollInterval);
                        Swal.fire({
                            icon: 'error',
                            title: 'Fallo en la Verificación',
                            text: data.error_message || 'El proceso de verificación reportó errores.',
                            confirmButtonText: 'Entendido'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                })
                .catch(() => {});
        }, 3000);
    }

    // 4. Modal Inspección de Archivo
    const inspectModal = new bootstrap.Modal(document.getElementById('modal-inspeccion-archivo'));
    document.querySelectorAll('.btn-inspect-file').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('inspect-path').textContent = this.getAttribute('data-path');
            document.getElementById('inspect-src-size').textContent = this.getAttribute('data-src-size');
            document.getElementById('inspect-src-date').textContent = this.getAttribute('data-src-date');
            document.getElementById('inspect-tgt-size').textContent = this.getAttribute('data-tgt-size');
            document.getElementById('inspect-tgt-date').textContent = this.getAttribute('data-tgt-date');

            const label = this.getAttribute('data-status-label');
            const cssClass = this.getAttribute('data-status-class');

            document.getElementById('inspect-veredicto-text').textContent = label;
            const badge = document.getElementById('inspect-veredicto-badge');
            badge.className = `badge ${cssClass} fs-3 px-3 py-2`;
            badge.textContent = label;

            inspectModal.show();
        });
    });

    // 5. Modal Resumen por Órbita al pulsar "Ver Reporte"
    const orbitModalEl = document.getElementById('modal-resumen-orbitas');
    const orbitModal = new bootstrap.Modal(orbitModalEl);

    document.querySelectorAll('.btn-ver-reporte-orbita').forEach(button => {
        button.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            if (!url) return;

            // Mostrar modal y estado de carga
            document.getElementById('m-orbit-loader').style.display = 'block';
            document.getElementById('m-orbit-content').style.display = 'none';
            document.getElementById('m-orbit-subtitle').textContent = 'Cargando datos de la verificación...';
            orbitModal.show();

            fetch(url, { credentials: 'same-origin' })
                .then(res => {
                    if (!res.ok) throw new Error('Error en el servidor al consultar el resumen');
                    return res.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo obtener el resumen');
                    }

                    // Encabezado y KPIs
                    const v = data.verification;
                    document.getElementById('m-orbit-subtitle').textContent = 
                        `Tarea: ${v.task_name} • Ejecución: ${v.executed_at} • Duración: ${v.duration}`;
                    document.getElementById('m-kpi-orbits').textContent = data.total_orbits_count;
                    document.getElementById('m-kpi-files').textContent = Number(v.total_files || 0).toLocaleString();
                    document.getElementById('m-kpi-confirmed').textContent = Number(v.total_confirmed || 0).toLocaleString();
                    document.getElementById('m-kpi-percent').textContent = `${v.percentage}%`;
                    document.getElementById('m-badge-total-count').textContent = `${data.total_orbits_count} órbita(s)`;

                    // Tabla de órbitas
                    const tbody = document.getElementById('m-orbit-tbody');
                    tbody.innerHTML = '';

                    if (!data.orbits || data.orbits.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center py-4 text-secondary">
                                    <i class="ti ti-folder-off fs-1 d-block mb-2 text-muted"></i>
                                    No hay registros de órbitas disponibles para esta verificación.
                                </td>
                            </tr>
                        `;
                    } else {
                        data.orbits.forEach(o => {
                            const p1Percent = o.total_files > 0 ? Math.round((o.phase1_ok / o.total_files) * 100) : 0;
                            const p2Percent = o.total_files > 0 ? Math.round((o.phase2_ok / o.total_files) * 100) : 0;
                            
                            const is100 = o.percentage >= 100;
                            const badgeEstado = is100
                                ? `<span class="badge bg-green-lt text-green fw-bold px-2 py-1"><i class="ti ti-check me-1"></i> Órbita OK (100%)</span>`
                                : `<span class="badge bg-warning-lt text-warning fw-bold px-2 py-1"><i class="ti ti-alert-triangle me-1"></i> Incompleta (${o.percentage}%)</span>`;

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs bg-primary-lt text-primary me-2 rounded">
                                            <i class="ti ti-satellite"></i>
                                        </div>
                                        <div>
                                            <span class="font-monospace fw-bold text-dark d-block">${o.orbit}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-lt fw-bold">${Number(o.total_files).toLocaleString()} arch.</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <small class="text-secondary">Synology $\\rightarrow$ Intermedia</small>
                                        <span class="badge bg-green-lt text-green fw-bold" style="font-size: 0.75rem;">
                                            ${o.phase1_ok} / ${o.total_files}
                                        </span>
                                    </div>
                                    <div class="progress progress-xs">
                                        <div class="progress-bar bg-success" style="width: ${p1Percent}%"></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <small class="text-secondary">Intermedia $\\rightarrow$ Inspur</small>
                                        <span class="badge ${p2Percent === 100 ? 'bg-green-lt text-green' : 'bg-azure-lt text-azure'} fw-bold" style="font-size: 0.75rem;">
                                            ${o.phase2_ok} / ${o.total_files}
                                        </span>
                                    </div>
                                    <div class="progress progress-xs">
                                        <div class="progress-bar ${p2Percent === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${p2Percent}%"></div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace fw-bold">${o.human_size}</span>
                                </td>
                                <td class="text-center">
                                    ${badgeEstado}
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }

                    // Ocultar loader y mostrar contenido
                    document.getElementById('m-orbit-loader').style.display = 'none';
                    document.getElementById('m-orbit-content').style.display = 'block';
                })
                .catch(err => {
                    orbitModal.hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al consultar resumen',
                        text: err.message || 'No se pudo cargar el resumen por órbita.',
                        confirmButtonText: 'Entendido'
                    });
                });
        });
    });

    // 6. Modal Detalle de Archivos de una Órbita (conglomerado -> archivos individuales)
    const detailModalEl = document.getElementById('modal-detalle-archivos-orbita');
    const detailModal = new bootstrap.Modal(detailModalEl);
    let currentOrbitFiles = [];
    let currentActiveFilter = 'all';

    function renderDetailTable() {
        const tbody = document.getElementById('m-det-tbody');
        tbody.innerHTML = '';
        const searchVal = (document.getElementById('m-det-search-input').value || '').toLowerCase().trim();

        let filtered = currentOrbitFiles.filter(f => {
            if (currentActiveFilter === 'ok' && f.is_failed) return false;
            if (currentActiveFilter === 'failed' && !f.is_failed) return false;
            if (searchVal && !f.file_name.toLowerCase().includes(searchVal) && !f.relative_path.toLowerCase().includes(searchVal)) {
                return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-secondary">
                        <i class="ti ti-file-search fs-1 d-block mb-2 text-muted"></i>
                        No hay archivos que coincidan con el filtro actual.
                    </td>
                </tr>
            `;
            return;
        }

        filtered.forEach(item => {
            const tr = document.createElement('tr');
            if (item.is_failed) {
                tr.classList.add('table-danger');
            }

            // Fase 1 badge
            let f1Badge = `<span class="badge bg-secondary-lt text-secondary">No detectado</span>`;
            if (item.phase1_status === 'confirmed') {
                f1Badge = `<span class="badge bg-success text-white py-1 px-2"><i class="ti ti-check me-1"></i> Copiado OK</span>`;
            } else if (item.phase1_status === 'size_mismatch') {
                f1Badge = `<span class="badge bg-danger text-white py-1 px-2"><i class="ti ti-alert-triangle me-1"></i> Dif. Tamaño</span>`;
            } else if (item.phase1_status === 'moved_to_inspur') {
                f1Badge = `<span class="badge bg-teal-lt text-teal py-1 px-2"><i class="ti ti-truck-delivery me-1"></i> Trasladado a Inspur</span>`;
            }

            // Fase 2 badge
            let f2Badge = `<span class="badge bg-danger-lt text-danger py-1 px-2"><i class="ti ti-x me-1"></i> Pendiente</span>`;
            if (item.phase2_status === 'confirmed') {
                f2Badge = `<span class="badge bg-teal text-white py-1 px-2"><i class="ti ti-circle-check me-1"></i> RAWDATA OK</span><div class="small text-muted font-monospace mt-1">${item.human_inspur_size}</div>`;
            } else if (item.phase2_status === 'size_mismatch') {
                f2Badge = `<span class="badge bg-danger text-white py-1 px-2"><i class="ti ti-alert-triangle me-1"></i> Truncado</span>`;
            }

            // Veredicto
            let verdictBadge = item.is_failed
                ? `<span class="badge bg-danger text-white fw-bold px-2 py-1"><i class="ti ti-alert-triangle me-1"></i> Fallido</span>`
                : `<span class="badge bg-green-lt text-green fw-bold px-2 py-1"><i class="ti ti-circle-check me-1"></i> 100% OK</span>`;

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-xs ${item.is_failed ? 'bg-danger text-white' : 'bg-azure-lt text-azure'} rounded me-2">
                            <i class="ti ${item.is_failed ? 'ti-alert-circle' : 'ti-file'}"></i>
                        </span>
                        <div class="text-truncate" style="max-width: 320px;">
                            <span class="fw-bold text-dark d-block text-truncate" title="${item.file_name}">${item.file_name}</span>
                            <small class="text-secondary font-monospace d-block text-truncate" title="${item.relative_path}">${item.relative_path}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="fw-bold text-dark">${item.human_synology_size}</div>
                    <small class="text-secondary font-monospace">${item.synology_mtime}</small>
                </td>
                <td class="text-center">${f1Badge}</td>
                <td class="text-center">${f2Badge}</td>
                <td class="text-center">
                    <span class="badge ${item.name_match ? 'bg-green-lt text-green' : 'bg-danger-lt text-danger'} me-1">Nom</span>
                    <span class="badge ${item.size_match || item.phase2_status === 'confirmed' ? 'bg-green-lt text-green' : 'bg-danger-lt text-danger'}">Tam</span>
                </td>
                <td class="text-center">${verdictBadge}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Listener para abrir el detalle de la órbita
    document.querySelectorAll('.btn-ver-detalle-orbita').forEach(button => {
        button.addEventListener('click', function () {
            const orbitName = this.getAttribute('data-orbit');
            const url = this.getAttribute('data-url');
            if (!url) return;

            document.getElementById('m-det-orbit-title').textContent = orbitName;
            document.getElementById('m-det-loader').style.display = 'block';
            document.getElementById('m-det-content').style.display = 'none';
            document.getElementById('m-det-search-input').value = '';
            currentActiveFilter = 'all';
            document.querySelectorAll('.btn-filter-orbit-files').forEach(b => b.classList.remove('active'));
            document.querySelector('.btn-filter-orbit-files[data-filter="all"]').classList.add('active');

            detailModal.show();

            fetch(url, { credentials: 'same-origin' })
                .then(res => {
                    if (!res.ok) throw new Error('Error al consultar archivos de la órbita');
                    return res.json();
                })
                .then(data => {
                    if (!data.success) throw new Error(data.message || 'Error en respuesta');

                    currentOrbitFiles = data.files || [];

                    document.getElementById('m-det-total').textContent = Number(data.total_files || 0).toLocaleString();
                    document.getElementById('m-det-correct').textContent = Number(data.correct_count || 0).toLocaleString();
                    document.getElementById('m-det-failed').textContent = Number(data.failed_count || 0).toLocaleString();

                    document.getElementById('count-all').textContent = data.total_files || 0;
                    document.getElementById('count-failed').textContent = data.failed_count || 0;
                    document.getElementById('count-ok').textContent = data.correct_count || 0;

                    renderDetailTable();

                    document.getElementById('m-det-loader').style.display = 'none';
                    document.getElementById('m-det-content').style.display = 'block';
                })
                .catch(err => {
                    detailModal.hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: err.message || 'No se pudieron cargar los archivos de la órbita.',
                        confirmButtonText: 'Entendido'
                    });
                });
        });
    });

    // Filtros por botón (Todos / Solo Fallidos / Solo Correctos)
    document.querySelectorAll('.btn-filter-orbit-files').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-filter-orbit-files').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentActiveFilter = this.getAttribute('data-filter');
            renderDetailTable();
        });
    });

    // Búsqueda en vivo
    document.getElementById('m-det-search-input')?.addEventListener('input', function () {
        renderDetailTable();
    });
});
</script>
@endpush

