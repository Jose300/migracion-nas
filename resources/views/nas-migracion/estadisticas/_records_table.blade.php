<div class="table-responsive">
    <table class="table table-vcenter table-striped card-table">
        <thead class="table-light">
            <tr>
                <th style="width: 50px;">#</th>
                <th>Responsable</th>
                <th>Fecha</th>
                <th>Satélite</th>
                <th>ID del Archivo</th>
                <th class="text-end">Tamaño (GB)</th>
                <th>Observaciones</th>
                @can('crear-estadisticas-nas')
                <th class="text-end" style="width: 70px;">Acción</th>
                @endcan
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $row)
            <tr>
                <td class="text-secondary font-monospace">
                    {{ $records->firstItem() + $index }}
                </td>
                <td class="fw-bold text-dark">
                    @if(strtolower($row->responsable) === 'bot_pss')
                        <div class="d-flex align-items-center">
                            <span class="badge bg-purple-lt text-purple fw-bold me-1">bot_pss</span>
                            @if($row->user)
                                <span class="badge bg-light text-dark border small fw-normal" title="Operador que ejecutó la tarea">
                                    <i class="ti ti-user me-1 text-primary"></i>{{ $row->user->name }}
                                </span>
                            @endif
                        </div>
                    @else
                        {{ $row->responsable }}
                    @endif
                </td>
                <td class="text-secondary small">
                    <i class="ti ti-calendar me-1"></i>
                    {{ is_string($row->fecha) ? \Carbon\Carbon::parse($row->fecha)->format('d/m/Y') : $row->fecha->format('d/m/Y') }}
                </td>
                <td>
                    <span class="badge {{ $row->satelite === 'VRSS-1' ? 'bg-info-lt' : 'bg-teal-lt' }} font-monospace fw-bold">
                        {{ $row->satelite }}
                    </span>
                </td>
                <td>
                    <code class="text-primary fw-bold">{{ $row->archivo_id ?: 'N/A' }}</code>
                    @if($row->cantidad_archivos > 1)
                        <span class="badge bg-secondary-lt ms-1">{{ $row->cantidad_archivos }} archivos</span>
                    @endif
                </td>
                <td class="text-end">
                    <span class="fw-bold font-monospace text-dark">{{ number_format($row->tamano_gb, 4) }}</span>
                    <span class="text-muted small">GB</span>
                </td>
                <td class="text-secondary small text-truncate" style="max-width: 250px;" title="{{ $row->observaciones }}">
                    {{ $row->observaciones ?: '-' }}
                </td>
                @can('crear-estadisticas-nas')
                <td class="text-end">
                    <form action="{{ route('nas-migracion.estadisticas.destroy', $row) }}" method="POST" class="d-inline form-delete-stat">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                                class="btn btn-icon btn-sm btn-outline-danger btn-delete-stat"
                                data-name="{{ $row->responsable }} - {{ $row->tamano_gb }} GB"
                                title="Eliminar este registro">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </td>
                @endcan
            </tr>
            @empty
            <tr>
                <td colspan="{{ auth()->user() && auth()->user()->can('crear-estadisticas-nas') ? 8 : 7 }}" class="text-center py-4 text-secondary">
                    <i class="ti ti-Mood-empty fs-2 d-block mb-1 text-muted"></i>
                    No se encontraron registros coincidentes con los filtros seleccionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.datatable-pagination', ['paginator' => $records, 'label' => 'registros'])
