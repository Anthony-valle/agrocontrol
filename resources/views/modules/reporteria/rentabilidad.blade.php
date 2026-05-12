@extends('layouts.main')

@section('titulo', 'Reportería de Rentabilidad')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Reportería de Rentabilidad</h1>
        <p class="text-muted mb-0">Consolidado económico por cultivo usando ventas registradas y consumos reales.</p>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Cultivos</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['cultivos']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Inversión</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['inversion'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Ingresos</small><h3 class="mt-2 mb-0 text-primary">{{ agro_number($metricas['ingresos'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Utilidad</small><h3 class="mt-2 mb-0 {{ $metricas['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">{{ agro_number($metricas['utilidad'], 2) }} Lps</h3></div></div></div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Detalle económico por cultivo</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive">
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
                                <tr><td colspan="9" class="text-center text-muted py-4">Todavía no hay datos suficientes para calcular rentabilidad.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection