<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Actividad de Preparación
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('preparacion-suelo-actividades.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Código</label>
                <input type="text" class="form-control" value="Autogenerado" readonly>
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">Actividad Principal</label>
                <input type="text" name="nombre" class="form-control" required maxlength="60" placeholder="Ej: Mecanización">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Actividad / Desglose</label>
                <input type="text" name="actividad_secundaria" class="form-control" required maxlength="60" placeholder="Ej: Romeplow">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="" selected disabled>Seleccione...</option>
                    <option value="Servicio">Servicio</option>
                    <option value="Hora Máquina">Hora Máquina</option>
                    <option value="Hectárea">Hectárea</option>
                    <option value="Jornal">Jornal</option>
                    <option value="Unidad">Unidad</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control" maxlength="150" placeholder="Opcional">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>