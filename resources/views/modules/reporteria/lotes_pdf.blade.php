<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Lotes</title>
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
    <h2>Reporte de Lotes</h2>
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    <p>Lotes: {{ agro_number($metricas['lotes']) }} | Area total: {{ agro_number($metricas['area_total'], 2) }} | Cultivos: {{ agro_number($metricas['cultivos']) }}</p>

    <table>
        <thead>
            <tr>
                <th>Lote</th>
                <th>Sucursal</th>
                <th>Estado</th>
                <th class="right">Area</th>
                <th class="right">Cultivos</th>
                <th class="right">Neta</th>
                <th class="right">Disponible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['sucursal'] }}</td>
                    <td>{{ $fila['estado'] }}</td>
                    <td class="right">{{ agro_number((float)$fila['area'], 2) }}</td>
                    <td class="right">{{ agro_number((int)$fila['cultivos']) }}</td>
                    <td class="right">{{ agro_number((float)$fila['cosecha_neta'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$fila['disponible'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin lotes para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
