@include('components.modal-header-edit', ['titulo' => 'Editar Mano de Obra'])

<form action="{{ route('labores.update', $labore->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Código SAP</label>
                <input type="text" name="codigo" class="form-control" value="{{ $labore->codigo }}" required maxlength="20">
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">Nombre de Actividad (Primaria)</label>
                <input type="text" name="nombre" class="form-control" value="{{ $labore->nombre }}" required maxlength="50" placeholder="Ej: Practicas Culturales">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Desglose (Actividad Secundaria)</label>
                <input type="text" name="actividad_secundaria" class="form-control" value="{{ $labore->actividad_secundaria }}" required maxlength="30" placeholder="Ej: Poda">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="" disabled>Seleccione...</option>
                    <option value="Jornal" {{ $labore->unidad_medida == 'Jornal' ? 'selected' : '' }}>Jornal</option>
                    <option value="Hora" {{ $labore->unidad_medida == 'Hora' ? 'selected' : '' }}>Hora</option>
                    <option value="Hectárea" {{ $labore->unidad_medida == 'Hectárea' ? 'selected' : '' }}>Hectárea</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Costo Unitario</label>
                <input type="number" step="0.01" name="costo_unitario" class="form-control" value="{{ $labore->costo_unitario }}" required min="0">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" {{ (string) old('estado', $labore->estado) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('estado', $labore->estado) === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control" value="{{ $labore->observaciones }}" maxlength="100">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
</form>