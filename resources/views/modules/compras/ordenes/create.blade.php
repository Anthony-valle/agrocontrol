@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    @php
        $totalInicial = collect($detalleItems)->sum(function ($item) {
            $cantidad = (float) ($item['cantidad'] ?? 0);
            $precio = isset($item['precio_unitario']) && $item['precio_unitario'] !== null
                ? (float) $item['precio_unitario']
                : 0;

            return $cantidad * $precio;
        });
    @endphp

    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Completa proveedor y precios unitarios antes de generar la orden.</p>
    </div>

    <section class="section">
        <form method="POST" action="{{ route('compras.solicitudes.order.store', $solicitud) }}">
            @csrf

            <div class="card agro-table-card mb-3">
                <div class="card-body p-4">
                    <div class="agro-table-toolbar mb-4">
                        <div class="agro-table-toolbar-group">
                            <div>
                                <h5 class="card-title mb-1">Solicitud {{ $solicitud->codigo ?: 'SC-' . $solicitud->id }}</h5>
                                <small class="text-muted d-block">{{ $solicitud->asunto }}</small>
                                <small class="text-muted d-block">Solicitante: {{ $solicitud->solicitante->usuario ?? 'N/D' }}</small>
                                <small class="text-muted d-block">Bodega: {{ $solicitud->bodegaDestino->nombre ?? 'N/D' }}</small>
                            </div>
                        </div>
                        <div class="agro-toolbar-actions">
                            <a href="{{ route('compras.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save me-1"></i> Guardar O.C.</button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Proveedor</label>
                            <input type="text" name="proveedor" class="form-control @error('proveedor') is-invalid @enderror" value="{{ old('proveedor') }}" required>
                            @error('proveedor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha emisión</label>
                            <input type="date" name="fecha_emision" class="form-control @error('fecha_emision') is-invalid @enderror" value="{{ old('fecha_emision', now()->format('Y-m-d')) }}" required>
                            @error('fecha_emision')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total estimado</label>
                            <input type="number" step="0.001" min="0" name="total_estimado" class="form-control @error('total_estimado') is-invalid @enderror" value="{{ old('total_estimado') }}" placeholder="Se calcula si lo dejas vacío">
                            <div class="form-text">Total calculado actual: <span id="ordenTotalCalculado">{{ number_format((float) old('total_estimado', $totalInicial), 3) }}</span></div>
                            @error('total_estimado')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control @error('observaciones') is-invalid @enderror" rows="3">{{ old('observaciones') }}</textarea>
                            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card agro-table-card">
                <div class="card-body p-4">
                    <h5 class="card-title">Productos de la orden</h5>

                    <div class="table-responsive agro-table-shell">
                        <table class="table table-hover align-middle agro-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 6%;">No.</th>
                                    <th>Producto</th>
                                    <th style="width: 16%;">Categoría</th>
                                    <th style="width: 10%;">Unidad</th>
                                    <th style="width: 12%;">Cantidad</th>
                                    <th style="width: 16%;">Precio unitario</th>
                                    <th style="width: 16%;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detalleItems as $index => $item)
                                    @php
                                        $oldDetalle = old('detalles.' . $index, $item);
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="detalles[{{ $index }}][descripcion]" value="{{ $oldDetalle['descripcion'] }}">
                                            <input type="hidden" name="detalles[{{ $index }}][categoria]" value="{{ $oldDetalle['categoria'] }}">
                                            <input type="hidden" name="detalles[{{ $index }}][unidad]" value="{{ $oldDetalle['unidad'] }}">
                                            <input type="hidden" name="detalles[{{ $index }}][cantidad]" value="{{ $oldDetalle['cantidad'] }}">
                                            <div class="fw-semibold">{{ $oldDetalle['descripcion'] }}</div>
                                            <span class="agro-table-meta">{{ $oldDetalle['categoria'] ?: 'Sin categoría' }}{{ $oldDetalle['unidad'] ? ' | ' . $oldDetalle['unidad'] : '' }}</span>
                                        </td>
                                        <td>{{ $oldDetalle['categoria'] ?: 'N/D' }}</td>
                                        <td>{{ $oldDetalle['unidad'] ?: 'N/D' }}</td>
                                        <td>
                                            {{ number_format((float) $oldDetalle['cantidad'], 3) }}
                                            <input type="hidden" class="detalle-cantidad-value" value="{{ (float) $oldDetalle['cantidad'] }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.001" min="0" name="detalles[{{ $index }}][precio_unitario]" class="form-control detalle-precio-unitario @error('detalles.' . $index . '.precio_unitario') is-invalid @enderror" value="{{ old('detalles.' . $index . '.precio_unitario', $oldDetalle['precio_unitario']) }}" placeholder="0.000">
                                            @error('detalles.' . $index . '.precio_unitario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <span class="detalle-subtotal-text">
                                                {{ number_format((float) $oldDetalle['cantidad'] * (float) ($oldDetalle['precio_unitario'] ?? 0), 3) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6" class="text-end">Total calculado</th>
                                    <th id="ordenTotalFooter">{{ number_format((float) old('total_estimado', $totalInicial), 3) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                </div>
            </div>
        </form>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const totalLabel = document.getElementById('ordenTotalCalculado');
        const totalFooter = document.getElementById('ordenTotalFooter');
        const totalInput = document.querySelector('input[name="total_estimado"]');

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: 3,
                maximumFractionDigits: 3,
            });
        }

        function updateTotals() {
            let total = 0;

            document.querySelectorAll('tbody tr').forEach(function (row) {
                const cantidad = parseFloat(row.querySelector('.detalle-cantidad-value')?.value || '0');
                const precio = parseFloat(row.querySelector('.detalle-precio-unitario')?.value || '0');
                const subtotal = cantidad * precio;
                const subtotalText = row.querySelector('.detalle-subtotal-text');

                if (subtotalText) {
                    subtotalText.textContent = formatNumber(subtotal);
                }

                total += subtotal;
            });

            if (totalLabel) {
                totalLabel.textContent = formatNumber(total);
            }

            if (totalFooter) {
                totalFooter.textContent = formatNumber(total);
            }

            if (totalInput && totalInput.value.trim() === '') {
                totalInput.placeholder = formatNumber(total);
            }
        }

        document.querySelectorAll('.detalle-precio-unitario').forEach(function (input) {
            input.addEventListener('input', updateTotals);
        });

        updateTotals();
    });
</script>
@endsection