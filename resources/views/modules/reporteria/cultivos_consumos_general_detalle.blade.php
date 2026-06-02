@extends('layouts.main')

@section('titulo', 'Detalle mensual de consumos por cultivo')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <style>
        .reporteria-shell {
            --cg-green-dark: #145c43;
            --cg-green: #1d6b4f;
            --cg-green-soft: #dbf5e1;
            --cg-green-pale: #eefaf1;
            --cg-line: #cfe5d6;
            --cg-muted: #5f756a;
            --cg-shadow: 0 18px 45px rgba(20, 92, 67, 0.10);
            background:
                radial-gradient(circle at top left, rgba(29, 107, 79, 0.10), transparent 28%),
                linear-gradient(180deg, #f6fcf7 0%, #f5f2e9 100%);
            padding-bottom: 2rem;
        }

        .reporteria-table-card {
            border: 1px solid rgba(20, 92, 67, 0.10) !important;
            border-radius: 1.5rem !important;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--cg-shadow);
            overflow: hidden;
        }

        .reporteria-table-card .card-body {
            padding: 1.4rem 1.5rem;
        }

        .reporteria-table-card .card-header {
            padding: 1.35rem 1.5rem 0;
        }

        .pagetitle h1 {
            color: var(--cg-green-dark);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .card-title {
            color: var(--cg-green-dark);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .reporteria-table-responsive {
            border: 1px solid rgba(20, 92, 67, 0.10) !important;
            border-radius: 1.2rem !important;
            background: #fff;
        }

        .detalle-action-btn {
            border-color: rgba(29, 107, 79, 0.35);
            color: var(--cg-green);
            font-weight: 600;
        }

        .detalle-action-btn:hover,
        .detalle-action-btn:focus {
            border-color: var(--cg-green);
            background: var(--cg-green);
            color: #fff;
        }

        .consumos-general-table {
            min-width: {{ 320 + (count($meses) * 360) }}px;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .consumos-general-table thead tr:first-child th,
        .consumos-general-table thead tr:nth-child(2) th {
            background: linear-gradient(180deg, #1f6f52 0%, #145c43 100%);
            color: #fff;
            border: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.18);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            text-align: center;
            vertical-align: middle;
            font-size: 0.94rem;
            padding: 0.7rem 0.7rem;
        }

        .consumos-general-table td,
        .consumos-general-table th {
            white-space: nowrap;
            vertical-align: middle;
            border-right: 1px solid #d4e3d8;
            border-bottom: 1px solid #d4e3d8;
            padding: 0.62rem 0.7rem;
        }

        .consumos-general-table tr > *:last-child {
            border-right: 0;
        }

        .consumos-general-table tbody tr:nth-child(even) td:not(.descripcion-col) {
            background: #fbfefb;
        }

        .consumos-general-table .descripcion-col {
            position: sticky;
            left: 0;
            z-index: 3;
            background: linear-gradient(180deg, #fcfffc 0%, #f0f8f2 100%);
            color: var(--cg-green-dark);
            font-weight: 700;
            box-shadow: 8px 0 20px rgba(20, 92, 67, 0.05);
            min-width: 260px;
            max-width: 260px;
        }

        .consumos-general-table thead .descripcion-col {
            z-index: 4;
            background: linear-gradient(180deg, #1f6f52 0%, #145c43 100%);
            color: #fff;
            box-shadow: none;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .totales-row td {
            font-weight: 800;
            background: linear-gradient(180deg, #f3faf4 0%, #ebf5ee 100%);
            border-top: 1px solid #c7dbcd;
        }

        .desviacion-negativa {
            color: #d11a2a;
            font-weight: 800;
        }

        .desviacion-positiva {
            color: #0f7a4b;
            font-weight: 800;
        }

        @media (max-width: 767.98px) {
            .consumos-general-table .descripcion-col {
                position: static;
                min-width: 180px;
                max-width: none;
                box-shadow: none;
            }
        }
    </style>

    <div class="pagetitle">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h1>Detalle mensual de {{ $cultivoSeleccionado->nombre }}</h1>
                <p class="text-muted mb-0">Esta vista queda separada del resumen general y te permite abrir el desglose completo por categoría.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary detalle-action-btn">Volver al resumen general</a>
                <a href="{{ route('reporteria.cultivos.show', $cultivoSeleccionado->id) }}" class="btn btn-outline-secondary detalle-action-btn">Abrir ficha completa</a>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 reporteria-table-card mb-4">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="card-title mb-1">Detalle mensual</h5>
                <p class="text-muted mb-0">Haz clic en Ver desglose para abrir todo el detalle de la categoría, como una tabla dinámica.</p>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive border rounded reporteria-table-responsive">
                    <table class="table table-sm align-middle consumos-general-table mb-0">
                        <thead>
                            <tr>
                                <th rowspan="2" class="descripcion-col">Descripción</th>
                                @foreach($meses as $mes)
                                    <th colspan="3">{{ $mes['label'] }}</th>
                                @endforeach
                                <th colspan="4">Totales</th>
                            </tr>
                            <tr>
                                @foreach($meses as $mes)
                                    <th>Plan</th>
                                    <th>Real</th>
                                    <th>Desviación</th>
                                @endforeach
                                <th>Plan</th>
                                <th>Real</th>
                                <th>Desviación</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filas as $fila)
                                <tr>
                                    <td class="descripcion-col fw-semibold">{{ $fila['categoria'] }}</td>
                                    @foreach($meses as $mes)
                                        @php($datosMes = $fila['meses'][$mes['key']] ?? ['plan' => 0, 'real' => 0, 'desviacion' => 0])
                                        <td class="text-end">{{ agro_number($datosMes['plan'], 2) }}</td>
                                        <td class="text-end">{{ agro_number($datosMes['real'], 2) }}</td>
                                        <td class="text-end {{ $datosMes['desviacion'] < 0 ? 'desviacion-negativa' : ($datosMes['desviacion'] > 0 ? 'desviacion-positiva' : '') }}">{{ agro_number($datosMes['desviacion'], 2) }}</td>
                                    @endforeach
                                    <td class="text-end fw-semibold">{{ agro_number($fila['total_plan'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ agro_number($fila['total_real'], 2) }}</td>
                                    <td class="text-end fw-semibold {{ $fila['total_desviacion'] < 0 ? 'desviacion-negativa' : ($fila['total_desviacion'] > 0 ? 'desviacion-positiva' : '') }}">{{ agro_number($fila['total_desviacion'], 2) }}</td>
                                    <td class="text-center">
                                        <a
                                            href="{{ route('reporteria.cultivos.consumos-categoria.pagina', array_merge(['cultivo' => $cultivoSeleccionado->id], $filterQuery ?? [], ['categoria' => $fila['categoria']])) }}"
                                            class="btn btn-sm btn-success"
                                        >Ver desglose</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="totales-row">
                                <td class="descripcion-col">Total costo de producción</td>
                                @foreach($meses as $mes)
                                    @php($datosMes = $totales['meses'][$mes['key']] ?? ['plan' => 0, 'real' => 0, 'desviacion' => 0])
                                    <td class="text-end">{{ agro_number($datosMes['plan'], 2) }}</td>
                                    <td class="text-end">{{ agro_number($datosMes['real'], 2) }}</td>
                                    <td class="text-end {{ $datosMes['desviacion'] < 0 ? 'desviacion-negativa' : ($datosMes['desviacion'] > 0 ? 'desviacion-positiva' : '') }}">{{ agro_number($datosMes['desviacion'], 2) }}</td>
                                @endforeach
                                <td class="text-end">{{ agro_number($totales['plan'], 2) }}</td>
                                <td class="text-end">{{ agro_number($totales['real'], 2) }}</td>
                                <td class="text-end {{ $totales['desviacion'] < 0 ? 'desviacion-negativa' : ($totales['desviacion'] > 0 ? 'desviacion-positiva' : '') }}">{{ agro_number($totales['desviacion'], 2) }}</td>
                                <td class="text-center">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
