@extends('layouts.app')

@section('title', 'Identidad del Sistema')

@push('css')
<style>
    .upload-box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        transition: all 0.25s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }
    .upload-box:hover, .upload-box.dragover {
        border-color: #206bc4;
        background: #f0f7ff;
    }
    .preview-canvas {
        min-height: 125px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        position: relative;
        padding: 1.25rem 1rem;
    }
    .preview-real-frame {
        background: #ffffff;
        border: 1.5px dashed #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 8px 14px;
        transition: all 0.2s ease;
    }
    .preview-real-frame.frame-navbar {
        width: 100%;
        max-width: 220px;
        min-height: 56px;
    }
    .preview-real-frame.frame-login {
        width: 100%;
        max-width: 310px;
        min-height: 85px;
    }
    .browser-mockup-tab {
        background: #f1f5f9;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
        padding: 8px 16px;
        display: inline-flex;
        align-items: center;
        font-size: 0.82rem;
        color: #334155;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        border-bottom: none;
        max-width: 260px;
    }
    .browser-mockup-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px 8px 0 0;
        padding: 6px 12px;
    }
</style>
@endpush

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    CONFIGURACIÓN &amp; INFRAESTRUCTURA
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-palette me-2 text-primary" style="font-size: 1.6rem; color: #d63384 !important;"></i>Identidad del Sistema
                </h2>
            </div>
            @can('editar-identidad')
            <div class="col-auto ms-auto d-print-none d-flex align-items-center gap-2">
                <button type="submit" form="form-system-identity" class="btn btn-success fw-bold shadow-sm px-3">
                    <i class="ti ti-device-floppy me-1 fs-3"></i> Guardar Cambios
                </button>
            </div>
            @endcan
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-circle-check fs-2 me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-triangle fs-2 me-2"></i>
                    <div>
                        <div class="fw-bold mb-1">Por favor verifica los siguientes errores:</div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        @endif

        <form id="form-system-identity" action="{{ route('identidad.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- 1. SECCIÓN: NOMBRE GENERAL DEL SISTEMA --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded-circle text-white me-3 shadow-sm" style="width: 42px; height: 42px; background: linear-gradient(135deg, #0ea5e9, #0284c7);">
                            <i class="ti ti-forms fs-2"></i>
                        </span>
                        <div>
                            <h3 class="card-title fw-bold mb-0">Nombre del Sistema</h3>
                            <div class="text-secondary small">Define el nombre global de la aplicación que se reflejará en toda la plataforma</div>
                        </div>
                    </div>
                    <span class="badge bg-blue-lt text-blue px-3 py-1 fw-bold">General</span>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-7">
                            <label class="form-label required fw-bold" for="app_name">Nombre de la Aplicación</label>
                            <div class="input-icon mb-2">
                                <span class="input-icon-addon">
                                    <i class="ti ti-app-window text-secondary"></i>
                                </span>
                                <input type="text" 
                                       class="form-control form-control-lg @error('app_name') is-invalid @enderror" 
                                       id="app_name" 
                                       name="app_name" 
                                       value="{{ old('app_name', $appName) }}" 
                                       maxlength="60" 
                                       required
                                       placeholder="Ej: Sistema Base"
                                       @cannot('editar-identidad') disabled @endcannot>
                            </div>
                            <div class="form-text text-muted">
                                <i class="ti ti-info-circle me-1"></i> Este nombre se visualizará en la barra de títulos del navegador, pie de página, notificaciones y correos del sistema.
                            </div>
                        </div>
                        <div class="col-lg-5 mt-3 mt-lg-0">
                            <div class="p-3 rounded-3 bg-body-tertiary border">
                                <div class="text-secondary small fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                    <i class="ti ti-eye me-1 text-primary"></i> Vista Previa en Pestaña del Navegador
                                </div>
                                <div class="browser-mockup-bar d-flex align-items-center">
                                    <div class="d-flex gap-1 me-3">
                                        <span class="rounded-circle" style="width: 9px; height: 9px; background: #ef4444;"></span>
                                        <span class="rounded-circle" style="width: 9px; height: 9px; background: #f59e0b;"></span>
                                        <span class="rounded-circle" style="width: 9px; height: 9px; background: #10b981;"></span>
                                    </div>
                                    <div class="browser-mockup-tab shadow-sm text-truncate">
                                        @if($favicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($favicon))
                                            <img id="tab-mock-favicon" 
                                                 src="{{ asset('storage/' . $favicon) }}" 
                                                 alt="Favicon" 
                                                 class="me-2" 
                                                 style="width: 16px; height: 16px; object-fit: contain;">
                                        @else
                                            <i id="tab-mock-icon" class="ti ti-world me-2 text-primary" style="font-size: 1rem;"></i>
                                            <img id="tab-mock-favicon" src="" alt="Favicon" class="me-2 d-none" style="width: 16px; height: 16px; object-fit: contain;">
                                        @endif
                                        <span id="tab-mock-title" class="text-truncate">Inicio - {{ $appName }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. SECCIÓN: LOGOTIPOS (BARRA LATERAL Y LOGIN) --}}
            <div class="row g-4 mb-4">
                {{-- Columna A: Logotipo Barra Lateral (Navbar) --}}
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md rounded-circle text-white me-3 shadow-sm" style="width: 42px; height: 42px; background: linear-gradient(135deg, #206bc4, #0054a6);">
                                    <i class="ti ti-layout-sidebar-left-collapse fs-2"></i>
                                </span>
                                <div>
                                    <h3 class="card-title fw-bold mb-0">Logotipo Barra Lateral (Navbar)</h3>
                                    <div class="text-secondary small">Visible en la barra de navegación del panel</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                @if($logoNavbar)
                                    <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Personalizado</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">Por Defecto</span>
                                @endif
                            </div>
                        </div>

                        {{-- Barra de especificaciones de dimensión --}}
                        <div class="bg-body-tertiary px-4 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 0.75rem;">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-danger-lt text-danger fw-bold" title="Imágenes con dimensiones menores a este valor no podrán cargarse">
                                    <i class="ti ti-shield-alert me-1"></i> Rango Mínimo: <strong>80 × 25 px</strong>
                                </span>
                                <span class="badge bg-blue-lt text-blue fw-bold" title="Tamaño óptimo para visualización nítida">
                                    <i class="ti ti-sparkles me-1"></i> Tamaño Óptimo: <strong>185 × 42 px</strong>
                                </span>
                            </div>
                            <span class="badge bg-success-lt text-success font-monospace" style="font-size: 0.70rem;">
                                <i class="ti ti-arrows-maximize me-1"></i> Auto-redimensionado activo
                            </span>
                        </div>

                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem;">
                                        Vista Previa en Barra Lateral
                                    </div>
                                    <span class="badge bg-blue-lt text-blue font-monospace" style="font-size: 0.68rem;">
                                        <i class="ti ti-dimensions me-1"></i> Visualización: 185 × 42 px
                                    </span>
                                </div>
                                <div class="preview-canvas mb-3">
                                    <div class="preview-real-frame frame-navbar shadow-sm">
                                        <div id="preview-container-navbar" class="text-center d-flex align-items-center justify-content-center" style="max-width: 185px; max-height: 42px;">
                                            @if($logoNavbar && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoNavbar))
                                                <img src="{{ asset('storage/' . $logoNavbar) }}" 
                                                     id="img-preview-navbar" 
                                                     alt="Logo Navbar" 
                                                     class="img-fluid" 
                                                     style="max-height: 42px; max-width: 185px; width: auto; height: auto; object-fit: contain; display: block;">
                                            @else
                                                <div id="default-preview-navbar" class="d-flex align-items-center justify-content-center">
                                                    <div class="bg-primary-lt p-2 rounded-3 me-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                        <i class="ti ti-building-store text-primary fs-2"></i>
                                                    </div>
                                                    <span class="h2 mb-0 fw-bold text-truncate" id="navbar-brand-preview-text" style="max-width: 135px;" title="{{ $appName }}">
                                                        {{ $appName }}
                                                    </span>
                                                </div>
                                                <img src="" id="img-preview-navbar" alt="Logo Navbar" class="img-fluid d-none" style="max-height: 42px; max-width: 185px; width: auto; height: auto; object-fit: contain; display: block;">
                                            @endif
                                        </div>
                                    </div>
                                    <div id="res-indicator-navbar" class="text-secondary small mt-2 text-center font-monospace" style="font-size: 0.72rem;">
                                        Proporción recomendada horizontal (~4:1) • Escalado automático proporcional
                                    </div>
                                </div>

                                @can('editar-identidad')
                                <div class="upload-box p-3 text-center mb-3" onclick="document.getElementById('file_logo_navbar').click();">
                                    <input type="file" 
                                           id="file_logo_navbar" 
                                           name="logo_navbar" 
                                           class="d-none" 
                                           accept=".png,.jpg,.jpeg,.svg,.webp" 
                                           onchange="previewImage(this, 'navbar')">
                                    <i class="ti ti-cloud-upload fs-1 text-primary mb-1 d-block"></i>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">Haz clic para subir un nuevo logotipo</div>
                                    <div class="text-secondary small">PNG, JPG, SVG o WEBP (Máx. 2 MB)</div>
                                    <div class="d-flex justify-content-center align-items-center gap-2 mt-2 flex-wrap">
                                        <span class="badge bg-danger-lt text-danger font-monospace" style="font-size: 0.70rem;">
                                            <i class="ti ti-ban me-1"></i> Bloqueo si &lt; 80 × 25 px
                                        </span>
                                        <span class="badge bg-primary-lt text-primary font-monospace" style="font-size: 0.70rem;">
                                            <i class="ti ti-check me-1"></i> Óptimo: 185 × 42 px
                                        </span>
                                    </div>
                                </div>
                                @endcan
                            </div>

                            @can('editar-identidad')
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <span class="text-muted small">Estado: {{ $logoNavbar ? 'Archivo subido' : 'Predeterminado SVG' }}</span>
                                @if($logoNavbar)
                                    <button type="button" 
                                            class="btn btn-danger btn-sm fw-bold shadow-sm" 
                                            onclick="confirmResetAsset('navbar', 'Logotipo de Barra Lateral')">
                                        <i class="ti ti-trash me-1"></i> Restablecer por defecto
                                    </button>
                                @endif
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- Columna B: Logotipo de Inicio de Sesión (Login) --}}
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md rounded-circle text-white me-3 shadow-sm" style="width: 42px; height: 42px; background: linear-gradient(135deg, #6366f1, #4338ca);">
                                    <i class="ti ti-login fs-2"></i>
                                </span>
                                <div>
                                    <h3 class="card-title fw-bold mb-0">Logotipo de Inicio de Sesión (Login)</h3>
                                    <div class="text-secondary small">Visible en la pantalla de autenticación y acceso</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                @if($logoLogin)
                                    <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Personalizado</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">Por Defecto</span>
                                @endif
                            </div>
                        </div>

                        {{-- Barra de especificaciones de dimensión --}}
                        <div class="bg-body-tertiary px-4 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 0.75rem;">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-danger-lt text-danger fw-bold" title="Imágenes con dimensiones menores a este valor no podrán cargarse">
                                    <i class="ti ti-shield-alert me-1"></i> Rango Mínimo: <strong>120 × 35 px</strong>
                                </span>
                                <span class="badge bg-indigo-lt text-indigo fw-bold" title="Tamaño óptimo para visualización nítida">
                                    <i class="ti ti-sparkles me-1"></i> Tamaño Óptimo: <strong>280 × 65 px</strong>
                                </span>
                            </div>
                            <span class="badge bg-success-lt text-success font-monospace" style="font-size: 0.70rem;">
                                <i class="ti ti-arrows-maximize me-1"></i> Auto-redimensionado activo
                            </span>
                        </div>

                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="text-secondary small fw-bold text-uppercase" style="font-size: 0.72rem;">
                                        Vista Previa en Inicio de Sesión
                                    </div>
                                    <span class="badge bg-indigo-lt text-indigo font-monospace" style="font-size: 0.68rem;">
                                        <i class="ti ti-dimensions me-1"></i> Visualización: 280 × 65 px
                                    </span>
                                </div>
                                <div class="preview-canvas mb-3">
                                    <div class="preview-real-frame frame-login shadow-sm">
                                        <div id="preview-container-login" class="text-center d-flex align-items-center justify-content-center" style="max-width: 280px; max-height: 65px;">
                                            @if($logoLogin && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoLogin))
                                                <img src="{{ asset('storage/' . $logoLogin) }}" 
                                                     id="img-preview-login" 
                                                     alt="Logo Login" 
                                                     class="img-fluid" 
                                                     style="max-height: 65px; max-width: 280px; width: auto; height: auto; object-fit: contain; display: block;">
                                            @else
                                                <div id="default-preview-login" class="d-flex align-items-center justify-content-center">
                                                    <div class="bg-primary-lt p-2 rounded-3 me-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                                        <i class="ti ti-building-store text-primary fs-1"></i>
                                                    </div>
                                                    <span class="h1 mb-0 fw-bold text-truncate" id="login-brand-preview-text" style="max-width: 210px;" title="{{ $appName }}">
                                                        {{ $appName }}
                                                    </span>
                                                </div>
                                                <img src="" id="img-preview-login" alt="Logo Login" class="img-fluid d-none" style="max-height: 65px; max-width: 280px; width: auto; height: auto; object-fit: contain; display: block;">
                                            @endif
                                        </div>
                                    </div>
                                    <div id="res-indicator-login" class="text-secondary small mt-2 text-center font-monospace" style="font-size: 0.72rem;">
                                        Proporción recomendada horizontal o cuadrada • Escalado automático proporcional
                                    </div>
                                </div>

                                @can('editar-identidad')
                                <div class="upload-box p-3 text-center mb-3" onclick="document.getElementById('file_logo_login').click();">
                                    <input type="file" 
                                           id="file_logo_login" 
                                           name="logo_login" 
                                           class="d-none" 
                                           accept=".png,.jpg,.jpeg,.svg,.webp" 
                                           onchange="previewImage(this, 'login')">
                                    <i class="ti ti-cloud-upload fs-1 text-primary mb-1 d-block" style="color: #6366f1 !important;"></i>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;">Haz clic para subir un nuevo logotipo</div>
                                    <div class="text-secondary small">PNG, JPG, SVG o WEBP (Máx. 2 MB)</div>
                                    <div class="d-flex justify-content-center align-items-center gap-2 mt-2 flex-wrap">
                                        <span class="badge bg-danger-lt text-danger font-monospace" style="font-size: 0.70rem;">
                                            <i class="ti ti-ban me-1"></i> Bloqueo si &lt; 120 × 35 px
                                        </span>
                                        <span class="badge bg-indigo-lt text-indigo font-monospace" style="font-size: 0.70rem;">
                                            <i class="ti ti-check me-1"></i> Óptimo: 280 × 65 px
                                        </span>
                                    </div>
                                </div>
                                @endcan
                            </div>

                            @can('editar-identidad')
                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <span class="text-muted small">Estado: {{ $logoLogin ? 'Archivo subido' : 'Predeterminado SVG' }}</span>
                                @if($logoLogin)
                                    <button type="button" 
                                            class="btn btn-danger btn-sm fw-bold shadow-sm" 
                                            onclick="confirmResetAsset('login', 'Logotipo de Inicio de Sesión')">
                                        <i class="ti ti-trash me-1"></i> Restablecer por defecto
                                    </button>
                                @endif
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. SECCIÓN: FAVICON DEL NAVEGADOR --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between border-bottom flex-wrap gap-2">
                    <div class="d-flex align-items-center">
                        <span class="avatar avatar-md rounded-circle text-white me-3 shadow-sm" style="width: 42px; height: 42px; background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="ti ti-photo-star fs-2"></i>
                        </span>
                        <div>
                            <h3 class="card-title fw-bold mb-0">Favicon del Navegador</h3>
                            <div class="text-secondary small">Icono diminuto que acompaña al título en las pestañas y marcadores del navegador</div>
                        </div>
                    </div>
                    @if($favicon)
                        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Personalizado</span>
                    @else
                        <span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">Por Defecto</span>
                    @endif
                </div>

                {{-- Barra de especificaciones de dimensión --}}
                <div class="bg-body-tertiary px-4 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 0.75rem;">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-danger-lt text-danger fw-bold" title="Favicons con dimensiones menores a 16x16 px no podrán cargarse">
                            <i class="ti ti-shield-alert me-1"></i> Rango Mínimo: <strong>16 × 16 px</strong>
                        </span>
                        <span class="badge bg-warning-lt text-dark fw-bold" title="Tamaño óptimo para nitidez en pestañas">
                            <i class="ti ti-sparkles me-1"></i> Tamaño Óptimo: <strong>32 × 32 px</strong> o <strong>64 × 64 px</strong>
                        </span>
                    </div>
                    <span class="badge bg-success-lt text-success font-monospace" style="font-size: 0.70rem;">
                        <i class="ti ti-arrows-maximize me-1"></i> Auto-optimización activa
                    </span>
                </div>

                <div class="card-body p-4">
                    <div class="row align-items-center g-4">
                        <div class="col-12 col-md-4 text-center">
                            <div class="text-secondary small mb-2 fw-bold text-uppercase" style="font-size: 0.72rem;">
                                Icono Actual (16x16 / 32x32 / 64x64)
                            </div>
                            <div class="d-inline-flex p-3 rounded-3 bg-body-tertiary border align-items-center justify-content-center shadow-sm" style="min-width: 100px; min-height: 100px;">
                                @if($favicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($favicon))
                                    <img src="{{ asset('storage/' . $favicon) }}" 
                                         id="img-preview-favicon" 
                                         alt="Favicon" 
                                         style="width: 48px; height: 48px; object-fit: contain;">
                                @else
                                    <div id="default-preview-favicon" class="d-flex align-items-center justify-content-center">
                                        <i class="ti ti-world text-primary" style="font-size: 2.8rem;"></i>
                                    </div>
                                    <img src="" id="img-preview-favicon" alt="Favicon" class="d-none" style="width: 48px; height: 48px; object-fit: contain;">
                                @endif
                            </div>
                            <div class="mt-2 text-muted small font-monospace">
                                {{ $favicon ? basename($favicon) : 'Ícono Predeterminado del Sistema' }}
                            </div>
                            <div id="res-indicator-favicon" class="text-secondary small mt-1 font-monospace" style="font-size: 0.72rem;"></div>
                        </div>

                        <div class="col-12 col-md-8">
                            @can('editar-identidad')
                            <div class="upload-box p-3 text-center mb-3" onclick="document.getElementById('file_favicon').click();">
                                <input type="file" 
                                       id="file_favicon" 
                                       name="favicon" 
                                       class="d-none" 
                                       accept=".ico,.png,.svg" 
                                       onchange="previewFavicon(this)">
                                <i class="ti ti-upload fs-1 text-warning mb-1 d-block"></i>
                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">Subir nuevo Favicon</div>
                                <div class="text-secondary small">Formatos admitidos: .ICO, .PNG o .SVG (Máx. 1 MB)</div>
                                <div class="d-flex justify-content-center align-items-center gap-2 mt-2 flex-wrap">
                                    <span class="badge bg-danger-lt text-danger font-monospace" style="font-size: 0.70rem;">
                                        <i class="ti ti-ban me-1"></i> Bloqueo si &lt; 16 × 16 px
                                    </span>
                                    <span class="badge bg-warning-lt text-dark font-monospace" style="font-size: 0.70rem;">
                                        <i class="ti ti-check me-1"></i> Óptimo: 32 × 32 px / 64 × 64 px
                                    </span>
                                </div>
                            </div>
                            @endcan

                            @can('editar-identidad')
                            @if($favicon)
                                <div class="text-end">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm fw-bold shadow-sm" 
                                            onclick="confirmResetAsset('favicon', 'Favicon del Navegador')">
                                        <i class="ti ti-trash me-1"></i> Restablecer Favicon por defecto
                                    </button>
                                </div>
                            @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. BOTONES INFERIORES DE ACCIÓN --}}
            @can('editar-identidad')
            <div class="d-flex align-items-center justify-content-end gap-2 pb-5">
                <a href="{{ route('dashboard') }}" class="btn btn-danger fw-bold px-4 py-2 shadow-sm">
                    <i class="ti ti-arrow-back-up me-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm">
                    <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
                </button>
            </div>
            @endcan
        </form>

    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputAppName = document.getElementById('app_name');
        const tabMockTitle = document.getElementById('tab-mock-title');
        const navbarBrandPreview = document.getElementById('navbar-brand-preview-text');
        const loginBrandPreview = document.getElementById('login-brand-preview-text');

        // Actualizar vistas previas en tiempo real al escribir el nombre
        if (inputAppName) {
            inputAppName.addEventListener('input', function () {
                const val = this.value.trim() || 'Sistema Base';
                if (tabMockTitle) tabMockTitle.textContent = 'Inicio - ' + val;
                if (navbarBrandPreview) navbarBrandPreview.textContent = val;
                if (loginBrandPreview) loginBrandPreview.textContent = val;
            });
        }
    });

    // Vista previa dinámica para los logotipos con prohibición estricta de imágenes muy pequeñas
    function previewImage(input, target) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const isSvg = file.name.toLowerCase().endsWith('.svg');
            
            // Validar tamaño máx de archivo (2MB)
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo muy pesado',
                    text: 'El archivo excede el tamaño máximo permitido de 2 MB.',
                    confirmButtonText: 'Entendido'
                });
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.getElementById('img-preview-' + target);
                const defaultPreview = document.getElementById('default-preview-' + target);
                const resIndicator = document.getElementById('res-indicator-' + target);

                // Si es SVG, es gráfico vectorial con escalado perfecto e infinito
                if (isSvg) {
                    if (resIndicator) {
                        resIndicator.innerHTML = `<span class="badge bg-purple-lt text-purple me-1"><i class="ti ti-vector"></i> Gráfico Vectorial SVG</span> Escalado óptimo automático sin pérdida`;
                    }
                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                    }
                    if (defaultPreview) {
                        defaultPreview.classList.add('d-none');
                    }
                    return;
                }

                // Cargar imagen en memoria para inspeccionar resolución nativa
                const tempImg = new Image();
                tempImg.onload = function () {
                    const origW = this.naturalWidth;
                    const origH = this.naturalHeight;
                    const minW = target === 'login' ? 120 : 80;
                    const minH = target === 'login' ? 35 : 25;
                    const optW = target === 'login' ? 280 : 185;
                    const optH = target === 'login' ? 65 : 42;
                    const targetName = target === 'login' ? 'Inicio de Sesión' : 'Barra Lateral';

                    // Si la imagen es inferior al rango mínimo, PROHIBIR la carga
                    if (origW < minW || origH < minH) {
                        input.value = '';
                        if (img) img.classList.add('d-none');
                        if (defaultPreview) defaultPreview.classList.remove('d-none');
                        if (resIndicator) {
                            resIndicator.innerHTML = `<span class="text-danger fw-bold"><i class="ti ti-alert-triangle me-1"></i> Imagen rechazada: Muy pequeña (${origW}×${origH} px)</span>`;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Imagen demasiado pequeña',
                            html: `
                                <div class="text-start">
                                    <p class="mb-2">La imagen seleccionada tiene una resolución de <strong class="text-danger font-monospace">${origW} × ${origH} px</strong>.</p>
                                    <p class="mb-2">El <strong>rango mínimo requerido</strong> para el logo de <strong>${targetName}</strong> es de <strong class="text-primary font-monospace">${minW} × ${minH} px</strong>.</p>
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small><i class="ti ti-shield-x me-1"></i> <strong>Carga bloqueada:</strong> Una imagen tan pequeña se vería borrosa y pixelada. Por favor sube una imagen de mayor resolución.</small>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Entendido, elegir otra imagen'
                        });
                        return;
                    }

                    // Calcular dimensiones escaladas respetando la proporción
                    const scale = Math.min(optW / origW, optH / origH, 1);
                    const fitW = Math.round(origW * scale);
                    const fitH = Math.round(origH * scale);

                    if (resIndicator) {
                        const resizeNotice = (origW > optW * 2 || origH > optH * 2) 
                            ? `<span class="badge bg-azure-lt text-azure me-1"><i class="ti ti-arrows-minimize"></i> Redimensionado óptimo al guardar</span>`
                            : `<span class="badge bg-green-lt text-success me-1"><i class="ti ti-check"></i> Tamaño óptimo</span>`;

                        resIndicator.innerHTML = `${resizeNotice} Original: <strong>${origW}×${origH} px</strong> ➔ Visualización: <strong class="text-primary font-monospace">${fitW}×${fitH} px</strong> (Proporción 100% conservada)`;
                    }

                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('d-none');
                    }
                    if (defaultPreview) {
                        defaultPreview.classList.add('d-none');
                    }
                };
                tempImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Vista previa dinámica para el favicon con validación de tamaño mínimo
    function previewFavicon(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const isSvg = file.name.toLowerCase().endsWith('.svg');

            // Validar tamaño máx (1MB)
            if (file.size > 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo muy grande',
                    text: 'El favicon no debe superar 1 MB.',
                    confirmButtonText: 'Entendido'
                });
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const previewImg = document.getElementById('img-preview-favicon');
                const defaultFavicon = document.getElementById('default-preview-favicon');
                const mockTabImg = document.getElementById('tab-mock-favicon');
                const mockTabIcon = document.getElementById('tab-mock-icon');
                const resIndicator = document.getElementById('res-indicator-favicon');

                if (isSvg) {
                    if (resIndicator) {
                        resIndicator.innerHTML = `<span class="badge bg-purple-lt text-purple me-1"><i class="ti ti-vector"></i> Favicon Vectorial SVG</span> Nítido a cualquier resolución`;
                    }
                    if (previewImg) { previewImg.src = e.target.result; previewImg.classList.remove('d-none'); }
                    if (defaultFavicon) defaultFavicon.classList.add('d-none');
                    if (mockTabImg) { mockTabImg.src = e.target.result; mockTabImg.classList.remove('d-none'); }
                    if (mockTabIcon) mockTabIcon.classList.add('d-none');
                    return;
                }

                const tempImg = new Image();
                tempImg.onload = function () {
                    const origW = this.naturalWidth;
                    const origH = this.naturalHeight;

                    // Si el favicon es menor a 16x16 px, PROHIBIR la carga
                    if (origW < 16 || origH < 16) {
                        input.value = '';
                        if (previewImg) previewImg.classList.add('d-none');
                        if (defaultFavicon) defaultFavicon.classList.remove('d-none');
                        if (mockTabImg) mockTabImg.classList.add('d-none');
                        if (mockTabIcon) mockTabIcon.classList.remove('d-none');
                        if (resIndicator) {
                            resIndicator.innerHTML = `<span class="text-danger fw-bold"><i class="ti ti-alert-triangle me-1"></i> Favicon rechazado: Menor a 16×16 px</span>`;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Favicon demasiado pequeño',
                            html: `
                                <div class="text-start">
                                    <p class="mb-2">El archivo seleccionado tiene <strong class="text-danger font-monospace">${origW} × ${origH} px</strong>.</p>
                                    <p class="mb-2">El <strong>rango mínimo requerido</strong> para un favicon es de <strong class="text-primary font-monospace">16 × 16 px</strong>.</p>
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small><i class="ti ti-shield-x me-1"></i> Se ha bloqueado la carga para evitar distorsiones en las pestañas del navegador.</small>
                                    </div>
                                </div>
                            `,
                            confirmButtonText: 'Entendido'
                        });
                        return;
                    }

                    if (resIndicator) {
                        resIndicator.innerHTML = `<span class="badge bg-green-lt text-success me-1"><i class="ti ti-check"></i> Válido</span> Resolución: <strong>${origW}×${origH} px</strong> ➔ Optimizado para pestañas`;
                    }

                    if (previewImg) { previewImg.src = e.target.result; previewImg.classList.remove('d-none'); }
                    if (defaultFavicon) defaultFavicon.classList.add('d-none');
                    if (mockTabImg) { mockTabImg.src = e.target.result; mockTabImg.classList.remove('d-none'); }
                    if (mockTabIcon) mockTabIcon.classList.add('d-none');
                };
                tempImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    // Confirmación para restablecer recurso (Logotipo o Favicon)
    function confirmResetAsset(type, label) {
        Swal.fire({
            title: '¿Restablecer ' + label + '?',
            text: 'Se eliminará el archivo personalizado y se volverá a utilizar el elemento por defecto del sistema.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, restablecer',
            cancelButtonText: 'Cancelar',
            reverseButtons: false
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Restableciendo...',
                    text: 'Por favor espere un momento.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ url('configuracion/identidad/asset') }}/" + type, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Restablecido!',
                            text: data.message,
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo restablecer el recurso.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'Ocurrió un error al procesar la solicitud.',
                        confirmButtonText: 'Aceptar'
                    });
                });
            }
        });
    }
</script>
@endpush
