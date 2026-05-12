<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Inventario</h2>
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Insumo</th>
                <th>Bodega</th>
                <th>Lote</th>
                <th class="right">Stock</th>
                <th class="right">Costo</th>
                <th>Vence</th>
            </tr>
        </thead>
        <tbody>
            @php($total = 0)
            @forelse($inventarios as $item)
                @php($total += ((float)$item->stock_actual * (float)$item->costo_promedio))
                <tr>
                    <td>{{ $item->insumo->nombre ?? '-' }}</td>
                    <td>{{ $item->bodega->nombre ?? '-' }}</td>
                    <td>{{ $item->numero_lote ?: '-' }}</td>
                    <td class="right">{{ agro_number((float)$item->stock_actual, 2) }}</td>
                    <td class="right">{{ agro_number((float)$item->costo_promedio, 2) }}</td>
                    <td>{{ $item->fecha_vencimiento ? \Carbon\Carbon::parse($item->fecha_vencimiento)->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="right">Valor total estimado</th>
                <th class="right">{{ agro_number($total, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
