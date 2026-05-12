<form action="{{ route('insumos.salida', $insumo) }}" method="POST">
    @csrf

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
        <label>Motivo</label>
        <textarea name="motivo" class="form-control"></textarea>
    </div>

    <div class="mb-2">
        <label>Fecha de salida</label>
        <input type="date" name="fecha_salida" class="form-control" value="{{ date('Y-m-d') }}" required>
    </div>

    <button class="btn btn-danger">Registrar Salida</button>
</form>