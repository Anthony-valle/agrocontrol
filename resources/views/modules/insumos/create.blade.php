<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="bi bi-plus-square me-2"></i> Registrar Insumo
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('insumos.store') }}" method="POST">
    @csrf
    <div class="modal-body">
        <div class="row g-3">

            {{-- Código --}}
            <div class="col-md-3">
                <label class="form-label">Código</label>
                <input type="text" name="codigo" class="form-control @error('codigo') is-invalid @enderror"
                       value="{{ old('codigo') }}" placeholder="Ej: INS-0001" required>
                @error('codigo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Nombre --}}
            <div class="col-md-5">
                <label class="form-label">Nombre del Insumo</label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre') }}" placeholder="Ej: Urea 46%" required>
                @error('nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Ingrediente Activo --}}
            <div class="col-md-4">
                <label class="form-label">Ingrediente Activo</label>
                <input type="text" name="ingrediente_activo" class="form-control"
                       value="{{ old('ingrediente_activo') }}" placeholder="Ej: Nitrógeno">
            </div>

            {{-- Categoría --}}
            <div class="col-md-4">
                <label class="form-label">Categoría</label>
                <select name="categoria_nombre" class="form-select @error('categoria_nombre') is-invalid @enderror" required>
                    <option value="" disabled {{ old('categoria_nombre') ? '' : 'selected' }}>Seleccione...</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->nombre }}" {{ old('categoria_nombre') == $categoria->nombre ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('categoria_nombre')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            @if($soportaSucursal ?? false)
                <div class="col-md-4">
                    <label class="form-label">Sucursal</label>
                    <select name="sucursal_id" class="form-select @error('sucursal_id') is-invalid @enderror" required>
                        <option value="" disabled {{ old('sucursal_id') ? '' : 'selected' }}>Seleccione...</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" {{ old('sucursal_id') == $sucursal->id ? 'selected' : '' }}>
                                {{ $sucursal->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Unidad de Medida --}}
            <div class="col-md-4">
                <label class="form-label">Unidad de Medida</label>
                <select name="unidad_medida" class="form-select" required>
                    <option value="Kg" {{ old('unidad_medida') == 'Kg' ? 'selected' : '' }}>Kg</option>
                    <option value="L" {{ old('unidad_medida') == 'L' ? 'selected' : '' }}>Litros (L)</option>
                    <option value="Unidad" {{ old('unidad_medida') == 'Unidad' ? 'selected' : '' }}>Unidad</option>
                    <option value="Saco" {{ old('unidad_medida') == 'Saco' ? 'selected' : '' }}>Saco</option>
                </select>
            </div>

            @if($soportaStockMinimo ?? false)
                <div class="col-md-4">
                    <label class="form-label">Stock Mínimo</label>
                    <input type="number" name="stock_minimo" class="form-control" value="{{ old('stock_minimo', 0) }}" min="0">
                </div>
            @endif

            @if($soportaEstado ?? false)
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="1" {{ old('estado',1) == 1 ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ old('estado') == 0 ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
            @endif

        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar Insumo</button>
    </div>
</form>