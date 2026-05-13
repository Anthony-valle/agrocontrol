<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Consumos</title>
    <style>
        @page { margin: 22px 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { margin-bottom: 14px; }
        .title { margin: 0; font-size: 20px; color: #16624f; }
        .meta { margin-top: 6px; color: #5b6470; font-size: 10px; }
        .summary { margin: 10px 0 14px; width: 100%; border-collapse: separate; border-spacing: 0; }
        .summary td { width: 25%; padding: 8px 10px; border: 1px solid #dbe5ec; background: #f7faf8; }
        .summary .label { display: block; font-size: 9px; text-transform: uppercase; color: #5b6470; margin-bottom: 3px; }
        .summary .value { font-size: 13px; font-weight: bold; color: #17324d; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #d9e2ec; padding: 6px; vertical-align: top; }
        th { background: #16624f; color: #fff; font-size: 9px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fbfd; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #6b7280; }
        .cell-title { font-weight: bold; color: #17324d; }
        .cell-meta { display: block; margin-top: 2px; color: #5b6470; font-size: 9px; }
        .w-date { width: 8%; }
        .w-place { width: 14%; }
        .w-insumo { width: 24%; }
        .w-lote { width: 18%; }
        .w-categoria { width: 10%; }
        .w-cantidad { width: 8%; }
        .w-subtotal { width: 10%; }
        tfoot td { background: #eaf5ef; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $total = 0;
        $lineas = $consumos->sum(fn ($consumo) => $consumo->detalles->count());
    @endphp

    <div class="header">
        <h2 class="title">Reporte de Consumos</h2>
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}
            @if(!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']))
                | Rango: {{ $filtros['fecha_inicio'] ?: '...' }} - {{ $filtros['fecha_fin'] ?: '...' }}
            @endif
        </div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="label">Registros</span>
                <span class="value">{{ agro_number($consumos->count()) }}</span>
            </td>
            <td>
                <span class="label">Lineas detalle</span>
                <span class="value">{{ agro_number($lineas) }}</span>
            </td>
            <td>
                <span class="label">Total consumo</span>
                <span class="value">{{ agro_number($consumos->sum('total'), 2) }} Lps</span>
            </td>
            <td>
                <span class="label">Promedio</span>
                <span class="value">{{ $consumos->count() > 0 ? agro_number($consumos->avg('total'), 2) : agro_number(0, 2) }} Lps</span>
            </td>
        </tr>
    </table>

    <table>
        <thead>
        <tr>
            <th class="w-date">Fecha</th>
            <th class="w-place">Lote / Cultivo</th>
            <th class="w-insumo">Insumo / Concepto</th>
            <th class="w-lote">Lote / Ingrediente activo</th>
            <th class="w-categoria">Categoría</th>
            <th class="w-cantidad right">Cantidad</th>
            <th class="center">Unidad</th>
            <th class="w-subtotal right">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @forelse($consumos as $consumo)
            @forelse($consumo->detalles as $detalle)
                @php
                    $total += (float)($detalle->subtotal ?? 0);
                    $categoria = trim((string) ($detalle->categoria ?? '-'));
                    $esManoObra = mb_strtolower($categoria) === 'mano de obra';
                    $codigo = $esManoObra ? '-' : ($detalle->insumo->codigo ?? '-');
                    $insumo = $esManoObra ? ($detalle->descripcion ?? '-') : ($detalle->insumo->nombre ?? $detalle->descripcion ?? '-');
                    $loteConsumido = $esManoObra ? '-' : ($detalle->lote ?? '-');
                    $ingredienteActivo = $esManoObra ? '-' : ($detalle->insumo->ingrediente_activo ?? $detalle->insumo->ingredientes_activo ?? '-');
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</td>
                    <td>
                        <span class="cell-title">{{ $consumo->cultivo->lote->nombre ?? '-' }}</span>
                        <span class="cell-meta">{{ $consumo->cultivo->nombre ?? '-' }}</span>
                    </td>
                    <td>
                        <span class="cell-title">{{ $insumo }}</span>
                        <span class="cell-meta">Codigo: {{ $codigo }}</span>
                    </td>
                    <td>
                        <span class="cell-title">{{ $loteConsumido }}</span>
                        <span class="cell-meta">{{ $ingredienteActivo }}</span>
                    </td>
                    <td>{{ $categoria !== '' ? $categoria : '-' }}</td>
                    <td class="right">{{ agro_number((float)($detalle->cantidad ?? 0), 2) }}</td>
                    <td class="center">{{ $detalle->unidad_medida ?? '-' }}</td>
                    <td class="right">{{ agro_number((float)($detalle->subtotal ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</td>
                    <td>
                        <span class="cell-title">{{ $consumo->cultivo->lote->nombre ?? '-' }}</span>
                        <span class="cell-meta">{{ $consumo->cultivo->nombre ?? '-' }}</span>
                    </td>
                    <td colspan="5" class="muted">Sin detalles</td>
                    <td class="center">-</td>
                    <td class="right">0.00</td>
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
            <td colspan="7" class="right">Total</td>
            <td class="right">{{ agro_number($total, 2) }}</td>
        </tr>
        </tfoot>
    </table>
</body>
</html>
