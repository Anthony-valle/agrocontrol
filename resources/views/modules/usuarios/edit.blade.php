@include('components.modal-header-edit', ['titulo' => 'Editar Usuario'])

<form id="formEditarUsuario" action="{{ route('usuarios.update', $user->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @include('modules.usuarios.partials.form-fields', ['modoEdicion' => true])

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
</form>

