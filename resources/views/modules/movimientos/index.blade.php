@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

<div class="card shadow-sm border-0">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold">
            <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>
            Historial de Movimientos
        </h5>
    </div>

    <div class="card-body">

        <div class="d-flex flex-wrap align-items-center mb-3 p-2 bg-white rounded shadow-sm gap-2 border">
            <form method="GET" action="{{ route('movimientos.index') }}" class="d-flex flex-wrap align-items-center gap-2 w-100">

                <div class="d-flex align-items-center gap-1 border-end pe-2">
                    <select name="perPage" id="perPage" class="form-select form-select-sm" style="width: 70px;">
                        <option value="5" {{ request('perPage')=='5'?'selected':'' }}>5</option>
                        <option value="10" {{ request('perPage')=='10' || !request('perPage')?'selected':'' }}>10</option>
                        <option value="20" {{ request('perPage')=='20'?'selected':'' }}>20</option>
                    </select>
                    <small class="text-muted" style="font-size: 0.75rem;">Registros</small>
                </div>

                <div class="input-group input-group-sm" style="width: 200px;">
                    <span class="input-group-text bg-light text-muted border-end-0">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="busqueda" name="search" class="form-control border-start-0" 
                           placeholder="Buscar..." value="{{ request('search') }}">
                </div>

                <select name="tipo" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">Todos</option>
                    <option value="ENTRADA" {{ request('tipo')=='ENTRADA'?'selected':'' }}>Entrada</option>
                    <option value="SALIDA" {{ request('tipo')=='SALIDA'?'selected':'' }}>Salida</option>
                    <option value="TRASLADO" {{ request('tipo')=='TRASLADO'?'selected':'' }}>Traslado</option>
                    <option value="AJUSTE" {{ request('tipo')=='AJUSTE'?'selected':'' }}>Ajuste</option>
                    <option value="CONSUMO" {{ request('tipo')=='CONSUMO'?'selected':'' }}>Consumo</option>
                </select>

                <div class="d-flex align-items-center gap-1">
                    <input type="date" name="desde" value="{{ request('desde') }}" 
                           class="form-control form-control-sm" style="width: 130px;">
                    <span class="text-muted small">-</span>
                    <input type="date" name="hasta" value="{{ request('hasta') }}" 
                           class="form-control form-control-sm" style="width: 130px;">
                </div>

                <div class="ms-auto d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa fa-filter"></i>
                    </button>
                    <a href="{{ route('movimientos.index') }}" class="btn btn-sm btn-outline-secondary" title="Limpiar">
                        <i class="fa-solid fa-rotate-right"></i>
                    </a>
                </div>

            </form>
        </div>

        <div class="table-responsive border rounded" style="overflow-x:auto;">
            <table class="table table-hover table-sm align-middle mb-0" style="min-width:1500px;" id="tablaMovimientos">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Lote</th>
                        <th>Stock</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                    <tr>
                        <td>{{ ($movimientos->firstItem() ?? 0) + $loop->index }}</td>
                        <td>{{ $mov->insumo->codigo ?? '-' }}</td>
                        <td>{{ $mov->insumo->nombre ?? '-' }}</td>
                        <td>
                            @php
                                $tipo = strtoupper($mov->tipo);
                                $esAumento = $mov->stock_actual > $mov->stock_anterior;
                            @endphp

                            @switch($tipo)
                                @case('ENTRADA')
                                    <span class="badge bg-success">ENTRADA</span>
                                    @break
                                @case('SALIDA')
                                @case('CONSUMO')
                                    <span class="badge bg-danger">{{ $tipo }}</span>
                                    @break
                                @case('AJUSTE')
                                    @if($esAumento)
                                        <span class="badge bg-success">AJUSTE (SUMA)</span>
                                    @else
                                        <span class="badge bg-danger">AJUSTE (RESTA)</span>
                                    @endif
                                    @break
                                @case('TRASLADO')
                                    <span class="badge bg-warning text-dark">TRASLADO</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">{{ $tipo }}</span>
                            @endswitch
                        </td>
                        <td>
                            @php
                                $colorClass = 'text-dark';
                                $signo = '';

                                if ($tipo === 'ENTRADA' || ($tipo === 'AJUSTE' && $esAumento)) {
                                    $colorClass = 'text-success';
                                    $signo = '+';
                                } elseif (in_array($tipo, ['SALIDA', 'CONSUMO']) || ($tipo === 'AJUSTE' && !$esAumento)) {
                                    $colorClass = 'text-danger';
                                    $signo = '-';
                                }
                            @endphp
                            <span class="fw-bold {{ $colorClass }}">
                                {{ $signo }}{{ agro_number($mov->cantidad, 2) }}
                            </span>
                        </td>
                        <td>{{ $mov->bodegaOrigen->nombre ?? '-' }}</td>
                        
                        <td class="text-center">
                            @php
                                $cultivoDestino = $mov->consumo?->cultivo ?? ($tipo === 'CONSUMO' ? ($consumoDestinoFallback ?? null) : null);
                            @endphp

                            @if($tipo === 'CONSUMO' && $cultivoDestino)
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-primary shadow-sm" style="font-size: 0.85rem; padding: 5px 10px;">
                                        <i class="fa-solid fa-seedling me-1"></i>
                                        Cultivo: {{ $cultivoDestino->nombre ?? 'Sin Cultivo' }}
                                    </span>
                                    <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                        {{ $cultivoDestino->lote->nombre ?? 'Sin lote' }}
                                    </small>
                                </div>
                            @elseif($tipo === 'ENTRADA' || $tipo === 'TRASLADO')
                                <span class="text-dark fw-bold">
                                    {{ $mov->bodegaDestino->nombre ?? 'La Pita Farms S.A' }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td><span class="badge border text-dark fw-normal">{{ $mov->numero_lote ?? '-' }}</span></td>
                        <td class="fw-bold text-end pe-3">{{ agro_number($mov->stock_actual, 2) }}</td>
                        <td class="text-muted small">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No se encontraron movimientos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movimientos->count() > 0)
            <div class="mt-3">
                @include('shared.table_pagination_footer', ['paginator' => $movimientos, 'ariaLabel' => 'Paginacion de historial de movimientos'])
            </div>
        @endif

    </div>
</div>

</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector('form[action="{{ route('movimientos.index') }}"]');
    const perPageSelect = document.getElementById("perPage");

    function initSelectBuscable(selector, placeholder) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
        const $el = jQuery(selector);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            theme: 'bootstrap-5',
            width: 'style',
            placeholder: placeholder || 'Seleccione...',
            minimumResultsForSearch: 0,
            allowClear: false
        });
    }

    initSelectBuscable('#perPage', 'Registros...');
    initSelectBuscable('select[name="tipo"]', 'Tipo...');

    if (form && perPageSelect) {
        perPageSelect.addEventListener("change", function () {
            form.submit();
        });
    }
});
</script>
@endsection