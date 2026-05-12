@extends('layouts.main')

@section('titulo', $titulo ?? 'Cosechas')

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Cosechas</h1> 
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Gestión de Cosechas</h5>

                        <!-- CONTROLES -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="5">5</option>
                                        <option value="30" selected>30</option>
                                        <option value="50">50</option>
                                        <option value="150">150</option>
                                    </select>
                                    <small class="text-muted text-nowrap">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar cosecha...">
                                </div>
                            </div>

                            <!-- BOTÓN -->
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a href="{{ route('cosecha.facturadas.index') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Ventas facturadas
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                    <i class="fa fa-plus me-1"></i> Nueva Cosecha
                                </button>
                            </div>

                        </div>

                        <!-- TABLA -->
                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaCosecha">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cultivo</th>
                                        <th>Fecha</th>
                                        <th>Cantidad</th>
                                        <th>Disponible</th>
                                        <th>Unidad</th>
                                        <th>Precio</th>
                                        <th>Ingreso</th>
                                        <th>Facturado</th>
                                        <th>Estado Venta</th>
                                        <th>Observación</th>
                                        <th>Registrado por</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cosechas as $item)
                                    @php
                                        $cantidadNeta = (float) $item->cantidad_neta;
                                        $cantidadDisponible = (float) $item->cantidad_disponible;
                                        $cantidadVendida = max($cantidadNeta - $cantidadDisponible, 0);
                                        $porcentajeVendido = $cantidadNeta > 0 ? ($cantidadVendida / $cantidadNeta) * 100 : 0;

                                        if ($cantidadVendida <= 0) {
                                            $estadoVenta = 'Sin vender';
                                            $estadoClase = 'bg-secondary';
                                        } elseif ($cantidadDisponible <= 0.0001) {
                                            $estadoVenta = 'Vendida';
                                            $estadoClase = 'bg-success';
                                        } else {
                                            $estadoVenta = 'Parcial';
                                            $estadoClase = 'bg-warning text-dark';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->cultivo->nombre }}</td>
                                        <td>{{ $item->fecha_cosecha }}</td>
                                        <td>{{ agro_number($item->cantidad_neta, 2, '.', ',') }}</td>
                                        <td>{{ agro_number($item->cantidad_disponible, 2, '.', ',') }}</td>
                                        <td>{{ $item->unidad_medida }}</td>
                                        <td>{{ $item->precio_venta_unitario !== null ? agro_number($item->precio_venta_unitario, 2, '.', ',') . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $item->precio_venta_unitario !== null ? agro_number($item->cantidad_neta * $item->precio_venta_unitario, 2, '.', ',') . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $item->facturas_sum_total !== null ? agro_number($item->facturas_sum_total, 2, '.', ',') . ' Lps' : '0.00 Lps' }}</td>
                                        <td>
                                            <span class="badge {{ $estadoClase }}">{{ $estadoVenta }}</span>
                                            <div class="small text-muted mt-1">{{ agro_number($porcentajeVendido, 1, '.', ',') }}% vendido</div>
                                        </td>
                                        <td>{{ $item->observaciones ?? '-' }}</td>
                                        <td>{{ $item->usuario->usuario ?? 'Sistema' }}</td>
                                        <td class="text-nowrap">
                                            <button class="btn btn-warning btn-sm btnEditar" data-id="{{ $item->id }}">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btnEliminar" data-id="{{ $item->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<!-- MODALES -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="modalContent"></div>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="modalContentEdit"></div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaCosecha");
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function mostrarErrores(error) {
        if (error && error.errors) {
            let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            Object.values(error.errors).flat().forEach(msg => {
                mensajes += `<li>${msg}</li>`;
            });
            mensajes += '</ul>';
            Swal.fire({ title: 'Error de validación', html: mensajes, icon: 'error' });
            return;
        }

        Swal.fire('Error', error.message || 'No se pudo procesar la solicitud.', 'error');
    }

    function bindAjaxForm(modalId, contentId, successMessage) {
        const modalElement = document.getElementById(modalId);
        const form = document.getElementById(contentId).querySelector('form');
        if (!form || form.dataset.ajaxBound === 'true') return;

        form.dataset.ajaxBound = 'true';
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);
            formData.set('_token', csrfToken);
            const requestUrl = new URL(form.action, window.location.origin);
            const relativeAction = `${requestUrl.pathname}${requestUrl.search}`;

            fetch(relativeAction, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw data;
                    return data;
                })
                .then(data => {
                    bootstrap.Modal.getInstance(modalElement)?.hide();
                    Swal.fire('Éxito', data.success || successMessage, 'success').then(() => location.reload());
                })
                .catch(mostrarErrores);
        });
    }

    function mostrarFilas(filasVisibles){
        filas.forEach(f => f.style.display = "none");
        filasVisibles.forEach(f => f.style.display = "");
    }

    function filtrarTabla() {
        const texto = inputBusqueda.value.toLowerCase();
        const filtradas = filas.filter(f => 
            Array.from(f.cells).some(c => c.textContent.toLowerCase().includes(texto))
        );
        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value)));
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", filtrarTabla);
    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value)));

    function inicializarFormularioCosecha() {
        const selectCultivo = document.getElementById('cosecha_cultivo_id');
        const inputUnidad = document.getElementById('cosecha_unidad_medida');
        const cantidadBruta = document.getElementById('cosecha_cantidad_bruta');
        const descarte = document.getElementById('cosecha_descarte');
        const cantidadNeta = document.getElementById('cosecha_cantidad_neta');
        const cantidadDisponible = document.getElementById('cosecha_cantidad_disponible');

        function actualizarUnidadMedida() {
            if (!selectCultivo || !inputUnidad) return;
            const unidad = selectCultivo.options[selectCultivo.selectedIndex]?.dataset.unidad || '';
            inputUnidad.value = unidad;
        }

        function actualizarCantidades() {
            if (!cantidadBruta || !descarte || !cantidadNeta || !cantidadDisponible) return;
            const bruta = parseFloat(cantidadBruta.value) || 0;
            const desc = parseFloat(descarte.value) || 0;
            let neta = bruta - desc;
            if (neta < 0) neta = 0;
            cantidadNeta.value = neta.toFixed(2);
            cantidadDisponible.value = neta.toFixed(2);
        }

        if (selectCultivo) {
            selectCultivo.addEventListener('change', actualizarUnidadMedida);
            actualizarUnidadMedida();
        }

        if (cantidadBruta) cantidadBruta.addEventListener('input', actualizarCantidades);
        if (descarte) descarte.addEventListener('input', actualizarCantidades);
        actualizarCantidades();
    }

    // CREAR
    document.getElementById("btnAbrirModal").addEventListener("click", () => {
        fetch("{{ route('cosecha.create') }}")
        .then(res => res.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;
            inicializarFormularioCosecha();
            new bootstrap.Modal(document.getElementById("modalCrear")).show();
            bindAjaxForm('modalCrear', 'modalContent', 'Cosecha registrada correctamente');
        });
    });

    // EDITAR
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.btnEditar');
        if(!btn) return;

        fetch(`/cosecha/${btn.dataset.id}/edit`)
        .then(res => res.text())
        .then(html => {
            document.getElementById('modalContentEdit').innerHTML = html;
            inicializarFormularioCosecha();
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
            bindAjaxForm('modalEditar', 'modalContentEdit', 'Cosecha actualizada correctamente');
        });
    });

    // ELIMINAR
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.btnEliminar');
        if(!btn) return;

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if(result.isConfirmed){
                fetch(`/cosecha/${btn.dataset.id}`, {
                    method:'DELETE',
                    credentials: 'same-origin',
                    headers:{
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':'application/json'
                    }
                })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw data;
                    return data;
                })
                .then(data => {
                    Swal.fire('Éxito', data.success || 'Cosecha eliminada correctamente', 'success').then(() => location.reload());
                })
                .catch(mostrarErrores);
            }
        });
    });

});
</script>

@endsection