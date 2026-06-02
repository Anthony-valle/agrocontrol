<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Consumos por Categoria</title>
    <style>
        @page { size: legal landscape; margin: 18px 16px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1f2937; }
        h2 { margin: 0 0 6px; font-size: 16px; }
        .meta { margin-bottom: 10px; color: #4b5563; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        th { background: #f3f4f6; font-size: 7.5px; }
        .right { text-align: right; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <h2>Detalle de Consumos por Categoria</h2>
    <div class="meta">
        Cultivo: {{ $cultivo->nombre }} | Categoria: {{ $categoria }} | Generado: {{ now()->format('d/m/Y H:i') }}
        @if($selectedFecha)
            | Fecha: {{ \Carbon\Carbon::parse($selectedFecha)->format('d/m/Y') }}
        @endif
        @if($selectedActividad)
            | Actividad: {{ $selectedActividad }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="nowrap">Fecha</th>
                <th class="nowrap">Consumo</th>
                <th>Codigo</th>
                <th>Insumo</th>
                <th>Bodega</th>
                <th>Lote</th>
                <th>Categoria</th>
                <th class="right">Cantidad</th>
                <th class="nowrap">Unidad</th>
                <th class="right">Costo Unitario</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detallesCategoria as $detalle)
                @php($esManoObra = $detalle->categoria === 'Mano De Obra')
                <tr>
                    <td class="nowrap">{{ $detalle->fecha ? \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') : '-' }}</td>
                    <td class="nowrap">#{{ $detalle->consumo_id }}</td>
                    <td class="nowrap">{{ $esManoObra ? '-' : ($detalle->codigo ?? '-') }}</td>
                    <td>{{ $esManoObra ? '-' : ($detalle->insumo ?? '-') }}</td>
                    <td>{{ $detalle->bodega }}</td>
                    <td>{{ $esManoObra ? '-' : $detalle->lote }}</td>
                    <td>{{ $detalle->categoria }}</td>
                    <td class="right nowrap">{{ agro_number($detalle->cantidad, 2) }}</td>
                    <td class="nowrap">{{ $detalle->unidad_medida }}</td>
                    <td class="right nowrap">{{ agro_number($detalle->costo_unitario, 2) }}</td>
                    <td class="right nowrap">{{ agro_number($detalle->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11">No hay registros relacionados con esta categoria.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="10" class="right">Total</th>
                <th class="right">{{ agro_number($totalCategoria, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
