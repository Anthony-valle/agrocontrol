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
                <a href="{{ route('cultivo.index') }}" class="dashboard-card-link">
                <div class="card info-card sales-card h-100 shadow-sm border-0 dashboard-click-card">
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
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <a href="{{ route('lotes.index') }}" class="dashboard-card-link">
                <div class="card info-card revenue-card h-100 shadow-sm border-0 dashboard-click-card">
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
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <a href="{{ route('insumos.index') }}" class="dashboard-card-link">
                <div class="card info-card customers-card h-100 shadow-sm border-0 dashboard-click-card">
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
                </a>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <a href="{{ route('reporteria.alertas') }}" class="dashboard-card-link">
                <div class="card info-card h-100 shadow-sm border-0 dashboard-click-card">
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
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0">
                            <a href="{{ route('cultivo.index') }}" class="dashboard-section-link">Cultivos recientes</a>
                        </h5>
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
                                        <tr class="dashboard-click-row" onclick="window.location='{{ route('cultivo.index') }}'">
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
                        <h5 class="card-title mb-0">
                            <a href="{{ route('lotes.index') }}" class="dashboard-section-link">Lotes recientes</a>
                        </h5>
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
                                        <tr class="dashboard-click-row" onclick="window.location='{{ route('lotes.index') }}'">
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
.dashboard-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
}

.dashboard-click-card {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    cursor: pointer;
}

.dashboard-card-link:hover .dashboard-click-card {
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(15, 90, 67, 0.12) !important;
}

.dashboard-click-row {
    cursor: pointer;
}

.dashboard-click-row:hover td {
    background: #f3faf6;
}

.dashboard-section-link {
    color: inherit;
    text-decoration: none;
}

.dashboard-section-link:hover,
.dashboard-section-link:focus {
    color: #0f5a43;
}

.dashboard .table-success th {
    background: #0f5a43;
    color: #fff;
    border-color: #0f5a43;
}
</style>
@endsection