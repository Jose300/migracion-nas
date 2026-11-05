@extends('layouts.app')

@section('title', 'Gestión de Roles')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Lista de Roles
                </h2>
                <div class="text-secondary mt-1">Configuración / Roles</div>
            </div>
            <!-- Page title actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex">
                    <button class="btn btn-primary d-none d-sm-inline-block btn-add-role" data-url="{{ route('roles.store') }}">
                        <i class="ti ti-plus me-1"></i>
                        AGREGAR ROL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <div class="card border-0 shadow-sm">
            <div class="card-body border-bottom py-3">
                <div class="d-flex">
                    <div class="text-secondary">
                        Mostrar
                        <div class="mx-2 d-inline-block">
                            <input type="text" class="form-control form-control-sm" value="10" size="3">
                        </div>
                        registros
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                    <thead>
                        <tr>
                            <th class="w-1 border-bottom-0">ID</th>
                            <th class="border-bottom-0">NOMBRE</th>
                            <th class="border-bottom-0 text-center">FECHA DE CREACIÓN</th>
                            <th class="text-center border-bottom-0 w-1">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr>
                            <td><span class="text-secondary">{{ $role->id }}</span></td>
                            <td>
                                <div class="font-weight-medium text-capitalize">{{ $role->name }}</div>
                            </td>
                            <td class="text-center">{{ $role->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                @if(strtolower($role->name) === 'develop')
                                    <span class="badge bg-primary text-white fw-bold px-2 py-1" title="Rol del sistema protegido">
                                        <i class="ti ti-lock me-1"></i> SISTEMA 🔒
                                    </span>
                                @else
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-icon btn-primary me-2 btn-edit-role" 
                                                data-url="{{ route('roles.edit', $role->id) }}"
                                                data-update-url="{{ route('roles.update', $role->id) }}">
                                            <i class="ti ti-edit fs-2"></i>
                                        </button>
                                        <button class="btn btn-icon btn-danger btn-delete" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modal-delete"
                                                data-name="{{ $role->name }}"
                                                data-url="{{ route('roles.destroy', $role->id) }}">
                                            <i class="ti ti-trash fs-2"></i>
                                        </button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between py-3">
                <div class="text-secondary" style="font-size: 0.85rem;">
                    Mostrando {{ $roles->firstItem() ?? 0 }} a {{ $roles->lastItem() ?? 0 }} de {{ $roles->total() }} registros
                </div>
                <div>
                    <div class="d-inline-flex align-items-center border rounded bg-white shadow-none" style="border-color: #e5e7eb !important; padding: 2px;">
                        @if ($roles->onFirstPage())
                            <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; font-size: 0.95rem; cursor: not-allowed; opacity: 0.35; user-select: none;">
                                &lsaquo;
                            </span>
                        @else
                            <a href="{{ $roles->previousPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
                                &lsaquo;
                            </a>
                        @endif

                        @if ($roles->lastPage() > 1)
                            @foreach ($roles->getUrlRange(1, $roles->lastPage()) as $page => $url)
                                @if ($page == $roles->currentPage())
                                    <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold rounded" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @else
                            <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold rounded" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                1
                            </span>
                        @endif

                        @if ($roles->hasMorePages())
                            <a href="{{ $roles->nextPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
                                &rsaquo;
                            </a>
                        @else
                            <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; font-size: 0.95rem; cursor: not-allowed; opacity: 0.35; user-select: none;">
                                &rsaquo;
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Role Modal --}}
<div class="modal modal-blur fade" id="modal-role" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-role-title">Agregar Nuevo Rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="role-form" action="" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- General Validation Alert inside Modal --}}
                    <div id="role-alert-error" class="alert alert-danger d-none mb-3" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                            <div>
                                <h4 class="alert-title">Corrija los errores:</h4>
                                <ul id="role-error-list" class="mb-0 small"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Nombre del Rol</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej: Supervisor, Vendedor">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger ms-auto fw-bold" data-bs-dismiss="modal">CERRAR</button>
                    <button type="submit" class="btn btn-success fw-bold" id="btn-save-role">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
    .datatable-page-btn {
        transition: background-color 0.15s ease, color 0.15s ease;
    }
    .datatable-page-btn:hover {
        background-color: #f1f5f9;
        color: var(--tblr-primary) !important;
    }
</style>
@endpush