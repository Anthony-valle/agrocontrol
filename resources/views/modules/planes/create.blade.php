@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="card shadow-sm">

        <!-- CABECERA -->
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-seedling me-2 text-success"></i>Planificación de Cultivo
            </h5>
            <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">MODO: CREACIÓN</span>
        </div>

        <div class="card-body">
            <form action="{{ route('planes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="cultivo_id" id="h_cultivo_id">
                <input type="hidden" name="fecha_plan" id="h_fecha_plan">
                <input type="hidden" name="cosecha_estimada" id="h_cosecha_estimada">

                <!-- FILTROS CABECERA -->
                <div class="row g-3 mb-4 bg-light p-3 rounded border">
                    <div class="col-md-3">
                        <label class="small fw-bold">Cultivo:</label>
                        <select id="cultivo_id_head" class="form-select form-select-sm border-0 shadow-sm" onchange="cargarDatosCultivo()">
                            <option value="">-- Seleccione --</option>
                            @foreach($cultivos as $item)
                                <option value="{{ $item->id }}" data-cosecha="{{ $item->cosecha_estimada }}" data-unidad="{{ $item->unidad_medida }}" data-duracion="{{ $item->duracion_ciclo ?? '' }}" data-siembra="{{ $item->fecha_siembra ? \Carbon\Carbon::parse($item->fecha_siembra)->format('Y-m-d') : '' }}">
                                    {{ $item->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Fecha Inicio:</label>
                        <input type="date" id="fecha_plan_head" class="form-control form-control-sm border-0 shadow-sm" value="{{ date('Y-m-d') }}" onchange="sincronizarCabecera(); actualizarSemanaCultivo()">
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold">Cosecha Estimada:</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="text" id="cosecha_estimada" class="form-control border-0 bg-white" readonly>
                            <span class="input-group-text border-0 bg-white text-muted" id="unid_vis">U.M.</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-primary">Semana Actual (Año):</label>
                        <div class="input-group input-group-sm shadow-sm">
                            <span class="input-group-text bg-primary text-white border-0"><i class="fa-solid fa-calendar-week"></i></span>
                            <input type="text" class="form-control border-0 bg-white fw-bold" value="{{ date('W') }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- BOTONES CATEGORÍAS -->
                @php
                    $categoriasBotones = collect($categoriasInsumos)
                        ->filter()
                        ->map(function ($categoria) {
                            $normalizada = strtolower(trim((string) $categoria));

                            return match ($normalizada) {
                                'plantula', 'plántula' => 'Plantula',
                                'otros insumos', 'otros_insumos', 'otros', 'otro insumo', 'otros insumo' => 'Otros Insumos',
                                'preparacion de suelo', 'preparación de suelo' => 'Maquinaria',
                                'semilla', 'semillas' => 'Semillas',
                                default => trim((string) $categoria),
                            };
                        })
                        ->reject(fn ($categoria) => in_array($categoria, ['Mano de Obra', 'Indirectos', 'CIF', 'Combustible'], true))
                        ->merge(['Otros Insumos', 'Maquinaria'])
                        ->unique()
                        ->sort()
                        ->values();

                    $estilosCategorias = [
                        'Fertilizante' => ['class' => 'success', 'icon' => 'fa-solid fa-leaf'],
                        'Fitosanitario' => ['class' => 'warning', 'icon' => 'fa-solid fa-spray-can'],
                        'Combustible' => ['class' => 'dark', 'icon' => 'fa-solid fa-gas-pump'],
                        'CIF' => ['class' => 'secondary', 'icon' => 'fa-solid fa-coins'],
                        'Indirectos' => ['class' => 'dark', 'icon' => 'fa-solid fa-gears'],
                        'Plantula' => ['class' => 'info', 'icon' => 'fa-solid fa-seedling'],
                        'Otros Insumos' => ['class' => 'secondary', 'icon' => 'fa-solid fa-box-open'],
                        'Maquinaria' => ['class' => 'dark', 'icon' => 'fa-solid fa-tractor'],
                        'Semillas' => ['class' => 'secondary', 'icon' => 'fa-solid fa-tags'],
                    ];
                @endphp
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="cargarManoObra()">
                        <i class="fa-solid fa-helmet-safety"></i> Mano de Obra
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="cargarIndirectosAgrupados()">
                        <i class="fa-solid fa-gears"></i> Indirectos / CIF / Combustible
                    </button>
                    @foreach($categoriasBotones as $categoriaInsumo)
                        @php
                            $configCategoria = $estilosCategorias[$categoriaInsumo] ?? ['class' => 'secondary', 'icon' => 'fa-solid fa-tags'];
                        @endphp
                        <button type="button" class="btn btn-outline-{{ $configCategoria['class'] }} btn-sm" onclick="cargarInsumos(@js($categoriaInsumo))">
                            <i class="{{ $configCategoria['icon'] }}"></i> {{ $categoriaInsumo }}
                        </button>
                    @endforeach
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltro()">
                        <i class="fa-solid fa-eraser"></i> Limpiar
                    </button>
                    <a href="{{ route('categorias.index') }}" class="btn btn-outline-dark btn-sm">
                        <i class="fa-solid fa-layer-group"></i> Crear categoría
                    </a>
                </div>

                <!-- FORMULARIO AGREGAR ACTIVIDAD -->
                <div class="card border-primary mb-3 shadow-sm">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="small fw-bold">Semana de Cultivo</label>
                                <input type="number" id="in_semana" class="form-control form-control-sm border-primary" value="1" min="1" step="1">
                            </div>
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
                                    <option value="">No aplica</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="small fw-bold">Cant.</label>
                                <input type="number" id="in_cant" class="form-control form-control-sm border-primary" value="1" min="1">
                            </div>
                            <div class="col-md-1">
                                <label class="small fw-bold">U.M.</label>
                                <input type="text" id="in_unidad" class="form-control form-control-sm border-primary bg-light" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="small fw-bold">Precio (L)</label>
                                <input type="number" id="in_precio" class="form-control form-control-sm border-primary bg-light" readonly>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success btn-sm w-100 fw-bold" onclick="agregarATabla()">
                                    <i class="fa-solid fa-plus-circle me-1"></i> AGREGAR FILA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABLA -->
                <div class="table-responsive">
                    <table class="table table-sm align-middle table-hover border">
                        <thead class="table-dark">
                            <tr>
                                <th>Semana Cultivo</th>
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

                <!-- FOOTER -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 border-top pt-3 gap-2 gap-md-0">
                    <a href="{{ route('planes.index') }}" class="btn btn-secondary shadow-sm btn-sm px-3 py-2">
                        <i class="fa-solid fa-arrow-left me-2"></i> Volver
                    </a>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="fs-6 fw-bold text-success">Total: L <span id="totalGral">0.00</span></div>
                        <button type="submit" class="btn btn-primary px-3 py-2 fw-bold shadow btn-sm">
                            <i class="fa-solid fa-floppy-disk me-2"></i>GUARDAR TODO EL PLAN
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.border-primary { border: 1px solid #0d6efd !important; }
.bg-light { background-color: #f8f9fa !important; }
.table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
@media (max-width: 600px) {
    .d-flex.gap-2.flex-wrap > a,
    .d-flex.gap-2.flex-wrap > button,
    .fs-6.mb-0.fw-bold.text-success {
        font-size: 0.95rem !important;
        padding: 0.4rem 0.7rem !important;
    }
    .btn-sm {
        font-size: 0.95rem !important;
        padding: 0.4rem 0.7rem !important;
    }
}
</style>

<script>
// --- Lógica unificada igual a Consumo ---
let detalles = [];
@isset($labores)
const labores = @json($labores);
@else
const labores = [];
@endisset

function categoriaPlanDesdeSeleccion(tipo) {
    return tipo === 'Maquinaria' ? 'Preparacion de Suelo' : tipo;
}

function resolverCategoriaIndirectaPorDescripcion(descripcion) {
    const texto = String(descripcion || '').trim().toLowerCase();

    if (texto === 'combustible') {
        return 'Combustible';
    }

    if (texto === 'nomina confidencial' || texto === 'nómina confidencial') {
        return 'CIF';
    }

    return 'Indirectos';
}

function resolverCategoriaPlanActual() {
    const inputCategoria = document.getElementById('in_cat');
    const categoriaBase = categoriaPlanDesdeSeleccion(inputCategoria.value);
    const grupo = inputCategoria.dataset.grupo || '';
    const descripcionSeleccionada = document.getElementById('in_desc').value;

    if (grupo === 'indirectos') {
        return resolverCategoriaIndirectaPorDescripcion(descripcionSeleccionada);
    }

    return categoriaBase;
}

function formatearNombreActividadPlan(categoria, nombre) {
    let texto = (nombre || '').trim();
    const categoriaNormalizada = (categoria || '').trim().toLowerCase();

    if (['preparacion de suelo', 'preparación de suelo'].includes(categoriaNormalizada)) {
        texto = texto.replace(/^mecanizaci[oó]n\s*[-:]\s*/i, '');
    }

    return texto;
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
    refrescarSelectBusqueda('#in_secundaria', 'No aplica');
}

function calcularSemanaCultivoDesdeCabecera() {
    const selectCultivo = document.getElementById('cultivo_id_head');
    const opcion = selectCultivo.options[selectCultivo.selectedIndex];
    const fechaSiembra = opcion?.dataset?.siembra;
    const fechaPlan = document.getElementById('fecha_plan_head').value;

    if (!fechaSiembra || !fechaPlan) {
        return null;
    }

    const inicio = new Date(`${fechaSiembra}T00:00:00`);
    const fecha = new Date(`${fechaPlan}T00:00:00`);
    const diferenciaDias = Math.floor((fecha - inicio) / (1000 * 60 * 60 * 24));

    if (!Number.isFinite(diferenciaDias)) {
        return null;
    }

    return diferenciaDias < 0 ? 1 : Math.floor(diferenciaDias / 7) + 1;
}

function actualizarSemanaCultivo(valor = null) {
    const input = document.getElementById('in_semana');
    const semanaActual = parseInt(valor ?? calcularSemanaCultivoDesdeCabecera() ?? input.value ?? 1, 10) || 1;
    input.value = Math.max(1, semanaActual);
}

function limpiarFiltro() {
    document.getElementById('in_cat').value = '';
    document.getElementById('in_cat').dataset.grupo = '';
    document.getElementById('in_desc').innerHTML = '<option value="">Seleccione...</option>';
    document.getElementById('in_secundaria').innerHTML = '<option value="">No aplica</option>';
    document.getElementById('in_precio').value = '';
    document.getElementById('in_unidad').value = '';
    document.getElementById('in_precio').readOnly = true;
    document.getElementById('in_precio').classList.add('bg-light');

    refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
    refrescarSelectBusqueda('#in_secundaria', 'No aplica');
}

function llenarSelectDescripcion(data) {
    const select = document.getElementById('in_desc');
    const categoriaActual = categoriaPlanDesdeSeleccion(document.getElementById('in_cat').value);
    select.innerHTML = '<option value="">Seleccione...</option>';
    const nombresUnicos = new Set();
    data.forEach(item => {
        if (!nombresUnicos.has(item.nombre)) {
            nombresUnicos.add(item.nombre);
            const opt = document.createElement('option');
            opt.value = item.nombre;
            const nombreMostrado = formatearNombreActividadPlan(categoriaActual, item.nombre);
            const codigo = (item.codigo || '').trim();
            opt.textContent = codigo ? `${codigo} - ${nombreMostrado}` : nombreMostrado;
            opt.dataset.unidad = item.unidad_medida || 'U.M.';
            opt.dataset.precio = item.precio_unitario ?? 0;
            opt.dataset.permitePrecioManual = item.permite_precio_manual ? '1' : '0';
            if (item.actividades_secundarias) {
                opt.dataset.actividades = JSON.stringify(item.actividades_secundarias);
            }
            select.appendChild(opt);
        }
    });
    document.getElementById('in_precio').value = '';
    document.getElementById('in_unidad').value = '';
    document.getElementById('in_precio').readOnly = true;
    document.getElementById('in_precio').classList.add('bg-light');
    document.getElementById('in_secundaria').innerHTML = '<option value="">No aplica</option>';

    refrescarSelectBusqueda('#in_desc', 'Buscar producto...');
    refrescarSelectBusqueda('#in_secundaria', 'No aplica');
}

function cargarManoObra() {
    document.getElementById('in_cat').value = 'Mano de Obra';
    document.getElementById('in_cat').dataset.grupo = '';
    fetch(`/planes/descripciones/Mano de Obra`)
        .then(res => res.json())
        .then(data => llenarSelectDescripcion(data))
        .catch(err => console.error(err));
}

function cargarIndirectosAgrupados() {
    document.getElementById('in_cat').value = 'Indirectos';
    document.getElementById('in_cat').dataset.grupo = 'indirectos';

    llenarSelectDescripcion([
        { nombre: 'Agua potable', unidad_medida: 'Servicio', precio_unitario: 0 },
        { nombre: 'Energia electrica', unidad_medida: 'kWh', precio_unitario: 0 },
        { nombre: 'Mantenimiento de maquinaria', unidad_medida: 'Servicio', precio_unitario: 0 },
        { nombre: 'Nomina confidencial', unidad_medida: 'Jornal', precio_unitario: 0 },
        { nombre: 'Combustible', unidad_medida: 'Litro', precio_unitario: 0 },
        { nombre: 'Otro indirecto / servicio', unidad_medida: 'Unidad', precio_unitario: 0 },
    ]);
}

function cargarInsumos(tipo) {
    document.getElementById('in_cat').value = categoriaPlanDesdeSeleccion(tipo);
    document.getElementById('in_cat').dataset.grupo = '';
    fetch(`/planes/descripciones/${encodeURIComponent(tipo)}`)
        .then(res => res.json())
        .then(data => llenarSelectDescripcion(data))
        .catch(err => console.error(err));
}

document.getElementById('in_desc').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const catInput = document.getElementById('in_cat');
    const catActual = catInput.value;
    const selSecundaria = document.getElementById('in_secundaria');
    const precioInput = document.getElementById('in_precio');
    const permitePrecioManual = opt?.dataset?.permitePrecioManual === '1';

    if (catInput.dataset.grupo === 'indirectos') {
        catInput.value = resolverCategoriaIndirectaPorDescripcion(opt?.value);
    }

    selSecundaria.innerHTML = '<option value="">No aplica</option>';
    if(opt.value) {
        precioInput.value = opt.dataset.precio;
        document.getElementById('in_unidad').value = opt.dataset.unidad;
        precioInput.readOnly = !(['Indirectos', 'CIF', 'Combustible'].includes(catInput.value) || permitePrecioManual);
        precioInput.classList.toggle('bg-light', !(['Indirectos', 'CIF', 'Combustible'].includes(catInput.value) || permitePrecioManual));
        if(catActual === 'Mano de Obra'){
            // Usar el dataset.actividades del option seleccionado
            let actividades = [];
            if(opt.dataset.actividades){
                try {
                    actividades = JSON.parse(opt.dataset.actividades);
                } catch(e) { actividades = []; }
            }
            actividades.forEach(act => {
                selSecundaria.appendChild(new Option(act, act));
            });
            refrescarSelectBusqueda('#in_secundaria', 'Buscar actividad...');
        } else {
            refrescarSelectBusqueda('#in_secundaria', 'No aplica');
        }
    } else {
        precioInput.value = '';
        precioInput.readOnly = true;
        precioInput.classList.add('bg-light');
        document.getElementById('in_unidad').value = '';
        refrescarSelectBusqueda('#in_secundaria', 'No aplica');
    }
});

if (window.jQuery) {
    jQuery(document).on('select2:select select2:clear', '#cultivo_id_head', cargarDatosCultivo);
    jQuery(document).on('select2:select select2:clear', '#in_desc', function() {
        document.getElementById('in_desc').dispatchEvent(new Event('change'));
    });
}

function cargarDatosCultivo() {
    const sel = document.getElementById('cultivo_id_head');
    const opt = sel.options[sel.selectedIndex];
    const inputCosecha = document.getElementById('cosecha_estimada');
    const spanUnidad = document.getElementById('unid_vis');
    if(sel.value) {
        const cosecha = parseFloat(opt.dataset.cosecha) || 0;
        const unidad = opt.dataset.unidad || 'U.M.';
        inputCosecha.value = cosecha.toLocaleString('en-US', {minimumFractionDigits:2});
        spanUnidad.innerText = unidad;
        document.getElementById('h_cultivo_id').value = sel.value;
        document.getElementById('h_cosecha_estimada').value = cosecha;
        sincronizarCabecera();
    } else {
        inputCosecha.value = "";
        spanUnidad.innerText = "U.M.";
    }

    actualizarSemanaCultivo();
}

function sincronizarCabecera() {
    document.getElementById('h_fecha_plan').value = document.getElementById('fecha_plan_head').value;
}

function agregarATabla() {
    const sem = parseInt(document.getElementById('in_semana').value, 10) || 0;
    const cat = resolverCategoriaPlanActual();
    const desc = document.getElementById('in_desc').value;
    const actSec = document.getElementById('in_secundaria').value;
    const cant = parseFloat(document.getElementById('in_cant').value) || 0;
    const unidad = document.getElementById('in_unidad').value;
    const precio = parseFloat(document.getElementById('in_precio').value) || 0;

    if(!desc || !cat || cant <= 0 || sem < 1) { alert("Complete todos los campos."); return; }

    const descripcionFinal = (cat === 'Mano de Obra' && actSec)
        ? `${desc} (${actSec})`
        : desc;

    const subtotal = cant * precio;

    detalles.push({semana: sem, categoria: cat, descripcion: descripcionFinal, cantidad: cant, unidad: unidad, precio: precio, subtotal: subtotal});
    detalles.sort((a,b)=>a.semana-b.semana);
    renderizarTabla();
}

function renderizarTabla() {
    const tbody = document.getElementById('cuerpoTabla');
    tbody.innerHTML = '';
    detalles.forEach((item,index)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.semana}<input type="hidden" name="semana[]" value="${item.semana}"></td>
            <td>${item.categoria}<input type="hidden" name="categoria[]" value="${item.categoria}"></td>
            <td>${item.descripcion}<input type="hidden" name="descripcion[]" value="${item.descripcion}"></td>
            <td>${item.cantidad}<input type="hidden" name="cantidad_estimada[]" value="${item.cantidad}"></td>
            <td>${item.unidad}<input type="hidden" name="unidad_medida[]" value="${item.unidad}"></td>
            <td class="text-end">${item.precio.toFixed(2)}<input type="hidden" name="costo_unitario[]" value="${item.precio}"></td>
            <td class="text-end fw-bold">${item.subtotal.toFixed(2)}<input type="hidden" class="subtotal-fila" value="${item.subtotal}"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarFila(${index})">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    calcularTotal();
}

function eliminarFila(index) {
    detalles.splice(index,1);
    renderizarTabla();
}

function calcularTotal() {
    let total = 0;
    detalles.forEach(item=>total+=item.subtotal);
    document.getElementById('totalGral').innerText = total.toLocaleString('en-US',{minimumFractionDigits:2, maximumFractionDigits:2});
}

window.onload = function() {
    sincronizarCabecera();
    actualizarSemanaCultivo();
    inicializarSelectsBuscables();
};
</script>
@endsection