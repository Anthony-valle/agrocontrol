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
                        <h5 class="card-title pb-0">Configuración de Cultivo</h5>

                        <!-- Controles: cantidad de registros + buscador + botón nuevo -->
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
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar cultivo...">
                                </div>
                            </div>

                            <!-- Botón Nuevo -->
                            <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Cultivo
                            </button>
                        </div>

                        <!-- Tabla responsive -->
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-sm align-middle mb-0" id="tablaCultivos" style="min-width:1500px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Lote</th>
                                        <th>Variedad</th>
                                        <th>Ciclo</th>
                                        <th>Fecha Siembra</th>
                                        <th>Duración (días)</th>
                                        <th>Fecha Cosecha</th>
                                        <th>Ha Sembradas</th>
                                        <th>Cosecha Estimada</th>
                                        <th>U.M</th>
                                        <th>Estado</th>
                                        <th>Creado por</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cultivos as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td class="text-nowrap">{{ $item->codigo }}</td>
                                        <td>{{ $item->nombre }}</td>
                                        <td>{{ $item->lote->nombre ?? '-' }}</td>
                                        <td>{{ $item->variedad ?? '-' }}</td>
                                        <td>{{ $item->ciclo ?? '-' }}</td>
                                        <td>{{ $item->fecha_siembra ? \Carbon\Carbon::parse($item->fecha_siembra)->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $item->duracion_ciclo ?? '-' }}</td>
                                        <td>{{ $item->fecha_cosecha ? \Carbon\Carbon::parse($item->fecha_cosecha)->format('d/m/Y') : '-' }}</td>
                                        <td>{{ $item->hectareas ?? '-' }}</td>
                                        <td>{{ $item->cosecha_estimada ?? '-' }}</td>
                                        <td>{{ $item->unidad_medida ?? '-' }}</td>
                                        <td>
                                            <span class="badge {{ $item->estado == 'Activo' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item->estado }}
                                            </span>
                                        </td>
                                        <td>{{ $item->creador->usuario ?? 'Sistema' }}</td>
                                        <td class="text-center text-nowrap">
                                            <button type="button" class="btn btn-primary btn-sm btnVerCultivo me-1" data-id="{{ $item->id }}" title="Ver detalle">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <a href="{{ route('reporte.cultivo.final', $item->id) }}" class="btn btn-info btn-sm me-1" title="Ver reporte">
                                                <i class="fa-solid fa-chart-line"></i>
                                            </a>
                                            <a href="{{ route('reporte.cultivo.historial', $item->id) }}" class="btn btn-secondary btn-sm me-1" title="Historial de consumo">
                                                <i class="fa-solid fa-clock-rotate-left"></i>
                                            </a>
                                            @if($item->estado !== 'Cerrado')
                                            <button type="button" class="btn btn-secondary btn-sm btnCerrarCultivo me-1" data-id="{{ $item->id }}" title="Cerrar cultivo">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                            @elseif(auth()->user()?->hasAnyRole(['propietario', 'admin', 'programador']))
                                            <button type="button" class="btn btn-success btn-sm btnReactivarCultivo me-1" data-id="{{ $item->id }}" title="Reactivar cultivo">
                                                <i class="fa-solid fa-lock-open"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-warning btn-sm btnEditarCultivo me-1" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btnEliminarCultivo" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($cultivos->isEmpty())
                            <div class="text-center mt-3">No hay cultivos registrados.</div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- MODALES -->
    <div class="modal fade" id="modalCultivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <div class="modal fade" id="modalCultivoEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>

    <div class="modal fade" id="modalCultivoShow" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalContentShow"></div>
        </div>
    </div>

</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tablaCultivos");
    if(!tabla) return;

    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function buildFreshUrl(url) {
        const freshUrl = new URL(url, window.location.origin);
        freshUrl.searchParams.set('_ts', Date.now().toString());
        return `${freshUrl.pathname}${freshUrl.search}`;
    }

    function recargarSinCache() {
        window.location.assign(buildFreshUrl(window.location.href));
    }

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
        const container = document.getElementById(contentId);
        const form = container.querySelector('form');
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
                    Swal.fire('Éxito', data.success || successMessage, 'success').then(() => recargarSinCache());
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

    // CREAR
    document.getElementById("btnAbrirModal").addEventListener("click", () => {
        fetch(buildFreshUrl("{{ route('cultivo.create') }}"), {
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
            .then(res => res.text())
            .then(html => {
                document.getElementById("modalContent").innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById("modalCultivo"));
                modal.show();
                bindAjaxForm('modalCultivo', 'modalContent', 'Cultivo registrado correctamente');
            });
    });

    // EDITAR
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnEditarCultivo");
        if(btn){
            const id = btn.dataset.id;
            fetch(buildFreshUrl(`/cultivo/${id}/edit`), {
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
                .then(res => res.text())
                .then(html => {
                    document.getElementById("modalContentEdit").innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById("modalCultivoEdit"));
                    modal.show();
                    bindAjaxForm('modalCultivoEdit', 'modalContentEdit', 'Cultivo actualizado correctamente');
                });
        }
    });

    // VER DETALLE
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnVerCultivo");
        if(btn){
            const id = btn.dataset.id;
            fetch(buildFreshUrl(`/cultivo/${id}`), {
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                }
            })
                .then(res => res.text())
                .then(html => {
                    document.getElementById("modalContentShow").innerHTML = html;
                    const modal = new bootstrap.Modal(document.getElementById("modalCultivoShow"));
                    modal.show();
                });
        }
    });

    // CERRAR
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnCerrarCultivo");
        if(btn){
            const id = btn.dataset.id;
            Swal.fire({
                title: 'Cerrar cultivo',
                text: 'Una vez cerrado, no podrá registrar más consumos ni cosechas en este cultivo.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if(result.isConfirmed){
                    fetch(`/cultivo/${id}/cerrar`, {
                        method: 'PATCH',
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
                        Swal.fire('Éxito', data.success || data.info || 'Cultivo actualizado.', 'success').then(() => recargarSinCache());
                    })
                    .catch(mostrarErrores);
                }
            });
        }
    });

    // REACTIVAR
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnReactivarCultivo");
        if(btn){
            const id = btn.dataset.id;
            Swal.fire({
                title: 'Reactivar cultivo',
                text: 'El cultivo volverá a estar disponible para operaciones y reportería activa.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Sí, reactivar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if(result.isConfirmed){
                    fetch(`/cultivo/${id}/reactivar`, {
                        method: 'PATCH',
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
                        Swal.fire('Éxito', data.success || data.info || 'Cultivo reactivado.', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }
    });

    // ELIMINAR
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnEliminarCultivo");
        if(btn){
            const id = btn.dataset.id;
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
                    fetch(`/cultivo/${id}`, {
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
                        Swal.fire('Éxito', data.success || 'Cultivo eliminado correctamente', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }
    });
});
</script>
@endsection