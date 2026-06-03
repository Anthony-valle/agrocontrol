@extends('layouts.main')

@section('titulo', 'Reportería de Mano de Obra')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Reportería de Mano de Obra</h1>
        <p class="text-muted mb-0">Consulta catálogo, costo promedio y ejecución registrada bajo la categoría Mano de Obra.</p>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Catálogo</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['catalogo']) }}</h3></div></div></div>
            <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Activas</small><h3 class="mt-2 mb-0 text-success">{{ agro_number($metricas['activas']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Costo promedio</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['costo_promedio'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Planificado</small><h3 class="mt-2 mb-0 text-primary">{{ agro_number($metricas['planificado'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-2"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Ejecutado</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['ejecutado'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-1"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Cultivos</small><h3 class="mt-2 mb-0 text-info">{{ agro_number($metricas['cultivos_con_ejecucion']) }}</h3></div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Agrupación por actividad</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Actividad</th><th>Registros</th><th>Activas</th><th>Costo prom.</th></tr></thead>
                                <tbody>
                                    @forelse($resumenSecundaria as $fila)
                                        <tr>
                                            <td>{{ $fila['actividad'] }}</td>
                                            <td>{{ $fila['registros'] }}</td>
                                            <td>{{ $fila['activas'] }}</td>
                                            <td>{{ agro_number($fila['costo_promedio'], 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin labores configuradas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Costo por cultivo y actividad</h5></div>
                    <div class="card-body pt-3">
                        <form method="GET" action="{{ route('reporteria.mano_obra') }}" class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                    <option value="5" {{ (int) $perPage === 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ (int) $perPage === 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                                </select>
                                <small class="text-muted text-nowrap">registros</small>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>#</th><th>Cultivo</th><th>Actividad</th><th>Registros</th><th>Cantidad</th><th>Costo total</th></tr></thead>
                                <tbody>
                                    @forelse($costosPorCultivoActividad as $fila)
                                        <tr>
                                            <td>{{ ($costosPorCultivoActividad->firstItem() ?? 0) + $loop->index }}</td>
                                            <td>{{ $fila->cultivo }}</td>
                                            <td>{{ $fila->actividad }}</td>
                                            <td>{{ agro_number($fila->registros) }}</td>
                                            <td>{{ agro_number((float) $fila->cantidad_total, 2) }} {{ $fila->unidad_medida }}</td>
                                            <td>{{ agro_number((float) $fila->subtotal_total, 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">No hay costos agrupados de mano de obra.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($costosPorCultivoActividad->hasPages())
                            <div class="mt-3">
                                {{ $costosPorCultivoActividad->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-xl-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Costo total por cultivo</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>Cultivo</th><th>Registros</th><th>Actividades</th><th>Costo total</th></tr></thead>
                                <tbody>
                                    @forelse($resumenCultivos as $fila)
                                        <tr>
                                            <td>{{ $fila['cultivo'] }}</td>
                                            <td>{{ agro_number($fila['registros']) }}</td>
                                            <td>{{ agro_number($fila['actividades']) }}</td>
                                            <td>{{ agro_number($fila['subtotal_total'], 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin costos registrados por cultivo.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Ejecución detallada por cultivo</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light"><tr><th>#</th><th>Fecha</th><th>Cultivo</th><th>Actividad</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
                                <tbody>
                                    @forelse($ejecuciones as $fila)
                                        <tr>
                                            <td>{{ ($ejecuciones->firstItem() ?? 0) + $loop->index }}</td>
                                            <td>{{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                            <td>{{ $fila['cultivo'] ?: 'Sin cultivo' }}</td>
                                            <td>{{ $fila['descripcion'] }}</td>
                                            <td>{{ agro_number($fila['cantidad'], 2) }} {{ $fila['unidad_medida'] }}</td>
                                            <td>{{ agro_number($fila['subtotal'], 2) }} Lps</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">No hay ejecución de mano de obra registrada.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($ejecuciones->hasPages())
                            <div class="mt-3">
                                {{ $ejecuciones->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection