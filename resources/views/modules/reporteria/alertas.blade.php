@extends('layouts.main')

@section('titulo', 'Reportería de Alertas')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Alertas y Notificaciones</h1>
        <p class="text-muted mb-0">Consolida notificaciones, riesgos de stock bajo y próximos vencimientos.</p>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">No leídas</small><h3 class="mt-2 mb-0 text-danger">{{ agro_number($metricas['no_leidas']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Notificaciones</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['registradas']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Stock bajo</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['stock_bajo']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Vencimientos</small><h3 class="mt-2 mb-0 text-info">{{ agro_number($metricas['vencimientos']) }}</h3></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Notificaciones recientes</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Fecha</th><th>Tipo</th><th>Mensaje</th><th>Estado</th></tr></thead>
                                <tbody>
                                    @forelse($notificaciones as $item)
                                        <tr>
                                            <td>{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                                            <td>{{ $item->tipo }}</td>
                                            <td>{{ $item->mensaje }}</td>
                                            <td>{!! $item->leido ? '<span class="badge bg-success">Leída</span>' : '<span class="badge bg-danger">Pendiente</span>' !!}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No hay notificaciones registradas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Stock bajo</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Insumo</th><th>Bodega</th><th>Stock</th></tr></thead>
                                <tbody>
                                    @forelse($stockBajo as $item)
                                        <tr><td>{{ $item->insumo->nombre ?? '-' }}</td><td>{{ $item->bodega->nombre ?? '-' }}</td><td class="text-danger fw-bold">{{ agro_number($item->stock_actual, 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">Sin insumos críticos.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Vencimientos</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                                <thead class="table-light"><tr><th>Insumo</th><th>Lote</th><th>Fecha</th></tr></thead>
                                <tbody>
                                    @forelse($vencimientos as $item)
                                        <tr><td>{{ $item->insumo->nombre ?? '-' }}</td><td>{{ $item->numero_lote ?: '-' }}</td><td>{{ \Carbon\Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') }}</td></tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No hay vencimientos próximos o vencidos.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection