@extends('layouts.main')

@section('titulo', 'Detalle de Lote')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $lote->nombre }}</h1>
        <p class="text-muted mb-0">Sucursal: {{ $lote->sucursal->nombre ?? '-' }} · Código: {{ $lote->codigo ?: 'Sin código' }}</p>
        <div class="mt-2 d-flex gap-2">
            <a href="{{ route('reporteria.lotes.show.excel', $lote->id) }}" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('reporteria.lotes.show.pdf', $lote->id) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Cultivos</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['cultivos']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Activos</small><h3 class="mt-2 mb-0 text-success">{{ agro_number($metricas['activos']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Inversión</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['inversion'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Ventas</small><h3 class="mt-2 mb-0 text-primary">{{ agro_number($metricas['ventas'], 2) }} Lps</h3></div></div></div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Cultivos del lote</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Cultivo</th><th>Estado</th><th>Siembra</th><th>Hectáreas</th><th>Inversión</th><th>Neta</th><th>Disponible</th><th>Ventas</th></tr></thead>
                        <tbody>
                            @forelse($cultivos as $cultivo)
                                <tr>
                                    <td><a href="{{ route('reporteria.cultivos.show', $cultivo['id']) }}" class="fw-semibold text-decoration-none">{{ $cultivo['nombre'] }}</a><div class="small text-muted">{{ $cultivo['codigo'] ?: 'Sin código' }}</div></td>
                                    <td>{{ $cultivo['estado'] }}</td>
                                    <td>{{ $cultivo['fecha_siembra'] ? \Carbon\Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-' }}</td>
                                    <td>{{ agro_number($cultivo['hectareas'], 2) }}</td>
                                    <td>{{ agro_number($cultivo['inversion'], 2) }} Lps</td>
                                    <td>{{ agro_number($cultivo['cosecha_neta'], 2) }}</td>
                                    <td>{{ agro_number($cultivo['disponible'], 2) }}</td>
                                    <td>{{ agro_number($cultivo['ventas'], 2) }} Lps</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">Este lote no tiene cultivos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Últimas cosechas del lote</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Fecha</th><th>Cultivo</th><th>Neta</th><th>Disponible</th><th>Unidad</th></tr></thead>
                        <tbody>
                            @forelse($cosechas as $cosecha)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                                    <td>{{ $cosecha->cultivo->nombre ?? '-' }}</td>
                                    <td>{{ agro_number($cosecha->cantidad_neta, 2) }}</td>
                                    <td>{{ agro_number($cosecha->cantidad_disponible, 2) }}</td>
                                    <td>{{ $cosecha->unidad_medida }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No hay cosechas registradas para este lote.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection