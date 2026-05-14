@extends('layouts.main')

@section('titulo', 'Reportería de Rentabilidad')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <div class="pagetitle">
        <h1>Reportería de Rentabilidad</h1>
        <p class="text-muted mb-0">Consolidado económico por cultivo usando ventas registradas y consumos reales.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 reporteria-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.rentabilidad') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Lote</label>
                        <select name="lote_id" id="lote_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}" {{ request('lote_id') == $lote->id ? 'selected' : '' }}>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cultivo</label>
                        <select name="cultivo_id" id="cultivo_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($cultivos as $cultivo)
                                <option value="{{ $cultivo->id }}" data-lote="{{ $cultivo->lotes_id }}" {{ request('cultivo_id') == $cultivo->id ? 'selected' : '' }}>
                                    {{ $cultivo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Fecha inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Fecha fin</label>
                        <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-control">
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>

                    <div class="col-12 reporteria-actions">
                        <a href="{{ route('reporteria.rentabilidad') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                </form>

                <div class="reporteria-filter-summary">
                    <div class="small text-muted mb-2">Los filtros de fecha se aplican asi:</div>
                    <div class="reporteria-filter-summary-badges">
                        <span class="badge text-bg-light">Inversión por fecha de consumo</span>
                        <span class="badge text-bg-light">Ingresos por fecha de factura</span>
                        <span class="badge text-bg-light">Producción y disponible por fecha de cosecha</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Cultivos</small><h3 class="reporteria-kpi-value">{{ agro_number($metricas['cultivos']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Inversión</small><h3 class="reporteria-kpi-value text-warning">{{ agro_number($metricas['inversion'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Ingresos</small><h3 class="reporteria-kpi-value text-primary">{{ agro_number($metricas['ingresos'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Utilidad</small><h3 class="reporteria-kpi-value {{ $metricas['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">{{ agro_number($metricas['utilidad'], 2) }} Lps</h3></div></div></div>
        </div>

        <div class="card shadow-sm border-0 reporteria-table-card">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Detalle económico por cultivo</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive reporteria-table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Cultivo</th><th>Lote</th><th>Estado</th><th>Producción</th><th>Disponible</th><th>Inversión</th><th>Ingresos</th><th>Utilidad</th><th>Margen</th></tr></thead>
                        <tbody>
                            @forelse($rentabilidad as $fila)
                                <tr>
                                    <td><a href="{{ route('reporteria.cultivos.show', $fila['id']) }}" class="fw-semibold text-decoration-none">{{ $fila['nombre'] }}</a></td>
                                    <td>{{ $fila['lote'] }}</td>
                                    <td>{{ $fila['estado'] }}</td>
                                    <td>{{ agro_number($fila['produccion'], 2) }}</td>
                                    <td>{{ agro_number($fila['disponible'], 2) }}</td>
                                    <td>{{ agro_number($fila['inversion'], 2) }} Lps</td>
                                    <td>{{ agro_number($fila['ingresos'], 2) }} Lps</td>
                                    <td class="fw-bold {{ $fila['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">{{ agro_number($fila['utilidad'], 2) }} Lps</td>
                                    <td>{{ $fila['margen'] !== null ? agro_number($fila['margen'], 2) . '%' : 'N/D' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No hay resultados de rentabilidad con los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const loteSelect = document.getElementById('lote_id');
    const cultivoSelect = document.getElementById('cultivo_id');

    if (!loteSelect || !cultivoSelect) {
        return;
    }

    const allOptions = Array.from(cultivoSelect.options).map(function (option) {
        return {
            value: option.value,
            text: option.text,
            lote: option.getAttribute('data-lote') || '',
            selected: option.selected,
        };
    });

    function renderCultivos() {
        const loteId = loteSelect.value;
        const selected = cultivoSelect.value;

        cultivoSelect.innerHTML = '';
        allOptions.forEach(function (item) {
            if (item.value === '' || loteId === '' || item.lote === loteId) {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.setAttribute('data-lote', item.lote);
                if (item.value === selected) {
                    option.selected = true;
                }
                cultivoSelect.appendChild(option);
            }
        });
    }

    loteSelect.addEventListener('change', function () {
        renderCultivos();
    });

    renderCultivos();
});
</script>
@endsection