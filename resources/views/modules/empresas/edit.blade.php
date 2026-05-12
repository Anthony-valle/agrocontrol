@include('components.modal-header-edit', ['titulo' => 'Editar Empresa'])

<form action="{{ route('empresas.update', $empresa->id) }}" method="POST" enctype="multipart/form-data" id="formEditarEmpresa">
    @csrf
    @method('PUT')

    @include('modules.empresas.partials.form-fields', ['empresaItem' => $empresa])

    <div class="modal-footer">
        <button type="submit" class="btn btn-warning">Actualizar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </div>
</form>
