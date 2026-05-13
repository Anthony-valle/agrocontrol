@extends('layouts.main')

@section('titulo', 'Reportería de Inventario')

@section('contenido')
<main id="main" class="main">
    <style>
        .inventario-filter-card {
            border-radius: 1.25rem;
        }

        .inventario-panel-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
        }

        .inventario-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .inventario-kpi-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
            min-height: 100%;
        }

        .inventario-kpi-card .card-body {
            padding: 1.15rem 1.25rem;
        }

        .inventario-kpi-label {
            display: block;
            min-height: 2.5rem;
            margin-bottom: 0.55rem;
            font-size: 0.8rem;
            letter-spacing: 0.04em;
            line-height: 1.25;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .inventario-kpi-value {
            margin: 0;
            font-size: clamp(1.8rem, 2.2vw, 2.35rem);
            line-height: 1.08;
            word-break: break-word;
        }

        .inventario-kpi-value.is-money {
            font-size: clamp(1.5rem, 1.95vw, 2.05rem);
        }

        .inventario-table-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .inventario-table-responsive table {
            min-width: 720px;
        }

        .inventario-table-responsive.table-compact table {
            min-width: 520px;
        }

        @media (max-width: 767.98px) {
            .inventario-filter-card .card-body {
                padding: 1.15rem;
            }

            .inventario-panel-card .card-body {
                padding: 1rem;
            }

            .inventario-kpi-card .card-body {
                padding: 1rem;
            }

            .inventario-kpi-label {
                min-height: auto;
            }

            .inventario-kpi-value,
            .inventario-kpi-value.is-money {
                font-size: 1.75rem;
            }
        }
    </style>

    <div class="pagetitle">
        <h1>Reportería de Inventario</h1>
        <p class="text-muted mb-0">Monitorea stock actual, lotes por vencer, valor estimado y últimos movimientos.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 inventario-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.inventario') }}" class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label fw-bold">Sucursal</label>
                        <select name="sucursal_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" {{ request('sucursal_id') == $sucursal->id ? 'selected' : '' }}>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label fw-bold">Bodega</label>
                        <select name="bodega_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($bodegas as $bodega)
                                <option value="{{ $bodega->id }}" {{ request('bodega_id') == $bodega->id ? 'selected' : '' }}>{{ $bodega->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label fw-bold">Categoría</label>
                        <select name="categoria" class="form-select">
                            <option value="">Todas</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria }}" {{ request('categoria') == $categoria ? 'selected' : '' }}>{{ $categoria }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4 col-xl-2">
                        <label class="form-label fw-bold">Vencimiento</label>
                        <select name="estado_vencimiento" class="form-select">
                            <option value="">Todos</option>
                            <option value="vencido" {{ request('estado_vencimiento') === 'vencido' ? 'selected' : '' }}>Vencidos</option>
                            <option value="proximo" {{ request('estado_vencimiento') === 'proximo' ? 'selected' : '' }}>Próximos</option>
                            <option value="vigente" {{ request('estado_vencimiento') === 'vigente' ? 'selected' : '' }}>Vigentes</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 col-xl-1 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i></button>
                    </div>
                    <div class="col-12">
                        <div class="inventario-filter-actions">
                            <a href="{{ route('reporteria.inventario.excel', request()->query()) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </a>
                            <a href="{{ route('reporteria.inventario.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Lotes</small>
                        <h3 class="inventario-kpi-value">{{ agro_number($metricas['lotes']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Stock total</small>
                        <h3 class="inventario-kpi-value">{{ agro_number($metricas['stock_total'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Valor estimado</small>
                        <h3 class="inventario-kpi-value is-money">{{ agro_number($metricas['valor_total'], 2) }} Lps</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Stock bajo</small>
                        <h3 class="inventario-kpi-value text-warning">{{ agro_number($metricas['stock_bajo']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Vencidos</small>
                        <h3 class="inventario-kpi-value text-danger">{{ agro_number($metricas['vencidos']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card inventario-kpi-card">
                    <div class="card-body">
                        <small class="inventario-kpi-label text-muted fw-bold">Próximos 30 días</small>
                        <h3 class="inventario-kpi-value text-info">{{ agro_number($metricas['proximos']) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card inventario-panel-card h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title inventario-table-title mb-0">Stock por Lote</h5></div>
                    <div class="card-body pt-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                    <small class="text-muted text-nowrap">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width: 280px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar insumo, bodega o lote...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive inventario-table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Insumo</th>
                                        <th>Bodega</th>
                                        <th>Lote</th>
                                        <th>Stock</th>
                                        <th>Costo Prom.</th>
                                        <th>Vence</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inventarios as $item)
                                        @php
                                            $fechaVencimiento = $item->fecha_vencimiento ? \Carbon\Carbon::parse($item->fecha_vencimiento) : null;
                                            $vencePronto = $fechaVencimiento && $fechaVencimiento->isFuture() && $fechaVencimiento->diffInDays(now()) <= 30;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item->insumo->nombre ?? '-' }}</div>
                                                <small class="text-muted">{{ $item->insumo->categoria_nombre ?? 'Sin categoría' }}</small>
                                            </td>
                                            <td>
                                                <div>{{ $item->bodega->nombre ?? '-' }}</div>
                                                <small class="text-muted">{{ $item->bodega->sucursal->nombre ?? '-' }}</small>
                                            </td>
                                            <td>{{ $item->numero_lote ?: '-' }}</td>
                                            <td class="fw-bold {{ $item->insumo && $item->insumo->stock_minimo !== null && $item->stock_actual <= $item->insumo->stock_minimo ? 'text-warning' : '' }}">{{ agro_number($item->stock_actual, 2) }}</td>
                                            <td>{{ agro_number($item->costo_promedio, 2) }} Lps</td>
                                            <td>
                                                @if($fechaVencimiento)
                                                    <span class="{{ $fechaVencimiento->isPast() ? 'text-danger' : ($vencePronto ? 'text-warning' : '') }}">
                                                        {{ $fechaVencimiento->format('d/m/Y') }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">No hay inventario para los filtros seleccionados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                            <small class="text-muted">
                                Mostrando {{ $inventarios->firstItem() ?? 0 }} a {{ $inventarios->lastItem() ?? 0 }} de {{ $inventarios->total() }} lotes.
                            </small>
                            {{ $inventarios->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card inventario-panel-card mb-4">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title inventario-table-title mb-0">Resumen por Categoría</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive inventario-table-responsive table-compact">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Lotes</th>
                                        <th>Stock</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($resumenCategorias as $fila)
                                        <tr>
                                            <td>{{ $fila['categoria'] }}</td>
                                            <td>{{ $fila['lotes'] }}</td>
                                            <td>{{ agro_number($fila['stock'], 2) }}</td>
                                            <td>{{ agro_number($fila['valor'], 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin datos por categoría.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card inventario-panel-card">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title inventario-table-title mb-0">Últimos Movimientos</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive inventario-table-responsive table-compact">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Insumo</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movimientos as $movimiento)
                                        <tr>
                                            <td>{{ $movimiento->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $movimiento->tipo }}</td>
                                            <td>{{ $movimiento->insumo->nombre ?? '-' }}</td>
                                            <td>{{ agro_number($movimiento->cantidad, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin movimientos recientes.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBusqueda = document.getElementById('inputBusqueda');
    const perPageSelect = document.getElementById('customPerPage');
    const tabla = document.querySelector('.inventario-table-responsive table tbody');

    if (perPageSelect) {
        perPageSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    if (!inputBusqueda || !tabla) {
        return;
    }

    const filas = Array.from(tabla.querySelectorAll('tr'));

    inputBusqueda.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();

        filas.forEach(function (fila) {
            if (fila.children.length === 1) {
                return;
            }

            const texto = fila.innerText.toLowerCase();
            fila.style.display = termino === '' || texto.includes(termino) ? '' : 'none';
        });
    });
});
</script>
@endpush