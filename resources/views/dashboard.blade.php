@extends('layouts.app')

@section('title', 'Resumen general')

@section('content')
<div class="row row-deck row-cards">
    <!-- Welcome Card -->
    <div class="col-12">
        <div class="card card-md border-0 shadow-sm overflow-hidden">
            <div class="row g-0">
                <div class="col-12">
                    <div class="card-body py-4">
                        <h2 class="h1 mb-2">¡Bienvenido de nuevo, {{ auth()->user()->name }}!</h2>
                        <p class="text-secondary mb-0">Agencia Bolivariana para Actividades Espaciales (ABAE) &bull; Plataforma de Administración y Migración NAS.</p>
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

    <!-- Logo ABAE Destacado -->
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden text-center bg-white p-4 p-md-5">
            <div class="card-body py-4 d-flex flex-column align-items-center justify-content-center">
                <img src="{{ asset('images/logo-abae.png') }}" 
                     alt="ABAE - Agencia Bolivariana para Actividades Espaciales" 
                     class="img-fluid mb-3" 
                     style="max-height: 280px; width: auto; object-fit: contain;">
                <h2 class="text-dark fw-bold mb-1" style="letter-spacing: 1px;">
                    AGENCIA BOLIVARIANA PARA ACTIVIDADES ESPACIALES
                </h2>
                <span class="text-secondary fs-4">
                    Dirección de Aplicaciones Espaciales &bull; Plataforma Central de Migración NAS
                </span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sparklineOptions = {
            chart: {
                type: 'area',
                height: 40,
                sparkline: {
                    enabled: true
                },
                animations: {
                    enabled: true
                }
            },
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            fill: {
                opacity: 0.1
            },
            tooltip: {
                enabled: false
            },
            colors: ['#206bc4']
        };

        new ApexCharts(document.querySelector("#chart-users"), {
            ...sparklineOptions,
            series: [{
                data: [5, 10, 15, 12, 18, 22, 25, 30]
            }]
        }).render();

        new ApexCharts(document.querySelector("#chart-roles"), {
            ...sparklineOptions,
            colors: ['#2fb344'],
            series: [{
                data: [2, 3, 3, 4, 4, 4, 4, 4]
            }]
        }).render();

        new ApexCharts(document.querySelector("#chart-permissions"), {
            ...sparklineOptions,
            colors: ['#f59f00'],
            series: [{
                data: [4, 4, 4, 4, 4, 4, 4, 4]
            }]
        }).render();

        new ApexCharts(document.querySelector("#chart-active"), {
            ...sparklineOptions,
            colors: ['#206bc4'],
            series: [{
                data: [1, 2, 2, 2, 2, 2, 2, 2]
            }]
        }).render();
    });
</script>
@endpush