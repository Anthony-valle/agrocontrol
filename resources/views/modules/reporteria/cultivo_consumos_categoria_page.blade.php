@extends('layouts.main')

@section('titulo', 'Desglose por categoría')

@section('contenido')
<main id="main" class="main reporteria-shell">
    @include('shared.reporteria_styles')
    <style>
        .reporteria-shell {
            --cg-navy: #123b67;
            --cg-shadow: 0 18px 45px rgba(18, 59, 103, 0.08);
            background:
                radial-gradient(circle at top left, rgba(31, 107, 143, 0.09), transparent 28%),
                linear-gradient(180deg, #f7fbfd 0%, #f5f2e9 100%);
            padding-bottom: 2rem;
        }

        .categoria-page-card {
            border: 1px solid rgba(18, 59, 103, 0.08) !important;
            border-radius: 1.5rem !important;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: var(--cg-shadow);
            overflow: hidden;
        }

        .categoria-page-card .card-body,
        .categoria-page-card .card-header {
            padding-inline: 1.5rem;
        }

        .categoria-detail-card-shell .card-body {
            padding: 1rem 1.1rem;
        }

        .categoria-detail-card-shell .row.g-3 {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.5rem;
        }

        .categoria-detail-card-shell label.small {
            font-size: 0.72rem !important;
            letter-spacing: 0.03em;
            margin-bottom: 0.2rem;
        }

        .categoria-detail-card-shell .form-select,
        .categoria-detail-card-shell .form-control {
            min-height: 2rem;
            padding-top: 0.2rem;
            padding-bottom: 0.2rem;
            font-size: 0.92rem;
        }

        .categoria-actividad-autocomplete {
            position: relative;
        }

        .categoria-actividad-suggestions {
            position: absolute;
            top: calc(100% + 0.3rem);
            left: 0;
            right: 0;
            z-index: 30;
            max-height: 16rem;
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(18, 59, 103, 0.12);
            border-radius: 0.8rem;
            box-shadow: 0 18px 40px rgba(18, 59, 103, 0.14);
            padding: 0.35rem;
        }

        .categoria-actividad-suggestion {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            border-radius: 0.6rem;
            padding: 0.55rem 0.7rem;
            color: #123b67;
            font-size: 0.9rem;
            line-height: 1.25;
        }

        .categoria-actividad-suggestion:hover,
        .categoria-actividad-suggestion:focus {
            background: #eaf4ff;
            color: #0f2f4f;
            outline: none;
        }

        .categoria-actividad-suggestion-empty {
            padding: 0.55rem 0.7rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .categoria-detail-card-shell .fs-4 {
            font-size: 1.05rem !important;
        }

        .categoria-detail-card-shell .small.text-muted {
            font-size: 0.76rem;
            line-height: 1.25;
        }

        .categoria-detail-card-shell .mt-1 {
            margin-top: 0.2rem !important;
        }

        .categoria-detail-card-shell .mt-2 {
            margin-top: 0.25rem !important;
        }

        .categoria-detail-card-shell .btn.btn-sm {
            padding: 0.38rem 0.7rem;
            font-size: 0.82rem;
        }

        .categoria-pagination-shell .pagination {
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .categoria-pagination-shell .page-link {
            min-width: 2.2rem;
            text-align: center;
            white-space: nowrap;
            font-size: 0.82rem;
        }

        .pagetitle h1 {
            color: var(--cg-navy);
            font-weight: 800;
            letter-spacing: -0.03em;
        }
    </style>

    <div class="pagetitle">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h1>Desglose completo por categoría</h1>
                <p class="text-muted mb-0">El detalle se muestra en una página aparte para revisar todo el movimiento de la categoría.</p>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">Volver al detalle mensual</a>
        </div>
    </div>

    <section class="section">
        <div class="card categoria-page-card">
            <div class="card-body py-4">
                @include('modules.reporteria.partials.cultivo_consumos_categoria')
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paginationState = { page: 1, perPage: 15 };

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

    function getActivityOptions(shell) {
        const rows = Array.from(shell?.querySelectorAll('tbody tr[data-categoria-actividad]') || []);
        return Array.from(new Set(rows
            .map((row) => (row.dataset.categoriaActividad || '').trim())
            .filter(Boolean)))
            .sort((left, right) => left.localeCompare(right, 'es', { sensitivity: 'base' }));
    }

    function getActivityTerm(shell) {
        const input = shell?.querySelector('.categoria-actividad-select');
        return (input?.value || '').trim();
    }

    function toggleActivitySuggestions(shell, shouldShow) {
        const box = shell?.querySelector('.categoria-actividad-suggestions');

        if (!box) {
            return;
        }

        box.classList.toggle('d-none', !shouldShow);
    }

    function renderActivitySuggestions(shell) {
        const input = shell?.querySelector('.categoria-actividad-select');
        const box = shell?.querySelector('.categoria-actividad-suggestions');

        if (!input || !box) {
            return;
        }

        const term = getActivityTerm(shell).toLowerCase();
        const options = getActivityOptions(shell)
            .filter((option) => term === '' || option.toLowerCase().includes(term))
            .slice(0, 10);

        box.innerHTML = '';

        if (options.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'categoria-actividad-suggestion-empty';
            empty.textContent = box.dataset.emptyMessage || 'No hay coincidencias.';
            box.appendChild(empty);
            toggleActivitySuggestions(shell, term !== '');
            return;
        }

        options.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'categoria-actividad-suggestion';
            button.textContent = option;
            button.dataset.value = option;
            box.appendChild(button);
        });

        toggleActivitySuggestions(shell, true);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    }

    function renderPagination(totalRows) {
        const list = document.getElementById('categoriaPaginationList');
        const info = document.getElementById('categoriaPaginationInfo');

        if (!list || !info) {
            return;
        }

        list.innerHTML = '';

        if (totalRows === 0) {
            info.textContent = 'No hay registros para mostrar.';
            return;
        }

        const totalPages = Math.max(1, Math.ceil(totalRows / paginationState.perPage));

        if (paginationState.page > totalPages) {
            paginationState.page = totalPages;
        }

        const start = (paginationState.page - 1) * paginationState.perPage;
        const end = Math.min(start + paginationState.perPage, totalRows);
        info.textContent = `Mostrando ${start + 1}-${end} de ${totalRows} registros | Hoja ${paginationState.page} de ${totalPages}`;

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
                paginationState.page = page;
                aplicarFiltrosCategoria(document.querySelector('.categoria-detail-card-shell'));
            });

            li.appendChild(button);
            list.appendChild(li);
        }

        function addEllipsis() {
            const li = document.createElement('li');
            li.className = 'page-item disabled';

            const span = document.createElement('span');
            span.className = 'page-link';
            span.textContent = '...';

            li.appendChild(span);
            list.appendChild(li);
        }

        addItem('Anterior', Math.max(1, paginationState.page - 1), paginationState.page === 1, false);

        const windowSize = 2;
        const startPage = Math.max(1, paginationState.page - windowSize);
        const endPage = Math.min(totalPages, paginationState.page + windowSize);

        if (startPage > 1) {
            addItem('1', 1, false, paginationState.page === 1);
        }

        if (startPage > 2) {
            addEllipsis();
        }

        for (let page = startPage; page <= endPage; page += 1) {
            addItem(String(page), page, false, page === paginationState.page);
        }

        if (endPage < totalPages - 1) {
            addEllipsis();
        }

        if (endPage < totalPages) {
            addItem(String(totalPages), totalPages, false, paginationState.page === totalPages);
        }

        addItem('Siguiente', Math.min(totalPages, paginationState.page + 1), paginationState.page === totalPages, false);
    }

    function aplicarFiltrosCategoria(shell) {
        if (!shell) {
            return;
        }

        const selectedDateInput = shell.querySelector('.categoria-fecha-select');
        const selectedCategoryInput = shell.querySelector('.categoria-categoria-select');
        const selectedDate = selectedDateInput?.value || '__ALL__';
        const selectedActivity = getActivityTerm(shell);
        const selectedCategory = selectedCategoryInput?.value || '';
        const rows = Array.from(shell.querySelectorAll('tbody tr[data-categoria-fecha]'));
        const totalCell = shell.querySelector('[data-categoria-total]');

        let visibleCount = 0;
        let visibleTotal = 0;

        const filteredRows = [];

        rows.forEach((row) => {
            const matchesDate = selectedDate === '__ALL__' || row.dataset.categoriaFecha === selectedDate;
            const descripcionFila = normalizeSearchText(row.dataset.categoriaBusqueda || row.dataset.categoriaActividad || '');
            const matchesActivity = selectedActivity === '' || descripcionFila.includes(normalizeSearchText(selectedActivity));
            const matches = matchesDate && matchesActivity;

            if (matches) {
                visibleCount += 1;
                visibleTotal += Number.parseFloat(row.dataset.categoriaSubtotal || '0') || 0;
                filteredRows.push(row);
            }
        });

        const totalRows = filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(Math.max(totalRows, 1) / paginationState.perPage));

        if (paginationState.page > totalPages) {
            paginationState.page = totalPages;
        }

        const start = (paginationState.page - 1) * paginationState.perPage;
        const end = start + paginationState.perPage;
        const visibleRows = new Set(filteredRows.slice(start, end));

        rows.forEach((row) => {
            row.style.display = visibleRows.has(row) ? '' : 'none';
        });

        if (totalCell) {
            totalCell.textContent = `${formatNumber(visibleTotal)} Lps`;
        }

        renderPagination(totalRows);

        const fechaInfo = shell.querySelector('.categoria-fecha-filter-info');
        if (fechaInfo) {
            if (selectedDate === '__ALL__') {
                fechaInfo.textContent = selectedActivity === ''
                    ? 'Mostrando todos los registros de la categoría.'
                    : `Mostrando ${visibleCount} registros para la descripción ${selectedActivity}.`;
            } else {
                const [year, month, day] = selectedDate.split('-');
                const label = day && month && year ? `${day}/${month}/${year}` : selectedDate;
                fechaInfo.textContent = selectedActivity === ''
                    ? `Mostrando ${visibleCount} registros para la fecha ${label}.`
                    : `Mostrando ${visibleCount} registros para la fecha ${label} y la descripción ${selectedActivity}.`;
            }
        }

        const actividadInfo = shell.querySelector('.categoria-actividad-filter-info');
        if (actividadInfo) {
            if (selectedActivity === '') {
                actividadInfo.textContent = selectedDate === '__ALL__'
                    ? 'Mostrando todas las descripciones de la categoría.'
                    : `Mostrando ${visibleCount} registros para la fecha seleccionada.`;
            } else {
                actividadInfo.textContent = selectedDate === '__ALL__'
                    ? `Mostrando ${visibleCount} registros para la descripción ${selectedActivity}.`
                    : `Mostrando ${visibleCount} registros para la descripción ${selectedActivity} en la fecha seleccionada.`;
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
            url.searchParams.set('categoria', selectedCategory || categoria);

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
    document.addEventListener('change', function (event) {
        const categorySelect = event.target.closest('.categoria-categoria-select');

        if (categorySelect) {
            const baseUrl = categorySelect.dataset.pageBaseUrl || '';

            if (!baseUrl) {
                return;
            }

            const shell = categorySelect.closest('.categoria-detail-card-shell');
            const fecha = shell?.querySelector('.categoria-fecha-select')?.value || '__ALL__';
            const descripcion = getActivityTerm(shell);
            const url = new URL(baseUrl, window.location.origin);

            url.searchParams.set('categoria', categorySelect.value);

            if (fecha !== '__ALL__') {
                url.searchParams.set('fecha', fecha);
            }

            if (descripcion !== '') {
                url.searchParams.set('descripcion', descripcion);
            }

            window.location.href = url.toString();
            return;
        }

        const filterSelect = event.target.closest('.categoria-fecha-select');

        if (!filterSelect) {
            return;
        }

        paginationState.page = 1;

        const shell = filterSelect.closest('.categoria-detail-card-shell');

        if (!shell) {
            return;
        }

        aplicarFiltrosCategoria(shell);
    });

    document.addEventListener('input', function (event) {
        const activityText = event.target.closest('.categoria-actividad-select');

        if (!activityText) {
            return;
        }

        paginationState.page = 1;

        const shell = activityText.closest('.categoria-detail-card-shell');

        if (!shell) {
            return;
        }

        renderActivitySuggestions(shell);
        aplicarFiltrosCategoria(shell);
    });

    document.addEventListener('focusin', function (event) {
        const activityText = event.target.closest('.categoria-actividad-select');

        if (!activityText) {
            return;
        }

        const shell = activityText.closest('.categoria-detail-card-shell');

        if (!shell) {
            return;
        }

        renderActivitySuggestions(shell);
    });

    document.addEventListener('click', function (event) {
        const suggestion = event.target.closest('.categoria-actividad-suggestion');

        if (suggestion) {
            const shell = suggestion.closest('.categoria-detail-card-shell');
            const input = shell?.querySelector('.categoria-actividad-select');

            if (!shell || !input) {
                return;
            }

            input.value = suggestion.dataset.value || '';
            paginationState.page = 1;
            toggleActivitySuggestions(shell, false);
            aplicarFiltrosCategoria(shell);
            input.focus();
            return;
        }

        document.querySelectorAll('.categoria-detail-card-shell').forEach((shell) => {
            const autocomplete = shell.querySelector('.categoria-actividad-autocomplete');

            if (autocomplete && !autocomplete.contains(event.target)) {
                toggleActivitySuggestions(shell, false);
            }
        });
    });

    aplicarFiltrosCategoria(document.querySelector('.categoria-detail-card-shell'));
});
</script>
@endpush