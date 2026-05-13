@extends('layouts.main')

@section('titulo', 'Detalle por Categoria')

@section('contenido')
<main id="main" class="main">
    <style>
        .categoria-detail-hero {
            border: 0;
            border-radius: 1.25rem;
            background: linear-gradient(135deg, #f3fbf5 0%, #eef7f2 52%, #e7f2ec 100%);
            box-shadow: 0 0.8rem 2rem rgba(15, 23, 42, 0.08);
        }

        .categoria-detail-hero .hero-title {
            margin: 0;
            font-size: clamp(1.7rem, 2vw, 2.2rem);
            font-weight: 800;
            color: #123b2a;
        }

        .categoria-detail-hero .hero-subtitle {
            color: #5c6b62;
        }

        .categoria-detail-topmeta {
            text-align: right;
            color: #5c6b62;
        }

        .categoria-surface-card {
            border: 0;
            border-radius: 1.2rem;
            box-shadow: 0 0.6rem 1.5rem rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }

        .categoria-mini-kpi {
            border: 1px solid #e4ece7;
            border-radius: 1rem;
            padding: 0.9rem 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f9fcfa 100%);
            min-height: 100%;
        }

        .categoria-mini-kpi .label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .categoria-mini-kpi .value {
            margin: 0;
            font-size: 1.55rem;
            line-height: 1.1;
            font-weight: 800;
            color: #17324d;
        }
    </style>
    <div class="pagetitle">
        <h1>Detalle por Categoria</h1>
    </div>

    <section class="section">
        <div class="card categoria-detail-hero mb-4">
            <div class="card-body p-4 p-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h2 class="hero-title">{{ $categoria }}</h2>
                        <p class="hero-subtitle mb-0">Cultivo: {{ $cultivo->nombre }} · Lote: {{ $cultivo->lote->nombre ?? '-' }}</p>
                    </div>
                    <div class="categoria-detail-topmeta">
                        <a href="{{ route('reporte.cultivo.final', $cultivo->id) }}" class="btn btn-outline-secondary btn-sm mb-2">
                            <i class="bi bi-arrow-left me-1"></i> Volver al reporte
                        </a>
                        <div class="small">Plan: {{ agro_number($planCostoTotal, 2) }} Lps | Real: {{ agro_number($realCostoTotal, 2) }} Lps</div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-sm-6 col-xl-3">
                        <div class="categoria-mini-kpi">
                            <span class="label">Plan Registros</span>
                            <p class="value">{{ agro_number($planDetallesCategoria->count()) }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="categoria-mini-kpi">
                            <span class="label">Real Registros</span>
                            <p class="value">{{ agro_number($consumosCategoria->count()) }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="categoria-mini-kpi">
                            <span class="label">Cantidad Plan</span>
                            <p class="value">{{ agro_number($planCantidadTotal, 2) }}</p>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="categoria-mini-kpi">
                            <span class="label">Cantidad Real</span>
                            <p class="value">{{ agro_number($realCantidadTotal, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="categoria-surface-card">
            @include('modules.reporteria.partials.cultivo_categoria_detalle')
        </div>
    </section>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function initCategoryTablePagination(config) {
        const table = document.getElementById(config.tableId);
        const info = document.getElementById(config.infoId);
        const list = document.getElementById(config.listId);

        if (!table || !info || !list) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr[data-detail-row]'));

        if (!rows.length) {
            info.textContent = 'No hay registros para mostrar.';
            return;
        }

        const perPage = 15;
        const state = { page: 1 };

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
            const totalRows = rows.length;
            const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

            if (state.page > totalPages) {
                state.page = totalPages;
            }

            const start = (state.page - 1) * perPage;
            const end = start + perPage;
            const visibleRows = new Set(rows.slice(start, end));

            rows.forEach((row) => {
                row.style.display = visibleRows.has(row) ? '' : 'none';
            });

            info.textContent = `Mostrando ${start + 1}-${Math.min(end, totalRows)} de ${totalRows} registros | Hoja ${state.page} de ${totalPages}`;
            renderPagination(totalPages);
        }

        render();
    }

    initCategoryTablePagination({
        tableId: 'planCategoriaTable',
        infoId: 'planCategoriaPaginationInfo',
        listId: 'planCategoriaPaginationList',
    });

    initCategoryTablePagination({
        tableId: 'realCategoriaTable',
        infoId: 'realCategoriaPaginationInfo',
        listId: 'realCategoriaPaginationList',
    });
});
</script>
@endpush
@endsection
