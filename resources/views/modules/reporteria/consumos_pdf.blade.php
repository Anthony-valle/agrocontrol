<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Consumos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 6px; }
        .meta { margin-bottom: 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Consumos</h2>
    <div class="meta">
        Generado: {{ now()->format('d/m/Y H:i') }}
        @if(!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']))
            | Rango: {{ $filtros['fecha_inicio'] ?: '...' }} - {{ $filtros['fecha_fin'] ?: '...' }}
        @endif
    </div>

    <table>
        <thead>
        <tr>
            <th>Fecha</th>
            <th>Lote</th>
            <th>Cultivo</th>
            <th>Insumo</th>
            <th>Categoría</th>
            <th>Cantidad</th>
            <th>Unidad</th>
            <th class="right">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @php($total = 0)
        @forelse($consumos as $consumo)
            @forelse($consumo->detalles as $detalle)
                @php($total += (float)($detalle->subtotal ?? 0))
                <tr>
                    <td>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</td>
                    <td>{{ $consumo->cultivo->lote->nombre ?? '-' }}</td>
                    <td>{{ $consumo->cultivo->nombre ?? '-' }}</td>
                    <td>{{ $detalle->insumo->nombre ?? '-' }}</td>
                    <td>{{ $detalle->categoria ?? '-' }}</td>
                    <td>{{ agro_number((float)($detalle->cantidad ?? 0), 2) }}</td>
                    <td>{{ $detalle->unidad_medida ?? '-' }}</td>
                    <td class="right">{{ agro_number((float)($detalle->subtotal ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</td>
                    <td>{{ $consumo->cultivo->lote->nombre ?? '-' }}</td>
                    <td>{{ $consumo->cultivo->nombre ?? '-' }}</td>
                    <td colspan="5">Sin detalles</td>
                </tr>
            @endforelse
        @empty
            <tr>
                <td colspan="8">No hay datos para los filtros seleccionados.</td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <th colspan="7" class="right">Total</th>
            <th class="right">{{ agro_number($total, 2) }}</th>
        </tr>
        </tfoot>
    </table>
</body>
</html>
