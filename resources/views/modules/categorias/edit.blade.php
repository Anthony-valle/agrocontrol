<div class="modal-header bg-warning text-white">
    <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i> Editar Categoría</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('categorias.update', $categoria->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        {{-- Nombre --}}
        <div class="mb-3">
            <label class="form-label">Nombre de la categoría</label>
            <input type="text" name="nombre" class="form-control" required maxlength="20" value="{{ old('nombre', $categoria->nombre) }}">
        </div>

        @if($soportaEstado ?? false)
            <div class="mb-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select" required>
                    <option value="1" {{ old('estado', $categoria->estado) == 1 ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ old('estado', $categoria->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
        @endif

        {{-- Sucursal --}}
        <div class="mb-3">
            <label class="form-label">Sucursal</label>
            <select name="sucursal_id" class="form-select" required>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" {{ old('sucursal_id', $categoria->sucursal_id) == $sucursal->id ? 'selected' : '' }}>
                        {{ $sucursal->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-warning">Actualizar</button>
    </div>
</form>