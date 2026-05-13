@extends('layouts.main')

@section('titulo', 'Reportería de Cultivos')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Reportería de Cultivos</h1>
        <p class="text-muted mb-0">Análisis productivo, disponibilidad e impacto económico por cultivo.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4">
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
                    <div class="col-md-5 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('reporteria.cultivos') }}" class="btn btn-outline-secondary">Limpiar</a>
                        <a href="{{ route('reporteria.cultivos.excel', request()->query()) }}" class="btn btn-success">Excel</a>
                        <a href="{{ route('reporteria.cultivos.pdf', request()->query()) }}" class="btn btn-danger">PDF</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Índice de Reportes por Cultivo</h5></div>
            <div class="card-body pt-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="25" {{ $perPage === 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage === 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage === 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <small class="text-muted text-nowrap">registros</small>
                        </div>

                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar cultivo, lote o código...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
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
                                        <a href="{{ route('reporte.cultivo.final', $cultivo['id']) }}" class="btn btn-info btn-sm me-1" title="Ver reporte">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </a>
                                        <a href="{{ route('reporteria.cultivos.show', $cultivo['id']) }}" class="btn btn-primary btn-sm me-1" title="Detalle de consumos por cultivo">
                                            <i class="fa-solid fa-list-check"></i>
                                        </a>
                                        <a href="{{ route('reporte.cultivo.historial', $cultivo['id']) }}" class="btn btn-secondary btn-sm" title="Historial de consumo">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
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