@extends('layouts.main')

@section('titulo', 'Recuperar Eliminados')

@section('contenido')
<main id="main" class="main">
    @if(session('import_auditoria'))
        <div class="alert alert-info alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <h5 class="mb-2"><i class="fa fa-clipboard-list me-2"></i> Resultado de Importación</h5>
            <pre class="mb-0 bg-light rounded p-2 small" style="max-height: 300px; overflow:auto;">{{ session('import_auditoria') }}</pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-trash-restore me-2 text-warning"></i>
                Recuperar Eliminados
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
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
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="inputBusqueda" class="form-control" placeholder="Buscar eliminado...">
                    </div>
                </div>
            </div>
            <div class="table-responsive border rounded">
                <table class="table table-hover w-100 mb-0" id="tablaRecuperar">
                    <thead class="table-warning text-dark">
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Tipo</th>
                            <th>Nombre / Código</th>
                            <th>Eliminado Por / Motivo</th>
                            <th class="text-center">Fecha Eliminado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eliminados as $item)
                        <tr>
                            <td class="text-center fw-bold">{{ $item['id'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $item['tipo'] }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $item['nombre'] ?? $item['codigo'] ?? '-' }}</span>
                                @if(!empty($item['codigo']) && !empty($item['nombre']) && $item['codigo'] !== $item['nombre'])
                                    <div class="small text-muted">Código: {{ $item['codigo'] }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small"><strong>Usuario:</strong> {{ $item['deleted_by'] ?? 'No registrado' }}</div>
                                <div class="small text-muted"><strong>Motivo:</strong> {{ $item['delete_reason'] ?? 'Sin justificación registrada' }}</div>
                            </td>
                            <td class="text-center text-muted">
                                {{ $item['deleted_at'] ? \Carbon\Carbon::parse($item['deleted_at'])->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="text-center">
                                <form action="{{ route('soporte.recuperar.restaurar', [$item['route_key'], $item['id']]) }}" method="POST" style="display:inline-block;" class="restore-form">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" title="Restaurar">
                                        <i class="fa-solid fa-undo"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fa fa-inbox fa-2x mb-2"></i><br>
                                No hay registros eliminados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tablaRecuperar");
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById("inputBusqueda");
    const perPageSelect = document.getElementById("customPerPage");
    const restoreForms = document.querySelectorAll('.restore-form');

    function mostrarFilas(filasVisibles){
        filas.forEach(f => f.style.display = "none");
        filasVisibles.forEach(f => f.style.display = "");
    }

    function filtrarTabla(){
        const texto = inputBusqueda.value.toLowerCase();
        const filtradas = filas.filter(f =>
            Array.from(f.cells).some(c =>
                c.textContent.toLowerCase().includes(texto)
            )
        );
        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value)));
    }

    restoreForms.forEach(form => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.restoreConfirmed === '1') {
                return;
            }

            event.preventDefault();

            const result = await Swal.fire({
                title: '¿Restaurar registro?',
                text: 'El elemento volverá a estar disponible en el sistema.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
            });

            if (!result.isConfirmed) {
                return;
            }

            form.dataset.restoreConfirmed = '1';
            form.requestSubmit();
        });
    });

    inputBusqueda.addEventListener("input", filtrarTabla);
    perPageSelect.addEventListener("change", filtrarTabla);
    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value)));
});
</script>
@endsection
