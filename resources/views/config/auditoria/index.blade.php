@extends('layouts.app')

@section('title', 'Auditoría de Cambios')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    SEGURIDAD, TRAZABILIDAD &amp; TRIGGERS
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-history me-2 text-primary" style="font-size: 1.6rem;"></i>Auditoría de Cambios del Sistema
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <span class="badge bg-blue-lt text-blue px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.85rem; border: 1px solid rgba(32, 107, 196, 0.25);">
                    <i class="ti ti-activity me-1 fs-3"></i> Registro Activo de Triggers
                </span>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <!-- Data Table Card -->
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

                    <div class="col-auto ms-auto d-flex align-items-center">
                        <a href="{{ route('auditorias.export', request()->only(['search', 'per_page'])) }}" class="btn btn-outline-success btn-sm me-3 fw-bold">
                            <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Exportar a Excel
                        </a>

                        <form action="{{ route('auditorias.index') }}" method="GET" class="d-flex align-items-center m-0">
                            @if(request('per_page')) <input type="hidden" name="per_page" value="{{ request('per_page') }}"> @endif

                            <div class="d-flex align-items-center">
                                <label class="text-secondary small me-2 mb-0">Buscar:</label>
                                <div class="input-icon">
                                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Filtrar registros..." value="{{ request('search') }}">
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
                            <th class="border-bottom-0">USUARIO / ROL</th>
                            <th class="border-bottom-0">MÓDULO</th>
                            <th class="border-bottom-0">ACCIÓN</th>
                            <th class="border-bottom-0">REGISTRO AFECTADO</th>
                            <th class="border-bottom-0">IP / ORIGEN</th>
                            <th class="text-center border-bottom-0 w-1">DETALLES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditorias as $audit)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $audit->created_at->format('d/m/Y') }}</div>
                                <div class="text-secondary small">{{ $audit->created_at->format('h:i:s A') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $audit->user_name ?? 'Sistema' }}</div>
                                <div>
                                    <span class="badge bg-blue-lt text-blue text-capitalize px-2 py-0 fw-bold" style="font-size: 0.72rem;">
                                        {{ $audit->user_role ?? 'Usuario' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-secondary-lt fw-bold px-2 py-1">{{ $audit->modulo }}</span>
                                </div>
                                <div class="text-secondary small mt-1">Tabla: {{ $audit->tabla }}</div>
                            </td>
                            <td>
                                @php $b = $audit->accion_badge; @endphp
                                <span class="badge {{ $b['class'] }} fw-bold px-2 py-1">
                                    <i class="{{ $b['icon'] }} me-1"></i> {{ $b['label'] }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($audit->registro_id)
                                        <span class="badge bg-secondary-lt text-secondary fw-bold me-2 px-2 py-1">{{ $audit->registro_id }}</span>
                                    @endif
                                    <span class="fw-bold text-dark">{{ $audit->registro_nombre ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark small d-flex align-items-center">
                                    <i class="ti ti-world me-1 text-primary"></i>
                                    <span class="font-monospace">{{ $audit->ip_address ?? '127.0.0.1' }}</span>
                                </div>
                                <div class="text-secondary small d-flex align-items-center mt-1">
                                    <i class="ti ti-device-desktop me-1"></i>
                                    <span>{{ $audit->browser }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-icon btn-outline-primary btn-sm btn-show-audit" data-id="{{ $audit->id }}" title="Ver Detalles">
                                    <i class="ti ti-eye fs-2"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">
                                <i class="ti ti-inbox fs-1 d-block mb-2 text-muted"></i>
                                No se encontraron registros de auditoría para los criterios seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex align-items-center py-3">
                <p class="m-0 text-secondary">
                    Mostrando <span>{{ $auditorias->firstItem() ?? 0 }}</span> a <span>{{ $auditorias->lastItem() ?? 0 }}</span> de <span>{{ $auditorias->total() }}</span> registros
                </p>
                <div class="pagination m-0 ms-auto">
                    {{ $auditorias->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Details Modal --}}
<div class="modal modal-blur fade" id="modal-audit-details" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="ti ti-file-text me-2 text-primary fs-2"></i>
                        Detalle del Evento de Auditoría #<span id="modal-audit-id"></span>
                    </h5>
                    <div class="text-secondary small mt-1">
                        Información registrada automáticamente por el trigger del sistema
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Metadata Grid --}}
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">Fecha / Hora</div>
                        <div class="fw-bold text-dark mt-1" id="modal-audit-date"></div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">Usuario Responsable</div>
                        <div class="fw-bold text-dark mt-1" id="modal-audit-user"></div>
                        <div class="small text-secondary" id="modal-audit-role"></div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">Acción Realizada</div>
                        <div class="mt-1" id="modal-audit-action"></div>
                    </div>

                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">Módulo / Tabla</div>
                        <div class="fw-bold text-dark mt-1" id="modal-audit-module"></div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">Registro Afectado</div>
                        <div class="fw-bold text-dark mt-1" id="modal-audit-record"></div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="text-secondary small text-uppercase fw-bold">IP &amp; Origen</div>
                        <div class="fw-bold text-dark mt-1 font-monospace" id="modal-audit-ip"></div>
                        <div class="small text-secondary" id="modal-audit-browser"></div>
                    </div>

                    <div class="col-12">
                        <div class="text-secondary small text-uppercase fw-bold">Ruta / URL &amp; Método</div>
                        <div class="small text-dark font-monospace bg-light p-2 rounded mt-1 border" id="modal-audit-url"></div>
                    </div>
                </div>

                <hr class="my-3 opacity-50">

                {{-- Tabs for Attribute Diff vs Raw JSON --}}
                <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" data-bs-toggle="tab" href="#tab-diff">
                            <i class="ti ti-git-commit me-1"></i> Campos Modificados
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold" data-bs-toggle="tab" href="#tab-raw">
                            <i class="ti ti-code me-1"></i> Data Cruda (JSON)
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Diff Table --}}
                    <div class="tab-pane active" id="tab-diff">
                        <div class="table-responsive border rounded">
                            <table class="table table-vcenter table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 25%;">Campo</th>
                                        <th style="width: 37.5%;" class="text-danger">Valor Anterior</th>
                                        <th style="width: 37.5%;" class="text-success">Valor Nuevo</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-diff-body">
                                    {{-- Dynamically populated via JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab 2: Raw JSON --}}
                    <div class="tab-pane" id="tab-raw">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label text-danger fw-bold small">Data Anterior (JSON):</label>
                                <pre id="modal-raw-before" class="bg-dark text-light p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.75rem;"></pre>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-success fw-bold small">Data Nueva (JSON):</label>
                                <pre id="modal-raw-after" class="bg-dark text-light p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.75rem;"></pre>
                            </div>
                        </div>
                    </div>
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
        const detailsModal = new bootstrap.Modal(document.getElementById('modal-audit-details'));

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

        // Handle Details Button Click
        document.querySelectorAll('.btn-show-audit').forEach(btn => {
            btn.addEventListener('click', function() {
                const auditId = this.dataset.id;
                fetch(`{{ url('configuracion/auditoria') }}/${auditId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modal-audit-id').textContent = data.id;
                    document.getElementById('modal-audit-date').innerHTML = `${data.fecha} <span class="text-secondary small">(${data.hora})</span>`;
                    document.getElementById('modal-audit-user').textContent = data.user_name || 'Sistema';
                    document.getElementById('modal-audit-role').textContent = `${data.user_role || 'Usuario'} • ${data.user_email || 'Sin correo'}`;
                    
                    const badge = data.badge;
                    document.getElementById('modal-audit-action').innerHTML = `
                        <span class="badge ${badge.class} fw-bold px-2 py-1">
                            <i class="${badge.icon} me-1"></i> ${badge.label}
                        </span>
                    `;

                    document.getElementById('modal-audit-module').innerHTML = `
                        <span class="badge bg-secondary-lt fw-bold">${data.modulo}</span>
                        <div class="text-secondary small mt-1">Tabla: <code>${data.tabla}</code></div>
                    `;

                    document.getElementById('modal-audit-record').innerHTML = `
                        ${data.registro_id ? `<span class="badge bg-secondary-lt me-1">${data.registro_id}</span>` : ''}
                        <span>${data.registro_nombre || 'N/A'}</span>
                    `;

                    document.getElementById('modal-audit-ip').textContent = data.ip_address || '127.0.0.1';
                    document.getElementById('modal-audit-browser').textContent = data.browser;

                    document.getElementById('modal-audit-url').innerHTML = `
                        <span class="badge bg-blue-lt me-2">${data.metodo_http || 'GET'}</span>
                        <span>${data.url || 'N/A'}</span>
                    `;

                    // Populate Diff Table
                    const diffBody = document.getElementById('modal-diff-body');
                    diffBody.innerHTML = '';

                    const before = data.data_anterior || {};
                    const after = data.data_nueva || {};
                    const keys = Array.from(new Set([
                        ...(data.campos_modificados || []),
                        ...Object.keys(before),
                        ...Object.keys(after)
                    ]));

                    if (keys.length === 0) {
                        diffBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">No hay registros detallados de atributos para este evento.</td></tr>`;
                    } else {
                        keys.forEach(key => {
                            const valBefore = before[key] !== undefined ? (typeof before[key] === 'object' ? JSON.stringify(before[key]) : String(before[key])) : '<span class="text-muted fst-italic">null</span>';
                            const valAfter = after[key] !== undefined ? (typeof after[key] === 'object' ? JSON.stringify(after[key]) : String(after[key])) : '<span class="text-muted fst-italic">null</span>';

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="fw-bold font-monospace small">${key}</td>
                                <td class="text-danger font-monospace small bg-danger-lt">${valBefore}</td>
                                <td class="text-success font-monospace small bg-success-lt">${valAfter}</td>
                            `;
                            diffBody.appendChild(tr);
                        });
                    }

                    // Populate Raw JSON
                    document.getElementById('modal-raw-before').textContent = JSON.stringify(before, null, 2);
                    document.getElementById('modal-raw-after').textContent = JSON.stringify(after, null, 2);

                    detailsModal.show();
                })
                .catch(err => {
                    console.error('Error fetching audit details:', err);
                });
            });
        });
    });
</script>
@endpush
