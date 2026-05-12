@extends('layouts.main')

@section('content')
<main id="main" class="main">

  <div class="pagetitle">
    <h1>Editar Producto</h1>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body">

        <h5 class="card-title">Editar Producto</h5>

        <form action="{{ route('productos.update', $producto->id) }}" method="POST">
          @csrf
          @method('PUT')

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control"
                     name="nombre_producto"
                     value="{{ $producto->nombre_producto }}">
            </div>

            <div class="col-md-5 mb-3">
              <label class="form-label">Ingrediente Activo</label>
              <input type="text" class="form-control"
                     name="ingrediente_activo"
                     value="{{ $producto->ingrediente_activo }}">
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Unidad</label>
              <input type="text" class="form-control"
                     name="unidad_medida"
                     value="{{ $producto->unidad_medida }}">
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Cantidad</label>
              <input type="number" class="form-control"
                     name="cantidad"
                     value="{{ $producto->cantidad }}">
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Precio</label>
              <input type="number" step="0.01" class="form-control"
                     name="precio_compra"
                     value="{{ $producto->precio_compra }}">
            </div>
          </div>

          <div class="text-end">
            <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
            <button class="btn btn-warning">
              <i class="bi bi-save"></i> Actualizar
            </button>
          </div>

        </form>

      </div>
    </div>
  </section>

</main>
@endsection
