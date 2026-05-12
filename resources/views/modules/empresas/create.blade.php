<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Nueva Empresa
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('empresas.store') }}" method="POST" enctype="multipart/form-data" id="formCrearEmpresa">
    @csrf
    @include('modules.empresas.partials.form-fields')

    <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </div>
</form>

