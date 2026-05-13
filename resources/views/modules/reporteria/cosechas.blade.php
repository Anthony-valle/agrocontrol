@extends('layouts.main')

@section('titulo', 'Reportería de Cosechas')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <div class="pagetitle">
        <h1>Reportería de Cosechas</h1>
        <p class="text-muted mb-0">Consulta producción bruta, descarte, neta y disponibilidad por cultivo y período.</p>
    </div>

    <section class="section">
        @php
            $hayFiltrosActivos = $hayFiltros ?? collect($filtrosAplicados ?? [])->filter()->isNotEmpty();
        @endphp

        <div class="card shadow-sm border-0 mb-4 reporteria-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.cosechas') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Cultivo</label>
                        <select name="cultivo_id" class="form-select">
                            <option value="">Todos los cultivos</option>
                            @foreach($cultivos as $cultivo)
                                <option value="{{ $cultivo->id }}" {{ request('cultivo_id') == $cultivo->id ? 'selected' : '' }}>
                                    {{ $cultivo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Desde</label>
                        <input type="date" name="desde" value="{{ request('desde') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Hasta</label>
                        <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter me-1"></i> Filtrar
                        </button>
                        <a href="{{ route('reporteria.cosechas') }}" class="btn btn-outline-secondary">Limpiar</a>
                    </div>
                    <div class="col-12 reporteria-actions">
                        <a href="{{ $hayFiltrosActivos ? route('reporteria.cosechas.excel', request()->query()) : '#' }}" class="btn btn-success btn-sm {{ $hayFiltrosActivos ? '' : 'disabled' }}" {{ $hayFiltrosActivos ? '' : 'aria-disabled=true tabindex=-1' }}>
                            <i class="bi bi-file-earmark-excel me-1"></i>Excel
                        </a>
                        <a href="{{ $hayFiltrosActivos ? route('reporteria.cosechas.pdf', request()->query()) : '#' }}" class="btn btn-danger btn-sm {{ $hayFiltrosActivos ? '' : 'disabled' }}" {{ $hayFiltrosActivos ? '' : 'aria-disabled=true tabindex=-1' }}>
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </a>
                    </div>
                </form>

                @if($hayFiltrosActivos)
                    <div class="reporteria-filter-summary d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="fw-semibold text-dark">Filtros aplicados:</span>
                            @if(!empty($filtrosAplicados['cultivo']))
                                <span class="badge rounded-pill text-bg-light border">Cultivo: {{ $filtrosAplicados['cultivo'] }}</span>
                            @endif
                            @if(!empty($filtrosAplicados['desde']))
                                <span class="badge rounded-pill text-bg-light border">Desde: {{ $filtrosAplicados['desde'] }}</span>
                            @endif
                            @if(!empty($filtrosAplicados['hasta']))
                                <span class="badge rounded-pill text-bg-light border">Hasta: {{ $filtrosAplicados['hasta'] }}</span>
                            @endif
                        </div>
                        <span class="small text-muted">Mostrando {{ agro_number($totales['registros']) }} registros filtrados</span>
                    </div>
                @else
                    <div class="reporteria-empty-state">
                        <div class="reporteria-empty-state-title">Aun no hay resultados para mostrar</div>
                        <div class="text-muted small mb-0">Selecciona al menos un cultivo o un rango de fechas y luego presiona Filtrar.</div>
                    </div>
                @endif
            </div>
        </div>

        @if($hayFiltrosActivos)
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100 reporteria-kpi-card">
                        <div class="card-body">
                            <small class="reporteria-kpi-label">Registros</small>
                            <h3 class="reporteria-kpi-value">{{ agro_number($totales['registros']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 reporteria-kpi-card">
                        <div class="card-body">
                            <small class="reporteria-kpi-label">Producción Bruta</small>
                            <h3 class="reporteria-kpi-value">{{ agro_number($totales['bruta'], 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 reporteria-kpi-card">
                        <div class="card-body">
                            <small class="reporteria-kpi-label">Producción Neta</small>
                            <h3 class="reporteria-kpi-value text-success">{{ agro_number($totales['neta'], 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 reporteria-kpi-card">
                        <div class="card-body">
                            <small class="reporteria-kpi-label">Rendimiento</small>
                            <h3 class="reporteria-kpi-value text-primary">{{ agro_number($totales['rendimiento'], 2) }}%</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 pb-0">
                            <h5 class="card-title mb-0">Resumen por Cultivo</h5>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cultivo</th>
                                            <th>Lote</th>
                                            <th>Neta</th>
                                            <th>Descarte</th>
                                            <th>Rendimiento</th>
                                            <th>Última cosecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($resumenPorCultivo as $fila)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ $fila['cultivo'] }}</div>
                                                    <small class="text-muted">{{ $fila['registros'] }} registros · {{ $fila['unidad_medida'] ?? '-' }}</small>
                                                </td>
                                                <td>{{ $fila['lote'] }}</td>
                                                <td class="text-success fw-bold">{{ agro_number($fila['neta'], 2) }}</td>
                                                <td>{{ agro_number($fila['descarte'], 2) }}</td>
                                                <td>{{ agro_number($fila['rendimiento'], 2) }}%</td>
                                                <td>{{ $fila['ultima_fecha'] ? \Carbon\Carbon::parse($fila['ultima_fecha'])->format('d/m/Y') : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">No hay cosechas para los filtros seleccionados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-0 pb-0">
                            <h5 class="card-title mb-0">Comportamiento Mensual</h5>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Periodo</th>
                                            <th>Bruta</th>
                                            <th>Neta</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($resumenMensual as $fila)
                                            <tr>
                                                <td>{{ $fila['periodo'] }}</td>
                                                <td>{{ agro_number($fila['bruta'], 2) }}</td>
                                                <td class="fw-bold text-success">{{ agro_number($fila['neta'], 2) }}</td>
                                                <td>{{ agro_number($fila['rendimiento'], 2) }}%</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">Sin datos mensuales.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detalle de Cosechas</h5>
                    <span class="badge bg-light text-dark">Disponible: {{ agro_number($totales['disponible'], 2) }}</span>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cultivo</th>
                                    <th>Lote</th>
                                    <th>Bruta</th>
                                    <th>Descarte</th>
                                    <th>Neta</th>
                                    <th>Disponible</th>
                                    <th>Precio</th>
                                    <th>Ingreso</th>
                                    <th>Registrado por</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cosechas as $cosecha)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                                        <td>{{ $cosecha->cultivo->nombre ?? '-' }}</td>
                                        <td>{{ $cosecha->cultivo->lote->nombre ?? '-' }}</td>
                                        <td>{{ agro_number($cosecha->cantidad_bruta, 2) }} {{ $cosecha->unidad_medida }}</td>
                                        <td>{{ agro_number($cosecha->descarte, 2) }} {{ $cosecha->unidad_medida }}</td>
                                        <td class="text-success fw-bold">{{ agro_number($cosecha->cantidad_neta, 2) }} {{ $cosecha->unidad_medida }}</td>
                                        <td>{{ agro_number($cosecha->cantidad_disponible, 2) }} {{ $cosecha->unidad_medida }}</td>
                                        <td>{{ $cosecha->precio_venta_unitario !== null ? agro_number($cosecha->precio_venta_unitario, 2) . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $cosecha->precio_venta_unitario !== null ? agro_number($cosecha->cantidad_neta * $cosecha->precio_venta_unitario, 2) . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $cosecha->usuario->usuario ?? 'Sistema' }}</td>
                                        <td>{{ $cosecha->observaciones ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-5">No hay cosechas registradas para mostrar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($cosechas->count() > 0)
                        @include('shared.table_pagination_footer', ['paginator' => $cosechas, 'ariaLabel' => 'Paginacion de cosechas'])
                    @endif
                </div>
            </div>
        @endif
    </section>
</main>
@endsection