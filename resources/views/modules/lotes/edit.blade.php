@include('components.modal-header-edit', ['titulo' => 'Editar Lote'])

<form action="{{ route('lotes.update', $lote->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="modal-body px-4 py-3 lote-modal-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Sucursal</label>
                <select name="sucursal_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    @foreach($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" {{ $lote->sucursal_id == $sucursal->id ? 'selected' : '' }}>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Código</label>
                <input type="text" name="codigo" class="form-control" required value="{{ $lote->codigo }}">
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Área (Ha)</label>
                <input type="text" id="area" name="area" class="form-control" readonly required value="{{ $lote->area }}">
                <div class="lote-metric-list">
                    <small id="area-label">{{ agro_number($lote->area, 2) }} Ha</small>
                    <small id="perimetro-label" class="text-muted">Perímetro: 0.00 m</small>
                    <small class="text-muted d-block mt-1 map-manual-hint d-none">Sin mapa puedes corregir el área manualmente.</small>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="{{ $lote->nombre }}">
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold mb-1">Estado</label>
                <select name="estado" class="form-select">
                    <option value="1" {{ $lote->estado == 1 ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ $lote->estado == 0 ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <input type="hidden" name="poligono" id="poligono"
                value='@json($lote->poligono)'
                data-poligono='@json($lote->poligono)'>

            <!-- MAPA -->
            <div class="col-md-12 lote-map-section">
                <label class="form-label small fw-semibold mb-2">Mapa del Lote</label>
                <div class="alert alert-warning d-none map-unavailable-alert mb-3">
                    Google Maps no está disponible en este momento. Puedes actualizar el lote conservando o editando manualmente el área.
                </div>
                <div id="map"></div>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2 lote-map-actions">
                    <button type="button" class="btn btn-success btn-sm map-action-btn" onclick="activarDibujo()">Dibujar</button>
                    <button type="button" class="btn btn-info btn-sm text-white map-action-btn" onclick="iniciarTracking(this)">Caminar lote</button>
                    <button type="button" class="btn btn-primary btn-sm map-action-btn" onclick="mostrarUbicacion(this)">Mi ubicación</button>
                    <button type="button" class="btn btn-secondary btn-sm map-action-btn" onclick="deshacer()">Deshacer</button>
                    <button type="button" class="btn btn-danger btn-sm map-action-btn" onclick="resetMapa()">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </div>
</form>

<style>
.lote-modal-body {
    padding-top: 0.75rem;
}

.lote-modal-body .row {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 0.45rem;
}

.lote-modal-body .form-label {
    margin-bottom: 0.2rem !important;
    font-size: 0.82rem;
    color: #4f5d5a;
    letter-spacing: 0.01em;
}

.lote-metric-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.15rem 0.8rem;
    align-items: center;
    padding-top: 0.15rem;
}

.lote-metric-list .map-manual-hint {
    flex-basis: 100%;
}

#map {
    width: 100%;
    height: clamp(280px, 42vh, 390px);
    min-height: 280px;
    max-height: 390px;
    border-radius: 12px;
    border: 1px solid #dbe5df;
    overflow: hidden;
}

.lote-modal-body .form-control,
.lote-modal-body .form-select {
    border-color: #d7dfeb;
    min-height: 36px;
    padding: 0.38rem 0.75rem;
    font-size: 0.92rem;
    border-radius: 8px;
    box-shadow: none;
    background-color: #fff;
}

.lote-modal-body .form-control:focus,
.lote-modal-body .form-select:focus {
    border-color: #6ea58e;
    box-shadow: 0 0 0 0.2rem rgba(15, 90, 67, 0.12);
}

.lote-modal-body small {
    display: inline-block;
    line-height: 1.15;
    font-size: 0.75rem;
    margin-top: 0;
}

.lote-modal-body .map-unavailable-alert {
    border-radius: 10px;
    font-size: 0.88rem;
}

.lote-map-section {
    padding-top: 0.35rem;
}

.lote-map-actions {
    margin-top: 0.65rem !important;
}

.lote-map-actions .btn {
    min-width: 92px;
    border-radius: 999px;
    padding: 0.38rem 0.8rem;
}

@media (max-width: 600px) {
    #map {
        height: 32vh;
        min-height: 220px;
    }

    .lote-modal-body {
        padding-inline: 1rem;
    }

    .lote-modal-body .row {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.65rem;
    }

    .lote-metric-list {
        gap: 0.1rem 0.45rem;
    }

    .lote-modal-body small {
        display: block;
    }

    .lote-map-actions .btn {
        min-width: 0;
        width: calc(50% - 0.5rem);
    }

    .modal-footer {
        gap: 0.5rem;
    }

    .modal-footer .btn {
        flex: 1 1 0;
    }
}

</style>