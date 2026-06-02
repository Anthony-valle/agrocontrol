@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Usuarios</h1>
    </div><!-- End Page Title -->

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Administrar Usuarios</h5>

                        <!-- Buscador y botón Nuevo Usuario -->
                        <div class="agro-table-toolbar">
                            <div class="agro-table-toolbar-group">
                                <div class="agro-toolbar-records">
                                    <select class="form-select form-select-sm agro-toolbar-select" id="selectPerPage" style="width:auto;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                    </select>
                                    <small class="text-muted">registros</small>
                                </div>
                                <input type="text" class="form-control form-control-sm agro-toolbar-search" id="inputBuscar" placeholder="Buscar usuario...">
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                <i class="fa fa-plus me-1"></i> Nuevo Usuario
                            </button>
                        </div>

                        <!-- MODAL CREAR -->
                        <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content" id="modalContent">
                                    <!-- Se carga create.blade.php vía AJAX -->
                                </div>
                            </div>
                        </div>

                        <!-- MODAL EDITAR -->
                        <div class="modal fade" id="modalUsuarioEdit" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content" id="modalContentEdit">
                                    <!-- Se carga edit.blade.php vía AJAX -->
                                </div>
                            </div>
                        </div>

                        <!-- TABLA USUARIOS -->
                        <div class="table-responsive agro-table-shell">
                            <table class="table table-hover agro-table" id="tablaUsuarios">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Usuario</th>
                                        <th>Rol(es)</th>
                                        <th>Sucursal</th>
                                        <th>Estado</th>
                                        <th>Imagen</th>
                                        <th>Creado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $item)
                                        <tr>
                                            <td>{{ $item->id }}</td>
                                            <td>{{ $item->nombre_completo }}</td>
                                            <td>{{ $item->usuario }}</td>
                                            <td>{{ $item->rol->nombre ?? 'Sin rol' }}</td>
                                            <td>{{ $item->sucursal->nombre ?? 'Sin sucursal' }}</td>
                                            <td>
                                                <span class="badge {{ $item->estado ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $item->estado ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>
                                                <img src="{{ $item->imagen_usuario_url }}" alt="Avatar" class="rounded-circle" width="40" height="40" style="object-fit: cover;" onerror="this.onerror=null;this.src='{{ asset('NiceAdmin/assets/img/default-user-avatar.svg') }}';">
                                            </td>
                                            <td>{{ $item->creador->usuario ?? 'Sistema' }}</td>
                                            <td class="text-center text-nowrap">
                                                <div class="d-inline-flex align-items-center gap-2 flex-nowrap acciones-usuario-wrap">
                                                <a href="#" class="btn btn-warning btn-sm btnEditarUsuario" data-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </a>
                                                @if(auth()->user()?->hasAnyRole(['propietario', 'admin']))
                                                <button type="button" class="btn btn-secondary btn-sm btnRevealTempPassword" data-id="{{ $item->id }}" data-usuario="{{ $item->usuario }}" title="Ver contraseña temporal">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                @endif
                                                @if(auth()->user()?->isSuperUser() || auth()->id() === $item->id)
                                                <button type="button" class="btn btn-info btn-sm btnResetPassword" data-id="{{ $item->id }}" data-usuario="{{ $item->usuario }}" title="Restablecer contraseña">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                                @endif
                                                <button type="button" class="btn btn-danger btn-sm btnEliminarUsuario" data-id="{{ $item->id }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <style>
                        #tablaUsuarios td.text-nowrap {
                            vertical-align: middle;
                        }

                        .acciones-usuario-wrap .btn {
                            min-width: 36px;
                            height: 36px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                        }
                        </style>
                        <!-- End Table -->

                    </div>
                </div>

            </div>
        </div>
    </section>

</main>

