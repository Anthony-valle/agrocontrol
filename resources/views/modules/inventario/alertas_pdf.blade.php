<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Alertas de Inventario</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 6px; }
        h4 { margin: 14px 0 6px; }
        .header { border-bottom: 2px solid #0f4c3a; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-logo-cell { width: 84px; vertical-align: top; }
        .header-info-cell { vertical-align: top; }
        .logo { width: 68px; height: 68px; object-fit: contain; }
        .brand { color: #0f4c3a; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .empresa { color: #0f4c3a; font-size: 19px; font-weight: 700; }
        .meta { color: #6b7280; font-size: 10px; }
        .bloque-reporte { background: #f3f4f6; border-left: 4px solid #0f4c3a; padding: 7px 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; font-size: 10px; }
        th { background: #f3f4f6; }
        .footer { margin-top: 14px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">
                    @if(!empty($logoPdf))
                        <img src="{{ $logoPdf }}" class="logo" alt="Logo AgroControl">
                    @endif
                </td>
                <td class="header-info-cell">
                    <div class="brand">AgroControl</div>
                    <div class="empresa">{{ $empresa->nombre ?? 'Empresa agrícola' }}</div>
                    <div class="meta">
                        RTN: {{ $empresa->rtn ?? 'N/D' }} |
                        Tel: {{ $empresa->telefono ?? 'N/D' }} |
                        Dirección: {{ $empresa->direccion ?? 'N/D' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="bloque-reporte">
        <h2>Reporte de Alertas de Inventario</h2>
        <div>
            Generado: {{ now()->format('d/m/Y H:i') }}
            | Filtro: {{ $tipoAlerta === 'stock_bajo' ? 'Insumos con stock bajo' : ($tipoAlerta === 'vencimientos' ? 'Insumos con vencimientos cercanos' : 'Todos') }}
        </div>
    </div>

    @if(!empty($pdfTruncado) && $pdfTruncado)
        <p>
            Nota: Se muestran las primeras {{ ($totalFilas ?? 0) - ($restantes ?? 0) }} alertas de {{ $totalFilas ?? 0 }} para asegurar la generación del PDF.
            Quedaron {{ $restantes ?? 0 }} alertas fuera de este archivo.
        </p>
    @endif

    @if($bajos->isNotEmpty())
        <h4>Tabla de Stock Bajo</h4>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th>Lote</th>
                    <th>Stock actual</th>
                    <th>Stock mínimo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bajos as $fila)
                    <tr>
                        <td>{{ $fila->insumo_codigo }}</td>
                        <td>{{ $fila->insumo_nombre }}</td>
                        <td>{{ $fila->lote_codigo }}</td>
                        <td>{{ $fila->stock_actual }}</td>
                        <td>{{ $fila->stock_minimo }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($vencimientos->isNotEmpty())
        <h4>Tabla de Vencimientos</h4>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Insumo</th>
                    <th>Lote</th>
                    <th>Fecha vencimiento</th>
                    <th>Días restantes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vencimientos as $fila)
                    <tr>
                        <td>{{ $fila->insumo_codigo }}</td>
                        <td>{{ $fila->insumo_nombre }}</td>
                        <td>{{ $fila->lote_codigo }}</td>
                        <td>{{ $fila->fecha_vencimiento }}</td>
                        <td>{{ $fila->dias_restantes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($bajos->isEmpty() && $vencimientos->isEmpty())
        <p>Sin alertas para el filtro seleccionado.</p>
    @endif

    <div class="footer">
        Documento generado automáticamente por AgroControl | {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
