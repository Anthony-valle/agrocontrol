<!-- modules/movimientos/entrada.blade.php -->
@extends('layouts.main')

@section('titulo', 'Registrar Entrada')

@section('contenido')
<main id="main" class="main">

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Registrar Entrada</h5>

            <form action="{{ route('movimientos.entrada.store') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label>Insumo</label>
                    <select name="insumo_id" class="form-control" required>
                        @foreach($insumos as $insumo)
                            <option value="{{ $insumo->id }}">{{ $insumo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label>Bodega</label>
                    <select name="bodega_id" class="form-control" required>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label>Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" step="0.01" required>
                </div>
                <div class="mb-2">
                    <label>Precio Unitario</label>
                    <input type="number" name="precio_unitario" class="form-control" step="0.01" required>
                </div>
                <div class="mb-2">
                    <label>Proveedor</label>
                    <input type="text" name="proveedor" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Factura / Referencia</label>
                    <input type="text" name="factura" class="form-control">
                </div>

                <button class="btn btn-success">Registrar Entrada</button>
            </form>
        </div>
    </div>

</main>
@endsection