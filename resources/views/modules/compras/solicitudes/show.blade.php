@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <style>
        .purchase-document-card {
            background: linear-gradient(180deg, #fffdfa 0%, #ffffff 100%);
            border: 1px solid rgba(122, 91, 38, 0.16);
            border-radius: 1rem;
            box-shadow: 0 0.85rem 1.8rem rgba(15, 23, 42, 0.06);
        }

        .purchase-document-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 2px solid #0f4c3a;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .purchase-document-brand {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .purchase-document-logo {
            width: 84px;
            height: 84px;
            object-fit: contain;
            border: 1px solid rgba(15, 76, 58, 0.12);
            border-radius: 0.85rem;
            padding: 0.35rem;
            background: #fff;
        }

        .purchase-document-company {
            color: #0f4c3a;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .purchase-document-company-meta {
            color: #6b7280;
            font-size: 0.88rem;
        }

        .purchase-document-title {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .purchase-document-table td,
        .purchase-document-table th {
            vertical-align: middle;
        }

        .purchase-document-signature {
            border-top: 1px solid #6c757d;
            padding-top: 0.45rem;
            margin-top: 3.5rem;
            text-align: center;
        }
    </style>
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Formato real de solicitud de compra para impresión, revisión y firmas.</p>
    </div>

    <section class="section">
        <div class="card purchase-document-card border-0">
            <div class="card-body p-4 p-lg-5">
                <div class="purchase-document-header">
                    <div class="purchase-document-brand">
                        @if(!empty($logoEmpresa))
                            <img src="{{ $logoEmpresa }}" alt="Logo empresa" class="purchase-document-logo">
                        @endif
                        <div>
                            <div class="purchase-document-company">{{ $empresa->nombre ?? 'AgroControl' }}</div>
                            <div class="purchase-document-company-meta">
                                RTN: {{ $empresa->rtn ?? 'N/D' }}
                                @if(!empty($empresa?->telefono))
                                    · Tel: {{ $empresa->telefono }}
                                @endif
                                @if(!empty($empresa?->direccion))
                                    · Dirección: {{ $empresa->direccion }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="purchase-document-title mb-2">SOLICITUD DE SUMINISTRO DE INSUMOS O MATERIALES</div>
                        <p class="text-muted mb-0">Código {{ $solicitud->codigo ?: 'SC-' . $solicitud->id }} · Estado {{ str_replace('_', ' ', $solicitud->estado) }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('compras.solicitudes.pdf', $solicitud) }}" class="btn btn-primary btn-sm">Descargar PDF</a>
                        <a href="{{ route('compras.solicitudes.pdf', ['solicitud' => $solicitud, 'inline' => 1]) }}" target="_blank" class="btn btn-outline-primary btn-sm">Imprimir PDF</a>
                        <a href="{{ route('compras.solicitudes.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                    </div>
                </div>

                <div class="mb-3 fs-5">
                    Sirva la presente para solicitar materiales para <strong>{{ $solicitud->asunto }}</strong>.
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6"><strong>Departamento solicitante:</strong> {{ $solicitud->departamento ?: 'General' }}</div>
                    <div class="col-md-6"><strong>Bodega destino:</strong> {{ $solicitud->bodegaDestino->nombre ?? 'Pendiente' }}</div>
                    <div class="col-md-6"><strong>Nombre de solicitante:</strong> {{ $solicitud->solicitante->nombre_completo ?? $solicitud->solicitante->usuario ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Fecha requerida:</strong> {{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'No definida' }}</div>
                    <div class="col-md-6"><strong>Prioridad:</strong> {{ ucfirst($solicitud->prioridad) }}</div>
                    <div class="col-md-6"><strong>Compra asignada:</strong> {{ $solicitud->gestorCompra->usuario ?? 'Pendiente' }}</div>
                </div>

                <div class="mb-3 fs-5">
                    A continuación, el detalle de los servicios, productos, insumos, pagos o materiales requeridos:
                </div>

                <div class="table-responsive agro-table-shell mb-4">
                    <table class="table table-hover purchase-document-table agro-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 8%;">No.</th>
                                <th>Descripción</th>
                                <th style="width: 16%;">Unidad</th>
                                <th style="width: 16%;">Cantidad</th>
                                <th style="width: 18%;">Precio est.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($solicitud->detalle_items_resolved as $index => $item)
                                <tr>
                                    <td>{{ number_format($index + 1, 2) }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $item['descripcion'] }}</div>
                                        <span class="agro-table-meta">{{ $item['unidad'] ?: 'Sin unidad' }}</span>
                                    </td>
                                    <td>{{ $item['unidad'] ?: 'N/A' }}</td>
                                    <td>{{ number_format((float) $item['cantidad'], 2) }}</td>
                                    <td>{{ $item['precio_estimado'] !== null ? number_format((float) $item['precio_estimado'], 2) : 'Pendiente' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mb-4">
                    <strong>Observaciones:</strong> {{ $solicitud->descripcion ?: 'Sin detalle adicional.' }}
                    @if($solicitud->observaciones_compra)
                        <br>
                        <strong>Seguimiento de compra:</strong> {{ $solicitud->observaciones_compra }}
                    @endif
                    @if($solicitud->motivo_rechazo)
                        <br>
                        <strong class="text-danger">Motivo de rechazo:</strong> <span class="text-danger">{{ $solicitud->motivo_rechazo }}</span>
                    @endif
                </div>

                <div class="mb-5">
                    {{ now()->translatedFormat('j \d\e F \d\e Y') }}
                </div>

                <div class="row g-5 text-center mt-2">
                    <div class="col-md-4">
                        <div class="purchase-document-signature fw-semibold">{{ $solicitud->solicitante->usuario ?? 'Pendiente' }}</div>
                        <div class="text-muted small">Firma de solicitante</div>
                    </div>
                    <div class="col-md-4">
                        <div class="purchase-document-signature fw-semibold">{{ $solicitud->aprobador->usuario ?? 'Pendiente gerencia' }}</div>
                        <div class="text-muted small">Vo. Bo. jefe o gerencia solicitante</div>
                    </div>
                    <div class="col-md-4">
                        <div class="purchase-document-signature fw-semibold">{{ $solicitud->aprobador->usuario ?? 'Pendiente gerencia' }}</div>
                        <div class="text-muted small">Autorización gerencial administrativa financiera</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection