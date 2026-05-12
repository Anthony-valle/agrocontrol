@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="fw-bold mb-0">
            <i class="{{ $iconoMovimiento ?? 'fa fa-exchange-alt' }} text-{{ $colorMovimiento ?? 'warning' }} me-2"></i>
            {{ $tituloMovimiento ?? 'Ajuste de Inventario (+ / -)' }}
        </h5>
    </div>

    <div class="card-body">
        <form id="ajusteForm" action="{{ $accionMovimiento ?? route('movimientos.ajuste.store') }}" method="POST">
            @csrf

            <div class="row g-4">

                <!-- IZQUIERDA: Configuración del ajuste -->
                <div class="col-md-5">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-{{ $colorMovimiento ?? 'warning' }} border-bottom pb-2">Configuración de {{ ($modoMovimiento ?? 'ajuste') === 'salida' ? 'Salida' : 'Ajuste' }}</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione Insumo</label>
                            <select id="insumo_id" class="form-select">
                                <option value="">Seleccione un insumo</option>
                                @foreach($insumos as $insumo)
                                    @php
                                        $stockTotal = $insumo->inventarioBodegas->sum('stock_actual') ?? 0;
                                    @endphp
                                    <option value="{{ $insumo->id }}">
                                        {{ $insumo->codigo }} - {{ $insumo->nombre }} (Disponible: {{ $stockTotal }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Bodega</label>
                            <select id="bodega_id" class="form-select">
                                <option value="">Seleccione</option>
                                @foreach($bodegas as $bodega)
                                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ ($modoMovimiento ?? 'ajuste') === 'salida' ? 'Cantidad a Salir' : 'Cantidad a Ajustar' }}</label>
                            <input type="number" step="0.01" id="cantidad" class="form-control" placeholder="0.00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Ajuste</label>
                            <select id="tipo_ajuste" class="form-select">
                                <option value="RESTA">➖ Restar (Salida)</option>
                                <option value="SUMA">➕ Sumar (Entrada)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Motivo / Descripción</label>
                            <input type="text" id="descripcion" class="form-control" placeholder="{{ $descripcionPlaceholder ?? 'Ej. Ajuste por pérdida' }}">
                        </div>
                    </div>
                </div>

                <!-- DERECHA: Lote y detalles -->
                <div class="col-md-7">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-{{ $colorMovimiento ?? 'warning' }} border-bottom pb-2">Detalles Técnicos y Lote</h6>

                        <div class="p-3 border rounded mb-3 bg-white shadow-sm">
                            <div class="row text-center">
                                <div class="col-3"><small>Stock Disponible</small><br><span id="stockInfo" class="badge bg-dark">0</span></div>
                                <div class="col-3"><small>Lote</small><br><span id="datoLote">-</span></div>
                                <div class="col-3"><small>F. Fabricación</small><br><span id="datoFab">-</span></div>
                                <div class="col-3"><small>F. Vencimiento</small><br><span id="datoVence">-</span></div>
                            </div>
                            <hr>
                            <div><small>Cantidad</small><br><span id="datoCantidad">0</span></div>
                            <div><small>Descripción</small><br><span id="datoDescripcion">-</span></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione Lote</label>
                            <select id="lote_id" class="form-select">
                                <option value="">Seleccione un lote</option>
                                @foreach($lotes as $lote)
                                    <option value="{{ $lote->numero_lote }}"
                                        data-insumo="{{ $lote->insumo_id }}"
                                        data-bodega="{{ $lote->bodega_id }}"
                                        data-stock="{{ $lote->stock_actual }}"
                                        data-fabricacion="{{ $lote->fecha_fabricacion }}"
                                        data-vencimiento="{{ $lote->fecha_vencimiento }}">
                                        {{ $lote->numero_lote }} 
                                        ({{ $lote->bodega->nombre ?? 'Sin Bodega' }};
                                        {{ agro_number($lote->stock_actual,2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" id="fecha_fabricacion">
                        <input type="hidden" id="fecha_vencimiento">

                        <button type="button" class="btn btn-{{ $colorMovimiento ?? 'warning' }} w-100 fw-bold" id="btnAgregar">
                            {{ $textoAgregarMovimiento ?? 'Agregar al listado' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Ajustes -->
            <div class="mt-4 table-responsive border rounded shadow-sm bg-white">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Insumo</th>
                            <th>Bodega</th>
                            <th>Tipo</th>
                            <th>Lote</th>
                            <th>Fabricación</th>
                            <th>Cantidad</th>
                            <th>Vencimiento</th>
                            <th>Descripción</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTabla"></tbody>
                </table>
            </div>

            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-{{ $colorMovimiento ?? 'warning' }} fw-bold px-4">
                    <i class="fa fa-save me-1"></i> {{ $textoBotonMovimiento ?? 'Procesar Ajuste' }}
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
    const modoMovimiento = @json($modoMovimiento ?? 'ajuste');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const form = document.getElementById('ajusteForm');
    const insumo = document.getElementById('insumo_id');
    const bodega = document.getElementById('bodega_id');
    const lote = document.getElementById('lote_id');
    const tipoAjuste = document.getElementById('tipo_ajuste');

    const stockInfo = document.getElementById('stockInfo');
    const fechaFab = document.getElementById('fecha_fabricacion');
    const fechaVen = document.getElementById('fecha_vencimiento');
    const datoLote = document.getElementById('datoLote');
    const datoFab = document.getElementById('datoFab');
    const datoVence = document.getElementById('datoVence');
    const datoCantidad = document.getElementById('datoCantidad');
    const datoDescripcion = document.getElementById('datoDescripcion');

    const cantidad = document.getElementById('cantidad');
    const descripcion = document.getElementById('descripcion');
    const cuerpoTabla = document.getElementById('cuerpoTabla');

    function initSelectBuscable(selector, placeholder) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) return;
        const $el = jQuery(selector);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: placeholder || 'Seleccione...',
            minimumResultsForSearch: 0,
            allowClear: false
        });
    }

    initSelectBuscable('#insumo_id', 'Buscar insumo...');
    initSelectBuscable('#bodega_id', 'Buscar bodega...');
    initSelectBuscable('#tipo_ajuste', 'Buscar tipo...');
    initSelectBuscable('#lote_id', 'Buscar lote...');

    if (modoMovimiento === 'salida') {
        tipoAjuste.value = 'RESTA';
        tipoAjuste.setAttribute('disabled', 'disabled');
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery(tipoAjuste).val('RESTA').trigger('change');
        }
    }

    function mostrarErrores(error) {
        if (error && error.errors) {
            let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            Object.values(error.errors).flat().forEach((msg) => {
                mensajes += `<li>${msg}</li>`;
            });
            mensajes += '</ul>';
            Swal.fire({ title: 'Error de validación', html: mensajes, icon: 'error' });
            return;
        }

        Swal.fire('Error', error?.message || 'No se pudo registrar el ajuste.', 'error');
    }

    const allOptions = Array.from(lote.querySelectorAll('option')).filter(op => op.value !== "");

    function resetFicha() {
        stockInfo.textContent = 0;
        datoLote.textContent = '-';
        datoFab.textContent = '-';
        datoVence.textContent = '-';
        datoCantidad.textContent = '0';
        datoDescripcion.textContent = '-';
        fechaFab.value = '';
        fechaVen.value = '';
        lote.dataset.stockActual = '0';
    }

    function actualizarFicha() {
        datoCantidad.textContent = cantidad.value || '0';
        datoDescripcion.textContent = descripcion.value || '-';
    }

    function obtenerLoteSeleccionado() {
        if (!lote.value) {
            return null;
        }

        return Array.from(lote.options).find((option) => option.value === lote.value) || null;
    }

    function sincronizarLoteSeleccionado() {
        const op = obtenerLoteSeleccionado();

        if (!op) {
            resetFicha();
            actualizarFicha();
            return;
        }

        const stockActual = parseFloat(op.dataset.stock || 0);
        stockInfo.textContent = Number.isFinite(stockActual) ? stockActual : 0;
        lote.dataset.stockActual = String(Number.isFinite(stockActual) ? stockActual : 0);
        fechaFab.value = op.dataset.fabricacion || '';
        fechaVen.value = op.dataset.vencimiento || '';
        datoLote.textContent = op.value || '-';
        datoFab.textContent = op.dataset.fabricacion || '-';
        datoVence.textContent = op.dataset.vencimiento || '-';
        actualizarFicha();
    }

    function filtrarLotes() {
        const selectedInsumo = insumo.value;
        const selectedBodega = bodega.value;
        lote.innerHTML = '<option value="">Seleccione un lote</option>';

        if (!selectedInsumo && !selectedBodega) {
            lote.innerHTML = '<option value="">Seleccione insumo y bodega</option>';
            resetFicha();
            initSelectBuscable('#lote_id', 'Seleccione insumo y bodega');
            return;
        }

        if (!selectedInsumo) {
            lote.innerHTML = '<option value="">Seleccione un insumo</option>';
            resetFicha();
            initSelectBuscable('#lote_id', 'Seleccione un insumo');
            return;
        }

        if (!selectedBodega) {
            lote.innerHTML = '<option value="">Seleccione una bodega</option>';
            resetFicha();
            initSelectBuscable('#lote_id', 'Seleccione una bodega');
            return;
        }

        const lotesFiltrados = [];
        allOptions.forEach(op => {
            if(op.dataset.insumo === selectedInsumo && op.dataset.bodega === selectedBodega){
                const cloned = op.cloneNode(true);
                lote.appendChild(cloned);
                lotesFiltrados.push(cloned);
            }
        });

        if (!lotesFiltrados.length) {
            lote.innerHTML = '<option value="">Sin lotes en la bodega seleccionada</option>';
            resetFicha();
            initSelectBuscable('#lote_id', 'Sin lotes disponibles');
            return;
        }

        resetFicha();
        initSelectBuscable('#lote_id', 'Buscar lote...');

        if (lotesFiltrados.length === 1) {
            lote.value = lotesFiltrados[0].value;

            if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery(lote).val(lotesFiltrados[0].value).trigger('change');
            } else {
                lote.dispatchEvent(new Event('change'));
            }

            sincronizarLoteSeleccionado();
        }
    }

    insumo.addEventListener('change', filtrarLotes);
    bodega.addEventListener('change', filtrarLotes);
    if (window.jQuery) {
        jQuery(document).on('select2:select select2:clear', '#insumo_id', filtrarLotes);
        jQuery(document).on('select2:select select2:clear', '#bodega_id', filtrarLotes);
    }

    lote.addEventListener('change', sincronizarLoteSeleccionado);
    if (window.jQuery) {
        jQuery(document).on('select2:select select2:clear', '#lote_id', sincronizarLoteSeleccionado);
    }

    cantidad.addEventListener('input', actualizarFicha);
    descripcion.addEventListener('input', actualizarFicha);

    document.getElementById('btnAgregar').addEventListener('click', () => {
        if(!insumo.value || !bodega.value || !lote.value || !cantidad.value){
            Swal.fire('Validación', 'Complete todos los campos.', 'warning');
            return;
        }

        const tipoSeleccionado = modoMovimiento === 'salida' ? 'RESTA' : tipoAjuste.value;
        const esResta = tipoSeleccionado === 'RESTA';
        const stockDisponible = parseFloat(lote.dataset.stockActual || '0');
        if(esResta && Number(cantidad.value) > stockDisponible){
            Swal.fire('Validación', 'La cantidad supera el stock disponible.', 'warning');
            return;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${insumo.selectedOptions[0].text}
                <input type="hidden" name="insumo_ids[]" value="${insumo.value}">
            </td>
            <td>${bodega.selectedOptions[0].text}
                <input type="hidden" name="bodega_origen_ids[]" value="${bodega.value}">
            </td>
            <td>${tipoSeleccionado === 'SUMA' ? '➕ Entrada' : '➖ Salida'}
                <input type="hidden" name="tipo_ajuste[]" value="${tipoSeleccionado}">
            </td>
            <td>${lote.value}
                <input type="hidden" name="lotes[]" value="${lote.value}">
            </td>
            <td>${fechaFab.value}
                <input type="hidden" name="fechas_fabricacion[]" value="${fechaFab.value}">
            </td>
            <td>${cantidad.value}
                <input type="hidden" name="cantidades[]" value="${cantidad.value}">
            </td>
            <td>${fechaVen.value}
                <input type="hidden" name="fechas_vencimiento[]" value="${fechaVen.value}">
            </td>
            <td>${descripcion.value}
                <input type="hidden" name="descripcion[]" value="${descripcion.value}">
            </td>
            <td><button type="button" class="btn btn-danger btn-sm eliminar">X</button></td>
        `;
        cuerpoTabla.appendChild(tr);

        insumo.value = '';
        bodega.value = '';
        lote.innerHTML = '<option value="">Seleccione un lote</option>';
        cantidad.value = '';
        descripcion.value = '';
        tipoAjuste.value = 'RESTA';
        resetFicha();
        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery(insumo).val('').trigger('change');
            jQuery(bodega).val('').trigger('change');
            jQuery(tipoAjuste).val('RESTA').trigger('change');
        }
        if (modoMovimiento === 'salida') {
            tipoAjuste.setAttribute('disabled', 'disabled');
        }
        initSelectBuscable('#lote_id', 'Buscar lote...');
    });

    cuerpoTabla.addEventListener('click', e => {
        if(e.target.classList.contains('eliminar')){
            e.target.closest('tr').remove();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!cuerpoTabla.children.length) {
            Swal.fire('Validación', modoMovimiento === 'salida' ? 'Agrega al menos una salida antes de procesar.' : 'Agrega al menos un ajuste antes de procesar.', 'warning');
            return;
        }

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw data;
                return data;
            })
            .then((data) => {
                Swal.fire('Éxito', data.success || 'Ajustes registrados correctamente.', 'success').then(() => {
                    window.location.href = data.redirect || '{{ route('movimientos.index') }}';
                });
            })
            .catch(mostrarErrores);
    });
});
</script>
@endsection