<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Sucursal
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<!-- Formulario -->
<form action="{{ route('sucursal.store') }}" method="POST" id="formCrearSucursal">
    @csrf
    <div class="modal-body">
        <div class="row g-3">

            <!-- Nombre Sucursal -->
            <div class="col-md-4">
                <label for="nombre" class="form-label">Nombre Sucursal </label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>

            <!-- Teléfono -->
            <div class="col-md-3">
                <label for="telefono" class="form-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" class="form-control">
            </div>

            <!-- Correo -->
            <div class="col-md-5">
                <label for="email" class="form-label">Correo</label>
                <input type="email" name="email" id="email" class="form-control">
            </div>

            <!-- Nombre Responsable -->
            <div class="col-md-4">
                <label for="responsable" class="form-label">Nombre Responsable </label>
                <input type="text" name="responsable" id="responsable" class="form-control" required>
            </div>

            <!-- Empresa -->
            <div class="col-md-4">
                <label for="empresa_id" class="form-label">Empresa </label>
                <select name="empresa_id" id="empresa_id" class="form-select" required>
                    <option value="" disabled selected>Seleccione empresa</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Estado -->
            <div class="col-md-4">
                <label for="estado" class="form-label">Estado</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>

             <!-- Dirección -->
            <div class="col-md-12">
                <label for="direccion" class="form-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" class="form-control" required>
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
