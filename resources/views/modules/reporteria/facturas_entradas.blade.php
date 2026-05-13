@extends('layouts.main')

@section('titulo', 'Facturas de Entradas')

@section('contenido')
<main id="main" class="main">
    <style>
        .facturas-shell {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .facturas-header {
            padding: 1.1rem 1.35rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

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

        .facturas-total {
            white-space: nowrap;
        }

        .facturas-badge-anexo {
            font-size: 0.92rem;
            font-weight: 600;
        }

        .facturas-anexo-cell {
            min-width: 130px;
            text-align: center;
        }

        .facturas-anexo-cell .btn {
            white-space: nowrap;
        }

        .facturas-filtros {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            background: linear-gradient(180deg, #f8fbff 0%, #f4f7fb 100%);
            padding: 1rem;
        }

        .facturas-filtros-grid {
            display: grid;
            grid-template-columns: minmax(110px, 130px) minmax(260px, 1.35fr) repeat(4, minmax(150px, 1fr));
            gap: 0.85rem;
            align-items: end;
        }

        .facturas-filtros-grid > div {
            min-width: 0;
        }

        .facturas-filtro-buscar {
            grid-column: span 1;
        }

        .facturas-filtros-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .facturas-filtros .form-select,
        .facturas-filtros .form-control,
        .facturas-filtros .input-group-text {
            border-radius: 0.75rem;
        }

        .facturas-filtros .input-group-text {
            border-right: 0;
        }

        .facturas-filtros .input-group .form-control {
            border-left: 0;
        }

        .facturas-filtros-acciones {
            display: flex;
            gap: 0.65rem;
            align-items: end;
            justify-content: flex-end;
            grid-column: 1 / -1;
        }

        .facturas-btn-icon {
            min-width: 42px;
            min-height: 42px;
            border-radius: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .facturas-tabla-wrap {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            overflow: hidden;
            background: #fff;
        }

        @media (max-width: 767.98px) {
            .facturas-kpi .card-body {
                padding: 1rem;
            }
        }

        @media (max-width: 1399.98px) {
            .facturas-filtros-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .facturas-filtros-acciones {
                justify-content: flex-start;
            }
        }

        @media (max-width: 991.98px) {
            .facturas-filtros-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .facturas-filtros-acciones {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 575.98px) {
            .facturas-header {
                padding: 1rem;
            }

            .facturas-filtros-grid {
                grid-template-columns: 1fr;
            }

            .facturas-filtros-acciones {
                grid-column: auto;
                flex-direction: column;
                align-items: stretch;
            }

            .facturas-filtros-acciones .btn {
                width: 100%;
            }
        }
    </style>

    <section class="section">
        <div class="card shadow-sm border-0 facturas-shell">
            <div class="card-header bg-white d-flex justify-content-between align-items-center facturas-header">
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
                    <form method="GET" action="{{ route('reporteria.facturas_entradas') }}" class="facturas-filtros mb-4">
                        <div class="facturas-filtros-grid">
                            <div>
                                <label for="customPerPage" class="facturas-filtros-label">Registros</label>
                                <select id="customPerPage" name="per_page" class="form-select form-select-sm">
                                    @foreach([10, 15, 20, 50, 100] as $size)
                                        <option value="{{ $size }}" {{ (int) request('per_page', 15) === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="facturas-filtro-buscar">
                                <label for="facturasSearch" class="facturas-filtros-label">Buscar</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input
                                        id="facturasSearch"
                                        type="text"
                                        name="q"
                                        class="form-control"
                                        placeholder="Buscar producto, código, proveedor o archivo..."
                                        value="{{ request('q') }}"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="filtroBodega" class="facturas-filtros-label">Bodega</label>
                                <select id="filtroBodega" name="bodega_id" class="form-select form-select-sm">
                                    <option value="">Todas las bodegas</option>
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}" {{ (string) request('bodega_id') === (string) $bodega->id ? 'selected' : '' }}>{{ $bodega->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="filtroAnexo" class="facturas-filtros-label">Anexo</label>
                                <select id="filtroAnexo" name="anexo" class="form-select form-select-sm">
                                    <option value="">Todos los anexos</option>
                                    <option value="con" {{ request('anexo') === 'con' ? 'selected' : '' }}>Con anexo</option>
                                    <option value="sin" {{ request('anexo') === 'sin' ? 'selected' : '' }}>Sin anexo</option>
                                </select>
                            </div>

                            <div>
                                <label for="fechaDesde" class="facturas-filtros-label">Fecha desde</label>
                                <input id="fechaDesde" type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                            </div>

                            <div>
                                <label for="fechaHasta" class="facturas-filtros-label">Fecha hasta</label>
                                <input id="fechaHasta" type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                            </div>

                            <div class="facturas-filtros-acciones">
                                <button type="submit" class="btn btn-primary btn-sm facturas-btn-icon" title="Filtrar">
                                    <i class="fa-solid fa-filter"></i>
                                </button>
                                <a href="{{ route('reporteria.facturas_entradas') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive facturas-table facturas-tabla-wrap">
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
                                        <td class="facturas-anexo-cell">
                                            @if(! blank($entrada->factura))
                                                <a href="{{ route('reporteria.facturas_entradas.show', $entrada) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fa-regular fa-file-lines me-1"></i>Ver anexo
                                                </a>
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

                    @include('shared.table_pagination_footer', ['paginator' => $entradas, 'ariaLabel' => 'Paginacion de facturas de entradas'])
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