<!-- SCRIPT VER CONTRASEÑA TEMPORAL (SOLO PROPIETARIO) -->
<script>
function initUsuarioBodegaConsumoState(scope = document) {
    const rolSelect = scope.querySelector('#usuario_rol_id');
    const bodegaSelect = scope.querySelector('#bodega_id_consumo');
    const bodegaWrap = scope.querySelector('#bodegaConsumoWrap');
    const bodegaHelp = scope.querySelector('#bodegaConsumoHelp');

    if (!rolSelect || !bodegaSelect || !bodegaWrap) {
        return;
    }

    const syncBodegaRequirement = () => {
        const selectedOption = rolSelect.options[rolSelect.selectedIndex];
        const roleName = String(selectedOption?.dataset?.roleName || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '');
        const isNotificador = roleName === 'notificador';

        bodegaSelect.required = isNotificador;
        bodegaSelect.disabled = !isNotificador;
        bodegaWrap.classList.toggle('d-none', !isNotificador);
        bodegaWrap.classList.toggle('border', isNotificador);
        bodegaWrap.classList.toggle('rounded', isNotificador);
        bodegaWrap.classList.toggle('p-2', isNotificador);
        bodegaWrap.classList.toggle('bg-light', isNotificador);

        if (!isNotificador) {
            bodegaSelect.value = '';
        }

        if (bodegaHelp) {
            bodegaHelp.textContent = isNotificador
                ? 'Obligatoria para el rol notificador. Ese usuario solo podrá consumir desde esta bodega.'
                : 'Opcional para otros roles.';
        }
    };

    if (!rolSelect.dataset.boundBodegaConsumo) {
        rolSelect.addEventListener('change', syncBodegaRequirement);
        rolSelect.dataset.boundBodegaConsumo = '1';
    }

    syncBodegaRequirement();
}

document.addEventListener('click', function(e){
    if(!e.target.closest('.btnRevealTempPassword')){
        return;
    }

    const button = e.target.closest('.btnRevealTempPassword');
    const id = button.dataset.id;
    const usuario = button.dataset.usuario || 'usuario';

    Swal.fire({
        title: 'Generar contraseña temporal',
        text: 'Se reemplazará la contraseña actual de ' + usuario + '. ¿Continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        fetch(`/usuarios/${id}/reveal-temporary-password`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw data;
            }
            return data;
        })
        .then((data) => {
            Swal.fire({
                title: 'Contraseña temporal',
                html: '<div class="text-start"><p class="mb-2"><strong>Usuario:</strong> ' + usuario + '</p>' +
                      '<p class="mb-2"><strong>Temporal:</strong> <code>' + data.temporary_password + '</code></p>' +
                      '<p class="small text-muted mb-0">' + (data.warning || '') + '</p></div>',
                icon: 'success',
                confirmButtonText: 'Entendido'
            });
        })
        .catch((error) => {
            Swal.fire('Error', error.message || 'No se pudo generar la contraseña temporal.', 'error');
        });
    });
});
</script>

<!-- SCRIPT TABLA, BUSCADOR Y SELECT -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    const tabla = document.getElementById("tablaUsuarios")?.tBodies?.[0];
    if (!tabla) {
        return;
    }

    const filas = Array.from(tabla.rows);
    const inputBuscar = document.getElementById("inputBuscar");
    const selectPerPage = document.getElementById("selectPerPage");

    function filtrarTabla() {
        const texto = (inputBuscar?.value || "").toLowerCase();
        const perPage = parseInt(selectPerPage?.value || "10", 10);

        let visibles = 0;
        filas.forEach(fila => {
            const textoFila = Array.from(fila.cells).map(c => c.textContent.toLowerCase()).join(" ");
            if (textoFila.includes(texto) && visibles < perPage) {
                fila.style.display = "";
                visibles++;
            } else {
                fila.style.display = "none";
            }
        });
    }

    inputBuscar?.addEventListener("input", filtrarTabla);
    selectPerPage?.addEventListener("change", filtrarTabla);
    filtrarTabla();
});
</script>


