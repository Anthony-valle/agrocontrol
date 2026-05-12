{{-- Componente para encabezado de modales de edición --}}
{{-- Uso: @include('components.modal-header-edit', ['titulo' => 'Editar Lote']) --}}
<div class="modal-header bg-warning text-white">
    <h5 class="modal-title">
        <i class="fa-solid fa-pen-to-square me-2"></i> {{ $titulo ?? 'Editar' }}
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
