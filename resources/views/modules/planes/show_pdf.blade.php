<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plan de Cultivo #{{ $plan->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 6px; }
        .meta { margin-bottom: 12px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Plan de Cultivo #{{ $plan->id }}</h2>
    <div class="meta">
        Cultivo: {{ $plan->cultivo->nombre ?? '-' }} |
        Fecha plan: {{ $plan->fecha_plan }} |
        Estado: {{ $plan->estado }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Semana Cultivo</th>
                <th>Categoria</th>
                <th>Descripcion</th>
                <th>Cantidad</th>
                <th>Unidad</th>
                <th class="right">Costo Unitario</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php($total = 0)
            @foreach($plan->detalles->sortBy('semana') as $detalle)
                @php($subtotal = (float) ($detalle->subtotal ?? ($detalle->cantidad_estimada * $detalle->costo_unitario)))
                @php($total += $subtotal)
                <tr>
                    <td>{{ $detalle->semana }}</td>
                    <td>{{ $detalle->categoria }}</td>
                    <td>{{ $detalle->descripcion }}</td>
                    <td>{{ agro_number((float) $detalle->cantidad_estimada, 3) }}</td>
                    <td>{{ $detalle->unidad_medida }}</td>
                    <td class="right">{{ agro_number((float) $detalle->costo_unitario, 3) }}</td>
                    <td class="right">{{ agro_number($subtotal, 3) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="right">Total presupuesto</th>
                <th class="right">{{ agro_number($total, 3) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>