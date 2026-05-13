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
                <div class="col-md-5">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Configuracion de Traslado</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Seleccione Insumo</label>
                            <select id="insumo_id" class="form-select shadow-sm">
                                <option value="">Seleccione un insumo</option>
                                @foreach($insumos as $insumo)
                                    <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>
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
                            <label class="form-label fw-bold small">Descripcion / Motivo</label>
                            <input type="text" id="descripcion" class="form-control" placeholder="Ej. Reabastecimiento">
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Detalles Tecnicos y Lote</h6>

                        <div class="p-3 border rounded mb-3 bg-white shadow-sm">
                            <div class="row text-center">
                                <div class="col-3 border-end"><small class="text-muted d-block">Stock</small><span id="stockInfo" class="badge bg-dark">0</span></div>
                                <div class="col-3 border-end"><small class="text-muted d-block">Lote Origen</small><span id="datoLote" class="fw-bold">-</span></div>
                                <div class="col-3 border-end"><small class="text-muted d-block">F. Fab</small><span id="datoFab" class="small">-</span></div>
                                <div class="col-3"><small class="text-muted d-block">F. Vence</small><span id="datoVence" class="small text-danger">-</span></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Seleccione Lote Origen</label>
                            <select id="lote_id" class="form-select shadow-sm" disabled>
                                <option value="">Seleccione un lote</option>
                                @foreach($lotes as $lote)
                                    <option value="{{ $lote->numero_lote }}"
                                        data-insumo="{{ $lote->insumo_id }}"
                                        data-bodega="{{ $lote->bodega_id }}"
                                        data-stock="{{ $lote->stock_actual }}"
                                        data-fabricacion="{{ $lote->fecha_fabricacion }}"
                                        data-vencimiento="{{ $lote->fecha_vencimiento }}">
                                        {{ $lote->numero_lote }} ({{ collect($bodegas)->firstWhere('id',$lote->bodega_id)->nombre ?? '-' }}: {{ $lote->stock_actual }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Lote Destino (opcional)</label>
                            <div class="input-group">
                                <input type="text" id="lote_destino_manual" class="form-control" placeholder="Escriba un nuevo lote si desea">
                                <select id="lote_destino_id" class="form-select" disabled>
                                    <option value="">Seleccione existente...</option>
                                    @foreach($lotes as $lote)
                                        <option value="{{ $lote->numero_lote }}"
                                            data-insumo="{{ $lote->insumo_id }}"
                                            data-bodega="{{ $lote->bodega_id }}"
                                            data-stock="{{ $lote->stock_actual }}"
                                            data-fabricacion="{{ $lote->fecha_fabricacion }}"
                                            data-vencimiento="{{ $lote->fecha_vencimiento }}">
                                            {{ $lote->numero_lote }} ({{ collect($bodegas)->firstWhere('id',$lote->bodega_id)->nombre ?? '-' }}: {{ $lote->stock_actual }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <small class="text-muted">Si deja vacio, se usara el mismo lote de origen. Si escribe uno nuevo, se creara automaticamente con vigencia de 2 anios.</small>
                        </div>

                        <button type="button" class="btn btn-warning w-100 fw-bold shadow-sm" id="btnAgregar">
                            <i class="fa fa-plus me-1"></i> AGREGAR AL LISTADO
                        </button>
                    </div>
                </div>
            </div>

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
                            <th>Descripcion</th>
                            <th>Accion</th>
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

<style>
.select2-container {
    width: 100% !important;
}

.select2-container--bootstrap-5 .select2-selection {
    min-height: calc(2.25rem + 2px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const form = document.getElementById('trasladoForm');
    const insumoSelect = document.getElementById('insumo_id');
    const loteSelect = document.getElementById('lote_id');
    const loteDestinoSelect = document.getElementById('lote_destino_id');
    const loteDestinoManual = document.getElementById('lote_destino_manual');
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
            allowClear: false,
        });
    }

    initSelectBuscable('#insumo_id', 'Buscar insumo...');
    initSelectBuscable('#bodega_origen_id', 'Buscar bodega origen...');
    initSelectBuscable('#bodega_destino_id', 'Buscar bodega destino...');
    initSelectBuscable('#lote_id', 'Seleccione insumo y bodega');
    initSelectBuscable('#lote_destino_id', 'Seleccione insumo y bodega');

    function mostrarErrores(error) {
        if (error && error.errors) {
            let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            Object.values(error.errors).flat().forEach((msg) => {
                mensajes += `<li>${msg}</li>`;
            });
            mensajes += '</ul>';
            Swal.fire({ title: 'Error de validacion', html: mensajes, icon: 'error' });
            return;
        }

        Swal.fire('Error', error?.message || 'No se pudo registrar el traslado.', 'error');
    }

    const lotesOriginales = Array.from(loteSelect.options).filter(o => o.value !== '');

    function resetFicha() {
        stockInfo.textContent = '0';
        datoLote.textContent = '-';
        datoFab.textContent = '-';
        datoVence.textContent = '-';
    }

    function filtrarLotesOrigen() {
        const insumo = insumoSelect.value;
        const bodega = bodegaOrigen.value;
        loteSelect.innerHTML = '<option value="">Seleccione un lote</option>';
        resetFicha();
        if (!insumo || !bodega) {
            loteSelect.disabled = true;
            initSelectBuscable('#lote_id', 'Seleccione insumo y bodega');
            return;
        }

        let encontrados = 0;
        lotesOriginales.forEach(option => {
            if(String(option.dataset.insumo) === String(insumo) && String(option.dataset.bodega) === String(bodega)) {
                loteSelect.appendChild(option.cloneNode(true));
                encontrados++;
            }
        });

        loteSelect.disabled = (encontrados === 0);
        if(encontrados === 0) {
            loteSelect.innerHTML = '<option value="">Sin stock en esta bodega</option>';
            initSelectBuscable('#lote_id', 'Sin lotes disponibles');
            return;
        }

        initSelectBuscable('#lote_id', 'Buscar lote origen...');
    }

    function filtrarLotesDestino() {
        const insumo = insumoSelect.value;
        const bodega = bodegaDestino.value;
        loteDestinoSelect.innerHTML = '<option value="">Seleccione existente...</option>';
        if (!insumo || !bodega) {
            loteDestinoSelect.disabled = true;
            initSelectBuscable('#lote_destino_id', 'Seleccione insumo y bodega');
            return;
        }

        loteDestinoSelect.disabled = false;
        lotesOriginales.forEach(option => {
            if(String(option.dataset.insumo) === String(insumo) && String(option.dataset.bodega) === String(bodega)) {
                loteDestinoSelect.appendChild(option.cloneNode(true));
            }
        });

        initSelectBuscable('#lote_destino_id', 'Buscar lote destino...');
    }

    insumoSelect.addEventListener('change', () => { filtrarLotesOrigen(); filtrarLotesDestino(); });
    bodegaOrigen.addEventListener('change', filtrarLotesOrigen);
    bodegaDestino.addEventListener('change', filtrarLotesDestino);

    if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
        jQuery(document).on('select2:select select2:clear', '#insumo_id', () => {
            filtrarLotesOrigen();
            filtrarLotesDestino();
        });
        jQuery(document).on('select2:select select2:clear', '#bodega_origen_id', filtrarLotesOrigen);
        jQuery(document).on('select2:select select2:clear', '#bodega_destino_id', filtrarLotesDestino);
        jQuery(document).on('select2:select select2:clear', '#lote_id', () => loteSelect.dispatchEvent(new Event('change')));
    }

    loteSelect.addEventListener('change', function() {
        const option = this.selectedOptions[0];
        if(option && option.value) {
            stockInfo.textContent = option.dataset.stock || '0';
            datoLote.textContent = option.value || '-';
            datoFab.textContent = option.dataset.fabricacion || '-';
            datoVence.textContent = option.dataset.vencimiento || '-';
        } else resetFicha();
    });

    btnAgregar.addEventListener('click', function() {
        const cantidad = parseFloat(cantidadInput.value);
        const stock = parseFloat(stockInfo.textContent);

        if(!insumoSelect.value || !bodegaOrigen.value || !bodegaDestino.value || !loteSelect.value || !cantidad) {
            Swal.fire('Validacion', 'Complete todos los campos obligatorios.', 'warning');
            return;
        }
        if(bodegaOrigen.value === bodegaDestino.value) {
            Swal.fire('Validacion', 'La bodega de origen y destino no pueden ser la misma.', 'warning');
            return;
        }
        if(cantidad > stock) {
            Swal.fire('Validacion', `La cantidad supera el stock disponible (${stock}).`, 'warning');
            return;
        }

        let loteDestinoFinal = loteDestinoManual.value || loteDestinoSelect.value || loteSelect.value;
        let fechaFab = '';
        let fechaVence = '';

        const hoy = new Date();
        const dd = String(hoy.getDate()).padStart(2,'0');
        const mm = String(hoy.getMonth()+1).padStart(2,'0');
        const yyyy = hoy.getFullYear();

        if(loteDestinoManual.value) {
            fechaFab = `${yyyy}-${mm}-${dd}`;
            fechaVence = `${yyyy+2}-${mm}-${dd}`;
        } else {
            fechaFab = loteSelect.selectedOptions[0].dataset.fabricacion || '';
            fechaVence = loteSelect.selectedOptions[0].dataset.vencimiento || '';
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${insumoSelect.selectedOptions[0].text}<input type="hidden" name="insumo_ids[]" value="${insumoSelect.value}"></td>
            <td><small>${bodegaOrigen.selectedOptions[0].text} → ${bodegaDestino.selectedOptions[0].text}</small>
                <input type="hidden" name="bodega_origen_ids[]" value="${bodegaOrigen.value}">
                <input type="hidden" name="bodega_destino_ids[]" value="${bodegaDestino.value}">
            </td>
            <td><span class="badge bg-secondary">${loteSelect.value}</span><input type="hidden" name="lotes_origen[]" value="${loteSelect.value}"></td>
            <td><span class="badge bg-info">${loteDestinoFinal}</span>
                <input type="hidden" name="lotes_destino[]" value="${loteDestinoFinal}">
                <input type="hidden" name="fechas_fabricacion_destino[]" value="${fechaFab}">
                <input type="hidden" name="fechas_vencimiento_destino[]" value="${fechaVence}">
            </td>
            <td>${cantidad}<input type="hidden" name="cantidades[]" value="${cantidad}"></td>
            <td>${fechaVence}</td>
            <td>${descripcionInput.value}<input type="hidden" name="descripcion[]" value="${descripcionInput.value}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btnEliminar"><i class="fa fa-trash"></i></button></td>
        `;
        cuerpoTabla.appendChild(tr);

        cantidadInput.value = '';
        descripcionInput.value = '';
        loteDestinoManual.value = '';
    });

    cuerpoTabla.addEventListener('click', function(e) {
        if(e.target.closest('.btnEliminar')) {
            e.target.closest('tr').remove();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!cuerpoTabla.children.length) {
            Swal.fire('Validacion', 'Agrega al menos un traslado antes de procesar.', 'warning');
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
                Swal.fire('Exito', data.success || 'Traslados registrados correctamente.', 'success').then(() => {
                    window.location.href = data.redirect || '{{ route('movimientos.index') }}';
                });
            })
            .catch(mostrarErrores);
    });
});
</script>
@endsection