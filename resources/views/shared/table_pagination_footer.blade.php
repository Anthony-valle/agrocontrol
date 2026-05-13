@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Pagination\LengthAwarePaginator $paginator */
    $maxPaginasVisibles = $maxPaginasVisibles ?? 6;
    $ariaLabel = $ariaLabel ?? 'Paginacion de tabla';
@endphp

<div class="tabla-paginada-footer">
    <small class="tabla-paginada-footer-info">
        @if($paginator->total() > 0)
            Mostrando {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} de {{ $paginator->total() }} registros | Hoja {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        @else
            No hay registros para mostrar.
        @endif
    </small>

    @if($paginator->lastPage() > 1)
        @php
            $paginaActual = $paginator->currentPage();
            $ultimaPagina = $paginator->lastPage();
            $inicioPagina = max(1, $paginaActual - intdiv($maxPaginasVisibles - 1, 2));
            $finPagina = min($ultimaPagina, $inicioPagina + $maxPaginasVisibles - 1);

            if (($finPagina - $inicioPagina + 1) < $maxPaginasVisibles) {
                $inicioPagina = max(1, $finPagina - $maxPaginasVisibles + 1);
            }
        @endphp
        <nav aria-label="{{ $ariaLabel }}">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}">Anterior</a>
                </li>

                @for($page = $inicioPagina; $page <= $finPagina; $page++)
                    <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                        <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endfor

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}">Siguiente</a>
                </li>
            </ul>
        </nav>
    @endif
</div>