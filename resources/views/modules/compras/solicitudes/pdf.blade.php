<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud de suministro de insumos o materiales</title>
    <style>
        @page { margin: 28px 34px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        .document-shell {
            border: 1px solid #d9e3dd;
            padding: 24px;
            background: #ffffff;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-logo-cell {
            width: 92px;
            vertical-align: top;
        }

        .header-info-cell {
            vertical-align: top;
        }

        .logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .brand {
            color: #0f4c3a;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .empresa {
            color: #0f4c3a;
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
        }

        .document-header {
            margin-bottom: 18px;
            border-bottom: 2px solid #0f4c3a;
            padding-bottom: 10px;
        }

        .document-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .document-meta {
            color: #6b7280;
            font-size: 11px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin: 18px 0 10px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .grid td {
            width: 50%;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .label {
            font-weight: 700;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        .items-table th {
            background: #0f4c3a;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .observaciones {
            margin-top: 16px;
            padding: 12px;
            border: 1px solid #d1d5db;
            min-height: 72px;
            background: #ffffff;
        }

        .signatures {
            width: 100%;
            margin-top: 48px;
            border-collapse: separate;
            border-spacing: 20px 0;
        }

        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #6b7280;
            padding-top: 6px;
            margin-top: 54px;
            font-weight: 700;
        }

        .signature-role {
            color: #6b7280;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="document-shell">
        <div class="document-header">
            <table class="header-table">
                <tr>
                    <td class="header-logo-cell">
                        @if(!empty($logoEmpresa))
                            <img src="{{ $logoEmpresa }}" class="logo" alt="Logo empresa">
                        @endif
                    </td>
                    <td class="header-info-cell">
                        <div class="brand">AgroControl</div>
                        <div class="empresa">{{ $empresa->nombre ?? 'Empresa agrícola' }}</div>
                        <div class="document-meta">
                            RTN: {{ $empresa->rtn ?? 'N/D' }}
                            @if(!empty($empresa?->telefono))
                                | Tel: {{ $empresa->telefono }}
                            @endif
                            @if(!empty($empresa?->direccion))
                                | Dirección: {{ $empresa->direccion }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
            <div class="document-title">SOLICITUD DE SUMINISTRO DE INSUMOS O MATERIALES</div>
            <div class="document-meta">Codigo {{ $solicitud->codigo ?: 'SC-' . $solicitud->id }} | Estado {{ str_replace('_', ' ', $solicitud->estado) }}</div>
        </div>

        <p>Sirva la presente para solicitar materiales para <strong>{{ $solicitud->asunto }}</strong>.</p>

        <table class="grid">
            <tr>
                <td><span class="label">Departamento solicitante:</span> {{ $solicitud->departamento ?: 'General' }}</td>
                <td><span class="label">Bodega destino:</span> {{ $solicitud->bodegaDestino->nombre ?? 'Pendiente' }}</td>
            </tr>
            <tr>
                <td><span class="label">Nombre de solicitante:</span> {{ $solicitud->solicitante->nombre_completo ?? $solicitud->solicitante->usuario ?? 'N/A' }}</td>
                <td><span class="label">Fecha requerida:</span> {{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'No definida' }}</td>
            </tr>
            <tr>
                <td><span class="label">Prioridad:</span> {{ ucfirst($solicitud->prioridad) }}</td>
                <td><span class="label">Compra asignada:</span> {{ $solicitud->gestorCompra->usuario ?? 'Pendiente' }}</td>
            </tr>
        </table>

        <div class="section-title">Detalle de materiales requeridos</div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No.</th>
                    <th>Descripcion</th>
                    <th style="width: 16%;">Unidad</th>
                    <th style="width: 16%;">Cantidad</th>
                    <th style="width: 18%;">Precio est.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitud->detalle_items_resolved as $index => $item)
                    <tr>
                        <td class="text-right">{{ number_format($index + 1, 2) }}</td>
                        <td>{{ $item['descripcion'] }}</td>
                        <td>{{ $item['unidad'] ?: 'N/A' }}</td>
                        <td class="text-right">{{ number_format((float) $item['cantidad'], 2) }}</td>
                        <td class="text-right">{{ $item['precio_estimado'] !== null ? number_format((float) $item['precio_estimado'], 2) : 'Pendiente' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Observaciones</div>
        <div class="observaciones">
            {{ $solicitud->descripcion ?: 'Sin detalle adicional.' }}
            @if($solicitud->observaciones_compra)

                Seguimiento de compra: {{ $solicitud->observaciones_compra }}
            @endif
            @if($solicitud->motivo_rechazo)

                Motivo de rechazo: {{ $solicitud->motivo_rechazo }}
            @endif
        </div>

        <p style="margin-top: 20px;">{{ now()->translatedFormat('j \\d\\e F \\d\\e Y') }}</p>

        <table class="signatures">
            <tr>
                <td>
                    <div class="signature-line">{{ $solicitud->solicitante->usuario ?? 'Pendiente' }}</div>
                    <div class="signature-role">Firma de solicitante</div>
                </td>
                <td>
                    <div class="signature-line">{{ $solicitud->aprobador->usuario ?? 'Pendiente gerencia' }}</div>
                    <div class="signature-role">Vo. Bo. jefe o gerencia solicitante</div>
                </td>
                <td>
                    <div class="signature-line">{{ $solicitud->aprobador->usuario ?? 'Pendiente gerencia' }}</div>
                    <div class="signature-role">Autorizacion gerencial administrativa financiera</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
