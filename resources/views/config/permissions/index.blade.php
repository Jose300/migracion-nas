@extends('layouts.app')

@section('title', 'Gestión de Permisos por Rol')

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title fw-bold text-dark">
                    Gestión de Permisos por Rol
                </h2>
                <div class="text-secondary mt-1 small">Configuración / Permisos</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Banner Informativo Rol Develop -->
        <div class="card bg-white border-0 shadow-sm mb-4" style="border-radius: 8px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-primary-lt text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="ti ti-info-circle fs-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="fw-bold fs-3 text-dark">Información</span>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                <i class="ti ti-crown me-1"></i> CONTROL TOTAL
                            </span>
                        </div>
                        <p class="text-secondary small mb-0" style="font-size: 0.85rem; line-height: 1.5;">
                            El rol Develop posee acceso y control total e irrevocable sobre todas las funciones y módulos del sistema. Su configuración se encuentra permanentemente bloqueada para garantizar la estabilidad e integridad de la plataforma.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Permisos por Rol -->
        <div class="card border-0 shadow-sm">
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-center justify-content-between">
                    <div class="col-auto d-flex align-items-center text-secondary small">
                        <span>Mostrar</span>
                        <select id="select-per-page" class="form-select form-select-sm mx-2" style="width: auto;">
                            <option value="10" {{ $permissions->perPage() == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $permissions->perPage() == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $permissions->perPage() == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $permissions->perPage() == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span>registros</span>
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm fw-semibold" id="btn-export-excel">
                            <i class="ti ti-file-spreadsheet me-1"></i> Exportar a Excel
                        </button>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search"></i>
                            </span>
                            <input type="text" id="filter-permissions" class="form-control form-control-sm" placeholder="Filtrar registros..." style="min-width: 220px;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap table-hover" id="permissions-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-bottom-0 text-start fw-bold ps-3 text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em; min-width: 280px;">
                                PERMISO DEL SISTEMA <i class="ti ti-arrows-sort text-muted ms-1"></i>
                            </th>
                            @foreach($roles as $role)
                            <th class="border-bottom-0 text-center fw-bold text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                {{ strtoupper($role->name) }} <i class="ti ti-arrows-sort text-muted ms-1"></i>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $moduleColors = [
                                'Mi Perfil' => 'cyan',
                                'Usuarios' => 'blue',
                                'Roles' => 'indigo',
                                'Permisos' => 'purple',
                                'Auditoría' => 'azure',
                                'Log de Errores' => 'danger',
                                'Sistema' => 'teal',
                                'Info. Sistema' => 'teal',
                                'Identidad del Sistema' => 'pink',
                            ];
                        @endphp

                        @forelse($permissions as $permission)
                        @php
                            $color = $moduleColors[$permission->module] ?? 'secondary';
                        @endphp
                        <tr class="permission-row">
                            <td class="text-start ps-3 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-{{ $color }}-lt text-{{ $color }} fw-bold text-center px-2 py-1" style="min-width: 80px;">
                                        {{ $permission->module ?? 'General' }}
                                    </span>
                                    <div>
                                        <div class="text-dark fw-bold permission-name" style="font-size: 0.88rem;">{{ $permission->name }}</div>
                                        <div class="text-secondary small mt-0 permission-desc" style="font-size: 0.78rem;">
                                            {{ $permission->description ?? 'Permiso para ' . $permission->name }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            @foreach($roles as $role)
                            @php
                                $isDevelop = strtolower($role->name) === 'develop';
                                $hasPermission = $isDevelop ? true : $role->hasPermissionTo($permission);
                            @endphp
                            <td class="text-center py-3">
                                @if($isDevelop)
                                    {{-- Rol Develop: Fijo en ON, bloqueado con candado dorado, no clickeable --}}
                                    <div class="d-inline-flex align-items-center gap-1" style="cursor: not-allowed;" title="El rol Develop posee control total permanente">
                                        <label class="form-check form-switch m-0 d-inline-block" style="pointer-events: none;">
                                            <input class="form-check-input" type="checkbox" checked disabled style="cursor: not-allowed; opacity: 1; background-color: var(--tblr-primary) !important; border-color: var(--tblr-primary) !important;">
                                        </label>
                                        <span class="small fw-bold text-primary ms-1" style="font-size: 0.78rem;">ON</span>
                                        <i class="ti ti-lock text-warning" style="font-size: 0.85rem;" title="Bloqueado permanentemente"></i>
                                    </div>
                                @else
                                    {{-- Otros roles: Switches interactivos --}}
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <label class="form-check form-switch m-0 d-inline-block">
                                            <input class="form-check-input permission-toggle" type="checkbox" 
                                                   data-role-id="{{ $role->id }}" 
                                                   data-permission-id="{{ $permission->id }}"
                                                   {{ $hasPermission ? 'checked' : '' }}>
                                        </label>
                                        <span class="form-check-label small fw-bold {{ $hasPermission ? 'text-primary' : 'text-secondary' }}" style="font-size: 0.78rem;">
                                            {{ $hasPermission ? 'ON' : 'OFF' }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($roles) + 1 }}" class="text-center py-4 text-secondary">
                                No se encontraron registros de permisos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex align-items-center justify-content-between py-3">
                <div class="text-secondary" style="font-size: 0.85rem;" id="datatable-info">
                    Mostrando {{ $permissions->firstItem() ?? 0 }} a {{ $permissions->lastItem() ?? 0 }} de {{ $permissions->total() }} registros
                </div>

                <div>
                    <div class="d-inline-flex align-items-center border rounded bg-white shadow-none" style="border-color: #e5e7eb !important; padding: 2px;">
                        {{-- Botón Anterior (<) --}}
                        @if ($permissions->onFirstPage())
                            <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; font-size: 0.95rem; cursor: not-allowed; opacity: 0.35; user-select: none;">
                                &lsaquo;
                            </span>
                        @else
                            <a href="{{ $permissions->previousPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
                                &lsaquo;
                            </a>
                        @endif

                        {{-- Números de Página --}}
                        @if ($permissions->lastPage() > 1)
                            @foreach ($permissions->getUrlRange(1, $permissions->lastPage()) as $page => $url)
                                @if ($page == $permissions->currentPage())
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

                        {{-- Botón Siguiente (>) --}}
                        @if ($permissions->hasMorePages())
                            <a href="{{ $permissions->nextPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
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

<!-- Toast de Notificación -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="permission-toast" class="toast align-items-center border-0 shadow-lg bg-white" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex p-2">
            <div class="toast-icon me-3 d-flex align-items-center justify-content-center bg-success-lt rounded-circle" style="width: 40px; height: 40px;">
                <i class="ti ti-check text-success fs-2"></i>
            </div>
            <div class="toast-body d-flex align-items-center fw-bold text-dark" id="toast-message" style="font-size: 0.95rem;">
                Permiso actualizado correctamente.
            </div>
            <button type="button" class="btn-close ms-auto me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="progress progress-sm">
            <div class="progress-bar bg-success" id="toast-progress" style="width: 100%"></div>
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

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.permission-toggle');
        const toastEl = document.getElementById('permission-toast');
        const toastMsg = document.getElementById('toast-message');
        const toast = new bootstrap.Toast(toastEl);

        // Actualizar paginación por página
        const perPageSelect = document.getElementById('select-per-page');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', this.value);
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });
        }

        // Filtro rápido de registros
        const searchInput = document.getElementById('filter-permissions');
        const datatableInfo = document.getElementById('datatable-info');
        const defaultInfoText = datatableInfo ? datatableInfo.textContent.trim() : '';

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('.permission-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (datatableInfo) {
                    if (query.length > 0) {
                        datatableInfo.textContent = `Mostrando 1 a ${visibleCount} de ${visibleCount} registros (filtrados)`;
                    } else {
                        datatableInfo.textContent = defaultInfoText;
                    }
                }
            });
        }

        // Exportar a Excel (CSV compatible con Excel con BOM UTF-8)
        const exportBtn = document.getElementById('btn-export-excel');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                const table = document.getElementById('permissions-table');
                let csv = [];
                
                // Encabezados
                const headerCells = table.querySelectorAll('thead th');
                let headerRow = [];
                headerCells.forEach(th => {
                    headerRow.push('"' + th.innerText.replace(/[\n\r]+/g, ' ').replace(/\s+/g, ' ').trim() + '"');
                });
                csv.push(headerRow.join(';'));

                // Filas visibles
                const rows = table.querySelectorAll('tbody tr.permission-row');
                rows.forEach(tr => {
                    if (tr.style.display !== 'none') {
                        let row = [];
                        
                        // Permiso y módulo
                        const permName = tr.querySelector('.permission-name')?.innerText.trim() || '';
                        const permDesc = tr.querySelector('.permission-desc')?.innerText.trim() || '';
                        row.push('"' + (permName + ' (' + permDesc + ')').replace(/"/g, '""') + '"');

                        // Estados por rol
                        const cells = tr.querySelectorAll('td');
                        for (let i = 1; i < cells.length; i++) {
                            const label = cells[i].innerText.replace(/[\n\r]+/g, ' ').trim();
                            const isChecked = cells[i].querySelector('input[type="checkbox"]')?.checked;
                            row.push('"' + (isChecked ? 'ON' : 'OFF') + '"');
                        }
                        csv.push(row.join(';'));
                    }
                });

                // Descargar archivo CSV con BOM
                const csvString = '\uFEFF' + csv.join('\r\n');
                const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.setAttribute('download', 'matriz_permisos_por_rol.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }

        // Manejo de switches interactivos para otros roles
        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const roleId = this.dataset.roleId;
                const permissionId = this.dataset.permissionId;
                const isChecked = this.checked;
                const label = this.closest('td').querySelector('.form-check-label');
                
                // Actualización visual inmediata
                if (label) {
                    label.textContent = isChecked ? 'ON' : 'OFF';
                    label.className = 'form-check-label small fw-bold ' + (isChecked ? 'text-primary' : 'text-secondary');
                }

                fetch("{{ route('permisos.toggle') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        role_id: roleId,
                        permission_id: permissionId
                    })
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    const iconBox = document.querySelector('.toast-icon');
                    const icon = iconBox.querySelector('i');
                    const progressBar = document.getElementById('toast-progress');

                    if (body.success) {
                        iconBox.className = 'toast-icon me-3 d-flex align-items-center justify-content-center bg-success-lt rounded-circle';
                        icon.className = 'ti ti-check text-success fs-2';
                        progressBar.className = 'progress-bar bg-success';
                        toastMsg.textContent = body.message;
                    } else {
                        iconBox.className = 'toast-icon me-3 d-flex align-items-center justify-content-center bg-danger-lt rounded-circle';
                        icon.className = 'ti ti-x text-danger fs-2';
                        progressBar.className = 'progress-bar bg-danger';
                        toastMsg.textContent = body.message || 'No se pudo actualizar el permiso.';
                        
                        // Revertir
                        this.checked = !isChecked;
                        if (label) {
                            label.textContent = !isChecked ? 'ON' : 'OFF';
                            label.className = 'form-check-label small fw-bold ' + (!isChecked ? 'text-primary' : 'text-secondary');
                        }
                    }
                    
                    progressBar.style.width = '100%';
                    toast.show();
                    
                    setTimeout(() => {
                        progressBar.style.transition = 'width 3s linear';
                        progressBar.style.width = '0%';
                    }, 100);
                })
                .catch(error => {
                    const iconBox = document.querySelector('.toast-icon');
                    const icon = iconBox.querySelector('i');
                    const progressBar = document.getElementById('toast-progress');

                    iconBox.className = 'toast-icon me-3 d-flex align-items-center justify-content-center bg-danger-lt rounded-circle';
                    icon.className = 'ti ti-alert-triangle text-danger fs-2';
                    progressBar.className = 'progress-bar bg-danger';
                    toastMsg.textContent = 'Error de conexión con el servidor.';
                    
                    this.checked = !isChecked;
                    if (label) {
                        label.textContent = !isChecked ? 'ON' : 'OFF';
                        label.className = 'form-check-label small fw-bold ' + (!isChecked ? 'text-primary' : 'text-secondary');
                    }
                    toast.show();
                });
            });
        });
    });
</script>
@endpush
