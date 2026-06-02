@extends('layouts.main')

@section('contenido')
@php
    $modoEdicion = $modoEdicion ?? false;
    $consumo = $consumo ?? null;
    $detallesIniciales = $detallesIniciales ?? [];
    $puedeUsarCategoriasRestringidas = $puedeUsarCategoriasRestringidas ?? false;
    $fechaConsumoInicial = old('fecha_consumo');

    if (!$fechaConsumoInicial && $consumo?->fecha_consumo) {
        $fechaConsumoInicial = \Carbon\Carbon::parse($consumo->fecha_consumo)->format('Y-m-d');
    }

    $fechaConsumoInicial = $fechaConsumoInicial ?: date('Y-m-d');
@endphp
<main id="main" class="main">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-boxes-stacked me-2 text-primary"></i>{{ $modoEdicion ? 'Editar Consumo' : 'Formulario de Consumo' }}
            </h5>
            <span class="badge {{ $modoEdicion ? 'bg-warning text-dark' : 'bg-primary' }} px-3 py-2 rounded-pill shadow-sm">{{ $modoEdicion ? 'MODO: EDICION' : 'MODO: FORMULARIO' }}</span>
        </div>

        <div class="card-body">
            @if(!empty($requiresAssignedConsumptionWarehouse ?? false))
                <div class="alert alert-info border-0 shadow-sm">
                    <strong>Bodega asignada:</strong> {{ $assignedConsumptionWarehouse['nombre'] ?? 'Sin asignar' }}.
                    @if(empty($assignedConsumptionWarehouse['id'] ?? null))
                        Tu usuario notificador no tiene una bodega asignada para consumo. Solicita la asignación antes de registrar consumos.
                    @else
                        Solo podrás consumir desde esa bodega.
                    @endif
                </div>
            @endif

            <form action="{{ $modoEdicion ? route('consumo.update', $consumo->id) : route('consumo.store') }}" method="POST" id="formConsumo">
                @csrf
                @if($modoEdicion)
                    @method('PUT')
                @endif
                <!-- Cabecera -->
                <input type="hidden" name="cultivo_id" id="h_cultivo_id">
                <input type="hidden" name="fecha_consumo" id="h_fecha_consumo">
                <input type="hidden" name="cosecha_estimada" id="h_cosecha_estimada">
                <input type="hidden" name="total_consumo" id="h_total_consumo" value="0">

                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                    <div class="col-md-3">
                        <label class="small fw-bold">Cultivo:</label>
                        <select id="cultivo_id_head" class="form-select form-select-sm border-0 shadow-sm" onchange="cargarDatosCultivo()">
                            <option value="">-- Seleccione --</option>
                            @foreach($cultivos as $item)
                                <option value="{{ $item->id }}" 
                                    {{ (string) old('cultivo_id', $consumo?->cultivo_id) === (string) $item->id ? 'selected' : '' }}
                                    data-cosecha="{{ $item->cosecha_estimada }}" 
                                    data-unidad="{{ $item->unidad_medida }}"
                                    data-estado="{{ $item->estado }}">
                                    {{ $item->nombre }} @if($item->estado === 'Cerrado')(CERRADO)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Fecha Consumo:</label>
                        <input type="date" id="fecha_input_ui" class="form-control form-control-sm border-0 shadow-sm" value="{{ $fechaConsumoInicial }}" onchange="sincronizarCabecera()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Cosecha Estimada:</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" id="cosecha_estimada_ui" class="form-control border-0 bg-white" readonly>
                            <span class="input-group-text border-0 bg-white text-muted" id="unid_vis">U.M.</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-primary">Semana Actual:</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <span class="input-group-text bg-primary text-white border-0"><i class="fa-solid fa-calendar-week"></i></span>
                            <input type="text" class="form-control border-0 bg-white fw-bold" value="{{ date('W') }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Historial de consumo eliminado por requerimiento -->

                <!-- Botones de categoría -->
                @php
                    $categoriasInsumos = collect($insumos)
                        ->pluck('categoria')
                        ->filter()
                        ->map(function ($categoria) {
                            $normalizada = strtolower(trim((string) $categoria));

                            return match ($normalizada) {
                                'plantula', 'plántula' => 'Plantula',
                                'otros insumos', 'otros_insumos', 'otros', 'otro insumo', 'otros insumo' => 'Otros Insumos',
                                default => trim((string) $categoria),
                            };
                        })
                        ->reject(fn ($categoria) => in_array($categoria, ['Mano de Obra', 'Indirectos', 'CIF', 'Combustible'], true))
                        ->merge(['Fertilizante', 'Fitosanitario', 'Otros Insumos'])
                        ->when($puedeUsarCategoriasRestringidas, fn ($collection) => $collection->merge(['Maquinaria']))
                        ->unique()
                        ->sort()
                        ->values();

                    $estilosCategorias = [
                        'Mano de Obra' => ['class' => 'primary', 'icon' => 'fa-solid fa-helmet-safety'],
                        'Fertilizante' => ['class' => 'success', 'icon' => 'fa-solid fa-leaf'],
                        'Fitosanitario' => ['class' => 'warning', 'icon' => 'fa-solid fa-spray-can'],
                        'Combustible' => ['class' => 'dark', 'icon' => 'fa-solid fa-gas-pump'],
                        'CIF' => ['class' => 'secondary', 'icon' => 'fa-solid fa-coins'],
                        'Indirectos' => ['class' => 'dark', 'icon' => 'fa-solid fa-gears'],
                        'Plantula' => ['class' => 'info', 'icon' => 'fa-solid fa-seedling'],
                        'Otros Insumos' => ['class' => 'secondary', 'icon' => 'fa-solid fa-box-open'],
                        'Maquinaria' => ['class' => 'dark', 'icon' => 'fa-solid fa-tractor'],
                    ];
                @endphp
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarCategoria('Mano de Obra')">
                        <i class="fa-solid fa-helmet-safety"></i> Mano de Obra
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="cargarCategoria('Indirectos')">
                        <i class="fa-solid fa-gears"></i> Indirectos / CIF / Combustible
                    </button>
                    @foreach($categoriasInsumos as $categoriaInsumo)
                        @php
                            $configCategoria = $estilosCategorias[$categoriaInsumo] ?? ['class' => 'secondary', 'icon' => 'fa-solid fa-tags'];
                        @endphp
                        <button type="button" class="btn btn-outline-{{ $configCategoria['class'] }} btn-sm" onclick="cargarCategoria(@js($categoriaInsumo))">
                            <i class="{{ $configCategoria['icon'] }}"></i> {{ $categoriaInsumo }}
                        </button>
                    @endforeach
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltroOpciones()">
                        <i class="fa-solid fa-eraser"></i> Limpiar
                    </button>
                </div>


                <!-- Formulario de insumo / mano de obra -->
                <div class="card border-primary mb-3 shadow-sm">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="small fw-bold">Categoría</label>
                                <input type="text" id="in_cat" class="form-control form-control-sm border-primary" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Actividad / Producto</label>
                                <select id="in_desc" class="form-select form-select-sm border-primary">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-md-2" id="col_secundaria">
                                <label class="small fw-bold" id="label_secundaria">Actividad Secundaria</label>
                                <select id="in_secundaria" class="form-select form-select-sm border-primary">
                                    <option value="">Seleccione...</option>
                                </select>
                                <input type="text" id="in_secundaria_manual" class="form-control form-control-sm border-primary d-none" placeholder="Escriba la descripción">
                            </div>
                            <div class="col-md-1">
                                <label class="small fw-bold">Cant.</label>
                                <input type="number" id="in_cant" class="form-control form-control-sm border-primary" value="1" step="0.001">
                            </div>
                            <div class="col-md-1">
                                <label class="small fw-bold">U.M.</label>
                                <input type="text" id="in_unidad" class="form-control form-control-sm border-primary bg-light" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Precio (L)</label>
                                <input type="number" id="in_precio" class="form-control form-control-sm border-primary bg-light" readonly>
                            </div>
                            <div class="col-md-2" id="col_bodega">
                                <label class="small fw-bold">Bodega</label>
                                <select id="in_bodega" class="form-select form-select-sm border-primary"></select>
                            </div>
                            <div class="col-md-3" id="col_lote">
                                <label class="small fw-bold">Lote</label>
                                <select id="in_lote" class="form-select form-select-sm border-primary"></select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold" onclick="agregarATabla()">
                                    <i class="fa-solid fa-plus-circle me-1"></i> AGREGAR FILA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de consumos -->
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-hover border">
                        <thead class="table-dark">
                            <tr>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>U.M.</th>
                                <th class="text-end">Costo Unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla"></tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 border-top pt-3 gap-2 gap-md-0">
                    <a href="{{ route('consumo.index') }}" class="btn btn-secondary shadow-sm btn-sm px-3 py-2">
                        <i class="fa-solid fa-arrow-left me-2"></i> Volver
                    </a>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="fs-6 fw-bold text-dark">Total: L <span id="totalGral">0.000</span></div>
                        <button type="submit" class="btn btn-success px-3 py-2 fw-bold shadow btn-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i> {{ $modoEdicion ? 'ACTUALIZAR CONSUMO' : 'GUARDAR CONSUMOS' }}
                        </button>
                    </div>
                </div>
                <style>
