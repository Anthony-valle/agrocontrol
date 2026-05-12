@extends('layouts.main')

@section('titulo', 'Dashboard de Reportería')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Dashboard de Reportería</h1>
        <p class="text-muted mb-0">Vista ejecutiva del sistema con producción, ventas, costos y alertas operativas.</p>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Lotes</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['lotes']) }}</h3></div></div></div>
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Cultivos activos</small><h3 class="mt-2 mb-0 text-success">{{ agro_number($metricas['cultivos_activos']) }}</h3></div></div></div>
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Cosecha neta</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['cosecha_neta'], 2) }}</h3></div></div></div>
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Ventas</small><h3 class="mt-2 mb-0 text-primary">{{ agro_number($metricas['ventas'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Consumos</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['consumos'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-2 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Alertas</small><h3 class="mt-2 mb-0 text-danger">{{ agro_number($metricas['alertas']) }}</h3></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Cultivos con mejor desempeño comercial</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cultivo</th>
                                        <th>Lote</th>
                                        <th>Producción</th>
                                        <th>Inversión</th>
                                        <th>Ventas</th>
                                        <th>Utilidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topCultivos as $fila)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $fila['nombre'] }}</div>
                                                <small class="text-muted">{{ $fila['estado'] }}</small>
                                            </td>
                                            <td>{{ $fila['lote'] }}</td>
                                            <td>{{ agro_number($fila['produccion'], 2) }}</td>
                                            <td>{{ agro_number($fila['inversion'], 2) }} Lps</td>
                                            <td class="text-primary fw-bold">{{ agro_number($fila['ingresos'], 2) }} Lps</td>
                                            <td class="fw-bold {{ $fila['utilidad'] >= 0 ? 'text-success' : 'text-danger' }}">{{ agro_number($fila['utilidad'], 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Sin información comercial registrada.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Stock crítico</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Insumo</th><th>Bodega</th><th>Stock</th></tr></thead>
                                <tbody>
                                    @forelse($stockCritico as $item)
                                        <tr>
                                            <td>{{ $item->insumo->nombre ?? '-' }}</td>
                                            <td>{{ $item->bodega->nombre ?? '-' }}</td>
                                            <td class="text-danger fw-bold">{{ agro_number($item->stock_actual, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay insumos en nivel crítico.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Actividad reciente</h5></div>
                    <div class="card-body pt-3">
                        <div class="list-group list-group-flush">
                            @forelse($actividadReciente as $evento)
                                <div class="list-group-item px-0 py-2 border-0 border-bottom">
                                    <div class="d-flex justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $evento['tipo'] }} · {{ $evento['titulo'] }}</div>
                                            <small class="text-muted">{{ $evento['detalle'] }}</small>
                                        </div>
                                        <small class="text-muted text-nowrap">{{ \Carbon\Carbon::parse($evento['fecha'])->format('d/m/Y') }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted">No hay actividad reciente.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection