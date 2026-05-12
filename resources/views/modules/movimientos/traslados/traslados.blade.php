@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="fa fa-exchange-alt text-warning me-2"></i>
            Traslado de Insumos entre Bodegas
        </h5>
    </div>

    <div class="card-body">
        <form id="trasladoForm" action="{{ route('movimientos.traslado.store') }}" method="POST">
            @csrf

            <div class="row g-4 mt-1">
                <!-- Configuración del traslado -->
                <div class="col-md-5">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Configuración de Traslado</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Seleccione Insumo</label>
                            <select id="insumo_id" class="form-select shadow-sm">
                                <option value="">Seleccione un insumo</option>
                                @foreach($insumos as $insumo)
                                    @php
                                        $stockTotal = $insumo->inventarioBodegas->sum('stock_actual') ?? 0;
                                    @endphp
                                    <option value="{{ $insumo->id }}"
                                        data-codigo="{{ $insumo->codigo }}"
                                        data-nombre="{{ $insumo->nombre }}"
                                        data-unidad="{{ $insumo->unidad_medida }}"
                                        data-ingrediente="{{ $insumo->ingrediente_activo }}"
                                        data-stock-total="{{ agro_number($stockTotal, 2, '.', '') }}">
                                        {{ $insumo->codigo }} - {{ $insumo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bodega Origen</label>
                                <select id="bodega_origen_id" class="form-select shadow-sm">
                                    <option value="">Seleccione</option>
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Bodega Destino</label>
                                <select id="bodega_destino_id" class="form-select shadow-sm">
                                    <option value="">Seleccione</option>
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Cantidad a Trasladar</label>
                            <input type="number" step="0.01" id="cantidad" class="form-control" placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Descripción / Motivo</label>
                            <input type="text" id="descripcion" class="form-control" placeholder="Ej. Reabastecimiento">
                        </div>
                    </div>
                </div>

                <!-- Detalles técnicos y lote -->
                <div class="col-md-7">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Detalles Técnicos y Lote</h6>

                        <div class="p-3 border rounded mb-3 bg-white shadow-sm">
                            <div class="row text-center text-md-start g-2">
                                <div class="col-md-3 col-6">
                                    <small class="text-muted d-block">Codigo</small>
                                    <span id="datoCodigo" class="fw-bold">-</span>
                                </div>
                                <div class="col-md-3 col-6">
                                    <small class="text-muted d-block">Unidad</small>
                                    <span id="datoUnidad" class="fw-bold">-</span>
                                </div>
                                <div class="col-md-3 col-6">
                                    <small class="text-muted d-block">Stock Total</small>
                                    <span id="datoStockTotal" class="badge bg-secondary">0</span>
                                </div>
                                <div class="col-md-3 col-6">
                                    <small class="text-muted d-block">Stock Lote</small>
                                    <span id="stockInfo" class="badge bg-dark">0</span>
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Ingrediente Activo</small>
                                    <span id="datoIngrediente" class="fw-bold">-</span>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="row text-center">
                                <div class="col-4 border-end"><small class="text-muted d-block">Lote Origen</small><span id="datoLote" class="fw-bold">-</span></div>
                                <div class="col-4 border-end"><small class="text-muted d-block">F. Fab</small><span id="datoFab" class="small">-</span></div>
                                <div class="col-4"><small class="text-muted d-block">F. Vence</small><span id="datoVence" class="small text-danger">-</span></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Seleccione Lote Origen</label>
                            <select id="lote_id" class="form-select shadow-sm">
                                <option value="">Seleccione un lote</option>
                                {{-- Opciones de lotes agrupadas por código de insumo --}}
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Lote Destino (opcional)</label>
                            <div class="input-group">
                                <input type="text" id="lote_destino_manual" class="form-control" placeholder="Escriba un nuevo lote si desea">
                                <select id="lote_destino_id" class="form-select">
                                    <option value="">Seleccione existente...</option>
                                    {{-- Opciones de lotes agrupadas por código de insumo --}}
                                </select>
                            </div>
                            <small class="text-muted">Si deja vacío, se usará el mismo lote de origen. Si escribe uno nuevo, se creará automáticamente con vigencia de 2 años.</small>
                        </div>

                        <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm" id="btnAgregar">
                            <i class="fa fa-plus me-1"></i> AGREGAR AL LISTADO
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de traslado -->
            <div class="mt-4 table-responsive border rounded bg-white shadow-sm">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Insumo</th>
                            <th>Origen / Destino</th>
                            <th>Lote Origen</th>
                            <th>Lote Destino</th>
                            <th>Cantidad</th>
                            <th>Vencimiento</th>
                            <th>Descripción</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTabla"></tbody>
                </table>
            </div>

            <div class="mt-4 text-end border-top pt-3">
                <button type="submit" class="btn btn-warning fw-bold px-5 shadow">
                    <i class="fa fa-exchange-alt me-1"></i> PROCESAR TRASLADO
                </button>
            </div>
        </form>
    </div>
</div>

</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const LOTE_SIN_VALOR = '__SIN_LOTE__';

    const insumoSelect = document.getElementById('insumo_id');
    const loteSelect = document.getElementById('lote_id');
    const loteDestinoSelect = document.getElementById('lote_destino_id');
    const loteDestinoManualInput = document.getElementById('lote_destino_manual');

    const datoCodigo = document.getElementById('datoCodigo');
    const datoUnidad = document.getElementById('datoUnidad');
    const datoStockTotal = document.getElementById('datoStockTotal');
    const datoIngrediente = document.getElementById('datoIngrediente');

    const stockInfo = document.getElementById('stockInfo');
    const datoLote = document.getElementById('datoLote');
    const datoFab = document.getElementById('datoFab');
    const datoVence = document.getElementById('datoVence');

    const cantidadInput = document.getElementById('cantidad');
    const descripcionInput = document.getElementById('descripcion');

    const bodegaOrigen = document.getElementById('bodega_origen_id');
    const bodegaDestino = document.getElementById('bodega_destino_id');

    const btnAgregar = document.getElementById('btnAgregar');
    const cuerpoTabla = document.getElementById('cuerpoTabla');

    // =============================
    // SELECT2
    // =============================
    function initSelect(selector) {
        $(selector).select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    function resetDetalleLote() {
        stockInfo.textContent = '0';
        datoLote.textContent = '-';
        datoFab.textContent = '-';
        datoVence.textContent = '-';
    }

    function obtenerOptionSeleccionada(select) {
        return select.selectedOptions && select.selectedOptions.length > 0
            ? select.selectedOptions[0]
            : null;
    }

    function obtenerLoteRealDesdeSelect(select) {
        const option = obtenerOptionSeleccionada(select);

        if (!option) {
            return '';
        }

        return option.dataset.loteReal ?? option.value ?? '';
    }

    function actualizarDetalleLoteSeleccionado() {
        const option = obtenerOptionSeleccionada(loteSelect);

        if (!option || !option.value) {
            resetDetalleLote();
            return;
        }

        stockInfo.textContent = option.dataset.stock || '0';
        datoLote.textContent = option.dataset.loteMostrar || option.value || '-';
        datoFab.textContent = option.dataset.fabricacion || '-';
        datoVence.textContent = option.dataset.vencimiento || '-';
    }

    function obtenerStockSeleccionado() {
        const option = obtenerOptionSeleccionada(loteSelect);

        if (!option || !option.value) {
            return 0;
        }

        return parseFloat(option.dataset.stock || '0');
    }

    function obtenerCantidadYaAgregada(insumoId, bodegaOrigenId, loteOrigen) {
        return Array.from(cuerpoTabla.querySelectorAll('tr')).reduce((total, fila) => {
            const filaInsumo = fila.dataset.insumoId || '';
            const filaBodegaOrigen = fila.dataset.bodegaOrigenId || '';
            const filaLoteOrigen = fila.dataset.loteOrigen || '';

            if (filaInsumo === insumoId && filaBodegaOrigen === bodegaOrigenId && filaLoteOrigen === loteOrigen) {
                return total + parseFloat(fila.dataset.cantidad || '0');
            }

            return total;
        }, 0);
    }

    initSelect('#insumo_id');
    initSelect('#bodega_origen_id');
    initSelect('#bodega_destino_id');
    initSelect('#lote_id');
    initSelect('#lote_destino_id');

    // =============================
    // DETALLE INSUMO
    // =============================
    function actualizarDetalleInsumo() {
        const option = obtenerOptionSeleccionada(insumoSelect);

        if (!option || !option.value) {
            datoCodigo.textContent = '-';
            datoUnidad.textContent = '-';
            datoStockTotal.textContent = '0';
            datoIngrediente.textContent = '-';
            return;
        }

        datoCodigo.textContent = option.dataset.codigo || '-';
        datoUnidad.textContent = option.dataset.unidad || '-';
        datoStockTotal.textContent = option.dataset.stockTotal || '0';
        datoIngrediente.textContent = option.dataset.ingrediente || '-';
    }

    // =============================
    // CARGAR LOTES ORIGEN
    // =============================
    function cargarLotesOrigen() {
        const insumoId = insumoSelect.value;
        const bodegaId = bodegaOrigen.value;

        // Destruir select2 si está inicializado
        if ($('#lote_id').hasClass('select2-hidden-accessible')) {
            $('#lote_id').select2('destroy');
        }
        loteSelect.innerHTML = '<option value="">Seleccione un lote</option>';
        // Si no hay insumo o bodega, salir
        if (!insumoId || !bodegaId) return;

        // Petición AJAX para obtener lotes de origen
        fetch(`{{ route('movimientos.lotes-insumo') }}?insumo_id=${insumoId}&bodega_id=${bodegaId}`)
        .then(res => res.json())
        .then(data => {
            loteSelect.innerHTML = '';
            if (!data || data.length === 0) {
                loteSelect.innerHTML = '<option value="">Sin stock disponible</option>';
            } else {
                data.forEach(lote => {
                    let option = document.createElement('option');
                    option.value = lote.numero_lote_value;
                    option.textContent = `${lote.numero_lote_mostrar} | Stock: ${lote.stock_actual}`;
                    option.dataset.loteReal = lote.numero_lote ?? '';
                    option.dataset.loteMostrar = lote.numero_lote_mostrar ?? 'SIN LOTE';
                    option.dataset.stock = lote.stock_actual;
                    option.dataset.fabricacion = lote.fecha_fabricacion ?? '-';
                    option.dataset.vencimiento = lote.fecha_vencimiento ?? '-';
                    loteSelect.appendChild(option);
                });
            }
            // Volver a inicializar select2
            $('#lote_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            if (loteSelect.options.length > 0 && loteSelect.options[0].value) {
                loteSelect.selectedIndex = 0;
                $('#lote_id').trigger('change');
            } else {
                resetDetalleLote();
            }
        })
        .catch(error => {
            loteSelect.innerHTML = '<option value="">Error al cargar lotes</option>';
            resetDetalleLote();
            console.error("Error:", error);
        });
    }

    // =============================
    // CARGAR LOTES DESTINO
    // =============================
    function cargarLotesDestino() {
        const insumoId = insumoSelect.value;
        const bodegaId = bodegaDestino.value;
        if ($('#lote_destino_id').hasClass('select2-hidden-accessible')) {
            $('#lote_destino_id').select2('destroy');
        }
        loteDestinoSelect.innerHTML = '<option value="">Seleccione...</option>';
        if (!insumoId || !bodegaId) return;
        // Petición AJAX para obtener lotes de destino
        fetch(`{{ route('movimientos.lotes-insumo') }}?insumo_id=${insumoId}&bodega_id=${bodegaId}`)
        .then(res => res.json())
        .then(data => {
            loteDestinoSelect.innerHTML = '<option value="">Seleccione...</option>';
            if (data && data.length > 0) {
                data.forEach(lote => {
                    let option = document.createElement('option');
                    option.value = lote.numero_lote_value;
                    option.textContent = lote.numero_lote_mostrar;
                    option.dataset.loteReal = lote.numero_lote ?? '';
                    loteDestinoSelect.appendChild(option);
                });
            }
            $('#lote_destino_id').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        })
        .catch(error => {
            loteDestinoSelect.innerHTML = '<option value="">Error al cargar lotes</option>';
            console.error("Error:", error);
        });
    }

    // =============================
    // EVENTOS
    // =============================
    // Al cambiar insumo, actualizar detalles y lotes
    $('#insumo_id').on('change', () => {
        actualizarDetalleInsumo();
        cargarLotesOrigen();
        cargarLotesDestino();
        loteDestinoManualInput.value = '';
        resetDetalleLote();
    });

    // Al cambiar bodega origen, cargar lotes de origen
    $('#bodega_origen_id').on('change', () => {
        cargarLotesOrigen();
        resetDetalleLote();
    });
    // Al cambiar bodega destino, cargar lotes de destino
    $('#bodega_destino_id').on('change', cargarLotesDestino);

    // =============================
    // CAMBIO DE LOTE
    // =============================
    $('#lote_id').on('change select2:select', actualizarDetalleLoteSeleccionado);

    // =============================
    // AGREGAR A TABLA
    // =============================
    btnAgregar.addEventListener('click', function() {

        const cantidad = parseFloat(cantidadInput.value);
        const stock = obtenerStockSeleccionado();
        const loteOrigenReal = obtenerLoteRealDesdeSelect(loteSelect);
        const loteOrigenMostrar = datoLote.textContent;
        const loteOrigenKey = loteOrigenReal || LOTE_SIN_VALOR;
        const cantidadYaAgregada = obtenerCantidadYaAgregada(insumoSelect.value, bodegaOrigen.value, loteOrigenKey);
        const disponible = stock - cantidadYaAgregada;

        if (!insumoSelect.value || !bodegaOrigen.value || !bodegaDestino.value || !loteSelect.value) {
            Swal.fire('Validación', 'Complete todos los campos.', 'warning');
            return;
        }

        if (bodegaOrigen.value === bodegaDestino.value) {
            Swal.fire('Validación', 'La bodega de origen y destino deben ser distintas.', 'warning');
            return;
        }

        if (Number.isNaN(cantidad) || cantidad <= 0) {
            Swal.fire('Validación', 'Ingrese una cantidad válida.', 'warning');
            return;
        }

        if (cantidad > disponible) {
            Swal.fire('Validación', `Inventario insuficiente. Disponible para este lote: ${disponible.toFixed(2)}.`, 'warning');
            return;
        }

        let loteDestinoManual = loteDestinoManualInput.value.trim();
        let loteDestinoReal = loteDestinoManual
            || obtenerLoteRealDesdeSelect(loteDestinoSelect)
            || loteOrigenReal;
        let loteDestinoMostrar = loteDestinoManual
            || (obtenerOptionSeleccionada(loteDestinoSelect)?.textContent ?? '').trim()
            || loteOrigenMostrar;

        if (!loteDestinoMostrar) {
            loteDestinoMostrar = 'SIN LOTE';
        }

        const tr = document.createElement('tr');
        tr.dataset.insumoId = insumoSelect.value;
        tr.dataset.bodegaOrigenId = bodegaOrigen.value;
        tr.dataset.loteOrigen = loteOrigenKey;
        tr.dataset.cantidad = cantidad;

        tr.innerHTML = `
            <td>${insumoSelect.selectedOptions[0].text}
                <input type="hidden" name="insumo_ids[]" value="${insumoSelect.value}">
            </td>
            <td>${bodegaOrigen.selectedOptions[0].text} → ${bodegaDestino.selectedOptions[0].text}
                <input type="hidden" name="bodega_origen_ids[]" value="${bodegaOrigen.value}">
                <input type="hidden" name="bodega_destino_ids[]" value="${bodegaDestino.value}">
            </td>
            <td>${loteOrigenMostrar}
                <input type="hidden" name="lotes_origen[]" value="${loteOrigenReal || LOTE_SIN_VALOR}">
            </td>
            <td>${loteDestinoMostrar}
                <input type="hidden" name="lotes_destino[]" value="${loteDestinoReal || LOTE_SIN_VALOR}">
            </td>
            <td>${cantidad}
                <input type="hidden" name="cantidades[]" value="${cantidad}">
            </td>
            <td>${datoVence.textContent}</td>
            <td>${descripcionInput.value}
                <input type="hidden" name="descripcion[]" value="${descripcionInput.value}">
            </td>
            <td><button type="button" class="btn btn-danger btnEliminar">X</button></td>
        `;

        cuerpoTabla.appendChild(tr);

        cantidadInput.value = '';
        descripcionInput.value = '';
        loteDestinoManualInput.value = '';
    });

    document.getElementById('trasladoForm').addEventListener('submit', function(e) {
        if (!cuerpoTabla.children.length) {
            e.preventDefault();
            Swal.fire('Validación', 'Agregue al menos un traslado al listado antes de procesar.', 'warning');
        }
    });

    // =============================
    // ELIMINAR FILA
    // =============================
    cuerpoTabla.addEventListener('click', function(e) {
        if (e.target.classList.contains('btnEliminar')) {
            e.target.closest('tr').remove();
        }
    });

});
</script>
@endsection

