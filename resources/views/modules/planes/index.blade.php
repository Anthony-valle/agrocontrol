@extends('layouts.main')
@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Planes de Cultivo</h1>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Gestión de Recetas y Planes por Semana de Cultivo</h5>

                <form method="GET" action="{{ route('planes.index') }}" id="filtrosPlanesForm" class="mb-3 p-2 bg-light rounded shadow-sm">
                    <div class="d-flex align-items-center gap-2 flex-nowrap">
                        <select name="per_page" id="perPagePlanes" class="form-select form-select-sm select-registros-planes">
                            <option value="10" {{ (int) request('per_page', 10) === 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ (int) request('per_page', 10) === 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ (int) request('per_page', 10) === 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-nowrap acciones-planes-form">
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportarPlan">
                            <i class="fa-solid fa-file-import me-1"></i> Carga Masiva
                        </button>
                        <a href="{{ route('planes.create') }}" class="btn btn-primary btn-sm">
                             <i class="fa-solid fa-circle-plus me-1"></i> Nuevo Plan
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover border rounded">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cultivo / Lote</th>
                                <th>Semanas de Cultivo</th>
                                <th>Fecha Plan</th>
                                <th>Presupuesto Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planes as $plan)
                                <tr>
                                    <td>{{ $plan->id }}</td>
                                    <td>{{ $plan->cultivo->nombre }}</td>
                                    <td>
                                        @php
                                            $semanaMin = $plan->detalles_min_semana;
                                            $semanaMax = $plan->detalles_max_semana;
                                        @endphp
                                        @if($semanaMin && $semanaMax)
                                            {{ $semanaMin === $semanaMax ? 'Semana ' . $semanaMin : 'Semanas ' . $semanaMin . ' - ' . $semanaMax }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $plan->fecha_plan }}</td>
                                    <td class="fw-bold">{{ agro_number($plan->total_presupuesto, 2) }} L</td>
                                    <td><span class="badge bg-warning text-dark">{{ $plan->estado }}</span></td>
                                    <td>
                                        <a href="{{ route('planes.show', $plan->id) }}" class="btn btn-info btn-sm">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        <a href="{{ route('planes.edit', $plan->id) }}" class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>

                                        <form action="{{ route('planes.destroy', $plan->id) }}" method="POST" style="display:inline-block;" class="form-eliminar-plan">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm btnEliminarPlan" data-id="{{ $plan->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay planes para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $planes->links() }}
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="modalImportarPlan" tabindex="-1" aria-labelledby="modalImportarPlanLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable importar-plan-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title" id="modalImportarPlanLabel">
                    <i class="fa-solid fa-file-import me-2"></i> Carga masiva de plan de cultivo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form action="{{ route('planes.importar') }}" method="POST" enctype="multipart/form-data" id="formImportarPlan">
                @csrf
                <div class="modal-body px-4 py-3 agro-modal-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <p class="fw-semibold mb-0">
                            Plantilla Excel para carga masiva del plan de cultivo:
                        </p>
                        <a href="{{ route('planes.importar.template') }}" class="btn btn-outline-success btn-sm">
                            <i class="fa-solid fa-file-arrow-down me-1"></i> Descargar plantilla
                        </a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Plan base opcional</label>
                            <input type="number" min="1" class="form-control" name="plan_id_base" placeholder="ID del plan existente">
                            <small class="text-muted">Si lo llenas, los detalles del Excel se agregan al plan indicado.</small>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold mb-1">Archivo Excel</label>
                            <input type="file" class="form-control" name="archivo_excel" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>

                    <div class="table-responsive mb-3 import-plan-preview-table">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>plan_id_base</th>
                                    <th>cultivo_id</th>
                                    <th>cultivo_codigo</th>
                                    <th>cultivo_nombre</th>
                                    <th>fecha_plan</th>
                                    <th>semana</th>
                                    <th>categoria</th>
                                    <th>descripcion</th>
                                    <th>cantidad_estimada</th>
                                    <th>unidad_medida</th>
                                    <th>costo_unitario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td>3</td>
                                    <td>CUL-0001</td>
                                    <td>Maiz Amarillo</td>
                                    <td>21/4/2026</td>
                                    <td>1</td>
                                    <td>Mano de Obra</td>
                                    <td>Limpieza de terreno</td>
                                    <td>3</td>
                                    <td>Jornal</td>
                                    <td>400</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>3</td>
                                    <td>CUL-0001</td>
                                    <td>Maiz Amarillo</td>
                                    <td>21/4/2026</td>
                                    <td>1</td>
                                    <td>Preparacion de Suelo</td>
                                    <td>Arado mecanizado</td>
                                    <td>1</td>
                                    <td>Servicio</td>
                                    <td>1800</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mb-0 border-0 rounded-3 import-plan-help">
                        <small>
                            Esta carga masiva crea o actualiza <b>planes de cultivo</b> y agrega automáticamente sus <b>detalles por semana</b> a partir del archivo Excel.
                            <br>
                            <b>plan_id_base</b> es opcional. Si lo envía, la importación agrega los detalles al plan existente en vez de crear uno nuevo.
                            <br>
                            <b>cultivo_id</b> es obligatorio y debe existir en el catalogo de cultivos.
                            <br>
                            Si varias filas pertenecen al mismo plan, puede repetir o dejar vacíos <b>cultivo_id</b> y <b>fecha_plan</b> en las filas siguientes; se reutiliza el valor anterior.
                            <br>
                            <b>categoria</b> puede venir como Mano de Obra, Fertilizante, Fitosanitario, Preparacion de Suelo, Otros Insumos u otra categoria definida en su archivo.
                            <br>
                            <b>descripcion</b> debe venir como producto o actividad (ejemplo: Limpieza de terreno, Arado mecanizado).
                            <br>
                            <b>cosecha_estimada</b> no va en Excel: ese dato se toma automaticamente del cultivo creado en el sistema.
                            <br>
                            La columna <b>semana</b> representa la <b>semana de cultivo</b> y debe venir entre 1 y 52.
                            <br>
                            Si no envía <b>fecha_plan</b>, se usará la fecha actual automáticamente.
                            <br>
                            Se omiten filas con <b>cantidad_estimada</b> menor o igual a 0.
                        </small>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-upload me-1"></i> Importar plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#filtrosPlanesForm {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

