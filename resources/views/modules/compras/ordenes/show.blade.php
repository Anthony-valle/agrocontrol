@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Documento generado desde una solicitud aprobada.</p>
    </div>

    <section class="section">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 border-bottom pb-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        @if(!empty($logoEmpresa))
                            <img src="{{ $logoEmpresa }}" alt="Logo empresa" style="width:72px;height:72px;object-fit:contain;">
                        @endif
                        <div>
                            <div class="fw-bold fs-4 text-success">{{ $empresa->nombre ?? 'AgroControl' }}</div>
                            <div class="text-muted small">
                                RTN: {{ $empresa->rtn ?? 'N/D' }}
                                @if(!empty($empresa?->telefono)) · Tel: {{ $empresa->telefono }} @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-md-end">
                        <div class="fw-bold fs-5">ORDEN DE COMPRA</div>
                        <div class="text-muted">{{ $orden->codigo }}</div>
                        <span class="badge bg-dark mt-2">{{ $orden->estado }}</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6"><strong>Proveedor:</strong> {{ $orden->proveedor }}</div>
                    <div class="col-md-6"><strong>Fecha emisión:</strong> {{ $orden->fecha_emision?->format('d/m/Y') }}</div>
                    <div class="col-md-6"><strong>Solicitud base:</strong> {{ $orden->solicitudCompra->codigo ?? 'N/D' }}</div>
                    <div class="col-md-6"><strong>Solicitante:</strong> {{ $orden->solicitudCompra->solicitante->usuario ?? 'N/D' }}</div>
                    <div class="col-md-6"><strong>Bodega destino:</strong> {{ $orden->solicitudCompra->bodegaDestino->nombre ?? 'N/D' }}</div>
                    <div class="col-md-6"><strong>Total estimado:</strong> {{ $orden->total_estimado !== null ? number_format((float) $orden->total_estimado, 2) : 'Pendiente' }}</div>
                    <div class="col-md-6"><strong>Recepción:</strong> {{ $orden->recepcion_estado_label }}</div>
                    <div class="col-md-6"><strong>Recibido por:</strong> {{ $orden->receptor->usuario ?? 'Pendiente' }}</div>
                    @if($orden->recibido_en)
                        <div class="col-md-6"><strong>Fecha recepción:</strong> {{ $orden->recibido_en->format('d/m/Y H:i') }}</div>
                    @endif
                    @if($orden->aprobadorDiferencias)
                        <div class="col-md-6"><strong>Aprobó diferencias:</strong> {{ $orden->aprobadorDiferencias->usuario }}</div>
                    @endif
                </div>

                @php($resumen = $orden->recepcion_resumen)

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Items completos</div>
                            <div class="fs-4 fw-bold">{{ $resumen['completos'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Con diferencias</div>
                            <div class="fs-4 fw-bold">{{ $resumen['con_diferencias'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Faltante total</div>
                            <div class="fs-4 fw-bold">{{ number_format((float) $resumen['faltante_total'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Recibido total</div>
                            <div class="fs-4 fw-bold">{{ number_format((float) $resumen['recibido_total'], 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive agro-table-shell mb-4">
                    <table class="table table-hover align-middle agro-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%;">No.</th>
                                <th>Descripción</th>
                                <th style="width: 18%;">Categoría</th>
                                <th style="width: 12%;">Unidad</th>
                                <th style="width: 14%;">Cantidad</th>
                                <th style="width: 14%;">Precio unitario</th>
                                <th style="width: 16%;">Subtotal</th>
                                <th style="width: 14%;">Recibido</th>
                                <th style="width: 14%;">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orden->detalle_items_resolved as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $item['descripcion'] ?? '' }}</div>
                                        <span class="agro-table-meta">{{ $item['categoria'] ?? 'N/D' }}{{ !empty($item['unidad']) ? ' | ' . $item['unidad'] : '' }}</span>
                                    </td>
                                    <td>{{ $item['categoria'] ?? 'N/D' }}</td>
                                    <td>{{ $item['unidad'] ?? 'N/D' }}</td>
                                    <td>{{ number_format((float) ($item['cantidad'] ?? 0), 2) }}</td>
                                    <td>{{ isset($item['precio_unitario']) && $item['precio_unitario'] !== null ? number_format((float) $item['precio_unitario'], 2) : 'Pendiente' }}</td>
                                    <td>{{ isset($item['subtotal']) && $item['subtotal'] !== null ? number_format((float) $item['subtotal'], 2) : 'Pendiente' }}</td>
                                    <td>{{ $item['cantidad_recibida'] !== null ? number_format((float) $item['cantidad_recibida'], 2) : 'Pendiente' }}</td>
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

                <div class="mb-4">
                    <strong>Observaciones:</strong> {{ $orden->observaciones ?: 'Sin observaciones.' }}
                    <br>
                    <strong>Recepción:</strong> {{ $orden->recepcion_observaciones ?: 'Sin observaciones de recepción.' }}
                    @if($orden->diferencias_observaciones)
                        <br>
                        <strong>Aprobación de diferencias:</strong> {{ $orden->diferencias_observaciones }}
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('compras.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                    @if(auth()->user()?->hasRole('compra') || auth()->user()?->isSuperUser())
                        <a href="{{ route('compras.ordenes.validation.show', $orden) }}" class="btn btn-dark btn-sm">Validar llegada</a>
                    @endif
                    <a href="{{ route('compras.ordenes.report') }}" class="btn btn-outline-dark btn-sm">Reporte O.C.</a>
                    <a href="{{ route('compras.solicitudes.show', $orden->solicitudCompra) }}" class="btn btn-primary btn-sm">Ver solicitud</a>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection