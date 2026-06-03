@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <style>
        .purchase-request-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            box-shadow: 0 0.7rem 1.5rem rgba(15, 23, 42, 0.06);
        }

        .purchase-request-paper {
            background: linear-gradient(180deg, #fffdfa 0%, #ffffff 100%);
            border: 1px solid rgba(122, 91, 38, 0.16);
            border-radius: 1rem;
        }

        .purchase-request-header {
            border-bottom: 2px solid rgba(122, 91, 38, 0.12);
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
        }

        .purchase-request-table td,
        .purchase-request-table th {
            vertical-align: middle;
            font-size: 0.92rem;
        }
    </style>

    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Llena la solicitud y mira en vivo cómo quedará el formato para imprimir.</p>
    </div>

    <section class="section">
        <div class="row g-4">
            <div class="col-12">
                <div class="card purchase-request-card purchase-request-paper h-100">
                    <div class="card-body p-4">
                        <div class="agro-table-toolbar mb-4">
                            <div class="agro-table-toolbar-group">
                                <div>
                                    <h5 class="card-title mb-0">Formulario de solicitud</h5>
                                    <small class="text-muted">Completa el documento y envialo al historial de revisión.</small>
                                </div>
                            </div>
                            <div class="agro-toolbar-actions">
                                <a href="{{ route('compras.solicitudes.index') }}" class="btn btn-primary btn-sm"><i class="fa fa-list me-1"></i> Ver historial</a>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('compras.solicitudes.store') }}" class="row g-3" id="solicitudCompraForm">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Departamento solicitante</label>
                                <input type="text" name="departamento" id="formDepartamento" class="form-control" maxlength="120" value="{{ old('departamento', auth()->user()->rol->nombre ?? '') }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Prioridad</label>
                                <select name="prioridad" id="formPrioridad" class="form-select" required>
                                    <option value="media" @selected(old('prioridad', 'media') === 'media')>Media</option>
                                    <option value="baja" @selected(old('prioridad') === 'baja')>Baja</option>
                                    <option value="alta" @selected(old('prioridad') === 'alta')>Alta</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bodega destino</label>
                                <select name="bodega_destino_id" id="formBodega" class="form-select">
                                    <option value="">Selecciona una bodega</option>
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}" @selected((string) old('bodega_destino_id') === (string) $bodega->id)>
                                            {{ $bodega->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Proyecto, obra o motivo</label>
                                <input type="text" name="asunto" id="formAsunto" class="form-control" maxlength="150" value="{{ old('asunto') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha requerida</label>
                                <input type="date" name="fecha_requerida" id="formFecha" class="form-control" value="{{ old('fecha_requerida') }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Observaciones generales</label>
                                <textarea name="descripcion" id="formDescripcion" class="form-control" rows="3" maxlength="3000" required>{{ old('descripcion') }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Detalle de materiales requeridos</label>
                                <div class="table-responsive agro-table-shell">
                                    <table class="table table-hover table-sm align-middle agro-table purchase-request-table mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 44%;">Descripción</th>
                                                <th style="width: 20%;">Categoría</th>
                                                <th style="width: 14%;">Unidad</th>
                                                <th style="width: 14%;">Cantidad</th>
                                                <th style="width: 6%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="detalleSolicitudBody">
                                            @php
                                                $oldDetalles = old('detalles', [[
                                                    'descripcion' => '',
                                                    'categoria' => '',
                                                    'unidad' => '',
                                                    'cantidad' => '',
                                                ]]);
                                            @endphp
                                            @foreach($oldDetalles as $index => $detalle)
                                                <tr>
                                                    <td><input type="text" name="detalles[{{ $index }}][descripcion]" class="form-control form-control-sm detalle-descripcion-input" maxlength="190" value="{{ $detalle['descripcion'] ?? '' }}" required></td>
                                                    <td>
                                                        <select name="detalles[{{ $index }}][categoria]" class="form-select form-select-sm detalle-categoria-select">
                                                            <option value="">Selecciona una categoría</option>
                                                            @foreach($categorias as $categoria)
                                                                <option value="{{ $categoria->nombre }}" @selected((string) ($detalle['categoria'] ?? '') === (string) $categoria->nombre)>
                                                                    {{ $categoria->nombre }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="text" name="detalles[{{ $index }}][unidad]" class="form-control form-control-sm detalle-unidad-input" maxlength="60" value="{{ $detalle['unidad'] ?? '' }}"></td>
                                                    <td><input type="number" step="0.01" min="0.01" name="detalles[{{ $index }}][cantidad]" class="form-control form-control-sm detalle-cantidad-input" value="{{ $detalle['cantidad'] ?? '' }}" required></td>
                                                    <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm detalle-remove-row"><i class="bi bi-x-lg"></i></button></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="agro-table-toolbar mt-2 mb-0">
                                    <div class="agro-table-toolbar-group">
                                        <div class="agro-toolbar-records">
                                            <small>detalle de materiales</small>
                                        </div>
                                    </div>
                                    <div class="agro-toolbar-actions">
                                        <button type="button" class="btn btn-primary btn-sm" id="agregarDetalleSolicitud"><i class="bi bi-plus-circle me-1"></i>Agregar renglón</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-1"></i>Enviar solicitud formal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const detalleBody = document.getElementById('detalleSolicitudBody');
        const agregarDetalle = document.getElementById('agregarDetalleSolicitud');

        if (!detalleBody || !agregarDetalle) {
            return;
        }

        const catalogoCategorias = @json($categorias->pluck('nombre')->values());

        function buildCategoryOptions() {
            return ['<option value="">Selecciona una categoría</option>']
                .concat(catalogoCategorias.map(function (categoria) {
                    return '<option value="' + categoria + '">' + categoria + '</option>';
                }))
                .join('');
        }

        function reindexRows() {
            detalleBody.querySelectorAll('tr').forEach(function (row, index) {
                row.querySelectorAll('input, select').forEach(function (field) {
                    field.name = field.name.replace(/detalles\[\d+\]/, 'detalles[' + index + ']');
                });
            });
        }

        agregarDetalle.addEventListener('click', function () {
            const index = detalleBody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><input type="text" name="detalles[' + index + '][descripcion]" class="form-control form-control-sm detalle-descripcion-input" maxlength="190" required></td>' +
                '<td><select name="detalles[' + index + '][categoria]" class="form-select form-select-sm detalle-categoria-select">' + buildCategoryOptions() + '</select></td>' +
                '<td><input type="text" name="detalles[' + index + '][unidad]" class="form-control form-control-sm detalle-unidad-input" maxlength="60"></td>' +
                '<td><input type="number" step="0.01" min="0.01" name="detalles[' + index + '][cantidad]" class="form-control form-control-sm detalle-cantidad-input" required></td>' +
                '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm detalle-remove-row"><i class="bi bi-x-lg"></i></button></td>';
            detalleBody.appendChild(tr);
        });

        detalleBody.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.detalle-remove-row');
            if (!removeButton) {
                return;
            }

            if (detalleBody.querySelectorAll('tr').length === 1) {
                return;
            }

            removeButton.closest('tr').remove();
            reindexRows();
        });
    });
</script>
@endpush
@endsection
