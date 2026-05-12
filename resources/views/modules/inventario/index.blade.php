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
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                <div class="d-flex align-items-center gap-3">

                    <!-- REGISTROS -->
                    <div class="d-flex align-items-center gap-2">
                        <select id="customPerPage" class="form-select form-select-sm" style="width:auto;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <small class="text-muted">registros</small>
                    </div>

                    <!-- BUSCADOR -->
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="inputBusqueda" class="form-control" placeholder="Buscar insumo...">
                    </div>

                    <!-- FILTRO BODEGA -->
                    <select id="filtroBodega" class="form-select form-select-sm">
                        <option value="">Todas las bodegas</option>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}"
                                {{ request('bodega_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->nombre }}
                            </option>
                        @endforeach
                    </select>

                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('reporteria.inventario.excel', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('reporteria.inventario.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </a>
                </div>
            </div>


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

                            <td>{{ $loop->iteration }}</td>
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

        </div>

    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaInventario");
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");

    function mostrarFilas(filasVisibles){
        filas.forEach(f => f.style.display = "none");
        filasVisibles.forEach(f => f.style.display = "");
    }

    function filtrarTabla(){
        const texto = inputBusqueda.value.toLowerCase();

        const filtradas = filas.filter(f =>
            Array.from(f.cells).some(c =>
                c.textContent.toLowerCase().includes(texto)
            )
        );

        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value)));
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", filtrarTabla);

    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value)));

    // FILTRO BODEGA
    document.getElementById("filtroBodega").addEventListener("change", function(){

        let bodega = this.value;
        let url = new URL(window.location.href);

        if(bodega){
            url.searchParams.set("bodega_id", bodega);
        }else{
            url.searchParams.delete("bodega_id");
        }

        window.location.href = url;
    });

});
</script>


@endsection

