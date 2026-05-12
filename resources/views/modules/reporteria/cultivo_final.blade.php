@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <style>
        .report-sheet + .report-sheet {
            margin-top: 1rem;
        }

        .kpi-cost-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .kpi-cost-card {
            height: 100%;
            padding: 1.25rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.75rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            text-align: center;
        }

        .kpi-cost-card.is-total {
            border-color: rgba(var(--bs-primary-rgb), 0.25);
            background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.08) 0%, rgba(var(--bs-primary-rgb), 0.14) 100%);
        }

        .kpi-cost-card .kpi-label {
            margin-bottom: 0.35rem;
            color: var(--bs-secondary-color);
        }

        .kpi-cost-card .kpi-value {
            margin-bottom: 0.35rem;
            font-weight: 700;
            color: var(--bs-heading-color);
        }

        .kpi-cost-card.is-total .kpi-value {
            color: var(--bs-primary);
        }

        .report-sheet.is-hidden {
            display: none;
        }

        .report-pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .report-pagination nav {
            max-width: 100%;
            overflow-x: auto;
        }

        .report-pagination .pagination {
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        @media print {
            .report-sheet.is-hidden {
                display: block !important;
            }

            .report-sheet.page-break {
                page-break-after: always;
            }

            .report-pagination {
                display: none !important;
            }
        }
    </style>
    <div class="pagetitle">
        <h1>Reporte de Cultivo: {{ $cultivo->nombre }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('cultivo.index') }}">Cultivos</a></li>
                <li class="breadcrumb-item active">Reporte</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row mb-3">
            <div class="col-md-8"></div>
            <div class="col-md-4 text-md-end">
                <button type="button" class="btn btn-success btn-sm me-2" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir / PDF
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="downloadReportCsv()">
                    <i class="bi bi-download"></i> Descargar CSV
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-3">
                <div class="card bg-light border-start border-primary border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Plan Cosecha Estimada</small>
                        <h3 class="fw-bold">{{ agro_number($planVsReal['cosecha_esperada'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-info border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Costo Plan</small>
                        <h3 class="fw-bold">{{ agro_number($planVsReal['presupuesto_plan'], 2) }} Lps</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-success border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Costo Real</small>
                        <h3 class="fw-bold">{{ agro_number($planVsReal['costo_real'], 2) }} Lps</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-{{ $planVsReal['diferencia'] >= 0 ? 'success' : 'danger' }} border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Diferencia</small>
                        <h3 class="fw-bold {{ $planVsReal['diferencia'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ agro_number($planVsReal['diferencia'], 2) }} Lps
                        </h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-3">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">KPI Costo de Producción</div>
                    <div class="card-body">
                        <div class="kpi-cost-grid">
                            @forelse($kpiCostoProduccion['categorias'] as $categoriaKpi)
                                <div class="kpi-cost-card">
                                    <p class="kpi-label">{{ $categoriaKpi['categoria'] }}</p>
                                    <h4 class="kpi-value">{{ agro_number($categoriaKpi['real_costo'], 2) }} Lps</h4>
                                    <small class="text-muted">Plan: {{ agro_number($categoriaKpi['plan_costo'], 2) }} Lps</small>
                                </div>
                            @empty
                                <div class="kpi-cost-card">
                                    <div class="text-muted">Sin categorías registradas.</div>
                                </div>
                            @endforelse
                            <div class="kpi-cost-card is-total">
                                <p class="kpi-label">Costo Producción Total</p>
                                <h4 class="kpi-value">{{ agro_number($kpiCostoProduccion['total'], 2) }} Lps</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-3">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">KPI Producción</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Ha cosechada</p>
                                <h4 class="fw-bold">{{ agro_number($kpiProduccion['ha_cosechadas'], 2) }}</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Ha sembradas</p>
                                <h4 class="fw-bold">{{ agro_number($kpiProduccion['ha_sembradas'], 2) }}</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Producción por ha cosechada</p>
                                <h4 class="fw-bold">{{ agro_number($kpiProduccion['produccion_por_ha_cosechada'], 2) }} kg/ha</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Producción cosecha kg</p>
                                <h4 class="fw-bold text-success">{{ agro_number($kpiProduccion['produccion_cosecha_kg'], 2) }} kg</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Costo Plan vs Real</div>
                    <div class="card-body">
                        <canvas id="chartCostoPlanReal" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Cosecha Plan vs Real</div>
                    <div class="card-body">
                        <canvas id="chartCosechaPlanReal" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Comparación Plan vs Real por Categoría</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Plan Cantidad</th>
                                        <th>Real Cantidad</th>
                                        <th>Real Costo</th>
                                        <th>Plan Costo</th>
                                        <th>Diferencia Costo (Real - Plan)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoryComparisons as $categoria => $comparacion)
                                        @php
                                            $realColorClass = $comparacion['sobre_plan_costo'] ? 'text-danger fw-bold' : 'text-primary fw-semibold';
                                            $diffColorClass = $comparacion['sobre_plan_costo'] ? 'text-danger fw-bold' : 'text-primary fw-semibold';
                                        @endphp
                                        <tr>
                                            <td>{{ $categoria }}</td>
                                            <td>{{ agro_number($comparacion['plan_cantidad'], 2) }}</td>
                                            <td>{{ agro_number($comparacion['real_cantidad'], 2) }}</td>
                                            <td class="{{ $realColorClass }}">{{ agro_number($comparacion['real_costo'], 2) }} Lps</td>
                                            <td>{{ agro_number($comparacion['plan_costo'], 2) }} Lps</td>
                                            <td class="{{ $diffColorClass }}">
                                                {{ agro_number($comparacion['diferencia_costo'], 2) }} Lps
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No hay datos de comparación de categorías.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="row text-center mt-4">
                            @foreach($categoryComparisons as $categoria => $comparacion)
                                <div class="col-md-4 mb-3">
                                    <p class="text-muted mb-1">{{ $categoria }}</p>
                                    <h5 class="fw-bold">{{ agro_number($comparacion['real_costo'], 2) }} Lps</h5>
                                    <small class="text-muted">Plan: {{ agro_number($comparacion['plan_costo'], 2) }} Lps</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Resumen de Producción Real</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Cosecha Total</p>
                                <h4 class="fw-bold">{{ agro_number($bruto, 2) }}</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Descarte</p>
                                <h4 class="fw-bold text-danger">{{ agro_number($descarte, 2) }}</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Producción Neta</p>
                                <h4 class="fw-bold text-success">{{ agro_number($neta, 2) }}</h4>
                            </div>
                            <div class="col-md-3">
                                <p class="text-muted mb-1">Costo Unitario Plan</p>
                                <h4 class="fw-bold text-primary">{{ $planVsReal['costo_unitario_plan'] !== null ? agro_number($planVsReal['costo_unitario_plan'], 2) . ' Lps/kg' : 'N/D' }}</h4>
                            </div>
                        </div>
                        <div class="row text-center mt-3">
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Ingresos</p>
                                <h4 class="fw-bold">{{ $tienePrecioVenta ? agro_number($ingresos, 2) . ' Lps' : 'N/D' }}</h4>
                            </div>
                            <div class="col-md-6">
                                <p class="text-muted mb-1">Utilidad</p>
                                <h4 class="fw-bold {{ $utilidad !== null && $utilidad >= 0 ? 'text-success' : ($utilidad !== null ? 'text-danger' : '') }}">{{ $utilidad !== null ? agro_number($utilidad, 2) . ' Lps' : 'N/D' }}</h4>
                            </div>
                        </div>
                        <div class="row text-center mt-3">
                            <div class="col-md-12">
                                <p class="text-muted mb-1">Rendimiento real</p>
                                <h4 class="fw-bold">{{ agro_number($planVsReal['rendimiento_real'], 1) }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Plan de Cultivo</div>
                    <div class="card-body">
                        @if($plan)
                            <p><strong>Fecha del plan:</strong> {{ \Carbon\Carbon::parse($plan->fecha_plan)->format('d/m/Y') }}</p>
                            <p><strong>Cosecha estimada:</strong> {{ agro_number($planVsReal['cosecha_esperada'], 2) }}</p>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 mb-2">
                                <small class="text-muted">Plan filtrable por semana y paginado en 15 registros.</small>
                                <div class="d-flex align-items-center gap-2">
                                    <label for="planWeekFilter" class="small text-muted mb-0">Semana:</label>
                                    <select id="planWeekFilter" class="form-select form-select-sm" style="width:auto; min-width: 140px;">
                                        <option value="">Todas</option>
                                        @foreach($planDetalles->pluck('semana')->filter()->unique()->sort()->values() as $semanaPlan)
                                            <option value="{{ $semanaPlan }}">Semana {{ $semanaPlan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if($planDetalles->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="planTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Semana</th>
                                                <th>Categoria</th>
                                                <th>Descripción</th>
                                                <th>Cantidad</th>
                                                <th>U.M.</th>
                                                <th>Costo Unit.</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($planDetalles->sortBy([['semana', 'asc'], ['categoria', 'asc'], ['descripcion', 'asc']]) as $detalle)
                                                <tr data-plan-row data-week="{{ $detalle->semana ?? 'Sin semana' }}">
                                                    <td>{{ $detalle->semana }}</td>
                                                    <td>{{ $detalle->categoria }}</td>
                                                    <td>{{ $detalle->descripcion }}</td>
                                                    <td>{{ agro_number($detalle->cantidad_estimada, 2) }}</td>
                                                    <td>{{ $detalle->unidad_medida }}</td>
                                                    <td>{{ agro_number($detalle->costo_unitario, 2) }}</td>
                                                    <td>{{ agro_number($detalle->subtotal, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="report-pagination" id="planPaginationContainer">
                                    <small class="text-muted" id="planPaginationInfo"></small>
                                    <nav aria-label="Paginacion del plan de cultivo">
                                        <ul class="pagination pagination-sm mb-0" id="planPaginationList"></ul>
                                    </nav>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">No hay detalles de plan registrados.</div>
                            @endif
                        @else
                            <div class="alert alert-warning">No existe un plan registrado para este cultivo.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Consumos Reales</div>
                    <div class="card-body">
                        @if($consumoItems->isNotEmpty())
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1 mb-2">
                                <small class="text-muted">Hoja Registro de Consumos filtrable por semana y paginada en 15 registros.</small>
                                <div class="d-flex align-items-center gap-2">
                                    <label for="consumoWeekFilter" class="small text-muted mb-0">Semana:</label>
                                    <select id="consumoWeekFilter" class="form-select form-select-sm" style="width:auto; min-width: 140px;">
                                        <option value="">Todas</option>
                                        @foreach($consumoItems->pluck('semana_cultivo')->filter()->unique()->sort()->values() as $semanaConsumo)
                                            <option value="{{ $semanaConsumo }}">Semana {{ is_numeric($semanaConsumo) ? (int) $semanaConsumo : $semanaConsumo }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0" id="consumoTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Semana</th>
                                            <th>Fecha</th>
                                            <th>Insumo</th>
                                            <th>Categoria</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>U.M.</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consumoItems->sortBy([['semana_cultivo', 'asc'], ['fecha_consumo', 'asc'], ['categoria', 'asc'], ['descripcion', 'asc']]) as $item)
                                            <tr data-consumo-row data-week="{{ $item['semana_cultivo'] ?? 'Sin semana' }}">
                                                <td>{{ is_numeric($item['semana_cultivo']) ? (int) $item['semana_cultivo'] : $item['semana_cultivo'] }}</td>
                                                <td>{{ \Carbon\Carbon::parse($item['fecha_consumo'])->format('d/m/Y') }}</td>
                                                <td>{{ $item['insumo'] }}</td>
                                                <td>{{ $item['categoria'] }}</td>
                                                <td>{{ $item['descripcion'] }}</td>
                                                <td>{{ agro_number($item['cantidad'], 2) }}</td>
                                                <td>{{ $item['unidad_medida'] }}</td>
                                                <td>{{ agro_number($item['subtotal'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                                <div class="report-pagination" id="consumoPaginationContainer">
                                    <small class="text-muted" id="consumoPaginationInfo"></small>
                                    <nav aria-label="Paginacion de consumos reales">
                                        <ul class="pagination pagination-sm mb-0" id="consumoPaginationList"></ul>
                                    </nav>
                                </div>
                        @else
                            <div class="alert alert-warning">No hay consumos vigentes registrados para este cultivo.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Cosechas Registradas</div>
                    <div class="card-body">
                        @if($cultivo->cosechas->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Bruto</th>
                                            <th>Descarte</th>
                                            <th>Neto</th>
                                            <th>Disponible</th>
                                            <th>Precio Unitario</th>
                                            <th>Ingreso</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cultivo->cosechas as $cosecha)
                                            @php
                                                $facturasCosecha = $cosecha->facturas ?? collect();
                                                $cantidadFacturada = (float) $facturasCosecha->sum('cantidad_vendida');
                                                $totalFacturado = (float) $facturasCosecha->sum('total');
                                                $precioFacturado = $cantidadFacturada > 0 ? ($totalFacturado / $cantidadFacturada) : null;
                                                $tienePrecioCosecha = $precioFacturado !== null
                                                    || (array_key_exists('precio_venta_unitario', $cosecha->getAttributes()) && $cosecha->precio_venta_unitario !== null);
                                                $precioMostrar = $precioFacturado ?? $cosecha->precio_venta_unitario;
                                                $ingresoMostrar = $totalFacturado > 0
                                                    ? $totalFacturado
                                                    : ($precioMostrar !== null ? ((float) $cosecha->cantidad_neta * (float) $precioMostrar) : null);
                                            @endphp
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                                                <td>{{ agro_number($cosecha->cantidad_bruta, 2) }}</td>
                                                <td>{{ agro_number($cosecha->descarte, 2) }}</td>
                                                <td>{{ agro_number($cosecha->cantidad_neta, 2) }}</td>
                                                <td>{{ agro_number($cosecha->cantidad_disponible, 2) }}</td>
                                                <td>{{ $tienePrecioCosecha ? agro_number($precioMostrar, 2) . ' Lps' : 'N/D' }}</td>
                                                <td>{{ $ingresoMostrar !== null ? agro_number($ingresoMostrar, 2) . ' Lps' : 'N/D' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-warning">No hay cosechas registradas para este cultivo.</div>
                        @endif
                        @unless($tienePrecioVenta)
                            <div class="alert alert-info mt-3 mb-0">Las cosechas no tienen un campo confirmado de precio de venta unitario, por eso ingresos y utilidad se muestran como no disponibles.</div>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function initReportPagination(config) {
            const sheets = Array.from(document.querySelectorAll(`[data-report-group="${config.group}"]`));
            const info = document.getElementById(config.infoId);
            const list = document.getElementById(config.listId);

            if (!sheets.length || !info || !list) {
                return;
            }

            const state = { currentPage: 1, totalPages: sheets.length };

            function renderPagination() {
                list.innerHTML = '';
                const maxVisiblePages = 7;

                function addItem(label, page, disabled, active) {
                    const li = document.createElement('li');
                    li.className = 'page-item';
                    if (disabled) li.classList.add('disabled');
                    if (active) li.classList.add('active');

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'page-link';
                    button.textContent = label;
                    button.disabled = !!disabled;
                    button.addEventListener('click', function () {
                        state.currentPage = page;
                        render();
                    });

                    li.appendChild(button);
                    list.appendChild(li);
                }

                addItem('Anterior', Math.max(1, state.currentPage - 1), state.currentPage === 1, false);

                let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(state.totalPages, startPage + maxVisiblePages - 1);

                if ((endPage - startPage + 1) < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                if (startPage > 1) {
                    addItem('1', 1, false, state.currentPage === 1);
                    if (startPage > 2) {
                        addItem('...', startPage - 1, true, false);
                    }
                }

                for (let page = startPage; page <= endPage; page += 1) {
                    addItem(String(page), page, false, page === state.currentPage);
                }

                if (endPage < state.totalPages) {
                    if (endPage < state.totalPages - 1) {
                        addItem('...', endPage + 1, true, false);
                    }
                    addItem(String(state.totalPages), state.totalPages, false, state.currentPage === state.totalPages);
                }

                addItem('Siguiente', Math.min(state.totalPages, state.currentPage + 1), state.currentPage === state.totalPages, false);
            }

            function render() {
                sheets.forEach((sheet, index) => {
                    sheet.classList.toggle('is-hidden', index + 1 !== state.currentPage);
                });

                info.textContent = `Pagina ${state.currentPage} de ${state.totalPages}`;
                renderPagination();
            }

            render();
        }

        function initPlanTablePagination() {
            const table = document.getElementById('planTable');
            const weekFilter = document.getElementById('planWeekFilter');
            const info = document.getElementById('planPaginationInfo');
            const list = document.getElementById('planPaginationList');

            if (!table || !weekFilter || !info || !list) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr[data-plan-row]'));
            const perPage = 15;
            const state = {
                page: 1,
                filter: ''
            };

            function getFilteredRows() {
                return rows.filter((row) => {
                    if (!state.filter) {
                        return true;
                    }

                    return String(row.dataset.week || '') === state.filter;
                });
            }

            function renderPagination(totalPages) {
                list.innerHTML = '';

                if (totalPages <= 1) {
                    return;
                }

                function addItem(label, page, disabled, active) {
                    const li = document.createElement('li');
                    li.className = 'page-item';
                    if (disabled) li.classList.add('disabled');
                    if (active) li.classList.add('active');

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'page-link';
                    button.textContent = label;
                    button.disabled = !!disabled;
                    button.addEventListener('click', function () {
                        state.page = page;
                        render();
                    });

                    li.appendChild(button);
                    list.appendChild(li);
                }

                addItem('Anterior', Math.max(1, state.page - 1), state.page === 1, false);

                for (let page = 1; page <= totalPages; page += 1) {
                    addItem(String(page), page, false, page === state.page);
                }

                addItem('Siguiente', Math.min(totalPages, state.page + 1), state.page === totalPages, false);
            }

            function render() {
                const filteredRows = getFilteredRows();
                const totalRows = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

                if (state.page > totalPages) {
                    state.page = totalPages;
                }

                const start = (state.page - 1) * perPage;
                const end = start + perPage;
                const visibleRows = new Set(filteredRows.slice(start, end));

                rows.forEach((row) => {
                    row.style.display = visibleRows.has(row) ? '' : 'none';
                });

                if (!totalRows) {
                    info.textContent = 'No hay registros para la semana seleccionada.';
                } else {
                    info.textContent = `Mostrando ${start + 1}-${Math.min(end, totalRows)} de ${totalRows} registros`;
                }

                renderPagination(totalPages);
            }

            weekFilter.addEventListener('change', function () {
                state.filter = this.value;
                state.page = 1;
                render();
            });

            render();
        }

        initPlanTablePagination();

        function initConsumoTablePagination() {
            const table = document.getElementById('consumoTable');
            const weekFilter = document.getElementById('consumoWeekFilter');
            const info = document.getElementById('consumoPaginationInfo');
            const list = document.getElementById('consumoPaginationList');

            if (!table || !weekFilter || !info || !list) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr[data-consumo-row]'));
            const perPage = 15;
            const state = {
                page: 1,
                filter: ''
            };

            function getFilteredRows() {
                return rows.filter((row) => {
                    if (!state.filter) {
                        return true;
                    }

                    return String(row.dataset.week || '') === state.filter;
                });
            }

            function renderPagination(totalPages) {
                list.innerHTML = '';

                if (totalPages <= 1) {
                    return;
                }

                function addItem(label, page, disabled, active) {
                    const li = document.createElement('li');
                    li.className = 'page-item';
                    if (disabled) li.classList.add('disabled');
                    if (active) li.classList.add('active');

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'page-link';
                    button.textContent = label;
                    button.disabled = !!disabled;
                    button.addEventListener('click', function () {
                        state.page = page;
                        render();
                    });

                    li.appendChild(button);
                    list.appendChild(li);
                }

                addItem('Anterior', Math.max(1, state.page - 1), state.page === 1, false);

                for (let page = 1; page <= totalPages; page += 1) {
                    addItem(String(page), page, false, page === state.page);
                }

                addItem('Siguiente', Math.min(totalPages, state.page + 1), state.page === totalPages, false);
            }

            function render() {
                const filteredRows = getFilteredRows();
                const totalRows = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

                if (state.page > totalPages) {
                    state.page = totalPages;
                }

                const start = (state.page - 1) * perPage;
                const end = start + perPage;
                const visibleRows = new Set(filteredRows.slice(start, end));

                rows.forEach((row) => {
                    row.style.display = visibleRows.has(row) ? '' : 'none';
                });

                if (!totalRows) {
                    info.textContent = 'No hay registros para la semana seleccionada.';
                } else {
                    info.textContent = `Mostrando ${start + 1}-${Math.min(end, totalRows)} de ${totalRows} registros`;
                }

                renderPagination(totalPages);
            }

            weekFilter.addEventListener('change', function () {
                state.filter = this.value;
                state.page = 1;
                render();
            });

            render();
        }

        initConsumoTablePagination();

        function renderComparisonChart(canvasId, config) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                return;
            }

            if (typeof Chart === 'undefined') {
                canvas.parentElement.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar el gráfico.</div>';
                return;
            }

            const context = canvas.getContext('2d');
            if (!context) {
                canvas.parentElement.innerHTML = '<div class="alert alert-warning mb-0">No se pudo inicializar el gráfico.</div>';
                return;
            }

            new Chart(context, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: config.datasetLabel,
                        data: config.values,
                        backgroundColor: config.backgroundColor,
                        borderColor: config.borderColor,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return Number(value).toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + (config.suffix || '');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.label + ': ' + Number(context.parsed.y ?? context.parsed).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + (config.suffix || '');
                                }
                            }
                        }
                    }
                }
            });
        }

        renderComparisonChart('chartCostoPlanReal', {
            labels: ['Plan', 'Real'],
            datasetLabel: 'Costo (Lps)',
            values: @json([(float) ($planVsReal['presupuesto_plan'] ?? 0), (float) ($planVsReal['costo_real'] ?? 0)]),
            backgroundColor: ['#4e73df', '#1cc88a'],
            borderColor: ['#2e59d9', '#157347'],
            suffix: ' Lps'
        });

        renderComparisonChart('chartCosechaPlanReal', {
            labels: ['Presupuestada', 'Real'],
            datasetLabel: 'Cosecha',
            values: @json([(float) ($planVsReal['cosecha_esperada'] ?? 0), (float) ($planVsReal['cosecha_real_neta'] ?? 0)]),
            backgroundColor: ['#0d6efd', '#198754'],
            borderColor: ['#0a58ca', '#157347'],
            suffix: ' kg'
        });
    });

    function downloadReportCsv() {
        const rows = [];
        document.querySelectorAll('main .card').forEach(card => {
            const title = card.querySelector('.card-header')?.textContent.trim();
            const table = card.querySelector('table');
            if (!title || !table) {
                return;
            }

            rows.push([title]);
            table.querySelectorAll('thead tr').forEach(tr => {
                rows.push(Array.from(tr.querySelectorAll('th')).map(th => th.textContent.trim()));
            });
            table.querySelectorAll('tbody tr').forEach(tr => {
                rows.push(Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim()));
            });
            rows.push([]);
        });

        if (!rows.length) {
            return;
        }

        const csvContent = rows.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.setAttribute('download', `reporte_cultivo_{{ $cultivo->id }}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
@endpush
@endsection