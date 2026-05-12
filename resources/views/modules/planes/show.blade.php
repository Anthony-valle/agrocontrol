@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Resumen del Plan de Cultivo</h1>
    </div>

    <section class="section">

        <div class="card shadow-sm">

            <!-- Encabezado del Plan -->
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fa-solid fa-seedling me-2"></i>
                    Plan #{{ $plan->id }}
                </h5>
            </div>

            <div class="card-body">
                @php
                    $detallesOrdenados = $plan->detalles->sortBy([
                        ['semana', 'asc'],
                        ['categoria', 'asc'],
                    ])->values();
                    $cosechaEstimadaMostrada = $plan->cultivo->cosecha_estimada ?? $plan->cosecha_estimada;

                    $formatearActividad = function ($categoria, $descripcion) {
                        $actividad = trim((string) $descripcion);
                        $categoriaNormalizada = mb_strtolower(trim((string) $categoria), 'UTF-8');

                        if (in_array($categoriaNormalizada, ['preparacion de suelo', 'preparación de suelo'], true)) {
                            $actividad = preg_replace('/^mecanizaci[oó]n\s*[-:]\s*/iu', '', $actividad) ?? $actividad;
                        }

                        return $actividad === '' ? '-' : $actividad;
                    };
                @endphp

                <!-- Información general -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="fw-bold">Cultivo</label>
                        <div>{{ $plan->cultivo->nombre }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold">Fecha Plan</label>
                        <div>{{ $plan->fecha_plan }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold">Cosecha Estimada</label>
                        <div>{{ agro_number((float) $cosechaEstimadaMostrada,2) }}</div>
                    </div>

                    <div class="col-md-3">
                        <label class="fw-bold">Estado</label>
                        <div>
                            <span class="badge bg-warning text-dark">{{ $plan->estado }}</span>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Título del detalle -->
                <h5 class="mb-3">
                    <i class="fa-solid fa-list me-2"></i>
                    Detalle de Actividades
                </h5>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded bg-light h-100 p-3">
                            <div class="text-muted small">Actividades visibles</div>
                            <div class="fs-4 fw-bold" id="resumenActividadesVisibles">0</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded bg-light h-100 p-3">
                            <div class="text-muted small">Cantidad total filtrada</div>
                            <div class="fs-4 fw-bold" id="resumenCantidadFiltrada">0.00</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded bg-light h-100 p-3">
                            <div class="text-muted small">Total presupuesto filtrado</div>
                            <div class="fs-4 fw-bold text-success" id="resumenPresupuestoFiltrado">0.00 L</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center mb-3 p-2 bg-white rounded shadow-sm gap-2 border">
                    <div class="d-flex align-items-center gap-1 border-end pe-2">
                        <select id="perPageActividades" class="form-select form-select-sm" style="width: 70px;">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="9999">Todas</option>
                        </select>
                        <small class="text-muted" style="font-size: 0.75rem;">Registros</small>
                    </div>

                    <div class="input-group input-group-sm" style="width: 220px;">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="busquedaActividades" class="form-control border-start-0" placeholder="Buscar actividad...">
                    </div>

                    <select id="filtroSemana" class="form-select form-select-sm" style="width: 180px;">
                        <option value="">Todas las semanas</option>
                        @foreach($detallesOrdenados->pluck('semana')->filter()->unique()->sort()->values() as $semana)
                            <option value="{{ $semana }}">Semana {{ $semana }}</option>
                        @endforeach
                    </select>

                    <div class="ms-auto d-flex gap-1">
                        <button type="button" id="btnFiltrarActividades" class="btn btn-sm btn-primary">
                            <i class="fa fa-filter"></i>
                        </button>
                        <button type="button" id="btnLimpiarActividades" class="btn btn-sm btn-outline-secondary" title="Limpiar">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabla de detalle -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="tablaActividadesPlan">

                        <!-- Encabezado gris suave -->
                        <thead class="table-secondary">
                            <tr>
                                <th>Semana Cultivo</th>
                                <th>Categoría</th>
                                <th>Actividad</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Costo Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php $total = 0; @endphp

                            @foreach($detallesOrdenados as $detalle)
                                @php
                                    $subtotal = $detalle->cantidad_estimada * $detalle->costo_unitario;
                                    $total += $subtotal;
                                    $actividad = $formatearActividad($detalle->categoria, $detalle->descripcion);
                                @endphp
                                <tr data-semana="{{ $detalle->semana }}" data-cantidad="{{ (float) $detalle->cantidad_estimada }}" data-subtotal="{{ (float) $subtotal }}">
                                    <td>Semana {{ $detalle->semana }}</td>
                                    <td>{{ $detalle->categoria }}</td>
                                    <td>{{ $actividad }}</td>
                                    <td>{{ $detalle->cantidad_estimada }}</td>
                                    <td>{{ $detalle->unidad_medida }}</td>
                                    <td>{{ agro_number($detalle->costo_unitario,2) }} L</td>
                                    <td class="fw-bold text-success">{{ agro_number($subtotal,2) }} L</td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end fw-bold">RESUMEN FILTRADO</td>
                                <td class="fw-bold">Cant.: <span id="footerCantidadFiltrada">0.00</span></td>
                                <td class="fw-bold text-success"><span id="footerPresupuestoFiltrado">0.00 L</span></td>
                            </tr>
                        </tfoot>

                    </table>
                </div>

            </div>

            <div class="card-footer text-end">
                <a href="{{ route('planes.export.excel', $plan->id) }}" class="btn btn-success me-2">
                    <i class="fa-solid fa-file-excel me-2"></i> Excel
                </a>
                <a href="{{ route('planes.export.pdf', $plan->id) }}" class="btn btn-danger me-2">
                    <i class="fa-solid fa-file-pdf me-2"></i> PDF
                </a>
                <a href="{{ route('planes.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i> Volver
                </a>
            </div>

        </div>

    </section>

</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaActividadesPlan');
    const inputBusqueda = document.getElementById('busquedaActividades');
    const perPageSelect = document.getElementById('perPageActividades');
    const semanaSelect = document.getElementById('filtroSemana');
    const btnFiltrar = document.getElementById('btnFiltrarActividades');
    const btnLimpiar = document.getElementById('btnLimpiarActividades');

    if (!tabla || !inputBusqueda || !perPageSelect || !semanaSelect) {
        return;
    }

    const filas = Array.from(tabla.tBodies[0].rows);

    function initSelectBuscable(selector, placeholder) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }

        const $el = jQuery(selector);
        if (!$el.length) {
            return;
        }

        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }

        $el.select2({
            theme: 'bootstrap-5',
            width: 'style',
            placeholder: placeholder || 'Seleccione...',
            minimumResultsForSearch: 0,
            allowClear: false,
        });
    }

    function aplicarFiltros() {
        const texto = inputBusqueda.value.trim().toLowerCase();
        const semana = semanaSelect.value;
        const limite = parseInt(perPageSelect.value, 10);

        const filtradas = filas.filter((fila) => {
            const coincideTexto = Array.from(fila.cells).some((celda) =>
                celda.textContent.toLowerCase().includes(texto)
            );
            const coincideSemana = !semana || fila.dataset.semana === semana;

            return coincideTexto && coincideSemana;
        });

        filas.forEach((fila) => {
            fila.style.display = 'none';
        });

        const filasAMostrar = semana ? filtradas : filtradas.slice(0, limite);

        filasAMostrar.forEach((fila) => {
            fila.style.display = '';
        });

        actualizarResumen(filasAMostrar);
    }

    function actualizarResumen(filasVisibles) {
        const totalActividades = filasVisibles.length;
        const totalCantidad = filasVisibles.reduce((acumulado, fila) => {
            return acumulado + (parseFloat(fila.dataset.cantidad || '0') || 0);
        }, 0);
        const totalPresupuesto = filasVisibles.reduce((acumulado, fila) => {
            return acumulado + (parseFloat(fila.dataset.subtotal || '0') || 0);
        }, 0);

        document.getElementById('resumenActividadesVisibles').textContent = totalActividades.toLocaleString('en-US');
        document.getElementById('resumenCantidadFiltrada').textContent = totalCantidad.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        document.getElementById('resumenPresupuestoFiltrado').textContent = totalPresupuesto.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + ' L';
        document.getElementById('footerCantidadFiltrada').textContent = totalCantidad.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        document.getElementById('footerPresupuestoFiltrado').textContent = totalPresupuesto.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + ' L';
    }

    inputBusqueda.addEventListener('input', aplicarFiltros);
    perPageSelect.addEventListener('change', aplicarFiltros);
    semanaSelect.addEventListener('change', aplicarFiltros);
    btnFiltrar?.addEventListener('click', aplicarFiltros);
    btnLimpiar?.addEventListener('click', () => {
        inputBusqueda.value = '';
        perPageSelect.value = '10';
        semanaSelect.value = '';

        if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
            jQuery('#perPageActividades').trigger('change.select2');
            jQuery('#filtroSemana').trigger('change.select2');
        }

        aplicarFiltros();
    });

    initSelectBuscable('#perPageActividades', 'Registros...');
    initSelectBuscable('#filtroSemana', 'Semana...');
    aplicarFiltros();
});
</script>
@endsection