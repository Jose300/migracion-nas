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
                <div class="spinner-border text-warning me-3" role="status"></div>
                <div class="flex-grow-1">
                    <h4 class="alert-title mb-1 fw-bold">Proceso de Cotejo y Comparación en Curso</h4>
                    <div class="text-secondary small">
                        El sistema está leyendo los metadatos de los archivos en la Synology y contrastándolos contra la NAS Inspur. La pantalla se actualizará automáticamente.
                    </div>
                    <div class="progress progress-sm mt-2">
                        <div class="progress-bar progress-bar-indeterminate bg-warning"></div>
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

        {{-- Tabla de Archivos Comparados --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center g-2">
                    <div class="col-md-4">
                        <h3 class="card-title text-dark fw-bold mb-0 d-flex align-items-center">
                            <i class="ti ti-table me-2 text-primary fs-2"></i> Cotejo Detallado de Archivos
                        </h3>
                        @if($verification)
                        <small class="text-secondary">
                            Sesión #{{ $verification->id }} &bull; Ejecutado el {{ $verification->started_at->format('d/m/Y H:i:s') }}
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
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre o ruta..." value="{{ request('search') }}">
                            </div>

                            <select name="status" class="form-select form-select-sm" style="width: 190px;" onchange="this.form.submit()">
                                <option value="all">Todos los Estados</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>✅ Confirmados (Idénticos)</option>
                                <option value="missing_target" {{ request('status') === 'missing_target' ? 'selected' : '' }}>❌ Faltantes en Inspur</option>
                                <option value="size_mismatch" {{ request('status') === 'size_mismatch' ? 'selected' : '' }}>⚠️ Diferencia de Tamaño</option>
                                <option value="date_mismatch" {{ request('status') === 'date_mismatch' ? 'selected' : '' }}>🕒 Diferencia de Fecha</option>
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
                            <th style="min-width: 240px;">Archivo / Ruta Relativa</th>
                            <th style="min-width: 170px;">NAS Synology (Origen)</th>
                            <th style="min-width: 170px;">NAS Inspur (Destino)</th>
                            <th class="text-center" style="min-width: 140px;">Parámetros</th>
                            <th class="text-center" style="min-width: 150px;">Veredicto</th>
                            <th class="text-end" style="width: 80px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-xs bg-azure-lt text-azure rounded me-2">
                                        <i class="ti ti-file fs-4"></i>
                                    </span>
                                    <div class="text-truncate" style="max-width: 320px;">
                                        <span class="fw-bold text-dark d-block text-truncate" title="{{ $item->file_name }}">
                                            {{ $item->file_name }}
                                        </span>
                                        <code class="text-secondary small text-truncate d-block" title="{{ $item->relative_path }}">
                                            {{ $item->relative_path }}
                                        </code>
                                    </div>
                                </div>
                            </td>

                            {{-- Synology --}}
                            <td>
                                <div class="fw-semibold text-dark">{{ $item->human_synology_size }}</div>
                                <div class="text-secondary small font-monospace">
                                    {{ $item->synology_mtime ? $item->synology_mtime->format('d/m/Y H:i:s') : '-' }}
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ number_format($item->synology_size) }} B</small>
                            </td>

                            {{-- Inspur --}}
                            <td>
                                @if($item->inspur_size !== null)
                                    <div class="fw-semibold text-dark">{{ $item->human_inspur_size }}</div>
                                    <div class="text-secondary small font-monospace">
                                        {{ $item->inspur_mtime ? $item->inspur_mtime->format('d/m/Y H:i:s') : '-' }}
                                    </div>
                                    <small class="text-muted" style="font-size: 0.72rem;">{{ number_format($item->inspur_size) }} B</small>
                                @else
                                    <span class="badge bg-danger-lt text-danger fw-bold">
                                        <i class="ti ti-x me-1"></i> No existe en destino
                                    </span>
                                @endif
                            </td>

                            {{-- Parámetros evaluados --}}
                            <td class="text-center">
                                <div class="d-inline-flex gap-1" style="font-size: 0.74rem;">
                                    {{-- Nombre --}}
                                    <span class="badge bg-green-lt text-green" title="Nombre: Coincide">
                                        <i class="ti ti-check"></i> Nom
                                    </span>

                                    {{-- Tamaño --}}
                                    @if($item->size_match)
                                        <span class="badge bg-green-lt text-green" title="Tamaño: Idéntico ({{ number_format($item->synology_size) }} bytes)">
                                            <i class="ti ti-check"></i> Tam
                                        </span>
                                    @else
                                        <span class="badge bg-danger-lt text-danger" title="Tamaño: Discrepancia">
                                            <i class="ti ti-x"></i> Tam
                                        </span>
                                    @endif

                                    {{-- Fecha --}}
                                    @if($item->date_match)
                                        <span class="badge bg-green-lt text-green" title="Fecha: Sincronizada">
                                            <i class="ti ti-check"></i> Fec
                                        </span>
                                    @else
                                        <span class="badge bg-warning-lt text-warning" title="Fecha: Diferencia de modificación">
                                            <i class="ti ti-clock"></i> Fec
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Veredicto --}}
                            <td class="text-center">
                                <span class="badge {{ $item->status_badge_class }} fw-bold px-2 py-1">
                                    @if($item->status === 'confirmed')
                                        <i class="ti ti-circle-check me-1"></i> Confirmado
                                    @elseif($item->status === 'size_mismatch')
                                        <i class="ti ti-alert-triangle me-1"></i> Dif. Tamaño
                                    @elseif($item->status === 'date_mismatch')
                                        <i class="ti ti-clock me-1"></i> Dif. Fecha
                                    @elseif($item->status === 'missing_target')
                                        <i class="ti ti-circle-x me-1"></i> Faltante Inspur
                                    @else
                                        {{ $item->status_label }}
                                    @endif
                                </span>
                            </td>

                            {{-- Acción / Modal Detalle --}}
                            <td class="text-end">
                                <button type="button" 
                                        class="btn btn-outline-info btn-sm btn-inspect-file"
                                        data-name="{{ $item->file_name }}"
                                        data-path="{{ $item->relative_path }}"
                                        data-src-size="{{ $item->human_synology_size }} ({{ number_format($item->synology_size) }} bytes)"
                                        data-src-date="{{ $item->synology_mtime ? $item->synology_mtime->format('d/m/Y H:i:s') : '-' }}"
                                        data-tgt-size="{{ $item->human_inspur_size }} ({{ $item->inspur_size !== null ? number_format($item->inspur_size) . ' bytes' : 'N/A' }})"
                                        data-tgt-date="{{ $item->inspur_mtime ? $item->inspur_mtime->format('d/m/Y H:i:s') : '-' }}"
                                        data-status-label="{{ $item->status_label }}"
                                        data-status-class="{{ $item->status_badge_class }}"
                                        title="Ver comparación técnica detallada">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-secondary">
                                @if(!$verification)
                                    <i class="ti ti-scan text-secondary fs-1 mb-2 d-block"></i>
                                    <p class="fw-semibold mb-1">Aún no se ha realizado ninguna verificación para esta tarea.</p>
                                    <small class="text-muted">Haz clic en <strong>"Verificar y Comparar Ahora"</strong> o <strong>"Simular Cotejo"</strong> para contrastar los archivos.</small>
                                @else
                                    <i class="ti ti-file-search text-secondary fs-1 mb-2 d-block"></i>
                                    <p class="fw-semibold mb-0">No se encontraron archivos que coincidan con los filtros aplicados.</p>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-between">
                {{ $items->links() }}
            </div>
            @endif
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
                                <a href="{{ route('nas-migracion.confirmacion.index', ['task_id' => $h->nas_migration_task_id, 'verification_id' => $h->id]) }}" 
                                   class="btn btn-outline-dark btn-sm fw-bold">
                                    <i class="ti ti-file-text me-1"></i> Ver Reporte
                                </a>
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
            const taskName = this.getAttribute('data-task-name');
            const url = this.getAttribute('data-url');

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
                            Swal.fire({
                                icon: 'success',
                                title: 'Cotejo en Marcha',
                                text: data.message,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al iniciar',
                                text: data.message || 'No se pudo iniciar la verificación.',
                                confirmButtonText: 'Cerrar'
                            });
                        }
                    })
                    .catch(err => {
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
            const taskName = this.getAttribute('data-task-name');
            const url = this.getAttribute('data-url');

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
});
</script>
@endpush

