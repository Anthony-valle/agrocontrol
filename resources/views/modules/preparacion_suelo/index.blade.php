@extends('layouts.main')

@section('titulo', 'Preparación de Suelo')

@section('contenido')
<main id="main" class="main">
    <style>
        .prep-hero {
            margin-bottom: 1.5rem;
        }

        .prep-form-card {
            overflow: hidden;
            border-radius: 1rem;
        }

        .prep-form-card .card-body {
            background: linear-gradient(180deg, #ffffff 0%, #f5f8fc 100%);
        }

        .prep-form-intro {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .prep-form-intro-copy {
            max-width: 720px;
        }

        .prep-form-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 0.9rem;
            border: 1px solid rgba(var(--bs-primary-rgb), 0.15);
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.06);
            color: var(--bs-primary);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .prep-field label {
            margin-bottom: 0.45rem;
            font-weight: 700;
        }

        .prep-field .form-control,
        .prep-field .form-select {
            min-height: 3rem;
            border-radius: 0.8rem;
        }

        .prep-field small {
            display: block;
            margin-top: 0.35rem;
        }

        .prep-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-top: 0.25rem;
        }

        .prep-summary-item {
            padding: 0.9rem 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            background: #fff;
        }

        .prep-summary-label {
            margin-bottom: 0.2rem;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary-color);
            font-weight: 700;
        }

        .prep-summary-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--bs-heading-color);
        }

        .prep-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            padding-top: 0.35rem;
        }

        .prep-actions .btn {
            min-height: 2.9rem;
            padding-inline: 1rem;
            border-radius: 0.85rem;
        }

        .prep-table-card {
            border-radius: 1rem;
            overflow: hidden;
        }

        .prep-table-card .card-header {
            padding-top: 1.25rem;
        }

        .prep-activities-card {
            border-radius: 1rem;
            overflow: hidden;
        }

        .prep-resumen-card {
            border-radius: 1rem;
            overflow: hidden;
        }

        .prep-resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
        }

        .prep-resumen-item {
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 1rem;
            background: #fff;
        }

        .prep-resumen-item .prep-activity-name {
            margin-top: 0;
        }

        .prep-resumen-kpi {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 0.9rem;
        }

        .prep-resumen-kpi-box {
            padding: 0.75rem;
            border-radius: 0.85rem;
            background: #f7f9fc;
        }

        .prep-resumen-kpi-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bs-secondary-color);
            font-weight: 700;
        }

        .prep-resumen-kpi-value {
            margin-top: 0.15rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--bs-heading-color);
        }

        .prep-resumen-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .prep-resumen-totals {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .prep-resumen-total-pill {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            min-width: 150px;
            padding: 0.8rem 1rem;
            border-radius: 0.95rem;
            background: #f7f9fc;
            border: 1px solid var(--bs-border-color);
        }

        .prep-resumen-total-pill strong {
            font-size: 1rem;
        }

        .prep-activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .prep-activity-item {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 1rem;
            background: #fff;
        }

        .prep-activity-code {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: rgba(var(--bs-primary-rgb), 0.08);
            color: var(--bs-primary);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .prep-activity-name {
            margin-top: 0.8rem;
            margin-bottom: 0.2rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--bs-heading-color);
        }

        .prep-activity-secondary {
            font-size: 0.92rem;
            color: var(--bs-secondary-color);
        }

        .prep-activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.85rem;
        }

        .prep-activity-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            background: #f5f8fc;
            color: var(--bs-secondary-color);
            font-size: 0.82rem;
        }

        .prep-activity-note {
            margin-top: 0.75rem;
            font-size: 0.88rem;
            color: var(--bs-secondary-color);
        }

        .prep-empty-state {
            padding: 1.5rem;
            border: 1px dashed var(--bs-border-color);
            border-radius: 1rem;
            text-align: center;
            color: var(--bs-secondary-color);
            background: #fff;
        }

        .prep-table-wrap {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.9rem;
            overflow: hidden;
        }

        .prep-table-wrap thead th {
            white-space: nowrap;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 3rem;
            border-radius: 0.8rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single,
        .select2-container--bootstrap-5 .select2-selection--multiple {
            padding-top: 0.42rem;
            padding-bottom: 0.42rem;
        }

        .select2-results__option .prep-option-line {
            display: block;
            font-weight: 700;
        }

        .select2-results__option .prep-option-subline {
            display: block;
            margin-top: 0.1rem;
            font-size: 0.82rem;
            color: #6c757d;
        }

        .select2-selection__rendered .prep-selected-inline {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            align-items: center;
        }

        .select2-selection__rendered .prep-selected-inline small {
            color: #6c757d;
            font-size: 0.82em;
        }

        .select2-results__option .prep-option-meta {
            display: inline-flex;
            gap: 0.35rem;
            align-items: center;
            margin-top: 0.2rem;
            font-size: 0.78rem;
            color: #6c757d;
        }

        @media (max-width: 991.98px) {
            .prep-form-intro {
                margin-bottom: 1rem;
            }

            .prep-form-badge {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <div class="pagetitle">
        <h1>Preparación de Suelo</h1>
        <p class="text-muted mb-0">Registra labores por lote y aplícalas a uno o varios cultivos del mismo lote.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4 prep-form-card">
            <div class="card-body p-4">
                <div class="prep-form-intro">
                    <div class="prep-form-intro-copy">
                        <h5 class="mb-1">Registrar actividad de preparación</h5>
                        <p class="text-muted mb-0">Selecciona el lote, los cultivos y la actividad. El sistema divide el costo total entre los cultivos elegidos y genera el registro automáticamente.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="prep-form-badge">
                            <i class="fa-solid fa-tractor"></i>
                            Aplicación masiva por lote
                        </div>
                        <a href="{{ route('preparacion-suelo-actividades.index') }}" class="btn btn-outline-primary btn-sm rounded-pill">
                            <i class="fa-solid fa-screwdriver-wrench me-1"></i> Administrar actividades
                        </a>
                    </div>
                </div>

                <form action="{{ route('preparacion-suelo.store') }}" method="POST" class="row g-3 align-items-start">
                    @csrf
                    <div class="col-lg-3 col-md-6 prep-field">
                        <label class="form-label fw-bold">Lote</label>
                        <select name="lote_id" id="prepSueloLote" class="form-select" required>
                            <option value="">Seleccione...</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}" {{ (string) old('lote_id') === (string) $lote->id ? 'selected' : '' }}>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-4 col-md-6 prep-field">
                        <label class="form-label fw-bold">Cultivos a aplicar</label>
                        <select name="cultivo_ids[]" id="prepSueloCultivos" class="form-select" multiple required></select>
                        <small class="text-muted">El costo total se divide entre los cultivos seleccionados.</small>
                    </div>

                    <div class="col-lg-2 col-md-6 prep-field">
                        <label class="form-label fw-bold">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ old('fecha', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-lg-3 col-md-6 prep-field">
                        <label class="form-label fw-bold">Actividad / Producto</label>
                        <select name="actividad" id="prepSueloActividad" class="form-select" required>
                            <option value="">Seleccione...</option>
                        </select>
                        <small class="text-muted">Se muestran actividad principal y desglose del catálogo de preparación.</small>
                        <input type="hidden" name="unidad_medida" id="prepSueloUnidad" value="Servicio">
                    </div>

                    <div class="col-lg-3 col-md-4 prep-field">
                        <label class="form-label fw-bold">Costo unitario</label>
                        <input type="number" name="costo_unitario" id="prepSueloCostoUnitario" class="form-control" min="0.01" step="0.01" value="{{ old('costo_unitario') }}" required>
                        <small class="text-muted">Se calcula por hectárea del cultivo seleccionado.</small>
                    </div>

                    <div class="col-lg-9 col-md-8 prep-field">
                        <label class="form-label fw-bold">Observación</label>
                        <input type="text" name="observacion" class="form-control" maxlength="255" value="{{ old('observacion') }}" placeholder="Qué se realizó, detalle u observación adicional...">
                    </div>

                    <div class="col-12">
                        <div class="prep-summary-grid">
                            <div class="prep-summary-item">
                                <div class="prep-summary-label">Cultivos seleccionados</div>
                                <div class="prep-summary-value" id="prepSueloResumenCultivos">0</div>
                            </div>
                            <div class="prep-summary-item">
                                <div class="prep-summary-label">Hectáreas totales</div>
                                <div class="prep-summary-value" id="prepSueloResumenHectareas">0.00 ha</div>
                            </div>
                            <div class="prep-summary-item">
                                <div class="prep-summary-label">Costo total calculado</div>
                                <div class="prep-summary-value text-primary" id="prepSueloResumenTotal">0.00 Lps</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 prep-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-circle-plus me-1"></i> Registrar y aplicar a cultivos
                        </button>
                        <a href="{{ route('planes.index') }}" class="btn btn-outline-secondary">Volver</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 prep-activities-card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="card-title mb-1">Actividades disponibles</h5>
                <p class="text-muted small mb-0">Este catálogo alimenta el selector de actividad y te permite ver rápidamente la estructura actividad principal + desglose.</p>
            </div>
            <div class="card-body pt-3">
                @if($actividadesCatalogo->isNotEmpty())
                    <div class="prep-activities-grid">
                        @foreach($actividadesCatalogo as $actividad)
                            <div class="prep-activity-item">
                                <span class="prep-activity-code">{{ $actividad['codigo'] }}</span>
                                <div class="prep-activity-name">{{ $actividad['nombre'] }}</div>
                                <div class="prep-activity-secondary">{{ $actividad['actividad'] }}</div>
                                <div class="prep-activity-meta">
                                    <span>
                                        <i class="fa-solid fa-ruler"></i>
                                        {{ $actividad['unidad_medida'] }}
                                    </span>
                                </div>
                                @if($actividad['observaciones'] !== '')
                                    <div class="prep-activity-note">{{ $actividad['observaciones'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="prep-empty-state">
                        No hay actividades registradas en el catálogo. Usa el botón Administrar actividades para crear la primera.
                    </div>
                @endif
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4 prep-resumen-card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="card-title mb-1">Aplicado por Lote y Cultivo</h5>
                <p class="text-muted small mb-0">Selecciona un lote y un cultivo activo para ver cuánto lleva aplicado por actividad de preparación de suelo.</p>
            </div>
            <div class="card-body pt-3">
                <div class="row g-3 mb-3">
                    <div class="col-lg-4 col-md-6 prep-field">
                        <label class="form-label fw-bold">Lote</label>
                        <select id="prepResumenLote" class="form-select">
                            <option value="">Seleccione...</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}">{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-5 col-md-6 prep-field">
                        <label class="form-label fw-bold">Cultivo activo</label>
                        <select id="prepResumenCultivo" class="form-select">
                            <option value="">Seleccione un lote primero...</option>
                        </select>
                        <small class="text-muted">Solo se muestran cultivos con estado Activo.</small>
                    </div>
                </div>

                <div class="prep-resumen-header">
                    <div>
                        <div class="small text-muted text-uppercase fw-bold">Resumen del cultivo</div>
                        <h6 class="mb-0" id="prepResumenTitulo">Selecciona lote y cultivo</h6>
                    </div>
                    <div class="prep-resumen-totals">
                        <div class="prep-resumen-total-pill">
                            <span class="small text-muted">Total aplicado</span>
                            <strong id="prepResumenMonto">0.00 Lps</strong>
                        </div>
                        <div class="prep-resumen-total-pill">
                            <span class="small text-muted">Hectáreas del cultivo</span>
                            <strong id="prepResumenHa">0.00 ha</strong>
                        </div>
                    </div>
                </div>

                <div id="prepResumenAplicadoGrid" class="prep-resumen-grid"></div>
                <div id="prepResumenEmpty" class="prep-empty-state">Selecciona un cultivo activo para ver cuánto lleva aplicado por actividad.</div>
            </div>
        </div>

        <div class="card shadow-sm border-0 prep-table-card">
            <div class="card-header bg-white border-0 pb-0">
                <h5 class="card-title mb-0">Registros aplicados</h5>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive prep-table-wrap">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Lote</th>
                                <th>Cultivo</th>
                                <th>Actividad realizada</th>
                                <th>Ha</th>
                                <th>Costo unitario</th>
                                <th>Observación</th>
                                <th>Unidad</th>
                                <th>Costo aplicado</th>
                                <th>Registrado por</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($registros as $registro)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($registro['fecha'])->format('d/m/Y') }}</td>
                                    <td>{{ $registro['lote'] }}</td>
                                    <td>{{ $registro['cultivo'] }}</td>
                                    <td>{{ $registro['actividad'] }}</td>
                                    <td>{{ agro_number((float) $registro['hectareas'], 2) }}</td>
                                    <td>{{ agro_number((float) $registro['costo_unitario'], 2) }} Lps</td>
                                    <td>{{ $registro['observacion'] !== '' ? $registro['observacion'] : '-' }}</td>
                                    <td>{{ $registro['unidad'] }}</td>
                                    <td class="fw-bold">{{ agro_number($registro['costo'], 2) }} Lps</td>
                                    <td>{{ $registro['registrado_por'] }}</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('preparacion-suelo.show', $registro['id']) }}" class="btn btn-info btn-sm text-white" title="Verificar registro">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        @if(strtoupper((string) $registro['estado']) !== 'ANULADO')
                                            <a href="{{ route('preparacion-suelo.edit', $registro['id']) }}" class="btn btn-warning btn-sm" title="Editar registro">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            @if(auth()->user()?->hasAnyRole(['admin', 'supervisor', 'propietario']))
                                                <button type="button" class="btn btn-danger btn-sm btnEliminarPreparacion" data-id="{{ $registro['id'] }}" title="Anular registro">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No hay registros de preparación de suelo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $registros->links('vendor.pagination.bootstrap-5-notext') }}
                </div>
            </div>
        </div>
    </section>
</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const preparacionSueloCultivos = @json($cultivos);
const preparacionSueloCultivosSeleccionados = @json(collect(old('cultivo_ids', []))->map(fn ($value) => (string) $value)->values());
const preparacionSueloActividades = @json($actividadesCatalogo->values());
const preparacionSueloRegistros = @json($registrosResumen);

function formatearActividadPreparacion(nombre) {
    return (nombre || '').replace(/^mecanizaci[oó]n\s*[-:]\s*/i, '').trim();
}

function escapeHtmlPreparacion(texto) {
    return String(texto ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function formatearNumeroPreparacion(valor, decimales = 2) {
    const numero = Number(valor || 0);

    return new Intl.NumberFormat('es-HN', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    }).format(Number.isFinite(numero) ? numero : 0);
}

function templateResultadoActividad(option) {
    if (!option.id) {
        return option.text;
    }

    const principal = option.element?.dataset?.principal || option.text || '';
    const secundaria = option.element?.dataset?.secundaria || '';

    return jQuery(`
        <span>
            <span class="prep-option-line">${escapeHtmlPreparacion(principal)}</span>
            <span class="prep-option-subline">${escapeHtmlPreparacion(secundaria)}</span>
        </span>
    `);
}

function templateResultadoCultivo(option) {
    if (!option.id) {
        return option.text;
    }

    const nombre = option.element?.dataset?.nombre || option.text || '';
    const hectareas = option.element?.dataset?.hectareas || '0.00';
    const unidad = option.element?.dataset?.unidadCultivo || 'ha';

    return jQuery(`
        <span>
            <span class="prep-option-line">${escapeHtmlPreparacion(nombre)}</span>
            <span class="prep-option-meta">
                <i class="fa-solid fa-ruler-combined"></i>
                ${escapeHtmlPreparacion(hectareas)} ${escapeHtmlPreparacion(unidad)}
            </span>
        </span>
    `);
}

function templateSeleccionCultivo(option) {
    if (!option.id) {
        return option.text;
    }

    const nombre = option.element?.dataset?.nombre || option.text || '';
    const hectareas = option.element?.dataset?.hectareas || '0.00';

    return jQuery(`
        <span class="prep-selected-inline">
            <span>${escapeHtmlPreparacion(nombre)}</span>
            <small>${escapeHtmlPreparacion(hectareas)} ha</small>
        </span>
    `);
}

function templateSeleccionActividad(option) {
    if (!option.id) {
        return option.text;
    }

    const principal = option.element?.dataset?.principal || option.text || '';
    const secundaria = option.element?.dataset?.secundaria || '';

    return jQuery(`
        <span class="prep-selected-inline">
            <span>${escapeHtmlPreparacion(principal)}</span>
            <small>${escapeHtmlPreparacion(secundaria)}</small>
        </span>
    `);
}

function initPreparacionSueloSelect(selector, placeholder) {
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

    const isActividad = selector === '#prepSueloActividad';
    const isCultivo = selector === '#prepSueloCultivos' || selector === '#prepResumenCultivo';

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: false,
        minimumResultsForSearch: 0,
        templateResult: isActividad
            ? templateResultadoActividad
            : (isCultivo ? templateResultadoCultivo : undefined),
        templateSelection: isActividad
            ? templateSeleccionActividad
            : (isCultivo ? templateSeleccionCultivo : undefined),
        escapeMarkup: (markup) => markup,
    });
}

