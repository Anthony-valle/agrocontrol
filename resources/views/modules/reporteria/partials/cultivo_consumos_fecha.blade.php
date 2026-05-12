@php
    $fechaFormateada = \Carbon\Carbon::parse($fecha)->format('d/m/Y');
@endphp

<div class="d-flex flex-column gap-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <div class="small text-uppercase text-muted fw-bold">Fecha consumo</div>
            <h5 class="mb-1">{{ $fechaFormateada }}</h5>
            <div class="text-muted small">{{ $cultivo->nombre }} · {{ agro_number($consumos->count()) }} consumos · {{ agro_number($totalFecha, 2) }} Lps</div>
        </div>
        <span class="badge text-bg-light border">Detalle cargado bajo demanda</span>
    </div>

    @forelse($consumos as $consumo)
        @php
            $estado = strtoupper((string) $consumo->estado);
        @endphp
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <div class="fw-bold">Consumo #{{ $consumo->id }}</div>
                        <div class="text-muted small">Registrado {{ $consumo->created_at?->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                    <div class="text-end">
                        @if($estado === 'FINALIZADO' || (string) $consumo->estado === '1')
                            <span class="badge bg-success">FINALIZADO</span>
                            <div class="small text-muted mt-1">Validó: {{ $consumo->validador->usuario ?? $consumo->validador->name ?? '-' }}</div>
                        @elseif($estado === 'ANULADO')
                            <span class="badge bg-danger">ANULADO</span>
                            <div class="small text-muted mt-1">Anuló: {{ $consumo->anulador->usuario ?? $consumo->anulador->name ?? '-' }}</div>
                        @else
                            <span class="badge bg-warning text-dark">PENDIENTE</span>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>U.M.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($consumo->detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->categoria }}</td>
                                    <td>{{ $detalle->descripcion }}</td>
                                    <td>{{ agro_number((float) $detalle->cantidad, 2) }}</td>
                                    <td>{{ $detalle->unidad_medida }}</td>
                                    <td>{{ agro_number((float) $detalle->subtotal, 2) }} Lps</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Este consumo no tiene detalles registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total del consumo</th>
                                <th>{{ agro_number((float) $consumo->total, 2) }} Lps</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-warning mb-0">No hay consumos vigentes para esta fecha.</div>
    @endforelse
</div>