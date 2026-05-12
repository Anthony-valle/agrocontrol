<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Categoría
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('categorias.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        {{-- Nombre --}}
        <div class="mb-3">
            <label class="form-label">Nombre de la categoría</label>
            <input type="text" name="nombre" class="form-control" required maxlength="50">
        </div>
        @if($soportaEstado ?? false)
            <div class="md-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" {{ old('estado', 1) == 1 ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ old('estado') == 0 ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
        @endif
            {{-- Sucursal --}}
            <div class="mb-3">
                <label class="form-label">Sucursal</label>
                <select name="sucursal_id" class="form-select" required>
                    <option value="" selected disabled>Seleccione...</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
