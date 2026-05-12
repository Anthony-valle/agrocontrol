@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <style>
        .report-table {
            table-layout: fixed;
            width: 100%;
            word-break: break-word;
            white-space: normal;
        }
        .report-table th,
        .report-table td {
            white-space: normal;
            vertical-align: middle;
        }
        .report-table .col-consumo { width: 8%; }
        .report-table .col-semana { width: 7%; }
        .report-table .col-fecha { width: 8%; }
        .report-table .col-total { width: 9%; }
        .report-table .col-categoria { width: 16%; }
        .report-table .col-descripcion { width: 32%; }
        .report-table .col-cantidad { width: 9%; }
        .report-table .col-um { width: 7%; }
        .report-table .col-subtotal { width: 10%; }
        .historial-selector-button {
            width: 100%;
            text-align: left;
            border: 1px solid #dbe5f0;
            border-radius: 1rem;
            background: #fff;
            padding: 0.9rem 1rem;
            transition: all 0.2s ease;
        }
        .historial-selector-button:hover,
        .historial-selector-button.is-active {
            border-color: #198754;
            background: #eef8f1;
            box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.12);
        }
        .historial-detail-shell {
            min-height: 320px;
            border: 1px dashed #d9e2ec;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f8fb 100%);
            padding: 1rem;
        }
        .historial-selector-search {
            margin-bottom: 1rem;
        }
    </style>
    <div class="pagetitle">
        <h1>Historial de Consumo del Cultivo</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('cultivo.index') }}">Cultivos</a></li>
                <li class="breadcrumb-item active">Historial de Consumo</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="card shadow-sm border-start border-primary border-4">
                    <div class="card-body">
                        <h5 class="fw-bold mb-2">{{ $cultivo->nombre }}</h5>
                        <p class="mb-1"><strong>Código:</strong> {{ $cultivo->codigo }}</p>
                        <p class="mb-1"><strong>Estado:</strong> {{ $cultivo->estado }}</p>
                        <p class="mb-0"><strong>Unidad de medida:</strong> {{ $cultivo->unidad_medida ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('reporte.cultivo.historial.excel', $cultivo->id) }}" class="btn btn-success btn-sm me-2">
                    <i class="fa-solid fa-file-excel"></i> Descargar Excel
                </a>
                <a href="{{ route('reporte.cultivo.historial.pdf', $cultivo->id) }}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                </a>
            </div>
        </div>

        <div class="row gy-3">
            <div class="col-lg-3">
                <div class="card bg-light border-start border-info border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Consumos Registrados</small>
                        <h3 class="fw-bold">{{ $totalConsumos }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-success border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Costo Total</small>
                        <h3 class="fw-bold">L {{ agro_number($totalConsumo, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-warning border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Cosecha Estimada</small>
                        <h3 class="fw-bold">{{ agro_number($cultivo->cosecha_estimada ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="card bg-light border-start border-dark border-4 shadow-sm">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase">Último Consumo</small>
                        <h3 class="fw-bold">{{ $ultimoConsumo ? \Carbon\Carbon::parse($ultimoConsumo->fecha_consumo)->format('d/m/Y') : 'N/A' }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporte.cultivo.historial', $cultivo->id) }}" class="d-flex flex-wrap justify-content-between align-items-end gap-3">
                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div>
                            <label class="form-label fw-bold mb-1">Fecha desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
                        </div>
                        <div>
                            <label class="form-label fw-bold mb-1">Fecha hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
                        </div>
                        <div>
                            <label class="form-label fw-bold mb-1">Registros</label>
                            <select name="per_page" class="form-select" onchange="this.form.submit()">
                                <option value="5" {{ (int) $perPage === 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ (int) $perPage === 15 ? 'selected' : '' }}>15</option>
                                <option value="20" {{ (int) $perPage === 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter me-1"></i> Filtrar
                        </button>
                        @if($fechaDesde !== '' || $fechaHasta !== '' || (int) $perPage !== 15)
                            <a href="{{ route('reporte.cultivo.historial', $cultivo->id) }}" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-eraser me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Resumen por Categoría</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold">Categoría</th>
                                        <th class="fw-bold text-end">Cantidad</th>
                                        <th class="fw-bold text-end">Costo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoryTotals as $categoria => $totales)
                                        <tr>
                                            <td class="fw-bold">{{ $categoria }}</td>
                                            <td class="text-end">{{ agro_number($totales['cantidad'], 2) }}</td>
                                            <td class="text-end">L {{ agro_number($totales['subtotal'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">Sin consumos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Detalle de Consumos por Cultivo</div>
                    <div class="card-body">
                        @if($consumos->isNotEmpty())
                            <div class="historial-selector-search">
                                <label class="form-label fw-bold mb-1">Buscar consumo</label>
                                <input type="text" id="historialSelectorSearch" class="form-control" placeholder="Buscar por ID, fecha, mes o total...">
                            </div>
                            @foreach($consumosAgrupadosPorMes as $bloqueMes)
                                <div class="mb-4" data-historial-month-group>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <h5 class="mb-1 text-capitalize">{{ $bloqueMes['titulo'] }}</h5>
                                            <small class="text-muted">{{ $bloqueMes['registros'] }} consumos en esta hoja</small>
                                        </div>
                                        <div class="text-end">
                                            <div class="small text-muted">Total del mes en esta hoja</div>
                                            <div class="fw-bold">L {{ agro_number($bloqueMes['total'], 2) }}</div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-2">
                                        @foreach($bloqueMes['items'] as $consumo)
                                            <button
                                                type="button"
                                                class="historial-selector-button {{ $loop->parent->first && $loop->first ? 'is-active' : '' }}"
                                                data-detalle-url="{{ route('reporte.cultivo.historial.detalle', ['cultivo_id' => $cultivo->id, 'consumo_id' => $consumo->id]) }}"
                                                data-historial-search="Consumo {{ $consumo->id }} {{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }} {{ $bloqueMes['titulo'] }} {{ agro_number($consumo->total, 2) }}"
                                            >
                                                <div class="fw-bold">Consumo #{{ $consumo->id }}</div>
                                                <div class="small text-muted">{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</div>
                                                <div class="small mt-1">Total: L {{ agro_number($consumo->total, 2) }}</div>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            @if($consumos->hasPages())
                                <div class="mt-4">
                                    {{ $consumos->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                                </div>
                            @endif
                        @else
                            <div class="alert alert-warning">No hay consumos registrados para este cultivo.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mt-4 mt-lg-0">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Consumo Seleccionado</div>
                    <div class="card-body">
                        <div id="historialConsumoDetalleContainer" class="historial-detail-shell d-flex align-items-center justify-content-center text-muted">
                            Selecciona un consumo para cargar el detalle.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailContainer = document.getElementById('historialConsumoDetalleContainer');
    const buttons = Array.from(document.querySelectorAll('.historial-selector-button'));
    const searchInput = document.getElementById('historialSelectorSearch');

    function formatNumber(value) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    function inicializarFiltrosDetalle() {
        const shell = detailContainer.querySelector('.historial-detalle-consumo');

        if (!shell) {
            return;
        }

        const categoriaSelect = shell.querySelector('[data-detalle-categoria]');
        const busquedaInput = shell.querySelector('[data-detalle-busqueda]');
        const resumen = shell.querySelector('[data-detalle-resumen]');
        const rows = Array.from(shell.querySelectorAll('[data-detalle-row]'));

        function aplicarFiltrosDetalle() {
            const categoria = categoriaSelect?.value || '';
            const busqueda = (busquedaInput?.value || '').trim().toLowerCase();

            let cantidadVisible = 0;
            let subtotalVisible = 0;
            let registrosVisibles = 0;

            rows.forEach((row) => {
                const matchesCategoria = categoria === '' || row.dataset.categoria === categoria;
                const matchesBusqueda = busqueda === '' || (row.dataset.search || '').includes(busqueda);
                const visible = matchesCategoria && matchesBusqueda;

                row.style.display = visible ? '' : 'none';

                if (visible) {
                    registrosVisibles += 1;
                    cantidadVisible += Number.parseFloat(row.dataset.cantidad || '0') || 0;
                    subtotalVisible += Number.parseFloat(row.dataset.subtotal || '0') || 0;
                }
            });

            if (resumen) {
                resumen.textContent = `Registros ${registrosVisibles} | Cantidad ${formatNumber(cantidadVisible)} | Total ${formatNumber(subtotalVisible)} Lps`;
            }
        }

        categoriaSelect?.addEventListener('change', aplicarFiltrosDetalle);
        busquedaInput?.addEventListener('input', aplicarFiltrosDetalle);
        aplicarFiltrosDetalle();
    }

    function aplicarFiltroSelector() {
        if (!searchInput) {
            return;
        }

        const search = searchInput.value.trim().toLowerCase();
        const monthGroups = Array.from(document.querySelectorAll('[data-historial-month-group]'));

        monthGroups.forEach((group) => {
            const groupButtons = Array.from(group.querySelectorAll('.historial-selector-button'));
            let visibles = 0;

            groupButtons.forEach((button) => {
                const matches = search === '' || (button.dataset.historialSearch || '').toLowerCase().includes(search);
                button.style.display = matches ? '' : 'none';
                if (matches) {
                    visibles += 1;
                }
            });

            group.style.display = visibles > 0 ? '' : 'none';
        });
    }

    if (!detailContainer || !buttons.length) {
        return;
    }

    async function cargarDetalle(button) {
        buttons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');

        detailContainer.innerHTML = '<div class="text-center text-muted py-5">Cargando detalle del consumo...</div>';

        try {
            const response = await fetch(button.dataset.detalleUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar el detalle del consumo.');
            }

            detailContainer.innerHTML = await response.text();
            inicializarFiltrosDetalle();
        } catch (error) {
            detailContainer.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el detalle del consumo seleccionado.</div>';
        }
    }

    buttons.forEach((button) => {
        button.addEventListener('click', function () {
            cargarDetalle(button);
        });
    });

    searchInput?.addEventListener('input', aplicarFiltroSelector);
    aplicarFiltroSelector();

    cargarDetalle(buttons[0]);
});
</script>
@endsection
