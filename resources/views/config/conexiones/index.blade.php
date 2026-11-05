@extends('layouts.app')

@section('title', 'Control de Conexiones al Sistema')

@push('css')
<!-- Vendor DataTables CSS -->
<link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables/responsive.bootstrap5.min.css') }}">
<style>
    .status-dot-animated {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        background-color: #2fb344;
        box-shadow: 0 0 0 0 rgba(47, 179, 68, 0.7);
        animation: pulse-green 1.8s infinite;
    }
    @keyframes pulse-green {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(47, 179, 68, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 8px rgba(47, 179, 68, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(47, 179, 68, 0);
        }
    }
    .user-agent-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 11px;
        word-break: break-all;
    }
</style>
@endpush

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-fluid container-xl">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-12 col-md">
                <div class="page-pretitle text-secondary">
                    Seguridad & Auditoría de Sesiones
                </div>
                <h2 class="page-title text-dark fw-bold d-flex align-items-center">
                    <i class="ti ti-devices text-teal me-2 fs-1"></i> Control de Conexiones al Sistema
                </h2>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list flex-wrap">
                    @canany(['descargar-excel-conexiones'])
                    <a href="{{ route('conexiones.export-excel', request()->all()) }}" class="btn btn-outline-success d-flex align-items-center shadow-xs">
                        <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Exportar a Excel
                    </a>
                    @endcanany

                    @canany(['eliminar-conexiones'])
                    <button type="button" class="btn btn-outline-danger d-flex align-items-center shadow-xs" data-bs-toggle="modal" data-bs-target="#modal-limpiar-conexiones">
                        <i class="ti ti-trash me-1 fs-3"></i> Depurar Antiguas
                    </button>
                    @endcanany
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-fluid container-xl">

        <!-- 1. Tarjetas de Métricas -->
        <div class="row row-cards mb-3">
            <div class="col-6 col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary-lt text-primary avatar">
                                    <i class="ti ti-server-2 fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium fs-2 fw-bold text-dark">
                                    {{ number_format($totalConexiones) }}
                                </div>
                                <div class="text-secondary small text-uppercase fw-semibold">
                                    Total Conexiones
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success-lt text-success avatar">
                                    <i class="ti ti-broadcast fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium fs-2 fw-bold text-success d-flex align-items-center gap-2">
                                    {{ number_format($conexionesActivas) }}
                                    @if($conexionesActivas > 0)
                                        <span class="status-dot-animated"></span>
                                    @endif
                                </div>
                                <div class="text-secondary small text-uppercase fw-semibold">
                                    Sesiones Activas Ahora
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-teal-lt text-teal avatar">
                                    <i class="ti ti-user-check fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium fs-2 fw-bold text-teal">
                                    {{ number_format($usuariosConectadosHoy) }}
                                </div>
                                <div class="text-secondary small text-uppercase fw-semibold">
                                    Usuarios Hoy
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-azure-lt text-azure avatar">
                                    <i class="ti ti-clock-hour-4 fs-2"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium fs-2 fw-bold text-azure font-monospace">
                                    {{ $promedioMinutos }} <small class="fs-4">min</small>
                                </div>
                                <div class="text-secondary small text-uppercase fw-semibold">
                                    Duración Promedio
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Filtros de Búsqueda -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2">
                <h4 class="card-title fw-bold text-dark mb-0 fs-3">
                    <i class="ti ti-filter text-primary me-1"></i> Filtros de Consulta
                </h4>
            </div>
            <div class="card-body p-3">
                <form method="GET" action="{{ route('conexiones.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold">Buscar por IP / Dispositivo / Ciudad</label>
                            <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Ej: 192.168, Chrome, Caracas..." value="{{ request('buscar') }}">
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold">Usuario</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="">-- Todos los Usuarios --</option>
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nombre_corto }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold">Estado de Sesión</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">-- Todos los Estados --</option>
                                <option value="Activa" {{ request('status') === 'Activa' ? 'selected' : '' }}>Activa (En Línea)</option>
                                <option value="Cerrada" {{ request('status') === 'Cerrada' ? 'selected' : '' }}>Cerrada por Usuario</option>
                                <option value="Expirada" {{ request('status') === 'Expirada' ? 'selected' : '' }}>Expirada por Inactividad</option>
                                <option value="Forzada" {{ request('status') === 'Forzada' ? 'selected' : '' }}>Cerrada / Otra Sesión</option>
                            </select>
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                        </div>

                        <div class="col-6 col-md-2">
                            <label class="form-label small fw-semibold">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                        </div>

                        <div class="col-12 col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100" title="Aplicar filtros">
                                <i class="ti ti-search"></i>
                            </button>
                            <a href="{{ route('conexiones.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros">
                                <i class="ti ti-rotate-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Tabla Principal de Conexiones -->
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table id="tabla-conexiones" data-module="conexiones" class="table card-table table-vcenter table-hover table-striped text-nowrap w-100">
                    <thead>
                        <tr>
                            <th class="w-1 text-center">ID</th>
                            <th>USUARIO</th>
                            <th>IP Y DISPOSITIVO</th>
                            <th>LUGAR / PAÍS</th>
                            <th>FECHA Y HORA DE INICIO</th>
                            <th>DURACIÓN DE SESIÓN</th>
                            <th class="text-center">ESTADO</th>
                            <th class="text-center w-1 no-sort">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conexiones as $con)
                        <tr id="conexion-row-{{ $con->id }}">
                            <td class="text-center font-monospace">
                                <span class="badge bg-light text-muted font-monospace">#{{ $con->id }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold">
                                        {{ $con->user ? $con->user->iniciales : '??' }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" title="{{ $con->user?->name ?? '' }}">{{ $con->user?->nombre_corto ?? 'Usuario Eliminado' }}</div>
                                        <div class="small text-muted d-flex align-items-center gap-1">
                                            <span>{{ $con->user?->email ?? '-' }}</span>
                                            @if($con->user?->roles->isNotEmpty())
                                                · <span class="badge bg-blue-lt px-1 py-0" style="font-size: 0.68rem;">{{ $con->user->roles->first()->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="font-monospace fw-bold text-dark d-flex align-items-center gap-1">
                                        <i class="ti ti-network text-secondary"></i> {{ $con->ip_address }}
                                    </span>
                                    <span class="text-muted small" title="{{ $con->user_agent }}">
                                        <i class="ti ti-device-laptop me-0.5 text-muted"></i> {{ $con->dispositivo ?? 'Dispositivo Web' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1.5">
                                    <img src="{{ $con->bandera_url }}" alt="{{ $con->codigo_pais }}" title="{{ $con->pais }}" width="28" height="20" loading="lazy" class="rounded-1 border shadow-xs" style="object-fit: cover;" onerror="this.replaceWith(document.createTextNode('{{ $con->codigo_pais }}'))">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $con->pais }}</div>
                                        <div class="small text-muted" style="font-size: 0.72rem;">{{ $con->ciudad }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.82rem;">
                                        {{ $con->login_at ? $con->login_at->timezone('America/Caracas')->format('d/m/Y h:i:s A') : '-' }}
                                    </span>
                                    <span class="text-muted small">
                                        hace {{ $con->login_at ? $con->login_at->diffForHumans() : '-' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($con->status === 'Activa')
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-success text-white fw-bold px-2 py-1 shadow-xs">
                                            <i class="ti ti-clock-play me-1"></i> {{ $con->duracion_formateada }}
                                        </span>
                                    </div>
                                    <div class="text-success small fw-semibold mt-0.5" style="font-size: 0.7rem;">
                                        <span class="status-dot-animated me-1"></span> En línea ahora
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-light text-dark font-monospace fw-bold px-2 py-1 border">
                                            <i class="ti ti-hourglass-empty me-1 text-muted"></i> {{ $con->duracion_formateada }}
                                        </span>
                                    </div>
                                    <div class="text-muted small mt-0.5" style="font-size: 0.7rem;">
                                        Fin: {{ $con->logout_at ? $con->logout_at->timezone('America/Caracas')->format('h:i A') : '-' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($con->status === 'Activa')
                                    <span class="badge bg-success-lt text-success fw-bold px-2 py-1">
                                        <i class="ti ti-circle-check me-0.5"></i> Activa
                                    </span>
                                @elseif($con->status === 'Cerrada')
                                    <span class="badge bg-secondary-lt fw-semibold px-2 py-1">
                                        <i class="ti ti-logout me-0.5"></i> Cerrada
                                    </span>
                                @elseif($con->status === 'Expirada')
                                    <span class="badge bg-warning-lt text-warning fw-semibold px-2 py-1" title="Cerrada por inactividad">
                                        <i class="ti ti-alarm-off me-0.5"></i> Expirada
                                    </span>
                                @elseif($con->status === 'Forzada')
                                    <span class="badge bg-danger-lt text-danger fw-semibold px-2 py-1" title="Cerrada por inicio de sesión en otro equipo">
                                        <i class="ti ti-device-laptop-off me-0.5"></i> Forzada
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Ver Detalle Técnico -->
                                    <button type="button" 
                                            class="btn btn-outline-info btn-icon btn-ver-conexion" 
                                            data-url="{{ route('conexiones.show', $con->id) }}"
                                            title="Ver Detalle de la Conexión">
                                        <i class="ti ti-eye fs-3"></i>
                                    </button>

                                    <!-- Cerrar Sesión Remota (Si está activa) -->
                                    @canany(['gestionar-conexiones'])
                                    @if($con->status === 'Activa' && $con->user_id !== auth()->id())
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-icon btn-cerrar-remota" 
                                            data-url="{{ route('conexiones.cerrar', $con->id) }}"
                                            data-name="{{ $con->user?->nombre_corto ?? 'Usuario' }}"
                                            title="Desconectar y Cerrar Sesión Remota">
                                        <i class="ti ti-plug-connected-x fs-3"></i>
                                    </button>
                                    @endif
                                    @endcanany

                                    @canany(['eliminar-conexiones'])
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-icon btn-eliminar-conexion" 
                                            data-url="{{ route('conexiones.destroy', $con->id) }}"
                                            title="Eliminar registro del historial">
                                        <i class="ti ti-trash fs-3"></i>
                                    </button>
                                    @endcanany
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- Modal: Ver Ficha Detallada de Conexión --}}
<div class="modal modal-blur fade" id="modal-detalle-conexion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-teal text-white py-2.5 px-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-shield-lock fs-2"></i>
                    <h5 class="modal-title fw-bold text-white mb-0">Detalle de Sesión y Conexión</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Tarjeta de Usuario -->
                <div class="border rounded-2 p-2.5 mb-2.5 bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                        <div class="avatar avatar-md rounded-circle bg-teal text-white fw-bold" id="modal-user-avatar">
                            US
                        </div>
                        <div>
                            <div class="fw-bold fs-3 text-dark" id="modal-user-name">---</div>
                            <div class="text-muted small" id="modal-user-email">---</div>
                        </div>
                        <span class="badge bg-indigo-lt fw-bold ms-auto" id="modal-user-role">---</span>
                    </div>

                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-secondary fw-semibold">Estado:</span>
                            <div id="modal-session-status">---</div>
                        </div>
                        <div class="col-6">
                            <span class="text-secondary fw-semibold">Duración Total:</span>
                            <div class="fw-bold font-monospace text-primary" id="modal-session-duracion">---</div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Ubicación e IP -->
                <div class="border rounded-2 p-2.5 mb-2.5 bg-white">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center">
                        <i class="ti ti-map-pin text-danger me-1"></i> Red y Ubicación Geográfica
                    </h6>
                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-secondary">Dirección IP:</span>
                            <div class="font-monospace fw-bold text-dark" id="modal-ip-address">---</div>
                        </div>
                        <div class="col-6">
                            <span class="text-secondary">País / Lugar:</span>
                            <div class="fw-bold text-dark" id="modal-pais-ciudad">---</div>
                        </div>
                        <div class="col-12 mt-1">
                            <span class="text-secondary">Dispositivo Detectado:</span>
                            <div class="fw-bold text-dark" id="modal-dispositivo">---</div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta de Tiempos -->
                <div class="border rounded-2 p-2.5 mb-2.5 bg-white">
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center">
                        <i class="ti ti-calendar-time text-primary me-1"></i> Cronología de la Sesión
                    </h6>
                    <div class="row g-2 small">
                        <div class="col-12 d-flex justify-content-between mb-1">
                            <span class="text-secondary">Inicio de Sesión:</span>
                            <span class="font-monospace fw-semibold text-dark" id="modal-login-time">---</span>
                        </div>
                        <div class="col-12 d-flex justify-content-between mb-1">
                            <span class="text-secondary">Última Actividad:</span>
                            <span class="font-monospace fw-semibold text-dark" id="modal-actividad-time">---</span>
                        </div>
                        <div class="col-12 d-flex justify-content-between mb-1">
                            <span class="text-secondary">Cierre de Sesión:</span>
                            <span class="font-monospace fw-semibold text-dark" id="modal-logout-time">---</span>
                        </div>
                        <div class="col-12 border-top pt-1 text-muted" id="modal-origen-cierre-box">
                            <strong>Motivo / Origen:</strong> <span id="modal-origen-cierre">---</span>
                        </div>
                    </div>
                </div>

                <!-- User Agent Completo -->
                <div>
                    <label class="form-label small fw-bold text-secondary mb-1">User Agent Completo de la Máquina</label>
                    <div class="user-agent-box" id="modal-user-agent">---</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-danger btn-sm ms-auto" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Depurar Registros Antiguos --}}
@canany(['eliminar-conexiones'])
<div class="modal modal-blur fade" id="modal-limpiar-conexiones" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form id="form-limpiar-conexiones" class="modal-content" action="{{ route('conexiones.limpiar') }}" method="POST">
            @csrf
            <div class="modal-header bg-danger text-white py-2.5 px-3 border-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-alert-triangle fs-2"></i>
                    <h5 class="modal-title fw-bold text-white mb-0">Depurar Historial de Conexiones</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-secondary mb-3">
                    Esta acción eliminará del historial las conexiones inactivas, cerradas o expiradas antiguas para optimizar el tamaño de la base de datos. <b>Las sesiones activas nunca serán eliminadas.</b>
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Seleccione la antigüedad de los registros a eliminar:</label>
                    <select name="dias" class="form-select">
                        <option value="30">Conexiones cerradas de más de 30 días</option>
                        <option value="60" selected>Conexiones cerradas de más de 60 días</option>
                        <option value="90">Conexiones cerradas de más de 90 días</option>
                        <option value="180">Conexiones cerradas de más de 180 días</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-success btn-sm fw-bold">
                    <i class="ti ti-trash me-1"></i> Proceder a Depurar
                </button>
            </div>
        </form>
    </div>
</div>
@endcanany

@endsection

@push('js')
<!-- Vendor Local DataTables JS -->
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/responsive.bootstrap5.min.js') }}"></script>
<script src="{{ asset('js/datatable-helper.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Inicializar DataTable para Conexiones
    if (typeof initStandardDataTable === 'function') {
        initStandardDataTable('#tabla-conexiones', {
            order: [[0, 'desc']]
        });
    } else if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#tabla-conexiones').DataTable({
            language: window.AppDataTableSpanish || {},
            order: [[0, 'desc']],
            responsive: true
        });
    }

    // 2. Ver Detalle de Conexión en Modal
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-ver-conexion');
        if (btn) {
            e.preventDefault();
            const url = btn.dataset.url;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.conexion) {
                    const c = data.conexion;
                    
                    document.getElementById('modal-user-name').textContent = data.usuario_nombre;
                    document.getElementById('modal-user-email').textContent = data.usuario_email;
                    document.getElementById('modal-user-role').textContent = data.usuario_rol;
                    document.getElementById('modal-user-avatar').textContent = data.usuario_iniciales || 'US';

                    document.getElementById('modal-session-status').innerHTML = c.status === 'Activa'
                        ? '<span class="badge bg-success text-white fw-bold"><span class="status-dot-animated me-1"></span> Activa (En Línea)</span>'
                        : `<span class="badge bg-secondary-lt fw-bold">${c.status}</span>`;

                    document.getElementById('modal-session-duracion').textContent = data.duracion_formateada;
                    document.getElementById('modal-ip-address').textContent = c.ip_address;
                    document.getElementById('modal-pais-ciudad').innerHTML = `<img src="${data.bandera_url}" width="22" height="16" class="rounded-1 border me-1 align-text-bottom" style="object-fit: cover;" alt=""> `;
                    document.getElementById('modal-pais-ciudad').append(`${c.pais} · ${c.ciudad}`);
                    document.getElementById('modal-dispositivo').textContent = c.dispositivo || 'No identificado';

                    document.getElementById('modal-login-time').textContent = data.login_formateado;
                    document.getElementById('modal-actividad-time').textContent = data.actividad_formateada;
                    document.getElementById('modal-logout-time').textContent = data.logout_formateado;

                    document.getElementById('modal-origen-cierre').textContent = c.origen_cierre || (c.status === 'Activa' ? 'Sesión actualmente activa' : 'Desconexión estándar');
                    document.getElementById('modal-user-agent').textContent = c.user_agent || 'No registrado';

                    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-conexion'));
                    modal.show();
                }
            })
            .catch(err => {
                console.error('Error al cargar detalle de la conexión:', err);
            });
        }
    });

    // 3. Cerrar Sesión Remota Forzada
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-cerrar-remota');
        if (btn) {
            e.preventDefault();
            const url = btn.dataset.url;
            const name = btn.dataset.name;

            Swal.fire({
                title: '¿Desconectar esta sesión?',
                html: `¿Está seguro de que desea cerrar remotamente la sesión activa del usuario <b>"${name}"</b>?<br><span class="text-muted small">El usuario será desconectado inmediatamente del sistema.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                confirmButtonText: '<i class="ti ti-plug-connected-x me-1"></i> Sí, Desconectar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Sesión Cerrada!',
                                text: data.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'No se pudo cerrar la sesión remota.',
                                confirmButtonColor: '#2fb344'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Red',
                            text: 'No se pudo completar la solicitud.',
                            confirmButtonColor: '#2fb344'
                        });
                    });
                }
            });
        }
    });

    // 4. Eliminar Registro de Conexión
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-eliminar-conexion');
        if (btn) {
            e.preventDefault();
            const url = btn.dataset.url;

            Swal.fire({
                title: '¿Eliminar registro?',
                text: 'Esta acción borrará este registro histórico de conexión.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                confirmButtonText: 'Sí, Eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
                }
            });
        }
    });

    // 5. Envío de formulario de depuración
    const formLimpiar = document.getElementById('form-limpiar-conexiones');
    if (formLimpiar) {
        formLimpiar.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('modal-limpiar-conexiones'))?.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Depuración Completada',
                        text: data.message,
                        timer: 1600,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        });
    }
});
</script>
@endpush

