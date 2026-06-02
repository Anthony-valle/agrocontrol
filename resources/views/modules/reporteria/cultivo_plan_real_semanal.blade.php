@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <style>
        .plan-real-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(15, 90, 67, 0.08);
            border-radius: 12px;
            background: #f8faf9;
            box-shadow: 0 4px 12px rgba(15, 90, 67, 0.06);
        }

        .plan-real-toolbar-copy {
            display: grid;
            gap: 0.2rem;
        }

        .plan-real-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #153243;
        }

        .plan-real-subtitle {
            color: #5f6c7b;
            font-size: 0.88rem;
        }

        .plan-real-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.9rem;
        }

        .plan-real-summary-card {
            border: 1px solid rgba(15, 90, 67, 0.1);
            border-radius: 16px;
            padding: 1rem 1.1rem;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 90, 67, 0.08);
        }

        .plan-real-summary-card .label {
            color: #6c757d;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
            font-weight: 700;
        }

        .plan-real-summary-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #132a13;
            margin-bottom: 0;
        }

        .plan-real-summary-card.is-alert .value {
            color: #b42318;
        }

        .plan-real-summary-card.is-warning .value {
            color: #c66a12;
        }

        .plan-real-summary-card.is-info .value {
            color: #175cd3;
        }

        .plan-real-filter-card,
        .plan-real-table-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 90, 67, 0.08);
            background: #fff;
        }

        .plan-real-filter-card .card-body {
            padding: 1rem 1rem 0.9rem;
        }

        .plan-real-filter-label {
            color: #344054;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 0.45rem;
        }

        .plan-real-filter-card .form-select,
        .plan-real-filter-card .form-control {
            min-height: 40px;
        }

        .plan-real-filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .plan-real-table-card .card-header {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid rgba(15, 90, 67, 0.08);
            background: #fff !important;
            color: #344054;
            font-size: 1rem;
        }

        .plan-real-table-card .card-body {
            padding: 1rem;
        }

        .plan-real-table th {
            white-space: nowrap;
            vertical-align: middle;
            border-color: rgba(21, 50, 67, 0.08);
        }

        .plan-real-table td {
            vertical-align: top;
            border-color: #d7e3dc;
        }

        .plan-real-detail-list {
            margin: 0;
            padding-left: 1rem;
            font-size: 0.86rem;
            color: #5f6c7b;
        }

        .plan-real-detail-list li + li {
            margin-top: 0.35rem;
        }

        .plan-real-mini-table td,
        .plan-real-mini-table th {
            padding: 0.45rem 0.6rem;
            font-size: 0.88rem;
        }

        .plan-real-empty {
            border: 1px dashed rgba(22, 98, 79, 0.25);
            border-radius: 0.85rem;
            padding: 1rem;
            text-align: center;
            color: #52616b;
            background: #f8faf9;
        }

        .plan-real-pagination-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 12px;
            background: #f8faf9;
            border: 1px solid rgba(15, 90, 67, 0.08);
        }

        .plan-real-pagination-meta,
        .plan-real-pagination-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }

        .plan-real-pagination-status {
            min-width: 150px;
            text-align: center;
            font-size: 0.9rem;
            color: #52616b;
            font-weight: 600;
        }

        .plan-real-main-table-shell {
            border: 1px solid rgba(15, 90, 67, 0.14);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        @media (max-width: 767.98px) {
            .plan-real-pagination-toolbar {
                align-items: stretch;
            }

            .plan-real-pagination-meta,
            .plan-real-pagination-controls {
                justify-content: space-between;
                width: 100%;
            }

            .plan-real-toolbar {
                align-items: stretch;
            }
        }
    </style>

    @php
        $estadoClasses = [
            'Coincide' => 'bg-success-subtle text-success',
            'Plan sin real' => 'bg-danger-subtle text-danger',
            'Real sin plan' => 'bg-warning-subtle text-warning-emphasis',
            'Diferencia de cantidad' => 'bg-primary-subtle text-primary',
            'Diferencia de costo' => 'bg-info-subtle text-info-emphasis',
            'Diferencia de cantidad y costo' => 'bg-dark-subtle text-dark',
        ];
    @endphp

    <div class="pagetitle">
        <h1>Comparacion semanal de cantidad de insumos: Plan vs Real</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('cultivo.index') }}">Cultivos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('reporte.cultivo.final', $cultivo->id) }}">Reporte final</a></li>
                <li class="breadcrumb-item active">Comparacion semanal de insumos</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="plan-real-toolbar">
            <div class="plan-real-toolbar-copy">
                <h5 class="plan-real-title">{{ $cultivo->nombre }}</h5>
                <div class="plan-real-subtitle">
                    Plan base: {{ $plan ? '#' . $plan->id : 'Sin plan registrado' }}
                    @if($plan?->fecha_plan)
                        | Fecha plan: {{ \Carbon\Carbon::parse($plan->fecha_plan)->format('d/m/Y') }}
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('reporte.cultivo.final', $cultivo->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Volver al reporte
                </a>
                <a href="{{ route('reporte.cultivo.plan-real-semanal.excel', $cultivo->id) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                </a>
            </div>
        </div>

        @if(! $plan)
            <div class="alert alert-warning">El cultivo no tiene un plan registrado. El reporte solo mostrará aplicaciones reales no planificadas.</div>
        @endif

        @if($comparaciones->isEmpty())
            <div class="plan-real-empty">
                No hay datos suficientes para comparar el plan y el real en este cultivo.
            </div>
        @else
            <div class="plan-real-summary-grid mb-4">
                <div class="plan-real-summary-card">
                    <div class="label">Registros comparados</div>
                    <p class="value" id="planRealTotalRegistros">{{ $totales['registros'] }}</p>
                </div>
                <div class="plan-real-summary-card is-alert">
                    <div class="label">Plan sin real</div>
                    <p class="value" id="planRealTotalPendientes">{{ $totales['pendientes'] }}</p>
                </div>
                <div class="plan-real-summary-card is-warning">
                    <div class="label">Real sin plan</div>
                    <p class="value" id="planRealTotalNoPlanificados">{{ $totales['no_planificados'] }}</p>
                </div>
                <div class="plan-real-summary-card is-info">
                    <div class="label">Desvios</div>
                    <p class="value" id="planRealTotalDesvios">{{ $totales['desvios'] }}</p>
                </div>
                <div class="plan-real-summary-card">
                    <div class="label">Costo plan</div>
                    <p class="value" id="planRealTotalCostoPlan">{{ agro_number($totales['costo_plan'], 2) }}</p>
                </div>
                <div class="plan-real-summary-card">
                    <div class="label">Costo real</div>
                    <p class="value" id="planRealTotalCostoReal">{{ agro_number($totales['costo_real'], 2) }}</p>
                </div>
            </div>

            <div class="card plan-real-filter-card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label plan-real-filter-label">Semana</label>
                            <select id="planRealWeekFilter" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($filtros['semanas'] as $semana)
                                    <option value="{{ $semana }}">Semana {{ $semana }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label plan-real-filter-label">Categoria</label>
                            <select id="planRealCategoryFilter" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($filtros['categorias'] as $categoria)
                                    <option value="{{ \Illuminate\Support\Str::slug($categoria) }}">{{ $categoria }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label plan-real-filter-label">Estado</label>
                            <select id="planRealStateFilter" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach($filtros['estados'] as $estado)
                                    <option value="{{ \Illuminate\Support\Str::slug($estado) }}">{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label plan-real-filter-label">Insumo o concepto</label>
                            <input
                                type="search"
                                id="planRealConceptFilter"
                                class="form-control form-control-sm"
                                placeholder="Buscar insumo plan o real"
                            >
                        </div>
                        <div class="col-12 plan-real-filter-actions">
                            <button type="button" id="planRealClearFilters" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-eraser"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card plan-real-table-card">
                <div class="card-header bg-white fw-bold">Detalle comparativo por semana</div>
                <div class="card-body">
                    <div class="plan-real-pagination-toolbar">
                        <div class="plan-real-pagination-meta">
                            <div class="d-flex align-items-center gap-2">
                                <label for="planRealPerPage" class="form-label plan-real-filter-label mb-0">Mostrar</label>
                                <select id="planRealPerPage" class="form-select form-select-sm" style="width: auto;">
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="small text-muted">registros</span>
                            </div>
                            <div class="small text-muted">
                                Registros visibles: <span id="planRealVisibleCount">{{ $comparaciones->count() }}</span>
                            </div>
                        </div>
                        <div class="plan-real-pagination-controls">
                            <button type="button" id="planRealPrevPage" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-chevron-left"></i> Anterior
                            </button>
                            <span id="planRealPageStatus" class="plan-real-pagination-status">Pagina 1 de 1</span>
                            <button type="button" id="planRealNextPage" class="btn btn-outline-secondary btn-sm">
                                Siguiente <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive plan-real-main-table-shell">
                        <table class="table table-sm table-bordered align-middle plan-real-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Semana</th>
                                    <th>Categoria</th>
                                    <th>Concepto</th>
                                    <th>Estado</th>
                                    <th>Cant. plan</th>
                                    <th>Cant. real</th>
                                    <th>Dif. cant.</th>
                                </tr>
                            </thead>
                            <tbody id="planRealTableBody">
                                @foreach($comparaciones as $fila)
                                    <tr
                                        data-semana="{{ $fila['semana'] > 0 ? $fila['semana'] : 'sin-semana' }}"
                                        data-semana-label="{{ $fila['semana'] > 0 ? 'Semana ' . $fila['semana'] : 'Sin semana' }}"
                                        data-semana-numero="{{ $fila['semana'] > 0 ? $fila['semana'] : 0 }}"
                                        data-categoria="{{ \Illuminate\Support\Str::slug($fila['categoria']) }}"
                                        data-estado="{{ \Illuminate\Support\Str::slug($fila['estado']) }}"
                                        data-estado-label="{{ $fila['estado'] }}"
                                        data-concepto="{{ \Illuminate\Support\Str::lower($fila['concepto']) }}"
                                        data-costo-plan="{{ $fila['costo_plan'] }}"
                                        data-costo-real="{{ $fila['costo_real'] }}"
                                        data-es-pendiente="{{ $fila['estado'] === 'Plan sin real' ? '1' : '0' }}"
                                        data-es-no-planificado="{{ $fila['estado'] === 'Real sin plan' ? '1' : '0' }}"
                                        data-es-desvio="{{ in_array($fila['estado'], ['Diferencia de cantidad', 'Diferencia de costo', 'Diferencia de cantidad y costo'], true) ? '1' : '0' }}"
                                    >
                                        <td>{{ $fila['semana'] > 0 ? 'Semana ' . $fila['semana'] : 'Sin semana' }}</td>
                                        <td>{{ $fila['categoria'] }}</td>
                                        <td>{{ $fila['concepto'] }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $estadoClasses[$fila['estado']] ?? 'bg-secondary-subtle text-secondary' }}">{{ $fila['estado'] }}</span>
                                        </td>
                                        <td>{{ agro_number($fila['cantidad_plan'], 2) }} {{ $fila['unidad_medida'] }}</td>
                                        <td>{{ agro_number($fila['cantidad_real'], 2) }} {{ $fila['unidad_medida'] }}</td>
                                        <td class="{{ $fila['diferencia_cantidad'] === 0.0 ? 'text-success' : 'text-danger fw-semibold' }}">{{ agro_number($fila['diferencia_cantidad'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>
</main>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const weekFilter = document.getElementById('planRealWeekFilter');
        const categoryFilter = document.getElementById('planRealCategoryFilter');
        const stateFilter = document.getElementById('planRealStateFilter');
        const conceptFilter = document.getElementById('planRealConceptFilter');
        const clearButton = document.getElementById('planRealClearFilters');
        const visibleCount = document.getElementById('planRealVisibleCount');
        const perPageSelect = document.getElementById('planRealPerPage');
        const prevPageButton = document.getElementById('planRealPrevPage');
        const nextPageButton = document.getElementById('planRealNextPage');
        const pageStatus = document.getElementById('planRealPageStatus');
        const totalRegistros = document.getElementById('planRealTotalRegistros');
        const totalPendientes = document.getElementById('planRealTotalPendientes');
        const totalNoPlanificados = document.getElementById('planRealTotalNoPlanificados');
        const totalDesvios = document.getElementById('planRealTotalDesvios');
        const totalCostoPlan = document.getElementById('planRealTotalCostoPlan');
        const totalCostoReal = document.getElementById('planRealTotalCostoReal');
        const rows = Array.from(document.querySelectorAll('#planRealTableBody tr'));
        const stateClasses = @json($estadoClasses);
        let currentPage = 1;

        if (!rows.length || !weekFilter || !categoryFilter || !stateFilter || !conceptFilter || !clearButton || !visibleCount || !perPageSelect || !prevPageButton || !nextPageButton || !pageStatus || !totalRegistros || !totalPendientes || !totalNoPlanificados || !totalDesvios || !totalCostoPlan || !totalCostoReal) {
            return;
        }

        function normalizeText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function formatNumber(value, decimals) {
            return Number(value || 0).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        }

        function updateSummaryTables(visibleRows) {
            const stateSummaryBody = document.getElementById('planRealStateSummaryBody');
            const weekSummaryBody = document.getElementById('planRealWeekSummaryBody');

            if (!stateSummaryBody || !weekSummaryBody) {
                return;
            }

            const stateMap = new Map();
            const weekMap = new Map();

            visibleRows.forEach(function (row) {
                const stateLabel = row.dataset.estadoLabel || 'Sin estado';
                const weekLabel = row.dataset.semanaLabel || 'Sin semana';
                const weekNumber = Number(row.dataset.semanaNumero || 0);
                const costoPlan = Number(row.dataset.costoPlan || 0);
                const costoReal = Number(row.dataset.costoReal || 0);
                const esPendiente = row.dataset.esPendiente === '1';
                const esNoPlanificado = row.dataset.esNoPlanificado === '1';
                const esDesvio = row.dataset.esDesvio === '1';

                if (!stateMap.has(stateLabel)) {
                    stateMap.set(stateLabel, {
                        estado: stateLabel,
                        registros: 0,
                        costoPlan: 0,
                        costoReal: 0,
                    });
                }

                if (!weekMap.has(weekLabel)) {
                    weekMap.set(weekLabel, {
                        semana: weekLabel,
                        semanaNumero: weekNumber,
                        registros: 0,
                        pendientes: 0,
                        noPlanificados: 0,
                        desvios: 0,
                        costoPlan: 0,
                        costoReal: 0,
                    });
                }

                const stateEntry = stateMap.get(stateLabel);
                stateEntry.registros += 1;
                stateEntry.costoPlan += costoPlan;
                stateEntry.costoReal += costoReal;

                const weekEntry = weekMap.get(weekLabel);
                weekEntry.registros += 1;
                weekEntry.pendientes += esPendiente ? 1 : 0;
                weekEntry.noPlanificados += esNoPlanificado ? 1 : 0;
                weekEntry.desvios += esDesvio ? 1 : 0;
                weekEntry.costoPlan += costoPlan;
                weekEntry.costoReal += costoReal;
            });

            const stateRows = Array.from(stateMap.values()).sort(function (left, right) {
                return left.estado.localeCompare(right.estado);
            });

            stateSummaryBody.innerHTML = stateRows.length
                ? stateRows.map(function (item) {
                    const stateClass = stateClasses[item.estado] || 'bg-secondary-subtle text-secondary';

                    return '<tr>' +
                        '<td><span class="badge rounded-pill ' + stateClass + '">' + item.estado + '</span></td>' +
                        '<td>' + formatNumber(item.registros, 0) + '</td>' +
                        '<td>' + formatNumber(item.costoPlan, 2) + '</td>' +
                        '<td>' + formatNumber(item.costoReal, 2) + '</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="4" class="text-center text-muted py-3">Sin registros con los filtros actuales.</td></tr>';

            const weekRows = Array.from(weekMap.values()).sort(function (left, right) {
                return left.semanaNumero - right.semanaNumero;
            });

            weekSummaryBody.innerHTML = weekRows.length
                ? weekRows.map(function (item) {
                    return '<tr>' +
                        '<td>' + item.semana + '</td>' +
                        '<td>' + formatNumber(item.registros, 0) + '</td>' +
                        '<td>' + formatNumber(item.pendientes, 0) + '</td>' +
                        '<td>' + formatNumber(item.noPlanificados, 0) + '</td>' +
                        '<td>' + formatNumber(item.desvios, 0) + '</td>' +
                        '<td>' + formatNumber(item.costoPlan, 2) + '</td>' +
                        '<td>' + formatNumber(item.costoReal, 2) + '</td>' +
                        '</tr>';
                }).join('')
                : '<tr><td colspan="7" class="text-center text-muted py-3">Sin registros con los filtros actuales.</td></tr>';
        }

        function updatePagination(visibleRows) {
            const perPage = Number(perPageSelect.value || 15);
            const totalPages = Math.max(1, Math.ceil(visibleRows.length / perPage));

            if (currentPage > totalPages) {
                currentPage = totalPages;
            }

            const startIndex = (currentPage - 1) * perPage;
            const endIndex = startIndex + perPage;

            visibleRows.forEach(function (row, index) {
                const shouldShow = index >= startIndex && index < endIndex;
                row.classList.toggle('d-none', !shouldShow);
            });

            pageStatus.textContent = 'Pagina ' + currentPage + ' de ' + totalPages;
            prevPageButton.disabled = currentPage === 1;
            nextPageButton.disabled = currentPage === totalPages;
        }

        function applyFilters() {
            const weekValue = weekFilter.value;
            const categoryValue = categoryFilter.value;
            const stateValue = stateFilter.value;
            const conceptValue = normalizeText(conceptFilter.value);
            let visibles = 0;
            let pendientes = 0;
            let noPlanificados = 0;
            let desvios = 0;
            let costoPlan = 0;
            let costoReal = 0;
            const visibleRows = [];

            rows.forEach(function (row) {
                const matchesWeek = !weekValue || row.dataset.semana === weekValue;
                const matchesCategory = !categoryValue || row.dataset.categoria === categoryValue;
                const matchesState = !stateValue || row.dataset.estado === stateValue;
                const rowConcept = normalizeText(row.dataset.concepto);
                const matchesConcept = !conceptValue || rowConcept.includes(conceptValue);
                const isVisible = matchesWeek && matchesCategory && matchesState && matchesConcept;

                row.classList.toggle('d-none', !isVisible);
                if (isVisible) {
                    visibles += 1;
                    pendientes += row.dataset.esPendiente === '1' ? 1 : 0;
                    noPlanificados += row.dataset.esNoPlanificado === '1' ? 1 : 0;
                    desvios += row.dataset.esDesvio === '1' ? 1 : 0;
                    costoPlan += Number(row.dataset.costoPlan || 0);
                    costoReal += Number(row.dataset.costoReal || 0);
                    visibleRows.push(row);
                }
            });

            visibleCount.textContent = String(visibles);
            totalRegistros.textContent = formatNumber(visibles, 0);
            totalPendientes.textContent = formatNumber(pendientes, 0);
            totalNoPlanificados.textContent = formatNumber(noPlanificados, 0);
            totalDesvios.textContent = formatNumber(desvios, 0);
            totalCostoPlan.textContent = formatNumber(costoPlan, 2);
            totalCostoReal.textContent = formatNumber(costoReal, 2);
            updateSummaryTables(visibleRows);
            updatePagination(visibleRows);
        }

        [weekFilter, categoryFilter, stateFilter].forEach(function (filter) {
            filter.addEventListener('change', applyFilters);
        });

        conceptFilter.addEventListener('input', applyFilters);

        perPageSelect.addEventListener('change', function () {
            currentPage = 1;
            applyFilters();
        });

        prevPageButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage -= 1;
                applyFilters();
            }
        });

        nextPageButton.addEventListener('click', function () {
            currentPage += 1;
            applyFilters();
        });

        clearButton.addEventListener('click', function () {
            weekFilter.value = '';
            categoryFilter.value = '';
            stateFilter.value = '';
            conceptFilter.value = '';
            currentPage = 1;
            applyFilters();
        });

        applyFilters();
    });
</script>
@endpush
@endsection