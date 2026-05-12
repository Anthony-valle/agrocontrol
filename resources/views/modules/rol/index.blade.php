@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">

  <div class="pagetitle">
    <h1>{{ $titulo }}</h1>
  </div>

  <section class="section">
    <div class="row">
      <div class="col-lg-12">

        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h5 class="card-title pb-0">Configuración de Roles</h5>

            <!-- Controles: cantidad de registros + buscador + botón -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                  <select id="customPerPage" class="form-select form-select-sm" style="width: auto;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                  </select>
                  <small class="text-muted text-nowrap">registros</small>
                </div>

                <div class="input-group input-group-sm" style="max-width: 250px;">
                  <span class="input-group-text bg-white border-end-0">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                  </span>
                  <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar rol, descripción o ID...">
                </div>
              </div>

              <div>
                <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                  <i class="fa fa-plus me-1"></i> Nuevo Rol
                </button>
              </div>
            </div>

            <!-- Modal Crear -->
            <div class="modal fade" id="modalRol" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content" id="modalContent">
                </div>
              </div>
            </div>

            <!-- Modal Editar -->
            <div class="modal fade" id="modalRolEdit" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content" id="modalContentEdit">
                </div>
              </div>
            </div>

            <div class="table-responsive border rounded">
              <table class="table table-hover w-100 mb-0" id="tablaRoles">
                <thead class="table-light">
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($roles as $item)
                  <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->nombre }}</td>
                    <td>{{ $item->descripcion ?: 'Sin descripción' }}</td>
                    <td class="text-center text-nowrap">
                      <a href="#" class="btn btn-warning btn-sm btnEditarRol" data-id="{{ $item->id }}">
                        <i class="fa-solid fa-pen-to-square"></i>
                      </a>

                      <button type="button" class="btn btn-danger btn-sm btnEliminarRol" data-id="{{ $item->id }}">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<!-- Modal Crear -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const tabla = document.getElementById('tablaRoles');
  const filas = Array.from(tabla.tBodies[0].rows);
  const inputBusqueda = document.getElementById('inputBusqueda');
  const perPageSelect = document.getElementById('customPerPage');
  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  function mostrarErrores(error) {
    if (error && error.errors) {
      let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
      Object.values(error.errors).flat().forEach(msg => {
        mensajes += `<li>${msg}</li>`;
      });
      mensajes += '</ul>';
      Swal.fire({ title: 'Error de validación', html: mensajes, icon: 'error' });
      return;
    }

    Swal.fire('Error', error.message || 'No se pudo procesar la solicitud.', 'error');
  }

  function bindAjaxForm(modalId, contentId, successMessage) {
    const form = document.getElementById(contentId).querySelector('form');
    if (!form || form.dataset.ajaxBound === 'true') return;

    form.dataset.ajaxBound = 'true';
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(form);
      formData.set('_token', csrfToken);
      const requestUrl = new URL(form.action, window.location.origin);
      const relativeAction = `${requestUrl.pathname}${requestUrl.search}`;

      fetch(relativeAction, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
        .then(async response => {
          const data = await response.json().catch(() => ({}));
          if (!response.ok) throw data;
          return data;
        })
        .then(data => {
          bootstrap.Modal.getInstance(document.getElementById(modalId))?.hide();
          Swal.fire('Éxito', data.success || successMessage, 'success').then(() => location.reload());
        })
        .catch(mostrarErrores);
    });
  }

  function mostrarFilas(filasVisibles) {
    filas.forEach(f => {
      f.style.display = 'none';
    });

    filasVisibles.forEach(f => {
      f.style.display = '';
    });
  }

  function filtrarTabla() {
    const texto = inputBusqueda.value.toLowerCase();
    const filtradas = filas.filter(f =>
      Array.from(f.cells).some(c => c.textContent.toLowerCase().includes(texto))
    );

    mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value, 10)));
  }

  inputBusqueda.addEventListener('input', filtrarTabla);
  perPageSelect.addEventListener('change', filtrarTabla);
  mostrarFilas(filas.slice(0, parseInt(perPageSelect.value, 10)));

  document.getElementById('btnAbrirModal').addEventListener('click', function() {
    fetch("{{ route('rol.create') }}")
      .then(response => response.text())
      .then(html => {
        document.getElementById('modalContent').innerHTML = html;
        new bootstrap.Modal(document.getElementById('modalRol')).show();
        bindAjaxForm('modalRol', 'modalContent', 'Rol creado correctamente');
      })
      .catch(error => console.error('Error al cargar el modal:', error));
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.btnEditarRol')) {
      e.preventDefault();
      let button = e.target.closest('.btnEditarRol');
      let id = button.dataset.id;

      fetch(`/rol/${id}/edit`)
        .then(response => response.text())
        .then(html => {
          document.getElementById('modalContentEdit').innerHTML = html;
          new bootstrap.Modal(document.getElementById('modalRolEdit')).show();
          bindAjaxForm('modalRolEdit', 'modalContentEdit', 'Rol actualizado correctamente');
        })
        .catch(error => console.error('Error al cargar el modal:', error));
    }

    if (e.target.closest('.btnEliminarRol')) {
      let button = e.target.closest('.btnEliminarRol');
      let id = button.dataset.id;

      Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(`/rol/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          })
          .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw data;
            return data;
          })
          .then(data => {
            Swal.fire('Eliminado', data.success || 'Rol eliminado correctamente.', 'success')
            .then(() => location.reload());
          })
          .catch(mostrarErrores);
        }
      });
    }
  });
});
</script>

@endsection
