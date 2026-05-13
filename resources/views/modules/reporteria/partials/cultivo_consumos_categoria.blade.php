@php
    $actividadesCategoria = $detallesCategoria
        ->pluck('descripcion')
        ->map(fn ($descripcion) => trim((string) $descripcion))
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<div
    class="card border-0 shadow-sm mb-0 categoria-detail-card-shell"
    data-export-excel-base="{{ route('reporteria.cultivos.consumos-categoria.excel', $cultivo->id) }}"
    data-export-pdf-base="{{ route('reporteria.cultivos.consumos-categoria.pdf', $cultivo->id) }}"
    data-export-categoria="{{ $categoria }}"
>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="fw-bold d-block text-muted small uppercase">Categoría</label>
                <div class="fs-4 fw-semibold">{{ $categoria }}</div>
            </div>
            <div class="col-md-4">
                <label class="fw-bold d-block text-muted small uppercase">Cultivo</label>
                <div>{{ $cultivo->nombre }}</div>
                <div class="small text-muted">{{ agro_number($detallesCategoria->count()) }} registros</div>
            </div>
            <div class="col-md-4">
                <label class="fw-bold d-block text-muted small uppercase">Totales</label>
                <div>{{ agro_number($cantidadCategoria, 2) }} cantidad</div>
                <div class="small text-muted">{{ agro_number($totalCategoria, 2) }} Lps</div>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <a href="{{ route('reporteria.cultivos.consumos-categoria.excel', ['cultivo' => $cultivo->id, 'categoria' => $categoria, 'fecha' => $selectedFecha ?: null, 'actividad' => $selectedActividad ?: null]) }}" class="btn btn-success btn-sm categoria-export-link" data-export-type="excel">
                        <i class="fa-solid fa-file-excel me-1"></i> Descargar Excel
                    </a>
                    <a href="{{ route('reporteria.cultivos.consumos-categoria.pdf', ['cultivo' => $cultivo->id, 'categoria' => $categoria, 'fecha' => $selectedFecha ?: null, 'actividad' => $selectedActividad ?: null]) }}" class="btn btn-danger btn-sm categoria-export-link" data-export-type="pdf">
                        <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
                    </a>
                </div>
            </div>
            <div class="col-12">
                <label class="fw-bold d-block text-muted small uppercase">Fechas relacionadas</label>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <button type="button" class="badge text-bg-success border-0 categoria-fecha-filter is-active" data-fecha="__ALL__">Todas</button>
                    @forelse($fechasCategoria as $fechaCategoria)
                        <button
                            type="button"
                            class="badge text-bg-light border categoria-fecha-filter"
                            data-fecha="{{ $fechaCategoria }}"
                        >{{ \Carbon\Carbon::parse($fechaCategoria)->format('d/m/Y') }}</button>
                    @empty
                        <span class="text-muted">Sin fechas relacionadas.</span>
                    @endforelse
                </div>
                <div class="small text-muted mt-2 categoria-fecha-filter-info">
                    Mostrando todos los registros de la categoría.
                </div>
            </div>
            <div class="col-12">
                <label class="fw-bold d-block text-muted small uppercase">Actividades relacionadas</label>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <button type="button" class="badge text-bg-success border-0 categoria-actividad-filter is-active" data-actividad="__ALL__">Todas</button>
                    @forelse($actividadesCategoria as $actividadCategoria)
                        <button
                            type="button"
                            class="badge text-bg-light border categoria-actividad-filter"
                            data-actividad="{{ $actividadCategoria }}"
                        >{{ $actividadCategoria }}</button>
                    @empty
                        <span class="text-muted">Sin actividades relacionadas.</span>
                    @endforelse
                </div>
                <div class="small text-muted mt-2 categoria-actividad-filter-info">
                    Mostrando todas las actividades de la categoría.
                </div>
            </div>
        </div>

        <hr>

        <h5 class="mb-3">
            <i class="fa-solid fa-list me-2"></i>
            Detalle completo de la categoría
        </h5>

        <div class="table-responsive tabla-consumo-wrap">
            <table class="table table-bordered table-sm align-middle tabla-consumo mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>Fecha</th>
                        <th>Consumo</th>
                        <th>Estado</th>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th>Bodega</th>
                        <th>Lote</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-end">Costo Unitario</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($detallesCategoria as $detalle)
                        @php
                            $estadoClass = match (strtoupper((string) $detalle->estado)) {
                                'FINALIZADO' => 'bg-success',
                                'ANULADO' => 'bg-danger',
                                default => 'bg-warning text-dark',
                            };

                            $esManoObra = $detalle->categoria === 'Mano De Obra';
                        @endphp
                        <tr data-categoria-fecha="{{ $detalle->fecha }}" data-categoria-actividad="{{ $detalle->descripcion }}" data-categoria-subtotal="{{ $detalle->subtotal }}">
                            <td>{{ \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') }}</td>
                            <td>#{{ $detalle->consumo_id }}</td>
                            <td><span class="badge {{ $estadoClass }}">{{ $detalle->estado }}</span></td>
                            <td>
                                @if($esManoObra)
                                    <span class="text-muted small">N/A</span>
                                @else
                                    {{ $detalle->codigo ?? '-' }}
                                @endif
                            </td>
                            <td>
                                @if($esManoObra)
                                    <span class="text-muted small">N/A (Labor)</span>
                                @else
                                    {{ $detalle->insumo ?? '-' }}
                                @endif
                            </td>
                            <td>{{ $detalle->bodega }}</td>
                            <td>{{ $detalle->lote }}</td>
                            <td>{{ $detalle->categoria }}</td>
                            <td>{{ $detalle->descripcion }}</td>
                            <td class="text-center">{{ agro_number($detalle->cantidad, 2) }}</td>
                            <td class="text-center">{{ $detalle->unidad_medida }}</td>
                            <td class="text-end">{{ agro_number($detalle->costo_unitario, 2) }} Lps</td>
                            <td class="text-end">{{ agro_number($detalle->subtotal, 2) }} Lps</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-3">No hay registros relacionados con esta categoría.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="12" class="text-end fw-bold py-3">TOTAL DE LA CATEGORÍA</td>
                        <td class="text-end py-3 fw-bold" data-categoria-total>{{ agro_number($totalCategoria, 2) }} Lps</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
