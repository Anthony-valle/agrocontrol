@include('components.modal-header-edit', ['titulo' => 'Editar Sucursal'])

<form action="{{ route('sucursal.update', $sucursal->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal-body">
        <div class="row">

            <!-- Nombre Sucursal -->
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre Sucursal </label>
                <input type="text" name="nombre" id="nombre" class="form-control" value="{{ $sucursal->nombre }}" required>
            </div>

            <!-- Teléfono -->
            <div class="col-md-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control" value="{{ $sucursal->telefono }}">
            </div>

            <!-- Correo -->
            <div class="col-md-5">
                <label for="email" class="form-label">Correo</label>
                <input type="email" name="email" id="email" class="form-control" value="{{ $sucursal->email }}">
            </div>

            <!-- Nombre Responsable -->
            <div class="col-md-4">
                <label for="responsable" class="form-label">Nombre Responsable </label>
                <input type="text" name="responsable" id="responsable" class="form-control" value="{{ $sucursal->responsable }}" required>
            </div>

            <!-- Empresa -->
            <div class="col-md-4">
                <label for="empresa_id" class="form-label">Empresa</label>
                <select name="empresa_id" id="empresa_id" class="form-select" required>
                    <option value="" disabled>Seleccione empresa...</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" {{ $empresa->id == $sucursal->empresa_id ? 'selected' : '' }}>
                            {{ $empresa->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Estado -->
            <div class="col-md-4">
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="1" {{ $sucursal->estado_texto ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ !$sucursal->estado_texto ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <!-- Dirección -->
            <div class="col-md-12">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control" value="{{ $sucursal->direccion }}" required>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
</form>
