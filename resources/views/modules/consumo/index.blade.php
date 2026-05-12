@extends('layouts.main')

@section('titulo', 'Consumos de Insumos')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Consumos de Insumos</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title pb-0">Gestión de Consumos Registrados</h5>

                <!-- CONTROLES -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                    <div class="d-flex align-items-center gap-3">
                        <select id="customPerPage" class="form-select form-select-sm" style="width:auto;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        <small class="text-muted">registros</small>

                        <div class="input-group input-group-sm" style="max-width:250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa fa-search text-muted"></i>
                            </span>
                            <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar consumo...">
                        </div>
                    </div>

                    <!-- BOTÓN CREAR -->
                    <a href="{{ route('consumo.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-circle-plus me-1"></i> Registrar Nuevo Consumo
                    </a>
                </div>

                <!-- TABLA -->
                <div class="table-responsive border rounded">
                    <table class="table table-hover table-sm align-middle mb-0 w-100" id="tablaConsumos">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cultivo</th>
                                <th>Categoría aplicada</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                                <th>Descripción</th>
                                <th>Fecha</th>
                                <th>Registrado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consumos as $index => $c)
                                @php
                                    $categoriasAplicadas = $c->detalles
                                        ->pluck('categoria')
                                        ->map(fn ($categoria) => trim((string) $categoria))
                                        ->filter()
                                        ->unique()
                                        ->values();

                                    $categoriaVisible = $categoriasAplicadas->first() ?: '-';
                                @endphp
                                <tr>
                                    <td>{{ $c->id }}</td>
                                    <td>{{ $c->cultivo->nombre ?? '-' }}</td>
                                    <td title="{{ $categoriasAplicadas->isNotEmpty() ? $categoriasAplicadas->join(', ') : '-' }}">{{ $categoriaVisible }}</td>
                                    <td>{{ $c->detalles->sum('cantidad') }}</td>
                                    <td>{{ agro_number($c->total, 2) }}</td>
                                    <td>{{ $c->detalles->first()?->descripcion ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($c->fecha_consumo)->format('d/m/Y') }}</td>
                                    <td>{{ $c->creador->usuario ?? 'Sistema' }}</td>

                                    <td class="text-center text-nowrap">
                                        <!-- VER (CORREGIDO) -->
                                        <button class="btn btn-info btn-sm btnVerConsumo" data-id="{{ $c->id }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        @if(
                                            $c->estado_normalizado === 'PENDIENTE'
                                            || ($c->estado_normalizado === 'FINALIZADO' && auth()->user()?->isSuperUser())
                                        )
                                            <a href="{{ route('consumo.edit', $c->id) }}" class="btn btn-warning btn-sm" title="Editar consumo">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>
                                            </a>
                                        @endif

                                        <!-- ANULAR (solo Admin/Supervisor/Propietario) -->
                                        @if(
                                            auth()->user()?->hasAnyRole(['admin', 'supervisor', 'propietario'])
                                            && $c->estado_normalizado === 'PENDIENTE'
                                        )
                                            <button class="btn btn-danger btn-sm btnEliminarConsumo" data-id="{{ $c->id }}" title="Anular consumo">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3" id="consumoPaginacionWrap">
                    <small class="text-muted" id="consumoPaginacionInfo"></small>
                    <nav aria-label="Paginacion de consumos">
                        <ul class="pagination pagination-sm mb-0" id="consumoPaginacion"></ul>
                    </nav>
                </div>

                @if($consumos->isEmpty())
                    <div class="text-center mt-3">No hay consumos registrados.</div>
                @endif

            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const tabla = document.getElementById("tablaConsumos");
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");
    const paginacionInfo = document.getElementById("consumoPaginacionInfo");
    const paginacion = document.getElementById("consumoPaginacion");
    const paginacionWrap = document.getElementById("consumoPaginacionWrap");
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const state = {
        page: 1,
        filteredRows: [...filas],
    };

    function mostrarErrores(error) {
        Swal.fire('Error', error?.message || 'No se pudo procesar la solicitud.', 'error');
    }

    function mostrarFilas(filasVisibles){
        filas.forEach(f => f.style.display = "none");
        filasVisibles.forEach(f => f.style.display = "");
    }

    function crearItemPaginacion(label, page, disabled = false, active = false) {
        const li = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'page-link';
        button.textContent = label;
        button.disabled = disabled;
        button.addEventListener('click', () => {
            if (disabled || active) {
                return;
            }

            state.page = page;
            renderTabla();
        });

        li.appendChild(button);

        return li;
    }

    function renderPaginacion(totalPages, totalRows) {
        paginacion.innerHTML = '';

        if (totalRows === 0) {
            paginacionInfo.textContent = 'No hay registros para mostrar.';
            paginacionWrap.style.display = '';
            return;
        }

        const perPage = parseInt(perPageSelect.value, 10);
        const start = ((state.page - 1) * perPage) + 1;
        const end = Math.min(totalRows, state.page * perPage);
        paginacionInfo.textContent = `Mostrando ${start}-${end} de ${totalRows} registros | Hoja ${state.page} de ${totalPages}`;

        if (totalPages <= 1) {
            paginacionWrap.style.display = '';
            return;
        }

        paginacion.appendChild(crearItemPaginacion('Anterior', Math.max(1, state.page - 1), state.page === 1));

        for (let page = 1; page <= totalPages; page += 1) {
            paginacion.appendChild(crearItemPaginacion(String(page), page, false, state.page === page));
        }

        paginacion.appendChild(crearItemPaginacion('Siguiente', Math.min(totalPages, state.page + 1), state.page === totalPages));
        paginacionWrap.style.display = '';
    }

    function renderTabla() {
        const perPage = parseInt(perPageSelect.value, 10);
        const totalRows = state.filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(totalRows / perPage));

        if (state.page > totalPages) {
            state.page = totalPages;
        }

        const start = (state.page - 1) * perPage;
        const visibles = state.filteredRows.slice(start, start + perPage);

        mostrarFilas(visibles);
        renderPaginacion(totalPages, totalRows);
    }

    function filtrarTabla() {
        const texto = inputBusqueda.value.toLowerCase();
        state.filteredRows = filas.filter(f => 
            Array.from(f.cells).some(c => c.textContent.toLowerCase().includes(texto))
        );
        state.page = 1;
        renderTabla();
    }

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", () => {
        state.page = 1;
        renderTabla();
    });
    renderTabla();

    // ACCIONES
    document.addEventListener("click", (e) => {

        // VER (CORREGIDO → ABRE SHOW.BLADE)
        if(e.target.closest('.btnVerConsumo')){
            const id = e.target.closest('.btnVerConsumo').dataset.id;
            window.location.href = `/consumo/${id}`;
        }

        // ANULAR
        if(e.target.closest('.btnEliminarConsumo')){
            const id = e.target.closest('.btnEliminarConsumo').dataset.id;

            Swal.fire({
                title: 'Anular consumo',
                text: "Esta acción retornará stock y dejará trazabilidad.",
                icon: 'warning',
                input: 'textarea',
                inputLabel: 'Motivo de anulación',
                inputPlaceholder: 'Escribe el motivo...',
                inputAttributes: {
                    maxlength: 255
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, anular',
                cancelButtonText: 'Cancelar',
                preConfirm: (value) => {
                    if (!value || !String(value).trim()) {
                        Swal.showValidationMessage('Debes ingresar un motivo de anulación.');
                    }
                    return value;
                }
            }).then(result => {
                if(result.isConfirmed){
                    const motivo = String(result.value || '').trim();

                    fetch(`/consumo/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ motivo_anulacion: motivo })
                    })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) throw data;
                        return data;
                    })
                    .then(data => {
                        Swal.fire('Éxito', data.success || 'Consumo anulado correctamente.', 'success').then(() => location.reload());
                    })
                    .catch(mostrarErrores);
                }
            });
        }

    });

});
</script>
@endsection