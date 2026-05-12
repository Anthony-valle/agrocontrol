<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nuevo Usuario
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form id="formCrearUsuario" action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    @include('modules.usuarios.partials.form-fields', ['modoEdicion' => false])

    <div class="modal-footer">
        <button id="btnGuardar" type="submit" class="btn btn-primary">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </div>
</form>