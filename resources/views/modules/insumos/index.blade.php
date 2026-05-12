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
                        <h5 class="card-title pb-0">Catálogo de Insumos</h5>

                        <!-- Controles: cantidad de registros + buscador + botones -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                            <!-- Buscador + select registros -->
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
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar insumo, código o bodega...">
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnAbrirModal">
                                    <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Insumo
                                </button>
                                <button type="button" class="btn btn-success btn-sm shadow-sm" id="btnAbrirModalImportar">
                                    <i class="fa-solid fa-file-excel me-2"></i> Importar Excel
                                </button>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaInsumos">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Nombre Producto</th>
                                        <th>Ingrediente Activo</th>
                                        <th>Categoría</th>
                                        <th>U.M</th>
                                        <th>Stock Mínimo</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($insumos as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->codigo }}</td>
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->ingrediente_activo_resuelto ?? '-' }}</td>
                                        <td>{{ $item->categoria_nombre_resuelto ?? 'Sin categoría' }}</td>
                                        <td>{{ $item->unidad_medida }}</td>
                                        <td>{{ $item->stock_minimo_resuelto ?? 0 }}</td>
                                        <td>{{ ($item->estado_resuelto ?? true) ? 'Activo' : 'Inactivo' }}</td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm btnEditarInsumos" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btnEliminarInsumos" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($insumos->isEmpty())
                            <div class="text-center mt-3">No hay insumos registrados.</div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- MODALES -->
    <div class="modal fade" id="modalInsumos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <div class="modal fade" id="modalInsumosEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>

    <div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentImportar"></div>
        </div>
    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tablaInsumos");
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

        if (error && error.summary_html) {
            Swal.fire({
                title: 'No se pudo completar la importación',
                html: `<p class="mb-2">${error.message || 'Revisa el detalle de filas y corrige el archivo.'}</p>${error.summary_html}`,
                icon: 'error'
            });
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
                    if (data.summary_html) {
                        Swal.fire({
                            title: 'Importación completada',
                            html: `<p class="mb-2">${data.success || successMessage}</p>${data.summary_html}`,
                            icon: 'success'
                        }).then(() => location.reload());
                        return;
                    }

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
        fetch("{{ route('insumos.create') }}")
            .then(res => res.text())
            .then(html => {
                document.getElementById("modalContent").innerHTML = html;
                new bootstrap.Modal(document.getElementById("modalInsumos")).show();
                bindAjaxForm('modalInsumos', 'modalContent', 'Insumo creado correctamente.');
            })
            .catch(err => console.error(err));
    });

    // MODAL IMPORTAR
    document.getElementById('btnAbrirModalImportar').addEventListener('click', function() {
        fetch("{{ route('insumos.importar') }}")
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalContentImportar').innerHTML = html;
                new bootstrap.Modal(document.getElementById('modalImportar')).show();
                bindAjaxForm('modalImportar', 'modalContentImportar', 'Insumos importados correctamente.');
            })
            .catch(error => console.error(error));
    });

    // MODAL EDITAR Y ELIMINAR
    document.addEventListener('click', function (e) {
        // Editar
        if(e.target.closest('.btnEditarInsumos')){
            let id = e.target.closest('.btnEditarInsumos').dataset.id;
            fetch(`/insumos/${id}/edit`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('modalContentEdit').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('modalInsumosEdit')).show();
                    bindAjaxForm('modalInsumosEdit', 'modalContentEdit', 'Insumo actualizado correctamente.');
                });
        }

        // Eliminar
        if(e.target.closest('.btnEliminarInsumos')){
            let id = e.target.closest('.btnEliminarInsumos').dataset.id;
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
                    fetch(`/insumos/${id}`, {
                        method:'DELETE',
                        credentials: 'same-origin',
                        headers: {
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
                        Swal.fire('Éxito', data.success || 'Insumo eliminado correctamente.', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }
    });
});
</script>
@endsection