<!-- SCRIPT RESTABLECER CONTRASEÑA -->
<script>
document.addEventListener('click', function(e){
    if(!e.target.closest('.btnResetPassword')){
        return;
    }

    const button = e.target.closest('.btnResetPassword');
    const id = button.dataset.id;
    const usuario = button.dataset.usuario || 'usuario';

    Swal.fire({
        title: 'Restablecer contraseña',
        html:
            '<p class="text-muted small mb-2">Usuario: <strong>' + usuario + '</strong></p>' +
            '<input id="swal-new-pass" type="password" class="swal2-input" placeholder="Nueva contraseña">' +
            '<input id="swal-confirm-pass" type="password" class="swal2-input" placeholder="Confirmar contraseña">',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        focusConfirm: false,
        preConfirm: () => {
            const pass = document.getElementById('swal-new-pass').value;
            const confirm = document.getElementById('swal-confirm-pass').value;

            if (!pass || !confirm) {
                Swal.showValidationMessage('Debes completar ambos campos');
                return false;
            }
            if (pass.length < 6) {
                Swal.showValidationMessage('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            if (pass !== confirm) {
                Swal.showValidationMessage('Las contraseñas no coinciden');
                return false;
            }

            return { pass, confirm };
        }
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        const formData = new FormData();
        formData.append('password', result.value.pass);
        formData.append('password_confirmation', result.value.confirm);

        fetch(`/usuarios/${id}/reset-password`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw data;
            }
            return data;
        })
        .then((data) => {
            Swal.fire('Éxito', data.success || 'Contraseña actualizada correctamente.', 'success');
        })
        .catch((error) => {
            if (error && error.errors) {
                let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
                Object.values(error.errors).flat().forEach(msg => {
                    mensajes += `<li>${msg}</li>`;
                });
                mensajes += '</ul>';
                Swal.fire({ title: 'Error de validación', html: mensajes, icon: 'error' });
                return;
            }

            Swal.fire('Error', error.message || 'No se pudo restablecer la contraseña.', 'error');
        });
    });
});
</script>

