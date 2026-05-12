<div class="modal-header bg-primary text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-circle-plus me-2"></i> Editar Rol
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>


<form action="{{ route('rol.update', $rol->id) }}" method="POST">
@csrf
@method('PUT')
@include('modules.rol.partials.form-fields', ['rolItem' => $rol])

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-primary">Guardar</button>
</div>
</form>
