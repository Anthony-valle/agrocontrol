<div class="modal-body">
    <div class="row g-3">

        <div class="col-md-6">
            <label class="form-label">Nombre completo</label>
            <input
                type="text"
                name="nombre_completo"
                class="form-control"
                value="{{ old('nombre_completo', $user->nombre_completo ?? '') }}"
                required
                maxlength="50"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Usuario</label>
            <input
                type="text"
                name="usuario"
                class="form-control"
                value="{{ old('usuario', $user->usuario ?? '') }}"
                required
                maxlength="20"
            >
        </div>

        <div class="col-md-3">
            <label class="form-label">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                @if(empty($modoEdicion)) required @endif
            >
            @if(!empty($modoEdicion))
                <small class="text-muted">Si no desea cambiar la contraseña, deje vacío.</small>
            @endif
        </div>

        <div class="col-md-4">
            <label class="form-label">Confirmar contraseña</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                @if(empty($modoEdicion)) required @endif
            >
            @if(empty($modoEdicion))
                <small id="password_error" class="text-danger" style="display:none;">Las contraseñas no coinciden</small>
            @endif
        </div>

        <div class="col-md-4">
            <label class="form-label">Sucursal</label>
            <select name="sucursal_id" class="form-select" required>
                <option value="">Seleccione sucursal</option>
                @foreach($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" {{ (string) old('sucursal_id', $user->sucursal_id ?? '') === (string) $sucursal->id ? 'selected' : '' }}>
                        {{ $sucursal->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Rol</label>
            <select name="rol_id" id="usuario_rol_id" class="form-select" required>
                <option value="">Seleccione rol</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->id }}" data-role-name="{{ strtolower(trim((string) $rol->nombre)) }}" {{ (string) old('rol_id', $user->rol_id ?? '') === (string) $rol->id ? 'selected' : '' }}>
                        {{ $rol->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-8">
            <label class="form-label">Imagen de usuario</label>
            <input type="file" name="imagen_usuario" class="form-control" accept="image/*">
            <div class="form-text">Formatos permitidos: JPG, PNG.</div>
        </div>

        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select" required>
                <option value="1" {{ (string) old('estado', isset($user) ? (int) $user->estado : 1) === '1' ? 'selected' : '' }}>Activo</option>
                <option value="0" {{ (string) old('estado', isset($user) ? (int) $user->estado : 1) === '0' ? 'selected' : '' }}>Inactivo</option>
            </select>
        </div>

    </div>
</div>