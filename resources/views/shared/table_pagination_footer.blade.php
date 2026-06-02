@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Pagination\LengthAwarePaginator $paginator */
    $maxPaginasVisibles = $maxPaginasVisibles ?? 5;
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

                @if($inicioPagina > 1)
                    <li class="page-item {{ 1 === $paginaActual ? 'active' : '' }}" @if(1 === $paginaActual) aria-current="page" @endif>
                        @if(1 === $paginaActual)
                            <span class="page-link">1</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                        @endif
                    </li>

                    @if($inicioPagina > 2)
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif
                @endif

                @for($page = $inicioPagina; $page <= $finPagina; $page++)
                    <li class="page-item {{ $page === $paginaActual ? 'active' : '' }}" @if($page === $paginaActual) aria-current="page" @endif>
                        @if($page === $paginaActual)
                            <span class="page-link">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        @endif
                    </li>
                @endfor

                @if($finPagina < $ultimaPagina)
                    @if($finPagina < ($ultimaPagina - 1))
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif

                    <li class="page-item {{ $ultimaPagina === $paginaActual ? 'active' : '' }}" @if($ultimaPagina === $paginaActual) aria-current="page" @endif>
                        @if($ultimaPagina === $paginaActual)
                            <span class="page-link">{{ $ultimaPagina }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($ultimaPagina) }}">{{ $ultimaPagina }}</a>
                        @endif
                    </li>
                @endif

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}">Siguiente</a>
                </li>
            </ul>
        </nav>
    @endif
</div>