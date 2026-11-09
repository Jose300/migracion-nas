@if(isset($paginator) && method_exists($paginator, 'total'))
<div class="card-footer d-flex align-items-center justify-content-between py-3">
    <div class="text-secondary" style="font-size: 0.85rem;">
        Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} {{ $label ?? 'registros' }}
    </div>

    <div>
        <div class="d-inline-flex align-items-center border rounded bg-white shadow-none" style="border-color: #e5e7eb !important; padding: 2px;">
            {{-- Botón Anterior (<) --}}
            @if ($paginator->onFirstPage())
                <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px; font-size: 0.95rem; cursor: not-allowed; opacity: 0.35; user-select: none;">
                    &lsaquo;
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
                    &lsaquo;
                </a>
            @endif

            {{-- Números de Página --}}
            @php
                $lastPage = $paginator->lastPage();
                $currentPage = $paginator->currentPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
                if ($currentPage <= 3) {
                    $endPage = min(5, $lastPage);
                }
                if ($currentPage >= $lastPage - 2) {
                    $startPage = max(1, $lastPage - 4);
                }
            @endphp

            @if ($lastPage > 1)
                @if ($startPage > 1)
                    <a href="{{ $paginator->url(1) }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        1
                    </a>
                    @if ($startPage > 2)
                        <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 24px; height: 32px; font-size: 0.85rem; user-select: none;">...</span>
                    @endif
                @endif

                @for ($page = $startPage; $page <= $endPage; $page++)
                    @if ($page == $currentPage)
                        <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold rounded" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            {{ $page }}
                        </a>
                    @endif
                @endfor

                @if ($endPage < $lastPage)
                    @if ($endPage < $lastPage - 1)
                        <span class="d-inline-flex align-items-center justify-content-center text-muted" style="width: 24px; height: 32px; font-size: 0.85rem; user-select: none;">...</span>
                    @endif
                    <a href="{{ $paginator->url($lastPage) }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        {{ $lastPage }}
                    </a>
                @endif
            @else
                <span class="d-inline-flex align-items-center justify-content-center bg-primary text-white fw-bold rounded" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    1
                </span>
            @endif

            {{-- Botón Siguiente (>) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="d-inline-flex align-items-center justify-content-center text-secondary text-decoration-none rounded datatable-page-btn" style="width: 32px; height: 32px; font-size: 0.95rem;">
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
@endif
