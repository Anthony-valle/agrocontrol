@php
    $actividadesCategoria = $detallesCategoria
        ->pluck('descripcion_filtro')
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
                <div class="p-3 border rounded bg-light shadow-sm categoria-filtros-surface">
                <div class="row g-3 align-items-start">
                    <div class="col-lg-3 col-md-6">
                        <label class="fw-bold d-block text-muted small uppercase">Categoría</label>
                        @if(($showCategoriaFilter ?? false) && isset($categoriasDisponibles))
                            <select class="form-select form-select-sm shadow-sm mt-1 categoria-categoria-select" data-page-base-url="{{ $categoriaPageBaseUrl ?? '' }}">
                                @foreach($categoriasDisponibles as $categoriaDisponible)
                                    <option value="{{ $categoriaDisponible }}" {{ $categoria === $categoriaDisponible ? 'selected' : '' }}>
                                        {{ $categoriaDisponible }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <div class="form-control form-control-sm shadow-sm mt-1 d-flex align-items-center bg-white">{{ $categoria }}</div>
                        @endif
                        <div class="small text-muted mt-2">&nbsp;</div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label class="fw-bold d-block text-muted small uppercase">Fechas relacionadas</label>
                        <select class="form-select form-select-sm shadow-sm mt-1 categoria-fecha-select" {{ $fechasCategoria->isEmpty() ? 'disabled' : '' }}>
                            <option value="__ALL__">Todas</option>
                            @foreach($fechasCategoria as $fechaCategoria)
                                <option value="{{ $fechaCategoria }}" {{ $selectedFecha === $fechaCategoria ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($fechaCategoria)->format('d/m/Y') }}
                                </option>
                            @endforeach
                        </select>
                        @if($fechasCategoria->isEmpty())
                            <div class="small text-muted mt-2">Sin fechas relacionadas.</div>
                        @endif
                        <div class="small text-muted mt-2 categoria-fecha-filter-info">
                            Mostrando todos los registros de la categoría.
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-8">
                        <label class="fw-bold d-block text-muted small uppercase">Descripción / insumo</label>
                        <div class="position-relative categoria-actividad-autocomplete">
                            <input
                                type="text"
                                class="form-control form-control-sm shadow-sm mt-1 categoria-actividad-select"
                                value="{{ $selectedActividad !== '__ALL__' ? $selectedActividad : '' }}"
                                placeholder="Escribe para buscar insumo"
                                autocomplete="off"
                                {{ $actividadesCategoria->isEmpty() ? 'disabled' : '' }}
                            >
                            <div class="categoria-actividad-suggestions d-none" data-empty-message="No hay coincidencias."></div>
                        </div>
                        @if($actividadesCategoria->isEmpty())
                            <div class="small text-muted mt-2">Sin descripciones relacionadas.</div>
                        @endif
                        <div class="small text-muted mt-2 categoria-actividad-filter-info">
                            Mostrando todas las descripciones de la categoría.
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <div class="d-grid gap-2 d-sm-flex justify-content-lg-end mt-1">
                            <a href="{{ route('reporteria.cultivos.consumos-categoria.excel', ['cultivo' => $cultivo->id, 'categoria' => $categoria, 'fecha' => $selectedFecha ?: null, 'descripcion' => $selectedActividad ?: null]) }}" class="btn btn-success btn-sm categoria-export-link" data-export-type="excel">
                                <i class="fa-solid fa-file-excel me-1"></i> Descargar Excel
                            </a>
                            <a href="{{ route('reporteria.cultivos.consumos-categoria.pdf', ['cultivo' => $cultivo->id, 'categoria' => $categoria, 'fecha' => $selectedFecha ?: null, 'descripcion' => $selectedActividad ?: null]) }}" class="btn btn-danger btn-sm categoria-export-link" data-export-type="pdf">
                                <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
                            </a>
                        </div>
                    </div>
                </div>
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
                        <th>Categoría</th>
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
                        <tr data-detail-row data-categoria-fecha="{{ $detalle->fecha }}" data-categoria-actividad="{{ $detalle->descripcion_filtro }}" data-categoria-busqueda="{{ $detalle->busqueda_filtro ?? $detalle->descripcion_filtro }}" data-categoria-subtotal="{{ $detalle->subtotal }}">
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
                            <td>{{ $detalle->categoria }}</td>
                            <td class="text-center">{{ agro_number($detalle->cantidad, 2) }}</td>
                            <td class="text-center">{{ $detalle->unidad_medida }}</td>
                            <td class="text-end">{{ agro_number($detalle->costo_unitario, 2) }} Lps</td>
                            <td class="text-end">{{ agro_number($detalle->subtotal, 2) }} Lps</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-3">No hay registros relacionados con esta categoría.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="9" class="text-end fw-bold py-3">TOTAL DE LA CATEGORÍA</td>
                        <td class="text-end py-3 fw-bold" data-categoria-total>{{ agro_number($totalCategoria, 2) }} Lps</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mt-3 categoria-pagination-shell">
            <div class="small text-muted" id="categoriaPaginationInfo">Mostrando 0 registros.</div>
            <nav aria-label="Paginación de detalle de categoría">
                <ul class="pagination pagination-sm mb-0" id="categoriaPaginationList"></ul>
            </nav>
        </div>
    </div>
</div>
