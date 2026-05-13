@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

<div class="card shadow-sm border-0">
    <!-- HEADER -->
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="fw-bold mb-0 fw-bold">
            <i class="fa-solid fa-plus-square me-2 text-success"></i>
            Registrar Entrada de Insumos
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('movimientos.entrada.importar.template') }}" class="btn btn-outline-success btn-sm shadow-sm">
                <i class="fa-solid fa-download me-2"></i> Descargar Plantilla
            </a>
            <button type="button" class="btn btn-success btn-sm shadow-sm" data-open-import-excel="true">
                <i class="fa-solid fa-file-excel me-2"></i> Carga Masiva Inicial (Excel)
            </button>
        </div>
    </div>

    <div class="card-body">
        <form id="entradaForm" action="{{ route('movimientos.entrada.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row g-4 mt-1">
                <!-- COLUMNA IZQUIERDA: CONFIGURACIÓN -->
                <div class="col-md-5 d-flex flex-column">
                    <label class="form-label fw-bold text-primary">Configuración de Entrada</label>
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">1. Seleccione Insumo</label>
                            <select name="insumo_id_tmp" id="insumo_id" class="form-select shadow-sm">
                                <option value="">Seleccione un insumo</option>
                                @foreach($insumos as $insumo)
                                    @php
                                        $costoInventario = $insumo->inventarioBodegas->avg('costo_promedio') ?? 0;
                                        $stockTotal = $insumo->inventarioBodegas->sum('stock_actual') ?? 0;
                                    @endphp
                                    <option value="{{ $insumo->id }}"
                                        data-codigo="{{ $insumo->codigo }}"
                                        data-ingrediente="{{ $insumo->ingrediente_activo }}"
                                        data-stock="{{ $stockTotal }}"
                                        data-unidad="{{ $insumo->unidad_medida }}"
                                        data-precio="{{ agro_number($costoInventario,2,'.','') }}">
                                        {{ $insumo->codigo }} - {{ $insumo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">2. Bodega Destino</label>
                            <select name="bodega_id_tmp" id="bodega_id" class="form-select shadow-sm">
                                <option value="">Seleccione una bodega</option>
                                @foreach($bodegas as $bodega)
                                    <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Cantidad</label>
                                <input type="number" step="0.01" id="cantidad" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Precio Compra</label>
                                <input type="number" step="0.01" id="precio_unitario" class="form-control" placeholder="L 0.00">
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-bold small">Proveedor</label>
                            <input type="text" id="proveedor" class="form-control" placeholder="Nombre del proveedor">
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: DETALLES TÉCNICOS -->
                <div class="col-md-7 d-flex flex-column">
                    <label class="form-label fw-bold text-primary">Detalles Técnicos y Lote</label>
                    <div class="p-4 border rounded bg-light shadow-sm h-100">
                        
                        <!-- Visor de información técnica -->
                        <div class="p-3 border rounded mb-3 bg-white shadow-sm">
                            <div class="row text-center text-md-start">
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block uppercase small fw-bold">Código</small>
                                    <span id="datoCodigo" class="fw-bold text-dark">-</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block uppercase small fw-bold">Unidad</small>
                                    <span id="datoUnidad" class="fw-bold text-dark">-</span>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <small class="text-muted d-block uppercase small fw-bold">Stock Actual</small>
                                    <span id="datoStock" class="badge bg-dark">0</span>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block uppercase small fw-bold">Ingrediente Activo</small>
                                    <span id="datoIngrediente" class="fw-bold text-dark">-</span>
                                </div>
                            </div>
                            <hr class="my-3">
                            <div class="row text-center bg-light rounded py-2 g-0">
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block small">Lote Digitado</small>
                                    <span id="datoLote" class="fw-bold text-primary small">-</span>
                                </div>
                                <div class="col-4 border-end">
                                    <small class="text-muted d-block small">F. Fab</small>
                                    <span id="datoFabrica" class="fw-bold text-primary small">-</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block small">F. Vence</small>
                                    <span id="datoVence" class="fw-bold text-primary small">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Inputs de Lote y Archivo -->
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">N° Lote</label>
                                <input type="text" id="numero_lote" class="form-control" placeholder="LOT-000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">F. Fab</label>
                                <input type="date" id="fecha_fabricacion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">F. Vence</label>
                                <input type="date" id="fecha_vencimiento" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold small">Factura (Digital)</label>
                                <input type="file" id="factura" class="form-control">
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="button" class="btn btn-success w-100 fw-bold shadow-sm" id="btnAgregar" style="padding: 0.5rem;">
                                    <i class="fa fa-plus me-1"></i> AGREGAR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA DE LISTADO TEMPORAL -->
            <div class="mt-5 table-responsive border rounded shadow-sm bg-white">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 text-start">Insumo</th>
                            <th>Bodega</th>
                            <th>Cant.</th>
                            <th>Precio</th>
                            <th>Lote</th>
                            <th>Vencimiento</th>
                            <th>Proveedor</th>
                            <th>Factura</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTabla">
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>

            <!-- BOTONES FINALES -->
            <div class="mt-4 d-flex gap-2 border-top pt-4 justify-content-end">
                <a href="{{ route('movimientos.entradas.index') }}" class="btn btn-light border px-4 fw-bold">
                    <i class="fa fa-arrow-left me-1"></i> Regresar
                </a>
                <a href="{{ route('movimientos.entrada.importar.template') }}" class="btn btn-outline-success px-4 fw-bold">
                    <i class="fa-solid fa-download me-2"></i> Plantilla Excel
                </a>
                <button type="button" class="btn btn-success px-4 shadow fw-bold" data-open-import-excel="true">
                    <i class="fa-solid fa-file-excel me-2"></i> Importar Excel
                </button>
                <button type="submit" class="btn btn-primary px-5 shadow fw-bold">
                    <i class="fa fa-save me-2"></i> Guardar Movimiento
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalImportarEntrada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="modalContentImportarEntrada">
            @include('modules.movimientos.entradas.importar_excel')
        </div>
    </div>
</div>

</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
span.badge { min-width: 40px; display: inline-block; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const form = document.getElementById('entradaForm');
    const insumoSelect = document.getElementById('insumo_id');
    const bodegaSelect = document.getElementById('bodega_id');
    const cantidadInput = document.getElementById('cantidad');
    const precioInput = document.getElementById('precio_unitario');
    const loteInput = document.getElementById('numero_lote');
    const fechaFabInput = document.getElementById('fecha_fabricacion');
    const fechaVenInput = document.getElementById('fecha_vencimiento');
    const proveedorInput = document.getElementById('proveedor');
    const facturaInput = document.getElementById('factura');
    const btnAgregar = document.getElementById('btnAgregar');
    const cuerpoTabla = document.getElementById('cuerpoTabla');
    const botonesImportacionInicial = document.querySelectorAll('[data-open-import-excel="true"]');

    const dCodigo = document.getElementById('datoCodigo');
    const dUnidad = document.getElementById('datoUnidad');
    const dStock = document.getElementById('datoStock');
    const dIngrediente = document.getElementById('datoIngrediente');
    const dLote = document.getElementById('datoLote');
    const dFab = document.getElementById('datoFabrica');
    const dVen = document.getElementById('datoVence');

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

        if (error && error.summary_html) {
            Swal.fire({
                title: 'No se pudo completar la carga masiva',
                html: `<p class="mb-2">${error?.message || 'Revisa el detalle de filas y corrige el archivo.'}</p>${error.summary_html}`,
                icon: 'error',
            });
            return;
        }

        Swal.fire('Error', error?.message || 'No se pudo registrar la entrada.', 'error');
    }

    function bindImportForm() {
        const modalElement = document.getElementById('modalImportarEntrada');
        const container = document.getElementById('modalContentImportarEntrada');
        const formImport = container.querySelector('form');

        if (!formImport || formImport.dataset.ajaxBound === 'true') {
            return;
        }

        formImport.dataset.ajaxBound = 'true';
        formImport.addEventListener('submit', function (e) {
            e.preventDefault();

            if (formImport.dataset.loading === 'true') {
                return;
            }

            formImport.dataset.loading = 'true';
            const submitBtn = formImport.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            Swal.fire({
                title: 'Importando archivo',
                html: 'Procesando la carga masiva de insumos. Esto puede tardar unos segundos...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(formImport);

            fetch(formImport.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    const rawText = await response.text();
                    const data = (() => {
                        try {
                            return rawText ? JSON.parse(rawText) : {};
                        } catch (e) {
                            return {
                                message: rawText || `Respuesta inesperada del servidor (${response.status})`,
                            };
                        }
                    })();

                    if (!response.ok) throw data;
                    return data;
                })
                .then((data) => {
                    bootstrap.Modal.getInstance(modalElement)?.hide();
                    Swal.close();

                    const defaultHtml = data.queued
                        ? '<p class="mb-0">La importacion fue enviada a la cola y se procesara en segundo plano.</p>'
                        : '<p class="mb-0">La importacion finalizo correctamente.</p>';
                    const successPrefix = data.success ? `<p class="mb-2">${data.success}</p>` : '';

                    Swal.fire({
                        title: data.title || (data.queued ? 'Carga masiva en cola' : 'Carga masiva completada'),
                        html: `${successPrefix}${data.summary_html || defaultHtml}`,
                        icon: 'success',
                    }).then(() => {
                        window.location.href = data.redirect || '{{ route('movimientos.index') }}';
                    });
                })
                .catch((error) => {
                    formImport.dataset.loading = 'false';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }

                    Swal.close();

                    if (error instanceof TypeError && error.message === 'Failed to fetch') {
                        mostrarErrores({
                            message: 'El servidor corto la respuesta de la carga masiva. Ya se reforzo el importador, pero si vuelve a pasar ahora mostrara el siguiente error real cuando el backend responda.',
                        });
                        return;
                    }

                    mostrarErrores(error);
                });
        });
    }

    function resetVisor() {
        dCodigo.textContent = '-';
        dUnidad.textContent = '-';
        dStock.textContent = '0';
        dIngrediente.textContent = '-';
        precioInput.value = '';
    }

    function limpiarCampos() {
        insumoSelect.value = '';
        bodegaSelect.value = '';
        cantidadInput.value = '';
        precioInput.value = '';
        loteInput.value = '';
        fechaFabInput.value = '';
        fechaVenInput.value = '';
        proveedorInput.value = '';
        facturaInput.value = '';
        resetVisor();
        dLote.textContent = '-';
        dFab.textContent = '-';
        dVen.textContent = '-';
    }

    insumoSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if(opt && opt.value !== "") {
            dCodigo.textContent = opt.dataset.codigo || '-';
            dUnidad.textContent = opt.dataset.unidad || '-';
            dStock.textContent = opt.dataset.stock || '0';
            dIngrediente.textContent = opt.dataset.ingrediente || '-';
            precioInput.value = opt.dataset.precio || '';
        } else resetVisor();
    });

    [loteInput, fechaFabInput, fechaVenInput].forEach(input => {
        input.addEventListener('input', () => {
            dLote.textContent = loteInput.value || '-';
            dFab.textContent = fechaFabInput.value || '-';
            dVen.textContent = fechaVenInput.value || '-';
        });
    });

    btnAgregar.addEventListener('click', function() {
        if (!insumoSelect.value || !bodegaSelect.value || !cantidadInput.value || parseFloat(cantidadInput.value)<=0 || !precioInput.value) {
            Swal.fire('Validación', 'Faltan campos obligatorios o la cantidad es inválida.', 'warning');
            return;
        }

        const tr = document.createElement('tr');

        // Crear DataTransfer para el archivo
        const dataTransfer = new DataTransfer();
        if(facturaInput.files[0]) dataTransfer.items.add(facturaInput.files[0]);

        tr.innerHTML = `
            <td class="ps-3 text-start fw-bold">
                ${insumoSelect.selectedOptions[0].text}
                <input type="hidden" name="insumo_ids[]" value="${insumoSelect.value}">
            </td>
            <td>
                ${bodegaSelect.selectedOptions[0].text}
                <input type="hidden" name="bodega_ids[]" value="${bodegaSelect.value}">
            </td>
            <td>
                ${cantidadInput.value}
                <input type="hidden" name="cantidades[]" value="${cantidadInput.value}">
            </td>
            <td>
                L. ${parseFloat(precioInput.value).toFixed(2)}
                <input type="hidden" name="precios[]" value="${precioInput.value}">
            </td>
            <td>
                ${loteInput.value || 'N/A'}
                <input type="hidden" name="lotes[]" value="${loteInput.value}">
            </td>
            <td>
                ${fechaVenInput.value || 'N/A'}
                <input type="hidden" name="fechas_vencimiento[]" value="${fechaVenInput.value}">
            </td>
            <td>
                ${proveedorInput.value || '-'}
                <input type="hidden" name="proveedores[]" value="${proveedorInput.value}">
            </td>
            <td>
                ${facturaInput.files[0] ? facturaInput.files[0].name : '-'}
                <input type="file" name="archivos[]" class="d-none archivoFila">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger btnEliminar">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
            <input type="hidden" name="fechas_fabricacion[]" value="${fechaFabInput.value}">
        `;

        // Asignar el archivo al input oculto
        if(facturaInput.files[0]) {
            tr.querySelector('.archivoFila').files = dataTransfer.files;
        }

        cuerpoTabla.appendChild(tr);
        limpiarCampos();
    });

    // Eliminar fila
    cuerpoTabla.addEventListener('click', function(e) {
        if(e.target.closest('.btnEliminar')) e.target.closest('tr').remove();
    });

    if (botonesImportacionInicial.length) {
        botonesImportacionInicial.forEach((btn) => {
            btn.addEventListener('click', function () {
                const bodegasActivas = Array.from(bodegaSelect.options).filter((opt) => opt.value !== '').length;
                if (bodegasActivas === 0) {
                    Swal.fire({
                        title: 'Falta almacén',
                        text: 'Primero debes crear un almacen (bodega) para poder hacer carga masiva.',
                        icon: 'warning',
                        confirmButtonText: 'Entendido',
                    });
                    return;
                }

                const modalElement = document.getElementById('modalImportarEntrada');
                bindImportForm();
                new bootstrap.Modal(modalElement).show();
            });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!cuerpoTabla.children.length) {
            Swal.fire('Validación', 'Agrega al menos un insumo antes de guardar.', 'warning');
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
                Swal.fire('Éxito', data.success || 'Entradas registradas correctamente.', 'success').then(() => {
                    window.location.href = data.redirect || '{{ route('movimientos.index') }}';
                });
            })
            .catch(mostrarErrores);
    });
});
</script>
@endsection