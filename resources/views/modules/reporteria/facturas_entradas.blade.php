@extends('layouts.main')

@section('titulo', 'Facturas de Entradas')

@section('contenido')
<main id="main" class="main">
    <style>
        .facturas-kpi {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
            min-height: 100%;
        }

        .facturas-kpi .card-body {
            padding: 1.1rem 1.25rem;
        }

        .facturas-kpi-label {
            display: block;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.45rem;
        }

        .facturas-kpi-value {
            margin: 0;
            font-size: clamp(1.55rem, 2vw, 2.1rem);
            line-height: 1.08;
        }

        .facturas-table table {
            min-width: 1120px;
        }

        .factura-path {
            max-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .facturas-total {
            white-space: nowrap;
        }

        .facturas-badge-anexo {
            font-size: 0.92rem;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .facturas-kpi .card-body {
                padding: 1rem;
            }
        }
    </style>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="fa-solid fa-paperclip me-2 text-primary"></i>
                    Facturas de Entradas
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card facturas-kpi">
                            <div class="card-body">
                                <small class="facturas-kpi-label text-muted fw-bold">Registros</small>
                                <h3 class="facturas-kpi-value">{{ agro_number($metricas['total_registros']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card facturas-kpi">
                            <div class="card-body">
                                <small class="facturas-kpi-label text-muted fw-bold">Inversión total</small>
                                <h3 class="facturas-kpi-value">{{ agro_number($metricas['total_inversion'], 2) }} Lps</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card facturas-kpi">
                            <div class="card-body">
                                <small class="facturas-kpi-label text-muted fw-bold">Con anexo</small>
                                <h3 class="facturas-kpi-value text-success">{{ agro_number($metricas['con_anexo']) }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card facturas-kpi">
                            <div class="card-body">
                                <small class="facturas-kpi-label text-muted fw-bold">Costo promedio</small>
                                <h3 class="facturas-kpi-value text-primary">{{ agro_number($metricas['costo_promedio'], 2) }} Lps</h3>
                            </div>
                        </div>
                    </div>
                </div>

                @if(! $tablaDisponible)
                    <div class="alert alert-warning mb-0">
                        La tabla de entradas legacy no está disponible en esta base, por lo que no se pueden consultar anexos todavía.
                    </div>
                @else
                    <form method="GET" action="{{ route('reporteria.facturas_entradas') }}" class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <select id="customPerPage" name="per_page" class="form-select form-select-sm" style="width:auto;">
                                    @foreach([10, 15, 20, 50, 100] as $size)
                                        <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">registros</small>
                            </div>

                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <span class="input-group-text bg-white">
                                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                </span>
                                <input
                                    type="text"
                                    name="q"
                                    class="form-control"
                                    placeholder="Buscar producto, código, proveedor o archivo..."
                                    value="{{ request('q') }}"
                                >
                            </div>

                            <select id="filtroBodega" name="bodega_id" class="form-select form-select-sm">
                                <option value="">Todas las bodegas</option>
                                @foreach($bodegas as $bodega)
                                    <option value="{{ $bodega->id }}" {{ (string) request('bodega_id') === (string) $bodega->id ? 'selected' : '' }}>{{ $bodega->nombre }}</option>
                                @endforeach
                            </select>

                            <select id="filtroAnexo" name="anexo" class="form-select form-select-sm">
                                <option value="">Todos los anexos</option>
                                <option value="con" {{ request('anexo') === 'con' ? 'selected' : '' }}>Con anexo</option>
                                <option value="sin" {{ request('anexo') === 'sin' ? 'selected' : '' }}>Sin anexo</option>
                            </select>

                            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-filter me-1"></i>
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('reporteria.facturas_entradas') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive border rounded facturas-table">
                        <table class="table table-hover w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Código</th>
                                    <th>Insumo</th>
                                    <th>Bodega</th>
                                    <th>Proveedor</th>
                                    <th>Unidad</th>
                                    <th>Cantidad</th>
                                    <th>Costo</th>
                                    <th>Total</th>
                                    <th>Anexo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entradas as $entrada)
                                    <tr>
                                        <td>{{ $entradas->firstItem() + $loop->index }}</td>
                                        <td>{{ $entrada->fecha_ingreso ? \Carbon\Carbon::parse($entrada->fecha_ingreso)->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $entrada->insumo->codigo ?? '-' }}</td>
                                        <td>{{ $entrada->insumo->nombre ?? 'Insumo eliminado' }}</td>
                                        <td>{{ $entrada->bodega->nombre ?? '-' }}</td>
                                        <td>{{ $entrada->proveedor ?: '-' }}</td>
                                        <td>{{ $entrada->insumo->unidad_medida ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-success facturas-badge-anexo">{{ agro_number((float) ($entrada->cantidad ?? $entrada->cantida ?? 0), 2) }}</span>
                                        </td>
                                        <td>L {{ agro_number((float) $entrada->costo_unitario, 2) }}</td>
                                        <td class="fw-bold facturas-total">L {{ agro_number(((float) ($entrada->cantidad ?? $entrada->cantida ?? 0)) * (float) $entrada->costo_unitario, 2) }}</td>
                                        <td>
                                            @if(! blank($entrada->factura))
                                                <div class="d-flex align-items-center gap-2">
                                                    <a href="{{ route('reporteria.facturas_entradas.show', $entrada) }}" class="btn btn-outline-primary btn-sm" target="_blank">Abrir</a>
                                                    <small class="text-muted factura-path" title="{{ $entrada->factura }}">{{ $entrada->factura }}</small>
                                                </div>
                                            @else
                                                <span class="badge bg-secondary">Sin anexo</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-4">No hay facturas de entradas para los filtros seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <small class="text-muted">
                            @if($entradas->total() > 0)
                                Mostrando {{ $entradas->firstItem() }}-{{ $entradas->lastItem() }} de {{ $entradas->total() }} registros | Hoja {{ $entradas->currentPage() }} de {{ $entradas->lastPage() }}
                            @else
                                No hay registros para mostrar.
                            @endif
                        </small>

                        @if($entradas->lastPage() > 1)
                            @php
                                $maxPaginasVisibles = 6;
                                $paginaActual = $entradas->currentPage();
                                $ultimaPagina = $entradas->lastPage();
                                $inicioPagina = max(1, $paginaActual - intdiv($maxPaginasVisibles - 1, 2));
                                $finPagina = min($ultimaPagina, $inicioPagina + $maxPaginasVisibles - 1);

                                if (($finPagina - $inicioPagina + 1) < $maxPaginasVisibles) {
                                    $inicioPagina = max(1, $finPagina - $maxPaginasVisibles + 1);
                                }
                            @endphp
                            <nav aria-label="Paginacion de facturas de entradas">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item {{ $entradas->onFirstPage() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $entradas->onFirstPage() ? '#' : $entradas->previousPageUrl() }}">Anterior</a>
                                    </li>

                                    @for($page = $inicioPagina; $page <= $finPagina; $page++)
                                        <li class="page-item {{ $page === $entradas->currentPage() ? 'active' : '' }}">
                                            <a class="page-link" href="{{ $entradas->url($page) }}">{{ $page }}</a>
                                        </li>
                                    @endfor

                                    <li class="page-item {{ $entradas->hasMorePages() ? '' : 'disabled' }}">
                                        <a class="page-link" href="{{ $entradas->hasMorePages() ? $entradas->nextPageUrl() : '#' }}">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const perPageSelect = document.getElementById("customPerPage");
    const filtroBodega = document.getElementById("filtroBodega");
    const filtroAnexo = document.getElementById("filtroAnexo");
    const formulario = perPageSelect?.closest("form");

    perPageSelect?.addEventListener("change", () => formulario?.submit());
    filtroBodega?.addEventListener("change", () => formulario?.submit());
    filtroAnexo?.addEventListener("change", () => formulario?.submit());
});
</script>
@endsection