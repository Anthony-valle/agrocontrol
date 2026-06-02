@extends('layouts.main')

@section('titulo', 'Reporte general de consumos por cultivo')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <style>
        .reporteria-shell {
            --cg-navy: #123b67;
            --cg-blue: #1f6b8f;
            --cg-blue-soft: #e7f4fb;
            --cg-ink: #16324d;
            --cg-muted: #62748a;
            --cg-line: #d8e5ef;
            --cg-panel: #ffffff;
            --cg-sand: #f7f5ef;
            --cg-shadow: 0 18px 45px rgba(18, 59, 103, 0.08);
            background:
                radial-gradient(circle at top left, rgba(31, 107, 143, 0.09), transparent 28%),
                linear-gradient(180deg, #f7fbfd 0%, #f5f2e9 100%);
            padding-bottom: 2rem;
        }

        .pagetitle {
            margin-bottom: 1.5rem;
        }

        .pagetitle h1 {
            color: var(--cg-navy);
            font-size: clamp(2rem, 3vw, 2.7rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            margin-bottom: 0.25rem;
        }

        .pagetitle p {
            color: var(--cg-muted) !important;
            font-size: 1.08rem;
            max-width: 72rem;
        }

        .reporteria-filter-card,
        .reporteria-table-card,
        .cultivo-resumen-card {
            border: 1px solid rgba(18, 59, 103, 0.08) !important;
            border-radius: 1.2rem !important;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--cg-shadow);
            backdrop-filter: blur(8px);
            overflow: hidden;
        }

        .reporteria-filter-card .card-body,
        .reporteria-table-card .card-body,
        .cultivo-resumen-card .card-body {
            padding: 1.4rem 1.5rem;
        }

        .reporteria-table-card .card-header,
        .cultivo-resumen-card .card-header {
            padding: 1.35rem 1.5rem 0;
        }

        .card-title {
            color: var(--cg-navy);
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .reporteria-filter-card .form-label {
            color: var(--cg-ink);
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: none;
            margin-bottom: 0.45rem;
        }

        .reporteria-filter-card .form-control,
        .reporteria-filter-card .form-select,
        .input-group-text,
        #inputBusqueda {
            min-height: 2.45rem;
            border-radius: 0.65rem !important;
            border-color: var(--cg-line);
            box-shadow: none !important;
        }

        .reporteria-filter-card .form-control:focus,
        .reporteria-filter-card .form-select:focus,
        #inputBusqueda:focus {
            border-color: rgba(31, 107, 143, 0.55);
        }

        .reporteria-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .reporteria-actions .btn,
        .cultivo-resumen-card .btn {
            min-height: 2.5rem;
            border-radius: 0.55rem;
            padding-inline: 1rem;
            font-weight: 600;
            box-shadow: none;
        }

        .reporteria-actions .btn-primary,
        .cultivo-selector-row .btn-primary {
            border: 0;
            background: linear-gradient(135deg, #1b78c2 0%, #2b8bff 100%);
        }

        .reporteria-actions .btn-success {
            border: 0;
            background: linear-gradient(135deg, #0d8a5c 0%, #19a974 100%);
        }

        .reporteria-filter-card .card-body {
            padding: 1.15rem 1.35rem;
        }

        .reporteria-filter-form {
            row-gap: 0.9rem !important;
        }

        .consumos-general-card {
            position: relative;
            border: 1px solid rgba(18, 59, 103, 0.08);
            border-radius: 1.35rem;
            background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(244,249,252,0.98) 100%);
            box-shadow: var(--cg-shadow);
            overflow: hidden;
        }

        .consumos-general-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 4px;
            background: linear-gradient(90deg, #1f6b8f 0%, #27a2c8 100%);
        }

        .consumos-general-card small {
            color: var(--cg-muted) !important;
            letter-spacing: 0.08em;
        }

        .consumos-general-card .fs-4,
        .consumos-general-card .fs-5 {
            color: var(--cg-ink);
            letter-spacing: -0.04em;
        }

        .consumos-general-note {
            border: 1px solid rgba(31, 107, 143, 0.12);
            border-left: 6px solid var(--cg-blue);
            border-radius: 1rem;
            background: linear-gradient(180deg, rgba(243,249,252,0.95) 0%, rgba(255,255,255,0.95) 100%);
            color: var(--cg-ink) !important;
            padding: 1.1rem 1.25rem;
        }

        .reporteria-table-responsive {
            border: 1px solid rgba(15, 90, 67, 0.14) !important;
            border-radius: 0 0 1rem 1rem !important;
            background: #fff;
        }

        .consumos-general-resumen-table {
            min-width: {{ 280 + (max(count($resumenGeneral['cultivos']), 1) * 270) }}px;
        }

        .consumos-general-table {
            min-width: {{ 320 + (count($meses) * 360) }}px;
        }

        .consumos-general-resumen-table,
        .consumos-general-table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .consumos-general-resumen-table thead tr:first-child th,
        .consumos-general-resumen-table thead tr:nth-child(2) th,
        .consumos-general-table thead tr:first-child th,
        .consumos-general-table thead tr:nth-child(2) th {
            background: #17684b;
            color: #fff;
            border: 0;
            text-align: center;
            vertical-align: middle;
            font-size: 0.94rem;
            padding: 0.7rem 0.7rem;
        }

        .consumos-general-resumen-table thead tr:first-child th:first-child,
        .consumos-general-table thead tr:first-child th:first-child {
            border-top-left-radius: 0.9rem;
        }

        .consumos-general-resumen-table thead tr:first-child th:last-child,
        .consumos-general-table thead tr:first-child th:last-child {
            border-top-right-radius: 0.9rem;
        }

        .consumos-general-resumen-table td,
        .consumos-general-resumen-table th,
        .consumos-general-table td,
        .consumos-general-table th {
            white-space: nowrap;
            vertical-align: middle;
            border-color: #d7e3dc;
            padding: 0.62rem 0.7rem;
        }

        .consumos-general-resumen-table tbody tr:nth-child(even) td:not(.descripcion-col),
        .consumos-general-table tbody tr:nth-child(even) td:not(.descripcion-col) {
            background: #fbfdfe;
        }

        .consumos-general-resumen-table .descripcion-col,
        .consumos-general-table .descripcion-col {
            position: sticky;
            left: 0;
            z-index: 3;
            background: linear-gradient(180deg, #fdfefe 0%, #f4f8fb 100%);
            color: var(--cg-ink);
            font-weight: 700;
            box-shadow: 8px 0 20px rgba(18, 59, 103, 0.04);
        }

        .consumos-general-resumen-table .descripcion-col {
            min-width: 220px;
            max-width: 220px;
        }

        .consumos-general-table .descripcion-col {
            min-width: 260px;
            max-width: 260px;
        }

        .consumos-general-resumen-table thead .descripcion-col,
        .consumos-general-table thead .descripcion-col {
            z-index: 4;
            background: #17684b;
            color: #fff;
            box-shadow: none;
        }

        .consumos-general-section-row td {
            font-weight: 800;
            background: linear-gradient(90deg, #eef7f2 0%, #f8fbf9 100%);
            color: var(--cg-navy);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .consumos-general-meta td {
            background: #fff;
        }

        .consumos-general-meta .descripcion-col {
            background: #f8fbfd;
        }

        .totales-row td {
            font-weight: 800;
            background: linear-gradient(180deg, #f3f7f9 0%, #edf4f7 100%);
        }

        .totales-row .descripcion-col {
            color: var(--cg-navy);
        }

        .desviacion-negativa {
            color: #d11a2a;
            font-weight: 800;
        }

        .desviacion-positiva {
            color: #0f7a4b;
            font-weight: 800;
        }

        .cultivo-selector-row {
            cursor: pointer;
            transition: background-color 0.18s ease;
        }

        .cultivo-selector-row:hover td {
            background: #eef7fb !important;
        }

        .cultivo-resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
        }

        .cultivo-resumen-item {
            border: 1px solid var(--cg-line);
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            padding: 0.95rem 1rem;
        }

        .cultivo-resumen-item small {
            display: block;
            color: var(--cg-muted);
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.05em;
            margin-bottom: 0.35rem;
            font-size: 0.72rem;
        }

        .cultivo-resumen-item strong {
            display: block;
            color: var(--cg-ink);
            font-size: 1.02rem;
            letter-spacing: -0.03em;
        }

        .badge {
            border-radius: 999px;
            padding: 0.5rem 0.85rem;
            font-weight: 700;
        }

        @media (max-width: 991.98px) {
            .reporteria-actions {
                width: 100%;
            }

            .reporteria-actions .btn {
                flex: 1 1 auto;
            }
        }

        @media (max-width: 767.98px) {
            .reporteria-shell {
                padding-bottom: 1rem;
            }

            .pagetitle h1 {
                font-size: 1.8rem;
            }

            .reporteria-filter-card .card-body,
            .reporteria-table-card .card-body,
            .cultivo-resumen-card .card-body {
                padding: 1rem;
            }

            .reporteria-table-card .card-header,
            .cultivo-resumen-card .card-header {
                padding: 1rem 1rem 0;
            }

            .consumos-general-resumen-table .descripcion-col,
            .consumos-general-table .descripcion-col {
                position: static;
                min-width: 180px;
                max-width: none;
                box-shadow: none;
            }
        }
    </style>


    <div class="pagetitle">
        <h1>Reporte general de consumos por cultivo</h1>
        <p class="text-muted mb-0">Selecciona un cultivo activo para ver su detalle completo por categoría, lote y meses.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 reporteria-filter-card">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.cultivos.consumos-general') }}" class="row g-3 align-items-end reporteria-filter-form">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Lote</label>
                        <select name="lote_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}" {{ request('lote_id') == $lote->id ? 'selected' : '' }}>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="Activo" {{ request('estado') === 'Activo' ? 'selected' : '' }}>Activo</option>
                            <option value="cerrado" {{ request('estado') === 'cerrado' ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                    </div>
                    <div class="col-md-3 reporteria-actions">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('reporteria.cultivos.consumos-general') }}" class="btn btn-outline-secondary">Limpiar</a>
                        <a href="{{ route('reporteria.cultivos.consumos-general.excel', request()->except('cultivo_id')) }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="alert consumos-general-note text-muted mb-4">
            Primero ves el resumen general de todos los cultivos filtrados. Luego eliges uno para abrir su detalle completo en una pantalla aparte, y ese mismo detalle también sale en Excel.
        </div>

        <div class="card shadow-sm border-0 reporteria-table-card mb-4">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="card-title mb-0">Resumen general de cultivos</h5>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive border rounded reporteria-table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0" id="tablaCultivosActivos">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Lote</th>
                                <th class="text-end">Área (Has)</th>
                                <th>Estado</th>
                                <th>Siembra</th>
                                <th class="text-end">Plan total</th>
                                <th class="text-end">Real total</th>
                                <th class="text-end">Desviación</th>
                                <th class="text-end">Desviación %</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cultivosActivos as $cultivo)
                                <tr
                                    class="cultivo-selector-row"
                                    data-url="{{ route('reporteria.cultivos.consumos-general.detalle', array_merge(['cultivo' => $cultivo['id']], request()->except('page', 'cultivo_id'))) }}"
                                >
                                    <td>{{ $cultivo['codigo'] ?: '-' }}</td>
                                    <td>
                                        <a href="{{ route('reporteria.cultivos.show', $cultivo['id']) }}" class="fw-semibold text-decoration-none">{{ $cultivo['nombre'] }}</a>
                                    </td>
                                    <td>{{ $cultivo['lote'] }}</td>
                                    <td class="text-end">{{ $cultivo['hectareas'] !== null ? agro_number($cultivo['hectareas'], 2) : '-' }}</td>
                                    <td>
                                        <span class="badge {{ $cultivo['estado'] === 'Activo' ? 'bg-success' : 'bg-danger' }}">{{ $cultivo['estado'] }}</span>
                                    </td>
                                    <td>{{ $cultivo['fecha_siembra'] ? \Carbon\Carbon::parse($cultivo['fecha_siembra'])->format('d/m/Y') : '-' }}</td>
                                    <td class="text-end fw-semibold">{{ agro_number($cultivo['total_plan'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ agro_number($cultivo['total_real'], 2) }}</td>
                                    <td class="text-end fw-semibold {{ $cultivo['desviacion'] < 0 ? 'desviacion-negativa' : ($cultivo['desviacion'] > 0 ? 'desviacion-positiva' : '') }}">{{ agro_number($cultivo['desviacion'], 2) }}</td>
                                    <td class="text-end fw-semibold">{{ $cultivo['porcentaje'] !== null ? agro_number($cultivo['porcentaje'], 2) : '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('reporteria.cultivos.consumos-general.detalle', array_merge(['cultivo' => $cultivo['id']], request()->except('page', 'cultivo_id'))) }}" class="btn btn-sm btn-primary">Ver completo</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center text-muted py-4">No hay cultivos activos para los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBusqueda = document.getElementById('inputBusqueda');
    const cultivoRows = document.querySelectorAll('.cultivo-selector-row[data-url]');

    cultivoRows.forEach(function (fila) {
        fila.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea')) {
                return;
            }

            const url = fila.dataset.url;

            if (url) {
                window.location.href = url;
            }
        });
    });

    if (!inputBusqueda) {
        return;
    }

    const tablaCultivos = document.querySelector('#tablaCultivosActivos tbody');

    if (!tablaCultivos) {
        return;
    }

    const filas = Array.from(tablaCultivos.querySelectorAll('tr'));

    inputBusqueda.addEventListener('input', function () {
        const termino = this.value.trim().toLowerCase();

        filas.forEach(function (fila) {
            const texto = fila.innerText.toLowerCase();
            fila.style.display = termino === '' || texto.includes(termino) ? '' : 'none';
        });
    });
});
</script>
@endpush
