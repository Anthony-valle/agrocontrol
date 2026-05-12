<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Lote</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 5px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Detalle de Lote: {{ $lote->nombre }}</h2>
    <p>Sucursal: {{ $lote->sucursal->nombre ?? '-' }} | Codigo: {{ $lote->codigo ?: '-' }}</p>

    <h3>Cultivos del lote</h3>
    <table>
        <thead>
            <tr>
                <th>Cultivo</th>
                <th>Estado</th>
                <th>Siembra</th>
                <th class="right">Hectareas</th>
                <th class="right">Inversion</th>
                <th class="right">Neta</th>
                <th class="right">Disponible</th>
                <th class="right">Ventas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cultivos as $cultivo)
                <tr>
                    <td>{{ $cultivo['nombre'] }}</td>
                    <td>{{ $cultivo['estado'] }}</td>
                    <td>{{ $cultivo['fecha_siembra'] ? \Carbon\Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-' }}</td>
                    <td class="right">{{ agro_number((float)$cultivo['hectareas'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$cultivo['inversion'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$cultivo['cosecha_neta'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$cultivo['disponible'], 2) }}</td>
                    <td class="right">{{ agro_number((float)$cultivo['ventas'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Sin cultivos asociados al lote.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Ultimas cosechas</h3>
    <table>
        <thead><tr><th>Fecha</th><th>Cultivo</th><th class="right">Neta</th><th class="right">Disponible</th></tr></thead>
        <tbody>
            @forelse($cosechas as $cosecha)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                    <td>{{ $cosecha->cultivo->nombre ?? '-' }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->cantidad_neta, 2) }}</td>
                    <td class="right">{{ agro_number((float)$cosecha->cantidad_disponible, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin cosechas registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