#filtrosPlanesForm .select-registros-planes {
    width: 90px;
    min-width: 90px;
}

#filtrosPlanesForm .btn {
    white-space: nowrap;
}

@media (min-width: 992px) {
    #filtrosPlanesForm {
        flex-wrap: nowrap;
    }

    #filtrosPlanesForm .acciones-planes-form {
        margin-left: auto;
    }
}

.importar-plan-dialog {
    width: min(94vw, 1120px);
    max-width: 1120px;
}

#modalImportarPlan .modal-content {
    max-height: calc(100vh - 2rem);
    border: 0;
    overflow: hidden;
}

#modalImportarPlan .modal-body {
    overflow-y: auto;
    max-height: calc(100vh - 10rem);
}

.agro-modal-body .form-control,
.agro-modal-body .form-select {
    border-color: #d7dfeb;
}

.agro-modal-body .form-label {
    margin-bottom: 0.2rem !important;
}

.import-plan-preview-table {
    border: 1px solid #dbe5df;
    border-radius: 12px;
}

.import-plan-preview-table table {
    margin-bottom: 0;
}

.import-plan-help {
    font-size: 0.95rem;
    line-height: 1.45;
}

#modalImportarPlan .modal-header,
#modalImportarPlan .modal-footer {
    position: sticky;
    z-index: 2;
}

#modalImportarPlan .modal-header {
    top: 0;
}

#modalImportarPlan .modal-footer {
    bottom: 0;
    background: #fff;
    box-shadow: 0 -8px 18px rgba(15, 90, 67, 0.08);
}

@media (max-width: 768px) {
    .importar-plan-dialog {
        width: calc(100vw - 1rem);
        max-width: none;
        margin: 0.5rem auto;
    }

    #modalImportarPlan .modal-body {
        max-height: calc(100vh - 8.8rem);
        padding-inline: 1rem !important;
    }

    .import-plan-help {
        font-size: 0.88rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtrosForm = document.getElementById('filtrosPlanesForm');
    const perPageSelect = document.getElementById('perPagePlanes');
    const formImportarPlan = document.getElementById('formImportarPlan');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (perPageSelect && filtrosForm) {
        perPageSelect.addEventListener('change', function() {
            filtrosForm.submit();
        });
    }

    document.querySelectorAll('.btnEliminarPlan').forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('.form-eliminar-plan');

            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    function mostrarErroresImportacionPlan(error) {
        if (error && error.errors) {
            let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            Object.values(error.errors).flat().forEach((msg) => {
                mensajes += `<li>${msg}</li>`;
            });
            mensajes += '</ul>';
            Swal.fire({
                title: 'Error de validación',
                html: mensajes,
                icon: 'error'
            });
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

        Swal.fire({
            title: 'No se pudo completar la importación',
            html: `<p class="mb-0">${error?.message || 'Revisa el archivo e intenta de nuevo.'}</p>`,
            icon: 'error',
        });
    }

    if (formImportarPlan) {
        formImportarPlan.addEventListener('submit', function(e) {
            e.preventDefault();

            if (formImportarPlan.dataset.loading === 'true') {
                return;
            }

            formImportarPlan.dataset.loading = 'true';
            const submitBtn = formImportarPlan.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            Swal.fire({
                title: 'Importando archivo',
                html: 'Procesando la carga masiva del plan de cultivo. Esto puede tardar unos segundos...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(formImportarPlan);
            formData.set('_token', csrfToken || '');
            const requestUrl = new URL(formImportarPlan.action, window.location.origin);
            const relativeAction = `${requestUrl.pathname}${requestUrl.search}`;

            fetch(relativeAction, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
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
                    bootstrap.Modal.getInstance(document.getElementById('modalImportarPlan'))?.hide();
                    Swal.close();

                    Swal.fire({
                        title: 'Carga masiva completada',
                        html: data.summary_html || '<p class="mb-0">Los planes se importaron correctamente.</p>',
                        icon: 'success',
                    }).then(() => {
                        window.location.href = data.redirect || '{{ route('planes.index') }}';
                    });
                })
                .catch((error) => {
                    Swal.close();

                    if (error instanceof TypeError && error.message === 'Failed to fetch') {
                        mostrarErroresImportacionPlan({
                            message: 'El servidor corto la respuesta durante la carga masiva del plan. Cuando el backend responda correctamente, aqui se mostrara el detalle real del error o el resumen de importación.',
                        });
                        return;
                    }

                    mostrarErroresImportacionPlan(error);
                })
                .finally(() => {
                    formImportarPlan.dataset.loading = 'false';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });
    }
});
</script>
@endsection