@php
    $empresaItem = $empresaItem ?? null;
    $tiposEmpresa = [
        'Agroindustrias',
        'Agrícola',
        'Ganadera',
        'Agropecuaria',
        'Horticultura',
        'Fruticultura',
        'Forestal',
        'Acuicultura',
        'Servicios agrícolas',
    ];
    $tipoSeleccionado = old('tipo_empresa', $empresaItem->tipo_empresa ?? 'Agroindustrias');
@endphp

<div class="modal-body">
    <div class="row g-3">

        <div class="col-md-4">
            <label for="empresa_nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="empresa_nombre" class="form-control" value="{{ old('nombre', $empresaItem->nombre ?? '') }}" required>
        </div>

        <div class="col-md-4">
            <label for="empresa_rtn" class="form-label">RTN</label>
            <input type="text" name="rtn" id="empresa_rtn" class="form-control" value="{{ old('rtn', $empresaItem->rtn ?? '') }}" required>
        </div>

        <div class="col-md-4">
            <label for="empresa_direccion" class="form-label">Dirección</label>
            <input type="text" name="direccion" id="empresa_direccion" class="form-control" value="{{ old('direccion', $empresaItem->direccion ?? '') }}">
        </div>

        <div class="col-md-4">
            <label for="empresa_telefono" class="form-label">Teléfono</label>
            <input type="text" name="telefono" id="empresa_telefono" class="form-control" value="{{ old('telefono', $empresaItem->telefono ?? '') }}">
        </div>

        <div class="col-md-4">
            <label for="empresa_email" class="form-label">Correo</label>
            <input type="email" name="email" id="empresa_email" class="form-control" value="{{ old('email', $empresaItem->email ?? '') }}">
        </div>

        <div class="col-md-4">
            <label for="empresa_pais" class="form-label">País</label>
            <input type="text" name="pais" id="empresa_pais" class="form-control" value="{{ old('pais', $empresaItem->pais ?? '') }}">
        </div>

        <div class="col-md-3">
            <label for="empresa_departamento" class="form-label">Departamento</label>
            <input type="text" name="departamento" id="empresa_departamento" class="form-control" value="{{ old('departamento', $empresaItem->departamento ?? '') }}">
        </div>

        <div class="col-md-4">
            <label for="empresa_tipo" class="form-label">Tipo de Empresa</label>
            <select name="tipo_empresa" id="empresa_tipo" class="form-select" required>
                @foreach($tiposEmpresa as $tipo)
                    <option value="{{ $tipo }}" {{ $tipoSeleccionado === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-5">
            <label for="empresa_logo" class="form-label">Logo</label>
            <input type="file" name="logo" id="empresa_logo" class="form-control" accept="image/*">

            @if($empresaItem?->logo_url)
                <small class="text-muted d-block mt-2">Logo actual:</small>
                <img src="{{ $empresaItem->logo_url }}" alt="Logo de {{ $empresaItem->nombre }}" class="img-thumbnail mt-1" style="width: 56px; height: 56px; object-fit: cover;">
            @endif
        </div>

    </div>
</div>