<div class="card border-0 shadow-sm mb-0 categoria-resumen-shell">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="small text-uppercase text-muted fw-bold">Categoria</div>
                <div class="fs-4 fw-semibold">{{ $categoria }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-muted fw-bold">Cultivo</div>
                <div>{{ $cultivo->nombre }}</div>
                <div class="small text-muted">{{ $cultivo->lote->nombre ?? '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="small text-uppercase text-muted fw-bold">Comparacion rapida</div>
                <div>Plan: {{ agro_number($planCostoTotal, 2) }} Lps</div>
                <div class="small text-muted">Real: {{ agro_number($realCostoTotal, 2) }} Lps</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100 border-0 bg-light-subtle">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span>Plan de la categoria</span>
                        <span class="small text-muted">{{ agro_number($planDetallesCategoria->count()) }} registros</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 small text-muted">
                            Cantidad plan: {{ agro_number($planCantidadTotal, 2) }}
                            <br>
                            Costo plan: {{ agro_number($planCostoTotal, 2) }} Lps
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="planCategoriaTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Semana</th>
                                        <th>Descripcion</th>
                                        <th class="text-end">Cantidad</th>
                                        <th>U.M.</th>
                                        <th class="text-end">Costo Unit.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($planDetallesCategoria as $detalle)
                                        <tr data-detail-row>
                                            <td>{{ $detalle->semana ?? '-' }}</td>
                                            <td>{{ $detalle->descripcion }}</td>
                                            <td class="text-end">{{ agro_number($detalle->cantidad_estimada, 2) }}</td>
                                            <td>{{ $detalle->unidad_medida }}</td>
                                            <td class="text-end">{{ agro_number($detalle->costo_unitario, 2) }}</td>
                                            <td class="text-end">{{ agro_number($detalle->subtotal, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No hay plan registrado para esta categoria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($planDetallesCategoria->isNotEmpty())
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted" id="planCategoriaPaginationInfo"></small>
                                <nav aria-label="Paginacion del plan por categoria">
                                    <ul class="pagination pagination-sm mb-0" id="planCategoriaPaginationList"></ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100 border-0 bg-light-subtle">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span>Consumo real de la categoria</span>
                        <span class="small text-muted">{{ agro_number($consumosCategoria->count()) }} registros</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 small text-muted">
                            Cantidad real: {{ agro_number($realCantidadTotal, 2) }}
                            <br>
                            Costo real: {{ agro_number($realCostoTotal, 2) }} Lps
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="realCategoriaTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Semana</th>
                                        <th>Fecha</th>
                                        <th>Insumo / Concepto</th>
                                        <th>Descripcion</th>
                                        <th class="text-end">Cantidad</th>
                                        <th>U.M.</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($consumosCategoria as $item)
                                        <tr data-detail-row>
                                            <td>{{ is_numeric($item->semana_cultivo) ? (int) $item->semana_cultivo : $item->semana_cultivo }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->fecha_consumo)->format('d/m/Y') }}</td>
                                            <td>{{ $item->insumo }}</td>
                                            <td>{{ $item->descripcion }}</td>
                                            <td class="text-end">{{ agro_number($item->cantidad, 2) }}</td>
                                            <td>{{ $item->unidad_medida }}</td>
                                            <td class="text-end">{{ agro_number($item->subtotal, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-3">No hay consumo real registrado para esta categoria.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($consumosCategoria->isNotEmpty())
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <small class="text-muted" id="realCategoriaPaginationInfo"></small>
                                <nav aria-label="Paginacion del consumo real por categoria">
                                    <ul class="pagination pagination-sm mb-0" id="realCategoriaPaginationList"></ul>
                                </nav>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
