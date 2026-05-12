<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">Nueva Cosecha</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('cosecha.store') }}" method="POST" id="formNuevaCosecha">
    @csrf

    <div class="modal-body">
        <div class="row">

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Cultivo</label>
                <select name="cultivo_id" id="cosecha_cultivo_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($cultivos as $c)
                        <option value="{{ $c->id }}" data-unidad="{{ $c->unidad_medida }}" data-estado="{{ $c->estado }}"
                            {{ old('cultivo_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nombre }} @if($c->estado === 'Cerrado')(CERRADO)@endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Unidad de Medida</label>
                <input type="text" id="cosecha_unidad_medida" name="unidad_medida"
                    class="form-control bg-light" readonly placeholder="Esperando cultivo..."
                    value="{{ old('unidad_medida') }}">
            </div>
            <div class="col-md-4 mb-3" id="cosechaEstadoAlertRow" style="display:none;">
                <label class="small fw-bold">Estado del Cultivo</label>
                <div id="cosechaEstadoAlert" class="alert alert-warning py-2 mb-0" role="alert"></div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Fecha Cosecha</label>
                <input type="date" name="fecha_cosecha" class="form-control"
                    value="{{ old('fecha_cosecha', date('Y-m-d')) }}" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Cantidad Bruta</label>
                <input type="number" step="0.01" name="cantidad_bruta" id="cosecha_cantidad_bruta"
                    class="form-control" value="{{ old('cantidad_bruta') }}" required>
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Descarte</label>
                <input type="number" step="0.01" name="descarte" id="cosecha_descarte"
                    class="form-control" value="{{ old('descarte', 0) }}">
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Cantidad Neta</label>
                <input type="number" step="0.01" id="cosecha_cantidad_neta"
                    class="form-control bg-light" readonly value="{{ old('cantidad_neta') }}">
            </div>

            <div class="col-md-4 mb-3">
                <label class="small fw-bold">Disponible</label>
                <input type="number" step="0.01" id="cosecha_cantidad_disponible"
                    class="form-control bg-light" readonly value="{{ old('cantidad_neta') }}">
            </div>

            <div class="col-md-8 mb-3">
                <label class="small fw-bold">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
            </div>

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar Cosecha</button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectCultivo = document.getElementById('cosecha_cultivo_id');
    const inputUnidad = document.getElementById('cosecha_unidad_medida');
    const cantidadBruta = document.getElementById('cosecha_cantidad_bruta');
    const descarte = document.getElementById('cosecha_descarte');
    const cantidadNeta = document.getElementById('cosecha_cantidad_neta');
    const cantidadDisponible = document.getElementById('cosecha_cantidad_disponible');

    function actualizarUnidadMedida() {
        if (!selectCultivo || !inputUnidad) return;
        const unidad = selectCultivo.options[selectCultivo.selectedIndex]?.dataset.unidad || '';
        inputUnidad.value = unidad;
    }

    function actualizarCantidades() {
        if (!cantidadBruta || !descarte || !cantidadNeta || !cantidadDisponible) return;

        const bruta = parseFloat(cantidadBruta.value) || 0;
        const desc = parseFloat(descarte.value) || 0;
        let neta = bruta - desc;
        if (neta < 0) neta = 0;

        cantidadNeta.value = neta.toFixed(2);
        cantidadDisponible.value = neta.toFixed(2);
    }

    function actualizarEstadoCultivo() {
        const estado = selectCultivo.options[selectCultivo.selectedIndex]?.dataset.estado || '';
        const alertRow = document.getElementById('cosechaEstadoAlertRow');
        const alertBox = document.getElementById('cosechaEstadoAlert');
        const submitButton = document.querySelector('#formNuevaCosecha button[type=submit]');

        if (!estado) {
            alertRow.style.display = 'none';
            if (submitButton) submitButton.disabled = false;
            return;
        }

        alertRow.style.display = '';
        if (estado.toLowerCase() === 'cerrado') {
            alertBox.className = 'alert alert-danger py-2 mb-0';
            alertBox.textContent = 'Este cultivo está cerrado. No se puede registrar cosecha.';
            if (submitButton) submitButton.disabled = true;
        } else {
            alertBox.className = 'alert alert-info py-2 mb-0';
            alertBox.textContent = 'Este cultivo está disponible para cosechas.';
            if (submitButton) submitButton.disabled = false;
        }
    }

    if (selectCultivo) {
        selectCultivo.addEventListener('change', actualizarUnidadMedida);
        selectCultivo.addEventListener('change', actualizarEstadoCultivo);
        actualizarUnidadMedida();
        actualizarEstadoCultivo();
    }

    if (cantidadBruta) {
        cantidadBruta.addEventListener('input', actualizarCantidades);
    }

    if (descarte) {
        descarte.addEventListener('input', actualizarCantidades);
    }

    actualizarCantidades();
});
</script>