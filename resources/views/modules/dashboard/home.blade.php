@extends('layouts.main')

@section('titulo', 'AgroControl')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Dashboard</h1>
        <p class="text-muted mb-0">Resumen general del estado de cultivos, lotes e inventario.</p>
    </div>

    <section class="section dashboard">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card sales-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Cultivos</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success">
                                <i class="bi bi-flower1"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalCultivos ?? 0 }}</h6>
                                <span class="text-muted small pt-2 ps-1">registrados</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card revenue-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Lotes</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary">
                                <i class="bi bi-grid"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $totalLotes ?? 0 }}</h6>
                                <span class="text-muted small pt-2 ps-1">activos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card customers-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Insumos</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $insumosTotales ?? 0 }}</h6>
                                <span class="text-muted small pt-2 ps-1">en catálogo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card info-card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title">Alertas</h5>
                        <div class="d-flex align-items-center">
                            <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="ps-3">
                                <h6>{{ $alertasStock ?? 0 }}</h6>
                                <span class="text-muted small pt-2 ps-1">stock bajo</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Cultivos recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($cultivosActivos ?? collect()) as $cultivo)
                                        <tr>
                                            <td>{{ $cultivo->codigo ?? '—' }}</td>
                                            <td>{{ $cultivo->nombre ?? 'Sin nombre' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">No hay cultivos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">Lotes recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($lotesActivos ?? collect()) as $lote)
                                        <tr>
                                            <td>{{ $lote->codigo ?? '—' }}</td>
                                            <td>{{ $lote->nombre ?? 'Sin nombre' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">No hay lotes registrados.</td>
                                        </tr>
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

<style>
.dashboard .table-success th {
    background: #0f5a43;
    color: #fff;
    border-color: #0f5a43;
}
</style>
@endsection