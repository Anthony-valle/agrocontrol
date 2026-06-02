@extends('layouts.main')

@section('titulo', 'Reportería de Cultivos')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <style>
        .reporteria-shell .reporteria-cultivos-actions {
            display: inline-flex;
            align-items: center;
            flex-wrap: nowrap;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.3rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef5f3 100%);
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
        }

        .reporteria-shell .reporteria-action-btn {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.8rem;
            border: 0;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 0.45rem 1rem rgba(15, 23, 42, 0.14);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }

        .reporteria-shell .reporteria-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.7rem 1.2rem rgba(15, 23, 42, 0.18);
            filter: saturate(1.05);
            color: #fff;
        }

        .reporteria-shell .reporteria-action-btn:focus-visible {
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.95), 0 0 0 0.35rem rgba(22, 101, 52, 0.32);
        }

        .reporteria-shell .reporteria-action-btn.is-report {
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
        }

        .reporteria-shell .reporteria-action-btn.is-plan-real {
            background: linear-gradient(135deg, #14532d 0%, #15803d 100%);
        }

        .reporteria-shell .reporteria-action-btn.is-detail {
            background: linear-gradient(135deg, #1d4ed8 0%, #4338ca 100%);
        }

        .reporteria-shell .reporteria-action-btn.is-history {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
        }

        @media (max-width: 991.98px) {
            .reporteria-shell .reporteria-cultivos-actions {
                border-radius: 1rem;
                padding: 0.4rem;
            }

            .reporteria-shell .reporteria-action-btn {
                width: 2.1rem;
                height: 2.1rem;
                border-radius: 0.7rem;
            }
        }
    </style>
    <div class="pagetitle">
        <h1>Reportería de Cultivos</h1>
        <p class="text-muted mb-0">Análisis productivo, disponibilidad e impacto económico por cultivo.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 reporteria-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.cultivos') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Lote</label>
                        <select name="lote_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}" {{ request('lote_id') == $lote->id ? 'selected' : '' }}>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="Activo" {{ request('estado') === 'Activo' ? 'selected' : '' }}>Activo</option>
                            <option value="cerrado" {{ request('estado') === 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>
                    <div class="col-md-5 reporteria-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('reporteria.cultivos') }}" class="btn btn-outline-secondary">Limpiar</a>
                        <a href="{{ route('reporteria.cultivos.excel', request()->query()) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel</a>
                        <a href="{{ route('reporteria.cultivos.pdf', request()->query()) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 reporteria-table-card">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Índice de Reportes por Cultivo</h5></div>
            <div class="card-body pt-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3 agro-table-toolbar">
                    <div class="d-flex align-items-center gap-3 agro-table-toolbar-group">
                        <div class="d-flex align-items-center gap-2 agro-toolbar-records">
                            <select id="customPerPage" class="form-select form-select-sm agro-toolbar-select" style="width: auto;">
                                <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <small class="text-muted text-nowrap">registros</small>
                        </div>

                        <div class="input-group input-group-sm agro-toolbar-search" style="max-width: 260px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar cultivo, lote o código...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded reporteria-table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tablaReporteriaCultivos" style="min-width: 1300px;">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Lote</th>
                                <th>Estado</th>
                                <th>Siembra</th>
                                <th>Producción</th>
                                <th>Disponible</th>
                                <th>Inversión</th>
                                <th>Ingresos</th>
                                <th>Utilidad</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cultivos as $cultivo)
                                <tr>
                                    <td>{{ $cultivo['id'] }}</td>
                                    <td>{{ $cultivo['codigo'] ?: '-' }}</td>
                                    <td class="fw-semibold">{{ $cultivo['nombre'] }}</td>
                                    <td>{{ $cultivo['lote'] }}</td>
                                    <td>
                                        <span class="badge {{ $cultivo['estado'] === 'Activo' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $cultivo['estado'] }}
                                        </span>
                                    </td>
                                    <td>{{ $cultivo['fecha_siembra'] ? \Carbon\Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-' }}</td>
                                    <td>{{ agro_number($cultivo['produccion'], 2) }} {{ $cultivo['unidad_medida'] }}</td>
                                    <td>{{ agro_number($cultivo['disponible'], 2) }} {{ $cultivo['unidad_medida'] }}</td>
                                    <td>{{ agro_number($cultivo['inversion'], 2) }} Lps</td>
                                    <td>{{ agro_number($cultivo['ingresos'], 2) }} Lps</td>
                                    <td class="fw-bold {{ $cultivo['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">{{ agro_number($cultivo['utilidad'], 2) }} Lps</td>
                                    <td class="text-center text-nowrap">
                                        <div class="reporteria-cultivos-actions">
                                        <a href="{{ route('reporte.cultivo.final', $cultivo['id']) }}" class="reporteria-action-btn is-report" title="Ver reporte" aria-label="Ver reporte del cultivo" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Ver reporte final">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </a>
                                        <a href="{{ route('reporte.cultivo.plan-real-semanal', $cultivo['id']) }}" class="reporteria-action-btn is-plan-real" title="Comparacion semanal de insumos" aria-label="Abrir comparacion semanal de cantidad de insumos entre plan y real" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Comparacion semanal de insumos">
                                            <i class="fa-solid fa-scale-balanced"></i>
                                        </a>
                                        <a href="{{ route('reporteria.cultivos.show', $cultivo['id']) }}" class="reporteria-action-btn is-detail" title="Detalle de consumos por cultivo" aria-label="Ver detalle de consumos por cultivo" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Detalle de consumos">
                                            <i class="fa-solid fa-list-check"></i>
                                        </a>
                                        <a href="{{ route('reporte.cultivo.historial', $cultivo['id']) }}" class="reporteria-action-btn is-history" title="Historial de consumo" aria-label="Ver historial de consumo" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Historial de consumo">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="text-center text-muted py-4">No hay cultivos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                    <small class="text-muted">
                        Mostrando {{ $cultivos->firstItem() ?? 0 }} a {{ $cultivos->lastItem() ?? 0 }} de {{ $cultivos->total() }} cultivos.
                    </small>
                    {{ $cultivos->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
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
    const tabla = document.querySelector('#tablaReporteriaCultivos tbody');
    const tooltipElements = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));

    if (window.bootstrap && window.bootstrap.Tooltip) {
        tooltipElements.forEach(function (element) {
            new window.bootstrap.Tooltip(element);
        });
    }

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