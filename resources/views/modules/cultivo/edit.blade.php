@include('components.modal-header-edit', ['titulo' => 'Editar Cultivo'])

<form action="{{ route('cultivo.update', $cultivo->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body" style="color: black">
        <div id="alertaHectareas" class="alert alert-danger d-none"></div>
        <div class="row">
            <!-- Código -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" value="{{ $cultivo->codigo }}" required>
            </div>
            <!-- Nombre -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Nombre del Cultivo</label>
                <input type="text" name="nombre" class="form-control" value="{{ $cultivo->nombre }}" required>
            </div>
            <!-- Lote -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Lote</label>
                <select name="lotes_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($lotes as $item)
                        <option value="{{ $item->id }}" {{ $cultivo->lotes_id == $item->id ? 'selected' : '' }}>{{ $item->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Variedad -->
            <div class="col-md-3 mb-3">
                <label class="form-label">Variedad</label>
                <input type="text" name="variedad" class="form-control" value="{{ $cultivo->variedad }}" required>
            </div>
            <!-- Ciclo -->
            <div class="col-md-1 mb-3">
                <label class="form-label">Ciclo</label>
                <input type="text" name="ciclo" class="form-control" value="{{ $cultivo->ciclo }}" required>
            </div>
            <!-- Fecha Siembra -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Fecha Siembra</label>
                <input type="date" name="fecha_siembra" id="fecha_siembra" class="form-control" value="{{ $cultivo->fecha_siembra }}" required>
            </div>
            <!-- Duración (días) -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Duración (días)</label>
                <input type="number" name="duracion_ciclo" id="duracion_ciclo" class="form-control" value="{{ $cultivo->duracion_ciclo }}" placeholder="Ej: 130" required>
            </div>
            <!-- Hectáreas sembradas -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Hectáreas sembradas</label>
                <input type="number" name="hectareas" class="form-control" step="0.01" value="{{ $cultivo->hectareas }}" placeholder="Ej: 2.5">
            </div>
            <!-- Rendimiento estimado -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Rendimiento Estimado (kg/ha)</label>
                <input type="number" name="cosecha_estimada" class="form-control" step="0.01" value="{{ $cultivo->cosecha_estimada }}" placeholder="Ej: 4500">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <optgroup label="Peso/Masa">
                        <option value="kg" {{ $cultivo->unidad_medida == 'kg' ? 'selected' : '' }}>Kilogramos (kg)</option>
                        <option value="lb" {{ $cultivo->unidad_medida == 'lb' ? 'selected' : '' }}>Libras (lb)</option>
                        <option value="t" {{ $cultivo->unidad_medida == 't' ? 'selected' : '' }}>Toneladas (t)</option>
                        <option value="qq" {{ $cultivo->unidad_medida == 'qq' ? 'selected' : '' }}>Quintales (qq)</option>
                        <option value="g" {{ $cultivo->unidad_medida == 'g' ? 'selected' : '' }}>Gramos (g)</option>
                    </optgroup>
                </select>
            </div>
            <!-- Estado -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="Activo" {{ $cultivo->estado == 'Activo' ? 'selected' : '' }}>Activo</option>
                    <option value="Inactivo" {{ $cultivo->estado == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <!-- Observaciones -->
            <div class="col-md-8 mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales...">{{ $cultivo->observaciones }}</textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnGuardarCultivo">Actualizar</button>
    </div>
</form>

<script>
document.addEventListener('input', function(e) {
    if(e.target.id === 'fecha_siembra' || e.target.id === 'duracion_ciclo') {
        const fechaSiembraInput = document.getElementById('fecha_siembra');
        const duracionInput = document.getElementById('duracion_ciclo');
        const fechaCosechaInput = document.getElementById('fecha_cosecha');
        const textoCosecha = document.getElementById('texto_cosecha');

        if(!fechaSiembraInput || !duracionInput || !fechaCosechaInput || !textoCosecha) return;

        const fechaVal = fechaSiembraInput.value;
        const duracionVal = parseInt(duracionInput.value);

        if(!fechaVal || isNaN(duracionVal)) {
            fechaCosechaInput.value = '';
            textoCosecha.innerText = '';
            return;
        }

        const [year, month, day] = fechaVal.split('-').map(Number);
        const fecha = new Date(year, month - 1, day);
        fecha.setDate(fecha.getDate() + duracionVal);

        const yyyy = fecha.getFullYear();
        const mm = String(fecha.getMonth() + 1).padStart(2, '0');
        const dd = String(fecha.getDate()).padStart(2, '0');

        textoCosecha.innerText = `Fecha estimada de cosecha: ${dd}/${mm}/${yyyy}`;
        fechaCosechaInput.value = `${yyyy}-${mm}-${dd}`;
    }
});

// Validación de hectáreas vs área del lote
const lotesData = @json($lotes);
let cultivosPorLote = {};
fetch('/api/lotes/hectareas-ocupadas')
    .then(res => res.json())
    .then(data => { cultivosPorLote = data; });

document.querySelector('select[name="lotes_id"]').addEventListener('change', validarHectareas);
document.querySelector('input[name="hectareas"]').addEventListener('input', validarHectareas);

function validarHectareas() {
    const loteId = document.querySelector('select[name="lotes_id"]').value;
    const hectareasInput = parseFloat(document.querySelector('input[name="hectareas"]').value) || 0;
    const alerta = document.getElementById('alertaHectareas');
    const btnGuardar = document.getElementById('btnGuardarCultivo');
    if (!loteId) { alerta.classList.add('d-none'); btnGuardar.disabled = false; return; }
    const lote = lotesData.find(l => l.id == loteId);
    const areaLote = lote ? parseFloat(lote.area) : 0;
    const ocupadas = cultivosPorLote[loteId] ? parseFloat(cultivosPorLote[loteId]) : 0;
    const total = ocupadas + hectareasInput;
    if (total > areaLote) {
        alerta.textContent = `¡Advertencia! La suma de hectáreas (${total}) excede el área del lote (${areaLote}).`;
        alerta.classList.remove('d-none');
        btnGuardar.disabled = true;
    } else {
        alerta.classList.add('d-none');
        btnGuardar.disabled = false;
    }
}
</script>