<!-- SCRIPT MODAL CREAR -->
<script>
document.getElementById('btnAbrirModal').addEventListener('click', function() {
    fetch(`{{ route('usuarios.create') }}?t=${Date.now()}`, {
        cache: 'no-store'
    })
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
            initUsuarioBodegaConsumoState(document.getElementById('modalContent'));

            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('password_confirmation');

            if(password && confirmPassword){
                function validarPassword(){
                    if(confirmPassword.value === ""){
                        confirmPassword.setCustomValidity("");
                        return;
                    }
                    if(password.value !== confirmPassword.value){
                        confirmPassword.setCustomValidity("Contraseñas no coinciden");
                    } else {
                        confirmPassword.setCustomValidity("");
                    }
                }
                password.addEventListener('input', validarPassword);
                confirmPassword.addEventListener('input', validarPassword);
            }

            // Manejador del envío del formulario de crear usuario
            const formCrearUsuario = document.getElementById('formCrearUsuario');
            if(formCrearUsuario){
                formCrearUsuario.addEventListener('submit', function(e){
                    e.preventDefault();
                    
                    // Validación del lado del cliente
                    const nombreCompleto = document.querySelector('input[name="nombre_completo"]').value.trim();
                    const usuario = document.querySelector('input[name="usuario"]').value.trim();
                    const password = document.querySelector('input[name="password"]').value;
                    const passwordConfirm = document.querySelector('input[name="password_confirmation"]').value;
                    const rolId = document.querySelector('select[name="rol_id"]').value;
                    const sucursalId = document.querySelector('select[name="sucursal_id"]').value;
                    const estado = document.querySelector('select[name="estado"]').value;
                    
                    // Validaciones previas
                    if(!nombreCompleto){
                        Swal.fire('Campo requerido', 'Por favor ingresa el nombre completo', 'warning');
                        return;
                    }
                    if(!usuario){
                        Swal.fire('Campo requerido', 'Por favor ingresa el nombre de usuario', 'warning');
                        return;
                    }
                    if(!password){
                        Swal.fire('Campo requerido', 'Por favor ingresa una contraseña', 'warning');
                        return;
                    }
                    if(password.length < 6){
                        Swal.fire('Contraseña débil', 'La contraseña debe tener al menos 6 caracteres', 'warning');
                        return;
                    }
                    if(password !== passwordConfirm){
                        Swal.fire('Contraseñas no coinciden', 'Las contraseñas deben ser iguales', 'warning');
                        return;
                    }
                    if(!rolId){
                        Swal.fire('Campo requerido', 'Por favor selecciona un rol', 'warning');
                        return;
                    }
                    if(!sucursalId){
                        Swal.fire('Campo requerido', 'Por favor selecciona una sucursal', 'warning');
                        return;
                    }
                    if(!estado){
                        Swal.fire('Campo requerido', 'Por favor selecciona un estado', 'warning');
                        return;
                    }
                    
                    // Enviar formulario si pasa validaciones
                    const formData = new FormData(this);
                    
                    fetch("{{ route('usuarios.store') }}", {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if(!response.ok){
                            const contentType = response.headers.get('content-type');
                            if(contentType && contentType.includes('application/json')){
                                return response.json().then(data => {
                                    throw data;
                                });
                            } else {
                                throw { 
                                    message: 'Error en el servidor. Por favor contacta al administrador.'
                                };
                            }
                        }
                        return response.json();
                    })
                    .then(data => {
                        try {
                            bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                        } catch(e) {
                            // Modal ya cerrado
                        }
                        Swal.fire('¡Éxito!', 'Usuario registrado correctamente', 'success').then(() => {
                            location.reload();
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        if(error.errors){
                            let mensajes = '<ul style="text-align: left;">';
                            for(let campo in error.errors){
                                const msgs = Array.isArray(error.errors[campo]) ? error.errors[campo] : [error.errors[campo]];
                                msgs.forEach(msg => {
                                    mensajes += '<li>' + msg + '</li>';
                                });
                            }
                            mensajes += '</ul>';
                            Swal.fire({
                                title: 'Error en la validación',
                                html: mensajes,
                                icon: 'error',
                                confirmButtonText: 'Aceptar'
                            });
                        } else if(error.message){
                            const detalle = error.error ? ('\n\nDetalle: ' + error.error) : '';
                            Swal.fire('Error', error.message + detalle, 'error');
                        } else {
                            Swal.fire('Error desconocido', 'No se pudo guardar el usuario. Por favor intenta de nuevo.', 'error');
                        }
                    });
                });
            }
        })
        .catch(error => {
            console.error(error);
            Swal.fire('Error', 'No se pudo cargar el formulario de creación', 'error');
        });
});
</script>