@media (max-width: 600px) {
    .d-flex.align-items-center.gap-3.flex-wrap > div,
    .d-flex.align-items-center.gap-3.flex-wrap > button {
        font-size: 0.95rem !important;
        padding: 0.4rem 0.7rem !important;
    }
    .fs-6 {
        font-size: 1rem !important;
    }
    .btn-sm {
        font-size: 0.95rem !important;
        padding: 0.4rem 0.7rem !important;
    }
}
</style>
            </form>
        </div>
    </div>
</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const insumos = @json($insumos);
const labores = @json($labores);
const detallesIniciales = @json($detallesIniciales);
const assignedConsumptionWarehouse = @json($assignedConsumptionWarehouse ?? null);
const requiresAssignedConsumptionWarehouse = @json($requiresAssignedConsumptionWarehouse ?? false);
    const formularioConsumo = document.getElementById('formConsumo');
    const cultivoHead = document.getElementById('cultivo_id_head');

    if (formularioConsumo) {
        formularioConsumo.scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    if (cultivoHead) {
        window.setTimeout(function () {
            cultivoHead.focus();
        }, 50);
    }

const puedeUsarCategoriasRestringidas = @json($puedeUsarCategoriasRestringidas);
let detalles = [];
let cultivoCerrado = false;
let inventarioPorInsumo = [];
let modoCostoManual = false;

function esCategoriaManual(cat) {
    return ['CIF'].includes(cat);
}

function esOpcionManualCategoria(opt) {
    return opt?.dataset?.manualCategoria === '1';
}

function configurarCampoSecundario({
    manual = false,
    label = 'Actividad Secundaria',
    placeholder = 'Escriba la descripción',
    opcionPorDefecto = 'No aplica',
    selectPlaceholder = 'No aplica'
} = {}) {
    const labelSecundaria = document.getElementById('label_secundaria');
    const selectSecundaria = document.getElementById('in_secundaria');
    const inputSecundaria = document.getElementById('in_secundaria_manual');
    const select2Container = selectSecundaria?.nextElementSibling;

    if (labelSecundaria) {
        labelSecundaria.textContent = label;
    }

    if (!selectSecundaria || !inputSecundaria) return;

    if (manual && select2Disponible()) {
        const $select = jQuery(selectSecundaria);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
    }

    selectSecundaria.classList.toggle('d-none', manual);
    inputSecundaria.classList.toggle('d-none', !manual);

    if (select2Container && select2Container.classList.contains('select2')) {
        select2Container.classList.toggle('d-none', manual);
    }

    if (manual) {
        selectSecundaria.value = '';
        inputSecundaria.placeholder = placeholder;
    } else {
        inputSecundaria.value = '';
        selectSecundaria.innerHTML = `<option value="">${opcionPorDefecto}</option>`;
        refrescarSelectBusqueda('#in_secundaria', selectPlaceholder);
    }
}

function configurarModoManualBasico({ unidadPorDefecto = 'Unidad', precioPorDefecto = '0' } = {}) {
    const selBodega = document.getElementById('in_bodega');
    const selLote = document.getElementById('in_lote');
    const unidad = document.getElementById('in_unidad');
    const precio = document.getElementById('in_precio');

    configurarCampoSecundario({
        manual: false,
        label: 'Actividad Secundaria',
        opcionPorDefecto: 'No aplica',
        selectPlaceholder: 'No aplica'
    });
    selBodega.innerHTML = '<option value="NA">No aplica</option>';
    selLote.innerHTML = '<option value="NA">No aplica</option>';

    unidad.readOnly = false;
    precio.readOnly = false;
    unidad.classList.remove('bg-light');
    precio.classList.remove('bg-light');
    unidad.value = unidad.value || unidadPorDefecto;
    precio.value = precio.value || precioPorDefecto;

    refrescarSelectBusqueda('#in_bodega', 'No aplica');
    refrescarSelectBusqueda('#in_lote', 'No aplica');
}

function agregarOpcionManualCategoria(selectDesc, categoria, etiqueta = '') {
    const opt = document.createElement('option');
    opt.value = `manual:${categoria}`;
    opt.textContent = etiqueta || `Otro de ${categoria}`;
    opt.dataset.manualCategoria = '1';
    opt.dataset.nombreBase = etiqueta || `Otro de ${categoria}`;
    opt.dataset.unidad = 'Unidad';
    opt.dataset.precio = 0;
    opt.dataset.ids = '[]';
    selectDesc.appendChild(opt);
}

function aplicarModoCategoria(cat) {
    modoCostoManual = esCategoriaManual(cat);

    const selDesc = document.getElementById('in_desc');
    const colBodega = document.getElementById('col_bodega');
    const colLote = document.getElementById('col_lote');
    const unidad = document.getElementById('in_unidad');
    const precio = document.getElementById('in_precio');

    if (modoCostoManual) {
        selDesc.innerHTML = '<option value="">Seleccione...</option>';
        ['Combustible tractor', 'Combustible bomba de riego', 'Mantenimiento equipo', 'Flete', 'Otro costo indirecto'].forEach(item => {
            const opt = new Option(item, item);
            opt.dataset.nombreBase = item;
            opt.dataset.unidad = 'Unidad';
            opt.dataset.precio = 0;
            opt.dataset.ids = '[]';
            selDesc.appendChild(opt);
        });

        configurarModoManualBasico({
            unidadPorDefecto: 'Unidad',
            precioPorDefecto: '0'
        });

        refrescarSelectBusqueda('#in_desc', 'Buscar concepto...');
    } else {
        colBodega.classList.remove('d-none');
        colLote.classList.remove('d-none');

        unidad.readOnly = true;
        precio.readOnly = true;
        unidad.classList.add('bg-light');
        precio.classList.add('bg-light');
        configurarCampoSecundario();
    }
}

function select2Disponible() {
    return window.jQuery && jQuery.fn && jQuery.fn.select2;
}

function refrescarSelectBusqueda(selector, placeholder = 'Seleccione...') {
    if (!select2Disponible()) return;

    const $el = jQuery(selector);
    if (!$el.length) return;

    if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
    }

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: false,
        minimumResultsForSearch: 0,
        dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : jQuery(document.body)
    });
}

