<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cosechas</title>
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
    <h2>Reporte de Cosechas</h2>
    <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    <p>Registros: {{ agro_number($totales['registros']) }} | Neta: {{ agro_number($totales['neta'], 2) }} | Rendimiento: {{ agro_number($totales['rendimiento'], 2) }}%</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cultivo</th>
                <th>Lote</th>
                <th class="right">Bruta</th>
                <th class="right">Descarte</th>
                <th class="right">Neta</th>
                <th class="right">Disponible</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cosechas as $cosecha)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                    <td>{{ $cosecha->cultivo->nombre ?? '-' }}</td>
                    <td>{{ $cosecha->cultivo->lote->nombre ?? '-' }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->cantidad_bruta, 2) }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->descarte, 2) }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->cantidad_neta, 2) }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->cantidad_disponible, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
