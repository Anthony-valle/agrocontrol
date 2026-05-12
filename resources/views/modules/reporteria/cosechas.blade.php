@extends('layouts.main')

@section('titulo', 'Reportería de Cosechas')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Reportería de Cosechas</h1>
        <p class="text-muted mb-0">Consulta producción bruta, descarte, neta y disponibilidad por cultivo y período.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4">
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
                    <div class="col-12 d-flex gap-2">
                        <a href="{{ route('reporteria.cosechas.excel', request()->query()) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i>Excel
                        </a>
                        <a href="{{ route('reporteria.cosechas.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Registros</small>
                        <h3 class="mt-2 mb-0">{{ agro_number($totales['registros']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Producción Bruta</small>
                        <h3 class="mt-2 mb-0">{{ agro_number($totales['bruta'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Producción Neta</small>
                        <h3 class="mt-2 mb-0 text-success">{{ agro_number($totales['neta'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Rendimiento</small>
                        <h3 class="mt-2 mb-0 text-primary">{{ agro_number($totales['rendimiento'], 2) }}%</h3>
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
            </div>
        </div>
    </section>
</main>
@endsection