@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

    <style>
        .tabla-consumo-wrap {
            border: 1px solid #d9e2ec;
            border-radius: 4px;
            overflow-x: auto;
            overflow-y: hidden;
            background: #fff;
            scrollbar-width: thin;
            scrollbar-color: #8aa7b7 #eef3f7;
        }

        .tabla-consumo-wrap::-webkit-scrollbar {
            height: 12px;
        }

        .tabla-consumo-wrap::-webkit-scrollbar-track {
            background: #eef3f7;
            border-radius: 0;
        }

        .tabla-consumo-wrap::-webkit-scrollbar-thumb {
            background: #8aa7b7;
            border-radius: 2px;
            border: 2px solid #eef3f7;
        }

        .tabla-consumo-wrap::-webkit-scrollbar-thumb:hover {
            background: #5f8194;
        }

        .tabla-consumo {
            margin-bottom: 0;
            min-width: 1120px;
        }

        .tabla-consumo thead th {
            background: #16624f;
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            border-color: #135241;
            vertical-align: middle;
            white-space: nowrap;
        }

        .tabla-consumo tbody td,
        .tabla-consumo tfoot td {
            vertical-align: middle;
            border-color: #dfe7ef;
        }

        .tabla-consumo tbody tr:nth-child(even) {
            background: #f8fbfd;
        }

        .tabla-consumo .insumo-nombre {
            font-weight: 700;
            color: #152536;
        }

        .tabla-consumo .lote-pill {
            display: inline-block;
            padding: 0.35rem 0.7rem;
            border: 1px solid #d7e1ea;
            border-radius: 999px;
            background: #fff;
            color: #486074;
            font-size: 0.9rem;
            line-height: 1.2;
        }

        .tabla-consumo .categoria-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .tabla-consumo .categoria-pill.categoria-mano-obra {
            background: #d9f1fb;
            color: #0c5460;
            border: 1px solid #bce4f3;
        }

        .tabla-consumo .categoria-pill.categoria-insumo {
            background: #f3f6f9;
            color: #34495e;
            border: 1px solid #d5dee8;
        }

        .tabla-consumo .subtotal-cell,
        .tabla-consumo .total-general {
            color: #198754;
            font-weight: 700;
        }

        .tabla-consumo .descripcion-cell {
            min-width: 220px;
        }

        .tabla-consumo-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }

        .tabla-consumo-toolbar .resumen-tabla {
            color: #6c757d;
            font-size: 0.92rem;
        }

        .tabla-consumo-toolbar .hoja-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border: 1px solid #d9e2ec;
            border-radius: 4px;
            background: #f8fbfd;
            color: #1f3d52;
            font-size: 0.86rem;
            font-weight: 700;
        }

        .tabla-consumo-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 0;
            flex-wrap: wrap;
        }

        .tabla-consumo-pagination .pagination {
            margin-bottom: 0;
        }

        @media (max-width: 991.98px) {
            .tabla-consumo-toolbar,
            .tabla-consumo-pagination {
                align-items: stretch;
            }

            .tabla-consumo {
                min-width: 0;
            }

            .tabla-consumo thead {
                display: none;
            }

            .tabla-consumo,
            .tabla-consumo tbody,
            .tabla-consumo tr,
            .tabla-consumo td,
            .tabla-consumo tfoot,
            .tabla-consumo tfoot tr {
                display: block;
                width: 100%;
            }

            .tabla-consumo tbody tr,
            .tabla-consumo tfoot tr {
                border-bottom: 1px solid #dfe7ef;
                padding: 0.85rem 0;
            }

            .tabla-consumo tbody td,
            .tabla-consumo tfoot td {
                border: 0;
                padding: 0.45rem 1rem;
                text-align: left !important;
            }

            .tabla-consumo tbody td::before,
            .tabla-consumo tfoot td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 0.18rem;
                font-size: 0.76rem;
                font-weight: 700;
                text-transform: uppercase;
                color: #6c7a89;
                letter-spacing: 0.03em;
            }

            .tabla-consumo tfoot td.total-general {
                font-size: 1.05rem;
            }
        }
    </style>

    <div class="pagetitle">
        <h1>Resumen del Consumo</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fa-solid fa-boxes-stacked me-2"></i>
                    Consumo #{{ $consumo->id }}
                </h5>
            </div>

            <div class="card-body">

                <div class="row mb-4 mt-3">
                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Cultivo</label>
                        <div class="fw-bold text-dark">{{ $consumo->cultivo->nombre ?? '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Fecha Consumo</label>
                        <div>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Semana Cultivo</label>
                        <div>{{ $consumo->cultivo?->calcularSemanaCultivoParaFecha($consumo->fecha_consumo) ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Semana Año</label>
                        <div>{{ $consumo->cultivo?->calcularSemanaAnioParaFecha($consumo->fecha_consumo) ?: '-' }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Total Consumo</label>
                        <div class="fw-bold text-success">{{ agro_number($consumo->total, 2) }} L</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold d-block text-muted small uppercase">Registrado por</label>
                        {{-- Corregido: Usamos la relación del objeto $consumo principal --}}
                        <div>{{ $consumo->usuario->name ?? 'Sistema' }}</div>
                    </div>

                    <div class="col-md-3 mt-3">
                        <label class="fw-bold d-block text-muted small uppercase">Estado</label>
                        @php($estado = $consumo->estado_normalizado)
                        <div>
                            @if($estado === 'ANULADO')
                                <span class="badge bg-danger">ANULADO</span>
                            @elseif($estado === 'FINALIZADO')
                                <span class="badge bg-success">FINALIZADO</span>
                            @else
                                <span class="badge bg-warning text-dark">PENDIENTE</span>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-3 mt-3">
                        <label class="fw-bold d-block text-muted small uppercase">Validado por</label>
                        <div>{{ $estado === 'FINALIZADO' ? ($consumo->validador->usuario ?? $consumo->validador->name ?? '-') : 'Pendiente' }}</div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <label class="fw-bold d-block text-muted small uppercase">Anulación</label>
                        @if($estado === 'ANULADO')
                            <div>
                                <span class="badge bg-danger-subtle text-danger border">{{ $consumo->anulador->usuario ?? $consumo->anulador->name ?? 'Sistema' }}</span>
                                <span class="text-muted ms-2">{{ optional($consumo->fecha_anulacion)->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="small mt-1">{{ $consumo->motivo_anulacion ?? '-' }}</div>
                        @else
                            <div class="text-muted">No anulado</div>
                        @endif
                    </div>
                </div>

                <hr>

                <h5 class="mb-3">
                    <i class="fa-solid fa-list me-2"></i>
                    Detalle de Insumos y Labores
                </h5>

                <div class="tabla-consumo-toolbar">
                    <small class="resumen-tabla">Detalle paginado en 15 registros por hoja y con barra de desplazamiento.</small>
                    <span class="hoja-badge" id="consumoDetalleHojaActual">
                        <i class="fa-regular fa-file-lines"></i>
                        Hoja 1
                    </span>
                </div>

                <div class="table-responsive tabla-consumo-wrap">
                    <table class="table table-bordered table-sm align-middle tabla-consumo" id="consumoDetalleTabla">
                        <thead class="table-secondary">
                            <tr>
                                <th>Codigo</th>
                                <th>Insumo</th>
                                <th>Bodega</th>
                                <th>Lote</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-end">Costo Unitario</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($consumo->detalles as $detalle)
                                <tr data-consumo-row>
                                    <td data-label="Codigo">
                                        @if($detalle->categoria === 'Mano de Obra')
                                            <span class="text-muted small">N/A</span>
                                        @else
                                            {{ $detalle->insumo->codigo ?? '-' }}
                                        @endif
                                    </td>
                                    {{-- CORRECCIÓN: Si es Mano de Obra, no mostramos el insumo --}}
                                    <td class="insumo-nombre" data-label="Insumo">
                                        @if($detalle->categoria === 'Mano de Obra')
                                            <span class="text-muted small italic">N/A (Labor)</span>
                                        @else
                                            {{ $detalle->insumo->nombre ?? '-' }}
                                        @endif
                                    </td>
                                    
                                    <td data-label="Bodega">{{ $detalle->bodega->nombre ?? '-' }}</td>
                                    <td data-label="Lote">
                                        <span class="lote-pill">
                                            {{ $detalle->lote ?? '-' }}
                                        </span>
                                    </td>
                                    <td data-label="Categoria">
                                        <span class="categoria-pill {{ $detalle->categoria === 'Mano de Obra' ? 'categoria-mano-obra' : 'categoria-insumo' }}">
                                            {{ $detalle->categoria ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="small descripcion-cell" data-label="Descripcion">{{ $detalle->descripcion ?? '-' }}</td>
                                    <td class="text-center" data-label="Cantidad">{{ agro_number($detalle->cantidad, 2) }}</td>
                                    <td class="text-center small" data-label="Unidad">{{ $detalle->unidad_medida ?? '-' }}</td>
                                    <td class="text-end" data-label="Costo Unitario">{{ agro_number($detalle->costo_unitario, 2) }} L</td>
                                    <td class="text-end subtotal-cell" data-label="Subtotal">
                                        {{ agro_number($detalle->subtotal, 2) }} L
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="table-light">
                            <tr>
                                <td colspan="9" class="text-end fw-bold py-3" data-label="Resumen">TOTAL GENERAL</td>
                                <td class="text-end py-3 total-general" style="font-size: 1.1rem;" data-label="Total General">
                                    {{ agro_number($consumo->total, 2) }} L
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($consumo->detalles->count() > 15)
                    <div class="tabla-consumo-pagination">
                        <small class="text-muted" id="consumoDetallePaginacionInfo"></small>
                        <nav aria-label="Paginacion del detalle del consumo">
                            <ul class="pagination pagination-sm" id="consumoDetallePaginacion"></ul>
                        </nav>
                    </div>
                @endif
            </div>

            <div class="card-footer text-end bg-white border-top-0">
                @if($consumo->estado_normalizado !== 'FINALIZADO' && $consumo->estado_normalizado !== 'ANULADO')
                    <form action="{{ route('consumo.finalizar', $consumo->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success px-4 me-2">
                            <i class="fa-solid fa-check me-2"></i> Finalizar Consumo
                        </button>
                    </form>
                @endif
                <a href="{{ route('consumo.index') }}" class="btn btn-secondary px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i> Volver al Listado
                </a>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.getElementById('consumoDetalleTabla');
        const info = document.getElementById('consumoDetallePaginacionInfo');
        const list = document.getElementById('consumoDetallePaginacion');
        const hojaBadge = document.getElementById('consumoDetalleHojaActual');

        if (!table || !info || !list || !hojaBadge) {
            return;
        }

        const rows = Array.from(table.querySelectorAll('tbody tr[data-consumo-row]'));
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

            rows.forEach(function (row) {
                row.style.display = visibleRows.has(row) ? '' : 'none';
            });

            info.textContent = `Mostrando ${start + 1}-${Math.min(end, totalRows)} de ${totalRows} registros`;
            hojaBadge.innerHTML = '<i class="fa-regular fa-file-lines"></i> Hoja ' + state.page + ' de ' + totalPages;
            renderPagination(totalPages);
        }

        render();
    });
</script>
@endsection