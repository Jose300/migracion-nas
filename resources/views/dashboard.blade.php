@extends('layouts.app')

@section('title', 'Resumen general')

@section('content')
<div class="row row-deck row-cards">
    <!-- Welcome Card -->
    <div class="col-12">
        <div class="card card-md border-0 shadow-sm overflow-hidden">
            <div class="row g-0">
                <div class="col-8">
                    <div class="card-body">
                        <h2 class="h1 mb-3">¡Bienvenido de nuevo, {{ auth()->user()->name }}!</h2>
                        <p class="text-secondary">Sistema Base de administración. Gestiona los usuarios, roles y permisos de tu plataforma de manera centralizada.</p>
                        <div class="mt-4">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-primary">
                                <i class="ti ti-users me-2"></i>Gestionar Usuarios
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-4 d-none d-md-block text-center bg-primary-lt">
                    <div class="p-4">
                        <i class="ti ti-shield-check text-primary" style="font-size: 8rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats widgets -->
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Usuarios Registrados</div>
                </div>
                <div class="h1 mb-3">{{ \App\Models\User::count() }}</div>
                <div id="chart-users" class="chart-sm"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Roles Configurados</div>
                </div>
                <div class="h1 mb-3">{{ \Spatie\Permission\Models\Role::count() }}</div>
                <div id="chart-roles" class="chart-sm"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Permisos Totales</div>
                </div>
                <div class="h1 mb-3">{{ \Spatie\Permission\Models\Permission::count() }}</div>
                <div id="chart-permissions" class="chart-sm"></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Usuarios Activos</div>
                </div>
                <div class="h1 mb-3">{{ \App\Models\User::where('status', 'Activo')->count() }}</div>
                <div id="chart-active" class="chart-sm"></div>
            </div>
        </div>
    </div>

    <!-- Main Charts -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3 class="card-title">Resumen de Actividad del Sistema</h3>
                <div id="traffic-summary" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title">Actividades Recientes</h3>
            </div>
            <div class="card-body pt-2">
                <div class="list-group list-group-flush list-group-hoverable">
                    <div class="list-group-item px-0">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="badge bg-green"></span></div>
                            <div class="col text-truncate">
                                <span class="text-body d-block">Inicio de sesión exitoso</span>
                                <div class="d-block text-secondary text-truncate mt-n1">
                                    {{ auth()->user()->name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="list-group-item px-0">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="badge bg-blue"></span></div>
                            <div class="col text-truncate">
                                <span class="text-body d-block">Base de datos migrada</span>
                                <div class="d-block text-secondary text-truncate mt-n1">
                                    Configuración de roles completada
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sparklineOptions = {
            chart: { type: 'area', height: 40, sparkline: { enabled: true }, animations: { enabled: true } },
            stroke: { width: 2, curve: 'smooth' },
            fill: { opacity: 0.1 },
            tooltip: { enabled: false },
            colors: ['#206bc4']
        };

        new ApexCharts(document.querySelector("#chart-users"), {
            ...sparklineOptions,
            series: [{ data: [5, 10, 15, 12, 18, 22, 25, 30] }]
        }).render();

        new ApexCharts(document.querySelector("#chart-roles"), {
            ...sparklineOptions,
            colors: ['#2fb344'],
            series: [{ data: [2, 3, 3, 4, 4, 4, 4, 4] }]
        }).render();

        new ApexCharts(document.querySelector("#chart-permissions"), {
            ...sparklineOptions,
            colors: ['#f59f00'],
            series: [{ data: [4, 4, 4, 4, 4, 4, 4, 4] }]
        }).render();

        new ApexCharts(document.querySelector("#chart-active"), {
            ...sparklineOptions,
            colors: ['#206bc4'],
            series: [{ data: [1, 2, 2, 2, 2, 2, 2, 2] }]
        }).render();

        new ApexCharts(document.querySelector("#traffic-summary"), {
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
            colors: ['#206bc4'],
            series: [{ name: 'Accesos', data: [12, 18, 25, 30, 28, 35, 40] }],
            xaxis: { categories: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'] },
            grid: { strokeDashArray: 4 },
        }).render();
    });
</script>
@endpush
