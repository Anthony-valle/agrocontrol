<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Consumos por Categoria</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h2 { margin: 0 0 6px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
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
                <th>Fecha</th>
                <th>Consumo</th>
                <th>Estado</th>
                <th>Codigo</th>
                <th>Insumo</th>
                <th>Bodega</th>
                <th>Lote</th>
                <th>Categoria</th>
                <th>Descripcion</th>
                <th class="right">Cantidad</th>
                <th>Unidad</th>
                <th class="right">Costo Unitario</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detallesCategoria as $detalle)
                @php($esManoObra = $detalle->categoria === 'Mano De Obra')
                <tr>
                    <td>{{ $detalle->fecha ? \Carbon\Carbon::parse($detalle->fecha)->format('d/m/Y') : '-' }}</td>
                    <td>#{{ $detalle->consumo_id }}</td>
                    <td>{{ $detalle->estado }}</td>
                    <td>{{ $esManoObra ? '-' : ($detalle->codigo ?? '-') }}</td>
                    <td>{{ $esManoObra ? '-' : ($detalle->insumo ?? '-') }}</td>
                    <td>{{ $detalle->bodega }}</td>
                    <td>{{ $esManoObra ? '-' : $detalle->lote }}</td>
                    <td>{{ $detalle->categoria }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td class="right">{{ agro_number($detalle->cantidad, 2) }}</td>
                    <td>{{ $detalle->unidad_medida }}</td>
                    <td class="right">{{ agro_number($detalle->costo_unitario, 2) }}</td>
                    <td class="right">{{ agro_number($detalle->subtotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No hay registros relacionados con esta categoria.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="12" class="right">Total</th>
                <th class="right">{{ agro_number($totalCategoria, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
