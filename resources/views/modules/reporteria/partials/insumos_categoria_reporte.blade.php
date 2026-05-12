<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-triangle-exclamation me-1"></i> Insumos con vencimiento cercano</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Insumo</th>
                                <th>Lote</th>
                                <th>Fecha Vencimiento</th>
                                <th>Días restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($vencimientos as $i)
                            <tr>
                                <td>{{ $i->insumo_codigo ?? '' }}</td>
                                <td>{{ $i->insumo_nombre }}</td>
                                <td>{{ $i->lote_codigo }}</td>
                                <td>{{ $i->fecha_vencimiento }}</td>
                                <td>{{ $i->dias_restantes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Sin insumos próximos a vencer</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold text-warning mb-3"><i class="fa-solid fa-box-open me-1"></i> Insumos con stock bajo</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Insumo</th>
                                <th>Lote</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($bajos as $i)
                            <tr>
                                <td>{{ $i->insumo_codigo ?? '' }}</td>
                                <td>{{ $i->insumo_nombre }}</td>
                                <td>{{ $i->lote_codigo }}</td>
                                <td>{{ $i->stock_actual }}</td>
                                <td>{{ $i->stock_minimo }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">Sin insumos con stock bajo</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