<!-- SCRIPT MODAL EDITAR -->
<script>
document.addEventListener('click', function(e){
    if(e.target.closest('.btnEditarUsuario')){
        e.preventDefault();
        const button = e.target.closest('.btnEditarUsuario');
        const id = button.dataset.id;

        fetch(`/usuarios/${id}/edit?t=${Date.now()}`, {
            cache: 'no-store'
        })
            .then(res => res.text())
            .then(html => {
                const modalEditElement = document.getElementById('modalUsuarioEdit');
                const modalContentEdit = document.getElementById('modalContentEdit');

                modalContentEdit.innerHTML = html;
                new bootstrap.Modal(modalEditElement).show();
                initUsuarioBodegaConsumoState(modalContentEdit);

                const formEditarUsuario = modalContentEdit.querySelector('#formEditarUsuario') || modalContentEdit.querySelector(`form[action*="/usuarios/${id}"]`);
                const password = formEditarUsuario?.querySelector('#password');
                const confirmPassword = formEditarUsuario?.querySelector('#password_confirmation');

                if(password && confirmPassword){
                    function validarPassword(){
                        if(confirmPassword.value === ""){
                            confirmPassword.setCustomValidity("");
                            return;
                        }
                        if(password.value !== confirmPassword.value){
                            confirmPassword.setCustomValidity("Las contraseñas no coinciden");
                        } else {
                            confirmPassword.setCustomValidity("");
                        }
                    }
                    password.addEventListener('input', validarPassword);
                    confirmPassword.addEventListener('input', validarPassword);
                }

                // Manejador del formulario de editar usuario
                if(formEditarUsuario && !formEditarUsuario.dataset.boundSubmit){
                    formEditarUsuario.dataset.boundSubmit = '1';

                    formEditarUsuario.addEventListener('submit', function(e){
                        e.preventDefault();

                        if (!formEditarUsuario.checkValidity()) {
                            formEditarUsuario.reportValidity();
                            return;
                        }

                        const formData = new FormData(this);

                        // Para formularios multipart en Laravel, POST + _method=PUT es más estable que PUT real.
                        if (!formData.has('_method')) {
                            formData.append('_method', 'PUT');
                        }

                        fetch(formEditarUsuario.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => {
                            if(!response.ok){
                                const contentType = response.headers.get('content-type');
                                if(contentType && contentType.includes('application/json')){
                                    return response.json().then(data => {
                                        throw data;
                                    });
                                } else {
                                    return response.text().then(text => {
                                        throw {
                                            message: 'Error en el servidor. Por favor contacta al administrador.',
                                            error: text
                                        };
                                    });
                                }
                            }
                            return response.json();
                        })
                        .then(data => {
                            bootstrap.Modal.getInstance(modalEditElement)?.hide();
                            Swal.fire('¡Éxito!', 'Usuario actualizado correctamente', 'success').then(() => {
                                location.reload();
                            });
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            if(error.errors){
                                let mensajes = '<ul style="text-align: left;">';
                                for(let campo in error.errors){
                                    const msgs = Array.isArray(error.errors[campo]) ? error.errors[campo] : [error.errors[campo]];
                                    msgs.forEach(msg => {
                                        mensajes += '<li>' + msg + '</li>';
                                    });
                                }
                                mensajes += '</ul>';
                                Swal.fire({
                                    title: 'Error en la validación',
                                    html: mensajes,
                                    icon: 'error',
                                    confirmButtonText: 'Aceptar'
                                });
                            } else if(error.message){
                                const detalle = error.error ? ('<div class="small text-start mt-2">' + String(error.error).slice(0, 500) + '</div>') : '';
                                Swal.fire({
                                    title: 'Error',
                                    html: error.message + detalle,
                                    icon: 'error'
                                });
                            } else {
                                Swal.fire('Error desconocido', 'No se pudo actualizar el usuario. Por favor intenta de nuevo.', 'error');
                            }
                        });
                    });
                }
            })
            .catch(err => {
                Swal.fire('Error', 'No se pudo cargar el formulario de edición', 'error');
                console.error(err);
            });
    }
});
</script>

<!-- SCRIPT ELIMINAR -->
<script>
document.addEventListener('click', function(e){
    if(e.target.closest('.btnEliminarUsuario')){
        const button = e.target.closest('.btnEliminarUsuario');
        const id = button.dataset.id;

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result)=>{
            if(result.isConfirmed){
                fetch(`/usuarios/${id}`, {
                    method:'DELETE',
                    headers:{
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept':'application/json'
                    }
                })
                .then(response => {
                    if(!response.ok){
                        return response.json().then(data => {
                            throw data;
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.fire(
                        '¡Eliminado!',
                        'El usuario ha sido eliminado correctamente',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                })
                .catch(error => {
                    console.error('Error al eliminar:', error);
                    Swal.fire(
                        'Error',
                        error.message || 'No se pudo eliminar el usuario. Por favor intenta de nuevo.',
                        'error'
                    );
                });
            }
        });
    }
});
</script>
@endsection