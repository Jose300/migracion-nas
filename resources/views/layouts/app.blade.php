<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>@yield('title', 'Dashboard') - {{ setting('app_name', config('app.name', 'Sistema Base')) }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $faviconAsset = setting('favicon');
    @endphp
    <link rel="shortcut icon" href="{{ $faviconAsset ? asset('storage/' . $faviconAsset) : asset('favicon.ico') }}" type="image/x-icon"/>
    
    <!-- CSS files -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        @import url('https://rsms.me/inter/inter.css');
        :root {
            --tblr-font-sans-serif: 'Inter var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        }
        body {
            font-feature-settings: "cv03", "cv04", "cv11";
        }

        /* Estructura base uniforme para opciones activas e inactivas del menú lateral */
        .navbar-vertical .navbar-nav .nav-link,
        .navbar-vertical .dropdown-menu .dropdown-item {
            margin-left: 0.5rem !important;
            margin-right: 0.5rem !important;
            width: calc(100% - 1rem) !important;
            border-radius: 6px !important;
            box-sizing: border-box !important;
            transition: background-color 0.15s ease, color 0.15s ease !important;
            transform: none !important;
        }

        /* Estilo visual exclusivo para la opción activa (sin alterar márgenes ni posición) */
        .navbar-vertical .navbar-nav .nav-item.active > .nav-link,
        .navbar-vertical .navbar-nav .nav-link.active,
        .navbar-vertical .dropdown-menu .dropdown-item.active {
            background-color: #e0f2fe !important;
            color: #0284c7 !important;
            font-weight: 600 !important;
        }

        /* Iconos del menú lateral: dimensiones fijas y alineación vertical estricta */
        .navbar-vertical .nav-link-icon,
        .navbar-vertical .dropdown-item .nav-link-icon {
            width: 1.5rem !important;
            min-width: 1.5rem !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-right: 0.5rem !important;
            transform: none !important;
            transition: none !important;
        }

        .navbar-vertical .nav-link .nav-link-icon i,
        .navbar-vertical .dropdown-item .nav-link-icon i {
            font-size: 1.15rem !important;
            vertical-align: middle !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transform: none !important;
            transition: none !important;
        }

        .navbar-vertical .nav-link:hover,
        .navbar-vertical .dropdown-item:hover {
            transform: none !important;
            transition: background-color 0.15s ease, color 0.15s ease !important;
        }

        /* Asegurar dimensiones estrictas y contención del logotipo en el navbar lateral */
        .navbar-vertical .navbar-brand {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            max-width: 100%;
            overflow: hidden;
        }
        .navbar-vertical .navbar-brand a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
        }

        /* Estándar del sistema para SweetAlert2: Botones de acción en verde (#2fb344) y cancelar en rojo (#d63939) */
        .swal2-actions {
            gap: 0.5rem;
        }
        .swal2-confirm {
            background-color: #2fb344 !important;
            border-color: #2fb344 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(47, 179, 68, 0.35) !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 0.55rem 1.25rem !important;
        }
        .swal2-confirm:hover,
        .swal2-confirm:focus {
            background-color: #28a03c !important;
            border-color: #28a03c !important;
            box-shadow: 0 2px 6px rgba(47, 179, 68, 0.5) !important;
        }
        .swal2-cancel {
            background-color: #d63939 !important;
            border-color: #d63939 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(214, 57, 57, 0.35) !important;
            font-weight: 600 !important;
            border-radius: 6px !important;
            padding: 0.55rem 1.25rem !important;
        }
        .swal2-cancel:hover,
        .swal2-cancel:focus {
            background-color: #bd2d2d !important;
            border-color: #bd2d2d !important;
            box-shadow: 0 2px 6px rgba(214, 57, 57, 0.5) !important;
        }

        /* Estándar del sistema: Botones de cerrar / cancelar en modales siempre en rojo (#d63939) */
        .modal-footer [data-bs-dismiss="modal"]:not(.btn-success) {
            background-color: #d63939 !important;
            border-color: #d63939 !important;
            color: #ffffff !important;
            font-weight: 600 !important;
        }
        .modal-footer [data-bs-dismiss="modal"]:not(.btn-success):hover,
        .modal-footer [data-bs-dismiss="modal"]:not(.btn-success):focus {
            background-color: #bd2d2d !important;
            border-color: #bd2d2d !important;
            color: #ffffff !important;
        }
    </style>
    @stack('css')
</head>
<body>
    <div class="page">
        <!-- Sidebar -->
        <aside class="navbar navbar-vertical navbar-expand-md bg-white">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-brand">
                    <a href="{{ url('/') }}">
                        @include('components.logo', ['type' => 'navbar'])
                    </a>
                </div>
                
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard') }}">
                                <span class="nav-link-icon">
                                    <i class="ti ti-home" style="color: #206bc4 !important;"></i>
                                </span>
                                <span class="nav-link-title">Inicio</span>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->is('perfil*') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('perfil.index') }}">
                                <span class="nav-link-icon">
                                    <i class="ti ti-user" style="color: #6366f1 !important;"></i>
                                </span>
                                <span class="nav-link-title">Mi Perfil</span>
                            </a>
                        </li>
                        @canany(['ver-usuarios', 'ver-roles', 'ver-permisos', 'ver-identidad', 'ver-auditoria', 'ver-logs', 'ver-sistema', 'ver-conexiones'])
                        <li class="nav-item dropdown {{ request()->is('configuracion*') ? 'active' : '' }}">
                            <a class="nav-link dropdown-toggle" href="#navbar-config" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                                <span class="nav-link-icon">
                                    <i class="ti ti-settings" style="color: #0ea5e9 !important;"></i>
                                </span>
                                <span class="nav-link-title">Configuración</span>
                            </a>
                            <div class="dropdown-menu {{ request()->is('configuracion*') ? 'show' : '' }}">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        @can('ver-usuarios')
                                        <a class="dropdown-item {{ request()->is('configuracion/usuarios*') ? 'active' : '' }}" href="{{ route('usuarios.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-users" style="color: #206bc4 !important;"></i>
                                            </span>
                                            Usuarios
                                        </a>
                                        @endcan
                                        
                                        @can('ver-roles')
                                        <a class="dropdown-item {{ request()->is('configuracion/roles*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-shield-lock" style="color: #8b5cf6 !important;"></i>
                                            </span>
                                            Roles
                                        </a>
                                        @endcan
                                        
                                        @can('ver-permisos')
                                        <a class="dropdown-item {{ request()->is('configuracion/permisos*') ? 'active' : '' }}" href="{{ route('permisos.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-key" style="color: #f59f00 !important;"></i>
                                            </span>
                                            Permisos
                                        </a>
                                        @endcan

                                        @can('ver-conexiones')
                                        <a class="dropdown-item {{ request()->is('configuracion/conexiones*') ? 'active' : '' }}" href="{{ route('conexiones.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-devices" style="color: #0ca678 !important;"></i>
                                            </span>
                                            Conexiones
                                        </a>
                                        @endcan

                                        @can('ver-auditoria')
                                        <a class="dropdown-item {{ request()->is('configuracion/auditoria*') ? 'active' : '' }}" href="{{ route('auditorias.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-history" style="color: #0ca678 !important;"></i>
                                            </span>
                                            Auditoría
                                        </a>
                                        @endcan

                                        @can('ver-logs')
                                        <a class="dropdown-item {{ request()->is('configuracion/log-errores*') ? 'active' : '' }}" href="{{ route('log-errores.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-bug" style="color: #d63939 !important;"></i>
                                            </span>
                                            Log de Errores
                                        </a>
                                        @endcan

                                        @can('ver-identidad')
                                        <a class="dropdown-item {{ request()->is('configuracion/identidad*') ? 'active' : '' }}" href="{{ route('identidad.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-palette" style="color: #d63384 !important;"></i>
                                            </span>
                                            Identidad del Sistema
                                        </a>
                                        @endcan

                                        @can('ver-sistema')
                                        <a class="dropdown-item {{ request()->is('configuracion/sistema*') ? 'active' : '' }}" href="{{ route('sistema.index') }}">
                                            <span class="nav-link-icon d-md-none d-lg-inline-block me-2">
                                                <i class="ti ti-info-circle" style="color: #0284c7 !important;"></i>
                                            </span>
                                            Info. Sistema
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endcanany
                    </ul>
                </div>
            </div>
        </aside>
        
        <div class="page-wrapper">
            <!-- Header -->
            <header class="navbar navbar-expand-md navbar-light d-none d-lg-flex d-print-none">
                <div class="container-xl">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbar-menu">
                        <div>
                            <form action="./" method="get" autocomplete="off" novalidate>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search"></i>
                                    </span>
                                    <input type="text" value="" class="form-control" placeholder="Buscar..." aria-label="Search in website">
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="navbar-nav flex-row ms-auto">
                        <div class="nav-item dropdown d-none d-md-flex me-3">
                            <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                                <i class="ti ti-bell fs-2"></i>
                                <span class="badge bg-red badge-notification"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Notificaciones</h3>
                                    </div>
                                    <div class="list-group list-group-flush list-group-hoverable">
                                        <div class="list-group-item">
                                            <div class="text-secondary">No hay notificaciones nuevas.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu" aria-expanded="false">
                                <div class="d-none d-xl-block pe-2 text-end">
                                    <div class="fw-bold text-uppercase">{{ auth()->user()->short_name }}</div>
                                    <div class="mt-1 small text-uppercase text-info fw-bold" style="font-size: 0.65rem;">
                                        {{ auth()->user()->getRoleNames()->first() ?? 'Usuario' }}
                                    </div>
                                </div>
                                <span class="avatar avatar-sm rounded-circle fw-bold text-info border border-info border-2 bg-white" 
                                      style="font-size: 0.7rem;">
                                    {{ auth()->user()->initials }}
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow shadow-sm border-0 p-3" style="min-width: 200px;">
                                <div class="text-center mb-3">
                                    <div class="fw-bold fs-3">{{ auth()->user()->name }}</div>
                                    <div class="text-secondary small">{{ auth()->user()->email }}</div>
                                    <div class="mt-2 text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                                        ROLE: {{ auth()->user()->getRoleNames()->first() ?? 'ADMINISTRADOR' }}
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('perfil.index') }}" class="dropdown-item py-2">
                                    <i class="ti ti-user text-primary me-2 fs-2"></i>
                                    <span class="fs-3">Mi Perfil</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <form action="{{ route('logout') }}" method="post" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item py-2">
                                        <i class="ti ti-power text-danger me-2 fs-2"></i>
                                        <span class="fs-3">Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                </div>
            </header>

            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>
            
            <footer class="footer footer-transparent d-print-none">
                <div class="container-xl">
                    <div class="row text-center align-items-center flex-row-reverse">
                        <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                            <ul class="list-inline list-inline-dots mb-0">
                                <li class="list-inline-item">
                                    Copyright &copy; {{ date('Y') }}
                                    <a href="." class="link-secondary">{{ setting('app_name', config('app.name', 'Sistema Base')) }}</a>.
                                    Todos los derechos reservados.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Libs JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('js')
    
    @if(request()->is('configuracion/usuarios*'))
        <script src="{{ asset('js/users.js') }}"></script>
    @endif
    @if(request()->is('configuracion/roles*'))
        <script src="{{ asset('js/roles.js') }}"></script>
    @endif

    <script>
        // Global SweetAlert Handler for Flash Messages
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: '¡Operación Exitosa!',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false,
                confirmButtonColor: '#206bc4'
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: '¡Error!',
                text: "{{ session('error') }}",
                confirmButtonColor: '#d63939'
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: '¡Atención!',
                text: "{{ session('warning') }}",
                confirmButtonColor: '#f59f00'
            });
        @endif

        @if(session('info') || session('status'))
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: "{{ session('info') ?? session('status') }}",
                confirmButtonColor: '#206bc4'
            });
        @endif
    </script>
</body>
</html>
