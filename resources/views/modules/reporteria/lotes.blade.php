@extends('layouts.main')

@section('titulo', 'Reportería de Lotes')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <div class="pagetitle">
        <h1>Reportería de Lotes</h1>
        <p class="text-muted mb-0">Resumen de área, cultivos y producción por lote.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 reporteria-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.lotes') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Sucursal</label>
                        <select name="sucursal_id" class="form-select">
                            <option value="">Todas</option>
                            @foreach($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" {{ request('sucursal_id') == $sucursal->id ? 'selected' : '' }}>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="Activo" {{ request('estado') === 'Activo' ? 'selected' : '' }}>Activo</option>
                            <option value="Inactivo" {{ request('estado') === 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-5 reporteria-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('reporteria.lotes') }}" class="btn btn-outline-secondary">Limpiar</a>
                        <a href="{{ route('reporteria.lotes.excel', request()->query()) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel</a>
                        <a href="{{ route('reporteria.lotes.pdf', request()->query()) }}" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Lotes</small><h3 class="reporteria-kpi-value">{{ agro_number($metricas['lotes']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Área total</small><h3 class="reporteria-kpi-value">{{ agro_number($metricas['area_total'], 2) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Cultivos</small><h3 class="reporteria-kpi-value">{{ agro_number($metricas['cultivos']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card h-100 reporteria-kpi-card"><div class="card-body"><small class="reporteria-kpi-label">Cosecha neta</small><h3 class="reporteria-kpi-value">{{ agro_number($metricas['cosecha_neta'], 2) }}</h3></div></div></div>
        </div>

        <div class="card shadow-sm border-0 reporteria-table-card">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Detalle por lote</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive reporteria-table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Lote</th>
                                <th>Sucursal</th>
                                <th>Estado</th>
                                <th>Área</th>
                                <th>Cultivos</th>
                                <th>Neta</th>
                                <th>Disponible</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $fila)
                                <tr>
                                    <td><div class="fw-semibold">{{ $fila['nombre'] }}</div><small class="text-muted">{{ $fila['codigo'] ?: 'Sin código' }}</small></td>
                                    <td>{{ $fila['sucursal'] }}</td>
                                    <td>{{ $fila['estado'] }}</td>
                                    <td>{{ agro_number($fila['area'], 2) }}</td>
                                    <td>{{ agro_number($fila['cultivos']) }}</td>
                                    <td>{{ agro_number($fila['cosecha_neta'], 2) }}</td>
                                    <td>{{ agro_number($fila['disponible'], 2) }}</td>
                                    <td><a href="{{ route('reporteria.lotes.show', $fila['id']) }}" class="btn btn-sm btn-outline-primary">Ver detalle</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No hay lotes para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