function renderCultivosPreparacion() {
    const loteId = document.getElementById('prepSueloLote').value;
    const select = document.getElementById('prepSueloCultivos');
    const selectedValues = Array.from(new Set([
        ...Array.from(select.selectedOptions).map((option) => option.value),
        ...preparacionSueloCultivosSeleccionados,
    ]));

    select.innerHTML = '';

    preparacionSueloCultivos
        .filter((cultivo) => String(cultivo.lotes_id) === String(loteId) && String(cultivo.estado).toLowerCase() !== 'cerrado')
        .forEach((cultivo) => {
            const option = document.createElement('option');
            option.value = cultivo.id;
            option.textContent = `${cultivo.nombre} (${parseFloat(cultivo.hectareas || 0).toFixed(2)} ha)`;
            option.dataset.nombre = cultivo.nombre;
            option.dataset.hectareas = cultivo.hectareas ?? 0;
            option.dataset.unidadCultivo = cultivo.unidad_medida || 'ha';
            option.selected = selectedValues.includes(String(cultivo.id));
            select.appendChild(option);
        });

    initPreparacionSueloSelect('#prepSueloCultivos', 'Selecciona cultivos...');
    actualizarResumenPreparacion();
}

function cultivoActivoPreparacion(cultivo) {
    return String(cultivo?.estado || '').trim().toLowerCase() === 'activo';
}

