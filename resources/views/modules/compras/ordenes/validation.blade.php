@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    @php($recepcionRegistrada = filled($orden->recepcion_estado))
    @php($puedeCompletarRecepcion = $orden->recepcion_estado === 'con_diferencias')
    @php($recepcionSoloLectura = $recepcionRegistrada && ! $puedeCompletarRecepcion)

    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">
            {{ $recepcionSoloLectura
                ? 'La llegada ya fue validada. Esta pantalla queda solo para consulta.'
                : ($puedeCompletarRecepcion
                    ? 'La orden quedo con diferencias. Si llego mas material, actualiza aqui el recibido acumulado.'
                    : 'Pantalla exclusiva para validar llegada y aprobar diferencias.') }}
        </p>
    </div>

    <section class="section">
        <div class="card agro-table-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                    <div>
                        <div class="fw-bold fs-5">{{ $orden->codigo }}</div>
                        <div class="text-muted">Proveedor: {{ $orden->proveedor }}</div>
                    </div>
                    <div class="text-md-end text-muted small">
                        <div>Solicitud: {{ $orden->solicitudCompra->codigo ?? 'N/D' }}</div>
                        <div>Solicitante: {{ $orden->solicitudCompra->solicitante->usuario ?? 'N/D' }}</div>
                        <div>Bodega: {{ $orden->solicitudCompra->bodegaDestino->nombre ?? 'N/D' }}</div>
                    </div>
                </div>

                @if($recepcionRegistrada)
                    <div class="alert alert-light border mb-3">
                        <div class="fw-semibold">Estado de recepción: {{ $orden->recepcion_estado_label }}</div>
                        <div class="small text-muted">
                            Registrada por {{ $orden->receptor->usuario ?? 'N/D' }}
                            el {{ optional($orden->recibido_en)->format('d/m/Y h:i A') ?? 'N/D' }}.
                        </div>
                        @if($puedeCompletarRecepcion)
                            <div class="small text-muted mt-1">Si al siguiente dia llega lo faltante, actualiza el total recibido final por cada producto.</div>
                        @endif
                    </div>
                @endif

                @if($recepcionSoloLectura)
                    <div class="d-grid gap-3">
                        <div class="table-responsive agro-table-shell">
                            <table class="table table-hover table-sm align-middle agro-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Detalle</th>
                                        <th style="width: 14%;">Solic.</th>
                                        <th style="width: 18%;">Recib.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orden->detalle_items_resolved as $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item['descripcion'] }}</div>
                                                <div class="small text-muted">{{ $item['categoria'] ?: 'Sin categoría' }}{{ $item['unidad'] ? ' | ' . $item['unidad'] : '' }}</div>
                                            </td>
                                            <td>{{ number_format((float) $item['cantidad'], 2) }}</td>
                                            <td>{{ number_format((float) ($item['cantidad_recibida'] ?? 0), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <label class="form-label">Observaciones de recepción</label>
                            <div class="form-control bg-light" style="min-height: 96px;">{{ $orden->recepcion_observaciones ?: 'Sin observaciones.' }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('compras.ordenes.validation.index') }}" class="btn btn-outline-secondary">Volver</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('compras.ordenes.receive', $orden) }}" class="d-grid gap-3">
                        @csrf
                        @if($puedeCompletarRecepcion)
                            <div class="alert alert-warning mb-0">
                                Registra el recibido acumulado total. Ejemplo: si ayer recibiste 8 y hoy llego 1 mas, debes dejar 9.
                            </div>
                        @endif
                        <div class="table-responsive agro-table-shell">
                            <table class="table table-hover table-sm align-middle agro-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Detalle</th>
                                        <th style="width: 14%;">Solic.</th>
                                        <th style="width: 18%;">Recibido total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orden->detalle_items_resolved as $index => $item)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item['descripcion'] }}</div>
                                                <div class="small text-muted">{{ $item['categoria'] ?: 'Sin categoría' }}{{ $item['unidad'] ? ' | ' . $item['unidad'] : '' }}</div>
                                            </td>
                                            <td>{{ number_format((float) $item['cantidad'], 2) }}</td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="detalles[{{ $index }}][cantidad_recibida]" class="form-control @error('detalles.' . $index . '.cantidad_recibida') is-invalid @enderror" value="{{ old('detalles.' . $index . '.cantidad_recibida', $item['cantidad_recibida'] ?? $item['cantidad']) }}" required>
                                                @error('detalles.' . $index . '.cantidad_recibida')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <label class="form-label">Observaciones de recepción</label>
                            <textarea name="recepcion_observaciones" class="form-control @error('recepcion_observaciones') is-invalid @enderror" rows="3">{{ old('recepcion_observaciones', $orden->recepcion_observaciones) }}</textarea>
                            @error('recepcion_observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('compras.ordenes.validation.index') }}" class="btn btn-outline-secondary">Volver</a>
                            <button type="submit" class="btn btn-dark">{{ $puedeCompletarRecepcion ? 'Actualizar recepción' : 'Guardar validación' }}</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        @if(auth()->user()?->isSuperUser() && $orden->recepcion_estado === 'con_diferencias')
            <div class="card agro-table-card border-warning shadow-sm">
                <div class="card-body p-4">
                    <h5 class="card-title text-warning">Aprobación de diferencias</h5>
                    <form method="POST" action="{{ route('compras.ordenes.approve-differences', $orden) }}" class="d-grid gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="table-responsive agro-table-shell">
                            <table class="table table-hover table-sm align-middle agro-table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th style="width: 16%;">Solicitado</th>
                                        <th style="width: 16%;">Recibido</th>
                                        <th style="width: 18%;">Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orden->detalle_items_resolved as $item)
                                        <tr>
                                            <td>{{ $item['descripcion'] }}</td>
                                            <td>{{ number_format((float) $item['cantidad'], 2) }}</td>
                                            <td>{{ number_format((float) ($item['cantidad_recibida'] ?? 0), 2) }}</td>
                                            <td>
                                                @if(($item['cantidad_faltante'] ?? 0) > 0)
                                                    Faltan {{ number_format((float) $item['cantidad_faltante'], 2) }}
                                                @elseif(($item['cantidad_excedente'] ?? 0) > 0)
                                                    Sobra {{ number_format((float) $item['cantidad_excedente'], 2) }}
                                                @else
                                                    Sin diferencia
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <label class="form-label">Justificación / aprobación</label>
                            <textarea name="diferencias_observaciones" class="form-control @error('diferencias_observaciones') is-invalid @enderror" rows="3">{{ old('diferencias_observaciones', $orden->diferencias_observaciones) }}</textarea>
                            @error('diferencias_observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <button type="submit" class="btn btn-warning">Aprobar diferencias</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </section>
</main>
@endsection