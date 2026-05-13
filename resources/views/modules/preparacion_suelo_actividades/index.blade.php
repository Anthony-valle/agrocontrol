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
                        <h5 class="card-title pb-0">Catálogo de Actividades</h5>

                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                            <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                    <small class="text-muted text-nowrap">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width: 280px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar actividad o código...">
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnAbrirModal">
                                <i class="fa-solid fa-circle-plus me-2"></i> Nueva Actividad
                            </button>
                        </div>

                        @if($actividadesEliminadas->isNotEmpty())
                            <div class="alert alert-warning d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                                <div>
                                    <strong>{{ $actividadesEliminadas->count() }}</strong> actividades eliminadas disponibles para recuperar.
                                </div>
                                <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#panelEliminadas" aria-expanded="false" aria-controls="panelEliminadas">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Ver eliminadas
                                </button>
                            </div>
                        @endif

                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaActividades">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Actividad Principal</th>
                                        <th>Desglose</th>
                                        <th>Unidad Medida</th>
                                        <th>Observaciones</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($actividades as $item)
                                        <tr>
                                            <td>{{ $item->codigo }}</td>
                                            <td>{{ $item->nombre }}</td>
                                            <td>{{ $item->actividad_secundaria }}</td>
                                            <td>{{ $item->unidad_medida }}</td>
                                            <td>{{ Str::limit($item->observaciones, 40) }}</td>
                                            <td>
                                                <span class="badge rounded-pill {{ $item->estado ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $item->estado ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <button class="btn btn-warning btn-sm btnEditarActividad" data-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('propietario'))
                                                    <button class="btn btn-danger btn-sm btnEliminarActividad" data-id="{{ $item->id }}">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($actividadesEliminadas->isNotEmpty())
                            <div class="collapse mt-3" id="panelEliminadas">
                                <div class="card border-warning shadow-sm mb-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-warning mb-3">Actividades eliminadas</h6>
                                        <div class="table-responsive border rounded">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead class="table-warning">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Actividad Principal</th>
                                                        <th>Desglose</th>
                                                        <th>Eliminada</th>
                                                        <th class="text-center">Acción</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($actividadesEliminadas as $item)
                                                        <tr>
                                                            <td>{{ $item->codigo }}</td>
                                                            <td>{{ $item->nombre }}</td>
                                                            <td>{{ $item->actividad_secundaria }}</td>
                                                            <td>{{ optional($item->deleted_at)->format('d/m/Y H:i') }}</td>
                                                            <td class="text-center">
                                                                <button class="btn btn-success btn-sm btnRestaurarActividad" data-id="{{ $item->id }}">
                                                                    <i class="fa-solid fa-rotate-left me-1"></i> Restaurar
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
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modalActividad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <div class="modal fade" id="modalActividadEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaActividades');
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById('inputBusqueda');
    const perPageSelect = document.getElementById('customPerPage');
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

    function mostrarFilas(filasVisibles) {
        filas.forEach(fila => fila.style.display = 'none');
        filasVisibles.forEach(fila => fila.style.display = '');
    }

    function filtrarTabla() {
        const texto = inputBusqueda.value.toLowerCase();
        const filtradas = filas.filter(fila =>
            Array.from(fila.cells).some(celda => celda.textContent.toLowerCase().includes(texto))
        );
        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value, 10)));
    }

    inputBusqueda.addEventListener('input', filtrarTabla);
    perPageSelect.addEventListener('change', filtrarTabla);
    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value, 10)));

    document.getElementById('btnAbrirModal').addEventListener('click', () => {
        fetch("{{ route('preparacion-suelo-actividades.create') }}")
            .then(res => res.text())
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('modalActividad')).show();
                bindAjaxForm('modalActividad', 'modalContent', 'Actividad registrada correctamente');
            });
    });

    document.addEventListener('click', event => {
        const editarBtn = event.target.closest('.btnEditarActividad');
        if (editarBtn) {
            fetch(`/preparacion-suelo-actividades/${editarBtn.dataset.id}/edit`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('modalContentEdit').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('modalActividadEdit')).show();
                    bindAjaxForm('modalActividadEdit', 'modalContentEdit', 'Actividad actualizada correctamente');
                });
        }

        const eliminarBtn = event.target.closest('.btnEliminarActividad');
        if (eliminarBtn) {
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
                if (result.isConfirmed) {
                    fetch(`/preparacion-suelo-actividades/${eliminarBtn.dataset.id}`, {
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
                            Swal.fire('Éxito', data.success || 'Actividad eliminada correctamente', 'success').then(() => location.reload());
                        })
                        .catch(mostrarErrores);
                }
            });
        }

        const restaurarBtn = event.target.closest('.btnRestaurarActividad');
        if (restaurarBtn) {
            Swal.fire({
                title: '¿Restaurar actividad?',
                text: 'La actividad volverá a estar disponible en el catálogo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(`/preparacion-suelo-actividades/${restaurarBtn.dataset.id}/restore`, {
                        method: 'POST',
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
                            Swal.fire('Éxito', data.success || 'Actividad restaurada correctamente', 'success').then(() => location.reload());
                        })
                        .catch(mostrarErrores);
                }
            });
        }
    });
});
</script>
@endsection