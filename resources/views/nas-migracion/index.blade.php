@extends('layouts.app')

@section('title', 'Migración NAS')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    ALMACENAMIENTO &amp; INFRAESTRUCTURA
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-server-2 me-2 text-primary" style="font-size: 1.6rem;"></i>Migración y Sincronización NAS
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                @can('ver-estadisticas-nas')
                <a href="{{ route('nas-migracion.estadisticas.index') }}" class="btn btn-outline-warning btn-sm fw-bold">
                    <i class="ti ti-chart-bar me-1"></i> Estadísticas
                </a>
                @endcan
                @can('crear-tarea-nas')
                <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-nueva-tarea">
                    <i class="ti ti-plus me-1 fs-3"></i> Nueva Tarea de Migración
                </button>
                @endcan
                <span class="badge bg-green-lt text-green px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.85rem; border: 1px solid rgba(47, 179, 68, 0.25);">
                    <i class="ti ti-bolt me-1 fs-3"></i> Motor Asíncrono Anti-Timeout Activo
                </span>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Flash Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible shadow-sm mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-circle-check fs-2 me-2"></i>
                <div class="fw-semibold">{{ session('success') }}</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible shadow-sm mb-3" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle fs-2 me-2"></i>
                <div class="fw-semibold">Por favor corrige los errores del formulario.</div>
            </div>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        @endif

        {{-- Stat Cards --}}
        <div class="row row-cards g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle bg-primary text-white me-3 shadow-sm" style="width: 48px; height: 48px;">
                                <i class="ti ti-folders fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-dark">{{ $totalTasks }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">TOTAL TAREAS</div>
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
                                <i class="ti ti-database-check fs-2"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-success">{{ $totalExitosas }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">SINCRONIZACIONES OK</div>
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
                                <i class="ti ti-refresh fs-2 {{ $totalEnEjecucion > 0 ? 'ti-spin' : '' }}"></i>
                            </span>
                            <div>
                                <div class="h1 mb-0 fw-bold text-warning" id="stat-running-count">{{ $totalEnEjecucion }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">EN SEGUNDO PLANO</div>
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
                                <div class="h1 mb-0 fw-bold text-danger">{{ $totalFallidas }}</div>
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.04em;">ERRORES / FALLOS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Banner informativo de protección contra cuelgues --}}
        <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex">
                <div class="me-3">
                    <i class="ti ti-shield-lock fs-1 text-info"></i>
                </div>
                <div>
                    <h4 class="alert-title mb-1 fw-bold">Protección Integral Anti-Cuelgues y Asincronía Activa</h4>
                    <div class="text-secondary small">
                        Las transferencias se ejecutan en <strong>segundo plano desacoplado</strong>. Puedes migrar millones de archivos o Terabytes sin que el navegador web sufra <em>timeout</em>. El motor utiliza <code>--timeout=300</code> para evitar bloqueos por micro-cortes y <code>--partial</code> para garantizar la reanudación automática.
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Tareas de Migración --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h3 class="card-title text-dark fw-bold mb-0 d-flex align-items-center">
                    <i class="ti ti-arrows-transfer-down me-2 text-primary fs-2"></i> Tareas de Sincronización Configuradas
                </h3>
                <span class="badge bg-blue-lt fw-bold">{{ $tasksActivas }} Activa(s)</span>
            </div>

            <div class="card-body p-0">
                @forelse($tasks as $task)
                <div class="p-3 border-bottom {{ $loop->last ? 'border-bottom-0' : '' }}" 
                     id="task-row-{{ $task->id }}" 
                     data-task-id="{{ $task->id }}" 
                     data-status-url="{{ route('nas-migracion.tasks.status', $task) }}"
                     data-current-status="{{ $task->status }}">
                    <div class="row align-items-center g-3">
                        <div class="col-xl-4 col-lg-5 col-md-12 mb-2 mb-xl-0">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md bg-azure-lt text-azure rounded me-3 flex-shrink-0">
                                    <i class="ti ti-device-floppy fs-2"></i>
                                </span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h4 class="mb-0 fw-bold text-dark text-truncate" title="{{ $task->name }}">{{ $task->name }}</h4>
                                    <small class="text-secondary d-block text-truncate" title="{{ $task->description }}">{{ $task->description ?: 'Sin descripción' }}</small>
                                    <div class="mt-1 d-flex flex-wrap gap-1 align-items-center" id="task-status-container-{{ $task->id }}">
                                        @if($task->status === 'running')
                                            <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center badge-status-pill">
                                                <span class="spinner-border spinner-border-sm me-1" role="status"></span> En Proceso
                                            </span>
                                        @elseif($task->last_run_status === 'completed')
                                            <span class="badge bg-green-lt text-green fw-bold badge-status-pill">
                                                <i class="ti ti-check me-1"></i> Sincronización OK
                                            </span>
                                        @elseif($task->last_run_status === 'failed')
                                            <span class="badge bg-danger-lt text-danger fw-bold badge-status-pill">
                                                <i class="ti ti-x me-1"></i> Falló última ejecución
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-lt fw-bold badge-status-pill">En espera</span>
                                        @endif
                                        <span class="badge bg-purple-lt">
                                            <i class="ti ti-bolt me-1"></i> Asíncrono
                                        </span>
                                        <span class="badge bg-primary-lt fw-bold" id="task-phase-badge-{{ $task->id }}">
                                            <i class="ti ti-refresh me-1"></i> <span class="phase-name">Iniciando...</span>
                                        </span>
                                        <span class="badge bg-blue-lt" title="Lote de órbitas configurado">
                                            <i class="ti ti-stack-2 me-1"></i> Lote: {{ $task->batch_size ?? 5 }} órbitas
                                        </span>
                                    </div>

                                    {{-- Barra de Progreso en Tiempo Real --}}
                                    <div id="task-progress-box-{{ $task->id }}" class="mt-2 {{ $task->status === 'running' ? '' : 'd-none' }}">
                                        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.74rem;">
                                            <span class="text-secondary fw-semibold text-truncate me-2">
                                                <span class="spinner-border spinner-border-sm me-1 text-success" style="width: 11px; height: 11px;" role="status"></span>
                                                <span id="task-progress-label-{{ $task->id }}">Iniciando proceso de transferencia...</span>
                                            </span>
                                            <span class="fw-bold text-dark font-monospace flex-shrink-0" id="task-progress-counter-{{ $task->id }}">
                                                @if($task->latestLog && $task->latestLog->total_files_in_batch > 0)
                                                    {{ $task->latestLog->files_transferred }} / {{ $task->latestLog->total_files_in_batch }} arch. ({{ round(($task->latestLog->files_transferred / $task->latestLog->total_files_in_batch) * 100) }}%)
                                                @else
                                                    {{ $task->latestLog ? ($task->latestLog->files_transferred ?? 0) : 0 }} archivos transferidos
                                                @endif
                                            </span>
                                        </div>
                                        <div class="progress progress-sm" style="height: 6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                                 id="task-progress-bar-{{ $task->id }}" 
                                                 role="progressbar" 
                                                 style="width: {{ $task->latestLog && $task->latestLog->total_files_in_batch > 0 ? min(100, round(($task->latestLog->files_transferred / $task->latestLog->total_files_in_batch) * 100)) : ($task->status === 'running' ? '10%' : '100%') }}%;" 
                                                 aria-valuenow="0" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-1">
                                            <small class="text-muted" style="font-size: 0.70rem;" id="task-progress-sub-{{ $task->id }}">
                                                Copiar desde la órbita: <code class="text-primary fw-bold">{{ $task->last_migrated_orbit ?: 'Primera detectada' }}</code>
                                            </small>
                                            <div class="text-secondary small font-monospace d-flex align-items-center" id="task-progress-timers-{{ $task->id }}" style="font-size: 0.69rem;">
                                                <span class="badge bg-blue-lt me-1" id="task-timer-elapsed-{{ $task->id }}" title="Tiempo transcurrido">
                                                    <i class="ti ti-clock me-1"></i> <span class="val-elapsed">00:00</span>
                                                </span>
                                                <span class="badge bg-purple-lt" id="task-timer-eta-{{ $task->id }}" title="Tiempo restante estimado">
                                                    <i class="ti ti-hourglass-low me-1"></i> <span class="val-eta">Estimando...</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detalle de Rutas y Conexión --}}
                        <div class="col-xl-6 col-lg-5 col-md-12 mb-2 mb-xl-0">
                            <div class="bg-light p-2 rounded border" style="font-size: 0.80rem; line-height: 1.5;">
                                <div class="row g-1 align-items-baseline mb-1">
                                    <div class="col-auto text-secondary fw-bold" style="min-width: 110px;">
                                        ORIGEN (SYN):
                                    </div>
                                    <div class="col d-flex flex-wrap align-items-center gap-1">
                                        <span class="badge bg-blue-lt">{{ $task->nas_host }}</span>
                                        <code class="text-dark bg-white px-2 py-0 rounded border small text-break" title="{{ $task->source_path }}">{{ $task->source_path }}</code>
                                    </div>
                                </div>
                                <div class="row g-1 align-items-baseline mb-1">
                                    <div class="col-auto text-secondary fw-bold" style="min-width: 110px;">
                                        COMPARTIDA:
                                    </div>
                                    <div class="col d-flex flex-wrap align-items-center gap-1">
                                        <span class="badge bg-purple-lt">MIGRACION_SYNOLOGY</span>
                                        <code class="text-dark bg-white px-2 py-0 rounded border small text-break" title="{{ $task->target_path }}">{{ $task->target_path }}</code>
                                    </div>
                                </div>
                                <div class="row g-1 align-items-baseline mb-1">
                                    <div class="col-auto text-secondary fw-bold" style="min-width: 110px;">
                                        INSPUR FINAL:
                                    </div>
                                    <div class="col d-flex flex-wrap align-items-center gap-1">
                                        <span class="badge bg-teal-lt">RAWDATA</span>
                                        <span class="badge bg-azure-lt font-monospace fw-bold">{{ $task->inspur_target_dir ?: 'VRSS-1' }}</span>
                                    </div>
                                </div>
                                <div class="row g-1 align-items-baseline mb-1">
                                    <div class="col-auto text-secondary fw-bold" style="min-width: 110px;">
                                        ÚLTIMA ÓRBITA:
                                    </div>
                                    <div class="col d-flex flex-wrap align-items-center gap-1">
                                        @if($task->last_migrated_orbit)
                                            <span class="badge bg-green-lt"><i class="ti ti-check me-1"></i>{{ $task->last_migrated_orbit }}</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">(Continuará desde la siguiente)</span>
                                        @else
                                            <span class="text-muted fst-italic" style="font-size: 0.75rem;">Ninguna registrada (Iniciará desde la primera)</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="border-top pt-1 mt-1 text-secondary" style="font-size: 0.72rem;">
                                    <i class="ti ti-arrows-right me-1 text-primary"></i> <span class="fw-semibold">Flujo:</span> Synology &rarr; <code>MIGRACION_SYNOLOGY</code> &rarr; <code>/HS_IPS_PRIVATE/RDP_FTP/RAWDATA/{{ $task->inspur_target_dir ?: 'VRSS-1' }}/</code>
                                </div>
                            </div>
                        </div>

                        {{-- Botones de Acción (Solo Iconos con Tooltip) --}}
                        <div class="col-xl-2 col-lg-2 col-md-12 text-xl-end text-lg-end text-start d-flex align-items-center justify-content-xl-end justify-content-lg-end justify-content-start gap-2 flex-wrap">
                            {{-- Botón Probar Conexión --}}
                            @can('probar-conexion-nas')
                            <button type="button" 
                                    class="btn btn-icon btn-outline-info btn-test-conn shadow-sm" 
                                    data-task-id="{{ $task->id }}" 
                                    data-task-name="{{ $task->name }}"
                                    data-url="{{ route('nas-migracion.tasks.test', $task) }}"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="Probar Conexión (Ping y SSH hacia {{ $task->nas_host }})">
                                <i class="ti ti-wifi fs-2"></i>
                            </button>
                            @endcan

                            {{-- Botón Sincronizar Ahora (Asíncrono en lote de órbitas) --}}
                            @can('ejecutar-migracion-nas')
                            <button type="button" 
                                    class="btn btn-icon btn-success shadow-sm btn-start-sync {{ $task->status === 'running' ? 'd-none' : '' }}" 
                                    id="btn-sync-{{ $task->id }}"
                                    data-task-id="{{ $task->id }}" 
                                    data-task-name="{{ $task->name }}"
                                    data-source-path="{{ $task->source_path }}"
                                    data-target-path="{{ $task->target_path }}"
                                    data-inspur-dir="{{ $task->inspur_target_dir ?: 'VRSS-1' }}"
                                    data-last-orbit="{{ $task->last_migrated_orbit }}"
                                    data-batch-size="{{ $task->batch_size ?? 5 }}"
                                    data-url="{{ route('nas-migracion.tasks.sync', $task) }}"
                                    data-status-url="{{ route('nas-migracion.tasks.status', $task) }}"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="Sincronizar Ahora (Lote de {{ $task->batch_size ?? 5 }} órbitas completas)">
                                <i class="ti ti-player-play fs-2"></i>
                            </button>

                            {{-- Botón Paralizar / Detener Ejecución (Visible cuando la tarea está en proceso) --}}
                            <button type="button" 
                                    class="btn btn-icon btn-danger shadow-sm btn-stop-sync {{ $task->status === 'running' ? '' : 'd-none' }}" 
                                    id="btn-stop-{{ $task->id }}"
                                    data-task-id="{{ $task->id }}" 
                                    data-task-name="{{ $task->name }}"
                                    data-stop-url="{{ route('nas-migracion.tasks.stop', $task) }}"
                                    data-bs-toggle="tooltip" 
                                    data-bs-placement="top" 
                                    title="Paralizar / Detener Ejecución de Tarea">
                                <i class="ti ti-player-stop fs-2"></i>
                            </button>
                            @endcan

                            {{-- Botón Editar --}}
                            @can('editar-tarea-nas')
                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="Configurar / Editar Tarea">
                                <button type="button" 
                                        class="btn btn-icon btn-outline-secondary btn-edit-task shadow-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modal-editar-tarea-{{ $task->id }}">
                                    <i class="ti ti-edit fs-2"></i>
                                </button>
                            </span>
                            @endcan

                            {{-- Botón Eliminar --}}
                            @can('eliminar-tarea-nas')
                            <form action="{{ route('nas-migracion.tasks.destroy', $task) }}" method="POST" class="d-inline form-delete-task">
                                @csrf
                                @method('DELETE')
                                <button type="button" 
                                        class="btn btn-icon btn-outline-danger btn-delete-task shadow-sm" 
                                        data-task-name="{{ $task->name }}"
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="Eliminar Tarea">
                                    <i class="ti ti-trash fs-2"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- Modal Editar Tarea --}}
                @can('editar-tarea-nas')
                <div class="modal fade" id="modal-editar-tarea-{{ $task->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('nas-migracion.tasks.update', $task) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title fw-bold text-white">
                                        <i class="ti ti-edit me-1"></i> Editar Tarea: {{ $task->name }}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        {{-- Sección 1: Datos Generales --}}
                                        <div class="col-12 border-bottom pb-1 mb-1">
                                            <h6 class="fw-bold text-primary text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                                <i class="ti ti-settings me-1 text-primary"></i> 1. Información General de la Tarea
                                            </h6>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label required fw-bold text-dark">Nombre de la Tarea</label>
                                            <input type="text" name="name" class="form-control" value="{{ old('name', $task->name) }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark">Frecuencia Programada</label>
                                            <select name="schedule_frequency" class="form-select">
                                                <option value="manual" {{ $task->schedule_frequency === 'manual' ? 'selected' : '' }}>Solo Manual</option>
                                                <option value="hourly" {{ $task->schedule_frequency === 'hourly' ? 'selected' : '' }}>Cada Hora (Automático)</option>
                                                <option value="daily" {{ $task->schedule_frequency === 'daily' ? 'selected' : '' }}>Diario</option>
                                                <option value="weekly" {{ $task->schedule_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label text-muted">Descripción</label>
                                            <input type="text" name="description" class="form-control" value="{{ old('description', $task->description) }}">
                                        </div>

                                        {{-- Sección 2: Conexión SSH Synology --}}
                                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                                            <h6 class="fw-bold text-azure text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                                <i class="ti ti-terminal-2 me-1 text-azure"></i> 2. Parámetros de Conexión Synology (SSH)
                                            </h6>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label required fw-bold text-dark">Host Synology (IP)</label>
                                            <input type="text" name="nas_host" class="form-control" value="{{ old('nas_host', $task->nas_host) }}" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label required fw-bold text-dark">Puerto SSH</label>
                                            <input type="number" name="nas_port" class="form-control" value="{{ old('nas_port', $task->nas_port) }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label required fw-bold text-dark">Usuario SSH</label>
                                            <input type="text" name="ssh_user" class="form-control" value="{{ old('ssh_user', $task->ssh_user) }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-truncate fw-bold text-dark" title="Contraseña SSH configurada en la Synology">Contraseña SSH <small class="text-secondary fw-normal">(Opcional)</small></label>
                                            <div class="input-group input-group-flat">
                                                <input type="password" 
                                                       name="ssh_password" 
                                                       id="ssh_password_edit_{{ $task->id }}" 
                                                       class="form-control" 
                                                       value="{{ old('ssh_password', $task->ssh_password) }}" 
                                                       placeholder="••••••••" 
                                                       title="Contraseña actual guardada. Dejar como está o modificar.">
                                                <span class="input-group-text">
                                                    <a href="javascript:void(0)" class="link-secondary btn-toggle-password" data-target="ssh_password_edit_{{ $task->id }}" title="Mostrar / ocultar contraseña" tabindex="-1">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Sección 3: Directorios y Rutas de Migración --}}
                                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                                            <h6 class="fw-bold text-teal text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                                <i class="ti ti-folder-share me-1 text-teal"></i> 3. Rutas de Origen y Destino en NAS Inspur
                                            </h6>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label required fw-bold text-dark">Carpeta Origen (Synology)</label>
                                            <select name="source_path" class="form-select" required>
                                                @php
                                                    $vol2Sources = array_filter($synologyAvailableSources, fn($s) => str_contains($s, 'dae-Volumen2_IPS'));
                                                    $vol4Sources = array_filter($synologyAvailableSources, fn($s) => str_contains($s, 'dae-Volumen4_Respaldo_IPS'));
                                                    $otherSources = array_filter($synologyAvailableSources, fn($s) => !str_contains($s, 'dae-Volumen2_IPS') && !str_contains($s, 'dae-Volumen4_Respaldo_IPS'));
                                                @endphp
                                                @if(count($vol2Sources) > 0)
                                                    <optgroup label="Volumen 2 (dae-Volumen2_IPS)">
                                                        @foreach($vol2Sources as $src)
                                                            @php
                                                                $relative = str_replace(['/volume1/dae-Volumen2_IPS/', '/volume2/dae-Volumen2_IPS/'], '', $src);
                                                                $display = empty(trim($relative, '/')) ? 'dae-Volumen2_IPS (Raíz)' : '📁 ' . trim($relative, '/');
                                                            @endphp
                                                            <option value="{{ $src }}" {{ old('source_path', $task->source_path) === $src ? 'selected' : '' }}>
                                                                {{ $display }} &nbsp; ({{ $src }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @if(count($vol4Sources) > 0)
                                                    <optgroup label="Volumen 4 (dae-Volumen4_Respaldo_IPS)">
                                                        @foreach($vol4Sources as $src)
                                                            @php
                                                                $relative = str_replace(['/volume1/dae-Volumen4_Respaldo_IPS/', '/volume4/dae-Volumen4_Respaldo_IPS/'], '', $src);
                                                                $display = empty(trim($relative, '/')) ? 'dae-Volumen4_Respaldo_IPS (Raíz)' : '📁 ' . trim($relative, '/');
                                                            @endphp
                                                            <option value="{{ $src }}" {{ old('source_path', $task->source_path) === $src ? 'selected' : '' }}>
                                                                {{ $display }} &nbsp; ({{ $src }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @if(count($otherSources) > 0)
                                                    <optgroup label="Otros Directorios">
                                                        @foreach($otherSources as $src)
                                                            <option value="{{ $src }}" {{ old('source_path', $task->source_path) === $src ? 'selected' : '' }}>
                                                                {{ basename(rtrim($src, '/')) }} &nbsp; ({{ $src }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                                @if(!in_array($task->source_path, $synologyAvailableSources) && !empty($task->source_path))
                                                    <option value="{{ $task->source_path }}" selected>{{ $task->source_path }}</option>
                                                @endif
                                            </select>
                                            <small class="text-secondary">Directorio a migrar desde la NAS Synology (dae-Volumen2_IPS o dae-Volumen4_Respaldo_IPS).</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required fw-bold text-dark">Carpeta Compartida Intermedia (Destino)</label>
                                            <select name="target_path" class="form-select" required>
                                                <option value="/volume3/dae-RowData/MIGRACION_SYNOLOGY/" selected>
                                                    dae-RowData &gt; MIGRACION_SYNOLOGY (/volume3/dae-RowData/MIGRACION_SYNOLOGY/)
                                                </option>
                                            </select>
                                            <small class="text-secondary">Carpeta compartida entre ambas NAS: <code>MIGRACION_SYNOLOGY</code></small>
                                        </div>

                                        <div class="col-md-12">
                                            <label class="form-label required fw-bold text-success text-nowrap d-flex align-items-center">
                                                <i class="ti ti-corner-down-right me-1"></i> Subdirectorio Destino Inspur (RAWDATA)
                                            </label>
                                            <select name="inspur_target_dir" class="form-select border-success" required>
                                                @foreach($inspurAvailableDirs as $dir)
                                                    <option value="{{ $dir }}" {{ (old('inspur_target_dir', $task->inspur_target_dir) == $dir) ? 'selected' : '' }}>
                                                        {{ $dir }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-success fw-semibold">Ubicación final en Inspur: <code>/HS_IPS_PRIVATE/RDP_FTP/RAWDATA/</code></small>
                                        </div>
                                        <div class="col-md-12 p-2 rounded bg-success-lt border border-success d-flex align-items-center">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" name="auto_move_to_inspur" value="1" {{ ($task->auto_move_to_inspur ?? true) ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold text-success">
                                                    <i class="ti ti-truck-delivery me-1"></i> Mover archivos a Inspur RAWDATA tras copiar a MIGRACION_SYNOLOGY (Traslado automático activo)
                                                </span>
                                            </label>
                                        </div>

                                        {{-- Sección 4: Configuración Avanzada Rsync --}}
                                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                                            <h6 class="fw-bold text-warning text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                                <i class="ti ti-adjustments-horizontal me-1 text-warning"></i> 4. Parámetros de Transferencia y Lotes
                                            </h6>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label fw-bold text-dark">Parámetros Rsync Anti-Cuelgues</label>
                                            <input type="text" name="rsync_options" class="form-control" value="{{ old('rsync_options', $task->rsync_options) }}">
                                            <small class="text-secondary">Recomendado: <code>-avh --stats --progress --timeout=300 --partial</code></small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label required fw-bold text-dark">Lote de Sincronización</label>
                                            <div class="input-group">
                                                <input type="number" name="batch_size" class="form-control" value="{{ old('batch_size', $task->batch_size ?? 5) }}" min="1" max="1000" required>
                                                <span class="input-group-text">órbitas</span>
                                            </div>
                                            <small class="text-secondary">Defecto: 5 órbitas completas (con CH1 y CH2).</small>
                                        </div>

                                        <div class="col-md-6 d-flex align-items-center pt-2">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" name="delete_after_sync" value="1" {{ $task->delete_after_sync ? 'checked' : '' }}>
                                                <span class="form-check-label text-danger">Purgar borrados (--delete)</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-center pt-2">
                                            <label class="form-check form-switch m-0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $task->is_active ? 'checked' : '' }}>
                                                <span class="form-check-label fw-bold text-primary">Tarea Activa para ejecución</span>
                                            </label>
                                        </div>

                                        <input type="hidden" name="ssh_auth_type" value="password">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success fw-bold">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endcan

                @empty
                <div class="text-center py-5">
                    <i class="ti ti-server-off text-secondary fs-1 mb-2"></i>
                    <p class="text-secondary fw-semibold">No hay tareas de migración configuradas.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Historial de Migraciones --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="row align-items-center g-2">
                    <div class="col">
                        <h3 class="card-title text-dark fw-bold mb-0 d-flex align-items-center">
                            <i class="ti ti-history me-2 text-primary fs-2"></i> Historial de Ejecuciones y Logs
                        </h3>
                    </div>
                    {{-- Filtros Rápidos --}}
                    <div class="col-auto">
                        <form method="GET" action="{{ route('nas-migracion.index') }}" class="d-flex align-items-center gap-2 m-0">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todos los Estados</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completadas</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Fallidas</option>
                                <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>En Ejecución</option>
                            </select>
                            <select name="trigger_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todos los Orígenes</option>
                                <option value="manual" {{ request('trigger_type') === 'manual' ? 'selected' : '' }}>Manual</option>
                                <option value="scheduled" {{ request('trigger_type') === 'scheduled' ? 'selected' : '' }}>Automático (Cron)</option>
                            </select>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>Tarea</th>
                            <th>Tipo</th>
                            <th>Duración</th>
                            <th>Archivos</th>
                            <th>Tamaño</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                <span class="fw-semibold text-dark">{{ $log->started_at->format('d/m/Y H:i:s') }}</span>
                                <div class="text-secondary small">{{ $log->started_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $log->task->name ?? 'Tarea eliminada' }}</div>
                                <small class="text-secondary">{{ $log->user->name ?? 'Sistema Automático' }}</small>
                            </td>
                            <td>
                                @if($log->trigger_type === 'manual')
                                    <span class="badge bg-blue-lt fw-bold"><i class="ti ti-hand-click me-1"></i> Manual</span>
                                @else
                                    <span class="badge bg-purple-lt fw-bold"><i class="ti ti-clock-play me-1"></i> Automático</span>
                                @endif
                            </td>
                            <td class="fw-semibold text-secondary">
                                {{ $log->duration_formatted }}
                            </td>
                            <td>
                                <span class="badge bg-secondary-lt fw-bold">{{ number_format($log->files_transferred) }} archivos</span>
                                @if($log->last_migrated_orbit_in_batch)
                                    <div class="small text-truncate mt-1" style="max-width: 140px;" title="Última órbita procesada: {{ $log->last_migrated_orbit_in_batch }}">
                                        <i class="ti ti-check text-success"></i> <code class="small text-success">{{ $log->last_migrated_orbit_in_batch }}</code>
                                    </div>
                                @endif
                                @if($log->orbits_skipped > 0)
                                    <div class="small text-muted" title="{{ $log->orbits_skipped }} órbitas ya existían en destino">
                                        <i class="ti ti-arrow-forward-up text-warning"></i> <small>{{ $log->orbits_skipped }} omitidas</small>
                                    </div>
                                @endif
                            </td>
                            <td class="fw-bold text-dark">
                                {{ $log->human_transferred_size ?: '0 B' }}
                            </td>
                            <td>
                                @if($log->status === 'completed')
                                    <span class="badge bg-green-lt text-green fw-bold">
                                        <i class="ti ti-check me-1"></i> Completado
                                    </span>
                                @elseif($log->status === 'running')
                                    <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> En curso
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span class="badge bg-danger-lt text-danger fw-bold" title="{{ $log->error_message }}">
                                        <i class="ti ti-alert-triangle me-1"></i> Falló
                                    </span>
                                @else
                                    <span class="badge bg-secondary-lt fw-bold">{{ ucfirst($log->status) }}</span>
                                @endif

                                {{-- Desglose visual de Fases: Synology Compartida e Inspur --}}
                                <div class="mt-1 d-flex flex-column gap-1" style="font-size: 0.70rem;">
                                    <div>
                                        <span class="text-secondary fw-semibold">Compartida (SYN):</span>
                                        @if($log->phase_synology_status === 'completed' || ($log->status === 'completed' && !$log->phase_synology_status))
                                            <span class="badge bg-green-lt py-0 px-1 text-success" title="Archivos transferidos con éxito a MIGRACION_SYNOLOGY"><i class="ti ti-check"></i> OK</span>
                                        @elseif($log->phase_synology_status === 'failed')
                                            <span class="badge bg-danger-lt py-0 px-1 text-danger" title="Falló la copia a MIGRACION_SYNOLOGY"><i class="ti ti-x"></i> Error</span>
                                        @elseif($log->status === 'running')
                                            <span class="badge bg-warning-lt py-0 px-1 text-warning"><i class="ti ti-loader"></i> Copiando</span>
                                        @else
                                            <span class="badge bg-secondary-lt py-0 px-1 text-muted">-</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="text-secondary fw-semibold">Inspur (RAWDATA):</span>
                                        @if($log->phase_inspur_status === 'completed' || ($log->files_moved > 0))
                                            <span class="badge bg-green-lt py-0 px-1 text-success" title="Archivos trasladados a Inspur RAWDATA"><i class="ti ti-check"></i> OK ({{ $log->files_moved }})</span>
                                        @elseif($log->phase_inspur_status === 'failed')
                                            <span class="badge bg-danger-lt py-0 px-1 text-danger" title="Falló el traslado interno en Inspur"><i class="ti ti-x"></i> Error</span>
                                        @elseif($log->phase_inspur_status === 'skipped')
                                            <span class="badge bg-azure-lt py-0 px-1 text-azure" title="Resguardado en recurso NFS"><i class="ti ti-cloud"></i> En NFS</span>
                                        @else
                                            <span class="badge bg-secondary-lt py-0 px-1 text-muted">-</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                @can('ver-consola-log-nas')
                                <button type="button" 
                                        class="btn btn-outline-dark btn-sm fw-bold btn-view-log" 
                                        data-url="{{ route('nas-migracion.logs.show', $log) }}"
                                        title="Ver salida de consola rsync">
                                    <i class="ti ti-terminal me-1"></i> Ver Consola
                                </button>
                                @else
                                <span class="text-muted fs-5">-</span>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">
                                No hay registros de sincronización disponibles.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('partials.datatable-pagination', ['paginator' => $logs, 'label' => 'registros'])
        </div>

    </div>
</div>

{{-- Modal Nueva Tarea --}}
@can('crear-tarea-nas')
<div class="modal fade" id="modal-nueva-tarea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('nas-migracion.tasks.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="ti ti-plus me-1"></i> Registrar Nueva Tarea de Migración
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        {{-- Sección 1: Datos Generales --}}
                        <div class="col-12 border-bottom pb-1 mb-1">
                            <h6 class="fw-bold text-primary text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                <i class="ti ti-settings me-1 text-primary"></i> 1. Información General de la Tarea
                            </h6>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label required fw-bold text-dark">Nombre de la Tarea</label>
                            <input type="text" name="name" class="form-control" placeholder="Ej. Copia Diaria Documentos Synology a Inspur" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-dark">Frecuencia Automática</label>
                            <select name="schedule_frequency" class="form-select">
                                <option value="manual">Solo Manual (Vía Botón)</option>
                                <option value="hourly" selected>Cada Hora (Automático)</option>
                                <option value="daily">Diario</option>
                                <option value="weekly">Semanal</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-muted">Descripción</label>
                            <input type="text" name="description" class="form-control" placeholder="Notas sobre los datos a transferir o VLAN utilizada">
                        </div>

                        {{-- Sección 2: Conexión SSH Synology --}}
                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                            <h6 class="fw-bold text-azure text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                <i class="ti ti-terminal-2 me-1 text-azure"></i> 2. Parámetros de Conexión Synology (SSH)
                            </h6>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label required fw-bold text-dark">Host Synology (IP)</label>
                            <input type="text" name="nas_host" class="form-control" value="192.168.3.124" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label required fw-bold text-dark">Puerto SSH</label>
                            <input type="number" name="nas_port" class="form-control" value="22" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required fw-bold text-dark">Usuario SSH</label>
                            <input type="text" name="ssh_user" class="form-control" value="admin" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-bold text-dark">Contraseña SSH</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="ssh_password" id="ssh_password_create" class="form-control" placeholder="Contraseña de la Synology" required>
                                <span class="input-group-text">
                                    <a href="javascript:void(0)" class="link-secondary btn-toggle-password" data-target="ssh_password_create" title="Mostrar / ocultar contraseña" tabindex="-1">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </span>
                            </div>
                        </div>

                        {{-- Sección 3: Directorios y Rutas de Migración --}}
                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                            <h6 class="fw-bold text-teal text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                <i class="ti ti-folder-share me-1 text-teal"></i> 3. Rutas de Origen y Destino en NAS Inspur
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required fw-bold text-dark">Carpeta Origen (Synology)</label>
                            <select name="source_path" class="form-select" required>
                                <option value="" disabled selected>-- Seleccione Carpeta Origen --</option>
                                @php
                                    $vol2Sources = array_filter($synologyAvailableSources, fn($s) => str_contains($s, 'dae-Volumen2_IPS'));
                                    $vol4Sources = array_filter($synologyAvailableSources, fn($s) => str_contains($s, 'dae-Volumen4_Respaldo_IPS'));
                                    $otherSources = array_filter($synologyAvailableSources, fn($s) => !str_contains($s, 'dae-Volumen2_IPS') && !str_contains($s, 'dae-Volumen4_Respaldo_IPS'));
                                @endphp
                                @if(count($vol2Sources) > 0)
                                    <optgroup label="Volumen 2 (dae-Volumen2_IPS)">
                                        @foreach($vol2Sources as $src)
                                            @php
                                                $relative = str_replace(['/volume1/dae-Volumen2_IPS/', '/volume2/dae-Volumen2_IPS/'], '', $src);
                                                $display = empty(trim($relative, '/')) ? 'dae-Volumen2_IPS (Raíz)' : '📁 ' . trim($relative, '/');
                                            @endphp
                                            <option value="{{ $src }}" {{ str_contains($src, 'RAWDATA PRINCIPAL') ? 'selected' : '' }}>
                                                {{ $display }} &nbsp; ({{ $src }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if(count($vol4Sources) > 0)
                                    <optgroup label="Volumen 4 (dae-Volumen4_Respaldo_IPS)">
                                        @foreach($vol4Sources as $src)
                                            @php
                                                $relative = str_replace(['/volume1/dae-Volumen4_Respaldo_IPS/', '/volume4/dae-Volumen4_Respaldo_IPS/'], '', $src);
                                                $display = empty(trim($relative, '/')) ? 'dae-Volumen4_Respaldo_IPS (Raíz)' : '📁 ' . trim($relative, '/');
                                            @endphp
                                            <option value="{{ $src }}">
                                                {{ $display }} &nbsp; ({{ $src }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if(count($otherSources) > 0)
                                    <optgroup label="Otros Directorios">
                                        @foreach($otherSources as $src)
                                            <option value="{{ $src }}">
                                                {{ basename(rtrim($src, '/')) }} &nbsp; ({{ $src }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <small class="text-secondary">Directorio a migrar desde la NAS Synology (dae-Volumen2_IPS o dae-Volumen4_Respaldo_IPS).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-bold text-dark">Carpeta Compartida Intermedia (Destino)</label>
                            <select name="target_path" class="form-select" required>
                                <option value="/volume3/dae-RowData/MIGRACION_SYNOLOGY/" selected>
                                    dae-RowData &gt; MIGRACION_SYNOLOGY (/volume3/dae-RowData/MIGRACION_SYNOLOGY/)
                                </option>
                            </select>
                            <small class="text-secondary">Carpeta compartida entre ambas NAS: <code>MIGRACION_SYNOLOGY</code></small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label required fw-bold text-success text-nowrap d-flex align-items-center">
                                <i class="ti ti-corner-down-right me-1"></i> Subdirectorio Destino Inspur (RAWDATA)
                            </label>
                            <select name="inspur_target_dir" class="form-select border-success" required>
                                @foreach($inspurAvailableDirs as $dir)
                                    <option value="{{ $dir }}" {{ $dir === 'VRSS-1' ? 'selected' : '' }}>
                                        {{ $dir }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-success fw-semibold">Ubicación final en Inspur: <code>/HS_IPS_PRIVATE/RDP_FTP/RAWDATA/</code></small>
                        </div>
                        <div class="col-md-12 p-2 rounded bg-success-lt border border-success d-flex align-items-center">
                            <label class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="auto_move_to_inspur" value="1" checked>
                                <span class="form-check-label fw-bold text-success">
                                    <i class="ti ti-truck-delivery me-1"></i> Mover archivos a Inspur RAWDATA tras copiar a MIGRACION_SYNOLOGY (Traslado automático activo)
                                </span>
                            </label>
                        </div>

                        {{-- Sección 4: Configuración Avanzada Rsync --}}
                        <div class="col-12 border-bottom pb-1 mt-3 mb-1">
                            <h6 class="fw-bold text-warning text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 0.78rem;">
                                <i class="ti ti-adjustments-horizontal me-1 text-warning"></i> 4. Parámetros de Transferencia y Lotes
                            </h6>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-bold text-dark">Parámetros Rsync Anti-Cuelgues</label>
                            <input type="text" name="rsync_options" class="form-control" value="-avh --stats --progress --timeout=300 --partial">
                            <small class="text-secondary">Incluye <code>--timeout=300</code> y <code>--partial</code> para evitar bloqueos.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required fw-bold text-dark">Lote de Sincronización</label>
                            <div class="input-group">
                                <input type="number" name="batch_size" class="form-control" value="5" min="1" max="1000" required>
                                <span class="input-group-text">órbitas</span>
                            </div>
                            <small class="text-secondary">Defecto: 5 órbitas completas (con CH1 y CH2).</small>
                        </div>

                        <div class="col-md-6 d-flex align-items-center pt-2">
                            <label class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="delete_after_sync" value="1">
                                <span class="form-check-label text-danger">Purgar borrados (--delete)</span>
                            </label>
                        </div>
                        <div class="col-md-6 d-flex align-items-center pt-2">
                            <label class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <span class="form-check-label fw-bold text-primary">Activar inmediatamente</span>
                            </label>
                        </div>

                        <input type="hidden" name="ssh_auth_type" value="password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold">Crear Tarea</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- Modal Visor de Consola / Terminal Log --}}
@can('ver-consola-log-nas')
<div class="modal fade" id="modal-visor-log" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-monospace d-flex align-items-center" id="visor-log-title">
                    <i class="ti ti-terminal me-2 text-green"></i> Consola Rsync: <span id="log-task-name" class="ms-1 fw-bold"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-dark">
                {{-- Barra superior de estadísticas rápidas --}}
                <div class="p-3 border-bottom border-secondary bg-black text-light d-flex flex-wrap gap-4 font-monospace" style="font-size: 0.85rem;">
                    <div><span class="text-secondary">Inicio:</span> <strong id="log-started-at" class="text-info">-</strong></div>
                    <div><span class="text-secondary">Duración:</span> <strong id="log-duration" class="text-warning">-</strong></div>
                    <div><span class="text-secondary">Archivos:</span> <strong id="log-files" class="text-primary">-</strong></div>
                    <div><span class="text-secondary">Tamaño:</span> <strong id="log-size" class="text-success">-</strong></div>
                    <div><span class="text-secondary">Estado:</span> <span id="log-status-badge"></span></div>
                </div>

                {{-- Ventana tipo Terminal --}}
                <div class="p-3">
                    <pre id="log-output-content" 
                         class="m-0 text-success font-monospace" 
                         style="background-color: #0c0d0e; padding: 1rem; border-radius: 6px; max-height: 480px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; font-size: 0.85rem; border: 1px solid #23272d;"></pre>
                </div>
            </div>
            <div class="modal-footer bg-dark border-secondary">
                <button type="button" class="btn btn-outline-light btn-sm" id="btn-copy-log">
                    <i class="ti ti-copy me-1"></i> Copiar Registro
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const activePollers = {};

    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Toggle visibilidad de contraseña con icono de ojo
    document.querySelectorAll('.btn-toggle-password').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input) {
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                if (icon) {
                    if (isPassword) {
                        icon.classList.remove('ti-eye');
                        icon.classList.add('ti-eye-off');
                    } else {
                        icon.classList.remove('ti-eye-off');
                        icon.classList.add('ti-eye');
                    }
                }
            }
        });
    });

    // 1. Probar Conexión (Ping & SSH)
    document.querySelectorAll('.btn-test-conn').forEach(button => {
        button.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            const taskName = this.getAttribute('data-task-name');

            Swal.fire({
                title: 'Comprobando Conexión...',
                html: `Verificando paquetes ICMP (Ping) y puerto SSH hacia <b>${taskName}</b>`,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
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
                        title: '¡Conexión Exitosa!',
                        html: `<div class="text-start p-2 bg-light rounded font-monospace" style="font-size: 0.85rem;">
                                <div class="text-success"><i class="ti ti-check"></i> Ping ICMP: Respondido con éxito.</div>
                                <div class="text-success"><i class="ti ti-check"></i> Puerto SSH: Abierto y listo.</div>
                                <div class="text-success"><i class="ti ti-check"></i> Autenticación: Verificada correctamente.</div>
                               </div>`,
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#2fb344'
                    });
                } else {
                    const info = data.data || {};
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        html: `<div class="text-start p-2 bg-light rounded" style="font-size: 0.85rem;">
                                <div><b>Ping:</b> ${info.ping ? '✅ Respondido' : '❌ Sin respuesta'}</div>
                                <div><b>Puerto SSH:</b> ${info.port_open ? '✅ Abierto' : '❌ Cerrado o inaccesible'}</div>
                                <div><b>Autenticación SSH:</b> ${info.ssh_auth ? '✅ OK' : '❌ Falló (Verifica usuario y contraseña)'}</div>
                                ${info.port_error ? `<div class="text-danger mt-1">${info.port_error}</div>` : ''}
                               </div>`,
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#d63939'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Fallo al ejecutar la prueba',
                    text: err.message,
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#d63939'
                });
            });
        });
    });

    // 2. Iniciar Sincronización Manual Asíncrona (Segundo Plano)
    document.querySelectorAll('.btn-start-sync').forEach(button => {
        button.addEventListener('click', function () {
            const taskId = this.getAttribute('data-task-id');
            const taskName = this.getAttribute('data-task-name');
            const batchSize = this.getAttribute('data-batch-size') || 500;
            const currentSource = this.getAttribute('data-source-path') || '/volume1/dae-RowData/';
            const currentTarget = this.getAttribute('data-target-path') || '/volume1/dae-RowData/MIGRACION_SYNOLOGY/';
            const currentInspurDir = this.getAttribute('data-inspur-dir') || 'VRSS-1';
            const currentLastOrbit = this.getAttribute('data-last-orbit') || '';
            const syncUrl = this.getAttribute('data-url');
            const statusUrl = this.getAttribute('data-status-url');

            Swal.fire({
                title: 'Confirmar Sincronización',
                html: `
                    <div class="text-start mb-2" style="font-size: 0.88rem;">
                        <p class="text-muted mb-3">¿Deseas iniciar la transferencia para la siguiente tarea configurada?</p>
                        
                        <div class="card bg-light-subtle border mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ti ti-server-2 text-primary fs-3 me-2"></i>
                                    <span class="fw-bold fs-4 text-dark text-truncate">${taskName}</span>
                                </div>
                                <div class="border-top pt-2">
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-secondary small fw-semibold"><i class="ti ti-folder me-1 text-primary"></i> 1. Origen (Synology):</span>
                                        <code class="text-dark fw-bold small">${currentSource}</code>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-secondary small fw-semibold"><i class="ti ti-folders me-1 text-purple"></i> 2. Compartida Intermedia:</span>
                                        <code class="text-purple fw-bold small">${currentTarget}</code>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-secondary small fw-semibold"><i class="ti ti-folder-check me-1 text-teal"></i> 3. Destino Inspur (/RAWDATA):</span>
                                        <span class="badge bg-teal-lt font-monospace fw-bold">${currentInspurDir}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-secondary small fw-semibold"><i class="ti ti-history me-1 text-success"></i> 4. Punto de Inicio (Órbita):</span>
                                        ${currentLastOrbit ? `<span class="badge bg-green-lt font-monospace fw-bold">${currentLastOrbit} (Reanuda desde siguiente)</span>` : `<span class="text-muted small fst-italic">Desde el inicio (Omite duplicados)</span>`}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="text-secondary small fw-semibold"><i class="ti ti-files me-1 text-azure"></i> Lote a Transferir:</span>
                                        <span class="badge bg-azure-lt fw-bold">${batchSize} archivos</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-secondary text-center small">
                            <i class="ti ti-shield-check me-1 text-success"></i> Se ignoran duplicados existentes en destino automáticamente.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-player-play me-1"></i> Iniciar Copia y Traslado',
                cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939'
            }).then(result => {
                if (result.isConfirmed) {
                    // Bloquear inmediatamente el botón de sincronizar en la tarjeta
                    const btn = document.getElementById(`btn-sync-${taskId}`);
                    if (btn) {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                    }

                    // Modal de comprobación previa de conectividad
                    Swal.fire({
                        title: 'Comprobando Conexión con NAS...',
                        html: `Verificando conectividad (Ping y puerto SSH) con <b>${taskName}</b> antes de iniciar...`,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Despachar el inicio asíncrono con los parámetros configurados de la tarea
                    fetch(syncUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            limit: batchSize,
                            source_path: currentSource,
                            inspur_target_dir: currentInspurDir
                        })
                    })
                    .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
                    .then(({ status, ok, body }) => {
                        if (ok && body.success) {
                            // Conexión exitosa y proceso en marcha
                            setTaskRunningState(taskId, true);
                            const progressBar = document.getElementById(`task-progress-bar-${taskId}`);
                            const progressCounter = document.getElementById(`task-progress-counter-${taskId}`);
                            const progressLabel = document.getElementById(`task-progress-label-${taskId}`);
                            if (progressBar) progressBar.style.width = '3%';
                            if (progressCounter) progressCounter.textContent = `0 / ${batchSize} arch. (0%)`;
                            if (progressLabel) progressLabel.textContent = `Transfiriendo ${currentSource} a Inspur (${currentInspurDir})...`;

                            Swal.fire({
                                icon: 'info',
                                title: 'Sincronización en Marcha',
                                html: `Conexión verificada.<br>Copiando lote de ${batchSize} archivos a <code>MIGRACION_SYNOLOGY</code> y trasladando a <code>${currentInspurDir}</code>.`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 4500,
                                timerProgressBar: true
                            });

                            // Iniciar polling fluido cada 1.5 segundos
                            startPollingTask(taskId, statusUrl, taskName);
                        } else {
                            // Fallo de conexión o error: paralizar y alertar de inmediato
                            setTaskRunningState(taskId, false);
                            Swal.fire({
                                icon: 'error',
                                title: 'Fallo de Conexión - Ejecución Paralizada',
                                html: `<div class="alert alert-danger text-start p-2 mb-2" style="font-size: 0.85rem;">
                                           <i class="ti ti-wifi-off me-1"></i> <b>La tarea no se puede ejecutar:</b><br>${body.message || 'La NAS remota no responde.'}
                                       </div>
                                       <div class="text-secondary small">La tarea ha sido paralizada para evitar bloqueos del sistema. Comprueba que la NAS esté encendida, en red y con el puerto SSH accesible.</div>`,
                                confirmButtonText: 'Entendido',
                                confirmButtonColor: '#2fb344'
                            });
                        }
                    })
                    .catch(err => {
                        setTaskRunningState(taskId, false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Comunicación - Ejecución Paralizada',
                            text: 'No se pudo comunicar con el servidor: ' + err.message,
                            confirmButtonText: 'Cerrar',
                            confirmButtonColor: '#d63939'
                        });
                    });
                }
            });
        });
    });

    // 2.1 Botón Paralizar / Detener Tarea en Ejecución
    document.querySelectorAll('.btn-stop-sync').forEach(button => {
        button.addEventListener('click', function () {
            const taskId = this.getAttribute('data-task-id');
            const taskName = this.getAttribute('data-task-name');
            const stopUrl = this.getAttribute('data-stop-url');

            Swal.fire({
                title: '¿Paralizar Ejecución de la Tarea?',
                html: `¿Estás seguro de que deseas detener la tarea <b>"${taskName}"</b>?<br><br>
                       <div class="alert alert-warning text-start p-2 mb-0" style="font-size: 0.85rem;">
                           <i class="ti ti-alert-triangle me-1"></i> Se abortará el proceso activo en segundo plano y la tarea volverá a estado inactivo.
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-player-stop me-1"></i> Sí, paralizar ejecución',
                cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939'
            }).then(result => {
                if (result.isConfirmed) {
                    if (activePollers[taskId]) {
                        clearInterval(activePollers[taskId]);
                        delete activePollers[taskId];
                    }

                    fetch(stopUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        setTaskRunningState(taskId, false);
                        Swal.fire({
                            icon: 'info',
                            title: 'Tarea Paralizada',
                            text: data.message,
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#2fb344'
                        }).then(() => {
                            window.location.reload();
                        });
                    })
                    .catch(err => {
                        setTaskRunningState(taskId, false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al paralizar',
                            text: err.message,
                            confirmButtonText: 'Cerrar',
                            confirmButtonColor: '#d63939'
                        });
                    });
                }
            });
        });
    });

    // Función para cambiar el estado visual de la fila y conmutar botones
    function setTaskRunningState(taskId, isRunning) {
        const btnSync = document.getElementById(`btn-sync-${taskId}`);
        const btnStop = document.getElementById(`btn-stop-${taskId}`);
        const statusContainer = document.getElementById(`task-status-container-${taskId}`);
        const progressBox = document.getElementById(`task-progress-box-${taskId}`);

        if (isRunning) {
            if (btnSync) btnSync.classList.add('d-none');
            if (btnStop) btnStop.classList.remove('d-none');
            if (statusContainer) {
                statusContainer.innerHTML = `
                    <span class="badge bg-warning-lt text-warning fw-bold d-inline-flex align-items-center badge-status-pill">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span> En Proceso de Copia (Segundo Plano)
                    </span>
                    <span class="badge bg-purple-lt ms-1"><i class="ti ti-bolt me-1"></i> Asíncrono</span>
                    <span class="badge bg-primary-lt fw-bold ms-1" id="task-phase-badge-${taskId}">
                        <i class="ti ti-refresh me-1"></i> <span class="phase-name">Iniciando...</span>
                    </span>
                `;
            }
            if (progressBox) {
                progressBox.classList.remove('d-none');
            }
        } else {
            if (btnSync) {
                btnSync.classList.remove('d-none');
                btnSync.disabled = false;
            }
            if (btnStop) {
                btnStop.classList.add('d-none');
            }
            if (statusContainer) {
                statusContainer.innerHTML = `
                    <span class="badge bg-secondary-lt fw-bold">En espera</span>
                    <span class="badge bg-blue-lt ms-1"><i class="ti ti-clock me-1"></i> Hourly</span>
                    <span class="badge bg-teal-lt ms-1"><i class="ti ti-stack-2 me-1"></i> Lote: 500 arch.</span>
                `;
            }
        }
    }

    // Polling de estado para tareas en ejecución con actualización en tiempo real
    function startPollingTask(taskId, statusUrl, taskName) {
        if (activePollers[taskId]) {
            clearInterval(activePollers[taskId]);
        }

        activePollers[taskId] = setInterval(() => {
            fetch(statusUrl, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                // Elementos de la barra de progreso
                const progressBox = document.getElementById(`task-progress-box-${taskId}`);
                const progressBar = document.getElementById(`task-progress-bar-${taskId}`);
                const progressCounter = document.getElementById(`task-progress-counter-${taskId}`);
                const progressLabel = document.getElementById(`task-progress-label-${taskId}`);

                if (progressBox) {
                    progressBox.classList.remove('d-none');
                }

                const currentFiles = data.files_transferred || 0;
                let totalFiles = data.total_files_in_batch || 0;
                if (totalFiles > 0 && currentFiles > totalFiles) {
                    totalFiles = currentFiles;
                }
                const batchOrbits = data.batch_size || 5;

                if (totalFiles > 0) {
                    const pct = Math.min(100, Math.round((currentFiles / totalFiles) * 100));
                    if (progressBar) {
                        progressBar.style.width = Math.max(5, pct) + '%';
                    }
                    if (progressCounter) {
                        progressCounter.textContent = `${currentFiles} / ${totalFiles} arch. (${pct}%)`;
                    }
                } else {
                    if (progressBar) {
                        progressBar.style.width = currentFiles > 0 ? '50%' : '15%';
                    }
                    if (progressCounter) {
                        progressCounter.textContent = `${currentFiles} arch. en proceso (Lote: ${batchOrbits} órbitas)`;
                    }
                }

                const subLabel = document.getElementById(`task-progress-sub-${taskId}`);
                if (subLabel) {
                    if (data.current_orbit) {
                        subLabel.innerHTML = `Copiar desde la órbita: <code class="text-primary fw-bold">${data.current_orbit}</code>`;
                    } else if (data.live_output) {
                        subLabel.textContent = data.live_output.split("\n").pop() || 'Procesando...';
                    }
                }

                // Actualizar Relojes de Tiempo Transcurrido y Tiempo Restante Estimado
                const elapsedBox = document.getElementById(`task-timer-elapsed-${taskId}`);
                const etaBox = document.getElementById(`task-timer-eta-${taskId}`);

                if (elapsedBox && data.elapsed_seconds !== undefined) {
                    const elSec = data.elapsed_seconds;
                    const elMin = Math.floor(elSec / 60);
                    const elRemSec = elSec % 60;
                    const elFmt = (elMin < 10 ? '0' : '') + elMin + ':' + (elRemSec < 10 ? '0' : '') + elRemSec;
                    elapsedBox.querySelector('.val-elapsed').textContent = elFmt;
                }

                if (etaBox) {
                    if (data.estimated_remaining_seconds !== null && data.estimated_remaining_seconds !== undefined) {
                        const etaSec = data.estimated_remaining_seconds;
                        if (etaSec > 3600) {
                            const hours = Math.floor(etaSec / 3600);
                            const mins = Math.floor((etaSec % 3600) / 60);
                            etaBox.querySelector('.val-eta').textContent = `~${hours}h ${mins}m restantes`;
                        } else if (etaSec > 60) {
                            const mins = Math.floor(etaSec / 60);
                            const secs = etaSec % 60;
                            etaBox.querySelector('.val-eta').textContent = `~${mins}m ${secs}s restantes`;
                        } else {
                            etaBox.querySelector('.val-eta').textContent = `~${etaSec}s restantes`;
                        }
                    } else {
                        etaBox.querySelector('.val-eta').textContent = 'Calculando ETA...';
                    }
                }

                // Actualizar Insignia de Fase Activa en la cabecera
                const phaseBadge = document.getElementById(`task-phase-badge-${taskId}`);
                if (phaseBadge && data.current_phase) {
                    if (data.current_phase_code === 'phase2') {
                        phaseBadge.className = 'badge bg-teal-lt fw-bold ms-1';
                        phaseBadge.innerHTML = `<i class="ti ti-truck-delivery me-1"></i> 2. Traslado a Inspur RAWDATA`;
                    } else if (data.current_phase_code === 'phase1') {
                        phaseBadge.className = 'badge bg-azure-lt fw-bold ms-1';
                        phaseBadge.innerHTML = `<i class="ti ti-folder-share me-1"></i> 1. Copiando a MIGRACION_SYNOLOGY`;
                    } else {
                        phaseBadge.className = 'badge bg-primary-lt fw-bold ms-1';
                        phaseBadge.innerHTML = `<i class="ti ti-refresh me-1"></i> ${data.current_phase}`;
                    }
                }

                if (data.status === 'running') {
                    if (progressLabel) {
                        if (data.current_phase_code === 'phase2') {
                            progressLabel.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-teal" style="width: 11px; height: 11px;"></span> <b>Fase 2 en curso:</b> Trasladando archivos a <code>Inspur RAWDATA (${data.latest_log?.inspur_target_dir_used || 'Destino'})</code>...`;
                        } else if (currentFiles > 0) {
                            progressLabel.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-success" style="width: 11px; height: 11px;"></span> <b>Fase 1 en curso:</b> Copiando archivos a <code>MIGRACION_SYNOLOGY</code>...`;
                        } else if (data.live_output && data.live_output.includes('INICIANDO')) {
                            progressLabel.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-primary" style="width: 11px; height: 11px;"></span> Conectado a Synology: Detectando órbitas y archivos pendientes...`;
                        } else {
                            progressLabel.innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-warning" style="width: 11px; height: 11px;"></span> Iniciando proceso de transferencia...`;
                        }
                    }
                } else if (data.status === 'idle' || data.status === 'failed' || data.latest_log?.status === 'failed') {
                    // La tarea finalizó o reportó fallo: paralizar polling
                    clearInterval(activePollers[taskId]);
                    delete activePollers[taskId];
                    setTaskRunningState(taskId, false);

                    const log = data.latest_log || {};
                    if (data.last_run_status === 'completed' && log.status === 'completed') {
                        if (progressBar) {
                            progressBar.style.width = '100%';
                            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                        }
                        if (progressLabel) {
                            progressLabel.textContent = '¡Lote completado!';
                        }
                        if (progressCounter) {
                            progressCounter.textContent = `${log.files || currentFiles} archivos transferidos`;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '¡Sincronización Finalizada con Éxito!',
                            html: `La tarea <b>${taskName}</b> completó la transferencia de las órbitas.<br><br>
                                   <div class="badge bg-green-lt p-2 fs-3">
                                       Archivos: ${log.files || currentFiles} | Tamaño: ${log.size || '0 B'} | Duración: ${log.duration || '-'}
                                   </div>`,
                            confirmButtonText: 'Actualizar Vista',
                            confirmButtonColor: '#2fb344'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        // Reportó error de conexión o fallo técnico
                        if (progressBar) {
                            progressBar.classList.remove('bg-primary', 'progress-bar-animated', 'progress-bar-striped');
                            progressBar.classList.add('bg-danger');
                        }
                        if (progressLabel) {
                            progressLabel.className = 'text-danger fw-bold';
                            progressLabel.innerHTML = '<i class="ti ti-alert-triangle me-1"></i> Ejecución Paralizada por Error';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Sincronización Paralizada por Error',
                            html: `La tarea <b>${taskName}</b> no se está ejecutando debido a un problema de conexión o configuración:<br><br>
                                   <div class="alert alert-danger text-start p-2 mb-2" style="font-size: 0.85rem;">
                                       <i class="ti ti-wifi-off me-1"></i> <b>Causa detectada:</b><br>${log.error_message || 'Fallo de conectividad con la NAS remota.'}
                                   </div>
                                   <div class="text-secondary small">La ejecución fue detenida para proteger el sistema. Verifica que la NAS esté encendida y accesible.</div>`,
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#2fb344'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                }
            })
            .catch(() => {
                // Silenciar errores temporales de red en el polling
            });
        }, 1500);
    }

    // Si al cargar la página alguna fila ya estaba en 'running', activar polling automático
    document.querySelectorAll('[data-current-status="running"]').forEach(row => {
        const taskId = row.getAttribute('data-task-id');
        const statusUrl = row.getAttribute('data-status-url');
        setTaskRunningState(taskId, true);
        startPollingTask(taskId, statusUrl, 'Tarea #' + taskId);
    });

    // 3. Confirmar Eliminación de Tarea con SweetAlert2
    document.querySelectorAll('.btn-delete-task').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const taskName = this.getAttribute('data-task-name') || 'esta tarea';
            Swal.fire({
                title: '¿Eliminar Tarea de Migración?',
                html: `¿Estás seguro de que deseas eliminar la tarea <b>"${taskName}"</b>?<br><br>
                       <div class="alert alert-warning text-start p-2 mb-0" style="font-size: 0.85rem;">
                           <i class="ti ti-alert-triangle me-1"></i> <b>Advertencia:</b> Esta acción no se puede deshacer. Los registros históricos y estadísticas asociadas se eliminarán permanentemente.
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-check me-1"></i> Sí, eliminar tarea',
                cancelButtonText: '<i class="ti ti-x me-1"></i> Cancelar',
                confirmButtonColor: '#2fb344',
                cancelButtonColor: '#d63939',
                focusCancel: true
            }).then(res => {
                if (res.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Timer para refrescar modal de visor en vivo automáticamente mientras esté abierto
    let visorLiveTimer = null;
    let currentVisorUrl = null;

    function refreshVisorContent() {
        if (!currentVisorUrl) return;
        fetch(currentVisorUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('log-task-name').textContent = data.task_name;
            document.getElementById('log-started-at').textContent = data.started_at;
            document.getElementById('log-duration').textContent = data.duration;
            const totalStr = data.total_files ? ` / ${data.total_files}` : '';
            document.getElementById('log-files').textContent = `${data.files}${totalStr} archivos`;
            document.getElementById('log-size').textContent = data.size || '0 B';

            const badge = document.getElementById('log-status-badge');
            if (data.status === 'completed') {
                badge.className = 'badge bg-success';
                badge.textContent = 'COMPLETADO';
            } else if (data.status === 'failed') {
                badge.className = 'badge bg-danger';
                badge.textContent = 'FALLIDO';
            } else {
                badge.className = 'badge bg-warning';
                badge.textContent = (data.status || 'EN CURSO').toUpperCase();
            }

            let fullText = data.output || 'Iniciando proceso de transferencia...';
            if (data.error_message) {
                fullText += "\n\n[ERROR]: " + data.error_message;
            }
            const logBox = document.getElementById('log-output-content');
            logBox.textContent = fullText;
            logBox.scrollTop = logBox.scrollHeight;

            if (data.status !== 'running' && visorLiveTimer) {
                clearInterval(visorLiveTimer);
                visorLiveTimer = null;
            }
        })
        .catch(() => {});
    }

    document.getElementById('modal-visor-log').addEventListener('hidden.bs.modal', function () {
        if (visorLiveTimer) {
            clearInterval(visorLiveTimer);
            visorLiveTimer = null;
        }
        currentVisorUrl = null;
    });

    // 4. Ver Consola / Log en Modal
    const modalVisor = new bootstrap.Modal(document.getElementById('modal-visor-log'));
    document.querySelectorAll('.btn-view-log').forEach(button => {
        button.addEventListener('click', function () {
            const url = this.getAttribute('data-url');
            currentVisorUrl = url;

            document.getElementById('log-task-name').textContent = 'Cargando...';
            document.getElementById('log-output-content').textContent = 'Cargando salida de consola...';

            modalVisor.show();
            refreshVisorContent();

            if (visorLiveTimer) clearInterval(visorLiveTimer);
            visorLiveTimer = setInterval(refreshVisorContent, 2000);
        });
    });

    // Event listener para el botón directo "Ver registro en vivo" en la tarjeta de progreso
    document.querySelectorAll('.btn-view-live-log').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const logUrl = this.getAttribute('data-log-url');
            if (logUrl) {
                const dummyBtn = document.createElement('button');
                dummyBtn.className = 'btn-view-log';
                dummyBtn.setAttribute('data-url', logUrl);
                document.body.appendChild(dummyBtn);
                // Disparar click reutilizando la lógica
                document.getElementById('log-task-name').textContent = 'Cargando...';
                document.getElementById('log-output-content').textContent = 'Cargando salida en vivo...';
                modalVisor.show();
                fetch(logUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.text())
                .then(text => {
                    let data = JSON.parse(text);
                    document.getElementById('log-task-name').textContent = data.task_name;
                    document.getElementById('log-started-at').textContent = data.started_at;
                    document.getElementById('log-duration').textContent = data.duration;
                    document.getElementById('log-files').textContent = data.files + ' archivos';
                    document.getElementById('log-size').textContent = data.size || '0 B';
                    const badge = document.getElementById('log-status-badge');
                    badge.className = data.status === 'completed' ? 'badge bg-success' : (data.status === 'failed' ? 'badge bg-danger' : 'badge bg-warning');
                    badge.textContent = (data.status || 'EN CURSO').toUpperCase();
                    let fullText = data.output || 'Esperando volcado de consola...';
                    if (data.error_message) {
                        fullText += "\n\n[ERROR]: " + data.error_message;
                    }
                    document.getElementById('log-output-content').textContent = fullText;
                }).catch(err => {
                    document.getElementById('log-output-content').textContent = 'Error al cargar el log: ' + err.message;
                }).finally(() => {
                    dummyBtn.remove();
                });
            }
        });
    });

    // 5. Copiar contenido de la consola
    document.getElementById('btn-copy-log').addEventListener('click', function () {
        const text = document.getElementById('log-output-content').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="ti ti-check me-1"></i> ¡Copiado!';
            setTimeout(() => {
                this.innerHTML = originalHtml;
            }, 2000);
        });
    });
});
</script>
@endpush
