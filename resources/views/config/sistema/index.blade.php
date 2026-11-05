@extends('layouts.app')

@section('title', 'Info. Sistema')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    CONFIGURACIÓN &amp; INFRAESTRUCTURA
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-server me-2 text-primary" style="font-size: 1.6rem;"></i>Info. Sistema
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="location.reload();">
                    <i class="ti ti-refresh me-1 fs-3"></i> Actualizar Diagnóstico
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        {{-- Tarjeta Única Integral de Información del Sistema --}}
        <div class="card border-0 shadow-sm">
            {{-- Encabezado de la Tarjeta --}}
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
                <div class="d-flex align-items-center">
                    <span class="avatar avatar-md rounded-circle text-white me-3 shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #206bc4, #0054a6);">
                        <i class="ti ti-cpu fs-2"></i>
                    </span>
                    <div>
                        <h3 class="card-title fw-bold mb-0" style="font-size: 1.15rem;">Especificaciones y Estado del Sistema</h3>
                        <div class="text-secondary small">Diagnóstico técnico básico del entorno, frameworks y base de datos</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($isLocal)
                        <span class="badge bg-warning-lt text-warning px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.82rem; border: 1px solid rgba(245, 159, 0, 0.35);">
                            <i class="ti ti-device-laptop me-1 fs-3"></i> Entorno Local
                        </span>
                    @else
                        <span class="badge bg-green-lt text-green px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.82rem; border: 1px solid rgba(47, 179, 68, 0.35);">
                            <i class="ti ti-world me-1 fs-3"></i> Entorno Web
                        </span>
                    @endif

                    @if($dbStatus)
                        <span class="badge bg-success-lt text-success px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.82rem; border: 1px solid rgba(47, 179, 68, 0.35);">
                            <i class="ti ti-database-check me-1 fs-3"></i> BD Conectada
                        </span>
                    @else
                        <span class="badge bg-danger-lt text-danger px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="font-size: 0.82rem; border: 1px solid rgba(214, 57, 57, 0.35);">
                            <i class="ti ti-database-x me-1 fs-3"></i> Error BD
                        </span>
                    @endif
                </div>
            </div>

            {{-- Cuerpo de la Tarjeta --}}
            <div class="card-body p-4">
                {{-- Fila con los 5 bloques de indicadores con iconos de colores llamativos --}}
                <div class="row g-3 mb-4">
                    {{-- 1. Versión del Sistema --}}
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="p-3 rounded-3 bg-body-tertiary border h-100 d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #206bc4, #0054a6); box-shadow: 0 4px 10px rgba(32, 107, 196, 0.3) !important;">
                                <i class="ti ti-package fs-2"></i>
                            </span>
                            <div class="overflow-hidden">
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">SISTEMA</div>
                                <div class="h3 mb-0 fw-bold text-dark font-monospace text-truncate">v{{ $appVersion }}</div>
                                <div class="text-muted small text-truncate" style="font-size: 0.75rem;" title="{{ $appName }}">{{ $appName }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Versión de PHP --}}
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="p-3 rounded-3 bg-body-tertiary border h-100 d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #6366f1, #4338ca); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3) !important;">
                                <i class="ti ti-brand-php fs-2"></i>
                            </span>
                            <div class="overflow-hidden">
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">PHP</div>
                                <div class="h3 mb-0 fw-bold text-dark font-monospace text-truncate">PHP {{ $phpVersion }}</div>
                                <div class="text-muted small font-monospace text-truncate" style="font-size: 0.75rem;" title="SAPI: {{ $phpSapi }}">SAPI: {{ $phpSapi }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Versión de Laravel --}}
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="p-3 rounded-3 bg-body-tertiary border h-100 d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #ff2d20, #dc2626); box-shadow: 0 4px 10px rgba(255, 45, 32, 0.3) !important;">
                                <i class="ti ti-brand-laravel fs-2"></i>
                            </span>
                            <div class="overflow-hidden">
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">LARAVEL</div>
                                <div class="h3 mb-0 fw-bold text-dark font-monospace text-truncate">v{{ $laravelVersion }}</div>
                                <div class="text-muted small text-truncate" style="font-size: 0.75rem;">Debug: {{ $appDebug ? 'Habilitado' : 'Deshabilitado' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Tipo de Entorno --}}
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="p-3 rounded-3 bg-body-tertiary border h-100 d-flex align-items-center">
                            @if($isLocal)
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #f59f00, #d97706); box-shadow: 0 4px 10px rgba(245, 159, 0, 0.3) !important;">
                                <i class="ti ti-device-laptop fs-2"></i>
                            </span>
                            @else
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3) !important;">
                                <i class="ti ti-world fs-2"></i>
                            </span>
                            @endif
                            <div class="overflow-hidden">
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">ENTORNO</div>
                                <div class="h3 mb-0 fw-bold {{ $isLocal ? 'text-warning' : 'text-success' }} text-truncate">{{ strtoupper($appEnv) }}</div>
                                <div class="text-muted small text-truncate" style="font-size: 0.75rem;" title="{{ $isLocal ? 'Local (Desarrollo)' : 'Web (Producción)' }}">{{ $isLocal ? 'Local (Desarrollo)' : 'Web (Producción)' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Base de Datos --}}
                    <div class="col-12 col-sm-6 col-lg">
                        <div class="p-3 rounded-3 bg-body-tertiary border h-100 d-flex align-items-center">
                            <span class="avatar avatar-md rounded-circle text-white me-3 flex-shrink-0 shadow-sm" style="width: 46px; height: 46px; background: linear-gradient(135deg, #0ca678, #087f5b); box-shadow: 0 4px 10px rgba(12, 166, 120, 0.3) !important;">
                                <i class="ti ti-database fs-2"></i>
                            </span>
                            <div class="overflow-hidden">
                                <div class="text-secondary small fw-bold text-uppercase" style="letter-spacing: 0.05em; font-size: 0.68rem;">BASE DE DATOS</div>
                                <div class="h3 mb-0 fw-bold text-dark font-monospace text-truncate" title="{{ ucfirst($dbDriver) }} {{ $dbVersion }}">
                                    {{ ucfirst($dbDriver) }} {{ Str::before($dbVersion, '-') }}
                                </div>
                                <div class="text-muted small text-truncate font-monospace" style="font-size: 0.75rem;" title="{{ $dbName }}">{{ $dbName }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detalle Técnico Estructurado dentro de la misma tarjeta --}}
                <div class="border rounded-3 p-3 p-md-4 bg-light-subtle">
                    <div class="row g-4">
                        {{-- Columna 1: Aplicación y Entorno --}}
                        <div class="col-md-6 border-end-md">
                            <h4 class="text-dark fw-bold mb-3 d-flex align-items-center">
                                <i class="ti ti-app-window me-2 text-primary"></i> Aplicación y Servidor
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2" style="width: 42%;">Nombre del Sistema:</td>
                                            <td class="text-dark fw-bold py-2">{{ $appName }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Versión de App:</td>
                                            <td class="py-2"><span class="badge bg-primary-lt fw-bold font-monospace">v{{ $appVersion }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Tipo de Entorno:</td>
                                            <td class="py-2">
                                                @if($isLocal)
                                                    <span class="badge bg-warning-lt text-warning fw-bold">LOCAL (Desarrollo)</span>
                                                @else
                                                    <span class="badge bg-success-lt text-success fw-bold">WEB (Producción)</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Modo Depuración:</td>
                                            <td class="py-2">
                                                @if($appDebug)
                                                    <span class="badge bg-yellow-lt fw-bold">Habilitado (Debug ON)</span>
                                                @else
                                                    <span class="badge bg-secondary-lt fw-bold">Deshabilitado (Debug OFF)</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">URL Base:</td>
                                            <td class="py-2 font-monospace small text-primary">{{ $appUrl }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Zona Horaria:</td>
                                            <td class="py-2 text-dark font-monospace small">{{ $appTimezone }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Columna 2: Framework y Base de Datos --}}
                        <div class="col-md-6">
                            <h4 class="text-dark fw-bold mb-3 d-flex align-items-center">
                                <i class="ti ti-database-cog me-2 text-success"></i> Framework &amp; Base de Datos
                            </h4>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2" style="width: 42%;">Versión de Laravel:</td>
                                            <td class="py-2"><span class="badge bg-danger-lt fw-bold font-monospace">v{{ $laravelVersion }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Versión de PHP:</td>
                                            <td class="py-2"><span class="badge bg-indigo-lt fw-bold font-monospace">PHP {{ $phpVersion }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Interfaz SAPI:</td>
                                            <td class="py-2 font-monospace small text-dark">{{ $phpSapi }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Motor de Base de Datos:</td>
                                            <td class="py-2 fw-bold text-dark font-monospace text-uppercase">{{ $dbDriver }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Base de Datos:</td>
                                            <td class="py-2 font-monospace fw-bold text-primary">{{ $dbName }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Versión del Servidor BD:</td>
                                            <td class="py-2 font-monospace small text-dark">{{ $dbVersion }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-secondary fw-semibold py-2">Estado de Conexión:</td>
                                            <td class="py-2">
                                                @if($dbStatus)
                                                    <span class="badge bg-success text-white fw-bold d-inline-flex align-items-center gap-1">
                                                        <i class="ti ti-check"></i> Conexión Exitosa
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger text-white fw-bold d-inline-flex align-items-center gap-1">
                                                        <i class="ti ti-x"></i> Error de Conexión
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
