@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Lista de Usuarios
                </h2>
                <div class="text-secondary mt-1">Configuración / Usuarios</div>
            </div>
            <!-- Page title actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex">
                    <button class="btn btn-primary d-none d-sm-inline-block btn-add-user" data-url="{{ route('usuarios.store') }}">
                        <i class="ti ti-plus me-1"></i>
                        AGREGAR USUARIO
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
                            <th class="border-bottom-0">EMAIL</th>
                            <th class="border-bottom-0">TELÉFONO</th>
                            <th class="border-bottom-0">ROL</th>
                            <th class="border-bottom-0">ESTADO</th>
                            <th class="border-bottom-0">FECHA DE CREACIÓN</th>
                            <th class="text-center border-bottom-0 w-1">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td><span class="text-secondary">{{ $user->id }}</span></td>
                            <td>
                                <div class="d-flex py-1 align-items-center">
                                    <span class="avatar me-2" style="background-image: url('https://ui-avatars.com/api/?name={{ $user->name }}')"></span>
                                    <div class="flex-fill">
                                        <div class="font-weight-medium">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="text-secondary">{{ $user->full_phone }}</span></td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-blue-lt">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-{{ $user->status == 'Activo' ? 'green' : 'red' }}-lt">{{ $user->status }}</span>
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-icon btn-primary me-2 btn-edit-user" 
                                            data-url="{{ route('usuarios.edit', $user->id) }}"
                                            data-update-url="{{ route('usuarios.update', $user->id) }}">
                                        <i class="ti ti-edit fs-2"></i>
                                    </button>
                                    <button class="btn btn-icon btn-danger btn-delete" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modal-delete"
                                            data-name="{{ $user->name }}"
                                            data-url="{{ route('usuarios.destroy', $user->id) }}">
                                        <i class="ti ti-trash fs-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center">
                <div class="pagination m-0 ms-auto">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- User Modal --}}
<div class="modal modal-blur fade" id="modal-user" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-user-title">Agregar Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="user-form" action="" method="POST">
                @csrf
                <div class="modal-body">
                    {{-- General Validation Alert inside Modal --}}
                    <div id="user-alert-error" class="alert alert-danger d-none mb-3" role="alert">
                        <div class="d-flex">
                            <div><i class="ti ti-alert-triangle icon alert-icon"></i></div>
                            <div>
                                <h4 class="alert-title">Corrija los errores:</h4>
                                <ul id="user-error-list" class="mb-0 small"></ul>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Nombres</label>
                            <input type="text" name="first_name" class="form-control" placeholder="Ej: Juan Antonio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Apellidos</label>
                            <input type="text" name="last_name" class="form-control" placeholder="Ej: Pérez Rodríguez">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label required">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cód. País</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white px-2">
                                    <span class="flag flag-xs flag-country-ve" id="user-modal-flag-icon" style="width: 1.25rem; height: 0.9rem;"></span>
                                </span>
                                <select name="country_code" id="user-country-code" class="form-select">
                                    @foreach(\App\Helpers\PhoneHelper::getCountryCodes() as $code => $country)
                                        <option value="{{ $code }}" data-iso="{{ $country['iso'] }}" {{ $code == '+58' ? 'selected' : '' }}>
                                            {{ $code }} ({{ $country['name'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cód. Área</label>
                            <select name="area_code" id="user-area-code" class="form-select">
                                <option value="">-- Seleccionar --</option>
                                @foreach(\App\Helpers\PhoneHelper::getAreaCodes()['+58'] as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" placeholder="1234567">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" autocomplete="new-password">
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary toggle-password" title="Mostrar contraseña" data-bs-toggle="tooltip">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </span>
                            </div>
                            <small class="form-hint mt-1 d-none" id="password-hint">(Dejar en blanco para mantener la actual)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Contraseña</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password_confirmation" class="form-control">
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary toggle-password" title="Mostrar contraseña" data-bs-toggle="tooltip">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Rol de Usuario</label>
                            <select name="role" class="form-select text-capitalize">
                                <option value="" selected disabled>Seleccione un rol</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Estado</label>
                            <select name="status" id="user-status-select" class="form-select fw-bold">
                                <option value="Activo" class="text-success">Activo</option>
                                <option value="Inactivo" class="text-danger">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger ms-auto fw-bold" data-bs-dismiss="modal">CERRAR</button>
                    <button type="submit" class="btn btn-success fw-bold" id="btn-save-user">GUARDAR CAMBIOS</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
    window.phoneAreaDictionary = @json(\App\Helpers\PhoneHelper::getAreaCodes());
</script>
@endpush
