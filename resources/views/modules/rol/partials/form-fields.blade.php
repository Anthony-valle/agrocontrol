@php
    $rolItem = $rolItem ?? null;
@endphp

<div class="modal-body">
    <div class="mb-3">
        <label for="rol_nombre" class="form-label">Nombre</label>
        <input
            type="text"
            name="nombre"
            id="rol_nombre"
            class="form-control"
            value="{{ old('nombre', $rolItem->nombre ?? '') }}"
            placeholder="Ej: Supervisor"
            required>
    </div>

    <div class="mb-3">
        <label for="rol_descripcion" class="form-label">Descripción</label>
        <textarea
            name="descripcion"
            id="rol_descripcion"
            class="form-control"
            rows="3"
            placeholder="Describe las responsabilidades del rol">{{ old('descripcion', $rolItem->descripcion ?? '') }}</textarea>
    </div>
</div>