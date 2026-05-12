@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title">{{ $titulo }}: {{ $insumo->nombre }}</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('insumos.entrada', $insumo->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label>Bodega</label>
                    <select name="bodega_id" class="form-control" required>
                        <option value="">Seleccione bodega</option>
                        @foreach($bodegas as $b)
                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Cantidad</label>
                    <input type="number" step="0.01" name="cantidad" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Precio unitario</label>
                    <input type="number" step="0.01" name="precio_unitario" class="form-control" value="{{ $insumo->precio_unitario }}" required>
                </div>

                <div class="mb-3">
                    <label>Proveedor</label>
                    <input type="text" name="proveedor" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Factura / Referencia</label>
                    <input type="text" name="factura" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Fecha de ingreso</label>
                    <input type="date" name="fecha_ingreso" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <button type="submit" class="btn btn-primary">Registrar Entrada</button>
                <a href="{{ route('insumos.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</main>
@endsection