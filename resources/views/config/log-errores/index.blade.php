@extends('layouts.app')

@section('title', 'Log de Errores')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    SEGURIDAD, MONITOREO &amp; EXCEPCIONES
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-bug me-2 text-danger" style="font-size: 1.6rem;"></i>Log de Errores del Sistema
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                @can('gestionar-logs')
                <form id="form-simulate-error" action="{{ route('log-errores.simulate') }}" method="POST" class="m-0">
                    @csrf
                    <button type="button" id="btn-simulate-error" class="btn btn-outline-danger btn-sm fw-bold">
                        <i class="ti ti-flame me-1 fs-3"></i> Simular Error de Prueba
                    </button>
                </form>
                @endcan

                <span class="badge bg-green-lt text-green px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.85rem; border: 1px solid rgba(47, 179, 68, 0.25);">
                    <i class="ti ti-shield-check me-1 fs-3"></i> Monitor Activo
                </span>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">



        {{-- Stat Cards --}}
        <div class="row row-cards g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-dark text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-bug fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-dark">{{ $totalErrores }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">TOTAL ERRORES</div>
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
                                <i class="ti ti-clock fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-danger">{{ $totalPendientes }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">PENDIENTES</div>
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
                                <i class="ti ti-eye fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-warning">{{ $totalEnRevision }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">EN REVISIÓN</div>
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
                                <i class="ti ti-check fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-success">{{ $totalResueltos }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">RESUELTOS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <form id="filter-errors-form" action="{{ route('log-errores.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase mb-1">
                                <i class="ti ti-flame me-1 text-danger"></i> Nivel de Severidad
                            </label>
                            <select name="nivel" class="form-select">
                                <option value="all">Todos los Niveles</option>
                                @foreach($niveles as $key => $label)
                                    <option value="{{ $key }}" {{ request('nivel') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase mb-1">
                                <i class="ti ti-activity me-1 text-primary"></i> Estado de Resolución
                            </label>
                            <select name="estado" class="form-select">
                                <option value="all">Todos los Estados</option>
                                @foreach($estados as $key => $label)
                                    <option value="{{ $key }}" {{ request('estado') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase mb-1">
                                <i class="ti ti-calendar me-1 text-primary"></i> Desde
                            </label>
                            <input type="date" name="desde" class="form-control" value="{{ request('desde') }}">
                        </div>

                        <div class="col-md-2 col-sm-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase mb-1">
                                <i class="ti ti-calendar me-1 text-primary"></i> Hasta
                            </label>
                            <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}">
                        </div>

                        <div class="col-md-2 col-sm-12 text-end">
                            <div class="d-flex gap-2">
                                <a href="{{ route('log-errores.index') }}" class="btn btn-outline-secondary w-50" title="Limpiar Filtros">
                                    <i class="ti ti-rotate"></i>
                                </a>
                                <button type="submit" class="btn btn-primary w-50 fw-bold">
                                    <i class="ti ti-filter"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Table Card --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body border-bottom py-3">
                <div class="row align-items-center g-2">
                    <div class="col-auto">
                        <div class="d-flex align-items-center text-secondary">
                            <span>Mostrar</span>
                            <div class="mx-2">
                                <select id="select-per-page" class="form-select form-select-sm" style="width: 70px;">
                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                    <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                            <span>registros</span>
                        </div>
                    </div>

                    <div class="col-auto ms-auto d-flex align-items-center flex-wrap gap-2">
                        @can('ver-logs')
                        <a href="{{ route('log-errores.export', request()->all()) }}" class="btn btn-outline-success btn-sm fw-bold">
                            <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Exportar a Excel
                        </a>
                        @endcan

                        @can('gestionar-logs')
                        @if($totalResueltos > 0)
                        <form id="form-clear-resolved" action="{{ route('log-errores.clear') }}" method="POST" class="m-0">
                            @csrf
                            <input type="hidden" name="mode" value="resueltos">
                            <button type="button" id="btn-clear-resolved" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-trash me-1"></i> Limpiar Resueltos
                            </button>
                        </form>
                        @endif
                        @endcan

                        <form action="{{ route('log-errores.index') }}" method="GET" class="d-flex align-items-center m-0">
                            @if(request('nivel')) <input type="hidden" name="nivel" value="{{ request('nivel') }}"> @endif
                            @if(request('estado')) <input type="hidden" name="estado" value="{{ request('estado') }}"> @endif
                            @if(request('desde')) <input type="hidden" name="desde" value="{{ request('desde') }}"> @endif
                            @if(request('hasta')) <input type="hidden" name="hasta" value="{{ request('hasta') }}"> @endif
                            @if(request('per_page')) <input type="hidden" name="per_page" value="{{ request('per_page') }}"> @endif

                            <div class="d-flex align-items-center">
                                <label class="text-secondary small me-2 mb-0">Buscar:</label>
                                <div class="input-icon">
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Mensaje, clase, archivo..." value="{{ request('search') }}">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search text-secondary"></i>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr class="text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                            <th class="border-bottom-0">FECHA / HORA <i class="ti ti-arrows-sort text-muted ms-1"></i></th>
                            <th class="border-bottom-0">NIVEL &amp; CÓDIGO</th>
                            <th class="border-bottom-0" style="min-width: 280px;">EXCEPCIÓN &amp; MENSAJE</th>
                            <th class="border-bottom-0">USUARIO AFECTADO</th>
                            <th class="border-bottom-0">RUTA / PETICIÓN</th>
                            <th class="border-bottom-0">ESTADO</th>
                            <th class="text-center border-bottom-0 w-1">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($errores as $err)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $err->created_at->format('d/m/Y') }}</div>
                                <div class="text-secondary small">{{ $err->created_at->format('h:i:s A') }}</div>
                            </td>
                            <td>
                                @php $nb = $err->nivel_badge; @endphp
                                <div>
                                    <span class="badge {{ $nb['class'] }} fw-bold px-2 py-1">
                                        <i class="{{ $nb['icon'] }} me-1"></i> {{ $nb['label'] }}
                                    </span>
                                </div>
                                @if($err->codigo)
                                    <div class="text-secondary small mt-1 font-monospace">Cod: {{ $err->codigo }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $err->clase_error }}">
                                    {{ class_basename($err->clase_error) }}
                                </div>
                                <div class="text-secondary small text-truncate mt-1" style="max-width: 340px;" title="{{ $err->mensaje }}">
                                    {{ $err->mensaje }}
                                </div>
                                <div class="small font-monospace text-muted mt-1" style="font-size: 0.72rem;">
                                    <i class="ti ti-file-code me-1"></i>{{ $err->archivo_corto }}:{{ $err->linea }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $err->user_name ?? 'Invitado / CLI' }}</div>
                                @if($err->user_email)
                                    <div class="text-secondary small">{{ $err->user_email }}</div>
                                @endif
                                <div class="text-muted small font-monospace mt-1">IP: {{ $err->ip_address ?? '127.0.0.1' }}</div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-blue-lt me-1 fw-bold">{{ $err->metodo_http ?? 'GET' }}</span>
                                </div>
                                <div class="text-secondary small text-truncate mt-1 font-monospace" style="max-width: 220px;" title="{{ $err->url }}">
                                    {{ parse_url($err->url, PHP_URL_PATH) ?? $err->url }}
                                </div>
                            </td>
                            <td>
                                @php $eb = $err->estado_badge; @endphp
                                <span class="badge {{ $eb['class'] }} fw-bold px-2 py-1" id="badge-estado-{{ $err->id }}">
                                    <i class="{{ $eb['icon'] }} me-1"></i> {{ $eb['label'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-icon btn-outline-primary btn-sm btn-show-error" data-id="{{ $err->id }}" title="Ver Detalle y Diagnóstico">
                                        <i class="ti ti-eye fs-2"></i>
                                    </button>
                                    @can('gestionar-logs')
                                    <button type="button" class="btn btn-icon btn-outline-danger btn-sm ms-1 btn-delete-error" 
                                            data-url="{{ route('log-errores.destroy', $err->id) }}" 
                                            data-id="{{ $err->id }}" 
                                            title="Eliminar Error">
                                        <i class="ti ti-trash fs-2"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="ti ti-shield-check fs-1 d-block mb-2 text-success"></i>
                                <strong>¡Excelente! No hay errores registrados con los criterios seleccionados.</strong>
                                <div class="text-muted small mt-1">El sistema se encuentra operando con normalidad.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex align-items-center py-3">
                <p class="m-0 text-secondary">
                    Mostrando <span>{{ $errores->firstItem() ?? 0 }}</span> a <span>{{ $errores->lastItem() ?? 0 }}</span> de <span>{{ $errores->total() }}</span> registros
                </p>
                <div class="pagination m-0 ms-auto">
                    {{ $errores->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Error Details & Resolution Modal --}}
<div class="modal modal-blur fade" id="modal-error-details" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="ti ti-bug me-2 text-danger fs-2"></i>
                        Detalle del Error #<span id="modal-err-id"></span>
                        <span class="ms-2" id="modal-err-level-badge"></span>
                        <span class="ms-2" id="modal-err-status-badge"></span>
                    </h5>
                    <div class="text-secondary small mt-1" id="modal-err-class"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                {{-- Alert with main error message --}}
                <div class="alert alert-danger d-flex align-items-start mb-4" role="alert">
                    <i class="ti ti-alert-triangle fs-1 me-3 text-danger"></i>
                    <div class="flex-fill">
                        <div class="fw-bold" style="font-size: 1rem;" id="modal-err-message"></div>
                        <div class="small text-secondary mt-1 font-monospace" id="modal-err-location"></div>
                    </div>
                </div>

                {{-- Summary Meta Grid --}}
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small text-uppercase fw-bold">Fecha / Hora</div>
                        <div class="fw-bold text-dark mt-1" id="modal-err-date"></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small text-uppercase fw-bold">Usuario Afectado</div>
                        <div class="fw-bold text-dark mt-1" id="modal-err-user"></div>
                        <div class="small text-secondary" id="modal-err-email"></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small text-uppercase fw-bold">Petición HTTP &amp; IP</div>
                        <div class="fw-bold text-dark mt-1" id="modal-err-http"></div>
                        <div class="small font-monospace text-secondary" id="modal-err-ip"></div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="text-secondary small text-uppercase fw-bold">Resolución</div>
                        <div class="fw-bold text-dark mt-1" id="modal-err-resolver"></div>
                        <div class="small text-secondary" id="modal-err-resolved-at"></div>
                    </div>

                    <div class="col-12">
                        <div class="text-secondary small text-uppercase fw-bold">URL Completa</div>
                        <div class="small text-dark font-monospace bg-light p-2 rounded mt-1 border text-break" id="modal-err-url"></div>
                    </div>
                </div>

                <hr class="my-3 opacity-50">

                {{-- Tabs for Stack Trace vs Payload vs Resolution Form --}}
                <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#err-tab-trace">
                            <i class="ti ti-terminal-2 me-1"></i> Stack Trace
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" data-bs-toggle="tab" href="#err-tab-payload">
                            <i class="ti ti-code me-1"></i> Parámetros &amp; Headers
                        </a>
                    </li>
                    @can('gestionar-logs')
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-primary" data-bs-toggle="tab" href="#err-tab-resolution">
                            <i class="ti ti-check me-1"></i> Gestión &amp; Solución
                        </a>
                    </li>
                    @endcan
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Stack Trace --}}
                    <div class="tab-pane active" id="err-tab-trace">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-secondary fw-bold">Traza de ejecución en el código fuente:</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copy-trace">
                                <i class="ti ti-copy me-1"></i> Copiar Trace
                            </button>
                        </div>
                        <pre id="modal-err-trace" class="bg-dark text-light p-3 rounded" style="max-height: 320px; overflow-y: auto; font-size: 0.75rem; line-height: 1.5;"></pre>
                    </div>

                    {{-- Tab 2: Payload & Headers --}}
                    <div class="tab-pane" id="err-tab-payload">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold small">Request Data (Parámetros sanitizados):</label>
                                <pre id="modal-err-payload" class="bg-dark text-light p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.75rem;"></pre>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold small">Cabeceras HTTP:</label>
                                <pre id="modal-err-headers" class="bg-dark text-light p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.75rem;"></pre>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Resolution Form --}}
                    @can('gestionar-logs')
                    <div class="tab-pane" id="err-tab-resolution">
                        <form id="form-update-status" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label required fw-bold">Estado de Resolución</label>
                                    <select name="estado" id="modal-input-status" class="form-select fw-bold">
                                        <option value="pendiente" class="text-danger">Pendiente</option>
                                        <option value="en_revision" class="text-warning">En Revisión</option>
                                        <option value="resuelto" class="text-success">Resuelto</option>
                                        <option value="ignorado" class="text-secondary">Ignorado</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Notas Técnicas / Solución Aplicada</label>
                                    <textarea name="solucion_notas" id="modal-input-notes" class="form-control" rows="4" placeholder="Describe aquí la causa raíz del error, el commit o parche aplicado, o el motivo de ignorarlo..."></textarea>
                                </div>
                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success fw-bold px-4" id="btn-save-status">
                                        <i class="ti ti-device-floppy me-1"></i> Guardar Cambios de Resolución
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endcan
                </div>

            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-danger ms-auto fw-bold" data-bs-dismiss="modal">CERRAR</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('modal-error-details');
        const errorModal = modalElement ? new bootstrap.Modal(modalElement) : null;
        let currentErrorId = null;

        // Handle Per Page change
        const perPageSelect = document.getElementById('select-per-page');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.set('page', '1');
                window.location.href = url.toString();
            });
        }

        // Copy Trace Button with SweetAlert Toast
        document.getElementById('btn-copy-trace')?.addEventListener('click', function() {
            const traceText = document.getElementById('modal-err-trace').textContent;
            navigator.clipboard.writeText(traceText).then(() => {
                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Stack trace copiado al portapapeles',
                        showConfirmButton: false,
                        timer: 2000
                    });
                } else {
                    this.innerHTML = '<i class="ti ti-check me-1"></i> ¡Copiado!';
                    setTimeout(() => {
                        this.innerHTML = '<i class="ti ti-copy me-1"></i> Copiar Trace';
                    }, 2000);
                }
            });
        });

        // Simulate Error with SweetAlert2
        document.getElementById('btn-simulate-error')?.addEventListener('click', function() {
            Swal.fire({
                title: '¿Simular error de prueba?',
                text: 'Se generará una excepción controlada para comprobar el registro y trigger de captura automática.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                confirmButtonText: '<i class="ti ti-flame me-1"></i> Sí, simular error',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-simulate-error').submit();
                }
            });
        });

        // Clear Resolved Errors with SweetAlert2
        document.getElementById('btn-clear-resolved')?.addEventListener('click', function() {
            Swal.fire({
                title: '¿Limpiar errores resueltos?',
                text: 'Se eliminarán permanentemente todos los registros marcados como Resueltos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                confirmButtonText: '<i class="ti ti-trash me-1"></i> Sí, limpiar resueltos',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-clear-resolved').submit();
                }
            });
        });

        // Delete Individual Error with SweetAlert2
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-error');
            if (btn) {
                const url = btn.dataset.url;
                const id = btn.dataset.id;
                Swal.fire({
                    title: '¿Eliminar registro de error?',
                    text: `Se eliminará el registro #${id}. Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#2fb344',
                    cancelButtonColor: '#d63939',
                    confirmButtonText: '<i class="ti ti-trash me-1"></i> Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = `
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content}">
                            <input type="hidden" name="_method" value="DELETE">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        });

        // Show Error Details Modal
        document.querySelectorAll('.btn-show-error').forEach(btn => {
            btn.addEventListener('click', function() {
                currentErrorId = this.dataset.id;
                fetch(`{{ url('configuracion/log-errores') }}/${currentErrorId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modal-err-id').textContent = data.id;
                    document.getElementById('modal-err-class').textContent = data.clase_error;
                    document.getElementById('modal-err-message').textContent = data.mensaje;
                    document.getElementById('modal-err-location').textContent = `${data.archivo}:${data.linea}`;
                    
                    const nb = data.nivel_badge;
                    document.getElementById('modal-err-level-badge').innerHTML = `
                        <span class="badge ${nb.class} fw-bold px-2 py-1">
                            <i class="${nb.icon} me-1"></i> ${nb.label}
                        </span>
                    `;

                    const eb = data.estado_badge;
                    document.getElementById('modal-err-status-badge').innerHTML = `
                        <span class="badge ${eb.class} fw-bold px-2 py-1">
                            <i class="${eb.icon} me-1"></i> ${eb.label}
                        </span>
                    `;

                    document.getElementById('modal-err-date').innerHTML = `${data.fecha} <span class="text-secondary small">(${data.hora})</span>`;
                    document.getElementById('modal-err-user').textContent = data.user_name || 'Invitado / CLI';
                    document.getElementById('modal-err-email').textContent = data.user_email || 'Sin correo asociado';

                    document.getElementById('modal-err-http').innerHTML = `<span class="badge bg-blue-lt me-1">${data.metodo_http || 'GET'}</span>`;
                    document.getElementById('modal-err-ip').textContent = `IP: ${data.ip_address || '127.0.0.1'}`;

                    document.getElementById('modal-err-resolver').textContent = data.resuelto_por ? `Por: ${data.resuelto_por}` : 'No resuelto';
                    document.getElementById('modal-err-resolved-at').textContent = data.resuelto_at ? `Fecha: ${data.resuelto_at}` : 'En espera';

                    document.getElementById('modal-err-url').textContent = data.url || 'N/A';
                    document.getElementById('modal-err-trace').textContent = data.trace || 'Sin traza disponible.';
                    
                    document.getElementById('modal-err-payload').textContent = JSON.stringify(data.request_data || {}, null, 2);
                    document.getElementById('modal-err-headers').textContent = JSON.stringify(data.headers || {}, null, 2);

                    // Setup Form in Resolution Tab
                    const formStatus = document.getElementById('form-update-status');
                    if (formStatus) {
                        formStatus.action = `{{ url('configuracion/log-errores') }}/${data.id}/estado`;
                        document.getElementById('modal-input-status').value = data.estado || 'pendiente';
                        document.getElementById('modal-input-notes').value = data.solucion_notas || '';
                    }

                    if (errorModal) {
                        errorModal.show();
                    }
                })
                .catch(err => {
                    console.error('Error fetching error log details:', err);
                });
            });
        });

        // Submit Status Update via AJAX
        document.getElementById('form-update-status')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const btnSave = document.getElementById('btn-save-status');
            btnSave.disabled = true;

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (errorModal) errorModal.hide();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        location.reload();
                    }
                }
                btnSave.disabled = false;
            })
            .catch(err => {
                console.error(err);
                btnSave.disabled = false;
            });
        });
    });
</script>
@endpush
