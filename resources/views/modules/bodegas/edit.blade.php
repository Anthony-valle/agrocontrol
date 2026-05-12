@include('components.modal-header-edit', ['titulo' => 'Editar Almacén'])

<!-- Formulario -->
<form action="{{ route('bodegas.update', $bodega->id) }}" method="POST" id="formEditarBodega">
@csrf
@method('PUT')

<div class="modal-body">
    <div class="row g-3">

        <!-- Código -->
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $bodega->codigo) }}" required>
            @error('codigo')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <!-- Nombre -->
        <div class="col-md-4">
            <label class="form-label">Nombre Almacén</label>
            <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $bodega->nombre) }}" required>
        </div>

        <!-- Responsable -->
        <div class="col-md-4">
            <label class="form-label">Responsable</label>
            <input type="text" name="responsable" class="form-control" value="{{ old('responsable', $bodega->responsable) }}" required>
        </div>

        <!-- Sucursal -->
        <div class="col-md-4">
            <label class="form-label">Sucursal</label>
            <select name="sucursal_id" class="form-select" required>
                <option value="">Seleccione sucursal</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}"
                        {{ old('sucursal_id', $bodega->sucursal_id) == $sucursal->id ? 'selected' : '' }}>
                        {{ $sucursal->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Estado -->
        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="1" {{ old('estado', $bodega->estado) == 1 ? 'selected' : '' }}>
                    Activo
                </option>
                <option value="0" {{ old('estado', $bodega->estado) == 0 ? 'selected' : '' }}>
                    Inactivo
                </option>
            </select>
        </div>

        <!-- Ubicación -->
        <div class="col-md-4">
            <label class="form-label">Dirección / Ubicación</label>
            <input type="text" name="ubicacion" class="form-control" value="{{ old('ubicacion', $bodega->ubicacion) }}" required>
        </div>

    </div>
</div>

<div class="modal-footer">
    <button type="submit" class="btn btn-success">
        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="fa-solid fa-xmark me-1"></i> Cancelar
    </button>
</div>

</form>
