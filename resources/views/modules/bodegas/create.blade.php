<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Almacén
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<!-- Formulario -->
<form action="{{ route('bodegas.store') }}" method="POST" id="formCrearBodega">
@csrf

<div class="modal-body">
    <div class="row g-3">

        {{-- Código --}}
        <div class="col-md-4">
            <label class="form-label">Código</label>
            <input type="text"
                   name="codigo"
                   class="form-control"
                   placeholder="Ej: 1000"
                   value="{{ old('codigo') }}"
                   required>
            @error('codigo')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <!-- Nombre Almacén -->
        <div class="col-md-4">
            <label class="form-label">Nombre Almacén</label>
            <input type="text" name="nombre" class="form-control" required>
        </div>

        <!-- Responsable -->
        <div class="col-md-4">
            <label class="form-label">Responsable</label>
            <input type="text" name="responsable" class="form-control" required>
        </div>

        <!-- Sucursal -->
        <div class="col-md-4">
            <label class="form-label">Sucursal</label>
            <select name="sucursal_id" class="form-select" required>
                <option value="" disabled selected>Seleccione sucursal</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}">
                        {{ $sucursal->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Estado -->
        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
            </select>
        </div>

        <!-- Dirección -->
        <div class="col-md-4">
            <label class="form-label">Dirección</label>
            <input type="text" name="ubicacion" class="form-control" required>
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
