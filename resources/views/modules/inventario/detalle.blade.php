@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <div>
            <h1>Detalle de Inventario</h1>
            <p class="text-muted mb-0">Lote {{ $inventario->numero_lote ?: '-' }} en {{ $inventario->bodega->nombre ?? '-' }}</p>
        </div>
        <a href="{{ route('inventarios.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <section class="section">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Codigo</small>
                        <div class="fw-bold">{{ $inventario->insumo->codigo ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Insumo</small>
                        <div class="fw-bold">{{ $inventario->insumo->nombre ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Stock actual</small>
                        <div class="fw-bold">{{ agro_number((float) $inventario->stock_actual, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted d-block">Costo promedio</small>
                        <div class="fw-bold">L {{ agro_number((float) $inventario->costo_promedio, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Categoria</small>
                        <div>{{ $inventario->categoria_resuelta ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Bodega</small>
                        <div>{{ $inventario->bodega->nombre ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Sucursal</small>
                        <div>{{ $inventario->bodega->sucursal->nombre ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Unidad</small>
                        <div>{{ $inventario->insumo->unidad_medida ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Fecha fabricacion</small>
                        <div>{{ $inventario->fecha_fabricacion ?: '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Fecha vencimiento</small>
                        <div>{{ $inventario->fecha_vencimiento ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title">Movimientos recientes del lote</h5>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Bodega origen</th>
                                <th>Bodega destino</th>
                                <th>Cantidad</th>
                                <th>Stock final</th>
                                <th>Registrado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movimientos as $movimiento)
                                <tr>
                                    <td>{{ $movimiento->created_at ? $movimiento->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td>{{ $movimiento->tipo ?? '-' }}</td>
                                    <td>{{ $movimiento->bodegaOrigen->nombre ?? '-' }}</td>
                                    <td>{{ $movimiento->bodegaDestino->nombre ?? '-' }}</td>
                                    <td>{{ agro_number((float) $movimiento->cantidad, 2) }}</td>
                                    <td>{{ agro_number((float) $movimiento->stock_actual, 2) }}</td>
                                    <td>{{ $movimiento->creador->usuario ?? 'Sistema' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay movimientos registrados para este lote.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection