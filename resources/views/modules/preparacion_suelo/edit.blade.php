@extends('layouts.main')

@section('titulo', 'Editar Preparación de Suelo')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Editar Preparación de Suelo</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form action="{{ route('preparacion-suelo.update', $consumo->id) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-bold">Lote</label>
                        <input type="text" class="form-control" value="{{ $consumo->cultivo->lote->nombre ?? '-' }}" readonly>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-bold">Cultivo</label>
                        <input type="text" class="form-control" value="{{ $consumo->cultivo->nombre ?? '-' }}" readonly>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-bold">Hectáreas</label>
                        <input type="text" id="prepSueloHectareas" class="form-control" value="{{ agro_number((float) ($consumo->cultivo->hectareas ?? $detalle->cantidad ?? 0), 2, '.', '') }}" readonly>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label fw-bold">Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ old('fecha', optional($consumo->fecha_consumo)->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-lg-5 col-md-6">
                        <label class="form-label fw-bold">Actividad</label>
                        <select name="actividad" id="prepSueloActividadEdit" class="form-select" required>
                            @forelse($actividadesCatalogo as $actividad)
                                <option value="{{ $actividad['nombre_completo'] }}" data-unidad="{{ $actividad['unidad_medida'] }}" {{ old('actividad', $actividadActual) === $actividad['nombre_completo'] ? 'selected' : '' }}>
                                    {{ $actividad['nombre'] }} - {{ $actividad['actividad'] }}
                                </option>
                            @empty
                                <option value="{{ old('actividad', $actividadActual) }}" selected>{{ old('actividad', $actividadActual) }}</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold">Unidad</label>
                        <input type="text" name="unidad_medida" id="prepSueloUnidadEdit" class="form-control" value="{{ old('unidad_medida', $detalle->unidad_medida) }}" readonly>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-bold">Costo unitario</label>
                        <input type="number" name="costo_unitario" id="prepSueloCostoUnitarioEdit" class="form-control" min="0.01" step="0.01" value="{{ old('costo_unitario', $detalle->costo_unitario) }}" required>
                    </div>

                    <div class="col-lg-12">
                        <label class="form-label fw-bold">Observación</label>
                        <input type="text" name="observacion" class="form-control" maxlength="255" value="{{ old('observacion', $observacionActual) }}">
                    </div>

                    <div class="col-lg-12">
                        <div class="alert alert-light border mb-0">
                            <div class="small text-muted">Costo total recalculado</div>
                            <div class="fs-4 fw-bold text-primary" id="prepSueloTotalEdit">0.00 Lps</div>
                        </div>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <a href="{{ route('preparacion-suelo.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        <a href="{{ route('preparacion-suelo.show', $consumo->id) }}" class="btn btn-info text-white">Verificar</a>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hectareas = parseFloat(document.getElementById('prepSueloHectareas').value) || 0;
    const costoInput = document.getElementById('prepSueloCostoUnitarioEdit');
    const totalNode = document.getElementById('prepSueloTotalEdit');
    const actividadSelect = document.getElementById('prepSueloActividadEdit');
    const unidadInput = document.getElementById('prepSueloUnidadEdit');

    function recalcularTotal() {
        const costoUnitario = parseFloat(costoInput.value) || 0;
        totalNode.textContent = `${(hectareas * costoUnitario).toFixed(2)} Lps`;
    }

    function sincronizarUnidad() {
        const option = actividadSelect.options[actividadSelect.selectedIndex];
        if (option?.dataset?.unidad) {
            unidadInput.value = option.dataset.unidad;
        }
    }

    costoInput.addEventListener('input', recalcularTotal);
    actividadSelect.addEventListener('change', sincronizarUnidad);

    sincronizarUnidad();
    recalcularTotal();
});
</script>
@endsection