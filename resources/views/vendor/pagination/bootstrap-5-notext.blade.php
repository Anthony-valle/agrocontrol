@if ($paginator->hasPages())
    @php
        $maxPaginasVisibles = 5;
        $paginaActual = $paginator->currentPage();
        $ultimaPagina = $paginator->lastPage();
        $inicioPagina = max(1, $paginaActual - intdiv($maxPaginasVisibles - 1, 2));
        $finPagina = min($ultimaPagina, $inicioPagina + $maxPaginasVisibles - 1);

        if (($finPagina - $inicioPagina + 1) < $maxPaginasVisibles) {
            $inicioPagina = max(1, $finPagina - $maxPaginasVisibles + 1);
        }
    @endphp
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div class="text-muted small">
            Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} registros
        </div>

        <nav aria-label="Paginacion" class="mt-0">
            <ul class="pagination mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                        <span class="page-link" aria-hidden="true">&lsaquo;</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&lsaquo;</a>
                    </li>
                @endif

                @if ($inicioPagina > 1)
                    <li class="page-item {{ $paginaActual === 1 ? 'active' : '' }}" @if($paginaActual === 1) aria-current="page" @endif>
                        @if ($paginaActual === 1)
                            <span class="page-link">1</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                        @endif
                    </li>

                    @if ($inicioPagina > 2)
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif
                @endif

                @for ($page = $inicioPagina; $page <= $finPagina; $page++)
                    <li class="page-item {{ $page === $paginaActual ? 'active' : '' }}" @if($page === $paginaActual) aria-current="page" @endif>
                        @if ($page === $paginaActual)
                            <span class="page-link">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        @endif
                    </li>
                @endfor

                @if ($finPagina < $ultimaPagina)
                    @if ($finPagina < ($ultimaPagina - 1))
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif

                    <li class="page-item {{ $paginaActual === $ultimaPagina ? 'active' : '' }}" @if($paginaActual === $ultimaPagina) aria-current="page" @endif>
                        @if ($paginaActual === $ultimaPagina)
                            <span class="page-link">{{ $ultimaPagina }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($ultimaPagina) }}">{{ $ultimaPagina }}</a>
                        @endif
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">&rsaquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente">
                        <span class="page-link" aria-hidden="true">&rsaquo;</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif