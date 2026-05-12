<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Lote y Cultivos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Lote y Cultivos</h2>
    <p>Lote: {{ $lote->nombre }} | Area total: {{ agro_number($areaTotal, 2) }} ha</p>
    <p>Area ocupada: {{ agro_number($areaOcupada, 2) }} ha | Disponible: {{ agro_number($areaDisponible, 2) }} ha</p>

    <h3>Cultivos activos</h3>
    <table>
        <thead>
            <tr>
                <th>Cultivo</th>
                <th>Variedad</th>
                <th class="right">Hectareas</th>
                <th class="right">Ocupacion %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cultivosData as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['variedad'] ?: '-' }}</td>
                    <td class="right">{{ agro_number((float)$fila['hectareas'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$fila['porcentaje'], 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="4">No hay cultivos asociados a este lote.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if(count($cultivosCerradosData ?? []))
        <h3 style="margin-top: 18px;">Cultivos cerrados</h3>
        <p>Estos cultivos ya no cuentan como area ocupada del lote.</p>
        <table>
            <thead>
                <tr>
                    <th>Cultivo</th>
                    <th>Variedad</th>
                    <th class="right">Area liberada</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($cultivosCerradosData ?? []) as $fila)
                    <tr>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['variedad'] ?: '-' }}</td>
                        <td class="right">{{ agro_number((float) $fila['hectareas'], 2) }}</td>
                        <td>{{ $fila['estado'] ?? 'Cerrado' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
