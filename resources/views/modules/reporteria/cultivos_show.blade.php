@extends('layouts.main')

@section('titulo', 'Detalle de Cultivo')

@section('contenido')
<main id="main" class="main">
    <style>
        .cultivo-kpi-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
            min-height: 100%;
        }

        .cultivo-kpi-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.78rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .cultivo-kpi-value {
            margin: 0;
            font-size: clamp(1.75rem, 2vw, 2.3rem);
            line-height: 1.1;
            word-break: break-word;
        }

        .cultivo-kpi-value.is-money {
            font-size: clamp(1.55rem, 1.8vw, 2.1rem);
        }

        .consumo-date-button {
            width: 100%;
            text-align: left;
            border: 1px solid #e7edf3;
            border-radius: 1rem;
            background: #fff;
            padding: 0.9rem 1rem;
            transition: all 0.2s ease;
        }

        .consumo-date-button:hover,
        .consumo-date-button.is-active {
            border-color: #198754;
            background: #eef8f1;
            box-shadow: 0 0.5rem 1rem rgba(25, 135, 84, 0.12);
        }

        .consumo-date-button .date-title {
            display: block;
            font-weight: 700;
            color: #17324d;
        }

        .consumo-date-button .date-meta {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            font-size: 0.85rem;
            color: #5b6776;
            margin-top: 0.35rem;
        }

        .consumo-detail-shell {
            min-height: 260px;
            border: 1px dashed #d9e2ec;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f8fb 100%);
            padding: 1rem;
        }

        .categoria-detail-button {
            border: 1px solid #cfe5d7;
            background: #f7fbf8;
            color: #166534;
            border-radius: 999px;
            padding: 0.3rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .categoria-detail-button.is-active {
            background: #198754;
            border-color: #198754;
            color: #fff;
        }

        @media (max-width: 576px) {
            .cultivo-kpi-card .card-body {
                padding: 1rem;
            }

            .cultivo-kpi-value,
            .cultivo-kpi-value.is-money {
                font-size: 1.65rem;
            }
        }
    </style>

    <div class="pagetitle">
        <h1>{{ $cultivo->nombre }}</h1>
        <p class="text-muted mb-0">Lote: {{ $cultivo->lote->nombre ?? '-' }} · Estado: {{ $cultivo->estado }}</p>
    </div>

    <section class="section">
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Planes</small>
                        <h3 class="cultivo-kpi-value">{{ agro_number($metricas['planes']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Consumos</small>
                        <h3 class="cultivo-kpi-value">{{ agro_number($metricas['consumos']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Inversión</small>
                        <h3 class="cultivo-kpi-value is-money text-warning">{{ agro_number($metricas['inversion'], 2) }} Lps</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Neta</small>
                        <h3 class="cultivo-kpi-value">{{ agro_number($metricas['cosecha_neta'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Disponible</small>
                        <h3 class="cultivo-kpi-value">{{ agro_number($metricas['disponible'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-2">
                <div class="card cultivo-kpi-card">
                    <div class="card-body">
                        <small class="cultivo-kpi-label text-muted fw-bold">Ingresos</small>
                        <h3 class="cultivo-kpi-value is-money text-primary">{{ agro_number($metricas['ingresos'], 2) }} Lps</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-xl-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Reporte de Consumos por Categoría</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Registros</th>
                                        <th>Cantidad Total</th>
                                        <th>Total</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categoriasConsumoReporte as $categoriaItem)
                                        <tr>
                                            <td class="fw-semibold">{{ $categoriaItem->categoria }}</td>
                                            <td>{{ agro_number($categoriaItem->registros) }}</td>
                                            <td>{{ agro_number($categoriaItem->cantidad_total, 2) }}</td>
                                            <td>{{ agro_number($categoriaItem->total, 2) }} Lps</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="categoria-detail-button"
                                                    data-url="{{ route('reporteria.cultivos.consumos-categoria', $cultivo->id) }}?categoria={{ urlencode($categoriaItem->categoria) }}"
                                                >Ver detalle</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-3">Sin consumos agrupados por categoría.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <div id="categoriaDetailContainer" class="consumo-detail-shell d-flex align-items-center justify-content-center text-muted">
                                Selecciona una categoría para cargar su detalle completo.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Cosechas recientes</h5></div>
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>Fecha</th><th>Neta</th><th>Disponible</th><th>Unidad</th></tr></thead>
                                <tbody>
                                    @forelse($cosechasRecientes as $cosecha)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($cosecha->fecha_cosecha)->format('d/m/Y') }}</td>
                                            <td>{{ agro_number($cosecha->cantidad_neta, 2) }}</td>
                                            <td>{{ agro_number($cosecha->cantidad_disponible, 2) }}</td>
                                            <td>{{ $cosecha->unidad_medida }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">Sin cosechas registradas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const categoriaDetailContainer = document.getElementById('categoriaDetailContainer');
    const categoriaButtons = Array.from(document.querySelectorAll('.categoria-detail-button'));

    function normalizeSearchText(value) {
        return (value || '')
            .toString()
            .trim()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .trim();
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    function aplicarFiltrosCategoria(shell) {
        if (!shell) {
            return;
        }

        const selectedDateInput = shell.querySelector('.categoria-fecha-select');
        const selectedActivityInput = shell.querySelector('.categoria-actividad-select');
        const selectedDate = selectedDateInput?.value || '__ALL__';
        const selectedActivity = (selectedActivityInput?.value || '').trim();
        const rows = Array.from(shell.querySelectorAll('tbody tr[data-categoria-fecha]'));
        const totalCell = shell.querySelector('[data-categoria-total]');

        let visibleCount = 0;
        let visibleTotal = 0;

        rows.forEach((row) => {
            const matchesDate = selectedDate === '__ALL__' || row.dataset.categoriaFecha === selectedDate;
            const searchText = normalizeSearchText(row.dataset.categoriaBusqueda || row.dataset.categoriaActividad || '');
            const matchesActivity = selectedActivity === '' || searchText.includes(normalizeSearchText(selectedActivity));
            const matches = matchesDate && matchesActivity;
            row.style.display = matches ? '' : 'none';

            if (matches) {
                visibleCount += 1;
                visibleTotal += Number.parseFloat(row.dataset.categoriaSubtotal || '0') || 0;
            }
        });

        if (totalCell) {
            totalCell.textContent = `${formatNumber(visibleTotal)} Lps`;
        }

        const fechaInfo = shell.querySelector('.categoria-fecha-filter-info');
        if (fechaInfo) {
            if (selectedDate === '__ALL__') {
                fechaInfo.textContent = selectedActivity === ''
                    ? 'Mostrando todos los registros de la categoría.'
                    : `Mostrando ${visibleCount} registros para la búsqueda ${selectedActivity}.`;
            } else {
                const [year, month, day] = selectedDate.split('-');
                const label = day && month && year ? `${day}/${month}/${year}` : selectedDate;
                fechaInfo.textContent = selectedActivity === ''
                    ? `Mostrando ${visibleCount} registros para la fecha ${label}.`
                    : `Mostrando ${visibleCount} registros para la fecha ${label} y la búsqueda ${selectedActivity}.`;
            }
        }

        const actividadInfo = shell.querySelector('.categoria-actividad-filter-info');
        if (actividadInfo) {
            if (selectedActivity === '') {
                actividadInfo.textContent = selectedDate === '__ALL__'
                    ? 'Mostrando todos los insumos y descripciones de la categoría.'
                    : `Mostrando ${visibleCount} registros para la fecha seleccionada.`;
            } else {
                actividadInfo.textContent = selectedDate === '__ALL__'
                    ? `Mostrando ${visibleCount} registros para la búsqueda ${selectedActivity}.`
                    : `Mostrando ${visibleCount} registros para la búsqueda ${selectedActivity} en la fecha seleccionada.`;
            }
        }

        const categoria = shell.dataset.exportCategoria || '';
        const excelBase = shell.dataset.exportExcelBase || '';
        const pdfBase = shell.dataset.exportPdfBase || '';
        const exportLinks = Array.from(shell.querySelectorAll('.categoria-export-link'));

        exportLinks.forEach((link) => {
            const baseUrl = link.dataset.exportType === 'pdf' ? pdfBase : excelBase;

            if (!baseUrl || !categoria) {
                return;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('categoria', categoria);

            if (selectedDate !== '__ALL__') {
                url.searchParams.set('fecha', selectedDate);
            } else {
                url.searchParams.delete('fecha');
            }

            if (selectedActivity !== '') {
                url.searchParams.set('descripcion', selectedActivity);
            } else {
                url.searchParams.delete('descripcion');
                url.searchParams.delete('actividad');
            }

            link.href = url.toString();
        });
    }

    async function cargarDetallePorCategoria(button) {
        if (!categoriaDetailContainer) {
            return;
        }

        categoriaButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');

        categoriaDetailContainer.innerHTML = '<div class="text-center text-muted py-5">Cargando detalle de categoría...</div>';

        try {
            const response = await fetch(button.dataset.url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar el detalle de la categoría.');
            }

            categoriaDetailContainer.innerHTML = await response.text();
            aplicarFiltrosCategoria(categoriaDetailContainer.querySelector('.categoria-detail-card-shell'));
        } catch (error) {
            categoriaDetailContainer.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el detalle de la categoría seleccionada.</div>';
        }
    }

    categoriaButtons.forEach((button) => {
        button.addEventListener('click', function () {
            cargarDetallePorCategoria(button);
        });
    });

    document.addEventListener('change', function (event) {
        const filterSelect = event.target.closest('.categoria-fecha-select');

        if (!filterSelect) {
            return;
        }

        const shell = filterSelect.closest('.categoria-detail-card-shell');

        if (!shell) {
            return;
        }

        aplicarFiltrosCategoria(shell);
    });

    document.addEventListener('input', function (event) {
        const filterSelect = event.target.closest('.categoria-actividad-select');

        if (!filterSelect) {
            return;
        }

        const shell = filterSelect.closest('.categoria-detail-card-shell');

        if (!shell) {
            return;
        }

        aplicarFiltrosCategoria(shell);
    });

    if (categoriaButtons.length) {
        cargarDetallePorCategoria(categoriaButtons[0]);
    }
});
</script>
@endpush
@endsection