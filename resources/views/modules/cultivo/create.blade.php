<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Cultivo
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<form action="{{ route('cultivo.store') }}" method="POST">
    @csrf
    <div class="modal-body" style="color: black">
        <div id="alertaHectareas" class="alert alert-danger d-none"></div>
        <div id="infoHectareas" class="alert alert-info d-none mb-3"></div>
        <div class="row">

            <!-- Código -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" required>
            </div>

            <!-- Nombre -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Nombre del Cultivo</label>
                <input type="text" name="nombre" class="form-control" required>
            </div>

            <!-- Lote -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Lote</label>
                <select name="lotes_id" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($lotes as $item)
                        <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Variedad -->
            <div class="col-md-3 mb-3">
                <label class="form-label">Variedad</label>
                <input type="text" name="variedad" class="form-control" required>
            </div>

            <!-- Ciclo -->
            <div class="col-md-1 mb-3">
                <label class="form-label">Ciclo</label>
                <input type="text" name="ciclo" class="form-control" required>
            </div>

            <!-- Fecha Siembra -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Fecha Siembra</label>
                <input type="date" name="fecha_siembra" id="fecha_siembra" class="form-control" required>
            </div>

            <!-- Duración (días) -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Duración (días)</label>
                <input type="number" name="duracion_ciclo" id="duracion_ciclo" class="form-control" placeholder="Ej: 130" required>
            </div>

            <!-- Hectáreas sembradas -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Hectáreas sembradas</label>
                <input type="number" name="hectareas" class="form-control" step="0.01" placeholder="Ej: 2.5">
            </div>

            <!-- Rendimiento estimado -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Rendimiento Estimado (kg/ha)</label>
                <input type="number" name="cosecha_estimada" class="form-control" step="0.01" placeholder="Ej: 4500">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="">Seleccione...</option>
                    <optgroup label="Peso/Masa">
                        <option value="kg">Kilogramos (kg)</option>
                        <option value="lb">Libras (lb)</option>
                        <option value="t">Toneladas (t)</option>
                        <option value="qq">Quintales (qq)</option>
                        <option value="g">Gramos (g)</option>
                    </optgroup>
                </select>
            </div>

            <!-- Estado -->
            <div class="col-md-4 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="Activo" selected>Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>
                        <!-- Observaciones -->
            <div class="col-md-8 mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
            </div>

        </div>
    </div>


    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnGuardarCultivo">Guardar</button>
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

        // Crear fecha correctamente
        const [year, month, day] = fechaVal.split('-').map(Number);
        const fecha = new Date(year, month - 1, day);
        fecha.setDate(fecha.getDate() + duracionVal);

        const yyyy = fecha.getFullYear();
        const mm = String(fecha.getMonth() + 1).padStart(2, '0');
        const dd = String(fecha.getDate()).padStart(2, '0');

        // Actualizar input y texto
        // Solo texto legible en DD/MM/YYYY
        textoCosecha.innerText = `Fecha estimada de cosecha: ${dd}/${mm}/${yyyy}`;

        // Input de fecha para enviar al servidor en formato YYYY-MM-DD
        fechaCosechaInput.value = `${yyyy}-${mm}-${dd}`;
    }
});

// Validación de hectáreas vs área del lote
const lotesData = @json($lotes);
let cultivosPorLote = {};

const loteSelect = document.querySelector('select[name="lotes_id"]');
const hectareasField = document.querySelector('input[name="hectareas"]');
const alerta = document.getElementById('alertaHectareas');
const info = document.getElementById('infoHectareas');
const btnGuardar = document.getElementById('btnGuardarCultivo');

// Cargar hectáreas ocupadas por lote vía AJAX
fetch('/api/lotes/hectareas-ocupadas')
    .then(res => res.json())
    .then(data => {
        cultivosPorLote = data || {};
        validarHectareas();
    })
    .catch(() => {
        cultivosPorLote = {};
        validarHectareas();
    });

loteSelect.addEventListener('change', validarHectareas);
hectareasField.addEventListener('input', validarHectareas);

function validarHectareas() {
    const loteId = loteSelect.value;
    const hectareasInput = parseFloat(hectareasField.value) || 0;

    if (!loteId) {
        alerta.classList.add('d-none');
        info.classList.add('d-none');
        btnGuardar.disabled = false;
        hectareasField.removeAttribute('max');
        return;
    }

    const lote = lotesData.find(l => l.id == loteId);
    const areaLote = lote ? parseFloat(lote.area) : 0;
    const ocupadas = cultivosPorLote[loteId] ? parseFloat(cultivosPorLote[loteId]) : 0;

    const disponibles = Math.max(0, areaLote - ocupadas);
    const total = ocupadas + hectareasInput;

    info.textContent = `Área lote: ${areaLote.toFixed(2)} ha | Ya ocupadas: ${ocupadas.toFixed(2)} ha | Disponibles: ${disponibles.toFixed(2)} ha`;
    info.classList.remove('d-none');

    hectareasField.setAttribute('max', disponibles.toFixed(2));

    if (disponibles <= 0) {
        alerta.textContent = 'Este lote ya no tiene hectáreas disponibles para registrar otro cultivo.';
        alerta.classList.remove('d-none');
        btnGuardar.disabled = true;
        return;
    }

    if (hectareasInput > disponibles) {
        alerta.textContent = `No se puede guardar: estás ingresando ${hectareasInput.toFixed(2)} ha y solo hay ${disponibles.toFixed(2)} ha disponibles en el lote.`;
        alerta.classList.remove('d-none');
        btnGuardar.disabled = true;
    } else if (total > areaLote) {
        alerta.textContent = `No se puede guardar: la suma total (${total.toFixed(2)} ha) excede el área del lote (${areaLote.toFixed(2)} ha).`;
        alerta.classList.remove('d-none');
        btnGuardar.disabled = true;
    } else {
        alerta.classList.add('d-none');
        btnGuardar.disabled = false;
    }
});

validarHectareas();
</script>