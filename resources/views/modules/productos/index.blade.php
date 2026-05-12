@extends('layouts.main')

@section('content')
<main id="main" class="main">

    <div class="pagetitle">
      <h1>Productos</h1>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-lg-12">

          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Lista de Productos</h5>

              <!-- Botón para abrir modal -->
              <div class="mb-2 text-end">
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#miModalFormulario">
                      <i class="bi bi-plus-circle"></i> Nuevo producto
                  </button>
              </div>

              <!-- Modal -->
              <div class="modal fade" id="miModalFormulario" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                      <div class="modal-content shadow">
                          <div class="modal-body">
                              @include('modules.productos.create')
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Tabla de productos -->
              <table class="table datatable">
                  <thead>
                      <tr>
                          <th>ID</th>
                          <th>CODIGO</th>
                          <th>NOMBRE PRODUCTOS</th>
                          <th>INGREDIENTE ACTIVO</th>
                          <th>U.M</th>
                          <th>STOCK</th>
                          <th>PRECIO UNITARIO</th>
                          <th>PROVEEDOR</th>
                          <th>Acciones</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($productos as $producto)
                      <tr>
                          <td>{{ $producto->id }}</td>
                          <td>{{ $producto->codigo_sap }}</td>
                          <td>{{ $producto->nombre_producto }}</td>
                          <td>{{ $producto->ingrediente_activo }}</td>
                          <td>{{ $producto->unidad_medida }}</td>
                          <td>{{ $producto->cantidad }}</td>
                          <td>L {{ agro_number($producto->precio_compra, 2) }}</td>
                          <td>{{ $producto->proveedor }}</td>
                          <td>
                              <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" class="d-inline eliminar-producto">
                                  @csrf
                                  @method('DELETE')
                                  <button type="button" class="btn btn-danger btn-sm btn-eliminar">
                                      <i class="bi bi-trash3"></i>
                                  </button>
                              </form>
                              <a href="{{ route('productos.edit', $producto->id) }}" class="btn btn-warning btn-sm">
                                  <i class="bi bi-pencil-square"></i>
                              </a>
                          </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

</main>
@endsection
