<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Mano de Obra
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('labores.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Código SAP</label>
                <input type="text" class="form-control" value="Autogenerado" readonly>
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">Nombre de Actividad (Primaria)</label>
                <input type="text" name="nombre" id="actividad_primaria" class="form-control" required maxlength="50" placeholder="Ej: Practicas Culturales">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Desglose (Actividad Secundaria)</label>
                <input type="text" name="actividad_secundaria" id="actividad_secundaria" class="form-control" required maxlength="30" placeholder="Ej: Poda">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="" selected disabled>Seleccione...</option>
                    <option value="Jornal">Jornal</option>
                    <option value="Hora">Hora</option>
                    <option value="Hectárea">Hectárea</option>
                </select>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">Costo Unitario</label>
                <input type="number" step="0.01" name="costo_unitario" class="form-control" required min="0">
            </div>

            {{-- Estado --}}
            <div class="col-md-2 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" {{ old('estado', 1) == 1 ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ old('estado') == 0 ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="col-md-5 mb-3">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control" maxlength="100">
            </div>
            
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>