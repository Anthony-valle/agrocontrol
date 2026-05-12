<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Categoría</small><h5 class="mt-2 mb-0">{{ $categoria->nombre }}</h5></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Insumos</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['insumos']) }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Stock total</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['stock_total'], 2) }}</h3></div></div></div>
    <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Valor total</small><h3 class="mt-2 mb-0 text-primary">{{ agro_number($metricas['valor_total'], 2) }} Lps</h3></div></div></div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Insumos de la categoría</h5>
        <span class="badge bg-warning text-dark">Stock bajo: {{ agro_number($metricas['stock_bajo']) }}</span>
    </div>
    <div class="card-body pt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <select id="categoriaPerPage" class="form-select form-select-sm" style="width: auto;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15" selected>15</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    <small class="text-muted text-nowrap">registros</small>
                </div>

                <div class="input-group input-group-sm" style="max-width: 280px;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text" id="categoriaBusqueda" class="form-control border-start-0" placeholder="Buscar insumo, código o bodega...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaCategoriaInsumos">
                <thead class="table-light">
                    <tr>
                        <th>Insumo</th>
                        <th>Código</th>
                        <th>Unidad</th>
                        <th>Stock total</th>
                        <th>Stock mínimo</th>
                        <th>Valor estimado</th>
                        <th>Bodegas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($insumos as $insumo)
                        @php
                            $stockTotal = $insumo->inventarioBodegas->sum('stock_actual');
                            $valorTotal = $insumo->inventarioBodegas->sum(fn ($lote) => $lote->stock_actual * $lote->costo_promedio);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $insumo->nombre }}</div>
                                <small class="text-muted">{{ $insumo->ingrediente_activo ?: 'Sin ingrediente activo' }}</small>
                            </td>
                            <td>{{ $insumo->codigo }}</td>
                            <td>{{ $insumo->unidad_medida }}</td>
                            <td class="fw-bold {{ $insumo->stock_minimo !== null && $stockTotal <= $insumo->stock_minimo ? 'text-danger' : '' }}">{{ agro_number($stockTotal, 2) }}</td>
                            <td>{{ agro_number((float) ($insumo->stock_minimo ?? 0), 2) }}</td>
                            <td>{{ agro_number($valorTotal, 2) }} Lps</td>
                            <td>
                                @forelse($insumo->inventarioBodegas as $lote)
                                    <div class="small">{{ $lote->bodega->nombre ?? '-' }}: {{ agro_number($lote->stock_actual, 2) }}</div>
                                @empty
                                    <span class="text-muted">Sin existencias</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay insumos asociados a esta categoría.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function initCategoriaTable() {
        const table = document.getElementById('tablaCategoriaInsumos');
        const perPageSelect = document.getElementById('categoriaPerPage');
        const searchInput = document.getElementById('categoriaBusqueda');

        if (!table || !table.tBodies || !table.tBodies[0] || !perPageSelect || !searchInput) {
            return;
        }

        const rows = Array.from(table.tBodies[0].rows);
        const tableWrapper = table.closest('.table-responsive');

        if (!rows.length || !tableWrapper) {
            return;
        }

        const previousContainer = document.getElementById('categoriaPaginationContainer');
        if (previousContainer) {
            previousContainer.remove();
        }

        const paginationContainer = document.createElement('div');
        paginationContainer.id = 'categoriaPaginationContainer';
        paginationContainer.className = 'd-flex flex-wrap justify-content-between align-items-center gap-2 mt-3';

        const info = document.createElement('small');
        info.className = 'text-muted';

        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'Paginacion de insumos por categoria');

        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm mb-0';
        nav.appendChild(ul);

        paginationContainer.appendChild(info);
        paginationContainer.appendChild(nav);
        tableWrapper.insertAdjacentElement('afterend', paginationContainer);

        const state = { currentPage: 1 };

        function normalizeText(value) {
            return (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function getFilteredRows() {
            const search = normalizeText(searchInput.value.trim());
            if (!search) {
                return rows;
            }

            return rows.filter((row) => Array.from(row.cells).some((cell) => normalizeText(cell.textContent).includes(search)));
        }

        function renderPagination(filteredRows, totalPages) {
            ul.innerHTML = '';

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
                ul.appendChild(li);
            }

            addItem('Anterior', Math.max(1, state.currentPage - 1), state.currentPage === 1, false);

            for (let page = 1; page <= totalPages; page += 1) {
                addItem(String(page), page, false, page === state.currentPage);
            }

            addItem('Siguiente', Math.min(totalPages, state.currentPage + 1), state.currentPage === totalPages, false);

            if (!filteredRows.length || totalPages <= 1) {
                nav.style.display = 'none';
            } else {
                nav.style.display = '';
            }
        }

        function render() {
            const perPage = parseInt(perPageSelect.value, 10) || 15;
            const filteredRows = getFilteredRows();
            const totalPages = Math.max(1, Math.ceil(filteredRows.length / perPage));

            if (state.currentPage > totalPages) {
                state.currentPage = totalPages;
            }

            const start = (state.currentPage - 1) * perPage;
            const end = start + perPage;
            const visibleRows = filteredRows.slice(start, end);

            rows.forEach((row) => {
                row.style.display = 'none';
            });

            visibleRows.forEach((row) => {
                row.style.display = '';
            });

            if (!filteredRows.length) {
                info.textContent = 'Sin resultados para la busqueda actual.';
            } else {
                info.textContent = `Mostrando ${start + 1} a ${Math.min(start + visibleRows.length, filteredRows.length)} de ${filteredRows.length} registros`;
            }

            renderPagination(filteredRows, totalPages);
        }

        searchInput.addEventListener('input', function () {
            state.currentPage = 1;
            render();
        });

        perPageSelect.addEventListener('change', function () {
            state.currentPage = 1;
            render();
        });

        render();
    })();
</script>