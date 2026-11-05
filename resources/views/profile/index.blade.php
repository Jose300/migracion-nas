@extends('layouts.app')

@section('title', 'Mi Perfil')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                    CUENTA & SEGURIDAD
                </div>
                <h2 class="page-title mt-1 d-flex align-items-center">
                    <i class="ti ti-user-circle me-2 text-primary" style="font-size: 1.6rem;"></i>Mi Perfil
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-check fs-2 me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-triangle fs-2 me-2"></i>
                    <div>
                        <strong>Ha ocurrido un error con los datos ingresados:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            $role = $user->getRoleNames()->first() ?? 'Develop';
            $roleUpper = strtoupper($role);

            $userPermissionsCount = $user->getAllPermissions()->count();
            $rolesConfig = [
                'Develop' => [
                    'area' => 'Soporte y Desarrollo',
                    'area_icon' => 'ti ti-code',
                    'access' => 'Acceso Total',
                    'access_icon' => 'ti ti-shield-check',
                    'modules' => 'Todos los Módulos Base',
                    'permissions' => $userPermissionsCount . ' Activos',
                ],
                'Admin' => [
                    'area' => 'Administración y Gestión',
                    'area_icon' => 'ti ti-briefcase',
                    'access' => 'Acceso Administrativo',
                    'access_icon' => 'ti ti-shield-check',
                    'modules' => 'Todos los Módulos Base',
                    'permissions' => $userPermissionsCount . ' Activos',
                ],

                'Usuario' => [
                    'area' => 'Operativo / Estándar',
                    'area_icon' => 'ti ti-user',
                    'access' => 'Acceso Básico',
                    'access_icon' => 'ti ti-shield',
                    'modules' => 'Módulos Asignados',
                    'permissions' => $userPermissionsCount . ' Activos',
                ],
            ];

            $currentRoleConfig = $rolesConfig[$role] ?? $rolesConfig['Develop'];
        @endphp

        <div class="row row-cards">
            <!-- Summary card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm text-center p-3 p-md-4">
                    <div class="card-body p-0">
                        <div class="mb-3">
                            <span class="avatar avatar-xl rounded-circle fw-bold text-white bg-primary mx-auto shadow-sm" style="font-size: 1.8rem; width: 84px; height: 84px; line-height: 84px;">
                                {{ $user->initials }}
                            </span>
                        </div>
                        <h3 class="card-title mb-1 fw-bold fs-2 text-dark">{{ $user->name }}</h3>
                        <p class="text-secondary small mb-3">{{ $user->email }}</p>
                        
                        <div class="d-flex justify-content-center mb-3">
                            <span class="badge bg-blue-lt text-blue text-uppercase px-3 py-1 fw-bold" style="font-size: 0.8rem;">
                                <i class="ti ti-shield me-1"></i> {{ $roleUpper }}
                            </span>
                        </div>

                        <hr class="my-3 opacity-50">

                        <div class="text-start">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Nombres:</span>
                                <span class="fw-bold text-dark" style="font-size: 0.875rem;">{{ $user->first_name ?? $user->name }}</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Apellidos:</span>
                                <span class="fw-bold text-dark" style="font-size: 0.875rem;">{{ $user->last_name ?? '-' }}</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Área / Especialidad:</span>
                                <span class="badge bg-cyan-lt text-cyan fw-bold px-2 py-1">
                                    <i class="{{ $currentRoleConfig['area_icon'] }} me-1"></i>{{ $currentRoleConfig['area'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Nivel de Acceso:</span>
                                <span class="badge bg-blue-lt text-blue fw-bold px-2 py-1">
                                    <i class="{{ $currentRoleConfig['access_icon'] }} me-1"></i>{{ $currentRoleConfig['access'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Módulos:</span>
                                <span class="badge bg-purple-lt text-purple fw-bold px-2 py-1">
                                    {{ $currentRoleConfig['modules'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-secondary small">Permisos del Sistema:</span>
                                <span class="badge bg-green-lt text-green fw-bold px-2 py-1">
                                    {{ $currentRoleConfig['permissions'] }}
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Estado de Cuenta:</span>
                                @if(($user->status ?? 'Activo') === 'Activo')
                                    <span class="badge bg-green-lt text-green fw-bold px-2 py-1 d-inline-flex align-items-center">
                                        <span class="badge-dot bg-success me-1" style="width: 6px; height: 6px; border-radius: 50%; display: inline-block;"></span> Activo
                                    </span>
                                @else
                                    <span class="badge bg-danger-lt text-danger fw-bold px-2 py-1 d-inline-flex align-items-center">
                                        <span class="badge-dot bg-danger me-1" style="width: 6px; height: 6px; border-radius: 50%; display: inline-block;"></span> Inactivo
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit profile form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h3 class="card-title fw-bold">
                            <i class="ti ti-edit me-2 text-primary"></i>Editar Información
                        </h3>
                    </div>
                    <form action="{{ route('perfil.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="card-body">
                            <h4 class="text-uppercase text-secondary fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                Datos Personales
                            </h4>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label required">Nombres</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user"></i>
                                        </span>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $user->first_name) }}" placeholder="Ej: Jose Alexander" required>
                                    </div>
                                    @error('first_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label required">Apellidos</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user"></i>
                                        </span>
                                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $user->last_name) }}" placeholder="Ej: Perera Guevara" required>
                                    </div>
                                    @error('last_name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label required">Correo Electrónico</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail"></i>
                                        </span>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" placeholder="correo@ejemplo.com" required>
                                    </div>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Cód. País</label>
                                    <div class="input-group">
                                        @php
                                            $selectedCode = old('country_code', $user->country_code ?? '+58');
                                            $selectedIso = \App\Helpers\PhoneHelper::getCountryCodes()[$selectedCode]['iso'] ?? 've';
                                        @endphp
                                        <span class="input-group-text bg-white px-2">
                                            <span class="flag flag-xs flag-country-{{ $selectedIso }}" id="profile-flag-icon" style="width: 1.25rem; height: 0.9rem;"></span>
                                        </span>
                                        <select name="country_code" id="profile-country-code" class="form-select @error('country_code') is-invalid @enderror">
                                            @foreach(\App\Helpers\PhoneHelper::getCountryCodes() as $code => $country)
                                                <option value="{{ $code }}" data-iso="{{ $country['iso'] }}" {{ $selectedCode == $code ? 'selected' : '' }}>
                                                    {{ $code }} ({{ $country['name'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('country_code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Cód. Área</label>
                                    <select name="area_code" id="profile-area-code" class="form-select @error('area_code') is-invalid @enderror">
                                        <option value="">-- Seleccionar --</option>
                                        @php
                                            $currentCountry = old('country_code', $user->country_code ?? '+58');
                                            $areaCodes = \App\Helpers\PhoneHelper::getAreaCodes()[$currentCountry] ?? [];
                                            $currentArea = old('area_code', $user->area_code);
                                        @endphp
                                        @foreach($areaCodes as $val => $label)
                                            <option value="{{ $val }}" {{ $currentArea == $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                        @if($currentArea && !array_key_exists($currentArea, $areaCodes))
                                            <option value="{{ $currentArea }}" selected>{{ $currentArea }}</option>
                                        @endif
                                    </select>
                                    @error('area_code')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Número Telefónico</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-phone"></i>
                                        </span>
                                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="1234567">
                                    </div>
                                    @error('phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <hr class="my-4">

                            <h4 class="text-uppercase text-secondary fw-bold mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                Cambiar Contraseña <span class="text-muted font-normal">(Opcional)</span>
                            </h4>
                            <p class="text-secondary small mb-3">Deja estos campos en blanco si no deseas cambiar tu contraseña actual.</p>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Contraseña Actual</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-lock"></i>
                                        </span>
                                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Ingresa tu contraseña actual">
                                    </div>
                                    @error('current_password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Nueva Contraseña</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-key"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Mínimo 8 caracteres">
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirmar Nueva Contraseña</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-key"></i>
                                        </span>
                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repite la nueva contraseña">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-light text-end py-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="ti ti-device-floppy me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneAreaDictionary = @json(\App\Helpers\PhoneHelper::getAreaCodes());
        const countrySelect = document.getElementById('profile-country-code');
        const areaSelect = document.getElementById('profile-area-code');

        if (countrySelect && areaSelect) {
            const updateFlag = function() {
                const selectedOpt = countrySelect.options[countrySelect.selectedIndex];
                const iso = selectedOpt ? selectedOpt.getAttribute('data-iso') : 've';
                const flagIcon = document.getElementById('profile-flag-icon');
                if (flagIcon) {
                    flagIcon.className = 'flag flag-xs flag-country-' + (iso || 've');
                }
            };

            countrySelect.addEventListener('change', function() {
                updateFlag();
                const country = this.value;
                const areas = phoneAreaDictionary[country] || {};
                const currentVal = areaSelect.value;
                
                areaSelect.innerHTML = '<option value="">-- Seleccionar --</option>';
                
                for (const [val, label] of Object.entries(areas)) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = label;
                    if (val === currentVal) opt.selected = true;
                    areaSelect.appendChild(opt);
                }
            });
        }
    });
</script>
@endpush