function renderCultivosResumen() {
    const loteId = document.getElementById('prepResumenLote').value;
    const select = document.getElementById('prepResumenCultivo');
    const selectedValue = select.value;

    select.innerHTML = '<option value="">Seleccione...</option>';

    preparacionSueloCultivos
        .filter((cultivo) => String(cultivo.lotes_id) === String(loteId) && cultivoActivoPreparacion(cultivo))
        .forEach((cultivo) => {
            const option = document.createElement('option');
            option.value = cultivo.id;
            option.textContent = `${cultivo.nombre} (${parseFloat(cultivo.hectareas || 0).toFixed(2)} ha)`;
            option.dataset.nombre = cultivo.nombre;
            option.dataset.hectareas = parseFloat(cultivo.hectareas || 0).toFixed(2);
            option.dataset.unidadCultivo = 'ha';
            option.selected = String(selectedValue) === String(cultivo.id);
            select.appendChild(option);
        });

    initPreparacionSueloSelect('#prepResumenCultivo', 'Selecciona cultivo activo...');
    actualizarResumenAplicadoCultivo();
}

function renderResumenActividadCard(item) {
    return `
        <div class="prep-resumen-item">
            <div class="prep-activity-name">${escapeHtmlPreparacion(item.actividad)}</div>
            <div class="prep-activity-secondary">${escapeHtmlPreparacion(item.aplicaciones)} aplicación(es)</div>
            <div class="prep-resumen-kpi">
                <div class="prep-resumen-kpi-box">
                    <div class="prep-resumen-kpi-label">Hectáreas del cultivo</div>
                    <div class="prep-resumen-kpi-value">${escapeHtmlPreparacion(formatearNumeroPreparacion(item.hectareas))} ha</div>
                </div>
                <div class="prep-resumen-kpi-box">
                    <div class="prep-resumen-kpi-label">Monto acumulado</div>
                    <div class="prep-resumen-kpi-value">${escapeHtmlPreparacion(formatearNumeroPreparacion(item.costo))} Lps</div>
                </div>
            </div>
            <div class="prep-activity-note">Última aplicación: ${escapeHtmlPreparacion(item.fecha)}</div>
        </div>
    `;
}

function actualizarResumenAplicadoCultivo() {
    const loteId = document.getElementById('prepResumenLote').value;
    const cultivoId = document.getElementById('prepResumenCultivo').value;
    const titulo = document.getElementById('prepResumenTitulo');
    const totalMonto = document.getElementById('prepResumenMonto');
    const totalHa = document.getElementById('prepResumenHa');
    const grid = document.getElementById('prepResumenAplicadoGrid');
    const empty = document.getElementById('prepResumenEmpty');

    if (!loteId || !cultivoId) {
        titulo.textContent = 'Selecciona lote y cultivo';
        totalMonto.textContent = `${formatearNumeroPreparacion(0)} Lps`;
        totalHa.textContent = `${formatearNumeroPreparacion(0)} ha`;
        grid.innerHTML = '';
        empty.textContent = 'Selecciona un cultivo activo para ver cuánto lleva aplicado por actividad.';
        empty.style.display = 'block';
        return;
    }

    const cultivo = preparacionSueloCultivos.find((item) => String(item.id) === String(cultivoId));
    const registros = preparacionSueloRegistros.filter((item) => String(item.lote_id) === String(loteId) && String(item.cultivo_id) === String(cultivoId));
    const hectareasCultivo = parseFloat(cultivo?.hectareas || 0);

    titulo.textContent = cultivo ? `${cultivo.nombre} · ${(parseFloat(cultivo.hectareas || 0)).toFixed(2)} ha` : 'Cultivo seleccionado';

    if (!registros.length) {
        totalMonto.textContent = `${formatearNumeroPreparacion(0)} Lps`;
        totalHa.textContent = `${formatearNumeroPreparacion(hectareasCultivo)} ha`;
        grid.innerHTML = '';
        empty.textContent = 'Este cultivo activo todavía no tiene aplicaciones registradas de preparación de suelo.';
        empty.style.display = 'block';
        return;
    }

    const resumen = Object.values(registros.reduce((acc, item) => {
        const key = item.actividad;
        if (!acc[key]) {
            acc[key] = {
                actividad: item.actividad,
                hectareas: hectareasCultivo,
                costo: 0,
                aplicaciones: 0,
                fecha: item.fecha,
            };
        }

        acc[key].costo += parseFloat(item.costo || 0);
        acc[key].aplicaciones += 1;
        if (String(item.fecha) > String(acc[key].fecha)) {
            acc[key].fecha = item.fecha;
        }
        return acc;
    }, {})).map((item) => ({
        ...item,
        hectareas: item.hectareas.toFixed(2),
        costo: item.costo.toFixed(2),
        fecha: item.fecha ? new Date(`${item.fecha}T00:00:00`).toLocaleDateString('es-HN') : '-',
    })).sort((a, b) => Number(b.costo) - Number(a.costo));

    totalMonto.textContent = `${registros.reduce((sum, item) => sum + (parseFloat(item.costo || 0)), 0).toFixed(2)} Lps`;
    totalMonto.textContent = `${formatearNumeroPreparacion(registros.reduce((sum, item) => sum + (parseFloat(item.costo || 0)), 0))} Lps`;
    totalHa.textContent = `${formatearNumeroPreparacion(hectareasCultivo)} ha`;
    grid.innerHTML = resumen.map(renderResumenActividadCard).join('');
    empty.style.display = 'none';
}

function actualizarResumenPreparacion() {
    const selectCultivos = document.getElementById('prepSueloCultivos');
    const costoUnitario = parseFloat(document.getElementById('prepSueloCostoUnitario').value) || 0;
    const seleccionados = Array.from(selectCultivos.selectedOptions);
    const totalCultivos = seleccionados.length;
    const totalHectareas = seleccionados.reduce((acumulado, option) => {
        return acumulado + (parseFloat(option.dataset.hectareas) || 0);
    }, 0);
    const totalCalculado = totalHectareas * costoUnitario;

    document.getElementById('prepSueloResumenCultivos').textContent = String(totalCultivos);
    document.getElementById('prepSueloResumenHectareas').textContent = `${totalHectareas.toFixed(2)} ha`;
    document.getElementById('prepSueloResumenTotal').textContent = `${totalCalculado.toFixed(2)} Lps`;
}

function cargarActividadesPreparacion() {
    const select = document.getElementById('prepSueloActividad');
    const unidad = document.getElementById('prepSueloUnidad');
    const oldActividad = @json(old('actividad'));

    select.innerHTML = '<option value="">Seleccione...</option>';

    preparacionSueloActividades.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.nombre_completo;
        option.textContent = `${item.nombre} - ${item.actividad}`;
        option.dataset.principal = item.nombre;
        option.dataset.secundaria = item.actividad;
        option.dataset.unidad = item.unidad_medida || 'Servicio';

        if (oldActividad && oldActividad === item.nombre_completo) {
            option.selected = true;
            unidad.value = option.dataset.unidad;
        }

        select.appendChild(option);
    });

    initPreparacionSueloSelect('#prepSueloActividad', 'Selecciona actividad...');
}

