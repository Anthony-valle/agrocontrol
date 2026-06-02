@extends('layouts.main')

@section('titulo', 'Consumos de Insumos')

@section('contenido')
<main id="main" class="main">
    <style>
        #consumoPaginacionWrap.agro-mobile-pagination {
            align-items: center;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(15, 90, 67, 0.12);
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbf9 100%);
            box-shadow: 0 4px 12px rgba(15, 90, 67, 0.05);
        }

        #consumoPaginacionWrap.agro-mobile-pagination #consumoPaginacionInfo {
            margin: 0;
            overflow-wrap: break-word;
        }

        #consumoPaginacionWrap.agro-mobile-pagination nav {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        #consumoPaginacionWrap.agro-mobile-pagination .pagination {
            flex-wrap: nowrap;
            width: max-content;
        }

        #consumoPaginacionWrap.agro-mobile-pagination .page-item {
            flex: 0 0 auto;
        }

        #consumoPaginacionWrap.agro-mobile-pagination .page-link {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            border-radius: 10px;
            border-color: #d7e3dc;
            color: #175c43;
        }

        #consumoPaginacionWrap.agro-mobile-pagination .page-item.active .page-link {
            background: #17684b;
            border-color: #17684b;
            color: #fff;
        }

        #modalImportarConsumo .modal-dialog {
            max-width: min(1280px, calc(100vw - 1.5rem));
        }

        #modalImportarConsumo .modal-body {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(15, 90, 67, 0.45) rgba(15, 90, 67, 0.08);
        }

        #modalImportarConsumo .modal-body::-webkit-scrollbar {
            width: 10px;
        }

        #modalImportarConsumo .modal-body::-webkit-scrollbar-track {
            background: rgba(15, 90, 67, 0.08);
            border-radius: 999px;
        }

        #modalImportarConsumo .modal-body::-webkit-scrollbar-thumb {
            background: rgba(15, 90, 67, 0.45);
            border-radius: 999px;
        }

        #modalImportarConsumo .modal-footer {
            position: sticky;
            bottom: 0;
            background: #fff;
            z-index: 2;
        }

        #modalImportarConsumo .import-preview-shell {
            border: 1px solid rgba(15, 90, 67, 0.14);
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fbf9 100%);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72);
            overflow: hidden;
        }

        #modalImportarConsumo .import-preview-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: rgba(15, 90, 67, 0.05);
            border-bottom: 1px solid rgba(15, 90, 67, 0.12);
        }

        #modalImportarConsumo .import-preview-toolbar small {
            color: #4b5563;
        }

        #modalImportarConsumo .import-preview-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(15, 90, 67, 0.45) rgba(15, 90, 67, 0.1);
        }

        #modalImportarConsumo .import-preview-scroll::-webkit-scrollbar {
            height: 12px;
        }

        #modalImportarConsumo .import-preview-scroll::-webkit-scrollbar-track {
            background: rgba(15, 90, 67, 0.08);
        }

        #modalImportarConsumo .import-preview-scroll::-webkit-scrollbar-thumb {
            background: rgba(15, 90, 67, 0.45);
            border-radius: 999px;
        }

        #modalImportarConsumo .import-preview-table {
            min-width: 1380px;
            margin-bottom: 0;
        }

        #modalImportarConsumo .import-preview-table th {
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 1;
            background: var(--bs-table-bg, #f8f9fa);
        }

        #modalImportarConsumo .import-preview-table th,
        #modalImportarConsumo .import-preview-table td {
            min-width: 110px;
            vertical-align: top;
            word-break: break-word;
        }

        #modalImportarConsumo .import-preview-table th:nth-child(1),
        #modalImportarConsumo .import-preview-table td:nth-child(1),
        #modalImportarConsumo .import-preview-table th:nth-child(6),
        #modalImportarConsumo .import-preview-table td:nth-child(6),
        #modalImportarConsumo .import-preview-table th:nth-child(8),
        #modalImportarConsumo .import-preview-table td:nth-child(8),
        #modalImportarConsumo .import-preview-table th:nth-child(14),
        #modalImportarConsumo .import-preview-table td:nth-child(14),
        #modalImportarConsumo .import-preview-table th:nth-child(15),
        #modalImportarConsumo .import-preview-table td:nth-child(15) {
            min-width: 150px;
        }

        @media (max-width: 767.98px) {
            #consumoPaginacionWrap.agro-mobile-pagination {
                align-items: stretch;
            }

            #consumoPaginacionWrap.agro-mobile-pagination #consumoPaginacionInfo,
            #consumoPaginacionWrap.agro-mobile-pagination nav {
                width: 100%;
            }

            #consumoPaginacionWrap.agro-mobile-pagination .page-link {
                padding: 0.38rem 0.62rem;
                font-size: 0.88rem;
            }

            #modalImportarConsumo .modal-body {
                padding-inline: 1rem !important;
            }

            #modalImportarConsumo .import-preview-toolbar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="pagetitle">
        <h1>Consumos de Insumos</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title pb-0">Gestión de Consumos Registrados</h5>

                <!-- CONTROLES -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3 agro-table-toolbar">

                    <div class="d-flex align-items-center gap-3 agro-table-toolbar-group">
                        <select id="filtroCultivo" class="form-select form-select-sm agro-toolbar-select" style="width:auto; min-width:220px;">
                            <option value="">Todos los cultivos</option>
                            @foreach($cultivos as $cultivo)
                                <option value="{{ $cultivo->id }}">{{ $cultivo->nombre }}</option>
                            @endforeach
                        </select>

                        <div class="d-flex align-items-center gap-2 agro-toolbar-records">
                            <select id="customPerPage" class="form-select form-select-sm agro-toolbar-select" style="width:auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <small class="text-muted">registros</small>
                        </div>

                        <div class="input-group input-group-sm agro-toolbar-search" style="max-width:250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa fa-search text-muted"></i>
                            </span>
                            <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar consumo...">
                        </div>
                    </div>

                    <!-- BOTÓN CREAR -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if(auth()->user()?->canManageMassImports())
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportarConsumo">
                            <i class="fa-solid fa-file-import me-1"></i> Carga Masiva
                        </button>
                        @endif
                        @if(auth()->user()?->hasAnyRole(['admin', 'supervisor', 'propietario']))
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnAnularCultivo" disabled>
                            <i class="fa-solid fa-ban me-1"></i> Anular consumos del cultivo
                        </button>
                        @endif
                        <a href="{{ route('consumo.create') }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-circle-plus me-1"></i> Registrar Nuevo Consumo
                        </a>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-sm align-middle mb-0 w-100" id="tablaConsumos">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cultivo</th>
                                <th>Categoría aplicada</th>
                                <th>Cantidad</th>
                                <th>Precio unitario</th>
                                <th>Costo</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Registrado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consumos as $index => $c)
                                @php
                                    $categoriasAplicadas = $c->detalles
                                        ->pluck('categoria')
                                        ->map(fn ($categoria) => trim((string) $categoria))
                                        ->filter()
                                        ->unique()
                                        ->values();

                                    $categoriaVisible = $categoriasAplicadas->first() ?: '-';
                                    $preciosUnitarios = $c->detalles
                                        ->pluck('costo_unitario')
                                        ->map(fn ($precio) => round((float) $precio, 3))
                                        ->unique()
                                        ->values();

                                    $precioVisible = $preciosUnitarios->count() === 1
                                        ? agro_number((float) $preciosUnitarios->first(), 3)
                                        : 'Varios';
                                @endphp
                                <tr data-cultivo-id="{{ $c->cultivo_id }}">
                                    <td>{{ $c->id }}</td>
                                    <td>{{ $c->cultivo->nombre ?? '-' }}</td>
                                    <td title="{{ $categoriasAplicadas->isNotEmpty() ? $categoriasAplicadas->join(', ') : '-' }}">{{ $categoriaVisible }}</td>
                                    <td>{{ $c->detalles->sum('cantidad') }}</td>
                                    <td title="{{ $preciosUnitarios->count() > 1 ? $preciosUnitarios->map(fn ($precio) => agro_number((float) $precio, 3))->join(', ') : $precioVisible }}">{{ $precioVisible }}</td>
                                    <td>{{ agro_number($c->total, 2) }}</td>
                                    <td>{{ $c->detalles->first()?->descripcion ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($c->fecha_consumo)->format('d/m/Y') }}</td>
                                    <td>{{ $c->creador->usuario ?? 'Sistema' }}</td>

                                    <td class="text-center text-nowrap">
                                        <!-- VER (CORREGIDO) -->
                                        <button class="btn btn-info btn-sm btnVerConsumo" data-id="{{ $c->id }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        @if(
                                            $c->estado_normalizado === 'PENDIENTE'
                                            || ($c->estado_normalizado === 'FINALIZADO' && auth()->user()?->isSuperUser())
                                        )
                                            <a href="{{ route('consumo.edit', $c->id) }}" class="btn btn-warning btn-sm" title="Editar consumo">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>
                                            </a>
                                        @endif

                                        <!-- ANULAR (solo Admin/Supervisor/Propietario) -->
                                        @if(
                                            auth()->user()?->hasAnyRole(['admin', 'supervisor', 'propietario'])
                                            && in_array($c->estado_normalizado, ['PENDIENTE', 'FINALIZADO'], true)
                                        )
                                            <button class="btn btn-danger btn-sm btnAnularConsumo" data-id="{{ $c->id }}" title="Anular consumo del cultivo">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 agro-mobile-pagination" id="consumoPaginacionWrap">
                    <small class="text-muted" id="consumoPaginacionInfo"></small>
                    <nav aria-label="Paginacion de consumos">
                        <ul class="pagination pagination-sm mb-0" id="consumoPaginacion"></ul>
                    </nav>
                </div>

                @if($consumos->isEmpty())
                    <div class="text-center mt-3">No hay consumos registrados.</div>
                @endif

            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="modalImportarConsumo" tabindex="-1" aria-labelledby="modalImportarConsumoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="modalImportarConsumoLabel">
                    <i class="fa-solid fa-file-import me-2"></i> Carga masiva de consumos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formImportarConsumo" action="{{ route('consumo.importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <p class="fw-semibold mb-0">Plantilla Excel para consumos historicos o consumos reales desde bodega:</p>
                        <a href="{{ route('consumo.importar.template') }}" class="btn btn-outline-success btn-sm">
                            <i class="fa-solid fa-file-arrow-down me-1"></i> Descargar plantilla
                        </a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold mb-1">Archivo Excel o CSV</label>
                            <input type="file" class="form-control" name="archivo_excel" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>

                    <div class="import-preview-shell mb-3">
                        <div class="import-preview-toolbar">
                            <small class="fw-semibold mb-0">Vista previa del formato esperado. Desliza la barra inferior para revisar todas las columnas.</small>
                            <small class="text-muted mb-0">Columnas: 15</small>
                        </div>
                        <div class="import-preview-scroll">
                        <table class="table table-sm table-bordered align-middle import-preview-table">
                            <thead class="table-light">
                                <tr>
                                    <th>consumo_referencia</th>
                                    <th>cultivo_id</th>
                                    <th>cultivo_codigo</th>
                                    <th>cultivo_nombre</th>
                                    <th>fecha_consumo</th>
                                    <th>aplicar_consumo_real_bodega</th>
                                    <th>insumo_codigo</th>
                                    <th>descripcion_consumo</th>
                                    <th>cantidad</th>
                                    <th>unidad_medida</th>
                                    <th>precio_unitario</th>
                                    <th>subtotal</th>
                                    <th>bodega_id</th>
                                    <th>bodega_nombre</th>
                                    <th>lote_consumo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CONS-HIST-001</td>
                                    <td>3</td>
                                    <td></td>
                                    <td></td>
                                    <td>2025-02-10</td>
                                    <td>NO</td>
                                    <td>INS-001</td>
                                    <td>Urea aplicada antes del sistema</td>
                                    <td>4.250</td>
                                    <td>KG</td>
                                    <td>590.125</td>
                                    <td>=I2*K2</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>CONS-HIST-002</td>
                                    <td></td>
                                    <td>CUL-0007</td>
                                    <td></td>
                                    <td>2025-03-15</td>
                                    <td>SI</td>
                                    <td>INS-014</td>
                                    <td>Aplicacion real tomada de bodega</td>
                                    <td>2.750</td>
                                    <td>KG</td>
                                    <td>837.990</td>
                                    <td>=I3*K3</td>
                                    <td>3</td>
                                    <td>Bodega Insumos</td>
                                    <td>LOT-PIT-014</td>
                                </tr>
                                <tr>
                                    <td>CONS-HIST-002</td>
                                    <td></td>
                                    <td>CUL-0007</td>
                                    <td></td>
                                    <td>2025-03-15</td>
                                    <td>SI</td>
                                    <td>INS-021</td>
                                    <td>Segunda linea del mismo consumo</td>
                                    <td>1.500</td>
                                    <td>KG</td>
                                    <td>158.940</td>
                                    <td>=I4*K4</td>
                                    <td>3</td>
                                    <td>Bodega Insumos</td>
                                    <td>LOT-PIT-015</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0 border-0 rounded-3">
                        <small>
                            <b>consumo_referencia</b> agrupa varias filas dentro del mismo consumo.
                            <br>
                            Puedes identificar el cultivo con <b>cultivo_id</b>, <b>cultivo_codigo</b> o <b>cultivo_nombre</b>.
                            <br>
                            <b>insumo_codigo</b> es obligatorio. Desde ese codigo el sistema resuelve automaticamente el <b>insumo</b>, la <b>categoria</b>, la <b>unidad</b> y el <b>costo</b>.
                            <br>
                            La cantidad enviada en el archivo se toma como el <b>consumo final exacto</b> de esa fila. <b>No</b> se multiplica por las hectareas del cultivo.
                            <br>
                            Si envias <b>precio_unitario</b>, el sistema toma exactamente ese valor historico del Excel y valida el precio de consumo notificado en ese momento.
                            <br>
                            Si envias <b>subtotal</b>, debe coincidir exactamente con <b>cantidad x precio_unitario</b>; si no coincide, la fila se rechaza.
                            <br>
                            Si una fila trae un valor <b>negativo</b> en <b>cantidad</b>, <b>precio_unitario</b> o <b>subtotal</b>, el sistema la interpreta como <b>ajuste/resta</b>: conserva el costo en positivo y registra la <b>cantidad en negativo</b> para descontarla.
                            <br>
                            Si envias <b>unidad_medida</b>, debe coincidir con la unidad configurada en el insumo; si no coincide, la fila se rechaza.
                            <br>
                            Si <b>aplicar_consumo_real_bodega = NO</b>, <b>precio_unitario</b> es obligatorio.
                            <br>
                            Si <b>aplicar_consumo_real_bodega = SI</b> y dejas <b>precio_unitario</b> vacio, usa el costo automatico del sistema.
                            <br>
                            Si <b>aplicar_consumo_real_bodega = SI</b>, el sistema valida insumo, bodega y lote, y descuenta inventario real.
                            <br>
                            Si <b>aplicar_consumo_real_bodega = NO</b>, registra el consumo historico sin tocar stock, ideal para consumos anteriores al uso del sistema.
                            <br>
                            Cuando pongas <b>NO</b>, deja <b>bodega_id</b>, <b>bodega_nombre</b> y <b>lote_consumo</b> vacios; el importador no los aplicara.
                            <br>
                            <b>descripcion_consumo</b> es opcional; si la dejas vacia, el sistema usa automaticamente el nombre del insumo.
                            <br>
                            <b>cantidad</b> acepta 3 decimales y representa la <b>cantidad total real consumida</b> en esa fila del archivo, exactamente como viene en el Excel.
                        </small>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitImportarConsumo">
                        <i class="fa-solid fa-upload me-1"></i> Importar consumos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaConsumos");
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");
    const filtroCultivo = document.getElementById("filtroCultivo");
    const btnAnularCultivo = document.getElementById("btnAnularCultivo");
    const paginacionInfo = document.getElementById("consumoPaginacionInfo");
    const paginacion = document.getElementById("consumoPaginacion");
    const paginacionWrap = document.getElementById("consumoPaginacionWrap");
    const formImportarConsumo = document.getElementById("formImportarConsumo");
    const btnSubmitImportarConsumo = document.getElementById("btnSubmitImportarConsumo");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const state = {
        page: 1,
        filteredRows: [...filas],
    };

    function getCultivoObjetivo() {
        const cultivoSeleccionado = filtroCultivo ? filtroCultivo.value : '';

        if (cultivoSeleccionado !== '') {
            return cultivoSeleccionado;
        }

        const cultivosVisibles = Array.from(new Set(
            state.filteredRows
                .map((fila) => fila.dataset.cultivoId || '')
                .filter((value) => value !== '')
        ));

        return cultivosVisibles.length === 1 ? cultivosVisibles[0] : '';
    }

    function syncBotonAnularCultivo() {
        if (!btnAnularCultivo) {
            return;
        }

        const cultivoObjetivo = getCultivoObjetivo();
        btnAnularCultivo.disabled = cultivoObjetivo === '';
        btnAnularCultivo.dataset.cultivoId = cultivoObjetivo;
    }

    function mostrarErrores(error) {
        const mensaje = error?.message || 'No se pudo procesar la solicitud.';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: typeof window.agroScrollableSwalHtml === 'function'
                ? window.agroScrollableSwalHtml(mensaje)
                : mensaje,
            customClass: {
                popup: 'agro-swal-scroll-popup'
            },
            confirmButtonText: 'Aceptar'
        });
    }

    function mostrarFilas(filasVisibles){
        filas.forEach(f => f.style.display = "none");
        filasVisibles.forEach(f => f.style.display = "");
    }

    function crearItemPaginacion(label, page, disabled = false, active = false) {
        const li = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', () => {
            if (disabled || active) {
                return;
            }

            state.page = page;
            renderTabla();
        });

        li.appendChild(button);

        return li;
    }

    function renderPaginacion(totalPages, totalRows) {
        paginacion.innerHTML = '';

        if (totalRows === 0) {
            paginacionInfo.textContent = 'No hay registros para mostrar.';
            paginacionWrap.style.display = '';
            return;
        }

        const perPage = parseInt(perPageSelect.value, 10);
        const start = ((state.page - 1) * perPage) + 1;
        const end = Math.min(totalRows, state.page * perPage);
        paginacionInfo.textContent = `Mostrando ${start}-${end} de ${totalRows} registros | Hoja ${state.page} de ${totalPages}`;

        if (totalPages <= 1) {
            paginacionWrap.style.display = '';
            return;
        }

        function agregarSeparador() {
            const li = document.createElement('li');
            li.className = 'page-item disabled';

            const span = document.createElement('span');
            span.className = 'page-link';
            span.textContent = '...';

            li.appendChild(span);
            paginacion.appendChild(li);
        }

        const windowSize = 2;
        const startPage = Math.max(1, state.page - windowSize);
        const endPage = Math.min(totalPages, state.page + windowSize);

        paginacion.appendChild(crearItemPaginacion('Anterior', Math.max(1, state.page - 1), state.page === 1));

        if (startPage > 1) {
            paginacion.appendChild(crearItemPaginacion('1', 1, false, state.page === 1));
            if (startPage > 2) {
                agregarSeparador();
            }
        }

        for (let page = startPage; page <= endPage; page += 1) {
            paginacion.appendChild(crearItemPaginacion(String(page), page, false, state.page === page));
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                agregarSeparador();
            }
            paginacion.appendChild(crearItemPaginacion(String(totalPages), totalPages, false, state.page === totalPages));
        }

        paginacion.appendChild(crearItemPaginacion('Siguiente', Math.min(totalPages, state.page + 1), state.page === totalPages));
        paginacionWrap.style.display = '';
    }

    function renderTabla() {
        const perPage = parseInt(perPageSelect.value, 10);
        const totalRows = state.filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const start = (state.page - 1) * perPage;
        const visibles = state.filteredRows.slice(start, start + perPage);

        mostrarFilas(visibles);
        renderPaginacion(totalPages, totalRows);
    }

    function filtrarTabla() {
        const texto = inputBusqueda.value.toLowerCase();
        const cultivoSeleccionado = filtroCultivo ? filtroCultivo.value : '';
        state.filteredRows = filas.filter(f => 
            (cultivoSeleccionado === '' || f.dataset.cultivoId === cultivoSeleccionado)
            && Array.from(f.cells).some(c => c.textContent.toLowerCase().includes(texto))
        );
        state.page = 1;
        syncBotonAnularCultivo();
        renderTabla();
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    if (filtroCultivo) {
        filtroCultivo.addEventListener("change", filtrarTabla);
    }
    perPageSelect.addEventListener("change", () => {
        state.page = 1;
        renderTabla();
    });

    if (formImportarConsumo) {
        formImportarConsumo.addEventListener("submit", () => {
            if (btnSubmitImportarConsumo) {
                btnSubmitImportarConsumo.disabled = true;
            }

            Swal.fire({
                icon: 'info',
                title: 'Cargando consumo masivo',
                html: 'El archivo se esta procesando. Espera un momento...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'agro-swal-scroll-popup'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
    }

    renderTabla();

    // ACCIONES
    document.addEventListener("click", (e) => {

        // VER (CORREGIDO → ABRE SHOW.BLADE)
        if(e.target.closest('.btnVerConsumo')){
            const id = e.target.closest('.btnVerConsumo').dataset.id;
            window.location.href = `/consumo/${id}`;
        }

        // ANULAR
        if(e.target.closest('.btnAnularConsumo')){
            const id = e.target.closest('.btnAnularConsumo').dataset.id;

            Swal.fire({
                title: 'Anular consumo',
                text: "Esta acción anulará el consumo de ese cultivo, dejará trazabilidad y revertirá stock si ya fue finalizado.",
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Motivo de anulación',
                inputPlaceholder: 'Escribe el motivo...',
                inputAttributes: {
                    maxlength: 255
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                preConfirm: (value) => {
                    if (!value || !String(value).trim()) {
                        Swal.showValidationMessage('Debes ingresar un motivo de anulación.');
                    }
                    return value;
                }
            }).then(result => {
                if(result.isConfirmed){
                    const motivo = String(result.value || '').trim();

                    fetch(`/consumo/${id}/anular`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ motivo_anulacion: motivo })
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw data;
                        return data;
                    })
                    .then(data => {
                        Swal.fire('Éxito', data.success || 'Consumo anulado correctamente.', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }

        if(e.target.closest('#btnAnularCultivo')){
            const cultivoId = btnAnularCultivo ? (btnAnularCultivo.dataset.cultivoId || '') : '';

            if (!cultivoId) {
                Swal.fire('Atención', 'Primero selecciona o filtra un único cultivo.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Anular consumos del cultivo',
                text: 'Se anularán todos los consumos del cultivo seleccionado. Si existen consumos finalizados, se revertirá el stock correspondiente.',
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Motivo de anulación',
                inputPlaceholder: 'Escribe el motivo...',
                inputAttributes: {
                    maxlength: 255
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular todos',
                cancelButtonText: 'Cancelar',
                preConfirm: (value) => {
                    if (!value || !String(value).trim()) {
                        Swal.showValidationMessage('Debes ingresar un motivo de anulación.');
                    }
                    return value;
                }
            }).then(result => {
                if(result.isConfirmed){
                    const motivo = String(result.value || '').trim();

                    fetch(`/consumo/cultivo/${cultivoId}/anular-todos`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ motivo_anulacion: motivo })
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw data;
                        return data;
                    })
                    .then(data => {
                        Swal.fire('Éxito', data.success || data.info || 'Consumos anulados correctamente.', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }

    });

});
</script>
@endsection