function inicializarSelectsBuscables() {
    refrescarSelectBusqueda('#cultivo_id_head', 'Buscar cultivo...');
    refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
    refrescarSelectBusqueda('#in_secundaria', 'Buscar actividad...');
    refrescarSelectBusqueda('#in_bodega', 'Buscar bodega...');
    refrescarSelectBusqueda('#in_lote', 'Buscar lote...');
}

function assignedWarehouseKey() {
    return String(assignedConsumptionWarehouse?.id || '').trim();
}

function obtenerOpcionSeleccionada(selectEl) {
    if (!selectEl) return null;

    if (selectEl.selectedIndex >= 0) {
        return selectEl.options[selectEl.selectedIndex] || null;
    }

    return Array.from(selectEl.options).find(o => o.value === selectEl.value) || null;
}

function formatearExistencia(valor) {
    return Number(valor || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function actualizarInfoLote(texto = '') {
    const info = document.getElementById('lote_stock_info');
    if (info) {
        info.innerText = texto;
    }
}

function normalizarTextoCombustible(valor) {
    return String(valor || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function normalizarCategoria(valor) {
    return String(valor || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function categoriaCoincide(categoriaInsumo, categoriaFiltro) {
    const categoriaNormalizada = normalizarCategoria(categoriaInsumo);
    const filtroNormalizado = normalizarCategoria(categoriaFiltro);

    if (categoriaNormalizada === filtroNormalizado) {
        return true;
    }

    const aliasCategorias = {
        'otros insumos': ['otros', 'otro insumo', 'otros insumo'],
        'plantula': ['plántula', 'plantulas', 'plántulas'],
    };

    const alias = aliasCategorias[filtroNormalizado] || [];
    return alias.includes(categoriaNormalizada);
}

function esInsumoCombustible(item) {
    const categoria = normalizarTextoCombustible(item?.categoria);
    const nombre = normalizarTextoCombustible(item?.nombre);

    const porCategoria =
        categoria.includes('combustible') ||
        categoria.includes('combutible') ||
        categoria.includes('gasolina') ||
        categoria.includes('diesel');

    const porNombre =
        nombre.includes('combustible') ||
        nombre.includes('combutible') ||
        nombre.includes('gasolina') ||
        nombre.includes('diesel') ||
        nombre.includes('super') ||
        nombre.includes('regular');

    return porCategoria || porNombre;
}

function renderizarLotesPorBodega(bodegaId) {
    const loteSelect = document.getElementById('in_lote');
    loteSelect.innerHTML = '<option value="">Seleccione...</option>';
    actualizarInfoLote();

    if (String(bodegaId) === 'GENERAL') {
        loteSelect.innerHTML = '<option value="GENERAL">General (sin lote)</option>';
        actualizarInfoLote();
        refrescarSelectBusqueda('#in_lote', 'General');
        return;
    }

    if (!bodegaId) {
        refrescarSelectBusqueda('#in_lote', 'Buscar lote...');
        return;
    }

    const lotesDisponibles = inventarioPorInsumo
        .filter(item => {
            const bodegaKey = String(item.bodega_id ?? item.bodega_nombre ?? '').trim();
            return bodegaKey === String(bodegaId).trim();
        })
;

    lotesDisponibles.forEach(item => {
            const stockLote = parseFloat(item.stock_actual || 0);
            const numeroLote = String(item.numero_lote || '').trim();
            const valorLote = numeroLote || 'GENERAL';
            const textoLote = numeroLote || 'General (sin lote)';
            const labelLote = `${textoLote} (Disp: ${formatearExistencia(stockLote)})`;
            const optL = new Option(labelLote, valorLote);
            optL.dataset.stock = parseFloat(item.stock_actual || 0);
            optL.dataset.bodegaRel = item.bodega_id ?? item.bodega_nombre ?? '';
            optL.dataset.insumoId = item.insumo_id || '';
            loteSelect.appendChild(optL);
        });

    if (lotesDisponibles.length === 0) {
        loteSelect.innerHTML = '<option value="">Sin lotes disponibles</option>';
        refrescarSelectBusqueda('#in_lote', 'Sin lotes disponibles');
        return;
    }

    refrescarSelectBusqueda('#in_lote', 'Buscar lote...');

    const primerLoteDisponible = loteSelect.options[1]?.value || '';
    if (!primerLoteDisponible) {
        return;
    }

    loteSelect.value = primerLoteDisponible;

    if (select2Disponible()) {
        jQuery(loteSelect).val(primerLoteDisponible).trigger('change');
        return;
    }

    loteSelect.dispatchEvent(new Event('change'));
}

function manejarCambioBodega() {
    const bodegaId = document.getElementById('in_bodega').value;
    const cantInput = document.getElementById('in_cant');

    cantInput.max = '';
    cantInput.dataset.stockMax = '';
    actualizarInfoLote();
    renderizarLotesPorBodega(bodegaId);
}

function manejarCambioLote() {
    const loteSelect = document.getElementById('in_lote');
    const opt = loteSelect.options[loteSelect.selectedIndex];
    const cantInput = document.getElementById('in_cant');

    if(opt?.value){
        document.getElementById('in_bodega').value = opt.dataset.bodegaRel;
        const stock = parseFloat(opt.dataset.stock || 0);
        cantInput.max = stock;
        cantInput.dataset.stockMax = stock;
        actualizarInfoLote();
        return;
    }

    cantInput.max = '';
    cantInput.dataset.stockMax = '';
    actualizarInfoLote();
}

// ------------------- CABECERA -------------------
function cargarDatosCultivo(){
    const sel = document.getElementById('cultivo_id_head');
    const opt = sel.options[sel.selectedIndex];
    const estado = opt?.dataset?.estado || '';

    cultivoCerrado = String(estado).toLowerCase() === 'cerrado';

    if(sel.value){
        document.getElementById('cosecha_estimada_ui').value = opt.dataset.cosecha || '';
        document.getElementById('unid_vis').innerText = opt.dataset.unidad || 'U.M.';
        document.getElementById('h_cultivo_id').value = sel.value;
        document.getElementById('h_cosecha_estimada').value = opt.dataset.cosecha || 0;
        sincronizarCabecera();
    } else {
        document.getElementById('h_cultivo_id').value = '';
    }
}

function cargarHistorialCultivo(cultivoId, estado){
    cultivoCerrado = estado.toLowerCase() === 'cerrado';
    const alertBox = document.getElementById('cultivoEstadoAlert');
    const historial = document.getElementById('historialCultivo');
    const cuerpo = document.getElementById('historialBody');

    if (!alertBox || !historial || !cuerpo) {
        return;
    }

    if(cultivoId){
        fetch(`/api/cultivo/${cultivoId}/consumos`)
            .then(res => res.json())
            .then(data => {
                historial.style.display = '';
                cuerpo.innerHTML = '';

                if(data.consumos.length === 0){
                    cuerpo.innerHTML = '<tr><td colspan="4" class="text-center">No hay consumos registrados para este cultivo.</td></tr>';
                } else {
                    data.consumos.forEach(item => {
                        cuerpo.innerHTML += `
                            <tr>
                                <td>${item.semana}</td>
                                <td>${item.fecha_consumo}</td>
                                <td>L ${Number(item.total).toLocaleString('es-ES', {minimumFractionDigits:2})}</td>
                                <td>${item.detalles.map(d => `${d.categoria}: ${d.descripcion} (${d.cantidad} ${d.unidad_medida})`).join('<br>')}</td>
                            </tr>`;
                    });
                }

                if (cultivoCerrado) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.textContent = 'Este cultivo está cerrado. No se pueden registrar más consumos ni cosechas.';
                    alertBox.classList.remove('d-none');
                } else {
                    alertBox.className = 'd-none';
                    alertBox.textContent = '';
                }

            })
            .catch(() => {
                historial.style.display = '';
                cuerpo.innerHTML = '<tr><td colspan="4" class="text-center">No se pudo cargar el historial de consumo.</td></tr>';
                alertBox.className = 'alert alert-warning';
                alertBox.textContent = 'No se pudo cargar el historial de consumo.';
                alertBox.classList.remove('d-none');
            });
    }
}

function sincronizarCabecera(){
    document.getElementById('h_fecha_consumo').value = document.getElementById('fecha_input_ui').value;
}

// ------------------- CARGA DE CATEGORÍA -------------------
function cargarCategoria(cat) {
    if (cat === 'Maquinaria' && !puedeUsarCategoriasRestringidas) {
        alert('Solo propietario, admin o programador pueden usar la categoría Maquinaria.');
        return;
    }

    document.getElementById('in_cat').value = cat;
    aplicarModoCategoria(cat);
    limpiarCamposCategoriaActual();

    if (modoCostoManual) {
        return;
    }

    let items;
    if (cat === 'Mano de Obra') {
        items = labores;
    } else if (cat === 'Indirectos') {
        const selectDesc = document.getElementById('in_desc');
        selectDesc.innerHTML = '<option value="">Seleccione...</option>';

        const ejemplos = [
            { nombre: 'Agua potable', tipo: 'manual', unidad: 'Servicio', precio: 0 },
            { nombre: 'Energia electrica', tipo: 'manual', unidad: 'kWh', precio: 0 },
            { nombre: 'Nomina confidencial', tipo: 'manual', unidad: 'Jornal', precio: 0 },
            { nombre: 'Combustible', tipo: 'combustible', unidad: 'Litro', precio: 0 }
        ];

        if (puedeUsarCategoriasRestringidas) {
            ejemplos.splice(2, 0, { nombre: 'Mantenimiento de maquinaria', tipo: 'manual', unidad: 'Servicio', precio: 0 });
        }

        ejemplos.forEach(item => {
            const opt = new Option(item.nombre, item.nombre);
            opt.dataset.nombreBase = item.nombre;
            opt.dataset.indirectoTipo = item.tipo;
            opt.dataset.unidad = item.unidad;
            opt.dataset.precio = item.precio;
            selectDesc.appendChild(opt);
        });

        agregarOpcionManualCategoria(selectDesc, cat, 'Otro indirecto / servicio');

        refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
        return;
    } else {
        items = insumos.filter(i => categoriaCoincide(i.categoria, cat));
    }
    const selectDesc = document.getElementById('in_desc');
    selectDesc.innerHTML = '<option value="">Seleccione...</option>';

    const itemsPorNombre = new Map();
    items.forEach(item => {
        const nombre = (item.nombre || '').trim();
        if (!itemsPorNombre.has(nombre)) {
            itemsPorNombre.set(nombre, []);
        }
        itemsPorNombre.get(nombre).push(item);
    });

    if (cat === 'Mano de Obra') {
        itemsPorNombre.forEach((grupo, nombre) => {
            const base = grupo[0] || {};
            const opt = document.createElement('option');
            opt.value = base.id || '';
            opt.textContent = nombre;
            opt.dataset.nombreBase = nombre;
            opt.dataset.unidad = base.unidad_medida || 'U.M.';
            opt.dataset.precio = base.precio || 0;
            opt.dataset.ids = JSON.stringify(grupo.map(g => g.id).filter(Boolean));

            const actividades = grupo.flatMap(g => Array.isArray(g.actividades_secundarias) ? g.actividades_secundarias : []);
            opt.dataset.actividades = JSON.stringify(actividades);

            selectDesc.appendChild(opt);
        });
    } else {
        selectDesc.innerHTML = '<option value="">Seleccione...</option>';

        Array.from(itemsPorNombre.entries())
            .map(([nombre, grupo]) => {
                const base = grupo[0] || {};
                const ids = grupo.map(g => g.id).filter(Boolean);
                const existenciaTotal = grupo.reduce((sum, g) => sum + parseFloat(g.existencia_total || 0), 0);

                return {
                    nombre,
                    base,
                    ids,
                    existenciaTotal
                };
            })
            .filter(item => item.existenciaTotal > 0)
            .sort((a, b) => a.nombre.localeCompare(b.nombre))
            .forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.base.id || '';
                const codigo = (item.base.codigo || '').trim();
                opt.textContent = codigo ? `${codigo} - ${item.nombre}` : item.nombre;
                opt.dataset.nombreBase = item.nombre;
                opt.dataset.existencia = item.existenciaTotal;
                opt.dataset.unidad = item.base.unidad_medida || 'U.M.';
                opt.dataset.precio = item.base.precio || 0;
                opt.dataset.ids = JSON.stringify(item.ids);
                selectDesc.appendChild(opt);
            });

        agregarOpcionManualCategoria(selectDesc, cat, `Otro insumo de ${cat}`);

        if (selectDesc.options.length === 2) {
            selectDesc.options[0].textContent = 'No hay insumos con existencia';
        }
    }

    refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
}

function cargarInventarioPorIds(idsInsumo, selBodega, selLote) {
    Promise.all(
        idsInsumo.map(insumoId =>
            fetch(`/api/inventario_bodega/${insumoId}`)
                .then(res => res.ok ? res.json() : [])
                .then(rows => (Array.isArray(rows) ? rows : []).map(r => ({ ...r, insumo_id: insumoId })))
                .catch(() => [])
        )
    ).then(listas => {
        const combinado = listas.flat();
        const unicos = new Map();

        combinado.forEach(item => {
            const bodegaKey = String(item.bodega_id ?? item.bodega_nombre ?? '').trim();
            const numeroLote = String(item.numero_lote || '').trim() || 'GENERAL';
            const key = `${bodegaKey}|${numeroLote}`;
            if (!unicos.has(key)) {
                unicos.set(key, { ...item, numero_lote: numeroLote });
            }
        });

        inventarioPorInsumo = Array.from(unicos.values());

        const bodegasAgregadas = new Set();
        selBodega.innerHTML = '<option value="">Seleccione...</option>';
        selLote.innerHTML = '<option value="">Seleccione...</option>';

        inventarioPorInsumo.forEach(item => {
            const bodegaKey = String(item.bodega_id ?? item.bodega_nombre ?? '').trim();
            if(!bodegaKey || bodegasAgregadas.has(bodegaKey)){
                return;
            }

            selBodega.appendChild(new Option(item.bodega_nombre || bodegaKey, bodegaKey));
            bodegasAgregadas.add(bodegaKey);
        });

        if (inventarioPorInsumo.length === 0) {
            selBodega.innerHTML = '<option value="GENERAL">General (sin bodega)</option>';
            selLote.innerHTML = '<option value="GENERAL">General (sin lote)</option>';
            selBodega.disabled = false;
            actualizarInfoLote();
            refrescarSelectBusqueda('#in_bodega', 'General');
            refrescarSelectBusqueda('#in_lote', 'General');
            return;
        }

        if (requiresAssignedConsumptionWarehouse) {
            const assignedKey = assignedWarehouseKey();
            selBodega.disabled = true;

            if (!assignedKey) {
                selBodega.innerHTML = '<option value="">Sin bodega asignada</option>';
                selLote.innerHTML = '<option value="">Sin lotes disponibles</option>';
                refrescarSelectBusqueda('#in_bodega', 'Sin bodega asignada');
                refrescarSelectBusqueda('#in_lote', 'Sin lotes disponibles');
                return;
            }

            const assignedOptionExists = Array.from(selBodega.options).some(option => option.value === assignedKey);
            if (!assignedOptionExists) {
                selBodega.innerHTML = '<option value="">Sin inventario en bodega asignada</option>';
                selLote.innerHTML = '<option value="">Sin lotes disponibles</option>';
                refrescarSelectBusqueda('#in_bodega', 'Sin inventario en bodega asignada');
                refrescarSelectBusqueda('#in_lote', 'Sin lotes disponibles');
                return;
            }

            selBodega.value = assignedKey;
            refrescarSelectBusqueda('#in_bodega', assignedConsumptionWarehouse?.nombre || 'Bodega asignada');
            renderizarLotesPorBodega(assignedKey);
            return;
        }

        selBodega.disabled = false;

        refrescarSelectBusqueda('#in_bodega', 'Buscar bodega...');
        refrescarSelectBusqueda('#in_lote', 'Buscar lote...');

        const totalBodegasDisponibles = Math.max(selBodega.options.length - 1, 0);
        if (totalBodegasDisponibles === 1) {
            const unicaBodegaDisponible = selBodega.options[1].value;
            selBodega.value = unicaBodegaDisponible;

            if (select2Disponible()) {
                jQuery(selBodega).trigger('change.select2');
            }

            renderizarLotesPorBodega(unicaBodegaDisponible);
        } else {
            selBodega.value = '';
            selLote.innerHTML = '<option value="">Seleccione una bodega</option>';
            refrescarSelectBusqueda('#in_lote', 'Seleccione una bodega');
        }
    });
}

// ------------------- CAMBIO DE INSUMO -------------------
function manejarCambioInsumo(event) {
    const selectInsumo = document.getElementById('in_desc');
    const opt = obtenerOpcionSeleccionada(selectInsumo);
    const catActual = document.getElementById('in_cat').value;
    const selSecundaria = document.getElementById('in_secundaria');
    const selBodega = document.getElementById('in_bodega');
    const selLote = document.getElementById('in_lote');
    const opcionManualCategoria = esOpcionManualCategoria(opt);

    if (modoCostoManual && !opcionManualCategoria) {
        return;
    }

    limpiarDependenciasInsumo();
    if(!selectInsumo.value || !opt) return;

    const unidadInput = document.getElementById('in_unidad');
    const precioInput = document.getElementById('in_precio');
    unidadInput.value = opt.dataset.unidad || '';
    precioInput.value = opt.dataset.precio || 0;

    if (opcionManualCategoria) {
        configurarModoManualBasico({
            unidadPorDefecto: opt.dataset.unidad || 'Unidad',
            precioPorDefecto: opt.dataset.precio || '0'
        });
        configurarCampoSecundario({
            manual: true,
            label: 'Descripción',
            placeholder: `Escriba el detalle para ${catActual}`
        });
        return;
    }

    if (catActual === 'Indirectos') {
        const tipo = opt.dataset.indirectoTipo || 'manual';

        if (tipo === 'manual') {
            configurarModoManualBasico({
                unidadPorDefecto: opt.dataset.unidad || 'Servicio',
                precioPorDefecto: opt.dataset.precio || '0'
            });
            return;
        }

        if (tipo === 'manoobra') {
            unidadInput.readOnly = false;
            precioInput.readOnly = false;
            unidadInput.classList.remove('bg-light');
            precioInput.classList.remove('bg-light');
            configurarCampoSecundario({
                manual: false,
                label: 'Actividad Secundaria',
                opcionPorDefecto: 'Seleccione...',
                selectPlaceholder: 'Buscar actividad...'
            });

            ['Nomina confidencial'].forEach(act => selSecundaria.appendChild(new Option(act, act)));
            selBodega.innerHTML = '<option value="">No aplica</option>';
            selLote.innerHTML = '<option value="">No aplica</option>';
            refrescarSelectBusqueda('#in_secundaria', 'Buscar actividad...');
            refrescarSelectBusqueda('#in_bodega', 'No aplica');
            refrescarSelectBusqueda('#in_lote', 'No aplica');
            return;
        }

        if (tipo === 'combustible') {
            unidadInput.readOnly = true;
            precioInput.readOnly = false;
            unidadInput.classList.add('bg-light');
            precioInput.classList.remove('bg-light');
            configurarCampoSecundario({
                manual: false,
                label: 'Actividad Secundaria',
                opcionPorDefecto: 'Seleccione...',
                selectPlaceholder: 'Buscar combustible...'
            });

            const combustibles = insumos
                .filter(esInsumoCombustible)
                .filter(i => {
                    const existencia = Number(i?.existencia_total);
                    return Number.isFinite(existencia) ? existencia > 0 : true;
                });

            const nombresUnicos = [...new Set(combustibles.map(i => (i.nombre || '').trim()).filter(Boolean))];
            selSecundaria.innerHTML = '<option value="">Seleccione...</option>';
            nombresUnicos.forEach(nombre => selSecundaria.appendChild(new Option(nombre, nombre)));
            refrescarSelectBusqueda('#in_secundaria', 'Buscar combustible...');

            // Si solo existe un combustible, autoseleccionarlo para cargar bodega/lote de inmediato.
            if (nombresUnicos.length === 1) {
                selSecundaria.value = nombresUnicos[0];
                manejarCambioSecundariaCombustible();
            }
            return;
        }
    }

    if(catActual === 'Mano de Obra'){
        configurarCampoSecundario({
            manual: false,
            label: 'Actividad Secundaria',
            opcionPorDefecto: 'Seleccione...',
            selectPlaceholder: 'Buscar actividad...'
        });
        // Buscar todas las actividades secundarias de todos los items con ese nombre
        let actividades = [];
        labores.forEach(item => {
            if(item.nombre === opt.textContent && Array.isArray(item.actividades_secundarias)){
                actividades = actividades.concat(item.actividades_secundarias);
            }
        });
        // Eliminar duplicados y vacíos
        const actividadesUnicas = [...new Set(actividades.filter(a => a && a.trim() !== ''))];
        actividadesUnicas.forEach(act => {
            selSecundaria.appendChild(new Option(act, act));
        });
        refrescarSelectBusqueda('#in_secundaria', 'Buscar actividad...');
    } else {
        let idsInsumo = [];
        try {
            idsInsumo = JSON.parse(opt.dataset.ids || '[]');
        } catch (e) {
            idsInsumo = [];
        }

        if (!Array.isArray(idsInsumo) || idsInsumo.length === 0) {
            idsInsumo = [selectInsumo.value];
        }

        cargarInventarioPorIds(idsInsumo, selBodega, selLote);
    }
}

function manejarCambioSecundariaCombustible() {
    const catActual = document.getElementById('in_cat').value;
    const opt = obtenerOpcionSeleccionada(document.getElementById('in_desc'));
    if (catActual !== 'Indirectos' || !opt || (opt.dataset.indirectoTipo || '') !== 'combustible') return;

    const nombreCombustible = document.getElementById('in_secundaria').value;
    if (!nombreCombustible) return;

    const combustibleNormalizado = normalizarTextoCombustible(nombreCombustible);
    const coincidentes = insumos
        .filter(esInsumoCombustible)
        .filter(i => normalizarTextoCombustible(i?.nombre) === combustibleNormalizado);

    if (!coincidentes.length) return;

    const idsInsumo = coincidentes.map(i => i.id).filter(Boolean);
    const base = coincidentes[0];
    document.getElementById('in_unidad').value = base.unidad_medida || 'Litro';
    document.getElementById('in_precio').value = base.precio || 0;

    cargarInventarioPorIds(idsInsumo, document.getElementById('in_bodega'), document.getElementById('in_lote'));
}

document.getElementById('in_secundaria').addEventListener('change', manejarCambioSecundariaCombustible);
if (window.jQuery) {
    jQuery(document).on('select2:select select2:clear', '#in_secundaria', manejarCambioSecundariaCombustible);
}

function limpiarDependenciasInsumo(){
    inventarioPorInsumo = [];
    configurarCampoSecundario({
        manual: false,
        label: 'Actividad Secundaria',
        opcionPorDefecto: 'Seleccione...',
        selectPlaceholder: 'Buscar actividad...'
    });
    document.getElementById('in_bodega').innerHTML = '<option value="">Seleccione...</option>';
    document.getElementById('in_lote').innerHTML = '<option value="">Seleccione...</option>';
    document.getElementById('in_unidad').value = '';
    document.getElementById('in_precio').value = '';
    actualizarInfoLote();

    refrescarSelectBusqueda('#in_bodega', 'Buscar bodega...');
    refrescarSelectBusqueda('#in_lote', 'Buscar lote...');
}

function limpiarCamposCategoriaActual() {
    document.getElementById('in_desc').innerHTML = '<option value="">Seleccione...</option>';
    document.getElementById('in_cant').value = 1;
    document.getElementById('in_cant').max = '';
    document.getElementById('in_cant').dataset.stockMax = '';
    document.getElementById('in_unidad').value = '';
    document.getElementById('in_precio').value = '';
    document.getElementById('in_secundaria_manual').value = '';
    limpiarDependenciasInsumo();
}

function limpiarDespuesDeAgregar(cat) {
    if (!cat) {
        limpiarFiltroOpciones();
        return;
    }

    cargarCategoria(cat);

    if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
        jQuery('#in_desc').val('').trigger('change');
        jQuery('#in_secundaria').val('').trigger('change');
        jQuery('#in_bodega').val('').trigger('change');
        jQuery('#in_lote').val('').trigger('change');
    }

    document.getElementById('in_cant').focus();
}

document.getElementById('in_desc').addEventListener('change', manejarCambioInsumo);
if (window.jQuery) {
    jQuery(document).on('select2:select select2:clear', '#cultivo_id_head', cargarDatosCultivo);
    jQuery(document).on('select2:select select2:clear', '#in_desc', manejarCambioInsumo);
}

// ------------------- CAMBIO DE BODEGA -------------------
document.getElementById('in_bodega').addEventListener('change', manejarCambioBodega);
if (window.jQuery) {
    jQuery(document).on('select2:select select2:clear', '#in_bodega', manejarCambioBodega);
}

// ------------------- CAMBIO DE LOTE -------------------
document.getElementById('in_lote').addEventListener('change', manejarCambioLote);
if (window.jQuery) {
    jQuery(document).on('select2:select select2:clear', '#in_lote', manejarCambioLote);
}

// ------------------- AGREGAR A TABLA -------------------
function agregarATabla(){
    const cat = document.getElementById('in_cat').value;
    const selectDesc = document.getElementById('in_desc');
    const opt = selectDesc.options[selectDesc.selectedIndex];

    if(!document.getElementById('h_cultivo_id').value) return alert("Seleccione un cultivo");
    if(cultivoCerrado) return alert("No se puede agregar consumo porque el cultivo está cerrado.");

    let nombre = '';
    let insumoId = '';
    let bodega_id = null;
    let lote = null;
    const cantidad = parseFloat(document.getElementById('in_cant').value);
    const unidadManual = (document.getElementById('in_unidad').value || '').trim();
    const precioManual = parseFloat(document.getElementById('in_precio').value || 0);
    const descripcionManual = (document.getElementById('in_secundaria_manual').value || '').trim();

    const tipoIndirecto = (cat === 'Indirectos' && opt) ? (opt.dataset.indirectoTipo || '') : '';
    const indirectoSinInventario = cat === 'Indirectos' && ['manual', 'manoobra'].includes(tipoIndirecto);
    const indirectoCombustible = cat === 'Indirectos' && tipoIndirecto === 'combustible';
    const opcionManualCategoria = esOpcionManualCategoria(opt);
    const usaCapturaManual = modoCostoManual || indirectoSinInventario || opcionManualCategoria;

    if (usaCapturaManual) {
        nombre = descripcionManual || (opt?.dataset?.nombreBase || opt?.textContent || '').trim();
        if (cat === 'Indirectos' && tipoIndirecto === 'manoobra') {
            const actSec = document.getElementById('in_secundaria').value;
            if (actSec) nombre += ' (' + actSec + ')';
        }

        if (opcionManualCategoria && !descripcionManual) {
            return alert('Escriba la descripción del otro insumo o servicio para esta categoría.');
        }

        if (!nombre || !cat || !Number.isFinite(cantidad) || cantidad <= 0 || !unidadManual || !Number.isFinite(precioManual) || precioManual < 0) {
            return alert('Complete descripcion, categoria, cantidad, unidad y precio para costo indirecto/CIF/combustible.');
        }
    } else {
        if(!opt.value || document.getElementById('in_cant').value <= 0) return alert("Datos incompletos");
        nombre = opt.dataset.nombreBase || opt.textContent;
        if (indirectoCombustible) {
            const combustibleSel = document.getElementById('in_secundaria').value;
            if (!combustibleSel) return alert('Seleccione el combustible (actividad secundaria).');
            nombre = `Combustible (${combustibleSel})`;
        }
        insumoId = opt.value;
    }

    if(!usaCapturaManual && cat === 'Mano de Obra'){
        const actSec = document.getElementById('in_secundaria').value;
        if(actSec) nombre += ' ('+actSec+')';
    } else if (!usaCapturaManual) {
        bodega_id = document.getElementById('in_bodega').value || null;
        lote = document.getElementById('in_lote').value || null;
        const loteSel = document.getElementById('in_lote');
        const loteOpt = loteSel.options[loteSel.selectedIndex];

        const esGeneral = String(bodega_id) === 'GENERAL' || String(lote) === 'GENERAL';
        if (esGeneral && indirectoCombustible) {
            bodega_id = null;
            lote = 'GENERAL';
        }

        if (!indirectoCombustible && inventarioPorInsumo.length === 0) {
            return alert('Este insumo no tiene inventario disponible para consumo.');
        }

        if(!esGeneral && (!bodega_id || !lote)) return alert("Seleccione bodega y lote");

        if (loteOpt && loteOpt.dataset && loteOpt.dataset.insumoId) {
            insumoId = loteOpt.dataset.insumoId;
        }

        let stockMax = NaN;

        // 1) Preferir el stock del option seleccionado (siempre actualizado al cargar lotes).
        if (loteOpt && loteOpt.dataset) {
            stockMax = parseFloat(loteOpt.dataset.stock || '');
        }

        // 2) Fallback al valor guardado en el input.
        if (!Number.isFinite(stockMax)) {
            stockMax = parseFloat(document.getElementById('in_cant').dataset.stockMax || '');
        }

        // 3) Último fallback: buscar en la colección actual de inventario por bodega+lote.
        if (!Number.isFinite(stockMax)) {
            const inventarioMatch = inventarioPorInsumo.find(item =>
                String(item.bodega_id) === String(bodega_id) && String(item.numero_lote) === String(lote)
            );
            stockMax = parseFloat(inventarioMatch?.stock_actual || 0);
        }

        if (!Number.isFinite(stockMax) || stockMax < 0) {
            stockMax = 0;
        }

        const cantInput = document.getElementById('in_cant');
        cantInput.dataset.stockMax = stockMax;
        cantInput.max = stockMax;

        const cantidad = parseFloat(document.getElementById('in_cant').value);
        if(!esGeneral && cantidad > stockMax) return alert(`Stock insuficiente para ${opt.textContent} en el lote ${lote}. Disponible: ${stockMax}`);
    }

    const precio = usaCapturaManual ? precioManual : parseFloat(document.getElementById('in_precio').value);
    const subtotal = cantidad * precio;

    detalles.push({
        id: insumoId,
        categoria: cat,
        nombre,
        cantidad,
        unidad: usaCapturaManual ? unidadManual : opt.dataset.unidad,
        precio,
        subtotal,
        bodega_id,
        lote
    });

    renderizarTabla();
    limpiarDespuesDeAgregar(cat);
}

function actualizarCantidadDetalle(index, value) {
    const cantidad = parseFloat(value);

    if (!Number.isFinite(cantidad) || cantidad <= 0) {
        return;
    }

    detalles[index].cantidad = cantidad;
    detalles[index].subtotal = cantidad * parseFloat(detalles[index].precio || 0);
    renderizarTabla();
}

// ------------------- RENDERIZAR TABLA -------------------
function renderizarTabla(){
    const tbody = document.getElementById('cuerpoTabla');
    tbody.innerHTML = '';
    let total = 0;

    detalles.forEach((item, index)=>{
        total += item.subtotal;
        tbody.innerHTML += `<tr>
            <td>${item.categoria}</td>
            <td>${item.nombre}
                <input type="hidden" name="items[${index}][id]" value="${item.id ?? ''}">
                <input type="hidden" name="items[${index}][nombre]" value="${item.nombre}">
                <input type="hidden" name="items[${index}][categoria]" value="${item.categoria}">
                <input type="hidden" name="items[${index}][precio]" value="${item.precio}">
                <input type="hidden" name="items[${index}][unidad]" value="${item.unidad}">
                <input type="hidden" name="items[${index}][bodega_id]" value="${item.bodega_id || ''}">
                <input type="hidden" name="items[${index}][lote]" value="${item.lote || ''}">
                <input type="hidden" name="items[${index}][cantidad]" value="${item.cantidad}">
            </td>
            <td>
                <div class="input-group input-group-sm" style="min-width: 130px;">
                    <input
                        type="number"
                        class="form-control form-control-sm"
                        min="0.001"
                        step="0.001"
                        value="${item.cantidad}"
                        onchange="actualizarCantidadDetalle(${index}, this.value)"
                    >
                    <span class="input-group-text bg-light">
                        <i class="fa-solid fa-pen-to-square text-muted"></i>
                    </span>
                </div>
            </td>
            <td>${item.unidad}</td>
            <td class="text-end">L ${item.precio.toFixed(3)}</td>
            <td class="text-end fw-bold">L ${item.subtotal.toFixed(3)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarDetalle(${index})">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });

    document.getElementById('totalGral').innerText = total.toLocaleString('en-US',{minimumFractionDigits:3, maximumFractionDigits:3});
    document.getElementById('h_total_consumo').value = total.toFixed(3); // Guardar total real en hidden
}

// ------------------- ELIMINAR FILA -------------------
function eliminarDetalle(index){
    detalles.splice(index,1);
    renderizarTabla();
}

// ------------------- LIMPIAR FILTROS -------------------
function limpiarFiltroOpciones(){
    document.getElementById('in_cat').value = '';
    document.getElementById('in_desc').innerHTML = '<option value="">Seleccione...</option>';
    document.getElementById('in_cant').value = 1;
    document.getElementById('in_cant').max = '';
    document.getElementById('in_cant').dataset.stockMax = '';
    document.getElementById('in_unidad').value = '';
    document.getElementById('in_precio').value = '';
    document.getElementById('in_secundaria_manual').value = '';
    modoCostoManual = false;
    limpiarDependenciasInsumo();
    aplicarModoCategoria('');
    refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
}

// ------------------- VALIDAR ENVÍO -------------------
document.getElementById('formConsumo').addEventListener('submit', function(e){
    if(detalles.length === 0){
        e.preventDefault();
        alert("No ha agregado insumos o labores.");
        return false;
    }
    if(cultivoCerrado){
        e.preventDefault();
        alert("El cultivo está cerrado, no puede registrar consumos.");
        return false;
    }
    renderizarTabla(); // Reindexa inputs y guarda total
});

// ------------------- INICIALIZAR -------------------
sincronizarCabecera();
inicializarSelectsBuscables();
aplicarModoCategoria('');

if (document.getElementById('cultivo_id_head').value) {
    cargarDatosCultivo();
}

if (Array.isArray(detallesIniciales) && detallesIniciales.length > 0) {
    detalles = detallesIniciales.map((item) => ({
        ...item,
        id: item.id ?? '',
        cantidad: parseFloat(item.cantidad || 0),
        precio: parseFloat(item.precio || 0),
        subtotal: parseFloat(item.subtotal || 0),
    }));
    renderizarTabla();
}
</script>
@endsection