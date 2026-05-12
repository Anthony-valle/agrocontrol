<form action="{{ route('insumos.traslado') }}" method="POST">
    @csrf

    <div class="mb-2">
        <label>Insumo</label>
        <select name="insumo_id" class="form-control" required>
            @foreach($insumos as $i)
                <option value="{{ $i->id }}">{{ $i->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label>Bodega Origen</label>
        <select name="bodega_origen_id" class="form-control" required>
            @foreach($bodegas as $b)
                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label>Bodega Destino</label>
        <select name="bodega_destino_id" class="form-control" required>
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
        <label>Motivo / Referencia</label>
        <input type="text" name="descripcion" class="form-control">
    </div>

    <button class="btn btn-warning">Registrar Traslado</button>
</form>