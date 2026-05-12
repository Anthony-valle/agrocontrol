@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

    <div class="card shadow-sm border-0">

        <!-- HEADER -->
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-bell text-danger me-2"></i>
                Alertas de Inventario
            </h5>
            @php
                $tipoAlerta = $tipoAlerta ?? request('tipo_alerta', 'todos');
                $rutaActual = request()->routeIs('reporteria.insumos_vencimiento')
                    ? route('reporteria.insumos_vencimiento')
                    : route('reporteria.insumos_stock_bajo');
            @endphp
        </div>

        <div class="card-body">

            <!-- CONTROLES -->
            <div class="d-flex flex-column flex-xxl-row justify-content-between align-items-stretch align-items-xxl-center mb-3 p-2 bg-light rounded shadow-sm gap-2">

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 grow">

                    <!-- REGISTROS -->
                    <div class="d-flex align-items-center gap-2 shrink-0">
                        <select id="customPerPage" class="form-select form-select-sm" style="width:auto;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <small class="text-muted">registros</small>
                    </div>

                    <!-- BUSCADOR -->
                    <div class="input-group input-group-sm grow" style="min-width: 220px;">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="inputBusqueda" class="form-control" placeholder="Buscar insumo...">
                    </div>

                    <!-- FILTRO TIPO ALERTA -->
                    <form method="GET" action="{{ $rutaActual }}" class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-center mb-0 w-100 w-lg-auto">
                        <select name="tipo_alerta" id="filtroTipo" class="form-select form-select-sm" style="min-width: 220px;" onchange="this.form.submit()">
                            <option value="todos"        {{ $tipoAlerta === 'todos'        ? 'selected' : '' }}>Todos</option>
                            <option value="vencimientos" {{ $tipoAlerta === 'vencimientos' ? 'selected' : '' }}>Solo vencimientos</option>
                            <option value="stock_bajo"   {{ $tipoAlerta === 'stock_bajo'   ? 'selected' : '' }}>Solo stock bajo</option>
                        </select>
                        <a href="{{ $rutaActual }}" class="btn btn-outline-secondary btn-sm shrink-0">Limpiar</a>
                    </form>

                </div>

                <!-- BOTONES DESCARGA -->
                <div class="d-flex flex-wrap gap-2 ms-0 ms-xxl-2">
                    <button type="button" class="btn btn-danger btn-sm" disabled title="Exportación no disponible en esta versión">
                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-success btn-sm" disabled title="Exportación no disponible en esta versión">
                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                    </button>
                </div>

            </div>

            <!-- TABLA -->
            <div class="table-responsive border rounded">

                <table class="table table-hover w-100 mb-0" id="tablaAlertas">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tipo alerta</th>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>Lote</th>
                            <th>Fecha vencimiento</th>
                            <th>Días restantes</th>
                            <th>Stock actual</th>
                            <th>Stock mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($filasAlerta as $fila)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if($fila->tipo === 'VENCIMIENTO')
                                    <span class="badge bg-danger">Vencimiento</span>
                                @else
                                    <span class="badge bg-warning text-dark">Stock bajo</span>
                                @endif
                            </td>
                            <td>{{ $fila->insumo_codigo }}</td>
                            <td>{{ $fila->insumo_nombre }}</td>
                            <td>{{ $fila->lote_codigo }}</td>
                            <td>{{ $fila->fecha_vencimiento }}</td>
                            <td>
                                @if($fila->dias_restantes !== '-')
                                    @if((int)$fila->dias_restantes < 0)
                                        <span class="badge bg-danger">Vencido</span>
                                    @elseif((int)$fila->dias_restantes <= 7)
                                        <span class="badge bg-danger">{{ $fila->dias_restantes }}d</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $fila->dias_restantes }}d</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($fila->stock_actual !== '-')
                                    <span class="badge bg-danger">{{ $fila->stock_actual }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $fila->stock_minimo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-3">Sin alertas para el filtro seleccionado</td></tr>
                    @endforelse
                    </tbody>
                </table>

            </div>

        </div>
    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla      = document.getElementById("tablaAlertas");
    const filas      = Array.from(tabla.tBodies[0].rows);
    const inputBusq  = document.getElementById("inputBusqueda");
    const perPageSel = document.getElementById("customPerPage");

    function mostrarFilas(filasVisibles) {
        filas.forEach(f => { f.style.display = "none"; });
        filasVisibles.forEach(f => { f.style.display = ""; });
    }

    function filtrarTabla() {
        const texto = inputBusq.value.toLowerCase();
        const filtradas = filas.filter(f =>
            Array.from(f.cells).some(c =>
                c.textContent.toLowerCase().includes(texto)
            )
        );

        mostrarFilas(filtradas.slice(0, parseInt(perPageSel.value)));
    }

    inputBusq.addEventListener("input", filtrarTabla);
    perPageSel.addEventListener("change", filtrarTabla);

    mostrarFilas(filas.slice(0, parseInt(perPageSel.value)));
});
</script>

@endsection
