<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cultivos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 10px; color: #4b5563; }
        .cards { margin-bottom: 10px; }
        .cards span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Cultivos</h2>
    <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    <div class="cards">
        <span>Registros: <strong>{{ agro_number($metricas['registros']) }}</strong></span>
        <span>Activos: <strong>{{ agro_number($metricas['activos']) }}</strong></span>
        <span>Inversión: <strong>{{ agro_number($metricas['inversion'], 2) }}</strong></span>
        <span>Ingresos: <strong>{{ agro_number($metricas['ingresos'], 2) }}</strong></span>
    </div>

    <table>
        <thead>
        <tr>
            <th>Cultivo</th>
            <th>Lote</th>
            <th>Estado</th>
            <th>Siembra</th>
            <th class="right">Producción</th>
            <th class="right">Disponible</th>
            <th class="right">Inversión</th>
            <th class="right">Ingresos</th>
            <th class="right">Utilidad</th>
        </tr>
        </thead>
        <tbody>
        @forelse($cultivos as $cultivo)
            <tr>
                <td>{{ $cultivo['nombre'] }}</td>
                <td>{{ $cultivo['lote'] }}</td>
                <td>{{ $cultivo['estado'] }}</td>
                <td>{{ $cultivo['fecha_siembra'] ? \Carbon\Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-' }}</td>
                <td class="right">{{ agro_number($cultivo['produccion'], 2) }}</td>
                <td class="right">{{ agro_number($cultivo['disponible'], 2) }}</td>
                <td class="right">{{ agro_number($cultivo['inversion'], 2) }}</td>
                <td class="right">{{ agro_number($cultivo['ingresos'], 2) }}</td>
                <td class="right">{{ agro_number($cultivo['utilidad'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="9">Sin datos para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