document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    initPreparacionSueloSelect('#prepSueloLote', 'Selecciona lote...');
    renderCultivosPreparacion();
    cargarActividadesPreparacion();
    actualizarResumenPreparacion();
    initPreparacionSueloSelect('#prepResumenLote', 'Selecciona lote...');
    renderCultivosResumen();

    document.getElementById('prepSueloLote').addEventListener('change', renderCultivosPreparacion);
    document.getElementById('prepResumenLote').addEventListener('change', renderCultivosResumen);
    document.getElementById('prepResumenCultivo').addEventListener('change', actualizarResumenAplicadoCultivo);
    document.getElementById('prepSueloActividad').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        if (!option) {
            return;
        }

        const unidadInput = document.getElementById('prepSueloUnidad');

        unidadInput.value = option.dataset.unidad || 'Servicio';
    });

    document.getElementById('prepSueloCostoUnitario').addEventListener('input', actualizarResumenPreparacion);
    document.getElementById('prepSueloCultivos').addEventListener('change', actualizarResumenPreparacion);

    if (window.jQuery) {
        jQuery(document).on('select2:select select2:clear', '#prepSueloLote', renderCultivosPreparacion);
        jQuery(document).on('select2:select select2:unselect select2:clear', '#prepSueloCultivos', actualizarResumenPreparacion);
        jQuery(document).on('select2:select select2:clear', '#prepResumenLote', renderCultivosResumen);
        jQuery(document).on('select2:select select2:clear', '#prepResumenCultivo', actualizarResumenAplicadoCultivo);
    }

    document.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.btnEliminarPreparacion');
        if (!deleteButton) {
            return;
        }

        Swal.fire({
            title: 'Anular registro',
            text: 'Esta acción ocultará el registro de la operación activa.',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Motivo de anulación',
            inputPlaceholder: 'Escribe el motivo...',
            inputAttributes: {
                maxlength: 255
            },
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar',
            preConfirm: (value) => {
                if (!value || !String(value).trim()) {
                    Swal.showValidationMessage('Debes ingresar un motivo de anulación.');
                }

                return value;
            }
        }).then(result => {
            if (!result.isConfirmed) {
                return;
            }

            fetch(`/preparacion-suelo/${deleteButton.dataset.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ motivo_anulacion: String(result.value || '').trim() })
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                })
                .then(data => {
                    Swal.fire('Éxito', data.success || 'Registro anulado correctamente.', 'success').then(() => location.reload());
                })
                .catch(error => {
                    Swal.fire('Error', error?.message || 'No se pudo anular el registro.', 'error');
                });
        });
    });
});
</script>
@endsection