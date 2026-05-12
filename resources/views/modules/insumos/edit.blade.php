<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="bi bi-plus-square fs-6 me-2"></i> Editar Insumo
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>


<form action="{{ route('insumos.update', $insumo->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label">Código del Insumo</label>
                <input type="text" name="codigo" class="form-control" value="{{ $insumo->codigo }}" required="100">
            </div>

            <div class="col-md-6">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" name="nombre" class="form-control" value="{{ $insumo->nombre }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Ingrediente Activo</label>
                <input type="text" name="ingrediente_activo" class="form-control" value="{{ old('ingrediente_activo', $insumo->ingrediente_activo_resuelto) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Categoría</label>
                <select name="categoria_nombre" class="form-select" required>
                    <option value="" disabled>Seleccione...</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->nombre }}" {{ old('categoria_nombre', $insumo->categoria_nombre_resuelto) == $categoria->nombre ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                    @if($insumo->categoria_nombre_resuelto && !$categorias->contains('nombre', $insumo->categoria_nombre_resuelto))
                        <option value="{{ $insumo->categoria_nombre_resuelto }}" selected>{{ $insumo->categoria_nombre_resuelto }}</option>
                    @endif

                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Unidad de Medida (U.M)</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="L" {{ $insumo->unidad_medida == 'L' ? 'selected' : '' }}>Litros (L)</option>
                    <option value="Kg" {{ $insumo->unidad_medida == 'Kg' ? 'selected' : '' }}>Kilogramos (Kg)</option>
                    <option value="Unidad" {{ $insumo->unidad_medida == 'Unidad' ? 'selected' : '' }}>Unidad</option>
                    <option value="Saco" {{ $insumo->unidad_medida == 'Saco' ? 'selected' : '' }}>Saco</option>
                </select>
            </div>

            @if($soportaSucursal ?? false)
                <div class="col-md-4">
                    <label class="form-label">Sucursal</label>
                    <select name="sucursal_id" class="form-select" required>
                        <option value="" disabled>Seleccione...</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" {{ $sucursal->id == $insumo->sucursal_id ? 'selected' : '' }}>
                                {{ $sucursal->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($soportaStockMinimo ?? false)
                <div class="col-md-4">
                    <label class="form-label">Stock Mínimo</label>
                    <input type="number" name="stock_minimo" class="form-control" value="{{ $insumo->stock_minimo ?? 0 }}" min="0">
                </div>
            @endif

            @if($soportaEstado ?? false)
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" required>
                        <option value="1" {{ ($insumo->estado_resuelto ?? true) ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ !($insumo->estado_resuelto ?? true) ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            @endif

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> Actualizar Insumo
        </button>
    </div>
</form>
