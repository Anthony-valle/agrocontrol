@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <style>
        #tablaCultivos {
            min-width: 1750px !important;
        }

        #tablaCultivos thead th,
        #tablaCultivos tbody td {
            white-space: nowrap;
        }
    </style>

    <div class="pagetitle">
        <h1>{{ $titulo }}</h1> 
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title pb-0">Configuración de Cultivo</h5>

                        @if(session('import_summary_html'))
                            <div class="alert alert-info mt-3 mb-2">
                                {!! session('import_summary_html') !!}
                            </div>
                        @endif

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
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if(auth()->user()?->canManageMassImports())
                                <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportarCultivo">
                                    <i class="fa-solid fa-file-import me-2"></i> Carga Masiva
                                </button>
                                @endif
                                <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                    <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Cultivo
                                </button>
                            </div>
                        </div>

                        <!-- Tabla responsive -->
                        <div class="table-responsive border rounded">
                            <table class="table table-hover table-sm align-middle mb-0" id="tablaCultivos" style="min-width:1750px;">
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
                                            @if($item->estado !== 'Cerrado' && auth()->user()?->hasAnyRole(['admin', 'administrador']))
                                            <button type="button" class="btn btn-secondary btn-sm btnCerrarCultivo me-1" data-id="{{ $item->id }}" title="Cerrar cultivo">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                            @elseif(auth()->user()?->hasAnyRole(['admin', 'administrador']))
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

    <div class="modal fade" id="modalImportarCultivo" tabindex="-1" aria-labelledby="modalImportarCultivoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title" id="modalImportarCultivoLabel">
                        <i class="fa-solid fa-file-import me-2"></i> Carga masiva de cultivos cerrados e historial
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form action="{{ route('cultivo.importar') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body px-4 py-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                            <p class="fw-semibold mb-0">Plantilla Excel para cultivos cerrados con consumo historico opcional:</p>
                            <a href="{{ route('cultivo.importar.template') }}" class="btn btn-outline-success btn-sm">
                                <i class="fa-solid fa-file-arrow-down me-1"></i> Descargar plantilla
                            </a>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold mb-1">Archivo Excel o CSV</label>
                                <input type="file" class="form-control" name="archivo_excel" accept=".xlsx,.xls,.csv" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>codigo</th>
                                        <th>nombre</th>
                                        <th>lote_id</th>
                                        <th>lote_nombre</th>
                                        <th>variedad</th>
                                        <th>ciclo</th>
                                        <th>fecha_siembra</th>
                                        <th>duracion_ciclo</th>
                                        <th>hectareas</th>
                                        <th>cosecha_estimada</th>
                                        <th>unidad_medida</th>
                                        <th>estado</th>
                                        <th>observaciones</th>
                                        <th>fecha_consumo</th>
                                        <th>aplicar_consumo_real_bodega</th>
                                        <th>insumo_codigo</th>
                                        <th>insumo_nombre</th>
                                        <th>categoria_consumo</th>
                                        <th>descripcion_consumo</th>
                                        <th>cantidad_por_ha</th>
                                        <th>unidad_consumo</th>
                                        <th>costo_unitario_consumo</th>
                                        <th>bodega_id</th>
                                        <th>bodega_nombre</th>
                                        <th>lote_consumo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>CUL-HIST-001</td>
                                        <td>Maiz Historico</td>
                                        <td>1</td>
                                        <td></td>
                                        <td>Hibrido A</td>
                                        <td>Primera</td>
                                        <td>2025-01-15</td>
                                        <td>130</td>
                                        <td>2.500</td>
                                        <td>4500.000</td>
                                        <td>kg</td>
                                        <td>Cerrado</td>
                                        <td>Cultivo cargado desde historial</td>
                                        <td>2025-02-10</td>
                                        <td>NO</td>
                                        <td>INS-001</td>
                                        <td>Urea 46%</td>
                                        <td>Fertilizante</td>
                                        <td>Urea aplicada antes del sistema</td>
                                        <td>4.250</td>
                                        <td>kg</td>
                                        <td>590.125</td>
                                        <td>2</td>
                                        <td>Bodega Central</td>
                                        <td>LOT-2025-001</td>
                                    </tr>
                                    <tr>
                                        <td>CUL-HIST-002</td>
                                        <td>Pitahaya Historica</td>
                                        <td></td>
                                        <td>Lote Central</td>
                                        <td>Roja</td>
                                        <td>Ciclo 1</td>
                                        <td>2024-05-20</td>
                                        <td>365</td>
                                        <td>1.250</td>
                                        <td>3200.500</td>
                                        <td>kg</td>
                                        <td>Cerrado</td>
                                        <td>Consumo si debe bajar inventario</td>
                                        <td>2024-06-15</td>
                                        <td>SI</td>
                                        <td>INS-014</td>
                                        <td>Sulfato de Potasio</td>
                                        <td>Fertilizante</td>
                                        <td>Aplicacion real tomada de bodega</td>
                                        <td>2.750</td>
                                        <td>kg</td>
                                        <td>837.990</td>
                                        <td>3</td>
                                        <td>Bodega Insumos</td>
                                        <td>LOT-PIT-014</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mb-0 border-0 rounded-3">
                            <small>
                                Puedes identificar el lote con <b>lote_id</b> o con <b>lote_nombre</b>.
                                <br>
                                Las columnas obligatorias son <b>codigo</b>, <b>nombre</b>, <b>variedad</b>, <b>ciclo</b>, <b>fecha_siembra</b>, <b>duracion_ciclo</b> y <b>unidad_medida</b>.
                                <br>
                                <b>fecha_cosecha</b> no va en el archivo: el sistema la calcula automáticamente con fecha_siembra + duracion_ciclo.
                                <br>
                                <b>hectareas</b> y <b>cosecha_estimada</b> aceptan 3 decimales.
                                <br>
                                Usa <b>estado = Cerrado</b> para cargar cultivos historicos ya finalizados.
                                <br>
                                Si llenas <b>fecha_consumo</b> y <b>cantidad_por_ha</b>, el sistema registra un consumo historico para ese cultivo.
                                <br>
                                Esa cantidad se toma como valor para <b>1 HA</b> y el sistema la multiplica automaticamente por las <b>hectareas del cultivo</b> creado o encontrado.
                                <br>
                                Si <b>aplicar_consumo_real_bodega = SI</b>, entonces valida <b>insumo</b>, <b>bodega/almacen</b> y <b>lote</b> y descuenta stock real de inventario.
                                <br>
                                Si <b>aplicar_consumo_real_bodega = NO</b>, guarda el consumo historico sin tocar inventario, ideal para consumos ocurridos antes de usar el sistema.
                                <br>
                                <b>bodega_id</b> o <b>bodega_nombre</b> y <b>lote_consumo</b> quedan en el detalle del consumo para trazabilidad.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-upload me-1"></i> Importar cultivos
                        </button>
                    </div>
                </form>
            </div>
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

    async function cargarContenidoModal(url, containerId, modalId, onLoaded = null) {
        const response = await fetch(buildFreshUrl(url), {
            cache: 'no-store',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache',
                'Accept': 'text/html'
            }
        });

        const html = await response.text();

        if (!response.ok) {
            throw new Error(response.status === 403
                ? 'No tienes permiso para realizar esta acción.'
                : 'No se pudo cargar la ventana solicitada.');
        }

        document.getElementById(containerId).innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById(modalId));
        modal.show();

        if (typeof onLoaded === 'function') {
            onLoaded();
        }
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
        cargarContenidoModal("{{ route('cultivo.create') }}", 'modalContent', 'modalCultivo', () => {
            bindAjaxForm('modalCultivo', 'modalContent', 'Cultivo registrado correctamente');
        }).catch(error => {
            Swal.fire('Acceso restringido', error.message, 'warning');
        });
    });

    // EDITAR
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnEditarCultivo");
        if(btn){
            const id = btn.dataset.id;
            cargarContenidoModal(`/cultivo/${id}/edit`, 'modalContentEdit', 'modalCultivoEdit', () => {
                bindAjaxForm('modalCultivoEdit', 'modalContentEdit', 'Cultivo actualizado correctamente');
            }).catch(error => {
                Swal.fire('Acceso restringido', error.message, 'warning');
            });
        }
    });

    // VER DETALLE
    document.addEventListener("click", e => {
        const btn = e.target.closest(".btnVerCultivo");
        if(btn){
            const id = btn.dataset.id;
            cargarContenidoModal(`/cultivo/${id}`, 'modalContentShow', 'modalCultivoShow').catch(error => {
                Swal.fire('Acceso restringido', error.message, 'warning');
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