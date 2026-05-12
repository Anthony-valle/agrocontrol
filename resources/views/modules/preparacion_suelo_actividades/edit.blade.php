@include('components.modal-header-edit', ['titulo' => 'Editar Actividad de Preparación'])

<form action="{{ route('preparacion-suelo-actividades.update', $actividad->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control" value="{{ $actividad->codigo }}" required maxlength="20">
            </div>

            <div class="col-md-5 mb-3">
                <label class="form-label">Actividad Principal</label>
                <input type="text" name="nombre" class="form-control" value="{{ $actividad->nombre }}" required maxlength="60">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Actividad / Desglose</label>
                <input type="text" name="actividad_secundaria" class="form-control" value="{{ $actividad->actividad_secundaria }}" required maxlength="60">
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="" disabled>Seleccione...</option>
                    <option value="Servicio" {{ $actividad->unidad_medida === 'Servicio' ? 'selected' : '' }}>Servicio</option>
                    <option value="Hora Máquina" {{ $actividad->unidad_medida === 'Hora Máquina' ? 'selected' : '' }}>Hora Máquina</option>
                    <option value="Hectárea" {{ $actividad->unidad_medida === 'Hectárea' ? 'selected' : '' }}>Hectárea</option>
                    <option value="Jornal" {{ $actividad->unidad_medida === 'Jornal' ? 'selected' : '' }}>Jornal</option>
                    <option value="Unidad" {{ $actividad->unidad_medida === 'Unidad' ? 'selected' : '' }}>Unidad</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" {{ (string) old('estado', $actividad->estado) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('estado', $actividad->estado) === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Observaciones</label>
                <input type="text" name="observaciones" class="form-control" value="{{ $actividad->observaciones }}" maxlength="150">
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
</form>