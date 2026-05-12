@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title pb-0">Configuración de la Sucursal</h5>

                        <!-- CONTROLES -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                            <!-- Buscador + registros -->
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width:auto;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                    <small class="text-muted">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width:250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar sucursal...">
                                </div>
                            </div>

                            <!-- BOTÓN -->
                            <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                <i class="fa fa-plus me-1"></i> Nueva Sucursal
                            </button>
                        </div>

                        <!-- TABLA -->
                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaSucursal">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Dirección</th>
                                        <th>Correo</th>
                                        <th>Responsable</th>
                                        <th>Empresa</th>
                                        <th>Estado</th>
                                        <th>Creado por</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sucursal as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->direccion }}</td>
                                        <td>{{ $item->email }}</td>
                                        <td>{{ $item->responsable }}</td>
                                        <td>{{ $item->empresa->nombre }}</td>
                                        <td>
                                            <span class="badge {{ $item->estado ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item->estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $item->creador->usuario ?? 'Sistema' }}</td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm btnEditarSucursal" data-id="{{ $item->id }}">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btnEliminarSucursal" data-id="{{ $item->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($sucursal->isEmpty())
                            <div class="text-center mt-3">No hay sucursales registradas.</div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- MODALES -->
    <div class="modal fade" id="modalSucursal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <div class="modal fade" id="modalSucursalEdit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaSucursal");
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
                    bootstrap.Modal.getInstance(document.getElementById(modalId))?.hide();
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
            Array.from(f.cells).some(c => 
                c.textContent.toLowerCase().includes(texto)
            )
        );

        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value)));
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", filtrarTabla);

    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value)));

    // CREAR
    document.getElementById("btnAbrirModal").addEventListener("click", () => {
        fetch("{{ route('sucursal.create') }}")
        .then(res => res.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;
            new bootstrap.Modal(document.getElementById("modalSucursal")).show();
            bindAjaxForm('modalSucursal', 'modalContent', 'Sucursal registrada correctamente');
        });
    });

    // EDITAR Y ELIMINAR
    document.addEventListener("click", function (e) {

        // EDITAR
        if(e.target.closest('.btnEditarSucursal')){
            let id = e.target.closest('.btnEditarSucursal').dataset.id;

            fetch(`/sucursal/${id}/edit`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('modalContentEdit').innerHTML = html;
                new bootstrap.Modal(document.getElementById('modalSucursalEdit')).show();
                bindAjaxForm('modalSucursalEdit', 'modalContentEdit', 'Sucursal actualizada correctamente');
            });
        }

        // ELIMINAR
        if(e.target.closest('.btnEliminarSucursal')){
            let id = e.target.closest('.btnEliminarSucursal').dataset.id;

            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if(result.isConfirmed){
                    fetch(`/sucursal/${id}`, {
                        method: 'DELETE',
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
                        Swal.fire('Éxito', data.success || 'Sucursal eliminada correctamente', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }

    });

});
</script>

@endsection