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
                        <h5 class="card-title pb-0">Catálogo de Mano de Obra</h5>
                        
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                    <small class="text-muted text-nowrap">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar labor o código...">
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnAbrirModal">
                                <i class="fa-solid fa-circle-plus me-2"></i> Nueva Labor
                            </button>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaLabores">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código SAP</th>
                                        <th>Nombre Actividad</th>
                                        <th>Desglose</th>
                                        <th>Unidad Medida</th>
                                        <th>Costo Unitario</th>
                                        <th>Observaciones</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labores as $item)
                                    <tr>
                                        <td>{{ $item->codigo }}</td>
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->actividad_secundaria }}</td>
                                        <td>{{ $item->unidad_medida }}</td>
                                        <td>{{ agro_number($item->costo_unitario, 2) }}</td>
                                        <td>{{ Str::limit($item->observaciones, 30) }}</td>
                                        <td>
                                            <span class="badge {{ $item->estado ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item->estado ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm btnEditarLabor" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btnEliminarLabor" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-trash"></i>
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

    <!-- MODALES -->
    <div class="modal fade" id="modalLabor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <div class="modal fade" id="modalLaborEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaLabores");
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
            Array.from(f.cells).some(c => c.textContent.toLowerCase().includes(texto))
        );
        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value)));
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", filtrarTabla);
    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value)));

    // MODAL CREAR
    document.getElementById("btnAbrirModal").addEventListener("click", () => {
        fetch("{{ route('labores.create') }}")
            .then(res => res.text())
            .then(html => {
                document.getElementById("modalContent").innerHTML = html;
                new bootstrap.Modal(document.getElementById("modalLabor")).show();
                bindAjaxForm('modalLabor', 'modalContent', 'Labor registrada correctamente');
            });
    });

    // MODAL EDITAR
    document.addEventListener("click", e => {
        const editarBtn = e.target.closest(".btnEditarLabor");
        if (editarBtn) {
            let id = editarBtn.dataset.id;
            fetch(`/labores/${id}/edit`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById("modalContentEdit").innerHTML = html;
                    new bootstrap.Modal(document.getElementById("modalLaborEdit")).show();
                    bindAjaxForm('modalLaborEdit', 'modalContentEdit', 'Labor actualizada correctamente');
                });
        }

        // ELIMINAR
        const eliminarBtn = e.target.closest(".btnEliminarLabor");
        if (eliminarBtn) {
            let id = eliminarBtn.dataset.id;
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
                if(result.isConfirmed) {
                    fetch(`/labores/${id}`, {
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
                        Swal.fire('Éxito', data.success || 'Labor eliminada correctamente', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }
    });

});
</script>
@endsection