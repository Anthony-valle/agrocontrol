@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

    <div class="card shadow-sm border-0">

        <!-- HEADER -->
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>
                Inventario de Insumos
            </h5>
        </div>

        <div class="card-body">

            <!-- ALERTAS -->
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif


            <!-- CONTROLES -->
            <form method="GET" action="{{ route('inventarios.index') }}" class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3 agro-table-toolbar">

                <div class="d-flex align-items-center gap-3 agro-table-toolbar-group">

                    <div class="d-flex align-items-center gap-2 agro-toolbar-records">
                        <select id="customPerPage" name="per_page" class="form-select form-select-sm agro-toolbar-select" style="width:auto;">
                            <option value="5" {{ (int) $perPage === 5 ? 'selected' : '' }}>5</option>
                            <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ (int) $perPage === 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                        </select>
                        <small class="text-muted">registros</small>
                    </div>

                    <div class="input-group input-group-sm agro-toolbar-search">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input
                            type="text"
                            id="inputBusqueda"
                            name="q"
                            class="form-control"
                            placeholder="Buscar insumo, codigo o bodega..."
                            value="{{ $search }}"
                        >
                    </div>

                    <select id="filtroBodega" name="bodega_id" class="form-select form-select-sm">
                        <option value="">Todas las bodegas</option>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}"
                                {{ request('bodega_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->nombre }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-filter me-1"></i> 
                    </button>

                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('reporteria.inventario.excel', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('reporteria.inventario.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                </div>
            </form>


            <!-- TABLA -->
            <div class="table-responsive border rounded">

                <table class="table table-hover w-100 mb-0" id="tablaInventario">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>Categoria</th>
                            <th>Bodega</th>
                            <th>Unidad</th>
                            <th>Stock</th>
                            <th>Lote</th>
                            <th>Costo</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($inventarios as $item)

                        <tr>

                            <td>{{ $inventarios->firstItem() + $loop->index }}</td>
                            <td>{{ $item->insumo->codigo ?? '-' }}</td>
                            <td>{{ $item->insumo->nombre ?? '-' }}</td>
                            <td>{{ $item->categoria_resuelta ?? '-' }}</td>
                            <td>{{ $item->bodega->nombre ?? '-' }}</td>
                            <td>{{ $item->insumo->unidad_medida ?? '-' }}</td>
                            <!-- STOCK -->
                            <td>
                                @php $min = $item->insumo->stock_minimo ?? 0; @endphp

                                @if($item->stock_actual <= $min)
                                    <span class="badge bg-danger">{{ $item->stock_actual }}</span>
                                @else
                                    <span class="badge bg-success">{{ $item->stock_actual }}</span>
                                @endif
                            </td>

                            <td>{{ $item->numero_lote ?: '-' }}</td>

                            <!-- COSTO -->
                            <td>L {{ agro_number($item->costo_promedio, 2) }}</td>

                            <!-- TOTAL -->
                            <td>
                                L {{ agro_number($item->stock_actual * $item->costo_promedio, 2) }}
                            </td>

                        

                        </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            <div id="inventarioPaginacionWrap">
                @include('shared.table_pagination_footer', ['paginator' => $inventarios, 'ariaLabel' => 'Paginacion de inventario'])
            </div>

        </div>

    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const perPageSelect = document.getElementById("customPerPage");
    const filtroBodega = document.getElementById("filtroBodega");
    const formulario = perPageSelect?.closest("form");

    perPageSelect?.addEventListener("change", () => formulario?.submit());
    filtroBodega?.addEventListener("change", () => formulario?.submit());

});
</script>


@endsection

